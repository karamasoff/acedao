<?php
namespace Acedao;


interface Validable {

	/**
	 * An array of fields that have to be set when inserting or updating
	 * @return array
	 */
	public function getMandatoryFields();

	/**
	 * This method will validate the data provided against the mandatory fields
	 * defined in the getMandatoryFields() method.
	 *
	 * @param array $userProvidedData
	 * @return bool
	 */
	public function validate(array $userProvidedData);
}