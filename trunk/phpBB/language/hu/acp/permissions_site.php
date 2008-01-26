<?php
/**
* acp_site (phpBB.hu Permission Set) [Hungarian]
*
* @package language
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

/**
*	MODDERS PLEASE NOTE
*
*	You are able to put your permission sets into a separate file too by
*	prefixing the new file with permissions_ and putting it into the acp
*	language folder.
*
*	An example of how the file could look like:
*
*	<code>
*
*	if (empty($lang) || !is_array($lang))
*	{
*		$lang = array();
*	}
*
*	// Adding new category
*	$lang['permission_cat']['bugs'] = 'Bugs';
*
*	// Adding new permission set
*	$lang['permission_type']['bug_'] = 'Bug Permissions';
*
*	// Adding the permissions
*	$lang = array_merge($lang, array(
*		'acl_bug_view'		=> array('lang' => 'Can view bug reports', 'cat' => 'bugs'),
*		'acl_bug_post'		=> array('lang' => 'Can post bugs', 'cat' => 'post'), // Using a phpBB category here
*	));
*
*	</code>
*/

// Site admin permissions
$lang = array_merge($lang, array(
	'acl_a_site'			=> array('lang' => 'Kezelheti az oldalt', 'cat' => 'misc'),
	'acl_a_bug_tracker'		=> array('lang' => 'Kezelheti a hibabejelentőt', 'cat' => 'misc'),
));

// Add content category
$lang['permission_cat']['scontent'] = 'Oldal tartalom';

// Content permissions
$lang = array_merge($lang, array(
	'acl_f_c_see'		=> array('lang' => 'Láthatja a tartalmat', 'cat' => 'scontent'),
	'acl_f_c_post'		=> array('lang' => 'Küldhet tartalmat', 'cat' => 'scontent'),
	'acl_f_c_edit'		=> array('lang' => 'Szerkesztheti a tartalmát', 'cat' => 'scontent'),
	'acl_f_c_delete'	=> array('lang' => 'Törölheti a tartalmát', 'cat' => 'scontent'),
	'acl_f_c_com_post'	=> array('lang' => 'Küldhet hozzászólást', 'cat' => 'scontent'),
	'acl_f_c_com_edit'	=> array('lang' => 'Szerkesztheti a hozzászólását', 'cat' => 'scontent'),
	'acl_f_c_com_delete'=> array('lang' => 'Törölheti a hozzászólását', 'cat' => 'scontent'),
	'acl_m_c_manage'	=> array('lang' => 'Kezelheti a tartalmakat', 'cat' => 'misc'),
));
/*
// Define categories and permission types
$lang = array_merge($lang, array(
	'permission_cat'	=> array(
		'actions'		=> 'Actions',
		'content'		=> 'Content',
		'forums'		=> 'Forums',
		'misc'			=> 'Misc',
		'permissions'	=> 'Permissions',
		'pm'			=> 'Private messages',
		'polls'			=> 'Polls',
		'post'			=> 'Post',
		'post_actions'	=> 'Post actions',
		'posting'		=> 'Posting',
		'profile'		=> 'Profile',
		'settings'		=> 'Settings',
		'topic_actions'	=> 'Topic actions',
		'user_group'	=> 'Users &amp; Groups',
	),

	// With defining 'global' here we are able to specify what is printed out if the permission is within the global scope.
	'permission_type'	=> array(
		'u_'			=> 'User permissions',
		'a_'			=> 'Admin permissions',
		'm_'			=> 'Moderator permissions',
		'f_'			=> 'Forum permissions',
		'global'		=> array(
			'm_'			=> 'Global moderator permissions',
		),
	),
));*/

/*// User Permissions
$lang = array_merge($lang, array(
	'acl_u_viewprofile'	=> array('lang' => 'Can view profiles, memberlist and online list', 'cat' => 'profile'),
	'acl_u_chgname'		=> array('lang' => 'Can change username', 'cat' => 'profile'),
	'acl_u_chgpasswd'	=> array('lang' => 'Can change password', 'cat' => 'profile'),
	'acl_u_chgemail'	=> array('lang' => 'Can change e-mail address', 'cat' => 'profile'),
	'acl_u_chgavatar'	=> array('lang' => 'Can change avatar', 'cat' => 'profile'),
	'acl_u_chggrp'		=> array('lang' => 'Can change default usergroup', 'cat' => 'profile'),

	'acl_u_attach'		=> array('lang' => 'Can attach files', 'cat' => 'post'),
	'acl_u_download'	=> array('lang' => 'Can download files', 'cat' => 'post'),
	'acl_u_savedrafts'	=> array('lang' => 'Can save drafts', 'cat' => 'post'),
	'acl_u_chgcensors'	=> array('lang' => 'Can disable word censors', 'cat' => 'post'),
	'acl_u_sig'			=> array('lang' => 'Can use signature', 'cat' => 'post'),

	'acl_u_sendpm'		=> array('lang' => 'Can send private messages', 'cat' => 'pm'),
	'acl_u_masspm'		=> array('lang' => 'Can send pm to multiple users and groups', 'cat' => 'pm'),
	'acl_u_readpm'		=> array('lang' => 'Can read private messages', 'cat' => 'pm'),
	'acl_u_pm_edit'		=> array('lang' => 'Can edit own private messages', 'cat' => 'pm'),
	'acl_u_pm_delete'	=> array('lang' => 'Can remove private messages from own folder', 'cat' => 'pm'),
	'acl_u_pm_forward'	=> array('lang' => 'Can forward private messages', 'cat' => 'pm'),
	'acl_u_pm_emailpm'	=> array('lang' => 'Can e-mail private messages', 'cat' => 'pm'),
	'acl_u_pm_printpm'	=> array('lang' => 'Can print private messages', 'cat' => 'pm'),
	'acl_u_pm_attach'	=> array('lang' => 'Can attach files in private messages', 'cat' => 'pm'),
	'acl_u_pm_download'	=> array('lang' => 'Can download files in private messages', 'cat' => 'pm'),
	'acl_u_pm_bbcode'	=> array('lang' => 'Can post BBCode in private messages', 'cat' => 'pm'),
	'acl_u_pm_smilies'	=> array('lang' => 'Can post smilies in private messages', 'cat' => 'pm'),
	'acl_u_pm_img'		=> array('lang' => 'Can post images in private messages', 'cat' => 'pm'),
	'acl_u_pm_flash'	=> array('lang' => 'Can post Flash in private messages', 'cat' => 'pm'),

	'acl_u_sendemail'	=> array('lang' => 'Can send e-mails', 'cat' => 'misc'),
	'acl_u_sendim'		=> array('lang' => 'Can send instant messages', 'cat' => 'misc'),
	'acl_u_ignoreflood'	=> array('lang' => 'Can ignore flood limit', 'cat' => 'misc'),
	'acl_u_hideonline'	=> array('lang' => 'Can hide online status', 'cat' => 'misc'),
	'acl_u_viewonline'	=> array('lang' => 'Can view hidden online users', 'cat' => 'misc'),
	'acl_u_search'		=> array('lang' => 'Can search board', 'cat' => 'misc'),
));

// Forum Permissions
$lang = array_merge($lang, array(
	'acl_f_list'		=> array('lang' => 'Can see forum', 'cat' => 'post'),
	'acl_f_read'		=> array('lang' => 'Can read forum', 'cat' => 'post'),
	'acl_f_post'		=> array('lang' => 'Can start new topics', 'cat' => 'post'),
	'acl_f_reply'		=> array('lang' => 'Can reply to topics', 'cat' => 'post'),
	'acl_f_icons'		=> array('lang' => 'Can use topic/post icons', 'cat' => 'post'),
	'acl_f_announce'	=> array('lang' => 'Can post announcements', 'cat' => 'post'),
	'acl_f_sticky'		=> array('lang' => 'Can post stickies', 'cat' => 'post'),

	'acl_f_poll'		=> array('lang' => 'Can create polls', 'cat' => 'polls'),
	'acl_f_vote'		=> array('lang' => 'Can vote in polls', 'cat' => 'polls'),
	'acl_f_votechg'		=> array('lang' => 'Can change existing vote', 'cat' => 'polls'),

	'acl_f_attach'		=> array('lang' => 'Can attach files', 'cat' => 'content'),
	'acl_f_download'	=> array('lang' => 'Can download files', 'cat' => 'content'),
	'acl_f_sigs'		=> array('lang' => 'Can use signatures', 'cat' => 'content'),
	'acl_f_bbcode'		=> array('lang' => 'Can post BBCode', 'cat' => 'content'),
	'acl_f_smilies'		=> array('lang' => 'Can post smilies', 'cat' => 'content'),
	'acl_f_img'			=> array('lang' => 'Can post images', 'cat' => 'content'),
	'acl_f_flash'		=> array('lang' => 'Can post Flash', 'cat' => 'content'),

	'acl_f_edit'		=> array('lang' => 'Can edit own posts', 'cat' => 'actions'),
	'acl_f_delete'		=> array('lang' => 'Can delete own posts', 'cat' => 'actions'),
	'acl_f_user_lock'	=> array('lang' => 'Can lock own topics', 'cat' => 'actions'),
	'acl_f_bump'		=> array('lang' => 'Can bump topics', 'cat' => 'actions'),
	'acl_f_report'		=> array('lang' => 'Can report posts', 'cat' => 'actions'),
	'acl_f_subscribe'	=> array('lang' => 'Can subscribe forum', 'cat' => 'actions'),
	'acl_f_print'		=> array('lang' => 'Can print topics', 'cat' => 'actions'),
	'acl_f_email'		=> array('lang' => 'Can e-mail topics', 'cat' => 'actions'),

	'acl_f_search'		=> array('lang' => 'Can search the forum', 'cat' => 'misc'),
	'acl_f_ignoreflood' => array('lang' => 'Can ignore flood limit', 'cat' => 'misc'),
	'acl_f_postcount'	=> array('lang' => 'Increment post counter<br /><em>Please note that this setting only affects new posts.</em>', 'cat' => 'misc'),
	'acl_f_noapprove'	=> array('lang' => 'Can post without approval', 'cat' => 'misc'),
));

// Moderator Permissions
$lang = array_merge($lang, array(
	'acl_m_edit'		=> array('lang' => 'Can edit posts', 'cat' => 'post_actions'),
	'acl_m_delete'		=> array('lang' => 'Can delete posts', 'cat' => 'post_actions'),
	'acl_m_approve'		=> array('lang' => 'Can approve posts', 'cat' => 'post_actions'),
	'acl_m_report'		=> array('lang' => 'Can close and delete reports', 'cat' => 'post_actions'),
	'acl_m_chgposter'	=> array('lang' => 'Can change post author', 'cat' => 'post_actions'),

	'acl_m_move'	=> array('lang' => 'Can move topics', 'cat' => 'topic_actions'),
	'acl_m_lock'	=> array('lang' => 'Can lock topics', 'cat' => 'topic_actions'),
	'acl_m_split'	=> array('lang' => 'Can split topics', 'cat' => 'topic_actions'),
	'acl_m_merge'	=> array('lang' => 'Can merge topics', 'cat' => 'topic_actions'),

	'acl_m_info'	=> array('lang' => 'Can view post details', 'cat' => 'misc'),
	'acl_m_warn'	=> array('lang' => 'Can issue warnings<br /><em>This setting is only assigned globally. It is not forum based.</em>', 'cat' => 'misc'), // This moderator setting is only global (and not local)
	'acl_m_ban'		=> array('lang' => 'Can manage bans<br /><em>This setting is only assigned globally. It is not forum based.</em>', 'cat' => 'misc'), // This moderator setting is only global (and not local)
));*/



?>
