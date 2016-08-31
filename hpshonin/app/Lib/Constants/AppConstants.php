<?php
/**
 * アプリケーション定数クラス
 * @author smurata
 *
 */
class AppConstants {

	/** YES */
	const FLAG_YES	= "1";
	/** NO */
	const FLAG_NO	= "0";

	/** ON */
	const FLAG_ON	= "1";
	/** OFF */
	const FLAG_OFF	= "0";

	/** TRUE */
	const FLAG_TRUE	= "1";
	/** FALSE */
	const FLAG_FALSE= "0";

	/** 管理者 */
	const ROLL_CD_ADMIN =	"0";
	/** 制作会社 */
	const ROLL_CD_DEVELOP =	"1";
	/** サイト担当者 */
	const ROLL_CD_SITE =	"2";
	/** 広報室 */
	const ROLL_CD_PR =	 	"3";

	/** 種別CD:公開 */
	const OPERATION_CD_PUBLIC = "1";
	/** 種別CD:削除 */
	const OPERATION_CD_DELETE = "2";

	/** 種別名称:公開 */
	const OPERATION_NAME_PUBLIC = "公開";
	/** 種別名称:削除 */
	const OPERATION_NAME_DELETE = "削除";

	/** 結果コード:未実施 */
	const RESULT_CD_NOT_EXECUTION = "0";
	/** 結果コード:成功 */
	const RESULT_CD_SUCCESS = "1";
	/** 結果コード:実行中 */
	const RESULT_CD_EXECUTION = "2";
	/** 結果コード:失敗 */
	const RESULT_CD_FAILURE = "9";

	/** 公開コード */
	const PUBLIC_CD_RESERVE = "0";
	const PUBLIC_CD_PROMPTLY = "1";

	/** 公開コード名称 */
	const PUBLIC_CD_NAME_RESERVE = "予約公開";
	const PUBLIC_CD_NAME_PROMPTLY = "即時公開";

	/** 更新フラグ：追加 */
	CONST MODIFY_FLG_ADD = '0';
	/** 更新フラグ：変更 */
	CONST MODIFY_FLG_MOD = '1';
	/** 更新フラグ：変更なし */
	CONST MODIFY_FLG_NO_MOD = '2';
	/** 更新フラグ：削除 */
	CONST MODIFY_FLG_DEL = '9';

	/** アップロードフォルダ */
	const DIRECTOR_UPLOAD_PATH = 'C:\hpshonin\data\packages';
	/** ブログ画像フォルダ */
#	const DIRECTOR_BLOG_IMAGE_PATH = 'C:\hpshonin\data\images';
	/** 作業フォルダ */
	const DIRECTOR_WORK_PATH = 'C:\hpshonin\work';
	/** 承認フォルダ */
	const DIRECTOR_APPROVAL_PATH = 'C:\hpshonin\webroot\static\prv';
	/** ステージングフォルダ */
	const DIRECTOR_STAGING_PATH = 'C:\hpshonin\webroot\static\stg';
	/** 公開フォルダ */
	const DIRECTOR_PUBLISH_PATH_1 = '\\\\10.0.0.16\ssroot$';
	const DIRECTOR_PUBLISH_PATH_2 = '\\\\10.0.0.19\ssroot$';
	/** IISの物理フォルダ */
	const DIRECTOR_PUBLISH_IIS_PATH = "C:\hpshonin\ssroot";
	/** 公開作業フォルダ */
#	const DIRECTOR_PUBLISH_WORK_PATH_1 = "\\\\10.0.0.16\ssroot$\hpshonin_work";
#	const DIRECTOR_PUBLISH_WORK_PATH_2 = "\\\\10.0.0.19\ssroot$\hpshonin_work";

	/** 自サーバ名（プロジェクト詳細用） */
	const HOME_URL = 'http://reverpro.cloudapp.net/ss';

	/** 承認用URL */
	const URL_APPROVAL = "http://hp-shonin.cloudapp.net/pe/static/prv";
	/** 公開用URL */
	const URL_PUBLISH = "http://hp-shonin.cloudapp.net/pe/static/stg";

	/** 承認用URL(diff用) */
	const URL_APPROVAL_DIFF = "http://127.0.0.1/pe/static/prv";
	/** 公開用URL(diff用) */
	const URL_PUBLISH_DIFF = "http://127.0.0.1/pe/static/stg";

	/** システムユーザーID */
	const USER_ID_SYSTEM = 0;

	/** cakeAPPパス */
	const CAKE_APP_PATH = 'C:\hpshonin\webapp\hpshonin\app';
	/** cakeコマンドパス */
	const CAKE_COMMAND_PATH = 'C:\hpshonin\webapp\hpshonin\app\Console';

	/** MTデータベース（編集用）サーバー */
	const MT_EDIT_DB_SERVER = 'localhost';
	/** MTデータベース（編集用）ユーザーパスワード */
	const MT_EDIT_DB_NAME = 'mt';
	/** MTデータベース（編集用）ユーザーID */
	const MT_EDIT_DB_USER = 'mtuser';
	/** MTデータベース（編集用）ユーザーパスワード */
	const MT_EDIT_DB_USER_PASSWORD = 'HPSh0n!n';

	/** 自サーバ名（メール用） */
	const MAIL_HOME_URL = 'http://hp-shonin.cloudapp.net/pe/hpshonin';
	/** メールタイトル用文字列 **/
	const MAIL_TITLE_HEAD ='[特設サイト]';

	/** 依頼用・公開承認ウィンドウのオプション **/
	const WINDOW_REQUEST_OPSION = 'width=500,height=550,resizable=yes,menubar=no,toolbar=no,scrollbars=yes';
	/** 公開設定用ウィンドウのオプション **/
	const WINDOW_UPSET_OPSION   = 'width=500,height=450,resizable=yes,menubar=no,toolbar=no,scrollbars=yes';

	/** GUID用クッキーディレクトリ  */
	const GUID_COOKIE_DIR = "/pe/";

	/** 公開フォルダに残すリビジョン数 */
	const REVISION_NUM_STAY_PUBLIC_DIRECTORY = 2;

	/** Appcmd用 サーバ */
	const APPCMD_SERVER_1 = 'http://10.0.0.16:5985';
	/** Appcmd用 サーバ */
	const APPCMD_SERVER_2 = 'http://10.0.0.19:5985';
	/** Appcmd用 サイト名 */
	const APPCMD_SITE_NAME = 'Default Web Site';
	/** Appcmd用 公開用パス */
	const APPCMD_PATH_NAME = '/ss';
	/** Appcmd用 サーバのユーザー */
	const APPCMD_SERVER_USER = 'tokusetsuuser';
	/** Appcmd用 サーバのユーザーのパスワード */
	const APPCMD_SERVER_USER_PASSWORD = 'P@ssw0rd123';
}
