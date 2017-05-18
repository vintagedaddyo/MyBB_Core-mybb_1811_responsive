<?php
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.");
}

function file_meta(){
    global $page, $db, $mybb, $lang;

    $lang->load("file_manager");

    $menu_list = array();
	$menu_list['10'] = array("id" => "file", "title" => $lang->file_title, "link" => "index.php?module=file");

    $page->add_menu_item($lang->file_title, "file", "index.php?module=file", 100, $menu_list);

    return true;
}

function file_action_handler($action){

    global $page, $mybb;

    $page->active_module = "file";
	$page->active_action = "file";

	return "file_manager.php";
}