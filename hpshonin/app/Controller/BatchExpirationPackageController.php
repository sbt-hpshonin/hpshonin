<?php
App::uses("BatchAppController", "Controller");
App::uses("EMailController", "Controller");

/**
 * 公開期限切れ設定コントロールクラス
 * @author smurata
 *
 */
class BatchExpirationPackageController extends BatchAppController {

	/** 公開予定日から猶予期間 */
	const TERM_DELAY = 15;
//	const TERM_DELAY = 1;

	/**
	 * 使用するモデル
	 * @var unknown
	 */
	var $uses = array('Package', 'ProjectUser', 'User');

	/**
	 * 処理実行
	 */
	public function execute() {
		$this->log("有効期限切れパッケージの処理を開始しました。", LOG_INFO);
		// 更新対象：
		// 以下の条件を満たすパッケージ情報のステータスCDを
		//'95':公開期限切れに設定(条件はすべて"AND")
		//・パッケージ情報.ステータスCD:'06'(公開完了)または'90', '91', '93', '95'ではない
		//・パッケージ情報.公開予定日＋15日 ≦ 現在日
		//・バッチキュー情報にパッケージIDに紐づくレコードが存在した場合、
		//　バッチキュー情報.結果CD:'2'(実行中)

		// 該当パッケージ抽出
		$sql
			= "SELECT distinct "
				."  Package.id,"
				."  Package.project_id "
				."FROM packages Package "
				."WHERE NOT EXISTS("
				."  SELECT * FROM batch_queues bq "
				."  WHERE Package.id = bq.package_id "
				."  AND bq.result_cd = '2' "
				."  AND bq.is_del = '0') "
				."AND adddate(Package.public_due_date, ".self::TERM_DELAY." + 1 ) < now() "
				."AND Package.is_del = '0' "
				."AND Package.status_cd NOT IN ('06', '91', '93', '95')";

		$packages = $this->Package->query($sql);

		// パッケージ更新
		foreach ($packages as $package) {
			$this->User->begin();
			try {
				$queue = array(
						'Package' => array(
								'id' => $package['Package']['id'],
								'project_id' => $package['Package']['project_id'],
								'status_cd'	=> '95',
								'modified_user_id' => 0
								//'execute_datetime'	=> date("Y-m-d H:i:s")
						)
				);
				$this->Package->save($queue);
				$this->log("有効期限切れパッケージ(ID:{$package['Package']['id']})に設定しました。", LOG_INFO);

				$this->mail_send($package['Package']['id']);
				$this->log("有効期限切れパッケージメールを送信しました。", LOG_INFO);
				$this->User->commit();
			}
			catch(Exception $ex) {
				$this->User->rollback();
				$this->log("有効期限切れパッケージ処理に失敗しました:{$ex->getMessage()}", LOG_ERR);
			}
		}
		$this->log("有効期限切れパッケージの処理が完了しました。", LOG_INFO);
	}

	/**
	 * メール送信
	 */
	private function mail_send($package_id = 0) {

		$ctrl = new EMailController();

		// パッケージ取得
		$optioon = array(
				'conditions' => array(
						'Package.id' => $package_id,
				),
				'recursive' => 1
		);
		$package = $this->Package->find('first', $optioon);

		$project_id = $package['Project']['id'];
		$project_name = $package['Project']['project_name'];
		$package_name = $package['Package']['package_name'];
		$camment = $package['Package']['camment'];
		//$public_due_date = DateUtil::dateFormat($package['Package']['public_due_date'], 'y/m/d H:i');
		//$request_modified = DateUtil::dateFormat($package['Package']['request_modified'], 'y/m/d');
		$date = date_create($package['Package']['public_due_date']);
		$public_due_date = date_format($date,  'Y/m/d');
		$date = date_create($package['Package']['request_modified']);
		$request_modified = date_format($date, 'Y/m/d H:i');

		$username = $package['User']['username'];
		$contact_address = $package['User']['contact_address'];


		// メール送信
		$sql = "select * from users User "
				."left join project_user ProjectUser "
						."on ProjectUser.user_id = User.id "
				."where ("
						."ProjectUser.is_del = '0' "
						."and ProjectUser.project_id = ? "
						."and User.is_del = '0'"
				.") or ("
						."User.roll_cd IN (0, 3) "
						."and User.is_del = '0'"
				.")"
		;
		$project_users = $this->User->query($sql, array($project_id));
		$tos = array();
		foreach($project_users as $project_user){
			$tos[] = $project_user['User']['email'];
		}

		if(count($tos)){
				$subject = AppConstants::MAIL_TITLE_HEAD ."失効通知({$project_name})";
				$bodys
					= "以下のパッケージは公開予定日から15日経過しましたので失効しました。\n"
					. AppConstants::MAIL_HOME_URL . "/packages/view/{$package_id}\n"
					. "\n"
					. "■ 概要\n"
					. "----------------------------------------------------------------------\n"
					. "プロジェクト名: {$project_name}\n"
					. "　パッケージ名: {$package_name}\n"
					. "　　公開予定日: {$public_due_date}\n"
					. "----------------------------------------------------------------------\n"
					. "\n"
					. "再申請する場合は、再度、パッケージ登録から行ってください。\n"
					. "承認依頼する際は、特記事項欄にどのパッケージの再申請かを\n"
					. "記載してください。\n"
					. "\n"
					. "このメールはシステムより自動配信されています。\n"
					. "返信は受付できませんので、ご了承ください。\n"
					;
				$ctrl->send_core($tos,$subject,$bodys);
		}
	}
}
?>