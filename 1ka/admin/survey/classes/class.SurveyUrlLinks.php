<?php 

class SurveyUrlLinks 
{

	private $_anketa;
	
	public function __construct($anketa = null)
	{
		try {
			if (!empty($anketa) && (int)$anketa > 0) 
			{
				$this->_anketa = $anketa;
			}
			else 
			{
				throw new Exception('Error: survey ID is missing. (HashUrl)');
			}
		} catch (Exception $e) {
			die( $e->getMessage().' Exiting script!');
		}
		
		return $this;
	}

	public function ajax() 
	{
		$action = $_GET['a'];
		switch ($action) 
		{
			case 'showLinks' :
				self :: showUrlLinks();
			break;
			case 'addLink' :
				self :: addLink();
			break;
			case 'saveComment' :
				self :: saveComment();
                        case 'saveRefresh' :
				self :: saveRefresh();
                        case 'saveAccessPassword' :
				self :: saveAccessPassword();
			break;
			case 'deleteLink' :
				self :: deleteLink();
			break;
			default:
				$this->showUrlLinks();
			break;
		}
		
	}
	
	function showUrlLinks($msg=null){
		global $lang, $site_url;
		
		$podstran = $_REQUEST['podstran'];
		$m = $_REQUEST['m'];
		
		$popUp = new PopUp();
		$popUp->setId('div_survey_links');
		$popUp->setHeaderText($lang['srv_public_link_title'].':');
		
		#vsebino shranimo v buffer
        ob_start();
        
        echo '<div class="popup_close"><a href="#" onClick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\'); return false;">✕</a></div>';

		if ($msg != null){
			echo ($msg);
			echo '<br />';
		}
		$hashUrl = new HashUrl($this->_anketa);
		$links = $hashUrl->getSurveyHashes();
		if (count($links) == 0){
			echo $lang['srv_public_link_noLink'].'<br />';
		}
		echo '<p><a href="#" onclick="addSurveyUrlLink(\''.$podstran.'\',\''.$m.'\');">'.$lang['srv_public_link_addLink'].'</a></p>';
		if (count($links) > 0){
		
			echo '<caption>'.$lang['srv_public_link_existing'].'</caption>';
			echo '<table class="tbl_survey_links">';
			
			echo '<tr>';
			echo '<th>&nbsp;</th>';
			echo '<th></th>';
			echo '<th>'.$lang['srv_inv_archive_comment'].'</th>';
			echo '<th>'.$lang['url'].'</th>';
                        echo '<th>'.$lang['srv_analiza_archive_access_password_label'].'</th>';
                        echo '<th>'.$lang['srv_public_link_refresh'].'</th>';
			echo '<th>'.$lang['srv_public_link_linkTo'].'</th>';
			echo '<th>'.$lang['srv_public_link_creationDate'].'</th>';
			echo '<th>'.$lang['srv_public_link_created'].'</th>';
			echo '</tr>';
			foreach ($links as $key => $link){
				echo '<tr>';
				echo '<td>';
				echo '<a href="#" onclick="deleteSurveyUrlLinks(\''.$this->_anketa.'\',\''.$link['hash'].'\',\''.$podstran.'\',\''.$m.'\');" title="'.$lang['srv_public_link_deleteLink'].'"><span class="sprites dataLinkDelete"></span></a>';
				echo '</td>';
				echo '<td>';
				echo $lang['srv_hash_url_'.$link['properties']['a']];
                echo '</td>';
				
				echo '<td>';
				echo '<span class="editable hash_comment" name="hash_comment" id="hash_comment_'.$this->_anketa.'_'.$link['hash'].'" data-hash="'.$link['hash'].'" data-anketa="'.$this->_anketa.'" contenteditable="true" style="display: block;width:100%" onblur="hash_comment_change(this);">';
				print_r($link['comment']);
				echo '</span></td>';
				
				echo '<td>';
                                //if this link is edited, inform editor of mobile App or API users
				echo '<a href="'.$site_url.'podatki/'.$this->_anketa.'/'.$link['hash'].'/" target="_blank">';
				echo $site_url.'podatki/'.$this->_anketa.'/'.$link['hash'].'/</a>';
				echo '</td>';
                                
                                echo '<td>';
				echo '<span class="editable hash_comment" name="access_password" id="access_password_'.$this->_anketa.'_'.$link['hash'].'" contenteditable="true" data-hash="'.$link['hash'].'" data-anketa="'.$this->_anketa.'" onblur="hash_access_password_change(this);" onpaste="return false;" onkeypress="return (this.innerText.length < 25);" style="min-width: 100px;max-width: 100px;">';
                                print_r($link['access_password']);
				echo '</span></td>';
                                
                                echo '<td style="text-align: center;">';
				echo '<input type="checkbox" name="hash_link_refresh" id="hash_link_refresh_'.$this->_anketa.'_'.$link['hash'].'" data-hash="'.$link['hash'].'" data-anketa="'.$this->_anketa.'" onchange="hash_refresh_change(this);"'. (($link['refresh'] == '1') ? ' checked' : '') .'>';
				echo '</td>';
				
				echo '<td>';
				if ($link['page'] == $hashUrl::PAGE_DATA){
					$page = $lang['srv_public_link_data'];
				}
				if ($link['page'] == $hashUrl::PAGE_ANALYSIS){
					$page = $lang['srv_public_link_analyse'];
				}
				echo $page;
                if ($link['properties']['a'] == A_ANALYSIS && isset($link['properties']['m'])) {
                    if ($link['properties']['m'] == M_ANALYSIS_DESCRIPTOR 
                        || $link['properties']['m'] == M_ANALYSIS_FREQUENCY
                        || $link['properties']['m'] == M_ANALYSIS_SUMMARY
                        || $link['properties']['m'] == M_ANALYSIS_SUMMARY_NEW
                        || $link['properties']['m'] == M_ANALYSIS_CREPORT
                        || $link['properties']['m'] == M_ANALYSIS_CHARTS) {
                        echo ' (' . $lang['srv_'.$link['properties']['m']].')';   
                    }
                }
				echo '</td>';
				
				echo '<td title="'.$link['add_date'].', '.$link['add_time'].'">';
				echo $link['add_date'];
				
				echo '</td>';
				
				echo '<td>';
				echo $link['email'];
				echo '</td>';
				
				echo '</tr>';
			}
			
			echo '</table>';
		}
		
		#dodamo vsebino
		$content = ob_get_clean();
		$popUp->setContent($content);
		
		# dodamo gumb Preklici
		$buttonClose = new PopUpCancelButton();
        $buttonClose->setCaption($lang['srv_zapri'])->setTitle($lang['srv_zapri']);
        $buttonClose->setFloat('right');
		$popUp->addButton($buttonClose);

		echo $popUp;
		
	}
	
	function addLink(){
		global $lang;
		global $global_user_id;
		
		#zaenkrat samo za podatke in par analiz
		$podstran = (isset($_REQUEST['podstran']) && ($_REQUEST['podstran'] == A_COLLECT_DATA || $_REQUEST['podstran'] == A_ANALYSIS )) 
			? $_REQUEST['podstran']
			: 'data';
		$m = (isset($_REQUEST['m']))
			? $_REQUEST['m']
			: '';

		# polovimo trenutno nastavljene profile
		SurveyUserSetting::getInstance()->Init($this->_anketa, $global_user_id);
		
        $this->addLinkAPI($global_user_id, $podstran, $m);
		
		$this->showUrlLinks($lang['srv_public_link_linkAdded']);
	}
        
    //da se lahko kreira linke tudi prek API
    function addLinkAPI ($user_id, $podstran, $m){
        SurveyStatusProfiles :: Init($this->_anketa, $user_id);
		$_PROFILE_ID_STATUS = SurveyStatusProfiles :: getCurentProfileId();
		
		SurveyVariablesProfiles::Init($this->_anketa);
		$_PROFILE_ID_VARIABLE = SurveyVariablesProfiles::getCurentProfileId();
		
		SurveyConditionProfiles::Init($this->_anketa, $user_id);
		$_PROFILE_ID_CONDITION = SurveyConditionProfiles::getCurentProfileId();
		                
		$hashUrl = new HashUrl($this->_anketa);
		$newHash = $hashUrl->getNewHash();
		$hashUrl->setPage($podstran);

		$properties = array(
			'anketa'=>$this->_anketa,
			'a'=>$podstran,
			'm'=>$m,
			'profile_id_status'=>$_PROFILE_ID_STATUS,
			'profile_id_variable'=>$_PROFILE_ID_VARIABLE,
			'profile_id_condition'=>$_PROFILE_ID_CONDITION
		);
		
		// Ce gre za porocilo po meri dodamo se id porocila in id avtorja		
		if($m == M_ANALYSIS_CREPORT){
			$creportProfile = SurveyUserSetting :: getInstance()->getSettings('default_creport_profile');
			$creportProfile = isset($creportProfile) && $creportProfile != '' ? $creportProfile : 0;	
			$creportAuthor = SurveyUserSetting :: getInstance()->getSettings('default_creport_author');
			$creportAuthor = isset($creportAuthor) && $creportAuthor != '' ? $creportAuthor : $user_id;	

			$properties['creportProfile'] = $creportProfile;
			$properties['creportAuthor'] = $creportAuthor;
		}
		
		$hashUrl->saveProperty($newHash, $properties);
    }
	
	function saveComment() {
		
		$hashUrl = new HashUrl($this->_anketa);
		$hash = $_REQUEST['hash'];
		$comment = $_REQUEST['comment'];
		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($comment, -4) == '<br>') {
			$comment = substr($comment, 0, -4);
		}
		
		$hashUrl->updateComment($hash,$comment);
	}
        
        function saveRefresh() {
		
		$hashUrl = new HashUrl($this->_anketa);
		$hash = $_REQUEST['hash'];
		$refresh = $_REQUEST['refresh'];
		
		$hashUrl->updateRefresh($hash,$refresh);
	}
        
        function saveAccessPassword() {
		
		$hashUrl = new HashUrl($this->_anketa);
		$hash = $_REQUEST['hash'];
		$pass = $_REQUEST['access_password'];

		// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
		if (substr($comment, -4) == '<br>') {
			$comment = substr($comment, 0, -4);
		}
		
		$hashUrl->updateAccessPassword($hash,$pass);
	}
	
	function deleteLink() {
		global $lang;
		
		$hashUrl = new HashUrl($this->_anketa);
		$hash = $_REQUEST['hash'];
		
		$hashUrl->deleteLink($hash);
		
		$this->showUrlLinks($lang['srv_public_link_linkDeleted']);
	}
	
}
