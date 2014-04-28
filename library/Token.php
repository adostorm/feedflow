<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-29
 * Time: 上午5:37
 */

namespace Util;


class Token {

    private function __construct() {}
    private function __clone() {}

    public static function gen($data, $key) {
        $token = $key;
        $token .= self::loop_array_token($data);
        $token .= $key;
        $token = strtoupper(md5($token));
        return $token;
    }

    private static function loop_array_token($param){
        $token = "";
        ksort($param);
        foreach($param as $k=>$v){
            if(is_array($v)){
                $token .="{$k}";
                $token .= self::loop_array_token($v);
            }else{
                $token .= "{$k}{$v}";
            }
        }
        //处理特殊转义字符。
        if(get_magic_quotes_gpc()) {
            return stripslashes($token);
        }
        return $token;
    }

}