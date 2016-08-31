<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');
App::uses('guidChkUtil', 'Lib/Utils');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

	public $uses = array('Authentication');
		public $components = array(
//			'Auth',
			'Auth' => array(
					'authenticate' => array(
							'Form' => array(
									'userModel' => 'User',
									'fields' => array('username' => 'email')
							)
					)
			),
			'Session',
//			'DebugKit.Toolbar'	// DebugKitを追加
	);

	/**
	 * (non-PHPdoc)
	 * @see Controller::beforeFilter()
	 */
	function beforeFilter(){
		
		$this->set('userAuth', $this->Auth->user());
		$this->__loggingAction("start");

		
		// GUID更新
		$this->manageGuid();
	}
	

	/**
	 * アクセス管理用GUID発行処理
	 * 
	 * @param なし
	 * @return true:正常/false:異常
	 * @auther hsuzuki
	 */
	private function manageGuid(){
		
		// ログインチェック
		if($this->Auth->loggedIn() == false){
			// ログインされていない→対象外
			return true;
		}
		
		while(True){
			
			//////////////////////////////////////////////
			// クッキーチェック
			//////////////////////////////////////////////
			if(isset($_COOKIE['hpshonin-id']) == false){
				// クッキーがセットされていない→対象
				break;
			}
			
			//////////////////////////////////////////////
			// ＤＢチェック
			//////////////////////////////////////////////
			$optioon = array(
					'conditions' => array(
							// 'user_id'=>$this->Auth->user('id'),
							'cookie_id' => $_COOKIE['hpshonin-id']
					),
					'recursive' => -1
			);
			$authentication = $this->Authentication->find("first",$optioon);
			if(count($authentication) == 0){
				// ＤＢがセットされていない→対象
				unset($authentication);
				break;
			}			
			
			//////////////////////////////////////////////
			// クッキー・ＤＢ比較チェック
			//////////////////////////////////////////////
			if( $authentication["Authentication"]["user_id"] !== $this->Auth->user('id')){
				// クッキーとＤＢが一致していない→対象
				unset($authentication);
				break;
			}
			unset($authentication);
			
			//////////////////////////////////////////////
			// GUID  アクセス日時更新
			//////////////////////////////////////////////
			$conditions = array('cookie_id' => $_COOKIE['hpshonin-id']);
			$authentication["modified"] = "'". date('Y-m-d H:i:s') . "'";
			
			return $this->Authentication->updateAll($authentication,$conditions);
		}
		
		
		//////////////////////////////////////////////
		// GUID発行
		//////////////////////////////////////////////
		$guid = guidChkUtil::getGUID();
		setcookie("hpshonin-id", $guid ,0,AppConstants::GUID_COOKIE_DIR);
		$authentication["user_id"] = $this->Auth->user('id');
		$authentication["cookie_id"] = $guid ;
		$authentication["modified"] = date('Y-m-d H:i:s');
		
		return $this->Authentication->save($authentication);
	}

	function afterFilter(){
		$this->__loggingAction("end");
	}

	private function __loggingAction( $point ){
		$this->log( "[".$this->name.".".$this->action."]".$point, date('Ymd') . "_controller");
	}
}
