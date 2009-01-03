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
* Indicate the correct HTTP status
*/
function http_status($code)
{
	$statuses = array(
		'200' => 'OK',
		'300' => 'Multiple',
		'301' => 'Moved Permanently',
		'302' => 'Moved Temporarily',
		'304' => 'Not Modified',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		);

	if (!isset($statuses[$code]))
	{
		return false;
	}

	$status = $statuses[$code];
	header("HTTP/1.1 {$code} {$status}");
	header("Status: {$code} {$status}", true, $code);
}

/**
* Make a HTTP redirect
*/
function http_redirect($location, $permanent = true)
{
	header('Location: ' . generate_board_url() . '/' . $location);
	http_status($permanent ? 301 : 302);
}

/**
 * Sends out a site notification
 *
 * @param array		$users		array of user ids; either simple user id list or user_id => array('uid' => 0, 'name' => '', 'email' => 'a@a.a', 'type' => 0, 'lang' => 'hu')
 * @param string	$template	template file to use (email/notifs/)
 * @param array		$vars		data to be included in the mail
 */
function send_notification($users, $template_file, $vars = array())
{
	global $db, $user, $phpbb_root_path, $phpEx;

	/**
	* Get the users
	*/
	// Loop over the users array, if deatils are not given query the user data
	$to_query = array();
	$get_users = $users;
	$users = array();
	foreach ($get_users as $key => $value)
	{
		// All the deatils are given
		if (!is_array($value))
		{
			$to_query[] = $value;
		}
		else
		{
			$users[(isset($value['uid']) ? $value['uid'] : $key)] = array(
				'uid'	=> isset($value['uid']) ? $value['uid'] : (isset($value['user_id']) ? $value['user_id'] : $key),
				'name'	=> isset($value['name']) ? $value['name'] : $value['username'],
				'email'	=> isset($value['email']) ? $value['email'] : $value['user_email'],
				'type'	=> isset($value['type']) ? $value['type'] : $value['user_site_notify_type'],
				'lang'	=> isset($value['lang']) ? $value['lang'] : $value['user_lang'],
			);
		}
	}

	if (!empty($to_query))
	{
		// Query users
		$sql = 'SELECT user_id, username, user_email, user_site_notify_type, user_lang
			FROM ' . USERS_TABLE . '
			WHERE ' . $db->sql_in_set('user_id', $to_query);
		$result = $db->sql_query($sql);

		while($row = $db->sql_fetchrow($result))
		{
			$users[$row['user_id']] = array(
				'uid'	=> $row['user_id'],
				'name'	=> $row['username'],
				'email'	=> $row['user_email'],
				'type'	=> $row['user_site_notify_type'],
				'lang'	=> $row['user_lang'],
			);
		}
	}

	// Get sender details: bot's username
	$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . ' WHERE user_id = ' . BOT_USER;
	$result = $db->sql_query($sql);
	$sender = $db->sql_fetchrow($result);

	// Initialize email messenger
	include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
	$messenger = new messenger();

	include_once($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);

	foreach ($users as $item)
	{
		if ($item['type'] == NOTIFY_EMAIL || $item['type'] == NOTIFY_SBOTH)
		{
			$messenger->template('notifs/' . $template_file, $item['lang']);
			$messenger->to($item['email'], $item['name']);

			$messenger->assign_vars($vars);
			$messenger->assign_vars(array(
				'USERNAME'		=> htmlspecialchars_decode($item['name']),
			));

			$messenger->send(NOTIFY_EMAIL);
		}
		if ($item['type'] == NOTIFY_PM || $item['type'] == NOTIFY_SBOTH)
		{
			// Get the text of the pm
			$tpl_file = "{$phpbb_root_path}language/$item[lang]/email/notifs/{$template_file}_pm.txt";

			if (!file_exists($tpl_file))
			{
				trigger_error("Could not find notification template file [ $tpl_file ]", E_USER_ERROR);
			}

			if (($message = @file_get_contents($tpl_file)) === false)
			{
				trigger_error("Failed opening template file [ $tpl_file ]", E_USER_ERROR);
			}

			$message = str_replace ("'", "\'", $message);
			$message = preg_replace('#\{([a-z0-9\-_]*?)\}#is', "' . ((isset(\$vars['\\1'])) ? \$vars['\\1'] : '') . '", $message);

			$vars += array(
				'USERNAME'		=> htmlspecialchars_decode($item['name']),
			);

			eval("\$message = '$message';");

			// We now try and pull a subject from the email body ... if it exists,
			// do this here because the subject may contain a variable
			$drop_header = '';
			$match = array();
			if (preg_match('#^(Subject:(.*?))$#m', $message, $match))
			{
				$subject = (trim($match[2]) != '') ? trim($match[2]) : (($subject != '') ? $subject : $user->lang['NO_EMAIL_SUBJECT']);
				$drop_header .= '[\r\n]*?' . preg_quote($match[1], '#');
			}
			else
			{
				$subject = (($subject != '') ? $subject : $user->lang['NO_EMAIL_SUBJECT']);
			}

			if ($drop_header)
			{
				$message = trim(preg_replace('#' . $drop_header . '#s', '', $message));
			}

			// Parse message
			$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
			generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

			$pm_data = array(
				'from_user_id'			=> $sender['user_id'],
				'from_username'			=> $sender['username'],
				'from_user_ip'			=> $_SERVER['SERVER_ADDR'],
				'icon_id'				=> 0,
				'enable_sig'			=> true,
				'enable_bbcode'			=> true,
				'enable_smilies'		=> true,
				'enable_urls'			=> true,
				'bbcode_bitfield'		=> $bitfield,
				'bbcode_uid'			=> $uid,
				'message'				=> $message,
				'address_list'			=> array('u' => array($item['uid'] => 'to')),
			);

			submit_pm('post', $subject, $pm_data, false);
		}
	}
}

/**
* Wrapper for page_header() - just gives us the ability to add some own code
*/
function site_header($page_title = '', $site_section = '', $breadcrumbs = array(), $display_online_list = false)
{
	global $phpbb_root_path, $template, $user;

	page_header($page_title, $display_online_list);

	foreach($breadcrumbs as $item)
	{
		$template->assign_block_vars('breadcrumbs', array(
			'PAGE_URL'		=> append_sid($phpbb_root_path . $item[0]),
			'PAGE_TITLE'	=> (isset($user->lang[$item[1]])) ? $user->lang[$item[1]] : $item[1],
		));
	}

	$template->assign_vars(array(
		'SITE_SECTION'	=> $site_section,

		'U_SITE_HOME'	=> append_sid($phpbb_root_path),
	));
}

/**
* Wrapper for page_footer() - should we have to insert some own code, the ability is given
*/
function site_footer($run_cron = true)
{
	global $sidemenu;

	/*if (isset($sidemenu))
	{
		$sidemenu->display();
	}*/

	page_footer($run_cron);
}

/**
 * Get the list of the users subscribed to a topic
 *
 * @param int	$forum_id		Topic's parent forum's id
 * @param int	$topic_id		Topic's id
 * @param array	$plus_ids		Additional user ids to include
 * @param array	$exclude_ids	User ids to be left out
 * @param bool	$check_status	Check whether the user has been notified but not read the content yet
 */
function get_subscribed_users($forum_id, $topic_id, $plus_ids = array(), $exclude_ids = array(), $check_status = false)
{
	global $auth, $db, $user;

	// Get banned User ID's
	$sql = 'SELECT ban_userid
		FROM ' . BANLIST_TABLE;
	$result = $db->sql_query($sql);

	$sql_ignore_users = ANONYMOUS . ', ' . $user->data['user_id'];
	while ($row = $db->sql_fetchrow($result))
	{
		if (isset($row['ban_userid']))
		{
			$sql_ignore_users .= ', ' . $row['ban_userid'];
		}
	}
	$db->sql_freeresult($result);

	$sql_exclude_users = (empty($exclude_ids)) ? '' : ', ' .implode(', ', $exclude_ids);

	$sql = 'SELECT u.user_id, u.username, u.user_email, user_site_notify_type, u.user_lang, u.user_jabber
		FROM ' . TOPICS_WATCH_TABLE . ' w, ' . USERS_TABLE . ' u
		WHERE w.topic_id = ' . $topic_id . "
			AND w.user_id NOT IN ({$sql_ignore_users}{$sql_exclude_users})
			" . (($check_status) ? 'AND w.notify_status = 0' : '') . "
			AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')
			AND u.user_id = w.user_id';
	$result = $db->sql_query($sql);

	$users = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$users[$row['user_id']] = $row;
	}
	$db->sql_freeresult($result);

	// Make sure users are allowed to read the forum
	$allowed_users = array();
	if (!empty($allowed_users))
	{
		foreach ($auth->acl_get_list(array_keys($users), 'f_c_see', $forum_id) as $forum_id => $forum_ary)
		{
			foreach ($forum_ary as $auth_option => $user_ary)
			{
				foreach ($user_ary as $user_id)
				{
					$allowed_users[$user_id] = $users[$user_id];
					unset($users[$user_id]);
				}
			}
		}
	}
	$not_authorised_users = $users;
	$allowed_users = $users;

	// Query the additonal users
	if (!empty($plus_ids))
	{
		$sql = 'SELECT u.user_id, u.username, u.user_email, user_site_notify_type, u.user_lang, u.user_jabber
			FROM ' . USERS_TABLE . ' u
			WHERE ' . $db->sql_in_set('u.user_id', $plus_ids);
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			if ($row['user_id'] != $user->data['user_id'] && !isset($allowed_users[$row['user_id']]))
			{
				$allowed_users[$row['user_id']] = $row;
			}
		}
		$db->sql_freeresult($result);
	}

	// Update the db if necessary
	if ($check_status)
	{
		$sql = 'UPDATE ' . TOPICS_WATCH_TABLE . "
			SET notify_status = 1
			WHERE topic_id = $topic_id
				AND " . $db->sql_in_set('user_id', array_keys($allowed_users));
		$db->sql_query($sql);
	}

	// Now delete the user_ids not authorised to receive notifications on this topic
	if (!empty($not_authorised_user_ids))
	{
		$sql = 'DELETE FROM ' . TOPICS_WATCH_TABLE . "
			WHERE topic_id = $topic_id
				AND " . $db->sql_in_set('user_id', $not_authorised_user_ids);
		$db->sql_query($sql);
	}

	return $allowed_users;
}

/**
 * Get the template of a "content post" and substitute the appropriate variables
 *
 * @param	string	$template_file	The template file of the content post located in the email/content/ folder of the language directory
 * @param	array	$vars			Custom vars to substitute
 * @return 	string
 */
function generate_content_post($template_file, $vars)
{
	global $phpbb_root_path, $phpEx;
	global $config;

	// Load the template file
	$tpl_file = "{$phpbb_root_path}language/{$config['default_lang']}/email/content/{$template_file}.txt";

	if (!file_exists($tpl_file))
	{
		trigger_error("Could not find notification template file [ $tpl_file ]", E_USER_ERROR);
	}

	if (($message = @file_get_contents($tpl_file)) === false)
	{
		trigger_error("Failed opening template file [ $tpl_file ]", E_USER_ERROR);
	}

	$message = str_replace ("'", "\'", $message);
	$message = preg_replace('#\{([a-z0-9\-_]*?)\}#is', "' . ((isset(\$vars['\\1'])) ? \$vars['\\1'] : '') . '", $message);

	eval("\$message = '$message';");

	return $message;
}


/**
* Class representing the menu on the (right) side
*
* Only used as a wrapper for the time being
*/
class sidemenu
{
	//protected $blocks = array();

	/**
	* Add a new sidemenu block
	*
	* @param string $title Title of the block (either a complete string or a language key)
	* @param string $url Optional url the menu header will point to - append_sid must already be run on it
	*/
	public function add_block($title, $url = null)
	{
		global $template, $user;

		$template->assign_block_vars('sidemenublock', array(
			'BLOCK_TITLE'	=> isset($user->lang[$title]) ? $user->lang[$title] : $title,
			'BLOCK_URL'		=> !empty($url) ? append_sid($url) : false,
		));

		/*$this->blocks[] = array(
			'title'	=> $title,
			'url'	=> $url,
			'items'	=> array(),
		);*/
	}

	/**
	* Add a new link element to the last opened block
	*
	* @param string $title Title of the menu item (can be a language key too)
	* @param string $url The url of the menu item (without append_sid run on it, can have params)
	* @param mixed $url_params String or array of additional url parameters
	* @param bool $force_sid Whether to add the sid always
	*/
	public function add_link($title, $url, $url_params = false, $force_sid = false)
	{
		global $template, $user, $url_rewriter;

		$template->assign_block_vars('sidemenublock.element', array(
			'ITEM_TITLE'	=> isset($user->lang[$title]) ? $user->lang[$title] : $title,
			'U_ITEM'		=> (!$force_sid) ? append_sid($url, $url_params) : $url_rewriter->rewrite($url, $url_params),
		));

		/*$this->blocks[count($this->blocks) - 1][] = array(
			'title'	=> $title,
			'url'	=> $url,
		);*/
	}

	/**
	* Add a new key-value pair type of element to the last opened block
	*
	* @param string $key
	* @param string $value
	*/
	public function add_kv_pair($key, $value)
	{
		global $template, $user;

		$template->assign_block_vars('sidemenublock.element', array(
			'ITEM_TITLE'	=> isset($user->lang[$key]) ? $user->lang[$key] : $key,
			'ITEM_VALUE'	=> isset($user->lang[$value]) ? $user->lang[$value] : $value,
		));
	}

	/**
	* Output collected data
	*/
	/*public function display()
	{

	}*/
}

/**
 * Generate a list formatted with BBCode of the assigned tags for an item
 *
 * @param array $assigned_tags IDs of the assigned tags
 * @param array $all_tagcats Multidimensional array, the first level key is the id of a tagcat and its value is an array of the tags the tagcat contains
 * @param array $tag_url_scheme URL for the tag page with the tag category name replaced with %1$s and the tag named replaced with %2$s, the first entry is the file, the second are the params
 */
function generate_tags_bbcode_list($assigned_tags, $all_tagcats, $tag_url_scheme)
{
	global $url_rewriter;

	// Group the assigned tags into categories by tag category id
	$assigned_tagcats = array();
	foreach ($all_tagcats as $tagcat)
	{
		foreach ($tagcat as $tag)
		{
			if (in_array($tag['tag_id'], $assigned_tags))
			{
				$assigned_tagcats[$tag['tagcat_id']][] = $tag;
			}
		}
	}

	// Generate BBCode list
	$tags_list = '[list]';
	foreach ($assigned_tagcats as $tagcat)
	{
		$tags_list .= '[*]' . $tagcat[0]['tagcat_title'] . '[list]';
		foreach ($tagcat as $tag)
		{
			$tags_list .= '[*][url=' . generate_board_url() . '/' . $url_rewriter->rewrite($tag_url_scheme[0], sprintf($tag_url_scheme[1], $tag['tagcat_name'], $tag['tag_name'])) . ']' . $tag['tag_title'] . '[/url][/*]';
		}
		$tags_list .= '[/list][/*]';
	}
	$tags_list .= '[/list]';

	return $tags_list;
}

/**
* Increase memory limit (code from install/index.php)
*
* @param int $new_mem_limit New minimum memory limit in megabytes
*/
function increase_mem_limit($new_mem_limit)
{
	$mem_limit = @ini_get('memory_limit');

	if (!empty($mem_limit))
	{
		$unit = strtolower(substr($mem_limit, -1, 1));
		$mem_limit = (int) $mem_limit;

		if ($unit == 'k')
		{
			$mem_limit = floor($mem_limit / 1024);
		}
		else if ($unit == 'g')
		{
			$mem_limit *= 1024;
		}
		else if (is_numeric($unit))
		{
			$mem_limit = floor((int) ($mem_limit . $unit) / 1048576);
		}
		$mem_limit = max($new_mem_limit, $mem_limit) . 'M';
	}
	else
	{
		$mem_limit = $new_mem_limit . 'M';
	}

	@ini_set('memory_limit', $mem_limit);
}

/**
 * Truncate text and add three dots to the end if necessary
 *
 * @param string $text Text to be truncated
 * @param int $max_length Text length limit
 */
function trim_text($text, $max_length)
{
	if (utf8_strlen($text) > $max_length)
	{
		$text = utf8_substr($text, 0, $max_length);

		// Do not cut the text in the middle of a word
		$text = substr($text, 0, strrpos ($text, ' '));

		// Append three dots indicating that this is not the real end of the text
		return $text . ' â¦';
	}
	else
	{
		return $text;
	}
}

/**
* BBCode-safe truncating of text
*
* @param string $text Text containing BBCode tags to be truncated
* @param string $uid BBCode uid
* @param int $max_length Text length limit
* @param string $bitfield BBCode bitfield (optional)
* @param bool $enable_bbcode Whether BBCode is enabled (true by default)
* @return string
*/
function trim_post ($text, $uid, $max_length, $bitfield = '', $enable_bbcode = true)
{
	// If there is any custom BBCode that can have space in its argument, turn this on,
	// but else I suggest turning this off as it adds one additional (cached) SQL query
	$check_custom_bbcodes = true;

	if ($enable_bbcode && $check_custom_bbcodes)
	{
		global $db;
		static $custom_bbcodes = array();

		// Get all custom bbcodes
		if (empty($custom_bbcodes))
		{
			$sql = 'SELECT bbcode_id, bbcode_tag
				FROM ' . BBCODES_TABLE;
			$result = $db->sql_query($sql, 108000);

			while ($row = $db->sql_fetchrow($result))
			{
				// There can be problems only with tags having an argument
				if (substr($row['bbcode_tag'], -1, 1) == '=')
				{
					$custom_bbcodes[$row['bbcode_id']] = array('[' . $row['bbcode_tag'], ':' . $uid . ']');
				}
			}
			$db->sql_freeresult($result);
		}
	}

	// First truncate the text
	if (utf8_strlen($text) > $max_length)
	{
		$text = utf8_substr($text, 0, $max_length);

		// Do not cut the text in the middle of a word
		$text = substr($text, 0, strrpos ($text, ' '));

		// Append three dots indicating that this is not the real end of the text
		$text .= ' â¦';

		if (!$enable_bbcode)
		{
			return $text;
		}
	}
	else
	{
		return $text;
	}

	// Some tags may contain spaces inside the tags themselves.
	// If there is any tag that had been started but not ended
	// cut the string off before it begins and add three dots
	// to the end of the text again as this has been just cut off too.
	$unsafe_tags = array(
		array('<', '>'),
		array('[quote=&quot;', "&quot;:$uid]"),
	);

	// If bitfield is given only check for tags that are surely existing in the text
	if (!empty($bitfield))
	{
		// Get all used tags
		$bitfield = new bitfield($bitfield);
		$bbcodes_set = $bitfield->get_all_set();

		// Add custom BBCodes having a parameter and being used
		// to the array of potential tags that can be cut apart.
		foreach ($custom_bbcodes as $bbcode_id => $bbcode_name)
		{
			if (in_array($bbcode_id, $bbcodes_set))
			{
				$unsafe_tags[] = $bbcode_name;
			}
		}
	}
	// Do the check for all possible tags
	else
	{
		$unsafe_tags += $custom_bbcodes;
	}

	foreach($unsafe_tags as $tag)
	{
		if (($start_pos = strrpos($text, $tag[0])) > strrpos($text, $tag[1]))
		{
			$text = substr($text, 0, $start_pos) . ' â¦';
		}
	}

	// Get all of the BBCodes the text contains.
	// If it does not contain any than just skip this step.
	// Preg expression is borrowed from strip_bbcode()
	if (preg_match_all("#\[(\/?)([a-z0-9\*\+\-]+)(?:=(&quot;.*&quot;|[^\]]*))?(?::[a-z])?(?:\:$uid)\]#", $text, $matches, PREG_PATTERN_ORDER) != 0)
	{
		$open_tags = array();

		for ($i = 0, $size = sizeof($matches[0]); $i < $size; ++$i)
		{
			$bbcode_name = &$matches[2][$i];
			$opening = ($matches[1][$i] == '/') ? false : true;

			// If a new BBCode is opened add it to the array of open BBCodes
			if ($opening)
			{
				$open_tags[] = array(
					'name' => $bbcode_name,
					'plus' => ($opening && $bbcode_name == 'list' && !empty($matches[3][$i])) ? ':o' : '',
				);
			}
			// If a BBCode is closed remove it from the array of open BBCodes.
			// As always only the last opened open tag can be closed
			// we only need to remove the last element of the array.
			else
			{
				array_pop($open_tags);
			}
		}

		// Sort open BBCode tags so the most recently opened will be the first (because it has to be closed first)
		krsort ($open_tags);

		// Close remaining open BBCode tags
		foreach ($open_tags as $tag)
		{
			$text .= '[/' . $tag['name'] . $tag['plus'] . ':' . $uid . ']';
		}
	}

	return $text;
}

/**
* Compare the version number of two tag entries
* (basically a wrapper for selecting the appropriate element from the array)
*/
function version_compare_tag($a, $b)
{
	return version_compare($a['tag_name'], $b['tag_name']);
}

?>