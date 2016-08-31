<?php
App::uses('AppHelper', 'View/Helper');
App::uses('Status',	'Lib');

/**
 * ステータスに関連したヘルパーを提供します
 *
 * @author admin
 *
 */
class StatusHelper extends AppHelper {

	/**
	 * 引数のコードに紐付く表示テキストを取得します。
	 *
	 * @param unknown $aStatusCode
	 * @return string
	 */
	public function obtainStatusText( $aStatusCode ) {
		return Status::getName( $aStatusCode );
	}
}
