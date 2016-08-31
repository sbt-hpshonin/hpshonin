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
 * @package       app.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<div class="container-fluid">

<div class="row-fluid">
	<div class="span12">
		<div class="titlebar">エラー <?php echo $error->getCode() ?></div>
	</div>
</div>
<div class="row-fluid">
	<div class="span12">
		<div class="alert">
			エラーが発生しました
		</div>
		<?php echo $this->Html->link('<i class="icon icon-chevron-left"></i>'."ログイン",  array('controller' => 'users', 'action' => 'login' ), array("class" => "btn", 'escape' => false) ); ?>
	</div>
</div>

</div><!-- /container-fluid -->