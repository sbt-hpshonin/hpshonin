<?php
App::uses('AppConstants',	'Lib/Constants');
/**
 * 承認システム-デフォルトレイアウト
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
		<div class="span2">
		<div id="header">
		<ul class="nav nav-tabs nav-stacked">
			<li id="menu_home"><?php echo $this->Html->link('<i class="icon icon-home"></i>ホーム', '/home', array('escape' => false)); ?></li>
			<li id="menu_project"><?php echo $this->Html->link('<i class="icon icon-folder-open"></i>プロジェクト', '/projects', array('escape' => false)); ?></li>
			<li id="menu_upset"><?php echo $this->Html->link('<i class="icon icon-calendar"></i>更新予定', '/schedule', array('escape' => false)); ?></li>
			<?php if ($userAuth['roll_cd'] !== AppConstants::ROLL_CD_DEVELOP ) :?>
			<li id="menu_user"><?php echo $this->Html->link('<i class="icon icon-user"></i>ユーザー管理', '/users', array('escape' => false)); ?></li>
			<?php endif; ?>
			<li id="menu_password"><?php echo $this->Html->link('<i class="icon icon-lock"></i>パスワード変更', '/users/password', array('escape' => false)); ?></li>
			<li id="menu_logout"><?php echo $this->Html->link('<i class="icon icon-off"></i>ログアウト', '/users/logout', array('escape' => false)); ?></li>
		</ul>
		</div>
		<?php echo h($userAuth['username']); ?>
    	</div>

		<div class="span10">
		<div id="content">
			<?php echo $this->fetch('content'); ?>
		</div>
		</div>

		<div id="footer">
		</div>
	</div>

</div><!-- /row-fluid -->
</div><!-- /container-fluid -->

</body>
</html>
