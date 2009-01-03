<?php
/** 
*
* @package install
* @version $Id$
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* This is the convertor of our own bug tracker to the new one
* 
* We couldn't use the convertor framework as we are not converting board data
* and we need to be able to use some of the built-in functions of phpBB such as topic posting.
* 
* @ignore
*/
/**
* Configuration data
*/
$olddb_host = 'localhost';
$olddb_db = 'phpbb-oldal';
$olddb_port = '';
$olddb_user = 'root';
$olddb_pwd = '';

/* For the real site */

$olddb_host = 'localhost';
$olddb_db = 'phpbb_oldal';
$olddb_port = '';
$olddb_user = 'phpbb_oldal';
$olddb_pwd = 'olympus';
/**/



/**
* Code begins
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();


// Only an admin (a founder) can run this script
if ($user->data['user_type'] != USER_FOUNDER)
{
	trigger_error('This is not for you.', E_USER_WARNING);
}

// Begin outputing the page
header('Content-type: text/html; charset=UTF-8');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo $user->lang['DIRECTION']; ?>" lang="<?php echo $user->lang['USER_LANG']; ?>" xml:lang="<?php echo $user->lang['USER_LANG']; ?>">
<head>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-language" content="<?php echo $user->lang['USER_LANG']; ?>" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="imagetoolbar" content="no" />

<title>HibajelentÅ adatok konvertÃ¡lÃ¡sa</title>

<link href="../adm/style/admin.css" rel="stylesheet" type="text/css" media="screen" />

</head>

<body>
<div id="wrap">
	<div id="page-header">&nbsp;</div>

	<div id="page-body">
		<div id="acp">
		<div class="panel">
			<span class="corners-top"><span></span></span>
				<div id="content">
					<div id="main">

	<h1>HibajelentÅ adatainak konvertÃ¡lÃ¡sa</h1>
	
	<!-- <h2>Kezdeti lÃ©pÃ©sek</h2> -->

	<br />
<?php 

// Connect to the old databse
$olddb = new $sql_db();
$olddb->sql_connect($olddb_host, $olddb_user, $olddb_pwd, $olddb_db, $olddb_port, false, false);

/**
* Truncate tables
*/
$tables = array(
	BUGS_COMPONENTS_TABLE,
	BUGS_PROJECTS_TABLE,
	BUGS_REPORTS_TABLE,
	BUGS_STATUSES_TABLE,
	BUGS_VERSIONS_TABLE,
);
foreach ($tables as $table_name)
{
	$db->sql_query('TRUNCATE TABLE ' . $table_name);
	
	// Looks like on certain systems we have to reset the auto increment value manually :|
	@$db->sql_query('ALTER TABLE `' . $table_name . '` AUTO_INCREMENT = 1');
}



// Only do it if everything goes well
			$db->sql_transaction('begin');

/**
* First convert the more or less "static" data such as statuses, components and versions
*/

/**
* Convert statuses
*/
$sql = 'SELECT * FROM phpbb_bugs_statuses';
$result = $olddb->sql_query($sql);

$insert_sql_array = $olddb->sql_fetchrowset($result);
$db->sql_multi_insert(BUGS_STATUSES_TABLE, $insert_sql_array);

print '<p><strong>StÃ¡tuszok</strong> Ã¡thozva.</p>';
flush();


/**
* Convert components
*/
$sql = 'SELECT * FROM phpbb_bugs_components';
$result = $olddb->sql_query($sql);

$insert_sql_array = $olddb->sql_fetchrowset($result);
$db->sql_multi_insert(BUGS_COMPONENTS_TABLE, $insert_sql_array);

print '<p><strong>Komponensek</strong> Ã¡thozva.</p>';
flush();


/**
* Convert versions
*/
$sql = 'SELECT * FROM phpbb_bugs_versions';
$result = $olddb->sql_query($sql);

$insert_sql_array = $olddb->sql_fetchrowset($result);
$db->sql_multi_insert(BUGS_VERSIONS_TABLE, $insert_sql_array);

print '<p><strong>VerziÃ³k</strong> Ã¡thozva.</p>';
flush();


/**
* Transfer projects
* 
* Assume project ids are totally following the rules (1, 2, 3 etc.)
*/
$sql = 'SELECT * FROM phpbb_bugs_projects';
$result2 = $olddb->sql_query($sql);

include("{$phpbb_root_path}includes/acp/acp_forums.$phpEx");
include("{$phpbb_root_path}/includes/acp/acp_bugs.{$phpEx}");
$acp_module = new acp_bugs();
$acp_module->parent = new acp_forums();

// Also grab here some project data for later use in the convertor
$projects = array();

while ($row2 = $olddb->sql_fetchrow($result2))
{
	/**
	* Create the forum and the project entries all at once
	*/
	$forum_data = array(
		'parent_id'				=> BUGS_FORUM_ID,
		'forum_type'			=> FORUM_POST,
		'type_action'			=> '',
		'forum_status'			=> ITEM_UNLOCKED,
		'forum_parents'			=> '',
		'forum_name'			=> utf8_normalize_nfc($row2['project_title']),
		'project_idname'		=> $row2['project_name'],
		'forum_link'			=> '',
		'forum_link_track'		=> false,
		'forum_desc'			=> '',
		'forum_desc_uid'		=> '',
		'forum_desc_options'	=> 7,
		'forum_desc_bitfield'	=> '',
		'forum_rules'			=> '',
		'forum_rules_uid'		=> '',
		'forum_rules_options'	=> 7,
		'forum_rules_bitfield'	=> '',
		'forum_rules_link'		=> '',
		'forum_image'			=> '',
		'forum_style'			=> 0,
		'display_on_index'		=> true,
		'forum_topics_per_page'	=> 0, 
		'enable_indexing'		=> true, 
		'enable_icons'			=> false,
		'enable_prune'			=> false,
		'enable_post_review'	=> true,
		'prune_days'			=> 7,
		'prune_viewed'			=> 7,
		'prune_freq'			=> 1,
		'prune_old_polls'		=> false,
		'prune_announce'		=> false,
		'prune_sticky'			=> false,
		'forum_password'		=> '',
		'forum_password_confirm'=> '',
		'forum_password_unset'		=> '',
	
		'show_active'			=> false,
	);
	
	$errors = $acp_module->update_forum_data($forum_data);
	
	/**
	* Also add permissions (inherit from the bug tracker container forum)
	*/
	if (!sizeof($errors))
	{
		$forum_perm_from = BUGS_FORUM_ID;

		// Copy permissions?
		if ($forum_perm_from && !empty($forum_perm_from) && $forum_perm_from != $forum_data['forum_id'])
		{
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
	}
	
	$projects[$row2['project_id']] = array(
		'project_id'	=> $row2['project_id'],
		'project_name'	=> $row2['project_name'],
		'project_title'	=> $row2['project_title'],
		'forum_id'		=> $forum_data['forum_id'],
	);
}
print '<p><strong>Projektek</strong> Ã¡thozva.</p>';
flush();


/**
* Transfer reports
*/
$sql = $olddb->sql_build_query('SELECT', array(
	'SELECT'	=> 'r.*, c.component_title, v.version_title',
	'FROM'		=> array('phpbb_bugs_reports'	=> 'r'),
	'LEFT_JOIN'		=> array(
		array(
			'FROM'	=> array('phpbb_bugs_components' => 'c'),
			'ON'	=> 'r.bug_component = c.component_id'
		),
		array(
			'FROM'	=> array('phpbb_bugs_versions' => 'v'),
			'ON'	=> 'r.bug_version = v.version_id'
		)
	),
));
$result = $olddb->sql_query($sql);

include_once("{$phpbb_root_path}includes/functions_posting.$phpEx");
include_once("{$phpbb_root_path}includes/message_parser.{$phpEx}");

// Also store some report/topic data for later use
$reports = array();

while ($row = $olddb->sql_fetchrow($result))
{
	decode_message($row['bug_description'], $row['bug_bbcode_uid']);
	
	// Merge report description, wrong text and suggested text fields
	$row['bug_description'] .= "\n";
	decode_message($row['bug_wrong_text'], $row['bug_bbcode_uid']);
	decode_message($row['bug_suggested_text'], $row['bug_bbcode_uid']);
	if (!empty($row['bug_wrong_text']))
	{
		$row['bug_description'] .= "\n[b]HibÃ¡s szÃ¶veg:[/b][quote]{$row['bug_wrong_text']}[/quote]";
	}
	if (!empty($row['bug_suggested_text']))
	{
		$row['bug_description'] .= "\n[b]Javasolt szÃ¶veg:[/b][quote]{$row['bug_suggested_text']}[/quote]";
	}
	
	$report_data = array(
		// "Acutual" report data
		'report_id'			=> $row['bug_id'],
		'topic_id'			=> null,
		'project_id'		=> $row['project_id'],
		'report_title'		=> $row['bug_title'],
		'report_desc'		=> $row['bug_description'],
		'enable_bbcode'		=> 1,
		'enable_smilies'	=> 1,
		'enable_magic_url'	=> 1,
		'report_component'	=> $row['bug_component'],
		'report_version'	=> $row['bug_version'],
		'report_status'		=> $row['bug_status'],
		'report_assigned'	=> $row['assigned_to'],
		'report_closed'		=> $row['bug_closed'] != 0 ? 1 : 0,
	
		// Forum data
		//'forum_id'			=> null,
	
		// Topic data
		'topic_poster'			=> $row['bug_reporter'],
		'topic_replies_real'	=> null,
		'topic_first_post_id'	=> null,
		'topic_last_post_id'	=> null,
	
		// Post data
		'post_id'			=> null,
		'poster_id'			=> $row['bug_reporter'],
		'post_time'			=> $row['bug_time'],
		'post_edit_reason'	=> null,
		'post_edit_locked'	=> 0,
	);
	
	// Parse text
	$message_parser = new parse_message();
	$message_parser->message = $report_data['report_desc'];
	$message_parser->parse(true, $report_data['enable_magic_url'], $report_data['enable_smilies'], true, true, true, true);

	// Just to make sure (easier development; although not every database (or table) engine support it )
	$db->sql_transaction('begin');
	
	// Insert into (our own) bug database (if adding: without the topic id for the time being)
	$sql_ary = array(
		'project_id'		=> $report_data['project_id'],
		'report_id'			=> $report_data['report_id'], // Only here
		'report_title'		=> $report_data['report_title'],
		'report_desc'		=> $message_parser->message,
		'report_component'	=> $report_data['report_component'],
		'report_version'	=> $report_data['report_version'],
		'report_status'		=> $report_data['report_status'],
		'report_assigned'	=> $report_data['report_assigned'],
		'report_closed'		=> $report_data['report_closed'],
	);
	$sql = 'INSERT INTO ' . BUGS_REPORTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
	$db->sql_query($sql);
	
	$report_data['report_id'] = $db->sql_nextid();

	// Generate post content
	$vars = array(
		'REPORT_TITLE'		=> $report_data['report_title'],
		'PROJECT_TITLE'		=> $projects[$row['project_id']]['project_title'],
		'COMPONENT_TITLE'	=> $row['component_title'],
		'VERSION_TITLE'		=> $row['version_title'],
		'REPORT_DESCRIPTION'=> $report_data['report_desc'],
		'U_REPORT'			=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}bugs.{$phpEx}", "mode=report&project={$projects[$row['project_id']]['project_name']}&report_id={$report_data['report_id']}"),
	);
	$message = generate_content_post('bug_report', $vars);
	$message_md5 = md5($message);
	
	$message_parser->message = &$message;
	$message_parser->parse(true, $report_data['enable_magic_url'], $report_data['enable_smilies'], true, true, true, true);
	
	// Post the topic
	$data = array(
		'forum_id'			=> $projects[$row['project_id']]['forum_id'],
		'topic_title'		=> $report_data['report_title'],
		'icon_id'			=> 0,
		'enable_bbcode'		=> 1,
		'enable_smilies'	=> 1,
		'enable_urls'		=> 1,
		'enable_sig'		=> 0,
		'message'			=> $message_parser->message,
		'message_md5'		=> $message_md5,
		'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
		'bbcode_uid'		=> $message_parser->bbcode_uid,

		'post_edit_locked'	=> $report_data['post_edit_locked'],
		'enable_indexing'	=> 4,
		'notify'			=> false,// Don't know what this option does, but set it a way that it does nothing
		'notify_set'		=> false,
		'post_time'			=> $report_data['post_time'],
		'forum_name'		=> $projects[$row['project_id']]['project_title'],
	
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
	
	submit_post('post', $report_data['report_title'], '', POST_NORMAL, $poll, $data);

	/*$data['topic_time'] = $report_data['post_time'];
	$data['post_time'] = $report_data['post_time'];
	$data['topic_poster'] = $report_data['topic_poster'];
	$data['poster_id'] = $report_data['topic_poster'];
	
	submit_post('edit', $report_data['report_title'], '', POST_NORMAL, $poll, $data);*/

	$sql = 'UPDATE ' . POSTS_TABLE . ' SET poster_id = ' . $row['bug_reporter'] . ', post_time = ' . $row['bug_time'] . ' WHERE post_id = ' . $data['post_id']; 
	$db->sql_query($sql);
	
	// Now update the report with the id of the topic
	$sql = 'UPDATE ' . BUGS_REPORTS_TABLE . ' SET topic_id = ' . $data['topic_id'] . ' WHERE report_id = ' . $report_data['report_id'];
	$db->sql_query($sql); 
	
	$db->sql_transaction('commit');
	
	$reports[$row['bug_id']] = array(
		'report_id'		=> $row['bug_id'],
		'topic_id'		=> $data['topic_id'],
		'forum_id'		=> $data['forum_id'],
		'topic_title'	=> $data['topic_title'],
		'project_id'	=> $row['project_id'],
	);
}

print '<p><strong>JelentÃ©sek</strong> Ã¡thozva.</p>';
flush();


/**
* Transfer comments 
*/
$sql = 'SELECT * FROM phpbb_bugs_comments';
$result = $olddb->sql_query($sql);

while ($row = $olddb->sql_fetchrow($result))
{
	decode_message($row['comment_text'], $row['comment_bbcode_uid']);
	$md5_message= md5($row['comment_text']);
	
	$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
	$allow_bbcode = $allow_urls = $allow_smilies = true;
	generate_text_for_storage($row['comment_text'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	
	$poll = false;
	$data = array(
		'forum_id'			=> $reports[$row['bug_id']]['forum_id'],
		'topic_id'			=> $reports[$row['bug_id']]['topic_id'],
		'topic_title'		=> $reports[$row['bug_id']]['topic_title'],
		'icon_id'			=> 0,
		'post_time'			=> $row['comment_time'],
		'message'			=> $row['comment_text'],
		'message_md5'		=> $message_md5,
		'bbcode_uid'		=> $uid,
		'bbcode_bitfield'	=> $bitfield,
		'enable_bbcode'		=> true,
		'enable_smilies'	=> true,
		'enable_urls'		=> true,
		'enable_sig'		=> 0,
		'post_edit_locked'	=> 0,
		'poster_id'			=> $row['comment_author'],
	
		'enable_indexing'	=> true,
		'forum_name'		=> $projects[$reports[$row['bug_id']]['project_id']]['project_title'],
		'notify'			=> false,
		'notify_set'		=> false,
	);
	
	submit_post('reply', $row['comment_title'], '', POST_NORMAL, $poll, $data);

	/*$sql = 'SELECT p.post_id, t.topic_replies_real, t.topic_first_post_id, t.topic_last_post_id FROM ' . POSTS_TABLE . ' p 
		LEFT JOIN ' . TOPICS_TABLE . ' t  ON p.topic_id = t.topic_id
		WHERE p.post_id = ' . $data['post_id'];
	$result53 = $db->sql_query($sql);
	$post = $db->sql_fetchrow($result53);
		
	$data['topic_time'] = $row['comment_time'];
	$data['post_time'] = $row['comment_time'];
	$data['topic_poster'] = $row['comment_author'];
	$data['poster_id'] = $row['comment_author'];
	$data['post_edit_reason'] = '';
	$data['topic_replies_real'] = $post['topic_replies_real'];
	$data['topic_first_post_id'] = $post['topic_first_post_id'];
	$data['topic_last_post_id'] = $post['topic_last_post_id'];*/
	
	$sql = 'UPDATE ' . POSTS_TABLE . ' SET poster_id = ' . $row['comment_author'] . ', post_time = ' . $row['comment_time'] . ' WHERE post_id = ' . $data['post_id']; 
	$db->sql_query($sql);
	/*$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_last_poster_id = ' . $row['comment_author'] . ', post_time = ' . $row['commen_time'] . ' WHERE post_id = ' . $data['post_id']; 		
	$db->sql_query($sql);*/
	//submit_post('edit', $row['comment_title'], '', POST_NORMAL, $poll, $data);
}

print '<p><strong>HozzÃ¡szÃ³lÃ¡sok</strong> Ã¡thozva.</p>';
flush();



// Let the conversion actually happen
$db->sql_transaction('commit');


?>
<br /><br />
<p><strong>A konvertÃ¡lÃ¡s sikeresen befejezÅdÃ¶tt. Ne feletsd el ÃºjraszinkronizÃ¡lni az Ã©rintett fÃ³rumokat!</strong></p>
					</div>
				</div>
			<span class="corners-bottom"><span></span></span>
		</div>
		</div>
	</div>

	<div id="page-footer">
		Powered by phpBB &copy; 2000, 2002, 2005, 2007 <a href="http://www.phpbb.com/">phpBB Group</a>
	</div>
</div>

</body>
</html>