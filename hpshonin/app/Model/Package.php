<?php
App::uses('AppModel', 'Model');
App::uses('HistoryPackage', 'Model');

/**
 * パッケージモデルクラス
 *
 * @author smurata
 *
 */
class Package extends AppModel {

	/**
	 * 参照するモデル
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
			)
	);

	public $validate = array(
			'package_name' => array(
					'rule' => 'notEmpty',
					'message' => 'パッケージ名は必須です。'
			),
			'operation_cd' => array(
					'rule' => 'notEmpty',
					'message' => '種別は必須です。'
			),
			'public_due_date' => array(
					'rule' => 'notEmpty',
					'message' => '公開予定日は必須です。'
			),
			'is_del' => array(
					'rule' => 'notEmpty',
					'message' => '削除フラグは必須です。'
			),
			'camment' => array(
					'rule' => array('between', '0', '1000'),
					'message' => 'コメントは1000文字以内で入力してください。'
			),
// 			'contents_file_name' => array(
// 					'sizeZero' => array(
// 							'rule' => array('checkFileNotEmpty'),
// 							'message' => 'コンテンツファイルは必須です。',
// 							'last'	=>	true
// 					),
// 					'uploadError' => array(
// 							'rule'    => 'uploadError',
// 							'message' => 'コンテンツファイルのアップロードに失敗しました。',
// 							'last'	=>	true
// 					),
// 					'fileSize' => array(
// 							'rule' => array('fileSize', '<=', '10MB'),
// 							'message' => 'コンテンツファイルには、10MB以下でアップロードしてください。',
// 							'last'	=>	true
// 					),
// 					'extension' => array(
// 							'rule'    => array('extension', array('zip')),
// 							'message' => 'コンテンツファイルには、ZIPファイルを指定してください。',
// 							'last'	=>	true
// 					),
// 			),
			'request_note' => array(
					'rule' => array('between', '0', '200'),
					'message' => '特記事項等は200文字以内で入力してください。'
			),
			'approval_note' => array(
					'rule' => array('between', '0', '200'),
					'message' => '特記事項等は200文字以内で入力してください。'
			),
	);

	/**
	 * ファイルインプット用の存在チェック
	 *
	 * @return boolean
	 */
	public function checkFileNotEmpty(){
		if ( !empty( $this->data[$this->alias]['contents_file_name'] ) ) {

			return !$this->data[$this->alias]['contents_file_name']['error'] == 4;
		}
		return false;
	}

	/**
	 * パッケージIDをキーに、１件取得
	 *
	 * @param unknown $packageId パッケージID
	 */
	public function findByPackageId($packageId) {
		return  $this->find('first', array('conditions' => array(
				'Package.id' => $packageId,
				'Package.is_del' => '0' )));
	}

	/**
	 * パッケージIDをキーに、１件取得(※削除フラグを考慮しない)
	 *
	 * @param unknown $packageId パッケージID
	 */
	public function findByPackageIdWithoutConsideringIsDel($packageId) {
		return  $this->find('first', array('conditions' => array(
				'Package.id' => $packageId)));
	}

	/**
	 * プロジェクトIDをキーに、リストを取得
	 *
	 * @param unknown $project_id プロジェクトID
	 */
	public function findListByPackageId($project_id) {
		return  $this->find('all', array('conditions' => array(
				'Package.project_id' => $project_id,
				'Package.is_del' => '0' )));
	}

	/**
	 * パッケージに更新があった場合、履歴に追加する。
	 * @param boolean $created
	 */
	public function afterSave($created) {
		$id = $this->data['Package']['id'];
		$queue = $this->findById($id);

		// 履歴の保存
		$histroy = new HistoryPackage();
		$histroy->set('is_del', $queue['Package']['is_del']);
		$histroy->set('created_user_id', $queue['Package']['modified_user_id']);
		$histroy->set('modified_user_id', $queue['Package']['modified_user_id']);
		$histroy->set('package_id', $queue['Package']['id']);
		$histroy->set('project_id', $queue['Package']['project_id']);
		$histroy->set('user_id', $queue['Package']['user_id']);
		$histroy->set('is_blog', $queue['Package']['is_blog']);
		$histroy->set('status_cd', $queue['Package']['status_cd']);
		$histroy->set('operation_cd', $queue['Package']['operation_cd']);
		$histroy->set('package_name', $queue['Package']['package_name']);
		$histroy->set('camment', $queue['Package']['camment']);
		$histroy->set('contents_file_name', $queue['Package']['contents_file_name']);
		$histroy->set('upload_file_name', $queue['Package']['upload_file_name']);
		$histroy->set('public_due_date', $queue['Package']['public_due_date']);
		$histroy->set('request_note', $queue['Package']['request_note']);
		$histroy->set('request_modified', $queue['Package']['request_modified']);
		$histroy->set('approval_modified', $queue['Package']['approval_modified']);
		$histroy->set('approval_note', $queue['Package']['approval_note']);
		$histroy->set('public_cd', $queue['Package']['public_cd']);
		$histroy->set('public_reservation_datetime', $queue['Package']['public_reservation_datetime']);
		$histroy->set('is_staging', $queue['Package']['is_staging']);

		$histroy->set('request_user_id' ,$queue['Package']['request_user_id']);
		$histroy->set('approval_user_id',$queue['Package']['approval_user_id']);
		$histroy->set('public_user_id'  ,$queue['Package']['public_user_id']);

		$histroy->set('message'         ,$queue['Package']['message']);
		$histroy->set('is_clean_file'   ,$queue['Package']['is_clean_file']);

		$histroy->save($histroy);

		parent::afterSave($created);
	}

	/**
	 * MTロールバック対象のリストを取得します。
	 *
	 * @return boolean|multitype: パッケージIDリスト
	 */
	public function getCleaningTargetListForRollbackMt() {

		// 削除フラグが"0"でステータスが93:却下か95:有効期限切れの場合、
		// または削除フラグが"1"でステータスが06:公開完了以外の場合
		// パッケージ掃除バッチを実施していない。

		// SQL文
		$sql = " SELECT
					id
				 FROM
					packages
				 WHERE
				 	(
						is_del      = '0'           AND
						is_blog     = '1'           AND
						status_cd   IN ('93', '95') AND
						is_clean_db = '0'
					)
					OR
					(
						is_del      = '1'   AND
						is_blog     = '1'   AND
						status_cd   != '06' AND
						is_clean_db = '0'
					) ";

		// SQL実行
		return  $this->query($sql);
	}

	/**
	 * コンテンツファイル削除の対象リストを取得します。
	 *
	 * @return boolean|multitype: パッケージIDリスト
	 */
	public function getCleaningTargetListForDeleteContentFile() {

		// 削除フラグが"0"でステータスが06:公開完了、93:却下か95:有効期限切れの場合、
		// または削除フラグが"1"の場合
		// パッケージ掃除バッチを実施していない。

		// SQL文
// (S) 2013.10.15 murata
// MTをつかわない方式に変更
//		$sql = " SELECT
//					id
//				 FROM
//					packages
//				 WHERE
//				 	(
//						is_del        = '0'               AND
//						is_blog       = '0'               AND
//						status_cd     IN ('06','93','95') AND
//						is_clean_file = '0'
//					)
//					OR
//					(
//						is_del        = '1' AND
//						is_blog       = '0' AND
//						is_clean_file = '0'
//					) ";
// (E) 2013.10.15
		$sql = " SELECT
					id
				 FROM
					packages
				 WHERE
				 	(
						is_del        = '0'               AND
						status_cd     IN ('06','93','95') AND
						is_clean_file = '0'
					)
					OR
					(
						is_del        = '1' AND
						is_clean_file = '0'
					) ";

		// SQL実行
		return  $this->query($sql);
	}

	/**
	 * ブログパッケージ画像削除の対象リストを取得します。
	 *
	 * @return boolean|multitype: パッケージIDリスト
	 */
	public function getCleaningTargetListForDeleteBlogImage() {

		// 削除フラグが"0"でステータスが06:公開完了、93:却下か95:有効期限切れの場合、
		// または削除フラグが"1"の場合
		// パッケージ掃除バッチを実施していない。

		// SQL文
		$sql = " SELECT
					id
				 FROM
					packages
				 WHERE
				 	(
						is_del        = '0'               AND
						is_blog       = '1'               AND
						status_cd     IN ('06','93','95') AND
						is_clean_file = '0'
					)
					OR
					(
						is_del        = '1' AND
						is_blog       = '1' AND
						is_clean_file = '0'
					) ";

		// SQL実行
		return  $this->query($sql);
	}

}
?>