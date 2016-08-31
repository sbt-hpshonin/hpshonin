<?php
App::uses('DbComponent', 'Controller/Component');
App::uses('Sanitize', 'Utility');

/**
 * MTデータベースアクセスクラス
 * @author keiohnishi
 *
 */
class MtDbComponent extends DbComponent {

	/*
	 * mt_authorテーブル取得
	*/
	public function getAuthor($id) {
		$str_sql = sprintf('SELECT * FROM mt_author WHERE author_id=%d', $id);
		$rs = $this->query($str_sql);

		return $rs;
	}

	/*
	 * mt_authorの格納
	*/
	public function saveAuthor($author_id, $username, $password, $email) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//MT方式のパスワード生成
		$pass = shell_exec(Batch::MT_PASS_GEN." ".$password);
		if (is_null($pass)) {
			//Perl呼び出しに失敗した場合、継続するがログを出す
			CakeLog::warning('【設定】パスワード生成Perl呼び出しに失敗しました。');
		}

		if (!$author_id) {
			//mt_authorが存在しない場合INSERT
			$str_sql = sprintf('INSERT INTO mt_author
					(author_api_password
					, author_auth_type
					, author_basename
					, author_can_create_blog
					, author_can_view_log
					, author_created_by
					, author_created_on
					, author_date_format
					, author_email
					, author_entry_prefs
					, author_external_id
					, author_hint
					, author_is_superuser
					, author_locked_out_time
					, author_modified_by
					, author_modified_on
					, author_name
					, author_nickname
					, author_password
					, author_preferred_language
					, author_public_key
					, author_remote_auth_token
					, author_remote_auth_username
					, author_status
					, author_text_format
					, author_type
					, author_url
					, author_userpic_asset_id)
					 VALUES ("%s"
					, "MT"
					, "%s"
					, NULL
					, NULL
					, 1
					, "%s"
					, "relative"
					, "%s"
					, "tag_delim=44"
					, NULL
					, NULL
					, NULL
					, 0
					, 1
					, "%s"
					, "%s"
					, "%s"
					, "%s"
					, "ja"
					, NULL
					, NULL
					, NULL
					, 1
					, "0"
					, 1
					, ""
					, 0)',
					$this->random(), $username, date("Y-m-d H:i:s"), $email, date("Y-m-d H:i:s"), $email, $username, $pass);
			$rs = $this->execute($str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		} else {
			//UPDATE
			$str_sql = sprintf('UPDATE mt_author SET
					 author_basename = "%s"
					, author_email = "%s"
					, author_modified_by = 1
					, author_modified_on = "%s"
					, author_name = "%s"
					, author_nickname = "%s"
					, author_password = "%s"
					, author_status = 1
					 WHERE author_id=%d',
					$username, $email, date("Y-m-d H:i:s"), $email, $username, $pass, $author_id);
			$rs = $this->execute($str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}

		return $result;
	}

	/*
	 * API用パスワード生成（MTはa～z,0～9のランダム８桁という仕様で生成している）
	*/
	function random($length = 8)
	{
		return substr(base_convert(md5(uniqid()), 16, 36), 0, $length);
	}

	/*
	 * mt_authorの削除（無効にする）
	*/
	public function deleteAuthor($author_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//UPDATE（author_status = 2が「無効」、1デフォルト「有効」）
		$str_sql = sprintf('UPDATE mt_author SET
				 author_status = 2 ,author_name = CONCAT(author_name, "_%d")
				 WHERE author_id=%d',
				$author_id, $author_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		return $result;
	}

	/*
	 * MT全体に対するパーミッションの格納
	*/
	public function savePermission($author_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//mt_permissionの存在チェック
		$str_sql = sprintf('SELECT * FROM mt_permission WHERE permission_author_id=%d AND permission_blog_id=0', $author_id);
		$rs = $this->query($str_sql);
		$rowCount = $rs->rowCount();

		if (!$rowCount) {
			//mt_permissionが存在しない場合INSERT
			$str_sql = sprintf('INSERT INTO mt_permission
					(permission_author_id
					, permission_blog_id
					, permission_blog_prefs
					, permission_created_by
					, permission_created_on
					, permission_entry_prefs
					, permission_modified_by
					, permission_modified_on
					, permission_page_prefs
					, permission_permissions
					, permission_restrictions
					, permission_role_mask
					, permission_template_prefs)
					  VALUES(%d
					, 0
					, NULL
					, 1
					, "%s"
					, NULL
					, NULL
					, "%s"
					, NULL
					, NULL
					, NULL
					, 0
					, NULL)',
					$author_id, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			$rs = $this->execute($str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}

		return $result;
	}

	/*
	 * ユーザーに対するプロジェクトのアソシエーション
	*/
	public function deleteUsersAssociation($author_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//mt_permissionの削除
		$str_sql = sprintf('DELETE FROM mt_permission WHERE permission_author_id=%d', $author_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		//mt_associationの削除
		$str_sql = sprintf('DELETE FROM mt_association WHERE association_author_id=%d', $author_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		return $result;
	}

	/*
	 * プロジェクトに対するユーザーのアソシエーション
	*/
	public function deleteProjectsAssociation($blog_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//mt_permissionの削除
		$str_sql = sprintf('DELETE FROM mt_permission WHERE permission_blog_id=%d', $blog_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		//mt_associationの削除
		$str_sql = sprintf('DELETE FROM mt_association WHERE association_blog_id=%d', $blog_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		return $result;
	}

	/*
	 * ユーザーとプロジェクトのアソシエーション
	*/
	public function makeAssociation($author_id, $blog_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//mt_permissionの存在チェック
		$str_sql = sprintf('SELECT * FROM mt_permission WHERE permission_author_id=%d AND permission_blog_id=%d', $author_id, $blog_id);
		$rs = $this->query($str_sql);
		$rowCount = $rs->rowCount();

		$str_sql_head = 'INSERT INTO mt_permission
							(permission_author_id
							, permission_blog_id
							, permission_blog_prefs
							, permission_created_by
							, permission_created_on
							, permission_entry_prefs
							, permission_modified_by
							, permission_modified_on
							, permission_page_prefs
							, permission_permissions
							, permission_restrictions
							, permission_role_mask
							, permission_template_prefs)';

		//mt_permissionが存在しない場合インサート
		if (!$rowCount) {
			//mt_permission（ブログに対するユーザーのパーミッション）をINSERT
			$str_sql = sprintf(' VALUES(
								%d
								, %d
								, NULL
								, 1
								, "%s"
								, NULL
								, NULL
								, "%s"
								, NULL
								, "\'create_post\',\'publish_post\',\'edit_all_posts\',\'manage_feedback\',\'edit_categories\',\'edit_tags\',\'manage_pages\',\'rebuild\',\'upload\',\'send_notifications\',\'edit_assets\',\'save_image_defaults\',\'manage_themes\',\'edit_templates\'"
								, NULL
								, 0
								, NULL)'
							, $author_id, $blog_id, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			$rs = $this->execute($str_sql_head.$str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}

		//mt_associationの存在チェック
		$str_sql = sprintf('SELECT * FROM mt_association WHERE association_author_id=%d AND association_blog_id=%d', $author_id, $blog_id);
		$rs = $this->query($str_sql);
		$rowCount = $rs->rowCount();

		$str_sql_head = 'INSERT INTO mt_association
							(association_author_id
							, association_blog_id
							, association_created_by
							, association_created_on
							, association_group_id
							, association_modified_by
							, association_modified_on
							, association_role_id
							, association_type)';

		//mt_associationが存在しない場合インサート
		if (!$rowCount) {
			//mt_association（ブログに対するユーザーのアソシエーション）をINSERT
			//3:デザイナ
			$str_sql = sprintf(' VALUES(
								%d
								, %d
								, 1
								, "%s"
								, 0
								, NULL
								, "%s"
								, 3
								, 1)'
					, $author_id, $blog_id, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			$rs = $this->execute($str_sql_head.$str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
			//5:編集者
			$str_sql = sprintf(' VALUES(
								%d
								, %d
								, 1
								, "%s"
								, 0
								, NULL
								, "%s"
								, 5
								, 1)'
					, $author_id, $blog_id, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			$rs = $this->execute($str_sql_head.$str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}

		return $result;
	}

	/*
	 * mt_blogテーブル取得
	*/
	public function getBlog($id) {
		$str_sql = sprintf('SELECT * FROM mt_blog WHERE blog_id=%d', $id);
		$rs = $this->query($str_sql);

		return $rs;
	}

	/*
	 * mt_blogの格納
	*/
	public function saveBlog($blog_id, $blog_name, $blog_path, $blog_url) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		if (!$blog_id) {
			//mt_blogが存在しない場合INSERT
			$str_sql = sprintf('INSERT INTO mt_blog
					(blog_allow_anon_comments
					, blog_allow_comment_html
					, blog_allow_commenter_regist
					, blog_allow_comments_default
					, blog_allow_pings
					, blog_allow_pings_default
					, blog_allow_reg_comments
					, blog_allow_unreg_comments
					, blog_archive_path
					, blog_archive_tmpl_category
					, blog_archive_tmpl_daily
					, blog_archive_tmpl_individual
					, blog_archive_tmpl_monthly
					, blog_archive_tmpl_weekly
					, blog_archive_type
					, blog_archive_type_preferred
					, blog_archive_url
					, blog_autodiscover_links
					, blog_autolink_urls
					, blog_basename_limit
					, blog_cc_license
					, blog_children_modified_on
					, blog_class
					, blog_content_css
					, blog_convert_paras
					, blog_convert_paras_comments
					, blog_created_by
					, blog_created_on
					, blog_custom_dynamic_templates
					, blog_date_language
					, blog_days_on_index
					, blog_description
					, blog_email_new_comments
					, blog_email_new_pings
					, blog_entries_on_index
					, blog_file_extension
					, blog_google_api_key
					, blog_internal_autodiscovery
					, blog_is_dynamic
					, blog_junk_folder_expiry
					, blog_junk_score_threshold
					, blog_language
					, blog_manual_approve_commenters
					, blog_moderate_pings
					, blog_moderate_unreg_comments
					, blog_modified_by
					, blog_modified_on
					, blog_mt_update_key
					, blog_name
					, blog_old_style_archive_links
					, blog_parent_id
					, blog_ping_blogs
					, blog_ping_google
					, blog_ping_others
					, blog_ping_technorati
					, blog_ping_weblogs
					, blog_remote_auth_token
					, blog_require_comment_emails
					, blog_sanitize_spec
					, blog_server_offset
					, blog_site_path
					, blog_site_url
					, blog_sort_order_comments
					, blog_sort_order_posts
					, blog_status_default
					, blog_theme_id
					, blog_use_comment_confirmation
					, blog_use_revision
					, blog_welcome_msg
					, blog_words_in_excerpt)
					 VALUES (
					NULL
					, 1
					, 1
					, 1
					, 0
					, 0
					, 1
					, 0
					, ""
					, NULL
					, NULL
					, NULL
					, NULL
					, NULL
					, "Monthly,Individual,Category,Page"
					, "Individual"
					, ""
					, NULL
					, 1
					, 100
					, ""
					, "%s"
					, "blog"
					, "{{theme_static}}css/editor.css"
					, "richtext"
					, "1"
					, 1
					, "%s"
					, "none"
					, "ja"
					, 0
					, ""
					, 1
					, 1
					, 10
					, "html"
					, NULL
					, 0
					, NULL
					, 14
					, 0
					, "ja"
					, NULL
					, 1
					, 2
					, 1
					, "%s"
					, NULL
					, "%s"
					, NULL
					, 1
					, 0
					, 0
					, NULL
					, 0
					, 0
					, NULL
					, 0
					, "0"
					, 9
					, "%s"
					, "%s"
					, "ascend"
					, "descend"
					, 2
					, "rainier"
					, 1
					, 1
					, NULL
					, 40)',
					date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $blog_name, $blog_path, $blog_url);
			$rs = $this->execute($str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		} else {
			//UPDATE
			$str_sql = sprintf('UPDATE mt_blog SET
					 blog_modified_on = "%s"
					, blog_name = "%s"
					, blog_site_path = "%s"
					, blog_site_url = "%s"
					 WHERE blog_id=%d',
					date("Y-m-d H:i:s"), $blog_name, $blog_path, $blog_url, $blog_id);
			$rs = $this->execute($str_sql);
			if(!$rs){
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}
		}

		return $result;
	}

	/*
	 * mt_blogの削除
	*/
	public function deleteBlog($blog_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		$str_sql = sprintf('DELETE FROM mt_blog WHERE blog_id=%d', $blog_id);
		$rs = $this->execute($str_sql);
		if(!$rs){
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		return $result;
	}

	/*
	 * mt_templateの格納
	*/
	public function saveTemplate($blog_id, $database) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//mt_templateの存在チェック
		$str_sql = sprintf('SELECT * FROM mt_template WHERE template_blog_id=%d', $blog_id);
		$rs = $this->query($str_sql);
		$rowCount = $rs->rowCount();

		if (!$rowCount) {
			//テンプレートをinsert
			$str_sql = 'INSERT INTO mt_template (
					template_blog_id
					, template_build_dynamic
					, template_build_interval
					, template_build_type
					, template_created_by
					, template_created_on
					, template_identifier
					, template_linked_file
					, template_linked_file_mtime
					, template_linked_file_size
					, template_modified_by
					, template_modified_on
					, template_name
					, template_outfile
					, template_rebuild_me
					, template_text
					, template_type
					, template_current_revision)
					 SELECT '
					.$blog_id.' as blog_id
					, template_build_dynamic
					, template_build_interval
					, template_build_type
					, template_created_by
					, template_created_on
					, template_identifier
					, template_linked_file
					, template_linked_file_mtime
					, template_linked_file_size
					, template_modified_by
					, template_modified_on
					, template_name
					, template_outfile
					, template_rebuild_me
					, template_text
					, template_type
					, template_current_revision
					FROM '.$database.'.mt_templates';
			$rs = $this->execute($str_sql);
			if (!$rs) {
				$result = AppConstants::RESULT_CD_FAILURE;
				return $result;
			}

			/*
			 * テンプレートマップをinsert（ここはテンプレートによって要カスタマイズ）
			 * アーカイブ名をmt_templateのtemplate_idと紐づける
			 * */
			$template_id = $this->lastInsertId();
			$this->saveTemplateMap('Monthly', $blog_id, $template_id + 4);
			$this->saveTemplateMap('Individual', $blog_id, $template_id + 10);
			$this->saveTemplateMap('Page', $blog_id, $template_id + 19);
			$this->saveTemplateMap('Category', $blog_id, $template_id + 27);

			CakeLog::debug(sprintf('ブログ（blog_id=%d）のテンプレートを登録しました。', $blog_id));
		}

		return $result;
	}

	/*
	 * mt_templatemapの格納
	*/
	private function saveTemplateMap($type, $blog_id, $template_id) {
		$result = AppConstants::RESULT_CD_SUCCESS;

		//テンプレートをinsert
		$str_sql = sprintf('INSERT INTO mt_templatemap (
				templatemap_archive_type
				, templatemap_blog_id
				, templatemap_build_interval
				, templatemap_build_type
				, templatemap_file_template
				, templatemap_is_preferred
				, templatemap_template_id)
					 VALUES (
					"%s"
					, %d
					, NULL
					, 1
					, NULL
					, 1
					, %d)', $type, $blog_id, $template_id);
		$rs = $this->execute($str_sql);
		if (!$rs) {
			$result = AppConstants::RESULT_CD_FAILURE;
			return $result;
		}

		return $result;
	}

	/*
	 * ブログルートパス取得
	*/
	public function getBlogRootPath($blog_id) {
		$result = $this->getBlogPath($blog_id);
		if ( empty( $result ) ){
			return null;
		}

		return $result['parent_path'];
	}

	/*
	 * ブログルートURL取得
	*/
	public function getBlogRootUrl($blog_id) {
		$result = $this->getBlogPath($blog_id);
		if ( empty( $result ) ){
			return null;
		}

		return $result['parent_url'];
	}

	/*
	 * ブログサイトパス取得
	*/
	public function getBlogSitePath($blog_id) {
		$result = $this->getBlogPath($blog_id);
		if ( empty( $result ) ){
			return null;
		}

		return $result['parent_path'].'\\'.$result['child_path'];
	}

	/*
	 * ブログサイトURL取得
	*/
	public function getBlogSiteUrl($blog_id) {
		$result = $this->getBlogPath($blog_id);
		if ( empty( $result ) ){
			return null;
		}

		return str_replace("/::/", $result['parent_url'], $result['child_url']);
	}

	/*
	 * ブログパス取得
	*/
	private function getBlogPath($blog_id) {
		$str_sql="SELECT ";
		$str_sql=$str_sql." bp.blog_site_url parent_url";
		$str_sql=$str_sql.", bc.blog_site_url child_url";
		$str_sql=$str_sql.", bp.blog_site_path parent_path";
		$str_sql=$str_sql.", bc.blog_site_path child_path";
		$str_sql=$str_sql." from";
		$str_sql=$str_sql." mt_blog bp";
		$str_sql=$str_sql." inner join mt_blog bc";
		$str_sql=$str_sql." on bp.blog_id = bc.blog_parent_id";
		$str_sql=$str_sql." where";
		$str_sql=$str_sql." bc.blog_id = ".$blog_id;

		$result = $this->queryFetch($str_sql);
		if ( empty( $result ) ){
			return null;
		}

		return $result;
	}

	/**
	 * 指定のプロジェクトIDで検索を行います。
	 * その他の検索条件を指定したい場合は引数に連想配列で指定してください。
	 *
	 *
	 */
	public function getEntryList( $project_id, $findconditions ){

		$str_sql="SELECT entry_id AS ID,";
		$str_sql=$str_sql."date_format(entry_modified_on, '%Y/%m/%d %k:%i') as MODIFIED,";
		$str_sql=$str_sql."entry_title  AS SUBJECT";
		$str_sql=$str_sql." FROM mt_entry ";

		$cond = "WHERE ";
		$cond = $cond."(entry_blog_id=".$project_id.")";
		$cond = $cond."AND (entry_status = 2)";
		$cond = $cond."AND (entry_class = 'entry')";
		if ( ! empty( $findconditions['entry_title'] ) ){
			$cond = $cond." AND ( entry_title LIKE '%".Sanitize::escape( $findconditions['entry_title'] )."%')";
		}

		if (  ! empty( $findconditions['entry_modified_on'] )  ) {
			if ( ! empty( $findconditions['entry_modified_on']['from'] ) ) {
				$from = $findconditions['entry_modified_on']['from']." 00:00:00";
				$cond = $cond." AND ( entry_modified_on >= '".$from."')";
			}
			if ( ! empty( $findconditions['entry_modified_on']['to'] ) ){
				$to = $findconditions['entry_modified_on']['to']." 23:59:59";
				$cond = $cond." AND ( entry_modified_on <= '".$to."')";
			}
		}

		$str_sql=$str_sql.$cond;
		$str_sql=$str_sql." order by entry_modified_on desc,entry_id desc";

		$result = $this->query($str_sql);

		return $result;

	}

	/**
	 * エントリーIDでMTEntryからデータを取得します
	 *
	 * @param unknown $entry_id
	 */
	public function getMtEntryByEntryId( $entry_id ){
		$str_sql="SELECT entry_id,";
		$str_sql=$str_sql."date_format(entry_modified_on, '%Y/%m/%d %k:%i') as entry_modified_on,";
		$str_sql=$str_sql."entry_title,";
		$str_sql=$str_sql."entry_text,";
		$str_sql=$str_sql."entry_text_more";
		$str_sql=$str_sql." FROM mt_entry ";
		$str_sql=$str_sql." WHERE entry_id =".$entry_id;
		return $this->queryFetch($str_sql);
	}

	/**
	 * ブログの関連記事を全て削除する
	 * @param unknown $project_id
	 * @return PDOStatement
	 */
	public function deleteMtEntry($project_id) {
		// リビジョン情報の削除
		$sql = "DELETE FROM  mt_entry_rev "
				."WHERE EXISTS ("
				."SELECT * FROM mt_entry e "
				."WHERE e.entry_id = entry_rev_entry_id "
				."AND e.entry_blog_id = {$project_id})";

		if (!$this->query($sql))
			return false;

		// メタ情報の削除
		$sql = "DELETE FROM  mt_entry_meta "
				."WHERE EXISTS ("
				."SELECT * FROM mt_entry e "
				."WHERE e.entry_id = entry_meta_entry_id "
				."AND e.entry_blog_id = {$project_id})";

		if (!$this->query($sql))
			return false;

		// 記事情報の削除
		$sql = "DELETE FROM  mt_entry "
				."WHERE entry_blog_id = {$project_id}";

		return $this->query($sql);
	}
}