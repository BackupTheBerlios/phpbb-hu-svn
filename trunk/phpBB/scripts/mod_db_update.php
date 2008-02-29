<?php
/** 
*
* @package site
* @version $Id: mods.php 9 2008-02-19 21:04:06Z fberci $
* @copyright (c) 2008 phpbb.hu
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
set_time_limit(0);

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/site/functions_mods.' . $phpEx);
include($phpbb_root_path . 'includes/site/functions_filesystem.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include($phpbb_root_path . 'includes/message_parser.' . $phpEx);
include($phpbb_root_path . 'includes/functions_compress.' . $phpEx);

// Start session management (as we need the $user->lang array)
$user->session_begin();
//$auth->acl($user->data);
$user->setup('site');

// Frequency for updating the mods
$update_freq = 3 * 24 * 60 * 60;


/**
* Prepare tags
*/
$sql = $db->sql_build_query('SELECT', array(
	'SELECT'	=> 't.tag_id, t.tag_name, t.tag_title, tc.tagcat_id, tc.tagcat_name, tc.tagcat_title',
	'FROM'		=> array(TAGS_TABLE	=> 't', TAGCATS_TABLE => 'tc'),
	'WHERE'		=> 't.tagcat_id = tc.tagcat_id AND tagcat_module = ' . TAG_MODS,
));
$result = $db->sql_query($sql);

// Group tags into categories
$tagcats = array();
while($row = $db->sql_fetchrow($result))
{
	if (!isset($tagcats[$row['tagcat_name']]))
	{
		$tagcats[$row['tagcat_name']][$row['tag_name']] = array();
	}
	
	$tagcats[$row['tagcat_name']][$row['tag_name']] = $row;
}
$db->sql_freeresult($result);

// Load the tag translation table
include("{$phpbb_root_path}includes/site/data/mod_tags.{$phpEx}");

/**
* Get forum data
*/
// Query forum details
$sql = 'SELECT forum_name, enable_indexing
	FROM ' . FORUMS_TABLE . '
	WHERE forum_id = ' . MODS_FORUM_ID;
$result = $db->sql_query($sql);
$forum_data = $db->sql_fetchrow($result);
$db->sql_freeresult();

/**
* Get the list of the MODs which need to be updated
*/
$sql = 'SELECT m.*,
		t.topic_poster, t.topic_replies_real, t.topic_first_post_id, t.topic_first_poster_name, t.topic_last_post_id,
		p.post_id, p.poster_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.bbcode_uid, p.post_edit_reason, p.post_edit_locked
	FROM ' . MODS_TABLE . ' m
	LEFT JOIN ' . TOPICS_TABLE . ' t
		ON m.topic_id = t.topic_id
	LEFT JOIN ' . POSTS_TABLE . ' p
		ON t.topic_first_post_id = p.post_id
	WHERE mod_last_checked < ' . (time() - $update_freq) . '
		AND t.topic_approved = 1';
/*$sql = 'SELECT m.*
	FROM ' . MODS_TABLE . ' m
	WHERE mod_last_checked < ' . (time() - $update_freq);*/
$result = $db->sql_query($sql);

while($row = $db->sql_fetchrow($result))
{
	try
	{
		$mod = new mod_pack($row['mod_db_id']);
	}
	catch(ModException $e)
	{
		continue;
	}
	
	try
	{
		$mod->get_pack_details();
		
		// Update MOD if needed
		if (version_compare($row['mod_version'], $mod->data['version'], '<'))
		{
			$mod->get_archive();
			
			// If the filename has been changed, change it for localisation pack too
			if ($mod->filename != $row['mod_filename'] && file_exists($phpbb_root_path . $config['mods_loc_store_path'] . $row['mod_filename'] . '.zip'))
			{
				rename($phpbb_root_path . $config['mods_loc_store_path'] . $row['mod_filename'] . '.zip', $phpbb_root_path . $config['mods_loc_store_path'] . $mod->filename . '.zip');
			}

			// Move localisation pack to the temp directory where the mod class is looking for it
			if (file_exists($phpbb_root_path . $config['mods_loc_store_path'] . $mod->filename . '.zip'))
			{
				copy($phpbb_root_path . $config['mods_loc_store_path'] . $mod->filename . '.zip', $phpbb_root_path . $config['mods_tmp_dir_path'] . 'localisations/' . $mod->filename . '.zip');
			}
			
			$mod->merge_packs();
			
			$row['mod_old_version']	= $row['mod_version'];
			
			$row['mod_en_title']	= $mod->data['title'];
			$row['mod_md5']			= $mod->data['md5'];
			$row['mod_version']		= $mod->data['version'];
			$row['mod_author_id']	= $mod->data['author']['id'];
			$row['mod_author_name']	= $mod->data['author']['name'];
			$row['mod_size']		= $mod->data['size'];
			$row['mod_filename']	= $mod->filename;
			
			// Update our MOD database
			$sql_ary = array(
				'mod_filename'		=> $row['mod_filename'],
				'mod_en_title'		=> $row['mod_en_title'],
				'mod_version'		=> $row['mod_version'],
				'mod_md5'			=> $row['mod_md5'],
				'mod_size'			=> $row['mod_size'],
				'mod_author_id'		=> $row['mod_author_id'],
				'mod_author_name'	=> $row['mod_author_name'],
				'mod_last_checked'	=> time(),
			);
			$sql = 'UPDATE ' . MODS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE mod_id = ' . $row['mod_id'];
			$db->sql_query($sql);
			
			/**
			* Localise tags
			*/
			$mod_tags = array();
			
			foreach ($mod->tags as $tagcat_en_name => $tagcat)
			{
				$tagcat_hu_name = (isset($tag_translation_table[$tagcat_en_name])) ? $tag_translation_table[$tagcat_en_name]['name'] : $tagcat_en_name;
				
				// @todo Create tag category if it doesn't exist?
				
				if (isset($tagcats[$tagcat_hu_name]))
				{
					foreach ($tagcat as $tag)
					{
						list($tag_en_name, $tag_en_title) = $tag;
						
						$tag_hu_name = (isset($tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name])) ? $tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name]['name'] : $tag_en_name;
						$tag_hu_title = (isset($tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name])) ? $tag_translation_table[$tagcat_en_name]['tags'][$tag_en_name]['title'] : $tag_en_title;
						
						// Create tag
						if (!isset($tagcats[$tagcat_hu_name][$tag_hu_name]))
						{
							$tagcat_info = current($tagcats[$tagcat_hu_name]);
							
							$tag_info = array(
								'tagcat_id'	=> $tagcat_info['tagcat_id'],
								'tag_name'	=> $tag_hu_name,
								'tag_title'	=> $tag_hu_title,
							);
							
							$sql = 'INSERT INTO ' . TAGS_TABLE . ' ' . $db->sql_build_array('INSERT', $tag_info);
							$db->sql_query($sql);
							
							$tag_info['tag_id'] = $db->sql_nextid();
							$tag_info = array_merge($tagcat_info, $tag_info);
							
							// Populate cache array
							$tagcats[$tagcat_info['tagcat_name']][$tag_info['tag_name']] = $tag_info;
						}
						
						$mod_tags[] = $tagcats[$tagcat_hu_name][$tag_hu_name]['tag_id'];
					}
				}
			}

			/**
			* Store assigned tags
			*/
			// When editing first delete all assigned tags
			$sql = 'DELETE FROM ' . TAGMATCH_TABLE . ' WHERE topic_id = ' . $row['topic_id'];
			$db->sql_query($sql);
			
			// Generate array to be inserted to the database
			$sql_insert_ary = array();
			foreach ($mod_tags as $tag_id)
			{
				$sql_insert_ary[] = array(
					'topic_id'	=> $row['topic_id'],
					'tag_id'	=> $tag_id,
				);
			}
			
			$db->sql_multi_insert(TAGMATCH_TABLE, $sql_insert_ary);
			
			/**
			* Generate post content
			*/
			$message_parser = new parse_message();		
			$vars = array(
				'MOD_HU_TITLE'		=> $row['mod_hu_title'],
				'MOD_EN_TITLE'		=> $row['mod_title'],
				'MOD_VERSION'		=> $row['mod_version'],
				'MOD_DESC'			=> $row['mod_desc'],
				'MOD_AUTHOR'		=> $row['mod_author_name'],
				'U_MOD_AUTHOR'		=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=' . $row['mod_author_id'],
				'U_MOD_COM_DB'		=> 'http://www.phpbb.com/mods/db/index.php?i=misc&mode=display&contrib_id=' . $row['mod_db_id'],
				'MOD_TAGS'			=> generate_tags_bbcode_list($mod_tags, $tagcats, array("{$phpbb_root_path}mods.{$phpEx}", "mode=listtag&cat=%1\$s&tag=%2\$s")),
				'U_MOD'				=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}mods.{$phpEx}", "mode=mod&id={$row['mod_id']}"),
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
			$data = array(
				'forum_id'			=> MODS_FORUM_ID,
				'topic_title'		=> $row['mod_hu_title'],
				'icon_id'			=> 0,
				'enable_bbcode'		=> 1,
				'enable_smilies'	=> 0,
				'enable_urls'		=> 1,
				'enable_sig'		=> 0,
				'message'			=> $message_parser->message,
				'message_md5'		=> $message_md5,
				'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
				'bbcode_uid'		=> $message_parser->bbcode_uid,
	
				'post_edit_locked'	=> $row['post_edit_locked'],
				'enable_indexing'	=> $forum_data['enable_indexing'],
				'notify'			=> false,
				'notify_set'		=> '',
				'post_time'			=> $row['post_time'],
				'forum_name'		=> $forum_data['forum_name'],
			
				'post_edit_reason'		=> $row['post_edit_reason'],
				'topic_replies_real'	=> $row['topic_replies_real'],
				'poster_id'				=> $row['poster_id'],
				'post_id'				=> &$row['post_id'],
				'topic_id'				=> &$row['topic_id'],
				'topic_poster'			=> $row['topic_poster'],
				'topic_first_post_id'	=> $row['topic_first_post_id'],
				'topic_last_post_id'	=> $row['topic_last_post_id'],
				'post_approved'			=> ($auth->acl_get('f_noapprove', MODS_FORUM_ID) || $auth->acl_get('m_approve', MODS_FORUM_ID)) ? 1 : 0,
			);
			$poll = false;
			
			submit_post('edit', $row['mod_hu_title'], $row['topic_first_poster_name'], POST_NORMAL, $poll, $data);
		
			// Send out moderator notification
			send_mod_notification($row);
		}
	}
	// Disapprove the MOD and send notification to the submitter that there is an error with his MOD he should fix ASAP
	catch(ModException $e)
	{
		// Disapprove topic
		$sql = 'SELECT p.post_time, p.post_text, p.bbcode_bitfield  FROM ' . POSTS_TABLE . ' p WHERE p.post_id = ' . $row['topic_first_post_id'];
		$result = $db->sql_query($sql);
		$post = $db->sql_fetchrow($result);
		
		$data = array(
			'forum_id'			=> MODS_FORUM_ID,
			'topic_title'		=> $row['mod_hu_title'],
			'icon_id'			=> 0,
			'enable_bbcode'		=> 1,
			'enable_smilies'	=> 0,
			'enable_urls'		=> 1,
			'enable_sig'		=> 0,
			'message'			=> $post['post_text'],
			'message_md5'		=> md5($post['post_text']),
			'bbcode_bitfield'	=> $post['bbcode_bitfield'],
			'bbcode_uid'		=> $row['bbcode_uid'],

			'post_edit_locked'	=> $row['post_edit_locked'],
			'enable_indexing'	=> $forum_data['enable_indexing'],
			'notify'			=> false,
			'notify_set'		=> '',
			'post_time'			=> $post['post_time'],
			'forum_name'		=> $forum_data['forum_name'],
		
			'post_edit_reason'		=> $row['post_edit_reason'],
			'topic_replies_real'	=> $row['topic_replies_real'],
			'poster_id'				=> $row['poster_id'],
			'post_id'				=> &$row['post_id'],
			'topic_id'				=> &$row['topic_id'],
			'topic_poster'			=> $row['topic_poster'],
			'topic_first_post_id'	=> $row['topic_first_post_id'],
			'topic_last_post_id'	=> $row['topic_last_post_id'],
			'post_approved'			=> 0,
		);
		$poll = false;
		
		submit_post('edit', $row['mod_hu_title'], $row['topic_first_poster_name'], POST_NORMAL, $poll, $data);
		
		send_notification(array($row['topic_poster']), 'mod_update_error', array(
			'MOD_HU_TITLE'		=> $row['mod_hu_title'],
			'MOD_EN_TITLE'		=> $row['mod_en_title'],
			'MOD_OLD_VERSION'	=> isset($row['mod_old_version']) ? $row['mod_old_version'] : $row['mod_version'],
			'MOD_NEW_VERSION'	=> isset($row['mod_old_version']) ? $row['mod_version'] : $user->lang['VERSION_UNKNOWN'],
			'MOD_DESC'			=> $row['mod_desc'],
			'MOD_AUTHOR'		=> $row['mod_author_name'],
			'ERRORS'			=> implode("\n", $e->getErrors()),
			'U_MOD_AUTHOR'		=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;u=' . $row['mod_author_id'],
			'U_MOD_COM_DB'		=> 'http://www.phpbb.com/mods/db/index.php?i=misc&mode=display&contrib_id=' . $row['mod_db_id'],
			'U_LOC_PACK'		=> generate_board_url() . '/' . $config['mods_loc_store_path'] . $row['mod_filename'] . '.zip',
			'U_MOD_PACK'		=> generate_board_url() . '/' . $config['downloads_path'] . '/mods/' . $row['mod_filename'] . '.zip',
			'U_MOD'				=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}mods.{$phpEx}", "mode=mod&id={$row['mod_id']}"),
		));
	}
	
	// Do cleanup
	$mod->cleanup();
	
	// MOD updated
	$sql = 'UPDATE ' . MODS_TABLE . ' SET mod_last_checked = ' . time() . ' WHERE mod_id = ' . $row['mod_id'];
	$db->sql_query($sql);
}

// Successfully terminated
die(1);

?>