<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-28
 * Time: 上午10:47
 */

use Util\Partition;
use HsMysql\CModel;

class CommonModel {

    protected $DI = null;

    protected $dbLink = '';

    protected $tbSuffix = '';

    protected $primary = 'PRIMARY';

    protected $partition = array();

    protected function getModel() {
        $model = CModel::init(
            $this->DI,
            $this->dbLink,
            $this->tbSuffix);

        $model->setPrimary($this->primary);

        return $model;
    }

    protected function getPartitionModel($key) {
        $partition = Partition::getInstance()->init(
            $this->dbLink,
            $this->tbSuffix,
            $this->partition
        )->run($key);

        $model = CModel::init(
            $this->DI,
            $partition->getLink(),
            $partition->getTbname());

        $model->setPrimary($this->primary);

        return $model;
    }

}