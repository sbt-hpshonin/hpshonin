<?php
App::uses('AppModel', 'Model');

/**
 * MovableType Entryモデルクラス
 *
 * @author hsuzuki
 *
 */
class MtEntry extends AppModel {
	public $name = 'MtEntry';

	var $useDbConfig = 'mt_edit_db'; // DB設定
	var $useTable = 'mt_entry';

	/**
	 * DBをapprovalに切り替える
	 * @author hsuzuki
	 */
//	public function useApproval(){
//		$this->setDataSource('mt_approval_db');//DBを元に戻す
//	}

	/**
	 * DBをeditに切り替える
	 * @author hsuzuki
	 */
	public function useEdit(){
		$this->setDataSource('mt_edit_db');//DB変更
	}
}

?>