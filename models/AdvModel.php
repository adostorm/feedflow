<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-9
 * Time: 上午2:35
 */

class AdvModel extends \Phalcon\Mvc\Model
{

    public $partition = array();

    public $tbname = '';

    public $dbname = '';

    public function init($id)
    {
        $_pr = \Util\Partition::getInstance()
            ->init($this->dbname, $this->tbname, $this->partition);
        $_pr->run($id);

        $conf = \Util\ReadConfig::get($_pr->getPartDbname(), $this->getDI());
        if(isset($conf->slaves) && $slaves = $conf->slaves->toArray()) {
            $rnd = array_rand($slaves);
            $this->setWriteConnectionService($_pr->getPartDbname());
            $this->setReadConnectionService($_pr->getPartDbname().'_read_'.$rnd);
        }else {
            $this->setConnectionService($_pr->getPartDbname());
        }

        $this->setSource($_pr->getPartTbname());
    }

}