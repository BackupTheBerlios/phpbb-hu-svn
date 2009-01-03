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
* This is the convertor of the phpBB2 knowledge base to our new own knowledge base
* This convertor does not convert everything!
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
	trigger_error('This is not meant for you.', E_USER_WARNING);
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

<title>ÃtmutatÃ³k konvertÃ¡lÃ¡sa</title>

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

	<h1>ÃtmutatÃ³k konvertÃ¡lÃ¡sa</h1>

	<br />
<?php 

// Connect to the old databse
$olddb = new $sql_db();
$olddb->sql_connect($olddb_host, $olddb_user, $olddb_pwd, $olddb_db, $olddb_port, false, false);

/**
* Truncate tables
*/
$tables = array(
	KB_ARTICLES_TABLE,
);
foreach ($tables as $table_name)
{
	$db->sql_query('TRUNCATE TABLE ' . $table_name);
	
	// Looks like on certain systems we have to reset the auto increment value manually :/
	@$db->sql_query('ALTER TABLE `' . $table_name . '` AUTO_INCREMENT = 1');
}



// Do not let the conversion take place if there has been any error
$db->sql_transaction('begin');

// Get the id of the version tags
$sql = 'SELECT t.tag_id
	FROM ' . TAGS_TABLE . ' t, ' . TAGCATS_TABLE . ' tc
	WHERE t.tagcat_id = tc.tagcat_id
		AND tc.tagcat_name = \'verzio\'
		AND tc.tagcat_module = ' . TAG_KB . '
	ORDER BY t.tag_name';
$result = $db->sql_query($sql);

$tag_phpbb2 = @array_shift($db->sql_fetchrow($result));
$tag_phpbb3 = @array_shift($db->sql_fetchrow($result));

if (is_null($tag_phpbb3))
{
	die('Error: The version tags haven\'t been created yet.');
}

// Query forum details
$sql = 'SELECT forum_name, enable_indexing
	FROM ' . FORUMS_TABLE . '
	WHERE forum_id = ' . KB_FORUM_ID;
$result = $db->sql_query($sql);
$forum_data = $db->sql_fetchrow($result);
$db->sql_freeresult();

// Query tags and group them by category id
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

/**
* Transfer articles
*/
$sql = 'SELECT * FROM phpbb_kb_articles ORDER BY article_id';
$result = $olddb->sql_query($sql);

include_once("{$phpbb_root_path}includes/functions_posting.$phpEx");
include_once("{$phpbb_root_path}includes/message_parser.{$phpEx}");

include_once("{$phpbb_root_path}install/convertors/functions_phpbb20.{$phpEx}");

// Also store some report/topic data for later use
//$reports = array();

while ($row = $olddb->sql_fetchrow($result))
{
	//decode_message($row['article_body'], $row['bbcode_uid']);
	
	$row['article_name'] = generate_name_from_title($row['article_title']);

	$message = $row['article_body'];
	$row['article_body'] = &$message;
	
	// Adjust size...
	if (strpos($message, '[size=') !== false)
	{
		$message = preg_replace_callback('/\[size=(\d*):(' . $row['bbcode_uid'] . ')\]/', 'phpbb_replace_size', $message);
	}

	$message = preg_replace('/\:(([a-z0-9]:)?)' . $row['bbcode_uid'] . '/s', '', $message);
	
	if (strpos($message, '[quote=') !== false)
	{
		$message = preg_replace('/\[quote="(.*?)"\]/s', '[quote=&quot;\1&quot;]', $message);
		$message = preg_replace('/\[quote=\\\"(.*?)\\\"\]/s', '[quote=&quot;\1&quot;]', $message);
		
		// let's hope that this solves more problems than it causes. Deal with escaped quotes.
		$message = str_replace('\"', '&quot;', $message);
		$message = str_replace('\&quot;', '&quot;', $message);
	}
	
	$message = str_replace('<', '&lt;', $message);
	$message = str_replace('>', '&gt;', $message);
	$message = str_replace('<br />', "\n", $message);
		
	
	// Convert abandoned [surl=] tags
	$row['article_body'] = str_replace('[surl=', '[url=' . generate_board_url() . '/', $row['article_body']);
		
	
	
	$message_parser = new parse_message();
	$message_parser->message = $row['article_body'];
	$message_parser->parse(true, true, true, true, true, true, true);

	$article_content_parsed = $message_parser->message;	
	
	// Define tags
	$tags = array();
	if ($row['article_version'] == 1 || $row['article_version'] == 2)
	{
		$tags[] = $tag_phpbb2;
	}
	if ($row['article_version'] == 1 || $row['article_version'] == 3)
	{
		$tags[] = $tag_phpbb3;
	}

	/**
	* Generate post content
	*/
	$vars = array(
		'ARTICLE_TITLE'		=> $row['article_title'],
		'ARTICLE_DESC'		=> $row['article_description'],
		'ARTICLE_CONTENT'	=> $row['article_body'],
		'ARTICLE_TAGS'		=> generate_tags_bbcode_list($tags, $tagcats, "{$phpbb_root_path}kb.{$phpEx}?mode=tag&cat=%1\$s&name=%2\$s"),
		'U_ARTICLE'			=> generate_board_url() . '/' . $url_rewriter->rewrite("{$phpbb_root_path}kb.{$phpEx}", "mode=article&name={$row['article_name']}"),
	);
	$message = generate_content_post('kb_article', $vars);
	$message_md5 = md5($message);
	
	$message_parser->message = &$message;
	$message_parser->parse(true, true, true, true, true, true, true);
	
	/*if (!empty($message_parser->warn_msg))
	{
		trigger_error(implode('<br />', $message_parser->warn_msg), E_USER_NOTICE);
	}*/
	
	// Initialize variables
	$row['post_id'] = 0;
	$row['topic_id'] = 0;
	
	$data = array(
		'forum_id'			=> KB_FORUM_ID,
		'topic_title'		=> $row['article_title'],
		'icon_id'			=> 0,
		'enable_bbcode'		=> 1,
		'enable_smilies'	=> 1,
		'enable_urls'		=> 1,
		'enable_sig'		=> 0,
		'message'			=> $message_parser->message,
		'message_md5'		=> $message_md5,
		'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
		'bbcode_uid'		=> $message_parser->bbcode_uid,

		'post_edit_locked'	=> 0,
		'enable_indexing'	=> $forum_data['enable_indexing'],
		'notify'			=> false,
		'notify_set'		=> '',
		'post_time'			=> $row['article_date'],
		'forum_name'		=> $forum_data['forum_name'],
	
		'post_edit_reason'		=> '',
		'topic_replies_real'	=> 0,
		'poster_id'				=> $row['article_author_id'],
		'post_id'				=> &$row['post_id'],
		'topic_id'				=> &$row['topic_id'],
		'topic_poster'			=> $row['article_author_id'],
	);
	$poll = false;
	
	submit_post('post', $row['article_title'], '', POST_NORMAL, $poll, $data);
	
	$sql = 'UPDATE ' . POSTS_TABLE . ' SET poster_id = ' . $row['article_author_id'] . ', post_time = ' . $row['article_date'] . ' WHERE post_id = ' . $data['post_id']; 
	$db->sql_query($sql);
	
	$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_views = ' . $row['views'] . ' WHERE topic_id = ' . $row['topic_id']; 
	$db->sql_query($sql);
	
	/**
	* Insert into our own custom database
	*/
	$sql_ary = array(
		'topic_id'			=> $row['topic_id'],
		'article_name'		=> $row['article_name'],
		'article_desc'		=> $row['article_description'],
		'article_content'	=> $article_content_parsed,
	);
	
	$sql = 'INSERT INTO ' . KB_ARTICLES_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
	$db->sql_query($sql);
	
	$article_data['article_id'] = $db->sql_nextid();

	/**
	* Store assigned tags
	*/
	// Generate array to be inserted to the database
	$sql_insert_ary = array();
	foreach ($tags as $tag_id)
	{
		$sql_insert_ary[] = array(
			'topic_id'	=> $row['topic_id'],
			'tag_id'	=> $tag_id,
		);
	}
	
	$db->sql_multi_insert(TAGMATCH_TABLE, $sql_insert_ary);	
}

print '<p><strong>ÃtmutatÃ³k</strong> Ã¡thozva.</p>';
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
<?php 

// Generate id name from a title
function generate_name_from_title($title)
{
	return strtolower(str_replace(array(',', '?', '!', '(', ')'), '', str_replace(array('Ã¡','Ã©','Ã­','Ã³','Ã¶','Å','Ãº','Ã¼','Å±','Ã','Ã','Ã','Ã','Ã','Å','Ã','Ã','Å°', ' '), array('a','e','i','o','o','o','u','u','u','a','e','i','o','o','o','u','u','u','-'), $title)));
}
?>