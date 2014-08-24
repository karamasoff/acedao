<?php
namespace Acedao\Test\Mock;


use Acedao\Brick\Dao;
use Acedao\Queriable;

class Buyer implements Queriable {
    use Dao;

	/**
	 * An array of field to select if nothing is provided in the query
	 *
	 * @return array
	 */
	public function getAllowedFields() {
		return array('firstname', 'lastname');
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
                        '[car].buyer_id = [buyer].id'
                    )
                )

            ),
            'where' => array(
                'id' => array(
                    '[buyer].id = :id'
                )
            ),
            'orderby' => array(
                'name' => array(
                    '[buyer].lastname :dir',
                    '[buyer].firstname :dir'
                )
            )
        );
	}
}