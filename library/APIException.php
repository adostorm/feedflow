<?php
/**
 * User: Jerry.Lee
 * Date: 14-5-5
 * Time: 下午4:31
 */

namespace Util;

use Phalcon\Exception;

final class APIException extends Exception
{

    protected $status = 0;

    protected $_types = array(
        'json' => 'application/json',
        'xml' => 'application/xml',
    );

    public function __construct($status, $code = 0, $message)
    {
        $this->status = $status;
        parent::__construct($message, $code);

        exit($this->render());
    }

    protected function render()
    {
        $this->sendHttpStatus($this->status);
        $this->sendContentType('json');

        $result = array(
            'status' => -1,
            'errmsg' => array(
                'errno' => $this->getCode(),
                'msg' => $this->getMessage(),
            ),
        );

        return json_encode($result);
    }

    public function sendContentType($type, $charset = 'utf-8')
    {
        $type = strtolower($type);
        if (!isset($this->_types[$type])) {
            $type = 'json';
        }
        header('Content-Type: ' . $this->_types[$type] . '; charset=' . $charset);
    }

    protected function sendHttpStatus($code)
    {
        static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:' . $code . ' ' . $_status[$code]);
        }
    }


}



