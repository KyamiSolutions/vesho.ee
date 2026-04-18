<?php
if ( ! defined('ABSPATH') ) exit;
if ( ! current_user_can('manage_options') ) wp_die('Puuduvad õigused.');

global $wpdb;

// ── Handle form actions ───────────────────────────────────────────────────────
$msg = '';

if ( isset($_POST['vesho_admin_action']) && check_admin_referer('vesho_manage_admins') ) {
    $action = $_POST['vesho_admin_action'];

    if ( $action === 'add' || $action === 'edit' ) {
        $uid      = absint($_POST['user_id'] ?? 0);
        $role_key = sanitize_key($_POST['vesho_role'] ?? 'vesho_admin');
        $perms    = array_map('sanitize_key', (array)($_POST['perms'] ?? []));

        if ( $action === 'add' ) {
            if ( ! $uid ) {
                $msg = '<div class="notice notice-error"><p>Vali kasutaja otsingust.</p></div>';
            } else {
                $u = get_user_by('id', $uid);
                if ( ! $u ) {
                    $msg = '<div class="notice notice-error"><p>Kasutajat ei leitud.</p></div>';
                } else {
                    if ( ! in_array('administrator', $u->roles) ) {
                        $u->add_role('vesho_crm_admin');
                    }
                    update_user_meta($uid, 'vesho_crm_role', $role_key);
                    update_user_meta($uid, 'vesho_crm_perms', $perms);
                    $msg = '<div class="notice notice-success"><p>' . esc_html($u->display_name) . ' lisatud adminiks.</p></div>';
                }
            }
        } elseif ( $action === 'edit' && $uid ) {
            update_user_meta($uid, 'vesho_crm_role', $role_key);
            update_user_meta($uid, 'vesho_crm_perms', $perms);
            $msg = '<div class="notice notice-success"><p>Admin uuendatud.</p></div>';
        }
    }

    if ( $action === 'delete' ) {
        $uid = absint($_POST['user_id'] ?? 0);
        if ( $uid && $uid !== get_current_user_id() ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($uid);
            $msg = '<div class="notice notice-success"><p>Admin kustutatud.</p></div>';
        }
    }
}

// ── Fetch all vesho admins ────────────────────────────────────────────────────
$admins = get_users(['role__in' => ['administrator','vesho_crm_admin'], 'number' => 200]);

// ── Roles definition ─────────────────────────────────────────────────────────
$all_roles = [
    'superadmin'   => 'Superadmin (täis juurdepääs)',
    'vesho_admin'  => 'Admin (CRM täis, v.a seaded)',
    'accountant'   => 'Arveldaja (arved, raportid)',
    'warehouse'    => 'Laohaldur (ladu, tellimused)',
    'hr'           => 'Töötajate haldur (töötajad, töökäsud)',
];

$all_perms = [
    'dashboard'     => 'Töölaud',
    'clients'       => 'Kliendid',
    'devices'       => 'Seadmed',
    'maintenances'  => 'Hooldused',
    'workorders'    => 'Töökäsud',
    'workers'       => 'Töötajad',
    'workhours'     => 'Töötunnid',
    'invoices'      => 'Arved',
    'orders'        => 'Tellimused',
    'inventory'     => 'Ladu',
    'receipts'      => 'Vastuvõtt',
    'sales'         => 'Müügiraport',
    'campaigns'     => 'Kampaaniad',
    'tickets'       => 'Tugipiletid',
    'calendar'      => 'Kalender',
    'route'         => 'Marsruut',
    'reminders'     => 'Meeldetuletused',
    'settings'      => 'Seaded',
    'pricelist'     => 'Hinnakiri',
    'services'      => 'Teenused',
    'requests'      => 'Päringud',
    'activity_log'  => 'Tegevuslogi',
];

$role_presets = [
    'superadmin'  => array_keys($all_perms),
    'vesho_admin' => array_diff(array_keys($all_perms), ['settings']),
    'accountant'  => ['dashboard','invoices','sales','clients','orders','campaigns'],
    'warehouse'   => ['dashboard','inventory','orders','receipts'],
    'hr'          => ['dashboard','workers','workorders','workhours','calendar','route','reminders'],
];

$edit_uid = absint($_GET['edit_uid'] ?? 0);
$edit_user = $edit_uid ? get_user_by('id', $edit_uid) : null;
$edit_role  = $edit_uid ? (get_user_meta($edit_uid,'vesho_crm_role',true) ?: 'vesho_admin') : '';
$edit_perms = $edit_uid ? (get_user_meta($edit_uid,'vesho_crm_perms',true) ?: []) : [];
?>

<div class="wrap">
<h1>👥 Administraatorid</h1>
<?php echo $msg; ?>

<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start;margin-top:16px">

<!-- ── List ─────────────────────────────────────────────────────────────── -->
<div class="crm-card" style="padding:0;overflow:hidden">
  <table class="wp-list-table widefat fixed striped" style="border:none">
    <thead>
      <tr>
        <th>Nimi</th>
        <th>Kasutajanimi</th>
        <th>Roll</th>
        <th>Juurdepääsud</th>
        <th style="width:120px">Toimingud</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($admins as $u):
        $crm_role  = get_user_meta($u->ID,'vesho_crm_role',true) ?: (in_array('administrator',$u->roles)?'superadmin':'vesho_admin');
        $crm_perms = get_user_meta($u->ID,'vesho_crm_perms',true) ?: ($crm_role==='superadmin' ? array_keys($all_perms) : []);
        $role_lbl  = $all_roles[$crm_role] ?? $crm_role;
        $is_super  = in_array('administrator',$u->roles);
    ?>
    <tr>
      <td><strong><?php echo esc_html($u->display_name); ?></strong><br><small style="color:#64748b"><?php echo esc_html($u->user_email); ?></small></td>
      <td><?php echo esc_html($u->user_login); ?></td>
      <td>
        <span style="padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;background:<?php echo $is_super?'#fef3c7':'#dbeafe'; ?>;color:<?php echo $is_super?'#92400e':'#1d4ed8'; ?>">
          <?php echo esc_html($role_lbl); ?>
        </span>
      </td>
      <td style="font-size:11px;color:#64748b">
        <?php echo $is_super ? 'Kõik' : implode(', ', array_map(fn($p)=>$all_perms[$p]??$p, array_slice($crm_perms,0,4))) . (count($crm_perms)>4 ? ' +' . (count($crm_perms)-4) : ''); ?>
      </td>
      <td>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-admins&edit_uid='.$u->ID); ?>" class="crm-btn crm-btn-sm crm-btn-outline">✏️</a>
        <?php if ($u->ID !== get_current_user_id() && !$is_super): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Kustuta admin?')">
          <?php wp_nonce_field('vesho_manage_admins'); ?>
          <input type="hidden" name="vesho_admin_action" value="delete">
          <input type="hidden" name="user_id" value="<?php echo $u->ID; ?>">
          <button type="submit" class="crm-btn crm-btn-sm crm-btn-outline" style="color:#dc2626;border-color:#dc2626">🗑️</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ── Add/Edit form ─────────────────────────────────────────────────────── -->
<div class="crm-card">
  <h3 style="margin-top:0"><?php echo $edit_user ? 'Muuda admini' : 'Lisa admin'; ?></h3>
  <form method="POST">
    <?php wp_nonce_field('vesho_manage_admins'); ?>
    <input type="hidden" name="vesho_admin_action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
    <?php if ($edit_user): ?>
    <input type="hidden" name="user_id" value="<?php echo $edit_uid; ?>">
    <div style="margin-bottom:14px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
      <strong><?php echo esc_html($edit_user->display_name); ?></strong>
      <span style="color:#64748b"> — <?php echo esc_html($edit_user->user_email); ?></span>
    </div>
    <?php else: ?>
    <div style="margin-bottom:14px;position:relative">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Otsi kasutajat *</label>
      <input type="text" id="user-search-input" placeholder="Nimi, kasutajanimi või e-post…"
             class="regular-text" style="width:100%" autocomplete="off">
      <div id="user-search-results" style="display:none;position:absolute;z-index:100;width:100%;border:1px solid #e2e8f0;border-radius:6px;background:#fff;margin-top:2px;max-height:200px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.1)"></div>
      <input type="hidden" name="user_id" id="selected-user-id" required>
      <div id="selected-user-display" style="display:none;margin-top:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;font-size:13px;align-items:center;justify-content:space-between">
        <span id="selected-user-label"></span>
        <button type="button" onclick="clearUserSelection()" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:16px;padding:0;margin-left:8px">✕</button>
      </div>
    </div>
    <?php endif; ?>

    <div style="margin-bottom:14px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Roll</label>
      <select name="vesho_role" id="role-select" style="width:100%" onchange="applyPreset(this.value)">
        <?php foreach ($all_roles as $k => $lbl): ?>
        <option value="<?php echo $k; ?>" <?php selected($edit_role, $k); ?>><?php echo esc_html($lbl); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom:18px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:8px">Juurdepääsud</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px" id="perm-grid">
        <?php foreach ($all_perms as $k => $lbl): ?>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer">
          <input type="checkbox" name="perms[]" value="<?php echo $k; ?>" id="perm-<?php echo $k; ?>"
                 <?php echo in_array($k, $edit_perms) ? 'checked' : ''; ?>>
          <?php echo esc_html($lbl); ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;gap:8px">
      <button type="submit" class="crm-btn crm-btn-primary" style="flex:1"><?php echo $edit_user ? '💾 Salvesta' : '➕ Lisa admin'; ?></button>
      <?php if ($edit_user): ?>
      <a href="<?php echo admin_url('admin.php?page=vesho-crm-admins'); ?>" class="crm-btn crm-btn-outline">✕</a>
      <?php endif; ?>
    </div>
  </form>
</div>
</div>
</div>

<script>
var presets = <?php echo json_encode($role_presets); ?>;
function applyPreset(role){
  var all = document.querySelectorAll('#perm-grid input[type="checkbox"]');
  var perms = presets[role] || [];
  all.forEach(function(cb){ cb.checked = perms.includes(cb.value); });
}

// ── User search ──────────────────────────────────────────────────────────────
var searchTimer;
var searchInput   = document.getElementById('user-search-input');
var searchResults = document.getElementById('user-search-results');
var selectedId    = document.getElementById('selected-user-id');
var selectedDisp  = document.getElementById('selected-user-display');
var selectedLabel = document.getElementById('selected-user-label');

if (searchInput) {
  searchInput.addEventListener('input', function(){
    clearTimeout(searchTimer);
    var q = this.value.trim();
    if (q.length < 2) { searchResults.style.display='none'; return; }
    searchTimer = setTimeout(function(){
      fetch(ajaxurl + '?action=vesho_search_wp_users&q=' + encodeURIComponent(q) + '&_wpnonce=<?php echo wp_create_nonce('vesho_admin_nonce'); ?>')
        .then(r => r.json())
        .then(function(res){
          if (!res.success || !res.data.length) {
            searchResults.innerHTML = '<div style="padding:10px 14px;color:#94a3b8;font-size:13px">Kasutajat ei leitud</div>';
          } else {
            searchResults.innerHTML = res.data.map(function(u){
              return '<div class="user-result-item" data-id="'+u.id+'" data-label="'+u.label+'" style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9">'
                + '<strong>'+u.display+'</strong> <span style="color:#64748b">'+u.login+'</span><br>'
                + '<small style="color:#94a3b8">'+u.email+'</small></div>';
            }).join('');
            searchResults.querySelectorAll('.user-result-item').forEach(function(el){
              el.addEventListener('mouseenter', function(){ this.style.background='#f8fafc'; });
              el.addEventListener('mouseleave', function(){ this.style.background=''; });
              el.addEventListener('click', function(){
                selectUser(this.dataset.id, this.dataset.label);
              });
            });
          }
          searchResults.style.display = 'block';
        });
    }, 250);
  });

  document.addEventListener('click', function(e){
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = 'none';
    }
  });
}

function selectUser(id, label) {
  selectedId.value           = id;
  selectedLabel.textContent  = label;
  selectedDisp.style.display = 'flex';
  searchInput.value          = '';
  searchResults.style.display = 'none';
}

function clearUserSelection() {
  selectedId.value           = '';
  selectedDisp.style.display = 'none';
}
</script>
