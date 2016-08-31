<?php
App::uses('Status', 'Lib');
App::uses('AppConstants', 'Lib/Constants');
App::uses('DateUtil', 'Lib/Utils');

//公開設定の変更ボタン表示用FLG

// 戻り先ＵＲＬ取得
$breadcrumb_ary = $this->Session->read("breadcrumb");
if(is_array($breadcrumb_ary) == true ){
	ksort($breadcrumb_ary);
}
$breadcrumb = "";
if(is_array($breadcrumb_ary) == true ){
	array_pop($breadcrumb_ary);
	$breadcrumb = array_pop($breadcrumb_ary);
}
if( $breadcrumb == ""){
	$breadcrumb = "../../";
}

?>
<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {

	$(function(){
		$('#menu_home').attr('class', '');
		$('#menu_project').attr('class', 'active');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', '');
		$('#menu_password').attr('class', '');
		$('#menu_logout').attr('class', '');

	});
})(jQuery);
</script>
<?php $this->end(); ?>
<?php
		$auth = $this->Session->read("Auth");
    	$user_id = $auth["User"]["id"];
    	$roll_cd = $auth["User"]["roll_cd"];
?>
    			<div class="row-fluid">
			<div class="span12">
				<?php echo $this->Title->makeTitleBar("プロジェクト詳細",h($project['Project']['project_name'])); ?>
				<?php echo $this->Session->flash(); ?>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th colspan="2">プロジェクト情報</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>プロジェクトID</td>
							<td><?php echo h($project['Project']['id']); ?></td>
						</tr>
						<tr>
							<td>プロジェクト名</td>
							<td><?php echo h($project['Project']['project_name']); ?></td>
						</tr>
						<tr>
							<td>管理</td>
							<td><?php echo h($project['Project']['department_name']); ?></td>
						</tr>
						<tr>
							<td>サイト名</td>
							<td><?php echo h($project['Project']['site_name']); ?></td>
						</tr>
						<tr>
							<td>サイトURL</td>
							<td><?php echo h(AppConstants::HOME_URL ."/". $project['Project']['site_url']); ?>/</td>
						</tr>
						<tr>
							<td>ブログURL</td>
							<td>
								<?php echo h(AppConstants::HOME_URL ."/". $project['Project']['site_url']); ?>/blog/
							</td>
						</tr>
					</tbody>
					</table>
				</div>

				<div class="mB20">
					<a href="<?php echo $breadcrumb; ?>" class="btn" ><i class="icon icon-chevron-left"></i>戻る</a>
					<?php if($project['Project']['is_del'] == 0 and ( $roll_cd == AppConstants::ROLL_CD_ADMIN or $roll_cd == AppConstants::ROLL_CD_PR )) { ?>
						<?php echo $this->Html->link( '編集', array( 'controller' => 'projects', 'action' => 'edit', h($project['Project']['id']) ), array("class" => "btn" ,"escape" => false) ); ?>
					<?php } ?>
				</div>

				<div class="subtitlebar">プロジェクトメンバー</div>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
<!--
							<th style="width: 3em"></th>
-->
							<th>ユーザー名</th>
							<th>メールアドレス</th>
							<th>連絡先</th>
							<th>アカウント種別</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($projectUsers as $projectUser) : ?>
						<tr>
<!--
							<td><input type="checkbox" class="_debug_admin"><input type="radio" name="a" class="_debug_charge"></td>
-->
							<td><?php echo h($projectUser['User']['username']); ?></td>
							<td><?php echo h($projectUser['User']['email']); ?></td>
							<td><?php echo h($projectUser['User']['contact_address']); ?></td>
							<td><?php echo h($projectUser["Roll"]["roll_name"]); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
					</table>
				</div>

				<div class="subtitlebar">更新パッケージ一覧</div>
				<div class="block">
					<form class="form">
						<?php
						 if($project['Project']['is_del'] == 0) {
							if ($roll_cd != AppConstants::ROLL_CD_PR){
						?>
							<?php echo $this->Html->link( '<i class="icon icon-file"></i>パッケージ登録', array( 'controller' => 'packages', 'action' => 'add', h($project['Project']['id']) ), array("class" => "btn" ,"escape" => false) ); ?>
							<?php echo $this->Html->link( '<i class="icon icon-file"></i>ブログパッケージ登録', array( 'controller' => 'packages', 'action' => 'add2', h($project['Project']['id']) ), array("class" => "btn" ,"escape" => false) ); ?>
						<?php } } ?>
						</form>
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>パッケージ名</th>
							<th>種別</th>
							<th>更新者</th>
							<th>ステータス</th>
							<th>承認依頼</th>
							<th>公開承認</th>
							<th>公開設定</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($packages as $package) : ?>
					<?php
							if ($package['Package']['status_cd'] == Status::STATUS_CD_RELEASE_RESERVE ) {
								// ステータス公開予約の場合
								$date= $package['Package']['public_reservation_datetime'];
								$wtimestmp=(strtotime($date));
								if ($wtimestmp > time()){
									// 公開予約日時が現在日時より大きい場合
									$public_reservation_flg=1;
								}else{
									// 公開予約日時が現在日時より小さい場合
									$public_reservation_flg=0;
								}
							}else{
								// ステータス公開完了以外の場合
								$public_reservation_flg=0;
							}
					?>
						<tr>
							<td><!-- # -->
								<?php echo h($package['Package']['id']); ?>
							</td>
							<td><!-- パッケージ名 -->
								<?php // パッケージ名
									if(
										// $package['Package']['status_cd'] != Status::STATUS_CD_PACKAGE_READY  AND
										//$package['Package']['status_cd'] != Status::STATUS_CD_PACKAGE_READY_REJECT AND
										//$package['Package']['status_cd'] != Status::STATUS_CD_PACKAGE_READY_ERROR AND
										$project['Project']['is_del'] == 0
									) {
										echo $this->Html->link(
												$package['Package']['package_name'],
												array(
														'controller' => 'packages',
														'action' => 'view/'. $package['Package']['id']
												)
										);
									}
									else{
										// プロジェクト削除時はリンクしない
										// パッケージ準備中はリンクしない
										echo $package['Package']['package_name'];
									}
								?>
							</td>
							<td><!-- 種別 -->
								<?php
								if($package['Package']['is_blog']==1){
									// ブログの場合
									echo h("ブログ");
								}
								else{
									// それ以外
									if($package['Package']['operation_cd'] == AppConstants::OPERATION_CD_PUBLIC)
										echo AppConstants::OPERATION_NAME_PUBLIC;
									else
										echo AppConstants::OPERATION_NAME_DELETE;
								}
								?>
							</td>
							<td><!-- 更新者 -->
								<?php
									if($package['ModifiedUser']['id']==0){
										echo h("システム");
									}
									else{
										echo h($package['ModifiedUser']['username']);
									}
								?>
							</td>
							<td><!-- ステータス -->
								<?php
								if( $package['Package']['is_del'] != 0 ){
									echo "削除";
								 }
								else{
									if( $package['Package']['status_cd'] == Status::STATUS_CD_PACKAGE_READY_REJECT ){ ?>
										<!-- エラーメッセージウィンドウ表示  -->
										<a href="javascript:void(0);"
										   onclick="$('#err_mes').html('<?php
												$message = $package['Package']['message'];
												$message = strip_tags($message);
												$message = nl2br($message);
												$message = str_replace(array("\r","\n"), '', $message );
												$message = str_replace("\\",'\\\\', $message);
												$message = str_replace('"','&quot;', $message);
												$message = addslashes($message);
												echo  $message; ?>');$.fancybox.open('#alert3',{modal:true,closeBtn:false});return false"><div class='text-error'>
										<?php echo Status::getName($package['Package']['status_cd']); ?>
										</div></a>
									<?php } else { ?>
										<?php echo Status::getName($package['Package']['status_cd']); ?>
									<?php } ?>
								<?php } ?>
							</td>
								<?php // if($package['Package']['status_cd']==91){ echo "<td colspan=3><div class='text-error'>".$package['Package']['message'] . "</div></td></tr>";continue; }?>
							<td><!--  承認依頼-->
								<?php if( $package['Package']['request_modified']) echo DateUtil::dateFormat($package['Package']['request_modified' ], 'y/m/d H:i');?>
 					     		<?php if( $package['Package']['is_del'] == 0 && $project['Project']['is_del'] == 0 ){ ?>
									<?php if ($package['Package']['status_cd'] == Status::STATUS_CD_PACKAGE_ENTRY ) {?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN :  // 管理者
											// case AppConstants::ROLL_CD_DEVELOP: // 製作会社
											case AppConstants::ROLL_CD_SITE:    // サイト担当者
											// case AppConstants::ROLL_CD_PR: // 広報室
										?>
										    <a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('request/'. $package['Package']['id']); ?>','request','<?php echo AppConstants::WINDOW_REQUEST_OPSION; ?>'); return false;">
											    依頼</a>
										<?php } ?>
									<?php } ?>
								<?php } ?>
								<?php
									switch($package['Package']['status_cd']){
										case Status::STATUS_CD_PACKAGE_READY		:	/**  パッケージ準備CD */
										case Status::STATUS_CD_PACKAGE_ENTRY		:	/**  パッケージ登録CD */
											break;
										case Status::STATUS_CD_APPROVAL_REQUEST		:	/**  承認依頼CD */
											print '<i class="icon icon-chevron-right"></i>';
											break;
										case Status::STATUS_CD_APPROVAL_OK			:	/**  承認許可CD */
										case Status::STATUS_CD_RELEASE_RESERVE		:	/**  公開予約CD */
										case Status::STATUS_CD_RELEASE_NOW			:	/**  即時公開CD */
										case Status::STATUS_CD_RELEASE_COMPLETE	:		/**  公開完了CD */
										case Status::STATUS_CD_RELEASE_READY	:		/**  公開事前準備CD */
											break;
										case Status::STATUS_CD_PACKAGE_READY_ERROR	:	/**  パッケージ準備エラーCD */
										case Status::STATUS_CD_PACKAGE_READY_REJECT	:	/**  パッケージ登録却下CD */
											//print '<i class="icon icon icon-remove"></i>';
											break;
										case Status::STATUS_CD_APPROVAL_REJECT		:	/**  承認却下CD */
										case Status::STATUS_CD_RELEASE_REJECT		:	/**  公開取消CD */
										case Status::STATUS_CD_RELEASE_ERROR		:	/**  公開エラーCD */
						    				break;
										case Status::STATUS_CD_RELEASE_EXPIRATION	:	/**  有効期限切れCD */
						    				break;
 					    			}
 					    		?>
								</td>
							<td><!-- 公開承認 -->
								<?php if( $package['Package']['approval_modified']) echo DateUtil::dateFormat($package['Package']['approval_modified'], 'y/m/d H:i'); ?>
 					     		<?php if( $package['Package']['is_del'] == 0 && $project['Project']['is_del'] == 0){ ?>
									<?php if ($package['Package']['status_cd']==Status::STATUS_CD_APPROVAL_REQUEST) { ?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN :   // 管理者
											// case AppConstants::ROLL_CD_DEVELOP: // 製作会社
											// case AppConstants::ROLL_CD_SITE:    // サイト担当者
											case AppConstants::ROLL_CD_PR :      // 広報室
										?>
											<a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('approval/'. $package['Package']['id']); ?>','request','<?php echo AppConstants::WINDOW_REQUEST_OPSION; ?>'); return false;">
									    		承認</a>
									    <?php } ?>
									<?php } ?>
								<?php } ?>
								<?php
									switch($package['Package']['status_cd']){
										case Status::STATUS_CD_PACKAGE_READY		:	/**  パッケージ準備CD */
										case Status::STATUS_CD_PACKAGE_ENTRY		:	/**  パッケージ登録CD */
										case Status::STATUS_CD_APPROVAL_REQUEST		:	/**  承認依頼CD */
											break;
										case Status::STATUS_CD_APPROVAL_OK			:	/**  承認許可CD */
										case Status::STATUS_CD_RELEASE_RESERVE		:	/**  公開予約CD */
										case Status::STATUS_CD_RELEASE_NOW			:	/**  即時公開CD */
										case Status::STATUS_CD_RELEASE_REJECT		:	/**  公開取消CD */
										case Status::STATUS_CD_RELEASE_READY		:	/**  公開事前準備CD */
											print '<i class="icon icon-circle"></i>';
											break;
										case Status::STATUS_CD_RELEASE_COMPLETE	:		/**  公開完了CD */
											// print '<i class="icon icon-chevron-right"></i>';
											break;
										case Status::STATUS_CD_PACKAGE_READY_ERROR	:	/**  パッケージ準備エラーCD */
										case Status::STATUS_CD_PACKAGE_READY_REJECT	:	/**  パッケージ登録却下CD */
											break;
										case Status::STATUS_CD_APPROVAL_REJECT		:	/**  承認却下CD */
											print '<i class="icon icon icon-remove"></i>';
						    				break;
										case Status::STATUS_CD_RELEASE_ERROR		:	/**  公開エラーCD */
						    				break;
										case Status::STATUS_CD_RELEASE_EXPIRATION	:	/**  有効期限切れCD */
											break;
					    			}
					    		?>
							</td>
							<td><!-- 公開設定 -->
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
									case Status::STATUS_CD_RELEASE_READY		:	/** 公開事前準備CD */
										if ($roll_cd == AppConstants::ROLL_CD_ADMIN
											|| $roll_cd == AppConstants::ROLL_CD_SITE
											|| $roll_cd == AppConstants::ROLL_CD_DEVELOP) {
											echo "公開前<br />準備中";
										}
										break;
								}

								 ?>
 					     		<?php if( $package['Package']['is_del'] == 0 && $project['Project']['is_del'] == 0){ ?>
									<?php
										if (($package['Package']['status_cd']==Status::STATUS_CD_APPROVAL_OK )
											OR ($package['Package']['status_cd']==Status::STATUS_CD_RELEASE_REJECT  )
											OR ($package['Package']['status_cd']==Status::STATUS_CD_RELEASE_ERROR  ))
										{
									?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN: // 管理者
											case AppConstants::ROLL_CD_DEVELOP: // 製作会社
											case AppConstants::ROLL_CD_SITE: // サイト担当者
											// case AppConstants::ROLL_CD_PR: // 広報室
										?>
										<a href="javascript:void(0);" class="btn" onClick="window.open('<?php echo $this->Html->Url('/publish/upset/'. $package['Project']['id'] .'/'. $package['Package']['id']); ?>','upset','<?php echo AppConstants::WINDOW_UPSET_OPSION; ?>'); return false;">
									 		公開設定</a>
								 		<?php } ?>
									<?php } ?>
									<?php if ($public_reservation_flg=='1') {?>
										<?php switch($roll_cd){
											case AppConstants::ROLL_CD_ADMIN: // 管理者
											case AppConstants::ROLL_CD_DEVELOP: // 製作会社
											case AppConstants::ROLL_CD_SITE: // サイト担当者
											// case AppConstants::ROLL_CD_PR: // 広報室
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
					<?php endforeach; ?>
					</tbody>
					</table>
					<div class="al_c">
						<div class="pagination">
<!-- 次のページへのリンクを表示する。 -->
							<ul>
							<?php
								if($this->Paginator->first('«', null, null, array('class' => 'disabled'))){
							    	echo "<li>". $this->Paginator->first('«', null, null, array('class' => 'disabled')) . "</li>";
							    }
							    echo "<li>". $this->Paginator->numbers(array('separator' => '</li><li>')) . "</li>";
							    if($this->Paginator->last('»', null, null, array('class' => 'disabled'))){
							    	echo "<li>". $this->Paginator->last('»', null, null, array('class' => 'disabled')) . "</li>";
							    }
							?>
							</ul>
						</div>
					</div>
				</div>
				<div class="mB20">
					<a href="<?php echo $breadcrumb; ?>" class="btn" ><i class="icon icon-chevron-left"></i>戻る</a>
				</div>
			</div>
		</div>

<div class="hide">
	<div id="alert3" class="obox" style="width:500px;">
		<div class="head">
			<p class="title">注意</p>
		</div>
		<div class="body" id="err_mes">
		</div>
		<div class="foot">
			<a href="javascript:void(0)" class="btn" onClick="$.fancybox.close();">閉じる</a>
		</div>
	</div>
</div>
