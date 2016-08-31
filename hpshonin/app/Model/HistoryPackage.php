<?php
App::uses('AppModel', 'Model');

/**
 * パッケージ履歴モデルクラス
 *
 * @author smurata
 *
 */
class HistoryPackage extends AppModel {
	/**
	 * 参照するモデル
	 */
	public $belongsTo = array(
			'Package',
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
			)
	);
}
?>