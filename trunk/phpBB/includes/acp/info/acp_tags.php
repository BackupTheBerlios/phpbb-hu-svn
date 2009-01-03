<?php
/** 
*
* @package acp
* @version $Id$
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @package module_install
*/
class acp_tags_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_tags',
			'title'		=> 'ACP_TAGS_MANAGEMENT',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'cats'		=> array('title' => 'ACP_TAGS_CATS', 'auth' => 'acl_a_site', 'cat' => array('ACP_TAGS_MANAGEMENT')),
				'tags'		=> array('title' => 'ACP_TAGS_TAGS', 'auth' => 'acl_a_site', 'cat' => array('ACP_TAGS_MANAGEMENT')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>