<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 26.02.2016
 *****************************************/

namespace App\Models;

use Common;

class User extends Model
{

    public static function addUserEmailToList($email)
    {
        # preverimo ali seznam alert lista za anketo že obstaja
        $sql_string = "SELECT pid, respondents FROM srv_invitations_recipients_profiles WHERE uid = '0' AND from_survey = '0'";
        $sql_query = sisplet_query($sql_string);
        if (mysqli_num_rows($sql_query) > 0) {
            # seznam že obstaja dodoamo nov email
            $sql_row = mysqli_fetch_assoc($sql_query);
            $emalis = explode("\n", $sql_row['respondents']);

            if (!in_array($email, $emalis) && $sql_row['pid'] > 0) {
                $emalis[] = $email;
                $sql = sisplet_query("UPDATE srv_invitations_recipients_profiles SET respondents = '" . implode("\n", $emalis) . "' WHERE from_survey = '0' AND pid ='" . $sql_row['pid'] . "'");
            }
        } else {
            #seznam še ne obstaja, skreiramo novega
            # shranjujemo v nov profil
            $sql_insert = "INSERT INTO srv_invitations_recipients_profiles (name,uid,fields,respondents,insert_time,comment,from_survey) "
                . "VALUES ('alert lista', '0', 'email', '$email', NOW(), 'alert lista','0' )";
            $sqlQuery = sisplet_query($sql_insert);
        }
    }

    public static function sinhronizeInvitationEmail($email)
    {
        Common::getInstance()->Init(get('anketa'));
        if (Common::getInstance()->validEmail($email)) {

            # vnešen email je vlejaven preverimo ali ze imamo vabilo za tega uporabnika
            $sqlu = sisplet_query("SELECT inv_res_id FROM srv_user WHERE id = '" . get('usr_id') . "' AND inv_res_id IS NOT NULL");

            if (mysqli_num_rows($sqlu) > 0) {
                # userj je dodan preko novih vabil zato updejtamo status še tam
                list($inv_res_id) = mysqli_fetch_row($sqlu);
                #updejtamo vabila
                $sqlString = "UPDATE srv_invitations_recipients SET email = '$email' WHERE ank_id='" . get('anketa') . "' AND id='$inv_res_id'";
                sisplet_query($sqlString);
                sisplet_query("COMMIT");

            }
        }
    }

}