<?php

App::uses('BatchAppController', 'Controller');
App::uses('TransactionComponent', 'Component');
App::uses('MtXmlRpcComponent', 'Controller/Component');

/**
 * ブログパッケージ掃除
 * @author tasano
 *
 */
class BatchCleanBlogPackageController extends BatchAppController {

	/** モデルを登録 */
	public $uses = array('Package', 'MtPost', 'MtMapping', 'MtProject');

	/** XML-RPCコンポーネント */
	protected $mtXmlRpcComponent;

	/**
	 * コンストラクタ
	 */
	function __construct() {

		// 親コンストラクタ
		parent::__construct();

		// コンポーネント初期化
		$this->mtXmlRpcComponent = new MtXmlRpcComponent();
	}

	/**
	 * 実行
	 *
	 * @return string 結果コード
	 */
	public function execute() {

		$this->log('ブログパッケージ掃除バッチを開始しました。', LOG_INFO);

		// エラーフラグ
		$error_flg = false;

		// ロールバック対象のプロジェクトIDを取得
		$target_list = $this->Package->getCleaningTargetListForRollbackMt();

		$this->log('対象パッケージID↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		$this->log($target_list);
		$this->log('対象パッケージID↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if($target_list === false) {
			$this->log('対象パッケージの取得に失敗しました。', LOG_ERR);
			$this->log('ブログパッケージ掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		}
		if (empty($target_list)) {
			$this->log('対象パッケージが存在しません。', LOG_DEBUG);
			$this->log('ブログパッケージ掃除を正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}

		// エラーフラグ
		$error_flg = false;

		// パッケージID分、繰り返す
		foreach ($target_list as $target) {

			// パッケージIDを取得
			$package_id = $target['packages']['id'];
			$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);

			// トランザクション開始
			if ($this->Transaction->begin() === false) {
				$this->log('トランザクション開始に失敗しました。', LOG_ERR);
				$error_flg = true;
				continue;
			}

			// パッケージを取得
			$package = $this->Package->findByPackageIdWithoutConsideringIsDel($package_id);
			if(empty($package)) {
				$this->log('パッケージ情報の取得失敗しました。パッケージID：[' . $package_id . ']', LOG_ERR);
				$error_flg = true;
				continue;
			}

			// 内部実行
			if ($this->executeInner($package) === false) {

				// エラーフラグを立てる
				$error_flg = true;

				// ロールバック
				if ($this->Components->Transaction->rollback(null) === false) {
					$this->log('ロールバックに失敗しました。', LOG_ERR);
					$error_flg = true;
				}

			} else {

				// 更新値
				$package['Package']['is_clean_db']		= AppConstants::FLAG_ON; 		// DB掃除フラグ
				$package['Package']['modified_user_id'] = AppConstants::USER_ID_SYSTEM;	// 更新者ID
				$package['Package']['modified']			= null;							// 更新日時

				// パッケージを更新
				$this->Package->save($package);

				// コミット
				if($this->Transaction->commit() === false) {
					$this->log('コミットに失敗しました。', LOG_ERR);
					$error_flg = true;
				}
			}
		}

		if ($error_flg == true) {
			$this->log('ブログパッケージ掃除バッチを異常終了しました。', LOG_ERR);
			return AppConstants::RESULT_CD_FAILURE; // 結果コード(:失敗)
		} else {
			$this->log('ブログパッケージ掃除バッチを正常終了しました。', LOG_INFO);
			return AppConstants::RESULT_CD_SUCCESS; // 結果コード(:成功）
		}
	}

	/**
	 * 内部処理
	 *
	 * @param unknown $package パッケージ
	 * @return boolean         成否
	 */
	 private function executeInner($package) {

		$this->log('ブログパッケージ掃除(内部) 開始', LOG_DEBUG);

		// パッケージIDを取得
		$package_id = $package['Package']['id'];
		$this->log('パッケージID：[' . $package_id . ']', LOG_DEBUG);
		if (empty($package_id)) {
			$this->log('パッケージIDが不正です。', LOG_ERR);
			return false;
		}

		// プロジェクトIDを取得
		$project_id = $package['Package']['project_id'];
		$this->log('プロジェクトID：[' . $project_id . ']', LOG_DEBUG);

		// MTプロジェクトを取得
		$mtProject = $this->MtProject->getMtProject($project_id);
		if(empty($mtProject)) {
			$this->log('MTプロジェクトの取得失敗しました。プロジェクトID：[' . $project_id . ']', LOG_ERR);
			$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
			return false;
		}

		// 承認用MTプロジェクトIDを取得
		$approval_mt_project_id = $mtProject['MtProject']['approval_mt_project_id'];
		$this->log('承認用MTプロジェクトID：[' . $approval_mt_project_id . ']', LOG_DEBUG);

			// MT記事リストを取得
			$mt_post_list = $this->MtPost->findListByPackageId($package_id);
			if (empty($mt_post_list)) {
				$this->log('MT記事の取得に失敗しました。[' . $package_id . ']', LOG_ERR);
				$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
				return false;
			}
			$this->log($mt_post_list, LOG_DEBUG);

			foreach ($mt_post_list as $mt_post) {

				// 編集用MT記事IDを取得
				$edit_mt_post_id = $mt_post['MtPost']['edit_mt_post_id'];
				$this->log('編集用MT記事ID：[' . $edit_mt_post_id .  ']', LOG_DEBUG);

				// MTマッピングを取得
				$mt_mapping = $this->MtMapping->findByEditMtPostId($edit_mt_post_id);
				if (empty($mt_mapping)) {
					$this->log('MTマッピング情報の取得に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);

					$mt_mapping['MtMapping']['id']                  = null;							// MT記事マッピングID
					$mt_mapping['MtMapping']['edit_mt_post_id']     = $edit_mt_post_id;				// 編集用MT記事ID
					$mt_mapping['MtMapping']['created_user_id']     = AppConstants::USER_ID_SYSTEM; // 作成者ID
					$mt_mapping['MtMapping']['created']             = null;							// 作成日時
					$mt_mapping['MtMapping']['approval_mt_post_id'] = null;							// 承認用記事ID
					$mt_mapping['MtMapping']['public_mt_post_id']   = null;							// 公開用記事ID
				}

				// 承認用MT記事IDを取得
				$approval_mt_post_id = $mt_mapping['MtMapping']['approval_mt_post_id'];
				$this->log('承認用MT記事ID：[' . $approval_mt_post_id .  ']', LOG_DEBUG);

				// 公開用MT記事IDを取得
				$public_mt_post_id = $mt_mapping['MtMapping']['public_mt_post_id'];
				$this->log('公開用MT記事ID：[' . $public_mt_post_id .  ']', LOG_DEBUG);

				// ------------------------
				// 承認用MTのブログを戻す。
				// mt_mappingを戻す。
				// ------------------------

				// ⇒公開用MT記事ID:NULL、承認用MT記事ID:NULL
				if (empty($public_mt_post_id) && empty($approval_mt_post_id)) {

					// 何もしない。
					$this->log('① 何もしない。', LOG_DEBUG);


				// ⇒公開用MT記事ID:NULL、承認用MT記事ID:NULL以外(つまり、公開パッケージで新規登録の場合)
				} else if (empty($public_mt_post_id) && !empty($approval_mt_post_id)) {

					// XMLRPCで承認用ブログから承認用MT記事IDの記事を削除
					$this->log('② XMLRPCで承認用ブログから承認用MT記事IDの記事を削除', LOG_DEBUG);

					$result = $this->mtXmlRpcComponent->deletePost($approval_mt_post_id, false);
					if ($result === false) {
						$this->log('記事削除に失敗しました。', LOG_ERR);
						$this->log('  記事ID：[' .$approval_mt_post_id  . ']', LOG_WARNING);
					}

					// 承認用MT記事IDに"NULL"を設定する。
					$mt_mapping['MtMapping']['approval_mt_post_id'] = null;

				// ⇒公開用MT記事ID:NULL以外、承認用MT記事ID:NULL(つまり、削除パッケージの場合)(※1)
				} else if (!empty($public_mt_post_id) && empty($approval_mt_post_id)) {

					// XMLRPCで公開用ブログから公開用MT記事IDの記事内容を取得し、
					// 同じくXMLRPCで承認用ブログにその内容を追加する。
					$this->log('③ XMLRPCで公開用ブログから公開用MT記事IDの記事内容を取得し、', LOG_DEBUG);
					$this->log('   同じくXMLRPCで承認用ブログにその内容を追加する。', LOG_DEBUG);

					// 記事を取得
					$result = $this->mtXmlRpcComponent->getPost($public_mt_post_id, false);
					if ($result === false) {
						$this->log('記事取得に失敗しました。', LOG_ERR);
						$this->log('  記事ID：[' .$public_mt_post_id  . ']', LOG_ERR);
						$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
						return false;
					}

					// 記事の本文を取得
					$title        = $result['title'];        // 件名
					$description  = $result['description'];  // 本文
					$dateCreated  = $result['dateCreated'];  // 投稿日時
					$mt_text_more = $result['mt_text_more']; // 追記
					$this->log('件名    ：[' . title        . ']', LOG_DEBUG);
					$this->log('本文    ：[' . $description . ']', LOG_DEBUG);
					$this->log('投稿日時：[' . dateCreated  . ']', LOG_DEBUG);
					$this->log('追記    ：[' . mt_text_more . ']', LOG_DEBUG);

					// 記事新規登録
					$new_post_id = $this->mtXmlRpcComponent->newPost(
								$approval_mt_project_id,	// ブログID：承認用MTプロジェクトID
								$title,						// 件名
								$description,				// 本文
								$dateCreated,				// 投稿日時
								$mt_text_more,				// 追記
								false);						// 編集用MTフラグ
					if($new_post_id === false) {
						$this->log('記事登録に失敗しました。ブログID：[' . $public_mt_project_id . ']', LOG_ERR);
						$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
						return false;
					}
					$this->log('新しい記事ID：[' . $new_post_id . ']', LOG_DEBUG);

					// マッピング情報の承認用MT記事IDに設定する。
					$mt_mapping['MtMapping']['approval_mt_post_id'] = $new_post_id;

				// ⇒公開用MT記事ID:NULL以外、承認用MT記事ID:NULL(つまり、公開パッケージで更新の場合)
				} else if (!empty($public_mt_post_id) && !empty($approval_mt_post_id)) {

					// XMLRPCで公開用ブログから公開用MT記事IDの記事内容を取得し、
					// 同じくXMLRPCで承認用ブログの承認用MT記事IDの記事を取得した記事内容で更新する。

					$this->log('④ XMLRPCで公開用ブログから公開用MT記事IDの記事内容を取得し、', LOG_DEBUG);
					$this->log('   同じくXMLRPCで承認用ブログの承認用MT記事IDの記事を取得した記事内容で更新する。', LOG_DEBUG);

					// 記事取得
					$result = $this->mtXmlRpcComponent->getPost($public_mt_post_id, false);
					if ($result === false) {
						$this->log('記事取得に失敗しました。', LOG_ERR);
						$this->log('  記事ID：[' . $public_mt_post_id  . ']', LOG_ERR);
						$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
						return false;
					}

					// 記事の本文を取得
					$title        = $result['title'];        // 件名
					$description  = $result['description'];  // 本文
					$dateCreated  = $result['dateCreated'];  // 投稿日時
					$mt_text_more = $result['mt_text_more']; // 追記
					$this->log('件名    ：[' . title        . ']', LOG_DEBUG);
					$this->log('本文    ：[' . $description . ']', LOG_DEBUG);
					$this->log('投稿日時：[' . dateCreated  . ']', LOG_DEBUG);
					$this->log('追記    ：[' . mt_text_more . ']', LOG_DEBUG);

					// 記事更新
					if ($this->mtXmlRpcComponent->editPost($approval_mt_post_id,	// 記事ID
											$title,					// 件名
											$description,			// 本文
											$dateCreated,			// 投稿日時
											$mt_text_more,			// 追記
											false					// 編集用MTフラグ
										   ) === false) {
						$this->log('記事更新に失敗しました。記事ID：[' . $public_mt_post_id . ']', LOG_ERR);
						$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
						return false;
					}

					// マッピング情報は更新しない。
				}

				// MTマッピングのシステム項目を設定
				$mt_mapping['MtMapping']['modified_user_id'] = AppConstants::USER_ID_SYSTEM; // 更新者ID
				$mt_mapping['MtMapping']['modified']         = null; // 更新日時

				// MTマッピングを更新
				if ($this->MtMapping->save($mt_mapping) === false) {
					$this->log('MTマッピングの更新に失敗しました。編集用MT記事ID：[' . $edit_mt_post_id . ']', LOG_ERR);
					$this->log('ブログパッケージ掃除(内部) 失敗', LOG_DEBUG);
					return false;
				}
			}

			$this->log('ブログパッケージ掃除(内部) 成功', LOG_DEBUG);
			return true;
	}

}