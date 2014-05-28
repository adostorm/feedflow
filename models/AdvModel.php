<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-9
 * Time: 上午2:35
 */

use \Util\Partition;
use \Util\ReadConfig;

class AdvModel extends \Phalcon\Mvc\Model
{

    public $partition = array();

    public $tbSuffix = '';

    public $dbLink = '';

    public function init($id)
    {
        $_pr = Partition::getInstance()
            ->init($this->dbLink, $this->tbSuffix, $this->partition)
            ->run($id);

        $conf = ReadConfig::get($_pr->getLink(), $this->getDI());
        if(isset($conf->slaves) && $slaves = $conf->slaves->toArray()) {
            $rnd = array_rand($slaves);
            $this->setWriteConnectionService($_pr->getLink());
            $this->setReadConnectionService($_pr->getLink().'_read_'.$rnd);
        }else {
            $this->setConnectionService($_pr->getLink());
        }

        $this->setSource($_pr->getTbname());
    }

}