<?php

App::uses('AppModel', 'Model');

/**
 * DBトランザクション制御コンポーネント
 *
 * @author nonaka
 */
class TransactionComponent extends Component {

	public $model = null;

	/**
	 * トランザクション開始
	 */
	public function begin() {
		$this->model = new AppModel();
		$this->model->begin();
	}

	/**
	 * コミット処理
	 */
	public function commit() {
		$this->model->commit();
	}

	/**
	 * ロールバック処理
	 */
	public function rollback($errorMessage) {
		$this->model->rollback();
	}

}
