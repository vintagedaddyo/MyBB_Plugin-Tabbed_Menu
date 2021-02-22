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
 
$plugins->add_hook("admin_forum_menu", "tab_menu_admin_nav");
$plugins->add_hook("admin_forum_action_handler", "tab_menu_action_handler");
$plugins->add_hook("index_end", "tab_menu_list");
$plugins->add_hook('admin_forum_permissions', 'tab_menu_admin_permissions');


function tab_menu_info()
{

	return array(
		'name'			=> 'Tabbed Menu',
		'description'	=> "Adds tabbed menu to your index page.",
		'website'		=> 'http://www.mybbcentral.com',
		'author'		=> 'Jesse Labrocca & minor 1.8 edits by Vintagedaddyo',
		'authorsite'	=> 'http://www.webmasterforums.biz',
		'version'		=> '2.0.2',
		"guid" 			=> "",
		"compatibility" => "16*,18*"
	);
}

function tab_menu_install()
{
	global $db;
	
	$db->query("CREATE TABLE ".TABLE_PREFIX."tabmenu (`tabid` smallint(3) NOT NULL auto_increment,`tabname` varchar(64) NOT NULL, `tabcats` varchar(255) NOT NULL, PRIMARY KEY  (`tabid`)) ENGINE=MyISAM;");
	
	$style= array("attachedto" => "index.php", "lastmodified" => TIME_NOW, "tid" => "1", "name" => "tabbed.css", "cachefile" => "tabbed.css","stylesheet" => ".shadetabs{
padding: 3px 0;
margin-left: 10px;
margin-top: 1px;
margin-bottom: 0;
font: bold 12px Verdana;
list-style-type: none;
text-align: left; /*set to left, center, or right to align the menu as desired*/
}

.shadetabs li{
display: inline;
margin: 0;
}

.shadetabs li a{
text-decoration: none;
position: relative;
z-index: 1;
padding: 3px 7px;
margin-right: 3px;
color: #000000;	
background: #DDDDDD;	
border: 1px solid #BBBBBB;
border-top-left-radius: 6px;
border-top-right-radius: 6px;
}

.shadetabs li a:hover{
text-decoration: underline;
}

.shadetabs li a.selected{ /*selected main tab style */
position: relative;
top: 1px;
}

.shadetabs li a.selected{ /*selected main tab style */
color: #FFFFFF;	
background:#007ED5;		
border-bottom-color: #007ED5;
}

.shadetabs li a.selected:hover{ /*selected main tab style */
text-decoration: none;
}

.tabcontent{
display:none;
}

@media print {
.tabcontent {
display:block !important;
}
}
");

	$db->insert_query("themestylesheets", $style);

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	update_theme_stylesheet_list("1");

}

function tab_menu_is_installed()
{
	global $db;
	
	if($db->table_exists("tabmenu"))
	{
		return true;
	}
	
	return false;
}

function tab_menu_activate()
{
	global $mybb;

	tab_menu_cache();
	
	require "../inc/adminfunctions_templates.php";

	find_replace_templatesets("forumbit_depth1_cat", '#<table border#', '<div id="tabmenu_{$forum[\'fid\']}"> <table border');

	find_replace_templatesets("forumbit_depth1_cat", '#</table>(\n?)<br />#', "</table>\n<br /></div>");

	find_replace_templatesets("index", '#header}#', 'header}{$tabmenu}');

	find_replace_templatesets("index", '#forums}#', 'forums}
<script type="text/javascript">
<!--//
var myflowers=new ddtabcontent("menutabs")
myflowers.setpersist(true)
myflowers.init()
-->
</script>
');

	find_replace_templatesets("headerinclude", '#stylesheets}#', 'stylesheets}
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/tabcontent.js">
/***********************************************
* Tab Content script v2.2- copyright Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/
</script>');

}

function tab_menu_deactivate()
{
	global $mybb;

	require "../inc/adminfunctions_templates.php";
	find_replace_templatesets("forumbit_depth1_cat", '#'.preg_quote('<div id="tabmenu_{$forum[\'fid\']}">').'#', '',0);
	find_replace_templatesets("forumbit_depth1_cat", '#'.preg_quote('<br /></div>').'#', '<br />',0);

	find_replace_templatesets("index", '#'.preg_quote('{$tabmenu}').'#', '',0);

	find_replace_templatesets("index", '#'.preg_quote('<script type="text/javascript">
<!--//
var myflowers=new ddtabcontent("menutabs")
myflowers.setpersist(true)
myflowers.init()
-->
</script>').'#', '',0);

	find_replace_templatesets("headerinclude", '#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/tabcontent.js">
/***********************************************
* Tab Content script v2.2- copyright Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/
</script>').'#', '',0);


}

function tab_menu_uninstall()
{
	global $db;
	
	if($db->table_exists("tabmenu")){
		$db->query("DROP TABLE ".TABLE_PREFIX."tabmenu");
	}
	
	$query = $db->simple_select("themestylesheets", "sid", "name='tabbed.css'");
	
	if($db->num_rows($query) > 0){
		$db->delete_query("themestylesheets", "name='tabbed.css'");
	}
	
	$query = $db->simple_select("datacache", "cache", "title='tabmenu'");
	
	if($db->num_rows($query) > 0){
		$db->delete_query("datacache", "title='tabmenu'");
	}

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	update_theme_stylesheet_list("1");
	@unlink(MYBB_ROOT."cache/themes/theme1/tabbed.css");
}

function tab_menu_admin_permissions(&$admin_permissions)
{
	global $db, $mybb;
  
	if($db->table_exists("tabmenu"))
	{
		$admin_permissions['tabmenu'] = "Can manage tab menu?";
	}

	return $admin_permissions;
}

function tab_menu_action_handler(&$action)
{
	$action['tabmenu'] = array('active' => 'tabmenu', 'file' => 'tabmenu.php');
	
	return $action;
}



function tab_menu_admin_nav(&$sub_menu)
{
	global $mybb;
	
	end($sub_menu);
	$key = (key($sub_menu))+10;
	
	if(!$key)
	{
		$key = '60';
	}
	
	$sub_menu[$key] = array('id' => 'tab_menu', 'title' => 'Tab Menu', 'link' => "index.php?module=forum-tabmenu");
	
	return $sub_menu;

}


function tab_menu_list()
{

global $cache, $db, $mybb, $tabmenu, $forum_cache;


	if(!is_array($forum_cache))
	{
		cache_forums();
	}

	$unviewableforums = array();

	foreach($forum_cache as $fid => $forum)
	{

		$perms = forum_permissions($forum['fid'], $mybb->user['uid'], $mybb->user['usergroup']);
		$pwverified = 1;

		if($forum['password'] != "")
		{
			if($_COOKIE['forumpass'][$forum['fid']] != md5($mybb->user['uid'].$forum['password']))
			{
				$pwverified = 0;
			}
		}

		if($perms['canview'] == "0" || $pwverified == 0)
		{

			$unviewableforums[] = $forum['fid'];
		}
	}

$tabmenu= "<ul id=\"menutabs\" class=\"shadetabs\">";

  $tabs_cache = $cache->read("tabmenu");
  
  if(is_array($tabs_cache)){
	  foreach($tabs_cache as $results)
	  {
		$tabs = explode(",", $results['tabcats']);

		$revtabs = "";

		while (list($key, $val) = each($tabs)) {


	if ($key != '0' && !in_array($val, $unviewableforums)){
			
			$revtabs .= "tabmenu_".$val.",";
	}
		}

		$revtabs = rtrim($revtabs,",");

		if ($results['tabid'] == '1'){

			$selected = "class=\"selected\"";

		$tabmenu .= "<li><a href=\"#\" rel=\"tabmenu_{$tabs[0]}\" rev=\"{$revtabs}\" {$selected}>{$results['tabname']}</a></li>";

		}else{
		$tabmenu .= "<li><a href=\"#\" rel=\"tabmenu_{$tabs[0]}\" rev=\"{$revtabs}\">{$results['tabname']}</a></li>";
		}

	  }
	}

$tabmenu .= "</ul>";

}

function tab_menu_cache()
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

?>