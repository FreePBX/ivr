<?php
namespace FreePBX\modules;
class Ivr extends \FreePBX_Helpers implements \BMO {
	private $temp = null;
	private $db = null;
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
		$s = ini_get("default_charset");
		$sql = 'SELECT * FROM ivr_details';
		if ($id) {
			$sql .= ' where  id = :id ';
		}
		$sql .= ' ORDER BY name';

		$sth = $this->Database->prepare($sql);
		$sth->execute(array(":id" => $id));
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
				if(empty($request['id']) && empty($request['action'])){
					$buttons = NULL;
				}
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
							$dname = pathinfo($dname,PATHINFO_FILENAME);
							$id = time().rand(1,1000);
							$name = $dname . '-' . $id . '.' . $extension;
							move_uploaded_file($tmp_name, $this->temp."/".$name);
							return array("status" => true, "filename" => pathinfo($dname,PATHINFO_FILENAME), "localfilename" => $name, "id" => $id);
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
	public function getRightNav($request) {
		if(isset($request['action']) && $request['action'] == 'edit' || $request['action'] == 'add'){
    	return load_view(__DIR__."/views/rnav.php",array('request' => $request));
		}
	}
}
