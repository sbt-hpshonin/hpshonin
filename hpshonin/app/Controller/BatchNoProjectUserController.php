<?php
App::uses("BatchAppController", "Controller");
App::uses("EMailController", "Controller");

/**
 * 無所属ユーザー削除コントローラ
 * @author smurata
 *
 */
class BatchNoProjectUserController extends BatchAppController {

	/**
	 * 無所属ユーザーの削除猶予期間
	 * @var unknown
	 */
	const TERM_DELETE_NO_PROJECT_USER = 7;
//	const TERM_DELETE_NO_PROJECT_USER = 1;

	/**
	 * 使用するモデル
	 * @var unknown
	 */
	public $uses = array('User', 'BatchQueue');

	/**
	 * 処理実行
	 */
	public function execute() {
		$this->log("無所属ユーザー削除の処理を開始しました。", LOG_INFO);

		// 削除対象：
		//	制作会社以下である。
		//	プロジェクト-ユーザーに何もない、
		//	紐付け先プロジェクトが削除されている
		$sql = "select * from users User ".
			"left join project_user pu on User.id = pu.user_id ".
			"left join projects p on p.id = pu.project_id and p.is_del = '0' ".
			"where User.is_del = '0' and User.roll_cd in (1, 2) and p.id is null ".
			"and TO_DAYS(NOW()) - TO_DAYS(User.modified) >= ".self::TERM_DELETE_NO_PROJECT_USER;

		$users = $this->User->query($sql);

		// 全てのユーザーを削除し、メールを送信する。
		foreach ($users as $user) {
			$this->User->begin();
			try {
				$this->log("無所属ユーザー(ID:{$user['User']['id']}、更新日時:{$user['User']['modified']})を削除します。", LOG_INFO);
				$this->User->deleteUser($user['User']['id']);
				$this->log("無所属ユーザーを削除しました。", LOG_INFO);

				// 削除処理
				// MTの仕様が「無所属のユーザーはログインできない」ので、
				// 特に処理はしないと判断。

				// メールの送信
				$controller = new EMailController();
				$controller->send_delete_user(array($user['User']['email']));
				$this->log("無所属ユーザー削除メールを送信しました。", LOG_INFO);

				$this->User->commit();
			}
			catch (Exception $ex) {
				$this->User->rollback();
				$this->log("無所属ユーザー削除処理に失敗しました:{$ex->getMessage()}", LOG_ERR);
			}
		}
		$this->log("無所属ユーザー削除の処理が完了しました。", LOG_INFO);
	}

}