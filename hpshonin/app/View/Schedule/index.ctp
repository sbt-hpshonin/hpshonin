<?php
App::uses('DateUtil', 'Lib/Utils');
?>
<script>
(function ($) {

$(function(){
	$('#menu_home').attr('class', '');
	$('#menu_project').attr('class', '');
	$('#menu_upset').attr('class', 'active');
	$('#menu_user').attr('class', '');
	$('#menu_password').attr('class', '');
	$('#menu_logout').attr('class', '');

	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();

	$('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,basicWeek'
		},
		editable: true,
		dayNamesShort: ['日','月','火','水','木','金','土'],
		titleFormat: {
			month: 'yyyy年 M月',
			week: "yyyy年 M月 d日{ '&#8212;'[yyyy年 ][M月] d日}"
		},
		columnFormat: {
			month: 'ddd',
			week: 'M/d（ddd）',
			day: 'M/d（ddd）'
		},
		buttonText: {
			prev: "<span class='fc-text-arrow'><i class=\"icon icon-chevron-left\"></i></span>",
			next: "<span class='fc-text-arrow'><i class=\"icon icon-chevron-right\"></i></span>",
			today: '今日',
			month: '月',
			week: '週',
			day: '日'
		},
		timeFormat: {
			'': 'HH:mm'
		},
		disableDragging:true,
		disableResizing:true,
		eventDbClick: function() {
			window.location.href='package_detail.html';
		},
	    eventClick: function(calEvent, jsEvent, view) {
			window.location.href='packages/view/'+ calEvent.package_id;
	    },

		eventColor: '#0088cc',
		events: [
			<?php foreach($projects as $project) : ?>
			{
				package_id: '<?php echo $project['Package']['id']; ?>',
				title: '<?php echo addslashes( $project['Package']['package_name']) . " - " . $project['Project']['project_name'] ;?>',
				start: '<?php echo DateUtil::dateFormat($project['Package']['public_reservation_datetime'], 'Y-m-d H:i'); ?>',
				allDay: false
			},
			<?php endforeach; ?>
		]
	});

	// 日付・記憶機能
    var year = $.cookie('fullCalendar_y');
    var month = $.cookie('fullCalendar_m');
    var day = $.cookie('fullCalendar_d');
    var view_name = $.cookie('fullCalendar_v');
	if(year){
		$('#calendar').fullCalendar('changeView',view_name);
		$('#calendar').fullCalendar('gotoDate', year,month - 1,day);
	}
	$(".fc-button").click(function(){
	    var d = $('#calendar').fullCalendar('getDate');
	    var year = $.fullCalendar.formatDate(d,'yyyy');
	    var month = $.fullCalendar.formatDate(d,'MM');
	    var day = $.fullCalendar.formatDate(d,'dd');
	    var view_name = $('#calendar').fullCalendar('getView').name;
	    $.cookie('fullCalendar_y',year);
	    $.cookie('fullCalendar_m',month);
	    $.cookie('fullCalendar_d',day);
	    $.cookie('fullCalendar_v',view_name);
	});
});

})(jQuery);
</script>
<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">更新予定<?php echo $this->Html->link('<i class="icon icon-question-sign icon-white"></i>ヘルプ', '/manual.pdf', array('class'=> 'pull-right' ,'target' => '_blank', "escape" => false)); ?></div>
		<?php echo $this->Session->flash(); ?>
		<div class="block">
			<div id="calendar"></div>
		</div>
	</div>
</div>

