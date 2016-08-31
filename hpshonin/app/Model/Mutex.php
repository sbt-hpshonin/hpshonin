<?php
App::uses('AppModel', 'Model');

/**
 * Mutexモデルクラス
 *
 * @author smurata
 */
class Mutex extends AppModel {
	/** 使用テーブル */
	public $uses = array('Package');

	/**
	 * ロックする
	 * @param unknown $batch_queue_id
	 * @param unknown $package_id
	 * @param unknown $project_id
	 * @param unknown $user_id
	 * @param unknown $process_id
	 * @return boolean
	 */
	private function lock($batch_queue_id, $package_id, $project_id, $user_id, $process_id) {
		$mutex = new Mutex();
		$mutex->set('batch_queue_id',	$batch_queue_id);
		$mutex->set('package_id',		$package_id);
		$mutex->set('project_id',		$project_id);
		$mutex->set('user_id',			$user_id);
		$mutex->set('pid',				$process_id);

		$mutex->save($mutex);

		return true;
	}

	/**
	 * ロックを解除する
	 * @param unknown $batch_queue_id
	 * @return boolean
	 */
	public function unlock($batch_queue_id) {
		$this->deleteAll(array(
				'Mutex.batch_queue_id' => $batch_queue_id
		), false);
		return true;
	}

	/**
	 * ロックの判定
	 * @param unknown $batch_queue_id
	 * @param unknown $package_id
	 * @param unknown $project_id
	 * @param unknown $user_id
	 * @param unknown $process_id
	 * @return boolean
	 */
	public function tryLock($batch_cd, $batch_queue_id, $package_id, $project_id, $user_id, $process_id) {

		try {
			$this->log("排他制御テーブルをロックします。", LOG_DEBUG);
			self::lockTable();

			$this->log("排他制御テーブルに登録された情報をチェックします。queue_id:{$batch_queue_id}", LOG_DEBUG);
			if (!self::checkLockCondition($batch_cd, $batch_queue_id, $package_id, $project_id, $user_id)) {
				$flg = false;
			} else {
				$this->log("排他制御テーブルに登録します。", LOG_DEBUG);
				$flg = self::lock($batch_queue_id, $package_id, $project_id, $user_id, $process_id);
			}
		}catch(Exception $ex) {
			$this->log("エラーにより排他制御テーブルのロックを解除します。", LOG_ERR);
			self::unlockTable();
			$this->log($ex->getMessage(), LOG_ERR);
			throw $ex;
		}

		$this->log("排他制御テーブルのロックを解除します。", LOG_DEBUG);
		self::unlockTable();

		return $flg;
	}

	/**
	 *
	 * @param unknown $batch_cd
	 * @param unknown $batch_queue_id
	 * @param unknown $package_id
	 * @param unknown $project_id
	 * @param unknown $user_id
	 * @return boolean
	 */
	private function checkLockCondition($batch_cd, $batch_queue_id, $package_id, $project_id, $user_id) {
		// キューによる判定
		$cnt = $this->find('count', array(
				'conditions' => array(
						'Mutex.batch_queue_id' => $batch_queue_id,
						'Mutex.is_del' => 0
				)
		));
		if ($cnt > 0) {
			$this->log("排他制御テーブルに同一バッチキューが存在します。queue_id:{$batch_queue_id}", LOG_DEBUG);
			return false;
		}

		$_project_id = -1;
		$flg = false;
		switch($batch_cd) {
			case Batch::BATCH_CD_MT_USER_CREATE:
			case Batch::BATCH_CD_MT_USER_UPDATE:
			case Batch::BATCH_CD_MT_USER_DELETE:
				if(empty($user_id)) break;
				// ユーザーによる判定
				if (self::hasSameUser($user_id)) {
					$this->log("排他制御テーブルに同一ユーザーが存在します。user_id:{$user_id}", LOG_DEBUG);
					return false;
				} else {
					return true;
				}
			case Batch::BATCH_CD_MT_PROJECT_CREATE:
			case Batch::BATCH_CD_MT_PROJECT_UPDATE:
			case Batch::BATCH_CD_MT_PROJECT_DELETE:
				if(empty($project_id)) break;
				$_project_id = $project_id;
				$flg = true;

				break;
			case Batch::BATCH_CD_PACKAGE_CREATE:
			case Batch::BATCH_CD_REQUEST_APPROVAL:
			case Batch::BATCH_CD_APPROVAL_OK:
			case Batch::BATCH_CD_APPROVAL_NG:
			case Batch::BATCH_CD_SCHEDULE:
			case Batch::BATCH_CD_RELEASE:
			case Batch::BATCH_CD_RESTORE:
				if(empty($package_id)) break;
				$p = new Package();
				$package = $p->find('first', array(
						'conditions' => array('Package.id' => $package_id),
						'recursive' =>-1
				));
				$_project_id = $package['Package']['project_id'];
				$flg = true;

				break;
			default:
		}
		if (!$flg) {
			$this->log("無効なバッチキューです。queue_id:{$batch_queue_id}", LOG_ERR);
			return false;
		}

		// プロジェクトによる判定
		if ($_project_id != -1) {
			if(self::hasSameProject($_project_id)) {
				$this->log("排他制御テーブルに同一プロジェクトが存在します。project_id:{$_project_id}", LOG_DEBUG);
				return false;
			} else {
				return true;
			}
		}
		$this->log("無効なバッチキューです。queue_id:{$batch_queue_id}", LOG_ERR);
		return false;
	}

	/**
	 * 同一プロジェクトがMutexに存在するか
	 * @param unknown $project_id
	 * @return boolean
	 */
	function hasSameProject($project_id) {
		$sql = 'select * from ('
			.'select COALESCE(Package.project_id, Project.id) project_id from mutexes Mutex '
			.'left join projects Project on Mutex.project_id = Project.id '
			.'left join packages Package on Mutex.package_id = Package.id) mm '
			.'where mm.project_id = ?';
		$res = $this->query($sql, array($project_id));

		return count($res) > 0 ? true : false;
	}

	/**
	 * 同一ユーザがMutexに存在するか
	 * @param unknown $user_id
	 * @return boolean
	 */
	function hasSameUser($user_id) {
			$cnt = $this->find('count', array(
				'conditions' => array(
						'Mutex.user_id' => $user_id,
						'Mutex.is_del' => 0
				),
				'recursive' =>-1
		));

		return $cnt > 0 ? true : false;
	}

	/**
	 * テーブルをロックする
	 */
	private function lockTable() {
		$this->query('LOCK TABLES mutexes WRITE, mutexes as Mutex WRITE'
					.', projects READ, projects as Project READ'
					.', packages READ, packages as Package READ'
				);
	}

	/**
	 * テーブルロックを解除する
	 */
	private function unlockTable() {
		$this->query('UNLOCK TABLES');
	}
}
?>