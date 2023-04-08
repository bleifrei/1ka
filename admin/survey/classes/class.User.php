<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 16.03.2018
 *****************************************/

class User {

	private static $_instance;

	private $user;

	public function __construct($user_id = null)
	{

	    if(is_null($user_id)){
          global $global_user_id;
          $user_id = $global_user_id;
      }

		$this->user = sisplet_query("SELECT * FROM users WHERE id='" . $user_id. "'", "obj");
	}

	public static function getInstance($user_id = null)
	{
		if (!self::$_instance) {
			self::$_instance = new User($user_id);
		}
		return self::$_instance;
	}

	/**
	 * Vrne polje vseh emailov s statusom kateri je aktivni
	 *
	 * @return array
	 */
	public function allEmails($without_master = FALSE)
	{
		$emails = $this->emails();

		if ($without_master) {
			unset($emails['master']);
		}

		return $emails;
	}

	private function emails()
	{
		$alternative_email_sql = sisplet_query("SELECT id, email, active FROM user_emails WHERE user_id='" . $this->user->id . "'", "obj");


		// Če ni akternativnih emailov vrni primarnega
		if (empty($alternative_email_sql)) {
			return [
				'master' => (object) [
					'id' => NULL,
					'email' => $this->user->email,
					'active' => '1',
				],
			];
		}

		if (!empty($alternative_email_sql->email)) {
			$alternative_email[] = $alternative_email_sql;
		} elseif ($alternative_email_sql) {
			$alternative_email = $alternative_email_sql;
		}

		$alternative_email['master'] = (object) [
			'id' => NULL,
			'email' => $this->user->email,
			'active' => '1',
		];

		return $alternative_email;
	}

	/**
	 * Pridobimo primarni email, ki ga uporabnik uporablja
	 *
	 * @return mixed
	 */
	public function primaryEmail()
	{
		$emails = $this->emails();

		foreach ($emails as $email) {
			if ($email->active == 1) {
				return $email->email;
			}
		}
	}

	public static function findByEmail($email = null){

	    $user_id = sisplet_query("SELECT id FROM users WHERE email='".$email."'", "obj");
        if(!empty($user_id)){
            return $user_id->id;
        }

        // Preverimo, če uporablja alternativni email
        $alternativni = sisplet_query("SELECT user_id FROM user_emails WHERE email='".$email."'", "obj");
            if(!empty($alternativni)){
                return $alternativni->user_id;
        }

        return null;
    }

    public static function findByEmail_AAI($email, $aai_id){

	    $user_id = sisplet_query("SELECT id FROM users WHERE email='".$email."'", "obj");
        if(!empty($user_id)){

            // Ce se nimamo zabelezenega aai_id-ja (uuid), ga pri prvi novi prijavi zabelezimo
            sisplet_query("UPDATE users SET aai_id='".$aai_id."' WHERE id='".$user_id->id."' AND email='".$email."' AND aai_id=''");

            return $user_id->id;
        }

        // Preverimo, če obstaja racun s tem aai id (uuid)
        $user_id = sisplet_query("SELECT id FROM users WHERE aai_id='".$aai_id."'", "obj");
        if(!empty($user_id)){

            // Ce obstaja pomeni da je bil aai email spremenjen - ga popravimo se v bazi
            sisplet_query("UPDATE users SET email='".$email."' WHERE id='".$user_id->id."' AND aai_id='".$aai_id."'");

            return $user_id->id;
        }

        return null;
    }

	public function insertAlternativeEmail($email = NULL, $active = 0)
	{
		if (is_null($email) || !validEmail($email) || !unikatenEmail($email)) {
			return NULL;
		}

		// Preverimo če email obstaja me duporabniki
		sisplet_query("INSERT INTO user_emails (user_id, email, active, created_at) VALUES ('" . $this->user->id . "', '" . $email . "', '" . $active . "', NOW())");

		return true;
	}

    /**
     * Vrnemo dodatne opcije, ki so vezane na uporabnika
     *
     * @param null $user
     * @param null $name
     *
     * @return null
     */
	public static function option($user = null, $name = null){
	    $option = sisplet_query("SELECT option_value FROM user_options WHERE user_id='".$user."' AND option_name='".$name."'", "obj");

	    if(!empty($option))
	        return $option->option_value;

	    return null;
  }

  public function setOption($name = null, $value = null)
  {
      if(is_null($name) || is_null($value))
          return null;

      $option = sisplet_query("SELECT id FROM user_options WHERE user_id='".$this->user->id ."' AND option_name='".$name."'", "obj");

      if(!empty($option)){
          sisplet_query("UPDATE user_options SET option_value='".$value."' WHERE user_id='".$this->user->id."' AND id='".$option->id."'");
      }
      else{;
          sisplet_query("INSERT INTO user_options (user_id, option_name, option_value, created_at) VALUES ('".$this->user->id."', '".$name."', '".$value."', NOW())");
      }
  }

  // Vrnemo polje userja
  public function getSetting($setting){
	  
		if(isset($this->user->$setting))
			return $this->user->$setting;
		else {
			return false;
		}
  }
  
}