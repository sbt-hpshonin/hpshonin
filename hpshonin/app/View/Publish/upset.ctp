<?php
App::uses('Status', 'Lib');
App::uses('DateUtil', 'Lib/Utils');
?>
<script>
(function ($) {

	$(function(){
		$('#datetimepicker').datetimepicker({
			language: 'ja',
			pick24HourFormat: true
		});

	    var api = $('#datetimepicker').datetimepicker({
	        format : 'hh:mm',
	        pickDate : false
	        }).data('datetimepicker');
	        api.widget.find('.timepicker-picker tr').each(function(){
	        var td = $(this).find('td');
	        td.eq(3).hide();
	        td.eq(4).hide();
	        });


		$('.fancybox').fancybox({
			'modal':true,
			'showCloseButton':false,
			'type' : 'iframe',
			'autoScale': true,
			'padding' : 2,
		});
	});
	
}
)(jQuery);
</script>

<div class="titlebar">公開設定</div>
		<form name="form" method="post" onsubmit=" return false;">
		<?php echo $this->GuidChk->putGuid(); ?>
		<div class="block">
		    <?php if($package['Package']['status_cd'] ==Status::STATUS_CD_RELEASE_RESERVE ){ ?>
		    <label><input type="radio" name="a" value="1"   checked/> 予約公開</label>
		    <div id="datetimepicker" class="input-append">
				<input name="span12" data-format="yyyy/MM/dd hh:mm" type="text" class="span12"  value="<?php echo DateUtil::dateFormat($package['Package']['public_reservation_datetime'], 'Y/m/d H:i'); ?>" />
		    <?php }else{ ?>
		    <label><input type="radio" name="a" value="1" /> 予約公開</label>
		    <div id="datetimepicker" class="input-append">
				<input name="span12" data-format="yyyy/MM/dd hh:mm" type="text" class="span12"></input>
			<?php }?>
				<span class="add-on"><i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>
			</div>
		</div>

		<div class="block">
			<label><input type="radio" name="a"  value="2"  style="margin:0" /> 即時公開</label>
		</div>
		</form>
		<div>
			<a class="btn fancybox"  href="<?php echo $this->Html->Url('upset_chk0/'. $project_id .'/' . $package_id); ?>">登録</a>
			<?php if ($package['Package']['status_cd'] == Status::STATUS_CD_RELEASE_RESERVE ||   
			          $package['Package']['status_cd'] == Status::STATUS_CD_RELEASE_NOW
			) {?>
			<a class="btn fancybox"  href="<?php echo $this->Html->Url('upset_del0/'. $project_id .'/' . $package_id); ?>">設定取消</a>
			<?php } ?>
			<a href="javascript:void(0);" class="btn" onClick="window.close();">キャンセル</a>
					</div>

<script>
	window.focus();
</script>
