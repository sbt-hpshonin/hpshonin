<?php

App::uses('BatchPackageController', 'Controller');
App::uses('Package', 'Model');
App::uses('Status', 'Lib');
App::uses('FileUtil', 'Lib/Utils');

/**
 * 削除パッケージ作成バッチ
 *
 * @author tasano
 *
 */
class BatchCreatePackageDeleteController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::CREATE;
		// バッチ名を設定
		$this->batch_name = '削除パッケージ作成バッチ';
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

		$this->log('削除パッケージ作成(内部) 開始', LOG_INFO);
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

		// 作業用フォルダを編集
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $this->id . DS . $site_url;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);

        // 承認用フォルダを編集
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

		// ------------------------------------------------------------------------------------------------------------
		// ② パッケージ情報のアップロードファイル名にある指示ファイルに指定されたファイルがステージング用に存在するかチェック
		// ------------------------------------------------------------------------------------------------------------
        $this->log('② パッケージ情報のアップロードファイル名にある指示ファイルに指定されたファイルがステージング用に存在するかチェック', LOG_DEBUG);

		// 削除指示ファイル名を取得
		$direction_file_name = $package['Package']['upload_file_name'];
		if (empty($direction_file_name)) {
			$this->log('アップロードファイル名が設定されていません。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		$this->log('アップロードファイル名：[' . $direction_file_name .']', LOG_DEBUG);

		// アップロードファイルの存在確認
		if (FileUtil::exists($upload_path . DS . $direction_file_name) === false) {
			$this->log('アップロードファイルが存在しません。', LOG_ERR);
			$this->log('  ファイル名：[' . $upload_path . DS . $direction_file_name . ']', LOG_ERR);
			return false;
		}

		// アップロードファイルを解凍
		if (FileUtil::extract($upload_path . DS . $direction_file_name, $work_path) === false) {
			$this->log('ファイルの解凍に失敗しました。', LOG_ERR);
			$this->log('  ZIPファイル名：[' . $upload_path . DS . $direction_file_name . ']', LOG_ERR);
			$this->log('  展開先       ：[' . $work_path . ']', LOG_ERR);
			// メッセージを設定
			$this->message = MsgConstants::ERROR_NOT_OPEN_PACKAGE;
			// 2013.10.28 H.Suzuki Changed
			// return false; // 業務エラー
			return true; // 業務エラー
			// 2013.10.28 H.Suzuki Changed END
		}

		// 削除指示ファイルの存在確認
		if (FileUtil::exists($work_path . DS . self::DELETE_FILE_NAME) === false) {
			$this->log('削除指示ファイルが存在しません。', LOG_ERR);
			$this->log('  ファイル名：[' . $work_path . DS . self::DELETE_FILE_NAME . ']', LOG_ERR);
			// メッセージを設定
			$this->message = MsgConstants::ERROR_WRONG_FILENAME;
			// 2013.10.28 H.Suzuki Changed
			// return false; // 業務エラー
			return true; // 業務エラー
			// 2013.10.28 H.Suzuki Changed END
		}

		// 削除対象ファイルリストを取得
		$file_list1 = FileUtil::getFileListFromFileContents($work_path . DS . self::DELETE_FILE_NAME);
		$this->log('ファイルリスト①↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($file_list1, LOG_DEBUG);
		$this->log('ファイルリスト①↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		// セパレータに「\」が使われていないかチェック
		foreach ($file_list1 as $file) {
			if (StringUtil::match($file, '\\') === true) {
				$this->log('セパレータに「\」が使用されています。ファイル名：[' . $file . ']', LOG_ERR);
				// メッセージを設定
				$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_CHAR_YEN, $file);
				// 2013.10.28 H.Suzuki Changed
				// return false; // 業務エラー
				return true; // 業務エラー
				// 2013.10.28 H.Suzuki Changed END
			}
		}

		// ファイル名に「..」が使われていないかチェック
		foreach ($file_list1 as $file) {
			foreach(explode("/",$file) as $baff){
				if($baff === ".."){
					$this->log('ファイル名に「..」が使用されています。ファイル名：[' . $file . ']', LOG_ERR);
					// メッセージを設定
					$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_CHAR_POINT, $file);
					// 2013.10.28 H.Suzuki Changed
					// return false; // 業務エラー
					return true; // 業務エラー
					// 2013.10.28 H.Suzuki Changed END
				}
			}
		}


		// 削除対象ファイルリストを取得
		$remove_file_list = FileUtil::getFileListFromFileContentsAndRemoveDir($work_path . DS . self::DELETE_FILE_NAME, $site_url);
		if (empty($remove_file_list)) {
			$this->log('削除対象ファイルリストの取得に失敗しました。削除指示：[' . $work_path . DS . self::DELETE_FILE_NAME . ']', LOG_ERR);
			return false;
		}
		$this->log('ファイルリスト②↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($remove_file_list, LOG_DEBUG);
		$this->log('ファイルリスト②↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		// 削除対象ファイルの存在確認
		$cnt = 0;
		foreach ($remove_file_list as $remove_file) {
			if (FileUtil::exists($staging_path . DS . $remove_file) === false) {
				$this->log('削除対象ファイルが存在しません。ファイル名：[' . $staging_path . DS . $remove_file . ']', LOG_ERR);
				// メッセージを設定
				$this->message = StringUtil::getMessage(MsgConstants::ERROR_NOT_EXISTS, '/' . $site_url . '/' . str_replace('\\', '/', $remove_file));
				// 2013.10.28 H.Suzuki Changed
				// return false; // 業務エラー
				return true; // 業務エラー
				// 2013.10.28 H.Suzuki Changed END
			}
			if (is_dir($staging_path . DS . $remove_file)) {
				$remove_file_list[$cnt] = $remove_file . DS;
			}
			$cnt++;
		}

// 2013.10.17 H.Suzuki Deleted
//		// 削除対象としてフォルダが指定された場合、配下のファイルも削除対象として指定されているかチェック
//		foreach ($remove_file_list as $remove_file) {
//
//			// フォルダの場合のみ
//			if (is_dir($publish_path . DS . $remove_file)) {
//
//				// 配下のファイルを取得
//				$file_list = FileUtil::getFileListFromDirAndRemoveString($publish_path . DS . $remove_file, $publish_path);
//				//$this->log($file_list, LOG_DEBUG);
//
//				// 包有するか
//				if ($this->includeAll($file_list, $remove_file_list) === false) {
//					$this->log('削除指定されたフォルダに、削除指定されていないファイルが存在します。', LOG_ERR);
//					$this->log('  フォルダ名：[' . $remove_file . ']', LOG_ERR);
//					return false;
//				}
//			}
//		}
// 2013.10.17 H.Suzuki Deleted END


// 2013.10.17 H.Suzuki Added
		// 削除対象としてフォルダが指定された場合、配下のファイルも削除対象として指定する
		$file_list_baff = array();
		foreach ($remove_file_list as $key => $remove_file) {

			// フォルダの場合のみ
			if (is_dir($staging_path . DS . $remove_file)) {
				$file_list_baff2 = FileUtil::getFileListFromDirAndRemoveString($staging_path . DS . $remove_file, $staging_path);
				if(count($file_list_baff2)){
					$file_list_baff[] = $remove_file ;
					$file_list_baff = array_merge($file_list_baff,$file_list_baff2);
				}
			}
		}
		foreach($file_list_baff as $key => $remove_file){
			$file_list_baff[$key] = DS .$site_url . DS . $remove_file;
		}
		// 2013.10.17 H.Suzuki Added END

		// ----------------------
		// ③ 削除ファイルを登録
		// ----------------------
		$this->log('③ 削除ファイルを登録', LOG_DEBUG);
		$remove_file_list2 = FileUtil::getFileListFromFileContents2($work_path . DS . self::DELETE_FILE_NAME);

// 2013.10.17 H.Suzuki Added
		$remove_file_list2 = array_unique(array_merge($remove_file_list2,$file_list_baff));
// 2013.10.17 H.Suzuki Added END
		foreach ($remove_file_list2 as $file_path_name) {

			// パラメータを作成
			$data = array (
					'id'				=> null,						   // コンテンツファイルID
					'is_del'            => AppConstants::FLAG_OFF,         // 削除フラグ
					'created_user_id'   => AppConstants::USER_ID_SYSTEM,   // 作成者ID
					'modified_user_id'  => AppConstants::USER_ID_SYSTEM,   // 更新者ID
					'package_id'        => $this->id,                      // パッケージID
					'file_path'         => $file_path_name,                // ファイルパス名
					'file_size'         => null,                           // サイズ
					'file_modified'     => null,                           // ファイル更新日時
					'modify_flg'        => AppConstants::MODIFY_FLG_DEL    // 更新フラグ：削除
			);

			// コンテンツファイルに登録
			if ($this->ContentsFile->save($data) === false) {
				$this->log('コンテンツファイルへの登録に失敗しました。', LOG_ERR);
				$this->log('  データ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_ERR);
				$this->log($data, LOG_ERR);
				$this->log('  データ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_ERR);
				return false;
			}
		}

		// ---------------------------------------------------------------
		// ④ 削除指示に指定されたファイル以外をステージング用フォルダからコピー
		// ---------------------------------------------------------------
		$this->log('削除指示に指定されたファイル以外をステージング用フォルダからコピー', LOG_DEBUG);

		// 承認用フォルダをクリーンアップ
		if (FileUtil::rmdirAll($approval_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ名：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		// ステージング用フォルダ ⇒ 承認用フォルダ
		if (FileUtil::dirCopy($staging_path, $approval_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

//		// ブログパッケージを除去
//		if (FileUtil::rmdirAll($approval_path . DS . 'blog') === false) {
//			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
//			$this->log('  対象フォルダ名：[' . $approval_path . DS . self::BLOG . ']', LOG_ERR);
//			return false;
//		}

		// 降順にソート
		if (rsort($remove_file_list) === false) {
			$this->log('ソートに失敗しました。', LOG_ERR);
			return false;
		}
		//$this->log('ソートリスト↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		//$this->log($remove_file_list, LOG_DEBUG);
		//$this->log('ソートリスト↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		// 指定されたファイルを承認用フォルダより除去
		foreach ($remove_file_list as $removeFileName) {
			// 2013.10.17 H.Suzuki Changed
			// if (FileUtil::remove($approval_path . DS . $removeFileName) === false) {
			//	$this->log('ファイルの削除に失敗しました。', LOG_ERR);
			//	$this->log('  対象ファイル名：[' . $approval_path . DS . $removeFileName . ']', LOG_ERR);
			//	return false;
			//}
			$file = $approval_path . DS . $removeFileName;
			if (file_exists($file)) {
				if (is_dir($file)) {
					$result = FileUtil::rmdirAll($file);
				} else {
					$result = FileUtil::remove($file);
				}
			} else {
				$result =  true;
			}
			if($result === false ){
				$this->log(__FILE__ . ' ファイルの削除に失敗しました。', LOG_ERR);
				$this->log(__FILE__ . '   対象ファイル名：[' . $file . ']', LOG_ERR);
				return false;
			}
			// 2013.10.17 H.Suzuki Changed END
		}

		// 承認用サイトへの置換処理
		parent::replacePathStagingToApproval($approval_path, $site_url);
				
		$this->log('削除パッケージ作成(内部) 成功', LOG_DEBUG);
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
	
	/**
	 * 包有するか判定します。
	 *
	 * @param  unknown $list1 包有されるリスト
	 * @param  unknown $list2 包有するリスト
	 * @return boolean        true：全て包有する、false：包有しないものもある
	 */
	private function includeAll ($list1, $list2) {

		$this->log('includeAll start', LOG_DEBUG);
		$this->log($list1, LOG_DEBUG);
		$this->log($list2, LOG_DEBUG);

		foreach ($list1 as $str1) {
			if ($this->includeOne($str1, $list2) === false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 包有するか判定します。
	 *
	 * @param  unknown $str  包有される文字列
	 * @param  unknown $list 包有するリスト
	 * @return boolean       true：包有する、false：包有しない
	 */
	private function includeOne ($str , $list) {

		$this->log('includeOne start', LOG_DEBUG);
		$this->log($str, LOG_DEBUG);
		$this->log($list, LOG_DEBUG);

		foreach ($list as $str2) {
			if ($str === $str2) {
				return true;
			}
		}
		return false;
	}

}
?>