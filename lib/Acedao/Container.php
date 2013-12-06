<?php

namespace Acedao;

class Container extends \Pimple {

	public function __construct(array $config)
	{
		parent::__construct();

		$this['config'] = array_merge($config, array(
			'mode' => 'production' // if "production": limit some errors, if "strict": raise more exceptions and is more verbose
		));

		$this['db'] = function($c) {
			return new Database($c['config']['db']);
		};

		$this['query'] = function($c) {
			return new Query($c);
		};
	}
}