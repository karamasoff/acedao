<?php

namespace Acedao\Test;

use Acedao\Container;
use Acedao\Exception;
use Acedao\Factory;
use Acedao\Test\Mock\Buyer;
use Acedao\Test\Mock\Car;
use Acedao\Test\Mock\Equipment;

class QueryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Container
	 */
	public $container;

	/**
	 * @var \Acedao\Query
	 */
	public $query;

	public function setUp() {

		$container = new Container(array(
            'mode' => 'strict',
            'namespace' => 'Acedao\Test\Mock',
            'tables' => array(
                'Car' => 'car',
                'Equipment' => 'equipment',
                'Car\Equipment' => 'car_equipment',
                'Car\Category' => 'car_category',
                'Buyer' => 'buyer',
                'Order' => 'order'
            )
        ));

		$this->container = $container;
		$this->query = $this->container['query'];

        // initialize some values
		$this->baseQuery();
	}

    public function baseQuery() {
        $config = array(
            'from' => 'car c',
            'join' => array(
                'buyer b' => 'Buyer',
                'car_equipment ce' => array(
                    'name' => 'CarEquipment',
                    'join' => array(
                        'equipment e' => 'Equipment'
                    )
                ),
                'order o' => 'Order',
                'car_category cc' => 'CarCategory'
            )
        );
        $this->query->prepareConfig($config);
        $this->query->prepareSelect();
    }

	/**
	 * @param string $initial
	 * @param array $expected
	 *
	 * @dataProvider providerTestExtractAlias
	 */
	public function testExtractAlias($initial, $expected) {

		$this->assertEquals($expected, $this->query->extractAlias($initial));
	}

	public function providerTestExtractAlias() {
		return array(
			array('table t', array(
				'table' => 'table',
				'alias' => 't'
			)),
			array('table', array(
				'table' => 'table',
				'alias' => 'table'
			))
		);
	}

	/**
	 * @param array $initialConfig
	 * @param array $expected
	 *
	 * @dataProvider providerGetSelectedFieldsProvided
	 */
	public function testGetSelectedFieldsProvided($initialConfig, $expected) {
		$this->assertEquals($expected, $this->query->getSelectedFields($initialConfig));
	}

    public function testGetSelectedFieldsProvidedExceptionMissingDependency() {
        $expected = array('id', 'name');
        $initial_config = array('select' => array('id', 'name'), 'table' => 'blouarp');
        try {
            $this->assertEquals($expected, $this->query->getSelectedFields($initial_config));
        } catch (Exception\MissingDependencyException $e) {
            return;
        } catch (Exception $e) {
            $this->fail("MissingDependencyException should have been raised. Another Exception was raised instead.");
        }

        $this->fail("MissingDependencyException should have been raised.");
    }

	public function providerGetSelectedFieldsProvided() {
		return array(
			array(array('select' => array('id', 'name'), 'table' => 'car'), array('id', 'name')),
			array(array('select' => array('id', 'name'), 'table' => 'buyer'), array('id', 'name')), // pas besoin de la table si on sait les champs qu'on veut...
            array(array('table' => 'car'), array('name', 'brand', 'model', 'price', 'selldate', 'buyer_id')), // pas de select fourni, prend les valeurs par défaut.
            array(array('addselect' => array('color'), 'table' => 'car'), array('name', 'brand', 'model', 'price', 'selldate', 'buyer_id', 'color')), // ajout d'un champ aux champs par défaut
            array(array('addselect' => 'color', 'table' => 'car'), array('name', 'brand', 'model', 'price', 'selldate', 'buyer_id', 'color')), // pareil, mais sans passer un tableau
            array(array('omit' => 'model', 'table' => 'car'), array('name', 'brand', 'price', 'selldate', 'buyer_id')), // retire un champ avec 'omit'
		);
	}

	public function testAliaseSelectedFields() {
		$alias = 'al';
		$fields = array('id', 'name');
		$expected = array('al.id', 'al.name');

		$this->assertEquals($expected, $this->query->aliaseSelectedFields($alias, $fields));
	}

    /**
     * @param $type
     * @param $name
     * @param $expected
     *
     * @dataProvider providerRetrieveFilter
     */
    public function testRetrieveFilter($type, $name, $expected) {
        $queriable = $this->container['Car'];
        $result = $this->query->retrieveFilter($queriable, $type, $name);
        $this->assertEquals($expected, $result);
    }

    public function providerRetrieveFilter() {
        return array(
            array('join', 'car_category', array(
                'on' => array(
                    '[car_category].id = [this].category_id'
                )
            )),
            array('where', 'color', array(
                '[this].color = :color'
            )),
            array('orderby', 'date_release', array(
                '[car].date_release :dir'
            )),
            array('join', 'dummy_table', false)
        );
    }

	/**
	 * @param array $before
	 * @param array $after
	 *
	 * @dataProvider providerFormatQueryParamsKeys
	 */
	public function testFormatQueryParamsKeys($before, $after) {
		$this->assertEquals($after, $this->query->formatQueryParamsKeys($before));
	}

	public function providerFormatQueryParamsKeys() {
		return array(
			array(array(), array()),
			array(array(2, 3, 4), array(':0' => 2, ':1' => 3, ':2' => 4)),
			array(array('id' => 2, ':blu' => 'adjeu'), array(':id' => 2, ':blu' => 'adjeu')),
			array(array('status1' => 'new', 'status2' => 'cancelled'), array(':status1' => 'new', ':status2' => 'cancelled')),
		);
	}

	/**
	 * @param array $before
	 * @param array $after
	 *
	 * @dataProvider providerNameAliasesSelectedFields
	 */
	public function testNameAliasesSelectedFields($before, $after) {
		$this->assertEquals($after, $this->query->nameAliasesSelectedFields($before));
	}

	public function providerNameAliasesSelectedFields() {
		return array(
			array(array(), array()),
			array(array('s.id', 'c.name'), array('s.id as s__id', 'c.name as c__name')),
			array(array('id', 'name'), array('id as id', 'name as name')),
		);
	}

	/**
	 * @param $alias
	 * @param $expected
	 *
	 * @dataProvider providerGetPathAlias
	 */
	public function testGetPathAlias($alias, $expected) {
		$result = $this->query->getPathAlias($alias);

		$this->assertEquals($expected, $result);
	}

	public function providerGetPathAlias() {
		return array(
			array(null, false),
			array('c', false),
			array('e', array('CarEquipment', 'Equipment')),
			array('b', array('Buyer')),
		);
	}

	/**
	 * @param $baseTree
	 * @param $baseRefs
	 * @param $baseTypes
	 * @param $parentAlias
	 * @param $alias
	 * @param $table
	 * @param $localJoins
	 * @param $joinedJoins
	 * @param $relation
	 * @param $expected
	 *
	 * @dataProvider providerRegisterAlias
	 */
	public function testRegisterAlias($baseTree, $baseRefs, $baseTypes, $parentAlias, $alias, $table, $localJoins, $joinedJoins, $relation, $expected) {

		$this->query->setAliasesTree($baseTree);
		$this->query->setAliasesReferences($baseRefs);
		$this->query->setRelationTypes($baseTypes);
		$this->query->registerAlias($parentAlias, $alias, $table, $localJoins, $joinedJoins, $relation);

		$this->assertEquals($expected, $this->query->getAliasesTree());
	}

	public function testRegisterAliasSecondLevel() {
		list($baseTree, $baseRefs) = $this->getSinisterAliasesBaseForRegister();
		$this->query->setAliasesTree($baseTree);
		$this->query->setAliasesReferences($baseRefs);

		$employee_joins = array(
			'person' => array(
				'select' => array('firstname', 'name'),
				'on' => array(
					'[person].id = [employee].person_id'
				)
			),
			'client' => array(
				'on' => array(
					'[client].id = [employee].client_id'
				)
			)
		);

		$person_joins = array();

		$result_person = $baseTree;
		$result_person['e']['children']['p'] = array(
			'table' => 'person',
			'type' => 'one',
			'children' => array()
		);

		$this->query->registerAlias('e', 'p', 'person', $employee_joins, $person_joins, 'person');

		$result = $this->query->getAliasesTree();
		unset($result['e']['children']['p']['parent']); // obligé de virer ce champ car sinon PHPUnit n'arrive pas à comparer car trop de récursion
		$this->assertEquals($result, $result_person);
	}

	public function providerRegisterAlias() {
		$sinister_joins = array(
			'employee' => array(
				'on' => array(
					'[sinister].employee_id = [employee].id'
				)
			),
			'sinister_status' => array(
				'on' => array(
					'[sinister_status].id = [sinister].status_id'
				)
			),
			'sinister_type' => array(
				'on' => array(
					'[sinister_type].id = [sinister].type_id'
				)
			),
			'sinister_note' => array(
				'type' => 'many',
				'on' => array(
					'[sinister].id = [sinister_note].sinister_id'
				)
			)
		);

		$employee_joins = array(
			'person' => array(
				'select' => array('firstname', 'name'),
				'on' => array(
					'[person].id = [employee].person_id'
				)
			),
			'client' => array(
				'on' => array(
					'[client].id = [employee].client_id'
				)
			)
		);

		$sinister_note_joins = array();

		list($base_sinister_tree, $base_sinister_refs, $base_relation_types) = $this->getSinisterAliasesBaseForRegister();

		$result_sinister_note = $base_sinister_tree;
		$result_sinister_note['n'] = array(
			'table' => 'sinister_note',
			'relation' => 'sinister_note',
			'type' => 'many',
			'children' => array(),
			'parent' => null
		);

		return array(
			array($base_sinister_tree, $base_sinister_refs, $base_relation_types, 's', 'e', 'employee', $sinister_joins, $employee_joins, 'employee', $base_sinister_tree), // alias existe déjà
			array($base_sinister_tree, $base_sinister_refs, $base_relation_types, 's', 'n', 'sinister_note', $sinister_joins, $sinister_note_joins, 'sinister_note', $result_sinister_note), // ajout d'un alias
			array($base_sinister_tree, $base_sinister_refs, $base_relation_types, 's', 'y', 'dummy_tabley', $sinister_joins, array(), 'dummy_tabley', $base_sinister_tree), // ajout d'un alias non référencé
			array($base_sinister_tree, $base_sinister_refs, $base_relation_types, 'e', 'z', 'dummy_table', $employee_joins, array(), 'dummy_table', $base_sinister_tree) // alias non référencé, autre source
		);
	}

	private function getSinisterAliasesBaseForRegister() {
		$e = array(
			'table' => 'employee',
			'relation' => 'employee',
			'type' => 'one',
			'children' => array(),
			'parent' => null
		);
		$ss = array(
			'table' => 'sinister_status',
			'relation' => 'sinister_status',
			'type' => 'one',
			'children' => array(),
			'parent' => null
		);
		$base = array(
			'e' => &$e,
			'ss' => &$ss
		);

		$ref = array(
			'e' => &$e,
			'ss' => &$ss
		);

		$relation_types = array(
			'employee' => 'one',
			'sinister_status' => 'one'
		);

		return array($base, $ref, $relation_types);
	}

	/**
	 * @param $sql
	 * @param $options
	 * @param $expected
	 *
	 * @dataProvider providerMapFilterParametersNames
	 */
	public function testMapFilterParametersNames($sql, $options, $expected) {

		$result = $this->query->mapFilterParametersNames($sql, $options);
		$this->assertEquals($expected, $result);
	}

	public function providerMapFilterParametersNames() {
		return array(
			array(
				"p.id = :id",
				array(22),
				array(':id' => 22)
			), // cas normal...
			array(
				"ss.machine_name != 'over'",
				array(true),
				array()
			), // paramètres fournis alors que pas besoin...
			array(
				"p.id = :id",
				array(22, 23, 24),
				array(':id' => 22)
			), // si trop de paramètres sont fournis, on prend juste ceux dont on a besoin...
            array(
                'p.id = :id',
                22,
                array(':id' => 22)
            ), // $options n'est pas un tableau
			array(
				'p.enabled = :enabled',
				true,
				array(':enabled' => true)
			), // $options est un booléen
			array(
				"s.date BETWEEN :start AND :end",
				array(':end' => '2013-12-31', ':start' => '2013-12-01'),
				array(':start' => '2013-12-01', ':end' => '2013-12-31')
			), // bons paramètres fournis dans le mauvais ordre
            array(
                "s.date BETWEEN :start AND :end",
                array(':rav' => 'bliblablouu', ':end' => '2013-12-31', ':start' => '2013-12-01'),
                array(':start' => '2013-12-01', ':end' => '2013-12-31')
            ), // bon paramètres fournis, mais avec d'autres mauvais
            array(
                "s.date BETWEEN :start AND :zend",
                array(':rav' => 'bliblablouu', ':zend' => '2013-12-31', ':start' => '2013-12-01'),
                array(':start' => '2013-12-01', ':zend' => '2013-12-31')
            ), // bon paramètres fournis, mais avec d'autres mauvais ET un ordre qui pourrait poser problème
            array(
                "[o.status NOT IN (:status1, :status2)",
                array('status1' => 'new', 'status2' => 'cancelled'),
                array(':status1' => 'new', ':status2' => 'cancelled')
            ), // paramètres fournis sans les ":" avant.
		);
	}

    public function testMapFilterParametersNamesExceptionNotEnough() {

        try {
            $sql = 's.date BETWEEN :start AND :end';
            $options = array('2013-12-31');
            $this->query->mapFilterParametersNames($sql, $options);
        } catch (Exception\WrongParameterException $e) {
            return;
        }

        $this->fail("WrongParameterException should have been raised.");
    }

    public function testMapFilterParametersNamesExceptionBadKeysMatching() {

        try {
            $sql = 's.date BETWEEN :start AND :end';
            $options = array(':start' => '2013-12-31', ':fin' => '2014-01-21');
            $this->query->mapFilterParametersNames($sql, $options);
        } catch (Exception\MissingKeyException $e) {
            return;
        }

        $this->fail("MissingKeyException should have been raised.");
    }

    public function testGetSortDirection() {
        $options = array('name' => 'asc');
        $expected = 'asc';
        $result = $this->query->getSortDirection($options);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $filtername
     * @param $expected
     *
     * @dataProvider providerExtractFilterAliasAndTable
     */
    public function testExtractFilterAliasAndTable($filtername, $expected) {
        $data = array(
            'base' => array(
                'alias' => 'c',
                'table' => 'car'
            )
        );

        $result = $this->query->extractFilterAliasAndTable($data, $filtername);
        $this->assertEquals($result, $expected);
    }

    public function testExtractFilterAliasAndTableExceptionAliasNotFound() {
        $data = array(
            'base' => array(
                'alias' => 'c',
                'table' => 'car'
            )
        );
        $filtername = 'blu.enabled'; // blu is not registered...
        try {
            $this->query->extractFilterAliasAndTable($data, $filtername);
        } catch (Exception $e) {
            return;
        }

        $this->fail('An Acedao\Exception was not raised.');
    }

    public function providerExtractFilterAliasAndTable() {
        return array(
            array('enabled', array('enabled', 'car', 'c')),
            array('c.color', array('color', 'car', 'c')),
            array('e.enabled', array('enabled', 'equipment', 'e')),
            array('e.fakefilter', array('fakefilter', 'equipment', 'e'))
        );
    }

    public function testEscapeTableNameInFromClause() {
        $this->query->prepareConfig(array(
            'from' => 'order o'
        ));
        list($parts, $params) = $this->query->prepareSelect();

        $this->assertEquals($parts['from'], "`order` o");
    }

    public function testEscapeTableNameInJoinClause() {
        $this->query->prepareConfig(array(
            'from' => 'car c',
            'join' => array(
                'order o' => array('name' => 'Commandes')
            )
        ));
        list($parts, $params) = $this->query->prepareSelect();

        $this->assertEquals($parts['leftjoin'][0], "LEFT JOIN `order` o ON c.id = o.car_id");
    }

    /**
     * @param $data
     * @param $filtername
     * @param $options
     * @param $connector
     * @param $expectedString
     *
     * @dataProvider providerTestGettingConditionsString
     */
    public function testGettingConditionStringFromFilter($data, $filtername, $options, $connector, $expectedString) {
        $string = $this->query->getConditionString($data, $filtername, $options, $connector);
        $this->assertEquals($string, $expectedString);
    }

    public function providerTestGettingConditionsString() {
        return array(
            array(
                array(
                    'base' => array(
                        'alias' => 'c',
                        'table' => 'car'
                    )
                ),
                'e.price_between',
                array(100, 1000),
                'and',
                'e.price >= :from and e.price <= :to'
            ),
            array(
                array(
                    'base' => array(
                        'alias' => 'c',
                        'table' => 'car'
                    )
                ),
                'or',
                array(
                    'e.price_between' => array(100, 1000),
                    'e.enabled' => true
                ),
                'and',
                '(e.price >= :from AND e.price <= :to OR e.enabled = true)'
            ),
            array(
                array(
                    'base' => array(
                        'alias' => 'c',
                        'table' => 'car'
                    )
                ),
                'c.colorIn',
                array('red', 'blue'),
                'and',
                'c.color IN (:gen_param_in0,:gen_param_in1)'
            )
        );
    }

    public function providerTestFullQuery() {
        return array(
            array(
                array(
                    'select' => array('name'),
                    'from' => 'car c',
                    'limit' => 5 // test de la clause 'limit'
                ),
                'SELECT c.name as c__name FROM car c LIMIT 5'
            ),
            array(
                array(
                    'select' => array('name'),
                    'from' => 'car c',
                    'limit' => array(30, 5) // test de la clause 'limit'
                ),
                'SELECT c.name as c__name FROM car c LIMIT 30,5'
            )
        );
    }

    /**
     * @param $config
     * @param $expectedString
     *
     * @dataProvider providerTestFullQuery
     */
    public function testFullQuery($config, $expectedString) {
        $this->query->prepareConfig($config);
        list($parts) = $this->query->prepareSelect();

        // construction de la requête SQL
        $sql = $this->query->prepareSelectSql($parts, $config);
        $this->assertEquals($sql, $expectedString);
    }

    public function testLimitBadFormatException() {
        $config = array(
            'select' => array('name'),
            'from' => 'car c',
            'limit' => array(30, 5, 3) // test de la clause 'limit'
        );
        try {
            $this->query->prepareConfig($config);
            list($parts) = $this->query->prepareSelect();

            // construction de la requête SQL
            $this->query->prepareSelectSql($parts, $config);
        } catch (Exception\WrongParameterException $e) {
            return;
        }

        $this->fail("WrongParameterException should have been raised.");
    }



/** ========== setting up the environment =================================== */



/** ======== / setting up the environment =================================== */
}