<?php
  namespace Hydra;
  /**
  * @author Nychka Yaroslav <nychka93@gmail.com>
  *
  * Кожне значення параметру, яке визначене в масиві даних,
  * задається через розділювач,та в строгій послідовності
  * якщо значення параметру може бути будь-яким,
  * тоді воно позначається спеціальним символом *
  * 
  * 
  * Приклад конфігу:
  * array(
  *     '140_1_PS' => "all present",
  *     '140_1_*' => "no avia",
  *     '140_*_PS' => "no system_id",
  *     '140_*_*' => "only referer_id",
  *     '*_1_PS' => "no referer_id",
  *     '*_1_*' => "only system_id",
  *     '*_*_PS' => "only avia"
  *  );
  * Приклад масиву даних:
  * array('referer_id' => 140, 'system_id' => 1, 'avia' => 'PS');
  *
  * Приклад таблиці пріоритетів, яка сформується: 
  *  array(
  *      array('referer_id' => 1, 'system_id' => 1, 'avia' => 1),
  *      array('referer_id' => 1, 'system_id' => 1, 'avia' => 0),
  *      array('referer_id' => 1, 'system_id' => 0, 'avia' => 1),
  *      array('referer_id' => 1, 'system_id' => 0, 'avia' => 0),
  *      array('referer_id' => 0, 'system_id' => 1, 'avia' => 1),
  *      array('referer_id' => 0, 'system_id' => 1, 'avia' => 0),
  *      array('referer_id' => 0, 'system_id' => 0, 'avia' => 1),
  *  );
  * 
  * Якщо параметр аvia взагалі не використовується,
  * тоді доцільно викинути його з конфігу та з масиву даних:
  * Приклад конфігу:
  * array(
  *     '140_1' => "all present",
  *     '140_*' => "no system id",
  *     '*_140' => "no referer_id",
  *  );
  * Приклад масиву даних:
  * array('referer_id' => 140, 'system_id' => 1);
  *
  * Приклад таблиці пріоритетів, яка сформується: 
  *
  * array(
  *   array('referer_id' => 1, 'system_id' => 1),
  *   array('referer_id' => 1, 'system_id' => 0),
  *   array('referer_id' => 0, 'system_id' => 1)
  * )
  */
  class HydraConfig {

    private static $configs = array();
    public static $settings = array();
    public static $namespace = 'hydra';

    public function __construct($data, $config){
      if(!is_array($data))
        throw new InvalidArgumentException('Data must be an associative array with parameters array("key" => "value").  What do you expect sending '.$data.' to HydraConfig?');
      if(!is_array($config) || !count($config))
        throw new InvalidArgumentException('Config must be an associative array HydraConfig format. See examples in HydraConfig source file');
      
      $this->delimiter = "\_";
      $this->any = "\*";
      $this->range_pattern = "/\[\d+\.\.\d+\]/";
      $this->range_index = null;
      $this->range_data = null;
      $this->data = $data;
      $this->config = $this->replace_ranges_with_numbers($config);
      $this->config_keys = array_keys($this->config);
      $this->priority_table = $this->build_priority_table();
    }
    public function define_range_position($key){
      $heads = preg_split("/$this->delimiter/", $key);
      foreach($heads as $index => $head){
        preg_match($this->range_pattern, $head, $out);
        if(count($out))return $index;
      }
    }
    /**
    * Знаходить число з масиву даних, яке відповідає діапазону чисел у конфігурації
    *
    * @return number
    */
    public function get_data_for_range($key){
      if(!$this->range_data){
        if(!$this->range_index)
          $this->range_index = $this->define_range_position($key);

        $keys = array_keys($this->data);
        $string_index = $keys[$this->range_index];
        $this->range_data = $this->data[$string_index];
      }
      return $this->range_data;
    }
    /**
    * Заміняє діапазони чисел числом з масиву даних, якщо воно входить в нього, в інакшому випадку 'N'
    * 
    * $data = array('referer_id' => 140, 'system_id' => null, 'avia' => 'PS', 'amount' => 500)
    * 1. '140_*_PS_[344..7899]' ~> '140_*_PS_500' 
    * 2. '140_*_PS_[501..7899]' ~> '140_*_PS_N'
    *
    * @return array конфіг із заміненими діапазонами на цілі числа
    */
    public function replace_ranges_with_numbers($config){
      $new_config = array();
      $self = $this;
      foreach($config as $key => $value){
        $new_key = preg_replace_callback($this->range_pattern, function($matches) use ($key, $self){
          if(count($matches)){
            $original_number = $self->get_data_for_range($key);
            $round_number = round($original_number);
            list($start, $end) = preg_split("/\.|\[|\]/", $matches[0],-1, PREG_SPLIT_NO_EMPTY);
            $amount = (in_array($round_number, range($start, $end))) ? $original_number : 'N';
            return $amount;
          }
        }, $key);
        $new_config[$new_key] = $value;
      }
      //echo "<pre>"; print_r($new_config); echo "</pre>";
      return $new_config;
    }
    /**
    * Визначає кіл-ть параметрів для побудови таблиці пріоритетів
    * @return Number
    */
    public function get_heads_count(){
      $key = $this->config_keys[0];
      $heads = preg_split("/$this->delimiter/", $key);
      return count($heads);
    }

    /**
    * Формує таблицю пріоритетів
    * | параметри | варіанти |
    * |     4     | 16 - 1   |
    * |     3     |  8 - 1   |
    * |     2     |  4 - 1   |
    * Повинен бути вказаний, хоча б один параметр,
    * варіант, де всі нулі, відкидається,
    * і загальна кіл-ть варіантів зменшується на одиницю
    *
    * Кіл-ть параметрів визначаює кіл-ть можливих варіантів,
    * а їхній пріоритет визначається розташуванням параметрів у масиві даних.
    * Наприклад параметр 'referer_id' має найвищий пріоритет,
    * тому що вказаний в масиві даних першим, в той час як 'avia'
    * має найнижчий, тому вказаний у самому кінці.
    *
    * приклад одного варіанту з таблиці пріоритетів:
    * array('referer_id' => 1, 'system_id' => 0, 'avia' => 1)
    *
    * @return Array - масив варіантів
    */
    private function build_priority_table(){
      $heads_count = $this->get_heads_count();
      $data_keys = array_keys($this->data);
      $columns_count = pow(2, $heads_count) - 1; 
      $numbers = range($columns_count, 1);
      $variations = array();

      if(count($data_keys) !== $heads_count)
        throw new LengthException('Data params count MUST BE equal to params count defined in hydra configuration: '/*.self::get_hydra_analog_config_name($this->config_name)*/);

      foreach($numbers as $num){
        $str = sprintf("%0".$heads_count."d", decbin($num)); // e.g 011
        $variation = array();
        foreach($data_keys as $key => $value) $variation[$value] = $str[$key];
        $variations[] = $variation;
      }
      return $variations;
    }
    /**
    * Будує шаблон згідно варіанту таблиці пріоритетів та даних
    * @param Array - варіант таблиці пріоритетів
    * @return string - regex pattern 
    */
    private function regex_transform($item){
      $pattern = "";
      foreach($item as $key => $value){
        $pattern .= ($value && isset($this->data[$key])) ? $this->data[$key] : $this->any;
        $pattern .= $this->delimiter;
      }
      return preg_replace("/\\\_$/", "", $pattern);
    }
    /**
    * Повертає найбільш підходящий варіант основуючись на таблиці пріоритетів та даних
    * @return Array
    */
    public function find(){
      foreach($this->priority_table as $value){
        $pattern = $this->regex_transform($value);
        $search_results = preg_grep("/$pattern/", $this->config_keys);

        if(count($search_results)){
          $arr = array_values($search_results);
          $value = array_shift($arr);
          return $this->config[$value];
        }
      }
      return array();
    }
  }
?>
