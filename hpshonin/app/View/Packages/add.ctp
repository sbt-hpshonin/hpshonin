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


var msg1 = '';
var msg2 = '';
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
				if(message != ""){
					msg1 = message;
				}
			}
		}
	);
	
//	$.ajax(
//			{
//				url : "<?php echo $this->Html->Url("/packages/add_chk2") ?>",
//				dataType : "text",
//				type:"POST",
//				async: false,
//				data: { 
//					"project_id":<?php echo $project['Project']['id']; ?>
//				},
//				success : function (package_table){
//				if(package_table != ""){
//					msg2 = package_table;
//				}
//			}
//		}
//	);

	if (msg2 != "") {
		$('#package_list').html(msg2);
		$.fancybox.open('#alert',{modal:true,closeBtn:false});
	} else if (msg1 != "") {
		$('#alert_messsage').text(msg1);
		$.fancybox.open('#alert2',{modal:true,closeBtn:false});
	} else {
		if(confirm('<?php echo MsgConstants::CONFIRM_EDIT ; ?>')) {
			$('#package_edit_form').submit();
			return false;
		}
	}
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
							<?php echo $this->Form->text('Package.public_due_date', array('id' => 'due_date', 'class' => 'span12','data-format'=>'yyyy/MM/dd')); ?>
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
						<?php echo $this->Form->textarea('Package.camment', array('class' => 'span10','rows'=>10,'placeholder'=>'更新内容などを記載')); ?>
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
<div class="hide">
	<div id="alert" class="obox" style="width:500px;">
		<div class="head">
			<p class="title">公開が完了していないパッケージが存在します</p>
		</div>
		<div class="body" id="err_mes">
			今回登録するパッケージが公開されると、以下のパッケージの内容が反映されていない状態になります。<br />
			今回登録するパッケージが、複数案の中の1案で、以下のパッケージがその他の案の場合は問題ありませんが、そうでない場合は、以下のパッケージの公開が完了してからパッケージを登録してください。
			<div id='package_list'></div>
			パッケージ登録してよろしいですか？
		</div>
		<div class="foot">
			<a href="javascript:void(0)" class="btn" onClick="if(msg1 == ''){ $('#package_edit_form').submit(); } else { $.fancybox.close(); $('#alert_messsage').text(msg1); $.fancybox.open('#alert2',{modal:true,closeBtn:false});} ; return false;">はい</a>
			<a href="javascript:void(0)" class="btn" onClick="$.fancybox.close();">いいえ</a>
		</div>
	</div>
</div>
<div class="hide">
	<div id="alert2" class="obox" style="width:500px;">
		<div class="head">
			<p class="title">注意</p>
		</div>
		<div class="body" id="err_mes">
			<div id='alert_messsage'></div>
		</div>
		<div class="foot">
			<a href="javascript:void(0)" class="btn" onClick="$('#package_edit_form').submit(); $.fancybox.close(); return false;">はい</a>
			<a href="javascript:void(0)" class="btn" onClick="$.fancybox.close();">いいえ</a>
		</div>
	</div>
</div>
