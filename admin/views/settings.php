<?php defined( 'ABSPATH' ) || exit;

$company_name    = get_option('vesho_company_name',    'Vesho OÜ');
$company_reg     = get_option('vesho_company_reg',     '');
$company_vat     = get_option('vesho_company_vat',     '');
$company_address = get_option('vesho_company_address', '');
$company_phone   = get_option('vesho_company_phone',   '');
$company_email   = get_option('vesho_company_email',   '');
$company_bank    = get_option('vesho_company_bank',    '');
$company_iban    = get_option('vesho_company_iban',    '');
$invoice_prefix  = get_option('vesho_invoice_prefix',  'INV-');
$invoice_start   = get_option('vesho_invoice_start',   '1001');
$portal_page     = get_option('vesho_portal_page',     '');
$login_page      = get_option('vesho_login_page',      '');
$worker_page     = get_option('vesho_worker_page',     '');

// Client portal config
$portal_title        = get_option('vesho_portal_title',        'Klientide Portaal');
$portal_welcome      = get_option('vesho_portal_welcome',      'Tere tulemast! Siin saad hallata oma teenuseid ja seadmeid.');
$portal_registration = get_option('vesho_portal_registration', '1');
$portal_show_devices      = get_option('vesho_portal_show_devices',      '1');
$portal_show_maintenances = get_option('vesho_portal_show_maintenances', '1');
$portal_show_services     = get_option('vesho_portal_show_services',     '1');
$portal_show_invoices     = get_option('vesho_portal_show_invoices',     '1');
$portal_show_support      = get_option('vesho_portal_show_support',      '1');
$show_contract_terms      = get_option('vesho_show_contract_terms',      '0');

// Sisu
$services_page_title    = get_option('vesho_services_page_title',    '');
$services_page_subtitle = get_option('vesho_services_page_subtitle', '');
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">⚙️ Seaded</h1>

    <?php if (isset($_GET['msg'])) :
        echo '<div class="crm-alert crm-alert-success">Seaded salvestatud!</div>';
    endif; ?>

    <!-- Tab navigation -->
    <div class="crm-tabs-bar" style="display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid #e2e8f0;padding-bottom:0;flex-wrap:wrap">
        <button type="button" class="crm-tab-btn active" data-tab="ettevote">🏢 Ettevõte</button>
        <button type="button" class="crm-tab-btn" data-tab="arveldus">💳 Arveldus</button>
        <button type="button" class="crm-tab-btn" data-tab="epood">🛒 E-pood</button>
        <button type="button" class="crm-tab-btn" data-tab="sisu">📝 Sisu</button>
        <button type="button" class="crm-tab-btn" data-tab="integratsioonid">🔗 Integratsioonid</button>
        <button type="button" class="crm-tab-btn" data-tab="kupsised">🍪 Küpsised</button>
        <button type="button" class="crm-tab-btn" data-tab="susteem">⚙️ Süsteem</button>
        <button type="button" class="crm-tab-btn" data-tab="valimus">🎨 Välimus</button>
    </div>
    <style>
    .crm-tab-btn{padding:8px 16px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
    .crm-tab-btn.active,.crm-tab-btn:hover{color:#00b4c8;border-bottom-color:#00b4c8}
    .crm-tab-section{display:none}.crm-tab-section.active{display:block}
    </style>

    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('vesho_save_settings'); ?>
        <input type="hidden" name="action" value="vesho_save_settings">

        <!-- ── TAB: Ettevõte ─────────────────────────────────────────── -->
        <div class="crm-tab-section active" data-tab-content="ettevote">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Ettevõtte andmed</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Ettevõtte nimi</label>
                        <input class="crm-form-input" type="text" name="company_name" value="<?php echo esc_attr($company_name); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Registrikood</label>
                        <input class="crm-form-input" type="text" name="company_reg" value="<?php echo esc_attr($company_reg); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">KMKR number</label>
                        <input class="crm-form-input" type="text" name="company_vat" value="<?php echo esc_attr($company_vat); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Telefon</label>
                        <input class="crm-form-input" type="text" name="company_phone" value="<?php echo esc_attr($company_phone); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">E-post</label>
                        <input class="crm-form-input" type="email" name="company_email" value="<?php echo esc_attr($company_email); ?>">
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Aadress</label>
                        <textarea class="crm-form-textarea" name="company_address" rows="2"><?php echo esc_textarea($company_address); ?></textarea>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Pank</label>
                        <input class="crm-form-input" type="text" name="company_bank" value="<?php echo esc_attr($company_bank); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">IBAN</label>
                        <input class="crm-form-input" type="text" name="company_iban" value="<?php echo esc_attr($company_iban); ?>">
                    </div>
                </div>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Arveldus ─────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="arveldus">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Arveldus</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Arve numbri eesliide</label>
                        <input class="crm-form-input" type="text" name="invoice_prefix" value="<?php echo esc_attr($invoice_prefix); ?>">
                        <small style="color:#6b8599;font-size:12px">Nt. INV-, VES-, 2024-</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Algav number</label>
                        <input class="crm-form-input" type="number" name="invoice_start" value="<?php echo esc_attr($invoice_start); ?>">
                        <small style="color:#6b8599;font-size:12px">Kasutatakse ainult uute arvete korral</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">KM määr (%)</label>
                        <input class="crm-form-input" type="number" step="0.1" min="0" max="100" name="vat_rate"
                               value="<?php echo esc_attr(get_option('vesho_vat_rate','22')); ?>" style="max-width:100px">
                        <small style="color:#6b8599;font-size:12px">Nt. 22 → 22%. Kasuta 0 kui ei ole KM-kohustuslane. Rakendub arvetel ja avalikul hinnakirjal.</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Maksetähtaeg (päevi)</label>
                        <input class="crm-form-input" type="number" min="0" max="180" name="invoice_due_days"
                               value="<?php echo esc_attr(get_option('vesho_invoice_due_days','14')); ?>" style="max-width:100px">
                        <small style="color:#6b8599;font-size:12px">Vaikimisi 14 päeva</small>
                    </div>
                </div>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: E-pood ────────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="epood">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🛒 E-pood</span></div>
                <div style="padding:20px">

                <div style="font-weight:600;font-size:13px;margin-bottom:10px;color:#1a2a38">Tarneviisid</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
                    <?php
                    $shipping_methods = [
                        'pickup'     => ['🏪 Järeltulek',   'shop_ship_pickup'],
                        'courier'    => ['🚚 Kuller',        'shop_ship_courier'],
                        'parcelshop' => ['📦 Pakiautomaat',  'shop_ship_parcelshop'],
                    ];
                    foreach ($shipping_methods as $key => [$label, $opt]):
                    ?>
                    <div style="padding:12px;border:1px solid #e2e8f0;border-radius:8px">
                        <div style="font-weight:600;font-size:13px;margin-bottom:8px"><?php echo $label; ?></div>
                        <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:8px;cursor:pointer">
                            <input type="checkbox" name="<?php echo $opt.'_enabled'; ?>" value="1"
                                   <?php checked(get_option('vesho_'.$opt.'_enabled','1'),'1'); ?>>
                            Aktiivne
                        </label>
                        <label class="crm-form-label" style="font-size:12px">Hind (€)</label>
                        <input class="crm-form-input" type="number" step="0.01" min="0"
                               name="<?php echo $opt.'_price'; ?>"
                               value="<?php echo esc_attr(get_option('vesho_'.$opt.'_price','0')); ?>"
                               style="max-width:90px;padding:5px 8px">
                        <small style="display:block;margin-top:2px;color:#6b8599;font-size:11px">0 = tasuta</small>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Kliendikaardi allahindlus (%)</label>
                        <input class="crm-form-input" type="number" step="0.1" min="0" max="100"
                               name="shop_loyalty_discount"
                               value="<?php echo esc_attr(get_option('vesho_shop_loyalty_discount','0')); ?>"
                               style="max-width:100px">
                        <small style="color:#6b8599;font-size:12px">Registreeritud klientidele automaatne allahindlus e-poes. 0 = keelatud.</small>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Müügitingimused (kuvatakse /muugitingimused lehel)</label>
                        <textarea class="crm-form-textarea" name="shop_terms" rows="6"
                                  style="font-size:12px"><?php echo esc_textarea(get_option('vesho_shop_terms','')); ?></textarea>
                        <small style="color:#6b8599;font-size:12px">Täistekst. Lingitakse jaluses automaatselt.</small>
                    </div>
                </div>

                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Sisu ──────────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="sisu">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">📝 Sisu seaded</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Teenuste lehe pealkiri</label>
                        <input class="crm-form-input" type="text" name="services_page_title"
                               value="<?php echo esc_attr($services_page_title); ?>"
                               placeholder="nt. Meie teenused">
                        <small style="color:#6b8599;font-size:12px">Kuvatakse teenuste lehe päises</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Teenuste lehe alapealkiri</label>
                        <input class="crm-form-input" type="text" name="services_page_subtitle"
                               value="<?php echo esc_attr($services_page_subtitle); ?>"
                               placeholder="nt. Professionaalne veemajandus">
                        <small style="color:#6b8599;font-size:12px">Väiksem tekst pealkirja all</small>
                    </div>
                </div>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Kliendi portaali seaded</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Portaali leht</label>
                        <?php wp_dropdown_pages(['name'=>'portal_page','selected'=>$portal_page,'show_option_none'=>'— Vali leht —','class'=>'crm-form-select']); ?>
                        <small style="color:#6b8599;font-size:12px">Shortcode: [vesho_client_portal]</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Sisselogimise leht</label>
                        <?php wp_dropdown_pages(['name'=>'login_page','selected'=>$login_page,'show_option_none'=>'— Vali leht —','class'=>'crm-form-select']); ?>
                        <small style="color:#6b8599;font-size:12px">Shortcode: [vesho_client_login]</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Portaali pealkiri</label>
                        <input class="crm-form-input" type="text" name="portal_title" value="<?php echo esc_attr($portal_title); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Sisselogimise alapealkiri</label>
                        <input class="crm-form-input" type="text" name="portal_login_sub"
                               value="<?php echo esc_attr(get_option('vesho_portal_login_sub','Logi sisse või registreeru uue kliendina')); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Registreerimine</label>
                        <select class="crm-form-select" name="portal_registration">
                            <option value="1" <?php selected($portal_registration,'1'); ?>>Lubatud</option>
                            <option value="0" <?php selected($portal_registration,'0'); ?>>Keelatud</option>
                        </select>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Tervitustekst</label>
                        <textarea class="crm-form-textarea" name="portal_welcome" rows="2"><?php echo esc_textarea($portal_welcome); ?></textarea>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Nähtavad tabid</label>
                        <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:6px">
                            <?php
                            $tabs = [
                                'portal_show_devices'      => 'Seadmed',
                                'portal_show_maintenances' => 'Hooldused',
                                'portal_show_services'     => 'Teenused (broneerimine)',
                                'portal_show_invoices'     => 'Arved',
                                'portal_show_support'      => 'Tugi / Piletid',
                                'show_contract_terms'      => 'Lepingutingimused (eraldi tab)',
                            ];
                            foreach ($tabs as $key => $label) :
                                $val = get_option('vesho_'.$key, '1');
                            ?>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                                <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php checked($val,'1'); ?>>
                                <?php echo $label; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Töötaja portaali seaded</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Töötaja portaali leht</label>
                        <?php wp_dropdown_pages(['name'=>'worker_page','selected'=>$worker_page,'show_option_none'=>'— Vali leht —','class'=>'crm-form-select']); ?>
                        <small style="color:#6b8599;font-size:12px">Shortcode: [vesho_worker_portal]</small>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Portaali link</label>
                        <div style="padding:8px 12px;background:#f0f7ff;border-radius:6px;font-family:monospace;font-size:13px">
                            <?php
                            $wp = get_page_by_path('worker');
                            echo $wp ? get_permalink($wp) : home_url('/worker/');
                            ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Õiguslikud tekstid</span></div>
                <div style="padding:20px">
                <table class="form-table">
                    <tr>
                        <th>Privaatsuspoliitika</th>
                        <td>
                            <textarea name="privacy_policy" rows="8" class="large-text"><?php echo esc_textarea(get_option('vesho_privacy_policy','')); ?></textarea>
                            <p class="description">Kuvatakse kliendipordaalis registreerimisel ja privaatsuspoliitika lehel.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Lepingutingimused (töökäsud)</th>
                        <td>
                            <textarea name="vesho_contract_terms" rows="6" class="large-text"><?php echo esc_textarea(get_option('vesho_contract_terms','')); ?></textarea>
                            <p class="description">Kuvatakse töökäsu kinnituse all ja lisatakse arvetele.</p>
                        </td>
                    </tr>
                </table>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Integratsioonid ───────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="integratsioonid">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🔗 Integratsioonid</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Google Analytics ID</label>
                        <input class="crm-form-input" type="text" name="ga_id"
                               value="<?php echo esc_attr(get_option('vesho_ga_id','')); ?>"
                               placeholder="G-XXXXXXXXXX">
                        <small style="color:#6b8599;font-size:12px">Kui täidetud, kuvatakse klientidele GDPR küpsiste banner</small>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">E-posti saatmine</label>
                        <div style="padding:10px 14px;background:#f0f7ff;border-radius:8px;font-size:13px;color:#1e3a8a">
                            📧 E-post saadetakse WordPressi vaikimisi <code>wp_mail()</code> kaudu. SMTP konfigureerimiseks kasuta pluginat nagu <strong>WP Mail SMTP</strong> või <strong>FluentSMTP</strong>.
                        </div>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Teavituste e-post</label>
                        <input class="crm-form-input" type="email" name="notify_email" value="<?php echo esc_attr(get_option('vesho_notify_email', get_option('admin_email'))); ?>">
                        <small style="color:#6b8599;font-size:12px">Kuhu saadetakse admin-teavitused</small>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Aktiivsed teavitused</label>
                        <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:6px">
                            <?php
                            $notifs = [
                                'notify_new_request'          => 'Uus päring (külastajalt)',
                                'notify_new_ticket'           => 'Uus tugipilet (kliendilt)',
                                'notify_invoice_paid'         => 'Arve märgitakse makstud',
                                'notify_new_client'           => 'Uus klient registreerub',
                                'notify_maintenance_reminder' => 'Hoolduseelne meeldetuletus',
                                'notify_low_stock'            => 'Madal laoseis (e-pood)',
                                'notify_worker_shift'         => 'Töötaja graafiku meeldetuletus',
                            ];
                            foreach ($notifs as $key => $label) :
                                $val = get_option('vesho_'.$key, '1');
                            ?>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                                <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php checked($val,'1'); ?>>
                                <?php echo $label; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">💳 Makseväravad</span></div>
                <div style="padding:20px">

                <div style="margin-bottom:16px;padding:12px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                    <div style="font-weight:600;font-size:13px;margin-bottom:10px">Stripe</div>
                    <div class="crm-form-grid">
                        <div class="crm-form-group">
                            <label class="crm-form-label">Publishable Key</label>
                            <input class="crm-form-input" type="text" name="stripe_pub_key"
                                   value="<?php echo esc_attr(get_option('vesho_stripe_pub_key','')); ?>"
                                   placeholder="pk_live_...">
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Secret Key</label>
                            <input class="crm-form-input" type="password" name="stripe_secret_key"
                                   value="<?php echo esc_attr(get_option('vesho_stripe_secret_key','')); ?>"
                                   placeholder="sk_live_...">
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Webhook Secret</label>
                            <input class="crm-form-input" type="password" name="stripe_webhook_secret"
                                   value="<?php echo esc_attr(get_option('vesho_stripe_webhook_secret','')); ?>"
                                   placeholder="whsec_...">
                        </div>
                        <div class="crm-form-group crm-form-full">
                            <label class="crm-form-label" style="font-weight:400;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="stripe_enabled" value="1"
                                       <?php checked(get_option('vesho_stripe_enabled','0'),'1'); ?>>
                                Stripe aktiivne
                            </label>
                        </div>
                        <div class="crm-form-group crm-form-full">
                            <label class="crm-form-label">Webhook URL (sisesta Stripe dashboardil)</label>
                            <div style="padding:7px 10px;background:#f0f7ff;border-radius:6px;font-family:monospace;font-size:12px">
                                <?php echo esc_url(rest_url('vesho/v1/stripe-webhook')); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;padding:12px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                    <div style="font-weight:600;font-size:13px;margin-bottom:10px">Maksekeskus</div>
                    <div class="crm-form-grid">
                        <div class="crm-form-group">
                            <label class="crm-form-label">Shop ID</label>
                            <input class="crm-form-input" type="text" name="mc_shop_id"
                                   value="<?php echo esc_attr(get_option('vesho_mc_shop_id','')); ?>">
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Salajane võti</label>
                            <input class="crm-form-input" type="password" name="mc_secret_key"
                                   value="<?php echo esc_attr(get_option('vesho_mc_secret_key','')); ?>">
                        </div>
                        <div class="crm-form-group crm-form-full" style="display:flex;gap:24px;flex-wrap:wrap">
                            <label class="crm-form-label" style="font-weight:400;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="mc_enabled" value="1"
                                       <?php checked(get_option('vesho_mc_enabled','0'),'1'); ?>>
                                Maksekeskus aktiivne
                            </label>
                            <label class="crm-form-label" style="font-weight:400;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="mc_sandbox" value="1"
                                       <?php checked(get_option('vesho_mc_sandbox','1'),'1'); ?>>
                                Testkeskkond (sandbox)
                            </label>
                        </div>
                        <div class="crm-form-group crm-form-full">
                            <label class="crm-form-label">Callback URL (sisesta Maksekeskuse halduspaneelil)</label>
                            <div style="padding:7px 10px;background:#f0f7ff;border-radius:6px;font-family:monospace;font-size:12px;user-select:all">
                                <?php echo esc_url(rest_url('vesho/v1/mc-notify')); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="padding:12px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                    <div style="font-weight:600;font-size:13px;margin-bottom:10px">Montonio</div>
                    <div class="crm-form-grid">
                        <div class="crm-form-group">
                            <label class="crm-form-label">Access Key</label>
                            <input class="crm-form-input" type="text" name="montonio_access_key"
                                   value="<?php echo esc_attr(get_option('vesho_montonio_access_key','')); ?>">
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Secret Key</label>
                            <input class="crm-form-input" type="password" name="montonio_secret_key"
                                   value="<?php echo esc_attr(get_option('vesho_montonio_secret_key','')); ?>">
                        </div>
                        <div class="crm-form-group crm-form-full" style="display:flex;gap:24px;flex-wrap:wrap">
                            <label class="crm-form-label" style="font-weight:400;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="montonio_enabled" value="1"
                                       <?php checked(get_option('vesho_montonio_enabled','0'),'1'); ?>>
                                Montonio aktiivne
                            </label>
                            <label class="crm-form-label" style="font-weight:400;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="montonio_sandbox" value="1"
                                       <?php checked(get_option('vesho_montonio_sandbox','1'),'1'); ?>>
                                Sandbox režiim (testimiseks)
                            </label>
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Return URL (sisesta Montonio halduspaneelil)</label>
                            <div style="padding:7px 10px;background:#f0f7ff;border-radius:6px;font-family:monospace;font-size:12px"><?php echo esc_url(home_url('/?vesho_montonio_return=1')); ?></div>
                        </div>
                        <div class="crm-form-group">
                            <label class="crm-form-label">Webhook URL (sisesta Montonio halduspaneelil)</label>
                            <div style="padding:7px 10px;background:#f0f7ff;border-radius:6px;font-family:monospace;font-size:12px"><?php echo esc_url(rest_url('vesho/v1/montonio-webhook')); ?></div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Küpsised ─────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="kupsised">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🍪 Küpsiste bänner</span></div>
                <div style="padding:20px">
                <table class="form-table">
                    <tr><th>Luba küpsiste bänner</th><td><label><input type="checkbox" name="cookie_banner_enabled" value="1" <?php checked(get_option('vesho_cookie_banner_enabled','1'),'1'); ?>> Näita küpsiste nõusoleku dialoogi</label></td></tr>
                    <tr><th>Bänneri pealkiri</th><td><input type="text" name="cookie_banner_title" value="<?php echo esc_attr(get_option('vesho_cookie_banner_title','Kasutame küpsiseid')); ?>" class="regular-text"></td></tr>
                    <tr><th>Bänneri tekst</th><td><textarea name="cookie_banner_text" rows="3" class="large-text"><?php echo esc_textarea(get_option('vesho_cookie_banner_text','Kasutame küpsiseid, et parandada kasutajakogemust ja analüüsida liiklust.')); ?></textarea></td></tr>
                    <tr><th>"Nõustun" nupu tekst</th><td><input type="text" name="cookie_accept_text" value="<?php echo esc_attr(get_option('vesho_cookie_accept_text','Nõustun kõigiga')); ?>" class="regular-text"></td></tr>
                    <tr><th>"Keeldu" nupu tekst</th><td><input type="text" name="cookie_reject_text" value="<?php echo esc_attr(get_option('vesho_cookie_reject_text','Ainult vajalikud')); ?>" class="regular-text"></td></tr>
                </table>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Süsteem ───────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="susteem">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🚧 Varsti tulekul / Saidi sulgemine</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group crm-form-full">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                            <input type="checkbox" name="coming_soon_mode" value="1"
                                   <?php checked(get_option('vesho_coming_soon_mode','0'),'1'); ?> style="accent-color:#f59e0b">
                            <span><strong>Lülita "Varsti tulekul" režiim sisse</strong> — kogu avalik sait kuvatakse ainult adminile, külastajatele näidatakse tulekul-lehte.</span>
                        </label>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Pealkiri</label>
                        <input class="crm-form-input" type="text" name="coming_soon_title"
                               value="<?php echo esc_attr(get_option('vesho_coming_soon_title','Varsti tulekul')); ?>">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Alampealkiri / sõnum</label>
                        <input class="crm-form-input" type="text" name="coming_soon_message"
                               value="<?php echo esc_attr(get_option('vesho_coming_soon_message','Töötame uue veebisaidi kallal. Peagi tagasi!')); ?>">
                    </div>
                </div>
                <?php if (get_option('vesho_coming_soon_mode','0') === '1') : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;color:#92400e;font-size:13px;font-weight:600">
                    🚧 "Varsti tulekul" režiim on AKTIIVNE — külastajad näevad tulekul-lehte
                </div>
                <?php endif; ?>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🔴 Portaali hooldusrežiim</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group crm-form-full">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                            <input type="checkbox" name="maintenance_mode" value="1"
                                   <?php checked(get_option('vesho_maintenance_mode','0'),'1'); ?> style="accent-color:#ef4444">
                            <span><strong>Lülita kliendipordaali hooldusrežiim sisse</strong> — portaalis kuvatakse teade "Hooldus käib". Admin näeb portaali ikka.</span>
                        </label>
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Hooldusrežiimi sõnum (kuvatakse klientidele)</label>
                        <input class="crm-form-input" type="text" name="maintenance_message"
                               value="<?php echo esc_attr(get_option('vesho_maintenance_message','Hooldus käib. Palume vabandust ebamugavuse pärast!')); ?>">
                    </div>
                </div>
                <?php if (get_option('vesho_maintenance_mode','0') === '1') : ?>
                <div style="margin-top:12px;padding:10px 14px;background:#fff0f0;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;font-size:13px;font-weight:600">
                    🔴 Portaali hooldusrežiim on praegu AKTIIVNE
                </div>
                <?php endif; ?>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">⏰ Automaatteavitused (Cron)</span></div>
                <div style="padding:20px">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Hoolduse meeldetuletus (päevi ette)</label>
                        <input class="crm-form-input" type="number" name="maintenance_reminder_days" min="0" max="30"
                               value="<?php echo esc_attr(get_option('vesho_maintenance_reminder_days','3')); ?>"
                               style="max-width:100px">
                        <small style="color:#6b8599;display:block;margin-top:4px">0 = keelatud. Vaikimisi 3 päeva.</small>
                    </div>
                    <div class="crm-form-group" style="display:flex;flex-direction:column;gap:10px;justify-content:flex-end">
                        <label class="crm-form-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400">
                            <input type="checkbox" name="low_stock_alert" value="1" <?php checked(get_option('vesho_low_stock_alert','1'),'1'); ?> style="accent-color:#00b4c8">
                            ⚠️ Madal laoseis → e-mail adminile (iga päev kell 08:00)
                        </label>
                        <label class="crm-form-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400">
                            <input type="checkbox" name="worker_reminder" value="1" <?php checked(get_option('vesho_worker_reminder','0'),'1'); ?> style="accent-color:#00b4c8">
                            📋 Töötaja meeldetuletus (1 päev enne töökäsku) — ainult kui <em>Tööemail</em> on täidetud
                        </label>
                    </div>
                </div>
                <p style="font-size:12px;color:#6b8599;margin-top:8px">
                    ⚙️ Ajastatud tööd käivitatakse WP-Cron abil. Teavitused saadetakse <em><?php echo esc_html(get_option('vesho_notify_email', get_option('admin_email'))); ?></em> (seadistatav Integratsioonide sektsioonis).
                </p>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">📍 Geofence GPS kellalöök</span></div>
                <div style="padding:20px">
                <p style="font-size:13px;color:#64748b;margin-bottom:14px">Kui raadius on seadistatud, kontrollitakse töötaja asukohta kellalöögi ajal. Söda koordinaatide leidmiseks ava Google Maps, paremklõpsa asukohal ja kopeeri koordinaadid.</p>
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Kontori laiuskraad (lat)</label>
                        <input class="crm-form-input" type="number" step="0.0000001" name="office_lat"
                               value="<?php echo esc_attr(get_option('vesho_office_lat','')); ?>" placeholder="nt 59.4370">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Kontori pikkuskraad (lng)</label>
                        <input class="crm-form-input" type="number" step="0.0000001" name="office_lng"
                               value="<?php echo esc_attr(get_option('vesho_office_lng','')); ?>" placeholder="nt 24.7536">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Lubatud raadius (meetrites, 0 = keelatud)</label>
                        <input class="crm-form-input" type="number" min="0" max="50000" name="geofence_radius"
                               value="<?php echo esc_attr(get_option('vesho_geofence_radius','0')); ?>" style="max-width:120px">
                    </div>
                    <div class="crm-form-group" style="display:flex;align-items:flex-end">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                            <input type="checkbox" name="geofence_warn_only" value="1"
                                   <?php checked(get_option('vesho_geofence_warn_only','1'),'1'); ?> style="accent-color:#f59e0b">
                            <span>Ainult hoiatus (ei blokeeri kellalööki)</span>
                        </label>
                    </div>
                </div>
                </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
              <div class="crm-card-header"><span class="crm-card-title">🔄 Update server</span></div>
              <div style="padding:20px">
                <p style="margin-bottom:8px;font-size:13px">Uuenduste haldus on saadaval: <a href="<?php echo admin_url('admin.php?page=vesho-crm-releases'); ?>">🚀 Uuendused</a></p>
                <p style="font-size:13px;color:#666">Update serveri URL: <code><?php echo esc_html(Vesho_CRM_Updater::get_server_url()); ?></code></p>
              </div>
            </div>

            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">Andmebaasi info</span></div>
                <div style="padding:20px">
                <?php
                global $wpdb;
                $tables = ['vesho_clients','vesho_devices','vesho_maintenances','vesho_invoices','vesho_workers','vesho_workorders','vesho_work_hours','vesho_inventory','vesho_price_list','vesho_services','vesho_guest_requests'];
                ?>
                <table class="crm-table" style="max-width:400px">
                    <thead><tr><th>Tabel</th><th>Kirjeid</th></tr></thead>
                    <tbody>
                    <?php foreach ($tables as $t) :
                        $cnt = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$t}");
                    ?>
                    <tr><td style="font-family:monospace;font-size:12px"><?php echo $wpdb->prefix.$t; ?></td><td><?php echo intval($cnt); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top:16px">
                    <strong>Plugin versioon:</strong> <?php echo VESHO_CRM_VERSION; ?><br>
                    <strong>DB versioon:</strong> <?php echo get_option('vesho_crm_version','–'); ?>
                </div>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

        <!-- ── TAB: Välimus ───────────────────────────────────────────── -->
        <div class="crm-tab-section" data-tab-content="valimus">
            <div class="crm-card" style="margin-bottom:20px">
                <div class="crm-card-header"><span class="crm-card-title">🎨 Välimus</span></div>
                <div style="padding:20px">
                <table class="form-table">
                    <tr>
                        <th>Ettevõtte logo</th>
                        <td>
                            <?php $logo = get_option('vesho_company_logo',''); ?>
                            <?php if($logo): ?><img src="<?php echo esc_url($logo); ?>" style="max-height:80px;display:block;margin-bottom:8px"><br><?php endif; ?>
                            <input type="text" name="company_logo" id="vesho_company_logo" value="<?php echo esc_url($logo); ?>" class="regular-text">
                            <button type="button" class="button" id="vesho_logo_upload_btn">Vali meediastt</button>
                            <p class="description">Logo kuvatakse arvetel ja kliendipordaalis.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Peamine värv</th>
                        <td><input type="color" name="primary_color" value="<?php echo esc_attr(get_option('vesho_primary_color','#00b4c8')); ?>"></td>
                    </tr>
                </table>
                </div>
            </div>
            <div class="crm-form-actions" style="justify-content:flex-start">
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta seaded</button>
            </div>
        </div>

    </form>


    <?php /* ── Notices — own form, OUTSIDE main settings form to avoid nested-form HTML bug ── */ ?>
    <div class="crm-card" style="margin-top:20px">
        <div class="crm-card-header">
            <span class="crm-card-title">📢 Portaali teated (ajastatud)</span>
        </div>
        <div style="padding:20px">
        <?php
        global $wpdb;
        $notices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_portal_notices ORDER BY starts_at DESC LIMIT 50");
        ?>
        <?php if (!empty($notices)) : ?>
        <table class="crm-table" style="margin-bottom:16px">
            <thead><tr><th>Pealkiri</th><th>Sihtkoht</th><th>Algab</th><th>Lõpeb</th><th>Aktiivne</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($notices as $n) : ?>
            <tr <?php if (!$n->active) echo 'style="opacity:.5"'; ?>>
                <td><strong><?php echo esc_html($n->title); ?></strong><br><small style="color:#6b8599"><?php echo esc_html(substr($n->message,0,60)); ?></small></td>
                <td><?php echo ['client'=>'Kliendid','worker'=>'Töötajad','both'=>'Mõlemad'][$n->target]??$n->target; ?></td>
                <td><?php echo $n->starts_at ? vesho_crm_format_date($n->starts_at) : '–'; ?></td>
                <td><?php echo $n->ends_at   ? vesho_crm_format_date($n->ends_at)   : '–'; ?></td>
                <td><?php echo $n->active ? '<span class="crm-badge badge-success">Jah</span>' : '<span class="crm-badge badge-gray">Ei</span>'; ?></td>
                <td>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_notice&notice_id='.$n->id),'vesho_delete_notice'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta teade?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <details <?php if (empty($notices)) echo 'open'; ?>>
            <summary style="cursor:pointer;font-weight:600;font-size:13px;margin-bottom:12px">+ Lisa uus teade</summary>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:12px">
                <?php wp_nonce_field('vesho_save_notice'); ?>
                <input type="hidden" name="action" value="vesho_save_notice">
                <div class="crm-form-grid">
                    <div class="crm-form-group">
                        <label class="crm-form-label">Pealkiri *</label>
                        <input class="crm-form-input" type="text" name="notice_title" required>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Tüüp</label>
                        <select class="crm-form-select" name="notice_type">
                            <option value="info">ℹ️ Info</option>
                            <option value="warning">⚠️ Hoiatus</option>
                            <option value="success">✅ Positiivne</option>
                        </select>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Sihtkoht</label>
                        <select class="crm-form-select" name="notice_target">
                            <option value="both">Mõlemad portaalid</option>
                            <option value="client">Ainult kliendid</option>
                            <option value="worker">Ainult töötajad</option>
                        </select>
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Alguskuupäev</label>
                        <input class="crm-form-input" type="date" name="notice_starts">
                    </div>
                    <div class="crm-form-group">
                        <label class="crm-form-label">Lõppkuupäev</label>
                        <input class="crm-form-input" type="date" name="notice_ends">
                    </div>
                    <div class="crm-form-group crm-form-full">
                        <label class="crm-form-label">Tekst *</label>
                        <textarea class="crm-form-textarea" name="notice_message" rows="2" required></textarea>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm">➕ Lisa teade</button>
                </div>
            </form>
        </details>
        </div>
    </div>
</div>
<script>
jQuery(function($){
    $('#vesho_logo_upload_btn').on('click',function(){
        var frame = wp.media({title:'Vali logo',multiple:false});
        frame.on('select',function(){
            var att = frame.state().get('selection').first().toJSON();
            $('#vesho_company_logo').val(att.url);
        });
        frame.open();
    });
});
</script>
<script>
document.querySelectorAll('.crm-tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.crm-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.crm-tab-section').forEach(function(s) { s.classList.remove('active'); });
        this.classList.add('active');
        var target = document.querySelector('[data-tab-content="' + this.dataset.tab + '"]');
        if (target) target.classList.add('active');
    });
});
// Activate tab from URL hash or tab_hash GET param
(function() {
    var hash = location.hash.replace('#', '');
    <?php if (!empty($_GET['tab_hash'])) echo "if(!hash) hash = " . json_encode(sanitize_text_field($_GET['tab_hash'])) . ";"; ?>
    if (hash) {
        var btn = document.querySelector('[data-tab="' + hash + '"]');
        if (btn) btn.click();
    }
})();
</script>
