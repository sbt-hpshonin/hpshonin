<?php
/**
 * 独自例外処理クラス<br>
 * データ取得アクション内で対象データがなかった場合にスローします。
 */
class NoDataException extends CakeException {

	public $login_flag;

	/**
	 * エラーページの表示内容を制御します。<br>
	 * 次の遷移へのアクションは、引数の[$login_flag]で制御します。<br>
	 * ディフォルトはログイン状態であるとみなしホーム画面への遷移になります。<br>
	 *
	 * @param String $message		表示するエラーメッセージを設定します。
	 * @param boolean $login_flag	ログイン画面かホーム画面かを判定します。
	 * @param number $code			httpエラーコードを設定します。
	 */
	public function __construct($message, $login_flag = false , $code = 500) {
		$this->login_flag = $login_flag;
		parent::__construct($message, $code);
	}
}