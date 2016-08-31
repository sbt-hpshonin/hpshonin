<?php
App::uses('AppModel', 'Model');

/**
 * MovableTypeイメージモデルクラス
 *
 * @author smurata
 *
*/
class MtImageFile extends AppModel {

	/**
	 * ファイルパスを取得します。
	 *
	 * @param  unknown $package_id      パッケージID
	 * @param  unknown $edit_mt_post_id 編集用MT記事ID
	 * @return boolean|multitype:       ファイルパスリスト
	 */
	public function getFilePathList($package_id, $edit_mt_post_id) {

		// SQL文
		$sql = " SELECT
					file_path,
					COUNT(*) AS cnt
				 FROM
					mt_image_files
				 WHERE
					file_path IN (
									SELECT
										file_path
								  	FROM
										mt_image_files
									WHERE
										edit_mt_post_id = $edit_mt_post_id AND
										package_id = $package_id
				 				)
				 GROUP BY
					file_path,
					image_modified ";

		// SQL実行
		$result =  $this->query($sql);

		if ($result === false) {
			return false;
		}

		// 扱いやすく編集
		$result2 = array();
		for ($i = 0; $i < count($result); $i++) {
			$result2[$i]['file_path'] = $result[$i]['file_path'];

			// 更新日時が同一のファイルが存在している場合
			if ($result[$i][0]['cnt'] > 1) {
				// 更新していないと判定
				$result2[$i]['modified']       = false;

			// 更新日時が同一のファイルが存在していない場合
			} else {
				// 更新していると判定
				$result2[$i]['modified']       = true;
			}
		}

		return $result2;
	}

	/**
	 * ファイルパスリストを取得します。
	 *
	 * @param  $project_id        プロジェクトID
	 * @return boolean|multitype: ファイルパスリスト
	 */
	public function getFilePathListWithIsDelete($project_id) {

		// SQL文
		$sql = "
				select
					mif.file_path,
					CASE
						WHEN pub_file.file_path is null then '1'
						ELSE '0'
					END is_delete
				from mt_image_files mif
					join packages p
						on p.id = mif.package_id
						and p.status_cd = '06'
					left join (
						select mif.file_path
						from mt_image_files mif
							join
								(
									select
										package_id,
										edit_mt_post_id
									from mt_posts mp
										join packages pkg
											on pkg.id = mp.package_id
											and pkg.status_cd = '06'
									where (mp.edit_mt_post_id, mp.modified) IN (
										select
											mp1.edit_mt_post_id,
											max(mp1.modified) modified
										from mt_posts mp1
											join packages pkg1
												on pkg1.id = package_id
												and pkg1.status_cd = '06'
										where mp1.is_del = '0'
										group by edit_mt_post_id
										)
										and mp.is_del = '0'
								) pub_posts
									on pub_posts.package_id = mif.package_id
									and pub_posts.edit_mt_post_id = mif.edit_mt_post_id
						where mif.is_del = '0'
						group by file_path
					) pub_file
						on pub_file.file_path = mif.file_path
				where mif.is_del = '0'
				and p.project_id = $project_id
				group by file_path
			";

		// SQL実行
		$list = $this->query($sql);
		if ($list === false) {
			return false;
		}

		$list2 = array();
		foreach ($list as $row) {
			$row2['file_path'] = $row['mif']['file_path'];
			$row2['is_delete'] = $row[0]['is_delete'];
			$list2[] = $row2;
		}

		return $list2;
	}

}
?>