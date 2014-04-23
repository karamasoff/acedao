<?php
namespace Acedao;


class Factory {

    /**
     * @param string $name Le nom de la DAO qu'on est en train de charger
     * @param Container Le container qui contient acedao ainsi que toutes les autres DAO
     * @param string $tableName Le nom de la table déduit du nom de la DAO
     * @return Queriable
     */
    public static function load($name, $acedaoContainer, $tableName) {
        $params = func_get_args();
        $params = array_slice($params, 3);

        /** @var Queriable $object */
        $object = new $name();
        $object->setTableName($tableName);
        $object->init($acedaoContainer);
        call_user_func_array(array($object, 'construct'), $params);
        $object->loadFilters();
        return $object;
    }
} 