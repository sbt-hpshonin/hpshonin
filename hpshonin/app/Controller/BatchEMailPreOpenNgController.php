<?php

App::import("Lib", "Utils/mail");
App::uses('EMailController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('DateUtil', 'Lib/Utils');

// 公開事前準備エラーメール クラス
class BatchEMailPreOpenNgController extends BatchAppController {

	public $uses = array('Package','ProjectUser','User');
	private $_mailer;

	var $id;

	/**
	 */
	function __construct() {
	}

	/*
	 *
	*/
	public function setId($id) {
		$this->id = $id;
	}
	/*
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("公開事前準備エラーメールバッチを開始しました。(%d)", $this->id), LOG_INFO);

		$result = $this->execute_core($this->id);

		$this->log(sprintf("公開事前準備エラーメールバッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	private function execute_core($package_id){

		$ctrl = new EMailController();

		// 指定パッケージ取得
		$optioon = array(
				'fields'=>array('Package.*','Project.*','Approval.*'),
				'conditions' => array(
						'Package.id' => $package_id
				),
				'recursive' => 1,
				'joins' => array(array(
						'table'=>'users',
						'alias'=>'Approval',
						'type' => 'LEFT',
						'conditions' => 'Package.approval_user_id = Approval.id'
				)),
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
		$approval_note = $package['Package']['approval_note'];
		$date = date_create($package['Package']['public_due_date']);
		$public_due_date = date_format($date,  'Y/m/d');
		$date = date_create($package['Package']['approval_modified']);
		$approval_modified = date_format($date, 'Y/m/d H:i:s');

		$username = $package['Approval']['username'];
		$contact_address = $package['Approval']['contact_address'];


		$ctrl = new EMailController();

		// 承認依頼メール送信
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

		if(count($tos)){
			$subject = AppConstants::MAIL_TITLE_HEAD ."承認エラー通知({$project_name})";
			$bodys
				= "以下のパッケージの承認、または、却下でエラーが発生しました。\n"
				. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
				. "\n"
				. "■ 概要\n"
				. "----------------------------------------------------------------------\n"
				. "　承認・却下日時: {$approval_modified}\n"
				. "承認・却下実施者: {$username}({$contact_address})\n"
				. "\n"
				. "　プロジェクト名: {$project_name}\n"
				. "　　パッケージ名: {$package_name}\n"
				. "　　　公開予定日: {$public_due_date}\n"
				. "----------------------------------------------------------------------\n"
				. "\n"
				. "一時的な障害により承認・却下処理が失敗した可能性があります。\n"
				. "時間をおいて、再度、承認・却下操作を行ってください。\n"
				. "\n"
				. "このメールはシステムより自動配信されています。\n"
				. "返信は受付できませんので、ご了承ください。\n"
				;

			$ctrl->send_core($tos,$subject,$bodys);
		}
		return AppConstants::RESULT_CD_SUCCESS;
	}
}