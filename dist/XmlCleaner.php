<?php
namespace Coercive\Utility\Xml;

use Exception;

/**
 * XmlCleaner
 *
 * @package 	Coercive\Utility\Xml
 * @link		https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2018 Anthony Moral
 * @license 	MIT
 */
class XmlCleaner
{
	# CLASS OPTIONS
	const OPTION_DECODE = 'DECODE';
	const OPTION_ENCODE_LOST_CHEVRON = 'OPTION_ENCODE_LOST_CHEVRON';
	const OPTION_OVERENCODE_ENCODED_CHEVRON = 'OPTION_OVERENCODE_ENCODED_CHEVRON';
	const OPTION_DELETE_DOCTYPE = 'DELETE_DOCTYPE';
	const OPTION_DELETE_COMMENT = 'OPTION_DELETE_COMMENT';
	const OPTION_DELETE_PARASITIC = 'DELETE_DELETE_PARASITIC';
	const OPTION_TAGS_CONVERSION = 'OPTION_TAGS_CONVERSION';

	/** @var array */
	private $options;

	/** @var string */
	private $xml = '';

	/**
	 * XMLCleaner constructor.
	 *
	 * @param array $options [optional]
	 */
	public function __construct(array $options = [])
	{
		$this->options = array_replace([
			self::OPTION_DECODE => [ ['&', '&amp;'] ],
			self::OPTION_ENCODE_LOST_CHEVRON => true,
			self::OPTION_OVERENCODE_ENCODED_CHEVRON => true,
			self::OPTION_DELETE_DOCTYPE => true,
			self::OPTION_DELETE_PARASITIC => true,
			self::OPTION_DELETE_COMMENT => true,
			self::OPTION_TAGS_CONVERSION => [ 'br' ]
		], $options);
	}

	/**
	 * GET XML CLEANED STRING
	 *
	 * @return string
	 */
	public function get(): string
	{
		return $this->xml ?: '';
	}

	/**
	 * FILE LOADER
	 *
	 * @param string $path
	 * @return XmlCleaner
	 * @throws Exception
	 */
	public function loadFile(string $path): XmlCleaner
	{
		# Skip on error
		if(!is_file($path)) { throw new Exception('Can\'t open the given file path : ' . $path); }

		# Open file and get datas
		$this->xml = file_get_contents($path);
		if(!$this->xml) { throw new Exception('Can\'t open file or empty'); }

		# Maintain chainability
		return $this;
	}

	/**
	 * FILE LOADER
	 *
	 * @param string $xml
	 * @return XmlCleaner
	 * @throws Exception
	 */
	public function loadString(string $xml): XmlCleaner
	{
		# Skip on error
		if(!$xml) { throw new Exception('Can\'t load xml datas or empty'); }

		# Set xml datas
		$this->xml = $xml;

		# Maintain chainability
		return $this;
	}

	/**
	 * CLEAN
	 *
	 * @return XmlCleaner
	 */
	public function clean(): XmlCleaner
	{
		# DECODE
		$this->decode();

		# CHEVRONS
		$this->encodeLostChevrons();

		# DELETE DOCTYPE
		$this->deleteDoctype();

		# DELETE PARASITIC
		$this->deleteParasitic();

		# DELETE COMMENTS
		$this->deleteComments();

		# TAG CONVERSION
		$this->tagsConversion();

		# DELETE WHITE SPACE
		$this->deleteWhiteSpace();

		# DELETE TAB
		$this->deleteTabulate();

		# DELETE LINE FEED
		$this->deleteLineFeed();

		# DELETE CARRIAGE RETURN
		$this->deleteCarriageReturn();

		# DELETE NULL BYTE
		$this->deleteNullByte();

		# DELETE VERTICAL TAB
		$this->deleteVerticalTab();

		# Maintain chainability
		return $this;
	}

	/**
	 * DECODE GLOBAL & SPECIFIC
	 *
	 * @return XmlCleaner
	 */
	public function decode(): XmlCleaner
	{
		# Force overencode already encoded chevron if enabled
		if($this->options[self::OPTION_OVERENCODE_ENCODED_CHEVRON]) {
			$this->xml = str_replace('&lt;', '&amp;lt;', $this->xml);
			$this->xml = str_replace('&gt;', '&amp;gt;', $this->xml);
		}

		# Decode entities datas
		$this->xml = html_entity_decode($this->xml);

		# Decode customs attributes
		foreach($this->options[self::OPTION_DECODE] as $replace) {
			$this->xml = str_replace($replace[0], $replace[1], $this->xml);
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * ENCODE CHEVRONS
	 *
	 * @return XmlCleaner
	 */
	public function encodeLostChevrons(): XmlCleaner
	{
		# Not activated
		if(!$this->options[self::OPTION_ENCODE_LOST_CHEVRON]) { return $this; }

		# Encodage des chevrons ouvrants
		$this->xml = preg_replace_callback(
			'`\<(?:[^a-z/\?\!]|[\s])`',
			function ($aMatches) {
				return str_replace('<', '&amp;lt;', $aMatches[0]);
			},
			$this->xml
		);

		# Encodage des chevrons fermants
		$this->xml = preg_replace_callback(
			'`(?:[\s]|[^a-z0-9"\'/\?\-])\>`',
			function ($aMatches) {
				return str_replace('>', '&amp;gt;', $aMatches[0]);
			},
			$this->xml
		);

		# Maintain chainability
		return $this;
	}

	/**
	 * DELETE DOCTYPE
	 *
	 * Delete goods and bads doctype witch can cause some xml-parsing errors
	 * Example : <!DOCTYPE myxml PUBLIC "-//MYXMLEXAMPLE//DTD MYXMLEXAMPLE XML//EN">
	 *
	 * @return XmlCleaner
	 */
	public function deleteDoctype(): XmlCleaner
	{
		# Delete doctype if activated
		if($this->options[self::OPTION_DELETE_DOCTYPE]) {
			$this->xml = preg_replace('`\<\!DOCTYPE [^<]*\>`i', '', $this->xml);
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * DELETE PARASITIC
	 *
	 * Delete parasitics elements witch cause fatal xml-parsing errors
	 * Examples :   <?lettrine;@0302?> ; <?tblwidth;126m?> ; <?bidencadre3;30;37m?> ; <?Pub Caret?>
	 *              <?Pub _bookmark Command="[Quick Mark]"?> ; <?xpp fin_fxapnot?> ; <?xpp error_tag #@@>
	 *
	 * @return XmlCleaner
	 */
	public function deleteParasitic(): XmlCleaner
	{
		# Delete parasitics datas if option enabled
		if($this->options[self::OPTION_DELETE_PARASITIC]) {
			$this->xml = preg_replace('`\<\?(?!xml)[^<]*\>`i', '', $this->xml);
			$this->xml = preg_replace('`\<\?[^<]*\?\>`i', '', $this->xml);
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * DELETE COMMENTS
	 *
	 * Example : <!-- ... -->
	 *
	 * @return XmlCleaner
	 */
	public function deleteComments(): XmlCleaner
	{
		# Delete doctype if activated
		if($this->options[self::OPTION_DELETE_COMMENT]) {
			$this->xml = preg_replace('`<!--.*-->`', '', $this->xml);
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * WHITE SPACE
	 *
	 * @return XmlCleaner
	 */
	public function deleteWhiteSpace(): XmlCleaner
	{
		# Delete double whitespaces
		while(strpos($this->xml, '  ') !== false) {
			$this->xml = str_replace('  ', ' ', $this->xml);
		}

		# Maintain chainability
		return $this;
	}

	/**
	 * TABULATE
	 *
	 * @return XmlCleaner
	 */
	public function deleteTabulate(): XmlCleaner
	{
		# Delete tabulate characters
		$this->xml = str_replace("\t", '', $this->xml);

		# Maintain chainability
		return $this;
	}

	/**
	 * LINE
	 *
	 * @return XmlCleaner
	 */
	public function deleteLineFeed(): XmlCleaner
	{
		# Delete new line characters
		$this->xml = str_replace("\n", '', $this->xml);

		# Maintain chainability
		return $this;
	}

	/**
	 * CARRIAGE
	 *
	 * @return XmlCleaner
	 */
	public function deleteCarriageReturn(): XmlCleaner
	{
		# Delete new carriage characters
		$this->xml = str_replace("\r", '', $this->xml);

		# Maintain chainability
		return $this;
	}

	/**
	 * NULL BYTE
	 *
	 * @return XmlCleaner
	 */
	public function deleteNullByte(): XmlCleaner
	{
		# Delete null byte characters
		$this->xml = str_replace("\0", '', $this->xml);

		# Maintain chainability
		return $this;
	}

	/**
	 * VERTICAL TAB
	 *
	 * @return XmlCleaner
	 */
	public function deleteVerticalTab(): XmlCleaner
	{
		# Delete vertical tabs characters
		$this->xml = str_replace("\x0B", '', $this->xml);

		# Maintain chainability
		return $this;
	}

	/**
	 * TAGS CONVERSION
	 *
	 * @return XmlCleaner
	 */
	public function tagsConversion(): XmlCleaner
	{
		# Characters conversions if enabled
		if($this->options[self::OPTION_TAGS_CONVERSION]) {
			foreach ($this->options[self::OPTION_TAGS_CONVERSION] as $tag) {
				$this->xml = preg_replace("`<$tag( [^>]*)?(?<!/)>`i", "<$tag$1 />", $this->xml);
			}
		}

		# Maintain chainability
		return $this;
	}
}
