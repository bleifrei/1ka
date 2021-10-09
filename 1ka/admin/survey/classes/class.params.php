<?php
/**
 * Created on 16.11.2008
 *
 * @author: Gorazd Veselic
 */
class enkaParameters
{
	/** @var object */
	var $_params = null;
	/**
	* Constructor
	* @param string The raw parms text
	*/
	function __construct($text)
	{
		$this->_params = $this->parse($text);
	}
	/**
	* Parsa tekstovni string
	* @param mixed The ini string or array of lines
	* @param boolean add an associative index for each section [in brackets]
	* @return object
	*/
	function parse($txt, $process_sections = false, $asArray = false)
	{
		if (is_string($txt))
		{
			$lines = explode("\n", $txt);
		}
		else
			if (is_array($txt))
			{
				$lines = $txt;
			}
			else
			{
				$lines = array ();
			}
		$obj = $asArray ? array () : new stdClass();
		$sec_name = '';
		$unparsed = 0;
		if (!$lines)
		{
			return $obj;
		}
		foreach ($lines as $line)
		{
			// ignore comments
			if ($line && $line[0] == ';')
			{
				continue;
			}
			$line = trim($line);
			if ($line == '')
			{
				continue;
			}
			if ($line && $line[0] == '[' && $line[strlen($line) - 1] == ']')
			{
				$sec_name = substr($line, 1, strlen($line) - 2);
				if ($process_sections)
				{
					if ($asArray)
					{
						$obj[$sec_name] = array ();
					}
					else
					{
						$obj-> $sec_name = new stdClass();
					}
				}
			}
			else
			{
				if ($pos = strpos($line, '='))
				{
					$property = trim(substr($line, 0, $pos));
					if (substr($property, 0, 1) == '"' && substr($property, -1) == '"')
					{
						$property = stripcslashes(substr($property, 1, count($property) - 2));
					}
					$value = trim(substr($line, $pos +1));
					if ($value == 'false')
					{
						$value = false;
					}
					if ($value == 'true')
					{
						$value = true;
					}
					if (substr($value, 0, 1) == '"' && substr($value, -1) == '"')
					{
						$value = stripcslashes(substr($value, 1, count($value) - 2));
					}
					if ($process_sections)
					{
						$value = str_replace('\n', "\n", $value);
						if ($sec_name != '')
						{
							if ($asArray)
							{
								$obj[$sec_name][$property] = $value;
							}
							else
							{
								$obj-> $sec_name-> $property = $value;
							}
						}
						else
						{
							if ($asArray)
							{
								$obj[$property] = $value;
							}
							else
							{
								$obj-> $property = $value;
							}
						}
					}
					else
					{
						$value = str_replace('\n', "\n", $value);
						if ($asArray)
						{
							$obj[$property] = $value;
						}
						else
						{
							$obj-> $property = $value;
						}
					}
				}
				else
				{
					if ($line && trim($line[0]) == ';')
					{
						continue;
					}
					if ($process_sections)
					{
						$property = '__invalid' . $unparsed++ . '__';
						if ($process_sections)
						{
							if ($sec_name != '')
							{
								if ($asArray)
								{
									$obj[$sec_name][$property] = trim($line);
								}
								else
								{
									$obj-> $sec_name-> $property = trim($line);
								}
							}
							else
							{
								if ($asArray)
								{
									$obj[$property] = trim($line);
								}
								else
								{
									$obj-> $property = trim($line);
								}
							}
						}
						else
						{
							if ($asArray)
							{
								$obj[$property] = trim($line);
							}
							else
							{
								$obj-> $property = trim($line);
							}
						}
					}
				}
			}
		}
		return $obj;
	}
	/**
	* Vrne params array
	* @return object
	*/
	function toObject()
	{
		return $this->_params;
	}
	/**
	 * Vrne array parametrov
	 * @return object
	 */
	function toArray()
	{
		return enkaObjectToArray($this->_params);
	}
	/** Doda ali ponastavi posamezen parameter
	* @param string The name of the param
	* @param string The value of the parameter
	* @return string The set value
	*/
	function set($key, $value = '')
	{
		$this->_params-> $key = $value;
		return $value;
	}
	/**
	* Nastavi privzeto vrednost če le ta ni določena
	* @param string The name of the param
	* @param string The value of the parameter
	* @return string The set value
	*/
	function def($key, $value = '')
	{
		return $this->set($key, $this->get($key, $value));
	}
	/** Vrne vrednost posameznega parametra
	* @param string The name of the param
	* @param mixed The default value if not found
	* @return string
	*/
	function get($key, $default = '')
	{
		if (isset ($this->_params-> $key))
		{
			return $this->_params-> $key === '' ? $default : $this->_params-> $key;
		}
		else
		{
			return $default;
		}
	}
	/** Vrne parametre v tekstovni obliki primeni za hrambo v sql tabeli
	 *  @return string
	 */
	function getString()
	{
		$txt = array ();
		foreach (get_object_vars($this->_params) as $k => $v)
		{
			$txt[] = "$k=$v";
		}
		$saveparams = implode("\n", $txt);
		return $saveparams;
	}
}
function enkaObjectToArray($p_obj)
{
	$retarray = null;
	if (is_object($p_obj))
	{
		$retarray = array ();
		foreach (get_object_vars($p_obj) as $k => $v)
		{
			if (is_object($v))
				$retarray[$k] = enkaObjectToArray($v);
			else
				$retarray[$k] = $v;
		}
	}
	return $retarray;
}
?>