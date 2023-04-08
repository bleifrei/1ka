<?php

/**
*
* CSV izvoz vseh anket 1ke, stevila responsov po letih
*
*/


ini_set('max_execution_time', 3600);		// 60 minutes


include_once('../../function.php');


$current_year = date("Y");
$prev_year = (int)$current_year - 1;

$data = array();
$data[] = array(
    'ID', 
    'Naslov', 
    'Aktivna', 
    'Avtor ID', 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov'), 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov '.$current_year), 
    iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov '.$prev_year)
);


// Vse ankete 1ke
$sql = sisplet_query("SELECT id, naslov, active, insert_uid FROM srv_anketa WHERE id>'0' ORDER BY id ASC");
if (!$sql) {
    echo mysqli_error($GLOBALS['connect_db']); 
    die();
}

// Loop cez ankete
while ($row = mysqli_fetch_array($sql)) {

    // Stevilo odgovorov ankete
    $response_count = 0;
    $response_count_current_year = 0;
    $response_count_prev_year = 0;

    // Prestejemo stevilo anket
    $sqlR = sisplet_query("SELECT count(id) AS response_count, YEAR(time_insert) AS year
                            FROM srv_user
                            WHERE ank_id='".$row['id']."' 
                                AND preview='0' AND testdata='0' AND deleted='0' AND lurker='0' AND (last_status='5' OR last_status='6')
                            GROUP BY YEAR(time_insert)
                        ");
    if (!$sqlR) {
        echo mysqli_error($GLOBALS['connect_db']); 
        die();
    }

    // Loop cez prestete ankete po letih
    while ($rowR = mysqli_fetch_array($sqlR)) {

        $response_count += (int)$rowR['response_count'];

        if($rowR['year'] == $current_year)
            $response_count_current_year = (int)$rowR['response_count'];

        if($rowR['year'] == $prev_year)
            $response_count_prev_year = (int)$rowR['response_count'];
    }


    ob_start();

	$line = array();

	$line[0] = $row['id'];
	$line[1] = iconv("UTF-8","Windows-1250//TRANSLIT", $row['naslov']);
	$line[2] = $row['active'];
	$line[3] = $row['insert_uid'];

	$line[4] = $response_count;
	$line[5] = $response_count_current_year;
	$line[6] = $response_count_prev_year;

	$data[] = $line;

	/*echo implode($line, ';');
	echo '<br />';*/

	ob_end_flush();
}


header('Content-Type: application/excel;');
header('Content-Disposition: attachment; filename="survey_statistics.csv"');

$fp = fopen('php://output', 'w');
foreach($data as $line) {
        //fputcsv($fp, $line, ',');
        fputcsv($fp, $line, ';');
}
fclose($fp);


