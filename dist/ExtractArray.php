<?php
namespace Coercive\Utility\Xml;

use Exception;

/**
 * ExtractArray
 *
 * @package 	Coercive\Utility\Xml
 * @link		https://github.com/Coercive/Xml
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2019 Anthony Moral
 * @license 	MIT
 */
class ExtractArray
{
	/** @var bool Array is init */
	private $booted = false;

	/** @var string Root path */
	private $root = '/';

	/** @var string Path step delimiter */
	private $delimiter = '/';

	/** @var bool Path required */
	private $required = false;

	/** @var array */
	private $source;

	/** @var array */
	private $processed = [];

	/** @var array */
	private $path;

	/** @var array */
	private $result = [];

	/** @var bool */
	private $recurse;

	/** @var bool */
	private $all;

	/** @var bool */
	private $founded;

	/** @var bool */
	private $foreground;

	/**
	 * RECURSIVE SEARCH
	 *
	 * @param array|mixed $root : Can be mixed if deep parse (field content)
	 * @param string $key : no int ! String compare
	 * @return array|mixed : Array of element or field content
	 */
	private function root($root, string $key)
	{
		# Initialize found recurse
		$this->recurse = false;

		# No datas, no process
		if(!$root) { return $root; }

		# No container
		if(!is_array($root)) { return $root; }

		# Recursive search
		foreach($root as $current => $datas) {

			# Found current key
			if($key === (string) $current) {
				$this->recurse = true;
				return $datas;
			}

			# Found key in content
			if(is_array($datas) && array_key_exists($key, $datas)) {
				$this->recurse = true;
				return $datas[$key];
			}

			# Recursif launch
			$inside = $this->root($datas, $key);
			if($this->recurse) { return $inside; }
		}

		# Not found
		return $root;
	}

	/**
	 * RECURSIVE GETTER
	 *
	 * @param mixed $datas
	 * @param string $key
	 * @return mixed
	 */
	private function extract($datas, string $key)
	{
		# Return current if no datas or if the end is reached
		if(!$datas || !is_array($datas)) { return $datas; }

		# Recursive search : key, inside key, or loop
		foreach($datas as $current => $data) {

			# CURRENT KEY FOUNDED
			if($key === (string) $current) {
				unset($datas[$current]);
				$this->result[] = [$current => $data];
				$this->founded = true;
				if(!$this->all) { return $datas; }
				continue;
			}

			# FOREGROUND
			if(is_numeric($current) && is_array($data) && isset($data[$key])) {
				unset($datas[$current][$key]);
				$this->result[] = $data;
				$this->founded = true;
				if(!$this->all) { return $datas; }
				continue;
			}

			# RECURSIF LAUNCH
			if($this->foreground && is_array($data)) {
				$datas[$current] = $this->extract($data, $key);
				if(!$this->all && $this->founded) { return $datas; }
			}

		}

		# Send prepared datas
		return $datas;
	}

	/**
	 * ExtractArray constructor.
	 *
	 * @param array $datas
	 * @param string $root [optional]
	 * @param string $delimiter [optional]
	 * @param bool $required [optional]
	 * @throws Exception
	 */
	public function __construct(array $datas, string $root = '/', string $delimiter = '/', bool $required = false)
	{
		# Set source
		$this->source = $datas;
		if(!$datas) {
			throw new Exception('Can\'t extract empty datas');
		}

		# Auto set
		$this
			->setRoot($root)
			->setDelimiter($delimiter)
			->setRequired($required);
	}

	/**
	 * Set root path
	 *
	 * @param string $root [optional]
	 * @return ExtractArray
	 */
	public function setRoot(string $root = '/'): ExtractArray
	{
		if(!$root) {
			throw new Exception('Root must be a valid string and not empty.');
		}
		$this->root = $root;
		return $this;
	}

	/**
	 * Set delimiter
	 *
	 * @param string $delimiter [optional]
	 * @return ExtractArray
	 */
	public function setDelimiter(string $delimiter = '/'): ExtractArray
	{
		if(!$delimiter) {
			throw new Exception('Delimiter is empty.');
		}
		$this->delimiter = $delimiter;
		return $this;
	}

	/**
	 * Set requiered path
	 *
	 * @param bool $state [optional]
	 * @return ExtractArray
	 */
	public function setRequired(bool $state = false): ExtractArray
	{
		$this->required = $state;
		return $this;
	}

	/**
	 * Set root array
	 *
	 * @return void
	 * @throws Exception
	 */
	public function boot()
	{
		# Set path
		$this->path = explode($this->delimiter, trim($this->root, " \t\n\r\0\x0B{$this->delimiter}"));

		# Init root for process
		$root = $this->source;

		# Assign root
		foreach($this->path as $key) {

			# Reasign root
			$root = $this->root($root, $key);

			# Required but not found : skip error
			if(!$this->recurse && $this->required) {
				throw new Exception('Required root not found for key : ' . $key . ', in this path : ' . $this->root);
			}

		}

		# Set datas for process
		$this->processed = $root;

		# Set already booted
		$this->booted = true;
	}

	/**
	 * GETTER
	 *
	 * /!\ Array of Arrays [ [], [], [] ... ] or string if uniq element
	 *
	 * @param string $key [optional]
	 * @param bool $all [optional]
	 * @param bool $foreground [optional]
	 * @return array|mixed
	 * @throws Exception
	 */
	public function get(string $key = null, bool $all = false, $foreground = false)
	{
		# Auto boot
		if(!$this->booted) {
			$this->boot();
		}

		# Initialize properties
		$this->all = $all;
		$this->foreground = $foreground;
		$this->founded = false;
		$this->result = [];

		# Empty target
		if(!$this->processed) {
			return $this->processed;
		}

		# Root content
		if(null === $key) {
			return $this->processed;
		}

		# Start recurse extract search
		$this->processed = $this->extract($this->processed, $key);
		return $this->result;
	}
}
