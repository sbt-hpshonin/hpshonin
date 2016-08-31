<?php

App::uses('BatchPackageController', 'Controller');

/**
 * 削除ブログパッケージ作成バッチ
 *
 * @author tasano
 *
*/
class BatchCreateBlogPackageDeleteController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::CREATE;
		// バッチ名を設定
		$this->batch_name = '削除ブログパッケージ作成バッチ';
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

		$this->log('削除ブログパッケージ作成(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		// ---------
		// 事前準備
		// ---------

		// パッケージ情報を取得
		$package = $this->Package->findByPackageId($this->id);
		if(empty($package)) {
			$this->log('パッケージ情報の取得失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($package, LOG_DEBUG);

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 承認用フォルダ(再構築先)を編集
		$blog_approval_dir1 = AppConstants::DIRECTOR_APPROVAL_PATH  . DS . $site_url . DS . self::BLOG;
		if (FileUtil::mkdir($blog_approval_dir1) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_approval_dir1 . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ(再構築先)：[' . $blog_approval_dir1 . ']', LOG_DEBUG);

		// 承認用フォルダ(保存先)を編集
		$blog_approval_dir2 = AppConstants::DIRECTOR_APPROVAL_PATH  . DS . $this->id  . DS . $site_url . DS . self::BLOG;
		if (FileUtil::mkdir($blog_approval_dir2) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ(保存先)：[' . $blog_approval_dir2 . ']', LOG_DEBUG);

		// パッケージIDをキーに、MT記事リストを取得
		$mt_post_list = $this->MtPost->findListByPackageid($this->id);
		if (empty($mt_post_list)) {
			$this->log('MT記事情報の取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}

		// 事前に必要な情報を取得
		foreach ($mt_post_list as $mt_post) {
			//$this->log($mt_post, LOG_DEBUG);

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

			// 編集用MT記事IDをキーに、MTマッピングを取得
			$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
			if (!isset($mt_mapping)) {
				$this->log('MTマッピング情報の取得に失敗しました。：[' . $edit_mt_post_id . ']', LOG_ERR);
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
			$temp['approval_mt_post_id']	= $approval_mt_post_id;	// 承認用MT記事ID
			$temp['public_mt_post_id']		= $public_mt_post_id;	// 公開用MT記事ID
			$temp_list[] = $temp;
		}
		$this->log($temp_list, LOG_DEBUG);

		foreach ($temp_list as $temp) {

			// 編集用MT記事IDを取得
			$edit_mt_post_id = $temp['edit_mt_post_id'];
			$this->log('Tmp.編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_DEBUG);

			// 承認用MT記事IDを取得
			$approval_mt_post_id = $temp['approval_mt_post_id'];
			$this->log('Tmp.承認用MT記事ID：[' . $approval_mt_post_id . ']', LOG_DEBUG);

			// 公開用MT記事IDを取得
			$public_mt_post_id = $temp['public_mt_post_id'];
			$this->log('Tmp.公開用MT記事ID：[' . $public_mt_post_id . ']', LOG_DEBUG);

			// ---------------------------------------
			// ④ 削除パッケージ登録された内容を削除
			// ---------------------------------------
			$this->log('④ 削除パッケージ登録された内容を削除', LOG_DEBUG);

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

			} else {
				// 記事を削除
				$this->log('記事を削除', LOG_DEBUG);
				if ($this->mt->deletePost($approval_mt_post_id, false) === false) {
					$this->log('記事削除に失敗しました。記事ID：[' . $approval_mt_post_id . ']', LOG_ERR);
					return false;
				}
			}

			// --------------------------
			// ⑤ MTマッピング情報を更新
			// --------------------------
			$this->log('⑤ MTマッピング情報を更新', LOG_DEBUG);

			// MTマッピングを更新
			$result = $this->MtMapping->updatePostId($edit_mt_post_id, 				// 編集用MT記事ID
													 null,  						// 承認用MT記事ID
													 $public_mt_post_id, 			// 公開用MT記事ID
													 AppConstants::USER_ID_SYSTEM); // ユーザID
			if (!isset($result)) {
				$this->log('MTマッピングの更新に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
				return false;
			}

// 			※ 削除の場合、サイト再構築処理は不要
//			// --------------------
//			// ⑥ サイト再構築実施
//			// --------------------
//			$this->log('⑥ サイト再構築実施', LOG_DEBUG);
//
//			// 承認用MTを再構築
//			$result = $this->mt->publishPost($approval_mt_post_id, false);
//			if (!($result == MtXmlRpcComponent::RESULT_CODE_SUCCESS)) {
//				$this->log('サイト再構築に失敗しました。記事ID：['. $approval_mt_post_id . ']', LOG_ERR);
//				return false;
//			}
		}

		// ---------------------------------------------------------
		// ⑦-1 HTMLファイルで指定されるているURLを承認用に書き換える
		// ---------------------------------------------------------
		$this->log('⑦ HTMLファイルで指定されるているURLを承認用に書き換える', LOG_DEBUG);

		// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
		$tartget1 = self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換前①：[' . $tartget1 . ']', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/(サイトURL)/blog)
		$replace1 = self::APPROVAL_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後①：[' . $replace1 . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_approval_dir1, $tartget1, $replace1, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換①に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_approval_dir1 . ']', LOG_ERR);
			return false;
		}

		// ---------------------------------------------------
		// 承認用フォルダ(再構築先) ⇒ 承認用フォルダ(保存先)
		// ---------------------------------------------------

		// 承認用フォルダ(再構築先)をクリーンアップ
		$this->log('  対象フォルダ：[' . $blog_approval_dir1 . ']', LOG_DEBUG);
		if( FileUtil::rmdirAll($blog_approval_dir2) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}

		// ファイルコピー
		if (FileUtil::dirCopy($blog_approval_dir1, $blog_approval_dir2) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $blog_approval_dir1 . ']', LOG_ERR);
			$this->log('  コピー先：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}

		// ---------------------------------------------------------
		// ⑦-2 HTMLファイルで指定されるているURLを承認用に書き換える
		// ---------------------------------------------------------
		$this->log('⑦ HTMLファイルで指定されるているURLを承認用に書き換える', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/(サイトURL)/blog)
		$tartget2 = self::APPROVAL_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換前②：[' . $replace1 . ']', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/パッケージID/(サイトURL)/blog)
		$replace2 = self::APPROVAL_SITE_URL . self::SLASH . $this->id . self::SLASH . $site_url . self::SLASH . self::BLOG;
		$this->log('置換後②：[' . $replace2 . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($blog_approval_dir2, $replace1, $replace2, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換②に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $blog_approval_dir2 . ']', LOG_ERR);
			return false;
		}

		$this->log('削除ブログパッケージ作成(内部) 成功', LOG_DEBUG);
		return true; // 成功
	}
}

?>