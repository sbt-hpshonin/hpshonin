<?php
App::uses('BatchAppController', 'Controller');

/**
 * ダミー
 * @author smurata
 *
*/
class BatchDummyController extends BatchAppController {

	var $_id = 0;

	/**
	 * コンストラクタ
	 * @param unknown $id
	 */
	public function __construct($id = 0) {
		$this->_id = $id;
	}

	/**
	 * 実行
	 */
	public function execute() {
		print_r('BatchDummyController:'.$this->_id);
		sleep(10);
		return AppConstants::RESULT_CD_SUCCESS;
	}
}
?>