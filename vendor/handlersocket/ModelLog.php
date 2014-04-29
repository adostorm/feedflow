<?php
/**
 * User: JiaJia.Lee
 * Date: 14-4-29
 * Time: 上午3:59
 */

namespace HSocket;


use Phalcon\Logger\Adapter\File;

class ModelLog extends File {

    private $file = '';

    private $name = '';

    private $options = null;

    private $diConfig = null;

    public function __construct($diConfig, $name='', $options=null) {
        $this->name = $name;
        $this->options = $options;
        $this->diConfig = $diConfig;

        $this->_init();

        parent::__construct($this->file, $this->options);
    }

    private function _init() {
        if(empty($this->name)) {
            $this->name = sprintf('%s.log', date('Ymd'));
        }
        if(empty($this->option)) {
            $this->option = null;
        }
        $this->file = $this->getApplicationPathByDiConfig($this->diConfig).$this->name;
        if(!file_exists($this->file)) {
            $fh = fopen($this->file,"w+");
            fclose($fh);
        }
    }

    private function getApplicationPathByDiConfig($diConfig) {
        $file = $diConfig->{'application'}->{'log'};
        if(!file_exists($file)) {
            mkdir($file, 0777, true);
        }
        return $file;
    }

}