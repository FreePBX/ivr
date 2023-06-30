<?php
namespace FreePBX\modules;
use FreePBX_Helpers;
use BMO;
use PDO;
class Ivr extends FreePBX_Helpers implements BMO {
	private $temp = null;
	private $db = null;
	const DEFAULTS = [
		'display' => '',
		'action' => '',
		'id' => '',
		'invalid_destination' => 'app-blackhole,hangup,1',
		'timeout_destination' => 'app-blackhole,hangup,1',
		'name' => 'New IVR',
		'description' => '',
		'announcement' => '',
		'directdial' => 'ext-local',
		'timeout_time' => 10,
		'alertinfo' => '',
		'rvolume' => '',
		'invalid_loops' => 3,
		'invalid_retry_recording' => 'default',
		'invalid_append_announce' => 0,
		'invalid_ivr_ret' => 0,
		'invalid_recording' => '',
		'timeout_loops' => 3,
		'timeout_retry_recording' => 'default',
		'timeout_append_announce' => 0,
		'timeout_ivr_ret' => 0,
		'strict_dial_timeout' => 2,
		'timeout_recording' => 'default',
		'retvm' => '',
		'entries' => [],
	];
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->temp = $this->FreePBX->Config->get("ASTSPOOLDIR") . "/tmp";
		if(!file_exists($this->temp)) {
			mkdir($this->temp,0777,true);
		}
	}

	public function setDatabase($pdo){
		$this->db = $pdo;
		return $this;
	}

	public function resetDatabase(){
		$this->db = $this->FreePBX->Database;
		return $this;
	}

	public function install() {

	}
	public function uninstall() {

	}

	public function doConfigPageInit($page) {

		foreach (self::DEFAULTS as $key => $value) {
			$vars[$key] = isset($_REQUEST[$key])?$_REQUEST[$key]:$value;
		}
		$bools = [ "invalid_append_announce", "timeout_append_announce", "timeout_ivr_ret", "invalid_ivr_ret" ];
		foreach ($bools as $b) {
			// If they're NOT empty, set them to (int)1. If they ARE empty, set them to (int)0.
			$vars[$b] = (int) !empty($vars[$b]);
		}
		// If announcement is 'None', that's actually NULL.
		if (isset($vars['announcement']) && !is_numeric($vars['announcement'])) {
			$vars['announcement'] = null;
		}
		if($vars['action'] === 'delete'){
			needreload();
			return $this->delete($vars['id']);
		}
		if($vars['action'] === 'save'){
			needreload();
			$id = $this->saveDetails($vars);
			$this->saveEntries($id,$vars['entries']);
			$this_dest = ivr_getdest($id);
			\fwmsg::set_dest($this_dest[0]);
		}
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
	public function getallivrsname($id){
		$sql = 'SELECT name FROM ivr_details';
		if ($id) {
			$sql .= ' where  id != :id ';
		}
		$sql .= ' ORDER BY name';
		$sth = $this->Database->prepare($sql);
		$sth->execute(array(":id" => $id));
		$res = array();
		$res = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
		return $res;
}

	public function delete($id){
		$this->deleteEntriesById($id)
		->deleteDetailsById($id);
	}

	public function saveDetails($vals){
		unset($vals['action']);
		unset($vals['display']);
		unset($vals['entries']);
		$keys = [];
		$placeholders = [];
		if ($vals['id']) {
			$start = 'REPLACE INTO `ivr_details`';
		} else {
			unset($vals['id']);
			$start = 'INSERT INTO `ivr_details`';
		}
		foreach($vals as $key => $value){
			$keys[] = $key;
			$placeholders[] = ':'.$key;
		}
		$keyString = rtrim(implode(',',$keys),',');
		$placeString = rtrim(implode(',',$placeholders),',');
		$sql = sprintf('%s (%s) VALUES (%s)', $start, $keyString, $placeString);
		$this->db->prepare($sql)->execute($vals);
		// Was this a new one?
		if (!isset($vals['id'])) {
			$vals['id'] = $this->db->lastInsertId('id');
		}
		return $vals['id'];
	}

	public function deleteDetailsById($id){
		$this->db->prepare('DELETE FROM ivr_details WHERE id = :id')->execute([':id' => $id]);
		return $this;
	}

	public function getDetails($id = false) {
		$s = ini_get("default_charset");
		$sql = 'SELECT * FROM ivr_details';
		$bindvalues = array();
		if ($id) {
			$sql .= ' where  id = :id ';
			$bindvalues[':id'] = $id;
		}
		$sql .= ' ORDER BY name';

		$sth = $this->Database->prepare($sql);
		$sth->execute($bindvalues);
		$res = $sth->fetchAll();
		if ($id && isset($res[0])) {
			$res[0]['name'] = htmlentities($res[0]['name'],ENT_COMPAT | ENT_HTML401, "UTF-8");
			$res[0]['description'] = htmlentities($res[0]['description'],ENT_COMPAT | ENT_HTML401, "UTF-8");
			return $res[0];
		} else {
			$res = is_array($res)?$res:array();
			foreach ($res as $key => $value) {
				$res[$key]['name'] = htmlentities($res[$key]['name'],ENT_COMPAT | ENT_HTML401, "UTF-8");
				$res[$key]['description'] = htmlentities($res[$key]['description'],ENT_COMPAT | ENT_HTML401, "UTF-8");
			}
			return $res;
		}
	}

	public function deleteEntriesById($id){
		$this->db->prepare('DELETE FROM ivr_entries WHERE ivr_id = :ivr_id')->execute([':ivr_id' => $id]);
		return $this;
	}

	public function saveEntries($id,$entries){
		$this->deleteEntriesById($id);
		if ($entries) {
			$entries['ivr_ret'] = array_values($entries['ivr_ret']);
			$stmt = $this->db->prepare('INSERT INTO ivr_entries VALUES (:ivr_id, :selection, :dest, :ivr_ret)');

			for ($i = 0; $i < count($entries['ext']); ++$i) {

				//make sure there is an extension & goto set - otherwise SKIP IT
				if ('' != trim($entries['ext'][$i]) && $entries['goto'][$i]) {
					$stmt->execute([
						':ivr_id' => $id,
						':selection' => $entries['ext'][$i],
						':dest' => $entries['goto'][$i],
						':ivr_ret' => (int) (isset($entries['ivr_ret'][$i]) ? $entries['ivr_ret'][$i] : '0'),
					]);
				}
			}
		}
		return true;
	}

	public function saveEntry($id,$entry) {
		$this->deleteEntriesById($id);
		if (is_array($entry) && !empty($entry)) {
			foreach($entry as $entries) {
				$stmt = $this->db->prepare('INSERT INTO ivr_entries VALUES (:ivr_id, :selection, :dest, :ivr_ret)');
				$stmt->execute([
					':ivr_id' => $entries['ivr_id'],
					':selection' => $entries['selection'],
					':dest' => $entries['dest'],
					':ivr_ret' =>(int) (isset($entries['ivr_ret']) ? $entries['ivr_ret'] : '0'),
					]);
			}
		}
		return true;
	}

	public function saveDetail($vals) {
		if (isset($vals['action'])) {
			unset($vals['action']);
		}
		if (isset($vals['display'])) {
			unset($vals['display']);
		}
		if (isset($vals['entries'])) {
			unset($vals['entries']);
		}
		$keys = [];
		$placeholders = [];
		$start = 'REPLACE INTO `ivr_details`';
		foreach($vals as $key => $value){
			$keys[] = $key;
			$placeholders[] = ':'.$key;
		}
		$keyString = rtrim(implode(',',$keys),',');
		$placeString = rtrim(implode(',',$placeholders),',');
		$sql = sprintf('%s (%s) VALUES (%s)', $start, $keyString, $placeString);
		$this->db->prepare($sql)->execute($vals);
		return $vals['id'];
	}

	public function getAllDetails() {
		$final = [];
		$all = $this->db->query('SELECT * FROM ivr_details ORDER BY name',PDO::FETCH_ASSOC);
		foreach ($all as $item) {
			$final[$item['id']][] = $item;
		}
		return $final;
	}

	public function getAllEntries(){
		$final = [];
		$all = $this->db->query('SELECT * FROM ivr_entries',PDO::FETCH_ASSOC);
		foreach ($all as $item){
			$final[$item['ivr_id']][] = $item;
		}
		return $final;
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
				'duplicate' => array(
					'name' => 'duplicate',
					'id' => 'duplicate',
					'value' => _('Duplicate')
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
			if(empty($request['id']) && empty($request['action'])){
				$buttons = NULL;
			}
			if($request['action'] == "save") {
				$buttons = NULL;
			}
			break;
		}
		return $buttons;
	}
	public function pageHook($request){
		return $this->FreePBX->Hooks->processHooks($request);
	}
	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'getJSON':
			case "savebrowserrecording":
			case 'upload':
			return true;
			break;
			default:
			return false;
			break;
		}
	}
	public function ajaxHandler(){
		switch ($_REQUEST['command']) {
			case "savebrowserrecording":
			if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
				$time = time().rand(1,1000);
				$filename = basename($_REQUEST['filename'])."-".$time.".wav";
				move_uploaded_file($_FILES["file"]["tmp_name"], $this->temp."/".$filename);
				return array("status" => true, "filename" => $_REQUEST['filename'], "localfilename" => $filename);
			}	else {
				return array("status" => false, "message" => _("Unknown Error"));
			}
			break;
			case "upload":
			foreach ($_FILES["files"]["error"] as $key => $error) {
				switch($error) {
					case UPLOAD_ERR_OK:
					$extension = pathinfo($_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
					$extension = strtolower($extension);
					$supported = $this->FreePBX->Media->getSupportedFormats();
					if(in_array($extension,$supported['in'])) {
						$tmp_name = $_FILES["files"]["tmp_name"][$key];
						$dname = \Media\Media::cleanFileName($_FILES["files"]["name"][$key]);
						$dname = basename($dname);
						$dname = pathinfo($dname,PATHINFO_FILENAME);
						$id = time().rand(1,1000);
						$name = $dname . '-' . $id . '.' . $extension;
						move_uploaded_file($tmp_name, $this->temp."/".$name);
						return array("status" => true, "filename" => $dname, "localfilename" => $name, "id" => $id);
					} else {
						return array("status" => false, "message" => _("Unsupported file format"));
						break;
					}
					break;
					case UPLOAD_ERR_INI_SIZE:
					return array("status" => false, "message" => _("The uploaded file exceeds the upload_max_filesize directive in php.ini"));
					break;
					case UPLOAD_ERR_FORM_SIZE:
					return array("status" => false, "message" => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"));
					break;
					case UPLOAD_ERR_PARTIAL:
					return array("status" => false, "message" => _("The uploaded file was only partially uploaded"));
					break;
					case UPLOAD_ERR_NO_FILE:
					return array("status" => false, "message" => _("No file was uploaded"));
					break;
					case UPLOAD_ERR_NO_TMP_DIR:
					return array("status" => false, "message" => _("Missing a temporary folder"));
					break;
					case UPLOAD_ERR_CANT_WRITE:
					return array("status" => false, "message" => _("Failed to write file to disk"));
					break;
					case UPLOAD_ERR_EXTENSION:
					return array("status" => false, "message" => _("A PHP extension stopped the file upload"));
					break;
				}
			}
			return array("status" => false, "message" => _("Can Not Find Uploaded Files"));
			break;
			case 'getJSON':
			switch ($_REQUEST['jdata']) {
				case 'grid':
				$ivrs = $this->getDetails();
				$ret = array();
				foreach ($ivrs as $r) {
					$r['name'] = $r['name'] ? $r['name'] : 'IVR ID: ' . $r['id'];
					$r['description'] = $r['description'] ? $r['description'] : '';
					$ret[] = array(
						'name' => $r['name'],
						'description' => $r['description'],
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
	public function getRightNav($request) {
		if(isset($request['action']) && ($request['action'] == 'edit' || $request['action'] == 'add')){
			return load_view(__DIR__."/views/rnav.php",array('request' => $request));
		}
	}
	public function showPage(){
		if(empty($_GET['action']) && empty($_GET['id']) || $_GET['action'] === 'delete'){
			return load_view(__DIR__ . '/views/grid.php');
		}
		$vars['ivr'] = self::DEFAULTS;
		if(!empty($_GET['id'])){
			$vars['ivr'] = $this->getDetails($_GET['id']);
		}
		return load_view(__DIR__ . '/views/form.php',$vars);
	}
}
