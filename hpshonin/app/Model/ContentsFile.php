<?php
App::uses('AppModel', 'Model');

/**
 * コンテンツファイルモデルクラス
 *
 * @author smurata
 *
*/
class ContentsFile extends AppModel {

	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
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
	 * パッケージIDをキーに、リストを取得
	 *
	 * @param  unknown $packageId                 パッケージID
	 * @return Ambigous <multitype:, NULL, mixed> パッケージリスト
	 */
	function findListByPackageId($packageId) {
		return  $this->find('all', array('conditions' => array(
				'ContentsFile.package_id' => $packageId,
				'ContentsFile.is_del' => '0' )));
	}

}
?>