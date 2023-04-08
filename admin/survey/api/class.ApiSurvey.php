<?php

/**
 *
 * 	Class ki vsebuje funkcije APIJA (branje iz ankete, ustvarjanje ankete, pisanje v anketo...)
 *
 */
class ApiSurvey {

    function __construct() {
        global $site_url;
        global $global_user_id;
    }

	
    // Izvedemo akcijo
    public function executeAction($params, $data) {
        global $site_url;
        global $global_user_id;
        global $lang;

        $json_array = array();

        // Preverimo ce ima user dostop do ankete
        $survey_access = false;

        if (isset($params['ank_id'])) {
            Common::getInstance()->Init($params['ank_id']);

            $d = new Dostop();
            if ($d->checkDostopSub('edit', $params['ank_id'])) {
                $survey_access = true;
            }

            $lang_admin = $this->getLang($params['ank_id']);
        } else {
            $survey_access = true;
            $lang_admin = $this->getLang(0);
        }

        //include right language
        $file = '../../../lang/' . $lang_admin . '.php';
        include($file);

        if (!$survey_access) {
            $json_array['error'] = 'User does not have access to this survey';
        } elseif (!isset($params['action'])) {
            $json_array['error'] = 'Action is not defined';
        } else {
            /*
             * $kategorija - uporabljeno za kategoriziranje akcij za api (mobile app)
             * 0 - default - app level - splosne akcije, ki se jih naceloma ne da kategorizirat
             *      npr. seznami anket, sprememba jezika,...
             * 1 - kreiranje in urejanje anket - akcije, za katere je potreben tool za kreiranje anket
             *      npr. sprememba kategorije, sprememba uvoda, vrstni red vprasanj, brisanje vprasanja... 
             * 2 - pregled ankete - vse, kar se dela z anketami, ko so ze zbirajo ali so ze zbrani podatki
             *      npr. status, dashboard, urejanje hashlink, rezultati,...
             * 3 - nastavitve ankete - vse nastavitve ankete, za katera se ne potrebuje tool za kreiranje anket
             *      npr. kopiranje ankete, aktivacija/deaktivacija, brisanje ankete, blokiranje ip 24ur,...
             */
            $kategorija = 0;

            Common::start();

            switch ($params['action']) {

                // BRANJE
                case 'getSurveyList':
                        $json_array = $this->getSurveyList(isset($params['limit']) ? $params['limit'] : ''
                                , isset($params['mobile_created']) ? $params['mobile_created'] : -1);
                    break;
                    
                case 'getSurveyInfo':
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyInfo($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyQuestions':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyQuestions($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurvey':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurvey($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyStatuses':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyStatuses($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;
                    
                case 'getSurveyAnswerState':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyAnswerState($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyDateTimeRange':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyDateTimeRange($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyRedirections':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyRedirections($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyParadata':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyParadata($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyDashboard':
                    $kategorija = 2;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->getSurveyDashboard($params['ank_id']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyFrequencies':
                    $kategorija = 2;
                    if (isset($params['ank_id']))
                        $json_array = $this->getSurveyFrequencies($params['ank_id']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyHashes':
                    $kategorija = 2;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->getSurveyHashes($params['ank_id']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'getSurveyResponses':
                    $json_array = $this->getSurveyResponses($data);
                    break;
					
                case 'getSurveyResponseData':
                        $kategorija = 2;
                        $usr_param = (isset($params['usr_param'])) ? $params['usr_param'] : '';
                        if (isset($params['ank_id']) && isset($params['usr_id']))
                            $json_array = $this->getSurveyResponseData($params['ank_id'], $params['usr_id'], $usr_param);
                    else
                        $json_array['error'] = 'Survey ID or respondent ID missing';
                break;	
					

                // PISANJE
                case 'createSurvey':
                    $kategorija = 1;
                    $json_array = $this->createSurvey($data);
                    break;

                case 'deleteSurvey':
                    $kategorija = 3;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->deleteSurvey($params['ank_id']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'deleteQuestion':
                    $kategorija = 1;
                    if (isset($params['ank_id']) && isset($data['que_id'])) {
                        $json_array = $this->deleteQuestion($params['ank_id'], $data['que_id']);
                    } else
                        $json_array['error'] = 'Survey or question ID missing';
                    break;

                case 'createQuestion':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->createQuestion($params['ank_id'], $data['question']);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'updateQuestion':
                    $kategorija = 1;
                    if (isset($params['ank_id']) && isset($data['question']['id_que']))
                        $json_array = $this->updateQuestion($params['ank_id'], $data['question']);
                    else
                        $json_array['error'] = 'Survey or question ID missing';
                    break;

                case 'copySurvey':
                    $kategorija = 3;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->copySurvey($params['ank_id']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'copyQuestion':
                    $kategorija = 1;
                    if (isset($params['ank_id']) && isset($data['que_id'])) {
                        $json_array = $this->copyQuestion($params['ank_id'], $data['que_id']);
                    } else
                        $json_array['error'] = 'Survey or question ID missing';
                    break;

                case 'updateOrCreateOption':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->updateOrCreateOption($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'deleteOption':
                    $kategorija = 1;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->deleteOption($params['ank_id'], $data['option_id']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'updateSurvey':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->updateSurvey($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                //rather use updateOrCreateOption
                case 'addQuestionVrednost':
                    $kategorija = 1;
                    if (isset($params['ank_id']) && isset($params['spr_id'])){
                        if($this->isQuestionSiblingOfSurvey($params['ank_id'], $params['spr_id']))
                            $json_array = $this->addQuestionVrednost($params['ank_id'], $params['spr_id'], $data);
                        else 
                            $json_array['error'] = "Question does not exist or does not belong to this survey";
                    }
                    else
                        $json_array['error'] = 'Survey ID or question ID missing';
                    break;

                case 'SurveyActivation':
                    $kategorija = 3;
                    if (isset($params['ank_id']))
                        $json_array = $this->SurveyActivation($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'BlockRepeatedIP':
                    $kategorija = 3;
                    if (isset($params['ank_id']))
                        $json_array = $this->BlockRepeatedIP($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'deleteLink':
                    $kategorija = 2;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->deleteLink($params['ank_id'], $data['hash']);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'addLink':
                    $kategorija = 2;
                    if (isset($params['ank_id'])) {
                        $json_array = $this->addLink($params['ank_id'], $data);
                    } else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'sendEmailInvitation':
                    if (isset($params['ank_id']))
                        $json_array = $this->sendEmailInvitation($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'addGroup':
                    $kategorija = 1;
                    if (isset($params['ank_id']))
                        $json_array = $this->addGroup($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;
                    
                case 'languageChange':
                    if (isset($data['lang'])) {
                        //change user interface language (interface settings)
                        sisplet_query("UPDATE users SET lang = '" . $data['lang'] . "' WHERE id = '$global_user_id'");
                        $json_array['note'] = 'Language changed';
                    } else
                        $json_array['error'] = 'Language code missing';
                    break;
                    
                case 'deleteSurveyUnit':
                    if (isset($params['ank_id']) && isset($data['srv_unit_id'])) {
                        $json_array = $this->deleteSurveyUnit($params['ank_id'], $data['srv_unit_id']);
                    } else
                        $json_array['error'] = 'Survey ID or/and unit ID is missing';
                    break;
					
				// EVOLI modul	
                case 'addGroupTeamMeter':
                    if (isset($params['ank_id']))
                        $json_array = $this->addGroupTeamMeter($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'sendEmailInvitationTeamMeter':
                    if (isset($params['ank_id']))
                        $json_array = $this->sendEmailInvitationTeamMeter($params['ank_id'], $data);
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;

                case 'createEvoliPass':
                    if (isset($params['ank_id']) && isset($params['email']))
                        $json_array = $this->createEvoliPass($params['ank_id'], $params['email']);
                    else
                        $json_array['error'] = 'Survey ID or customer email missing';
                    break;

                case 'getEvoliPass':
                    if (isset($params['ank_id']) && isset($params['email']))
                        $json_array = $this->getEvoliPass($params['ank_id'], $params['email']);
                    else
                        $json_array['error'] = 'Survey ID or customer email missing';
                    break;
				

				// GORENJE modul
				case 'createUser':
					// Zaenkrat se lahko dodaja uporabnike samo na gorenje instalaciji (narejeno posebej za njih)
					if(Common::checkModule('gorenje'))
						$json_array = $this->createUser($data);
				break;	


                // MOBILE APP ONLY
                case 'getMobileAppVersion':
                    $json_array = $this->getMobileAppVersion();
                    break;

                case 'updateQuestionOrder':
                    $kategorija = 1;
                    if (isset($params['ank_id'])){
                        $sm = new SurveyMobile();
                        $json_array = $sm->updateQuestionOrder($params['ank_id'], $data);
                    }
                    else
                        $json_array['error'] = 'Survey ID missing';
                    break;
					
                // MAZA APP only
                case 'mazaUpdateDeviceInfo':
                    if (isset($data['deviceInfo'])) {
                        $sm = new SurveyMobile();
                        $json_array = $sm->mazaUpdateDeviceInfo($data['deviceInfo']);
                    } else
                        $json_array['error'] = 'Param deviceInfo missing';
                    break;

                case 'mazaInsertTrackingLocations':
                    if (isset($data['locations']) && !empty($data['locations'])) {
                        $sm = new SurveyMobile();
                        $sm->mazaInsertTrackingLocations($data['locations']);
                        $json_array['note'] = 'Locations inserted';
                    }
                    if (isset($data['activity_recognition']) && !empty($data['activity_recognition'])) {
                        $sm = new SurveyMobile();
                        $sm->mazaInsertTrackingAR($data['activity_recognition']);
                        $json_array['note'] .= ' AR inserted';
                    }
                    if (isset($data['edit_locations']) && !empty($data['edit_locations'])) {
                        $sm = new SurveyMobile();
                        $sm->mazaEditTrackingLocations($data['edit_locations']);
                        $json_array['note'] .= ' locations edited';
                    }
                    break;
                    
                case 'mazaUpdateTrackingLog':
                    if (isset($data['trackingLog'])) {
                        $sm = new SurveyMobile();
                        $sm->mazaUpdateTrackingLog($data['trackingLog']);
                        $json_array['note'] = 'Tracking log updated';
                    } else
                        $json_array['error'] = 'Param trackingLog missing';
                    break;
                    
                case 'mazaInsertTriggeredGeofences':
                    if (isset($data['triggeredGeofences'])) {
                        $sm = new SurveyMobile();
                        $new_tgeof_id = $sm->mazaInsertTriggeredGeofences($data['triggeredGeofences']);
                        if($new_tgeof_id != 0)
                            $json_array['tgeof_id'] = $new_tgeof_id;
                        $json_array['note'] = 'Geofences updated';
                    } else
                        $json_array['error'] = 'Param triggeredGeofences missing';
                    break;

                case 'mazaUpdateRegistrationId':
                    if (isset($data['registration_id'])) {
                        sisplet_query("UPDATE maza_app_users SET registration_id = '" . $data['registration_id'] . "' WHERE id = '$global_user_id'");
                        $json_array['note'] = 'Registration ID info updated';
                    } else
                        $json_array['error'] = 'Param registration_id missing';
                    break;

                case 'mazaGetAlarms':
                    $sm = new SurveyMobile();
                    $json_array['alarms'] = $sm -> mazaGetAlarms();
                    break;
                
                case 'mazaGetGeofences':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaGetGeofences();
                    break;
                
                case 'mazaGetActivities':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaGetActivities();
                    break;
                
                case 'mazaGetTracking':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaGetTracking();
                    break;
                
                case 'mazaGetEntries':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaGetEntries();
                    break;
                
                case 'mazaGetMyLocations':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaGetMyLocations();
                    break;
                
                case 'mazaSetNextpinTrackingPermission':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaSetNextpinTrackingPermission($data);
                    break;
                
                case 'mazaSetTrackingPermission':
                    $sm = new SurveyMobile();
                    $json_array = $sm -> mazaSetTrackingPermission($data);
                    break;
                
                case 'mazaUnsubscribeSurvey':
                    $sm = new SurveyMobile();
                    if(isset($data['ank_id']))
                        $json_array = $sm -> mazaUnsubscribeSurvey($data['ank_id']);
                    else
                        $json_array['error'] = "Param ank_id missing";
                    break;
                
                case 'mazaGetSurveyList':
                    if(isset($data['timeZone'])){
                        $sm = new SurveyMobile();
                        $json_array = array_values($sm->mazaGetSurveyList($data['timeZone'], $data['srv_id']));
                    } else
                        $json_array['error'] = 'Param timeZone missing';
                    break;
                    
                case 'mazaGetSubscriptionsList':
                    if(isset($data['timeZone'])){
                        $sm = new SurveyMobile();
                        $json_array = $sm->mazaGetSubscriptionsList($data['timeZone']);
                    } else
                        $json_array['error'] = 'Param timeZone missing';
                    break;
                    
                case 'mazaMergeIdentifier':
                    if(isset($data['identifierToMerge'])){
                        $sm = new SurveyMobile();
                        $json_array = $sm->mazaMergeIdentifier($data['identifierToMerge']);
                    } else
                        $json_array['error'] = 'Param identifierToMerge missing';
                    break;
                    
                case 'mazaGetSurveysInfoByIdentifier':
                    if(isset($data['identifierToMerge'])){
                        $sm = new SurveyMobile();
                        $json_array = $sm->mazaGetSurveysInfoByIdentifier($data['identifierToMerge']);
                    } else
                        $json_array['error'] = 'Param identifierToMerge missing';
                    break;
                    
                case 'mazaDeleteSurveyUnit':
                    if (isset($data['ank_id']) && isset($data['srv_unit_id'])) {
                        $json_array = $this->deleteSurveyUnit($data['ank_id'], $data['srv_unit_id']);
                    } else
                        $json_array['error'] = 'Survey ID or/and unit ID is missing';
                    break;
                    
                // WPN - web push notifications
                case 'wpnAddSubscription':
                    if (isset($data['endpoint']) && isset($data['keys'])) {
                        $wpn = new WPN();
                        $json_array = $wpn->ajax_wpn_save_subscription($data);
                    } else
                        $json_array['error'] = 'Params missing';
                    break;
            }

            Common::stop();

            //zaenkrat spremljamo samo mobile app, brez preverbe logina
            if ($params['identifier'] == 'mobileApp' && $params['action'] != 'getMobileAppVersion')
                $this->tracking_api($params['ank_id'], $global_user_id, $params['action'], $kategorija);
        }

        $response = json_encode($json_array, true);
        echo $response;
    }

    /**
     * Saves log of api functions usage tracking
     * @param type $ank_id - survey ID
     * @param type $user - user ID
     * @param type $action - function
     * @param type $kategorija - category of function
     */
    private function tracking_api($ank_id, $user, $action, $kategorija) {
        $ank_id = $ank_id != null ? $ank_id : 0;
        sisplet_query("INSERT INTO srv_tracking_api (ank_id, datetime, ip, user, action, kategorija) VALUES ('$ank_id', NOW(), '" . GetIP() . "', '$user', '$action', '$kategorija')");
    }
    
    /**
     * @api {get} https://www.1ka.si/api/getSurveyHashes/survey/:id getSurveyHashes
     * @apiName getSurveyHashes
     * @apiGroup Data and analysis
     * @apiDescription Get all hash links of survey. Example of hash (public) link: https://www.1ka.si/podatki/50/5BABEC6D/ ([SITE_ROOT]/podatki/[SURVEY_ID]/[HASH_CODE]/)
     * 
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess (Main Fields) {String} hash Hash code for link
     * @apiSuccess (Main Fields) {String} comment Comment of hash link
     * @apiSuccess (Main Fields) {String} refresh 0-refresh mode off, 1-auto refresh site every x seconds
     * @apiSuccess (Main Fields) {String} access_password If not NULL or "", this password is needed to access public link
     * @apiSuccess (Main Fields) {String} page Broad type of content of hash link (analysis, data)
     * @apiSuccess (Main Fields) {String} add_date Date of creation
     * @apiSuccess (Main Fields) {String} add_time Time of creation
     * @apiSuccess (Main Fields) {String} email Email of author
     * @apiSuccess (Main Fields) {Object} properties Properties of hash link
     * @apiSuccess (Hash link Fields) {String} anketa ID of survey that hash link belong to
     * @apiSuccess (Hash link Fields) {String} a Broad type of content of hash link (analysis, data)
     * @apiSuccess (Hash link Fields) {String} m Specific type of content of hash link (analysis_creport, descriptor, frequency, charts, sumarnik)
     * @apiSuccess (Hash link Fields) {String} profile_id_status 
     * @apiSuccess (Hash link Fields) {String} profile_id_variable 
     * @apiSuccess (Hash link Fields) {String} profile_id_condition 
     * 
     * @apiSuccessExample {json} Success-Response:
     *  [{
                "hash": "179A60BA",
                "properties": {
                        "anketa": "50",
                        "a": "analysis",
                        "m": "frequency",
                        "profile_id_status": 2,
                        "profile_id_variable": 0,
                        "profile_id_condition": 1
                },
                "comment": "Frequencies",
                "refresh": "0",
                "access_password": "",
                "page": "analysis",
                "add_date": "17.05.2019",
                "add_time": "12:38",
                "email": "admin"
        }, {
                "hash": "F3FB9720",
                "properties": {
                        "anketa": "50",
                        "a": "analysis",
                        "m": "charts",
                        "profile_id_status": 2,
                        "profile_id_variable": 0,
                        "profile_id_condition": 1
                },
                "comment": "Charts",
                "refresh": "0",
                "access_password": "",
                "page": "analysis",
                "add_date": "17.05.2019",
                "add_time": "12:37",
                "email": "admin"
        }, {
                "hash": "2D704440",
                "properties": {
                        "anketa": "50",
                        "a": "data",
                        "m": "",
                        "profile_id_status": 2,
                        "profile_id_variable": 0,
                        "profile_id_condition": 1
                },
                "comment": "",
                "refresh": "0",
                "access_password": null,
                "page": "data",
                "add_date": "17.05.2019",
                "add_time": "12:37",
                "email": "admin"
        }, {
                "hash": "7A96B2C7",
                "properties": {
                        "anketa": "50",
                        "a": "analysis",
                        "m": "sumarnik",
                        "profile_id_status": 2,
                        "profile_id_variable": 0,
                        "profile_id_condition": 1
                },
                "comment": "Summary",
                "refresh": "0",
                "access_password": "",
                "page": "analysis",
                "add_date": "17.05.2019",
                "add_time": "12:36",
                "email": "admin"
        }]
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyHashes($ank_id){
            $hashUrl = new HashUrl($ank_id);
            return $hashUrl->getSurveyHashes();
    }
	
    /**
     * @api {get} https://www.1ka.si/api/getSurveyQuestions/survey/:id getSurveyQuestions
     * @apiName getSurveyQuestions
     * @apiGroup Questions and variables
     * @apiDescription Get info of all questions of survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess (Main Fields) {String} id Id of question
     * @apiSuccess (Main Fields) {String} tip Type of question (verbal)
     * @apiSuccess (Main Fields) {String} naslov Title of question
     * @apiSuccess (Main Fields) {String} info Additional information of question (e.g. "Multiple answers are possible")
     * @apiSuccess (Main Fields) {String} variable Short mark of question (question name)
     * @apiSuccess (Main Fields) {String} stran_id Id of page
     * @apiSuccess (Main Fields) {String} stran_naslov Title of page
     * @apiSuccess (Main Fields) {String} vrstni_red Sequence number of the question
     * @apiSuccess (Main Fields) {Object} vrednosti Values o questions (possible answers)
     * @apiSuccess (Value Fields) {String} id Id of value in question
     * @apiSuccess (Value Fields) {String} naslov Title of value in question
     * @apiSuccess (Value Fields) {String} variable Short mark of value in question (value name)
     * @apiSuccess (Value Fields) {String} vrstni_red Sequence number of value in the question
     * @apiSuccessExample {json} Success-Response:
     *     {"1234":{
     *          "id":"1234",
     *          "tip":"One answer",
     *          "naslov":"Question tittle 1",
     *          "info":"",
     *          "variable":"Q1",
     *          "stran_id":"2890",
     *          "stran_naslov":"Page 1",
     *          "vrstni_red":"1",
     *          "vrednosti":{
     *              "48495":{
     *                  "id":"48495",
     *                  "naslov":"Write text 1",
     *                  "variable":"1",
     *                  "vrstni_red":"1"},
     *               "48496":{
     *                  "id":"48496",
     *                  "naslov":"Write text 2",
     *                  "variable":"2",
     *                  "vrstni_red":"2"}
     *          }
     *      }},
     *      {"1235"...
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyQuestions($ank_id) {
        global $lang;
        global $global_user_id;

        $json_array = array();

        // Napolnimo podatke o vseh vprasanjih v anketi
        $sql = sisplet_query("SELECT s.id, s.tip, s.naslov, s.info, s.variable, s.gru_id, s.vrstni_red, g.naslov as gru_naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$ank_id' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
        while ($row = mysqli_fetch_assoc($sql)) {

            // Vrednosti v vprasanju
            $vrednosti = array();
            $sqlV = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_vrednost WHERE spr_id='" . $row['id'] . "' ORDER BY vrstni_red ASC");
            while ($rowV = mysqli_fetch_assoc($sqlV)) {
                $vrednosti[$rowV['id']] = array(
                    'id' => $rowV['id'],
                    'naslov' => $rowV['naslov'],
                    'variable' => $rowV['variable'],
                    'vrstni_red' => $rowV['vrstni_red']
                );
            }

            $json_array[$row['id']] = array(
                'id' => $row['id'],
                'tip' => $lang['srv_vprasanje_tip_' . $row['tip']],
                'naslov' => $row['naslov'],
                'info' => $row['info'],
                'variable' => $row['variable'],
                'stran_id' => $row['gru_id'],
                'stran_naslov' => $row['gru_naslov'],
                'vrstni_red' => $row['vrstni_red'],
                'vrednosti' => $vrednosti
            );
        }

        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurvey/survey/:id getSurvey
     * @apiName getSurvey
     * @apiGroup Surveys
     * @apiDescription Get info of survey and its questions
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess (Main Fields) {String} link Link of survey
     * @apiSuccess (Main Fields) {String} title Title of survey
     * @apiSuccess (Main Fields) {String} intro Introduction text ("" means default)
     * @apiSuccess (Main Fields) {String} concl Conclusion text ("" means default)
     * @apiSuccess (Main Fields) {String} show_intro Hide or show introduction (0-hide, 1-show)
     * @apiSuccess (Main Fields) {String} show_concl Hide or show conclusion (0-hide, 1-show)
     * @apiSuccess (Main Fields) {String} page_id ID of last page in survey
     * @apiSuccess (Main Fields) {Object[]} questions Array of all questions in survey
     * @apiSuccess (Question Fields) {String} id Id of question in survey
     * @apiSuccess (Question Fields) {String} type Type in text of question in survey
     * @apiSuccess (Question Fields) {String} type_code Type in code of question in survey
     * @apiSuccess (Question Fields) {String} title Title/text of question in survey
     * @apiSuccess (Question Fields) {String} info Additional information of question (e.g. "Multiple answers are possible")
     * @apiSuccess (Question Fields) {String} variable Short mark of question in survey (question name)
     * @apiSuccess (Question Fields) {String} page_id ID of page that question is at
     * @apiSuccess (Question Fields) {String} page_title Name/text of page that question is at
     * @apiSuccess (Question Fields) {String} reminder Reminder of question (0-no reminder, 1-soft reminder, 2-hard reminder)
     * @apiSuccess (Question Fields) {String} order Sequence number of question in page
     * @apiSuccess (Question Fields) {String} params Additional params as string for question
     * @apiSuccess (Question Fields) {Object[]} options Array of options/answers/values of question 
     * @apiSuccess (Value Fields) {String} id Id of value in question
     * @apiSuccess (Value Fields) {String} title Title of value in question
     * @apiSuccess (Value Fields) {String} variable Short mark of value in question (value name)
     * @apiSuccess (Value Fields) {String} other Is this value other (0-basic, 1-other)
     * @apiSuccess (Value Fields) {String} order Sequence number of value in the question
     * 
     * @apiSuccessExample {json} Success-Response:
     *  {
            "link": "http:\/\/192.168.0.101\/1ka\/a\/109",
            "title": "A survey",
            "intro": "",
            "concl": "",
            "show_intro": "1",
            "show_concl": "1",
            "page_id": "135",
            "questions": [{
                    "id": "487",
                    "type": "Single answer",
                    "type_code": "1",
                    "title": "City",
                    "info": "",
                    "variable": "Q1",
                    "page_id": "134",
                    "page_title": "Stran 1",
                    "reminder": "0",
                    "orientation": "1",
                    "order": "1",
                    "params": [],
                    "options": [{
                            "id": "1438",
                            "title": "Ljubljana",
                            "variable": "1",
                            "other": "0",
                            "order": "1"
                    }, {
                            "id": "1439",
                            "title": "Berlin",
                            "variable": "2",
                            "other": "0",
                            "order": "2"
                    }, {
                            "id": "1440",
                            "title": "London",
                            "variable": "3",
                            "other": "0",
                            "order": "3"
                    }, {
                            "id": "1445",
                            "title": "Other:",
                            "variable": "4",
                            "other": "1",
                            "order": "4"
                    }]
            }, {
                    "id": "488",
                    "type": "Multiple answer",
                    "type_code": "2",
                    "title": "Country",
                    "info": "Multiple answers possible",
                    "variable": "Q2",
                    "page_id": "134",
                    "page_title": "Stran 1",
                    "reminder": "0",
                    "orientation": "1",
                    "order": "2",
                    "params": [],
                    "options": [{
                            "id": "1441",
                            "title": "Slovenia",
                            "variable": "Q2a",
                            "other": "0",
                            "order": "1"
                    }, {
                            "id": "1442",
                            "title": "Germany",
                            "variable": "Q2b",
                            "other": "0",
                            "order": "2"
                    }, {
                            "id": "1443",
                            "title": "UK",
                            "variable": "Q2c",
                            "other": "0",
                            "order": "3"
                    }, {
                            "id": "1446",
                            "title": "Other:",
                            "variable": "Q2d",
                            "other": "1",
                            "order": "4"
                    }]
            }, {
                    "id": "489",
                    "type": "Text input",
                    "type_code": "21",
                    "title": "Write a name",
                    "info": "",
                    "variable": "Q3",
                    "page_id": "135",
                    "page_title": "Page 2",
                    "reminder": "0",
                    "orientation": "1",
                    "order": "1",
                    "params": {
                            "taWidth": "-1",
                            "taSize": "1",
                            "captcha": "0",
                            "emailVerify": "0",
                            "prevAnswers": "0",
                            "disabled_vprasanje": "0"
                    },
                    "options": [{
                            "id": "1444",
                            "title": "Input text",
                            "variable": "Q3a",
                            "other": "0",
                            "order": "1"
                    }]
            }]
        }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurvey($ank_id) {
        global $lang;
        global $global_user_id;

        $sqlank = sisplet_query("SELECT naslov, introduction, conclusion, show_intro, show_concl FROM srv_anketa WHERE id='$ank_id'");
        $rowank = mysqli_fetch_assoc($sqlank);

        $sqlgru = sisplet_query("SELECT g.id as gru_id FROM srv_grupa g WHERE g.ank_id='$ank_id'");
        $rowgru = mysqli_fetch_assoc($sqlgru);
        
        SurveyInfo::getInstance()->SurveyInit($ank_id);
        $link = SurveyInfo::getSurveyLink();

        $json_array = array('link' => $link, 'title' => $rowank['naslov'], 'intro' => $rowank['introduction'], 'concl' => $rowank['conclusion'],
            'show_intro' => $rowank['show_intro'], 'show_concl' => $rowank['show_concl'], 'page_id' => $rowgru['gru_id'], 'questions' => array());

        $vprasanja = array();
        // Napolnimo podatke o vseh vprasanjih v anketi
        $sql = sisplet_query("SELECT s.*, g.naslov as gru_naslov, g.id as gru_id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$ank_id' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
        while ($row = mysqli_fetch_assoc($sql)) {
            $spremenljivkaParams = new enkaParameters($row['params']);
            $json_array['page_id'] = $row['gru_id'];
            // Vrednosti v vprasanju
            $vrednosti = array();
            $sqlV = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='" . $row['id'] . "' ORDER BY vrstni_red ASC");
            while ($rowV = mysqli_fetch_assoc($sqlV)) {
                array_push($vrednosti, array(
                    'id' => $rowV['id'],
                    'title' => strip_tags($rowV['naslov']),
                    'variable' => $rowV['variable'],
                    'other' => $rowV['other'],
                    'order' => $rowV['vrstni_red']
                ));
            }

            array_push($json_array['questions'], array(
                'id' => $row['id'],
                'type' => $lang['srv_vprasanje_tip_' . $row['tip']],
                'type_code' => $row['tip'],
                'title' => strip_tags($row['naslov']),
                'info' => $row['info'],
                'variable' => $row['variable'],
                'page_id' => $row['gru_id'],
                'page_title' => $row['gru_naslov'],
                'reminder' => $row['reminder'],
                'orientation' => $row['orientation'],
                'order' => $row['vrstni_red'],
                'params' => $spremenljivkaParams->toArray(),
                'options' => $vrednosti
            ));
        }

        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyStatuses/survey/:id getSurveyStatuses
     * @apiName getSurveyStatuses
     * @apiGroup Dashboard
     * @apiDescription Get statuses of responses of survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess {Object} valid 6-finished surveys, 5-partially finished surveys
     * @apiSuccess {Object} nonvalid 6l-lurkers, 5l-lurkers, 4-click on survey, 3-click on intro, -1-unknown status
     * @apiSuccess {Object} invitation (non-surveyed units) 2-email sent (error), 1-email sent (non-response), 0-email not sent
     * @apiSuccessExample {json} Success-Response:
     *     {"valid":{"6":50,"5":0},
     *      "nonvalid":{"6l":0,"5l":0,"4":0,"3":0,"-1":0},
     *      "invitation":{"2":0,"1":0,"0":0}}
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyStatuses($ank_id) {
        global $lang;
        global $global_user_id;

        $ss = new SurveyStatistic();
        $ss->Init($ank_id);
        $ss->prepareStatusView();

        $json_array = $ss->getUserByStatus();

        return $json_array;
    }

    // Vrne response rate za anketo
    /**
     * @api {get} https://www.1ka.si/api/getSurveyAnswerState/survey/:id getSurveyAnswerState
     * @apiName getSurveyAnswerState
     * @apiGroup Dashboard
     * @apiDescription Get response rate for survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess (Main Fields) {Object[]} status Basic status of answers (3ll-entered intro, 4ll-entered frist page, 5ll-started responding, 5-partially completed, 6-completed)
     * @apiSuccess (Main Fields) {Object[]} usability Unit usability (unit (bottom usable limit/top usable limit))
     * @apiSuccess (Main Fields) {Object[]} breakoffs Data of responents breakoffs
     * @apiSuccess (Data Fields) {Number} freq Frequency
     * @apiSuccess (Data Fields) {String} state Realtive frequency
     * @apiSuccessExample {json} Success-Response:
     *{
	"status": {
		"3ll": {
			"freq": 29,
			"state": "100%"
		},
		"4ll": {
			"freq": 27,
			"state": "93%"
		},
		"5ll": {
			"freq": 20,
			"state": "69%"
		},
		"5": {
			"freq": 18,
			"state": "62%"
		},
		"6": {
			"freq": 18,
			"state": "62%"
		}
	},
	"usability": {
		"unit": "(50%\/80%)",
		"usable": {
			"freq": 1,
			"state": "5%"
		},
		"partusable": {
			"freq": 6,
			"state": "30%"
		},
		"unusable": {
			"freq": 13,
			"state": "65%"
		}
	},
	"breakoffs": {
		"intro": {
			"freq": 9,
			"state": "31%"
		},
		"questionnaire": {
			"freq": 0,
			"state": "0% (neto 0%)"
		},
		"total": {
			"freq": 9,
			"state": "31%"
		}
	}
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyAnswerState($ank_id) {
        global $lang;
        global $global_user_id;

        $ss = new SurveyStatistic();
        $ss->Init($ank_id);
        $ss->prepareStatusView();

        $json_array = $ss->JsonAnswerStateView();

        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyRedirections/survey/:id getSurveyRedirections
     * @apiName getSurveyRedirections
     * @apiGroup Dashboard
     * @apiDescription Get all redirections of survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccessExample {json} Success-Response:
     *{
	"3": 0,
	"4": 0,
	"5": 0,
	"6": 0,
	"valid": {
		"email": 86,
		"www.1ka.si": 23,
		"www.customsite.si": 1
	},
	"email": 86,
	"direct": 4,
	"cntAll": 0
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyRedirections($ank_id) {
        global $lang;
        global $global_user_id;

        $ss = new SurveyStatistic();
        $ss->Init($ank_id);
        $ss->prepareStatusView();

        $json_array = $ss->getUserRedirections();

        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyDateTimeRange/survey/:id getSurveyDateTimeRange
     * @apiName getSurveyDateTimeRange
     * @apiGroup Dashboard
     * @apiDescription Get object of nubers of all responses by date and hour in day (keys as date and hour in day, values as number of answers at that time)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccessExample {json} Success-Response:
     *{
	"2017-10-02 09": "10",
	"2017-10-03 13": "1",
	"2017-11-10 11": "3",
	"2017-11-10 12": "7",
	"2017-11-10 13": "1",
	"2017-11-10 14": "7",
        "2017-11-10 17": "2"
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyDateTimeRange($ank_id) {
        global $lang;
        global $global_user_id;

        $ss = new SurveyStatistic();
        $ss->Init($ank_id);
        $ss->setPeriod(PERIOD_HOUR_PERIOD);
        $ss->PrepareDateView();

        $json_array = $ss->getArrayRange();

        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyParadata/survey/:id getSurveyParadata
     * @apiName getSurveyParadata
     * @apiGroup Dashboard
     * @apiDescription Get paradata of responses of survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess {Object[]} valid Paradata of valid answers/respondents
     * @apiSuccess {Object[]} all Paradata of all (valid and nonvalid) answers/respondents
     * @apiSuccessExample {json} Success-Response:
     *{
	"valid": {
		"unfilteredCount": 3,
		"allCount": 2,
		"pcCount": "2",
		"mobiCount": 0,
		"tabletCount": 0,
		"robotCount": 0,
		"jsActive": 2,
		"jsNonActive": 0,
		"jsUndefined": 0,
		"browser": {
			"Other": "2"
		},
		"os": {
			"Other": "2"
		}
	},
	"all": {
		"unfilteredCount": 3,
		"allCount": 3,
		"pcCount": "3",
		"mobiCount": 0,
		"tabletCount": 0,
		"robotCount": 0,
		"jsActive": 3,
		"jsNonActive": 0,
		"jsUndefined": 0,
		"browser": {
			"Other": "3"
		},
		"os": {
			"Other": "3"
		}
	}
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyParadata($ank_id) {
        global $lang;
        global $global_user_id;

        $spg = new SurveyParaGraph($ank_id);
        $spg->setParaGraphFilter(array('status' => 1, 'pc' => 1, 'tablet' => 1, 'mobi' => 1, 'robot' => 1));
        $json_array_valid = $spg->collectParaGraphDataNew();
        $spg->setParaGraphFilter(array('status' => 0, 'pc' => 1, 'tablet' => 1, 'mobi' => 1, 'robot' => 1));
        $json_array_all = $spg->collectParaGraphDataNew();

        return (array('valid' => $json_array_valid, 'all' => $json_array_all));
    }
    
    /**
     * @api {get} https://www.1ka.si/api/getSurveyDashboard/survey/:id getSurveyDashboard
     * @apiName getSurveyDashboard
     * @apiGroup Dashboard
     * @apiDescription Get all dashboard data of survey (if survey has no responses, only survey info is returned)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess {Object[]} info Info of survey (basic dashboard info)
     * @apiSuccess {Object[]} [statuses] Statuses of responses of survey (optional)
     * @apiSuccess {Object[]} [datetime] Object of nubers of all responses by date and hour in day (optional)
     * @apiSuccess {Object[]} [redirections] Redirections of survey (optional)
     * @apiSuccess {Object[]} [paradata] Paradata of responses of survey (optional)
     * @apiSuccess {Object[]} [responserate] Response rate of survey (optional)
     * @apiSuccessExample {json} Success-Response:
     *{
	"info": [SEE OUTPUT OF FUNCTION getSurveyInfo],
	"statuses": [SEE OUTPUT OF FUNCTION getSurveyStatuses],
	"datetime": [SEE OUTPUT OF FUNCTION getSurveyDateTimeRange],
	"redirections": [SEE OUTPUT OF FUNCTION getSurveyDateTimeRange],
	"paradata": [SEE OUTPUT OF FUNCTION getSurveyParadata],
	"responserate": [SEE OUTPUT OF FUNCTION getSurveyAnswerState]
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyDashboard($ank_id){
        $json_array = array();
        $json_array['info'] = $this->getSurveyInfo($ank_id);

        //if there are no answers, no need for dashboard, only info needed
        if ($json_array['info']['surveys'][0]['answers'] > 0) {
            $json_array['statuses'] = $this->getSurveyStatuses($ank_id);
            $json_array['datetime'] = $this->getSurveyDateTimeRange($ank_id);
            $json_array['redirections'] = $this->getSurveyRedirections($ank_id);
            $json_array['paradata'] = $this->getSurveyParadata($ank_id);
            $json_array['responserate'] = $this->getSurveyAnswerState($ank_id);
        }
        
        return $json_array;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyFrequencies/survey/:id getSurveyFrequencies
     * @apiName getSurveyFrequencies
     * @apiGroup Data and analysis
     * @apiDescription Get frequencies for all radio, checkbox, dropdown and plain text questions in the survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess (Question Fields) {String} besedilo_vprasanja Text of question
     * @apiSuccess (Question Fields) {String} id_vprasanja Id of question (left side of '_' is actual ID of question, right side is ID of sequence within question)
     * @apiSuccess (Question Fields) {String} vrsta_vprasanja Code of question type: 0-single choice (radio, dropdown), 1-multiple choice (checkbox), 2-text
     * @apiSuccess (Question Fields) {Object} odgovori Answers
     * @apiSuccess (Answer Fields) {Object} invalid Invalid answers
     * @apiSuccess (Answer Fields) {Number} invalidCnt Count of all invalid answers
     * @apiSuccess (Answer Fields) {Number} allCnt Count of all answers
     * @apiSuccess (Answer Fields) {Number} validCnt Count of all valid answers
     * @apiSuccess (Answer Fields) {Object[]} valid Array of all valid asnwers
     * @apiSuccess (Answer Fields) {String} naslov Text/name/title of answer/choice (not in single choice)
     * @apiSuccess (Valid answer Fields - single choice) {String} text Text/name/title of answer/choice
     * @apiSuccess (Valid answer Fields - single choice) {String} text_graf Text of answer/choice in graph
     * @apiSuccess (Valid answer Fields - single choice) {String} cnt Count of choices for this answer
     * @apiSuccess (Valid answer Fields - single choice) {Number} vrednost Value of answer/choice
     * @apiSuccess (Valid answer Fields - multiple choice) {String} text 0-not checked, 1-checked
     * @apiSuccess (Valid answer Fields - text) {String} text Actual text asnwer
     * @apiSuccess (Valid answer Fields - text) {Number} cnt Count of same asnwer
     * 
     * @apiSuccessExample {json} Success-Response:
     * [{
                "besedilo_vprasanja": "Best counrty in Europe",
                "id_vprasanja": "118_0",
                "vrsta_vprasanja": "0",
                "odgovori": {
                        "invalid": {
                                "-1": {"text": "Unanswered question","cnt": "1"},
                                "-2": {"text": "Skipped question (IF logic)","cnt": 0},
                                "-3": {"text": "Drop-out","cnt": 0},
                                "-4": {"text": "Subsequent question","cnt": 0},
                                "-5": {"text": "Empty unit","cnt": 0},
                                "-97": {"text": "Invalid","cnt": 0},
                                "-98": {"text": "Refused","cnt": 0},
                                "-99": {"text": "Don&#39;t know","cnt": 0}
                        },
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "validCnt": 4,
                        "valid": [{
                                "text": "Slovenia",
                                "text_graf": "Slovenia",
                                "cnt": "1",
                                "vrednost": 1
                        }, {
                                "text": "Spain",
                                "text_graf": "Spain",
                                "cnt": 0,
                                "vrednost": 2
                        }, {
                                "text": "Germany",
                                "text_graf": "Germany",
                                "cnt": "2",
                                "vrednost": 3
                        }, {
                                "text": "Other:",
                                "text_graf": "Other:",
                                "cnt": "1",
                                "vrednost": 4
                        }, {
                                "text": "estonia",
                                "cnt": 1,
                                "text_graf": null,
                                "other": "Other:",
                                "vrednost": "estonia"
                        }]
                }
        }, {
                "besedilo_vprasanja": "Cities you visited",
                "id_vprasanja": "119_0",
                "vrsta_vprasanja": "1",
                "odgovori": [{
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "valid": [{
                                "text": "0",
                                "text_graf": null,
                                "cnt": "1"
                        }, {
                                "text": "1",
                                "text_graf": null,
                                "cnt": "3"
                        }],
                        "validCnt": 4,
                        "naslov": "Ljubljana"
                }, {
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "valid": [{
                                "text": "0",
                                "text_graf": null,
                                "cnt": "3"
                        }, {
                                "text": "1",
                                "text_graf": null,
                                "cnt": "1"
                        }],
                        "validCnt": 4,
                        "naslov": "Berlin"
                }, {
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "valid": [{
                                "text": "0",
                                "text_graf": null,
                                "cnt": "2"
                        }, {
                                "text": "1",
                                "text_graf": null,
                                "cnt": "2"
                        }],
                        "validCnt": 4,
                        "naslov": "Madrid"
                }, {
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "valid": [{
                                "text": "0",
                                "text_graf": null,
                                "cnt": "3"
                        }, {
                                "text": "1",
                                "text_graf": null,
                                "cnt": "1"
                        }],
                        "validCnt": 4,
                        "naslov": "London"
                }, {
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "valid": [{
                                "text": "0",
                                "text_graf": null,
                                "cnt": 0
                        }, {
                                "text": "1",
                                "text_graf": null,
                                "cnt": "4"
                        }],
                        "validCnt": 4,
                        "naslov": "Other:"
                }, {
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "validCnt": 4,
                        "average": null,
                        "valid": [{
                                "text": "paris",
                                "cnt": 1,
                                "text_graf": null,
                                "other": "Other:"
                        }, {
                                "text": "zagreb",
                                "cnt": 1,
                                "text_graf": null,
                                "other": "Other:"
                        }, {
                                "text": "lisbon",
                                "cnt": 2,
                                "text_graf": null,
                                "other": "Other:"
                        }],
                        "other": "Other:"
                }]
        }, {
                "besedilo_vprasanja": "Write a name",
                "id_vprasanja": "120_0",
                "vrsta_vprasanja": "2",
                "odgovori": [{
                        "invalid": {[SEE FIRST QUESTION]},
                        "invalidCnt": 1,
                        "allCnt": 5,
                        "validCnt": 4,
                        "average": null,
                        "valid": [{
                                "text": "lucy",
                                "cnt": 1,
                                "text_graf": null
                        }, {
                                "text": "crish",
                                "cnt": 2,
                                "text_graf": null
                        }, {
                                "text": "marie",
                                "cnt": 1,
                                "text_graf": null
                        }]
                }]
       }]
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyFrequencies($ank_id) {
        global $lang;
        global $global_user_id;

        $_GET['a'] = 'getSurveyFrequenciesAPI';
        SurveyAnalysis::Init($ank_id);

        $json_array = array();

        // Zracunamo frekvence
        $frequencies = SurveyAnalysis::getFrequencys();

        //error_log(serialize($frequencies));
        // Loop cez vsa vprasanja
        $cnt = 0;
        foreach (SurveyAnalysis::$_HEADERS AS $spid => $spremenljivka) {
            if ($spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm' && isset($spremenljivka['tip']) && in_array($spremenljivka['tip'], array('1', '2', '3', '21'))) {

                // Naslov vprasanja
                $json_array[$cnt]['besedilo_vprasanja'] = $spremenljivka['naslov'];
                $json_array[$cnt]['id_vprasanja'] = $spid;

                // text
                if ($spremenljivka['tip'] == 21) {
                    $json_array[$cnt]['vrsta_vprasanja'] = '2';

                    $variables = explode('_', $spremenljivka['sequences']);
                    $i = 0;
                    foreach ($variables as $variable) {

                        // Frekvence vprasanja - vsako polje posebej
                        $json_array[$cnt]['odgovori'][$i] = $frequencies[$variable];

                        // Popravimo da je lahko json array
                        $valid_array = array();
                        if (count($json_array[$cnt]['odgovori'][$i]['valid']) > 0) {
                            $j = 0;
                            foreach ($json_array[$cnt]['odgovori'][$i]['valid'] as $key => $val) {
                                $valid_array[$j] = $val;

                                $j++;
                            }
                        }

                        unset($json_array[$cnt]['odgovori'][$i]['valid']);
                        $json_array[$cnt]['odgovori'][$i]['valid'] = $valid_array;

                        $i++;
                    }
                }
                // checkbox
                else if ($spremenljivka['tip'] == 2) {
                    $json_array[$cnt]['vrsta_vprasanja'] = '1';

                    $variables = explode('_', $spremenljivka['sequences']);
                    $i = 0;
                    foreach ($variables as $variable) {
                        //var_dump($spremenljivka['grids'][0]['variables']);
                        //$out = array_values($frequencies[$variable]);
                        // Frekvence vprasanja - vsak checkbox posebej
                        $json_array[$cnt]['odgovori'][$i] = $frequencies[$variable];
                        //$json_array[$cnt]['odgovori'][$i] = array_values($frequencies[$variable]);
                        //je navadni checkbox
                        if ($json_array[$cnt]['odgovori'][$i]['valid'][0] != null) {
                            // Dodamo se text checkboxa
                            $json_array[$cnt]['odgovori'][$i]['naslov'] = $spremenljivka['grids'][0]['variables'][$i]['naslov'];
                        }
                        //so vnesene opcije "Drugo:"
                        else {
                            // Popravimo da je lahko json array
                            $valid_array = array();
                            if (count($json_array[$cnt]['odgovori'][$i]['valid']) > 0) {
                                $j = 0;
                                foreach ($json_array[$cnt]['odgovori'][$i]['valid'] as $key => $val) {
                                    $valid_array[$j] = $val;
                                    $j++;
                                }
                            }

                            //ce obstajajo odgovori na opcijo other, jih izpisi
                            if ($valid_array != null) {
                                unset($json_array[$cnt]['odgovori'][$i]['valid']);
                                $json_array[$cnt]['odgovori'][$i]['valid'] = $valid_array;
                                //dodamo znacko da je other
                                $json_array[$cnt]['odgovori'][$i]['other'] = $spremenljivka['grids'][0]['variables'][$i]['naslov'];
                                // Dodamo se text checkboxa
                                //$json_array[$cnt]['odgovori'][$i]['naslov'] = $spremenljivka['grids'][0]['variables'][$i]['naslov'];
                            }
                            //ce ne obstajajo odgovori na opcijo other, sploh ne posiljaj tega objekta
                            else {
                                unset($json_array[$cnt]['odgovori'][$i]);
                            }
                        }
                        $i++;
                    }
                }
                // radio
                else {
                    $json_array[$cnt]['vrsta_vprasanja'] = '0';

                    // Frekvence vprasanja
                    $variable = explode('_', $spremenljivka['sequences']);
                    $json_array[$cnt]['odgovori'] = $frequencies[$variable[0]];
                    
                    // Popravimo da je lahko json array
                    $valid_array = array();
                    $i = 0;
                    foreach ($json_array[$cnt]['odgovori']['valid'] as $key => $val) {
                        $valid_array[$i] = $val;
                        $valid_array[$i]['vrednost'] = $key;

                        $i++;
                    }

                    if (isset($frequencies[$variable[1]]['valid'])) {
                        foreach ($frequencies[$variable[1]]['valid'] as $key => $val) {
                            $valid_array[$i] = $val;
                            $valid_array[$i]['vrednost'] = $key;
                            //$valid_array[$i]['other'] = 1;//zdaj se po defaultu izpise other: [String drugo]
                            $i++;
                        }
                    }

                    unset($json_array[$cnt]['odgovori']['valid']);
                    $json_array[$cnt]['odgovori']['valid'] = $valid_array;
                }

                $cnt++;
            }
        }
        return $json_array;
    }

    /**
     * Vrne seznam anket za uporabnika ali info dolocene ankete
     * @param type $ank_id
     * @param type $limit - limit koliko anket vrne (zadnji vnos DESC)
     * @param type $mobile_created - 0=vse ankete, 1=samo mobilne ankete
     * @return array
     */
    private function getSurveyListInfo($ank_id = 0, $limit = '', $mobile_created = -1) {
        $SL = new SurveyList();

        $surveys = $SL->getSurveysSimple($ank_id, $limit, $mobile_created, true);

        $json_array = array();
        $json_array['count'] = count($surveys);
        $json_array['surveys'] = $surveys;
        if($ank_id > 0){
            SurveyInfo::getInstance()->SurveyInit($ank_id);
            $json_array['link'] = SurveyInfo::getSurveyLink();
        }

        return $json_array;
    }
    
    /**
     * @api {get} https://www.1ka.si/api/getSurveyInfo/survey/:id getSurveyInfo
     * @apiName getSurveyInfo
     * @apiGroup Surveys
     * @apiDescription Get info of survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} id Id of survey
     * 
     * @apiSuccess {Number} count Number of surveys in list
     * @apiSuccess {String} link Access link of survey for respondents
     * @apiSuccess {Object[]} surveys Array of surveys
     * @apiSuccess {String} id ID of survey
     * @apiSuccess {String} naslov Title of survey
     * @apiSuccess {String} active Current activity of survey (1 – survey is active, 0 – survey is not active)
     * @apiSuccess {String} block_ip Blocked IP in minutes – 0 off (1440 = 24h) - if on, respondent can not access to survey again for given minutes
     * @apiSuccess {String} e_name Name of editor of survey
     * @apiSuccess {String} i_name Name of author of survey
     * @apiSuccess {String} e_time Last edited
     * @apiSuccess {String} i_time Created
     * @apiSuccess {String} v_time_first First entry
     * @apiSuccess {String} v_time_last Last entry
     * @apiSuccess {String} answers Number of units
     * @apiSuccess {String} variables Number of questions
     * @apiSuccess {String} lastingfrom Date of start survey duration
     * @apiSuccess {String} lastinguntill Date of end survey duration
     * @apiSuccess {String} survey_type Type of survey (2-survey, 0-voting, 1-form)
     * @apiSuccess {String} link Link of survey
     * @apiSuccessExample {json} Success-Response:
     *     {"count":1,
     *           "surveys":[
     *              {"id":"29",
     *              "folder":"1",
     *              "del":"1",
     *              "naslov":"Test 111",
     *              "active":"1",
     *              "mobile_created":"0",
     *              "block_ip":"0",
     *              "edit_uid":"1045",
     *              "e_name":"admin",
     *              "e_surname":"admin",
     *              "e_email":"admin",
     *              "insert_uid":"1045",
     *              "i_name":"admin",
     *              "i_surname":"admin",
     *              "i_email":"admin",
     *              "e_time":"08.11.18 11:36",
     *              "i_time":"27.07.18 11:36",
     *              "v_time_first":"27.07.18 14:31",
     *              "v_time_last":"20.08.18 9:33",
     *              "answers":"8",
     *              "approp":"7",
     *              "variables":"12",
     *              "trajanjeod":"08.11.18",
     *              "trajanjedo":"08.02.19",
     *              "survey_type":"2"}
     *      ],
     *      "link":"http:\/\/www.1ka.si\/a\/109"
     *    }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyInfo($ank_id) {
        return $this->getSurveyListInfo($ank_id);
    }
    
    /**
     * @api {get} https://www.1ka.si/api/getSurveyList?limit=3 getSurveyList
     * @apiName getSurveyList
     * @apiGroup Surveys
     * @apiDescription Get list of info of all surveys
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam {Number} limit Optional Limit of surveys to return, DESC order by time of new input (answer)
     * 
     * @apiSuccess {Number} count Number of surveys in list
     * @apiSuccess {Object[]} surveys Array of surveys
     * @apiSuccess {String} id ID of survey
     * @apiSuccess {String} naslov Title of survey
     * @apiSuccess {String} active Current activity of survey (1 – survey is active, 0 – survey is not active)
     * @apiSuccess {String} block_ip Blocked IP in minutes – 0 off (1440 = 24h) - if on, respondent can not access to survey again for given minutes
     * @apiSuccess {String} e_name Name of editor of survey
     * @apiSuccess {String} i_name Name of author of survey
     * @apiSuccess {String} e_time Last edited
     * @apiSuccess {String} i_time Created
     * @apiSuccess {String} v_time_first First entry
     * @apiSuccess {String} v_time_last Last entry
     * @apiSuccess {String} answers Number of units
     * @apiSuccess {String} variables Number of questions
     * @apiSuccess {String} lastingfrom Date of start survey duration
     * @apiSuccess {String} lastinguntill Date of end survey duration
     * @apiSuccess {String} survey_type Type of survey (2-survey, 0-voting, 1-form)
     * @apiSuccessExample {json} Success-Response:
     *     {"count":3,
     *           "surveys":[
     *              {"id":"29",
     *              "folder":"1",
     *              "del":"1",
     *              "naslov":"Test 111",
     *              "active":"1",
     *              "mobile_created":"0",
     *              "block_ip":"0",
     *              "edit_uid":"1045",
     *              "e_name":"admin",
     *              "e_surname":"admin",
     *              "e_email":"admin",
     *              "insert_uid":"1045",
     *              "i_name":"admin",
     *              "i_surname":"admin",
     *              "i_email":"admin",
     *              "e_time":"08.11.18 11:36",
     *              "i_time":"27.07.18 11:36",
     *              "v_time_first":"27.07.18 14:31",
     *              "v_time_last":"20.08.18 9:33",
     *              "answers":"8",
     *              "approp":"7",
     *              "variables":"12",
     *              "trajanjeod":"08.11.18",
     *              "trajanjedo":"08.02.19",
     *              "survey_type":"2"},...
     *      ]}
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyList($limit = '', $mobile_created = -1) {
        return $this->getSurveyListInfo(0, $limit, $mobile_created);
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyResponses getSurveyResponses
     * @apiName getSurveyResponses
     * @apiGroup Surveys
     * @apiDescription Get list of numbers of all surveys responses (and info about activity) with keys as survey ID
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiSuccess {String} answers Number of all responses
     * @apiSuccess {String} active Is survey active right now (1-active, 0-unactive)
     * @apiSuccessExample {json} Success-Response:
     *{
	"4401": {
		"answers": "1103",
		"active": "0"
	},
	"5012": {
		"answers": "190",
		"active": "1"
	},
	"5330": {
		"answers": "88",
		"active": "1"
	}
     *}
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyResponses($data) {
        global $global_user_id;

        $result = array();

        // ce imas hkrati dostop do ankete (srv_dostop) in preko managerskega dostopa (srv_dostop_manage) se brez DISTINCT podvajajo ankete
        $stringSurveyList = "SELECT DISTINCT sa.id, sa.active, ";
        $stringSurveyList .= "sal.answers as answers "; // vedno prestejemo odgovore

        $stringSurveyList .= "FROM srv_anketa sa ";
        $stringSurveyList .= "LEFT OUTER JOIN srv_survey_list AS sal ON sal.id = sa.id ";

        # kdo lahko ureja anketo (briše)
        // tega substringy se ne da dodatno razbit z prepareSubquery, ker selectamo 2 elementa...
        $stringSurveyList .= "LEFT OUTER JOIN (SELECT 1 AS canEdit, ank_id FROM srv_dostop WHERE FIND_IN_SET('edit', dostop ) ='1' AND aktiven = '1' AND uid = '$global_user_id' OR uid IN (" . SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '" . $global_user_id . "'")) . ")) AS sd ON sd.ank_id = sa.id ";

        $stringSurveyList .= "WHERE sa.backup='0' AND sa.id>0 AND active >= '0' AND invisible = '0' ";

        if (isset($data['mobile_created']) && $data['mobile_created'] != -1)
            $stringSurveyList .= "AND sa.mobile_created='" . $data['mobile_created'] . "' ";

        $stringSurveyList .= "AND NOT EXISTS (SELECT * FROM srv_mysurvey_anketa sma WHERE sma.ank_id=sa.id AND sma.usr_id='$global_user_id') ";

        $sqlSurveyList = sisplet_query($stringSurveyList);

        while ($rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
            $result[$rowSurveyList['id']]['answers'] = $rowSurveyList['answers'];
            $result[$rowSurveyList['id']]['active'] = $rowSurveyList['active'];
        }
	
        return $result;
    }

    /**
     * @api {get} https://www.1ka.si/api/getSurveyResponseData/survey/:id?usr_id=333 getSurveyResponseData
     * @apiName getSurveyResponseData
     * @apiGroup Data and analysis
     * @apiDescription Get basic info and all values/answers of response
     * 
     * @apiParam {Number} id ID of survey
     * @apiParam {Number} usr_id ID of response to analyse
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiSuccess {String} relevance Relevance of response (1-valid, 0-unvalid)
     * @apiSuccess {String} status Status code of response (6-Completed, 5-partially completed, 4-entered first page, 3-entered intro)
     * @apiSuccess {String} recnum Record number (sequence of response in survey)
     * @apiSuccess {String} itime Date of response
     * @apiSuccess {String} [ALL_OTHERS] Keys as names of values, values as answers
     * @apiSuccessExample {json} Success-Response:
     *{
        "relevance (Relevance)": "1",
        "status (Status)": "6",
        "recnum (Record number)": "1",
        "itime (Date)": "20.05.2019",
        "Q1 (City)": "1",
        "Q1_4_text (Other:)": "-2",
        "Q2a (Slovenia)": "1",
        "Q2b (Germany)": "1",
        "Q2c (UK)": "0",
        "Q2d (Other:)": "0",
        "Q2d_text (Other:)": "-2",
        "Q3 (Vpi\u0161ite besedilo)": "Manja"
      }
     * 
     * @apiVersion 1.0.0
     */
    private function getSurveyResponseData($ank_id, $usr_id, $usr_param='') {
        global $site_path;
		
		$json_array = array();
		
		// Preverimo ce je user id ok nastavljen
		if ($usr_id <= 0) {
			$json_array['error'] = 'Error! User ID is not set!';
			return $json_array;
		} 
		

        // Poskrbimo za datoteko s podatki
        $SDF = SurveyDataFile::get_instance();
        $SDF->init($ank_id);           
        $SDF->prepareFiles();  

        $_headFileName = $SDF->getHeaderFileName();
        $_dataFileName = $SDF->getDataFileName();
        $_fileStatus = $SDF->getStatus();
		
		// Preverimo ce je ok ustvarjena datoteka s podatki in nastavimo header
		if ($_fileStatus >= 0 && $_dataFileName !== null && $_dataFileName !== '' && $_headFileName !== null && $_headFileName !== '') {
			$_HEADERS = unserialize(file_get_contents($_headFileName));
		} 
		else {
            $json_array['error'] = 'Error! Data file is missing!';

			return $json_array;
		}
		
		
		// Nastavimo na katerem mestu je user id - po defaultu jemljemo usr_id, ki je na 1. mestu
		$user_position = '1';
		
		# naredimo header row
		$header_array = array();
		$cnt_header = 2;
		foreach ($_HEADERS AS $spid => $spremenljivka) {
			if (count($spremenljivka['grids']) > 0) {
				foreach ($spremenljivka['grids'] AS $gid => $grid) {
					foreach ($grid['variables'] AS $vid => $variable ){
						if ($spremenljivka['tip'] !== 'sm' && !($variable['variable'] == 'uid' && $variable['naslov'] == 'User ID')){
							
							$header_array[] = strip_tags($variable['variable']) . ' ('.strip_tags($variable['naslov']).')';
							
							// Ce ne primerjamo z usr_id ampak s posebno sistemsko spremenljivko ki belezi id
							if($usr_param != '' && $usr_param == strip_tags($variable['variable']))
								$user_position = $cnt_header;							
							
							$cnt_header++;
						}
					}
				}
			}
		}
			
		// Podatke sfiltriramo glede na user id
		$user_filter = '($'.$user_position.'=='.$usr_id.')';
		//$status_filter = '('.STATUS_FIELD.'==6)&&('.LURKER_FIELD.'==0)';
		
		//$start_sequence = $_HEADERS['_settings']['dataSequence'];
		$start_sequence = 1;
		$end_sequence = $_HEADERS['_settings']['metaSequence']-1;
		
		$field_delimit = ';';
		
		// Filtriramo podatke po statusu in jih zapisemo v temp folder
		$tmp_folder = $site_path . EXPORT_FOLDER.'/';	
		
		if (IS_WINDOWS) {
			$out = shell_exec('awk -F"|" "BEGIN {{OFS=\",\"} {ORS=\"\n\"}} '.$user_filter.'" '.$_dataFileName.' | cut -d "|" -f '.$start_sequence.'-'.$end_sequence.' >> '.$tmp_folder.'/temp_api_'.$ank_id.'_'.$usr_id.'.dat');
		} 
		else {
			$out = shell_exec('awk -F"|" \'BEGIN {{OFS=","} {ORS="\n"}} '.$user_filter.'\' '.$_dataFileName.' | cut -d \'|\' -f '.$start_sequence.'-'.$end_sequence.' >> '.$tmp_folder.'/temp_api_'.$ank_id.'_'.$usr_id.'.dat');
		}
			
			
		if ($fd = fopen($tmp_folder.'/temp_api_'.$ank_id.'_'.$usr_id.'.dat', "r")) {
													
			$data_array = array();
			
			// Loop cez vrstice/respondente (ce jih je slucajno vec)
			$cnt = 0;
			while ($line = fgets($fd)) {
				
				$temp = array();
				$temp = explode('|', $line);

				// Pobrisemo prvo polje user id), ker ga ne rabimo vec
				$line = substr($line, strpos($line, '|')+1);
				
				$line = str_replace(array("\r","\n","\"","|", "\'", "\""), array("","","",'";"', "'", ""), $line);
				
				$data_array = explode(';', $line);
				
				$cnt++;
			}
		}
		fclose($fd);

		// Na koncu pobrisemo temp datoteko
		if (file_exists($tmp_folder.'/temp_api_'.$ank_id.'_'.$usr_id.'.dat')) {
			unlink($tmp_folder.'/temp_api_'.$ank_id.'_'.$usr_id.'.dat');
		}
		
		
		// Ce respondent ne obstaja
		if($cnt == 0){
			$json_array['error'] = 'Error! Respondent '.$usr_id.' does not exist!';
			return $json_array;
		}
		// Drugace pripravimo odgovor
		else{
			
			foreach($header_array as $key => $header_el){
				
				// Pocistimo dolocena polja (invitation, lurker...)
				if(!in_array($key, array(1,3,4,6)))
					$json_array[$header_el] = $data_array[$key];
			}
		}
		
		
        return $json_array;
    }

    /**
     * @api {post} https://www.1ka.si/api/createSurvey createSurvey
     * @apiName createSurvey
     * @apiGroup Surveys
     * @apiDescription Create survey with questions
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (Survey fields) {String} naslov_vprasalnika Title/name of survey
     * @apiParam (Survey fields) {Number} survey_type Type of survey (0-voting, 2-survey)
     * @apiParam (Survey fields) {Object} uvod Introducrion data
     * @apiParam (Survey fields) {Number} [hide_uvod] Do we hide introduction (0-show, 1-hide, default is 0)
     * @apiParam (Survey fields) {Object} zakljucek Conclusion data
     * @apiParam (Survey fields) {Number} [hide_zakljucek] Do we hide conclusion (0-show, 1-hide, default is 0)
     * @apiParam (Survey fields) {String} besedilo Text of introduction or conclusion (set it on "" for default text)
     * @apiParam (Survey fields) {Object[]} [vprasanja] Array of all questions to add to survey
     * @apiParam (Question fields) {String} besedilo_vprasanja Text of question
     * @apiParam (Question fields) {Number} mesto_vprasanja Order of question sequence to place this question in page
     * @apiParam (Question fields) {Number} vrsta_vprasanja Type of question (0-radio, 1-checkbox, 2-text)
     * @apiParam (Question fields) {Number} [reminder] Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder) (default is 0)
     * @apiParam (Question fields) {String} [other] Text of option other to add (for radio and checkbox)
     * @apiParam (Question fields) {Number} [velikost_polja] Height size in lines of text field (for text question) (default is single line)
     * @apiParam (Question fields) {String[]} [Odgovori] Array of options to add to question (for radio and checkbox)
     * @apiParamExample {json} Post-example (For survey): 
      {
	"naslov_vprasalnika": "This is title of new survey",
	"survey_type": 2,
	"uvod": {
		"besedilo": "This is text of intruduction",
		"hide_uvod": 0
	},
	"zakljucek": {
		"besedilo": "",
		"hide_zakljucek": 1
	},
	"vprasanja": [{
			"besedilo_vprasanja": "This is text of question number 1",
			"mesto_vprasanja": 1,
			"vrsta_vprasanja": 1,
			"reminder": 0,
			"other": "Other:",
			"Odgovori": ["Text of option 1", "Text of option 2", "Text of option 3"]
		},
		{
			"besedilo_vprasanja": "This is text of question number 2",
			"mesto_vprasanja": 2,
			"vrsta_vprasanja": 2,
			"velikost_polja": 10,
			"reminder": 1
		},
                {
			"besedilo_vprasanja": "This is text of question number 3",
			"mesto_vprasanja": 3,
			"vrsta_vprasanja": 0,
			"Odgovori": ["Text of option 1", "Text of option 2", "Text of option 3"]
		}
	]
      }
     * 
     * @apiParamExample {json} Post-example (For voting): 
      {
	"naslov_vprasalnika": "This is title of new survey",
	"survey_type": 0,
        "besedilo_vprasanja": "This is text of question number 1",
        "vrsta_vprasanja": 0,
        "other": "Other:",
        "Odgovori": ["Text of option 1", "Text of option 2", "Text of option 3"]
      }
     * 
     * @apiSuccess {String} url Link to new survey
     * @apiSuccess {String} id ID of new survey
     * @apiSuccessExample {json} Success-Response:
     *     {"url":"http:\/\/141.255.212.38\/1ka\/a\/56","id":56,"note":"Survey created"}
     * 
     * @apiVersion 1.0.0
     */
    private function createSurvey($data) {
        global $lang;
        global $site_url;
        global $global_user_id;

        $json_array = array();

        // Preverimo ce imamo osnovne podatke za ustvarjanje ankete
        if (!isset($data['naslov_vprasalnika']) || !isset($data['survey_type'])) {
            $json_array['error'] = 'Title or/and type of survey missing';
            return $json_array;
        }

        $mobile_created = (isset($data['mobile_created']) && $data['mobile_created'] == 1) ? 1 : 0;

        $url = $site_url;
        $naslov = $data['naslov_vprasalnika'];
        $purifier = New Purifier();
        $naslov = $purifier->purify_DB($naslov);
        $survey_type = $data['survey_type'];

        $akronim = $naslov;

        $starts = "NOW()";
        $expire = "NOW() + INTERVAL 3 MONTH  ";

        $lang_resp = $data['lang_resp'];
        $lang_admin = $data['lang_admin'];

        $autoActiveSurvey = 0;

        $res = sisplet_query("SELECT value FROM misc WHERE what='SurveyCookie'");
        list ($SurveyCookie) = mysqli_fetch_row($res);

        // Nastavimo se hash
        $hash = Common::generateSurveyHash();

        // GLASOVANJE
        if ($survey_type == 0) {

            $sql = sisplet_query("INSERT INTO srv_anketa (id, hash, naslov, akronim, db_table, starts, expire, dostop, insert_uid, insert_time, edit_uid, edit_time, cookie, text, url, intro_opomba, survey_type, lang_admin, lang_resp, active, skin, show_intro, show_concl, locked, mobile_created) " .
                    "VALUES ('', '".$hash."', $naslov', '$akronim', '1', $starts, $expire, '0', '$global_user_id', NOW(), '$global_user_id', NOW(), '$SurveyCookie', '', '$url', '', '0', '$lang_admin', '$lang_resp', '0', '1kaBlue', '0', '0', '0', '$mobile_created')");

            if (!$sql) {
                $error = mysqli_error($GLOBALS['connect_db']);
            }
            $anketa = mysqli_insert_id($GLOBALS['connect_db']);


            if ($anketa > 0) {

                $url .= 'a/' . $hash;

                // vnesemo tudi 1. grupo aka page
                $sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] 1', '1')");
                $grupa = mysqli_insert_id($GLOBALS['connect_db']);


                // Dodamo edino vpraasanje
                $title = $purifier->purify_DB($data['besedilo_vprasanja']);
                $vrstni_red = '1';
                $variable = 'Q' . $vrstni_red;

                // checkbox
                if ($data['vrsta_vprasanja'] == '1') {
                    $type = 2;
                    $size = count($data['Odgovori']) > 0 ? count($data['Odgovori']) : 3;
                }
                // radio
                else {
                    $type = 1;
                    $size = count($data['Odgovori']) > 0 ? count($data['Odgovori']) : 3;
                }

                // Vstavimo vprasanje
                $sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red) 
					VALUES ('', '$grupa', '<p>$title</p>', '$variable', '$size', '$type', '$vrstni_red')");
                $spremenljivka = mysqli_insert_id($GLOBALS['connect_db']);

                // Gremo cez posamezne vrednosti in jih dodamo
                if (count($data['Odgovori']) > 0) {
                    $cnt = 1;
                    foreach ($data['Odgovori'] as $vrednost) {
                        $vrednost = $purifier->purify_DB($vrednost);
                        $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) 
							VALUES ('', '$spremenljivka', '$vrednost', '$cnt', '$cnt')");

                        $cnt++;
                    }
                } else {
                    for ($i = 1; $i <= $size; $i++) {
                        $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, variable, vrstni_red) 
							VALUES ('', '$spremenljivka', '$i', '$i')");
                    }
                }
                
                if (isset($data['other']) && $data['other'] != '') {
                    $Vpr = new Vprasanje();
                    $Vpr->vrednost_new($data['other'], 1, null, $spremenljivka);
                }
            }
        }
        // NAVADNA ANKETA
        else {
            $uvod = $data['uvod'];
            $uvod_besedilo = $purifier->purify_DB($uvod['besedilo']);
            $show_intro = ($uvod['hide_uvod'] == '1') ? 0 : 1;

            $zakljucek = $data['zakljucek'];
            $zakljucek_besedilo = $purifier->purify_DB($zakljucek['besedilo']);
            $show_concl = ($zakljucek['hide_zakljucek'] == '1') ? 0 : 1;

            $sql = sisplet_query("INSERT INTO srv_anketa (id, hash, naslov, akronim, db_table, starts, expire, dostop, insert_uid, insert_time, edit_uid, edit_time, cookie, text, url, intro_opomba, survey_type, lang_admin, lang_resp, active, skin, introduction, conclusion, show_intro, show_concl, locked, mobile_created) " .
                    "VALUES ('', '".$hash."', $naslov', '$akronim', '1', $starts, $expire, '0', '$global_user_id', NOW(), '$global_user_id', NOW(), '$SurveyCookie', '', '$url', '', '2', '$lang_admin', '$lang_resp', '$autoActiveSurvey', '1ka', '$uvod_besedilo', '$zakljucek_besedilo', '$show_intro', '$show_concl', '1', '$mobile_created')");
            if (!$sql) {
                $error = mysqli_error($GLOBALS['connect_db']);
            }
            $anketa = mysqli_insert_id($GLOBALS['connect_db']);


            if ($anketa > 0) {

                $url .= 'a/' . $hash;

                // vnesemo tudi 1. grupo aka page
                $sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] 1', '1')");
                $grupa = mysqli_insert_id($GLOBALS['connect_db']);


                // Gremo cez vprasanja in jih dodamo
                if (isset($data['vprasanja']) && count($data['vprasanja']) > 0) {
                    foreach ($data['vprasanja'] as $vprasanje) {
                        $prasanjedata = array();
                        $prasanjedata['group_id'] = $grupa;
                        $prasanjedata['title'] = $vprasanje['besedilo_vprasanja'];
                        $prasanjedata['order'] = $vprasanje['mesto_vprasanja'];
                        $prasanjedata['type_code'] = $vprasanje['vrsta_vprasanja'];
                        if (isset($vprasanje['velikost_polja']))
                            $prasanjedata['taSize'] = $vprasanje['velikost_polja'];
                        $prasanjedata['reminder'] = $vprasanje['reminder'];
                        $prasanjedata['other'] = $vprasanje['other'];
                        $prasanjedata['options'] = $vprasanje['Odgovori'];

                        $this->createQuestion($anketa, $prasanjedata, false);
                    }
                }
            }
        }

        if ($anketa > 0) {
            // dodamo se uporabnika v dostop
            $sql = sisplet_query("INSERT INTO srv_dostop (ank_id, uid) VALUES ('$anketa', '$global_user_id')");

            //rabi se, da se naredi vrstica v tabeli srv_branching - na zacetku naredil, 
            //ker drugace ni delalo vredu kopiranje vrednosti pri kopiranju anket
            new Branching($anketa);

            $json_array['url'] = $url;
            $json_array['id'] = $anketa;

            $json_array['note'] = 'Survey created';
        } 
        else {
            $json_array['error'] = 'Error creating survey';
        }

        return $json_array;
    }

    /**
     * @api {post} https://www.1ka.si/api/createQuestion/survey/:id createQuestion
     * @apiName createQuestion
     * @apiGroup Questions and variables
     * @apiDescription Add new question to survey, put it on last spot of given group/page in survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey to add new question to
     * 
     * @apiParam (POST parameter) {String} title Text of question
     * @apiParam (POST parameter) {Number} [group_id] Id of page/group to put question in (default is last page/group)
     * @apiParam (POST parameter) {Number} type_code Type of question (0-radio, 1-checkbox, 2-text)
     * @apiParam (POST parameter) {Number} [reminder] Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder) (default is 0)
     * @apiParam (POST parameter) {String} [other] Text of option other to add (for cshoose type questions)
     * @apiParam (POST parameter) {Number} [taSize] Height size in lines of text field (for text question) (default is single line)
     * @apiParam (POST parameter) {String[]} [options] Array of options to add to question (for cshoose type questions)
     * @apiParamExample {json} Post-example (For choice-type): 
      {
	"question": {
			"title": "This is text of choice type question",
			"type_code": 1,
                        "group_id": 2027,
			"reminder": 0,
			"other": "Other:",
			"options": ["Text of option 1", "Text of option 2", "Text of option 3"]
		}	
      }
     * 
     * @apiParamExample {json} Post-example (For text-type): 
      {
	"question": {
			"title": "This is text of text type question",
			"type_code": 2,
                        "group_id": 2027,
			"reminder": 1,
			"taSize": 3
		}	
      }
     * 
     * @apiSuccess {String} que_id ID of new question
     * @apiSuccessExample {json} Success-Response:
     *     {"que_id":5056,"note":"Question created"}
     * 
     * @apiVersion 1.0.0
     */
    private function createQuestion($ank_id, $vprasanje, $prestevilci = true) {
        global $lang;
        $purifier = New Purifier();
        
        if (!isset($vprasanje['type_code'])){
            $json_array['error'] = 'Type code of question missing';
            return $json_array;
        }
        
        //ce ni nastavljene grupe, vprasanje postavimo kar v zadnjo
        if(!isset($vprasanje['group_id']) || !$vprasanje['group_id']){
            $sql = sisplet_query("SELECT id from srv_grupa WHERE ank_id='$ank_id' ORDER BY vrstni_red DESC LIMIT 1;", 'obj');
            if($sql->id)
                $grupa=$sql->id;
            //ce se ne obstaja grupa, jo kreiramo
            else{
                // vnesemo tudi 1. grupo aka page
                $sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$ank_id', '$lang[srv_stran] 1', '1')");
                $grupa = mysqli_insert_id($GLOBALS['connect_db']);
            }
        }
        else
            $grupa = $vprasanje['group_id'];
        
        //ce ni dolocen vrstni red za spremenljivko, jo postavi na zadnje mesto grupe
        if(!isset($vprasanje['order']) || !$vprasanje['order']){
            $sql = sisplet_query("SELECT vrstni_red from srv_spremenljivka WHERE gru_id='$grupa' ORDER BY vrstni_red DESC LIMIT 1;", 'obj');
            if($sql->vrstni_red)
                $vrstni_red=$sql->vrstni_red+1;
        }
        else
            $vrstni_red = $vprasanje['order'];
        
        $reminder = $vprasanje['reminder'] ? $vprasanje['reminder'] : 0;
        $other = $vprasanje['other'] ? $vprasanje['other'] : '';
        $variable = 'Q' . $vrstni_red;
        $title = (isset($vprasanje['title']) && $vprasanje['title'] != '') ? $purifier->purify_DB($vprasanje['title']) : $variable;
        $params = '';

        // checkbox
        if ($vprasanje['type_code'] == '1') {
            $type = 2;
            $size = (isset($vprasanje['options']) && count($vprasanje['options']) > 0) ? count($vprasanje['options']) : 3;
        }
        // textbox
        elseif ($vprasanje['type_code'] == '2') {
            $type = 21;
            $size = 1;
            if($vprasanje['taSize'])
                $params = 'taSize=' . $vprasanje['taSize'];
        }
        // radio
        else {
            $type = 1;
            $size = (isset($vprasanje['options']) && count($vprasanje['options']) > 0) ? count($vprasanje['options']) : 3;
        }

        // Vstavimo vprasanje
        $sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red, reminder, params) 
				VALUES ('', '$grupa', '<p>$title</p>', '$variable', '$size', '$type', '$vrstni_red', '$reminder', '$params')");
        $spremenljivka = mysqli_insert_id($GLOBALS['connect_db']);

        //vnesi vrednost samo za textbox
        if ($vprasanje['type_code'] == '2') {
            $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$spremenljivka', '" . $variable . "a', '1', '1')");
        }

        // Gremo cez posamezne vrednosti in jih dodamo
        if (isset($vprasanje['options']) && count($vprasanje['options']) > 0) {
            $cnt = 1;
            foreach ($vprasanje['options'] as $vrednost) {
                $vrednost = $purifier->purify_DB($vrednost);
                $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$spremenljivka', '$vrednost', '$cnt', '$cnt')");
                $cnt++;
            }
        }

        //dodamo se OTHER option, ce je nastavljeno
        if ($other != '') {
            $Vpr = new Vprasanje();
            $Vpr->vrednost_new($other, 1, null, $spremenljivka);
        }

        Common::getInstance()->updateEditStamp();
        Common::prestevilci($spremenljivka);
        if ($prestevilci)
            Common::prestevilci();

        //potrebno za branching, da se podatki osvezijo in posodobijo v tabeli srv_branching
        sisplet_query("UPDATE srv_anketa SET branching='0' WHERE id = '$ank_id'");
        //zazeni branching, da se podatki v tabeli srv_branching posodobijo
        new Branching($ank_id);

        $json_array['note'] = 'Question created';
        $json_array['que_id'] = $spremenljivka;
        return $json_array;
    }
    
    /**
     * @api {post} https://www.1ka.si/api/updateQuestion/survey/:id updateQuestion
     * @apiName updateQuestion
     * @apiGroup Questions and variables
     * @apiDescription Update basic question properties
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} id_que ID of question
     * @apiParam (POST parameter) {String} [title] Title/text of question
     * @apiParam (POST parameter) {String} [reminder] Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder)
     * @apiParam (POST parameter) {String} [other] Text of option "Other" (update or add)
     * @apiParamExample {json} Post-example: 
      {
	"question": {
            "id_que": "8487",
            "title": "Which city you like most?",
            "reminder": "1",
            "other": "Other:"
	}
      }
     * 
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Question updated"}
     * 
     * @apiVersion 1.0.0
     */
    private function updateQuestion($ank_id, $vprasanje) {
        $purifier = New Purifier();

        if($this->isQuestionSiblingOfSurvey($ank_id, $vprasanje['id_que'])){
            if(!isset($vprasanje['title']) && !isset($vprasanje['reminder']) && !isset($vprasanje['other']) && !isset($vprasanje['taSize'])){
                $json_array['error'] = 'Nothing to update';
                return $json_array;
            }
            else{
                $query = "UPDATE srv_spremenljivka SET";

                if (isset($vprasanje['title']))
                    $query .= " naslov='<p>" . $purifier->purify_DB($vprasanje['title']) . "</p>',";

                if (isset($vprasanje['reminder']))
                    $query .= " reminder='" . $vprasanje['reminder'] . "',";

                $other = isset($vprasanje['other']) ? $vprasanje['other'] : null;
                $id_spr = $vprasanje['id_que'];

                //v dokumentaciji to izpustimo, ker pobrise vse druge parametre (v aplikaciji pa se to vseeno uporablja)
                if (isset($vprasanje['taSize']))
                    $query .= " params='taSize=" . $vprasanje['taSize'] . "',";

                $sql = sisplet_query(substr($query, 0, -1) . " WHERE id='$id_spr';");
                if (!$sql)
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);

                // Gremo cez posamezne vrednosti in jih dodamo
                /* if(count($vprasanje['Odgovori']) > 0){
                  $cnt = 1;
                  foreach($vprasanje['Odgovori'] as $vrednost){
                  $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red)
                  VALUES ('', '$spremenljivka', '$vrednost', '$cnt', '$cnt')");
                  $cnt++;
                  }
                  }
                  else{
                  for($i=1; $i<=$size; $i++){
                  $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$spremenljivka', '$i', '$i', '$i')");
                  }
                  }
                 */

                //ce se posodobi other
                if ($other !== null) {
                    $sql = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_vrednost WHERE spr_id='$id_spr' AND other='1';");
                    $otherV = mysqli_fetch_assoc($sql);

                    //other obstaja za to spremenljivko
                    if ($otherV) {
                        //samo spremeni title
                        if ($other != '' && $otherV['naslov'] != $other) {
                            sisplet_query("UPDATE srv_vrednost SET naslov='" . $purifier->purify_DB($other) . "' WHERE id='" . $otherV['id'] . "';");
                        }
                        //delete other
                        elseif ($other == '') {
                            $this->deleteOption($ank_id, $otherV['id']);
                        }
                    }
                    //other ne obstaja za to spremenljivko
                    else {
                        //se ni other v tej spremenljivki, dodaj ga
                        if ($other != '') {
                            $Vpr = new Vprasanje();
                            $Vpr->vrednost_new($other, 1, null, $id_spr);
                            Common::prestevilci($id_spr);
                        }
                    }
                }

                Common::getInstance()->Init($ank_id);
                Common::getInstance()->updateEditStamp();

                $json_array['note'] = 'Question updated';
            }
        }
        else{
            $json_array['error'] = 'Question does not exist or not belong to this survey';
        }
        return $json_array;
    }

    /**
     * @api {post} https://www.1ka.si/api/updateSurvey/survey/:id updateSurvey
     * @apiName updateSurvey
     * @apiGroup Surveys
     * @apiDescription Update basic survey properties
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} [title] Title of survey
     * @apiParam (POST parameter) {String} [que_title] Title/text of question (only voting)
     * @apiParam (POST parameter) {String} [introduction] Introduction text of survey or form ("" stands for default text)
     * @apiParam (POST parameter) {String} [conclusion] Conclusion text of survey or form ("" stands for default text)
     * @apiParam (POST parameter) {String} [show_intro] Do we show introduction (0-hide, 1-show)
     * @apiParam (POST parameter) {String} [show_concl] Do we show conclusion (0-hide, 1-show)
     * @apiParamExample {json} Post-example (survey or form): 
      {
        "title":"A survey",
        "introduction":"",
        "conclusion":"Thank you!",
        "show_intro":"1",
        "show_concl":"1"
      }
     * @apiParamExample {json} Post-example (voting): 
      {
        "title":"Weekly voting",
        "que_title":"What is your vote?"
      }
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Survey updated"}
     * 
     * @apiVersion 1.0.0
     */
    private function updateSurvey($ank_id, $data) {
        $purifier = New Purifier();
        
        //get survey type
        $sqlV = sisplet_query("SELECT survey_type FROM srv_anketa WHERE id='" . $ank_id . "'");

        //voting
        if (mysqli_fetch_assoc($sqlV)['survey_type'] == 0) {
            if(isset($data['title'])){
                $sql = sisplet_query("UPDATE srv_anketa SET naslov='" . $purifier->purify_DB($data['title']) . "', akronim='" . $purifier->purify_DB($data['title']) . "' WHERE id=" . $ank_id . ";");
                if (!$sql)
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
            }

            if (isset($data['que_title'])){
                //get id of question of voting
                $sqlV = sisplet_query("SELECT s.id AS id FROM srv_anketa a, srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . $ank_id . "' AND s.gru_id=g.id;");

                $sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='<p>" . $purifier->purify_DB($data['que_title']) . "</p>' WHERE id='" . mysqli_fetch_assoc($sqlV)['id'] . "';");
                if (!$sql)
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
            }
            
            if(!isset($data['title']) && !isset($data['que_title'])){
                $json_array['note'] = 'Nothing to update';
            }
            else {
                $json_array['note'] = 'Survey updated';
                Common::getInstance()->updateEditStamp();
            }
        }

        //questionaire or form
        else {
            $set_query = "";
            if (isset($data['title']))
                $set_query .= "naslov='" . $purifier->purify_DB($data['title']) . "', akronim='" . $purifier->purify_DB($data['title']) . "',";
            if (isset($data['introduction']))
                $set_query .= "introduction='" . $purifier->purify_DB($data['introduction']) . "',";
            if (isset($data['conclusion']))
                $set_query .= "conclusion='" . $purifier->purify_DB($data['conclusion']) . "',";
            if (isset($data['show_intro']))
                $set_query .= "show_intro='" . $data['show_intro'] . "',";
            if (isset($data['show_concl']))
                $set_query .= "show_concl='" . $data['show_concl'] . "',";
            
            if(!$set_query){
                $json_array['note'] = 'Nothing to update';
            }
            else{
                $sql = sisplet_query("UPDATE srv_anketa SET " . substr($set_query, 0, -1) . " WHERE id=" . $ank_id . ";");
                if (!$sql)
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
                else
                    $json_array['note'] = 'Survey updated';
            }
        }

        return $json_array;
    }

    private function getQuestionIdOfOption($option_id = null) {
        if ($option_id != null) {
            $sqlV = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id='" . $option_id . "'");
            $rowV = mysqli_fetch_assoc($sqlV);
            return $rowV['spr_id'];
        }
        return;
    }

    /**
     * @api {post} https://www.1ka.si/api/updateOrCreateOption/survey/:id updateOrCreateOption
     * @apiName updateOrCreateOption
     * @apiGroup Questions and variables
     * @apiDescription Update or add a value/option to question (for picking type of question - single or multiple choice)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} option_text Title/text of option/value
     * @apiParam (POST parameter) {String} [que_id] ID of question to add new option/value (needed only for adding)
     * @apiParam (POST parameter) {String} [option_id] ID of option/value to update (needed only for updating)
     * @apiParamExample {json} Post-example (adding): 
      {
        "option_text":"First option",
        "que_id":"3894"
      }
     * @apiSuccessExample {json} Success-Response (adding):
     *     {"note":"Option added","opt_id":9619}
     * 
     * @apiParamExample {json} Post-example (updating): 
      {
        "option_text":"First option",
        "option_id":"9618"
      }
     * @apiSuccessExample {json} Success-Response (updating):
     *     {"note":"Option updated","opt_id":"9618"}
     * 
     * @apiVersion 1.0.0
     */
    private function updateOrCreateOption($ank_id, $data) {
        $purifier = New Purifier();
 
        if (!isset($data['option_text'])){
                $json_array['error'] = 'Option text missing';
                return $json_array;
        }
            
        $naslov = $purifier->purify_DB($data['option_text']);

        if (!isset($data['option_id']) || $data['option_id'] == '') {
            if (!isset($data['que_id'])){
                $json_array['error'] = 'Question ID missing';
                return $json_array;
            }
            if($this->isQuestionSiblingOfSurvey($ank_id, $data['que_id'])){
                $json_array = $this->addQuestionVrednost($ank_id, $data['que_id'], array('naslov' => $data['option_text']), true);
            }
            else {
                $json_array['error'] = "Question does not exist or does not belong to this survey";
            }
        } else {
            $spr_id = $this->getQuestionIdOfOption($data['option_id']);
            if($this->isQuestionSiblingOfSurvey($ank_id, $spr_id)){
                $s = sisplet_query("UPDATE srv_vrednost SET naslov='" . $naslov . "' WHERE id = '" . $data['option_id'] . "'");
                if (!$s)
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
                else {
                    Common::getInstance()->updateEditStamp();
                    Common::prestevilci($this->getQuestionIdOfOption($data['option_id']));

                    $json_array['note'] = 'Option updated';
                    $json_array['opt_id'] = $data['option_id'];
                }
            }
            else{
                $json_array['error'] = "Option does not exist or does not belong to this survey";
            }
        }
        return $json_array;
    }

    /**
     * @api {delete} https://www.1ka.si/api/deleteOption/survey/:id deleteOption
     * @apiName deleteOption
     * @apiGroup Questions and variables
     * @apiDescription Delete option/value of question (for picking type of question - single or multiple choice)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} option_id ID of option/value to delete
     * @apiParamExample {json} Post-example: 
            {"option_id":"424"}
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Option deleted"}
     * 
     * @apiVersion 1.0.0
     */
    private function deleteOption($ank_id, $option_id) {
        $spr_id = $this->getQuestionIdOfOption($option_id);

        if($this->isQuestionSiblingOfSurvey($ank_id, $spr_id)){
            $Vpr = new Vprasanje();
            if ($Vpr->ajax_vrednost_delete($option_id, true)['error'] == 0) {
                $json_array['note'] = 'Option deleted';
                Common::getInstance()->updateEditStamp();
                Common::prestevilci($spr_id);
            } else
                $json_array['error'] = 'Error has occurred';
        }
        else {
            $json_array['error'] = 'Option does not exist or does not belong to this survey';
        }

        return $json_array;
    }
    
    /**
     * @api {delete} https://www.1ka.si/api/deleteSurvey/survey/:id deleteSurvey
     * @apiName deleteSurvey
     * @apiGroup Surveys
     * @apiDescription Delete survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey to delete
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Survey deleted"}
     * 
     * @apiVersion 1.0.0
     */
    private function deleteSurvey($ank_id) {
        $SM = new SurveyAdmin();
        $SM->anketa_delete($ank_id);
        $json_array['note'] = 'Survey deleted';

        return $json_array;
    }
    
    /**
     * @api {post} https://www.1ka.si/api/addLink/survey/:id addLink
     * @apiName addLink
     * @apiGroup Data and analysis
     * @apiDescription Add new public link (hash link of data or analysis). Example of hash (public) link: https://www.1ka.si/podatki/50/5BABEC6D/ ([SITE_ROOT]/podatki/[SURVEY_ID]/[HASH_CODE]/)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} a Broad type of content of hash link (analysis, data) (if data, parameter m is not needed)
     * @apiParam (POST parameter) {String} m Specific type of content of hash link (analysis_creport, descriptor, frequency, charts, sumarnik) (when parameter a is "data", this parameter is not needed)
     * @apiParamExample {json} Post-example: 
            {"a":"analysis", "m":"frequency"}
     * 
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Link added"}
     * 
     * @apiVersion 1.0.0
     */
    private function addLink($ank_id, $data){        
        global $global_user_id;
        
        $hashUrl = new SurveyUrlLinks($ank_id);
        $hashUrl->addLinkAPI($global_user_id, $data['a'], $data['m']);

        $json_array['note'] = 'Link added';
        
        return $json_array;
    }
    
    /**
     * @api {delete} https://www.1ka.si/api/deleteLink/survey/:id deleteLink
     * @apiName deleteLink
     * @apiGroup Data and analysis
     * @apiDescription Delete specific public link (hash link of data or analysis)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} hash Hash code of public link to delete
     * @apiParamExample {json} Post-example: 
            {"hash":"5BABEC6D"}
     * 
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Link deleted"}
     * 
     * @apiVersion 1.0.0
     */
    private function deleteLink($ank_id, $hash){     
        $sql = "SELECT anketa FROM srv_hash_url WHERE hash='$hash';";
        $que = sisplet_query($sql, 'obj');
        
        if($que->anketa == $ank_id){
            $hashUrl = new HashUrl($ank_id);
            $hashUrl->deleteLink($hash);

            $json_array['note'] = 'Link deleted';
        }
        else{
            $json_array['error'] = 'Hash does not exist or does not belong to this survey';
        }
     
        
        return $json_array;
    }
    
    /**
     * @api {delete} https://www.1ka.si/api/deleteQuestion/survey/:id deleteQuestion
     * @apiName deleteQuestion
     * @apiGroup Questions and variables
     * @apiDescription Delete question
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {Number} que_id ID of question to delete
     * @apiParamExample {json} Post-example: 
            {"que_id":4240}
     * 
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Question deleted"}
     * 
     * @apiVersion 1.0.0
     */
    private function deleteQuestion($ank_id, $que_id) {
        if($this->isQuestionSiblingOfSurvey($ank_id, $que_id)){
            $sa = new SurveyAdmin();
            $sa->brisi_spremenljivko($que_id);

            Common::getInstance()->updateEditStamp();
            Common::prestevilci();
            $json_array['note'] = 'Question deleted';
        }
        else {
            $json_array['error'] = 'Question does not exist or not belong to this survey';
        }

        return $json_array;
    }
    
    /**
     * @api {put} https://www.1ka.si/api/copySurvey/survey/:id copySurvey
     * @apiName copySurvey
     * @apiGroup Surveys
     * @apiDescription Make a copy of specific survey
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey to copy
     * 
     * @apiSuccess {Number} id ID of new survey
     * 
     * @apiSuccessExample {json} Success-Response:
     *     {"id":5194,"note":"Survey copied"}
     * 
     * @apiVersion 1.0.0
     */
    private function copySurvey($ank_id) {
        $lib = new Library();
        $json_array['id'] = $lib->ajax_anketa_copy_new($ank_id);
        $json_array['note'] = 'Survey copied';

        return $json_array;
    }
    
    /**
     * @api {post} https://www.1ka.si/api/copyQuestion/survey/:id copyQuestion
     * @apiName copyQuestion
     * @apiGroup Questions and variables
     * @apiDescription Make a copy of specific question and put it +1 in order to original question on same page
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {Number} que_id ID of question to copy
     * @apiParamExample {json} Post-example: 
            {"que_id":12240}
     * 
     * @apiSuccess {Number} que_id ID of new question
     * @apiSuccessExample {json} Success-Response:
     *     {"id":12831,"note":"Question copied"}
     * 
     * @apiVersion 1.0.0
     */
    private function copyQuestion($ank_id, $que_id) {
        if($this->isQuestionSiblingOfSurvey($ank_id, $que_id)){
            $ba = new BranchingAjax($ank_id);
            $new_id = $ba->spremenljivka_new($que_id, 0, 0, $que_id);
            $json_array['que_id'] = $new_id;
            $json_array['note'] = 'Question copied';

            Common::getInstance()->updateEditStamp();
            Common::prestevilci();
        }
        else {
            $json_array['error'] = 'Question does not exist or not belong to this survey';
        }

        return $json_array;
    }
    
    /**
     * Check if question belongs to survey
     * @param type $ank_id - id of survey
     * @param type $que_id - id of question
     * @return boolean Does question belongs this survey
     */
    private function isQuestionSiblingOfSurvey($ank_id, $que_id){
                $sql = "SELECT s.gru_id, gru.ank_id FROM srv_spremenljivka as s "
                        . "LEFT JOIN (SELECT id, ank_id FROM srv_grupa) AS gru ON gru.id = gru_id "
                        . "WHERE s.id='$que_id';";
                $res = sisplet_query($sql, 'obj');
                return $res->ank_id == $ank_id;
    }
    
    /**
     * @api {delete} https://www.1ka.si/api/deleteSurveyUnit/survey/:id deleteSurveyUnit
     * @apiName deleteSurveyUnit
     * @apiGroup Data and analysis
     * @apiDescription Delete unit/response in survey data (whole response of a respondent)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {String} srv_unit_id ID of unit/response to delete
     * @apiParamExample {json} Post-example: 
            {"srv_unit_id":"12774"}
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Survey unit deleted"}
     * 
     * @apiVersion 1.0.0
     */
    private function deleteSurveyUnit($ank_id, $unit_id) {
        $sql = "SELECT ank_id FROM srv_user WHERE id='$unit_id';";
        $que = sisplet_query($sql, 'obj');
        if($que->ank_id == $ank_id){
            $sql = "DELETE FROM srv_user WHERE id='$unit_id' AND ank_id='$ank_id';";
            if(sisplet_query($sql))
                $json_array['note'] = 'Survey unit deleted';
            else 
                $json_array['error'] = 'Error has occurred';
        }
        else{
            $json_array['error'] = 'Survey unit does not exist or does not belong to this survey';
        }
        return $json_array;
    }

    // Doda vrednost v vprasanje
    // Rather use updateOrCreateOption!!!!!!!!!!!!!!!!!
    private function addQuestionVrednost($ank_id, $spr_id, $data, $other_to_last = false) {
        global $lang;
        global $site_url;
        global $global_user_id;
        $purifier = New Purifier();

        $json_array = array();

        // Preverimo ce imamo osnovne podatke za dodajanje vrednosti
        if (!isset($data['naslov'])) {
            $json_array['error'] = 'Option title missing';
            return $json_array;
        }

        // Preverimo ce obstaja vprasanje
        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.id='" . $spr_id . "' AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "'");
        if (mysqli_num_rows($sql) > 0) {

            // Dobimo ustrezen vrstni red
            $sqlV = sisplet_query("SELECT v.vrstni_red AS vrstni_red, v.other AS other, v.id AS id FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.spr_id='" . $spr_id . "' AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "' ORDER BY v.vrstni_red DESC LIMIT 1");
            $rowV = mysqli_fetch_assoc($sqlV);

            //ce je other zadnji, premakni other za eno naprej po vrsnm redu, in novo vrednost na mesto other
            if ($other_to_last && $rowV['other'] == 1) {
                $vrstni_red = (int) $rowV['vrstni_red'];

                //premakni other
                $s = sisplet_query("UPDATE srv_vrednost SET vrstni_red='" . ($vrstni_red + 1) . "' WHERE id = '" . $rowV['id'] . "'");
                if (!$s) {
                    $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
                    return $json_array;
                }
            } else
                $vrstni_red = (int) $rowV['vrstni_red'] + 1;

            $title = $purifier->purify_DB($data['naslov']);

            // Vstavimo vrednost v vprasanje
            $sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, naslov2, vrstni_red) VALUES ('', '$spr_id', '$title', '$title', '$vrstni_red')");
            $opt_id = mysqli_insert_id($GLOBALS['connect_db']);

            // Prestevilcimo da se nastavi variabla
            //Common::getInstance()->Init($ank_id);
            Common::prestevilci($spr_id);
            Common::getInstance()->updateEditStamp();

            $json_array['note'] = 'Option added';
            $json_array['opt_id'] = $opt_id;
            return $json_array;
        }
        else {
            $json_array['error'] = 'Question does not exist';
            return $json_array;
        }
    }

    /**
     * @api {post} https://www.1ka.si/api/SurveyActivation/survey/:id SurveyActivation
     * @apiName SurveyActivation
     * @apiGroup Surveys
     * @apiDescription Activate (for 3 months from now) or deactivate survey (start it or stop it)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {Number} [active] If this parameter is not set, survey will be deactivated (0-deactivate survey, 1-activate survey)
     * @apiParamExample {json} Post-example: 
            {"active":1}
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"Survey activity changed"}
     * 
     * @apiVersion 1.0.0
     */
    private function SurveyActivation($ank_id, $data) {
        global $global_user_id;

        $active = (isset($data['active'])) ? $data['active'] : 0;

        $starts = "NOW()";
        $expire = "NOW() + INTERVAL 3 MONTH";
        $updateActiveTime = ", starts=$starts, expire=$expire";

        if ($active == 1) {
            $activity_insert_string = "INSERT INTO srv_activity (sid, starts, expire, uid) VALUES('" . $ank_id . "', $starts, $expire, '" . $global_user_id . "' )";
            $sql_insert = sisplet_query($activity_insert_string);
            //ignoriraj erorror, ce ze obstaja identicna vrstica
            /* if (!$sql_insert)
              $json_array['error'] = mysqli_error($GLOBALS['connect_db']); */
        }

        $sql = sisplet_query("UPDATE srv_anketa SET active=" . $active
                . ($active == 1 ? $updateActiveTime : '') . " WHERE id=" . $ank_id . ";");

        if (!$sql) {
            $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
        } else {
            if (!isset($json_array['error']))
                $json_array['note'] = 'Survey activity changed';
        }

        return $json_array;
    }

    // Blokiraj ponoven IP (trenutno samo za 24ur)
    /**
     * @api {post} https://www.1ka.si/api/BlockRepeatedIP/survey/:id BlockRepeatedIP
     * @apiName BlockRepeatedIP
     * @apiGroup Surveys
     * @apiDescription Block repeated IP (do not allow respondent to respond to survey again for the next x minutes)
     * 
     * @apiHeader {String} identifier Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)
     * @apiHeader {String} token SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)
     * @apiHeaderExample {json} Request-Example:
            { "identifier": "abcdefgh01234567",
              "token": "bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh" }
     * 
     * @apiParam (GET parameter) {Number} id Id of survey
     * 
     * @apiParam (POST parameter) {Number} [blockIP] In minutes - if this parameter is not set, blocking IP will be turned off (possible options are 10, 20, 30, 60, 720, 1440, 0-ip blocking off)
     * @apiParamExample {json} Post-example: 
            {"blockIP":1440}
     * @apiSuccessExample {json} Success-Response:
     *     {"note":"IP blocking changed"}
     * 
     * @apiVersion 1.0.0
     */
    private function BlockRepeatedIP($ank_id, $data) {

        $blockIP = (isset($data['blockIP'])) ? $data['blockIP'] : 0;

        $sql = sisplet_query("UPDATE srv_anketa SET block_ip=" . $blockIP
                . " WHERE id=" . $ank_id . ";");
        if (!$sql) {
            $json_array['error'] = mysqli_error($GLOBALS['connect_db']);
        } else {
            $json_array['note'] = 'IP blocking changed';
        }

        return $json_array;
    }

    // Poslje email vabilo novemu respondentu
    private function sendEmailInvitation($ank_id, $data) {
        global $lang;
        global $global_user_id;
        global $admin_type;

        $json_array = array();

        // Preverimo ce sploh imamo vklopljena vabila
        $isEmail = (int) SurveyInfo::getInstance()->checkSurveyModule('email');
        $d = new Dostop();
        if (!((int) $isEmail > 0)) {

            $json_array['error'] = 'Invitations are not enabled for this survey!';
            return $json_array;

            exit();
        }

        $email = (isset($data['email'])) ? $data['email'] : '';
        $firstname = (isset($data['firstname'])) ? $data['firstname'] : '';
        $lastname = (isset($data['lastname'])) ? $data['lastname'] : '';
		
		// Opcijski dodatni parametri, ki jih lahko dodamo url-ju na anketo
        $param_string = (isset($data['param_string'])) ? $data['param_string'] : '';
		
		// Opcijsko nastavimo tudi cas poteka vabila
		if(isset($data['expired']) && is_numeric($data['expired'])){		
			$expired = date('Y-m-d H:i:s', strtotime("+".$data['expired']." days"));
		}
		else{
			$expired = '0000-00-00 00:00:00';
		}

        // Zaenkrat so vsi 3 parametri obvezni
        if ($email != '' && $firstname != '' && $lastname != '') {

            // Preverimo ce obstajajo vse 3 sistemske spremenljivke
            $sqlVariable = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE (s.variable='email' OR s.variable='ime' OR s.variable='priimek') AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "'");
            if (mysqli_num_rows($sqlVariable) != 3) {

                $json_array['error'] = 'Missing system variables (variables email, ime and priimek must exist in survey)!';
            } else {
                $SI = new SurveyInvitationsNew($ank_id);

                // polovimo sistemske spremenljivke z vrednostmi
                $qrySistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "' AND variable IN('email', 'ime', 'priimek') ORDER BY g.vrstni_red, s.vrstni_red");
                $sys_vars = array();
                $sys_vars_ids = array();
                while ($row = mysqli_fetch_assoc($qrySistemske)) {
                    $sys_vars[$row['id']] = array('id' => $row['id'], 'variable' => $row['variable'], 'naslov' => $row['naslov']);
                    $sys_vars_ids[] = $row['id'];
                }
                $sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id, vrstni_red, variable FROM srv_vrednost WHERE spr_id IN(" . implode(',', $sys_vars_ids) . ") ORDER BY vrstni_red ASC ");
                while ($row = mysqli_fetch_assoc($sqlVrednost)) {
                    $sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
                }

                $list_id = '';

                // Generiramo kodo za respondenta
                // katera gesla (code) že imamo v bazi za to anketo
                $password_in_db = array();
                $sql_query = sisplet_query("SELECT password FROM srv_invitations_recipients WHERE ank_id='" . $ank_id . "' AND deleted = '0'");
                while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                    $password_in_db[$sql_row['password']] = $sql_row['password'];
                }
                // Izberemo random hash, ki se ni v bazi
                do {
                    list($code, $cookie) = $SI->generateCode();
                } while (in_array($code, $password_in_db));


                // VSTAVIMO RESPONDENTA V SEZNAM
                $sql_insert_start = sisplet_query("INSERT INTO srv_invitations_recipients 
										(ank_id, email, firstname, lastname, password, cookie, sent, responded, unsubscribed, deleted, date_inserted, date_expired, inserted_uid, list_id) 
										VALUES 
										('" . $ank_id . "', '" . $email . "', '" . $firstname . "', '" . $lastname . "', '" . $code . "', '" . $cookie . "', '0', '0', '0', '0', NOW(), ".$expired.", '" . $global_user_id . "', '" . $list_id . "')");
                $rec_id = mysqli_insert_id($GLOBALS['connect_db']);


                // polovimo sporočilo in prejemnike
                $sql_query_m = sisplet_query("SELECT id, subject_text, body_text, reply_to, isdefault, comment, naslov, url FROM srv_invitations_messages WHERE ank_id = '" . $ank_id . "' AND isdefault='1'");
                if (mysqli_num_rows($sql_query_m) > 0) {
                    $sql_row_m = mysqli_fetch_assoc($sql_query_m);
                } else {
                    // Nimamo še vsebine sporočila
                    $json_array['error'] = 'Email server settings and message not set!';
                    return $json_array;

                    exit();
                }

                // Kreiramo mail
                $subject_text = $sql_row_m['subject_text'];
                $body_text = $sql_row_m['body_text'];

                // Naslov za odgovor je avtor ankete
                if ($SI->validEmail($sql_row_m['reply_to'])) {
                    $reply_to = $sql_row_m['reply_to'];
                } else {
                    $reply_to = Common::getInstance()->getReplyToEmail();
                }

                # če mamo SEO
                $nice_url = SurveyInfo::getSurveyLink();

                $date_sent = date("Y-m-d H:i:s");
                $msg_url = $sql_row_m['url'];

                # odvisno ali imamo url za jezik.
                if ($msg_url != null && trim($msg_url) != '') {
                    $url = $msg_url . '?code=' . $code;
                } else {
                    $url = $nice_url . '&code=' . $code;
                }

                $url .= '&ai=' . (int) $arch_id;
				
				// URL-ju dodamo se opcijske dodatne parametre ce so nastavljeni
				$url .= '&'.$param_string;
				
                #odjava
                $unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $ank_id . '&code=' . $code;


                // VSTAVIMO POSILJANJE V ARHIV
                $arvhive_naslov = 'mailing_' . date("d.m.Y") . ', ' . date("H:i:s");
                $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive 
											(id, ank_id, date_send, subject_text, body_text, uid, comment, naslov, rec_in_db)
											VALUES 
											(NULL, '$ank_id', '$date_sent', '$subject_text', '$body_text', '$global_user_id', '', '$arvhive_naslov', '1')");
                $arch_id = mysqli_insert_id($GLOBALS['connect_db']);


                $user_body_text = str_replace(
                        array(
                    '#URL#',
                    '#URLLINK#',
                    '#UNSUBSCRIBE#',
                    '#FIRSTNAME#',
                    '#LASTNAME#',
                    '#EMAIL#',
                    '#CODE#',
                    '#PASSWORD#'
                        ), array(
                    '<a href="' . $url . '">' . $url . '</a>',
                    $url,
                    '<a href="' . $unsubscribe . '">' . $lang['user_bye_hl'] . '</a>',
                    $firstname,
                    $lastname,
                    $email,
                    $code,
                    $code
                        ), $body_text
                );


                // POSLJEMO MAIL
                $resultX = null;
                try {
                    $MA = new MailAdapter($ank_id, $type='invitation');
                    $MA->addRecipients($email);
                    $resultX = $MA->sendMail($user_body_text, $subject_text);
                } catch (Exception $e) {
                    // todo fajn bi bilo zalogirat kaj se dogaja
                    $__error = $e->getMessage();
                    $__errStack = $e->getTraceAsString();
                }


                // Vabilo OK poslano
                if ($resultX) {
                    // Updatamo prejemnika - status in sent
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET sent='1', date_sent='" . $date_sent . "', last_status='1' WHERE id='" . $rec_id . "'");

                    // Updatamo se arhiv
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='1', cnt_error='0' WHERE id='" . $arch_id . "'");

                    // Updatamo arhiv prejemnikov
                    $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ('" . $arch_id . "','" . $rec_id . "','1')");

                    // Updatamo tracking
                    $sqlQueryTracking = sisplet_query("INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ('" . $arch_id . "',NOW(),'" . $rec_id . "','1')");


                    // Dodamo userje v bazo
                    $sqlUserInsert = sisplet_query("INSERT INTO srv_user 
									(ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
									VALUES 
									('" . $ank_id . "', '" . $email . "', '" . $cookie . "', '" . $code . "', '1', NOW(), '" . $rec_id . "') ON DUPLICATE KEY UPDATE cookie = '" . $cookie . "', pass='" . $code . "'");
                    $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                    if ($usr_id) {
                        // vstavimo v srv_userbase
                        sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('" . $usr_id . "','0',NOW(),'" . $global_user_id . "')");

                        // vstavimo v srv_userstatus
                        sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('" . $usr_id . "', '0', '0', NOW())");

                        // vstavimo v srv_data_text (email, ime, priimek)
                        SurveyInfo::getInstance()->SurveyInit($ank_id);
                        $db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
                        foreach ($sys_vars AS $sid => $spremenljivka) {
                            if ($spremenljivka['variable'] == 'email')
                                sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $email . "', '" . $usr_id . "')");
                            elseif ($spremenljivka['variable'] == 'ime')
                                sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $firstname . "', '" . $usr_id . "')");
                            elseif ($spremenljivka['variable'] == 'priimek')
                                sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $lastname . "', '" . $usr_id . "')");
                        }
                    }
                    else {
                        // lahko da user že obstaja in je šlo za duplicated keys
                    }

                    $json_array['note'] = 'Email succesfully sent.';
                }
                // Vabilo ni bilo poslano
                else {
                    // Updatamo prejemnika - status in sent
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status='2' WHERE id='" . $rec_id . "'");

                    // Updatamo se arhiv
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='0', cnt_error='1' WHERE id='" . $arch_id . "'");

                    // Updatamo arhiv prejemnikov
                    $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ('" . $arch_id . "','" . $rec_id . "','0')");

                    // Updatamo tracking
                    $sqlQueryTracking = sisplet_query("INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ('" . $arch_id . "',NOW(),'" . $rec_id . "','2')");


                    // Dodamo userje v bazo
                    $sqlUserInsert = sisplet_query("INSERT INTO srv_user 
									(ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
									VALUES 
									('" . $ank_id . "', '" . $email . "', '" . $cookie . "', '" . $code . "', '2', NOW(), '" . $rec_id . "') ON DUPLICATE KEY UPDATE cookie = '" . $cookie . "', pass='" . $code . "'");
                    $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                    if ($usr_id) {
                        // vstavimo v srv_userbase
                        sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('" . $usr_id . "','0',NOW(),'" . $global_user_id . "')");

                        // vstavimo v srv_userstatus
                        sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('" . $usr_id . "', '0', '0', NOW())");

                        // vstavimo v srv_data_text (email, ime, priimek)
                        SurveyInfo::getInstance()->SurveyInit($ank_id);
                        $db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
                        foreach ($sys_vars AS $sid => $spremenljivka) {
                            if ($spremenljivka['variable'] == 'email') {
                                $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $email . "', '" . $usr_id . "')");
                            } elseif ($spremenljivka['variable'] == 'ime') {
                                $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $firstname . "', '" . $usr_id . "')");
                            } elseif ($spremenljivka['variable'] == 'priimek') {
                                $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $lastname . "', '" . $usr_id . "')");
                            }
                        }
                    } else {
                        // lahko da user že obstaja in je šlo za duplicated keys
                    }

                    $json_array['error'] = 'Email sending error!';
                }
            }
        }
        // Manjkajo parametri (email, firstname, lastname)
        else {
            $json_array['error'] = 'Missing parameters (email, firstname and lastname are mandatory)!';
        }

        return $json_array;
    }

    // Doda novo skupino
    private function addGroup($ank_id, $data) {
        global $lang;
        global $global_user_id;
        global $admin_type;
        global $site_path;

        $json_array = array();

        // Naslov skupine je obvezen
        $group_naslov = (isset($data['title'])) ? $data['title'] : '';
        if ($group_naslov != '') {

            $ss = new SurveySkupine($ank_id);
            $spr_id = $ss->hasSkupine();

            // Na zacetku moramo ustvarit najprej vprasanje
            if ($spr_id == 0) {

                $sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$ank_id' AND vrstni_red='1'");
                $rowG = mysqli_fetch_array($sqlG);
                $gru_id = $rowG['id'];

                $b = new Branching($ank_id);
                $spr_id = $b->nova_spremenljivka($grupa = $gru_id, $grupa_vrstni_red = 1, $vrstni_red = 0);

                $sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='" . $lang['srv_skupina'] . "', variable='" . strtolower($lang['srv_skupina']) . "', variable_custom='1', skupine='1', sistem='1', visible='0', size='0' WHERE id='$spr_id'");

                Vprasanje::change_tip($spr_id, 1);
            }

            // Ustvarimo vrednost v vprasanju
            $v = new Vprasanje($ank_id);
            $v->spremenljivka = $spr_id;
            $vre_id = $v->vrednost_new($group_naslov);

            // Preverimo ce imamo nice URL -> dodamo dodatnega za skupine
            $sql = sisplet_query("SELECT * FROM srv_nice_links WHERE ank_id='$ank_id'");
            if (mysqli_num_rows($sql) > 0) {

                Common::updateEditStamp();

                $row = mysqli_fetch_array($sql);

                $add = false;

                $anketa = $ank_id;
                $nice_url = $row['link'];

                $sql2 = sisplet_query("SELECT variable, vrstni_red FROM srv_vrednost WHERE id='$vre_id'");
                $row2 = mysqli_fetch_array($sql2);
                $nice_url .= '_' . $row2['vrstni_red'];

                $f = @fopen($site_path . '.htaccess', 'rb');
                if ($f !== false) {
                    $add = true;
                    while (!feof($f)) {
                        $r = fgets($f);
                        if (strpos($r, "^" . $nice_url . '\b') !== false) {  // preverimo, da ni tak redirect ze dodan
                            $add = false;
                        }
                    }
                    fclose($f);
                }

                if (strlen($nice_url) < 3)
                    $add = false;

                if (SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
                    $link = 'main/survey/uporabnost.php?anketa=' . SurveyInfo::getInstance()->getSurveyHash() . '&skupina=' . $vre_id;
                else
                    $link = 'main/survey/index.php?anketa=' . SurveyInfo::getInstance()->getSurveyHash() . '&skupina=' . $vre_id;

                if ($add) {
                    $f = @fopen($site_path . '.htaccess', 'a');
                    if ($f !== false) {
                        fwrite($f, "\nRewriteRule ^" . $nice_url . '\b(.*)			' . $link . "&foo=\$1&%{QUERY_STRING}");
                        fclose($f);

                        $sqlI = sisplet_query("INSERT INTO srv_nice_links_skupine (id,ank_id,nice_link_id,vre_id,link) VALUES ('','$ank_id','$row[id]','$vre_id','$nice_url')");
                    }
                }
            }

            $sqlVrednost = sisplet_query("SELECT variable FROM srv_vrednost WHERE id='" . $vre_id . "'");
            $rowVrednost = mysqli_fetch_array($sqlVrednost);

            // Vrnemo grupo
            $json_array['group'] = $rowVrednost['variable'];

            // Vrnemo tudi url do ankete za ustvarjeno skupino
            $nice_url = SurveyInfo::getSurveyLink();
            $json_array['url'] = $nice_url . '?skupina=' . $rowVrednost['variable'];

            $json_array['note'] = 'Group succesfully added.';
        } else {
            $json_array['error'] = 'Missing parameter (group title is mandatory)!';
        }

        return $json_array;
    }

	
    // Doda novo skupino za modul Evoli - teammeter
    private function addGroupTeamMeter($ank_id, $data) {
        global $lang;
        global $global_user_id;
        global $admin_type;
        global $site_path;

        $json_array = array();

        // Obvezni parametri
        $group_naslov = (isset($data['title'])) ? $data['title'] : '';
        $email = (isset($data['email'])) ? $data['email'] : '';
        $language = (isset($data['language'])) ? $data['language'] : '';
        $kvota_max = (isset($data['quota'])) ? $data['quota'] : '';

        if ($group_naslov != '' && $email != '' && $language != '' && $kvota_max != '') {

            $ss = new SurveySkupine($ank_id);
            $spr_id = $ss->hasSkupine();

            // Na zacetku moramo ustvarit najprej vprasanje
            if ($spr_id == 0) {

                $sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$ank_id' AND vrstni_red='1'");
                $rowG = mysqli_fetch_array($sqlG);
                $gru_id = $rowG['id'];

                $b = new Branching($ank_id);
                $spr_id = $b->nova_spremenljivka($grupa = $gru_id, $grupa_vrstni_red = 1, $vrstni_red = 0);

                $sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='" . $lang['srv_skupina'] . "', variable='skupina', variable_custom='1', skupine='1', sistem='1', visible='0', size='0' WHERE id='$spr_id'");

                Vprasanje::change_tip($spr_id, 1);
            }

            // Ustvarimo vrednost v vprasanju
            $v = new Vprasanje($ank_id);
            $v->spremenljivka = $spr_id;
            $vre_id = $v->vrednost_new($group_naslov);

            // Preverimo ce imamo nice URL -> dodamo dodatnega za skupine
            $sql = sisplet_query("SELECT * FROM srv_nice_links WHERE ank_id='$ank_id'");
            if (mysqli_num_rows($sql) > 0) {

                Common::updateEditStamp();

                $row = mysqli_fetch_array($sql);

                $add = false;

                $anketa = $ank_id;
                $nice_url = $row['link'];

                $sql2 = sisplet_query("SELECT variable, vrstni_red FROM srv_vrednost WHERE id='$vre_id'");
                $row2 = mysqli_fetch_array($sql2);
                $nice_url .= '_' . $row2['vrstni_red'];

                $f = @fopen($site_path . '.htaccess', 'rb');
                if ($f !== false) {
                    $add = true;
                    while (!feof($f)) {
                        $r = fgets($f);
                        if (strpos($r, "^" . $nice_url . '\b') !== false) {  // preverimo, da ni tak redirect ze dodan
                            $add = false;
                        }
                    }
                    fclose($f);
                }

                if (strlen($nice_url) < 3)
                    $add = false;

                if (SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
                    $link = 'main/survey/uporabnost.php?anketa=' . SurveyInfo::getInstance()->getSurveyHash() . '&skupina=' . $vre_id;
                else
                    $link = 'main/survey/index.php?anketa=' . SurveyInfo::getInstance()->getSurveyHash() . '&skupina=' . $vre_id;

                if ($add) {
                    $f = @fopen($site_path . '.htaccess', 'a');
                    if ($f !== false) {
                        fwrite($f, "\nRewriteRule ^" . $nice_url . '\b(.*)			' . $link . "&foo=\$1&%{QUERY_STRING}");
                        fclose($f);

                        $sqlI = sisplet_query("INSERT INTO srv_nice_links_skupine (id,ank_id,nice_link_id,vre_id,link) VALUES ('','$ank_id','$row[id]','$vre_id','$nice_url')");
                    }
                }
            }

            if ($language == 'eng')
                $lang_id = 2;
            elseif ($language == 'dan')
                $lang_id = 29;
            else
                $lang_id = 1;

            $nice_url = SurveyInfo::getSurveyLink();
            $group_url = $nice_url . '?skupina=' . $vre_id . '&language=' . $lang_id;

            // Dodamo se vrednosti v posebno tabelo za evoli team meter
            $sqlI = sisplet_query("INSERT INTO srv_evoli_teammeter 
									(ank_id, skupina_id, email, lang_id, url, kvota_max) 
									VALUES ('" . $ank_id . "', '" . $vre_id . "', '" . $email . "', '" . $lang_id . "', '" . $group_url . "', '" . $kvota_max . "')");


            // Vrnemo grupo
            $json_array['group'] = $vre_id;

            // Vrnemo tudi url do ankete za ustvarjeno skupino
            $json_array['url'] = $group_url;

            $json_array['note'] = 'Group succesfully added.';
        }
        else {
            $json_array['error'] = 'Missing parameters (group title, email, language id and quota are mandatory)!';
        }

        return $json_array;
    }

    // Poslje email vabilo novemu respondentu za modul Evoli - url se uporabi za specificno grupo
    private function sendEmailInvitationTeamMeter($ank_id, $data) {
        global $lang;
        global $global_user_id;
        global $admin_type;

        $json_array = array();

        // Preverimo ce sploh imamo vklopljena vabila
        $isEmail = (int) SurveyInfo::getInstance()->checkSurveyModule('email');
        $d = new Dostop();
        if (!((int) $isEmail > 0)) {

            $json_array['error'] = 'Invitations are not enabled for this survey!';
            return $json_array;

            exit();
        }

        $email = (isset($data['email'])) ? $data['email'] : '';
        $firstname = (isset($data['firstname'])) ? $data['firstname'] : '';
        $lastname = (isset($data['lastname'])) ? $data['lastname'] : '';
        $group = (isset($data['group'])) ? $data['group'] : '';

        // Zaenkrat so vsi 4 parametri obvezni
        if ($email != '' && $firstname != '' && $lastname != '' && $group != '') {

            // Preverimo ce obstajajo vse 3 sistemske spremenljivke
            $sqlVariable = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE (s.variable='email' OR s.variable='ime' OR s.variable='priimek') AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "'");
            if (mysqli_num_rows($sqlVariable) != 3) {

                $json_array['error'] = 'Missing system variables (variables email, ime and priimek must exist in survey)!';
            } else {

                $skupina_id = 0;
                $lang_id = 0;

                // Preverimo ce obstaja skupina
                $sqlTM = sisplet_query("SELECT tm.*, v.naslov 
											FROM srv_evoli_teammeter tm, srv_vrednost v, srv_spremenljivka s, srv_grupa g 
											WHERE s.gru_id=g.id AND v.spr_id=s.id AND v.id=tm.skupina_id
												AND g.ank_id='" . $ank_id . "' AND s.skupine='1' AND v.naslov='" . $group . "'");
                if (mysqli_num_rows($sqlTM) == 1) {
                    $rowTM = mysqli_fetch_array($sqlTM);

                    $skupina_id = $rowTM['skupina_id'];
                    $lang_id = $rowTM['lang_id'];
                }

                if ($skupina_id == 0 || $lang_id == 0) {

                    $json_array['error'] = 'Group "' . $group . '" does not exist!';
                    return $json_array;

                    exit();
                } else {
                    $SI = new SurveyInvitationsNew($ank_id);

                    // polovimo sistemske spremenljivke z vrednostmi
                    $qrySistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='" . $ank_id . "' AND variable IN('email', 'ime', 'priimek') ORDER BY g.vrstni_red, s.vrstni_red");
                    $sys_vars = array();
                    $sys_vars_ids = array();
                    while ($row = mysqli_fetch_assoc($qrySistemske)) {
                        $sys_vars[$row['id']] = array('id' => $row['id'], 'variable' => $row['variable'], 'naslov' => $row['naslov']);
                        $sys_vars_ids[] = $row['id'];
                    }
                    $sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id, vrstni_red, variable FROM srv_vrednost WHERE spr_id IN(" . implode(',', $sys_vars_ids) . ") ORDER BY vrstni_red ASC ");
                    while ($row = mysqli_fetch_assoc($sqlVrednost)) {
                        $sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
                    }

                    $list_id = '';

                    // Generiramo kodo za respondenta
                    // katera gesla (code) že imamo v bazi za to anketo
                    $password_in_db = array();
                    $sql_query = sisplet_query("SELECT password FROM srv_invitations_recipients WHERE ank_id='" . $ank_id . "' AND deleted = '0'");
                    while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                        $password_in_db[$sql_row['password']] = $sql_row['password'];
                    }
                    // Izberemo random hash, ki se ni v bazi
                    do {
                        list($code, $cookie) = $SI->generateCode();
                    } while (in_array($code, $password_in_db));


                    // VSTAVIMO RESPONDENTA V SEZNAM
                    $sql_insert_start = sisplet_query("INSERT INTO srv_invitations_recipients 
											(ank_id, email, firstname, lastname, password, cookie, sent, responded, unsubscribed, deleted, date_inserted, inserted_uid, list_id) 
											VALUES 
											('" . $ank_id . "', '" . $email . "', '" . $firstname . "', '" . $lastname . "', '" . $code . "', '" . $cookie . "', '0', '0', '0', '0', NOW(), '" . $global_user_id . "', '" . $list_id . "')");
                    $rec_id = mysqli_insert_id($GLOBALS['connect_db']);


                    // polovimo sporočilo in prejemnike
                    $sql_query_m = sisplet_query("SELECT id, subject_text, body_text, reply_to, isdefault, comment, naslov, url FROM srv_invitations_messages WHERE ank_id = '" . $ank_id . "' AND isdefault='1'");
                    if (mysqli_num_rows($sql_query_m) > 0) {
                        $sql_row_m = mysqli_fetch_assoc($sql_query_m);
                    } else {
                        // Nimamo še vsebine sporočila
                        $json_array['error'] = 'Email server settings and message not set!';
                        return $json_array;

                        exit();
                    }

                    // Kreiramo mail
                    $subject_text = $sql_row_m['subject_text'];
                    $body_text = $sql_row_m['body_text'];

                    // Naslov za odgovor je avtor ankete
                    if ($SI->validEmail($sql_row_m['reply_to'])) {
                        $reply_to = $sql_row_m['reply_to'];
                    } else {
                        $reply_to = Common::getInstance()->getReplyToEmail();
                    }

                    # če mamo SEO
                    $nice_url = SurveyInfo::getSurveyLink();

                    $date_sent = date("Y-m-d H:i:s");
                    $msg_url = $sql_row_m['url'];

                    # odvisno ali imamo url za jezik.
                    if ($msg_url != null && trim($msg_url) != '') {
                        $url = $msg_url . '?code=' . $code;
                    } else {
                        $url = $nice_url . '&code=' . $code;
                    }

                    $url .= '&ai=' . (int) $arch_id;

                    // Url-ju dodamo se grupo in jezik
                    $url .= '&skupina=' . $skupina_id . '&language=' . $lang_id;

                    #odjava
                    $unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $ank_id . '&code=' . $code;


                    // VSTAVIMO POSILJANJE V ARHIV
                    $arvhive_naslov = 'mailing_' . date("d.m.Y") . ', ' . date("H:i:s");
                    $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive 
												(id, ank_id, date_send, subject_text, body_text, uid, comment, naslov, rec_in_db)
												VALUES 
												(NULL, '$ank_id', '$date_sent', '$subject_text', '$body_text', '$global_user_id', '', '$arvhive_naslov', '1')");
                    $arch_id = mysqli_insert_id($GLOBALS['connect_db']);


                    $user_body_text = str_replace(
                            array(
                        '#URL#',
                        '#URLLINK#',
                        '#UNSUBSCRIBE#',
                        '#FIRSTNAME#',
                        '#LASTNAME#',
                        '#EMAIL#',
                        '#CODE#',
                        '#PASSWORD#'
                            ), array(
                        '<a href="' . $url . '">' . $url . '</a>',
                        $url,
                        '<a href="' . $unsubscribe . '">' . $lang['user_bye_hl'] . '</a>',
                        $firstname,
                        $lastname,
                        $email,
                        $code,
                        $code
                            ), $body_text
                    );


                    // POSLJEMO MAIL
                    $resultX = null;
                    try {
                        $MA = new MailAdapter($ank_id, $type='invitation');
                        $MA->addRecipients($email);
                        $resultX = $MA->sendMail($user_body_text, $subject_text);
                    } catch (Exception $e) {
                        // todo fajn bi bilo zalogirat kaj se dogaja
                        $__error = $e->getMessage();
                        $__errStack = $e->getTraceAsString();
                    }


                    // Vabilo OK poslano
                    if ($resultX) {
                        // Updatamo prejemnika - status in sent
                        $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET sent='1', date_sent='" . $date_sent . "', last_status='1' WHERE id='" . $rec_id . "'");

                        // Updatamo se arhiv
                        $sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='1', cnt_error='0' WHERE id='" . $arch_id . "'");

                        // Updatamo arhiv prejemnikov
                        $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ('" . $arch_id . "','" . $rec_id . "','1')");

                        // Updatamo tracking
                        $sqlQueryTracking = sisplet_query("INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ('" . $arch_id . "',NOW(),'" . $rec_id . "','1')");


                        // Dodamo userje v bazo
                        $sqlUserInsert = sisplet_query("INSERT INTO srv_user 
										(ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
										VALUES 
										('" . $ank_id . "', '" . $email . "', '" . $cookie . "', '" . $code . "', '1', NOW(), '" . $rec_id . "') ON DUPLICATE KEY UPDATE cookie = '" . $cookie . "', pass='" . $code . "'");
                        $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                        if ($usr_id) {
                            // vstavimo v srv_userbase
                            sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('" . $usr_id . "','0',NOW(),'" . $global_user_id . "')");

                            // vstavimo v srv_userstatus
                            sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('" . $usr_id . "', '0', '0', NOW())");

                            // vstavimo v srv_data_text (email, ime, priimek)
                            SurveyInfo::getInstance()->SurveyInit($ank_id);
                            $db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
                            foreach ($sys_vars AS $sid => $spremenljivka) {
                                if ($spremenljivka['variable'] == 'email')
                                    sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $email . "', '" . $usr_id . "')");
                                elseif ($spremenljivka['variable'] == 'ime')
                                    sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $firstname . "', '" . $usr_id . "')");
                                elseif ($spremenljivka['variable'] == 'priimek')
                                    sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $lastname . "', '" . $usr_id . "')");
                            }
                        }
                        else {
                            // lahko da user že obstaja in je šlo za duplicated keys
                        }

                        $json_array['note'] = 'Email succesfully sent.';
                    }
                    // Vabilo ni bilo poslano
                    else {
                        // Updatamo prejemnika - status in sent
                        $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status='2' WHERE id='" . $rec_id . "'");

                        // Updatamo se arhiv
                        $sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='0', cnt_error='1' WHERE id='" . $arch_id . "'");

                        // Updatamo arhiv prejemnikov
                        $sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ('" . $arch_id . "','" . $rec_id . "','0')");

                        // Updatamo tracking
                        $sqlQueryTracking = sisplet_query("INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ('" . $arch_id . "',NOW(),'" . $rec_id . "','2')");


                        // Dodamo userje v bazo
                        $sqlUserInsert = sisplet_query("INSERT INTO srv_user 
										(ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
										VALUES 
										('" . $ank_id . "', '" . $email . "', '" . $cookie . "', '" . $code . "', '2', NOW(), '" . $rec_id . "') ON DUPLICATE KEY UPDATE cookie = '" . $cookie . "', pass='" . $code . "'");
                        $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                        if ($usr_id) {
                            // vstavimo v srv_userbase
                            sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('" . $usr_id . "','0',NOW(),'" . $global_user_id . "')");

                            // vstavimo v srv_userstatus
                            sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('" . $usr_id . "', '0', '0', NOW())");

                            // vstavimo v srv_data_text (email, ime, priimek)
                            SurveyInfo::getInstance()->SurveyInit($ank_id);
                            $db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
                            foreach ($sys_vars AS $sid => $spremenljivka) {
                                if ($spremenljivka['variable'] == 'email') {
                                    $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $email . "', '" . $usr_id . "')");
                                } elseif ($spremenljivka['variable'] == 'ime') {
                                    $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $firstname . "', '" . $usr_id . "')");
                                } elseif ($spremenljivka['variable'] == 'priimek') {
                                    $data_insert = sisplet_query("INSERT INTO srv_data_text" . $db_table . " (spr_id, vre_id, text, usr_id) VALUES ('" . $sid . "', '" . $spremenljivka['vre_id'] . "', '" . $lastname . "', '" . $usr_id . "')");
                                }
                            }
                        } else {
                            // lahko da user že obstaja in je šlo za duplicated keys
                        }

                        $json_array['error'] = 'Email sending error!';
                    }
                }
            }
        }
        // Manjkajo parametri (email, firstname, lastname)
        else {
            $json_array['error'] = 'Missing parameters (email, firstname and lastname are mandatory)!';
        }

        return $json_array;
    }

    // Doda kupcu iz trgovine moznost dostopa do evoli landing paga (vrne token za dostop s katerim lahko enkrat izpolni formo in poslje vabila)
    private function createEvoliPass($ank_id, $email) {
        global $lang;
        global $global_user_id;
        global $admin_type;

        $json_array = array();

        // Oba parametra sta obvezna
        if ($ank_id != '' && $email != '') {

            // Zgeneriramo nakljucno geslo, ki se ne obstaja v bazi
            $pass = substr(md5(microtime()), rand(0, 26), 5);
            $sql = sisplet_query("SELECT * FROM srv_evoli_landingPage_access WHERE pass='" . $pass . "'");
            while (mysqli_num_rows($sql) > 0) {
                $pass = substr(md5(microtime()), rand(0, 26), 5);
                $sql = sisplet_query("SELECT * FROM srv_evoli_landingPage_access WHERE pass='" . $pass . "'");
            }

            // Vstavimo kupca v tabelo za dostop
            $sqlI = sisplet_query("INSERT INTO srv_evoli_landingPage_access (ank_id, email, pass, time_created) VALUES ('" . $ank_id . "', '" . $email . "', '" . $pass . "', NOW())");

            $json_array['pass'] = $pass;
        }
        // Manjkajo parametri (email, firstname, lastname)
        else {
            $json_array['error'] = 'Missing parameters (survey id and email are mandatory)!';
        }

        return $json_array;
    }

    // Vrne pass za kupca iz trgovine za moznost dostopa do evoli landing paga (vrne token za dostop s katerim lahko enkrat izpolni formo in poslje vabila)
    private function getEvoliPass($ank_id, $email) {
        global $lang;
        global $global_user_id;
        global $admin_type;

        $json_array = array();

        // Oba parametra sta obvezna
        if ($ank_id != '' && $email != '') {

            $sql = sisplet_query("SELECT pass FROM srv_evoli_landingPage_access WHERE ank_id='" . $ank_id . "' AND email='" . $email . "' AND used='0'");
            if (mysqli_num_rows($sql) > 0) {
                $row = mysqli_fetch_array($sql);
                $json_array['pass'] = $row['pass'];
            } else {
                $json_array['pass'] = '-1';
            }
        }
        // Manjkajo parametri (email, firstname, lastname)
        else {
            $json_array['error'] = 'Missing parameters (survey id and email are mandatory)!';
        }

        return $json_array;
    }

	
    // Vrne verzijo mobilne aplikacije
    // TRENUTNO SE NE RABI VEC
    private function getMobileAppVersion() {
        global $lang;
        global $global_user_id;

        $sm = new SurveyMobile();
        $mobile_versions = $sm->getMobileVersion();

        $obj['note'] = "login OK";
        $obj['version'] = $mobile_versions;

        return $obj;
    }

    private function getLang($anketa) {
        $lang_admin = 0;
        if ($anketa > 0) {
            $sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
            $row = @mysqli_fetch_array($sql);
            $lang_admin = $row['lang_admin'];
        }
        if ($lang_admin == 0) {
            //$sql = sisplet_query("SELECT * FROM misc WHERE what = 'SurveyLang_admin'");
            $sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
            $row = @mysqli_fetch_array($sql);
            $lang_admin = $row['lang'];
        }
        if ($lang_admin == 0) {
            $sql = sisplet_query("SELECT * FROM misc WHERE what = 'SurveyLang_admin'");
            $row = @mysqli_fetch_array($sql);
            $lang_admin = $row['value'];
        }
        if ($lang_admin == 0)
            $lang_admin = 2; // za vsak slucaj, ce ni v bazi

        return $lang_admin;
    }

	
	// Ustvari novega uporabnika - zaenkrat dovoljeno samo za Gorenje instalacijo
	private function createUser($data){
		global $pass_salt;
		global $lang;
		
		$json_array = array();
		
		$email = (isset($data['email'])) ? $data['email'] : '';
		$name = (isset($data['name'])) ? $data['name'] : '';
		$pass = (isset($data['pass'])) ? $data['pass'] : '';
		
				
		// Email in name sta obvezna, psss je lahko tudi prazen
		if($email != '' && $name != ''){
								
			// Preverimo ce ze obstaja email
			$sql = sisplet_query ("SELECT * FROM users WHERE email='".$email."'");
			if(mysqli_num_rows($sql) > 0){
				
				$json_array['error'] = 'Uporabnik z izbanim emailom že obstaja!';
				return $json_array;	

				exit();			
			}

			$kdaj = date('Y-m-d');
			$priimek = '';
			
			// Status ima vedno active?
			//if ($banan == 1)
			//	$status = 0;
			//elseif ($active == 1)
				$status = 1;
			//else
			//	$status = 2;
		
			// Zakodiramo geslo ki ga insertamo
			if($pass == '')
				$g = '';
			else
				$g = base64_encode((hash(SHA256, $pass . $pass_salt)));
			
			// Vstavimo userja v bazo
			sisplet_query ("INSERT INTO users 
								(type, email, name, surname, pass, status, when_reg, came_from, lang) 
							VALUES 
								('3', '".$email."', '".$name."', '".$priimek."', '".$g."', '".$status."', '".$kdaj."', '0', '".$lang['id']."')");
								
			$json_array['note'] = 'Uporabnik '.$name.' ('.$email.') uspešno dodan in aktiviran.';
		}
		// Manjkajo parametri (email, name)
		else{
			$json_array['error'] = 'Missing parameters (email and name are mandatory)!';			
		}
		
		return $json_array;
	}
}
