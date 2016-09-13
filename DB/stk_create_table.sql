CREATE TABLE `authentications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '�F��ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '���[�U�[ID',
  `cookie_id` varchar(40) NOT NULL COMMENT '�N�b�L�[ID',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4309 DEFAULT CHARSET=utf8 COMMENT='�F��';


CREATE TABLE `batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�o�b�`�N��ID',
  `batch_cd` varchar(2) NOT NULL COMMENT '�o�b�`�N��CD : ''11'':MT�v���W�F�N�g�쐬/''12'':MT�v���W�F�N�g�X�V/''13'':MT�v���W�F�N�g�폜/\n''14'':MT���[�U�[�쐬/''15'':MT���[�U�[�ύX/''16'':MT���[�U�[�폜/''17'':���������[�U�[�폜\n''21'':�p�b�P�[�W�쐬/''22'':���F�˗�/\n''31'':���F����/''32'':���F�p��/\n''41'':�X�e�[�W���O/''42'':���J',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT '�v���W�F�N�gID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '���[�U�[ID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT '�p�b�P�[�WID',
  `execute_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '�o�b�`�N������ : ���ݓ��������̒l�𒴂��Ă���ꍇ�A�o�b�`�����s����B\n�\����J�̏ꍇ�́A���J�\�������o�^����B',
  `result_cd` varchar(1) NOT NULL DEFAULT '0' COMMENT '����CD : ''0'':�����{/''1'':����/''2'':���s��/''9'':���s',
  `start_at` datetime DEFAULT NULL COMMENT '�o�b�`�J�n����',
  `end_at` datetime DEFAULT NULL COMMENT '�o�b�`�I������',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3700 DEFAULT CHARSET=utf8 COMMENT='�o�b�`�L���[';


CREATE TABLE `contents_files` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�R���e���c�t�@�C��ID',
  `package_id` int(10) unsigned NOT NULL COMMENT '�p�b�P�[�WID',
  `file_path` varchar(255) NOT NULL COMMENT '�t�@�C���p�X��',
  `file_size` int(10) unsigned DEFAULT NULL COMMENT '�T�C�Y',
  `file_modified` datetime DEFAULT NULL COMMENT '�t�@�C���X�V���� : �X�V�t�@�C���̍X�V����',
  `modify_flg` varchar(1) NOT NULL COMMENT '�X�V�t���O : ''0'':�ǉ�/''1'':�ύX/''2'':�X�V�Ȃ�/''9'':�폜',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `contents_files_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=62676 DEFAULT CHARSET=utf8 COMMENT='�R���e���c�t�@�C��';


CREATE TABLE `extension_batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�o�b�`�L���[�g��ID',
  `batch_queue_id` int(10) unsigned DEFAULT NULL COMMENT '�o�b�`�N��ID',
  `is_send_mail` varchar(1) DEFAULT NULL COMMENT '���[�����M�t���O : ''1'':���[�����M/''0'':����ȊO',
  `is_timeout_mail` varchar(1) DEFAULT NULL COMMENT '�^�C���A�E�g���M�t���O : ''1'':���[�����M/''0'':����ȊO',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COMMENT='�o�b�`�L���[�g��';


CREATE TABLE `history_batch_queues` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�o�b�`�N������ID',
  `batch_queue_id` int(10) unsigned NOT NULL COMMENT '�o�b�`�N��ID',
  `batch_cd` varchar(2) NOT NULL COMMENT '�o�b�`�N��CD : ''11'':MT�v���W�F�N�g�쐬/''12'':MT�v���W�F�N�g�X�V/''13'':MT�v���W�F�N�g�폜/\n''14'':MT���[�U�[�쐬/''15'':MT���[�U�[�ύX/''16'':MT���[�U�[�폜/''17'':���������[�U�[�폜\n''21'':�p�b�P�[�W�쐬/''22'':���F�˗�/\n''31'':���F����/''32'':���F�p��/\n''41'':�X�e�[�W���O/''42'':���J',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT '�v���W�F�N�gID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '���[�U�[ID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT '�p�b�P�[�WID',
  `execute_datetime` datetime DEFAULT NULL COMMENT '�o�b�`�N������ : ���ݓ��������̒l�𒴂��Ă���ꍇ�A�o�b�`�����s����B\n�\����J�̏ꍇ�́A���J�\�������o�^����B',
  `result_cd` varchar(1) NOT NULL DEFAULT '0' COMMENT '����CD : ''0'':�����{/''1'':����/''2'':���s��/''9'':���s',
  `start_at` datetime DEFAULT NULL COMMENT '�o�b�`�J�n����',
  `end_at` datetime DEFAULT NULL COMMENT '�o�b�`�I������',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18513 DEFAULT CHARSET=utf8 COMMENT='�o�b�`�L���[���� : �o�b�`���̗���\n�o�b�`���e�[�u���ւ̒ǉ�/�X�V���̃g���K�[�ɂ���āA�o�^����o�b�`����ǉ�';


CREATE TABLE `history_packages` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�p�b�P�[�W����ID',
  `package_id` int(10) unsigned NOT NULL COMMENT '�p�b�P�[�WID',
  `project_id` int(10) unsigned NOT NULL COMMENT '�v���W�F�N�gID',
  `user_id` int(10) unsigned NOT NULL COMMENT '���[�U�[ID',
  `is_blog` varchar(1) NOT NULL DEFAULT '0' COMMENT '�u���O�t���O : ''1'':�u���O/''0'':����ȊO',
  `status_cd` varchar(2) NOT NULL DEFAULT '00' COMMENT '�X�e�[�^�XCD : ''00'':�p�b�P�[�W����/''01'':�p�b�P�[�W�o�^/''02'':���F�˗�/\n''03'':���F����/''04'':���J�\��/''05'':�������J/''06'':���J����/\n''90'':�p�b�P�[�W�����G���[/''91'':�p�b�P�[�W�o�^�p��/\n''92'':���F�˗��L�����Z��/''93'':���F�p��/\n''94'':���J���/''95'':���J�����؂�/''96'':���J�G���[',
  `operation_cd` varchar(1) NOT NULL DEFAULT '1' COMMENT '�@�\���CD : ''1'':���J/''2'':�폜',
  `package_name` varchar(50) NOT NULL COMMENT '�p�b�P�[�W��',
  `camment` text COMMENT '�R�����g',
  `contents_file_name` varchar(255) DEFAULT NULL COMMENT '�R���e���c : ��ʂɂĐݒ肳�ꂽ�Ƃ��̃t�@�C����',
  `upload_file_name` varchar(255) DEFAULT NULL COMMENT '�A�b�v���[�h�t�@�C���� : �V�X�e�����ŕێ����郆�j�[�N�ȃt�@�C����',
  `public_due_date` date DEFAULT NULL COMMENT '���J�\��� : ���J���̒x����`�F�b�N���鏉�����͒l',
  `request_note` text COMMENT '���F�˗����L����',
  `request_modified` datetime DEFAULT NULL COMMENT '���F�˗��X�V����',
  `request_user_id` int(10) unsigned DEFAULT NULL COMMENT '���F�˗����[�U�[ID',
  `approval_modified` datetime DEFAULT NULL COMMENT '���F�X�V����',
  `approval_note` text COMMENT '���F���L����',
  `approval_user_id` int(10) unsigned DEFAULT NULL COMMENT '���F���[�U�[ID',
  `public_cd` varchar(1) DEFAULT NULL COMMENT '���JCD : ''0'':�\����J/''1'':�������J',
  `public_reservation_datetime` datetime DEFAULT NULL COMMENT '���J�\�����',
  `public_user_id` int(10) unsigned DEFAULT NULL COMMENT '���J���[�U�[ID',
  `is_staging` varchar(1) DEFAULT NULL COMMENT '�X�e�[�W���O�t���O : ''1'':�X�e�[�W���O���{/''0'':����ȊO',
  `message` text COMMENT '���b�Z�[�W',
  `is_clean_file` varchar(1) DEFAULT '0' COMMENT '�t�@�C���|���t���O : ''0'':�����{/''1'':���{',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `history_packages_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `history_packages_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `history_packages_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7748 DEFAULT CHARSET=utf8 COMMENT='�p�b�P�[�W���� : �p�b�P�[�W���̗���';


CREATE TABLE `mt_entry_app_bak` (
  `entry_id` int(11) NOT NULL COMMENT '�G���g���[ID',
  `entry_allow_comments` tinyint(4) DEFAULT NULL COMMENT '�G���g���[allow�R�����gs',
  `entry_allow_pings` tinyint(4) DEFAULT NULL COMMENT '�G���g���[allow_pings',
  `entry_atom_id` varchar(255) DEFAULT NULL COMMENT '�G���g���[atom_id',
  `entry_author_id` int(11) NOT NULL COMMENT '�G���g���[author_id',
  `entry_authored_on` datetime DEFAULT NULL COMMENT '�G���g���[authored_on',
  `entry_basename` varchar(255) DEFAULT NULL COMMENT '�G���g���[base���O',
  `entry_blog_id` int(11) DEFAULT NULL COMMENT '�G���g���[b���Oid',
  `entry_category_id` int(11) DEFAULT NULL COMMENT '�G���g���[�J�e�S���[id',
  `entry_class` varchar(255) DEFAULT NULL COMMENT '�G���g���[��ass',
  `entry_comment_count` int(11) DEFAULT NULL COMMENT '�G���g���[�R�����gcount',
  `entry_convert_breaks` varchar(60) DEFAULT NULL COMMENT '�G���g���[convert_breaks',
  `entry_created_by` int(11) DEFAULT NULL COMMENT '�G���g���[created_by',
  `entry_created_on` datetime DEFAULT NULL COMMENT '�G���g���[created_on',
  `entry_excerpt` mediumtext COMMENT '�G���g���[excerpt',
  `entry_keywords` mediumtext COMMENT '�G���g���[�L�[words',
  `entry_modified_by` int(11) DEFAULT NULL COMMENT '�G���g���[modified_by',
  `entry_modified_on` datetime DEFAULT NULL COMMENT '�G���g���[modified_on',
  `entry_ping_count` int(11) DEFAULT NULL COMMENT '�G���g���[ping_count',
  `entry_pinged_urls` mediumtext COMMENT '�G���g���[pinged_u���[��s',
  `entry_status` smallint(6) NOT NULL COMMENT '�G���g���[status',
  `entry_tangent_cache` mediumtext COMMENT '�G���g���[tangent_cache',
  `entry_template_id` int(11) DEFAULT NULL COMMENT '�G���g���[template_id',
  `entry_text` mediumtext COMMENT '�G���g���[text',
  `entry_text_more` mediumtext COMMENT '�G���g���[text_more',
  `entry_title` varchar(255) DEFAULT NULL COMMENT '�G���g���[�^�C�g��',
  `entry_to_ping_urls` mediumtext COMMENT '�G���g���[to_ping_u���[��s',
  `entry_week_number` int(11) DEFAULT NULL COMMENT '�G���g���[week��ber',
  `entry_current_revision` int(11) NOT NULL COMMENT '�G���g���[current_revision',
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='���F�pMT�G���g���[';


CREATE TABLE `mt_entry_pub_bak` (
  `entry_id` int(11) NOT NULL COMMENT '�G���g���[ID',
  `entry_allow_comments` tinyint(4) DEFAULT NULL COMMENT '�G���g���[allow�R�����gs',
  `entry_allow_pings` tinyint(4) DEFAULT NULL COMMENT '�G���g���[allow_pings',
  `entry_atom_id` varchar(255) DEFAULT NULL COMMENT '�G���g���[atom_id',
  `entry_author_id` int(11) NOT NULL COMMENT '�G���g���[author_id',
  `entry_authored_on` datetime DEFAULT NULL COMMENT '�G���g���[authored_on',
  `entry_basename` varchar(255) DEFAULT NULL COMMENT '�G���g���[base���O',
  `entry_blog_id` int(11) DEFAULT NULL COMMENT '�G���g���[b���Oid',
  `entry_category_id` int(11) DEFAULT NULL COMMENT '�G���g���[�J�e�S���[id',
  `entry_class` varchar(255) DEFAULT NULL COMMENT '�G���g���[��ass',
  `entry_comment_count` int(11) DEFAULT NULL COMMENT '�G���g���[�R�����gcount',
  `entry_convert_breaks` varchar(60) DEFAULT NULL COMMENT '�G���g���[convert_breaks',
  `entry_created_by` int(11) DEFAULT NULL COMMENT '�G���g���[created_by',
  `entry_created_on` datetime DEFAULT NULL COMMENT '�G���g���[created_on',
  `entry_excerpt` mediumtext COMMENT '�G���g���[excerpt',
  `entry_keywords` mediumtext COMMENT '�G���g���[�L�[words',
  `entry_modified_by` int(11) DEFAULT NULL COMMENT '�G���g���[modified_by',
  `entry_modified_on` datetime DEFAULT NULL COMMENT '�G���g���[modified_on',
  `entry_ping_count` int(11) DEFAULT NULL COMMENT '�G���g���[ping_count',
  `entry_pinged_urls` mediumtext COMMENT '�G���g���[pinged_u���[��s',
  `entry_status` smallint(6) NOT NULL COMMENT '�G���g���[status',
  `entry_tangent_cache` mediumtext COMMENT '�G���g���[tangent_cache',
  `entry_template_id` int(11) DEFAULT NULL COMMENT '�G���g���[template_id',
  `entry_text` mediumtext COMMENT '�G���g���[text',
  `entry_text_more` mediumtext COMMENT '�G���g���[text_more',
  `entry_title` varchar(255) DEFAULT NULL COMMENT '�G���g���[�^�C�g��',
  `entry_to_ping_urls` mediumtext COMMENT '�G���g���[to_ping_u���[��s',
  `entry_week_number` int(11) DEFAULT NULL COMMENT '�G���g���[week��ber',
  `entry_current_revision` int(11) NOT NULL COMMENT '�G���g���[current_revision',
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='���J�pMT�G���g���[';


CREATE TABLE `mt_mappings` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MT�L���}�b�s���OID',
  `edit_mt_post_id` int(10) unsigned NOT NULL COMMENT '�ҏW�pMT�L��ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='MT�L���}�b�s���O';


CREATE TABLE `mt_posts` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MT�L��ID',
  `package_id` int(10) unsigned NOT NULL COMMENT '�p�b�P�[�WID',
  `edit_mt_post_id` int(10) unsigned DEFAULT NULL COMMENT '�ҏW�pMT�L��ID',
  `subject` text COMMENT '����',
  `contents` text COMMENT '�L�����e',
  `post_modified` datetime DEFAULT NULL COMMENT '�L���X�V���� : �L���̓o�^����',
  `modify_flg` varchar(1) DEFAULT NULL COMMENT '�X�V�t���O : ''0'':�ǉ�/''1'':�ύX/''2'':�X�V�Ȃ�/''9'':�폜',
  `contents_more` text COMMENT '�L�����e���A',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `mt_posts_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=543 DEFAULT CHARSET=utf8 COMMENT='MT�L��';


CREATE TABLE `mt_projects` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'MT�v���W�F�N�gID',
  `project_id` int(10) unsigned NOT NULL COMMENT '�v���W�F�N�gID',
  `edit_mt_project_id` int(10) unsigned NOT NULL COMMENT '�ҏW�pMT�v���W�F�N�gID',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `mt_projects_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='MT�v���W�F�N�g';


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
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�r������ID',
  `batch_queue_id` int(10) unsigned DEFAULT NULL COMMENT '�o�b�`�N��ID',
  `package_id` int(10) unsigned DEFAULT NULL COMMENT '�p�b�P�[�WID',
  `project_id` int(10) unsigned DEFAULT NULL COMMENT '�v���W�F�N�gID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '���[�U�[ID',
  `pid` bigint(20) DEFAULT NULL COMMENT '�v���Z�XID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=374 DEFAULT CHARSET=utf8 COMMENT='�r������';


CREATE TABLE `packages` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�p�b�P�[�WID',
  `project_id` int(10) unsigned NOT NULL COMMENT '�v���W�F�N�gID',
  `user_id` int(10) unsigned NOT NULL COMMENT '���[�U�[ID',
  `is_blog` varchar(1) NOT NULL DEFAULT '0' COMMENT '�u���O�t���O : ''1'':�u���O/''0'':����ȊO',
  `status_cd` varchar(2) NOT NULL DEFAULT '00' COMMENT '�X�e�[�^�XCD : ''00'':�p�b�P�[�W����/''01'':�p�b�P�[�W�o�^/''02'':���F�˗�/\n''03'':���F����/''04'':���J�\��/''05'':�������J/''06'':���J����/\n''90'':�p�b�P�[�W�����G���[/''91'':�p�b�P�[�W�o�^�p��/\n''92'':���F�˗��L�����Z��/''93'':���F�p��/\n''94'':���J���/''95'':���J�����؂�/''96'':���J�G���[',
  `operation_cd` varchar(1) NOT NULL DEFAULT '1' COMMENT '�@�\���CD : ''1'':���J/''2'':�폜',
  `package_name` varchar(50) NOT NULL COMMENT '�p�b�P�[�W��',
  `camment` text COMMENT '�R�����g',
  `contents_file_name` varchar(255) DEFAULT NULL COMMENT '�R���e���c : ��ʂɂĐݒ肳�ꂽ�Ƃ��̃t�@�C����',
  `upload_file_name` varchar(255) DEFAULT NULL COMMENT '�A�b�v���[�h�t�@�C���� : �V�X�e�����ŕێ����郆�j�[�N�ȃt�@�C����',
  `public_due_date` date NOT NULL COMMENT '���J�\��� : ���J���̒x����`�F�b�N���鏉�����͒l',
  `request_note` text COMMENT '���F�˗����L����',
  `request_modified` datetime DEFAULT NULL COMMENT '���F�˗��X�V����',
  `request_user_id` int(10) unsigned DEFAULT NULL COMMENT '���F�˗����[�U�[ID',
  `approval_note` text COMMENT '���F���L����',
  `approval_modified` datetime DEFAULT NULL COMMENT '���F�X�V����',
  `approval_user_id` int(10) unsigned DEFAULT NULL COMMENT '���F���[�U�[ID',
  `public_cd` varchar(1) DEFAULT NULL COMMENT '���JCD : ''0'':�\����J/''1'':�������J',
  `public_reservation_datetime` datetime DEFAULT NULL COMMENT '���J�\�����',
  `public_user_id` int(10) unsigned DEFAULT NULL COMMENT '���J���[�U�[ID',
  `is_staging` varchar(1) NOT NULL DEFAULT '0' COMMENT '�X�e�[�W���O�t���O : ''1'':�X�e�[�W���O���{/''0'':����ȊO',
  `message` text COMMENT '���b�Z�[�W',
  `is_clean_file` varchar(1) DEFAULT '0' COMMENT '�t�@�C���|���t���O : ''0'':�����{/''1'':���{',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `packages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1310 DEFAULT CHARSET=utf8 COMMENT='�p�b�P�[�W';


CREATE TABLE `project_user` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�����v���W�F�N�gID',
  `user_id` int(10) unsigned NOT NULL COMMENT '���[�U�[ID',
  `project_id` int(10) unsigned NOT NULL COMMENT '�v���W�F�N�gID',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `project_user_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `project_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COMMENT='�����v���W�F�N�g�}�X�^';


CREATE TABLE `projects` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '�v���W�F�N�gID',
  `project_name` varchar(50) NOT NULL COMMENT '�v���W�F�N�g��',
  `department_name` varchar(50) NOT NULL COMMENT '�Ǘ����喼',
  `site_name` varchar(255) NOT NULL COMMENT '�T�C�g��',
  `site_url` varchar(255) NOT NULL COMMENT '�T�C�gURL',
  `project_free_word` text COMMENT '�t���[���[�h���� : �t���[���[�h�����Ŏg�p\n�v���W�F�N�g���A�Ǘ����喼�A�T�C�g���A�T�C�gURL���f���~�^�Ō���',
  `is_clean` varchar(1) DEFAULT '0' COMMENT '�|���t���O : ''0'':�����{/''1'':���{',
  `public_package_id` int(10) unsigned DEFAULT NULL COMMENT '���݌��J���̃p�b�P�[�WID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='�v���W�F�N�g�}�X�^';


CREATE TABLE `rolls` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `roll_cd` varchar(1) NOT NULL COMMENT '�A�J�E���g���CD',
  `roll_name` text NOT NULL COMMENT '�A�J�E���g��ʖ�',
  PRIMARY KEY (`roll_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='�A�J�E���g��ʃ}�X�^';


CREATE TABLE `users` (
  `is_del` varchar(1) NOT NULL DEFAULT '0' COMMENT '�폜�t���O : ''1'':�폜/''0'':����ȊO',
  `created` datetime DEFAULT NULL COMMENT '�쐬����',
  `modified` datetime DEFAULT NULL COMMENT '�X�V����',
  `created_user_id` int(10) unsigned DEFAULT NULL COMMENT '�쐬��ID',
  `modified_user_id` int(10) unsigned DEFAULT NULL COMMENT '�X�V��ID',
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '���[�U�[ID',
  `username` varchar(50) NOT NULL COMMENT '���[�U�[��',
  `password` text NOT NULL COMMENT '�p�X���[�h',
  `email` varchar(255) NOT NULL COMMENT '���[���A�h���X',
  `contact_address` text NOT NULL COMMENT '�A����',
  `roll_cd` varchar(1) NOT NULL COMMENT '�A�J�E���g���CD',
  `user_free_word` text COMMENT '�t���[���[�h������ : �t���[���[�h�����p�̕�����\n���[�U�[���A���[���A�h���X�A�A������f���~�^�Ō���',
  `mt_author_id` int(10) unsigned DEFAULT NULL COMMENT 'MT���[�U�[ID',
  PRIMARY KEY (`id`),
  KEY `roll_cd` (`roll_cd`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`roll_cd`) REFERENCES `rolls` (`roll_cd`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COMMENT='���[�U�}�X�^ : ���[�U���Ǘ�';

