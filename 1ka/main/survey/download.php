<?php

include ('../../function.php');

$code = $_GET['code'];
$anketa = $_GET['anketa'];

$sql = sisplet_query("SELECT filename FROM srv_data_upload WHERE ank_id='$anketa' AND code='$code'");
if (mysqli_num_rows($sql) == 0) die();
$row = mysqli_fetch_array($sql);

$filename = $row['filename'];

$mimetype = 'mime/type';

header('Content-Type: '.$mimetype );
header('Content-Disposition: attachment; filename="'.$filename.'"');

readfile_chunked('uploads/'.$filename);


  // Read a file and display its content chunk by chunk
  function readfile_chunked($filename, $retbytes = TRUE) {
    $buffer = '';
    $cnt =0;
    // $handle = fopen($filename, 'rb');
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
      return false;
    }
    while (!feof($handle)) {
      $buffer = fread($handle, 1024*1024);
      echo $buffer;
      ob_flush();
      flush();
      if ($retbytes) {
        $cnt += strlen($buffer);
      }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
      return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
  }


?>