<?php
App::uses('BatchMtController', 'Controller');

/**
 * MTプロジェクト更新
 * @author keiohnishi
 *
 */
class BatchEditProjectController extends BatchMtController {

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
		$this->log(sprintf("MTプロジェクト更新バッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTのDBオブジェクト生成
		$this->EditMtDb = $this->Components->load('EditMtDb');
// 		$this->ApprovalMtDb = $this->Components->load('ApprovalMtDb');

		// トランザクション開始
		if ($this->Transaction->begin() === false
		|| $this->EditMtDb->beginTransaction() === false) {
// 		|| $this->ApprovalMtDb->beginTransaction() === false) {
			$this->log('トランザクション開始に失敗しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}

		//MTブログの作成
		$result = $this->execSaveProject($this->id);

		if($result === AppConstants::RESULT_CD_FAILURE){
			//ロールバック
			if ($this->Components->Transaction->rollback(null) === false
			|| $this->EditMtDb->rollBack() === false) {
// 			|| $this->ApprovalMtDb->rollBack() === false) {
				$this->log('ロールバックに失敗しました。', LOG_ERR);
			}
		} else {
			//コミット
			if($this->Transaction->commit() === false
			|| $this->EditMtDb->commit() === false) {
// 			|| $this->ApprovalMtDb->commit() === false) {
				$this->log('コミットに失敗しました。', LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
		}

		//データベースサーバへの接続の切断
		$this->EditMtDb->close();
// 		$this->ApprovalMtDb->close();

		$this->log(sprintf("MTプロジェクト更新バッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}
}
?>