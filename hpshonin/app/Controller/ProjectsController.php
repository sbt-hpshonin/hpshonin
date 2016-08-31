<?php
App::uses('AppController', 'Controller');
App::uses('MsgConstants',	'Lib/Constants');
App::uses('AppConstants',	'Lib/Constants');
App::uses('Status', 'Lib');
App::uses('Batch', 'Lib');
App::import("Lib", "Constants/AppConstants");
App::uses('Sanitize', 'Utility');
App::uses('guidChkUtil', 'Lib/Utils');
App::uses('Component', 'Component');
App::uses('AuthorityChkComponent', 'Controller/Component');
App::uses('MsgConstants',	'Lib/Constants');


/**
 * プロジェクトコントローラクラス
 * @author smurata
 *
*/
class ProjectsController extends AppController {

	// public $uses = array('User', 'Project','ProjectUser', 'Roll', 'Auth','Package', 'BatchQueue');
	public $uses = array('Project','ProjectUser', 'Auth','Package', 'BatchQueue','User');
	var $helpers = array("Session");

	/**
	 * 初期表示
	*/
	public function index() {
		$this->setAction("search");
	}
	
	function beforeFilter(){
		parent::beforeFilter();
		//認証不要のページの指定
		// （別ウィンドウのため、ログインページには飛ばさずに個別対応）
		$this->Auth->allow('approval', 'request');
	}
	
	/**
	 * プロジェクト検索
	 */
	public function search() {

		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");

		// 該当プロジェクトが１件の場合、詳細に移動
		if($roll_cd == AppConstants::ROLL_CD_DEVELOP or
		   $roll_cd == AppConstants::ROLL_CD_SITE ){
			$sql =
				"SELECT distinct ".
				"`Project`.id ".
				" FROM `project_user` AS `ProjectUser` ".
				" LEFT JOIN `projects` AS `Project` ON (`ProjectUser`.`project_id` = `Project`.`id`) ".
				" where ProjectUser.user_id = " . addslashes($user_id).
				"   and project.is_del = 0 ".
				" order by project.id ";
			$projects = $this->Project->query($sql);
			if(count($projects)==1){
				return $this->redirect("../projects/view/". $projects[0]["Project"]["id"]);
			}
		}
		
		
		// 戻るボタン押下時遷移用ＵＲＬセット
		$breadcrumb_ary = $this->Session->read("breadcrumb");
		// $breadcrumb_ary[1] = $this->name ."/". $this->action;
		$breadcrumb_ary[1] = $_SERVER["REQUEST_URI"];
		unset($breadcrumb_ary[2]);
		unset($breadcrumb_ary[3]);
		unset($breadcrumb_ary[4]);
		$this->Session->delete("breadcrumb");
		$this->Session->write("breadcrumb",$breadcrumb_ary );
//print_r($breadcrumb_ary);
		
		@$search_word     = $this->data["search_word"];
		if($search_word==NULL){
			$search_word= '';
		}
		
		// 検索文字列のエスケープ
		$search_word_txt = $search_word; 
		$search_word_txt = str_replace('\\','\\\\',$search_word_txt); // \のエスケープ
		$search_word_txt = str_replace('%','\\%',$search_word_txt);   // %のエスケープ
		$search_word_txt = "%{$search_word_txt}%";
		
		// プロジェクト検索
		if($roll_cd == AppConstants::ROLL_CD_ADMIN or
		   $roll_cd == AppConstants::ROLL_CD_PR ){
			
			$optioon = array(
					'conditions' => array(
							'project.is_del'=>0,
							array("or" => array(
									"project.project_name like ?" => $search_word_txt,
									"department_name like ?" => $search_word_txt,
									"site_name like ?" => $search_word_txt,
							)
							)
					),
					'order' => array("project.id"),
					'recursive' => -1
			);
			$del_optioon = array(
					'conditions' => array(
							'project.is_del'=>1,
					),
					'order' => array("project.modified desc"),
					'recursive' => -1
			);
			$projects = $this->Project->find('all', $optioon);
			$del_projects = $this->Project->find('all', $del_optioon);
		}
		else{
			$sql =
			"SELECT distinct ".
			"`Project`.* ".
			" FROM `project_user` AS `ProjectUser` ".
			" LEFT JOIN `projects` AS `Project` ON (`ProjectUser`.`project_id` = `Project`.`id`) ".
			" where ProjectUser.user_id = " . addslashes($user_id).
			"   and project.is_del = 0 ".
			"   and ( project.project_name like ? or department_name like ? or site_name like ? ) ".
			" order by project.id ";

			$sql2 =
			"SELECT distinct ".
			"`Project`.* ".
			" FROM `project_user` AS `ProjectUser` ".
			" LEFT JOIN `projects` AS `Project` ON (`ProjectUser`.`project_id` = `Project`.`id`) ".
			" where ProjectUser.user_id = " . addslashes($user_id).
			"   and project.is_del = 1 ".
			" order by project.modified desc";

			$projects = $this->ProjectUser->query($sql,array($search_word_txt,$search_word_txt,$search_word_txt));
			$del_projects = $this->ProjectUser->query($sql2);
		}


		$this->set('projects', $projects);
		$this->set('del_projects', $del_projects);

		$this->set("search_word",$search_word);
		$this->set("roll_cd",$roll_cd);
	}

	/**
	 * プロジェクト詳細
	 * @param $id	プロジェクトID
	 */
	public function view($id="") {

		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$roll_cd = AuthComponent::user("roll_cd");
		
		////////////////////////////////////////////////////////
		// 権限チェック
		////////////////////////////////////////////////////////
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authProject($id) == False ){
		
			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}
		
		
		// プロジェクト検索
		$optioon = array(
				'conditions' => array(
						'Project.id' => $id
				),
				'recursive' => -1
		);
		$projects = $this->Project->find('first', $optioon);
		if(count($projects)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$this->set('project', $projects);


		// プロジェクトメンバー検索
		// $optioon = array(
		// 		'conditions' => array(
		// 				'ProjectUser.project_id' => $id,
		// 		),
		// 		'recursive' => 1
		// );
		// $projectUsers = $this->ProjectUser->find('all', $optioon);
		$sql 
			= "select "
			. "  User.*,Roll.roll_name "
			. "from project_user as ProjectUser "
			. "inner join users as User on ProjectUser.user_id = User.id and User.is_del='0' "
			. "inner join rolls as Roll on User.roll_cd = Roll.roll_cd "
			. "where"
			. "  ProjectUser.project_id = ? "
			. "  AND ( User.roll_cd=". AppConstants::ROLL_CD_DEVELOP . " or User.roll_cd=". AppConstants::ROLL_CD_SITE . ")"
			. "order by User.id "
			;		
		$projectUsers = $this->ProjectUser->query($sql,array($id));
		$this->set('projectUsers', $projectUsers);

		// パッケージ検索
		$this->paginate = array(
				'conditions' => array(
						'Package.project_id' => $id,
						'Package.is_del' => 0,
				),
				'limit' => 10,
				// 'order' => "Package.modified desc",
				'order' => "Package.id desc ",
				'recursive' => 0,
		);

		// 戻るボタン押下時遷移用ＵＲＬセット
		if($this->action != "view_short"){
			$breadcrumb_ary = $this->Session->read("breadcrumb");
			//$breadcrumb_ary[2] = "/". $this->name ."/". $this->action ."/". $id ;
			$breadcrumb_ary[3] = $_SERVER["REQUEST_URI"];
			unset($breadcrumb_ary[4]);
			$this->Session->delete("breadcrumb");
			$this->Session->write("breadcrumb",$breadcrumb_ary );
//print_r($breadcrumb_ary);
		}
				
		$data = $this->paginate('Package');
		$this->set('packages', $data);
		$this->set('roll_cd', $roll_cd);
		
		//debug($this->ProjectUser->getDataSource()->getLog());
	}

	/**
	 * プロジェクト詳細(ポップアップ用)
	 * @param $id	プロジェクトID
	 */
	public function view_short($id="") {

		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		//DIE("TEST VIEW_SHORT");
		$this->layout = "publish";
		$this->view($id);


	}
	/**
	 * プロジェクト追加
	 * @author hsuzuki
	 */
	public function add() {

		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authAdmin() == False ){
		
			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}
		
		while (True) {

			if ($this->request->is('post') == False) {
				// 新規→入力
				break;
			}

			// GUIDチェック
			if( guidChkUtil::chkGUID() == false ){
				throw new NoDataException( MsgConstants::ERROR_GUID );
			}
				
			// エラーチェック
			$this->Project->set($this->data);
			if($this->Project->validates() == False){
				// エラーあり→再入力
				break;
			}

			$this->Project->begin();

			// プロジェクト登録
			$project_data = $this->data;
			$project_data["created_user_id"] = $user_id;
			$project_data["modified_user_id"] = $user_id;
			if (!$this->Project->save($project_data)) {
				// 登録失敗→再入力
				$this->Project->rollback();
				$this->Session->setFlash(MsgConstants::ERROR_DB);
				break;
			}
			$project_id = $this->Project->getInsertID();
			
			// バッチキュー登録
			$this->BatchQueue->set( "created_user_id" , $user_id  );
			$this->BatchQueue->set( "modified_user_id" , $user_id  );
			$this->BatchQueue->set( "batch_cd" , Batch::BATCH_CD_MT_PROJECT_CREATE  );
			$this->BatchQueue->set( "project_id" , $project_id  );
			if( $this->BatchQueue->save() == false ){
				// 登録失敗→再入力
				$this->Project->rollback();
				$this->Session->setFlash(MsgConstants::ERROR_DB);
				break;
			}
			
			
			// debug($this->ProjectUser->getDataSource()->getLog());
			
			$this->Project->commit();
			// 登録成功→プロジェクト一覧へ
			//登録成功時　詳細画面に遷移　BY　清野
			//return $this->redirect("../projects/");
			return $this->redirect("../projects/view/".$project_id);
		}

		// 入力画面表示
		$projects = array();
		if ($this->request->is('post') == True) {
			$projects['Project']['id'] = "";
			$projects['Project']['project_name'] = $this->data["project_name"];
			$projects['Project']['department_name'] = $this->data["department_name"];
			$projects['Project']['site_name'] = $this->data["site_name"];
			$projects['Project']['site_url'] = $this->data["site_url"];
		}

		$this->set("new", true);
		$this->set('project', $projects);
		$this->render("edit");

	}

	/**
	 * プロジェクト更新
	 * @param $id プロジェクトID
	 */
	public function edit( $id = "" ) {
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authAdmin() == False ){
		
			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}		

		while ($this->request->is('post') == True) {

			// GUIDチェック
			if( guidChkUtil::chkGUID() == false ){
				throw new NoDataException( MsgConstants::ERROR_GUID );
			}
				
			//
			// 楽観ロックチェックを行う
			//
			if ( $this->Session->check( "Versioned".$this->Project->alias ) ) {

				// 更新時のデータが取得でなかった場合は編集中にほかのデータが更新ていると判断する
				$find_conditions = array( "conditions" => array( "id"=> $id, "modified" => $this->Session->read("Versioned".$this->Project->alias) ), 'recursive' => -1  );
				$data_count = $this->Project->find("count", $find_conditions );
				if ( $data_count == 0 ) {
					$this->Session->setFlash(MsgConstants::ERROR_OPTIMISTIC_LOCK);
					break;
				}
			}

			// エラーチェック
			$this->Project->set($this->data);
			if($this->Project->validates() == False){
				break;
			}


			$this->Project->begin();

			// プロジェクト登録
			$project_data["project_name"] = $this->data ["project_name"];
			$project_data["department_name"] = $this->data["department_name"];
			$project_data["site_name"] = $this->data["site_name"];
			$project_data["id"] = $id;
			$project_data["modified_user_id"] = $user_id;
			
			$this->Project->set($project_data);
			
			if ($this->Project->save($project_data) == false) {
				// 登録失敗→再入力
				$this->Project->rollback();
				break;
			}

			// バッチキュー登録
			$this->BatchQueue->set( "created_user_id" , $user_id  );
			$this->BatchQueue->set( "modified_user_id" , $user_id  );
			$this->BatchQueue->set( "batch_cd" , Batch::BATCH_CD_MT_PROJECT_UPDATE  );
			$this->BatchQueue->set( "project_id" , $id  );

			if( $this->BatchQueue->save() == false ){
				// 登録失敗→再入力
				$this->Project->rollback();
				break;
			}


			$this->Project->commit();

			$this->redirect("/projects");
			return;
		}

		// プロジェクト検索
		$optioon = array(
				'conditions' => array('Project.id' => $id),
				'recursive' => -1
		);
		$projects = $this->Project->find('first', $optioon);
		if($projects == False){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// プロジェクトユーザー検索
		$sql
			= "select "
			. "  User.*,Roll.roll_name "
			. "from project_user as ProjectUser "
			. "inner join users as User on ProjectUser.user_id = User.id and User.is_del='0' "
			. "inner join rolls as Roll on User.roll_cd = Roll.roll_cd "
			. "where"
			. "  ProjectUser.project_id = ? "
			. "  AND ( User.roll_cd=". AppConstants::ROLL_CD_DEVELOP . " or User.roll_cd=". AppConstants::ROLL_CD_SITE . ")"
			. "order by User.id "
			;
		$project_user = $this->ProjectUser->query($sql,array($id));
		
		//print_r($project_user);

		$this->set('project_name', $projects['Project']['project_name']);
		if ($this->request->is('post') == True) {
			$projects['Project']['project_name'] = $this->data["project_name"];
			$projects['Project']['department_name'] = $this->data["department_name"];
			$projects['Project']['site_name'] = $this->data["site_name"];
			$projects['Project']['site_url'] = $this->data["site_url"];
		}

		// 初期表示の遷移と判断する
		else {
			// 楽観ロック用のバージョンをセッションに追加
			$this->Session->write("Versioned".$this->Project->alias, $projects['Project']['modified']);
		}

		$this->set("new", false);
		$this->set('project', $projects);
		$this->set('project_user',$project_user);
	}

	/**
	 * 削除処理
	 * @param $id プロジェクトID
	 * @author hsuzuki
	 *
	 */
	public function delete( $id='' ) {

		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}


		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authAdmin() == False ){
		
			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}
		

		// 状態チェック 
		$optioon = array(
				'conditions' => array(
						'id'     => $id,
				),
				'recursive' => -1
		);
		$projects = $this->Project->find("first", $optioon );
		if ( count($projects) == 0 ) {
			// 該当プロジェクトなし
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		if ( $projects["Project"]["is_del"] == 1 ) {
			// 削除済み
			$this->Session->setFlash(MsgConstants::ERROR_OPTIMISTIC_LOCK);
			return $this->redirect('/projects/view/'.$id);
		}
		
		
		$this->Project->begin();

		// プロジェクト登録
		$project_data["id"] = $id;
		$project_data["modified_user_id"] = $user_id;
		$project_data["is_del"] = 1;
		$project_data["public_reservation_datetime"] = date('Y-m-d H:i:s');
		if ($this->Project->save($project_data) == false) {
			// 登録失敗
			$this->Project->rollback();
			$this->Session->setFlash(MsgConstants::ERROR_DB );
			return $this->redirect("../projects/view/".$id);
		}

		// バッチキュー登録
		$this->BatchQueue->set( "created_user_id" , $user_id  );
		$this->BatchQueue->set( "modified_user_id" , $user_id  );
		$this->BatchQueue->set( "batch_cd" , Batch::BATCH_CD_MT_PROJECT_DELETE );
		$this->BatchQueue->set( "project_id" , $id  );
		if( $this->BatchQueue->save() == false ){
			// 登録失敗
			$this->Project->rollback();
			$this->Session->setFlash(MsgConstants::ERROR_DB );
			return $this->redirect("../projects/view/".$id);
		}


		$this->Project->commit();
		//削除正常終了時はプロジェクト一覧に遷移
		
		//$this->redirect("../projects/view/".$id);
		return $this->redirect("../projects/");
	}

	/**
	 * 承認依頼
	 * @param $package_id パッケージID
	 */
	public function request($package_id="") {

		// パッケ－ジID未指定
		if($package_id == "" || preg_match("/^[0-9]+$/", $package_id) !== 1){
			// throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
				
		}
		
		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}
		
		$request_note = "";
		$err_msg = array();

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authPackage($package_id) == False ){
		
			// 必要権限がない場合にはエラー表示
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_AUTH);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}
		
		// 特記事項等のバリデーションチェックをし、ステータスを承認依頼(02)に更新
		while(True){
			// データ取得
			$optioon = array(
					'conditions' => array(
							'Package.id'     => $package_id,
							//						'Package.is_del' => AppConstants::FLAG_OFF,
					),
					'recursive' => -1
			);
			// 取得結果が１件以外の場合はホームにリダイレクトする
			$count = $this->Package->find('count', $optioon);
			if($count !== 1){
				//throw new NoDataException( MsgConstants::ERROR_NO_DATA );
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				return;
			}
			$package = $this->Package->find('first', $optioon);
			
				
			//該当パッケージが論理削除された場合エラー
			if ($package['Package']['is_del'] == AppConstants::FLAG_ON){
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			
			
			//現在のステータスが承認依頼できるかチェック
			if ($package['Package']['status_cd'] != Status::STATUS_CD_PACKAGE_ENTRY){
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_REQUEST);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			
			
			if(isset($this->request->data['request_note']) == False ){
				// 新規処理　画面表示→

				// 楽観ロック用のバージョンをセッションに追加
				$this->Session->write("Versioned".$this->Package->alias, $package['Package']['modified']);
				break;
			}
			
			
			//
			// 楽観ロックチェック
			//
			if ( $this->Session->check( "Versioned".$this->Package->alias ) ) {

				// 更新時のデータが取得でなかった場合は編集中にほかのデータが更新されていると判断する
				$find_conditions = array( "conditions" => array(
						"id"=> $package_id,
						"modified" => $this->Session->read("Versioned".$this->Package->alias)
				), 'recursive' => -1  );
				$data_count = $this->Package->find("count", $find_conditions );
				if ( $data_count == 0 ) {
					// エラーメッセージセット
					$err_msg[] = array("Versioned" => MsgConstants::ERROR_OPTIMISTIC_LOCK);
					$this->layout = "publish";
					$this->render("err");
					break;
				}
			}
			
			
			$request_note = $this->request->data['request_note'];
			
			
			// バリデーションチェック
			$this->Package->set($this->request->data);
			if($this->Package->validates() === False){
				// エラーメッセージセット
				$err_msg = $this->Package->validationErrors;
				break;
			}

			$user_id = $this->Auth->user('id');

			$this->Package->begin();
			
			
			// パッケージのステータスコード更新
			$package_data = array(
					"id"               => "'". $package_id . "'",
					"modified_user_id" => "'". $user_id . "'",
					"modified"         => "'". date('Y-m-d H:i:s') . "'",
					"request_user_id"  => "'". $user_id . "'",
					"request_note"     => "'". Sanitize::escape($this->request->data['request_note']) . "'",
					"request_modified" => "'". date('Y-m-d H:i:s') . "'",
					"status_cd"        => "'". Status::STATUS_CD_APPROVAL_REQUEST . "'",
			);
			$condition = array(
					'Package.id'        => $package_id ,
					'Package.is_del'    => AppConstants::FLAG_OFF ,
					"Package.status_cd" => Status::STATUS_CD_PACKAGE_ENTRY ,
			);
			if (!$this->Package->updateAll($package_data,$condition )) {
				$this->Package->rollback();
				
				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			// 履歴登録
			$this->Package->data['Package']['id'] = $package_id;
			$this->Package->afterSave("");
			
			// バッチ起動情報にキュー
			$batch_queues_data = array(
					"package_id"       => $package_id,
					"project_id"       => $package["Package"]["project_id"],
					"create_user_id"   => $user_id,
					"created"          => date('Y-m-d H:i:s'),
					"modified_user_id" => $user_id,
					"modified"         => date('Y-m-d H:i:s'),
					"batch_cd"         => Batch::BATCH_CD_REQUEST_APPROVAL,
			);
			if (!$this->BatchQueue->save($batch_queues_data)) {
				$this->Package->rollback();
				
				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			
			
			$this->Package->commit();
			
			// DB更新時はウィンドウを閉じて親ウィンドウを更新する
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}
		
		
		
		// 入力画面表示
		$this->set('id', $package_id);
		$this->set('request_note', $request_note);
		$this->set('package', $package);
		$this->set('err_msg', $err_msg);
		$this->layout = "publish";

	}

	/**
	 * 公開承認
	 * @param $package_id パッケージID
	 */
	public function approval($package_id="") {
		
		// プロジェクトID未指定
		if($package_id == "" || preg_match("/^[0-9]+$/", $package_id) !== 1){
			//throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}
		
		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authAdmin() == False ){

			// 必要権限がない場合にはエラー表示
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_AUTH);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}
		
		$approval_note = "";
		$err_msg = array();


		// 特記事項等のバリデーションチェックをし、ステータスを承認許可(03)または承認却下(93)に更新
		while(True){
			// データ取得
			$optioon = array(
					'conditions' => array(
							'Package.id'     => $package_id,
					),
					'recursive' => -1
			);
			// 取得結果が１件以外の場合はホームにリダイレクト
			$count = $this->Package->find('count', $optioon);
			if($count !== 1){
				// throw new NoDataException( MsgConstants::ERROR_NO_DATA );
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				return;
			}
			$package = $this->Package->find('first', $optioon);
			
			
			//該当パッケージが論理削除された場合エラー
			if ($package['Package']['is_del'] == AppConstants::FLAG_ON){
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			
			}
				
			
			//現在のステータスが承認できるかチェック
			if ($package['Package']['status_cd'] != Status::STATUS_CD_APPROVAL_REQUEST){
				// エラーメッセージセット
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_APPROVAL);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
					
			}
				
			
			if(isset($this->request->data['approval_note']) == False){
				// 新規処理　画面表示→

				// 楽観ロック用のバージョンをセッションに追加
				$this->Session->write("Versioned".$this->Package->alias, $package['Package']['modified']);
				break;
			}

			//
			// 楽観ロックチェック
			//
			if ( $this->Session->check( "Versioned".$this->Package->alias ) ) {

				// 更新時のデータが取得でなかった場合は編集中にほかのデータが更新されていると判断する
				$find_conditions = array( "conditions" => array(
						"id"=> $package_id,
						"modified" => $this->Session->read("Versioned".$this->Package->alias)
				), 'recursive' => -1  );
				$data_count = $this->Package->find("count", $find_conditions );
				if ( $data_count == 0 ) {
					// エラーメッセージセット
					$err_msg[] = array("Versioned" => MsgConstants::ERROR_OPTIMISTIC_LOCK);
					$this->set('err_msg', $err_msg);
					$this->layout = "publish";
					$this->render("err");
					break;
				}
			}
			
			
			$approval_note = $this->request->data['approval_note'];
			
			
			// 承認・却下によりステータスコードを設定する
			// 承認・却下以外はホームにリダイレクト
			$status = $this->request->data['status'];
			if ($status === 'approval'){
//				$status_cd = Status::STATUS_CD_APPROVAL_OK;
				$status_cd = Status::STATUS_CD_RELEASE_READY;
				$batch_cd = Batch::BATCH_CD_APPROVAL_OK;
			}elseif($status === 'reject'){
				$status_cd = Status::STATUS_CD_APPROVAL_REJECT;
				$batch_cd = Batch::BATCH_CD_APPROVAL_NG;
			}else{
				$this->redirect("../home");
				return;
			}

			// バリデーションチェック
			$this->Package->set($this->request->data);
			if($this->Package->validates() === False){
				// エラーメッセージセット
				$err_msg = $this->Package->validationErrors;
				break;
			}
			
			$user_id = $this->Auth->user('id');
			
			$this->Package->begin();
			
			
			// パッケージのステータスコード更新
			$package_data = array(
					"id"                => "'". $package_id . "'",
					"modified_user_id"  => "'". $user_id . "'",
					"modified"          => "'". date('Y-m-d H:i:s') . "'",
					"approval_user_id"  => "'". $user_id . "'",
					"approval_note"     => "'". Sanitize::escape($this->request->data['approval_note']) . "'",
					"approval_modified" => "'". date('Y-m-d H:i:s') . "'",
					"status_cd"         => "'". $status_cd . "'",
			);
			$condition = array(
					'Package.id'        => $package_id ,
					'Package.is_del'    => AppConstants::FLAG_OFF ,
					"Package.status_cd" => Status::STATUS_CD_APPROVAL_REQUEST ,
			);
			if (!$this->Package->updateAll($package_data,$condition )) {
				$this->Package->rollback();
				
				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			// 履歴登録
			$this->Package->data['Package']['id'] = $package_id;
			$this->Package->afterSave("");
			
			
			// バッチ起動情報にキュー
			$batch_queues_data = array(
					"package_id"       => $package_id,
					"project_id"       => $package["Package"]["project_id"],
					"create_user_id"   => $user_id,
					"created"          => date('Y-m-d H:i:s'),
					"modified_user_id" => $user_id,
					"modified"         => date('Y-m-d H:i:s'),
					"batch_cd"         => $batch_cd,
			);
			if(!$this->BatchQueue->save($batch_queues_data)) {
				$this->Package->rollback();
				
				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				$this->set('err_msg', $err_msg);
				$this->layout = "publish";
				$this->render("err");
				break;
			}
			
			
			$this->Package->commit();
			
			
			// DB更新時はウィンドウを閉じて親ウィンドウを更新する
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}

		// 入力画面表示
		$this->set('id', $package_id);
		$this->set('approval_note', $approval_note);
		$this->set('package', $package);
		$this->set('err_msg', $err_msg);
		
		$this->layout = "publish";

	}
}
?>