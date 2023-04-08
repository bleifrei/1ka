<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 06.05.2017
 *****************************************/

namespace Hierarhija;


include_once($_SERVER['DOCUMENT_ROOT'].'/function.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/admin/survey/definition.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/admin/exportclases/class.pdfIzvozAnalizaFunctions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/admin/exportclases/class.enka.pdf.php');

define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za navadne odgovore
define("ALLOW_HIDE_ZERRO_MISSING", true); // omogočimo delovanje prikazovanja/skrivanja ničelnih vnosti za missinge

define("NUM_DIGIT_AVERAGE", 2); 	// stevilo digitalnih mest za povprecje
define("NUM_DIGIT_DEVIATION", 2); 	// stevilo digitalnih mest za povprecje

define("M_ANALIZA_DESCRIPTOR", "descriptor");
define("M_ANALIZA_FREQUENCY", "frequency");

define("FNT_FREESERIF", "freeserif");
define("FNT_FREESANS", "freesans");
define("FNT_HELVETICA", "helvetica");

define("FNT_MAIN_TEXT", FNT_FREESANS);
define("FNT_QUESTION_TEXT", FNT_FREESANS);
define("FNT_HEADER_TEXT", FNT_FREESANS);

define("FNT_MAIN_SIZE", 10);
define("FNT_QUESTION_SIZE", 9);
define("FNT_HEADER_SIZE", 10);

define("RADIO_BTN_SIZE", 3);
define("CHCK_BTN_SIZE", 3);
define("LINE_BREAK", 6);

define ('PDF_MARGIN_HEADER', 8);
define ('PDF_MARGIN_FOOTER', 12);
define ('PDF_MARGIN_TOP', 18);
define ('PDF_MARGIN_BOTTOM', 18);
define ('PDF_MARGIN_LEFT', 15);
define ('PDF_MARGIN_RIGHT', 15);

use enka_TCPDF;
use SurveyUserSession;
use SurveyMeans;
use HierarhijaAnalysis;
use SurveyInfo;
use SurveyUserSetting;
use SurveyDataSettingProfiles;

class HierarhijaIzvozAnalize
{

    var $anketa;// = array();			// trenutna anketa

    var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
    var $pdf;
    var $currentStyle;
    var $db_table = '';

    public $meansClass = null;		//means class

    var $meanData1;
    var $meanData2;

    var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...


    /**
     * @desc konstruktor
     */
    function __construct ($anketa = null, $podstran = 'mean')
    {
        global $site_path;
        global $global_user_id;

        // preverimo ali imamo stevilko ankete
        if ( is_numeric($anketa) )
        {
            $this->anketa['id'] = $anketa;
            $this->anketa['podstran'] = $podstran;
            // create new PDF document
            $this->pdf = new enka_TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        }
        else
        {
            $this->pi['msg'] = "Anketa ni izbrana!";
            $this->pi['canCreate'] = false;
            return false;
        }
        $_GET['a'] = A_ANALYSIS;

        // preberemo nastavitve iz baze (prej v sessionu)
        SurveyUserSession::Init($this->anketa['id']);
        $this->sessionData = SurveyUserSession::getData();

        // ustvarimo means objekt
        $this->meansClass = new HierarhijaAnalysis($anketa);

        if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
        {
            $this->anketa['uid'] = $global_user_id;
            SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
        }
        else
            return false;
        // ce smo prisli do tu je vse ok
        $this->pi['canCreate'] = true;

        return true;
    }

    // SETTERS && GETTERS

    function checkCreate()
    {
        return $this->pi['canCreate'];
    }
    function getFile($fileName)
    {
        //Close and output PDF document
        ob_end_clean();
        $this->pdf->Output($fileName, 'I');
    }


    function init()
    {
        global $lang;

        // array used to define the language and charset of the pdf file to be generated
        $language_meta = Array();
        $language_meta['a_meta_charset'] = 'UTF-8';
        $language_meta['a_meta_dir'] = 'ltr';
        $language_meta['a_meta_language'] = 'sl';
        $language_meta['w_page'] = $lang['page'];

        //set some language-dependent strings
        $this->pdf->setLanguageArray($language_meta);

        //set margins
        $this->pdf->setPrintHeaderFirstPage(true);
        $this->pdf->setPrintFooterFirstPage(true);
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set header and footer fonts
        $this->pdf->setHeaderFont(Array(FNT_HEADER_TEXT, "I", FNT_HEADER_SIZE));
        $this->pdf->setFooterFont(Array(FNT_HEADER_TEXT, 'I', FNT_HEADER_SIZE));


        // set document information
        $this->pdf->SetAuthor('An Order Form');
        $this->pdf->SetTitle('An Order');
        $this->pdf->SetSubject('An Order');

        // set default header data
        $this->pdf->SetHeaderData(null, null, "www.1ka.si", $this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()));

        //set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $this->pdf->SetFont(FNT_MAIN_TEXT, '', FNT_MAIN_SIZE);
        //set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        return true;
    }

    function encodeText($text)
    { // popravimo sumnike ce je potrebno
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        $text = str_replace(array("&scaron;","&#353;","&#269;"),array("š","š","č"),$text);
        return strip_tags($text);
    }

    function createPdf()
    {
        global $site_path;
        global $lang;


        // izpisemo prvo stran
        //$this->createFrontPage();

        $this->pdf->AddPage();

        $this->pdf->setFont('','B','11');
        $this->pdf->MultiCell(150, 5, $lang['srv_hierarchy_analysis_export'], 0, 'L', 0, 1, 0 ,0, true);

        $this->pdf->setDrawColor(128, 128, 128);
        $this->pdf->setFont('','','6');

        # polovimo nastavtve missing profila
        //SurveyConditionProfiles:: getConditionString();


        $this->meanData1 = $this->sessionData['means']['means_variables']['variabla1'];
        $this->meanData2 = $this->sessionData['means']['means_variables']['variabla2'];

        $means = array();
        # če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
        //$doNewLine = SurveyTimeProfiles :: printIsDefaultProfile(false);

        # če imamo filter ifov ga izpišemo
        //$doNewLine = SurveyConditionProfiles:: getConditionString($doNewLine );

        # če imamo filter spremenljivk ga izpišemo
        //$doNewLine = SurveyVariablesProfiles:: getProfileString($doNewLine , true) || $doNewLine;

        if ($this->meanData1 !== null && $this->meanData2 !== null) {
            $variables1 = $this->meanData2;
            $variables2 = $this->meanData1;
            $c1=0;
            $c2=0;

            if(is_array($variables2) && count($variables2) > 0){
                #prikazujemo ločeno
                if ($this->sessionData['means']['meansSeperateTables'] == true || $this->sessionData['mean_charts']['showChart'] == '1') {
                    foreach ($variables2 AS $v_second) {
                        if (is_array($variables1) && count($variables1) > 0) {
                            foreach ($variables1 AS $v_first) {
                                $_means = $this->meansClass->createMeans($v_first, $v_second);
                                if ($_means != null) {
                                    $means[$c1][0] = $_means;
                                }
                                $c1++;
                            }
                        }
                    }
                }
                #prikazujemo skupaj
                else {
                    foreach ($variables2 AS $v_second) {
                        if (is_array($variables1) && count($variables1) > 0) {
                            foreach ($variables1 AS $v_first) {
                                $_means = $this->meansClass->createMeans($v_first, $v_second);
                                if ($_means != null) {
                                    $means[$c1][$c2] = $_means;
                                }
                                $c2++;
                            }
                        }
                        $c1++;
                        $c2=0;
                    }
                }
            }


            if (is_array($means) && count($means) > 0) {

                $count = 0;
                foreach ($means AS $mean_sub_grup) {

                    if($this->sessionData['mean_charts']['showChart'] == '1'){
                        if($count > 0){
                            $this->pdf->AddPage();
                        }

                        $this->pdf->ln(10);

                        $this->displayMeansTable($mean_sub_grup);
                        $this->displayChart($count);
                    }
                    else{
                        if($count%2 == 0 && $count > 0){
                            $this->pdf->AddPage();
                        }

                        $this->pdf->ln(10);
                        $this->displayMeansTable($mean_sub_grup);
                        $this->pdf->ln(10);
                    }

                    $count++;
                }
            }
        }
    }

    public function displayMeansTable($_means) {
        global $lang;

        #število vratic in število kolon
        $cols = count($_means);
        # preberemo kr iz prvega loopa
        $rows = count($_means[0]['options']);

        // sirina ene celice
        $singleWidth = round( 180 / $cols / 5 );

        // visina prve vrstice
        $firstHeight = 0;
        for ($i = 0; $i < $cols; $i++) {

            $label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);

            /*$linecount = $this->pdf->getNumLines($this->encodeText($label1), $singleWidth*2);
            $height = ( $linecount == 1 ? 4.7 : (4.7 + ($linecount-1)*3.3) );
            $firstHeight = ($height > $firstHeight) ? $height : $firstHeight;*/
            $firstHeight = ($firstHeight > $this->getCellHeight($this->encodeText($label1), $singleWidth*2)) ? $firstHeight : $this->getCellHeight($this->encodeText($label1), $singleWidth*2);
        }


        // prva vrstica
        $this->pdf->setFont('','B','6');

        $label2 = $this->meansClass->getSpremenljivkaTitle($_means[0]['v2']);
        $this->pdf->MultiCell(80, $firstHeight, $this->encodeText($label2), 'TLR', 'C', 0, 0, 0 ,0, true);

        for ($i = 0; $i < $cols; $i++) {

            $label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);
            $this->pdf->MultiCell($singleWidth*5, $firstHeight, $this->encodeText($label1), 1, 'C', 0, 0, 0 ,0, true);
        }
        $this->pdf->MultiCell(1, $firstHeight, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);

        $this->pdf->setFont('','','6');

        // druga vrstica
        $this->pdf->MultiCell(80, 7, $this->encodeText(''), 'BLR', 'C', 0, 0, 0 ,0, true);

        for ($i = 0; $i < $cols; $i++) {

            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_means_label']), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_hierarchy_label_st']), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_hierarchy_label_min']), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_hierarchy_label_max']), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($lang['srv_hierarchy_label_std_dev']), 1, 'C', 0, 0, 0 ,0, true);
        }
        $this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);


        // vrstice s podatki
        if (count($_means[0]['options']) > 0) {
            foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {

                $variabla = $crossVariabla2['naslov'];
                # če ni tekstovni odgovor dodamo key
                if ($crossVariabla2['type'] !== 't' ) {
                    if ($crossVariabla2['vr_id'] == null) {
                        $variabla .= ' ( '.$ckey2.' )';
                    } else {
                        $variabla .= ' ( '.$crossVariabla2['vr_id'].' )';
                    }
                }
                $this->pdf->MultiCell(80, 7, $this->encodeText($variabla), 1, 'C', 0, 0, 0 ,0, true);

                # celice z vsebino
                for ($i = 0; $i < $cols; $i++) {
                    $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
                    $this->pdf->MultiCell($singleWidth, 7, $this->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]), 1, 'C', 0, 0, 0 ,0, true);

                    $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['min'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
                    $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['max'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
                    $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['stdDeviation'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);

                }
                $this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
            }
        }

        // SKUPAJ
        $this->pdf->MultiCell(80, 7, $this->encodeText($lang['srv_means_label3']), 1, 'C', 0, 0, 0 ,0, true);

        for ($i = 0; $i < $cols; $i++) {

            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText((int)$_means[$i]['sumaSkupna']), 1, 'C', 0, 0, 0 ,0, true);

            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMin'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMax'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);
            $this->pdf->MultiCell($singleWidth, 7, $this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaStdDeviation'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))), 1, 'C', 0, 0, 0 ,0, true);

        }
        $this->pdf->MultiCell(1, 7, $this->encodeText(''), 0, 'C', 0, 1, 0 ,0, true);
    }

    function displayChart($counter){
        global $lang;

        $variables1 = $this->meanData1;
        $variables2 = $this->meanData2;

        $pos1 = floor($counter / count($variables2));
        $pos2 = $counter % count($variables2);

        $chartID = implode('_', $variables1[$pos1]).'_'.implode('_', $variables2[$pos2]);
        $chartID .= '_counter_'.$counter;


        $settings = $this->sessionData['mean_charts'][$chartID];
        $imgName = $settings['name'];

        $size = getimagesize('pChart/Cache/'.$imgName);
        $height = $size[1] / 4;

        if($this->pdf->getY() + $height > 250)
        {
            $this->pdf->AddPage();
        }
        else
            $this->pdf->setY($this->pdf->getY() + 15);


        $this->pdf->Image('pChart/Cache/'.$imgName, $x='', $y='', $w=200, $h, $type='PNG', $link='', $align='N', $resize=true, $dpi=1600, $palign='C', $ismask=false, $imgmask=false, $border=0);


        $this->pdf->setY($this->pdf->getY() + 5);
    }

    /*Skrajsa tekst in doda '...' na koncu*/
    function snippet($text,$length=64,$tail="...")
    {
        $text = trim($text);
        $txtl = strlen($text);
        if($txtl > $length)
        {
            for($i=1;$text[$length-$i]!=" ";$i++)
            {
                if($i == $length)
                {
                    return substr($text,0,$length) . $tail;
                }
            }
            $text = substr($text,0,$length-$i+1) . $tail;
        }
        return $text;
    }

    function drawLine()
    {
        $cy = $this->pdf->getY();
        $this->pdf->Line(15, $cy , 195, $cy , $this->currentStyle);
    }

    function setUserId($usrId) {$this->anketa['uid'] = $usrId;}
    function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

    function formatNumber($value,$digit=0,$sufix="")
    {
        if ( $value <> 0 && $value != null )
            $result = round($value,$digit);
        else
            $result = "0";
        $result = number_format($result, $digit, ',', '.').$sufix;

        return $result;
    }

    function getCellHeight($string, $width){

        $this->pdf->startTransaction();
        // get the number of lines calling you method
        $linecount = $this->pdf->MultiCell($width, 0, $string, 0, 'L', 0, 0, '', '', true, 0, false, true, 0);
        // restore previous object
        $this->pdf = $this->pdf->rollbackTransaction();

        $height = ($linecount <= 1) ? 4.7 : $linecount * ($this->pdf->getFontSize() * $this->pdf->getCellHeightRatio()) + 2;

        return $height;
    }

}