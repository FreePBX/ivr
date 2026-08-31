<?php

namespace FreePBX\modules\Ivr\Api\Gql;

use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLRelay\Relay;

class Ivr extends Base
{
    protected $module = 'ivr';
    protected $description = 'Used to build a menu that lets callers select where their call is routed';
    private $entryInputType = null;

    private const NAME_MAX_LENGTH = 50;
    private const DESCRIPTION_MAX_LENGTH = 150;
    private const SELECTION_MAX_DIGITS = 10;
    private const SELECTION_MAX_LENGTH = 30;
    private const DESTINATION_MAX_LENGTH = 50;
    private const ENTRY_DESTINATION_MAX_LENGTH = 200;
    private const RESERVED_SELECTIONS = ['i', 't'];

    public static function getScopes()
    {
        return [
            'read:ivr' => [
                'description' => _('Read IVRs'),
            ],
            'write:ivr' => [
                'description' => _('Write IVRs'),
            ],
        ];
    }

    public function queryCallback()
    {
        if ($this->checkReadScope("ivr")) {
            return function () {
                return [
                    'fetchAllIvrs' => [
                        'type' => $this->typeContainer->get('ivr')->getConnectionType(),
                        'description' => _('List all of the IVRs on the system'),
                        'args' => Relay::connectionArgs(),
                        'resolve' => function ($root, $args) {
                            $rows = $this->getAllIvrRows();
                            $res = Relay::connectionFromArray($rows, $args);
                            if ((is_countable($res['edges']) ? count($res['edges']) : 0) > 0) {
                                $message = _('IVR data found successfully');
                            } else {
                                $message = _('No Data Found');
                            }
                            return array_merge($res, ['response' => $res, 'total' => count($rows), 'status' => true, 'message' => $message]);
                        },
                    ],
                    'fetchIvr' => [
                        'type' => $this->typeContainer->get('ivr')->getObject(),
                        'description' => _('Fetch a single IVR by id'),
                        'args' => [
                            'id' => [
                                'type' => Type::nonNull(Type::id()),
                                'description' => _('The IVR id'),
                            ]
                        ],
                        'resolve' => function ($root, $args) {
                            $row = $this->getIvrRow($args['id']);
                            if (!empty($row)) {
                                return ['response' => $row, 'status' => true, 'message' => _('IVR data found successfully')];
                            }
                            return ['status' => false, 'message' => _('IVR does not exist')];
                        }
                    ]
                ];
            };
        }
    }

    public function mutationCallback()
    {
        if ($this->checkWriteScope("ivr")) {
            return function () {
                return [
                    'addIvr' => Relay::mutationWithClientMutationId([
                        'name' => 'addIvr',
                        'description' => _('Add a new IVR to the system'),
                        'inputFields' => array_merge(
                            [
                                'name' => [
                                    'type' => Type::nonNull(Type::string()),
                                    'description' => _('The name of the IVR')
                                ]
                            ],
                            $this->getSharedInputFields()
                        ),
                        'outputFields' => $this->getIvrOutputFields(),
                        'mutateAndGetPayload' => function ($input) {
                            $entries = isset($input['entries']) ? $input['entries'] : [];
                            $failure = $this->validateIvrValues($input, $entries, true, null);
                            if (!is_null($failure)) {
                                return ['status' => false, 'message' => $failure];
                            }
                            $vals = array_merge($this->getIvrDefaults(), $this->whitelistIvrValues($input));
                            $vals['id'] = '';
                            $vals = $this->normalizeIvrValues($vals);
                            $id = $this->freepbx->Ivr->saveDetails($vals);
                            $this->freepbx->Ivr->saveEntry($id, $this->buildEntryRows($id, $entries));
                            \needreload();
                            return ['response' => $this->getIvrRow($id), 'status' => true, 'message' => _('IVR created successfully')];
                        }
                    ]),
                    'updateIvr' => Relay::mutationWithClientMutationId([
                        'name' => 'updateIvr',
                        'description' => _('Update an IVR on the system'),
                        'inputFields' => array_merge(
                            [
                                'id' => [
                                    'type' => Type::nonNull(Type::id()),
                                    'description' => _('The IVR id')
                                ],
                                'name' => [
                                    'type' => Type::string(),
                                    'description' => _('The name of the IVR')
                                ]
                            ],
                            $this->getSharedInputFields()
                        ),
                        'outputFields' => $this->getIvrOutputFields(),
                        'mutateAndGetPayload' => function ($input) {
                            $existing = $this->getIvrRow($input['id']);
                            if (empty($existing)) {
                                return ['status' => false, 'message' => _('IVR does not exist')];
                            }
                            $existing['name'] = html_entity_decode((string)($existing['name'] ?? ''), ENT_QUOTES);
                            $existing['description'] = html_entity_decode((string)($existing['description'] ?? ''), ENT_QUOTES);
                            $supplied = $this->whitelistIvrValues($input);
                            $entries = isset($input['entries']) ? $input['entries'] : null;
                            $failure = $this->validateIvrValues($supplied, $entries, isset($input['name']), $input['id']);
                            if (!is_null($failure)) {
                                return ['status' => false, 'message' => $failure];
                            }
                            $vals = array_merge($this->whitelistIvrValues($existing), $supplied);
                            $vals['id'] = $input['id'];
                            $vals = $this->normalizeIvrValues($vals);
                            $id = $this->freepbx->Ivr->saveDetails($vals);
                            if (!is_null($entries)) {
                                $this->freepbx->Ivr->saveEntry($id, $this->buildEntryRows($id, $entries));
                            }
                            \needreload();
                            return ['response' => $this->getIvrRow($id), 'status' => true, 'message' => _('IVR updated successfully')];
                        }
                    ]),
                    'deleteIvr' => Relay::mutationWithClientMutationId([
                        'name' => 'deleteIvr',
                        'description' => _('Remove an IVR from the system'),
                        'inputFields' => [
                            'id' => [
                                'type' => Type::nonNull(Type::id()),
                                'description' => _('The IVR id')
                            ]
                        ],
                        'outputFields' => [
                            'deletedId' => [
                                'type' => Type::nonNull(Type::id()),
                                'description' => _('The id of the deleted IVR'),
                                'resolve' => function ($payload) {
                                    return $payload['id'];
                                }
                            ],
                            'status' => [
                                'type' => Type::boolean(),
                                'description' => _('Status of the request')
                            ],
                            'message' => [
                                'type' => Type::string(),
                                'description' => _('Message for the request')
                            ],
                        ],
                        'mutateAndGetPayload' => function ($input) {
                            $existing = $this->getIvrRow($input['id']);
                            if (empty($existing)) {
                                return ['id' => $input['id'], 'status' => false, 'message' => _('IVR does not exist')];
                            }
                            $this->freepbx->Ivr->delete($input['id']);
                            \needreload();
                            return ['id' => $input['id'], 'status' => true, 'message' => _('IVR deleted successfully')];
                        }
                    ])
                ];
            };
        }
    }

    public function initializeTypes()
    {
        $entry = $this->typeContainer->create('ivrEntry', 'object');
        $entry->setDescription(_('A single selection of an IVR'));
        $entry->addFieldCallback(function () {
            return [
                'ivr_id' => [
                    'type' => Type::int(),
                    'description' => _('The id of the IVR this entry belongs to')
                ],
                'selection' => [
                    'type' => Type::string(),
                    'description' => _('The digits the caller presses to reach the destination')
                ],
                'dest' => [
                    'type' => Type::string(),
                    'description' => _('The destination this selection sends the caller to')
                ],
                'ivr_ret' => [
                    'type' => Type::boolean(),
                    'description' => _('Return to this IVR after the destination has finished')
                ],
            ];
        });

        $ivr = $this->typeContainer->create('ivr');
        $ivr->setDescription($this->description);

        $ivr->setGetNodeCallback(function ($id) {
            return $this->getIvrRow($id);
        });

        $ivr->addInterfaceCallback(function () {
            return [$this->getNodeDefinition()['nodeInterface']];
        });

        $ivr->addFieldCallback(function () {
            return [
                'id' => Relay::globalIdField('ivr', function ($row) {
                    return $this->readColumn($row, 'id');
                }),
                'ivrId' => [
                    'type' => Type::id(),
                    'description' => _('The IVR id'),
                    'resolve' => function ($row) {
                        return $this->readColumn($row, 'id');
                    }
                ],
                'name' => $this->decodedStringField('name', _('The name of the IVR')),
                'description' => $this->decodedStringField('description', _('A description of what this IVR is used for')),
                'announcement' => $this->intField('announcement', _('The recording played when the caller enters the IVR')),
                'directdial' => $this->stringField('directdial', _('Allow callers to dial an extension directly from this IVR')),
                'invalid_loops' => $this->stringField('invalid_loops', _('How many times an invalid entry is accepted before the caller is sent to the invalid destination')),
                'invalid_retry_recording' => $this->stringField('invalid_retry_recording', _('The recording played after an invalid entry')),
                'invalid_destination' => $this->stringField('invalid_destination', _('Where the caller is sent after too many invalid entries')),
                'invalid_recording' => $this->stringField('invalid_recording', _('The recording played before the caller is sent to the invalid destination')),
                'retvm' => $this->stringField('retvm', _('Return the caller to the IVR after a voicemail message has been left')),
                'timeout_recording' => $this->stringField('timeout_recording', _('The recording played before the caller is sent to the timeout destination')),
                'timeout_retry_recording' => $this->stringField('timeout_retry_recording', _('The recording played after the caller has not made a selection in time')),
                'timeout_destination' => $this->stringField('timeout_destination', _('Where the caller is sent after too many timeouts')),
                'timeout_loops' => $this->stringField('timeout_loops', _('How many times a timeout is accepted before the caller is sent to the timeout destination')),
                'alertinfo' => $this->stringField('alertinfo', _('ALERT_INFO used for distinctive ring with SIP devices')),
                'rvolume' => $this->stringField('rvolume', _('Override the ringer volume')),
                'timeout_time' => $this->intField('timeout_time', _('How long the IVR waits for a selection before it times out')),
                'strict_dial_timeout' => $this->intField('strict_dial_timeout', _('How the IVR waits for additional digits after a valid selection')),
                'timeout_append_announce' => $this->booleanField('timeout_append_announce', _('Append the announcement to the timeout retry recording')),
                'invalid_append_announce' => $this->booleanField('invalid_append_announce', _('Append the announcement to the invalid retry recording')),
                'timeout_ivr_ret' => $this->booleanField('timeout_ivr_ret', _('Return to this IVR after the timeout destination has finished')),
                'invalid_ivr_ret' => $this->booleanField('invalid_ivr_ret', _('Return to this IVR after the invalid destination has finished')),
                'accept_pound_key' => $this->booleanField('accept_pound_key', _('Treat the pound key as a selection instead of the end of an entry')),
                'entries' => [
                    'type' => Type::listOf($this->typeContainer->get('ivrEntry')->getObject()),
                    'description' => _('The selections of this IVR'),
                    'resolve' => function ($row) {
                        $id = $this->readColumn($row, 'id');
                        if (empty($id)) {
                            return [];
                        }
                        return $this->freepbx->Ivr->getEntries($id);
                    }
                ],
                'message' => [
                    'type' => Type::string(),
                    'description' => _('Message for the request')
                ],
                'status' => [
                    'type' => Type::boolean(),
                    'description' => _('Status for the request')
                ],
            ];
        });

        $ivr->setConnectionResolveNode(function ($edge) {
            return $edge['node'];
        });

        $ivr->setConnectionFields(function () {
            return [
                'totalCount' => [
                    'type' => Type::int(),
                    'description' => _('A count of the total number of objects in this connection, ignoring pagination.'),
                    'resolve' => function ($root, $args) {
                        if (isset($root['total'])) {
                            return $root['total'];
                        }
                        if (isset($root['response']['edges'])) {
                            return is_countable($root['response']['edges']) ? count($root['response']['edges']) : 0;
                        }
                        return 0;
                    }
                ],
                'ivrs' => [
                    'type' => Type::listOf($this->typeContainer->get('ivr')->getObject()),
                    'description' => _('A list of all of the objects returned in the connection.'),
                    'resolve' => function ($root, $args) {
                        if (isset($root['response'])) {
                            return array_map(function ($row) {
                                return $row['node'];
                            }, $root['response']['edges']);
                        }
                        return null;
                    }
                ],
                'message' => [
                    'type' => Type::string(),
                    'description' => _('Message for the request')
                ],
                'status' => [
                    'type' => Type::boolean(),
                    'description' => _('Status for the request')
                ],
            ];
        });
    }

    public function postInitializeTypes()
    {
        $destinations = $this->typeContainer->get('destination');
        $destinations->addTypeCallback(function () {
            return [
                $this->typeContainer->get('ivr')->getObject()
            ];
        });

        $destinations->addResolveTypeCallback(function ($value, $context, $info) {
            if (is_array($value) && isset($value['graphqlType']) && $value['graphqlType'] == 'ivr') {
                return $this->typeContainer->get('ivr')->getObject();
            }
        });

        $destinations->addResolveValueCallback(function ($value) {
            if (preg_match('/^ivr-(\d+),/', trim((string)$value), $matches)) {
                $row = $this->getIvrRow($matches[1]);
                if (!empty($row)) {
                    return array_merge($row, ['graphqlType' => 'ivr']);
                }
            }
        });
    }

    private function readColumn($row, $column)
    {
        if (isset($row[$column])) {
            return $row[$column];
        }
        if (isset($row['response'][$column])) {
            return $row['response'][$column];
        }
        return null;
    }

    private function stringField($column, $description)
    {
        return [
            'type' => Type::string(),
            'description' => $description,
            'resolve' => function ($row) use ($column) {
                return $this->readColumn($row, $column);
            }
        ];
    }

    private function decodedStringField($column, $description)
    {
        return [
            'type' => Type::string(),
            'description' => $description,
            'resolve' => function ($row) use ($column) {
                $value = $this->readColumn($row, $column);
                if (is_null($value)) {
                    return null;
                }
                return html_entity_decode((string)$value, ENT_QUOTES);
            }
        ];
    }

    private function intField($column, $description)
    {
        return [
            'type' => Type::int(),
            'description' => $description,
            'resolve' => function ($row) use ($column) {
                $value = $this->readColumn($row, $column);
                if (is_null($value) || $value === '') {
                    return null;
                }
                return (int)$value;
            }
        ];
    }

    private function booleanField($column, $description)
    {
        return [
            'type' => Type::boolean(),
            'description' => $description,
            'resolve' => function ($row) use ($column) {
                $value = $this->readColumn($row, $column);
                if (is_null($value)) {
                    return null;
                }
                return (bool)$value;
            }
        ];
    }

    private function getSharedInputFields()
    {
        return [
            'description' => [
                'type' => Type::string(),
                'description' => _('A description of what this IVR is used for')
            ],
            'directdial' => [
                'type' => Type::string(),
                'description' => _('Allow callers to dial an extension directly from this IVR')
            ],
            'alertinfo' => [
                'type' => Type::string(),
                'description' => _('ALERT_INFO used for distinctive ring with SIP devices')
            ],
            'rvolume' => [
                'type' => Type::string(),
                'description' => _('Override the ringer volume')
            ],
            'invalid_loops' => [
                'type' => Type::string(),
                'description' => _('How many times an invalid entry is accepted before the caller is sent to the invalid destination. A number between 0 and 10 or disabled')
            ],
            'invalid_retry_recording' => [
                'type' => Type::string(),
                'description' => _('The recording played after an invalid entry')
            ],
            'invalid_recording' => [
                'type' => Type::string(),
                'description' => _('The recording played before the caller is sent to the invalid destination')
            ],
            'invalid_destination' => [
                'type' => Type::string(),
                'description' => _('Where the caller is sent after too many invalid entries, for example app-blackhole,hangup,1')
            ],
            'timeout_loops' => [
                'type' => Type::string(),
                'description' => _('How many times a timeout is accepted before the caller is sent to the timeout destination. A number between 0 and 10 or disabled')
            ],
            'timeout_retry_recording' => [
                'type' => Type::string(),
                'description' => _('The recording played after the caller has not made a selection in time')
            ],
            'timeout_recording' => [
                'type' => Type::string(),
                'description' => _('The recording played before the caller is sent to the timeout destination')
            ],
            'timeout_destination' => [
                'type' => Type::string(),
                'description' => _('Where the caller is sent after too many timeouts, for example app-blackhole,hangup,1')
            ],
            'retvm' => [
                'type' => Type::string(),
                'description' => _('Return the caller to the IVR after a voicemail message has been left')
            ],
            'announcement' => [
                'type' => Type::int(),
                'description' => _('The id of the recording played when the caller enters the IVR')
            ],
            'timeout_time' => [
                'type' => Type::int(),
                'description' => _('How long the IVR waits for a selection before it times out')
            ],
            'strict_dial_timeout' => [
                'type' => Type::int(),
                'description' => _('How the IVR waits for additional digits after a valid selection. One of 0, 1 or 2')
            ],
            'invalid_append_announce' => [
                'type' => Type::boolean(),
                'description' => _('Append the announcement to the invalid retry recording')
            ],
            'invalid_ivr_ret' => [
                'type' => Type::boolean(),
                'description' => _('Return to this IVR after the invalid destination has finished')
            ],
            'timeout_append_announce' => [
                'type' => Type::boolean(),
                'description' => _('Append the announcement to the timeout retry recording')
            ],
            'timeout_ivr_ret' => [
                'type' => Type::boolean(),
                'description' => _('Return to this IVR after the timeout destination has finished')
            ],
            'accept_pound_key' => [
                'type' => Type::boolean(),
                'description' => _('Treat the pound key as a selection instead of the end of an entry')
            ],
            'entries' => [
                'type' => Type::listOf($this->getEntryInputType()),
                'description' => _('The selections of this IVR')
            ],
        ];
    }

    private function getEntryInputType()
    {
        if (is_null($this->entryInputType)) {
            $this->entryInputType = new InputObjectType([
                'name' => 'ivrEntryInput',
                'description' => _('A single selection of an IVR'),
                'fields' => [
                    'selection' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => _('The digits the caller presses to reach the destination')
                    ],
                    'dest' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => _('The destination this selection sends the caller to')
                    ],
                    'ivr_ret' => [
                        'type' => Type::boolean(),
                        'description' => _('Return to this IVR after the destination has finished')
                    ],
                ]
            ]);
        }
        return $this->entryInputType;
    }

    private function getIvrOutputFields()
    {
        return [
            'ivr' => [
                'type' => $this->typeContainer->get('ivr')->getObject(),
                'description' => _('The IVR'),
                'resolve' => function ($payload) {
                    if (!empty($payload['status'])) {
                        return $payload['response'];
                    }
                    return null;
                }
            ],
            'status' => [
                'type' => Type::boolean(),
                'description' => _('Status of the request')
            ],
            'message' => [
                'type' => Type::string(),
                'description' => _('Message for the request')
            ],
        ];
    }

    private function getIvrDefaults()
    {
        $defaults = \FreePBX\modules\Ivr::DEFAULTS;
        unset($defaults['display']);
        unset($defaults['action']);
        unset($defaults['entries']);
        return $defaults;
    }

    private function whitelistIvrValues($values)
    {
        return array_intersect_key($values, $this->getIvrDefaults());
    }

    private function normalizeIvrValues($vals)
    {
        $booleans = ['invalid_append_announce', 'invalid_ivr_ret', 'timeout_append_announce', 'timeout_ivr_ret', 'accept_pound_key'];
        foreach ($booleans as $key) {
            if (isset($vals[$key]) && $vals[$key]) {
                $vals[$key] = 1;
            } else {
                $vals[$key] = 0;
            }
        }
        if (!isset($vals['announcement']) || !is_numeric($vals['announcement'])) {
            $vals['announcement'] = null;
        }
        return $vals;
    }

    private function buildEntryRows($id, $entries)
    {
        $rows = [];
        if (!is_array($entries)) {
            return $rows;
        }
        foreach ($entries as $entry) {
            $ivrRet = 0;
            if (isset($entry['ivr_ret']) && $entry['ivr_ret']) {
                $ivrRet = 1;
            }
            $rows[] = [
                'ivr_id' => $id,
                'selection' => isset($entry['selection']) ? $entry['selection'] : '',
                'dest' => isset($entry['dest']) ? $entry['dest'] : '',
                'ivr_ret' => $ivrRet,
            ];
        }
        return $rows;
    }

    private function getAllIvrRows()
    {
        $rows = $this->freepbx->Ivr->getDetails();
        if (!is_array($rows)) {
            return [];
        }
        $final = [];
        foreach ($rows as $row) {
            $final[] = $this->filterRowColumns($row);
        }
        return $final;
    }

    private function getIvrRow($id)
    {
        if (empty($id)) {
            return null;
        }
        $row = $this->freepbx->Ivr->getDetails($id);
        if (empty($row) || !isset($row['id'])) {
            return null;
        }
        return $this->filterRowColumns($row);
    }

    private function filterRowColumns($row)
    {
        if (!is_array($row)) {
            return $row;
        }
        return array_filter($row, function ($key) {
            return is_string($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function validateIvrValues($values, $entries, $nameRequired, $currentId)
    {
        if ($this->containsLineBreak($values)) {
            return _('Values must not contain line breaks');
        }
        if (isset($values['name']) || $nameRequired) {
            $name = isset($values['name']) ? trim((string)$values['name']) : '';
            if ($nameRequired && $name === '') {
                return _('Name is required');
            }
            if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
                return _('Name must not exceed 50 characters');
            }
            if (in_array($name, $this->freepbx->Ivr->getallivrsname($currentId), true)) {
                return _('Name is already in use by another IVR');
            }
        }
        if (isset($values['description']) && mb_strlen((string)$values['description']) > self::DESCRIPTION_MAX_LENGTH) {
            return _('Description must not exceed 150 characters');
        }
        if (isset($values['timeout_time']) && $values['timeout_time'] < 0) {
            return _('Timeout must be 0 or greater');
        }
        foreach (['invalid_loops', 'timeout_loops'] as $key) {
            if (isset($values[$key]) && !$this->isValidLoops($values[$key])) {
                return _('Loops must be between 0 and 10 or disabled');
            }
        }
        if (isset($values['strict_dial_timeout'])) {
            $strictDialTimeout = $values['strict_dial_timeout'];
            if (!is_numeric($strictDialTimeout) || !in_array((int)$strictDialTimeout, [0, 1, 2], true)) {
                return _('Strict dial timeout must be 0, 1 or 2');
            }
        }
        foreach (['invalid_destination', 'timeout_destination'] as $key) {
            if (isset($values[$key]) && !$this->isValidDestination($values[$key], self::DESTINATION_MAX_LENGTH)) {
                return _('Invalid destination format');
            }
        }
        if (is_array($entries)) {
            $selections = [];
            foreach ($entries as $entry) {
                $selection = isset($entry['selection']) ? (string)$entry['selection'] : '';
                if (!$this->isValidSelection($selection)) {
                    return _('Invalid entry selection');
                }
                $destination = isset($entry['dest']) ? $entry['dest'] : '';
                if (!$this->isValidDestination($destination, self::ENTRY_DESTINATION_MAX_LENGTH)) {
                    return _('Invalid entry destination');
                }
                $selections[] = $selection;
            }
            if (count($selections) !== count(array_unique($selections))) {
                return _('Duplicate entry selections');
            }
        }
        return null;
    }

    private function isValidSelection($value)
    {
        if (in_array($value, self::RESERVED_SELECTIONS, true)) {
            return true;
        }
        if (strlen($value) > self::SELECTION_MAX_LENGTH) {
            return false;
        }
        if (!preg_match('/\A[-0-9\[\]+.|ZzXxNn*#_!\/]+\z/', $value)) {
            return false;
        }
        return strlen($this->countableDigits($value)) <= self::SELECTION_MAX_DIGITS;
    }

    private function countableDigits($selection)
    {
        // mirrors the length check the IVR entry grid applies before it submits
        $withoutRanges = preg_replace('/\[(.+?)\]/', '0', $selection);
        return preg_replace('/[_\-]/', '', $withoutRanges, 1);
    }

    private function isValidLoops($value)
    {
        if ($value === 'disabled') {
            return true;
        }
        if (!preg_match('/^\d+$/', (string)$value)) {
            return false;
        }
        return (int)$value >= 0 && (int)$value <= 10;
    }

    private function isValidDestination($value, $maxLength)
    {
        $value = (string)$value;
        if (strlen($value) > $maxLength) {
            return false;
        }
        return (bool)preg_match('/\A[a-zA-Z0-9_\-]+,[^,\r\n]+,[^,\r\n]+\z/', $value);
    }

    private function containsLineBreak($values)
    {
        foreach ($values as $value) {
            if (is_string($value) && preg_match('/[\r\n]/', $value)) {
                return true;
            }
        }
        return false;
    }
}
