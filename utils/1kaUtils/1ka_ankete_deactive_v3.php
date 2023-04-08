<?php
/**
 *
 * Podatke neaktivnih anket, ki se ze nekaj casa niso spreminjale prenese iz _active tabel v arhivske tabele
 *
 * NOVA SKRIPTA ZA TABELE
 *
 *	srv_data_vrednost
 *	srv_data_text
 *	srv_data_grid
 *	srv_data_textgrid
 *	srv_data_checkgrid
 *	srv_user_grupa
 *	srv_tracking
 *
*/

include_once('../../function.php');

// Time limit skripte
set_time_limit(1800); 	// 30 minut


// ID ankete za arhiviranje (opcijsko)
$survey_id = (isset($_GET['ank_id'])) ? ' AND id = '.$_GET['ank_id'] : '';

// Zacetni ID ankete kjer zacnemo arhiviranje (opcijsko)
$start = (isset($_GET['start'])) ? ' AND id > '.$_GET['start'] : '';

// Limit stevila anket, ki jih naenkrat arhiviramo
$limit = (isset($_GET['limit'])) ? $_GET['limit'] : 200;


// Stevilka arhivskih anket kamor kopiramo (zaenkrat _archive2)
/*$archive_table = '0';
$archive_table_string = '_archive1';*/
$archive_table = '2';
$archive_table_string = '_archive2';


// Arhiviramo specificno anketo
if($survey_id != ''){
    $sql = sisplet_query("SELECT * FROM srv_anketa WHERE active='0' AND id > '0' AND db_table = '1' ".$survey_id."");
}
// Loop čez aktivne ankete, ali mlajše od 3 mesecev (od zadnje spremembe, ali da so takrat pretekle)
else{
    $sql = sisplet_query("SELECT * FROM srv_anketa
                            WHERE active='0' 
                                AND id > '0'
                                AND db_table = '1'
                                AND edit_time < NOW() - INTERVAL 3 MONTH
                                AND expire < NOW() - INTERVAL 3 MONTH
                                ".$survey_id."
                                ".$start."
                            LIMIT ".$limit."
    ");
}
if (!$sql) { echo mysqli_error($GLOBALS['connect_db']).' 50'; die(); }


echo 'Arhiviranje '.mysqli_num_rows($sql).' anket.<hr>';


// Loop po anketah
while ($row = mysqli_fetch_array($sql)) {
	
	$s = sisplet_query("START TRANSACTION");
	if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 60'; die(); }

	echo 'Anketa '.$row['id'].'<br />';
	
	
	// Arhiviranje tabele srv_user_grupa
	archive_srv_user_grupa($row['id']);
	
	// Arhiviranje tabele srv_data_vrednost
	archive_srv_data_vrednost($row['id']);
	
	// Arhiviranje tabele srv_data_grid
	archive_srv_data_grid($row['id']);
	
	// Arhiviranje tabele srv_data_text
	archive_srv_data_text($row['id']);
	
	// Arhiviranje tabele srv_data_textgrid
	archive_srv_data_textgrid($row['id']);
	
	// Arhiviranje tabele srv_data_checkgrid
	archive_srv_data_checkgrid($row['id']);
	
	// Arhiviranje tabele srv_tracking
	archive_srv_tracking($row['id']);
	
	
	// Popravimo anketo med arhivske (ni vec _active)
	$s = sisplet_query("UPDATE srv_anketa SET db_table = '".$archive_table."' WHERE id = '$row[id]'");
	if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 70'; die(); }
    
    
	//$s = sisplet_query("ROLLBACK");
	$s = sisplet_query("COMMIT");
	if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 80'; die(); }


	flush(); @ob_flush();
}



// ARHIVIRANJE srv_user_grupa
function archive_srv_user_grupa($ank_id){
    global $archive_table_string;

    // Vstavimo v arhivsko tabelo (srv_user_grupa_archive2)
    $sql1 = sisplet_query("INSERT INTO srv_user_grupa".$archive_table_string." (gru_id, usr_id, time_edit, preskocena) 
                            SELECT d2.gru_id, d2.usr_id, d2.time_edit, d2.preskocena
                                FROM srv_user_grupa_active d2, srv_grupa g
                                WHERE d2.gru_id = g.id
								    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 90'; die(); }

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_user_grupa_active d, srv_grupa g
                        WHERE d.gru_id = g.id
							AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 91'; die(); }
}


// ARHIVIRANJE srv_data_vrednost
function archive_srv_data_vrednost($ank_id){
    global $archive_table_string;
    
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

    // Vstavimo v arhivsko tabelo (srv_data_vrednost_archive2)
    $sql1 = sisplet_query("INSERT INTO srv_data_vrednost".$archive_table_string." (spr_id, vre_id, usr_id, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.loop_id
                                FROM srv_data_vrednost_active d2, srv_spremenljivka s, srv_grupa g
                                WHERE d2.spr_id = s.id
								    AND s.gru_id = g.id AND s.gru_id > '0'
								    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 100'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 101'; die(); }
}


// ARHIVIRANJE srv_data_text
function archive_srv_data_text($ank_id){
    global $archive_table_string;
    
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

	// Vstavimo v arhivsko tabelo (srv_data_text)
	$sql1 = sisplet_query("INSERT INTO srv_data_text".$archive_table_string." (spr_id, vre_id, text, text2, usr_id, loop_id) 
								SELECT d2.spr_id, d2.vre_id, d2.text, d2.text2, d2.usr_id, d2.loop_id
									FROM srv_data_text_active d2, srv_spremenljivka s, srv_grupa g
									WHERE d2.spr_id = s.id
									AND s.gru_id = g.id AND s.gru_id > '0'
									AND g.ank_id = '$ank_id'
								");
	if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 110'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

	// Pobrisemo iz aktivne tabele
	$s = sisplet_query("DELETE d
                        FROM srv_data_text_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 111'; die(); }
    

    // Se dodatno kopiranje za komentarje (kjer je spr_id=0)
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

    $sql1 = sisplet_query("INSERT INTO srv_data_text".$archive_table_string." (spr_id, vre_id, text, text2, usr_id, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.text, d2.text2, d2.usr_id, d2.loop_id
                                FROM srv_data_text_active d2, srv_spremenljivka s, srv_grupa g
                                WHERE d2.spr_id = '0'
                                    AND d2.vre_id = s.id AND d2.vre_id > '0'
                                    AND s.gru_id = g.id AND s.gru_id > '0'
                                    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 112'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_text_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = '0'
                            AND d.vre_id = s.id AND d.vre_id > '0'
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 113'; die(); }
}


// ARHIVIRANJE srv_data_grid
function archive_srv_data_grid($ank_id){
    global $archive_table_string;
    
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

    // Vstavimo v arhivsko tabelo (srv_data_grid)
    $sql1 = sisplet_query("INSERT INTO srv_data_grid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.loop_id
                                FROM srv_data_grid_active d2, srv_spremenljivka s, srv_grupa g
                                WHERE d2.spr_id = s.id
								    AND s.gru_id = g.id AND s.gru_id > '0'
								    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 210'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_grid_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 211'; die(); }


    // Se dodatno za kombinirane tabele
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

    $sql1 = sisplet_query("INSERT INTO srv_data_grid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.loop_id
                                FROM srv_data_grid_active d2, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                                WHERE d2.spr_id = m.spr_id AND m.parent=s.id
                                    AND s.gru_id = g.id AND s.gru_id > '0'
                                    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 212'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_grid_active d, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                        WHERE d.spr_id = m.spr_id AND m.parent=s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 213'; die(); }
}


// ARHIVIRANJE srv_data_textgrid
function archive_srv_data_textgrid($ank_id){
    global $archive_table_string;
    
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

	// Vstavimo v arhivsko tabelo (srv_data_textgrid)
	$sql1 = sisplet_query("INSERT INTO srv_data_textgrid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, text, loop_id) 
								SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.text, d2.loop_id
									FROM srv_data_textgrid_active d2, srv_spremenljivka s, srv_grupa g
									WHERE d2.spr_id = s.id
									AND s.gru_id = g.id AND s.gru_id > '0'
									AND g.ank_id = '$ank_id'
	");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 310'; die(); }
    
    sisplet_query("SET FOREIGN_KEY_CHECKS=1");
    
    // Pobrisemo iz aktivne tabele
	$s = sisplet_query("DELETE d
                        FROM srv_data_textgrid_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 311'; die(); }
    

    // Se dodatno za kombinirane tabele
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");

	$sql1 = sisplet_query("INSERT INTO srv_data_textgrid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, text, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.text, d2.loop_id
                                FROM srv_data_textgrid_active d2, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                                WHERE d2.spr_id = m.spr_id AND m.parent=s.id
                                    AND s.gru_id = g.id AND s.gru_id > '0'
                                    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 312'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");
    
    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_textgrid_active d, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                        WHERE d.spr_id = m.spr_id AND m.parent=s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
	");
	if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 313'; die(); }
}


// ARHIVIRANJE srv_data_checkgrid
function archive_srv_data_checkgrid($ank_id){
    global $archive_table_string;
    
     // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
     sisplet_query("SET FOREIGN_KEY_CHECKS=0");

    // Vstavimo v arhivsko tabelo (srv_data_checkgrid)
	$sql1 = sisplet_query("INSERT INTO srv_data_checkgrid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, loop_id)
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.loop_id
                                FROM srv_data_checkgrid_active d2, srv_spremenljivka s, srv_grupa g
                                WHERE d2.spr_id = s.id
                                    AND s.gru_id = g.id AND s.gru_id > '0'
                                    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 410'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_checkgrid_active d, srv_spremenljivka s, srv_grupa g
                        WHERE d.spr_id = s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 411'; die(); }
    

    // Se dodatno za kombinirane tabele
    // Samo za kopiranje ugasnemo foreign key check, ker obstajajo primeri, kjer usr_id ne obstaja
    sisplet_query("SET FOREIGN_KEY_CHECKS=0");
	$sql1 = sisplet_query("INSERT INTO srv_data_checkgrid".$archive_table_string." (spr_id, vre_id, usr_id, grd_id, loop_id) 
                            SELECT d2.spr_id, d2.vre_id, d2.usr_id, d2.grd_id, d2.loop_id
                                FROM srv_data_checkgrid_active d2, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                                WHERE d2.spr_id = m.spr_id AND m.parent=s.id
                                    AND s.gru_id = g.id AND s.gru_id > '0'
                                    AND g.ank_id = '$ank_id'
    ");
    if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 412'; die(); }

    sisplet_query("SET FOREIGN_KEY_CHECKS=1");

    // Pobrisemo iz aktivne tabele
    $s = sisplet_query("DELETE d
                        FROM srv_data_checkgrid_active d, srv_spremenljivka s, srv_grupa g, srv_grid_multiple m
                        WHERE d.spr_id = m.spr_id AND m.parent=s.id
                            AND s.gru_id = g.id AND s.gru_id > '0'
                            AND g.ank_id = '$ank_id'
    ");
    if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 413'; die(); }
}


// ARHIVIRANJE srv_tracking
function archive_srv_tracking($ank_id){
	global $archive_table_string;
    
	// Vstavimo v arhivsko tabelo (srv_tracking)
	$sql1 = sisplet_query("INSERT INTO srv_tracking".$archive_table_string." (`ank_id`, `datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) 
                            SELECT d2.ank_id, d2.datetime, d2.ip, d2.user, d2.get, d2.post, d2.status, d2.time_seconds
                                FROM srv_tracking_active d2
                                WHERE d2.ank_id = '$ank_id'
	");
	if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']).' 510'; die(); }
	
	// Pobrisemo iz aktivne tabele (srv_tracking_active)
	$s = sisplet_query("DELETE FROM srv_tracking_active WHERE ank_id = '$ank_id'");
	if (!$s) { echo mysqli_error($GLOBALS['connect_db']).' 511'; die(); }
}


?>