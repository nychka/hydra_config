<?php

 namespace Hydra;
/* implements Transformable, Suitable*/
 class RangePattern extends Pattern
 {
  public function __construct($hydra)
  {
    $this->hydra = $hydra;
    $this->pattern = "/\[\d+\-\d+\]/";
    $this->extractPattern = "/\-|\[|\]/";
  }
  /**
  * Перевіряє число згідно правила
  *
  * @param string $match
  * @param number $round_num
  * @return bool
  */
  public function extract($match, $round_num){
    list($start, $end) = $this->get_result($match);
    return ($round_num >= $start && $round_num <= $end);
  }
 }