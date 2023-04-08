<?php

/**
 *
 *	Class ki skrbi za izris GDPR dokumentov v latex
 *
 *
 */


//include('../../function.php');
include('../../vendor/autoload.php');

 
class LatexGDPR{
	
	protected $anketa;

	protected $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	protected $pdf;
	protected $currentStyle;
		
	protected $texNewLine = '\\\\ ';
	protected $texBigSkip = '\bigskip';
	
	
	function __construct($anketa=null){
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) ){
			$this->anketa = $anketa;
        }
        else{
			$this->pi['msg'] = "Anketa ni izbrana!";
            $this->pi['canCreate'] = false;
            
			return false;
		}
			
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;		
	}
	
	
	public function displayGDPR($export_subtype=''){
		global $lang;
            
        $tex = '';

        // Definiramo
        $tex = '';
        
         // Izpis posameznega porocila
         if($export_subtype == 'individual'){

            // Naslov dokumenta
            $tex .= '\noindent\MakeUppercase{\huge \textbf{'.$lang['export_gdpr_individual'].'}}'.$this->texBigSkip.$this->texNewLine.$this->texNewLine;

            // Pridobimo array z vsemi texti
            $text_array = GDPR::getGDPRInfoArray($this->anketa);
        }
        elseif($export_subtype == 'activity'){

            // Naslov dokumenta
            $tex .= '\noindent\MakeUppercase{\huge \textbf{'.$lang['export_gdpr_activity'].'}}'.$this->texBigSkip.$this->texNewLine.$this->texNewLine;

            // Pridobimo array z vsemi texti
            $text_array = GDPR::getGDPREvidencaArray($this->anketa);
        }

        
        // Loop po posameznih sklopih
        foreach($text_array as $sklop){

            // Naslov sklopa
            $tex .= '\textbf{'.$sklop['heading'].'}';
            $tex .= $this->texNewLine;

            // Loop po posameznih vrsticah
            foreach($sklop['text'] as $vrstica){

                //$tex .= '\text{'.$vrstica.'}';
                $tex .= '{'.$vrstica.'}';
                $tex .= $this->texNewLine;
            }

            $tex .= $this->texNewLine;
        }

        $tex .= $this->texNewLine.$lang['date'].': '.date('j.n.Y').$this->texNewLine;


        // Se pobarvamo text znotraj <strong> taga
        //$tex = str_replace('<strong>', '\textcolor{1ka_orange}{', $tex);
        $tex = str_replace('<strong>', '\textcolor{crta}{', $tex);
        $tex = str_replace('</strong>', '}', $tex);
    
        // Se replacamo href-e
        preg_match_all("|<a.*(?=href=\"([^\"]*)\")[^>]*>([^<]*)</a>|i", $tex, $matches);
        foreach($matches[0] as $key => $val){

            $url = $matches[1][$key];
            $url_text = $matches[2][$key];
            
            $tex = str_replace($matches[0][$key], '\textcolor{crta}{\underline{\href{'.$url.'}'.'{'.$url_text.'}}}', $tex);
        }


		return $tex;
    }
    
}