<?php

namespace Acedao;

class Container extends \Pimple\Container {

	public function __construct(array $config)
	{
		parent::__construct();

		$this['config'] = array_merge($config, array(
			'mode' => 'production', // if "production": limit some errors, if "strict": raise more exceptions and is more verbose
            'encoding' => 'utf8'
		));

		$this['db'] = function($c) {
			$db = new Database($c['config']['db']);
            $db->execute("SET NAMES '" . $c['config']['encoding'] . "';");
            return $db;
        };

		$this['query'] = $this->factory(function($c) {
			return new Query($c);
		});
	}
}