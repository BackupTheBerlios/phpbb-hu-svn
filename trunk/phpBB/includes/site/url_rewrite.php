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

function microtime_float() 
{ 
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/**
* URL REWRITER
* A class for rewriting URLs to look pretty and make them shorter
* @package phpBB3
*/
class url_rewriter
{
	// Preg expressions
	protected $urls;

	// Some config options for development
	public $enabled = true;
	public $time = 0;

	/**
	* Initialize (load the urls, etc.)
	*/
	public function __construct()
	{
		global $cache, $phpbb_hook;

		// Get the prepared urls
		//$this->urls = $cache->get('_urls');
		if (!$this->urls)
		{
			$this->urls = $this->prepare_urls();
			$cache->put('_urls', $this->urls);
		}

		// Check if we are in the ACP -> disable url rewriting
		if (substr(dirname($_SERVER['SCRIPT_NAME']), -3) == 'adm')
		{
			$this->enabled = false;
		}

		// Actually enable the url rewriting
		// In development mode it can be turned off by specifing the urw parameter with a value of 0
		if ($this->enabled && !(defined('DEBUG_EXTRA') && request_var('urw', 1) == 0))
		{
			$phpbb_hook->register('append_sid', 'append_sid_rewrite');
		}
	}

	/**
	* Rewrite an url
	*
	* @param string $file The file the url points to (ie. viewforum.php)
	* @param string $params The parameters part of the url (ie. f=1&start=5)
	*/
	public function rewrite($file, $params = '')
	{
		if (!$this->enabled)
		{
			return $file . '?' . $params;
		}

		// First cut off the directory
		$file = preg_replace('#^(\.?\.\/)+#', '', $file);
		/*if (substr($file, 0, 2) == './')
		{
			$file = substr($file, 2);
		}*/
		
		if (!isset($this->urls[$file]))
		{
			return (empty($params)) ? $file : $file . '?' . $params;
		}

		$url = $params;
		foreach ($this->urls[$file] as $item)
		{
			$url = preg_replace($item[0], $item[1], $url);
			if ($url != $params)
			{
				break;
			}
		}

		if (strpos($url, '?') === false && ($sep_pos = strpos($url, '&')) !== false)
		{
			$url = substr($url, 0, $sep_pos) . '?' . substr($url, $sep_pos + 1);
		}

		return $url;
	}


	/**
	* Load the data files (containing the urls) and process it for later use (and cache of course)
	*/
	protected function prepare_urls()
	{
		global $phpEx, $phpbb_root_path;

		// Load the data
		include($phpbb_root_path . 'includes/site/data/urls.' . $phpEx);

		// Begin filtering the urls array out
		$store = array();

		foreach ($urls as $file => $items)
		{
			$store[$file] = array();

			foreach ($items as $item)
			{
				// While developping there can be some empty entries
				if (empty($item[1]))
				{
					continue;
				}

				// Workaround for urls with no (at all) or no processed parameters
				if ($item[0] == '^')
				{
					$store[$file][] = array(
						0 => '#^(.+)=(.+)$#i',
						1 => $item[1] . '?$1=$2'
						);

					$store[$file][] = array(
						0 => '#' . $item[0] . '$#i',
						1 => $item[1]
						);
				}
				else
				{
					$store[$file][] = array(
						0 => '#' . $item[0] . '#i',
						1 => $item[1]
						);
				}
			}
		}

		return $store;
	}

	public function __destruct()
	{
		//print number_format($this->time, 8);
	}
}


/**
* Append session id to url (changed for url rewrite)
*
* @param object &$hook The phpbb_hook object
* @param string $url The url the session id needs to be appended to (can have params)
* @param mixed $params String or array of additional url parameters
* @param bool $is_amp Is url using &amp; (true) or & (false)
* @param string $session_id Possibility to use a custom session id instead of the global one
*/
function append_sid_rewrite(&$hook, $url, $params = false, $is_amp = true, $session_id = false)
{
	global $_SID, $_EXTRA_URL;
	global $url_rewriter;

	//$start_time = microtime_float();

	$anchor = '';
	if (strpos($url, '#') !== false)
	{
		list($url, $anchor) = explode('#', $url, 2);
		$anchor = '#' . $anchor;
	}
	elseif (!is_array($params) && strpos($params, '#') !== false)
	{
		list($params, $anchor) = explode('#', $params, 2);
		$anchor = '#' . $anchor;
	}

	// Assign sid if session id is not specified
	if ($session_id === false)
	{
		$session_id = $_SID;
	}

	// First create the $params parameter

	// Build string if parameters are specified as array
	if (is_array($params))
	{
		$output = array();

		foreach ($params as $key => $item)
		{
			if ($item === NULL)
			{
				continue;
			}

			if ($key == '#')
			{
				$anchor = '#' . $item;
				continue;
			}

			$output[] = $key . '=' . $item;
		}

		$params = implode('&', $output);
	}
	// Use & instead of &amp; for easier handling in url rewrites
	elseif ($params != false)
	{
		$params = str_replace('&amp;', '&', $params);
	}

	// If there are any parameters in the url then cut them off and place them in $params
	if (($qm_pos = strpos($url, '?')) !== false)
	{
		$add_params = substr($url, $qm_pos + 1);

		if ($is_amp === true)
		{
			$add_params = str_replace('&amp;', '&', $add_params);
		}

		$params = (empty($params)) ? $add_params : $params . '&' . $add_params;

		$url = substr($url, 0, $qm_pos);
	}

	// Appending custom url parameter?
	$params .= (!empty($_EXTRA_URL)) ? ((empty($params)) ? '' : '&') . implode('&', $_EXTRA_URL) : '';

	// Now rewrite the url
	$url = $url_rewriter->rewrite($url, $params);

	// Transform & back to its entity equivalent
	if ($is_amp == true)
	{
		$url = str_replace ('&', '&amp;', $url);
	}

	// And finally append the sid
	if ($session_id)
	{
		$amp_delim = ($is_amp) ? '&amp;' : '&';
		$url .= ((strpos($url, '?') === false) ? '?' : $amp_delim) . 'sid=' . $session_id;
	}

	//$url_rewriter->time += microtime_float() - $start_time;

	return $url . $anchor;
}

?>