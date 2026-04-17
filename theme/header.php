<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ── Fixed top wrapper ─────────────────────────────────────────────────── -->
<div class="site-top-wrapper">

<!-- ── Top Bar ────────────────────────────────────────────────────────────── -->
<div class="site-topbar">
    <span>Jälgi meid:</span>
    <?php $fb = get_theme_mod('vesho_facebook','#'); $li = get_theme_mod('vesho_linkedin','#'); $ig = get_theme_mod('vesho_instagram','#'); ?>
    <a href="<?php echo esc_url($fb); ?>" aria-label="Facebook" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
    </a>
    <a href="<?php echo esc_url($li); ?>" aria-label="LinkedIn" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
    </a>
    <a href="<?php echo esc_url($ig); ?>" aria-label="Instagram" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
    </a>
</div>

<!-- ── Site Header ─────────────────────────────────────────────────────────── -->
<header class="site-header" id="site-header" role="banner">
    <div class="header-inner container">

        <!-- Logo -->
        <a class="header-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php bloginfo( 'name' ); ?> – avaleht">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="header-logo__icon" aria-hidden="true">
                    <!-- Water drop SVG -->
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#00b4c8"/>
                        <path d="M11 20C11 17 13 14.5 14.5 12.5" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="header-logo__name">VESHO</span>
            <?php endif; ?>
        </a>

        <!-- Primary Navigation -->
        <nav class="header-nav" id="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Peamine navigatsioon', 'vesho' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'menu_class'     => 'nav__list',
                'container'      => false,
                'walker'         => new Vesho_Nav_Walker(),
                'fallback_cb'    => 'vesho_fallback_nav',
            ) );
            ?>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <!-- Phone (desktop) -->
            <?php $phone = get_theme_mod( 'vesho_phone', '+372 5XXX XXXX' ); ?>
            <a class="header-phone" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>" aria-label="Helista meile">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.64A2 2 0 012 .81h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                </svg>
                <?php echo esc_html( $phone ); ?>
            </a>

            <!-- User Avatar / Login -->
            <?php
            $portal_page   = get_page_by_path('klient');
            $portal_url    = $portal_page ? get_permalink($portal_page) : home_url('/klient/');
            $is_wp_admin   = current_user_can('manage_options');
            $is_crm_client = is_user_logged_in() && current_user_can('vesho_client');
            $is_worker     = is_user_logged_in() && current_user_can('vesho_worker');
            ?>
            <?php if ( is_user_logged_in() ) :
                $cur_user  = wp_get_current_user();
                $disp_name = $cur_user->display_name ?: $cur_user->user_login;
                $avatar_letter = strtoupper( mb_substr( $disp_name, 0, 1 ) );
                if ( $is_wp_admin )      $sub = 'admin vaade';
                elseif ( $is_worker )    $sub = 'töötaja';
                else                     $sub = 'klient';
            ?>
            <div class="user-dropdown" id="userDropdown">
                <button class="user-dropdown__toggle" id="userDropdownBtn" aria-expanded="false">
                    <span class="user-avatar"><?php echo esc_html($avatar_letter); ?></span>
                    <span class="user-name"><?php echo esc_html($disp_name); ?></span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="user-dropdown__menu" id="userDropdownMenu" aria-hidden="true">
                    <div class="user-dropdown__header">
                        <strong><?php echo esc_html($disp_name); ?></strong>
                        <span>(<?php echo esc_html($sub); ?>)</span>
                    </div>
                    <?php if ( $is_crm_client ) : ?>
                        <a href="<?php echo esc_url($portal_url); ?>">&#128200; Minu ülevaade</a>
                        <a href="<?php echo esc_url(add_query_arg('ptab','maintenances',$portal_url)); ?>">&#128295; Minu hooldused</a>
                        <a href="<?php echo esc_url(add_query_arg('ptab','invoices',$portal_url)); ?>">&#128196; Minu arved</a>
                        <a href="<?php echo esc_url(add_query_arg('ptab','profile',$portal_url)); ?>">&#9881;&#65039; Konto seaded</a>
                    <?php elseif ( $is_worker ) : ?>
                        <a href="<?php echo esc_url(home_url('/worker/')); ?>">&#128295; Minu töökäsud</a>
                    <?php elseif ( $is_wp_admin ) : ?>
                        <a href="<?php echo esc_url($portal_url); ?>">&#128100; Kliendi portaal</a>
                        <a href="<?php echo esc_url(home_url('/worker/')); ?>">&#128295; Töötaja portaal</a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm')); ?>">&#9883;&#65039; CRM Paneel</a>
                        <a href="<?php echo esc_url(admin_url()); ?>">&#9881;&#65039; WP Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="user-dropdown__logout">&#128682; Logi välja</a>
                </div>
            </div>
            <?php else : ?>
                <button class="btn btn-portal-link" id="open-login-modal" aria-haspopup="dialog" aria-controls="login-modal">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Logi sisse
                </button>
            <?php endif; ?>

            <!-- User dropdown JS -->
            <script>
            (function(){
                var btn = document.getElementById('userDropdownBtn');
                var menu = document.getElementById('userDropdownMenu');
                if (!btn || !menu) return;
                btn.addEventListener('click', function(e){
                    e.stopPropagation();
                    var open = menu.classList.toggle('open');
                    btn.setAttribute('aria-expanded', open);
                    menu.setAttribute('aria-hidden', !open);
                });
                document.addEventListener('click', function(){ menu.classList.remove('open'); btn && btn.setAttribute('aria-expanded','false'); });
            })();
            </script>

            <!-- CTA Button -->
            <button
                class="btn btn-primary header-cta"
                id="header-cta-btn"
                aria-haspopup="dialog"
                aria-controls="service-modal"
            >
                <?php _e( 'Küsi Pakkumist', 'vesho' ); ?>
            </button>

            <!-- Hamburger (mobile) -->
            <button
                class="hamburger"
                id="hamburger-btn"
                aria-expanded="false"
                aria-controls="primary-nav"
                aria-label="<?php esc_attr_e( 'Ava/sulge menüü', 'vesho' ); ?>"
            >
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
            </button>
        </div>

    </div><!-- .header-inner -->

    <!-- Mobile nav overlay -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay" aria-hidden="true"></div>
</header><!-- .site-header -->

</div><!-- .site-top-wrapper -->

<!-- ── Service Request Modal ──────────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-backdrop" role="presentation" aria-hidden="true"></div>
<div class="modal" id="service-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true" tabindex="-1">
    <div class="modal__inner">
        <button class="modal__close" id="modal-close-btn" aria-label="<?php esc_attr_e( 'Sulge modaal', 'vesho' ); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <div class="modal__header">
            <span class="modal__icon" aria-hidden="true">💧</span>
            <h2 class="modal__title" id="modal-title"><?php _e( 'Küsi Pakkumist', 'vesho' ); ?></h2>
            <p class="modal__sub"><?php _e( 'Täitke vorm ja võtame teiega ühendust 24 tunni jooksul.', 'vesho' ); ?></p>
        </div>

        <div id="modal-success" class="modal__success" style="display:none;" role="status" aria-live="polite">
            <span class="modal__success-icon" aria-hidden="true">✅</span>
            <p class="modal__success-title"><?php _e( 'Päring edukalt saadetud!', 'vesho' ); ?></p>
            <p class="modal__success-sub"><?php _e( 'Võtame teiega lähiajal ühendust.', 'vesho' ); ?></p>
            <button class="btn btn-primary" id="modal-new-request"><?php _e( 'Saada uus päring', 'vesho' ); ?></button>
        </div>

        <form class="modal__form" id="service-request-form" novalidate>
            <?php wp_nonce_field( 'vesho_nonce', 'vesho_nonce_field' ); ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sr-name"><?php _e( 'Nimi', 'vesho' ); ?> <span aria-hidden="true">*</span></label>
                    <input
                        class="form-input"
                        type="text"
                        id="sr-name"
                        name="name"
                        placeholder="<?php esc_attr_e( 'Teie nimi', 'vesho' ); ?>"
                        required
                        autocomplete="name"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label" for="sr-email"><?php _e( 'E-post', 'vesho' ); ?> <span aria-hidden="true">*</span></label>
                    <input
                        class="form-input"
                        type="email"
                        id="sr-email"
                        name="email"
                        placeholder="nimi@ettevote.ee"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sr-phone"><?php _e( 'Telefon', 'vesho' ); ?></label>
                    <input
                        class="form-input"
                        type="tel"
                        id="sr-phone"
                        name="phone"
                        placeholder="+372 5XXX XXXX"
                        autocomplete="tel"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label" for="sr-device"><?php _e( 'Seadme nimi / tüüp', 'vesho' ); ?></label>
                    <input
                        class="form-input"
                        type="text"
                        id="sr-device"
                        name="device_name"
                        placeholder="<?php esc_attr_e( 'nt. veepump, filtreerimissüsteem', 'vesho' ); ?>"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sr-service"><?php _e( 'Teenuse tüüp', 'vesho' ); ?></label>
                    <select class="form-select" id="sr-service" name="service_type">
                        <option value=""><?php _e( '— Vali teenus —', 'vesho' ); ?></option>
                        <?php
                        $services = function_exists('vesho_get_services') ? vesho_get_services() : [];
                        foreach ( $services as $svc ) {
                            printf(
                                '<option value="%s">%s %s</option>',
                                esc_attr( $svc->name ),
                                esc_html( $svc->icon ?? '💧' ),
                                esc_html( $svc->name )
                            );
                        }
                        ?>
                        <option value="Muu"><?php _e( 'Muu', 'vesho' ); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sr-date"><?php _e( 'Soovitud kuupäev', 'vesho' ); ?></label>
                    <input
                        class="form-input"
                        type="date"
                        id="sr-date"
                        name="preferred_date"
                        min="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="sr-desc"><?php _e( 'Kirjeldus', 'vesho' ); ?></label>
                <textarea
                    class="form-textarea"
                    id="sr-desc"
                    name="description"
                    rows="4"
                    placeholder="<?php esc_attr_e( 'Kirjeldage lühidalt oma probleemi või soovi...', 'vesho' ); ?>"
                ></textarea>
            </div>

            <div id="modal-error" class="alert alert-error" style="display:none;" role="alert" aria-live="assertive"></div>

            <button type="submit" class="btn btn-primary w-full" id="modal-submit-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                <?php _e( 'Saada Päring', 'vesho' ); ?>
            </button>
        </form>
    </div><!-- .modal__inner -->
</div><!-- #service-modal -->

<!-- ── Login / Register Modal ─────────────────────────────────────────────── -->
<?php if (!is_user_logged_in()) : ?>
<!-- Login modal — only for guests -->
<div class="modal-backdrop" id="login-modal-backdrop" aria-hidden="true"></div>
<div class="modal" id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" aria-hidden="true" tabindex="-1">
    <div class="modal__inner" style="max-width:420px">
        <button class="modal__close" id="login-modal-close" aria-label="Sulge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <div class="modal__header" style="padding-bottom:0">
            <span class="modal__icon" aria-hidden="true">💧</span>
            <h2 class="modal__title" id="login-modal-title">Klientide Portaal</h2>
            <p class="modal__sub">Logi sisse või registreeru uue kliendina</p>
        </div>

        <!-- Tabs -->
        <div style="display:flex;border-bottom:2px solid #dce8ef;margin:16px 0 0">
            <button class="lm-tab active" data-tab="login" style="flex:1;padding:11px;background:none;border:none;border-bottom:2px solid transparent;font-size:14px;font-weight:600;color:#6b8599;cursor:pointer;margin-bottom:-2px;transition:.15s">Logi sisse</button>
            <button class="lm-tab" data-tab="register" style="flex:1;padding:11px;background:none;border:none;border-bottom:2px solid transparent;font-size:14px;font-weight:600;color:#6b8599;cursor:pointer;margin-bottom:-2px;transition:.15s">Registreeru</button>
        </div>

        <!-- Login Panel -->
        <div class="lm-panel" id="lm-panel-login" style="padding:20px 0 4px">
            <div id="lm-login-msg" style="display:none;padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:12px"></div>
            <form id="lm-login-form" novalidate>
                <?php wp_nonce_field('vesho_portal_nonce','lm_nonce'); ?>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">E-posti aadress</label>
                        <input type="email" name="email" required autocomplete="username" placeholder="nimi@ettevote.ee"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Parool</label>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <button type="submit" class="btn btn-primary w-full" style="margin-top:4px">Logi sisse</button>
                </div>
            </form>
        </div>

        <!-- Register Panel -->
        <div class="lm-panel" id="lm-panel-register" style="display:none;padding:20px 0 4px">
            <div id="lm-register-msg" style="display:none;padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:12px"></div>
            <form id="lm-register-form" novalidate>
                <?php wp_nonce_field('vesho_portal_nonce','lm_nonce_reg'); ?>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <!-- Kliendi tüüp -->
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:6px">Kliendi tüüp</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <label id="lm-type-eraisik" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;border:2px solid #00b4c8;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;color:#00b4c8;background:#f0fdfe;transition:.15s">
                                <input type="radio" name="client_type" value="eraisik" checked style="display:none"> 👤 Eraisik
                            </label>
                            <label id="lm-type-firma" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px;border:2px solid #dce8ef;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;color:#6b8599;background:#fff;transition:.15s">
                                <input type="radio" name="client_type" value="firma" style="display:none"> 🏢 Ettevõte
                            </label>
                        </div>
                    </div>
                    <div>
                        <label id="lm-name-label" style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Täisnimi *</label>
                        <input type="text" name="name" required autocomplete="name" placeholder="Nimi Perekonnanimi"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <!-- Firma väljad (peidetud vaikimisi) -->
                    <div class="lm-firma-field" style="display:none">
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Ettevõtte nimi *</label>
                        <input type="text" name="company" placeholder="OÜ Näide"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" class="lm-firma-field" style="display:none">
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Registrikood</label>
                            <input type="text" name="reg_code" placeholder="12345678"
                                style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">KMKR nr</label>
                            <input type="text" name="vat_number" placeholder="EE123456789"
                                style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                        </div>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">E-posti aadress *</label>
                        <input type="email" name="email" required autocomplete="email" placeholder="nimi@ettevote.ee"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Telefon</label>
                        <input type="tel" name="phone" autocomplete="tel" placeholder="+372 5XXX XXXX"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:4px">Parool * <span style="font-weight:400;color:#6b8599">(min. 8 märki)</span></label>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="••••••••"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:7px;font-size:14px;box-sizing:border-box;outline:none">
                    </div>
                    <button type="submit" class="btn btn-primary w-full" style="margin-top:4px">Registreeru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    var portalUrl = '<?php echo esc_js($portal_url); ?>';
    var ajaxUrl   = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

    // Open modal
    function openLoginModal(tab) {
        document.getElementById('login-modal').setAttribute('aria-hidden','false');
        document.getElementById('login-modal').classList.add('is-open');
        document.getElementById('login-modal-backdrop').setAttribute('aria-hidden','false');
        document.getElementById('login-modal-backdrop').classList.add('is-open');
        document.body.style.overflow = 'hidden';
        if (tab) switchTab(tab);
    }
    function closeLoginModal() {
        document.getElementById('login-modal').setAttribute('aria-hidden','true');
        document.getElementById('login-modal').classList.remove('is-open');
        document.getElementById('login-modal-backdrop').setAttribute('aria-hidden','true');
        document.getElementById('login-modal-backdrop').classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Tab switching
    function switchTab(name) {
        document.querySelectorAll('.lm-tab').forEach(function(t){
            var active = t.dataset.tab === name;
            t.classList.toggle('active', active);
            t.style.color = active ? '#00b4c8' : '#6b8599';
            t.style.borderBottomColor = active ? '#00b4c8' : 'transparent';
        });
        document.querySelectorAll('.lm-panel').forEach(function(p){ p.style.display='none'; });
        var panel = document.getElementById('lm-panel-' + name);
        if (panel) panel.style.display='block';
    }

    document.querySelectorAll('.lm-tab').forEach(function(t){
        t.addEventListener('click', function(){ switchTab(t.dataset.tab); });
    });

    // Trigger open
    var openBtn = document.getElementById('open-login-modal');
    if (openBtn) openBtn.addEventListener('click', function(){ openLoginModal('login'); });

    // Close
    document.getElementById('login-modal-close').addEventListener('click', closeLoginModal);
    document.getElementById('login-modal-backdrop').addEventListener('click', closeLoginModal);
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLoginModal(); });

    // Auto-open if ?login=1 in URL
    if (window.location.search.indexOf('login=1') !== -1) openLoginModal('login');
    if (window.location.search.indexOf('register=1') !== -1) openLoginModal('register');

    // AJAX form helper
    function doAjax(formId, action, msgId, btnLabel) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var msg = document.getElementById(msgId);
            var fd  = new FormData(form);
            fd.append('action', action);
            // Fix nonce field name — handler expects 'nonce'
            var nonceEl = form.querySelector('[name$="_nonce"]');
            if (nonceEl) fd.append('nonce', nonceEl.value);
            var btn = form.querySelector('button[type="submit"]');
            var orig = btn ? btn.textContent : '';
            if (btn) { btn.disabled=true; btn.textContent='...'; }

            fetch(ajaxUrl, {method:'POST', body:fd})
            .then(function(r){ return r.json(); })
            .then(function(d){
                msg.style.display='block';
                if (d.success) {
                    msg.style.background='#d1fae5'; msg.style.color='#065f46'; msg.style.border='1px solid #a7f3d0';
                    msg.textContent = (d.data && d.data.message) || 'Õnnestus!';
                    setTimeout(function(){
                        window.location.href = (d.data && d.data.redirect) || portalUrl;
                    }, 600);
                } else {
                    msg.style.background='#fee2e2'; msg.style.color='#991b1b'; msg.style.border='1px solid #fecaca';
                    msg.textContent = (d.data && d.data.message) || 'Viga! Proovi uuesti.';
                    if (btn) { btn.disabled=false; btn.textContent=orig; }
                }
            })
            .catch(function(){
                msg.style.display='block';
                msg.style.background='#fee2e2'; msg.style.color='#991b1b';
                msg.textContent='Ühenduse viga';
                if (btn) { btn.disabled=false; btn.textContent=orig; }
            });
        });
    }

    doAjax('lm-login-form',    'vesho_client_login',    'lm-login-msg');
    doAjax('lm-register-form', 'vesho_client_register', 'lm-register-msg');

    // Firma / eraisik toggle
    document.querySelectorAll('#lm-register-form input[name="client_type"]').forEach(function(radio){
        radio.addEventListener('change', function(){
            var isFirma = radio.value === 'firma';
            document.querySelectorAll('.lm-firma-field').forEach(function(el){
                el.style.display = isFirma ? '' : 'none';
            });
            var nameLabel = document.getElementById('lm-name-label');
            if (nameLabel) nameLabel.textContent = isFirma ? 'Kontaktisik *' : 'Täisnimi *';
            // Style the toggle buttons
            var eBtn = document.getElementById('lm-type-eraisik');
            var fBtn = document.getElementById('lm-type-firma');
            if (eBtn) { eBtn.style.borderColor=isFirma?'#dce8ef':'#00b4c8'; eBtn.style.color=isFirma?'#6b8599':'#00b4c8'; eBtn.style.background=isFirma?'#fff':'#f0fdfe'; }
            if (fBtn) { fBtn.style.borderColor=isFirma?'#00b4c8':'#dce8ef'; fBtn.style.color=isFirma?'#00b4c8':'#6b8599'; fBtn.style.background=isFirma?'#f0fdfe':'#fff'; }
        });
    });
    // Clicking the label toggles the radio
    ['lm-type-eraisik','lm-type-firma'].forEach(function(id){
        var lbl = document.getElementById(id);
        if (lbl) lbl.addEventListener('click', function(){
            var r = lbl.querySelector('input[type="radio"]');
            if (r) { r.checked=true; r.dispatchEvent(new Event('change',{bubbles:true})); }
        });
    });
})();
</script>
<?php endif; ?>

<!-- ── Main Content Wrapper ───────────────────────────────────────────────── -->
<div id="page-content">
