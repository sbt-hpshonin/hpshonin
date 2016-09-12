<?php

App::uses('BatchAppController', 'Controller');
App::uses('MsgConstants', 'Lib/Constants');
App::uses('TransactionComponent', 'Component');
App::uses('Status', 'Lib');
App::uses('BatchEMailOpenFinishController', 'Controller');
App::uses('BatchEMailOpenNgController', 'Controller');
App::uses('BatchEMailApprovalRequestOkController', 'Controller');
App::uses('BatchEMailPreOpenNgController', 'Controller');

/**
 * パッケージ管理バッチの抽象クラス
 *
 * @author tasano
 *
 */
abstract class BatchPackageController extends BatchAppController {

	/** 作成 */
	const CREATE = "1";
	/** 公開 */
	const PUBLISH = "2";
	
	const APPROVAL = "3";

	/** 編集サイトURL */
	const EDIT_SITE_URL     = 'http://hp-shonin.shutoko.jp/pe/static/edt/blogroot';
	/** 承認サイトURL */
	const APPROVAL_SITE_URL = 'http://hp-shonin.shutoko.jp/pe/static/prv';
	/** ステージングサイトURL */
	const STAGING_SITE_URL  = 'http://hp-shonin.shutoko.jp/pe/static/stg';
	/** 公開サイトURL */
	const PUBLIC_SITE_URL   = 'http://www.shutoko.jp/ss';

	/** 削除指示ファイル名 */
	const DELETE_FILE_NAME  = 'delete.txt';

	/** ブログフォルダ名 */
	const BLOG = 'blog';

	/** スラッシュ */
	const SLASH = '/';

	/** 拡張子 HTML */
	const EXT_HTML = 'html';

	/** 接尾文字列 新フォルダ */
	const SUFFIX_NEW = '_new';

	/** 接尾文字列 旧フォルダ */
	const SUFFIX_OLD = '_old';

	/** モデルを登録 */
	public $uses = array('Package',				// パッケージ
						 'Project',				// プロジェクト
						 'ContentsFile',		// コンテンツファイル
						 'MtPost',				// MT記事
						 'MtMapping',			// MTマッピング
						 'MtProject',			// MTプロジェクト
						 'MtImageFile',			// MT画像ファイル
						 'MtStagingImgFile',	// MTステージング画像ファイル
						 'MtEntryAppBak',
						 'MtEntryPubBak'
						);


	/** バッチ名 */
	protected $batch_name;

	/** 作成・公開区分 */
	protected $create_publish_div;

	/** 処理成功時のステータスコード */
	protected $status_cd_on_normal;

	/** 処理失敗時のステータスコード */
	protected $status_cd_on_abend;

	/** パッケージID */
	protected $id;

	/** パッケージ */
	protected $package;

	/** XML-RPCコンポーネント */
	protected $mt;

	/** メッセージ */
	protected $message;

	/** ブログ画像削除フラグ */
	protected $blog_images_delete_flg;

	/** 再実行の規定数 */
	const COUNT_RETRY = 3;

	/** 再実行までの待ち秒数 */
	const WAIT_SECOND = 180;

	/** 処理開始時刻 */
	protected $start_at;
	
	/** 処理終了時刻 */
	protected $end_at;
	
	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();
	}

	/**
	 * パッケージIDを設定
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * 実行
	 *
	 * @return string 結果コード
	 */
	public function execute() {

		$this->log($this->batch_name . 'を開始しました。', LOG_INFO);

		// 本来はBatchCDがパッケージ登録か公開かで比較すべきだが、
		// 修正が多岐にわたるため、完了時のステータスで比較
		if ($this->status_cd_on_normal == Status::STATUS_CD_RELEASE_COMPLETE) {
			$retry = self::COUNT_RETRY;
		} else {
			$retry = 1;
		}

		for($i = 1 ; $i <= $retry ; $i++){

			// トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

			$result = $this->execute_inner($this->id);
			if ($result != false){
				$this->log('内部処理成功', LOG_DEBUG);
				break;
			}

			$this->log('内部処理失敗', LOG_DEBUG);

			// ロールバック
			if ($this->Components->Transaction->rollback(null) === false) {
				$this->log('ロールバックに失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

			if ($i < $retry) {
				sleep(self::WAIT_SECOND);
			}
		}

		if ($result != false)
		{
			// +++++
			// 成功
			// +++++

			// ---------------------
			// ステータスCDをに更新
			// ---------------------

			// パッケージを取得
			$this->package = $this->Package->findByPackageId($this->id);
			if(empty($this->package)) {
				$this->log('パッケージの取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
				$this->log($this->batch_name . ' 異常終了', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// 項目を設定
			if( $this->message == "" ){
				$this->package['Package']['status_cd']		  = $this->status_cd_on_normal; // ステータスCD
				$this->package['Package']['message']		  = null; 						// メッセージ
				$this->package['Package']['modified_user_id'] = $this->getModifiedUserId();	// 更新者
				$this->package['Package']['modified']		  = null;						// 更新日時
			}
			else{
				$this->package['Package']['status_cd']        = $this->status_cd_on_abend;	// ステータスCD
				$this->package['Package']['message']          = $this->message;				// メッセージ
				$this->package['Package']['modified_user_id'] = $this->getModifiedUserId();	// 更新者ID
				$this->package['Package']['modified']         = null;						// 更新日時
			}

			// バリデートは使用しない
			unset($this->Package->validate);

			// パッケージを更新
			if ($this->Package->save($this->package) === false) {
				$this->log('パッケージの更新に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// コミット
			if($this->Transaction->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

			// ステージングフォルダの不要画像削除
			if ($this->blog_images_delete_flg) {
				$this->deleteUnnecessaryImagesFromStaging();
			}

			// 成功時の処理を実行
			if (!$this->execute_after_success()) {
				$package = $this->Package->findById($this->id);
				$package['Package']['message'] = '予期せぬエラーが発生しました。';
				$package['Package']['status_cd'] = $this->status_cd_on_abend;
				$this->Package->save($package);
				$this->log('成功時の後処理に失敗しました。', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

			// 結果コード(:成功)を返却
			$this->log($this->batch_name . 'を正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS;

		} else {

			// +++++
			// 失敗
			// +++++

			// ---------------------
			// ステータスCDをに更新
			// ---------------------

			// 再トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

			// パッケージを取得
			$this->package = $this->Package->findByPackageId($this->id);
			if(empty($this->package)) {
				$this->log('パッケージの取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// 項目を設定
			$this->package['Package']['status_cd']        = $this->status_cd_on_abend;	// ステータスCD
			$this->package['Package']['message']          = $this->getMessage();		// メッセージ
			$this->package['Package']['modified_user_id'] = $this->getModifiedUserId();	// 更新者ID
			$this->package['Package']['modified']         = null;						// 更新日時

			// バリデートは使用しない
			unset($this->Package->validate);

			// パッケージを更新
			if ($this->Package->save($this->package) === false) {
				$this->log('パッケージの更新に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// コミット
			if($this->Transaction->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// 失敗時の処理を実行
			if(!$this->execute_after_failure()) {
				$this->log('失敗時の後処理に失敗しました。', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// 結果コード(:失敗)を返却
			$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}
	}

	/**
	 * 内部実行
	 *
	 * @return boolean 成否
	 */
	abstract public function execute_inner();

	/**
	 * 成功時後実行
	 *
	 * @return boolean 成否
	 */
	abstract protected function execute_after_success();

	/**
	 * 失敗時後実行
	 *
	 * @return boolean 成否
	 */
	abstract protected function execute_after_failure();


	/**
	 * 公開用フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用フォルダリスト
	 */
	protected function getDirectorPublishPathList() {
		$ret = array();
		for($i = 1; ; $i++) {
			@ $path = constant('AppConstants::DIRECTOR_PUBLISH_PATH_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}

	/**
	 * 公開用フォルダを取得(使用しない)
	 *
	 * @return string 公開用フォルダ
	 */
	protected function getDirectorPublishPath() {
		return AppConstants::DIRECTOR_PUBLISH_PATH_1;
	}

	/**
	 * 公開用作業フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用作業フォルダリスト
	 */
	protected function getDirectorPublishWorkPathList() {
		$ret = array();
		for($i = 1; ; $i++) {
			@ $path = constant('AppConstants::DIRECTOR_PUBLISH_WORK_PATH_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}

	/**
	 * 公開用作業フォルダリストを取得
	 *
	 * @return string 公開用作業フォルダ
	 */
	protected function getDirectorPublishWorkPath() {
		return AppConstants::DIRECTOR_PUBLISH_WORK_PATH_1;
	}

	/**
	 * メッセージを取得します。
	 *
	 * @return string メッセージ
	 */
	private function getMessage() {

		// メッセージが設定されていない場合
		if (empty($this->message)) {
			return MsgConstants::ERROR_SYSTEM; // システムエラー

		// メッセージが設定されている場合
		} else {
			return $this->message; // なにがしかの業務エラー
		}
	}

	/**
	 * 更新者IDを取得します。
	 *
	 * @return 更新者ID
	 */
	private function getModifiedUserId() {

		// 作成の場合
		if ($this->create_publish_div === self::CREATE) {
			return $this->package['Package']['user_id']; // ユーザID

		// 公開の場合
		} else if ($this->create_publish_div === self::PUBLISH) {
			return $this->package['Package']['public_user_id']; // 公開ユーザID
		} else if ($this->create_publish_div === self::APPROVAL) {
			return $this->package['Package']['approval_user_id'];	// 承認ユーザID
		}
	}

	/**
	 * 公開/削除ブログパッケージ公開で、ステージングフォルダの不要画像削除を実施する。
	 *
	 */
	protected function deleteUnnecessaryImagesFromStaging() {

		$this->log('deleteUnnecessaryImagesFromStaging 開始', LOG_DEBUG);

		// -----------------------------------------------------------------------------------------------------------
		// ステージング用フォルダにブログを再構築し、パッケージ情報のステータスを"06:公開完了"にした後に、
		// ステージング画像ファイルビューからis_delete:"1"のレコードを取得し、
		// ステージング用フォルダ([ステージング保存先フォルダ\サイトURL\])からファイルパス名の画像ファイルを削除する。
		// ------------------------------------------------------------------------------------------------------------

		// プロジェクトIDを取得
		$project_id = $this->package['Project']['id'];
		$this->log('プロジェクトID：[' . $project_id . ']');

		// ファイルパスリスト
		$file_path_list = $this->MtImageFile->getFilePathListWithIsDelete($project_id);
		foreach ($file_path_list as $file_path) {

			if ($file_path['is_delete'] == true) {
				// ファイルパス名を取得
				$file_path = $file_path['file_path'];

				// 存在する場合のみ
				if (FileUtil::exists(AppConstants::DIRECTOR_STAGING_PATH . $file_path)) {

					// ステージング用フォルダより画像ファイルを削除
					FileUtil::remove(AppConstants::DIRECTOR_STAGING_PATH . $file_path);
				}
			}
		}

		$this->log('deleteUnnecessaryImagesFromStaging 終了', LOG_DEBUG);
	}

	/**
	 * ブログパッケージ登録で、承認用フォルダにステージング用フォルダの画像をコピーする。
	 *
	 * @param unknown $package パッケージ
	 */
	protected function copyImagesFromStaging($package) {

		$this->log('copyImagesFromStaging 開始', LOG_DEBUG);

		// ----------------------------------------------------------------------------------------
		// ブログパッケージ登録バッチがMTから取得した画像を承認用フォルダにコピーする前に、
		// ステージング画像ファイルビューからis_delete:"0"のレコードを取得し、
		// ステージング用フォルダ([ステージング保存先フォルダ\サイトURL\])から
		// 承認用フォルダ([承認用保存先フォルダ\(パッケージID)\サイトURL\])にファイルをコピーする。
		// -----------------------------------------------------------------------------------------

		$this->log($package);

		// プロジェクトIDを取得
		$project_id = $package['Project']['id'];
		$this->log('プロジェクトID：[' . $project_id . ']');

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']');

		// パッケージIDを取得
		$package_id = $package['Package']['id'];
		$this->log('パッケージID：[' . $package_id . ']');

		// ファイルパスリストを取得
		$file_path_list = $this->MtImageFile->getFilePathListWithIsDelete($project_id);
		foreach ($file_path_list as $file_path) {

			if ($file_path['is_delete'] == false) {

				// ファイルパス名を取得
				$file_path = $file_path['file_path'];
				$this->log('ファイルパス名：['. $file_path . ']', LOG_DEBUG);

				// 存在する場合のみ
				if (FileUtil::exists(AppConstants::DIRECTOR_STAGING_PATH . $file_path)) {

					// ステージング用フォルダより承認用フォルダへ画像ファイルをコピー
					FileUtil::copy(AppConstants::DIRECTOR_STAGING_PATH . $file_path,
							   AppConstants::DIRECTOR_APPROVAL_PATH . DS . $package_id . $file_path);
				}
			}
		}

		$this->log('copyImagesFromStaging 終了', LOG_DEBUG);
	}

	/**
	 * 公開用サーバーリストを取得
	 *
	 * @return multitype:mixed 公開用サーバーリスト
	 */
	protected function getDirectorPublishServerList() {
		$ret = array();
		for($i = 1; ; $i++) {
			@ $path = constant('AppConstants::APPCMD_SERVER_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}
	
	/**
	 * 公開処理
	 * @param unknown $publish_server_list
	 * @param unknown $site_url
	 * @return boolean
	 */
	protected function executeAppcmd($package_id) {

		//----------
		// 事前準備
		//----------
		// 処理開始時刻を記録
		$this->start_at = date('Y-m-d H:i:s');
		
		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
		}

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 公開用サーバー
		$publish_server_list = array();
		foreach ($this->getDirectorPublishServerList() as $director_publish_server) {
			$publish_server = $director_publish_server;
			$this->log('公開用サーバー：[' . $publish_server . ']', LOG_DEBUG);
			$publish_server_list[] = $publish_server;
		}

		// IISの物理フォルダ、IISではスラッシュを受け付けないので、パースする
		$publish_path_iis = str_replace("/", "\\", AppConstants::DIRECTOR_PUBLISH_IIS_PATH . DS . $site_url. DS . $package_id);
		
		// 公開処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_server_list); $i++) {

			// 公開用サーバー
			$publish_server = $publish_server_list[$i];

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
		$package['Project']['public_package_id'] = $package_id;
		$package['Project']["modified"] = null;
		if (!$this->Project->save($package['Project'])) {
			$this->log("パッケージIDの保存に失敗しました。", LOG_ERR);
			$result = AppConstants::RESULT_CD_FAILURE;
			return false;
		}
		
		// 処理終了時刻を記録
		$this->end_at = date('Y-m-d H:i:s');
		
		return true;
	}
	
	/**
	 * ステージングサイトの切り替え
	 * @param unknown $site_url
	 * @return boolean
	 */
	protected function appcmdStaging($package_id) {
		
		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
		}

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);
		
		// IISの物理フォルダ、IISではスラッシュを受け付けないので、パースする
		$publish_path_iis = str_replace("/", "\\", AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url. DS . $package_id);
		
		// ------------------------------------
		// ⑤ 仮想ディレクトリの存在チェック
		// ------------------------------------
		$this->log('⑤ ステージング用仮想ディレクトリの存在チェック', LOG_DEBUG);

		// AppcmdでIISの階層ディレクトリ状況を取得
		$result = shell_exec('%windir%\system32\inetsrv\appcmd list vdir "'
				. AppConstants::APPCMD_STAGING_SITE_NAME . AppConstants::APPCMD_STAGING_PATH_NAME . self::SLASH . $site_url . '" ' . self::SLASH . 'xml');

		// 取得したXMLをパース
		$vdir = simplexml_load_string($result);
		// 仮想ディレクトリ名を取得
		$vdirName = $vdir->VDIR[0]['path'];

		// AppConstants::APPCMD_SITE_NAMEが公開サーバーに存在しない
		if(empty($vdirName)) {
			$this->log('AppConstants::APPCMD_SITE_NAMEが存在しません。', LOG_ERR);
			return false;
		}

		// ------------------------------------
		// ⑥ 仮想ディレクトリの設定・切り替え
		// ------------------------------------
		$this->log('⑥ ステージング用仮想ディレクトリの設定・切り替え', LOG_DEBUG);

		// 仮想ディレクトリが未定義の場合、スラッシュが返る
		if($vdirName == self::SLASH) {
			// AppcmdでIISの仮想ディレクトリの設定
			$result = shell_exec('%windir%\system32\inetsrv\appcmd add vdir '
					. self::SLASH . 'app.name:"' . AppConstants::APPCMD_STAGING_SITE_NAME . '" '
					. self::SLASH . 'path:' . AppConstants::APPCMD_STAGING_PATH_NAME . self::SLASH . $site_url
					. ' ' . self::SLASH . 'physicalPath:' . $publish_path_iis);
		} else {
			// AppcmdでIISの仮想ディレクトリの切り替え
			$result = shell_exec('%windir%\system32\inetsrv\appcmd set vdir '
					. self::SLASH . 'vdir.name:"' . AppConstants::APPCMD_STAGING_SITE_NAME . AppConstants::APPCMD_STAGING_PATH_NAME . self::SLASH . $site_url . '" '
					. self::SLASH . 'physicalPath:' . $publish_path_iis);
		}

		// ------------------------------------
		// ⑦ 仮想ディレクトリの存在チェック
		// ------------------------------------
		$this->log('⑦ ステージング用仮想ディレクトリの存在チェック', LOG_DEBUG);
		// AppcmdでIISの階層ディレクトリ状況を取得
		$result = shell_exec('%windir%\system32\inetsrv\appcmd list vdir "'
				. AppConstants::APPCMD_STAGING_SITE_NAME . AppConstants::APPCMD_STAGING_PATH_NAME . self::SLASH . $site_url . '" ' . self::SLASH . 'xml');

		// 取得したXMLをパース
		$vdir = simplexml_load_string($result);
		// 仮想ディレクトリ名を取得
		$vdirName = $vdir->VDIR[0]['path'];

		// 作成・更新した仮想ディレクトリと、取得した仮想ディレクトリが一致しなければ失敗と判断する
		if($vdirName != AppConstants::APPCMD_STAGING_PATH_NAME . self::SLASH .$site_url) {
			$this->log('仮想ディレクトリの作成に失敗しました。['.$vdirName.']', LOG_ERR);
			//return false;
		}

		// 仮想ディレクトリの物理フォルダ名を取得
		$physicalPathName = $vdir->VDIR[0]['physicalPath'];

		// 作成・更新した物理フォルダと、取得した物理フォルダが一致しなければ失敗と判断する
		if($physicalPathName != $publish_path_iis) {
			$this->log('仮想ディレクトリの物理フォルダの作成に失敗しました。', LOG_ERR);
			//return false;
		}
		
		$this->log('ステージングサイトの切り替え 終了', LOG_DEBUG);
		return true;
	}
	
	
	/**
	 * ステージング用から承認用フォルダへのURL変換処理
	 * @param unknown $approval_path
	 * @return boolean
	 */
	protected function replacePathStagingToApproval($approval_path, $site_url) {
		// -----------------------------------
		// 3. パス置換処理(ステージング⇒承認)
		// -----------------------------------
		$this->log('3. パス置換処理(ホスト名)-承認', LOG_DEBUG);
		// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/)
		$tartget = AppConstants::URL_HOST_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列(http://(承認サイトのURL)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_HOST_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($approval_path, $tartget, $replace)) {
			return false;
		}
		
		$this->log('3. パス置換処理(サイトパス)-承認', LOG_DEBUG);
		// 置換前((ステージングサイトのパス)/(サイトのURL)/)
		$tartget = AppConstants::URL_PATH_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列((承認サイトのパス)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_PATH_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($approval_path, $tartget, $replace)) {
			return false;
		}

		return true;
	}
	
	/**
	 * 作業用から承認用フォルダへのURL変換処理
	 * @param unknown $approval_path
	 * @return boolean
	 */
	protected function replacePathOriginalToApproval($approval_path, $site_url) {
		// -----------------------------------
		// 3. パス置換処理(オリジナル⇒承認)
		// -----------------------------------
		$this->log('3. パス置換処理(ホスト名)-承認', LOG_DEBUG);
		// 置換前(http://(オリジナルのURL)/(サイトのURL)/)
		$tartget = AppConstants::URL_HOST_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列(http://(承認サイトのURL)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_HOST_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($approval_path, $tartget, $replace)) {
			return false;
		}
		
		$this->log('3. パス置換処理(サイトパス)-承認', LOG_DEBUG);
		// 置換前((オリジナルのパス)/(サイトのURL)/)
		$tartget = AppConstants::URL_PATH_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列((承認サイトのパス)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_PATH_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($approval_path, $tartget, $replace)) {
			return false;
		}

		return true;
	}

	/**
	 * ステージング用フォルダを更新
	 * 公開用フォルダからステージング用フォルダにファイルをコピーし、URLを変換する。
	 * @return boolean
	 */
	protected function updateStagingPath($package_id) {
		$this->log('ステージング用フォルダを更新 開始', LOG_DEBUG);

		//----------
		// ①事前準備
		//----------
		
		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
		}
		
		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 公開されているパッケージID
		$public_package_id = $package['Project']['public_package_id'];
		$this->log('公開パッケージID：[' . $public_package_id . ']', LOG_DEBUG);
		if (!is_numeric($public_package_id)) {
			$public_package_id = 0;
		}
		
		// ステージング用フォルダを編集
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id;
		if (FileUtil::mkdir($staging_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ステージング用フォルダ：[' . $staging_path . ']', LOG_DEBUG);
		
		// 公開用フォルダ接続確認
		$publish_path = "";
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			$publish_path = $director_publish_path . DS . $site_url. DS . $package_id;
			if (FileUtil::exists($publish_path) === false) {
				$this->log('フォルダを認識できません。', LOG_ERR);
				$this->log('  フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			break;
		}
		
		
		// 時間のかかる処理を後に移動
		// ------------------------------------------------------------
		// ③ ステージング用フォルダに、作業フォルダのファイルを上書き
		// ------------------------------------------------------------
		$this->log('③ ステージング用フォルダに、作業フォルダのファイルを上書き', LOG_DEBUG);
		
		// ステージング用フォルダをクリーンアップ
		if (FileUtil::rmdirAll($staging_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		
		// 公開用フォルダ ⇒ ステージング用フォルダ
		if (FileUtil::dirCopy($publish_path, $staging_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $publish_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		
		// -----------------------------------
		// 3. パス置換処理(ステージング)
		// -----------------------------------
		$this->log('3. パス置換処理(ホスト名)-ステージング', LOG_DEBUG);
		
		// 置換前(http://(公開サイトのURL)/(サイトのURL)/blog)
		$tartget = self::PUBLIC_SITE_URL
		. self::SLASH . $site_url
		. self::SLASH . self::BLOG
		. self::SLASH;
		
		// 置換後列(http://(ステージングサイトのURL)/(サイトURL)/blog)
		$replace = self::STAGING_SITE_URL
		. self::SLASH . $site_url
		. self::SLASH . self::BLOG
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($staging_path . DS . self::BLOG, $tartget, $replace)) {
			return false;
		}
		
		// 置換前(http://(公開サイトのURL)/(サイトのURL)/)
		$tartget = AppConstants::URL_HOST_REPLACE_PUBLISH
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列(http://(ステージングサイトのURL)/(サイトURL)/)
		$replace = AppConstants::URL_HOST_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($staging_path, $tartget, $replace)) {
			return false;
		}
		
		$this->log('3. パス置換処理(サイトパス)-ステージング', LOG_DEBUG);
		// 置換前((公開サイトのパス)/(サイトのURL)/)
		$tartget = AppConstants::URL_PATH_REPLACE_PUBLISH
		. self::SLASH . $site_url
		. self::SLASH;
		
		// 置換後列((ステージングサイトのパス)/(サイトURL)/)
		$replace = AppConstants::URL_PATH_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;
		
		// htmlファイルを一括置換
		if (!self::replaceUrlPath($staging_path, $tartget, $replace)) {
			return false;
		}
		
		// ステージングサイトの切り替え処理
		if (!self::appcmdStaging($package_id)) {
			$this->log('ステージングサイトの切り替え処理に失敗しました。', LOG_ERR);
			return false;
		}
		
		return true;
	}
	
	/**
	 * URL置換処理
	 * @param unknown $path
	 * @param unknown $tartget
	 * @param unknown $replace
	 * @return boolean
	 */
	protected function replaceUrlPath($path, $tartget, $replace) {
		// 置換前
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);
		
		// 置換後列
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);
		
		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $path . ']', LOG_ERR);
			return false;
		}
		return true;
	}
	
	/**
	 * 承認完了メールを送信する
	 * @return boolean
	 */
	protected function sendMailApprovalRequestOk() {
		$cntl = new BatchEMailApprovalRequestOkController();
		$cntl->setId($this->id);
		
		if ($cntl->execute() === AppConstants::RESULT_CD_FAILURE) {
			$this->log('承認完了メール送信に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 承認エラーメールを送信する
	 * @return boolean
	 */
	protected function sendMailPreOpenNg() {
		$cntl = new BatchEMailPreOpenNgController();
		$cntl->setId($this->id);
		
		if ($cntl->execute() === AppConstants::RESULT_CD_FAILURE) {
			$this->log('承認エラーメール送信に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 公開完了メールを送信する。
	 * @return boolean
	 */
	protected function sendMailOpenFinish() {
		$mail = new BatchEMailOpenFinishController();
		$mail->setId($this->id);				// パッケージid
		$mail->setStartAt($this->start_at);
		$mail->setEndAt($this->end_at);
		if( $mail->execute() === AppConstants::RESULT_CD_FAILURE) {
			$this->log('公開完了メール送信に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
				} else {
			return true;
		}
	}
	
	/**
	 * 公開エラーメールを送信する。
	 * @return boolean
	 */
	protected function sendMailOpenNg() {
		$mail = new BatchEMailOpenNgController();
		$mail->setId($this->id);				// パッケージid
		$mail->setStartAt($this->start_at);
		$mail->setEndAt($this->end_at);
		if( $mail->execute() === AppConstants::RESULT_CD_FAILURE) {
			$this->log('公開エラーメール送信に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 生きてるパッケージの公開用サイトを再構築する
	 * @param unknown $package_id
	 * @return boolean
	 */
	protected function rebuildPublic($package_id) {
		// 生きてるパッケージに対し、公開サイトの再構築
		$packages = self::getAlivePackage($package_id);
		
		$this->log('対象パッケージ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($packages, LOG_DEBUG);
		$this->log('対象パッケージ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);
		
		
		foreach ($packages as $package) {
			$package_id2 = $package['Package']['id'];
			$operationCd = $package['Package']['operation_cd'];
			$isBlog = $package['Package']['is_blog'];
		
			if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 公開パッケージ作成
				$ret = self::copyPublicPackagePublic($package_id2);
			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 削除パッケージ作成
				$ret = self::copyPublicPackageDelete($package_id2);
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				$ret = self::copyPublicBlogPackage($package_id2);
			}
			if (!$ret) return false;
		}
		return true;
	}
	
	
	/**
	 * 公開フォルダ内の生きているパッケージを取得する。
	 * @param unknown $package_id
	 * @return パッケージ
	 */
	protected function getAlivePackage($package_id) {
		$sql = <<< EOT
select Package.* from packages Package
where Package.project_id in (
	select pk.project_id from packages pk
	where pk.id = ?)
and Package.is_del = '0'
and Package.id <> ?
and Package.status_cd in
EOT;
		$sql .= " ("
			. Status::STATUS_CD_APPROVAL_OK . ","
			. Status::STATUS_CD_RELEASE_ERROR . ","
			. Status::STATUS_CD_RELEASE_NOW . ","
			. Status::STATUS_CD_RELEASE_REJECT . ","
			. Status::STATUS_CD_RELEASE_RESERVE 
			.")";
			
		return $this->Package->query($sql, array($package_id, $package_id));
	}

	/** 
	 * パッケージ公開用公開サイトコピー処理
	 * @param unknown $package_id
	 * @return boolean
	 */
	protected function copyPublicPackagePublic($package_id) {

		$this->log('公開パッケージ承認(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		
		//----------
		// ①事前準備
		//----------
		
		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
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
		
		// パッケージフォルダを編集
		$upload_path = AppConstants::DIRECTOR_UPLOAD_PATH;
		$this->log('パッケージ用フォルダ： [' . $upload_path . ']', LOG_DEBUG);

		// 作業用フォルダを編集
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $package_id . DS . $site_url;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);
		
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
			$publish_path = $director_publish_path . DS . $site_url . DS . $package_id;
			if (FileUtil::rmdirAll($publish_path) === false) {
				$this->log('フォルダの削除に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			if (FileUtil::mkdir($publish_path) === false) {
				$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用フォルダ：[' . $publish_path . ']', LOG_DEBUG);
			$publish_path_list[] = $publish_path;
		}
		
		// ----------------------------------------------------------------
		// ② ワークにステージングの内容をコピーし、パッケージの内容を上書き
		// ----------------------------------------------------------------
		$this->log('② ワークにステージングの内容をコピーし、パッケージの内容を上書き', LOG_DEBUG);

		// アップロードファイル名を取得
		$upload_file_name = $package['Package']['upload_file_name'];
		if (empty($upload_file_name)) {
			$this->log('アップロードファイル名が設定されていません。パッケージID：[' . $package_id . ']', LOG_ERR);
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
		if (FileUtil::extract($upload_path . DS. $upload_file_name, AppConstants::DIRECTOR_WORK_PATH . DS . $package_id) === false) {
			$this->log('ファイルの解凍に失敗しました。', LOG_ERR);
			$this->log('  ZIPファイル：[' . $upload_path . DS. $upload_file_name . ']', LOG_ERR);
			$this->log('  展開先     ：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		
		// ステージングフォルダの存在チェック
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id;
		$is_exist_staging = false;
		if (FileUtil::exists($staging_path)) {
			$is_exist_staging = true;
			$this->log('ステージング用フォルダは存在します。：[' . $staging_path . ']', LOG_DEBUG);
		} else {
			$is_exist_staging = false;
			$this->log('ステージング用フォルダは存在しません。：[' . $staging_path . ']', LOG_DEBUG);
		}
		
		// ------------------------------------
		// ③ 公開用フォルダにコピー
		// ------------------------------------
		$this->log('④ 公開用フォルダにコピー', LOG_DEBUG);

		// コピー処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_path_list); $i++) {
		
			// 公開用フォルダ
			$publish_path = $publish_path_list[$i];
		
			// ステージング用フォルダ ⇒ 公開用フォルダ
			if ($is_exist_staging) {
				if (FileUtil::dirCopy($staging_path, $publish_path) === false) {
					$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
					$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
					$this->log('  コピー先：[' . $publish_path . ']', LOG_ERR);
					return false;
				}
}

			// 作業フォルダ ⇒ 公開用フォルダ
			if (FileUtil::dirCopy($work_path, $publish_path) === false) {
				$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
				$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
				$this->log('  コピー先：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
				
			// -----------------------------------
			// 3. パス置換処理(公開用)
			// -----------------------------------
		
			// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/)
			$tartget = AppConstants::URL_HOST_REPLACE_STAGING
			. self::SLASH . $site_url
			. self::SLASH;
		
			// 置換後列(http://(公開サイトのURL)/(サイトURL)/)
			$replace = AppConstants::URL_HOST_REPLACE_PUBLISH
			. self::SLASH . $site_url
			. self::SLASH;
		
			// htmlファイルを一括置換
			if (!self::replaceUrlPath($publish_path, $tartget, $replace)) {
				return false;
			}
				
			$this->log('3. パス置換処理(サイトパス)-公開用', LOG_DEBUG);
			// 置換前((ステージングサイトのパス)/(サイトのURL)/)
			$tartget = AppConstants::URL_PATH_REPLACE_STAGING
			. self::SLASH . $site_url
			. self::SLASH;
		
			// 置換後列((公開サイトのパス)/(パッケージID)/(サイトURL)/)
			$replace = AppConstants::URL_PATH_REPLACE_PUBLISH
			. self::SLASH . $site_url
			. self::SLASH;
		
			// htmlファイルを一括置換
			if (!self::replaceUrlPath($publish_path, $tartget, $replace)) {
				return false;
			}
		
		}
		$this->log('公開パッケージ承認(内部) 成功', LOG_DEBUG);
		
		return true;
	}

	/**
	 * パッケージ削除用公開サイトコピー処理
	 * @param unknown $package_id
	 * @return boolean
	 */
	protected function copyPublicPackageDelete($package_id) {
		
		$this->log('公開パッケージ承認(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);

		//----------
		// ①事前準備
		//----------

		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
		}

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 公開されているパッケージID
		$public_package_id = $package['Project']['public_package_id'];
		$this->log('公開パッケージID：[' . $public_package_id . ']', LOG_DEBUG);
		if (!is_numeric($public_package_id)) {
			$public_package_id = 0;
		}
		
		// パッケージフォルダを編集
		$upload_path = AppConstants::DIRECTOR_UPLOAD_PATH;
		$this->log('パッケージ用フォルダ： [' . $upload_path . ']', LOG_DEBUG);

		// 作業用フォルダを編集
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $package_id . DS . $site_url;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);

		// ステージング用フォルダを編集
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id;
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
			$publish_path = $director_publish_path . DS . $site_url. DS . $package_id;
			if (FileUtil::rmdirAll($publish_path) === false) {
				$this->log('フォルダの削除に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			if (FileUtil::mkdir($publish_path) === false) {
				$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用フォルダ：[' . $publish_path . ']', LOG_DEBUG);
			$publish_path_list[] = $publish_path;
		}

		// ------------------------------------
		// ② 作業用フォルダにコピー
		// ------------------------------------
		$this->log('② 作業用フォルダにコピー', LOG_DEBUG);
		
		// 作業用フォルダをクリーンアップ
		if (FileUtil::rmdirAll($work_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		
		// ステージング用フォルダ ⇒ ワークフォルダ
		if (FileUtil::dirCopy($staging_path, $work_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $work_path . ']', LOG_ERR);
			return false;
		}
			
		
		// --------------------------------------------------------
		// ③ 削除指示されたファイルを作業用フォルダから削除
		// --------------------------------------------------------
		$this->log('③ 削除指示されたファイルを作業用フォルダから削除', LOG_DEBUG);
		
		// コンテンツファイル情報を取得
		$contents_file_list = $this->ContentsFile->findListByPackageId($package_id);
		if (empty($contents_file_list)) {
			$this->log('コンテンツファイル情報の取得に失敗しました。[' . $package_id . ']', LOG_ERR);
			return false;
		}
		//$this->log($contents_file, LOG_DEBUG);
		
		// 削除ファイルリストを取得
		$$remove_file_list = array();
		foreach ($contents_file_list as $contents_file) {
			$work = $contents_file['ContentsFile']['file_path'];
			$work = StringUtil::trim($work, DS);
			$work = StringUtil::ltrimOnce($work, $site_url . DS);
			$remove_file_list[] = $work;
		}
		
		// 降順にソート
		if (rsort($remove_file_list) === false) {
			$this->log('ソートに失敗しました。', LOG_ERR);
			return false;
		}
		$this->log('ソートリスト↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($remove_file_list, LOG_DEBUG);
		$this->log('ソートリスト↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);
		
		// ------------------------------------
		// ④ ワーク用フォルダから削除
		// ------------------------------------
		$this->log('④ 公開用フォルダから削除', LOG_DEBUG);
		
		foreach ($remove_file_list as $remove_file) {
			$file = trim($work_path . DS . $remove_file, DS);
			if (FileUtil::exists($file)) {
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
				$this->log(__FILE__ . '   対象ファイルパス名：[' . $file . ']', LOG_ERR);
				return false;
			}
		}
			
		// -----------------------------------
		// 3. パス置換処理
		// -----------------------------------

		// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/)
		$tartget = AppConstants::URL_HOST_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;

		// 置換後列(http://(公開サイトのURL)/(サイトURL)/)
		$replace = AppConstants::URL_HOST_REPLACE_PUBLISH
		. self::SLASH . $site_url
		. self::SLASH;

		// htmlファイルを一括置換
		if (!self::replaceUrlPath($work_path, $tartget, $replace)) {
			return false;
		}

		$this->log('3. パス置換処理(サイトパス)-公開用', LOG_DEBUG);
		// 置換前((ステージングサイトのパス)/(サイトのURL)/)
		$tartget = AppConstants::URL_PATH_REPLACE_STAGING
		. self::SLASH . $site_url
		. self::SLASH;

		// 置換後列((公開サイトのパス)/(パッケージID)/(サイトURL)/)
		$replace = AppConstants::URL_PATH_REPLACE_PUBLISH
		. self::SLASH . $site_url
		. self::SLASH;

		// htmlファイルを一括置換
		if (!self::replaceUrlPath($work_path, $tartget, $replace)) {
			return false;
		}
		
		// コピー処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_path_list); $i++) {
		
			// 公開用フォルダ
			$publish_path = $publish_path_list[$i];
		
			// ------------------------------------
			// ⑤ 公開用フォルダにコピー
			// ------------------------------------
			$this->log('④ 公開用フォルダにコピー', LOG_DEBUG);

			// ステージング用フォルダ ⇒ 公開用フォルダ
			if (FileUtil::dirCopy($work_path, $publish_path) === false) {
				$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
				$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
				$this->log('  コピー先：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			
		}

		$this->log('公開パッケージ承認(内部) 成功', LOG_DEBUG);
		
		return true;
	}
	
	/**
	 * ブログパッケージ用公開サイトコピー処理
	 * @param unknown $package_id
	 * @return boolean
	 */
	protected function copyPublicBlogPackage($package_id) {
		
		$this->log('ブログパッケージ承認 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		
		//----------
		// 事前準備
		//----------
		
		// パッケージIDをキーに、パッケージを１件取得
		$package = $this->Package->findByPackageId($package_id);
		if(empty($package)) {
			$this->log('パッケージの取得に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			return false; // 失敗
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
		
		// 作業用フォルダの存在チェック
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $package_id . DS . $site_url . DS . self::BLOG;
		if (FileUtil::exists($work_path) === false) {
			$this->log('フォルダが存在しません。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);
		
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
			$publish_path = $director_publish_path . DS . $site_url . DS . $package_id;
			if (FileUtil::rmdirAll($publish_path) === false) {
				$this->log('フォルダの削除に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			if (FileUtil::mkdir($publish_path) === false) {
				$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $publish_path . ']', LOG_ERR);
				return false;
			}
			$this->log('公開用フォルダ：[' . $publish_path . ']', LOG_DEBUG);
			$publish_path_list[] = $publish_path;
		}
		
		// ステージングフォルダの存在チェック
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id;
		$is_exist_staging = false;
		if (FileUtil::exists($staging_path)) {
			$is_exist_staging = true;
			$this->log('ステージング用フォルダは存在します。：[' . $staging_path . ']', LOG_DEBUG);
		} else {
			$is_exist_staging = false;
			$this->log('ステージング用フォルダは存在しません。：[' . $staging_path . ']', LOG_DEBUG);
		}
		
		// ----------------
		// 2. パス置換処理
		// ----------------
		// コピー処理：公開用サーバ分繰り返す
		for ($i = 0; $i < count($publish_path_list); $i++) {
		
			// 公開用フォルダ
			$publish_path = $publish_path_list[$i];
		
			// ------------------------------------
			// ④ 公開用フォルダにコピー
			// ------------------------------------
			$this->log('④ 公開用フォルダにコピー', LOG_DEBUG);
		
			// ステージング用フォルダ ⇒ 公開用フォルダ
			if ($is_exist_staging) {
				if (FileUtil::dirCopy($staging_path, $publish_path) === false) {
					$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
					$this->log('  コピー元：[' . $staging_path . ']', LOG_ERR);
					$this->log('  コピー先：[' . $publish_path . ']', LOG_ERR);
					return false;
				}
			}
				
			// 公開用フォルダの/blog以下をクリア
			if (FileUtil::rmdirAll($publish_path . DS . self::BLOG) === false) {
				$this->log('フォルダの削除に失敗しました。', LOG_ERR);
				$this->log('  対象フォルダ：[' . $publish_path . DS . self::BLOG . ']', LOG_ERR);
				return false;
			}
		
			// ワークフォルダ ⇒ 公開用フォルダ
			if (FileUtil::dirCopy($work_path, $publish_path . DS . self::BLOG) === false) {
				$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
				$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
				$this->log('  コピー先：[' . $publish_path . DS . self::BLOG . ']', LOG_ERR);
				return false;
			}
		
			// ------------------------------------------------------
			// HTMLファイルで指定されているURLを公開用に書き換える
			// ------------------------------------------------------
			$this->log('HTMLファイルで指定されているURLを公開用に書き換える', LOG_DEBUG);
		
			// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
			$tartget = self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG. self::SLASH;
		
			// 置換後列(http://(公開サイトのURL)/(サイトURL)/blog)
			$replace = self::PUBLIC_SITE_URL . self::SLASH . $site_url . self::SLASH . self::BLOG. self::SLASH;
		
			// htmlファイルを一括置換
			if (!self::replaceUrlPath($publish_path . DS . self::BLOG, $tartget, $replace)) {
				return false;
			}
				
			// -----------------------------------
			// 3. パス置換処理(公開用)
			// -----------------------------------
		
			// 置換前(http://(ステージングサイトのURL)/(サイトのURL)/)
			$tartget = AppConstants::URL_HOST_REPLACE_STAGING
			. self::SLASH . $site_url
			. self::SLASH;
		
			// 置換後列(http://(公開サイトのURL)/(サイトURL)/)
			$replace = AppConstants::URL_HOST_REPLACE_PUBLISH
			. self::SLASH . $site_url
			. self::SLASH;
		
			// htmlファイルを一括置換
			if (!self::replaceUrlPath($publish_path, $tartget, $replace)) {
				return false;
			}
				
			$this->log('3. パス置換処理(サイトパス)-公開用', LOG_DEBUG);
			// 置換前((ステージングサイトのパス)/(サイトのURL)/)
			$tartget = AppConstants::URL_PATH_REPLACE_STAGING
			. self::SLASH . $site_url
			. self::SLASH;
		
			// 置換後列((公開サイトのパス)/(パッケージID)/(サイトURL)/)
			$replace = AppConstants::URL_PATH_REPLACE_PUBLISH
			. self::SLASH . $site_url
			. self::SLASH;
		
			// htmlファイルを一括置換
			if (!self::replaceUrlPath($publish_path, $tartget, $replace)) {
				return false;
			}
		}
		
		$this->log('ブログパッケージ承認 成功', LOG_DEBUG);
		
		return true; // 成功
	}
}
?>
