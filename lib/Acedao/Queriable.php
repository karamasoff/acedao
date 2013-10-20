<?php

namespace acedao;


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
	 * @param string $key Provide a key to get a subset of the filters
	 * @return array
	 */
	public function getFilters($key = null);
}