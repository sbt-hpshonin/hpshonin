<?php

App::uses('BatchPackageController', 'Controller');
App::uses('Package', 'Model');
App::uses('MtImageFile', 'Model');
App::uses('Status', 'Lib');
App::uses('AppConstants', 'Lib/Constants');
App::uses('FileUtil', 'Lib/Utils');
App::uses('StringUtil', 'Lib/Utils');

/**
 * 公開ブログパッケージ作成バッチ
 *
 * @author tasano
 *
*/
class BatchCreateBlogPackagePublishController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::CREATE;
		// バッチ名を設定
		$this->batch_name = '公開ブログパッケージ作成バッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_PACKAGE_ENTRY; // '01'(パッケージ登録);
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_PACKAGE_READY_REJECT; // '91'(パッケージ登録却下)
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {

		$this->log('公開ブログパッケージ作成(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		// ---------
		// 事前準備
		// ---------

		// 作業領域リスト
		$temp_list = array();

		// パッケージを取得
		$package = $this->Package->findByPackageId($this->id);
		if(empty($package)) {
			$this->log('パッケージ情報の取得失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($package, LOG_DEBUG);

		// 公開予定日を取得
		$public_due_date = $package['Package']['public_due_date'];
		$this->log('公開予定日：[' . $public_due_date . ']', LOG_DEBUG);

		// プロジェクトIDを取得
		$project_id = $package['Project']['id'];
		$this->log('プロジェクトID：[' . $project_id . ']', LOG_DEBUG);

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// MTプロジェクトを取得
		$mtProject = $this->MtProject->getMtProject($project_id);
		if(empty($mtProject)) {
			$this->log('MTプロジェクトの取得失敗しました。プロジェクトID：[' . $project_id . ']', LOG_ERR);
			return false;
		}
		//$this->log($mtProject, LOG_DEBUG);

		// 承認用MTプロジェクトIDを取得
		$approval_mt_project_id = $mtProject['MtProject']['approval_mt_project_id'];
		$this->log('承認用MTプロジェクトID：[' . $approval_mt_project_id . ']', LOG_DEBUG);

		// ブログ用パッケージフォルダを編集
		$blog_package_dir = AppConstants::DIRECTOR_BLOG_IMAGE_PATH  . DS . $this->id . DS . $site_url . DS . 'blog';
		$this->log('ブログ用パッケージフォルダ：[' . $blog_package_dir . ']', LOG_DEBUG);

		// 承認用フォルダ(再構築先)を編集
		$blog_approval_dir1 = AppConstants::DIRECTOR_APPROVAL_PATH  . DS . $site_url . DS . 'blog';
		if (FileUtil::mkdir($blog_approval_dir1) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_approval_dir1 . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ(再構築先)：[' . $blog_approval_dir1 . ']', LOG_DEBUG);

		// 承認用フォルダ(保存先)を編集
		$blog_approval_dir2 = AppConstants::DIRECTOR_APPROVAL_PATH  . DS . $this->id  . DS . $site_url . DS. 'blog';
		if (FileUtil::mkdir($blog_approval_dir2) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ(保存先)：[' . $blog_approval_dir2 . ']', LOG_DEBUG);

		// パッケージIDをキーに、MT記事リストを取得
		$mt_post_list = $this->MtPost->findListByPackageId($this->id);
		if (empty($mt_post_list)) {
			$this->log('MT記事情報の取得に失敗しました。[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($mt_post_list, LOG_DEBUG);

		// 事前に必要な情報を取得
		foreach ($mt_post_list as $mt_post) {

			// 編集用MT記事IDを取得
			$edit_mt_post_id = $mt_post['MtPost']['edit_mt_post_id'];
			$this->log('編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// 件名を取得
			$subject = $mt_post['MtPost']['subject'];
			$this->log('件名：[' . $subject . ']', LOG_DEBUG);

			// 記事内容を取得
			$contents = $mt_post['MtPost']['contents'];
			$this->log('記事内容：[' . $contents . ']', LOG_DEBUG);

			// 記事更新日時を取得
			$post_modified = $mt_post['MtPost']['post_modified'];
			$this->log('記事内容：[' . $post_modified . ']', LOG_DEBUG);

			// 更新フラグを取得
			$modify_flg = $mt_post['MtPost']['modify_flg'];
			$this->log('更新フラグ：[' . $modify_flg . ']', LOG_DEBUG);

			// 記事内容moreを取得
			$contents_more = $mt_post['MtPost']['contents_more'];
			$this->log('記事内容more：[' . $contents_more . ']', LOG_DEBUG);

			// 編集用MT記事IDをキーに、MTマッピングを取得
			$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
			if (empty($mt_mapping)) {
				$this->log('MTマッピング情報の取得に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
			}
			//$this->log($mt_mapping, LOG_DEBUG);

			// 承認用MT記事IDを取得
			$approval_mt_post_id = $mt_mapping['MtMapping']['approval_mt_post_id'];
			$this->log('承認用MT記事ID：[' . $approval_mt_post_id . ']', LOG_DEBUG);

			// 公開用MT記事IDを取得
			$public_mt_post_id = $mt_mapping['MtMapping']['public_mt_post_id'];
			$this->log('公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// 作業領域へ退避
			$temp = array();
			$temp['edit_mt_post_id']		= $edit_mt_post_id;		// 編集用MT記事ID
			$temp['subject']	 			= $subject;				// 件名
			$temp['contents']	 			= $contents;			// 記事内容
			$temp['post_modified']			= $post_modified;		// 記事更新日時
			$temp['modify_flg']	 			= $modify_flg;			// 更新フラグ
			$temp['contents_more']	 		= $contents_more;		// 記事内容more
			$temp['approval_mt_post_id']	= $approval_mt_post_id;	// 承認用MT記事ID
			$temp['public_mt_post_id']		= $public_mt_post_id;	// 公開用MT記事ID
			$temp_list[] = $temp;
		}
		$this->log($temp_list, LOG_DEBUG);

		// --------------------------------------------
		// ② 同一記事に差分があるか記事内容をチェック
		// --------------------------------------------
		$this->log('② 同一記事に差分があるか記事内容をチェック', LOG_DEBUG);

		foreach ($temp_list as $temp) {

			if (!empty($temp['public_mt_post_id'])) {

				// Temp.記事内容を取得
				$contents = $temp['contents'];
				$this->log('Temp.記事内容：[' . $contents . ']', LOG_DEBUG);

				// Temp.公開用MT記事IDを取得
				$public_mt_post_id = $temp['public_mt_post_id'];
				$this->log('Temp.公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

				// 公開用MTの記事を取得
				$result = $this->mt->getPost($public_mt_post_id, false);
				if ($result === false) {
					$this->log('記事取得に失敗しました。', LOG_ERR);
					$this->log('  記事ID：[' .$public_mt_post_id  . ']', LOG_ERR);
					return false;
				}

				// 記事の件名を取得
				$title = $result['title'];
				$this->log('記事の件名：[' . $title . ']', LOG_DEBUG);

				// 記事の本文を取得
				$description = $result['description'];
				$this->log('記事の本文：[' . $description . ']', LOG_DEBUG);

				// 記事内容を比較
				$this->log('文字列サイズ①：[' . strlen($contents) . ']', LOG_DEBUG);
				for ($i = 0; $i < strlen($contents); $i++) {
					$hex1 = $hex1 . '[' . bin2hex(substr($contents, $i, 1)) . ']';
				}
				$this->log('文字列16進数①[' . $hex1 . ']',LOG_ERR);

				$this->log('文字列サイズ②：[' . strlen($description) . ']', LOG_DEBUG);
				for ($i = 0; $i < strlen($description); $i++) {
					$hex2 = $hex2 . '[' . bin2hex(substr($description, $i, 1)) . ']';
				}
				$this->log('文字列16進数②[' . $hex2 . ']',LOG_ERR);

				// 改行コードを統一
				$contents2    = StringUtil::replaceLineFeedCode($contents);
				$description2 = StringUtil::replaceLineFeedCode($description);

				// 全ての画像が変更されていないか
				$notAtAll = $this->judgeNotModifyAtAll($this->id, $public_mt_post_id);

				// 内容比較
				if ((StringUtil::compare($contents2, $description2) === 0) && ($notAtAll === true)) {
					$this->log('記事内容に差異がありません。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
					// メッセージを設定
					$this->message = StringUtil::getMessage2(MsgConstants::ERROR_NO_CHANGE_POST, $public_mt_post_id, $title);
					return false; // 業務エラー
				}
			}
		}

		foreach ($temp_list as $temp) {

			// ------------------------------------------
			// ④ パッケージ登録された内容及び画像を登録
			// ------------------------------------------
			$this->log('④ パッケージ登録された内容及び画像を登録', LOG_DEBUG);

			// Temp.件名を取得
			$subject = $temp['subject'];
			$this->log('Temp.件名：[' . $subject . ']', LOG_DEBUG);

			// Temp.記事内容を取得
			$contents = $temp['contents'];
			$this->log('Temp.記事内容：[' . $contents . ']', LOG_DEBUG);

			// Temp.記事更新日時を取得
			$post_modified = $temp['post_modified'];
			$this->log('Temp.記事更新日時：[' . $post_modified . ']', LOG_DEBUG);

			// Temp.記事内容moreを取得
			$contents_more = $temp['contents_more'];
			$this->log('Temp.記事内容more：[' . $contents_more . ']', LOG_DEBUG);

			// Temp.編集用MT記事IDを取得
			$edit_mt_post_id = $temp['edit_mt_post_id'];
			$this->log('Temp.編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// Temp.承認用MT記事IDを取得
			$approval_mt_post_id = $temp['approval_mt_post_id'];
			$this->log('Temp.承認用MT記事ID：[' . $approval_mt_post_id . ']', LOG_DEBUG);

			// Temp.公開用MT記事IDを取得
			$public_mt_post_id = $temp['public_mt_post_id'];
			$this->log('Temp.公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// 承認用MT記事IDが設定されていない場合⇒登録
			if (empty($approval_mt_post_id)) {

				// 記事登録 ⇒ 承認用MT
				$post_id = $this->mt->newPost($approval_mt_project_id, // ブログID：承認用MTプロジェクトID
											   $subject,			   // 件名
											   $contents,			   // 本文
//											   $post_modified,		   // 投稿日時：記事更新日時
											   $public_due_date,	   // 投稿日時：公開予定日
											   $contents_more,		   // 追記
											   false);				   // 編集用MTフラグ
				if(post_id < 0) {
					$this->log('記事登録に失敗しました。ブログID：[' . $approval_mt_project_id . ']', LOG_ERR);
					return false;
				}

				// 新たに作成された記事IDを退避
				$this->log('新しい記事ID：[' . $post_id . ']', LOG_DEBUG);
				$approval_mt_post_id = $post_id;
				$temp['approval_mt_post_id'] = $approval_mt_post_id;

				// MTマッピング同期
				$this->MtMapping->updatePostId($edit_mt_post_id,				// 編集用MT記事ID
											   $post_id,						// 承認用MT記事ID
											   $public_mt_post_id,				// 公開用MT記事ID
											   AppConstants::USER_ID_SYSTEM);	// ユーザID

			// 承認用MT記事IDが設定されている場合⇒更新
			} else {

				// 記事更新 ⇒ 承認用MT
				$result = $this->mt->editPost($approval_mt_post_id, // 承認用MT記事ID
											  $subject,				// 件名
											  $contents,			// 本文
//											  $post_modified,		// 投稿日時：記事更新日時
											  $public_due_date,	    // 投稿日時：公開予定日
											  $contents_more,		// 追記
											  false);				// 編集用MTフラグ
				if (!$result) {
					$this->log('記事更新に失敗しました。記事ID：[' . $approval_mt_post_id . ']', LOG_ERR);
					return false;
				}
			}

			// --------------------
			// ⑥ サイト再構築実施
			// --------------------
			$this->log('⑥ サイト再構築実施', LOG_DEBUG);

			// サイト再構築 ⇒ 承認用フォルダ
			$result = $this->mt->publishPost($approval_mt_post_id, false);
			if(!$result) {
				$this->log('サイト再構築実施に失敗しました。記事ID：[' . $approval_mt_post_id . ']', LOG_ERR);
				return false;
			}

		}

		// ----------------------------------------------------------------------------------
		// ブログパッケージ登録で、承認用フォルダにステージング用フォルダの画像をコピーする。
		// -----------------------------------------------------------------------------------
		if ($this->copyImagesFromStaging($package) === false) {
			$this->log('承認用フォルダにステージング用フォルダの画像のコピーに失敗しました。', LOG_ERR);
			return false;
		}

		// --------------------------------------------------------------------
		// 画像コピー ブログ用パッケージ用フォルダ ⇒ 承認用フォルダ(再構築先)
		// --------------------------------------------------------------------
		if (FileUtil::dirCopy($blog_package_dir, $blog_approval_dir1) === false) {
			$this->log('ファイルのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $blog_package_dir . ']', LOG_ERR);
			$this->log('  コピー先：[' . $blog_approval_dir1 . ']', LOG_ERR);
			return false;
		}

		// ---------------------------------------------------------
		// ⑦ HTMLファイルで指定されるているURLを承認用に書き換える
		// ---------------------------------------------------------
		$this->log('⑦ HTMLファイルで指定されるているURLを承認用に書き換える', LOG_DEBUG);

		// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
		$tartget = self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/(サイトURL)/blog)
		$replace1 = self::APPROVAL_SITE_URL .  self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後：[' . $replace1 . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_approval_dir1, $tartget, $replace1, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換①に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_approval_dir1 . ']', LOG_ERR);
			return false;
		}

		// ---------------------------------------------------
		// 承認用フォルダ(再構築先) ⇒ 承認用フォルダ(保存先)
		// ---------------------------------------------------
		if (FileUtil::dirCopy($blog_approval_dir1, $blog_approval_dir2) === false) {
			$this->log('ファイルのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $blog_approval_dir1 . ']', LOG_ERR);
			$this->log('  コピー先：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}

		// ---------------------------------------------------------
		// ⑦ HTMLファイルで指定されるているURLを承認用に書き換える
		// ---------------------------------------------------------
		$this->log('⑦-2 HTMLファイルで指定されるているURLを承認用に書き換える', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/パッケージID/(サイトURL)/blog)
		$replace2 = self::APPROVAL_SITE_URL . self::SLASH . $this->id . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後②：[' . $replace2 . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_approval_dir2, $replace1, $replace2, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換②に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}

		$this->log('公開ブログパッケージ作成(内部) 成功', LOG_DEBUG);
		return true; // 成功
	}

	/**
	 * 全ての画像の更新日時が変更されていないか判定します。
	 *
	 * @param  unknown $package_id      パッケージID
	 * @param  unknown $edit_mt_post_id 編集者用MT記事ID
	 * @return boolean                  true：全ての画像が変更されていない、false：変更された画像が1件以上ある
	 */
	private function  judgeNotModifyAtAll($package_id, $edit_mt_post_id) {

		$this->log('judgeNotModifyAtAll', LOG_DEBUG);
		$this->log('  $package_id     ：[' . $package_id . ']', LOG_DEBUG);
		$this->log('  $edit_mt_post_id：[' . $edit_mt_post_id . ']', LOG_DEBUG);

		// フラグ
		$notAtAll = true;

		$result = $this->MtImageFile->getFilePathList($package_id, $edit_mt_post_id);

		foreach ($result as $row) {
			$this->log($row, LOG_DEBUG);
			$file_path = $row['file_path'];
			$modified    = $row['modified'];
			if ($modified === true) {
				$notAtAll = false;
			}
		}
		$this->log('$notAtAll：['. $notAtAll . ']', LOG_DEBUG);

		return $notAtAll;
	}

}
?>