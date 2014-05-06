<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-6
 * Time: 下午5:49
 */

class CCommonModel extends \Phalcon\Mvc\Model {

    public $tableName = '';

    public $connectService = '';

    public $partition = ''; //mod

    public function initialize()
    {
        $this->setConnectionService($this->connectService);

        if($this->partition) {
            $this->setSource($this->tableName);
        }
    }

}