<?php
App::uses('DateUtil', 'Lib/Utils');
?>
<?php
$this->start('script');?>
<script>
(function ($) {

	$(function(){
		$('#menu_home').attr('class',  'active');
		$('#menu_project').attr('class','');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', '');
		$('#menu_password').attr('class', '');
		$('#menu_logout').attr('class', '');
	});

})(jQuery);
</script>
<?php $this->end(); ?>
<div class="row-fluid">
			<div class="span12">
				<?php echo $this->Title->makeTitleBar("ホーム"); ?>
			</div>
			<?php echo $this->Session->flash(); ?>
		</div>
		<div class="row-fluid">
			<div class="span12">
				<div class="alert">
					<a href="#block1" class="anc">パッケージ登録：<?php echo count($projects00)?>件</a>&emsp;&emsp;
					<a href="#block2" class="anc">承認依頼：<?php echo count($projects01)?>件</a>&emsp;&emsp;
					<a href="#block3" class="anc">承認済み：<?php echo count($projects02)?>件</a>&emsp;&emsp;
					<?php if(count($projects03)){ ?>
						<a href="#block4" class="anc">公開エラー：<?php echo count($projects03)?>件</a>
					<?php } ?>
					</div>

				<div class="block" data-anc="#block1">
					<div class="head">パッケージ登録：<?php echo count($projects00)?>件</div>
					<div class="body">
						<table class="table table-hover">
						<thead>
							<tr>
								<th width="40%">プロジェクト名</th>
								<th width="40%">パッケージ名</th>
								<th width="20%">実施日時</th>
							</tr>
						</thead>
						<tbody>
					<?php foreach($projects00 as $project) : ?>
						<tr>
							<td><?php // 	パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php
									echo $this->Html->link( $project['Package']['package_name'],
										'/packages/view/'.$project['Package']['id']
									); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Package']['modified'], 'Y/m/d H:i'); ?></td>
						</tr>
					<?php endforeach; ?>
						</tbody>
						</table>
						<div>&nbsp;<small><a href="#top" class="anc pull-right"><i class="icon icon-circle-arrow-up"></i>ページトップへ</a></small></div>
					</div>
				</div>

				<div class="block" data-anc="#block2">
					<div class="head">承認依頼：<?php echo count($projects01)?>件</div>
					<div class="body">
						<table class="table table-hover">
						<thead>
							<tr>
								<th width="40%">プロジェクト名</th>
								<th width="40%">パッケージ名</th>
								<th width="20%">実施日時</th>
							</tr>
						</thead>
						<tbody>
					<?php foreach($projects01 as $project) : ?>
						<tr>
							<td><?php // 	パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php
									echo $this->Html->link( $project['Package']['package_name'],
										'/packages/view/'.$project['Package']['id']
									); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Package']['modified'], 'Y/m/d H:i'); ?></td>
						</tr>
					<?php endforeach; ?>
						</tbody>
						</table>
						<div>&nbsp;<small><a href="#top" class="anc pull-right"><i class="icon icon-circle-arrow-up"></i>ページトップへ</a></small></div>
					</div>
				</div>

				<div class="block" data-anc="#block3">
					<div class="head">承認済み：<?php echo count($projects02)?>件</div>
					<div class="body">
						<table class="table table-hover">
						<thead>
							<tr>
								<th width="40%">プロジェクト名</th>
								<th width="40%">パッケージ名</th>
								<th width="20%">実施日時</th>
							</tr>
						</thead>
						<tbody>
					<?php foreach($projects02 as $project) : ?>
						<tr>
							<td><?php // 	パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php
									echo $this->Html->link( $project['Package']['package_name'],
										'/packages/view/'.$project['Package']['id']
									); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Package']['modified'], 'Y/m/d H:i'); ?></td>
						</tr>
					<?php endforeach; ?>
						</tbody>
						</table>
						<div>&nbsp;<small><a href="#top" class="anc pull-right"><i class="icon icon-circle-arrow-up"></i>ページトップへ</a></small></div>
					</div>
				</div>
<?php if (count($projects03)){ ?>
				<div class="block" data-anc="#block4">
					<div class="head">公開エラー：<?php echo count($projects03)?>件</div>
					<div class="body">
						<table class="table table-hover">
						<thead>
							<tr>
								<th width="40%">プロジェクト名</th>
								<th width="40%">パッケージ名</th>
								<th width="20%">実施日時</th>
							</tr>
						</thead>
						<tbody>
					<?php foreach($projects03 as $project) : ?>
						<tr>
							<td><?php // 	パッケージ名
									echo $this->Html->link(
										$project['Project']['project_name'],
										'/projects/view/'.$project['Project']['id']
									);
							?></td>
							<td><?php
									echo $this->Html->link( $project['Package']['package_name'],
										'/packages/view/'.$project['Package']['id']
									); ?></td>
							<td><?php echo DateUtil::dateFormat($project['Package']['modified'], 'Y/m/d H:i'); ?></td>
						</tr>
					<?php endforeach; ?>
						</tbody>
						</table>
						<div>&nbsp;<small><a href="#top" class="anc pull-right"><i class="icon icon-circle-arrow-up"></i>ページトップへ</a></small></div>
					</div>
				</div>
<?php } ?>
		</div>
