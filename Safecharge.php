<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */

/**
 * Load common includes
 */
require_once dirname(__FILE__) . '/libs/SafechargeCommon.php';

// Load other classes
require_once dirname(__FILE__) . '/libs/SafechargeRequest.php';
require_once dirname(__FILE__) . '/libs/SafechargeResponse.php';

/**
 * SafeCharge Gateway API
 *
 * @package SafeCharge
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 * @link http://www.safecharge.com
 */
class Safecharge {

	// Shortcuts and external exposure

	const REQUEST_TYPE_AUTH   = SafechargeConstants::REQUEST_TYPE_AUTH;
	const REQUEST_TYPE_SETTLE = SafechargeConstants::REQUEST_TYPE_SETTLE;
	const REQUEST_TYPE_SALE   = SafechargeConstants::REQUEST_TYPE_SALE;
	const REQUEST_TYPE_CREDIT = SafechargeConstants::REQUEST_TYPE_CREDIT;
	const REQUEST_TYPE_VOID   = SafechargeConstants::REQUEST_TYPE_VOID;
	const REQUEST_TYPE_AVS    = SafechargeConstants::REQUEST_TYPE_AVS;

	const RESPONSE_STATUS_APPROVED = SafechargeConstants::RESPONSE_STATUS_APPROVED;
	const RESPONSE_STATUS_SUCCESS  = SafechargeConstants::RESPONSE_STATUS_SUCCESS;
	const RESPONSE_STATUS_DECLINED = SafechargeConstants::RESPONSE_STATUS_DECLINED;
	const RESPONSE_STATUS_ERROR    = SafechargeConstants::RESPONSE_STATUS_ERROR;
	const RESPONSE_STATUS_PENDING  = SafechargeConstants::RESPONSE_STATUS_PENDING;

	/**
	 * Settings
	 */
	protected $settings = array();

	/**
	 * Place to store the instance of the SafechargeRequest object
	 */
	protected $request;

	/**
	 * Place to store the instance of the SafechargeResponse object
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * Supported settings are:
	 *
	 * - username   -  SafeCharge username. Required for any queries to the gateway
	 * - password   -  SafeCharge password. Required for any queries to the gateway
	 * - timeout    -  Network operations timeout (in seconds)
	 * - live       -  Set to true for Live server, otherwise Test server will be used
	 * - log        -  Full path to the log file, if logging is necessary
	 * - padFrom    -  When masking credit card numbers in logs, leave so many starting digits
	 * - padTo      -  When masking credit card numbers in logs, leave so many ending digits
	 * - padWith    -  When masking credit card numbers in logs, use this character for masking
	 * - instanceId - Some ID of this object intance to keep related queries together
	 *
	 * @param array $settings Settings
	 */
	public function __construct($settings = array()) {

		$defaultSettings = array(
				'username' => SafechargeConstants::REQUEST_DEFAULT_USERNAME,
				'password' => SafechargeConstants::REQUEST_DEFAULT_PASSWORD,
				'timeout' => SafechargeConstants::REQUEST_DEFAULT_TIMEOUT,
				'live' => SafechargeConstants::REQUEST_DEFAULT_LIVE,

				'log' => '',

				'padFrom' => SafechargeConstants::DEFAULT_PAD_FROM,
				'padTo' => SafechargeConstants::DEFAULT_PAD_TO,
				'padWith' => SafechargeConstants::DEFAULT_PAD_WITH,

				'instanceId' => rand(1,100000),
			);
		$this->settings = array_merge($defaultSettings, $settings);

		$this->response = new SafechargeResponse();

		$this->log("Initialized");
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->log("Shutting down");
	}

	/**
	 * Send request
	 *
	 * @throws Exception
	 * @param string $type Type of query (Auth, Settle, etc)
	 * @param array $params Query params
	 * @return null|object Null on failure, or SimpleXMLElement on success
	 */
	public function doQuery($type, $params) {
		$result = null;

		$this->request = new SafechargeRequest($this->settings);

		$queryId = $this->request->getId();
		$this->log("$queryId Starting new [$type] query");

		$this->response->setQueryId($queryId);

		try {
			$this->request->setType($type);
			$this->request->setLive($this->settings['live']);
			$this->request->setCredentials($this->settings['username'], $this->settings['password']);
			$this->request->setParameters($params);

			$logQueryUrl = $this->request->build(true);
			$this->log("$queryId Sending query: $logQueryUrl");

			$queryUrl = $this->request->build();
			$response = $this->request->send($queryUrl, $queryId);

			$this->log("$queryId Parsing XML response");
			$result = $this->response->parse($response);
		}
		catch (NetworkException $e) {
			$this->log("$queryId Caught Network Exception: " . $e->getMessage());
			throw new Exception("Gateway communications error. Please try again later.");
		}
		catch (InternalException $e) {
			$this->log("$queryId Caught Internal Exception: " . $e->getMessage());
			throw new Exception("Internal server error. Please try again later.");
		}
		catch (ResponseException $e) {
			$this->log("$queryId Caught Response Exception: " . $e->getMessage());
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
