<?php
if(!defined("IN_MYBB"))
    die("Direct initialization of this file is not allowed");

require_once("function.php");

$lang->load('file_manager');
$page->add_breadcrumb_item($lang->file_title, "index.php?module=file");

if(isset($mybb->input['directory'])) {
	$page->add_breadcrumb_item($lang->file_nav_root, "index.php?module=file");
	$dir = htmlspecialchars_uni(str_replace(array("\\", "//"), "/", $mybb->input['directory']));
	$list_dir = explode("/", $dir);
	foreach($list_dir as $d){
		if($d == "") continue;
		$path = substr($dir, 0, strrpos($dir, $d) + strlen($d));
		$page->add_breadcrumb_item($d, "index.php?module=file&amp;directory=$path");
	}
} else {
    $dir = "";
	$page->add_breadcrumb_item($lang->file_nav_root, "index.php?module=file");
}

if($mybb->input['file'] && !empty($mybb->input['file'])){
	$file = htmlspecialchars_uni($mybb->input['file']);
	if(!empty($dir))
		$file_path = MYBB_ROOT.$dir."/".$file;
	else
		$file_path = MYBB_ROOT.$file;
}

$sub_tabs['file_manager'] = array(
	'title' => $lang->file_sub_name,
	'link' => "index.php?module=file&directory={$dir}",
	'description' => $lang->file_sub_name_desc
);
$sub_tabs['new_folder'] = array(
	'title' => $lang->file_sub_ndir,
	'link' => "index.php?module=file&action=new_folder&directory={$dir}",
	'description' => $lang->file_sub_ndir_desc
);
$sub_tabs['new_file'] = array(
	'title' => $lang->file_sub_nfile,
	'link' => "index.php?module=file&action=new_file&directory={$dir}",
	'description' => $lang->file_sub_nfile_desc
);
$sub_tabs['upload'] = array(
	'title' => $lang->file_sub_upload,
	'link' => "index.php?module=file&action=upload&directory={$dir}",
	'description' => $lang->file_sub_upload_desc
);

if($mybb->input['action'] == "upload") {
    if($mybb->request_method == "post") {
        $files = $_FILES['files'];
        $count = count($files['name']);
        if($count == 0) {
            flash_message($lang->file_upload_nofile, "error");
            admin_redirect("index.php?module=file&action=upload&directory={$dir}");
        }
        
        for($i=0;$i<$count;$i++) {
            $target = MYBB_ROOT . $dir . "/" . $files['name'][$i];   
            if(file_exists($target)) {
                $lang->file_upload_exists = $lang->sprintf($lang->file_upload_exists, $files['name'][$i]);
                flash_message($lang->file_upload_exists, "error");
                admin_redirect("index.php?module=file&action=upload&directory={$dir}");
            }
        }
        
        $up = $er = 0;
        for($i=0;$i<$count;$i++) {
            $target = MYBB_ROOT . $dir . "/" . $files['name'][$i];
            
            if(move_uploaded_file($files["tmp_name"][$i], $target))
                $up++;
        }
        
        if($up == $count) {
            flash_message($lang->file_upload_uploaded, "success");
            admin_redirect("index.php?module=file&action=upload&directory={$dir}");
        }
        else {
            $lang->file_upload_part_uploaded = $lang->sprintf($lang->file_upload_part_uploaded, $up, $count);
            flash_message($lang->file_upload_uploaded, "success");
            admin_redirect("index.php?module=file&action=upload&directory={$dir}");
        }
    }
    $page->add_breadcrumb_item($lang->file_nav_upload, "index.php?module=file&action=upload&directory={$dir}");
    
    $page->extra_header .= <<<EOT
<style>
.file__upload__preview { width: 100% }
.file__upload__preview__default  { display: none }
</style>
<script type="text/javascript">
$(document).ready(function() {
    var preview = $(".file__upload__preview");
    var default_row = preview.find(".file__upload__preview__default");
    var button = $(".file__upload__button");
    button.change(function(e) {
        preview.find(".file__upload__preview__file").remove();
        if(this.files && this.files.length > 0) {
            preview.find(".file__upload__preview__empty").hide();
            for(var i=0;i<this.files.length;i++) {
                var file = this.files[i];
                var row = "<tr class=\"file__upload__preview__file\">"+default_row.html()+"</tr>";
                row = row.replace("Name", file.name);
                size = file.size;
                type = 'B';
                if(size > 1024) {
                    size = size / 1024;
                    type = "KB";
                }
                if(size > 1024) {
                    size = size / 1024;
                    type = "MB";
                }
                if(size > 1024) {
                    size = size / 1024;
                    type = "GB";
                }
                row = row.replace("Size", size.toFixed(3)+type);
                preview.find(".file__upload__preview__empty").after(row);
            }
        }
    });
});
</script>
EOT;
    
    $page->output_header();
	$page->output_nav_tabs($sub_tabs, 'upload');
    
    $form = new Form("index.php?module=file&action=upload&directory={$dir}", "post", "upload_files", true);
    $form_container = new FormContainer($lang->file_upload_title, 'file__upload tfixed');
    
    $form_container->output_row($lang->file_upload_select, $lang->file_upload_select_desc, '<input name="files[]" class="file__upload__button" type="file" multiple="multiple">');
    $form_container->end(); 

    $buttons[] = $form->generate_submit_button($lang->file_button_upload, array('name' => 'save', 'class' => 'file__submit'));
    
    $table = new Table;
    
    $table->construct_header($lang->file_upload_file_name);
    $table->construct_header($lang->file_upload_file_size);
    
    $table->construct_cell("Name");
    $table->construct_cell("Size");
    $table->construct_row(array("class" => "file__upload__preview__default"));
    
    $table->construct_cell($lang->file_upload_files_no, array("colspan" => "2"));
    $table->construct_row(array("class" => "file__upload__preview__empty"));
    
    $table->output($lang->file_upload_files, 1, "file__upload__preview general");
    
    $form->output_submit_wrapper($buttons);
    
    $form->end();
    
    $page->output_footer();
}
if($mybb->input['action'] == "edit") {
    if(isset($file) && !empty($file)) {
        if(file_exists($file_path) && !is_dir($file_path)){
			if(is_editable($file)) {
				if($mybb->request_method == "post"){
					if($mybb->input['close']){
						admin_redirect("index.php?module=file&amp;directory=$dir");
					} else {
						$content = trim($mybb->input['file_content']);

						$fhandle = fopen($file_path, "w");
						fwrite($fhandle, $content);
						fclose($fhandle);

						flash_message($lang->file_success_edit, "success");
						admin_redirect("index.php?module=file&amp;directory=$dir");
					}
				}
				$path_parts = pathinfo($file);
				$content = file_get_contents($file_path);
				
				$lang->file_nav_edit = $lang->sprintf($lang->file_nav_edit, $file);
				$page->add_breadcrumb_item($lang->file_nav_edit, "index.php?module=file&amp;action=edit&amp;directory={$dir}&amp;file=$file");

				if($admin_options['codepress'] != 0){
					$page->extra_header .= '
<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="./jscripts/codemirror/mode/clike/clike.js"></script>
<script src="./jscripts/codemirror/mode/php/php.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">
<script type="text/javascript">
var code = 1;
$(document).ready(function() {
    var inputs = $(".file__submit");
	$("input[name=\'continue\']").click(function(event) {
		event.preventDefault();
		if(code == 1)
            content = editor.getValue();
        else
            content = document.getElementById("file_content").value;
        
        var formData = {
			\'my_post_key\' : $(\'input[name="my_post_key"]\').val(),
			\'file_content\' : content
		}
        
        inputs.prop("disabled", true);
		$.ajax({
			url: "./modules/file/quick_save.php?directory='.$dir.'&file='.$file.'",
			type: "post",
			dataType: "json",
			data: formData,
			success: function(data) {
                $.jGrowl(data.msg);
                inputs.prop("disabled", false);
			},
			error: function(data){
				$.jGrowl("'.$lang->file_error_edit.'");
                inputs.prop("disabled", false);
				console.log(data);
			}
		});
	});
});
</script>
';
				}

				$page->output_header();

				$form = new Form("index.php?module=file&amp;action=edit&amp;directory=$dir&amp;file=$file", "post", "edit_file");
				
				$lang->file_edit_title = $lang->sprintf($lang->file_edit_title, $dir, $file);
				$form_container = new FormContainer($lang->file_edit_title, 'tfixed');

				$form_container->output_row("", "", $form->generate_text_area("file_content", $content, array('id' => 'file_content', 'style' => 'width: 100%; height: 500px;')));
				$form_container->end();

				$buttons[] = '<input class="submit_button file__submit" type="button" name="continue" value="'.$lang->file_button_quick_save.'" />';
				$buttons[] = $form->generate_submit_button($lang->file_button_save, array('name' => 'save', 'class' => 'file__submit'));
				$buttons[] = $form->generate_submit_button($lang->file_button_close, array('name' => 'close', 'class' => 'file__submit'));
                $buttons[] = '<input class="submit_button file__editor" type="button" name="editorType" value="'.$lang->file_button_text.'" />';

				$form->output_submit_wrapper($buttons);

				$form->end();

                echo '<script type"text/javascript">
                    var editor = CodeMirror.fromTextArea(document.getElementById("file_content"), {
                        lineNumbers: true,
                        lineWrapping: true,
                        foldGutter: true,
                        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                        viewportMargin: Infinity,
                        indentWithTabs: true,
                        indentUnit: 4,
                        mode: "'.get_codemirror_type($path_parts['extension']).'",
                        theme: "mybb"
                    });
                    
                    $(".file__editor").click(function(e) {
                        e.preventDefault();
                        if(code == 1) {
                            editor.toTextArea();
                            code = 0;
                        }
                        else {
                            editor = CodeMirror.fromTextArea(document.getElementById("file_content"), {
                                lineNumbers: true,
                                lineWrapping: true,
                                foldGutter: true,
                                gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                                viewportMargin: Infinity,
                                indentWithTabs: true,
                                indentUnit: 4,
                                mode: "'.get_codemirror_type($path_parts['extension']).'",
                                theme: "mybb"
                            });
                            code = 1;
                        }
                    });
                </script>';

				$page->output_footer();
			} else {
				flash_message($lang->file_error_edit_type, "error");
				admin_redirect("index.php?module=file&directory=$dir");
			}
        } else {
			flash_message($lang->file_error_exists, "error");
			admin_redirect("index.php?module=file");
        }
    } else {
		flash_message($lang->file_error_edit_file, "error");
		admin_redirect("index.php?module=file");
    }
}
elseif($mybb->input['action'] == "delete") {
	if(isset($file) && !empty($file)) {
		if(file_exists($file_path) && !is_dir($file_path)) {
			if($mybb->request_method == "post") {
				if($mybb->input['delete']) {
					if(unlink($file_path)) {
						flash_message($lang->file_success_delete_file, "success");
						admin_redirect("index.php?module=file&directory=$dir");
					} else {
						flash_message($lang->file_error_delete, "error");
						admin_redirect("index.php?module=file&action=delete&directory=$dir&file=$file");
					}
				} else {
					admin_redirect("index.php?module=file&directory=$dir");
				}
			}
			$lang->file_nav_delete_file = $lang->sprintf($lang->file_nav_delete_file, $file);
			$page->add_breadcrumb_item($lang->file_nav_delete_file, "index.php?module=file&action=delete&directory=$dir&file=$file");
			$page->output_header();

			$form = new Form("index.php?module=file&amp;action=delete&amp;directory=$dir&amp;file=$file", "post", "delete_file");
			
			$lang->file_delete_file_title = $lang->sprintf($lang->file_delete_file_title, $dir, $file);
			$form_container = new FormContainer($lang->file_delete_file_title, 'tfixed');

			$form_container->output_row($lang->file_delete_file_desc, $lang->file_delete_warn);
			$form_container->end();

			$buttons[] = $form->generate_submit_button($lang->file_button_delete, array('name' => 'delete'));
			$buttons[] = $form->generate_submit_button($lang->file_button_close, array('name' => 'close'));

			$form->output_submit_wrapper($buttons);

			$form->end();

			$page->output_footer();
		} else {
			flash_message($lang->file_error_exists, "error");
			admin_redirect("index.php?module=file&directory=$dir");
		}
	} elseif(isset($dir) && !empty($dir) && empty($file)) {
		if($mybb->input['rdir']){
			$rdir = htmlspecialchars_uni($mybb->input['rdir']);
		} else {
			$rdir = "/";	
		}
		if(file_exists(MYBB_ROOT.$dir) && !empty($dir)) {
			if($dir != "/") {
				if($mybb->request_method == "post") {	
					if($mybb->input['delete'])	{
						
						if(removedir(MYBB_ROOT.$dir)) {
							flash_message($lang->file_success_delete_dir, "success");
							admin_redirect("index.php?module=file&directory=$rdir");
						} else {
							flash_message($lang->file_error_delete, "error");
							admin_redirect("index.php?module=file&directory=$rdir");
						}
					} else {
						admin_redirect("index.php?module=file&directory=$rdir");	
					}
				}
				
				$lang->file_nav_delete_dir = $lang->sprintf($lang->file_nav_delete_dir, $dir);
				$page->add_breadcrumb_item($lang->file_nav_delete_dir, "index.php?module=file&action=delete&directory=$dir");
				$page->output_header();

				$form = new Form("index.php?module=file&amp;action=delete&amp;directory=$dir&rdir=$rdir", "post", "delete_dir");
				
				$lang->file_delete_dir_title = $lang->sprintf($lang->file_delete_dir_title, $dir);
				$form_container = new FormContainer($lang->file_delete_dir_title, 'tfixed');

				$form_container->output_row($lang->file_delete_dir_desc, $lang->file_delete_warn);
				$form_container->end();

				$buttons[] = $form->generate_submit_button($lang->file_button_delete, array('name' => 'delete'));
				$buttons[] = $form->generate_submit_button($lang->file_button_close, array('name' => 'close'));

				$form->output_submit_wrapper($buttons);

				$form->end();

				$page->output_footer();
			} else {
				flash_message($lang->file_error_delete_root, "error");
				admin_redirect("index.php?module=file");
			}
		} else {
			flash_message($lang->file_error_exists_dir, "error");
			admin_redirect("index.php?module=file");
		}
	} else {
		flash_message($lang->file_error_delete_input, "error");
		admin_redirect("index.php?module=file");
	}
}
elseif($mybb->input['action'] == "new_folder"){
	if($mybb->request_method == "post"){
    	if($mybb->input['close']){
			admin_redirect("index.php?module=file&directory=$dir");	
		} elseif($mybb->input['create']) {
			if(!empty($mybb->input['folder_name'])){
				$nfolder = htmlspecialchars_uni($mybb->input['folder_name']);
				if(!file_exists(MYBB_ROOT.$dir.$nfolder."/")){
					if(mkdir(MYBB_ROOT.$dir."/".$nfolder, 0777)) {
						flash_message($lang->file_success_newdir, "success");
						admin_redirect("index.php?module=file&directory=$dir/$nfolder");
					} else {
						flash_message($lang->file_error_newdir, "error");
						admin_redirect("index.php?module=file&action=new_dir&directory=$dir");
					}
				} else {
					flash_message($lang->file_error_newdir_exists, "error");
					admin_redirect("index.php?module=file&action=new_folder&directory=$dir");	
				}
			} else {
				flash_message($lang->file_error_newdir_name, "error");
				admin_redirect("index.php?module=file&action=new_folder&directory=$dir");
			}
		}
	}
	$page->add_breadcrumb_item($lang->file_nav_newdir, "index.php?module=file&directory=$dir&action=new_folder");
	
	$page->output_header();
	$page->output_nav_tabs($sub_tabs, 'new_folder');
	
	$form = new Form("index.php?module=file&amp;action=new_folder&amp;directory=$dir", "post", "new_folder");
    
	$lang->file_newdir_title = $lang->sprintf($lang->file_newdir_title, $dir);
	$form_container = new FormContainer($lang->file_newdir_title, 'tfixed');

	$form_container->output_row($lang->file_newdir_desc, $lang->file_newdir_subdesc, $form->generate_text_box('folder_name'));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->file_button_newdir, array('name' => 'create'));
	$buttons[] = $form->generate_submit_button($lang->file_button_close, array('name' => 'close'));

	$form->output_submit_wrapper($buttons);
	$form->end();
	$page->output_footer();
}
elseif($mybb->input['action'] == "new_file"){
	if($mybb->request_method == "post"){
    	if($mybb->input['close']){
			admin_redirect("index.php?module=file&directory=$dir");	
		} elseif($mybb->input['create']) {
			if(!empty($mybb->input['file_name'])){
				$nfile = htmlspecialchars_uni($mybb->input['file_name']);
				if(!file_exists(MYBB_ROOT.$dir."/".$nfile)){
					$newfile = fopen(MYBB_ROOT.$dir."/".$nfile, "w");
					fclose($newfile);

					flash_message($lang->file_success_newfile, "success");
					admin_redirect("index.php?module=file&action=edit&directory=$dir&file=$nfile");
				} else {
					flash_message($lang->file_error_newfile_name, "error");
					admin_redirect("index.php?module=file&action=new_file&directory=$dir");	
				}
			} else {
				flash_message($lang->file_error_newfile_name, "error");
				admin_redirect("index.php?module=file&action=new_file&directory=$dir");
			}
		}
	}
	$page->add_breadcrumb_item($lang->file_nav_newfile, "index.php?module=file&directory=$dir&action=new_file");
	
	$page->output_header();
	$page->output_nav_tabs($sub_tabs, 'new_file');
	
	$form = new Form("index.php?module=file&amp;action=new_file&amp;directory=$dir", "post", "new_file");
    
	$lang->file_newfile_title = $lang->sprintf($lang->file_newfile_title, $dir);
	$form_container = new FormContainer($lang->file_newfile_title, 'tfixed');

	$form_container->output_row($lang->file_newfile_desc, $lang->file_newfile_subdesc, $form->generate_text_box('file_name'));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->file_button_newfile, array('name' => 'create'));
	$buttons[] = $form->generate_submit_button($lang->file_button_close, array('name' => 'close'));

	$form->output_submit_wrapper($buttons);
	$form->end();
	$page->output_footer();
}

if(!$mybb->input['action']) {
    $open = new DirectoryIterator(MYBB_ROOT.$dir);
    
    $directory = $files = array();
	foreach($open as $file){
        if(in_array($file->getfilename(), array(".", ".."))) continue;
        
        if($file->isDir()){
            $directory[] = array(
				'name' => $file->getfilename()
			);
        }
		
        if($file->isFile()){
			$files[] = array(
				'name' => $file->getfilename(),
				'ext' => $file->getExtension()
			);
        }
    }
    asort($directory);
    asort($files);
    
    $page->extra_header .= '
<style>
input.file__search { width: 100%; box-sizing: border-box; margin-bottom: 8px; }
.file__icon { width: 40px;height: 40px }
.file__actions__delete { color: red; font-weight: bold }
</style>
<script type="text/javascript">
var dir = "'.$dir.'";
$(document).ready(function() {
    $(".file__search").attr("placeholder", $(".file__search").attr("value")).val(\'\').focus();
    
    $(".file__search").keyup(function() {
        var search = $(this).val();
        
        $(".file").each(function(i) {
            if(i != 0) {
                row = $(this);
                var id = row.find(".file__name__tag").text();
                
                if(id.indexOf(search) != 0)
                    row.hide();
                else
                    row.show();
            }
        })
    });
    
	$(".file__actions__rename").click(function(event){
		event.preventDefault();
		var tr = $(this).parents(".file"),
            name = tr.find(".file__name__tag").text();
		tr.find(".file__name__form").remove();
		tr.find(".file__name__tag").hide();
		tr.find(".file__name").append(\'<form action="#" method="post" name="form_rename" class="file__name__form"><input type="text" name="new_name" value="\'+name+\'" class="file__name__form__input text_input"> <input type="button" name="rename"  value="'.$lang->file_button_rename.'" class="file__name__form__submit submit_button"> <input type="button" name="cancel" value="'.$lang->file_button_cancel.'" class="file__name__form__cancel submit_button"></form>\');
        
        tr.find(".file__name__form__cancel").click(function() {
			tr.find(".file__name__tag").show();
			tr.find(".file__name__form").remove();
		});
		
        tr.find(".file__name__form__input").keydown(function(e) {
            if(e.keyCode == 13) {
                e.preventDefault();
                rename(tr);
                return false;
            }
        });
        
		tr.find(".file__name__form__submit").click(function(e) {
            e.preventDefault();
            rename(tr);
        });
	});
});

function rename(tr) {
    var form = tr.find(".file__name__form");
    var dir = "'.$dir.'";
    var new_name = form.find(".file__name__form__input").val();
    
    form.find("input").prop(\'disabled\', true);
    $.ajax({
        url: "./modules/file/rename.php?directory="+dir,
        type: "post",
        dataType: "json",
        data: {
            \'new_name\' : new_name,
            \'file\' : tr.find(".file__name__tag").text()
        },
        success: function(data) {
            if(data.success) {
                $.jGrowl(data.text);
                tr.find(".file__name__form").remove();
                tr.find(".file__name__tag").show();
                tr.find(".file__icon").attr("src", data.icon);
                
                if(tr.hasClass("dir")) {
                    tr.find(".file__name__tag").attr("href", "index.php?module=file&directory="+dir+"/"+new_name).html("<strong>"+new_name+"</strong>");
                    tr.find(".file__actions__delete").attr("href", "index.php?module=file&action=delete&directory="+dir+"/"+new_name+"&rdir="+dir);
                }
                else {
                    tr.find(".file__name__tag").text(new_name);
                    tr.find(".file__actions__edit").parent().remove();
                    tr.find(".file__actions").prepend(data.edit);
                    tr.find(".file__actions__delete").attr("href", "index.php?module=file&action=delete&directory="+dir+"&file="+new_name);
                }
            }

            if(data.error) {
                $.jGrowl(data.text);
                form.find("input").prop(\'disabled\', false);
            }
        },
        error: function(data){
            $.jGrowl("'.$lang->file_error_rename.'");
            form.find("input").prop(\'disabled\', false);
        }
    });
}
</script>
';
	
    $page->output_header();
	$page->output_nav_tabs($sub_tabs, "file_manager");
	
    $search = new Form("index.php?module=file&directory={$dir}", "POST");
	echo $search->generate_text_box('search', $lang->file_search, array('class' => 'file__search'));
	$search->end();
	
    $table = new Table;
    
    $table->construct_header($lang->file_file, array("width" => "60%","colspan" => "2"));
    $table->construct_header($lang->file_modified, array("width" => "15%", "class" => "align_center"));
    $table->construct_header($lang->file_actions, array("width" => "25%", "class" => "align_center"));
    
    if (!empty($dir) && !in_array($dir, array('/', '\\', '.'))) {
        $back = dirname($dir);
        if (empty($back) || in_array($back, array('/', '\\', '.'))) {
            $back = $lang->file_nav_root;
        } else {
            $back = basename($back);
        }
        $back = $lang->sprintf($lang->file_goback, $back);
        $table->construct_cell("<img src=\"extension/return.png\" class=\"file__icon\">");
        $table->construct_cell("<a href=\"index.php?module=file&directory=".dirname($dir)."\" class=\"name\"><strong>".$back."</strong></a>", array("width" => "60%", "class" => "file_name"));
        $table->construct_cell("");
        $table->construct_cell("");
        $table->construct_row(array("class" => "file"));
    }
    
    $editTime = my_date($mybb->settings['dateformat'].' '.$mybb->settings['timeformat'], filemtime(MYBB_ROOT.$dir));
    
	foreach($directory as $d) {
		$table->construct_cell("<img src=\"extension/folder.png\" class=\"file__icon\">");
		$table->construct_cell("<a href=\"index.php?module=file&directory={$dir}/{$d['name']}\" class=\"file__name__tag\"><strong>".$d['name']."</strong></a>", array("width" => "60%", "class" => "file__name"));
        $table->construct_cell($editTime, array("width" => "15%", "class" => "file__modified align_center"));
		$table->construct_cell("<span><a href=\"#rename\" class=\"file__actions__rename\">{$lang->file_rename}</a> |</span> <span><a class=\"file__actions__delete\" href=\"index.php?module=file&action=delete&directory={$dir}/{$d['name']}&rdir={$dir}\">{$lang->file_delete}</a></span>", array("width" => "25%", "class" => "file__actions align_center"));
		$table->construct_row(array("class" => "file dir"));
	}
	
	foreach($files as $f) {
        $ext = get_file_extension($f['ext']);
        if($ext == "image")
            $ext = $mybb->settings['bburl']."/".$dir."/".$f['name'];
        else
            $ext = "extension/{$ext}.png";
		$table->construct_cell("<img src=\"{$ext}\" class=\"file__icon\">");
		$table->construct_cell("<strong class=\"file__name__tag\">{$f['name']}</strong>", array("width" => "60%", "class" => "file__name"));
        $table->construct_cell($editTime, array("width" => "15%", "class" => "file__modified align_center"));
		$action = "";
		if(is_editable($f['name']))
			$action .= "<span><a href=\"index.php?module=file&action=edit&directory={$dir}&file={$f['name']}\" class=\"file__actions__edit\">{$lang->file_edit}</a> |</span> ";
		$action .= "<span><a href=\"#rename\" class=\"file__actions__rename\">{$lang->file_rename}</a> |</span> <span><a class=\"file__actions__delete\" href=\"index.php?module=file&action=delete&directory={$dir}&file={$f['name']}\">{$lang->file_delete}</a></span>";
		$table->construct_cell($action, array("width" => "25%", "class" => "file__actions align_center"));
		$table->construct_row(array("class" => "file"));
	}
	
    $table->output($lang->file_title);
    $page->output_footer();
}