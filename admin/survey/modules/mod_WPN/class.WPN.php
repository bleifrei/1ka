<?php
/**
 * 
 * MAZA - mobilna aplikacija za anketirance
 * Class za posiljanje sporocil uporabnikom (v mojih anketah)
 * 
 * Uroš Podkrižnik 16.10.2017
 */

require __DIR__ . '/../../../../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;

class WPN {
    
    var $_ank_id;
    var $_ank_link;
    var $_ank_title;

    function __construct($ank_id = 0) {
        $this->_ank_id = $ank_id;
        //get survey title and link
        if($ank_id > 0){
            SurveyInfo::getInstance()->SurveyInit($ank_id);
            $this->_ank_title = SurveyInfo::getSurveyColumn('naslov');
            $this->_ank_link = SurveyInfo::getSurveyLink();
        }
    }   
    
        public function display() {
        global $admin_type, $global_user_id, $lang;

        // Izpis vseh poslanih sporocil
        if ($admin_type == 0) {           
            //is survey activated
            $act = sisplet_query("SELECT active FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');
            if($act->active != 1)
                echo '<p class="red">'.$lang['srv_anketa_noactive2'].'<p>';
    
            $this->sendMessageForm();
                
            if(isset($_GET['FCM_response']))
                echo '<br><br>'.$_GET['FCM_response'];
        }
    }
    
        // Obrazec za posiljanje notificationa
    private function sendMessageForm() {
        global $admin_type, $global_user_id, $lang;
        
        //FORM FOR WEB PUSH NOTIFICATIONs
        echo '<fieldset>';
        echo '<legend>'.$lang["srv_wpn"].'</legend>';
        echo '<form name="wpn_send_notification" id="wpn_send_notification" method="post" action="ajax.php?t=WPN&a=wpn_send_notification">';
        /* echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';

          // Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';
         */

        echo '<input type="hidden" name="anketa" value="' . $this->_ank_id . '">';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="wpn_title" id="wpn_title" size="35" value="' . $lang['srv_wpn_notification_title_default'] . '" maxlength="35"><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="wpn_message" id="wpn_message" size="45" value="'.$this->_ank_title.'" maxlength="45"></textarea><br><br>';

        //echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" value="1" />';
        //echo $lang['srv_maza_notification_priority'] . '</label><br><br>';

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" value="1" />';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#wpn_send_notification\').submit();">';
        echo $lang['srv_notifications_send'];
        echo '</a></div></span>';

        echo '<br><br><p id="maza_result">' . $_POST['maza_result'] . '</p>';

        echo '</form>';
        echo '</fieldset>';
    }
    
    //PWA
    public function ajax_wpn_save_subscription(){        
        //whole link for endpoint - browser request link and endpoint key of user
        $endpoint = $_POST['endpoint'];
        //last slash index
        $lsi = strrpos($endpoint, '/');
        //endpoint key
        $endpoint_key = substr($endpoint, $lsi + 1);
        //endpoint link
        $endpoint_link = substr($endpoint, 0, $lsi + 1);
        
        sisplet_query("INSERT INTO browser_notifications_respondents (timestamp_joined, endpoint_link, endpoint_key, public_key, auth) "
                    . "VALUES (NOW(), '".$endpoint_link."', '".$endpoint_key."', '".$_POST['keys']['p256dh']."', '".$_POST['keys']['auth']."')");
        
        echo 'Subscription added';
    }
    
    public function sendWebPushNotificationsToAll(){
        
        $title = $_POST['wpn_title'];
        $message = $_POST['wpn_message'];
        
        $payload = array('title'=>$title, 'message'=>$message, 'link'=>$this->_ank_link);
        
        /*$notifications = [ // this is the structure for the working draft from october 2018 (https://www.w3.org/TR/2018/WD-push-api-20181026/) 
            [
                'subscription' => Subscription::create([
                    "endpoint" => "https://updates.push.services.mozilla.com/wpush/v1/gAAAAABdoHSjAgtlSI2QNu_D6p3WDWITErDHYLWfbf37pgJd1HYnIukVaHfAxAOi4gxzPq1c8baWSMk9O6YkSOrbn7brlWaLpcNnKba1xgia13ESTwKNaevwY9_ciy3ojB4IXprryHTb",
                    "keys" => [
                        'p256dh' => "BIvluRM0T5ezCbH_IwEhsDr8D-kXq4sRfgmaG4OKOGbqrE6CWwcrvY5S7YpqfMgn_ZqOHlLaZX11skBWar3Xj3w",
                        'auth' => "Tqco8e4PIAZL9zogxks3qQ"
                    ]
                ])
            ],
            [
                'subscription' => Subscription::create([
                    "endpoint" => "https://fcm.googleapis.com/fcm/send/cDZBBiO8nwA:APA91bG6pQJNapbmiT0zMED_HEiQNi2OxgmAJbjqWnny1H78FXFRzJXtcBi62xwdZHOr9GWrBsbE5ePPK58m9H9ZKhy0Q8TFKCu-Os-ykAN2IJL4lPVcUyslBMt8sCABlomHUHl8AQLR",
                    "keys" => [
                        'p256dh' => "BPyTEM7mLZAFQm-8bSsVmAJRDMeCTjwSmOCJXieK-xtwcRsKE9zLHZRpfp52ChQzrDZLi_n0RdBiX5yydC7DL90",
                        'auth' => "iSKAhIqNvjlaOhXzk4ulJw"
                    ]
                ])
            ],
            [
                'subscription' => Subscription::create([
                    "endpoint" => "https://updates.push.services.mozilla.com/wpush/v2/gAAAAABdpZDL1nFRXedBJYYfTwdcqfrf2khsEXllMLEWPBIolO1t1wkptE7HzkypkPDEVwYq0ju1kNblwJHxA9v0k05oVNCuxBi0l0dDqsrZZ_TRao_hDprjzoSuuHE5z4zrTzbTwwxKqmxDYvF_1Ty28qaUaaLqFJGgOTgSjN9W3bkifRqDZnQ",
                    "keys" => [
                        'p256dh' => "BESWycM2xqcaFcvG1kYWGpnamq6IZd8mhGtSQpUsROBn0ejJmwI_vptgpW4jBwbQrcb-T8sXvmRRZ4HY9VLWJes",
                        'auth' => "WnRV6LQEYcvwb21DnpXzkA"
                    ]
                ])
            ],
            [
                'subscription' => Subscription::create([
                    "endpoint" => "https://fcm.googleapis.com/fcm/send/cyGorE1fYnM:APA91bHq1if5UrRr1uI8HLD92M2OCekH1kH6q7HmrINkMFHmLq_RtuytWb9DKo2446WgvDRbzUjQXwQ88_b70NzOUBBFWhxaslJuQFyJZPHIkxRf--MIHTY-KSQ1JcPQYcED1QreaEZf",
                    "keys" => [
                        'p256dh' => "BPTZTRDEztn_YpAvdv4wjtCyNqo0RNFfWbjm9r7bgyZh2RBZgDvitaW_68hNC6cYGzKGJM9aMpGi59-_H8HYSn8",
                        'auth' => "M79ZKrfJwEzPfHIpkN-tIw"
                    ]
                ])
            ]
        ];*/
        
        //get all subscriptions and put them in array
        $subscriptions = sisplet_query("SELECT endpoint_link, endpoint_key, public_key, auth FROM browser_notifications_respondents", 'array');
        $notifications = array();
        foreach ($subscriptions as $subsc){
            $subscription = array("endpoint"=>$subsc['endpoint_link'].$subsc['endpoint_key'], "keys"=>array('p256dh' => $subsc['public_key'],'auth' => $subsc['auth']));
            array_push($notifications, array('subscription' => Subscription::create($subscription)));
        }

        $auth = array(
            'VAPID' => array(
                'subject' => 'mailto:enklikanketa@gmail.com',
                'publicKey' => 'BNVIBdCsC6vkmByQJ861pusHN1mV76X3mvAa1u4PxmleTv2m2whcEu9Elhh8Qz3XnqV6k58YCSVqaafl3bhPKLU', 
                'privateKey' => 'c7mxuK7Nexe4NHnCtYE79p0iHzaXZGikWpua7z66dQg',
            ),
        );
        $webPush = new WebPush($auth);
        // send multiple notifications with payload
        foreach ($notifications as $notification) {
            $webPush->sendNotification(
                $notification['subscription'],
                json_encode($payload)
            );
        }
        
        // handle eventual errors here, and remove the subscription from your server if it is expired
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                echo "<br>[v] Message sent successfully for subscription {$endpoint}.";
            } else {
                echo "<br>[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
            }
        }
    }
    
}
?>