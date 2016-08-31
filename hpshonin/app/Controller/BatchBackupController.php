<?php
App::uses('BatchAppController', 'Controller');

// 公開予定通知メール クラス
class BatchBackupController extends BatchAppController {

	/** mysqldumpのフルパス**/
	const DUMPCMD  = "C:/Program Files/MySQL/MySQL Server 5.6/bin/mysqldump.exe";

	/** バックアップ用ディレクトリ **/
	const DUMPPATH = "C:/backup/database/Daily";
	
	/** 保存するファイル数 **/
	const PRESERVE_CNT = 7;
	
	/**
	 * 実行
	 */
	public function execute() {
		$this->log(sprintf("ＤＢバックアップバッチを開始しました。" ), LOG_INFO);

		$result = $this->execute_core();

		$this->log(sprintf("ＤＢバックアップバッチを終了しました。(%d)" ,$result), LOG_INFO);
		return $result;
	}

	
	/**
	 * DBバックアップexecute_core
	 * @param なし
	 * @author hsuzuki
	 */
	private function execute_core(){

		include_once APP . 'Config' . DS . 'database.php';
		$database_config = New DATABASE_CONFIG;

		while(True){
			// STK DBバックアップ
			$result = self::exec_dump($database_config->default);
			if($result == AppConstants::RESULT_CD_FAILURE){
				break;
			}
			
			// MT DBバックアップ
			$result = self::exec_dump($database_config->mt_edit_db);
			if($result == AppConstants::RESULT_CD_FAILURE){
				break;
			}
			
			// STK 古いダンプファイルの削除
			$result = self::clean_dump($database_config->default);
			if($result == AppConstants::RESULT_CD_FAILURE){
				break;
			}
			
			// MT 古いダンプファイルの削除
			$result = self::clean_dump($database_config->mt_edit_db);
			if($result == AppConstants::RESULT_CD_FAILURE){
				break;
			}
				
				
			return AppConstants::RESULT_CD_SUCCESS;
		}

		return AppConstants::RESULT_CD_FAILURE;
	}
	
	
	/**
	 * DBバックアップ処理 (mysqldump)
	 * @param $db_conf database.phpのdb設定
	 * @author hsuzuki
	 */
	private function exec_dump($db_conf){

		$host = $db_conf["host"];
		$user = $db_conf["login"];
		$pass = $db_conf["password"];
		$db   = $db_conf["database"];
		
		$outputfile = self::DUMPPATH . "/{$host}_{$db}_". date("Ymd") .".dump";
			
		$cmd = '"'. self::DUMPCMD .'"' 
			." -c "
			." --lock-tables "
			." --host={$host} "
			." --user={$user} "
			." --password={$pass} {$db} "
			." > \"{$outputfile}\"  2>NUL";

		system( $cmd ,$return_var) ;
		if( $return_var == 0 ){
			// OK
			return AppConstants::RESULT_CD_SUCCESS;
		}
		else{
			// NG
			return AppConstants::RESULT_CD_FAILURE;
		}
	}
	
	
	/**
	 * 古いバックアップの削除処理
	 * @param $db_conf database.phpのdb設定
	 * @author hsuzuki
	 */
	private function clean_dump	($db_conf){
	
		$host = $db_conf["host"];
		$db   = $db_conf["database"];
	
		$outputfile = self::DUMPPATH . "/{$host}_{$db}_*.dump";
		$entry = glob($outputfile);
		if($entry === false){
			$this->log(sprintf("ファイル情報取得に失敗しました。(%s)" ,$outputfile), LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE;
		}
		rsort($entry);
		
		foreach ($entry as $key => $filename) {
			if($key < self::PRESERVE_CNT){
				// 削除対象外
				continue;
			}
			
			// 削除対象
			if(unlink($filename) == false){
				$this->log(sprintf("削除に失敗しました。(%s)" ,$filename), LOG_ERR);
				return AppConstants::RESULT_CD_FAILURE;
			}
			
			$this->log(sprintf("削除しました。(%s)" ,$filename), LOG_INFO);
		}
		
		return AppConstants::RESULT_CD_SUCCESS;
	}
}