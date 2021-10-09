<?php

# Skripta za downloadanje filov (ker ga izvedemo preko lepega linka "www.1ka.si/1ka_install")


if($_GET['type'] == 'install')
        header("location: https://www.1ka.si/uploadi/1ka_install_7-5-2021.zip");
elseif($_GET['type'] == 'offline')
        header("location: https://www.1ka.si/uploadi/1ka_install_offline_23-11-2018.zip");


?>
