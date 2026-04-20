<?php defined('ABSPATH') || exit;
$site_url   = get_site_url();
$admin_url  = admin_url();
$plugin_ver = VESHO_CRM_VERSION;
$theme_ver  = wp_get_theme()->get('Version');
$php_ver    = PHP_VERSION;
$db_ver     = $GLOBALS['wpdb']->db_version();
$wp_ver     = get_bloginfo('version');
$vat        = get_option('vesho_vat_rate', '24');
?>
<style>
/* ── Docs layout ──────────────────────────────────────────────────────────── */
.vdocs-wrap{display:flex;gap:28px;max-width:1100px;margin:0 auto;padding:24px 8px 64px;align-items:flex-start}
.vdocs-toc{width:220px;flex-shrink:0;position:sticky;top:calc(var(--wp-admin--admin-bar--height,32px) + 16px);max-height:calc(100vh - 80px);overflow-y:auto}
.vdocs-toc-inner{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px 0}
.vdocs-toc-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;padding:0 16px 8px}
.vdocs-toc a{display:flex;align-items:center;gap:7px;padding:6px 16px;font-size:12px;color:#475569;text-decoration:none;border-left:2px solid transparent;transition:.15s}
.vdocs-toc a:hover{background:#f8fafc;color:#1e293b}
.vdocs-toc a.active{background:#f0fdff;color:#00b4c8;border-left-color:#00b4c8;font-weight:600}
.vdocs-main{flex:1;min-width:0}

/* ── Sections ──────────────────────────────────────────────────────────────── */
.vdocs-section{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:24px;overflow:hidden;scroll-margin-top:80px}
.vdocs-section-head{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px}
.vdocs-section-head h2{margin:0;font-size:16px;font-weight:700;color:#1e293b}
.vdocs-section-head .ico{font-size:20px}
.vdocs-section-desc{padding:0 22px 4px;margin-top:14px;font-size:13px;color:#475569;line-height:1.6}
.vdocs-body{padding:16px 22px 22px}

/* ── Grid cards ─────────────────────────────────────────────────────────── */
.vdocs-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.vdocs-grid-3{grid-template-columns:1fr 1fr 1fr}
@media(max-width:700px){.vdocs-grid,.vdocs-grid-3{grid-template-columns:1fr}.vdocs-toc{display:none}}
.vdocs-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px}
.vdocs-card h3{margin:0 0 6px;font-size:13px;font-weight:700;color:#1e293b}
.vdocs-card p{margin:0 0 6px;font-size:13px;color:#475569;line-height:1.55}
.vdocs-card ul{margin:4px 0 0;padding-left:18px;font-size:13px;color:#475569;line-height:1.8}
.vdocs-card-url{display:inline-block;margin-top:8px;font-size:11px;font-weight:700;background:#1e293b;color:#fff;border-radius:5px;padding:2px 8px;text-decoration:none;font-family:monospace;letter-spacing:.5px}

/* ── Portal cards ────────────────────────────────────────────────────────── */
.vdocs-portals{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin:14px 0}
.vdocs-portal-card{border:1px solid #e2e8f0;border-radius:10px;padding:16px;text-align:center;text-decoration:none;color:inherit;transition:.15s}
.vdocs-portal-card:hover{border-color:#00b4c8;background:#f0fdff}
.vdocs-portal-card .portal-icon{font-size:28px;margin-bottom:8px}
.vdocs-portal-card h3{margin:0 0 4px;font-size:13px;font-weight:700;color:#1e293b}
.vdocs-portal-card p{margin:0 0 8px;font-size:12px;color:#64748b;line-height:1.4}
.vdocs-portal-card code{display:inline-block;font-size:11px;background:#f1f5f9;border-radius:4px;padding:1px 7px;color:#475569}

/* ── Steps ─────────────────────────────────────────────────────────────── */
.vdocs-steps{margin:12px 0}
.vdocs-step{display:flex;gap:12px;margin-bottom:10px}
.vdocs-step:last-child{margin-bottom:0}
.vdocs-step-n{width:24px;height:24px;border-radius:50%;background:#00b4c8;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.vdocs-step-b strong{font-size:13px;color:#1e293b;display:block;margin-bottom:1px}
.vdocs-step-b p{margin:0;font-size:13px;color:#475569;line-height:1.5}

/* ── Info boxes ──────────────────────────────────────────────────────────── */
.vdocs-info{display:flex;gap:10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:13px;color:#1d4ed8;margin:10px 0;line-height:1.5}
.vdocs-warn{display:flex;gap:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:13px;color:#92400e;margin:10px 0;line-height:1.5}
.vdocs-tip{display:flex;gap:10px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;padding:10px 14px;font-size:13px;color:#065f46;margin:10px 0;line-height:1.5}
.vdocs-info .ico,.vdocs-warn .ico,.vdocs-tip .ico{font-size:16px;flex-shrink:0;margin-top:1px}

/* ── KPI table ───────────────────────────────────────────────────────────── */
.vdocs-kpi-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0}
.vdocs-kpi-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px}
.vdocs-kpi-item strong{display:block;font-size:13px;color:#1e293b;margin-bottom:2px}
.vdocs-kpi-item span{font-size:12px;color:#64748b;line-height:1.4}

/* ── Sub heading ─────────────────────────────────────────────────────────── */
.vdocs-h3{font-size:14px;font-weight:700;color:#1e293b;margin:18px 0 8px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
.vdocs-h3:first-child{margin-top:0}

/* ── Sysinfo ─────────────────────────────────────────────────────────────── */
.vdocs-sysinfo{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin:12px 0}
.vdocs-sys-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px}
.vdocs-sys-item .lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:3px}
.vdocs-sys-item .val{font-size:13px;font-weight:700;color:#1e293b;word-break:break-all}

/* ── Workflow ─────────────────────────────────────────────────────────────── */
.vdocs-flow{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:10px 0}
.vdocs-flow-step{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;color:#1e293b}
.vdocs-flow-arrow{color:#94a3b8;font-size:14px;font-weight:700}

/* ── Links ───────────────────────────────────────────────────────────────── */
a.vdocs-btn{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:12px;font-weight:600;color:#00b4c8;text-decoration:none}
a.vdocs-btn:hover{color:#008fa0}
code.vdocs-code{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px;font-size:12px;color:#1e293b}
</style>

<div class="crm-page-header">
  <div class="crm-page-header__logo">
    <svg width="26" height="26" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#fff"/>
    </svg>
  </div>
  <div class="crm-page-header__body">
    <h1 class="crm-page-header__title">📖 Kasutusjuhend</h1>
    <p class="crm-page-header__subtitle">Vesho CRM täielik juhend omanikule ja administraatorile</p>
  </div>
  <div class="crm-page-header__actions">
    <a href="<?php echo admin_url('admin.php?page=vesho-crm'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">← Töölaud</a>
  </div>
</div>

<div class="vdocs-wrap">

  <!-- ── SISUKORD ─────────────────────────────────────────────────────────── -->
  <nav class="vdocs-toc">
    <div class="vdocs-toc-inner">
      <div class="vdocs-toc-title">Sisukord</div>
      <a href="#ylevaade">📖 Ülevaade</a>
      <a href="#toolaud">🏠 Töölaud</a>
      <a href="#kliendid">👥 Kliendid ja seadmed</a>
      <a href="#hooldused">📅 Kalender ja hooldused</a>
      <a href="#tookasud">🔧 Töökäsud</a>
      <a href="#tootajad">👷 Töötajad</a>
      <a href="#arved">🧾 Arved ja PDF</a>
      <a href="#ladu">📦 Ladu</a>
      <a href="#hinnakiri">💰 Hinnakiri</a>
      <a href="#seaded">⚙️ Seaded</a>
      <a href="#veebileht">🎨 Veebileht</a>
      <a href="#tootaja-portaal">👷 Töötaja portaal</a>
      <a href="#kliendi-portaal">🌐 Kliendi portaal</a>
      <a href="#toovood">🔄 Tüüpilised töövood</a>
      <a href="#uuendused">🚀 Uuendused</a>
      <a href="#sysinfo">🖥️ Süsteemi info</a>
    </div>
  </nav>

  <!-- ── PÕHISISU ──────────────────────────────────────────────────────────── -->
  <div class="vdocs-main">

    <!-- ÜLEVAADE -->
    <div class="vdocs-section" id="ylevaade">
      <div class="vdocs-section-head"><span class="ico">📖</span><h2>Ülevaade</h2></div>
      <div class="vdocs-body">
        <p class="vdocs-section-desc" style="margin:0 0 14px">Vesho CRM on Vesho OÜ jaoks ehitatud terviklahendus hooldusäri juhtimiseks WordPress'i peal. Kõik on ühes kohas: kliendid, seadmed, hooldusgraafik, töökäsud, töötajad, arved ja ladu.</p>

        <div class="vdocs-h3">Kolm eraldi portaali</div>
        <div class="vdocs-portals">
          <a class="vdocs-portal-card" href="<?php echo $admin_url; ?>admin.php?page=vesho-crm" target="_blank">
            <div class="portal-icon">🖥️</div>
            <h3>Admin paneel</h3>
            <p>Kogu äri haldamine — kliendid, arved, töökäsud, seaded</p>
            <code>/wp-admin</code>
          </a>
          <a class="vdocs-portal-card" href="<?php echo $site_url; ?>/worker/" target="_blank">
            <div class="portal-icon">👷</div>
            <h3>Töötaja portaal</h3>
            <p>Töötaja vaatab ja lõpetab oma töökäske, lisab materjale</p>
            <code>/worker/</code>
          </a>
          <a class="vdocs-portal-card" href="<?php echo $site_url; ?>/klient/" target="_blank">
            <div class="portal-icon">🌐</div>
            <h3>Kliendi portaal</h3>
            <p>Klient näeb oma seadmeid, arveid, saab tellida teenust</p>
            <code>/klient/</code>
          </a>
        </div>

        <div class="vdocs-h3">Sisselogimine admin paneeli</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">WordPress admin on aadressil <code class="vdocs-code"><?php echo $site_url; ?>/wp-admin/</code>. Logi sisse oma WordPress administraatori kontoga.</p>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Iga töötaja logib sisse oma portaali <code class="vdocs-code">/worker/</code> — nemad WordPress admin paneeli ei kasuta.</span></div>
      </div>
    </div>

    <!-- TÖÖLAUD -->
    <div class="vdocs-section" id="toolaud">
      <div class="vdocs-section-head"><span class="ico">🏠</span><h2>Töölaud</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Töölaud on avaleht mida näed kohe pärast sisselogimist. See annab kiire ülevaate hetkeolukorrast.</p>

        <div class="vdocs-h3">KPI kaardid (ülareas)</div>
        <div class="vdocs-kpi-grid">
          <div class="vdocs-kpi-item"><strong>👥 Aktiivsed kliendid</strong><span>Kõigi aktiivsete klientide koguarv. Klõps viib klientide lehele.</span></div>
          <div class="vdocs-kpi-item"><strong>⚡ Töös praegu</strong><span>Hetkel "In progress" staatuses töökäsud. Klõps viib töökäskude lehele.</span></div>
          <div class="vdocs-kpi-item"><strong>🔧 Hooldusi täna</strong><span>Tänaseks planeeritud hoolduste arv. Klõps viib kalenderisse.</span></div>
          <div class="vdocs-kpi-item"><strong>🧾 Arved ootel</strong><span>Tasumata arvete arv ja summa. Punane = tähtaeg ületatud.</span></div>
        </div>

        <div class="vdocs-h3">Tänased hooldused</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Nimekiri tänaseks planeeritud hooldustest koos kliendi, seadme, töötaja ja staatusega.</p>

        <div class="vdocs-h3">Meeldetuletused</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Seadmed, millele hooldus läheneb järgmise 30 päeva jooksul. Klõps meeldetuletuse ikoonil viib vastava kliendi juurde.</p>

        <div class="vdocs-h3">Tulud (graafik)</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Viimase 6 kuu tulud tulpdiagrammil. Kuu peal hõljutades näed täpset summat.</p>

        <div class="vdocs-h3">Kiirlingid päises</div>
        <p style="font-size:13px;color:#475569;margin:0">Päises on kolm kiirnuppu: <strong>+ Lisa klient</strong>, <strong>+ Lisa hooldus</strong>, <strong>+ Lisa arve</strong> — levinumad toimingud ühe klikiga.</p>
      </div>
    </div>

    <!-- KLIENDID JA SEADMED -->
    <div class="vdocs-section" id="kliendid">
      <div class="vdocs-section-head"><span class="ico">👥</span><h2>Kliendid ja seadmed</h2></div>
      <div class="vdocs-body">

        <div class="vdocs-h3">Kliendi lisamine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Mine Kliendid → "+ Lisa klient"</strong><p>Või kasuta töölaua kiirnuppu päises.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Täida andmed</strong><p>Nimi, e-post (vajalik portaali ligipääsuks), telefon, aadress.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Vali tüüp: Eraisik või Firma</strong><p>Firma puhul ilmuvad lisaväljad: Registrikood ja KMKR nr — need kuvatakse PDF arvel.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">4</div><div class="vdocs-step-b"><strong>Salvesta → kliki "Ligipääs"</strong><p>Süsteem genereerib automaatselt juhusliku parooli ja saadab selle kliendile emailile.</p></div></div>
        </div>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Klientide nimekirjas näed 🏢 märgist firma klientide juures. Saad filtreerida: <strong>Kõik / Eraisik / Firma</strong>.</span></div>

        <div class="vdocs-h3">Kliendi portaali ligipääs saatmine</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Iga kliendi real on nupp <strong>🔑 Ligipääs</strong> — genereerib uue juhusliku parooli, salvestab selle ja saadab kliendile emailile. Klient logib sisse e-posti + parooliga aadressil <code class="vdocs-code">/klient/</code>.</p>
        <div class="vdocs-warn"><span class="ico">⚠️</span><span>Parool on nähtav ainult selles ühes emailis — süsteem ei salvesta seda lihttekstina. Klient saab parooli hiljem portaalis muuta (<strong>Seaded → Muuda parooli</strong>).</span></div>

        <div class="vdocs-h3">Seadme lisamine kliendile</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Ava klient → vahekaart "Seadmed"</strong><p></p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Kliki "+ Lisa seade"</strong><p>Täida seadme nimi, mudel, seerianumber, paigaldusaasta.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Määra hooldusintervall (kuud)</strong><p>Süsteem arvutab automaatselt järgmise hoolduse kuupäeva ja kuvab töölaua meeldetuletustes.</p></div></div>
        </div>
        <div class="vdocs-tip"><span class="ico">💡</span><span>Seadmele saad lisada pildi, dokumentide linke ja märkmeid. Klient näeb seadme infot oma portaalis.</span></div>

        <div class="vdocs-h3">Külaliste taotlused</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Kui külaline (ilma kontota) täidab esilehel kontaktvormi, ilmub see <strong>Kliendid → 👤 Külaliste taotlused</strong> tabis. Punane badge näitab uute taotluste arvu.</p>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Ava taotlus</strong><p>Näed nime, emaili, telefonit, seadme infot, soovitud kuupäeva ja kirjeldust.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Vajuta "Lisa kliendiks"</strong><p>Kliendi loomise vorm avaneb eeltäidetuna (nimi, email, telefon).</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Salvesta → saada portaali ligipääs</strong><p>Klient saab emailiga parooli → logib portaali → pärast teenuse tegemist saab arve maksta.</p></div></div>
        </div>
      </div>
    </div>

    <!-- KALENDER JA HOOLDUSED -->
    <div class="vdocs-section" id="hooldused">
      <div class="vdocs-section-head"><span class="ico">📅</span><h2>Kalender ja hooldused</h2></div>
      <div class="vdocs-body">

        <div class="vdocs-h3">Hoolduse planeerimine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Hooldused → "+ Lisa hooldus"</strong><p>Või ava Kalender → kliki kuupäeval.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Vali klient ja seade</strong><p>Seadme valimisel täidetakse automaatselt eelmine hoolduskuupäev ja soovituslik järgmine.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Määra töötaja ja kuupäev</strong><p>Töötaja näeb hooldust oma portaalis koheselt.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">4</div><div class="vdocs-step-b"><strong>Salvesta</strong><p>Kalender uueneb automaatselt.</p></div></div>
        </div>

        <div class="vdocs-h3">Hoolduse staatused</div>
        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>📋 Planeeritud</h3><p>Hooldus on ajakavasse lisatud, töötajale määratud.</p></div>
          <div class="vdocs-card"><h3>✅ Tehtud</h3><p>Töötaja on hoolduse portaalis lõpetatuks märkinud. Seerianumber ja kuupäev salvestatakse.</p></div>
          <div class="vdocs-card"><h3>❌ Tühistatud</h3><p>Hooldus ei toimu. Jääb logi jaoks alles.</p></div>
          <div class="vdocs-card"><h3>🔴 Hilinenud</h3><p>Planeeritud kuupäev on möödas ja staatus pole "Tehtud". Töölaud hoiatab.</p></div>
        </div>

        <div class="vdocs-h3">Meeldetuletused</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Vesho CRM → <strong>Meeldetuletused</strong> näitab seadmeid, millele hooldus tulemas järgmise 30 päeva jooksul (arvestatakse hooldusintervallist). Töölaud kuvab samad meeldetuletused ka päevas.</p>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Intervall seadistatakse igale seadmele eraldi (kuud). Kui seadmel pole hooldusi tehtud, loetakse hooldus kohe vajalikuks.</span></div>

        <div class="vdocs-h3">Marsruut</div>
        <p style="font-size:13px;color:#475569;margin:0">Vesho CRM → <strong>Marsruut</strong> — planeerib päeva töökäskude optimaalse läbimise järjekorra kaardil. Kasulik kui päevas on mitu klienti eri asukohtades.</p>
      </div>
    </div>

    <!-- TÖÖKÄSUD -->
    <div class="vdocs-section" id="tookasud">
      <div class="vdocs-section-head"><span class="ico">🔧</span><h2>Töökäsud</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Töökäsk on tööülesanne töötajale — seotud kliendi, seadme ja kuupäevaga. Töötaja näeb töökäsku oma portaalis ja märgib selle tehtuks.</p>

        <div class="vdocs-h3">Töökäsu loomine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Töökäsud → "+ Lisa töökäsk"</strong><p></p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Vali klient, seade, töötaja, prioriteet ja kuupäev</strong><p>Lisa kirjeldus mida täpselt teha on.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Salvesta</strong><p>Töötaja näeb töökäsku koheselt oma portaalis. Saad saata ka emaili teavituse.</p></div></div>
        </div>

        <div class="vdocs-h3">Staatused</div>
        <div class="vdocs-flow">
          <div class="vdocs-flow-step">📋 Uus</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">⚙️ Määratud</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">🔄 Töös</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">✅ Lõpetatud</div>
        </div>
        <div class="vdocs-tip"><span class="ico">💡</span><span>Töötaja muudab staatuse ise portaalis. Admin saab staatust muuta ka CRM-is. Lõpetamisel saab töötaja lisada kommentaari ja pildid.</span></div>
      </div>
    </div>

    <!-- TÖÖTAJAD -->
    <div class="vdocs-section" id="tootajad">
      <div class="vdocs-section-head"><span class="ico">👷</span><h2>Töötajad</h2></div>
      <div class="vdocs-body">

        <div class="vdocs-h3">Töötaja lisamine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Töötajad → "+ Lisa töötaja"</strong><p></p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Täida nimi, telefon, e-post</strong><p>E-post on vajalik portaali ligipääsuks.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Salvesta</strong><p>Süsteem loob automaatselt WordPressi kasutaja rolliga <code class="vdocs-code">vesho_worker</code>. Töötaja saab kutsemeili portaali ligipääsu linkiga.</p></div></div>
        </div>

        <div class="vdocs-h3">Töötunnid</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Vesho CRM → <strong>Töötunnid</strong> — töötajate logitud tunnid. Töötaja saab portaalis märkida töö alguse ja lõpu aja. Admin näeb kokkuvõtet siin.</p>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Töötajale saab määrata tunnitasu — raport arvutab automaatselt töötasud perioodi kaupa.</span></div>
      </div>
    </div>

    <!-- ARVED -->
    <div class="vdocs-section" id="arved">
      <div class="vdocs-section-head"><span class="ico">🧾</span><h2>Arved ja PDF</h2></div>
      <div class="vdocs-body">
        <div class="vdocs-tip"><span class="ico">💡</span><span>KM-määr on praegu <strong><?php echo $vat; ?>%</strong>. Muutmiseks: <a href="<?php echo admin_url('admin.php?page=vesho-crm-settings'); ?>" style="color:#065f46;font-weight:600">Seaded → KM-määr</a>.</span></div>

        <div class="vdocs-h3">Arve koostamine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Arved → "+ Lisa arve"</strong><p>Või töölaua kiirnupp päises.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Vali klient</strong><p>Firma klientide puhul täidetakse registrikood ja KMKR automaatselt.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Lisa read</strong><p>Iga rida: kirjeldus, kogus, ühiku hind. KM arvutatakse automaatselt.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">4</div><div class="vdocs-step-b"><strong>Määra maksetähtaeg</strong><p>Vaikimisi 14 päeva. Töölaud hoiatab punaselt kui tähtaeg on ületatud.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">5</div><div class="vdocs-step-b"><strong>Salvesta → "Saada e-postiga"</strong><p>Klient saab arve emailile. Arve ilmub ka kliendi portaalis.</p></div></div>
        </div>

        <div class="vdocs-h3">Arvete staatused</div>
        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>📝 Mustand</h3><p>Salvestatud, pole saadetud. Klient ei näe seda portaalis.</p></div>
          <div class="vdocs-card"><h3>📤 Saadetud</h3><p>Klient on saanud emaili. Nähtav portaalis.</p></div>
          <div class="vdocs-card"><h3>✅ Tasutud</h3><p>Makse laekunud — märgi käsitsi "Tasutud". Arve läheb müügiraportisse.</p></div>
          <div class="vdocs-card"><h3>🔴 Tähtaeg ületatud</h3><p>Maksetähtaeg möödas ja pole tasutud. Töölaud hoiatab.</p></div>
        </div>

        <div class="vdocs-h3">Arve printimine / PDF</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Ava arve → kliki "Prindi / PDF"</strong><p>Avaneb printimisvaade uues aknas.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Salvesta PDF-ina</strong><p>Printeriks vali "Salvesta PDF-ina" (Chrome/Edge). Fail salvestatakse arvutisse.</p></div></div>
        </div>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>PDF-il kuvatakse automaatselt: firma logo, nimi, aadress, kliendi andmed, arve read KM-ga, pangaandmed. Seadistatav: <a href="<?php echo admin_url('admin.php?page=vesho-crm-settings'); ?>" style="color:#1d4ed8">Seaded</a>.</span></div>
      </div>
    </div>

    <!-- LADU -->
    <div class="vdocs-section" id="ladu">
      <div class="vdocs-section-head"><span class="ico">📦</span><h2>Ladu</h2></div>
      <div class="vdocs-body">

        <div class="vdocs-h3">Laoseis</div>
        <p style="font-size:13px;color:#475569;margin:0 0 10px">Vesho CRM → <strong>Ladu</strong> — kõik kaubad nimekiri koos koguse, minimaalse koguse ja asukohaga. Punane rida = kogus alla miinimumi. Töölaud näitab laopuuduse hoiatust automaatselt.</p>

        <div class="vdocs-h3">Kauba vastuvõtt</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Vastuvõtt → "+ Uus vastuvõtt"</strong><p></p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Vali tarnija, lisa kaubad ja kogused</strong><p>Saad seostada ostutellimuse numbriga.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Kinnita vastuvõtt</strong><p>Kogused uuenevad laos automaatselt.</p></div></div>
        </div>

        <div class="vdocs-h3">Ostutellimused tarnijatelt</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Vesho CRM → <strong>Ostutellimused</strong> — loo ostutellimus tarnijale kui ladu on tühjaks saamas. Vastuvõtmisel sulgeb ostutellimuse automaatselt.</p>
        <div class="vdocs-tip"><span class="ico">💡</span><span>Tarnijaid haldad: Vesho CRM → <strong>Tarnijad</strong>. Iga tarnija juures saab hoida kontaktinfot ja tarneaega.</span></div>
      </div>
    </div>

    <!-- HINNAKIRI -->
    <div class="vdocs-section" id="hinnakiri">
      <div class="vdocs-section-head"><span class="ico">💰</span><h2>Hinnakiri ja e-pood</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Hinnakiri sisaldab nii teenuseid (tööd) kui ka müügikaupasid. Avalik hinnakiri kuvatakse veebilehel shortcode'iga.</p>

        <div class="vdocs-grid">
          <div class="vdocs-card">
            <h3>Toote/teenuse lisamine</h3>
            <ul>
              <li>Vesho CRM → Hinnakiri → "+ Lisa"</li>
              <li>Täida nimi, kategooria, hind (km-ta), kirjeldus</li>
              <li>"Avalik" = kuvatakse veebilehel</li>
              <li>"Müük portaalis" = klient saab portaalis tellida</li>
            </ul>
          </div>
          <div class="vdocs-card">
            <h3>Veebilehe hinnakiri</h3>
            <ul>
              <li>Shortcode: <code class="vdocs-code">[vesho_price_list]</code></li>
              <li>Filtreeritud: <code class="vdocs-code">[vesho_price_list category="hooldus"]</code></li>
              <li>Lisa Elementoris "Shortcode" widgetiga mis tahes lehele</li>
            </ul>
          </div>
          <div class="vdocs-card">
            <h3>E-poe tellimused</h3>
            <ul>
              <li>Klient saab portaalist tellida tooteid</li>
              <li>Tellimused: Vesho CRM → Tellimused</li>
              <li>Staatused: Uus → Töötlemisel → Saadetud → Lõpetatud</li>
            </ul>
          </div>
          <div class="vdocs-card">
            <h3>Kampaaniad</h3>
            <ul>
              <li>Vesho CRM → Kampaaniad → lisa allahindlus</li>
              <li>Seo tootega, määra protsent ja kehtivusaeg</li>
              <li>Klient näeb allahindlust portaali poes</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- SEADED -->
    <div class="vdocs-section" id="seaded">
      <div class="vdocs-section-head"><span class="ico">⚙️</span><h2>Seaded</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Vesho CRM → <strong>Seaded</strong> — süsteemi üldseaded. Muutused mõjutavad kõiki PDF-e, e-kirju ja portaale.</p>
        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>🏢 Firma andmed</h3><p>Nimi, aadress, telefon, e-post, koduleht, registrikood, KMKR. Kuvatakse PDF arvedel ja e-kirjades.</p></div>
          <div class="vdocs-card"><h3>💶 KM-määr</h3><p>Praegu: <strong><?php echo $vat; ?>%</strong>. Mõjutab kõiki uusi arveid. Vanad arved jäävad muutumatuks.</p></div>
          <div class="vdocs-card"><h3>🖼️ Logo</h3><p>PDF arvedel ja e-kirjades kuvatav logo. Soovitus: PNG läbipaistva taustaga.</p></div>
          <div class="vdocs-card"><h3>📧 E-posti seaded</h3><p>SMTP server, saatja nimi ja e-post. Vaikimisi kasutatakse WordPress'i wp_mail.</p></div>
          <div class="vdocs-card"><h3>🔢 Arve prefiks</h3><p>Arve numbri eesliide, nt "VESHO-2024-". Määra enne esimese arve loomist.</p></div>
          <div class="vdocs-card"><h3>💳 Pangaandmed</h3><p>IBAN, pank, selgituse tekst. Kuvatakse PDF arve allosas.</p></div>
        </div>

        <div class="vdocs-h3">Portaali teated klientidele</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Vesho CRM → <strong>Portaali teated</strong> — lisa bänner kliendi portaali avalehele (nt "Hinnad muutuvad 1. maist"). Saad ajastada algus- ja lõppkuupäevaga.</p>

        <div class="vdocs-h3">Administraatorid</div>
        <p style="font-size:13px;color:#475569;margin:0 0 8px">Vesho CRM → <strong>Administraatorid</strong> — anna CRM ligipääs teisele kasutajale ilma täis WordPress admin õigusteta. Roll: <code class="vdocs-code">vesho_crm_admin</code>.</p>
      </div>
    </div>

    <!-- VEEBILEHT -->
    <div class="vdocs-section" id="veebileht">
      <div class="vdocs-section-head"><span class="ico">🎨</span><h2>Veebilehe muutmine (Elementor)</h2></div>
      <div class="vdocs-body">
        <div class="vdocs-warn"><span class="ico">⚠️</span><span>Enne suuri muutusi tee varukoopia: WordPress Admin → Tööriistad → Eksport. Soovitus: installi UpdraftPlus automaatseks varundamiseks.</span></div>

        <div class="vdocs-h3">Lehe redigeerimine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>WordPress Admin → Lehed</strong><p>Hõljuta hiirega lehel → kliki <strong>"Redigeeri Elementoriga"</strong>.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Kliki elemendil lehel</strong><p>Vasakul paneel avab vastava widgeti seaded.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Muuda tekst, pilt, värvid</strong><p>Teksti saab muuta otse klõpsates. Pildil klõps avab meediateegi.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">4</div><div class="vdocs-step-b"><strong>Kliki "Avalda"</strong><p>Ülaparemas nurgas roheline nupp. Muutused on kohe nähtavad külastajatele.</p></div></div>
        </div>

        <div class="vdocs-h3">Leheküljed ja nende funktsioonid</div>
        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>Avaleht</h3><p>Peamine turundusleht. Kõik plokid muudetavad Elementoriga. Statistikaarvud (500+, 12+, 98%) — klõpsa numbril ja muuda.</p></div>
          <div class="vdocs-card"><h3>Meist</h3><p>Firma tutvustus. Tekst, pildid, meeskond — kõik Elementoriga muudetav.</p></div>
          <div class="vdocs-card"><h3>Teenused</h3><p>Teenuste loetelu. Hinnakirja kuvamiseks kasuta shortcode <code class="vdocs-code">[vesho_price_list]</code>.</p></div>
          <div class="vdocs-card"><h3>Pood</h3><p>E-pood aadressil <code class="vdocs-code">/pood/</code>. Tooted hallatakse Hinnakirjas, mitte Elementoris.</p></div>
          <div class="vdocs-card"><h3>Klient</h3><p>Kliendi portaal. <strong>ÄRA MUUDA</strong> shortcode'i <code class="vdocs-code">[vesho_client_portal]</code> — portaal töötab automaatselt.</p></div>
          <div class="vdocs-card"><h3>Töötaja</h3><p>Töötaja portaal. Sama — <strong>ÄRA MUUDA</strong> shortcode'i <code class="vdocs-code">[vesho_worker_portal]</code>.</p></div>
        </div>

        <div class="vdocs-h3">Logo muutmine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>WordPress Admin → Välimus → Kohanda</strong><p></p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Saidi identiteet → Logo</strong><p>Lae üles uus pilt. Soovituslik: PNG läbipaistva taustaga, ~200×60px.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Avalda</strong><p>Kohandaja paremal ülal sinine "Avalda" nupp.</p></div></div>
        </div>
        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Logo muutmine Kohandajas uuendab nii veebilehe päist kui ka mobiilivaate hamburger-menüüd automaatselt.</span></div>
      </div>
    </div>

    <!-- TÖÖTAJA PORTAAL -->
    <div class="vdocs-section" id="tootaja-portaal">
      <div class="vdocs-section-head"><span class="ico">👷</span><h2>Töötaja portaal</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Töötaja portaal on aadressil <a href="<?php echo $site_url; ?>/worker/" target="_blank" style="color:#00b4c8;font-weight:600"><?php echo $site_url; ?>/worker/</a> — mobiilisõbralik vaade töötajatele.</p>

        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>Ülevaade</h3><p>Töötaja näeb oma aktiivsete töökäskude arvu, tänaseid töökäske ja lähimaid hooldusi.</p></div>
          <div class="vdocs-card"><h3>Töökäsud</h3><p>Kõik määratud töökäsud. Saab muuta staatust (Uus → Töös → Lõpetatud), lisada kommentaari ja pilte.</p></div>
          <div class="vdocs-card"><h3>Hooldused</h3><p>Tänased ja tulevased hooldused. Lõpetamisel märgib kuupäeva ja saab lisada märkmeid.</p></div>
          <div class="vdocs-card"><h3>Töötunnid</h3><p>Logib töö alguse ja lõpu aja. Admin näeb kokkuvõtet CRM-is.</p></div>
        </div>

        <div class="vdocs-h3">Töötaja ligipääsu andmine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → Töötajad → Lisa töötaja</strong><p>Täida nimi ja e-post.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Süsteem saadab emaili portaali lingiga</strong><p>Töötaja klõpsab lingil ja seab parooli.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Töötaja logib sisse /worker/ aadressil</strong><p>Mobiilis saab paigaldada ka avakuvale (PWA).</p></div></div>
        </div>
        <div class="vdocs-tip"><span class="ico">💡</span><span>Mobiilis Chrome'is: mine <code class="vdocs-code">/worker/</code> → aadressiriba kõrval ilmub ⊕ ikoon → "Installi rakendus" → ikoon ilmub avakuvale. Töötab nagu äpp.</span></div>
      </div>
    </div>

    <!-- KLIENDI PORTAAL -->
    <div class="vdocs-section" id="kliendi-portaal">
      <div class="vdocs-section-head"><span class="ico">🌐</span><h2>Kliendi portaal</h2></div>
      <div class="vdocs-body">
        <p style="font-size:13px;color:#475569;margin:0 0 14px">Kliendi portaal on aadressil <a href="<?php echo $site_url; ?>/klient/" target="_blank" style="color:#00b4c8;font-weight:600"><?php echo $site_url; ?>/klient/</a> — klient haldab oma suhet Veshoga iseseisvalt.</p>

        <div class="vdocs-grid">
          <div class="vdocs-card"><h3>Ülevaade</h3><p>Järgmine hooldus, tasumata arved, aktiivsed seadmed, tehtud hooldused.</p></div>
          <div class="vdocs-card"><h3>Seadmed</h3><p>Kõik kliendi seadmed koos hooldusajaloo ja järgmise hoolduse kuupäevaga.</p></div>
          <div class="vdocs-card"><h3>Arved</h3><p>Kõik arved — saab vaadata, alla laadida PDF-na. Maksmata arved on punaselt esile tõstetud.</p></div>
          <div class="vdocs-card"><h3>Hooldused</h3><p>Tulevased ja tehtud hooldused koos töötaja ja märkmetega.</p></div>
          <div class="vdocs-card"><h3>Tugi</h3><p>Klient saab saata tugipileti. Admin näeb pileteid: Vesho CRM → Tugipiletid.</p></div>
          <div class="vdocs-card"><h3>Broneeri hooldus</h3><p>Klient saab ise soovida hoolduse aega. Taotlus ilmub CRM-is Päringutesse.</p></div>
        </div>

        <div class="vdocs-info"><span class="ico">ℹ️</span><span>Kliendi portaali bännerteate lisamiseks: Vesho CRM → <strong>Portaali teated</strong>. Kuvatakse kõigile sisselogitud klientidele.</span></div>
      </div>
    </div>

    <!-- TÜÜPILISED TÖÖVOOD -->
    <div class="vdocs-section" id="toovood">
      <div class="vdocs-section-head"><span class="ico">🔄</span><h2>Tüüpilised töövood</h2></div>
      <div class="vdocs-body">

        <div class="vdocs-h3">🆕 Uus klient → esimene hooldus → arve</div>
        <div class="vdocs-flow">
          <div class="vdocs-flow-step">Lisa klient</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Lisa seade</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Planeeri hooldus</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Töötaja teeb töö</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Koosta arve</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Saada kliendile</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Märgi tasutud</div>
        </div>

        <div class="vdocs-h3">📩 Veebilehelt tulev hinnapäring</div>
        <div class="vdocs-flow">
          <div class="vdocs-flow-step">Päring saabub</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">CRM → Päringud</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Lisa kliendiks</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Saada portaali ligipääs</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Planeeri hooldus</div>
        </div>

        <div class="vdocs-h3">🔔 Igakuine hoolduste planeerimine</div>
        <div class="vdocs-flow">
          <div class="vdocs-flow-step">Töölaud → Meeldetuletused</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Vali seadmed</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Lisa hooldused kalendrisse</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Marsruut optimeerib sõidu</div>
        </div>

        <div class="vdocs-h3">📦 Ladu tühjeneb → telli juurde</div>
        <div class="vdocs-flow">
          <div class="vdocs-flow-step">Töölaud hoiatab laopuudusest</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Loo ostutellimus</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Kaup saabub → Vastuvõtt</div><div class="vdocs-flow-arrow">→</div>
          <div class="vdocs-flow-step">Laoseis uueneb</div>
        </div>
      </div>
    </div>

    <!-- UUENDUSED -->
    <div class="vdocs-section" id="uuendused">
      <div class="vdocs-section-head"><span class="ico">🚀</span><h2>Uuendused</h2></div>
      <div class="vdocs-body">
        <div class="vdocs-tip"><span class="ico">✅</span><span>Praegu paigaldatud: Vesho CRM <strong>v<?php echo esc_html($plugin_ver); ?></strong> · Teema <strong>v<?php echo esc_html($theme_ver); ?></strong></span></div>

        <div class="vdocs-h3">CRM ja teema uuendamine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Vesho CRM → 🚀 Uuendused</strong><p>Näed kas uus versioon on saadaval. Muudatuste logi näitab mis parandati.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>Kliki "Uuenda pistikprogrammi" / "Uuenda teemat"</strong><p>Süsteem laeb alla ja paigaldab automaatselt. Leht jääb töös — alla 10 sekundit.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Kontrolli versiooni</strong><p>Lehekülje alumises osas peaks versiooninumber olema uuenenud.</p></div></div>
        </div>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-releases'); ?>" class="vdocs-btn">→ Ava uuendused</a>

        <div class="vdocs-h3">WordPress uuendamine</div>
        <div class="vdocs-steps">
          <div class="vdocs-step"><div class="vdocs-step-n">1</div><div class="vdocs-step-b"><strong>Tee varukoopia enne uuendust</strong><p>UpdraftPlus → varunda nüüd.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">2</div><div class="vdocs-step-b"><strong>WordPress Admin → Tööriistad → Uuendused</strong><p>Uuenda WordPress core, seejärel pistikprogrammid, seejärel teemad.</p></div></div>
          <div class="vdocs-step"><div class="vdocs-step-n">3</div><div class="vdocs-step-b"><strong>Kontrolli veebilehte</strong><p>Ava avalik leht ja portaalid — veendu et kõik töötab.</p></div></div>
        </div>
        <div class="vdocs-warn"><span class="ico">⚠️</span><span>Ära uuenda WordPressi kohe kui see tuleb — oota 1-2 nädalat. Selle ajaga parandatakse võimalikud vead uues versioonis.</span></div>
      </div>
    </div>

    <!-- SÜSTEEMI INFO -->
    <div class="vdocs-section" id="sysinfo">
      <div class="vdocs-section-head"><span class="ico">🖥️</span><h2>Süsteemi info</h2></div>
      <div class="vdocs-body">
        <div class="vdocs-sysinfo">
          <div class="vdocs-sys-item"><div class="lbl">Veebileht</div><div class="val"><?php echo esc_html($site_url); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">Vesho CRM</div><div class="val">v<?php echo esc_html($plugin_ver); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">Teema</div><div class="val">v<?php echo esc_html($theme_ver); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">WordPress</div><div class="val">v<?php echo esc_html($wp_ver); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">PHP</div><div class="val"><?php echo esc_html($php_ver); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">MySQL</div><div class="val"><?php echo esc_html($db_ver); ?></div></div>
          <div class="vdocs-sys-item"><div class="lbl">KM-määr</div><div class="val"><?php echo esc_html($vat); ?>%</div></div>
          <div class="vdocs-sys-item"><div class="lbl">Admin URL</div><div class="val"><?php echo esc_html($site_url); ?>/wp-admin/</div></div>
        </div>
      </div>
    </div>

  </div><!-- .vdocs-main -->
</div><!-- .vdocs-wrap -->

<script>
(function(){
  var links = document.querySelectorAll('.vdocs-toc a');
  var sections = document.querySelectorAll('.vdocs-section[id]');
  function onScroll(){
    var scrollY = window.scrollY + 100;
    sections.forEach(function(sec){
      var top = sec.offsetTop, h = sec.offsetHeight;
      if(scrollY >= top && scrollY < top + h){
        links.forEach(function(l){ l.classList.remove('active'); });
        var active = document.querySelector('.vdocs-toc a[href="#'+sec.id+'"]');
        if(active) active.classList.add('active');
      }
    });
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();
})();
</script>
