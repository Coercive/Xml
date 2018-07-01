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
 * @copyright   (c) 2018 Anthony Moral
 * @license 	MIT
 */
class FormatML
{
    const CONTENT = '_@$#&|||~~~REPLACE_CONTENT~~~|||&#$@_';

    const OPTION_ALL_TAGS_REQUIRED = 'OPTION_ALL_TAGS_REQUIRED';
    const OPTION_SKIP_MISSING_TAGS_CONTENT = 'OPTION_SKIP_MISSING_TAGS_CONTENT';

	/** @var array */
	private $options = [];

    /** @var array */
    private $source = [];

    /** @var array */
    private $directives = [];

    /** @var string */
    private $output = '';

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
            self::OPTION_SKIP_MISSING_TAGS_CONTENT => false
        ], $options);

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
     * @return string
	 * @throws Exception
     */
    private function format(array $datas) {

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
                    $output .= $this->format($item);
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
                        $output = str_replace(self::CONTENT, $output, $directive);
                        break;

					# Custom function handler
                    case 'object':
                        $output = $directive($output, $currentAttr ? $attributes[$currentAttr] : []);
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

        }

        # Send formated content
        return $output;
    }
}