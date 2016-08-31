<?php
//App::import('Vendor', 'Crypt_Blowfish', array('file' => 'Crypt' . DS . 'Blowfish.php'));
//App::import("Lib", "Constants/AppConstants");
App::uses('PasswordUtil', 'Lib/Utils');

class CryptShell extends AppShell {

	public function main() {
//		$key = 'secret keyword';
//
//		$blowfish = new Crypt_Blowfish(AppConstants::PASSWORD_KEY);
		$password = '123456789ABCDEFG';


		// 暗号化
//		$encrypt= base64_encode($blowfish->encrypt($password));
		$encrypt = PasswordUtil::encode($password);
		echo 'ango:'.$encrypt."\r\n";

		// 復号化
//		$decrypt = $blowfish->decrypt(base64_decode($encrypt));
		$decrypt = PasswordUtil::decode($encrypt);
		echo 'fukugo:'.$decrypt."\r\n";
	}
}
?>