<?php
App::uses('AppModel', 'Model');

/**
 * MovableType記事モデルクラス
 *
 * @author smurata
 *
*/
class MtPost extends AppModel {
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
	 * @param  unknown $package_id                パッケージID
	 * @return Ambigous <multitype:, NULL, mixed> パッケージリスト
	 */
	function findListByPackageId($package_id) {
		return  $this->find('all', array('conditions' => array(
				'MtPost.package_id' => $package_id,
				'MtPost.is_del' => '0' )));
	}

}
