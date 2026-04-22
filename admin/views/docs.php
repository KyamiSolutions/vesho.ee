<?php defined('ABSPATH') || exit;
$site_url   = get_site_url();
$admin_url  = admin_url();
$plugin_ver = VESHO_CRM_VERSION;
$theme_ver  = wp_get_theme()->get('Version');
$php_ver    = PHP_VERSION;
$db_ver     = $GLOBALS['wpdb']->db_version();
$wp_ver     = get_bloginfo('version');
$vat        = get_option('vesho_vat_rate', '24');
$php_ok     = version_compare($php_ver, '8.0', '>=');
$wp_ok      = version_compare($wp_ver,  '6.0', '>=');
?>
<style>
/* ── Reset inside WP admin ───────────────────────────────────────────────── */
#vesho-docs-root *{box-sizing:border-box;margin:0;padding:0}
#vesho-docs-root ul,#vesho-docs-root ol{list-style:none}
#vesho-docs-root a{text-decoration:none}

/* ── Root wrapper — pulls out of WP admin padding ───────────────────────── */
#vesho-docs-root{
  display:flex;
  margin:-10px -20px -10px -20px;
  min-height:calc(100vh - 32px);
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:#f8fafc;
}

/* ══ SIDEBAR ════════════════════════════════════════════════════════════════ */
.vd-sidebar{
  width:272px;
  flex-shrink:0;
  background:#0d1f2d;
  position:sticky;
  top:32px;
  height:calc(100vh - 32px);
  overflow-y:auto;
  display:flex;
  flex-direction:column;
  scrollbar-width:thin;
  scrollbar-color:rgba(255,255,255,.12) transparent;
}
.vd-sidebar::-webkit-scrollbar{width:4px}
.vd-sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:2px}

/* Logo / header */
.vd-logo{
  padding:22px 20px 18px;
  border-bottom:1px solid rgba(255,255,255,.07);
  flex-shrink:0;
}
.vd-logo-top{display:flex;align-items:center;gap:10px;margin-bottom:4px}
.vd-logo-icon{
  width:32px;height:32px;border-radius:8px;
  background:linear-gradient(135deg,#00b4c8,#0087a0);
  display:flex;align-items:center;justify-content:center;
  font-size:16px;flex-shrink:0;
}
.vd-logo-name{font-size:15px;font-weight:700;color:#fff;letter-spacing:-.2px}
.vd-logo-ver{font-size:11px;color:rgba(255,255,255,.35);margin-left:42px}

/* Search */
.vd-search{padding:12px 14px;flex-shrink:0}
.vd-search input{
  width:100%;padding:8px 12px 8px 34px;
  background:rgba(255,255,255,.07);
  border:1px solid rgba(255,255,255,.1);
  border-radius:6px;color:#fff;font-size:13px;outline:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='rgba(255,255,255,.4)' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:10px center;
}
.vd-search input::placeholder{color:rgba(255,255,255,.3)}
.vd-search input:focus{border-color:#00b4c8;background-color:rgba(0,180,200,.08)}

/* Nav */
.vd-nav{flex:1;padding:4px 0 32px}
.vd-nav-cat{
  font-size:10px;font-weight:700;letter-spacing:.1em;
  text-transform:uppercase;color:rgba(255,255,255,.28);
  padding:18px 20px 6px;
}
.vd-nav a{
  display:flex;align-items:center;gap:8px;
  padding:7px 20px;font-size:13px;
  color:rgba(255,255,255,.6);
  border-left:2px solid transparent;
  transition:all .12s;
  line-height:1.3;
}
.vd-nav a:hover{color:#fff;background:rgba(255,255,255,.05)}
.vd-nav a.active{
  color:#00b4c8;border-left-color:#00b4c8;
  background:rgba(0,180,200,.09);font-weight:600;
}
.vd-nav a .ni{font-size:14px;flex-shrink:0;opacity:.8}

/* ══ CONTENT ════════════════════════════════════════════════════════════════ */
.vd-content{
  flex:1;min-width:0;
  padding:44px 56px 100px;
  max-width:900px;
}

/* Section */
.vd-sec{margin-bottom:64px;scroll-margin-top:24px}
.vd-sec-title{
  font-size:28px;font-weight:800;color:#0f172a;
  border-bottom:2px solid #e2e8f0;
  padding-bottom:14px;margin-bottom:28px;
  display:flex;align-items:center;gap:12px;
  letter-spacing:-.5px;
}

/* Headings inside section */
.vd-h3{font-size:18px;font-weight:700;color:#1e293b;margin:36px 0 12px}
.vd-h4{font-size:14px;font-weight:700;color:#334155;margin:20px 0 8px;text-transform:uppercase;letter-spacing:.04em;font-size:12px}

/* Paragraph */
.vd-content p{font-size:14px;color:#475569;line-height:1.8;margin-bottom:14px}
.vd-content p:last-child{margin-bottom:0}

/* Inline code */
.vd-content code,.vc{
  background:#f1f5f9;border:1px solid #e2e8f0;
  border-radius:4px;padding:2px 7px;
  font-size:12px;color:#1e293b;
  font-family:'Courier New',Courier,monospace;
}

/* Code block */
.vd-pre{
  background:#1e293b;border-radius:8px;
  padding:18px 20px;margin:16px 0;overflow-x:auto;
}
.vd-pre code{
  background:none;border:none;color:#e2e8f0;
  font-size:13px;padding:0;white-space:pre;line-height:1.6;
}

/* Steps */
.vd-steps{margin:16px 0 24px}
.vd-step{display:flex;gap:16px;margin-bottom:18px}
.vd-step:last-child{margin-bottom:0}
.vd-step-n{
  width:28px;height:28px;border-radius:50%;
  background:#00b4c8;color:#fff;
  font-size:12px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;margin-top:3px;
}
.vd-step-b strong{font-size:14px;color:#1e293b;display:block;margin-bottom:4px;font-weight:700}
.vd-step-b p{margin:0;font-size:13px;color:#64748b;line-height:1.65}

/* Callouts */
.vd-note,.vd-warn,.vd-tip{
  border-radius:0 8px 8px 0;padding:14px 18px;
  margin:18px 0;font-size:13px;line-height:1.7;
}
.vd-note{background:#eff6ff;border-left:4px solid #3b82f6;color:#1e40af}
.vd-warn{background:#fffbeb;border-left:4px solid #f59e0b;color:#78350f}
.vd-tip {background:#f0fdf4;border-left:4px solid #22c55e;color:#14532d}
.vd-note b,.vd-warn b,.vd-tip b{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;opacity:.75}

/* Table */
.vd-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:13px}
.vd-table th{
  background:#f8fafc;text-align:left;
  padding:10px 14px;font-weight:700;
  color:#334155;border-bottom:2px solid #e2e8f0;
}
.vd-table td{
  padding:10px 14px;color:#475569;
  border-bottom:1px solid #f1f5f9;vertical-align:top;
}
.vd-table tr:last-child td{border-bottom:none}
.vd-table tr:hover td{background:#fafafa}

/* Badges */
.vb{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;gap:4px}
.vb-blue{background:#dbeafe;color:#1d4ed8}
.vb-green{background:#dcfce7;color:#166534}
.vb-yellow{background:#fef9c3;color:#854d0e}
.vb-red{background:#fee2e2;color:#991b1b}
.vb-gray{background:#f1f5f9;color:#475569}
.vb-cyan{background:#e0f7fa;color:#006d7a}

/* Grid */
.vd-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:16px 0}
.vd-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin:16px 0}
.vd-card{background:#fff;border:1px solid #e8eef2;border-radius:8px;padding:18px 20px}
.vd-card h4{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:8px}
.vd-card p{font-size:13px;color:#475569;margin:0;line-height:1.65}
.vd-card ul{margin:8px 0 0;padding-left:18px;list-style:disc;font-size:13px;color:#475569;line-height:1.9}

/* Portal cards */
.vd-portals{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin:20px 0}
.vd-portal-card{
  border:1px solid #e2e8f0;border-radius:10px;
  padding:24px 20px;text-align:center;
  color:inherit;transition:.15s;background:#fff;display:block;
}
.vd-portal-card:hover{border-color:#00b4c8;box-shadow:0 4px 16px rgba(0,180,200,.15);transform:translateY(-2px)}
.vd-portal-card .picon{font-size:36px;margin-bottom:12px;display:block}
.vd-portal-card h3{font-size:15px;font-weight:700;color:#1e293b;margin-bottom:6px}
.vd-portal-card p{font-size:13px;color:#64748b;margin-bottom:12px;line-height:1.5}

/* Flow */
.vd-flow{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:16px 0 24px;padding:16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0}
.vd-flow-step{background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:8px 14px;font-size:13px;font-weight:600;color:#1e293b}
.vd-flow-arrow{color:#94a3b8;font-size:20px;font-weight:300}

/* KPI grid */
.vd-kpi{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0}
.vd-kpi-item{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;display:flex;gap:12px;align-items:flex-start}
.vd-kpi-icon{font-size:22px;flex-shrink:0;margin-top:1px}
.vd-kpi-body strong{display:block;font-size:13px;color:#1e293b;margin-bottom:3px;font-weight:700}
.vd-kpi-body span{font-size:12px;color:#64748b;line-height:1.5}

/* Sys info */
.vd-sysinfo{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;margin:20px 0}
.vd-sys{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px}
.vd-sys .lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px}
.vd-sys .val{font-size:16px;font-weight:800;color:#0f172a;word-break:break-all;line-height:1.2}
.vd-sys .sub{font-size:11px;color:#94a3b8;margin-top:4px}
.vd-sys.ok .val{color:#166534}.vd-sys.warn .val{color:#92400e}.vd-sys.err .val{color:#991b1b}

/* Shortcode pill */
.sc{display:inline-block;background:#0d1f2d;color:#00b4c8;border-radius:6px;padding:3px 10px;font-family:monospace;font-size:12px;font-weight:600;letter-spacing:.3px}

/* Divider */
.vd-divider{border:none;border-top:1px solid #f1f5f9;margin:32px 0}

/* Responsive */
@media(max-width:960px){
  .vd-sidebar{display:none}
  .vd-content{padding:24px 20px 60px;max-width:100%}
  .vd-grid2,.vd-grid3,.vd-portals{grid-template-columns:1fr}
  .vd-kpi{grid-template-columns:1fr}
}

/* Search highlight */
.vd-search-hidden{display:none!important}
</style>

<div id="vesho-docs-root">

  <!-- ══ SIDEBAR ═══════════════════════════════════════════════════════════ -->
  <aside class="vd-sidebar">
    <div class="vd-logo">
      <div class="vd-logo-top">
        <div class="vd-logo-icon">💧</div>
        <div class="vd-logo-name">Vesho CRM</div>
      </div>
      <div class="vd-logo-ver">Kasutusjuhend · v<?php echo esc_html($plugin_ver); ?></div>
    </div>
    <div class="vd-search"><input type="text" id="vdSearchInput" placeholder="Otsi juhendist…"></div>
    <nav class="vd-nav" id="vdNav">

      <div class="vd-nav-cat">Alustamine</div>
      <a href="#ylevaade" class="vd-nav-link"><span class="ni">📖</span>Ülevaade</a>
      <a href="#nouded" class="vd-nav-link"><span class="ni">⚙️</span>Süsteemi nõuded</a>
      <a href="#sisselogimine" class="vd-nav-link"><span class="ni">🔑</span>Sisselogimine</a>

      <div class="vd-nav-cat">Admin paneel</div>
      <a href="#toolaud" class="vd-nav-link"><span class="ni">🏠</span>Töölaud</a>
      <a href="#kliendid" class="vd-nav-link"><span class="ni">👥</span>Kliendid</a>
      <a href="#seadmed" class="vd-nav-link"><span class="ni">🔧</span>Seadmed</a>
      <a href="#hooldused" class="vd-nav-link"><span class="ni">📅</span>Hooldused</a>
      <a href="#tookasud" class="vd-nav-link"><span class="ni">📋</span>Töökäsud</a>
      <a href="#tootajad" class="vd-nav-link"><span class="ni">👷</span>Töötajad</a>

      <div class="vd-nav-cat">Arveldus &amp; Ladu</div>
      <a href="#arved" class="vd-nav-link"><span class="ni">🧾</span>Arved ja PDF</a>
      <a href="#ladu" class="vd-nav-link"><span class="ni">📦</span>Ladu</a>
      <a href="#hinnakiri" class="vd-nav-link"><span class="ni">💰</span>Hinnakiri ja e-pood</a>

      <div class="vd-nav-cat">Portaalid</div>
      <a href="#tootaja-portaal" class="vd-nav-link"><span class="ni">👷</span>Töötaja portaal</a>
      <a href="#kliendi-portaal" class="vd-nav-link"><span class="ni">🌐</span>Kliendi portaal</a>

      <div class="vd-nav-cat">Veebileht</div>
      <a href="#elementor" class="vd-nav-link"><span class="ni">🎨</span>Elementor</a>
      <a href="#shortcodes" class="vd-nav-link"><span class="ni">〈/〉</span>Shortcode'id</a>

      <div class="vd-nav-cat">Seaded</div>
      <a href="#seaded" class="vd-nav-link"><span class="ni">⚙️</span>Üldseaded</a>
      <a href="#email-seaded" class="vd-nav-link"><span class="ni">📧</span>E-post ja teavitused</a>

      <div class="vd-nav-cat">Töövood</div>
      <a href="#toovood" class="vd-nav-link"><span class="ni">🔄</span>Tüüpilised töövood</a>

      <div class="vd-nav-cat">Süsteem</div>
      <a href="#uuendused" class="vd-nav-link"><span class="ni">🚀</span>Uuendused</a>
      <a href="#sysinfo" class="vd-nav-link"><span class="ni">🖥️</span>Süsteemi info</a>
    </nav>
  </aside>

  <!-- ══ CONTENT ════════════════════════════════════════════════════════════ -->
  <main class="vd-content">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- ÜLEVAADE -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="ylevaade">
      <h2 class="vd-sec-title"><span>📖</span>Ülevaade</h2>

      <p><strong>Vesho CRM</strong> on Vesho OÜ jaoks loodud terviklahendus veeseadmete hooldusäri juhtimiseks. Süsteem töötab otse teie WordPress veebilehel — eraldi tarkvara ei ole vaja. Kõik äriprotsessid ühes kohas: kliendid, seadmed, hooldusgraafik, töökäsud, töötajad, arved ja ladu.</p>

      <div class="vd-h3">Kolm portaali</div>
      <p>Süsteem koosneb kolmest eraldi kasutajaliidesest, millest igaüks on mõeldud erinevale kasutajale:</p>
      <div class="vd-portals">
        <a class="vd-portal-card" href="<?php echo esc_url($admin_url . 'admin.php?page=vesho-crm'); ?>" target="_blank">
          <span class="picon">🖥️</span>
          <h3>Admin paneel</h3>
          <p>Kogu äri haldamine — kliendid, arved, töökäsud, ladu, seaded. Ainult administraatoritele.</p>
          <code><?php echo esc_html($site_url); ?>/wp-admin/</code>
        </a>
        <a class="vd-portal-card" href="<?php echo esc_url($site_url . '/worker/'); ?>" target="_blank">
          <span class="picon">👷</span>
          <h3>Töötaja portaal</h3>
          <p>Mobiilisõbralik vaade töötajatele — töökäsud, hooldused, töötundide logimine.</p>
          <code><?php echo esc_html($site_url); ?>/worker/</code>
        </a>
        <a class="vd-portal-card" href="<?php echo esc_url($site_url . '/klient/'); ?>" target="_blank">
          <span class="picon">🌐</span>
          <h3>Kliendi portaal</h3>
          <p>Klient näeb oma seadmeid, hooldusi, arveid ja saab teenust tellida.</p>
          <code><?php echo esc_html($site_url); ?>/klient/</code>
        </a>
      </div>

      <div class="vd-h3">Põhifunktsioonid</div>
      <div class="vd-grid2">
        <div class="vd-card"><h4>👥 Kliendihaldus</h4><p>Eraisikud ja firmad, seadmed, portaali ligipääs, külaliste taotluste haldamine.</p></div>
        <div class="vd-card"><h4>📅 Hooldusplaneerimine</h4><p>Kalendrivaade, meeldetuletused, marsruudi optimeerimine, automaatsed tähtajad.</p></div>
        <div class="vd-card"><h4>📋 Töökäsud</h4><p>Töötajatele ülesannete määramine, staatuse jälgimine, lõpetamise kinnitamine.</p></div>
        <div class="vd-card"><h4>🧾 Arveldus</h4><p>PDF arved, KM-arvutus, makseseire, e-posti saatmine, pangaandmed.</p></div>
        <div class="vd-card"><h4>📦 Laohaldus</h4><p>Laoseis, miinimumkogused, kauba vastuvõtt, ostutellimused tarnijatele.</p></div>
        <div class="vd-card"><h4>💰 E-pood</h4><p>Toodete ja teenuste hinnakiri, kliendi portaali pood, kampaaniad.</p></div>
      </div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SÜSTEEMI NÕUDED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="nouded">
      <h2 class="vd-sec-title"><span>⚙️</span>Süsteemi nõuded</h2>

      <table class="vd-table">
        <thead><tr><th>Komponent</th><th>Miinimum</th><th>Soovitatav</th><th>Praegune</th></tr></thead>
        <tbody>
          <tr><td>WordPress</td><td>6.0</td><td>6.5+</td><td><?php echo $wp_ok ? '<span class="vb vb-green">✓ '.$wp_ver.'</span>' : '<span class="vb vb-red">✗ '.$wp_ver.'</span>'; ?></td></tr>
          <tr><td>PHP</td><td>8.0</td><td>8.2+</td><td><?php echo $php_ok ? '<span class="vb vb-green">✓ '.$php_ver.'</span>' : '<span class="vb vb-red">✗ '.$php_ver.'</span>'; ?></td></tr>
          <tr><td>MySQL / MariaDB</td><td>5.7 / 10.3</td><td>8.0+ / 10.6+</td><td><span class="vb vb-green">✓ <?php echo esc_html($db_ver); ?></span></td></tr>
          <tr><td>HTTPS</td><td>Kohustuslik</td><td>—</td><td>—</td></tr>
          <tr><td>Elementor</td><td>3.0 (veebilehe jaoks)</td><td>3.18+</td><td>—</td></tr>
        </tbody>
      </table>

      <div class="vd-tip"><b>Nõuanne</b>Hosta veebilehte serveril, mis toetab PHP 8.2+ — see tagab parima jõudluse ja turvalisuse.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SISSELOGIMINE -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="sisselogimine">
      <h2 class="vd-sec-title"><span>🔑</span>Sisselogimine</h2>

      <div class="vd-h3">Admin paneeli sisselogimine</div>
      <p>WordPress admin paneel asub aadressil <code><?php echo esc_html($site_url); ?>/wp-admin/</code>. Logi sisse oma WordPress administraatori kontoga. Pärast sisselogimist on vasakpoolses menüüs <strong>Vesho CRM</strong> sektsioon.</p>

      <div class="vd-h3">Töötaja portaali sisselogimine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Mine aadressile <code><?php echo esc_html($site_url); ?>/worker/</code></strong><p>Töötaja portaali sisselogimisleht avaneb automaatselt kui pole sisse logitud.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Sisesta e-posti aadress ja parool</strong><p>Sama e-post ja parool, mis saadeti töötaja lisamisel. Parool on muudetav portaali seadetes.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Mobiilis: lisa avakuvale</strong><p>Chrome'is ilmub aadressiribal ⊕ ikoon → "Lisa avakuvale" → töötab nagu äpp.</p></div></div>
      </div>

      <div class="vd-h3">Kliendi portaali sisselogimine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Mine aadressile <code><?php echo esc_html($site_url); ?>/klient/</code></strong><p>Kliendi portaali sisselogimisleht.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Sisesta e-posti aadress ja parool</strong><p>Parool saadeti emailiga kui admin klõpsis "🔑 Ligipääs" kliendi real.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Parooli unustamisel</strong><p>Kasuta "Unustasin parooli" linki — süsteem saadab uue parooli emailile. Või admin saab uue parooli genereerida CRM-ist.</p></div></div>
      </div>

      <div class="vd-note"><b>Oluline</b>Töötajad ja kliendid ei kasuta WordPress admin paneeli — neil on oma eraldi portaalid ülaltoodud aadressidel.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- TÖÖLAUD -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="toolaud">
      <h2 class="vd-sec-title"><span>🏠</span>Töölaud</h2>

      <p>Töölaud on esimene leht mida näed pärast sisselogimist. See annab reaalajas ülevaate kogu äri hetkeolukorrast — aktiivsed tööd, lähenevad hooldused, maksmata arved ja lao seisund.</p>

      <div class="vd-h3">KPI kaardid</div>
      <p>Töölaua ülaosas on neli põhinäitajat:</p>
      <div class="vd-kpi">
        <div class="vd-kpi-item"><div class="vd-kpi-icon">👥</div><div class="vd-kpi-body"><strong>Aktiivsed kliendid</strong><span>Kõigi aktiivsete klientide koguarv. Klõps viib klientide nimekirja.</span></div></div>
        <div class="vd-kpi-item"><div class="vd-kpi-icon">⚡</div><div class="vd-kpi-body"><strong>Töös praegu</strong><span>Hetkel "In progress" staatuses töökäsud. Klõps viib töökäskude lehele.</span></div></div>
        <div class="vd-kpi-item"><div class="vd-kpi-icon">🔧</div><div class="vd-kpi-body"><strong>Hooldusi täna</strong><span>Tänaseks planeeritud hoolduste arv. Klõps avab kalendri.</span></div></div>
        <div class="vd-kpi-item"><div class="vd-kpi-icon">🧾</div><div class="vd-kpi-body"><strong>Arved ootel</strong><span>Tasumata arvete arv ja kogusumma. Punane number = tähtaeg ületatud.</span></div></div>
      </div>

      <div class="vd-h3">Tänased hooldused</div>
      <p>Nimekiri tänaseks planeeritud hooldustest koos kliendi nime, seadme, määratud töötaja ja staatusega. Klõps real avab vastava hoolduse detailvaate.</p>

      <div class="vd-h3">Meeldetuletused</div>
      <p>Seadmed, millele on hooldus planeeritud järgmise 30 päeva jooksul (arvestatakse hooldusintervallist ja viimasest hoolduskuupäevast). Nimekirjas on kliendi nimi, seadme nimi ja päevad järgmise hoolduseni. Klõps meeldetuletuse ikoonil viib vastava kliendi juurde.</p>

      <div class="vd-h3">Tulude graafik</div>
      <p>Viimase 6 kuu kogutulud tulpdiagrammil. Hõljuta hiirega kuu peal — näed täpset summat. Arvestab ainult <strong>Tasutud</strong> staatuses arveid.</p>

      <div class="vd-h3">Laopuuduse hoiatused</div>
      <p>Kui mõne toote laoseis langeb alla seadistatud miinimumkoguse, ilmub töölaudale punane hoiatusbänner koos tootenimega. Klõps viib laohalduse lehele.</p>

      <div class="vd-h3">Kiirlingid päises</div>
      <p>Töölaua päises on kolm kiirnuppu kõige sagedasematele toimingutele:</p>
      <div class="vd-grid3">
        <div class="vd-card"><h4>+ Lisa klient</h4><p>Avab kliendi lisamise vormi otse töölaualt.</p></div>
        <div class="vd-card"><h4>+ Lisa hooldus</h4><p>Avab hoolduse planeerimise vormi.</p></div>
        <div class="vd-card"><h4>+ Lisa arve</h4><p>Avab uue arve koostamise.</p></div>
      </div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- KLIENDID -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="kliendid">
      <h2 class="vd-sec-title"><span>👥</span>Kliendid</h2>

      <div class="vd-h3">Kliendi lisamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Kliendid → "+ Lisa klient"</strong><p>Või kasuta töölaua kiirnuppu päises. Avaneb modaalaken kliendi andmetega.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali kliendi tüüp: Eraisik või Firma</strong><p>Firma valimisel ilmuvad lisaväljad: Registrikood ja KMKR number. Need kuvatakse automaatselt PDF arvedel.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Täida kohustuslikud väljad</strong><p>Nimi ja e-posti aadress on kohustuslikud — e-post on vajalik portaali ligipääsuks. Telefon ja aadress on valikulised aga soovitatavad.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Salvesta klient</strong><p>Klient ilmub nimekirja. Järgmise sammuna saad saata portaali ligipääsu.</p></div></div>
      </div>

      <div class="vd-h3">Klientide nimekirja filtreerimine</div>
      <p>Klientide nimekirjas saab filtreerida: <strong>Kõik / Eraisik / Firma</strong>. Firma klientide juures on 🏢 ikoon. Saad otsida klientide nimistu otsinguribaga (otsib nime, e-posti ja telefoni järgi).</p>

      <div class="vd-h3">Portaali ligipääsu saatmine</div>
      <p>Iga kliendi real on nupp <strong>🔑 Ligipääs</strong>. Vajutamisel süsteem:</p>
      <ul style="margin:12px 0 16px;padding-left:20px;list-style:disc;font-size:14px;color:#475569;line-height:2">
        <li>Genereerib juhusliku turvalise parooli</li>
        <li>Salvestab parooli krüpteeritult andmebaasi</li>
        <li>Saadab kliendile emaili portaali lingiga ja parooliga</li>
      </ul>
      <div class="vd-warn"><b>Tähelepanu</b>Parool on nähtav ainult emailis. Süsteem ei salvesta seda lihttekstina ega kuva hiljem. Kui klient kaotab parooli, loo uus klõpsates uuesti "Ligipääs".</div>

      <div class="vd-h3">Kliendi detailvaade</div>
      <p>Klõps kliendi nimel avab kliendi detailvaate vahekaartidega:</p>
      <table class="vd-table">
        <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
        <tbody>
          <tr><td><strong>Ülevaade</strong></td><td>Kokkuvõte — seadmete arv, avatud töökäsud, viimased hooldused, maksmata arved</td></tr>
          <tr><td><strong>Seadmed</strong></td><td>Kliendi seadmete nimekiri koos hooldusajaloo ja järgmise hoolduse kuupäevaga</td></tr>
          <tr><td><strong>Hooldused</strong></td><td>Kõik selle kliendi jaoks planeeritud ja tehtud hooldused</td></tr>
          <tr><td><strong>Töökäsud</strong></td><td>Kõik selle kliendiga seotud töökäsud</td></tr>
          <tr><td><strong>Arved</strong></td><td>Kliendi kõik arved, maksestaatused ja koguarvete summa</td></tr>
          <tr><td><strong>Märkmed</strong></td><td>Vabas vormis märkmed kliendi kohta (ainult adminile nähtav)</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Külaliste taotlused</div>
      <p>Kui veebilehel olev kontaktvorm täidetakse ilma sisselogimata (külaline), salvestab süsteem taotluse eraldi. Punane badge kliendide menüüs näitab uute taotluste arvu.</p>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Kliendid → vahekaart "👤 Külaliste taotlused"</strong><p>Näed kõiki sissetulnud taotlusi koos tähtajaga.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Ava taotlus — vaata detaile</strong><p>Nimi, e-post, telefon, seadme kirjeldus, soovitud kuupäev, sõnum.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Vajuta "Lisa kliendiks"</strong><p>Kliendi lisamise vorm avaneb eeltäidetud andmetega (nimi, e-post, telefon).</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Salvesta → saada portaali ligipääs → planeeri hooldus</strong><p>Külaline on nüüd klient ning saab portaali ligipääsu.</p></div></div>
      </div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SEADMED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="seadmed">
      <h2 class="vd-sec-title"><span>🔧</span>Seadmed</h2>

      <div class="vd-h3">Seadme lisamine kliendile</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava klient → vahekaart "Seadmed" → "+ Lisa seade"</strong><p>Avaneb seadme lisamise modaalaken.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida seadme andmed</strong><p>Kohustuslik: seadme nimi. Soovitatav: mudel, seerianumber, paigaldusaasta, asukoht.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Määra hooldusintervall (kuud)</strong><p>Nt 6 = hooldus iga 6 kuu tagant. Süsteem arvutab järgmise hoolduse kuupäeva automaatselt ja kuvab töölaua meeldetuletustes.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Lisa pilt (valikuline)</strong><p>Seadme foto — klient näeb seda oma portaalis seadme detailvaates.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Salvesta</strong><p>Seade ilmub kliendi seadmete nimekirja ja on kohe nähtav portaalis.</p></div></div>
      </div>

      <div class="vd-h3">Seadme hooldusajalugu</div>
      <p>Iga seadme detailvaates on täielik hooldusajalugu — kuupäev, töötaja, märkmed, järgmise hoolduse tähtaeg. Klient näeb sama ajalugu oma portaalis.</p>

      <div class="vd-tip"><b>Nõuanne</b>Lisa seadmele dokumendilingid (kasutusjuhend, paigaldussertifikaat) märkmete väljale — klient näeb neid portaalis ja saab soovi korral alla laadida.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- HOOLDUSED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="hooldused">
      <h2 class="vd-sec-title"><span>📅</span>Hooldused</h2>

      <div class="vd-h3">Hoolduse planeerimine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Hooldused → "+ Lisa hooldus"</strong><p>Või avaleht → kliki kuupäeval kalendris. Või kasuta töölaua kiirnuppu.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient</strong><p>Pärast kliendi valimist laadib süsteem automaatselt tema seadmed.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Vali seade</strong><p>Seadme valimisel täitub automaatselt: eelmine hoolduskuupäev, hooldusintervall, soovituslik järgmine hooldus.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Määra töötaja ja kuupäev/kellaaeg</strong><p>Töötaja näeb hooldust oma portaalis kohe pärast salvestamist.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Lisa kirjeldus (valikuline)</strong><p>Mida täpselt teha tuleb. Töötaja näeb seda portaalis.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">6</div><div class="vd-step-b"><strong>Salvesta</strong><p>Hooldus ilmub kalendrisse ja töölaua tänaste hoolduste nimekirja (kui kuupäev on täna).</p></div></div>
      </div>

      <div class="vd-h3">Hoolduse staatused</div>
      <table class="vd-table">
        <thead><tr><th>Staatus</th><th>Tähendus</th><th>Kes muudab</th></tr></thead>
        <tbody>
          <tr><td><span class="vb vb-blue">📋 Planeeritud</span></td><td>Hooldus on ajakavasse lisatud, töötajale määratud</td><td>Admin</td></tr>
          <tr><td><span class="vb vb-green">✅ Tehtud</span></td><td>Töötaja on hoolduse portaalis lõpetatuks märkinud. Kuupäev ja info salvestatakse ajalukku.</td><td>Töötaja / Admin</td></tr>
          <tr><td><span class="vb vb-gray">❌ Tühistatud</span></td><td>Hooldus ei toimu. Jääb logi jaoks alles, ei arvestata statistikas.</td><td>Admin</td></tr>
          <tr><td><span class="vb vb-red">🔴 Hilinenud</span></td><td>Planeeritud kuupäev on möödas ja staatus ei ole "Tehtud". Töölaud hoiatab.</td><td>Automaatne</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Meeldetuletused</div>
      <p>Vesho CRM → <strong>Meeldetuletused</strong> näitab kõiki seadmeid, millele hooldus on vajalik järgmise 30 päeva jooksul. Arvutus põhineb viimase hoolduse kuupäeval ja seadme hooldusintervalllil.</p>
      <div class="vd-note"><b>Kuidas arvutus töötab</b>Järgmise hoolduse kuupäev = viimase hoolduse kuupäev + hooldusintervall kuudes. Kui seadmel pole ühtegi hooldust tehtud, loetakse hooldus kohe vajalikuks.</div>

      <div class="vd-h3">Marsruudi planeerimine</div>
      <p>Vesho CRM → <strong>Marsruut</strong> — vali päev ja töötaja. Süsteem kuvab kaardil kõik selle päeva hoolduse asukohad ning soovitab optimaalse läbimise järjekorra. Kasulik kui ühel päeval on mitu klienti eri asukohtades.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- TÖÖKÄSUD -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="tookasud">
      <h2 class="vd-sec-title"><span>📋</span>Töökäsud</h2>

      <p>Töökäsk on konkreetne tööülesanne töötajale — seotud kliendi, seadme ja tähtajaga. Töötaja näeb töökäsku oma portaalis, muudab staatust ja märgib tehtuks.</p>

      <div class="vd-h3">Töökäsu loomine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töökäsud → "+ Lisa töökäsk"</strong><p>Avaneb uue töökäsu loomise vorm.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient ja seade</strong><p>Seade on valikuline — saab lisada ka ilma konkreetse seadmeta (nt üldine paigaldus).</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Määra töötaja ja prioriteet</strong><p>Prioriteedid: 🟢 Madal, 🟡 Keskmine, 🔴 Kõrge, 🆘 Kriitline. Kõrge prioriteet on portaalis esile tõstetud.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Määra tähtaeg ja kirjeldus</strong><p>Kirjelda täpselt mida teha tuleb. Töötaja näeb seda portaalis.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Salvesta</strong><p>Töötaja näeb töökäsku portaalis kohe. Saad saata ka e-posti teavituse.</p></div></div>
      </div>

      <div class="vd-h3">Staatuste töövoog</div>
      <div class="vd-flow">
        <div class="vd-flow-step">📋 Uus</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">⚙️ Määratud</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">🔄 Töös</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">✅ Lõpetatud</div>
      </div>

      <table class="vd-table">
        <thead><tr><th>Staatus</th><th>Tähendus</th></tr></thead>
        <tbody>
          <tr><td><span class="vb vb-gray">Uus</span></td><td>Töökäsk on loodud aga pole kellelegi määratud</td></tr>
          <tr><td><span class="vb vb-blue">Määratud</span></td><td>Töötajale määratud, töö pole veel alanud</td></tr>
          <tr><td><span class="vb vb-yellow">Töös</span></td><td>Töötaja on alustanud, töö käib</td></tr>
          <tr><td><span class="vb vb-green">Lõpetatud</span></td><td>Töötaja on tehtuks märkinud, admin saab kinnitada</td></tr>
        </tbody>
      </table>

      <div class="vd-tip"><b>Töötaja vaade</b>Lõpetamisel saab töötaja portaalis lisada: lõpetamise kommentaari, kasutatud materjalid laost, fotod tehtud tööst. Admin näeb kõiki neid andmeid töökäsu detailvaates.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- TÖÖTAJAD -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="tootajad">
      <h2 class="vd-sec-title"><span>👷</span>Töötajad</h2>

      <div class="vd-h3">Töötaja lisamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töötajad → "+ Lisa töötaja"</strong><p></p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida andmed: nimi, telefon, e-posti aadress</strong><p>E-post on kohustuslik — sellele saadetakse portaali ligipääs ja süsteem loob WordPressi kasutajakonto.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Salvesta</strong><p>Süsteem loob automaatselt WordPressi kasutaja rolliga <code>vesho_worker</code> ja saadab töötajale kutsemeili portaali ligipääsu lingiga.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Töötaja seab parooli</strong><p>Töötaja klõpsab emailis oleval lingil ja seab endale parooli. Seejärel saab ta sisse logida <code>/worker/</code> aadressil.</p></div></div>
      </div>

      <div class="vd-h3">Töötaja andmete muutmine</div>
      <p>Klõps töötaja nimel avab detailvaate kus saab muuta: nime, telefoni, e-posti, tunnitasu. Töötaja saab ise oma parooli muuta portaali seadetes.</p>

      <div class="vd-h3">Töötunnid</div>
      <p>Vesho CRM → <strong>Töötunnid</strong> — töötaja logib portaalis töö alguse ja lõpu aja. Admin näeb kõigi töötajate logitud tunnid siin kokkuvõtlikus vaates.</p>
      <div class="vd-grid2">
        <div class="vd-card"><h4>Raport perioodi kaupa</h4><p>Filtreeri kuupäevavahemiku järgi — näed iga töötaja töötunde ja arvutatud töötasu (tunnitasu × tunnid).</p></div>
        <div class="vd-card"><h4>Töötaja detailvaade</h4><p>Klikk töötajal näitab tema kõiki töökäske, hooldusi ja logitud töötunde ajalooliselt.</p></div>
      </div>

      <div class="vd-h3">Töötaja deaktiveerimine</div>
      <p>Töötaja lahkumise korral märgi töötaja <strong>Mitteaktiivseks</strong> — töötaja kaotab portaali ligipääsu aga tema ajaloolised andmed (töökäsud, hooldused, tunnid) jäävad alles.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- ARVED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="arved">
      <h2 class="vd-sec-title"><span>🧾</span>Arved ja PDF</h2>

      <div class="vd-tip"><b>KM-määr</b>Praegu kehtiv KM-määr on <strong><?php echo esc_html($vat); ?>%</strong>. Muutmiseks: <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm-settings')); ?>" style="color:#14532d;font-weight:600">Seaded → KM-määr</a>.</div>

      <div class="vd-h3">Arve koostamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Arved → "+ Lisa arve"</strong><p>Või töölaua kiirnupp. Avaneb arve koostamise leht.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient</strong><p>Firma klientide puhul täidetakse registrikood ja KMKR automaatselt PDF-i jaoks.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa arve read</strong><p>Iga rida: kirjeldus, kogus, ühiku hind (km-ta). KM (<?php echo esc_html($vat); ?>%) ja kogusumma arvutatakse automaatselt reaalajas.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Seosta töökäsk (valikuline)</strong><p>Saad seostada arve konkreetse töökäsuga — kasulik aruandluse jaoks.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Määra maksetähtaeg</strong><p>Vaikimisi 14 päeva tänasest. Tähtaeg on muudetav. Töölaud kuvab punase hoiatuse kui tähtaeg ületatud.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">6</div><div class="vd-step-b"><strong>Salvesta ja saada</strong><p>"Salvesta mustandina" = klient ei näe. "Salvesta ja saada e-postiga" = arve saadetakse kliendile emailile ja ilmub portaali.</p></div></div>
      </div>

      <div class="vd-h3">Arvete staatused</div>
      <table class="vd-table">
        <thead><tr><th>Staatus</th><th>Nähtav portaalis?</th><th>Arvestub tuludes?</th></tr></thead>
        <tbody>
          <tr><td><span class="vb vb-gray">📝 Mustand</span></td><td>Ei</td><td>Ei</td></tr>
          <tr><td><span class="vb vb-blue">📤 Saadetud</span></td><td>Jah</td><td>Ei</td></tr>
          <tr><td><span class="vb vb-green">✅ Tasutud</span></td><td>Jah</td><td>Jah</td></tr>
          <tr><td><span class="vb vb-red">🔴 Tähtaeg ületatud</span></td><td>Jah (punane)</td><td>Ei</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Arve printimine ja PDF</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava arve → kliki "🖨️ Prindi / PDF"</strong><p>Avaneb printimisvaade uues brauseriaknast.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali printer</strong><p>Füüsilise printimiseks vali printer. PDF loomiseks vali "Salvesta PDF-ina" (Chrome/Edge).</p></div></div>
      </div>
      <p>PDF-il kuvatakse automaatselt: firma logo, nimi, registrikood, KMKR, aadress, kliendi andmed, arve read KM-ga, kogusumma, maksetähtaeg, pangaandmed, selgitus. Kõik konfigureeritav: <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm-settings')); ?>" style="color:#00b4c8">Seaded</a>.</p>

      <div class="vd-h3">Arve märkimine tasututuks</div>
      <p>Kui makse laekub, ava arve ja vajuta <strong>"Märgi tasututuks"</strong>. Süsteem salvestab tasumise kuupäeva, arve liigub müügiraporti tulude hulka.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- LADU -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="ladu">
      <h2 class="vd-sec-title"><span>📦</span>Ladu</h2>

      <div class="vd-h3">Laoseis</div>
      <p>Vesho CRM → <strong>Ladu</strong> — kõik laokaubad koos koguse, ühiku, miinimumkoguse ja asukohaga. Punane rida tähendab, et kogus on alla miinimumi. Töölaud kuvab laopuuduse hoiatuse automaatselt.</p>

      <div class="vd-h3">Toote lisamine lattu</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Ladu → "+ Lisa toode"</strong><p></p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida: nimi, SKU, ühik, algkogus, miinimumkogus, asukoht</strong><p>Miinimumkogus käivitab automaatse laopuuduse hoiatuse töölauds.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Salvesta</strong><p>Toode ilmub laonimekirja. Kogust saab muuta vastuvõtu kaudu.</p></div></div>
      </div>

      <div class="vd-h3">Kauba vastuvõtt</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Vastuvõtt → "+ Uus vastuvõtt"</strong><p></p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali tarnija, lisa kaubad ja kogused</strong><p>Saad seostada ostutellimuse numbriga — see sulgeb vastava ostutellimuse automaatselt.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kinnita vastuvõtt</strong><p>Laokogused uuenevad automaatselt. Vastuvõtt salvestatakse ajalukku.</p></div></div>
      </div>

      <div class="vd-h3">Ostutellimused tarnijatele</div>
      <p>Vesho CRM → <strong>Ostutellimused</strong> — loo ostutellimus tarnijale kui ladu on tühjaks saamas. Saad eksportida PDF-ina ja saata tarnijale emailiga.</p>
      <div class="vd-note"><b>Tarnijad</b>Tarnijaid haldad: Vesho CRM → <strong>Tarnijad</strong>. Iga tarnija juures saab hoida: firma nimi, kontaktisik, telefon, e-post, tarneaeg päevades.</div>

      <div class="vd-h3">Materjalide kasutamine töökäskudel</div>
      <p>Töötaja saab portaalis töökäsku lõpetades märkida kasutatud materjalid laost koos kogustega. Laokogused vähenevad automaatselt. Admin näeb materjalide kasutust töökäsu detailvaates.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- HINNAKIRI -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="hinnakiri">
      <h2 class="vd-sec-title"><span>💰</span>Hinnakiri ja e-pood</h2>

      <p>Hinnakiri sisaldab nii teenuseid (tööd) kui müügikaupasid. Avaliku hinnakirja saab kuvada veebilehel, portaali poes saavad kliendid tooteid tellida.</p>

      <div class="vd-h3">Toote / teenuse lisamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Hinnakiri → "+ Lisa"</strong><p></p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida: nimi, kategooria, hind (km-ta), kirjeldus</strong><p>Kategooria võimaldab hinnakirja filtreerida nii veebilehel kui portaalis.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Vali nähtavuse seaded</strong><p>"Avalik veebilehel" = kuvatakse [vesho_price_list] shortcode'iga. "Müük portaalis" = klient saab portaalist tellida.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Lisa pilt (valikuline)</strong><p>Toote foto kuvatakse portaali poes.</p></div></div>
      </div>

      <div class="vd-h3">Hinnakiri veebilehel</div>
      <p>Lisa shortcode Elementori lehele:</p>
      <p><span class="sc">[vesho_price_list]</span> — kõik avalikud tooted/teenused</p>
      <p><span class="sc">[vesho_price_list category="hooldus"]</span> — filtreerib kategooria järgi</p>

      <div class="vd-h3">E-poe tellimused</div>
      <p>Klient saab portaali poes tooteid tellida. Tellimused ilmuvad: <strong>Vesho CRM → Tellimused</strong>.</p>
      <div class="vd-flow">
        <div class="vd-flow-step">🆕 Uus</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">⚙️ Töötlemisel</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">📦 Saadetud</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">✅ Lõpetatud</div>
      </div>

      <div class="vd-h3">Kampaaniad ja allahindlused</div>
      <p>Vesho CRM → <strong>Kampaaniad</strong> → Lisa allahindlus. Seo konkreetse tootega, määra allahindluse protsent ja kehtivusaeg (algus- ja lõppkuupäev). Klient näeb allahindlust portaali poes automaatselt.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- TÖÖTAJA PORTAAL -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="tootaja-portaal">
      <h2 class="vd-sec-title"><span>👷</span>Töötaja portaal</h2>

      <p>Töötaja portaal asub aadressil <a href="<?php echo esc_url($site_url . '/worker/'); ?>" target="_blank" style="color:#00b4c8;font-weight:600"><?php echo esc_html($site_url); ?>/worker/</a>. See on mobiilisõbralik liides töötajatele — ei nõua WordPress teadmisi.</p>

      <div class="vd-h3">Portaali vahekaardid</div>
      <table class="vd-table">
        <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
        <tbody>
          <tr><td><strong>Ülevaade</strong></td><td>Aktiivsete töökäskude arv, tänased töökäsud, lähimad hooldused</td></tr>
          <tr><td><strong>Töökäsud</strong></td><td>Kõik määratud töökäsud. Saab muuta staatust, lisada kommentaari, märkida materjale, lisada fotosid</td></tr>
          <tr><td><strong>Hooldused</strong></td><td>Tänased ja tulevased hooldused. Lõpetamisel märgib kuupäeva, lisab märkmed</td></tr>
          <tr><td><strong>Töötunnid</strong></td><td>Logib töö alguse ja lõpu aja. Kumulatiivne vaade selle kuu tundidest</td></tr>
          <tr><td><strong>Seaded</strong></td><td>Parooli muutmine, teavituste seaded</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Mobiilis avakuvale paigaldamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava portaal Chrome'is mobiilsel</strong><p>Mine aadressile <code><?php echo esc_html($site_url); ?>/worker/</code> ja logi sisse.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vajuta aadressiriba kõrval olevat ⊕ nuppu</strong><p>Või Chrome menüü (⋮) → "Lisa avakuvale" / "Install app".</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kinnita paigaldamine</strong><p>Portaali ikoon ilmub telefoni avakuvale. Avamine töötab nagu tavalist äppi.</p></div></div>
      </div>
      <div class="vd-tip"><b>PWA tugi</b>Portaal töötab progressiivse veebirakendusena (PWA) — täisekraanil, ilma brauseri riistadeta, kiire käivitusega.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- KLIENDI PORTAAL -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="kliendi-portaal">
      <h2 class="vd-sec-title"><span>🌐</span>Kliendi portaal</h2>

      <p>Kliendi portaal asub aadressil <a href="<?php echo esc_url($site_url . '/klient/'); ?>" target="_blank" style="color:#00b4c8;font-weight:600"><?php echo esc_html($site_url); ?>/klient/</a>. Klient haldab oma suhet Veshoga iseseisvalt — näeb seadmeid, arveid, hoolduste ajalugu.</p>

      <div class="vd-h3">Portaali vahekaardid</div>
      <table class="vd-table">
        <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
        <tbody>
          <tr><td><strong>Ülevaade</strong></td><td>Järgmine hooldus, tasumata arved, aktiivsed seadmed, viimaste hoolduste kokkuvõte</td></tr>
          <tr><td><strong>Seadmed</strong></td><td>Kõik kliendi seadmed — mudel, seerianumber, hooldusajalugu, järgmine hooldus</td></tr>
          <tr><td><strong>Hooldused</strong></td><td>Tulevased ja tehtud hooldused koos töötaja ja märkmetega</td></tr>
          <tr><td><strong>Arved</strong></td><td>Kõik arved koos staatuse, summa ja PDF allalaadimise võimalusega. Maksmata arved on punaselt esile tõstetud</td></tr>
          <tr><td><strong>Pood</strong></td><td>Toodete ja teenuste kataloog — klient saab tellida (kui "Müük portaalis" on aktiveeritud)</td></tr>
          <tr><td><strong>Minu tellimused</strong></td><td>E-poe tellimuste ajalugu ja staatused</td></tr>
          <tr><td><strong>Tugi</strong></td><td>Klient saab saata tugipileti — ilmub CRM-is: Vesho CRM → Tugipiletid</td></tr>
          <tr><td><strong>Broneeri</strong></td><td>Klient saab soovida hoolduse aega — ilmub CRM-is: Päringud</td></tr>
          <tr><td><strong>Seaded</strong></td><td>Parooli muutmine, kontaktandmete uuendamine</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Portaali bännerteade</div>
      <p>Vesho CRM → <strong>Portaali teated</strong> — lisa teade mis kuvatakse kõigile sisselogitud klientidele portaali ülaosas. Kasulik näiteks: "Hinnad muutuvad 1. maist", "Pühadel suletud 24.-26. dets". Saad ajastada: algus- ja lõppkuupäev.</p>

      <div class="vd-note"><b>E-poe tellimuste nägemine</b>Klient näeb oma e-poe tellimusi portaali vahekaardil "Minu tellimused". Tellimused seotakse kliendiga e-posti aadressi järgi — ka külalistellimused (enne kliendiks saamist) kuvatakse õige kliendi all.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- ELEMENTOR -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="elementor">
      <h2 class="vd-sec-title"><span>🎨</span>Veebileht ja Elementor</h2>

      <div class="vd-warn"><b>Enne suuri muutusi</b>Tee alati varukoopia! WordPress Admin → Tööriistad → Eksport. Või kasuta UpdraftPlus pluginat automaatseks varundamiseks.</div>

      <div class="vd-h3">Lehe redigeerimine Elementoriga</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>WordPress Admin → Lehed</strong><p>Leia leht mida soovid muuta.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Hõljuta hiirega lehel → "Redigeeri Elementoriga"</strong><p>Elementori redaktor avaneb uues vaates.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Klõpsa elemendil mida tahad muuta</strong><p>Vasakul paneel avab vastava elemendi seaded. Teksti saab muuta otse klõpsates.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Piltide muutmine</strong><p>Klõps pildil avab meediateegi — vali uus pilt või lae üles.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Klõpsa "Avalda" (roheline nupp üleval paremal)</strong><p>Muutused on kohe nähtavad veebilehe külastajatele.</p></div></div>
      </div>

      <div class="vd-h3">Lehed ja nende funktsioonid</div>
      <table class="vd-table">
        <thead><tr><th>Leht</th><th>URL</th><th>Märkused</th></tr></thead>
        <tbody>
          <tr><td><strong>Avaleht</strong></td><td><code>/</code></td><td>Turundusleht. Kõik muudetav Elementoriga. Statistikaarvud — klõpsa numbril ja muuda.</td></tr>
          <tr><td><strong>Meist</strong></td><td><code>/meist/</code></td><td>Firma tutvustus, meeskond, pildid.</td></tr>
          <tr><td><strong>Teenused</strong></td><td><code>/teenused/</code></td><td>Kasuta <span class="sc">[vesho_price_list]</span> hinnakirja kuvamiseks.</td></tr>
          <tr><td><strong>Pood</strong></td><td><code>/pood/</code></td><td>E-pood. Tooted hallatakse CRM-i hinnakirjas, mitte Elementoris.</td></tr>
          <tr><td><strong>Klient</strong></td><td><code>/klient/</code></td><td>⚠️ ÄRA MUUDA shortcode'i <span class="sc">[vesho_client_portal]</span>.</td></tr>
          <tr><td><strong>Töötaja</strong></td><td><code>/worker/</code></td><td>⚠️ ÄRA MUUDA shortcode'i <span class="sc">[vesho_worker_portal]</span>.</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Logo muutmine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>WordPress Admin → Välimus → Kohanda</strong><p></p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Saidi identiteet → Logo</strong><p>Lae üles uus pilt. Soovituslik: PNG läbipaistva taustaga, ligikaudu 200×60 pikslit.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Klõpsa "Avalda"</strong><p>Logo uueneb kohe nii päises kui mobiilmenüüs.</p></div></div>
      </div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SHORTCODES -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="shortcodes">
      <h2 class="vd-sec-title"><span>〈/〉</span>Shortcode'id</h2>

      <p>Vesho CRM lisab WordPress shortcode'id mida saab kasutada mis tahes lehel, postituses või Elementori "Shortcode" widgetis.</p>

      <table class="vd-table">
        <thead><tr><th>Shortcode</th><th>Kirjeldus</th></tr></thead>
        <tbody>
          <tr><td><span class="sc">[vesho_client_portal]</span></td><td>Kliendi portaal. Lisata lehe <code>/klient/</code> sisusse. Ära muuda.</td></tr>
          <tr><td><span class="sc">[vesho_worker_portal]</span></td><td>Töötaja portaal. Lisata lehe <code>/worker/</code> sisusse. Ära muuda.</td></tr>
          <tr><td><span class="sc">[vesho_price_list]</span></td><td>Kõik avalikud tooted ja teenused hinnakirjast.</td></tr>
          <tr><td><span class="sc">[vesho_price_list category="hooldus"]</span></td><td>Filtreeritud hinnakiri kategooria järgi.</td></tr>
          <tr><td><span class="sc">[vesho_shop]</span></td><td>E-poe kaupade kuvamine avalikul lehel.</td></tr>
        </tbody>
      </table>

      <div class="vd-tip"><b>Elementoris kasutamine</b>Elementori vasakpaneelist otsi "Shortcode" widget → lohista lehele → sisesta shortcode väljale. Muutused nähtavad kohe eelvaates.</div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SEADED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="seaded">
      <h2 class="vd-sec-title"><span>⚙️</span>Üldseaded</h2>

      <p>Vesho CRM → <strong>Seaded</strong>. Muutused mõjutavad kõiki PDF-e, e-kirju ja portaale. Salvesta alati pärast muutmist.</p>

      <div class="vd-grid2">
        <div class="vd-card">
          <h4>🏢 Firma andmed</h4>
          <ul>
            <li>Firma nimi ja lühinimi</li>
            <li>Registrikood ja KMKR number</li>
            <li>Aadress, telefon, e-post, koduleht</li>
            <li>Kuvatakse PDF arvedel ja e-kirjades</li>
          </ul>
        </div>
        <div class="vd-card">
          <h4>💶 KM-määr</h4>
          <ul>
            <li>Praegu: <strong><?php echo esc_html($vat); ?>%</strong></li>
            <li>Mõjutab kõiki uusi arve ridu</li>
            <li>Vanad arved jäävad muutumatuks</li>
            <li>Muutmisel uueneb ka portaali poe hind</li>
          </ul>
        </div>
        <div class="vd-card">
          <h4>🖼️ Logo PDF-il</h4>
          <ul>
            <li>Eraldi logo PDF arvedele</li>
            <li>Soovitus: PNG läbipaistva taustaga</li>
            <li>Ideaalne suurus: 300×100 px</li>
          </ul>
        </div>
        <div class="vd-card">
          <h4>🔢 Arve nummerdamine</h4>
          <ul>
            <li>Prefiks: nt <code>VESHO-2024-</code></li>
            <li>Algnumber: millest alustada</li>
            <li>Määra enne esimese arve loomist!</li>
          </ul>
        </div>
        <div class="vd-card">
          <h4>💳 Pangaandmed</h4>
          <ul>
            <li>IBAN konto number</li>
            <li>Panga nimi</li>
            <li>Selgituse tekst (nt "Arve nr {nr}")</li>
            <li>Kuvatakse PDF arve allosas</li>
          </ul>
        </div>
        <div class="vd-card">
          <h4>📋 Portaali teated</h4>
          <ul>
            <li>Bänner kliendi portaali avale</li>
            <li>Seadistatav algus- ja lõppkuupäev</li>
            <li>Nähtav kõigile sisselogitud klientidele</li>
          </ul>
        </div>
      </div>

      <div class="vd-h3">Administraatorid</div>
      <p>Vesho CRM → <strong>Administraatorid</strong> — anna CRM ligipääs teisele kasutajale ilma täieliku WordPress administraatori õiguseta. WordPress roll: <code>vesho_crm_admin</code>. See kasutaja näeb kõiki CRM vaateid aga ei saa muuta WordPress teema- või pluginate seadeid.</p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- E-POST -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="email-seaded">
      <h2 class="vd-sec-title"><span>📧</span>E-post ja teavitused</h2>

      <div class="vd-h3">SMTP seadistamine</div>
      <p>Vaikimisi kasutab WordPress server emaili saatmiseks, mis võib sattuda rämpsposti. Soovitame seadistada SMTP:</p>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Seaded → E-posti seaded</strong><p>Täida: SMTP server, port, kasutajanimi, parool, saatja nimi ja e-post.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Testi saatmist</strong><p>"Saada testmeil" nupp kontrollib kas seaded toimivad.</p></div></div>
      </div>

      <div class="vd-h3">Automaatsed teavitused</div>
      <table class="vd-table">
        <thead><tr><th>Sündmus</th><th>Saaja</th><th>Saadetakse automaatselt?</th></tr></thead>
        <tbody>
          <tr><td>Portaali ligipääsu genereerimine</td><td>Klient</td><td>Jah</td></tr>
          <tr><td>Arve saatmine</td><td>Klient</td><td>Admin vajutab "Saada"</td></tr>
          <tr><td>Töötaja kutse</td><td>Töötaja</td><td>Jah (lisamisel)</td></tr>
          <tr><td>Töökäsu määramine</td><td>Töötaja</td><td>Valikuline</td></tr>
          <tr><td>Tugipileti vastus</td><td>Klient</td><td>Jah</td></tr>
        </tbody>
      </table>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- TÖÖVOOD -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="toovood">
      <h2 class="vd-sec-title"><span>🔄</span>Tüüpilised töövood</h2>

      <div class="vd-h3">🆕 Uus klient → esimene hooldus → arve</div>
      <div class="vd-flow">
        <div class="vd-flow-step">1. Lisa klient</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">2. Lisa seade</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">3. Saada portaali ligipääs</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">4. Planeeri hooldus</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">5. Töötaja teeb töö</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">6. Koosta arve</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">7. Saada kliendile</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">8. Märgi tasututuks</div>
      </div>

      <div class="vd-h3">📩 Veebilehelt tulev hinnapäring</div>
      <div class="vd-flow">
        <div class="vd-flow-step">1. Päring saabub</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">2. CRM → Külaliste taotlused</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">3. "Lisa kliendiks"</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">4. Saada portaali ligipääs</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">5. Planeeri hooldus</div>
      </div>

      <div class="vd-h3">🔔 Igakuine hoolduste planeerimine</div>
      <div class="vd-flow">
        <div class="vd-flow-step">1. Töölaud → Meeldetuletused</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">2. Vali seadmed nimekirjast</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">3. Lisa hooldused kalendrisse</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">4. Marsruut optimeerib</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">5. Töötajad saavad teavituse</div>
      </div>

      <div class="vd-h3">📦 Laopuudus → ostutellimus → vastuvõtt</div>
      <div class="vd-flow">
        <div class="vd-flow-step">1. Töölaud hoiatab</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">2. Ladu → vaata puuduolevad</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">3. Loo ostutellimus</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">4. Kaup saabub → Vastuvõtt</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">5. Laoseis uueneb</div>
      </div>

      <div class="vd-h3">🔧 Kiire töökäsk (rikke korral)</div>
      <div class="vd-flow">
        <div class="vd-flow-step">1. Lisa töökäsk (prioriteet: Kõrge)</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">2. Määra töötaja</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">3. Töötaja saab teavituse</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">4. Töötaja märgib tehtuks</div><div class="vd-flow-arrow">→</div>
        <div class="vd-flow-step">5. Koosta arve</div>
      </div>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- UUENDUSED -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="uuendused">
      <h2 class="vd-sec-title"><span>🚀</span>Uuendused</h2>

      <div class="vd-tip"><b>Paigaldatud versioon</b>Vesho CRM <strong>v<?php echo esc_html($plugin_ver); ?></strong> · Teema <strong>v<?php echo esc_html($theme_ver); ?></strong></div>

      <div class="vd-h3">CRM pistikprogrammi uuendamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → 🚀 Uuendused</strong><p>Süsteem kontrollib kas uus versioon on saadaval. Muudatuste logi näitab mida parandati.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Klõpsa "Uuenda pistikprogrammi"</strong><p>Süsteem laeb alla uue versiooni GitHubist ja paigaldab automaatselt. Alla 10 sekundit.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kontrolli versiooni</strong><p>Uuenduste lehe allosas on paigaldatud versioon — veendu et number on muutunud.</p></div></div>
      </div>

      <div class="vd-h3">Teema uuendamine</div>
      <p>Teema uueneb samal lehel (<strong>Vesho CRM → Uuendused</strong>) paralleelselt pistikprogrammiga. Protsess on identne.</p>

      <div class="vd-h3">WordPress'i uuendamine</div>
      <div class="vd-steps">
        <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Tee varukoopia enne uuendust</strong><p>UpdraftPlus → "Varunda nüüd" → oota kuni valmis.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>WordPress Admin → Tööriistad → Uuendused</strong><p>Järjekord: esmalt WordPress core, seejärel pistikprogrammid, lõpuks teemad.</p></div></div>
        <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kontrolli veebilehte</strong><p>Ava avalik leht, kliendi portaal ja töötaja portaal — veendu et kõik töötab.</p></div></div>
      </div>
      <div class="vd-warn"><b>Ettevaatust</b>Ära uuenda WordPress'i kohe kui uus versioon ilmub — oota 1–2 nädalat. Selle ajaga leitakse ja parandatakse võimalikud vead uues versioonis.</div>

      <div class="vd-h3">Uuendus ei ilmu?</div>
      <p>WordPress puhverdab uuenduste kontrolli tulemused. Tühjenda puhver: mine aadressile <a href="<?php echo esc_url(admin_url('update-core.php?force-check=1')); ?>" style="color:#00b4c8"><?php echo esc_html($admin_url); ?>update-core.php?force-check=1</a></p>
    </section>

    <hr class="vd-divider">

    <!-- ─────────────────────────────────────────────────────────────────── -->
    <!-- SÜSTEEMI INFO -->
    <!-- ─────────────────────────────────────────────────────────────────── -->
    <section class="vd-sec" id="sysinfo">
      <h2 class="vd-sec-title"><span>🖥️</span>Süsteemi info</h2>

      <p>Reaalajas süsteemi olekuteave. Kasulik kui pead võtma ühendust tugiga või kontrollima serverinõuete vastavust.</p>

      <div class="vd-sysinfo">
        <div class="vd-sys">
          <div class="lbl">Vesho CRM</div>
          <div class="val">v<?php echo esc_html($plugin_ver); ?></div>
          <div class="sub">Pistikprogramm</div>
        </div>
        <div class="vd-sys">
          <div class="lbl">Teema</div>
          <div class="val">v<?php echo esc_html($theme_ver); ?></div>
          <div class="sub">Vesho theme</div>
        </div>
        <div class="vd-sys <?php echo $wp_ok ? 'ok' : 'warn'; ?>">
          <div class="lbl">WordPress</div>
          <div class="val">v<?php echo esc_html($wp_ver); ?></div>
          <div class="sub"><?php echo $wp_ok ? 'Nõue täidetud' : 'Soovitatav uuendada'; ?></div>
        </div>
        <div class="vd-sys <?php echo $php_ok ? 'ok' : 'err'; ?>">
          <div class="lbl">PHP</div>
          <div class="val"><?php echo esc_html($php_ver); ?></div>
          <div class="sub"><?php echo $php_ok ? 'Nõue täidetud' : '⚠ Vaja PHP 8.0+'; ?></div>
        </div>
        <div class="vd-sys">
          <div class="lbl">MySQL / MariaDB</div>
          <div class="val"><?php echo esc_html($db_ver); ?></div>
          <div class="sub">Andmebaasi versioon</div>
        </div>
        <div class="vd-sys">
          <div class="lbl">KM-määr</div>
          <div class="val"><?php echo esc_html($vat); ?>%</div>
          <div class="sub">Kehtiv käibemaks</div>
        </div>
        <div class="vd-sys">
          <div class="lbl">Veebileht</div>
          <div class="val" style="font-size:13px"><?php echo esc_html($site_url); ?></div>
          <div class="sub">Site URL</div>
        </div>
        <div class="vd-sys">
          <div class="lbl">Admin URL</div>
          <div class="val" style="font-size:13px"><?php echo esc_html($admin_url); ?></div>
          <div class="sub">WordPress admin</div>
        </div>
      </div>

      <div class="vd-h3">PHP seaded</div>
      <table class="vd-table">
        <thead><tr><th>Seade</th><th>Praegune väärtus</th><th>Soovituslik</th></tr></thead>
        <tbody>
          <?php
          $mem = ini_get('memory_limit');
          $upload = ini_get('upload_max_filesize');
          $post = ini_get('post_max_size');
          $maxExec = ini_get('max_execution_time');
          ?>
          <tr><td>memory_limit</td><td><?php echo esc_html($mem); ?></td><td>256M+</td></tr>
          <tr><td>upload_max_filesize</td><td><?php echo esc_html($upload); ?></td><td>32M+</td></tr>
          <tr><td>post_max_size</td><td><?php echo esc_html($post); ?></td><td>64M+</td></tr>
          <tr><td>max_execution_time</td><td><?php echo esc_html($maxExec); ?>s</td><td>120s+</td></tr>
        </tbody>
      </table>

      <div class="vd-h3">Aktiivsed pistikprogrammid</div>
      <?php
      $active_plugins = get_option('active_plugins', []);
      $plugin_data_list = [];
      foreach ($active_plugins as $plugin_file) {
          $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
          if (!empty($plugin_info['Name'])) {
              $plugin_data_list[] = ['name' => $plugin_info['Name'], 'version' => $plugin_info['Version']];
          }
      }
      ?>
      <table class="vd-table">
        <thead><tr><th>Pistikprogramm</th><th>Versioon</th></tr></thead>
        <tbody>
          <?php foreach ($plugin_data_list as $pl): ?>
          <tr><td><?php echo esc_html($pl['name']); ?></td><td><?php echo esc_html($pl['version']); ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

  </main><!-- .vd-content -->
</div><!-- #vesho-docs-root -->

<script>
(function(){
  /* ── Scroll spy ── */
  var links = document.querySelectorAll('.vd-nav-link');
  var sections = document.querySelectorAll('.vd-sec[id]');
  function onScroll(){
    var y = window.scrollY + 80;
    var cur = null;
    sections.forEach(function(s){
      if(y >= s.offsetTop) cur = s.id;
    });
    links.forEach(function(l){
      var href = l.getAttribute('href');
      l.classList.toggle('active', href === '#' + cur);
    });
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();

  /* ── Smooth scroll ── */
  links.forEach(function(l){
    l.addEventListener('click', function(e){
      var id = l.getAttribute('href').slice(1);
      var el = document.getElementById(id);
      if(el){ e.preventDefault(); el.scrollIntoView({behavior:'smooth'}); }
    });
  });

  /* ── Search ── */
  var inp = document.getElementById('vdSearchInput');
  if(inp){
    inp.addEventListener('input', function(){
      var q = inp.value.trim().toLowerCase();
      links.forEach(function(l){
        if(!q){ l.classList.remove('vd-search-hidden'); return; }
        var txt = l.textContent.toLowerCase();
        l.classList.toggle('vd-search-hidden', !txt.includes(q));
      });
      /* Hide category labels with no visible items */
      document.querySelectorAll('.vd-nav-cat').forEach(function(cat){
        var next = cat.nextElementSibling;
        var hasVisible = false;
        while(next && !next.classList.contains('vd-nav-cat')){
          if(!next.classList.contains('vd-search-hidden')) hasVisible = true;
          next = next.nextElementSibling;
        }
        cat.style.display = hasVisible ? '' : 'none';
      });
    });
  }

  /* ── Active item scroll into view on load ── */
  var activeLink = document.querySelector('.vd-nav-link.active');
  if(activeLink) activeLink.scrollIntoView({block:'nearest'});
})();
</script>
