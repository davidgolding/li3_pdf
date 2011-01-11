<?php
/**
 * li3_pdf: Pdf for Lithium
 *
 * @copyright     Copyright 2011, Martin Samson <pyrolian@gmail.com>
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace li3_pdf\extensions\adapter\template\view;

use \lithium\util\String;
use \lithium\core\Libraries;
use \lithium\template\TemplateException;
use li3_pdf\extensions\PdfWrapper;

class Pdf extends \lithium\template\view\Renderer implements \ArrayAccess {

	/**
	 * These configuration variables will automatically be assigned to their corresponding protected
	 * properties when the object is initialized.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'classes' => 'merge', 'request', 'context', 'strings', 'handlers', 'view', 'compile'
	);

	/**
	 * An array containing the variables currently in the scope of the template. These values are
	 * manipulable using array syntax against the template object, i.e. `$this['foo'] = 'bar'`
	 * inside your template files.
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Variables that have been set from a view/element/layout/etc. that should be available to the
	 * same rendering context.
	 *
	 * @var array Key/value pairs of variables
	 */
	protected $_vars = array();
	
	/**
	 * `Pdf`'s dependencies. These classes are used by the output handlers to generate URLs
	 * for dynamic resources and static assets, as well as compiling the templates.
	 *
	 * @see Renderer::$_handlers
	 * @var array
	 */
	protected $_classes = array(
		'router' => 'lithium\net\http\Router',
		'media'  => 'lithium\net\http\Media',
		'pdf' => 'li3_pdf\extensions\PdfWrapper'
	);

	protected $Pdf = null;
	
	public function __construct(array $config = array()) {
		$defaults = array('classes' => array(), 'compile' => false, 'extract' => true);
		parent::__construct($config + $defaults);
	}

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param string $data
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = array(), array $options = array()) {
		if(!$this->Pdf){
			$this->Pdf = new PdfWrapper(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		}
		$defaults = array('context' => array());
		$options += $defaults;

		$this->_context = $options['context'] + $this->_context;
		$this->_data = (array) $data + $this->_vars;
		$template__ = $template;
		unset($options, $template, $defaults, $data);
		
		if ($this->_config['extract']) {
			extract($this->_data, EXTR_OVERWRITE);
		} elseif ($this->_view) {
			extract((array) $this->_view->outputFilters, EXTR_OVERWRITE);
		}
		include $template__;
	}

	/**
	 * Returns a template file name
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	public function template($type, $options) {
		if (!isset($this->_config['paths'][$type])) {
			return null;
		}
		$options = array_filter($options, function($item) { return is_string($item); });

		$library = Libraries::get(isset($options['library']) ? $options['library'] : true);
		$options['library'] = $library['path'];
		$path = $this->_paths((array) $this->_config['paths'][$type], $options);
		return $path;
	}

	/**
	 * Allows checking to see if a value is set in template data, i.e. `$this['foo']` in templates.
	 *
	 * @param string $offset The key / variable name to check.
	 * @return boolean Returns `true` if the value is set, otherwise `false`.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_data);
	}

	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}

	/**
	 * Searches a series of path templates for a matching template file, and returns the file name.
	 *
	 * @param array $paths The array of path templates to search.
	 * @param array $options The set of options keys to be interpolated into the path templates
	 *              when searching for the correct file to load.
	 * @return string Returns the first template file found. Throws an exception if no templates
	 *         are available.
	 */
	protected function _paths($paths, $options) {
		foreach ($paths as $path) {
			if (file_exists($path = String::insert($path, $options))) {
				return $path;
			}
		}
		throw new TemplateException("Template not found at {$path}");
	}
}

?>