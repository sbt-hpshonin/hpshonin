<!DOCTYPE html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>首都高 承認システム</title>

<?php
		echo $this->Html->meta('icon');

		/**** 全ページ共通の設定 ****/
		echo $this->Html->css('/lib/bootstrap/css/bootstrap.css');
		echo $this->Html->css('/lib/bootstrap/css/bootstrap-responsive.css');
		echo $this->Html->css('/site.css');

		echo $this->Html->script('/lib/jquery.js');
		echo $this->Html->script('/lib/bootstrap/js/bootstrap.js');
		echo $this->Html->script('/site.js');


		echo $this->Html->css('/debug_kit/css/debug_toolbar.css');
		echo $this->Html->script('/debug_kit/js/jquery.js');
		echo $this->Html->script('/debug_kit/js/js_debug_toolbar.js');
		echo $this->Html->script('/lib/highlight/jquery.highlight-4.js');
		echo $this->Html->script('/lib/contextmenu/jquery.contextmenu.r2.js');
		echo $this->Html->script('/lib/selection/jquery.selection-min.js');


		/**** 全ページ共通の設定 ****/

		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
?>
<script>
(function ($) {
var isHighlight = false;
var strHighlight = "";

$(function(){

	mergely_init('#diff_src');
	mergely_init('#diff_text');
	mergely_contents('#diff_text', 'lhs', '<?php echo $this->Html->url("/packages/strip/old");?>');
	mergely_contents('#diff_text', 'rhs', '<?php echo $this->Html->url("/packages/strip/new");?>');
	mergely_contents('#diff_src', 'lhs', '<?php echo $this->Html->url("/packages/getdata/old");?>');
	mergely_contents('#diff_src', 'rhs', '<?php echo $this->Html->url("/packages/getdata/new");?>');

	var items = [""];
	var item_cnt = 0;

	$('#tabnav .taba').click(function(e){

		var id = $(this).attr('href');

		$('#tabnav .taba').closest('li').removeClass('active');
		$(this).closest('li').addClass('active');

		$('.tabc').hide();
		$(id).show();

		switch (id) {
		case '#pane_preview':
			break;
		case '#pane_text':
			$(window).trigger('resize');
			break;
		case '#pane_src':
			$(window).trigger('resize');
			break;
		}

		return false;
	});
	$('.tabc').hide();
	$('#tabnav .taba:first').trigger('click');

	$('.search_on').hide();

	// テキスト検索
	var max_cnt = 0;
	$('.search_do').click(function(){
		var search_txt = $('.search_txt').val();
 		if (search_txt !=""){
			changeTab("#tabind1");

			setHighlightString(search_txt);

			items = $($($('#iframeR').contents()).find(".highlight:visible"));
			max_cnt = items.length;
			item_cnt = 0;
			tops = $($(items)[item_cnt]).offset().top;
			left = $($(items)[item_cnt]).offset().left;
			$(window.iframeR).scrollTop( tops );

			$('.search_on').show();
			$('.search_off').hide();

			document.getElementById("search_txt").disabled = true;

 		}
		return false;
	});
	$('.search_cr').click(function(){
		removeHighlightString();

		$('.search_off').show();
		$('.search_on').hide();
		$('.search_txt').val('');
		document.getElementById("search_txt").disabled = false;
		changeTab("#tabind1");

		return false;
	});
	$('.search_up').click(function(){

		changeTab("#tabind1");
		if(item_cnt == 0){
			return false;
		}
		item_cnt --;
		tops = $($(items)[item_cnt]).offset().top;
		left = $($(items)[item_cnt]).offset().left;

		$(window.iframeR).scrollTop( tops );

	});
	$('.search_down').click(function(){
		changeTab("#tabind1");
		if(item_cnt >= max_cnt){
			return false;
		}
		item_cnt ++;
		tops = $($(items)[item_cnt]).offset().top;
		left = $($(items)[item_cnt]).offset().left;
		$(window.iframeR).scrollTop( tops );

	});
	$('.search_txt').keypress(function(e){
		if ($(this).val()!='' && e.which===13) $('.search_do').trigger('click');
	});

	window.onload = function() {
		// 左右フレーム連動スクロール
		$(window.iframeL).scroll(function() {
			$(window.iframeR).scrollTop( $(window.iframeL).scrollTop() );
			$(window.iframeR).scrollLeft( $(window.iframeL).scrollLeft() );
		});
		$(window.iframeR).scroll(function() {
			$(window.iframeL).scrollTop( $(window.iframeR).scrollTop() );
			$(window.iframeL).scrollLeft( $(window.iframeR).scrollLeft() );
		});

		// 左右フレーム検索ハイライト用スタイルシート挿入
		$('#iframeL').contents().find('body').append("<style>.highlight{background-color:yellow}</style>")
		$('#iframeR').contents().find('body').append("<style>.highlight{background-color:yellow}</style>")
	}

	// ContextMenu
	menu_init('#diff_text', 'lhs');
	menu_init('#diff_text', 'rhs');
	menu_init('#diff_src', 'lhs');
	menu_init('#diff_src', 'rhs');


	// iframeテスト
    $('#setHighLight').click(function(){
		try {
			var str = $('#resultTextArea').text();
			var str = '首都高';
	    	var obj = $('#iframeL').contents();
	    	obj.find('body').removeHighlight().highlight(str);
	    } catch(e) {
	    	alert(e.message);
	    }
    });

    // ハイライトチェック
    $('#checkHighlight').click(function() {
    	strHighlight = $('#txtHighlight').val();
    	isHighlight = $('#checkHighlight').is(':checked');
    	isHighlight ?  setHighlightString(strHighlight) : removeHighlightString() ;
    });

	// iframeのHPにスタイルを追加
//	try {
//		var ifmL = $('#iframeL').contents();
//		var stl = ifmL.createElement('style');
//		stl.text = '.highlight{background-color:yellow}';
//		ifmL.find('head').append(stl);
//	} catch(e) {
//		alert(e.message);
//	}
});

function mergely_init (id) {
	$(id).mergely({
		width: 'auto',
		height: '500',
		cmsettings:{
			readOnly:true,
			lineWrapping:true
		},
// 現状はプレビューのみ
//		resized: function() {
//			if (isHighlight) {
//				resizeHighlight(id, strHighlight);
//			} else {
//				resizeHighlight(id, '');
//			}
//		},
//		loaded: function() {
//			if (isHighlight) {
//				resizeHighlight(id, strHighlight);
//			} else {
//				resizeHighlight(id, '');
//			}
//		},
	});
}

function mergely_contents (id, hs, url) {
	$.ajax({
		type: 'GET', dataType: 'text',
		url: url,
		contentType: 'text/plain',
		success: function (response) {
			$(id).mergely(hs, response);
		}
	});
}

function menu_init(id, hs) {
	var mergely_id = id + "-editor-" + hs;
	$(mergely_id).contextMenu('myMenu1',
	{
		bindings: {
			'highlight': function(t) {
				setHighlight(id, hs);
			},
			'remove': function(t) {
				removeHighlight();
			},
		}
	});
}

/*
 * タブ自動切り替え
 */
function changeTab(tabname){

	$('#tabnav .taba').closest('li').removeClass('active');
	$(this).closest('li').addClass('active');
	$('.tabc').hide();
	$('#pane_preview').show();
	$(tabname).closest('li').addClass('active');
}

// 右クリックハイライトの処理
function setHighlight(id, hs) {
	var cm = $(id).mergely('cm', hs);
	var str = cm.getDoc().getSelection();


	$('.search_on').show();
	$('.search_off').hide();

	changeTab("#tabind1");

	setHighlightString(str);

	items = $($($('#iframeR').contents()).find(".highlight:visible"));
	max_cnt = items.length;
	item_cnt = 0;
	tops = $($(items)[item_cnt]).offset().top;
	left = $($(items)[item_cnt]).offset().left;
	$(window.iframeR).scrollTop( tops );

	if (str.length > 0) {
		isHighlight = true;
		strHighlight = str;
	} else {
		isHighlight = false;
		strHighlight = "";
	}
	$('#search_txt').val(strHighlight);
	document.getElementById("search_txt").disabled = true;


	$('#txtHighlight').val(strHighlight);
	$('#checkHighlight').attr('checked', isHighlight);
}

function setHighlightString(str) {
	// すべてのパネルが対象
// 現状はプレビューのみ
//	$('#all_pane').removeHighlight().highlight(str);
	// iframeのハイライト
	$('#iframeL').contents().find('body').removeHighlight().highlight(str);
	$('#iframeR').contents().find('body').removeHighlight().highlight(str);
}

function removeHighlightString() {
	$('#iframeL').contents().find('body').removeHighlight();
	$('#iframeR').contents().find('body').removeHighlight();
}

function resizeHighlight(id, str) {
	$(id).removeHighlight().highlight(str);
}

function removeHighlight() {
	// すべてのパネルが対象
// 現状はプレビューのみ
//	$('#all_pane').removeHighlight();
	// iframe
	$('#iframeL').contents().find('body').removeHighlight();
	$('#iframeR').contents().find('body').removeHighlight();
	isHighlight = false;
	strHighlight = "";

	$('#txtHighlight').val(strHighlight);
	$('#checkHighlight').attr('checked', isHighlight);

	$('.search_cr').trigger('click')

	changeTab("#tabind1");
}




})(jQuery);
</script>
</head>
<body class="diff">
<div class="container-fluid">
<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">差分比較</div>
		<ul class="nav nav-tabs" id="tabnav">
			<?php if($type == "htm" || $type == "img"){ ?>
			<li id="tabind1"><a href="#pane_preview" class="taba">プレビュー</a></li>
			<?php }?>
			<?php if($type == "htm"){ ?>
			<li id="tabind2"><a href="#pane_text" class="taba">テキスト比較</a></li>
			<?php }?>
			<?php if($type == "htm" || $type == "txt"){ ?>
			<li id="tabind3"><a href="#pane_src" class="taba">HTML比較</a></li>
			<?php }?>
			
			<?php if($type == "htm"){ ?>
			<li class="pull-right">
			<div class="input-append searchbox">
					<input type="text" id="search_txt" class=" search_txt"    placeholder="テキスト検索…">
					<span class="search_off">
					<a href="#" class="btn search_do"><i class="icon icon-search"></i></a>
					</span>
					<span class="search_on">
					<a href="#" class="btn search_up"><i class="icon icon-chevron-up"></i></a>
					<a href="#" class="btn search_down"><i class="icon icon-chevron-down"></i></a>
					<a href="#" class="btn search_cr"><i class="icon icon-remove"></i></a>
					</span>
					<a style="margin-left: 10px"  href="#" class="btn"  onClick="location.reload(true);">再読込</a>
				</div>
			</li>
			<?php }?>
		</ul>

		<div id="all_pane">
			<?php if($type == "htm" || $type == "img"){ ?>
			<div id="pane_preview" class="tabc clearfix">
			<?php if ($old_url  != ""){?>
				<div class="floatL"><iframe name="iframeL" class="iframeL" id="iframeL" src="<?php print $old_url; ?>" height="100%" width="100%" border="0" frameborder="0"></iframe></div>
			<?php }
				if ($new_url !==""){
			?>
				<div class="floatR"><iframe name="iframeR" id="iframeR" src="<?php print $new_url; ?>" height="100%" width="100%" border="0" frameborder="0"></iframe></div>
			<?php } ?>
			</div>
			<?php }?>
			<?php if($type == "htm"){ ?>
			<div id="pane_text" class="tabc"><div id="diff_text"></div></div>
			<?php }?>
			<?php if($type == "htm" || $type == "txt"){ ?>
			<div id="pane_src" class="tabc"><div id="diff_src"></div></div>
			<?php }?>
			</div>
	</div>
</div><!-- /row-fluid -->
</div><!-- /container-fluid -->
<?php if($type == "htm"){ ?>
<div class="contextMenu" id="myMenu1">
   <ul>
      <li id="highlight">ハイライト</li>
      <li id="remove">ハイライト解除</li>
   </ul>
</div>
<?php }?>
</body>
</html>
