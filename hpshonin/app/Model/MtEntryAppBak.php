<?php
App::uses('AppModel', 'Model');

/**
 * mt_entry_app_bakモデルクラス
 *
 * @author tasano
 *
 */
class MtEntryAppBak extends AppModel {

	/**
	 * テーブル
	 */
	public $useTable = 'mt_entry_app_bak';

	/**
	 * 主キー
	 */
	public $primaryKey = 'entry_id';

	/**
	 * コピーします。
	 *
	 * @param  unknown $project_id プロジェクトID
	 * @return boolean             成否
	 */
	public function copy($project_id) {
		// MTのDB名
		$mt_db = AppConstants::MT_EDIT_DB_NAME;

		// 削除
		$sql1 = "
				delete
				from
					mt_entry_app_bak
				where
					entry_blog_id = (
										select
											edit_mt_project_id
										from
											mt_projects
										where
											project_id = $project_id
									)
				";

		$result1 = $this->query($sql1);
		$this->log($result1, LOG_DEBUG);
		if ($result1 === false) {
			return false;
		}

		// 追加
		$sql2 = "
		  		insert into
					mt_entry_app_bak
				select
					*
				from
					$mt_db.mt_entry
				where
					entry_blog_id = (
										select
											edit_mt_project_id
										from
											mt_projects
										where
											project_id = $project_id
									)
				and entry_status = 2
				";

		$result2 = $this->query($sql2);
		$this->log($result2, LOG_DEBUG);
		if ($result2 === false) {
			return false;
		}

		return true;
	}

}
?>