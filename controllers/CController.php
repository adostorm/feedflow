<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-29
 * Time: 上午4:56
 */

class CController extends \Phalcon\Mvc\Controller
{

    public function render($data, $message = 'OK')
    {

        header('HTTP/1.1 200 OK');
        header('Status: 200 OK');
        header('Content-Type: application/json; charset=utf-8');

        $result = array(
            'status' => 0,
            'errmsg' => array(
                'errno' => 0,
                'msg' => $message,
            ),
            'data' => $data
        );

        exit(json_encode($result));
    }

}