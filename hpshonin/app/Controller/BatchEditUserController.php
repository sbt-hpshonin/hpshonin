<?php
App::uses('BatchMtController', 'Controller');

/**
 * MTユーザー更新
 * @author keiohnishi
 *
 */
class BatchEditUserController extends BatchMtController {

	var $id;

	/*
	 * ユーザーテーブルのidを設定
	*/
	public function setId($id) {
		$this->id = $id;
	}

	/*
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("MTユーザー更新バッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTのDBオブジェクト生成
		$this->EditMtDb = $this->Components->load('EditMtDb');

		// トランザクション開始
		if ($this->Transaction->begin() === false
		|| $this->EditMtDb->beginTransaction() === false) {
			$this->log('トランザクション開始に失敗しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}

		//MTユーザーの格納
		$result = $this->execSaveUser($this->id);

		if($result === AppConstants::RESULT_CD_FAILURE){
			//ロールバック
			if ($this->Components->Transaction->rollback(null) === false
			|| $this->EditMtDb->rollBack() === false) {
				$this->log('ロールバックに失敗しました。', LOG_ERR);
			}
		} else {
			//コミット
			if($this->Transaction->commit() === false
			|| $this->EditMtDb->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
		}

		//データベースサーバへの接続の切断
		$this->EditMtDb->close();

		$this->log(sprintf("MTユーザー更新バッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}
}
?>