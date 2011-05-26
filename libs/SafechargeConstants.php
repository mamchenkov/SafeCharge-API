<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */
/**
 * SafeCharge Constants
 *
 * A collection of constants used by all other Safecharge
 * classes.
 *
 * @package SafeCharge
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 */
class SafechargeConstants  {

	const SERVER_LIVE = 'https://process.safecharge.com/service.asmx/Process?';
	const SERVER_TEST = 'https://test.safecharge.com/service.asmx/Process?';

	const REQUEST_TYPE_AUTH   = 'Auth';
	const REQUEST_TYPE_SETTLE = 'Settle';
	const REQUEST_TYPE_SALE   = 'Sale';
	const REQUEST_TYPE_CREDIT = 'Credit';
	const REQUEST_TYPE_VOID   = 'Void';
	const REQUEST_TYPE_AVS    = 'AVSOnly';

	const REQUEST_DEFAULT_USERNAME = '';
	const REQUEST_DEFAULT_PASSWORD = '';
	const REQUEST_DEFAULT_TIMEOUT = 30;
	const REQUEST_DEFAULT_LIVE = false;

	const REQUEST_DEFAULT_IP_ADDRESS = '127.0.0.1';
	const REQUEST_DEFAULT_RESPONSE_FORMAT = 4;
	const REQUEST_DEFAULT_IS_3D_TRANS = 0;

	const RESPONSE_STATUS_APPROVED = 'APPROVED';
	const RESPONSE_STATUS_SUCCESS  = 'SUCCESS';
	const RESPONSE_STATUS_DECLINED = 'DECLINED';
	const RESPONSE_STATUS_ERROR    = 'ERROR';
	const RESPONSE_STATUS_PENDING  = 'PENDING';

	const RESPONSE_XML_VERSION = '1.0';
	const RESPONSE_XML_ENCODING = 'utf-8';

	const DEFAULT_PAD_FROM = 6;
	const DEFAULT_PAD_TO = 4;
	const DEFAULT_PAD_WITH =  'x';

	/**
	 * Minimum length of a credit card number
	 */
	const CARD_NUMBER_MIN_LENGTH = 13;

	/**
	 * Maximum length of a credit card number
	 *
	 * @link http://www.merriampark.com/anatomycc.htm
	 */
	const CARD_NUMBER_MAX_LENGTH = 19;
}
?>
