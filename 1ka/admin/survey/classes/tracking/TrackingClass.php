<?php

/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 02.02.2017
 *****************************************/
class TrackingClass
{
    private $sub;
    private $anketa;
    private $status = '';
	
	private $db_table = "";

    public function __construct($anketa = null)
    {
        if ((isset ($_REQUEST['anketa']) && $_REQUEST['anketa'] > 0) || (isset ($anketa) && $anketa > 0)) {
            $this->anketa = (isset ($_REQUEST['anketa']) && $_REQUEST['anketa'] > 0) ? $_REQUEST['anketa'] : $anketa;
        } else {
            return 'Anketa ID ne obstaja';
        }
		
		# poiščemo aktivno anketo
		SurveyInfo :: getInstance()->SurveyInit($this->anketa);
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';

        if ($_GET['m'] == 'tracking_data')
            $this->sub = 'data';
        elseif ($_GET['appendMerge'] == '1')
            $this->sub = 'append';
        else
            $this->sub = 'survey';

        // Filter po statusu
        if (isset($_GET['status']) && in_array($_GET['status'], array('0', '1', '2', '3', '4', '5', '6')))
            $this->status = " AND status = '{$_GET[status]}' ";


        return $this;
    }

    private static $_instance;

    /**
     * V kolikor razred kličemo statično
     *
     * @return instance
     */

    public static function init()
    {
        if (!static::$_instance)
            static::$_instance = new TrackingClass();

        return static::$_instance;
    }

    /**
     * Filter po statusih
     * Filter omogoča, da se prvi parameter ne upošteva statusa, v koliko ni filtra upošteva vse statuse.
     * Drugi parameter naredi inverzno operacijo - išče samo po tem statusu
     *    0 => urejanje
     *    1 => uvoz podatkov
     *    2 => analiza
     *    3 => reporti
     *    4 => podatki
     *    5 => objava - vabila
     *    6 => hierarhija
     *    20 => hierarhija - splošno
     *    21 => hierarhija - gradnja strukture
     *    22 => hierarhija - uporabniki
     *
     * @param (int or array) $exclude_status
     * @param (boolean) $invert_status
     * @return $this
     */
    public function filter($exclude_status = null, $invert_status = false)
    {
        $opcija = '!';
        if ($invert_status)
            $opcija = '';


        if (!is_null($exclude_status) && is_int($exclude_status)) {
            $this->status = " AND status " . $opcija . "= '" . $exclude_status . "' ";
        } elseif (!is_null($exclude_status) && is_array($exclude_status)) {

            if (!empty($opcija)) {
                $this->status = " AND status NOT IN (" . implode(',', $exclude_status) . ")";
            } else {
                $this->status = " AND status IN (" . implode(',', $exclude_status) . ")";
            }


        }

        return $this;
    }


    /**
     * @desc prikaze tracking sprememb
     * status:
     *   -1 => unknown
     *    0 => urejanje
     *    1 => uvoz podatkov
     *    2 => analiza
     *    3 => reporti
     *    4 => podatki
     *    5 => objava - vabila
     *    20 => hierarhija - splošno
     *    21 => hierarhija - gradnja strukture
     *    22 => hierarhija - uporabniki
     */
    public function trackingDisplay()
    {
        global $lang;

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_survey_archives_tracking_' . $this->sub] . '</legend>';

        // Izvoz v Excel
        echo '<p><a href="index.php?anketa=' . $this->anketa . '&a=' . $_GET['a'] . '&d=download">Download Excel</a></p>';


        echo '<table id="tracking">';

        // Tabela s podatki o spremembah podatkov
        if ($this->sub == 'data') {

            // Filter po podatkih
            $data = ' AND (`get` LIKE \'%edit_data%\' 
							OR (`get` LIKE \'%a: "data", m: "quick_edit"%\' AND `get` LIKE \'%post: "1"%\')
							OR (`get` LIKE \'%a: "dataCopyRow"%\')
							OR (`get` LIKE \'%a: "dataDeleteMultipleRow"%\')
							OR (`get` LIKE \'%a: "dataDeleteRow"%\')
							OR (`get` LIKE \'%urejanje: "1"%\' AND status=\'4\')
						)';

            // Prva vrstica
            echo '<tr>';
            echo '	<th>' . $lang['date'] . '</th>';
            echo '	<th>User</th>';
            echo '	<th class="center">IP</th>';
            echo '	<th class="center">Recnum</th>';
            echo '	<th>GET</th>';
            echo '	<th>POST</th>';
            echo '</tr>';

            // Vrstice s podatki
            $sql = sisplet_query("SELECT * FROM srv_tracking".$this->db_table." WHERE ank_id = '$this->anketa' " . $appendMerge . " " . $data . " ORDER BY datetime DESC");
            while ($row = mysqli_fetch_array($sql)) {
                echo '<tr>';

                $sqlu = sisplet_query("SELECT name, surname FROM users WHERE id = '$row[user]'");
                $rowu = mysqli_fetch_array($sqlu);

                // Pri podatkih dobimo posebej podatke o editiranem respondentu
                $usr_id = '';
                $cookie = '';

                // Preverimo ce imamo usr_id v GET-u
                $get_array_temp = explode(', ', $row['get']);
                foreach ($get_array_temp AS $get_val) {
                    $param = explode(': ', $get_val);
                    $get_array[$param[0]] = $param[1];
                }

                if (isset($get_array['usr_id']) && $get_array['usr_id'] != '') {
                    $usr_id = trim($get_array['usr_id'], '"');
                } // Preverimo ce iammo slucajno cookie
                elseif (isset($get_array['survey-' . $this->anketa]) && $get_array['survey-' . $this->anketa] != '') {
                    $cookie = trim($get_array['survey-' . $this->anketa], '"');
                } else {
                    // Preverimo ce imamo usr_id v POST-u
                    $post_array_temp = explode(', ', $row['post']);
                    foreach ($post_array_temp AS $post_val) {
                        $param = explode(': ', $post_val);
                        $post_array[$param[0]] = $param[1];
                    }

                    if (isset($post_array['usr_id']) && $post_array['usr_id'] != '')
                        $usr_id = trim($post_array['usr_id'], '"');
                }

                if ($usr_id != '') {
                    $sqlR = sisplet_query("SELECT recnum FROM srv_user WHERE id = '$usr_id'");
                    $rowR = mysqli_fetch_array($sqlR);

                    $recnum = $rowR['recnum'];
                } elseif ($cookie != '') {
                    $sqlR = sisplet_query("SELECT recnum FROM srv_user WHERE cookie = '$cookie'");
                    $rowR = mysqli_fetch_array($sqlR);

                    $recnum = $rowR['recnum'];
                } else
                    $recnum = 0;

                echo '	<td>' . datetime($row['datetime']) . '</td>';
                echo '	<td>' . $rowu['name'] . ' ' . $rowu['surname'] . '</td>';
                echo '	<td class="center">' . $row['ip'] . '</td>';
                echo '	<td class="center">' . $recnum . '</td>';
                echo '	<td>' . $row['get'] . '</td>';
                echo '	<td>' . $row['post'] . '</td>';

                echo '</tr>';
            }
        } // Tabela s podatki o spremembah - vse oz. merge/append
        else {

            // Legenda statusov
            $statuses = array(
                -1 => $lang['srv_unknown'],
                0 => $lang['srv_urejanje'],
                1 => $lang['import_data'],
                2 => $lang['export_analisys'],
                3 => $lang['srv_reporti'],
                4 => $lang['srv_podatki'],
                5 => $lang['srv_inv_nav_email'],
                20 => $lang['srv_hierarchy'], // Splošni podatki o hierarhiji
                21 => $lang['srv_hierarchy_structure'], // Grajenje hierarhije
                22 => $lang['srv_hierarchy_users'], // Urejanje uporabnikov
            );


            // Filter za uvoze
            if ($this->sub == 'append')
                $appendMerge = " AND (`get` LIKE '%appendMerge%' OR status='1') ";
            else
                $appendMerge = "";

            // Prva vrstica
            echo '<tr>';
            echo '	<th>' . $lang['date'] . '</th>';
            echo '	<th>User</th>';
            echo '	<th class="center">IP</th>';
            echo '	<th class="center">Podstran</th>';
            echo '	<th>GET</th>';
            echo '	<th>POST</th>';
            echo '</tr>';

            // Vrstice s podatki
            $sql = sisplet_query("SELECT * FROM srv_tracking".$this->db_table." WHERE ank_id = '$this->anketa' " . $this->status . " " . $appendMerge . " " . $data . " ORDER BY datetime DESC");
            while ($row = mysqli_fetch_array($sql)) {
                echo '<tr>';

                $sqlu = sisplet_query("SELECT name, surname FROM users WHERE id = '$row[user]'");
                $rowu = mysqli_fetch_array($sqlu);

                echo '	<td>' . datetime($row['datetime']) . '</td>';
                echo '	<td>' . $rowu['name'] . ' ' . $rowu['surname'] . '</td>';
                echo '	<td class="center">' . $row['ip'] . '</td>';
                echo '	<td class="center">' . $statuses[$row['status']] . '</td>';
                echo '	<td>' . $row['get'] . '</td>';
                echo '	<td>' . $row['post'] . '</td>';

                echo '</tr>';
            }
        }

        echo '</table>';

        echo '</fieldset>';
    }

    public function csvExport()
    {
        define('delimiter', ';');


        $podatki = 'datetime' . delimiter;
        $podatki .= 'uid' . delimiter;
        $podatki .= 'ip' . delimiter;
        $podatki .= 'status' . delimiter;
        $podatki .= 'parameter' . delimiter;
        $podatki .= 'value' . delimiter;
        $podatki .= 'parameter' . delimiter;
        $podatki .= 'value' . delimiter;


        $podatki .= "\n";

        $sql = sisplet_query("SELECT * FROM srv_tracking".$this->db_table." WHERE ank_id = '" . $this->anketa . "' " . $this->status . " ORDER BY datetime DESC");
        while ($row = mysqli_fetch_array($sql)) {


            $sqlu = sisplet_query("SELECT name, surname, id FROM users WHERE id = '$row[user]'");
            $rowu = mysqli_fetch_array($sqlu);

            $podatki .= '' . datetime($row['datetime']) . delimiter;
            $podatki .= '' . $rowu['id'] . delimiter;
            $podatki .= '' . $row['ip'] . delimiter;
            $podatki .= '' . $row['status'] . delimiter;

            foreach (explode(',', $row['get']) AS $value) {
                $value = explode(':', $value);
                $podatki .= trim($value[0]) . delimiter;
                $podatki .= trim($value[1]) . delimiter;
            }

            $podatki .= "\n";
        }

        $ime = str_replace('-', '_', $_GET['a']);
        return Export::init()->csv('Spremembe_' . $ime, $podatki);
    }

    /**
     * Update srv_tracking table
     * @desc vnese spremembo v srv_tracking za sledenje sprememb
     *
     * @param (int) $anketa
     * @param (int) $status
     */
    private static $time_start;

    static function update($anketa, $status = 0)
    {
        global $global_user_id;

		# poiščemo aktivno anketo
		SurveyInfo :: getInstance()->SurveyInit($anketa);
		$db_table = (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) ? '_active' : '';
		
        $get = '';
        foreach ($_GET AS $key => $val) {
            if ($get != '')
                $get .= ', ';
            $get .= $key . ': "' . $val . '"';
        }

        $post = '';
        foreach ($_POST AS $key => $val) {
            if ($post != '')
                $post .= ', ';

//            if (is_array($val))
//                $val = implode(',', $val);

            if (is_array($val))
                $val = self::arrayToString($val);

            $post .= $key . ': "' . $val . '"';
        }

        // izracunamo trajanje skripte v sekundah
        if (self::$time_start != null)
            $time_seconds = microtime(true) - self::$time_start;
        else
            $time_seconds = 0;

        // IP uporabnika
        $ip = GetIP();

        $s = sisplet_query("INSERT INTO srv_tracking".$db_table." (ank_id, datetime, ip, user, `get`, post, status, time_seconds) VALUES ('$anketa', NOW(), '".$ip."', '$global_user_id', '$get', '$post','$status', '$time_seconds')");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
    }
    
    static function update_user($status = 0)
    {
        global $global_user_id;
		
        $get = '';
        foreach ($_GET AS $key => $val) {
            if ($get != '')
                $get .= ', ';
            $get .= $key . ': "' . $val . '"';
        }

        $post = '';
        foreach ($_POST AS $key => $val) {
            if ($post != '')
                $post .= ', ';

//            if (is_array($val))
//                $val = implode(',', $val);

            if (is_array($val))
                $val = self::arrayToString($val);

            $post .= $key . ': "' . $val . '"';
        }

        // izracunamo trajanje skripte v sekundah
        if (self::$time_start != null)
            $time_seconds = microtime(true) - self::$time_start;
        else
            $time_seconds = 0;

        // IP uporabnika
        $ip = GetIP();

        $s = sisplet_query("INSERT INTO user_tracking (datetime, ip, user, `get`, post, status, time_seconds) VALUES (NOW(), '".$ip."', '$global_user_id', '$get', '$post','$status', '$time_seconds')");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
    }

    private static function arrayToString($array)
    {
        $string = "";
        if (is_array($array)) {
            foreach ($array as $key => $value) {

                if (is_array($value)) {
                    $string .= ', '.$key .': [' . self::arrayToString($value) . '] ';
                } else {
                    if ($key == 0)
                        $string .= $key . ': ' . $value . ' ';
                }


            }
        }

        return $string;
    }


}