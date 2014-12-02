<?php

namespace Hydra\Test;

use Hydra\HydraConfigArray;

class HydraConfigArrayTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessageRegExp /^Data must be an associative array/
	*/
	public function testWrongData()
	{
		$data = null;
		$config = array(
			'12_*_*' => 'foo_and_*_and_*',
			'12_BAR_*' => 'foo_and_bar_and_*',
			'12_BAR_[50..101]' => 'range between 50 and 100 successed'
		);
		$hydra = new HydraConfigArray($data, $config);
	}
	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessageRegExp /^Config must be an associative array in HydraConfig format/
	*/
	public function testWrongConfig()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR',
			'num' => 100
		);
		$config = null;
		$hydra = new HydraConfigArray($data, $config);
	}
	public function testDataIsCorrect()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR'
		);
		$config = array(
			'12_*' => 'foo_and_*',
			'12_BAR' => 'foo_and_bar',
		);
		$hydra = new HydraConfigArray($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'foo_and_bar');
	}
	public function testRangeParameter()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR',
			'num' => 100
		);
		$config = array(
			'12_*_*' => 'foo_and_*_and_*',
			'12_BAR_*' => 'foo_and_bar_and_*',
			'12_BAR_[50..101]' => 'range between 50 and 100 successed'
		);
		$hydra = new HydraConfigArray($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'range between 50 and 100 successed');
	}
	public function testRangeParameter2()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR',
			'num' => 102
		);
		$config = array(
			'12_*_*' => 'foo_and_*_and_*',
			'12_BAR_*' => 'foo_and_bar_and_*',
			'12_BAR_[102..200]' => 'range between 102 and 200 successed',
			'12_BAR_[50..101]' => 'range between 50 and 100 successed',
		);
		$hydra = new HydraConfigArray($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'range between 102 and 200 successed');
	}
	public function testGreaterThanPattern()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR',
			'num' => 201
		);
		$config = array(
			'12_*_*' => 'foo_and_*_and_*',
			'12_BAR_*' => 'foo_and_bar_and_*',
			'12_BAR_[>200]' => 'number is greater than 200 successed',
			'12_BAR_[50..100]' => 'range between 50 and 100 successed',
		);
		$hydra = new HydraConfigArray($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'number is greater than 200 successed');
	}
	public function testGreaterThanPattern2()
	{
		$data = array(
			'foo' => 12,
			'bar' => 'BAR',
			'num' => 99
		);
		$config = array(
			'12_*_*' => 'foo_and_*_and_*',
			'12_BAR_*' => 'foo_and_bar_and_*',
			'12_BAR_[50..100]' => 'range between 50 and 100 successed',
			'12_BAR_[>200]' => 'number is greater than 200 successed',
			'12_BAR_[>99]' => 'number is greater than 99 successed',
		);
		$hydra = new HydraConfigArray($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'range between 50 and 100 successed');
	}
}