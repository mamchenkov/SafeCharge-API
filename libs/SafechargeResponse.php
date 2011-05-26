<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */

/**
 * Load common includes
 */
require_once dirname(__FILE__) . '/SafechargeCommon.php';

/**
 * SafeCharge Response
 *
 * Successful queries to SafeCharge Gateway will return an
 * instance of this class.
 *
 * @package SafeCharge
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 */
class SafechargeResponse  {

	protected $queryId;

	public function __construct() {
	}

	public function setQueryId($queryId) {
		$this->queryId = $queryId;
	}

	/**
	 * Parse XML response 
	 *
	 * @throws InternalException
	 * @throws ResponseException
	 * @param string $response Response to parse
	 * @return null|object SimpleXMLElement object if parsed, plain text otherwise
	 */
	public function parse($response) {
		$result = null;

		$this->validate($response);

		if (!class_exists('SimpleXMLElement')) {
			throw new InternalException("Insufficient PHP support: missing SimpleXMLElement class");
		}

		try {
			$result = new SimpleXMLElement($response);
		}
		catch (Exception $e) {
			$this->log("$queryId Failed to parse XML response: " . $e->getMessage());
			throw new ResponseException("Failed to parse XML response: " . $e->getMessage());
		}

		return $result;
	}

	/**
	 * Validate gateway response
	 *
	 * @throws InternalException
	 * @throws ResponseException
	 * @param string $response Response to validate
	 * @return void
	 */
	protected function validate($response) {
		if (!function_exists('libxml_use_internal_errors')) {
			throw new InternalException("Insufficient PHP support: missing libxml_use_internal_errors() function");
		}
		libxml_use_internal_errors(true);

		if (!class_exists('DOMDocument')) {
			throw new InternalException("Insufficient PHP support: missing DOMDocument class");
		}
		$doc = new DOMDocument(SafechargeConstants::RESPONSE_XML_VERSION, SafechargeConstants::RESPONSE_XML_ENCODING);
		$doc->loadXML($response);

		if (!function_exists('libxml_get_errors')) {
			throw new InternalException("Insufficient PHP support: missing libxml_get_errors() function");
		}
		$errors = libxml_get_errors();

		if (!empty($errors)) {
			throw new ResponseException("Failed to validate XML response");
		}
	}

}
?>