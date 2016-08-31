<?php
App::uses('BatchPackageController', 'Controller');

/**
 * 公開パッケージ承認
 * @author smurata
 *
 */
class BatchApprovalPackagePublishController extends BatchPackageController {
	
	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = parent::APPROVAL;
		// バッチ名を設定
		$this->batch_name = '公開パッケージ承認バッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_APPROVAL_OK; // '03'(承認済み)
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_APPROVAL_REQUEST; // '02'(承認依頼)
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {
		// 公開フォルダにファイルをコピーする
		return parent::copyPublicPackagePublic($this->id);
	}

	/**
	 * 成功時後実行
	 */
	function execute_after_success() {
		// 承認完了メールを送信
		return $this->sendMailApprovalRequestOk();
	}

	/**
	 * 失敗時後実行
	 */
	function execute_after_failure() {
		// 公開事前失敗メールを送信
		return $this->sendMailPreOpenNg();
	}
}