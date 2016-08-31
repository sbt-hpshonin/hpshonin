<?php
App::uses('BatchAppController', 'Controller');
App::uses('PasswordUtil', 'Lib/Utils');

/**
 * MT関連共通
 * @author keiohnishi
 *
*/
class BatchMtController extends BatchAppController {
	var $uses = array('User', 'ProjectUser', 'Project', 'MtProject', 'MtTemplate');

	/*
	 * MTユーザーの格納
	*/
	public function execSaveUser($user_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;
		$this->log(sprintf("ユーザー(%d)保存を開始しました。", $user_id), LOG_INFO);

		try{
			//ユーザーテーブルの読み込み
			$user = $this->User->getUser($user_id);
			if(!$user){
				//ユーザーが存在しないならエラー終了
				$this->log(sprintf("存在しないユーザー(%d)が指定されました。", $user_id), LOG_ERR);
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}

			//ユーザーとmt_authorの関連が正しいことを確認する
			if ($user["User"]["mt_author_id"]) {
				//ユーザーに関連したmt_authorが存在しない場合、関連を削除する
				$rs = $this->EditMtDb->getAuthor($user["User"]["mt_author_id"]);
				$rowCount = $rs->rowCount();

				if (!$rowCount) {
					$this->log("Userとmt_authorの関連が不正です。関連をクリアしました。", LOG_WARNING);
					$user["User"]["mt_author_id"] = null;
					$user["User"]["modified"] = null;
				}
			}

			$userName = $user["User"]["username"];
			$email = $user["User"]["email"];
			$password = PasswordUtil::decode($user["User"]["password"]);
			$this->log("Userパスワードdecodeに成功しました。", LOG_DEBUG);

			//mt_authorをINSERT/UPDATE
			$result = $this->EditMtDb->saveAuthor($user["User"]["mt_author_id"], $userName, $password, $email);
			if($result === AppConstants::RESULT_CD_FAILURE){
				//エラー終了
				$this->log(sprintf("MTユーザーの保存に失敗しました。(%s)", $email), LOG_ERR);
				return $result;
			}

			//mt_authorのidを取得
			if ($user["User"]["mt_author_id"]) {
				$author_id = $user["User"]["mt_author_id"];
				$this->log(sprintf("MTユーザーを更新しました。(author_id=%d)", $author_id), LOG_DEBUG);
			} else {
				$author_id = $this->EditMtDb->lastInsertId();
				$user["User"]["modified"] = null;
				$this->log(sprintf("MTユーザーを追加しました。(author_id=%d)", $author_id), LOG_DEBUG);
			}

			//DELETE-INSERT
			$this->EditMtDb->deleteUsersAssociation($author_id);

			//mt_permission（MT本体に対するパーミッション）をINSERT
			$result = $this->EditMtDb->savePermission($author_id);
			if($result === AppConstants::RESULT_CD_FAILURE){
				//エラー終了
				$this->log(sprintf("MT本体に対するパーミッションの保存に失敗しました。(%s)", $email), LOG_ERR);
				return $result;
			}

			//プロジェクトユーザーテーブルの読み込み（ブログ登録済みのルート）
			$projectUser = $this->ProjectUser->getProjectUser($user_id);
			//登録されているプロジェクトでループ
			foreach($projectUser as $project){
				$mtProject = $this->MtProject->getMtProject($project["ProjectUser"]["project_id"]);
				if (!$mtProject) {
					$this->log(sprintf("プロジェクト(%d)に対するMTブログが存在しません。", $project["ProjectUser"]["project_id"]), LOG_WARNING);
					continue;
				}
				$mt_project_id = $mtProject["MtProject"]["edit_mt_project_id"];
				//ユーザーとプロジェクトのアソシエーション
				$result = $this->EditMtDb->makeAssociation($author_id, $mt_project_id);
				if($result === AppConstants::RESULT_CD_FAILURE){
					//エラー終了
					$this->log(sprintf("MTユーザーとプロジェクトのアソシエーションの保存に失敗しました。(%s)", $email), LOG_ERR);
					return $result;
				}
				//MT管理者とプロジェクトのアソシエーション
				$result = $this->EditMtDb->makeAssociation(1, $mt_project_id);
				if($result === AppConstants::RESULT_CD_FAILURE){
					//エラー終了
					$this->log(sprintf("MT管理者とプロジェクト(%d)のアソシエーションの保存に失敗しました。", $mt_project_id), LOG_ERR);
					return $result;
				}
				$this->log(sprintf("MTユーザーとプロジェクトのアソシエーションを保存しました。(author_id=%d、blog_id=%d)", $author_id, $mt_project_id), LOG_DEBUG);
			}

			//Userテーブルにmt_authorのidを格納する（Userは別トランザクションなので最後に行っている）
			$user["User"]["mt_author_id"] = $author_id;
			if (!$this->User->save($user)) {
				// エラー終了
				$this->log(sprintf("ユーザーとMTユーザーの関連付けに失敗しました。(%s)", $email), LOG_ERR);
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}catch (Exception $e){
			$this->log(sprintf("ユーザーの保存中に予期せぬエラーが発生しました。(%s)", $e), LOG_ERR);
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		$this->log(sprintf("ユーザー(%d)保存が正常終了しました。", $user_id), LOG_INFO);
		return $result;
	}

	/*
	 * MTブログの作成
	*/
	public function execSaveProject($project_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;
		$this->log(sprintf("プロジェクト(%d)保存を開始しました。", $project_id), LOG_INFO);

		try{
			//プロジェクトテーブルの読み込み
			$project = $this->Project->getProject($project_id);
			if(!$project){
				//プロジェクトが存在しないならエラー終了
				$this->log(sprintf("存在しないプロジェクト(%d)が指定されました。", $project_id), LOG_ERR);
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}

			//MTプロジェクトテーブルの読み込み
			$edit_blog_id = null;
// 			$approval_blog_id = null;
// 			$public_blog_id = null;
			$mtProject = $this->MtProject->getMtProject($project_id);
			if($mtProject){
				//登録済みのプロジェクトなのでmt_blogのid（編集、承認、ステージング）を取得
				$edit_blog_id = $mtProject["MtProject"]["edit_mt_project_id"];
// 				$approval_blog_id = $mtProject["MtProject"]["approval_mt_project_id"];
// 				$public_blog_id = $mtProject["MtProject"]["public_mt_project_id"];
				$this->log(sprintf("MTブログを更新します。(edit_blog_id=%d)", $edit_blog_id), LOG_DEBUG);
			}

			$blog_name = $project["Project"]["site_name"];

			//編集用mt_blogをINSERT/UPDATE
			//￥をエスケープしてパスを生成
			$edit_blog_path = str_replace("\\", "\\\\", sprintf(Batch::MT_EDIT_PATH, $project["Project"]["site_url"]));
			$edit_blog_url = sprintf(Batch::MT_EDIT_URL, $project["Project"]["site_url"]);
			$result = $this->EditMtDb->saveBlog($edit_blog_id, $blog_name, $edit_blog_path, $edit_blog_url);
			if($result === AppConstants::RESULT_CD_FAILURE){
				//エラー終了
				$this->log(sprintf("編集用ブログの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
				return $result;
			}

			//編集用mt_blogのidを取得
			if (!$edit_blog_id) {
				$edit_blog_id = $this->EditMtDb->lastInsertId();
				$this->log(sprintf("MTブログを追加しました。(編集用blog_id=%d)", $edit_blog_id), LOG_DEBUG);
			}

			//承認用mt_blogをINSERT/UPDATE
			//￥をエスケープしてパスを生成
// 			$approval_blog_path = str_replace("\\", "\\\\", sprintf(Batch::MT_APPROVAL_PATH, $project["Project"]["site_url"]));
// 			$approval_blog_url = sprintf(Batch::MT_APPROVAL_URL, $project["Project"]["site_url"]);
// 			$result = $this->ApprovalMtDb->saveBlog($approval_blog_id, $blog_name.'(承認用)', $approval_blog_path, $approval_blog_url);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("承認用ブログの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
// 				return $result;
// 			}

			//承認用mt_blogのidを取得
// 			if (!$approval_blog_id) {
// 				$approval_blog_id = $this->ApprovalMtDb->lastInsertId();
// 				$this->log(sprintf("MTブログを追加しました。(承認用blog_id=%d)", $approval_blog_id), LOG_DEBUG);
// 			}

			//ステージング用mt_blogをINSERT/UPDATE
			//￥をエスケープしてパスを生成
// 			$public_blog_path = str_replace("\\", "\\\\", sprintf(Batch::MT_STAGING_PATH, $project["Project"]["site_url"]));
// 			$public_blog_url = sprintf(Batch::MT_STAGING_URL, $project["Project"]["site_url"]);
// 			$result = $this->ApprovalMtDb->saveBlog($public_blog_id, $blog_name, $public_blog_path, $public_blog_url);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("ステージング用ブログの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
// 				return $result;
// 			}

			//ステージング用mt_blogのidを取得
// 			if (!$public_blog_id) {
// 				$public_blog_id = $this->ApprovalMtDb->lastInsertId();
// 				$this->log(sprintf("MTブログを追加しました。(ステージング用blog_id=%d)", $public_blog_id), LOG_DEBUG);
// 			}

			//MT管理者と編集用プロジェクトのアソシエーション
			$result = $this->EditMtDb->makeAssociation(1, $edit_blog_id);
			if($result === AppConstants::RESULT_CD_FAILURE){
				//エラー終了
				$this->log(sprintf("MT管理者と編集用プロジェクトのアソシエーションの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
				return $result;
			}

			//MT管理者と承認用プロジェクトのアソシエーション
// 			$result = $this->ApprovalMtDb->makeAssociation(1, $approval_blog_id);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("MT管理者と承認用プロジェクトのアソシエーションの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
// 				return $result;
// 			}

			//MT管理者とステージング用プロジェクトのアソシエーション
// 			$result = $this->ApprovalMtDb->makeAssociation(1, $public_blog_id);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("MT管理者とステージング用プロジェクトのアソシエーションの保存に失敗しました。(%s)", $blog_name), LOG_ERR);
// 				return $result;
// 			}

			//データベース名取得
			$database = $this->Project->getDatabaseName();

			//MTテンプレート雛形テーブルを編集用MTにコピー
			$result = $this->EditMtDb->saveTemplate($edit_blog_id, $database);
			if($result === AppConstants::RESULT_CD_FAILURE){
				//エラー終了
				$this->log(sprintf("編集用MTテンプレートの保存に失敗しました。ブログID(%d)", $edit_blog_id), LOG_ERR);
				return $result;
			}

			//MTテンプレート雛形テーブルを承認用MTにコピー
// 			$result = $this->ApprovalMtDb->saveTemplate($approval_blog_id, $database);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("承認用MTテンプレートの保存に失敗しました。ブログID(%d)", $approval_blog_id), LOG_ERR);
// 				return $result;
// 			}

			//MTテンプレート雛形テーブルをステージング用MTにコピー
// 			$result = $this->ApprovalMtDb->saveTemplate($public_blog_id, $database);
// 			if($result === AppConstants::RESULT_CD_FAILURE){
// 				//エラー終了
// 				$this->log(sprintf("ステージング用MTテンプレートの保存に失敗しました。ブログID(%d)", $public_blog_id), LOG_ERR);
// 				return $result;
// 			}

			//MTブログ公開フォルダの作成
			$editBlogSitePath = $this->EditMtDb->getBlogSitePath($edit_blog_id);
// 			$approvalBlogSitePath = $this->ApprovalMtDb->getBlogSitePath($approval_blog_id);
// 			$publicBlogSitePath = $this->ApprovalMtDb->getBlogSitePath($public_blog_id);
			if (FileUtil::mkdir($editBlogSitePath) === false) {
				$this->log('編集用MTブログ公開フォルダの作成に失敗しました。フォルダ名：[' . $editBlogSitePath . ']', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
// 			if (FileUtil::mkdir($approvalBlogSitePath) === false) {
// 				$this->log('承認用MTブログ公開フォルダの作成に失敗しました。フォルダ名：[' . $approvalBlogSitePath . ']', LOG_ERR);
// 				return AppConstants::RESULT_CD_FAILURE;
// 			}
// 			if (FileUtil::mkdir($publicBlogSitePath) === false) {
// 				$this->log('ステージング用MTブログ公開フォルダの作成に失敗しました。フォルダ名：[' . $publicBlogSitePath . ']', LOG_ERR);
// 				return AppConstants::RESULT_CD_FAILURE;
// 			}

			//MtProjectテーブルにmt_blogのidを格納する（MtProjectは別トランザクションなので最後に行っている）
			if(!$mtProject){
				$mtProjectData["MtProject"]["project_id"] = $project_id;
				$mtProjectData["MtProject"]["edit_mt_project_id"] = $edit_blog_id;
// 				$mtProjectData["MtProject"]["approval_mt_project_id"] = $approval_blog_id;
// 				$mtProjectData["MtProject"]["public_mt_project_id"] = $public_blog_id;
				$this->MtProject->create();
				if (!$this->MtProject->save($mtProjectData)) {
					// 更新に失敗したのでエラー終了
					$this->log(sprintf("プロジェクトとMTブログの関連付けに失敗しました。(%s)", $blog_name), LOG_ERR);
					$result = AppConstants::RESULT_CD_FAILURE;
					return $result;
				}
			}
		}catch (Exception $e){
			$this->log(sprintf("プロジェクトの保存中に予期せぬエラーが発生しました。(%s)", $e), LOG_ERR);
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		$this->log(sprintf("プロジェクト(%d)保存が正常終了しました。", $project_id), LOG_INFO);
		return $result;
	}
}