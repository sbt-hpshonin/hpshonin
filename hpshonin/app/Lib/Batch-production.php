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
	/**  差し戻し */
	const BATCH_CD_RESTORE					= "51";
	
	
	/**  MTプロジェクト作成 */
	const BATCH_NAME_MT_PROJECT_CREATE		= "MTプロジェクト作成";
	/**  MTプロジェクト更新 */
	const BATCH_NAME_MT_PROJECT_UPDATE		= "MTプロジェクト更新";
	/**  MTプロジェクト削除 */
	const BATCH_NAME_MT_PROJECT_DELETE		= "MTプロジェクト削除";
	/**  MTユーザー作成 */
	const BATCH_NAME_MT_USER_CREATE			= "MTユーザー作成";
	/**  MTユーザー更新 */
	const BATCH_NAME_MT_USER_UPDATE			= "MTユーザー更新";
	/**  MTユーザー削除 */
	const BATCH_NAME_MT_USER_DELETE			= "MTユーザー削除";
	/**  MT無所属ユーザー削除 */
	const BATCH_NAME_NO_PROJECT_USER_DELETE	= "MT無所属ユーザー削除";
	/**  パッケージ作成 */
	const BATCH_NAME_PACKAGE_CREATE			= "パッケージ作成";
	/**  承認依頼 */
	const BATCH_NAME_REQUEST_APPROVAL		= "承認依頼";
	/**  承認許可 */
	const BATCH_NAME_APPROVAL_OK			= "承認許可";
	/**  承認却下 */
	const BATCH_NAME_APPROVAL_NG			= "承認却下";
	/**  公開予定 */
	const BATCH_NAME_SCHEDULE				= "公開予定";
	/**  公開 */
	const BATCH_NAME_RELEASE				= "公開";
	/**  差し戻し */
	const BATCH_NAME_RESTORE				= "差し戻し";
	
	
	/** MTパスワード生成perlのパス */
	const MT_PASS_GEN = 'C:\Perl64\bin\perl.exe C:\hpshonin\webapp\hpshonin\app\Lib\Utils\genpass.pl';

	/** MT編集用サイトのパス */
	const MT_EDIT_PATH = 'blogroot\%s\blog';

	/** MT編集用サイトのURL */
	const MT_EDIT_URL = '/::/blogroot/%s/blog/';

	
	/**
	 * バッチコードの名称を取得
	 * @param $batchCd	バッチCD
	 * @author hsuzuki
	 */
	static function getName($batchCd) {
		switch ($batchCd) {
			case Batch::BATCH_CD_MT_PROJECT_CREATE		:
				return  Batch::BATCH_NAME_MT_PROJECT_CREATE		;
			case Batch::BATCH_CD_MT_PROJECT_UPDATE		:
				return  Batch::BATCH_NAME_MT_PROJECT_UPDATE		;
			case Batch::BATCH_CD_MT_PROJECT_DELETE		:
				return  Batch::BATCH_NAME_MT_PROJECT_DELETE		;
			case Batch::BATCH_CD_MT_USER_CREATE			:
				return  Batch::BATCH_NAME_MT_USER_CREATE		;
			case Batch::BATCH_CD_MT_USER_UPDATE			:
				return  Batch::BATCH_NAME_MT_USER_UPDATE		;
			case Batch::BATCH_CD_MT_USER_DELETE			:
				return  Batch::BATCH_NAME_MT_USER_DELETE		;
			case Batch::BATCH_CD_NO_PROJECT_USER_DELETE	:
				return  Batch::BATCH_NAME_NO_PROJECT_USER_DELETE;
			case Batch::BATCH_CD_PACKAGE_CREATE			:
				return  Batch::BATCH_NAME_PACKAGE_CREATE		;
			case Batch::BATCH_CD_REQUEST_APPROVAL		:
				return  Batch::BATCH_NAME_REQUEST_APPROVAL		;
			case Batch::BATCH_CD_APPROVAL_OK			:
				return  Batch::BATCH_NAME_APPROVAL_OK			;
			case Batch::BATCH_CD_APPROVAL_NG			:
				return  Batch::BATCH_NAME_APPROVAL_NG			;
			case Batch::BATCH_CD_SCHEDULE				:
				return  Batch::BATCH_NAME_SCHEDULE				;
			case Batch::BATCH_CD_RELEASE				:
				return  Batch::BATCH_NAME_RELEASE				;
			case Batch::BATCH_CD_RESTORE				:
				return  Batch::BATCH_NAME_RESTORE				;
			default:
				return "";
		}
	}
}
?>
