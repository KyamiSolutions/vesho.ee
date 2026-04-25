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
$gid_set    = !empty(get_option('vesho_google_client_id',''));
$gsec_set   = !empty(get_option('vesho_google_client_secret',''));
$google_ok  = $gid_set && $gsec_set;
?>
<style>
#vd*{box-sizing:border-box;margin:0;padding:0}
#vdWrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;background:#f1f5f9;margin:-10px -20px -10px;min-height:100vh}

/* ── Header ── */
#vdHeader{background:#0d1f2d;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px}
#vdHeader h1{font-size:17px;font-weight:700;color:#fff;letter-spacing:-.3px;display:flex;align-items:center;gap:10px}
#vdHeader h1 span:first-child{background:#00b4c8;border-radius:6px;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
.vd-hver{font-size:11px;font-weight:600;background:rgba(0,180,200,.18);color:#00b4c8;border-radius:4px;padding:2px 8px;letter-spacing:.3px}
.vd-hback{font-size:12px;font-weight:600;color:rgba(255,255,255,.5);text-decoration:none;transition:.15s;display:flex;align-items:center;gap:5px}
.vd-hback:hover{color:#fff}

/* ── Tab nav ── */
#vdTabs{background:#fff;border-bottom:1px solid #e2e8f0;position:sticky;top:32px;z-index:99;overflow-x:auto;scrollbar-width:none;white-space:nowrap;padding:0 24px;display:flex;gap:2px}
#vdTabs::-webkit-scrollbar{display:none}
.vd-tab{display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:44px;font-size:12px;font-weight:600;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;text-decoration:none;white-space:nowrap;transition:.15s}
.vd-tab:hover{color:#1e293b}
.vd-tab.active{color:#00b4c8;border-bottom-color:#00b4c8}
.vd-tab-icon{font-size:14px}

/* ── Body ── */
#vdBody{display:flex;gap:0;max-width:1280px;margin:0 auto;padding:32px 32px 80px}

/* ── TOC sidebar ── */
#vdToc{width:200px;flex-shrink:0;position:sticky;top:calc(32px + 44px + 20px);max-height:calc(100vh - 120px);overflow-y:auto;margin-left:32px;order:2;scrollbar-width:none}
#vdToc::-webkit-scrollbar{display:none}
.vd-toc-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:8px;padding:0 4px}
#vdToc a{display:block;padding:6px 8px;font-size:12px;color:#64748b;text-decoration:none;border-left:2px solid transparent;border-radius:0 4px 4px 0;transition:.12s;line-height:1.4}
#vdToc a:hover{color:#1e293b;background:#f8fafc}
#vdToc a.active{color:#00b4c8;border-left-color:#00b4c8;background:#f0fdff;font-weight:600}

/* ── Main content ── */
#vdMain{flex:1;min-width:0;order:1}

/* ── Panel ── */
.vd-panel{display:none}
.vd-panel.show{display:block}

/* ── Section ── */
.vd-sec{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:24px 28px;margin-bottom:16px;scroll-margin-top:100px}
.vd-sec-h{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px}
.vd-sec-h .ic{width:26px;height:26px;border-radius:6px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.vd-sec-h .ic.cyan{background:#e0f7fa}

/* ── Typography ── */
.vd-p{font-size:13px;color:#475569;line-height:1.75;margin-bottom:12px}
.vd-p:last-child{margin-bottom:0}
.vd-h3{font-size:13px;font-weight:700;color:#0f172a;margin:20px 0 8px;text-transform:uppercase;letter-spacing:.05em}
.vd-h3:first-child{margin-top:0}
code,.vc{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 6px;font-size:11px;color:#1e293b;font-family:monospace}

/* ── Steps ── */
.vd-steps{margin:12px 0}
.vd-step{display:flex;gap:12px;margin-bottom:12px}
.vd-step:last-child{margin-bottom:0}
.vd-step-n{width:22px;height:22px;border-radius:50%;background:#00b4c8;color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.vd-step-b strong{font-size:13px;color:#0f172a;display:block;margin-bottom:2px;font-weight:600}
.vd-step-b p{margin:0;font-size:12px;color:#64748b;line-height:1.6}

/* ── Grid ── */
.vd-g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:12px 0}
.vd-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:12px 0}
.vd-g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:12px 0}
.vd-c{background:#f8fafc;border:1px solid #e8eef2;border-radius:8px;padding:14px 16px}
.vd-c h4{font-size:12px;font-weight:700;color:#0f172a;margin-bottom:6px}
.vd-c p{font-size:12px;color:#475569;margin:0;line-height:1.6}
.vd-c ul{margin:6px 0 0;padding-left:14px;list-style:disc;font-size:12px;color:#475569;line-height:1.85}

/* ── Portal cards ── */
.vd-portals{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:12px 0}
.vd-pc{border:1px solid #e2e8f0;border-radius:8px;padding:18px 16px;text-align:center;color:inherit;text-decoration:none;display:block;transition:.15s;background:#fff}
.vd-pc:hover{border-color:#00b4c8;box-shadow:0 2px 12px rgba(0,180,200,.12);transform:translateY(-1px)}
.vd-pc .pi{font-size:28px;display:block;margin-bottom:8px}
.vd-pc h3{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:4px}
.vd-pc p{font-size:11px;color:#64748b;margin-bottom:8px;line-height:1.5}

/* ── Callouts ── */
.vd-note,.vd-warn,.vd-tip{border-radius:0 6px 6px 0;padding:10px 14px;margin:12px 0;font-size:12px;line-height:1.7}
.vd-note{background:#eff6ff;border-left:3px solid #3b82f6;color:#1e40af}
.vd-warn{background:#fffbeb;border-left:3px solid #f59e0b;color:#78350f}
.vd-tip{background:#f0fdf4;border-left:3px solid #22c55e;color:#14532d}
.vd-note b,.vd-warn b,.vd-tip b{font-size:10px;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:3px;opacity:.7}

/* ── Table ── */
.vd-t{width:100%;border-collapse:collapse;margin:12px 0;font-size:12px}
.vd-t th{background:#f8fafc;text-align:left;padding:8px 12px;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
.vd-t td{padding:9px 12px;color:#475569;border-bottom:1px solid #f1f5f9;vertical-align:top}
.vd-t tr:last-child td{border-bottom:none}
.vd-t tr:hover td{background:#fafafa}

/* ── Badges ── */
.vb{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.vb-b{background:#dbeafe;color:#1d4ed8}.vb-g{background:#dcfce7;color:#166534}
.vb-y{background:#fef9c3;color:#854d0e}.vb-r{background:#fee2e2;color:#991b1b}
.vb-gray{background:#f1f5f9;color:#475569}.vb-c{background:#e0f7fa;color:#006d7a}

/* ── Flow ── */
.vd-flow{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:12px 0;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px}
.vd-fs{background:#fff;border:1px solid #e2e8f0;border-radius:5px;padding:6px 12px;font-size:11px;font-weight:600;color:#1e293b}
.vd-fa{color:#cbd5e1;font-size:16px;font-weight:700}

/* ── Shortcode pill ── */
.sc{display:inline-block;background:#1e293b;color:#00b4c8;border-radius:4px;padding:2px 8px;font-family:monospace;font-size:11px;font-weight:600}

/* ── Sys info ── */
.vd-si{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin:12px 0}
.vd-si-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px}
.vd-si-item .lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:4px}
.vd-si-item .val{font-size:14px;font-weight:800;color:#0f172a;word-break:break-all}
.vd-si-item .sub{font-size:10px;color:#94a3b8;margin-top:2px}
.vd-si-item.ok .val{color:#166534}.vd-si-item.err .val{color:#991b1b}

/* ── Changelog ── */
.vd-cl-item{border-left:3px solid #00b4c8;padding:10px 14px;margin-bottom:8px;background:#f0fdff;border-radius:0 6px 6px 0}
.vd-cl-item h4{font-size:12px;font-weight:700;color:#0f172a;margin-bottom:4px}
.vd-cl-item ul{margin:0;padding-left:14px;list-style:disc;font-size:12px;color:#475569;line-height:1.8}

/* ── Responsive ── */
@media(max-width:1100px){#vdToc{display:none}#vdBody{padding:20px 16px 60px}}
@media(max-width:700px){.vd-g2,.vd-g3,.vd-g4,.vd-portals{grid-template-columns:1fr}.vd-sec{padding:16px}}
</style>

<div id="vdWrap">

<!-- ── HEADER ── -->
<div id="vdHeader">
  <h1><span>💧</span>Vesho CRM — Kasutusjuhend <span class="vd-hver">v<?php echo esc_html($plugin_ver); ?></span></h1>
  <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm')); ?>" class="vd-hback">← Töölaud</a>
</div>

<!-- ── TABS ── -->
<div id="vdTabs">
  <a class="vd-tab active" data-panel="alustamine" href="#"><span class="vd-tab-icon">📖</span>Alustamine</a>
  <a class="vd-tab" data-panel="admin" href="#"><span class="vd-tab-icon">🖥️</span>Admin paneel</a>
  <a class="vd-tab" data-panel="arveldus" href="#"><span class="vd-tab-icon">🧾</span>Arveldus &amp; Ladu</a>
  <a class="vd-tab" data-panel="portaalid" href="#"><span class="vd-tab-icon">🌐</span>Portaalid</a>
  <a class="vd-tab" data-panel="veebileht" href="#"><span class="vd-tab-icon">🎨</span>Veebileht</a>
  <a class="vd-tab" data-panel="seaded" href="#"><span class="vd-tab-icon">⚙️</span>Seaded</a>
  <a class="vd-tab" data-panel="toovood" href="#"><span class="vd-tab-icon">🔄</span>Töövood</a>
  <a class="vd-tab" data-panel="uuendused" href="#"><span class="vd-tab-icon">🚀</span>Uuendused</a>
  <a class="vd-tab" data-panel="susteem" href="#"><span class="vd-tab-icon">🖥️</span>Süsteem</a>
</div>

<!-- ── BODY ── -->
<div id="vdBody">
<div id="vdMain">

<!-- ════════════════════════════════════════════════════════ ALUSTAMINE -->
<div class="vd-panel show" id="panel-alustamine">

  <div class="vd-sec" id="ylevaade">
    <div class="vd-sec-h"><span class="ic cyan">📖</span>Ülevaade</div>
    <p class="vd-p"><strong>Vesho CRM</strong> on Vesho OÜ jaoks loodud terviklahendus veeseadmete hooldusäri juhtimiseks — kliendid, seadmed, hooldusgraafik, töökäsud, arved ja ladu ühes kohas.</p>
    <div class="vd-h3">Kolm portaali</div>
    <div class="vd-portals">
      <a class="vd-pc" href="<?php echo esc_url($admin_url.'admin.php?page=vesho-crm'); ?>" target="_blank">
        <span class="pi">🖥️</span><h3>Admin paneel</h3>
        <p>Kogu äri haldamine — kliendid, arved, töökäsud, seaded</p>
        <code>/wp-admin/</code>
      </a>
      <a class="vd-pc" href="<?php echo esc_url($site_url.'/worker/'); ?>" target="_blank">
        <span class="pi">👷</span><h3>Töötaja portaal</h3>
        <p>Mobiilisõbralik liides töötajatele — töökäsud, hooldused, tunnid</p>
        <code>/worker/</code>
      </a>
      <a class="vd-pc" href="<?php echo esc_url($site_url.'/klient/'); ?>" target="_blank">
        <span class="pi">🌐</span><h3>Kliendi portaal</h3>
        <p>Klient näeb seadmeid, arveid, hooldusi ja saab tellida</p>
        <code>/klient/</code>
      </a>
    </div>
    <div class="vd-h3">Põhifunktsioonid</div>
    <div class="vd-g3">
      <div class="vd-c"><h4>Kliendihaldus</h4><p>Eraisikud ja firmad, seadmed, portaali ligipääs, külaliste taotlused</p></div>
      <div class="vd-c"><h4>Hooldusplaneerimine</h4><p>Kalender, meeldetuletused, marsruudi optimeerimine, automaatsed tähtajad</p></div>
      <div class="vd-c"><h4>Töökäsud</h4><p>Ülesanded töötajatele, staatuse jälgimine, materjalide kasutamine</p></div>
      <div class="vd-c"><h4>Arveldus</h4><p>PDF arved, KM-arvutus, makseseire, e-posti saatmine</p></div>
      <div class="vd-c"><h4>Laohaldus</h4><p>Laoseis, kauba vastuvõtt, ostutellimused tarnijatele</p></div>
      <div class="vd-c"><h4>E-pood</h4><p>Hinnakiri, kliendi portaali pood, kampaaniad</p></div>
    </div>
  </div>

  <div class="vd-sec" id="nouded">
    <div class="vd-sec-h"><span class="ic">⚙️</span>Süsteemi nõuded</div>
    <table class="vd-t">
      <thead><tr><th>Komponent</th><th>Miinimum</th><th>Soovitatav</th><th>Praegune</th></tr></thead>
      <tbody>
        <tr><td>WordPress</td><td>6.0</td><td>6.5+</td><td><?php echo $wp_ok?'<span class="vb vb-g">✓ '.esc_html($wp_ver).'</span>':'<span class="vb vb-r">✗ '.esc_html($wp_ver).'</span>'; ?></td></tr>
        <tr><td>PHP</td><td>8.0</td><td>8.2+</td><td><?php echo $php_ok?'<span class="vb vb-g">✓ '.esc_html($php_ver).'</span>':'<span class="vb vb-r">✗ '.esc_html($php_ver).'</span>'; ?></td></tr>
        <tr><td>MySQL / MariaDB</td><td>5.7 / 10.3</td><td>8.0+ / 10.6+</td><td><span class="vb vb-g">✓ <?php echo esc_html($db_ver); ?></span></td></tr>
        <tr><td>HTTPS</td><td colspan="2">Kohustuslik (Google OAuth nõuab)</td><td>—</td></tr>
      </tbody>
    </table>
  </div>

  <div class="vd-sec" id="sisselogimine">
    <div class="vd-sec-h"><span class="ic">🔑</span>Sisselogimine</div>

    <div class="vd-h3">Admin paneel</div>
    <p class="vd-p">Mine <code><?php echo esc_html($site_url); ?>/wp-admin/</code> — logi sisse WordPress administraatori kontoga. Vasakpoolses menüüs ilmub <strong>Vesho CRM</strong> sektsioon.</p>

    <div class="vd-h3">Töötaja portaal — 3 viisi</div>
    <div class="vd-g3">
      <div class="vd-c">
        <h4>🔢 Nimi + PIN-kood</h4>
        <ul>
          <li>Sisesta täisnimi (vali nimekirjast)</li>
          <li>Sisesta 6-kohaline PIN-kood</li>
          <li>PINi annab administraator töötaja lisamisel</li>
          <li>PIN saab muuta: Töötajad → muuda</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>📷 QR-kaart skannimine</h4>
        <ul>
          <li>Vajuta "Skänni töötaja QR kaart"</li>
          <li>Hoia töötaja QR-koodi kaamera ette</li>
          <li>Logi koheselt sisse — ei tarvitse PINi</li>
          <li>QR-kaart: Töötajad → töötaja → "Prindi QR"</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>🔵 Google konto</h4>
        <ul>
          <li>Vajuta "Jätka Google'iga"</li>
          <li>Google konto e-post peab vastama töötaja e-postile CRM-is</li>
          <li>Ainult kui seadetes aktiveeritud (Seaded → Google Login)</li>
        </ul>
      </div>
    </div>
    <div class="vd-tip"><b>Mobiili soovitus</b>Chrome → <code>/worker/</code> → aadressiribal ⊕ → "Lisa avakuvale" — töötab nagu äpp, täisekraanil, ilma brauseri ribata.</div>

    <div class="vd-h3">Kliendi portaal — 2 viisi</div>
    <div class="vd-g2">
      <div class="vd-c">
        <h4>📧 E-post + parool</h4>
        <ul>
          <li>Mine <code><?php echo esc_html($site_url); ?>/klient/</code></li>
          <li>Kasuta e-posti ja parooli</li>
          <li>Parool saadeti emailiga kui admin vajutas "🔑 Ligipääs"</li>
          <li>Unustasid parooli? Admin genereerib uue</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>🔵 Google konto</h4>
        <ul>
          <li>Vajuta "Jätka Google'iga" portaali lehel</li>
          <li>Või kliki "G" ikoonil peasaidi päise nupul "Portaal"</li>
          <li>Google konto e-post peab olema sama mis portaali konto e-post</li>
          <li>Ainult kui seadetes aktiveeritud</li>
        </ul>
      </div>
    </div>
    <div class="vd-note"><b>NB</b>Töötajad ja kliendid ei kasuta wp-admin paneeli — neil on eraldi portaalid. Google loginupp ilmub automaatselt kui Seaded → Google Login on seadistatud.</div>
  </div>

</div><!-- /panel-alustamine -->

<!-- ══════════════════════════════════════════════════════ ADMIN PANEEL -->
<div class="vd-panel" id="panel-admin">

  <div class="vd-sec" id="toolaud">
    <div class="vd-sec-h"><span class="ic cyan">🏠</span>Töölaud</div>
    <p class="vd-p">Esimene leht pärast sisselogimist — reaalajas ülevaade kogu ärist.</p>
    <div class="vd-h3">KPI kaardid</div>
    <div class="vd-g4">
      <div class="vd-c"><h4>👥 Aktiivsed kliendid</h4><p>Koguarv. Klõps → klientide nimekiri.</p></div>
      <div class="vd-c"><h4>⚡ Töös praegu</h4><p>"In progress" töökäsud. Klõps → töökäsud.</p></div>
      <div class="vd-c"><h4>🔧 Hooldusi täna</h4><p>Tänased planeeritud hooldused. Klõps → kalender.</p></div>
      <div class="vd-c"><h4>🧾 Arved ootel</h4><p>Tasumata arved + summa. Punane = tähtaeg ületatud.</p></div>
    </div>
    <div class="vd-h3">Töölaua muud elemendid</div>
    <div class="vd-g3">
      <div class="vd-c"><h4>Tänased hooldused</h4><p>Nimekiri tänaseks planeeritud hooldustest — klient, seade, töötaja, staatus.</p></div>
      <div class="vd-c"><h4>Meeldetuletused</h4><p>Seadmed mille hooldus tuleb järgmise 30 päeva jooksul (arvestatakse intervallist).</p></div>
      <div class="vd-c"><h4>Tulud (graafik)</h4><p>Viimase 6 kuu tasutud arvetest tulud tulpdiagrammil. Hiirega üle → täpne summa.</p></div>
      <div class="vd-c"><h4>Ootavad broneeringud</h4><p>Klientide hooldussoovid — kinnita ✓ või lükka tagasi ✕ otse töölaual.</p></div>
      <div class="vd-c"><h4>Nädalatunnid</h4><p>Töötajate selle nädala töötunnid jooksvalt.</p></div>
      <div class="vd-c"><h4>Laopuuduse hoiatus</h4><p>Punane bänner kui mõne kauba kogus on alla miinimumi.</p></div>
    </div>
  </div>

  <div class="vd-sec" id="kliendid">
    <div class="vd-sec-h"><span class="ic cyan">👥</span>Kliendid</div>
    <div class="vd-h3">Kliendi lisamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Kliendid → "+ Lisa klient"</strong><p>Avaneb modaalaken.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali tüüp: Eraisik või Firma</strong><p>Firma puhul ilmuvad lisaväljad: registrikood ja KMKR — kuvatakse PDF arvedel.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Täida nimi, e-post, telefon, aadress</strong><p>E-post on vajalik portaali ligipääsuks.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Salvesta → vajuta "🔑 Ligipääs"</strong><p>Süsteem genereerib parooli ja saadab kliendile emailiga. Klient saab kohe sisse logida.</p></div></div>
    </div>
    <div class="vd-warn"><b>Tähelepanu</b>Parool kuvatakse ainult emailis — süsteem ei salvesta seda lihttekstina. Uue parooli saad alati uuesti genereerida kliendi lehel.</div>
    <div class="vd-h3">Kliendi detailvaate vahekaardid</div>
    <table class="vd-t">
      <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
      <tbody>
        <tr><td>Ülevaade</td><td>Seadmete arv, avatud töökäsud, maksmata arved, kiirtegevused</td></tr>
        <tr><td>Seadmed</td><td>Seadmete nimekiri, hooldusajalugu, järgmine hooldus, lisamine</td></tr>
        <tr><td>Hooldused</td><td>Kõik planeeritud ja tehtud hooldused, staatuse filtreerimine</td></tr>
        <tr><td>Töökäsud</td><td>Kliendiga seotud töökäsud, prioriteedid, staatused</td></tr>
        <tr><td>Arved</td><td>Kõik arved, maksestaatused, PDF allalaadimine</td></tr>
        <tr><td>Märkmed</td><td>Vabas vormis sisemised märkmed (ainult adminile nähtav)</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Külaliste taotlused</div>
    <p class="vd-p">Kui külaline täidab veebilehel kontaktvormi, ilmub taotlus <strong>Kliendid → 👤 Külaliste taotlused</strong>. Sealt saad ühe klõpsuga lisada külalise kliendiks — kõik andmed täituvad automaatselt.</p>
    <div class="vd-tip"><b>Kiirotsing</b>Klientide otsinguväli otsib automaatselt kirjutades — nimi, e-post, telefon, registrikood. Ei pea nuppu vajutama.</div>
  </div>

  <div class="vd-sec" id="seadmed">
    <div class="vd-sec-h"><span class="ic cyan">🔧</span>Seadmed</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava klient → Seadmed → "+ Lisa seade"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida: nimi, mudel, seerianumber, paigaldusaasta</strong><p>Seadme tüüp aitab filtreerida ja otsida.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Määra hooldusintervall (kuud)</strong><p>Süsteem arvutab järgmise hoolduse kuupäeva automaatselt. Kui viimane hooldus pole tehtud, hakkab loendus paigaldusaastast.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Lisa pilt (valikuline)</strong><p>Pilt kuvatakse kliendi portaalis seadme kaardil. Ideaalne formaat: JPG, kuni 2MB.</p></div></div>
    </div>
    <div class="vd-tip"><b>Nõuanne</b>Lisa seadmele dokumendid ja garantiiinfo märkmete väljale — klient näeb neid portaalis seadme all.</div>
  </div>

  <div class="vd-sec" id="hooldused">
    <div class="vd-sec-h"><span class="ic cyan">📅</span>Hooldused</div>
    <div class="vd-h3">Hoolduse planeerimine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Kalender → "+ Lisa hooldus"</strong><p>Või kliki kalendris konkreetsel kuupäeval.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient → seade → töötaja → kuupäev</strong><p>Seadme valimisel täitub automaatselt eelmine hoolduskuupäev ja soovitatav järgmine.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa märkused ja hinnanguline aeg</strong><p>Töötaja näeb märkusi portaalis.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Salvesta</strong><p>Töötaja näeb hooldust portaalis kohe. Teavituse saatmine: Seaded → Teavitused.</p></div></div>
    </div>
    <div class="vd-h3">Staatused</div>
    <div class="vd-g4">
      <div class="vd-c"><h4><span class="vb vb-b">Planeeritud</span></h4><p>Ajakavasse lisatud, töötajale määratud.</p></div>
      <div class="vd-c"><h4><span class="vb vb-g">Tehtud</span></h4><p>Töötaja on lõpetatuks märkinud.</p></div>
      <div class="vd-c"><h4><span class="vb vb-gray">Tühistatud</span></h4><p>Ei toimu, jääb logi jaoks alles.</p></div>
      <div class="vd-c"><h4><span class="vb vb-r">Hilinenud</span></h4><p>Tähtaeg möödas, pole tehtud.</p></div>
    </div>
    <div class="vd-h3">Kiire lõpetamine kalendrist</div>
    <p class="vd-p">Kliki kalendris hooldusel → avaneb modal → vajuta <strong>✓ Tehtud</strong> — hooldus märgitakse tehtuks ja seadme järgmine hoolduskuupäev uueneb automaatselt. Ei pea avama hoolduse muutmislehte.</p>
  </div>

  <div class="vd-sec" id="tookasud">
    <div class="vd-sec-h"><span class="ic cyan">📋</span>Töökäsud</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töökäsud → "+ Lisa töökäsk"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient, seade, töötaja, prioriteet, tähtaeg</strong><p>Prioriteedid: <span class="vb vb-gray">Madal</span> <span class="vb vb-b">Keskmine</span> <span class="vb vb-y">Kõrge</span> <span class="vb vb-r">Kriitline</span></p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa kirjeldus ja eeldatavad materjalid</strong><p>Töötaja näeb töökäsku kohe portaalis.</p></div></div>
    </div>
    <div class="vd-h3">Töövoog</div>
    <div class="vd-flow">
      <div class="vd-fs">Uus</div><div class="vd-fa">→</div>
      <div class="vd-fs">Määratud</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töös</div><div class="vd-fa">→</div>
      <div class="vd-fs">Lõpetatud</div>
    </div>
    <div class="vd-tip"><b>Lõpetamisel</b>Töötaja saab lisada: kommentaari, kasutatud materjalid laost, fotod (enne/pärast). Admin näeb kõiki andmeid töökäsu detailvaates.</div>
    <div class="vd-h3">Kiire staatuse muutmine nimekirjas</div>
    <p class="vd-p">Töökäsude nimekirjas on staatus muudetav otse ripploendist — kliki staatusel, vali uus. Muutus salvestub koheselt ilma lehte laadimata. Fotod vaatad 📷 nupuga otse nimekirjas.</p>
  </div>

  <div class="vd-sec" id="tootajad">
    <div class="vd-sec-h"><span class="ic cyan">👷</span>Töötajad</div>
    <div class="vd-h3">Töötaja lisamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töötajad → "+ Lisa töötaja"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida nimi, telefon, e-post, 6-kohaline PIN</strong><p>PIN on vajalik portaali sisselogimiseks. Vali unikaalne 6-kohaline number.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Salvesta</strong><p>Süsteem loob WP kasutaja rolliga <code>vesho_worker</code> ja saadab kutsemeili.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Prindi QR-kaart</strong><p>Töötaja detail → "Prindi QR" — lamineerige kaart kiiremaks sisselogimiseks.</p></div></div>
    </div>
    <div class="vd-h3">Töötaja haldamine</div>
    <table class="vd-t">
      <thead><tr><th>Toiming</th><th>Kuidas</th></tr></thead>
      <tbody>
        <tr><td>Aktiveeri / deaktiveeri</td><td>Nimekirjas 🟢 / ⚫ nupp — muutub kohe. Mitteaktiivne ei saa sisse logida.</td></tr>
        <tr><td>Muuda PINi</td><td>Töötaja detail → muuda → uus PIN → salvesta</td></tr>
        <tr><td>Määra tunnitasu</td><td>Töötaja detail → Tunnitasu (€/h) → raport arvutab töötasud</td></tr>
        <tr><td>Vaata töötunde</td><td>Vesho CRM → Töötunnid → filtreeri töötaja järgi</td></tr>
        <tr><td>Prindi QR-kaart</td><td>Töötaja detail → "Prindi QR" → avab printimisvaate</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Töötunnid ja töötasud</div>
    <p class="vd-p">Töötaja logib portaalis alguse ja lõpu aja. Admin näeb <strong>Vesho CRM → Töötunnid</strong> — töötaja kaupa kokkuvõtted, nädala- ja kuuaruanne, tunnitasude arvutus.</p>
  </div>

  <div class="vd-sec" id="marsruut">
    <div class="vd-sec-h"><span class="ic cyan">🗺️</span>Marsruut ja geofencing</div>
    <div class="vd-h3">Marsruudi optimeerimine</div>
    <p class="vd-p"><strong>Vesho CRM → Marsruut</strong> — vaata päeva hooldused kaardil. Nupuriba näitab järgmise 7 päeva hoolduste arve — klõpsa päeval et kohe laadida. Google Mapsi link avab optimeeritud marsruudi navigaatoris.</p>
    <div class="vd-h3">Geofencing (asukoha kontroll)</div>
    <p class="vd-p">Töötaja saab töötunde logida ainult lubatud asukoha lähedalt. Seadistatav raadiusega (meetrites).</p>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Seaded → Geofencing → "Luba asukoha kontroll"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Sisesta koordinaadid (laiuskraad, pikkuskraad)</strong><p>Google Maps → paremklõps asukohal → "Kopeeri koordinaadid"</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Määra raadius (nt 500 meetrit)</strong><p>"Ainult hoiatus" — logib aga hoiatab, kui väljas. Või blokeeri täielikult.</p></div></div>
    </div>
    <div class="vd-note"><b>Teave</b>Geofencing nõuab, et töötaja lubab brauseril asukohta lugeda. iOS Safari ja Chrome toetavad seda.</div>
  </div>

</div><!-- /panel-admin -->

<!-- ═══════════════════════════════════════════════════ ARVELDUS & LADU -->
<div class="vd-panel" id="panel-arveldus">

  <div class="vd-sec" id="arved">
    <div class="vd-sec-h"><span class="ic cyan">🧾</span>Arved ja PDF</div>
    <div class="vd-tip"><b>KM-määr</b>Praegu kehtiv KM-määr on <strong><?php echo esc_html($vat); ?>%</strong>. Muutmiseks: <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm-settings')); ?>" style="color:#14532d;font-weight:600">Seaded → KM-määr</a>. Vanad arved ei muutu.</div>
    <div class="vd-h3">Arve koostamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Arved → "+ Lisa arve"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient (kiirotsing nimega)</strong><p>Firma klientide puhul täituvad registrikood ja KMKR automaatselt.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa read: kirjeldus, kogus, ühikuhind</strong><p>KM (<?php echo esc_html($vat); ?>%) ja kogusumma arvutatakse automaatselt. KM lüliti keerab kõikidel ridadel korraga.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Määra maksetähtaeg (vaikimisi 14 päeva)</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Salvesta ja saada e-postiga</strong><p>Arve saadetakse kliendile ja ilmub portaalis koheselt.</p></div></div>
    </div>
    <div class="vd-h3">Arvete staatused</div>
    <table class="vd-t">
      <thead><tr><th>Staatus</th><th>Portaalis nähtav</th><th>Arvestub tuludes</th><th>Saab muuta</th></tr></thead>
      <tbody>
        <tr><td><span class="vb vb-gray">Mustand</span></td><td>Ei</td><td>Ei</td><td>Jah</td></tr>
        <tr><td><span class="vb vb-b">Saadetud</span></td><td>Jah</td><td>Ei</td><td>Jah</td></tr>
        <tr><td><span class="vb vb-g">Tasutud</span></td><td>Jah</td><td>Jah</td><td>Piiratud</td></tr>
        <tr><td><span class="vb vb-r">Tähtaeg ületatud</span></td><td>Jah (punane)</td><td>Ei</td><td>Jah</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Kiired toimingud</div>
    <div class="vd-g3">
      <div class="vd-c"><h4>📋 Kopeeri uueks</h4><p>📋 nupp nimekirjas — loob mustandkoopia täna kuupäevaga. Kasulik korduvarvete jaoks.</p></div>
      <div class="vd-c"><h4>✉️ Saada e-post</h4><p>Saada arve kliendile otse nimekirja nupust, ilma arvet avamata.</p></div>
      <div class="vd-c"><h4>🖨️ PDF printimine</h4><p>Ava arve → "Prindi / PDF" → printimisvaade. Salvesta PDF: vali printeriks "Salvesta PDF-ina".</p></div>
    </div>
    <div class="vd-h3">Kreeditarve</div>
    <p class="vd-p">Tasutud/saadetud arve juures → "Kreeditarve" → sisesta summa ja põhjus → süsteem loob kreeditarve viitenumbriga.</p>
  </div>

  <div class="vd-sec" id="maksed">
    <div class="vd-sec-h"><span class="ic cyan">💳</span>Maksete vastuvõtmine (e-pood)</div>
    <p class="vd-p">Kliendi portaali e-poes saab aktiveerida erinevaid makseviise. Seadistatav <strong>Seaded → Maksed</strong>.</p>
    <div class="vd-g3">
      <div class="vd-c">
        <h4>💳 Stripe</h4>
        <ul>
          <li>Kaardimaksed (Visa, Mastercard, jt)</li>
          <li>Vaja: Stripe avalik võti + salajane võti</li>
          <li>Livelöme: stripe.com → API keys</li>
          <li>Testmode: kasuta <code>sk_test_...</code> võtmeid</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>🏦 Montonio</h4>
        <ul>
          <li>Pangalingid (LHV, SEB, Swedbank jt)</li>
          <li>Vaja: Montonio Store UUID + Secret key</li>
          <li>Livelöme: montonio.com → API seaded</li>
          <li>Sandbox-režiim testimiseks saadaval</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>🏧 MakeCommerce</h4>
        <ul>
          <li>Pangalingid + kaardimaksed</li>
          <li>Vaja: Shop ID + Secret key</li>
          <li>makecommerce.eu portaalist</li>
          <li>Sandbox-režiim testimiseks saadaval</li>
        </ul>
      </div>
    </div>
    <div class="vd-warn"><b>Ettevaatust</b>Ära aktiveeri mitut makseproviderit korraga — valige üks. Testige makset alati enne päris kasutamist sandbox-režiimis.</div>
  </div>

  <div class="vd-sec" id="ladu">
    <div class="vd-sec-h"><span class="ic cyan">📦</span>Ladu</div>
    <div class="vd-h3">Laoseis</div>
    <p class="vd-p"><strong>Vesho CRM → Ladu</strong> — kõik kaubad koos koguse, miinimumkoguse ja asukohaga. Punane rida = kogus alla miinimumi. Töölaud kuvab laopuuduse hoiatuse automaatselt.</p>
    <div class="vd-tip"><b>Kiirmuutmine</b>Laonimekirjas klõpsa koguse lahtrile — muutub otse redigeeritavaks. Enter salvestab, Escape tühistab.</div>
    <div class="vd-h3">Kauba vastuvõtt</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Vastuvõtt → "+ Uus vastuvõtt"</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali tarnija, lisa kaubad ja kogused</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kinnita vastuvõtt</strong><p>Laokogused uuenevad automaatselt. Seotud ostutellimus suletakse.</p></div></div>
    </div>
    <div class="vd-h3">Ostutellimused tarnijale</div>
    <p class="vd-p"><strong>Vesho CRM → Ostutellimused</strong> — loo ostutellimus tarnijale kui ladu tühjeneb. Vastuvõtmisel suletakse ostutellimus automaatselt ja laokogus uueneb.</p>
  </div>

  <div class="vd-sec" id="hinnakiri">
    <div class="vd-sec-h"><span class="ic cyan">💰</span>Hinnakiri ja e-pood</div>
    <div class="vd-g2">
      <div class="vd-c">
        <h4>Toote / teenuse lisamine</h4>
        <ul>
          <li>Vesho CRM → Hinnakiri → "+ Lisa"</li>
          <li>Nimi, kategooria, hind (km-ta), kirjeldus</li>
          <li>"Avalik" = kuvatakse veebilehel</li>
          <li>"Müük portaalis" = klient saab tellida</li>
          <li>Saab lisada pilti tootele</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>Shortcode hinnakirjale</h4>
        <ul>
          <li>Kõik: <span class="sc">[vesho_price_list]</span></li>
          <li>Filtreeritult: <span class="sc">[vesho_price_list category="X"]</span></li>
          <li>Lisa Elementori "Shortcode" widgetiga</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>Tarne valikud</h4>
        <ul>
          <li>Seaded → Tarnimine</li>
          <li>Pealetulek, kullerteenistus, pakipunkt</li>
          <li>Iga valik eraldi lülitiga sisse/välja</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>Kampaaniad</h4>
        <ul>
          <li>Vesho CRM → Kampaaniad</li>
          <li>Seo tootega, määra % ja kehtivusaeg</li>
          <li>Aktiveeri / peata AJAX-nupuga nimekirjast</li>
          <li>Klient näeb allahindlust portaali poes</li>
        </ul>
      </div>
    </div>
  </div>

</div><!-- /panel-arveldus -->

<!-- ═══════════════════════════════════════════════════════ PORTAALID -->
<div class="vd-panel" id="panel-portaalid">

  <div class="vd-sec" id="tootaja-portaal">
    <div class="vd-sec-h"><span class="ic cyan">👷</span>Töötaja portaal — <a href="<?php echo esc_url($site_url.'/worker/'); ?>" target="_blank" style="font-size:12px;font-weight:400;color:#00b4c8"><?php echo esc_html($site_url); ?>/worker/</a></div>
    <div class="vd-h3">Vahekaardid</div>
    <table class="vd-t">
      <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
      <tbody>
        <tr><td>Ülevaade</td><td>Aktiivsed töökäsud, tänased tööd, lähimad hooldused, kokkuvõte</td></tr>
        <tr><td>Töökäsud</td><td>Muuda staatust, lisa kommentaar, märgi materjalid, lisa fotod (enne/pärast)</td></tr>
        <tr><td>Hooldused</td><td>Tänased ja tulevased hooldused, lõpetamise kinnitamine</td></tr>
        <tr><td>Töötunnid</td><td>Logi töö algus/lõpp, kuu kokkuvõte, kellaajasissekanded</td></tr>
        <tr><td>Seaded</td><td>Parooli muutmine, teavituste eelistused</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Sisselogimisviisid</div>
    <div class="vd-g3">
      <div class="vd-c"><h4>🔢 Nimi + 6-kohaline PIN</h4><p>Vali oma nimi nimekirjast, sisesta PIN. Administraator annab PINi.</p></div>
      <div class="vd-c"><h4>📷 QR-kaart</h4><p>"Skänni töötaja QR kaart" → hoia kaamera ette. Logib koheselt sisse.</p></div>
      <div class="vd-c"><h4>🔵 Google</h4><p>"Jätka Google'iga" → Google konto e-post peab olema sama mis CRM-i töötaja e-post.</p></div>
    </div>
    <div class="vd-h3">Mobiilis avakuvale (PWA)</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava Chrome mobiilis → /worker/</strong><p></p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Aadressiribal ⊕ → "Lisa avakuvale"</strong><p>Ikoon ilmub avakuvale — töötab nagu äpp, täisekraanil ilma brauseri ribata.</p></div></div>
    </div>
    <div class="vd-note"><b>iOS Safari</b>Jagamise nupp (☐↑) → "Lisa avakuvale". Portaal töötab täisekraanil, tumedal topbaaril, turvaala toega.</div>
  </div>

  <div class="vd-sec" id="kliendi-portaal">
    <div class="vd-sec-h"><span class="ic cyan">🌐</span>Kliendi portaal — <a href="<?php echo esc_url($site_url.'/klient/'); ?>" target="_blank" style="font-size:12px;font-weight:400;color:#00b4c8"><?php echo esc_html($site_url); ?>/klient/</a></div>
    <div class="vd-h3">Vahekaardid</div>
    <table class="vd-t">
      <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
      <tbody>
        <tr><td>Ülevaade</td><td>Järgmine hooldus, tasumata arved, aktiivsed seadmed, viimased tegevused</td></tr>
        <tr><td>Seadmed</td><td>Seadmete kaardid piltidega, hooldusajalugu, järgmine hooldus, garantii</td></tr>
        <tr><td>Hooldused</td><td>Tulevased ja tehtud hooldused, staatus, märkused</td></tr>
        <tr><td>Arved</td><td>Kõik arved, PDF allalaadimine, punane = maksmata, veebimaks (kui aktiveeritud)</td></tr>
        <tr><td>Pood</td><td>Toodete kataloog, tellimine, ostukorv, makseviisid</td></tr>
        <tr><td>Minu tellimused</td><td>E-poe tellimuste ajalugu, staatused, PDF</td></tr>
        <tr><td>Tugi</td><td>Tugipileti saatmine → admin näeb: Vesho CRM → Tugipiletid</td></tr>
        <tr><td>Broneeri</td><td>Hoolduse soovitamine → admin näeb: Päringud → kinnita / lükka tagasi</td></tr>
        <tr><td>Leping</td><td>Lepingudokument ja tingimused (kui seadistatud)</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Sisselogimisviisid</div>
    <div class="vd-g2">
      <div class="vd-c"><h4>📧 E-post + parool</h4><p>Kliendi ligipääs genereeritakse adminipaneelist. Parool saadetakse emailiga.</p></div>
      <div class="vd-c"><h4>🔵 Google</h4><p>"Jätka Google'iga" — e-post peab vastama kliendi CRM-i e-postile. Saadaval ka peasaidi login modaalis.</p></div>
    </div>
    <div class="vd-h3">Portaali bännerteade</div>
    <p class="vd-p"><strong>Vesho CRM → Portaali teated</strong> — lisa teade kõigile sisselogitud klientidele (nt hinnamuutus, pühadel suletud). Saab määrata algus- ja lõppkuupäeva. Kuvatakse portaali ülaosas värvilise bännerina.</p>
    <div class="vd-h3">Hooldusrežiim</div>
    <p class="vd-p"><strong>Seaded → Hooldusrežiim</strong> — lülita kliendi portaal ajutiselt hooldusrežiimile. Kuvatakse teade "Hooldus käib". Admin näeb portaali ikka normaalselt.</p>
  </div>

  <div class="vd-sec" id="peasait-portaal">
    <div class="vd-sec-h"><span class="ic cyan">🏠</span>Peasaidi login nupp</div>
    <p class="vd-p">Veebilehe päises on nupp <strong>"Portaal"</strong> — avab modaalaknakese kus klient saab sisse logida või registreeruda ilma lehelt lahkumata.</p>
    <div class="vd-g2">
      <div class="vd-c">
        <h4>Login modal sisaldab</h4>
        <ul>
          <li>Logi sisse vahekaart: e-post + parool</li>
          <li>Registreeru vahekaart: nimi, e-post, parool, kliendi tüüp</li>
          <li>Google nupp (kui aktiveeritud seadetes)</li>
          <li>AJAX — ei laadi lehte uuesti</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>Portaali pealkirja muutmine</h4>
        <ul>
          <li>Seaded → Kliendi portaali seaded</li>
          <li>"Portaali pealkiri" → muuda teksti</li>
          <li>Kuvatakse nii modaalis kui /klient/ lehel</li>
        </ul>
      </div>
    </div>
    <div class="vd-tip"><b>Registreerimine</b>Seaded → Kliendi portaal → "Luba iseteeninduse registreerimine" — kui välja lülitada, saavad sisse logida ainult admin poolt lisatud kliendid.</div>
  </div>

</div><!-- /panel-portaalid -->

<!-- ═══════════════════════════════════════════════════════ VEEBILEHT -->
<div class="vd-panel" id="panel-veebileht">

  <div class="vd-sec" id="elementor">
    <div class="vd-sec-h"><span class="ic cyan">🎨</span>Elementor — lehe redigeerimine</div>
    <div class="vd-warn"><b>Enne muutusi</b>Tee varukoopia: WordPress Admin → Tööriistad → Eksport. Või UpdraftPlus automaatseks varundamiseks.</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>WordPress Admin → Lehed → hõljuta lehel</strong><p>Kliki "Redigeeri Elementoriga".</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Klõpsa elemendil → vasakpaneel avab seaded</strong><p>Teksti saab muuta otse klõpsates. Pildid, nupud, värvid muudetavad vasakpaneelist.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Klõpsa "Avalda" (roheline nupp)</strong><p>Muutused on kohe nähtavad külastajatele.</p></div></div>
    </div>
    <div class="vd-h3">Lehed</div>
    <table class="vd-t">
      <thead><tr><th>Leht</th><th>URL</th><th>Märkused</th></tr></thead>
      <tbody>
        <tr><td>Avaleht</td><td><code>/</code></td><td>Kõik muudetav Elementoriga</td></tr>
        <tr><td>Teenused</td><td><code>/teenused/</code></td><td>Kasuta <span class="sc">[vesho_price_list]</span></td></tr>
        <tr><td>Pood</td><td><code>/pood/</code></td><td>Tooted hallatakse CRM-i hinnakirjas</td></tr>
        <tr><td>Klient</td><td><code>/klient/</code></td><td>⚠️ ÄRA muuda shortcode'i</td></tr>
        <tr><td>Töötaja</td><td><code>/worker/</code></td><td>⚠️ ÄRA muuda shortcode'i</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Vesho Stats Grid widget</div>
    <p class="vd-p">Elementori vasakpaneeli widgetide nimekirjas on <strong>"Vesho Stats Grid"</strong> — numbrikaardid statistikaks (nt "500+ klienti", "10 aastat kogemust"). Numbrid ja tekstid muudetavad otse Elementoris.</p>
  </div>

  <div class="vd-sec" id="shortcodes">
    <div class="vd-sec-h"><span class="ic">〈/〉</span>Shortcode'id</div>
    <table class="vd-t">
      <thead><tr><th>Shortcode</th><th>Kirjeldus</th></tr></thead>
      <tbody>
        <tr><td><span class="sc">[vesho_client_portal]</span></td><td>Kliendi portaal — lehe /klient/ sisu. Ära muuda.</td></tr>
        <tr><td><span class="sc">[vesho_worker_portal]</span></td><td>Töötaja portaal — lehe /worker/ sisu. Ära muuda.</td></tr>
        <tr><td><span class="sc">[vesho_price_list]</span></td><td>Kõik avalikud tooted ja teenused kaardivaatena</td></tr>
        <tr><td><span class="sc">[vesho_price_list category="X"]</span></td><td>Hinnakiri filtreeritud kategooria järgi</td></tr>
        <tr><td><span class="sc">[vesho_shop]</span></td><td>E-poe kaupade kuvamine ostuvõimalusega</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Kuulutuste riba (announcement bar)</div>
    <p class="vd-p">Seaded → Kuulutuste riba — tekst, link, värvid, nähtavus (kõigile / ainult külalistele). Kuvatakse veebilehe päise kohal. Sulgetav klõpsuga. Hea eriaktsioone reklaamimiseks.</p>
  </div>

</div><!-- /panel-veebileht -->

<!-- ═════════════════════════════════════════════════════════ SEADED -->
<div class="vd-panel" id="panel-seaded">

  <div class="vd-sec" id="uldseaded">
    <div class="vd-sec-h"><span class="ic cyan">⚙️</span>Üldseaded</div>
    <p class="vd-p">Vesho CRM → <strong>Seaded</strong>. Muutused mõjutavad kõiki PDF-e, e-kirju ja portaale.</p>
    <div class="vd-g3">
      <div class="vd-c"><h4>Firma andmed</h4><ul><li>Nimi, registrikood, KMKR</li><li>Aadress, telefon, e-post</li><li>Kuvatakse PDF arvedel ja jaluses</li></ul></div>
      <div class="vd-c"><h4>KM-määr (praegu <?php echo esc_html($vat); ?>%)</h4><ul><li>Mõjutab ainult uusi arveid</li><li>Vanad arved ei muutu</li><li>Määra enne esimest arvet</li></ul></div>
      <div class="vd-c"><h4>Logo PDF-il</h4><ul><li>PNG läbipaistva taustaga</li><li>Ideaalne: 300×100 px</li><li>Kuvatakse arve ülaosas</li></ul></div>
      <div class="vd-c"><h4>Arve nummerdamine</h4><ul><li>Prefiks: nt <code>VESHO-2024-</code></li><li>Järjekorranumber: algab 1-st</li><li>Määra enne esimest arvet!</li></ul></div>
      <div class="vd-c"><h4>Pangaandmed</h4><ul><li>IBAN, pank, selgituse tekst</li><li>Kuvatakse PDF allosas</li></ul></div>
      <div class="vd-c"><h4>Administraatorid</h4><ul><li>Anna CRM ligipääs teisele WP kasutajale</li><li>Roll: <code>vesho_crm_admin</code></li></ul></div>
    </div>
  </div>

  <div class="vd-sec" id="google-login-seaded">
    <div class="vd-sec-h"><span class="ic cyan">🔵</span>Google Login seadistamine</div>
    <?php if($google_ok): ?>
    <div class="vd-tip"><b>✅ Aktiivne</b>Google OAuth credentials on seadistatud. Nupp ilmub portaalides vastavalt allolevatele lülitite seadistustele.</div>
    <?php else: ?>
    <div class="vd-warn"><b>⚠️ Seadistamata</b>Google Client ID või Secret puudub — Google login nupp ei ilmu. Järgi alltoodud juhiseid.</div>
    <?php endif; ?>
    <div class="vd-h3">Google Cloud Console seadistamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Mine <a href="https://console.cloud.google.com/" target="_blank" style="color:#00b4c8">console.cloud.google.com</a></strong><p>Logi sisse Google kontoga (ettevõtte konto eelistatult).</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Loo uus projekt või vali olemasolev</strong><p>APIs &amp; Services → "Create Project" → anna nimi (nt "Vesho CRM")</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>OAuth consent screen seadistamine</strong><p>APIs &amp; Services → OAuth consent screen → User Type: External → täida nimi + e-post → Save</p></div></div>
      <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Loo OAuth 2.0 Client ID</strong><p>Credentials → Create Credentials → OAuth Client ID → Application type: <strong>Web application</strong></p></div></div>
      <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Lisa Authorized redirect URIs</strong>
        <p>Lisa mõlemad:<br>
        <code><?php echo esc_html(home_url('/?vesho_google_cb=client')); ?></code><br>
        <code><?php echo esc_html(home_url('/?vesho_google_cb=worker')); ?></code></p>
      </div></div>
      <div class="vd-step"><div class="vd-step-n">6</div><div class="vd-step-b"><strong>Kopeeri Client ID ja Client Secret</strong><p>Vesho CRM → Seaded → Google Login → kleebi mõlemad väljad → Salvesta</p></div></div>
    </div>
    <div class="vd-h3">Portaalide lülitid</div>
    <p class="vd-p">Pärast credentials sisestamist saab iga portaali jaoks eraldi Google logini sisse/välja lülitada:<br>
    <strong>Seaded → Google Login → "Luba Google login portaalides"</strong> — eraldi checkbox kliendi- ja töötajaporteali jaoks.</p>
    <div class="vd-note"><b>Teave</b>Google konto e-post peab vastama CRM-is registreeritud e-postile. Kui töötajal on muu Google konto, ei saa ta sellega sisse logida — admin peab CRM-is e-posti muutma.</div>
  </div>

  <div class="vd-sec" id="email-seaded">
    <div class="vd-sec-h"><span class="ic cyan">📧</span>E-post ja teavitused</div>
    <div class="vd-h3">SMTP seadistamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Seaded → E-posti seaded</strong><p>Täida SMTP server, port (587 TLS / 465 SSL), kasutajanimi, parool, saatja nimi ja e-post.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>"Saada testmeil"</strong><p>Saadetakse testmeil administraatori aadressile — kontrollib kas seaded toimivad.</p></div></div>
    </div>
    <div class="vd-h3">Automaatsed teavitused</div>
    <table class="vd-t">
      <thead><tr><th>Sündmus</th><th>Saaja</th><th>Automaatne?</th></tr></thead>
      <tbody>
        <tr><td>Portaali ligipääs genereeritud</td><td>Klient</td><td>Jah — kohe</td></tr>
        <tr><td>Arve saatmine</td><td>Klient</td><td>Admin vajutab "Saada"</td></tr>
        <tr><td>Töötaja kutse</td><td>Töötaja</td><td>Jah — lisamisel</td></tr>
        <tr><td>Töökäsu määramine</td><td>Töötaja</td><td>Valikuline (Seaded)</td></tr>
        <tr><td>Hoolduse meeldetuletus</td><td>Admin</td><td>Jah — X päeva enne</td></tr>
        <tr><td>Laopuuduse hoiatus</td><td>Admin</td><td>Jah — kui alla min</td></tr>
        <tr><td>Uus tugipilet</td><td>Admin</td><td>Jah — kohe</td></tr>
        <tr><td>Uus broneering/päring</td><td>Admin</td><td>Jah — kohe</td></tr>
      </tbody>
    </table>
    <div class="vd-h3">Küpsiste bänner</div>
    <p class="vd-p">Seaded → Küpsiste bänner — lülita küpsiste nõusoleku dialoog sisse/välja. CRM valib automaatselt privaatsust säästvaima valiku kui kasutaja ei reageeri.</p>
  </div>

  <div class="vd-sec" id="portaali-seaded">
    <div class="vd-sec-h"><span class="ic cyan">🌐</span>Portaalide seaded</div>
    <div class="vd-g2">
      <div class="vd-c">
        <h4>Kliendi portaali seaded</h4>
        <ul>
          <li>Portaali pealkiri ja alapealkiri</li>
          <li>Luba / keela registreerimine</li>
          <li>Kuva / peida menüüpunktid (seadmed, hooldused, arved, pood, tugi)</li>
          <li>Lepingutingimuste leht</li>
        </ul>
      </div>
      <div class="vd-c">
        <h4>Töötaja portaali seaded</h4>
        <ul>
          <li>Töötaja portaali leht (URL)</li>
          <li>Google login sisse/välja</li>
          <li>QR-login sisse/välja</li>
          <li>Geofencing raadiuse ja koordinaatide seadistamine</li>
        </ul>
      </div>
    </div>
  </div>

</div><!-- /panel-seaded -->

<!-- ══════════════════════════════════════════════════════ TÖÖVOOD -->
<div class="vd-panel" id="panel-toovood">
  <div class="vd-sec" id="toovood">
    <div class="vd-sec-h"><span class="ic cyan">🔄</span>Tüüpilised töövood</div>

    <div class="vd-h3">Uus klient → esimene hooldus → arve</div>
    <div class="vd-flow">
      <div class="vd-fs">Lisa klient</div><div class="vd-fa">→</div>
      <div class="vd-fs">Lisa seade</div><div class="vd-fa">→</div>
      <div class="vd-fs">Saada portaali ligipääs</div><div class="vd-fa">→</div>
      <div class="vd-fs">Planeeri hooldus</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töötaja teeb töö</div><div class="vd-fa">→</div>
      <div class="vd-fs">Koosta arve</div><div class="vd-fa">→</div>
      <div class="vd-fs">Märgi tasututuks</div>
    </div>

    <div class="vd-h3">Veebilehelt tulev hinnapäring</div>
    <div class="vd-flow">
      <div class="vd-fs">Päring saabub</div><div class="vd-fa">→</div>
      <div class="vd-fs">CRM → Külaliste taotlused</div><div class="vd-fa">→</div>
      <div class="vd-fs">"Lisa kliendiks"</div><div class="vd-fa">→</div>
      <div class="vd-fs">Saada portaali ligipääs</div><div class="vd-fa">→</div>
      <div class="vd-fs">Planeeri hooldus</div>
    </div>

    <div class="vd-h3">Kliendi ise tehtud broneering portaalist</div>
    <div class="vd-flow">
      <div class="vd-fs">Klient: portaal → Broneeri</div><div class="vd-fa">→</div>
      <div class="vd-fs">Admin saab teavituse</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töölaud → Ootavad broneeringud → Kinnita ✓</div><div class="vd-fa">→</div>
      <div class="vd-fs">Hooldus ilmub kalendrisse</div>
    </div>

    <div class="vd-h3">Hoolduse kiire lõpetamine kalendrist</div>
    <p class="vd-p">Kliki kalendris hooldusel → avaneb modal → vajuta <strong>✓ Tehtud</strong> — hooldus märgitakse tehtuks, seadme järgmine hooldus uueneb automaatselt. Ei pea avama hoolduse muutmislehte.</p>

    <div class="vd-h3">Igakuine hoolduste planeerimine</div>
    <div class="vd-flow">
      <div class="vd-fs">Töölaud → Meeldetuletused</div><div class="vd-fa">→</div>
      <div class="vd-fs">Vali seadmed nimekirjast</div><div class="vd-fa">→</div>
      <div class="vd-fs">Lisa hooldused kalendrisse</div><div class="vd-fa">→</div>
      <div class="vd-fs">Marsruut → optimeeri</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töötajad saavad teavituse</div>
    </div>

    <div class="vd-h3">Laopuudus → ostutellimus</div>
    <div class="vd-flow">
      <div class="vd-fs">Töölaud hoiatab punaselt</div><div class="vd-fa">→</div>
      <div class="vd-fs">Ladu → vaata puuduolevad</div><div class="vd-fa">→</div>
      <div class="vd-fs">Loo ostutellimus tarnijale</div><div class="vd-fa">→</div>
      <div class="vd-fs">Kaup saabub → Vastuvõtt</div><div class="vd-fa">→</div>
      <div class="vd-fs">Laoseis uueneb</div>
    </div>

    <div class="vd-h3">Kiire töökäsk rikke korral</div>
    <div class="vd-flow">
      <div class="vd-fs">Lisa töökäsk (Kriitline)</div><div class="vd-fa">→</div>
      <div class="vd-fs">Määra töötaja</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töötaja saab teavituse</div><div class="vd-fa">→</div>
      <div class="vd-fs">Töötaja teeb → Tehtuks</div><div class="vd-fa">→</div>
      <div class="vd-fs">Koosta arve</div>
    </div>

    <div class="vd-h3">Tugipilet kliendilt</div>
    <div class="vd-flow">
      <div class="vd-fs">Klient: portaal → Tugi → saada pilet</div><div class="vd-fa">→</div>
      <div class="vd-fs">Admin saab e-posti teavituse</div><div class="vd-fa">→</div>
      <div class="vd-fs">CRM → Tugipiletid → ava pilet</div><div class="vd-fa">→</div>
      <div class="vd-fs">Vasta split-vaates</div><div class="vd-fa">→</div>
      <div class="vd-fs">Sulge pilet</div>
    </div>

    <div class="vd-h3">Korduvklient: iga-aastane hooldusteade</div>
    <div class="vd-flow">
      <div class="vd-fs">Süsteem saadab automaatse meeldetuletuse</div><div class="vd-fa">→</div>
      <div class="vd-fs">Klient broneerib portaalist</div><div class="vd-fa">→</div>
      <div class="vd-fs">Admin kinnitab</div><div class="vd-fa">→</div>
      <div class="vd-fs">Hooldus → arve</div>
    </div>
  </div>
</div><!-- /panel-toovood -->

<!-- ══════════════════════════════════════════════════════ UUENDUSED -->
<div class="vd-panel" id="panel-uuendused">

  <div class="vd-sec" id="uuendused">
    <div class="vd-sec-h"><span class="ic cyan">🚀</span>Uuendused</div>
    <div class="vd-tip"><b>Paigaldatud</b>Vesho CRM <strong>v<?php echo esc_html($plugin_ver); ?></strong> · Teema <strong>v<?php echo esc_html($theme_ver); ?></strong></div>

    <div class="vd-h3">Uuendusprotsess</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → 🚀 Uuendused</strong><p>Kontrollib uut versiooni GitHubist. Muudatuste logi näitab mis parandati.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Klõpsa "Uuenda plugin kohe" / "Uuenda teema kohe"</strong><p>Laeb alla GitHubist, paigaldab automaatselt (&lt; 30 sek).</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Uuendus ei ilmu? Klõpsa "Kontrolli uuendusi kohe"</strong><p>Tühjendab vahemälu ja kontrollib uuesti. Kui ikka ei ilmu — <a href="<?php echo esc_url(admin_url('update-core.php?force-check=1')); ?>" style="color:#00b4c8">tühjenda WP puhver siin</a>.</p></div></div>
    </div>

    <div class="vd-h3">Versiooniajalugu — v2.9.x</div>

    <div class="vd-cl-item">
      <h4>v2.9.109 — Google login lülitid toimivad</h4>
      <ul><li>Google login checkbox "Client portaal" ja "Worker portaal" kontrollivad nüüd tegelikult nupu nähtavust</li><li>Eelmine versioon ignoreeris lüliteid — nupp ilmus alati kui credentials olid seadistatud</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.108 — Töötaja Google nupp valge stiil</h4>
      <ul><li>Töötaja portaali Google login nupp muudeti: valge taust, tume tekst — sama stiil kui kliendi portaalis</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.107 — Google nupp peasaidi modaalis</h4>
      <ul><li>Peasaidi päise login modaalaknas on nüüd Google nupp (ilmub ainult kui seadistatud)</li><li>Töötaja portaali Google nupu teksti värv fikseeritud</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.106 — PIN 6 kohta töötaja portaalis</h4>
      <ul><li>PIN-koodi sisestusväli ja näidikud muudeti 4-lt 6-le kohale (vastab tegelikule PINi pikkusele)</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.105 — Google nupu tekst nähtav kliendi portaalis</h4>
      <ul><li>Google login nupu tekst "Jätka Google'iga" oli valge valgel taustal — fikseeritud CSS !important reeglitega</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.104 — Google login fallback + debug</h4>
      <ul><li>Kliendi portaali Google nupp: fallback kui Vesho_Google_Auth klass pole laetud</li><li>Admin näeb hoiatust kui Google Client ID puudub</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.103 — Scroll + Google debug</h4>
      <ul><li>Hiirega scrollimine töötab desktop-režiimis klient- ja töötajaportealis</li><li>Google login enabled_for() alati true kui credentials olemas</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.100 — Mobiili parendused</h4>
      <ul>
        <li>Alumine navbar scrollitav külili — kõik menüüpunktid ligipääsetavad</li>
        <li>Lao tabid scrollivad külili mitte ei murdu</li>
        <li>CRM tabid kõigil lehtedel: flex-shrink:0 + nowrap</li>
      </ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.90 — CRM admin mobiil</h4>
      <ul><li>CRM admin mobiilivaade: alumine navbar + WP chrome fix</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.89 — Worker cache fix</h4>
      <ul><li>Worker portaal: no-cache päised (Hostinger LiteSpeed cache fix)</li><li>CRM täielik mobiilivaade</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.87 — iOS Safari login fix</h4>
      <ul><li>Worker login: AJAX → server-side POST (iOS Safari küpsise fix)</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.82 — Vesho Stats Grid widget</h4>
      <ul><li>Uus Elementor widget "Vesho Stats Grid" — muuda numbreid ja tekste otse Elementoris</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.72 — Native app mobiili UI</h4>
      <ul><li>Kõik 4 portaali: WP header peidetud, dark topbar safe-area toega, frosted glass bottom nav, fade animatsioon, theme-color meta</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.56 — Mobiili parendused</h4>
      <ul><li>crm-stat-card CSS korrektne stiiliplokk</li><li>Tugipiletid: split-vaade mobiilis</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.55 — Kliendi kustutamine blokeeritud</h4>
      <ul><li>Maksmata arved blokeerivad kustutamise</li><li>Avatud töökäsud blokeerivad kustutamise</li></ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.54 — Uued funktsioonid</h4>
      <ul>
        <li>Tugipiletid: split-vaade — nimekiri + detail kõrvuti</li>
        <li>Töötunnid: kellaajavahekaart (sisse/välja logimine)</li>
        <li>Töökäskude fotod nimekirjast (📷 nupp)</li>
        <li>KM lüliti arvete vormil</li>
        <li>Kliendi automaatne täitmine arvetel</li>
      </ul>
    </div>
    <div class="vd-cl-item">
      <h4>v2.9.53 — Broneeringud ja marsruut</h4>
      <ul>
        <li>Broneeringute kinnitamine otse töölaual</li>
        <li>7-päeva eelvaade marsruudi lehel</li>
        <li>Kampaaniate AJAX-lüliti</li>
        <li>Lao koguse kiirmuutmine</li>
      </ul>
    </div>

    <div class="vd-h3">WordPress core uuendamine</div>
    <div class="vd-steps">
      <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Tee varukoopia enne uuendust</strong><p>UpdraftPlus → "Varunda nüüd".</p></div></div>
      <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>WP Admin → Tööriistad → Uuendused</strong><p>Järjekord: core → pistikprogrammid → teemad.</p></div></div>
      <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kontrolli portaale pärast uuendust</strong><p>Ava /klient/ ja /worker/ — veendu et kõik töötab.</p></div></div>
    </div>
    <div class="vd-warn"><b>Ettevaatust</b>Ära uuenda WordPressi kohe uue versiooni ilmumisel — oota 1–2 nädalat kuni vead on parandatud.</div>
  </div>

</div><!-- /panel-uuendused -->

<!-- ══════════════════════════════════════════════════════ SÜSTEEM -->
<div class="vd-panel" id="panel-susteem">

  <div class="vd-sec" id="sysinfo">
    <div class="vd-sec-h"><span class="ic cyan">🖥️</span>Süsteemi info</div>
    <div class="vd-si">
      <div class="vd-si-item"><div class="lbl">Vesho CRM</div><div class="val">v<?php echo esc_html($plugin_ver); ?></div><div class="sub">Pistikprogramm</div></div>
      <div class="vd-si-item"><div class="lbl">Teema</div><div class="val">v<?php echo esc_html($theme_ver); ?></div><div class="sub">Vesho theme</div></div>
      <div class="vd-si-item <?php echo $wp_ok?'ok':'err'; ?>"><div class="lbl">WordPress</div><div class="val">v<?php echo esc_html($wp_ver); ?></div><div class="sub"><?php echo $wp_ok?'Nõue täidetud':'Uuenda!'; ?></div></div>
      <div class="vd-si-item <?php echo $php_ok?'ok':'err'; ?>"><div class="lbl">PHP</div><div class="val"><?php echo esc_html($php_ver); ?></div><div class="sub"><?php echo $php_ok?'OK':'Vaja 8.0+'; ?></div></div>
      <div class="vd-si-item"><div class="lbl">MySQL</div><div class="val"><?php echo esc_html($db_ver); ?></div><div class="sub">Andmebaas</div></div>
      <div class="vd-si-item"><div class="lbl">KM-määr</div><div class="val"><?php echo esc_html($vat); ?>%</div><div class="sub">Kehtiv</div></div>
      <div class="vd-si-item <?php echo $google_ok?'ok':'err'; ?>"><div class="lbl">Google OAuth</div><div class="val"><?php echo $google_ok?'✓ OK':'✗ Puudub'; ?></div><div class="sub"><?php echo $google_ok?'Seadistatud':'Seadista seadetes'; ?></div></div>
      <div class="vd-si-item"><div class="lbl">Saidi URL</div><div class="val" style="font-size:11px"><?php echo esc_html($site_url); ?></div><div class="sub">Site URL</div></div>
      <div class="vd-si-item"><div class="lbl">memory_limit</div><div class="val"><?php echo esc_html(ini_get('memory_limit')); ?></div><div class="sub">Soovitatav 256M+</div></div>
      <div class="vd-si-item"><div class="lbl">upload_max</div><div class="val"><?php echo esc_html(ini_get('upload_max_filesize')); ?></div><div class="sub">Soovitatav 32M+</div></div>
      <div class="vd-si-item"><div class="lbl">max_exec_time</div><div class="val"><?php echo esc_html(ini_get('max_execution_time')); ?>s</div><div class="sub">Soovitatav 120s+</div></div>
    </div>

    <div class="vd-h3">Aktiivsed pistikprogrammid</div>
    <?php
    $active_plugins = get_option('active_plugins',[]);
    $plist = [];
    foreach($active_plugins as $pf){
      $pd = get_plugin_data(WP_PLUGIN_DIR.'/'.$pf,false,false);
      if(!empty($pd['Name'])) $plist[] = $pd;
    }
    ?>
    <table class="vd-t">
      <thead><tr><th>Pistikprogramm</th><th>Versioon</th></tr></thead>
      <tbody>
        <?php foreach($plist as $pl): ?>
        <tr><td><?php echo esc_html($pl['Name']); ?></td><td><?php echo esc_html($pl['Version']); ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div><!-- /panel-susteem -->

</div><!-- #vdMain -->

<!-- ── TOC ── -->
<div id="vdToc">
  <div class="vd-toc-title" id="vdTocTitle">Sellel lehel</div>
  <div id="vdTocLinks"></div>
</div>

</div><!-- #vdBody -->
</div><!-- #vdWrap -->

<script>
(function(){
  var tabs   = document.querySelectorAll('.vd-tab');
  var panels = document.querySelectorAll('.vd-panel');
  var tocDiv = document.getElementById('vdTocLinks');

  function buildToc(panelId){
    var panel = document.getElementById('panel-'+panelId);
    if(!panel){ tocDiv.innerHTML=''; return; }
    var secs = panel.querySelectorAll('.vd-sec[id]');
    var html = '';
    secs.forEach(function(s){
      var title = s.querySelector('.vd-sec-h');
      var text = s.id;
      if(title){
        var clone = title.cloneNode(true);
        var ic = clone.querySelector('.ic');
        if(ic) ic.remove();
        clone.querySelectorAll('a').forEach(function(l){ l.remove(); });
        text = clone.textContent.trim().replace(/\s+/g,' ');
      }
      html += '<a href="#'+s.id+'" data-id="'+s.id+'">'+text+'</a>';
    });
    tocDiv.innerHTML = html;
    document.querySelectorAll('#vdTocLinks a').forEach(function(a){
      a.addEventListener('click',function(e){
        e.preventDefault();
        var el=document.getElementById(a.dataset.id);
        if(el) el.scrollIntoView({behavior:'smooth'});
      });
    });
  }

  function activateTab(tabEl){
    var panelId = tabEl.dataset.panel;
    tabs.forEach(function(t){ t.classList.remove('active'); });
    panels.forEach(function(p){ p.classList.remove('show'); });
    tabEl.classList.add('active');
    var panel = document.getElementById('panel-'+panelId);
    if(panel) panel.classList.add('show');
    buildToc(panelId);
    window.scrollTo({top:0,behavior:'smooth'});
    history.replaceState(null,'','#'+panelId);
  }

  tabs.forEach(function(t){
    t.addEventListener('click',function(e){
      e.preventDefault();
      activateTab(t);
    });
  });

  var hash = location.hash.replace('#','');
  if(hash){
    var found = false;
    tabs.forEach(function(t){ if(t.dataset.panel===hash){ activateTab(t); found=true; } });
    if(!found) buildToc('alustamine');
  } else {
    buildToc('alustamine');
  }

  window.addEventListener('scroll',function(){
    var y = window.scrollY+120;
    var activePanel = document.querySelector('.vd-panel.show');
    if(!activePanel) return;
    var secs = activePanel.querySelectorAll('.vd-sec[id]');
    var cur = null;
    secs.forEach(function(s){ if(y>=s.offsetTop) cur=s.id; });
    document.querySelectorAll('#vdTocLinks a').forEach(function(a){
      a.classList.toggle('active', a.dataset.id===cur);
    });
  },{passive:true});
})();
</script>
