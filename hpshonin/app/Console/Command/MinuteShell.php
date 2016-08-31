<?php
App::uses("FileUtil", "Lib/Utils");
App::uses("BatchEMailStopProcessController", "Controller");

/**
 * 分毎バッチシェルクラス
 * @author hsuzuki
 *
 */
class MinuteShell extends AppShell {

	/**
	 * メイン処理
	 * ・バッチ異常を検知しメールを送信
	 */
	public function main() {
		
		// バッチ異常検知メール
		$controller = new  BatchEMailStopProcessController();
		$controller->execute();
	}
}
?>