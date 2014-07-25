<?php

namespace Acedao;

class Container extends \Pimple\Container {

	public function __construct(array $config)
	{
		parent::__construct();

		$this['config'] = array_merge(array(
			'mode' => 'production', // if "production": limit some errors, if "strict": raise more exceptions and is more verbose
            'encoding' => 'utf8',
            'namespace' => ''
		), $config);

		$this['db'] = function($c) {
			$db = new Database($c['config']['db']);
            $db->execute("SET NAMES '" . $c['config']['encoding'] . "';");
            return $db;
        };

		$this['query'] = $this->factory(function($c) {
			return new Query($c);
		});

        if (isset($this['config']['tables']) && is_array($this['config']['tables'])) {
            $namespace = $this['config']['namespace'];
            if ($namespace) {
                $namespace .= '\\';
            }
            foreach ($this['config']['tables'] as $classname => $tablename) {
                $this[$classname] = function ($c) use ($classname, $tablename, $namespace) {
                    return Factory::load($namespace . $classname, $c, $tablename);
                };
            }
        }
	}
}