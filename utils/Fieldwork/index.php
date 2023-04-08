<html>
    <head>
        <title>1KA</title>
        <meta charset="utf-8">
        <link rel="shortcut icon" sizes="192x192" href="1ka-192.png">
        <link rel="shortcut icon" sizes="128x128" href="1ka-128.png">
        <style type="text/css">
            html, body {
                font-family: Arial;
            }
            span.veliki {
                display: block;
                width: 50px;
                height: 50px;
                margin-left: auto;
                margin-right: auto;
                text-align: center;
                padding: 7px 6px 4px 6px;
                font: bold 38px helvetica;
                color: #black;
                background-color: #9a9add;
                border: 1px solid #9a9add;
                border-radius: 10px;
            }
            span.veliki.rdeci {
                background-color: #dd9a9a;
            }
            span.veliki.zeleni {
                background-color: #9aff9a;
                padding-bottom: 10px;
                padding-top: 2px;
            }
            div.blok {
                displaY: block;
                float: left;
                width: 200px;
                height: 200px;
                
                text-align: center;
                text-transform: uppercase;
            }
            
            div.spodaj {
                cleaR: both;
                width: 900px;
                margin-top: 25px;
                margin-left: auto;
                margin-right: auto;
            }
            div.zgoraj {
                clear: both;
                width: 900px;
                margin-top: 25px;
                margin-left: auto;
                margin-right: auto;
            }            
            a, a:visited {
                text-decoration: none;
                color: black;
            }
        </style>
    </head>
    
    <body>
        <div class="zgoraj">
<?php
        include_once ('../../settings.php');
        include_once ('../../function.php');
        $result = sisplet_query ("SELECT naslov, id FROM srv_anketa WHERE id IN (" .implode (",", $terminal_surveys) .")");
        while ($r = mysqli_fetch_row ($result)) {
?>
        <div class="blok">
            <a href="/a/<?=$r[1]?>" target="_blank"><span class="veliki"><?=$r[0][0]?></span><br>
            <?=$r[0]?></a>
            
        </div>
<?
        }
?>
        </div>
        <br><br><br>
        <div class="spodaj">
        <div class="blok">
            <a href="/utils/SurveySyncDump.php" target="_blank"><span class="veliki zeleni">&#8644;</span><br>
            Sihnroniziraj</a>
            
        </div>
        <div class="blok">
            <a href="/admin/" target="_blank"><span class="veliki rdeci">A</span><br>
            Admin</a>
            
        </div>
        </div>
        
    </body>
</html>
<?php
?>