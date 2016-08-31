<?php
App::import('Vendor', 'Crypt_Blowfish', array('file' => 'Crypt' . DS . 'Blowfish.php'));
/**
 * パスワード処理
 * @author smurata
 *
 */
class PasswordUtil {
	/** パスワードキー */
	const PASSWORD_KEY = "jDSfg;as894jSD98jeimaSD8";

	/**
	 * パスワードを暗号化する。
	 * @param unknown $password パスワード
	 * @return string 暗号化されたパスワード
	 */
	public static function encode($password) {
		$blowfish = new Crypt_Blowfish(PasswordUtil::PASSWORD_KEY);
		$encrypt = "";
		if (!empty($password)) {
			$encrypt= base64_encode($blowfish->encrypt($password));
		}

		return $encrypt;
	}

	/**
	 * パスワードを復号する。
	 * @param unknown $encrypt	暗号化されたパスワード
	 * @return string 復号されたパスワード
	 */
	public static function decode($encrypt) {
		$blowfish = new Crypt_Blowfish(PasswordUtil::PASSWORD_KEY);
		$decrypt = $blowfish->decrypt(base64_decode($encrypt));

		return $decrypt;
	}
}
?>