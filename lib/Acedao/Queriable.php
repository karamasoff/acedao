<?php

namespace Acedao;


interface Queriable {

    /**
     * Set the table name
     *
     * @param string $tablename
     * @return Queriable
     */
    public function setTableName($tablename);

    /**
     * Set the table name
     *
     * @param string $alias
     * @return Queriable
     */
    public function setAlias($alias);

    /**
     * Set the DAO filters
     *
     * @param array $filters
     * @return Queriable
     */
    public function setFilters(array $filters);

    /**
     * Get the table name
     *
     * @param string $suffix The table alias suffix
     * @return mixed
     */
    public function t($suffix = null);

    /**
     * Initialisation method used inside the Dao trait
     *
     * @param Container $c
     * @return void
     */
    public function init(Container $c);

    /**
     * An array of all the authorized fields that can be used in a query on this table
     * @return mixed
     */
    public function getAllowedFields();

    /**
     * This methods will be defined in the Dao traits
     * Get the defined query possibilities of the Queriable object
     * or a subset of it.
     *
     * @param string $key Provide a key to get a subset of the filters
     * @return array
     */
    public function getFilters($key = null);

    /**
     * Select query
     *
     * @param array $config
     * @param bool $debug
     * @return array
     */
    public function select(array $config, $debug = false);

    /**
     * Delete query
     *
     * @param array|int $config If int is passed, the record with the relative ID will be deleted, if array is passed, will be used as the select() method $config array
     * @return @return int Number of deleted records
     */
    public function delete($config);

    /**
     * Insert/Update query
     *
     * @param array $data Associative array representing data to save
     * @param array $allowedFields Overrides getAllowedFields() returned array
     * @param bool $debug Display some debugging informations
     * @return int If inserted -> last insert id. If updated, number of udpated rows.
     */
    public function save(array $data, array $allowedFields = null, $debug = false);
}