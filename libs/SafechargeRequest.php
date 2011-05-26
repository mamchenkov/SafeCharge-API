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

/**
 * Load common includes
 */
require_once dirname(__FILE__) . '/SafechargeCommon.php';

/**
 * SafeCharge Request
 *
 * Quries to Safecharge gateway are done via an instance of this
 * class.
 *
 * @package SafeCharge
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 */
class SafechargeRequest  {

	const QUERY_AUTH   = SafechargeConstants::REQUEST_TYPE_AUTH;
	const QUERY_SETTLE = SafechargeConstants::REQUEST_TYPE_SETTLE;
	const QUERY_SALE   = SafechargeConstants::REQUEST_TYPE_SALE;
	const QUERY_CREDIT = SafechargeConstants::REQUEST_TYPE_CREDIT;
	const QUERY_VOID   = SafechargeConstants::REQUEST_TYPE_VOID;
	const QUERY_AVS    = SafechargeConstants::REQUEST_TYPE_AVS;

	/**
	 * Request ID
	 *
	 * This can be any number or string. We use it mostly for
	 * logging and troubleshooting.
	 */
	protected $id;

	/**
	 * Settings
	 */
	protected $settings = array();

	/**
	 * Associative array of query parameters
	 */
	protected $params = array();

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
	 * Constructor
	 *
	 * @param string $id Optional query id. If not given, will be generated automatically
	 */
	public function __construct($settings = array(), $id = null) {
		$settings = (array) $settings;
		$this->settings = $settings;

		$id = (string) $id;
		$this->id = empty($id) ? $this->generateId() : $id;
		$this->params = $this->getDefaultParameters();

	}

	/**
	 * Generate query id
	 *
	 * @return string
	 */
	protected function generateId() {
		$result = '[QUERY ' . (string) rand(1, 100000) . ']';
		return $result;
	}

	/**
	 * Get query id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get array of default parameters
	 *
	 * @return array
	 */
	protected function getDefaultParameters() {
		$result =array(
				'sg_ClientLoginID'  => (string) $this->settings['username'],
				'sg_ClientPassword' => (string) $this->settings['password'],
				'sg_IPAddress'      => SafechargeConstants::REQUEST_DEFAULT_IP_ADDRESS,
				'sg_ResponseFormat' => SafechargeConstants::REQUEST_DEFAULT_RESPONSE_FORMAT,
				'sg_Is3dTrans'      => SafechargeConstants::REQUEST_DEFAULT_IS_3D_TRANS,
				'sg_ClientUniqueID' => (string) time(),
			);

		return $result;
	}

	/**
	 * Set query type
	 *
	 * @type string $type Query type
	 */
	public function setType($type) {
		$this->validateType($type);
		$this->params['sg_TransType'] = $type;
	}

	/**
	 * Validate query type
	 *
	 * @throws InternalException
	 * @param array $type Query type
	 */
	protected function validateType($type) {
		$type = (string) $type;
		// Type not empty
		if (empty($type)) {
			throw new InternalException("Transaction type required, but not specified");
		}
		// Type is allowed
		if (!in_array($type, $this->transactionTypes)) {
			throw new InternalException("Transaction type [$type] is not supported");
		}
	}


	/**
	 * Populate query with parameters
	 */
	public function setParameters($params) {
		$defaultParams = $this->params;
		$allParams = array_merge($defaultParams, $params);

		$this->validateParameters($allParams);

		$this->params = $allParams;
	}

	/**
	 * Check query parameters
	 *
	 * This is a wrapper method for all sorts of checks
	 *
	 * @param array $params Query parameters
	 */
	protected function validateParameters($params) {
		$this->doQueryCheckFields($params);
		$this->doQueryCheckNoExtra($params);
		$this->doQueryCheckCardNumber($params);
	}

	/**
	 * Check that query parameters are within field specs
	 *
	 * - Check that all required fields are present
	 * - Check that all fields are of the correct type
	 * - Check that all fields have a value that is within size limits
	 *
	 * @throws ValidationException
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
	 * @throws InternalException
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
	 * @throws CardNumberException
	 * @param array $params Query parameters
	 */
	protected function doQueryCheckCardNumber($params) {
		$cardNumber = $params['sg_CardNumber'];

		if ($cardNumber <> $this->cleanCardNumber($cardNumber)) {
			throw new CardNumberException("Card number is not pre-processed");
		}

		$cardLength = strlen($cardNumber); 

		if ($cardLength < SafechargeConstants::CARD_NUMBER_MIN_LENGTH) {
			throw new CardNumberException("Card number is too short");
		}

		if ($cardLength > SafechargeConstants::CARD_NUMBER_MAX_LENGTH) {
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
	 * Wrapper for http_build_query()
	 *
	 * We need this to avoid sensitive details like login credentials
	 * and card numbers falling through into log files.
	 *
	 * @param boolean $safe Build a safe query or full query
	 * @return string
	 */
	public function build($safe = false) {
		$result = '';

		$params = $this->params;
		if ($safe) {
			// Replace completely
			if (!empty($params['sg_ClientPassword'])) {
				$params['sg_ClientPassword'] = $this->padString($params['sg_ClientPassword'], 0, 0, 'x');
			}
			if (!empty($params['sg_CVV2'])) {
				$params['sg_CVV2'] = $this->padString($params['sg_CVV2'], 0, 0, 'x');
			}

			// Replace partially
			if (!empty($params['sg_CardNumber'])) {
				$params['sg_CardNumber'] = $this->padString($params['sg_CardNumber'], 6, 4, 'x');
			}
		}
		$server = $this->settings['live'] ? SafechargeConstants::SERVER_LIVE : SafechargeConstants::SERVER_TEST;
		$result = $server . http_build_query($params);

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
	public function padString($string, $start = 6, $end = 4, $char = 'x') {
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
	 * Send query to SafeCharge gateway
	 *
	 * @throws NetworkException
	 * @param string $queryUrl Query to send
	 * @return string
	 */
	public function send($queryUrl) {
		$result = null;

		$curl = new Net_Curl($queryUrl);
		if (!is_object($curl)) {
			throw new NetworkException("Failed to initialize Net_Curl");
		}

		// Curl settings
		$curl->timeout = $this->settings['timeout'];

		$curl_result = $curl->create();
		if (PEAR::isError($curl_result)) {
			throw new NetworkException("Failed to create Curl request");
		}

		// Fetch
		$result = trim($curl->execute());
		if (PEAR::isError($result)) {
			throw new NetworkException("Failed to execute Curl request");
		}

		return $result;
	}

}
?>
