<?php
App::uses('Status', 'Lib');
App::uses('Batch', 'Lib');

App::uses('AppController', 'Controller');
App::uses('AppConstants',	'Lib/Constants');
App::uses('MsgConstants',	'Lib/Constants');

App::import('Model', 'Package');
App::import('Model', 'BatchQueue');
App::uses('AuthorityChkComponent', 'Controller/Component');
App::uses('guidChkUtil', 'Lib/Utils');


/**
 * 公開コントローラクラス
 * @author smurata
 *
*/
class PublishController extends AppController {

	public $uses = array('Package','BatchQueues');
	public $components = array("Cookie");

	public function index() {
		// 404
	}

	function beforeFilter(){
		parent::beforeFilter();
		//認証不要のページの指定
		// （別ウィンドウのため、ログインページには飛ばさずに個別対応）
		$this->Auth->allow(
			'upset','upset_chk0','upset_chk1','upset_chk2','upset_chk2_5','upset_chk3','upset_chk5','upset_chk6','upset_chk7','upset_end',
			'upset_del0','upset_del1','upset_del2','upset_del3'
		);
	}


	/**
	 * 公開設定画面
	 * @author hsuzuki
	 * @param $project_id プロジェクトID
	 * @param $package_id パッケージID
	*/
	public function upset($project_id="",$package_id="") {

		// パラメタチェック
		if($project_id=="" or $package_id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}


		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}


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
		
		
		$package = new Package();
		$optioon = array(
				'conditions' => array(
					'Package.id' => $package_id
				),
				'recursive' => -1
		);
		$packages = $package->find('first', $optioon);
		if(count($packages) == 0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		//該当パッケージが論理削除された場合エラー
		if ($packages['Package']['is_del'] == AppConstants::FLAG_ON){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}

		//現在のステータスが公開できるかチェック
		if ( $packages['Package']['status_cd'] != Status::STATUS_CD_APPROVAL_OK  AND
		     $packages['Package']['status_cd'] != Status::STATUS_CD_RELEASE_RESERVE AND
		     $packages['Package']['status_cd'] != Status::STATUS_CD_RELEASE_REJECT AND
		     $packages['Package']['status_cd'] != Status::STATUS_CD_RELEASE_NOW AND
		     $packages['Package']['status_cd'] != Status::STATUS_CD_RELEASE_ERROR
		){
			// エラーメッセージセット
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_UPSET);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}

		// 楽観ロック用のバージョンをセッションに追加
		$this->Session->write("Versioned".$this->Package->alias, $packages['Package']['modified']);

		$this->set('package', $packages);
		$this->set('project_id', $project_id);
		$this->set('package_id', $package_id);
		$this->set('chancel_reload',false);
		$this->layout = "publish";
	}

	/**
	 * 公開設定チェック前処理・fancyboxから値を取得してupset_chk1に渡す。
	 * @param $project_id プロジェクトID
	 * @param $package_id パッケージID
	 * @author hsuzuki
	*/
	public function upset_chk0($project_id='',$package_id='') {

		// パラメタチェック
		if($project_id=="" or $package_id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

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
		
		$this->set("project_id",$project_id);
		$this->set("package_id",$package_id);
		$this->layout = "publish";
	}

	/**
	 * 公開設定チェック処理１
	 *     バリデーションチェック
	 *     バッチキューチェック
	 *     公開期限切れチェック
	 *     公開予定日チェック
	 * @param $project_id プロジェクトID
	 * @param $package_id パッケージID
	 * @author hsuzuki
	*/
	public function upset_chk1($project_id='',$package_id='') {

		/////////////////////////////////////////////////////
		// パラメタチェック
		/////////////////////////////////////////////////////
		if($project_id=="" or $package_id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_GUID );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		
		/////////////////////////////////////////////////////
		// 権限チェック
		/////////////////////////////////////////////////////
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authPackage($package_id) == False ){
		
			// 必要権限がない場合にはエラー表示
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_AUTH);
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		

		/////////////////////////////////////////////////////
		// バリデーションチェック
		/////////////////////////////////////////////////////
		$input = $this->data;
		@$target_date = $this->data["target_date"];
		@$target_flg = $this->data["target_flg"];
		if($target_flg == "2"){
			$target_date = date("Y/m/d H:i:s");
			$input["target_date"] = $target_date;
		}

		//print_r($this->data);
		$this->Package->validate = array(
				'target_flg' => array(
						'rule' => array('inList',array('1','2')),
						'message' => '公開方法は必須です。'
				),
				'target_date' => array(
						'rule' => array('datetime','ymd'),
						'required'=>true,
						'message' => '公開予定日を正しく入力してください。'
				),
		);
		$this->Package->set($input);
		if($this->Package->validates() == False){
			if(isset($this->Package->validationErrors)){
				$this->set('err_msg', $this->Package->validationErrors);
				$this->layout = "publish";
				$this->render("warning");
				return;
			}
		}
		
		/////////////////////////////////////////////////////
		// 現在日付チェック
		/////////////////////////////////////////////////////
		if(strtotime($target_date) < time() - 60 * 2 ){
			$err_msg[] = array("Versioned" => "過去の日時は指定できません。" );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		
		/////////////////////////////////////////////////////
		// バッチ起動情報チェック
		/////////////////////////////////////////////////////
		$batch_queues = new BatchQueue();
		$optioon = array(
				'conditions' => array(
						'BatchQueue.package_id' => $package_id,
						'BatchQueue.batch_cd' => Batch::BATCH_CD_RELEASE, /**  公開 */
						'BatchQueue.result_cd' => array(
								AppConstants::RESULT_CD_EXECUTION,
								AppConstants::RESULT_CD_SUCCESS),
						'BatchQueue.is_del' => '0'),
				'recursive' => -1
		);
		$batch_queue = $batch_queues->find('all', $optioon);
		if(count($batch_queue) != 0 ){
			// 該当するバッチ起動情報が存在し、結果CDが'0':未実施以外の場合→確認メッセージ
			$err_msg[] = array("Versioned" => "公開が実行中、または完了しているため、公開設定できません。" );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}


		/////////////////////////////////////////////////////
		// 公開期限切れチェック
		/////////////////////////////////////////////////////
		$package = new Package();
		$optioon = array(
				'conditions' => array(
						'package.id' => $package_id,
						'package.status_cd' => Status::STATUS_CD_RELEASE_EXPIRATION ,
						'package.is_del' => '0'
				),
				'recursive' => -1
		);
		$packages = $package->find('all', $optioon);
		//print_r($batch_queue);
		if(count($packages) != 0 ){
			// ⇒DBの該当するパッケージ情報.ステータスCDが'95'公開期限切れの場合
			$this->layout = "publish";
			$err_msg[] = array("Versioned" => "有効期限切れです。" );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		
		/////////////////////////////////////////////////////
		// 公開予定日チェック
		/////////////////////////////////////////////////////
		$db_date = date('Y-m-d', strtotime($target_date));
		$package = new Package();
		$optioon = array(
				'conditions' => array(
						'package.id' => $package_id,
						'package.is_del' => '0',
						'public_due_date >' => $db_date
				),
				'recursive' => -1
		);
		$packages = $package->find('first', $optioon);
		if(count($packages) != 0 ){
			// 公開日時が公開予定日より前の場合
			$this->layout = "publish";
			$datetime = new DateTime($packages['Package']['public_due_date']);
			$err_msg[] = array("Versioned" => "公開予定日（". $datetime->format('Y/m/d') . "）より前に公開できません。" );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}

		
		/////////////////////////////////////////////////////
		// 公開日時・公開予定日比較チェック
		/////////////////////////////////////////////////////
		$package = new Package();
		$optioon = array(
				'conditions' => array(
						'package.id' => $package_id,
						'package.is_del' => '0',
						'public_due_date !=' => $db_date
				),
				'recursive' => -1
		);
		$packages = $package->find('first', $optioon);


		
		$this->Session->write("project_id",$project_id);
		$this->Session->write("package_id",$package_id);
		$this->Session->write("target_date",$target_date);
		$this->Session->write("target_flg",$target_flg);
		
		//$this->set('public_due_date', $packages['Package']['public_due_date']);	// 公開予定日
		$this->set('publish_date', $db_date);								// 公開日

		if(count($packages) != 0 ){
			// 公開日時が公開予定日と一致していない場合
			$this->set('public_due_date', $packages['Package']['public_due_date']);	// 公開予定日
			$this->layout = "publish";
			$this->render("upset_chk4");
		}
		else{
			// 公開日時が公開予定日と一致している場合
			$this->set('public_due_date', $db_date);	// 公開予定日
			$this->setAction("upset_chk5");
		}
	}

	/**
	 * 公開設定チェック処理５
	 *     公開日同一パッケージ閲覧
	 * @author hsuzuki
	 */
	public function upset_chk5() {
		// ⇒公開日が同一であるパッケージが存在する場合

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_GUID );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		
		$project_id = $this->Session->read("project_id");
		$package_id = $this->Session->read("package_id");
		$target_date = $this->Session->read("target_date");
		$target_flg = $this->Session->read("target_flg");
		$db_date = date('Y-m-d', strtotime($target_date));
		$db_date2 = date('Y-m-d', strtotime("{$db_date} +1 days"));
		
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
		
		$package = new Package();
		$optioon = array(
				'conditions' => array(
						'Project.id' => $project_id,     // 同一プロジェクトかつ
						'Package.id !=' => $package_id,  // 自身以外かつ
						'Package.public_reservation_datetime between ? and ?' => array($db_date,$db_date2) ,// 公開予約日が同じもの
						'Package.is_del' =>  AppConstants::FLAG_OFF,
						'Package.status_cd !=' =>  Status::STATUS_CD_RELEASE_REJECT,
				),
				'recursive' => 1
		);
		$packages = $package->find('all', $optioon);
		if(count($packages) != 0 ){
			// 公開予約日が一致するパッケージが存在する (NG)
			$this->set('packages', $packages);
			$this->set('target_date',$target_date);

			$this->layout = "publish";
		}
		else{
			// 公開予約日が一致するパッケージが存在しない (OK)
			$this->setAction("upset_chk7");
		}
	}

	/**
	 * 公開設定最終確認
	*/
	public function upset_chk7() {

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_GUID );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("warning");
			return;
		}
		
		$package_id = $this->Session->read("package_id");
		
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
		
		$this->layout = "publish";
	}


	/**
	 * 公開設定登録処理
	 * @authr hszuki
	*/
	public function upset_end() {

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_GUID );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}
		
		$user_id = $this->Auth->user('id');
		$project_id = $this->Session->read("project_id");
		$package_id = $this->Session->read("package_id");
		$target_date = $this->Session->read("target_date");
		$target_flg = $this->Session->read("target_flg");
		$db_date = date('Y-m-d H:i:s', strtotime($target_date));
		
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
		
		while(True){
			// 現在のステータスが、公開予約・即時公開に変更可能か判定
			$package = new Package();
			$optioon = array(
					'conditions' => array(
							'package.id' => $package_id,
					),
					'recursive' => -1
			);
			$packages = $package->find('first', $optioon);
			if(count($packages) !== 1){
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			}


			//該当パッケージが論理削除された場合エラー
			if($packages['Package']['is_del'] == AppConstants::FLAG_ON){
				//$this->autoRender = false;

				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
				break;
			}


			//現在のステータスが公開できるかチェック
			$date= $packages['Package']['public_reservation_datetime'];
			$wtimestmp=(strtotime($date));
			if (($packages['Package']['status_cd'] ==Status::STATUS_CD_APPROVAL_OK)
			  or  ($packages['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_NOW )
			  or  ($packages['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_REJECT)
			  or  ($packages['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_ERROR)
			  or (($packages['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_RESERVE)
			  		and ($wtimestmp > time()) ) ){
			}else{
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_UPSET);
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
					$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_OPTIMISTIC_LOCK);
					break;
				}
			}

			$user_id = $this->Auth->user('id');

			$this->Package->begin();

			// パッケージ情報更新
			$package_data["id"] = "'". $package_id ."'" ;
			$package_data["public_user_id"] = "'". $user_id ."'";
			$package_data["modified_user_id"] = "'". $user_id ."'";
			$package_data["modified"] = "'". date('Y-m-d H:i:s') ."'";
			if($target_flg == "1"){
				// 予約公開
				$package_data["public_reservation_datetime"] = "'". $db_date ."'";
				$package_data["status_cd"] = "'". Status::STATUS_CD_RELEASE_RESERVE ."'";
				$package_data["public_cd"] = "'". AppConstants::PUBLIC_CD_RESERVE ."'";
			}
			else{
				// 即時公開
				$package_data["public_reservation_datetime"] = "'". date('Y-m-d H:i:s') ."'";
				$package_data["status_cd"] = "'". Status::STATUS_CD_RELEASE_NOW ."'";
				$package_data["public_cd"] = "'". AppConstants::PUBLIC_CD_PROMPTLY ."'";
			}
			$condition = array(
					'Package.id'        => $package_id ,
					'Package.is_del'    => AppConstants::FLAG_OFF ,
					"Package.status_cd" => array(
						Status::STATUS_CD_APPROVAL_OK,
						Status::STATUS_CD_RELEASE_NOW ,
						Status::STATUS_CD_RELEASE_REJECT,
						Status::STATUS_CD_RELEASE_RESERVE,
						Status::STATUS_CD_RELEASE_ERROR
					) ,
			);
			if ($this->Package->updateAll($package_data,$condition) == False) {
				// DB ERROR
				$this->Package->rollback();

				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				break;
			}
			// 履歴登録
			$this->Package->data['Package']['id'] = $package_id;
			$this->Package->afterSave("");


			// 古いバッチキュー削除
			$fields = array(
				"is_del" => '1',
				"modified_user_id" => $user_id,
				"modified" => date("'Y-m-d H:i:s'"),
			);
			$conditions =array(
				"is_del" => '0',
				"package_id" => $package_id,
				"project_id" => $project_id,
				"batch_cd"   => array(Batch::BATCH_CD_SCHEDULE, Batch::BATCH_CD_RELEASE)
			);
			if( $this->BatchQueues->updateAll($fields,$conditions) == False) {
				// DB ERROR
				$this->Package->rollback();

				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				break;
			}

			// バッチキュー情報登録
			// 2回連続でBatchQueuesに挿入するためidに空文字を設定する
			$batch_queues_data["id"] = "";
			$batch_queues_data["package_id"] = $package_id;
			$batch_queues_data["project_id"] = $project_id;
			$batch_queues_data["created_user_id"] = $user_id;
			$batch_queues_data["modified_user_id"] = $user_id;
			$batch_queues_data["user_id"] = $user_id;
			$batch_queues_data["created"] = date('Y-m-d H:i:s');
			if($target_flg == "1"){
				$batch_queues_data["batch_cd"] = Batch::BATCH_CD_SCHEDULE;
				if(!$this->BatchQueues->save($batch_queues_data)) {
					// DB ERROR
					$this->Package->rollback();

					$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
					break;
				}

				$batch_queues_data["execute_datetime"] = $db_date;
			}
			$batch_queues_data["batch_cd"] = Batch::BATCH_CD_RELEASE;

			if($this->BatchQueues->save($batch_queues_data) == FALSE ) {
				// DB ERROR
				$this->Package->rollback();
				$err_msg[] = array("Versioned" => MsgConstants::ERROR_DB);
				break;
			}


			// 更新成功
			$this->Package->commit();

			// 親画面リロード・ウィンドウクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";

			exit;
		}

		// 更新NG処理
		$this->set('err_msg', $err_msg);
		$this->layout = "publish";
		$this->render("err");
	}

	/**
	 * 削除前チェック処理
	 * @param $project_id プロジェクトID
	 * @param $package_id プロジェクトID
	 * @author hsuzuki
	*/
	public function upset_del0($project_id='',$package_id='') {

		// パラメタチェック
		if($project_id=="" or $package_id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}

		$this->Session->write("project_id",$project_id);
		$this->Session->write("package_id",$package_id);

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
		
		// バッチ起動情報チェック
		$batch_queues = new BatchQueue();
		$optioon = array(
				'conditions' => array(
						'BatchQueue.package_id' => $package_id,
						'BatchQueue.batch_cd' => Batch::BATCH_CD_RELEASE, /**  公開 */
						'result_cd' => array(
							AppConstants::RESULT_CD_SUCCESS,	/** 結果コード:成功 */
							AppConstants::RESULT_CD_EXECUTION	/** 結果コード:実行中 */
						),
						'BatchQueue.is_del' => '0'
				),
				'recursive' => -1
		);
		$batch_queue = $batch_queues->find('all', $optioon);

		if(count($batch_queue) != 0 ){
			// 該当するバッチ起動情報が存在し、結果CDが'0':未実施以外の場合→確認メッセージ
			$this->layout = "publish";
		}
		else{
			// それ以外→次へ
			$this->setAction("upset_del1");
		}
	}

    /**
     * 削除チェック処理２
     * @author hsuzuki
     */
    public function upset_del1() {

    	$project_id = $this->Session->read("project_id");
    	$package_id = $this->Session->read("package_id");

    	// パラメタチェック
		if($project_id=="" or $package_id == ""){
			// パラメタエラー（NG）
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
//			exit;
		}

    	if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
		}

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
		
		while(True){
	    	// パッケージ情報チェック
	    	$package = new Package();
	    	$optioon = array(
    				'conditions' => array(
    						'package.id' => $package_id,
    				),
	    			'recursive' => -1
	    	);
	    	$packages = $package->find('first', $optioon);
	    	if(count($packages) == 0){
	    		throw new NoDataException( MsgConstants::ERROR_NO_DATA );
	    	}


	    	//該当パッケージが論理削除された場合エラー
	    	if ($packages['Package']['is_del'] == AppConstants::FLAG_ON){
	    		$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
	    		break;
	    	}


	    	// ステータスチェック
	    	switch($packages['Package']['status_cd']){
	    		case Status::STATUS_CD_RELEASE_RESERVE:
					$date= $packages['Package']['public_reservation_datetime'];
					$wtimestmp=(strtotime($date));
	    			if( $wtimestmp < time()){
		    			// 異常：公開予定日以降
	    				break 2;
	    			}
	    			// 正常：公開予定
	    			break;
	    		case Status::STATUS_CD_APPROVAL_OK:
	    			// 正常：承認許可
	    			break;
	    		case Status::STATUS_CD_RELEASE_REJECT:
	    			// 正常：公開取消
	    			break;
	    		case Status::STATUS_CD_RELEASE_ERROR:
	    			// 正常：公開エラー
	    			break;
	    		case Status::STATUS_CD_RELEASE_EXPIRATION:
	    			// 異常：期限切れ
	    			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_EXPIRATION);
	    			break 2;
	    		default:
	    			// 異常：その他
	    			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_UPSET);
	    			break 2;
	    	}

	    	// 正常処理　　→次へ
	    	$this->redirect("upset_del2");
//	    	exit;
		}

    	// 異常処理　　→エラー画面
	    $this->set('err_msg', $err_msg);
	    $this->layout = "publish";
	    $this->render("err");
    }


    /**
     * 削除設定最終確認
     * @author hsuzuki
     */
    public function upset_del2() {

    	if( $this->Auth->loggedIn() == False ){
    		// "ログインタイムアウトの場合・親画面をリロードしてクローズ
    		$this->autoRender = false;
    		print "<script>parent.window.opener.location.reload(true);</script>";
    		print "<script>parent.window.close();</script>";
    		exit;
    	}

    	$package_id = $this->Session->read("package_id");

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
    	 
    	$this->layout = "publish";
    }

	/**
	 * 削除処理
	 * @author hsuzuki
	 */
	public function upset_del3() {

    	if( $this->Auth->loggedIn() == False ){
    		// "ログインタイムアウトの場合・親画面をリロードしてクローズ
    		$this->autoRender = false;
    		print "<script>parent.window.opener.location.reload(true);</script>";
    		print "<script>parent.window.close();</script>";
    		exit;
    	}

    	/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_GUID );
			$this->set('err_msg', $err_msg);
			$this->layout = "publish";
			$this->render("err");
			return;
		}
    	
		$package_id = $this->Session->read("package_id");
    	
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
    	
    	while(TRUE){
	    	$user_id = $this->Auth->user('id');
			$project_id = $this->Session->read("project_id");
			$package_id = $this->Session->read("package_id");



			// パッケージ情報チェック
			$package = new Package();
			$optioon = array(
					'conditions' => array(
							'package.id' => $package_id,
					),
					'recursive' => -1
			);
			$packages = $package->find('first', $optioon);
			if(count($packages) !== 1){
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			}


			//該当パッケージが論理削除された場合エラー
			if ($packages['Package']['is_del'] == AppConstants::FLAG_ON){
	    		$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_NO_DATA);
	    		break;
			}


    		    	// ステータスチェック
	    	switch($packages['Package']['status_cd']){
	    		case Status::STATUS_CD_RELEASE_RESERVE:
					$date= $packages['Package']['public_reservation_datetime'];
					$wtimestmp=(strtotime($date));
	    			if( $wtimestmp < time()){
		    			// 異常：公開予定日以降
	    				break 2;
	    			}
	    			// 正常：公開予定
	    			break;
	    		case Status::STATUS_CD_APPROVAL_OK:
	    			// 正常：承認許可
	    			break;
	    		case Status::STATUS_CD_RELEASE_REJECT:
	    			// 正常：公開取消
	    			break;
	    		case Status::STATUS_CD_RELEASE_ERROR:
	    			// 正常：公開エラー
	    			break;
	    		case Status::STATUS_CD_RELEASE_EXPIRATION:
	    			// 異常：期限切れ
	    			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_EXPIRATION);
	    			break 2;
	    		default:
	    			// 異常：その他
	    			$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_STATUS_UPSET);
	    			break 2;
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
					$this->autoRender = false;

					print MsgConstants::ERROR_OPTIMISTIC_LOCK . "<br>";
					print "<button onClick='parent.$.fancybox.close();'>閉じる</button>";

					exit;
				}
			}


			$this->Package->begin();

			// パッケージ情報更新
			$package_data["id"] = "'". $package_id ."'";
			$package_data["modified_user_id"] = "'". $user_id ."'";
			$package_data["modified"] = "'". date('Y-m-d H:i:s') ."'";
			$package_data["public_reservation_datetime"] = "'".date('Y-m-d H:i:s') ."'";
			$package_data["status_cd"] = "'". Status::STATUS_CD_RELEASE_REJECT ."'";
			$package_data["public_cd"] = "null";
			$condition = array(
					'Package.id'        => $package_id ,
					'Package.is_del'    => AppConstants::FLAG_OFF ,
					"Package.status_cd" => array(
//						Status::STATUS_CD_APPROVAL_OK,
						Status::STATUS_CD_RELEASE_REJECT,
						Status::STATUS_CD_RELEASE_RESERVE
					) ,
			);
			if ($this->Package->updateAll($package_data,$condition) == False) {
				// DB ERROR
				$this->Package->rollback();
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_DB);
				break;
			}
			// 履歴登録
			$this->Package->data['Package']['id'] = $package_id;
			$this->Package->afterSave("");

			// バッチキュー削除
			$fields = array(
					"is_del" => '1',
					"modified_user_id" => $user_id,
					"modified" => date("'Y-m-d H:i:s'"),
			);
			$conditions =array(
					"is_del" => '0',
					"package_id" => $package_id,
					"project_id" => $project_id,
					"batch_cd"   => array(Batch::BATCH_CD_SCHEDULE, Batch::BATCH_CD_RELEASE)
			);
			if( $this->BatchQueues->updateAll($fields,$conditions) == False) {
				// DB ERROR
				$this->Package->rollback();
				$err_msg[] = array("Versioned" =>  MsgConstants::ERROR_DB);
				break;
			}


			$this->Package->commit();


			// 親画面リロード・ウィンドウクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			exit;
    	}

    	// 異常処理　　→エラー画面
	    $this->set('err_msg', $err_msg);
	    $this->layout = "publish";
	    $this->render("err");
    }
}