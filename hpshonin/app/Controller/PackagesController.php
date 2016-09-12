<?php
App::uses('AppController', 'Controller');
App::uses('AppConstants',	'Lib/Constants');
App::uses('MsgConstants',	'Lib/Constants');
App::uses('DateUtil', 'Lib/Utils');
App::uses('Status',	'Lib');
App::uses('Batch',	'Lib');
App::uses('StringUtil', 'Lib/Utils');

App::uses('Sanitize', 'Utility');
App::uses('guidChkUtil', 'Lib/Utils');
App::uses('AuthorityChkComponent', 'Controller/Component');

/**
 * パッケージコントローラ
 * @author smurata
 *
*/
class PackagesController extends AppController {

	public $uses = array('Package', 'HistoryPackage', 'ContentsFile','Project', 'MtPost', 'MtProject', 'MtImageFile' , 'BatchQueue','MtEntry', 'MtMapping','ProjectUser','MtEntryAppBak','MtEntryPubBak');

	public $helpers = array('Status');

	public function index() {
		return $this->redirect('/projects');
	}

	function beforeFilter(){
		parent::beforeFilter();
		//認証不要のページの指定
		// （別ウィンドウのため、ログインページには飛ばさずに個別対応）
		$this->Auth->allow('diff', 'diff_blog');
	}

	/**
	 * パッケージ詳細
	 * @param $id	パッケージID
	 */
	public function view($id = "") {

		$package_id = $id;
		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authPackage($id) == False ){

			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}

		// パッケージ検索
		$optioon = array(
				'conditions' => array(
						'Package.id' => $id,
				),
				'recursive' => 0,
		);
		$packages = $this->Package->find('first', $optioon);
		if ( empty( $packages )) {
			// 該当パッケージなし
			throw new NoDataException( MsgConstants::ERROR_NO_DATA   );
		}
		$this->set( 'package', $packages);

		// プロジェクト検索
		$project = $this->Project->getProject( $packages['Package']['project_id'] );
		if ( empty( $project )) {
			// 該当プロジェクトなし
			throw new NoDataException( MsgConstants::ERROR_NO_DATA   );
		}
		$this->set( 'project', $project);
		$this->set( 'roll_cd', $this->Auth->user("roll_cd") );
		$site_url = $project["Project"]["site_url"];


		//公開設定の変更ボタン表示用FLG
		if ($status=$packages['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_RESERVE ) {
			$date= $packages['Package']['public_reservation_datetime'];
			//die($date);
			$wtimestmp=(strtotime($date));
			if ($wtimestmp > time()){
				$public_reservation_flg=1;
			}else{
				$public_reservation_flg=0;
			}
		}else{
			$public_reservation_flg=0;
		}
		$this->set('public_reservation_flg', $public_reservation_flg);


		//履歴一覧
		$historys = $this->HistoryPackage->find('all', array(
				'fields' => array('modified','status_cd','ModifiedUser.username'),
				'conditions' => array(
						$this->HistoryPackage->alias.'.package_id' => $id ,
						$this->HistoryPackage->alias.'.status_cd !=' => Status::STATUS_CD_APPROVAL_OK,
						$this->HistoryPackage->alias.'.is_clean_file' => '0'
				),
				'recursive' => 0,
				'order' => array( $this->HistoryPackage->alias.'.modified DESC')
		));
		$this->set('historys', $historys);


		//ファイル一覧
		//コンテンツファイル取得
			// ファイル一覧リンク用URL
			$old_base_url = AppConstants::URL_PUBLISH 	;
			$new_base_url = AppConstants::URL_APPROVAL."/". $id;
			$this->set('old_base_url', $old_base_url);
			$this->set('new_base_url', $new_base_url);


			$ContentsFile = $this->ContentsFile;
			$optioon = array(
					'conditions' => array('Package_id' => $id),
					'order' => array('file_path'),
					'recursive' => -1
			);
			$result = $ContentsFile->find('all', $optioon);

			////////////////////////////////////////////
			// ディレクトリ優先ソート
			////////////////////////////////////////////
			if(count($result) > 0){
				$before = "";
				foreach($result as $key => $filedata) {
					$filepass =$filedata['ContentsFile']['file_path'];
					if( $before . "\\" == $filepass){
						unset($result[$key -1]);
					}
					$before = $filepass;
				}

				foreach($result as $filedata) {
					$filepass =$filedata['ContentsFile']['file_path'];
					$filedata_tmp[$filepass]=$filedata;
				}
				$filepath_baff = array();
				foreach($result as $filedata) {
					$filepath_baff[] = $filedata['ContentsFile']['file_path'];
					}
				usort($filepath_baff, array("PackagesController", "compare_path"));
				unset($result);
				foreach($filepath_baff as $filedata) {
					$result[] = $filedata_tmp[$filedata];
				}
			}

			$file = array();
			$str_cnt_zen=0;
			$file_cnt =0;
			$id_cnt =array();
			$id_cnt[0]=0;
			$zen_str=array();
			$zen_str[0]="";
			foreach($result as $filedata) {
				//DB配列情報を作業変数にセット
				$filepass =str_replace("\\","/",$filedata['ContentsFile']['file_path']);
				@$filesize = $filedata['ContentsFile']['file_size'];
				@$file_modified  = $filedata['ContentsFile']['file_modified'];
				@$modify_flg   = $filedata['ContentsFile']['modify_flg'];
				@$contents_files_id = $filedata['ContentsFile']['id'];
				$file_cnt++;
				$str=explode("/",$filepass);
				$str_cnt=count($str);
				$parnt_id="";

				for($i=0;$i<$str_cnt;$i++){
					//前回ファイルと比較
					$out_flg=0;
					//ID_CNTの計算
					if (count($zen_str)>$i){
						if (implode("\\", array_slice($zen_str, 0, $i + 1, true)) == implode("\\", array_slice($str, 0, $i + 1, true))){
							//出力対象外
							$out_flg=1;
						}else{
							$id_cnt[$i]++;
						}

					}else{
						$id_cnt[$i]=1;
					}
					//ID 	$parent_idの計算
					if ($i==0){
						$id=$id_cnt[$i];
						$parent_id="";
					}else{
						$parent_id=$id;
						$id=$parent_id."-".$id_cnt[$i];
					}
					// アイコン・ファイルサイズデータ作成
					if ($i==$str_cnt-1 && substr($filepass,-1) !== "/"){
						if($packages['Package']['operation_cd'] == 1){
							switch($modify_flg){
								case AppConstants::MODIFY_FLG_ADD :
									// 追加ファイルアイコン
									$mode="file3";
									break;
								case AppConstants::MODIFY_FLG_MOD :
								case AppConstants::MODIFY_FLG_NO_MOD :
									// 変更ファイルアイコン
									$mode="file";
									break;
								case AppConstants::MODIFY_FLG_DEL:
									// 削除ファイルアイコン
									$mode="file1";
									break;
							}
						}
						else{
							// 削除ファイルアイコン
							$mode="file1";
						}


						if ($filesize <1000){
							if($filesize != ""){
							// $disp_size=$filesize . "B";
							$disp_size= number_format($filesize)."B";
							}
							else{
								$disp_size="";
							}
						}else{
							if ($filesize <1024*1000){
								$size=floor($filesize/1024);
								$disp_size=number_format($filesize/1024,2)."KB";
							}else{
								$size=floor($filesize/1024*1024);
								$disp_size=number_format($filesize/(1024*1024))."MB";

							}
						}
						$size=$filesize/100;

						$disp_date=$file_modified;

					}else{
						// ディレクトリアイコン
						$mode="folder";
						$disp_size="__";
						$disp_date="__";
					}

					if ($out_flg==0 & $str[$i] != "")  {
						$file[$file_cnt]=array();
						$file[$file_cnt]['id']=$id;
						$file[$file_cnt]['fid']= $file_cnt;
						$file[$file_cnt]['parent_id']=$parent_id;
						$file[$file_cnt]['mode']=$mode;
						$file[$file_cnt]['text']=$str[$i];
						$file[$file_cnt]['file_size']=$disp_size;
						$file[$file_cnt]['file_modified']=$disp_date;
						$file[$file_cnt]['contents_files_id'] = $contents_files_id;

						$file[$file_cnt]['modify_flg'] = $modify_flg;
						$file[$file_cnt]['filepass'] = $filepass;
						$file_cnt++;
					}

				}
				$zen_str=$str;
			}
			$this->set('file', $file);

		// ブログパッケージ登録の場合
		if ($packages['Package']['is_blog'] =="1") {
			$package_id = $packages['Package']['id'];

			// 記事一覧を取得する
			$option = array(
					'conditions' => array('package_id' => $package_id , 'is_del' => '0'),
					'order' => array('edit_mt_post_id DESC'),
					'recursive' => -1
			);
			$mt_posts = $this->MtPost->find("all", $option );

			// 記事URLを取得
			foreach($mt_posts as $key => $mt_post){

				$entry_id = $mt_post["MtPost"]["edit_mt_post_id"];

				$new_path = $this->__entryId2url4appbak($entry_id);
				if($new_path==""){
					$new_url = "";
				}
				else{
					$new_url = AppConstants::URL_APPROVAL ."/{$package_id}/{$site_url}/{$new_path}";
				}
				$old_path = $this->__entryId2url4pubbak($entry_id);
				if($old_path==""){
					$old_url = "";
				}
				else{
					$old_url = AppConstants::URL_PUBLISH ."/{$site_url}/{$old_path}";
				}

				$mt_posts["$key"]["MtPost"]["old_url"] = $old_url;
				$mt_posts["$key"]["MtPost"]["new_url"] = $new_url;

			}

			$this->set( "mt_posts", $mt_posts );
		}

		$this->set( "pre_package_id", $this->Package->getPriviousVersionPackageId($package_id));

	}

	/**
	 * パッケージ追加
	 * @param $id プロジェクトID
	 */
	public function add($id='') {

		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// 権限チェック
		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");
		if($roll_cd == AppConstants::ROLL_CD_DEVELOP or
		$roll_cd == AppConstants::ROLL_CD_SITE ){
			$optioon = array(
					'conditions' => array(
							'ProjectUser.project_id' => $id,
							'ProjectUser.user_id' => $user_id
					),
					'recursive' => -1
			);
			$count = $this->ProjectUser->find('count', $optioon);
			if($count==0){
				// 非メンバー(NG)
				throw new NoDataException( MsgConstants::ERROR_AUTH );
			}
		}

		$result = $this->Project->find('first', array('conditions' => array('Project.id' => $id),'recursive' => -1) );
		if ( empty($result) ) {
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		// radioボタンの初期値
		$this->request->data['Package']['operation_cd'] = AppConstants::OPERATION_CD_PUBLIC;
		$this->set('project', $result);
		$this->layout = "package_add";
	}


	/**
	 * ブログパッケージ追加画面表示
	 * @param $project_id プロジェクトID
	 * @param $errmsg エラーメッセージ
	 * @auther hsuzuki
	 */
	private function __add2Render($project_id='',$errmsg = ''){

		$result = $this->Project->find('first',
				array(
					'conditions' => array(
							'Project.id' => $project_id,
							'Project.is_del' => 0
					),
					'recursive' => -1
				)
		);
		if ( empty($result) ) {
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$this->set( 'project', $result);

		// 検索条件をセット
		$findConditions = array();

		if ( ! empty( $this->request->data['Package']['blog_freeword'] ) ) {
			// 検索文字列のエスケープ
			$search_word_txt = $this->request->data['Package']['blog_freeword'];
			$search_word_txt = str_replace('\\','\\\\',$search_word_txt); // \のエスケープ
			$search_word_txt = str_replace('%','\\%',$search_word_txt);   // %のエスケープ
			$findConditions["entry_title"] = $search_word_txt;
		}

		if ( !empty( $this->request->data['Package']['blog_from'] ) || ! empty( $this->request->data['Package']['blog_to'] ) ) {

			if ( !empty( $this->request->data['Package']['blog_from'] ) ) {
				$findConditions['entry_modified_on']['from'] = $this->request->data['Package']['blog_from'];
			}

			if ( ! empty( $this->request->data['Package']['blog_to'] ) ) {
				$findConditions['entry_modified_on']['to'] = $this->request->data['Package']['blog_to'];
			}
		}

		$this->set( "blogdata"        ,$this->__getMtEntryList( AppConstants::OPERATION_CD_PUBLIC, $project_id , $findConditions) );
		// $this->set( "delete_blogdata" ,$this->__getMtEntryList( AppConstants::OPERATION_CD_DELETE, $project_id ) );
		$this->set( "delete_blogdata" ,$this->__getMtEntryDelList($project_id) );

		if ( $errmsg != null ) {
			$this->set("errmsg", $errmsg);
		}

		$this->layout = "package_add";
		return $this->render("add2");
	}

	/**
	 * ブログパッケージ追加
	 * @param $id パッケージID
	 * @param $errmsg エラーメッセージ
	 */
	public function add2($id='') {

		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// 権限チェック
		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");
		if($roll_cd == AppConstants::ROLL_CD_DEVELOP or
		$roll_cd == AppConstants::ROLL_CD_SITE ){
			$optioon = array(
					'conditions' => array(
							'ProjectUser.project_id' => $id,
							'ProjectUser.user_id' => $user_id
					),
					'recursive' => -1
			);
			$count = $this->ProjectUser->find('count', $optioon);
			if($count==0){
				// 非メンバー(NG)
				throw new NoDataException( MsgConstants::ERROR_AUTH );
			}
		}

		// 画面表示
		$this->__add2Render($id,'');
	}

	/**
	 * ブログパッケージ検索
	 */
	public function blog_search() {

		@$id = $this->request->data['Package']['project_id'];

		// パラメタチェック
		if($id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// 画面表示
		$this->__add2Render($id,'');
	}

	/**
	 * パッケージ登録チェック（Ajax）
	 * @param project_id
	 * @param due_date
	 * @return エラーメッセージ
	 * @author hsuzuki
	 */
	public function add_chk(){
		if ($this->request->is('post') == False) {
			// 入力なし(NG)
			$this->autoRender = false;
			return;
		}
		@$project_id = $this->request->data['project_id'];
		@$public_due_date = $this->request->data['due_date'];

		$this->autoRender = false;

			// 公開予定日チェック
		$db_date = date('Y-m-d', strtotime($public_due_date));
		if ( $db_date < date('Y-m-d') ) {
			// return ("公開予定日には今日以降を指定してください。");
		}
		$optioon = array(
				'conditions' => array(
						'Package.project_id' => $project_id,	// 同一プロジェクトかつ
						'Package.public_due_date' => $db_date ,	// 公開予定日が同じかつ
						'Package.is_del' => 0,
						'Package.is_blog' => 0,
						'Package.status_cd' => array(			// ステータスが生きているもの
								Status::STATUS_CD_PACKAGE_READY,	/**  パッケージ準備CD */
								Status::STATUS_CD_PACKAGE_ENTRY,	/**  パッケージ登録CD */
								Status::STATUS_CD_APPROVAL_REQUEST,	/**  承認依頼CD */
								Status::STATUS_CD_APPROVAL_OK,		/**  承認許可CD */
								Status::STATUS_CD_RELEASE_RESERVE,	/**  公開予約CD */
								Status::STATUS_CD_RELEASE_NOW,
								Status::STATUS_CD_RELEASE_ERROR,
								Status::STATUS_CD_RELEASE_REJECT,
								Status::STATUS_CD_RELEASE_READY
						),
				),
				'recursive' => -1
		);
		$count = $this->Package->find('count', $optioon);
		if( $count != 0 ){

			// 公開予定日が一致するパッケージが存在する (NG)
			return ("同一プロジェクトに公開予定日が同じパッケージがあります。よろしいですか。");
		}
		return ;
	}

	/**
	 * パッケージ登録チェック（Ajax）
	 * @param project_id
	 * @return エラーメッセージ
	 * @author murata
	 */
	public function add_chk2(){
		if ($this->request->is('post') == False) {
			// 入力なし(NG)
			$this->autoRender = false;
		return ;
	}
		@$project_id = $this->request->data['project_id'];
	
		$this->autoRender = false;
	
		// 公開予定日チェック
		$db_date = date('Y-m-d', strtotime($public_due_date));
		if ( $db_date < date('Y-m-d') ) {
			// return ("公開予定日には今日以降を指定してください。");
		}
		$optioon = array(
				'conditions' => array(
						'Package.project_id' => $project_id,	// 同一プロジェクトかつ
						'Package.is_del' => 0,
						'Package.is_blog' => 0,
						'Package.status_cd' => array(			// ステータスが生きているもの
								Status::STATUS_CD_PACKAGE_READY,	/**  パッケージ準備CD */
								Status::STATUS_CD_PACKAGE_ENTRY,	/**  パッケージ登録CD */
								Status::STATUS_CD_APPROVAL_REQUEST,	/**  承認依頼CD */
								Status::STATUS_CD_APPROVAL_OK,		/**  承認許可CD */
								Status::STATUS_CD_RELEASE_RESERVE,	/**  公開予約CD */
								Status::STATUS_CD_RELEASE_NOW,
								Status::STATUS_CD_RELEASE_ERROR,
								Status::STATUS_CD_RELEASE_REJECT,
								Status::STATUS_CD_RELEASE_READY
						),
				),
				'recursive' => -1
		);

		$packages = $this->Package->find('all', $optioon);
	
		$html = "";
		if (count($packages) > 0) {
			$html = <<< EOT
<div class="maxH160">
	<table class="table table-hover">
		<thead>
			<tr>
				<th>#</th>
				<th>パッケージ名</th>
				<th>公開状態</th>
			</tr>
		</thead>
		<tbody>
EOT;
			foreach($packages as $package){
				$status_name = Status::getName($package['Package']['status_cd']);
	
				$html .= <<< EOT
			<tr>
				<td>{$package['Package']['id']}</td>
				<td>{$package['Package']['package_name']}</td>
				<td>{$status_name}</td>
			</tr>
EOT;
			}
			$html .= <<< EOT
		</tbody>
	</table>
</div>
EOT;
		}
	
		return $html;
	}	
	

	/**
	 * BLOGパッケージ登録チェック（Ajax）
	 * @param project_id
	 * @param due_date
	 * @return エラーメッセージ
	 * @author hsuzuki
	 */
	public function add2_chk(){
		if ($this->request->is('post') == False) {
			// 入力なし(NG)
			$this->autoRender = false;
			return;
		}
		@$project_id = $this->request->data['project_id'];
		@$public_due_date = $this->request->data['due_date'];

		$this->autoRender = false;

			// 公開予定日チェック
		$db_date = date('Y-m-d', strtotime($public_due_date));
		if ( $db_date < date('Y-m-d') ) {
			// return ("公開予定日には今日以降を指定してください。");
		}
		$optioon = array(
				'conditions' => array(
						'Package.project_id' => $project_id,	// 同一プロジェクトかつ
						'Package.public_due_date' => $db_date ,	// 公開予定日が同じかつ
						'Package.is_del' => 0,
						'Package.is_blog' => 1,
						'Package.status_cd' => array(
								Status::STATUS_CD_RELEASE_COMPLETE,
						),
				),
				'recursive' => -1
		);
		$count = $this->Package->find('count', $optioon);
		if( $count != 0 ){

			// 公開予定日が一致するパッケージが存在する (NG)
			return ("同一プロジェクトに公開予定日が同じパッケージがあります。よろしいですか。");
		}

		return ;
	}

	/**
	 * パッケージ登録（通常）
	 * @param $project_id プロジェクトID
	 */
	public function insert($project_id = '') {

		$this->layout = "package_add";
		// GUIDチェック
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}

		if ($this->request->is('post') == False) {
			// 入力なし(NG)
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		if($project_id==""){
			// 入力なし(NG)
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// 権限チェック
		$user_id = $this->Auth->user("id");
		$roll_cd = $this->Auth->user("roll_cd");
		if($roll_cd == AppConstants::ROLL_CD_DEVELOP or
		   $roll_cd == AppConstants::ROLL_CD_SITE ){
			$optioon = array(
					'conditions' => array(
							'ProjectUser.project_id' => $project_id,
							'ProjectUser.user_id' => $user_id
					),
					'recursive' => -1
			);
			$count = $this->ProjectUser->find('count', $optioon);
			if($count==0){
				// 非メンバー(NG)
				throw new NoDataException( MsgConstants::ERROR_AUTH );
			}
		}

		// プロジェクト
		$projects = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => -1 ));
		if ( count($projects) == 0 ) {
			// 該当プロジェクトなし(NG)
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$this->set('project', $projects);

		if(isset($this->request->data['Package']['project_id']) == false){
			// パラメタ落ち・送信ファイルが過大と判断(NG)
			$this->Package->invalidate("contents_file_name", "コンテンツファイルが大きすぎます。");
			return $this->render("add");
		}

		if($project_id != $this->request->data['Package']['project_id']){
			// GETとPOSTのproject_idの値が違う(NG)
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}


		//
		// validation
		$this->Package->set( $this->request->data );
		if( !$this->Package->validates() ){
			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => -1 ));
			//$this->set('project', $result);
			return $this->render("add");
		}
		$public_due_date = $this->request->data['Package']['public_due_date'];

		// 公開予定日チェック
		$db_date = date('Y-m-d', strtotime($public_due_date));
		if ( $db_date < date('Y-m-d') ) {

			$this->Package->invalidate("public_due_date", "公開予定日には今日以降を指定してください。");
			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => -1 ));
			//$this->set('project', $result);
			return $this->render("add");

		}

		$contents_file_name = $this->request->data['Package']['contents_file_name'];
		$fileName = mb_strtolower( substr($contents_file_name["name"], strrpos($contents_file_name["name"], '.') + 1) );

		// 必須かとzipかどうかを判定する
		$contents_file_name_check_flag = true;
		if ( $contents_file_name["size"] == 0 ) {

			$message =  "コンテンツファイルは必須です。";
			$contents_file_name_check_flag = false;
		}
		else if ( $fileName != "zip"  ){
			$message =  "コンテンツファイルはzip形式でアップロードしてください。";
			$contents_file_name_check_flag = false;
		}

		if ( ! $contents_file_name_check_flag ) {
			$this->Package->invalidate("contents_file_name", $message);
			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => -1 ));
			//$this->set('project', $result);
			return $this->render("add");
		}

		//
		// 画像の加工を行う
		//
		$user_uploaded_contents_file = $this->request->data['Package']['contents_file_name'];

		$uploadfile = tempnam( AppConstants::DIRECTOR_UPLOAD_PATH, "PHP" );

		$uploadfile3 = substr($uploadfile,0,strlen($uploadfile)-3).'ZIP';

		// ファイルの移動に失敗した場合はエラーとする
		if ( !move_uploaded_file( $user_uploaded_contents_file['tmp_name'], $uploadfile) ){

			$this->Package->invalidate("contents_file_name", "ファイルのアップロードに失敗しました。");

			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => 0 ));
			//$this->set('project', $result);

			return $this->render("add");
		}

		//ファイルの拡張子を変更
		rename( $uploadfile, $uploadfile3);


		$this->Package->begin();

		//
		// 新規登録時の初期値をセットして登録する
		//
		$user_id = $this->Auth->user('id');
		$this->request->data['Package']['is_del'] = "0";
		$this->request->data['Package']['is_blog'] = "0";
		$this->request->data['Package']['created_user_id'] = $user_id;
		$this->request->data['Package']['modified_user_id'] = $user_id;
		$this->request->data['Package']['status_cd'] = Status::STATUS_CD_PACKAGE_READY;
		$this->request->data['Package']['user_id'] = $user_id;
		$this->request->data['Package']['contents_file_name'] = $user_uploaded_contents_file['name'];
		$this->request->data['Package']['upload_file_name'] = basename( $uploadfile3 );
		if( $this->Package->save( $this->request->data, false ) == False ){
			// パッケージ登録失敗
			$this->Package->rollback();

			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => 0 ));
			//$this->set('project', $result);
			return $this->render("add");
		}

		//
		// バッチキューへ登録する
		//
		if( $this->__insertBatchQueue( $this->Package->id ) == False ){
			// バッチキュー登録失敗
			$this->Package->rollback();

			//$result = $this->Project->find('first',  array( 'conditions' => array('Project.id' => $project_id), 'recursive' => 0 ));
			//$this->set('project', $result);
			return $this->render("add");
		}

		$this->Package->commit();

		return $this->redirect('/projects/view/'.$project_id);
	}


	/**
	 * ブログパッケージ追加
	 */
	public function blog_insert() {

		$this->layout = "package_add";
		// GUIDチェック
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}

		@$project_id = $this->request->data['Package']['project_id'];
		// パラメタチェック
		if($project_id == ""){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		////////////////////////////////////////////////////////
		// 権限チェック
		////////////////////////////////////////////////////////
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authProject($project_id) == False ){

			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}


		////////////////////////////////////////////////////////
		// 他パッケージチェック
		////////////////////////////////////////////////////////
		$conditions["Package.is_del"] = "0";
		$conditions["Package.is_blog"] = "1";
		$conditions["Package.project_id"] = $project_id;
		$conditions["Package.status_cd"] = array(
				 Status::STATUS_CD_PACKAGE_READY
				,Status::STATUS_CD_PACKAGE_ENTRY
				,Status::STATUS_CD_APPROVAL_REQUEST
				,Status::STATUS_CD_APPROVAL_OK
				,Status::STATUS_CD_RELEASE_RESERVE
				,Status::STATUS_CD_RELEASE_NOW
				,Status::STATUS_CD_RELEASE_ERROR
				,Status::STATUS_CD_RELEASE_REJECT
				,Status::STATUS_CD_RELEASE_READY


		);
		$pre_package = $this->Package->find( "count" , array( "conditions" => $conditions ) );
		if ( $pre_package != 0 ) {
			$errmsg = 'ブログパッケージは、同一プロジェクトの他のパッケージの公開が完了するまで、新しいパッケージを登録することができません。';
			return $this->__add2Render( $project_id, $errmsg );
		}

		////////////////////////////////////////////////////////
		// バリデーションチェック
		////////////////////////////////////////////////////////
		$this->Package->set($this->request->data);
		// エラーの場合
		if( !$this->Package->validates() ){
			// return $this->__add2ErrorRender();
			return $this->__add2Render( $project_id );
		}

		$public_due_date = $this->request->data['Package']['public_due_date'];
		if ( date('Y-m-d', strtotime($public_due_date)) < date('Y-m-d') ) {
			$errmsg = "公開予定日には今日以降を指定してください。";
			return $this->__add2Render( $project_id, $errmsg );
		}


		////////////////////////////////////////////////////////
		// 指定記事数チェック
		////////////////////////////////////////////////////////
		$check_flag = false;

		// チェックあり公開記事件数
		$maxcnt = $this->request->data['Package']['blogcnt'];
		$check_id = "blogchk_";
		$check_value_id = "blogid_";
		for( $i=1; $i <= $maxcnt; $i++ ){
			if (  $this->request->data['Package'][$check_id.$i] ==  AppConstants::FLAG_ON ) {
				$check_flag = true;
				break;
			}
		}

		// 削除記事件数
		if( $this->request->data['Package']['deleteblogcnt'] > 0 ){
			$check_flag = true;
		}
		if ( !$check_flag ){
			// 指定記事なし
			$errmsg = '記事を選択してください。';
			return $this->__add2Render( $project_id, $errmsg );
		}

		$this->Package->begin();

		////////////////////////////////////////////////////////
		// Package登録
		////////////////////////////////////////////////////////
		$user_id = $this->Auth->user('id');
		$this->request->data['Package']['is_del'] =  AppConstants::FLAG_FALSE;
		$this->request->data['Package']['is_blog'] =  AppConstants::FLAG_TRUE;
		$this->request->data['Package']['created_user_id'] = $user_id;
		$this->request->data['Package']['modified_user_id'] = $user_id;
		$this->request->data['Package']['status_cd '] = Status::STATUS_CD_PACKAGE_READY;
		$this->request->data['Package']['user_id'] = $user_id;
		if ( !$this->Package->save($this->request->data, false) ) {
			$errmsg = 'パッケージDB登録に失敗しました。';
			$this->Package->rollback();
			return $this->__add2Render( $project_id, $errmsg );
		}
		$package_id = $this->Package->id;


		////////////////////////////////////////////////////////
		// 公開記事登録
		////////////////////////////////////////////////////////
		for( $i=1; $i <= $maxcnt; $i++ ){

			$chkname= $check_id.$i;
			$val= $this->request->data['Package'][$chkname];
			if ( $val == AppConstants::FLAG_OFF  ) {
				continue;
			}

			// チェックがあったIDを取得
			$idname = $check_value_id.$i;
			$entry_id = $this->request->data['Package'][$idname];

			// IDから記事の情報を取得する
			$optioon = array(
					'conditions' => array('entry_id' => $entry_id),
					'recursive' => -1
			);
			$blogentry = $this->MtEntry->find('first', $optioon);
			if(count($blogentry)==0){
				throw new NoDataException( MsgConstants::ERROR_NO_DATA );
			}

			$insertdata['is_del'] = AppConstants::FLAG_FALSE;
			$insertdata['created_user_id'] = $user_id;
			$insertdata['modified_user_id'] = $user_id;
			$insertdata['package_id'] = $package_id;
			$insertdata['subject'] = $blogentry['MtEntry']['entry_title'];
			$insertdata['contents']  =$blogentry['MtEntry']['entry_text'];
			$insertdata['post_modified'] = $blogentry['MtEntry']['entry_modified_on'];
			$insertdata['contents_more'] = $blogentry['MtEntry']['entry_text_more'];
			$insertdata['edit_mt_post_id'] = $blogentry['MtEntry']['entry_id'];

			// 追加記事・更新記事　判定
			if( $this->__getMtEntryChg($blogentry['MtEntry']['entry_id'] ) == true ){
				$insertdata['modify_flg'] = AppConstants::MODIFY_FLG_MOD ;
			}
			else{
				$insertdata['modify_flg'] = AppConstants::MODIFY_FLG_ADD ;
			}

			$this->MtPost->create();
			if( !$this->MtPost->save($insertdata, false) ){
				$errmsg = '記事の登録に失敗しました。';
				$this->Package->rollback();
				return $this->__add2Render( $project_id, $errmsg );
			}
		}


		////////////////////////////////////////////////////////
		// 削除記事登録
		////////////////////////////////////////////////////////
		$mt_entrys = $this->__getMtEntryDelList($project_id);
		foreach($mt_entrys as  $blogentry){
			$insertdata['is_del'] = AppConstants::FLAG_FALSE;
			$insertdata['created_user_id'] = $user_id;
			$insertdata['modified_user_id'] = $user_id;
			$insertdata['package_id'] = $package_id;
			$insertdata['subject'] = $blogentry['MtEntry']['entry_title'];
			$insertdata['contents']  =$blogentry['MtEntry']['entry_text'];
			$insertdata['post_modified'] = $blogentry['MtEntry']['entry_modified_on'];
			$insertdata['contents_more'] = $blogentry['MtEntry']['entry_text_more'];
			$insertdata['edit_mt_post_id'] = $blogentry['MtEntry']['entry_id'];
			$insertdata['modify_flg'] = AppConstants::MODIFY_FLG_DEL ;

			$this->MtPost->create();
			if( !$this->MtPost->save($insertdata, false) ){
				$errmsg = '記事の登録に失敗しました。';
				$this->Package->rollback();
				return $this->__add2Render( $project_id, $errmsg );
			}
		}


		////////////////////////////////////////////////////////
		// バッチキュー登録
		////////////////////////////////////////////////////////
		if( $this->__insertBatchQueue( $this->Package->id ) == False ){
			// バッチキュー登録失敗
			$this->Package->rollback();
			return $this->redirect('/projects/view/'.$project_id);
		}

		$this->Package->commit();

		return $this->redirect('/projects/view/'.$project_id);
	}

	/**
	 * パッケージ削除
	 * @param $id  パッケージID
	 * @author hsuzuki
	 */
	public function delete($id='') {

		if($id == ''){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		/////////////////////////////////////////////////////
		// GUIDチェック
		/////////////////////////////////////////////////////
		if( guidChkUtil::chkGUID() == false ){
			throw new NoDataException( MsgConstants::ERROR_GUID );
		}

		// ユーザー取得
		$user_id = $this->Auth->user("id");

		// 権限チェック
		$this->authorityChkComponent = new AuthorityChkComponent();
		if( $this->authorityChkComponent->authPackage($id) == False ){

			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}

		// プロジェクトID取得
		$optioon = array(
				'fields' => array('project_id','is_del','status_cd'),
				'conditions' => array('Package.id' => $id),
				'recursive' => 0
		);
		$packages = $this->Package->find('first', $optioon);
		if(count($packages)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$project_id=$packages['Package']['project_id'];


		// ステータスチェック
		if(	$packages['Package']['is_del'] == 1 ||
		    $packages['Package']['status_cd'] == Status::STATUS_CD_APPROVAL_REQUEST ||
		    $packages['Package']['status_cd'] == Status::STATUS_CD_RELEASE_COMPLETE
		){
			$this->Session->setFlash(MsgConstants::ERROR_OPTIMISTIC_LOCK);
			return $this->redirect('/packages/view/'.$id);
		}


		while(True){

			//パッケージ削除処理
			$packages['Package']['is_del'] = '1';
			$packages['Package']['id'] = $id;
			$packages['Package']['modified_user_id'] = $user_id;
			$this->Package->begin();
			if( $this->Package->save($packages, false) == False ){
				$this->Package->rollback();
				break;
			}


			// バッチキュー削除処理
			$this->BatchQueue->unbindModel(array('belongsTo'=>array( "Project","User","Package", 'CreatedUser',"ModifiedUser")), false);
			$fields = array(
					"is_del" => '1',
					"modified_user_id" => $user_id,
					"modified" => date("'Y-m-d H:i:s'"),
			);
			$conditions =array(
					"is_del" => '0',
					"package_id" => $id,
			);
			if( $this->BatchQueue->updateAll($fields,$conditions) == False) {
				// DB ERROR
				$this->Package->rollback();
				break;
			}

			// 削除成功
			$this->Package->commit();

			//プロジェクト詳細に移動
			return $this->redirect('/projects/view/'.$project_id);
		}

		// 削除失敗
		$this->Session->setFlash(MsgConstants::ERROR_DB);
		return $this->redirect('/packages/view/'.$id);

	}

	/**
	 * BLOG比較
	 * @author hsuzuki
	 * @param $package_id パッケージID
	 * @param $entry_id テーブルmt_entryのID
	 */
	public function diff_blog($package_id='',$entry_id = '') {

		if($entry_id == ''){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
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
		if( $this->authorityChkComponent->authPackage($package_id) == False ){

			// 必要権限がない場合にはエラー表示
			throw new NoDataException( MsgConstants::ERROR_AUTH );
		}


		// パッケージ検索
		$optioon = array(
				'conditions' => array('Package.id' => $package_id),
				'fields' => array('project_id'),
				'recursive' => -1
		);
		$packages = $this->Package->find('first', $optioon);
		if(count($packages)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		// サイトＵＲＬ取得
		$result = $this->Project->find('first', array(
				'conditions' => array('Project.id' => $packages['Package']['project_id'] ),
				'fields' => array('site_url', 'public_package_id'),
				'recursive' => -1
		) );
		if(count($result)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$site_url = $result["Project"]["site_url"];
		$public_package_id = $result['Project']['public_package_id'];




		$new_path = $this->__entryId2url4appbak($entry_id);
		if($new_path==""){
			$new_url = "";
			$new_url_diff = "";
		}
		else{
			$new_url = AppConstants::URL_APPROVAL ."/{$package_id}/{$site_url}/{$new_path}";
			$new_url_diff = AppConstants::URL_APPROVAL_DIFF ."/{$package_id}/{$site_url}/{$new_path}";
		}
		$old_path = $this->__entryId2url4pubbak($entry_id);
		if($old_path==""){
			$old_url = "";
			$old_url_diff = "";
		}
		else{
			$old_url = AppConstants::URL_PUBLISH ."/{$site_url}/{$old_path}";
			$old_url_diff = AppConstants::URL_PUBLISH_DIFF ."/{$site_url}/{$public_package_id}/{$old_path}";
		}

		//die("OLD=".$old_url." new=".$new_url);

		$this->Session->write("old_url",$old_url_diff);
		$this->Session->write("new_url",$new_url_diff);

		$this->set("old_url",$old_url);
		$this->set("new_url",$new_url);

		$this->set("package_id",$package_id);

		$this->set("type","htm");
		$this->set("modify_flg","1");

		$this->layout = "ajax";
		$this->render("diff");
	}
	/**
	 * approval用DBを用いてentry_idをBLOGのパスに変換する
	 * @param $entry_id エントリーID
	 * @return BLOGのパス
	 * @author hsuzuki
	 */
	private function __entryId2url4approval($entry_id=''){

		// DB切り替え(APPROVAL)
		$this->MtEntry->useApproval();

		// mt_entry検索
		$optioon = array(
				'fields' => array('entry_basename','entry_authored_on'),
				'conditions' => array('entry_id' => $entry_id),
				'recursive' => -1
		);
		$mt_entry = $this->MtEntry->find('first', $optioon);
		if(count($mt_entry)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		$entry_basename = $mt_entry['MtEntry']['entry_basename'];

		// MT特有のファイル名置換処理
		$entry_basename = str_replace("_","-",$entry_basename);

		$entry_authored_on = $mt_entry['MtEntry']['entry_authored_on'];
		$entry_authored_on_y = date('Y', strtotime($entry_authored_on));
		$entry_authored_on_m = date('m', strtotime($entry_authored_on));

		$base_path = "blog/{$entry_authored_on_y}/{$entry_authored_on_m}/{$entry_basename}.html";

		return $base_path ;
	}


	/**
	 * appbakを用いてentry_idをBLOGのパスに変換する
	 * @param $entry_id エントリーID
	 * @return BLOGのパス
	 * @author hsuzuki
	 */
	private function __entryId2url4appbak($entry_id=''){

		// mt_entry検索
		$optioon = array(
				'fields' => array('entry_basename','entry_authored_on'),
				'conditions' => array('entry_id' => $entry_id),
				'recursive' => -1
		);
		$mt_entry = $this->MtEntryAppBak->find('first', $optioon);
		if(count($mt_entry)==0){
return "";
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		$entry_basename = $mt_entry['MtEntryAppBak']['entry_basename'];

		// MT特有のファイル名置換処理
		$entry_basename = str_replace("_","-",$entry_basename);

		$entry_authored_on = $mt_entry['MtEntryAppBak']['entry_authored_on'];
		$entry_authored_on_y = date('Y', strtotime($entry_authored_on));
		$entry_authored_on_m = date('m', strtotime($entry_authored_on));

		$base_path = "blog/{$entry_authored_on_y}/{$entry_authored_on_m}/{$entry_basename}.html";

		return $base_path ;
	}


	/**
	 * pubbakを用いてentry_idをBLOGのパスに変換する
	 * @param $entry_id エントリーID
	 * @return BLOGのパス
	 * @author hsuzuki
	 */
	private function __entryId2url4pubbak($entry_id=''){

		// mt_entry検索
		$optioon = array(
				'fields' => array('entry_basename','entry_authored_on'),
				'conditions' => array('entry_id' => $entry_id),
				'recursive' => -1
		);
		$mt_entry = $this->MtEntryPubBak->find('first', $optioon);
		if(count($mt_entry)==0){
return "";
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		$entry_basename = $mt_entry['MtEntryPubBak']['entry_basename'];

		// MT特有のファイル名置換処理
		$entry_basename = str_replace("_","-",$entry_basename);

		$entry_authored_on = $mt_entry['MtEntryPubBak']['entry_authored_on'];
		$entry_authored_on_y = date('Y', strtotime($entry_authored_on));
		$entry_authored_on_m = date('m', strtotime($entry_authored_on));

		$base_path = "blog/{$entry_authored_on_y}/{$entry_authored_on_m}/{$entry_basename}.html";

		return $base_path ;
	}


	/**
	 * パッケージ比較
	 * @author hsuzuki
	 * @param $contents_files_id テーブルcontents_filesのID
	 */
	public function diff($contents_files_id = '') {

		if($contents_files_id == ''){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}

		if( $this->Auth->loggedIn() == False ){
			// "ログインタイムアウトの場合・親画面をリロードしてクローズ
			$this->autoRender = false;
			print "<script>parent.window.opener.location.reload(true);</script>";
			print "<script>parent.window.close();</script>";
			return;
		}


		$ContentsFile = $this->ContentsFile;
		$optioon = array(
				'conditions' => array('ContentsFile.id' => $contents_files_id),
				'recursive' => 0
		);
		$result = $ContentsFile->find('first', $optioon);
		if(count($result)==0){
			throw new NoDataException( MsgConstants::ERROR_NO_DATA );
		}
		$package_id = $result ['ContentsFile']['package_id'];
		$file_path = $result ['ContentsFile']['file_path'];

		$file_path = str_replace("\\","/",$file_path);


		$modify_flg = $result ['ContentsFile']['modify_flg'];
		$this->set("modify_flg",$modify_flg);

		$optioon = array(
				'conditions' => array('Package.id' => $package_id),
				'recursive' => 1,
		);
		$packages = $this->Package->find('first', $optioon);

		$site_url = $packages['Project']['site_url'];
		$public_package_id = $packages['Project']['public_package_id'];

		$old_url = AppConstants::URL_PUBLISH  . "/" . $site_url . StringUtil::ltrimOnce($file_path, "/" . $site_url);
		$new_url = AppConstants::URL_APPROVAL . "/" . $package_id . $file_path;
		$old_url_diff = AppConstants::URL_PUBLISH_DIFF . "/" . $site_url . "/". $public_package_id . StringUtil::ltrimOnce($file_path, "/" . $site_url);
		$new_url_diff = AppConstants::URL_APPROVAL_DIFF . "/" . $package_id . $file_path;
		// $this->autoRender = false;

		// @$data = file($old_url_diff);
		$data = is_readable($old_url_diff);
		if($data){

		}else{
//			$old_url="";
		}

		// $this->autoRender = true;
		// $this->autoRender = false;
		$data ="";
		// @$data = file($new_url_diff);
		$data = is_readable($new_url_diff);
		if($data){

		}else{
//			$new_url="";
//			$new_url_diff="";
		}
		// $this->autoRender = true;



		//追加・削除対策
		if ($modify_flg == AppConstants::MODIFY_FLG_ADD ){
			$old_url="";
			$old_url_diff="";
		}
		if ($modify_flg == AppConstants::MODIFY_FLG_DEL ){
			$new_url ="";
			$new_url_diff ="";
		}


		$this->Session->write("old_url",$old_url_diff);
		$this->Session->write("new_url",$new_url_diff);

		$this->set("package_id",$package_id);
		$this->set("old_url",$old_url);
		$this->set("new_url",$new_url);

		$this->layout = "ajax";

		// 拡張子によるテンプレート切り替え
		if ($new_url != ""){
			$extension_ary = explode("?", strtolower( pathinfo($new_url, PATHINFO_EXTENSION)));
		}elseif($old_url != ""){
			$extension_ary = explode("?", strtolower( pathinfo($old_url, PATHINFO_EXTENSION)));
		}else{
			$extension_ary = explode("?", strtolower( pathinfo($file_path, PATHINFO_EXTENSION)));
		}

		$extension = $extension_ary[0];
		switch($extension){
			case "html":
			case "htm":
				$type = "htm";
				break;
			case "jpg":
			case "jpeg":
			case "png":
			case "gif":
			case "tif":
			case "tiff":
			case "jfif":
			case "bmp":
			case "pdf":
			case "xls":
			case "xlsx":
			case "doc":
			case "docx":
			case "ppt":
			case "pptx":
				$type = "img";
				break;
			default:
				//$this->render('diff_img');
				$type = "txt";
				break;
		}
		$this->set("type",$type);
	}


	/**
	 * HTMLタグ等削除表示
	 * @param $target ターゲット番号
	 */
	public function strip($target, $package_id) {

		$this->autoRender = false;

		$target_url = $this->Session->read($target . "_url");

		if ($target_url !=""){
			if(is_readable($target_url) == false){
				return false;
			}

			exec(AppConstants::TEXT_CONVERTER_PATH . ' "' . $target_url . '"', $output, $return_code);
			if ($return_code == 0)
			{
				@$data = file($output[0]);
				unlink($output[0]);
				foreach ($data as &$line_data) {
					switch($target){
						case "old":
							$line_data = str_replace(AppConstants::URL_PUBLISH                                 ,AppConstants::HOME_URL,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_STAGING                    ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_PATH_REPLACE_STAGING                    ,AppConstants::URL_PATH_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                 ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							break;
						case "new":
							$line_data = str_replace(AppConstants::URL_APPROVAL .              "/{$package_id}",AppConstants::HOME_URL,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_PATH_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_PATH_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                 ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							break;
					}
				}
				print implode('', $data);
				return;
			}

			@$data = file($target_url);
			if($data===false){
				$err_ary = error_get_last();
				print $err_ary["message"];
				return;
			}

			$source_html = "";
			if($data){
				foreach ($data as &$line_data) {
					switch($target){
						case "old":
							$line_data = str_replace(AppConstants::URL_PUBLISH                                 ,AppConstants::HOME_URL,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_STAGING                    ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_PATH_REPLACE_STAGING                    ,AppConstants::URL_PATH_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                 ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							break;
						case "new":
							$line_data = str_replace(AppConstants::URL_APPROVAL .              "/{$package_id}",AppConstants::HOME_URL,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_PATH_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_PATH_REPLACE_PUBLISH,$line_data);
							$line_data = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                 ,AppConstants::URL_HOST_REPLACE_PUBLISH,$line_data);
							break;
					}
				}
				$source_html = implode('', $data);
			}

			// JavaScript/スタイルシート削除
			$source_html = $this->strip_between_tag($source_html,"script");
			$source_html = $this->strip_between_tag($source_html,"SCRIPT");
			$source_html = $this->strip_between_tag($source_html,"style");
			$source_html = $this->strip_between_tag($source_html,"STYLE");

			// HTMLタグ削除
			$source_txt = strip_tags($source_html);

			// 空行削除
			$source_html = "";
			$target_data_ary = preg_split("/[\r|\n]/s",$source_txt);
			foreach($target_data_ary as $data){
				$data = trim ($data);
				$source_html .= $data ;
				if(mb_strlen($data) > 0 ){
					$source_html .= "\n";
				}
			}
		}else{
			$source_html = "";
		}

		print $source_html;
	}

	/**
	 * 指定タグにはさまれた文字列を削除
	 * @auther hsuzuki
	 */
	function strip_between_tag($str,$tag){
		$pattern = sprintf("!<%s.*?>.*?</%s>!ims",$tag,$tag);
		$ret = preg_replace($pattern,"",$str);
		return $ret;

	}

	/**
	 * HTMLタグ等削除表示（試作１）
	 * @param $target ターゲット番号
	 * @auther hsuzuki
	 */
	public function strip_new1($target) {

		require_once ('simple_html_dom.php');

		$this->autoRender = false;
		$target_url = $this->Session->read($target . "_url");

		$dom =file_get_html($target_url);
		foreach ($dom->find('script') as $el) {
			$el->outertext = '';
		}
		foreach ($dom->find('style') as $el) {
			$el->outertext = '';
		}
		$source_html = $dom->save();
		$dom->clear();

		$source_txt = strip_tags($source_html);

		print $source_txt;

	}

	/**
	 * HTMLタグ等削除表示（試作２）
	 * @param $target ターゲット番号
	 * @auther hsuzuki
	 */
	public function strip_new($target) {


		$this->autoRender = false;
		$target_url = $this->Session->read($target . "_url");

		//print $target_url;
		$html = file_get_contents($target_url);

		$domDocument = new DOMDocument();
		@$domDocument->loadHTML($html);
		$xmlString = $domDocument->saveXML();


		$source_txt = strip_tags($xmlString);

		$xmlObject = simplexml_load_string($xmlString);
		var_dump($xmlObject);

		print $source_txt;
	}


	/**
	 * ファイル取得表示
	 * @param $target 指定セッション番号
	 * @param $package_id パッケージＩＤ
	 *
	 * @author hsuzuki
	 */
	public function getdata($target='',$package_id='') {

		$this->autoRender = false;

		$target_url = $this->Session->read($target . "_url");
		if ($target_url !=""){

			if(is_readable($target_url)==false){
				return false;
			}
			@$data = file($target_url);

			$source_html = "";
			if($data){
				$source_html = implode('', $data);
			}
			$source_txt = $source_html;

			switch($target){
				case "old":
					$source_txt = str_replace(AppConstants::URL_PUBLISH                                 ,AppConstants::HOME_URL,$source_txt);
					$source_txt = str_replace(AppConstants::URL_HOST_REPLACE_STAGING                    ,AppConstants::URL_HOST_REPLACE_PUBLISH,$source_txt);
					$source_txt = str_replace(AppConstants::URL_PATH_REPLACE_STAGING                    ,AppConstants::URL_PATH_REPLACE_PUBLISH,$source_txt);
					$source_txt = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                  ,AppConstants::URL_HOST_REPLACE_PUBLISH,$source_txt);
					break;
				case "new":
					$source_txt = str_replace(AppConstants::URL_APPROVAL .              "/{$package_id}",AppConstants::HOME_URL,$source_txt);
					$source_txt = str_replace(AppConstants::URL_HOST_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_HOST_REPLACE_PUBLISH,$source_txt);
					$source_txt = str_replace(AppConstants::URL_PATH_REPLACE_APPROVAL . "/{$package_id}",AppConstants::URL_PATH_REPLACE_PUBLISH,$source_txt);
					$source_txt = str_replace(AppConstants::URL_HOST_REPLACE_ORIGINAL_2                  ,AppConstants::URL_HOST_REPLACE_PUBLISH,$source_txt);
					break;
			}
		}else{
			$source_txt="";
		}



		print $source_txt;
	}


	/**
	 * 引数の条件でmt_entryから検索を行います。
	 *
	 * @param $compornentObject		接続先のDBコンポーネントオブジェクト
	 * @param $project_id			プロジェクトID
	 * @param $findConditions		取得する検索条件
	 * @return multitype:NULL
	 */
	private function __getMtEntryList( $operation_cd, $project_id, $findConditions = null ){

		$result= array();
		//コンテンツファイル取得
		$option = array(
				'conditions' => array('project_id' => $project_id),
				'recursive' => -1
		);

		$mtProject =  $this->MtProject->find('first', $option);
		if (empty($mtProject)) {
			return $result;
		}
//		$public_mt_project_id = $mtProject['MtProject']['public_mt_project_id'];
		$mt_project_id = -1;
		//(S) 2013.10.12 murata
		//operation_cdは使用しないし、public_mt_project_idも使用しないので、
		//使わないことを前提に修正する。
		//if ($operation_cd === AppConstants::OPERATION_CD_PUBLIC) {
			$mt_project_id = $mtProject['MtProject']['edit_mt_project_id'];
		//} else {
		//	$mt_project_id = $mtProject['MtProject']['public_mt_project_id'];
		//}
		//(E) 2013.10.12
//print $mt_project_id;
		$compornentObject = $this->__getMtDbObject($operation_cd);
		if (!empty($compornentObject)) {
			$rs = $compornentObject->getEntryList( $mt_project_id, $findConditions);

			$rowCount = $rs->rowCount();
			for($i=1;$i<=$rowCount;$i++){
				$result[$i]= $compornentObject->fetch($rs);
			}
		}
		return $result;
	}

	/**
	 * MTの削除記事検索
	 *
	 * @param $project_id  プロジェクトID
	 * @return mt_entry_pub_buk検索結果配列
	 * @author hsuzuki
	 */
	private function __getMtEntryDelList( $project_id ){

		$result= array();
		//コンテンツファイル取得
		$option = array(
				'conditions' => array('project_id' => $project_id),
				'recursive' => -1
		);
		$mtProject =  $this->MtProject->find('first', $option);
		if (empty($mtProject)) {
			return $result;
		}
		//(S) 2013.10.12 murata
		// public_mt_project_idは使用されなくなるので、edit_mt_project_idが正しい
		//$mt_project_id = $mtProject['MtProject']['public_mt_project_id'];
		$mt_project_id = $mtProject['MtProject']['edit_mt_project_id'];
		//(E) 2013.10.12

		$sql
			= "SELECT "
			. "  MtEntry.entry_title SUBJECT, "
			. "  MtEntry.entry_modified_on MODIFIED, "
			. "  MtEntry.entry_id AS ID, "
			. "  MtEntry.* "
			. "FROM mt_entry_pub_bak as MtEntry "
			. "LEFT JOIN ". AppConstants::MT_EDIT_DB_NAME .".mt_entry ON MtEntry.entry_id = mt_entry.entry_id "
			. "WHERE MtEntry.entry_blog_id = ? "
			. "  AND mt_entry.entry_id IS NULL "
			. " order by MtEntry.entry_modified_on desc,MtEntry.entry_id desc"
			;
		$mt_entry_pub_buk = $this->MtEntryPubBak->query($sql,array($mt_project_id));

/*		SELECT pub.entry_title, pub.entry_modified_on
		FROM stk.mt_entry_pub_bak pub
		LEFT JOIN mt_entry_app_bak app ON pub.entry_id = app.entry_id
		WHERE app.entry_id IS NULL
		ORDER BY pub.entry_modified_on DESC
		LIMIT 0 , 30
*/
		return $mt_entry_pub_buk;
	}


	/**
	 * MTの変更記事チェック
	 *
	 * @param $entry_id エントリーID
	 * @return true:変更記事/false:追加記事
	 * @author hsuzuki
	 */
	private function __getMtEntryChg($entry_id ){

		$optioon = array(
				'conditions' => array(
					'entry_id' => $entry_id,
					'entry_status' => 2
				),
				'recursive' => 0
		);
		$count = $this->MtEntryPubBak->find('count',$optioon);
		if($count == 0){
			return false;
		}
		else{
			return true;
		}
	}



	/**
	 * バッチキューテーブルへ登録を行います。
	 *
	 * @param unknown $aBatchCd
	 * @param unknown $aPackegeId
	 */
	private function __insertBatchQueue( $aPackegeId ){


		$user_id = $this->Auth->user('id');
		$this->BatchQueue->set( "created_user_id" , $user_id  );
		$this->BatchQueue->set( "modified_user_id" , $user_id  );
		$this->BatchQueue->set( "batch_cd" , Batch::BATCH_CD_PACKAGE_CREATE  );
		$this->BatchQueue->set( "package_id" , $aPackegeId  );

		return $this->BatchQueue->save();

	}

	/**
	 *
	 * @param unknown $operation_cd
	 */
	private function __getMtDbObject( $operation_cd ){

		if ( $operation_cd == AppConstants::OPERATION_CD_PUBLIC ) {
			$this->EditMtDb = $this->Components->load('EditMtDb');
			return $this->EditMtDb;
		}
		else {
			$this->ApprovalMtDb = $this->Components->load('ApprovalMtDb');
			return $this->ApprovalMtDb;
		}
	}

	/***************************************************/

	/**
	 * ??使用箇所不明
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	public function mt_get_by_key($id) {

		//トランザクション開始
		//$this->EditMtDb->beginTransaction();

		try{

			//キー検索SQL
			$str_sql="SELECT entry_id AS ID,";
			$str_sql=$str_sql."date_format(entry_modified_on, '%Y/%m/%d %k:%i') as MODIFIED,";
			$str_sql=$str_sql."entry_title  AS SUBJECT,";
			$str_sql=$str_sql."entry_text  AS CONTENS,";
			$str_sql=$str_sql."entry_text_more  AS CONTENS_MORE";

			$str_sql=$str_sql." FROM mt_entry ";
			$str_sql=$str_sql." WHERE entry_id =".$id;

			$rs = $this->EditMtDb->queryFetch($str_sql);

			//コミット
			$this->EditMtDb->commit();
		}catch (Exception $e){
			// 例外が発生したのでロールバック
			$this->EditMtDb->rollBack();
			echo 'is rollbacked.';
			echo 'SQL='.$str_sql;

			$result = AppConstants::RESULT_CD_FAILURE;
		}

		//データベースサーバへの接続の切断
		$this->EditMtDb->close();

		//DB情報を出力エリアにセット


		//DIE(var_dump($rs));
		return  $rs;
		//return  $blogentry;

	}

	static function compare_path($path1, $path2) {
		$path_array1 = explode("\\", $path1);
		$path1_depth = count($path_array1);
		$path_array2 = explode("\\", $path2);
		$path2_depth = count($path_array2);
		$depth = min($path1_depth, $path2_depth) - 1;
		for($i = 0; $i < $depth; $i++) {
			$cmp = strcmp($path_array1[$i], $path_array2[$i]);
			if($cmp !== 0) {
				return $cmp;
			}
		}
		if ($path1_depth === $path2_depth) {
			return strcmp($path_array1[$path1_depth - 1], $path_array2[$path2_depth - 1]);
		}
		return $path2_depth - $path1_depth;
	}

	/**
	 * 公開差し戻し処理
	 * @param unknown $id
	 */
	public function restore($id) {

		$authorityChkComponent = new AuthorityChkComponent();
		if($authorityChkComponent->authPackage($id) == False) {
			throw new NoDataException(MsgConstants::ERROR_AUTH);
		}

		if(guidChkUtil::chkGUID() == false) {
			return $this->redirect('/packages/view/'.$id);
		}

		$this->Package->begin();

		if ($id !== $this->Package->getPriviousVersionPackageId($id)) {
			$this->Package->rollback();
			return $this->redirect('/packages/view/' . $id);
		}

		$user_id = $this->Auth->user('id');

		$package = $this->Package->findById($id);
		$package['Package']['modified'] = null;
		$package['Package']['modified_user_id'] = $user_id;
		$package['Package']['public_user_id'] = $user_id;
		$package['Package']['public_reservation_datetime'] = date('Y-m-d H:i:s');
		$package['Package']['status_cd'] = Status::STATUS_CD_RELEASE_NOW;
		if ($this->Package->save($package) == false) {
			$this->Package->rollback();
			return $this->redirect('/packages/view/'.$id);
		} 

		$this->BatchQueue->set( "created_user_id" , $user_id  );
		$this->BatchQueue->set( "modified_user_id" , $user_id  );
		$this->BatchQueue->set( "batch_cd" , Batch::BATCH_CD_RESTORE );
		$this->BatchQueue->set( "package_id" , $id  );

		if ($this->BatchQueue->save() == false) {
			$this->Package->rollback();
		} else {
			$this->Package->commit();
		}
		return $this->redirect('/packages/view/'.$id);
	}
}
?>
