<?php
/**
 * ステータス
 * @author smurata
 *
 */
class Status {
	/**  パッケージ準備CD */
	const STATUS_CD_PACKAGE_READY		= "00";
	/**  パッケージ登録CD */
	const STATUS_CD_PACKAGE_ENTRY		= "01";
	/**  承認依頼CD */
	const STATUS_CD_APPROVAL_REQUEST	= "02";
	/**  承認許可CD */
	const STATUS_CD_APPROVAL_OK			= "03";
	/**  公開予約CD */
	const STATUS_CD_RELEASE_RESERVE		= "04";
	/**  即時公開CD */
	const STATUS_CD_RELEASE_NOW			= "05";
	/**  公開完了CD */
	const STATUS_CD_RELEASE_COMPLETE	= "06";
	/**  パッケージ準備エラーCD */
	const STATUS_CD_PACKAGE_READY_ERROR	= "90";
	/**  パッケージ登録却下CD */
	const STATUS_CD_PACKAGE_READY_REJECT= "91";
	/**  承認却下CD */
	const STATUS_CD_APPROVAL_REJECT		= "93";
	/**  公開取消CD */	
	const STATUS_CD_RELEASE_REJECT		= "94";
	/**  有効期限切れCD */
	const STATUS_CD_RELEASE_EXPIRATION	= "95";
	/**  公開エラーCD */
	const STATUS_CD_RELEASE_ERROR		= "96";


	/**  パッケージ準備名称 */
	const STATUS_NAME_PACKAGE_READY			= "パッケージ準備";
	/**  パッケージ登録名称 */
	const STATUS_NAME_PACKAGE_ENTRY			= "パッケージ登録";
	/**  承認依頼名称 */
	const STATUS_NAME_APPROVAL_REQUEST		= "承認待ち";
	/**  承認許可名称 */
	const STATUS_NAME_APPROVAL_OK			= "承認済み";
	/**  公開予約名称 */
	const STATUS_NAME_RELEASE_RESERVE		= "公開予約";
	/**  即時公開名称 */
	const STATUS_NAME_RELEASE_NOW			= "即時公開";
	/**  公開完了名称 */
	const STATUS_NAME_RELEASE_COMPLETE		= "公開完了";
	/**  パッケージ準備エラー名称 */
	const STATUS_NAME_PACKAGE_READY_ERROR	= "パッケージ準備エラー";
	/**  パッケージ登録却下名称 */
	const STATUS_NAME_PACKAGE_READY_REJECT	= "パッケージ登録却下";
	/**  承認却下名称 */
	const STATUS_NAME_APPROVAL_REJECT		= "却下";
	/**  公開取消名称 */
	const STATUS_NAME_RELEASE_REJECT		= "公開取消";
	/**  有効期限切れ名称 */
	const STATUS_NAME_RELEASE_EXPIRATION	= "有効期限切れ";
	/**  公開エラー名称 */
	const STATUS_NAME_RELEASE_ERROR			= "公開エラー";

	/**
	 * ステータス名称を取得
	 * @param string $statusCd	ステータスCD
	 */
	static function getName($statusCd) {
		switch ($statusCd) {
			case Status::STATUS_CD_PACKAGE_READY:
				return Status::STATUS_NAME_PACKAGE_READY;

			case Status::STATUS_CD_PACKAGE_ENTRY:
				return Status::STATUS_NAME_PACKAGE_ENTRY;

			case Status::STATUS_CD_APPROVAL_REQUEST:
				return Status::STATUS_NAME_APPROVAL_REQUEST;

			case Status::STATUS_CD_APPROVAL_OK:
				return Status::STATUS_NAME_APPROVAL_OK;

			case Status::STATUS_CD_RELEASE_RESERVE:
				return Status::STATUS_NAME_RELEASE_RESERVE;

			case Status::STATUS_CD_RELEASE_NOW:
				return Status::STATUS_NAME_RELEASE_NOW;

			case Status::STATUS_CD_RELEASE_COMPLETE:
				return Status::STATUS_NAME_RELEASE_COMPLETE;

			case Status::STATUS_CD_PACKAGE_READY_ERROR:
				return Status::STATUS_NAME_PACKAGE_READY_ERROR;

			case Status::STATUS_CD_PACKAGE_READY_REJECT:
				return Status::STATUS_NAME_PACKAGE_READY_REJECT;

			case Status::STATUS_CD_APPROVAL_REJECT:
				return Status::STATUS_NAME_APPROVAL_REJECT;

			case Status::STATUS_CD_RELEASE_REJECT:
				return Status::STATUS_NAME_RELEASE_REJECT;

			case Status::STATUS_CD_RELEASE_EXPIRATION:
				return Status::STATUS_NAME_RELEASE_EXPIRATION;

			case Status::STATUS_CD_RELEASE_ERROR:
				return Status::STATUS_NAME_RELEASE_ERROR;

			default:
				return "";
		}
	}
}
?>