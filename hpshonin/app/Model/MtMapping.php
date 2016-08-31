<?php
App::uses('AppModel', 'Model');

/**
 * MovableTypeマッピングモデルクラス
 *
 * @author smurata
 *
*/
class MtMapping extends AppModel {
	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
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
	 * ?編集用MT記事IDをキーに、１件取得します。
	 *
	 * @param unknown $edit_mt_post_id 編集用MT記事ID
	 */
	public function findByEditMtPostId( $edit_mt_post_id ) {
		return  $this->find('first', array('conditions' => array(
				'MtMapping.edit_mt_post_id' => $edit_mt_post_id,
				'MtMapping.is_del' => '0' )));
	}

	/**
	 * ?編集用MT記事IDをキーに、１件取得します。
	 *
	 * @param unknown $public_mt_post_id 公開用MT記事ID
	 */
// 	public function findByPublicMtPostId( $public_mt_post_id ) {
// 		return  $this->find('first', array('conditions' => array(
// 				'MtMapping.public_mt_post_id' => $public_mt_post_id,
// 				'MtMapping.is_del' => '0' )));
// 	}

	/**
	 * 記事IDを更新します。
	 *
	 * @param unknown $edit_mt_post_id     編集用MT記事ID
	 * @param unknown $approval_mt_post_id 承認用MT記事ID
	 * @param unknown $public_mt_post_id   公開用MT記事ID
	 * @param unknown $user_id             ユーザID
	 */
	public function updatePostId($edit_mt_post_id, $approval_mt_post_id, $public_mt_post_id, $user_id) {

		// 編集用MT記事IDをキーに、１件取得
		$mt_mapping = $this->findByEditMtPostId($edit_mt_post_id);

		// 存在しなかった場合
		if (empty($mt_mapping)) {

			// 新たに生成
			$mt_mapping['MtMapping']['id']              = null;				// MT記事マッピングID
			$mt_mapping['MtMapping']['edit_mt_post_id'] = $edit_mt_post_id;	// 編集用MT記事ID
			$mt_mapping['MtMapping']['created_user_id'] = $user_id; 		// 作成者ID
			$mt_mapping['MtMapping']['created']         = null;				// 作成日時
		}

// 		$mt_mapping['MtMapping']['approval_mt_post_id'] = $approval_mt_post_id;	// 承認用記事ID
// 		$mt_mapping['MtMapping']['public_mt_post_id']   = $public_mt_post_id;	// 公開用記事ID
		$mt_mapping['MtMapping']['modified_user_id']     = $user_id; 			// 更新者ID
		$mt_mapping['MtMapping']['modified']            = null;					// 更新日時

		// 保存
		return $this->save($mt_mapping);
	}

}
?>