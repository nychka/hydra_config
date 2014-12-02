<?php

 namespace Hydra;
/* implements Transformable, Suitable*/
 class GreaterThanPattern extends Pattern
 {
  public function __construct($hydra)
  {
    $this->hydra = $hydra;
    $this->pattern = '/\[\\>\d+\]/';
    $this->extractPattern = "/\>|\[|\]/";
  }
  /**
  * Перевіряє число згідно правила
  *
  * @param string $match
  * @param number $round_num
  * @return bool
  */
  public function extract($match, $round_num){
    list($result) = preg_split($this->extractPattern, $match,-1, PREG_SPLIT_NO_EMPTY);
    echo "$round_num > $result";
    return ($round_num > $result);
  }
 }