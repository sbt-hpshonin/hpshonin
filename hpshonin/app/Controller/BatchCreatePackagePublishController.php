<?php

App::uses('BatchPackageController', 'Controller');
App::uses('Package', 'Model');
App::uses('Status', 'Lib');
App::uses('AppConstants', 'Lib/Constants');
App::uses('FileUtil', 'Lib/Utils');

/**
 * 公開パッケージ作成バッチ
 *
 * @author tasano
 *
 */
class BatchCreatePackagePublishController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::CREATE;
		// バッチ名を設定
		$this->batch_name = '公開パッケージ作成バッチ';
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

		$this->log('公開パッケージ作成(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		// ---------
		// 事前準備
		// ---------

		// パッケージ情報を取得
		$package = $this->Package->findByPackageId($this->id);
		if (empty($package)) {
			$this->log('パッケージ情報の取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 公開されているパッケージID
		$public_package_id = $package['Project']['public_package_id'];
		$this->log('公開パッケージID：[' . $public_package_id . ']', LOG_DEBUG);
		if (is_null($public_package_id)) {
			$public_package_id = 0;
		}
		
		// パッケージ用フォルダを生成
		$upload_path = AppConstants::DIRECTOR_UPLOAD_PATH;
		$this->log('パッケージ用フォルダ：[' . $upload_path . ']', LOG_DEBUG);

		// 作業用フォルダを生成
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $this->id . DS . $site_url;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);

		// 承認用フォルダを生成
		$approval_path = AppConstants::DIRECTOR_APPROVAL_PATH . DS . $this->id . DS . $site_url;
		if (FileUtil::mkdir($approval_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $approval_path . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ：[' . $approval_path . ']', LOG_DEBUG);

		// ステージング用フォルダを生成
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id;
		if (FileUtil::mkdir($staging_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ステージング用フォルダ：[' . $staging_path . ']', LOG_DEBUG);

		// -------------------------------------------------------------
		// ② パッケージ情報のアップロードファイル名にあるパッケージを展開
		// -------------------------------------------------------------
		$this->log('② パッケージ情報のアップロードファイル名にあるパッケージを展開', LOG_DEBUG);

		// アップロードファイル名を取得
		$upload_file_name = $package['Package']['upload_file_name'];
		if (!isset($upload_file_name)) {
			$this->log('アップロードファイル名が設定されていません。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		$this->log('アップロードファイル名：[' . $upload_file_name . ']', LOG_DEBUG);

		// 作業フォルダをクリーンアップ
		if(FileUtil::rmdirAll($work_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $work_path . ']', LOG_ERR);
			return false;
		}

		// アップロードファイルの存在確認
		if (FileUtil::exists($upload_path . DS . $upload_file_name) === false) {
			$this->log('アップロードファイルが存在しません。', LOG_ERR);
			$this->log('  ファイル名：[' . $upload_path . DS . $upload_file_name . ']', LOG_ERR);
			return false;
		}

		// アップロードファイルを解凍（展開先：作業用フォルダ）
		if (FileUtil::extract($upload_path . DS . $upload_file_name, AppConstants::DIRECTOR_WORK_PATH . DS . $this->id) === false) {
			$this->log('アップロードファイルの解凍に失敗しました。', LOG_ERR);
			$this->log('  ZIPファイル：[' . $upload_path . DS. $upload_file_name . ']', LOG_ERR);
			$this->log('  展開先     ：[' . $work_path . ']', LOG_ERR);
			// メッセージを設定
			$this->message = MsgConstants::ERROR_NOT_OPEN_PACKAGE;
			// 2013.10.28 H.Suzuki Changed
			// return false; // 業務エラー
			return true; // 業務エラー
			// 2013.10.28 H.Suzuki Changed END
		}

		// 展開したフォルダのルートがサイトURLとなっているかチェック
		if (FileUtil::exists($work_path) === false) {
			$this->log('フォルダが存在しません。フォルダ名：[' . $work_path . ']', LOG_ERR);
			// メッセージを設定
			$this->message = MsgConstants::ERROR_WRONG_SITE_URL;
			// 2013.10.28 H.Suzuki Changed
			// return false; // 業務エラー
			return true; // 業務エラー
			// 2013.10.28 H.Suzuki Changed END
		}

		// ------------------------------------------------------------------
		// ③ 同名ファイルに差分があるかファイルサイズとハッシュ値をチェック
		// ------------------------------------------------------------------
		$this->log('③ 同名ファイルに差分があるかファイルサイズとハッシュ値をチェック', LOG_DEBUG);

		// ファイルリストを取得
		$file_list = FileUtil::getFileListFromDirAndRemoveString($work_path, $work_path);
		if ($file_list === false) {
			$this->log('ファイルリストの取得に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダパス名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		if (empty($file_list)) {
			$this->log('アップロードファイルにファイルが存在しません。', LOG_ERR);
			$this->log('  対象フォルダパス名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ファイルリスト↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($file_list, LOG_DEBUG);
		$this->log('ファイルリスト↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		// ファイル名の妥当性チェック
		$extentions = explode(",", AppConstants::BAN_EXT_LIST);
		for ($i = 0; $i < count($file_list); $i++) {
			$file = $file_list[$i];
			if (StringUtil::validForSiteUrl($file) === false) {
				$this->log('URLとして使用できないファイルが存在します。', LOG_WARNING);
				$this->log('  ファイル名：[' . $file . ']', LOG_WARNING);
				// メッセージを設定
				$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_CHAR , '/' . $site_url . '/' . str_replace("\\", "/", $file));
				// 2013.10.28 H.Suzuki Changed
				// return false; // 業務エラー
				return true; // 業務エラー
				// 2013.10.28 H.Suzuki Changed END
			}

			// ファイル拡張子がNGだった場合
			if (in_array(strtolower(FileUtil::getExtention($file)), $extentions)) {
				$this->log('アップロードできない拡張子のファイルが存在します。', LOG_ERR);
				$this->log('  ファイル名：[' . $file . ']', LOG_ERR);
				$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_FILE_EXT , '/' . $site_url . '/' . str_replace("\\", "/", $file));

				return true; // 業務エラー
			}
		}

		// 作業用フォルダとステージング用フォルダを比較

		// -----------------------------------
		// 3. パス置換処理
		// -----------------------------------
		$this->log('3. パス置換処理(ホスト名)', LOG_DEBUG);

		// フォルダ配下一括コピー（ワーク用フォルダ ⇒ 承認用フォルダ）
		if (FileUtil::dirCopy($work_path, $approval_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		// 登録パッケージの変換処理
		// 置換前(http://(編集サイトのURL)/(サイトのURL)/)
		$tartget = AppConstants::URL_HOST_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_HOST_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($approval_path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		$this->log('3. パス置換処理(サイトパス)', LOG_DEBUG);

		// 登録パッケージの変換処理
		// 置換前((編集サイトのパス)/(サイトのURL)/)
		$tartget = AppConstants::URL_PATH_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列((承認サイトのパス)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_PATH_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($approval_path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $approval_path . ']', LOG_ERR);
			return false;
		}


		// 差異のないファイルを取得
		$file = FileUtil::getNoDiffContents($file_list, $approval_path, $staging_path);
		if (!empty($file)) {
			$this->log('変更のないファイルが存在します。', LOG_WARNING);
			$this->log('  ファイル名：[' . $file . ']', LOG_WARNING);
			// メッセージを設定
			$file = '/' . $site_url . '/' . str_replace(DS, '/', $file);
			$this->message = StringUtil::getMessage(MsgConstants::ERROR_NO_CHANGE_FILE, $file);
			// 2013.10.28 H.Suzuki Changed
			// return false; // 業務エラー
			// 承認用フォルダをクリーンアップ

			if (FileUtil::rmdirAll($approval_path) === false) {
				$this->log('フォルダの削除に失敗しました。', LOG_ERR);
				$this->log('  対象フォルダ名：[' . $approval_path . ']', LOG_ERR);
			}

			return true; // 業務エラー
			// 2013.10.28 H.Suzuki Changed END
		}

		// ----------------------
		// ④ 更新ファイルを登録
		// ----------------------
		$this->log('④ 更新ファイルを登録', LOG_DEBUG);

		foreach ($file_list as $file_path_name) {

			// ファイルサイズ取得
			$file_size = FileUtil::size($work_path . DS . $file_path_name);
			if ($file_size === false) {
				$this->log('ファイルサイズの取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// ファイル更新日時取得
			$file_time = FileUtil::filetime($work_path . DS . $file_path_name);
			if ($file_time === false) {
				$this->log('ファイル更新日時の取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// 更新フラグ判定（'0':追加/'1':変更）
			if (FileUtil::exists($staging_path . DS . $file_path_name)) {
				$modify_flg = AppConstants::MODIFY_FLG_MOD; // 変更
			} else {
				$modify_flg = AppConstants::MODIFY_FLG_ADD; // 追加
			}

			// パラメータを作成
			$data = array (
						'id'				=> null,									// コンテンツファイルID
						'is_del'			=> AppConstants::FLAG_OFF,					// 削除フラグ
						'created_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 作成者ID
						'modified_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 更新者ID
						'package_id'		=> $this->id,								// パッケージID
						'file_path'			=> DS . $site_url . DS . $file_path_name,	// ファイルパス名
						'file_size'			=> $file_size,								// サイズ
						'file_modified'		=> $file_time,								// ファイル更新日時
						'modify_flg'		=> $modify_flg								// 更新フラグ
					  );

			// コンテンツファイルへ登録
			$result = $this->ContentsFile->save($data);
			//$this->log($result, LOG_DEBUG);
			if ($result === false) {
				$this->log('コンテンツファイルへの登録に失敗しました。', LOG_ERR);
				$this->log('データ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
				$this->log($data, LOG_ERR);
				$this->log('データ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');
				return false;
			}
		}

		// ------------------------------------------------------------
		// ⑤ ステージング用フォルダをコピーし、作業フォルダのファイルで上書き
		// ------------------------------------------------------------
		$this->log('⑤ ステージング用フォルダをコピーし、作業フォルダのファイルで上書き', LOG_DEBUG);

		// 承認用フォルダをクリーンアップ
		if (FileUtil::rmdirAll($approval_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ名：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		// フォルダ配下一括コピー（ステージング用フォルダ ⇒ 承認用フォルダ）
		if (FileUtil::dirCopy($staging_path, $approval_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

//		// ブログパッケージを除去
//		if (FileUtil::rmdirAll($approval_path . DS . self::BLOG) === false) {
//			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
//			$this->log('  対象フォルダ名：[' . $approval_path . DS . self::BLOG . ']', LOG_ERR);
//			return false;
//		}

		// フォルダ配下一括コピー（作業フォルダ ⇒ 承認用フォルダ）
		if (FileUtil::dirCopy($work_path, $approval_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		// 承認用サイトへの置換処理
		parent::replacePathStagingToApproval($approval_path, $site_url);
		parent::replacePathOriginalToApproval($approval_path, $site_url);
		
		$this->log('公開パッケージ作成(内部) 成功!', LOG_DEBUG);
		return true; // 成功
	}

	/**
	 * 成功時後実行
	 * 
	 * @return boolean 成否
	 */
	function execute_after_success() {
		return true;
	}
	
	/**
	 * 失敗時後実行
	 * 
	 * @return boolean 成否
	 */
	function execute_after_failure() {
		return true;
	}
}

?>