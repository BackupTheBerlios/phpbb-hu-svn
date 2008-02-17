<?php
/**
*
* @package site
* @version $Id$
* @copyright (c) 2008 phpbb.hu
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

// Advanced functions related to the filesystem

/**
* List all files of a directory recursively
*/
function scandir_rec($dir, $original_dir_length = 0)
{
	$invisible_file_names = array(".", "..", ".htaccess", ".htpasswd");
	
	$return_data = array();
	if (empty($original_dir_length))
	{
		$original_dir_length = strlen($dir) + 1;
	}
	
	$dir_content = scandir($dir);
	
	foreach($dir_content as $content)
	{
		$path = $dir . '/' . $content;
		if (!in_array($content, $invisible_file_names))
		{
			if (is_file($path) && is_readable($path))
			{
				$return_data[] = substr($path, $original_dir_length);
			}
			elseif (is_dir($path) && is_readable($path))
			{
				$return_data = array_merge($return_data, scandir_rec($path, $original_dir_length));
			}
		}
	}
	
	return $return_data;
}

/**
* Delete a directory and all of its content
*/
function rmdir_rec ($dir_name)
{
	if (!is_dir($dir_name))
	{
		return false;
	}
	
	$dir = opendir($dir_name);
	
	while(($file = readdir($dir)) !== false)
	{
		if ($file != '.' && $file != '..')
		{
			if (is_dir($dir_name . '/' . $file))
			{
				rmdir_rec($dir_name . '/' . $file);
			}
			else
			{
				@unlink($dir_name . '/' . $file);
			}
		}
	}
	
	closedir($dir);
	
	rmdir($dir_name);
}
?>