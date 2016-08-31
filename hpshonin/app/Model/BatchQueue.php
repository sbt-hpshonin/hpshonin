<?php
App::uses('AppModel', 'Model');

/**
 * バッチキューモデルクラス
 *
 * @author smurata
 *
 */
class BatchQueue extends AppModel {

	/**
	 * 参照されるモデル
	 */
	public $hasMany = "HistoryBatchQueue";

	/**
	 * 参照するモデル
	 */
	public $belongsTo = array(
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

	/**
	 * キューテーブルに更新があった場合、履歴に追加する。
	 * @param boolean $created
	 */
	public function afterSave($created) {
		$id = $this->data['BatchQueue']['id'];
		$queue = $this->findById($id);

		// 履歴の保存
		$histroy = new HistoryBatchQueue();
		$histroy->set('is_del', $queue['BatchQueue']['is_del']);
		$histroy->set('created_user_id', $queue['BatchQueue']['modified_user_id']);
		$histroy->set('modified_user_id', $queue['BatchQueue']['modified_user_id']);
		$histroy->set('batch_queue_id', $queue['BatchQueue']['id']);
		$histroy->set('batch_cd', $queue['BatchQueue']['batch_cd']);
		$histroy->set('project_id', $queue['BatchQueue']['project_id']);
		$histroy->set('user_id', $queue['BatchQueue']['user_id']);
		$histroy->set('package_id', $queue['BatchQueue']['package_id']);
		$histroy->set('execute_datetime', $queue['BatchQueue']['execute_datetime']);
		$histroy->set('result_cd', $queue['BatchQueue']['result_cd']);
		$histroy->save($histroy);

		parent::afterSave($created);
	}
}
?>