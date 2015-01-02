<?php
namespace Acedao;

use Acedao\Brick\Dao;

class Table implements Queriable {
    use Dao;

    public function __construct($tablename, Query $query) {
        $config = $query->getDependency($tablename);
        $this->setTableName($config['table'])
            ->setAlias($config['alias'])
            ->setFilters($config['filters']);

        $this->query = $query;
    }
}