<?php
  namespace Hydra;

  class HydraConfigKohana extends HydraConfig {

    private static $configs = array();
    public static $settings = array();
    public static $namespace = 'hydra';

    public function __construct($data, $config_name){
      parent::__construct();

      $this->data = $data;
      $this->config_name = $config_name;
	    $config = self::get_config(self::get_hydra_config_name($config_name));
      $this->config = $this->replace_ranges_with_numbers($config);
      $this->config_keys = array_keys($this->config);
      $this->priority_table = $this->build_priority_table();
      $this->original_config = self::get_config($config_name);
    }
    /**
    * Повертає усі конфіги у їх початковий стан
    */
    public static function unwrap_all(){
      foreach(self::$configs as $config){$config->unwrap_config();unset($config); }
    }
    /**
    * повертає аналог імені у форматі запису згідно Hydra конфігурації
    * використовується при пошуку аналогу існуючої конфігурації для заміщення
    * 'config.alternative_payment_systems' ~> 'hydra.config:alternative_payment_systems'
    * @param string $cofig_name
    * @return string
    */
    public static function get_hydra_config_name($config_name){
      $hydra_config_name = self::get_hydra_analog_config_name($config_name);
      return (!self::is_hydra_compatible($config_name)) ? $hydra_config_name : $config_name;
    }
    /**
    * Згідно переданих даних, шукає найбільш підходяще значення з вказаної конфігурації та
    * заміщує її цим знайденим значенням
    * 
    * @param Array $data - містить дані по яких здійснюється пошук у hydra-конфігурації
    * @param string $config_name - конфігурація, яка міститиме дані згідно переданих даних
    * @return HydraConfig instance
    */
    public static function create($data, $config_name){
      if(!self::is_hydra_enabled()) return false;
      if(!self::is_hydra_compatible($config_name) && !self::has_hydra_config_analog($config_name))
        throw new UnexpectedValueException('Hydra configuration analog '.self::get_hydra_analog_config_name($config_name).' not found. Create it or check name again');
      if(!is_array($data))
        throw new InvalidArgumentException('Data must be an associative array with parameters array("key" => "value").  What do you expect sending '.$data.' to HydraConfig?');
      if(!(count($data) > 1))
        throw new LengthException('Data length for HydraConfig must be more than one - see examples in HydraConfig source file');
      if(!count(self::get_config(self::get_hydra_config_name($config_name))))
        throw new UnexpectedValueException('Requested config: '.self::get_hydra_config_name($config_name).' is empty');

      $hydra = new HydraConfigKohana($data, $config_name);
      self::$configs[$config_name] = $hydra;
      return $hydra;
    }
    /**
    * Перевіряє чи HydraConfig включений
    * задається конфігом $config[self::$namespace] ~> $config['hydra']
    * @return Boolean
    */
    public static function is_hydra_enabled(){
      $config_name = 'config.'.self::$namespace;
      return self::config_exists($config_name) && self::get_config($config_name);
    }
    /**
    * Перевіряє чи конфігурація є hydra сумісна
    * @param string $config_name
    * @return Boolean
    */
    public static function is_hydra_compatible($config_name){
      $namespace = self::$namespace;
      preg_match("/^$namespace\.[\w\_\-\.]+/", $config_name, $matches);
      return count($matches);
    }
    public static function get_hydra_analog_config_name($config_name){
      $name = preg_replace("/\./", ":", $config_name);
      $hydra_config_analog = self::$namespace.".".$name;
      return $hydra_config_analog;
    }
    /**
    * Перевіряє чи конфіг має hydra аналог для заміщення
    * 
    * існуючий конфіг та його hydra-аналог:
    * 'config.alternative_payment_systems' ~ 'hydra.config:alternative_payment_systems'
    * 
    * @param $config_name - звичайний (НЕ hydra сумісний) конфіг
    * @return Boolean
    */
    public static function has_hydra_config_analog($config_name){
      $hydra_config_analog = self::get_hydra_analog_config_name($config_name);
      return self::config_exists($hydra_config_analog);
    }
    /**
    * Повертає конфіг у початковий стан
    * @param string $config_name
    * @return Array - відновлена конфігурація
    */
    public static function unwrap($config_name){
      if(array_key_exists($config_name, self::$configs)){
        $config = self::$configs[$config_name]->unwrap_config();
        return $config;
      }
      throw new UnexpectedValueException("You are trying to unwrap config ".$config_name.", but wait a minute - are you sure that it has valid name and it is wrapped?");
    }
    /**
    * Перевіряє чи конфігурація обгорнута
    * @return Boolean
    */
    public static function is_wrapped($config_name){
      return array_key_exists($config_name, self::$configs);
    }
    /**
    * Витягує hydra config по імені
    * @param string $config_name
    * @param HydraCofig instance
    */
    public static function get_hydra_by_config($config_name){
      if(self::is_wrapped($config_name))
        return self::$configs[$config_name];

      throw new UnexpectedValueException("Hydra not found by config name: ".$config_name.". Check if such config is valid and if it has been already wrapped by HydraConfig");
    }
    /**
    * Перевіряє чи конфігурація існує
    * @param string $config_name
    * @return Boolean
    */
    public static function config_exists($config_name){
      return (null !== Kohana::config($config_name));
    }
    /**
    * Витягує конфігурацію по імені
    * @param string $config_name
    * @return Array
    */
    public static function get_config($config_name){
      $config = Kohana::config($config_name);
      if(null === $config)
        throw new UnexpectedValueException('Config '.$config_name.' not found. Please check if Kohana::config(\''.$config_name.'\') returns correct value');
      return $config;
    }
    /**
    * Оновлює конфігурацію
    * @param string $config_name
    * @param Array $config
    */
    public static function set_config($config_name, $config){
      Kohana::config_set($config_name, $config);
    }
    /**
    * Повертає початкову конфігурацію
    * @return Array
    */
    public function get_original_config(){
      return $this->original_config;
    }
    /**
    * Повертає заміщену конфігурацію
    * @return Array
    */
    public function get_final_config(){
      return $this->final_config;
    }
    /**
    * Заміщує конфігурацію згідно даних
    * @return Array - оновлена конфігурація
    */
    public function wrap_config(){
      $config = $this->find();
      $final_config = (!self::is_hydra_compatible($this->config_name)) ? array_merge($this->original_config, $config) : $config;
      self::set_config($this->config_name,  $final_config);
      $this->final_config = $config;
      return $config;
    }
    /**
    * Повертає конфігурацію в її початковий стан
    * @return Array - початкова конфігурація
    */
    public function unwrap_config(){
      self::set_config($this->config_name, $this->original_config);
      return $this->original_config;
    }
  }
