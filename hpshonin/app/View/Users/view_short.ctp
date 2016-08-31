<?php
App::uses('DateUtil', 'Lib/Utils');
$user = $this->request->data;
?>
		<div class="row-fluid">
			<div class="span12">
				<?php echo $this->Title->makeTitleBar("ユーザー詳細") ?>
				<?php echo $this->Session->flash(); ?>

				<div class="block">
					<div class="text-error">
						<?php
							if(isset($this->validationErrors) && isset($this->validationErrors["User"])){
								foreach($this->validationErrors["User"] as $key => $data){
									foreach($data as $key2 => $data2){
										print($data2) . "<br>";
									}
								}
							}
						?>
					</div>
					<table class="table table-hover">
					<thead>
						<tr>
							<th colspan="2">ユーザー情報</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>ユーザーID</td>
							<td><?php echo h($user['User']['id']); ?></td>
						</tr>
						<tr>
							<td>ユーザー名</td>
							<td><?php echo h($user['User']['username']); ?></td>
						</tr>
						<tr>
							<td>メールアドレス</td>
							<td><?php echo h($user['User']['email']); ?></td>
						</tr>
						<tr>
							<td>連絡先</td>
							<td><?php echo h($user['User']['contact_address']); ?></td>
						</tr>
						<tr>
							<td>アカウント種別</td>
							<td><?php echo h($user['Roll']['roll_name']); ?></td>
						</tr>
					</tbody>
					</table>
				</div>
				<div class="subtitlebar">所属プロジェクト一覧</div>
				<div class="block">
					<table class="table table-hover">
					<thead>
						<tr>
							<th>#</th>
							<th>プロジェクト名</th>
							<th>管理</th>
							<th>サイト名</th>
							<!--<th>更新</th>-->
						</tr>
					</thead>
					<tbody>
						<?php foreach($project_user as $project) : ?>
							<?php if ( $project['Project']["is_del"] == AppConstants::FLAG_ON || ( $roll_cd == AppConstants::ROLL_CD_SITE && !in_array( $project['Project']['id'] ,$white_list ) ) ) { ?>
								<?php continue; ?>
							<?php } ?>
							<tr>
								<td><?php echo h($project['Project']['id'] ); ?></td>
								<td><?php echo h( $project['Project']['project_name'] ); ?></td>
								<td><?php echo h( $project['Project']['department_name'] ); ?></td>
								<td><?php echo h( $project['Project']['site_name'] ); ?></td>
								<!--<td><?php echo DateUtil::dateFormat($project['Project']['modified'], 'Y-m-d H:i'); ?></td>-->
							</tr>
						<?php endforeach; ?>
					</tbody>
					</table>
				</div>
				<div class="mB20">
					<button class="btn" onClick="parent.$.fancybox.close();">戻る</button>
				</div>
				</div>
		</div>
