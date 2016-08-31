<?php
App::uses('AppModel', 'Model');

/**
 * プロジェクトモデルクラス
 *
 * @author smurata
 *
 */
class Project extends AppModel {
	/**
	 * 参照されるモデル
	 */
	public $hasMany = array(
			"BatchQueue",
			"HistoryBatchQueue",
			"Package",
			'ProjectUser',
			'MtProject',
	);

	public $name = "Project";
	public $validate = array(
			'project_name' => array(
						'rule' => 'notEmpty',
						'message' => 'プロジェクト名は必須です。'
					),
			'department_name' => array(
						'rule' => 'notEmpty',
						'message' => '管理は必須です。'
					),
			'site_name' => array(
						'rule' => 'notEmpty',
						'message' => 'サイト名は必須です。'
					),
			'site_url' => array(
							array(
								'rule' => 'notEmpty',
								'message' => 'サイトURLは必須です。'
							),
							array(
								// 'rule' => array('custom','/^([-_.!a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/'),
								'rule' => array('custom','/^([-_a-zA-Z0-9]+)$/'),
								'message' => 'サイトURLに使用できる文字は英数字と-と_だけです。'
							,),
							array(
								'rule' => 'existingSiteUrl',
								'on' =>  'create',
								'message' => 'すでに使われているサイトURLです。'
							),
							array(
								'rule' => 'vanSiteUrl',
								'message' => ''
							),
					),
	);

	
	/**
	 * サイトURLの禁止名チェック
	 * @param  $value サイトURL
	 * @return true:OK/false:MG
	 * @author hsuzuki
	 */
	public function vanSiteUrl($value) {
		$url_ary = explode(",", AppConstants::BAN_URL_LIST);
		foreach($url_ary as $url){
			if(strtolower( $value["site_url"] ) === strtolower($url) ){
				$this->invalidate("site_url",$value["site_url"]. "というサイトURLは使えません。");
				return false;
			}
		}
		return true;
	}	
	
	
	/**
	 * サイトURLの既存チェック
	 * @param  $value サイトURL
	 * @return true:既存登録なし/false:既存登録あり
	 */
	public function existingSiteUrl($value) {

		if(isset($this->data[$this->name]["id"])){
			// 更新時チェック
			$optioon = array(
					'conditions' => array(
							'Project.is_del' => 0,
							'Project.site_url' => $value,
							'Project.id != ' => $this->data[$this->name]["id"],
					),
					'recursive' =>-1
			);
		}
		else{
			// 新規登録時チェック
			$optioon = array(
					'conditions' => array(
							'Project.is_del' => 0,
							'Project.site_url' => $value,
					),
					'recursive' =>-1
			);
		}
		$count = $this->find('count', $optioon);
		if($count == 0){
			$ret_val = true;
		}
		else{
			$ret_val = false;
		}
		return $ret_val;
	}

	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
			// 作成者のエリアス
			'CreatedUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'created_user_id'
			),
			// 更新者のエリアス
			'ModifiedUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'modified_user_id'
			),
			'Package' => array(
					'className'		=> 'Package',
					'foreignKey'	=> 'id'
			)
	);

	/**
	 * 削除されていない全プロジェクトの取得
	 * @return Ambigous <multitype:, NULL, mixed>
	 */
	public function getProjects() {
		$projects = $this->find('all',
				Array(
						'conditions' => Array(
								'Project.is_del' => 0
						),
						'recursive' => 0
				)
		);

		return $projects;
	}

	/*
	 * プロジェクトテーブル取得
	*/
	public function getProject($id, $del='0') {
		$project = $this->find('first',
				Array(
						'conditions' => Array(
								'Project.id' => $id,
								'Project.is_del' => $del
								),
						'recursive' => 1
				)
		);

		return $project;
	}

	/**
	 * 掃除対象リストを取得
	 *
	 * @param  unknown $id                        プロジェクトID
	 * @return Ambigous <multitype:, NULL, mixed> 対象リスト
	 */
	public function getCleaningTargetList() {
		$project = $this->find('all',
				Array(
						'conditions' => Array(
								'Project.is_del'   => '1',
								'Project.is_clean' => '0',
						),
						'recursive' => 0
				)
		);

		return $project;
	}

}
?>