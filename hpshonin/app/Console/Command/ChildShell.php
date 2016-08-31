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
App::import("Controller", "BatchCreateBlogPackage");
App::import("Controller", "BatchPublishPackagePublish");
App::import("Controller", "BatchPublishPackageDelete");
App::import("Controller", "BatchPublishBlogPackage");
App::import("Controller", "BatchEMailOpenFinish");
App::import("Controller", "BatchEMailOpenNg");
App::import("Controller", "BatchEMailOpenSchedule");
App::import("Controller", "BatchApprovalPackagePublish");
App::import("Controller", "BatchApprovalPackageDelete");
App::import("Controller", "BatchApprovalBlogPackage");
App::import("Controller", "BatchPublishPackageRestore");

//App::import("Controller", "BatchDummy");

App::import("Lib", "Constants/AppConstants");
App::import("Lib", "Batch");
class ChildShell extends AppShell {

	/**
	 * 使用するモデル
	 * @var unknown
	 */
	public $uses = array('BatchQueue', 'Mutex');

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
		}

		// Mutexにより、同一プロジェクトまたはユーザーの場合、処理抜けする。
		$project_id = $queue['BatchQueue']['project_id'];
		$package_id = $queue['BatchQueue']['package_id'];
		$user_id = $queue['BatchQueue']['user_id'];
		$batchCd = $queue['BatchQueue']['batch_cd'];
		$process_id = 0;
		$this->log("Mutex登録：id[$queue_id]", LOG_INFO);
		if (!$this->Mutex->tryLock($batchCd, $queue_id, $package_id, $project_id, $user_id, $process_id)) {
			$this->log("同一のプロジェクトまたはユーザのバッチ処理が実行中なので、処理を停止します。：id[$queue_id]", LOG_INFO);
			exit;
		}

		$queue['BatchQueue']['result_cd'] = AppConstants::RESULT_CD_EXECUTION;
		$this->BatchQueue->save($queue);


		//バッチ起動CDで振り分ける
		$this->Obj = null;

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
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				$this->Obj = new BatchCreateBlogPackageController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			}
		} else if ($batchCd === Batch::BATCH_CD_REQUEST_APPROVAL) {
			//'22':承認依頼
			$this->Obj = new BatchEMailApprovalRequestController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);	//パッケージid
		} else if ($batchCd === Batch::BATCH_CD_APPROVAL_OK) {
			//'31':承認許可
			$operationCd = $queue['Package']['operation_cd'];
			$isBlog = $queue['Package']['is_blog'];
			if ($operationCd === AppConstants::OPERATION_CD_PUBLIC &&
			$isBlog === AppConstants::FLAG_FALSE) {
				// 公開パッケージ作成
				$this->Obj = new BatchApprovalPackagePublishController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			} else if ($operationCd === AppConstants::OPERATION_CD_DELETE &&
			$isBlog === AppConstants::FLAG_FALSE) {
				// 削除パッケージ作成
				$this->Obj = new BatchApprovalPackageDeleteController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				$this->Obj = new BatchApprovalBlogPackageController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);	// パッケージid
			}
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
			} else if ($isBlog === AppConstants::FLAG_TRUE) {
				// 公開ブログパッケージ公開
				$this->Obj = new BatchPublishBlogPackageController();
				$this->Obj->setId($queue['BatchQueue']['package_id']);
			}
		} else if ($batchCd === Batch::BATCH_CD_RESTORE) {
			$this->Obj = new BatchPublishPackageRestoreController();
			$this->Obj->setId($queue['BatchQueue']['package_id']);
//		} else if ($batchCd === '99') {
//			$this->Obj = new BatchDummyController();
		} else {
			$this->log("無効なバッチCD：batch_cd[$batchCd]", LOG_ERR);
		}


		// キューのコマンドを実行し、結果を保存する
		if ($this->Obj) {
			$this->log("バッチコントロールを開始", LOG_INFO);
			$resultCd = $this->Obj->execute();
			$queue['BatchQueue']['result_cd'] = $resultCd;
		}
		// 終了時刻を取得
		$queue['BatchQueue']['end_at'] = date('Y-m-d H:i:s');

		//結果をバッチキューに更新
		$this->log("結果をキューに保存：result_cd[$resultCd]", LOG_INFO);
		$this->BatchQueue->save($queue);

		// 処理開始に生成したMutexを解放
		$this->log("Mutex解放：id[$queue_id]", LOG_INFO);
		$this->Mutex->unlock($queue_id);

		$this->log("子プロセス終了", LOG_INFO);
	}
}
?>