<?php
/**
 * Copyright 2011 Jesse LaBrocca, All Rights Reserved
 * Plugin: Tabbed Menu v2.0.2
 * Website: http://mybbcentral.com
 * License: http://mybbcentral.com/license.php
 *
 */ 


// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    $init_check = "&#77-121-98-98-32-67-101-110-116-114-97-108;";
    $init_error = str_replace("-",";&#", $init_check);
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />". $init_error);
} 


// Load language packs for this section
$lang->load("forum_tabmenu");

$page->add_breadcrumb_item($lang->tab_main, "index.php?module=forum-tabmenu");

	switch ($mybb->input['action'])
	{

	case "tabmenu_edit":
	$nav = "tabmenu_edit";
    break;
	case "tabmenu_add":
	$nav = "tabmenu_add";
    break;
	default:
    $nav = "tabmenu_home";

	}

log_admin_action();

	$page->output_header($lang->tabmenu_admin_index);
	
	$sub_tabs['tabmenu_home'] = array(
		'title' => $lang->tab_admin_sub_home,
		'link' => "index.php?module=forum-tabmenu",
		'description' => $lang->tabmenu_admin_sub_home_desc
	);

	$sub_tabs['tabmenu_add'] = array(
		'title' => $lang->tab_admin_sub_add,
		'link' => "index.php?module=forum-tabmenu&amp;action=tabmenu_add",
		'description' => $lang->tabmenu_admin_sub_add_desc
	);

	$sub_tabs['tabmenu_edit'] = array(
		'title' => $lang->tab_admin_sub_edit,
		'link' => "index.php?module=forum-tabmenu&amp;action=tabmenu_edit",
		'description' => $lang->tabmenu_admin_sub_add_edit
	);

	$page->output_nav_tabs($sub_tabs, $nav);


if($page->active_action != "tabmenu"){	return; }


if($mybb->input['action'] == "tabmenu_add")
{

	$form = new Form("index.php?module=forum-tabmenu&amp;action=tabmenu_add_save", "post", "tabmenu_add_save",1);
	$form_container = new FormContainer($lang->edit_header);

	$form_container->output_row_header($lang->create, array('class' => 'align_left', 'width' => '85%'));	

	$form_container->output_row($lang->name, "", $form->generate_text_box('tabname', '', array('id' => 'tabname')), 'tabname');

	$query = $db->simple_select("tabmenu", "*");
	
	$tabcatslist = "";
	
	while($tab = $db->fetch_array($query)){
		$tabcatslist .= "{$tab['tabcats']},";
	}
	
	$tabcatslist = rtrim($tabcatslist,", ");
	$tabcatslist = explode(",",str_replace(" ","",$tabcatslist));
	
		
	$forum_cache = cache_forums();
	foreach($forum_cache as $forum)
	{
		if($forum['type'] == "c" && !in_array($forum['fid'], $tabcatslist))
		{
			$options[$forum['fid']] = $forum['name'];
		}
	}
	
	
	if(!empty($options)){
		$form_container->output_row("Select", $lang->edit_select, $form->generate_select_box('tabforums[]', $options, "tabforums", array('id' => 'tabforums', 'multiple' => true, 'size' => 5)), 'tabforums');
	} else {
		$form_container->output_row("", "", "<em>{$lang->no_available}</em>");
	}

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->submit);
	$form->output_submit_wrapper($buttons);
	$form->end();

}


if($mybb->input['action'] == "tabmenu_edit")
{

	if(!isset($mybb->input['tabid'])) 
	{

		flash_message($lang->tabid_error, 'error');
		admin_redirect("index.php?module=forum-tabmenu");
	}

		$form = new Form("index.php?module=forum-tabmenu&amp;action=tabmenu_save", "post", "tabmenu_save",1);
		$form_container = new FormContainer($lang->edit_header);

	echo $form->generate_hidden_field("tabid", intval($mybb->input['tabid']));

	$query = $db->simple_select("tabmenu", "*");
	
	$tabcatslist = "";
	
	while($tab = $db->fetch_array($query)){
		if($tab['tabid']==intval($mybb->input['tabid'])){
			$thistabname = $tab['tabname'];
		} else {
			$tabcatslist .= "{$tab['tabcats']},";
		}
	}
	
	$tabcatslist = rtrim($tabcatslist,", ");
	$tabcatslist = explode(",",str_replace(" ","",$tabcatslist));
	
	$form_container->output_row_header($thistabname, array('class' => 'align_left', 'width' => '85%'));	

	$form_container->output_row($lang->name, "", $form->generate_text_box('tabname', $thistabname, array('id' => 'tabname')), 'tabname');


	$forum_cache = cache_forums();
	foreach($forum_cache as $forum)
	{
		if($forum['type'] == "c" && !in_array($forum['fid'], $tabcatslist))
		{
			$options[$forum['fid']] = $forum['name'];
		}
	}
	
	if(!empty($options)){
		$form_container->output_row("Select", $lang->edit_select, $form->generate_select_box('tabforums[]', $options, "tabforums", array('id' => 'tabforums', 'multiple' => true, 'size' => 5)), 'tabforums');
	} else {
		$form_container->output_row("", "", "<em>{$lang->no_available}</em>");
	}
	
	$form_container->output_row($lang->cat_overwrite, $lang->overwrite_cats, $form->generate_check_box('keeptabs', 1, "<span style='font-weight:normal'>Keep category settings.</span>", array('checked' => 1, 'id' => 'keeptabs')));

		$form_container->end();
	
		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();

}



if($mybb->input['action'] == "tabmenu_delete")
{

	$db->delete_query("tabmenu", "tabid='".intval($mybb->input['tabid'])."'");
	
	tabs_cache();
	flash_message($lang->delete_success, 'success');
	admin_redirect("index.php?module=forum-tabmenu");

}



if($mybb->input['action'] == "tabmenu_save")
{

	if(!isset($mybb->input['tabid']))
	{
		flash_message($lang->tabid_error, 'error');
		admin_redirect("index.php?module=forum-tabmenu");
	}else{
		$tabid = intval($mybb->input['tabid']);
	}
	
	if(empty($mybb->input['tabname'])){
		flash_message($lang->tabname_error, 'error');
		admin_redirect("index.php?module=forum-tabmenu&action=tabmenu_edit&tabid={$tabid}");
	}
	
	if(empty($mybb->input['tabforums']) && empty($mybb->input['keeptabs'])){
		flash_message($lang->tabforums_error, 'error');
		admin_redirect("index.php?module=forum-tabmenu&action=tabmenu_edit&tabid={$tabid}");
	}

	$query = $db->simple_select("tabmenu", "*");
	while($checktabs = $db->fetch_array($query)){
		if($checktabs['tabid'] == $tabid){
			continue;
		}
		$allowed = explode(",",str_replace(" ","",$checktabs['tabcats']));
		if(!is_null($mybb->input['tabforums'])){
			foreach ($mybb->input['tabforums'] as $tabcat) {
				if (in_array($tabcat, $allowed)) {
					flash_message($lang->already_assigned, 'error');
					admin_redirect("index.php?module=forum-tabmenu");
				}
			}
		}
	}
	
	
	if(is_array($mybb->input['tabforums']))
	{
		$tabs = implode(",", $mybb->input['tabforums']);
	}

	if(is_null($mybb->input['keeptabs'])){
		$tab_update = array(
			"tabcats" => $tabs,
			"tabname" => $db->escape_string($mybb->input['tabname']),
		);
	} else {
		$tab_update = array(
			"tabname" => $db->escape_string($mybb->input['tabname']),
		);
	}

	
	$db->update_query("tabmenu", $tab_update, "tabid='{$tabid}'");
	
	tabs_cache();
	flash_message($lang->save_success, 'success');
	admin_redirect("index.php?module=forum-tabmenu");

}

if($mybb->input['action'] == "tabmenu_add_save")
{

		if(is_array($mybb->input['tabforums']))
		{
			$tabs = implode(",", $mybb->input['tabforums']);
		}
		
		if(empty($mybb->input['tabname'])){
			flash_message($lang->tabname_error, 'error');
			admin_redirect("index.php?module=forum-tabmenu&action=tabmenu_add");
		}
		
		if(empty($mybb->input['tabforums'])){
			flash_message($lang->tabforums_error, 'error');
			admin_redirect("index.php?module=forum-tabmenu&action=tabmenu_edit&tabid={$tabid}");
		}
		
		$query = $db->simple_select("tabmenu", "*");
		while($checktabs = $db->fetch_array($query)){
			$allowed = explode(",",str_replace(" ","",$checktabs['tabcats']));
			if(!is_null($mybb->input['tabforums'])){
				foreach ($mybb->input['tabforums'] as $tabcat) {
					if (in_array($tabcat, $allowed)) {
						flash_message($lang->already_assigned, 'error');
						admin_redirect("index.php?module=forum-tabmenu");
					}
				}
			}
		}
			
			$tab_insert = array(
				"tabcats" => $tabs,
				"tabname" => $db->escape_string($mybb->input['tabname']),

			);
			$db->insert_query("tabmenu", $tab_insert);
		
		tabs_cache();
		flash_message($lang->add_save_success, 'success');
		admin_redirect("index.php?module=forum-tabmenu");

}

if(!$mybb->input['action'])
{

	$table = new Table;

	$table->construct_header($lang->name, array('class' => 'align_left', 'width' => '25%'));
	$table->construct_header($lang->categories, array('class' => 'align_left', 'width' => '55%'));
	$table->construct_header($lang->options, array('class' => 'align_center', 'colspan' => '2'));
	
	$tabcatlist = "";
	
	$query = $db->simple_select("tabmenu", "*");
	while($tabmenu = $db->fetch_array($query))
	{
		$tabcatnames = "";
		$tabcats = explode(',', $tabmenu['tabcats']);
		
		$tabcatlist .= "{$tabmenu['tabcats']},";
		
		$forum_cache = cache_forums();
		foreach($forum_cache as $forum)
		{
			if(in_array($forum['fid'],$tabcats))
			{
			$tabcatnames .= $forum['name'].", ";
			}
		}

		$tabcatnames = rtrim($tabcatnames,", ");

			$table->construct_cell("<div><strong>{$tabmenu['tabname']}</strong></div>");
			$table->construct_cell("<div>{$tabcatnames}</div>");
			$table->construct_cell("<div><a href=\"index.php?module=forum-tabmenu&amp;action=tabmenu_delete&amp;tabid={$tabmenu['tabid']}\">$lang->delete</a></div>",array('class' => 'align_center'));
			$table->construct_cell("<div><a href=\"index.php?module=forum-tabmenu&amp;action=tabmenu_edit&amp;tabid={$tabmenu['tabid']}\">$lang->edit</a></div>",array('class' => 'align_center'));

			$table->construct_row();
	}
	
	$tabcatlist = explode(",",str_replace(" ","",$tabcatlist));
		
	$unusedcats = "";
	
	$forum_cache = cache_forums();
	foreach($forum_cache as $forum)
	{
		if($forum['type'] == "c")
		{
			if(!in_array($forum['fid'], $tabcatlist))
			{
				$unusedcats .= $forum['name'].", ";
			}
		}
	}
	
	if($unusedcats !== ""){
		echo "<div id=\"flash_message\" class=\"error\">
				$lang->unassigned_cats
			</div>";
		
		if($db->num_rows($query) > 0){
			$table->construct_cell("<div>-</div>", array('class' => 'align_center', 'colspan' => '4'));
			$table->construct_row();
		}
		
		$unusedcats = rtrim($unusedcats,", ");
		$table->construct_cell("<div><strong><em>$lang->unassigned</em></strong></div>");
		$table->construct_cell("<div>{$unusedcats}</div>", array('class' => 'align_left', 'colspan' => '3'));
		$table->construct_row();
	}
	
	$table->output($lang->tab_main);

}

function tabs_cache()
{
	global $db, $cache;
	
	$tabbed = array();
	
		$query = $db->simple_select("tabmenu", "*");
		while($data = $db->fetch_array($query))
		{
			$tabbed[$data['tabid']] = array("tabid"=>$data['tabid'],"tabname"=>$data['tabname'],"tabcats"=>$data['tabcats']);
		}

	$cache->update("tabmenu", $tabbed);

} 

		$page->output_footer();

?>