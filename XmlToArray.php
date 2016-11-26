<?php
namespace Coercive\Utility\Xml;

use \Exception;

/**
 * XmlToArray
 * PHP Version 	7
 *
 * @version		1
 * @package 	Coercive\Utility\Xml
 * @link		@link https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2016 - 2017 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class XmlToArray {

    # CLASS OPTIONS
    const XML_OPTION_CASE_FOLDING = 'XML_OPTION_CASE_FOLDING';
    const XML_OPTION_SKIP_WHITE = 'XML_OPTION_SKIP_WHITE';
    const XML_OPTION_TARGET_ENCODING = 'XML_OPTION_TARGET_ENCODING';

	/** @var array */
	private $_aOptions;

	/** @var string */
	private $_sXML = '';

	/** @var array */
	private $_aXML = null;

	/**
	 * EXCEPTION
	 *
	 * @param string $sMessage
	 * @param int $sLine [optional]
	 * @param string $sMethod [optional]
	 * @throws Exception
	 */
	static private function _exception($sMessage, $sLine = __LINE__, $sMethod = __METHOD__) {
		throw new Exception("$sMessage \nMethod :  $sMethod \nLine : $sLine");
	}

	/**
	 * XMLFile constructor.
	 *
	 * @param string $sXmlContent
	 * @param array $aOptions [optional]
	 */
	public function __construct($sXmlContent, $aOptions = []) {

		# OPTIONS
		$this->_aOptions = array_replace_recursive([
            self::XML_OPTION_CASE_FOLDING => 0,
            self::XML_OPTION_SKIP_WHITE => 0,
            self::XML_OPTION_TARGET_ENCODING => 'UTF-8'
		], $aOptions);

		# LOAD
        if(!$sXmlContent || !is_string($sXmlContent)) {
            self::_exception("NOT A VALID XML STRING OR EMPTY", __LINE__, __METHOD__);
        }
        $this->_sXML = $sXmlContent;

	}

	/**
	 * GET XML ARRAY
	 *
	 * @return array
	 */
	public function get() {
        return $this->_aXML ?: [];
	}

    /**
     * PARSE XML TO ARRAY
     *
     * @return $this
     */
    public function parse() {
        $this->_aXML = $this->_parseArray();
        return $this;
    }

	/**
	 * XML TO ARRAY
	 *
	 * @return array
	 */
	private function _parseArray() {

		# INIT
		$iNbProcess = 0;

		/** @var resource $rParser */
		$rParser = xml_parser_create();

		# OPTIONS
        if(!xml_parser_set_option($rParser, XML_OPTION_CASE_FOLDING, $this->_aOptions[self::XML_OPTION_CASE_FOLDING])) {
            self::_exception('ERROR PARSER OPTION : ' . self::XML_OPTION_CASE_FOLDING, __LINE__, __METHOD__);
        }
        if(!xml_parser_set_option($rParser, XML_OPTION_SKIP_WHITE, $this->_aOptions[self::XML_OPTION_SKIP_WHITE])) {
            self::_exception('ERROR PARSER OPTION : ' . self::XML_OPTION_SKIP_WHITE, __LINE__, __METHOD__);
        }
        if(!xml_parser_set_option($rParser, XML_OPTION_TARGET_ENCODING, $this->_aOptions[self::XML_OPTION_TARGET_ENCODING])) {
            self::_exception('ERROR PARSER OPTION : ' . self::XML_OPTION_TARGET_ENCODING, __LINE__, __METHOD__);
        }

		# PARSE ARRAY
        if(!xml_parse_into_struct($rParser, $this->_sXML, $aValues, $aIndexes)) {
            self::_exception('ERROR PARSER : PARSE FAILLURE', __LINE__, __METHOD__);
        }

		# DELETE RESOURCE
        if(!xml_parser_free($rParser)) {
            self::_exception('ERROR PARSER : CAN\'T CLOSE RESOURCE', __LINE__, __METHOD__);
        }

		# SUB PARSE ARRAY
		$aItems = $this->_subParseArray($aValues, $iNbProcess);

		return [$aItems, $iNbProcess];

	}

	/**
	 * SUB XML TAGS
	 *
	 * @param array $aXml
	 * @param int $i
	 * @param null|string $sOpenTag [optional] : For recursiv tag
	 * @return array|string
	 */
	private function _subParseArray($aXml, &$i, $sOpenTag = null) {

		# INIT
		$aItems = array();

		# DEFAULT
		if(!$aXml) { return $aItems; }

		# Ajouter les attributs
		$aItems[$aXml[$i]['tag']] = isset($aXml[$i]['attributes']) ? ['@attributes' => $aXml[$i]['attributes']] : [];

		# TAG COMPLETE
		if ($aXml[$i]['type'] === 'complete' && isset($aXml[$i]['value'])) {
			$aItems[$aXml[$i]['tag']] = isset($aXml[$i]['attributes']) ? array_merge(['@attributes' => $aXml[$i]['attributes']], (array)$aXml[$i]['value']) : $aXml[$i]['value'];
			$i++;
			return $aItems;
		}

		# TAG CDATA
		if ($aXml[$i]['type'] === 'cdata' && isset($aXml[$i]['value'])) {
			$aItems = $aXml[$i]['value'];
			$i++;
			return $aItems;
		}

		# TAG EMPTY
		if ($aXml[$i]['type'] === 'complete') {
			$i++;
			return $aItems;
		}

		// RECURSIV TAG WITH TAGS IN
		if ($aXml[$i]['type'] === 'open') {

			# Open Tag Name / Level
			$sOpenTag = $aXml[$i]['tag'];
			$iLevel   = $aXml[$i]['level'];

			# SET
			if (!isset($aItems[$sOpenTag])) { $aItems[$sOpenTag] = []; }

			# VALUE
			if (isset($aXml[$i]['value'])) { $aItems[$sOpenTag][] = $aXml[$i]['value']; }

			# INIT
			$i++;
			$bComplete = false;

			# LOOP
			while (!$bComplete && $i < count($aXml)) {
				if ($aXml[$i]['tag'] === $sOpenTag &&
					$aXml[$i]['type'] === 'close' &&
					$aXml[$i]['level'] === $iLevel ) {
					$bComplete = true;
				} else {
					$aItems[$sOpenTag][] = $this->_subParseArray($aXml, $i, $sOpenTag);
					if ($i < count($aXml) && $aXml[$i]['type'] === 'close') {
						$i++;
						return $aItems;
					}
				}
			}
			return $aItems;
		}

		# IF HERE => ERROR
		self::_exception('ERROR PARSER SUB PARSE TYPE NO CATCH', __LINE__, __METHOD__);
		return [];
	}

}