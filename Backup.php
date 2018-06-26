<?php
namespace FreePBX\modules\Ivr;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $this->addDependency('core');
    $this->addDependency('recordings');
    $congfigs = [
        'ivrs' => $this->FreePBX->Ivr->getDetails(),
        'entries' => $this->FreePBX->Ivr->getAllEntries(),
    ];
    $this->addConfigs($this->FreePBX->Ivr->getDetails());
  }
}