<?php
/**
 * データが存在しなかった場合に表示されるエラーページです。
 */
?>
<div class="container-fluid">

<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">エラー <?php echo h($error['code']) ?></div>
	</div>
</div>
<div class="row-fluid">
	<div class="span12">
		<div class="alert">
			<?php echo h($error['message']) ?>
		</div>
		<?php if( $error['login_flag'] ) { ?>
			<?php echo $this->Html->link('<i class="icon icon-chevron-left"></i>'."ログイン",  array('controller' => 'users', 'action' => 'login' ), array("class" => "btn", 'escape' => false) ); ?>
		<?php }else { ?>
			<?php echo $this->Html->link('<i class="icon icon-chevron-left"></i>'."トップ",  array('controller' => 'home', 'action' => 'index' ), array("class" => "btn", 'escape' => false) ); ?>
		<?php } ?>
	</div>
</div>

</div><!-- /container-fluid -->
