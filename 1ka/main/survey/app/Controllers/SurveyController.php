<?php
/***************************************
 * Description: Glavni razred pri main survey, ki skrbi za klice vseh ostalih razredov, ki nato sestavijo prikaz ankete
 * Autor: Robert Šmalc
 * Created date: 22.01.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\InitClass as Init;
use App\Controllers\LanguageController as Language;
use SurveyMissingValues;
use SurveySetting;

class SurveyController extends Controller
{
    private $printPreviewSet;

    public function __construct($printPreviewSet = false)
    {
        // Pridobimo vse globalne spremenljivke
        parent::getGlobalVariables();

        // Pridobimo spremenljivke za Header
        $this->getHeader();

        // Shranimo pvrednost predogleda
        save('printPreview', $printPreviewSet);

        if (isset($this->get->pages) && $this->get->pages == 'all')
            save('displayAllPages', true);

        save('mobile', Helper::mobile());

        // Pridobimo datoteko za jezike
        Language::getLanguageFile();

        // Ali imamo perdogled že rešene ankete
        if (isset($this->get->quick_view) && $this->get->quick_view == 1)
            save('quick_view', true);

        // Če je arhivirano pošiljanje emailov
        if (isset($this->get->ai) && (int)$this->get->ai > 0)
            save('user_inv_archive', (int)$this->get->ai);

        // Poiščemo missing vrednosti ankete smv
        save('smv', new SurveyMissingValues(get('anketa')));

        // Če imamo izklopljeno mobilno prilagajanje, potem ignoriramo mobitele in vedno prikazemo vse enako
        SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_friendly = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_friendly');
        if ($mobile_friendly == 0)
            save('mobile', 0);

        // Inicializacija vsega
        new Init();
    }

    /************************************************
     * Poberemo spremenljivke iz get requestov in piškotkov
     ************************************************/
    public function getHeader()
    {
        $header = new Header();

        // Pridobimo vse GET parametre
        $this->get = $header->getAllUrlParameters();

        //Pridobimo vse $_COOKIE paramletre
        $this->cookie = $header->getAllCookieParameters();
    }


}