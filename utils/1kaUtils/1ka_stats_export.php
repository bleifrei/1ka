<?php

/**
*
* CSV izvoz vseh uporabnikov 1ke, stevila anket po letih, stevila responsov po letih (www, virtualke in AAI)
* 
* inkrementalno dodajanje v datoteko, ker gre prepocasi na www
*
*/


ini_set('max_execution_time', 3600);		// 60 minutes


include_once('../../function.php');


// Koliko userjev naenkrat dodajamo v csv (zaradi hitrosti)
$limit = 1000;


// Leta za katera stejemo ankete, response
$current_year = date("Y");
$prev_year1 = (int)$current_year - 1;
$prev_year2 = (int)$current_year - 2;


// Zadnji user dodan v datoteko (dodajamo inkrementalno)
$last_user_id = 0;

// Odpremo csv ce ze obstaja in preberemo zadnji vnos da nadaljujemo kjer smo ostali
if (($handle = fopen('temp/user_survey_statistics.csv', 'a+')) !== FALSE) {
    
    while (($csv_row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $last_user_id = $csv_row[0];
    }
}
else{
    echo 'Error creating CSV file!';
    die();
}


// CSV je prazen - dodamo naslovno vrstico
if($last_user_id == 0){

    $line = array(
        'ID', 
        'Email', 
        'Ime', 
        'Priimek', 

        'Tip', 
        'Status', 
        'Potrjen', 
        'Jezik', 
        'GDPR', 

        'Datum registracije', 
        'Zadnja prijava', 

        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket '.$current_year), 
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket '.$prev_year1),
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket '.$prev_year2),
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. anket pred '.$prev_year2), 

        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov '.$current_year), 
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov '.$prev_year1),
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov '.$prev_year2),
        iconv("UTF-8","Windows-1250//TRANSLIT", 'Št. odgovorov pred '.$prev_year2)
    );

    // Dodamo naslovno vrstico v CSV datoteko
    fputcsv($handle, $line, ';');
}


// Loop cez userje 1ke
$sql = sisplet_query("SELECT id, email, name, surname, type, status, approved, lang, gdpr_agree, when_reg, last_login 
                        FROM users 
                        WHERE id>'".$last_user_id."'
                        ORDER BY id ASC
                        LIMIT ".$limit."
                    ");
if (!$sql) {
    echo mysqli_error($GLOBALS['connect_db']); 
    die();
}

while ($row = mysqli_fetch_array($sql)) {

    // Stevilo anket uporabnika
    $survey_count_current_year = 0;
    $survey_count_prev_year1 = 0;
    $survey_count_prev_year2 = 0;
    $survey_count_prev_years = 0;

    // Stevilo odgovorov ankete
    $response_count_current_year = 0;
    $response_count_prev_year1 = 0;
    $response_count_prev_year2 = 0;
    $response_count_prev_years = 0;

    // Loop cez vse ankete uporabnika
    $sqlA = sisplet_query("SELECT id, YEAR(insert_time) AS insert_year
                            FROM srv_anketa 
                            WHERE insert_uid='".$row['id']."'
                            ORDER BY insert_time ASC
                        ");
    if (!$sqlA) {
        echo mysqli_error($GLOBALS['connect_db']); 
        die();
    }

    while ($rowA = mysqli_fetch_array($sqlA)) {

        if($rowA['insert_year'] == $current_year)
            $survey_count_current_year++;

        if($rowA['insert_year'] == $prev_year1)
            $survey_count_prev_year1++;

        if($rowA['insert_year'] == $prev_year2)
            $survey_count_prev_year2++;
    
        if($rowA['insert_year'] < $prev_year2)
            $survey_count_prev_years++;


        // Prestejemo stevilo responsov
        $sqlR = sisplet_query("SELECT count(id) AS response_count, YEAR(time_insert) AS year
                                FROM srv_user
                                WHERE ank_id='".$rowA['id']."' 
                                    AND preview='0' AND testdata='0' AND deleted='0' AND lurker='0' AND (last_status='5' OR last_status='6')
                                GROUP BY YEAR(time_insert)
                            ");
        if (!$sqlR) {
            echo mysqli_error($GLOBALS['connect_db']); 
            die();
        }

        // Loop cez prestete response po letih
        while ($rowR = mysqli_fetch_array($sqlR)) {

            if($rowR['year'] == $current_year)
                $response_count_current_year += (int)$rowR['response_count'];

            if($rowR['year'] == $prev_year1)
                $response_count_prev_year1 += (int)$rowR['response_count'];

            if($rowR['year'] == $prev_year2)
                $response_count_prev_year2 += (int)$rowR['response_count'];
        
            if($rowR['year'] < $prev_year2)
                $response_count_prev_years += (int)$rowR['response_count'];
        }
    }


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
    $line[7] = $row['lang'];
	$line[8] = $row['gdpr_agree'];

	$line[9] = $row['when_reg'];
	$line[10] = $row['last_login'];

	$line[11] = $survey_count_current_year;
	$line[12] = $survey_count_prev_year1;
	$line[13] = $survey_count_prev_year2;
	$line[14] = $survey_count_prev_years;

	$line[15] = $response_count_current_year;
	$line[16] = $response_count_prev_year1;
	$line[17] = $response_count_prev_year2;
	$line[18] = $response_count_prev_years;


    // Dodamo vrstico s podatki v CSV datoteko
    fputcsv($handle, $line, ';');

    echo 'Dodan uporabnik '.$row['id'].'<br />';
}


fclose($handle);

