<?php
/**
 * 承認システム-エラーページ用レイアウト
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<?php echo $this->Html->charset(); ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>首都高 承認システム</title>
<?php
echo $this->Html->meta('icon');

/**** 全ページ共通の設定 ****/
echo $this->Html->css('/lib/bootstrap/css/bootstrap');
echo $this->Html->css('/lib/bootstrap/css/bootstrap-responsive');
echo $this->Html->css('/site');

echo $this->Html->script('/lib/jquery.js');
echo $this->Html->script('/lib/bootstrap/js/bootstrap.js');
echo $this->Html->script('/site');
/**** 全ページ共通の設定 ****/

echo $this->fetch('meta');
echo $this->fetch('css');
echo $this->fetch('script');
?>
</head>
<body class="home" data-anc="#top">

	<div class="container-fluid">
		<div class="row-fluid">

			<div id="container">

				<div class="span12">
					<div id="content">
						<?php echo $this->fetch('content'); ?>
					</div>
				</div>

				<div id="footer"></div>
			</div>

		</div>
		<!-- /row-fluid -->
	</div>
	<!-- /container-fluid -->

</body>
</html>
