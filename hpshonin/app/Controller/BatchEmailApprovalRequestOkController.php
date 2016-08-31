<?php

App::import("Lib", "Utils/mail");
App::uses('EMailController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('DateUtil', 'Lib/Utils');

/**
 * 承認結果通知メール・承認完了通知メール(承認) クラス
 */
// class BatchEMailApprovalRequestOkController extends BatchAppController {
Class BatchEMailApprovalRequestOkController extends AppController {

	public $uses = array('Package','ProjectUser','User');
	private $_mailer;

	var $id;

	/*
	 * プロジェクトテーブルのidを設定
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/*
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("承認結果通知メール・承認完了通知メール(承認) バッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTブログの作成
		$result = $this->execute_core($this->id);

		$this->log(sprintf("承認結果通知メール・承認完了通知メール(承認) を終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	// private function execute_core($package_id){
	public function execute_core($package_id){

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
		//$public_due_date = DateUtil::dateFormat($package['Package']['public_due_date'], 'y/m/d H:i');
		//$approval_modified = DateUtil::dateFormat($package['Package']['approval_modified'], 'y/m/d');
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
				$subject = AppConstants::MAIL_TITLE_HEAD ."承認完了通知({$project_name})";
				$bodys
					= "以下のパッケージを承認しました。\n"
					. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
					. "\n"
					. "■ 概要\n"
					. "----------------------------------------------------------------------\n"
					. "　承認・却下日時: {$approval_modified}\n"
					. "承認・却下実施者: {$username}({$contact_address})\n"
					. "　承認・却下結果: 承認\n"
					. "\n"
					. "　プロジェクト名: {$project_name}\n"
					. "　　パッケージ名: {$package_name}\n"
					. "　　　公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "■ コメント\n"
					. "----------------------------------------------------------------------\n"
					. "{$approval_note}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
					;

				$ctrl->send_core($tos,$subject,$bodys);
		}

		$ctrl = new EMailController();

		// 承認結果通知メール送信
		$optioon3 = array(
				'conditions' => array(
						'ProjectUser.is_del' => 0,
						'ProjectUser.project_id' =>  $project_id,
						'User.is_del' => 0,
						'User.roll_cd' => array(1,2)
				),
				'recursive' => 1
		);
		$project_users3 = $this->ProjectUser->find('all',$optioon3);
		$tos3 = array();
		foreach($project_users3 as $project_user){
			$tos3[] = $project_user['User']['email'];
		}
		if(count($tos3)){
				$subject = AppConstants::MAIL_TITLE_HEAD ."承認結果通知({$project_name})";
				$bodys
					= "以下のパッケージが承認されました。\n"
					. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
					. "\n"
					. "■ 概要\n"
					. "----------------------------------------------------------------------\n"
					. "承認・却下日時: {$approval_modified}\n"
					. "承認・却下結果: 承認\n"
					. "\n"
					. "プロジェクト名: {$project_name}\n"
					. "　パッケージ名: {$package_name}\n"
					. "　　公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "■ コメント\n"
					. "----------------------------------------------------------------------\n"
					. "{$approval_note}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
					;

				$ctrl->send_core($tos3,$subject,$bodys);
		}
		return AppConstants::RESULT_CD_SUCCESS;
	}
}