<?php
namespace Voilab\Acedao;

use Voilab\Acedao\Brick\Dao;

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