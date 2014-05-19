############### 一, 创建db_countstate的表##########################

-- 创建feed_index
SET @sql = "
	(
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
";
CALL test.create_table('db_countstate', 'feed_index', 0, 0, FALSE, @sql);
#CALL test.delete_table('db_countstate', 'feed_index', 0, 0, );


-- 创建user_count
SET @sql = "
	(
	  `uid` int(11) unsigned NOT NULL COMMENT '会员ID',
	  `follow_count` mediumint(8) DEFAULT '0' COMMENT '关注数',
	  `fans_count` mediumint(8) DEFAULT '0' COMMENT '粉丝数',
	  `feed_count` mediumint(8) DEFAULT '0' COMMENT '动态数',
	  PRIMARY KEY (`uid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员计数器'
";
CALL test.create_table('db_countstate', 'user_count', 0, 20, TRUE, @sql);
#CALL test.delete_table('db_countstate', 'user_count', 0, 20, );

###############  二,  创建db_feedcontent的表##########################

-- 创建 feed_content
SET @sql = "
	(
	  `feed_id` int(11) unsigned NOT NULL COMMENT '流水号',
	  `app_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
	  `source_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '来源。1：网站；2：Android；3：IOS；4：IPAD',
	  `object_type` smallint(6) NOT NULL DEFAULT '0' COMMENT '类型。image, text, video, attension, favorite, post, reply',
	  `object_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应类型的ID',
	  `author_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布者ID',
	  `author` varchar(30) NOT NULL,
	  `content` varchar(200) NOT NULL DEFAULT '' COMMENT '内容',
	  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
	  `weight` tinyint(4) DEFAULT '0' COMMENT '权重',
	  `attachment` varchar(1000) DEFAULT '',
	  `extends` blob ,
	  PRIMARY KEY (`feed_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='feed内容表'
";
CALL test.create_table('db_feedcontent', 'feed_content', 0, 20, TRUE, @sql);
#CALL test.delete_table('db_feedcontent', 'feed_content', 0, 300, );


###############  三  创建`db_feedstate`的表##########################

-- 创建 feed_relation
SET @sql = "
	(
	  `app_id` tinyint(4) NOT NULL,
	  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
	  `feed_id` int(11) NOT NULL DEFAULT '0' COMMENT '动态内容ID',
	  `weight` tinyint(1) NOT NULL DEFAULT '0' COMMENT '权重',
	  `create_at` int(11) DEFAULT '0' COMMENT '时间',
	  UNIQUE KEY `idx0` (`uid`,`feed_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='feed 推送关系'

";
CALL test.create_table('db_feedstate', 'feed_relation', 0, 300, TRUE, @sql);
#CALL test.delete_table('db_feedstate', 'feed_relation', 0, 300, );


###############  四  创建`db_userfeed`的表##########################

-- 创建 user_feed
SET @sql = "
	(
	  `app_id` tinyint(4) DEFAULT NULL,
	  `uid` int(11) DEFAULT NULL,
	  `feed_id` int(11) DEFAULT NULL,
	  `create_at` int(11) DEFAULT NULL,
	  UNIQUE KEY `idx0` (`uid`,`feed_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8

";
CALL test.create_table('db_userfeed', 'user_feed', 0, 300, TRUE, @sql);
#CALL test.delete_table('db_userfeed', 'user_feed', 0, 300, );


###############  五  创建``db_userstate``的表##########################

-- 创建 user_relation
SET @sql = "
	(
	  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
	  `friend_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '好友会员ID',
	  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态。0：A关注B；1：A与B相互关注',
	  `weight` tinyint(1) NOT NULL DEFAULT '0' COMMENT '权重',
	  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
	  UNIQUE KEY `idx0` (`uid`,`friend_uid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员关系'
";
CALL test.create_table('db_userstate', 'user_relation', 0, 300, TRUE, @sql);
#CALL test.delete_table('db_userstate', 'user_relation', 0, 300, );


