<?php

/**
 * Test speed of query
 * Uroš Podkrižnik 21.12.2017
 */
class testQuerySpeed {

    function __construct() {
        
    }

    function test($sql_query = "SELECT id FROM srv_anketa", $loopnum = 1000) {
        $microa = 0;
        $microo = 0;
        $microni = 0;
        $micron = 0;
        for ($i = 0; $i < $loopnum; $i++) {
            $starta = microtime(true);
            $sql_array = sisplet_query($sql_query, 'array');
            foreach ($sql_array as $pair)
                $res = $pair;
            $enda = microtime(true);
            $microa += ($enda - $starta);

            $starto = microtime(true);
            $sql_obj = sisplet_query($sql_query, 'obj');
            foreach ($sql_obj as $pair)
                $res = $pair;
            $endo = microtime(true);
            $microo += ($endo - $starto);

            $startn = microtime(true);
            $sql = sisplet_query($sql_query);
            while ($row = mysqli_fetch_assoc($sql))
                $res = $row;
            $endn = microtime(true);
            $microni += ($endn - $startn);

            $startn = microtime(true);
            $sql = sisplet_query($sql_query);
            while ($row = mysqli_fetch_assoc($sql))
                $res = $row;
            $endn = microtime(true);
            $micron += ($endn - $startn);
        }
        error_log('$microa ' . $microa);
        error_log('$microo ' . $microo);
        error_log('$microni ' . $microni);
        error_log('$micron ' . $micron);
    }

}
