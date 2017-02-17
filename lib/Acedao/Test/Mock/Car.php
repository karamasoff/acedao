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
	public function getAllowedFields() {
		return array('name', 'brand', 'model', 'price', 'selldate', 'buyer_id');
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
                'buyer' => array(
                    'on' => array(
                        '[this].buyer_id = [buyer].id'
                    )
                ),
                'car_category' => array(
                    'on' => array(
                        '[car_category].id = [this].category_id'
                    )
                ),
                'car_equipment' => array(
                    'type' => 'many',
                    'on' => array(
                        '[this].id = [car_equipment].car_id'
                    )
                ),
                'order' => array(
                    'on' => array(
                        '[this].id = [order].car_id'
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
                    '[this].color = :color'
                ),
                'colorIn' => array(
                    '[this].color IN (:in)'
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