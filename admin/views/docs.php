<?php defined('ABSPATH') || exit;
$wp_version  = get_bloginfo('version');
$site_url    = get_site_url();
$admin_url   = admin_url();
$plugin_ver  = VESHO_CRM_VERSION;
$theme_ver   = wp_get_theme()->get('Version');
$php_ver     = PHP_VERSION;
$db_ver      = $GLOBALS['wpdb']->db_version();
?>

<div class="crm-page-header">
  <div class="crm-page-header__logo">
    <svg width="26" height="26" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#fff"/>
    </svg>
  </div>
  <div class="crm-page-header__body">
    <h1 class="crm-page-header__title">📖 Vesho CRM — Juhend</h1>
    <p class="crm-page-header__subtitle">Omaniku ja haldaja kasutusjuhend</p>
  </div>
  <div class="crm-page-header__actions">
    <a href="<?php echo admin_url('admin.php?page=vesho-crm'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">← Töölaud</a>
  </div>
</div>

<style>
.docs-wrap { max-width:960px; margin:0 auto; padding:24px 8px 48px; }
.docs-section { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:24px; overflow:hidden; }
.docs-section-head { background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:14px 20px; display:flex; align-items:center; gap:10px; }
.docs-section-head h2 { margin:0; font-size:15px; font-weight:700; color:#1e293b; }
.docs-section-head .icon { font-size:18px; }
.docs-body { padding:20px; }
.docs-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:640px){ .docs-grid { grid-template-columns:1fr; } }
.docs-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; }
.docs-card h3 { margin:0 0 6px; font-size:13px; font-weight:700; color:#1e293b; }
.docs-card p { margin:0 0 8px; font-size:13px; color:#475569; line-height:1.5; }
.docs-card p:last-child { margin-bottom:0; }
.docs-card ul { margin:4px 0 0; padding-left:18px; font-size:13px; color:#475569; line-height:1.7; }
.docs-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:#00b4c8; text-decoration:none; margin-top:6px; }
.docs-link:hover { color:#008fa0; }
.sysinfo { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
.sysinfo-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; }
.sysinfo-item .label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:4px; }
.sysinfo-item .value { font-size:14px; font-weight:700; color:#1e293b; }
.docs-step { display:flex; gap:12px; margin-bottom:12px; }
.docs-step:last-child { margin-bottom:0; }
.docs-step-num { width:26px; height:26px; border-radius:50%; background:#00b4c8; color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.docs-step-body { flex:1; }
.docs-step-body strong { font-size:13px; color:#1e293b; display:block; margin-bottom:2px; }
.docs-step-body p { margin:0; font-size:13px; color:#475569; line-height:1.5; }
.docs-step-body code { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; padding:1px 5px; font-size:12px; color:#1e293b; }
.docs-warn { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; font-size:13px; color:#92400e; margin-bottom:12px; }
.docs-tip { background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:10px 14px; font-size:13px; color:#065f46; margin-bottom:12px; }
</style>

<div class="docs-wrap">

  <!-- SÜSTEEMIINFO -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🖥️</span>
      <h2>Süsteemi info</h2>
    </div>
    <div class="docs-body">
      <div class="sysinfo">
        <div class="sysinfo-item"><div class="label">Veebileht</div><div class="value"><?php echo esc_html($site_url); ?></div></div>
        <div class="sysinfo-item"><div class="label">Vesho CRM versioon</div><div class="value"><?php echo esc_html($plugin_ver); ?></div></div>
        <div class="sysinfo-item"><div class="label">Teema versioon</div><div class="value"><?php echo esc_html($theme_ver); ?></div></div>
        <div class="sysinfo-item"><div class="label">WordPress</div><div class="value"><?php echo esc_html($wp_version); ?></div></div>
        <div class="sysinfo-item"><div class="label">PHP</div><div class="value"><?php echo esc_html($php_ver); ?></div></div>
        <div class="sysinfo-item"><div class="label">MySQL</div><div class="value"><?php echo esc_html($db_ver); ?></div></div>
      </div>
    </div>
  </div>

  <!-- KIIRLINGID -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">⚡</span>
      <h2>Kiirlingid</h2>
    </div>
    <div class="docs-body">
      <div class="docs-grid">
        <div class="docs-card">
          <h3>WordPress Admin</h3>
          <p>Kõik WordPress seaded, kasutajad, lehed ja pistikprogrammid.</p>
          <a href="<?php echo $admin_url; ?>" class="docs-link" target="_blank">→ Ava WordPress Admin</a>
        </div>
        <div class="docs-card">
          <h3>Veebilehe esilehekülg</h3>
          <p>Vaata kuidas veebileht välja näeb külastajale.</p>
          <a href="<?php echo $site_url; ?>" class="docs-link" target="_blank">→ Ava veebileht</a>
        </div>
        <div class="docs-card">
          <h3>Kliendi portaal</h3>
          <p>Kliendid logivad sisse ja näevad oma seadmeid, arveid, hooldusi.</p>
          <a href="<?php echo $site_url; ?>/klient/" class="docs-link" target="_blank">→ Ava kliendi portaal</a>
        </div>
        <div class="docs-card">
          <h3>Töötaja portaal</h3>
          <p>Töötajad näevad oma töökäske ja märgivad hooldusi tehtuks.</p>
          <a href="<?php echo $site_url; ?>/tootaja/" class="docs-link" target="_blank">→ Ava töötaja portaal</a>
        </div>
        <div class="docs-card">
          <h3>Uuendused</h3>
          <p>Kontrolli uue versiooni saadavust ja uuenda pistikprogrammi/teemat.</p>
          <a href="<?php echo admin_url('admin.php?page=vesho-crm-releases'); ?>" class="docs-link">→ Ava uuendused</a>
        </div>
        <div class="docs-card">
          <h3>Seaded</h3>
          <p>KM-määr, e-posti seaded, arve prefiks, logo ja muud süsteemiseaded.</p>
          <a href="<?php echo admin_url('admin.php?page=vesho-crm-settings'); ?>" class="docs-link">→ Ava seaded</a>
        </div>
      </div>
    </div>
  </div>

  <!-- CRM PÕHIFUNKTSIOONID -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">👥</span>
      <h2>Klientide haldus</h2>
    </div>
    <div class="docs-body">
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Uue kliendi lisamine</h3>
          <ul>
            <li>Vesho CRM → Kliendid → <strong>+ Lisa klient</strong></li>
            <li>Täida nimi, telefon, e-post, aadress</li>
            <li>Vali tüüp: Eraisik / Ettevõte</li>
            <li>Salvesta — klient saab automaatselt portaali ligipääsu</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Seadme lisamine kliendile</h3>
          <ul>
            <li>Ava klient → vahekaart <strong>Seadmed</strong></li>
            <li>Kliki <strong>+ Lisa seade</strong></li>
            <li>Täida seadme nimi, mudel, seerianumber</li>
            <li>Määra hooldusintervall (kuud) — süsteem meenutab automaatselt</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Kliendi portaali ligipääs</h3>
          <ul>
            <li>Klient saab kutse e-postiga automaatselt</li>
            <li>Kui klient unustab parooli → portaali "Unustasin parooli" link</li>
            <li>Portaali URL: <code><?php echo $site_url; ?>/klient/</code></li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Päringud (hinnapäringud)</h3>
          <ul>
            <li>Klientide saadetud hinnapäringud: <strong>Vesho CRM → Päringud</strong></li>
            <li>Saad muuta staatust: Uus → Vaadatud → Vastatud</li>
            <li>Vastata saab otse CRM-ist e-postiga</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- HOOLDUSED -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🔧</span>
      <h2>Hooldused ja töökäsud</h2>
    </div>
    <div class="docs-body">
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Hoolduse planeerimine</h3>
          <ul>
            <li>Vesho CRM → Hooldused → <strong>+ Lisa hooldus</strong></li>
            <li>Vali klient, seade, töötaja, kuupäev</li>
            <li>Töötaja näeb hooldust oma portaalis</li>
            <li>Kalender: <strong>Vesho CRM → Kalender</strong></li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Hoolduste meeldetuletused</h3>
          <ul>
            <li><strong>Töölaud</strong> näitab automaatselt seadmeid, millele hooldus läheneb (30 päeva)</li>
            <li>Vesho CRM → Meeldetuletused — täielik nimekiri</li>
            <li>Intervall seadistatakse seadme juures (kuud)</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Töötaja portaal</h3>
          <ul>
            <li>Töötajad logivad sisse: <code><?php echo $site_url; ?>/tootaja/</code></li>
            <li>Näevad oma töökäske ja hooldusi</li>
            <li>Märgivad tööd tehtuks — uuendus kajastub kohe CRM-is</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Töötajate lisamine</h3>
          <ul>
            <li>Vesho CRM → Töötajad → <strong>+ Lisa töötaja</strong></li>
            <li>Täida nimi, telefon, e-post</li>
            <li>Süsteem loob automaatselt WordPress kasutaja</li>
            <li>Töötaja saab kutsemeili portaali ligipääsuks</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- ARVELDUS -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🧾</span>
      <h2>Arved ja maksed</h2>
    </div>
    <div class="docs-body">
      <div class="docs-tip">💡 KM-määra muutmiseks mine <strong>Vesho CRM → Seaded → KM-määr</strong>. Praegu: <?php echo get_option('vesho_vat_rate', '24'); ?>%</div>
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Arve koostamine</h3>
          <ul>
            <li>Vesho CRM → Arved → <strong>+ Lisa arve</strong></li>
            <li>Vali klient, lisa read (teenus, kogus, hind)</li>
            <li>Arve number genereeritakse automaatselt</li>
            <li>Saada kliendile: <strong>Saada e-postiga</strong></li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Arve staatused</h3>
          <ul>
            <li><strong>Mustand</strong> — salvestatud, pole saadetud</li>
            <li><strong>Saadetud</strong> — klient on saanud</li>
            <li><strong>Tasutud</strong> — makse laekunud, märgi käsitsi</li>
            <li><strong>Tähtaeg ületatud</strong> — töölaud hoiatab punaselt</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Arvete printimine / PDF</h3>
          <ul>
            <li>Ava arve → kliki <strong>Prindi / PDF</strong></li>
            <li>Brauseris avaneb printimisvaade</li>
            <li>Salvesta PDF-ina: "Salvesta PDF-ina" printerina</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Müügiraport</h3>
          <ul>
            <li>Vesho CRM → Müügiraport</li>
            <li>Näitab käivet kuude kaupa</li>
            <li>Töölaua graafik — viimased 6 kuud</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- LADU -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">📦</span>
      <h2>Ladu ja inventuur</h2>
    </div>
    <div class="docs-body">
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Laoseis</h3>
          <ul>
            <li>Vesho CRM → Ladu — kõik kaubad ja kogused</li>
            <li>Punane hoiatus — kogus alla miinimumi</li>
            <li>Töölaud näitab automaatselt laopuudust</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Kauba vastuvõtt</h3>
          <ul>
            <li>Vesho CRM → Vastuvõtt → <strong>+ Uus vastuvõtt</strong></li>
            <li>Vali tarnija, lisa kaubad ja kogused</li>
            <li>Kinnita — kogused uuenevad laos automaatselt</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Ostutellimused</h3>
          <ul>
            <li>Vesho CRM → Ostutellimused</li>
            <li>Telli tarnijalt — seo laokaubaga</li>
            <li>Vastuvõtmisel sulgeb ostutellimuse</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Hinnakiri</h3>
          <ul>
            <li>Vesho CRM → Hinnakiri — teenuste hinnad</li>
            <li>Avalik hinnakiri: shortcode <code>[vesho_price_list]</code></li>
            <li>Saab filtreerida kategooria järgi</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- VEEBILEHT JA ELEMENTOR -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🎨</span>
      <h2>Veebilehe muutmine (Elementor)</h2>
    </div>
    <div class="docs-body">
      <div class="docs-warn">⚠️ Enne suuri muutusi tee varukoopia: WordPress Admin → Tööriistad → Eksport</div>
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Lehe redigeerimine Elementoriga</h3>
          <div class="docs-step">
            <div class="docs-step-num">1</div>
            <div class="docs-step-body"><strong>Ava leht</strong><p>WordPress Admin → Lehed → hõljuta hiirega lehel</p></div>
          </div>
          <div class="docs-step">
            <div class="docs-step-num">2</div>
            <div class="docs-step-body"><strong>Kliki "Redigeeri Elementoriga"</strong><p>Avaneb visuaalne redaktor</p></div>
          </div>
          <div class="docs-step">
            <div class="docs-step-num">3</div>
            <div class="docs-step-body"><strong>Kliki elemendil</strong><p>Vasakul paneel näitab muutmisvõimalusi</p></div>
          </div>
          <div class="docs-step">
            <div class="docs-step-num">4</div>
            <div class="docs-step-body"><strong>Avalda</strong><p>Ülaparemas nurgas roheline "Avalda" nupp</p></div>
          </div>
        </div>
        <div class="docs-card">
          <h3>Teksti muutmine</h3>
          <ul>
            <li>Kliki tekstiplokil → vasakul "Text Editor"</li>
            <li>Kliki tekstil otse → kirjuta uus tekst</li>
            <li>Pealkirjad: "Heading" widget → muuda Title välja</li>
            <li>Salvestab automaatselt eelvaates, aga <strong>vajab "Avalda"</strong></li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Pildi vahetamine</h3>
          <ul>
            <li>Kliki pildil → vasakul kliki pildil uuesti</li>
            <li>Avaneb meediateek — vali või lae üles uus pilt</li>
            <li>Soovituslik formaat: <strong>WebP</strong> või JPEG</li>
            <li>Max suurus: 1920px lai, alla 500 KB</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Logo muutmine</h3>
          <ul>
            <li>WordPress Admin → Välimus → Kohanda</li>
            <li>→ Saidi identiteet → Logo</li>
            <li>Soovituslik: PNG läbipaistva taustaga, ~200×60px</li>
            <li>Avalda Kohandaja nupuga</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Portaali teated klientidele</h3>
          <ul>
            <li>Vesho CRM → Portaali teated</li>
            <li>Lisa bänner (nt hooldustööde info) kliendi portaali</li>
            <li>Saab ajastada algus- ja lõppkuupäevaga</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>E-pood (tooted)</h3>
          <ul>
            <li>Vesho CRM → Hinnakiri — lisa tooted</li>
            <li>Pood kuvatakse leheküljel <code>/pood/</code></li>
            <li>Kliendid saavad tellida läbi portaali</li>
            <li>Tellimused: Vesho CRM → Tellimused</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- KASUTAJAD JA LIGIPÄÄS -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🔐</span>
      <h2>Kasutajad ja ligipääs</h2>
    </div>
    <div class="docs-body">
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Admin-kasutaja lisamine</h3>
          <ul>
            <li>WordPress Admin → Kasutajad → Lisa uus</li>
            <li>Kasutajaroll: <strong>Administraator</strong></li>
            <li>Uus kasutaja saab kõik CRM-i õigused</li>
          </ul>
          <a href="<?php echo admin_url('user-new.php'); ?>" class="docs-link">→ Lisa kasutaja</a>
        </div>
        <div class="docs-card">
          <h3>CRM admini õigused</h3>
          <ul>
            <li>Vesho CRM → Administraatorid</li>
            <li>Saa anda CRM-i ligipääs ilma täis WordPress adminita</li>
            <li>Roll: <code>vesho_crm_admin</code></li>
          </ul>
          <a href="<?php echo admin_url('admin.php?page=vesho-crm-admins'); ?>" class="docs-link">→ Halda admineid</a>
        </div>
        <div class="docs-card">
          <h3>Parooli muutmine</h3>
          <ul>
            <li>WordPress Admin → Kasutajad → Sinu profiil</li>
            <li>Keri alla → "Konto haldamine"</li>
            <li>Genereeri uus parool või kirjuta ise</li>
          </ul>
          <a href="<?php echo admin_url('profile.php'); ?>" class="docs-link">→ Minu profiil</a>
        </div>
        <div class="docs-card">
          <h3>Kahefaktoriline autentimine</h3>
          <ul>
            <li>Soovituslik — installeeri <strong>WP 2FA</strong> pistikprogramm</li>
            <li>WordPress Admin → Pistikprogrammid → Lisa uus → otsi "WP 2FA"</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- UUENDUSED -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🚀</span>
      <h2>Pistikprogrammi ja teema uuendamine</h2>
    </div>
    <div class="docs-body">
      <div class="docs-tip">✅ Praegu paigaldatud: Vesho CRM v<?php echo esc_html($plugin_ver); ?> · Teema v<?php echo esc_html($theme_ver); ?></div>
      <div class="docs-grid">
        <div class="docs-card">
          <h3>Kuidas uuendada</h3>
          <div class="docs-step">
            <div class="docs-step-num">1</div>
            <div class="docs-step-body"><strong>Ava Uuendused</strong><p>Vesho CRM → 🚀 Uuendused — näed kas uus versioon on saadaval</p></div>
          </div>
          <div class="docs-step">
            <div class="docs-step-num">2</div>
            <div class="docs-step-body"><strong>Kliki "Uuenda"</strong><p>Süsteem laeb uue versiooni automaatselt alla ja paigaldab</p></div>
          </div>
          <div class="docs-step">
            <div class="docs-step-num">3</div>
            <div class="docs-step-body"><strong>Kontrolli</strong><p>Versiooni number peaks muutuma — vaata siit lehelt "Süsteemi info"</p></div>
          </div>
          <a href="<?php echo admin_url('admin.php?page=vesho-crm-releases'); ?>" class="docs-link">→ Ava uuendused</a>
        </div>
        <div class="docs-card">
          <h3>WordPress uuendamine</h3>
          <ul>
            <li>WordPress Admin → Tööriistad → Uuendused</li>
            <li>Uuenda WordPress core, pistikprogrammid, teemad eraldi</li>
            <li>Tee <strong>enne uuendust varukoopia</strong></li>
            <li>Peale uuendust kontrolli et veebileht töötab</li>
          </ul>
          <a href="<?php echo admin_url('update-core.php'); ?>" class="docs-link">→ WordPress uuendused</a>
        </div>
        <div class="docs-card">
          <h3>Varukoopia tegemine</h3>
          <ul>
            <li>Installi <strong>UpdraftPlus</strong> (tasuta)</li>
            <li>Seadista automaatne varundamine Google Drive'i / Dropboxi</li>
            <li>Varunda enne iga suuremat muutust</li>
          </ul>
        </div>
        <div class="docs-card">
          <h3>Probleemide korral</h3>
          <ul>
            <li>Luba <strong>WP_DEBUG</strong> — vaata error logi</li>
            <li>Deaktiveeri uusim pistikprogramm</li>
            <li>Vaheta teema ajutiselt Twenty Twenty-Three</li>
            <li>Võta ühendust arendajaga: <a href="mailto:kyamisolutions@gmail.com" style="color:#00b4c8">kyamisolutions@gmail.com</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- KIIRNUPUD -->
  <div class="docs-section">
    <div class="docs-section-head">
      <span class="icon">🗂️</span>
      <h2>Kõik CRM lehed</h2>
    </div>
    <div class="docs-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
        <?php
        $links = [
          ['Töölaud',         'vesho-crm',                   '🏠'],
          ['Kliendid',        'vesho-crm-clients',            '👥'],
          ['Seadmed',         'vesho-crm-devices',            '⚙️'],
          ['Hooldused',       'vesho-crm-maintenances',       '🔧'],
          ['Kalender',        'vesho-crm-calendar',           '📅'],
          ['Töökäsud',        'vesho-crm-workorders',         '📋'],
          ['Töötajad',        'vesho-crm-workers',            '👷'],
          ['Töötunnid',       'vesho-crm-workhours',          '⏱️'],
          ['Arved',           'vesho-crm-invoices',           '🧾'],
          ['Müügiraport',     'vesho-crm-sales',              '📈'],
          ['Tellimused',      'vesho-crm-orders',             '🛒'],
          ['Ladu',            'vesho-crm-inventory',          '📦'],
          ['Hinnakiri',       'vesho-crm-pricelist',          '💰'],
          ['Päringud',        'vesho-crm-requests',           '📩'],
          ['Tugipiletid',     'vesho-crm-tickets',            '🎫'],
          ['Kampaaniad',      'vesho-crm-campaigns',          '📣'],
          ['Portaali teated', 'vesho-crm-notices',            '📢'],
          ['Teenused',        'vesho-crm-services',           '🛠️'],
          ['Meeldetuletused', 'vesho-crm-reminders',          '🔔'],
          ['Ülesanded',       'vesho-crm-tasks',              '✅'],
          ['Tegevuslogi',     'vesho-crm-activity',           '📜'],
          ['Seaded',          'vesho-crm-settings',           '⚙️'],
          ['Administraatorid','vesho-crm-admins',             '👤'],
          ['Uuendused',       'vesho-crm-releases',           '🚀'],
        ];
        foreach ($links as $l): ?>
        <a href="<?php echo admin_url('admin.php?page=' . $l[1]); ?>" style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:9px 12px;text-decoration:none;color:#1e293b;font-size:13px;font-weight:500;transition:background .15s" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
          <span><?php echo $l[2]; ?></span> <?php echo $l[0]; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
