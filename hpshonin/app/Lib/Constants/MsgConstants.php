<?php
class MsgConstants {
	/**
	 * 共通
	 */
	const CONFIRM_EDIT			= "登録します。よろしいですか。";
	const CONFIRM_DELETE		= "削除します。よろしいですか。";

	/**
	 * ユーザ管理(UM001)
	 */

	/** 正常系 */
	const SUCCESS_USER_ADD		= "ユーザーを登録しました。";
	const SUCCESS_USER_UPDATE	= "ユーザーを更新しました。";
//     const SUCCESS_REQEST_UPDATE = "パッケージ承認依頼が完了しました。";

	/** 異常系 */
	const ERROR_USER_ADD		= "ユーザーの登録に失敗しました。";
	const ERROR_USER_UPDATE		= "ユーザーの更新に失敗しました。。";
// 	const ERROR_USER_NOT_FOUND	= "指定したユーザーは存在しません。";
// 	const ERROR_REQUEST_UPDATE = "パッケージ承認依頼が失敗しました。";
	const ERROR_OPTIMISTIC_LOCK = "すでに別ユーザーが編集しているため、更新できません。ページを再読み込みしてください。";
	const ERROR_STATUS_REQUEST	= "ステータスが既に「パッケージ登録」ではないため承認依頼できません。ページを再読み込みしてください。";
	const ERROR_STATUS_APPROVAL = "ステータスが既に「承認待ち」ではないため公開承認できません。ページを再読み込みしてください。";
	const ERROR_STATUS_UPSET	= "ステータスが既に公開設定より進んでいるため公開設定できません。ページを再読み込みしてください。";
//	const ERROR_STATUS_UPSET_DEL = "対象のデータのステータスが不正のため公開取消できません。";

//	const ERROR_LOGIN			= "メールアドレス、または、パスワードが間違っています。";
	const ERROR_NO_DATA			= "対象のデータが見つかりませんでした。";

	const ERROR_DB				= "データベースエラーが発生しました。";
	const ERROR_EXPIRATION		= "公開期限切れです。";

	const ERROR_NOT_OPEN_PACKAGE = "コンテンツファイルが開けません。";
	const ERROR_WRONG_SITE_URL   = "コンテンツファイルの作り方が間違っています。\nプロジェクトのサイトURLをルートフォルダにしてZIPに固めてください。";
	const ERROR_WRONG_CHAR       = "ファイル名またはフォルダ名に:*?\"<>|#{}%&~+または全角文字をいれないでください。\n{0}";
	const ERROR_NO_CHANGE_FILE	 = "現在、公開されているファイルから更新されていないファイルがあります。\n{0}\n新規または更新があるファイルだけでコンテンツファイルを作成してください。";
	const ERROR_WRONG_FILE_EXT	 = "アップロード禁止の拡張子のファイルが含まれているため、アップロードできません。\n{0}";

	const ERROR_WRONG_FILENAME	 = "削除指示ファイルが存在しません。\nファイル名は\"delete.txt\"にしてZIPに固めてください。";
	const ERROR_NOT_EXISTS		 = "削除指示ファイルに記載されたファイルで公開されていないものがあります。\n{0}\n公開されているファイルのみ指定してください。";
	const ERROR_WRONG_CHAR_YEN	 = "削除指示ファイルに記載されたファイルパスの区切り文字に'\'が使われています。\n{0}\n区切り文字は'/'を使用してください。";
	const ERROR_WRONG_CHAR_POINT = "削除指示ファイルに記載されたファイルに'..'が使われています。\n{0}\n..は使用しないでください。";
	
	const ERROR_NO_CHANGE_POST	 = "現在、公開されている記事から更新されていない記事があります。\n{0} {1}\n新規または更新がある記事だけを指定してください。";
	const ERROR_NO_CHANGE_POST_AT_ALL	 = "更新されている記事がありません。";

	const ERROR_GUID = "不正な操作を検知しました。";
	const ERROR_AUTH = "権限がありません。";
	
	const ERROR_SYSTEM = "システムエラー";
}