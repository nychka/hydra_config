<?php

namespace Hydra\Test;

use Hydra\HydraConfig;

class HydraConfigTest extends \PHPUnit_Framework_TestCase
{
	public function testIsTrue()
	{
		$this->assertEquals(1, 1);
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
		$hydra = new HydraConfig($data, $config);
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
		$hydra = new HydraConfig($data, $config);
		$result = $hydra->find();

		$this->assertEquals($result, 'range between 50 and 100 successed');
	}
}