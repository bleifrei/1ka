<?php
/*
 * Created on 24.2.2009
 *
 * ahthor: Gorazd Veselic
 *
 * Za potrebe custom header in footer
 */

require_once('class.tcpdf.php');
// Extend the TCPDF class to create custom Header and Footer
	class enka_TCPDF extends TCPDF
	{
		/**
		 * @var Ce je true izpisemo header na prvi strani
		 * @access protected
		 */
		protected $print_header_first_page = true;

		/**
		 * @var @var Ce je true izpisemo footer na prvi strani
		 * @access protected
		 */
		protected $print_footer_first_page = true;
		
		/**
		 * @var @var datum, ki ga izpisemo v footerju
		 * @access protected
		 */
		protected $footer_date = null;

		/**
	 	 * Nastavimo ali printamo header na prvi strani.
		 * @param boolean $value set to true to print the page footer (default), false otherwise.
		 */
		public function setPrintHeaderFirstPage($val=true) {
			$this->print_header_first_page = $val;
		}

		/**
	 	 * Nastavimo ali printamo header na prvi strani.
		 * @param boolean $value set to true to print the page footer (default), false otherwise.
		 */
		public function setPrintFooterFirstPage($val=true) {
			$this->print_footer_first_page = $val;
		}

		/**
	 	 * Vrne ali printamo header na prvi strani
		 * @return boolean
		 */
		public function doPrint_header_first_page() {
			 return $this->print_header_first_page;
		}

		/**
	 	 * Vrne ali printamo footer na prvi strani
		 * @return boolean
		 */
		public function doPrint_footer_first_page() {
			return $this->print_footer_first_page;
		}
		
		/**
	 	 * Nastavimo datum v footerju
		 */
		public function setFooterDate($date=null) {
			$this->footer_date = $date;
		}

		/** OVERRIDE
	 	 * This method is used to render the page header.
	 	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
		 */

		public function Header() {
			//ali izpise header
			$doIzpis = true;
			if ( !$this->doPrint_header_first_page())
				$doIzpis = ( $this->PageNo() != 1);

			if ($doIzpis)
			{
				$ormargins = $this->getOriginalMargins();
				$headerfont = $this->getHeaderFont();
				$headerdata = $this->getHeaderData();

				$imgy = $this->GetY();
				$cell_height = round(($this->getCellHeightRatio() * $headerfont[2]) / $this->getScaleFactor(), 2);
				// set starting margin for text data cell
				if ($this->getRTL()) {
					$header_x = $ormargins['right'] + ($headerdata['logo_width'] * 1.1);
				} else {
					$header_x = $ormargins['left'] + ($headerdata['logo_width'] * 1.1);
				}
				$this->SetTextColor(0, 0, 0);
				// header title
//				$this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
				$this->SetX($header_x);
				$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
				
				$extend_width = ($this->CurOrientation == 'L') ? 1.4 : 1;
				
				// Posebej header za gorenje
				if(Common::checkModule('gorenje')){
					// header string
					$this->MultiCell(90*$extend_width, $cell_height, /*$headerdata['title']*/'', 0, 'L', 0, 0, 0 ,0, true);
					$this->MultiCell(90*$extend_width, $cell_height, $headerdata['string'],  0, 'R', 0, 1, 0 ,0, true);

					// print an ending header line
					$this->SetLineStyle(array('width' => 0.85 / $this->getScaleFactor(), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
					$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));
					if ($this->getRTL()) {
						$this->SetX($ormargins['right']);
					} else {
						$this->SetX($ormargins['left']);
					}
					$this->Cell(0, 0, "".'', 'T', 0, 'C');
					
					$image_file = '../survey/modules/mod_gorenje/img_new/logo.png';
					$this->Image($image_file, 15, 8, 25, '', 'PNG', '', 'T', true, 300, '', false, false, 0, false, false, false);	
				}
				else{
                    // header string
                    // Avk nima www.1ka.si texta v glavi
                    global $mysql_database_name;
                    if($mysql_database_name == "vprasalnikiavksi")
                        $this->MultiCell(90*$extend_width, $cell_height, '', 0, 'L', 0, 0, 0 ,0, true);
                    else
                        $this->MultiCell(90*$extend_width, $cell_height, $headerdata['title'], 0, 'L', 0, 0, 0 ,0, true);
                    
					$this->MultiCell(90*$extend_width, $cell_height, $headerdata['string'],  0, 'R', 0, 1, 0 ,0, true);

					// print an ending header line
					$this->SetLineStyle(array('width' => 0.85 / $this->getScaleFactor(), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
					$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));
					if ($this->getRTL()) {
						$this->SetX($ormargins['right']);
					} else {
						$this->SetX($ormargins['left']);
					}
					$this->Cell(0, 0, "".'', 'T', 0, 'C');
				}
			}
		}

		/** OVERRIDE
	 	 * This method is used to render the page footer.
	 	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
		 */
		public function Footer() {

			$cur_y = $this->GetY();
			$ormargins = $this->getOriginalMargins();
			$this->SetTextColor(0, 0, 0);
			//set style for cell border
			$line_width = 0.85 / $this->getScaleFactor();
			$this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

			//ali izpise footer
			$doIzpis = true;
			if ( !$this->doPrint_footer_first_page())
				$doIzpis = ( $this->PageNo() != 1);

			if (empty($this->pagegroups))
			{
				$curr = $this->getAliasNumPage();
				$all = $this->getAliasNbPages();
			}
			else
			{
				$curr = $this->getPageNumGroupAlias();
				$all = $this->getPageGroupAlias();
			}

			$pagenumtxt = $this->l['w_page'].' '.$curr.' / '.$all;

			$this->SetY($cur_y);

			//print date
			if($this->footer_date){
				$this->SetX($ormargins['left']);
				$this->Cell(0, 0, date('d.m.Y', strtotime($this->footer_date)), 'T', 0, 'L');
			}				
			
			//Print page number
			if ($doIzpis)
			if ($this->getRTL()) {
				$this->SetX($ormargins['right']);
				$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
			} else {
				$this->SetX($ormargins['left']);
				$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'R');
			}
		}
		/** OVERRIDE: z novo erzijo ne deluje več javascript form elementi
		 * ker Adobe krešira
		 *
		 */
		public function RadioButton($name, $w, $prop=array())
		{
			$prop['strokeColor'] = isset($prop['strokeColor'] ) ? $prop['strokeColor'] : 'black';
			$prop['full'] = (isset( $prop['full'] ) && $prop['full'] == true) ? true : false;

			$oldStyle = $this->GetLineStyle;
			$this->SetLineStyle(array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$cy = $this->getY();
			$cx = $this->getX();
			$lch = $this->getLastH();
			//	$this->_addfield('radiobutton', $name, $this->x, $this->y, $w, $w, $prop);

			//  void   Circle (float $x0, float $y0, float $r,  $astart,
			// [ $afinish = 360], [string $style = ""],
			// [array $line_style = array()], [array $fill_color = array()], [integer $nc = 8], float $astart:, float $afinish:)
			// narišemo krogec
			$this->Circle($cx, $cy+($this->getLastH()/2), $w/2 );

			// zapolnimo user vrednost
			$this->SetFillColor(0, 0, 0);
			( $prop['full'] ) ? $this->Circle($cx, $cy+($this->getLastH()/2), $w/3.5,0,360,'F') : null;

			$this->SetLineStyle = $oldStyle;
		}
		/** OVERRIDE: z novo verzijo ne deluje več javascript form elementi
		 * ker Adobe krešira, zato sami narišemo škatlco
		 *
		 */
		public function CheckBox($name, $w, $prop=array())
		{
//			$prop['value'] = ($checked ? 'Yes' : 'Off');
			$prop['strokeColor'] = isset($prop['strokeColor'] ) ? $prop['strokeColor'] : 'black';
			$prop['full'] = (isset( $prop['full'] ) && $prop['full'] == true) ? true : false;

			$oldStyle = $this->GetLineStyle;
			$this->SetLineStyle(array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$cy = $this->getY();
			$cx = $this->getX();
			$lch = $this->getLastH();
			// narišemo kvadratek
			$styleBox = array('L' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
                'T' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
                'R' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
                'B' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)));

			$this->Rect($cx-($w/2), $cy, $w, $w, 'DF', $styleBox, array(255, 255, 255));

			// zapolnimo user vrednost
			$this->SetFillColor(0, 0, 0);
			$prop['full'] ? $this->Rect($cx-($w/2)+$w/4, $cy+$w/4, $w/2, $w/2, 'DF', $styleBox, array(0, 0, 0)) : null;
			$this->SetLineStyle = $oldStyle;
		}

		/** Izrišemo okvir za text
		 *
		 */
		public function TextBox($w, $h, $prop=array())
		{
			if (!isset($prop['strokeColor'])) {
				$prop['strokeColor'] = 'black';
			}
			$cy = $this->getY();
			$cx = $this->getX();
			$lch = $this->getLastH();
			// narišemo kvadratek
			$styleBoxDotted = array('L' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'T' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'R' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'B' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)));

			$cx = ( (PDF_MARGIN_LEFT) ? PDF_MARGIN_LEFT : 15 );
			$this->Rect($cx, $cy, $w, $h, 'DF', $styleBoxDotted, array(255, 255, 255));
		}
		/** Izrišemo okvir za text - kjer jih je vec (multi...)
		 *
		 */
		public function TextBoxes($w, $h, $prop=array())
		{
			if (!isset($prop['strokeColor'])) {
				$prop['strokeColor'] = 'black';
			}
			$cy = $this->getY();
			$cx = $this->getX();
			$lch = $this->getLastH();
			// narišemo kvadratek
			$styleBoxDotted = array('L' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'T' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'R' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)),
                'B' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)));

			//$cx = ( (PDF_MARGIN_LEFT) ? PDF_MARGIN_LEFT : 15 );
			$this->Rect($cx, $cy, $w, $h, 'DF', $styleBoxDotted, array(255, 255, 255));
		}
		public function TabledTextBox($cols, $rows, $prop = array() )
		{
			$totalWidth = ( ( $prop['totalWidth'] ) ? $prop['totalWidth'] : 180 );
			$spaceWidth = ( ( $prop['spaceWidth'] ) ? $prop['spaceWidth'] : 4 );
			$rowHeaders = $prop['rowHeaders'];
			$type = ( ( isset ( $prop['type'] ) ) ? $prop['type'] : 'box' ); // def. rišemo box
			$drawRowHeader = ( ( isset( $prop['rowHeaders'] ) && is_array($prop['rowHeaders'])) ? 1 : 0 );
			$cols += $drawRowHeader;
			if ($type == povezave)
			{
				$cols ++;
				$rows ++;
				$headerBox = 1;
				$drawRowHeader = 1;
			}

			$spaces = ($cols-1)*2*$spaceWidth;
			$cellWidth = ( ( $prop['cellWidth'] ) ? $prop['cellWidth'] : (($totalWidth-$spaces) / $cols) );
			$lineHeight = ( ( $prop['lineHeight'] ) ? $prop['lineHeight'] : 5 );
			$lineStyle = ( ( isset( $prop['lineStyle'] ) && is_array($prop['lineStyle']) ) ? $prop['lineStyle'] : array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(128, 128, 128)) );
			$headerStyle = ( ( isset( $prop['headerStyle'] ) && is_array($prop['headerStyle']) ) ? $prop['headerStyle'] : array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)) );
			$headerBox = ( ( isset ( $prop['headerBox'] ) ) ? $prop['headerBox'] : 0 );
			$vLine = ( ( isset( $prop['vLine'] ) ) ? $prop['vLine'] : 1 ); // po def. rišemo vertikalne črte
			$hLine = ( ( isset( $prop['hLine'] ) && $headerBox ) ? $prop['hLine'] : 0 ); // če ni header boxa ne rišemo črte
			$chkWidth = 3;

			$startCy = $this->getY();
			$cx = $this->getX();
			$lineX = Array();
			for ($i = 0; $i < $rows; $i++)
			{
				// nastavimo stil
				$this->SetLineStyle((($headerBox == 1 && $i == 0)?$headerStyle:$lineStyle));

				$cy = $this->getY();
				// če rišemo rowHeaders cols povečamo za 1

				for ($j = 0; $j < $cols; $j++)
				{
					$cellX = $cx+$j*$cellWidth+ ($j*2*$spaceWidth);
					if ($i == 0 && $j != 0)
						$lineX[] = $cellX-$spaceWidth;

					if ( $drawRowHeader  && $j == 0 )
					{ // v 0,0 ne rišemo ničesar če imamo drawRowHeader
						// v x>0,0 izpišemo text rowHeader
						if ( $i > 0 )
						{
							if ( $type == 'radio' )
								$this->Cell(0, 0, $rowHeaders[$i-1], '', 0, 'L');
							else if ( $type == 'povezave')
							{
								$this->SetLineStyle($headerStyle);
								$this->Rect($cellX, $cy, $cellWidth, $lineHeight, '', "", array(255, 255, 255));
							}
						}

					}
					//če je header rišemo header
					else if ( $headerBox == 1 && $i == 0 )
						$this->Rect($cellX, $cy, $cellWidth, $lineHeight, '', "", array(255, 255, 255));
					else
					{
						// v odvisnosti od tipa narišemo box(tabelo), radio ali check
						switch ( $type )
						{
							case 'radio':
								$this->SetLineStyle($headerStyle);
								$lch = $this->getLastH();
								// narišemo krogec
								$this->Circle($cellX+($cellWidth/2), $cy+($this->getLastH()/2), 1.5);
							break;
							case 'povezave':
								$this->SetLineStyle($headerStyle);
								$lch = $this->getLastH();
								// če je potrebno narišemo checkbox
								if ( $j > $i )
								{
									$styleBox = array('L' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
						                'T' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
						                'R' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)),
						                'B' => array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0, 0, 0)));
									$this->Rect($cellX+($cellWidth-$chkWidth)/2, $cy+$chkWidth/4, 3, 3, 'DF', $styleBox, array(255, 255, 255));
								}

							break;

							default:
								$this->Rect($cellX, $cy, $cellWidth, $lineHeight, '', "", array(255, 255, 255));
							break;
						}
					}
				}
				// dodamo razmak
				if ($i != $rows-1)
					$this->setY($cy+$lineHeight *1.3);
			}

			$this->setY($cy+$lineHeight);

			if ( $vLine )
			foreach ( $lineX as $lx )
			{
       			$this->Line($lx, $startCy , $lx, $this->getY() , array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			}
			// po svoje je hLine brezveze, ker je nepregledno
			if ( $hLine )
				$this->Line($cx, $startCy+$lineHeight*1.15 , $cellWidth *$cols+$spaces+$cx, $startCy+$lineHeight*1.15 , array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

		}

		public function drawLine()
		{
			$this->currentStyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(128, 0, 0));
			$cy = $this->getY();
			$this->Line(15, $cy , 15, $cy , $this->currentStyle);
		}
		
		public function prepareHeatmapImage($data4Coords, $backgroundImg, $latInMm, $lngInMm, $ImgWidth, $ImgHeight, $heatmap_click_size, $heatmap_click_color, $heatmap_click_shape, $spr_id, $bgImageType, $uploadDir){
				//global $site_path;
				//define('UPLOAD_DIR', $site_path.'main/survey/uploads/');
				//define('UPLOAD_DIR', $site_path.'admin/exportclases/temp/');
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
