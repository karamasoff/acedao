<?php
namespace Acedao\Test\Mock;

use Acedao\Brick\Dao;
use Acedao\Queriable;

class Car implements Queriable {
    use Dao;

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getDefaultFields() {
		return array('name', 'brand', 'model', 'price', 'selldate', 'color');
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
                'car_category' => array(
                    'on' => array(
                        '[car_category].id = [car].category_id'
                    )
                ),
                'car_equipment' => array(
                    'on' => array(
                        '[car].id = [car_equipment].car_id'
                    )
                )

            ),
            'where' => array(
                'id' => array(
                    '[car].id = :id'
                ),
                'category_id' => array(
                    '[car].category_id = :categoryId'
                ),
                'color' => array(
                    '[car].color = :color'
                )
            ),
            'orderby' => array(
                'date_release' => array(
                    '[car].date_release :dir'
                )
            )
        );
	}
}