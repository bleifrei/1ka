<?php

include_once('../../settings.php');
include_once('../../function.php');
//include_once('class.xls.php');
include_once('../../vendor/autoload.php');

class xls {

	private $xls_string = '';
	private $row = 0;
	private $col = 0;
	private $filename = '';

	function __construct($filename = false) {
		if ($filename) {
			$this->filename = $this->check_extension($filename);
		} else {
			$this->filename = 'XLS_download_' . date('dmY') . '.xls';
		}
	}


	function display($filename, $output) {

		header('Content-type: application/vnd.ms-excel; charset=windows-1250');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		
		echo $output;

		exit;
	}
}

?>