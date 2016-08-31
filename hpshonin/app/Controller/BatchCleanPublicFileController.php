<?php
App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');

/**
 * 公開サイトファイル掃除バッチ
 *
 * @author smurata
 *
*/
class BatchCleanPublicFileController extends BatchAppController {

	/** モデルを登録 */
	public $uses = array('Project');

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();
	}

	/**
	 * 実行
	 *
	 * @return string 結果コード
	 */
	public function execute() {

		$this->log('公開サイトファイル掃除バッチを開始しました。', LOG_INFO);

		// プロジェクトを取得
		$project_list = $this->Project->getProjects();

		$this->log('対象プロジェクト↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		$this->log($project_list);
		$this->log('対象プロジェクト↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if (empty($project_list)) {
			$this->log('プロジェクトが存在しません。', LOG_DEBUG);
			$this->log('公開サイトファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功)
		}

		// エラーフラグ
		$error_flg = false;

		foreach ($project_list as $project) {

			// パッケージIDを取得
			$package_id = $project['Project']['public_package_id'];
			$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);

			// 内部実行
			if  ($this->executeInner($project) === false) {
				// エラーフラグを立てる
				$error_flg = true;
			}
		}

		if ($error_flg == true) {
			$this->log('公開サイトファイル掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('公開サイトファイル掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功)
		}
	}

	/**
	 * 内部処理
	 *
	 * @param unknown $project プロジェクト
	 * @return boolean         成否
	 */
	private function executeInner($project) {

		$this->log('公開サイトファイル掃除バッチ(内部) 開始', LOG_DEBUG);

		// パッケージIDを取得
		$package_id = $project['Project']['public_package_id'];
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		if (empty($package_id)) {
			$this->log('プロジェクトは未公開です。', LOG_ERR);
			$this->log('公開サイトファイル掃除バッチ(内部) 正常終了', LOG_DEBUG);
			return true;
		}

		// サイトURLを取得
		$site_url = $project['Project']['site_url'];
		$this->log('サイトURL名：[' . $site_url . ']', LOG_DEBUG);
		if (empty($site_url)) {
			$this->log('サイトURL名が不正です。', LOG_ERR);
			$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
			return false;
		}

		// 全公開フォルダに対して処理を実行
		$publish_paths = self::getDirectorPublishPathList();
		$this->log('対象公開パス↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		$this->log($publish_paths);
		$this->log('対象公開パス↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		foreach($publish_paths as $publish_path) {
			// サイトURLの物理パスの存在チェック
			$site_path = $publish_path . DS . $site_url;
			if (!FileUtil::exists($site_path)) {
				$this->log('サイトURLの物理パスが存在しません。サイトパス：[' . $site_path . ']', LOG_ERR);
				$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
				return false;
			}

			$dirs = self::getDeleteDirectoryList($site_path, $package_id);
			if (empty($dirs)) {
				$this->log('削除する過去の公開ファイルは存在しません。：[' . $package_id . ']', LOG_DEBUG);
			} else {
				$this->log('削除対象ディレクトリ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
				$this->log($dirs);
				$this->log('削除対象ディレクトリ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

				foreach ($dirs as $dir) {
					$filePath = $site_path.DS.$dir;
					if(!FileUtil::rmdirAll($filePath)) {
						$this->log('公開サイトのファイル削除に失敗しました。ファイルパス：[' . $filePath. ']', LOG_ERR);
						$this->log('公開サイトファイル掃除バッチ(内部) 異常終了', LOG_DEBUG);
						return false;;
					}
				}
			}
		}

		$this->log('登録関連ファイル掃除バッチ(内部) 正常終了', LOG_DEBUG);
		return true;
	}

	/**
	 * 公開用フォルダリストを取得
	 *
	 * @return multitype:mixed 公開用フォルダリスト
	 */
	private function getDirectorPublishPathList() {
		$ret = array();
		for($i = 1; ; $i++) {
			$path = constant('AppConstants::DIRECTOR_PUBLISH_PATH_' . $i);
			if (!$path) {
				break;
			}
			$ret[] = $path;
		}
		return $ret;
	}


	/**
	 * 削除対象ディレクトの取得
	 * @param unknown $site_path
	 * @param unknown $public_package_id
	 * @return number|multitype:unknown
	 */
	private function getDeleteDirectoryList($site_path, $public_package_id) {

		$this->log('公開サイトファイル掃除バッチ(削除対象ディレクトの取得) 開始', LOG_DEBUG);


		// サイトフォルダのディレクトリ群から削除対象を絞り込む
		$dirs = scandir($site_path, 1);
		usort($dirs, 'comp');

		$cnt = count($dirs);
		$rev = 0;
		$del_dirs = array();
		// 最新から比較する
		for($i = $cnt - 1 ; $i >= 0 ; $i--) {
			$filePath = $site_path.DS.$dirs[$i];
			if (!is_numeric($dirs[$i])) {
				$this->log('数字以外は対象外：ディレクトリ['.$dirs[$i].']', LOG_DEBUG);
				continue;
			} else if (!file_exists($filePath) or !is_dir($filePath)) {
				$this->log('ファイルは対象外：ディレクトリ['.$dirs[$i].']', LOG_DEBUG);
				continue;
			} else if (intval($dirs[$i]) == $public_package_id) {
				$this->log('現在公開中のディレクトリは対象外：ディレクトリ['.$dirs[$i].']', LOG_DEBUG);
				continue;
			} else if ( $rev < AppConstants::REVISION_NUM_STAY_PUBLIC_DIRECTORY) {
				$this->log('最近のリビジョン数分は対象外：ディレクトリ['.$dirs[$i].']', LOG_DEBUG);
				$rev++;
				continue;
			}
			$del_dirs[] = $dirs[$i];
		}

		$this->log('公開サイトファイル掃除バッチ(削除対象ディレクトの取得) 正常終了', LOG_DEBUG);

		return $del_dirs;
	}

	/**
	 * ソートの関数
	 * @param unknown $a
	 * @param unknown $b
	 * @return number
	 */
	function comp($a, $b) {
		if ($a == $b) {
			return 0;
		} else if ($a < $b) {
			return -1;
		} else {
			return 1;
		}
	}
}