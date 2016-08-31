<?php

App::import("Lib", "Utils/mail");
App::uses('AppController', 'Controller');
App::uses('DateUtil', 'Lib/Utils');

class EMailController {
	
	public $uses = array('Package','ProjectUser');
	private $_mailer;
	
	public function __construct() {
		$this->_mailer = new PHPMailer();
		$this->_mailer->CharSet = 'UTF-8';
		$this->_mailer->Encoding = 'quoted-printable';
		$this->_mailer->IsSMTP();
		$this->_mailer->Host = 'ssl://smtp.gmail.com:465';
		$this->_mailer->SMTPAuth = true;
		$this->_mailer->Username = 'hp-shonin@shutoko.jp';  // Gmailのアカウント名
		$this->_mailer->Password = 'GAuhp500';  // Gmailのパスワード
		$this->_mailer->From     = 'hp-shonin@shutoko.jp';  // Fromのメールアドレス
		$this->_mailer->FromName = '';
		
		// $this->_mailer->SMTPDebug = 1; // デバッグ用
	}


	/**
	 * メール送信関数
	 * @param $tos メール送信先配列
	 * @param $subject メールタイトル
	 * @param $bodys メール本文
	 */
	public function send_core($tos,$subject,$bodys) {
		foreach($tos as $to) {
			$this->_mailer->AddAddress($to); // 宛先
		}
		
		$this->_mailer->Subject  = $subject;
		$this->_mailer->Body = $bodys;

		//print "<pre>";
		//print $subject . "\n";
		//print $bodys;
		//exit;

		$ret_val = $this->_mailer->Send();
		if(!$ret_val) {
			// echo "Message was not sent";
			// echo "Mailer Error: " . $this->_mailer->ErrorInfo;
		} else {
			// echo "Message has been sent";
		}
		return $ret_val;
	}
	
	/**
	 * ユーザー削除通知メール送信
	 * 
	 * @param $tos メール送信先配列
	 * @return true:送信成功/false:送信失敗
	 * @author hsuzuki
	 */
	public function send_delete_user($tos){
	// 
		$subject = AppConstants::MAIL_TITLE_HEAD . "ユーザー削除通知";
		$bodys
			= "ホームページ承認システムのユーザーから削除されました。\n"
			. "\n"
			. "このメールはシステムより自動配信されています。\n"
			. "返信は受付できませんので、ご了承ください。\n"
			;
		
		return $this->send_core($tos,$subject,$bodys);
	}
	
	/**
	 * ユーザー追加通知メール送信
	 * 
	 * @param $tos メール送信先配列
	 * @param $to  登録者メールアドレス
	 * @param $pass 登録者パスワード
	 * @return true:送信成功/false:送信失敗
	 * @author hsuzuki
	 */
	public function send_add_user($tos,$to,$pass){
		$subject = AppConstants::MAIL_TITLE_HEAD ."ユーザー登録通知";
		$bodys
			= "ホームページ承認システムにユーザー登録されました。\n"
			. "以下のアドレスからシステムにログインできます。\n"
			. AppConstants::MAIL_HOME_URL . "\n"
			. ""
			. "メールアドレス: {$to} \n"
			. "パスワード: {$pass} \n"
			. "\n"
			. "このメールはシステムより自動配信されています。\n"
			. "返信は受付できませんので、ご了承ください。\n"
			;
		return $this->send_core($tos,$subject,$bodys);
	}
	
	
	
	
	
	
	
	public function approvalRequest($id=0){
		
		// 指定パッケージ取得
		$optioon = array(
				'conditions' => array(
						'Package.id' => $id  
				),
				'recursive' => 1
		);
		$package = $this->Package->find('first', $optioon);
		if(count($package)){
			// 該当パッケージなし
			return AppConstants::RESULT_CD_FAILURE;
		}
		
		$project_id = $package['Project']['id'];
		$project_name = $package['Project']['project_name'];
		$package_id = $package['Package']['id'];
		$package_name = $package['Package']['package_name'];
		$camment = $package['Package']['camment'];
		$public_due_date = DateUtil::dateFormat($package['Package']['public_due_date'], 'y/m/d H:i');
		$request_modified = DateUtil::dateFormat($package['Package']['request_modified'], 'y/m/d');
		$username = $package['User']['username'];
		$contact_address = $package['User']['contact_address'];
			
			
		// 承認依頼メール送信
		$optioon2 = array(
				'conditions' => array(
						'ProjectUser.is_del' => 0,
						'ProjectUser.project_id' =>  $project_id,
						'User.is_del' => 0,
						'User.roll_cd' => array(0,3)
				),
				'recursive' => 1
		);
		$project_users = $this->ProjectUser->find('all',$optioon2);
		$tos = array();
		foreach($project_users as $project_user){
			$tos[] = $project_user['User']['email'];
		}
		
		if(count($tos)){
			$subject = "[HP承認]承認依頼通知({$project_name})";
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
							. "{$camment}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
							;
							$ctrl->send_core($tos,$subject,$bodys);
		}
			
		// 承認依頼完了メール送信
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
				$subject = "[HP承認]承認依頼完了通知({$project_name})";
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
					. "{$camment}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
							;
				$ctrl->send_core($tos3,$subject,$bodys);
		}
		return AppConstants::RESULT_CD_SUCCESS;
	}
	
	
	
	
	public function send($package_id) {
	
		$subject = '[HP承認]承認依頼完了通知(東京Smooth)';

		$package = $this->Package->findById($package_id);
//debug($package);
		$url = 'http://xxx.tekito.jp/xxxxx-xxxxx-xxx-xx';
		$executeDate = $package['Package']['request_modified'];		//
		$userName = $package['User']['username'];		//
		$projectName = $package['Project']['project_name'];
		$packageName = $package['Package']['package_name'];
		$publishDate = $package['Package']['public_reservation_datetime'];
		$comment = $package['Package']['request_note'];
		$tos = array("shutoko@galab.intraservice.jp", "smurata@tech.softbank.co.jp");

		foreach($tos as $to) {
			$this->_mailer->AddAddress($to); // 宛先
		}
		$this->_mailer->Subject  = $subject;

		$bodys     = <<<EOT
以下のパッケージの承認を依頼しました。
$url

■ 概要
----------------------------------------------------------------------
　　　　実施日時: $executeDate
　　　　　担当者: $userName

　プロジェクト名: $projectName
　　パッケージ名: $packageName
　　　公開予定日: $publishDate
----------------------------------------------------------------------

■ コメント
----------------------------------------------------------------------
$comment
----------------------------------------------------------------------

このメールはシステムより自動配信されています。
返信は受付できませんので、ご了承ください。
EOT;

//		$mailer->Body = sprintf($bodys, $url, $executeDate, $userName,
//				 $projectName, $packageName, $publishDate, $comment);
		$this->_mailer->Body = $bodys;
//debug($mailer->Body);

	//	$mailer->AddAddress('shutoko@galab.intraservice.jp'); // 宛先
		  
		if(!$this->_mailer->Send()) {
		   echo "Message was not sent";
		   echo "Mailer Error: " . $mailer->ErrorInfo;
		} else {
		   echo "Message has been sent";
		}
	}



	/**
	 * 承認依頼通知
	 */
	public function send2() {
		// テスト
		$_package_id = 1;

		$package = $this->Package->findById($_package_id);
//debug($package);
		$url = "http://xxx.tekito.jp/xxxxx-xxxxx-xxx-xx";
//		$executeDate = $package['Package']['request_modified'];		//
		$executeDate ="";		//
		$userName = $package['User']['username'];		//
//		$projectName = $package['Project']['proejct_name'];
		$projectName = "プロジェクトA";
		$packageName = $package['Package']['package_name'];
		$publishDate = $package['Package']['public_reservation_datetime'];
		$comment = $package['Package']['request_note'];
		$subject = '[HP承認]承認依頼完了通知(東京Smooth)';

		$tos = array('piyo.murata-ss.net@i.softbank.jp', 'smurata@tech.softbank.co.jp');

		$from = 'shutoko@galab.intraservice.jp';
		
		$mailer = new PHPMailer();
		$mailer->CharSet = 'UTF-8';
		$mailer->Encoding = 'quoted-printable';
		$mailer->IsSMTP();
		$mailer->Host = 'ssl://smtp.gmail.com:465';
		$mailer->SMTPAuth = true;
		$mailer->Username = 'shutoko@galab.intraservice.jp';  // Gmailのアカウント名
		$mailer->Password = 'dOqEwj2w';  // Gmailのパスワード
		$mailer->From     = $from;  // Fromのメールアドレス
		$mailer->FromName = '';
		//$mailer->AddAddress('shutoko@galab.intraservice.jp'); // 宛先
		foreach($tos as $to) {
			$mailer->AddAddress($to); // 宛先
		}
		$mailer->Subject  = $subject;


		$bodys     = <<<'EOT'
以下のパッケージの承認を依頼しました。
%s

■ 概要
----------------------------------------------------------------------
　　　　実施日時: %s
　　　　　担当者: %s

　プロジェクト名: %s
　　パッケージ名: %s
　　　公開予定日: %s
----------------------------------------------------------------------

■ コメント
----------------------------------------------------------------------
%s
----------------------------------------------------------------------

このメールはシステムより自動配信されています。
返信は受付できませんので、ご了承ください。
EOT;

		$mailer->Body = sprintf($bodys, $url, $executeDate, $userName,
				 $projectName, $packageName, $publishDate, $comment);

debug('成功3');
		if($mailer->Send()) {
debug('success4');
		} else {
debug('failure4'. $mailer->ErrorInfo);
		}
/*		$email->transport('Debug');

		$email->from($from);
		$email->to($to);

		$email->subject("[HP承認]承認依頼通知({$project})");
		$email->template('request');
		$email->emailFormat('text');

		$email->viewVars(array(
				'url' => $url,
				'package' => $package['Package'],
				'project' => $package['Project'],
				'user' => $package['User']
		));

		$message = $email->send('これはテストメールの本文です');
		$this->set('message', $message);
*/
		}
}