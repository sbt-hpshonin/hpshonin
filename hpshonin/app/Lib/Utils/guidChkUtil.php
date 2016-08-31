<?php
/**
 * GUIDを用いた画面遷移異常チェック処理
 *
 * @author hsuzuki
 *
 */
class guidChkUtil {

	/**
	 * GUIDの生成関数
	 * @param なし
	 * @return GUID
	 */
	public static function getGUID(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
			return $uuid;
		}
	}
	
	
	/**
	 * GUIDの値セット関数
	 * @param なし
	 * @return hidden表示用文字列
	 * @author hsuzuki
	 */
	public function putGuid(){
		
		$mgid = self::getGUID();
		// SessionComponent::write("mgid",$mgid);
		$_SESSION["mgid"] = $mgid;
		return "<input type='hidden' name='mgid' value='{$mgid}'>";
	}
	
	
	/**
	 * GUIDの値チェック関数
	 * @param なし
	 * @return true:OK/false:NG
	 * @author hsuzuki
	 */
	public function chkGUID(){
    	
   		$smgid = "";
   		$hmgid = "";
   		if ($this->Session->read("mgid")) {
   			$smgid = $this->Session->read("mgid");
   		}
   		if (isset($this->request->data["mgid"])) {
   			$hmgid = $this->request->data["mgid"];
   		}
   		if($smgid != $hmgid){
   			return false;
   		}
   		
   		return true;
	}
}
?>