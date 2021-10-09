<?php

/**
 *
 *  Class ki skrbi za posiljanje cron emailov povezanih z racunom, placili...
 *
 */

class UserMailCron{


    // ID userja
    private $usr_id = '';

    
    function __construct(){
            
    }


    // Nastavimo fazo v kateri se nahaja uporabnik
    public function setStage($stage){

    }


    // Izvedemo cron ob 9h zjutraj
    public static function executeCron(){

        // Loop cez vse userje v bazi
        $sql = sisplet_query("SELECT c.*, u.email, u.ime, u.type, u.status, u.name, u.surname
                                        FROM user_cronjob c, users u
                                        WHERE (a.package_id = 2 OR a.package_id = 3) 
                                            AND ".$interval_query."
                                            AND u.id=a.usr_id
                                    ");

        while($row = mysqli_fetch_array($sql)){
            
            // Process cronjob for user
        }
    }

}