<?php
App::import("Controller", "BatchNewProject");
App::import("Controller", "BatchEditProject");
App::import("Controller", "BatchDeleteProject");
App::import("Controller", "BatchNewUser");
App::import("Controller", "BatchEditUser");
App::import("Controller", "BatchDeleteUser");
App::import("Controller", "BatchDeleteUserNoProject");
App::import("Controller", "BatchEMailApprovalRequest");
App::import("Controller", "BatchEMailApprovalRequestOk");
App::import("Controller", "BatchEMailApprovalRequestNg");
App::import("Controller", "BatchCreatePackagePublish");
App::import("Controller", "BatchCreatePackageDelete");
//App::import("Controller", "BatchCreateBlogPackagePublish");
//App::import("Controller", "BatchCreateBlogPackageDelete");
App::import("Controller", "BatchCreateBlogPackage");
App::import("Controller", "BatchPublishPackagePublish");
App::import("Controller", "BatchPublishPackageDelete");
//App::import("Controller", "BatchPublishBlogPackagePublish");
//App::import("Controller", "BatchPublishBlogPackageDelete");
App::import("Controller", "BatchPublishBlogPackage");
App::import("Controller", "BatchEMailOpenFinish");
App::import("Controller", "BatchEMailOpenNg");
App::import("Controller", "BatchEMailOpenSchedule");

App::import("Lib", "Constants/AppConstants");
App::import("Controller", "BatchDummy");	//テスト用(リリース時には使わない)
App::import("Lib", "Batch");
class ChildShell extends AppShell {

	/** 再実行の規定数 */
	const COUNT_RETRY = 5;
	/** 再実行までの待ち秒数 */
	const WAIT_SECOND = 10;

	/**
	 * 使用するモデル
	 * @var unknown
	 */
	public $uses = array('BatchQueue');

	/**
	 *
	 */
	public function main() {
		$this->log("子プロセス開始", LOG_INFO);

		$queue_id = 0;
		if(isset($this->args[0])) {
			$queue_id = $this->args[0];
		} else {
			$this->log("バッチの引数が足りません。", LOG_ERR);
			exit;
		}

		$queue = $this->BatchQueue->findById($queue_id);

		if (empty($queue)) {
			$this->log("削除されてます。：id[$queue_id]", LOG_ERR);
			exit;
		} else {
			if ($queue['BatchQueue']['result_cd'] !== AppConstants::RESULT_CD_EXECUTION) {
				$this->log("別プロセスから起動されてます。：id[$queue_id]", LOG_ERR);
				exit;
			}
		}

		//バッチ起動CDで振り分ける
		$this->Obj = null;

		$batchCd = $queue['BatchQueue']['batch_cd'];
		if ($batchCd === Batch::BATCH_CD_MT_PROJECT_CREATE) {
			//'11':MTプロジェクト作成
			$this->Obj = new BatchNewProjectController();
			$this->Obj->setId($queue['BatchQueue']['project_id']);	//プロジェクトid
		} else if ($batchCd === Batch::BATCH_CD_MT_PROJECT_UPDATE) {
			//'12':MTプロジェクト更新
			$this->Obj = new BatchEditProjectController();
			$this->Obj->setId($queue['BatchQueue']['project_id']);	//プロジェクトid
		} else if ($batchCd === Batch::BATCH_CD_MT_PROJECT_DELETE) {
			//'13':MTプロジェクト削除
			$this->Obj = new BatchDeleteProjectController();
			$this->Obj->setId($queue['BatchQueue']['project_id']);	//プロジェクトid
		} else if ($batchCd === Batch::BATCH_CD_MT_USER_CREATE) {
			//'14':MTユーザー作成
			$this->Obj = new BatchNewUserController();
			$this->Obj->setId($queue['BatchQueue']['user_id']);	//ユーザーid
		} else if ($batchCd === Batch::BATCH_CD_MT_USER_UPDATE) {
			//'15':MTユーザー変更
			$this->Obj = new BatchEditUserController();
			$this->Obj->setId($queue['BatchQueue']['user_id']);	//ユーザーid
		} else if ($batchCd === Batch::BATCH_CD_MT_USER_DELETE) {
			//'16':MTユーザー削除
			$this->Obj = new BatchDeleteUserController();
			$this->Obj->setId($queue['BatchQueue']['user_id']);	//ユーザーid
		} else if ($batchCd === Batch::BATCH_CD_NO_PROJECT_USER_DELETE) {
			//'17':無所属ユーザー削除
			$this->Obj = new BatchDeleteUserNoProjectController();
			$userId = $queue['User']['id'];
			$this->Obj->setId($userId);	//ユーザーid
		} else if ($batchCd === Batch::BATCH_CD_PACKAGE_CREATE) {
			//'21':パッケージ作成
			$operationCd = $queue['Package']['operation_cd'];
			$isBlog = $queue['Package']['is_blog'];
			if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 公開パッケージ作成
				$this->Obj = new BatchCreatePackagePublishController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 削除パッケージ作成
				$this->Obj = new BatchCreatePackageDeleteController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
// (S)2013.10.12 murata ブログのパッケージ作成は一つに統一。
//			} else if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
//				$isBlog === AppConstants::FLAG_TRUE) {
//				// 公開ブログパッケージ作成
//				$this->Obj = new BatchCreateBlogPackagePublishController();
//				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
//			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
//				$isBlog === AppConstants::FLAG_TRUE) {
//				// 削除ブログパッケージ作成
//				$this->Obj = new BatchCreateBlogPackageDeleteController();
//				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
//			}
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				$this->Obj = new BatchCreateBlogPackageController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			}
// (E)2013.10.12
		} else if ($batchCd === Batch::BATCH_CD_REQUEST_APPROVAL) {
			//'22':承認依頼
			$this->Obj = new BatchEMailApprovalRequestController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);	//パッケージid
		} else if ($batchCd === Batch::BATCH_CD_APPROVAL_OK) {
			//'31':承認許可
			$this->Obj = new BatchEMailApprovalRequestOkController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);	//パッケージid
		} else if ($batchCd === Batch::BATCH_CD_APPROVAL_NG) {
			//'32':承認却下
			$this->Obj = new BatchEMailApprovalRequestNgController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);	//パッケージid
		} else if ($batchCd === Batch::BATCH_CD_SCHEDULE) {
			//'41':公開予定
			$this->Obj = new BatchEMailOpenScheduleController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);	//パッケージid
		} else if ($batchCd === Batch::BATCH_CD_RELEASE) {
			//'42':公開
			$operationCd = $queue['Package']['operation_cd'];
			$isBlog = $queue['Package']['is_blog'];
			if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 公開パッケージ公開
				$this->Obj = new BatchPublishPackagePublishController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
				$isBlog === AppConstants::FLAG_FALSE) {
				// 削除パッケージ公開
				$this->Obj = new BatchPublishPackageDeleteController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
// (S)2013.10.12 murata ブログのパッケージ作成は一つに統一。
//			} else if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
//				$isBlog === AppConstants::FLAG_TRUE) {
//				// 公開ブログパッケージ公開
//				$this->Obj = new BatchPublishBlogPackagePublishController();
//				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
//			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
//				$isBlog === AppConstants::FLAG_TRUE) {
//				// 削除ブログパッケージ公開
//				$this->Obj = new BatchPublishBlogPackageDeleteController();
//				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
//			}
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				// 公開ブログパッケージ公開
				$this->Obj = new BatchPublishBlogPackageController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);
			}
// (E)2013.10.12
		} else if ($batchCd === '99') {
			// テスト用のダミーバッチ(後で削除予定)
			$this->Obj = new BatchDummyController();
		} else {
			$this->log("無効なバッチCD：batch_cd[$batchCd]", LOG_ERR);
		}


		// キューのコマンドを実行し、結果を保存する
		if ($this->Obj) {
			$this->log("バッチコントロールを開始", LOG_INFO);
// (S) 2013.10.17 murata リトライはここでは不可能・バッチコントローラ側で制御する。
//			$i = 0;
//			do {
//				$i++;
				$resultCd = $this->Obj->execute();
//				// 失敗したら
//				if ($resultCd === AppConstants::RESULT_CD_FAILURE &&
//					$i < self::COUNT_RETRY) {
//					sleep(self::WAIT_SECOND);
//				}
//			} while ($resultCd === AppConstants::RESULT_CD_FAILURE &&
//					$batchCd === Batch::BATCH_CD_RELEASE &&
//					$i < self::COUNT_RETRY	// リトライ
//			);
// (E) 2013.10.17
			$queue['BatchQueue']['result_cd'] = $resultCd;

			// 終了時刻を取得
			$queue['BatchQueue']['end_at'] = date('Y-m-d H:i:s');

			//結果をバッチキューに更新
			$this->log("結果をキューに保存：result_cd[$resultCd]", LOG_INFO);
			$this->BatchQueue->save($queue);

			// 実行後処理
			if ($batchCd === Batch::BATCH_CD_RELEASE){
				switch($resultCd){
					case AppConstants::RESULT_CD_SUCCESS:
						$this->log("公開完了メールを送信", LOG_INFO);
						// 公開完了
						$email_obj = new BatchEMailOpenFinishController;
						$email_obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
						$resultCd = $email_obj->execute();
						break;

					case AppConstants::RESULT_CD_FAILURE:
						$this->log("公開エラーメールを送信", LOG_INFO);
						// 公開エラー
						$email_obj = new BatchEMailOpenNgController;
						$email_obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
						$resultCd = $email_obj->execute();
						break;
				}
			}
		}
		$this->log("子プロセス終了", LOG_INFO);
	}
}
?>