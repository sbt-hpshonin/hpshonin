<?php

App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');

/**
 * パッケージ登録関連ファイル掃除バッチ
 * パッケージ登録のコンテンツファイル(アップロードしたZIPファイル)等を削除する。
 *
 * @author tasano
 *
*/
class BatchCleanPackageContentFileController extends BatchAppController {

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

		$this->log('パッケージ登録関連ファイル掃除バッチを開始しました。', LOG_INFO);

		// コンテンツファイル削除対象のプロジェクトIDを取得
		$package_list = $this->Package->getDeadPackages();

		$this->log('対象パッケージID↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($package_list, LOG_DEBUG);
		$this->log('対象パッケージID↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		if($package_list === false) {
			$this->log('対象パッケージの取得に失敗しました。', LOG_ERR);
			$this->log('パッケージ登録関連ファイル掃除バッチを異常終了しました。', LOG_DEBUG);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		}

		if (empty($package_list)) {
			$this->log('対象パッケージが存在しません。', LOG_DEBUG);
			$this->log('パッケージ登録関連ファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功)
		}

		// エラーフラグ
		$error_flg = false;

		foreach ($package_list as $package) {

			// パッケージIDを取得
			$package_id = $package['pkg']['id'];
			$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);

			// トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				$error_flg = true;
				break;
			}

			// パッケージを取得
			$package = $this->Package->findByPackageIdWithoutConsideringIsDel($package_id);
			if(empty($package)) {
				$this->log('パッケージ情報の取得失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
				$error_flg = true;
				// ロールバック
				if ($this->Components->Transaction->rollback(null) === false) {
					$this->log('ロールバックに失敗しました。', LOG_ERR);
					break;
				}
				continue;
			}

			// 内部実行
			if ($this->executeInner($package) === false) {

				$error_flg = true;

			} else {

				// 更新値
				$package['Package']['is_clean_file']	= AppConstants::FLAG_ON; 		// ファイル掃除フラグ
				// $package['Package']['modified_user_id'] = AppConstants::USER_ID_SYSTEM;	// 更新者ID

				// パッケージを更新
				if ($this->Package->save($package) === false) {
					$error_flg = true;
					$this->log('packagesテーブルの更新に失敗しました。', LOG_ERR);
					if ($this->Components->Transaction->rollback(null) === false) {
						$this->log('ロールバックに失敗しました。', LOG_ERR);
						break;
					}
				} else {
					// コミット
					if($this->Transaction->commit() === false) {
						$this->log('コミットに失敗しました。', LOG_ERR);
						$error_flg = true;
						break;
					}
				}
			}
		}

		if ($error_flg === true) {
			$this->log('パッケージ登録関連ファイル掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('パッケージ登録関連ファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功)
		}
	}

	/**
	 * 内部処理
	 *
	 * @param unknown $package パッケージ
	 * @return boolean         成否
	 */
	private function executeInner($package) {

		$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 開始', LOG_DEBUG);

		// ---------------------------------------------------------------------
		// コンテンツファイル情報から該当するパッケージのファイルパス名を取得し、
		// システム保存フォルダから全て削除する。
		// ---------------------------------------------------------------------

		// パッケージIDを取得
		$package_id = $package['Package']['id'];
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		if (empty($package_id)) {
			$this->log('パッケージIDが不正です。', LOG_ERR);
			$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		// アップロードファイル名を取得
		$upload_file_name = $package['Package']['upload_file_name'];
		$this->log('アップロードファイル名：[' . $upload_file_name . ']', LOG_DEBUG);

		// アップロードファイルを削除
		if (!empty($upload_file_name) &&
			FileUtil::exists(AppConstants::DIRECTOR_UPLOAD_PATH . DS . $upload_file_name)) {
			if (FileUtil::remove(AppConstants::DIRECTOR_UPLOAD_PATH . DS . $upload_file_name) === false) {
				$this->log('アップロードファイルの削除に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
				$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
				return false;
			}
		}

		// 作業用フォルダに展開されたファイルを除去
		if (FileUtil::rmdirAll(AppConstants::DIRECTOR_WORK_PATH . DS . $package_id) === false) {
			$this->log('作業用フォルダに展開されたファイルの削除に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		// 承認用フォルダにコピーされたファイルを除去
		if (FileUtil::rmdirAll(AppConstants::DIRECTOR_APPROVAL_PATH . DS . $package_id) === false) {
			$this->log('承認用フォルダにコピーされたファイルの削除に失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
			$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		$this->log('パッケージ登録関連ファイル掃除バッチ(内部) 正常終了', LOG_DEBUG);
		return true;
	}

}