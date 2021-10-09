<?
mb_internal_encoding("UTF-8");
	echo "Test šumnikov";
require_once('../survey/class.SurveyInfo.php');
SurveyInfo::getInstance()->SurveyInit($_GET['anketa']);
echo "<hr>";
echo SurveyInfo::getInstance()->getSurveyTitle();
echo "<hr>";

$string = SurveyInfo::getInstance()->getSurveyTitle();
$string = iconv ("UTF-8", "CP1250", $string);
for($i = 0; $i < strlen($string); $i++)
{
	echo $string[$i]." -> ".ord($string[$i])."<br>";
}
?>