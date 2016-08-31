<?php
App::uses('AppModel', 'Model');

/**
 * 拡張バッチキューモデルクラス
 *
 * @author smurata
 *
 */
class ExtensionBatchQueue extends AppModel {

	//var $useTable = 'extension_batch_queues"';
	// var $primaryKey = 'batch_queue_id';
	var $belongsTo = array(
		'BatchQueue' => array(
			'className' => 'BatchQueue',
			'foreignKey' => 'batch_queue_id',
			'type' => 'right',
			'associationForeignKey' => 'id',
		)
	);

	/**
	 * 参照されるモデル
	 */
	public $hasMany = "BatchQueue";
}
?>