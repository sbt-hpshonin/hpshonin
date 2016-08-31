<?php

App::uses('BatchAppController', 'Controller');
App::uses('MsgConstants', 'Lib/Constants');
App::uses('TransactionComponent', 'Component');
App::uses('MtXmlRpcComponent', 'Controller/Component');

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

	/** 編集サイトURL */
	const EDIT_SITE_URL     = 'http://hp-shonin.cloudapp.net/pe/static/edt/blogroot';
	/** 承認サイトURL */
	const APPROVAL_SITE_URL = 'http://hp-shonin.cloudapp.net/pe/static/prv';
	/** ステージングサイトURL */
	const STAGING_SITE_URL  = 'http://hp-shonin.cloudapp.net/pe/static/stg';
	/** 公開サイトURL */
	const PUBLIC_SITE_URL   = 'http://reverpro.cloudapp.net/ss';

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

		// トランザクション開始
		if ($this->Transaction->begin() === false) {
			$this->log('トランザクション開始に失敗しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		}

		if ($this->execute_inner($this->id))
		{
			// +++++
			// 成功
			// +++++
			$this->log('内部処理成功', LOG_DEBUG);

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
			$this->package['Package']['status_cd']		  = $this->status_cd_on_normal; // ステータスCD
			$this->package['Package']['message']		  = null; 						// メッセージ
			$this->package['Package']['modified_user_id'] = $this->getModifiedUserId();	// 更新者
			$this->package['Package']['modified']		  = null;						// 更新日時

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

			// 結果コード(:成功)を返却
			$this->log($this->batch_name . 'を正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS;

		} else {

			// +++++
			// 失敗
			// +++++
			$this->log('内部処理失敗', LOG_DEBUG);

			// ロールバック
			if ($this->Components->Transaction->rollback(null) === false) {
				$this->log('ロールバックに失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
			}

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
				AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
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
				AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
			}

			// コミット
			if($this->Transaction->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				$this->log($this->batch_name . 'を異常終了しました。', LOG_ERR);
				AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗);
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
	 * 公開用フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用フォルダリスト
	 */
	protected function getDirectorPublishPathList() {
		$ret = array();
		for($i = 1; ; $i++) {
			$path = constant('AppConstants::DIRECTOR_PUBLISH_PATH_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}

	/**
	 * 公開用フォルダを取得
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
			$path = constant('AppConstants::DIRECTOR_PUBLISH_WORK_PATH_' . $i);
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
			$path = constant('AppConstants::APPCMD_SERVER_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}
}

?>
