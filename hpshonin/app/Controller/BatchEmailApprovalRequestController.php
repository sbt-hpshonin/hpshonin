<?php

App::import("Lib", "Utils/mail");
App::uses('EMailController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('DateUtil', 'Lib/Utils');

// 承認依頼メール・承認依頼完了メール クラス
class BatchEMailApprovalRequestController extends BatchAppController {

	public $uses = array('Package','ProjectUser','User');
	private $_mailer;

	var $id;

	/**
	 * プロジェクトテーブルのidを設定
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("承認依頼・承認依頼完了メールバッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTブログの作成
		$result = $this->execute_core($this->id);

		$this->log(sprintf("承認依頼・承認依頼完了メールを終了しました。(%d)", $result), LOG_INFO);
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
		// $camment = $package['Package']['camment'];
		$request_note  = $package['Package']['request_note'];
		// $public_due_date = DateUtil::dateFormat($package['Package']['public_due_date'], 'y/m/d H:i');
		// $request_modified = DateUtil::dateFormat($package['Package']['request_modified'], 'y/m/d');
		$date = date_create($package['Package']['public_due_date']);
		$public_due_date = date_format($date,  'Y/m/d');
		$date = date_create($package['Package']['request_modified']);
		$request_modified = date_format($date, 'Y/m/d H:i:s');

		$username = $package['RequestUser']['username'];
		$contact_address = $package['RequestUser']['contact_address'];


		// 承認依頼メール送信
		$optioon2 = array(
				'fields' => 'User.email',
				'conditions' => array(
						'User.is_del' => 0,
						'User.roll_cd' => array(0,3)
				),
				'recursive' => -1
		);
		$project_users = $this->User->find('all',$optioon2);
		$tos = array();
		foreach($project_users as $project_user){
			$tos[] = $project_user['User']['email'];
		}

		if(count($tos)){
			$subject = AppConstants::MAIL_TITLE_HEAD . "承認依頼通知({$project_name})";
			$bodys
			= "以下のパッケージの承認依頼が来ました。\n"
					. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
					. "\n"
					. "■ 概要\n"
					. "----------------------------------------------------------------------\n"
					. "　　　依頼日時: {$request_modified}\n"
					. "　　　　依頼者: {$username}({$contact_address})\n"
					. "\n"
					. "プロジェクト名: {$project_name}\n"
					. "　パッケージ名: {$package_name}\n"
					. "　　公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "■ コメント\n"
					. "----------------------------------------------------------------------\n"
					// . "{$camment}\n"
					. "{$request_note}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
							;
							$ctrl->send_core($tos,$subject,$bodys);
		}

		$ctrl = new EMailController();

		// 承認依頼完了メール送信
		$optioon3 = array(
				'fields' => 'User.email',
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
				$subject = AppConstants::MAIL_TITLE_HEAD ."承認依頼完了通知({$project_name})";
				$bodys
				= "以下のパッケージの承認依頼を出しました。\n"
				. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
				. "\n"
				. "■ 概要\n"
				. "----------------------------------------------------------------------\n"
					. "　　　依頼日時: {$request_modified}\n"
					. "　　　　依頼者: {$username}({$contact_address})\n"
					. "\n"
					. "プロジェクト名: {$project_name}\n"
					. "　パッケージ名: {$package_name}\n"
					. "　　公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "■ コメント\n"
					. "----------------------------------------------------------------------\n"
					// . "{$camment}\n"
					. "{$request_note}\n"
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