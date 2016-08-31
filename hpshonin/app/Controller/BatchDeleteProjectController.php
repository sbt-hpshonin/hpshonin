<?php
App::uses('BatchAppController', 'Controller');

/**
 * MTプロジェクト削除
 * @author keiohnishi
 *
 */
class BatchDeleteProjectController extends BatchAppController {
	var $uses = array('Project', 'MtProject');

	var $id;

	/** スラッシュ */
	const SLASH = '/';

	/*
	 * ユーザーテーブルのidを設定
	*/
	public function setId($id) {
		$this->id = $id;
	}

	/*
	 * 実行
	 */
	public function execute() {
		$result = AppConstants::RESULT_CD_SUCCESS;
		$this->log(sprintf("MTプロジェクト削除バッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTのDBオブジェクト生成
		$this->EditMtDb = $this->Components->load('EditMtDb');
// 		$this->ApprovalMtDb = $this->Components->load('ApprovalMtDb');

		// トランザクション開始
		if ($this->Transaction->begin() === false
		|| $this->EditMtDb->beginTransaction() === false) {
// 		|| $this->ApprovalMtDb->beginTransaction() === false) {
			$this->log('トランザクション開始に失敗しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}

		//MTブログの削除
		$result = $this->execDeleteProject($this->id);

		if($result === AppConstants::RESULT_CD_FAILURE){
			//ロールバック
			if ($this->Components->Transaction->rollback(null) === false
			|| $this->EditMtDb->rollBack() === false) {
// 			|| $this->ApprovalMtDb->rollBack() === false) {
				$this->log('ロールバックに失敗しました。', LOG_ERR);
			}
		} else {
			//コミット
			if($this->Transaction->commit() === false
			|| $this->EditMtDb->commit() === false) {
// 			|| $this->ApprovalMtDb->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
		}

		//データベースサーバへの接続の切断
		$this->EditMtDb->close();
// 		$this->ApprovalMtDb->close();

		$this->log(sprintf("MTプロジェクト削除バッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	/*
	 * MTブログの削除
	*/
	public function execDeleteProject($project_id) {
		$this->log(sprintf("プロジェクト(%d)削除を開始しました。", $project_id), LOG_INFO);

		try{
			//プロジェクトテーブルの読み込み(削除フラグON)
			$project = $this->Project->getProject($project_id, AppConstants::FLAG_ON);
			if(!$project){
				//プロジェクトが存在しないならエラー終了
				$this->log(sprintf("存在しないプロジェクト(%d)が指定されました。または削除指定がありません。", $project_id), LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;;
			}

			$site_url = $project['Project']['site_url'];

			//MTプロジェクトテーブルの読み込み
			$mtProject = $this->MtProject->getMtProject($project_id, AppConstants::FLAG_ON);
			if(!$mtProject){
				//MTプロジェクトが存在しないならエラー終了
				$this->log(sprintf("プロジェクト(%d)とMTブログの関連が存在しません。", $project_id), LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;;
			}

			//mt_blogのid（編集、承認、ステージング）を取得
			$edit_blog_id = $mtProject["MtProject"]["edit_mt_project_id"];
// 			$approval_blog_id = $mtProject["MtProject"]["approval_mt_project_id"];
// 			$public_blog_id = $mtProject["MtProject"]["public_mt_project_id"];

			//mt_blogをDELETE
			$this->EditMtDb->deleteBlog($edit_blog_id);
// 			$this->ApprovalMtDb->deleteBlog($approval_blog_id);
// 			$this->ApprovalMtDb->deleteBlog($public_blog_id);

			//プロジェクトのアソシエーションをDELETE
			$this->EditMtDb->deleteProjectsAssociation($edit_blog_id);
// 			$this->ApprovalMtDb->deleteProjectsAssociation($approval_blog_id);
// 			$this->ApprovalMtDb->deleteProjectsAssociation($public_blog_id);

			//MtProjectテーブルをDELETE（MtProjectは別トランザクションなので最後に行っている）
			if (!$this->MtProject->delete($mtProject["MtProject"]["id"])) {
				// 削除に失敗したのでエラー終了
				$this->log(sprintf("プロジェクトとMTブログの関連削除に失敗しました。(%s)", $site_url), LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;;
			}

			// AppcmdでIISの仮想ディレクトリの削除
			$this->log('仮想ディレクトリの削除', LOG_DEBUG);
			$result = shell_exec('WinRS -r:' . AppConstants::APPCMD_SERVER
					. ' -u:' . AppConstants::APPCMD_SERVER_USER
					. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
					. ' %windir%\system32\inetsrv\appcmd delete vdir '
					. self::SLASH . 'vdir.name:"' . AppConstants::APPCMD_SITE_NAME . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url . '" ');

			$this->log('仮想ディレクトリの存在チェック', LOG_DEBUG);
			// AppcmdでIISの階層ディレクトリ状況を取得
			$result = shell_exec('WinRS -r:' . AppConstants::APPCMD_SERVER
					. ' -u:' . AppConstants::APPCMD_SERVER_USER
					. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
					. ' %windir%\system32\inetsrv\appcmd list vdir "'
					. AppConstants::APPCMD_SITE_NAME . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url . '" ' . self::SLASH . 'xml');

			// 取得したXMLをパース
			$vdir = simplexml_load_string($result);
			// 仮想ディレクトリ名を取得
			$vdirName = $vdir->VDIR[0]['path'];

			// 仮想ディレクトリが存在しない場合スラッシュが返る
			if($vdirName != self::SLASH) {
				$this->log('仮想ディレクトリの削除に失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
		}catch (Exception $e){
			$this->log(sprintf("プロジェクトの削除中に予期せぬエラーが発生しました。(%s)", $e), LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}

		$this->log(sprintf("プロジェクト(%d)削除が正常終了しました。", $project_id), LOG_INFO);
		return AppConstants::RESULT_CD_SUCCESS;
	}
}
?>