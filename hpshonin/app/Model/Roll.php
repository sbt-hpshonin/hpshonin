<?php
App::uses('AppModel', 'Model');

/**
 * アカウント種別モデルクラス
 *
 * @author smurata
 *
 */
class Roll extends AppModel {
	/**
	 * 主キー
	 * @var unknown_type
	 */
	public $primaryKey = 'roll_cd';

	/**
	 * 参照されるモデル
	 */
	public $hasMany = array(
			'User'	=> array(
					'className'	=> 'User',
					'foreignKey'=> 'roll_cd'
			)
	);

	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
			// 作成者のエリアス
			'CreatedUser' => array(
					'className'	=> 'User',
					'foreignKey'=> 'created_user_id'
			),
			// 更新者のエリアス
			'ModifiedUser' => array(
					'className'	=> 'User',
					'foreignKey'=> 'modified_user_id'
			)
	);
}
?>