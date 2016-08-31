<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
<body>

<div class="container-fluid">
<div class="row-fluid">

	<div id="container">
		<div id="header">
    	</div>

		<div class="span12">
		<div id="content">

			<?php echo $this->Session->flash(); ?>

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
