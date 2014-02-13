<?php
namespace Acedao;


class Factory {

    /**
     * @param $name
     * @return Queriable
     */
    public static function load($name) {
	    $params = func_get_args();
	    array_shift($params);
        /** @var Queriable $object */
	    $object = new $name();
	    call_user_func_array(array($object, 'construct'), $params);
        $object->loadFilters();
        return $object;
    }
} 