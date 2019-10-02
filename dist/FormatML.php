<?php
namespace Coercive\Utility\Xml;

use Exception;

/**
 * FormatML
 *
 * @package 	Coercive\Utility\Xml
 * @link		https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2020 Anthony Moral
 * @license 	MIT
 */
class FormatML
{
	const CONTENT = '_@$#&|||~~~REPLACE_CONTENT~~~|||&#$@_';
	const AUTO_ML = '_@$#&|||~~~AUTO_REBUILD_CONTENT~~~|||&#$@_';

	const OPTION_ALL_TAGS_REQUIRED = 'OPTION_ALL_TAGS_REQUIRED';
	const OPTION_SKIP_MISSING_TAGS_CONTENT = 'OPTION_SKIP_MISSING_TAGS_CONTENT';
	const OPTION_AUTO_REBUILD_MISSING_TAGS = 'OPTION_AUTO_REBUILD_MISSING_TAGS';

	const HTML_EMPTY_TAGS = [
		'area',
		'base',
		'br',
		'col',
		'embed',
		'hr',
		'img',
		'input',
		'keygen',
		'link',
		'meta',
		'param',
		'source',
		'track',
		'wbr',
	];

	/** @var array */
	private $options = [];

	/** @var array */
	private $source = [];

	/** @var array */
	private $directives = [];

	/** @var string */
	private $output = '';

	/**
	 * AUTO REBUILD FROM X/HTML TO X/HTML
	 *
	 * @param string $tag
	 * @param string $content
	 * @param array $attributes [optional]
	 * @return string
	 */
	static public function autoRebuild(string $tag, string $content, array $attributes = []): string
	{
		# Attributes to string
		$attrs = '';
		foreach ($attributes as $name => $value) {
			$attrs .= ' ' . $name . '="' . $value . '"';
		}

		# Reformat empty tags
		if(!$content && in_array($tag, self::HTML_EMPTY_TAGS, true)) {
			return '<' . $tag . $attrs . ' />';
		}

		# Classic reformat
		else {
			return '<' . $tag . $attrs . '>' . $content . '</' . $tag . '>';
		}
	}

	/**
	 * TO STRING
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		try {
			return $this->get();
		}
		catch (Exception $e) {
			return '';
		}
	}

	/**
	 * FormatML constructor.
	 *
	 * @param array $datas
	 * @param array $directives [optional]
	 * @param array $options [optional]
	 * @throws Exception
	 */
	public function __construct(array $datas, array $directives = [], array $options = [])
	{
		# Vérify givent datas
		if(!$datas) { throw new Exception('Empty given datas'); }

		# Set source datas
		$this->source = $datas;

		# Set directives
		$this->directives = $directives;

		# Set options (with default)
		$this->options = array_replace_recursive([
			self::OPTION_ALL_TAGS_REQUIRED => false,
			self::OPTION_SKIP_MISSING_TAGS_CONTENT => false,
			self::OPTION_AUTO_REBUILD_MISSING_TAGS => false
		], $options);

		# Verify options
		if(($this->options[self::OPTION_ALL_TAGS_REQUIRED] || $this->options[self::OPTION_SKIP_MISSING_TAGS_CONTENT]) && $this->options[self::OPTION_AUTO_REBUILD_MISSING_TAGS]) {
			throw new Exception('You can\'t use autorebuild system if you required all tag or want to skip missing tags content.');
		}

		# Format document
		$this->output = $this->format($this->source);
	}

	/**
	 * GET FORMATED STRING
	 *
	 * @return string
	 */
	public function get(): string
	{
		return $this->output ?: '';
	}

	/**
	 * PARSE ARRAY TO STRING
	 *
	 * @param array $datas
	 * @param array $parents [optional]
	 * @return string
	 * @throws Exception
	 */
	private function format(array $datas, array $parents = []) {

		# Initialize
		$output = '';

		# No datas, no process
		if(empty($datas)) { return ''; }

		# PROCESSING
		$currentAttr = 0;
		$attributes = [];
		foreach($datas as $key => $item) {

			# Do not process an attribute item
			if($key === '@attributes') { continue; }

			# Extract attributes datas
			if(isset($item['@attributes'])) {
				$currentAttr++;
				$attributes[$currentAttr] = $item['@attributes'];
				unset($item['@attributes']);
			}

			# Prepare content by type
			switch(gettype($item)) {
				case 'string':
					$output .= $item;
					break;
				case 'boolean':
				case 'integer':
				case 'double':
				case 'NULL':
					$output .= (string) $item;
					break;
				case 'array':
					if(is_string($key)) {
						$parents[] = $key;
					}
					$output .= $this->format($item, $parents);
					break;
				default:
					throw new Exception('Item type is not recognized : ' . gettype($item));
			}

			# Process format content with directive
			if(is_string($key) && isset($this->directives[$key])) {

				# Bind in variable for handle function
				$directive = $this->directives[$key];

				switch(gettype($directive)) {

					# Automatic replace
					case 'string':

						# AUTO REBUILD
						if($directive === self::AUTO_ML) {
							$output = $this->autoRebuild($key, $output, $currentAttr ? $attributes[$currentAttr] : []);
							break;
						}

						# AUTO REPLACE
						$output = str_replace(self::CONTENT, $output, $directive);
						break;

					# Custom function handler
					case 'object':
						$output = $directive($output, $currentAttr ? $attributes[$currentAttr] : [], $parents);
						break;

					default:
						throw new Exception('Invalid directive : ' . $key);
				}
			}

			# Send error if all tags required option is enabled
			elseif (is_string($key) && $this->options[self::OPTION_ALL_TAGS_REQUIRED]) {
				throw new Exception('All tags required. Missing : ' . $key);
			}

			# Clear content if skip missing is enable
			elseif(is_string($key) && $this->options[self::OPTION_SKIP_MISSING_TAGS_CONTENT]) {
				$output = '';
			}

			# Auto rebuild missing tags enabled
			elseif(is_string($key) && $this->options[self::OPTION_AUTO_REBUILD_MISSING_TAGS]) {
				$output = self::autoRebuild($key, $output, $currentAttr ? $attributes[$currentAttr] : []);
			}
		}

		# Send formated content
		return $output;
	}
}