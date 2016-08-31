<?php
App::uses('BatchAppController', 'Controller');
// App::import("Controller", "BatchEMailDeleteUser");

/**
 * MTユーザー削除
 * @author keiohnishi
 *
 */
class BatchDeleteUserController extends BatchAppController {
	var $uses = array('User');

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
		$result = AppConstants::RESULT_CD_SUCCESS;
		$this->log(sprintf("MTユーザー削除バッチを開始しました。(%d)", $this->id), LOG_INFO);

		//MTのDBオブジェクト生成
		$this->EditMtDb = $this->Components->load('EditMtDb');

		// トランザクション開始
		if ($this->Transaction->begin() === false
		|| $this->EditMtDb->beginTransaction() === false) {
			$this->log('トランザクション開始に失敗しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}

		//MTユーザーの削除
		$result = $this->execDeleteUser($this->id);

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

		$this->log(sprintf("MTユーザー削除バッチを終了しました。(%d)", $result), LOG_INFO);
		return $result;
	}

	/*
	 * MTユーザーの削除
	*/
	public function execDeleteUser($user_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;
		$this->log(sprintf("ユーザー(%d)削除を開始しました。", $user_id), LOG_INFO);

		try{
			//ユーザーテーブルの読み込み(削除フラグON)
			$user = $this->User->getUser($user_id, AppConstants::FLAG_ON);
			if(!$user){
				//ユーザーが存在しないならエラー終了
				$this->log(sprintf("存在しないユーザー(%d)が指定されました。または削除指定がありません。", $user_id), LOG_ERR);
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}

			//ユーザーとmt_authorの関連が正しいことを確認する
			if ($user["User"]["mt_author_id"]) {
				//ユーザーに関連したmt_authorが存在しない場合、関連を削除する
				$rs = $this->EditMtDb->getAuthor($user["User"]["mt_author_id"]);
				$rowCount = $rs->rowCount();

				if (!$rowCount) {
					$this->log("Userとmt_authorの関連が不正です。関連をクリアしました。", LOG_WARNING);
					$user["User"]["mt_author_id"] = null;
					$user["User"]["modified"] = null;
				}
			}

			//mt_authorをUPDATE（無効にする）
			if ($user["User"]["mt_author_id"]) {
				$result = $this->EditMtDb->deleteAuthor($user["User"]["mt_author_id"]);
				if($result === AppConstants::RESULT_CD_FAILURE){
					//エラー終了
					$this->log(sprintf("MTユーザーの削除に失敗しました。(%s)", $user["User"]["email"]), LOG_ERR);
					return $result;
				}
			}

			//Userテーブルにmt_authorのidを格納する（Userは別トランザクションなので最後に行っている）
			if (!$this->User->save($user)) {
				// 更新に失敗したのでエラー終了
				$this->log(sprintf("ユーザーとMTユーザーの関連更新に失敗しました。(%s)", $user["User"]["email"]), LOG_ERR);
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}catch (Exception $e){
			$this->log(sprintf("ユーザーの削除中に予期せぬエラーが発生しました。(%s)", $e), LOG_ERR);
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		$this->log(sprintf("ユーザー(%d)削除が正常終了しました。", $user_id), LOG_INFO);
		return $result;
	}
}
?>