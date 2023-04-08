<?php

/***************************************
 * Description: xport class za izvoz
 * Autor: Robert Šmalc
 * Created date: 02.06.2016
 *****************************************/
class Export
{

    private $anketa;
    public function __construct($anketa = null)
    {
        if ((isset ($_REQUEST['anketa']) && $_REQUEST['anketa'] > 0) || (isset ($anketa) && $anketa > 0)) {
            $this->anketa = (isset ($_REQUEST['anketa']) && $_REQUEST['anketa'] > 0) ? $_REQUEST['anketa'] : $anketa;
        } else {
            return 'Anketa ID ne obstaja';
        }

        return $this;
    }

    static private $instance;
    public static function init()
    {
        if (!self::$instance)
            self::$instance = new Export();

        return self::$instance;
    }


    /************************************************
     * Izvoz polja v CSV format
     * @return CSV
     ************************************************/

    protected $prefix;
    protected $array;
    protected $ime;

    public function csv($ime = 'CSV_izvoz', $array = null, $prefix = null)
    {
        global $site_path;

        // Prefix mora biti male črke in braz presledkov
        if (!is_null($prefix)) {
            $prefix = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $prefix)));
        }else{
            $ime = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $ime)));
            $prefix = $ime;
        }

        $prefix .= '_';

        $temp_folder = $site_path . 'admin/survey/tmp/';

        // če direktorij še ne obstaja
        if (!file_exists($temp_folder))
            mkdir($temp_folder, 0755);

        $file = $temp_folder . $prefix . $this->anketa . '.csv';

        $datoteka = fopen($file, 'w') or die('Datoteke (' . $file . ') ni mogoče odpreti.');

        // Preverimo če gre za polje ali string
        if(is_array($array)) {
            foreach ($array as $row) {
                fputcsv($datoteka, $row, ',');
            }
        }elseif(is_string($array)){
            fputs($datoteka, $array);
        }

        fclose($datoteka);

        ob_clean();

        header('Content-Description: File Transfer');
//        header("Content-type: text/x-csv; charset=utf-8");
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($file));
        header("Content-Disposition: attachment; filename=" . $ime . ".csv");

        flush();

        readfile($file);

        if (file_exists($file))
            unlink($file);

        die();
    }

}