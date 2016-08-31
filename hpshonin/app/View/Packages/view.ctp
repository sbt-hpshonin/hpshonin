<?php
App::uses('DateUtil', 'Lib/Utils');
App::uses('MsgConstants', 'Lib/Constants');
App::uses('AppConstants',	'Lib/Constants');

// 戻り先ＵＲＬ取得
$breadcrumb_ary = $this->Session->read("breadcrumb");
ksort($breadcrumb_ary);
$breadcrumb = "";
if(is_array($breadcrumb_ary) == true ){
	$breadcrumb = array_pop($breadcrumb_ary);
}
if( $breadcrumb == ""){
	$breadcrumb = "../../";
}


switch ($package['Package']['status_cd']){
	case Status::STATUS_CD_PACKAGE_READY :
		// ステータスが準備中のものは、ファイル一覧はリンクしない
		$link_flg = false;
		break;
	case Status::STATUS_CD_RELEASE_COMPLETE :
	case Status::STATUS_CD_PACKAGE_READY_ERROR :
	case Status::STATUS_CD_PACKAGE_READY_REJECT :
	case Status::STATUS_CD_APPROVAL_REJECT :
	case Status::STATUS_CD_RELEASE_EXPIRATION :
		// ステータスが完了しているものは、ファイル一覧はリンクしない
		$link_flg = false;
		break;
	default:
		if($package['Package']['is_del']==1){
			// 削除したものは、ファイル一覧からリンクしない
			$link_flg = false;
		}
		else{
			// ステータスが完了していないものは、ファイル一覧からリンクする
			$link_flg = true;
		}
		break;
}
?>
<?php $this->start('script');?>
<script type="text/javascript">
(function ($) {
	$(function(){
		// 削除選択時処理
		$('#delete').click(function(){
			if (confirm('<?php echo MsgConstants::CONFIRM_DELETE; ?>') ) {
				$('#package_edit_form').attr('action', '<?php echo $this->Html->url('/packages/delete/'.$package['Package']['id']); ?>');
				$('#package_edit_form').submit();
				return false;
			}
		});
	});
})(jQuery);
</script>
<?php $this->end(); ?>
	
<div class="row-fluid">
			<div class="span12">
				<span class="titlebar" style=" width:97%; height:15px; display: inline-block;_display: inline;">
					<div style="z-index: 1; position: absolute; right:32px; float: right;"><?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
					<div style="z-index: 0; position: relative; text-overflow:clip; white-space: nowrap; overflow:hidden; width:90%; height:20px;">パッケージ詳細 - <?php echo h($project['Project']['project_name']); ?></div>
				</span>
				<?php echo $this->Session->flash(); ?>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th colspan="2">パッケージ情報</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td width="30%">パッケージID</td>
							<td width="70%">
							<?php
							 $packege_id=h($package['Package']['id']);
							 echo $packege_id;
							 ?>
							 </td>
						</tr>
						<tr>
							<td>パッケージ名</td>
							<td><?php echo h($package['Package']['package_name']); ?></td>
						</tr>
						<tr>
							<td>種別</td>
							<td><?php
								if($package['Package']['is_blog']==1){
									// ブログの場合
									echo h("ブログ");
								}
								else{
									
							        if (h($package['Package']['operation_cd'])==AppConstants::OPERATION_CD_PUBLIC) { echo(AppConstants::OPERATION_NAME_PUBLIC); }
							        if (h($package['Package']['operation_cd'])==AppConstants::OPERATION_CD_DELETE) { echo(AppConstants::OPERATION_NAME_DELETE); }
							    }
							?></td>
						</tr>
						<tr>
							<td>公開予定日</td>
							<td><?php echo DateUtil::dateFormat( $package['Package']['public_due_date'], 'Y/m/d'); ?></td>
						</tr>
						<tr>
							<td>コメント</td>
							<td><?php echo $this->Form->textarea('Package.camment', array('class' => 'span10','value'=>h($package['Package']['camment']),'disabled'=>'disabled')); ?></td>
							</tr>
						<tr>
							<td>ステータス</td>
							<td>
								<?php 
								if( $package['Package']['is_del'] == 1 ){
									echo "削除";
								}
								else{
									echo $this->Status->obtainStatusText( $package['Package']['status_cd'] );
									if($package['Package']['message'] != ""){
										echo "<br/><div class='text-error'>". h($package['Package']['message'] ) . "</div>";
									}
								} 
								?>
							</td>
						</tr>
					</tbody>
					</table>
					<table class="table mB0">
					<thead>
						<tr>
							<th>承認依頼</th>
							<th>公開承認</th>
							<th>公開設定</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php // 承認依頼 ///////////////////////////////////////////////////////////////// ?>
								<?php // ボタンの場合 ?>
								<?php if ( $package['Package']['is_del'] == 0 && $package['Package']['status_cd'] == STATUS::STATUS_CD_PACKAGE_ENTRY && $roll_cd != AppConstants::ROLL_CD_PR ) { ?>
									<a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('/projects/request/'. $package['Package']['id']); ?>','request','<?php echo AppConstants::WINDOW_REQUEST_OPSION; ?>'); return false;">依頼</a>
								<?php // 日付の場合 ?>
								 <?php } else if( $package['Package']['status_cd'] != STATUS::STATUS_CD_PACKAGE_READY && $package['Package']['status_cd'] != STATUS::STATUS_CD_PACKAGE_READY_REJECT ){ ?>
									<?php echo DateUtil::dateFormat( $package['Package']['request_modified'], 'y/m/d H:i'); ?>
									<?php if ( $package['Package']['status_cd'] == STATUS::STATUS_CD_APPROVAL_REQUEST ){ ?>
										<i class="icon icon-chevron-right"></i>
									<?php } ?>
	                            <?php } ?>
	                            <?php // ×
							      	if (( $package['Package']['status_cd'] == STATUS::STATUS_CD_PACKAGE_READY_REJECT) or ( $package['Package']['status_cd'] == STATUS::STATUS_CD_PACKAGE_READY_ERROR )){
										//echo('<i class="icon icon icon-remove"></i>');
									}
							    ?>
							</td>
							<td><?php // 公開承認 ///////////////////////////////////////////////////////////////// ?>
								<?php // ボタンの場合 ?>
								<?php if ( $package['Package']['is_del'] == 0 && $package['Package']['status_cd'] == STATUS::STATUS_CD_APPROVAL_REQUEST ) { ?>
									<?php if( $roll_cd == AppConstants::ROLL_CD_PR || $roll_cd == AppConstants::ROLL_CD_ADMIN ) { ?>
										<a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('/projects/approval/'. $package['Package']['id']); ?>','request','<?php echo AppConstants::WINDOW_REQUEST_OPSION; ?>'); return false;">承認</a>
									<?php } ?>

								<?php // 日付の場合 ?>
								<?php } else if( $package['Package']['status_cd'] != STATUS::STATUS_CD_PACKAGE_READY && $package['Package']['status_cd'] != STATUS::STATUS_CD_PACKAGE_ENTRY  && $package['Package']['status_cd'] != STATUS::STATUS_CD_PACKAGE_READY_REJECT ){ ?>
									<?php echo DateUtil::dateFormat( $package['Package']['approval_modified'], 'y/m/d H:i'); ?>
									<?php if ( $package['Package']['status_cd'] == STATUS::STATUS_CD_APPROVAL_REJECT ){ ?>
										<i class="icon icon-remove"></i>
									<?php } else if( $package['Package']['status_cd'] == STATUS::STATUS_CD_APPROVAL_OK || $package['Package']['status_cd'] == STATUS::STATUS_CD_RELEASE_RESERVE || $package['Package']['status_cd'] == STATUS::STATUS_CD_RELEASE_REJECT  ) { ?>
										<i class="icon icon-circle"></i>
									<?php } ?>
								<?php } ?>
							</td>
							<td><?php // 公開設定 ///////////////////////////////////////////////////////////////// ?>
								<?php
								switch($package['Package']['status_cd']){
									case Status::STATUS_CD_PACKAGE_READY		:	/**  パッケージ準備CD */
									case Status::STATUS_CD_PACKAGE_ENTRY		:	/**  パッケージ登録CD */
									case Status::STATUS_CD_APPROVAL_REQUEST		:	/**  承認依頼CD */
									case Status::STATUS_CD_APPROVAL_OK			:	/**  承認許可CD */
										break;
									case Status::STATUS_CD_RELEASE_RESERVE		:	/**  公開予約CD */
									case Status::STATUS_CD_RELEASE_NOW			:	/**  即時公開CD */
										if ($package['Package']['public_reservation_datetime'] !== "" &&
										$package['Package']['is_del'] == 0
										){
											echo DateUtil::dateFormat($package['Package']['public_reservation_datetime'], 'y/m/d H:i');
										}
										break;
									case Status::STATUS_CD_RELEASE_COMPLETE	:		/**  公開完了CD */
										echo DateUtil::dateFormat($package['Package']['modified'], 'y/m/d H:i');
										break;
									case Status::STATUS_CD_RELEASE_EXPIRATION	:	/**  有効期限切れCD */
									case Status::STATUS_CD_RELEASE_ERROR		:	/**  公開エラーCD */
									case Status::STATUS_CD_PACKAGE_READY_ERROR	:	/**  パッケージ準備エラーCD */
									case Status::STATUS_CD_PACKAGE_READY_REJECT	:	/**  パッケージ登録却下CD */
									case Status::STATUS_CD_APPROVAL_REJECT		:	/**  承認却下CD */
									case Status::STATUS_CD_RELEASE_REJECT		:	/**  公開取消CD */
										break;
								}
								

								 ?>
 					     		<?php if($package['Package']['is_del'] == 0 && $project['Project']['is_del'] == 0){ ?>
									<?php
										if (($package['Package']['status_cd']==Status::STATUS_CD_APPROVAL_OK )
											OR ($package['Package']['status_cd']==Status::STATUS_CD_RELEASE_REJECT )
											OR ($package['Package']['status_cd']==Status::STATUS_CD_RELEASE_ERROR  ))
										{
									?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN : // 管理者
											case AppConstants::ROLL_CD_DEVELOP : // 製作会社
											case AppConstants::ROLL_CD_SITE : // サイト担当者
											// case AppConstants::ROLL_CD_PR : // 広報室
										?>
										<a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('/publish/upset/'. $package['Project']['id'] .'/'. $package['Package']['id']); ?>','upset','<?php echo AppConstants::WINDOW_UPSET_OPSION; ?>'); return false;">
									 		公開設定</a>
								 		<?php } ?>
									<?php } ?>
									<?php if ($public_reservation_flg=='1') {?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN : // 管理者
											case AppConstants::ROLL_CD_DEVELOP : // 製作会社
											case AppConstants::ROLL_CD_SITE : // サイト担当者
											// case AppConstants::ROLL_CD_PR : // 広報室
										?>
									<br/><a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('/publish/upset/'. $package['Project']['id'] .'/'. $package['Package']['id']); ?>','upset','<?php echo AppConstants::WINDOW_UPSET_OPSION; ?>'); return false;">
										変更・取消</a>
								 		<?php } ?>
									<?php } ?>
								<?php } ?>
								<?php
									switch($package['Package']['status_cd']){
										case Status::STATUS_CD_PACKAGE_READY		:	/**  パッケージ準備CD */
										case Status::STATUS_CD_PACKAGE_ENTRY		:	/**  パッケージ登録CD */
										case Status::STATUS_CD_APPROVAL_REQUEST		:	/**  承認依頼CD */
										case Status::STATUS_CD_APPROVAL_OK			:	/**  承認許可CD */
										case Status::STATUS_CD_RELEASE_RESERVE		:	/**  公開予約CD */
										case Status::STATUS_CD_RELEASE_NOW			:	/**  即時公開CD */
											// print '<i class="icon icon-chevron-right"></i>';
											break;
										case Status::STATUS_CD_RELEASE_COMPLETE	:		/**  公開完了CD */
											print '<i class="icon icon-circle"></i>';
											break;
										case Status::STATUS_CD_PACKAGE_READY_ERROR	:	/**  パッケージ準備エラーCD */
										case Status::STATUS_CD_PACKAGE_READY_REJECT	:	/**  パッケージ登録却下CD */
										case Status::STATUS_CD_APPROVAL_REJECT		:	/**  承認却下CD */
										case Status::STATUS_CD_RELEASE_REJECT		:	/**  公開取消CD */
											break;
										case Status::STATUS_CD_RELEASE_ERROR		:	/**  公開エラーCD */
											print '<i class="icon icon icon-remove"></i>';
						    				break;
										case Status::STATUS_CD_RELEASE_EXPIRATION	:	/**  有効期限切れCD */
											break;
					    			}
					    		?>

							</td>
						</tr>
					</tbody>
					</table>
				</div>

				<div class="subtitlebar">ファイル一覧</div>
				<?php  if ($package['Package']['is_blog']=="1") {?>
				<?php // 記事一覧 /////////////////////////////////////////////////////////// ?>
					<table class="treetable">
						<thead>
							<tr>
								<th>記事タイトル</th>
								<th width="20%">更新日時</th>
							</tr>
						</thead>
						<tbody>
						<?php $i = 0;foreach( $mt_posts as $post){ ?>
							<tr data-tt-id='<?php echo $i ++; ?>'>
								<td>
									<?php
									// ファイルアイコン・記事名・地球儀アイコンの表示
									if($link_flg == false){
										// ステータスが完了しているものは、ファイル一覧はリンクしない
											// 機能種別：公開
											switch ($post["MtPost"]['modify_flg']){
												case AppConstants::MODIFY_FLG_ADD : // 追加
													echo "<span class='file3'>". $post["MtPost"]["subject"] ."</span>";
													break;
												case AppConstants::MODIFY_FLG_MOD: // 変更
												case AppConstants::MODIFY_FLG_NO_MOD: // 無変更
													echo "<span class='file'>" . $post["MtPost"]["subject"] .'</span>';
													break;
												case AppConstants::MODIFY_FLG_DEL: // 削除
													echo "<span class='file1'>" . $post["MtPost"]["subject"] ."</span>";
													break;
											}
									}
									else{
										// ステータスが完了していないものは、ファイル一覧からリンクする
											// 機能種別：公開
											switch ($post["MtPost"]['modify_flg']){
												case AppConstants::MODIFY_FLG_ADD : // 追加
													echo "<span class='file3'>". $post["MtPost"]["subject"] ."</span>";
													echo '<a href="'. $post["MtPost"]['new_url'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
												case AppConstants::MODIFY_FLG_MOD: // 変更
												case AppConstants::MODIFY_FLG_NO_MOD: // 無変更
													echo '<a href="javascript:void(0);" onClick="window.open(\''. $this->Html->Url('diff_blog/'.$package['Package']['id'] ."/". $post["MtPost"]["edit_mt_post_id"]) .'\',\'diff\',\'width=1000,height=730\'); return false;"><span class=\'file\'>'. $post["MtPost"]["subject"] .'</span></a>';
													echo '<a href="'. $post["MtPost"]['new_url'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
												case AppConstants::MODIFY_FLG_DEL: // 削除
													echo "<span class='file1'>". $post["MtPost"]["subject"] ."</span>";
													echo '<a href="'. $post["MtPost"]['old_url'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
											}
									}
									?>
								</td>
								<td><?php echo DateUtil::dateFormat( $post["MtPost"]["post_modified"], 'Y/m/d H:i:s') ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php }?>
				<?php if (count($file) > 0) {?>
				<?php // ファイル一覧 /////////////////////////////////////////////////////////// ?>
					<table class="treetable">
					<thead>
						<tr>
							<th>名前</th>
							<th>サイズ</th>
							<th width="20%">更新日時</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($file as $filedata) : ?>
						<tr data-tt-id='<?php echo $filedata['id'] ?>'  <?php if( $filedata['parent_id'] != "") { echo "data-tt-parent-id='".$filedata['parent_id']."'"; }?> >
							<td>
								<?php 
								if( $filedata['mode'] == "folder") {
									// フォルダー表示 
									echo "<span class='". $filedata['mode'] ."'>" . $filedata['text'] ."</span>";
								}else {
									// ファイルアイコン・記事名・地球儀アイコンの表示
									if($link_flg == false){ 
										echo "<span class='{$filedata['mode']}'>{$filedata['text']}</span>";
									}
									else{
										if($package['Package']['operation_cd']== AppConstants::OPERATION_CD_PUBLIC){
											// 機能種別：公開
											switch ($filedata['modify_flg']){
												case AppConstants::MODIFY_FLG_ADD : // 追加
													echo "<span class='{$filedata['mode']}'>{$filedata['text']}</span>";
													echo '<a href="'. $new_base_url . $filedata['filepass'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
												case AppConstants::MODIFY_FLG_MOD :
												case AppConstants::MODIFY_FLG_NO_MOD :
													echo '<a href="#" onClick="window.open(\''. $this->Html->Url('diff/'.$filedata['contents_files_id']) .'\',\'diff\',\'width=1000,height=730\'); return false;"><span class=\''. ($filedata['mode']) . '\'>'. $filedata['text'] . '</span></a>';
													echo '<a href="'. $new_base_url . $filedata['filepass'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
												case AppConstants::MODIFY_FLG_DEL:
													echo "<span class='{$filedata['mode']}'>{$filedata['text']}</span>";
													echo '<a href="'. $old_base_url . $filedata['filepass'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
													break;
											}
										}
										else{
											// 機能種別：削除
											echo "<span class='{$filedata['mode']}'>{$filedata['text']}</span>";
											echo '<a href="'. $old_base_url . $filedata['filepass'] .'" target="_blank"><i class="icon icon-globe"></i></a>';
										}
									}
									?>
								 <?php }?>
							</td>
							<td><?php echo( $filedata['file_size'] ) ?></td>
							<td>
								<?php if( $filedata['mode'] == "folder") { ?>
									<?php echo $filedata['file_modified'] ?>
							 	<?php } else{ ?>
									<?php if($filedata['file_modified']){ echo DateUtil::dateFormat( $filedata['file_modified'], 'Y/m/d H:i:s'); } ?>
							 	<?php } ?>
						 	</td>
						 </tr>
					<?php endforeach; ?>
					</tbody>
					</table>
					<?php } ?>
				<div class="subtitlebar">履歴</div>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th>日時</th>
							<th>イベント</th>
							<th>操作者</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$cnt = 0;
						foreach($historys as $history) : 
							$cnt ++;
							
							if( $package['Package']['is_del'] == 1 and $cnt == 1 ){
							?>
								<tr>
									<td><?php echo DateUtil::dateFormat( $history['HistoryPackage']['modified'], 'Y/m/d H:i') ?></td>
									<td>削除</td>
									<td><?php echo h($history['ModifiedUser']['username']) ?></td>
								</tr>
							<?php 
							}
							if($history['HistoryPackage']['status_cd'] != Status::STATUS_CD_PACKAGE_READY){
							?>
								<tr>
									<td><?php echo DateUtil::dateFormat( $history['HistoryPackage']['modified'], 'Y/m/d H:i') ?></td>
									<td><?php echo $this->Status->obtainStatusText( $history['HistoryPackage']['status_cd'] );  ?></td>
									<td><?php echo h($history['ModifiedUser']['username']) ?></td>
								</tr>
							<?php 
							}
						endforeach; 
						?>
					</tbody>
					</table>
				</div>

				<form id='package_edit_form' method="post">
				<?php echo $this->GuidChk->putGuid(); ?>
				
				<div class="mB20">
					<a href="<?php echo $breadcrumb; ?>" class="btn" ><i class="icon icon-chevron-left"></i>戻る</a>
					<?php
						switch ($package['Package']['status_cd']){
							case Status::STATUS_CD_PACKAGE_ENTRY :
							case Status::STATUS_CD_PACKAGE_READY_REJECT :
							case Status::STATUS_CD_APPROVAL_OK :
							case Status::STATUS_CD_APPROVAL_REJECT :
							case Status::STATUS_CD_RELEASE_REJECT :
							case Status::STATUS_CD_RELEASE_EXPIRATION :
							case Status::STATUS_CD_RELEASE_ERROR :
								if(	$package['Package']['is_del'] == 0 ){
									echo $this->Html->link('削除', 'javascript:void(0)' ,array('class' => 'btn',"id"=>"delete"));
								}
								else{
									echo $this->Html->link('削除', "javascript:void(0)",array('class' => 'btn',"disabled" => TRUE ));
								}
								break;
							default:
								echo $this->Html->link('削除', 'javascript:void(0)' ,array('class' => 'btn',"id"=>"delete"));
								break;
						}
					?>
				</div>
				</form>
			</div>
</div>
