<?php 

class HashUrl 
{

	private $_anketa;
	private $_hash_length = 8;
	private $_hash_page = 'data';
	
	const PAGE_DATA = 'data';
	const PAGE_ANALYSIS = 'analysis';
	
	public function __construct($anketa = null)
	{
		global $lang;
		try {
			if (!empty($anketa) && (int)$anketa > 0) 
			{
				$this->_anketa = $anketa;
			}
			else 
			{
				throw new Exception($lang['srv_urlLinks_exception_sid']);
			}
		} catch (Exception $e) {
			die( $e->getMessage().' Exiting script!');
		}
		
		return $this;
	}

	public function hashExists($_hash)
	{

		if (!empty($_hash))
		{
			$str = "SELECT hash FROM srv_hash_url WHERE anketa='$this->_anketa' AND hash='$_hash'";
			$qry = sisplet_query($str);
			return mysqli_num_rows($qry);
		}
		return false;
	}
	
	public function getProperties($_hash)
	{

		if (!empty($_hash))
		{
			$str = "SELECT properties FROM srv_hash_url WHERE anketa='$this->_anketa' AND hash='$_hash'";
			
			$qry = sisplet_query($str);
			list($properties) = mysqli_fetch_row($qry);
			$_properties = unserialize($properties);
			if (is_array($_properties))
			{
				return $_properties;
			}
		}
		
		return array();
	}

	public function saveProperty($hash, $properties = array())
	{
		global $global_user_id;
		if (!empty($hash))
		{
			$_properties = serialize($properties);
			$str = "SELECT h.hash, h.properties, h.comment, h.page, h.add_date, u.email FROM srv_hash_url as h LEFT JOIN users AS u ON h.add_uid = u.id WHERE anketa='$this->_anketa'";
			
			$str = "INSERT INTO srv_hash_url (hash,anketa,properties,page,add_date,add_uid) VALUES"
				  ." ('{$hash}','{$this->_anketa}','{$_properties}','{$this->_hash_page}', NOW(), {$global_user_id})"
				  ." ON DUPLICATE KEY UPDATE properties = '".$_properties."'";
			$updated = sisplet_query($str);
			sisplet_query('COMMIT');
		}
		else
		{
			die($lang['srv_urlLinks_error_save']);
		}
		return $this;
	}
	
	public function getNewHash()
	{
		$hashs_in_db = array();
		$str = "SELECT hash FROM srv_hash_url WHERE anketa='$this->_anketa'";
		$qry = sisplet_query($str);
		while (list($hash_in_db) = mysqli_fetch_row($qry))
		{
			$hashs_in_db[] = $hash_in_db;
		}
		
		do 
		{
			$newHash = $this->generateHash();
		}
		while (in_array($newHash,$hashs_in_db));
		if (!empty($newHash) && $newHash != '')
		{
			return $newHash;
		}
		else
		{
			die('Can\'t generate new hash!');
		}
	}

	private function generateHash()
	{
		return substr(strtoupper(hash('md5', uniqid() )),0,$this->_hash_length);
	}
	
	public function getSurveyHashes()
	{
		$result = array();
		$str = "SELECT h.hash, h.properties, h.comment, h.refresh, h.access_password, h.page, DATE_FORMAT(h.add_date,'".STP_CALENDAR_DATE_FORMAT."') as add_date, DATE_FORMAT(h.add_date,'%H:%i') as add_date, u.email FROM srv_hash_url as h LEFT JOIN users AS u ON h.add_uid = u.id WHERE anketa='$this->_anketa' ORDER BY h.add_date DESC";
		$qry = sisplet_query($str);
		while ( list($hash,$properties,$comment,$refresh,$access_password, $page, $add_date, $add_time, $email) = mysqli_fetch_row($qry))
		{
			$result[] = array('hash'=>$hash,'properties'=>unserialize($properties), 'comment'=>$comment, 'refresh'=>$refresh, 'access_password'=>$access_password,
						'page'=>$page, 'add_date'=>$add_date, 'add_time'=>$add_time, 'email'=>$email);
		}
		
		return $result;
		
	}
	
	public function updateComment($hash,$comment)
	{
		$str = "UPDATE srv_hash_url SET comment='$comment' WHERE anketa='$this->_anketa' AND hash='$hash'";
		sisplet_query($str);
	}
        
        public function updateRefresh($hash,$refresh)
	{
		$str = "UPDATE srv_hash_url SET refresh='$refresh' WHERE anketa='$this->_anketa' AND hash='$hash'";
		sisplet_query($str);
	}
        
        public function updateAccessPassword($hash,$pass)
	{
		$str = "UPDATE srv_hash_url SET access_password='$pass' WHERE anketa='$this->_anketa' AND hash='$hash'";
		sisplet_query($str);
	}
	
	public function deleteLink($hash)
	{
		$str = "DELETE FROM srv_hash_url WHERE anketa='$this->_anketa' AND hash='$hash'";
		sisplet_query($str);
	}
	
	public function setPage($string)
	{
		if ($string == HashUrl::PAGE_ANALYSIS)
		{
			$this->_hash_page = HashUrl::PAGE_ANALYSIS; 
		}
		else
		{
			$this->_hash_page = HashUrl::PAGE_DATA;
		}
	}
        
        /**
         * Check if hashlink access password matches
         * @param type $hash - haslink id
         * @param type $pass - access password
         * @return boolean
         */
        public function CheckHashAccessPass($hash, $pass) {
            $sql = sisplet_query("SELECT access_password AS pass FROM srv_hash_url WHERE hash = '$hash'");
            if($sql){
                $row = mysqli_fetch_array($sql);
                if($row['pass'] == $pass)
                    return true;
                else
                    return false;
            }
            return false;   
	}
        
        /**
         * Check if hashlink access password exists
         * @return boolean
         */
        public function IsHashAccessPass($hash) {
            $sql = sisplet_query("SELECT access_password AS pass FROM srv_hash_url WHERE hash = '$hash'");
            if($sql){
                $row = mysqli_fetch_array($sql);
                if($row['pass'] == '' || $row['pass'] == 'NULL')
                    return false;
                else
                    return true;
            }
            return false;   
	}
        
        /**
         * Check if hashlink refresh is on
         * @param type $hash - haslink id
         * @return boolean
         */
        public function IsHashRefresh($hash) {
            $sql = sisplet_query("SELECT refresh FROM srv_hash_url WHERE hash = '$hash'");
            if($sql){
                $row = mysqli_fetch_array($sql);
                if($row['refresh'] == '1')
                    return true;
                else
                    return false;
            }
            return false;   
	}
        
        /**
         * Display from for password to access public link
         * @global type $lang
         * @param type $hash - hash id
         */
        public function HashlinkAccessPasswordForm($hash){
            global $lang, $site_url, $lang_admin; 

            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
            
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
            echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            //echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
            echo '<script type="text/javascript" src="'.$site_url.'admin/survey/script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
            if ($_GET['mode'] != 'old') {
			echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=jsnew"></script>'."\n";
		} else {
			echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=js"></script>'."\n";
		}
            echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=css" media="screen" rel="stylesheet" />';
            echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=cssPrint" media="print" rel="stylesheet" />';
            echo '<style>';
            echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
            echo '</style>';
            echo '<!--[if lt IE 7]>';
            echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
            echo '<![endif]-->';
            echo '<!--[if IE 7]>';
            echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
            echo '<![endif]-->';
            echo '<!--[if IE 8]>';
            echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
            echo '<![endif]-->';
            echo '<style>';
            echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
            echo '</style>';
            echo '</head>'."\n";
            echo '<body id="arch_body" >'."\n";
            echo '<div id="arch_body_div">';
            
            echo '<br><div style="float:left"><fieldset>';
            echo '<legend>' . $lang['srv_analiza_archive_access'] . '</legend>';

            echo '<form name="archive_access_pass_form" id="archive_access_pass_form" method="post" action="'.$site_url.'podatki/'.$this->_anketa.'/'.$hash.'/">';
            //echo '<input type="hidden" name="archive_id" value="' . $aid . '">';

            //user insertet wrong password
            if(isset($_SESSION['hashlink_access'][$hash]) && $_SESSION['hashlink_access'][$hash] == '0')
                echo '<i class="red" id="archive_access_wrong_pass_warning">' . $lang['srv_analiza_archive_access_wrong_pass'] . '</i><br>';

            echo '<br>'.$lang['srv_analiza_archive_access_password_label'].': ';
            echo '<input type="password" name="hashlink_access_pass" id="hashlink_access_pass" maxlength="25" value="" /><br><br>';

            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#archive_access_pass_form\').submit();">';
            echo $lang['srv_analiza_archive_access_button'];
            echo '</a></div></span><br><br>';
            echo '</form></fieldset></div>';

            #izpišemo še zaključek html
            echo '</div>'."\n";
            echo '</div>'."\n";
            echo '</body>'."\n";
            echo '</html>';
        }
        
        /**
         * Just for acces with password
         */
        function checkHashlinkAccessSessionValues($hash){
            if(isset($_POST['hashlink_access_pass'])){
                if($this->CheckHashAccessPass($hash, $_POST['hashlink_access_pass']))
                    $_SESSION['hashlink_access'][$hash] = '1';
                else
                    $_SESSION['hashlink_access'][$hash] = '0';
            }
        }
}
