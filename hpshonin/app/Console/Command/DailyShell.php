<?php
App::uses("FileUtil", "Lib/Utils");
App::uses("BatchCleanPackageContentFileController", "Controller");
App::uses("BatchCleanBlogPackageImageFileController", "Controller");
App::uses("BatchCleanProjectController", "Controller");
App::uses("BatchCleanPublicFileController", "Controller");
App::uses("BatchExpirationPackageController", "Controller");
App::uses("BatchNoProjectUserController", "Controller");

/**
 * 日毎バッチシェルクラス
 * @author smurata
 *
 */
class DailyShell extends AppShell {

	/**
	 * メイン処理
	 * ・静的パッケージのコンテンツファイル削除
	 * ・削除プロジェクトの公開フォルダ削除
	 * ・有効期限切れパッケージのステータス変更
	 * ・無所属ユーザーの削除
	 */
	public function main() {
		// パッケージのコンテンツファイル削除
		$controller = new BatchCleanPackageContentFileController();
		$controller->execute();

		// 削除プロジェクトの公開フォルダ削除
		$controller = new BatchCleanProjectController();
		$controller->execute();

		// 公開フォルダにある過去に公開したファイルの削除
		$controller = new BatchCleanPublicFileController();
		$controller->execute();

		// 有効期限切れパッケージのステータス変更
		$controller = new BatchExpirationPackageController();
		$controller->execute();

		// 無所属ユーザーの削除
		$controller = new BatchNoProjectUserController();
		$controller->execute();
	}
}
?>