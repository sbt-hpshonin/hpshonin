<?php
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
			公開予定日は<?php echo DateUtil::dateFormat($public_due_date, 'Y/m/d');?>ですが、<?php echo DateUtil::dateFormat($publish_date, 'Y/m/d');?>に公開します。
			公開設定してよろしいですか。
		</div>
		<form name="form" method="post" id="upset" action="<?php echo $this->Html->url("upset_chk5");?>">
		<input type="hidden" name="mgid" value="<?php echo $_SESSION['mgid'] ?>">
		<div class="foot">
			<button class="btn"">はい</button>
			<button class="btn" onClick="parent.$.fancybox.close();return false;">いいえ</button>
		</div>
		</form>
		