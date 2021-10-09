<?php
// USEFUL http://support.microsoft.com/kb/270906
// ne tak dober: http://www.devx.com/asp/Article/17964/0/page/3

/*
 * Created on 28.2.2009
 *
 * ahthor: Gorazd Veselic
 *
 * Za potrebe razširitev rtf
 */

require_once('class.rtf.php');

class enka_RTF extends RTF
{

	/** prekodiramo šumnike
	 *
	 */
	function enkaEncode($msg){
	
		// Ce so slucajno znaki v cirilici
		if(preg_match('/[А-Яа-яЁё]/u', $msg)){
		
			// Pretvorimo encoding (cp1251 -> cirilica)
			$msg = iconv ("UTF-8", "CP1251//IGNORE", $msg);	
			
			// Zamenjamo znake v cirilici
			$newMsg = "";
			for($i = 0; $i < strlen($msg); $i++){
			
				$char = "";
				switch ( ord($msg[$i]) ){

					case /*А*/ 192: $char = "\\u1040x"; break;
					case /*а*/ 224: $char = "\\u1072x"; break;
					case /*Б*/ 193: $char = "\\u1041x"; break;
					case /*б*/ 225: $char = "\\u1073x"; break;
					case /*В*/ 194: $char = "\\u1042x"; break;
					case /*в*/ 226: $char = "\\u1074x"; break;
					case /*Г*/ 195: $char = "\\u1043x"; break;
					case /*г*/ 227: $char = "\\u1075x"; break;
					case /*Д*/ 196: $char = "\\u1044x"; break;
					case /*д*/ 228: $char = "\\u1076x"; break;
					case /*Е*/ 197: $char = "\\u1045x"; break;
					case /*е*/ 229: $char = "\\u1077x"; break;
					case /*Ж*/ 198: $char = "\\u1046x"; break;
					case /*ж*/ 230: $char = "\\u1078x"; break;
					case /*З*/ 199: $char = "\\u1047x"; break;
					case /*з*/ 231: $char = "\\u1079x"; break;
					case /*И*/ 200: $char = "\\u1048x"; break;
					case /*и*/ 232: $char = "\\u1080x"; break;
					case /*Й*/ 201: $char = "\\u1049x"; break;
					case /*й*/ 233: $char = "\\u1081x"; break;
					case /*К*/ 202: $char = "\\u1050x"; break;
					case /*к*/ 234: $char = "\\u1082x"; break;
					case /*Л*/ 203: $char = "\\u1051x"; break;
					case /*л*/ 235: $char = "\\u1083x"; break;
					case /*М*/ 204: $char = "\\u1052x"; break;
					case /*м*/ 236: $char = "\\u1084x"; break;
					case /*Н*/ 205: $char = "\\u1053x"; break;
					case /*н*/ 237: $char = "\\u1085x"; break;
					case /*О*/ 206: $char = "\\u1054x"; break;
					case /*о*/ 238: $char = "\\u1086x"; break;
					case /*П*/ 207: $char = "\\u1055x"; break;
					case /*п*/ 239: $char = "\\u1087x"; break;
					case /*Р*/ 208: $char = "\\u1056x"; break;
					case /*р*/ 240: $char = "\\u1088x"; break;
					case /*С*/ 209: $char = "\\u1057x"; break;
					case /*с*/ 241: $char = "\\u1089x"; break;
					case /*Т*/ 210: $char = "\\u1058x"; break;
					case /*т*/ 242: $char = "\\u1090x"; break;
					case /*У*/ 211: $char = "\\u1059x"; break;
					case /*у*/ 243: $char = "\\u1091x"; break;
					case /*Ф*/ 212: $char = "\\u1060x"; break;
					case /*ф*/ 244: $char = "\\u1092x"; break;
					case /*Х*/ 213: $char = "\\u1061x"; break;
					case /*х*/ 245: $char = "\\u1093x"; break;
					case /*Ц*/ 214: $char = "\\u1062x"; break;
					case /*ц*/ 246: $char = "\\u1094x"; break;
					case /*Ч*/ 215: $char = "\\u1063x"; break;
					case /*ч*/ 247: $char = "\\u1095x"; break;
					case /*Ш*/ 216: $char = "\\u1064x"; break;
					case /*ш*/ 248: $char = "\\u1096x"; break;
					case /*Щ*/ 217: $char = "\\u1065x"; break;
					case /*щ*/ 249: $char = "\\u1097x"; break;
					case /*Ъ*/ 218: $char = "\\u1066x"; break;
					case /*ъ*/ 250: $char = "\\u1098x"; break;
					case /*Ы*/ 219: $char = "\\u1067x"; break;
					case /*ы*/ 251: $char = "\\u1099x"; break;
					case /*Ь*/ 220: $char = "\\u1068x"; break;
					case /*ь*/ 252: $char = "\\u1100x"; break;
					case /*Э*/ 221: $char = "\\u1069x"; break;
					case /*э*/ 253: $char = "\\u1101x"; break;
					case /*Ю*/ 222: $char = "\\u1070x"; break;
					case /*ю*/ 254: $char = "\\u1102x"; break;
					case /*Я*/ 223: $char = "\\u1071x"; break;
					case /*я*/ 255: $char = "\\u1103x"; break;

					default: $char = $msg[$i]; break;
				}
				$newMsg .= $char;
			}
		}	
		// Drugace popravljamo čšž-je
		else{
			// Pretvorimo encoding
			$msg = iconv ("UTF-8", "CP1250", $msg);
			
			// Zamenjamo čšž znake
			$newMsg = "";
			for($i = 0; $i < strlen($msg); $i++){
				
				$char = "";
				switch ( ord($msg[$i]) ){
				
					/* TODO:
					 * đ -> 240
					 * ć -> 230
					 * Đ -> 208
					 * Ć -> 198
					 */
					 
					// š -> 154 => \\u0353s
					case 154: $char = "\\u0353s"; break;
					// č -> 232 => \\u0269c
					case 232: $char = "\\u0269s"; break;
					// ž -> 158 => \\u0382z
					case 158: $char = "\\u0382z"; break;
					// Š -> 138 => \\u0352S
					case 138: $char = "\\u0352S"; break;
					// Č -> 200 => \\u0268C
					case 200: $char = "\\u0268C"; break;
					// Ž -> 142 => \\u0381Z
					case 142: $char = "\\u0381Z"; break;
					//263
					case 230: $char = "\\u0263c"; break;

					case 198: $char = "\\u0262C"; break;
					
					default: $char = $msg[$i]; break;
				}
				$newMsg .= $char;
			}
		}	
		
		$msg = $newMsg;

		$msg = str_replace ("&scaron;", "\u0353s", $msg);
		$msg = str_replace ("&Scaron;", "\u0352S", $msg);
		//$msg = str_replace (array("\'c4\u141\'8d", "\'c5\'be", "\'c5\'a1", "Č", "Ž", "Š"), array("\'e8", "\'9e", "\'9a", "\'c8", "\'8e", "\'8a"), $msg);

		return $msg;
	}

	function add_text($msg, $align = 'left')
	{

		$this->align($align);
		$this->MyRTF .= "{";

		if (empty($this->TextDecoration))
		{
			$this->TextDecoration .= $this->_font($this->dfl_FontID);
			$this->TextDecoration .= $this->_font_size($this->dfl_FontSize);
		}

		$this->MyRTF .= $this->TextDecoration;
		$this->MyRTF .= "{";
		$this->MyRTF .= $this->enkaEncode($msg);
		$this->MyRTF .= "}} ";

		$this->TextDecoration = '';

	}

	function draw_title($title, $align = 'left')
	{
		//global $this;

		$this->set_font("Arial Black", 15);
		$TITLE = $this->bold(1) . $this->underline(1) . $title . $this->underline(0) . $this->bold(0);
		$this->new_line();
		$this->add_text($TITLE, $align);
		$this->new_line();
		$this->new_line();
	}

	/** WriteTitle & author
	 *  extending: Write the title and author for the document properties
	 *
	 */
	function WriteTitle($title = 'http://www.1ka.si/', $author = 'http://www.1ka.si/')
	{
		$this->MyRTF .= "{\\info{\\title ".$this->enkaEncode($title)."}{\\author ".$this->enkaEncode($author)."}}";
	}

	/** WriteHeader
	 * extending: Write the page header
	 */
	 function WriteHeader($header = "", $align='center', $landscape=false)
	 {
		/*$this->MyRTF .= "{\\header\\pard";
		$this->align($align);
		$this->MyRTF .= "{";
		$this->WriteBorder('bottom');
		$this->MyRTF .= "\\fs22 ".$this->enkaEncode($header)."\\par}}";*/
		
		$extend_width = ($landscape) ? 1.5 : 1;
		
		$this->MyRTF .= "{\\header\\fs22";
		
		$tableHeader = '\trowd\trql\trrh400';
		$table = '\clvertalc\clbrdrb\brdrs\brdrw10\cellx'.(5000*$extend_width);	
		$tableEnd = '\pard\intbl '.$this->enkaEncode('www.1ka.si').'\ql\cell';			
		$table .= '\clvertalc\clbrdrb\brdrs\brdrw10\cellx'.(9400*$extend_width);	
		$tableEnd .= '\pard\intbl '.$this->enkaEncode($header).'\qr\cell';				
		$tableEnd .= '\pard\intbl\row';			
		$this->MyRTF .= $this->enkaEncode($tableHeader.$table.$tableEnd);
		
		$this->MyRTF .= "}";
	 }

	/** WriteFooter
	 * extending: Write the page footer
	 */
	 function WriteFooter($footer = "", $align='right', $landscape=false)
	 {
		$footer = str_replace("{PAGE}", "{\\field{\\*\\fldinst PAGE}{\\fldrslt 1}}", $footer);
		$footer = str_replace("{NUMPAGES}", "{\\field{\\*\\fldinst NUMPAGES}{\\fldrslt 1}}", $footer);

		$date = date("d.m.Y");
		
		
		/*$this->MyRTF .= "{\\footer\\pard";
		$this->align($align);
		$this->WriteBorder('top');
		$this->MyRTF .= "\\fs18 ". $this->enkaEncode($footer) .
						"\\par}";*/
						
		$extend_width = ($landscape) ? 1.5 : 1;
						
		$this->MyRTF .= "{\\footer\\fs18";
		
		$tableHeader = '\trowd\trql\trrh400';
		$table = '\clvertalc\clbrdrt\brdrs\brdrw10\cellx'.(5000*$extend_width);	
		$tableEnd = '\pard\intbl '.$this->enkaEncode($date).'\ql\cell';			
		$table .= '\clvertalc\clbrdrt\brdrs\brdrw10\cellx'.(9400*$extend_width);	
		$tableEnd .= '\pard\intbl '.$this->enkaEncode($footer).'\qr\cell';				
		$tableEnd .= '\pard\intbl\row';			
		$this->MyRTF .= $this->enkaEncode($tableHeader.$table.$tableEnd);
		
		$this->MyRTF .= "}";
	 }

	/** WriteBorder
	 * extending: draw a border
	 */
	 function WriteBorder($borders)
	 {
		if ( is_string ( $borders ) )
			$borders = (array($borders));

		if ( is_array( $borders )  )
			foreach ( $borders as $border )
				switch (strtolower($border))
				{
					case 'top': $this->MyRTF .= "\\brdrt\\brdrs\\brdrw10\\brsp100"; break;
					case 'bottom': $this->MyRTF .= "\\brdrb\\brdrs\\brdrw10\\brsp100"; break;
					case 'left': $this->MyRTF .= "\\brdrl\\brdrs\\brdrw10\\brsp100"; break;
					case 'right': $this->MyRTF .= "\\brdrr\\brdrs\\brdrw10\\brsp100"; break;
				}
	 }

	 function TextCell($text=null,$attribs=array() )
	 {
//		 	print_r($attribs);
		$width = ( ( $attribs['width'] ) ? $attribs['width'] : 9500 );
		$height = ( ( $attribs['height'] ) ? $attribs['height'] : 1 );
		$align = ( ( $attribs['align'] ) ? $attribs['align'] : 'left' );
		$valign = ( ( $attribs['valign'] ) ? $attribs['valign'] : 'center' );
		$tableBorder = ( ( $attribs['border'] ) ? $attribs['border'] : array() );
		$colorF = ( ( $attribs['colorF'] ) ? $attribs['colorF'] : "0" );
		$colorB = ( ( $attribs['colorB'] ) ? $attribs['colorB'] : "0" );


		// narišemo tabelo z eno celico in okvirjem
 		$this->MyRTF .= "\\par\\trowd\\trgaph12\\trleft0\\trrh".( $height*250 )."\\trleft0".$this->vAlignString($valign)
		.$this->TableBorder($tableBorder)."\\cellx".$width."\\pard\\intbl".(($colorB)?" \\cb".$colorB:"").(($colorF)?" \\cf".$colorF:"").$this->alignString($align)." ".( ($text) ? "{".$this->enkaEncode($text)."}" : '{}').(($colorB)?" \\cb0":"").(($colorF)?" \\cf1":"")."\\cell\\pard\\intbl\\row\\pard";
			 
	 }
	
	function TextCells($text1=null,$text2=null )
	 {
//		 	print_r($attribs);
		$width = ( ( $attribs['width'] ) ? $attribs['width'] : 4300 );
		$height = ( ( $attribs['height'] ) ? $attribs['height'] : 1 );
		$align = ( ( $attribs['align'] ) ? $attribs['align'] : 'left' );
		$valign = ( ( $attribs['valign'] ) ? $attribs['valign'] : 'center' );
		$tableBorder = ( ( $attribs['border'] ) ? $attribs['border'] : array() );
		$colorF = ( ( $attribs['colorF'] ) ? $attribs['colorF'] : "0" );
		$colorB = ( ( $attribs['colorB'] ) ? $attribs['colorB'] : "0" );
		$width2 = 7000;

		// narišemo tabelo z dvema celicama in okvirjem
/*  		$this->MyRTF .= "\\par\\trowd\\trgaph12\\trleft0\\trrh".( $height*250 )."\\trleft0".$this->vAlignString($valign)
		.$this->TableBorder($tableBorder)."\\cellx".$width."\\pard\\intbl".(($colorB)?" \\cb".$colorB:"").(($colorF)?" \\cf".$colorF:"").$this->alignString($align)." ".( ($text) ? "{".$this->enkaEncode($text)."}" : '{}').(($colorB)?" \\cb0":"").(($colorF)?" \\cf1":"")."\\cell\\pard\\intbl\\row\\pard"; */
		 
 		$this->MyRTF .= "\\par\\trowd\\trgaph12\\trleft0\\trrh".( $height*250 )."\\trleft0".$this->vAlignString($valign)
		.$this->TableBorder($tableBorder)."\\cellx".$width."\\cellx".$width2."\\pard\\intbl".(($colorB)?" \\cb".$colorB:"").(($colorF)?" \\cf".$colorF:"").$this->alignString($align)." ".( ($text1) ? "{".$this->enkaEncode($text1).": }" : '{}').(($colorB)?" \\cb0":"").(($colorF)?" \\cf1":"")."\\cell \\pard\\intbl ".$this->enkaEncode($text2).": \\cell \\row \\pard";
		
		//$this->MyRTF .= "\\par\\trowd \\trgaph12 \\cellx4200\\cellx7000 \\pard\\intbl ".$this->enkaEncode($text1)."\\cell \\pard\\intbl ".$this->enkaEncode($text2)."\\cell \\row";
			 
	 }

	/**
	 * Vrne text za okvirje celice v tabeli
	 */
	 function TableBorder($borders)
	 {
		$result = "";
		if ( is_string ( $borders ) )
			$borders = (array($borders));

		if ( is_array( $borders )  )
			foreach ( $borders as $border )
			{
				switch (strtolower($border))
				{
					case 'top': $result .= "\\clbrdrt\\brdrth"; break;
					case 'bottom': $result .= "\\clbrdrb\\brdrth"; break;
					case 'left': $result .= "\\clbrdrl\\brdrth"; break;
					case 'right': $result .= "\\clbrdrr\\brdrth"; break;
				}
			}
		return $result;
	 }

	/**
     * Align text and images
     * (This is not intended to be used directly)
     *
     * @arg1		keyword  (left|center|right|justify)
	 * @return: string
     */
	function alignString($where = 'left')
	{
		switch ( strtolower ($where) )
		{
			case 'left':	return "\\ql ";	break;
			case 'center':	return "\\qc ";	break;
			case 'right':	return "\\qr ";	break;
			case 'justify':	return "\\qj ";	break;
			default:				$this->alignString('left');		break;
		}
	}

// \vertalt  	Text is top-aligned (the default).
// \vertalb 	Text is bottom-aligned.
// \vertalc 	Text is centered vertically.
// \vertalj 	Text is justified vertically.
	/**
     * Align text and images
     * (This is not intended to be used directly)
     *
     * @arg1		keyword  (left|center|right|justify)
	 * @return: string
     */
	function vAlignString( $where )
	{
		switch ( strtolower ($where) )
		{
			case 'top':		return " \\clvertalt ";	break;
			case 'middle':	return " \\clvertalc ";	break;
			case 'bottom':	return " \\clvertalb "; break;
			default:	$this->vAlignString('middle'); break;
		}
	}
	public function TableFromArray($cellWidths, $content, $prop = array())
	{
		$border = ( ( $prop['border'] && is_array( $prop['border'] ) )? $prop['border'] : null);
		$headerBox = ( ( isset ( $prop['headerBox'] ) ) ? $prop['headerBox'] : 0 );
		$headerBorder = ( ( isset ( $prop['headerBorder'] ) ) ? $prop['headerBorder'] : array('top','bottom', 'left','right') );
		$spacer = ( ( isset ( $prop['spacer'] ) ) ? $prop['spacer'] : 1 );
		$spacerWidth = ( ( isset ( $prop['spacerWidth'] ) ) ? $prop['spacerWidth'] : 300 );

		$curWidth = 0;

		$resultString = "{\\par\\fs22"
		."\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";

            foreach ( $content as $contentKey => $contentValue )
            {
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = "";
				$tableHeader_title = "";

				$tableHeader_finish = "\\pard\\intbl\\row";
				// $curWidth = 0
				$curWidth = 0;

				foreach ( $contentValue as $key => $value )
				{
					$curentBorder = "";
					if ($contentKey == 0 && $headerBox)
						$curentBorder = $headerBorder;
					else
						$curentBorder = $border[$key];
					$curWidth += ( ( $cellWidths[$key] ) ? $cellWidths[$key] : "1000");

   					$tableHeader_width .= $this->TableBorder($curentBorder)."\\cellx". ( $curWidth );
            		$tableHeader_title .= "\\pard\\intbl\\ql{".$value."}\\cell";

					// dodamo spacer (razen za zadnjo celico)
					if ( $spacer && $key < sizeOf($contentValue)-1)
					{
						$curWidth += $spacerWidth;
   						$tableHeader_width .= "\\cellx". ( $curWidth );
            			$tableHeader_title .= "\\pard\\intbl\\ql{}\\cell";

					}

				}
                $resultString .= $tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish;
			}

			$resultString .= "}";
			$this->MyRTF .= $this->enkaEncode($resultString);
	}
	
	public function TableFromArraySelect($cellWidths, $content, $SeznamBorders = array(), $numOfRows, $prop = array())
	{
		$border = ( ( $prop['border'] && is_array( $prop['border'] ) )? $prop['border'] : null);
		$headerBox = ( ( isset ( $prop['headerBox'] ) ) ? $prop['headerBox'] : 0 );
		$headerBorder = ( ( isset ( $prop['headerBorder'] ) ) ? $prop['headerBorder'] : array('top','bottom', 'left','right') );
		$spacer = ( ( isset ( $prop['spacer'] ) ) ? $prop['spacer'] : 1 );
		$spacerWidth = ( ( isset ( $prop['spacerWidth'] ) ) ? $prop['spacerWidth'] : 300 );

		$curWidth = 0;

		$keyBorder = 0;
		$resultString = "{\\par\\fs22"
		."\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";

            foreach ( $content as $contentKey => $contentValue )
            {
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = "";
				$tableHeader_title = "";

				$tableHeader_finish = "\\pard\\intbl\\row";
				// $curWidth = 0
				$curWidth = 0;

				foreach ( $contentValue as $key => $value )
				{
					$curentBorder = "";
 					if ($contentKey == 0 && $headerBox)
						$curentBorder = $headerBorder;
					else
						$curentBorder = $SeznamBorders[$keyBorder];
					$curWidth += ( ( $cellWidths[$key] ) ? $cellWidths[$key] : "1000");

   					//$tableHeader_width .= $this->TableBorder($curentBorder)."\\cellx". ( $curWidth );
            		//$tableHeader_title .= "\\pard\\intbl\\ql{".$value."}\\cell";
					
					$tableHeader_width .= $this->TableBorder($curentBorder)."\\cellx". ( $curWidth )."\\cellx".( $curWidth + 300);
					if($keyBorder == 0){	//ce je prva vrstica v tabeli
						$tableHeader_title .= "\\pard\\intbl{".$value."}\\cell \\pard\\intbl{".$this->ImageToString("arrowUpSelect.png", "15")."}\\cell";				
					}elseif($keyBorder == ($numOfRows-1)){	//ce je zadnja vrstica v tabeli
						$tableHeader_title .= "\\pard\\intbl{".$value."}\\cell \\pard\\intbl{".$this->ImageToString("arrowDownSelect.png", "15")."}\\cell";
					}else{	//ce je vrstica, ki ni ne zadnja vrstica in ne prva v tabeli
						$tableHeader_title .= "\\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell";
					}
            		

					// dodamo spacer (razen za zadnjo celico)
					if ( $spacer && $key < sizeOf($contentValue)-1)
					{
						$curWidth += $spacerWidth;
   						$tableHeader_width .= "\\cellx". ( $curWidth );
            			$tableHeader_title .= "\\pard\\intbl\\ql{}\\cell";

					}
					$keyBorder++;

				}
                $resultString .= $tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish;
			}

			$resultString .= "}";
			$this->MyRTF .= $this->enkaEncode($resultString);
	}

	public function TableFromArrayDragDrop($cellWidths, $content, $SeznamBorders = array(), $numOfRows, $prop = array())
	{
		$border = ( ( $prop['border'] && is_array( $prop['border'] ) )? $prop['border'] : null);
		$headerBox = ( ( isset ( $prop['headerBox'] ) ) ? $prop['headerBox'] : 0 );
		$headerBorder = ( ( isset ( $prop['headerBorder'] ) ) ? $prop['headerBorder'] : array('top','bottom', 'left','right') );
		$spacer = ( ( isset ( $prop['spacer'] ) ) ? $prop['spacer'] : 1 );
		$spacerWidth = ( ( isset ( $prop['spacerWidth'] ) ) ? $prop['spacerWidth'] : 300 );

		$curWidth = 0;
		
		
		$width1 = 500;
		
		$PredefinedSeznamBorders[0] = array('top', 'left', 'right');
		$PredefinedSeznamBorders[1] = array('left', 'right');
		$PredefinedSeznamBorders[2] = array('right', 'left', 'bottom');
		$PredefinedSeznamBorders[3] = array('top', 'left', 'right', 'bottom');
		
		$keyBorder = 0;
		$resultString = "{\\par\\fs22"
		."\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";

            foreach ( $content as $contentKey => $contentValue )
            {
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = "";
				$tableHeader_title = "";				
				$tableHeader_finish = "\\pard\\intbl\\row";
				// $curWidth = 0
				$curWidth = 0;
				
				//$tableBlankCells = "\\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
				$numOfCells = 6;
				$tableBlankCells = "";
				$tableBlankCellsArrow = "";
				for($i = 0; $i<$numOfCells; $i++){
					$tableBlankCells .= "\\pard\\intbl\\cell ";					
/*  					if($i == 3){
						$tableBlankCellsArrow .="\\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell ";
					}else{
						$tableBlankCellsArrow .="\\pard\\intbl\\cell ";	
					} */
				}
				//$tableBlankCellsArrow = "\\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell ";
				
				$tableBlank_width = "";
				
				foreach ( $contentValue as $key => $value )
				{
					$curentBorder = "";
 					if ($contentKey == 0 && $headerBox)
						$curentBorder = $headerBorder;
					else
						$curentBorder = $SeznamBorders[$keyBorder];
					
					$curWidth += ( ( $cellWidths[$key] ) ? $cellWidths[$key] : "1000");
					

					$tableBlank_width = "\\cellx".($width1)."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
					
					if($keyBorder == 0){	//ce je prva vrstica v tabeli
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[0])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
						//$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}elseif($keyBorder == ($numOfRows-1)){	//ce je zadnja vrstica v tabeli
						//$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 300).$this->TableBorder($PredefinedSeznamBorders[2])."\\cellx".( $width1 + $curWidth + 300 + $curWidth);
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[2])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}else{	//ce je vrstica, ki ni ne zadnja vrstica in ne prva v tabeli
						//$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 300).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 300 + $curWidth);
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						//$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell";
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}
            		

					// dodamo spacer (razen za zadnjo celico)
					if ( $spacer && $key < sizeOf($contentValue)-1)
					{
						$curWidth += $spacerWidth;
   						$tableHeader_width .= "\\cellx". ( $curWidth );
            			$tableHeader_title .= "\\pard\\intbl\\ql{}\\cell";

					}
					
					if($keyBorder != ($numOfRows-1)){	//ce ni zadnja vrstica v tabeli
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCells;
					}
					
/* 					if($keyBorder != ($numOfRows-1) && $keyBorder != 1){	//ce ni zadnja vrstica v tabeli
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCells;
					}elseif($keyBorder == 1){
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCellsArrow;
					} */
					
					$keyBorder++;

				}
                $resultString .= $tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish;
			}

			$resultString .= "}";
			$this->MyRTF .= $this->enkaEncode($resultString);
	}
	
	//public function TableFromArrayDragDropGrid($cellWidths, $content, $SeznamBorders = array(), $numOfRows, $prop = array())
	public function TableFromArrayDragDropGrid($cellWidths, $content, $numOfRows, $prop = array())
	{
		$border = ( ( $prop['border'] && is_array( $prop['border'] ) )? $prop['border'] : null);
		$headerBox = ( ( isset ( $prop['headerBox'] ) ) ? $prop['headerBox'] : 0 );
		$headerBorder = ( ( isset ( $prop['headerBorder'] ) ) ? $prop['headerBorder'] : array('top','bottom', 'left','right') );
		$spacer = ( ( isset ( $prop['spacer'] ) ) ? $prop['spacer'] : 1 );
		$spacerWidth = ( ( isset ( $prop['spacerWidth'] ) ) ? $prop['spacerWidth'] : 300 );

		$curWidth = 0;
		
		
		$width1 = 500;
		
		$PredefinedSeznamBorders[0] = array('top', 'left', 'right');
		$PredefinedSeznamBorders[1] = array('left', 'right');
		$PredefinedSeznamBorders[2] = array('right', 'left', 'bottom');
		$PredefinedSeznamBorders[3] = array('top', 'left', 'right', 'bottom');
		
		$keyBorder = 0;
		$resultString = "{\\par\\fs22"
		."\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";

            foreach ( $content as $contentKey => $contentValue )
            {
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = "";
				$tableHeader_title = "";				
				$tableHeader_finish = "\\pard\\intbl\\row";
				// $curWidth = 0
				$curWidth = 0;
				
				//$tableBlankCells = "\\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
				$numOfCells = 6;
				$tableBlankCells = "";
				$tableBlankCellsArrow = "";
				for($i = 0; $i<$numOfCells; $i++){
					$tableBlankCells .= "\\pard\\intbl\\cell ";					
/*  					if($i == 3){
						$tableBlankCellsArrow .="\\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell ";
					}else{
						$tableBlankCellsArrow .="\\pard\\intbl\\cell ";	
					} */
				}
				//$tableBlankCellsArrow = "\\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell ";
				
				$tableBlank_width = "";
				
				//foreach ( $contentValue as $key => $value )
				for($z = 0; $z < $numOfRows; $z++)
				{
					$curentBorder = "";
 					if ($contentKey == 0 && $headerBox)
						$curentBorder = $headerBorder;
					else
						$curentBorder = $SeznamBorders[$keyBorder];
					
					$curWidth += ( ( $cellWidths[$z] ) ? $cellWidths[$z] : "1000");
					

					$tableBlank_width = "\\cellx".($width1)."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
					
					if($keyBorder == 0){	//ce je prva vrstica v tabeli
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[0])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$content[$z]."}\\cell \\pard\\intbl\\cell \\pard\\intbl{".$this->ImageToString("arrow.png", "100")."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
						//$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}elseif($keyBorder == ($numOfRows-1)){	//ce je zadnja vrstica v tabeli
						//$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 300).$this->TableBorder($PredefinedSeznamBorders[2])."\\cellx".( $width1 + $curWidth + 300 + $curWidth);
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[2])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$content[$z]."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}else{	//ce je vrstica, ki ni ne zadnja vrstica in ne prva v tabeli
						//$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 300).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 300 + $curWidth);
						$tableHeader_width .= "\\cellx".($width1)." ".$this->TableBorder($PredefinedSeznamBorders[3])."\\cellx". ( $width1 + $curWidth )."\\cellx".( $width1 + $curWidth + 150)."\\cellx".( $width1 + $curWidth + 150 + 750)."\\cellx".( $width1 + $curWidth + 150 + 750 + 150).$this->TableBorder($PredefinedSeznamBorders[1])."\\cellx".( $width1 + $curWidth + 150 + 750 + 150 + $curWidth);
						//$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell \\pard\\intbl\\cell \\pard\\intbl{".$value."}\\cell";
						$tableHeader_title .= "\\pard\\intbl\\cell \\pard\\intbl{".$content[$z]."}\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell \\pard\\intbl\\cell";
					}
            		

					// dodamo spacer (razen za zadnjo celico)
					if ( $spacer && $z < sizeOf($content)-1)
					{
						$curWidth += $spacerWidth;
   						$tableHeader_width .= "\\cellx". ( $curWidth );
            			$tableHeader_title .= "\\pard\\intbl\\ql{}\\cell";

					}
					
					if($keyBorder != ($numOfRows-1)){	//ce ni zadnja vrstica v tabeli
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCells;
					}
					
/* 					if($keyBorder != ($numOfRows-1) && $keyBorder != 1){	//ce ni zadnja vrstica v tabeli
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCells;
					}elseif($keyBorder == 1){
						$tableHeader_title .= $tableHeader_finish.$tableHeader_base.$tableBlank_width.$tableBlankCellsArrow;
					} */
					
					$keyBorder++;

				}
                $resultString .= $tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish;
			}

			$resultString .= "}";
			$this->MyRTF .= $this->enkaEncode($resultString);
	}
	
	public function TableSNPodvprasanje($colHeaders, $rowHeaders, $prop = array())
	{
		$cols = sizeOf($colHeaders);
		$rows = sizeOf($rowHeaders)+1;
		$type = ( ( isset ( $prop['type'] ) ) ? $prop['type'] : 'box' ); //radio, povezave, def=box

		// kalkulacije širin
		$fullWidth = 9500;
		$spaceWidth = 300;
		$spacesWidth = round($spaceWidth * ($cols-1));
		$cellWidth = round(($fullWidth - $spacesWidth) / $cols);

		$resultString = "{\\par\\fs22"
		."\\trowd\\trhdr\\trgaph20\\trleft0\\trrh162";
			for ($i = 0; $i < $rows; $i++)
            {
				$tableHeader_base = "\\trowd\\trgaph12\\trleft0\\trrh262";
				$tableHeader_width = "";
				$tableHeader_title = "";

				$tableHeader_finish = "\\pard\\intbl\\row";

				$curWidth = 0;
				$index=0;
				for ($j = 0; $j < $cols; $j++)
				{
					$border = "";
					$value = "";
					$align = "\\ql"; // left
					if ($i == 0)
					{
						$value = $colHeaders[$j];
						if ( $j != 0 )
							$border = "bottom";
						$align = "\\qc";
					}
					else if ( $j == 0)
					{
						$value = $rowHeaders[$i-1];
						if ( $type == 'povezave')
							$border = "bottom";
					}
					else
					{
						// v odvisnosti od tipa prikažemo
						switch ( $type )
						{
							case 'radio': // radio button
								$value = $this->ImageToString( "radio.png", "15");
							break;
							case 'povezave': // radio button
								if ( $j > $i )
									$value =$this->ImageToString( "checkbox.png", "15");
//								else $value = "";
							break;

							default: // box
								$value =$this->ImageToString( "checkbox.png", "15");
							break;
}
//						$value = "v";
						$align="\\qc";
					}


            		$curWidth += $cellWidth;
            		$tableHeader_width .= $this->TableBorder($border)."\\cellx". ( $curWidth );
            		$tableHeader_title .= "\\pard\\intbl".$align."{".$value."}\\cell";

					// dodamo spacer
					if ($j < $cols-1)
					{
						$curWidth += $spaceWidth;
						$tableHeader_width .= "\\cellx". ( $curWidth ); // space
	            		$tableHeader_title .= "\\pard\\intbl\\ql{ }\\cell";
					}

            		$index++;
				}
                $resultString .= $tableHeader_base.$tableHeader_width.$tableHeader_title.$tableHeader_finish;
			}

			$resultString .= "}";
//			print_r($resultString);
			$this->MyRTF .= $this->enkaEncode($resultString);

	}

	/**
     * Insert radio/checkbox image (it took it from folder: $site_path.'admin/survey/img_0/')
     *
     * @arg1		string	(image filename)
	  * @arg2		int		(int 1-100)
	  * @arg3		keyword  (left|center|right|justify)
	  * @return		void
     */
	function ImageToString($image, $ratio, $align = 'left')
	{
		global $site_path;
		
		$file = @file_get_contents($site_path.'admin/survey/img_0/'.$image);

		if (empty($file)) {
			print_r("Error geting file:".$site_path.'admin/survey/img_0/'.$image);
			return NULL;
		}
		$result = $this->alignString;
		$result .= "{";
		$result .= "\\pict\\jpegblip\\picscalex". $ratio ."\\picscaley". $ratio ."\\bliptag132000428 ";
		$result .= trim(bin2hex($file));
		$result .= "\n}\n";
		return $result;
	}

	
	// Pretvori html v rtf string
	function HTMLtoRTF($string) {
	
		/*if(preg_match("/<UL>(.*?)<\/UL>/mi", $string) || preg_match("/<ul>(.*?)<\/ul>/mi", $string)
			|| preg_match("/<OL>(.*?)<\/OL>/mi", $string) || preg_match("/<ol>(.*?)<\/ol>/mi", $string)) {*/
			$string = str_replace("<UL>", "", $string);
			$string = str_replace("<ul>", "", $string);
			$string = str_replace("</UL>", "", $string);
			$string = str_replace("</ul>", "", $string);
			$string = str_replace("<OL>", "", $string);
			$string = str_replace("<ol>", "", $string);
			$string = str_replace("</OL>", "", $string);
			$string = str_replace("</ol>", "", $string);
			$string = preg_replace("/<LI>(.*?)<\/LI>/mi", "\\f3\\'B7\\tab\\f{$this->dfl_FontID} \\1\\par", $string);
		/*}*/
		
		$string = preg_replace("/<P>(.*?)<\/P>/mi", "\\1\\par ", $string);
		$string = preg_replace("/<STRONG>(.*?)<\/STRONG>/mi", "\\b \\1\\b0 ", $string);
		$string = preg_replace("/<B>(.*?)<\/B>/mi", "\\b \\1\\b0 ", $string);
		$string = preg_replace("/<EM>(.*?)<\/EM>/mi", "\\i \\1\\i0 ", $string);
		$string = preg_replace("/<U>(.*?)<\/U>/mi", "\\ul \\1\\ul0 ", $string);
		$string = preg_replace("/<STRIKE>(.*?)<\/STRIKE>/mi", "\\strike \\1\\strike0 ", $string);
		$string = preg_replace("/<SUB>(.*?)<\/SUB>/mi", "{\\sub \\1}", $string);
		$string = preg_replace("/<SUP>(.*?)<\/SUP>/mi", "{\\super \\1}", $string);
				
		$string = preg_replace("/<H1>(.*?)<\/H1>/mi", "\\fs48\\b \\1\\b0\\fs{$this->_font_size($this->dfl_FontSize)}\\par ", $string);
		$string = preg_replace("/<H2>(.*?)<\/H2>/mi", "\\fs36\\b \\1\\b0\\fs{$this->_font_size($this->dfl_FontSize)}\\par ", $string);
		$string = preg_replace("/<H3>(.*?)<\/H3>/mi", "\\fs27\\b \\1\\b0\\fs{$this->_font_size($this->dfl_FontSize)}\\par ", $string);
			
		$string = preg_replace("/<HR(.*?)>/i", "\\brdrb\\brdrs\\brdrw30\\brsp20 \\pard\\par ", $string);
		$string = str_replace("<BR>", "\\par ", $string);
		$string = str_replace("<TAB>", "\\tab ", $string);
		
		//$string = $this->nl2par($string);
		
		// Porezemo zadnji line break zaradi
		$string = substr($string, 0, -2);
		
		return $string;
	}
	
	// Convert newlines into \par
	function nl2par($text) {
		$text = str_replace("\n", "\\par ", $text);
		
		return $text;
	}
	
	public function prepareHeatmapImage($data4Coords, $backgroundImg, $latInMm, $lngInMm, $ImgWidth, $ImgHeight, $heatmap_click_size, $heatmap_click_color, $heatmap_click_shape, $spr_id, $bgImageType, $uploadDir){
			//global $site_path;
			//define('UPLOAD_DIR', $site_path.'main/survey/uploads/');
			#izris tock na sliko######################################################################################
			
			switch ($bgImageType){
				case 'jpg':
					$backgroundImg = imagecreatefromjpeg($backgroundImg);	//nalozena slika ozadja
				break;
				case 'png':
					$backgroundImg = imagecreatefrompng($backgroundImg);	//nalozena slika ozadja
				break;
				case 'gif':
					$backgroundImg = imagecreatefromgif($backgroundImg);	//nalozena slika ozadja
				break;
			}								
			
			$orig_width = imagesx($backgroundImg);
			$orig_height = imagesy($backgroundImg);
			
			$backgroundImgResized = imagecreatetruecolor($ImgWidth, $ImgHeight);	//ustvari sliko ustrezne velikosti
			//imagecopyresized($backgroundImgResized, $backgroundImg, 0, 0, 0, 0, $ImgWidth, $ImgHeight, $orig_width, $orig_height);	//kopiraj original (vecjo) sliko v sliko ustrezne velikosti, ki jo je izbral uporabnik
			imagecopyresampled($backgroundImgResized, $backgroundImg, 0, 0, 0, 0, $ImgWidth, $ImgHeight, $orig_width, $orig_height);	//kopiraj original (vecjo) sliko v sliko ustrezne velikosti, ki jo je izbral uporabnik
			
			// barva oblike klika - pride v taki obliki #f26970
			$barva = substr($heatmap_click_color, 1);	//f26970
			$rHex = '0x'.str_replace(substr($barva, 2),"",$barva);	//f2
			//error_log("rHex: $rHex");
			$barvatmp = substr($barva, 2);	//6970	
			$barvatmpG = str_replace(substr($barvatmp, 2),"",$barvatmp); //69
			$gHex = '0x'.$barvatmpG;
			//error_log("gHex: $gHex");
			$barvatmpB = substr($barvatmp, 2);	//70
			$bHex = '0x'.$barvatmpB;
			//error_log("bHex: $bHex");
			$barvaOblike = imagecolorallocate($backgroundImgResized, $rHex, $gHex, $bHex);				
			// barva oblike klika - konec				
			
			// narisi obliko klika
			foreach($data4Coords as $row){	//za vsako klikano tocko
				if($heatmap_click_shape == 1){	//krog
					imagefilledarc($backgroundImgResized, $row['lat'], $row['lng'], $heatmap_click_size*2, $heatmap_click_size*2, 0, 360, $barvaOblike, IMG_ARC_PIE);
				}elseif($heatmap_click_shape == 2){	//kvadrat
					$x1 = $row['lat'] - $heatmap_click_size/2;
					$y1 = $row['lng'] - $heatmap_click_size/2;
					$x2 = $row['lat'] + $heatmap_click_size/2;
					$y2 = $row['lng'] + $heatmap_click_size/2;
					imagefilledrectangle($backgroundImgResized, $x1, $y1, $x2, $y2, $barvaOblike);
				}
			}
			// narisi obliko klika - konec
			
			//$file = UPLOAD_DIR . $heatmapId . '.png';
			//$file = UPLOAD_DIR . 'test_'.$spr_id.'.png';
			$file = $uploadDir . 'results_'.$spr_id.'.png';
			imagepng($backgroundImgResized, $file, 6, NULL);
			imagedestroy($backgroundImgResized);
			
			return $file;
			
			#izris tock na sliko######################################################################################konec
	}
	
	
}
?>