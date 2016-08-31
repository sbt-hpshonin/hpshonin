<?php
App::uses('AppModel', 'Model');
App::uses('ProjectUser', 'Model');

/**
 * 権限チェックコンポーネント
 *
 * @author hsuzuki
 */
class AuthorityChkComponent extends Object{
//class AuthorityChkComponent extends Component{
	public $uses = array('ProjectUser');
	

	//var $_controller;
	var $ProjectUser;

	//function startup(& $controller) {
	//	$this->_controller = $controller;
	//}
	
	/**
	 * プロジェクト編集権限チェック
	 * @param $project_id プロジェクトID
	 * @return true:プロジェクト編集可/false:不可
	 * @author hsuzuki
	 */
	public function authProject($project_id){
	
		if($this->authAdmin()==false){
			
			$user_id = AuthComponent::user("id");
			$roll_cd = AuthComponent::user("roll_cd");
			
			$this->loadModel("ProjectUser");
			
			$optioon = array(
					'conditions' => array(
							'ProjectUser.project_id' => $project_id,
							'ProjectUser.user_id' => $user_id
					),
					'recursive' => -1
			);
			$count = $this->ProjectUser->find('count', $optioon);
			
			if($count==0){
				return false;
			}
			
		}
		return true;
	}

	/**
	 * パッケージ編集権限チェック
	 * @param $package_id パッケージID
	 * @return true:パッケージ編集可/false:不可
	 * @author hsuzuki
	 */
	public function authPackage($package_id){
	
		if($this->authAdmin()==false){
			
			$user_id = AuthComponent::user("id");
			$roll_cd = AuthComponent::user("roll_cd");
			
			$this->loadModel("ProjectUser");
			$sql1 
				= "SELECT project_id "
				. " FROM packages "
				. "WHERE id = ? "
				;
			$sql2
				= "select "
				. "  project_user.* "
				. "from ($sql1)	as packages "
				. "left join project_user "
				. "on packages.project_id = project_user.project_id "
				. "where project_user.user_id = ? "
				;	
			$project_user = $this->ProjectUser->query($sql2,array($package_id,$user_id));
			
			if(count($project_user) != 1){
				return false;
			}
		}
		return true;
	}
	private function loadModel($modelName) {
		if (!empty($this->{$modelName})) {
			// すでに存在すればそのままreturn
			return;
		} elseif (!empty($this->controller->{$modelName})) {
			// 呼び出し元のコントローラでusesしてあれば$this->{モデル名}に参照渡し
			$this->{$modelName} = $this->controller->{$modelName};
		} else {
			// コントローラでusesしていなければコンポーネントでモデルを読み込む
			App::uses($modelName, 'Model');
			$this->{$modelName} = new $modelName;
		}
	}
	/**
	 * スーパーユーザー権限チェック
	 * @return true:管理者または広報/false:それ以外
	 * @author hsuzuki
	 */
	public function authAdmin(){
	
		$user_id = AuthComponent::user("id");
		$roll_cd = AuthComponent::user("roll_cd");
		if($roll_cd == AppConstants::ROLL_CD_DEVELOP or
		   $roll_cd == AppConstants::ROLL_CD_SITE ){
			return false;
		}
		return true;
	}
	
}
?>