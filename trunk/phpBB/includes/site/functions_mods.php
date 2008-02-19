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
* MOD package
* Class for magaging MOD packages (downloading, updating) and also for adding language packs/files
* @package site
*/
class mod_pack
{
	// MOD details
	public $mod_id;
	public $data = array();
	public $tags = array();
	public $filename;
		
	// For development
	protected $start_time;
	
	/**
	* Set some basic settings
	*/
	public function __construct($mod_id)
	{
		global $config;
		
		$this->mod_id = $mod_id;
		
		// Enable opening of remote URLs
		@ini_set('allow_url_fopen', 1);
		
		// If memory is not enough try to set it to a higher value (code from install/index.php)
		increase_mem_limit(32);
		
		// For developmental purposes
		$this->start_time = microtime_float();
	}
	
	/**
	* Download the package from phpBB.com
	*/
	public function get_archive()
	{
		global $phpbb_root_path, $config;
		
		$url = 'http://www.phpbb.com/mods/db/download/' . $this->mod_id . '/';
		
		// Create a new cURL resource
		$ch = curl_init();
		// Set URL
		curl_setopt($ch, CURLOPT_URL, $url);
		// Set the function for handling headers (to get the filename)
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, '_read_page_header'));
		// Grab the content of the package
		ob_start();
		// Get the pack
		curl_exec($ch);
		// Store the content of the pack
		file_put_contents($phpbb_root_path . $config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '.zip', ob_get_contents());
		// Stop storing the output
		ob_end_clean();
		// Close cURL resource, and free up system resources
		curl_close($ch);
		
		return true;
	}
	
	/**
	* Handle the headers of a HTML page - callback for cURL
	* Store the name of the downloaded file
	* 
	* @access private
	*/
	protected function _read_page_header($curl_handler, $header)
	{
		if (preg_match('#^Content-Disposition: attachment; filename=([a-z0-9._-]+)\.zip\s?$#is', $header, $match))
		{
			$this->filename = $match[1];
		}
		
		return strlen($header);
	}	
	
	/**
	* Get details of the pack (version, md5, tags etc.)
	*/
	public function get_pack_details()
	{
		$url = 'http://www.phpbb.com/mods/db/index.php?i=misc&mode=display&contrib_id=' . $this->mod_id;
	
		// Parse the file with the DOM parser
		$page = new DOMDocument();
		
		// Load the HTML file, but suppress warnings as the HTML can be not totally valid
		@$page->loadHTMLFile($url);
		
		// Make a SimpleXML object from DOM	
		$page = simplexml_import_dom($page);

		// Get title
		list($title) = $page->xpath("//div[@id='main']/h3");
		$this->data['title'] = (string) $title;
		
		// If the page/MOD does not exists return with an array containing the error key
		if (empty($this->data['title']))
		{
			throw new ModException(array('MOD_NOT_EXISTS'));
		}

		// Get MD5
		$result = $page->xpath("//div[@id='extras']//dl[@class='extra-box download-contrib']//dd");
		preg_match('#MD5 hash: ([a-f0-9]+)$#is', (string) $result[0], $match);
		$this->data['md5'] = $match[1];

		// Get details
		$details_list = $page->xpath("//ul[@class='topiclist forums']/li[@class='row']");
		
		foreach ($details_list as $element)
		{
			$name = isset($element->dl->dt[0]) ? substr($element->dl->dt[0], 0, -1) : false;
			$value = $element->dl->dd;
			
			switch($name)
			{
				case 'Author':
					$attrs = $value->a->attributes();
					preg_match('#u\=([0-9]+)&?#is', (string) $attrs['href'], $match);
					$this->data['author']['id'] = $match[1];
					$this->data['author']['name'] = (string) $value->a;
					break;
				
				case 'Version':
					$this->data['version'] = (string) $value;
					break;
				
				// Tags
				case 'Category':
				case 'Complexity':
				case 'Time':
				case 'phpBB version':
					foreach ($value->ul->children() as $tag_element)
					{
						$attrs = $tag_element->a->attributes();
						preg_match('#mode=group:([a-z]+)&sub=([a-z0-9._-]+)&?#is', (string) $attrs['href'], $match);
						list(, $tagcat_name, $tag_name) = $match;
						
						if (!isset($this->tags[$tagcat_name]))
						{
							$this->tags[$tagcat_name] = array();
						}
						
						$this->tags[$tagcat_name][$tag_name] = array($tag_name, (string) $tag_element->a);
					}			
					break;
			}
		}
		
		return true;
	}
	
	/**
	* Merge MOD and the according language pack together
	*
	* @param bool $test Whether to generate the MOD package or just test the language pack
	*/	
	public function merge_packs($test = false)
	{
		global $phpbb_root_path, $config;
		
		/**
		* Unzip both packages
		*/
		$mod = new compress_zip('r', $phpbb_root_path . $config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '.zip');
		$mod->extract($phpbb_root_path . $config['mods_tmp_dir_path'] . 'mods/');
		
		/* ZipArchive is not currently supported on the server - leave this here for possible later use
		$mod = new ZipArchive();
		$mod->open($config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '.zip');
		$mod->extractTo($config['mods_tmp_dir_path'] . 'mods/');*/
		
		// If no localisation pack exists then we just check the original MOD package to make sure all Hungarian translation files are included
		if (file_exists($phpbb_root_path . $config['mods_tmp_dir_path'] . 'localisations/' . $this->filename . '.zip'))
		{
			$loc = new compress_zip('r', $phpbb_root_path . $config['mods_tmp_dir_path'] . 'localisations/' . $this->filename . '.zip');
			$loc->extract($phpbb_root_path . $config['mods_tmp_dir_path'] . 'localisations/');
		}
		
		/**
		* Merge packages
		*/
		$errors = array();
		
		// Introduce variables with short names for frequently used file paths
		$mod_dir = $phpbb_root_path . $config['mods_tmp_dir_path'] . 'mods/' . $this->filename;
		$loc_dir = $phpbb_root_path . $config['mods_tmp_dir_path'] . 'localisations/' . $this->filename;
		
		// First look at the language files in the mods directory
		if (file_exists($mod_dir . '/root/language/en/') && !file_exists($mod_dir . '/root/language/hu/'))
		{
			$files = scandir_rec($mod_dir . '/root/language/en/');
			
			foreach ($files as $file)
			{
				if (!file_exists($loc_dir . '/root/language/hu/' . $file))
				{
					$errors[] = array('MISSING_LANGUAGE_FILE', 'root/language/hu/' . $file);
				}
				else
				{
					// Check PHP syntax (assume we are on a unix-based system)
					if (substr(shell_exec('php -l ' . $loc_dir . '/root/language/hu/' . $file), 0, 11) == 'Parse error')
					{
						$errors[]= array('SYNTAX_ERROR', 'root/language/hu/' . $file);
					}
					else
					{
						$dir_name = $mod_dir . '/root/language/hu/' . site_dirname($file);
						if (!file_exists($dir_name))
						{
							mkdir($dir_name, 0755, true);
						}
						
						rename($loc_dir . '/root/language/hu/' . $file, $mod_dir . '/root/language/hu/' . $file);
					}
				}
			}
		}
		
		// Next the styles directory
		if (file_exists($mod_dir . '/root/styles/prosilver/imageset/en/') && !file_exists($mod_dir . '/root/styles/prosilver/imageset/hu/'))
		{
			// Check whether prosilver images are in place
			$files = scandir_rec($mod_dir . '/root/styles/prosilver/imageset/en/');
			
			foreach($files as $file)
			{
				if (!file_exists($loc_dir . '/root/styles/prosilver/imageset/hu/' . $file))
				{
					$errors[]= array('MISSING_STYLE_IMAGE', '/root/styles/prosilver/imageset/hu/' . $file);
				}
			}
			
			// Copy all image files
			$files = scandir_rec($loc_dir . '/root/styles/');
			
			foreach ($files as $file)
			{
				if (substr($file, -4) == '.gif' && !file_exists($loc_dir . '/root/styles/' . $file))
				{
						$dir_name = $mod_dir . '/root/styles/' . site_dirname($file);
						if (!file_exists($dir_name))
						{
							mkdir($dir_name, 0755, true);
						}
						
						rename($loc_dir . '/root/styles/' . $file, $mod_dir . '/root/styles/' . $file);
				}
			}
		}
				
		// Copy the entire contrib directory
		if (file_exists($loc_dir . '/contrib/'))
		{
			$files = scandir_rec($loc_dir . '/contrib/');
			
			foreach($files as $file)
			{
				if (!file_exists($mod_dir . '/contrib/' . $file))
				{
					$dir_name = $mod_dir . '/contrib/' . site_dirname($file) . '/';
					if (!file_exists($dir_name))
					{
						mkdir($dir_name, 0755, true);
					}
					
					rename($loc_dir . '/contrib/' . $file, $mod_dir . '/contrib/' . $file);
				}
			}
		}
		
		// Now the Hungarian MODX file
		if (file_exists($loc_dir . '/languages/hu.xml') && !file_exists($mod_dir . '/languages/hu.xml'))
		{
			if (!file_exists($mod_dir . '/languages/'))
			{
				mkdir($mod_dir . '/languages/', 0755);
			}
			
			rename($loc_dir . '/languages/hu.xml', $mod_dir . '/languages/hu.xml');
		}
		
		// Style localisations
		if (file_exists($loc_dir . '/templates/'))
		{
			$files = scandir_rec($loc_dir . '/templates/');
			foreach ($files as $file)
			{
				if (preg_match('#^([^/]+)\/hu\.xml$#is', $file, $match) && !file_exists($mod_dir . '/templates/' . $file))
				{
					mkdir($mod_dir . '/templates/' . $match[1], 0755, true);
					rename($loc_dir . '/templates/' . $file, $mod_dir . '/templates/' . $file);
				}
			}
		}
		
		// And finally merge the translation and the original version of install.xml (or alternatively MOD_NAME.xml)
		// @todo implement the merge
		
		/**
		* "Zip everything back"
		*/
		if (!empty($errors))
		{
			throw new ModException($errors);
		}
		
		if ($test)
		{
			return true;
		}
		
		// Remove old file?
		if (file_exists($config['downloads_path'] . '/mods/' . $this->filename . '.zip'))
		{
			unlink($config['downloads_path'] . '/mods/' . $this->filename . '.zip');
		}
		
		/* ZipArchive is not supported on the server - but leave this here for possible later use
		$final = new DirZipArchive();
		$final->open('$config['downloads_path'] . '/mods/' . $this->filename . '.zip', ZIPARCHIVE::CREATE);
		$final->addDir($mod_dir . '/', $this->filename);
		$final->close();*/
		
		// Generate final MOD pack
		$final = new compress_zip('w', $config['downloads_path'] . '/mods/' . $this->filename . '.zip');	
		$filelist = scandir_rec($mod_dir . '/');	
		foreach ($filelist as $file)
		{
			// Add file to archive
			$final->add_custom_file($mod_dir . '/' . $file, $this->filename . '/' . $file);
		}	
		$final->close();
		
		$this->data['size'] = filesize($config['downloads_path'] . '/mods/' . $this->filename . '.zip');
		
		return true;
	}
	
	/**
	* Do cleanup: remove every temporarily created directory and file
	*/
	public function cleanup()
	{
		global $config;
		
		rmdir_rec($config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '/');
		rmdir_rec($config['mods_tmp_dir_path'] . 'localisations/' . $this->filename . '/');
		
		if (file_exists($config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '.zip'))
		{
			unlink($config['mods_tmp_dir_path'] . 'mods/' . $this->filename . '.zip');
		}
		if (file_exists($config['mods_tmp_dir_path'] . 'localisations/' . $this->filename . '.zip'))
		{
			unlink($config['mods_tmp_dir_path'] . 'localisations/' . $this->filename . '.zip');
		}
	}
	
	/**
	* Print out the time currently spent (for developmental purposes)
	*/
	protected function time($log)
	{
		print $log . ': ' . number_format(microtime_float() - $this->start_time, 8) . "<br />\n";
		flush();
	}
}

/**
* Class for adding dirs with files and subdirectories
* Source: http://hu.php.net/manual/hu/ref.zip.php#78940
* 
* @todo Remove this class as there is no need for it since we don't use ZipArchive for packaging anymore
*
* <code>
*  $archive = new DirZipArchive;
*  // .....
*  $archive->addDir('test/blub', 'blub');
* </code>
* @author Nicolas Heimann 
*/
class DirZipArchive extends ZipArchive
{
	/**
	* Add a dir with files and subdirectories to the archive
	*
	* @param string $location Real Location
	* @param string $name Name in Archive
	* @access private
	*/
	public function addDir($location, $name)
	{
		$this->addEmptyDir($name);
		
		$this->addDirDo($location, $name);
	}

	/**
	* Add files & Directories to archive
	*
	* @param string $location Real location
	* @param string $name Name in archive
	* @access private
	*/
	protected function addDirDo($location, $name)
	{
		$name .= '/';
		$location .= '/';
		
		// Read all files in tje directories
		$dir = opendir($location);
		while (($file = readdir($dir)) !== false)
		{
			if ($file != '.' && $file != '..')
			{
				// Recursive, if it is a directory: FlxZipArchive::addDir(), else ::File();
				$do = (is_dir($location . $file)) ? 'addDir' : 'addFile';
				$this->$do($location . $file, $name . $file);
			}
		}
	}
}

/**
* Custom exception class for handling errors occuring while querying the phpBB.com MOD DB
*/
class ModException extends Exception
{
	protected $errors = array();
	
	// Redefine the exception so message isn't optional
	public function __construct($error, $code = 0)
	{
		$this->errors = $error;

		// Make sure everything is assigned properly
		parent::__construct('MOD packaging error', $code);
	}

	// Return error
	public function getErrors()
	{
		global $user;
		
		$errors = array();

		foreach ($this->errors as $error)
		{
			if (is_array($error))
			{
				$errors[] = sprintf((isset($user->lang['NO_MOD_' . $error[0]])) ? $user->lang['NO_MOD_' . $error[0]] : $user->lang[$error[0]], $error[1]);
			}
			else
			{
				$errors[] = (isset($user->lang['NO_MOD_' . $error])) ? 'NO_MOD_' . $error : $error;
			}
		}
		
		return $errors;
	}
}

/*
* Custom version of dirname that is recognised by file_exists()
*/
function site_dirname($file)
{
	return ($dirname = dirname($file)) == '.' ? '' : $dirname . '/';
}

?>