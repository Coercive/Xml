<?php
namespace Coercive\Utility\Xml;

use \Exception;

/**
 * XmlCleaner
 * PHP Version 	7
 *
 * @version		1
 * @package 	Coercive\Utility\Xml
 * @link	https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2016 - 2017 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class XmlCleaner {

    # CLASS OPTIONS
    const OPTION_DECODE = 'DECODE';
    const OPTION_DELETE_DOCTYPE = 'DELETE_DOCTYPE';
    const OPTION_DELETE_PARASITIC = 'DELETE_DELETE_PARASITIC';

	/** @var array */
	private $_aOptions;

	/** @var string */
	private $_sXML = '';

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
	 * XMLCleaner constructor.
	 *
	 * @param array $aOptions [optional]
	 */
	public function __construct($aOptions = []) {

		$this->_aOptions = array_replace_recursive([
			self::OPTION_DECODE => [ ['&', '&amp;'] ],
            self::OPTION_DELETE_DOCTYPE => true,
            self::OPTION_DELETE_PARASITIC => true
		], $aOptions);

	}

    /**
     * GET XML CLEANED STRING
     *
     * @return string
     */
	public function get() {
	    return $this->_sXML ?: '';
    }

	/**
	 * FILE LOADER
	 *
	 * @param string $sXmlPath
     * @return $this
	 * @throws Exception
	 */
	public function loadFile($sXmlPath) {

	    # SKIP ON ERROR
        if(!file_exists($sXmlPath) || !is_file($sXmlPath)) {
            self::_exception("NOT A VALID FILE : $sXmlPath", __LINE__, __METHOD__);
        }

		# SET
		$this->_sXML = file_get_contents($sXmlPath);
		if(!$this->_sXML) { self::_exception('FILE GET CONTENT ERROR OR EMPTY', __LINE__, __METHOD__); }

		return $this;

	}

    /**
     * FILE LOADER
     *
     * @param string $sXmlString
     * @return $this
     * @throws Exception
     */
    public function loadString($sXmlString) {

        # SKIP ON ERROR
        if(!$sXmlString || !is_string($sXmlString)) {
            self::_exception("NOT A VALID STRING OR EMPTY", __LINE__, __METHOD__);
        }

        # SET
        $this->_sXML = $sXmlString;

        return $this;

    }

    /**
     * CLEAN
     *
     * @return $this
     */
	public function clean() {

        # DECODE
        $this->_decode();

        # DELETE DOCTYPE
        $this->_deleteDoctype();

        # DELETE PARASITIC
        $this->_deleteParasitic();
	
	# DELETE WHITE SPACE
	$this->_deleteWhiteSpace();
	
	# DELETE TAB
	$this->_deleteTabulate();

	# DELETE LINE FEED
	$this->_deleteLineFeed();

	# DELETE CARRIAGE RETURN
	$this->_deleteCarriageReturn();

	# DELETE NULL BYTE
	$this->_deleteNullByte();

	# DELETE VERTICAL TAB
	$this->_deleteVerticalTab();

        return $this;

    }

	/**
	 * DECODE GLOBAL & SPECIFIC
     *
     * @return void
	 */
	private function _decode() {

		# HTML ENTITY
		$this->_sXML = html_entity_decode($this->_sXML);

		# DECODE SPECIFICS ATTRIBUTES
		foreach($this->_aOptions[self::OPTION_DECODE] as $aSearchReplace) {
			$this->_sXML = str_replace($aSearchReplace[0], $aSearchReplace[1], $this->_sXML);
		}

	}

    /**
     * DELETE DOCTYPE
     *
     * Delete goods and bads doctype witch can cause some xml-parsing errors
     * Example : <!DOCTYPE myxml PUBLIC "-//MYXMLEXAMPLE//DTD MYXMLEXAMPLE XML//EN">
     *
     * @return void
     */
    private function _deleteDoctype() {

        if($this->_aOptions[self::OPTION_DELETE_DOCTYPE]) {
            $this->_sXML = preg_replace('`\<\!DOCTYPE [^<]*\>`i', '', $this->_sXML);
        }

    }

    /**
     * DELETE PARASITIC
     *
     * Delete parasitics elements witch cause fatal xml-parsing errors
     * Examples :   <?lettrine;@0302?> ; <?tblwidth;126m?> ; <?bidencadre3;30;37m?> ; <?Pub Caret?>
     *              <?Pub _bookmark Command="[Quick Mark]"?> ; <?xpp fin_fxapnot?> ; <?xpp error_tag #@@>
     *
     * @return void
     */
    private function _deleteParasitic() {

        if($this->_aOptions[self::OPTION_DELETE_PARASITIC]) {
            $this->_sXML = preg_replace('`\<\?(?!xml)[^<]*\>`i', '', $this->_sXML);
        }

    }
	
	/**
	 * WHITE SPACE
	 *
	 * @return void
	 */
	private function _deleteWhiteSpace() {

		while(strpos($this->_sXML, '  ') !== false) {
			$this->_sXML = str_replace('  ', ' ', $this->_sXML);
		}

	}
	
	/**
	 * TABULATE
	 *
	 * @return void
	 */
	private function _deleteTabulate() {

		$this->_sXML = str_replace("\t", '', $this->_sXML);

	}

	/**
	 * LINE
	 *
	 * @return void
	 */
	private function _deleteLineFeed() {

		$this->_sXML = str_replace("\n", '', $this->_sXML);

	}

	/**
	 * CARRIAGE
	 *
	 * @return void
	 */
	private function _deleteCarriageReturn() {

		$this->_sXML = str_replace("\r", '', $this->_sXML);

	}

	/**
	 * NULL BYTE
	 *
	 * @return void
	 */
	private function _deleteNullByte() {

		$this->_sXML = str_replace("\0", '', $this->_sXML);

	}

	/**
	 * VERTICAL TAB
	 *
	 * @return void
	 */
	private function _deleteVerticalTab() {

		$this->_sXML = str_replace("\x0B", '', $this->_sXML);

	}

}
