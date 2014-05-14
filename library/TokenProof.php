<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-13
 * Time: 下午4:30
 */

namespace Util;


final class TokenProof
{

    public static function check($app)
    {
        $params = array();
        foreach ($_REQUEST as $key => $value) {
            if ($key != 'token' && $key != '_url' && !is_numeric($key) && $key != 'showtoken') {
                $params[$key] = $app->request->get($key);
            }
        }
        $secrete = 'TO0nOIvhIFSitBMUgxlXbxmvris=';
        $token = \Util\Token::gen($params, $secrete);
        if ($app->request->get('showtoken')) {
            header('Content-Type: text/html; charset=utf8');
            echo "<pre>";
            var_dump($app->request->get());
            var_dump($params);
            var_dump($_SERVER['REQUEST_URI']);
            var_dump(strtoupper($token));
            var_dump(time());
            var_dump((time() - (int)$app->request->get('t')));
            echo "</pre>";
        }
        if ((time() - (int)$app->request->get('t')) < 1800) {
            if ($token != strtoupper($app->request->get('token'))) {
                throw new \Util\APIException(403, 2200, 'token不正确');
            }
        } else {
            throw new \Util\APIException(403, 2300, 'token已过期');
        }
    }


}