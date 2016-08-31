<?php
App::uses('DateUtil', 'Lib/Utils');
App::uses('AppConstants', 'Lib/Utils');
?>
<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {

	$(function(){
		$('#menu_home').attr('class', '');
		$('#menu_project').attr('class', '');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', 'active');
		$('#menu_password').attr('class', '');
		$('#menu_logout').attr('class', '');
	});

})(jQuery);
</script>
<?php $this->end(); ?>
<div class="row-fluid">
			<div class="span12">
				<?php echo $this->Title->makeTitleBar("ユーザー管理") ?>
				<?php echo $this->Session->flash(); ?>

				<div class="block">
					<?php echo $this->Form->create(array( 'action' => '/search', 'class' => 'form' )); ?>
						<?php echo @$this->Html->link('ユーザー作成', '/users/edit/'.$user['User']['id'], array('class' => 'btn'));?>&nbsp;
						<?php echo $this->Form->text('freeword', array('class' => 'span3', 'placeholder' => 'フリーワード検索…','maxlength'=>100));?>
						<?php echo $this->Form->button('検索', array('class' => 'btn'));?>
					<?php echo $this->Form->end(); ?>
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>ユーザー名</th>
							<th>メールアドレス</th>
							<th>連絡先</th>
							<th>プロジェクト</th>
							<th>アカウント種別</th>
							<!--<th>更新日時</th>-->
						</tr>
					</thead>
					<tbody>
					<?php foreach($users as $user) : ?>

						<tr>
							<td><?php echo h($user['User']['id']); ?></td>
							<td><?php // ユーザー名
								echo $this->Html->link(
									$user['User']['username'],
									'/users/view/'.$user['User']['id']
								);
							?></td>
							<td><?php echo h($user['User']['email']) ?></td>
							<td><?php echo h($user['User']['contact_address']); ?></td>
							<td>
							<?php
							//array_multisort($project['id'], SORT_ASC, $user['ProjectUser']);
							if( $user['User']["roll_cd"] == AppConstants::ROLL_CD_ADMIN  OR
								$user['User']["roll_cd"] == AppConstants::ROLL_CD_PR
							){
								echo "全プロジェクト";
							}
							else{
								$data=$user['ProjectUser'];
								$max_project_id=0;
								$project_name =array();
								foreach($data as $project) {
									$project_name[$project['Project']['id']]=$project['Project']['project_name'];
									$project_isdel[$project['Project']['id']]=$project['Project']['is_del'];
	
									if ($max_project_id<$project['Project']['id']){
	                                    $max_project_id=$project['Project']['id'];
	                                }
	                            }
	                            if ($max_project_id >0){
									for ($i=1;$i<=$max_project_id;$i++){
										if (isset($project_name[$i])){
							 				$project_id=$project['Project']['id'];
							 				if ($project_isdel[$i]=="0"){
							 					echo h($project_name[$i])."<br />";
											}
										}
	
									}
								}
							}
							?>
							</td>
							<td><?php echo h($user['Roll']['roll_name']); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
			</div>
		</div>