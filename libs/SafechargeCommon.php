<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */

// Load exceptions
require_once dirname(__FILE__) . '/exceptions/CardNumberException.php';
require_once dirname(__FILE__) . '/exceptions/InternalException.php';
require_once dirname(__FILE__) . '/exceptions/NetworkException.php';
require_once dirname(__FILE__) . '/exceptions/ValidationException.php';
require_once dirname(__FILE__) . '/exceptions/ResponseException.php';

// Load constants
require_once dirname(__FILE__) . '/SafechargeConstants.php';

?>
