<?php
App::uses('MsgConstants', 'Lib/Constants');
App::uses('AppConstants', 'Lib/Constants');
//App::uses('guidChkUtil', 'Lib/Utils');


//編集権の判定
$accout_edit_flg = true;
while(true){
	if (isset($this->request->data['User']['id']) != true) {
		// 新規時はOK
		break;
	}
	if($auth_user_id == $this->request->data['User']['id'] ){
		// 自分自身の編集はOK
		break;
	}
	if($auth_user_roll_cd == AppConstants::ROLL_CD_ADMIN ){
		// 管理者はOK
		break;
	}
	
	if($this->request->data['User']['roll_cd'] == AppConstants::ROLL_CD_DEVELOP ){
		// 製作会社の編集はOK
		break;
	}

	if($this->request->data['User']['roll_cd'] == AppConstants::ROLL_CD_SITE ){
		if($auth_user_roll_cd == AppConstants::ROLL_CD_PR){
			// サイト担当者の編集は広報ならOK
			break;
		}
	}
	
	// サイト担当同士の広報同士の編集は不可
	$accout_edit_flg = false;
	break;
}

?>

<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {
	$(function(){
		$('#menu_home').attr('class', '');
		$('#menu_project').attr('class', '');
		$('#menu_upset').attr('class', '');
		$('#menu_user').attr('class', 'active');
		$('#menu_password').attr('class', '');
		$('#menu_logout').attr('class', '');


		$('#update').click(function(){
			<?php if (count($projects)){ ?>
			if( $("#projects_list :checkbox:checked").length + <?php echo $shadow_belong_cnt; ?> == 0 ){
				if(confirm("プロジェクトに所属していないユーザーを登録します。よろしいですか。")){
					$('#user_edit_form').attr('action', '<?php echo $this->Html->url("/users/update/");?>' );
					$('#user_edit_form').submit();
					return false;
				} else {
					return false;
				}
			}
			<?php } ?>
			if (confirm('<?php echo MsgConstants::CONFIRM_EDIT; ?>') ) {
				$('#user_edit_form').attr('action', '<?php echo $this->Html->url("/users/update/");?>' );
				$('#user_edit_form').submit();
				return false;
			}
		});

		// 削除選択時処理
		$('#delete').click(function(){
			<?php if ( isset($this->request->data['User']['id']) &&
						$this->request->data['User']['id'] == $auth_user_id){ ?>
				if (!confirm('ユーザー削除後にログアウトします。よろしいですか。') ) {
					return false;
				}
			<?php } ?>
			if (confirm('<?php echo MsgConstants::CONFIRM_DELETE; ?>') ) {
				$('#user_edit_form').attr('action', '<?php echo $this->Html->url("/users/delete/");?>');
				$('#user_edit_form').submit();
				return false;
			}
		});

		// アカウント種別選択時処理
		$('#roll_cd').change(function(){
			if( $('#roll_cd').val() == <?php echo AppConstants::ROLL_CD_ADMIN ; ?> ||
			    $('#roll_cd').val() == <?php echo AppConstants::ROLL_CD_PR;  ?>
			){
				// 管理者および広報の場合にはすべてのプロジェクトを選択
				$('input.project:checkbox').prop('checked','checked');  
				$('input.project:checkbox').prop('disabled','disabled');  
			}
			else{
				// 製作会社およびサイト担当者の場合にはプロジェクトを個別選択
				$('input.project:checkbox').prop('disabled',false);  
			}
		});

		$('.fancybox').fancybox({
			modal:true,
		     'type' : 'iframe'
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
		<div class="row-fluid">
			<div class="span12">
				<div class="titlebar">ユーザー<?php if( isset($this->request->data['User']['id']) ) echo "編集"; else echo "追加";?><?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
					<?php echo $this->Session->flash(); ?>

					<div class="block">
						<?php echo $this->Form->create('User', array('id' => 'user_edit_form','type' => 'POST')) ."\n";?>
						<?php echo $this->GuidChk->putGuid(); ?>
						<div class="text-error">
							<?php
								if(isset($this->validationErrors) && isset($this->validationErrors["User"])){
									foreach($this->validationErrors["User"] as $key => $data){
										foreach($data as $key2 => $data2){
											print($data2) . "<br>";
										}
									}
								}
								if(isset($errcnt) && $errcnt>0){
									for($i=1;$i<$errcnt+1;$i++){
										print($errmsg[$i]) . "<br>";
									}
							    }

							?>
						</div>
						<table class="table table-hover">
						<thead>
							<tr>
								<th colspan="2">ユーザー情報</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>ユーザーID</td>
								<td><?php
									if (isset($this->request->data['User']['id'])) {
										echo $this->request->data['User']['id'];
										echo $this->Form->hidden('User.id');
									}
								 ?></td>
							</tr>
							<?php
							if($accout_edit_flg == true){
							?>
								<tr>
									<td>ユーザー名</td>
									<td><?php echo $this->Form->text('User.username', array('class' => 'span10', 'error' => false, 'maxlength' => '50')); ?></td>
								</tr>
								<tr>
									<td>メールアドレス</td>
									<td><?php echo $this->Form->text('User.email', array('class' => 'span10','autocomplete'=>'off', 'error' => false, 'maxlength' => '200')); ?></td>
								</tr>
								<tr>
									<td>パスワード</td>
									<td><?php echo $this->Form->password('User.password', array('class' => 'span4','autocomplete'=>'off', 'error' => false, 'maxlength' => '20')); ?></td>
								</tr>
								<tr>
									<td>パスワード(再入力)</td>
									<td><?php echo $this->Form->password('User.password_re', array('class' => 'span4', 'error' => false, 'maxlength' => '20')); ?></td>
								</tr>
								<tr>
									<td>連絡先</td>
									<td><?php echo $this->Form->text('User.contact_address', array('class' => 'span10', 'error' => false, 'maxlength' => '50')); ?></td>
								</tr>
								<tr>
									<td>アカウント種別</td>
									<td><?php echo $this->Form->select('User.roll_cd', $rolls, array('error' => false ,'id'=>'roll_cd','style'=>'margin-top: 13px;')); ?></td>
								</tr>
							<?php 
							}
							else{
							?>
								<tr>
									<td>ユーザー名</td>
									<td><?php echo $this->request->data['User']['username']; ?></td>
								</tr>
								<tr>
									<td>メールアドレス</td>
									<td><?php echo $this->request->data['User']['email']; ?></td>
								</tr>
								<tr>
									<td>連絡先</td>
									<td><?php echo $this->request->data['User']['contact_address']; ?></td>
								</tr>
								<tr>
									<td>アカウント種別</td>
									<td><?php echo $rolls[ $this->request->data['User']['roll_cd'] ]; ?></td>
								</tr>
							<?php 
							}
							?>
						</tbody>
					</table>
				</div>

				<div class="subtitlebar">所属プロジェクト</div>
				<div class="block">
					<table class="table table-hover" id="projects_list">
					<thead>
						<tr>
							<th></th>
							<th>#</th>
							<th>プロジェクト名</th>
							<th>管理</th>
							<th>サイト名</th>
						</tr>
					</thead>
					<tbody>
					<?php $i = 0; ?>
					<?php foreach($projects as $project) : ?>
						<tr>
							<td>
							<?php 
							if( isset($this->request->data['User']) AND
								( $this->request->data['User']['roll_cd'] == AppConstants::ROLL_CD_ADMIN  OR
								  $this->request->data['User']['roll_cd'] == AppConstants::ROLL_CD_PR )
							){
								echo "<input class='project' type='hidden'  name='project_check[{$i}]' value='{$project['Project']['id']}' checked>";
								echo "<input class='project' type='checkbox' checked disabled>";
							}
							else{
								echo "<input class='project' type='checkbox'  name='project_check[{$i}]' value='{$project['Project']['id']}' ". checked($project['Project']['id'], $project_checks ) . ">";
							}
							$i++;
							?>
							</td>
							<td><?php echo h($project['Project']['id']); ?></td>
							<td>
							<?php
//							$user_ary = array();
//							if(isset($project_user)){
//								foreach($project_user as $key => $data ) {
									echo $this->Html->link(
										$project["Project"]["project_name"],
										"/projects/view_short/".$project['Project']['id'],array("class"=>"fancybox")
									);
//								}
//								print implode(",",$user_ary);
//							}
							?>
							</td>
							<td><?php echo h($project['Project']['department_name']); ?></td>
							<td><?php echo h($project['Project']['site_name']); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
				<div class="mB20">
				<?php
					if (empty($this->request->data['User']['id'])) {	// 新規の場合
						$url = '/users/';
					} else {							// 編集の場合
						$url = '/users/view/'.$this->request->data['User']['id'];
					}
					echo $this->Html->link('<i class="icon icon-chevron-left"></i>戻る', $url, array('escape' => false, 'class' => 'btn'));
				?>
					<a href="#" id="update" class="btn">登録</a>
				<?php  if (empty($this->request->data['User']['id'])) {}else { ?>
					<a href="#" id="delete" class="btn">削除</a>
				<?php } ?>
				</div>
				<?php echo $this->Form->end(); ?>
			</div>
		</div>
<?php
	function checked($project_id, $project_checks) {
		if (empty($project_checks)) return "";
		foreach ($project_checks as $id) {
			if ($id === $project_id) {
				return "checked";
			}
		}

	}
?>

