<?php
App::uses('AppController', 'Controller');
App::uses('Status', 'Lib');
App::uses('AppConstants',	'Lib/Constants');

/**
 * スケジュール表示クラス
 * @author hsuzuki
 *
 */
class ScheduleController extends AppController {

	public $uses = array('ProjectUser');
	
	/**
	 * スケジュール表示
	 * @author hsuzuki
	 */
	public function index() {
    	
		// 戻るボタン押下時遷移用ＵＲＬセット
		$breadcrumb_ary = $this->Session->read("breadcrumb");
		$breadcrumb_ary[1] = $_SERVER["REQUEST_URI"];
		unset($breadcrumb_ary[2]);
		unset($breadcrumb_ary[3]);
		unset($breadcrumb_ary[4]);
		$this->Session->delete("breadcrumb");
		$this->Session->write("breadcrumb",$breadcrumb_ary );
		
		
		$user_id = $this->Auth->user("id");
    	$roll_cd = $this->Auth->user("roll_cd");
    	
		// パッケージ検索 
		if($roll_cd == AppConstants::ROLL_CD_ADMIN or $roll_cd == AppConstants::ROLL_CD_PR){
			$where = "";
		}
		else{
			$where = " WHERE ProjectUser.user_id = {$user_id}" ;
		}
		$sql
			= "select distinct Project.*, Package.* from "
			. "(select * from packages where packages.is_del = '0' and packages.status_cd = ?) as Package "
			. " left join projects     as Project     on Project.id = Package.project_id      and Project.is_del = '0' "
			. " left join project_user as ProjectUser on Project.id = ProjectUser.project_id and ProjectUser.is_del = '0' "
			. $where
			. " order by Package.modified desc "
			;
		
		$projects = $this->ProjectUser->query($sql,array(Status::STATUS_CD_RELEASE_RESERVE));
		$this->set('projects', $projects);
	}
}