<?php
namespace Acedao;


class Factory {

    /**
     * @param $name
     * @return Queriable
     */
    public static function load($name) {
        /** @var Queriable $object */
        $object = new $name;
        $object->loadFilters();
        return $object;
    }
} 