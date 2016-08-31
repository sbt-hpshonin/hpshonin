<?php
App::uses('Status', 'Lib');
App::uses('AppConstants', 'Lib/Constants');
App::uses('DateUtil', 'Lib/Utils');

//公開設定の変更ボタン表示用FLG
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
						<tr>
							<td><!-- # -->
								<?php echo h($package['Package']['id']); ?>
							</td>
							<td><!-- パッケージ名 -->
								<?php // パッケージ名
										echo $package['Package']['package_name'];
								?>
							</td>
							<td><!-- 種別 -->
								<?php if($package['Package']['operation_cd'] == AppConstants::OPERATION_CD_PUBLIC) echo AppConstants::OPERATION_NAME_PUBLIC; else echo AppConstants::OPERATION_NAME_DELETE; ?>
							</td>
							<td><!-- 更新者 -->
								<?php echo h($package['ModifiedUser']['username']); ?>
							</td>
							<td><!-- ステータス -->
								<?php 
								if( $package['Package']['is_del'] != 0 ){
									echo "削除";
								 }
								else{
								?>
									<?php echo Status::getName($package['Package']['status_cd']); ?>
								<?php } ?>
							</td>
							<td><!--  承認依頼-->
								<?php if ($package['Package']['request_modified']) echo DateUtil::dateFormat($package['Package']['request_modified' ], 'y/m/d H:i');?>
								</td>
							<td><!-- 公開承認 -->
								<?php if ($package['Package']['approval_modified']) echo DateUtil::dateFormat($package['Package']['approval_modified'], 'y/m/d H:i'); ?>
 					     		<?php if($project['Project']['is_del'] == 0){ ?>
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
 					    				case Status::STATUS_CD_RELEASE_REJECT		:	/**  公開取消CD */
 					    				case Status::STATUS_CD_RELEASE_ERROR		:	/**  公開エラーCD */
 					    					break;
 					    				case Status::STATUS_CD_RELEASE_EXPIRATION	:	/**  有効期限切れCD */
 					    					if ($package['Package']['request_modified']!=NULL and
 					    					$package['Package']['approval_modified']=="") {
 					    						print '<i class="icon icon icon-remove"></i>';
 					    					}
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
										echo "公開前<br />準備中";
										break;
								}
								?>
								<?php
									if($project['Project']['is_del'] == 0){
										switch($package['Package']['status_cd']){
											case Status::STATUS_CD_PACKAGE_READY		:	/**  パッケージ準備CD */
											case Status::STATUS_CD_PACKAGE_ENTRY		:	/**  パッケージ登録CD */
											case Status::STATUS_CD_APPROVAL_REQUEST		:	/**  承認依頼CD */
											case Status::STATUS_CD_APPROVAL_OK			:	/**  承認許可CD */
											case Status::STATUS_CD_RELEASE_RESERVE		:	/**  公開予約CD */
											case Status::STATUS_CD_RELEASE_NOW			:	/**  即時公開CD */
											case Status::STATUS_CD_RELEASE_READY		:	/** 公開事前準備CD */
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
												if ($package['Package']['public_reservation_datetime']!="") {
													print '<i class="icon icon icon-remove"></i>';
												}
												break;
										}
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
					<button class="btn" onClick="parent.$.fancybox.close();">戻る</button>
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
