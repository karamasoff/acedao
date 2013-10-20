<?php

namespace Acedao\Test;


use Acedao\Container;

class QueryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Container
	 */
	public $container;

	/**
	 * @var Query
	 */
	public $query;

	public function setUp() {

		$container = new Container(array('mode' => 'strict'));

		$sinister = $this->getMockBuilder('\Acedao\Test\Sinister')
			->getMock();
		$sinister->expects($this->any())
			->method('getDefaultFields')
			->will($this->returnValue(array('field1', 'field2')));

		$container['sinister'] = function() use ($sinister) {
			return $sinister;
		};

		$this->container = $container;
		$this->query = $this->container['query'];
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
			'notselect' => 'blup',
			'table' => 'sinister'
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

	public function providerGetSelectedFieldsProvided() {
		return array(
			array(array('select' => array('id', 'name'), 'table' => 'sinister'), array('id', 'name')),
			array(array('select' => array('id', 'name')), array('id', 'name')), // pas besoin de la table si on sait les champs qu'on veut...
		);
	}

	public function testAliaseSelectedFields() {
		$alias = 'al';
		$fields = array('id', 'name');
		$expected = array('al.id', 'al.name');

		$this->assertEquals($expected, $this->query->aliaseSelectedFields($alias, $fields));
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
		$reference = $this->getAliasesBaseForSearch();
		$result = $this->query->getPathAlias($reference, $alias);

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
	 * @param $base
	 * @param $parentAlias
	 * @param $alias
	 * @param $table
	 * @param $localJoins
	 * @param $joinedJoins
	 * @param $expected
	 *
	 * @dataProvider providerRegisterAlias
	 */
	public function testRegisterAlias($base, $parentAlias, $alias, $table, $localJoins, $joinedJoins, $expected) {

		$this->query->registerAlias($base, $parentAlias, $alias, $table, $localJoins, $joinedJoins);

		$this->assertEquals($expected, $base);
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

		$person_joins = array();
		$sinister_note_joins = array();

		$base_sinister = $this->getSinisterAliasesBaseForRegister();
		$base_employee = $this->getEmployeeAliasesBaseForRegister();

		$result_sinister_note = $base_sinister;
		$result_sinister_note['n'] = array(
			'table' => 'sinister_note',
			'type' => 'many',
			'children' => array()
		);

		$result_person = $base_sinister;
		$result_person['e']['children']['p'] = array(
			'table' => 'person',
			'type' => 'one',
			'children' => array()
		);

		return array(
			array($base_sinister, 's', 'e', 'employee', $sinister_joins, $employee_joins, $base_sinister), // alias existe déjà
			array($base_sinister, 's', 'n', 'sinister_note', $sinister_joins, $sinister_note_joins, $result_sinister_note), // ajout d'un alias
			array($base_sinister, 's', 'y', 'dummy_tabley', $sinister_joins, array(), $base_sinister), // ajout d'un alias non référencé
			array($base_sinister, 'e', 'p', 'person', $employee_joins, $person_joins, $result_person), // ajout d'un alias au niveau d'en-dessous
			array($base_sinister, 'e', 'z', 'dummy_table', $employee_joins, array(), $base_sinister) // alias non référencé, autre source
		);
	}

	private function getAliasesBaseForSearch() {
		return array(
			'e' => array(
				'table' => 'employee',
				'type' => 'one',
				'children' => array(
					'c' => array(
						'table' => 'client',
						'type' => 'one',
						'children' => array()
					),
					'p' => array(
						'table' => 'person',
						'type' => 'one',
						'children' => array()
					)
				)
			),
			'ss' => array(
				'table' => 'sinister_status',
				'type' => 'one',
				'children' => array()
			)
		);
	}

	private function getSinisterAliasesBaseForRegister() {
		return array(
			'e' => array(
				'table' => 'employee',
				'type' => 'one',
				'children' => array()
			),
			'ss' => array(
				'table' => 'sinister_status',
				'type' => 'one',
				'children' => array()
			)
		);
	}

	private function getEmployeeAliasesBaseForRegister() {
		return array(
			'c' => array(
				'table' => 'client',
				'type' => 'one',
				'children' => array()
			),
			'p' => array(
				'table' => 'person',
				'type' => 'one',
				'children' => array()
			)
		);
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
				array(true)
			), // paramètres fournis alors que pas besoin... on retourne le tableau tel quel.
			array(
				"p.id = :id",
				array(22, 23, 24),
				array(':id' => 22)
			), // si trop de paramètres sont fournis, on prend juste ceux dont on a besoin...
			array(
				"s.date BETWEEN :start AND :end",
				array('2013-12-01'),
				false
			), // pas assez de paramètres fournis pour remplir les params de la query
			array(
				"s.date BETWEEN :start AND :end",
				array(':end' => '2013-12-31', ':start' => '2013-12-01'),
				array(':start' => '2013-12-31', ':end' => '2013-12-01')
			) // ce cas de figure n'arrivera pas, car on passe uniquement des tableaux sans clés explicites à cette méthode.
		);
	}
}