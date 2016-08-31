<?php

App::uses('CakeEmail', 'Network/Email');
App::uses('BatchAppController', 'Controller');

class SampleMailController extends BatchAppController {

	/**
	 * 承認依頼通知
	 */
	public function execute() {
		$email = new CakeEmail();
		
		$project = $this->ProjectUser->find('all', $optioon);
		
		
		
		

		$project = '東京Smooth';
		$from = 'smurata@tech.softbank.co.jp';
		$to = array('shigeru@murata-ss.net', 'test@test.jp');

		$email->transport('Debug');

		$email->from($from);
		$email->to($to);

		$email->subject("[HP承認]承認依頼通知({$project})");

		$message = $email->send('これはテストメールの本文です');

		$this->set('message', $message);
	}
}