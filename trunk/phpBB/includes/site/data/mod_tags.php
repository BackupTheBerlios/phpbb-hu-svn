<?php
/** 
*
* @package site
* @version $Id$
* @copyright (c) 2008 phpbb.hu
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

$tag_tranlsation_table = array(
	'phpbb' => array(
		'name'	=> 'phpbb',
		'title'	=> 'phpBB verzió',
		'tags'	=> array(),
	),
	'category' => array(
		'name'	=> 'kategoria',
		'title'	=> 'Kategória',
		'tags'	=> array(
			'addons'		=> array('name' => 'kiegeszitok',	'title' => 'Kiegészítők'),
			'cosmetic'		=> array('name' => 'kinezet', 		'title' => 'Kinézet'),
			'admin'			=> array('name' => 'admin', 		'title' => 'Admin eszközök'),
			'syndication'	=> array('name' => 'szindikacio', 	'title' => 'Szindikáció'),
			'bbcode'		=> array('name' => 'bbcode', 		'title' => 'BBCode'),
			'security'		=> array('name' => 'biztonsag', 	'title' => 'Biztonság'),
			'communication'	=> array('name' => 'kommunikacio', 	'title' => 'Kommunikáció'),
			'profile'		=> array('name' => 'profil', 		'title' => 'Profil/Felhasználói vezérlőpult'),
			'tools'			=> array('name' => 'eszkozok', 		'title' => 'Eszközök'),
			'antispam'		=> array('name' => 'antispam', 		'title' => 'Antispam'),
			'moderator'		=> array('name' => 'moderator', 	'title' => 'Moderátori eszközök'),
			'entertainment'	=> array('name' => 'szorakozas', 	'title' => 'Szórakozás, kikapcsolódás'),
		),
	),
	'complexity' => array(
		'name'	=> 'bonyolultsag',
		'title'	=> 'Bonyolultság',
		'tags'	=> array(
			'sql_schema'	=> array('name' => 'sql_szerkezet',	'title' => 'SQL struktúra változtatások'),
			'sql_data'		=> array('name' => 'sql_adat', 		'title' => 'SQL adat változtatások'),
			'tpl_edits'		=> array('name' => 'sablon_valt', 	'title' => 'Sablon változtatások'),
			'lang_edits'	=> array('name' => 'nyelvi_valt', 	'title' => 'Nyelvi változtatások'),
			'file_edits'	=> array('name' => 'fajl_valt', 	'title' => 'Állomány változtatások'),
		),
	),
	'time' => array(
		'name'	=> 'telepitesido',
		'title'	=> 'Telepítés időtartama',
		'tags'	=> array(
			'1'		=> array('name' => '1',		'title' => '~ 1 perc'),
			'3'		=> array('name' => '3',		'title' => '~ 3 perc'),
			'5'		=> array('name' => '5',		'title' => '~ 5 perc'),
			'10'	=> array('name' => '10',	'title' => '~ 10 perc'),
			'15'	=> array('name' => '15',	'title' => '~ 15 perc'),
			'20'	=> array('name' => '20',	'title' => '~ 20 perc'),
			'25'	=> array('name' => '25',	'title' => '~ 25 perc'),
			'30'	=> array('name' => '30',	'title' => '~ 30 perc'),
			'45'	=> array('name' => '45',	'title' => '~ 45 perc'),
			'60'	=> array('name' => '60',	'title' => '~ 60+ perc'),
			'90'	=> array('name' => '90',	'title' => '~ 90+ perc'),
			'120'	=> array('name' => '120',	'title' => '~ 120+ perc'),
		),
	),
);

?>