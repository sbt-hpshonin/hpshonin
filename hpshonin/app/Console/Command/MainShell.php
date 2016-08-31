<?php
App::import("Lib", "Constants/AppConstants");
App::uses("BatchCleanBlogPackageController", "Controller");
App::import("Lib", "Batch");

// バッチ用の設定
CakeLog::config('batch', array(
	'engine' => 'FileLog',
	//	'types' => array('notice', 'info', 'debug', 'error'),
	'file' => "stk_batch_command".date('Ymd')
));

/**
 * シェルのメインクラス
 * @author smurata
 *
 */
class MainShell extends AppShell {
	// モデルを使用する場合は指定
	var $uses = array('BatchQueue');

	/**
	 * シェル実行
	 * ・キューテーブルから実行する情報を取得し、一つずつ実行
	 * ・実行中は結果コード：実行中にし、終われば関数の返り値を登録する。
	 */
	public function main() {
		$this->log('********** Batch Main Start **********', LOG_INFO);

		// 未実施のキューを取得
		$queues = $this->BatchQueue->find('all',
				array(
						'conditions' => array(
								'BatchQueue.result_cd'	=> array(
										AppConstants::RESULT_CD_NOT_EXECUTION
								),
								'BatchQueue.execute_datetime <=' => date("Y-m-d H:i:s"),
								// 2013.10.22 H.Suzuki Added
								'BatchQueue.is_del' => 0,
								// 2013.10.22 H.Suzuki Added END
						),
						'order'		=> array(
								// 2013.10.23 H.Suzuki Chaged
								// 'BatchQueue.execute_datetime' => 'desc'
								'BatchQueue.execute_datetime' => 'asc'
								// 2013.10.23 H.Suzuki Chaged END
						),
						'recursive' => 0
				)
		);

		// 全てのキューで以下を実施
		foreach ($queues as $queue) {
			// 実行しない条件を列挙
			if ($this->isPackageBelongSameProject($queue)) continue;

			// キューを実行中に変更
			$queue['BatchQueue']['result_cd'] = AppConstants::RESULT_CD_EXECUTION;
			
			// 2013.10.18 H.Suzuki Added
			$queue['BatchQueue']['start_at'] = date('Y-m-d H:i:s');
			// 2013.10.18 H.Suzuki Added END
			$this->BatchQueue->save($queue);

			// 非同期で起動
			$command = "start cmd.exe /c Console\cake Child {$queue['BatchQueue']['id']}";
			$this->log('popen = ['.$command.']', LOG_INFO);
			$fp = popen($command, 'r');
			pclose($fp);

			// 一秒まつ(気持ち)
			sleep(1);
 		}
		$this->log('********** Batch Main End   **********', LOG_INFO);
	}

	/**
	 *
	 * @param BatchQueue $queue
	 */
	private function isPackageBelongSameProject($queue) {
		// パッケージ作成および公開であった場合、同一プロジェクトか確認
		if ($queue['BatchQueue']['batch_cd'] === Batch::BATCH_CD_PACKAGE_CREATE ||
		$queue['BatchQueue']['batch_cd'] === Batch::BATCH_CD_RELEASE) {

			$options = array(
					'conditions' => array(
							'BatchQueue.result_cd'	=> AppConstants::RESULT_CD_EXECUTION,
							'BatchQueue.batch_cd' => $queue['BatchQueue']['batch_cd'],
							'Package.project_id' => $queue['Package']['project_id'],
							'BatchQueue.is_del' => AppConstants::FLAG_OFF
					),
					'recursive' => 0
			);
			$cnt = $this->BatchQueue->find('count', $options);
			if ($cnt > 0) {
				$this->log('same project!!');
				return true;
			}
		}
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Object::log()
	 * 以下を$typeで指定
	 * 　LOG_DEBUG:デバッグ(値の確認など、自由なメッセージ)
	 * 　LOG_INFO:情報(成功時のメッセージなど)
	 * 　LOG_WARNING:警告(運用上のエラー)
	 * 　LOG_ERR:エラー(プログラム上のエラー)
	 * 　LOG_EMERG:致命的エラー(システム上のエラー)
	 *
	 * 現状はメッセージの加工は行っていません。
	 * 設定でどんな$typeでも"stk_batch_yyyymmdd.log"に出力します。
	 */
	public function log($msg, $type = LOG_ERR) {
		// 何か文字列突っ込む

		parent::log($msg, $type);
	}
}
?>