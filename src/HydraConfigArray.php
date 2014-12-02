<?php

namespace Hydra;

class HydraConfigArray extends HydraConfig
{
  public function __construct($data, $config){
    if(!is_array($data))
      throw new \InvalidArgumentException('Data must be an associative array with parameters array("key" => "value").  What do you expect sending '.$data.' to HydraConfig?');
    if(!is_array($config) || !count($config))
      throw new \InvalidArgumentException('Config must be an associative array in HydraConfig format. See examples in HydraConfig source file');

    parent::__construct();

    $this->data = $data;
    $this->config = $this->replace_ranges_with_numbers($config);
    $this->config_keys = array_keys($this->config);
    $this->priority_table = $this->build_priority_table();
  }
}