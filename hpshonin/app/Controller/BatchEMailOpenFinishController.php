<?php

App::import("Lib", "Utils/mail");
App::uses('EMailController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('DateUtil', 'Lib/Utils');

// 公開完了メール クラス
class BatchEMailOpenFinishController extends BatchAppController {

	public $uses = array('Package','ProjectUser','User');
	private $_mailer;

	var $id;
	var $start_at;
	var $end_at;

	/**
	 */
	function __construct() {
		$this->start_at = date('Y-m-d H:i:s');
		$this->end_at = date('Y-m-d H:i:s');
	}

	/*
	 *
	*/
	public function setId($id) {
		$this->id = $id;
	}
	public function setStartAt($at) {
		$this->start_at = $at;
	}
	public function setEndAt($at) {
		$this->end_at = $at;
	}

	/*
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("公開完了メールバッチを開始しました。(%d)", $this->id), LOG_INFO);

		$result = $this->execute_core($this->id);

		$this->log(sprintf("公開完了メールバッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	private function execute_core($package_id){

		$ctrl = new EMailController();

		// 指定パッケージ取得
		$optioon = array(
				'conditions' => array(
						'Package.id' => $package_id
				),
				'recursive' => 1
		);
		$package = $this->Package->find('first', $optioon);
		if(count($package)==0){
			// 該当パッケージなし
			return AppConstants::RESULT_CD_FAILURE;
		}

		$project_id = $package['Project']['id'];
		$project_name = $package['Project']['project_name'];
		$package_id = $package['Package']['id'];
		$package_name = $package['Package']['package_name'];
		$camment = $package['Package']['camment'];
		//$start_at = DateUtil::dateFormat($this->start_at, 'y/m/d H:i');
		//$end_at = DateUtil::dateFormat($this->end_at, 'y/m/d');
		$date = date_create($this->start_at);
		$start_at = date_format($date,  'Y/m/d H:i:s');
		$date = date_create($this->end_at);
		$end_at = date_format($date, 'Y/m/d H:i:s');
		$date = date_create($package['Package']['public_due_date']);
		$public_due_date = date_format($date, 'Y/m/d');

		$username = $package['User']['username'];
		$contact_address = $package['User']['contact_address'];

		$optioon2 = array(
				'conditions' => array(
						'User.is_del' => 0,
						'User.roll_cd' => array(0,3)
				),
				'recursive' => 0
		);
		$project_users = $this->User->find('all',$optioon2);
		$tos = array();
		foreach($project_users as $project_user){
			$tos[] = $project_user['User']['email'];
		}
		$optioon2 = array(
				'conditions' => array(
						'ProjectUser.is_del' => 0,
						'ProjectUser.project_id' =>  $project_id,
						'User.is_del' => 0,
						'User.roll_cd' => array(1,2)
				),
				'recursive' => 1
		);
		$project_users = $this->ProjectUser->find('all',$optioon2);
		foreach($project_users as $project_user){
			$tos[] = $project_user['User']['email'];
		}

		if(count($tos)){
			$subject = AppConstants::MAIL_TITLE_HEAD ."公開完了通知({$project_name})";
			$bodys
					= "以下のパッケージの公開が完了しました。\n"
					. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
					. "\n"
					. "■ 概要\n"
					. "----------------------------------------------------------------------\n"
					. "　作業開始日時: {$start_at}\n"
					. "　　　公開日時: {$end_at}\n"
					. "\n"
					. "プロジェクト名: {$project_name}\n"
					. "　パッケージ名: {$package_name}\n"
					. "当初公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
							;
			$ctrl->send_core($tos,$subject,$bodys);
		}

		return AppConstants::RESULT_CD_SUCCESS;
	}
}