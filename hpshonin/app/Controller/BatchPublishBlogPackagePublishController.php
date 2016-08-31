<?php

App::uses('BatchPackageController', 'Controller');

/**
 * 公開ブログパッケージ公開バッチ
 *
 *　(現在未使用)
 * @author tasano
 *
*/
class BatchPublishBlogPackagePublishController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::PUBLISH;
		// バッチ名を設定
		$this->batch_name = '公開ブログパッケージ公開バッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_RELEASE_COMPLETE; // '06'(公開完了)
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_RELEASE_ERROR; // '96'(公開エラー)
		// ブログ画像削除フラグを立てる
		$this->blog_images_delete_flg = true;
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {

		$this->log('公開ブログパッケージ公開 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		// ---------
		// 事前準備
		// ---------

		// パッケージを取得
		$package = $this->Package->findByPackageId($this->id);
		if(empty($package)) {
			$this->log('パッケージの取得失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($package, LOG_DEBUG);

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
		//$this->log($mtProject);

		// 公開用MTプロジェクトIDを取得
		$public_mt_project_id = $mtProject['MtProject']['public_mt_project_id'];
		$this->log('公開用MTプロジェクトID：[' . $public_mt_project_id . ']', LOG_DEBUG);

		// ブログ用パッケージフォルダを編集
		$blog_package_dir = AppConstants::DIRECTOR_BLOG_IMAGE_PATH  . DS . $this->id . DS . $site_url . DS . self::BLOG;
		$this->log('ブログ用パッケージフォルダ：[' . $blog_package_dir . ']', LOG_DEBUG);

		// ステージング用フォルダを編集
		$blog_staging_dir = AppConstants::DIRECTOR_STAGING_PATH  . DS . $site_url . DS . self::BLOG;
		if (FileUtil::mkdir($blog_staging_dir) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_staging_dir . ']', LOG_ERR);
			return false;
		}
		$this->log('ステージング用フォルダ：[' . $blog_staging_dir . ']', LOG_DEBUG);

		// 公開用フォルダ接続確認
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			if (FileUtil::exists($director_publish_path) === false) {
				$this->log('フォルダを認識できません。', LOG_ERR);
				$this->log('  フォルダ名：[' . $director_publish_path . ']', LOG_ERR);
				return false;
			}
		}

		// 公開用フォルダ
		$blog_pulbic_dir_list = array();
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			$blog_pulbic_dir = $director_publish_path . DS . $site_url . DS . self::BLOG;
			if (FileUtil::mkdir($blog_pulbic_dir) === false) {
				$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_pulbic_dir . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用フォルダ：[' . $blog_pulbic_dir . ']', LOG_DEBUG);
			$blog_pulbic_dir_list[] = $blog_pulbic_dir;
		}

		// 公開用作業フォルダ
		$blog_pulbic_work_dir_list = array();
		foreach ($this->getDirectorPublishWorkPathList() as $director_publish_work_path) {
			$blog_pulbic_work_dir = $director_publish_work_path . DS . $site_url . DS . self::BLOG;
			$this->log('公開用作業フォルダ：[' . $blog_pulbic_work_dir . ']', LOG_DEBUG);
			$blog_pulbic_work_dir_list[] = $blog_pulbic_work_dir;
		}

		// ----------------------------------
		// ② パッケージ登録された内容を登録
		// ----------------------------------
		$this->log('② パッケージ登録された内容を登録', LOG_DEBUG);

		// パッケージIDをキーに、MT記事リストを取得
		$mt_post_list = $this->MtPost->findListByPackageId($this->id);
		if (empty($mt_post_list)) {
			$this->log('MT記事の取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($mt_post_list, LOG_DEBUG);

		// MT記事分、繰り返す
		foreach ($mt_post_list as $mt_post) {

			// 編集用MT記事IDを取得
			$edit_mt_post_id = $mt_post['MtPost']['edit_mt_post_id'];
			if (empty($edit_mt_post_id)) {
				$this->log('編集用MT記事IDが設定されていません。パッケージID：[' . $this->id . ']', LOG_ERR);
				return false;
			}
			$this->log('編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// MTマッピング取得
			$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
			if (empty($mt_mapping)) {
				$this->log('MTマッピング情報の取得に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
			}
			//$this->log($mt_mapping, LOG_DEBUG);

			// 承認用MT記事IDを取得
			$approval_mt_post_id = $mt_mapping['MtMapping']['approval_mt_post_id'];
			$this->log('承認用MT記事ID：[' . $approval_mt_post_id . ']', LOG_DEBUG);

			//ログに承認用MTに記事が存在しない警告が表示される

			// 記事の存在確認
			$this->log('記事の存在確認', LOG_DEBUG);

			if ($this->mt->getPost($approval_mt_post_id, false) === false) {

				// 結果詳細を取得
				$result_code = $this->mt->resultCode;
				$this->log('$result_code：[' . $result_code . ']', LOG_DEBUG);

				// 接続失敗
				if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_CONNECT) {
					$this->log('MTサーバとの接続に失敗しました。記事ID：[' . $approval_mt_post_id . ']', LOG_ERR);
					return false;

					// デコード失敗
				} else if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_DECODE) {
					$this->log('デコードに失敗しました。記事ID：[' . $approval_mt_post_id . ']', LOG_ERR);
					return false;

					// 記事が存在しない ※処理継続
				} else if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_FOUND) {
					$this->log('記事が存在しませんでした。記事ID：[' . $approval_mt_post_id . ']', LOG_WARNING);
				}
			}

			// 公開用MT記事IDを取得
			$public_mt_post_id = $mt_mapping['MtMapping']['public_mt_post_id'];
			$this->log('公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// タイトルを取得
			$title = $mt_post['MtPost']['subject'];
			$this->log('タイトル：[' . $title . ']', LOG_DEBUG);

			// 本文を取得
			$description = $mt_post['MtPost']['contents'];
			$this->log('本文：[' . $description . ']', LOG_DEBUG);

			// 更新日時を取得
			$dateCreated =$mt_post['MtPost']['post_modified'];
			$this->log('更新日時：[' . $dateCreated . ']', LOG_DEBUG);

			// 追記を取得
			$mt_text_more = $mt_post['MtPost']['contents_more'];
			$this->log('追記：[' . $dateCreated . ']', LOG_DEBUG);

			// 登録
			if (empty($public_mt_post_id)) {

				// 記事新規登録
				$public_mt_post_id = $this->mt->newPost($public_mt_project_id,	// ブログID：公開用MTプロジェクトID
														 $title,				// 件名
														 $description,			// 本文
														 $dateCreated,			// 投稿日時
														 $mt_text_more,			// 追記
														 false);				// 編集用MTフラグ
				if($public_mt_post_id === false) {
					$this->log('記事登録に失敗しました。ブログID：[' . $public_mt_project_id . ']', LOG_ERR);
					return false;
				}
				$this->log('新しい記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

				// MTマッピング同期
				if($this->MtMapping->updatePostId($edit_mt_post_id, 			// 編集用MT記事ID
												  $approval_mt_post_id, 		// 承認用MT記事ID
												  $public_mt_post_id, 			// 公開用MT記事ID
												  AppConstants::USER_ID_SYSTEM	// ユーザID
												 ) === false) {
					$this->log('MTマッピングの更新に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
					return false;
				}

			// 更新
			} else {

				// 記事更新
				if ($this->mt->editPost($public_mt_post_id,	// 記事ID
										$title,				// 件名
										$description,		// 本文
										$dateCreated,		// 投稿日時
										$mt_text_more,		// 追記
										false				// 編集用MTフラグ
									   ) === false) {
					$this->log('記事更新に失敗しました。記事ID：[' . $public_mt_post_id . ']', LOG_ERR);
					return false;
				}
			}
		}

		// ------------------------------------
		// ③ パッケージに関連する画像をコピー
		// ------------------------------------
		$this->log('③ パッケージに関連する画像をコピー', LOG_DEBUG);

		// 画像コピー(ブログ用パッケージフォルダ ⇒ ステージング用フォルダ)
		if (FileUtil::dirCopy($blog_package_dir, $blog_staging_dir) === false) {
			$this->log('ファイルのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $blog_package_dir . ']', LOG_ERR);
			$this->log('  コピー先：[' . $blog_staging_dir . ']', LOG_ERR);
			return false;
		}

		// --------------------
		// ④ サイト再構築実施
		// --------------------
		$this->log('④ サイト再構築実施', LOG_DEBUG);
		foreach ($mt_post_list as $mt_post) {

			// 編集用MT記事IDを取得
			$edit_mt_post_id = $mt_post['MtPost']['edit_mt_post_id'];
			$this->log('編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// MTマッピング取得
			$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
			if (empty($mt_mapping)) {
				$this->log('MTマッピング情報の取得に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
				return false;
			}
			$this->log($mt_mapping, LOG_DEBUG);

			// 公開用MT記事IDを取得
			$public_mt_post_id = $mt_mapping['MtMapping']['public_mt_post_id'];
			$this->log('公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// 公開用MTを再構築
			if ($this->mt->publishPost($public_mt_post_id, false) === false) {
				$this->log('再構築に失敗しました。公開用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
				return false;
			}
		}

		// ---------------------------------------------------------
		// ⑤ HTMLファイルで指定されるているURLを公開用に書き換える
		// ---------------------------------------------------------
		$this->log('⑤ HTMLファイルで指定されるているURLを公開用に書き換える', LOG_DEBUG);

		// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
		$tartget = self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換前①：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列(http://(公開サイトのURL)/(サイトURL)/blog)
		$replace = self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後①：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_staging_dir, $tartget, $replace, self::EXT_HTML) === false) {
			$this->log('htmlファイルを一括置換①に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_staging_dir . ']', LOG_ERR);
			return false;
		}

		// 公開用フォルダ分、繰り返す
		for ($i = 0; $i < count($blog_pulbic_dir_list); $i++) {

			// 公開用フォルダ
			$blog_pulbic_dir = $blog_pulbic_dir_list[$i];

			// 公開用作業フォルダ
			$blog_pulbic_work_dir = $blog_pulbic_work_dir_list[$i];

			// --------------------------------
			// ⑥ 公開用フォルダに別名でコピー
			// --------------------------------
			$this->log('⑥ 公開用フォルダに別名でコピー', LOG_DEBUG);

			// 公開用作業フォルダをクリーンアップ
			if (FileUtil::rmdirAll($blog_pulbic_work_dir) === false) {
				$this->log('公開用作業フォルダをクリーンアップに失敗しました。', LOG_ERR);
				$this->log('  対象フォルダ：[' . $blog_pulbic_work_dir . ']', LOG_ERR);
				return false;
			}

			// ステージング用フォルダ ⇒ 公開用作業用フォルダ
			if (FileUtil::dirCopy($blog_staging_dir, $blog_pulbic_work_dir . self::SUFFIX_NEW) === false) {
				$this->log('ファイルコピーに失敗（ステージング用フォルダ ⇒ 公開用作業用フォルダ）', LOG_ERR);
				$this->log('  コピー元：[' . $blog_staging_dir . ']', LOG_ERR);
				$this->log('  コピー先：[' . $blog_pulbic_work_dir . self::SUFFIX_NEW . ']', LOG_ERR);
				return false;
			}

			// ---------------------------------------------------------
			// ⑤ HTMLファイルで指定されるているURLを公開用に書き換える
			// ---------------------------------------------------------
			$this->log('⑤-2 HTMLファイルで指定されるているURLを公開用に書き換える', LOG_DEBUG);

			// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/blog)
			$tartget2 = self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
			$this->log('置換前②：[' . $tartget2 . ']', LOG_DEBUG);

			// 置換後列(http://(公開サイトのURL)/(サイトURL)/blog)
			$replace2 = self::PUBLIC_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
			$this->log('置換後②：[' . $replace2 . ']', LOG_DEBUG);

			// htmlファイルを一括置換
			if (FileUtil::replaceContentsAll($blog_pulbic_work_dir . self::SUFFIX_NEW, $tartget2, $replace2, self::EXT_HTML) === false) {
				$this->log('htmlファイルを一括置換②に失敗しました。', LOG_ERR);
				$this->log('  対象フォルダ：[' . $blog_staging_dir . ']', LOG_ERR);
				return false;
			}

			// ------------------------------------------------------
			// ⑦ 元の公開フォルダとコピーした公開フォルダをリネーム
			// ------------------------------------------------------
			$this->log('⑦ 元の公開フォルダとコピーした公開フォルダをリネーム', LOG_DEBUG);

			// 元の公開フォルダをリネーム
			if (FileUtil::rename($blog_pulbic_dir, $blog_pulbic_work_dir . self::SUFFIX_OLD) === false) {
				$this->log('ファイル移動に失敗', LOG_ERR);
				$this->log('  移動元：[' . $blog_pulbic_dir . ']', LOG_ERR);
				$this->log('  移動先：[' . $blog_pulbic_work_dir . self::SUFFIX_OLD . ']', LOG_ERR);
				return false;
			}

			// コピーした公開フォルダをリネーム
			if (FileUtil::rename( $blog_pulbic_work_dir . self::SUFFIX_NEW, $blog_pulbic_dir) === false) {
				$this->log('ファイル移動に失敗', LOG_ERR);
				$this->log('  移動元：[' . $blog_pulbic_work_dir . self::SUFFIX_NEW . ']', LOG_ERR);
				$this->log('  移動先：[' . $blog_pulbic_dir . ']', LOG_ERR);
				return false;
			}

			// --------------------------
			// ⑧ 元の公開フォルダを削除
			// --------------------------
			$this->log('⑧ 元の公開フォルダを削除', LOG_DEBUG);

			if (FileUtil::rmdirAll($blog_pulbic_work_dir . self::SUFFIX_OLD) === false) {
				$this->log('フォルダ削除に失敗', LOG_ERR);
				$this->log('  対象フォルダ：[' . $blog_pulbic_work_dir . self::SUFFIX_OLD . ']', LOG_ERR);
				return false;
			}
		}

		$this->log('公開ブログパッケージ公開 成功', LOG_DEBUG);
		return true; // 成功
	}

}

?>