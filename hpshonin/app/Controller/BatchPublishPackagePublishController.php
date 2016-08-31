<?php

App::uses('BatchPackageController', 'Controller');

/**
 * 公開パッケージ公開
 *
 * @author tasano
 *
*/
class BatchPublishPackagePublishController extends BatchPackageController {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::PUBLISH;
		// バッチ名を設定
		$this->batch_name = '公開パッケージ公開バッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_RELEASE_COMPLETE; // '06'(公開完了)
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_RELEASE_ERROR; // '96'(公開エラー)
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {

		$this->log('公開パッケージ公開(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		//----------
		// 事前準備
		//----------

		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($this->id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false; // 失敗
		}

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// パッケージフォルダを編集
		$upload_path = AppConstants::DIRECTOR_UPLOAD_PATH;
		$this->log('パッケージ用フォルダ： [' . $upload_path . ']', LOG_DEBUG);

		// 作業用フォルダを編集
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $this->id . DS . $site_url;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);

		// ステージング用フォルダを編集
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url;
		if (FileUtil::mkdir($staging_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ステージング用フォルダ：[' . $staging_path . ']', LOG_DEBUG);

		// 公開用フォルダ接続確認
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			if (FileUtil::exists($director_publish_path) === false) {
				$this->log('フォルダを認識できません。', LOG_ERR);
				$this->log('  フォルダ名：[' . $director_publish_path . ']', LOG_ERR);
				return false;
			}
		}

		// 公開用フォルダ
		$publish_path_list = array();
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			$publish_path = $director_publish_path . DS . $site_url. DS . $this->id;
			if (FileUtil::mkdir($publish_path) === false) {
				$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用フォルダ：[' . $publish_path . ']', LOG_DEBUG);
			$publish_path_list[] = $publish_path;
		}

		// 公開用サーバー
		$publish_server_list = array();
		foreach ($this->getDirectorPublishServerList() as $director_publish_server) {
			$publish_server = $director_publish_server;
			$this->log('公開用サーバー：[' . $publish_server . ']', LOG_DEBUG);
			$publish_server_list[] = $publish_server;
		}

		// IISの物理フォルダ、IISではスラッシュを受け付けないので、パースする
		$publish_path_iis = str_replace("/", "\\", AppConstants::DIRECTOR_PUBLISH_IIS_PATH . DS . $site_url. DS . $this->id);

		// ----------------------------------------------------------------
		// ② パッケージ情報のアップロードファイル名にあるパッケージを展開
		// ----------------------------------------------------------------
		$this->log('② パッケージ情報のアップロードファイル名にあるパッケージを展開', LOG_DEBUG);

		// アップロードファイル名を取得
		$upload_file_name = $package['Package']['upload_file_name'];
		if (empty($upload_file_name)) {
			$this->log('アップロードファイル名が設定されていません。パッケージID：[' . $this->id . ']', LOG_ERR);
		}
		$this->log('アップロードファイル名：[' . $upload_file_name . ']', LOG_DEBUG);

		// 作業用フォルダをクリーンアップ
		if (FileUtil::rmdirAll($work_path) === false) {
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

		// ファイルを解凍（展開先：作業用フォルダ/パッケージID）
		if (FileUtil::extract($upload_path . DS. $upload_file_name, AppConstants::DIRECTOR_WORK_PATH . DS . $this->id) === false) {
			$this->log('ファイルの解凍に失敗しました。', LOG_ERR);
			$this->log('  ZIPファイル：[' . $upload_path . DS. $upload_file_name . ']', LOG_ERR);
			$this->log('  展開先     ：[' . $work_path . ']', LOG_ERR);
			return false;
		}

		// ------------------------------------------------------------
		// ③ ステージング用フォルダに、作業フォルダのファイルを上書き
		// ------------------------------------------------------------
		$this->log('③ ステージング用フォルダに、作業フォルダのファイルを上書き', LOG_DEBUG);

		// 作業フォルダ ⇒ ステージング用フォルダ
		if (FileUtil::dirCopy($work_path, $staging_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $staging_path . ']', LOG_ERR);
			return false;
		}

		// コピー処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_path_list); $i++) {

			// 公開用フォルダ
			$publish_path = $publish_path_list[$i];

			// ------------------------------------
			// ④ 公開用フォルダにコピー
			// ------------------------------------
			$this->log('④ 公開用フォルダにコピー', LOG_DEBUG);

			// ステージング用フォルダ ⇒ 公開用フォルダ
			if (FileUtil::dirCopy($staging_path, $publish_path) === false) {
				$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
				$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
				$this->log('  コピー先：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
		}

		// 公開処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_path_list); $i++) {

			// 公開用サーバー
			$publish_server = $publish_server_list[$i];
			// 公開用フォルダ
			$publish_path = $publish_path_list[$i];

			// ------------------------------------
			// ⑤ 仮想ディレクトリの存在チェック
			// ------------------------------------
			$this->log('⑤ 仮想ディレクトリの存在チェック', LOG_DEBUG);

			// AppcmdでIISの階層ディレクトリ状況を取得
			$result = shell_exec('WinRS -r:' . $publish_server
					. ' -u:' . AppConstants::APPCMD_SERVER_USER
					. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
					. ' %windir%\system32\inetsrv\appcmd list vdir "'
					. AppConstants::APPCMD_SITE_NAME . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url . '" ' . self::SLASH . 'xml');

			// 取得したXMLをパース
			$vdir = simplexml_load_string($result);
			// 仮想ディレクトリ名を取得
			$vdirName = $vdir->VDIR[0]['path'];

			// AppConstants::APPCMD_SITE_NAMEが公開サーバーに存在しない
			if(empty($vdirName)) {
				$this->log('AppConstants::APPCMD_SITE_NAMEが公開サーバーに存在しません。', LOG_ERR);
				return false;
			}

			// ------------------------------------
			// ⑥ 仮想ディレクトリの設定・切り替え
			// ------------------------------------
			$this->log('⑥ 仮想ディレクトリの設定・切り替え', LOG_DEBUG);

			// 仮想ディレクトリが未定義の場合、スラッシュが返る
			if($vdirName == self::SLASH) {
				// AppcmdでIISの仮想ディレクトリの設定
				$result = shell_exec('WinRS -r:' . $publish_server
						. ' -u:' . AppConstants::APPCMD_SERVER_USER
						. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
						. ' %windir%\system32\inetsrv\appcmd add vdir '
						. self::SLASH . 'app.name:"' . AppConstants::APPCMD_SITE_NAME . self::SLASH . '" '
						. self::SLASH . 'path:' . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url
						. ' ' . self::SLASH . 'physicalPath:' . $publish_path_iis);
			} else {
				// AppcmdでIISの仮想ディレクトリの切り替え
				$result = shell_exec('WinRS -r:' . $publish_server
						. ' -u:' . AppConstants::APPCMD_SERVER_USER
						. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
						. ' %windir%\system32\inetsrv\appcmd set vdir '
						. self::SLASH . 'vdir.name:"' . AppConstants::APPCMD_SITE_NAME . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url . '" '
						. self::SLASH . 'physicalPath:' . $publish_path_iis);
			}

			// ------------------------------------
			// ⑦ 仮想ディレクトリの存在チェック
			// ------------------------------------
			$this->log('⑦ 仮想ディレクトリの存在チェック', LOG_DEBUG);
			// AppcmdでIISの階層ディレクトリ状況を取得
			$result = shell_exec('WinRS -r:' . $publish_server
					. ' -u:' . AppConstants::APPCMD_SERVER_USER
					. ' -p:' . AppConstants::APPCMD_SERVER_USER_PASSWORD
					. ' %windir%\system32\inetsrv\appcmd list vdir "'
					. AppConstants::APPCMD_SITE_NAME . AppConstants::APPCMD_PATH_NAME . self::SLASH . $site_url . '" ' . self::SLASH . 'xml');

			// 取得したXMLをパース
			$vdir = simplexml_load_string($result);
			// 仮想ディレクトリ名を取得
			$vdirName = $vdir->VDIR[0]['path'];

			// 作成・更新した仮想ディレクトリと、取得した仮想ディレクトリが一致しなければ失敗と判断する
			if($vdirName != AppConstants::APPCMD_PATH_NAME . self::SLASH .$site_url) {
				$this->log('仮想ディレクトリの作成に失敗しました。', LOG_ERR);
				return false;
			}

			// 仮想ディレクトリの物理フォルダ名を取得
			$physicalPathName = $vdir->VDIR[0]['physicalPath'];

			// 作成・更新した物理フォルダと、取得した物理フォルダが一致しなければ失敗と判断する
			if($physicalPathName != $publish_path_iis) {
				$this->log('仮想ディレクトリの物理フォルダの作成に失敗しました。', LOG_ERR);
				return false;
			}
		}

		// 公開物理フォルダに使用しているパッケージIDをプロジェクトに保存
		$package['Project']['public_package_id'] = $this->id;
		$package['Project']["modified"] = null;
		if (!$this->Project->save($package['Project'])) {
			$this->log("パッケージIDの保存に失敗しました。", LOG_ERR);
			$result = AppConstants::RESULT_CD_FAILURE;
			return false;
		}

		$this->log('公開パッケージ公開(内部) 成功', LOG_DEBUG);
		return true; // 成功
	}

}

?>