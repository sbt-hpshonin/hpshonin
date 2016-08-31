<?php
App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');

/**
 * 公開サイトファイル掃除バッチ
 *
 * @author smurata
 *
*/
class BatchCleanPublicFileController extends BatchAppController {

	/** モデルを登録 */
	public $uses = array('Package');

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();
	}

	/**
	 * 実行
	 *
	 * @return string 結果コード
	 */
	public function execute() {

		$this->log('公開サイトファイル掃除バッチを開始しました。', LOG_INFO);

		// エラーフラグ
		$error_flg = false;

		// 内部実行
		if  ($this->executeInner() === false) {
			// エラーフラグを立てる
			$error_flg = true;
		}

		if ($error_flg == true) {
			$this->log('公開サイトファイル掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('公開サイトファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功)
		}
	}

	/**
	 * 内部処理
	 *
	 * @param unknown $project プロジェクト
	 * @return boolean         成否
	 */
	private function executeInner() {
		$this->log('公開サイトファイル掃除バッチ(内部) 開始', LOG_DEBUG);
		
		$packages = $this->Package->getAlivePackages();

		// 全公開フォルダに対して処理を実行
		$publish_paths = self::getDirectorPublishPathList();
		$this->log('対象公開パス↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($publish_paths, LOG_DEBUG);
		$this->log('対象公開パス↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		foreach($publish_paths as $publish_path) {
			
			foreach ($packages as $package) {
				$site_url = $package['pj']['site_url'];
				$package_id = $package['pkg']['id'];
				
				// パッケージIDを取得
				$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
				if (empty($package_id)) {
					$this->log('パッケージIDが不正です。', LOG_ERR);
					$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
					return false;
				}
		
				// サイトURLを取得
				$this->log('サイトURL名：[' . $site_url . ']', LOG_DEBUG);
				if (empty($site_url)) {
					$this->log('サイトURL名が不正です。', LOG_ERR);
					$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
					return false;
				}
				
				// サイトURLの物理パスの存在チェック
				$site_path = $publish_path . DS . $site_url . DS . $package_id;
				$this->log('削除サイト：[' . $site_path . ']', LOG_DEBUG);
				if (!FileUtil::exists($site_path)) {
					$this->log('サイトURLの物理パスが存在しません。サイトパス：[' . $site_path . ']', LOG_DEBUG);
					continue;
				}

				if(!FileUtil::rmdirAll($site_path)) {
					$this->log('公開サイトのファイル削除に失敗しました。ファイルパス：[' . $site_path. ']', LOG_ERR);
					$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
					return false;;
				}
			}
		}
		
		// ステージングサイト掃除処理
		if (!self::delStagingSite()) {
			return false;
		}
		
		$this->log('公開サイトファイル掃除バッチ(内部) 完了', LOG_DEBUG);
		
		return true;
	}

	/**
	 * ステージングサイト掃除処理
	 * @return boolean
	 */
	private function delStagingSite() {

		$this->log('公開サイトファイル掃除バッチ(ステージング削除処理) 開始', LOG_DEBUG);

		$packages = $this->Package->getAlivePackages();
		
		foreach ($packages as $package) {
			$site_url = $package['pj']['site_url'];
			$package_id = $package['pkg']['id'];
	
			// パッケージIDを取得
			$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
			if (empty($package_id)) {
				$this->log('パッケージIDが不正です。', LOG_ERR);
				$this->log('公開サイトファイル掃除バッチ(ステージング削除処理) 異常終了', LOG_DEBUG);
				return false;
			}
	
			// サイトURLを取得
			$this->log('サイトURL名：[' . $site_url . ']', LOG_DEBUG);
			if (empty($site_url)) {
				$this->log('サイトURL名が不正です。', LOG_ERR);
				$this->log('公開サイトファイル掃除バッチ(ステージング削除処理) 異常終了', LOG_DEBUG);
				return false;
			}
	
			// サイトURLの物理パスの存在チェック
			$site_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $package_id;
			$this->log('削除サイト：[' . $site_path . ']', LOG_DEBUG);
			if (!FileUtil::exists($site_path)) {
				$this->log('サイトURLの物理パスが存在しません。サイトパス：[' . $site_path . ']', LOG_DEBUG);
				continue;
			}
	
			if(!FileUtil::rmdirAll($site_path)) {
				$this->log('ステージングサイトのファイル削除に失敗しました。ファイルパス：[' . $site_path. ']', LOG_ERR);
				$this->log('公開サイトファイル掃除バッチ(ステージング削除処理) 異常終了', LOG_DEBUG);
				return false;;
			}
		}
		
		$this->log('公開サイトファイル掃除バッチ(ステージング削除処理) 完了', LOG_DEBUG);
		
		return true;
	}
	
	
	
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
}