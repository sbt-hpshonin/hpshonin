<style>
body {
margin: 0px 0px 40px 0px;
}
</style>
<div class="container-fluid">
<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">エラー</div>
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
				</div>
				<div>
					<a href="#" class="btn" onClick="parent.parent.window.opener.location.reload(true);parent.close()">閉じる</a>
				</div>
			</form>
		</div>
	</div><!-- /row-fluid -->
</div><!-- /container-fluid -->
