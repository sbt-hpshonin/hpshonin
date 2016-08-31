<?php

App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');
App::uses('MtXmlRpcComponent', 'Controller/Component');

/**
 * ブログパッケージ登録関連ファイル掃除バッチ
 * ブログパッケージ登録の画像ファイル等を削除する。
 *
 * @author tasano
 *
*/
class BatchCleanBlogPackageImageFileController extends BatchAppController {

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

		$this->log('ブログパッケージ登録関連ファイル掃除バッチを開始しました。', LOG_INFO);

		// ブログ画像削除対象のプロジェクトIDを取得
		$target_list = $this->Package->getCleaningTargetListForDeleteBlogImage();

		$this->log('対象パッケージID↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($target_list, LOG_DEBUG);
		$this->log('対象パッケージID↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		if($target_list === false) {
			$this->log('ブログ画像削除対象パッケージの取得に失敗しました。', LOG_ERR);
			$this->log('ブログパッケージ登録関連ファイル掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		}

		if (empty($target_list)) {
			$this->log('ブログ画像削除対象パッケージが存在しません。', LOG_DEBUG);
			$this->log('ブログパッケージ登録関連ファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}

		// エラーフラグ
		$error_flg = false;

		foreach ($target_list as $target) {

			// パッケージIDを取得
			$package_id = $target['packages']['id'];
			$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);

			// トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				$error_flg = true;
				continue;
			}

			// パッケージを取得
			$package = $this->Package->findByPackageIdWithoutConsideringIsDel($package_id);
			if(empty($package)) {
				$this->log('パッケージ情報の取得失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
				$error_flg = true;
				continue;
			}

			// 内部処理実行
			if ($this->executeInner($package) === false) {

				// エラーフラグを立てる
				$error_flg = true;

				// ロールバック
				if ($this->Components->Transaction->rollback(null) === false) {
					$this->log('ロールバックに失敗しました。', LOG_ERR);
					$error_flg = true;
				}

			} else {

				// 更新値
				$package['Package']['is_clean_file']	= AppConstants::FLAG_ON; 		// ファイル掃除フラグ
				$package['Package']['modified_user_id'] = AppConstants::USER_ID_SYSTEM;	// 更新者ID
				$package['Package']['modified']			= null;							// 更新日時

				// パッケージを更新
				$this->Package->save($package);

				// コミット
				if($this->Transaction->commit() === false) {
					$this->log('コミットに失敗しました。', LOG_ERR);
					$error_flg = true;
				}
			}
		}

		if ($error_flg == true) {
			$this->log('ブログパッケージ登録関連ファイル掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('ブログパッケージ登録関連ファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}
	}

	/**
	 * 内部処理
	 *
	 * @param  unknown $package パッケージ
	 * @return boolean          成否
	 */
	 private function executeInner($package) {

		$this->log('ブログパッケージ登録関連ファイル掃除(内部) 開始', LOG_DEBUG);

		// パッケージIDを取得
		$package_id = $package['Package']['id'];
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		if (empty($package_id)) {
			$this->log('パッケージIDが不正です。', LOG_ERR);
			$this->log('ブログパッケージ登録関連ファイル掃除(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		// ブログ画像用フォルダのブログ用画像を削除
		if (FileUtil::rmdirAll(AppConstants::DIRECTOR_BLOG_IMAGE_PATH . DS . $package_id) === false) {
			$this->log('ブログ画像の削除に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			$this->log('ブログパッケージ登録関連ファイル掃除(内部) 異常終了', LOG_DEBUG);
			return false;;
		}

		// 承認用フォルダのファイルを削除
		if (FileUtil::rmdirAll(AppConstants::DIRECTOR_APPROVAL_PATH . DS . $package_id) === false) {
			$this->log('承認用フォルダにコピーされたファイルの削除に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			$this->log('ブログパッケージ登録関連ファイル掃除(内部) 異常終了', LOG_DEBUG);
			return false;;
		}

		$this->log('ブログパッケージ登録関連ファイル掃除(内部) 正常終了', LOG_DEBUG);
	}

}