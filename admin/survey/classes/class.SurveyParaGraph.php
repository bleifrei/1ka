<?php

class SurveyParaGraph{

	private $anketa;									# id ankete
	private $paraGraph_filter = array();				# Filtriranje parapodatkov (po napravi ali po statusu)
	
	function __construct($anketa){
		global $global_user_id;
		
		if((int)$anketa > 0){			
			$this->anketa = $anketa;
			
			SurveyStatusProfiles :: Init($this->anketa);		
		} 
		else{
			echo 'Invalid Survey ID!';
			exit();
		}
	}
        
    function setParaGraphFilter($pgf) { 

        $this->paraGraph_filter = $pgf; 
    }
	
	function DisplayParaGraph(){
		global $lang;
		global $site_path;
		global $admin_type;

		// Nastavimo filter
		$this->paraGraphSetFilter();
		
		// Popravimo stare ankete -> v bazo vnesemo browser, os, device, popravljen js
		$this->paraGraphFixOld();
		
		// Zberemo podatke vseh userjev
		$paraData = $this->collectParaGraphDataNew();
					
		
		echo '<p>'.$lang['srv_para_graph_text'].'</p>';
		
		
		// PC, tablica, mobi
		echo '<fieldset><legend>'.$lang['srv_para_graph_device'].'</legend>';		

		// Filter po napravi
		echo '<div style="margin:5px 0 15px 5px;">';
		echo '<label>'.$lang['srv_analiza_filter'].': </label>';
		echo '<label for="paraGraph_filter_pc"><input type="checkbox" id="paraGraph_filter_pc" '.($this->paraGraph_filter['pc']==1 ? ' checked="checked"' : '').' onClick="changeParaGraphFilter();">'.$lang['srv_para_graph_device0'].'</label>';
		echo ' <label for="paraGraph_filter_mobi"><input type="checkbox" id="paraGraph_filter_mobi" '.($this->paraGraph_filter['mobi']==1 ? ' checked="checked"' : '').' onClick="changeParaGraphFilter();">'.$lang['srv_para_graph_device1'].'</label>';
		echo ' <label for="paraGraph_filter_tablet"><input type="checkbox" id="paraGraph_filter_tablet" '.($this->paraGraph_filter['tablet']==1 ? ' checked="checked"' : '').' onClick="changeParaGraphFilter();">'.$lang['srv_para_graph_device2'].'</label>';
		echo ' <label for="paraGraph_filter_robot"><input type="checkbox" id="paraGraph_filter_robot" '.($this->paraGraph_filter['robot']==1 ? ' checked="checked"' : '').' onClick="changeParaGraphFilter();">'.$lang['srv_para_graph_device3'].'</label>';
		echo '&nbsp;&nbsp;&nbsp;<label>('.$lang['srv_para_graph_filteredCnt'].' '.$paraData['allCount'].')</label>';
		echo '</div>';
	
		
		echo '<table style="width:100%">';
		
		echo '<tr>';
		echo '<th nowrap>'.$lang['srv_para_graph_device0'].'</th>';
		echo '<td style="width:100%">';
		echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['pcCount']/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
		echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['pcCount'].'</span></span>';
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<th nowrap>'.$lang['srv_para_graph_device1'].'</th>';
		echo '<td style="width:100%">';
		echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['mobiCount']/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
		echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['mobiCount'].'</span></span>';
		echo '</td>';		
		echo '</tr>';
		
		echo '<tr>';
		echo '<th nowrap>'.$lang['srv_para_graph_device2'].'</th>';
		echo '<td style="width:100%">';
		echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['tabletCount']/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
		echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['tabletCount'].'</span></span>';
		echo '</td>';
		echo '</tr>';
		
		// Zaenkrat nimamo detekcije robotov, ker se uporablja browscap lite
		/*echo '<tr>';
		echo '<th nowrap>'.$lang['srv_para_graph_device3'].'</th>';
		echo '<td style="width:100%">';
		echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['robotCount']/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
		echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['robotCount'].'</span></span>';
		echo '</td>';		
		echo '</tr>';

		//echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
		//echo '<tr><td></td><th style="text-align:left; padding-right: 20px" nowrap>'.$lang['srv_anl_suma1'].': '.$paraData['allCount'].'</th></tr>';*/
		
		echo '</table>';
		
        echo '</fieldset>';
        
		
		// Browser		
		echo '<fieldset><legend>'.$lang['srv_para_graph_browser'].'</legend>';		

		echo '<table style="width:100%">';

		if(count($paraData['browser']) > 0){
			if(count($paraData['browser']) > 1)
				ksort($paraData['browser'], SORT_REGULAR);
			
			foreach($paraData['browser'] as $key => $browserCnt){
							
				if($key != $lang['srv_para_graph_other_slo'] && $key != $lang['srv_para_graph_other_ang']){
					echo '<tr>';
					echo '<th nowrap>'.$key.'</th>';
					echo '<td style="width:100%">';
					echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $browserCnt/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
					echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$browserCnt.'</span></span>';
					echo '</td>';
					echo '</tr>';	
				}
			}
		
			if(isset($paraData['browser'][$lang['srv_para_graph_other_slo']]) && $paraData['browser'][$lang['srv_para_graph_other_slo']] > 0){
				echo '<tr>';
				echo '<th nowrap>'.$lang['srv_para_graph_other'].'</th>';
				echo '<td style="width:100%">';
				echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['browser'][$lang['srv_para_graph_other_slo']]/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
				echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['browser'][$lang['srv_para_graph_other_slo']].'</span></span>';
				echo '</td>';
				echo '</tr>';
			}
			
			if(isset($paraData['browser'][$lang['srv_para_graph_other_ang']]) && $paraData['browser'][$lang['srv_para_graph_other_ang']] > 0){
				echo '<tr>';
				echo '<th nowrap>'.$lang['srv_para_graph_other'].'</th>';
				echo '<td style="width:100%">';
				echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['browser'][$lang['srv_para_graph_other_ang']]/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
				echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['browser'][$lang['srv_para_graph_other_ang']].'</span></span>';
				echo '</td>';
				echo '</tr>';
			}
			
			//echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
			//echo '<tr><td></td><th style="text-align:left; padding-right: 20px" nowrap>'.$lang['srv_anl_suma1'].': '.$paraData['allCount'].'</th></tr>';
		}
		
		echo '</table>';
		
		echo '</fieldset>';
		
		
		// Operacijski sistem		
		echo '<fieldset><legend>'.$lang['srv_para_graph_os'].'</legend>';		

		echo '<table style="width:100%">';

		if(count($paraData['os']) > 0){		
			if(count($paraData['os']) > 1)
				ksort($paraData['os'], SORT_REGULAR);
			
			foreach($paraData['os'] as $key => $osCnt){
				
				if($key != $lang['srv_para_graph_other_slo'] && $key != $lang['srv_para_graph_other_ang']){
					echo '<tr>';
					echo '<th nowrap>'.$key.'</th>';
					echo '<td style="width:100%">';
					echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $osCnt/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
					echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$osCnt.'</span></span>';
					echo '</td>';
					echo '</tr>';
				}
			}
			
			if(isset($paraData['os'][$lang['srv_para_graph_other_slo']]) && $paraData['os'][$lang['srv_para_graph_other_slo']] > 0){
				echo '<tr>';
				echo '<th nowrap>'.$lang['srv_para_graph_other'].'</th>';
				echo '<td style="width:100%">';
				echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['os'][$lang['srv_para_graph_other_slo']]/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
				echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['os'][$lang['srv_para_graph_other_slo']].'</span></span>';
				echo '</td>';
				echo '</tr>';
			}
			
			if(isset($paraData['os'][$lang['srv_para_graph_other_ang']]) && $paraData['os'][$lang['srv_para_graph_other_ang']] > 0){
				echo '<tr>';
				echo '<th nowrap>'.$lang['srv_para_graph_other'].'</th>';
				echo '<td style="width:100%">';
				echo '  <div class="graph_lb" style="text-align: right; float: left; width: '.($paraData['allCount']>0 ? $paraData['os'][$lang['srv_para_graph_other_ang']]/$paraData['allCount']*85 : '0').'%">&nbsp;</div>';
				echo '  <span style="display: block; margin: auto auto auto 5px; float: left">'.$paraData['os'][$lang['srv_para_graph_other_ang']].'</span></span>';
				echo '</td>';
				echo '</tr>';
			}
			
			//echo '<tr><td colspan="3" style="border-bottom:1px solid #E4E4F9"></td></tr>';
			//echo '<tr><td></td><th style="text-align:left; padding-right: 20px" nowrap>'.$lang['srv_anl_suma1'].': '.$paraData['allCount'].'</th></tr>';
		}
		
		echo '</table>';
		
		echo '</fieldset>';
	}

	
	function collectParaGraphData(){
		global $lang;
		
		SurveySetting::getInstance()->Init($this->anketa);
		
		// Preberemo tabelo s podatki za izbrane filtre (ce ze obstaja)
		$filterString = implode('_', $this->paraGraph_filter);
		$paraData = unserialize(SurveySetting::getInstance()->getSurveyMiscSetting('para_graph_data_'.$filterString));
		
		// Pogledamo kdaj je bila kreirana datoteka (ce imamo nove podatke)
		$sqlTime = sisplet_query("SELECT UNIX_TIMESTAMP(last_update) AS last_update FROM srv_data_files WHERE sid='".$this->anketa."'");
		$rowTime = mysqli_fetch_array($sqlTime);
		$time = $rowTime['last_update'];

		$status = ($this->paraGraph_filter['status'] == 1) ? ' AND last_status>\'4\'' : ' AND last_status>\'2\'';
		$sqlu = sisplet_query("SELECT useragent FROM srv_user WHERE ank_id='".$this->anketa."' ".$status." AND preview='0' AND deleted='0'");
		
		// Ce se nimamo shranjenih izracunov (timestamp datoteke se ne ujema), racunamo na novo
		if(!isset($paraData['allCount']) || $paraData['timestamp'] != $time || $_GET['refresh'] == 1){

			$sqlu2 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='".$this->anketa."' AND last_status>'2' AND u.preview='0' AND u.deleted='0'");
			
			$paraData = array(
				'timestamp'			=> $time,
				'unfilteredCount'	=> mysqli_num_rows($sqlu2),
				'allCount' 			=> 0, 
				'pcCount' 			=> 0, 
				'mobiCount' 		=> 0, 
				'tabletCount' 		=> 0, 
				'robotCount' 		=> 0, 
				'browser' 			=> array(),
				'os' 				=> array()
			);
								
			$detect = New Mobile_Detect();

			// Loop cez vse ustrezne respondente
			while($rowu = mysqli_fetch_array($sqlu)){
				
				// Detect mobilnikov in tablic
				$detect->setUserAgent($rowu['useragent']);
					
				// Detect z browscap	
				$browserDetect = get_browser($rowu['useragent'], true);
				
				// Filtriranje po napravi
				if(  ($this->paraGraph_filter['pc'] != 0 || $detect->isMobile() || $browserDetect['crawler'] == 1)
						&& ($this->paraGraph_filter['tablet'] != 0 || !$detect->isTablet())
						&& ($this->paraGraph_filter['mobi'] != 0 || !$detect->isMobile() || $detect->isTablet())
						&& ($this->paraGraph_filter['robot'] != 0 || $browserDetect['crawler'] != 1) ){
						
					// Naprava
					if($detect->isMobile()) {			
						if($detect->isTablet())
							$paraData['tabletCount']++;
						else
							$paraData['mobiCount']++;
					}
					elseif($browserDetect['crawler'] == 1)
						$paraData['robotCount']++;
					else
						$paraData['pcCount']++;
										
					// Browser
					if($browserDetect['browser'] == 'Default Browser')
						$browser = $lang['srv_para_graph_other'];
					else
						$browser = $browserDetect['browser'].' '.$browserDetect['version'];
					$paraData['browser'][$browser]++;
					
					// OS
					if($browserDetect['platform'] == 'unknown')
						$os = $lang['srv_para_graph_other'];
					else
						$os = $browserDetect['platform'];
					
					$paraData['os'][$os]++;
					
			
					$paraData['allCount']++;
				}
			}
			
			// Na koncu shranimo nove izracune v bazo	
			SurveySetting::getInstance()->setSurveyMiscSetting('para_graph_data_'.$filterString, serialize($paraData));
		}
		
		return $paraData;
	}
	
	function collectParaGraphDataNew(){
		global $lang;
				
		$paraData = array(
			/*'timestamp'			=> $time,*/
			'unfilteredCount'	=> 0,
			'allCount' 			=> 0, 
			'pcCount' 			=> 0, 
			'mobiCount' 		=> 0, 
			'tabletCount' 		=> 0, 
			'robotCount' 		=> 0, 
			'browser' 			=> array(),
			'os' 				=> array()
		);
			

		// Filter za status
		$status_filter = ($this->paraGraph_filter['status'] == 1) ? ' AND last_status>\'4\' AND lurker=\'0\'' : ' AND last_status>\'2\'';
		
		// Filter za napravo
		$device_filter = ' AND (';	
		$device_filter .= ($this->paraGraph_filter['pc'] != 0) ? 'device=\'0\' OR ' : '';
		$device_filter .= ($this->paraGraph_filter['mobi'] != 0) ? 'device=\'1\' OR ' : '';
		$device_filter .= ($this->paraGraph_filter['tablet'] != 0) ? 'device=\'2\' OR ' : '';
		$device_filter .= ($this->paraGraph_filter['robot'] != 0) ? 'device=\'3\' OR ' : '';
		$device_filter = substr($device_filter, 0, -4) . ')';

	
		// Prestejemo vse
		$sql = sisplet_query("SELECT id FROM srv_user WHERE ank_id='".$this->anketa."' AND last_status>'2' AND useragent!='' AND preview='0' AND deleted='0'");
		$paraData['unfilteredCount'] = mysqli_num_rows($sql);
		
		// Prestejemo vse filtrirane
		$sql = sisplet_query("SELECT id FROM srv_user WHERE ank_id='".$this->anketa."' ".$status_filter." ".$device_filter." AND useragent!='' AND preview='0' AND deleted='0'");
		$paraData['allCount'] = mysqli_num_rows($sql);
					
		// Prestejemo naprave
		$sql = sisplet_query("SELECT device, count(*) FROM srv_user WHERE ank_id='".$this->anketa."' ".$status_filter." ".$device_filter." AND useragent!='' AND preview='0' AND deleted='0'
							GROUP BY device");
		while($row = mysqli_fetch_array($sql)){			
			if($row['device'] == 0)
				$paraData['pcCount'] = $row['count(*)'];
			elseif($row['device'] == 1)
				$paraData['mobiCount'] = $row['count(*)'];
			elseif($row['device'] == 2)
				$paraData['tabletCount'] = $row['count(*)'];
			elseif($row['device'] == 3)
				$paraData['robotCount'] = $row['count(*)'];
		}		
				
		// Prestejemo browserje
		$sql = sisplet_query("SELECT browser, count(*) FROM srv_user WHERE ank_id='".$this->anketa."' ".$status_filter." ".$device_filter." AND useragent!='' AND preview='0' AND deleted='0'
							AND browser!='' GROUP BY browser");
		while($row = mysqli_fetch_array($sql)){
			$paraData['browser'][$row['browser']] = $row['count(*)'];
		}
			
		// Prestejemo os
		$sql = sisplet_query("SELECT os, count(*) FROM srv_user WHERE ank_id='".$this->anketa."' ".$status_filter." ".$device_filter." AND useragent!='' AND preview='0' AND deleted='0'
							AND os!='' GROUP BY os");
		while($row = mysqli_fetch_array($sql)){
			$paraData['os'][$row['os']] = $row['count(*)'];
		}	

		
		return $paraData;
	}
	
	
	function paraGraphSetFilter(){
		
		// Nastavimo filter po statusu (vsi ali ustrezni)
		$this->paraGraph_filter['status'] = (SurveyStatusProfiles::getDefaultProfile() == 2) ? 1 : 0; 
		/*if(isset($_GET['status']))
			$this->paraGraph_filter['status'] = $_GET['status'];
		else
			$this->paraGraph_filter['status'] = 0;*/
			
		// Nastavimo filter po napravi (pc, mobi, tablica, crawler)	
		$this->paraGraph_filter['pc'] = (isset($_GET['pc'])) ? $_GET['pc'] : 1;
		$this->paraGraph_filter['tablet'] = (isset($_GET['tablet'])) ? $_GET['tablet'] : 1;
		$this->paraGraph_filter['mobi'] = (isset($_GET['mobi'])) ? $_GET['mobi'] : 1;
		$this->paraGraph_filter['robot'] = (isset($_GET['robot'])) ? $_GET['robot'] : 1;
	}
	
	
	// Se pozene za stare ankete ki imajo v bazi samo useragent (samo prvic ko gledamo staro anketo) oz. ce hocemo na novo zracunat vse ($_GET['refresh'] == 1)
	function paraGraphFixOld(){
		global $lang;
		
		if(isset($_GET['refresh']) && $_GET['refresh'] == 1)
			$sqlu = sisplet_query("SELECT id, useragent FROM srv_user WHERE ank_id='".$this->anketa."' AND last_status>'2' AND useragent!='' AND preview='0' AND deleted='0'");
		else
			$sqlu = sisplet_query("SELECT id, useragent FROM srv_user WHERE ank_id='".$this->anketa."' AND last_status>'2' AND useragent!='' AND browser='' AND preview='0' AND deleted='0'");
		if(mysqli_num_rows($sqlu) > 0){
	
			$detect = New Mobile_Detect();
			
			$cnt = 0;
			while($rowu = mysqli_fetch_array($sqlu)){
				
				// Detect mobilnikov in tablic
				$detect->setUserAgent($rowu['useragent']);
					
				// Detect z browscap	
				$browserDetect = get_browser($rowu['useragent'], true);
											
				// Naprava
				if($detect->isMobile()) {			
					if($detect->isTablet())
						$device = 2;
					else
						$device = 1;
				}
				elseif($browserDetect['crawler'] == 1)
					$device = 3;
				else
					$device = 0;
								
				// Browser
				if($browserDetect['browser'] == 'Default Browser')
					$browser = $lang['srv_para_graph_other'];
				else
					$browser = $browserDetect['browser'].' '.$browserDetect['version'];
				
				// OS
				if($browserDetect['platform'] == 'unknown')
					$os = $lang['srv_para_graph_other'];
				else
					$os = $browserDetect['platform'];
				
				$sqlu2 = sisplet_query("UPDATE srv_user SET device='$device', browser='$browser', os='$os' WHERE ank_id='".$this->anketa."' AND id='$rowu[id]'");
				if (!$sqlu2)
					echo mysqli_error($GLOBALS['connect_db']);
					
				$cnt++;
			}
			
			echo '<div style="float:right; margin:-10px 20px 0 0;">Posodobljenih je bilo '.$cnt.' starih vnosov.</div>';
		}
	}
	
}