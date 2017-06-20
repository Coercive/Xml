<?php
namespace Coercive\Utility\Xml;

use \Exception;

/**
 * ExtractArray
 * PHP Version 	7
 *
 * @version	1
 * @package 	Coercive\Utility\Xml
 * @link	@link https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2016 - 2017 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class ExtractArray {

	# PROPERTIES ASSIGNEMENT
	const ROOT = 'ROOT';
	const ROOT_DELIMITER = 'ROOT_DELIMITER';
	const ROOT_REQUIRED = 'ROOT_REQUIRED';

	/** @var array */
	private $_aOptions;

	/** @var array */
	private $_aSourceArray;

	/** @var array */
	private $_aRootArray;

	/** @var array */
	private $_aProcessArray = [];

	/** @var array */
	private $_aResultsArray = [];

	/** @var array */
	private $_aRootPath;

	/** @var bool */
	private $_bFoundRecurse;
	private $_bGetAll;
	private $_bFoundOne;
	private $_bForeground;

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
	 * ExtractArray constructor.
	 *
	 * @param array $aArray
	 * @param array $aOptions [optional]
	 */
	public function __construct($aArray, $aOptions = []) {

		# OPTIONS
		$this->_aOptions = array_replace_recursive([
			self::ROOT => '/',
            self::ROOT_DELIMITER => '/',
            self::ROOT_REQUIRED => false
		], $aOptions);

		# SET PROPERTIES
        if(!$aArray || !is_array($aArray)) {
            self::_exception("NOT A VALID SOURCE ARRAY OR EMPTY", __LINE__, __METHOD__);
        }

		$this->_aSourceArray = $aArray;

        if(!$this->_aOptions[self::ROOT] || !is_string($this->_aOptions[self::ROOT]) && !is_array($this->_aOptions[self::ROOT])) {
            self::_exception("NOT A VALID ROOT", __LINE__, __METHOD__);
        }

        if(is_string($this->_aOptions[self::ROOT]) && (!$this->_aOptions[self::ROOT_DELIMITER] || !is_string($this->_aOptions[self::ROOT_DELIMITER]))) {
            self::_exception("NOT A VALID ROOT_DELIMITER", __LINE__, __METHOD__);
        }

		$this->_aRootPath = is_string($this->_aOptions[self::ROOT]) ? explode('/', trim($this->_aOptions[self::ROOT], " \t\n\r\0\x0B/")) : $this->_aOptions[self::ROOT];

        $this->_setRootArray();

	}

	/**
	 * SET ROOT ARRAY
	 *
	 * use private $this->_aRootArray
	 *
	 * @return void
	 */
	private function _setRootArray() {

		# SET
		$this->_aRootArray = $this->_aSourceArray;

		# LOOP IF
		foreach($this->_aRootPath as $sRootKey) {

			# REASIGN ROOT
			$this->_aRootArray = $this->_resurseSearch($this->_aRootArray, $sRootKey);

			# REQUIRED ? => BLOCK PROCESS
			if(!$this->_bFoundRecurse && $this->_aOptions[self::ROOT_REQUIRED]) {
				self::_exception('REQUIRED ROOT IS USED :: KEY NOT FOUND : ' . $sRootKey, __LINE__, __METHOD__);
			}

		}

        $this->_aProcessArray = $this->_aRootArray;

	}

	/**
	 * RECURSIVE SEARCH
	 *
	 * @param array|mixed $aArray : Can be mixed if deep parse (field content)
	 * @param string $sKey : no int ! String compare
	 * @return array|mixed : Array of element or field content
	 */
	private function _resurseSearch($aArray, $sKey) {

		# INIT
		$this->_bFoundRecurse = false;

		# SKIP
		if(!$aArray || !is_array($aArray)) { return $aArray; }

		# RECURSIVE Search
		foreach($aArray as $sCurrentKey => $mValue) {

			# FOUND CURRENT KEY
			if($sKey === (string)$sCurrentKey) {
				$this->_bFoundRecurse = true;
				return $mValue;
			}

			# FOUND CONTENT KEY
			if(is_array($mValue) && array_key_exists($sKey, $mValue)) {
				$this->_bFoundRecurse = true;
				return $mValue[$sKey];
			}

			# RECURSIF LAUNCH
			$aFoundRecurse = $this->_resurseSearch($mValue, $sKey);
			if($this->_bFoundRecurse) { return $aFoundRecurse; }
		}

		# NOT FOUND
		return $aArray;

	}

	/**
	 * GETTER
	 *
	 * /!\ Array of Arrays [ [], [], [] ... ] or string if uniq element
	 *
	 * @param string $sKey [optional]
	 * @param bool $bAll [optional]
	 * @param bool $bForeground [optional]
	 * @return array|mixed
	 */
	public function get($sKey = null, $bAll = false, $bForeground = false) {

		# INIT
		$this->_bGetAll = $bAll;
		$this->_bForeground = $bForeground;
		$this->_bFoundOne = false;
		$this->_aResultsArray = [];

		# SKIP
		if(!$this->_aProcessArray || !is_array($this->_aProcessArray)) { return $this->_aProcessArray; }

		# NULL
		if(null === $sKey) { return $this->_aProcessArray; }

		# RECURSE SEARCH
		$this->_aProcessArray = $this->_recurseGet($this->_aProcessArray, $sKey);
		return $this->_aResultsArray;

	}

	/**
	 * RECURSIVE GETTER
	 *
	 * @param array $aArray
	 * @param string $sKey
	 * @return array
	 */
	private function _recurseGet($aArray, $sKey) {

		# SKIP
		if(!$aArray || !is_array($aArray)) { return $aArray; }

		# RECURSIVE Search
		foreach($aArray as $mCurrentKey => $mValue) {

			# FOUND CURRENT KEY
			if($sKey === (string) $mCurrentKey) {
				unset($aArray[$mCurrentKey]);
				$this->_aResultsArray[] = $mValue;
				$this->_bFoundOne = true;
				if(!$this->_bGetAll) { return $aArray; }
				continue;
			}

			# FOREGROUND
			if(is_numeric($mCurrentKey) && $this->_bForeground && is_array($mValue) && isset($mValue[$sKey])) {
				unset($aArray[$mCurrentKey][$sKey]);
				$this->_aResultsArray[] = $mValue;
				$this->_bFoundOne = true;
				if(!$this->_bGetAll) { return $aArray; }
				continue;
			}

			# RECURSIF LAUNCH
			if(!$this->_bForeground && is_array($mValue)) {
				$aArray[$mCurrentKey] = $this->_recurseGet($mValue, $sKey);
				if(!$this->_bGetAll && $this->_bFoundOne) { return $aArray; }
			}

		}

		return $aArray;

	}

}
