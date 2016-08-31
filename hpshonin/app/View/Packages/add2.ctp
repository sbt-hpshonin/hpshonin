<?php
App::uses('MsgConstants', 'Lib/Constants');
//App::uses('guidChkUtil', 'Lib/Utils');
?>
<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
(function ($) {
	$('#menu_home').attr('class', '');
	$('#menu_project').attr('class', 'active');
	$('#menu_upset').attr('class', '');
	$('#menu_user').attr('class', '');
	$('#menu_password').attr('class', '');
	$('#menu_logout').attr('class', '');

	$(function(){
		$('#search').click(function(){
			$('#package_edit_form').attr('action', '<?php echo $this->Html->url("/packages/blog_search/"); ?>');
			$('#package_edit_form').submit();
		});

    var api_from = $('#datetimepicker_from').datetimepicker({
    	language: 'ja',
        format : 'yyyy/MM/dd',
        pickTime : false
        }).data('datetimepicker');
        api_from.widget.on('click','td.day',function(){
        api_from.hide();
        });

    var api_to = $('#datetimepicker_to').datetimepicker({
    	language: 'ja',
        format : 'yyyy/MM/dd',
        pickTime : false
        }).data('datetimepicker');
        api_to.widget.on('click','td.day',function(){
        api_to.hide();
        });

	var api = $('#datetimepicker').datetimepicker({
		language: 'ja',
	    format : 'yyyy/MM/dd',
	    pickTime : false
	    }).data('datetimepicker');
	    api.widget.on('click','td.day',function(){
	    api.hide();
	    });
	});
})(jQuery);


//作成ボタンチェック
function submitChk(){

	$.ajax(
		{
			url : "<?php echo $this->Html->Url("/packages/add2_chk") ?>",
			dataType : "text",
			type:"POST",
			async: false,
			data: {
				"project_id":<?php echo $project['Project']['id']; ?>,
				"due_date":$('#due_date').val()
			},
			success : function (message){
				if(message == ""){
					message = '<?php echo MsgConstants::CONFIRM_EDIT  ?>';
				}
				if( confirm(message) == true ){
					$("#package_edit_form").submit();
				}
			}
		}
	);
}
</script>
<?php $this->end(); ?>
<div class="row-fluid">
	<div class="span12">
		<?php echo $this->Title->makeTitleBar("パッケージ登録",h($project['Project']['project_name'])) ?>
		<?php echo $this->Session->flash(); ?>
		<div class="block">
			<div class="text-error">
				<?php
				if(isset($errmsg) && ($errmsg !="")){
					print($errmsg) . "<br>";
				}
				if(isset($this->validationErrors) && isset($this->validationErrors["Package"])){
					foreach($this->validationErrors["Package"] as $key => $data){
						foreach($data as $key2 => $data2){
							print($data2) . "<br>";
						}
					}
					echo('<BR>');
				}
				?>
			</div>

			<?php echo $this->Form->create('Package', array('id' => 'package_edit_form', "action" => "blog_insert" ,'type' => 'POST','enctype'=>'multipart/form-data', 'class'=>'form-horizontal','novalidate' => true));?>
			<?php echo $this->GuidChk->putGuid(); ?>
				<?php echo $this->Form->hidden( "project_id", array('value'=> $project['Project']['id'] ) );  ?>
				<div class="control-group">
					<label class="control-label" for="input_name">パッケージ名</label>
					<div class="controls">
						<?php echo $this->Form->text('Package.package_name', array('class' => 'span12','placeholder'=>'パッケージ名', "maxlength" => 50)); ?>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">公開予定日</label>
					<div class="controls">
						<div id="datetimepicker" class="input-append datetimepicker">
							<?php echo $this->Form->text('Package.public_due_date', array('id' => 'due_date','class' => 'span12','data-format'=>'yyyy/MM/dd HH:mm:ss PP')); ?>
							<span class="add-on"><i data-time-icon="icon-time"
								data-date-icon="icon-calendar"></i> </span>
						</div>
						<div>
							<span class="exc">※公開予定日以前には公開できません。また、公開予定日から15日間後にパッケージは無効となります。</span>
						</div>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label" for="input_comment">コメント</label>
					<div class="controls">
						<?php echo $this->Form->textarea('Package.camment', array('class' => 'span10','rows'=>10,'placeholder'=>'更新内容などを記載')); ?>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label" for="input_comment">更新記事</label>
					<div class="controls">

						<?php echo $this->Form->text('Package.blog_freeword', array('class' => 'span4','placeholder'=>'記事タイトル検索…', "maxlength" => 100)); ?>
						<div id="datetimepicker_from" class="input-append datetimepicker">
							<?php echo $this->Form->text('Package.blog_from', array('class' => 'span12', 'placeholder'=>"更新日…",'data-format'=>'yyyy/MM/dd HH:mm:ss PP')); ?>
							<span class="add-on"><i data-time-icon="icon-time"
								data-date-icon="icon-calendar"></i> </span>
						</div>
						～
						<div id="datetimepicker_to" class="input-append datetimepicker">
							<?php echo $this->Form->text('Package.blog_to', array('class' => 'span12', 'data-format'=>'yyyy/MM/dd HH:mm:ss PP')); ?>
							<span class="add-on"><i data-time-icon="icon-time"
								data-date-icon="icon-calendar"></i> </span>
						</div>
						<a href="#" id="search" class="btn">検索</a>
						<div class="maxH220" id="publish">
							<?php $DBCNT=0 ?>
							<table class="table table-hover">
								<thead>
									<tr>
										<th width="5%"></th>
										<th width="70%">記事タイトル</th>
										<th width="25%">更新日時</th>
									</tr>
								</thead>
								<?php if( count( $blogdata ) ) { ?>
								<tbody>
									<?php foreach($blogdata as $blogentry) : ?>
										<?php $DBCNT++; ?>
										<tr>
											<td><?php echo $this->Form->checkbox('Package.blogchk_'.$DBCNT); ?>
											</td>
											<td><?php echo ($blogentry['SUBJECT']) ?></td>
											<td><?php echo ($blogentry['MODIFIED']) ?></td>
											<td><input type="hidden"
												name="data[Package][blogid_<?php echo($DBCNT) ?>]"
												id="PackageBlogId_<?php echo($DBCNT) ?>"
												value="<?php  echo($blogentry['ID']); ?>" /></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<?php }else { ?>
								<tbody>
									<tr><td></td><td colspan="2" class="text-error">記事がありませんでした。</td></tr>
								</tbody>
								<?php }?>
							</table>
							<input type="hidden" name="data[Package][blogcnt]" id="PackageBlogcnt" value="<?php  echo($DBCNT); ?> " />
						</div>
						<div class="maxH220" id="delete" >
							<?php $counter = 0 ?>
							<?php if( count( $delete_blogdata ) ) { ?>
							<table class="table table-hover">
							<thead>
									<tr>
										<th width="5%"></th>
										<th width="70%">削除記事タイトル</th>
										<th width="25%">更新日時</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($delete_blogdata as $blogentry) : ?>
										<?php $counter++; ?>
										<tr>
											<td></td>
											<td><?php echo ($blogentry['MtEntry']['SUBJECT']) ?></td>
											<td><?php echo (DateUtil::dateFormat( $blogentry['MtEntry']['MODIFIED'], 'Y/m/d H:i')) ?></td>
											<td><input type="hidden"
												name="data[Package][deleteblogid_<?php echo($counter) ?>]"
												id="PackageDeleteBlogId_<?php echo($counter) ?>"
												value="<?php  echo($blogentry['MtEntry']['ID']); ?>" /></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<?php } ?>
							<input type="hidden" name="data[Package][deleteblogcnt]"  value="<?php  echo($counter); ?> " />
						</div>
					</div>
				</div>

				<div class="control-group">
					<div class="controls">
						<?php echo $this->Html->link( '<i class="icon icon-chevron-left"></i>戻る', array( 'controller' => 'projects', 'action' => 'view',$project['Project']['id'] ), array("class" => "btn" ,"escape" => false) ); ?>
						<?php echo $this->Form->button("<i class='icon icon-file'></i>作成", array( "type" => "button", "div" => false, "escape" => false, "label" => false ,"class" => "btn", "onclick" => "submitChk() ; return false ;")); ?>
					</div>
				</div>
			<?php echo $this->Form->end();?>
		</div>
	</div>
</div>