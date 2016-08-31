<?php

App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');

/**
 * プロジェクト掃除バッチ
 * 削除されたプロジェクトの公開用のフォルダを削除する。
 *
 * @author tasano
 *
*/
class BatchCleanProjectController extends BatchAppController {

	/** モデルを登録 */
	public $uses = array('Project');

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

		$this->log('プロジェクト掃除バッチを開始しました。', LOG_INFO);

		// 掃除対象のプロジェクトIDを取得
		$target_list = $this->Project->getCleaningTargetList();

		$this->log('対象プロジェクトID↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($target_list, LOG_DEBUG);
		$this->log('対象プロジェクトID↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		if($target_list === false) {
			$this->log('掃除対象プロジェクトの取得に失敗しました。', LOG_ERR);
			$this->log('プロジェクト掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		}

		if (empty($target_list)) {
			$this->log('掃除対象プロジェクトが存在しません。', LOG_DEBUG);
			$this->log('プロジェクト掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}

		// エラーフラグ
		$error_flg = false;

		foreach ($target_list as $project) {

			// トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				$error_flg = true;
				continue;
			}

			// 内部処理実行
			if ($this->executeInner($project) === false) {

				// エラーフラグを立てる
				$error_flg = true;

				// ロールバック
				if ($this->Components->Transaction->rollback(null) === false) {
					$this->log('ロールバックに失敗しました。', LOG_ERR);
					$error_flg = true;
				}

			} else {

				// 更新値
				$project['Project']['is_clean']			= AppConstants::FLAG_ON; 		// 掃除フラグ
				$project['Project']['modified_user_id'] = AppConstants::USER_ID_SYSTEM;	// 更新者ID

				// パッケージを更新
				$this->Project->save($project);

				// コミット
				if($this->Transaction->commit() === false) {
					$this->log('コミットに失敗しました。', LOG_ERR);
					$error_flg = true;
				}
			}
		}

		if ($error_flg == true) {
			$this->log('プロジェクト掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('プロジェクト掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}
	}

	/**
	 * 内部処理
	 *
	 * @param  unknown $project プロジェクト
	 * @return boolean          成否
	 */
	 private function executeInner($project) {

		$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 開始', LOG_DEBUG);

		// サイトURLを取得
		$site_url = $project['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);
		if (empty($site_url)) {
			$this->log('サイトURLが不正です。', LOG_ERR);
			$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		$director_publish_path_list = $this->getDirectorPublishPathList();

		// 公開用のフォルダ削除
		foreach ($director_publish_path_list as $director_publish_path) {

			$this->log('公開用フォルダ：[' . $director_publish_path . ']', LOG_DEBUG);

			if (FileUtil::exists($director_publish_path . DS . $site_url)) {
				if (FileUtil::rmdirAll($director_publish_path . DS . $site_url) === false) {
					$this->log('公開用フォルダの削除に失敗しました。サイトURL：[' . $site_url . ']', LOG_ERR);
					$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 異常終了', LOG_DEBUG);
					return false;;
				}
			}
		}

		// ステージング用フォルダを削除
		if (FileUtil::exists(AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url)) {
			if (FileUtil::rmdirAll(AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url) === false) {
				$this->log('ステージング用フォルダの削除に失敗しました。サイトURL：[' . $site_url . ']', LOG_ERR);
				$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 異常終了', LOG_DEBUG);
				return false;;
			}
		}

		// MTブログフォルダを削除
		if (FileUtil::exists(AppConstants::DIRECTORY_MT_BLOG_PATH . DS . $site_url)) {
			if (FileUtil::rmdirAll(AppConstants::DIRECTORY_MT_BLOG_PATH . DS . $site_url) === false) {
				$this->log('MTブログフォルダの削除に失敗しました。サイトURL：[' . $site_url . ']', LOG_ERR);
				$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 異常終了', LOG_DEBUG);
				return false;;
			}
		}

		$this->log('削除されたプロジェクトの公開用のフォルダを削除(内部) 正常終了', LOG_DEBUG);
	}

	/**
	 * 公開用フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用フォルダリスト
	 */
	private function getDirectorPublishPathList() {
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

?>