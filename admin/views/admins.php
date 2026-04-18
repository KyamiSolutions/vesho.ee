<?php
if ( ! defined('ABSPATH') ) exit;
if ( ! current_user_can('manage_options') ) wp_die('Puuduvad õigused.');

global $wpdb;

// ── Handle form actions ───────────────────────────────────────────────────────
$msg = '';

if ( isset($_POST['vesho_admin_action']) && check_admin_referer('vesho_manage_admins') ) {
    $action = $_POST['vesho_admin_action'];

    if ( $action === 'add' || $action === 'edit' ) {
        $uid        = absint($_POST['user_id'] ?? 0);
        $username   = sanitize_user($_POST['username'] ?? '');
        $email      = sanitize_email($_POST['email'] ?? '');
        $display    = sanitize_text_field($_POST['display_name'] ?? '');
        $password   = $_POST['password'] ?? '';
        $role_key   = sanitize_key($_POST['vesho_role'] ?? 'vesho_admin');
        $perms      = array_map('sanitize_key', (array)($_POST['perms'] ?? []));

        if ( $action === 'add' ) {
            if ( ! $username || ! $email ) {
                $msg = '<div class="notice notice-error"><p>Kasutajanimi ja e-post on kohustuslikud.</p></div>';
            } elseif ( username_exists($username) || email_exists($email) ) {
                $msg = '<div class="notice notice-error"><p>Kasutajanimi või e-post on juba kasutusel.</p></div>';
            } else {
                $uid = wp_create_user( $username, $password ?: wp_generate_password(), $email );
                if ( is_wp_error($uid) ) {
                    $msg = '<div class="notice notice-error"><p>' . esc_html($uid->get_error_message()) . '</p></div>';
                } else {
                    wp_update_user(['ID'=>$uid,'display_name'=>$display]);
                    $u = new WP_User($uid);
                    $u->set_role('vesho_crm_admin');
                    update_user_meta($uid, 'vesho_crm_role', $role_key);
                    update_user_meta($uid, 'vesho_crm_perms', $perms);
                    $msg = '<div class="notice notice-success"><p>Admin lisatud.</p></div>';
                }
            }
        } elseif ( $action === 'edit' && $uid ) {
            $upd = ['ID' => $uid];
            if ( $display )  $upd['display_name'] = $display;
            if ( $email )    $upd['user_email']    = $email;
            if ( $password ) $upd['user_pass']     = $password;
            wp_update_user($upd);
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
    <?php endif; ?>

    <?php if (!$edit_user): ?>
    <div style="margin-bottom:14px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kasutajanimi *</label>
      <input type="text" name="username" required class="regular-text" style="width:100%">
    </div>
    <?php endif; ?>

    <div style="margin-bottom:14px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Kuvatav nimi</label>
      <input type="text" name="display_name" value="<?php echo esc_attr($edit_user?->display_name??''); ?>" class="regular-text" style="width:100%">
    </div>

    <div style="margin-bottom:14px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">E-post<?php echo $edit_user?'':' *'; ?></label>
      <input type="email" name="email" value="<?php echo esc_attr($edit_user?->user_email??''); ?>" <?php echo $edit_user?'':'required'; ?> class="regular-text" style="width:100%">
    </div>

    <div style="margin-bottom:14px">
      <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px"><?php echo $edit_user ? 'Uus parool (tühi = muutmata)' : 'Parool'; ?></label>
      <input type="password" name="password" <?php echo $edit_user?'':'required'; ?> class="regular-text" style="width:100%" autocomplete="new-password">
    </div>

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
</script>
