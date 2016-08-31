<?php
App::uses('AppConstants', 'Lib/Constants');
App::uses('MsgConstants', 'Lib/Constants');
//App::uses('guidChkUtil', 'Lib/Utils');
?>

<?php // 個別にscriptを入れる場合
$this->start('script');?>
<script>
$(function(){
	$('#menu_home').attr('class', '');
	$('#menu_project').attr('class', 'active');
	$('#menu_upset').attr('class', '');
	$('#menu_user').attr('class', '');
	$('#menu_password').attr('class', '');
	$('#menu_logout').attr('class', '');

    var api = $('#datetimepicker').datetimepicker({
    	language: 'ja',
        format : 'yyyy/MM/dd',
        pickTime : false
        }).data('datetimepicker');
        api.widget.on('click','td.day',function(){
        api.hide();
	});
});


// 作成ボタンチェック
function submitChk(){

	$.ajax(
		{
			url : "<?php echo $this->Html->Url("/packages/add_chk") ?>",
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
		<span class="titlebar" style=" width:97%; height:15px; display: inline-block;_display: inline;">
			<div style="z-index: 1; position: absolute; right:32px; float: right;"><?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
			<div style="z-index: 0; position: relative; text-overflow:clip; white-space: nowrap; overflow:hidden; width:90%; height:20px;">パッケージ登録 - <?php echo h($project['Project']['project_name']); ?></div>
		</span>
		<?php echo $this->Session->flash(); ?>
		<div class="block">
			<div class="text-error">
				<?php
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
			<?php echo $this->Form->create('Package', array('id' => 'package_edit_form', 'action' => 'insert/'. $project['Project']['id'] ,'class'=>'form-horizontal','type' => 'POST','enctype' => 'multipart/form-data','novalidate' => true));?>
			<?php echo $this->GuidChk->putGuid(); ?>
				<?php echo $this->Form->hidden( "project_id", array('value'=> $project['Project']['id'] ) );  ?>

				<div class="control-group">
					<label class="control-label" for="input_name">パッケージ名</label>
					<div class="controls">
						<?php echo $this->Form->text('Package.package_name', array('class' => 'span12','placeholder'=>'パッケージ名', "maxlength" => 50)); ?>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label">種別</label>
					<div class="controls">
						<?php echo $this->Form->radio('Package.operation_cd',array("1"=>"公開<br>","2"=>"削除"), array("legend"=>false,'label'=>false) );?>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label">公開予定日</label>
					<div class="controls">
						<div id="datetimepicker" class="input-append datetimepicker">
							<?php echo $this->Form->text('Package.public_due_date', array('id' => 'due_date', 'class' => 'span12','data-format'=>'yyyy/MM/dd HH:mm:ss PP')); ?>
							<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
						</div>
						<div>
							<span class="exc">※公開予定日以前には公開できません。また、公開予定日から15日間後にパッケージは無効となります。</span>
						</div>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label" for="input_comment">コメント</label>
					<div class="controls">
						<?php echo $this->Form->textarea('Package.camment', array('class' => 'span10','placeholder'=>'更新内容などを記載')); ?>
					</div>
				</div>

				<div class="control-group">
					<label class="control-label">コンテンツ</label>
					<div class="controls">
						<?php echo $this->Form->file("contents_file_name", array(  "label" => false ) ); ?>
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
