<?php
/**************************************
 * Download-System for MyBB           *
 * Version: 1.0                       *
 * Copyright © 2006-2010 StefanT      *
 * All Rights Reserved                *
 * Website: http://www.mybbcoder.info *
 **************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$download_system = new DownloadSystem;
$download_system->hooks();

/**
 * Plugin Information for the Plugin System
 *
 * @return array Plugin Information.
 */
function download_system_info()
{
	return array(
		'name' => 'Inline Downloads',
		'description' => 'A download-system based on threads',
		'website' => 'http://www.mybbcoder.info',
		'author' => 'StefanT',
		'authorsite' => 'http://www.mybbcoder.info',
		'version' => '1.0',
		'guid' => '39e98cabc9fd4a51e206af5f312f02fc',
		'compatibility' => '14*, 16*'
	);
}

function download_system_install()
{
	global $db;
	if($db->table_exists('downloads'))
	{
		$db->drop_table('downloads');
	}
	if($db->field_exists('did', 'threads'))
	{
		$db->drop_column('threads', 'did');
	}
	switch($db->type)
	{
		case "sqlite2":
		case "sqlite3":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads (
				did INTEGER PRIMARY KEY,
				tid int NOT NULL default '0',
				aid int NOT NULL default '0',
				link varchar(120) NOT NULL,
				title varchar(120) NOT NULL,
				version varchar(120) NOT NULL,
				preview varchar(120) NOT NULL,
				author varchar(120) NOT NULL,
				author_website varchar(120) NOT NULL,
				author_original varchar(120) NOT NULL,
				license text NOT NULL,
				last bigint NOT NULL,
				guestdl smallint NOT NULL default '0',
				own smallint NOT NULL default '0',
				downloads int NOT NULL default '0'
			);");
			$db->add_column("threads", "did", "int NOT NULL default '0'");
			break;
		case "pgsql":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads (
				did serial,
				tid int NOT NULL default '0',
				aid int NOT NULL default '0',
				link varchar(120) NOT NULL,
				title varchar(120) NOT NULL,
				version varchar(120) NOT NULL,
				preview varchar(120) NOT NULL,
				author varchar(120) NOT NULL,
				author_website varchar(120) NOT NULL,
				author_original varchar(120) NOT NULL,
				license text NOT NULL,
				last bigint NOT NULL,
				guestdl smallint NOT NULL default '0',
				own smallint NOT NULL default '0',
				downloads int NOT NULL default '0',
				PRIMARY KEY (did)
			);");
			$db->add_column("threads", "did", "int NOT NULL default '0'");
			break;
		default:
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads (
				did int(10) NOT NULL auto_increment,
				tid int(10) unsigned NOT NULL default '0',
				aid int(10) unsigned NOT NULL default '0',
				link varchar(120) NOT NULL,
				title varchar(120) NOT NULL,
				version varchar(120) NOT NULL,
				preview varchar(120) NOT NULL,
				author varchar(120) NOT NULL,
				author_website varchar(120) NOT NULL,
				author_original varchar(120) NOT NULL,
				license text NOT NULL,
				last bigint(30) NOT NULL,
				guestdl smallint(1) unsigned NOT NULL default '0',
				own smallint(1) unsigned NOT NULL default '0',
				downloads int(10) unsigned NOT NULL default '0',
				PRIMARY KEY (did)
				) ENGINE=MyISAM;");
				$db->add_column("threads", "did", "int unsigned NOT NULL default '0'");
	}

	$array = array(
		"title" => "download_postbit",
		"template" => $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="text-align:center;">
<tr>
<td class="trow1">{$lang->ds_title}: {$download[\'title\']}</td>
<td class="trow2">{$lang->ds_version}: {$download[\'version\']}</td>
<td class="trow1">{$lang->ds_dateline}: {$download[\'dateline\']}{$download[\'demo\']}</td>
</tr>
{$download[\'author\']}
<tr>
<td class="trow1">{$lang->ds_downloads}: {$download[\'downloads\']}</td>
<td class="trow2">{$download[\'downloadbutton\']}</td>
<td class="trow1">{$download[\'guestdl\']}</td>
</tr>
<tr><td colspan="3" class="trow2 smalltext">Download-System &copy; 2006-2010 <a href="http://www.mybbcoder.info">StefanT</a></td></tr>
</table>'),
		"sid" => "-1"
		);
	$db->insert_query('templates', $array);

	$array = array(
		"title" => "download_license",
		"template" => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$download[\'title\']} - {$lang->ds_license}</title>
{$headerinclude}
</head>
<body>
{$header}
<form method="post" action="{$threadlink}">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr><td class="thead"><strong>{$download[\'title\']} - {$lang->ds_license}</strong></td></tr>
<tr><td class="trow1">
{$download[\'license\']}
</td></tr>
<tr><td class="trow2">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="download" />
<input type="hidden" name="do" value="download" />
<input type="submit" class="submit" name="accept" value="{$lang->ds_accept}" />
<input type="submit" class="submit" name="return" value="{$lang->ds_not_accept}" />
</td></tr></table>
</form>
{$footer}
</body>
</html>'),
		"sid" => "-1"
		);
	$db->insert_query('templates', $array);

	$array = array(
		"title" => "download_newthread",
		"template" => $db->escape_string('<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr><td class="thead" colspan="2"><strong>{$lang->ds_information}</strong></td></tr>
<tr><td class="trow1"><strong>{$lang->ds_file}:</strong></td><td class="trow1"><select name="ds_file">
<option value="">$lang->ds_please_select</option>
<option value="link"{$ds_file[\'link\']}>$lang->ds_file_link</option>
{$ds_file_options}
</select ></td></tr>
<tr><td class="trow2"><strong>{$lang->ds_link_thread}:</strong></td><td class="trow2"><input type="text" class="textbox" name="ds_link" value="{$ds_link}" /></td></tr>
<tr><td class="trow1"><strong>{$lang->ds_version}:</strong></td><td class="trow1"><input type="text" class="textbox" name="ds_version" value="{$ds_version}" /></td></tr>
<tr><td class="trow2"><strong>{$lang->ds_demo} ({$lang->ds_optional}):</strong></td><td class="trow2"><input type="text" class="textbox" name="ds_preview" value="{$ds_preview}" /></td></tr>
<tr><td class="trow1"><strong>{$lang->ds_own}?</strong></td><td class="trow1"><input type="checkbox" class="checkbox" name="ds_own" value="1"{$ds_own} /></td></tr>
<tr><td class="trow2"><strong>{$lang->ds_guestdl_yes}?</strong></td><td class="trow2"><input type="checkbox" class="checkbox" name="ds_guestdl" value="1"{$ds_guestdl} /></td></tr>
{$ds_edit_time}
<tr class="tcat"><td colspan="2"><strong>{$lang->ds_author}</strong></td></tr>
<tr><td class="trow1"><strong>{$lang->ds_author}:</strong></td><td class="trow1"><input type="text" class="textbox" name="ds_author" value="{$ds_author}" /></td></tr>
<tr><td class="trow2"><strong>{$lang->ds_author_website}:</strong></td><td class="trow2"><input type="text" class="textbox" name="ds_author_website" value="{$ds_author_website}" /></td></tr>
<tr><td class="trow1"><strong>{$lang->ds_author_original}:</strong></td><td class="trow1"><input type="text" class="textbox" name="ds_author_original" value="{$ds_author_original}" /></td></tr>
<tr class="tcat"><td colspan="2"><strong>{$lang->ds_license} ({$lang->ds_optional})</strong></td></tr>
<tr><td class="trow2" colspan="2"><textarea name="ds_license" rows="10" cols="70">{$ds_license}</textarea></td></tr></table>'),
		"sid" => "-1"
		);
	$db->insert_query('templates', $array);

	$array = array(
		'name' => 'downloadsystem',
		'title' => 'Inline Downloads',
		'description' => 'Plugin-Settings',
		'disporder' => 30,
		'isdefault' => 0
	);
	$gid = $db->insert_query('settinggroups', $array);

	$array = array(
		'name' => 'downloadsystemfid',
		'title' => 'Forum-IDs',
		'description' => 'Enter the forum-ids where you want to use the plugin. (seperate with commas)',
		'optionscode' => 'text',
		'value' => 0,
		'disporder' => 1,
		'gid' => $gid,
		'isdefault' => 0
	);
	$db->insert_query('settings', $array);
	rebuild_settings();
}

function download_system_uninstall()
{
	global $db;
	$db->drop_column('threads', 'did');
	$db->drop_table('downloads');
	$db->delete_query('templates', 'title=\'download_postbit\'');
	$db->delete_query('templates', 'title=\'download_license\'');
	$db->delete_query('templates', 'title=\'download_newthread\'');
	$db->delete_query('settinggroups', 'name=\'downloadsystem\'');
	$db->delete_query('settings', 'name=\'downloadsystemfid\'');
	rebuild_settings();
}

function download_system_activate()
{
	require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("postbit", '#'.preg_quote("{\$post['message']}").'#', "{\$post['download_system']}{\$post['message']}");
	find_replace_templatesets("postbit_classic", '#'.preg_quote("{\$post['message']}").'#', "{\$post['download_system']}{\$post['message']}");
	find_replace_templatesets("newthread", '#'.preg_quote("{\$attachbox}").'#', "{\$attachbox}{\$download_table}");
	find_replace_templatesets("editpost", '#'.preg_quote("{\$attachbox}").'#', "{\$attachbox}{\$download_table}");
}

function download_system_deactivate()
{
	require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("postbit", '#'.preg_quote("{\$post['download_system']}").'#', '', 0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote("{\$post['download_system']}").'#', '', 0);
	find_replace_templatesets("newthread", '#'.preg_quote("{\$download_table}").'#', '', 0);
	find_replace_templatesets("editpost", '#'.preg_quote("{\$download_table}").'#', '', 0);
}

function download_system_is_installed()
{
	global $db;
	if($db->table_exists('downloads'))
	{
		return true;
	}
	return false;
}

class DownloadSystem
{
	// Forums
	var $forums = array();

	// Do we have a download?
	var $download = false;

	// Set the id
	var $did;

	// Set errors
	var $errors;

	// Data
	var $data = '';

	/**
	 * Get settings
	 */
	function DownloadSystem()
	{
		global $mybb;
		$this->forums = @explode(',', $mybb->settings['downloadsystemfid']);
	}

	/************************************************
	 * We have to use this alternative add function *
	 * because add_hook doesn't support classes.    *
	 ************************************************/
	/**
	 * Add all our hooks
	 */
	function hooks()
	{
		global $mybb, $plugins, $templatelist;
		// postbit
		$plugins->hooks["postbit"][10]["ds_postbit"] = array("function" => array($this, "postbit_post"));
		$plugins->hooks["postbit_prev"][10]["ds_postbit_preview"] = array("function" => array($this, "postbit_preview"));

		// newthread
		$plugins->hooks["newthread_do_newthread_start"][10]["ds_newthread_do_start"] = array("function" => array($this, "newthread_do_start"));
		$plugins->hooks["newthread_do_newthread_end"][10]["ds_newthread_do_end"] = array("function" => array($this, "newthread_do_end"));
		$plugins->hooks["newthread_start"][10]["ds_newthread_start"] = array("function" => array($this, "newthread_start"));
		$plugins->hooks["newthread_end"][10]["ds_newthread_end"] = array("function" => array($this, "newthread_end"));
		$plugins->hooks["datahandler_post_validate_thread"][10]["ds_newthread_datahandler_thread"] = array("function" => array($this, "newthread_datahandler_thread"));
		$plugins->hooks["datahandler_post_insert_thread"][10]["ds_newthread_datahandler_insert_thread"] = array("function" => array($this, "newthread_datahandler_insert_thread"));

		// editpost
		$plugins->hooks["editpost_do_editpost_start"][10]["ds_editpost_do_start"] = array("function" => array($this, "editpost_do_start"));
		$plugins->hooks["editpost_do_editpost_end"][10]["ds_editpost_do_end"] = array("function" => array($this, "editpost_do_end"));
		$plugins->hooks["editpost_start"][10]["ds_editpost_start"] = array("function" => array($this, "editpost_start"));
		$plugins->hooks["editpost_action_start"][10]["ds_editpost_start"] = array("function" => array($this, "editpost_start"));
		$plugins->hooks["editpost_end"][10]["ds_editpost_end"] = array("function" => array($this, "editpost_end"));
		$plugins->hooks["datahandler_post_validate_post"][10]["ds_editpost_datahandler_post"] = array("function" => array($this, "editpost_datahandler_post"));

		// other hooks
		// delete thread
		$plugins->hooks["class_moderation_delete_thread"][10]["ds_delete_thread"] = array("function" => array($this, "delete_thread"));
		// merge threads
		$plugins->hooks["moderation_do_merge"][10]["ds_merge"] = array("function" => array($this, "merge"));
		// copy threads
		$plugins->hooks["class_moderation_copy_thread"][10]["ds_copy"] = array("function" => array($this, "copy"));
		// inline actions
		$plugins->hooks["showthread_start"][10]["ds_showthread"] = array("function" => array($this, "showthread"));
		// disable attachment download
		$plugins->hooks["attachment_end"][10]["ds_attachment"] = array("function" => array($this, "attachment"));


		// Add our templates to the list to cache them
		if(my_strpos($_SERVER['PHP_SELF'], 'showthread.php'))
		{
			$templatelist .= ",download_postbit";
		}
		if(my_strpos($_SERVER['PHP_SELF'], 'showthread.php') && $mybb->input['action'] == 'download')
		{
			$templatelist .= ",download_license";
		}
		if(my_strpos($_SERVER['PHP_SELF'], 'newthread.php') || my_strpos($_SERVER['PHP_SELF'], 'editpost.php'))
		{
			$templatelist .= ",download_newthread";
		}
	}

	/**
	 * The normal post view will be edited.
	 *
	 * @param array post information.
	 */
	function postbit_post(&$post)
	{
		global $db, $thread;
		if($post['pid'] == $thread['firstpost'] && $thread['did'] != 0)
		{
			$query = $db->simple_select('downloads', '*', 'did='.intval($thread['did']));
			$this->postbit($post, $db->fetch_array($query));
		}
	}

	/**
	 * The normal preview post view will be edited.
	 *
	 * @param array post information.
	 */
	function postbit_preview(&$post)
	{
		// Do we have a valid download?
		if($this->download != true || is_array($this->errors))
		{
			return;
		}

		global $thread;

		// Save all information
		$download_array = array('did' => 0,
			'tid' => 0,
			'aid' => $mybb->input['ds_file'],
			'link' => $mybb->input['ds_link'],
			'title' => $mybb->input['subject'],
			'version' => $mybb->input['ds_version'],
			'preview' => $mybb->input['ds_preview'],
			'author' => $mybb->input['ds_author'],
			'author_website' => $mybb->input['ds_author_website'],
			'author_original' => $mybb->input['ds_original'],
			'license' => $mybb->input['ds_license'],
			'last' => '',
			'guestdl' => $mybb->input['ds_guestdl'],
			'own' => $mybb->input['ds_own'],
			'downloads' => 0
			);

		if($download['downloads'])
		{
			$download_array['downloads'] = $download['downloads'];
		}

		if($thread['tid'])
		{
			$download_array['tid'] = $thread['tid'];
			$download_array['did'] = $thread['did'];
		}

		// Built the template
		$this->postbit($post, $download_array);
	}

	/**
	 * Get the download template.
	 *
	 * @param array post information.
	 * @param array download information.
	 */
	function postbit(&$post, $download)
	{
		global $mybb, $lang, $templates, $attachcache, $thread, $theme;

		// Get language
		$lang->load("downloads");

		if(!isset($thread))
		{
			$thread = array('uid' => $mybb->user['uid']);
		}

		// Poster is a guest (banning/deleting)
		if($thread['uid'] == 0)
		{
			$thread = array('uid' => -1);
		}

		if($download['preview'] != '')
		{
			if(!preg_match("#^http://#", $download['preview']))
			{
				$download['preview'] = "http://{$download['preview']}";
			}
			$download['demo'] = "<br />{$lang->ds_preview}: {$download['preview']}";
		}

		if(!isset($download['link']))
		{
			$download['link'] = '';
		}

		if(!isset($download['guestdl']))
		{
			$download['guestdl'] = 1;
		}

		$download['title'] = htmlspecialchars_uni($download['title']);

		if($download['last'] != 0)
		{
			$download['last_dateline'] = my_date($mybb->settings['dateformat'], $download['last'], "", false);
			$version_ad = " ({$download['last_dateline']})";
		}
		else
		{
			$version_ad = '';
		}

		$download['version'] = htmlspecialchars_uni($download['version'])."</strong>{$version_ad}";

		// Get dates
		$download['dateline'] = my_date($mybb->settings['dateformat'], $post['dateline'], "", false);

		if($download['link'] == '')
		{
			// Attachment
			$file = $attachcache[$post['pid']][$download['aid']];
			$ext = get_extension($file['filename']);
			$download['filetype'] = get_attachment_icon($ext)." {$ext}";
			$download['filesize'] = get_friendly_size($file['filesize']);

			$download['downloads'] = "{$file['downloads']} / ".intval($download['downloads'])." ({$download['filesize']} / {$download['filetype']})";
		}

		// Isn't the poster the author?
		if($download['own'] != 1)
		{
			$download['author'] = htmlspecialchars_uni($download['author']);
			$download['author_website'] = htmlspecialchars_uni($download['author_website']);
			$download['author_original'] = htmlspecialchars_uni($download['author_original']);
			$download['author'] = "<tr><td class=\"trow1\">{$lang->ds_author}: {$download['author']}</td><td class=\"trow2\">{$lang->ds_author_website}: {$download['author_website']}</td><td class=\"trow1\">{$lang->ds_original}: {$download['author_original']}</td></tr>";
		}
		else
		{
			$download['author'] = "";	
		}

		if($thread['uid'] == $mybb->user['uid'])
		{
			$download['seconds'] = 0;
		}
		elseif($mybb->user['uid'] != 0)
		{
			// Seconds for members
			$download['seconds'] = 5;
		}
		else
		{
			$download['seconds'] = 10;
		}

		// Do we have a link or an attachment?
		if($download['link'] != '')
		{
			// Link
			$link_button = "{$lang->ds_download} ({$lang->ds_link})";
		}
		else
		{
			// Attachment
			$link_button = "{$lang->ds_download}";
		}

		$download['downloadbutton'] = "<form method=\"post\" action=\"".get_thread_link($thread['tid'])."\">\n"
			."<input type=\"hidden\" name=\"action\" value=\"download\" />\n"
			."<input type=\"hidden\" name=\"do\" value=\"download\" />\n"
			."<input type=\"submit\" class=\"submit\" id=\"ds_submit\"";
		if($mybb->user['usergroup'] == 5 || ($download['guestdl'] == 0 && $mybb->user['uid'] == 0))
		{
			$download['downloadbutton'] .= " disabled=\"disabled\"";
		}
		$download['downloadbutton'] .= " value=\"{$link_button}\" />\n</form>";

		// The different rights
		if($mybb->user['usergroup'] == 5)
		{
			$download['downloadbutton'] .= "<strong>{$lang->ds_activate_your_account}</strong>";
		}
		elseif($download['guestdl'] == 0 && $mybb->user['uid'] == 0)
		{
			$download['downloadbutton'] .= "<strong><a href=\"{$mybb->settings['bburl']}/member.php?action=register\">{$lang->welcome_register}</a> &mdash; <a href=\"{$mybb->settings['bburl']}/member.php?action=login\">{$lang->welcome_login}</a></strong>";
		}

		if($download['guestdl'] == 0)
		{
			$download['guestdl'] = $lang->ds_guestdl_no;
		}
		else
		{
			$download['guestdl'] = $lang->ds_guestdl_yes;
		}

		// Parse out all attachment tags
		$post['attachments'] = $post['thumblist'] = $post['imagelist'] = $post['attachmentlist'] = $post['attachedthumbs'] = $post['attachedimages'] = '';
		$validationcount = $tcount = 0;
		if(is_array($attachcache[$post['pid']]))
		{ // This post has 1 or more attachments
			foreach($attachcache[$post['pid']] as $aid => $attachment)
			{
				if($attachment['aid'] == $download['aid'])
				{
					continue;
				}
				if($attachment['visible'])
				{ // There is an attachment thats visible!
					$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);
					$attachment['filesize'] = get_friendly_size($attachment['filesize']);
					$ext = get_extension($attachment['filename']);
					if($ext == "jpeg" || $ext == "gif" || $ext == "bmp" || $ext == "png" || $ext == "jpg")
					{
						$isimage = true;
					}
					else
					{
						$isimage = false;
					}
					$attachment['icon'] = get_attachment_icon($ext);
					// Support for [attachment=id] code
					if(stripos($post['message'], "attachment.php?aid={$attachment['aid']}") !== false)
					{
						// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
						// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
						// Show as download for all other cases 
						if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
						{
							eval("\$attbit = \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
						}
						elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
						{
							eval("\$attbit = \"".$templates->get("postbit_attachments_images_image")."\";");
						}
						else
						{
							eval("\$attbit = \"".$templates->get("postbit_attachments_attachment")."\";");
						}
						$post['message'] = preg_replace("#\[attachment=".$attachment['aid']."]#si", $attbit, $post['message']);
					}
					else
					{
						// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
						// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
						// Show as download for all other cases 
						if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
						{
							eval("\$post['thumblist'] .= \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
							if($tcount == 5)
							{
								$thumblist .= "<br />";
								$tcount = 0;
							}
							++$tcount;
						}
						elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
						{
							eval("\$post['imagelist'] .= \"".$templates->get("postbit_attachments_images_image")."\";");
						}
						else
						{
							eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment")."\";");
						}
					}
				}
				else
				{
					$validationcount++;
				}
			}
			if($validationcount > 0 && is_moderator($post['fid']))
			{
				if($validationcount == 1)
				{
					$lang->postbit_unapproved_attachments = $lang->postbit_unapproved_attachment;
				}
				else
				{
					$lang->postbit_unapproved_attachments = $lang->sprintf($lang->postbit_unapproved_attachments, $validationcount);
				}
				eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment_unapproved")."\";");
			}
			if($post['thumblist'])
			{
				eval("\$post['attachedthumbs'] = \"".$templates->get("postbit_attachments_thumbnails")."\";");
			}
			if($post['imagelist'])
			{
				eval("\$post['attachedimages'] = \"".$templates->get("postbit_attachments_images")."\";");
			}
			if($post['attachmentlist'] || $post['thumblist'] || $post['imagelist'])
			{
				eval("\$post['attachments'] = \"".$templates->get("postbit_attachments")."\";");
			}
		}

		if(isset($download['aid']) && $download['aid'] != 0)
		{
			$attachment = $attachcache[$post['pid']][$download['aid']];
			$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);
			$attachment['filesize'] = get_friendly_size($attachment['filesize']);
			$ext = get_extension($attachment['filename']);
			if($ext == "jpeg" || $ext == "gif" || $ext == "bmp" || $ext == "png" || $ext == "jpg")
			{
				$isimage = true;
			}
			else
			{
				$isimage = false;
			}
			$attachment['icon'] = get_attachment_icon($ext);
			eval("\$attbit1 = \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
			eval("\$attbit2 = \"".$templates->get("postbit_attachments_images_image")."\";");
			eval("\$attbit3 = \"".$templates->get("postbit_attachments_attachment")."\";");
			$post['message'] = str_replace(array($attbit1, $attbit2, $attbit3), '', $post['message']);
		}

		// Get final template
		eval("\$post['download_system'] = \"".$templates->get("download_postbit")."\";");
	}

	/**
	 * Delete download.
	 */
	function delete_thread($tid)
	{
		global $thread, $db;
		if($thread['did'] != 0)
		{
			$query = $db->simple_select('downloads', '*', 'did='.intval($thread['did']));
			$download = $db->fetch_array($query);
			$db->delete_query("downloads", 'did='.intval($thread['did']));
		}
	}

	/**
	 * Merge threads.
	 */
	function merge()
	{
		global $mybb, $thread, $lang, $db;

		// Load language
		$lang->load('downloads');

		// explode at # sign in a url (indicates a name reference) and reassign to the url
		$realurl = explode("#", $mybb->input['threadurl']);
		$mybb->input['threadurl'] = $realurl[0];
		
		// Are we using an SEO URL?
		if(substr($mybb->input['threadurl'], -4) == "html")
		{
			// Get thread to merge's tid the SEO way
			preg_match("#thread-([0-9]+)?#i", $mybb->input['threadurl'], $threadmatch);
			preg_match("#post-([0-9]+)?#i", $mybb->input['threadurl'], $postmatch);
			
			if($threadmatch[1])
			{
				$parameters['tid'] = $threadmatch[1];
			}
			
			if($postmatch[1])
			{
				$parameters['pid'] = $postmatch[1];
			}
		}
		else
		{
			// Get thread to merge's tid the normal way
			$splitloc = explode(".php", $mybb->input['threadurl']);
			$temp = explode("&", my_substr($splitloc[1], 1));

			if(!empty($temp))
			{
				for($i = 0; $i < count($temp); $i++)
				{
					$temp2 = explode("=", $temp[$i], 2);
					$parameters[$temp2[0]] = $temp2[1];
				}
			}
			else
			{
				$temp2 = explode("=", $splitloc[1], 2);
				$parameters[$temp2[0]] = $temp2[1];
			}
		}
		if($parameters['pid'] && !$parameters['tid'])
		{
			$query = $db->simple_select("posts", "*", "pid='".intval($parameters['pid'])."'");
			$post = $db->fetch_array($query);
			$mergetid = $post['tid'];
		}
		elseif($parameters['tid'])
		{
			$mergetid = $parameters['tid'];
		}
		$mergetid = intval($mergetid);

		if($thread['did'] != 0)
		{
			$query = $db->simple_select("threads", "*", "tid='".intval($mergetid)."'");
			$mergethread = $db->fetch_array($query);
			if($mergethread['dateline'] < $thread['dateline'])
			{
				error($lang->ds_error_is_download);
				exit;
			}
		}

		if(!isset($mergethread))
		{
			$query = $db->simple_select("threads", "*", "tid='".intval($mergetid)."'");
			$mergethread = $db->fetch_array($query);
		}

		if($mergethread['did'] != 0)
		{
			error($lang->ds_error_is_download);
			exit;
		}
	}

	/**
	 * Copy thread.
	 */
	function copy($array)
	{
		global $db, $lang;

		$thread = get_thread($array['tid']);
		if($thread['did'] != 0)
		{
			// Load language
			$lang->load('downloads');

			error($lang->ds_error_is_download);
		}
	}

	/**
	 * All showthread actions.
	 */
	function showthread()
	{
		global $mybb;
		if($mybb->input['action'] != 'download')
		{
			return;
		}

		global $db, $lang, $templates, $thread, $header, $headerinclude, $footer, $theme;

		$lang->load('downloads');

		// Download
		if($mybb->input['do'] == 'download' && $mybb->request_method == 'post')
		{
			global $session, $parser;
			if($thread['did'] != 0)
			{
				$query = $db->simple_select('downloads', '*', 'did='.intval($thread['did']));
			}
			else
			{
				error($lang->error_invalidthread);
			}

			$download = $db->fetch_array($query);

			if($mybb->user['uid'] == 0 && isset($download['guestdl']) && $download['guestdl'] == 0)
			{
				error_no_permission();
			}

			if(isset($mybb->input['return']))
			{
				header("Location: {$mybb->settings['bburl']}/".get_thread_link($thread['tid']));
				exit;
			}

			$captcha_error = '';

			if(isset($mybb->input['my_post_key']) && verify_post_check($mybb->input['my_post_key'], false))
			{
				if($download['link'])
				{
					$query = $db->query("UPDATE ".TABLE_PREFIX."downloads SET downloads=downloads+1 WHERE did=".intval($download['did']));
					header("Location: {$download['link']}");
				}
				else
				{
					$aid = intval($download['aid']);

					$query = $db->simple_select("attachments", "*", "aid='{$aid}'");
					$attachment = $db->fetch_array($query);

					$attachupdate = array(
						"downloads" => $attachment['downloads']+1,
					);
					$db->update_query("attachments", $attachupdate, "aid='{$attachment['aid']}'");

					if($thread['did'] != 0)
					{
						$query = $db->write_query("UPDATE ".TABLE_PREFIX."downloads SET downloads=downloads+1 WHERE did=".intval($thread['did']));
					}

					$ext = get_extension($attachment['filename']);
					$attachment['filename'] = "{$download['tid']}_{$download['title']}_{$download['version']}";
					$attachment['filename'] = str_replace(array(' ', '.', '/'), '_', $attachment['filename']).'.'.$ext;

					$attachment['filename'] = rawurlencode($attachment['filename']);

					// output file
					$ext = get_extension($attachment['filename']);
					if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "msie") !== false && strpos($attachment['filetype'], "image") === false)
					{
						header("Content-disposition: attachment; filename={$attachment['filename']}");
					}
					else
					{
						header("Content-disposition: inline; filename={$attachment['filename']}");
					}
					header("Content-type: {$attachment['filetype']}");
					header("Content-length: {$attachment['filesize']}");
					echo file_get_contents($mybb->settings['uploadspath']."/".$attachment['attachname']);
					exit;
				}
			}
			else
			{
				// Do we have a custom license?
				if($download['license'] != '' && !isset($download['url']))
				{
					$parser_options = array(
						"allow_html" => 0,
						"allow_mycode" => 1,
						"allow_smilies" => 1,
						"allow_imgcode" => 1
					);
					$download['license'] = $parser->parse_message($download['license'], $parser_options);
				}
				elseif(!isset($download['url']))
				{
					$download['license'] = $lang->ds_license_text;
				}
				$threadlink = get_thread_link($thread['tid']);
				eval("\$output = \"".$templates->get("download_license")."\";");
			}

			output_page($output);
			exit;
		}
	}

	/**
	 * We don't want attachment download.
	 */
	function attachment()
	{
		global $mybb, $db, $lang, $attachment, $thread;
		if($thread['did'] == 0)
		{
			return;
		}
		$query = $db->simple_select("downloads", "*", "aid='{$attachment['aid']}'");
		$download = $db->fetch_array($query);
		// Is this a download?
		if(isset($download['aid']))
		{
			// Reset attachment count
			$attachupdate = array(
				"downloads" => $attachment['downloads'],
			);
			$db->update_query("attachments", $attachupdate, "aid='{$attachment['aid']}'");
			$lang->load('downloads');
			error($lang->ds_error_use_button);
		}
	}

	/**
	 * Start of do_newthread.
	 */
	function newthread_do_start()
	{
		global $mybb, $lang, $fid, $forum;
		// Do we have a download forum?
		if(in_array($forum['fid'], $this->forums))
		{
			// Load language
			$lang->load("downloads");
			// Mark as download
			$this->download = true;
			// Modify input
			$mybb->input['ds_title'] = $mybb->input['subject'];
			$mybb->input['subject'] = "{$mybb->input['subject']} {$mybb->input['ds_version']}";
		}
	}

	/**
	 * End of do_newthread.
	 */
	function newthread_do_end()
	{
		global $mybb, $tid, $db;
		// Do we have a download?
		if($this->download == true)
		{
			// Set did
			$db->update_query("downloads", array("tid" => intval($tid)), "did='".$this->did."'");
		}
	}

	/**
	 * Alters validate thread handler.
	 *
	 * @param resource posthandler.
	 */
	function newthread_datahandler_thread(&$posthandler)
	{
		global $mybb;
		// Do we have a download?
		if($this->download == true)
		{
			// Validate the input
			$this->validate();

			// Get errors
			if($mybb->input['ds_file'] == "")
			{
				$posthandler->set_error("ds_no_file");
			}
			if($mybb->input['ds_file'] == "link" && !$mybb->input['ds_link'])
			{
				$posthandler->set_error("ds_no_link");
			}
			if($mybb->input['ds_version'] == "")
			{
				$posthandler->set_error("ds_no_version");
			}
			if($mybb->input['ds_author'] == "" && $mybb->input['ds_own'] != 1)
			{
				$posthandler->set_error("ds_no_author");
			}
		}
	}

	/**
	 * Alters thread insert handler.
	 *
	 * @param resource posthandler.
	 */
	function newthread_datahandler_insert_thread(&$posthandler)
	{
		global $mybb, $db, $draft_check, $thread;
		// Do we have a download?
		if($this->download == true)
		{
			// Do we have a draft?
			if(!$posthandler->thread_insert_data['fid'])
			{
				// No
				$download_array = array(
					'aid' => intval($mybb->input['ds_file']),
					'link' => $db->escape_string($mybb->input['ds_link']),
					'title' => $db->escape_string($mybb->input['ds_title']),
					'version' => $db->escape_string($mybb->input['ds_version']),
					'preview' => $db->escape_string($mybb->input['ds_preview']),
					'author' => $db->escape_string($mybb->input['ds_author']),
					'author_website' => $db->escape_string($mybb->input['ds_author_website']),
					'author_original' => $db->escape_string($mybb->input['ds_author_original']),
					'license' => $db->escape_string($mybb->input['ds_license']),
					'last' => '',
					'guestdl' => intval($mybb->input['ds_guestdl']),
					'own' => intval($mybb->input['ds_own'])
					);

				$db->update_query("downloads", $download_array, "tid='{$thread['tid']}'");
			}
			else
			{
				// Yes
				$download_array = array('tid' => intval($tid),
					'aid' => intval($mybb->input['ds_file']),
					'link' => $db->escape_string($mybb->input['ds_link']),
					'title' => $db->escape_string($mybb->input['ds_title']),
					'version' => $db->escape_string($mybb->input['ds_version']),
					'preview' => $db->escape_string($mybb->input['ds_preview']),
					'author' => $db->escape_string($mybb->input['ds_author']),
					'author_website' => $db->escape_string($mybb->input['ds_author_website']),
					'author_original' => $db->escape_string($mybb->input['ds_author_original']),
					'license' => $db->escape_string($mybb->input['ds_license']),
					'last' => '',
					'guestdl' => intval($mybb->input['ds_guestdl']),
					'own' => intval($mybb->input['ds_own']),
					'downloads' => 0
					);

				$did = $db->insert_query("downloads", $download_array);

				$posthandler->thread_insert_data['did'] = intval($did);
				$this->did = $did;
			}
		}
	}

	/**
	 * Start of newthread.
	 */
	function newthread_start()
	{
		global $mybb, $lang, $fid, $thread_errors, $forum;

		// Do we have a download?
		if($this->download == true)
		{
			return false;
		}
		// De we have a download forum?
		if(in_array($forum['fid'], $this->forums))
		{
			// Load language
			$lang->load("downloads");
			$this->download = true;

			// Validate the input
			$this->validate();

			if($mybb->input['previewpost'])
			{
				// Get errors
				if($mybb->input['ds_file'] == "")
				{
					$this->errors[] = "ds_no_file";
				}
				if($mybb->input['ds_file'] == "link" && !$mybb->input['ds_link'])
				{
					$this->errors[] = "ds_no_link";
				}
				if($mybb->input['ds_version'] == "")
				{
					$this->errors[] = "ds_no_version";
				}
				if($mybb->input['ds_author'] == "" && $mybb->input['ds_own'] != 1)
				{
					$this->errors[] = "ds_no_author";
				}
			}
		}
	}

	/**
	 * End of newthread.
	 */
	function newthread_end()
	{
		global $mybb, $lang, $theme, $templates, $download_table, $db, $posthash, $posthandler, $thread_errors, $preview, $thread, $post, $pid, $subject;

		// Do we have a download?
		if($this->download != true)
		{
			return false;
		}

		// Edit template
		$lang->thread_subject = $lang->ds_title;
		$lang->your_message = $lang->ds_description.":";

		// Editing a draft thread -> Fetch old data
		if($mybb->input['action'] == 'editdraft' && $mybb->user['uid'] != 0)
		{
			$query = $db->simple_select("downloads", "*", "did='{$thread['did']}'");
			$download = $db->fetch_array($query);

			$subject = htmlspecialchars_uni($download['title']);
			$ds_version = htmlspecialchars_uni($download['version']);
			$ds_link = htmlspecialchars_uni($download['link']);
			$ds_author = htmlspecialchars_uni($download['author']);
			$ds_author_website = htmlspecialchars_uni($download['author_website']);
			$ds_author_original = htmlspecialchars_uni($download['author_original']);
			$ds_preview = htmlspecialchars_uni($download['preview']);
			$ds_license = htmlspecialchars_uni($download['license']);

			if($download['aid'] != 0)
			{
				$ds_file[$download['aid']] = " selected=\"selected\"";
			}

			if($download['link'] != '')
			{
				$ds_file['link'] = " selected=\"selected\"";
			}

			if($download['guestdl'] == 1)
			{
				$ds_guestdl = " checked=\"checked\"";
			}

			if($download['own'] == 1)
			{
				$ds_own = " checked=\"checked\"";
			}
		}
		else
		{
			$ds_version = htmlspecialchars_uni($mybb->input['ds_version']);
			$ds_link = htmlspecialchars_uni($mybb->input['ds_link']);
			$ds_author = htmlspecialchars_uni($mybb->input['ds_author']);
			$ds_author_website = htmlspecialchars_uni($mybb->input['ds_author_website']);
			$ds_author_original = htmlspecialchars_uni($mybb->input['ds_author_original']);
			$ds_preview = htmlspecialchars_uni($mybb->input['ds_preview']);
			$ds_license = htmlspecialchars_uni($mybb->input['ds_license']);

			if(isset($mybb->input['ds_file']))
			{
				$ds_file[$mybb->input['ds_file']] = " selected=\"selected\"";
			}

			if($mybb->input['ds_guestdl'] == 1)
			{
				$ds_guestdl = " checked=\"checked\"";
			}

			if($mybb->input['ds_own'] == 1)
			{
				$ds_own = " checked=\"checked\"";
			}
		}

		if($mybb->input['action'] == "editdraft")
		{
			$attachwhere = "pid='$pid'";
		}
		else
		{
			$attachwhere = "posthash='".$db->escape_string($posthash)."'";
		}
		// Get attachments
		$query = $db->simple_select("attachments", "*", $attachwhere);
		while($attachment = $db->fetch_array($query))
		{
			$ds_file_options .= "<option value=\"{$attachment['aid']}\"".$ds_file[$attachment['aid']].">{$attachment['filename']}</option>\n";
		}

		// Set errors
		if($posthandler && $this->errors)
		{
			foreach($this->errors as $each_error)
			{
				$posthandler->set_error($each_error);
			}

			$post_errors = $posthandler->get_friendly_errors();
			$thread_errors = inline_error($post_errors);
			$preview = '';
		}

		if(!isset($download['tid']))
		{
			$download['tid'] = 0;
		}

		$ds_edit_time = '';
		$forum_select = '';

		// Get template
		eval("\$download_table = \"".$templates->get("download_newthread")."\";");
	}

	/**
	 * Start of do_editpost.
	 */
	function editpost_do_start()
	{
		global $mybb, $lang, $fid, $thread, $db, $post, $download;
		// Do we have a download?
		if($thread['firstpost'] == $post['pid'] && $thread['did'] != 0)
		{
			// Get it
			$query = $db->simple_select("downloads", "*", "did='{$thread['did']}'");
			$download = $db->fetch_array($query);
		}
		if($download)
		{
			// Load language
			$lang->load("downloads");
			// Mark as download
			$this->download = true;
			// Modify input
			$mybb->input['ds_title'] = $mybb->input['subject'];
			$mybb->input['subject'] = "{$mybb->input['subject']} {$mybb->input['ds_version']}";
		}
	}

	/**
	 * End of do_editpost.
	 */
	function editpost_do_end()
	{
		global $mybb, $db, $tid, $forum, $download;

		// Do we have a download?
		if($this->download == true)
		{
			// Get download information
			$download_array = array(
				'aid' => intval($mybb->input['ds_file']),
				'link' => $db->escape_string($mybb->input['ds_link']),
				'title' => $db->escape_string($mybb->input['ds_title']),
				'version' => $db->escape_string($mybb->input['ds_version']),
				'preview' => $db->escape_string($mybb->input['ds_preview']),
				'author' => $db->escape_string($mybb->input['ds_author']),
				'author_website' => $db->escape_string($mybb->input['ds_author_website']),
				'author_original' => $db->escape_string($mybb->input['ds_author_original']),
				'license' => $db->escape_string($mybb->input['ds_license']),
				'last' => '',
				'guestdl' => intval($mybb->input['ds_guestdl']),
				'own' => intval($mybb->input['ds_own'])
				);

			$db->update_query("downloads", $download_array, "tid='{$tid}'");
		}
	}

	/**
	 * Validate update handler.
	 *
	 * @param resource posthandler.
	 */
	function editpost_datahandler_post(&$posthandler)
	{
		global $mybb;
		// Do we have a download?
		if($this->download == true)
		{
			// Validate the input
			$this->validate(true);

			// Get errors
			if($mybb->input['ds_file'] == "")
			{
				$posthandler->set_error("ds_no_file");
			}
			if($mybb->input['ds_file'] == "link" && !$mybb->input['ds_link'])
			{
				$posthandler->set_error("ds_no_link");
			}
			if($mybb->input['ds_version'] == "")
			{
				$posthandler->set_error("ds_no_version");
			}
			if($mybb->input['ds_author'] == "" && $mybb->input['ds_own'] != 1)
			{
				$posthandler->set_error("ds_no_author");
			}
		}
	}

	/**
	 * Start of editpost.
	 */
	function editpost_start()
	{
		global $mybb, $lang, $fid, $thread_errors, $db, $thread, $download, $post;
		// Do we have a download?
		if($thread['firstpost'] == $post['pid'] && $thread['did'] != 0 && $this->download != true)
		{
			// Get it
			$query = $db->simple_select("downloads", "*", 'did='.intval($thread['did']));
			$download = $db->fetch_array($query);
		}
		if(isset($download['did']))
		{
			// Load language
			$lang->load("downloads");
			$this->download = true;

			// Validate the input
			$this->validate(true);

			if($mybb->input['previewpost'])
			{
				// Get errors
				if($mybb->input['ds_file'] == "")
				{
					$this->errors[] = "ds_no_file";
				}
				if($mybb->input['ds_file'] == "link" && !$mybb->input['ds_link'])
				{
					$this->errors[] = "ds_no_link";
				}
				if($mybb->input['ds_version'] == "")
				{
					$this->errors[] = "ds_no_version";
				}
				if($mybb->input['ds_author'] == "" && $mybb->input['ds_own'] != 1)
				{
					$this->errors[] = "ds_no_author";
				}
			}
		}
	}

	/**
	 * End of editpost.
	 */
	function editpost_end()
	{
		global $mybb, $lang, $theme, $templates, $download_table, $db, $posthash, $post_errors, $preview, $download, $posthash, $maximageserror, $pid, $subject, $post, $forum, $ds_fid;

		// Do we have a download?
		if($this->download != true)
		{
			return false;
		}

		// Edit template
		$lang->thread_subject = $lang->ds_title;
		$lang->your_message = $lang->ds_description;

		// Does input exist?
		if(!$mybb->input['attachmentaid'] && !$mybb->input['newattachment'] && !$mybb->input['previewpost'] && !$maximageserror && !$post_errors)
		{
			// Yes
			$subject = htmlspecialchars_uni($download['title']);
			$ds_version = htmlspecialchars_uni($download['version']);
			$ds_link = htmlspecialchars_uni($download['link']);
			$ds_author = htmlspecialchars_uni($download['author']);
			$ds_author_website = htmlspecialchars_uni($download['author_website']);
			$ds_author_original = htmlspecialchars_uni($download['author_original']);
			$ds_preview = htmlspecialchars_uni($download['preview']);
			$ds_license = htmlspecialchars_uni($download['license']);
			$ds_fid = $forum['fid'];

			if($download['aid'] != 0)
			{
				$ds_file[$download['aid']] = " selected=\"selected\"";
			}

			if($download['link'] != '')
			{
				$ds_file['link'] = " selected=\"selected\"";
			}

			if($download['guestdl'] == 1)
			{
				$ds_guestdl = " checked=\"checked\"";
			}

			if($download['own'] == 1)
			{
				$ds_own = " checked=\"checked\"";
			}
		}
		else
		{
			// No
			$ds_version = htmlspecialchars_uni($mybb->input['ds_version']);
			$ds_link = htmlspecialchars_uni($mybb->input['ds_link']);
			$ds_author = htmlspecialchars_uni($mybb->input['ds_author']);
			$ds_author_website = htmlspecialchars_uni($mybb->input['ds_author_website']);
			$ds_author_original = htmlspecialchars_uni($mybb->input['ds_author_original']);
			$ds_preview = htmlspecialchars_uni($mybb->input['ds_preview']);
			$ds_license = htmlspecialchars_uni($mybb->input['ds_license']);
			$ds_fid = $mybb->input['ds_fid'];

			if(isset($mybb->input['ds_file']))
			{
				$ds_file[$mybb->input['ds_file']] = " selected=\"selected\"";
			}

			if($mybb->input['ds_guestdl'] == 1)
			{
				$ds_guestdl = " checked=\"checked\"";
			}

			if($mybb->input['ds_own'] == 1)
			{
				$ds_own = " checked=\"checked\"";
			}
		}

		if($posthash)
		{
			$attachwhere = "posthash='".$db->escape_string($posthash)."'";
		}
		else
		{
			$attachwhere = "pid='$pid'";
		}
		// Get attachments
		$query = $db->simple_select("attachments", "*", $attachwhere);
		while($attachment = $db->fetch_array($query))
		{
			$ds_file_options .= "<option value=\"{$attachment['aid']}\"".$ds_file[$attachment['aid']].">{$attachment['filename']}</option>\n";
		}

		$ds_edit_time = "<tr><td class=\"trow1\"><strong>{$lang->ds_edit_time}?</strong></td><td class=\"trow1\"><input type=\"checkbox\" class=\"checkbox\" name=\"ds_edit_time\" value=\"1\"{$ds_edit_time} /></td></tr>";

		// Set errors
		if($posthandler && $this->errors)
		{
			foreach($this->errors as $each_error)
			{
				$posthandler->set_error($each_error);
			}

			$post_errors = $posthandler->get_friendly_errors();
			$thread_errors = inline_error($post_errors);
			$preview = '';
		}

		// Get template
		eval("\$download_table = \"".$templates->get("download_newthread")."\";");
	}

	/**
	 * Validate input.
	 */
	function validate($post=false)
	{
		global $mybb;
		$this->validate_yes('ds_can_dl');
		$this->validate_yes('ds_own');
		$this->validate_file();
		$this->validate_text('ds_version');
		$this->validate_text('ds_preview');
		if($mybb->input['ds_own'] == 1)
		{
			$mybb->input['ds_author'] = $mybb->input['ds_author_website'] = $mybb->input['ds_author_original'] = '';
		}
		else
		{
			$this->validate_text('ds_author');
			$this->validate_text('ds_author_website');
			$this->validate_text('ds_author_original');
		}
		$this->validate_text('ds_license');
		if($post)
		{
			$this->validate_yes('ds_edit_time');
			$this->validate_fid();
		}
	}

	/**
	 * Validate options.
	 *
	 * @param string option.
	 */
	function validate_yes($option)
	{
		global $mybb;
		if($mybb->input[$option] == 1)
		{
			$mybb->input[$option] = 1;
		}
		else
		{
			$mybb->input[$option] = 0;
		}
	}

	/**
	 * Validate textbox.
	 *
	 * @param string option.
	 */
	function validate_text($option)
	{
		global $mybb;
		$mybb->input[$option] = trim($mybb->input[$option]);
	}

	/**
	 * Validate forum.
	 */
	function validate_fid()
	{
		global $mybb;
		$mybb->input['ds_fid'] = intval($mybb->input['ds_fid']);
	}

	/**
	 * Validate file.
	 */
	function validate_file()
	{
		global $mybb;
		// We have a link
		if($mybb->input['ds_file'] == 'link')
		{
			$mybb->input['ds_file'] = 'link';
		}
		// We have a aid
		elseif(intval($mybb->input['ds_file']) != 0)
		{
			$mybb->input['ds_file'] = intval($mybb->input['ds_file']);
			$mybb->input['ds_link'] = '';
		}
		// We have nothing
		else
		{
			$mybb->input['ds_file'] = '';
		}
	}
	
	function get_thread_link_do($tid, $do)
	{
		global $mybb;
		if($mybb->settings['seourls'] == "yes" || ($mybb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1))
		{
			$action = 'download-'.$do;
		}
		else
		{
			$action = 'download&do='.$do;
		}
		return get_thread_link($tid, 0, $action);
	}
}
?>
