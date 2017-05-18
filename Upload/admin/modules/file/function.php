<?php
function get_file_extension($ext){
	switch($ext) {
		case "html":
		case "htm":
			return "html";
		case "php":
			return "php";
		case "css":
			return "css";
		case "js":
			return "js";
		case "xml":
			return "xml";
		case "png":
			return "png";
		case "gif":
			return "gif";
		case "bmp":
			return "bmp";
		case "jpg":
		case "jpeg":
			return "jpeg";
		case "rar":
			return "rar";
		case "zip":
			return "zip";
		default:
			return "other";
	}
}

function is_editable($file) {
	$ext = pathinfo($file);
	$ext = $ext['extension'];
	if(in_array($ext, array("php", "html", "htm", "xml", "htaccess", "js", "css", "txt", ""))) {
		return true;
		exit;
	}

	return false;
}

function get_codemirror_type($ext){
	switch($ext) {
		case "php":
			return "application/x-httpd-php";
		case "css":
			return "text/css";
		case "js":
			return "text/javascript";
		default:
			return "text/html";
	}
}

function removedir($directory) {
	foreach(scandir($directory) as $f) {
		if(in_array($f, array(".", ".."))) continue;
		if (is_dir($directory.'/'.$f)) {
			removedir($directory.'/'.$f);
		} else {
			unlink($directory.'/'.$f);
		}
	}
	return rmdir($directory);
}