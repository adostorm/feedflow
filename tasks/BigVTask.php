<?php
/**
 * User: JiaJia.Lee
 * Date: 14-5-6
 * Time: 上午3:49
 */

class BigVTask extends \Phalcon\CLI\Task {

    public function runAction() {
        $this->_process();
    }


    private function _process() {
        $count = new UserCountModel($this->getDI());
        $results = $count->getBigVList();
    }

}