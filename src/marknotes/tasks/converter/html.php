<?php
/**
 * Display the HTML rendering of the note in a nice HTML layout.
 * Use the defined template, from settings.json->template->html
 * Called when the URL is something like http://localhost/notes/docs/marknotes.html
 * i.e. accessing the .html file
 */

namespace MarkNotes\Tasks\Converter;

defined('_MARKNOTES') or die('No direct access allowed');

class HTML
{
	protected static $hInstance = null;

	public function __construct()
	{
		return true;
	}

	public static function getInstance()
	{
		if (self::$hInstance === null) {
			self::$hInstance = new HTML();
		}

		return self::$hInstance;
	}

	/**
	 * @param  string  $html [description]	html rendering
	 *					of the .md file
	 * @return {[type]		Nothing
	 */
	public function run(string $html, array $params = null) : string
	{
		$aeEvents = \MarkNotes\Events::getInstance();
		$aeFiles = \MarkNotes\Files::getInstance();
		$aeHTML = \MarkNotes\FileType\HTML::getInstance();
		$aeSession = \MarkNotes\Session::getInstance();
		$aeSettings = \MarkNotes\Settings::getInstance();

		// Give headings an ID
		$html = $aeHTML->addHeadingsID($html, false);

		// Give an ID to every single <p> so we can easily
		// use AnchorJS library to create links to them
		// https://github.com/bryanbraun/anchorjs
		$html = $aeHTML->addParagraphsID($html);

		// Check if a template has been specified in the parameters
		// and if so, check that this file exists, default is html
		$template = $aeSettings->getTemplateFile('html');

		if (isset($params['template'])) {
			$template = $aeSettings->getTemplateFile($params['template']);
			if (!$aeFiles->exists($template)) {
				$template = $aeSettings->getTemplateFile('html');
			}
		}

		if ($aeFiles->exists($template)) {
			$html = $aeHTML->replaceVariables($aeFiles->getContent($template), $html, $params);
		}

		// --------------------------------
		// Get additionnal CSS and JS

		// Call render.html present in page HTML plugins
		$aeEvents->loadPlugins('page.html');
		$args = array(&$html);
		$aeEvents->trigger('page.html::render.html', $args);
		$html = $args[0];

		// Remember the HTML before calling render.js and render.css
		// to make possible to not load .js / .css based on the
		// resulting HTML content
		$aeSession->set('html', $html);

		if (strpos($html, '<!--%ADDITIONNAL_JS%-->') !== false) {
			$aeEvents->loadPlugins('page.html');
			$additionnalJS = '';
			$args = array(&$additionnalJS);
			$aeEvents->trigger('page.html::render.js', $args);
			$html = str_replace('<!--%ADDITIONNAL_JS%-->', $args[0], $html);
		}

		if (strpos($html, '<!--%ADDITIONNAL_CSS%-->') !== false) {
			$aeEvents->loadPlugins('page.html');
			$additionnalCSS = '';
			$args = array(&$additionnalCSS);
			$aeEvents->trigger('page.html::render.css', $args);
			$html = str_replace('<!--%ADDITIONNAL_CSS%-->', $args[0], $html);
		}

		return $html;
	}
}
