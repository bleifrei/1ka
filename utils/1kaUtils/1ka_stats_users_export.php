<?php

/**
*
* CSV izvoz vseh uporabnikov 1ke, stevila anket po letih
*
*/


ini_set('max_execution_time', 3600);		// 60 minutes


include_once('../../function.php');


$current_year = date("Y");
$prev_year = (int)$current_year - 1;

$data = array();
$data[] = array(
    'ID', 
    'Email', 
    'Ime', 
    'Priimek', 
    'Tip', 
    'Status', 
    'Potrjen', 
    'GDPR', 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket'), 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket '.$current_year), 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket '.$prev_year)
);


// Vsi userji 1ke
$sql = sisplet_query("SELECT id, email, name, surname, type, status, approved, gdpr_agree FROM users ORDER BY id ASC");
if (!$sql) {
    echo mysqli_error($GLOBALS['connect_db']); 
    die();
}

// Loop cez userje
while ($row = mysqli_fetch_array($sql)) {

    // Stevilo anket uporabnika
    $survey_count = 0;
    $survey_count_current_year = 0;
    $survey_count_prev_year = 0;

    // Prestejemo stevilo anket
    $sqlA = sisplet_query("SELECT count(id) AS survey_count, YEAR(insert_time) AS year
                            FROM srv_anketa 
                            WHERE insert_uid='".$row['id']."'
                            GROUP BY YEAR(insert_time)
                        ");
    if (!$sqlA) {
        echo mysqli_error($GLOBALS['connect_db']); 
        die();
    }

    // Loop cez prestete ankete po letih
    while ($rowA = mysqli_fetch_array($sqlA)) {

        $survey_count += (int)$rowA['survey_count'];

        if($rowA['year'] == $current_year)
            $survey_count_current_year = (int)$rowA['survey_count'];

        if($rowA['year'] == $prev_year)
            $survey_count_prev_year = (int)$rowA['survey_count'];
    }


    ob_start();

	$line = array();

	$line[0] = $row['id'];
	$line[1] = $row['email'];
	$line[2] = iconv("UTF-8","Windows-1250//TRANSLIT", $row['name']);
	$line[3] = iconv("UTF-8","Windows-1250//TRANSLIT", $row['surname']);

    if($row['type'] == '0')
        $line[4] = 'Administrator';
    elseif($row['type'] == '1')
        $line[4] = 'Manager';
    else
        $line[4] = 'Uporabnik';

	$line[5] = $row['status'];
	$line[6] = $row['approved'];
	$line[7] = $row['gdpr_agree'];

	$line[8] = $survey_count;
	$line[9] = $survey_count_current_year;
	$line[10] = $survey_count_prev_year;

	$data[] = $line;

	/*echo implode($line, ';');
	echo '<br />';*/

	ob_end_flush();
}


header('Content-Type: application/excel');
header('Content-Disposition: attachment; filename="user_statistics.csv"');

$fp = fopen('php://output', 'w');
foreach($data as $line) {
        //fputcsv($fp, $line, ',');
        fputcsv($fp, $line, ';');
}
fclose($fp);

