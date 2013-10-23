<?php
namespace Acedao;


interface Validable {

	/**
	 * An array of fields that have to be set when inserting or updating
	 * @return array
	 */
	public function getMandatoryFields();

	/**
	 * An array that defines the fields that have to match a certain format
	 * i.e. email, credit card number, alpha only, etc.
	 * @return array
	 */
	public function getFormattedFields();

	/**
	 * A list of fields that will be computed by the query.
	 * @return array
	 */
	public function getAllowedFields();

	/**
	 * This method will validate the data provided against the mandatory fields
	 * defined in the getMandatoryFields(), getFormattedFields() and getAllowedFields() methods.
	 *
	 * @param array $userProvidedData
	 * @return bool
	 */
	public function validate(array $userProvidedData);
}