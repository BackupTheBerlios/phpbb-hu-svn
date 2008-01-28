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
	protected $mod_id;
	protected $data = array();
	protected $tags = array();
	protected $filename;
	
	// Temporary directory for the handling of files
	public $tmp_dir = 'files/private/mods/tmp/';
		
	// For development
	protected $start_time;
	
	/**
	* Set some basic settings
	*/
	public function __construct($mode, $mod_id)
	{
		$this->mod_id = $mod_id;
		
		/*if ($mode == 'new')
		{
			$this->get_pack();
		}
		elseif ($mode == 'update')
		{
			$this->get_pack();
		}*/
		
		// Enable opening of remote URLs
		@ini_set('allow_url_fopen', 1);
		
		// If memory is not enough try to set it to a higher value (code from install/index.php)
		increase_mem_limit(32);
		
		// For developmental purposes
		$this->start_time = microtime_float();
		
		// Development: test functions
		$this->get_pack_details();
		$this->get_pack();
		
		$this->merge_packs();
		
		//$this->cleanup();
	}
	
	/**
	* Download the package from phpBB.com
	*/
	public function get_pack()
	{
		$url = 'http://www.phpbb.com/mods/db/download/' . $this->mod_id . '/';
		
		$this->time('before downloading');
		
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
		file_put_contents($this->tmp_dir . 'mods/' . $this->filename . '.zip', ob_get_contents());
		// Stop storing the output
		ob_end_clean();
		// Close cURL resource, and free up system resources
		curl_close($ch);
		
		$this->time('after download');
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
	
		$this->time('begin');
		
		// Parse the file with the DOM parser
		$page = new DOMDocument();

		$this->time('loadbegin');
		
		// Load the HTML file, but suppress warnings as the HTML can be not totally valid
		@$page->loadHTMLFile($url);
		
		$this->time('loaded');
		
		// Make a SimpleXML object from DOM	
		$page = simplexml_import_dom($page);
		
		$this->time('imported');
		
		// Get title
		list($title) = $page->xpath("//div[@id='main']/h3");
		$this->data['title'] = (string) $title;
		
		$this->time('title');
		
		// Get MD5
		$result = $page->xpath("//div[@id='extras']//dl[@class='extra-box download-contrib']//dd");
		preg_match('#MD5 hash: ([a-f0-9]+)$#is', (string) $result[0], $match);
		$this->data['md5'] = $match[1];
		
		$this->time('md5');
		
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
						
						$this->tags[$tagcat_name][] = $tag_name;
					}			
					break;
			}
		}
		
		$this->time('details');
	}
	
	/**
	* Merge MOD and the according language pack together
	*/
	public function merge_packs()
	{
		/**
		* Unzip both packages
		*/
		$mod = new ZipArchive();
		$mod->open($this->tmp_dir . 'mods/' . $this->filename . '.zip');
		$mod->extractTo($this->tmp_dir . 'mods/');
		
		$loc = new ZipArchive();
		$loc->open($this->tmp_dir . 'localisations/' . $this->filename . '.zip');
		$loc->extractTo($this->tmp_dir . 'localisations/');
		
		/**
		* Merge packages
		*/
		$errors = array();
		
		// First look at the language files in the mods directory
		if (file_exists($this->tmp_dir . 'mods/' . $this->filename . '/root/language/en/') && !file_exists($this->tmp_dir . 'mods/' . $this->filename . '/root/language/hu/'))
		{
			$files = scandir_rec($this->tmp_dir . 'mods/' . $this->filename . '/root/language/en/');
			
			foreach ($files as $file)
			{
				if (!file_exists($this->tmp_dir . 'localisations/' . $this->filename . '/root/language/hu/' . $file))
				{
					$errors[] = array('MISSING_LANGUAGE_FILE', 'root/language/hu/' . $file);
				}
				else
				{
					// Check PHP syntax (assume we are on a unix-based system)
					if (substr(shell_exec('php -l ' . $this->tmp_dir . 'localisations/' . $this->filename . '/root/language/hu/' . $file), 0, 11) == 'Parse error')
					{
						$errors[]= array('SYNTAX_ERROR', 'root/language/hu/' . $file);
					}
					else
					{
						$dir_name = $this->tmp_dir . 'mods/' . $this->filename . '/root/language/hu/' . dirname($file);
						if (!file_exists($dir_name))
						{
							mkdir($dir_name, 0755, true);
						}
						
						rename($this->tmp_dir . 'localisations/' . $this->filename . '/root/language/hu/' . $file, $this->tmp_dir . 'mods/' . $this->filename . '/root/language/hu/' . $file);
					}
				}
			}
		}
		
		// Next the styles directory
		if (file_exists($this->tmp_dir . 'mods/' . $this->filename . '/root/styles/prosilver/imageset/en/') && !file_exists($this->tmp_dir . 'mods/' . $this->filename . '/root/styles/prosilver/imageset/hu/'))
		{
			// Check whether prosilver images are in place
			$files = scandir_rec($this->tmp_dir . 'mods/' . $this->filename . '/root/styles/prosilver/imageset/en/');
			
			foreach($files as $file)
			{
				if (!file_exists($this->tmp_dir . 'localisations/' . $this->filename . '/root/styles/prosilver/imageset/hu/' . $file))
				{
					$errors[]= array('MISSING_STYLE_IMAGE', '/root/styles/prosilver/imageset/hu/' . $file);
				}
			}
			
			// Copy all image files
			$files = scandir_rec($this->tmp_dir . 'localisations/' . $this->filename . '/root/styles/');
			
			foreach ($files as $file)
			{
				if (substr($file, -4) == '.gif' && !file_exists($this->tmp_dir . 'localisations/' . $this->filename . '/root/styles/' . $file))
				{
						$dir_name = $this->tmp_dir . 'mods/' . $this->filename . '/root/styles/' . dirname($file);
						if (!file_exists($dir_name))
						{
							mkdir($dir_name, 0755, true);
						}
						
						rename($this->tmp_dir . 'localisations/' . $this->filename . '/root/styles/' . $file, $this->tmp_dir . 'mods/' . $this->filename . '/root/styles/' . $file);
				}
			}
		}
				
		// Copy the entire contrib directory
		if (file_exists($this->tmp_dir . 'localisations/' . $this->filename . '/contrib/'))
		{
			$files = scandir_rec($this->tmp_dir . 'localisations/' . $this->filename . '/contrib/');
			
			foreach($files as $file)
			{
				if (!file_exists($this->tmp_dir . 'mods/' . $this->filename . '/contrib/' . $file))
				{
					$dir_name = $this->tmp_dir . 'mods/' . $this->filename . '/contrib/' . dirname($file);
					if (!file_exists($dir_name))
					{
						mkdir($dir_name, 0755, true);
					}
					
					rename($this->tmp_dir . 'localisations/' . $this->filename . '/contrib/' . $file, $this->tmp_dir . 'mods/' . $this->filename . '/contrib/' . $file);
				}
			}
		}
		
		// Now the Hungarian MODX file
		if (file_exists($this->tmp_dir . 'localisations/' . $this->filename . '/languages/hu.xml'))
		{
			if (!file_exists($this->tmp_dir . 'mods/' . $this->filename . '/languages/'))
			{
				mkdir($this->tmp_dir . 'mods/' . $this->filename . '/languages/', 0755);
			}
			
			rename($this->tmp_dir . 'localisations/' . $this->filename . '/languages/hu.xml', $this->tmp_dir . 'mods/' . $this->filename . '/languages/hu.xml');
		}
		
		// Style localisations
		$files = scandir_rec($this->tmp_dir . 'localisations/' . $this->filename . '/templates/');
		foreach ($files as $file)
		{
			if (preg_match('#^([^/]+)\/hu\.xml$#is', $file, $match))
			{
				mkdir($this->tmp_dir . 'mods/' . $this->filename . '/templates/' . $match[1], 0755, true);
				rename($this->tmp_dir . 'localisations/' . $this->filename . '/templates/' . $file, $this->tmp_dir . 'mods/' . $this->filename . '/templates/' . $file);
			}
		}
		
		// And finally merge the translation and the original version of install.xml (or alternatively MOD_NAME.xml)
		// @todo implement the merge
		
		/**
		* "Zip everything back"
		*/
		if (!empty($errors))
		{
			return $errors;
		}
		
		// Remove old file?
		/*if (file_exists('files/downloads/mods/' . $this->filename . '.zip'))
		{
			unlink('files/downloads/mods/' . $this->filename . '.zip');
		}*/
		
		$final = new DirZipArchive();
		$final->open('files/downloads/mods/' . $this->filename . '.zip', ZIPARCHIVE::CREATE);
		$final->addDir($this->tmp_dir . 'mods/' . $this->filename . '/', $this->filename);
		$final->close();
		
		return true;
	}
	
	/**
	* Do cleanup: delete every temporarily created directory and file
	*/
	public function cleanup()
	{
		rmdir_rec($this->tmp_dir . 'mods/' . $this->filename . '/');
		rmdir_rec($this->tmp_dir . 'localisations/' . $this->filename . '/');
		
		unlink($this->tmp_dir . 'mods/' . $this->filename . '.zip');
		unlink($this->tmp_dir . 'localisations/' . $this->filename . '.zip');
	}
	
	/**
	* Print out the time currently spent
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
	private function addDirDo($location, $name)
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

?>