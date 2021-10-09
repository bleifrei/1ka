<?php
	# ali je OS windows ali linux
	define('IS_WINDOWS', (DIRECTORY_SEPARATOR === '\\') ? TRUE : FALSE);
	define('IS_LINUX', (DIRECTORY_SEPARATOR === '\\') ? FALSE : TRUE);

	# NASTAVITVE ANKETE
	define('ANKETA_NASLOV_MAXLENGTH', '40');		# KOLIKO ZNAKOV LAHKO VSEBUJE INTERNO IME ANKETE
	define('ANKETA_AKRONIM_MAXLENGTH', '100');	# KOLIKO ZNAKOV LAHKO VSEBUJE AKRONIM ANKETE
	define('ANKETA_NOTE_MAXLENGTH', '250');		# KOLIKO ZNAKOV LAHKO VSEBUJE OPIS ANKETE

	# za url-je za navigacijo
	define('NEW_LINE', "\n");

	##### NAVIGACIJA ####
    define("NAVI_STATUS", "NAVI_STATUS");
    define("NAVI_STATUS_OSNOVNI", "NAVI_STATUS_OSNOVNI");
    define("NAVI_STATUS_TRAJANJE", "NAVI_STATUS_TRAJANJE");
    define("NAVI_AAPOR", "AAPOR");
    define("NAVI_UREJANJE", "NAVI_UREJANJE");
    define("NAVI_TESTIRANJE", "NAVI_TESTIRANJE");
    define("NAVI_TESTIRANJE_KOMENTARJI", "NAVI_TESTIRANJE_KOMENTARJI");
    define("NAVI_TESTIRANJE_PREDVIDENI", "NAVI_TESTIRANJE_PREDVIDENI");
    define("NAVI_TESTIRANJE_CAS", "NAVI_TESTIRANJE_CAS");
    define("NAVI_TESTIRANJE_VNOSI", "NAVI_TESTIRANJE_VNOSI");
    define("NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY", "NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY");
    define("NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY_OLD", "NAVI_TESTIRANJE_LANGUAGE_TECHNOLOGY_OLD");
    define("NAVI_UREJANJE_BRANCHING", "NAVI_UREJANJE_BRANCHING");
    define("NAVI_UREJANJE_ANKETA", "NAVI_UREJANJE_ANKETA");
    define("NAVI_UREJANJE_TEMA", "NAVI_UREJANJE_TEMA");
    define("NAVI_UREJANJE_ALERT", "NAVI_UREJANJE_ALERT");
    define("NAVI_UREJANJE_TESTIRANJE", "NAVI_UREJANJE_TESTIRANJE");
    define("NAVI_TESTIRANJE_KOMENTARJI_ANKETA", "NAVI_TESTIRANJE_KOMENTARJI_ANKETA");
    define("NAVI_UREJANJE_PREVAJANJE", "NAVI_UREJANJE_PREVAJANJE");
    define("NAVI_OBJAVA", "NAVI_OBJAVA");
    define("NAVI_ANALYSIS", "NAVI_ANALYSIS");
    define("NAVI_RESULTS", "NAVI_RESULTS");
    define("NAVI_ADVANCED", "NAVI_ADVANCED");
    define("NAVI_UPORABNOST", "NAVI_UPORABNOST");
    define("NAVI_HIERARHIJA_SUPERADMIN", "NAVI_HIERARHIJA_SUPERADMIN");
    define("NAVI_HIERARHIJA", "NAVI_HIERARHIJA");
    define("NAVI_KVIZ", "NAVI_KVIZ");
    define("NAVI_VOTING", "NAVI_VOTING");
    define("NAVI_VNOS", "NAVI_VNOS");
    define("NAVI_PHONE", "NAVI_PHONE");
    define("NAVI_360", "NAVI_360");
    define("NAVI_SOCIAL_NETWORK", "NAVI_SOCIAL_NETWORK");
    define("NAVI_SLIDESHOW", "NAVI_SLIDESHOW");
    define("NAVI_STATISTIC_ANALYSIS", "NAVI_STATISTIC_ANALYSIS");
    define("NAVI_ANALYSIS_LINKS", "NAVI_ANALYSIS_LINKS");
    define("NAVI_ANALYSIS_TIMES", "NAVI_ANALYSIS_TIMES");
    define("NAVI_DATA", "NAVI_DATA");
    define("NAVI_DATA_EXPORT", "NAVI_DATA_EXPORT");


	# Dashboard - status - report
	define("A_REPORTI", "reporti");
	define("A_NONRESPONSE_GRAPH", "nonresponse_graph");
	define("A_PARA_GRAPH", "para_graph");
	define("A_USABLE_RESP", "usable_resp");
	define("A_KAKOVOST_RESP", "kakovost_resp");
	define("A_SPEEDER_INDEX", "speeder_index");
	define("A_TEXT_ANALYSIS", "text_analysis");
	define("A_GEOIP_LOCATION", "geoip_location");
    define("A_EDITS_ANALYSIS", "edits_analysis");
	define("A_UL_EVALVATION", "ul_evalvation");
	define("A_REMINDER_TRACKING", "reminder_tracking");
	define("A_REMINDER_TRACKING_RECNUM", "recnum");
	define("A_REMINDER_TRACKING_VAR", "vars");

	#urejanje
	define("A_BRANCHING", "branching");
	define("A_SETTINGS", "nastavitve");
	define("A_NAGOVORI", "nagovori");
	define("A_ALERT", "alert");
	define("A_TESTIRANJE", "testiranje");
	define("A_ARHIVI", "arhivi");
	define("A_TRACKING", "tracking");
    define("A_TRACKING_HIERARHIJA", "tracking-hierarhija");
	define("A_GLASOVANJE", "glasovanja");

	# TESTIRANJE
	define('M_TESTIRANJE_REVIEW', 'pregled');
	define('M_TESTIRANJE_DIAGNOSTIKA', 'diagnostika');
	define('M_TESTIRANJE_KOMENTARJI', 'komentarji');
	define('M_TESTIRANJE_KOMENTARJI_ANKETA', 'komentarji_anketa');
	define('M_TESTIRANJE_VNOSI', 'testnipodatki');
    define('M_TESTIRANJE_TRAJANJE', 'trajanje');
	define('M_TESTIRANJE_PREDVIDENI', 'predvidenicas');
	define("M_TESTIRANJE_CAS", "cas");

	# objava
	define('A_VABILA', 'vabila');
	define('A_EMAIL', 'email');
	define('A_INVITATIONS', 'invitations');
	define('M_INVITATIONS_STATUS', 'inv_status');
	define('M_INVITATIONS_SETTINGS', 'inv_settings');
	define('M_INVITATIONS', 'vabila');

	# TELEFON
	define('A_TELEPHONE', 'telephone');

	# CHAT
	define('A_CHAT', 'chat');
	
	# PANEL
	define('A_PANEL', 'panel');

	# FIELDWORK (tablice, notebooki)
	define('A_FIELDWORK', 'fieldwork');
        
        # Mobilna aplikacija za anketirance
	define('A_MAZA', 'maza');
        
        # Web push notifications
	define('A_WPN', 'wpn');
        
	# 360 STOPINJ
	define('A_360', '360_stopinj');
	define('A_360_1KA', '360_stopinj_1ka');

	# SA- HIERARHIJA
	define('A_HIERARHIJA', 'hierarhija'); #izgradnja hierarhije
	define('M_ADMIN_UREDI_SIFRANTE', 'uredi-sifrante'); #hierarhija - uredi šifrante za kasnejšo izgradno
	define('M_ADMIN_UVOZ_SIFRANTOV', 'uvoz-sifrantov'); #hierarhija - uvoz sifrantov
    define('M_ADMIN_UPLOAD_LOGO', 'upload-logo'); #hierarhija - upload logo
	define('M_ADMIN_IZVOZ_SIFRANTOV', 'izvoz-sifrantov'); #hierarhija - izvoz sifrantov
	define('M_UREDI_UPORABNIKE', 'uredi-uporabnike'); #hierarhija - uredi uporabnike
    define('M_ADMIN_AKTIVACIJA', 'aktivacija-strukture-ankete'); #aktivacija hierarhije
    define('M_ADMIN_KOPIRANJE', 'kopiranje-strukture-in-uporabnikov'); #kopiranje hierarhije
	define('M_ANALIZE', 'analize'); #hierarhija analize
	define('M_HIERARHIJA_STATUS', 'status'); #hierarhija - statusi


	# REZULTATI
	#analize
	define('A_ANALYSIS', 'analysis');
	define('M_ANALYSIS_DESCRIPTOR', 'descriptor');
	define('M_ANALYSIS_FREQUENCY', 'frequency');
	define('M_ANALYSIS_SUMMARY', 'sumarnik');
	define('M_ANALYSIS_SUMMARY_NEW', 'sums_new');
	define('M_ANALYSIS_CROSSTAB', 'crosstabs');
	define("M_ANALYSIS_MULTICROSSTABS", "multicrosstabs");
	define('M_ANALYSIS_MEANS', 'means');
	define('M_ANALYSIS_MEANS_HIERARHY', 'hierarhy-means');
	define('M_ANALYSIS_TTEST', 'ttest');
	define('M_ANALYSIS_BREAK', 'break');
	define('M_ANALYSIS_STATISTICS', 'statistics');
	define('M_ANALYSIS_ARCHIVE', 'anal_arch');
	define("M_ANALYSIS_LINKS", "analysis_links");
	define("M_ANALYSIS_CREPORT", "analysis_creport");
	define("M_ANALYSIS_CHARTS", "charts");
	define("M_ANALYSIS_PARA", "para");
	define("M_ANALYSIS_NONRESPONSES", "nonresponses");
	define("M_ANALYSIS_VIZUALIZACIJA", "vizualizacija");
	define("M_ANALYSIS_360", "360_stopinj");
	define("M_ANALYSIS_360_1KA", "360_stopinj_1ka");
	define('M_ANALYSIS_HEATMAP', 'heatmap');
	# vnosi - zbiranje podatkov
	define('A_COLLECT_DATA', 'data');
	define('M_COLLECT_DATA_VIEW', 'view');
	define('M_COLLECT_DATA_VARIABLE_VIEW', 'variables');
	define('M_COLLECT_DATA_EDIT', 'edit');
	define('M_COLLECT_DATA_QUICKEDIT', 'quick_edit');
	define('M_COLLECT_DATA_MONITORING', 'monitoring');
	define('M_COLLECT_DATA_PRINT', 'print');
	define('M_COLLECT_DATA_CALCULATION', 'calculation');
	define('M_COLLECT_DATA_CODING', 'coding');
	define('M_COLLECT_DATA_RECODING', 'recoding');
	define('M_COLLECT_DATA_RECODING_DASHBOARD', 'recoding_dashboard');
	define('A_COLLECT_DATA_EXPORT', 'export');
	define('A_COLLECT_DATA_EXPORT_ALL', 'export_PDF');
	define('M_EXPORT_EXCEL', 'excel');
	define('M_EXPORT_EXCEL_XLS', 'excel_xls');
	define('M_EXPORT_SPSS', 'spss');
	define('M_EXPORT_SAV', 'sav');
	define('M_EXPORT_TXT', 'txt');

	# dodatne nastavitve
	define('A_ADVANCED', 'advanced');
	define('A_UPORABNOST', 'uporabnost');
	define('A_HIERARHIJA_SUPERADMIN', 'hierarhija_superadmin');
	define('A_KVIZ', 'kviz');
	define('A_VOTING', 'voting');
	define('A_VNOS', 'vnos');
	define('A_PHONE', 'telefon'); # Telefon
	define('T_PHONE', 'telefon'); # Telefon
	define('A_SOCIAL_NETWORK', 'social_network');
	define('A_SLIDESHOW', 'slideshow');
	define('A_ADVANCED_PARADATA', 'advanced_paradata');
	define('A_JSON_SURVEY_EXPORT', 'json_survey_export');


	# primerno redirektamo klik na link anketo (dashboard .vs. urejanje)
	define("A_REDIRECTLINK", "redirectLink");

	define("A_QUICK_SETTINGS", "quicksettings");

	# za tretji nivo navigacije
	define("A_OSNOVNI_PODATKI", "osn_pod"); # urejanje ankete - osnovni podatki
	define('A_MISSING', 'missing');	# urejanje ankete - manjkajoče vrednosti
	define('A_TEMA', 'tema');	# urejanje ankete - manjkajoče vrednosti
	define('A_COOKIE', 'piskot');	# urejanje ankete - manjkajoče vrednosti
	define("A_KOMENTARJI", "komentarji"); # urejanje ankete - komentarjivrednosti
	define("A_KOMENTARJI_ANKETA", "komentarji_anketa"); # urejanje ankete - komentarjivrednosti
	define("A_TRAJANJE", "trajanje"); # urejanje ankete - komentarjivrednosti
	define("A_TRAJANJE_PREDVIDENI", "predvidenicas"); # urejanje ankete - komentarjivrednosti
	define("A_TRAJANJE_CAS", "cas"); # urejanje ankete - komentarjivrednosti
	define('A_UREJANJE', 'urejanje');	# urejanje ankete - komentarjivrednosti
	define('A_DOSTOP', 'dostop');	# urejanje ankete - manjkajoče vrednosti
	define('A_JEZIK', 'jezik');	# urejanje ankete - manjkajoče vrednosti
	define('A_PREVAJANJE', 'prevajanje');	# urejanje ankete - manjkajoče vrednosti
	define('A_FORMA', 'forma');	# urejanje ankete - manjkajoče vrednosti
	define('A_METADATA', 'metadata');	# urejanje ankete - prikaz metapodatkov
	define('A_MOBILESETTINGS', 'mobile_settings');	# urejanje ankete - nastavitve prikaza pri mobitelih
	define('A_PRIKAZ', 'prikaz');	# prikaz podatkov in analiz
	define('A_MAILING', 'advanced_email');	# nastavitve email strežnika
	define('A_SKUPINE', 'skupine');	# skupine
	define('A_EXPORTSETTINGS', 'export_settings');	# nastavitve pdf/rtf izvozov
	define('A_GDPR', 'gdpr_settings');	# GDPR nastavitve posamezne ankete
    define('A_LANGUAGE_TECHNOLOGY', 'language_technology');    # skupine
	define('A_LANGUAGE_TECHNOLOGY_OLD', 'language_technology_old');	# skupine

	##### NAVIGACIJA ####

	# profili mankjajočih vrednosti
	define('MISSING_TYPE_SUMMARY', '0');
	define('MISSING_TYPE_DESCRIPTOR', '1');
	define('MISSING_TYPE_FREQUENCY', '2');
	define('MISSING_TYPE_CROSSTAB', '3');

	# za vnose ali analize in kreacijo datotek
	define('EXPORT_FOLDER', 'admin/survey/SurveyData');
	define('VALID_USER_LIMIT_STRING', ' AND u.last_status IN (5,6) ');
	define('ALLOW_CREATE_LIMIT', 80);		        # prvih 100 userjev vedno spustimo skozi
	define('AUTO_CREATE_LIMIT', 150);			    # Koliko je meja, ko ne prikazujemo progresbara, in avtomatsko skreiramo datoteko ON THE FLY
	define('AUTO_CREATE_TIME_LIMIT', 10);		    # Na koliko sekund pustimo da se generira inkrementalno s progressbarom
	define('AUTO_CREATE_PREVENT_LIMIT', 1000);	# Koliko je meja, ko avtomatsko sploh ne generiramo datoteke s podatki
	define('ONLY_VALID_LIMIT', 3000);		        # nad koliko respondentov lovimo samo ustrezne
	define('MAX_USER_PER_LOOP', 250);
	define('FILE_STATUS_OK', '1');			    # datoteka je ažurna
	define('FILE_STATUS_OLD', '0');		        # datoteka je stara
	define('FILE_STATUS_NO_FILE', '-1');	        # datoteka ne obstaja
	define('FILE_STATUS_NO_DATA', '-2');	        # v bazi nipodatkov
	define('FILE_STATUS_SRV_DELETED', '-3');	    # Anketa je bila izbrisana

	define('INCREMENTAL_LOCK_TIMEOUT', 10);	    # po kolikem času tajmoutamo možnost ponovnega generiranja (10min)

	define("SYSTEM_VARIABLES", serialize (array('geslo','email','telefon','ime','priimek','naziv','drugo','odnos')));

	# fiksna polja v tabeli s podatki (prvo je 0)
	# polja po vrsti :
	# - user_id (1)
	# - datum odgovora(2)
	# - ustreznost (3)
	# - email	(4)
	# - status	(5)
	# - lurker (6)
	# - time insered (unix) (7)
	# - record_number (8)
	define('USER_ID_FIELD', '$1');
	define('RELEVANCE_FIELD', '$2');
	define('EMAIL_FIELD', '$3');
	define('STATUS_FIELD', '$4');
	define('LURKER_FIELD', '$5');
	define('TIME_FIELD', '$6');
	define('MOD_REC_FIELD', '$7');
	define('ITIME_FIELD', '$8');

	define('SCP_DEFAULT_PROFILE', 1);
	define('SSP_DEFAULT_PROFILE', 2);			#ustrezni

	define('PERMANENT_DATE', '2099-01-01');	# Kateri datum velja kot datum trajne ankete

	# privzete nastavitve analiz
	define('NUM_DIGIT_PERCENT', 0);						# stevilo digitalnih mest za odstotek
	define('NUM_DIGIT_AVERAGE', 1);						# stevilo digitalnih mest za povprecje
	define('NUM_DIGIT_DEVIATION', 2);					# stevilo digitalnih mest za odklon
	define('NUM_DIGIT_RESIDUAL', 3);					# stevilo digitalnih mest za residuale
	define('NUM_DIGIT_PERCENT_MAX', 6);						# max stevilo digitalnih mest za odstotek
	define('NUM_DIGIT_AVERAGE_MAX', 6);						# max stevilo digitalnih mest za povprecje
	define('NUM_DIGIT_DEVIATION_MAX', 6);					# max stevilo digitalnih mest za odklon
	define('NUM_DIGIT_RESIDUAL_MAX', 6);					# max stevilo digitalnih mest za residual
	
	define('TEXT_ANSWER_LIMIT', 100);					# max stevilo text odgovorov pri izvozih


	define('SURVEY_LIST_DATE_FORMAT', '%d.%m.%y');					# max stevilo digitalnih mest za residual

	define('STP_DATE_FORMAT', 'd.m.Y');				# format v katerem operiramo v tem klasu
	define('STP_OUTPUT_DATE_FORMAT', 'Y-m-d'); 		# format v katerem vrne
	define('STP_CALENDAR_DATE_FORMAT', '%d.%m.%Y');	# format prikaza koledarja
	define('STP_DATE_FORMAT_SHORT', 'j.n.y');
	define('STP_TIME_FORMAT_SHORT', 'G:i');

	define('SDS_DEFAULT_PROFILE', 0);

	define ('STR_OTHER_TEXT', '_text');
	define ('STR_DLMT', "|");
	define ('DAT_EXT', '.dat');
	define ('TMP_EXT', '.tmp');
	define ('PIPE_CHAR', '\x7C');
	define ('STR_LESS_THEN', '\x3C');
	define ('STR_GREATER_THEN', '\x3E');
	define ('STR_EQUALS', '\x3D');


?>