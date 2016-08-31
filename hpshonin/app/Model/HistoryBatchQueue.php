<?php
App::uses('AppModel', 'Model');

/**
 * バッチキュー履歴モデルクラス
 *
 * @author smurata
 *
*/
class HistoryBatchQueue extends AppModel {
	/**
	 * 参照するモデル
	 */
	public $belongsTo = array(
			'BatchQueue',
			"Project",
			"User",
			"Package",
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