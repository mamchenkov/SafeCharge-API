<?php
/**
 * PHP 5
 *
 * @package SafeCharge
 */

/**
 * Include SafeCharge class
 */
require_once dirname(__FILE__) . '/../SafeCharge.php';

/**
 * SafeCharge Test
 *
 * @author Leonid Mamchenkov <leonid@mamchenkov.net>
 * @package SafeCharge
 */
class SafeChargeTest extends PHPUnit_Framework_TestCase {

	/**
	 * List of valid test card numbers
	 *
	 * @link https://test.safecharge.com/doc/Test_CreditCards.txt
	 */
	private $validCards = array(
			'4000021059386316',
			'4000024473425231',
			'4000037434826586',
			'4000046595404935',
			'4000050320287425',
			'4000065919262217',
			'4000097446238923',
			'4444419078717848',
			'4444426864550804',
			'4444436501403986',
			'4444458284615321',
			'4444465389525081',
			'4444470762656560',
			'4444480853526820',
			'4444499431371889',
			'4444498667002689',
			'4444498051157677',
			'4444495866098625',
			'4444412360952876',
			'4000024059825994',
			'5100260315810893',
			'5100270690090656',
			'5100286513996754',
			'5333305231532763',
			'5333314109355505',
			'5333326770941868',
			'5333333139584041',
			'5333332933601845',
			'5333332230811667',
			'5333331746326111',
			'5333344683545239',
			'5333347342022912',
			'5333351770315269',
			'5333368254969676',
			'5333377040264581',
			'5333385639118126',
			'5333390439512403',
			'5333335034327954',
			'5333339469130529',
			'5333337896594614',
			'5333336113126473',
			'5333334054117130',
			'5333337861359175',
			'4012001036275556',
			'4012001038443335',
			'4012001036298889',
			'4012001036983332',
			'4012001037167778',
			'4012001037490014',
			'4005559876540',
			'36000023818683',
			'36000097503567',
			'6331101999990016',
		);

	/**
	 * Test Auth transactions
	 *
	 * !!! ATTENTION !!! Specify your SafeCharge test credentials in this method
	 *
	 * @dataProvider getValidTransactions
	 */
	public function test_doQuery($type, $params) {

		$settings = array(
				'username' => '',
				'password' => '',
			);

		if (empty($settings['username']) || empty($settings['password'])) {
			$this->markTestSkipped("Both username and password to SafeCharge API are required to run this test");
		}

		$sf = new SafeCharge($settings);

		$result = $sf->doQuery($type, $params, true);
		$this->assertFalse(empty($result), "Result is empty");
		$this->assertTrue(is_object($result), "Result is not an XML object");
		$this->assertFalse(empty($result->TransactionID), "No transaction ID");
		$this->assertEquals(SafeCharge::STATUS_APPROVED, (string) $result->Status, "Transaction not approved [" . $result->Status . "], reason: [" . $result->Reason . "]");
	}

	/**
	 * Provides valid transaction data
	 */
	public function getValidTransactions() {
		$result = array();
		$cards = $this->validCards;
		shuffle($cards);
		foreach ($this->validCards as $card) {
			$transaction = array(
				SafeCharge::QUERY_AUTH, 
				array(
					'sg_FirstName'=>'John',
					'sg_LastName'=>'Smith',
					'sg_Address'=>'Elm Street, 13',
					'sg_City'=>'London',
					'sg_State'=>'London',
					'sg_Zip'=>'3031',
					'sg_Country'=>'GB',
					'sg_Phone'=>'123456790',
					'sg_Email'=>'john@smith.com',
					'sg_Is3dTrans' => 0,
					'sg_Currency' => 'GBP',
					'sg_Amount' => '99.99',
					'sg_NameOnCard' => 'John Smith',
					'sg_CardNumber' => (string) $card,
					'sg_ExpMonth' => '12',
					'sg_ExpYear' => '13',
					'sg_CVV2' => '123',
					)
				);
			$result[] = $transaction;
		}
		return $result;
	}

}
?>
