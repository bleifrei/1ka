<?php
/***************************************
 * Description: Omogočanje dostopa za SA modul
 * Autor: Robert Šmalc
 * Created date: 28.09.2017
 *****************************************/

namespace Hierarhija\Ajax;


class AjaxHierarhijaDostopUporabnikovClass {

	public function __construct()
	{
		global $admin_type;

		if($admin_type != 0)
			return false;

		if (!$this->isAjax())
			return redirect('/admin/survey/');
	}

	public function popupNew($id = null){
		$user = null;

		if(!empty($id)) {
			$user = sisplet_query("SELECT id, u.name, u.surname, u.email, d.ustanova, d.aai_email FROM srv_hierarhija_dostop AS d LEFT JOIN users AS u ON u.id=d.user_id WHERE id='".$id."'", "obj");

			if(empty($user->email))
				$user = null;
		}

		global $lang;
		echo '<div class="sa-modul">';

		echo '<div>';
		if(!empty($user)){
			echo '<h2> Urejanje uporabnika <span class="red">'.$user->name.' ' .$user->surname.'</span> za dostop do SA modula</h2>';
		}else {
			echo '<h2> Vpiši uporabnika za dostop do SA modula</h2>';
		}
		echo '<div>';

		// EMAIL
		echo '<div style="width: 100%;">';
		echo '<label>Elektronski naslov</label><br/>';
			if(!empty($user)){
				echo '<input type="email" value="'.$user->email .'" disabled="disabled" style="width: 80%; padding: 4px; margin: 2px 0;"/>';
			}else{
				echo '<input type="email" 
	                        value="" 
	                        placeholder="janez.novak@sola.si" 
	                        id="sa-email" 
	                        onblur="preveriVpisanEmailZaSAdostop()"
	                        required="required" 
	                        style="width: 80%; padding: 4px; margin: 2px 0;"/>';
				echo '<input type="hidden" value="" id="sa-id" "/>';
				echo '<span id="sa-email-sporocilo"></span>';
			}
		echo '</div>';

		// Organizzacija
		echo '<div style="padding:15px 0;width: 100%;">';
		echo '<label>Šola oz. javni zavod</label><br/>';
		echo '<input type="text" value="'.(!empty($user) ? $user->ustanova : NULL).'" placeholder="Srednja šola" id="sa-ustanova" required="required" style="width: 80%; padding: 4px; margin: 2px 0;"/>';
		echo '</div>';

		// Email za AAI dostop
		echo '<div style="padding-bottom:15px; width: 100%;">';
		echo '<label>Elektronski naaslov za AAI dostop (v kolikor je primarni email drugačen od AAI dostopa)</label><br/>';
		echo '<input type="email" value="'.(!empty($user) ? $user->aai_email : NULL).'" placeholder="janez.novak@guest.arnes.si" id="sa-aai" style="width: 80%; padding: 4px; margin: 2px 0;"/>';
		echo '</div>';

		echo '</div>';
		echo '</div>';

		// Gumb za zapret popup in potrdit
		echo '<div class="sa-modul">';
		echo '<div class="buttonwrapper spaceRight floatLeft">';
		if(!empty($user)) {
			echo '<a class="ovalbutton ovalbutton_orange sa-potrdi" href="#" onclick="posodobiSAuporabnika(\'' . $user->id . '\')"; return false;"><span>' . $lang['srv_potrdi'] . '</span></a>' . "\n\r";
		}else {
			echo '<a class="ovalbutton ovalbutton_orange sa-potrdi" href="#" onclick="shraniSAuporabnika()"; return false;"><span>' . $lang['srv_potrdi'] . '</span></a>' . "\n\r";
		}
		echo '</div>';

		echo '<div class="buttonwrapper spaceRight floatLeft">';
		echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $lang['srv_close_profile'] . '</span></a>' . "\n\r";
		echo '</div>';
		echo '</div>';

		echo '</div>';

	}

	public function save(){
		$email = (!empty($_POST['email']) ? $_POST['email'] : null);
		$user_id = (!empty($_POST['id']) ? $_POST['id'] : null);
		$ustanova = (!empty($_POST['ustanova']) ? $_POST['ustanova'] : null);
		$aai_email = (!empty($_POST['aai']) ? $_POST['aai'] : null);

		$uporabnik = sisplet_query("SELECT id, email FROM users WHERE email='".$email."'", "obj");
		if($uporabnik->id != $user_id)
			return false;

		sisplet_query("INSERT INTO srv_hierarhija_dostop (user_id, dostop, ustanova, aai_email) VALUES ('".$uporabnik->id."', '1', '".$ustanova."', '".$aai_email."')");

		echo 'success';
	}

	public function update(){
		$user_id = (!empty($_POST['id']) ? $_POST['id'] : null);
		$ustanova = (!empty($_POST['ustanova']) ? $_POST['ustanova'] : null);
		$aai_email = (!empty($_POST['aai']) ? $_POST['aai'] : null);

		sisplet_query("UPDATE srv_hierarhija_dostop SET ustanova='".$ustanova."' , aai_email='".$aai_email."' WHERE user_id='".$user_id."'");

		echo 'success';
	}

	public function delete(){
		$user_id = (!empty($_POST['id']) ? $_POST['id'] : null);

		sisplet_query("DELETE FROM srv_hierarhija_dostop WHERE user_id='".$user_id."'");
	}

	public function checkUserEmail(){
		$email = (!empty($_POST['email']) ? $_POST['email'] : null);

		if(!validEmail($email)) {
			echo json_encode([
				                 'tip' => 'error',
				                 'sporocilo' => 'Napačen email.'
			                 ]);

			return FALSE;
		}

		$uporabnik = sisplet_query("SELECT id, email FROM users WHERE email='".$email."'", "obj");

		if(empty($uporabnik)) {
			echo json_encode([
				                 'tip' => 'error',
				                 'sporocilo' => 'Uporabnika z omenjenim emailom ni v bazi.'
			                 ]);

			return FALSE;
		}

		echo json_encode([
			                 'tip' => 'success',
			                 'sporocilo' => 'Email pravilen, ker uporabnik obstaja v bazi.',
			                 'id' => $uporabnik->id
		                 ]);
	}

	public function show(){
		global $lang;
		global $global_user_id;
		global $admin_type;

		$user_id = (!empty($_POST['id']) ? $_POST['id'] : null);

		if(is_null($user_id))
			return false;


		echo '<div style="float: right; width: 250px; max-height: 345px; overflow-y: auto">';
		echo '<h3><strong>'.$lang['srv_ankete'].'</strong></h3>';

		echo '<ul>';
		$sql = sisplet_query("SELECT srv_anketa.id, srv_anketa.naslov FROM srv_dostop, srv_anketa WHERE srv_dostop.uid='$user_id' AND srv_dostop.ank_id=srv_anketa.id ORDER BY srv_anketa.edit_time DESC");
		while ($row = mysqli_fetch_array($sql)) {
			echo '<li><a href="#" onclick="anketa_user_dostop(\''.$uid.'\', \''.$row['id'].'\'); return false;">'.$row['naslov'].'</a></li>';
		}

		echo '</ul>';
		echo '</div>';

		$user = sisplet_query("SELECT id, u.name, u.surname, u.email, d.ustanova, d.aai_email, u.type, u.status, DATE_FORMAT(d.created_at, '%d.%m.%Y - %H:%i') AS created, DATE_FORMAT(d.updated_at, '%d.%m.%Y - %H:%i') AS updated FROM srv_hierarhija_dostop AS d LEFT JOIN users AS u ON u.id=d.user_id WHERE user_id='".$user_id."'", "obj");

		echo '<div class="sa-modul">';
		echo '<h3><strong>'.$lang['user2'].'</strong></h3>';
		echo '<p><label for="type">'.$lang['admin_type'].':</label>';
			switch ($user->type){
				case 0:
					echo $lang['admin_manager'];
					break;
				case 1:
					 echo $lang['admin_manager'];
					 break;
				case 2:
					echo $lang['admin_clan'];
					break;
				default:
					echo $lang['admin_narocnik'];
			}
		echo '</p>';
		echo '<p><label for="status">'.$lang['status'].':</label>';
		switch ($user->status){
			case 0:
				echo $lang['srv_user_banned'];
				break;
			case 1:
				echo $lang['srv_user_notbanned'];
				break;
		}
		echo '</p>';
		echo '<p><label for="email">'.$lang['email'].':</label>'.$user->email.'</p>';
		echo '<p><label for="name">'.$lang['name'].':</label>'.$user->name.'</p>';
		echo '<p><label for="surname">'.$lang['surname'].':</label>'.$user->surname.'</p>';
		echo '<p><label for="ustanova">'.$lang['srv_hierarchy_users_organization'].':</label>'.$user->ustanova.'</p>';
		echo '<p><label for="aai_uporabnik">'.$lang['srv_hierarchy_users_aai'].':</label>'.$user->aai_email.'</p>';
		echo '<p><label for="created">'.$lang['srv_hierarchy_users_created'].':</label>'.$user->created.'</p>';
		echo '<p><label for="updatetd">'.$lang['srv_hierarchy_users_updated'].':</label>'.$user->updated.'</p>';

		// Gumb za zapret popup in potrdit
		echo '<div style="    display: block;">';
			echo '<div class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $lang['srv_close_profile'] . '</span></a>' . "\n\r";
			echo '</div>';
		echo '</div>';
		echo '</div>';


	}


	/**
	 * Preverimo, če je ajax request
	 *
	 * @return boolean
	 */
	private function isAjax()
	{
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			return true;

		return false;
	}

}