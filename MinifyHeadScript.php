<?php

/**
 * 
 * Helper for setting and retrieving script elements for HTML head section
 * with the added twist of minifying the javascript files. 
 * 
 * ** PREREQUISITES **
 * This file expects that you have installed in ../ZendFramworkProject/Public/min 
 * and that it is working. If your location has changed, modify 
 * $this->$_minifyLocation to your current location.
 * 
 * ** INSTALLATION **
 * Simply drop this file into your ../ZendFramworkProject/application/views/helpers
 * directory.
 * 
 * ** USAGE **
 * In your Layout or View scripts, you can simply call minifyHeadScript
 * in the same way that you used to call headScript. Here is an example:
 * 
  echo $this->minifyHeadScript()	   
	->prependFile('/js/jquery.delaytrigger.js')	// 11th
	->prependFile('/js/sorttable.js')				    // 10th
	->prependFile('/js/jquery.alerts.js')			  // 9th
	->prependFile('/js/jqModal.js')					    // 8th
	->prependFile('/js/jquery.maskedinput.js')	// 7th
	->prependFile('/js/jquery.checkbox.js')			// 6th
	->prependFile('/js/jquery.tablesorter.min.js')	// 5th
	->prependFile('/js/jquery.autocomplete.js')	// 4th
	->prependFile('/js/jquery.color.js')			  // 3rd
	->prependFile('/js/jquery-1.3.2.min.js') 		// 2nd
	->prependFile('/js/main.js') 					      // 1st
	->appendScript('
		$(document).ready(function() {
			$(\'#ajaxWait\').ajaxStart(function() {
		      $(this).show();
		    }).ajaxStop(function() {
		      $(this).hide();
			      });
			
			try { init(); } catch(e) {}
		});                                       // Last
	');
 * 
 * 
 *
 * @see 			 http://code.google.com/p/minify/
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2010-2011 Signature Tech Studios (http://www.stechstudio.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @author 		 Rob "Bubba" Hines
 *
 */
class Zend_View_Helper_MinifyHeadScript extends Zend_View_Helper_HeadScript {
	
	/**
	 * 
	 * The folder to be appended to the base url to find minify on your server.
	 * The default assumes you installed minify in your documentroot\min directory
	 * if you modified the directory name at all, you need to let the helper know 
	 * here.
	 * @var string
	 */
	protected $_minifyLocation = '/min/';
	
	/**
	 * Registry key for placeholder
	 * @var string
	 */
	protected $_regKey = 'Zend_View_Helper_MinifyHeadScript';
	
	/**
	 * Return headScript object
	 *
	 * Returns headScript helper object; optionally, allows specifying a script
	 * or script file to include.
	 *
	 * @param  string $mode 			Script or file
	 * @param  string $spec 			Script/url
	 * @param  string $placement	Append, prepend, or set
	 * @param  array  $attrs 			Array of script attributes
	 * @param  string $type 			Script type and/or array of script attributes
	 * @return Zend_View_Helper_HeadScript
	 */
	public function minifyHeadScript($mode = Zend_View_Helper_HeadScript::FILE, $spec = null, $placement = 'APPEND', array $attrs = array(), $type = 'text/javascript') {
		return parent::headScript($mode, $spec, $placement, $attrs, $type);
	}
	
	/**
	 * 
	 * Gets a string representation of the headscripts suitable for inserting
	 * in the html head section. All included javascript files will be minified
	 * and any script sections will remain as is. 
	 * 
	 * It is important to note that the minified javascript files will be minified
	 * in reverse order of being added to this object, and ALL files will be rendered
	 * prior to inline scripts being rendered.
	 *
	 * @see Zend_View_Helper_HeadScript->toString()
	 * @param  string|int $indent
	 * @return string
	 */
	public function toString($indent = null) {
		// An array of Script Items to be rendered
		$items = array();
		
		// An array of Javascript Items
		$scripts = array();
		
		// The base URL
		$baseUrl = $this->getBaseUrl();
		
		// Any indentation we should use.
		$indent = (null !== $indent) ? $this->getWhitespace($indent) : $this->getIndent();
		
		// Will use this for minifying the javascript files.
		$minItem = new stdClass();
		$minItem->type = 'text/javascript';
		
		// Determining the appropriate way to handle inline scripts
		if ($this->view) {
			$useCdata = $this->view->doctype()->isXhtml() ? true : false;
		} else {
			$useCdata = $this->useCdata ? true : false;
		}
		
		$escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
		$escapeEnd = ($useCdata) ? '//]]>' : '//-->';
		
		// we need to find all the files for inclusion and add to our scripts array
		foreach ( $this as $item ) {
			if (!$this->_isValid($item)) {
				continue;
			}
			if (isset($item->attributes ['src']) && !empty($item->attributes ['src'])) {
				$scripts [] = str_replace($baseUrl, '', $item->attributes ['src']);
			}
		}
		
		//remove the slash at the beginning if there is one  
		if (substr($baseUrl, 0, 1) == '/') {
			$baseUrl = substr($baseUrl, 1);
		}
		
		// We will create our minify URL here. 
		if (is_null($baseUrl) || $baseUrl == '') {
			$minItem->attributes ['src'] = $this->getMinUrl() . '?f=' . implode(',', $scripts);
		} else {
			$minItem->attributes ['src'] = $this->getMinUrl() . '?b=' . $baseUrl . '&f=' . implode(',', $scripts);
		}
		
		// we got our minified item, add it to the Item Array.
		$items [] = $this->itemToString($minItem, '', '', '');
		
		// now get the rest of the inlines scripts, if any
		$this->getContainer()->ksort();
		foreach ( $this as $item ) {
			if (!$this->_isValid($item)) {
				continue;
			}
			// skip it if it is a file, we already did those.
			if (isset($item->attributes ['src']) && !empty($item->attributes ['src'])) {
				continue;
			}
			// add it to the items array
			$items [] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
		}
		
		// turn it into one big string
		$return = implode($this->getSeparator(), $items);
		
		// send it back
		return $return;
	}
	
	/**
	 * Retrieve the minify url
	 *
	 * @return string
	 */
	public function getMinUrl() {
		return $this->getBaseUrl() . $this->_minifyLocation;
	}
	
	/**
	 * Retrieve the currently set base URL
	 *
	 * @return string
	 */
	public function getBaseUrl() {
		return Zend_Controller_Front::getInstance()->getBaseUrl();
	}

}