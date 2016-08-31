(function(){
	var path;
	var scripts = document.getElementsByTagName("script");
	var i = scripts.length;
	
	while (i--) {
		var match = scripts[i].src.match(/(^|.*\/)site\.js$/);
		if (match) {
			path = match[1];
			break;
		}
	}
	
	if (path.length) {
		document.write('<script type="text/javascript" src="' + path + 'lib/jquery.cookie.js"></script>');
		
		document.write('<link href="' + path + 'lib/fancybox/jquery.fancybox.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/fancybox/jquery.fancybox.js"></script>');
		
		document.write('<link href="' + path + 'lib/minimalect/jquery.minimalect.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/minimalect/jquery.minimalect.js"></script>');
		
		document.write('<link href="' + path + 'lib/treetable/jquery.treetable.css" rel="stylesheet">');
		document.write('<link href="' + path + 'lib/treetable/jquery.treetable.theme.default.css" rel="stylesheet">');
		document.write('<link href="' + path + 'lib/treetable/jquery.treetable.theme.default.custom.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/treetable/jquery.treetable.js"></script>');
		
		document.write('<link href="' + path + 'lib/fullcalendar/fullcalendar.custom.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/jquery-ui-1.10.2.custom.min.js"></script>');
		document.write('<script type="text/javascript" src="' + path + 'lib/fullcalendar/fullcalendar.js"></script>');
		
		document.write('<link href="' + path + 'lib/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>');
		
		document.write('<link href="' + path + 'lib/mergely/codemirror.css" rel="stylesheet">');
		document.write('<link href="' + path + 'lib/mergely/mergely.css" rel="stylesheet">');
		document.write('<script type="text/javascript" src="' + path + 'lib/mergely/codemirror.min.js"></script>');
		document.write('<script type="text/javascript" src="' + path + 'lib/mergely/mergely.min.js"></script>');
	}
	
})();


(function ($) {

$(function(){
	
	// ----- デモ用 -----
	if ($.cookie('_u')==1) {
		// 管理者ログイン
		$('._debug_charge').hide();
		$('._debug_user').hide();
		$('._debug_admin').show();
//		$('.nav').after('<div style="font-size:.5em;text-align:center;">テスト：広報室</div>');
	} else if ($.cookie('_u')==2) {
		// 担当者ログイン
		$('._debug_admin').hide();
		$('._debug_user').hide();
		$('._debug_charge').show();
//		$('.nav').after('<div style="font-size:.5em;text-align:center;">テスト：担当者</div>');
	} else {
		// 通常ログイン
		$('._debug_admin').hide();
		$('._debug_charge').hide();
		$('._debug_user').show();
//		$('.nav').after('<div style="font-size:.5em;text-align:center;">テスト：制作会社</div>');
	}
	// ----- デモ用 -----
	
	
	$('select').minimalect({theme:'Bubble', placeholder:'選択してください'});
	
	$('.treetable').treetable({expandable:true}).each(function(){
		//		$(this).treetable('expandAll');
		$(this).treetable('expandNode', $('tbody tr:first', this).attr('data-tt-id'));
	});
	
	$('.anc').click(function(){
		$('html,body').animate({scrollTop:$('[data-anc='+$(this).attr('href')+']').offset().top}, 'fast');
		return false;
	});
});

})(jQuery);
