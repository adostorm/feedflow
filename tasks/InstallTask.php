<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-30
 * Time: 上午9:41
 */


class InstallTask extends \Phalcon\CLI\Task {

    const BLACK = 30;
    const RED = 31;
    const GREEN = 32;
    const BROWN = 33;
    const BLUE = 34;
    const PURPLE = 35;
    const INDIGO = 36;
    const WHITE = 37;

    private $connection = null;

    private $dbname = '';

    private $tbname = '';

    public function helpAction() {
        $this->_help();
    }

    public function runAction($args=array()) {
        $this->_run($args);
    }

    private function _help() {
        $help = array();
        $help[] = str_pad('', 25, '*').' Command Tips '.str_pad('', 25, '*');

        $help[] = '- cli: ';
        $help[] = '  $ php /{$path}/cli.php Install run';
        $help[] = '';

        $help[] = '- expression format: ';
        $help[] = '  [[create|drop|truncate]:dbname:tbname:offset:limit [ ...]]';
        $help[] = '';

        $help[] = '- definition :';
        $help[] = '  +----------------+-------------------------------------------+';
        $help[] = '  | database       | table                                     |';
        $help[] = '  +----------------+-------------------------------------------+';
        $help[] = '  | db_countstate  | feed_index, user_count, user_feed_count   |';
        $help[] = '  | db_feedcontent | feed_content                              |';
        $help[] = '  | db_feedstate   | feed_relation                             |';
        $help[] = '  | db_userfeed    | user_feed                                 |';
        $help[] = '  | db_userstate   | user_relation                             |';
        $help[] = '  +----------------+-------------------------------------------+';
        $help[] = '';

        $help[] = '- fast :';
        $help[] = ' 1, [cmd]:db_countstate:feed_index:0:0';
        $help[] = ' 2, [cmd]:db_countstate:user_count:0:30';
        $help[] = ' 3, [cmd]:db_countstate:user_feed_count:0:30';
        $help[] = ' 4, [cmd]:db_feedcontent:feed_relation:0:30';
        $help[] = ' 5, [cmd]:db_userfeed:user_feed:0:30';
        $help[] = ' 6, [cmd]:db_userstate:user_relation:0:30';
        $help[] = '';

        $help[] = '- test :';
        $help[] = '  1, create:test:user:0:1';
        $help[] = '  2, create:test:user:0:1 truncate:test:example:0:1';
        $help[] = '';

        echo $this->_highlight(implode(PHP_EOL, $help), self::BLUE);
        echo PHP_EOL;
        exit;
    }

    private function _run($args) {
        $count = count($args);
        if($count <= 0) {
            $this->helpAction();
            exit;
        }

        echo PHP_EOL;
        foreach($args as $arg) {
            echo $this->_highlight('********** EXECUTE# '.$arg.' **********', self::BROWN).PHP_EOL;

            $_args = explode(':',$arg);
            if(count($_args) != 5) {
                echo 'Error expression : ' . $this->_highlight($arg, self::RED);
            } else {
                list($cmd, $dbname, $tbname, $offset, $limit) = explode(':', $arg);

                $offset = (int) $offset;
                $limit = (int) $limit;

                $func = sprintf('_%sTable', $cmd);
                if(!method_exists($this, $func)) {
                    echo 'Invalid command :'.$this->_highlight($cmd, self::RED);
                } else if(!isset($this->shemas[$dbname])) {
                    echo 'Invalid database : ' . $this->_highlight($dbname, self::RED);
                } else if(!isset($this->shemas[$dbname][$tbname])) {
                    echo 'Invalid table : '. $this->_highlight($tbname, self::RED);
                } else if($offset < -1) {
                    echo 'Invalid offset : '. $this->_highlight($offset, self::RED);
                } else if($limit < -1 || $limit > 100) {
                    echo 'Invalid limit : '
                        . $this->_highlight($limit, self::RED)
                        . ' is too big, The limit is between '
                        . $this->_highlight(0, self::GREEN)
                        . ' and '
                        . $this->_highlight(100, self::GREEN);
                } else {

                    $this->connection = $this->getDI()->getShared('link_'.$dbname);

                    $this->dbname = $dbname;
                    $this->tbname = $tbname;

                    $isSingle = $offset == 0 && $limit == 0;

                    if($isSingle) {
                        $limit = 1;
                    }

                    $_indexTbname = $tbname;

                    while($limit > 0) {

                        $limit --;

                        if(!$isSingle) {
                            $_indexTbname = $tbname . '_' . $offset;
                            $offset ++;
                        }

                        $result = call_user_func_array(
                            array($this, sprintf('_%sTable', $cmd))
                            , array($_indexTbname)
                        );

                        if($result === 0) {
                            echo $this->_getTipsStatus('ok', self::BLUE, self::BLUE);
                        } else if($result == -98) {
                            echo $this->_getTipsStatus('exists', self::BLUE, self::GREEN);
                            echo PHP_EOL;
                            continue;
                        } else if($result == -99) {
                            echo $this->_getTipsStatus('not exists', self::BLUE, self::GREEN);
                            echo PHP_EOL;
                            continue;
                        } else {
                            echo $this->_getTipsStatus($result, self::BLUE, self::RED);
                        }
                        echo PHP_EOL;

                    }
                }
            }

            echo PHP_EOL;
        }
    }

    private function _getTipsInfo($text, $color1, $color2) {
        $tmp = array();
        $tmp[] = $this->_highlight('Now is', $color2);
        $tmp[] = $this->_highlight($text['cmd'], $color1);
        $tmp[] = $this->_highlight($this->dbname.'.'.$text['indexTbname'], $color2);
        return implode(' ', $tmp);
    }

    private function _getTipsStatus($text, $lineColor, $textColor) {
        $tmp = array();
        $tmp[] = $this->_highlight(str_pad('', 20, '.'), $lineColor);
        $tmp[] = $this->_highlight($text, $textColor);
        return implode('', $tmp);
    }


    private function _highlight($text, $color) {
       return chr(27).'['.$color.'m'.$text.chr(27).'[0m';
    }

    private function _checkTable($tbname) {
        return (int) $this->connection->tableExists($tbname);
    }

    private function _createTable($indexTbname) {
        try {
            echo $this->_getTipsInfo(array(
                'cmd'=>'creating',
                'indexTbname'=>$indexTbname,
            ), self::PURPLE, self::BLUE);

            if($this->_checkTable($indexTbname)) {
                return -98;
            }
            $sql = sprintf($this->shemas[$this->dbname][$this->tbname], $indexTbname);
            $status = $this->connection->execute($sql);
            if($status) { return 0; }
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }

    private function _dropTable($indexTbname) {
        try {
            echo $this->_getTipsInfo(array(
                'cmd'=>'dropping',
                'indexTbname'=>$indexTbname,
            ), self::PURPLE, self::BLUE);

            if(!$this->_checkTable($indexTbname)) {
                return -99;
            }
            $status = $this->connection->dropTable($indexTbname);
            if($status) { return 0; }
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }

    private function _truncateTable($indexTbname) {
        try {
            echo $this->_getTipsInfo(array(
                'cmd'=>'truncating',
                'indexTbname'=>$indexTbname,
            ), self::PURPLE, self::BLUE);

            if(!$this->_checkTable($indexTbname)) {
                return -99;
            }
            $sql = 'truncate '.$indexTbname;
            $status = $this->connection->execute($sql);
            if($status) { return 0; }
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }

    private $shemas = array(
        'db_countstate'=>array(

            'feed_index'=>"CREATE TABLE `%s` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Feed流水号'",

            'user_count'=>"CREATE TABLE `%s` (
                  `uid` int(11) unsigned NOT NULL COMMENT '会员ID',
                  `follow_count` mediumint(8) DEFAULT '0' COMMENT '关注数',
                  `fans_count` mediumint(8) DEFAULT '0' COMMENT '粉丝数',
                  PRIMARY KEY (`uid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员好友粉丝计数器'
            ",

            'user_feed_count'=>"CREATE TABLE `%s` (
                  `uid` int(10) unsigned NOT NULL COMMENT '会员ID',
                  `app_id` tinyint(4) NOT NULL COMMENT '应用ID',
                  `feed_count` mediumint(9) DEFAULT '0' COMMENT '动态数',
                  `unread_count` mediumint(9) DEFAULT '0' COMMENT '未读动态数',
                  UNIQUE KEY `idx0` (`uid`,`app_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员Feed计数器'
            ",
        ),

        'db_feedcontent'=>array(
            'feed_content'=>"
                CREATE TABLE `%s` (
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
                  `extends` blob,
                  PRIMARY KEY (`feed_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='feed内容表'
            ",
        ),

        'db_feedstate'=>array(
            'feed_relation'=>"
                CREATE TABLE `%s` (
                  `app_id` tinyint(4) NOT NULL,
                  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
                  `feed_id` int(11) NOT NULL DEFAULT '0' COMMENT '动态内容ID',
                  `weight` tinyint(1) NOT NULL DEFAULT '0' COMMENT '权重',
                  `create_at` int(11) DEFAULT '0' COMMENT '时间',
                  UNIQUE KEY `idx0` (`uid`,`feed_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='feed 推送关系'
            ",
        ),
        'db_userfeed'=>array(
            'user_feed'=>"
                CREATE TABLE `%s` (
                  `app_id` tinyint(4) DEFAULT NULL COMMENT '应用ID',
                  `uid` int(11) DEFAULT NULL COMMENT '会员ID',
                  `feed_id` int(11) DEFAULT NULL COMMENT 'Feed内容ID',
                  `create_at` int(11) DEFAULT NULL COMMENT '创建时间',
                  UNIQUE KEY `idx0` (`uid`,`feed_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户发布的Feed关系'
            ",
        ),

        'db_userstate'=>array(
            'user_relation'=>"
                CREATE TABLE `%s` (
                  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
                  `friend_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '好友会员ID',
                  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态。0：A关注B；1：A与B相互关注',
                  `weight` tinyint(1) NOT NULL DEFAULT '0' COMMENT '权重',
                  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
                  UNIQUE KEY `idx0` (`uid`,`friend_uid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员关系'
            ",
        ),
    );

}