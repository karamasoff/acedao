<?php

namespace Acedao;


interface Queriable {

    /**
     * Set the table name
     *
     * @param string $tablename
     * @return void
     */
    public function setTableName($tablename);

    /**
     * Get the table name
     *
     * @param string $alias The table alias
     * @return mixed
     */
    public function t($alias = null);

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
     * Defines all the query possibilities of the Queriable object (a table)
     * - join
     * - where
     * - orderby
     *
     * @return array
     */
    public function defineFilters();

    /**
     * This methods will be defined in the Dao traits
     * It takes the return of defineFilters and put it in a $filters property also
     * defined in the trait.
     *
     * @return void
     */
    public function loadFilters();

    /**
     * This methods will be defined in the Dao traits
     * Get the defined query possibilities of the Queriable object
     * or a subset of it.
     *
     * @param string $key Provide a key to get a subset of the filters
     * @return array
     */
    public function getFilters($key = null);
}