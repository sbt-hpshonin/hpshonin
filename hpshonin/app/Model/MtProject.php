<?php
App::uses('AppModel', 'Model');

/**
 * MovableTypeプロジェクトモデルクラス
 *
 * @author smurata
 *
*/
class MtProject extends AppModel {

	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
			"Project",
			// 作成者のエリアス
			'CreatedUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'created_user_id'
			),
			// 更新者のエリアス
			'ModifiedUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'modified_user_id'
			)
	);

	/*
	 * MTプロジェクトテーブル取得
	*/
	public function getMtProject($id, $del='0') {
		$mtProject = $this->find('first',
				Array(
						'conditions' => Array(
								'MtProject.project_id' => $id,
								//'MtProject.is_del' => $del,
								'Project.is_del' => $del
						),
						'recursive' => 1
				)
		);

		return $mtProject;
	}
}
?>