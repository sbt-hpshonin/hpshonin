<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>首都高 承認システム</title>
	<?php
		echo $this->Html->meta('icon');

		/**** 全ページ共通の設定 ****/
		echo $this->Html->css('/lib/bootstrap/css/bootstrap');
		echo $this->Html->css('/site');

		echo $this->Html->script('/lib/jquery.js');
		echo $this->Html->script('/lib/bootstrap/js/bootstrap.js');
		echo $this->Html->script('/site');
		/**** 全ページ共通の設定 ****/

		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');
	?>
<style>
body {
	background-color:#f5f5f5;
}
.form-login {
	max-width: 400px;
	padding: 19px 29px 29px;
	margin: 0 auto 20px;
	background-color: #fff;
	border: 1px solid #e5e5e5;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
	-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
	box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.form-login .form-login-heading,
.form-login .checkbox {
	margin-bottom: 10px;
}
.form-login input[type="text"],
.form-login input[type="password"] {
	font-size: 16px;
	height: auto;
	margin-bottom: 15px;
	padding: 7px 9px;
}
</style>
<script>
(function ($) {
	$(function(){

		$('#login').click(function(){
			$('#form-login').attr('action', '<?php $this->Html->url('/users/login');?>');
			$('#form-login').submit();
		});


	});
})(jQuery);
</script>
</head>
<body>

<div class="container">

<form class="form-login"  id="form-login" method="POST">
<h2 class="form-login-heading">首都高<br />ホームページ承認システム</h2>
<?php if(isset($message)) echo $message; ?>

<input type="text"     name="email"    class="input-block-level" maxlength="255" placeholder="Email address" value="<?php if(isset($this->data["email"]))echo htmlspecialchars($this->data["email"]); ?>">
<input type="password" name="password" class="input-block-level" maxlength="20"  placeholder="Password">
<button class="btn" id="login">ログイン</button><br />
</form>
</div> <!-- /container -->
</body>
</html>
