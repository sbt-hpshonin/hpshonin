<?php
App::uses('AppHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');


/**
 * ページヘッダ作成用ライブラリ
 *
 * @author hsuzuki
 */
class TitleHelper extends AppHelper {

	/**
	 * マニュアルへのリンク作成
	 * @param なし
	 * @return Link用文字列
	 */
	static function makeHelpLink(){

		$Html = new HtmlHelper(new View());

		$help_file = "/manual.pdf";
		switch(AuthComponent::user("roll_cd")){
			case AppConstants::ROLL_CD_ADMIN:
				$help_file = "/manual_pr.pdf";
				break;
			case AppConstants::ROLL_CD_PR:
				$help_file = "/manual_pr.pdf";
				break;
			case AppConstants::ROLL_CD_SITE:
				$help_file = "/manual_mgr.pdf";
				break;
			case AppConstants::ROLL_CD_DEVELOP:
				$help_file = "/manual_dev.pdf";
				break;
		}

		return $Html->link(
				'<i class="icon icon-question-sign icon-white"></i>ヘルプ',
				$help_file,
				array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)
		);
	}

	/**
	 * ページヘッダのHTML作成
	 * @param $caption ページタイトル
	 * @param $memo    ページのサブタイトル
	 * @return HTML用文字列
	 */
	static function makeTitleBar($caption,$memo=''){

		if ($memo != ""){
			$memo = " - " . $memo;
		}

		$title_bar
			= '<div class="titlebar">'
			. '<table class="titlebar" style="width: 100%; table-layout: fixed;">'
			. '<tr>'
			. '<td style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"><span style="font-size: 18px;">'. $caption . $memo .'</span></td>'
			. '<td style="width: 60px; text-align: right">'.self::makeHelpLink().'</td>'
			. '</tr>'
			. '</table>'
			. '</div>';

		return $title_bar;
	}
}
