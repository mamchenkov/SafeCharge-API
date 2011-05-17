<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */

/**
 * Include Net_Curl
 */
require_once 'Net/Curl.php';

require_once dirname(__FILE__) . '/libs/CardNumberException.php';
require_once dirname(__FILE__) . '/libs/InternalException.php';
require_once dirname(__FILE__) . '/libs/NetworkException.php';
require_once dirname(__FILE__) . '/libs/ValidationException.php';


/**
 * SafeCharge Gateway API
 *
 * @package SafeCharge
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 * @link http://www.safecharge.com
 */
class SafeCharge {

	const QUERY_AUTH   = 'Auth';
	const QUERY_SETTLE = 'Settle';
	const QUERY_SALE   = 'Sale';
	const QUERY_CREDIT = 'Credit';
	const QUERY_VOID   = 'Void';
	const QUERY_AVS    = 'AVSOnly';

	const STATUS_APPROVED = 'APPROVED';
	const STATUS_SUCCESS  = 'SUCCESS';
	const STATUS_DECLINED = 'DECLINED';
	const STATUS_ERROR    = 'ERROR';
	const STATUS_PENDING  = 'PENDING';

	const SERVER_LIVE = 'https://process.safecharge.com/service.asmx/Process?';
	const SERVER_TEST = 'https://test.safecharge.com/service.asmx/Process?';

	/**
	 * Minimum length of a credit card number
	 */
	const MIN_LENGTH = 13;

	/**
	 * Maximum length of a credit card number
	 *
	 * @link http://www.merriampark.com/anatomycc.htm
	 */
	const MAX_LENGTH = 19;

	/**
	 * List of all supported transaction types
	 */
	protected $transactionTypes = array(
			self::QUERY_AUTH,
			self::QUERY_SETTLE,
			self::QUERY_SALE,
			self::QUERY_CREDIT,
			self::QUERY_VOID,
			self::QUERY_AVS,
		);

	/**
	 * Settings
	 */
	protected $settings = array();

	/**
	 * Transaction fields
	 *
	 * See Page 22 Appendix I: Input Parameter Tables from
	 * SafeCharge GateWay Direct Integration Guide
	 */
	protected $transactionFields = array(
			// Customer Details
			array('name' => 'sg_FirstName',      'type' => 'string',  'size' => 30,  'required' => true),
			array('name' => 'sg_LastName',       'type' => 'string',  'size' => 40,  'required' => true),
			array('name' => 'sg_Address',        'type' => 'string',  'size' => 60,  'required' => true),
			array('name' => 'sg_City',           'type' => 'string',  'size' => 30,  'required' => true),
			array('name' => 'sg_State',          'type' => 'string',  'size' => 30,  'required' => true),
			array('name' => 'sg_Zip',            'type' => 'string',  'size' => 10,  'required' => true),
			array('name' => 'sg_Country',        'type' => 'string',  'size' => 3,   'required' => true),
			array('name' => 'sg_Phone',          'type' => 'string',  'size' => 18,  'required' => true),
			array('name' => 'sg_IPAddress',      'type' => 'string',  'size' => 15,  'required' => true),
			array('name' => 'sg_Email',          'type' => 'string',  'size' => 100, 'required' => true),
			array('name' => 'sg_Ship_Country',   'type' => 'string',  'size' => 2,   'required' => false),
			array('name' => 'sg_Ship_State',     'type' => 'string',  'size' => 2,   'required' => false),
			array('name' => 'sg_Ship_City',      'type' => 'string',  'size' => 30,  'required' => false),
			array('name' => 'sg_Ship_Address',   'type' => 'string',  'size' => 60,  'required' => false),
			array('name' => 'sg_Ship_Zip',       'type' => 'string',  'size' => 10,  'required' => false),
			// Transaction Details
			array('name' => 'sg_Is3dTrans',      'type' => 'numeric', 'size' => 1,   'required' => true),
			array('name' => 'sg_TransType',      'type' => 'string',  'size' => 20,  'required' => true),
			array('name' => 'sg_Currency',       'type' => 'string',  'size' => 3,   'required' => true),
			array('name' => 'sg_Amount',         'type' => 'string',  'size' => 10,  'required' => true),
			array('name' => 'sg_AuthCode',       'type' => 'string',  'size' => 10,  'required' => array(self::QUERY_SETTLE, self::QUERY_CREDIT, self::QUERY_VOID)),
			array('name' => 'sg_ClientLoginID',  'type' => 'string',  'size' => 24,  'required' => true),
			array('name' => 'sg_ClientPassword', 'type' => 'string',  'size' => 24,  'required' => true),
			array('name' => 'sg_ClientUniqueID', 'type' => 'string',  'size' => 64,  'required' => false),
			array('name' => 'sg_TransactionID',  'type' => 'int',     'size' => 32,  'required' => array(self::QUERY_SETTLE, self::QUERY_CREDIT, self::QUERY_VOID)),
			array('name' => 'sg_AVS_Approves',   'type' => 'string',  'size' => 10,  'required' => false),
			array('name' => 'sg_CustomData',     'type' => 'string',  'size' => 255, 'required' => false),
			array('name' => 'sg_UserID',         'type' => 'string',  'size' => 50,  'required' => false),
			array('name' => 'sg_CreditType',     'type' => 'int',     'size' => 1,   'required' => array(self::QUERY_CREDIT)),
			array('name' => 'sg_WebSite',        'type' => 'string',  'size' => 50,  'required' => false),
			array('name' => 'sg_ProductID',      'type' => 'string',  'size' => 50,  'required' => false),
			array('name' => 'sg_ResponseFormat', 'type' => 'numeric', 'size' => 1,   'required' => true),
			array('name' => 'sg_Rebill',         'type' => 'string',  'size' => 10,  'required' => false),
			array('name' => 'sg_ResponseURL',    'type' => 'string',  'size' => 256, 'required' => false),
			array('name' => 'sg_TemplateID',     'type' => 'string',  'size' => 10,  'required' => false),
			array('name' => 'sg_VIPCardHolder',  'type' => 'int',     'size' => 1,   'required' => false),
			// Credit / Debit Card Details
			array('name' => 'sg_NameOnCard',     'type' => 'string',  'size' => 70,  'required' => true),
			array('name' => 'sg_CardNumber',     'type' => 'string',  'size' => 20,  'required' => true),
			array('name' => 'sg_ExpMonth',       'type' => 'string',  'size' => 2,   'required' => true),
			array('name' => 'sg_ExpYear',        'type' => 'string',  'size' => 2,   'required' => true),
			array('name' => 'sg_CVV2',           'type' => 'numeric', 'size' => 4,   'required' => true),
			array('name' => 'sg_DC_Issue',       'type' => 'numeric', 'size' => 2,   'required' => false),
			array('name' => 'sg_DC_StartMon',    'type' => 'string',  'size' => 2,   'required' => false),
			array('name' => 'sg_DC_StartYear',   'type' => 'string',  'size' => 2,   'required' => false),
			array('name' => 'sg_IssuingBankName','type' => 'string',  'size' => 255, 'required' => false),
		);

	/**
	 * Constructor
	 *
	 * Supported settings are:
	 * - username   -  SafeCharge API username. Required for any queries to the API
	 * - password   -  SafeCharge API password. Required for any queries to the API
	 * - timeout    -  Network operations timeout (in seconds)
	 * - live       -  Set to true for Live server, otherwise Test server will be used
	 * - log        -  Full path to the log file, if logging is necessary
	 * - instanceId - Some ID of this object intance to keep related queries together
	 *
	 * @param array $settings Settings
	 */
	public function __construct($settings = array()) {

		$defaultSettings = array(
				'username' => '',
				'password' => '',
				'timeout' => 30,
				'live' => false,
				'log' => '',
				'padFrom' => 6,
				'padTo' => 4,
				'padWith' => 'x',
				'instanceId' => rand(1,100000),
			);
		$this->settings = array_merge($defaultSettings, $settings);

		$this->log("Initialized");
	}

	/**
	 * Send request
	 *
	 * @param string $type Type of query (Auth, Settle, etc)
	 * @param array $params Query params
	 * @return null|string|object Null on failure, string or SimpleXMLElement on success
	 */
	public function doQuery($type, $params) {
		$result = null;

		$queryId = '[QUERY ' . (string) rand(1, 100000) . ']';
		$this->log("$queryId Starting new [$type] query");

		try {
			$params['sg_TransType'] = $type;

			$this->log("$queryId Populating query parameters");
			$params = $this->doQueryPopulate($params);

			$this->log("$queryId Checking query parameters");
			$this->doQueryCheck($params);

			$this->log("$queryId Building query string");
			$queryServer = $this->settings['live'] ? self::SERVER_LIVE : self::SERVER_TEST;
			$queryUrl = $queryServer . $this->doQueryBuild($params);
			$logQueryUrl = $queryServer . $this->doQueryBuild($params, true);
			$this->log("$queryId Query URL: $logQueryUrl");

			$response = $this->doQuerySend($queryUrl, $queryId);
			$result = $this->doQueryParse($response, $queryId);
		}
		catch (NetworkException $e) {
			$this->log("$queryId Caught Network Exception: " . $e->getMessage());
			throw new Exception("Gateway communications error. Please try again later.");
		}
		catch (InternalException $e) {
			$this->log("$queryId Caught Internal Exception: " . $e->getMessage());
			throw new Exception("Internal server error. Please try again later.");
		}
		catch (ValidationException $e) {
			$this->log("$queryId Caught Validation Exception: " . $e->getMessage());
			throw new Exception("Validation error: " . $e->getMessage() . ".  Please correct your data and try again.");
		}
		catch (CardNumberException $e) {
			$this->log("$queryId Caught Card Number Exception: " . $e->getMessage());
			throw new Exception("Credit card number is invalid. Please correct and try again.");
		}
		catch (Exception $e) {
			$this->log("$queryId Caught Exception: " . $e->getMessage());
			throw new Exception("Internal server error. Please try again later");
		}

		$this->log("$queryId Result: " . print_r($result, true));

		return $result;
	}

	/**
	 * Send query to SafeCharge API
	 *
	 * @param string $queryUrl Query to send
	 * @param string $queryId ID of the query (for logging purposes)
	 * @return string
	 */
	protected function doQuerySend($queryUrl, $queryId = null) {
		$result = null;

		$curl = new Net_Curl($queryUrl);
		if (!is_object($curl)) {
			$this->log("$queryId Failed to initialize Net_Curl");
			throw new NetworkException("Failed to initialize Net_Curl");
		}

		// Curl settings
		$curl->timeout = $this->settings['timeout'];

		$curl_result = $curl->create();
		if (PEAR::isError($curl_result)) {
			$this->log("$queryId Failed to create Curl request");
			throw new NetworkException("Failed to create Curl request");
		}

		// Fetch
		$result = trim($curl->execute());
		if (PEAR::isError($result)) {
			$this->log("$queryId Failed to execute Curl request");
			throw new NetworkException("Failed to execute Curl request");
		}

		return $result;
	}

	/**
	 * Parse response as XML
	 *
	 * @param string $response Response to parse
	 * @param string $queryId ID of the query (for logging purposes)
	 * @return string|object SimpleXMLElement object if parsed, plain text otherwise
	 */
	protected function doQueryParse($response, $queryId = null) {
		$result = '';

		if (!empty($response) && preg_match('#<#', $response)) {
			$this->log("$queryId Parsing XML response");
			$result = new SimpleXMLElement($response);
		}
		else {
			$result = $response;
		}

		return $result;
	}

	/**
	 * Populate query with default parameters
	 *
	 * @return array
	 */
	protected function doQueryPopulate($params) {
		$result = array();

		$defaultParams = array(
				'sg_ClientLoginID' => $this->settings['username'],
				'sg_ClientPassword' => $this->settings['password'],
				'sg_IPAddress' => '127.0.0.1',
				'sg_ResponseFormat' => 4,
				'sg_ClientUniqueID' => (string) time(),
			);
		$result = array_merge($defaultParams, $params);

		return $result;
	}

	/**
	 * Wrapper for http_build_query()
	 *
	 * We need this to avoid sensitive details like login credentials
	 * and card numbers falling through into log files.
	 *
	 * @param array $params Query parameters
	 * @param boolean $safe Build a safe query or full query
	 * @return string
	 */
	protected function doQueryBuild($params, $safe = false) {
		$result = '';

		if ($safe) {
			// Replace completely
			if (!empty($params['sg_ClientPassword'])) {
				$params['sg_ClientPassword'] = $this->padString($params['sg_ClientPassword'], 0, 0);
			}
			if (!empty($params['sg_CVV2'])) {
				$params['sg_CVV2'] = $this->padString($params['sg_CVV2'], 0, 0);
			}

			// Replace partially
			if (!empty($params['sg_CardNumber'])) {
				$params['sg_ClientPassword'] = $this->padString($params['sg_CardNumber']);
			}
		}
		$result = http_build_query($params);

		return $result;
	}

	/**
	 * Check query parameters
	 *
	 * This is a wrapper method for all sorts of checks
	 *
	 * @param array $params Query parameters
	 */
	protected function doQueryCheck($params) {
		$this->doQueryCheckType($params);
		$this->doQueryCheckFields($params);
		$this->doQueryCheckNoExtra($params);
		$this->doQueryCheckCardNumber($params);
	}

	/**
	 * Check that query parameters have correct transaction type set
	 *
	 * @param array $params Query params
	 */
	protected function doQueryCheckType($params) {
		// Type not empty
		if (empty($params['sg_TransType'])) {
			throw new InternalException("Transaction type required, but not specified");
		}
		// Type is allowed
		if (!in_array($params['sg_TransType'], $this->transactionTypes)) {
			throw new InternalException("Transaction type [" . $params['sg_TransType'] . "] is not supported");
		}
	}

	/**
	 * Check that query parameters are within field specs
	 *
	 * - Check that all required fields are present
	 * - Check that all fields are of the correct type
	 * - Check that all fields have a value that is within size limits
	 *
	 * @param array $params Query parameters
	 */
	protected function doQueryCheckFields($params) {
		$transactionType = $params['sg_TransType'];
		// Check for all required fields and formats
		foreach ($this->transactionFields as $field) {
			$isRequired = false;

			// Field is required for current transaction type
			if (is_array($field['required']) && in_array($transactionType, $field['required'])) {
				$isRequired = true;
			}

			// Field is required for all transaction types
			elseif (is_bool($field['required']) && ($field['required'] === true)) {
				$isRequired = true;
			}

			// Check that all required parameters are present
			if (($isRequired) && (!in_array($field['name'], array_keys($params)))) {
				throw new ValidationException("Parameter [" . $field['name'] . "] is required, but not specified");
			}

			// Check that the format is more or less correct
			if (!empty($params[ $field['name'] ]) && strlen($params[ $field['name'] ]) > $field['size']) {
				throw new ValidationException(sprintf("Value [%s] in field [%s] is over the size limit [%s]", $params[ $field['name'] ], $field['name'], $field['size']));
			}


			if (!empty($params[ $field['name'] ])) {
				// Check the type of the value
				$correctType = false;
				switch ($field['type']) {
					case 'string':
						$correctType = is_string($params[ $field['name'] ]);
						break;
					case 'numeric':
						$correctType = is_numeric($params[ $field['name'] ]);
						break;
					case 'int':
						$correctType = is_int($params[ $field['name'] ]);
						break;
				}

				if (!$correctType) {
					throw new ValidationException(sprintf("Value [%s] in field [%s] is not of expected type [%s]", $params[ $field['name'] ], $field['name'], $field['type']));
				}
			}
		}
	}

	/**
	 * Check that there are no extra parameters in the query
	 *
	 * SafeCharge does not support arbitrary parameters in query. Only
	 * supported fields should be present.  This method checks that 
	 * there are no extra unsupported fields.
	 *
	 * @param array $params Query parameters
	 */
	protected function doQueryCheckNoExtra($params) {
		$allowedFields = array();
		foreach ($this->transactionFields as $field) {
			$allowedFields[] = $field['name'];
		}
		foreach ($params as $key => $value) {
			if (!in_array($key, $allowedFields)) {
				throw new InternalException("Field [$key] is not supported");
			}
		}
	}

	/**
	 * Check the validity of the credit card number
	 *
	 * - Check minimum length
	 * - Check maximum length
	 * - Check with Luhn algorithm
	 *
	 * @param array $params Query parameters
	 */
	protected function doQueryCheckCardNumber($params) {
		$cardNumber = $params['sg_CardNumber'];

		if ($cardNumber <> $this->cleanCardNumber($cardNumber)) {
			throw new CardNumberException("Card number is not pre-processed");
		}

		$cardLength = strlen($cardNumber); 

		if ($cardLength < self::MIN_LENGTH) {
			throw new CardNumberException("Card number is too short");
		}

		if ($cardLength > self::MAX_LENGTH) {
			throw new CardNumberException("Card number is too long");
		}

		/* Credit card LUHN checker - coded '05 shaman - www.planzero.org     *
		 * This code has been released into the public domain, however please *
		 * give credit to the original author where possible.                 */
		$parity = $cardLength % 2;
		$sum = 0;
		for ($i = 0; $i < $cardLength; $i++) { 
			$digit = $cardNumber[$i];
			if ($i % 2 == $parity) $digit = $digit * 2; 
			if ($digit > 9) $digit = $digit - 9; 
			$sum = $sum + $digit;
		}
		$valid = ($sum % 10 == 0) ? true : false; 

		if (!$valid) {
			throw new CardNumberException("Invalid checksum");
		}
	}

	/**
	 * Remove any non-digit characters from card number
	 *
	 * @param string $number Card number to clean
	 * @return string
	 */
	public function cleanCardNumber($number) {
		$result = preg_replace('/\D/', '', $number);
		return $result;
	}

	/**
	 * Replace middle of the string with given char, keeping length
	 *
	 * <code>
	 * print padString('1234567890123456', 0, 0, 'x');
	 * </code>
	 *
	 * @param string $string String to process
	 * @param integer $start Characters to skip from start
	 * @param integer $end Characters to leave at the end
	 * @param string $char Character to replace with
	 * @return string
	 */
	public function padString($string, $start = null, $end = null, $char = null) {
		$result = ''; 

		if ($start === null) { $start = $this->settings['padFrom']; }
		if ($end === null)   { $end   = $this->settings['padTo'];   }
		if ($char === null)  { $char  = $this->settings['padWith']; }

		$length = strlen($string) - $start - $end;
		if ($length <= 0) {
			$result = $string;
		}   
		else {
			$replacement = sprintf("%'{$char}" . $length . "s", $char);
			$result = substr_replace($string, $replacement, $start, $length);
		}   

		return $result;
	}

	/**
	 * Log message to a file if it was given in settings
	 *
	 * @param string $msg Message to log
	 */
	protected function log($msg) {
		if (!empty($this->settings['log'])) {
			$now = date('Y-m-d H:i:s');
			$logMessage = sprintf("%s : [%s] : %s\n", $now, $this->settings['instanceId'], $msg);
			file_put_contents($this->settings['log'], $logMessage, FILE_APPEND | LOCK_EX);
		}
	}
}
?>
