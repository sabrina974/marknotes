<?php
/**
* markdown - Script that will transform your notes taken in the Markdown format (.md files) into a rich website
* @version   : 1.0.5
* @author    : christophe@aesecure.com
* @license   : MIT
* @url       : https://github.com/cavo789/markdown
* @package   : 2017-02-16T12:37:19.430Z
*/?>
<?php

namespace AeSecureMDTasks;
        
/**
* Return the HTML rendering of a .md file
*
*/

class Display
{
    public static function Run(array $params)
    {

        // If the filename doesn't mention the file's extension, add it.
        if (substr($params['filename'], -3)!='.md') {
            $params['filename'].='.md';
        }
      
        $aeDebug=\AeSecure\Debug::getInstance();
        $aeSettings=\AeSecure\Settings::getInstance();
        
        $fullname=str_replace('/', DIRECTORY_SEPARATOR, utf8_decode($aeSettings->getFolderDocs(true).
           ltrim($params['filename'], DS)));

        if (!file_exists($fullname)) {
            echo str_replace(
                '%s',
                '<strong>'.$fullname.'</strong>',
                $aeSettings->getText('file_not_found', 'The file [%s] doesn\\&#39;t exists')
            );
            die();
        }
        
        $markdown=file_get_contents($fullname);
      
        $old=$markdown;
      
        // -----------------------------------------------------------------------
        // URL Cleaner : Make a few cleaning like replacing space char in URL or in image source
        // Replace " " by "%20"
      
        if (preg_match_all('/<img *src *= *[\'|"]([^\'|"]*)/', $markdown, $matches)) {
            foreach ($matches[1] as $match) {
                $sMatch=str_replace(' ', '%20', $match);
                $markdown=str_replace($match, $sMatch, $markdown);
            }
        }
      
        // And do the same for links
        if (preg_match_all('/<a *href *= *[\'|"]([^\'|"]*)/', $markdown, $matches)) {
            foreach ($matches[1] as $match) {
                $sMatch=str_replace(' ', '%20', $match);
                $markdown=str_replace($match, $sMatch, $markdown);
            }
        }
      
        $icons='';

        
        // Initialize the encryption class
        $aesEncrypt=new \AeSecure\Encrypt($aeSettings->getEncryptionPassword(), $aeSettings->getEncryptionMethod());

        // bReturn will be set on TRUE when the file has been rewritten (when <encrypt> content has been found)
        // $markdown will contains the new content (once encryption has been done)
        list($bReturn, $markdown)=$aesEncrypt->HandleEncryption($fullname, $markdown);
      
        // -----------------------------------
        // Add additionnal icons at the left

        $fnameHTML=\AeSecure\Files::replaceExtension($fullname, 'html');

        $fnameHTMLrel=str_replace(str_replace('/', DS, $aeSettings->getFolderWebRoot()), '', $fnameHTML);

        // Generate the URL (full) to the html file, f.i. http://localhost/docs/folder/file.html
        $tmp = rtrim(\AeSecure\Functions::getCurrentURL(false, true), '/').'/'.str_replace(DS, '/', $fnameHTMLrel);

        // Open new window icon
        if ($aeSettings->getSaveHTML()) {
            $icons.='<i id="icon_window" data-task="window" data-file="'.utf8_encode($tmp).
               '" class="fa fa-external-link" aria-hidden="true" title="'.$aeSettings->getText('open_html', 'Open in a new window').'"></i>';
        }
     
        // Edit icon : only if an editor has been defined
        if ($aeSettings->getEditAllowed()) {
            $icons.='<i id="icon_edit" data-task="edit" class="fa fa-pencil-square-o" aria-hidden="true" '.
               'title="'.$aeSettings->getText('edit_file', 'Edit').'" data-file="'.$params['filename'].'"></i>';
        }

        // Call the Markdown parser (https://github.com/erusev/parsedown)
        $lib=$aeSettings->getFolderLibs()."parsedown/Parsedown.php";
        if (!file_exists($lib)) {
            self::ShowError(str_replace(
                '%s',
                '<strong>'.$lib.'</strong>',
                $aeSettings->getText('file_not_found', 'The file [%s] doesn\\&#39;t exists')
            ), true);
        }
        require_once($lib);
        $Parsedown=new \Parsedown();
        $html=$Parsedown->text($markdown);

        // -------------------------------------------------------------------------------
        //
        // Check the presence of tags i.e. things like §tag, §frama, §webdev, ...
        // The § sign followed by a word
      
        /*$matches = array();

       preg_match_all('/'.PREFIX_TAG.'([a-zA-Z0-9]+)/', $html, $matches);
       // If matches is greater than zero, there is at least one <encrypt> tag found in the file content

        if (count($matches[1])>0) self::StoreTags($matches[1]);*/
  
        //
        // -------------------------------------------------------------------------------
      
        // Check if the .html version of the markdown file already exists; if not, create it
        if ($aeSettings->getSaveHTML()) {
            if (is_writable(dirname($fullname).DS)) {
                // If the file already exists check his version (md5) against the new content :
                // replace the file if not the latest version
                if (file_exists($fnameHTML)) {
                    $md5=md5_file($fnameHTML);
                    if ($md5!==md5($html)) {
                        @unlink($fnameHTML);
                    }
                }

                if (!\AeSecure\Files::fileExists($fnameHTML)) {
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

                        for ($i; $i<$j; $i++) {
                            $tmp=str_replace($matches[0][$i], '<strong class="confidential">'.
                               $aeSettings->getText('confidential', 'confidential').'</strong>', $tmp);
                        }
                    }

                    if ($handle = fopen($fnameHTML, 'w+')) {
                        // Try to find a heading 1 and if so use that text for the title tag of the generated page
                        $matches=array();
                        try {
                            preg_match_all('/<h1>(.*)<\/h1>/', $tmp, $matches);
                            if (count($matches[1])>0) {
                                $title=((count($matches)>0)?rtrim(@$matches[1][0]):'');
                            } else {
                                $title='';
                            }
                        } catch (Exception $e) {
                        }
                        
                        if (\AeSecure\Files::fileExists($template = $aeSettings->getTemplateFile('html'))) {
                            $content=file_get_contents($template);
         
                            // Write the file but first replace variables
                            $content=str_replace('%TITLE%', $title, $content);
                            $content=str_replace('%CONTENT%', $tmp, $content);
                  
                            // Perhaps a Google font should be used.
                            $sFont=$aeSettings->getPageGoogleFont(false);
                            $content=str_replace('%FONT%', $sFont, $content);
                  
                            fwrite($handle, $content);

                            fclose($handle);
                        }
                    } // if ($handle = fopen($fname,'w+'))
                } // if (!file_exists($fname))
            } // if (is_writable(dirname($fname)))
        } // if (OUTPUT_HTML===TRUE)
      
        // -----------------------------------------------------------------------
        // Once the .html file has been written on disk, not before !
        //
        // Check if the file contains words present in the tags.json file : if the file being displayed
        // contains a word (f.i. "javascript") that is in the tags.json (so it's a known tag) and that
        // word is not prefixed by the "§" sign add it : transform the "plain text" word and add the "tag" prefix

        if (\AeSecure\Files::fileExists($fname = $aeSettings->getFolderWebRoot().'tags.json')) {
            if (filesize($fname)>0) {
                $aeJSON=\AeSecure\JSON::getInstance();

                $arrTags=$aeJSON->json_decode($fname);
                               
                foreach ($arrTags as $tag) {
                    // For each tag, try to find the word in the markdown file
               
                    // /( |\\n|\\r|\\t)+               Before the tag, allowed : space, carriage return, linefeed or tab
                    // [^`\/\\#_\-§]?                  Before the tag, not allowed : `, /, \, #, -, _ and § (the PREFIX_TAG)
                    // ('.preg_quote($tag).')          The tag term (f.i. "javascript"
                    // (\\n|,|;|\\.|\\)|[[:blank:]]|$) After the tag, allowed : carriage return, comma, dot comma, dot, ending ), tag or space or end of line
               
                    // Capture the full line (.* ---Full Regex--- .*)
                    preg_match_all('/(.*( |\\n|\\r|\\t|\\*|\\#)+('.preg_quote($tag).')(\\n|,|;|\\.|\\)|\\t|\\*|\\#| |$)*)/i', $markdown, $matches);

                    foreach ($matches[0] as $match) {
                        if (count($match)>0) {
                            preg_match('/(.*( |\\n|\\r|\\t|\\*|\\#)+('.preg_quote($tag).')(\\n|,|;|\\.|\\)|\\t|\\*|\\#| |$).*)/i', $match, $matches);

                            // Replace, in the line, the word f.i.    (don't use a preg_replace because preg_replace will replace all occurences of the word)

                            //   Line  : Start a SSH connexion     (original)
                            //   By    : Start a §SSH connexion    (new line)

                            // $matches[2] : what was just before the tag      f.i.   " Start a SSH, then ..."  => the space before SSH
                            // $matches[3] : the tag                                  " Start a SSH, then ..."  => SSH
                            // $matches[4] : what was just after the tag              " Start a SSH, then ..."  => the comma after SSH

                            $sLine=str_ireplace($matches[2].$matches[3].$matches[4], $matches[2].$aeSettings->getPrefixTag().$matches[3].$matches[4], $matches[0]);

                            // And now, replace the original line ($matches[0]) by the new one in the document.

                            $markdown=str_replace($matches[0], $sLine, $markdown);
                        } // if (count($match)>0)
                    } // foreach ($matches[0] as $match)
                } // foreach
            } // if(filesize($fname)>0)
        } // if (\AeSecure\Files::fileExists($fname=$this->_rootFolder.'tags.json'))
      
        //
        // -----------------------------------------------------------------------

        // Generate the URL (full) to the html file, f.i. http://localhost/docs/folder/file.html
        $fnameHTML = str_replace('\\', '/', rtrim(\AeSecure\Functions::getCurrentURL(false, true), '/').str_replace(str_replace('/', DS, dirname($_SERVER['SCRIPT_FILENAME'])), '', $fnameHTML));

        // Retrieve the URL to this note
        $thisNote= urldecode(\AeSecure\Functions::getCurrentURL(false, false));

        // Keep only the script name and querystring so remove f.i. http://localhost/notes/
        //$thisNote=str_replace(Functions::getCurrentURL(FALSE,TRUE),'',$thisNote);
    
        $toolbar='<div id="icons" class="onlyscreen fa-3x">'.
            '<i id="icon_fullscreen" data-task="fullscreen" class="fa fa-arrows-alt" aria-hidden="true" title="'.$aeSettings->getText('fullscreen', 'Display the note in fullscreen', true).'"></i>'.
            '<i id="icon_refresh" data-task="display" data-file="'.$params['filename'].'" class="fa fa-refresh" aria-hidden="true" title="'.$aeSettings->getText('refresh', 'Refresh', true).'"></i>'.
            '<i id="icon_clipboard" data-task="clipboard" class="fa fa-clipboard" data-clipboard-target="#note_content" aria-hidden="true" title="'.$aeSettings->getText('copy_clipboard', 'Copy the note&#39;s content, with page layout, in the clipboard', true).'"></i>'.
            '<i id="icon_printer" data-task="printer" class="fa fa-print" aria-hidden="true" title="'.$aeSettings->getText('print_preview', 'Print preview', true).'"></i>'.
            '<i id="icon_pdf" data-task="pdf" data-file="'.$params['filename'].'" class="fa fa-file-pdf-o" aria-hidden="true" title="'.$aeSettings->getText('export_pdf', 'Export the note as a PDF document', true).'"></i>'.
            '<i id="icon_link_note" data-task="link_note" class="fa fa-link" data-clipboard-text="'.$thisNote.'" aria-hidden="true" title="'.$aeSettings->getText('copy_link', 'Copy the link to this note in the clipboard', true).'"></i>'.
            '<i id="icon_slideshow" data-task="slideshow" data-file="'.$params['filename'].'" class="fa fa-desktop" aria-hidden="true" title="'.$aeSettings->getText('slideshow', 'slideshow', true).'"></i>'.
            $icons.
            '<i id="icon_settings_clear" data-task="settings" class="fa fa-eraser" aria-hidden="true" title="'.$aeSettings->getText('settings_clean', 'Clear cache', true).'"></i>'.
        '</div>';
      
        $html=$toolbar.'<div id="icon_separator" class="only_screen"/><div id="note_content">'.$html.'</div>';
      
        $html=str_replace('src="images/', 'src="'.$aeSettings->getFolderDocs(false).'/'.str_replace(DS, '/', dirname($params['filename'])).'/images/', $html);
        $html=str_replace('href="files/', 'href="'.$aeSettings->getFolderDocs(false).'/'.str_replace(DS, '/', dirname($params['filename'])).'/files/', $html);
        $html='<div class="hidden filename">'.utf8_encode($fullname).'</div>'.$html.'<hr/>';

        // LazyLoad images ?
        if ($aeSettings->getOptimisationLazyLoad()) {
            $html=str_replace('<img src=', '<img class="lazyload" data-src=', $html);
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        die();
    } // function Run()
} // class Display