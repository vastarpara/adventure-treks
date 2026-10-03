<?php
/**
 * Bookings PDF (A4 landscape) built on the bundled FPDF library.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Includes
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\FPDF' ) ) {
	require_once ADVENTURE_TREKS_PATH . 'includes/vendor/fpdf/fpdf.php';
}

/**
 * BookingsPdf class.
 */
class BookingsPdf extends \FPDF {

	/**
	 * Report heading lines (title and filter summary).
	 *
	 * @var string[]
	 */
	private $heading = array();

	/**
	 * Column definitions: label, width (mm), alignment.
	 *
	 * @var array
	 */
	private $columns = array();

	/**
	 * Constructor: A4 landscape, millimetres.
	 *
	 * @param string[] $heading Heading lines.
	 * @param array    $columns Columns as array of array( label, width, align ).
	 */
	public function __construct( $heading, $columns ) {
		parent::__construct( 'L', 'mm', 'A4' );
		$this->heading = $heading;
		$this->columns = $columns;
		$this->SetMargins( 10, 10, 10 );
		$this->SetAutoPageBreak( true, 14 );
		$this->AliasNbPages();
	}

	/**
	 * Convert UTF-8 text to the PDF's Latin-1 charset (the built-in fonts have no Unicode support).
	 *
	 * @param string $text UTF-8 text.
	 * @return string
	 */
	public static function latin( $text ) {
		$text = str_replace( array( '₹', '€' ), array( 'Rs.', 'EUR' ), (string) $text );
		$out  = iconv( 'UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text );
		return false === $out ? '' : $out;
	}

	/**
	 * Cut text so it fits a cell, adding an ellipsis.
	 *
	 * @param string $text  Latin-1 text.
	 * @param float  $width Cell width in mm.
	 * @return string
	 */
	private function fit( $text, $width ) {
		$max = $width - 2;
		if ( $this->GetStringWidth( $text ) <= $max ) {
			return $text;
		}
		while ( '' !== $text && $this->GetStringWidth( $text . '...' ) > $max ) {
			$text = substr( $text, 0, -1 );
		}
		return $text . '...';
	}

	/**
	 * Page header: heading lines and the table header row.
	 *
	 * @return void
	 */
	public function Header() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->SetFont( 'Helvetica', 'B', 15 );
		$this->Cell( 0, 8, self::latin( $this->heading[0] ), 0, 1 );
		$this->SetFont( 'Helvetica', '', 9 );
		$this->SetTextColor( 90, 90, 90 );
		foreach ( array_slice( $this->heading, 1 ) as $line ) {
			$this->Cell( 0, 5, self::latin( $line ), 0, 1 );
		}
		$this->SetTextColor( 0, 0, 0 );
		$this->Ln( 2 );

		$this->SetFont( 'Helvetica', 'B', 9 );
		$this->SetFillColor( 51, 65, 85 );
		$this->SetTextColor( 255, 255, 255 );
		$this->SetDrawColor( 200, 205, 215 );
		foreach ( $this->columns as $col ) {
			$this->Cell( $col[1], 8, self::latin( $col[0] ), 1, 0, $col[2], true );
		}
		$this->Ln();
		$this->SetTextColor( 0, 0, 0 );
	}

	/**
	 * Page footer: generation time and page number.
	 *
	 * @return void
	 */
	public function Footer() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->SetY( -10 );
		$this->SetFont( 'Helvetica', '', 8 );
		$this->SetTextColor( 120, 120, 120 );
		$this->Cell( 0, 5, self::latin( 'Generated ' . wp_date( 'd M Y, h:i A' ) ), 0, 0, 'L' );
		$this->Cell( 0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R' );
	}

	/**
	 * Add one table row.
	 *
	 * @param string[] $cells UTF-8 cell values, in column order.
	 * @param bool     $zebra Shade the row.
	 * @param bool     $bold  Bold row (used for totals).
	 * @return void
	 */
	public function row( $cells, $zebra = false, $bold = false ) {
		$this->SetFont( 'Helvetica', $bold ? 'B' : '', 9 );
		$this->SetFillColor( 241, 244, 249 );
		$this->SetDrawColor( 220, 224, 232 );
		foreach ( $this->columns as $i => $col ) {
			$value = self::latin( isset( $cells[ $i ] ) ? $cells[ $i ] : '' );
			$this->Cell( $col[1], 7, $this->fit( $value, $col[1] ), 1, 0, $col[2], $zebra || $bold );
		}
		$this->Ln();
	}
}
