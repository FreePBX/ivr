<?php
namespace FreePBX\modules\Ivr\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Api\utests\ApiBaseTestCase;

class IvrGqlApiTest extends ApiBaseTestCase {
	protected static $ivr;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$ivr = self::$freepbx->Ivr;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
	}

	private function createIvrMock($methods) {
		$mockHelper = $this->getMockBuilder(\FreePBX\modules\Ivr::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->setMethods($methods)
			->getMock();

		self::$freepbx->Ivr = $mockHelper;
		return $mockHelper;
	}

	private function ivrRow($overrides = []) {
		$row = [
			'id' => '1',
			'name' => 'Main Menu',
			'description' => 'Main menu',
			'announcement' => '4',
			'directdial' => 'ext-local',
			'invalid_loops' => '3',
			'invalid_retry_recording' => 'default',
			'invalid_destination' => 'app-blackhole,hangup,1',
			'invalid_recording' => '',
			'retvm' => '',
			'timeout_time' => '10',
			'timeout_recording' => 'default',
			'timeout_retry_recording' => 'default',
			'timeout_destination' => 'app-blackhole,hangup,1',
			'timeout_loops' => '3',
			'timeout_append_announce' => '0',
			'invalid_append_announce' => '0',
			'timeout_ivr_ret' => '0',
			'invalid_ivr_ret' => '0',
			'alertinfo' => '',
			'rvolume' => '',
			'strict_dial_timeout' => '2',
			'accept_pound_key' => '0',
		];
		return array_merge($row, $overrides);
	}

	/**
	 * Ivr::getDetails leaves the PDO fetch mode at the connection default, so rows
	 * carry both the column names and their positions.
	 */
	private function fetchBothRow($overrides = []) {
		$row = $this->ivrRow($overrides);
		$final = [];
		$position = 0;
		foreach ($row as $column => $value) {
			$final[$column] = $value;
			$final[$position++] = $value;
		}
		return $final;
	}

	public function test_fetchAllIvrs_all_good_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->ivrRow(['id' => '1', 'name' => 'Main Menu', 'description' => 'Main menu', 'announcement' => '4', 'timeout_time' => '10', 'accept_pound_key' => '1']),
				$this->ivrRow(['id' => '2', 'name' => 'After Hours', 'description' => '', 'announcement' => '5', 'timeout_time' => '15', 'accept_pound_key' => '0']),
			]);

		$response = $this->request('query {
										fetchAllIvrs {
											status
											message
											totalCount
											ivrs {
												ivrId
												name
												description
												announcement
												timeout_time
												accept_pound_key
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchAllIvrs":{"status":true,"message":"IVR data found successfully","totalCount":2,"ivrs":[{"ivrId":"1","name":"Main Menu","description":"Main menu","announcement":4,"timeout_time":10,"accept_pound_key":true},{"ivrId":"2","name":"After Hours","description":"","announcement":5,"timeout_time":15,"accept_pound_key":false}]}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchAllIvrs_when_no_ivr_exists_should_return_no_data_found() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn([]);

		$response = $this->request('query {
										fetchAllIvrs {
											status
											message
											totalCount
											ivrs {
												ivrId
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchAllIvrs":{"status":true,"message":"No Data Found","totalCount":0,"ivrs":[]}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchAllIvrs_when_paginated_should_expose_edges_and_page_info() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->ivrRow(['id' => '1', 'name' => 'Main Menu']),
				$this->ivrRow(['id' => '2', 'name' => 'After Hours']),
				$this->ivrRow(['id' => '3', 'name' => 'Weekend']),
			]);

		$response = $this->request('query {
										fetchAllIvrs(first: 1) {
											totalCount
											pageInfo {
												hasNextPage
												hasPreviousPage
											}
											edges {
												node {
													ivrId
												}
											}
											ivrs {
												ivrId
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchAllIvrs":{"totalCount":3,"pageInfo":{"hasNextPage":true,"hasPreviousPage":false},"edges":[{"node":{"ivrId":"1"}}],"ivrs":[{"ivrId":"1"}]}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchAllIvrs_should_drop_the_positional_columns_of_the_fetched_rows() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->fetchBothRow(['id' => '1', 'name' => 'Main Menu']),
				$this->fetchBothRow(['id' => '2', 'name' => 'After Hours']),
			]);

		$response = $this->request('query {
										fetchAllIvrs {
											totalCount
											ivrs {
												ivrId
												name
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchAllIvrs":{"totalCount":2,"ivrs":[{"ivrId":"1","name":"Main Menu"},{"ivrId":"2","name":"After Hours"}]}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchIvr_all_good_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getEntries']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow([
				'id' => '1',
				'name' => 'Test &amp; IVR',
				'description' => 'Support &amp; Sales',
				'announcement' => '4',
				'timeout_time' => '10',
				'strict_dial_timeout' => '2',
				'invalid_loops' => '3',
				'accept_pound_key' => '1',
				'timeout_ivr_ret' => '0',
			]));

		$mockHelper->method('getEntries')
			->willReturn([
				['ivr_id' => '1', 'selection' => '1', 'dest' => 'from-did-direct,101,1', 'ivr_ret' => '1'],
				['ivr_id' => '1', 'selection' => '2', 'dest' => 'app-blackhole,hangup,1', 'ivr_ret' => '0'],
			]);

		$response = $this->request('{
										fetchIvr(id: "1") {
											status
											message
											ivrId
											name
											description
											announcement
											directdial
											timeout_time
											strict_dial_timeout
											invalid_loops
											accept_pound_key
											timeout_ivr_ret
											entries {
												ivr_id
												selection
												dest
												ivr_ret
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchIvr":{"status":true,"message":"IVR data found successfully","ivrId":"1","name":"Test & IVR","description":"Support & Sales","announcement":4,"directdial":"ext-local","timeout_time":10,"strict_dial_timeout":2,"invalid_loops":"3","accept_pound_key":true,"timeout_ivr_ret":false,"entries":[{"ivr_id":1,"selection":"1","dest":"from-did-direct,101,1","ivr_ret":true},{"ivr_id":1,"selection":"2","dest":"app-blackhole,hangup,1","ivr_ret":false}]}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchIvr_should_expose_a_relay_global_id() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '1']));

		$response = $this->request('{
										fetchIvr(id: "1") {
											id
											ivrId
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchIvr":{"id":"aXZyOjE=","ivrId":"1"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_node_on_an_ivr_global_id_should_return_the_ivr() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '1', 'name' => 'Main Menu']));

		$response = $this->request('{
										node(id: "aXZyOjE=") {
											id
											... on ivr {
												ivrId
												name
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"node":{"id":"aXZyOjE=","ivrId":"1","name":"Main Menu"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchIvr_without_an_id_should_be_rejected_by_the_schema() {

		$this->createIvrMock(['getDetails'])
			->expects($this->never())
			->method('getDetails');

		$response = $this->request('{
										fetchIvr {
											status
											message
										}
									}');

		$message = json_decode((string)$response->getBody(), true)['errors'][0]['message'];

		$this->assertEquals('Field "fetchIvr" argument "id" of type "ID!" is required but not provided.', $message);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_fetchIvr_on_invalid_id_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->ivrRow(['id' => '1']),
				$this->ivrRow(['id' => '2', 'name' => 'After Hours']),
			]);

		$response = $this->request('{
										fetchIvr(id: "999") {
											status
											message
											ivrId
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"IVR does not exist","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_all_good_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$savedDetails = null;
		$savedEntryId = null;
		$savedEntries = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('saveDetails')
			->willReturnCallback(function($vals) use (&$savedDetails) {
				$savedDetails = $vals;
				return '9';
			});

		$mockHelper->method('saveEntry')
			->willReturnCallback(function($id, $entries) use (&$savedEntryId, &$savedEntries) {
				$savedEntryId = $id;
				$savedEntries = $entries;
				return true;
			});

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '9', 'name' => 'Support Menu', 'description' => 'Support']));

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Support Menu"
											description: "Support"
											announcement: 3
											timeout_time: 12
											accept_pound_key: true
											invalid_append_announce: false
											entries: [
												{ selection: "1", dest: "from-did-direct,101,1", ivr_ret: true }
											]
										}) {
											status
											message
											ivr {
												ivrId
												name
												description
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"addIvr":{"status":true,"message":"IVR created successfully","ivr":{"ivrId":"9","name":"Support Menu","description":"Support"}}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertTrue(is_array($savedDetails));
		$this->assertSame('', $savedDetails['id']);
		$this->assertSame('Support Menu', $savedDetails['name']);
		$this->assertSame('Support', $savedDetails['description']);
		$this->assertEquals(3, $savedDetails['announcement']);
		$this->assertEquals(12, $savedDetails['timeout_time']);
		$this->assertSame(1, $savedDetails['accept_pound_key']);
		$this->assertSame(0, $savedDetails['invalid_append_announce']);
		$this->assertSame(0, $savedDetails['timeout_append_announce']);
		$this->assertSame(0, $savedDetails['invalid_ivr_ret']);
		$this->assertSame(0, $savedDetails['timeout_ivr_ret']);
		$this->assertEquals('ext-local', $savedDetails['directdial']);
		$this->assertEquals('default', $savedDetails['invalid_retry_recording']);
		$this->assertEquals('default', $savedDetails['timeout_retry_recording']);
		$this->assertEquals('default', $savedDetails['timeout_recording']);
		$this->assertEquals(3, $savedDetails['invalid_loops']);
		$this->assertEquals(3, $savedDetails['timeout_loops']);
		$this->assertEquals(2, $savedDetails['strict_dial_timeout']);
		$this->assertEquals('app-blackhole,hangup,1', $savedDetails['invalid_destination']);
		$this->assertEquals('app-blackhole,hangup,1', $savedDetails['timeout_destination']);
		$this->assertArrayNotHasKey('display', $savedDetails);
		$this->assertArrayNotHasKey('action', $savedDetails);
		$this->assertArrayNotHasKey('entries', $savedDetails);

		$this->assertEquals('9', $savedEntryId);
		$this->assertTrue(is_array($savedEntries));
		$this->assertCount(1, $savedEntries);
		$this->assertEquals('9', $savedEntries[0]['ivr_id']);
		$this->assertSame('1', $savedEntries[0]['selection']);
		$this->assertSame('from-did-direct,101,1', $savedEntries[0]['dest']);
		$this->assertSame(1, $savedEntries[0]['ivr_ret']);
	}

	public function test_addIvr_should_accept_the_dial_patterns_the_entry_grid_accepts() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$savedEntries = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('saveDetails')
			->willReturn('9');

		$mockHelper->method('saveEntry')
			->willReturnCallback(function($id, $entries) use (&$savedEntries) {
				$savedEntries = $entries;
				return true;
			});

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '9', 'name' => 'Patterns']));

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Patterns"
											entries: [
												{ selection: "_1XX", dest: "from-did-direct,101,1" }
												{ selection: "[0-9]", dest: "from-did-direct,102,1" }
												{ selection: "*97", dest: "from-did-direct,103,1" }
												{ selection: "#", dest: "from-did-direct,104,1" }
												{ selection: "t", dest: "from-did-direct,105,1" }
												{ selection: "i", dest: "from-did-direct,106,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"addIvr":{"status":true,"message":"IVR created successfully"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertCount(6, $savedEntries);
		$this->assertSame('_1XX', $savedEntries[0]['selection']);
		$this->assertSame('[0-9]', $savedEntries[1]['selection']);
		$this->assertSame('*97', $savedEntries[2]['selection']);
		$this->assertSame('#', $savedEntries[3]['selection']);
		$this->assertSame('t', $savedEntries[4]['selection']);
		$this->assertSame('i', $savedEntries[5]['selection']);
	}

	public function test_addIvr_on_a_selection_that_is_not_a_dial_pattern_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											entries: [
												{ selection: "abc", dest: "from-did-direct,101,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid entry selection","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_selection_longer_than_ten_digits_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											entries: [
												{ selection: "12345678901", dest: "from-did-direct,101,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid entry selection","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_duplicate_selections_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											entries: [
												{ selection: "1", dest: "from-did-direct,101,1" }
												{ selection: "1", dest: "from-did-direct,102,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Duplicate entry selections","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_invalid_destination_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$mockHelper->expects($this->never())
			->method('saveEntry');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											invalid_destination: "app-blackhole"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid destination format","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_destination_carrying_a_line_break_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Dialplan Break"
											invalid_destination: "app-blackhole,hangup,1\n#include /tmp/evil.conf"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Values must not contain line breaks","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_destination_ending_in_a_line_break_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Trailing Break"
											timeout_destination: "app-blackhole,hangup,1\n"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Values must not contain line breaks","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_name_carrying_a_line_break_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken"
											alertinfo: "Bellcore-dr1\n[from-internal]"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Values must not contain line breaks","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_an_entry_selection_ending_in_a_line_break_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Label"
											entries: [
												{ selection: "1\n", dest: "from-did-direct,101,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid entry selection","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_an_entry_destination_carrying_a_line_break_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Goto"
											entries: [
												{ selection: "1", dest: "from-did-direct,101,1\n[from-internal]" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid entry destination","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_selection_longer_than_the_column_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Long Selection"
											entries: [
												{ selection: "'.str_repeat('[', 100).']", dest: "from-did-direct,101,1" }
											]
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid entry selection","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_destination_longer_than_the_column_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Long Destination"
											invalid_destination: "app-blackhole,hangup,'.str_repeat('1', 60).'"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Invalid destination format","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_name_that_is_already_taken_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn(['Main Menu', 'After Hours']);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "After Hours"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Name is already in use by another IVR","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_name_longer_than_fifty_characters_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "'.str_repeat('a', 51).'"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Name must not exceed 50 characters","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_name_of_fifty_multibyte_characters_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$name = str_repeat("\xc4\x8d", 50);
		$savedDetails = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('saveDetails')
			->willReturnCallback(function($vals) use (&$savedDetails) {
				$savedDetails = $vals;
				return '9';
			});

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '9', 'name' => $name]));

		$response = $this->request('mutation {
										addIvr(input: {
											name: "'.$name.'"
										}) {
											status
											message
										}
									}');

		$this->assertEquals('{"data":{"addIvr":{"status":true,"message":"IVR created successfully"}}}',(string)$response->getBody());

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertSame($name, $savedDetails['name']);
	}

	public function test_addIvr_on_a_description_longer_than_the_column_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Long Description"
											description: "'.str_repeat('d', 151).'"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Description must not exceed 150 characters","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_out_of_range_loops_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											invalid_loops: "11"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Loops must be between 0 and 10 or disabled","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_out_of_range_strict_dial_timeout_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											strict_dial_timeout: 5
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Strict dial timeout must be 0, 1 or 2","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addIvr_on_a_negative_timeout_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										addIvr(input: {
											name: "Broken Menu"
											timeout_time: -5
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Timeout must be 0 or greater","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateIvr_with_partial_input_should_send_full_row_to_saveDetails() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$savedDetails = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow([
				'id' => '4',
				'name' => 'Old &amp; Name',
				'description' => 'Desc &amp; more',
				'announcement' => '4',
				'timeout_time' => '10',
				'accept_pound_key' => '1',
				'timeout_enabled' => '',
			]));

		$mockHelper->method('saveDetails')
			->willReturnCallback(function($vals) use (&$savedDetails) {
				$savedDetails = $vals;
				return '4';
			});

		$mockHelper->expects($this->never())
			->method('saveEntry');

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											name: "New Name"
										}) {
											status
											message
											ivr {
												ivrId
											}
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"updateIvr":{"status":true,"message":"IVR updated successfully","ivr":{"ivrId":"4"}}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertTrue(is_array($savedDetails));
		$this->assertEquals('4', $savedDetails['id']);
		$this->assertSame('New Name', $savedDetails['name']);
		$this->assertSame('Desc & more', $savedDetails['description']);
		$this->assertEquals(4, $savedDetails['announcement']);
		$this->assertEquals(10, $savedDetails['timeout_time']);
		$this->assertEquals('ext-local', $savedDetails['directdial']);
		$this->assertEquals('default', $savedDetails['invalid_retry_recording']);
		$this->assertEquals('3', $savedDetails['invalid_loops']);
		$this->assertEquals('app-blackhole,hangup,1', $savedDetails['timeout_destination']);
		$this->assertSame(1, $savedDetails['accept_pound_key']);
		$this->assertSame(0, $savedDetails['timeout_ivr_ret']);
		$this->assertArrayNotHasKey('entries', $savedDetails);
		$this->assertArrayNotHasKey('display', $savedDetails);
		$this->assertArrayNotHasKey('timeout_enabled', $savedDetails);
	}

	public function test_updateIvr_should_not_validate_the_columns_it_was_not_given() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$savedDetails = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow([
				'id' => '4',
				'invalid_destination' => '',
				'invalid_loops' => '',
				'strict_dial_timeout' => '',
			]));

		$mockHelper->method('saveDetails')
			->willReturnCallback(function($vals) use (&$savedDetails) {
				$savedDetails = $vals;
				return '4';
			});

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											name: "New Name"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"updateIvr":{"status":true,"message":"IVR updated successfully"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertSame('New Name', $savedDetails['name']);
		$this->assertSame('', $savedDetails['invalid_destination']);
		$this->assertSame('', $savedDetails['invalid_loops']);
		$this->assertSame('', $savedDetails['strict_dial_timeout']);
	}

	public function test_updateIvr_keeping_its_own_name_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '4', 'name' => 'Main Menu']));

		$mockHelper->method('getallivrsname')
			->with($this->equalTo('4'))
			->willReturn(['After Hours']);

		$mockHelper->method('saveDetails')
			->willReturn('4');

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											name: "Main Menu"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"updateIvr":{"status":true,"message":"IVR updated successfully"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_updateIvr_taking_the_name_of_another_ivr_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '4', 'name' => 'Main Menu']));

		$mockHelper->method('getallivrsname')
			->willReturn(['After Hours']);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											name: "After Hours"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Name is already in use by another IVR","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateIvr_with_an_empty_entry_list_should_clear_the_entries() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$savedEntries = null;

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '4']));

		$mockHelper->method('saveDetails')
			->willReturn('4');

		$mockHelper->method('saveEntry')
			->willReturnCallback(function($id, $entries) use (&$savedEntries) {
				$savedEntries = $entries;
				return true;
			});

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											entries: []
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"updateIvr":{"status":true,"message":"IVR updated successfully"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());

		$this->assertTrue(is_array($savedEntries));
		$this->assertCount(0, $savedEntries);
	}

	public function test_updateIvr_with_an_empty_name_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'getallivrsname', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getallivrsname')
			->willReturn([]);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '4']));

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "4"
											name: ""
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Name is required","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateIvr_on_invalid_id_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'saveDetails', 'saveEntry']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->ivrRow(['id' => '1']),
				$this->ivrRow(['id' => '2', 'name' => 'After Hours']),
			]);

		$mockHelper->expects($this->never())
			->method('saveDetails');

		$mockHelper->expects($this->never())
			->method('saveEntry');

		$response = $this->request('mutation {
										updateIvr(input: {
											id: "999"
											name: "New Name"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"IVR does not exist","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_deleteIvr_all_good_should_return_true() {

		$mockHelper = $this->createIvrMock(['getDetails', 'delete']);

		$mockHelper->method('getDetails')
			->willReturn($this->ivrRow(['id' => '7', 'name' => 'Removable']));

		$mockHelper->expects($this->once())
			->method('delete')
			->with($this->equalTo('7'));

		$response = $this->request('mutation {
										deleteIvr(input: {
											id: "7"
										}) {
											status
											message
											deletedId
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"deleteIvr":{"status":true,"message":"IVR deleted successfully","deletedId":"7"}}}',$json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_deleteIvr_on_invalid_id_should_return_false() {

		$mockHelper = $this->createIvrMock(['getDetails', 'delete']);

		$mockHelper->method('getDetails')
			->willReturn([
				$this->ivrRow(['id' => '1']),
				$this->ivrRow(['id' => '2', 'name' => 'After Hours']),
			]);

		$mockHelper->expects($this->never())
			->method('delete');

		$response = $this->request('mutation {
										deleteIvr(input: {
											id: "999"
										}) {
											status
											message
										}
									}');

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"IVR does not exist","status":false}]}',$json);

		$this->assertEquals(400, $response->getStatusCode());
	}
}
