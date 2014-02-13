<?php
namespace Acedao\Test\Mock\Car;

use Acedao\Dao;
use Acedao\Queriable;

class Category implements Queriable {
    use Dao;

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getDefaultFields() {
		return array('name', 'description', 'enabled');
	}

	/**
	 * Defines all the query possibilities of the Queriable object (a table)
	 * - join
	 * - where
	 * - orderby
	 *
	 * @return array
	 */
	public function defineFilters() {
		return array(
            'join' => array(
                'car' => array(
                    'on' => array(
                        '[car].category_id = [car_category].id'
                    )
                )
            ),
            'where' => array(
                'enabled' => array(
                    '[car_category].enabled = 1'
                )
            ),
            'orderby' => array(
                'name' => array(
                    '[car_category].name :dir'
                )
            )
        );
	}
}