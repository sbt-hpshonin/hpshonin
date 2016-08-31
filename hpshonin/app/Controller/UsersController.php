<?php
App::uses('AppController',	'Controller');
App::uses('EMailController','Controller');
App::uses('MsgConstants',	'Lib/Constants');
App::uses('AppConstants',	'Lib/Constants');
App::uses('PasswordUtil',	'Lib/Utils');

App::uses('Batch',	'Lib');
App::uses('guidChkUtil', 'Lib/Utils');

/**
 * ユーザーコントローラクラス
 * @author smurata
 *
 */
class UsersController extends AppController {

	/**
	 * モデル名
	 * @var unknown_type
	 */
	public $uses = array('User', 'Project', 'ProjectUser', 'Roll', 'Auth','Email','Authentication');
	public $components = array(
//			'Auth' => array(
//					'authenticate' => array(
//							'Form' => array(
//									'userModel' => 'User',
//									'fields' => array('username' => 'email')
//							)
//					)
//			),
			'Session','Cookie',
	);

	/**
	 * (non-PHPdoc)
	 * @see Controller::beforeFilter()
	 */
	function beforeFilter(){
		parent::beforeFilter();
		//認証不要のページの指定
		$this->Auth->allow('login', 'logout');
	}

	/**
	 * 初期表示
	 */
	public function index() {
		$this->setAction('search');

//		$optioon = array(
///				'recursive' => 2,
//				'conditions' => array(
//						'User.is_del' => '0',
//				)
//		);
//		$this->request->data = $this->User->find('all', $optioon);
//		$this->render('search');
    }

	/**
	 * ログイン
	 * @author hsuzuki
	 */
	public function login() {
		while(True){
			$this->autoLayout = false;

			if ($this->request->is('post') == False) {
				// 初期表示
				break;
			}

			@$mail     = $this->data["email"];
			@$password = $this->data["password"];

			//////////////////////////////////////////////
			// パスワードチェック
			//////////////////////////////////////////////
			$option = array(
				'conditions' => array(
						'User.email' => $mail,
						'User.is_del' => "0"
				),
				'recursive' => -1
			);
			$User = $this->User->find('first', $option);
			if(count($User) == 0){
				// 該当ユーザーなし
				$this->set("message","メールアドレスまたはパスワードが間違っています。");
				break;
			}


			if ($User['User']['password'] !== PasswordUtil::encode($password)) {
				// パスワード不一致
				$this->set("message","メールアドレスまたはパスワードが間違っています。");
				break;
			}
			// セッションセット
			$this->Auth->login($User["User"]);
			
			
			//////////////////////////////////////////////
			// GUID発行
			//////////////////////////////////////////////
			$guid = guidChkUtil::getGUID();
			$optioon = array(
					'conditions' => array(
							'user_id'=>$User["User"]["id"],
					),
					'recursive' => -1
			);
			if($this->Authentication->find("count",$optioon) == 0){
				$authentication["user_id"] = $User["User"]["id"];
				$authentication["cookie_id"] = $guid ;
				$authentication["modified"] = date('Y-m-d H:i:s');
				$this->Authentication->save($authentication);
			}
			else{
				$authentication["cookie_id"] = "'".$guid . "'";
				$authentication["modified"] = "'". date('Y-m-d H:i:s') . "'";
				$this->Authentication->updateAll($authentication,$optioon["conditions"]);
			}
			setcookie("hpshonin-id", $guid ,0,AppConstants::GUID_COOKIE_DIR);
			
			
			// ログイン成功→直前URLに戻る
			$this->redirect($this->Auth->redirect() );
			exit;
		}
		// ログイン失敗→ログイン画面を表示
	}

	/**
	 * ログアウト
	 */
	public function logout() {
		
		//////////////////////////////////////////////
		// GUID削除
		//////////////////////////////////////////////
		setcookie("hpshonin-id", "",time() - 60*60*24*365,AppConstants::GUID_COOKIE_DIR);
		$conditions = array('user_id' => $this->Auth->user('id'));
		$this->Authentication->deleteAll($conditions);
		
		
		$this->Auth->logout();
		$this->Session->delete("breadcrumb");
		return $this->redirect("/");
	}

	/**
	 * ユーザー一覧
	 */
	public function search() {

		// 戻るボタン押下時遷移用ＵＲＬセット
		$breadcrumb_ary = $this->Session->read("breadcrumb");
		// $breadcrumb_ary[1] = $this->name ."/". $this->action;
		$breadcrumb_ary[1] = $_SERVER["REQUEST_URI"];
		unset($breadcrumb_ary[2]);
		unset($breadcrumb_ary[3]);
		unset($breadcrumb_ary[4]);
		$this->Session->delete("breadcrumb");
		$this->Session->write("breadcrumb",$breadcrumb_ary );

		@$freeword = $this->request->data['User']['freeword'];

		// 検索文字列のエスケープ
		$search_word_txt = $freeword;
		$search_word_txt = str_replace('\\','\\\\',$search_word_txt); // \のエスケープ
		$search_word_txt = str_replace('%','\\%',$search_word_txt);   // %のエスケープ
		$search_word_txt = "%{$search_word_txt}%";


		$freewordCondition = array(
				'User.username like'		=> $search_word_txt,
				'User.email like'			=> $search_word_txt,
				'User.contact_address like'	=> $search_word_txt
		);
		$rollCd = $this->Auth->user('roll_cd');


		// 権限チェック
		switch($rollCd){
			case AppConstants::ROLL_CD_ADMIN : // 管理者
			case AppConstants::ROLL_CD_PR :    // 広報室
			case AppConstants::ROLL_CD_SITE :  // サイト担当者
				break;
			case AppConstants::ROLL_CD_DEVELOP :// 制作会社
			default:
				return $this->redirect("/home");
		}


		// 管理者 > 広報室 > サイト担当者
		$findConditions["conditions"] = array();

		$option = array( 'recursive' => 0,'order' => array("User.id"), );
		$finder = array( 'User.is_del' => '0', 'or'=> $freewordCondition );

		// 広報室の場合
		if ( $rollCd ==  AppConstants::ROLL_CD_PR ) {
			$finder["User.roll_cd"] = array( AppConstants::ROLL_CD_PR ,AppConstants::ROLL_CD_SITE , AppConstants::ROLL_CD_DEVELOP );

		}
		// サイト担当者の場合
		else if ( $rollCd ==  AppConstants::ROLL_CD_SITE ) {
			$finder["User.roll_cd"] = array( AppConstants::ROLL_CD_SITE , AppConstants::ROLL_CD_DEVELOP );
		}

		$option["conditions"] = $finder;
		$option['recursive'] = 0;
		$users = $this->User->find('all', $option);


		// プロジェクト情報取得
		foreach($users as $key => $user){
			$user_id = $user["User"]["id"];

			$optioon = array(
					'conditions' => array(
							'ProjectUser.user_id'=>$user_id,
							'project.is_del'=>0,
					),
					'recursive' => 0
			);
			$projects = $this->ProjectUser->find('all',$optioon);
			$users[$key]["ProjectUser"] = $projects;
		}
		$this->set("users",$users );

		$roll_cd = $this->Auth->user('roll_cd');

		$this->set("roll_cd",$roll_cd );
	}

	/**
	 * ユーザー詳細
	 * @param $id ユーザーID
	 */
	public function view($id = null) {

		// 戻るボタン押下時遷移用ＵＲＬセット
		if($this->action != "view_short"){
			$breadcrumb_ary = $this->Session->read("breadcrumb");
			//$breadcrumb_ary[2] = "/". $this->name ."/". $this->action ."/". $id ;
			$breadcrumb_ary[4] = $_SERVER["REQUEST_URI"];
			$this->Session->delete("breadcrumb");
			$this->Session->write("breadcrumb",$breadcrumb_ary );
//print_r($breadcrumb_ary);
		}

		// ユーザー情報取得
		$option = array(
				'conditions' => array(
						'User.id' => $id,
						'User.is_del' => 0
				),
				'recursive' => 0
		);
		$this->request->data = $this->User->find('first', $option);
		if ( count($this->request->data) == 0 ) {
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

    	/////////////////////////////////////////////////////
    	// 権限チェック
    	/////////////////////////////////////////////////////
    	$rollCd = $this->Auth->user('roll_cd');
		switch($rollCd){
    		case AppConstants::ROLL_CD_ADMIN:
    		case AppConstants::ROLL_CD_PR:
    			// 管理者・広報は自由に閲覧できる
    			break;
    		case AppConstants::ROLL_CD_DEVELOP:
    			// 製作会社は閲覧権限なし
    			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
    		case AppConstants::ROLL_CD_SITE:
    			// サイト担当者は製作会社とサイト担当者のみ閲覧できる
   				if( $this->request->data["User"]["roll_cd"] == AppConstants::ROLL_CD_ADMIN OR
   				    $this->request->data["User"]["roll_cd"] == AppConstants::ROLL_CD_PR
   				){
   					// 管理者と広報の閲覧はできない
   					throw new NoDataException( MsgConstants::ERROR_NO_DATA );
   				}
    	}
		
		
		//更新可能か判定
		$roll_cd = $this->Auth->user('roll_cd');
		$this->set("roll_cd",$roll_cd );

		// 所属プロジェクト情報取得
		$conditions["ProjectUser.user_id"] = $id;
		$project_user = $this->ProjectUser->find("all", array(
				"order" => array( "Project.id")
				, "conditions" => $conditions ) );

		//$sql
		//	= "SELECT "
		//	. "  project_user.*"
		//	. "FROM "
		//	. "  (SELECT * FROM `project_user` WHERE user_id=9) AS project_user "
		//	. "INNER JOIN "
		//	. "  (SELECT * FROM `project_user` WHERE user_id=10) AS sql2 "
		//	. "ON project_user.project_id = sql2.project_id";

		$this->set( "project_user" ,$project_user );
    }
    
	/**
	 * ユーザー詳細(ポップアップ用)
	 * @param $id ユーザーID
	 */
	public function view_short($id = null) {
		$this->layout = "publish";
		$this->view($id);
	}
    /**
     * ユーザ作成および編集
     * @param $id ユーザーID
     */
    public function edit($id = null) {

    	$this->User->id = $id;
		// ユーザー情報

    	$rollCd = $this->Auth->user('roll_cd');

		// ユーザ情報取得
		if ( $this->User->exists() ) {
	    	$option = array(
	    			'conditions' => array(
    					'User.id' => $id,
	    				'User.is_del' => '0'
	    			),
	    			'recursive' => 0
	    	);
	    	$user = $this->User->find('first', $option);

    		$this->request->data = $user;
	    	// 現状は空の文字列
	    	unset($this->request->data['User']['password']);
	    	unset($this->request->data['User']['password_re']);
		}
		else if( !empty( $id ) ){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		/////////////////////////////////////////////////////
		// 権限チェック
		/////////////////////////////////////////////////////
		switch($rollCd){
			case AppConstants::ROLL_CD_ADMIN:
			case AppConstants::ROLL_CD_PR:
				// 管理者・広報は自由に削除できる
				break;
			case AppConstants::ROLL_CD_DEVELOP:
				// 製作会社は削除権限なし
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			case AppConstants::ROLL_CD_SITE:
				// サイト担当者は製作会社とサイト担当者のみ削除できる
				if(isset($user)==true){
					if( $user["User"]["roll_cd"] == AppConstants::ROLL_CD_ADMIN OR
					$user["User"]["roll_cd"] == AppConstants::ROLL_CD_PR
					){
						// 管理者と広報の削除はできない
						throw new NoDataException( MsgConstants::ERROR_NO_DATA );
					}
				}
		}

        // 所属プロジェクト情報取得
		$projectusers = $this->ProjectUser->getProjectUser($this->User->id);
		//if( empty( $projectusers ) ){
		//	throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		//}


		// アカウント種別(select用にフィールド合わせる)


//		$roll_cd = $this->Auth->user('roll_cd');
//		if ($roll_cd  == AppConstants::ROLL_CD_SITE){
//			$conditon =  array('Roll.roll_cd' => array (
//								AppConstants::ROLL_CD_DEVELOP
//						));
//		}elseif ($roll_cd == AppConstants::ROLL_CD_PR){
//			$conditon = array(
//					'Roll.roll_cd' => array(
//							AppConstants::ROLL_CD_DEVELOP,
//							AppConstants::ROLL_CD_PR,
//							AppConstants::ROLL_CD_SITE
//					)
//			);
//		}else{
//			$conditon = array();
//		}
//    	$rolls = $this->Roll->find('list', array('fields' =>array('Roll.roll_cd', 'Roll.roll_name'),
//			                                 'conditions' => $conditon));
		$rolls = $this->getRoll($this->Auth->user('roll_cd'));

		// 参照可能プロジェクト
		$projects = $this->getProjectAuth();

    	$project_checks = null;
		if ($this->User->exists()) {
	    	foreach ($projects as $project) {
	    		// foreach($user['ProjectUser'] as $pu) {
	    		//	if ($project['Project']['id'] === $pu['project_id']) {
	    		foreach($projectusers as $pu) {
		    		if ($project['Project']['id'] === $pu['ProjectUser']['project_id']) {
		    			$project_checks[] = $project['Project']['id'];
		    			break;
		    		}
	    		}
	    	}
		}


		if(isset($projectusers)){
			$all_belong_cnt    = count ($projectusers);         // 対象ユーザの所属プロジェクト
		}
		else{
			$all_belong_cnt    = 0;
		}
		$common_belong_cnt = count ($project_checks );             // 対象ユーザとアクセスユーザの共通所属プロジェクト
		$shadow_belong_cnt = $all_belong_cnt - $common_belong_cnt; // 対象ユーザのみが所属するプロジェクト
    	$this->set('projects', $projects);
    	$this->set('rolls', $rolls);
	    $this->set('project_checks', $project_checks);
	    $this->set('shadow_belong_cnt', $shadow_belong_cnt);
	    $this->set('all_belong_cnt',$all_belong_cnt);

	    // 楽観ロック用のバージョンをセッションに追加
	    if (empty($this->request->data['User']['modified'])){
	    	$modified="";
	    }else{
	    	$modified=$this->request->data['User']['modified'];
	    }
	    $this->Session->write("Versioned".$this->User->alias, $modified);
		$this->set("auth_user_id",$this->Auth->user('id'));
 		$this->set("auth_user_roll_cd",$this->Auth->user('roll_cd'));
    }

    /**
     * 参照可能なプロジェクトを取得
     * @return unknown
     */
    private function getProjectAuth() {
    	$projects = array();

    	if ($this->Auth->user('roll_cd') === '2') {
    		$sql = "select * from projects Project ".
    				"join project_user ProjectUser on Project.id = ProjectUser.project_id ".
    				"where Project.is_del = '0' and ProjectUser.user_id = ?";
    		$projects= $this->Project->query($sql, array($this->Auth->user('id')));
    	} else if ($this->Auth->user('roll_cd') === '0' ||
    		$this->Auth->user('roll_cd') === '3') {
    		$option = array(
    				'conditions' => array(
    						'Project.is_del' => AppConstants::FLAG_OFF
    				),
    				'recursive' => -1
    		);
    		$projects = $this->Project->find('all', $option);
    	}
    	return $projects;
    }


    /**
     * 更新
     * 更新後は詳細画面に遷移
     */
    public function update() {

		// GUIDチェック
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}


    	$this->set("auth_user_id",$this->Auth->user('id'));
    	$this->set("auth_user_roll_cd",$this->Auth->user('roll_cd'));
    	$err = false;
    	$errcnt =0;
    	$errmsg=array();
    	if (!empty($this->request->data['User']['id'])) {
    		$this->User->id = $this->request->data['User']['id'];

    		// 楽観ロックチェック
    		if ( $this->Session->check( "Versioned".$this->User->alias ) ) {
    			// 更新時のデータが取得でなかった場合は編集中にほかのデータが更新ていると判断する
    			$find_conditions = array( "conditions" => array( "id"=> $this->User->id, "modified" => $this->Session->read("Versioned".$this->User->alias) ), 'recursive' => -1  );
    			$data_count = $this->User->find("count", $find_conditions );

    			if ( $data_count == 0 ) {
    				$this->Session->setFlash(MsgConstants::ERROR_OPTIMISTIC_LOCK);

    				// アカウント種別(select用にフィールド合わせる)
    				$rolls = $this->getRoll($this->Auth->user('roll_cd'));

    				// 参照可能プロジェクト
    				$projects = $this->getProjectAuth();

    				$this->set('projects', $projects);
    				$this->set('rolls', $rolls);
    				if(isset($this->request->data['project_check'])){
    					$this->set('project_checks', $this->request->data['project_check']);
    				}
    				else{
    					$this->set('project_checks', array());
    				}

    				return $this->render('edit');
    			}
    		}
    	}

    	// メールアドレス・パスワード編集権限チェック
    	while(true){
    		if (isset($this->request->data['User']['id']) != true) {
    			// 新規時はOK
    			break;
    		}
    		if($this->Auth->user('roll_cd') == AppConstants::ROLL_CD_ADMIN or
    		   $this->Auth->user('roll_cd') == AppConstants::ROLL_CD_PR
    		){
    			// 管理者・広報はOK
    			break;
    		}
    		if($this->Auth->user('id') == $this->request->data['User']['id'] ){
    			// 自分自身の編集はOK
    			break;
    		}
    		if($this->request->data['User']['roll_cd'] == AppConstants::ROLL_CD_DEVELOP ){
    			// 製作会社の編集はOK
    			break;
    		}
    		
    		// サイト担当同士のメールアドレス・パスワード編集は不可
    		$user = $this->User->findById($this->User->id);
    		$this->request->data['User']['email'] = $user['User']['email'];
    		$this->request->data['User']['password']="";
    		$this->request->data['User']['password_re']="";
    		break;
    	}
    	
    	
    	// エラーチェック

		$this->User->set($this->request->data);
    	if(!$this->User->validates()){
    		$err =true;
		}

		// パスワード長さチェック
		if($this->request->data['User']['password'] != ""){
			if( strlen($this->request->data['User']['password']) < 8 OR
			    strlen($this->request->data['User']['password']) >20){
	    		$err =true;
				$errcnt++;
				$errmsg[$errcnt]= 'パスワードは8文字以上20文字以下です。';
			}
		}


		//２つのパスワードを比較
		if ($this->request->data['User']['password'] != $this->request->data['User']['password_re']){
			$err =true;
			$errcnt++;
			$errmsg[$errcnt]="パスワードが一致しません。";
		}
		//同一メールアドレス判定
		if (isset($this->request->data['User']['id'])){
			$id=$this->request->data['User']['id'];
		}else{
			$id ="";
		}

		// 既存メールアドレスチェック
		$mail=$this->request->data['User']['email'];
    	$option = array(
    			'conditions' => array('User.is_del' => '0','User.email' => $mail),
    			'recursive' => -1
    	);
		$users = $this->User->find('first', $option);
		if (isset($users['User'])){
			if ($users['User']['id']!=$id){
				$err =true;
				$errcnt++;
				$errmsg[$errcnt]="指定されたメールアドレスはすでに存在しています。";
			}
		}


    	$option = array(
    			'conditions' => array('User.is_del' => '0','User.id' => $id),
    			'recursive' => -1
    	);
		$users = $this->User->find('first', $option);


		if (isset($users['User'])){
			if($users["User"]["roll_cd"] == 0
				AND $this->request->data['User']['roll_cd'] != 0){
				// 更新対象が管理者で、かつ管理者から変更しようとする場合

				// 管理者数をカウント
				$find_conditions = array(
						"conditions" => array(
								"is_del" => 0,
								"roll_cd" => 0,
						),
						"recursive" => -1
				);
				$data_count = $this->User->find("count", $find_conditions );
				if($data_count <= 1){
					// 管理者が最後の１人→権限変更不可
					$err =true;
					$errcnt++;
					$errmsg[$errcnt]="管理者は１人以上必要です。";
				}
			}
		}

		//die(var_dump($this->request->data['project_check']));
		if($err==false) {

	    	// 追加処理
	    	if (!$this->User->exists()) {
	    		if ($this->request->is('post')) {

	    			// ユーザ登録通知メール送信
	    			$pass = $this->request->data['User']['password'];
					$to = $this->request->data['User']['email'];
					$ctrl = new EMailController();
					$ctrl->send_add_user(array($to),$to,$pass);

	    			// 新規登録
	    			$this->User->create();
	    			//パスワードのハッシュ値変換
	    			$this->request->data['User']['password'] = PasswordUtil::encode($this->request->data['User']['password']);
	    			//登録者・更新者をセット
	    			$this->request->data['User']['modified_user_id'] = $this->Auth->user('id');
	    			$this->request->data['User']['created_user_id'] = $this->Auth->user('id');
	    			$this->User->begin();
	    			if ($this->User->save($this->request->data) &&
	    				// プロジェクトユーザーテーブル追加
	    				$this->__rebuildProjectUser($this->request->data) &&
	    				//バッチキューテーブルへ登録
	    				$this->__insertBatchQueue( Batch::BATCH_CD_MT_USER_CREATE , $this->User->id )) {

	    				$this->User->commit();

	    				$this->Session->setFlash(MsgConstants::SUCCESS_USER_ADD);
	    				$this->redirect('/users/view/'.$this->User->id);
	    			} else {
	    				$this->User->rollback();
	    				$this->Session->setFlash(MsgConstants::ERROR_USER_ADD);
	    				$err =true;
	    			}
	    		}
	    	} else {	// 更新処理
	    		if ($this->request->is('post') || $this->request->is('put')) {
	    			// パスワードの入力がない場合、DBの値を入れて保存する。
	    			if (!empty($this->request->data['User']['password'])) {
	    				$this->request->data['User']['password'] = PasswordUtil::encode($this->request->data['User']['password']);
	    			} else {
	    				$user = $this->User->findById($this->User->id);
	    				$this->request->data['User']['password'] = $user['User']['password'];
	    					    			}
	    			//更新者IDセット
	    			$this->request->data['User']['modified_user_id'] = $this->Auth->user('id');
	    			$this->User->begin();
	    			if (
	    				// ユーザーテーブル更新
	    				$this->User->save($this->request->data) &&
	    				// プロジェクトユーザーテーブル更新
	    				$this->__rebuildProjectUser($this->request->data) &&
	    				//バッチキューテーブルへ登録
	    				$this->__insertBatchQueue( Batch::BATCH_CD_MT_USER_UPDATE, $this->User->id )
	    			) {

	    				$this->User->commit();

	    				$this->Session->setFlash(MsgConstants::SUCCESS_USER_UPDATE);
	    				// 自分の編集をした場合、認証情報をアップデート
	    				if ($this->Auth->user('id') === $this->User->id) {
	    					$user = $this->User->findById($this->User->id);
	    					$this->Auth->login($user['User']);
	    				}
	    				$this->redirect('/users/view/'.$this->User->id);
	    			} else {
	    				//unset($this->request->data['User']['password']);
	    				//unset($this->request->data['User']['password_re']);
	    				$this->User->rollback();
	    				$this->Session->setFlash(MsgConstants::ERROR_USER_UPDATE);
	    				$err =true;
	    			}
	    		}
	    	}
	    	//バッチキュー登録
	    	//履歴テーブル作成


//	    	$user_id = $this->Auth->user('id');
//
//	    	$this->BatchQueue->set( "created_user_id" , $user_id  );
//	    	$this->BatchQueue->set( "modified_user_id" , $user_id  );
//	    	$this->BatchQueue->set( "batch_cd" , $bach_cd );
//	    	$this->BatchQueue->set( "user_id" ,$this->User->id );
//	    	//die(var_dump($this->BatchQueue));
//
//	    	$this->BatchQueue->save();


		}

    	if ($err) {
    		// 所属プロジェクト情報取得
    		$projectusers = $this->ProjectUser->getProjectUser($this->User->id);

    		//die(var_dump($this->request->data['project_check']));

//	    	// アカウント種別(select用にフィールド合わせる)
//			$roll_cd = $this->Auth->user('roll_cd');
//			if ($roll_cd  =='2'){
//			//DIE("TEST1");
//				$conditon =  array('Roll.roll_cd ' => '2');
//			}elseif ($roll_cd=="3"){
//				$conditon = array('Roll.roll_cd >' => '0','Roll.roll_cd <' => '4');
//			}else{
//				$conditon = array('Roll.roll_cd <' => '4');
//			}
//			//die("cd=".$roll_cd." cond=".var_dump($conditon));
//
//		$rolls = $this->Roll->find('list', array('fields' =>array('Roll.roll_cd', 'Roll.roll_name'),
//			                                 'conditions' => $conditon));

    		$rolls = $this->getRoll($this->Auth->user('roll_cd'));
    		// 参照可能プロジェクト
	    	$projects = $this->getProjectAuth();

			$project_checks = null;
			if ($this->User->exists()) {
				foreach ($projects as $project) {
					// foreach($user['ProjectUser'] as $pu) {
					//	if ($project['Project']['id'] === $pu['project_id']) {
					foreach($projectusers as $pu) {
						if ($project['Project']['id'] === $pu['ProjectUser']['project_id']) {
							$project_checks[] = $project['Project']['id'];
							break;
						}
					}
				}
			}

			if(isset($projectusers)){
				$all_belong_cnt    = count ($projectusers);         // 対象ユーザの所属プロジェクト
			}
			else{
				$all_belong_cnt    = 0;
			}

	    	//$common_belong_cnt = count ( empty($this->request->data['project_check'] )? 0  : empty($this->request->data['project_check'] )  );             // 対象ユーザとアクセスユーザの共通所属プロジェクト
	    	$common_belong_cnt = count ($project_checks );             // 対象ユーザとアクセスユーザの共通所属プロジェクト
	    	$shadow_belong_cnt = $all_belong_cnt - $common_belong_cnt; // 対象ユーザのみが所属するプロジェクト


	    	$this->set('projects', $projects);
	    	$this->set('rolls', $rolls);
	    	//die(var_dump($this->request));
	    	$this->set('shadow_belong_cnt', $shadow_belong_cnt);
	    	$this->set('all_belong_cnt',$all_belong_cnt);

	    	if (empty($this->request->data['project_check'])){
	    		$this->set('project_checks', null);
	    	}else{
	    		$this->set('project_checks', $this->request->data['project_check']);
	    	}

	    	$this->set('errmsg', $errmsg);
	    	$this->set('errcnt', $errcnt);



	    	//die(var_dump($errmsg));
	    	//die("CNT=".$errcnt."");
	    	$this->render('edit');
    	}
	}

	/**
	 * ユーザーロールによって参照できるロールをselect形式で取得
	 * @param unknown $roll_cd
	 * @return unknown
	 */
	private function getRoll($roll_cd) {
		if ($roll_cd  == AppConstants::ROLL_CD_SITE){
			$conditon =  array('Roll.roll_cd' => array (
					AppConstants::ROLL_CD_DEVELOP,
					AppConstants::ROLL_CD_SITE
			));
		}elseif ($roll_cd == AppConstants::ROLL_CD_PR){
			$conditon = array(
					'Roll.roll_cd' => array(
							AppConstants::ROLL_CD_DEVELOP,
							AppConstants::ROLL_CD_PR,
							AppConstants::ROLL_CD_SITE
					)
			);
		}else{
			$conditon = array();
		}
		$rolls = $this->Roll->find('list', array('fields' =>array('Roll.roll_cd', 'Roll.roll_name'),
				'conditions' => $conditon ,'recursive' => -1));

		return $rolls;
	}

	/**
	 * プロジェクト紐付け情報を再構築する
	 * @param  $data 画面入力値
	 * @return true:成功/false:失敗
	 */
	private function __rebuildProjectUser($data) {

		$projectUsers = null;
		if (!empty($data['User']['id'])){
			// 編集処理 //////////////////////////////////////////////////////////////

			if ($this->Auth->user('roll_cd') == AppConstants::ROLL_CD_SITE ) {
				// 対象ユーザとログインユーザ共に所属するプロジェクトの取得
				$sql = "SELECT distinct id,project_id FROM project_user ProjectUser ".
						"WHERE EXISTS (SELECT * FROM project_user pu2 WHERE ProjectUser.project_id = pu2.project_id and pu2.user_id = ?) ".
						"and ProjectUser.user_id = ? order by project_id ";
				$projectUsers = $this->ProjectUser->query($sql, array($this->Auth->user('id'), $data['User']['id']));
			} else {
				// 対象ユーザが所属するプロジェクトの取得
				$sql = "SELECT distinct id,project_id FROM project_user ProjectUser ".
						"where ProjectUser.user_id = ? order by project_id ";
				$projectUsers = $this->ProjectUser->query($sql, array($data['User']['id']));
			}

			// 登録されているプロジェクトID配列生成
			$project_id_db_ary = array();
			foreach ($projectUsers as $pu) {
				$project_id_db_ary[] = $pu['ProjectUser']['project_id'];
			}

			// 入力されたプロジェクトID配列生成
			if(isset($data['project_check'])){
				$project_id_input_ary = $data['project_check'];
			}
			else{
				$project_id_input_ary = array();
			}

			// プロジェクトからはずす処理
			foreach (array_diff($project_id_db_ary,$project_id_input_ary) as $key => $project_id) {
				// プロジェクト検索
				$optioon = array(
						'ProjectUser.user_id' => $data['User']['id'],
						'ProjectUser.project_id' => $project_id
				);
				if( $this->ProjectUser->deleteAll($optioon) == false ){
					// DB Error
					return false;
				}
			}

			// プロジェクトに追加する処理
			foreach (array_diff($project_id_input_ary,$project_id_db_ary) as $key => $project_id) {
				// プロジェクト検索
				$this->ProjectUser->create();
				$this->ProjectUser->set('created_user_id', $this->Auth->user('id'));
				$this->ProjectUser->set('modified_user_id', $this->Auth->user('id'));
				$this->ProjectUser->set('user_id', $this->User->id);
				$this->ProjectUser->set('project_id', $project_id);
				if ($this->ProjectUser->save() == false) {
					// DB Error
					return false;
				}
			}
		}
		else{
			if (isset($data['project_check'])){

				// 新規登録処理 //////////////////////////////////////////////////////////////
				foreach ($data['project_check'] as $check) {
					$this->ProjectUser->create();
					$this->ProjectUser->set('created_user_id', $this->Auth->user('id'));
					$this->ProjectUser->set('modified_user_id', $this->Auth->user('id'));
					$this->ProjectUser->set('user_id', $this->User->id);
					$this->ProjectUser->set('project_id', $check);
					if (!$this->ProjectUser->save()) {
						// DB Error
						return false;
					}
				}
			}
		}

		// debug($this->ProjectUser->getDataSource()->getLog());
		return true;
	}

    /**
     * 削除
     */
	public function delete() {

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}


		/////////////////////////////////////////////////////
		// パラメタチェック
		/////////////////////////////////////////////////////
		if(isset($this->request->data['User']['id']) == False){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$this->User->id = $this->request->data['User']['id'];
		$roll_cd = $this->Auth->user('roll_cd');


		/////////////////////////////////////////////////////
		// 削除対象ユーザー検索
		/////////////////////////////////////////////////////
		$find_conditions = array(
				"conditions" => array(
						"id" => $this->User->id,
				),
				"recursive" => -1
		);
		$user = $this->User->find("first", $find_conditions );
		if (empty($user)) {
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}


		/////////////////////////////////////////////////////
		// 権限チェック
		/////////////////////////////////////////////////////
		switch($roll_cd){
			case AppConstants::ROLL_CD_ADMIN:
			case AppConstants::ROLL_CD_PR:
				// 管理者・広報は自由に削除できる
				break;
			case AppConstants::ROLL_CD_DEVELOP:
				// 製作会社は削除権限なし
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			case AppConstants::ROLL_CD_SITE:
				// サイト担当者は製作会社とサイト担当者のみ削除できる
				if( $user["User"]["roll_cd"] == AppConstants::ROLL_CD_ADMIN OR
				    $user["User"]["roll_cd"] == AppConstants::ROLL_CD_PR
				){
					// 管理者と広報の削除はできない
					throw new NoDataException( MsgConstants::ERROR_NO_DATA );
				}
		}


		/////////////////////////////////////////////////////
		// 管理者数チェック
		/////////////////////////////////////////////////////
		if($user["User"]["roll_cd"] == AppConstants::ROLL_CD_ADMIN ){
			// 削除対象が管理者の場合

			// 管理者数をカウント
			$find_conditions = array(
					"conditions" => array(
						"is_del" => 0,
						"roll_cd" => AppConstants::ROLL_CD_ADMIN,
					),
					"recursive" => -1
			);
			$data_count = $this->User->find("count", $find_conditions );
			if($data_count <= 1){
				// 管理者が最後の１人→削除不可
				$this->Session->setFlash('管理者は１人以上必要です。');
				return $this->redirect('/users/edit/'. $this->User->id);
			}
		}


		/////////////////////////////////////////////////////
		// プロジェクトチェック
		/////////////////////////////////////////////////////
		switch($roll_cd){
			case AppConstants::ROLL_CD_ADMIN:
			case AppConstants::ROLL_CD_PR:
				// 管理者・広報は自由に削除できる
				break;
			case AppConstants::ROLL_CD_DEVELOP:
				// 製作会社は削除権限なし
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			case AppConstants::ROLL_CD_SITE:

				// 対象ユーザの所属プロジェクト情報取得
				$projectusers = $this->ProjectUser->getProjectUser($this->User->id);
				foreach ($projectusers as $projectuser) {
					$target_project[] = $projectuser["ProjectUser"]["project_id"];
				}

				// ログインユーザの参照可能プロジェクト取得
				$projects = $this->getProjectAuth();
				$project_checks = null;
				foreach ($projects as $project) {
					$own_project[] = $project["ProjectUser"]["project_id"];
				}


				if(count(array_diff($target_project,$own_project)) > 0){
					$this->Session->setFlash('ログインユーザーが所属していないプロジェクトに所属しているため、削除できません。');
					return $this->redirect('/users/edit/'. $this->User->id);
				}
				break;
		}


		/////////////////////////////////////////////////////
		// ＤＢ登録処理
		/////////////////////////////////////////////////////
		$user['User']['is_del'] = '1';
		$user['User']['modified'] = date('Y-m-d H:i:s');
		$user['User']['modified_user_id'] = $this->Auth->user('id');

		$this->User->begin();

		if ($this->User->save($user) &&
			$this->__insertBatchQueue( Batch::BATCH_CD_MT_USER_DELETE , $this->User->id )) {

   			// ユーザー削除通知メール送信
			$to = $user['User']['email'];
			$ctrl = new EMailController();
			$ctrl->send_delete_user(array($to));

			$this->User->commit();

			$this->Session->setFlash('ユーザーを削除しました。');
		}else{
			$this->User->rollback();
			$this->Session->setFlash('ユーザーの削除に失敗しました。');
		}
		// debug($this->User->getDataSource()->getLog());exit;

		if($this->User->id == $this->Auth->user('id') ){
			// 自分で自分を消した場合→強制ログアウト
			$this->redirect('/Users/logout');
		}
		else{
			// 通常→ユーザー一覧
			$this->redirect('/users/search/');
		}
	}


    /**
     * パスワード変更
     * @author hsuzuki
     */
	public function password() {

		while (true){

			if( $this->request->is('post') == false) {
				break;
			}

			// GUIDチェック
			if( guidChkUtil::chkGUID() == false ){
				throw new NoDataException( MsgConstants::ERROR_GUID );
			}
			
			// バリデーションチェック
			$this->User->set($this->request->data);
			$this->User->validate = $this->User->validate2;
			if($this->User->validates()==false){
				break;
			}

			// パスワード長さチェック
			if( strlen($this->request->data['User']['password_new']) < 8 OR
				strlen($this->request->data['User']['password_new']) >20){
				$this->User->invalidate("password_new", 'パスワードは8文字以上20文字以下です。');
				break;
			}

			// パスワードチェック
			if( $this->isSamePassword($this->Auth->user('id'), $this->request->data['User']['password_old']) != true ){
				$this->User->invalidate("password_old", 'パスワードが違います。');
				break;
			}
			
			
			$this->User->begin();

			// ユーザーテーブル更新
			$user['User']['id'] = $this->Auth->user('id');
			$user['User']['password'] = PasswordUtil::encode($this->request->data['User']['password_new']);
			$user['User']['modified_user_id'] = $this->Auth->user('id');
			$user['User']['modified'] = date('Y-m-d H:i:s');
			if ($this->User->save($user) != true ){
				$this->User->rollback();
				$this->Session->setFlash('パスワード変更に失敗しました。');
				break;
			}
			
			//バッチキューテーブルへ登録
			if( $this->__insertBatchQueue( Batch::BATCH_CD_MT_USER_UPDATE, $this->Auth->user('id')) != true ){
				$this->User->rollback();
				$this->Session->setFlash('パスワード変更に失敗しました。');
				break;
			}
			
			// セッションセット
			$optioon = array(
					'conditions' => array(
							'User.is_del'=> AppConstants::FLAG_OFF ,
							'User.id' => $this->Auth->user('id')
					),
					'recursive' => -1
			);
			$user = $this->User->find('first',$optioon);
			if(empty($user) == true ){
				$this->User->rollback();
				$this->Session->setFlash('パスワード変更に失敗しました。');
				break;
			}

			$this->Auth->login($user['User']);
			$this->User->commit();
			$this->Session->setFlash('新しいパスワードに変更しました。');
			
			break;
		}
		$this->User->validate = $this->User->validate1; // エラーポップアップを出さないため
    	// 入力値を初期化
    	$this->request->data['User']['password_old'] = '';
    	$this->request->data['User']['password_new'] = '';
    	$this->request->data['User']['password_new_re'] = '';
	}

	/**
	 * パスワード同一チェック
	 * @param $user_id	ユーザ
	 * @param $pass		パスワード
	 * @return boolean	true:一致/false:不一致
	 */
	private function isSamePassword($user_id, $pass) {
		// $user = $this->User->findById($user_id,'password');
		
		$optioon = array(
				'fields' => 'password',
				'conditions' => array(
						'User.is_del'=> AppConstants::FLAG_OFF ,
						'User.id' => $user_id
				),
				'recursive' => -1
		);
		$user = $this->User->find('first',$optioon);

		if(!empty($user) ) {
			if ($user['User']['password'] === PasswordUtil::encode($pass)) {
				return true;
			}
		}
		return false;
	}
	/**
	 * バッチキューテーブルへ登録を行います。
	 * トランザクションは関数の外で行うこと
	 *
	 * @param unknown $aBatchCd
	 * @param unknown $aPackegeId
	 */
	private function __insertBatchQueue( $aBatchCd, $aUserId ){

		$user_id = $this->Auth->user('id');

		//die(var_dump($this->User->BatchQueue));

		$this->User->BatchQueue->set( "created_user_id" , $user_id  );
		$this->User->BatchQueue->set( "modified_user_id" , $user_id  );
		$this->User->BatchQueue->set( "batch_cd" , $aBatchCd  );
		$this->User->BatchQueue->set( "user_id" , $aUserId  );
		return $this->User->BatchQueue->save();
	}

}

