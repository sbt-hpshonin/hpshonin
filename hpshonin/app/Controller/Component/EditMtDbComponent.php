<?php
App::uses('MtDbComponent', 'Controller/Component');

/**
 * 編集用MTデータベースアクセスクラス
 * @author keiohnishi
 *
 */
class EditMtDbComponent extends MtDbComponent {

	// コンストラクタ
    function EditMtDbComponent() {
    	$this->connect(AppConstants::MT_EDIT_DB_SERVER, AppConstants::MT_EDIT_DB_NAME, AppConstants::MT_EDIT_DB_USER, AppConstants::MT_EDIT_DB_USER_PASSWORD);
    }
}