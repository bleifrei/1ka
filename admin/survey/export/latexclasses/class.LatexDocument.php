<?php

/**
 *
 *	Class ki skrbi inicializacijo latex dokumenta
 *
 *
 */
 
####################################
/*Uporabljeni Latex Packages:
- geometry za robove, ipd.
- tabularx za lazje urejanje tabel (ni podprto v latex2rtf)
- \usepackage{ltablex} - za vecstransko tabelo, \textheight=730pt za portrait, pred vsako tabelo \keepXColumns, da obdrzi sirino tabele na sirini teksta
- inputenc za uporabo utf8
- fancyhdr za ureditev glav in nog
- lastpage za pridobitev zadnje strani v dokumentu oz. stevilo strani v dokumentu, da deluje, je potrebno tex compile-at 2 krat
- \usepackage{multirow} za vecvrsticno tabelo
- \usepackage{tikz} in \usetikzlibrary{calc} za risanje drsnikov
- \usepackage{enumitem} za itemize, ki je potreben za pravilen izris rolet in izberite iz seznama v tabelah
- \usepackage{eurosym} za izpis simbola €
- \usepackage[export]{adjustbox} za poravnavo slik


linespread je razmik med vrsticami

\renewcommand{\tabularxcolumn}[1]{>{\arraybackslash}m{#1}} za sredinsko poravnane zadeve v tabularx tabeli

newenvironment{absolutelynopagebreak} za prepreciti prelome strani, kjer ni potrebno

\newcolumntype{s}{>{\hsize=.55\hsize \centering\arraybackslash}X} za sredinsko poravnane celice manjse dimenzije (0.55 navadne dimenzije)

\newcolumntype{S}{>{\hsize=.2\hsize}X}	za celice manjse dimenzije (0.3 navadne dimenzije)

\newcolumntype{b}{>{\hsize=.5\hsize}X} za celice manjse dimenzije (0.5 navadne dimenzije)

\newcolumntype{B}{>{\hsize=1.3\hsize}X} za celico vecje dimenzije od navadne (1.3 navadne dimenzije)

\newcolumntype{A}{>{\hsize=3cm}X} za fiksno sirino prvega stolpca@tabularx

\newcolumntype{C}{>{\centering\arraybackslash}X}	za sredinsko poravnavo celice, ki se samodejno prilagaja sirini

\newcolumntype{R}{>{\raggedleft\arraybackslash}X}	za desno poravnavo celice, ki se samodejno prilagaja sirini

\newcolumntype{P}{>{\hsize=.1\hsize \centering\arraybackslash}X} za fiksno 10% fiksno sirino stolpca, ki ima sredinsko poravnavo (npr. analiza prvi, levi stolpec)

\usepackage{montserrat} pisava v novem slogu 1KA

\usepackage{wasysym} za neposredno risanje checkbox in radio button

#za prenos predolgega odgovora v novo vrstico########################
\usepackage{pgf} za aritmetiko z length

#za prenos predolgega odgovora v novo vrstico za vodoravno pod vprasanjem
				\newcommand{\isAnswerBreakPodVprasanjem}[1]{\settowidth{\answerLength}{#1} \addtolength{\questionTotalLength}{\answerLength} \ifnum\questionTotalLength>\textwidth \mbox{} \\\\ #1 \settowidth{\questionTotalLength}{0}  \else #1  \fi}

				\renewcommand{\isAnswerBreakPodVprasanjem}[1]{
						\settowidth{\answerLength}{#1} 	%dolzina odgovora
						\addtolength{\questionTotalLength}{\answerLength} %dolzini odgovora dodaj trenutni dolzini celotnega vprasanja (vrstice)
						\ifnum\questionTotalLength>\textwidth 	%ce je trenutna dolzina vprasanja vecja od sirine teksta (lista)
							\mbox{} \\\\ #1 %pejdi v novo vrstico in izpisi odgovor
							\ifnum\answerLength>\textwidth %ce je dolzina odgovora daljsa od od sirine teksta (lista)
								\pgfmathsetmacro{\ratio}{\answerLength/\textwidth} %koliko je tekst vprasanja daljsi od sirine teksta (lista)
								\pgfmathtruncatemacro{\macro}{\answerLength/\textwidth} %kaksen je ostanek, decimalke, brez celih stevilk
								\pgfmathsetmacro{\newLengthA}{\the\textwidth * (\ratio-\macro)} %dolzina vrstice, kjer se konca predolgo besedilo odgovora
								\setlength{\questionTotalLength}{\newLengthA pt} %trenutna dolzina celotnega vprasanja (vrstice) je enaka dolzini vrstice, kjer se konca predolgo besedilo odgovora
							\else %drugace, torej dolzina odgovora ni daljsa od ene vrstice
								\setlength{\questionTotalLength}{\answerLength} %trenutna dolzina celotnega vprasanja (vrstice) je enaka dolzini odgovora
							\fi 
						\else %drugace, torej trenutna dolzina vprasanja ni vecja od sirine teksta (lista)
							#1 %izpisi odgovor
						\fi
				}
#za prenos predolgega odgovora v novo vrstico za vodoravno pod vprasanjem - konec

#za prenos predolgega odgovora v novo vrstico za vodoravno ob vprasanju

#za prenos predolgega odgovora v novo vrstico za vodoravno ob vprasanju - konec

#za prenos predolgega odgovora v novo vrstico - konec########################

#za izris izbranega radio button
\newcommand{\radio}{\ooalign{\hidewidth$\bullet$\hidewidth\cr$\ocircle$}}
#za izris izbranega radio button - konec

#ureditev barve za celotno besedilo
default barva je #333 oz. RGB: 51, 51, 51
omenjeno kodo je potrebno deliti z 255, da dobimo stevilke, ki ustrezajo Latex => 51/255=0.2
\definecolor{oneclick}{rgb}{0.2, 0.2, 0.2}	- definicija barve
\color{oneclick} v preamble, torej pred \begin{document} - sprozi uporabo barve za celoten dokument
#ureditev barve za celotno besedilo - konec


*/
####################################konec

//namespace Export\Latexclasses;
//include('../../function.php');
include('../../vendor/autoload.php');
define("ENKA_LOGO_SIZE", 'width=3.51cm,height=2cm,keepaspectratio');
define("ENKA_LOGO_SIZE_HEADER", 'width=1.75cm,height=1cm,keepaspectratio');
define("SINGLE_TABLE_WIDTH", 3000);
define("PAGE_TEXT_WIDTH", 10200); //17 cm, 170 mm, je 10200 twips, 1 mm je 60 twips

#definicija za izris drsnika s kroglico
define ("circleSlider", '\def\circleSLIDER#1#2{% 1: length, 2: position of the mark (0 to 1)
						\tikz[baseline=-0.1cm]{
						 \coordinate (start) at (0,-0.1cm);
						 \coordinate (end) at (#1,0.1cm);
						 \coordinate (mark) at ($(start|-0,0)!#2!(end|-0,0)$);
						 \fill[rounded corners=0.1cm, draw=gray, fill=lightgray] (start) rectangle (end);
						 \fill[draw=gray, rounded corners=0.2mm, fill=gray!20!gray] (mark) circle(.15) ;
						}
						}');
						
#definicija za izris drsnika brez kroglice
define ("emptySlider", '\def\emptySLIDER#1{% 1: length
						\tikz[baseline=-0.1cm]{
						 \coordinate (start) at (0,-0.1cm);
						 \coordinate (end) at (#1,0.1cm);
						 \fill[rounded corners=0.1cm, draw=gray, fill=lightgray] (start) rectangle (end);
						}
						}');
						
#definicija latex kode za dodajanje skripte za generiranje xls iz html
define ("headWithXlsScript", 
	'\ifdefined\HCode
	\AtBeginDocument{%
	\Configure{@HEAD}{\HCode{<script src="./export/script/saveAsExcel2.js"></script>\Hnewline}}
	\ConfigureEnv{quote}{\Tg<quote>}{\Tg</quote>}{}{}
	}
	\fi');

class LatexDocument{
	
	var $export_type;			// Tip izvoza (vprašalnik, analize...)
	var $export_subtype;		// Podtip izvoza
	var $export_format;			// Format izvoza (latex->pdf, latex->rtf, xls...)
	
	var $anketa;				// ID ankete
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka = null;		// trenutna spremenljivka
	
	//spremenljivke za Nastavitve pdf/rtf izvozov	
	var $export_font_size = 10;
	var $export_numbering = 0;
	var $export_show_if = 0;
	var $export_show_intro = 0;
	//var $export_show_gdpr_intro = 0;
	//var $export_data_type = 0;	// nacin izpisa vprasanlnika - kratek -> 0, dolg -> 1, zelo kratek -> 2
	var $export_data_type = 0;	// nacin izpisa vprasalnika - Razsirjen -> 1, Skrcen -> 2
	var $export_data_font_size;
	var $export_data_numbering;
	var $export_data_show_recnum;
	var $export_data_show_if;
	var $export_data_PB;
	var $export_data_skip_empty;
	var $export_data_skip_empty_sub;
	var $export_data_landscape;
	//spremenljivke za Nastavitve pdf/rtf izvozov - konec
	
	var $head;	// za shrambo tex preamble in zacetek dokumenta
	var $tail;		// za shrambo tex zakljucka dokumenta
	var $naslovnicaUkaz; //za shrambo ukaza za izris naslovnice dokumenta
	
	var $headerAndFooter; //za shrambo ukaza za izris glave in noge dokumenta
	protected $surveyStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	protected $analysisStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	protected $statusStyle; //za shrambo environmenta vprasalnika (omogoca spreminjanje velikosti besedila glede na izbrano nastavitev)
	
	var $commentType = 1;	// tip izpisa komentarjev
	
	var $texNewLine = '\\\\ ';
	
	protected $isAnswer = '';
	protected $isAnswerBreakPodVprasanjem = '';
	
	protected $pathToTexFile;
	
	protected $path2Images;
	
	protected $language = -1;		// Katero verzijo prevoda izvazamo
	protected $usr_id; //id respondenta

	protected $admin_type;				   
    public $casIzvajanjaPhp = null; //Funkcija namenjena samo testiranju
	
	function __construct($anketa=null){
		global $site_path, $global_user_id, $admin_type, $lang;		
		$this->anketa = $anketa;
		$this->path2Images = $site_path.'admin/survey/export/latexclasses/textemp/images/';
		$this->admin_type = $admin_type;						  

		$this->casIzvajanjaPhp = microtime(true);
	}
	###################################### konec construct-a
	
	public function createDocument($export_type='', $export_subtype='', $export_format='', $sprID = null){
		global $lang, $site_path;
		$this->usr_id = $_GET['usr_id'];
		if($export_subtype=='heatmap_image'){	//ce je potrebno zgenerirati sliko heatmap
			$this->HeatmapImage($_GET['sprID']);
			return;
		}		
		
		$this->spremenljivka = $sprID;
		
		// Ustvarimo ogrodje dokumenta (locena funkcija), glavo, nogo, naslovnico...		
		$this->InitDocumentVars($export_type, $export_subtype, $export_format);	//pridobi vse potrebne spremenljivke za ustvarjanje ogrodja dokumenta
		
		#spremenljivke#################################################################
		$datumGeneriranjaIzvoza = date("d. m. Y");
		
		$anketaUstvarjena = SurveyInfo::getInstance()->getSurveyInsertDate();
		$dolgoImeAnkete = $this->encodeText(SurveyInfo::getSurveyColumn('akronim'));

		if($this->language!=-1){ //ce ni default jezik, ampak je prevod			
			$_lang = '_'.$this->language;
			$kratkoImeAnkete = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime'.$_lang);
		}else{
			$kratkoImeAnkete = SurveyInfo::getSurveyColumn('naslov');
		}	
		$kratkoImeAnkete = $this->encodeText($kratkoImeAnkete);

		$steviloVprasanj = SurveyInfo::getSurveyQuestionCount();		
		$anketaSpremenjena = SurveyInfo::getSurveyEditDate();		
		$avtorAnkete = SurveyInfo::getSurveyInsertName();
		$avtorSpremenilAnketo = SurveyInfo::getSurveyEditName();		
		$surveyId = SurveyInfo::getSurveyId();
		################################################
		#spremenljivke################################################################# konec

		/*echo 'export_type: '.$export_type.'</br>';
		echo 'export_subtype: '.$export_subtype.'</br>';
		echo 'export_format: '.$export_format.'</br>';
		echo 'anketaID: '.$_GET['anketa'].'</br>';
		/*echo 'sprID: '.$_GET['sprID'].'</br>';
		echo '$this->export_font_size: '.$this->export_font_size.'</br>';
		echo '$this->export_data_type: '.$this->export_data_type.'</br>';
		echo 'SurveyId: '.SurveyInfo::getSurveyId().'</br>'; */
		
		#za pridobitev jezika respondenta
		//pridobitev splosnega jezika ankete za respondenta nastavitev=> Osnovni jezik za respondente:		
		$sqlL = sisplet_query("SELECT lang_resp FROM srv_anketa WHERE id='$this->anketa' ");
		$rowL = mysqli_fetch_array($sqlL);
		$this->language = $rowL['lang_resp'];
		
		############testiranje za jezik
		if(isset($_GET['language'])){
			$this->language = $_GET['language'];
			//echo "jezik test: ".$this->language."</br>";
			// Naložimo jezikovno datoteko
			$file = '../../lang/'.$this->language.'.php';
			include($file);
			$_SESSION['langX'] = $site_url .'lang/'.$this->language.'.php';
		}
		############testiranje za jezik - konec

		if ($this->usr_id  != '') {	//ce je izpis za dolocenega respondenta
			$sqlL = sisplet_query("SELECT language FROM srv_user WHERE id = '$this->usr_id ' AND ank_id='$this->anketa' ");
			$rowL = mysqli_fetch_array($sqlL);
			$this->language = $rowL['language'];
			$lang['id'] = $this->language;
		}
		#za pridobitev jezika respondenta - konec

		//Tex preamble in zacetek latex dokumenta
		$tex = $this->head;
		
		//Dodatek h kodi, da bo črka đ vidna v pdf
		if($export_format == 'pdf'){
			$tex .= "\\fontencoding{T1}\selectfont \n";
		}
		
		if($export_format != 'xls'){	//ce ni xls
			//izris glave pa noge
			$tex .= $this->GenerateHeaderFooter($dolgoImeAnkete, $lang['page'], $datumGeneriranjaIzvoza, $export_format);

			//ce ni izpis za enega respondenta IN ni izpis analize IN ni izpis status
			if($export_subtype!='q_data' && $export_type!='analysis' && $export_type!='status' && $export_subtype!='edits_analysis' && $export_type!='gdpr'){	
				
				//Izris naslovnice				
				if($export_subtype=='q_data_all'){ //ce je izpis vseh odgovorov
					$vsiOdgovoriBesedilo = $lang['export_firstpage_results'];
				}else{
					$vsiOdgovoriBesedilo = $lang['srv_rep_vprasalnik'];
				}
				$tex .= $this->GenerateNaslovnica ($export_format, $anketaSpremenjena, $lang['export_firstpage_shortname'], $kratkoImeAnkete, $lang['export_firstpage_longname'], $lang['export_firstpage_qcount'], $steviloVprasanj, $lang['export_firstpage_author'], $avtorAnkete, $lang['export_firstpage_edit'], $avtorSpremenilAnketo, $anketaUstvarjena, $dolgoImeAnkete, $lang['export_firstpage_date'], $vsiOdgovoriBesedilo);		
				//za ureditev naslova in podnaslova na naslovnici
				//$tex .= $this->GenerateNaslovnicaNaslovi ($export_format, $anketaSpremenjena, $lang['export_firstpage_shortname'], $kratkoImeAnkete, $lang['export_firstpage_longname'], $lang['export_firstpage_qcount'], $steviloVprasanj, $lang['export_firstpage_author'], $avtorAnkete, $lang['export_firstpage_edit'], $avtorSpremenilAnketo, $anketaUstvarjena, $dolgoImeAnkete, $lang['export_firstpage_date'], $vsiOdgovoriBesedilo);		
			}
		}
			
		//zacetek izpisa ############################################################################
		$tex .= '\begin{'.$export_type.'} ';
		
		if($export_format == 'rtf'){	//ce je rtf, pred prvim vprasanjem, dodatna prazna vrstica zaradi tezav s poravnavo
			$tex .= $this->texNewLine;
		}
		
		// Glede na tip in podtip poklicemo ustrezen razred za izris vsebine porocila (npr LatexFreq, LatexTTest, ...)
		
		switch ( $export_type )
		{
			case 'survey':
				$survey = new LatexSurvey($this->anketa, $export_format, $this->export_show_intro, $this->export_show_if, $this->export_data_skip_empty, $this->export_data_skip_empty_sub);
				//$tex .= $survey->displaySurvey($export_subtype);
 				switch ( $export_subtype )
				{
					case 'q_empty':						
						$tex .= $survey->displaySurvey($export_subtype, $this->export_data_type, $this->language);
					break;
					case 'q_data':						
						$tex .= $survey->displaySurvey($export_subtype, $this->export_data_type, $this->language);
					break;
					case 'q_data_all':					
						$tex .= $survey->displayAllSurveys($export_subtype, $export_format, $this->export_data_type);
					break;
					case 'q_comment':
						$tex .= $survey->displaySurveyCommentaries($export_subtype, $this->export_data_type);
					break;
				}
			break;
			
			case 'analysis':
				$analysis = new LatexAnalysis($this->anketa, $export_format, $this->spremenljivka);				
				$tex .= $analysis->displayAnalysis($export_subtype);
			break;

			case 'status':
				$status = new LatexStatus($this->anketa);
				$tex .= $status->displayStatus();
            break;
            
			case 'gdpr':				
				$gdpr = new LatexGDPR($this->anketa);
				$tex .= $gdpr->displayGDPR($export_subtype);
			break;
			
			case 'other':
				if($export_subtype == 'edits_analysis'){
/* 					if ($_GET['seansa'] > 0){
					//if (isset ($_GET['seansa'])){
						$seansa = $_GET['seansa'];
					}else{
						$seansa = '30';
					}						
					if (isset ($_GET['time'])){
						$time = $_GET['time'];
					}else{
						$time = '1 month';
					}						
					if (isset ($_GET['status'])){
						$status = $_GET['status'];
					}else{
						$status = 0;
					}						
					if (isset ($_GET['from'])){
						$from = $_GET['from'];
					}else{
						$from = '';
					}						
					if (isset ($_GET['to'])){
						$to = $_GET['to'];
					}else{
						$to = '';
					}
					if (isset ($_GET['user'])){
						$user = $_GET['user'];
					}else{
						$user = 'all';	
					}	
					if (isset ($_GET['period'])){
						$period = $_GET['period'];
					}else{
						$period = 'day';
					}
					//print_r($_GET);
					//print_r($_POST);
					if (isset ($_GET['seansa'])){
						echo "seansa iz get: ".$_GET['seansa']."</br>";
					} */
					
					//$editAnalysis = new LatexEditsAnalysis($this->anketa, $seansa, $time, $status, $from, $to, $user, $period);
					$editAnalysis = new LatexEditsAnalysis($this->anketa);
					$tex .= $editAnalysis->displayEditAnalysis();
				}
			break;
			
			case 'data':
				echo "exporting data";
				$tex .= 'To je tip data \\\\ ';
				case 'full':					
					$tex .= 'To je podtip full';
				break;
				case 'list':					
					$tex .= 'To je podtip list';
				break;
			break;		
		}
			
		//konec izpisa######################################################################################################
		$tex .= ' \end{'.$export_type.'}';
		
		//zakljucek latex dokumenta
		$tex.= $this->tail;
		############################################################### - zakljucek latex dokumenta
		
		
		//izris latex kode

		$this->export_subtype = $export_subtype;
		$this->export_format = $export_format;
		
		# generating tex file
		$this->pathToTexFile = $site_path.'admin/survey/export/latexclasses/textemp/';
		//$filenameTex = $this->pathToTexFile.'export_'.$export_subtype.'_'.$surveyId.'_'.$export_format.'.tex';
		
		$niPrijavljenUporabnik = 0;
		if ($this->admin_type==-1) {	//ce ni prijavljen uporabnik
			$niPrijavljenUporabnik = 1; //dodaj info v imenu tex datoteke
		}		
		$filenameTex = $this->pathToTexFile.'export_'.$export_subtype.'_'.$surveyId.'_'.$export_format.'_'.$niPrijavljenUporabnik.'.tex';
		$filename = $this->pathToTexFile.'export_'.$export_subtype.'_'.$surveyId.'_'.$export_format.'_'.$niPrijavljenUporabnik;
		$fp = fopen($filenameTex, "w") or
				die ("cannot generate file $filenameTex<br>\n");
		fwrite($fp, $tex) or
				die ("cannot send data to file<br>\n");
		fclose($fp);
		# generating tex file - konec

        /*********************** TEST ********************************/
        //TODO: Samo za test briši na produkciji
        global $admin_type;
        if($admin_type == 0){
            $koncniCas = number_format((microtime(true) - $this->casIzvajanjaPhp), 2);

            $SL = new SurveyLog();
            $SL->addMessage(SurveyLog::IZVOZ, 'PHP: '.$koncniCas.'s, anketa: '.$this->anketa.', vrsta datoteke: '.$export_format.', vrsta izvoza: '.$export_subtype);
            $SL->write();

            $samoLatex = microtime(true);
        }
		/********************* END TEST ******************************/
		
		/*UREDITEV ODSTRANJEVANJA PRAZNIH VRSTIC IN CHARACTER-JEV IZ TEX DATOTEKE******************/
		$ukazOdstrani = "sed -i '/^[[:space:]]*$/d' ".$filenameTex;
		exec($ukazOdstrani);
		/*UREDITEV ODSTRANJEVANJA PRAZNIH VRSTIC IN CHARACTER-JEV IZ TEX DATOTEKE - END ******************/
		
		if($export_format == 'pdf'){
			# generating pdf output			
			$this->OutputPdf($filenameTex, $surveyId, $niPrijavljenUporabnik);
			# generating pdf output - konec
		}elseif($export_format == 'rtf'){
			# generating rtf output			
			$this->OutputRtf($filenameTex, $surveyId, $niPrijavljenUporabnik);
			# generating rtf output - konec
		}elseif($export_format == 'xls'){
			# generating html output			
			$this->OutputHtml($filenameTex, $surveyId, $filename);
			# generating html output - konec
		}

        /*********************** TEST ********************************/
        //TODO: Samo za test briši na produkciji
        if($admin_type == 0){
            $koncniCas = number_format((microtime(true) - $samoLatex), 2);

            $SL = new SurveyLog();
            $SL->addMessage(SurveyLog::IZVOZ, 'Latex: '.$koncniCas.'s, anketa: '.$this->anketa.', vrsta datoteke: '.$export_format.', vrsta izvoza: '.$export_subtype);
            $SL->write();
        }
        /********************* END TEST *****************************/

		//brisanje temp datotek tex		
		unlink($filenameTex);	//tex
		unlink($filename.".aux");	//aux
		unlink($filename.".log");	//log
		unlink($filename.".pdf");	//pdf
		unlink($filename.".out");	//out
		//brisanje temp datotek tex - konec
		
		//brisanje temp slikovnih datotek
		//$this->DeleteTmpImages($surveyId);
		//brisanje temp slikovnih datotek - konec
		
	}
	###################################### konec funkcije createDocument
	
	
	#####################################################################################################
	//Podporne funkcije za delovanje createDocument
	#####################################################################################################
	function InitDocumentVars($export_type='', $export_subtype='', $export_format='')
	{	
		global $site_path;
		global $lang;
		$baseLineSkip = intval($this->export_font_size*1.2);
		
		$this->export_type = $export_type;
		$this->export_subtype = $export_subtype;
				
		if($export_format == 'xls'){			
			//$xlsExportFilename = $export_format.'_'.$export_type.'_'.$export_subtype.'_'.$this->anketa;
			if($this->spremenljivka){
				$xlsExportFilename = $export_format.'_'.$export_type.'_'.$export_subtype.'_'.$this->anketa.'_'.$this->spremenljivka;
			}else{
				$xlsExportFilename = $export_format.'_'.$export_type.'_'.$export_subtype.'_'.$this->anketa;
			}
		 
			$button4XlsExport = '\Configure{BODY}          
		   {\SaveEndP\IgnorePar 
			   \HCode{<input type="button" value="'.$lang['srv_export_2_xls_button'].'" onclick="exportTableToExcel(\''.$xlsExportFilename.'\', \''.$this->export_subtype.'\')"/>\Hnewline
			 }\ShowPar\par}';
		 
		}
		
		//if($export_type == 'survey'||$export_type == 'analysis'){	//ce je format 'survey' ali 'analysis', potrebuje naslednje nastavitve
		if($export_type == 'survey'){	//ce je format 'survey', potrebuje naslednje nastavitve
		
			#Nastavitve pdf/rtf izvozov################################################################
			
			SurveySetting::getInstance()->Init($this->anketa);

			############testiranje za jezik
			if(isset($_GET['language'])){
				$this->language = $_GET['language'];
				//echo "jezik test: ".$this->language."</br>";
				// Naložimo jezikovno datoteko
				$file = '../../lang/'.$this->language.'.php';
				include($file);
				$_SESSION['langX'] = $site_url .'lang/'.$this->language.'.php';
			}
			############testiranje za jezik - konec
			
			//Izpis vprasalnika
			// Prikazi uvoda (default ne)
			$this->export_show_intro = SurveySetting::getInstance()->getSurveyMiscSetting('export_show_intro');
			//Ce je vprasalnik z izpisom odgovorov respondentov - $export_subtype => 'q_data' || 'q_data_all'
			if($export_subtype == 'q_data' || $export_subtype == 'q_data_all'){	// ce je subtype-a 'q_data' || 'q_data_all'
				// Tip izvoza (0->navaden-default, 1->dolg, 2->kratek) -> ne velja vec, saj sedaj sta samo dva tipa izvozov (razsirjen in skrcen)
				// Tip izvoza (1->razsirjen, 2->skrcen)
				$this->export_data_type = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_type');
				//echo "tip izvoza: ".$this->export_data_type."</br>";
				if($this->export_data_type == 0)	{
					$this->export_data_type = 1;
				}
				//$this->export_type = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_type');
				//$this->type = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_type');
				// Velikost pisave (default 10)
				//$this->export_data_font_size = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_font_size');
				$this->export_font_size = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_font_size');
				// Številčenje vprašanj (default da)
				//$this->export_data_numbering = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_numbering');
				$this->export_numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_numbering');
				// Prikaz recnuma (default da)
				//$this->export_data_show_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');
				$this->export_show_recnum = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');				
				// Prikaz pogojev (default da)
				//$this->export_data_show_if = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_if');
				$this->export_show_if = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_if');
				// Page break med posameznimi respondenti (default ne)
				$this->export_data_PB = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_PB');
				// Izpusti vprasanja brez odgovora (default ne)
				$this->export_data_skip_empty = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty');
				// Izpusti podvprasanja brez odgovora (default ne)
				$this->export_data_skip_empty_sub = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_skip_empty_sub');
				// Landscape postavitev izvoza (default ne)
				$this->export_data_landscape = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_landscape');
			}else{	//ce je prazen vprasalnik
				//$this->export_type = SurveySetting::getInstance()->getSurveyMiscSetting('export_type');
				// Prikaz pogojev (default da)
				$this->export_show_if = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_show_if');
				// Velikost pisave (default 10) - samo vprasanj
				$this->export_font_size = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_font_size');
				// Številčenje vprašanj (default da)
				$this->export_numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_numbering');
			}
			#Nastavitve pdf/rtf izvozov################################################################konec
		}
		
		//echo "export show if: ".$this->export_show_if."</br>";
		//echo "export type: ".$export_type." subtype: ".$this->export_subtype."</br>";
		#### pokomentiral, ker z uporabo template ni vec potrebno in ker po novih specifikacijah je velikost pisave in razmik med vrsticami predefiniran
/*  		if($export_type == 'analysis'||$export_type == 'status'||$export_type == 'other'){	//ce je izpis analize ALI status ALI drugo
			if($export_type == 'analysis'){
				$tableAnalysisEnvironment = '	\newenvironment{tableAnalysis}
				 {\parindent0pt \fontsize{6}{'.$baseLineSkip.'} \selectfont }
				 { }';
				if($export_format == 'xls' || $export_format == 'pdf'){
					$lTablex = '\usepackage{ltablex}';
				}				
				//$linespread = 1.15;
				$linespread = 1.5;
			//}elseif($export_type == 'status'){
			}elseif($export_type == 'status'||$export_subtype == 'edits_analysis'){
				$tableStatusEnvironment = '	\newenvironment{tableStatus}
				 {\parindent0pt \fontsize{8}{'.$baseLineSkip.'} \selectfont }
				 { }';
				//$linespread = 0.8;
				$linespread = 1;
			}
		}else{
			$tableAnalysisEnvironment = '';
			$tableStatusEnvironment = '';
			$lTablex = '';	//ce je ltablex prisoten pri survey izvozih, so tezave
			//$linespread = 1.15;
			$linespread = 1.5;
		}
		if($this->export_data_landscape==1||($export_type=='analysis'&&($this->export_subtype=='crosstab'||$this->export_subtype=='mean'||$this->export_subtype=='ttest'||$this->export_subtype=='multicrosstab')||$this->export_subtype=='break'||$this->export_subtype=='chart'||$this->export_subtype=='creport'||$this->export_subtype=='status')){	//ce je postavitev landscape ALI je izvoz analiz in (Tabele ali Povprecja ali T-test ali Multitabele ali razbitje)
			$landscapePostavitev = "landscape";
			$visinaTeksta = 210;
			$sirinaTeksta = 294;
			$hSize = '\hsize='.$sirinaTeksta.'mm';
		}else{
			$landscapePostavitev = "portrait";
			$visinaTeksta = 294;
			$hSize = '';
		} */
		#### pokomentiral, ker z uporabo template ni vec potrebno in ker po novih specifikacijah je velikost pisave in razmik med vrsticami predefiniran - konec

		if($export_format == 'pdf'){
			####################################			
			//tex template dokumenta za pdf
			if($export_type=='analysis'&&($this->export_subtype=='sums'||$this->export_subtype=='freq'||$this->export_subtype=='desc')){	//ce je analiza, kjer ni potreben landscape pogled
				$this->head = '
					\documentclass{latexTemplatePdfAnalysisPortrait}	%include datoteke s template
					\graphicspath{ {'.$site_path.'admin/survey/export/latexclasses/textemp/images/}, {'.$site_path.'uploadi/editor/}, {'.$site_path.'main/survey/uploads/}, {'.$site_path.'admin/survey/pChart/Cache/} }
					\begin{document}
				';
			//}elseif($this->export_data_landscape==1||($export_type=='analysis'&&($this->export_subtype=='crosstab'||$this->export_subtype=='mean'||$this->export_subtype=='ttest'||$this->export_subtype=='multicrosstab')||$this->export_subtype=='break'||$this->export_subtype=='chart'||$this->export_subtype=='creport'||$this->export_subtype=='status')||($export_type=='other'&&$export_subtype == 'edits_analysis')){	//ce je potreben landscape pogled
			}elseif(($export_type=='analysis'&&($this->export_subtype=='crosstab'||$this->export_subtype=='mean'||$this->export_subtype=='ttest'||$this->export_subtype=='multicrosstab')||$this->export_subtype=='break'||$this->export_subtype=='chart'||$this->export_subtype=='creport'||$this->export_subtype=='status')||($export_type=='other'&&$export_subtype == 'edits_analysis')){	//ce je potreben landscape pogled
				$this->head = '
					\documentclass{latexTemplatePdfAnalysisAndOtherLandscape}	%include datoteke s template
					\graphicspath{ {'.$site_path.'admin/survey/export/latexclasses/textemp/images/}, {'.$site_path.'uploadi/editor/}, {'.$site_path.'main/survey/uploads/}, {'.$site_path.'admin/survey/pChart/Cache/} }
					\begin{document}
				';

			}else{	//ce je vprasalnik
				$this->head = '
					\documentclass{latexTemplatePdfSurvey}	%include datoteke s template
					\graphicspath{ {'.$site_path.'admin/survey/export/latexclasses/textemp/images/}, {'.$site_path.'uploadi/editor/}, {'.$site_path.'main/survey/uploads/}, {'.$site_path.'admin/survey/pChart/Cache/} }
					'.circleSlider.'	%funkcija za izris sliderja z bunkico
					'.emptySlider.'		%funkcija za izris sliderja
					\begin{document}
				';
			}
			#################################### definiranje ukaza za glave in noge pdf - konec
			
		}else if($export_format == 'rtf'||$export_format == 'xls'){
			####################################
			//tex preamble + zacetek dokumenta za rtf
 			$this->head = '
				\documentclass[10pt]{article}
				\usepackage[a4paper, margin=20mm]{geometry}
				\usepackage[utf8]{inputenc}
				\usepackage{color}
				\usepackage{graphicx}
				\newenvironment{'.$export_type.'}
				 { }
				 { }
				\newenvironment{tableAnalysis}
				 {\parindent0pt \fontsize{6}{'.$baseLineSkip.'} \selectfont }
				 { }
				\usepackage{fancyhdr}
				\pagestyle{fancy}
			'; 

			if($export_format == 'rtf'&&$export_type=='analysis'){
				//$this->head .= '\graphicspath{ {'.$site_path.'admin/survey/export/latexclasses/textemp/images/}, {'.$site_path.'uploadi/editor/}, {'.$site_path.'main/survey/uploads/}, {'.$site_path.'admin/survey/pChart/Cache/} }';
			}
			
			if($export_format == 'xls'){
				$this->head .= '
					'.headWithXlsScript.'
					'.$button4XlsExport.'
				';
			}
			
 			$this->head .= '				
				\begin{document}
			';
			
			#################################### tex preamble + zacetek dokumenta za rtf - konec
			####################################
			//definiranje ukaza za glave in noge za rtf
			/*ima 5 vhodnih podatkov:
			1. ime ankete v glavi levo;
			2. logo 1KA v glavi na desni;
			3. besedilo "Stran" v nogi na desni;
			4. velikost logo 1KA v glavi na desni;
			5. datum generiranja izvoza v nogi na levi.
			*/
  			/* $this->headerAndFooter = '				
				\\newcommand{\headerfooter}[5]{
					\\lhead{\\includegraphics[scale=#4]{#2} #1}
					\\lfoot{www.1ka.si}
					\\rfoot{#3 \\thepage}
				}
			';	 */	
			
			if($this->usr_id){
				//echo "usr id: ".$this->usr_id;
				$recnum = $this->getRecnum();
				if($recnum && (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum')){
					$recnumBesedilo = "(Recnum $recnum)";
				}else{
					$recnumBesedilo = "";
				}
			}

			$this->headerAndFooter = '				
				\\newcommand{\headerfooter}[6]{
					\\lhead{\\includegraphics[scale=#4]{#2} #1 '.$recnumBesedilo.'}
					\\lfoot{www.1ka.si}
					\\rfoot{#3 \\thepage}
				}
			';
/* 			$this->headerAndFooter = '				
				\\newcommand{\headerfooter}[5]{
					\\lhead{#1}
					\\rhead{\\includegraphics[scale=#4]{#2}}					
					\\lfoot{www.1ka.si}
					\\rfoot{#3 \\thepage}
				}
			'; */
			#################################### definiranje ukaza za glave in noge za rtf - konec			
		}
		
		####################################
		//zakljucek dokumenta
		$this->tail='
			\end{document}
		';
		####################################konec
		
	}
	###################################### konec InitDocumentVars
	
	//Funkcija za izris glave in noge za pdf ######################################
	function GenerateHeaderFooter($imeAnkete='', $stranDokumenta=null, $datumGeneriranjaIzvoza='', $export_format=''){
		global $lang, $site_path;
		//Definiranje ukaza
		$tex = $this->headerAndFooter;	//definiranje ukaza za glavo in nogo dokumenta
		
		//izbira ustreznega logotipa za določen jezik
		if($lang['id'] == 1){	//ce je id 1, naj bo slovenski
			$logo1ka = 'logo1ka';
		}else{	//ce je bilo kateri drugi, naj bo angleski
			$logo1ka = 'logo1kaeng';
		}		
		//izbira ustreznega logotipa za določen jezik - konec
		
		if($this->usr_id){

			$recnum = $this->getRecnum();

			if($recnum && (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum') == 1){
				$recnumBesedilo = "(Recnum $recnum)";
			}else{
				$recnumBesedilo = "";
			}
		}
		
		//Izris glave in noge s predefiniranim ukazom za pdf		
		$tex .= "\headerfooter{".$imeAnkete."}{".$this->path2Images."".$logo1ka."}{".$stranDokumenta."}{".ENKA_LOGO_SIZE_HEADER."}{".$datumGeneriranjaIzvoza."}{".$recnumBesedilo."}";
		
		return $tex;
	}
	######################################

	
	//Funkcija za izpis naslovnice ######################################
	function GenerateNaslovnica($export_format='', $anketaSpremenjenaNaslovnica='', $kratkoImeAnketeBesedilo='', $kratkoImeAnkete='', $dolgoImeAnketeBesedilo='', $steviloVprasanjNaslovnicaBesedilo='', $steviloVprasanjNaslovnica='', $avtorNaslovnicaBesedilo='', $avtorNaslovnica='', $avtorSpremenilNaslovnicaBesedilo='', $avtorSpremenilNaslovnica='', $anketaUstvarjenaNaslovnica='', $imeAnkete='', $dneBesedilo='', $vsiOdgovoriBesedilo=''){
		global $lang, $site_path;
		$tex = '';

		//Aktiviranost ankete ##########################################################
		$activity = SurveyInfo::getSurveyActivity();
		$activityTex = $this->GetAktiviranostAnkete($activity);
		//Aktiviranost ankete - konec ##################################################
		
		//izbira ustreznega logotipa za določen jezik
		if($lang['id'] == 1){	//ce je id 1, naj bo slovenski
			$logo1ka = 'logo1ka';
		}else{	//ce je bilo kateri drugi, naj bo angleski
			$logo1ka = 'logo1kaeng';
		}
		//izbira ustreznega logotipa za določen jezik - konec
		//echo "stevilo spremenljivk: ".(SurveyInfo::getSurveyVariableCount());
		$steviloSpremenljivk = SurveyInfo::getSurveyVariableCount();

		
		
		if($export_format == 'pdf'){			
			####################################
			//klicanje latex funkcije za generiranje naslovnice
			$tex .= ' 
				\naslovnica
				{'.ENKA_LOGO_SIZE.', right}
				{'.$logo1ka.'}
				{'.$imeAnkete.'}
				{'.$vsiOdgovoriBesedilo.'}
				{\MakeUppercase{{'.$kratkoImeAnketeBesedilo.'}}: & {'.$kratkoImeAnkete.'}}
				{\MakeUppercase{{'.$steviloVprasanjNaslovnicaBesedilo.'}}: & {'.$steviloVprasanjNaslovnica.'} \\\\
				& \\\\
				\MakeUppercase{{'.$lang['srv_usableResp_qcount'].'}}: & {'.$steviloSpremenljivk.'}}				
				{\MakeUppercase{{'.$lang['srv_displaydata_status'].'}}: & {'.$activityTex.'}}
				{\MakeUppercase{{'.$avtorNaslovnicaBesedilo.'}}: & {'.$avtorNaslovnica.', '.$anketaUstvarjenaNaslovnica.'}}
				{\MakeUppercase{{'.$avtorSpremenilNaslovnicaBesedilo.'}}: & {'.$avtorSpremenilNaslovnica.', '.$anketaSpremenjenaNaslovnica.'}}			
			';
			#################################### //tex za pdf naslovnico - konec
		}else if($export_format == 'rtf'){
			####################################		
			//tex za rtf naslovnico
 			$tex .= '
				%\\includegraphics['.ENKA_LOGO_SIZE.', right]{'.$this->path2Images.''.$logo1ka.'} \\par
				\\vspace{4cm}
				\\noindent
				{\\huge \\textbf{\\noindent {'.$imeAnkete.'} }} \\par
				\\vspace{1cm}
				\\vspace{0.5cm}				
				\\noindent
				{\\huge \\textbf{\\noindent {'.$vsiOdgovoriBesedilo.'} }} \\par
				\\noindent							
				\\begin{tabular}{ll}				
					& \\\\
					'.$kratkoImeAnketeBesedilo.': & '.$kratkoImeAnkete.' \\\\
					& \\\\
					'.$steviloVprasanjNaslovnicaBesedilo.': & '.$steviloVprasanjNaslovnica.' \\\\
					& \\\\
					'.$lang['srv_usableResp_qcount'].': & '.$steviloSpremenljivk.' \\\\
					& \\\\
					'.$lang['srv_displaydata_status'].': & '.$activityTex.' \\\\
					& \\\\
					'.$avtorNaslovnicaBesedilo.': & '.$avtorNaslovnica.', '.$anketaUstvarjenaNaslovnica.' \\\\
					& \\\\
					'.$avtorSpremenilNaslovnicaBesedilo.': & '.$avtorSpremenilNaslovnica.', '.$anketaSpremenjenaNaslovnica.'
				\\end{tabular}
				\\newline
				\\newpage
			';
			#################################### //tex za rtf naslovnico - konec
		}	
		return $tex;
	}
	###########################################
	
	//Funkcija za pridobitev aktiviranosti ankete
	function GetAktiviranostAnkete($activity=''){
		global $lang;
		$tex = '';
		$_last_active = end($activity);
		if (SurveyInfo::getSurveyColumn('active') == 1) {	//ce je anketa aktivna	
			$tex = ''.$lang['srv_anketa_active2'].'';	//zapisi: "Anketa je aktivna"			
		}else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				$tex = ''.$lang['srv_survey_non_active_notActivated1'].'';	//zapisi: "Anketa se ni bila aktivirana"
			} else {
				# anketa je že bila aktivirana ampak je sedaj neaktivna
				$tex = ''.$lang['srv_survey_non_active1'].'';	//zapisi: "Anketa je zakljucena"
			}
		}		
		// Aktivnost	
		if( count($activity) > 0 ){
			$tex = ''.$lang['export_firstpage_active_from'].': '.SurveyInfo::getSurveyStartsDate().'';	//zapisi: "Aktivna od:"
			$tex .= ' '.$lang['export_firstpage_active_until'].': '.SurveyInfo::getInstance()->getSurveyExpireDate().' '; //zapisi: "Aktivna do:"
		}
		return $tex;
	}
	#############################################
	
	//Funkcija za generiranje in brisanje datotek za pdf izvoz
	function OutputPdf($filenameTex='', $surveyId=null, $niPrijavljenUporabnik=null){
		global $site_path;
		
		# generating pdf file	//ukaz je potrebno zagnati 2x, ker drugace ne pride do koncnega stevila strani, ki se nahaja v nogi, poleg trenutne strani
		chdir($this->pathToTexFile);
		if(IS_WINDOWS){			
			//za windows sisteme			
			$ukaz = 'latexmk -pdf '.$filenameTex;
			//$ukaz = "latexmk -silent -f -pdf -e \'$max_repeat=2\' -pdflatex=\"/usr/bin/pdflatex -interaction=batchmode \"". $filenameTex;
			exec($ukaz);
		}elseif(IS_LINUX){
			//za linux sisteme			
			exec("/usr/bin/pdflatex ".$filenameTex);
			//exec("/usr/bin/buf_size=2000000 pdflatex ".$filenameTex);

		}
		# generating pdf file - konec
		
  		$filename = 'export_'.$this->export_subtype.'_'.$surveyId.'_'.$this->export_format.'_'.$niPrijavljenUporabnik;
		$filenamePdf = $filename.'.pdf';
		$filenameAux = $filename.'.aux';
		$filenameLog = $filename.'.log';
		$filenameOut = $filename.'.out';
		$file = $this->pathToTexFile.$filenamePdf;
		
  		header('Content-type: application/pdf');
		header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
		header('Pragma: public');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');	
		//header('Content-Transfer-Encoding: binary');
		//header('Accept-Ranges: bytes');
		//header("Content-Length: " . filesize($file));
		header('Content-Disposition: inline; filename="' . $filenamePdf . '"');

		readfile($file);
		
		//brisanje temp datotek slik
		$this->DeleteChartTmpImage();
		//brisanje temp datotek slik - konec
	}
	#############################################
	
	//Funkcija, ki skrbi za brisanje tmp png datotek za izpis izvozov chart
	function DeleteChartTmpImage(){
		global $site_path;
		$path = $site_path.'admin/survey/pChart/Cache/';
		$dirList = glob($path . '*');
		foreach ($dirList as $file) {
			$fileExtArr = explode('.', $file);
			$fileExt = $fileExtArr[count($fileExtArr)-1];
			//if($fileExt == 'png'){
			if($fileExt == 'pdf'){
				unlink($file);
			}
		}
	}
	//Funkcija, ki skrbi za brisanje tmp png datotek za izpis izvozov chart - konec	
	
	//Funkcija, ki skrbi za brisanje tmp slikovnih datotek
	function DeleteTmpImages($surveyId=null){
		global $site_path;
		$path = $site_path.'uploadi/editor/';	//pot do mape s tmp slikovnimi datotekami
		
		$sqlSprem = sisplet_query("SELECT element_spr FROM srv_branching WHERE ank_id='".$surveyId."' ");
		$sqlStavek = "SELECT element_spr FROM srv_branching WHERE ank_id='".$surveyId."' ";			
		
		while ($rowSprem = mysqli_fetch_assoc($sqlSprem)){
			
			if($rowSprem['element_spr']){
				//$textTest = $path.$rowSprem['element_spr'];
				$textTest = $path.$rowSprem['element_spr']."_tmpImage";
				//echo "Funkcija DeleteTmpImages ".$textTest." </br>";
				$file2Delete = glob($textTest.'*');
				//echo count($file2Delete)."</br>";
				foreach ($file2Delete as $file) {
					//echo "Funkcija DeleteTmpImages ".$file." </br>";
					unlink($file);
				} 
			}
		}
	}
	//Funkcija, ki skrbi za brisanje tmp slikovnih datotek - konec
	
	
	
	//Funkcija za generiranje in brisanje datotek za rtf izvoz
	function OutputRtf($filenameTex='', $surveyId=null, $niPrijavljenUporabnik=null){
		global $site_path;
		
		# generating rtf file
		if(IS_WINDOWS){
			//za windows sisteme
			$latex2Rtf = 'latex2rt';
		}elseif(IS_LINUX){
			//za linux sisteme
			$latex2Rtf = 'latex2rtf';
		}
		
		$rtfGeneratingCommand = $latex2Rtf.' '.$filenameTex;
		//$rtfGeneratingCommand = $latex2Rtf.' -d 2 '.$filenameTex.' 2>latex2rtf2.log';	//ukaz, ki ustavri se log datoteko
		chdir($this->pathToTexFile);
		//exec($rtfGeneratingCommand);

		shell_exec($rtfGeneratingCommand);
		# generating rtf file - konec
		
		//$filename = 'export_'.$this->export_subtype.'_'.$surveyId.'_'.$this->export_format;
		$filename = 'export_'.$this->export_subtype.'_'.$surveyId.'_'.$this->export_format.'_'.$niPrijavljenUporabnik;
		$filenameRtf = $filename.'.rtf';	
		$file = $this->pathToTexFile.$filenameRtf;		
		//$file = $filenameRtf;
		//echo "file: ".$file;
		
		#uredi sirino stolpca tabele glede na stevilo stolpcev v tabeli neposredno v rtf ############################################
		$this->urediStolpceTabele($file);
		#uredi sirino stolpca tabele glede na stevilo stolpcev v tabeli neposredno v rtf - konec ####################################
		
		#spremeni font v Montserrat neposredno v rtf ############################################
		$this->urediFont($file);
		#spremeni font v Montserrat neposredno v rtf - konec ####################################
		
		#dodaj ustrezno barvo za crto glave in naslovnice neposredno v rtf ############################################
		$this->urediBarvoCrte($file);
		#dodaj ustrezno barvo za crto glave in naslovnice neposredno v rtf - konec ####################################
		
		#dodaj ustrezno navpicno crto pred informacijami o anketi neposredno v rtf ############################################		
		if($this->export_subtype=="q_empty"||$this->export_subtype=="q_data_all"){
			$this->dodajCrtoPred($file);
		}
		#dodaj ustrezno navpicno crto pred informacijami o anketi neposredno v rtf - konec ####################################		
		
		#dodaj ustrezno navpicno crto v glavi dokumenta neposredno v rtf ############################################
		$this->dodajCrtoGlava($file);
		#dodaj ustrezno navpicno crto v glavi dokumenta neposredno v rtf - konec ####################################		
		
		#dodaj ustrezno navpicno crto v nogi dokumenta neposredno v rtf ############################################
		$this->dodajCrtoNoga($file);
		#dodaj ustrezno navpicno crto v nogi dokumenta neposredno v rtf - konec ####################################

		#header-ji rtf datoteke
   	 	header('Content-type: application/rtf');		
		header('Content-Transfer-Encoding: binary');
		//header('Content-Length: ' . filesize($file));
		header('Content-Disposition: inline; filename="' . $filenameRtf . '"');
		//header('Content-Disposition: attachment;filename="'.basename($filename).'"');
		#header-ji rtf datoteke - konec
		
		#Stara varianta
		//readfile($file);
		#Stara varianta - konec
		
		#nova varianta
		set_time_limit(0);
		$chunksize = 2 * (1024 * 1024); //5 MB (= 5 242 880 bytes) per one chunk of file.
		//$fileD = @fopen($file,"r");
		$fileD = @fopen($file,"rb");
		while(!feof($fileD))
		{
			print(@fread($fileD, $chunksize));
			ob_flush();
			flush();
		}
		#nova varianta - konec
		
		//brisanje temp datotek
		unlink($file);	//rtf
		//brisanje temp datotek - konec
	}
	#############################################	
	
	//Funkcija za generiranje html kode
	function OutputHtml($filenameTex='', $surveyId=null, $filename=''){
		global $site_path;
		//echo "filename: ".$filename." ";
 		
		# generating html file
		$htmlGeneratingCommand = 'htlatex '.$filenameTex;		
		//$htmlGeneratingCommand = 'htlatex '.$filenameTex.' hello';
		chdir($this->pathToTexFile);
		exec($htmlGeneratingCommand);	//5 kratna ponovitev ukaza, da se \multicolumn latex koda lahko prenese pravilno v colspan HTML
		exec($htmlGeneratingCommand);
		exec($htmlGeneratingCommand);
		exec($htmlGeneratingCommand);
		exec($htmlGeneratingCommand);
		# generating html file - konec
		
		echo file_get_contents($filename.'.html');	//odpri in pokazi html izvoz z gumbom za izvoz iz html v xls
		
		$filenameCss = $filename.'.css';
		$filenameHtml = $filename.'.html';
		$filenameIdv = $filename.'.idv';
		$filenameLg = $filename.'.lg';
		$filenameTmp = $filename.'.tmp';
		$filename4tc = $filename.'.4tc';
		$filenameAux = $filename.'.aux';
		$filenameDvi = $filename.'.dvi';
		$filenameLog = $filename.'.log';
		$filenameXref = $filename.'.xref';
		$filename4ct = $filename.'.4ct';
				
		//brisanje temp datotek
 		unlink($filenameCss);	//css
 		unlink($filenameHtml);	//html
 		unlink($filenameIdv);	//idv
 		unlink($filenameLg);	//lg
 		unlink($filenameTmp);	//tmp
 		unlink($filename4tc);	//4tc
 		unlink($filenameAux);	//Aux
 		unlink($filenameDvi);	//Dvi
		unlink($filenameLog);	//log
		unlink($filenameXref);	//Xref
		unlink($filename4ct);	//4ct		
		//brisanje temp datotek - konec
	}
	#############################################
	
	
	####################################################################################
	//Funkcija, ki skrbi za urejanje sirine stolpca tabele glede na stevilo stolpcev v tabeli
	function urediStolpceTabele($file=null){
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$pos = 0;	//belezi pozicijo cellx kode v rtf
		$posRowB = 0;	//belezi pozicijo \\trowd kode v rtf, zacetek vrstice tabele
		$posRowEnd = 0;		//belezi pozicijo \\row kode v rtf, zakljucek vrstice tabele
		$findB = 'trowd';	//rtf koda za zacetek tabele
		$findCellx = 'cellx';	//rtrf koda za ureditev sirine celice
		$findRow = '\row';
		$numOfRowOccurrences = substr_count ($rtfCode, $findB);	//belezi stevilo najdenih "trowd" v rtf kodi, stevilo vrstic v tabeli
				
  		if($numOfRowOccurrences){	//ce se pojavi kaksna vrstica v tabeli
		
			for($i=0;$i<$numOfRowOccurrences;$i++){	//preleti vsako vrstico tabele
				$posRowB = strpos($rtfCode, '\trowd', $posRowB+1);		//belezi pozicijo zacetka kode za vrstico v tabeli				
				$posRowEnd = strpos($rtfCode, $findRow, $posRowEnd+1);	//belezi pozicijo konca kode za vrstico v tabeli
				$posRowEnd = $posRowEnd + strlen($findRow);
							
				$substringVrstice = substr($rtfCode, $posRowB, (($posRowEnd-$posRowB)+1)); //belezi kodo celotne vrstice v tabeli
				
				$lengthSubstringVrstice = strlen($substringVrstice); //dolzina trenutnega substring-a s katero se bo odstranilo staro kodo

				$numOfCellxOccurrences = substr_count ($substringVrstice, $findCellx);	//belezi stevilo najdenih "cellx" v rtf kodi za eno vrstico tabele
				$pos = 0;
				$posB = 0;
				//echo "trowd: ".$posRowB."</br>";
				//echo "numOfCellxOccurrences: ".$numOfCellxOccurrences."</br>";
								
				if($numOfCellxOccurrences>2){	//ce imamo vec kot 2 stolpca, prilagodi sirino stolpca glede na stevilo stolpcev
					for($j=1;$j<=$numOfCellxOccurrences;$j++){
						$width = round( $j*PAGE_TEXT_WIDTH/($numOfCellxOccurrences) );
						//echo "substringVrstice: ".$substringVrstice."</br></br>";
						
						$posB = strpos($substringVrstice, 'cellx', $posB+1); //pozicija zacetka cellx kode, ki jo je potrebno nadomestiti
						$posE = strpos($substringVrstice, "\\", $posB+1);	//pozicija konca cellx kode, ki jo je potrebno nadomestiti

						$cellXString = substr($substringVrstice, $posB, (($posE-$posB)));	//trenutna cellx koda s sirino stolpca
						//echo $cellXString."</br>";
						$lastCellx = substr_count ($cellXString, '{');	//belezi stevilo najdenih "cellx" v rtf kodi za eno vrstico tabele
						if($lastCellx){							
							$posSymbol = strpos($cellXString, '{', 0);
							$cellXString = substr($cellXString,0,$posSymbol);
							//echo $cellXString."</br>";
						}						
						//echo $cellXString."</br>";
						$replace = 'cellx'.$width;	//nadomestna cellx koda s prilagojeno sirino glede na stevilo stolpcev
						
						$substringVrstice = substr_replace($substringVrstice,'',$posB,strlen($cellXString));	//izbrisi trenutno kodo za cellx
						
						$substringVrstice = substr_replace($substringVrstice,$replace,$posB,0);	//nadomesti z novo kodo s posodobljeno sirino stolpca za cellx
						
					}
					//echo "</br> substringVrstice changed: ".$substringVrstice."</br></br></br>";
					
					//iz trenutne rtf kode odstrani del s starim substring-om
					$rtfCode = substr_replace($rtfCode,'',$posRowB, $lengthSubstringVrstice);
					
					//na mestu starega substring dodaj spremenjenega
					$rtfCode = substr_replace($rtfCode,$substringVrstice,$posRowB,0);
					
				}elseif($numOfCellxOccurrences==1){	//ce je samo ena tabela (Izberite s seznama, Povleci-spusti, ...)					
					//echo "substringVrstice: ".$substringVrstice."</br></br>";
					
					$posB = strpos($substringVrstice, 'cellx', $posB+1); //pozicija zacetka cellx kode, ki jo je potrebno nadomestiti
					$posE = strpos($substringVrstice, "\\", $posB+1);	//pozicija konca cellx kode, ki jo je potrebno nadomestiti

					$cellXString = substr($substringVrstice, $posB, (($posE-$posB)));	//trenutna cellx koda s sirino stolpca
					//echo $cellXString."</br>";
					$lastCellx = substr_count ($cellXString, '{');	//belezi stevilo najdenih "cellx" v rtf kodi za eno vrstico tabele
					if($lastCellx){							
						$posSymbol = strpos($cellXString, '{', 0);
						$cellXString = substr($cellXString,0,$posSymbol);
						//echo $cellXString."</br>";
					}						
					//echo $cellXString."</br>";
					//$replace = 'cellx'.$width;	//nadomestna cellx koda s prilagojeno sirino glede na stevilo stolpcev
					$replace = 'cellx'.SINGLE_TABLE_WIDTH;	//nadomestna cellx koda s prilagojeno sirino glede na stevilo stolpcev
					
					$substringVrstice = substr_replace($substringVrstice,'',$posB,strlen($cellXString));	//izbrisi trenutno kodo za cellx
					
					$substringVrstice = substr_replace($substringVrstice,$replace,$posB,0);	//nadomesti z novo kodo s posodobljeno sirino stolpca za cellx
					
					//iz trenutne rtf kode odstrani del s starim substring-om
					$rtfCode = substr_replace($rtfCode,'',$posRowB, $lengthSubstringVrstice);
					
					//na mestu starega substring dodaj spremenjenega
					$rtfCode = substr_replace($rtfCode,$substringVrstice,$posRowB,0);
				}
			}
		}		

   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	
	//Funkcija, ki skrbi za urejanje sirine stolpca tabele glede na stevilo stolpcev v tabeli - konec
	###################################################################################################	
	
	####################################################################################
	//Funkcija, ki skrbi za spremembo fonta rtf dokumenta neposredno v rtf
	function urediFont($file=null){
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$posOrigFont = 0;	//belezi pozicijo imena fonta "Times New Roman" v rtf
		$origFont = 'Times New Roman';	//belezi ime fonta, ki ga zelimo zamenjati
		$lenOrigFont = strlen($origFont);		
		$newFont = 'Montserrat';
		
		$posOrigFont = strpos($rtfCode, $origFont);
		//echo "posOrigFont: ".$posOrigFont."</br>";
		//echo "lenOrigFont: ".$lenOrigFont."</br>";
		
		//iz trenutne rtf kode odstrani del s starim substring-om
		$rtfCode = substr_replace($rtfCode,'', $posOrigFont, $lenOrigFont);
		
		//na mestu starega substring dodaj spremenjenega
		$rtfCode = substr_replace($rtfCode, $newFont, $posOrigFont, 0);
		
//		echo "rtfCode: </br>";
//		echo $rtfCode;
	
   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	//Funkcija, ki skrbi za spremembo fonta rtf dokumenta neposredno v rtf - konec
	###################################################################################################		
	
	####################################################################################
	//Funkcija, ki skrbi za dodajanje ustrezne barve navpicne crte neposredno v rtf
	function urediBarvoCrte($file=null){
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$posColorTbl = 0;	//belezi pozicijo besedila "\colortbl;" v rtf
		$textColorTbl = '\colortbl;';	//belezi besedilo, ki iscemo
		$lenColorTbl = strlen($textColorTbl);	//dolzina besedila "\colortbl;", po kateri je potrebno dodati novo barvo
		
		$newColor = '\red30\green136\blue229;';
		
		$posColorTbl = strpos($rtfCode, $textColorTbl);
		$posNewColor = $posColorTbl + $lenColorTbl;	//hrani pozicijo nove barve
		
		//na ustreznem mestu dodaj novo barvo - substr_replace(string,replacement,start,length)
		$rtfCode = substr_replace($rtfCode, $newColor, $posNewColor, 0);
	
   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	//Funkcija, ki skrbi za dodajanje ustrezne barve navpicne crte neposredno v rtf - konec
	###################################################################################################		
	
	####################################################################################
	//Funkcija, ki skrbi za dodajanje navpicne crte pred informacijami o anketi neposredno v rtf
	function dodajCrtoPred($file=null){
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$textPar = '{\pard\plain\s0\qj\widctlpar\f0\fs20\sl240\slmult1 \fi0 \par'; //hrani besedilo za zacetek naslednjega odstavka pred katerim se mora nahajati crta
		$posBesedila = 0;	//dodal definicijo pred klicem strpos, ker je javilo napako
		$posPar = strpos($rtfCode, $textPar, $posBesedila); //pozicija zacetka naslednjega odstavka pred katerim se mora nahajati crta - //strpos(string,find,start)
		
		$textCrta = '\pard \brdrb \brdrs\brdrw120\brsp20\brdrcf1 {\fs4\~}\par \pard';	//hrani besedilo za izris crte zelene debeline (120) in barve (brdrcf1), ki je potrebno dodati rtf kodi	
		$posCrta = $posPar;	//hrani pozicijo kode z zeleno crto
		
		//na ustreznem mestu dodaj zeleno crto - substr_replace(string,replacement,start,length)
		$rtfCode = substr_replace($rtfCode, $textCrta, $posCrta, 0);
			
   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	//Funkcija, ki skrbi za dodajanje navpicne crte pred informacijami o anketi neposredno v rtf - konec
	###################################################################################################
		
	###################################################################################
	//Funkcija, ki skrbi za dodajanje crte v glavi neposredno v rtf
	function dodajCrtoGlava($file=null){
		global $lang;
		
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$findHeaderStart = '\header';	//hrani besedilo zacetka glave, ki jo je potrebno najti
		$posHeaderStart = strrpos($rtfCode, $findHeaderStart); //pozicija besedila za zacetek glave dokumenta - //strrpos(string,find)	
		
		$findPicStart = '\pict';	//hrani besedilo zacetka slike v glavi, kjer je potrebno dodati crto
		$posPicStart = strpos($rtfCode, $findPicStart, $posHeaderStart); //pozicija besedila zacetka slike - //strpos(string,find,start)
		
		$findPicEnd = '}';	//hrani besedilo konca slike v glavi
		$posPicEnd = strpos($rtfCode, $findPicEnd, $posPicStart); //pozicija besedila konca slike v glavi dokumenta - //strpos(string,find,start)
		
		$findTitleEnd = '\tab'; //hrani besedilo konca naslova vprasalnika v glavi, kjer je potrebno dodati crto
		$posTitleEnd = strpos($rtfCode, $findTitleEnd, $posPicEnd); //pozicija besedila konca naslova vprasalnika v glavi dokumenta - //strpos(string,find,start)

		$textCrta = '\pard \brdrb \brdrs\brdrw120\brsp20\brdrcf1 {\fs4\~}\par \pard';	//hrani besedilo za izris crte zelene debeline (120) in barve (brdrcf1), ki je potrebno dodati rtf kodi	
		$posCrta = $posTitleEnd+strlen($findTitleEnd);	//hrani pozicijo kode z zeleno crto
		
		//na ustreznem mestu dodaj zeleno crto - substr_replace(string,replacement,start,length)
		$rtfCode = substr_replace($rtfCode, $textCrta, $posCrta, 0);

   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	//Funkcija, ki skrbi za dodajanje crte v glavi neposredno v rtf
	###################################################################################################
	
	
	####################################################################################
	//Funkcija, ki skrbi za dodajanje crte v nogi neposredno v rtf
	function dodajCrtoNoga($file=null){
		global $lang;
		
		$rtfCode = file_get_contents($file);	//string z generirano rtf kodo
		
		$findFooterStart = '\footer';	//hrani besedilo zacetka noge
		$posFooterStart = strrpos($rtfCode, $findFooterStart); //pozicija besedila za zacetek noge dokumenta - //strrpos(string,find,start) - najde zadnje besedilo v kodi
		$lenFooterStart = strlen($findFooterStart);	//dolzina besedila "\footer", po kateri je potrebno dodati kodo za crto v nogi

		$textCrta = '\pard \brdrb \brdrs\brdrw10\brsp20\brdrcf2 {\fs4\~}\par \pard';	//hrani besedilo za izris crte zelene debeline (10) in barve (brdrcf2), ki je potrebno dodati rtf kodi	
		$posCrta = $posFooterStart + $lenFooterStart;	//hrani pozicijo kode z zeleno crto
		
		//na ustreznem mestu dodaj zeleno crto - substr_replace(string,replacement,start,length)
		$rtfCode = substr_replace($rtfCode, $textCrta, $posCrta, 0);

   		file_put_contents($file, $rtfCode);	//prenesi preurejeno kodo v obstojeco rtf datoteko
	}
	//Funkcija, ki skrbi za dodajanje crte v nogi neposredno v rtf - konec
	###################################################################################################
	
	function HeatmapImage($sprId=null){
		$imageFileName = 'heatmap'.$sprId.'.png';
		//echo "imageFileName: ".$imageFileName."</br>";
		global $site_path;
		global $site_url;
		//echo '<img alt="" src="'.$site_url.'main/survey/uploads/'.$fileName.'"></br>';
		$src = $site_url.'main/survey/uploads/'.$imageFileName;
		$image = imagecreatefrompng($src);
		
		imagealphablending($image, false);
		imagesavealpha($image, true);
				
		header('Content-Disposition: Attachment;filename='.$imageFileName.';filename*=utf8'.$imageFileName);
		header('Content-Type: image/png');
		//header('Content-Type: image/png; charset=utf-8');
		//header('Content-Type: application/force-download');

		imagepng($image);
		imagedestroy($image);
	}

	function getRecnum(){
		$izbranStatusProfile = SurveyStatusProfiles :: getStatusAsQueryString();
		$sqluString = "SELECT id, last_status, lurker, recnum FROM srv_user WHERE ank_id = '".$this->anketa."' ".$izbranStatusProfile." AND deleted='0' AND preview='0' AND id='".$this->usr_id."' ORDER BY recnum";
		//echo $sqluString;
		$sqlu = sisplet_query($sqluString);		
		$rowu = mysqli_fetch_array($sqlu);
		$recnum = $rowu['recnum'];
		return $recnum;
	}

	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno
	function encodeText($text=''){
		global $site_path, $lang;
		//$text = str_replace(' ','X',$text);	//nadomesti presledke
		//echo "Encoding ".$text."</br>";
		
		$this->path2UploadedImages = $site_path.'uploadi/editor/';
		if($text == ''){	//ce ni teksta, vrni se
			return;			
		}
			
		//ureditev posebnih karakterjev za Latex	http://www.cespedes.org/blog/85/how-to-escape-latex-special-characters, https://en.wikibooks.org/wiki/LaTeX/Special_Characters#Other_symbols
		$text = str_replace('\\','\textbackslash{} ',$text);
		//$text = str_replace('{','\{',$text);		
		//$text = str_replace('}','\}',$text);	
		$text = str_replace('$','\$ ',$text);
		$text = str_replace('#','\# ',$text);
		$text = str_replace('%','\% ',$text);		
		$text = str_replace('€','\euro',$text);		
		$text = str_replace('^','\textasciicircum{} ',$text);		
		$text = str_replace('_','\_ ',$text);	
		$text = str_replace('~','\textasciitilde{} ',$text);
		if(strpos($text, '&amp;')){	//ce je prisotno v besedilu &amp;'
			$text = str_replace('&amp;','\& ',$text);
		}else{
			$text = str_replace('&','\& ',$text);
		}
		$text = str_replace('&nbsp;','~',$text);
		//$text = str_replace('&lt;','\textless ',$text);
		$text = str_replace('&lt;',' \textless ',$text);
		//$text = str_replace('&gt;','\textgreater ',$text);
		$text = str_replace('&gt;',' \textgreater ',$text);
		//ureditev posebnih karakterjev za Latex - konec

		//ureditev grskih crk
		$text = str_replace('α','\textalpha ',$text);
		$text = str_replace('β','\textbeta ',$text);
		$text = str_replace('γ','\textgamma ',$text);
		$text = str_replace('δ','\textdelta ',$text);
		$text = str_replace('ε','\textepsilon ',$text);
		$text = str_replace('ζ','\textzeta ',$text);
		$text = str_replace('η','\texteta ',$text);
		$text = str_replace('θ','\texttheta ',$text);
		$text = str_replace('ι','\textiota ',$text);
		$text = str_replace('κ','\textkappa ',$text);
		$text = str_replace('λ','\textlambda ',$text);
		$text = str_replace('μ','\textmugreek ',$text);
		$text = str_replace('ν','\textnu ',$text);
		$text = str_replace('ξ','\textxi ',$text);
		//$text = str_replace('ο','\textomikron ',$text);
		$text = str_replace('π','\textpi ',$text);
		$text = str_replace('ρ','\textrho ',$text);
		$text = str_replace('σ','\textsigma ',$text);
		$text = str_replace('τ','\texttau ',$text);
		$text = str_replace('υ','\textupsilon ',$text);
		$text = str_replace('φ','\textphi ',$text);
		$text = str_replace('χ','\textchi ',$text);
		$text = str_replace('ψ','\textpsi ',$text);
		$text = str_replace('ω','\textomega ',$text);
		//ureditev grskih crk - konec


		//RESEVANJE BESEDILA V CIRILICI
		$contains_cyrillic = (bool) preg_match('/[\p{Cyrillic}]/u', $text);	//ali je v besedilu cirilica?		
		if($contains_cyrillic){	// ce je cirilica v besedilu
			$text = '\foreignlanguage{russian}{'.$text.'}';
		}
		//RESEVANJE BESEDILA V CIRILICI - konec
		
		return $text;
	}
	#funkcija ki skrbi za encode dolocenih spornih delov besedila v latex-u prijazno - konec
}