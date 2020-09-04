<?php
namespace FreePBX\modules\Ivr;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		foreach ($configs['ivrs'] as $id => $ivr) {
			$this->FreePBX->Ivr->saveDetail($ivr['0']);
		}

		foreach($configs['entries'] as $id => $entry) {
			$this->FreePBX->Ivr->saveEntry($id, $entry);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyDatabase($pdo);
		$this->FreePBX->Database->query("UPDATE `ivr_details` SET `strict_dial_timeout`=2 WHERE `strict_dial_timeout`=0"); //changing Force Strict Dial Timeout to No_Legacy if No.
	}
}

