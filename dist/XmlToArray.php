<?php
namespace Coercive\Utility\Xml;

use Exception;

/**
 * XmlToArray
 *
 * @package 	Coercive\Utility\Xml
 * @link		https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2018 Anthony Moral
 * @license 	MIT
 */
class XmlToArray
{
    # CLASS OPTIONS
    const XML_OPTION_CASE_FOLDING = 'XML_OPTION_CASE_FOLDING';
    const XML_OPTION_SKIP_WHITE = 'XML_OPTION_SKIP_WHITE';
    const XML_OPTION_TARGET_ENCODING = 'XML_OPTION_TARGET_ENCODING';

	/** @var array */
	private $options;

	/** @var string */
	private $xml = '';

	/** @var array */
	private $datas = null;

	/**
	 * XMLFile constructor.
	 *
	 * @param string $xml
	 * @param array $options [optional]
	 * @throws Exception
	 */
	public function __construct(string $xml, array $options = [])
	{
		# Handle options
		$this->options = array_replace_recursive([
            self::XML_OPTION_CASE_FOLDING => 0,
            self::XML_OPTION_SKIP_WHITE => 0,
            self::XML_OPTION_TARGET_ENCODING => 'UTF-8'
		], $options);

		# Vérify datas
        if(!$xml) { throw new Exception('Empty xml content'); }

        # Bind datas
        $this->xml = $xml;
	}

	/**
	 * GET XML ARRAY
	 *
	 * @return array
	 */
	public function get(): array
	{
        return $this->datas ?: [];
	}

    /**
     * PARSE XML TO ARRAY
     *
     * @return XmlToArray
	 * @throws Exception
     */
    public function parse(): XmlToArray
	{
		# Start parse process
		if(null === $this->datas) {
			 $this->init();
		}

        # Maintain chainability
        return $this;
    }

	/**
	 * XML TO ARRAY
	 *
	 * @return XmlToArray
	 * @throws Exception
	 */
	private function init(): XmlToArray
	{
		# Initialize loop parser
		$nb = 0;

		/** @var resource $parser Load xml parser */
		$parser = xml_parser_create();

		# Controls whether case-folding is enabled for this XML parser.
        if(!xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, $this->options[self::XML_OPTION_CASE_FOLDING])) {
            throw new Exception('Error parser option : XML_OPTION_CASE_FOLDING');
        }

        # Whether to skip values consisting of whitespace characters.
		if(!xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $this->options[self::XML_OPTION_SKIP_WHITE])) {
			throw new Exception('Error parser option : XML_OPTION_SKIP_WHITE');
        }

        # Sets which target encoding to use in this XML parser
		# By default, it is set to the same as the source encoding used by xml_parser_create().
		# Supported target encodings are ISO-8859-1, US-ASCII and UTF-8.
        if(!xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $this->options[self::XML_OPTION_TARGET_ENCODING])) {
			throw new Exception('Error parser option : XML_OPTION_TARGET_ENCODING');
        }

		# Parsing process : transform xml to basic struct
        if(!xml_parse_into_struct($parser, $this->xml, $struct, $aIndexes)) {
        	$code = xml_get_error_code($parser);
        	$message = xml_error_string($code);
			throw new Exception("Error parser failure\n - Code : $code\n - Message : $message");
        }

		# Free parser memory
        if(!xml_parser_free($parser)) {
			throw new Exception('Can\'t close parser ressource');
        }

		# SUB PARSE ARRAY
		$datas = $this->recurseParse($struct, $nb);

        # Set prepared datas
		$this->datas = [$datas, $nb];

		# Maintain chainability
		return $this;
	}

	/**
	 * SUB XML TAGS
	 *
	 * @param array $xml
	 * @param int $i
	 * @param null|string $openTag [optional] : For recursiv tag
	 * @return array|string
	 * @throws Exception
	 */
	private function recurseParse(array $xml, int &$i, string $openTag = null)
	{
		# Initialize datas
		$datas = array();

		# Skip if empty xml part
		if(!$xml) { return $datas; }

		# Ajouter les attributs
		$datas[$xml[$i]['tag']] = isset($xml[$i]['attributes']) ? ['@attributes' => $xml[$i]['attributes']] : [];

		# TAG COMPLETE
		if ($xml[$i]['type'] === 'complete' && isset($xml[$i]['value'])) {
			$datas[$xml[$i]['tag']] = isset($xml[$i]['attributes']) ? array_merge(['@attributes' => $xml[$i]['attributes']], (array)$xml[$i]['value']) : $xml[$i]['value'];
			$i++;
			return $datas;
		}

		# TAG CDATA
		if ($xml[$i]['type'] === 'cdata' && isset($xml[$i]['value'])) {
			$datas = $xml[$i]['value'];
			$i++;
			return $datas;
		}

		# TAG EMPTY
		if ($xml[$i]['type'] === 'complete') {
			$i++;
			return $datas;
		}

		# RECURSIV TAG WITH TAGS IN
		if ($xml[$i]['type'] === 'open') {

			# Open Tag Name / Level
			$openTag = $xml[$i]['tag'];
			$level = $xml[$i]['level'];

			# SET
			if (!isset($datas[$openTag])) { $datas[$openTag] = []; }

			# VALUE
			if (isset($xml[$i]['value'])) { $datas[$openTag][] = $xml[$i]['value']; }

			# INIT
			$i++;
			$completed = false;

			# LOOP
			while (!$completed && $i < count($xml)) {
				if ($xml[$i]['tag'] === $openTag &&
					$xml[$i]['type'] === 'close' &&
					$xml[$i]['level'] === $level ) {
					$completed = true;
				} else {
					$datas[$openTag][] = $this->recurseParse($xml, $i, $openTag);
					if ($i < count($xml) && $xml[$i]['type'] === 'close') {
						$i++;
						return $datas;
					}
				}
			}
			return $datas;
		}

		# IF HERE => ERROR
		throw new Exception('Recurse parser error : type not catched');
	}
}
