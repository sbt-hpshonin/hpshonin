<?php
//App::import("Controller", "BatchNewProject");
//App::import("Controller", "BatchEditProject");
//App::import("Controller", "BatchDeleteProject");
//App::import("Controller", "BatchNewUser");
//App::import("Controller", "BatchEditUser");
//App::import("Controller", "BatchDeleteUser");
//App::import("Controller", "BatchDeleteUserNoProject");
//App::import("Controller", "BatchEMailApprovalRequest");
//App::import("Controller", "BatchEMailApprovalRequestOk");
//App::import("Controller", "BatchEMailApprovalRequestNg");
//App::import("Controller", "BatchCreatePackagePublish");
//App::import("Controller", "BatchCreatePackageDelete");
//App::import("Controller", "BatchCreateBlogPackage");
App::import("Controller", "BatchPublishPackagePublish");
App::import("Controller", "BatchPublishPackageDelete");
App::import("Controller", "BatchPublishBlogPackage");
//App::import("Controller", "BatchEMailOpenFinish");
//App::import("Controller", "BatchEMailOpenNg");
//App::import("Controller", "BatchEMailOpenSchedule");
//App::import("Controller", "BatchApprovalPackagePublish");
//App::import("Controller", "BatchApprovalPackageDelete");
//App::import("Controller", "BatchApprovalBlogPackage");
App::import("Controller", "BatchPublishPackageRestore");

//App::import("Controller", "BatchDummy");

App::import("Lib", "Constants/AppConstants");
App::import("Lib", "Batch");
class TestBatchPackageShell extends AppShell {

	/**
	 *
	 */
	public function main() {
		$this->log("TestBatchPackageShell開始", LOG_INFO);

		//テストメソッド実行
		$this->Obj = null;
//		$this->Obj = new BatchPublishPackagePublishController();
//		$this->Obj = new BatchPublishPackageDeleteController();
//		$this->Obj = new BatchPublishPackageRestoreController();
		$this->Obj = new BatchPublishBlogPackageController();
		$this->Obj->execute_after_success();

		$this->log("TestBatchPackageShell終了", LOG_INFO);
	}
}
?>