<?php
namespace FreePBX\modules;
class Ivr extends \FreePBX_Helpers implements \BMO {
	public function install() {}
	public function uninstall() {}
	public function backup() {}
	public function restore($backup) {}
	public function doConfigPageInit($page) {}
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
			return $res;
		}

	}
}
