<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-13
 * Time: 下午12:28
 */

class BigvTask extends \Phalcon\CLI\Task
{

    /**
     * php cli.php Bigv run
     */
    public function runAction()
    {
        $this->_processQueue();
    }

    private function _processQueue()
    {
        $userCountModel = new UserCountModel($this->getDI());
        $userCountModel->buildBigvCache();
    }
}