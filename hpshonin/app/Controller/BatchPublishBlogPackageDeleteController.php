<?php

App::uses('BatchPackageController', 'Controller');

/**
 * 削除ブログパッケージ公開バッチ
 *
 * @author tasano
 *
*/
class BatchPublishBlogPackageDeleteController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::PUBLISH;
		// バッチ名を設定
		$this->batch_name = '削除ブログパッケージ公開バッチ';
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
	 * @return 成否
	 */
	function execute_inner() {

		$this->log('削除ブログパッケージ公開(内部) 開始', LOG_DEBUG);
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

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// ステージング用フォルダ
		$blog_staging_dir = AppConstants::DIRECTOR_STAGING_PATH	 . DS . $site_url . DS . self::BLOG;
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

		// MT記事リストを取得
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
			$this->log('編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// MTマッピングを取得
			$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
			if (empty($mt_mapping)) {
				$this->log('MTマッピングの取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
				return false;
			}

			// 承認用MT記事IDを取得
			$approval_mt_post_id = $mt_mapping['MtMapping']['approval_mt_post_id'];
			$this->log('公開用MT記事ID：[' . $approval_mt_post_id . ']', LOG_DEBUG);

			// 公開用MT記事IDを取得
			$public_mt_post_id = $mt_mapping['MtMapping']['public_mt_post_id'];
			if (empty($public_mt_post_id)) {
				$this->log('公開用MT記事IDが設定されていません。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// --------------------------------------
			// ② 削除パッケージ登録された内容を削除
			// --------------------------------------
			$this->log('② 削除パッケージ登録された内容を削除', LOG_DEBUG);


			// 記事の存在確認
			$this->log('記事の存在確認', LOG_DEBUG);

			if ($this->mt->getPost($public_mt_post_id, false) === false) {

				// 結果詳細を取得
				$result_code = $this->mt->resultCode;
				$this->log('$result_code：[' . $result_code . ']', LOG_DEBUG);

				// 接続失敗
				if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_CONNECT) {
					$this->log('MTサーバとの接続に失敗しました。記事ID：[' . $public_mt_post_id . ']', LOG_ERR);
					return false;

					// デコード失敗
				} else if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_DECODE) {
					$this->log('デコードに失敗しました。記事ID：[' . $public_mt_post_id . ']', LOG_ERR);
					return false;

					// 記事が存在しない ※処理継続
				} else if ($result_code == MtXmlRpcComponent::RESULT_CODE_NOT_FOUND) {
					$this->log('記事が存在しませんでした。記事ID：[' . $public_mt_post_id . ']', LOG_WARNING);
				}

			} else {
				// 公開用MTより記事を削除
				$this->log('記事を削除', LOG_DEBUG);
				if ($this->mt->deletePost($public_mt_post_id, false) === false) {
					$this->log('記事削除に失敗しました。記事ID：[' . $public_mt_post_id . ']', LOG_WARNING);
				}
			}

			// MTマッピング同期
			if($this->MtMapping->updatePostId($edit_mt_post_id, 			// 編集用MT記事ID
											  $approval_mt_post_id, 		// 承認用MT記事ID
											  null, 						// 公開用MT記事ID
											  AppConstants::USER_ID_SYSTEM	// ユーザID
											 ) === false) {
				$this->log('MTマッピングの更新に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
				return false;
			}

//			※ 削除の場合、再構築不要
//			// --------------------
//			// ③ サイト再構築実施
//			// --------------------
//			$this->log('③ サイト再構築実施', LOG_DEBUG);
//
//			// 公開用MTを再構築
//			if ($this->mt->publishPost($public_mt_post_id, false) === false) {
//				$this->log('サイト再構築に失敗しました。公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_ERR);
//				return false;
//			}
		}

		// ---------------------------------------------------------
		// ④-1 HTMLファイルで指定されるているURLを公開用に書き換える
		// ---------------------------------------------------------
		$this->log('④-1 HTMLファイルで指定されるているURLを公開用に書き換える', LOG_DEBUG);

		// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
		$tartget = self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換前①：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列(http://(ステージングサイトのURL)/(サイトURL)/blog)
		$replace = self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後①：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_staging_dir, $tartget, $replace, self::EXT_HTML) === false) {
			$this->log('htmlファイルを一括置換①に失敗しました。', LOG_ERR);
			$this->log('  置換前①：[' . $tartget . ']', LOG_ERR);
			$this->log('  置換後①：[' . $replace . ']', LOG_ERR);
			return false;
		}

		// 公開フォルダ分、繰り返す
		for ($i = 0; $i < count($blog_pulbic_dir_list); $i++) {

			// 公開用フォルダ
			$blog_pulbic_dir = $blog_pulbic_dir_list[$i];

			// 公開用作業フォルダ
			$blog_pulbic_work_dir = $blog_pulbic_work_dir_list[$i];

			// --------------------------------
			// ⑤ 公開用フォルダに別名でコピー
			// --------------------------------
			$this->log('⑤ 公開用フォルダに別名でコピー', LOG_DEBUG);

			// 公開用作業フォルダをクリーンアップ
			if (FileUtil::rmdirAll($blog_pulbic_work_dir) === false) {
				$this->log('公開用作業フォルダのクリーンアップに失敗しました。対象フォルダ：[' . $blog_pulbic_work_dir . ']', LOG_ERR);
				return false;
			}

			// ステージング用フォルダ ⇒ 公開用作業フォルダ
			if (FileUtil::dirCopy($blog_staging_dir, $blog_pulbic_work_dir . self::SUFFIX_NEW) === false) {
				$this->log('ファイルコピーに失敗しました。(ステージング用フォルダ ⇒ 公開用フォルダ) 。', LOG_ERR);
				$this->log('  コピー元フォルダ：[' . $blog_staging_dir . ']', LOG_ERR);
				$this->log('  コピー先フォルダ：[' . $blog_pulbic_work_dir .  self::SUFFIX_NEW . ']', LOG_ERR);
				return false;
			}

			// ---------------------------------------------------------
			// ④-2 HTMLファイルで指定されるているURLを公開用に書き換える
			// ---------------------------------------------------------
			$this->log('④-2 HTMLファイルで指定されるているURLを公開用に書き換える', LOG_DEBUG);

			// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/blog)
			$tartget2 = self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
			$this->log('置換前②：[' . $tartget2 . ']', LOG_DEBUG);

			// 置換後列(http://(公開サイトのURL)/(サイトURL)/blog)
			$replace2 = self::PUBLIC_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
			$this->log('置換後②：[' . $replace2 . ']', LOG_DEBUG);

			// htmlファイルを一括置換
			if (FileUtil::replaceContentsAll($blog_pulbic_work_dir . self::SUFFIX_NEW, $tartget2, $replace2, self::EXT_HTML) === false) {
				$this->log('htmlファイルを一括置換②に失敗しました。', LOG_ERR);
				$this->log('  置換前②：[' . $tartget2 . ']', LOG_ERR);
				$this->log('  置換後②：[' . $replace2 . ']', LOG_ERR);
				return false;
			}

			// ------------------------------------------------------
			// ⑥ 元の公開フォルダとコピーした公開フォルダをリネーム
			// ------------------------------------------------------
			$this->log('⑥ 元の公開フォルダとコピーした公開フォルダをリネーム', LOG_DEBUG);

			// 元の公開フォルダをリネーム
			if (FileUtil::rename($blog_pulbic_dir, $blog_pulbic_work_dir . self::SUFFIX_OLD) === false) {
				$this->log('ファイル移動に失敗しました。', LOG_ERR);
				$this->log('  移動元フォルダ：[' . $blog_pulbic_dir . ']', LOG_ERR);
				$this->log('  移動先フォルダ：[' . $blog_pulbic_work_dir . self::SUFFIX_OLD . ']', LOG_ERR);
				return false;
			}

			// コピーした公開フォルダをリネーム
			if (FileUtil::rename($blog_pulbic_work_dir . self::SUFFIX_NEW, $blog_pulbic_dir) === false) {
				$this->log('ファイル移動に失敗しました。', LOG_ERR);
				$this->log('  移動元フォルダ：[' . $blog_pulbic_work_dir .  self::SUFFIX_NEW . ']', LOG_ERR);
				$this->log('  移動先フォルダ：[' . $blog_pulbic_dir . ']', LOG_ERR);
				return false;
			}

			// --------------------------
			// ⑦ 元の公開フォルダを削除
			// --------------------------
			$this->log('⑦ 元の公開フォルダを削除', LOG_DEBUG);

			if (FileUtil::rmdirAll($blog_pulbic_work_dir . self::SUFFIX_OLD) === false) {
				$this->log('元の公開フォルダを削除を失敗しました。', LOG_ERR);
				return false;
			}
		}

		$this->log('削除ブログパッケージ公開(内部) 成功', LOG_DEBUG);
		return true; // 成功
	}

}

?>