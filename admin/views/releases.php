<?php defined('ABSPATH') || exit;
global $wpdb;

$plugin_info  = Vesho_CRM_Updater::get_release_info('plugin');
$theme_info   = Vesho_CRM_Updater::get_release_info('theme');
$theme        = wp_get_theme('vesho');
$server_url   = Vesho_CRM_Updater::get_server_url();
$upload       = wp_upload_dir();
$releases_dir = $upload['basedir'] . '/vesho-releases';
$nonce        = wp_create_nonce('vesho_admin_nonce');
?>
<div class="wrap vesho-admin-wrap">
<h1 class="crm-page-title">🚀 Uuenduste haldus</h1>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

<!-- PLUGIN RELEASE CARD -->
<div class="crm-card">
  <div class="crm-card-header"><span class="crm-card-title">🔌 Vesho CRM plugin</span></div>
  <div style="padding:20px">
    <table class="widefat" style="margin-bottom:16px">
      <tr><td style="font-weight:600;width:160px">Paigaldatud versioon</td><td><span style="background:#e0f7fa;color:#006064;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700"><?php echo VESHO_CRM_VERSION; ?></span></td></tr>
      <tr><td style="font-weight:600">Viimane uuenduspakett</td><td><?php echo $plugin_info ? '<span style="background:#e8f5e9;color:#1b5e20;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700">' . esc_html($plugin_info->version) . '</span>' : '<span style="color:#999">—</span>'; ?></td></tr>
      <tr><td style="font-weight:600">Paketi loomisaeg</td><td><?php echo $plugin_info ? esc_html($plugin_info->last_updated) : '—'; ?></td></tr>
      <?php if ($plugin_info): ?>
      <tr><td style="font-weight:600">Allalaadimislink</td><td><a href="<?php echo esc_url($plugin_info->download_url); ?>" target="_blank" style="color:#00b4c8;font-size:12px">📥 vesho-crm.zip</a></td></tr>
      <?php endif; ?>
    </table>

    <hr style="margin:16px 0">
    <h4 style="margin-bottom:12px">Loo uus uuenduspakett</h4>
    <div style="margin-bottom:10px">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px">Uus versiooninumber</label>
      <input type="text" id="plugin-new-version" value="<?php
        $parts = explode('.', VESHO_CRM_VERSION);
        $parts[2] = (int)($parts[2] ?? 0) + 1;
        echo implode('.', $parts);
      ?>" style="width:140px;padding:7px 10px;border:1.5px solid #ddd;border-radius:6px;font-size:14px">
    </div>
    <div style="margin-bottom:12px">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px">Muudatuste logi (kuvatakse uuenduse popupis)</label>
      <textarea id="plugin-changelog" rows="4" style="width:100%;padding:8px;border:1.5px solid #ddd;border-radius:6px;font-size:13px;resize:vertical" placeholder="- Lisatud kreeditarved&#10;- Parandatud inventuuri viga&#10;- Uuendused portaalis"></textarea>
    </div>
    <button id="btn-create-plugin-release" class="button button-primary" style="background:#00b4c8;border-color:#00b4c8">
      🚀 Loo plugin uuenduspakett
    </button>
    <div id="plugin-release-msg" style="margin-top:10px;display:none"></div>
  </div>
</div>

<!-- THEME RELEASE CARD -->
<div class="crm-card">
  <div class="crm-card-header"><span class="crm-card-title">🎨 Vesho teema</span></div>
  <div style="padding:20px">
    <table class="widefat" style="margin-bottom:16px">
      <tr><td style="font-weight:600;width:160px">Paigaldatud versioon</td><td><span style="background:#e0f7fa;color:#006064;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700"><?php echo esc_html($theme->get('Version')); ?></span></td></tr>
      <tr><td style="font-weight:600">Viimane uuenduspakett</td><td><?php echo $theme_info ? '<span style="background:#e8f5e9;color:#1b5e20;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700">' . esc_html($theme_info->version) . '</span>' : '<span style="color:#999">—</span>'; ?></td></tr>
      <tr><td style="font-weight:600">Paketi loomisaeg</td><td><?php echo $theme_info ? esc_html($theme_info->last_updated) : '—'; ?></td></tr>
      <?php if ($theme_info): ?>
      <tr><td style="font-weight:600">Allalaadimislink</td><td><a href="<?php echo esc_url($theme_info->download_url); ?>" target="_blank" style="color:#00b4c8;font-size:12px">📥 vesho-theme.zip</a></td></tr>
      <?php endif; ?>
    </table>

    <hr style="margin:16px 0">
    <h4 style="margin-bottom:12px">Loo uus uuenduspakett</h4>
    <div style="margin-bottom:10px">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px">Uus versiooninumber</label>
      <?php
        $tv = explode('.', $theme->get('Version'));
        $tv[2] = (int)($tv[2] ?? 0) + 1;
      ?>
      <input type="text" id="theme-new-version" value="<?php echo implode('.', $tv); ?>" style="width:140px;padding:7px 10px;border:1.5px solid #ddd;border-radius:6px;font-size:14px">
    </div>
    <div style="margin-bottom:12px">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px">Muudatuste logi</label>
      <textarea id="theme-changelog" rows="4" style="width:100%;padding:8px;border:1.5px solid #ddd;border-radius:6px;font-size:13px;resize:vertical" placeholder="- Disain uuendatud&#10;- Mobiilivaade parandatud"></textarea>
    </div>
    <button id="btn-create-theme-release" class="button button-primary" style="background:#00b4c8;border-color:#00b4c8">
      🚀 Loo teema uuenduspakett
    </button>
    <div id="theme-release-msg" style="margin-top:10px;display:none"></div>
  </div>
</div>

</div><!-- /grid -->

<!-- UPDATE SERVER CONFIG -->
<div class="crm-card" style="margin-bottom:20px">
  <div class="crm-card-header"><span class="crm-card-title">⚙️ Update serveri seaded</span></div>
  <div style="padding:20px">
    <table class="form-table">
      <tr>
        <th style="width:200px">Update serveri URL</th>
        <td>
          <input type="text" id="update-server-url" value="<?php echo esc_url($server_url); ?>" class="regular-text" style="width:400px">
          <button class="button" id="btn-save-server-url">Salvesta</button>
          <p class="description">URL kus asuvad <code>plugin-info.json</code>, <code>theme-info.json</code> ja ZIP failid.<br>
          Vaikimisi: <code><?php echo esc_url($server_url); ?></code></p>
        </td>
      </tr>
      <tr>
        <th>Releases kataloog</th>
        <td>
          <code><?php echo esc_html($releases_dir); ?></code><br>
          <?php if (file_exists($releases_dir)): ?>
          <span style="color:#27ae60">✅ Kataloog olemas</span>
          <?php else: ?>
          <span style="color:#e74c3c">❌ Kataloog puudub — luuakse esimese paketi loomisel</span>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th>Failid serveris</th>
        <td>
          <?php
          $files = ['plugin-info.json', 'theme-info.json', 'vesho-crm.zip', 'vesho-theme.zip'];
          foreach ($files as $f):
            $exists = file_exists($releases_dir . '/' . $f);
            echo '<div style="margin-bottom:4px">';
            echo $exists ? '✅' : '⬜';
            echo ' <code>' . esc_html($f) . '</code>';
            if ($exists) {
              $size = round(filesize($releases_dir . '/' . $f) / 1024, 0);
              echo ' <span style="color:#888;font-size:12px">(' . $size . ' KB)</span>';
            }
            echo '</div>';
          endforeach;
          ?>
        </td>
      </tr>
    </table>

    <hr style="margin:16px 0">
    <h4 style="margin-bottom:8px">🔍 WordPress uuenduste kontroll</h4>
    <p style="margin-bottom:12px;color:#666;font-size:13px">
      WordPress kontrollib uuendusi automaatselt kord päevas. Vajuta allolevat nuppu kohe kontrollimiseks.
    </p>
    <button id="btn-force-check" class="button">
      🔄 Kontrolli uuendusi kohe
    </button>
    <span id="force-check-msg" style="margin-left:10px;font-size:13px"></span>

    <hr style="margin:16px 0">
    <h4 style="margin-bottom:8px">📋 Kuidas kasutada teisel serveril</h4>
    <p style="font-size:13px;color:#555">
      Lisa live serveril <strong>wp-config.php</strong> faili või vesho-crm plugina seadetesse update serveri URL:<br><br>
      <code style="background:#f5f5f5;padding:8px 12px;display:inline-block;border-radius:4px;font-size:13px">
        Update serveri URL: <?php echo esc_html($server_url); ?>
      </code><br><br>
      Live server kontrollib seda URLi automaatselt. Kui siin lood uue paketi, näeb live server kohe uuendust WP dashboard → Uuendused lehel.
    </p>
  </div>
</div>

<script>
var veshoNonce = '<?php echo $nonce; ?>';

function showMsg(elId, msg, ok) {
  var el = document.getElementById(elId);
  el.style.display = 'block';
  el.style.padding = '10px 14px';
  el.style.borderRadius = '6px';
  el.style.fontSize = '13px';
  el.style.background = ok ? '#ecfdf5' : '#fef2f2';
  el.style.color = ok ? '#065f46' : '#991b1b';
  el.style.border = ok ? '1px solid #6ee7b7' : '1px solid #fca5a5';
  el.innerHTML = msg;
}

function createRelease(type) {
  var version   = document.getElementById(type + '-new-version').value.trim();
  var changelog = document.getElementById(type + '-changelog').value.trim();
  var btn       = document.getElementById('btn-create-' + type + '-release');
  var msgEl     = type + '-release-msg';

  if (!version) { showMsg(msgEl, 'Sisesta versiooninumber!', false); return; }

  btn.disabled = true;
  btn.textContent = '⏳ Loon paketti...';

  fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=vesho_create_release&nonce=' + veshoNonce
        + '&type=' + encodeURIComponent(type)
        + '&version=' + encodeURIComponent(version)
        + '&changelog=' + encodeURIComponent(changelog)
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      showMsg(msgEl,
        '✅ ' + d.data.message + '<br>' +
        '<a href="' + d.data.download_url + '" target="_blank" style="color:#059669">📥 Laadi alla</a> &nbsp;|&nbsp; ' +
        '<a href="' + d.data.info_url + '" target="_blank" style="color:#059669">📄 Vaata JSON</a>',
        true
      );
      btn.textContent = '✅ Loodud!';
      setTimeout(() => {
        btn.disabled = false;
        btn.textContent = '🚀 Loo ' + type + ' uuenduspakett';
      }, 3000);
    } else {
      showMsg(msgEl, '❌ ' + d.data, false);
      btn.disabled = false;
      btn.textContent = '🚀 Loo ' + type + ' uuenduspakett';
    }
  });
}

document.getElementById('btn-create-plugin-release').addEventListener('click', () => createRelease('plugin'));
document.getElementById('btn-create-theme-release').addEventListener('click',  () => createRelease('theme'));

document.getElementById('btn-force-check').addEventListener('click', function() {
  this.disabled = true;
  document.getElementById('force-check-msg').textContent = 'Kontrollin...';
  fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=vesho_force_update_check&nonce=' + veshoNonce
  })
  .then(r => r.json())
  .then(d => {
    document.getElementById('force-check-msg').textContent = d.success ? '✅ ' + d.data.message : '❌ Viga';
    document.getElementById('btn-force-check').disabled = false;
    if (d.success) setTimeout(() => window.location.href = '/wp-admin/update-core.php', 1500);
  });
});

document.getElementById('btn-save-server-url').addEventListener('click', function() {
  var url = document.getElementById('update-server-url').value.trim();
  fetch(ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=vesho_save_update_server&nonce=' + veshoNonce + '&url=' + encodeURIComponent(url)
  }).then(r => r.json()).then(d => {
    alert(d.success ? 'Salvestatud!' : 'Viga');
  });
});
</script>
