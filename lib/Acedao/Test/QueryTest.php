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
		$this->initAliasesBaseForSearch();
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


	public function testGetSelectedFieldsDefaultContainsId() {
		$this->assertContains('id', $this->query->getSelectedFields(array(
			'table' => 'car' // don't pass the 'select' key...
		)));
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


    public function testGetSelectedFieldsProvidedExceptionMissingKey() {
        $expected = array('id', 'name');
        $initial_config = array('select' => array('id', 'name'));
        try {
            $this->assertEquals($expected, $this->query->getSelectedFields($initial_config));
        } catch (Exception\MissingKeyException $e) {
            return;
        } catch (Exception $e) {
            $this->fail("MissingKeyException should have been raised. Another Exception was raised instead.");
        }

        $this->fail("MissingKeyException should have been raised.");
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
                    '[car_category].id = [car].category_id'
                )
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
			array('s', false),
			array('e', array('employee')),
			array('p', array('employee', 'person')),
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

    public function providerGetSortDirection() {

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
                'alias' => 's',
                'table' => 'sinister'
            )
        );

        $result = $this->query->extractFilterAliasAndTable($data, $filtername);
        $this->assertEquals($result, $expected);
    }

    public function testExtractFilterAliasAndTableExceptionAliasNotFound() {
        $data = array(
            'base' => array(
                'alias' => 's',
                'table' => 'sinister'
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
            array('enabled', array('enabled', 'sinister', 's')),
            array('s.enabled', array('enabled', 'sinister', 's')),
            array('e.enabled', array('enabled', 'employee', 'e'))
        );
    }

    public function testEscapeTableNameInFromClause() {
        list($parts, $params) = $this->query->prepareSelect(array(
            'from' => 'order o'
        ));

        $this->assertEquals($parts['from'][0], "`order` o");
    }

    public function testEscapeTableNameInJoinClause() {
        list($parts, $params) = $this->query->prepareSelect(array(
            'from' => 'car c',
            'join' => array(
                'order o' => array('name' => 'Commandes')
            )
        ));

        $this->assertEquals($parts['leftjoin'][0], "LEFT JOIN `order` o ON c.id = o.car_id");
    }




/** ========== setting up the environment =================================== */

    private function initAliasesBaseForSearch() {
        $c = array(
            'table' => 'client',
            'relation' => 'client',
            'type' => 'one',
            'children' => array()
        );
        $p = array(
            'table' => 'person',
            'relation' => 'person',
            'type' => 'one',
            'children' => array()
        );
        $e = array(
            'table' => 'employee',
            'relation' => 'employee',
            'type' => 'one',
            'children' => array(
                'c' => &$c,
                'p' => &$p
            )
        );
        $c['parent'] = &$e;
        $p['parent'] = &$e;

        $ss = array(
            'table' => 'sinister_status',
            'relation' => 'sinister_status',
            'type' => 'one',
            'children' => array()
        );
        $e['parent'] = null;
        $ss['parent'] = null;

        $base = array(
            'e' => &$e,
            'ss' => &$ss
        );
        $reference = array(
            'c' => &$c,
            'p' => &$p,
            'e' => &$e,
            'ss' => &$ss
        );

        $base_types = array(
            'client' => 'one',
            'person' => 'one',
            'employee' => 'one',
            'sinister_status' => 'one'
        );

        $this->query->setAliasesTree($base);
        $this->query->setAliasesReferences($reference);
        $this->query->setRelationTypes($base_types);
    }

/** ======== / setting up the environment =================================== */
}