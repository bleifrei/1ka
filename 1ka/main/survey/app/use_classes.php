<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\AjaxController as Ajax;
use App\Controllers\BodyController as Body;
use App\Controllers\CheckController as Check;
use App\Controllers\DisplayController as Display;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\InitClass as Init;
use App\Controllers\JsController as Js;
use App\Controllers\LanguageController as Language;
use App\Controllers\StatisticController as Statistic;
use App\Controllers\SurveyController as Survey;
use App\Controllers\VariableClass as Variable;
use App\Models\Model;
use App\Models\SaveSurvey;
use App\Models\User;

// Iz admin/survey
use Common;
use Cache;
use SurveyInfo;
use SurveySetting;
use SurveySlideshow;
use Mobile_Detect;
use SurveyMissingValues;
use AdvancedParadata;
use Branching;
use enkaParameters;



// Vprašanja
use App\Controllers\Vprasanja\ComputeController as Compute;
use App\Controllers\Vprasanja\DatumController as Datum;
use App\Controllers\Vprasanja\DoubleController as Double;
use App\Controllers\Vprasanja\DragDropController as DragDrop;
use App\Controllers\Vprasanja\DynamicController as Dynamic;
use App\Controllers\Vprasanja\ImenaController as Imena;
use App\Controllers\Vprasanja\MaxDiffController as MaxDiff;
use App\Controllers\Vprasanja\MultigridController as Multigrid;
use App\Controllers\Vprasanja\NumberController as Number;
use App\Controllers\Vprasanja\OneAgainstAnotherController as OneAgainstAnother;
use App\Controllers\Vprasanja\QuotaController as Quota;
use App\Controllers\Vprasanja\RadioCheckboxSelectController as RadioCheckboxSelect;
use App\Controllers\Vprasanja\RankingController as Ranking;
use App\Controllers\Vprasanja\SystemVariableController as SystemVariable;
use App\Controllers\Vprasanja\TextController as Text;
use App\Controllers\Vprasanja\VprasanjaController as Vprasanja;
use App\Controllers\Vprasanja\VsotaController as Vsota;
use App\Controllers\Vprasanja\MapsController as Maps;
