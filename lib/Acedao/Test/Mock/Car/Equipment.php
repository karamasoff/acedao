<?php
namespace Acedao\Test\Mock\Car;

use Acedao\Brick\Dao;
use Acedao\Queriable;

class Equipment implements Queriable {
    use Dao;

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getDefaultFields() {
		return array('name', 'description', 'price', 'enabled');
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
                        '[car].id = [car_equipment].car_id'
                    )
                ),
                'equipment' => array(
                    'on' => array(
                        '[equipment].id = [car_equipment].equipment_id'
                    )
                )
            ),
            'where' => array(),
            'orderby' => array()
        );
	}
}