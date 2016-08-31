<?php
App::uses('DateUtil', 'Lib/Utils');
App::uses('AppConstants',	'Lib/Constants');
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
<?php // 個別にstyleを入れる場合
$this->start('css');?>
<style>
</style>
<?php $this->end(); ?>

		<div class="row-fluid">
			<div class="span12">
				<div class="titlebar">プロジェクト一覧<?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
				<?php echo $this->Session->flash(); ?>
				<div class="block">
					<form class="form" method="post">
						<?php 
						if($roll_cd == AppConstants::ROLL_CD_ADMIN or
						   $roll_cd == AppConstants::ROLL_CD_PR ){
						?>
							<a class="btn" href="<?php echo $this->Html->Url('/projects/add');?>">プロジェクト作成</a>&nbsp;
						<?php 
						}
						?>
						<input type="text" maxlength="100" name="search_word" value="<?php echo h($search_word); ?>" class="span3" placeholder="フリーワード検索…">
						<button type="submit" class="btn">検索</button>
					</form>
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>プロジェクト名</th>
							<th>管理</th>
							<th>サイト名</th>
							<th>更新日時</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($projects as $project) : ?>
						<tr>
							<td><?php echo h($project['Project']['id']); ?></td>
							<td><?php // パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php echo h($project['Project']['department_name']); ?></td>
							<td><?php echo h($project['Project']['site_name']); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Project']['modified'], 'Y/m/d H:i'); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php 
		if($roll_cd == AppConstants::ROLL_CD_ADMIN or
		   $roll_cd == AppConstants::ROLL_CD_PR ){
		?>
		<div class="row-fluid">
			<div class="span12">
				<div class="subtitlebar">削除プロジェクト</div>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>プロジェクト名</th>
							<th>管理</th>
							<th>サイト名</th>
							<th>登録日時</th>
							<th>削除日時</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($del_projects as $project) : ?>
						<tr>
							<td><?php echo h($project['Project']['id']); ?></td>
							<td><?php // パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php echo h($project['Project']['department_name']); ?></td>
							<td><?php echo h($project['Project']['site_name']); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Project']['created'], 'Y/m/d H:i'); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Project']['modified'], 'Y/m/d H:i'); ?></td>
							</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
		} 
		?>
