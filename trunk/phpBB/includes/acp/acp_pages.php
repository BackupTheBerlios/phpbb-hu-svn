<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package acp
*/
class acp_pages
{
	var $u_action;
	var $parent_id = 0;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_admin_path, $phpbb_root_path, $phpEx;

		$user->add_lang('acp/site');
		$this->tpl_name = 'acp_pages';
		$this->page_title = 'ACP_PAGES';

		/**
		* Pages
		*/
		if ($mode == 'pages')
		{
			$action = request_var('action', '');
			$update	= (isset($_POST['update'])) ? true : false;
			$page_id = request_var('p', 0);
			
			if ($action == 'add' || $action == 'edit')
			{
				$form_key = 'acp_pages';
				add_form_key($form_key);
				
				$errors = array();
				
				if ($update && !check_form_key($form_key))
				{
					$update = false;
					$errors[] = $user->lang['FORM_INVALID'];
				}
		
				$page_data = array(
					'page_url'		=> request_var('page_url', ''),
					'page_section'	=> request_var('page_section', ''),
					'page_file'		=> request_var('page_file', ''),
					'page_title'	=> utf8_normalize_nfc(request_var('page_title', '', true)),
					'page_content'	=> utf8_normalize_nfc(request_var('page_content', '', true)),
					'page_comments'	=> utf8_normalize_nfc(request_var('page_comments', '', true)),
				);

				// Do some checks
				if ($update)
				{
					// Only files in subdirectories are allowed
					if (strpos($page_data['page_file'], '..') !== false)
					{
						$errors[] = $user->lang['NO_PAGE_FILE_FORMAT'];
					}
					elseif (!file_exists($phpbb_root_path . $page_data['page_file']))
					{
						$errors[] = $user->lang['NO_PAGE_FILE'];
					}
					
					$sql = 'SELECT page_id FROM ' . PAGES_TABLE . " WHERE page_url =  '" . $db->sql_escape($page_data['page_url']) . "'
						" . ($action == 'edit' ? 'AND page_id <> ' . $page_id : '');
					$result = $db->sql_query($sql);
					if ($db->sql_fetchrow($result))
					{
						$errors[] = $user->lang['NO_PAGE_URL_EXISTS'];
					}
					elseif (!preg_match('#^[a-z0-9._-]*$#is', $page_data['page_url']))
					{
						$errors[] = $user->lang['NO_PAGE_URL_FORMAT'];
					}
				}
				
				// HTML tags _can_ be used in page content so we have to decode them here
				if ($update && !sizeof($errors))
				{
					$page_data['page_content'] = htmlspecialchars_decode($page_data['page_content']);
				}
				
				if ($action == 'add')
				{
					$this->page_title = 'ADD_PAGE';
					
					if ($update && !sizeof($errors))
					{
						$sql = 'INSERT INTO ' . PAGES_TABLE . ' ' . $db->sql_build_array('INSERT', $page_data);
						$db->sql_query($sql);
						trigger_error($user->lang['PAGE_ADDED'] . adm_back_link($this->u_action));
					}
				}
				
				elseif ($action == 'edit')
				{
					$this->page_title = 'EDIT_PAGE';

					if ($update && !sizeof($errors))
					{
						$sql = 'UPDATE ' . PAGES_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $page_data) . '
							WHERE page_id = ' . $page_id;
						$result = $db->sql_query($sql);
						trigger_error($user->lang['PAGE_EDITED'] . adm_back_link($this->u_action));
					}
					elseif (!sizeof($errors))
					{
						// Query the page details
						$sql = 'SELECT * FROM ' . PAGES_TABLE  . " WHERE page_id = {$page_id}";
						$result = $db->sql_query($sql);
						if ($row = $db->sql_fetchrow($result))
						{
							$page_data = array(
								'page_url'		=> $row['page_url'],
								'page_section'	=> $row['page_section'],
								'page_file'		=> $row['page_file'],
								'page_title'	=> $row['page_title'],
								'page_content'	=> htmlspecialchars($row['page_content']),
								'page_comments'	=> $row['page_comments'],
							);
						}
						else
						{
							trigger_error($user->lang['NO_PAGE'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
				}

				$template->assign_vars(array(
					'S_EDIT_PAGE'	=> true,
					'S_ERROR'		=> (sizeof($errors)) ? true : false,
					'S_ADD_ACTION'	=> ($action == 'add') ? true : false,

					'U_BACK'		=> $this->u_action,
					'U_EDIT_ACTION'	=> $this->u_action . "&amp;action=$action&amp;p=$page_id",

					'L_TITLE'		=> $user->lang[$this->page_title],
					'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',

					'PAGE_URL'		=> $page_data['page_url'],
					'PAGE_SECTION'	=> $page_data['page_section'],
					'PAGE_FILE'		=> $page_data['page_file'],
					'PAGE_TITLE_V'	=> $page_data['page_title'],
					'PAGE_CONTENT'	=> $page_data['page_content'],
					'PAGE_COMMENTS'	=> $page_data['page_comments'],
				
					'TEXT_ROWS'		=> request_var('text_rows', 20),
				));
				
				
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
				// Make sure the page exists
				$sql = 'SELECT page_id FROM ' . PAGES_TABLE . ' WHERE page_id = ' . $page_id;
				$result = $db->sql_query($sql);
				if (!$db->sql_fetchrow($result))
				{
					trigger_error('NO_PAGE', E_USER_WARNING);
				}

				if (confirm_box(true))
				{
					// Remove page
					$sql = 'DELETE FROM ' . PAGES_TABLE . " 
						WHERE page_id = '$page_id'";
					$result = $db->sql_query($sql);

					trigger_error($user->lang['PAGE_DELETED'] . adm_back_link($this->u_action));
				}
				else
				{
					confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
						'p'			=> $page_id,
						'mode'		=> $mode,
						'action'	=> $action))
					);
				}
			}

			$this->page_title = 'ACP_PAGES';

			// Query the statuses
			$sql = 'SELECT page_id, page_url, page_section, page_file, page_title FROM ' . PAGES_TABLE  . ' ORDER BY page_url ASC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$url = $this->u_action . '&amp;p=' . $row['page_id'];

				$template->assign_block_vars('page', array(
					'PAGE_ID'		=> $row['page_id'],
					'PAGE_URL'		=> $row['page_url'],
					'PAGE_SECTION'	=> $row['page_section'],
					'PAGE_FILE'		=> $row['page_file'],
					'PAGE_TITLE'	=> $row['page_title'],
					'U_EDIT'		=> $url . '&amp;action=edit',
					'U_DELETE'		=> $url . '&amp;action=delete',)
				);
			}

			$template->assign_vars(array(
				'S_PAGES'		=> true,
				'U_ACTION'		=> $this->u_action,)
			);
		}
	}

	// Functions (placeholder)
}

?>