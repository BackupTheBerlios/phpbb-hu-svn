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

define('SITE_SECTION', 'bugs');

$mode = request_var('mode', '');
$project_name = request_var('project', '');

/**
* List reports 
*/
if ($mode == 'project')
{
	$start = request_var('start', 0);
	// Filtering params
	$filter_ary = array(
		'closed'	=> request_var('closed', 0),
		'component'	=> request_var('component', -1),
		'assigned'	=> request_var('assigned', -1),
		'status'	=> request_var('status', -1),
		'version'	=> request_var('version', -1),
		'reporter'	=> request_var('reporter', -1),
	);
	$sort_option = request_var('order_by', 'desc');
	$sort_order = request_var('order', 'reported');
		
	// Check if project exists
	// and also grab project and forum data (forum tracking data too)
	$sql_array = array(
		'SELECT'	=> 'p.*, f.forum_status', // f.forum_desc AS project_description, f.forum_desc_uid, f.forum_desc_bitfield, f.forum_desc_options, f.forum_topics, f.forum_topics_real
		'FROM'		=> array(BUGS_PROJECTS_TABLE	=> 'p'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'p.forum_id = f.forum_id'
			)
		),
		'WHERE'	=> "p.project_name = '" . $db->sql_escape($project_name) . "'",
	);
	
	// Forum tracking
	if ($config['load_db_lastread'] && $user->data['is_registered'])
	{
		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
			'ON'	=> 'ft.user_id = ' . $user->data['user_id'] . ' AND ft.forum_id = f.forum_id'
		);
		$sql_array['SELECT'] .= ', ft.mark_time';
	}

	$result = $db->sql_query($db->sql_build_query('SELECT', $sql_array));

	if (($project = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_PROJECT', E_USER_NOTICE);
	}
	
	$db->sql_freeresult($result);

	// Often used variables
	$forum_id = $project['forum_id'];
	$project_id = $project['project_id'];
	
	// Check if user has the necessary permissions
	if (!$auth->acl_get('f_c_see', $forum_id))
	{
		http_status(403);
		trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
	}
	
	// Filtering
	$filter_sql_ary = array();
	if ($filter_ary['closed'] == 0 || $filter_ary['closed'] == 1)
	{
		$filter_sql_ary[] = 'r.report_closed = ' . $filter_ary['closed'];
	}
	if ($filter_ary['component'] != -1)
	{
		$filter_sql_ary[] = 'r.report_component = ' . $filter_ary['component'];
	}
	if ($filter_ary['assigned'] != -1)
	{
		$filter_sql_ary[] = 'r.report_assigned = ' . $filter_ary['assigned'];
	}
	if ($filter_ary['status'] != -1)
	{
		$filter_sql_ary[] = 'r.report_status = ' . $filter_ary['status'];
	}
	if ($filter_ary['version'] != -1)
	{
		$filter_sql_ary[] = 'r.report_version = ' . $filter_ary['version'];
	}
	if ($filter_ary['reporter'] != -1)
	{
		$filter_sql_ary[] = 't.topic_poster = ' . $filter_ary['reporter'];
	}
	
	$u_filt_param_pieces = array();
	foreach($filter_ary as $option => $value)
	{
		$u_filt_param_pieces[] = $option . '=' . $value;
	}
	$u_filt_param = implode('&amp;', $u_filt_param_pieces);
	
	// Sorting
	$sort_options = array(
		'id'		=> 'r.report_id',
		'title'		=> 'r.report_title',
		'component'	=> 'c.component_title',
		'reporter'	=> 't.topic_poster',
		'assigned'	=> 'u.username',
		'status'	=> 's.status_title',
		'version'	=> 'v.version_title',
		'reported'	=> 't.topic_time',
		'lastpost'	=> 't.topic_last_post_time',
	);
	
	// If the given sort option / order doesn't exist reset it to a default one
	$sort_option = !isset($sort_options[$sort_option]) ? 'reported' : $sort_option;
	$sort_order = $sort_order == 'asc' ? 'asc' : 'desc';
	
	$sql_sort = $sort_options[$sort_option] . ' ' . $sort_order;
 	
	$u_filt_param .= '&amp;order_by=' . $sort_option . '&amp;order=' . $sort_order;
	
	// Get the total number of reports
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'COUNT(r.report_id) AS reports_count',		
		'FROM'		=> array(BUGS_REPORTS_TABLE	=> 'r'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'r.topic_id = t.topic_id'
			),
		),
		'WHERE'	=> "r.project_id = '{$project_id}'
			" . ($auth->acl_get('m_approve', $forum_id) ? '' : 'AND (topic_approved = 1 OR topic_poster =' . $user->data['user_id'] . ')') .
			(empty($filter_sql_ary) ? '' : ' AND ' . implode(' AND ', $filter_sql_ary)),
		));
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$reports_count = $row['reports_count'];	
	$db->sql_freeresult($result);
	
	// Query the reports
	$sql_array = array(
		'SELECT'	=> 'r.*, t.topic_poster, t.topic_time, t.topic_replies, t.topic_replies_real, t.topic_status, t.topic_first_poster_name, t.topic_first_poster_colour, t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour, t.topic_last_post_time, t.poll_start, c.component_title, s.status_title, v.version_title, a.user_id AS assigned_id, a.username AS assigned_name, a.user_colour AS assigned_colour',
		'FROM'		=> array(BUGS_REPORTS_TABLE	=> 'r'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'r.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(BUGS_COMPONENTS_TABLE => 'c'),
				'ON'	=> 'r.report_component = c.component_id'
			),
			array(
				'FROM'	=> array(BUGS_STATUSES_TABLE => 's'),
				'ON'	=> 'r.report_status = s.status_id'
			),
			array(
				'FROM'	=> array(BUGS_VERSIONS_TABLE => 'v'),
				'ON'	=> 'r.report_version = v.version_id'
			),
			array(
				'FROM'	=> array(USERS_TABLE => 'a'),
				'ON'	=> 'r.report_assigned = a.user_id'
			)
		),
		'WHERE'	=> "r.project_id = '{$project['project_id']}'
			" . ($auth->acl_get('m_approve', $forum_id) ? '' : 'AND (topic_approved = 1 OR topic_poster =' . $user->data['user_id'] . ')') .
			(empty($filter_sql_ary) ? '' : ' AND ' . implode(' AND ', $filter_sql_ary)),
		'ORDER_BY'	=> $sql_sort,
	);
	
	// Topic tracking
	if ($user->data['is_registered'])
	{
		if ($config['load_db_track'])
		{
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_POSTED_TABLE => 'tp'), 'ON' => 'tp.topic_id = t.topic_id AND tp.user_id = ' . $user->data['user_id']);
			$sql_array['SELECT'] .= ', tp.topic_posted';
		}
	
		if ($config['load_db_lastread'])
		{
			$sql_array['LEFT_JOIN'][] = array('FROM' => array(TOPICS_TRACK_TABLE => 'tt'), 'ON' => 'tt.topic_id = t.topic_id AND tt.user_id = ' . $user->data['user_id']);
			$sql_array['SELECT'] .= ', tt.mark_time';
		}
	}
	
	$result = $db->sql_query_limit($db->sql_build_query('SELECT', $sql_array), 25, $start);

	// Insert the entries into an array, because we have to have an array of these to be able to use topic tracking functions
	$rowset = $topic_list = array();
	while ($row = $db->sql_fetchrow($result))
	{
		/*if ($row['topic_status'] == ITEM_MOVED)
		{
			$shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
		}*/

		$rowset[$row['topic_id']] = $row;
		$topic_list[] = $row['topic_id'];
	}
	
	// Take care of topic tracking
	$topic_tracking_info = $tracking_topics = array();

	if ($config['load_db_lastread'] && $user->data['is_registered'])
	{
		$topic_tracking_info = get_topic_tracking($forum_id, $topic_list, $rowset, array($forum_id => $project['mark_time']));
		$mark_time_forum = (!empty($project['mark_time'])) ? $project['mark_time'] : $user->data['user_lastmark'];
	}
	else if ($config['load_anon_lastread'] || $user->data['is_registered'])
	{
		$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_list);

		if (!$user->data['is_registered'])
		{
			$user->data['user_lastmark'] = (isset($tracking_topics['l'])) ? (int) (base_convert($tracking_topics['l'], 36, 10) + $config['board_startdate']) : 0;
		}
		$mark_time_forum = (isset($tracking_topics['f'][$forum_id])) ? (int) (base_convert($tracking_topics['f'][$forum_id], 36, 10) + $config['board_startdate']) : $user->data['user_lastmark'];
	}
	
	foreach ($rowset as $topic_id => $row)
	{
		$replies = ($auth->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];
		
		$unread_topic = (isset($topic_tracking_info[$topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$topic_id]) ? true : false;

		// Get folder img, topic status/type related information
		$folder_img = $folder_alt = $topic_type = '';
		topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $row['topic_type']);
		
		$template->assign_block_vars('reports', array(
			'REPORT_ID'					=> $row['report_id'],
			'REPORT_TITLE'				=> censor_text($row['report_title']),
			'REPORT_POSTER'				=> $row['topic_poster'],
			'REPORT_AUTHOR'				=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'REPORT_AUTHOR_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'REPORT_AUTHOR_FULL'		=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
			'LAST_POST_AUTHOR'			=> get_username_string('username', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
			'LAST_POST_AUTHOR_COLOUR'	=> get_username_string('colour', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
			'LAST_POST_AUTHOR_FULL'		=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
			'REPORT_TIME'				=> $user->format_date($row['topic_time']),		
			'LAST_POST_TIME'			=> $user->format_date($row['topic_last_post_time']),
			'LONG_INFO'					=> sprintf(($replies == 0 ? $user->lang['BUG_LONG_INFO'] : $user->lang['BUG_LONG_INFO_REPLIED']), get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']), $user->format_date($row['topic_time']), get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']), $user->format_date($row['topic_last_post_time'])),
			'REPORT_REPLIES'			=> $replies,
			'REPORT_COMPONENT'			=> $row['component_title'],
			'REPORT_STATUS'				=> $row['status_title'],
			'REPORT_VERSION'			=> $row['version_title'],
			'REPORT_URL'				=> append_sid($phpbb_root_path . 'bugs.' . $phpEx, 'mode=report&amp;project=' . urlencode($project['project_name']) . '&amp;report_id=' . $row['report_id']),
			'REPORT_ASSIGNED'			=> $row['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('username', $row['assigned_id'], $row['assigned_name'], $row['assigned_colour']),
			'REPORT_ASSIGNED_COLOUR'	=> $row['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('colour', $row['assigned_id'], $row['assigned_name'], $row['assigned_colour']),
			'REPORT_ASSIGNED_FULL'		=> $row['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('full', $row['assigned_id'], $row['assigned_name'], $row['assigned_colour']),

			'TOPIC_FOLDER_IMG'		=> $user->img($folder_img, $folder_alt),
			'TOPIC_FOLDER_IMG_SRC'	=> $user->img($folder_img, $folder_alt, false, '', 'src'),
			'TOPIC_FOLDER_IMG_ALT'	=> $user->lang[$folder_alt],
			'S_UNREAD_TOPIC'		=> $unread_topic,
		));
	}
	$db->sql_freeresult($result);
	
	$fields_ary = $filter_ary;
	unset($fields_ary['closed']);
	$fields_ary += array('order_by' => $sort_option, 'order' => $sort_order);
	$s_fields = build_hidden_fields($fields_ary);
	
	$template->assign_vars(array(
		'FILT_CLOSED'			=> $filter_ary['closed'],
		'S_FILTER_HIDDEN_FIELDS'=> $s_fields,

		'S_IS_LOCKED'			=> ($project['forum_status'] == ITEM_LOCKED) ? true : false,
		'S_DISPLAY_POST_INFO'	=> $auth->acl_get('f_c_post', $project['forum_id']) || $user->data['user_id'] == ANONYMOUS,
		'U_ADD_REPORT'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", 'mode=add&project=' . $project['project_name']),
		'U_ACTION'				=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", 'mode=project&project=' . $project['project_name']),
		'U_MY_REPORTS'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", 'mode=project&project=' . $project['project_name'] . '&amp;' . str_replace('reporter=' . $filter_ary['reporter'], 'reporter=' . $user->data['user_id'], $u_filt_param)),
		'U_MY_ASSIGNED_REPORTS'	=> $auth->acl_get('m_c_manage', $project['forum_id']) ? append_sid("{$phpbb_root_path}bugs.{$phpEx}", 'mode=project&project=' . $project['project_name'] . '&amp;' . str_replace('assigned=' . $filter_ary['assigned'], 'assigned=' . $user->data['user_id'], $u_filt_param)) : false,

		'NEWEST_POST_IMG'			=> $user->img('icon_topic_newest', 'VIEW_NEWEST_POST'),
		'LAST_POST_IMG'				=> $user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
	
		'PAGINATION'	=> generate_pagination(append_sid("{$phpbb_root_path}bugs.$phpEx", "mode=project&amp;project={$project_name}&amp;$u_filt_param"), $reports_count, 25, $start, true),
		'PAGE_NUMBER'	=> on_page($reports_count, 25, $start),
		'TOTAL_REPORTS'	=> ($reports_count == 1) ? $user->lang['VIEW_REPORT'] : sprintf($user->lang['VIEW_REPORTS'], $reports_count),
	));
	
	site_header(
		$user->lang['BUG_TRACKER'] . ': ' . $project['project_title'],
		'bugs',
		array(array('bugs.' . $phpEx, 'BUG_TRACKER'), array("bugs.$phpEx?mode=project&amp;project={$project['project_name']}", $project['project_title'])) // todo@ add sorting and start parameters?
	);
	
	$template->set_filenames(array(
		'body' => 'bugs_project.html')
	);
		
	site_footer();
}

/**
* Report display page
*/
elseif ($mode == 'report')
{
	$project_name = request_var('project', '');
	$report_id = request_var('report_id', 0);
	$action = request_var('action', '');
	
	// Query the report
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'r.*, pr.*, f.forum_last_post_time, t.topic_approved, t.topic_poster, t.topic_time, t.topic_status, t.topic_first_poster_name, t.topic_first_poster_colour, t.topic_last_post_time, p.post_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.post_text, p.bbcode_bitfield, p.bbcode_uid, c.component_title, s.status_title, v.version_title, a.user_id AS assigned_id, a.username AS assigned_name, a.user_colour AS assigned_colour',
		'FROM'		=> array(BUGS_REPORTS_TABLE	=> 'r'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(BUGS_PROJECTS_TABLE => 'pr'),
				'ON'	=> 'r.project_id = pr.project_id'
			),
			array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'pr.forum_id = f.forum_id'
			),
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'r.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(POSTS_TABLE => 'p'),
				'ON'	=> 't.topic_first_post_id = p.post_id'
			),
			array(
				'FROM'	=> array(BUGS_COMPONENTS_TABLE => 'c'),
				'ON'	=> 'r.report_component = c.component_id'
			),
			array(
				'FROM'	=> array(BUGS_STATUSES_TABLE => 's'),
				'ON'	=> 'r.report_status = s.status_id'
			),
			array(
				'FROM'	=> array(BUGS_VERSIONS_TABLE => 'v'),
				'ON'	=> 'r.report_version = v.version_id'
			),
			array(
				'FROM'	=> array(USERS_TABLE => 'a'),
				'ON'	=> 'r.report_assigned = a.user_id'
			)
		),
		'WHERE'	=> "r.report_id = {$report_id} AND pr.project_name = '" . $db->sql_escape($project_name) . "'",
	));
	$result = $db->sql_query($sql);
	
	if (($report = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_REPORT', E_USER_NOTICE);
	}
	elseif (!$auth->acl_get('f_c_see', $report['forum_id']) || ($report['topic_approved'] == 0 && !$auth->acl_get('m_approve', $report['forum_id']) && $report['topic_poster'] != $user->data['user_id']))
	{
		http_status(403);
		trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
	}
	$db->sql_freeresult($result);
	
	// Often used variables
	$project_id = $report['project_id'];
	$forum_id = $report['forum_id'];
	$topic_id = $report['topic_id'];

	// Find out whether the user is watching the report
	if ($user->data['user_id'] != ANONYMOUS)
	{
		$sql = 'SELECT notify_status FROM ' . TOPICS_WATCH_TABLE . " WHERE topic_id = {$topic_id} AND user_id = {$user->data['user_id']}";
		$result = $db->sql_query($sql);
		$is_subscribed = $db->sql_fetchrow($result) != false;
		$db->sql_freeresult($result);
	}
	else
	{
		$is_subscribed = false;
	}

	/**
	* Actions (such as assigning, subscribing etc.)
	*/
	if ($action == 'subscribe' && !$is_subscribed && $user->data['user_id'] != ANONYMOUS && $auth->acl_get('f_subscribe', $forum_id))
	{
		$sql = 'INSERT INTO ' . TOPICS_WATCH_TABLE . " (user_id, topic_id, notify_status)
			VALUES ({$user->data['user_id']}, {$topic_id}, 1)";
		$db->sql_query($sql);
		$is_subscribed = true;
	}
	elseif ($action == 'unsubscribe' && $is_subscribed && $user->data['user_id'] != ANONYMOUS)
	{
		$sql = 'DELETE FROM ' . TOPICS_WATCH_TABLE . "
			WHERE user_id = {$user->data['user_id']} AND topic_id = {$topic_id}";
		$db->sql_query($sql);
		$is_subscribed = false;
	}
	elseif ($action == 'assign')
	{
		// Check form
		if (!check_form_key('bug_modify'))
		{
			trigger_error('FORM_INVALID');
		}
		
		include("{$phpbb_root_path}includes/functions_user.{$phpEx}");
		
		$user_id_ary = array();
		$usernames = array(request_var('assigned', '', true));
		user_get_id_name($user_id_ary, $usernames, array(USER_NORMAL, USER_FOUNDER));
		$new_assigned = (empty($user_id_ary)) ? 0 : $user_id_ary[0];
		
		if ($new_assigned == $report['assigned_id'])
		{
			trigger_error('CANNOT_REASSIGN_SAME', E_USER_NOTICE);
		}
	
		// Perform the action
		$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . ' SET report_assigned = ' . $new_assigned . ' WHERE report_id = ' . $report_id;
		$db->sql_query($sql);
		
		// Update the data queried before
		$old_assigned_name = $report['assigned_name'];
		$report['assigned_id'] = $new_assigned;
		$report['assigned_name'] = $usernames[0];
		if ($new_assigned != 0)
		{
			$sql = 'SELECT u.user_colour FROM ' . USERS_TABLE . ' u WHERE u.user_id = ' . $new_assigned;
			$result = $db->sql_query($sql);
			$assigned = $db->sql_fetchrow($result);
			$report['assigned_colour'] = $assigned['user_colour'];
		}
		else
		{
			$report['assigned_colour'] = '';
		}
		
		// Send out notifications
		$notif_users = get_subscribed_users($forum_id, $topic_id, array($new_assigned));
		
		send_notification($notif_users, 'bug_assigned', array(
			'REPORT_ID'		=> $report['report_id'],
			'REPORT_TITLE'	=> $report['report_title'],
			'OLD_ASSIGNED'	=> $old_assigned_name,
			'NEW_ASSIGNED'	=> $report['assigned_name'],
			'PROJECT_TITLE'	=> $report['project_title'],
			'U_REPORT'		=> generate_board_url() . '/' . $url_rewriter->rewrite("bugs.{$phpEx}", "mode=report&project={$report['project_name']}&report_id={$report_id}"),
			'PERFORMER'		=> $user->data['username'],
		));
	}
	elseif ($action == 'status')
	{
		$status_id = request_var('status', 0);
		
		// Check form
		if (!check_form_key('bug_modify'))
		{
			trigger_error('FORM_INVALID');
		}
		
		// Check if the status exists
		$sql = 'SELECT s.* FROM ' . BUGS_STATUSES_TABLE . ' s WHERE s.status_id = ' . $status_id;
		$result = $db->sql_query($sql);
		
		if (($status = $db->sql_fetchrow($result)) == false)
		{
			trigger_error('NO_STATUS', E_USER_NOTICE);
		}
		
		// Update the database
		$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . ' SET report_status = ' . $status_id . (($report['report_closed'] != $status['status_closed']) ? ', report_closed = ' . $status['status_closed'] : '');
		$db->sql_query($sql);
		
		$old_status_title = $report['status_title'];
		$old_closed = $report['report_closed'];
		$report['report_status'] = $status['status_id'];
		$report['status_title'] = $status['status_title'];
		$report['report_closed'] = $status['status_closed'];

		// Send out notifications
		$notif_users = get_subscribed_users($forum_id, $topic_id);
		
		send_notification($notif_users, 'bug_status_changed', array(
			'REPORT_ID'		=> $report['report_id'],
			'REPORT_TITLE'	=> $report['report_title'],
			'OLD_STATUS'	=> $old_status_title,
			'NEW_STATUS'	=> $report['status_title'],
			'IS_CLOSED'		=> ($report['report_closed'] == 1) ? $user->lang['YES'] : $user->lang['NO'],
			'PROJECT_TITLE'	=> $report['project_title'],
			'U_REPORT'		=> generate_board_url() . '/' . $url_rewriter->rewrite("bugs.{$phpEx}", "mode=report&project={$report['project_name']}&report_id={$report_id}"),
			'PERFORMER'		=> $user->data['username'],
		));
	}
	
	/**
	* Assign template variables
	*/
	$report['bbcode_options'] = (($report['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($report['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($report['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
	
	$template->assign_vars(array(
		'REPORT_TITLE'		=> $report['report_title'],
		'REPORT_TEXT'		=> generate_text_for_display($report['report_desc'], $report['bbcode_uid'], $report['bbcode_bitfield'], $report['bbcode_options']),
		'REPORT_ID'			=> $report['report_id'],
		'PROJECT_TITLE'		=> $report['project_title'],
		'REPORT_COMPONENT'	=> $report['component_title'],
		'REPORT_STATUS'		=> $report['status_title'],
		'REPORT_VERSION'	=> $report['version_title'],
		'ASSIGNED'			=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('username', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'ASSIGNED_COLOUR'	=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('colour', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'ASSIGNED_FULL'		=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('full', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'REPORTED'			=> get_username_string('username', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORTED_COLOUR'	=> get_username_string('colour', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORTED_FULL'		=> get_username_string('full', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORT_TIME'		=> $user->format_date($report['topic_time']),
		'S_IS_CLOSED'		=> $report['report_closed'] == 1,
	
		'U_BUG_TRACKER'		=> append_sid("{$phpbb_root_path}bugs.{$phpEx}"),
	
		'U_SUBSCRIBE'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=subscribe"),
		'U_UNSUBSCRIBE'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=unsubscribe"),
		'S_IS_SUBSCRIBED'		=> $is_subscribed,
		'U_EDIT'				=> (($auth->acl_get('f_c_edit', $forum_id) && $report['topic_poster'] == $user->data['user_id']) || $auth->acl_get('m_edit', $forum_id)) ? append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=edit&amp;project={$report['project_name']}&amp;report_id={$report_id}") : false,
		'U_ADD_REPLY'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=reply&amp;project={$report['project_name']}&amp;report_id={$report_id}"),
		'S_IS_LOCKED'			=> $report['topic_status'] == ITEM_LOCKED,
		'S_DISPLAY_REPLY_INFO'	=> $auth->acl_get('f_c_com_post', $forum_id),
		'S_DISPLAY_SUBSCRIBE_INFO'=> ($is_subscribed ||  $auth->acl_get('f_subscribe', $forum_id)),

		'REPORTED_IMG'		=> $user->img('icon_topic_reported', 'POST_REPORTED'),
		'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'POST_UNAPPROVED'),

		'S_DISPLAY_ASSIGN'	=> $auth->acl_get('m_c_manage', $forum_id) ? true : false,
		'U_ASSIGN_ACTION'	=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=assign"),
		'U_FIND_USERNAME'	=> append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=searchuser&amp;form=bug_assign&amp;field=form_assigned&amp;select_single=true"),	
		'S_DISPLAY_STATUS'	=> ($auth->acl_get('m_c_manage', $forum_id) || $report['assigned_id'] == $user->data['user_id']) ? true : false,
		'U_STATUS_ACTION'	=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=status"),
	));
	
	// Both forms use the same constant
	add_form_key('bug_modify');
	
	// Build the status box
	if ($auth->acl_get('m_c_manage', $forum_id) || $report['assigned_id'] == $user->data['user_id'])
	{
		// Query statuses
		$sql = 'SELECT s.* FROM ' . BUGS_STATUSES_TABLE . ' s ORDER BY s.status_closed ASC, s.status_title ASC';
		$result = $db->sql_query($sql);
		
		$s_status_options = '';
		while ($row = $db->sql_fetchrow($result))
		{
			$s_status_options .= '<option value="' . $row['status_id'] . '"' . (($row['status_id'] == $report['report_status']) ? ' selected="selected"' : '') . '>' . $row['status_title'] . '</option>';
		}
		$db->sql_freeresult($result);
		
		$template->assign_vars(array('S_STATUS_OPTIONS' => $s_status_options));
	}
	
	// Display sidemenu
	$sidemenu = new sidemenu();
	
	$sidemenu->add_block('REPORT_DETAILS');
	$sidemenu->add_kv_pair('REPORT_ID',		$report['report_id']);
	$sidemenu->add_kv_pair('PROJECT',		$report['project_title']);
	$sidemenu->add_kv_pair('VERSION',		$report['version_title']);
	$sidemenu->add_kv_pair('STATUS',		$report['status_title']);
	$sidemenu->add_kv_pair('COMPONENT',		$report['component_title']);
	$sidemenu->add_kv_pair('ASSIGNED_TO',	$report['assigned_id'] == 0 ? 'UNASSIGNED' : get_username_string('full', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']));
	$sidemenu->add_kv_pair('REPORTED_BY',	get_username_string('full', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']));
	$sidemenu->add_kv_pair('REPORTED_ON',	$user->format_date($report['topic_time']));
	
	$sidemenu->add_block('OPTIONS');
	if (($auth->acl_get('f_c_edit', $forum_id) && $report['topic_poster'] == $user->data['user_id']) || $auth->acl_get('m_edit', $forum_id))
	{
		$sidemenu->add_link('EDIT_REPORT', "{$phpbb_root_path}bugs.{$phpEx}", "mode=edit&amp;project={$report['project_name']}&amp;report_id={$report_id}");
	}
	if ($is_subscribed)
	{
		$sidemenu->add_link('UNSUBSCRIBE_REPORT', "{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=unsubscribe");
	}
	elseif ($auth->acl_get('f_subscribe', $forum_id))
	{
		$sidemenu->add_link('SUBSCRIBE_REPORT', "{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=subscribe");
	}
	
	// List comments
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'p.*, u.*, r.rank_title',
		'FROM'		=> array(POSTS_TABLE	=> 'p', USERS_TABLE => 'u'),
		'WHERE'		=> "p.topic_id = {$topic_id}
			AND p.post_id != {$report['post_id']}
			" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . '
			AND u.user_id = p.poster_id',
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(RANKS_TABLE => 'r'),
				'ON'	=> 'u.user_rank = r.rank_id AND r.rank_special = 1'
			)
		),
		'ORDER_BY'	=> 'p.post_time ' . ($user->data['user_post_sortby_dir'] == 'a' ? 'ASC' : 'DESC'),
	));
	$result = $db->sql_query($sql);

	$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);
	
	while ($row = $db->sql_fetchrow($result))
	{		
		$row['bbcode_options'] = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		
		$post_unread = (isset($topic_tracking_info[$topic_id]) && $row['post_time'] > $topic_tracking_info[$topic_id]) ? true : false;
		
		// @todo add edit option?
		$template->assign_block_vars('commentrow', array(
			'COMMENT_ID'		=> $row['post_id'],
			'U_MINI_POST'		=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}#comment-{$row['post_id']}"),
			'POST_SUBJECT'		=> $row['post_subject'],
			'POSTED_INFO'		=> sprintf($user->lang['POSTED_INFO'], get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], $row['post_username']), ($row['rank_title'] == '' ? '' : '(' . $row['rank_title'] . ')'), $user->format_date($row['post_time'])),
			'COMMENT_ID'		=> $row['post_id'],
			'POST_AUTHOR'		=> get_username_string('username', $row['user_id'], $row['username'], $row['user_colour'], $row['post_username']),
			'POST_AUTHOR_COLOUR'=> get_username_string('colour', $row['user_id'], $row['username'], $row['user_colour'], $row['post_username']),
			'POST_AUTHOR_FULL'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], $row['post_username']),
			'MINI_POST_IMG'		=> ($post_unread) ? $user->img('icon_post_target_unread', 'NEW_POST') : $user->img('icon_post_target', 'POST'),
			'MESSAGE'			=> generate_text_for_display($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']),
			'COMMENT_ID'		=> $row['post_id'],
			'COMMENT_ID'		=> $row['post_id'],
		
			'U_MCP_REPORT'		=> ($auth->acl_get('m_report', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=report_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
			'U_MCP_APPROVE'		=> ($auth->acl_get('m_approve', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=approve_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
		
			'S_POST_REPORTED'	=> ($row['post_reported'] == 1 && $auth->acl_get('m_report', $forum_id)) ? true : false,
			'S_POST_UNAPPROVED'	=> ($row['post_approved'] == 0) ? true : false,
		));
	}
	$db->sql_freeresult($result);
	
	// Mark comments read
	if (isset($topic_tracking_info[$topic_id]) && $report['topic_last_post_time'] > $topic_tracking_info[$topic_id] && $report['topic_last_post_time'] > $topic_tracking_info[$topic_id])
	{
		markread('topic', $forum_id, $topic_id, $report['topic_last_post_time']);
	
		// Update forum info
		update_forum_tracking_info($forum_id, $report['forum_last_post_time']);
	}

	
	// Finally display the page
	site_header(
		$user->lang['BUG_TRACKER'] . ' - ' . $report['report_title'],
		'bugs',
		array(array('bugs.' . $phpEx, 'BUG_TRACKER'), array("bugs.$phpEx?mode=project&amp;project={$report['project_name']}", $report['project_title']), array("bugs.$phpEx?mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}", sprintf($user->lang['BUG_NO'], $report_id)))
	);

	$template->set_filenames(array(
		'body' => 'bugs_report.html')
	);

	site_footer();
}

/**
* Add or edit report 
*/
elseif ($mode == 'add' || $mode == 'edit')
{
	$project_name = request_var('project', '');
	$report_id = request_var('report_id', 0);
	
	// Load language file
	$user->add_lang('posting');
	
	// Include files
	include("{$phpbb_root_path}includes/functions_user.{$phpEx}");
	include("{$phpbb_root_path}includes/functions_posting.{$phpEx}");
		
	// Check if project exists (also grab project and forum data)
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'p.*, f.forum_status, f.enable_indexing', // f.forum_desc AS project_description, f.forum_desc_uid, f.forum_desc_bitfield, f.forum_desc_options, f.forum_topics, f.forum_topics_real
		'FROM'		=> array(BUGS_PROJECTS_TABLE	=> 'p'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'p.forum_id = f.forum_id'
			)
		),
		'WHERE'	=> "p.project_name = '" . $db->sql_escape($project_name) . "'",
	));
	$result = $db->sql_query($sql);
	if (($project = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_PROJECT', E_USER_NOTICE);
	}
	elseif ($project['forum_status'] == ITEM_LOCKED && !$auth->acl_get('m_', $project['forum_id']))
	{
		trigger_error('FORUM_LOCKED', E_USER_NOTICE);
	}
	$db->sql_freeresult($result);
	
	// Introduce some new varibles for often used values
	$project_id = $project['project_id'];
	$forum_id = $project['forum_id'];
	
	// Check if user has the necessary permissions (only if adding new - for editing later)
	if ($mode == 'add' && !$auth->acl_get('f_c_post', $forum_id))
	{
		http_status(403);
		trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
	}
	
	/**
	* Get report data (either from the database or from the form or from both!) 
	*/
	// $report_data schema
	$report_data = array(
		// "Acutual" report data
		'report_id'			=> null,
		'topic_id'			=> null,
		'project_id'		=> $project['project_id'],
		'report_title'		=> null,
		'report_desc'		=> null,
		'enable_bbcode'		=> null, // Currently not implemented
		'enable_smilies'	=> null, // Currently not implemented
		'enable_magic_url'	=> null, // Currently not implemented
		'report_component'	=> null,
		'report_version'	=> null,
	
		// Forum data
		//'forum_id'			=> null,
	
		// Topic data
		'topic_poster'			=> $user->data['user_id'], // Set default to current user
		'topic_replies_real'	=> null,
		'topic_first_post_id'	=> null,
		'topic_last_post_id'	=> null,
	
		// Post data
		'post_id'			=> null,
		'poster_id'			=> $user->data['user_id'], // Set default to current user
		'post_time'			=> time(), // Set default to current time
		'post_edit_reason'	=> null,
		'post_edit_locked'	=> 0, // Set deafult value to false
	);

	// Load the to be edited report
	if ($mode == 'edit')
	{
		$sql = 'SELECT r.*, t.topic_poster, t.topic_replies_real, p.post_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.bbcode_uid, p.post_edit_reason, p.post_edit_locked
			FROM ' . BUGS_REPORTS_TABLE . ' r
			LEFT JOIN ' . TOPICS_TABLE . ' t
				ON r.topic_id = t.topic_id
			LEFT JOIN ' . POSTS_TABLE . ' p
				ON t.topic_first_post_id = p.post_id
			WHERE r.report_id = ' . $report_id;
		$result = $db->sql_query($sql);
		
		if (($report_db_data = $db->sql_fetchrow($result)) == false)
		{
			trigger_error('NO_REPORT', E_USER_NOTICE);
		}
		
		// Update report data while necessary the original schema
		$report_data = array_merge($report_data, $report_db_data);
		
		// Check if the user has the appropriate permissions
		if (($report_data['topic_poster'] != $user->data['user_id'] || !$auth->acl_get('f_c_edit', $forum_id)) && !$auth->acl_get('m_edit', $forum_id))
		{
			http_status(403);
			trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
		}
	}
	
	// Get data from the form
	if ($mode != 'edit' || isset($_POST['preview']) || isset($_POST['submit']))
	{
		$report_get_data = array(
			'report_title'		=> utf8_normalize_nfc(request_var('report_title', '', true)),
			'report_desc'		=> utf8_normalize_nfc(request_var('report_description', '', true)),
			'enable_bbcode'		=> 1, // isset($_POST['disable_bbcode']) ? 0 : 1,
			'enable_smilies'	=> 1, // isset($_POST['disable_smilies']) ? 0 : 1,
			'enable_magic_url'	=> 1, // isset($_POST['disable_magic_url']) ? 0 : 1,
			'report_component'	=> request_var('component_id', 0),
			'report_version'	=> request_var('version_id', 0),
		);
		$report_data = array_merge($report_data, $report_get_data);
	}
	// Get data entirely from the db (on edit)
	else
	{
		decode_message($report_data['report_desc'], $report_data['bbcode_uid']);
	}
	
	/**
	* Run some checks on the data submitted
	*/
	if (isset($_POST['submit']) || isset($_POST['preview']))
	{
		$error = array();
		
		// Version check
		$sql = 'SELECT v.version_title FROM ' . BUGS_VERSIONS_TABLE . ' v WHERE v.version_id = ' . $report_data['report_version'] . ' AND v.project_id = ' . $project_id . ' AND v.accept_new = 1';
		$result = $db->sql_query($sql);
		if (($version = $db->sql_fetchrow($result)) == false)
		{
			$error[] = 'NO_VERSION';
		}
		else
		{
			$report_data['version_title'] = $version['version_title'];
		}
		
		// Component check
		$sql = 'SELECT c.component_title FROM ' . BUGS_COMPONENTS_TABLE . ' c WHERE c.component_id = ' . $report_data['report_component'] . ' AND c.project_id = ' . $project_id;
		$result = $db->sql_query($sql);
		if (($component = $db->sql_fetchrow($result)) == false)
		{
			$error[] = 'NO_COMPONENT';
		}
		else
		{
			$report_data['component_title'] = $component['component_title'];
		}

		$error = array_merge($error, validate_data($report_data, array(
			'report_title'	=> array('string', false, 1, 60),
			'report_desc'	=> array('string', false),
		)));
		
		if (!$report_data['report_desc'] || !utf8_clean_string($report_data['report_desc']))
		{
			$error[] = 'NO_REPORT_DESCRIPTION';
		}
		
		$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);		
	}
	
	/**
	* Update the database
	*/
	if (isset($_POST['submit']) && !sizeof($error))
	{
		if (!check_form_key('add_report'))
		{
			trigger_error('FORM_INVALID');
		}
		
		// Parse text
		include("{$phpbb_root_path}includes/message_parser.{$phpEx}");
		
		$message_parser = new parse_message();
		$message_parser->message = $report_data['report_desc'];
		$message_parser->parse(true, $report_data['enable_magic_url'], $report_data['enable_smilies'], $auth->acl_get('f_img', $forum_id), $auth->acl_get('f_flash', $forum_id), true, true);
				
		// Just to make sure (also easier development; although not every database (or table) engine supports it)
		$db->sql_transaction('begin');
		
		// Insert into (our own) bug database (if adding: without the topic id for the time being)
		if ($mode == 'add')
		{
			$sql_ary = array(
				'project_id'		=> $project_id,
				'report_title'		=> $report_data['report_title'],
				'report_desc'		=> $message_parser->message,
				'report_component'	=> $report_data['report_component'],
				'report_version'	=> $report_data['report_version'],
				//'report_status'		=> 1, // This will make it (?) (should we use a database default value??)
			);
			$sql = 'INSERT INTO ' . BUGS_REPORTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			
			$report_data['report_id'] = $db->sql_nextid();
		}
		elseif ($mode == 'edit')
		{
			$sql_ary = array(
				'report_title'		=> $report_data['report_title'],
				'report_desc'		=> $message_parser->message,
				'report_component'	=> $report_data['report_component'],
				'report_version'	=> $report_data['report_version'],
			);
			$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE report_id = ' . $report_data['report_id'];
			$db->sql_query($sql);
		}
		
		// Generate post content
		$vars = array(
			'REPORT_TITLE'		=> $report_data['report_title'],
			'PROJECT_TITLE'		=> $project['project_title'],
			'COMPONENT_TITLE'	=> $report_data['component_title'],
			'VERSION_TITLE'		=> $report_data['version_title'],
			'REPORT_DESCRIPTION'=> $report_data['report_desc'],
			'U_REPORT'			=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&project={$project_name}&report_id={$report_data['report_id']}"),
		);
		$message = generate_content_post('bug_report', $vars);
		$message_md5 = md5($message);
		
		$message_parser->message = &$message;
		$message_parser->parse(true, $report_data['enable_magic_url'], $report_data['enable_smilies'], $auth->acl_get('f_img', $forum_id), $auth->acl_get('f_flash', $forum_id), true, true);
		
		/*if (!empty($message_parser->warn_msg))
		{
			trigger_error(implode('<br />', $message_parser->warn_msg), E_USER_NOTICE);
		}*/
		
		// Post the topic
		$data = array(
			'forum_id'			=> $forum_id,
			'topic_title'		=> $report_data['report_title'],
			'icon_id'			=> 0,
			'enable_bbcode'		=> 1,
			'enable_smilies'	=> $report_data['enable_smilies'],
			'enable_urls'		=> $report_data['enable_magic_url'],
			'enable_sig'		=> 0,
			'message'			=> $message_parser->message,
			'message_md5'		=> $message_md5,
			'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
			'bbcode_uid'		=> $message_parser->bbcode_uid,

			'post_edit_locked'	=> $report_data['post_edit_locked'],
			'enable_indexing'	=> $project['enable_indexing'],
			'notify'			=> false,// Don't know what this option does, but set it a way that it does nothing
			'notify_set'		=> false,
			'post_time'			=> $report_data['post_time'],
			'forum_name'		=> $project['project_title'],
		
			'post_edit_reason'		=> $report_data['post_edit_reason'],
			'topic_replies_real'	=> $report_data['topic_replies_real'],
			'poster_id'				=> $report_data['topic_poster'],
			'post_id'				=> $report_data['post_id'],
			'topic_id'				=> $report_data['topic_id'],
			'topic_poster'			=> $report_data['topic_poster'],
			'topic_first_post_id'	=> $report_data['topic_first_post_id'],
			'topic_last_post_id'	=> $report_data['topic_last_post_id'],
		);
		$poll = false;
		
		submit_post(($mode == 'add' ? 'post' : 'edit'), $report_data['report_title'], '', POST_NORMAL, $poll, $data);
		
		// Now update the report with the id of the topic
		if ($mode == 'add')
		{
			$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . ' SET topic_id = ' . $data['topic_id'] . ' WHERE report_id = ' . $report_data['report_id'];
			$db->sql_query($sql); 
		}
		
		// Subscribe to report
		// Handle it ourselves, beacuse this way we can prevent the forum system from sending forum(!) notifications (instead of site ones) - note the the notify_status default value of 1
		if (isset($_POST['subscribe_report']))
		{
			$sql = 'INSERT INTO ' . TOPICS_WATCH_TABLE . " (user_id, topic_id, notify_status)
				VALUES ({$user->data['user_id']}, {$data['topic_id']}, 1)";
			$db->sql_query($sql);
		}
		
		$db->sql_transaction('commit');
		
		// Give success messages
		$redirect_url = append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$project_name}&amp;report_id={$report_data['report_id']}");
		meta_refresh(3, $redirect_url);
		$message = sprintf($user->lang[($mode == 'add' ? 'REPORT_ADDED' : 'REPORT_UPDATED')], '<a href="' . $redirect_url . '">', '</a>');
		trigger_error($message);
	}
	
	/**
	* Generate preview
	*/
	elseif (isset($_POST['preview']) && !empty($report_data['report_desc']))
	{
		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$report_desc_parse = $report_data['report_desc'];
		generate_text_for_storage($report_desc_parse, $report_data['bbcode_uid'], $report_data['bbcode_bitfield'], $report_data['bbcode_options'], $report_data['enable_bbcode'], $report_data['enable_magic_url'], $report_data['enable_smilies']);
		
		$template->assign_vars(array(
			'PREVIEW_TEXT'	=> generate_text_for_display($report_desc_parse, $report_data['bbcode_uid'], $report_data['bbcode_bitfield'], $report_data['bbcode_options']),
			
			'S_PREVIEW'		=> true,
		));
	}
	
	/**
	* Display the form 
	*/
	add_form_key('add_report');
	
	// Get components
	$sql = 'SELECT c.component_id, c.component_title FROM ' . BUGS_COMPONENTS_TABLE . ' c WHERE c.project_id = ' . $project_id . ' ORDER BY c.component_title ASC';
	$result = $db->sql_query($sql, 7200);
	$s_component_options = '';
	while ($row = $db->sql_fetchrow($result))
	{
		$s_component_options .= '<option value="' . $row['component_id'] . '"' . (($row['component_id'] == $report_data['report_component']) ? ' selected="selected"' : '') . '>' . $row['component_title'] . '</option>';
	}
	$db->sql_freeresult($result);	

	// Get versions
	$sql = 'SELECT v.version_id, v.version_title FROM ' . BUGS_VERSIONS_TABLE . ' v WHERE v.project_id = ' . $project_id . ' AND accept_new = 1 ORDER BY v.version_id ASC';
	$result = $db->sql_query($sql, 7200);
	$s_version_options = '';
	while ($row = $db->sql_fetchrow($result))
	{
		$s_version_options .= '<option value="' . $row['version_id'] . '"' . (($row['version_id'] == $report_data['report_version']) ? ' selected="selected"' : '') . '>' . $row['version_title'] . '</option>';
	}
	$db->sql_freeresult($result);	
	
	$template->assign_vars(array(
		// Assign "input" variables
		'REPORT_TITLE'			=> $report_data['report_title'],
		'REPORT_DESCRIPTION'	=> $report_data['report_desc'],
		/*'S_BBCODE_CHECKED'		=> ($report_data['enable_bbcode']) ? '' : ' checked="checked"',
		'S_SMILIES_CHECKED'		=> ($report_data['enable_smilies']) ? '' : ' checked="checked"',
		'S_MAGIC_URL_CHECKED'	=> ($report_data['enable_magic_url']) ? '' : ' checked="checked"',*/
		'S_SUBSCRIBE_CHECKED'	=> (isset($_POST['subscribe_report']) || ($mode == 'add' && (!isset($_POST['submit']) && !isset($_POST['preview'])))) ? ' checked="checked"' : '',
	
		'PROJECT'				=> $project['project_title'],
		'U_ACTION'				=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", ($mode == 'add' ? "mode=add&amp;project={$project['project_name']}" : "mode=edit&amp;project={$project['project_name']}&amp;report_id={$report_id}")),
		'S_MODE'				=> $mode,
		'S_PREVIEW_BOLD'		=> ((isset($error) && sizeof($error)) || !isset($_POST['preview'])) ? true : false,
	
		'ERROR'					=> (isset($error) && sizeof($error)) ? implode('<br />', $error) : false,
	
		'S_COMPONENT_OPTIONS'	=> $s_component_options,
		'S_VERSION_OPTIONS'		=> $s_version_options,
		/*'S_BBCODE_ALLOWED'		=> $auth->acl_get('f_bbcode', $forum_id),
		'S_SMILIES_ALLOWED'		=> $auth->acl_get('f_smilies', $forum_id),
		'S_LINKS_ALLOWED'		=> ($config['allow_post_links']) ? true : false,*/
	
		'U_BUG_TRACKER'			=> append_sid($phpbb_root_path . 'bugs.' . $phpEx),

	));	
		
	// Display the page
	site_header(
		$user->lang['BUG_TRACKER'] . ' - ' . ($mode == 'add' ? $user->lang['ADD_REPORT'] : $user->lang['EDIT_REPORT']),
		'bugs',
		array(array('bugs.' . $phpEx, 'BUG_TRACKER'), array("bugs.$phpEx?mode=project&amp;project={$project['project_name']}", $project['project_title']), array("bugs.$phpEx?" . ($mode == 'add' ? "mode=add&amp;project={$project['project_name']}" : "mode=edit&amp;project={$project['project_name']}&amp;report_id={$report_id}"), ($mode == 'add' ? 'ADD_REPORT' : 'EDIT_REPORT')))
	);

	$template->set_filenames(array(
		'body' => 'bugs_add.html')
	);

	site_footer();
}

/**
* Add comment page 
*/
elseif ($mode == 'reply')
{
	$project_name = request_var('project', '');
	$report_id = request_var('report_id', 0);

	// Load language file
	$user->add_lang('posting');
	
	// Include files
	include("{$phpbb_root_path}includes/functions_posting.{$phpEx}");
	include("{$phpbb_root_path}includes/message_parser.{$phpEx}");
	
	// Query the report
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'r.*, pr.*, t.topic_approved, t.topic_poster, t.topic_time, t.topic_status, t.topic_type, t.topic_first_poster_name, t.topic_first_poster_colour, f.enable_indexing, p.post_id, p.enable_bbcode, p.enable_smilies, p.enable_magic_url, p.post_text, p.bbcode_bitfield, p.bbcode_uid, c.component_title, s.status_title, v.version_title, a.user_id AS assigned_id, a.username AS assigned_name, a.user_colour AS assigned_colour',
		'FROM'		=> array(BUGS_REPORTS_TABLE	=> 'r'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(BUGS_PROJECTS_TABLE => 'pr'),
				'ON'	=> 'r.project_id = pr.project_id'
			),
			array(
				'FROM'	=> array(TOPICS_TABLE => 't'),
				'ON'	=> 'r.topic_id = t.topic_id'
			),
			array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'pr.forum_id = f.forum_id'
			),
			array(
				'FROM'	=> array(POSTS_TABLE => 'p'),
				'ON'	=> 't.topic_first_post_id = p.post_id'
			),
			array(
				'FROM'	=> array(BUGS_COMPONENTS_TABLE => 'c'),
				'ON'	=> 'r.report_component = c.component_id'
			),
			array(
				'FROM'	=> array(BUGS_STATUSES_TABLE => 's'),
				'ON'	=> 'r.report_status = s.status_id'
			),
			array(
				'FROM'	=> array(BUGS_VERSIONS_TABLE => 'v'),
				'ON'	=> 'r.report_version = v.version_id'
			),
			array(
				'FROM'	=> array(USERS_TABLE => 'a'),
				'ON'	=> 'r.report_assigned = a.user_id'
			)
		),
		'WHERE'	=> "r.report_id = {$report_id} AND pr.project_name = '" . $db->sql_escape($project_name) . "'",
	));
	$result = $db->sql_query($sql);
	
	if (($report = $db->sql_fetchrow($result)) == false)
	{
		http_status(404);
		trigger_error('NO_REPORT', E_USER_NOTICE);
	}
	elseif (!$auth->acl_get('f_c_com_post', $report['forum_id']) || ($report['topic_approved'] == 0 && !$auth->acl_get('m_approve', $report['forum_id']) && $report['topic_poster'] != $user->data['user_id']))
	{
		http_status(403);
		trigger_error('NOT_AUTHORISED', E_USER_NOTICE);
	}
	elseif ($report['topic_status'] == ITEM_LOCKED && !$auth->acl_get('m_', $report['forum_id']))
	{
		trigger_error('TOPIC_LOCKED', E_USER_NOTICE);
	}
	$db->sql_freeresult($result);
	
	// Find out whether the user is watching the report
	if ($user->data['user_id'] != ANONYMOUS)
	{
		$sql = 'SELECT notify_status FROM ' . TOPICS_WATCH_TABLE . " WHERE topic_id = {$report['topic_id']} AND user_id = {$user->data['user_id']}";
		$result = $db->sql_query($sql);
		$is_subscribed = $db->sql_fetchrow($result) != false;
		$db->sql_freeresult($result);
	}
	else
	{
		$is_subscribed = false;
	}
	
	// Get submitted data
	$comment_data = array(
		'comment_subject'	=> utf8_normalize_nfc(request_var('comment_subject', '', true)),
		'comment_message'	=> utf8_normalize_nfc(request_var('comment_message', '', true)),
		'enable_bbcode'		=> (isset($_POST['disable_bbcode']) ? 0 : 1),
		'enable_smilies'	=> (isset($_POST['disable_smilies']) ? 0 : 1),
		'enable_magic_url'	=> (isset($_POST['disable_magic_url']) ? 0 : 1),
	);
	
	// Run checks
	if (isset($_POST['preview']) || isset($_POST['submit']))
	{

		$message_parser = new parse_message();
		$message_parser->message = &$comment_data['comment_message'];
		$message_md5 = md5($message_parser->message);
		$message_parser->parse($comment_data['enable_bbcode'], ($config['allow_post_links']) ? $comment_data['enable_magic_url'] : false, $comment_data['enable_smilies'], $auth->acl_get('f_img', $report['forum_id']), $auth->acl_get('f_flash', $report['forum_id']), true, $config['allow_post_links']);
		
		if (sizeof($message_parser->warn_msg))
		{
			$error = $message_parser->warn_msg;
		}
	}
	
	// Preview comment
	if (isset($_POST['preview']) && !empty($comment_data['comment_message']))
	{
		$template->assign_vars(array(
			'PREVIEW_TEXT'	=> $message_parser->format_display($comment_data['enable_bbcode'], $comment_data['enable_magic_url'], $comment_data['enable_smilies'], false),
			'S_PREVIEW'		=> true,
		));
	}
	
	// Post comment
	if (isset($_POST['submit']))
	{
		if (!check_form_key('add_comment'))
		{
			trigger_error('FORM_INVALID');
		}
	
		$poll = false;
		$data = array(
			'forum_id'			=> $report['forum_id'],
			'topic_id'			=> $report['topic_id'],
			'topic_title'		=> $report['report_title'],
			'icon_id'			=> 0,
			'post_time'			=> time(),
			'message'			=> $message_parser->message,
			'message_md5'		=> $message_md5,
			'bbcode_uid'		=> $message_parser->bbcode_uid,
			'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
			'enable_bbcode'		=> $comment_data['enable_bbcode'],
			'enable_smilies'	=> $comment_data['enable_smilies'],
			'enable_urls'		=> $comment_data['enable_magic_url'],
			'enable_sig'		=> 0,
			'post_edit_locked'	=> 0,
		
			'enable_indexing'	=> $report['enable_indexing'],
			'forum_name'		=> $report['project_title'],
			'notify'			=> false,
			'notify_set'		=> false,
		);
		
		submit_post('reply', $comment_data['comment_subject'], '', $report['topic_type'], $poll, $data);
		
		// Send out notifications
		$notif_users = get_subscribed_users($report['forum_id'], $report['topic_id']);
		
		send_notification($notif_users, 'bug_comment_added', array(
			'REPORT_ID'		=> $report['report_id'],
			'REPORT_TITLE'	=> $report['report_title'],
			'PROJECT_TITLE'	=> $report['project_title'],
			'U_REPORT'		=> generate_board_url() . '/' . $url_rewriter->rewrite("bugs.{$phpEx}", "mode=report&project={$report['project_name']}&report_id={$report_id}"),
			'U_COMMENT'		=> generate_board_url() . '/' . $url_rewriter->rewrite("bugs.{$phpEx}", "mode=report&project={$report['project_name']}&report_id={$report_id}") . '#comment-' . $data['post_id'],
			'PERFORMER'		=> $user->data['username'],
		));
		
		$redirect_url = append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$project_name}&amp;report_id={$report_id}#comment-{$data['post_id']}");
		meta_refresh(3, $redirect_url);
		$message = sprintf($user->lang['COMMENT_ADDED'], '<a href="' . $redirect_url . '">', '</a>');
		trigger_error($message);
	}
	
	// Display forms
	add_form_key('add_comment');
	
	if (isset($message_parser))
	{
		$comment_data['comment_message'] = $message_parser->decode_message($message_parser->bbcode_uid, false);
	}
	$report['bbcode_options'] = (($report['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($report['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($report['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);

	$template->assign_vars(array(
		// Form data
		'COMMENT_SUBJECT'		=> $comment_data['comment_subject'],
		'COMMENT_MESSAGE'		=> $comment_data['comment_message'],
		'S_BBCODE_CHECKED'		=> ($comment_data['enable_bbcode']) ? '' : ' checked="checked"',
		'S_SMILIES_CHECKED'		=> ($comment_data['enable_smilies']) ? '' : ' checked="checked"',
		'S_MAGIC_URL_CHECKED'	=> ($comment_data['enable_magic_url']) ? '' : ' checked="checked"',
		'S_BBCODE_ALLOWED'		=> $auth->acl_get('f_bbcode', $report['forum_id']),
		'S_SMILIES_ALLOWED'		=> $auth->acl_get('f_smilies', $report['forum_id']),
		'S_LINKS_ALLOWED'		=> ($config['allow_post_links']) ? true : false,
		'ERROR'					=> (isset($error) && sizeof($error)) ? implode('<br />', $error) : false,
	
		'S_PREVIEW_BOLD'		=> (!empty($error) || !isset($_POST['preview'])) ? true : false,
	
		// Report data
		'REPORT_TITLE'		=> $report['report_title'],
		'REPORT_TEXT'		=> generate_text_for_display($report['report_desc'], $report['bbcode_uid'], $report['bbcode_bitfield'], $report['bbcode_options']),
		'REPORT_ID'			=> $report['report_id'],
		'PROJECT_TITLE'		=> $report['project_title'],
		'REPORT_COMPONENT'	=> $report['component_title'],
		'REPORT_STATUS'		=> $report['status_title'],
		'REPORT_VERSION'	=> $report['version_title'],
		'ASSIGNED'			=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('username', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'ASSIGNED_COLOUR'	=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('colour', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'ASSIGNED_FULL'		=> $report['assigned_id'] == 0 ? $user->lang['UNASSIGNED'] : get_username_string('full', $report['assigned_id'], $report['assigned_name'], $report['assigned_colour']),
		'REPORTED'			=> get_username_string('username', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORTED_COLOUR'	=> get_username_string('colour', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORTED_FULL'		=> get_username_string('full', $report['topic_poster'], $report['topic_first_poster_name'], $report['topic_first_poster_colour']),
		'REPORT_TIME'		=> $user->format_date($report['topic_time']),
		'S_IS_CLOSED'		=> $report['report_closed'] == 1,
	
		'U_BUG_TRACKER'		=> append_sid("{$phpbb_root_path}bugs.{$phpEx}"),

		'U_RETURN'				=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}"),
		'U_SUBSCRIBE'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=subscribe"),
		'U_UNSUBSCRIBE'			=> append_sid("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}&amp;action=unsubscribe"),
		'S_IS_SUBSCRIBED'		=> $is_subscribed,
		'S_IS_LOCKED'			=> $report['topic_status'] == ITEM_LOCKED,
		'S_DISPLAY_SUBSCRIBE_INFO'=> ($is_subscribed ||  $auth->acl_get('f_subscribe', $report['forum_id'])),
	));
	
	// Display the page
	site_header(
		$user->lang['BUG_TRACKER'] . ' - ' . $report['report_title'],
		'bugs',
		array(array('bugs.' . $phpEx, 'BUG_TRACKER'), array("bugs.$phpEx?mode=project&amp;project={$report['project_name']}", $report['project_title']), array("{$phpbb_root_path}bugs.$phpEx?mode=report&amp;project={$report['project_name']}&amp;report_id={$report_id}", sprintf($user->lang['BUG_NO'], $report_id)), array("bugs.$phpEx?mode=reply&amp;project={$report['project_name']}&amp;report_id={$report_id}", 'ADD_COMMENT'))
	);

	$template->set_filenames(array(
		'body' => 'bugs_comment_add.html')
	);

	site_footer();
}

/**
* Projects display page 
*/
else
{
	// @todo Display open assigned and open own reports
	
	// Query projects
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT'	=> 'p.*, f.forum_desc AS project_description, f.forum_desc_uid, f.forum_desc_bitfield, f.forum_desc_options',
		'FROM'		=> array(BUGS_PROJECTS_TABLE	=> 'p'),
		'LEFT_JOIN'		=> array(
			array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'p.forum_id = f.forum_id'
			)
		),
		'ORDER_BY'	=> 'f.left_id ASC',
	));
	
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		if ($auth->acl_get('f_c_see', $row['forum_id']))
		{
			$template->assign_block_vars('projects', array(
				'PROJECT_TITLE'			=> $row['project_title'],
				'PROJECT_DESCRIPTION'	=> generate_text_for_display($row['project_description'], $row['forum_desc_uid'], $row['forum_desc_bitfield'], $row['forum_desc_options']),
				'PROJECT_URL'			=> append_sid($phpbb_root_path . 'bugs.' . $phpEx, 'mode=project&amp;project=' . urlencode($row['project_name'])),
			));
		}
	}
	$db->sql_freeresult($result);

	// Assign some vars
	$template->assign_vars(array(
		'U_BUG_TRACKER'	=> append_sid($phpbb_root_path . 'bugs.' . $phpEx),
	));

	// Output page
	site_header($user->lang['BUG_TRACKER'], 'bugs', array(array('bugs.' . $phpEx, 'BUG_TRACKER')));

	$template->set_filenames(array(
		'body' => 'bugs_index.html')
	);

	site_footer();
}
?>