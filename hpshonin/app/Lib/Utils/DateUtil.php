<?php
/**
 * 日付用のユーティリティクラス
 * @author smurata
 *
 */
class DateUtil {
	/**
	 * 日付フォーマットを取得
	 * @param string $date		日付文字列
	 * @param string $format	日付フォーマット
	 */
	public static function dateFormat($date, $format) {
		if( $date == ""){
			return;
		}
		
		$datetime = new DateTime($date);
		echo $datetime->format($format);
	}
}
?>