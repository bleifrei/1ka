<?php

class SurveyAapor {
	
	private $userBy = array();
	private $_surveyId;
	private $_aapor = array();

	public $usability = array();
	public $userByStatus = array();
	//public $cntUserByStatus = array('valid'=>0, 'nonvalid'=>0, 'invitation'=>0);
	public $cntUserByStatus = array();
	public $appropriateStatus = array(6,5);
	public $unAppropriateStatus = array('6l','5l',4,3);
	public $invitationStatus = array(2,1,0);
	public $testDataCount = 0;

	public $cntValidRedirections = 0;					// Stejemo vlejavne referale (ki vsebujejo tekst)
	public $cntNonValidRedirections = 0;				// Stejemo neveljavne referale (ki ne vsebujejo teksta)
	public $userRedirections = array(3=>0,4=>0,5=>0,6=>0,'valid' => array('email'=>0),'email'=>0,'direct'=>0);					// za porazdelitev redirekcij na anketo
	public $maxRedirection = 0;							// koliko je maksimalno število klikov (za primerno širino diva)
	public $maxCharRedirection = 0;						// max stevilo znakovv v "host" (za lepsi izpis redirekcij)

	public $realUsersByStatus_all = 0;					// skupaj frekvenc
	public $realUsersByStatus = array();				// frekvenca po posameznem statusu (uposeteva da ce je kdo koncal anketo jo je tudi zacel)
	public $respondentLangArray = array();				# grupiranje po jezikih

	public $tmp_direct = 0;
	public $emailStatus = array(0,1,2);

	function __construct($cntAnswers,$answers,$surveyId) {
		global $lang;
		
		$this->cntUserByStatus = $cntAnswers; 
		$this->userByStatus = $answers;
		$this->_surveyId = $surveyId;
		//$this->izracunajPodatke($this->_surveyId);
		//error_log("ID: ".$surveyId);
		
		$sur = new SurveyUsableResp($surveyId, $generateDatafile=false);
		
		if(!$sur->hasDataFile())
			echo $lang['srv_dashboard_no_file']; // Ce se ni zgenerirana datoteka s podatki izpisemo error
		else
			$this->usability = $sur->calculateData(); // Dobimo array z usability podatki
		//var_dump($this->usability);

		$page = $_GET['m'];

		if(strcmp($page,'aapor2') == 0)
			$this->calculateFullAapor();
		else if(strcmp($page,'aapor1') == 0)
			$this->calculateAapor();
	}

	function calculateAapor() {
		global $lang;
			$s6 = (int)$this->userByStatus['valid'][6];
			$s5 = (int)$this->userByStatus['valid'][5];
			$s6l = (int)$this->userByStatus['nonvalid']['6l'];
			$s5l = (int)$this->userByStatus['nonvalid']['5l'];
			$s4 = (int)$this->userByStatus['nonvalid'][4];
			$s3 = (int)$this->userByStatus['nonvalid'][3];
			$s2 = (int)$this->userByStatus['nonvalid'][2];
			$s1 = (int)$this->userByStatus['nonvalid'][1];
			$s0 = (int)$this->userByStatus['nonvalid'][0];
			$s_1 = (int)$this->userByStatus['nonvalid'][-1];
			
			

	   /*     debug
			echo '<br>'.$s6;
			echo '<br>'.$s5;
			echo '<br>'.$s6l;
			echo '<br>'.$s5l;
			echo '<br>'.$s4;
			echo '<br>'.$s3;
			echo '<br>'.$s2;
			echo '<br>'.$s1;
			echo '<br>'.$s0;
			echo '<br>'.$s_1;
			*/

		/* Po starem še
		 * $rr1 = @$s6 / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+($s2+$s0));
			$rr2 = @($s6+$s5) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+($s2+$s0));
			$rr3 = @$s6 / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+0.5*($s2+$s0));
			$rr4 = @($s6+$s5) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+0.5*($s2+$s0));
			$rr5 = @$s6 / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1));
			$rr6 = @($s6+$s5) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1));

			$con1 = @($s6+$s5+$s6l+$s5l+$s4+$s3) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+($s2+$s0));
			$con2 = @($s6+$s5+$s6l+$s5l+$s4+$s3) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1)+0.5*($s2+$s0));
			$con3 = @($s6+$s5+$s6l+$s5l+$s4+$s3) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1));

			$pror = @($s6+$s5) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1));
			$comr = @($s6+$s5) / ( ($s6+$s5)+($s6l+$s5l+$s4+$s3+$s1));




			pregledovanje
			$crr = $pror * $comr;
		*/


			//$this->izracunajPodatke($this->_surveyId);
			$usable = $this->usability['usable'];
			$partusable = $this->usability['partusable'];
			$all = $this->usability['all'];
			$niodgo = (int)$this->_answwers['invitation'][1];
			$rr1_apro = 0;
			$rr2_apro = 0;
			$rr5_apro = 0;
			$rr6_apro = 0;
			if($all > 0){
				$rr1_apro = $this->formatNumber(($usable)/$all,3,'');
				//$rr5_apro = $this->formatNumber(($usable)/($all-$niodgo),3,'');
				$rr2_apro = $this->formatNumber(($usable+$partusable)/$all,3,'');
				//$rr6_apro = $this->formatNumber(($usable+$partusable)/($all-$niodgo),3,'');
			}

			echo '<div class="floatLeft">';
				//echo 'Uporabni: '.$usability['usable'].'<br> Delno uporabni: '.$usability['partusable'].'<br> Vsi:'.$usability['all'].'<br><br>';
				echo '<b>'.$lang['srv_aapor_show_approximate_calculation'].'</b><br>';
				echo '<label>RR1\': '.$rr1_apro.'</label><br>';
				echo '<label>RR2\': '.$rr2_apro.'</label><br>';
				//echo '<label>RR5\': '.$rr5_apro.'</label><br>';
				//echo '<label>RR6\': '.$rr6_apro.'</label><br>';
			echo '</div>';
		/*echo '<b>AAPOR response rate glede na 1KA statuse:</b>';
		echo '<br />RR1 = '.$this->formatNumber($rr1,3,'');
		echo '<br />RR2 = '.$this->formatNumber($rr2,3,'');
		echo '<br /><span class="red strong">RR3 = '.$this->formatNumber($rr3,3,'').'</span>';
		echo '<br />RR4 = '.$this->formatNumber($rr4,3,'');
		echo '<br />RR5 = '.$this->formatNumber($rr5,3,'');
		echo '<br />RR6 = '.$this->formatNumber($rr6,3,'');
		echo '<br />';
		echo '<br /><b>Contact rates:</b>';
		echo '<br />CON1 = '.$this->formatNumber($con1,3,'');
		echo '<br />CON2 = '.$this->formatNumber($con2,3,'');
		echo '<br />CON3 = '.$this->formatNumber($con3,3,'');
		echo '<br />';
		echo '<br /><b>AAPOR paneli:</b>';
		echo '<br />Profile rate: PROR = '.$this->formatNumber($pror,3,'');
		echo '<br />Completion rate: COMR = '.$this->formatNumber($comr,3,'');
		echo '<br />';
		echo '<br /><b>Cumulative response rate:</b>';
		echo '<br />CUMRR = '.$this->formatNumber($crr,3,'');*/

			//echo '</div>';
			
			echo '<div class="floatLeft" style="margin-left:50px">';
			echo '<b>Statusi:</b>';
			echo '<br/>';
			$arrayStatusi = array('6'=>$s6,'6l'=>$s6l,'5'=>$s5,'5l'=>$s5l,'4'=>$s4,'3'=>$s3,'2'=>$s2,'1'=>$s1,'0'=>$s0);
			foreach ($arrayStatusi as $status => $value) {
				echo '<span class="dashboard_status_span">' . $lang['srv_userstatus_'.$status] . ' ('.$status.') :</span>' . $value
							. '<br/>';
			}
			echo '<br /><br/>';
			
			echo 'Povezave:<br/>';
		   // echo '<a href="http://www.aapor.org/For_Researchers/4683.htm" target="_blank">';
			echo '<a href="https://www.esomar.org/knowledge-and-standards/research-resources/aapor-standard-definitions.php" target="_blank">';
			echo 'Standard Definitions – Final Dispositions of Case Codes and Outcome Rates for Surveys (PDF)';
			echo '</a>';
			echo '</div>';
			echo '<br class="clr"/>';
	}

	function calculateFullAapor(){
		global $lang;

		echo '<div class="floatLeft">';
		echo '<h2>'.$lang['srv_lnk_AAPOR2'].'</h2>';
		echo'
			<form id="aaporForm">
			<input type="hidden" name="anketa" value="'.$this->_surveyId.'" />
			<table  id="aapor_table">
			<tbody>
				<tr>
					<td></td><td><label>'.$lang['srv_aapor_show_approximate'].'</label><input onchange="prikazi('.$this->_surveyId.')" id="prikazipriblizek" type="checkbox" name="prikaziPriblizek" /></td>
				</tr>
				<tr>
					<td><span class="main_title_aapor">Returne questionaire (1.0)</span></td><td><input class="main_aapor main_title_aapor" type="text" name="rq" readonly/></td>
				</tr>
				<tr>
					<td><span class="category_span">Complete (1.1)</span></td><td><input onchange="calculateReturne()" class="input_aapor category_span" type="text" name="complete" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Partial or break-off (1.2)</span></td><td><input onchange="calculateReturne()" class="input_aapor category_span" type="text" name="partial" /></td>
				</tr>
				<tr>
					<td><span class="main_title_aapor">Eligible (2.0)</span></td><td><input class="main_aapor main_title_aapor" type="text" name="eligible" readonly/></td>
				</tr>
				<tr>
					<td><span class="category_span">Refusal (2.11)</span></td><td><input class="read_aapor category_span" type="text" name="refusal" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Explicit refusal (2.111)</span></td><td><input onchange="calculateRefusal()" class="input_aapor subcategory1_span" type="text" name="refusalEx" /></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Implicit refusal (2.112)</span></td><td><input class="read_aapor subcategory1_span" type="text" name="refusalIm" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory2_span">Logged on to survay, did not complete any items (2.1121)</span></td><td><input onchange="calculateRefusalIm()" class="input_aapor subcategory2_span" type="text" name="loggedNotComplete" /></td>
				</tr>
				<tr>
					<td><span class="subcategory2_span">Read receipt confirmation, refusal (2.1122)</span></td><td><input onchange="calculateRefusalIm()" class="input_aapor subcategory2_span" type="text" name="readReceiptConfirmation" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Brak-off or partial with insufficient information (2.12)</span></td><td><input onchange="calculateEligible()" class="input_aapor category_span" type="text" name="breakOff" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Not-Contact (2.20)</span></td><td><input onchange="calculateEligible()" class="read_aapor category_span" type="text" name="nonContact" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Respondenr was unavailable during field period (2.26)</span></td><td><input onchange="calculateNonContact()" class="input_aapor subcategory1_span" type="text" name="respondentUnavailable" /></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Completed questionnaire, but not retuned during field period (2.27)</span></td><td><input onchange="calculateNonContact()" class="input_aapor subcategory1_span" type="text" name="completedNotReturned" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Other (2.30)</span></td><td><input onchange="calculateEligible()" class="read_aapor category_span" type="text" name="otherEligible" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Language barrier (2.33)</span></td><td><input onchange="calculateOtherEligible()" class="input_aapor subcategory1_span" type="text" name="languageBarrier" /></td>
				</tr>
				<tr>
					<td><span class="main_title_aapor">Unknown eligible, "non-interview" (3.0)</span></td><td><input class="main_aapor main_title_aapor" type="text" name="unknownEligible" readonly/></td>
				</tr>
				<tr>
					<td><span class="category_span">Nothing known about respondent or address (3.10)</span></td><td><input onchange="calculateUnknownEligibility()" class="read_aapor category_span" type="text" name="nothingKnown" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">No invitation sent (3.11)</span></td><td><input onchange="calculateNothingKnownRespondent()" class="input_aapor subcategory1_span" type="text" name="noInvitation" /></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Nothing ever returned (3.19)</span></td><td><input onchange="calculateNothingKnownRespondent()" class="input_aapor subcategory1_span" type="text" name="nothingReturned" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Invitation returned undelivered (3.30)</span></td><td><input onchange="calculateUnknownEligibility()" class="input_aapor category_span" type="text" name="invitationReturnedUndelivered" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Invitation returned with forwarding information (3.40)</span></td><td><input onchange="calculateUnknownEligibility()" class="input_aapor category_span" type="text" name="invitationReturnedForwarding" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Other (3.90)</span></td><td><input onchange="calculateUnknownEligibility()" class="read_aapor category_span" type="text" name="otherUnknownEligible" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Returned from a unsampled email address (3.91)</span></td><td><input onchange="calculateOtherUnknownEligibility()" class="input_aapor subcategory1_span" type="text" name="returnedUnsampledEmail" /></td>
				</tr>
				<tr>
					<td><span class="main_title_aapor">Not eligible, Returned (4.0)</span></td><td><input class="main_aapor main_title_aapor" type="text" name="notEligible" readonly/></td>
				</tr>
				<tr>
					<td><span class="category_span">Selected Respondent Screende Out of Sample (4.10)</span></td><td><input onchange="calculateNotEligible()" class="input_aapor category_span" type="text" name="selectedRespondent" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Quota Filled (4.80)</span></td><td><input onchange="calculateNotEligible()" class="read_aapor category_span" type="text" name="quotaFilled" readonly/></td>
				</tr>
				<tr>
					<td><span class="subcategory1_span">Duplicate Listing (4.81)</span></td><td><input onchange="calculateQuotaFilled()" class="input_aapor subcategory1_span" type="text" name="duplicateListing" /></td>
				</tr>
				<tr>
					<td><span class="category_span">Other (4.90)</span></td><td><input onchange="calculateNotEligible()" class="input_aapor category_span" type="text" name="otherNotEligible" /></td>
				</tr>
				<tr id="totalSub">
					<td class="totalSubTd">Skupna vsota:</td><td><span class="totalSubSpan"></span></td>
				</tr>
				<tr id="totalSub">
					<td class="totalSubTd">'.$lang['srv_statistic_sum_all'].'</td><td><span class="totalSubDatabaseSpan"></span></td>
				</tr>
				<tr>
					<td class="aaporSpace"></td>
				</tr>
				 <tr>
					<td><span class="main_title_aapor aapor_e">e <span style="font-size: 70%">['.$lang['srv_statistic_e_description'].']<span></span></td><td><input class="input_aapor" type="text" name="e" /></td>
				</tr>
				<tr>
					<td colspan="2"><span class="floatLeft spaceRight aaporButton"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="getCallculationAapor('.$this->_surveyId.')"><span>'.$lang['srv_aapor_calculation'].'</span></a></div></span></td>
				</tr>
			</tbody>
			</table>
			</form>
		';
		echo '</div>';
		echo '<div class="floatLeft show_calculation">';
		echo '<h2>'.$lang['srv_aapor_show_calculation'].'</h2>';
		echo '<table>
				<tbody>
					<tr>
						<td>RR1:</td><td><span id="rr1"></span></td>
					</tr>
					<tr>
						<td>RR2:</td><td><span id="rr2"></span></td>
					</tr>
					<tr>
						<td>RR3:</td><td><span id="rr3"></span></td>
					</tr>
					<tr>
						<td>RR4:</td><td><span id="rr4"></span></td>
					</tr>
					<tr>
						<td>RR5:</td><td><span id="rr5"></span></td>
					</tr>
					<tr>
						<td>RR6:</td><td><span id="rr6"></span></td>
					</tr>
				</tbody>
			  </table>';
		echo '</div>';;
	}
	function calculationForFullAapor(){
		$data = array();
		$rr1 = 0;
		$rr2 = 0;
		$rr3 = 0;
		$rr4 = 0;
		$rr5 = 0;
		$rr6 = 0;

		$refusal = $_POST['refusal'];
		$refusal = $refusal=='' || !is_numeric($refusal) ? 0:$refusal;

		$breakOff = $_POST['breakOff'];
		$breakOff = $breakOff=='' || !is_numeric($breakOff) ? 0:$breakOff;

		$invitationReturnedUndelivered = $_POST['invitationReturnedUndelivered'];
		$invitationReturnedUndelivered = $invitationReturnedUndelivered=='' || !is_numeric($invitationReturnedUndelivered) ? 0:$invitationReturnedUndelivered;

		$invitationReturnedForwarding = $_POST['invitationReturnedForwarding'];
		$invitationReturnedForwarding = $invitationReturnedForwarding=='' || !is_numeric($invitationReturnedForwarding) ? 0:$invitationReturnedForwarding;

		$otherUnknownEligible = $_POST['otherUnknownEligible'];
		$otherUnknownEligible = $otherUnknownEligible=='' || !is_numeric($otherUnknownEligible) ? 0:$otherUnknownEligible;

		$i = $_POST['complete'];
		$i = $i=='' || !is_numeric($i) ? 0:$i;

		$p = $_POST['partial'];
		$p = $p=='' || !is_numeric($p) ? 0:$p;

		$r = $refusal+$breakOff;

		$nc = $_POST['nonContact'];
		$nc = $nc=='' || !is_numeric($nc) ? 0:$nc;

		$o = $_POST['otherEligible'];
		$o = $o == '' || !is_numeric($o) ? 0:$o;

		$uh = $_POST['nothingKnown'];
		$uh = $uh=='' || !is_numeric($uh) ? 0:$uh;

		$uo = $invitationReturnedUndelivered + $invitationReturnedForwarding + $otherUnknownEligible;

		$e = $_POST['e'];
		$e = $e=='' || !is_numeric($e) ? 100:$e;
		if($e > 100 || $e<0){
			$e = 100;
		}
		$e = $e/100;
		//error_log("E je ".$e);
		$sub1 = $i+$p+$r+$nc+$o+$uh+$uo;
		$sub2 = $i+$p+$r+$nc+$o+$e*($uh+$uo);
		$sub3 = $i+$p+$r+$nc+$o;

		//error_log($refusal." - ".$breakOff." - ".$invitationReturnedUndelivered." - ".$invitationReturnedForwarding." - ".$otherUnknownEligible." - ".$i."- ".$p." - ".$r." - ".$nc." - ".$o." - ".$uh." - ".$uo." - ".$e);

		if($sub1 > 0){
			$rr1 = $i/($sub1);
			$rr2 = ($i+$p)/($sub1);
		}
		if($sub2 > 0){
			$rr3 = $i/($sub2);
			$rr4 = ($i+$p)/($sub2);
		}
		if($sub3 > 0){
			$rr5 = $i/($sub3);
			$rr6 = ($i+$p)/($sub3);
		}

		$data['rr1'] = $this->formatNumber($rr1,3,'');
		$data['rr2'] = $this->formatNumber($rr2,3,'');
		$data['rr3'] = $this->formatNumber($rr3,3,'');
		$data['rr4'] = $this->formatNumber($rr4,3,'');
		$data['rr5'] = $this->formatNumber($rr5,3,'');
		$data['rr6'] = $this->formatNumber($rr6,3,'');

		echo json_encode($data);
	}

	function izracunajPodatke($id){
		/*$sur = new SurveyUsableResp($id, $generateDatafile=false);

		if($sur->hasDataFile())
			$this->usability = $sur->calculateData(); // Dobimo array z usability podatki*/
		$qry_string = "SELECT id, language, last_status, lurker, inv_res_id, referer FROM srv_user WHERE ank_id = '".$id."' AND preview = '0' AND deleted='0'";
		$qry = sisplet_query($qry_string);

		if (mysqli_num_rows($qry) > 0) {
			$user_id_to_check_link = array(); # id-ji uporabnikov pri katerih imamo direkten klik. naknadno ugotavljamo ali je slučajno e-mail vabilo
			while ($row = mysqli_fetch_assoc($qry)) {
				if ((int)$row['testdata'] > 0) {
					$this->testDataCount++;
				}
				// dodamo statuse
				if (in_array($row['last_status'], $this->appropriateStatus))
				{
					# če ni lurker je ok
					if ($row['lurker'] == 0)
					{
						$this->userByStatus['valid'][$row['last_status']] += 1;
						$this->cntUserByStatus['valid'] += 1;
					}
					else
					{
						# če je lurker ga dodamo k neveljavnim
						$this->userByStatus['nonvalid'][$row['last_status'].'l'] += 1;
						$this->cntUserByStatus['nonvalid'] += 1;
					}
				}
				# neveljavne enote
				else if (in_array($row['last_status'], $this->unAppropriateStatus))
				{
					$this->userByStatus['nonvalid'][$row['last_status']] += 1;
					$this->cntUserByStatus['nonvalid'] += 1;
				}
				# emaili
				else if (in_array($row['last_status'], $this->invitationStatus))
				{
					$this->userByStatus['invitation'][$row['last_status']] += 1;
					$this->cntUserByStatus['invitation'] += 1;
				}

				#polovimo redirekte
				if (in_array((int)$row['last_status'], $this->invitationStatus))
				{
					# email vabila ... ne lovimo redirektov
					# podatek o referalu je prazen lahko da email ni bil poslan, ali pa gre za direkten link
					#$this->cntNonValidRedirections += 1;
					#$this->userRedirections[(int)$row['last_status']] += 1;
				}
				else {
					# če so vabila
					if ($row['inv_res_id'] != null )
					{
						$this->cntValidRedirections += 1;
						$this->userRedirections["valid"]['email'] += 1;
						$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["valid"]['email']);
					}
					# če imamo referal
					else if ($row['referer'] != "")
					{
						$parsed = parse_url($row['referer']);
						$this->cntValidRedirections += 1;
						$this->userRedirections["valid"][$parsed['host']] += 1;
						$this->maxCharRedirection = max($this->maxCharRedirection , strlen ($parsed['host']) );
						$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["valid"][$parsed['host']] );
					}
					# če ne je najbrž direkten link
					else
					{
						# shranimo id_userjev za katere nato ugotavljamo ali je link res direkten ali obstaja kaksen zapis da je slo preko e-maila
						$user_id_to_check_link[] = $row['id'];
						$this->tmp_direct +=1;
					}
				}
				#polovimo jezike
				if (isset($respondentLangArray[$row['language']]))
				{
					$respondentLangArray[$row['language']] ++;
				}
				else
				{
					$respondentLangArray[$row['language']] = 1;
				}
			}
		}

		# od direktnega klika odštejemo e-mail vabila
		if (count($user_id_to_check_link)> 0) {
			$qry_stringEmail = "SELECT COUNT(*) as cnt FROM srv_userstatus  WHERE usr_id IN (".implode(',', $user_id_to_check_link).") AND status IN (".implode(',', $this->emailStatus).")";
			$qryEmail = sisplet_query($qry_stringEmail);
			$rwsEmail = mysqli_fetch_assoc($qryEmail);
			$this->userRedirections["email"] = (int)$rwsEmail['cnt'];
			$this->userRedirections["direct"] = (int)$this->tmp_direct - (int)$rwsEmail['cnt'];
		}
		// prestejemo max stevilo klikov za lepsi izris tabele
		$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["2"], $this->userRedirections["1"], $this->userRedirections["0"],$this->userRedirections["direct"], $this->userRedirections['email']);

		# izracunamo realne frekvence po statusih
		# Klik na anketo - vsak ki je končal anketo (itd...) je "najbrž" tudi kliknil na anketo..
		$this->realUsersByStatus_all = $this->userByStatus['valid'][6]
			+ $this->userByStatus['valid'][5]
			+ $this->userByStatus['nonvalid']['5l']
			+ $this->userByStatus['nonvalid']['6l']
			+ $this->userByStatus['nonvalid'][4]
			+ $this->userByStatus['nonvalid'][3]
			+ $this->userByStatus['nonvalid'][-1];
		// Klik na prvo stran - vsak ki je končal anketo (itd...) je "najbrž" tudi kliknil na anketo..
		# končal anketo => 6
		$this->realUsersByStatus[6] = array('cnt'=>$this->userByStatus['valid'][6], 'percent'=>0);
		# začel izpolnjevat => 5 = 6 + 5
		$this->realUsersByStatus[5] = array('cnt'=>$this->userByStatus['valid'][5]+$this->realUsersByStatus[6]['cnt'], 'percent'=>0);
		# Koliko ljudi je dejansko končalo anketo ne glede na to ali so lurkerji 6 + 6l
		$this->realUsersByStatus['6ll'] = array('cnt'=>$this->userByStatus['nonvalid']['6l']+$this->realUsersByStatus[6]['cnt'], 'percent'=>0);
		# delno izpolnjena 4ll => 6 + 5 + 6l + 5l
		$this->realUsersByStatus['5ll'] = array('cnt'=>$this->userByStatus['nonvalid']['6l']+$this->userByStatus['nonvalid']['5l']+$this->realUsersByStatus[5]['cnt'], 'percent'=>0);
		# klik na prvo stran => 4 = 6 + 6l + 5l + 5 + 4
		$this->realUsersByStatus['4ll'] = array('cnt'=>$this->userByStatus['nonvalid'][4]+$this->realUsersByStatus['5ll']['cnt'], 'percent'=>0);
		# klik na anketo => 3 = 6 + 6l + 5l + 5 + 4 + 3
		$this->realUsersByStatus['3ll'] = array('cnt'=>$this->userByStatus['nonvalid'][3]+$this->userByStatus['nonvalid'][-1]+$this->realUsersByStatus['4ll']['cnt'], 'percent'=>0);
		//if ($this->emailInvitation == 1)
		{
			$this->realUsersByStatus['email']
				= array('cnt'=>(isset($this->userByStatus['valid']['email'])?$this->userByStatus['valid']['email']:0), 'percent'=>0);
		}

		$qry_string = "SELECT * FROM srv_invitations_recipients WHERE ank_id='".$id."' AND deleted='0'";
		$qry = sisplet_query($qry_string);
		$this->userByStatus['invitation'][0]=0;
		$this->userByStatus['invitation'][1]=0;
		$this->userByStatus['invitation'][2]=0;
		while ($row = mysqli_fetch_assoc($qry)) {
			if(in_array($row['last_status'], $this->invitationStatus)){
				$this->userByStatus['invitation'][$row['last_status']] += 1;
			}
		}
	}
	function prikaziPriblizek(){
		$data = array();
		error_log("Id v metodi priblizek: ".$this->_surveyId);
		
		/*$ss = new SurveyStatistic();
		$ss->Init($this->_surveyId);
		$ss->PrepareDateView();
		$this->userByStatus = $ss->getUserByStatus();*/
		//$this->izracunajPodatke($id);
		//var_dump($this->cntUserByStatus);

		$usable = $this->usability['usable'];
		$partusable = $this->usability['partusable'];
		$unusable = $this->usability['unusable'];
		$status3 = $this->userByStatus['nonvalid'][3];
		$status4 = $this->userByStatus['nonvalid'][4];
		$status34 = 0;
		$status0 = $this->userByStatus['invitation'][0];
		$status2 = $this->userByStatus['invitation'][2];
		$status02 = 0;
		$status1 = $this->userByStatus['invitation'][1];
		//if(!is_null($status3) && !is_null($status4) && is_int($status3) && is_int($status4)){
		$status34 = $status3+$status4;
		//}
		//if(!is_null($status0) && !is_null($status2) && is_int($status0) && is_int($status2)){
		$status02 = $status0+$status2;
		//}

		$skupaj = $this->cntUserByStatus['valid']+$this->cntUserByStatus['nonvalid']+$this->cntUserByStatus['invitation'];

		$data['usable'] = is_null($usable) || !is_int($usable) ? 0 : $usable;
		$data['partusable'] = is_null($partusable) || !is_int($partusable) ? 0 : $partusable;
		$data['unusable'] = is_null($unusable) || !is_int($unusable) ? 0 : $unusable;
		$data['status1'] = is_null($status1) || !is_int($status1) ? 0 : $status1;
		$data['status34'] = $status34;
		$data['status02'] = $status02;
		$data['skupaj'] = $skupaj;

		echo json_encode($data);
	}

	/** Lepo oblikuje number string
	 *
	 * @param float $value
	 * @param int $digit
	 * @param string $sufix
	 * @return string
	 */
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
				$result = round($value, $digit);
		else
				$result = "0";
		$result = number_format($result, $digit, '.', ',') . $sufix;

		return $result;
	}

}
