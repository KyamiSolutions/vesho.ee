<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action         = isset($_GET['action'])         ? sanitize_text_field($_GET['action'])  : '';
$maintenance_id = isset($_GET['maintenance_id']) ? absint($_GET['maintenance_id'])        : 0;
$device_id      = isset($_GET['device_id'])      ? absint($_GET['device_id'])             : 0;
$search         = isset($_GET['s'])              ? sanitize_text_field($_GET['s'])        : '';
$filter_status  = isset($_GET['status'])         ? sanitize_text_field($_GET['status'])  : '';

$all_devices = $wpdb->get_results("SELECT d.id, d.name, c.name as client_name FROM {$wpdb->prefix}vesho_devices d LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id=c.id ORDER BY c.name,d.name ASC");
$all_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");

$edit = null;
if ( $action === 'edit' && $maintenance_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_maintenances WHERE id=%d", $maintenance_id));
}

$where  = '1=1';
if ($device_id)     { $where .= $wpdb->prepare(' AND m.device_id=%d', $device_id); }
if ($filter_status) { $where .= $wpdb->prepare(' AND m.status=%s', $filter_status); }
if ($search)        { $where .= $wpdb->prepare(' AND (m.description LIKE %s OR d.name LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$maintenances = $wpdb->get_results(
    "SELECT m.*, d.name as device_name, c.name as client_name
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id=d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id=c.id
     WHERE $where ORDER BY m.scheduled_date DESC LIMIT 300"
);
$total = count($maintenances);

$statuses = ['pending'=>'Ootel (broneeringud)','scheduled'=>'Planeeritud','completed'=>'Lõpetatud','cancelled'=>'Tühistatud'];
$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances WHERE status='pending'");
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📅 Hooldused <span class="crm-count">(<?php echo $total; ?>)</span>
        <?php if ($pending_count > 0) : ?>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&status=pending'); ?>"
           style="margin-left:12px;font-size:13px;padding:3px 10px;background:#f59e0b;color:#fff;border-radius:12px;text-decoration:none;font-weight:600">
            🔔 <?php echo $pending_count; ?> ootel broneeringut
        </a>
        <?php endif; ?>
    </h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Hooldus lisatud!','updated'=>'Hooldus uuendatud!','deleted'=>'Hooldus kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda hooldust':'Lisa uus hooldus'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_maintenance'); ?>
            <input type="hidden" name="action" value="vesho_save_maintenance">
            <?php if ($edit) : ?><input type="hidden" name="maintenance_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Seade *</label>
                    <select class="crm-form-select" name="device_id" required>
                        <option value="">— Vali seade —</option>
                        <?php foreach ($all_devices as $d) : ?>
                            <option value="<?php echo $d->id; ?>" <?php selected($edit->device_id??$device_id, $d->id); ?>><?php echo esc_html($d->client_name.' – '.$d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Planeeritud kuupäev</label>
                    <input class="crm-form-input" type="date" name="scheduled_date" value="<?php echo esc_attr($edit->scheduled_date??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Tehtud kuupäev</label>
                    <input class="crm-form-input" type="date" name="completed_date" value="<?php echo esc_attr($edit->completed_date??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Staatus</label>
                    <select class="crm-form-select" name="status">
                        <?php foreach ($statuses as $v=>$l) : ?>
                            <option value="<?php echo $v; ?>" <?php selected($edit->status??'scheduled',$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Töötaja</label>
                    <select class="crm-form-select" name="worker_id">
                        <option value="">— Vali töötaja —</option>
                        <?php foreach ($all_workers as $w) : ?>
                            <option value="<?php echo $w->id; ?>" <?php selected($edit->worker_id??0,$w->id); ?>><?php echo esc_html($w->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Hind (€)</label>
                    <input class="crm-form-input" type="number" step="0.01" name="locked_price" value="<?php echo esc_attr($edit->locked_price??''); ?>">
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus</label>
                    <textarea class="crm-form-textarea" name="description"><?php echo esc_textarea($edit->description??''); ?></textarea>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Märkused</label>
                    <textarea class="crm-form-textarea" name="notes"><?php echo esc_textarea($edit->notes??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>

        <?php if ($edit): ?>
        <?php
        $maint_photos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE maintenance_id=%d ORDER BY created_at ASC", $edit->id
        ));
        ?>
        <div style="border-top:1px solid #e5edf4;margin-top:24px;padding-top:20px">
          <div style="font-weight:700;font-size:14px;color:#0d1f2d;margin-bottom:12px">📷 Fotod (<?php echo count($maint_photos); ?>/10)</div>
          <div id="maint-photo-grid" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px">
            <?php foreach ($maint_photos as $mp): ?>
            <div id="mphoto-<?php echo $mp->id; ?>" style="position:relative;width:100px;height:100px">
              <img src="<?php echo esc_url($mp->filename); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
              <button type="button" onclick="deleteMaintPhoto(<?php echo $mp->id; ?>)" style="position:absolute;top:2px;right:2px;width:22px;height:22px;background:rgba(220,38,38,.85);border:none;border-radius:50%;color:#fff;font-size:12px;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center">✕</button>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($maint_photos) < 10): ?>
          <label style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#e0f7fa;border:1px solid #00b4c8;color:#007a8a;border-radius:6px;font-size:13px;cursor:pointer;font-weight:500">
            📤 Lisa foto
            <input type="file" id="maint-photo-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="uploadMaintPhoto(this,<?php echo $edit->id; ?>)">
          </label>
          <span id="maint-photo-msg" style="margin-left:10px;font-size:13px;color:#64748b"></span>
          <?php endif; ?>
        </div>
        <script>
        var maintAdminNonce = '<?php echo wp_create_nonce('vesho_admin_nonce'); ?>';
        function uploadMaintPhoto(input, mid) {
          if (!input.files[0]) return;
          var msg = document.getElementById('maint-photo-msg');
          msg.textContent = 'Laen üles...';
          var fd = new FormData();
          fd.append('action', 'vesho_admin_upload_maint_photo');
          fd.append('nonce', maintAdminNonce);
          fd.append('maintenance_id', mid);
          fd.append('photo', input.files[0]);
          fetch(ajaxurl, {method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            input.value = '';
            if (d.success) {
              msg.textContent = '✓ Foto lisatud';
              var grid = document.getElementById('maint-photo-grid');
              var div = document.createElement('div');
              div.id = 'mphoto-' + d.data.photo_id;
              div.style.cssText = 'position:relative;width:100px;height:100px';
              div.innerHTML = '<img src="'+d.data.url+'" style="width:100%;height:100%;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">'
                + '<button type="button" onclick="deleteMaintPhoto('+d.data.photo_id+')" style="position:absolute;top:2px;right:2px;width:22px;height:22px;background:rgba(220,38,38,.85);border:none;border-radius:50%;color:#fff;font-size:12px;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center">✕</button>';
              grid.appendChild(div);
              setTimeout(()=>{ msg.textContent=''; }, 2000);
            } else { msg.textContent = '❌ ' + (d.data && d.data.message || 'Viga'); }
          }).catch(()=>{ msg.textContent = '❌ Ühenduse viga'; });
        }
        function deleteMaintPhoto(photoId) {
          if (!confirm('Kustuta foto?')) return;
          fetch(ajaxurl, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=vesho_admin_delete_maint_photo&nonce='+maintAdminNonce+'&photo_id='+photoId
          }).then(r=>r.json()).then(d=>{
            if (d.success) { var el=document.getElementById('mphoto-'+photoId); if(el) el.remove(); }
          });
        }
        </script>
        <?php endif; ?>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&action=add'.($device_id?'&device_id='.$device_id:'')); ?>" class="crm-btn crm-btn-primary">+ Lisa hooldus</a>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_export_maintenances_csv'),'vesho_export_maintenances_csv'); ?>" class="crm-btn crm-btn-outline crm-btn-sm" title="Laadi alla CSV (Excel)">⬇️ CSV</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-maintenances">
                <select class="crm-form-select" name="status" style="max-width:160px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik staatused</option>
                    <?php foreach ($statuses as $v=>$l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($filter_status,$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi seadet, kirjeldust..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($maintenances)) : ?>
            <div class="crm-empty">Hooldusi ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Seade</th><th>Klient</th><th>Planeeritud</th><th>Tehtud</th><th>Staatus</th><th>Hind</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($maintenances as $m) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $m->id; ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&action=edit&device_id='.$m->device_id); ?>"><?php echo esc_html($m->device_name?:'–'); ?></a></td>
                <td><?php echo esc_html($m->client_name?:'–'); ?></td>
                <td><?php echo vesho_crm_format_date($m->scheduled_date); ?></td>
                <td><?php echo vesho_crm_format_date($m->completed_date); ?></td>
                <td><?php echo vesho_crm_status_badge($m->status); ?></td>
                <td><?php echo $m->locked_price!==null ? vesho_crm_format_money($m->locked_price) : '–'; ?></td>
                <td class="td-actions">
                    <?php if ($m->status === 'pending') : ?>
                    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('vesho_save_maintenance'); ?>
                        <input type="hidden" name="action" value="vesho_save_maintenance">
                        <input type="hidden" name="maintenance_id" value="<?php echo $m->id; ?>">
                        <input type="hidden" name="device_id" value="<?php echo $m->device_id; ?>">
                        <input type="hidden" name="scheduled_date" value="<?php echo esc_attr($m->scheduled_date); ?>">
                        <input type="hidden" name="status" value="scheduled">
                        <input type="hidden" name="description" value="<?php echo esc_attr($m->description); ?>">
                        <button type="submit" class="crm-btn crm-btn-sm" style="background:#10b981;color:#fff;border:none;cursor:pointer" title="Kinnita broneering + saada kliendile email">✓ Kinnita</button>
                    </form>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&action=edit&maintenance_id='.$m->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_maintenance&maintenance_id='.$m->id),'vesho_delete_maintenance'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta hooldus?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
