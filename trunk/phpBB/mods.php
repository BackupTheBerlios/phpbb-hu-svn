<?php
/** 
*
* @package site
* @version $Id$
* @copyright (c) 2007 phpbb.hu
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('site');

define('SITE_SECTION', 'mods');

$mode = request_var('mode', '');


/*
$start_time = microtime_float();

//$mod = new mod_pack('new', 2964); // 3177
$mod = new mod_pack('new', 2964);
//$mod->get_pack_details();
$mod->get_archive();
$mod->merge_packs();

//$mod->get_pack_details();

print number_format(microtime_float() - $start_time, 8);

exit;
*/

// Check permission for viewing the MODs database
if (!$auth->acl_get('f_c_see', MODS_FORUM_ID))
{
	http_status(403);
	trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
}

/**
* List tags within a specified tag category
*/
if ($mode == 'tagcat')
{
	$tagcat_name = request_var('cat', '');
	
	// Get all tag categories
	$sql = 'SELECT tagcat_id, tagcat_name, tagcat_title
		FROM ' . TAGCATS_TABLE . '
		WHERE tagcat_module = ' . TAG_MODS /*. "
			AND tagcat_name = '" . $db->sql_escape($tagcat_name) . "'"*/;
	$result = $db->sql_query($sql);
	
	/*if (($tagcat = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_TAGCAT');
	}
	$db->sql_freeresult($result);*/
	
	$tagcats = array();
	$tagcat = false;
	
	while($row = $db->sql_fetchrow($result))
	{
		$tagcats[] = $row;
		
		if ($row['tagcat_name'] == $tagcat_name)
		{
			$tagcat = $row;
		}
	}
	$db->sql_freeresult($result);
	
	if (!$tagcat)
	{
		http_status(404);
		trigger_error('NO_TAGCAT');
	}
	
	$sql = 'SELECT tag_id, tag_name, tag_title
		FROM ' . TAGS_TABLE . '
		WHERE tagcat_id = ' . $tagcat['tagcat_id'];;
	$result = $db->sql_query($sql);
	
	$tags = array();
	
	while($row = $db->sql_fetchrow($result))
	{
		$tags[] = $row;
		
		$template->assign_block_vars('tagrow', array(
			'TAG_TITLE'	=> $row['tag_title'],
			'U_TAG'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=listtag&amp;cat=' . $tagcat['tagcat_name'] . '&amp;tag=' . $row['tag_name']),
		));
	}
	$db->sql_freeresult($result);
	
	// Assign some vars
	$template->assign_vars(array(
		'TAGCAT_NAME'		=> $tagcat['tagcat_name'],
		'TAGCAT_TITLE'		=> $tagcat['tagcat_title'],
		'U_TAGCAT'			=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=tagcat&cat=' . $tagcat['tagcat_name']),
	
		'U_MODS_DB'			=> append_sid($phpbb_root_path . 'mods.' . $phpEx),
	));

	// Display sidemenu
	$sidemenu = new sidemenu();
	
	$sidemenu->add_block('MODS_DB');
	$sidemenu->add_link('MODS_DB_INDEX', "{$phpbb_root_path}mods.{$phpEx}");
	if ($auth->acl_get('f_c_post', MODS_FORUM_ID))
	{
		$sidemenu->add_link('ADD_MOD', "{$phpbb_root_path}mods.{$phpEx}", 'mode=add');
	}
	
	// Display tags in the sidemenu
	$sidemenu->add_block('TAGS');
	
	foreach ($tagcats as $cat)
	{
		$sidemenu->add_link($cat['tagcat_title'], "{$phpbb_root_path}mods.{$phpEx}", 'mode=tagcat&cat=' . $cat['tagcat_name']);
	
		// For the selected tag category also list tags
		if ($tagcat['tagcat_id'] == $cat['tagcat_id'])
		{
			foreach ($tags as $tag)
			{
				$sidemenu->add_link('» ' . $tag['tag_title'], "{$phpbb_root_path}mods.{$phpEx}", 'mode=listtag&amp;cat=' . $cat['tagcat_name'] . '&amp;tag=' . $tag['tag_name']);
			}
		}
	}
	
	// Output page
	site_header($user->lang['MODS_DB'] . ': ' . sprintf($user->lang['TAGCAT_X'], $tagcat['tagcat_title']), 'mods', array(array('mods.' . $phpEx, 'MODS_DB'), array('mods.' . $phpEx . '?mode=tagcat&amp;cat=' . $tagcat['tagcat_name'], sprintf($user->lang['TAGCAT_X'], $tagcat['tagcat_title']))));

	$template->set_filenames(array(
		'body' => 'mods_tagcat.html')
	);

	site_footer();
}

/**
* Article display page
*/
elseif ($mode == 'listtag')
{
	$tagcat_name = request_var('cat', '');
	$tag_name = request_var('tag', '');
	$start = request_var('start', '');
	
	$sql = 'SELECT t.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title
		FROM ' . TAGS_TABLE . ' t, ' . TAGCATS_TABLE . " tc
		WHERE tag_name = '" . $db->sql_escape($tag_name) . "'
			AND t.tagcat_id = tc.tagcat_id
			AND tc.tagcat_name = '" . $db->sql_escape($tagcat_name) . "'
			AND tc.tagcat_module = " . TAG_MODS;
	$result = $db->sql_query($sql);
		
	if (($tag = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_TAG');
	}
	$db->sql_freeresult($result);
	
	// Get the total number of MODs
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'=> 'COUNT(m.mod_id) AS mods_count',		
		'FROM'	=> array(MODS_TABLE => 'm', TAGMATCH_TABLE => 'tm'),
		'WHERE'	=> "tm.tag_id = {$tag['tag_id']} AND m.topic_id = tm.topic_id",
	));
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$mods_count = $row['mods_count'];	
	$db->sql_freeresult($result);
	
	// Query last ten MODs
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'm.mod_id, m.topic_id, m.mod_hu_title, m.mod_version, m.mod_desc,
						t.topic_approved, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_first_poster_name, t.topic_first_poster_colour,
						GROUP_CONCAT(tms.tag_id) AS tags',
		'FROM'		=> array(MODS_TABLE => 'm', TAGMATCH_TABLE => 'tm'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'm.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(TAGMATCH_TABLE => 'tms'),
				'ON'	=> 'tms.topic_id = m.topic_id'
			)
		),

		'WHERE'		=> 'm.topic_id = tm.topic_id AND tm.tag_id = ' . $tag['tag_id'] . (($auth->acl_get('m_approve', MODS_FORUM_ID) ? '' : ' AND t.topic_approved = 1')),
		'GROUP_BY'	=> 'm.mod_id',
		'ORDER_BY'	=> 't.topic_title ASC',
	));
	$result = $db->sql_query_limit($sql, 25, $start);

	while ($row = $db->sql_fetchrow($result))
	{
		// Assign some general variables about the article
		$template->assign_block_vars('modrow', array(
			'MOD_TITLE'				=> $row['mod_hu_title'],
			'MOD_VERSION'			=> $row['mod_version'],
			'MOD_DESC'				=> trim_text($row['mod_desc'], 120),
			'MOD_ID'				=> $row['mod_id'],
			'U_MOD'					=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=mod&amp;id=' . $row['mod_id']),
			/*'MOD_POSTER'			=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'MOD_POSTER_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'MOD_POSTER_FULL'		=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),*/
			'MOD_POSTED'			=> $user->format_date($row['topic_time']),	
			//'MOD_VIEWS'			=> $row['topic_views'],	
			'MOD_APPROVED'			=> $row['topic_approved'],	
		
			'U_MCP_QUEUE'		=> ($auth->acl_get('m_approve', MODS_FORUM_ID)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=queue&amp;mode=approve_details&amp;t={$row['topic_id']}", true) : false,
			'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'TOPIC_UNAPPROVED'),
		));
		
		/* Isn't needed for the time being
		* @todo It still needs to be decided what info to show
		* One thing is sure, we don't want to display all tags
		// Assign tags
		$mod_tags = array();
		$tags_list = (!empty($row['tags'])) ? explode(',', $row['tags']) : array();
		
		// Group assigned tags into categories
		foreach ($tags_list as $tag)
		{
			$mod_tags[$tags[$tag]['tagcat_id']][] = &$tags[$tag]; // Maybe the index could be changed from tagcat_id to tagcat_name as the latter is more often used
		}
		
		// Sort the array containing the article tags in order to list them always in the same order
		ksort($mod_tags);
		
		// Loop over the tag categories
		foreach ($mod_tags as $tagcat)
		{
			// Category variables
			$template->assign_block_vars('articles.tagcats', array(
				'TAGCAT_NAME'	=> $tagcat[0]['tagcat_name'],
				'TAGCAT_TITLE'	=> $tagcat[0]['tagcat_title'],
				'U_TAGCAT'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=tagcat&amp;cat=' . $tagcat[0]['tagcat_name']),
			));
			
			// Tag details
			foreach($tagcat as $tag)
			{
				$template->assign_block_vars('articles.tagcats.tags', array(
					'TAG_TITLE'	=> $tag['tag_title'],
					'U_TAG'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=listtag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name']),
				));
			}
		}*/
	}
	$db->sql_freeresult($result);
	
	// Assign some vars
	$template->assign_vars(array(
		'U_MODS_DB'			=> append_sid($phpbb_root_path . 'mods.' . $phpEx),
	
		'PAGINATION'	=> generate_pagination(append_sid("{$phpbb_root_path}mods.$phpEx", "mode=listtag&amp;cat={$tag['tagcat_name']}&amp;tag={$tag['tag_name']}"), $mods_count, 25, $start, true),
		'PAGE_NUMBER'	=> on_page($mods_count, 25, $start),
		'TOTAL_MODS'	=> ($mods_count == 1) ? $user->lang['VIEW_MOD'] : sprintf($user->lang['VIEW_MODS'], $reports_count),
	));
	
	// Output page
	site_header(
		$user->lang['MODS_DB'] . ': ' . sprintf($user->lang['TAG_X'], $tag['tag_title'] . ' (' .  $tag['tagcat_title'] . ')'),
		'mods',
		array(
			array('mods.' . $phpEx, 'MODS_DB'),
			array('mods.' . $phpEx . '?mode=tagcat&amp;cat=' . $tag['tagcat_name'], $tag['tagcat_title']),
			array('mods.' . $phpEx . '?mode=listtag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name'], $tag['tag_title']),
		)
	);

	$template->set_filenames(array(
		'body' => 'mods_taglist.html')
	);

	site_footer();
}

/**
* MOD display page
*/
elseif ($mode == 'mod')
{
	$mod_id = request_var('id', 0);
	
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'm.*, t.topic_approved, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_first_poster_name, t.topic_first_poster_colour',
		'FROM'		=> array(MODS_TABLE => 'm'),
		'LEFT_JOIN'	=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'm.topic_id = t.topic_id'
			),
		),
		'WHERE'		=> 'mod_id = ' . $mod_id,
	));
	$result = $db->sql_query($sql);
	
	if (($mod = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_MOD');
	}
	elseif ($mod['topic_approved'] != 1 && !$auth->acl_get('m_approve', MODS_FORUM_ID))
	{
		http_status(403);
		trigger_error('NO_MOD_UNAPPROVED');
	}
	
	// Query tags
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
		'FROM'		=> array(TAGMATCH_TABLE => 'tm'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TAGS_TABLE => 't'),
				'ON'	=> 'tm.tag_id = t.tag_id'
			),
			array(
				'FROM'	=> array(TAGCATS_TABLE => 'tc'),
				'ON'	=> 't.tagcat_id = tc.tagcat_id'
			),
		),
		'WHERE'		=> 'tm.topic_id = ' . $mod['topic_id'],
	));
	$result = $db->sql_query($sql);

	// Group tags into categories
	$tagcats = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$tagcats[$row['tagcat_id']][] = $row;
	}
	
	foreach ($tagcats as $tagcat)
	{
		// Category variables
		$template->assign_block_vars('tagcat', array(
			'TAGCAT_NAME'	=> $tagcat[0]['tagcat_name'],
			'TAGCAT_TITLE'	=> $tagcat[0]['tagcat_title'],
			'U_TAGCAT'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=tagcat&amp;cat=' . $tagcat[0]['tagcat_name']),
		));
		
		// Tag details
		foreach($tagcat as $tag)
		{
			$template->assign_block_vars('tagcat.tag', array(
				'TAG_TITLE'	=> $tag['tag_title'],
				'U_TAG'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=listtag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name']),
			));
		}
	}
	
	// Update topic view - needed?
	/*if (isset($user->data['session_page']) && (strpos($user->data['session_page'], '&id=' . $mod['mod_id']) && strpos($user->data['session_page'], '&t=' . $mod['topic_id']) === false) === false)
	{
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
			WHERE topic_id = {$article['topic_id']}";
		$db->sql_query($sql);
	}*/
	
	if ($mod['mod_size'] > 1024 * 1024)
	{
		$mod_size = number_format(($mod['mod_size'] / (1024 * 1024)), 2, ',', ' ') . ' MB';
	}
	else
	{
		$mod_size = number_format(($mod['mod_size'] / 1024), 2, ',', ' ') . ' KB';
	}
	
	// Assign some vars
	$template->assign_vars(array(
		'MOD_TITLE'			=> $mod['mod_hu_title'],
		'MOD_EN_TITLE'		=> $mod['mod_en_title'],
		'MOD_ID'			=> $mod['mod_id'],
		'MOD_DB_ID'			=> $mod['mod_db_id'],
		'MOD_FILENAME'		=> $mod['mod_filename'],
		'MOD_VERSION'		=> $mod['mod_version'],
		'MOD_MD5'			=> $mod['mod_md5'],
		'MOD_SIZE'			=> $mod_size,
		'MOD_AUTHOR'		=> $mod['mod_author_name'],
		'U_MOD_AUTHOR'		=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&ampu=' . $mod['mod_author_id'],
		'MOD_AUTHOR_FULL'	=> '<a href="http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=' . $mod['mod_author_id'] . '">' . $mod['mod_author_name'] . '</a>',
		'MOD_DESC'			=> nl2br($mod['mod_desc']),
		'MOD_POSTER'		=> get_username_string('username', $mod['topic_poster'], $mod['topic_first_poster_name'], $mod['topic_first_poster_colour']),
		'MOD_POSTER_COLOUR'	=> get_username_string('colour', $mod['topic_poster'], $mod['topic_first_poster_name'], $mod['topic_first_poster_colour']),
		'MOD_POSTER_FULL'	=> get_username_string('full', $mod['topic_poster'], $mod['topic_first_poster_name'], $mod['topic_first_poster_colour']),
		'U_MOD_TOPIC'		=> append_sid("{$phpbb_root_path}viewtopic.{$phpEx}", 'f=' . MODS_FORUM_ID . "&amp;t={$mod['topic_id']}"),
		'U_COM_PAGE'		=> 'http://www.phpbb.com/mods/db/index.php?i=misc&mode=display&contrib_id=' . $mod['mod_db_id'],
	
		'DOWNLOAD_MOD'		=> sprintf($user->lang['DOWNLOAD_MOD'], $mod['mod_hu_title'], $mod['mod_version']),
		// Provide direct link for the time being
		'U_DOWNLOAD'		=> $config['downloads_path'] . 'mods/' . $mod['mod_filename'] . '.zip',
	
		'U_MODS_DB'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx),
	));
	
	// Display sidemenu
	$sidemenu = new sidemenu();
	
	$sidemenu->add_block('MODS_DB');
	$sidemenu->add_link('MODS_DB_INDEX', "{$phpbb_root_path}mods.{$phpEx}");
	if ($auth->acl_get('f_c_post', MODS_FORUM_ID))
	{
		$sidemenu->add_link('ADD_MOD', "{$phpbb_root_path}mods.{$phpEx}", 'mode=add');
	}
	
	// Display tags in the sidemenu
	$sidemenu->add_block('TAGS');
	
	$sql = 'SELECT tagcat_id, tagcat_name, tagcat_title
		FROM ' . TAGCATS_TABLE . '
		WHERE tagcat_module = ' . TAG_MODS;
	$result = $db->sql_query($sql);
	
	while ($tagcat = $db->sql_fetchrow($result))
	{
		$sidemenu->add_link($tagcat['tagcat_title'], "{$phpbb_root_path}mods.{$phpEx}", 'mode=tagcat&cat=' . $tagcat['tagcat_name']);
	}
	
	// Actions
	$sidemenu->add_block('ACTIONS');
	if (($auth->acl_get('f_c_edit', MODS_FORUM_ID) && $mod['topic_poster'] == $user->data['user_id']) || $auth->acl_get('m_edit', MODS_FORUM_ID))
	{
		$sidemenu->add_link('EDIT_MOD', $phpbb_root_path . 'mods.' . $phpEx, 'mode=edit&amp;id=' . $mod['mod_id']);
	}
	if (!$mod['topic_approved'] && $auth->acl_get('m_approve', MODS_FORUM_ID))
	{
		$sidemenu->add_link('APPROVE_MOD', "{$phpbb_root_path}mcp.$phpEx", "i=queue&amp;mode=approve_details&amp;t={$mod['topic_id']}", true);
	}
	if ($auth->acl_get('m_delete', MODS_FORUM_ID) || ($mod['topic_poster'] == $user->data['user_id'] && $auth->acl_get('m_delete', MODS_FORUM_ID)))
	{
		$sidemenu->add_link('DELETE_MOD', $phpbb_root_path . 'mods.' . $phpEx, 'mode=delete&amp;id=' . $mod['mod_id']);
	}
	
	// Output page
	site_header(
		$user->lang['MODS_DB'] . ': ' . $mod['mod_hu_title'] . ' ' . $mod['mod_version'],
		'mods',
		array(
			array('mods.' . $phpEx, 'MODS_DB'),
			array('mods.' . $phpEx . '?mode=mod&amp;id=' . $mod['mod_id'], $mod['mod_hu_title'] . ' ' . $mod['mod_version']),
		)
	);

	$template->set_filenames(array(
		'body' => 'mods_mod.html')
	);

	site_footer();
}

/**
* Add or edit MOD
*/
elseif ($mode == 'add' || $mode == 'edit')
{
	$mod_id = request_var('id', 0);
	
	// Load language file
	$user->add_lang('posting');
	
	// Include files
	include("{$phpbb_root_path}includes/functions_user.{$phpEx}");
	include("{$phpbb_root_path}includes/functions_posting.{$phpEx}");
	include("{$phpbb_root_path}includes/site/functions_mods.{$phpEx}");
	include("{$phpbb_root_path}includes/site/functions_filesystem.{$phpEx}");
	include("{$phpbb_root_path}includes/functions_compress.{$phpEx}");
	include("{$phpbb_root_path}includes/functions_upload.{$phpEx}");
	
	// Schema for $article_data
	$mod_data = array(
		'mod_id'			=> null,
		'mod_db_id'			=> null,
		'mod_com_url'		=> null,
		'mod_filename'		=> null,
		'mod_hu_title'		=> null,
		'mod_en_title'		=> null,
		'mod_version'		=> null,
		'mod_md5'			=> null,
		'mod_size'			=> null,
		'mod_author_id'		=> null,
		'mod_author_name'	=> null,
		'mod_desc'			=> null,
	
		'topic_id'			=> null,
	
		'post_edit_locked'	=> 0,

		// Topic data
		'topic_id'				=> null,
		'topic_poster'			=> $user->data['user_id'], // Set default to current user
		'topic_replies_real'	=> null,
		'topic_first_post_id'	=> null,
		'topic_first_poster_name'=> null,
		'topic_last_post_id'	=> null,
	
		// Post data
		'post_id'			=> null,
		'poster_id'			=> $user->data['user_id'], // Set default to current user
		'post_time'			=> time(), // Set default to current time
		'post_edit_reason'	=> null,
		'post_edit_locked'	=> 0, // Set deafult value to false
		
		'enable_bbcode'		=> 1,
		'enable_smilies'	=> 0,
		'enable_magic_url'	=> 0,
		'bbcode_uid'		=> null,
		'bbcode_bitfield'	=> null,
	);
	
	if ($mode == 'add')
	{
		// Does the user have the necessary permission to post?
		if (!$auth->acl_get('f_c_post', MODS_FORUM_ID))
		{
			http_status(403);
			trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
		}
	}
	
	// Get MOD details
	elseif ($mode == 'edit')
	{
		$sql = 'SELECT m.*,
				t.topic_poster, t.topic_replies_real, t.topic_first_post_id, t.topic_first_poster_name, t.topic_last_post_id,
				p.post_id, p.poster_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.bbcode_uid, p.post_edit_reason, p.post_edit_locked
			FROM ' . MODS_TABLE . ' m
			LEFT JOIN ' . TOPICS_TABLE . ' t
				ON m.topic_id = t.topic_id
			LEFT JOIN ' . POSTS_TABLE . ' p
				ON t.topic_first_post_id = p.post_id
			WHERE m.mod_id = ' . $mod_id;
		$result = $db->sql_query($sql);
		
		if (($mod_db_data = $db->sql_fetchrow($result)) == false)
		{
			trigger_error('NO_MOD', E_USER_NOTICE);
		}
		
		// Update article data while preserving the original schema
		$mod_data = array_merge($mod_data, $mod_db_data);
		
		// Check if the user has the necessary permissions
		if (($mod_data['topic_poster'] != $user->data['user_id'] || !$auth->acl_get('f_c_edit', MODS_FORUM_ID)) && !$auth->acl_get('m_edit', MODS_FORUM_ID))
		{
			http_status(403);
			trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
		}
	}

	// Get data from the form
	if ($mode != 'edit' || isset($_POST['submit']))
	{
		$mod_get_data = array(
			'mod_hu_title'		=> utf8_normalize_nfc(request_var('mod_title', '', true)),
			'mod_com_url'		=> request_var('mod_com_url', ''),
			'mod_desc'			=> utf8_normalize_nfc(request_var('mod_desc', '', true)),
			'article_content'	=> utf8_normalize_nfc(request_var('article_content', '', true)),
		);
		$mod_data = array_merge($mod_data, $mod_get_data);
	}

	/**
	* Processing phase
	*/
	$error = array();
	
	// Run checks on the submitted data
	if (isset($_POST['submit']))
	{
		// First check for security
		if (!check_form_key('add_mod'))
		{
			trigger_error('FORM_INVALID');
		}
		
		if (!utf8_clean_string($mod_data['mod_hu_title']))
		{
			$error[] = 'NO_MOD_TITLE';
		}
		
		if (!utf8_clean_string($mod_data['mod_desc']))
		{
			$error[] = 'NO_MOD_DESC';
		}

		// File upload
		$upload = new fileupload('', array('zip'));
		if ($upload->is_valid('mod_loc_pack'))
		{
			$file = $upload->form_upload('mod_loc_pack');
			
			if (!empty($file->error))
			{
				$error += $file->error;
			}
		}
		
		// 
		if ($mode == 'add' && !preg_match('#^(?:http\://www\.phpbb\.com/mods/db/index\.php\?i\=misc&amp;mode\=display&amp;contrib_id\=)?([0-9]+)$#', $mod_data['mod_com_url'], $match))
		{
			$error[] = 'NO_COM_URL_FORMAT';
		}
		else
		{
			try
			{
				if ($mode == 'add')
				{
					list(, $mod_data['mod_db_id']) = $match;
					
					$sql = 'SELECT mod_id FROM ' . MODS_TABLE . ' WHERE mod_db_id = ' . $mod_data['mod_db_id'];
					$result = $db->sql_query($sql);
					
					if ($db->sql_fetchrow($result))
					{
						// Not the proper way but this is the most simple solution
						throw new ModException(array('MOD_EXISTS'));
					}
				}
					
				$mod = new mod_pack($mod_data['mod_db_id']);
				$mod->get_pack_details();
				$mod->get_archive();
				
				// Move localisation pack to its place since we have now the filename
				if(isset($file) && !sizeof($file->error))
				{
					rename($file->filename, $config['mods_tmp_dir_path'] . '/localisations/' . $mod->filename . '.zip');
				}
				
				$mod->merge_packs((bool) sizeof($error));
			}
			catch(ModException $e)
			{
				$error += $e->getErrors();
			}
			
			if (empty($error))
			{
				// Store localisation pack
				if (file_exists($config['mods_loc_store_path'] . $mod->filename . '.zip'))
				{
					unlink($config['mods_loc_store_path'] . $mod->filename . '.zip');
				}
				copy($phpbb_root_path . $mod->tmp_dir . 'localisations/' . $mod->filename . '.zip', $config['mods_loc_store_path'] . $mod->filename . '.zip');
			}
		}
	}

	/**
	* Update the database
	*/
	if (isset($_POST['submit']) && !sizeof($error))
	{
		// Just to make sure (also easier development; although not every database (or table) engine supports it)
		$db->sql_transaction('begin');
		
		$mod_data['mod_en_title']	= $mod->data['title'];
		$mod_data['mod_md5']		= $mod->data['md5'];
		$mod_data['mod_version']	= $mod->data['version'];
		$mod_data['mod_author_id']	= $mod->data['author']['id'];
		$mod_data['mod_author_name']= $mod->data['author']['name'];
		$mod_data['mod_size']		= $mod->data['size'];
		$mod_data['mod_filename']	= $mod->filename;

		/**
		* Insert into our own custom database (if adding: without the topic id for the time being)
		*/
		$sql_ary = array(
			'mod_db_id'			=> $mod_data['mod_db_id'],
			'mod_filename'		=> $mod_data['mod_filename'],
			'mod_hu_title'		=> $mod_data['mod_hu_title'],
			'mod_en_title'		=> $mod_data['mod_en_title'],
			'mod_filename'		=> $mod_data['mod_filename'],
			'mod_version'		=> $mod_data['mod_version'],
			'mod_md5'			=> $mod_data['mod_md5'],
			'mod_size'			=> $mod_data['mod_size'],
			'mod_author_id'		=> $mod_data['mod_author_id'],
			'mod_author_name'	=> $mod_data['mod_author_name'],
			'mod_desc'			=> $mod_data['mod_desc'],
		);
		
		if ($mode == 'add')
		{
			$sql = 'INSERT INTO ' . MODS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			
			$mod_data['mod_id'] = $db->sql_nextid();
		}
		elseif ($mode == 'edit')
		{
			$sql = 'UPDATE ' . MODS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE mod_id = ' . $mod_data['mod_id'];
			$db->sql_query($sql);
		}
		
		/**
		* Localise tags
		* @todo Move this into a function
		*/
		// Get all possible tags
		$sql = $db->sql_build_query('SELECT', array(
			'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
			'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
			'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tagcat_module = ' . TAG_MODS,
		));
		$result = $db->sql_query($sql);
		
		// Group tags into categories
		// @todo Are all of them needed?
		$tags_by_id = $tagcats_by_id_id = $tagcats_by_id_name = $tagcats_by_name_id = $tagcats_by_name_name = array();
		while($row = $db->sql_fetchrow($result))
		{
			$tags_by_id[$row['tag_id']] = $row;
			
			if (!isset($tagcats_by_id_ids[$row['tagcat_id']]))
			{
				$tagcats_by_id_id[$row['tagcat_id']] = $tagcats_by_id_name[$row['tagcat_id']] = $tagcats_by_name_id[$row['tagcat_name']] = $tagcats_by_name_name[$row['tagcat_name']][$row['tag_name']] = array();
			}
			
			$tagcats_by_id_id[$row['tagcat_id']][$row['tag_id']] = &$tags_by_id[$row['tag_id']];
			$tagcats_by_id_name[$row['tagcat_id']][$row['tag_name']] = &$tags_by_id[$row['tag_id']];
			$tagcats_by_name_id[$row['tagcat_name']][$row['tag_id']] = &$tags_by_id[$row['tag_id']];
			$tagcats_by_name_name[$row['tagcat_name']][$row['tag_name']] = &$tags_by_id[$row['tag_id']];
		}
		$db->sql_freeresult($result);
		
		// Load the tag translation table
		include("{$phpbb_root_path}includes/site/data/mod_tags.{$phpEx}");
		
		$mod_tags = array();
		
		foreach ($mod->tags as $tagcat_en_name => $tagcat)
		{
			$tagcat_hu_name = (isset($tag_translation_table[$tagcat_en_name])) ? $tag_translation_table[$tagcat_en_name]['name'] : $tagcat_en_name;
			
			// @todo Create tag category if it doesn't exist?
			
			if (isset($tagcats_by_name_name[$tagcat_hu_name]))
			{
				foreach ($tagcat as $tag)
				{
					list($tag_en_name, $tag_en_title) = $tag;
					
					$tag_hu_name = (isset($tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name])) ? $tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name]['name'] : $tag_en_name;
					$tag_hu_title = (isset($tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name])) ? $tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name]['title'] : $tag_en_title;
					
					// Create tag
					if (!isset($tagcats_by_name_name[$tagcat_hu_name][$tag_hu_name]))
					{
						//$tagcat_info = each($tagcats_by_name_name[$tagcat_hu_name]);reset($tagcats_by_name_name[$tagcat_hu_name]);
						$tagcat_info = current($tagcats_by_name_name[$tagcat_hu_name]);
						
						$tag_info = array(
							'tagcat_id'	=> $tagcat_info['tagcat_id'],
							'tag_name'	=> $tag_hu_name,
							'tag_title'	=> $tag_hu_title,
						);
						
						$sql = 'INSERT INTO ' . TAGS_TABLE . ' ' . $db->sql_build_array('INSERT', $tag_info);
						$db->sql_query($sql);
						
						$tag_info['tag_id'] = $db->sql_nextid();
						$tag_info = array_merge($tagcat_info, $tag_info);
						
						// Populate cache arrays
						$tags_by_id[$tag_info['tag_id']] = $tag_info;
						
						$tagcats_by_id_id[$tagcat_info['tagcat_id']][$tag_info['tag_id']] = &$tags_by_id[$tag_info['tag_id']];
						$tagcats_by_id_name[$tagcat_info['tagcat_id']][$tag_info['tag_name']] = &$tags_by_id[$tag_info['tag_id']];
						$tagcats_by_name_id[$tagcat_info['tagcat_name']][$tag_info['tag_id']] = &$tags_by_id[$tag_info['tag_id']];
						$tagcats_by_name_name[$tagcat_info['tagcat_name']][$tag_info['tag_name']] = &$tags_by_id[$tag_info['tag_id']];
					}
					
					$mod_tags[] = $tagcats_by_name_name[$tagcat_hu_name][$tag_hu_name]['tag_id'];
				}
			}
		}
		
		/**
		* Generate post content
		*/
		include("{$phpbb_root_path}includes/message_parser.{$phpEx}");
		
		$message_parser = new parse_message();		
		$vars = array(
			'MOD_HU_TITLE'		=> $mod_data['mod_hu_title'],
			'MOD_EN_TITLE'		=> $mod_data['mod_en_title'],
			'MOD_VERSION'		=> $mod_data['mod_version'],
			'MOD_DESC'			=> $mod_data['mod_desc'],
			'MOD_AUTHOR'		=> $mod_data['mod_author_name'],
			'U_MOD_AUTHOR'		=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=' . $mod_data['mod_author_id'],
			'U_MOD_COM_DB'		=> 'http://www.phpbb.com/mods/db/index.php?i=misc&mode=display&contrib_id=' . $mod_data['mod_db_id'],
			'MOD_TAGS'			=> generate_tags_bbcode_list($mod_tags, $tagcats_by_name_name, array("{$phpbb_root_path}mods.{$phpEx}", "mode=listtag&cat=%1\$s&tag=%2\$s")),
			'U_MOD'				=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}mods.{$phpEx}", "mode=mod&id={$mod_data['mod_id']}"),
		);
		$message = generate_content_post('mod_pack', $vars);
		$message_md5 = md5($message);	
		$message_parser->message = &$message;
		$message_parser->parse(true, true, false, false, false, true, true);
		
		if (!empty($message_parser->warn_msg))
		{
			trigger_error(implode('<br />', $message_parser->warn_msg), E_USER_NOTICE);
		}
		
		/**
		* Submit the post
		*/
		// Query forum details
		$sql = 'SELECT forum_name, enable_indexing
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . MODS_FORUM_ID;
		$result = $db->sql_query($sql);
		$forum_data = $db->sql_fetchrow($result);
		$db->sql_freeresult();
		
		$data = array(
			'forum_id'			=> MODS_FORUM_ID,
			'topic_title'		=> $mod_data['mod_hu_title'],
			'icon_id'			=> 0,
			'enable_bbcode'		=> 1,
			'enable_smilies'	=> 0,
			'enable_urls'		=> 1,
			'enable_sig'		=> 0,
			'message'			=> $message_parser->message,
			'message_md5'		=> $message_md5,
			'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
			'bbcode_uid'		=> $message_parser->bbcode_uid,

			'post_edit_locked'	=> $mod_data['post_edit_locked'],
			'enable_indexing'	=> $forum_data['enable_indexing'],
			'notify'			=> false,
			'notify_set'		=> '',
			'post_time'			=> $mod_data['post_time'],
			'forum_name'		=> $forum_data['forum_name'],
		
			'post_edit_reason'		=> $mod_data['post_edit_reason'],
			'topic_replies_real'	=> $mod_data['topic_replies_real'],
			'poster_id'				=> $mod_data['poster_id'],
			'post_id'				=> &$mod_data['post_id'],
			'topic_id'				=> &$mod_data['topic_id'],
			'topic_poster'			=> $mod_data['topic_poster'],
			'topic_first_post_id'	=> $mod_data['topic_first_post_id'],
			'topic_last_post_id'	=> $mod_data['topic_last_post_id'],
		);
		$poll = false;
		
		submit_post(($mode == 'add' ? 'post' : 'edit'), $mod_data['mod_hu_title'], $mod_data['topic_first_poster_name'], POST_NORMAL, $poll, $data);
		
		// Now update the mod DB entry with the id of the topic
		if ($mode == 'add')
		{
			$sql = 'UPDATE ' . MODS_TABLE . ' SET topic_id = ' . $mod_data['topic_id'] . ' WHERE mod_id = ' . $mod_data['mod_id'];
			$db->sql_query($sql); 
		}
		
		/**
		* Store assigned tags
		*/
		// When editing first delete all assigned tags
		if ($mode == 'edit')
		{
			$sql = 'DELETE FROM ' . TAGMATCH_TABLE . ' WHERE topic_id = ' . $mod_data['topic_id'];
			$db->sql_query($sql);
		}
		
		// Generate array to be inserted to the database
		$sql_insert_ary = array();
		foreach ($mod_tags as $tag_id)
		{
			$sql_insert_ary[] = array(
				'topic_id'	=> $mod_data['topic_id'],
				'tag_id'	=> $tag_id,
			);
		}
		
		$db->sql_multi_insert(TAGMATCH_TABLE, $sql_insert_ary);
		
		// And finally commit the whole
		$db->sql_transaction('commit');

		// Give success messages
		// @todo Send out notifications to moderators with links to the translation pack?
		if ($auth->acl_get('f_noapprove', $data['forum_id']) || $auth->acl_get('m_approve', $data['forum_id']))
		{
			$redirect_url = append_sid("{$phpbb_root_path}mods.{$phpEx}", "mode=mod&amp;id={$mod_data['mod_id']}");
			$message = sprintf($user->lang[($mode == 'add' ? 'MOD_ADDED' : 'MOD_UPDATED')], '<a href="' . $redirect_url . '">', '</a>');
			meta_refresh(5, $redirect_url);
		}
		// If the article is waiting for approval do not redirect the user but tell him this
		else
		{
			$redirect_url = append_sid("{$phpbb_root_path}mods.{$phpEx}");
			$message = sprintf($user->lang[($mode == 'add' ? 'MOD_ADDED_MOD' : 'MOD_UPDATED_MOD')], '<a href="' . $redirect_url . '">', '</a>');
		}
		
		// Do cleanup
		$mod->cleanup();
		
		trigger_error($message);
	}

	// @todo Move this to a better place!!
	if (isset($mod))
	{
		$mod->cleanup();
	}
	
	$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);			
	
	/**
	* Display the form 
	*/
	add_form_key('add_mod');
	
	$template->assign_vars(array(
		// Assign "input" variables
		'MOD_TITLE'			=> $mod_data['mod_hu_title'],
		'MOD_COM_URL'		=> $mod_data['mod_com_url'],
		'MOD_DESC'			=> $mod_data['mod_desc'],
	
		'U_ACTION'			=> append_sid("{$phpbb_root_path}mods.{$phpEx}", ($mode == 'add' ? 'mode=add' : "mode=edit&amp;id={$mod_id}")),
		'S_MODE'			=> $mode,
	
		'ERROR'				=> (isset($error) && sizeof($error)) ? implode('<br />', $error) : false,
	
		'U_MODS_DB'			=> append_sid($phpbb_root_path . 'mods.' . $phpEx),
	));	
	
	/**
	* Display the page
	*/
	if ($mode == 'add')
	{
		site_header(
			$user->lang['MODS_DB'] . ' - ' . $user->lang['ADD_MOD'],
			'mods',
			array(array('mods.' . $phpEx, 'MODS_DB'), array("mods.{$phpEx}?mode=add", 'ADD_MOD'))
		);
	}
	elseif ($mode == 'edit')
	{
		site_header(
			$user->lang['MODS_DB'] . ' - ' . $user->lang['EDIT_MOD'],
			'mods',
			array(array('mods.' . $phpEx, 'MODS_DB'), array("mods.{$phpEx}?mode=mod&amp;id={$mod_data['mod_id']}", $mod_data['mod_hu_title']), array("mods.{$phpEx}?mode=edit&amp;id={$mod_id}", 'EDIT'))
		);
	}


	$template->set_filenames(array(
		'body' => 'mods_add.html')
	);

	site_footer();
}

/**
* Delete article
*/
elseif ($mode == 'delete')
{

}

/**
* Index page: display most recent articles and also list some tags
*/
else
{
	// First get all tags - cache for 6 hours
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
		'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
		'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tagcat_module = ' . TAG_MODS,
	));
	$result = $db->sql_query($sql, 21600);
	
	$tags = array();
	while($row = $db->sql_fetchrow($result))
	{
		$tags[$row['tag_id']] = $row;
	}
	$db->sql_freeresult($result);
	
	// Query last ten MODs
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'm.mod_id, m.topic_id, m.mod_hu_title, m.mod_version, m.mod_desc,
						t.topic_approved, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_first_poster_name, t.topic_first_poster_colour,
						GROUP_CONCAT(tm.tag_id) AS tags',
		'FROM'		=> array(MODS_TABLE => 'm'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'm.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(TAGMATCH_TABLE => 'tm'),
				'ON'	=> 'tm.topic_id = m.topic_id'
			)
		),

		'WHERE'		=> (($auth->acl_get('m_approve', MODS_FORUM_ID)) ? false : 't.topic_approved = 1'),
		'GROUP_BY'	=> 'm.mod_id',
		'ORDER_BY'	=> 't.topic_time DESC',
	));
	$result = $db->sql_query_limit($sql, 10);

	while ($row = $db->sql_fetchrow($result))
	{
		// Assign some general variables about the article
		$template->assign_block_vars('modrow', array(
			'MOD_TITLE'				=> $row['mod_hu_title'],
			'MOD_VERSION'			=> $row['mod_version'],
			'MOD_DESC'				=> trim_text($row['mod_desc'], 120),
			'MOD_ID'				=> $row['mod_id'],
			'U_MOD'					=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=mod&amp;id=' . $row['mod_id']),
			/*'MOD_POSTER'			=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'MOD_POSTER_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'MOD_POSTER_FULL'		=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),*/
			'MOD_POSTED'			=> $user->format_date($row['topic_time']),	
			//'MOD_VIEWS'			=> $row['topic_views'],	
			'MOD_APPROVED'			=> $row['topic_approved'],	
		
			'U_MCP_QUEUE'		=> ($auth->acl_get('m_approve', MODS_FORUM_ID)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=queue&amp;mode=approve_details&amp;t={$row['topic_id']}", true) : false,
			'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'TOPIC_UNAPPROVED'),
		));
		
		/* Isn't needed for the time being
		* @todo It still needs to be decided what info to show
		* One thing is sure, we don't want to display all tags
		// Assign tags
		$mod_tags = array();
		$tags_list = (!empty($row['tags'])) ? explode(',', $row['tags']) : array();
		
		// Group assigned tags into categories
		foreach ($tags_list as $tag)
		{
			$mod_tags[$tags[$tag]['tagcat_id']][] = &$tags[$tag]; // Maybe the index could be changed from tagcat_id to tagcat_name as the latter is more often used
		}
		
		// Sort the array containing the article tags in order to list them always in the same order
		ksort($mod_tags);
		
		// Loop over the tag categories
		foreach ($mod_tags as $tagcat)
		{
			// Category variables
			$template->assign_block_vars('articles.tagcats', array(
				'TAGCAT_NAME'	=> $tagcat[0]['tagcat_name'],
				'TAGCAT_TITLE'	=> $tagcat[0]['tagcat_title'],
				'U_TAGCAT'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=tagcat&amp;cat=' . $tagcat[0]['tagcat_name']),
			));
			
			// Tag details
			foreach($tagcat as $tag)
			{
				$template->assign_block_vars('articles.tagcats.tags', array(
					'TAG_TITLE'	=> $tag['tag_title'],
					'U_TAG'		=> append_sid($phpbb_root_path . 'mods.' . $phpEx, 'mode=listtag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name']),
				));
			}
		}*/
	}
	$db->sql_freeresult($result);
	
	// Assign some vars
	$template->assign_vars(array(
		'U_MODS_DB'			=> append_sid($phpbb_root_path . 'mods.' . $phpEx),
	));

	// Display sidemenu
	$sidemenu = new sidemenu();
	
	$sidemenu->add_block('MODS_DB');
	if ($auth->acl_get('f_c_post', MODS_FORUM_ID))
	{
		$sidemenu->add_link('ADD_MOD', "{$phpbb_root_path}mods.{$phpEx}", 'mode=add');
	}
	
	// Display tags in the sidemenu
	$tag_cats = array();
	foreach($tags as &$tag)
	{
		$tag_cats[$tag['tagcat_id']][] = $tag;
	}

	$sidemenu->add_block('TAGS');
	
	foreach ($tag_cats as $tag_cat)
	{
		$sidemenu->add_link($tag_cat[0]['tagcat_title'], "{$phpbb_root_path}mods.{$phpEx}", 'mode=tagcat&cat=' . $tag_cat[0]['tagcat_name']);
	}
	
	// Output page
	site_header($user->lang['MODS_DB'], 'mods', array(array('mods.' . $phpEx, 'MODS_DB')));

	$template->set_filenames(array(
		'body' => 'mods_index.html')
	);

	site_footer();
}
?>