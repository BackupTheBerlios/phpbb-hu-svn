<?php
/** 
*
* @package phpBB3
* @copyright (c) 2005 phpBB Group 
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
$user->setup();

print '<pre>';
/*header('Content-type: text/txt');
header('Content-disposition: inline');*/

//send_notification(array(3), 'test', array('VALTOZO' => 'próba [i]üzenet[/i]!!!'))

$users = get_notification_users(54, 1771, array(), array(3));


print "\n\n\n\n";

var_dump($users);
print '</pre>';
?>