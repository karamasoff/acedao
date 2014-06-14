<?php
namespace Acedao\Test\Mock;

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
		return array();
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
                'car_equipment' => array(
                    'on' => array(
                        '[car_equipment].equipment_id = [equipment].id'
                    )
                )
            ),
            'where' => array(
                'enabled' => array(
                    '[equipment].enabled = true'
                ),
                'price_between' => array(
                    '[equipment].price >= :from',
                    '[equipment].price <= :to'
                )
            ),
            'orderby' => array(
                'price' => array(
                    '[equipment].price :dir'
                )
            )
        );
	}
}