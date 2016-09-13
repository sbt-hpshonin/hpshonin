CREATE TABLE `authentications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '認証ID',
  `user_id` int(10) unsigned NOT NULL COMMENT 'ユーザーID',
  `cookie_id` varchar(40) NOT NULL COMMENT 'クッキーID',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4309 DEFAULT CHARSET=utf8 COMMENT='認証';


CREATE TABLE `batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'バッチ起動ID',
  `batch_cd` varchar(2) NOT NULL COMMENT 'バッチ起動CD : ''11'':MTプロジェクト作成/''12'':MTプロジェクト更新/''13'':MTプロジェクト削除/\n''14'':MTユーザー作成/''15'':MTユーザー変更/''16'':MTユーザー削除/''17'':無所属ユーザー削除\n''21'':パッケージ作成/''22'':承認依頼/\n''31'':承認許可/''32'':承認却下/\n''41'':ステージング/''42'':公開',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT 'プロジェクトID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'ユーザーID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT 'パッケージID',
  `execute_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'バッチ起動日時 : 現在日時がこの値を超えている場合、バッチを実行する。\n予約公開の場合は、公開予約日時を登録する。',
  `result_cd` varchar(1) NOT NULL DEFAULT '0' COMMENT '結果CD : ''0'':未実施/''1'':成功/''2'':実行中/''9'':失敗',
  `start_at` datetime DEFAULT NULL COMMENT 'バッチ開始日時',
  `end_at` datetime DEFAULT NULL COMMENT 'バッチ終了日時',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3700 DEFAULT CHARSET=utf8 COMMENT='バッチキュー';


CREATE TABLE `contents_files` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'コンテンツファイルID',
  `package_id` int(10) unsigned NOT NULL COMMENT 'パッケージID',
  `file_path` varchar(255) NOT NULL COMMENT 'ファイルパス名',
  `file_size` int(10) unsigned DEFAULT NULL COMMENT 'サイズ',
  `file_modified` datetime DEFAULT NULL COMMENT 'ファイル更新日時 : 更新ファイルの更新日時',
  `modify_flg` varchar(1) NOT NULL COMMENT '更新フラグ : ''0'':追加/''1'':変更/''2'':更新なし/''9'':削除',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `contents_files_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=62676 DEFAULT CHARSET=utf8 COMMENT='コンテンツファイル';


CREATE TABLE `extension_batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'バッチキュー拡張ID',
  `batch_queue_id` int(10) unsigned DEFAULT NULL COMMENT 'バッチ起動ID',
  `is_send_mail` varchar(1) DEFAULT NULL COMMENT 'メール送信フラグ : ''1'':メール送信/''0'':それ以外',
  `is_timeout_mail` varchar(1) DEFAULT NULL COMMENT 'タイムアウト送信フラグ : ''1'':メール送信/''0'':それ以外',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COMMENT='バッチキュー拡張';


CREATE TABLE `history_batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'バッチ起動履歴ID',
  `batch_queue_id` int(10) unsigned NOT NULL COMMENT 'バッチ起動ID',
  `batch_cd` varchar(2) NOT NULL COMMENT 'バッチ起動CD : ''11'':MTプロジェクト作成/''12'':MTプロジェクト更新/''13'':MTプロジェクト削除/\n''14'':MTユーザー作成/''15'':MTユーザー変更/''16'':MTユーザー削除/''17'':無所属ユーザー削除\n''21'':パッケージ作成/''22'':承認依頼/\n''31'':承認許可/''32'':承認却下/\n''41'':ステージング/''42'':公開',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT 'プロジェクトID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'ユーザーID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT 'パッケージID',
  `execute_datetime` datetime DEFAULT NULL COMMENT 'バッチ起動日時 : 現在日時がこの値を超えている場合、バッチを実行する。\n予約公開の場合は、公開予約日時を登録する。',
  `result_cd` varchar(1) NOT NULL DEFAULT '0' COMMENT '結果CD : ''0'':未実施/''1'':成功/''2'':実行中/''9'':失敗',
  `start_at` datetime DEFAULT NULL COMMENT 'バッチ開始日時',
  `end_at` datetime DEFAULT NULL COMMENT 'バッチ終了日時',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18513 DEFAULT CHARSET=utf8 COMMENT='バッチキュー履歴 : バッチ情報の履歴\nバッチ情報テーブルへの追加/更新時のトリガーによって、登録するバッチ情報を追加';


CREATE TABLE `history_packages` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'パッケージ履歴ID',
  `package_id` int(10) unsigned NOT NULL COMMENT 'パッケージID',
  `project_id` int(10) unsigned NOT NULL COMMENT 'プロジェクトID',
  `user_id` int(10) unsigned NOT NULL COMMENT 'ユーザーID',
  `is_blog` varchar(1) NOT NULL DEFAULT '0' COMMENT 'ブログフラグ : ''1'':ブログ/''0'':それ以外',
  `status_cd` varchar(2) NOT NULL DEFAULT '00' COMMENT 'ステータスCD : ''00'':パッケージ準備/''01'':パッケージ登録/''02'':承認依頼/\n''03'':承認許可/''04'':公開予約/''05'':即時公開/''06'':公開完了/\n''90'':パッケージ準備エラー/''91'':パッケージ登録却下/\n''92'':承認依頼キャンセル/''93'':承認却下/\n''94'':公開取消/''95'':公開期限切れ/''96'':公開エラー',
  `operation_cd` varchar(1) NOT NULL DEFAULT '1' COMMENT '機能種別CD : ''1'':公開/''2'':削除',
  `package_name` varchar(50) NOT NULL COMMENT 'パッケージ名',
  `camment` text COMMENT 'コメント',
  `contents_file_name` varchar(255) DEFAULT NULL COMMENT 'コンテンツ : 画面にて設定されたときのファイル名',
  `upload_file_name` varchar(255) DEFAULT NULL COMMENT 'アップロードファイル名 : システム内で保持するユニークなファイル名',
  `public_due_date` date DEFAULT NULL COMMENT '公開予定日 : 公開時の遅れをチェックする初期入力値',
  `request_note` text COMMENT '承認依頼特記事項',
  `request_modified` datetime DEFAULT NULL COMMENT '承認依頼更新日時',
  `request_user_id` int(10) unsigned DEFAULT NULL COMMENT '承認依頼ユーザーID',
  `approval_modified` datetime DEFAULT NULL COMMENT '承認更新日時',
  `approval_note` text COMMENT '承認特記事項',
  `approval_user_id` int(10) unsigned DEFAULT NULL COMMENT '承認ユーザーID',
  `public_cd` varchar(1) DEFAULT NULL COMMENT '公開CD : ''0'':予約公開/''1'':即時公開',
  `public_reservation_datetime` datetime DEFAULT NULL COMMENT '公開予約日時',
  `public_user_id` int(10) unsigned DEFAULT NULL COMMENT '公開ユーザーID',
  `is_staging` varchar(1) DEFAULT NULL COMMENT 'ステージングフラグ : ''1'':ステージング実施/''0'':それ以外',
  `message` text COMMENT 'メッセージ',
  `is_clean_file` varchar(1) DEFAULT '0' COMMENT 'ファイル掃除フラグ : ''0'':未実施/''1'':実施',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `history_packages_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `history_packages_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `history_packages_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7748 DEFAULT CHARSET=utf8 COMMENT='パッケージ履歴 : パッケージ情報の履歴';


CREATE TABLE `mt_entry_app_bak` (
  `entry_id` int(11) NOT NULL COMMENT 'エントリーID',
  `entry_allow_comments` tinyint(4) DEFAULT NULL COMMENT 'エントリーallowコメントs',
  `entry_allow_pings` tinyint(4) DEFAULT NULL COMMENT 'エントリーallow_pings',
  `entry_atom_id` varchar(255) DEFAULT NULL COMMENT 'エントリーatom_id',
  `entry_author_id` int(11) NOT NULL COMMENT 'エントリーauthor_id',
  `entry_authored_on` datetime DEFAULT NULL COMMENT 'エントリーauthored_on',
  `entry_basename` varchar(255) DEFAULT NULL COMMENT 'エントリーbase名前',
  `entry_blog_id` int(11) DEFAULT NULL COMMENT 'エントリーbログid',
  `entry_category_id` int(11) DEFAULT NULL COMMENT 'エントリーカテゴリーid',
  `entry_class` varchar(255) DEFAULT NULL COMMENT 'エントリー列ass',
  `entry_comment_count` int(11) DEFAULT NULL COMMENT 'エントリーコメントcount',
  `entry_convert_breaks` varchar(60) DEFAULT NULL COMMENT 'エントリーconvert_breaks',
  `entry_created_by` int(11) DEFAULT NULL COMMENT 'エントリーcreated_by',
  `entry_created_on` datetime DEFAULT NULL COMMENT 'エントリーcreated_on',
  `entry_excerpt` mediumtext COMMENT 'エントリーexcerpt',
  `entry_keywords` mediumtext COMMENT 'エントリーキーwords',
  `entry_modified_by` int(11) DEFAULT NULL COMMENT 'エントリーmodified_by',
  `entry_modified_on` datetime DEFAULT NULL COMMENT 'エントリーmodified_on',
  `entry_ping_count` int(11) DEFAULT NULL COMMENT 'エントリーping_count',
  `entry_pinged_urls` mediumtext COMMENT 'エントリーpinged_uロールs',
  `entry_status` smallint(6) NOT NULL COMMENT 'エントリーstatus',
  `entry_tangent_cache` mediumtext COMMENT 'エントリーtangent_cache',
  `entry_template_id` int(11) DEFAULT NULL COMMENT 'エントリーtemplate_id',
  `entry_text` mediumtext COMMENT 'エントリーtext',
  `entry_text_more` mediumtext COMMENT 'エントリーtext_more',
  `entry_title` varchar(255) DEFAULT NULL COMMENT 'エントリータイトル',
  `entry_to_ping_urls` mediumtext COMMENT 'エントリーto_ping_uロールs',
  `entry_week_number` int(11) DEFAULT NULL COMMENT 'エントリーweek数ber',
  `entry_current_revision` int(11) NOT NULL COMMENT 'エントリーcurrent_revision',
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='承認用MTエントリー';


CREATE TABLE `mt_entry_pub_bak` (
  `entry_id` int(11) NOT NULL COMMENT 'エントリーID',
  `entry_allow_comments` tinyint(4) DEFAULT NULL COMMENT 'エントリーallowコメントs',
  `entry_allow_pings` tinyint(4) DEFAULT NULL COMMENT 'エントリーallow_pings',
  `entry_atom_id` varchar(255) DEFAULT NULL COMMENT 'エントリーatom_id',
  `entry_author_id` int(11) NOT NULL COMMENT 'エントリーauthor_id',
  `entry_authored_on` datetime DEFAULT NULL COMMENT 'エントリーauthored_on',
  `entry_basename` varchar(255) DEFAULT NULL COMMENT 'エントリーbase名前',
  `entry_blog_id` int(11) DEFAULT NULL COMMENT 'エントリーbログid',
  `entry_category_id` int(11) DEFAULT NULL COMMENT 'エントリーカテゴリーid',
  `entry_class` varchar(255) DEFAULT NULL COMMENT 'エントリー列ass',
  `entry_comment_count` int(11) DEFAULT NULL COMMENT 'エントリーコメントcount',
  `entry_convert_breaks` varchar(60) DEFAULT NULL COMMENT 'エントリーconvert_breaks',
  `entry_created_by` int(11) DEFAULT NULL COMMENT 'エントリーcreated_by',
  `entry_created_on` datetime DEFAULT NULL COMMENT 'エントリーcreated_on',
  `entry_excerpt` mediumtext COMMENT 'エントリーexcerpt',
  `entry_keywords` mediumtext COMMENT 'エントリーキーwords',
  `entry_modified_by` int(11) DEFAULT NULL COMMENT 'エントリーmodified_by',
  `entry_modified_on` datetime DEFAULT NULL COMMENT 'エントリーmodified_on',
  `entry_ping_count` int(11) DEFAULT NULL COMMENT 'エントリーping_count',
  `entry_pinged_urls` mediumtext COMMENT 'エントリーpinged_uロールs',
  `entry_status` smallint(6) NOT NULL COMMENT 'エントリーstatus',
  `entry_tangent_cache` mediumtext COMMENT 'エントリーtangent_cache',
  `entry_template_id` int(11) DEFAULT NULL COMMENT 'エントリーtemplate_id',
  `entry_text` mediumtext COMMENT 'エントリーtext',
  `entry_text_more` mediumtext COMMENT 'エントリーtext_more',
  `entry_title` varchar(255) DEFAULT NULL COMMENT 'エントリータイトル',
  `entry_to_ping_urls` mediumtext COMMENT 'エントリーto_ping_uロールs',
  `entry_week_number` int(11) DEFAULT NULL COMMENT 'エントリーweek数ber',
  `entry_current_revision` int(11) NOT NULL COMMENT 'エントリーcurrent_revision',
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公開用MTエントリー';


CREATE TABLE `mt_mappings` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MT記事マッピングID',
  `edit_mt_post_id` int(10) unsigned NOT NULL COMMENT '編集用MT記事ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='MT記事マッピング';


CREATE TABLE `mt_posts` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MT記事ID',
  `package_id` int(10) unsigned NOT NULL COMMENT 'パッケージID',
  `edit_mt_post_id` int(10) unsigned DEFAULT NULL COMMENT '編集用MT記事ID',
  `subject` text COMMENT '件名',
  `contents` text COMMENT '記事内容',
  `post_modified` datetime DEFAULT NULL COMMENT '記事更新日時 : 記事の登録日時',
  `modify_flg` varchar(1) DEFAULT NULL COMMENT '更新フラグ : ''0'':追加/''1'':変更/''2'':更新なし/''9'':削除',
  `contents_more` text COMMENT '記事内容モア',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `mt_posts_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=543 DEFAULT CHARSET=utf8 COMMENT='MT記事';


CREATE TABLE `mt_projects` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MTプロジェクトID',
  `project_id` int(10) unsigned NOT NULL COMMENT 'プロジェクトID',
  `edit_mt_project_id` int(10) unsigned NOT NULL COMMENT '編集用MTプロジェクトID',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `mt_projects_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='MTプロジェクト';


CREATE TABLE `mt_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_blog_id` int(11) NOT NULL,
  `template_build_dynamic` tinyint(4) DEFAULT NULL,
  `template_build_interval` int(11) DEFAULT NULL,
  `template_build_type` smallint(6) DEFAULT NULL,
  `template_created_by` int(11) DEFAULT NULL,
  `template_created_on` datetime DEFAULT NULL,
  `template_identifier` varchar(50) DEFAULT NULL,
  `template_linked_file` varchar(255) DEFAULT NULL,
  `template_linked_file_mtime` varchar(10) DEFAULT NULL,
  `template_linked_file_size` int(11) DEFAULT NULL,
  `template_modified_by` int(11) DEFAULT NULL,
  `template_modified_on` datetime DEFAULT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_outfile` varchar(255) DEFAULT NULL,
  `template_rebuild_me` tinyint(4) DEFAULT NULL,
  `template_text` mediumtext,
  `template_type` varchar(25) NOT NULL,
  `template_current_revision` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`template_id`),
  KEY `mt_template_identifier` (`template_identifier`),
  KEY `mt_template_outfile` (`template_outfile`),
  KEY `mt_template_name` (`template_name`),
  KEY `mt_template_type` (`template_type`),
  KEY `mt_template_blog_id` (`template_blog_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;


CREATE TABLE `mutexes` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '排他制御ID',
  `batch_queue_id` int(10) unsigned DEFAULT NULL COMMENT 'バッチ起動ID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT 'パッケージID',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT 'プロジェクトID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'ユーザーID',
  `pid` bigint(20) DEFAULT NULL COMMENT 'プロセスID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=374 DEFAULT CHARSET=utf8 COMMENT='排他制御';


CREATE TABLE `packages` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'パッケージID',
  `project_id` int(10) unsigned NOT NULL COMMENT 'プロジェクトID',
  `user_id` int(10) unsigned NOT NULL COMMENT 'ユーザーID',
  `is_blog` varchar(1) NOT NULL DEFAULT '0' COMMENT 'ブログフラグ : ''1'':ブログ/''0'':それ以外',
  `status_cd` varchar(2) NOT NULL DEFAULT '00' COMMENT 'ステータスCD : ''00'':パッケージ準備/''01'':パッケージ登録/''02'':承認依頼/\n''03'':承認許可/''04'':公開予約/''05'':即時公開/''06'':公開完了/\n''90'':パッケージ準備エラー/''91'':パッケージ登録却下/\n''92'':承認依頼キャンセル/''93'':承認却下/\n''94'':公開取消/''95'':公開期限切れ/''96'':公開エラー',
  `operation_cd` varchar(1) NOT NULL DEFAULT '1' COMMENT '機能種別CD : ''1'':公開/''2'':削除',
  `package_name` varchar(50) NOT NULL COMMENT 'パッケージ名',
  `camment` text COMMENT 'コメント',
  `contents_file_name` varchar(255) DEFAULT NULL COMMENT 'コンテンツ : 画面にて設定されたときのファイル名',
  `upload_file_name` varchar(255) DEFAULT NULL COMMENT 'アップロードファイル名 : システム内で保持するユニークなファイル名',
  `public_due_date` date NOT NULL COMMENT '公開予定日 : 公開時の遅れをチェックする初期入力値',
  `request_note` text COMMENT '承認依頼特記事項',
  `request_modified` datetime DEFAULT NULL COMMENT '承認依頼更新日時',
  `request_user_id` int(10) unsigned DEFAULT NULL COMMENT '承認依頼ユーザーID',
  `approval_note` text COMMENT '承認特記事項',
  `approval_modified` datetime DEFAULT NULL COMMENT '承認更新日時',
  `approval_user_id` int(10) unsigned DEFAULT NULL COMMENT '承認ユーザーID',
  `public_cd` varchar(1) DEFAULT NULL COMMENT '公開CD : ''0'':予約公開/''1'':即時公開',
  `public_reservation_datetime` datetime DEFAULT NULL COMMENT '公開予約日時',
  `public_user_id` int(10) unsigned DEFAULT NULL COMMENT '公開ユーザーID',
  `is_staging` varchar(1) NOT NULL DEFAULT '0' COMMENT 'ステージングフラグ : ''1'':ステージング実施/''0'':それ以外',
  `message` text COMMENT 'メッセージ',
  `is_clean_file` varchar(1) DEFAULT '0' COMMENT 'ファイル掃除フラグ : ''0'':未実施/''1'':実施',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `packages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1310 DEFAULT CHARSET=utf8 COMMENT='パッケージ';


CREATE TABLE `project_user` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '所属プロジェクトID',
  `user_id` int(10) unsigned NOT NULL COMMENT 'ユーザーID',
  `project_id` int(10) unsigned NOT NULL COMMENT 'プロジェクトID',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `project_user_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `project_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COMMENT='所属プロジェクトマスタ';


CREATE TABLE `projects` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'プロジェクトID',
  `project_name` varchar(50) NOT NULL COMMENT 'プロジェクト名',
  `department_name` varchar(50) NOT NULL COMMENT '管理部門名',
  `site_name` varchar(255) NOT NULL COMMENT 'サイト名',
  `site_url` varchar(255) NOT NULL COMMENT 'サイトURL',
  `project_free_word` text COMMENT 'フリーワード文字 : フリーワード検索で使用\nプロジェクト名、管理部門名、サイト名、サイトURLをデリミタで結合',
  `is_clean` varchar(1) DEFAULT '0' COMMENT '掃除フラグ : ''0'':未実施/''1'':実施',
  `public_package_id` int(10) unsigned DEFAULT NULL COMMENT '現在公開中のパッケージID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='プロジェクトマスタ';


CREATE TABLE `rolls` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `roll_cd` varchar(1) NOT NULL COMMENT 'アカウント種別CD',
  `roll_name` text NOT NULL COMMENT 'アカウント種別名',
  PRIMARY KEY (`roll_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='アカウント種別マスタ';


CREATE TABLE `users` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '削除フラグ : ''1'':削除/''0'':それ以外',
  `created` datetime DEFAULT NULL COMMENT '作成日時',
  `modified` datetime DEFAULT NULL COMMENT '更新日時',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '作成者ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '更新者ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ユーザーID',
  `username` varchar(50) NOT NULL COMMENT 'ユーザー名',
  `password` text NOT NULL COMMENT 'パスワード',
  `email` varchar(255) NOT NULL COMMENT 'メールアドレス',
  `contact_address` text NOT NULL COMMENT '連絡先',
  `roll_cd` varchar(1) NOT NULL COMMENT 'アカウント種別CD',
  `user_free_word` text COMMENT 'フリーワード文字列 : フリーワード検索用の文字列\nユーザー名、メールアドレス、連絡先をデリミタで結合',
  `mt_author_id` int(10) unsigned DEFAULT NULL COMMENT 'MTユーザーID',
  PRIMARY KEY (`id`),
  KEY `roll_cd` (`roll_cd`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`roll_cd`) REFERENCES `rolls` (`roll_cd`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COMMENT='ユーザマスタ : ユーザを管理';

