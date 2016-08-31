<?php
App::uses('AppModel', 'Model');

/**
 * プロジェクト-ユーザーモデルクラス
 *
 * @author smurata
 *
 */
class ProjectUser extends AppModel {

	/**
	 * テーブル
	 */
	public $useTable = 'project_user';

	/**
	 * 主キー
	 */
	public $primaryKey = 'id';

	/**
	 * 参照するテーブル
	 */
	public $belongsTo = array(
			'Project',
			'User',
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
			),
	);

	/*
	 * プロジェクトユーザーテーブル取得
	*/
	public function getProjectUser($id) {
		$projectUser = $this->find('all',
				Array(
						'conditions' => Array(
								'ProjectUser.user_id' => $id,
								'ProjectUser.is_del' => '0',
								'User.is_del' => '0',
								'Project.is_del' => '0'
						),
						'recursive' => 1
				)
		);

		return $projectUser;
	}
}
?>