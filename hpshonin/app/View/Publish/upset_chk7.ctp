<style>
body {
margin: 0px 0px 40px 0px;
}
</style>
		<div class="head">
			<p class="title"></p>
		</div>
		<div class="body">
			<?php echo MsgConstants::CONFIRM_EDIT; ?>
			<div class="maxH160">
			</div>
		</div>
		<form name="form" method="post" action="<?php echo $this->Html->url("upset_end");?>">
		<input type="hidden" name="mgid" value="<?php echo $_SESSION['mgid'] ?>">
		<div class="foot">
			<button class="btn" >はい</button>
			<button class="btn" onClick="parent.$.fancybox.close();return false;">いいえ</button>
		</div>
		</form>
