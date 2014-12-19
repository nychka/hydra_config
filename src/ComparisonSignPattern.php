<?php

 namespace Hydra;
/* implements Transformable, Suitable*/
 class ComparisonSignPattern extends Pattern
 {
  public function __construct($hydra, $sign)
  {
    $this->hydra = $hydra;
    $this->sign = $sign;
    $sign = preg_quote($sign);
    $this->pattern = "/\[$sign\d+\]/";
    $this->extractPattern = "/$sign|\[|\]/";
  }
  /**
  * Перевіряє число згідно правила
  *
  * @param string $match
  * @param number $round_num
  * @return bool
  */
  public function extract($match, $round_num){
    list($result) = $this->get_result($match);

    switch($this->sign){
      case '>': 
        return ($round_num > $result);
      case '>=':
        return ($round_num >= $result);
      case '<':
        return ($round_num < $result);
      case '<=':
        return ($round_num <= $result);
    }
  }
 }