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
class acp_tags
{
	var $u_action;
	var $parent_id = 0;
	var $modules = array(
		TAG_KB		=> 'KB',
		TAG_MODS	=> 'MODS',
	);

	function main($id, $mode)
	{
		/*global $db, $user, $auth, $template, $cache;
		global $config, $phpbb_admin_path, $phpbb_root_path, $phpEx;*/
		global $cache, $db, $user, $template;
		global $phpbb_root_path, $phpEx;

		$user->add_lang('acp/site');
		$this->tpl_name = 'acp_tags';
		$this->page_title = 'ACP_TAGS_MANAGEMENT';

		/**
		* Tag categories
		*/
		if ($mode == 'cats')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$tagcat_id = request_var('tc', 0);

			if ($action == 'add' || $action == 'edit')
			{
				$errors = array();

				$tagcat_data = array(
					'tagcat_name'	=> request_var('tagcat_name', ''),
					'tagcat_title'	=> utf8_normalize_nfc(request_var('tagcat_title', '', true)),
					'tagcat_module'	=> request_var('tagcat_module', 0),
				);
				
				// Run some checks
				if ($update)
				{
					if (empty($tagcat_data['tagcat_name']))
					{
						$errors[] = $user->lang['TAGCAT_IDNAME_EMPTY'];
					}
					elseif (strlen($tagcat_data['tagcat_name']) > 100)
					{
						$errors[] = $user->lang['TAGCAT_IDNAME_TOO_LONG'];
					}
					elseif (!preg_match('#^[a-z0-9_-]+$#i', $tagcat_data['tagcat_name']))
					{
						$errors[] = $user->lang['TAGCAT_IDNAME_WRONG_CHARACTERS'];
					}
					// Check if a tag category with this name (within the same module) already exists
					else
					{
						$sql = 'SELECT tagcat_id
							FROM ' . TAGCATS_TABLE  . " tc
							WHERE tagcat_name = '" . $db->sql_escape($tagcat_data['tagcat_name']) . "'
								AND tagcat_module = {$tagcat_data['tagcat_module']}";
						$result = $db->sql_query($sql);
						if ($db->sql_fetchrow($result) != false)
						{
							$errors[] = $user->lang['TAGCAT_IDNAME_ALREADY_EXISTS'];
						}
					}
					if (empty($tagcat_data['tagcat_title']))
					{
						$errors[] = $user->lang['TAGCAT_TITLE_EMPTY'];
					}
					if ($action == 'add' && !isset($this->modules[$tagcat_data['tagcat_module']]))
					{
						$errors[] = $user->lang['TAGCAT_MODULE_WRONG'];
					}
				}

				if ($action == 'add' && !sizeof($errors))
				{
					$this->page_title = 'ADD_TAGCAT';
					
					if ($update)
					{
						$sql = 'INSERT INTO ' . TAGCATS_TABLE . ' ' . $db->sql_build_array('INSERT', $tagcat_data);
						$db->sql_query($sql);
						
						// Purge cache
						$cache->destroy('sql', TAGCATS_TABLE);
						
						trigger_error($user->lang['TAGCAT_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit' && !sizeof($errors))
				{
					$this->page_title = 'EDIT_TAGCAT';

					$sql = 'SELECT * FROM ' . TAGCATS_TABLE  . " WHERE tagcat_id = '{$tagcat_id}'";
					$result = $db->sql_query($sql);
					if ($row = $db->sql_fetchrow($result))
					{
						if ($update && !sizeof($errors))
						{
							// Do not update tagcat module
							unset($tagcat_data['tagcat_module']);
							
							$sql = 'UPDATE ' . TAGCATS_TABLE . '
								SET ' . $db->sql_build_array('UPDATE', $tagcat_data) . '
								WHERE tagcat_id = ' . $tagcat_id;
							$result = $db->sql_query($sql);
							
							// Purge cache
							$cache->destroy('sql', TAGCATS_TABLE);
							
							trigger_error($user->lang['TAGCAT_EDITED'] . adm_back_link($this->u_action));
						}
						else
						{
							$tagcat_data['tagcat_title'] = $row['tagcat_title'];
							$tagcat_data['tagcat_name'] = $row['tagcat_name'];
							$tagcat_data['tagcat_module'] = $row['tagcat_module'];
						}
					}
					else
					{
						trigger_error($user->lang['NO_TAGCAT'] . adm_back_link($this->u_action), E_USER_WARNING);
					}
				}

				$modules_list = '';
				foreach ($this->modules as $id => $name)
				{
					$modules_list .= '<option value="' . $id . '" ' . ($id == $tagcat_data['tagcat_module'] ? ' selected="selected"' : '') . '>' . $user->lang[$name] . '</option>';
				}
				
				$template->assign_vars(array(
					'S_EDIT_TAGCAT'	=> true,
					'S_ERROR'		=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'	=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;tc=$tagcat_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'TAGCAT_TITLE'			=> $tagcat_data['tagcat_title'],
					'TAGCAT_NAME'			=> $tagcat_data['tagcat_name'],
					'TAGCAT_MODULE_TITLE'	=> isset($this->modules[$tagcat_data['tagcat_module']]) ? $user->lang[$this->modules[$tagcat_data['tagcat_module']]] : '',
					'S_MODULES_OPTIONS'		=> $modules_list,
					)
				);
			}
			switch ($action)
			{
				case 'add':
					$template->assign_vars(array(
						'S_TAGCAT_ADD'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;
	
				break;

				case 'edit':

					$template->assign_vars(array(
						'S_TAGCAT_EDIT'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;

				break;
			}
			if ($action == 'delete')
			{
				// Make sure the tag category exists
				$sql = 'SELECT tagcat_id FROM ' . TAGCATS_TABLE . ' WHERE tagcat_id = ' . $tagcat_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_TAGCAT', E_USER_WARNING);
				}
				
				// Check whether the category contains any tag
				$sql = 'SELECT COUNT(tag_id) AS num FROM ' . TAGS_TABLE . ' WHERE tagcat_id = ' . $tagcat_id;
				$result = $db->sql_query($sql);
				if ($db->sql_fetchfield('num', null, $result) > 0)
				{
					trigger_error('TAGCAT_HAS_CHILDREN', E_USER_WARNING);
				}
				
				if (confirm_box(true))
				{
					$sql = 'DELETE FROM ' . TAGCATS_TABLE . " 
						WHERE tagcat_id = '$tagcat_id'";
					$result = $db->sql_query($sql);

					// Purge cache
					$cache->destroy('sql', TAGCATS_TABLE);
					
					trigger_error($user->lang['TAGCAT_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'tc'		=> $tagcat_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'TAGS_CATS';

			// Query the tag categories
			$sql = 'SELECT * FROM ' . TAGCATS_TABLE  . ' ORDER BY tagcat_module ASC, tagcat_title ASC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$url = $this->u_action . '&amp;tc=' . $row['tagcat_id'];

				$template->assign_block_vars('tagcats', array(
					'TAGCAT_TITLE'		=> $row['tagcat_title'],
					'TAGCAT_MODULE'		=> isset($this->modules[$row['tagcat_module']]) ? $user->lang[$this->modules[$row['tagcat_module']]] : '',
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_TAGCATS'		=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}

		/**
		* Tags
		*/
		elseif ($mode == 'tags')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$tag_id = request_var('t', 0);

			if ($action == 'add' || $action == 'edit')
			{
				$errors = array();

				$tag_data = array(
					'tagcat_id'	=> request_var('tagcat_id', 0),
					'tag_title'	=> utf8_normalize_nfc(request_var('tag_title', '', true)),
					'tag_name'	=> request_var('tag_name', ''),
				);

				// Run some checks
				if ($update)
				{
					if (empty($tag_data['tag_name']))
					{
						$errors[] = $user->lang['TAG_IDNAME_EMPTY'];
					}
					elseif (strlen($tag_data['tag_name']) > 100)
					{
						$errors[] = $user->lang['TAG_IDNAME_TOO_LONG'];
					}
					elseif (!preg_match('#^[a-z0-9_-]+$#i', $tag_data['tag_name']))
					{
						$errors[] = $user->lang['TAG_IDNAME_WRONG_CHARACTERS'];
					}
					// Check if a tag with this name (within the same category) already exists
					else
					{
						$sql = 'SELECT tag_id
							FROM ' . TAGS_TABLE  . " t
							WHERE tag_name = '" . $db->sql_escape($tag_data['tag_name']) . "'
								AND tagcat_id = {$tag_data['tagcat_id']}";
						$result = $db->sql_query($sql);
						if ($db->sql_fetchrow($result) != false)
						{
							$errors[] = $user->lang['TAG_IDNAME_ALREADY_EXISTS'];
						}
					}
					if (empty($tag_data['tag_title']))
					{
						$errors[] = $user->lang['TAG_TITLE_EMPTY'];
					}
				}
				
				if ($action == 'add')
				{
					$this->page_title = 'ADD_TAG';
					
					if ($update)
					{
						// Validate tag catetory
						$sql = 'SELECT tagcat_id FROM ' . TAGCATS_TABLE . ' WHERE tagcat_id = ' . $tag_data['tagcat_id'];
						$result = $db->sql_query($sql);
						if (!$db->sql_fetchrow($result))
						{
							trigger_error($user->lang['NO_TAGCAT'] . adm_back_link($this->u_action));
						}

						$sql = 'INSERT INTO ' . TAGS_TABLE . ' ' . $db->sql_build_array('INSERT', $tag_data);
						$db->sql_query($sql);
						
						// Purge cache
						$cache->destroy('sql', TAGS_TABLE);
						
						trigger_error($user->lang['TAG_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit')
				{
					$this->page_title = 'EDIT_TAG';

					if ($update)
					{
						unset($tag_data['tagcat_id']); // Do not update that - tags cannot be transferred between tag categories
						$sql = 'UPDATE ' . TAGS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $tag_data) . '
							WHERE tag_id = ' . $tag_id;
						$result = $db->sql_query($sql);
						
						// Purge cache
						$cache->destroy('sql', TAGS_TABLE);
						
						trigger_error($user->lang['TAG_EDITED'] . adm_back_link($this->u_action));
					}
					else
					{
						// Query the tag details
						$sql = 'SELECT t.*, tc.tagcat_title
							FROM ' . TAGS_TABLE  . ' t
								LEFT JOIN ' . TAGCATS_TABLE  . " tc ON t.tagcat_id = tc.tagcat_id
							WHERE tag_id = '{$tag_id}'";
						$result = $db->sql_query($sql);
						if ($row = $db->sql_fetchrow($result))
						{
							$tag_data['tag_title'] = $row['tag_title'];
							$tag_data['tag_name'] = $row['tag_name'];
							$tag_data['tagcat_title'] = $row['tagcat_title'];
						}
						else
						{
							trigger_error($user->lang['NO_TAG'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
				}

				$tagcats_list = '';
				$sql = 'SELECT tagcat_id, tagcat_title FROM ' . TAGCATS_TABLE . ' ORDER BY tagcat_title';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
					{
						$tagcats_list .= '<option value="' . $row['tagcat_id'] . '" ' . ($row['tagcat_id'] == $tag_data['tagcat_id'] ? ' selected="selected"' : '') . '>' . $row['tagcat_title'] . '</option>';
					}					

				$template->assign_vars(array(
					'S_EDIT_TAG'	=> true,
					'S_ERROR'		=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'	=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;t=$tag_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'TAG_TITLE'			=> $tag_data['tag_title'],
					'TAG_NAME'			=> $tag_data['tag_name'],
					'S_TAGCATS_OPTIONS'	=> $tagcats_list,
					)
				);
			}
			switch ($action)
			{
				case 'add':
					$template->assign_vars(array(
						'S_TAG_ADD'	=> true,
						'U_ACTION'		=> $this->u_action,)
					);

					return;
	
				break;

				case 'edit':

					$template->assign_vars(array(
						'TAGCAT_TITLE'		=> $tag_data['tagcat_title'],

						'S_TAG_EDIT'	=> true,
						'U_ACTION'			=> $this->u_action,)
					);

					return;

				break;
			}
			if ($action == 'delete')
			{
				// Make sure the tag exists
				$sql = 'SELECT tag_id FROM ' . TAGS_TABLE . ' WHERE tag_id = ' . $tag_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_TAG', E_USER_WARNING);
				}

				if (confirm_box(true))
				{
					// All content items having this tag assigned will lose this tag
					$sql = 'DELETE FROM ' . TAGMATCH_TABLE . " 
						WHERE tag_id = '{$tag_id}'";
					$result = $db->sql_query($sql);

					// Now, remove the tag
					$sql = 'DELETE FROM ' . TAGS_TABLE . " 
						WHERE tag_id = '$tag_id'";
					$result = $db->sql_query($sql);

					if (!$result)
					{
						trigger_error('NO_TAG', E_USER_WARNING);
					}

					// Purge cache
					$cache->destroy('sql', TAGS_TABLE);
					
					trigger_error($user->lang['TAG_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						't'			=> $tag_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'TAGS';

			$sql = $db->sql_build_query('SELECT', array(
				'SELECT'	=> 't.*, tc.tagcat_title, tc.tagcat_module, COUNT(tm.tag_id) as num',	
				'FROM'		=> array(
					TAGS_TABLE		=> 't',
				),
				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array(TAGCATS_TABLE => 'tc'),
						'ON'	=> 't.tagcat_id = tc.tagcat_id'
					),
					array(
						'FROM'	=> array(TAGMATCH_TABLE => 'tm'),
						'ON'	=> 't.tag_id = tm.tag_id'
					)
				),		
				'GROUP_BY'	=> 't.tag_id',
				'ORDER_BY'	=> 'tc.tagcat_title, t.tag_title',
			));
			$result = $db->sql_query($sql);

			$cur_tagcat = 0;
			while ($row = $db->sql_fetchrow($result))
			{
				if ($row['tagcat_id'] != $cur_tagcat)
				{
					$template->assign_block_vars('tagcats', array(
						'TAGCAT_TITLE'	=> sprintf($user->lang['TAGCAT_NAMED'], $row['tagcat_title'], $user->lang[$this->modules[$row['tagcat_module']]]),
					));
					$cur_tagcat = $row['tagcat_id']; 
				}

				$url = $this->u_action . '&amp;t=' . $row['tag_id'];

				$template->assign_block_vars('tagcats.tags', array(
					'TAG_TITLE'			=> $row['tag_title'],
					'TAG_USED_NUM'		=> $row['num'],
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_TAGS'		=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}





	}





}

?>