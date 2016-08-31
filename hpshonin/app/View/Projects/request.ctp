<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {
	$(function(){
		$('#request').click(function(){
			if (confirm('承認依頼します。よろしいですか。') ) {
				$('#form-request').attr('action', '<?php $this->Html->url('/projects/request');?>');
				$('#form-request').submit();
			}
			return false;
		});
	});

})(jQuery);
</script>
<?php $this->end(); ?>
<?php // 個別にstyleを入れる場合
$this->start('css');?>
<style>
</style>
<?php $this->end(); ?>

<div class="container-fluid">
<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">承認依頼</div>
			<?php echo $this->Session->flash(); ?>
			<form class="form-request"  id="form-request" method="POST">
				<div class="block">
					<div class="text-error">
						<?php
							if(is_array($err_msg)){
								foreach($err_msg as $errors){
									foreach($errors as $key => $err){
										echo ("<br>");
										echo h($err);
									}
								}
							}
						?>
					</div>
					<table class="table table-hover"  style="margin:0;padding:0;">
						<thead>
							<tr>
								<th colspan="2">パッケージ情報</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>パッケージID</td>
								<td><?php echo h($id) ?></td>
							</tr>
							<tr>
								<td>パッケージ名</td>
								<td><?php echo h($package['Package']['package_name']); ?></td>
							</tr>
							<tr>
								<td>コメント</td>
								<td><textarea style="width: 300px; height : 100px; margin:0;padding:0;" disabled><?php echo h($package['Package']['camment']); ?></textarea></td>
								</tr>
							<tr>
								<td>特記事項等</td>
								<td><textarea name="request_note" style="width: 300px; height : 100px; margin:0;padding:0;"><?php echo h($request_note); ?></textarea></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div>
					<button class="btn" id="request">依頼</button>
					<a href="#" class="btn" onClick="window.close()">キャンセル</a>
				</div>
			</form>
		</div>
	</div><!-- /row-fluid -->
</div><!-- /container-fluid -->
