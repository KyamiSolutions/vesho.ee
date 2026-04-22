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
#vd*{box-sizing:border-box;margin:0;padding:0}
#vdWrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;background:#f1f5f9;margin:-10px -20px -10px;min-height:100vh}

/* ── Header ── */
#vdHeader{background:#0d1f2d;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px}
#vdHeader h1{font-size:17px;font-weight:700;color:#fff;letter-spacing:-.3px;display:flex;align-items:center;gap:10px}
#vdHeader h1 span{background:#00b4c8;border-radius:6px;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
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

/* ── TOC sidebar (right/sticky) ── */
#vdToc{width:200px;flex-shrink:0;position:sticky;top:calc(32px + 44px + 20px);max-height:calc(100vh - 120px);overflow-y:auto;margin-left:32px;order:2;scrollbar-width:none}
#vdToc::-webkit-scrollbar{display:none}
.vd-toc-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-bottom:8px;padding:0 4px}
#vdToc a{display:block;padding:6px 8px;font-size:12px;color:#64748b;text-decoration:none;border-left:2px solid transparent;border-radius:0 4px 4px 0;transition:.12s;line-height:1.4}
#vdToc a:hover{color:#1e293b;background:#f8fafc}
#vdToc a.active{color:#00b4c8;border-left-color:#00b4c8;background:#f0fdff;font-weight:600}

/* ── Main content ── */
#vdMain{flex:1;min-width:0;order:1}

/* ── Panel (section group) ── */
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
            <tr><td>HTTPS</td><td colspan="2">Kohustuslik</td><td>—</td></tr>
          </tbody>
        </table>
      </div>

      <div class="vd-sec" id="sisselogimine">
        <div class="vd-sec-h"><span class="ic">🔑</span>Sisselogimine</div>
        <div class="vd-h3">Admin paneel</div>
        <p class="vd-p">Mine <code><?php echo esc_html($site_url); ?>/wp-admin/</code> — logi sisse WordPress administraatori kontoga. Vasakpoolses menüüs ilmub <strong>Vesho CRM</strong> sektsioon.</p>
        <div class="vd-h3">Töötaja portaal</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Mine <code><?php echo esc_html($site_url); ?>/worker/</code></strong><p>Sisselogimisleht avaneb automaatselt.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Sisesta e-post ja parool</strong><p>Parool saadeti töötaja lisamisel emailiga.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Mobiilis: lisa avakuvale</strong><p>Chrome → aadressiribal ⊕ → "Lisa avakuvale" — töötab nagu äpp.</p></div></div>
        </div>
        <div class="vd-h3">Kliendi portaal</div>
        <p class="vd-p">Mine <code><?php echo esc_html($site_url); ?>/klient/</code> — e-post + parool (parool saadeti emailiga kui admin vajutas "🔑 Ligipääs").</p>
        <div class="vd-note"><b>NB</b>Töötajad ja kliendid ei kasuta wp-admin paneeli — neil on oma eraldi portaalid.</div>
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
        <div class="vd-h3">Muud töölaua elemendid</div>
        <div class="vd-g3">
          <div class="vd-c"><h4>Tänased hooldused</h4><p>Nimekiri tänaseks planeeritud hooldustest — klient, seade, töötaja, staatus.</p></div>
          <div class="vd-c"><h4>Meeldetuletused</h4><p>Seadmed mille hooldus tuleb järgmise 30 päeva jooksul (arvestatakse intervallist).</p></div>
          <div class="vd-c"><h4>Tulud (graafik)</h4><p>Viimase 6 kuu tasutud arvetest tulupd tulpdiagrammil.</p></div>
        </div>
      </div>

      <div class="vd-sec" id="kliendid">
        <div class="vd-sec-h"><span class="ic cyan">👥</span>Kliendid</div>
        <div class="vd-h3">Kliendi lisamine</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Kliendid → "+ Lisa klient"</strong><p>Avaneb modaalaken.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali tüüp: Eraisik või Firma</strong><p>Firma puhul ilmuvad lisaväljad: registrikood ja KMKR — kuvatakse PDF arvedel.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Täida nimi ja e-post (kohustuslik)</strong><p>E-post on vajalik portaali ligipääsuks.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Salvesta → vajuta "🔑 Ligipääs"</strong><p>Süsteem genereerib parooli ja saadab kliendile emailiga.</p></div></div>
        </div>
        <div class="vd-warn"><b>Tähelepanu</b>Parool kuvatakse ainult emailis — süsteem ei salvesta seda lihttekstina. Uue parooli saad alati uuesti genereerida.</div>
        <div class="vd-h3">Kliendi detailvaate vahekaardid</div>
        <table class="vd-t">
          <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
          <tbody>
            <tr><td>Ülevaade</td><td>Seadmete arv, avatud töökäsud, maksmata arved</td></tr>
            <tr><td>Seadmed</td><td>Seadmete nimekiri, hooldusajalugu, järgmine hooldus</td></tr>
            <tr><td>Hooldused</td><td>Kõik planeeritud ja tehtud hooldused</td></tr>
            <tr><td>Arved</td><td>Kõik arved ja maksestaatused</td></tr>
            <tr><td>Märkmed</td><td>Vabas vormis sisemised märkmed (ainult adminile)</td></tr>
          </tbody>
        </table>
        <div class="vd-h3">Külaliste taotlused</div>
        <p class="vd-p">Kui külaline täidab veebilehel kontaktvormi, ilmub taotlus <strong>Kliendid → 👤 Külaliste taotlused</strong>. Sealt saad ühe klõpsuga lisada külalise kliendiks.</p>
        <div class="vd-tip"><b>Kiirotsing</b>Klientide, töötajate ja arvete otsingukast otsib automaatselt kirjutades — ei pea nuppu vajutama.</div>
      </div>

      <div class="vd-sec" id="seadmed">
        <div class="vd-sec-h"><span class="ic cyan">🔧</span>Seadmed</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava klient → Seadmed → "+ Lisa seade"</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida: nimi, mudel, seerianumber, paigaldusaasta</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Määra hooldusintervall (kuud)</strong><p>Süsteem arvutab järgmise hoolduse kuupäeva automaatselt.</p></div></div>
        </div>
        <div class="vd-tip"><b>Nõuanne</b>Lisa seadmele pilt ja dokumentide lingid märkmete väljale — klient näeb neid portaalis.</div>
      </div>

      <div class="vd-sec" id="hooldused">
        <div class="vd-sec-h"><span class="ic cyan">📅</span>Hooldused</div>
        <div class="vd-h3">Hoolduse planeerimine</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Hooldused → "+ Lisa hooldus"</strong><p>Või kliki kalendris kuupäeval.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient → seade → töötaja → kuupäev</strong><p>Seadme valimisel täitub automaatselt eelmine hooldus ja järgmise soovitus.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Salvesta</strong><p>Töötaja näeb hooldust portaalis kohe.</p></div></div>
        </div>
        <div class="vd-h3">Staatused</div>
        <div class="vd-g4">
          <div class="vd-c"><h4><span class="vb vb-b">Planeeritud</span></h4><p>Ajakavasse lisatud, töötajale määratud.</p></div>
          <div class="vd-c"><h4><span class="vb vb-g">Tehtud</span></h4><p>Töötaja on lõpetatuks märkinud.</p></div>
          <div class="vd-c"><h4><span class="vb vb-gray">Tühistatud</span></h4><p>Ei toimu, jääb logi jaoks.</p></div>
          <div class="vd-c"><h4><span class="vb vb-r">Hilinenud</span></h4><p>Tähtaeg möödas, pole tehtud.</p></div>
        </div>
      </div>

      <div class="vd-sec" id="tookasud">
        <div class="vd-sec-h"><span class="ic cyan">📋</span>Töökäsud</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töökäsud → "+ Lisa töökäsk"</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient, seade, töötaja, prioriteet, tähtaeg</strong><p>Prioriteedid: Madal / Keskmine / Kõrge / Kriitline.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa kirjeldus → Salvesta</strong><p>Töötaja näeb töökäsku kohe portaalis.</p></div></div>
        </div>
        <div class="vd-h3">Töövoog</div>
        <div class="vd-flow">
          <div class="vd-fs">Uus</div><div class="vd-fa">→</div>
          <div class="vd-fs">Määratud</div><div class="vd-fa">→</div>
          <div class="vd-fs">Töös</div><div class="vd-fa">→</div>
          <div class="vd-fs">Lõpetatud</div>
        </div>
        <div class="vd-tip"><b>Lõpetamisel</b>Töötaja saab lisada: kommentaari, kasutatud materjalid laost, fotod. Admin näeb kõiki andmeid töökäsu detailvaates.</div>
        <div class="vd-h3">Kiire staatuse muutmine</div>
        <p class="vd-p">Töökäsude nimekirjas on staatus muudetav otse ripploendist — kliki staatusel, vali uus. Muutus salvestub koheselt ilma lehte laadimata.</p>
      </div>

      <div class="vd-sec" id="tootajad">
        <div class="vd-sec-h"><span class="ic cyan">👷</span>Töötajad</div>
        <div class="vd-h3">Töötaja lisamine</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Töötajad → "+ Lisa töötaja"</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Täida nimi, telefon, e-post</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Salvesta</strong><p>Süsteem loob WP kasutaja rolliga <code>vesho_worker</code> ja saadab kutsemeili.</p></div></div>
        </div>
        <div class="vd-h3">Töötaja aktiveerimine / deaktiveerimine</div>
        <p class="vd-p">Töötajate nimekirjas on iga rea lõpus <strong>🟢 / ⚫ nupp</strong> — vajutades muutub töötaja staatus kohe (ei laadi lehte uuesti). Mitteaktiivne töötaja ei saa portaali sisse logida.</p>
        <div class="vd-h3">Töötunnid ja töötasud</div>
        <p class="vd-p">Vesho CRM → <strong>Töötunnid</strong> — töötaja logib portaalis alguse ja lõpu aja. Admin näeb kokkuvõtet perioodi kaupa. Töötajale saab määrata tunnitasu — raport arvutab töötasud automaatselt.</p>
      </div>

    </div><!-- /panel-admin -->

    <!-- ═══════════════════════════════════════════════════ ARVELDUS & LADU -->
    <div class="vd-panel" id="panel-arveldus">

      <div class="vd-sec" id="arved">
        <div class="vd-sec-h"><span class="ic cyan">🧾</span>Arved ja PDF</div>
        <div class="vd-tip"><b>KM-määr</b>Praegu kehtiv KM-määr on <strong><?php echo esc_html($vat); ?>%</strong>. Muutmiseks: <a href="<?php echo esc_url(admin_url('admin.php?page=vesho-crm-settings')); ?>" style="color:#14532d;font-weight:600">Seaded → KM-määr</a>.</div>
        <div class="vd-h3">Arve koostamine</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Arved → "+ Lisa arve"</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali klient</strong><p>Firma klientide puhul täituvad registrikood ja KMKR automaatselt.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Lisa read: kirjeldus, kogus, ühikuhind</strong><p>KM (<?php echo esc_html($vat); ?>%) ja kogusumma arvutatakse automaatselt.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">4</div><div class="vd-step-b"><strong>Määra maksetähtaeg (vaikimisi 14 päeva)</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">5</div><div class="vd-step-b"><strong>Salvesta ja saada e-postiga</strong><p>Arve saadetakse kliendile ja ilmub portaalis.</p></div></div>
        </div>
        <div class="vd-h3">Staatused</div>
        <table class="vd-t">
          <thead><tr><th>Staatus</th><th>Portaalis nähtav</th><th>Arvestub tuludes</th></tr></thead>
          <tbody>
            <tr><td><span class="vb vb-gray">Mustand</span></td><td>Ei</td><td>Ei</td></tr>
            <tr><td><span class="vb vb-b">Saadetud</span></td><td>Jah</td><td>Ei</td></tr>
            <tr><td><span class="vb vb-g">Tasutud</span></td><td>Jah</td><td>Jah</td></tr>
            <tr><td><span class="vb vb-r">Tähtaeg ületatud</span></td><td>Jah (punane)</td><td>Ei</td></tr>
          </tbody>
        </table>
        <div class="vd-h3">PDF printimine</div>
        <p class="vd-p">Ava arve → "🖨️ Prindi / PDF" → avaneb printimisvaade. PDF salvestamiseks vali printeriks "Salvesta PDF-ina" (Chrome/Edge).</p>
        <div class="vd-h3">Kiired toimingud nimekirjas</div>
        <div class="vd-g3">
          <div class="vd-c"><h4>📋 Kopeeri uueks</h4><p>Iga arve juures on 📋 nupp — loob mustandkoopia täna kuupäevaga. Kasulik korduvarvete jaoks.</p></div>
          <div class="vd-c"><h4>✉️ Saada e-post</h4><p>Saada arve kliendile otse nimekirja nupust, ilma arvet avamata.</p></div>
          <div class="vd-c"><h4>KARV Kreeditarve</h4><p>Tasutud/saadetud arve juures — loo kreeditarve summa ja põhjusega.</p></div>
        </div>
      </div>

      <div class="vd-sec" id="ladu">
        <div class="vd-sec-h"><span class="ic cyan">📦</span>Ladu</div>
        <div class="vd-h3">Laoseis</div>
        <p class="vd-p">Vesho CRM → <strong>Ladu</strong> — kõik kaubad koos koguse, miinimumkoguse ja asukohaga. Punane rida = kogus alla miinimumi. Töölaud kuvab laopuuduse hoiatuse automaatselt.</p>
        <div class="vd-h3">Kauba vastuvõtt</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Vastuvõtt → "+ Uus vastuvõtt"</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Vali tarnija, lisa kaubad ja kogused</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Kinnita vastuvõtt</strong><p>Laokogused uuenevad automaatselt.</p></div></div>
        </div>
        <div class="vd-h3">Ostutellimused</div>
        <p class="vd-p">Vesho CRM → <strong>Ostutellimused</strong> — loo ostutellimus tarnijale kui ladu tühjeneb. Vastuvõtmisel suletakse ostutellimus automaatselt.</p>
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
            </ul>
          </div>
          <div class="vd-c">
            <h4>Veebilehe hinnakiri</h4>
            <ul>
              <li>Kõik avalikud: <span class="sc">[vesho_price_list]</span></li>
              <li>Kategooria järgi: <span class="sc">[vesho_price_list category="X"]</span></li>
              <li>Lisa Elementori "Shortcode" widgetiga</li>
            </ul>
          </div>
          <div class="vd-c">
            <h4>E-poe tellimused</h4>
            <ul>
              <li>Tellimused: Vesho CRM → Tellimused</li>
              <li>Staatused: Uus → Töötlemisel → Saadetud → Lõpetatud</li>
            </ul>
          </div>
          <div class="vd-c">
            <h4>Kampaaniad</h4>
            <ul>
              <li>Vesho CRM → Kampaaniad</li>
              <li>Seo tootega, määra % ja kehtivusaeg</li>
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
        <table class="vd-t">
          <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
          <tbody>
            <tr><td>Ülevaade</td><td>Aktiivsed töökäsud, tänased tööd, lähimad hooldused</td></tr>
            <tr><td>Töökäsud</td><td>Muuda staatust, lisa kommentaar, märgi materjalid, lisad fotod</td></tr>
            <tr><td>Hooldused</td><td>Tänased ja tulevased hooldused, lõpetamise kinnitamine</td></tr>
            <tr><td>Töötunnid</td><td>Logi töö algus ja lõpp. Kuu kokkuvõte.</td></tr>
            <tr><td>Seaded</td><td>Parooli muutmine, teavitused</td></tr>
          </tbody>
        </table>
        <div class="vd-h3">Mobiilis avakuvale paigaldamine (PWA)</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Ava Chrome mobiilis → /worker/</strong><p></p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Aadressiribal ⊕ → "Lisa avakuvale"</strong><p>Portaali ikoon ilmub avakuvale — töötab nagu äpp, täisekraanil.</p></div></div>
        </div>
      </div>

      <div class="vd-sec" id="kliendi-portaal">
        <div class="vd-sec-h"><span class="ic cyan">🌐</span>Kliendi portaal — <a href="<?php echo esc_url($site_url.'/klient/'); ?>" target="_blank" style="font-size:12px;font-weight:400;color:#00b4c8"><?php echo esc_html($site_url); ?>/klient/</a></div>
        <table class="vd-t">
          <thead><tr><th>Vahekaart</th><th>Sisu</th></tr></thead>
          <tbody>
            <tr><td>Ülevaade</td><td>Järgmine hooldus, tasumata arved, aktiivsed seadmed</td></tr>
            <tr><td>Seadmed</td><td>Seadmed, hooldusajalugu, järgmine hooldus</td></tr>
            <tr><td>Hooldused</td><td>Tulevased ja tehtud hooldused</td></tr>
            <tr><td>Arved</td><td>Kõik arved, PDF allalaadimine, punane = maksmata</td></tr>
            <tr><td>Pood</td><td>Toodete kataloog, tellimine (kui aktiveeritud)</td></tr>
            <tr><td>Minu tellimused</td><td>E-poe tellimuste ajalugu ja staatused</td></tr>
            <tr><td>Tugi</td><td>Tugipileti saatmine → CRM: Vesho CRM → Tugipiletid</td></tr>
            <tr><td>Broneeri</td><td>Hoolduse soov → CRM: Päringud</td></tr>
          </tbody>
        </table>
        <div class="vd-note"><b>Portaali bännerteade</b>Vesho CRM → <strong>Portaali teated</strong> — lisa teade kõigile sisselogitud klientidele (nt hinnamuutus, pühadel suletud). Seadistatav algus- ja lõppkuupäev.</div>
      </div>

    </div><!-- /panel-portaalid -->

    <!-- ═══════════════════════════════════════════════════════ VEEBILEHT -->
    <div class="vd-panel" id="panel-veebileht">

      <div class="vd-sec" id="elementor">
        <div class="vd-sec-h"><span class="ic cyan">🎨</span>Elementor — lehe redigeerimine</div>
        <div class="vd-warn"><b>Enne muutusi</b>Tee varukoopia: WordPress Admin → Tööriistad → Eksport. Või UpdraftPlus automaatseks varundamiseks.</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>WordPress Admin → Lehed → hõljuta lehel</strong><p>Kliki "Redigeeri Elementoriga".</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Klõpsa elemendil → vasakpaneel avab seaded</strong><p>Teksti saab muuta otse klõpsates.</p></div></div>
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
      </div>

      <div class="vd-sec" id="shortcodes">
        <div class="vd-sec-h"><span class="ic">〈/〉</span>Shortcode'id</div>
        <table class="vd-t">
          <thead><tr><th>Shortcode</th><th>Kirjeldus</th></tr></thead>
          <tbody>
            <tr><td><span class="sc">[vesho_client_portal]</span></td><td>Kliendi portaal — lehe /klient/ sisu. Ära muuda.</td></tr>
            <tr><td><span class="sc">[vesho_worker_portal]</span></td><td>Töötaja portaal — lehe /worker/ sisu. Ära muuda.</td></tr>
            <tr><td><span class="sc">[vesho_price_list]</span></td><td>Kõik avalikud tooted ja teenused</td></tr>
            <tr><td><span class="sc">[vesho_price_list category="X"]</span></td><td>Hinnakiri filtreeritud kategooria järgi</td></tr>
            <tr><td><span class="sc">[vesho_shop]</span></td><td>E-poe kaupade kuvamine</td></tr>
          </tbody>
        </table>
      </div>

    </div><!-- /panel-veebileht -->

    <!-- ═════════════════════════════════════════════════════════ SEADED -->
    <div class="vd-panel" id="panel-seaded">

      <div class="vd-sec" id="seaded">
        <div class="vd-sec-h"><span class="ic cyan">⚙️</span>Üldseaded</div>
        <p class="vd-p">Vesho CRM → <strong>Seaded</strong>. Muutused mõjutavad kõiki PDF-e, e-kirju ja portaale.</p>
        <div class="vd-g3">
          <div class="vd-c"><h4>Firma andmed</h4><ul><li>Nimi, registrikood, KMKR</li><li>Aadress, telefon, e-post</li><li>Kuvatakse PDF arvedel</li></ul></div>
          <div class="vd-c"><h4>KM-määr (praegu <?php echo esc_html($vat); ?>%)</h4><ul><li>Mõjutab uusi arveid</li><li>Vanad arved ei muutu</li></ul></div>
          <div class="vd-c"><h4>Logo PDF-il</h4><ul><li>PNG läbipaistva taustaga</li><li>Ideaalne: 300×100 px</li></ul></div>
          <div class="vd-c"><h4>Arve nummerdamine</h4><ul><li>Prefiks: nt <code>VESHO-2024-</code></li><li>Määra enne esimest arvet!</li></ul></div>
          <div class="vd-c"><h4>Pangaandmed</h4><ul><li>IBAN, pank, selgituse tekst</li><li>Kuvatakse PDF allosas</li></ul></div>
          <div class="vd-c"><h4>Administraatorid</h4><ul><li>Anna CRM ligipääs teisele kasutajale</li><li>Roll: <code>vesho_crm_admin</code></li></ul></div>
        </div>
      </div>

      <div class="vd-sec" id="email-seaded">
        <div class="vd-sec-h"><span class="ic cyan">📧</span>E-post ja teavitused</div>
        <div class="vd-h3">SMTP seadistamine</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → Seaded → E-posti seaded</strong><p>Täida SMTP server, port, kasutajanimi, parool, saatja nimi.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>"Saada testmeil"</strong><p>Kontrollib kas seaded toimivad.</p></div></div>
        </div>
        <div class="vd-h3">Automaatsed teavitused</div>
        <table class="vd-t">
          <thead><tr><th>Sündmus</th><th>Saaja</th><th>Automaatne?</th></tr></thead>
          <tbody>
            <tr><td>Portaali ligipääs genereeritud</td><td>Klient</td><td>Jah</td></tr>
            <tr><td>Arve saatmine</td><td>Klient</td><td>Admin vajutab "Saada"</td></tr>
            <tr><td>Töötaja kutse</td><td>Töötaja</td><td>Jah (lisamisel)</td></tr>
            <tr><td>Töökäsu määramine</td><td>Töötaja</td><td>Valikuline</td></tr>
          </tbody>
        </table>
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
          <div class="vd-fs">Külaliste taotlused</div><div class="vd-fa">→</div>
          <div class="vd-fs">"Lisa kliendiks"</div><div class="vd-fa">→</div>
          <div class="vd-fs">Saada portaali ligipääs</div><div class="vd-fa">→</div>
          <div class="vd-fs">Planeeri hooldus</div>
        </div>

        <div class="vd-h3">Hoolduse kiire lõpetamine kalendrist</div>
        <p class="vd-p">Kliki kalendris hooldusel → avaneb modal → vajuta <strong>✓ Tehtud</strong> — hooldus märgitakse tehtuks ja kuupäev salvestatakse kohe. Ei pea avama hoolduse muutmislehte.</p>

        <div class="vd-h3">Igakuine hoolduste planeerimine</div>
        <div class="vd-flow">
          <div class="vd-fs">Töölaud → Meeldetuletused</div><div class="vd-fa">→</div>
          <div class="vd-fs">Vali seadmed nimekirjast</div><div class="vd-fa">→</div>
          <div class="vd-fs">Lisa hooldused kalendrisse</div><div class="vd-fa">→</div>
          <div class="vd-fs">Marsruut optimeerib</div><div class="vd-fa">→</div>
          <div class="vd-fs">Töötajad saavad teavituse</div>
        </div>

        <div class="vd-h3">Laopuudus → ostutellimus</div>
        <div class="vd-flow">
          <div class="vd-fs">Töölaud hoiatab</div><div class="vd-fa">→</div>
          <div class="vd-fs">Ladu → vaata puuduolevad</div><div class="vd-fa">→</div>
          <div class="vd-fs">Loo ostutellimus</div><div class="vd-fa">→</div>
          <div class="vd-fs">Kaup saabub → Vastuvõtt</div><div class="vd-fa">→</div>
          <div class="vd-fs">Laoseis uueneb</div>
        </div>

        <div class="vd-h3">Kiire töökäsk (rikke korral)</div>
        <div class="vd-flow">
          <div class="vd-fs">Lisa töökäsk (Kriitline)</div><div class="vd-fa">→</div>
          <div class="vd-fs">Määra töötaja</div><div class="vd-fa">→</div>
          <div class="vd-fs">Töötaja saab teavituse</div><div class="vd-fa">→</div>
          <div class="vd-fs">Tehtuks märkimine</div><div class="vd-fa">→</div>
          <div class="vd-fs">Koosta arve</div>
        </div>
      </div>
    </div><!-- /panel-toovood -->

    <!-- ══════════════════════════════════════════════════════ SÜSTEEM -->
    <!-- ══════════════════════════════════════════════════════ UUENDUSED -->
    <div class="vd-panel" id="panel-uuendused">

      <div class="vd-sec" id="uuendused">
        <div class="vd-sec-h"><span class="ic cyan">🚀</span>Uuendused</div>
        <div class="vd-tip"><b>Paigaldatud</b>Vesho CRM <strong>v<?php echo esc_html($plugin_ver); ?></strong> · Teema <strong>v<?php echo esc_html($theme_ver); ?></strong></div>
        <div class="vd-h3">v2.9.54 — Uued funktsioonid</div>
        <div class="vd-g3">
          <div class="vd-c"><h4>🎫 Tugipiletid: split-vaade</h4><p>Piletite leht on nüüd jagatud vaateks: vasak paneel näitab nimekirja, parem paneel laab valitud pileti detailid AJAX-iga — lehte ei laadita uuesti.</p></div>
          <div class="vd-c"><h4>💬 Vastamine ja staatuse muutmine kohapeal</h4><p>Pileti detailpaneelis saad saata vastuse ja muuta staatust otse — ilma eraldi vaateta. Vastus ilmub sõnumite ahelasse koheselt.</p></div>
          <div class="vd-c"><h4>🕐 Töötunnid: kellaajavahekaart</h4><p>Töötundide lehel on nüüd teine vahekaart "Kellaaeg sisse/välja" — näitab aktiivseid ja lõpetatud kellaajasisestusi.</p></div>
          <div class="vd-c"><h4>📊 Töötunnide kokkuvõttekaardid</h4><p>Töötundide lehe ülaosas on kolm kokkuvõttekaarti: nädala tunnid, kuu tunnid ja praegu töös olevad töötajad.</p></div>
          <div class="vd-c"><h4>📷 Töökäskude fotod nimekirjast</h4><p>Töökäskude nimekirjas on 📷 nupp iga rea juures. Klõpsa, et laadida fotod AJAX-iga ridade vahele — enne/pärast/muu rühmitusega.</p></div>
          <div class="vd-c"><h4>🧾 KM lüliti arvete vormil</h4><p>Arve ridade tabeli kohal on "KM-ga / KM-ta" nupp — lülitab kõik read korraga. KM väärtused taastatakse tagasilülitamisel.</p></div>
          <div class="vd-c"><h4>🔍 Kliendi automaatne täitmine arvetel</h4><p>Arve vorme saab kliendi leida kiirelt nimeotsinguga — kirjuta nimi, vali soovitus. Ei tarvitse enam kerida pikka nimekirja.</p></div>
        </div>
        <div class="vd-h3">v2.9.53 — Uued funktsioonid</div>
        <div class="vd-g3">
          <div class="vd-c"><h4>📋 Broneeringute kinnitamine töölaual</h4><p>Töölaud näitab nüüd iga ootava broneeringu eraldi reana — kinnita ✓ või lükka tagasi ✕ ilma lehelt lahkumata.</p></div>
          <div class="vd-c"><h4>⏱️ Nädalatunnid töölaual</h4><p>Uus kaart näitab töötajate selle nädala töötunde jooksvalt.</p></div>
          <div class="vd-c"><h4>🗺️ 7-päeva eelvaade (marsruut)</h4><p>Marsruudi lehel on nüüd nupuriba järgmise 7 päeva hoolduste arvudega — klõpsa päeval et otse laadida.</p></div>
          <div class="vd-c"><h4>🎉 Kampaaniate AJAX-lüliti</h4><p>Kampaaniate nimekirjas saab peata/jätka-nupuga lülitada ilma lehelaadimiseta. Lisandus ka kustuta-nupp.</p></div>
          <div class="vd-c"><h4>📦 Lao koguse kiirmuutmine</h4><p>Laonimekirjas klõpsa koguse lahtrile — muutub otse redigeeritavaks. Enter salvestab, Escape tühistab.</p></div>
          <div class="vd-c"><h4>📊 Müügiraporti tooltip</h4><p>Käibegraafikul hiirega üle baaride liikudes kuvatakse täpne summa.</p></div>
        </div>
        <div class="vd-h3">v2.9.52 — Uued funktsioonid</div>
        <div class="vd-g3">
          <div class="vd-c"><h4>✓ Tehtud (kalender)</h4><p>Kalendris hooldusel klõpsates saab selle otse tehtuks märkida — ilma muutmisleheta.</p></div>
          <div class="vd-c"><h4>Töökäsu staatuse muutmine</h4><p>Töökäsude nimekirjas saab staatust muuta otse rippmenüüst — salvestub koheselt.</p></div>
          <div class="vd-c"><h4>Töötaja aktiveerimine</h4><p>🟢/⚫ nupuga saab töötaja aktiveerida/deaktiveerida otse nimekirjast.</p></div>
          <div class="vd-c"><h4>Arve kopeerimine</h4><p>📋 nupp arve nimekirjas — loob mustandkoopia täna kuupäevaga.</p></div>
          <div class="vd-c"><h4>Kiirotsing</h4><p>Kliendid, töötajad, arved ja töökäsud otsivad automaatselt kirjutades.</p></div>
          <div class="vd-c"><h4>Kasutusjuhend uuendatud</h4><p>Kõik uued funktsioonid on dokumenteeritud — sh lühijuhised ja töövood.</p></div>
        </div>
        <div class="vd-h3">CRM pistikprogramm ja teema</div>
        <div class="vd-steps">
          <div class="vd-step"><div class="vd-step-n">1</div><div class="vd-step-b"><strong>Vesho CRM → 🚀 Uuendused</strong><p>Kontrollib uut versiooni. Muudatuste logi näitab mis parandati.</p></div></div>
          <div class="vd-step"><div class="vd-step-n">2</div><div class="vd-step-b"><strong>Klõpsa "Uuenda"</strong><p>Laeb alla GitHubist, paigaldab automaatselt (&lt; 10 sek).</p></div></div>
          <div class="vd-step"><div class="vd-step-n">3</div><div class="vd-step-b"><strong>Uuendus ei ilmu?</strong><p>Mine <a href="<?php echo esc_url(admin_url('update-core.php?force-check=1')); ?>" style="color:#00b4c8">siia</a> — tühjendab WordPress uuenduste puhvri.</p></div></div>
        </div>
        <div class="vd-h3">WordPress core</div>
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
  var tabs    = document.querySelectorAll('.vd-tab');
  var panels  = document.querySelectorAll('.vd-panel');
  var tocDiv  = document.getElementById('vdTocLinks');

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
        // also remove any link children text
        var links = clone.querySelectorAll('a');
        links.forEach(function(l){ l.remove(); });
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

  /* Restore from hash */
  var hash = location.hash.replace('#','');
  if(hash){
    var found = false;
    tabs.forEach(function(t){ if(t.dataset.panel===hash){ activateTab(t); found=true; } });
    if(!found) buildToc('alustamine');
  } else {
    buildToc('alustamine');
  }

  /* Scroll spy for TOC */
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
