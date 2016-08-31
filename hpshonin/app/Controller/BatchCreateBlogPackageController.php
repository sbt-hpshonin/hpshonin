<?php

App::uses('BatchPackageController', 'Controller');
App::uses('EditMtDbComponent', 'Controller/Component');
App::uses('Package', 'Model');
App::uses('MtImageFile', 'Model');
App::uses('Status', 'Lib');
App::uses('AppConstants', 'Lib/Constants');
App::uses('FileUtil', 'Lib/Utils');
App::uses('StringUtil', 'Lib/Utils');


/**
 * ブログパッケージ作成バッチ
 *
 * @author tasano
 *
*/
class BatchCreateBlogPackageController extends BatchPackageController {

	/** 編集用MTデータベースアクセスコンポーネント */
	private $editMtDbComponent;

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// 作成・公開区分
		$this->create_publish_div = self::CREATE;
		// バッチ名を設定
		$this->batch_name = 'ブログパッケージ作成バッチ';
		// 処理成功時のステータスコードを設定
		$this->status_cd_on_normal = Status::STATUS_CD_PACKAGE_ENTRY; // '01'(パッケージ登録);
		// 処理失敗時のステータスコードを設定
		$this->status_cd_on_abend  = Status::STATUS_CD_PACKAGE_READY_REJECT; // '91'(パッケージ登録却下)

		// コンポーネント初期化
		$this->editMtDbComponent = new EditMtDbComponent();
	}

	/**
	 * 実行
	 *
	 * @return boolean 成否
	 */
	function execute_inner() {

		$this->log('ブログパッケージ作成(内部) 開始', LOG_DEBUG);
		$this->log('パッケージID：[' . $this->id . ']', LOG_DEBUG);

		// ---------
		// 事前準備
		// ---------

		// パッケージを取得
		$package = $this->Package->findByPackageId($this->id);
		if (empty($package)) {
			$this->log('パッケージ情報の取得に失敗しました。パッケージID：[' . $this->id . ']', LOG_ERR);
			return false;
		}
		//$this->log($package, LOG_DEBUG);

		// プロジェクトIDを取得
		$project_id = $package['Project']['id'];
		$this->log('プロジェクトID：[' . $project_id . ']', LOG_DEBUG);

		// サイトURLを取得
		$site_url = $package['Project']['site_url'];
		$this->log('サイトURL：[' . $site_url . ']', LOG_DEBUG);

		// 公開されているパッケージID
		$public_package_id = $package['Project']['public_package_id'];
		$this->log('公開パッケージID：[' . $public_package_id . ']', LOG_DEBUG);
		if (is_null($public_package_id)) {
			$public_package_id = 0;
		}
		
		// MTプロジェクトを取得
		$mtProject = $this->MtProject->getMtProject($project_id);
		if(empty($mtProject)) {
			$this->log('MTプロジェクトの取得失敗しました。プロジェクトID：[' . $project_id . ']', LOG_ERR);
			return false;
		}

		// 編集用MTプロジェクトID(ブログID)を取得
		$edit_mt_project_id = $mtProject['MtProject']['edit_mt_project_id'];
		$this->log('編集用MTプロジェクトID：[' . $edit_mt_project_id . ']', LOG_DEBUG);

		// 編集用フォルダを編集
		$edit_path = $this->editMtDbComponent->getBlogSitePath($edit_mt_project_id);
		$this->log('編集用フォルダ：[' . $edit_path . ']', LOG_DEBUG);

		// 作業用フォルダを編集
		$work_path = AppConstants::DIRECTOR_WORK_PATH . DS . $this->id . DS . $site_url . DS . self::BLOG;
		if (FileUtil::mkdir($work_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('作業用フォルダ：[' . $work_path . ']', LOG_DEBUG);

		// 承認用フォルダを編集
		$approval_path = AppConstants::DIRECTOR_APPROVAL_PATH . DS . $this->id . DS . $site_url . DS . self::BLOG;
		if (FileUtil::mkdir($approval_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $approval_path . ']', LOG_ERR);
			return false;
		}
		$this->log('承認用フォルダ：[' . $approval_path . ']', LOG_DEBUG);

		// ステージング用フォルダを生成
		$staging_path = AppConstants::DIRECTOR_STAGING_PATH . DS . $site_url . DS . $public_package_id . DS . self::BLOG;
		if (FileUtil::mkdir($staging_path) === false) {
			$this->log('フォルダの作成に失敗しました。フォルダ名：[' . $staging_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ステージング用フォルダ：[' . $staging_path . ']', LOG_DEBUG);

		// ----------
		// 1. db更新
		// ----------
		$this->log('1. db更新', LOG_DEBUG);
		if ($this->MtEntryAppBak->copy($project_id) === false) {
			$this->log('テーブルの更新に失敗しました。', LOG_ERR);
			return false;
		}
		$package_id = $this->id;

		// 選択から漏れた記事追加処理
		$mt_entry_app_baks = $this->__getMtEntryAddList($project_id , $package_id);
		$this->log("選択から漏れた追加記事:" . count($mt_entry_app_baks)  . "件", LOG_DEBUG);
		foreach($mt_entry_app_baks as $mt_entry) {
			$insertdata['is_del'] = AppConstants::FLAG_FALSE;
			$insertdata['created_user_id'] = AppConstants::USER_ID_SYSTEM;
			$insertdata['modified_user_id'] = AppConstants::USER_ID_SYSTEM;
			$insertdata['package_id'] = $package_id;
			$insertdata['subject'] = $mt_entry['eab']['entry_title'];
			$insertdata['contents']  =$mt_entry['eab']['entry_text'];
			$insertdata['post_modified'] = $mt_entry['eab']['entry_modified_on'];
			$insertdata['contents_more'] = $mt_entry['eab']['entry_text_more'];
			$insertdata['edit_mt_post_id'] = $mt_entry['eab']['entry_id'];
			$insertdata['modify_flg'] = AppConstants::MODIFY_FLG_ADD ;

			$this->MtPost->create();
			if(!$this->MtPost->save($insertdata, false)) {
				$this->log('テーブルの更新に失敗しました。', LOG_ERR);
				return false;
			}
		}

		// 選択から漏れた記事追加処理
		$mt_entry_app_baks = $this->__getMtEntryModifyList($project_id, $package_id);
		$this->log("選択から漏れた更新記事:" . count($mt_entry_app_baks)  . "件", LOG_DEBUG);
		foreach($mt_entry_app_baks as $mt_entry) {
			$insertdata['is_del'] = AppConstants::FLAG_FALSE;
			$insertdata['created_user_id'] = AppConstants::USER_ID_SYSTEM;
			$insertdata['modified_user_id'] = AppConstants::USER_ID_SYSTEM;
			$insertdata['package_id'] = $package_id;
			$insertdata['subject'] = $mt_entry['eab']['entry_title'];
			$insertdata['contents']  =$mt_entry['eab']['entry_text'];
			$insertdata['post_modified'] = $mt_entry['eab']['entry_modified_on'];
			$insertdata['contents_more'] = $mt_entry['eab']['entry_text_more'];
			$insertdata['edit_mt_post_id'] = $mt_entry['eab']['entry_id'];
			$insertdata['modify_flg'] = AppConstants::MODIFY_FLG_MOD ;

			$this->MtPost->create();
			if(!$this->MtPost->save($insertdata, false)) {
				$this->log('テーブルの更新に失敗しました。', LOG_ERR);
				return false;
			}
		}


		// ------------------
		// 2. ファイルコピー
		// ------------------
		$this->log('2. ファイルコピー', LOG_DEBUG);

		// 編集用フォルダ→作業用フォルダ
		if (FileUtil::rmdirAll($work_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $work_path . ']', LOG_ERR);
			return false;
		}

		if (FileUtil::dirCopy($edit_path, $work_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $edit_mt_project_id . ']', LOG_ERR);
			$this->log('  コピー先：[' . $work_path . ']', LOG_ERR);
			return false;
		}

		// ファイルリストを取得
		$file_list = FileUtil::getFileListFromDirAndRemoveString($work_path, $work_path);
		if ($file_list === false) {
			$this->log('ファイルリストの取得に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダパス名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		if (empty($file_list)) {
			$this->log('ファイルが存在しません。', LOG_ERR);
			$this->log('  対象フォルダパス名：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		$this->log('ファイルリスト↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($file_list, LOG_DEBUG);
		$this->log('ファイルリスト↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		// ファイル名の妥当性チェック
		$extentions = explode(",", AppConstants::BAN_EXT_LIST);
		for ($i = 0; $i < count($file_list); $i++) {
			$file = $file_list[$i];
			if (StringUtil::validForSiteUrl($file) === false) {
				$this->log('URLとして使用できないファイルが存在します。', LOG_WARNING);
				$this->log('  ファイル名：[' . $file . ']', LOG_WARNING);
				// メッセージを設定
				$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_CHAR , '/' . $site_url . '/' . self::BLOG . '/' . str_replace("\\", "/", $file));
				return true; // 業務エラー
			}

			// ファイル拡張子がNGだった場合
//			if (in_array(strtolower(FileUtil::getExtention($file)), $extentions)) {
//				$this->log('アップロードできない拡張子のファイルが存在します。', LOG_ERR);
//				$this->log('  ファイル名：[' . $file . ']', LOG_ERR);
//				$this->message = StringUtil::getMessage(MsgConstants::ERROR_WRONG_FILE_EXT , '/' . $site_url . '/' . self::BLOG . '/' . str_replace("\\", "/", $file));
//
//				return true; // 業務エラー
//			}
		}

		// 差分があるかチェック
		if (FileUtil::hasDiffDir($work_path, $staging_path,
				array(self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH,
						AppConstants::URL_HOST_REPLACE_ORIGINAL . self::SLASH . $site_url . self::SLASH,
						AppConstants::URL_PATH_REPLACE_ORIGINAL . self::SLASH . $site_url . self::SLASH),
				array(self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH,
						AppConstants::URL_HOST_REPLACE_STAGING . self::SLASH . $site_url . self::SLASH,
						AppConstants::URL_PATH_REPLACE_STAGING . self::SLASH . $site_url . self::SLASH)) === false) {
			$this->log('記事内容に差異がありません。サイトURL：[' . $site_url . ']', LOG_ERR);
			// メッセージを設定
			$this->message = MsgConstants::ERROR_NO_CHANGE_POST_AT_ALL;
			return true;
		}

		// 編集用フォルダ→承認用フォルダ
		if (FileUtil::rmdirAll($approval_path) === false) {
			$this->log('フォルダの削除に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $work_path . ']', LOG_ERR);
			return false;
		}
		if (FileUtil::dirCopy($work_path, $approval_path) === false) {
			$this->log('フォルダのコピーに失敗しました。', LOG_ERR);
			$this->log('  コピー元：[' . $work_path . ']', LOG_ERR);
			$this->log('  コピー先：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		// -----------------------------------
		// 3. パス置換処理 ※今まで通りルール
		// -----------------------------------
		$this->log('3. パス置換処理', LOG_DEBUG);

		// 置換前(http://(編集サイトのURL)/(サイトのURL)/blog)
		$tartget = self::EDIT_SITE_URL
					. self::SLASH . $site_url
					. self::SLASH . self::BLOG;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列(http://(承認サイトのURL)/(パッケージID)/(サイトURL)/blog)
		$replace = self::APPROVAL_SITE_URL
					. self::SLASH . $this->id
					. self::SLASH . $site_url
					. self::SLASH . self::BLOG;
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($approval_path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

// (S) 2013.11.03 murata
		$this->log('3. パス置換処理(ホスト名)', LOG_DEBUG);
		// 置換前((編集サイトのパス)/(サイトのURL)/blog)
		$tartget = AppConstants::URL_HOST_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列((承認サイトのパス)/(パッケージID)/(サイトURL)/blog)
		$replace = AppConstants::URL_HOST_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($approval_path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $approval_path . ']', LOG_ERR);
			return false;
		}

		$this->log('3. パス置換処理(サイトパス)', LOG_DEBUG);
			// 置換前((編集サイトのパス)/(サイトのURL)/blog)
		$tartget = AppConstants::URL_PATH_REPLACE_ORIGINAL
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換前：[' . $tartget . ']', LOG_DEBUG);

		// 置換後列((承認サイトのパス)/(パッケージID)/(サイトURL)/blog)
		$replace = AppConstants::URL_PATH_REPLACE_APPROVAL
		. self::SLASH . $this->id
		. self::SLASH . $site_url
		. self::SLASH;
		$this->log('置換後：[' . $replace . ']', LOG_DEBUG);

		// htmlファイルを一括置換
		if (FileUtil::replaceContentsAll($approval_path, $tartget, $replace, self::EXT_HTML) == false) {
			$this->log('htmlファイルを一括置換に失敗しました。', LOG_ERR);
			$this->log('  対象フォルダ：[' . $approval_path . ']', LOG_ERR);
			return false;
		}
// (E) 2013.11.03

		// -----------------
		// 更新ファイル登録
		// -----------------
		$this->log('更新ファイル登録', LOG_DEBUG);

		foreach ($file_list as $file_path_name) {

			// ファイルサイズ取得
			$file_size = FileUtil::size($work_path . DS . $file_path_name);
			if ($file_size === false) {
				$this->log('ファイルサイズの取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// ファウル更新日時取得
			$file_time = FileUtil::filetime($work_path . DS . $file_path_name);
			if ($file_time === false) {
				$this->log('ファイル更新日時の取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// 更新フラグ判定（'0':追加/'1':変更）
			if (FileUtil::exists($staging_path . DS . $file_path_name)) {

				if (FileUtil::hasDiffArray($work_path . DS . $file_path_name,
									  $staging_path . DS . $file_path_name,
									  array(self::EDIT_SITE_URL . self::SLASH . $site_url . self::SLASH,
									  		AppConstants::URL_HOST_REPLACE_ORIGINAL . self::SLASH . $site_url . self::SLASH,
									  		AppConstants::URL_PATH_REPLACE_ORIGINAL . self::SLASH . $site_url . self::SLASH),
									  array(self::STAGING_SITE_URL . self::SLASH . $site_url . self::SLASH,
									  		AppConstants::URL_HOST_REPLACE_STAGING . self::SLASH . $site_url . self::SLASH,
									  		AppConstants::URL_PATH_REPLACE_STAGING . self::SLASH . $site_url . self::SLASH))) {
					$modify_flg = AppConstants::MODIFY_FLG_MOD; // 変更
				} else {
					$modify_flg = AppConstants::MODIFY_FLG_NO_MOD; // 変更なし
					//変更ファイルは差分リストに表示させない
					continue;
				}

			} else {
				$modify_flg = AppConstants::MODIFY_FLG_ADD; // 追加
			}

			// パラメータを作成
			$data = array (
					'id'				=> null,									// コンテンツファイルID
					'is_del'			=> AppConstants::FLAG_OFF,					// 削除フラグ
					'created_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 作成者ID
					'modified_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 更新者ID
					'package_id'		=> $this->id,								// パッケージID
					'file_path'			=> DS . $site_url . DS . self::BLOG . DS . $file_path_name,	// ファイルパス名
					'file_size'			=> $file_size,								// サイズ
					'file_modified'		=> $file_time,								// ファイル更新日時
					'modify_flg'		=> $modify_flg								// 更新フラグ
			);

			// コンテンツファイルへ登録
			$result = $this->ContentsFile->save($data);
			//$this->log($result, LOG_DEBUG);
			if ($result === false) {
				$this->log('コンテンツファイルへの登録に失敗しました。', LOG_ERR);
				$this->log('データ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_ERR);
				$this->log($data, LOG_ERR);
				$this->log('データ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_ERR);
				return false;
			}
		}

		// ------------------------
		// 4. 削除の検知処理を追加
		// ------------------------
		$this->log('4. 削除の検知処理を追加', LOG_DEBUG);

		// ステージング用フォルダのファイルリストを取得
		$staging_file_list = FileUtil::getFileListFromDirAndRemoveString($staging_path, $staging_path);

		// 削除ファイルリストを取得
		$delete_file_list = $this->getDeleteFileList($file_list, $staging_file_list);
		$this->log('delete_file_list↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_DEBUG);
		$this->log($delete_file_list, LOG_DEBUG);
		$this->log('delete_file_list↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_DEBUG);

		foreach ($delete_file_list as $file_path_name) {

			// ファイルサイズ取得
			$file_size = FileUtil::size($staging_path . DS . $file_path_name);
			if ($file_size === false) {
				$this->log('ファイルサイズの取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// ファウル更新日時取得
			$file_time = FileUtil::filetime($staging_path . DS . $file_path_name);
			if ($file_time === false) {
				$this->log('ファイル更新日時の取得に失敗しました。', LOG_ERR);
				$this->log('  対象ファイルパス名：[' . $work_path . DS . $file_path_name . ']', LOG_ERR);
				return false;
			}

			// パラメータを作成
			$data = array (
					'id'				=> null,									// コンテンツファイルID
					'is_del'			=> AppConstants::FLAG_OFF,					// 削除フラグ
					'created_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 作成者ID
					'modified_user_id'	=> AppConstants::USER_ID_SYSTEM,			// 更新者ID
					'package_id'		=> $this->id,								// パッケージID
					'file_path'			=> DS . $site_url . DS . self::BLOG . DS . $file_path_name,	// ファイルパス名
					'file_size'			=> $file_size,								// サイズ
					'file_modified'		=> $file_time,								// ファイル更新日時
					'modify_flg'		=> AppConstants::MODIFY_FLG_DEL				// 更新フラグ：削除
			);

			// コンテンツファイルへ登録
			$result = $this->ContentsFile->save($data);
			//$this->log($result, LOG_DEBUG);
			if ($result === false) {
				$this->log('コンテンツファイルへの登録に失敗しました。', LOG_ERR);
				$this->log('データ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓', LOG_ERR);
				$this->log($data, LOG_ERR);
				$this->log('データ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑', LOG_ERR);
				return false;
			}
		}

		$this->log('ブログパッケージ作成(内部) 成功', LOG_DEBUG);
		return true; // 成功
	}

	/**
	 * 成功時後実行
	 *
	 * @return boolean 成否
	 */
	function execute_after_success() {
		return true;
	}
	
	/**
	 * 失敗時後実行
	 *
	 * @return boolean 成否
	 */
	function execute_after_failure() {
		return true;
	}

	/**
	 * 削除対象ファイルリストを取得します。
	 *
	 * @param  unknown $work_file_list    作業用ファイルリスト
	 * @param  unknown $staging_file_list ステージング用ファイルリスト
	 * @return multitype:                 削除対象リスト
	 */
	private function getDeleteFileList($work_file_list, $staging_file_list) {

		$this->log('getDeleteFileList start', LOG_DEBUG);

		$delete_file_list = array();

		foreach ($staging_file_list as $staging_file) {

			if ($this->hasFile($staging_file, $work_file_list) === false) {
				$delete_file_list[] = $staging_file;
			}
		}

		//$this->log($delete_file_list, LOG_DEBUG);
		$this->log('getDeleteFileList end', LOG_DEBUG);
		return $delete_file_list;
	}


	/**
	 * ブログに新規に追加され、選択から漏れている記事を取得
	 * @param unknown $project_id
	 * @param unknown $package_id
	 * @return mt_entry_pub_buk検索結果配列
	 */
	private function __getMtEntryAddList($project_id, $package_id) {
		// 追加処理
		$sql =
		"	select "
				."eab.entry_id "
				.", eab.entry_modified_on "
				.", eab.entry_title "
				.", eab.entry_text "
				.", eab.entry_text_more "
			."from "
				."mt_entry_app_bak eab "
			."left join mt_entry_pub_bak epb "
			."on eab.entry_id = epb.entry_id "
			."left join mt_posts p "
			."on eab.entry_id = p.edit_mt_post_id "
			."and p.package_id = ? "
			."join mt_projects mp "
			."on mp.edit_mt_project_id = eab.entry_blog_id "
			."and mp.is_del = '0' "
			."where "
				."mp.project_id = ? "
			."and epb.entry_id is null "
			."and p.id is null "
			."and eab.entry_class = 'entry'";

		return  $this->MtEntryAppBak->query($sql, array($package_id, $project_id));
	}


	/**
	 * ブログで更新され、選択から漏れている記事を取得
	 * @param unknown $package_id
	 * @param unknown $project_id
	 */
	private function __getMtEntryModifyList($project_id, $package_id) {
		 // 更新処理
		$sql =
			"select "
				."eab.entry_id "
				.", eab.entry_modified_on "
				.", eab.entry_title "
				.", eab.entry_text "
				.", eab.entry_text_more "
			."from "
				."mt_entry_app_bak eab "
			."inner join mt_entry_pub_bak epb "
			."on eab.entry_id = epb.entry_id "
			."left join mt_posts p "
			."on eab.entry_id = p.edit_mt_post_id "
			."and p.package_id = ? "
			."join mt_projects mp "
			."on mp.edit_mt_project_id = eab.entry_blog_id "
			."and mp.is_del = '0' "
			."where "
				."mp.project_id = ? "
			."and ("
				."eab.entry_title <> epb.entry_title "
				."or eab.entry_text <> epb.entry_text "
				."or eab.entry_text_more <> epb.entry_text_more)"
			."and p.id is null "
			."and eab.entry_class = 'entry'";
		
		return $this->MtEntryAppBak->query($sql, array($package_id, $project_id));
	}

	/**
	 * ファイルリストに、ファイルか存在するか判定します。
	 *
	 * @param unknown $target    ファイル
	 * @param unknown $file_list ファイルリスト
	 * @return boolean           true：存在する、false：存在しない
	 */
	private function hasFile($target, $file_list) {

		foreach ($file_list as $file ) {
			if ($file == $target) {
				return true; // 存在する
			}
		}

		return false; // 存在しない
	}

}
?>
