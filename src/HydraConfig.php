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
  abstract class HydraConfig {

    public function __construct(){
      $this->measure_start(__FUNCTION__);
      $this->delimiter = "\_";
      $this->any = "\*";
      $this->range_index = null;
      $this->range_data = null;
      $this->patterns = array(
        new RangePattern($this),
        new ComparisonSignPattern($this, '>'),
        new ComparisonSignPattern($this, '>='),
        new ComparisonSignPattern($this, '<'),
        new ComparisonSignPattern($this, '<=')
      );
      $this->measure_end(__FUNCTION__);
    }
    public function get_debug_info()
    {
      return $this->debug;
    }
    public function measure_start($method_name)
    {
      if(!isset($this->debug)) $this->debug = array('total_time/ms' => 0, 'total_memory/kb' => 0);

      if(!isset($this->debug[$method_name])){
        $this->debug[$method_name] = array('time' => array(), 'memory' => array());
      } 
      $this->debug[$method_name]['time']['start'] = microtime(true);
      $this->debug[$method_name]['memory']['start'] = memory_get_usage();
    }
    public function measure_end($method_name)
    {
      $this->debug[$method_name]['time']['end'] = microtime(true);
      $this->debug[$method_name]['memory']['end'] = memory_get_usage();
      $time_end = $this->debug[$method_name]['time']['end'];
      $time_start = $this->debug[$method_name]['time']['start'];

      $memory_end = $this->debug[$method_name]['memory']['end'];
      $memory_start = $this->debug[$method_name]['memory']['start'];

      $this->debug[$method_name]['total/ms'] = ($time_end - $time_start) * 1000; // miliseconds
      $this->debug['total_time/ms'] += $this->debug[$method_name]['total/ms'];
      $this->debug[$method_name]['total/kb'] = ($memory_end - $memory_start) / 1024; // kilobytes
      $this->debug['total_memory/kb'] += $this->debug[$method_name]['total/kb'];
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
      $this->measure_start(__FUNCTION__);
      $new_config = array();
      $self = $this;
      foreach($config as $key => $value){
        $new_key = $this->transform_numbers($key);
        $new_config[$new_key] = $value;
      }
      // echo "<pre>"; print_r($new_config); echo "</pre>";
      $this->measure_end(__FUNCTION__);
      return $new_config;
    }
    public function transform_numbers($key){
      $patternObj = $this->patterns[0];
      $new_key = $key;
      foreach($this->patterns as $patternObj){
        $new_key = $patternObj->transform($new_key);
      }
      return $new_key;
    }
    /**
    * Визначає кіл-ть параметрів для побудови таблиці пріоритетів
    * @return Number
    */
    public function get_heads_count($config_key = null){
      $this->measure_start(__FUNCTION__);
      $key = $config_key ? $config_key : $this->config_keys[0];
      $heads = preg_split("/$this->delimiter/", $key);
      $this->measure_end(__FUNCTION__);
      return count($heads);
    }

    /**
    * Перевіряє чи кіл-ть параметрів в конфігурації дорівнює кіл-ті переданих параметрів даних
    *
    * @param array $data_keys
    * @return bool
    */
    public function check_heads_count($data_keys){
      $this->measure_start(__FUNCTION__);
      $heads_count_should_be = count($data_keys);
      foreach($this->config_keys as $key){
        $hydra_params_count = $this->get_heads_count($key);
        if($hydra_params_count !== $heads_count_should_be){
          throw new LengthException("Invalid key: $key - data params count: $heads_count_should_be MUST BE equal to params count: $hydra_params_count defined in hydra configuration");
        }
      }
      $this->measure_end(__FUNCTION__);
      return true;
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
    public function build_priority_table(){
      $this->measure_start(__FUNCTION__);
      $heads_count = $this->get_heads_count();
      $data_keys = $this->get_data_keys();//array_keys($this->data);
      $columns_count = pow(2, $heads_count) - 1; 
      $numbers = range($columns_count, 1);
      $variations = array();

      $this->check_heads_count($data_keys);

      foreach($numbers as $num){
        $str = sprintf("%0".$heads_count."d", decbin($num)); // e.g 011
        $variation = array();
        foreach($data_keys as $key => $value) $variation[$value] = $str[$key];
        $variations[] = $variation;
      }
      $this->measure_end(__FUNCTION__);
      return $variations;
    }
    public function get_delimiter(){
      return $this->delimiter;
    }
    public function get_data(){
      return $this->data;
    }
    public function get_data_keys(){
      if(!isset($this->data_keys)){
        $this->data_keys = array_keys($this->data);
      }
      return $this->data_keys;
    }
    /**
    * Будує шаблон згідно варіанту таблиці пріоритетів та даних
    * @param Array - варіант таблиці пріоритетів
    * @return string - regex pattern 
    */
    public function regex_transform($item){
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
      $this->measure_start(__FUNCTION__);
      foreach($this->priority_table as $value){
        $pattern = $this->regex_transform($value);
        $search_results = preg_grep("/$pattern/", $this->config_keys);

        if(count($search_results)){
          $arr = array_values($search_results);
          $value = array_shift($arr);
          $this->measure_end(__FUNCTION__);
          return $this->config[$value];
        }
      }
      $this->measure_end(__FUNCTION__);
      return array();
    }
  }
