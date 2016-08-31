<?php
// 動作させるコントローラをインポート
App::import("Controller", "SampleMail");
App::uses('AppConstants', 'Lib/Constants');

/**
 * シェルのメインクラス
 * @author smurata
 *
 */
class SampleShell extends AppShell {

	public function main() {
		$str = mb_convert_encoding('cd '.AppConstants::CAKE_APP_PATH, "SJIS");
		exec($str);
		for ($i = 1 ; $i < 4 ; $i++) {
			$this->log($i." times\r\n");
			$fp = popen("start Console\cake Child {$i}", 'r');
			pclose($fp);

//			sleep(1);
		}
	}
}
?>