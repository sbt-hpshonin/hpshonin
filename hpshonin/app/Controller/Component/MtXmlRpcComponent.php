<?php

require_once 'XML/RPC.php';

/**
 * XML-RPCユーティリティクラス
 *
 * @author tasano
 *
 */
class MtXmlRpcComponent {

	/** 結果詳細：接続失敗 */
	const RESULT_CODE_NOT_CONNECT = '1';

	/** 結果詳細：処理失敗 */
	const RESULT_CODE_NOT_PROCESS = '2';

	/** 結果詳細：デコード失敗 */
	const RESULT_CODE_NOT_DECODE  = '3';

	/** 結果詳細：該当データなし */
	const RESULT_CODE_NOT_FOUND   = '4';

	/** 結果詳細：成功 */
	const RESULT_CODE_SUCCESS     = '5';

	/** 編集用MTのホスト名 */
	const EDIT_MT_HOST = "http://localhost";

	/** 承認・公開用MTのホスト名 */
	const MT_HOST = "http://localhost";

	/** 編集用MTのポート */
	const EDIT_MT_PORT = 80;

	/** 承認・公開用MTのポート */
	const MT_PORT = 80;

	/** 編集用MTのパス名 */
	const EDIT_MT_XML_RPC_PATH = "/mt-edit/mt-xmlrpc.cgi";

	/** 承認・公開用MTのパス名 */
	const MT_XML_RPC_PATH = "/mt-approval/mt-xmlrpc.cgi";

	/** 結果詳細 */
	var $resultCode;

	/**
	 * 記事を取得
	 *
	 * @param  unknown $post_id  記事ID
	 * @param  unknown $edit_flg 編集用MTフラグ
	 * @return boolean|unknown   記事情報
	 */
	public function getPost($post_id, $edit_flg) {

		CakeLog::debug('MtXmlRpcComponent::getPost start');
		CakeLog::debug('  $post_id  ：[' . $post_id . ']');
		CakeLog::debug('  $edit_flg ：[' . $edit_flg . ']');

		// XML-RPCクライアントを生成
		$client   = self::createClient($edit_flg);

		// メッセージを編集
		$postid   = new XML_RPC_Value($post_id,                       'string'); // 記事ID
		$username = new XML_RPC_Value(AppConstants::MT_USER_ID,       'string'); // ユーザー
		$passwd   = new XML_RPC_Value(AppConstants::MT_USER_PASSWORD, 'string'); // パスワード
		$message  = new XML_RPC_Message('metaWeblog.getPost', array(
												$postid,
												$username,
												$passwd
									  ));

		// メッセージを送信
		$result = $client->send($message);
		if ($result == false) {
			CakeLog::error('MTサーバに接続できませんでした。');
			$this->resultCode = self::RESULT_CODE_NOT_CONNECT; // 接続失敗
			return false;
		}

		CakeLog::debug('$result↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		CakeLog::debug($result->serialize());
		CakeLog::debug('$result↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if ($result->faultCode()) {
			CakeLog::warning('該当記事が存在しませんでした。');
			CakeLog::warning('  faultCode：[' . $result->faultCode() . ']');
			CakeLog::warning('  faultString：[' . $result->faultString() . ']');
			$this->resultCode = self::RESULT_CODE_NOT_FOUND; // 該当データなし
			return false;
		}

		// デコード
		$decoded = XML_RPC_decode($result->value());
		if ($decoded  === false) {
			CakeLog::error('デコードに失敗しました。');
			$this->resultCode = self::RESULT_CODE_NOT_DECODE; // デコード失敗
			return false;
		}

		// デコードされた戻り値を返却
		$this->resultCode = self::RESULT_CODE_SUCCESS;
		return $decoded;
	}

	/**
	 * 記事を新規登録
	 *
	 * @param  unknown $blogid       ブログID
	 * @param  unknown $title        タイトル
	 * @param  unknown $description  本文
	 * @param  unknown $date         更新日時
	 * @param  unknown $mt_text_more 追記
	 * @param  unknown $edit_flg     編集用MTフラグ
	 * @return boolean|unknown       記事ID
	 */
	public function newPost($blogid, $title, $description, $date, $mt_text_more, $edit_flg) {

		CakeLog::info('MtXmlRpcComponent::newPost start');
		CakeLog::debug('  $blogid      ：[' . $blogid . ']');
		CakeLog::debug('  $title       ：[' . $title . ']');
		CakeLog::debug('  $description ：[' . $description . ']');
		CakeLog::debug('  $date        ：[' . $date . ']');
		CakeLog::debug('  $mt_text_more：[' . $mt_text_more . ']');
		CakeLog::debug('  $edit_flg    ：[' . $edit_flg . ']');

		// XML-RPCクライアントの生成
		$client   = self::createClient($edit_flg);

		// パラメータの準備
		$blogid   = new XML_RPC_Value($blogid,                        'string'); // ブログID
		$username = new XML_RPC_Value(AppConstants::MT_USER_ID,       'string'); // ユーザ
		$passwd   = new XML_RPC_Value(AppConstants::MT_USER_PASSWORD, 'string'); // パスワード
		$publish  = new XML_RPC_Value('1',                            'string'); // 公開する
		$content  = new XML_RPC_Value(array(
						"title"        => new XML_RPC_Value($title, 'string'),			// 記事の件名
						"description"  => new XML_RPC_Value($description, 'string'),	// 記事の本文
						"dateCreated"  => new XML_RPC_Value($date, 'dateTime.iso8601'),	// 記事の投稿日時
						"mt_text_more" => new XML_RPC_Value($mt_text_more, 'string')	// 追記
					),"struct");

		// メッセージの生成
		$message = new XML_RPC_Message('metaWeblog.newPost',array(
												$blogid,
												$username,
												$passwd,
												$content,
												$publish));

		// メッセージを送信
		$result = $client->send($message);
		if ($result == false) {
			CakeLog::error('MTサーバに接続できませんでした。');
			$resultCode = self::RESULT_CODE_NOT_CONNECT; // 接続失敗
			return false;
		}

		CakeLog::debug('$result↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		CakeLog::debug($result->serialize());
		CakeLog::debug('$result↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if ($result->faultCode()) {
			CakeLog::error('登録処理に失敗しました。');
			CakeLog::error('  faultCode：[' . $result->faultCode() . ']');
			CakeLog::error('  faultString：[' . $result->faultString() . ']');
			$resultCode = RESULT_CODE_NOT_PROCESS; // 処理失敗
			return false;
		}

		// デコード
		$decoded = XML_RPC_decode($result->value());
		CakeLog::debug($decoded);
		if ($decoded === false) {
			CakeLog::error('デコードに失敗しました。');
			$resultCode = self::RESULT_CODE_NOT_DECODE; // デコード失敗
			return false;
		}

		// デコードされた戻り値(記事ID)を返却
		$resultCode = self::RESULT_CODE_SUCCESS; // 成功
		return $decoded;
	}

	/**
	 * 記事を更新
	 *
	 * @param  unknown $postid       記事ID
	 * @param  unknown $title        タイトル
	 * @param  unknown $description  本文
	 * @param  unknown $date         更新日時
	 * @param  unknown $mt_text_more 追記
	 * @param  unknown $edit_flg     編集用MTフラグ
	 * @return boolean               true:成功、false：失敗
	 */
	public function editPost($postid, $title, $description, $date,  $mt_text_more, $edit_flg) {

		CakeLog::info('MtXmlRpcComponent::editPost start');
		CakeLog::debug('  $postid      ：[' . $postid . ']');
		CakeLog::debug('  $title       ：[' . $title . ']');
		CakeLog::debug('  $description ：[' . $description . ']');
		CakeLog::debug('  $date        ：[' . $date . ']');
		CakeLog::debug('  $mt_text_more：[' . $mt_text_more . ']');
		CakeLog::debug('  $edit_flg    ：[' . $edit_flg . ']');

		// XML-RPCクライアントの生成
		$client   = self::createClient($edit_flg);

		// パラメータの準備
		$postid   = new XML_RPC_Value($postid,                        'string'); // 記事ID
		$username = new XML_RPC_Value(AppConstants::MT_USER_ID,       'string'); // ユーザ
		$passwd   = new XML_RPC_Value(AppConstants::MT_USER_PASSWORD, 'string'); // パスワード
		$publish  = new XML_RPC_Value('1',                            'string'); // 公開する
		$content  = new XML_RPC_Value(array(
				"title"        => new XML_RPC_Value($title,        'string'),			// 記事の件名
				"description"  => new XML_RPC_Value($description,  'string'),			// 記事の本文
				"dateCreated"  => new XML_RPC_Value($date,         'dateTime.iso8601'),	// 記事の投稿日時
				"mt_text_more" => new XML_RPC_Value($mt_text_more, 'string')			// 追記
		),"struct");

		// メッセージの生成
		$message = new XML_RPC_Message('metaWeblog.editPost',array(
				$postid,
				$username,
				$passwd,
				$content,
				$publish

		));

		// メッセージの送信
		$result = $client->send($message);
		if ($result == false) {
			CakeLog::error('MTサーバに接続できませんでした。');
			$resultCode = self::RESULT_CODE_NOT_CONNECT; // 接続失敗
			return false;
		}

		CakeLog::debug('$result↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		CakeLog::debug($result->serialize());
		CakeLog::debug('$result↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if ($result->faultCode()) {
			CakeLog::error('更新処理に失敗しました。');
			CakeLog::error('  faultCode：[' . $result->faultCode() . ']');
			CakeLog::error('  faultString：[' . $result->faultString() . ']');
			$resultCode = self::RESULT_CODE_NOT_PROCESS; // 処理失敗
			return false;
		}

		$resultCode = self::RESULT_CODE_SUCCESS; // 成功
		return true;
	}

	/**
	 * 記事を削除
	 *
	 * @param  unknown $postid   記事ID
	 * @param  unknown $edit_flg 編集用MTフラグ
	 * @return boolean           true：成功、false：失敗
	 */
	public function deletePost($postid, $edit_flg) {

		CakeLog::debug('MtXmlRpcComponent::deletePost start');
		CakeLog::debug('  $postid  ：[' . $postid . ']');
		CakeLog::debug('  $edit_flg：[' . $edit_flg . ']');

		// XML-RPCクライアントの生成
		$client = self::createClient($edit_flg);

		// パラメータを準備
		$appkey   = new XML_RPC_Value('',                             'string'); // 任意でよい
		$postid   = new XML_RPC_Value($postid,                        'string'); // 記事ID
		$username = new XML_RPC_Value(AppConstants::MT_USER_ID,       'string'); // ユーザ
		$passwd   = new XML_RPC_Value(AppConstants::MT_USER_PASSWORD, 'string'); // パスワード
		$publish  = new XML_RPC_Value('1',                            'string'); // 公開する

		// メッセージを生成
		$message = new XML_RPC_Message('blogger.deletePost',array(
				 $appkey,
				 $postid,
				 $username,
				 $passwd,
				 $publish));

		// メッセージを送信
		$result = $client->send($message);

		if ($result == false) {
			CakeLog::error('MTサーバに接続できませんでした。[' . $edit_flg . ']');
			$resultCode = RESULT_CODE_NOT_CONNECT; // 接続失敗
			return false;
		}

		CakeLog::debug('$result↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		CakeLog::debug($result->serialize());
		CakeLog::debug('$result↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if ($result->faultCode()) {
			CakeLog::error('削除処理に失敗しました。');
			CakeLog::error('  faultCode：[' . $result->faultCode() . ']');
			CakeLog::error('  faultString：[' . $result->faultString() . ']');
			$resultCode = self::RESULT_CODE_NOT_PROCESS; // 処理失敗
			return false;
		}

		$resultCode = self::RESULT_CODE_SUCCESS; // 成功
		return true;
	}

	/**
	 * 記事を公開
	 *
	 * @param  unknown $postid   記事ID
	 * @param  unknown $edit_flg 編集用MTフラグ
	 * @return boolean           true：成功、false：失敗
	 */
	public function publishPost($postid, $edit_flg) {

		CakeLog::info('MtXmlRpcComponent::publishPost start');
		CakeLog::debug('  $postid   ：[' . $postid . ']');
		CakeLog::debug('  $edit_flg ：[' . $edit_flg . ']');

		// XML-RPCクライアントを生成
		$client   = self::createClient($edit_flg);

		// パラメータの準備
		$postid   = new XML_RPC_Value( $postid,                        'string' ); // 記事ID
		$username = new XML_RPC_Value( AppConstants::MT_USER_ID,       'string' ); // ユーザ
		$passwd   = new XML_RPC_Value( AppConstants::MT_USER_PASSWORD, 'string' ); // パスワード

		// メッセージの生成
		$message = new XML_RPC_Message('mt.publishPost',array(
				$postid,
				$username,
				$passwd
		));

		// メッセージの送信
		$result = $client->send($message);
		if ($result == false) {
			CakeLog::debug('MTサーバに接続できませんでした。');
			$resultCode = self::RESULT_CODE_NOT_CONNECT; // 接続失敗
			return false;
		}

		CakeLog::debug('$result↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓');
		CakeLog::debug($result->serialize());
		CakeLog::debug('$result↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑');

		if ($result->faultCode()) {
			CakeLog::debug('XML-RPCにて、失敗しました。');
			CakeLog::debug('  faultCode：[' . $result->faultCode() . ']');
			CakeLog::debug('  faultString：[' . $result->faultString() . ']');
			$resultCode = RESULT_CODE_NOT_PROCESS; // 処理失敗
			return false;
		}

		$resultCode = self::RESULT_CODE_SUCCESS; // 成功
		return true;
	}

	/**
	 * クライアントを生成
	 *
	 * @param  unknown $edit_flg 編集用MTフラグ
	 * @return XML_RPC_client    クライアント
	 */
	private function createClient($edit_flg) {

		CakeLog::debug('MtXmlRpcComponent::createClient');
		CakeLog::debug('  $edit_flg：[' . $edit_flg . ']');

		if ($edit_flg == true) {
			// XML-RPCクライアントを生成(編集用MT)
			CakeLog::debug('編集用MTとのクライアントを生成');
			return new XML_RPC_client(self::EDIT_MT_XML_RPC_PATH, self::EDIT_MT_HOST, self::EDIT_MT_PORT);
		} else {
			// XML-RPCクライアントを生成(承認・公開用MT)
			CakeLog::debug('承認・公開用MTとのクライアントを生成');
			return new XML_RPC_client(self::MT_XML_RPC_PATH, self::MT_HOST, self::MT_PORT);
		}
	}

}
?>