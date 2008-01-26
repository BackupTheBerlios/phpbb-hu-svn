<?php
/**
*
* @package acp
* @version $Id: acp_forums.php,v 1.68 2007/07/22 20:10:08 acydburn Exp $
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package acp
*/
class acp_bugs
{
	var $u_action;
	var $parent_id = 0;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache;
		global $config, $phpbb_admin_path, $phpbb_root_path, $phpEx;

		$user->add_lang('acp/forums');
		$user->add_lang('acp/site');
		$this->tpl_name = 'acp_bugs';
		$this->page_title = 'ACP_BUG_TRACKER';

		/**
		* Projects (also creating forums)
		*/
		if ($mode == 'projects')
		{
			$this->parent_id = BUGS_FORUM_ID;
	
			$action		= request_var('action', '');
			$update		= (isset($_POST['update'])) ? true : false;
			$forum_id	= request_var('f', 0);
	
			// This is just a shame copy of acp_forums, use acp_forus whenever possible (for functions)
			include("{$phpbb_root_path}includes/acp/acp_forums.$phpEx");
			$this->parent = new acp_forums();
	
			$forum_data = $errors = array();
	
			// Check additional permissions
			/*switch ($action)
			{
				case 'delete':
	
					if (!$auth->acl_get('a_forumdel'))
					{
						trigger_error($user->lang['NO_PERMISSION_FORUM_DELETE'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
					}
	
				break;
	
				case 'add':
	
					if (!$auth->acl_get('a_forumadd'))
					{
						trigger_error($user->lang['NO_PERMISSION_FORUM_ADD'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
					}
				
				break;
			}*/
	
			// Major routines
			if ($update)
			{
				switch ($action)
				{
					case 'delete':
						$action_subforums	= request_var('action_subforums', '');
						$subforums_to_id	= request_var('subforums_to_id', 0);
						$action_posts		= request_var('action_posts', '');
						$posts_to_id		= request_var('posts_to_id', 0);
	
						$errors = $this->delete_forum($forum_id, $action_posts, $action_subforums, $posts_to_id, $subforums_to_id);
	
						if (sizeof($errors))
						{
							break;
						}
	
						$auth->acl_clear_prefetch();
						$cache->destroy('sql', FORUMS_TABLE);
	
						trigger_error($user->lang['PROJECT_DELETED'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
		
					break;
	
					case 'edit':
						$forum_data = array(
							'forum_id'		=>	$forum_id
						);
	
					// No break here
	
					case 'add':
	
						$forum_data += array(
							'parent_id'				=> BUGS_FORUM_ID,
							'forum_type'			=> FORUM_POST,
							'type_action'			=> request_var('type_action', ''),
							'forum_status'			=> request_var('forum_status', ITEM_UNLOCKED),
							'forum_parents'			=> '',
							'forum_name'			=> utf8_normalize_nfc(request_var('forum_name', '', true)),
							'project_idname'		=> request_var('project_idname', ''),
							'forum_link'			=> '',
							'forum_link_track'		=> false,
							'forum_desc'			=> utf8_normalize_nfc(request_var('forum_desc', '', true)),
							'forum_desc_uid'		=> '',
							'forum_desc_options'	=> 7,
							'forum_desc_bitfield'	=> '',
							'forum_rules'			=> utf8_normalize_nfc(request_var('forum_rules', '', true)),
							'forum_rules_uid'		=> '',
							'forum_rules_options'	=> 7,
							'forum_rules_bitfield'	=> '',
							'forum_rules_link'		=> request_var('forum_rules_link', ''),
							'forum_image'			=> request_var('forum_image', ''),
							'forum_style'			=> request_var('forum_style', 0),
							'display_on_index'		=> request_var('display_on_index', false),
							'forum_topics_per_page'	=> request_var('topics_per_page', 0), 
							'enable_indexing'		=> request_var('enable_indexing', true), 
							'enable_icons'			=> request_var('enable_icons', false),
							'enable_prune'			=> request_var('enable_prune', false),
							'enable_post_review'	=> request_var('enable_post_review', true),
							'prune_days'			=> request_var('prune_days', 7),
							'prune_viewed'			=> request_var('prune_viewed', 7),
							'prune_freq'			=> request_var('prune_freq', 1),
							'prune_old_polls'		=> request_var('prune_old_polls', false),
							'prune_announce'		=> request_var('prune_announce', false),
							'prune_sticky'			=> request_var('prune_sticky', false),
							'forum_password'		=> request_var('forum_password', '', true),
							'forum_password_confirm'=> request_var('forum_password_confirm', '', true),
						);
	
	
						$forum_data['show_active'] = ($forum_data['forum_type'] == FORUM_POST) ? request_var('display_recent', false) : request_var('display_active', false);
	
						// Get data for forum rules if specified...
						if ($forum_data['forum_rules'])
						{
							generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], request_var('rules_parse_bbcode', false), request_var('rules_parse_urls', false), request_var('rules_parse_smilies', false));
						}
	
						// Get data for forum description if specified
						if ($forum_data['forum_desc'])
						{
							generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], request_var('desc_parse_bbcode', false), request_var('desc_parse_urls', false), request_var('desc_parse_smilies', false));
						}
	
						$errors = $this->update_forum_data($forum_data);
	
						if (!sizeof($errors))
						{
							$forum_perm_from = request_var('forum_perm_from', 0);
	
							// Copy permissions?
							if ($forum_perm_from && !empty($forum_perm_from) && $forum_perm_from != $forum_data['forum_id'])
							{
								// if we edit a forum delete current permissions first
								if ($action == 'edit')
								{
									$sql = 'DELETE FROM ' . ACL_USERS_TABLE . '
										WHERE forum_id = ' . (int) $forum_data['forum_id'];
									$db->sql_query($sql);
		
									$sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . '
										WHERE forum_id = ' . (int) $forum_data['forum_id'];
									$db->sql_query($sql);
								}
	
								// From the mysql documentation:
								// Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
								// Due to this we stay on the safe side if we do the insertion "the manual way"
	
								// Copy permisisons from/to the acl users table (only forum_id gets changed)
								$sql = 'SELECT user_id, auth_option_id, auth_role_id, auth_setting
									FROM ' . ACL_USERS_TABLE . '
									WHERE forum_id = ' . $forum_perm_from;
								$result = $db->sql_query($sql);
	
								$users_sql_ary = array();
								while ($row = $db->sql_fetchrow($result))
								{
									$users_sql_ary[] = array(
										'user_id'			=> (int) $row['user_id'],
										'forum_id'			=> (int) $forum_data['forum_id'],
										'auth_option_id'	=> (int) $row['auth_option_id'],
										'auth_role_id'		=> (int) $row['auth_role_id'],
										'auth_setting'		=> (int) $row['auth_setting']
									);
								}
								$db->sql_freeresult($result);
	
								// Copy permisisons from/to the acl groups table (only forum_id gets changed)
								$sql = 'SELECT group_id, auth_option_id, auth_role_id, auth_setting
									FROM ' . ACL_GROUPS_TABLE . '
									WHERE forum_id = ' . $forum_perm_from;
								$result = $db->sql_query($sql);
	
								$groups_sql_ary = array();
								while ($row = $db->sql_fetchrow($result))
								{
									$groups_sql_ary[] = array(
										'group_id'			=> (int) $row['group_id'],
										'forum_id'			=> (int) $forum_data['forum_id'],
										'auth_option_id'	=> (int) $row['auth_option_id'],
										'auth_role_id'		=> (int) $row['auth_role_id'],
										'auth_setting'		=> (int) $row['auth_setting']
									);
								}
								$db->sql_freeresult($result);
	
								// Now insert the data
								$db->sql_multi_insert(ACL_USERS_TABLE, $users_sql_ary);
								$db->sql_multi_insert(ACL_GROUPS_TABLE, $groups_sql_ary);
							}
	
							$auth->acl_clear_prefetch();
							$cache->destroy('sql', FORUMS_TABLE);
		
							$acl_url = '&amp;mode=setting_forum_local&amp;forum_id[]=' . $forum_data['forum_id'];
	
							$message = ($action == 'add') ? $user->lang['PROJECT_CREATED'] : $user->lang['PROJECT_UPDATED'];
	
							// Redirect to permissions
							if ($auth->acl_get('a_fauth'))
							{
								$message .= '<br /><br />' . sprintf($user->lang['REDIRECT_ACL'], '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=permissions' . $acl_url) . '">', '</a>');
							}
	
							// redirect directly to permission settings screen if authed
							if ($action == 'add' && !$forum_perm_from && $auth->acl_get('a_fauth'))
							{
								meta_refresh(4, append_sid("{$phpbb_admin_path}index.$phpEx", 'i=permissions' . $acl_url));
							}
	
							trigger_error($message . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
						}
	
					break;
				}
			}
	
			switch ($action)
			{
				case 'move_up':
				case 'move_down':
	
					if (!$forum_id)
					{
						trigger_error($user->lang['NO_FORUM'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
					}
	
					$sql = 'SELECT *
						FROM ' . FORUMS_TABLE . "
						WHERE forum_id = $forum_id";
					$result = $db->sql_query($sql);
					$row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
	
					if (!$row)
					{
						trigger_error($user->lang['NO_FORUM'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
					}
	
					$move_forum_name = $this->parent->move_forum_by($row, $action, 1);
	
					if ($move_forum_name !== false)
					{
						add_log('admin', 'LOG_FORUM_' . strtoupper($action), $row['forum_name'], $move_forum_name);
						$cache->destroy('sql', FORUMS_TABLE);
					}
	
				break;
	
				case 'add':
				case 'edit':
	
					if ($update)
					{
						$forum_data['forum_flags'] = 0;
						$forum_data['forum_flags'] += (request_var('forum_link_track', false)) ? FORUM_FLAG_LINK_TRACK : 0;
						$forum_data['forum_flags'] += (request_var('prune_old_polls', false)) ? FORUM_FLAG_PRUNE_POLL : 0;
						$forum_data['forum_flags'] += (request_var('prune_announce', false)) ? FORUM_FLAG_PRUNE_ANNOUNCE : 0;
						$forum_data['forum_flags'] += (request_var('prune_sticky', false)) ? FORUM_FLAG_PRUNE_STICKY : 0;
						$forum_data['forum_flags'] += ($forum_data['show_active']) ? FORUM_FLAG_ACTIVE_TOPICS : 0;
						$forum_data['forum_flags'] += (request_var('enable_post_review', true)) ? FORUM_FLAG_POST_REVIEW : 0;
					}
	
					// Show form to create/modify a forum
					if ($action == 'edit')
					{
						$this->page_title = 'EDIT_PROJECT';
						$row = $this->parent->get_forum_info($forum_id);
						$old_forum_type = $row['forum_type'];
	
						// Get the project's id name
						$sql = 'SELECT project_name
							FROM ' . BUGS_PROJECTS_TABLE . "
							WHERE forum_id = $forum_id";
						$result = $db->sql_query($sql);
						$row['project_idname'] = $db->sql_fetchfield('project_name', false, $result);
						$db->sql_freeresult($result);
	
						if (!$update)
						{
							$forum_data = $row;
						}
						else
						{
							$forum_data['left_id'] = $row['left_id'];
							$forum_data['right_id'] = $row['right_id'];
						}
	
						// Make sure no direct child forums are able to be selected as parents.
						$exclude_forums = array();
						foreach (get_forum_branch($forum_id, 'children') as $row)
						{
							$exclude_forums[] = $row['forum_id'];
						}
	
						$parents_list = make_forum_select($forum_data['parent_id'], $exclude_forums, false, false, false);
	
						$forum_data['forum_password_confirm'] = $forum_data['forum_password'];
					}
					else
					{
						$this->page_title = 'CREATE_PROJECT';
	
						$forum_id = BUGS_FORUM_ID;
	
						// Fill forum data with default values
						if (!$update)
						{
							$forum_data = array(
								'parent_id'				=> BUGS_FORUM_ID,
								'forum_type'			=> FORUM_POST,
								'forum_status'			=> ITEM_UNLOCKED,
								'forum_name'			=> utf8_normalize_nfc(request_var('forum_name', '', true)),
								'project_idname'		=> '',
								'forum_link'			=> '',
								'forum_link_track'		=> false,
								'forum_desc'			=> '',
								'forum_rules'			=> '',
								'forum_rules_link'		=> '',
								'forum_image'			=> '',
								'forum_style'			=> 0,
								'display_on_index'		=> false,
								'forum_topics_per_page'	=> 0, 
								'enable_indexing'		=> true, 
								'enable_icons'			=> false,
								'enable_prune'			=> false,
								'prune_days'			=> 7,
								'prune_viewed'			=> 7,
								'prune_freq'			=> 1,
								'forum_flags'			=> FORUM_FLAG_POST_REVIEW,
								'forum_password'		=> '',
								'forum_password_confirm'=> '',
							);
						}
					}
	
					$forum_rules_data = array(
						'text'			=> $forum_data['forum_rules'],
						'allow_bbcode'	=> true,
						'allow_smilies'	=> true,
						'allow_urls'	=> true
					);
	
					$forum_desc_data = array(
						'text'			=> $forum_data['forum_desc'],
						'allow_bbcode'	=> true,
						'allow_smilies'	=> true,
						'allow_urls'	=> true
					);
	
					$forum_rules_preview = '';
	
					// Parse rules if specified
					if ($forum_data['forum_rules'])
					{
						if (!isset($forum_data['forum_rules_uid']))
						{
							// Before we are able to display the preview and plane text, we need to parse our request_var()'d value...
							$forum_data['forum_rules_uid'] = '';
							$forum_data['forum_rules_bitfield'] = '';
							$forum_data['forum_rules_options'] = 0;
	
							generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], request_var('rules_allow_bbcode', false), request_var('rules_allow_urls', false), request_var('rules_allow_smilies', false));
						}
	
						// Generate preview content
						$forum_rules_preview = generate_text_for_display($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options']);
	
						// decode...
						$forum_rules_data = generate_text_for_edit($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_options']);
					}
	
					// Parse desciption if specified
					if ($forum_data['forum_desc'])
					{
						if (!isset($forum_data['forum_desc_uid']))
						{
							// Before we are able to display the preview and plane text, we need to parse our request_var()'d value...
							$forum_data['forum_desc_uid'] = '';
							$forum_data['forum_desc_bitfield'] = '';
							$forum_data['forum_desc_options'] = 0;
	
							generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], request_var('desc_allow_bbcode', false), request_var('desc_allow_urls', false), request_var('desc_allow_smilies', false));
						}
	
						// decode...
						$forum_desc_data = generate_text_for_edit($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_options']);
					}
	
					$styles_list = style_select($forum_data['forum_style'], true);
	
					$statuslist = '<option value="' . ITEM_UNLOCKED . '"' . (($forum_data['forum_status'] == ITEM_UNLOCKED) ? ' selected="selected"' : '') . '>' . $user->lang['UNLOCKED'] . '</option><option value="' . ITEM_LOCKED . '"' . (($forum_data['forum_status'] == ITEM_LOCKED) ? ' selected="selected"' : '') . '>' . $user->lang['LOCKED'] . '</option>';
	
					$sql = 'SELECT forum_id
						FROM ' . FORUMS_TABLE . '
						WHERE forum_type = ' . FORUM_POST . "
							AND forum_id <> $forum_id";
					$result = $db->sql_query($sql);
	
					if ($db->sql_fetchrow($result))
					{
						$template->assign_vars(array(
							'S_MOVE_FORUM_OPTIONS'		=> make_forum_select($forum_data['parent_id'], $forum_id, false, true, false))
						);
					}
					$db->sql_freeresult($result);
	
					// Subforum move options
					if ($action == 'edit' && $forum_data['forum_type'] == FORUM_CAT)
					{
						$subforums_id = array();
						$subforums = get_forum_branch($forum_id, 'children');
	
						foreach ($subforums as $row)
						{
							$subforums_id[] = $row['forum_id'];
						}
	
						$forums_list = make_forum_select($forum_data['parent_id'], $subforums_id);
	
						$sql = 'SELECT forum_id
							FROM ' . FORUMS_TABLE . '
							WHERE forum_type = ' . FORUM_POST . "
								AND forum_id <> $forum_id";
						$result = $db->sql_query($sql);
	
						if ($db->sql_fetchrow($result))
						{
							$template->assign_vars(array(
								'S_MOVE_FORUM_OPTIONS'		=> make_forum_select($forum_data['parent_id'], $subforums_id)) // , false, true, false???
							);
						}
						$db->sql_freeresult($result);
	
						$template->assign_vars(array(
							'S_HAS_SUBFORUMS'		=> ($forum_data['right_id'] - $forum_data['left_id'] > 1) ? true : false,
							'S_FORUMS_LIST'			=> $forums_list)
						);
					}
	
					$s_show_display_on_index = false;
	
					if ($forum_data['parent_id'] > 0)
					{
						// if this forum is a subforum put the "display on index" checkbox
						if ($parent_info = $this->parent->get_forum_info($forum_data['parent_id']))
						{
							if ($parent_info['parent_id'] > 0 || $parent_info['forum_type'] == FORUM_CAT)
							{
								$s_show_display_on_index = true;
							}
						}
					}
	
					$template->assign_vars(array(
						'S_EDIT_FORUM'		=> true,
						'S_ERROR'			=> (sizeof($errors)) ? true : false,
						'S_PARENT_ID'		=> BUGS_FORUM_ID,
						'S_FORUM_PARENT_ID'	=> $forum_data['parent_id'],
						'S_ADD_ACTION'		=> ($action == 'add') ? true : false,
	
						'U_BACK'		=> $this->u_action . '&amp;parent_id=' . $this->parent_id,
						'U_EDIT_ACTION'	=> $this->u_action . "&amp;parent_id={$this->parent_id}&amp;action=$action&amp;f=$forum_id",
	
						'L_COPY_PERMISSIONS_EXPLAIN'	=> $user->lang['COPY_PERMISSIONS_' . strtoupper($action) . '_EXPLAIN'],
						'L_TITLE'						=> $user->lang[$this->page_title],
						'ERROR_MSG'						=> (sizeof($errors)) ? implode('<br />', $errors) : '',
	
						'FORUM_NAME'				=> $forum_data['forum_name'],
						'PROJECT_IDNAME'			=> $forum_data['project_idname'],
						'FORUM_DATA_LINK'			=> $forum_data['forum_link'],
						'FORUM_IMAGE'				=> $forum_data['forum_image'],
						'FORUM_IMAGE_SRC'			=> ($forum_data['forum_image']) ? $phpbb_root_path . $forum_data['forum_image'] : '',
						'FORUM_POST'				=> FORUM_POST,
						'FORUM_LINK'				=> FORUM_LINK,
						'FORUM_CAT'					=> FORUM_CAT,
						'PRUNE_FREQ'				=> $forum_data['prune_freq'],
						'PRUNE_DAYS'				=> $forum_data['prune_days'],
						'PRUNE_VIEWED'				=> $forum_data['prune_viewed'],
						'TOPICS_PER_PAGE'			=> $forum_data['forum_topics_per_page'],
						'FORUM_PASSWORD'			=> $forum_data['forum_password'],
						'FORUM_PASSWORD_CONFIRM'	=> $forum_data['forum_password_confirm'],
						'FORUM_RULES_LINK'			=> $forum_data['forum_rules_link'],
						'FORUM_RULES'				=> $forum_data['forum_rules'],
						'FORUM_RULES_PREVIEW'		=> $forum_rules_preview,
						'FORUM_RULES_PLAIN'			=> $forum_rules_data['text'],
						'S_BBCODE_CHECKED'			=> ($forum_rules_data['allow_bbcode']) ? true : false,
						'S_SMILIES_CHECKED'			=> ($forum_rules_data['allow_smilies']) ? true : false,
						'S_URLS_CHECKED'			=> ($forum_rules_data['allow_urls']) ? true : false,
	
						'FORUM_DESC'				=> $forum_desc_data['text'],
						'S_DESC_BBCODE_CHECKED'		=> ($forum_desc_data['allow_bbcode']) ? true : false,
						'S_DESC_SMILIES_CHECKED'	=> ($forum_desc_data['allow_smilies']) ? true : false,
						'S_DESC_URLS_CHECKED'		=> ($forum_desc_data['allow_urls']) ? true : false,
	
						'S_STATUS_OPTIONS'			=> $statuslist,
						'S_STYLES_OPTIONS'			=> $styles_list,
						'S_FORUM_OPTIONS'			=> make_forum_select(($action == 'add') ? $forum_data['parent_id'] : false, ($action == 'edit') ? $forum_data['forum_id'] : false, false, false, false),
						'S_SHOW_DISPLAY_ON_INDEX'	=> $s_show_display_on_index,
						'S_FORUM_POST'				=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
						'S_FORUM_ORIG_POST'			=> (isset($old_forum_type) && $old_forum_type == FORUM_POST) ? true : false,
						'S_FORUM_ORIG_CAT'			=> (isset($old_forum_type) && $old_forum_type == FORUM_CAT) ? true : false,
						'S_FORUM_ORIG_LINK'			=> (isset($old_forum_type) && $old_forum_type == FORUM_LINK) ? true : false,
						'S_FORUM_LINK'				=> ($forum_data['forum_type'] == FORUM_LINK) ? true : false,
						'S_FORUM_CAT'				=> ($forum_data['forum_type'] == FORUM_CAT) ? true : false,
						'S_ENABLE_INDEXING'			=> ($forum_data['enable_indexing']) ? true : false,
						'S_TOPIC_ICONS'				=> ($forum_data['enable_icons']) ? true : false,
						'S_DISPLAY_ON_INDEX'		=> ($forum_data['display_on_index']) ? true : false,
						'S_PRUNE_ENABLE'			=> ($forum_data['enable_prune']) ? true : false,
						'S_FORUM_LINK_TRACK'		=> ($forum_data['forum_flags'] & FORUM_FLAG_LINK_TRACK) ? true : false,
						'S_PRUNE_OLD_POLLS'			=> ($forum_data['forum_flags'] & FORUM_FLAG_PRUNE_POLL) ? true : false,
						'S_PRUNE_ANNOUNCE'			=> ($forum_data['forum_flags'] & FORUM_FLAG_PRUNE_ANNOUNCE) ? true : false,
						'S_PRUNE_STICKY'			=> ($forum_data['forum_flags'] & FORUM_FLAG_PRUNE_STICKY) ? true : false,
						'S_DISPLAY_ACTIVE_TOPICS'	=> ($forum_data['forum_flags'] & FORUM_FLAG_ACTIVE_TOPICS) ? true : false,
						'S_ENABLE_POST_REVIEW'		=> ($forum_data['forum_flags'] & FORUM_FLAG_POST_REVIEW) ? true : false,
						)
					);
	
					return;
	
				break;
	
				case 'delete':
	
					if (!$forum_id)
					{
						trigger_error($user->lang['NO_FORUM'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
					}
	
					$forum_data = $this->parent->get_forum_info($forum_id);
	
					$subforums_id = array();
					$subforums = get_forum_branch($forum_id, 'children');
	
					foreach ($subforums as $row)
					{
						$subforums_id[] = $row['forum_id'];
					}
	
					$forums_list = make_forum_select($forum_data['parent_id'], $subforums_id);
	
					$sql = 'SELECT forum_id
						FROM ' . FORUMS_TABLE . '
						WHERE forum_type = ' . FORUM_POST . "
							AND forum_id <> $forum_id";
					$result = $db->sql_query($sql);
	
					if ($db->sql_fetchrow($result))
					{
						$template->assign_vars(array(
							'S_MOVE_FORUM_OPTIONS'		=> make_forum_select($forum_data['parent_id'], $subforums_id, false, true)) // , false, true, false???
						);
					}
					$db->sql_freeresult($result);
	
					$parent_id = ($this->parent_id == $forum_id) ? 0 : $this->parent_id;
	
					$template->assign_vars(array(
						'S_DELETE_FORUM'		=> true,
						'U_ACTION'				=> $this->u_action . "&amp;parent_id={$parent_id}&amp;action=delete&amp;f=$forum_id",
						'U_BACK'				=> $this->u_action . '&amp;parent_id=' . $this->parent_id,
	
						'FORUM_NAME'			=> $forum_data['forum_name'],
						'S_FORUM_POST'			=> ($forum_data['forum_type'] == FORUM_POST) ? true : false,
						'S_FORUM_LINK'			=> ($forum_data['forum_type'] == FORUM_LINK) ? true : false,
						'S_HAS_SUBFORUMS'		=> ($forum_data['right_id'] - $forum_data['left_id'] > 1) ? true : false,
						'S_FORUMS_LIST'			=> $forums_list,
						'S_ERROR'				=> (sizeof($errors)) ? true : false,
						'ERROR_MSG'				=> (sizeof($errors)) ? implode('<br />', $errors) : '')
					);
	
					return;
				break;
			}
	
			$navigation = $user->lang['BUG_TRACKER'];
	
			$this->page_title = 'BUG_TRACKER_PROJECTS';

			$sql = 'SELECT bp.*, f.*
				FROM ' . BUGS_PROJECTS_TABLE  . " bp,
					" . FORUMS_TABLE . " f 
				WHERE bp.forum_id = f.forum_id
				ORDER BY f.left_id";
			$result = $db->sql_query($sql);
	
			if ($row = $db->sql_fetchrow($result))
			{
				do
				{
					$url = $this->u_action . "&amp;parent_id=$this->parent_id&amp;f={$row['forum_id']}";
	
					$template->assign_block_vars('projects', array(
						'FOLDER_IMAGE'		=> '<img src="images/icon_folder.gif" alt="' . $user->lang['FOLDER'] . '" />',
						'PROJECT_IMAGE'		=> ($row['forum_image']) ? '<img src="' . $phpbb_root_path . $row['forum_image'] . '" alt="" />' : '',
						'PROJECT_IMAGE_SRC'	=> ($row['forum_image']) ? $phpbb_root_path . $row['forum_image'] : '',
						'PROJECT_NAME'		=> $row['forum_name'],
						'PROJECT_DESCRIPTION'	=> generate_text_for_display($row['forum_desc'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
						'PROJECT_TOPICS'		=> $row['forum_topics'],
						'PROJECT_POSTS'		=> $row['forum_posts'],
	
						'U_PROJECT'			=> $this->u_action . '&amp;parent_id=' . $row['forum_id'],
						'U_MOVE_UP'			=> $url . '&amp;action=move_up',
						'U_MOVE_DOWN'		=> $url . '&amp;action=move_down',
						'U_EDIT'			=> $url . '&amp;action=edit',
						'U_DELETE'			=> $url . '&amp;action=delete',)
					);
				}
				while ($row = $db->sql_fetchrow($result));
			}
			$db->sql_freeresult($result);
	
			$template->assign_vars(array(
				'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',
				'NAVIGATION'	=> $navigation,
				'U_SEL_ACTION'	=> $this->u_action,
				'U_ACTION'		=> $this->u_action . '&amp;parent_id=' . $this->parent_id,)
			);
		}

		/**
		* Statuses
		*/
		elseif ($mode == 'statuses')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$status_id = request_var('st', 0);

			if ($action == 'add' || $action == 'edit')
			{
				$errors = array();

				$status_data = array(
					'status_title' => utf8_normalize_nfc(request_var('status_title', '', true)),
					'status_closed' => intval((bool) request_var('status_closed', 0)),
				);

				if ($action == 'add')
				{
					$this->page_title = 'ADD_STATUS';
					
					if ($update)
					{
						$sql = 'INSERT INTO ' . BUGS_STATUSES_TABLE . ' ' . $db->sql_build_array('INSERT', $status_data);
						$db->sql_query($sql);
						trigger_error($user->lang['STATUS_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit')
				{
					$this->page_title = 'EDIT_STATUS';

					if ($update)
					{
						$sql = 'UPDATE ' . BUGS_STATUSES_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $status_data) . '
							WHERE status_id = ' . $status_id;
						$result = $db->sql_query($sql);
						trigger_error($user->lang['STATUS_EDITED'] . adm_back_link($this->u_action));
					}
					else
					{
						// Query the status details
						$sql = 'SELECT * FROM ' . BUGS_STATUSES_TABLE  . " WHERE status_id = '{$status_id}'";
						$result = $db->sql_query($sql);
						if ($row = $db->sql_fetchrow($result))
						{
							$status_data['status_closed'] = $row['status_closed'];
							$status_data['status_title'] = $row['status_title'];
						}
						else
						{
							trigger_error($user->lang['NO_STATUS'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
				}

				$template->assign_vars(array(
					'S_EDIT_STATUS'	=> true,
					'S_ERROR'		=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'	=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;st=$status_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'STATUS_TITLE'		=> $status_data['status_title'],
					'S_STATUS_CLOSED'	=> ($status_data['status_closed']) ? true : false,
					)
				);
				
				
				return;
			}
			switch ($action)
			{
				case 'add':
					$template->assign_vars(array(
						'S_STATUS_ADD'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;
	
				break;

				case 'edit':

					$template->assign_vars(array(
						'S_STATUS_EDIT'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;

				break;
			}
			if ($action == 'delete')
			{
				// Make sure the status exists
				$sql = 'SELECT status_id FROM ' . BUGS_STATUSES_TABLE . ' WHERE status_id = ' . $status_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_STATUS', E_USER_WARNING);
				}

				if (confirm_box(true))
				{
					// Bugs having this status assigned will have a status id of 1
					$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . " 
						SET report_status = '1'
						WHERE report_status = '$status_id'";
					$result = $db->sql_query($sql);

					// Now, remove the status
					$sql = 'DELETE FROM ' . BUGS_STATUSES_TABLE . " 
						WHERE status_id = '$status_id'";
					$result = $db->sql_query($sql);

					trigger_error($user->lang['STATUS_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'st'		=> $status_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'BUG_TRACKER_STATUSES';

			// Query the statuses
			$sql = 'SELECT * FROM ' . BUGS_STATUSES_TABLE  . ' ORDER BY status_closed ASC, status_title ASC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$url = $this->u_action . '&amp;st=' . $row['status_id'];

				$template->assign_block_vars('statuses', array(
					'STATUS_TITLE'		=> $row['status_title'],
					'STATUS_CLOSED'		=> $row['status_closed'] ? $user->lang['STATUS_CLOSED'] : $user->lang['STATUS_OPEN'],
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_STATUSES'	=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}

		/**
		* Versions
		*/
		elseif ($mode == 'versions')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$version_id = request_var('v', 0);

			if ($action == 'add' || $action == 'edit')
			{
				$errors = array();

				$version_data = array(
					'project_id'	=> request_var('project_id', 0),
					'version_title'	=> utf8_normalize_nfc(request_var('version_title', '', true)),
					'accept_new'	=> intval((bool) request_var('accept_new', 1)),
				);

				if ($action == 'add')
				{
					$this->page_title = 'ADD_VERSION';
					
					if ($update)
					{
						// Validate project
						$sql = 'SELECT project_id FROM ' . BUGS_PROJECTS_TABLE . ' WHERE project_id = ' . $version_data['project_id'];
						$result = $db->sql_query($sql);
						if (!$db->sql_fetchrow($result))
						{
							trigger_error($user->lang['NO_PROJECT'] . adm_back_link($this->u_action));
						}

						$sql = 'INSERT INTO ' . BUGS_VERSIONS_TABLE . ' ' . $db->sql_build_array('INSERT', $version_data);
						$db->sql_query($sql);
						trigger_error($user->lang['VERSION_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit')
				{
					$this->page_title = 'EDIT_VERSION';

					if ($update)
					{
						unset($version_data['project_id']); // Do not update that - versions cannot be transferred to other projects
						$sql = 'UPDATE ' . BUGS_VERSIONS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $version_data) . '
							WHERE version_id = ' . $version_id;
						$result = $db->sql_query($sql);
						trigger_error($user->lang['VERSION_EDITED'] . adm_back_link($this->u_action));
					}
					else
					{
						// Query the version details
						$sql = 'SELECT v.*, p.project_title
							FROM ' . BUGS_VERSIONS_TABLE  . ' v
								LEFT JOIN ' . BUGS_PROJECTS_TABLE  . " p ON v.project_id = p.project_id
							WHERE version_id = '{$version_id}'";
						$result = $db->sql_query($sql);
						if ($row = $db->sql_fetchrow($result))
						{
							$version_data['version_title'] = $row['version_title'];
							$version_data['project_id'] = $row['project_id'];
							$version_data['project_title'] = $row['project_title'];
							$version_data['accept_new'] = $row['accept_new'];
						}
						else
						{
							trigger_error($user->lang['NO_VERSION'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
				}

				$projects_list = '';
				$sql = 'SELECT project_id, project_title FROM ' . BUGS_PROJECTS_TABLE . ' ORDER BY project_title';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
					{
						$projects_list .= '<option value="' . $row['project_id'] . '" ' . ($row['project_id'] == $version_data['project_id'] ? ' selected="selected"' : '') . '>' . $row['project_title'] . '</option>';
					}					

				$template->assign_vars(array(
					'S_EDIT_VERSION'	=> true,
					'S_ERROR'		=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'	=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;v=$version_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'VERSION_TITLE'		=> $version_data['version_title'],
					'S_PROJECT_OPTIONS'	=> $projects_list,
					'S_ACCEPT_NEW'		=> ($version_data['accept_new']) ? true : false,
					)
				);
			}
			switch ($action)
			{
				case 'add':
					$template->assign_vars(array(
						'S_VERSION_ADD'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;
	
				break;

				case 'edit':

					$template->assign_vars(array(
						'PROJECT_TITLE'	=> $version_data['project_title'],

						'S_VERSION_EDIT'	=> true,
						'U_ACTION'			=> $this->u_action,)
					);

					return;

				break;
			}
			if ($action == 'delete')
			{
				// Make sure the version exists
				$sql = 'SELECT version_id FROM ' . BUGS_VERSIONS_TABLE . ' WHERE version_id = ' . $version_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_VERSION', E_USER_WARNING);
				}

				if (confirm_box(true))
				{
					// Bugs having this version assigned will have a version id of 1
					$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . " 
						SET report_version = 1
						WHERE report_version = '{$version_id}'";
					$result = $db->sql_query($sql);

					// Now, remove the version
					$sql = 'DELETE FROM ' . BUGS_VERSIONS_TABLE . " 
						WHERE version_id = '$version_id'";
					$result = $db->sql_query($sql);

					if (!$result)
					{
						trigger_error('NO_VERSION', E_USER_WARNING);
					}

					trigger_error($user->lang['VERSION_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'v'			=> $version_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'BUG_TRACKER_VERSIONS';

			$sql = $db->sql_build_query('SELECT', array(
				'SELECT'	=> 'v.*, p.project_title',	
				'FROM'		=> array(
					BUGS_VERSIONS_TABLE		=> 'v',
				),
				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array(BUGS_PROJECTS_TABLE => 'p'),
						'ON'	=> 'v.project_id = p.project_id'
					)
				),		
				'ORDER_BY'	=> 'p.project_title, v.version_id',
			));
			$result = $db->sql_query($sql);

			$cur_project = 0;
			while ($row = $db->sql_fetchrow($result))
			{
				if ($row['project_id'] != $cur_project)
				{
					$template->assign_block_vars('projects', array(
						'PROJECT_TITLE'	=> sprintf($user->lang['PROJECT_NAMED'], $row['project_title']),)
					);
					$cur_project = $row['project_id'];
				}

				$url = $this->u_action . '&amp;v=' . $row['version_id'];

				$template->assign_block_vars('projects.versions', array(
					'VERSION_TITLE'		=> $row['version_title'],
					'VERSION_ACCEPT_NEW'=> $row['accept_new'] ? $user->lang['YES'] : $user->lang['NO'],
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_VERSIONS'	=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}

		/**
		* Components
		*/
		elseif ($mode == 'components')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$component_id = request_var('c', 0);

			if ($action == 'add' || $action == 'edit')
			{
				$errors = array();

				$component_data = array(
					'project_id'	=> request_var('project_id', 0),
					'component_title'	=> utf8_normalize_nfc(request_var('component_title', '', true)),
				);

				if ($action == 'add')
				{
					$this->page_title = 'ADD_COMPONENT';
					
					if ($update)
					{
						// Validate project
						$sql = 'SELECT project_id FROM ' . BUGS_PROJECTS_TABLE . ' WHERE project_id = ' . $component_data['project_id'];
						$result = $db->sql_query($sql);
						if (!$db->sql_fetchrow($result))
						{
							trigger_error($user->lang['NO_PROJECT'] . adm_back_link($this->u_action));
						}

						$sql = 'INSERT INTO ' . BUGS_COMPONENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $component_data);
						$db->sql_query($sql);
						trigger_error($user->lang['COMPONENT_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit')
				{
					$this->page_title = 'EDIT_COMPONENT';

					if ($update)
					{
						unset($component_data['project_id']); // Do not update that - components cannot be transferred to other projects
						$sql = 'UPDATE ' . BUGS_COMPONENTS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $component_data) . '
							WHERE component_id = ' . $component_id;
						$result = $db->sql_query($sql);
						trigger_error($user->lang['COMPONENT_EDITED'] . adm_back_link($this->u_action));
					}
					else
					{
						// Query the component details
						$sql = 'SELECT c.*, p.project_title
							FROM ' . BUGS_COMPONENTS_TABLE  . ' c
								LEFT JOIN ' . BUGS_PROJECTS_TABLE  . " p ON c.project_id = p.project_id
							WHERE component_id = '{$component_id}'";
						$result = $db->sql_query($sql);
						if ($row = $db->sql_fetchrow($result))
						{
							$component_data['component_title'] = $row['component_title'];
							$component_data['project_id'] = $row['project_id'];
							$component_data['project_title'] = $row['project_title'];
						}
						else
						{
							trigger_error($user->lang['NO_COMPONENT'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
				}

				$projects_list = '';
				$sql = 'SELECT project_id, project_title FROM ' . BUGS_PROJECTS_TABLE . ' ORDER BY project_title';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
					{
						$projects_list .= '<option value="' . $row['project_id'] . '" ' . ($row['project_id'] == $component_data['project_id'] ? ' selected="selected"' : '') . '>' . $row['project_title'] . '</option>';
					}					

				$template->assign_vars(array(
					'S_EDIT_COMPONENT'	=> true,
					'S_ERROR'			=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'		=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;c=$component_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'COMPONENT_TITLE'	=> $component_data['component_title'],
					'S_PROJECT_OPTIONS'	=> $projects_list,
					)
				);
			}
			switch ($action)
			{
				case 'add':
					$template->assign_vars(array(
						'S_COMPONENT_ADD'	=> true,
						'U_ACTION'			=> $this->u_action,)
					);

					return;
	
				break;

				case 'edit':

					$template->assign_vars(array(
						'PROJECT_TITLE'	=> $component_data['project_title'],

						'S_COMPONENT_EDIT'	=> true,
						'U_ACTION'			=> $this->u_action,)
					);

					return;

				break;
			}
			if ($action == 'delete')
			{
				// Make sure the component exists
				$sql = 'SELECT component_id FROM ' . BUGS_COMPONENTS_TABLE . ' WHERE component_id = ' . $component_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_COMPONENT', E_USER_WARNING);
				}

				if (confirm_box(true))
				{
					// Bugs having this component assigned will have a component id of 1
					$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . " 
						SET report_component = 1
						WHERE report_component = '{$component_id}'";
					$result = $db->sql_query($sql);

					// Now, remove the version
					$sql = 'DELETE FROM ' . BUGS_COMPONENTS_TABLE . " 
						WHERE component_id = '$component_id'";
					$result = $db->sql_query($sql);

					if (!$result)
					{
						trigger_error('NO_COMPONENT', E_USER_WARNING);
					}

					trigger_error($user->lang['COMPONENT_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'c'			=> $component_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'BUG_TRACKER_COMPONENTS';

			$sql = $db->sql_build_query('SELECT', array(
				'SELECT'	=> 'c.*, p.project_title',	
				'FROM'		=> array(
					BUGS_COMPONENTS_TABLE		=> 'c',
				),
				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array(BUGS_PROJECTS_TABLE => 'p'),
						'ON'	=> 'c.project_id = p.project_id'
					)
				),		
				'ORDER_BY'	=> 'p.project_title, c.component_title',
			));
			$result = $db->sql_query($sql);

			$cur_project = 0;
			while ($row = $db->sql_fetchrow($result))
			{
				if ($row['project_id'] != $cur_project)
				{
					$template->assign_block_vars('projects', array(
						'PROJECT_TITLE'	=> sprintf($user->lang['PROJECT_NAMED'], $row['project_title']),)
					);
					$cur_project = $row['project_id'];
				}

				$url = $this->u_action . '&amp;c=' . $row['component_id'];

				$template->assign_block_vars('projects.components', array(
					'COMPONENT_TITLE'	=> $row['component_title'],
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_COMPONENTS'	=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}
	}

	/**
	* Update forum data
	*/
	function update_forum_data(&$forum_data)
	{
		global $db;

		$errors = array();

		if (!$forum_data['project_idname'])
		{
			$errors[] = $user->lang['PROJECT_IDNAME_EMPTY'];
		}
		elseif (strlen($forum_data['project_idname']) > 100)
		{
			$errors[] = $user->lang['PROJECT_IDNAME_TOO_LONG'];
		}
		elseif (!preg_match('#^[a-z0-9_-]+$#i', $forum_data['project_idname']))
		{
			$errors[] = $user->lang['PROJECT_IDNAME_WRONG_CHARACTERS'];
		}

		if (sizeof($errors))
		{
			return $errors;
		}
	
		$new_project = (isset($forum_data['forum_id'])) ? false : true;
		$project_idname = $forum_data['project_idname'];
		unset($forum_data['project_idname']);

		// First add/update the forum (also generates the forum id!)
		$errors = $this->parent->update_forum_data(&$forum_data);

		if (sizeof($errors))
		{
			return $errors;
		}
	
		$project_data_sql = array(
			'forum_id' 		=> $forum_data['forum_id'],
			'project_name'	=> $project_idname,
			'project_title'	=> $forum_data['forum_name'],
			);

		if ($new_project)
		{
			$sql = 'INSERT INTO ' . BUGS_PROJECTS_TABLE . ' ' . $db->sql_build_array('INSERT', $project_data_sql);
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'UPDATE ' . BUGS_PROJECTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $project_data_sql) . '
				WHERE forum_id = ' . $forum_data['forum_id'];
			$db->sql_query($sql);
		}
	}

	/**
	* Remove complete project
	* Currently only supports removing only the project (not even the report entries!)
	*/
	function delete_forum($forum_id, $action_posts = 'delete', $action_subforums = 'delete', $posts_to_id = 0, $subforums_to_id = 0)
	{
		global $db, $user, $cache;

		// First remove the project entry
		$sql = 'DELETE FROM ' . BUGS_PROJECTS_TABLE . '
			WHERE forum_id = ' . $forum_id;
		$db->sql_query($sql);

		// Then remove the forum itself
		return $this->parent->delete_forum($forum_id, $action_posts, $action_subforums, $posts_to_id, $subforums_to_id);
	}
}

?>