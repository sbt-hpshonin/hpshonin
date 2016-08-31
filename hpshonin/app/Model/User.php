<?php
App::uses('AppModel', 'Model');
App::uses('AppConstants', 'Lib/Constants');
/**
 * ユーザーモデル
 *
 */
class User extends AppModel {


	public $name = "User";

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $hasMany = array(
			'BatchQueue',
			'HistoryBatchQueue',
			'HistoryPackage',
			'Package',
			'ProjectUser'
	);

	/**
	 * バリデーション
	 * @var unknown
	 */
	public $validate = array(
			'username' => array(
					'rule' => 'notEmpty',
					'message' => 'ユーザー名は必須です。'
			),
			'email' => array(
					'rulename1' => array(
							'rule' => 'notEmpty',
							'message' => 'メールアドレスは必須です。'
					),
					'rulename2' => array (
							'last'	=>	false,
							'rule' => 'email',
							'message' => 'メールアドレスが間違っています。'
					),
					'rulename3' => array(
							'last'	=>	false,
							'rule' => array('maxLength','255'),
							'message' => 'メールアドレスは最大255文字までです。'
					),

			),
			'password' => array(
					'rulename1' => array (
							'rule' => 'notEmpty',
							'on' =>  'create',
							'message' => 'パスワードは必須です。'
					),
//					'rulename2' => array (
//							'rule' => array('between', 8, 16),
//							'on' =>  'create',
//							'message' => 'パスワードは8文字以上16文字以下です'
//					)
			),

			'contact_address' => array(
					'rule' => 'notEmpty',
					'message' => '連絡先は必須です。'
			),
			'roll_cd' => array(
					'rule' => 'notEmpty',
					'message' => 'アカウント種別は必須です。'
			),

			// パスワード変更
//			'password_old' => array(
//					'rulename1' => array (
//							'rule' => 'notEmpty',
//							'message' => 'パスワードは必須です'
//					)
//			),
//			'password_new' => array(
//					'rule' => 'notEmpty',
//					'on' =>  'create',
//					'message' => 'パスワードは必須です'
//			),
//			'password_new_re' => array(
//					'rule' => array('samePassword', 'password_new'),
//					'message' => '新しいパスワードが一致しません',
//					'allowEmpty' => false
//			)
	);

	public $validate1 = array(
			'username' => array(
					'rule' => 'notEmpty',
					'message' => 'ユーザー名は必須です。'
			),
			'email' => array(
					'rulename1' => array(
							'rule' => 'notEmpty',
							'message' => 'メールアドレスは必須です。'
					),
					'rulename2' => array (
							'rule' => 'email',
							'message' => 'メールアドレスが間違っています。'
					),
					'rulename3' => array(
							'rule' => array('maxLength','255'),
							'message' => 'メールアドレスは最大255文字までです。'
					),

			),
			'password' => array(
					'rulename1' => array (
							'rule' => 'notEmpty',
							'on' =>  'create',
							'message' => 'パスワードは必須です。'
					),
//					'rulename2' => array (
//							'rule' => array('between', 8, 16),
//							'on' =>  'create',
//							'message' => 'パスワードは8文字以上16文字以下です'
//					)
			),

			'contact_address' => array(
					'rule' => 'notEmpty',
					'message' => '連絡先は必須です。'
			),
			'roll_cd' => array(
					'rule' => 'notEmpty',
					'message' => 'アカウント種別は必須です。'
			),

			// パスワード変更
//			'password_old' => array(
//					'rulename1' => array (
//							'rule' => 'notEmpty',
//							'message' => 'パスワードは必須です'
//					)
//			),
//			'password_new' => array(
//					'rule' => 'notEmpty',
//					'on' =>  'create',
//					'message' => 'パスワードは必須です'
//			),
//			'password_new_re' => array(
//					'rule' => array('samePassword', 'password_new'),
//					'message' => '新しいパスワードが一致しません',
//					'allowEmpty' => false
//			)
	);

	public $validate2 = array(
			'password_old' => array(
					'rule' => 'notEmpty',
					'message' => '現在のパスワードは必須です。'
			),
			'password_new' => array(
					'rule' => 'notEmpty',
					'message' => '新しいパスワードは必須です。'
			),
			'password_new_re' => array(
					'rule' => array('samePassword', 'password_new'),
					'message' => '新しいパスワードが一致しません。',
					'allowEmpty' => false
			)
	);

	/**
	 * 参照するモデル
	 * @var unknown_type
	 */
	public $belongsTo = array(
			'Roll' => array(
					'className'		=> 'Roll',
					'foreignKey'	=> 'roll_cd'
			),
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
	 * パスワードの同一チェック
	 * @param unknown $value
	 * @param unknown $field_name
	 * @return boolean
	 */
	public function samePassword($value , $field_name) {
		$v1 = array_shift($value);
		$v2 = $this->data[$this->name][$field_name];
		return $v1 === $v2;
	}

	/**
	 * ユーザーテーブル取得
	 */
	public function getUser($id, $del='0') {
		$user = $this->find('first',
				Array(
						'conditions' => Array(
								'User.id' => $id,
								'User.is_del' => $del
								),
						'recursive' => 1
				)
		);

		return $user;
	}

	/**
	 * ユーザー論理削除
	 * @param unknown $id
	 */
	public function deleteUser($id) {
		$this->id = $id;
		$this->saveField('is_del', AppConstants::FLAG_ON);
	}
}
