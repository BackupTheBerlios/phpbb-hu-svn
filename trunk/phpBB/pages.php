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

// Get the url
$url = request_var('url', '');

if (empty($url))
{
	$url = substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['SCRIPT_NAME'])) + 1);
}

// Query page
$sql = 'SELECT page_id, page_url, page_section, page_file, page_title, page_content
	FROM ' . PAGES_TABLE . "
	WHERE page_url = '" . $db->sql_escape($url) . "'";
$result = $db->sql_query($sql);

if (($page = $db->sql_fetchrow($result)) == false)
{
	// Page is not found, get the 404 page
	http_status(404);
	
	$sql = 'SELECT page_id, page_url, page_section, page_file, page_title, page_content
		FROM ' . PAGES_TABLE . "
		WHERE page_url = '404'";
	$result = $db->sql_query($sql);
	
	if (($page = $db->sql_fetchrow($result)) == false)
	{
		// If that is not available either, fall back to a default error message
		define('SITE_SECTION', 'main');
		trigger_error('NOT_FOUND');
	}
}

// Define the site section
define('SITE_SECTION', $page['page_section']);

// Indicate that this script can display pages
define('IN_PAGES', true);

// Include the PHP file
// Included PHP files can use every variable including $page
include($phpbb_root_path . $page['page_file']);
?>