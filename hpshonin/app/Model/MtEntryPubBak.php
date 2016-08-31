<?php
App::uses('AppModel', 'Model');

/**
 * mt_entry_pub_bakモデルクラス
 *
 * @author tasano
 *
 */
class MtEntryPubBak extends AppModel {

	/**
	 * テーブル
	 */
	public $useTable = 'mt_entry_pub_bak';

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

		// 削除
		$sql1 = "
				delete from
					mt_entry_pub_bak
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
					mt_entry_pub_bak
				select
					*
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

		$result2 = $this->query($sql2);
		$this->log($result2, LOG_DEBUG);
		if ($result2 === false) {
			return false;
		}

		return true;
	}

}
?>