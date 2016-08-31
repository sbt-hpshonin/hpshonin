<?php
App::uses('MtDbComponent', 'Controller/Component');

/**
 * 承認用MTデータベースアクセスクラス
 * @author keiohnishi
 *
 */
class ApprovalMtDbComponent extends MtDbComponent {

	// コンストラクタ
    function ApprovalMtDbComponent() {
//     	$this->connect(AppConstants::MT_APPROVAL_DB_SERVER, AppConstants::MT_APPROVAL_DB_NAME, AppConstants::MT_APPROVAL_DB_USER, AppConstants::MT_APPROVAL_DB_USER_PASSWORD);
    }
}