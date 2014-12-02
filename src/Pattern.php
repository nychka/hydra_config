<?php

namespace Hydra;

abstract class Pattern
{
  /**
  * Заміщує шаблон даними згідно відповідного правила
  *
  * @param string $key
  * @return string 
  */
  public function transform($key)
  {
    $self = $this;

    $new_key = preg_replace_callback($this->pattern, function($matches) use ($key, $self){
      if(count($matches)){
        $original_number = $self->get_data_for_range($key);
        $roundNum = round($original_number);
        $extracted = $self->extract($matches[0], $roundNum);
        return ($extracted) ? $original_number : 'N';
      }
    }, $key);
    if($key !== $new_key)$key = $new_key;
    return $new_key;
  }
  /**
  * Визначає позицію даних, які потрібно замінити
  *
  * @param string $key
  * @return number
  */
  public function define_range_position($key){
    $delimiter = $this->hydra->get_delimiter();
    $heads = preg_split("/$delimiter/", $key);
    foreach($heads as $index => $head){
      preg_match($this->pattern, $head, $out);
      if(count($out))return $index;
    }
  }
  /**
  * Знаходить число з масиву даних, яке відповідає діапазону чисел у конфігурації
  *
  * @param string $key
  * @return number
  */
  public function get_data_for_range($key){
    $data = $this->hydra->get_data();
    $keys = $this->hydra->get_data_keys();
    if(!$this->range_data){
      if(!$this->range_index){
        $this->range_index = $this->define_range_position($key);
      }
      $keys = array_keys($data);
      $string_index = $keys[$this->range_index];
      $this->range_data = $data[$string_index];
    }
    return $this->range_data;
  }
}