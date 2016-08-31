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
		$('#menu_project').attr('class', 'active');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', '');
		$('#menu_password').attr('class', '');
		$('#menu_logout').attr('class', '');

		$('#update').click(function(){
			if(confirm('<?php echo MsgConstants::CONFIRM_EDIT; ?>')){
				$('#projects_edit_form').attr('action', '' );
				$('#projects_edit_form').submit();
				return false;
			}
		});


	<?php if( isset($project['Project']) ){ ?>
		$('#delete').click(function(){
			if(confirm('<?php echo MsgConstants::CONFIRM_DELETE; ?>')){
				$('#projects_edit_form').attr('action', '<?php echo $this->Html->url('/projects/delete/'. $project["Project"]["id"]);?>' );
				$('#projects_edit_form').submit();
				return false;
			}
		});
	<?php }?>
		
		$('.fancybox').fancybox({
			modal:true,
		     'type' : 'iframe'
		});
	});
})(jQuery);
</script>
<?php $this->end(); ?>
		<div class="row-fluid">
			<div class="span12">
				<?php 
					if($new == true ) {$caption = "プロジェクト追加"; }else {$caption = "プロジェクト編集";} 
					if(isset($project['Project']['project_name']) == true ){$memo = h($project['Project']['project_name']);} else{$memo = "";}
				?>
				<?php echo $this->Title->makeTitleBar($caption,$memo) ?>
				<?php echo $this->Session->flash(); ?>
				<form method="post" class='projects_edit_form' id='projects_edit_form'">
				<?php echo $this->GuidChk->putGuid(); ?>
				<div class="block">
					<div class="text-error">
						<?php
							if(isset($this->validationErrors) && isset($this->validationErrors["Project"])){
								foreach($this->validationErrors["Project"] as $key => $data){
									foreach($data as $key2 => $data2){
										print($data2) . "<br>";
									}
								}
							}
						?>
					</div>
					<table class="table table-hover">
						<thead>
							<tr>
								<th colspan="2">プロジェクト情報</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>プロジェクトID</td>
								<td><?php if(isset($project['Project'])){ echo h($project['Project']['id']) . "<input type='hidden' maxlength='50' name='id' value='{$project['Project']['id']}'>";} ?>
							</tr>
							<tr>
								<td>プロジェクト名</td>
								<td><input type="text" maxlength="50" name='project_name' value="<?php if(isset($project['Project'])) echo htmlspecialchars($project['Project']['project_name']); ?>" class="span4" maxlength="50"></td>
							</tr>
							<tr>
								<td>管理</td>
								<td><input type="text" maxlength="50" name='department_name'  value="<?php if(isset($project['Project'])) echo htmlspecialchars($project['Project']['department_name']); ?>" class="span4" maxlength="50"></td>
							</tr>
							<tr>
								<td>サイト名</td>
								<td><input type="text" maxlength="50" name='site_name'  value="<?php if(isset($project['Project'])) echo htmlspecialchars($project['Project']['site_name']); ?>" class="span4" maxlength="255"></td>
							</tr>
							<tr>
								<td>サイトURL</td>
								<td class="va_m">
									<?php if($new == true ){ ?>
										<?php echo AppConstants::HOME_URL ."/" ; ?><input type="text"   maxlength="50" name='site_url' value="<?php if(isset($project['Project'])) echo h($project['Project']['site_url']); ?>" class="span4"maxlength="255">
									<?php }else{?>
										<?php echo AppConstants::HOME_URL ."/"; if(isset($project['Project'])) echo h($project['Project']['site_url']); ?>
										<input type="hidden" name='site_url' value="<?php if(isset($project['Project'])) echo h($project['Project']['site_url']); ?>" class="span4" >
									<?php }?>
								</td>
							</tr>
							<tr>
								<td>ブログURL</td>
								<td><?php echo h(AppConstants::HOME_URL ."/"); if(isset($project['Project'])) echo h($project['Project']['site_url']); else echo "###"; ?>/blog/<br /><span class="exc">※ブログはプロジェクト作成時に自動で作成されます。</span></td>
							</tr>
						</tbody>
						</table>
					</div>

					<div class="subtitlebar">プロジェクトメンバー</div>
					<div class="block">
						<table class="table table-hover">
						<thead>
							<tr>
								<th>ユーザー名</th>
								<th>メールアドレス</th>
								<th>連絡先</th>
								<th>アカウント種別</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							if(isset($project_user) && count($project_user)){
								foreach($project_user as $projectUser) : 
							?>
							<tr>
								<td><?php 
									echo $this->Html->link(
										$projectUser['User']['username'],
										'/users/view_short/'.$projectUser['User']['id'],
										array("class"=>"fancybox")
									);
								?></td>
								<td><?php echo h($projectUser['User']['email']); ?></td>
								<td><?php echo h($projectUser['User']['contact_address']); ?></td>
								<td><?php echo h($projectUser["Roll"]["roll_name"]); ?></td>
								</tr>
							<?php 
								endforeach;
							} 
							?>
						</tbody>
						</table>
					</div>
					
					<div class="mB20">
						<?php
							if ($new == true ) {
								$url = '/projects/search/';
							} else {
								$url = '/projects/view/'. $project["Project"]["id"];
							}
							echo $this->Html->link('<i class="icon icon-chevron-left"></i>戻る', $url, array('escape' => false, 'class' => 'btn')) ."\n";
							echo $this->Html->link('登録', '#', array('class' => 'btn','id'=>"update" ))."\n";
							if($new != true ){
								echo $this->Html->link('削除', '#', array('class' => 'btn','id'=>"delete" ));
							}
						?>
					</div>
				</form>
			</div>
		</div>
