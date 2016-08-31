<?php
App::uses('AppModel', 'Model');
App::uses('HistoryPackage', 'Model');
App::uses('Status', 'Lib');
App::uses('FileUtil', 'Lib/Utils');
App::uses('AppConstants', 'Lib/Constants');

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
			),
			// 承認依頼者のエリアス
			'RequestUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'request_user_id'
			),
			// 公開実施者のエリアス
			'PublicUser' => array(
					'className'		=> 'User',
					'foreignKey'	=> 'public_user_id'
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
						is_del        = '0'                      AND
						status_cd     IN ('06', '91', '93','95') AND
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

	/**
	 * 前回公開され、公開用およびステージング用にファイルが残っているパッケージの取得
	 * @param unknown $id
	 */
	public function getPriviousVersionPackageId($id) {

		$sql = <<<EOT
select
	Package.*
	, Project.*
from
	packages Package
		join projects Project
		on Package.project_id = Project.id
where
	Package.is_del = '0'
	and Package.status_cd = ?
	and Package.is_clean_file = '0'
	and Package.project_id = ?
	and not exists (
		select
			*
		from
			batch_queues bq
				join packages p
				on bq.package_id = p.id
		where
			bq.is_del = '0'
			and bq.batch_cd in ('42', '51')
			and bq.result_cd in (0, 2)
			and p.project_id = ?)
order by
	Package.modified desc
	, Package.id
limit 2
EOT;

		$packages = $this->findById($id);
		$packages = $this->query($sql, array(Status::STATUS_CD_RELEASE_COMPLETE, $packages['Project']['id'], $packages['Project']['id']));
		if (count($packages) < 2) {
			return -1;
		}

		if ($packages[0]['Package']['is_blog'] !== '0') {
			return -1;
		}

		$package = $packages[1];
		
		$site_url = $package['Project']['site_url'];
		$recent_package_id = $package['Package']['id'];

		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $recent_package_id;
		if (FileUtil::exists($staging_path) === false) {
			return -1;
		}

		// 公開用フォルダ接続確認
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			if (FileUtil::exists($director_publish_path) === false) {
				return -1;
			}
		}

		// 公開用フォルダ
		$publish_path_list = array();
		foreach ($this->getDirectorPublishPathList() as $director_publish_path) {
			$publish_path = $director_publish_path . DS . $site_url. DS . $recent_package_id;
			if (FileUtil::exists($publish_path) === false) {
				return -1;
			}
		}
		return $recent_package_id;
	}
	
	/**
	 * 更新から一定期間の経過した生きたステータスのパッケージを取得
	 * @return mixed
	 */
	public function getAlivePackages() {
		$sql = <<<EOT
select
	pj.site_url
	, pkg.id
from
	projects pj
		inner join packages pkg
		on pj.id = pkg.project_id
where
	pj.public_package_id <> pkg.id
	and pkg.status_cd = '06'/*公開完了*/
	and to_days(now()) - to_days(pkg.modified) >= 7
order by
	pj.id
	, pkg.id
EOT;
		
		return $this->query($sql);
	}

	/**
	 * 使用されないパッケージを取得
	 * @return mixed
	 */
	public function getDeadPackages() {
		$sql = <<<EOT
select
	pj.site_url
	, pkg.id
from
	projects pj
		inner join packages pkg
		on pj.id = pkg.project_id
where
	pkg.is_clean_file = '0'
	and (
		pj.is_del = '1'
		or (
				(
					pkg.status_cd = '06'/*公開完了*/
					and to_days(now()) - to_days(pkg.modified) >= 7)
			or
				(
					pkg.is_del = '1')
			or
				(
					pkg.status_cd in ('91'/*パッケージ登録却下*/, '93'/*却下*/, '95'/*有効期限切れ*/))
		)
	)
order by
	pj.id
	, pkg.id
EOT;

		return $this->query($sql);
	}

	
	/**
	 * 公開用フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用フォルダリスト
	 */
	private function getDirectorPublishPathList() {
		$ret = array();
		for($i = 1; ; $i++) {
			@ $path = constant('AppConstants::DIRECTOR_PUBLISH_PATH_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}
}
?>