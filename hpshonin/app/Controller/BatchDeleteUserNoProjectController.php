<?php
App::uses('BatchAppController', 'Controller');

/**
 * 無所属ユーザー削除クラス
 */
class BatchDeleteUserNoProjectController extends BatchAppController {
	public $uses = array('User');

	private $_userId;

	public function setId($id) {
		$this->_userId = $id;
	}

	/**
	 * 処理実行
	 * @return string 結果コード
	 */
	public function execute() {
		$this->log("無所属ユーザー削除の処理を開始します(user_id:$this->_userId)。", LOG_INFO);

		if (!isset($this->_userId)) {
			$this->log("無所属ユーザー削除の処理を中止します。理由：user_idが空", LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}
		// モデル側にメソッドを置く予定
		$this->User->id = $this->_userId;
		$this->User->saveField('is_del', AppConstants::FLAG_ON);


		// 削除処理
		// MTの仕様が「無所属のユーザーはログインできない」ので、
		// 特に処理はしないと判断。

		// メール送信
		// TODO:メール送信

		$this->log("無所属ユーザー削除の処理が完了しました(user_id:$this->_userId)。", LOG_INFO);
		return AppConstants::RESULT_CD_SUCCESS;
	}
}
?>