<?php
App::uses('DateUtil', 'Lib/Utils');
$user = $this->request->data;

// 戻り先ＵＲＬ取得
$breadcrumb_ary = $this->Session->read("breadcrumb");
ksort($breadcrumb_ary);
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
				<div class="titlebar">ユーザー詳細<?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
				<?php echo $this->Session->flash(); ?>
				<div class="block">
					<div class="text-error">
						<?php
							if(isset($this->validationErrors) && isset($this->validationErrors["User"])){
								foreach($this->validationErrors["User"] as $key => $data){
									foreach($data as $key2 => $data2){
										print($data2) . "<br>";
									}
								}
							}
						?>
					</div>
					<table class="table table-hover">
					<thead>
						<tr>
							<th colspan="2">ユーザー情報</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>ユーザーID</td>
							<td><?php echo h($user['User']['id']); ?></td>
						</tr>
						<tr>
							<td>ユーザー名</td>
							<td><?php echo h($user['User']['username']); ?></td>
						</tr>
						<tr>
							<td>メールアドレス</td>
							<td><?php echo h($user['User']['email']); ?></td>
						</tr>
						<tr>
							<td>連絡先</td>
							<td><?php echo h($user['User']['contact_address']); ?></td>
						</tr>
						<tr>
							<td>アカウント種別</td>
							<td><?php echo h($user['Roll']['roll_name']); ?></td>
						</tr>
					</tbody>
					</table>
				</div>
				<div class="subtitlebar">所属プロジェクト一覧</div>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>プロジェクト名</th>
							<th>管理</th>
							<th>サイト名</th>
							<!--<th>更新</th>-->
						</tr>
					</thead>
					<tbody>
						<?php foreach($project_user as $project) : ?>
							<?php if ( $project['Project']["is_del"] == AppConstants::FLAG_ON  ) { ?>
								<?php continue; ?>
							<?php } ?>
							<tr>
								<td><?php echo h($project['Project']['id'] ); ?></td>
								<td><?php echo $this->Html->link( $project['Project']['project_name'] ,'/projects/view/'.$project['Project']['id'] ); ?></td>
								<td><?php echo h( $project['Project']['department_name'] ); ?></td>
								<td><?php echo h( $project['Project']['site_name'] ); ?></td>
								<!--<td><?php echo DateUtil::dateFormat($project['Project']['modified'], 'Y-m-d H:i'); ?></td>-->
							</tr>
						<?php endforeach; ?>
					</tbody>
					</table>
				</div>
				<div class="mB20">
					<?php echo $this->Html->link('<i class="icon icon-chevron-left"></i>戻る', $breadcrumb, array('escape' => false, 'class' => 'btn')); ?>
					<?php if ( $roll_cd != AppConstants::ROLL_CD_PR || $user['User']['roll_cd'] != AppConstants::ROLL_CD_ADMIN){
						echo $this->Html->link('編集', '/users/edit/'.$user['User']['id'], array('class' => 'btn'));
					}?>
				</div>
			</div>
		</div>
<!--<pre>
  <?php print_r($user); ?>
</pre>-->
