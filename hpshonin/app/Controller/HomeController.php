<?php
App::uses('AppController', 'Controller');
App::uses('Status', 'Lib');
App::uses('AppConstants',	'Lib/Constants');

/**
 * ホームコントローラー
 * @author hsuzuki
 *
 */
class HomeController extends AppController {

	public $uses = array('ProjectUser');

	/**
	 * ホーム画面表示
	 * @author hsuzuki
	 */
	public function index() {

		// 戻るボタン押下時遷移用ＵＲＬセット
		$breadcrumb_ary[0] = $_SERVER["REQUEST_URI"];
		$this->Session->delete("breadcrumb");
		$this->Session->write("breadcrumb",$breadcrumb_ary );

		
		$user_id = $this->Auth->user("id");
    	$roll_cd = $this->Auth->user("roll_cd");
		if($roll_cd == AppConstants::ROLL_CD_ADMIN or $roll_cd == AppConstants::ROLL_CD_PR){
			$where = "";
		}
		else{
			$where = " WHERE ProjectUser.user_id = {$user_id}" ;
		}
		$sql 
			= "select distinct Project.*, Package.* from "
			. "(select * from packages where packages.is_del = '0' and packages.status_cd in(%s)) as Package "
			. " left join projects     as Project     on Project.id = Package.project_id      and Project.is_del = '0' "
			. " left join project_user as ProjectUser on Project.id = ProjectUser.project_id and ProjectUser.is_del = '0' "
			. $where 
			. " order by Package.modified desc " 
			;
		
    	// パッケージ登録検索
		$in = "'". Status::STATUS_CD_PACKAGE_ENTRY  ."'";
		$projects = $this->ProjectUser->query(sprintf($sql,$in));
		$this->set('projects00', $projects);

		// 承認依頼検索
		$in = "'". Status::STATUS_CD_APPROVAL_REQUEST  ."'";
		$projects = $this->ProjectUser->query(sprintf($sql,$in));
		$this->set('projects01', $projects);

		// 承認済み検索
		$in = "'". Status::STATUS_CD_APPROVAL_OK . "','" . Status::STATUS_CD_RELEASE_REJECT ."'";
		$projects = $this->ProjectUser->query(sprintf($sql,$in));
		$this->set('projects02', $projects);
		
		// 公開エラー検索
		$in = "'". Status::STATUS_CD_RELEASE_ERROR ."'";
		$projects = $this->ProjectUser->query(sprintf($sql,$in));
		$this->set('projects03', $projects);
		
		// debug($this->ProjectUser->getDataSource()->getLog());
	}
}