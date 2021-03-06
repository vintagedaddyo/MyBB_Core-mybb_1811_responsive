<?php
define("IN_MYBB", 1);
define("IN_ADMINCP", 1);
require_once "../../../inc/init.php";

if($mybb->request_method != "post") {
	die("Direct initialization of this file is not allowed.");
}

if(!isset($cp_language))
{
	if(!file_exists(MYBB_ROOT."inc/languages/".$mybb->settings['cplanguage']."/admin/file_manager.lang.php"))
	{
		$mybb->settings['cplanguage'] = "english";
	}
	$lang->set_language($mybb->settings['cplanguage'], "admin");
}


$lang->load("file_manager");

$data = array();
if(isset($mybb->input['directory'])) {
	$dir = htmlspecialchars_uni($mybb->input['directory']);
	$dir = MYBB_ROOT.$dir;
} else {
	$dir = MYBB_ROOT;
}

if(isset($mybb->input['file']) && !empty($mybb->input['file'])) {
	$file = htmlspecialchars_uni($mybb->input['file']);
	$file_path = $dir."/".$file;
    
	if(file_exists($file_path) && !is_dir($file_path)){
		if(isset($mybb->input['file_content'])) {
			$content = trim($mybb->input['file_content']);
		} else {
			$content = "";	
		}

		$fopen = fopen($file_path, "w");
		fwrite($fopen, $content);
		fclose($fopen);

		$data['type'] = 'success';
		$data['msg'] = $lang->file_success_edit;
    } else {
        $data['type'] = 'error';
        $data['msg'] = $lang->file_error_exists;
    }
} else {
    $data['type'] = 'error';
    $data['msg'] = $lang->file_error_edit_file;
}

echo json_encode($data);