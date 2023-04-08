<?php

/***************************************
 * Description:
 * Autor: Uroš Podkrižnik
 * Created date: 09.04.2020
 *****************************************/
class UserTrackingClass
{	

    public function __construct()
    {
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
            static::$_instance = new UserTrackingClass();

        return static::$_instance;
    }

    /**
     * @desc prikaze tracking sprememb
     */
    public function userTrackingDisplay()
    {
        global $lang;
        global $global_user_id;
        
        echo '<div id="div_archive_content" class="tracking">';

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_survey_archives_tracking'] . '</legend>';

        // Izvoz v Excel
        echo '<p>'.$lang['srv_survey_archives_tracking_last_changes'].'</p>';

        echo '<div id="table-tracking-wrapper1">';
        echo '<div id="table-tracking-wrapper2">';


        echo '<table id="tracking">';

        // Prva vrstica
        echo '<tr>';
        echo '	<th>' . $lang['date'] . '</th>';
        echo '	<th class="center">IP</th>';
        echo '	<th>GET</th>';
        echo '	<th>POST</th>';
        echo '</tr>';

        // Vrstice s podatki
        $sql = sisplet_query("SELECT * FROM user_tracking WHERE user = '$global_user_id' ORDER BY datetime DESC LIMIT 25");
        
        while ($row = mysqli_fetch_array($sql)) {
            echo '<tr>';

            echo '	<td>' . datetime($row['datetime']) . '</td>';
            echo '	<td class="center">' . $row['ip'] . '</td>';
            echo '	<td>' . $row['get'] . '</td>';
            echo '	<td>' . $row['post'] . '</td>';

            echo '</tr>';
        }

        echo '</table>';

        echo '</div>';
        echo '</div>';

        echo '</fieldset>';
        
        echo '<br class="clr" />';
        echo '</div>';         
    }

    public function csvExport()
    {
        global $global_user_id;
        
        define('delimiter', ';');


        $podatki = 'datetime' . delimiter;
        //$podatki .= 'uid' . delimiter;
        $podatki .= 'ip' . delimiter;
        //$podatki .= 'status' . delimiter;
        $podatki .= 'parameter' . delimiter;
        $podatki .= 'value' . delimiter;
        $podatki .= 'parameter' . delimiter;
        $podatki .= 'value' . delimiter;


        $podatki .= "\n";

        $sql = sisplet_query("SELECT * FROM user_tracking WHERE user = '$global_user_id' ORDER BY datetime DESC");
        while ($row = mysqli_fetch_array($sql)) {


            $sqlu = sisplet_query("SELECT name, surname, id FROM users WHERE id = '$row[user]'");
            $rowu = mysqli_fetch_array($sqlu);

            $podatki .= '' . datetime($row['datetime']) . delimiter;
            //$podatki .= '' . $rowu['id'] . delimiter;
            $podatki .= '' . $row['ip'] . delimiter;
            //$podatki .= '' . $row['status'] . delimiter;

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
}