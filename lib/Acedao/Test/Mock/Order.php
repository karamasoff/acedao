<?php
namespace Acedao\Test\Mock;


use Acedao\Brick\Dao;
use Acedao\Queriable;

class Order implements Queriable {
    use Dao;

    public function construct() {
        $this->escapeTablename = true;
    }

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getAllowedFields() {
		return array('date', 'amount');
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
                        '[car].id = [this].car_id'
                    )
                )
            ),
            'where' => array(
                'id' => array(
                    '[this].id = :id'
                )
            ),
            'orderby' => array(
                'date' => array(
                    '[this].date :dir'
                )
            )
        );
	}
}