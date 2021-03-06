<?php

App::uses('BatchPackageController', 'Controller');
App::uses('AppConstants', 'Lib/Constants');

/**
 * パッケージ差し戻しバッチ
 *
 * @author tasano
 *
*/
class BatchPublishPackageRestoreController extends BatchPackageController {
	
	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::PUBLISH;
		// バッチ名を設定
		$this->batch_name = 'パッケージ差し戻しバッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_RELEASE_COMPLETE;	// '06'(公開完了)
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_RELEASE_ERROR;	// '96'(公開エラー)
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {

		$this->log('パッケージ差し戻し 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);
		
		// 公開処理
		if(!parent::executeAppcmd($this->id)) {
			return false;
		}
		
		$this->log('パッケージ差し戻し 終了', LOG_DEBUG);
		
		return true; // 成功
	}

	/**
	 * 成功時後実行
	 * 
	 * @return boolean 成否
	 */
	function execute_after_success() {
		$this->log('パッケージ差し戻し(成功時後実行) 開始', LOG_DEBUG);

		$success = false;
		for ($i = 1 ; $i <= AppConstants::RETRY_MAX_COUNT; $i++)
		{
			// ステージングサイト切り替え
			if (!parent::appcmdStaging($this->id)) {
				$this->log('リトライ修正:ステージングサイト切り替え 失敗'.$i.'回目', LOG_DEBUG);
				sleep(10);	// 10秒待つ
				continue;
			}
				
			// 生きてるパッケージに対し、公開サイトの再構築
			if (!parent::rebuildPublic($this->id)) {
				$this->log('リトライ修正:公開したパッケージの内容をコピー 失敗'.$i.'回目', LOG_DEBUG);
				sleep(10);	// 10秒待つ
				continue;
			}
			$success = true;
			break;
		}
		
		// 上記処理が正常に修正しない場合、失敗
		if (!$success) {
			$this->log('リトライ修正 失敗', LOG_DEBUG);
			return false;
		}

		$this->log('ブログパッケージ公開 成功', LOG_DEBUG);
		return true; // 成功
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