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
 * @copyright   (c) 2018 Anthony Moral
 * @license 	MIT
 */
class ExtractArray
{
	# PROPERTIES ASSIGNEMENT
	const ROOT = 'ROOT';
	const ROOT_DELIMITER = 'ROOT_DELIMITER';
	const ROOT_REQUIRED = 'ROOT_REQUIRED';

	/** @var array */
	private $options;

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
	private $all;
	private $founded;
	private $foreground;

	/**
	 * ExtractArray constructor.
	 *
	 * @param array $datas
	 * @param array $options [optional]
	 * @throws Exception
	 */
	public function __construct(array $datas, array $options = [])
	{
		# Prepare options
		$this->options = array_replace_recursive([
			self::ROOT => '/',
            self::ROOT_DELIMITER => '/',
            self::ROOT_REQUIRED => false
		], $options);

		# Verify datas
        if(!$datas) { throw new Exception('Can\'t extract empty datas'); }

        # Set source
		$this->source = $datas;

        # Verify root
        if(!$this->options[self::ROOT] || !is_string($this->options[self::ROOT]) && !is_array($this->options[self::ROOT])) {
			throw new Exception('Root must be a valid string or array and not empty');
        }

        # Verify delimiter
        if(is_string($this->options[self::ROOT]) && (!$this->options[self::ROOT_DELIMITER] || !is_string($this->options[self::ROOT_DELIMITER]))) {
			throw new Exception('Root is set as string, but the delimiter is empty or not string');
        }

        # Set path
		$this->path = is_string($this->options[self::ROOT]) ? explode('/', trim($this->options[self::ROOT], " \t\n\r\0\x0B/")) : $this->options[self::ROOT];

        # Init root array for process
        $this->setRoot();
	}

	/**
	 * SET ROOT ARRAY
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setRoot()
	{
		# Init root for process
		$root = $this->source;

		# Assign root
		foreach($this->path as $key) {

			# Reasign root
			$root = $this->search($root, $key);

			# Required but not found : skip error
			if(!$this->recurse && $this->options[self::ROOT_REQUIRED]) {
				throw new Exception('Required root not found. Clé : ' . $key);
			}

		}

		# Set datas for process
        $this->processed = $root;
	}

	/**
	 * RECURSIVE SEARCH
	 *
	 * @param array|mixed $root : Can be mixed if deep parse (field content)
	 * @param string $key : no int ! String compare
	 * @return array|mixed : Array of element or field content
	 */
	private function search($root, string $key)
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
			$inside = $this->search($datas, $key);
			if($this->recurse) { return $inside; }
		}

		# Not found
		return $root;
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
	 */
	public function get(string $key = null, bool $all = false, $foreground = false)
	{
		# Initialize properties
		$this->all = $all;
		$this->foreground = $foreground;
		$this->founded = false;
		$this->result = [];

		# Empty target
		if(!$this->processed) { return $this->processed; }

		# Root content
		if(null === $key) { return $this->processed; }

		# Start recurse extract search
		$this->processed = $this->extract($this->processed, $key);
		return $this->result;
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
				$this->result[] = $data;
				$this->founded = true;
				if(!$this->all) { return $datas; }
				continue;
			}

			# FOREGROUND
			if(is_numeric($current) && $this->foreground && is_array($data) && isset($data[$key])) {
				unset($datas[$current][$key]);
				$this->result[] = $data;
				$this->founded = true;
				if(!$this->all) { return $datas; }
				continue;
			}

			# RECURSIF LAUNCH
			if(!$this->foreground && is_array($data)) {
				$datas[$current] = $this->extract($data, $key);
				if(!$this->all && $this->founded) { return $datas; }
			}

		}

		# Send prepared datas
		return $datas;
	}
}