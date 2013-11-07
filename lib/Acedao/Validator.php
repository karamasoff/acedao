<?php
namespace Acedao;


trait Validator {

	/**
	 * An array of fields that have to be set when inserting or updating
	 * @return array
	 */
	abstract public function getMandatoryFields();

	/**
	 * An array that defines the fields that have to match a certain format
	 * i.e. email, credit card number, alpha only, etc.
	 * @return array
	 */
	abstract public function getFormattedFields();

	/**
	 * A list of fields that will be computed by the query.
	 * @return array
	 */
	abstract public function getAllowedFields();

	/**
	 * This method will validate the data provided against fields
	 * defined in the getMandatoryFields(), getFormattedFields() and getAllowedFields() methods.
	 *
	 * @param array $userProvidedData
	 * @return bool
	 */
	public function validate(array $userProvidedData) {
		$results = array(
			'success' => true
		);

        // allowed fields
        $allowed_fields = $this->getAllowedFields();
        if (count($allowed_fields) > 0) {
            $diff = array_diff(array_keys($userProvidedData), $allowed_fields);
            if (count($diff) > 0) {
                $results['success'] = false;
                $results['message'][] = "Unknown fields.";
                $results['featuring'] = $diff;
            }
        }

        // mandatory fields
        $mandatory_fields = $this->getMandatoryFields();
        if (count($mandatory_fields) > 0) {
            $diff = array_diff($mandatory_fields, array_keys($userProvidedData));
            if (count($diff) > 0) {
                $results['success'] = false;
                $results['message'][] = "Missing mandatory fields.";
                $results['missing'] = $diff;
            }
        }

		// formatted fields


		return $results;
	}
}