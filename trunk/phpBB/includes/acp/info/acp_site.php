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
class acp_site_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_site',
			'title'		=> 'ACP_SITE_MANAGEMENT',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'		=> array('title' => 'ACP_SITE_SETTINGS', 'auth' => 'acl_a_site', 'cat' => array('ACP_SITE_MANAGEMENT')),
				//'pages'			=> array('title' => 'ACP_PAGES', 'auth' => 'acl_a_site', 'cat' => array('ACP_SITE_MANAGEMENT')),
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