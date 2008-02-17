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
if (!defined('IN_PHPBB'))
{
	exit;
}

$urls = array(
	'download.php' => array(
		array('^id=(\d+)$', 'csatolmany/$1'),
		array('^mode=view&id=(\d+)', 'csatolmany/$1/megtekintes'),
	),
	'faq.php' => array(
		array('^$', 'forum/gyik'),
		array('^mode=bbcode$', 'forum/gyik/bbcode'),
	),
	'index.php' => array(
		array('^', 'forum'),
	),
	'memberlist.php' => array(
		array('^mode=viewprofile&u=(\d+)$', 'tagok/$1'),
		array('^mode=email&u=(\d+)$', 'tagok/$1/email'),
		array('^mode=email&t=(\d+)$', 'tagok/ertesites/$1'),
		array('^mode=leaders', 'tagok/acsapat'),
		array('^mode=searchuser', 'tagok/kereses'),
		array('^mode=group&g=(\d+)', 'tagok/csoport/$1'),
		array('^g=(\d+)&mode=group', 'tagok/csoport/$1'),
		array('^mode=group&g=(\d+)', 'tagok/csoport'),
		array('^mode=contact&action=([a-z]+)&u=(\d+)', 'tagok/$2/kapcsolat/$1'),
		array('^mode=', 'tagok'),
		array('^', 'tagok'),
	),
	'posting.php' => array(
		array('^mode=post&f=(\d+)', 'kuldes/ujtema/$1'),
		array('^mode=reply&f=(\d+)&t=(\d+)', 'kuldes/valasz/$1/$2'),
		array('^mode=quote&f=(\d+)&p=(\d+)', 'kuldes/idezet/$1/$2'),
		array('^mode=edit&f=(\d+)&p=(\d+)', 'kuldes/szerk/$1/$2'),
		array('^mode=delete&f=(\d+)&p=(\d+)', 'kuldes/torol/$1/$2'),
		array('^', 'kuldes'),
	),
	'report.php' => array(
		array('^f=(\d+)&p=(\d+)', 'forum/jelentes/$1/$2'),
		array('^', 'forum/jelentes'), // I don't think this is actually used but ...
	),
	'search.php' => array(
		array('^search_id=unanswered', 'kereses/megvalaszolatlan'),
		array('^search_id=newposts', 'kereses/uj'),
		array('^search_id=egosearch', 'kereses/sajat'),
		array('^search_id=active_topics', 'kereses/aktiv'),
		array('^author_id=(\d+)', 'kereses/szerzo/$1'),
		array('^author_id=(\d+)&sr=posts', 'kereses/szerzo/$1/hsz'),
		array('^author_id=(\d+)&sr=topic', 'kereses/szerzo/$1/tema'),
		array('^', 'kereses'),
	),
	'ucp.php' => array(
		array('^mode=register', 'regisztracio'),
		array('^mode=login', 'belepes'),
		array('^mode=terms', 'forum/felhfelt'),
		array('^mode=privacy', 'forum/adatvedelem'),
		array('^', 'fvp'),
	),
	'viewforum.php' => array(
		array('^f=([0-9]+)', 'forum/$1'),
		),
	'viewonline.php' => array(
		array('^', 'kivanitt'),
	),
	'viewtopic.php' => array(
		array('^f=(\d+)&t=(\d+)', 'forum/$1/$2'),
		array('^p=(\d+)', 'forum/hsz/$1'),
		array('^f=(\d+)&p=(\d+)', 'forum/hsz/$2'),
		array('^t=(\d+)', 'forum/tema/$1'),
	),
	
	// Site
	'bugs.php' => array(
		array('^mode=project&project=([a-z0-9_-]+)', 'bugs/$1'),
		array('^mode=report&project=([a-z0-9_-]+)&report_id=([0-9]+)', 'bugs/$1/$2'),
		array('^mode=add&project=([a-z0-9_-]+)', 'bugs/$1/uj'),
		array('^mode=edit&project=([a-z0-9_-]+)&report_id=([0-9]+)', 'bugs/$1/$2/szerk'),
		array('^mode=reply&project=([a-z0-9_-]+)&report_id=([0-9]+)', 'bugs/$1/$2/hozzaszol'),
		array('^', 'bugs'),
	),
	'kb.php' => array(
		array('^mode=listtags', 'utmutatok/cimkek'),
		array('^mode=listtagcats&cat=([a-z0-9+_-]+)', 'utmutatok/cimkek/$1'),
		array('^mode=tag&cat=([a-z0-9+_-]+)&tag=([a-z0-9+_-]+)', 'utmutatok/cimkek/$1/$2'),
		array('^mode=article&name=([a-z0-9_-]+)', 'utmutatok/cikk/$1'),
		array('^mode=add', 'utmutatok/uj'),
		array('^mode=edit&id=([0-9]+)', 'utmutatok/szerk/$1'),
		array('^mode=delete&id=([0-9]+)', 'utmutatok/torol/$1'),
		array('^', 'utmutatok'),
	),
	'mods.php' => array(
		array('^mode=tagcat&cat=([a-zA-Z0-9.+_-]+)', 'modok/cimkek/$1'),
		array('^mode=listtag&cat=([a-zA-Z0-9.+_-]+)&tag=([a-zA-Z0-9.+_-]+)', 'modok/cimkek/$1/$2'),
		array('^mode=mod&id=([0-9]+)', 'modok/mod/$1'),
		array('^mode=add', 'modok/uj'),
		array('^mode=edit&id=([0-9]+)', 'modok/szerk/$1'),
		array('^mode=delete&id=([0-9]+)', 'modok/torol/$1'),
		array('^', 'modok'),
	),
);

?>