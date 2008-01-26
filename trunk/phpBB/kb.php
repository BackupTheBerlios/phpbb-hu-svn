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
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('site');

define('SITE_SECTION', 'kb');

$mode = request_var('mode', '');

// Check permission to see the knowledge base
if (!$auth->acl_get('f_c_see', KB_FORUM_ID))
{
	http_status(403);
	trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
}

/**
* List tags (together with their possible values) 
*/
if ($mode == 'listtags')
{
	http_status(404);
	trigger_error('Ez a funkció még nem került implementálásra.');
}

/**
* Article display page
*/
elseif ($mode == 'article')
{
	$article_name = request_var('name', '');
	$action = request_var('action', '');
	
	// Query article details
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'a.article_id, a.topic_id, a.article_name, a.article_desc, a.article_content,
						t.topic_title, t.topic_title, t.topic_poster, t.topic_time, t.topic_first_poster_name, t.topic_first_poster_colour, t.topic_approved,
						p.post_text, p.bbcode_uid, p.bbcode_bitfield, p.enable_bbcode, p.enable_smilies, p.enable_magic_url',
		'FROM'		=> array(KB_ARTICLES_TABLE => 'a'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'a.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(POSTS_TABLE => 'p'),
				'ON'	=> 't.topic_first_post_id = p.post_id'
			),
		),
		'WHERE'		=> "a.article_name = '" . $db->sql_escape($article_name) . "'" . (($auth->acl_get('m_approve', KB_FORUM_ID)) ? '' : ' AND t.topic_approved = 1 OR t.topic_poster = ' . $user->data['user_id']),
	));
	$result = $db->sql_query($sql);
	
	if (($article = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_ARTICLE', E_USER_NOTICE);
	}
	
	/**
	* (Dis)approval process
	*/
	if (($action == 'approve' || $action == 'disapprove') && $auth->acl_get('m_approve', KB_FORUM_ID))
	{
		/**
		* @todo Implement this feature
		*  - present a confirm window first
		*  - maybe we could leave this out entirely as this feature already exists in the MCP
		*/
		trigger_error('Ez a funkció egyelőre (?) nem működik. A jóváhagyáshoz vagy elutasításhoz menj a moderátori vezérlőpultba.');
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
		'WHERE'		=> 'tm.topic_id = ' . $article['topic_id'],
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
			'U_TAGCAT'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=listtagcats&amp;cat=' . $tagcat[0]['tagcat_name']),
		));
		
		// Tag details
		foreach($tagcat as $tag)
		{
			$template->assign_block_vars('tagcat.tag', array(
				'TAG_TITLE'	=> $tag['tag_title'],
				'U_TAG'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=tag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name']),
			));
		}
	}
	
	// Update topic view
	if (isset($user->data['session_page']) && (strpos($user->data['session_page'], '&name=' . $article['article_name']) && strpos($user->data['session_page'], '&t=' . $article['topic_id']) === false) === false)
	{
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
			WHERE topic_id = {$article['topic_id']}";
		$db->sql_query($sql);
	}
	
	// Set the value of bbcode options which is needed for generate_text_for_display()
	$article['bbcode_options'] = (($article['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($article['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($article['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
	
	// Assign some vars
	$template->assign_vars(array(
		'ARTICLE_TITLE'			=> $article['topic_title'],
		'ARTICLE_DESC'			=> $article['article_desc'],
		'ARTICLE_ID'			=> $article['article_id'],
		'ARTICLE_NAME'			=> $article['article_name'],
		'U_ARTICLE'				=> append_sid("{$phpbb_root_path}kb.{$phpEx}", 'mode=article&amp;name=' . $article['article_name']),
		'ARTICLE_POSTED'		=> $user->format_date($article['topic_time']),
		'ARTICLE_AUTHOR'		=> get_username_string('username', $article['topic_poster'], $article['topic_first_poster_name'], $article['topic_first_poster_colour']),
		'ARTICLE_AUTHOR_COLOUR'	=> get_username_string('colour', $article['topic_poster'], $article['topic_first_poster_name'], $article['topic_first_poster_colour']),
		'ARTICLE_AUTHOR_FULL'	=> get_username_string('full', $article['topic_poster'], $article['topic_first_poster_name'], $article['topic_first_poster_colour']),
	
		'ARTICLE_TEXT'		=> generate_text_for_display($article['article_content'], $article['bbcode_uid'], $article['bbcode_bitfield'], $article['bbcode_options']),
	
		'U_KB'				=> append_sid($phpbb_root_path . 'kb.' . $phpEx),
	));
	
	// Display sidemenu
	$sidemenu = new sidemenu();
	$sidemenu->add_block('KB', $phpbb_root_path . 'kb.' . $phpEx);
	if ($auth->acl_get('f_c_post', KB_FORUM_ID))
	{
		$sidemenu->add_link('ADD_ARTICLE', $phpbb_root_path . 'kb.' . $phpEx, 'mode=add');
	}
	if (($auth->acl_get('f_c_edit', KB_FORUM_ID) && $article['topic_poster'] == $user->data['user_id']) || $auth->acl_get('m_edit', KB_FORUM_ID))
	{
		$sidemenu->add_link('EDIT_ARTICLE', $phpbb_root_path . 'kb.' . $phpEx, 'mode=edit&amp;id=' . $article['article_id']);
	}
	if (!$article['topic_approved'] && $auth->acl_get('m_approve', KB_FORUM_ID))
	{
		$sidemenu->add_link('ARTICLE_APPROVE', "{$phpbb_root_path}mcp.$phpEx", "i=queue&amp;mode=approve_details&amp;t={$article['topic_id']}", true);
	}
	if ($auth->acl_get('m_delete', KB_FORUM_ID) || ($article['topic_poster'] == $user->data['user_id'] && $auth->acl_get('m_delete', KB_FORUM_ID)))
	{
		$sidemenu->add_link('DELETE_ARTICLE', $phpbb_root_path . 'kb.' . $phpEx, 'mode=delete&amp;id=' . $article['article_id']);
	}
		
	// Output page
	site_header(
		sprintf($user->lang['VIEW_ARTICLE'], $article['topic_title']),
		'kb',
		array(array('kb.' . $phpEx, 'KB'), array("kb.$phpEx?mode=article&amp;name={$article['article_name']}", $article['topic_title']))
	);
		
	$template->set_filenames(array(
		'body' => 'kb_article.html')
	);

	site_footer();
}

/**
* Redirect page in order to make old urls compatible
*/
elseif ($mode == 'article_id')
{
	$article_id = request_var('id', 0);
	
	$sql = 'SELECT article_name FROM ' . KB_ARTICLES_TABLE . ' WHERE article_id = ' . $article_id;
	$result = $db->sql_query($sql);
	
	if (($article = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_ARTICLE');
	}
	
	// Redirect user to the new url
	http_status(301);
	header('Location: ' . generate_board_url() . '/' . append_sid("{$phpbb_root_path}kb.{$phpEx}", 'mode=article&name=' . $article['article_name']));
	exit_handler();
}

/**
* Add or edit article
*/
elseif ($mode == 'add' || $mode == 'edit')
{
	$article_id = request_var('id', 0);
	$tags = request_var('article_tags', array(0));
	
	// Load language file
	$user->add_lang('posting');
	
	// Include files
	include("{$phpbb_root_path}includes/functions_user.{$phpEx}");
	include("{$phpbb_root_path}includes/functions_posting.{$phpEx}");

	// Schema for $article_data
	$article_data = array(
		'article_title'		=> null,
		'article_name'		=> null,
		'article_desc'		=> null,
		'article_content'	=> null,
	
		'enable_bbcode'		=> null,
		'enable_smilies'	=> null,
		'enable_magic_url'	=> null,
		'bbcode_uid'		=> null,
		'bbcode_bitfield'	=> null,
	
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
	);
	
	if ($mode == 'add')
	{
		// Does the user have the necessary permission to post?
		if (!$auth->acl_get('f_c_post', KB_FORUM_ID))
		{
			http_status(403);
			trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
		}
	}
	
	// Get article details
	elseif ($mode == 'edit')
	{
		$sql = 'SELECT a.*, t.topic_title as article_title,
				t.topic_poster, t.topic_replies_real, t.topic_first_post_id, t.topic_first_poster_name, t.topic_last_post_id,
				p.post_id, p.poster_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.bbcode_uid, p.post_edit_reason, p.post_edit_locked
			FROM ' . KB_ARTICLES_TABLE . ' a
			LEFT JOIN ' . TOPICS_TABLE . ' t
				ON a.topic_id = t.topic_id
			LEFT JOIN ' . POSTS_TABLE . ' p
				ON t.topic_first_post_id = p.post_id
			WHERE a.article_id = ' . $article_id;
		$result = $db->sql_query($sql);
		
		if (($article_db_data = $db->sql_fetchrow($result)) == false)
		{
			trigger_error('NO_ARTICLE', E_USER_NOTICE);
		}
		
		// Update article data while preserving the original schema
		$article_data = array_merge($article_data, $article_db_data);
		
		// Check if the user has the necessary permissions
		if (($article_data['topic_poster'] != $user->data['user_id'] || !$auth->acl_get('f_c_edit', KB_FORUM_ID)) && !$auth->acl_get('m_edit', KB_FORUM_ID))
		{
			http_status(403);
			trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
		}
	}


	// BBCode Statuses
	$bbcode_status	= true; // ($config['allow_bbcode'] && $auth->acl_get('f_bbcode', KB_FORUM_ID)) ? true : false;
	$smilies_status	= ($bbcode_status && $config['allow_smilies'] && $auth->acl_get('f_smilies', KB_FORUM_ID)) ? true : false;
	$img_status		= ($bbcode_status && $auth->acl_get('f_img', KB_FORUM_ID)) ? true : false;
	$url_status		= ($config['allow_post_links']) ? true : false;
	$flash_status	= ($bbcode_status && $auth->acl_get('f_flash', KB_FORUM_ID) && $config['allow_post_flash']) ? true : false;
	
	// Get data from the form
	if ($mode != 'edit' || isset($_POST['preview']) || isset($_POST['submit']))
	{
		$article_get_data = array(
			'article_title'		=> utf8_normalize_nfc(request_var('article_title', '', true)),
			'article_name'		=> request_var('article_name', ''),
			'article_desc'		=> utf8_normalize_nfc(request_var('article_desc', '', true)),
			'article_content'	=> utf8_normalize_nfc(request_var('article_content', '', true)),
			'enable_bbcode'		=> 1,
			'enable_smilies'	=> $smilies_status ? (isset($_POST['disable_smilies']) ? 0 : 1) : 0,
			'enable_magic_url'	=> $url_status ? (isset($_POST['disable_magic_url']) ? 0 : 1) : 0,
		);
		$article_data = array_merge($article_data, $article_get_data);
	}
	// Get data entirely from the db (on edit)
	else
	{
		decode_message($article_data['article_content'], $article_data['bbcode_uid']);
	}

	/**
	* Tags...
	*/
	// Get assigned tags (when editing)
	if ($mode == 'edit' && !isset($_POST['submit']) && !isset($_POST['preview']))
	{
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
			'WHERE'		=> 'tm.topic_id = ' . $article_data['topic_id'],
		));
		$result = $db->sql_query($sql);
	
		while ($row = $db->sql_fetchrow($result))
		{
			$tags[] = $row['tag_id'];
		}
	}
	
	// Get all possible tags and group them into categories
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
		'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
		'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tagcat_module = ' . TAG_KB,
	));
	$result = $db->sql_query($sql, 21600);
	
	$tagcats = array();
	while($row = $db->sql_fetchrow($result))
	{
		$tagcats[$row['tagcat_id']][] = $row;
	}
	$db->sql_freeresult($result);
	
	// Generate selects for the tags
	foreach ($tagcats as $tagcat)
	{
		$tag_options = array();
		foreach ($tagcat as $tag)
		{
			$tag_options .= '<option value="' . $tag['tag_id'] . '"' . (in_array($tag['tag_id'], $tags) ? ' selected="selected"' : '') . '>' . $tag['tag_title'] . '</option>';
		}
		
		$template->assign_block_vars('tagcat', array(
			'TAGCAT_TITLE'	=> $tagcat[0]['tagcat_title'],
			'TAGCAT_NAME'	=> $tagcat[0]['tagcat_name'],
			'TAGCAT_ID'		=> $tagcat[0]['tagcat_id'],
			'TAG_OPTIONS'	=> $tag_options,
			'SELECT_SIZE'	=> min(count($tagcat), 5),
		));
	}

	/**
	* Processing phase
	*/
	// Run checks on the submitted data
	if (isset($_POST['submit']) || isset($_POST['preview']))
	{
		// First check for security
		if (!check_form_key('add_article'))
		{
			trigger_error('FORM_INVALID');
		}
		
		$error = array();
		
		if (!utf8_clean_string($article_data['article_title']))
		{
			$error[] = 'NO_ARTICLE_TITLE';
		}
		
		if (!utf8_clean_string($article_data['article_name']))
		{
			$error[] = 'NO_ARTICLE_NAME';
		}
		elseif (!preg_match('#^([a-z0-9_-]+)$#is', $article_data['article_name']))
		{
			$error[] = 'NO_ARTICLE_NAME_FORMAT';
		}
		// Check whether an another article with this name exits
		else
		{
			$sql = 'SELECT article_id
				FROM ' . KB_ARTICLES_TABLE . "
				WHERE article_name = '" . $db->sql_escape($article_data['article_name']) . "'
				" . ($mode == 'edit' ? 'AND article_id <> ' . $article_id : '');
			$result = $db->sql_query($sql);
			if ($db->sql_fetchrow($result) != false)
			{
				$error[] = 'NO_ARTICLE_NAME_EXISTS';
			}
		}
		
		if (!utf8_clean_string($article_data['article_desc']))
		{
			$error[] = 'NO_ARTICLE_DESC';
		}
		// Strlen (AFAIK) is not utf-8 compatible, but this is what we need when entering data to the database
		elseif (strlen($article_data['article_desc']) > 255)
		{
			$error[] = 'NO_ARTICLE_DESC_LONG';
		}

		if (!utf8_clean_string($article_data['article_content']))
		{
			$error[] = 'NO_ARTICLE_CONTENT';
		}
		
		// Check whether the assigned tags can really be assigned
		if (!empty($tags))
		{
			$sql = $db->sql_build_query('SELECT', array(
				'SELECT'	=> 't.tag_id',
				'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
				'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tc.tagcat_module = ' . TAG_KB . ' AND ' . $db->sql_in_set('t.tag_id', $tags),
			));
			$result = $db->sql_query($sql);
			
			if (sizeof($db->sql_fetchrowset($result)) != sizeof($tags))
			{
				$error[] = 'NO_TAG_VALID';
			}
		}
		
		$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);		
	}

	// Start parsing the content
	if (isset($_POST['submit']) || isset($_POST['preview']))
	{
		include("{$phpbb_root_path}includes/message_parser.{$phpEx}");
		
		$message_parser = new parse_message();
		$message_parser->message = $article_data['article_content'];
		$message_parser->parse($article_data['enable_bbcode'], $article_data['enable_magic_url'], $article_data['enable_smilies'], $img_status, $flash_status, true, true);
	
		$article_content_parsed = $message_parser->message;	
	}
	
	/**
	* Update the database
	*/
	if (isset($_POST['submit']) && !sizeof($error))
	{
		// Just to make sure (also easier development; although not every database (or table) engine supports it)
		$db->sql_transaction('begin');
		
		/**
		* Generate post content
		*/
		$vars = array(
			'ARTICLE_TITLE'		=> $article_data['article_title'],
			'ARTICLE_DESC'		=> $article_data['article_desc'],
			'ARTICLE_CONTENT'	=> $article_data['article_content'],
			'ARTICLE_TAGS'		=> generate_tags_bbcode_list($tags, $tagcats, "{$phpbb_root_path}kb.{$phpEx}?mode=tag&cat=%1\$s&name=%2\$s"),
			'U_ARTICLE'			=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}kb.{$phpEx}", "mode=article&name={$article_data['article_name']}"),
		);
		$message = generate_content_post('kb_article', $vars);
		$message_md5 = md5($message);
		
		$message_parser->message = &$message;
		$message_parser->parse(true, $url_status, $smilies_status, $img_status, $flash_status, true, true);
		
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
			WHERE forum_id = ' . KB_FORUM_ID;
		$result = $db->sql_query($sql);
		$forum_data = $db->sql_fetchrow($result);
		$db->sql_freeresult();
		
		$data = array(
			'forum_id'			=> KB_FORUM_ID,
			'topic_title'		=> $article_data['article_title'],
			'icon_id'			=> 0,
			'enable_bbcode'		=> 1,
			'enable_smilies'	=> $article_data['enable_smilies'],
			'enable_urls'		=> $article_data['enable_magic_url'],
			'enable_sig'		=> 0,
			'message'			=> $message_parser->message,
			'message_md5'		=> $message_md5,
			'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
			'bbcode_uid'		=> $message_parser->bbcode_uid,

			'post_edit_locked'	=> $article_data['post_edit_locked'],
			'enable_indexing'	=> $forum_data['enable_indexing'],
			'notify'			=> false,
			'notify_set'		=> '',
			'post_time'			=> $article_data['post_time'],
			'forum_name'		=> $forum_data['forum_name'],
		
			'post_edit_reason'		=> $article_data['post_edit_reason'],
			'topic_replies_real'	=> $article_data['topic_replies_real'],
			'poster_id'				=> $article_data['poster_id'],
			'post_id'				=> &$article_data['post_id'],
			'topic_id'				=> &$article_data['topic_id'],
			'topic_poster'			=> $article_data['topic_poster'],
			'topic_first_post_id'	=> $article_data['topic_first_post_id'],
			'topic_last_post_id'	=> $article_data['topic_last_post_id'],
		);
		$poll = false;
		
		submit_post(($mode == 'add' ? 'post' : 'edit'), $article_data['article_title'], $article_data['topic_first_poster_name'], POST_NORMAL, $poll, $data);
		
		/**
		* Insert into our own custom database
		*/
		$sql_ary = array(
			'topic_id'			=> $article_data['topic_id'],
			'article_name'		=> $article_data['article_name'],
			'article_desc'		=> $article_data['article_desc'],
			'article_content'	=> $article_content_parsed,
		);
		
		if ($mode == 'add')
		{
			$sql = 'INSERT INTO ' . KB_ARTICLES_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			
			$article_data['article_id'] = $db->sql_nextid();
		}
		elseif ($mode == 'edit')
		{
			$sql = 'UPDATE ' . KB_ARTICLES_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE article_id = ' . $article_data['article_id'];
			$db->sql_query($sql);
		}
		
		/**
		* Store assigned tags
		*/
		// When editing first delete all assigned tags
		if ($mode == 'edit')
		{
			$sql = 'DELETE FROM ' . TAGMATCH_TABLE . ' WHERE topic_id = ' . $article_data['topic_id'];
			$db->sql_query($sql);
		}
		
		// Generate array to be inserted to the database
		$sql_insert_ary = array();
		foreach ($tags as $tag_id)
		{
			$sql_insert_ary[] = array(
				'topic_id'	=> $article_data['topic_id'],
				'tag_id'	=> $tag_id,
			);
		}
		
		$db->sql_multi_insert(TAGMATCH_TABLE, $sql_insert_ary);
		
		// And finally commit the whole
		$db->sql_transaction('commit');

		// Give success messages
		if ($auth->acl_get('f_noapprove', $data['forum_id']) || $auth->acl_get('m_approve', $data['forum_id']))
		{
			$redirect_url = append_sid("{$phpbb_root_path}kb.{$phpEx}", "mode=article&amp;name={$article_data['article_name']}");
			$message = sprintf($user->lang[($mode == 'add' ? 'ARTICLE_ADDED' : 'ARTICLE_UPDATED')], '<a href="' . $redirect_url . '">', '</a>');
			meta_refresh(5, $redirect_url);
		}
		// If the article is waiting for approval do not redirect the user but tell him this
		else
		{
			$redirect_url = append_sid("{$phpbb_root_path}kb.{$phpEx}");
			$message = sprintf($user->lang[($mode == 'add' ? 'ARTICLE_ADDED_MOD' : 'ARTICLE_UPDATED_MOD')], '<a href="' . $redirect_url . '">', '</a>');
		}
		
		trigger_error($message);
	}

	/**
	* Generate preview
	*/
	elseif (isset($_POST['preview']))
	{
		$article_data['bbcode_options'] = (($article_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($article_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($article_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		
		$template->assign_vars(array(
			'PREVIEW_TEXT'	=> generate_text_for_display($message_parser->message, $message_parser->bbcode_uid, $message_parser->bbcode_bitfield, $article_data['bbcode_options']),
			
			'S_PREVIEW'		=> true,
		));
	}

	/**
	* Display the form 
	*/
	add_form_key('add_article');
	
	$template->assign_vars(array(
		// Assign "input" variables
		'ARTICLE_TITLE'			=> $article_data['article_title'],
		'ARTICLE_NAME'			=> $article_data['article_name'],
		'ARTICLE_DESC'			=> $article_data['article_desc'],
		'ARTICLE_CONTENT'		=> $article_data['article_content'],

		//'S_BBCODE_CHECKED'		=> ($$article_data['enable_bbcode']) ? '' : ' checked="checked"',
		'S_SMILIES_CHECKED'		=> ($article_data['enable_smilies']) ? '' : ' checked="checked"',
		'S_MAGIC_URL_CHECKED'	=> ($article_data['enable_magic_url']) ? '' : ' checked="checked"',
	
		'U_ACTION'				=> append_sid("{$phpbb_root_path}kb.{$phpEx}", ($mode == 'add' ? 'mode=add' : "mode=edit&amp;id={$article_id}")),
		'S_MODE'				=> $mode,
		'S_PREVIEW_BOLD'		=> ((isset($error) && sizeof($error)) || !isset($_POST['preview'])) ? true : false,
	
		'ERROR'					=> (isset($error) && sizeof($error)) ? implode('<br />', $error) : false,
	
		'S_BBCODE_ALLOWED'		=> $bbcode_status, // $auth->acl_get('f_bbcode', $forum_id),
		'S_SMILIES_ALLOWED'		=> $smilies_status,
		'S_LINKS_ALLOWED'		=> $url_status,
		'S_BBCODE_IMG'			=> $img_status,
		'S_BBCODE_FLASH'		=> $flash_status,
		'S_BBCODE_QUOTE'		=> true,
		'U_KB'					=> append_sid($phpbb_root_path . 'kb.' . $phpEx),
	));	
	
	/**
	*  Display custom BBCodes
	*/
	display_custom_bbcodes();
	
	// Add own cutom BBCodes (not displayed by default; method based on the one of display_custom_bbcodes())
	
	// Just to make sure specify here 50 - that should be enough
	$num_predefined_bbcodes = 50;

	$sql = 'SELECT bbcode_id, bbcode_tag, bbcode_helpline
		FROM ' . BBCODES_TABLE . '
		WHERE display_on_posting = 0
			AND ' . $db->sql_in_set('bbcode_tag', array('h1', 'h2', 'h3')) . '
		ORDER BY bbcode_tag';
	$result = $db->sql_query($sql);

	$i = 0;
	while ($row = $db->sql_fetchrow($result))
	{
		$template->assign_block_vars('custom_tags', array(
			'BBCODE_NAME'		=> "'[{$row['bbcode_tag']}]', '[/" . str_replace('=', '', $row['bbcode_tag']) . "]'",
			'BBCODE_ID'			=> $num_predefined_bbcodes + ($i * 2),
			'BBCODE_TAG'		=> $row['bbcode_tag'],
			'BBCODE_HELPLINE'	=> $row['bbcode_helpline'],
			'A_BBCODE_HELPLINE'	=> str_replace(array('&amp;', '&quot;', "'", '&lt;', '&gt;'), array('&', '"', "\'", '<', '>'), $row['bbcode_helpline']),
		));

		$i++;
	}
	$db->sql_freeresult($result);
	
	/**
	* Display the page
	*/
	if ($mode == 'add')
	{
		site_header(
			$user->lang['KB'] . ' - ' . $user->lang['ADD_ARTICLE'],
			'kb',
			array(array('kb.' . $phpEx, 'KB'), array("kb.{$phpEx}?mode=add'", 'ADD_ARTICLE'))
		);
	}
	elseif ($mode == 'edit')
	{
		site_header(
			$user->lang['KB'] . ' - ' . $user->lang['EDIT_ARTICLE'],
			'kb',
			array(array('kb.' . $phpEx, 'KB'), array("kb.{$phpEx}?mode=article&amp;name={$article_data['article_name']}", $article_data['article_title']), array("kb.{$phpEx}?mode=edit&amp;id={$article_id}", 'EDIT'))
		);
	}


	$template->set_filenames(array(
		'body' => 'kb_add_article.html')
	);

	site_footer();
}

/**
* Delete article
*/
elseif ($mode == 'delete')
{
	$article_id = request_var('id', 0);

	// Check whether the article exists
	$sql = 'SELECT a.article_id,
		t.topic_id, t.topic_approved, t.topic_reported, t.topic_poster, t.topic_type, t.topic_first_post_id, t.topic_last_post_id,
		p.poster_id, p.post_reported
		FROM ' . KB_ARTICLES_TABLE . ' a, ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p
		WHERE a.topic_id = t.topic_id
			AND t.topic_first_post_id = p.post_id
			AND article_id = ' . $article_id;
	$result = $db->sql_query($sql);
	
	if (($article = $db->sql_fetchrow($result)) == false)
	{
		trigger_error('NO_ARTICLE', E_USER_NOTICE);
	}
	
	if (!$auth->acl_get('m_delete', KB_FORUM_ID) && !($auth->acl_get('f_c_del', KB_FORUM_ID) && $article['topic_poster'] == $user->data['user_id']))
	{
		http_status(403);
		trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
	}
	
	if (confirm_box(true))
	{
		include("{$phpbb_root_path}includes/functions_posting.{$phpEx}");
		
		$db->sql_transaction('begin');

		// Delete article
		$sql = 'DELETE FROM ' . KB_ARTICLES_TABLE . '
			WHERE article_id = ' . $article_id;
		$result = $db->sql_query($sql);
		
		// Delete tags
		$sql = 'DELETE FROM ' . TAGMATCH_TABLE . '
			WHERE topic_id = ' . $article['topic_id'];
		$result = $db->sql_query($sql);
		
		// Delete topic
		delete_post(KB_FORUM_ID, $article['topic_id'], $article['topic_first_post_id'], $article);
		
		$db->sql_transaction('commit');
		
		trigger_error(sprintf($user->lang['ARTICLE_DELETED'], '<a href="' . append_sid("{$phpbb_root_path}kb.{$phpEx}") . '">', '</a>'));
	}
	else
	{
		confirm_box(false, $user->lang['DELETE_ARTICLE_CONFIRM'], build_hidden_fields(array(
			'id'		=> $article_id,
			'mode'		=> $mode))
		);
	}
}

/**
* Index page: display most recent articles and also list some tags
*/
else
{
	$filter_ary = request_var('filter', array(0 => 0));
	$tagcat_name = request_var('cat', '');
	$tag_name = request_var('tag', '');
	
	// First get all tags - cache for 6 hours
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
		'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
		'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tagcat_module = ' . TAG_KB,
	));
	$result = $db->sql_query($sql, 21600);
	
	$tags = array();
	while($row = $db->sql_fetchrow($result))
	{
		$tags[$row['tag_id']] = $row;
	}
	$db->sql_freeresult($result);
	
	/**
	* In tag mode make a filter condition for the tag and show only articles that have this tag
	*/
	if ($mode == 'tag')
	{
		$sql = $db->sql_build_query('SELECT', array(
			'SELECT'	=> 't.tag_id',
			'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
			'WHERE'		=> "t.tagcat_id = tc.tagcat_id
				AND t.tag_name = '" . $db->sql_escape($tag_name) . "'
				AND tc.tagcat_name = '" . $db->sql_escape($tagcat_name) . "'
				AND tagcat_module = " . TAG_KB,
		));
		
		$result = $db->sql_query($sql);
		if ($row = $db->sql_fetchrow($result))
		{
			$filter_ary = array($row['tag_id']);
		}
		else
		{
			http_status(404);
			trigger_error('NO_TAG', E_USER_NOTICE);
		}
	}
	
	/**
	* Query articles based on the filter parameters or in case these don't exist query all articles (no limit!) 
	*/
	// Generate SQL from the filter conditions
	$sql_where = array();
	$sql_table_aliases = array();
	foreach($filter_ary as $key => $tag_id)
	{
		if ($tag_id != 0)
		{
			$sql_table_aliases[] = 'filt' . $key;
			$sql_where[] = "filt{$key}.topic_id = a.topic_id AND filt{$key}.tag_id = {$tag_id}";
		}
	}
	
	// Query articles
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'a.article_id, a.topic_id, a.article_name, a.article_desc,
						t.topic_approved, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_first_poster_name, t.topic_first_poster_colour,
						GROUP_CONCAT(tm.tag_id) AS tags',
		'FROM'		=> array(KB_ARTICLES_TABLE => 'a', TAGMATCH_TABLE => $sql_table_aliases),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'a.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(TAGMATCH_TABLE => 'tm'),
				'ON'	=> 'tm.topic_id = a.topic_id'
			)
		),
		//'WHERE'		=> implode(' AND ', $sql_where + (($auth->acl_get('m_approve', KB_FORUM_ID)) ? array(): array('(t.topic_approved = 1 OR t.topic_poster = ' . $user->data['user_id'] . ')'))),
		'WHERE'		=> implode(' AND ', $sql_where + (($auth->acl_get('m_approve', KB_FORUM_ID)) ? array(): array('t.topic_approved = 1'))),
		'GROUP_BY'	=> 'a.article_id',
		'ORDER_BY'	=> 't.topic_title ASC',
	));
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		// Assign some general variables about the article
		$template->assign_block_vars('articles', array(
			'ARTICLE_TITLE'			=> $row['topic_title'],
			'ARTICLE_NAME'			=> $row['article_name'],
			'ARTICLE_DESC'			=> $row['article_desc'],
			'ARTICLE_ID'			=> $row['article_id'],
			'U_ARTICLE'				=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=article&amp;name=' . $row['article_name']),
			'ARTICLE_AUTHOR'		=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'ARTICLE_AUTHOR_COLOUR'	=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'ARTICLE_AUTHOR_FULL'	=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'ARTICLE_POSTED'		=> $user->format_date($row['topic_time']),	
			'ARTICLE_VIEWS'			=> $row['topic_views'],	
			'ARTICLE_APPROVED'		=> $row['topic_approved'],	
		
			'U_MCP_QUEUE'		=> ($auth->acl_get('m_approve', KB_FORUM_ID)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=queue&amp;mode=approve_details&amp;t={$row['topic_id']}", true) : false,
			'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'TOPIC_UNAPPROVED'),
		));
		
		// Assign tags
		$article_tags = array();
		$tags_list = explode(',', $row['tags']);
		
		// Group assigned tags into categories
		foreach ($tags_list as $tag)
		{
			$article_tags[$tags[$tag]['tagcat_id']][] = &$tags[$tag]; // Maybe the index could be changed from tagcat_id to tagcat_name as the latter is more often used
		}
		
		// Sort the array containing the article tags in order to list them always in the same order
		ksort($article_tags);
		
		// Loop over the tag categories
		foreach ($article_tags as $tagcat)
		{
			// Category variables
			$template->assign_block_vars('articles.tagcats', array(
				'TAGCAT_NAME'	=> $tagcat[0]['tagcat_name'],
				'TAGCAT_TITLE'	=> $tagcat[0]['tagcat_title'],
				'U_TAGCAT'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=listtagcats&amp;cat=' . $tagcat[0]['tagcat_name']),
			));
			
			// Tag details
			foreach($tagcat as $tag)
			{
				$template->assign_block_vars('articles.tagcats.tags', array(
					'TAG_TITLE'	=> $tag['tag_title'],
					'U_TAG'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=tag&amp;cat=' . $tag['tagcat_name'] . '&amp;tag=' . $tag['tag_name']),
				));
			}
		}
	}
	$db->sql_freeresult($result);
	
	/**
	* Display filter
	*/
	$tag_cats = array();
	foreach($tags as &$tag)
	{
		$tag_cats[$tag['tagcat_id']][] = $tag;
	}
	
	// Display filter
	foreach($tag_cats as $tag_cat)
	{
		// Generate tag select options
		$tag_options = '<option value="">' . $user->lang['ANY'] . '</option>';
		foreach($tag_cat as $tag)
		{
			$tag_options .= '<option value="' . $tag['tag_id'] . '"' . (in_array($tag['tag_id'], $filter_ary) ? ' selected="selected"' : '') . '>' . $tag['tag_title'] . '</option>';
		}
		
		$template->assign_block_vars('tagcats', array(
			'TAGCAT_NAME'	=> $tag_cat[0]['tagcat_name'],
			'TAGCAT_TITLE'	=> $tag_cat[0]['tagcat_title'],
			'TAG_OPTIONS'	=> $tag_options,
		));
	}

	// Assign some vars
	$template->assign_vars(array(
		'U_KB'				=> append_sid($phpbb_root_path . 'kb.' . $phpEx),
		'U_FILTER_ACTION'	=> append_sid($phpbb_root_path . 'kb.' . $phpEx),
	));

	// Display sidemenu
	$template->assign_block_vars('sidemenublock', array(
		'BLOCK_TITLE'	=> $user->lang['KB'],
		'BLOCK_URL'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx),
	));
	if ($auth->acl_get('f_c_post', KB_FORUM_ID))
	{
		$template->assign_block_vars('sidemenublock.element', array(
			'ITEM_TITLE'	=> $user->lang['ADD_ARTICLE'],
			'U_ITEM'		=> append_sid($phpbb_root_path . 'kb.' . $phpEx, 'mode=add'),
		));
	}
	
	// Output page
	site_header($user->lang['KB'], 'kb', array(array('kb.' . $phpEx, 'KB')));

	$template->set_filenames(array(
		'body' => 'kb_index.html')
	);

	site_footer();
}
?>