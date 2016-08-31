<?php

/**
 * 文字列操作ユーティリティクラスです。
 *
 * @author tasano
 *
 */
class StringUtil {

	/** 復帰・改行 */
	const CRLF = "\r\n";

	/** 復帰 */
	const CR   = "\r";

	/** 改行 */
	const LF   = "\n";

	/**
	 * 改行コードを統一します。
	 *
	 * @param  unknown $str 文字列
	 * @return mixed        改行コードが変換された文字列
	 */
	public static function replaceLineFeedCode($str) {
		return str_replace(array( self::CRLF, self::CR, self::LF), self::LF,  $str);
	}

	/**
	 * 比較します。
	 *
	 * @param  unknown $str1 文字列１
	 * @param  unknown $str2 文字列２
	 * @return number        str1 が str2 よりも小さければ < 0 を、str1が str2よりも大きければ > 0 を、 等しければ 0 を返します。
	 */
	public static function compare($str1, $str2) {
		return strcmp($str1, $str2);
	}

	/**
	 * トリムします。
	 *
	 * @param  unknown $str 文字列
	 * @param  unknown $del 削除される文字
	 * @return string       変換後の文字列
	 */
	public static function trim($str, $del) {
		return trim($str, $del);
	}

	/**
	 * 文字列の最初から削除文字列を一回のみ除去します。
	 *
	 * @param  unknown $str1  検索を行う文字列
	 * @param  unknown $str2  削除文字列
	 * @return string|unknown 削除された文字列
	 */
	public static  function ltrimOnce($str1, $str2) {
		// 指定した文字列で始まる場合
		if (self::startsWith($str1, $str2)) {
			return substr($str1, strlen($str2));
		} else {
			return $str1;
		}
	}

	/**
	 * 指定された開始文字列で始まるか判定します。
	 *
	 * @param  string $haystack 検索を行う文字列
	 * @param  string $needle   開始文字列
	 * @return                  true：開始文字列で始まる、false：開始文字列で始まらない
	 */
	public static function startsWith($haystack, $needle){
		return strpos($haystack, $needle, 0) === 0;
	}


	/**
	 * URLとして使用できるか判定します。
	 *
	 * @param unknown $text 文字列
	 * @return boolean true：使用可能、false：使用不可
	 */
	public static function validForSiteUrl($text) {

		// 半角英数字(記号含む)で構成されるかチェック
		if (self::isAlNumSign($text) === false) {
			return false;
		}

		// 使用できない文字が含まれているかチェック
		if (self::hasProhibit($text)) {
			return false;
		}

		return true;
	}

	/**
	 * 半角英数字(記号含む)で構成されているかチェックします。
	 *
	 * @param unknown $text 文字列
	 * @return boolean true：すべて半角英数字(記号含む)である、false：すべてではない
	 */
	public static function isAlNumSign($text) {
		if (preg_match("/^[a-zA-Z0-9!-\/:-@¥[-`{-~]+$/",$text)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 使用できない文字が含まれているかチェック
	 * @param unknown $text
	 */
	public static function hasProhibit($text) {

		CakeLog::debug('StringUtil::hasProhibit');
		CakeLog::debug('  $text：[' . $text . ']');

		if (ereg("[:*?\"<>|#{}%&~+]", $text)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 文字を含むかチェックします。
	 *
	 * @param  unknown $haystack 包有する文字列
	 * @param  unknown $needle   包有される文字列
	 * @return boolean           true：含む、false：含まない
	 */
	public static function match($haystack, $needle) {

		CakeLog::debug('StringUtil::match');
		CakeLog::debug('  $haystack：[' . $haystack . ']');
		CakeLog::debug('  $needle  ：[' . $needle . ']');

		if (strpos($haystack, $needle) === false) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * メッセージを取得します
	 *
	 * @param  unknown $message  メッセージ
	 * @param  unknown $replace1 置換文字1
	 * @param  unknown $replace2 置換文字2
	 * @return string  置換後のメッセージ
	 */
	public static function getMessage2($message, $replace1, $replace2) {

		$work = str_replace('{0}',  self::encode($replace1), $message);
		$work = str_replace('{1}',  self::encode($replace2), $work);

		return $work;
	}

	/**
	 * メッセージを取得します
	 *
	 * @param  unknown $message  メッセージ
	 * @param  unknown $replace1 置換文字1
	 * @param  unknown $replace2 置換文字2
	 * @return string  置換後のメッセージ
	 */
	public static function getMessage($message, $replace) {

		CakeLog::debug('StringUtil::getMessage');
		CakeLog::debug('  $message：[' . $message . ']');
		CakeLog::debug('  $replace：[' . $replace . ']');

		$work =  str_replace('{0}',  self::encode($replace), $message);

		return $work;
	}

	/**
	 * エンコードします。
	 *
	 * @param  unknown $str エンコード前
	 * @return string       エンコード後
	 */
	public static function encode ($str) {
		return mb_convert_encoding($str, 'UTF8', 'auto');
	}

	/**
	 * 文字列から文字列を除去します。
	 *
	 * @param  unknown $str1 文字列
	 * @param  unknown $str2 除去される文字列
	 * @return mixed         成否
	 */
	public static function remove($str1, $str2) {
		return str_replace($str2, '', $str1);
	}

}

?>