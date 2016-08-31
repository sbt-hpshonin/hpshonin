<?php
/**
 * 起動バッチ
 * @author smurata
 *
 */
class Batch {

	/**  MTプロジェクト作成 */
	const BATCH_CD_MT_PROJECT_CREATE		= "11";
	/**  MTプロジェクト更新 */
	const BATCH_CD_MT_PROJECT_UPDATE		= "12";
	/**  MTプロジェクト削除 */
	const BATCH_CD_MT_PROJECT_DELETE		= "13";
	/**  MTユーザー作成 */
	const BATCH_CD_MT_USER_CREATE			= "14";
	/**  MTユーザー更新 */
	const BATCH_CD_MT_USER_UPDATE			= "15";
	/**  MTユーザー削除 */
	const BATCH_CD_MT_USER_DELETE			= "16";
	/**  MT無所属ユーザー削除 */
	const BATCH_CD_NO_PROJECT_USER_DELETE	= "17";
	/**  パッケージ作成 */
	const BATCH_CD_PACKAGE_CREATE			= "21";
	/**  承認依頼 */
	const BATCH_CD_REQUEST_APPROVAL			= "22";
	/**  承認許可 */
	const BATCH_CD_APPROVAL_OK				= "31";
	/**  承認却下 */
	const BATCH_CD_APPROVAL_NG				= "32";
	/**  公開予定 */
	const BATCH_CD_SCHEDULE					= "41";
	/**  公開 */
	const BATCH_CD_RELEASE					= "42";

	/** MTパスワード生成perlのパス */
	const MT_PASS_GEN = 'C:\Perl64\bin\perl.exe C:\hpshonin\webapp\hpshonin\app\Lib\Utils\genpass.pl';

	/** MT編集用サイトのパス */
	const MT_EDIT_PATH = 'blogroot\%s\blog';

	/** MT編集用サイトのURL */
	const MT_EDIT_URL = '/::/blogroot/%s/blog/';
}
?>