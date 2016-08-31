<style>
body {
margin: 0px 0px 40px 0px;
}
</style>
<div class="head">
			<p class="title">エラー</p>
		</div>
		<div class="body">
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
		<div class="foot">
			<button class="btn" onClick="parent.$.fancybox.close();">閉じる</button>
		</div>