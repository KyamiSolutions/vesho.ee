<?php
/**
 * Vesho Stats Grid — Elementor widget
 * Elementori paneelis: Vesho → Stats Grid
 * 4 statistikaplokki, mobiilis 2×2.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Vesho_Widget_Stats_Grid extends Widget_Base {

    public function get_name()        { return 'vesho_stats_grid'; }
    public function get_title()       { return 'Stats Grid'; }
    public function get_icon()        { return 'eicon-counter'; }
    public function get_categories()  { return [ 'vesho' ]; }
    public function get_keywords()    { return [ 'stats', 'counter', 'numbers', 'vesho' ]; }

    protected function register_controls() {

        $this->start_controls_section( 'section_stats', [
            'label' => 'Statistika',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        for ( $i = 1; $i <= 4; $i++ ) {
            $this->add_control( "stat{$i}_num", [
                'label'   => "Stat {$i} — number",
                'type'    => Controls_Manager::TEXT,
                'default' => [ '500+', '10+', '24h', '99%' ][ $i - 1 ],
            ] );
            $this->add_control( "stat{$i}_label", [
                'label'     => "Stat {$i} — tekst",
                'type'      => Controls_Manager::TEXT,
                'default'   => [ 'Rahulolev klient', 'Aastat kogemust', 'Reaktsiooniaeg', 'Rahulolu' ][ $i - 1 ],
                'separator' => $i < 4 ? 'after' : 'none',
            ] );
        }

        $this->end_controls_section();
    }

    protected function render() {
        $s     = $this->get_settings_for_display();
        $stats = [];
        for ( $i = 1; $i <= 4; $i++ ) {
            $stats[] = [
                'num'   => esc_html( $s[ "stat{$i}_num" ]   ?? '' ),
                'label' => esc_html( $s[ "stat{$i}_label" ] ?? '' ),
            ];
        }
        ?>
        <div class="stats-grid">
            <?php foreach ( $stats as $stat ) : ?>
            <div class="stat-item">
                <span class="stat-item__num"><?php echo $stat['num']; ?></span>
                <span class="stat-item__label"><?php echo $stat['label']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
