<?php

App::import("Lib", "Utils/mail");
App::import("Lib", "Batch");
App::uses('EMailController', 'Controller');
App::uses('BatchAppController', 'Controller');
App::uses('AppConstants',	'Lib/Constants');

/**
 * バッチ異常検知メール クラス
 * @author hsuzuki
 */
class BatchEMailStopProcessController extends BatchAppController {

	/** メール送信先設定 **/
	var $tos = array("sbt-shutoko-pj@tech.softbank.co.jp");

	/** 実行中を異常とする時間 **/
	const CHECK_TIME = "1:00:00";

	/** 実行待を異常とする時間 **/
	const WAIT_TIME = "1:00:00";

	public $helpers = array("Batch");

	public $uses = array('BatchQueue','ExtensionBatchQueue');
	private $_mailer;

	var $id;
	var $start_at;
	var $end_at;


	/*
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("バッチ異常検知を開始しました。" ), LOG_INFO);

		$result = $this->execute_core();

		$this->log(sprintf("バッチ異常検知を終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	private function execute_core(){

		$this->autoRender = false;
		$list = "";

		/**
		 * メール本文作成
		 */

		// 結果コード:RESULT_CD_FAILURE
		$queues_failure = self::getBatchQueueFailure();
		if (count($queues_failure) > 0){
			$this->log("異常終了であるレコード有:", LOG_INFO);
			$this->log($queues_failure, LOG_INFO);

			$list .= self::createList($queues_failure, '異常終了');
			$list .= "\n";
		}

		// 結果コード:RESULT_CD_EXECUTION
		$queues_execution = self::getBatchQueueExecution();
		if (count($queues_execution) > 0) {
			$this->log("実行中であるレコード有:", LOG_INFO);
			$this->log($queues_failure, LOG_INFO);

			$list .= self::createList($queues_execution, '実行中');
			$list .= "\n";
		}

		// 結果コード:RESULT_CD_NO_EXECUTION
		$queues_no_execution = self::getBatchQueueNoExecution();
		if (count($queues_no_execution) > 0) {
			$this->log("実行待であるレコード有:", LOG_INFO);
			$this->log($queues_failure, LOG_INFO);

			$list .= self::createList($queues_no_execution, '実行待');
			$list .= "\n";
		}

		if($list==""){
			// 異常なし[OK]
			return AppConstants::RESULT_CD_SUCCESS;
		}

		/**
		 * メール送信
		 */
		$now = date('Y/m/d H:i');
		$subject = AppConstants::MAIL_TITLE_HEAD ."バッチ処理エラー検知通知";
		$bodys
				= "以下の起動バッチIDが正常に動作が完了していません。({$now})\n"
				. "\n"
				. "{$list}"
//				. "このメールはシステムより自動配信されています。\n"
//				. "返信は受付できませんので、ご了承ください。\n"
				;

		$ctrl = new EMailController();
		$ctrl->send_core($this->tos,$subject,$bodys);


		/**
		 * 異常メール送信済みフラグセット
		 * タイムアウトメール送信済みフラグセット
		 */
		$queues = array_merge($queues_failure, $queues_execution, $queues_no_execution);
		if (!self::saveExtensionBatchQueue($queues)) {
			return AppConstants::RESULT_CD_FAILURE;
		}

		return AppConstants::RESULT_CD_SUCCESS;
	}

	/**
	 * 異常終了のキューを取得
	 */
	private function getBatchQueueFailure() {
		$sql =
		"SELECT "
			."`BatchQueue`.`result_cd`, `BatchQueue`.`batch_cd`, `BatchQueue`.`id`, `BatchQueue`.`start_at`,BatchQueue.execute_datetime "
		."FROM `batch_queues`  AS `BatchQueue` "
		."LEFT JOIN `extension_batch_queues` AS `ExtensionBatchQueue` "
		."ON `BatchQueue`.`id` = `ExtensionBatchQueue`.`batch_queue_id` and `ExtensionBatchQueue`.is_del = 0 "
		."WHERE `BatchQueue`.`is_del` = '0' "
		."AND `BatchQueue`.`result_cd` = '9' "
		."AND `ExtensionBatchQueue`.`is_send_mail` is NULL "
		."ORDER BY `BatchQueue`.`result_cd` DESC, `BatchQueue`.`id` ASC "
		;

		return $this->BatchQueue->query($sql);
	}

	/**
	 * 実行中のキューを取得
	 */
	private function getBatchQueueExecution() {
		$sql =
		"SELECT "
			."`BatchQueue`.`result_cd`, `BatchQueue`.`batch_cd`, `BatchQueue`.`id`, `BatchQueue`.`start_at`,BatchQueue.execute_datetime "
		."FROM `batch_queues`  AS `BatchQueue` "
		."LEFT JOIN `extension_batch_queues` AS `ExtensionBatchQueue` "
		."ON `BatchQueue`.`id` = `ExtensionBatchQueue`.`batch_queue_id` and `ExtensionBatchQueue`.is_del = 0 "
		."WHERE `BatchQueue`.`is_del` = '0' "
		."AND `BatchQueue`.`result_cd` = '2' "
		."AND `BatchQueue`.`start_at` < addtime(now(),'-". self::CHECK_TIME ."') "
		."AND `ExtensionBatchQueue`.`is_send_mail` is NULL "
		."ORDER BY `BatchQueue`.`result_cd` DESC, `BatchQueue`.`id` ASC "
		;

		return $this->BatchQueue->query($sql);
	}

	/**
	 * 実行待のキューを取得
	 */
	private function getBatchQueueNoExecution() {
		$sql =
		"SELECT "
		."`BatchQueue`.`result_cd`, `BatchQueue`.`batch_cd`, `BatchQueue`.`id`, `BatchQueue`.`start_at`,BatchQueue.execute_datetime "
		."FROM `batch_queues`  AS `BatchQueue` "
		."LEFT JOIN `extension_batch_queues` AS `ExtensionBatchQueue` "
		."ON `BatchQueue`.`id` = `ExtensionBatchQueue`.`batch_queue_id` and `ExtensionBatchQueue`.is_del = 0 "
		."WHERE `BatchQueue`.`is_del` = '0' "
		."AND `BatchQueue`.`result_cd` = '0' "
		."AND `BatchQueue`.`execute_datetime` < addtime(now(),'-". self::WAIT_TIME ."') "
		."AND `ExtensionBatchQueue`.`is_timeout_mail` IS NULL "
		."ORDER BY `BatchQueue`.`result_cd` DESC, `BatchQueue`.`id` ASC "
		;

		return $this->BatchQueue->query($sql);
	}

	/**
	 * エラーリストを生成する
	 * @param unknown $_queues
	 * @param unknown $_title
	 * @return string
	 */
	private function createList($_queues, $_title) {
		$list = "";
		$cnt = count($_queues);
		if ($cnt > 0) {
			$list .= "{$_title}({$cnt}件):\n";
			$list .= "　";
			foreach($_queues as $queue){
				$list .= "{$queue['BatchQueue']['id']}, ";
			}
			$list = substr($list, 0, -2);
			$list .= "\n";
		}
		return $list;
	}

	/**
	 * 拡張バッチキューのデータを追加・更新する。
	 * @param unknown $_queues
	 * @return boolean
	 */
	private function saveExtensionBatchQueue($_queues) {
		$data1 = array();
		foreach($_queues as $key => $queue){
			if (!empty($queue["ExtensionBatchQueue"]["id"])) {
				$data1[$key]["ExtensionBatchQueue"]["id"] = $queue["ExtensionBatchQueue"]["id"];
			}
			$data1[$key]["ExtensionBatchQueue"]["batch_queue_id"] = $queue["BatchQueue"]["id"];
			switch( $queue["BatchQueue"]["result_cd"]){
				case AppConstants::RESULT_CD_NOT_EXECUTION:
					$data1[$key]["ExtensionBatchQueue"]["is_timeout_mail"] = 1;
					break;
				case AppConstants::RESULT_CD_FAILURE:
				case AppConstants::RESULT_CD_EXECUTION:
					$data1[$key]["ExtensionBatchQueue"]["is_send_mail"] = 1;
					break;
			}
		}
		if(count($data1)){
			return $this->ExtensionBatchQueue->saveAll($data1);
		}
		return true;

	}
}