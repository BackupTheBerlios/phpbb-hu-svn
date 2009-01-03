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
class acp_bugs_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_bugs',
			'title'		=> 'ACP_BUG_TRACKER',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'projects'	=> array('title' => 'ACP_BUG_TRACKER_PROJECTS', 'auth' => 'acl_a_bug_tracker', 'cat' => array('ACP_BUG_TRACKER')),
				'statuses'	=> array('title' => 'ACP_BUG_TRACKER_STATUSES', 'auth' => 'acl_a_bug_tracker', 'cat' => array('ACP_BUG_TRACKER')),
				'versions'	=> array('title' => 'ACP_BUG_TRACKER_VERSIONS', 'auth' => 'acl_a_bug_tracker', 'cat' => array('ACP_BUG_TRACKER')),
				'components'=> array('title' => 'ACP_BUG_TRACKER_COMPONENTS', 'auth' => 'acl_a_bug_tracker', 'cat' => array('ACP_BUG_TRACKER')),
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