<?php
/**
 *	dk PDF extends FPDF
 *	dkPDF.php
 *	Created on 18.12.2013.
 *
 *	@author Daniel Rufus Kaldheim <daniel@kaldheim.org>
 *	@copyright 2010 - 2013 Crudus Media
 *	@version 1.0.0
 *
 */
require('FPDF/src/fpdf/FPDF.php');


class dkPDF extends fpdf\FPDF {

	var $custom_fonts = array();
	var $produce;
	var $header_content = array();
	var $widths;
	var $aligns;
	var $row_header = array();

	function __construct($config = array('orientation' => 'P', 'unit' => 'mm', 'format' => 'A4')){
		parent::__construct($config['orientation'],$config['unit'],$config['format']);

		$this->initialize($config);
		$this->set_custom_fonts();
	}

	/**
	 * Repeatable header
	 */
	function Header() {
	}

	/**
	 * Repatable footer
	 */
	function Footer() {
	}

	/**
	 * Initialize the class
	 * @param  array  $config
	 */
	public function initialize($config = array()) {
		foreach ($config as $key => $val) {
			if (isset($this->$key)) {
				$this->$key = $val;
			}
		}
	}


	/**
	 *
	 * Increase the abscissa of the current position.
	 *
	 * @param int $x
	 *
	 * @return void
	 *
	 */
	public function moveX($x) {
		$posX = $this->GetX();
		$posX += $x;
		$this->SetX($posX);
	}

	/**
	 *
	 * Increase the ordinate of the current position.
	 *
	 * @param int $y
	 *
	 * @return void
	 *
	 */
	public function moveY($y) {
		$posX = $this->GetX();
		$posY = $this->GetY();
		$posY += $y;
		$this->SetXY($posX, $posY);
	}

	public function Circle($x, $y, $r, $style='') {
		$this->Ellipse($x, $y, $r, $r, $style);
	}

	public function Ellipse($x, $y, $rx, $ry, $style='D') {
		if ($style == 'F'){
			$op = 'f';
		}
		elseif ($style == 'FD' or $style == 'DF') {
			$op = 'B';
		}
		else {
			$op = 'S';
		}
		$lx = 4/3 * (M_SQRT2 - 1) * $rx;
		$ly = 4/3 * (M_SQRT2 - 1) * $ry;
		$k = $this->k;
		$h = $this->h;
		$this->_out(sprintf('%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c',
			($x+$rx)*$k, ($h-$y)*$k,
			($x+$rx)*$k, ($h-($y-$ly))*$k,
			($x+$lx)*$k, ($h-($y-$ry))*$k,
			$x*$k, ($h-($y-$ry))*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
			($x-$lx)*$k, ($h-($y-$ry))*$k,
			($x-$rx)*$k, ($h-($y-$ly))*$k,
			($x-$rx)*$k, ($h-$y)*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
			($x-$rx)*$k, ($h-($y+$ly))*$k,
			($x-$lx)*$k, ($h-($y+$ry))*$k,
			$x*$k, ($h-($y+$ry))*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c %s',
			($x+$lx)*$k, ($h-($y+$ry))*$k,
			($x+$rx)*$k, ($h-($y+$ly))*$k,
			($x+$rx)*$k, ($h-$y)*$k,
			$op));
	}

	/*******************
	 *                 *
	 *      Table      *
	 *                 *
	 *******************/

	function SetWidths($w) {
		//Set the array of column widths
		$this->widths=$w;
	}

	function SetAligns($a) {
		//Set the array of column alignments
		$this->aligns=$a;
	}

	function RowHeader($data, $border = 0, $fill = false, $border_top = 0, $border_bottom = 0, $arguments = array()) {

		if ($data === FALSE) {
			$data = $this->row_header['data'];
			$border = $this->row_header['border'];
			$fill = $this->row_header['fill'];
			$border_top = $this->row_header['border_top'];
			$border_bottom = $this->row_header['border_bottom'];
			$arguments = $this->row_header['arguments'];
		}
		else {
			$this->row_header = array('data' => $data, 'border' => $border, 'fill' => $fill, 'border_top' => $border_top, 'border_bottom' => $border_bottom, 'arguments' => $arguments);
		}

		if (isset($arguments['textcolor'])) {
			if (is_array($arguments['textcolor'])) {
				$this->SetTextColor($arguments['textcolor']['r'], $arguments['textcolor']['g'], $arguments['textcolor']['b']);
			}
			else {
				$this->SetTextColor($arguments['textcolor']);
			}
		}

		if (isset($arguments['font'])) {
			$this->SetFont($arguments['font']['family'], ((isset($arguments['font']['style']) ? $arguments['font']['style'] : '')), ((isset($arguments['font']['size']) ? $arguments['font']['size'] : 0)));
		}

		if (isset($arguments['fillcolor'])) {
			if (is_array($arguments['fillcolor'])) {
				$this->SetFillColor($arguments['fillcolor']['r'], $arguments['fillcolor']['g'], $arguments['fillcolor']['b']);
			}
			else {
				$this->SetFillColor($arguments['fillcolor']);
			}
		}

		if (isset($arguments['drawcolor'])) {
			if (is_array($arguments['drawcolor'])) {
				$this->SetDrawColor($arguments['drawcolor']['r'], $arguments['drawcolor']['g'], $arguments['drawcolor']['b']);
			}
			else {
				$this->SetDrawColor($arguments['drawcolor']);
			}
		}
		$this->Row($data, $border, $fill, $border_top, $border_bottom);
	}

	function Row($data, $border = 0, $fill = false, $border_top = 0, $border_bottom = 0) {
		//Calculate the height of the row
		$nb = 0;
		for($i = 0; $i < count($data); $i++)
			$nb = max( $nb, $this->NbLines( $this->widths[$i], $data[$i]) );
		$h = 5 * $nb;
		//Issue a page break first if needed
		if ($this->CheckPageBreak($h)) {
			$this->AddPage($this->CurOrientation);
			if ($this->row_header) {
				$this->RowHeader(FALSE);
			}
		}

		if ($border_top) {
			$x = $this->GetX();
			$y = $this->GetY();
			$w = array_sum($this->widths);
			$this->Line(($x + 0.1), $y, (($x + $w) - 0.1), $y);
		}
		//Draw the cells of the row
		for($i = 0; $i < count($data); $i++) {
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();

			if (($border && (!$border_top && !$border_bottom)) or $fill) {
				//Draw the border
				$this->Rect($x, $y, $w, $h, (($border) ? 'D' : '').(($fill) ? 'F' : ''));
			}

			//Print the text
			$this->MultiCell($w, 5, $data[$i], 0, $a);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}

		//Go to the next line
		$this->Ln($h);

		if ($border_bottom) {
			$x = $this->GetX();
			$y = $this->GetY();
			$w = array_sum($this->widths);
			$this->Line(($x + 0.1), $y, (($x + $w) - 0.1), $y);
		}
	}

	function CheckPageBreak($h) {
		//If the height h would cause an overflow, add a new page immediately
		return $this->GetY() + $h > $this->PageBreakTrigger;
	}

	function NbLines($w,$txt) {
		//Computes the number of lines a MultiCell of width w will take
		$cw = &$this->CurrentFont['cw'];
		if ($w == 0)
			$w = $this->w-$this->rMargin-$this->x;
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
		$s = str_replace("\r",'',$txt);
		$nb = strlen($s);
		if ($nb > 0 and $s[$nb-1] == "\n")
			$nb--;
		$sep =- 1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ($i < $nb) {
			$c = $s[$i];
			if ($c == "\n") {
				$i++;
				$sep =-1;
				$j = $i;
				$l = 0;
				$nl++;
				continue;
			}
			if ($c == ' ')
				$sep = $i;
			$l += $cw[$c];
			if ($l > $wmax) {
				if ($sep ==-1) {
					if ($i == $j) {
						$i++;
					}
				}
				else {
					$i = $sep+1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
			}
			else {
				$i++;
			}
		}
		return $nl;
	}


	/*******************
	 *                 *
	 *  Helper files   *
	 *                 *
	 *******************/

	public function px2unit($px) {
		return ($px / $this->k);
	}


	public function hex2rgb($hex) {
		$hex = str_replace("#", "", $hex);
		if (strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		}
		else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		return array($r, $g, $b);
	}

	/*****************************
	 *                           *
	 *  Overwrites parent class  *
	 *                           *
	 *****************************/


	function SetDrawColor($r, $g=null, $b=null) {
		if (strlen($r) > 3) {
			$rgb = $this->hex2rgb($r);
			$r = $rgb[0];
			$g = $rgb[1];
			$b = $rgb[2];
		}
		parent::SetDrawColor($r, $g, $b);
	}

	function SetFillColor($r, $g=null, $b=null) {
		if (strlen($r) > 3) {
			$rgb = $this->hex2rgb($r);
			$r = $rgb[0];
			$g = $rgb[1];
			$b = $rgb[2];
		}
		parent::SetFillColor($r, $g, $b);
	}

	function SetTextColor($r, $g=null, $b=null) {
		if (strlen($r) > 3) {
			$rgb = $this->hex2rgb($r);
			$r = $rgb[0];
			$g = $rgb[1];
			$b = $rgb[2];
		}
		parent::SetTextColor($r, $g, $b);
	}


	function _putinfo() {
		$this->_out('/Producer '.$this->_textstring($this->producer));
		if(!empty($this->title))
			$this->_out('/Title '.$this->_textstring($this->title));
		if(!empty($this->subject))
			$this->_out('/Subject '.$this->_textstring($this->subject));
		if(!empty($this->author))
			$this->_out('/Author '.$this->_textstring($this->author));
		if(!empty($this->keywords))
			$this->_out('/Keywords '.$this->_textstring($this->keywords));
		if(!empty($this->creator))
			$this->_out('/Creator '.$this->_textstring($this->creator));
		$this->_out('/CreationDate '.$this->_textstring('D:'.@date('YmdHis')));
	}

	function SetProducer($producer, $isUTF8 = false) {
		// Producer of document
		if($isUTF8)
			$title = $this->_UTF8toUTF16($producer);
		$this->producer = $producer;
	}


	/**************
	 *            *
	 *  Privates  *
	 *            *
	 **************/

	private function set_custom_fonts() {
		foreach ($this->custom_fonts as $font) {
			$this->AddFont($font['family'],$font['style'], $font['file']);
		}
	}
}

/* End of file dkPDF.php */
/* Location: ./dkPDF.php */

?>
