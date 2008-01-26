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
if (!defined('IN_PHPBB') && !defined('IN_PAGES'))
{
	exit;
}

// Simple text display page

$template->assign_vars(array(
	'PAGE_TITLE'	=> $page['page_title'],
	'PAGE_CONTENT'	=> $page['page_content'],
	'PAGE_URL'		=> $page['page_url'],
));

// Output page
site_header((isset($user->lang[$page['page_title']]) ? $user->lang[$page['page_title']] : $page['page_title']), $page['page_section'], array(array($page['page_url'], $page['page_title'])));

$template->set_filenames(array(
	'body' => 'pages/text.html')
);

site_footer();
?>