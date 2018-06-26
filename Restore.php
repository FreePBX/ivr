<?php
namespace FreePBX\modules\Ivr;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    foreach ($configs['ivrs'] as $ivr) {
        $this->FreePBX->Ivr->saveDetails($ivr);
    }
    foreach($configs['entries'] as $id => $entries){
        $this->FreePBX->Ivr->saveEntries($id, $enrties);
    }
  }
}