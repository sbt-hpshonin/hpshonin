<?php
App::uses('Status', 'Lib');
App::uses('DateUtil', 'Lib/Utils');
?>
<style>
body {
margin: 0px 0px 40px 0px;
}
</style>
<div class="head">
	<p class="title">注意</p>
		</div>
		<div class="body">
			同一日(<?php echo DateUtil::dateFormat($target_date, 'Y/m/d'); ?>)に以下のパッケージがすでに公開済み、<br />または、公開予約されています。
			<div class="maxH160">
				<table class="table table-hover">
				<thead>
					<tr>
						<th>#</th>
						<th>パッケージ名</th>
						<th>公開状態</th>
					</tr>
				</thead>
				<tbody>
				
					<?php foreach($packages as $package){ ?>
						<?php //print_r($package['Package']); ?>
					<tr>
						<td><?php echo $package['Package']['id']; ?></td>
						<td><?php echo $package['Package']['package_name']; ?></td>
						<td><?php echo Status::getName($package['Package']['status_cd']); ?></td>
					</tr>
					<?php } ?>
				</tbody>
				</table>
			</div>
			公開設定してよろしいですか。
		</div>
		<form name="form" method="post" id="upset" action="<?php echo $this->Html->url("upset_chk7");?>">
		<input type="hidden" name="mgid" value="<?php echo $_SESSION['mgid'] ?>">
		<div class="foot">
			<button class="btn"">はい</button>
			<button class="btn" onClick="parent.$.fancybox.close();return false;">いいえ</button>
		</div>
		</form>
