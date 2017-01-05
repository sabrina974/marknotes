<?php                 /* REQUIRES PHP 7.x AT LEAST */

/**
 * Author : AVONTURE Christophe - https://www.aesecure.com
 * 
 * Written date : December 14 2016 
 * 
 * PHP Version : This script has been developed for PHP 7+, the script won't run with PHP 5.x
 * 
 * Put this script in a website root folder (f.i. site /documentation) and create a subfolder called "docs" (see DOC_FOLDER constant).
 * In "docs", create as many subfolders you want and store there your markdown (.md) files.
 * Access to this script with your browser like f.i. http://localhost/documentation/index.php 
 * The script will display the list of all .md files (found in the folder's structure) and, by clicking on a .md file,
 * will display his html output (created on-the-fly), the html version will be saved on the disk. 
 * 
 * History :
 * 
 * 2017-01-05 : + Add custom.js to allow the end user to add his own script
 *              + Add a IMG_MAX_WIDTH constant to be sure that images will be correctly resized if too big
 * 2017-01-04 : + Add Print preview feature (thanks to https://github.com/etimbo/jquery-print-preview-plugin)
 *              + Remove icons (images) and use font-awesome instead
 * 2017-01-03 : + Improve add icons (based on jQuery and no more pure css)
 *              + Add filtering on folder name : just click on a folder name and the list will be limited to that folder
 *              + Start editing code
 *              + Remove leading / ending spaces before searching
 *              + Add Google font support (node "page::google_font" in the settings.json file)
 * 2016-12-30 : + Search supports encrypted data now
 * 2016-12-21 : + Add search functionality, add comments, add custom.css, 
 *              + Add change a few css to try to make things clearer, force links (<a href="">) to be opened in a new tab
 * 2016-12-19 : + Add support for encryption (tag <encrypt>)
 * 2016-12-14 : First version
 */

// PHP 7 : force the use of the correct type
declare(strict_types=1);

define('DEBUG',TRUE);

// When images are too big, force a resize by css to a max-width of ...
define('IMG_MAX_WIDTH','800');

// -------------------------------------------------------------------------------------------------------------------------
// 
// Can be overwritten in settings.json

define('OUTPUT_HTML',TRUE);
define('HTML_TEMPLATE',
   '<!DOCTYPE html>'.
   '<html lang="en">'.
      '<head>'.
         '<meta http-equiv="Cache-control" content="public">'.
         '<meta charset="utf-8"/>'.
         '<meta http-equiv="content-type" content="text/html; charset=UTF-8" />'.
         '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'.
         '<meta http-equiv="X-UA-Compatible" content="IE=9; IE=8;" />'.
         '<title>%TITLE%</title>'.
         '<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">'.
         '%FONT%'.
      '</head>'.
      '<body>'.
         '<page><div class="container">%CONTENT%</div></page>'.
      '</body>'.
      '</html>');

define('APP_NAME','Notes management (c) Christophe Avonture');

// Folder in this application where .md files are stored
define('DOC_FOLDER','docs');

// Max allowed size for the search string
define ('SEARCH_MAX_WIDTH',100);

// Default text, english
define('CONFIDENTIAL','confidential');
define('EDIT_FILE','Edit');
define('EDITOR','C:\Windows\System32\notepad.exe'); // default editor
define('IS_ENCRYPTED','This information is encrypted in the original file and decoded here for screen display');
define('OPEN_HTML','Open in a new window');
define('SEARCH_PLACEHOLDER','Type here keywords and search for them');
define('SEARCH_NO_RESULT','No result found');

set_time_limit(0);

if(!defined('DS')) define('DS',DIRECTORY_SEPARATOR);

class aeSecureFct {
	
   /**
    * Return the current URL
    * 
    * @param type $use_forwarded_host
    * @param type $bNoScriptName          If FALSE, only return the URL and folders name but no script name (f.i. remove index.php and parameters if any)
    * @return type string
    */  
   static public function getCurrentURL(bool $use_forwarded_host=FALSE, bool $bNoScriptName=FALSE) : string {
      $ssl      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on');
      $sp       = strtolower($_SERVER['SERVER_PROTOCOL']);
      $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl)?'s':'');
      $port     = $_SERVER['SERVER_PORT'];
      $port     = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
      $host     = ($use_forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
      $host     = isset($host) ? $host : $_SERVER['SERVER_NAME'].$port;
      return $protocol .'://'.$host.($bNoScriptName===TRUE?dirname($_SERVER['REQUEST_URI']).'/':$_SERVER['REQUEST_URI']);
   } // function getCurrentURL

   /**
    * Safely read posted variables
    * 
    * @param type $name          f.i. "password"
    * @param type $type          f.i. "string"
    * @param type $default       f.i. "default"
    * @return type
    */
   static public function getParam(string $name, string $type='string', $default='', bool $base64=false, int $maxsize=0) {
      
      $tmp='';
      $return=$default;
      
      if (isset($_POST[$name])) {
         if (in_array($type,array('int','integer'))) {
            $return=filter_input(INPUT_POST, $name, FILTER_SANITIZE_NUMBER_INT);
         } elseif ($type=='boolean') {
            // false = 5 characters
            $tmp=substr(filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING),0,5);
            $return=(in_array(strtolower($tmp), array('on','true')))?true:false;
         } elseif ($type=='string') {
            $return=filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);    
            if($base64===true) $return=base64_decode($return);
            if($maxsize>0) $return=substr($return, 0, $maxsize);
         } elseif ($type=='unsafe') {
            $return=$_POST[$name];            
         }
		 
      } else { // if (isset($_POST[$name]))
     
         if (isset($_GET[$name])) {
            if (in_array($type,array('int','integer'))) {
               $return=filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
            } elseif ($type=='boolean') {
               // false = 5 characters
               $tmp=substr(filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING),0,5);
               $return=(in_array(strtolower($tmp), array('on','true')))?true:false;
            } elseif ($type=='string') {
               $return=filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);    
               if($base64===true) $return=base64_decode($return);    
               if($maxsize>0) $return=substr($return, 0, $maxsize);             
            } elseif ($type=='unsafe') {
               $return=$_GET[$name];            
            }
         } // if (isset($_GET[$name])) 
				
      } // if (isset($_POST[$name]))
      
      if ($type=='boolean') $return=(in_array($return, array('on','1'))?true:false);
      
      return $return;	   
	  
   } // function getParam()
  
   /**
    * Generic function for adding a js in the HTML response
    * @param type $localfile
    * @param type $weblocation
    * @return string
    */
   static public function addJavascript(string $localfile, string $weblocation='', bool $defer=false) : string {
      
      $return='';
   
      // Perhaps the script (aesecure_quickscan.php) is a symbolic link so __DIR__ is the folder where the 
      // real file can be found and SCRIPT_FILENAME his link, the line below should therefore not be used anymore
      //if (is_file(dirname(__DIR__).DS.str_replace('/',DS,$localfile))) {

      if (is_file(str_replace('/',DS,dirname($_SERVER['SCRIPT_FILENAME'])).DS.$localfile)) {
        $return='<script '.($defer==true?'defer="defer" ':'').'type="text/javascript" src="'.$localfile.'"></script>';
      } else {
         if($weblocation!='') $return='<script '.($defer==true?'defer="defer" ':'').'type="text/javascript" src="'.$weblocation.'"></script>';
      }
      
      return $return;
      
   } // function addJavascript()  
   
   /**
    * Generic function for adding a css in the HTML response
    * @param type $localfile
    * @param type $weblocation
    * @return string
    */
   static public function addStylesheet(string $localfile, string $weblocation='') : string {
      
      $return='';
      
      // Perhaps the script (aesecure_quickscan.php) is a symbolic link so __DIR__ is the folder where the 
      // real file can be found and SCRIPT_FILENAME his link, the line below should therefore not be used anymore
      //if (is_file(dirname(__DIR__).DS.str_replace('/',DS,$localfile))) {
      if (is_file(str_replace('/',DS,dirname($_SERVER['SCRIPT_FILENAME'])).DS.$localfile)) {
         $return='<link href="'.$localfile.'" rel="stylesheet" />';
      } else {
         if($weblocation!='') $return='<link href="'.$weblocation.'" rel="stylesheet" />';
      }
      
      return $return;
      
   } // function addStylesheet()
   
} // class aeSecureFct

class aeSecureFiles {
	
   /**
    * Check if a file exists and return FALSE if not.  Disable temporarily errors to avoid warnings f.i. when the file
    * isn't reachable due to open_basedir restrictions
    * 
    * @param type $filename
    * @return boolean
    */
   static public function fileExists(string $filename) : bool {
      
      if ($filename=='') return FALSE;
     
      $errorlevel=error_reporting();
      error_reporting(0);

      $wReturn = is_file($filename);

      error_reporting($errorlevel);

      return $wReturn;
    
   } // function fileExists()
   
   /**
    * Check if a file exists and return FALSE if not.  Disable temporarily errors to avoid warnings f.i. when the file
    * isn't reachable due to open_basedir restrictions
    * 
    * @param type $filename
    * @return boolean
    */
   static private function folderExists(string $folderName) : bool {
      
      if ($folderName=='') return FALSE;

      $errorlevel=error_reporting();
      error_reporting($errorlevel & ~E_NOTICE & ~E_WARNING);

      $wReturn = is_dir($folderName);

      error_reporting($errorlevel);

      return $wReturn;

   } // function folderExists()
   
   /**
    * Recursive glob : retrieve all files that are under $path (if empty, $path is the root folder of the website)
    * 
    * For instance : aeSecureFct::rglob($pattern='.htaccess',$path=$rootFolder); to find every .htaccess files on the server
    * If folders should be skipped : 
    *    aeSecureFct::rglob('.htaccess',$rootFolder,0,array('aesecure','administrator'))
    * 
    * @param type $pattern
    * @param type $path
    * @param type $flags
    * @param type $arrSkipFolder   Folders to skip... (subfolders will be also skipped)
    * @return type
    */
   static public function rglob(string $pattern='*', string $path='', int $flags=0, $arrSkipFolder=null) : array {
      
      static $adjustCase=false;
      
      // glob() is case sensitive so, search for PHP isn't searching for php.
      // Here, the pattern will be changed to be case insensitive.
      // "*.php" will be changed to "*.[pP][hH][pP]"
      
      if (($pattern!='') && ($adjustCase==false)) {
         $length = strlen($pattern);
         $tmp=$pattern;
         $pattern='';
         for ($i=0; $i<$length; $i++) {
            $pattern.=(ctype_alpha($tmp[$i]) ? '['.strtolower($tmp[$i]).strtoupper($tmp[$i]).']' : $tmp[$i]);
         }
         // Do this only once
         $adjustCase=true;
      }         
      
      // If the "$path" is one of the folder to skip, ... skip it.
      
      if (($arrSkipFolder!=null) && (count($arrSkipFolder)>0)) {
         foreach ($arrSkipFolder as $folder) {
            if (self::startsWith($path, $folder)) return null;
         } // foreach
         
      } // if (($arrSkipFolder!=null) && (count($arrSkipFolder)>0))
      
      $paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR);
      $files=glob(rtrim($path,DS).DS.$pattern, $flags);
      
      foreach ($paths as $path) {
         
         if( self::folderExists($path)) { 

            // Avoid recursive loop when the folder is a symbolic link
            if (rtrim(str_replace('/',DS,$path),DS)==realpath($path)) { 
               $arr=self::rglob($pattern, $path, $flags, $arrSkipFolder);
               if (($arr!=null) && (count($arr)>0)) $files=array_merge($files,$arr);
            } else {
               // $path is a symbolic link.  Doing a glob on a symbolic link will create a recursive
               // call and will crash the script
            }
            
         } // if(!(is_link($path))) {
         
      } // foreach
      
      // Don't use the / notation but well the correct directory separator
      foreach ($files as $key=>$value) $files[$key]=str_replace('/',DS,$value);
	  
      @sort($files);
	  
      return $files;
	  
   } // function rglob()
   
   static public function replace_extension(string $filename, string $new_extension) : string {
      $info = pathinfo($filename);
      return dirname($filename).DS.$info['filename'].'.'.$new_extension;
   } // function replace_extension
   
} // class aeSecureFiles 

/**
 * Encryption class.  Use SSL for the encryption.  
 * 
 * Partially based on @link : http://php.net/manual/fr/function.openssl-decrypt.php#111832
 */
class aeSecureEncrypt {
   
   private $_method='';
   private $_password='';
   private $_iv='';
   
   /**
    * Initialize the class
    * 
    * @param string $password  The password to use for the encryption
    * @param string $method    OPTIONAL, If not mentionned, will be 'aes-256-ctr'
    */
   function __construct(string $password, string $method) {
      
      $this->_password=$password;
      
      if (trim($method)==NULL) {
         $this->_method='aes-256-ctr';
      } else {      
         $this->_method=$method;
      }

      // Dynamically generate an "IV" i.eI and initialization vector that will ensure cypher to be unique 
      // (http://stackoverflow.com/questions/11821195/use-of-initialization-vector-in-openssl-encrypt)
      // And concatenate that "IV" to the encrypted texte
      
      $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
      $this->_iv= mcrypt_create_iv($iv_size, MCRYPT_RAND);;

   } // function __construct()   
   
   /**
    * Encrypt a string by using SSL
    * 
    * @param string $data      The string that should be encrypted
    * @param string $password  OPTIONAL, If not mentionned, use the password define during the class initialization
    * @param string $method    OPTIONAL, If not mentionned, will be 'aes-256-ctr'
    * @return type             The string, encrypted.  The first characters will contains the "Initialization vector", required for the decryption
    */
   public function sslEncrypt(string $data, $password) : string {
      
      if ($password===NULL) $password=$this->_password;
      
      if(function_exists('openssl_encrypt')) {
         return urlencode($this->_iv.openssl_encrypt(urlencode($data), $this->_method, $password, 0, $this->_iv));
      } else {
         return urlencode($this->_iv.exec("echo \"".urlencode($data)."\" | openssl enc -".urlencode($this->_method)." -base64 -nosalt -K ".bin2hex($password)." -iv ".bin2hex($this->_iv)));
      }
      
   } // function sslEncrypt()
   
   /**
    * Decrypt a SSL encrypted string
    * 
    * @param string $data   The string to decrypt.  The first characters contains the "Initialiation vector"
    * @param string $password  OPTIONAL, If not mentionned, use the password define during the class initialization
    * @return type          The string, decrypted
    */
   public function sslDecrypt(string $data, $password) : string {
  
      if ($password===NULL) $password=$this->_password;
      
      $tmp=urldecode($data);
      
      $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
      $iv = substr($tmp, 0, $iv_size);

      if(function_exists('openssl_decrypt')) {
         return trim(urldecode(openssl_decrypt(substr($tmp, $iv_size), $this->_method, $password, 0, $iv)));
      } else {
        return trim(urldecode(exec("echo \"".$tmp."\" | openssl enc -".$this->_method." -d -base64 -nosalt -K ".bin2hex($password)." -iv ".bin2hex($this->_iv))));
      }

   } // function sslDecrypt()
   
} // class aeSecureEncrypt

class aeSecureMarkdown {
   
   private $_json=null;                // JSON, contains the content of the settings.json file

   private $_rootFolder='';            // root folder of the web application (f.i. "C:\Christophe\Documents\")
   
   private $_settingsEditor='';        // defaut editor program 
   
   private $_settingsFontName='';      // Google fontname if specified in the settings.json file
   
   private $_settingsDocsFolder='';    // subfolder f.i. 'docs' where markdown files are stored (f.i. "Docs\")
   private $_settingsLanguage='en';    // user's language
   
   private $_encryptionMethod='';      // Method to use for the encryption
   private $_encryptionPassword='';    // Password for the encryption / decryption
   
   private $_saveHTML=TRUE;            // When displaying a .md file, generate and store its .html rendering
   private $_htmlTemplate='';          // Template to use for the exportation
      
   /**
    * Class constructor : initialize a few private variables
    * 
    * @param string $folder    Root folder of the website (f.i. "C:\Christophe\Documents\").
    * @return boolean
    */
   function __construct(string $folder) {
      
      // Get the root folder and be sure the folder ends with a slash
      $this->_rootFolder=rtrim($folder,DS).DS;
      
      // Initialize with default values
      $this->_settingsDocsFolder=''; //DOC_FOLDER.DS;
      $this->_settingsLanguage='en';
      $this->_htmlTemplate=HTML_TEMPLATE;
      $this->_saveHTML=OUTPUT_HTML;
      $this->_settingsImgMaxWidth=IMG_MAX_WIDTH;
      
      // No password defined by default
      $this->_settingsPassword='';
      
      // Read the user's settings
      self::ReadSettings();

      return true;

   } // function __construct()
   
   /**
    * Read the user's settings i.e. the file "settings.json"
    * Initialize class properties 
    */
   private function ReadSettings() : bool {
     
      // Process the settings.json file
      if (aeSecureFiles::fileExists($fname=$this->_rootFolder.'settings.json')) {
   
         $this->_json=json_decode(file_get_contents($fname),true);

         if(isset($this->_json['editor'])) {
            $this->_settingsEditor=rtrim($this->_json['editor']);
            if(!file_exists($this->_settingsEditor)) $this->_settingsEditor=EDITOR;
         } else {
            $this->_settingsEditor=EDITOR;
         }
         
         // Retrieve the subfolder if any
         if(isset($this->_json['folder']))   $this->_settingsDocsFolder=rtrim($this->_json['folder'],DS).DS;
         if(isset($this->_json['language'])) $this->_settingsLanguage=$this->_json['language'];
         
         // Retrieve the password if mentionned
         if(isset($this->_json['password'])) $this->_settingsPassword=$this->_json['password'];
         
          // Get export settings
         if(isset($this->_json['page'])) {
            $tmp=$this->_json['page'];
            // Spaces should be replaced by a "+" sign
            if(isset($tmp['google_font'])) $this->_settingsFontName=str_replace(' ','+',$tmp['google_font']);
            if(isset($tmp['img_maxwidth'])) $this->_settingsImgMaxWidth=str_replace(' ','+',$tmp['img_maxwidth']);
            
         }
        
         // Get export settings
         if(isset($this->_json['encryption'])) {
            $tmp=$this->_json['encryption'];
            if(isset($tmp['password'])) $this->_encryptionPassword=$tmp['password'];
            if(isset($tmp['method']))   $this->_encryptionMethod=$tmp['method'];
         }
         
         // Get export settings
         if(isset($this->_json['export'])) {
            $tmp=$this->_json['export'];
            if(isset($tmp['save_html'])) $this->_saveHTML=$tmp['save_html'];
            if(isset($tmp['html']))      $this->_htmlTemplate=$tmp['html'];
         }
         
      }  
      
      return TRUE;
      
   } // function ReadSettings()
   
   public function getSetting(string $name, $defaultValue) {
      switch ($name) {
         case 'ImgMaxWidth' :
            return $this->_settingsImgMaxWidth;
            break;
         default:
           return null;        
      }
   } // function getSetting()
   
   /**
    * Get the list of .md files.  This list will be used in the "table of contents"
    * 
    * @return string
    */   
   public function ListFiles() : string {
	   	  
      $arrFiles=array_unique(aeSecureFiles::rglob('*.md',$this->_rootFolder.$this->_settingsDocsFolder));

      // Be carefull, folders / filenames perhaps contains accentuated characters
      $arrFiles=array_map('utf8_encode', $arrFiles);
      
      // Sort, case insensitve
      natcasesort($arrFiles);   

      $sReturn = '<div class="rootfolder">'.$this->_rootFolder.$this->_settingsDocsFolder.'</div>'.
         '<table id="tblFiles" class="table tablesorter table-hover table-bordered">'.
        /* '<thead>'.
            '<tr>'.
               '<td data-placeholder="Filter on a folder" class="filter-select filter-exact ext">Folder</td>'.
               '<td data-placeholder="Search for a filename..."  data-filter="false">Filename</td>'.
            '</tr>'.
         '</thead>'.*/
         '<tbody>';

      foreach ($arrFiles as $file) {
         
         // Don't mention the full path, should be relative for security reason
         $file=str_replace($this->_rootFolder.$this->_settingsDocsFolder,'',$file);
         
         $folder=(trim(dirname($file))=='.')?'(root)':dirname($file);
         
         $sReturn.='<tr><td data-folder="'.$folder.'">'.$folder.'</td><td data-file="'.$file.'">'.str_replace('.md','',basename($file)).'</td></tr>';
         
      }
      
      $sReturn.='</tbody></table><div class="countfiles">#'.count($arrFiles).' files processed</div>';

      return $sReturn;
	  
   } // function ListFiles()	  
  
   /**
    * Return the HTML rendering of a .md file
    * 
    * @param type $filename   Relative filename of the .md file to open and display
    * @return string          HTML rendering 
    */ 
   public function ShowFile(string $filename) : string {
	    
      $fullname=utf8_decode($this->_rootFolder.$this->_settingsDocsFolder.$filename);
      
      $markdown=file_get_contents($fullname);
      
      $icons='';

      // Check if there are <encrypt> tags.  If yes, check the status (encrypted or not) and retrieve its content
      $matches = array();    
      // ([\\S\\n\\r\\s]*?)  : match any characters, included new lines
      preg_match_all('/<encrypt[[:blank:]]*([^>]*)>([\\S\\n\\r\\s]*?)<\/encrypt>/', $markdown, $matches);
      
      // If matches is greater than zero, there is at least one <encrypt> tag found in the file content
      if (count($matches[1])>0) {
         
         $icon_stars='<i class="icon_encrypted fa fa-lock onlyscreen" aria-hidden="true" '.
            'data-encrypt="true" title="'.str_replace('"','\"',$this->getText('is_encrypted','IS_ENCRYPTED')).'"></i>';
         
         // Initialize the encryption class
         $aesEncrypt=new aeSecureEncrypt($this->_encryptionPassword, $this->_encryptionMethod);
         
         $j=count($matches[0]);
               
         $i=0;
         
         $rewriteFile=FALSE;
     
         // Loop and process every <encrypt> tags
         // For instance : <encrypt data-encrypt="true">ENCRYPTED TEXT</encrypt>

         for($i;$i<$j;$i++) {
                        
            // Retrieve the attributes (f.i. data-encrypt="true")            
            $attributes=$matches[1][$i];
            
            $isEncrypted=FALSE;
            
            $tmp=array();
            preg_match('#data-encrypt="(.*)"#', $attributes, $tmp);
            
            if(count($tmp)>0) {
               // Only when data-encrypt="true" is found, consider the content has an encrypted one.
               $isEncrypted=(strcasecmp(rtrim($tmp[1]),'true')===0?TRUE:FALSE);
            }

            // Retrieve the text (encrypted if data-encrypt was found and set on "true"; uncrypted otherwise)
            $words=$matches[2][$i];

            // If we need to crypt we a new password, 
            // NULL = try to use the current password, defined in the settings.json file
            //$decrypt=$aesEncrypt->sslDecrypt($words,NULL);
            //if (!ctype_print($decrypt)) {  // ctype_print will return FALSE when the string still contains binary info => decrypt has failed
            //   $words=$aesEncrypt->sslDecrypt($words,'');
            //   $isEncrypted=FALSE;
            //}
            
            if (!$isEncrypted) {
               
               // At least one <encrypt> tag found without attribute data-encrypt="true" => the content should be encrypted and
               // the file should be override with encrypted data
               
               $encrypted=$aesEncrypt->sslEncrypt($words,NULL);              

               $markdown=str_replace($matches[0][$i], utf8_encode('<encrypt data-encrypt="true">'.$encrypted.'</encrypt>'), $markdown);
               
               $rewriteFile=TRUE;

            } // if (!$isEncrypted)
            
         } // for($i;$i<$j;$i++)         

         if ($rewriteFile===TRUE) {
            
            // The file has been changed => there was information with an <encrypt> tag but not yet encrypted.
            // Now, $markdown contains only encrypted <encrypt> tag (i.e. with data-encrypt="true" attribute)

            rename($fullname, $fullname.'.old');
            
            try {
               
               if ($handle = fopen($fullname,'w')) {
                  fwrite($handle, $markdown);
                  fclose($handle);		
               }               
               
               if (filesize($fullname)>0) unlink($fullname.'.old');
               
            } catch (Exception $ex) {               
            }

         } // if ($rewriteFile===TRUE)

         // --------------------------------------------------------------------------------------------
         // 
         // Add a three-stars icon (only for the display) to inform the user about the encrypted feature
 
         $matches = array();         
         // ([\\S\\n\\r\\s]*?)  : match any characters, included new lines
         preg_match_all('/<encrypt[[:blank:]]*[^>]*>([\\S\\n\\r\\s]*?)<\/encrypt>/', $markdown, $matches);

         // If matches is greater than zero, there is at least one <encrypt> tag found in the file content
         if (count($matches[1])>0) {
            
            $j=count($matches[0]);

            $i=0;
         
            for($i;$i<$j;$i++) {

               // Great, the info is already encrypted
               
               //$icons='<i id="icon_lock" class="fa fa-lock" aria-hidden="true"></i>';
               
               $decrypt=$aesEncrypt->sslDecrypt($matches[1][$i],NULL);

               $markdown=str_replace($matches[1][$i], $icon_stars.$decrypt.$icon_stars, $markdown);
               
            }

         }
         
         // Release 
         
         unset($aesEncrypt);
         
      } // if (count($matches[1])>0)
      
      // -----------------------------------
      // Add additionnal icons at the left

      $fnameHTML=aeSecureFiles::replace_extension($fullname,'html');
      
      // Generate the URL (full) to the html file, f.i. http://localhost/docs/folder/file.html
      $tmp = str_replace('\\','/',rtrim(aeSecureFct::getCurrentURL(FALSE,TRUE),'/').str_replace(str_replace('/',DS,dirname($_SERVER['SCRIPT_FILENAME'])),'',$fnameHTML));
   
      // Open new window icon
      if(OUTPUT_HTML===TRUE) $icons.='<i data-file="'.utf8_encode(str_replace(DS,'/',$tmp)).'" id="icon_window" class="fa fa-external-link" aria-hidden="true" title="'.$this->getText('open_html','OPEN_HTML').'"></i>';
      
      // Edit icon : only if an editor has been defined
      if ($this->_settingsEditor!=='') {
         $icons.='<i id="icon_edit" class="fa fa-pencil-square-o" aria-hidden="true" title="'.$this->getText('edit_file','EDIT_FILE').'" data-file="'.utf8_encode($filename).'"></i>';
      }

      require_once("libs/Parsedown.php");
      $Parsedown=new Parsedown();      
      $html=$Parsedown->text($markdown);
      
      // Check if the .html version of the markdown file already exists; if not, create it 
      if ($this->_saveHTML===TRUE) {

         if (is_writable(dirname($fullname).DS)) {

            // If the file already exists check his version (md5) against the new content : replace the file if not the latest version
            if(file_exists($fnameHTML)) {
               $md5=md5_file($fnameHTML);
               if ($md5!==md5($html)) @unlink($fnameHTML);
            }

            if (!file_exists($fnameHTML)) {
               
               $tmp=$html;
               
               // Don't save unencrypted informations
               $matches = array();
         
               // ([\\S\\n\\r\\s]*?)  : match any characters, included new lines
               preg_match_all('/<encrypt[[:blank:]]*[^>]*>([\\S\\n\\r\\s]*?)<\/encrypt>/', $tmp, $matches);
               //preg_match_all('/<encrypt[[:blank:]]*[^>]*>(.*?)<\/encrypt>/', $tmp, $matches);

               // If matches is greater than zero, there is at least one <encrypt> tag found in the file content
               if (count($matches[0])>0) {

                  $j=count($matches[0]);

                  $i=0;

                  for($i;$i<$j;$i++) {
                     $tmp=str_replace($matches[0][$i], '<strong class="confidential">'.$this->getText('confidential','CONFIDENTIAL').'</strong>', $tmp);
                  }
               }

               if ($handle = fopen($fnameHTML,'w+')) {

                  // Try to find a heading 1 and if so use that text for the title tag of the generated page
                  $matches=array();
                  try {
                     preg_match_all('/<h1>(.*)<\/h1>/', $tmp, $matches);
                     if(count($matches[1])>0) {
                        $title=((count($matches)>0)?rtrim(@$matches[1][0]):'');  
                     } else {
                        $title='';
                     }
                  } catch(Exception $e){    
                  }

                  
                  // Write the file but first replace variables
                  $tmpl=str_replace('%TITLE%',$title,$this->_htmlTemplate);
                  $tmpl=str_replace('%CONTENT%',$tmp,$tmpl);
                  
                  // Perhaps a Google font should be used.  
                  $sFont=self::GoogleFont();
                  $tmpl=str_replace('%FONT%',$sFont,$tmpl);
                  
                  fwrite($handle,$tmpl);

                  fclose($handle);		

               } // if ($handle = fopen($fname,'w+'))

             } // if (!file_exists($fname))

		 } // if (is_writable(dirname($fname)))

      } // if (OUTPUT_HTML===TRUE)
      
      // Generate the URL (full) to the html file, f.i. http://localhost/docs/folder/file.html
      $fnameHTML = str_replace('\\','/',rtrim(aeSecureFct::getCurrentURL(FALSE,TRUE),'/').str_replace(str_replace('/',DS,dirname($_SERVER['SCRIPT_FILENAME'])),'',$fnameHTML));
      
      $html=str_replace('</h1>', '</h1><div id="icons" class="onlyscreen fa-3x"><i id="icon_printer" class="fa fa-print" aria-hidden="true"></i>'.$icons.'</div>',$html);
      $html=str_replace('src="images/', 'src="'.DOC_FOLDER.'/'.str_replace(DS,'/',dirname($filename)).'/images/',$html);
      $html=str_replace('href="files/', 'href="'.DOC_FOLDER.'/'.str_replace(DS,'/',dirname($filename)).'/files/',$html);
      $html='<div class="onlyscreen filename">'.utf8_encode($fullname).'</div>'.$html.'<hr/>';

      return $html;
	  
   } // function ShowFile()
   
   /**
    * Return the translation of a given text
    * @param string $variable
    */
   public function getText(string $variable, string $default) : string {
      
      if (isset($this->_json['languages'][$this->_settingsLanguage])) {
         $lang=&$this->_json['languages'][$this->_settingsLanguage];
         return isset($lang[$variable]) ? $lang[$variable] : (trim($default)!=='' ? constant($default) : '');
      } else {
         return (trim($default)!=='' ? constant($default) : '');
      }
      
   } // function getText()
   
   /**
    * Search for "$keywords" in the filename or in the file content.  Stop on the first occurence to speed up
    * the process.
    * 
    * Note : if the content contains encrypted data, the data is decrypted so the search can be done on these info too
    * 
    * @param string $keywords
    * @return array
    */
   public function Search(string $keywords) : array {
      
      $arrFiles=array_unique(aeSecureFiles::rglob('*.md',$this->_rootFolder.$this->_settingsDocsFolder));
      
      if (count($arrFiles)==0) return null;

      // Be carefull, folders / filenames perhaps contains accentuated characters
      $arrFiles=array_map('utf8_encode', $arrFiles);
      
      // Sort, case insensitve
      natcasesort($arrFiles);   
      
      $return=array();

      // Initialize the encryption class
      $aesEncrypt=new aeSecureEncrypt($this->_encryptionPassword, $this->_encryptionMethod);

      //$return['keywords']=$keywords;
      foreach ($arrFiles as $file) {
         
         // Don't mention the full path, should be relative for security reason
         $file=str_replace($this->_rootFolder.$this->_settingsDocsFolder,'',$file);
         
         // If the keyword can be found in the document title, yeah, it's the fatest solution,
         // return that filename
         
         if (stripos($file, $keywords)!==FALSE) {
         
            // Found in the filename => stop process of this file
            $return['files'][]=$file;
            
         } else {
            
            // Open the file and check against its content
            
            $fullname=utf8_decode($this->_rootFolder.$this->_settingsDocsFolder.$file);
            $content=file_get_contents($fullname);
            
            if (stripos($content, $keywords)!==FALSE) {
               
               // The searched pattern has been found in the filecontent (unencrypted), great, return the filename
               
               $return['files'][]=$file;
               
            } else {
               
               // Not found in filename and filecontent (unencrypted); check if there are encrypted info

               // Check if the note has encrypted data.  If you, decrypt and search in the decrypted version
               
               $matches = array();         
               // ([\\S\\n\\r\\s]*?)  : match any characters, included new lines
               preg_match_all('/<encrypt[[:blank:]]*([^>]*)>([\\S\\n\\r\\s]*?)<\/encrypt>/', $content, $matches);

               // If matches is greater than zero, there is at least one <encrypt> tag found in the file content
               if (count($matches[1])>0) {
                  
                  $j=count($matches[0]);

                  $i=0;

                  // Loop and process every <encrypt> tags
                  // For instance : <encrypt data-encrypt="true">ENCRYPTED TEXT</encrypt>

                  for($i;$i<$j;$i++) {
                        
                     // Retrieve the attributes (f.i. data-encrypt="true")            
                     $attributes=$matches[1][$i];

                     // Are there data-encrypt=true content ? If yes, unencrypt it
                     $tmp=array();
                     preg_match('#data-encrypt="(.*)"#', $attributes, $tmp);
                     
                     if(count($tmp)>0) {
                        // Only when data-encrypt="true" is found, consider the content has an encrypted one.
                        $isEncrypted=(strcasecmp(rtrim($tmp[1]),'true')===0?TRUE:FALSE);
                        $decrypt=$aesEncrypt->sslDecrypt($matches[2][$i],NULL);
                        $content=str_replace($matches[2][$i], $decrypt, $content);
                     }                  

                     // Now $content is full decrypted, search again
                     if (stripos($content, $keywords)!==FALSE) { 
                        $return['files'][]=$file;
                        break;                        
                     }
                  
                  } // for($i;$i<$j;$i++)
                  
               } // if (count($matches[1])>0) {
                  
            } // if (stripos($content, $keywords)!==FALSE) {
            
         } // if (stripos($file, $keywords)!==FALSE)
            
      } // foreach ($arrFiles as $file)
      
      unset($aesEncrypt);
      
      return $return;
      
   } // function Search()
   
   /**
    * Start the editor program
    * 
    * @param string $filename   Relative filename like "privÃ©\Fisc.md".  Need to be utf8_decoded
    * @return bool
    */
   public function Edit(string $filename) : bool {
      
      if ($filename!="") {
         
         // Get the fullname like "C:\Christophe\notes\docs\privé\Fisc.md"
         $fullname=utf8_decode($this->_rootFolder.$this->_settingsDocsFolder.$filename);

         try {

            // Make the command line : the editor followed by the name of the file that should be edited
            // For instance : C:\Windows\System32\notepad.exe "C:\Christophe\notes\docs\privé\Fisc.md"
            $cmd = $this->_settingsEditor.' "'.$fullname.'"'; // Open the directory and select the file

            //echo __LINE__.'  **'.$cmd.'**<br/>';   
            if (substr(php_uname(), 0, 7) == "Windows") {
               pclose(popen("start /B ".$cmd, "r"));
            } else {
               exec($cmd);
            }

         } catch (Exception $ex) {
            die('Error in line '.__LINE__.' : '.$ex->getMessage());
         }
         
      } // if ($filename!="") 
    
      return true;
      
   } // function Edit()
   
   /**
    * Detect if a Google Font was specified in the json and if so, generate a string to load that font
    * 
    * @return string
    */
   public function GoogleFont() : string {
      
      $result='';
      
      if ($this->_settingsFontName!=='') {
         
         $result='<link href="https://fonts.googleapis.com/css?family='.$this->_settingsFontName.'" rel="stylesheet">';

         $i=0;
         $result.='<style>';
         $sFontName=str_replace('+',' ',$this->_settingsFontName);
         for($i=1;$i<7;$i++) $result.='page h'.$i.'{font-family:"'.$sFontName.'";}';
         $result.='</style>';
         
      } // if ($this->_settingsFontName!=='')
     
      return $result;
      
   } // function GoogleFont()
   
} // class aeSecureMarkdown

   if (DEBUG===TRUE) {
      ini_set("display_errors", "1");
      ini_set("display_startup_errors", "1");
      ini_set("html_errors", "1");
      ini_set("docref_root", "http://www.php.net/");
      ini_set("error_prepend_string", "<div style='color:black;font-family:verdana;border:1px solid red; padding:5px;'>");
      ini_set("error_append_string", "</div>");
      error_reporting(E_ALL);
   } else {	   
      ini_set('error_reporting', E_ALL & ~ E_NOTICE);	  
   }
   
   $task=aeSecureFct::getParam('task','string','',false);

   // Create an instance of the class and initialize the rootFolder variable (type string)
   $aeSMarkDown = new aeSecureMarkdown((string) str_replace('/',DS,dirname($_SERVER['SCRIPT_FILENAME'])).DS);
   
   switch ($task) {
      
      case 'display':
         
         header('Content-Type: text/html; charset=utf-8'); 
         $fname=json_decode(urldecode(base64_decode(aeSecureFct::getParam('param','string','',false))));
         $result=$aeSMarkDown->ShowFile($fname);
         echo $result;
         unset($aeSMarkDown);
         die();
         
      case 'edit' : 
         
         $fname=json_decode(urldecode(base64_decode(aeSecureFct::getParam('param','string','',false))));
         $aeSMarkDown->Edit($fname);
         unset($aeSMarkDown);
          
         die();
         
      case 'listFiles':
         
         // Retrieve the list of .md files.   
         // Default task
         
         echo $aeSMarkDown->ListFiles();
         unset($aeSMarkDown);
         die();
		 
      case 'search': 
         
         header('Content-Type: application/json'); 
         $json=$aeSMarkDown->Search(aeSecureFct::getParam('param','string','',false, SEARCH_MAX_WIDTH));
         echo json_encode($json, JSON_PRETTY_PRINT); 
         unset($aeSMarkDown);
         die();
         
   } // switch ($task)
   
?>

<!DOCTYPE html>
<html lang="en">

   <head>
      <meta charset="utf-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="robots" content="noindex, nofollow" />
      <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8;" /> 
      <meta http-equiv="cache-control" content="max-age=0" />
      <meta http-equiv="cache-control" content="no-cache" />
      <meta http-equiv="expires" content="0" />
      <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
      <meta http-equiv="pragma" content="no-cache" />
      <title><?php echo APP_NAME;?></title>
      <link href="data:image/x-icon;base64,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" rel="shortcut icon" type="image/vnd.microsoft.icon"/>  

      <?php 
      
         // Check if a Google font was specified and if so, load it
         echo $aeSMarkDown->GoogleFont();
      
         // Load Bootstrap
         echo aeSecureFct::addStylesheet('libs/bootstrap.min.css','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
         echo aeSecureFct::addStylesheet('libs/font-awesome/css/font-awesome.min.css','https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
         
         echo aeSecureFct::addStylesheet('libs/print-preview/print-preview.css');
         
      ?>

      <style>

         img {max-width: <?php echo $aeSMarkDown->getSetting('ImgMaxWidth','800');?>px;}
         
         @media screen {
            
            /* By selecting a file from the filelist, highlight its name */
            #tblFiles > tbody > tr:nth-child(odd) .selected{background-color:#90b6e2;color:white;}
            #tblFiles  > tbody > tr:nth-child(even) .selected{background-color:#90b6e2;color:white;}

            /* Style for the "Open in a new window" hyperlink */
            .open_newwindow{text-decoration:underline;}

            /* Style for the "Edit file" hyperlink */
            .edit_file{text-decoration:underline;color:#337ab7;cursor:pointer;}

            /* Style for the formatting of the name of the file, displayed in the content, first line */
            .filename{font-style:italic;font-weight:bold;color:#dfdfe0;top:15px;position:inherit;} 

            /* Default page background color */
            body{background:#F7F2E9;}

            /* The root folder name */
            .rootfolder{display:none;}

            /* The search area.  The width will be initialized by javascript */
            #edtSearch{position:fixed;left:5px;top:5px;z-index: 1;}

            /* Formating of the array with the list of files */
            /* The search area.  The width will be initialized by javascript */
            #tblFiles{font-size:0.8em;color:#445c7b;background-color:#f5f5f5;}
            #tblFiles>thead>tr{font-size:1.2em;color:#445c7b;background-color:#c7c0c0;color:white;}
            /*#tblFiles>thead>tr.tablesorter-filter-row{background-color:red;color:white;}*/

            /* The icons area is used f.i. for displaying a lock icon when the note contains encrypted data */
            #icons {display:inline-block;position:absolute;top:5px;right:-1px;margin-right:10px;}
            
            /* Images */
            #icon_edit{margin-left:20px;color:lightgray;}
            #icon_lock{margin-left:2px;color:#abe0ab;}
            #icon_printer{color:lightgray;}
            #icon_window{margin-left:20px;color:lightgray;}
            
            .icon_file{padding-left:5px;}
            .icon_encrypted{padding-left:5px;padding-right:5px;color:#abe0ab;}
            
            /* Content if the full page : contains the list of files and the content of the select note */
            
            #CONTENT{margin-left:10px;top:5px !important;max-height:960px;overflow-y:auto;overflow-x:auto;}

            /* TDM if the left part, i.e. the container of the search area and TOC (the list of files) */
            #TDM{left:5px; top:5px !important;max-height:960px;overflow-y:auto;overflow-x:auto;}
            #TOC{position:inherit;top:30px;}
            
            /* page is used to display the content of the selected note */
            page{background:white;display:none;margin:0 auto;margin-bottom:0.5cm;box-shadow:0 0 0.5cm rgba(0,0,0,0.5);}

            /* Don't display informations that are targeted for printers only */            
            .onlyprint{display:none;}
            
            .countfiles{font-size:xx-small;font-style:italic;}
            /* Use by the jQuery Highlite plugin, highlight searched keywords */
            .highlight{background-color:yellow;border-radius:.125em;}  
            
            .download-link{background-color:rgba(255, 235, 59, 0.21);text-decoration:underline;}
            
         } /* @media screen */

         @media print {
            
            #CONTENT:before {
               content:"<?php echo APP_NAME;?>";
               display:block;
               text-align:center;
               border:1px solid #ccc;
               font-style:italic;
               margin:0 0 1em;
               padding:8px 10px;
            }
            /*page[size="A4"][layout="portrait"] {width:29.7cm;height:21cm;}*/

            body {
               background:#FFF;
               color: #000;
               font-family: Georgia, serif;
               line-height: 1.2;
            }

            #CONTENT p, table, ul, ol, dl, blockquote, pre, code, form {
               margin: 0 0 1em;
            }

            #CONTENT h1,h2,h3,h4,h5 {
               font-weight: normal;
               margin: 2em 0 0.5em;
               text-shadow: rgba(0, 0, 0, 0.44) 1px 1px 2px;
            }
            
            /* Put the href of the link in the content, juste after the link */
            /* So <a href="www.google.be">Google</a> will be printed like */
            /* Google (www.google.be) */
            a:link:after {
               content: " (" attr(href) ") ";
               font-size: 80%;
               text-decoration: none;
            }
            
            #CONTENT h1 {font-size:2em; margin: 2em 0 0.25em;}
            #CONTENT h2 {font-size:1.7em;}
            #CONTENT h3 {font-size:1.5em;}
            #CONTENT h4 {font-size:1.2em;}
            #CONTENT h5 {font-size:1em;}

            #CONTENT ul, li {
               display:block;
               page-break-inside:avoid;
            }

            /* Don't print objects that only should be displayed on screen */
            .onlyscreen{display:none;}
            
            /* Don't print the left part */
            #TDM{display:none;}

            /*body, page{box-shadow:0;}*/
            
            /* Make text a little larger on print */
            /*page{font-size:larger;}*/

            footer {
               position:fixed;
               display:block;
               bottom:0px;
               border-top: 1px solid #cecece;
               font-size: 0.83em;
               margin: 2em 0 0;
               padding: 1em 0 0;
            }
         }

       </style>
       
      <?php 
         // Add your custom stylesheet if the custom.css file is present
         echo aeSecureFct::addStylesheet('custom.css');
      ?>
   </head>
   
   <body style="overflow:hidden;">
   
      <div class="row">
         <div class="container col-md-4 onlyscreen" id="TDM">
            <div class="container col-md-12"><input id="edtSearch" type="text" size="60" maxlength="<?php echo SEARCH_MAX_WIDTH; ?>" placeholder="<?php echo $aeSMarkDown->getText('search_placeholder','SEARCH_PLACEHOLDER');?>"/></div>
            <div id="TOC" class="onlyscreen">&nbsp;</div>	  
         </div>
         <page size="A4" layout="portrait" class="container col-md-8" id="CONTENT">&nbsp;</page>

         <div id="aside">&nbsp;</div>   
         
      </div>
      
      <?php 
         echo aeSecureFct::addJavascript('libs/jquery.min.js','//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
         echo aeSecureFct::addJavascript('libs/bootstrap.min.js','//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js');
         echo aeSecureFct::addJavascript('libs/jquery.tablesorter.combined.min.js','https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.25.3/js/jquery.tablesorter.combined.min.js');
         echo aeSecureFct::addJavascript('libs/jquery.noty.packaged.min.js','https://cdnjs.cloudflare.com/ajax/libs/jquery-noty/2.3.8/packaged/jquery.noty.packaged.min.js');
         echo aeSecureFct::addJavascript('libs/highlite/js/jquery.highlite.js');
         echo aeSecureFct::addJavascript('libs/print-preview/jquery.print-preview.js');
      ?>

      <footer class="onlyprint">&nbsp;</footer>	        

      <?php 
         // Add your custom javascript if the custom.js file is present
         echo aeSecureFct::addJavascript('custom.js');
      ?>
      <script type="text/javascript">

         $(document).ready(function() {

            // Add keybinding (not recommended for production use)
            /*$(document).bind('keydown', function(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (code == 80 && !$('#print-modal').length) {
                    $.printPreview.loadPrintPreview();
                    return false;
                }            
            });*/

            // On page entry, get the list of .md files on the server
            ajaxify({task:'listFiles',callback:'initFiles(data)',target:'TOC'});
            
            // Size correctly depending on screen resolution
            $('#TDM').css('max-height', $(window).height()-10);
            $('#TDM').css('min-height', $(window).height()-10);
            
            // Maximise the width of the table of contents i.e. the array with the list of files
            $('#TOC').css('width', $('#TDM').width()-5);
            $('#edtSearch').css('width', $('#TDM').width()-5);
            
            $('#CONTENT').css('max-height', $(window).height()-10);
            $('#CONTENT').css('min-height', $(window).height()-10);
            $('#CONTENT').css('width', $('#CONTENT').width()-5);
            
            // When the user will exit the field, call the onChangeSearch function to fire the search
            $('#edtSearch').change(function(e) { onChangeSearch(); } );
            
         }); // $( document ).ready()
		 
         /**
          * Run an ajax query
          * 
          * @param {type} params
          *      task = which task should be fired
          *      param = (optional) parameter to provide for the calling task
          *      callback = (optional) Function to call once the ajax call is successfully done
          * 
          * @returns {undefined}
          */

         function ajaxify($params) { 

            var $data = new Object;
            $data.task  = (($params.task=='undefined')?'':$params.task);
            $data.param = (($params.param=='undefined')?'':$params.param);
            
            var $target='#'+(($params.target=='undefined')?'TDM':$params.target);

            $.ajax({
               beforeSend: function() {
                  $($target).html('<div><span class="ajax_loading">&nbsp;</span><span style="font-style:italic;font-size:1.5em;">Un peu de patience svp...</span></div>');
               },// beforeSend()
               async:true,
               type:'<?php echo (DEBUG===true?'GET':'POST'); ?>',
               url: '<?php echo basename(__FILE__); ?>',
               data:$data,
               datatype:'html',
               success: function (data) {     
                  
                  $($target).html(data); 
				  
                  /* jshint ignore:start */
                  var $callback=($params.callback==undefined)?'':$params.callback;
                  if($callback!=='') eval($callback);				  
                  /* jshint ignore:end */
               }
            }); // $.ajax() 
		 
         } // function ajaxify()
         
         /**
          * Called once 
          */
         function initFiles() {
            
            $("#tblFiles").tablesorter({
               widthFixed: false,
               sortMultiSortKey: "shiftKey",
               sortResetKey: "ctrlKey",
               headers: {
                  0: {sorter: "text"},  // Foldername
                  1: {sorter: "text"}   // Filename
               },
               ignoreCase: true,
               headerTemplate: "{content} {icon}",
               widgets: [ ],
               initWidgets: true,
               sortList: [[0,0],[1,0]]
            }); // $("#tblFiles")
            
            $('#tblFiles > tbody  > tr > td').click(function(e) {
               
               // By clicking on the second column, with the data-file attribute, display the file content
               if ($(this).attr('data-file')) {
                  var $fname=window.btoa(encodeURIComponent(JSON.stringify($(this).data('file'))));              
                  ajaxify({task:'display',param:$fname,callback:'afterDisplay()',target:'CONTENT'});
                  $(this).addClass("selected");                  
               }
               
               // By clicking on the first column (with foldername), get the folder name and apply a filter to only display files in that folder
               if ($(this).attr('data-folder')) {
                  $('#edtSearch').val($(this).data('folder'));   
                  onChangeSearch();
               }
               
            }); // $('#tblFiles > tbody  > tr > td').click()
            
            // Interface : put the cursor immediatly in the edit box
            try {               
               $('#edtSearch').focus();
            } catch(err) {         
            }
			 
            // See if the custominiFiles() function has been defined and if so, call it
            if (typeof custominiFiles !== 'undefined' && $.isFunction(custominiFiles)) {
               custominiFiles();
            }
            
            return true;
			 
         } // iniFiles()

         /** 
          *  Force links that points on the same server (localhost) to be opened in a new window
          * @returns {Boolean}     
          */
         function forceNewWindow() {

            $('a').each(function() {					
               //var a = new RegExp('/' + window.location.host + '/');
               //if(a.test(this.href)) {
                  $(this).click(function(e) {
                     e.preventDefault(); 
                     e.stopImmediatePropagation();
                     window.open(this.href, '_blank');
                  });
               //}
            }); // $('a').each()
            
            return true;      
            
         } // function forceNewWindow()
         
         /**
          * Add icons to .pdf, .xls, .doc, ... hyperlinks
          */
         function addIcons() {
            
            $("a").each(function() {
               
               $href=$(this).attr("href");   
               $sAnchor=$(this).text();
               
               if (/\.doc[x]?$/i.test($href)) { 
                  // Word document
                  $sAnchor+='<i class="icon_file fa fa-file-word-o" aria-hidden="true"></i>';            
                  $(this).html($sAnchor).addClass('download-link');       
               } else if (/\.pdf$/i.test($href)) { 
                  // PDF
                  $sAnchor+='<i class="icon_file fa fa-file-pdf-o" aria-hidden="true"></i>';            
                  $(this).html($sAnchor).addClass('download-link');       
               } else if (/\.ppt[x]?$/i.test($href)) { 
                  // Powerpoint
                  $sAnchor+='<i class="icon_file fa fa-file-powerpoint-o" aria-hidden="true"></i>';            
                  $(this).html($sAnchor).addClass('download-link');       
               } else if (/\.xls[m|x]?$/i.test($href)) { 
                  // Excel
                  $sAnchor+='<i class="icon_file fa fa-file-excel-o" aria-hidden="true"></i>';            
                  $(this).html($sAnchor).addClass('download-link');       
               } else if (/\.(7z|gzip|tar|zip)$/i.test($href)) { 
                  // Archive
                  $sAnchor+='<i class="icon_file fa fa-file-archive-o" aria-hidden="true"></i>';            
                  $(this).html($sAnchor).addClass('download-link');    
               }
               
            });

            return true;  
            
         } // function addIcons()
         
         /**
          * Called when a file is displayed
          */
         function afterDisplay() {
                     
            /*
             * Initialise print preview plugin
             */
            // Add link for print preview and intialise
            $('#icon_printer').printPreview();
            
            $('#CONTENT').show();
            
            $('html, body').animate({
               'scrollTop' : $("#CONTENT").position().top -25
            });
                        
            // Retrieve the heading 1 from the loaded file 
            var $title=$('#CONTENT h1').text();				  
            if ($title!=='') $('title').text($title);
            
            var $fname=$('div.filename').text();				  
            if ($fname!=='') $('#footer').html('<strong style="text-transform:uppercase;">'+$fname+'</strong>');
            
            // Force links that points on the same server (localhost) to be opened in a new window
            forceNewWindow();
            
            // Add icons to .pdf, .xls, .doc, ... hyperlinks
            addIcons();
            
            // Interface : put the cursor immediatly in the edit box
            try {               
               $('#edtSearch').focus();
            } catch(err) {         
            }
			 
            // Get the searched keywords.  Apply the restriction on the size.
            var $searchKeywords = $('#edtSearch').val().substr(0, <?php echo SEARCH_MAX_WIDTH; ?>).trim();
          
            if ($searchKeywords!='') {
               $("#CONTENT").highlite({
                  text: $searchKeywords
               });
            }
            
            // By clicking on the "Edit" link, start the associated edit program (like Notepad f.i.)
            $('#icon_edit').click(function(e) {
               var $fname=window.btoa(encodeURIComponent(JSON.stringify($(this).data('file'))));      
               ajaxify({task:'edit',param:$fname});
            })
   
            $('#icon_window').click(function(e) {
               var $fname=$(this).data('file');  
               window.open($fname);
            })
            
            // See if the customafterDisplay() function has been defined and if so, call it
            if (typeof customafterDisplay !== 'undefined' && $.isFunction(customafterDisplay)) {
               customafterDisplay($fname);
            }
            
            return true;
            
         } // function afterDisplay()
         
         /**
          * 
          * @returns {undefined}
          */
         function onChangeSearch() {
            
            // Get the searched keywords.  Apply the restriction on the size.
            var $searchKeywords = $('#edtSearch').val().substr(0, <?php echo SEARCH_MAX_WIDTH; ?>).trim();
            
            var $bContinue=true;
            // See if the customonChangeSearch() function has been defined and if so, call it
            if (typeof customonChangeSearch !== 'undefined' && $.isFunction(customonChangeSearch)) {
               $bContinue=customonChangeSearch($searchKeywords);
            }
            
            if ($bContinue==true) {
               // On page entry, get the list of .md files on the server
               ajaxify({task:'search',param:$searchKeywords, callback:'afterSearch("'+$searchKeywords+'",data)'});
            } else {
               console.log('cancel the search');
            }
            
            return true;
            
         } // Search()
         
         /*
          * Called when the ajax request "onChangeSearch" has been successfully fired.
          * Process the result of the search : the returned data is a json string that represent an 
          * array of files that matched the searched pattern.
          */
         function afterSearch($keywords, $data) {
           
            // Check if we've at least one file
            if (Object.keys($data).length>0) {
               
               // Process every rows of the tblFiles array => process every files 
               $('#tblFiles > tbody  > tr > td').each(function() {
                  
                  // Be sure to process only cells with the data-file attribute.
                  // That attribute contains the filename, not encoded
                  if ($(this).attr('data-file')) {
                     
                     // Get the filename (is relative like /myfolder/filename.md)
                     $filename=$(this).data('file');
                     $tr=$(this).parent();
                     
                     // Default : hide the filename
                     $tr.hide();                     
                     
                     // Now, check if the file is mentionned in the result, if yes, show the row back
                     $.each($data, function() {
                        $.each(this, function($key, $value) {                           
                           if ($value==$filename) {
                              $tr.show();
                              return false;  // break
                           }
                        });
                     }); // $.each($data)
                     
                  }
               }); // $('#tblFiles > tbody  > tr > td')
      
            } else {
               
               if ($keywords!=='') {
               
                  // Nothing found
                  var n = noty({
                     text: '<?php echo str_replace("'","\'",$aeSMarkDown->getText('search_no_result','SEARCH_NO_RESULT'));?>',
                     theme: 'relax',
                     timeout: 2400,
                     layout: 'bottomRight',
                     type: 'success'
                  }); // noty() 
                  
               } else { // if ($keywords!=='')
               
                  // show everything back
                  $('#tblFiles > tbody  > tr > td').each(function() {
                     if ($(this).attr('data-file')) {
                        $(this).parent().show();
                     }
                  });
               } // if ($keywords!=='')
               
            } // if (Object.keys($data).length>0)
            
            // See if the customafterSearch() function has been defined and if so, call it
            if (typeof customafterSearch !== 'undefined' && $.isFunction(customafterSearch)) {
               customafterSearch($keywords, $data);
            }
            
            
         } // function afterSearch()
         
      </script>
	  
   </body>
</html>   

<?php

   unset($aeSMarkDown);

?>