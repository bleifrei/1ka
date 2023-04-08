<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 26.02.2016
 *****************************************/

namespace App\Controllers;

use App\Models\Model;
use SurveyInfo;

class StatisticController extends Controller
{

    /**
     * @desc poisce vprasanja, ki imajo statistiko in jih poklice
     */
    public static function displayStatistika($konec = false)
    {

        $sql = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '" . get('grupa') . "'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['vrstni_red'];

        if ($konec) $vrstni_red = 99999999;

        //preverimo ce gre za glasovanje ali navadno anketo
        $rowA = SurveyInfo::getInstance()->getSurveyRow();

        //statistika za glasovanje
        if ($rowA['survey_type'] == 0) {

            if (get('printPreview')) {
                $sql2 = sisplet_query("SELECT ank_id, spr_id, show_title FROM srv_glasovanje WHERE ank_id='" . get('anketa') . "'");
                $row2 = mysqli_fetch_array($sql2);

                $spremenljivka = $row2['spr_id'];

                $sqla = sisplet_query("SELECT starts, expire, statistics, db_table FROM srv_anketa WHERE id ='$row2[ank_id]'");
                $rowa = mysqli_fetch_array($sqla);

                $row = Model::select_from_srv_spremenljivka($spremenljivka);

                $text = $rowa['statistics'];

                if ($text != '')
                    echo '      <h3>' . $text . "\n";
                else
                    echo '      <h3>' . "\n";

                if ($row2['show_title'] == 0)
                    echo '      ' . $row['naslov'] . '</h3>' . "\n";
                else
                    echo '      </h3>' . "\n";
            } else {
                $sqlG = sisplet_query("SELECT spr_id FROM srv_glasovanje WHERE ank_id='" . get('anketa') . "'");
                $rowG = mysqli_fetch_array($sqlG);

                $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='" . get('anketa') . "' AND vrstni_red<'$vrstni_red' ORDER BY vrstni_red DESC LIMIT 1");
                if (mysqli_num_rows($sql) > 0) {
                    $row = mysqli_fetch_array($sql);
                    $prev_grupa = $row['id'];

                    $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$prev_grupa' AND stat>'0' ORDER BY vrstni_red ASC");
                    while ($row = mysqli_fetch_array($sql)) {
                        echo ' <div class="spremenljivka" id="spremenljivka_statistika">' . "\n";
                        self::displayStatGlasovanje($rowG['spr_id']);
                        echo ' </div>' . "\n";
                    }
                }
            }
        } //navadna statistika
        else {
            $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='" . get('anketa') . "' AND vrstni_red<'$vrstni_red' ORDER BY vrstni_red DESC LIMIT 1");
            if (mysqli_num_rows($sql) > 0) {
                $row = mysqli_fetch_array($sql);
                $prev_grupa = $row['id'];

                $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$prev_grupa' AND stat='1' ORDER BY vrstni_red ASC");
                while ($row = mysqli_fetch_array($sql)) {
                    echo ' <div class="spremenljivka" id="spremenljivka_statistika">' . "\n";
                    self::displayStat($row['id']);
                    echo ' </div>' . "\n";
                }
            }
        }

    }

    /**
     * @desc prikaze statistike za odgovor
     */
    public static function displayStat($spremenljivka, $spol = 0)
    {
        global $lang;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $text = $lang['results'];

        echo '      <h3>' . $text . ': ' . $row['naslov'] . '</h3>' . "\n";

        $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost v, srv_data_vrednost" . get('db_table') . " d WHERE v.spr_id='$spremenljivka' AND v.id=d.vre_id");
        $row = mysqli_fetch_array($sql);
        $total = $row['count'];

        $sql = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red");
        while ($row = mysqli_fetch_array($sql)) {
            $sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_vrednost" . get('db_table') . " WHERE vre_id='$row[id]'");
            $row1 = mysqli_fetch_array($sql1);


            if ($total > 0)
                $procent = round($row1['count'] / $total, 3) * 100;
            else
                $procent = 0;

            echo '        <p><strong>' . $row['naslov'] . ':</strong> ' . $procent . '%</p>' . "\n";
        }
    }

    /**
     * @desc prikaze statistiko za glasovanje
     */
    public function displayStatGlasovanje($spremenljivka, $spol = 0)
    {
        global $lang;
        global $site_url;
        global $global_user_id;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        //preverimo ce gre za glasovanje
        $sql2 = sisplet_query("SELECT * FROM srv_glasovanje WHERE spr_id='$spremenljivka'");
        $row2 = mysqli_fetch_array($sql2);

        $sqla = sisplet_query("SELECT starts, expire, statistics, db_table FROM srv_anketa WHERE id ='$row2[ank_id]'");
        $rowa = mysqli_fetch_array($sqla);

        SurveyInfo::getInstance()->SurveyInit($row2['ank_id']);	
        $db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
        save('db_table', $db_table);

        $text = $rowa['statistics'];

        if ($text != '')
            echo '      <span class="glas_settings_title">' . $text . "\n";
        else
            echo '      <span class="glas_settings_title">' . "\n";

        if ($row2['show_title'] == 0)
            echo '      ' . $row['naslov'] . '</span>' . "\n";
        else
            echo '      </span>' . "\n";

        $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost v, srv_data_vrednost" . get('db_table') . " d WHERE v.spr_id='$spremenljivka' AND v.id=d.vre_id");
        $row = mysqli_fetch_array($sql);
        $total = $row['count'];

        $sql = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red");
        while ($row = mysqli_fetch_array($sql)) {

            //prikaz statistike za moske(pri glasovanju)
            if ($spol == 1) {
                $sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_vrednost" . get('db_table') . " v, srv_data_glasovanje g WHERE v.vre_id='$row[id]' AND v.usr_id=g.usr_id AND g.spr_id='$spremenljivka' AND g.spol='1' ");
                $row1 = mysqli_fetch_array($sql1);
            } //prikaz statistike za zenske(pri glasovanju)
            elseif ($spol == 2) {
                $sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_vrednost" . get('db_table') . " v, srv_data_glasovanje g WHERE v.vre_id='$row[id]' AND v.usr_id=g.usr_id AND g.spr_id='$spremenljivka' AND g.spol='2' ");
                $row1 = mysqli_fetch_array($sql1);
            } else {
                $sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_vrednost" . get('db_table') . " WHERE vre_id='$row[id]'");
                $row1 = mysqli_fetch_array($sql1);
            }

            if ($total > 0)
                $procent = round($row1['count'] / $total, 3) * 100;
            else
                $procent = 0;

            echo '        <p style="margin-top: 10px;"><strong>' . $row['naslov'] . ':</strong>' . "\n";


            //ce gre za glasovanje in imamo nastavljen prikaz grafa ga izrisemo
            if ($row2['show_graph'] == 1) {

                echo '</p>';

                echo '        <div "style=" margin: -20px 0 0 25%; text-align: right; width: 25%; font-size: 12px;">' . "\n";
                if ($row2['show_results'] == 1) {
                    echo $row1['count'];
                    if ($row2['show_percent'] == 1)
                        echo ' (' . $procent . '%)';
                } elseif ($row2['show_percent'] == 1)
                    echo $procent . '%';
                echo '        </div>' . "\n";

                echo '<div id="graph"><div class="graph_fill" style="width: ' . $procent . '%;"></div></div>';
            } else {
                if ($row2['show_results'] == 1) {
                    echo $row1['count'];
                    if ($row2['show_percent'] == 1)
                        echo ' (' . $procent . '%)';
                } elseif ($row2['show_percent'] == 1)
                    echo $procent . '%';
                echo '</p>';
            }
        }

        //stevilo vseh glasov
        if ($row2['stat_count'] == 1) {
            if ($spol != 0) {
                $sql5 = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_glasovanje WHERE spr_id='$spremenljivka' AND spol='1' ");
                $row5 = mysqli_fetch_array($sql5);
                $moskih_glasov = $row5['count'];

                if ($spol == 1) {
                    $percent = round($moskih_glasov / $total, 3) * 100;
                    $total = $moskih_glasov;
                } else {
                    $percent = round(($total - $moskih_glasov) / $total, 3) * 100;
                    $total = $total - $moskih_glasov;
                }
            }

            echo '<p style="margin-top: 10px;">' . $lang['glasovanja_count'] . ': ' . $total;
            if ($spol != 0)
                echo ' (' . $percent . '%)';
            echo '</p>';
        }

        //cas aktivnosti ankete
        if ($row2['stat_time'] == 1) {
            $start = strtotime($rowa['starts']);
            $end = strtotime($rowa['expire']);
            echo '<p>' . $lang['glasovanja_time'] . ' ' . date("m.d.Y", $start) . ' ' . $lang['glasovanja_time_end'] . ' ' . date("m.d.Y", $end) . '</p>';
        }

        //prikaz statistike po spolu (samo kjer je nastavljena opcija izbira spola) - gumbi za preklop
        if ($row2['spol'] == 1) {
            echo '<div class="spol_buttons">' . "\n";

            if ($spol == 0) echo '<input type="button" class="active" value="' . $lang['hour_all'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'0\');">';
            else            echo '<input type="button" value="' . $lang['hour_all'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'0\');">';

            if ($spol == 1) echo '<input type="button" class="active" value="' . $lang['glasovanja_spol_moski'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'1\');">';
            else            echo '<input type="button" value="' . $lang['glasovanja_spol_moski'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'1\');">';

            if ($spol == 2) echo '<input type="button" class="active" value="' . $lang['glasovanja_spol_zenske'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'2\');">';
            else            echo '<input type="button" value="' . $lang['glasovanja_spol_zenske'] . '" onClick="stat_spol(\'' . $spremenljivka . '\', \'2\');">';

            echo '</div>' . "\n";
        }

        //prikaz arhiva statistik anket
        if ($row2['stat_archive'] == 1) {

            echo '<div id="arhiv">';
            echo 'Ankete iz arhiva:';

            $sqlArchive = sisplet_query("SELECT ank_id FROM srv_glasovanje WHERE stat_archive = '1' AND ank_id != '$row2[ank_id]'");

            echo '<ul>';
            while ($rowArchive = mysqli_fetch_array($sqlArchive)) {
                $sql4 = sisplet_query("SELECT naslov, starts, insert_uid, hash FROM srv_anketa WHERE id = '$rowArchive[ank_id]'");
                $row4 = mysqli_fetch_array($sql4);

                if($row4['insert_uid'] == $global_user_id){
                    echo '<li>';

                    echo '<a href="' . $site_url . 'a/' . $row4['hash'] . '&glas_end=0' . get('cookie_url') . '">' . $row4['naslov'] . '</a>';
                    echo ' (' . $row4['starts'] . ')';

                    echo '</li>';
                }
            }
            echo '</ul>';
            echo '</div>';
        }
    }

}