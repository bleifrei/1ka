<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 22.05.2017
 *****************************************/

namespace Hierarhija;

use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use SurveyDataFile;
use SurveyInfo;

class HierarhijaPorocilaClass
{
    // Konstante do direktorijev
    const tempFolder = "admin/survey/modules/mod_hierarhija/porocila/temp/";
    const scriptFolder = "admin/survey/modules/mod_hierarhija/porocila/R/";
    const resultFolder = "admin/survey/modules/mod_hierarhija/porocila/results/";

    public function __construct($anketa = null)
    {
        if (is_null($anketa))
            return 'Ni izbrane ankete';

        $this->anketa = $anketa;

        global $site_path;
        $this->site_path = $site_path;

    }

    public function izvoz($vrsta = 'word')
    {
        if (!empty($_GET['t']) && in_array($_GET['t'], ['word', 'pdf']))
            $vrsta = $_GET['t'];

        // Samo za debug
        if (false && $admin_type == 0) {

            // R parametri za PDF
            if ($vrsta == 'pdf') {
                $script = $this->site_path . self::scriptFolder . 'test_1ka_pdf.R';
                $content_type = 'text/x-csv; charset=utf-8';
                $file_name = 'test.pdf';
            }else{
                // R parametri, za DOC,DOCX
                $script = $this->site_path . self::scriptFolder . 'test_1ka.R';
                $content_type = 'application/octet-stream';
                $file_name = 'test.docx';
            }

            $out = exec('Rscript ' . $script . ' 2>&1', $output, $return_var);

            // Testiranje - izpis errorjev
                echo '<div>';
                echo 'Rscript ' . $script;
                //echo '<br />'.$out.'<br />';
                var_dump($output);
                echo '</div>';


            // Pripravimo file za download
            if (file_exists($this->site_path . self::resultFolder . $file_name)) {

                $file = $this->site_path . self::resultFolder . $file_name;

                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . basename($file_name));
                header('Content-Type: '.$content_type);
                header("Content-Transfer-Encoding: Binary");
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));

                ob_clean();
                flush();

                readfile($file);
            }

        }

        // Pripravi CSV s podatki
         $this->pripraviCSV();
	       $this->pripraviCSVuciteljev();

        // R parametri, za DOC,DOCX
        $script = $this->site_path . self::scriptFolder . 'Hierarhija_1ka.R';
        $content_type = 'application/octet-stream';
        //Ime ankete
        $ime = slug(SurveyInfo::getSurveyTitle(), '_');
        $file_name = 'SAMOEVALVACIJSKO_POROCILO__' . $ime . '.docx';

        // R parametri za PDF
        if ($vrsta == 'pdf') {
            $script = $this->site_path . self::scriptFolder . 'Hierarhija_1ka_pdf.R';
            $content_type = 'text/x-csv; charset=utf-8';
            $file_name = 'SAMOEVALVACIJSKO_POROCILO_' . $ime . '.pdf';
        }

        $shrani_id = HierarhijaQuery::getOptions($this->anketa, 'srv_hierarhija_shrani_id');
        $logo = sisplet_query("SELECT logo FROM srv_hierarhija_shrani WHERE id='" . $shrani_id . "' AND anketa_id='" . $this->anketa . "'", "obj")->logo;

        // 1 - odgovori v anketami
				// 2 - seznam uciteljev in predmetov
	      // 3 - ime datoteke v katero shranimo (pdf, docx)
	      // 4 - logo
        $param = 'hierarhija_' . $this->anketa . '.csv hierarhija_ucitelji_' . $this->anketa . '.csv '. $file_name . ' ' . $logo;

        $out = exec('Rscript ' . $script . ' ' . $param . ' 2>&1', $output, $return_var);

        // Testiranje - izpis errorjev
        if ($admin_type == 0) {
            echo '<div>';
            echo 'Rscript ' . $script;
            //echo '<br />'.$out.'<br />';
            var_dump($output);
            echo '</div>';
        }

        // Pripravimo file za download
        if (file_exists($this->site_path . self::resultFolder . $file_name)) {

            $file = $this->site_path . self::resultFolder . $file_name;

            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename=' . basename($file_name));
            header('Content-Type: '.$content_type);
            header("Content-Transfer-Encoding: Binary");
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));

            ob_clean();
            flush();

            readfile($file);
        }

        // Na koncu pobrisemo zacasne datoteke
        $this->deleteTemp();

        // Ugasnemo skripto:)
        die();
    }

	/**
	 * Pripravimo CSV izvoz vseh podatkov
	 */
    private function pripraviCSV()
    {
        $temp_folder = $this->site_path . self::tempFolder;

        $SDF = SurveyDataFile::get_instance();
        $SDF->init($this->anketa);
        $_headFileName = $SDF->getHeaderFileName();
        $_dataFileName = $SDF->getDataFileName();
        $_fileStatus = $SDF->getStatus();

        if(count(file($_dataFileName)) < 3){
            global $site_url;
            echo 'Premalo podatkov';
            redirect($site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA_SUPERADMIN . '&m=' . M_ANALIZE.'&error=invalid');
            die();
        }

        if ($_headFileName != null && $_headFileName != '') {
            $_HEADERS = unserialize(file_get_contents($_headFileName));
        } else {
            echo 'Error! Empty file name!';
        }

        // Zaenkrat dopuscamo samo status 6 in brez lurkerjev
        $status_filter = '(' . STATUS_FIELD . ' ~ /6|5/)&&(' . LURKER_FIELD . '==0)';
        //$status_filter = '('.STATUS_FIELD.'==6)&&('.LURKER_FIELD.'==0)';

        //$start_sequence = $_HEADERS['_settings']['dataSequence'];
        $start_sequence = 2;
        $end_sequence = $_HEADERS['_settings']['metaSequence'] - 1;

        $field_delimit = ';';

        // Pridobi filtre
//	    $hierarhija_analize =  (new \HierarhijaAnalysis($this->anketa));
//	    $hierarhija_filter = $hierarhija_analize->filterHierarhijeIzTekstovneDatoteke();
//	    $ucitelj_filter = $hierarhija_analize->filterHierarhijeZaSpecificnegaUciteljaIzDatoteke();
//	    (!empty($ucitelj_filter) ? $ucitelj_filter : $hierarhija_filter) // preveri izpis conzole

        // Filtriramo podatke po statusu in jih zapisemo v temp folder
        $out = shell_exec('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} ' . $status_filter . '\' '  .  $_dataFileName . ' | cut -d \'|\' -f ' . $start_sequence . '-' . $end_sequence . ' >> ' . $temp_folder . 'temp_data_' . $this->anketa . '.dat');
//            dump('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$status_filter.'\' '.$_dataFileName.' | cut -d \'|\' -f '.$start_sequence.'-'.$end_sequence.' >> '.$temp_folder.'temp_data_'.$this->anketa.'.dat');


        // Ustvarimo koncni CSV
        if ($fd = fopen($temp_folder . 'temp_data_' . $this->anketa . '.dat', "r")) {

            $fd2 = fopen($temp_folder . 'hierarhija_' . $this->anketa . '.csv', "w+");

            $convertType = 1; // kateri tip konvertiranja uporabimo
            $convertTypes[1] = array('charSet' => 'windows-1250',
                'delimit' => ';',
                'newLine' => "\n",
                'BOMchar' => "\xEF\xBB\xBF");
            # dodamo boomchar za utf-8
            fwrite($fd2, $convertTypes[$convertType]['BOMchar']);

            # naredimo header row
            foreach ($_HEADERS AS $spid => $spremenljivka) {
                if (count($spremenljivka['grids']) > 0) {
                    foreach ($spremenljivka['grids'] AS $gid => $grid) {
                        foreach ($grid['variables'] AS $vid => $variable) {
                            if ($spremenljivka['tip'] !== 'sm' && !($variable['variable'] == 'uid' && $variable['naslov'] == 'User ID')) {
                                $output1 .= strip_tags($variable['variable']) . $field_delimit;
                                $output2 .= '"' . strip_tags($variable['naslov']) . '"' . $field_delimit;
                            }
                        }
                    }
                }
            }

            fwrite($fd2, $output1 . "\r\n");
            fwrite($fd2, $output2 . "\r\n");

            while ($line = fgets($fd)) {

                $temp = array();
                $temp = explode('|', $line);

                // Zamenjamo številke z vrednostmi predmetov hierarhije
                $grupaId = HierarhijaOnlyQuery::getGrupaId($this->anketa);

                // Pripravimo polje po katerem iščemo nivoje
                $stRavni = sisplet_query("SELECT count('id') as st FROM srv_hierarhija_ravni WHERE anketa_id = '" . $this->anketa . "'", "obj")->st;
                $isci_po_polju = "'nivo1'";
                for ($i = 2; $i <= $stRavni; $i++) {
                    $isci_po_polju .= ",'nivo" . $i . "'";
                }

                $elementi_hierarhije = sisplet_query("SELECT id, variable FROM srv_spremenljivka WHERE gru_id='" . $grupaId . "' AND variable IN (" . $isci_po_polju . ") ORDER BY vrstni_red", "obj");

                // od 8 elementa v txt datoteki se začne naprej hierarhija
                $line_explode = explode('|', $line);
                // številka prvega elementa, ki ga bomo zamenjali - vloga
                $st = 9;
                foreach ($elementi_hierarhije as $element) {
                    $ime_strukture = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='" . $element->id . "' AND variable='" . $line_explode[$st] . "'", "obj")->naslov;
                    $line_explode[$st] = $ime_strukture;
                    $st++;
                }

                $line = join('|', $line_explode);
                $line = '"' . str_replace(array("\r", "\n", "\"", "|"), array("", "", "", '";"'), $line) . '"';


                // Spremenimo encoding v windows-1250
                //$line = iconv("UTF-8","Windows-1250//TRANSLIT", $line);

                fwrite($fd2, $line);
                fwrite($fd2, "\r\n");

            }

            fclose($fd2);
        }
        fclose($fd);


        // Na koncu pobrisemo temp datoteke
        if (file_exists($temp_folder . '/temp_data_' . $this->anketa . '.dat')) {
            unlink($temp_folder . '/temp_data_' . $this->anketa . '.dat');
        }
    }

	/**
	 * Izvoz hierarhije učiteljev z imenomin priimkom ter elektronskim naslovom
	 */
    private function pripraviCSVuciteljev(){
    	$ucitelji = HierarhijaIzvoz::getInstance($this->anketa)->csvIzvozStruktureZaObdelavo(true);

	    $temp_folder = $this->site_path . self::tempFolder;
	    $file = fopen($temp_folder . 'hierarhija_ucitelji_' . $this->anketa . '.csv', "w+");

	    $convertType = 1; // kateri tip konvertiranja uporabimo
	    $convertTypes[1] = array('charSet' => 'windows-1250',
		    'delimit' => ';',
		    'newLine' => "\n",
		    'BOMchar' => "\xEF\xBB\xBF");
	    # dodamo boomchar za utf-8
	    fwrite($file, $convertTypes[$convertType]['BOMchar']);

    	foreach($ucitelji as $ucitelj){
    		fputcsv($file, $ucitelj);
	    }

	    fclose($file);
    }

    private function deleteTemp()
    {
        $temp_folder = $this->site_path . self::tempFolder;

        // Pobriše CSV datoteko
        if (file_exists($temp_folder . '/hierarhija' . $this->anketa . '.csv'))
            unlink($temp_folder . '/hierarhija' . $this->anketa . '.csv');

	    // Pobriše CSV datoteko uciteljev
	    if (file_exists($temp_folder . '/hierarhija_ucitelji_' . $this->anketa . '.csv'))
		      unlink($temp_folder . '/hierarhija_ucitelji_' . $this->anketa . '.csv');

        // Pobrisemo še vse v rezultatih
        $files = glob($this->site_path . self::resultFolder . '*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
    }
}