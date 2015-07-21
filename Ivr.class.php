<?php
namespace FreePBX\modules;
class Ivr extends \FreePBX_Helpers implements \BMO {
	public function install() {}
	public function uninstall() {}
	public function backup() {}
	public function restore($backup) {}
	public function doConfigPageInit($page) {

	}
	public function search($query, &$results) {
		$ivrs = $this->getDetails();
		foreach ($ivrs as $ivr) {
			$results[] = array(
				"text" => _("IVR").": ".$ivr['name'],
				"type" => "get",
				"dest" => "?display=ivr&action=edit&id=".$ivr['id']
			);
		}
	}

	public function getDetails($id = false) {
		$sql = 'SELECT * FROM ivr_details';
		if ($id) {
			$sql .= ' where  id = :id ';
		}
		$sql .= ' ORDER BY name';

		$sth = $this->Database->prepare($sql);
		$sth->execute(array(":id" => $id));
		$res = $sth->fetchAll();
		if ($id && isset($res[0])) {
			return $res[0];
		} else {
			$res = is_array($res)?$res:array();
			return $res;
		}
	}
	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			case 'ivr':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['id'])) {
					unset($buttons['delete']);
				}
				isset($request['action'])?'':$buttons = NULL;
			break;
		}
		return $buttons;
	}
	public function pageHook($request){
		return \FreePBX::Hooks()->processHooks($request);
	}
	public function ajaxRequest($req, &$setting) {
	switch ($req) {
		case 'getJSON':
			return true;
		break;
		default:
			return false;
		break;
	}
}
public function ajaxHandler(){
	switch ($_REQUEST['command']) {
		case 'getJSON':
			switch ($_REQUEST['jdata']) {
				case 'grid':
					$ivrs = $this->getDetails();
					$ret = array();
					foreach ($ivrs as $r) {
						$r['name'] = $r['name'] ? $r['name'] : 'IVR ID: ' . $r['id'];
						$ret[] = array(
								'name' => $r['name'],
								'id' => $r['id'],
								'link' => array($r['id'],$r['name'])
							);
					}
					return $ret;
					break;
					default:
						return false;
					break;
				}
			break;
			default:
				return false;
			break;
		}
	}
}
