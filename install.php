<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$table = \FreePBX::Database()->migrate("ivr_details");
$cols = array (
  'id' =>
  array (
    'type' => 'integer',
    'primaryKey' => true,
    'autoincrement' => true,
  ),
  'name' =>
  array (
    'type' => 'string',
    'length' => '50',
    'notnull' => false,
  ),
  'description' =>
  array (
    'type' => 'string',
    'length' => '150',
    'notnull' => false,
  ),
  'announcement' =>
  array (
    'type' => 'integer',
    'notnull' => false,
  ),
  'directdial' =>
  array (
    'type' => 'string',
    'length' => '50',
    'notnull' => false,
  ),
  'invalid_loops' =>
  array (
    'type' => 'string',
    'length' => '10',
    'notnull' => false,
  ),
  'invalid_retry_recording' =>
  array (
    'type' => 'string',
    'length' => '25',
    'notnull' => false,
  ),
  'invalid_destination' =>
  array (
    'type' => 'string',
    'length' => '50',
    'notnull' => false,
  ),
  'timeout_enabled' =>
  array (
    'type' => 'string',
    'length' => '50',
    'notnull' => false,
  ),
  'invalid_recording' =>
  array (
    'type' => 'string',
    'length' => '25',
    'notnull' => false,
  ),
  'retvm' =>
  array (
    'type' => 'string',
    'length' => '8',
    'notnull' => false,
  ),
  'timeout_time' =>
  array (
    'type' => 'integer',
    'notnull' => false,
  ),
  'timeout_recording' =>
  array (
    'type' => 'string',
    'length' => '25',
    'notnull' => false,
  ),
  'timeout_retry_recording' =>
  array (
    'type' => 'string',
    'length' => '25',
    'notnull' => false,
  ),
  'timeout_destination' =>
  array (
    'type' => 'string',
    'length' => '50',
    'notnull' => false,
  ),
  'timeout_loops' =>
  array (
    'type' => 'string',
    'length' => '10',
    'notnull' => false,
  ),
  'timeout_append_announce' =>
  array (
    'type' => 'boolean',
    'default' => '1',
  ),
  'invalid_append_announce' =>
  array (
    'type' => 'boolean',
    'default' => '1',
  ),
  'timeout_ivr_ret' =>
  array (
    'type' => 'boolean',
    'default' => '0',
  ),
  'invalid_ivr_ret' =>
  array (
    'type' => 'boolean',
    'default' => '0',
  ),
  'alertinfo' =>
  array (
    'type' => 'string',
    'length' => '150',
    'notnull' => false,
  ),
	'rvolume' =>
	array (
		'type' => 'string',
		'length' => '2',
		'notnull' => true,
		'default' => ''
	),
);


$indexes = array (
);
$table->modify($cols, $indexes);
unset($table);

$table = \FreePBX::Database()->migrate("ivr_entries");
$cols = array (
  'ivr_id' =>
  array (
    'type' => 'integer',
  ),
  'selection' =>
  array (
    'type' => 'string',
    'length' => '10',
    'notnull' => false,
  ),
  'dest' =>
  array (
    'type' => 'string',
    'length' => '200',
    'notnull' => false,
  ),
  'ivr_ret' =>
  array (
    'type' => 'boolean',
    'default' => '0',
  ),
);


$indexes = array (
);
$table->modify($cols, $indexes);
unset($table);
