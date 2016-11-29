<?php
namespace Coercive\Utility\Xml;

use \Exception;

/**
 * FormatML
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
class FormatML {

    const CONTENT = '_@$#&|||~~~REPLACE_CONTENT~~~|||&#$@_';

    const OPTION_ALL_TAGS_REQUIRED = 'OPTION_ALL_TAGS_REQUIRED';
    const OPTION_SKIP_MISSING_TAGS_CONTENT = 'OPTION_SKIP_MISSING_TAGS_CONTENT';

    /** @var array */
    private $_aSourceArray;

    /** @var array */
    private $_aFormatDirectives;

    /** @var int */
    private $_iAttributes = 0;

    /** @var array */
    private $_aAttributes = [];

    /** @var string */
    private $_sOutputString = '';

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
	 * TO STRING
	 *
	 * @return string
	 */
    public function __toString() {
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
     * @param array $aArray
     * @param array $aFormatDirectives [optional]
     * @param array $aOptions [optional]
     */
    public function __construct($aArray, $aFormatDirectives = [], $aOptions = []) {

	if(!$aArray) {
            self::_exception("EMPTY SOURCE ARRAY", __LINE__, __METHOD__);
        }
	if(!is_array($aArray)) {
	    $aArray = (array) $aArray;
	}
        $this->_aSourceArray = $aArray;

        if(!is_array($aFormatDirectives)) {
            self::_exception("NOT A VALID DIRECTIVE ARRAY", __LINE__, __METHOD__);
        }
        $this->_aFormatDirectives = $aFormatDirectives;

        $this->_aOptions = array_replace_recursive([
            self::OPTION_ALL_TAGS_REQUIRED => false,
            self::OPTION_SKIP_MISSING_TAGS_CONTENT => false
        ], $aOptions);

        $this->_sOutputString = $this->_parse($this->_aSourceArray);
    }

    /**
     * GET FORMATED STRING
     *
     * @return string
     */
    public function get() {
        return $this->_sOutputString ?: '';
    }

    /**
     * PARSE ARRAY TO STRING
     *
     * @param array $aArray
     * @return string
     */
    private function _parse($aArray) {

        # INIT
        $sOutputString = '';

        # EMPTY
        if(empty($aArray)) { return ''; }

        # PROCESSING
        foreach($aArray as $mKey => $mItem) {

            # ATTRIBUTES PREPARE
            if($mKey === '@attributes') {
                $this->_iAttributes++;
                $this->_aAttributes[$this->_iAttributes] = $mItem;
                continue;
            }

            # CONTENT
            switch(gettype($mItem)) {
                case 'string':
                    $sOutputString .= htmlentities($mItem);
                    break;
                case 'boolean':
                case 'integer':
                case 'double':
                case 'NULL':
                    $sOutputString .= (string) $mItem;
                    break;
                case 'array':
                    $sOutputString .= $this->_parse($mItem);
                    break;
                default:
                    self::_exception('ITEM TYPE NO RECOGNIZE : ' . gettype($mItem), __LINE__, __METHOD__);
            }

            # CONTAINER
            if(is_string($mKey) && isset($this->_aFormatDirectives[$mKey])) {

                $mDirective = $this->_aFormatDirectives[$mKey];

                switch(gettype($mDirective)) {

                    case 'string':
                        $sOutputString = str_replace(self::CONTENT, $sOutputString, $mDirective);
                        break;

                    case 'object':
                        $sOutputString = $mDirective($sOutputString, $this->_iAttributes ? $this->_aAttributes[$this->_iAttributes] : []);
                        break;

                    default:
                        self::_exception('NOT VALID DIRECTIVE : ' . $mKey, __LINE__, __METHOD__);

                }
            }
            elseif (is_string($mKey) && $this->_aOptions[self::OPTION_ALL_TAGS_REQUIRED]) {
                self::_exception('ALL TAGS REQUIRED, MISSING : ' . $mKey, __LINE__, __METHOD__);
            }
            elseif(is_string($mKey) && $this->_aOptions[self::OPTION_SKIP_MISSING_TAGS_CONTENT]) {
                $sOutputString = '';
            }

        }

        return html_entity_decode($sOutputString, ENT_QUOTES | ENT_HTML5);

    }

}
