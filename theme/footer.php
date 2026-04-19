</div><!-- #page-content -->

<!-- ── Site Footer ─────────────────────────────────────────────────────────── -->
<footer class="site-footer" role="contentinfo">

    <!-- Footer Top -->
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">

                <!-- Brand Column -->
                <div class="footer-col footer-col--brand">
                    <a class="footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php bloginfo( 'name' ); ?>">
                        <?php if ( has_custom_logo() ) : ?>
                            <?php the_custom_logo(); ?>
                        <?php else : ?>
                            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#00b4c8"/>
                            </svg>
                            <span class="footer-logo__name">VESHO</span>
                        <?php endif; ?>
                    </a>
                    <p class="footer-tagline">
                        <?php _e( 'Professionaalsed veesüsteemide lahendused kodudele ja ettevõtetele üle Eesti.', 'vesho' ); ?>
                    </p>
                    <!-- Social -->
                    <div class="footer-social">
                        <?php $fb = get_option( 'vesho_facebook', '' ); if ( $fb ) : ?>
                            <a href="<?php echo esc_url( $fb ); ?>" class="footer-social__link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php $li = get_option( 'vesho_linkedin', '' ); if ( $li ) : ?>
                            <a href="<?php echo esc_url( $li ); ?>" class="footer-social__link" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Links Column 1: Teenused -->
                <div class="footer-col">
                    <h3 class="footer-col__title"><?php _e( 'Teenused', 'vesho' ); ?></h3>
                    <?php if ( has_nav_menu( 'footer-1' ) ) : ?>
                        <?php wp_nav_menu( array(
                            'theme_location' => 'footer-1',
                            'menu_class'     => 'footer-nav',
                            'container'      => false,
                            'depth'          => 1,
                        ) ); ?>
                    <?php else : ?>
                        <ul class="footer-nav">
                            <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'teenused' ) ) ?: home_url( '/teenused/' ) ); ?>"><?php _e( 'Kõik teenused', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Hooldus', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Paigaldus', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Remont', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Konsultatsioon', 'vesho' ); ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Links Column 2: Ettevõte -->
                <div class="footer-col">
                    <h3 class="footer-col__title"><?php _e( 'Ettevõte', 'vesho' ); ?></h3>
                    <?php if ( has_nav_menu( 'footer-2' ) ) : ?>
                        <?php wp_nav_menu( array(
                            'theme_location' => 'footer-2',
                            'menu_class'     => 'footer-nav',
                            'container'      => false,
                            'depth'          => 1,
                        ) ); ?>
                    <?php else : ?>
                        <ul class="footer-nav">
                            <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'meist' ) ) ?: home_url( '/meist/' ) ); ?>"><?php _e( 'Meist', 'vesho' ); ?></a></li>
                            <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'kontakt' ) ) ?: home_url( '/kontakt/' ) ); ?>"><?php _e( 'Kontakt', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Hinnakirja', 'vesho' ); ?></a></li>
                            <li><a href="#"><?php _e( 'Privaatsuspoliitika', 'vesho' ); ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Contact Column -->
                <div class="footer-col">
                    <h3 class="footer-col__title"><?php _e( 'Kontakt', 'vesho' ); ?></h3>
                    <ul class="footer-contact">
                        <?php
                        $phone   = get_theme_mod( 'vesho_phone', vesho_get_setting( 'company_phone', '+372 5XXX XXXX' ) );
                        $email   = get_theme_mod( 'vesho_email', vesho_get_setting( 'company_email', 'info@vesho.ee' ) );
                        $address = get_theme_mod( 'vesho_address', vesho_get_setting( 'company_address', 'Tallinn, Eesti' ) );
                        $hours   = vesho_get_setting( 'working_hours', 'E–R 9:00–18:00' );
                        ?>
                        <li class="footer-contact__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.64A2 2 0 012 .81h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
                        </li>
                        <li class="footer-contact__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                        </li>
                        <li class="footer-contact__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?php echo esc_html( $address ); ?></span>
                        </li>
                        <li class="footer-contact__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span><?php echo esc_html( $hours ); ?></span>
                        </li>
                    </ul>
                </div>

            </div><!-- .footer-grid -->
        </div><!-- .container -->
    </div><!-- .footer-top -->

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom__inner">
                <p class="footer-copyright">
                    &copy; <?php echo date( 'Y' ); ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>.
                    <?php _e( 'Kõik õigused kaitstud.', 'vesho' ); ?>
                </p>
                <p class="footer-legal">
                    <?php
                    $reg = vesho_get_setting( 'company_reg', '' );
                    $vat = vesho_get_setting( 'company_vat', '' );
                    if ( $reg ) printf( __( 'Reg nr: %s', 'vesho' ), esc_html( $reg ) );
                    if ( $reg && $vat ) echo ' &nbsp;|&nbsp; ';
                    if ( $vat ) printf( __( 'KMKR: %s', 'vesho' ), esc_html( $vat ) );
                    ?>
                </p>
            </div>
        </div>
    </div><!-- .footer-bottom -->

</footer><!-- .site-footer -->

<?php wp_footer(); ?>

<?php
// ── Küpsiste bänner — ainult avalikul lehel, mitte portaalides ───────────────
$_ck_enabled = get_option('vesho_cookie_banner_enabled','1') === '1';
$_ck_show    = $_ck_enabled && !is_user_logged_in();
if ($_ck_show):
    $_ck_title  = esc_html(get_option('vesho_cookie_banner_title','Kasutame küpsiseid'));
    $_ck_text   = esc_html(get_option('vesho_cookie_banner_text','Kasutame küpsiseid, et parandada kasutajakogemust ja analüüsida liiklust.'));
    $_ck_accept = esc_html(get_option('vesho_cookie_accept_text','Nõustun kõigiga'));
    $_ck_reject = esc_html(get_option('vesho_cookie_reject_text','Ainult vajalikud'));
?>
<div id="vesho-cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a2535;color:#fff;padding:16px 24px;z-index:99998;box-shadow:0 -2px 12px rgba(0,0,0,.3);justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
    <div>
        <strong><?php echo $_ck_title; ?></strong><br>
        <span style="font-size:13px;opacity:.85"><?php echo $_ck_text; ?></span>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
        <button onclick="veshoCookieConsent('reject')" style="padding:8px 16px;background:transparent;color:#fff;border:1px solid rgba(255,255,255,.4);border-radius:6px;cursor:pointer;font-size:13px"><?php echo $_ck_reject; ?></button>
        <button onclick="veshoCookieConsent('accept')" style="padding:8px 16px;background:#00b4c8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px"><?php echo $_ck_accept; ?></button>
    </div>
</div>
<script>
(function(){
    var b = document.getElementById('vesho-cookie-banner');
    if (!b) return;
    if (!localStorage.getItem('vesho_cookie_consent')) b.style.display = 'flex';
    window.veshoCookieConsent = function(choice) {
        localStorage.setItem('vesho_cookie_consent', choice);
        localStorage.setItem('vesho_cookie_consent_date', new Date().toISOString());
        b.style.display = 'none';
        if (choice === 'accept' && typeof gtag !== 'undefined') {
            gtag('consent', 'update', { analytics_storage: 'granted' });
        }
    };
})();
</script>
<?php endif; ?>
</body>
</html>

