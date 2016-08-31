<?php
App::uses('AppModel', 'Model');

/**
 * Authenticationsモデルクラス
 *
 * @author hsuzuki
 *
 */
class Authentication extends AppModel {
	public $name = 'Authentication';

	var $useTable = 'authentications';
}
?>