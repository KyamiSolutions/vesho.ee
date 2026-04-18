<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Minimal pure-PHP PDF generator for Vesho CRM invoices.
 * No external dependencies — generates valid PDF 1.4 binary.
 */
class Vesho_PDF {

    private $objects   = [];
    private $n         = 0;
    private $pages     = [];
    private $cur_page  = null;
    private $fonts     = [];
    private $cur_font  = 'F1';
    private $cur_size  = 11;
    private $fill      = '0 0 0';   // RGB stroke/fill (0-1)
    private $line_w    = 0.5;

    // A4 in points (1 pt = 1/72 inch)
    const W = 595.28;
    const H = 841.89;
    const M = 40;   // margin

    private $y;       // current draw Y (top-down, we flip internally)

    public function __construct() {
        $this->y = self::M;
        $this->_add_font( 'F1', 'Helvetica' );
        $this->_add_font( 'F2', 'Helvetica-Bold' );
        $this->_add_font( 'F3', 'Helvetica-Oblique' );
        $this->add_page();
    }

    // ── Fonts ────────────────────────────────────────────────────────────────

    private function _add_font( $alias, $base ) {
        $id = count( $this->objects ) + 1;
        $this->objects[ $id ] = sprintf(
            "<< /Type /Font /Subtype /Type1 /BaseFont /%s /Encoding /WinAnsiEncoding >>",
            $base
        );
        $this->fonts[ $alias ] = $id;
    }

    public function set_font( $alias = 'F1', $size = 11 ) {
        $this->cur_font = $alias;
        $this->cur_size = $size;
    }

    public function set_fill( $r, $g, $b ) {
        $this->fill = sprintf( '%.3f %.3f %.3f', $r/255, $g/255, $b/255 );
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public function add_page() {
        $this->cur_page = [];
        $this->pages[]  = &$this->cur_page;
        $this->y        = self::M;
    }

    private function _append( $cmd ) {
        $this->cur_page[] = $cmd;
    }

    // ── Primitives ───────────────────────────────────────────────────────────

    /** Draw text at absolute position (top-down coords). */
    public function text( $x, $y, $str, $size = null, $font = null ) {
        $f = $font ?? $this->cur_font;
        $s = $size ?? $this->cur_size;
        $py = self::H - $y;
        $str = $this->_enc( $str );
        $this->_append( sprintf(
            "BT /%s %s Tf %s rg %.2f %.2f Td (%s) Tj ET",
            $f, $s, $this->fill, $x, $py, $str
        ) );
    }

    /** Draw a horizontal line. */
    public function hline( $x1, $y, $x2, $width = 0.5, $gray = 0.8 ) {
        $py = self::H - $y;
        $this->_append( sprintf(
            "%.3f G %.2f w %.2f %.2f m %.2f %.2f l S 0 G %.2f w",
            $gray, $width, $x1, $py, $x2, $py, $this->line_w
        ) );
    }

    /** Filled rectangle (top-down coords). */
    public function rect_fill( $x, $y, $w, $h, $r, $g, $b ) {
        $py = self::H - $y - $h;
        $this->_append( sprintf(
            "%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f 0 0 0 rg",
            $r/255, $g/255, $b/255, $x, $py, $w, $h
        ) );
    }

    // ── Flow helpers ─────────────────────────────────────────────────────────

    public function get_y()       { return $this->y; }
    public function set_y( $y )  { $this->y = $y; }

    /**
     * Add a text line at current Y and advance Y by $line_height.
     */
    public function ln( $str, $x = null, $size = null, $font = null, $line_height = null ) {
        $s = $size ?? $this->cur_size;
        $lh = $line_height ?? ( $s * 1.45 );
        $this->text( $x ?? self::M, $this->y, $str, $s, $font );
        $this->y += $lh;
    }

    public function move_y( $delta ) { $this->y += $delta; }

    /**
     * Table row: array of [text, x, width, align].
     */
    public function row( $cols, $row_h = 18, $bg = null ) {
        if ( $bg ) {
            $this->rect_fill( self::M, $this->y, self::W - self::M * 2, $row_h, ...$bg );
        }
        foreach ( $cols as [ $text, $x, $w, $align ] ) {
            $s = $this->cur_size;
            $tw = $this->_text_width( $text, $s );
            $tx = $align === 'R' ? ( $x + $w - $tw - 2 )
                : ( $align === 'C' ? ( $x + ( $w - $tw ) / 2 ) : $x + 2 );
            $this->text( $tx, $this->y + $row_h - 5, $text );
        }
        $this->y += $row_h;
    }

    // ── Output ───────────────────────────────────────────────────────────────

    public function output( $filename = 'document.pdf' ) {
        $pdf  = "%PDF-1.4\n";
        $xref = [];

        // Allocate object IDs
        // Font objects are already in $this->objects keyed by font id
        // Page streams + Page dicts + Pages + Catalog
        $font_objs = array_values( $this->fonts ); // [int, ...]
        $max_font_obj = max( $font_objs );

        $next = $max_font_obj + 1;
        $stream_ids = [];
        $page_ids   = [];

        foreach ( $this->pages as $p ) {
            $stream_ids[] = $next++;
            $page_ids[]   = $next++;
        }

        $pages_id   = $next++;
        $catalog_id = $next++;

        // Build font resource string
        $font_res = '';
        foreach ( $this->fonts as $alias => $obj_id ) {
            $font_res .= "/$alias $obj_id 0 R ";
        }

        // Write font objects
        foreach ( $this->objects as $id => $dict ) {
            $xref[ $id ] = strlen( $pdf );
            $pdf .= "$id 0 obj\n$dict\nendobj\n";
        }

        // Write page streams + dicts
        foreach ( $this->pages as $i => $cmds ) {
            $stream_content = implode( "\n", $cmds );
            $sid = $stream_ids[ $i ];
            $pid = $page_ids[ $i ];

            $xref[ $sid ] = strlen( $pdf );
            $pdf .= "$sid 0 obj\n<< /Length " . strlen( $stream_content ) . " >>\nstream\n$stream_content\nendstream\nendobj\n";

            $xref[ $pid ] = strlen( $pdf );
            $pdf .= "$pid 0 obj\n<< /Type /Page /Parent $pages_id 0 R"
                  . " /MediaBox [0 0 " . self::W . " " . self::H . "]"
                  . " /Contents $sid 0 R"
                  . " /Resources << /Font << $font_res>> >>"
                  . " >>\nendobj\n";
        }

        // Pages
        $kids = implode( ' ', array_map( fn($id) => "$id 0 R", $page_ids ) );
        $xref[ $pages_id ] = strlen( $pdf );
        $pdf .= "$pages_id 0 obj\n<< /Type /Pages /Kids [$kids] /Count " . count( $page_ids ) . " >>\nendobj\n";

        // Catalog
        $xref[ $catalog_id ] = strlen( $pdf );
        $pdf .= "$catalog_id 0 obj\n<< /Type /Catalog /Pages $pages_id 0 R >>\nendobj\n";

        // xref table
        $xref_offset = strlen( $pdf );
        $total = $catalog_id + 1;
        $pdf .= "xref\n0 $total\n";
        $pdf .= "0000000000 65535 f \n";
        for ( $i = 1; $i < $total; $i++ ) {
            if ( isset( $xref[ $i ] ) ) {
                $pdf .= sprintf( "%010d 00000 n \n", $xref[ $i ] );
            } else {
                $pdf .= "0000000000 65535 f \n";
            }
        }

        $pdf .= "trailer\n<< /Size $total /Root $catalog_id 0 R >>\nstartxref\n$xref_offset\n%%EOF";

        if ( ! headers_sent() ) {
            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Content-Length: ' . strlen( $pdf ) );
            header( 'Cache-Control: private, no-cache' );
        }
        echo $pdf;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function _enc( $str ) {
        // Convert UTF-8 to WinAnsi (ISO-8859-1 superset)
        $str = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $str ) ?: $str;
        // Escape PDF special chars
        $str = str_replace( ['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $str );
        return $str;
    }

    private function _text_width( $str, $size ) {
        // Approximate: Helvetica avg char width ~0.52 of point size
        return mb_strlen( $str ) * $size * 0.52;
    }
}

// ── Invoice PDF Builder ───────────────────────────────────────────────────────

function vesho_build_invoice_pdf( $invoice_id ) {
    global $wpdb;

    $inv = $wpdb->get_row( $wpdb->prepare(
        "SELECT i.*, c.name as client_name, c.email as client_email,
                c.phone as client_phone, c.address as client_address,
                c.client_type, c.reg_code, c.vat_number, c.contact_person
         FROM {$wpdb->prefix}vesho_invoices i
         LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id = c.id
         WHERE i.id = %d", $invoice_id
    ) );
    if ( ! $inv ) return false;

    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id = %d ORDER BY id ASC",
        $invoice_id
    ) );

    $settings = [];
    $rows = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$wpdb->prefix}vesho_settings" );
    foreach ( $rows as $r ) $settings[ $r->setting_key ] = $r->setting_value;

    $company     = $settings['company_name']  ?? 'Vesho';
    $company_reg = $settings['company_reg']   ?? '';
    $company_vat = $settings['company_vat']   ?? '';
    $company_addr= $settings['company_address'] ?? '';
    $company_iban= $settings['bank_iban']      ?? '';
    $company_email= $settings['company_email'] ?? '';
    $company_phone= $settings['company_phone'] ?? '';

    $pdf = new Vesho_PDF();

    // ── Header bar ───────────────────────────────────────────────────────────
    $pdf->rect_fill( 0, 0, Vesho_PDF::W, 56, 0, 180, 200 );
    $pdf->set_fill( 255, 255, 255 );
    $pdf->set_font( 'F2', 18 );
    $pdf->text( Vesho_PDF::M, 20, $company );
    $pdf->set_font( 'F1', 9 );
    $pdf->text( Vesho_PDF::M, 38, $company_addr );
    $pdf->set_fill( 0, 0, 0 );

    // ── Invoice title ────────────────────────────────────────────────────────
    $pdf->set_y( 72 );
    $pdf->set_font( 'F2', 15 );
    $pdf->ln( 'ARVE ' . $inv->invoice_number );
    $pdf->set_font( 'F1', 10 );
    $pdf->ln( 'Kuupäev: ' . date( 'd.m.Y', strtotime( $inv->invoice_date ) ) );
    if ( $inv->due_date ) {
        $pdf->ln( 'Maksetähtaeg: ' . date( 'd.m.Y', strtotime( $inv->due_date ) ) );
    }

    // ── Client info ──────────────────────────────────────────────────────────
    $pdf->move_y( 6 );
    $pdf->hline( Vesho_PDF::M, $pdf->get_y(), Vesho_PDF::W - Vesho_PDF::M );
    $pdf->move_y( 8 );
    $pdf->set_font( 'F2', 10 );
    $pdf->ln( 'Klient:' );
    $pdf->set_font( 'F1', 10 );
    $pdf->ln( $inv->client_name ?? '' );
    if ( $inv->client_address ) $pdf->ln( $inv->client_address );
    if ( $inv->client_type === 'firma' ) {
        if ( $inv->reg_code )  $pdf->ln( 'Reg: ' . $inv->reg_code );
        if ( $inv->vat_number ) $pdf->ln( 'KM reg: ' . $inv->vat_number );
    }
    if ( $inv->client_email ) $pdf->ln( $inv->client_email );

    // ── Items table header ────────────────────────────────────────────────────
    $pdf->move_y( 10 );
    $cx = Vesho_PDF::M;
    $cw = [ 240, 50, 60, 50, 65 ]; // Nimetus, Kogus, Ühik, Hind, Kokku
    $cols_hdr = [
        [ 'Nimetus',  $cx,                  $cw[0], 'L' ],
        [ 'Kogus',    $cx+$cw[0],           $cw[1], 'R' ],
        [ 'Ühik',     $cx+$cw[0]+$cw[1],    $cw[2], 'L' ],
        [ 'Hind',     $cx+$cw[0]+$cw[1]+$cw[2], $cw[3], 'R' ],
        [ 'Kokku',    $cx+$cw[0]+$cw[1]+$cw[2]+$cw[3], $cw[4], 'R' ],
    ];
    $pdf->set_font( 'F2', 9 );
    $pdf->set_fill( 255, 255, 255 );
    $pdf->row( $cols_hdr, 18, [ 0, 180, 200 ] );
    $pdf->set_fill( 0, 0, 0 );

    // ── Items ────────────────────────────────────────────────────────────────
    $subtotal  = 0;
    $total_vat = 0;
    $bg_alt    = [ 245, 250, 252 ];
    $pdf->set_font( 'F1', 9 );

    foreach ( $items as $idx => $item ) {
        $line_total = (float)$item->quantity * (float)$item->unit_price;
        $vat_amt    = $line_total * ( (float)( $item->vat_rate ?? 0 ) / 100 );
        $subtotal  += $line_total;
        $total_vat += $vat_amt;

        $bg = ( $idx % 2 === 1 ) ? $bg_alt : null;
        $pdf->row( [
            [ $item->description,  $cx,                                 $cw[0], 'L' ],
            [ number_format( $item->quantity, 2, ',', '.' ), $cx+$cw[0], $cw[1], 'R' ],
            [ $item->unit ?? 'tk', $cx+$cw[0]+$cw[1],                  $cw[2], 'L' ],
            [ number_format( $item->unit_price, 2, ',', '.' ) . ' €',  $cx+$cw[0]+$cw[1]+$cw[2], $cw[3], 'R' ],
            [ number_format( $line_total, 2, ',', '.' ) . ' €',         $cx+$cw[0]+$cw[1]+$cw[2]+$cw[3], $cw[4], 'R' ],
        ], 16, $bg );

        // Page break
        if ( $pdf->get_y() > Vesho_PDF::H - 120 ) {
            $pdf->add_page();
            $pdf->set_y( Vesho_PDF::M );
        }
    }

    // ── Totals ───────────────────────────────────────────────────────────────
    $pdf->hline( Vesho_PDF::M, $pdf->get_y(), Vesho_PDF::W - Vesho_PDF::M );
    $pdf->move_y( 6 );
    $rx = Vesho_PDF::W - Vesho_PDF::M - 160;

    $pdf->set_font( 'F1', 10 );
    $pdf->text( $rx, $pdf->get_y(), 'Kokku (neto):' );
    $pdf->text( Vesho_PDF::W - Vesho_PDF::M - 2 - 60, $pdf->get_y(), number_format( $subtotal, 2, ',', '.' ) . ' €' );
    $pdf->move_y( 16 );

    if ( $total_vat > 0 ) {
        $pdf->text( $rx, $pdf->get_y(), 'KM:' );
        $pdf->text( Vesho_PDF::W - Vesho_PDF::M - 2 - 60, $pdf->get_y(), number_format( $total_vat, 2, ',', '.' ) . ' €' );
        $pdf->move_y( 16 );
    }

    $grand = $subtotal + $total_vat;
    $pdf->set_font( 'F2', 12 );
    $pdf->text( $rx, $pdf->get_y(), 'KOKKU:' );
    $pdf->text( Vesho_PDF::W - Vesho_PDF::M - 2 - 70, $pdf->get_y(), number_format( $grand, 2, ',', '.' ) . ' €' );
    $pdf->move_y( 20 );

    // ── Payment info ─────────────────────────────────────────────────────────
    if ( $company_iban ) {
        $pdf->hline( Vesho_PDF::M, $pdf->get_y(), Vesho_PDF::W - Vesho_PDF::M );
        $pdf->move_y( 8 );
        $pdf->set_font( 'F2', 10 );
        $pdf->ln( 'Pangaandmed:' );
        $pdf->set_font( 'F1', 10 );
        $pdf->ln( 'IBAN: ' . $company_iban );
        if ( $company_email ) $pdf->ln( 'E-post: ' . $company_email );
        if ( $company_phone ) $pdf->ln( 'Tel: ' . $company_phone );
        if ( $company_vat )   $pdf->ln( 'KM reg: ' . $company_vat );
        if ( $company_reg )   $pdf->ln( 'Reg nr: ' . $company_reg );
    }

    $filename = 'arve-' . sanitize_file_name( $inv->invoice_number ) . '.pdf';
    $pdf->output( $filename );
    exit;
}
