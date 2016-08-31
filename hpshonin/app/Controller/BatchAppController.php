<?php
App::uses('Controller', 'Controller');
App::uses('TransactionComponent', 'Component');

App::import("Lib", "Batch");

// バッチ用の設定
CakeLog::config('batch', array(
	'engine' => 'FileLog',
//	'types' => array('notice', 'info', 'debug', 'error'),
	'file' => "stk_batch_".date('Ymd')
));

/**
 * バッチ用コントローラー
 * @author keiohnishi
 *
 */
class BatchAppController extends Controller {

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		$this->Transaction = $this->Components->load('Transaction');
	}

	/**
	 * バッチ共通のコンポーネントを登録
	 */
// 	public $components = array(
// 	);

	/**
	 * (non-PHPdoc)
	 * @see Object::log()
	 * 以下を$typeで指定
	 * 　LOG_DEBUG:デバッグ(値の確認など、自由なメッセージ)
	 * 　LOG_INFO:情報(成功時のメッセージなど)
	 * 　LOG_WARNING:警告(運用上のエラー)
	 * 　LOG_ERR:エラー(プログラム上のエラー)
	 * 　LOG_EMERG:致命的エラー(システム上のエラー)
	 *
	 * 現状はメッセージの加工は行っていません。
	 * 設定でどんな$typeでも"stk_batch_yyyymmdd.log"に出力します。
	 */
	public function log($msg, $type = LOG_ERR) {
		// 何か文字列突っ込む

		parent::log($msg, $type);
	}
}
