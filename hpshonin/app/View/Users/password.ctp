<?php
App::uses('MsgConstants', 'Lib/Constants');
//App::uses('guidChkUtil', 'Lib/Utils');
?>

<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {

	$(function(){
		$('#menu_home').attr('class', '');
		$('#menu_project').attr('class', '');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', '');
		$('#menu_password').attr('class', 'active');
		$('#menu_logout').attr('class', '');

		$('#update').click(function(){
			if (confirm('<?php echo MsgConstants::CONFIRM_EDIT; ?>') ) {
				$('#user_edit_form').submit();
			}
		});
	});
	
	
})(jQuery);
</script>
<?php $this->end(); ?>
		<div class="row-fluid">
			<div class="span12">
				<?php echo $this->Title->makeTitleBar("パスワード変更") ?>
					<?php echo $this->Session->flash(); ?>

					<?php echo $this->Form->create('User', array(
							'id' => 'user_edit_form',
							'url' => '/users/password',
							'type' => 'POST',
							'class' => 'form form-horizontal wide'
					))?>
					<?php echo $this->GuidChk->putGuid(); ?>
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
					<div class="control-group">
					<label class="control-label" for="pw1">現在のパスワードを入力</label>
					<div class="controls">
					<?php echo $this->Form->password('password_old', array('error' => false, 'id' => 'pw1', 'class' => 'span4', 'autocomplete'=>'off', 'maxlength' => '20'))?>
					</div>
					</div>

					<div class="control-group">
					<label class="control-label" for="pw2">新しいパスワードを入力</label>
					<div class="controls">
					<?php echo $this->Form->password('password_new', array('error' => false, 'id' => 'pw2', 'class' => 'span4', 'autocomplete'=>'off', 'maxlength' => '20'))?>
					</div>
					</div>

					<div class="control-group">
					<label class="control-label" for="pw3">新しいパスワードを再入力</label>
					<div class="controls">
					<?php echo $this->Form->password('password_new_re', array('error' => false, 'id' => 'pw3', 'class' => 'span4', 'autocomplete'=>'off', 'maxlength' => '20'))?>
					</div>
					</div>

					<div class="control-group">
					<div class="controls">
					<?php echo $this->Html->link('パスワード変更','#', array('id' => 'update', 'class' => 'btn')); ?>
					</div>
					</div>
					<?php echo $this->end(); ?>
				</div>
			</div>
		</div>
