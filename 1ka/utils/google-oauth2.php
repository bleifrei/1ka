<?php

    require_once ('../settings.php');
    require_once ('../function.php');
    require_once ('../function/ProfileClass.php');
    

    $profile = new Profile();
    $profile->GoogleLogin();

?>