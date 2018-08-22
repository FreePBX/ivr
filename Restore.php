<?php
namespace FreePBX\modules\Ivr;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = reset($this->getConfigs());
    if(empty($config)){
	echo _("Backup empty").PHP_EOL;
	return;
    }
    foreach ($configs['ivrs'] as $ivr) {
        $this->FreePBX->Ivr->saveDetails($ivr);
    }
    foreach($configs['entries'] as $id => $entries){
        $this->FreePBX->Ivr->saveEntries($id, $enrties);
    }
  }
  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
      $tables = array_flip($tables+$unknownTables);
      if(!isset($tables['ivr_entries'])){
          return $this;
      }
      $bmo = $this->FreePBX->Ivr;
      $bmo->setDatabase($pdo);
      $configs = [
        'ivrs' => $this->FreePBX->Ivr->getDetails(),
        'entries' => $this->FreePBX->Ivr->getAllEntries(),
      ];
      $bmo->resetDatabase();
      foreach ($configs['ivrs'] as $ivr) {
        $bmo->saveDetails($ivr);
      }
      foreach ($configs['entries'] as $id => $entries) {
        $bmo->saveEntries($id, $enrties);
      }
      return $this;
  }  
}

