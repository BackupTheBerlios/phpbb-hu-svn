<?php
/**
*
* info_acp_site [Hungarian]
*
* @package language
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

// Common
$lang = array_merge($lang, array(
	'ACP_BUG_TRACKER'			=> 'Hibajelentő',
	'ACP_BUG_TRACKER_COMPONENTS'=> 'Komponensek',
	'ACP_BUG_TRACKER_SETTINGS'	=> 'Hibajelentő beállítások',
	'ACP_BUG_TRACKER_PROJECTS'	=> 'Projektek',
	'ACP_BUG_TRACKER_STATUSES'	=> 'Státuszok',
	'ACP_BUG_TRACKER_VERSIONS'	=> 'Verziók',

	'ACP_CAT_SITE'				=> 'Oldal',

	'ACP_PAGES'					=> 'Oldalak/lapok',

	'ACP_SITE_MANAGEMENT'		=> 'Oldal kezelése',
	'ACP_SITE_SETTINGS'			=> 'Oldal beállítások',

	'ACP_TAGS_CATS'				=> 'Címke kategóriák',
	'ACP_TAGS_MANAGEMENT'		=> 'Címkék kezelése',
	'ACP_TAGS_TAGS'				=> 'Címkék',
));

// Bug tracker
$lang = array_merge($lang, array(
	'IMG_BUTTON_REPORT_NEW'		=> 'Új jelentés',
));
?>
