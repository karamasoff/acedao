<?php

namespace Acedao;


interface Queriable {

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getDefaultFields();

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