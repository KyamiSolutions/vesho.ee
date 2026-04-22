<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action       = isset($_GET['action'])       ? sanitize_text_field($_GET['action'])  : '';
$workorder_id = isset($_GET['workorder_id']) ? absint($_GET['workorder_id'])          : 0;
$search       = isset($_GET['s'])            ? sanitize_text_field($_GET['s'])        : '';
$filter_st    = isset($_GET['status'])       ? sanitize_text_field($_GET['status'])  : '';

$all_clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_clients ORDER BY name ASC");
$all_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");
$all_devices = $wpdb->get_results("SELECT d.id, d.name, c.name as client_name FROM {$wpdb->prefix}vesho_devices d LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id=c.id ORDER BY d.name ASC");

$edit = null;
if ( $action === 'edit' && $workorder_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_workorders WHERE id=%d", $workorder_id));
}

$where = '1=1';
if ($filter_st) { $where .= $wpdb->prepare(' AND wo.status=%s', $filter_st); }
if ($search)    { $where .= $wpdb->prepare(' AND (wo.title LIKE %s OR c.name LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$workorders = $wpdb->get_results(
    "SELECT wo.*, c.name as client_name, w.name as worker_name, d.name as device_name,
            COUNT(ph.id) as photo_count
     FROM {$wpdb->prefix}vesho_workorders wo
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON wo.client_id=c.id
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON wo.worker_id=w.id
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON wo.device_id=d.id
     LEFT JOIN {$wpdb->prefix}vesho_workorder_photos ph ON ph.workorder_id=wo.id
     WHERE $where GROUP BY wo.id ORDER BY wo.created_at DESC LIMIT 200"
);
$total = count($workorders);

$statuses   = ['open'=>'Ootel','assigned'=>'Määratud','in_progress'=>'Töös','completed'=>'Lõpetatud','cancelled'=>'Tühistatud'];
$priorities = ['low'=>'Madal','normal'=>'Tavaline','high'=>'Kõrge','urgent'=>'Kiire'];

// ── Töötajate tööaeg (kuu) widget ─────────────────────────────────────────────
$kuu_start = date('Y-m-01');
$nadal_start = date('Y-m-d', strtotime('monday this week'));
$worker_time_stats = $wpdb->get_results($wpdb->prepare(
    "SELECT w.id, w.name,
            COALESCE(SUM(CASE WHEN wh.date >= %s THEN wh.hours ELSE 0 END), 0) as kuu_hours,
            COALESCE(SUM(CASE WHEN wh.date >= %s THEN wh.hours ELSE 0 END), 0) as nadal_hours,
            COUNT(DISTINCT CASE WHEN wo.status IN ('open','assigned','in_progress') THEN wo.id END) as active_wo
     FROM {$wpdb->prefix}vesho_workers w
     LEFT JOIN {$wpdb->prefix}vesho_work_hours wh ON wh.worker_id = w.id
     LEFT JOIN {$wpdb->prefix}vesho_workorders wo ON wo.worker_id = w.id AND wo.status IN ('open','assigned','in_progress')
     WHERE w.active = 1
     GROUP BY w.id ORDER BY kuu_hours DESC, w.name ASC",
    $kuu_start, $nadal_start
));

// ── PDF print view ────────────────────────────────────────────────────────────
if ( $action === 'print' && $workorder_id ) {
    $wo = $wpdb->get_row( $wpdb->prepare(
        "SELECT wo.*, c.name as client_name, c.address as client_address, c.phone as client_phone, c.email as client_email,
                w.name as worker_name,
                d.name as device_name, d.model as device_model, d.serial_number
         FROM {$wpdb->prefix}vesho_workorders wo
         LEFT JOIN {$wpdb->prefix}vesho_clients c ON wo.client_id=c.id
         LEFT JOIN {$wpdb->prefix}vesho_workers w ON wo.worker_id=w.id
         LEFT JOIN {$wpdb->prefix}vesho_devices d ON wo.device_id=d.id
         WHERE wo.id=%d", $workorder_id
    ) );
    if ( ! $wo ) wp_die( 'Töökäsku ei leitud' );
    $used_items = $wpdb->get_results( $wpdb->prepare(
        "SELECT iu.quantity, inv.name, inv.unit, inv.unit_price,
                (iu.quantity * inv.unit_price) as line_total
         FROM {$wpdb->prefix}vesho_inventory_usage iu
         JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id = iu.inventory_id
         WHERE iu.workorder_id = %d", $workorder_id
    ) );
    $co_name  = get_option( 'vesho_company_name', 'Vesho OÜ' );
    $co_addr  = get_option( 'vesho_company_address', '' );
    $co_email = get_option( 'vesho_company_email', '' );
    $co_phone = get_option( 'vesho_company_phone', '' );
    $status_labels   = ['open'=>'Ootel','assigned'=>'Määratud','in_progress'=>'Töös','completed'=>'Lõpetatud','cancelled'=>'Tühistatud'];
    $priority_labels = ['low'=>'Madal','normal'=>'Tavaline','high'=>'Kõrge','urgent'=>'Kiire'];
    ?>
    <style>
    body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:0;padding:24px}
    .wo-header{display:flex;justify-content:space-between;margin-bottom:20px;border-bottom:2px solid #1e293b;padding-bottom:16px}
    .wo-title{font-size:24px;font-weight:700;color:#1e293b}
    .wo-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;margin-bottom:20px}
    .wo-meta-item{display:flex;gap:8px}
    .wo-meta-label{color:#64748b;font-size:12px;min-width:120px;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
    table{width:100%;border-collapse:collapse;margin:16px 0}
    th{background:#f0f4f8;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
    td{padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13px}
    .total-row td{font-weight:700;border-top:2px solid #e2e8f0}
    @media print{button{display:none}}
    </style>
    <div class="wo-header">
        <div>
            <div class="wo-title">Töökäsk #<?php echo $wo->id; ?></div>
            <div style="font-size:13px;margin-top:4px;color:#64748b"><?php echo esc_html($wo->title); ?></div>
        </div>
        <div style="text-align:right">
            <div style="font-weight:700;font-size:15px"><?php echo esc_html($co_name); ?></div>
            <?php if ($co_addr)  echo '<div style="font-size:12px;color:#555">'.esc_html($co_addr).'</div>'; ?>
            <?php if ($co_email) echo '<div style="font-size:12px;color:#555">'.esc_html($co_email).'</div>'; ?>
            <?php if ($co_phone) echo '<div style="font-size:12px;color:#555">'.esc_html($co_phone).'</div>'; ?>
        </div>
    </div>
    <div class="wo-meta-grid">
        <?php
        $meta = [
            'Staatus'       => $status_labels[$wo->status ?? ''] ?? $wo->status,
            'Prioriteet'    => $priority_labels[$wo->priority ?? ''] ?? $wo->priority,
            'Klient'        => $wo->client_name,
            'Telefon'       => $wo->client_phone,
            'Aadress'       => $wo->client_address,
            'E-post'        => $wo->client_email,
            'Seade'         => trim(($wo->device_name ?? '') . ($wo->device_model ? ' ('.$wo->device_model.')' : '')),
            'Seerianumber'  => $wo->serial_number,
            'Töötaja'       => $wo->worker_name,
            'Kuupäev'       => $wo->scheduled_date ? date('d.m.Y', strtotime($wo->scheduled_date)) : '',
        ];
        foreach ($meta as $label => $val) :
            if (!$val) continue;
        ?>
        <div class="wo-meta-item">
            <span class="wo-meta-label"><?php echo esc_html($label); ?></span>
            <span><?php echo esc_html($val); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($wo->description || $wo->notes) : ?>
    <div style="margin-bottom:16px">
        <?php if ($wo->description) : ?>
        <div style="margin-bottom:8px"><strong>Kirjeldus:</strong><br><?php echo nl2br(esc_html($wo->description)); ?></div>
        <?php endif; ?>
        <?php if ($wo->notes) : ?>
        <div><strong>Märkused:</strong><br><?php echo nl2br(esc_html($wo->notes)); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($used_items)) : ?>
    <div style="font-size:15px;font-weight:700;margin-bottom:8px">Kasutatud materjalid</div>
    <table>
        <thead><tr><th>Nimetus</th><th>Kogus</th><th>Ühiku hind</th><th>Summa</th></tr></thead>
        <tbody>
        <?php $mat_total = 0; foreach ($used_items as $item) :
            $mat_total += (float)$item->line_total; ?>
        <tr>
            <td><?php echo esc_html($item->name); ?></td>
            <td><?php echo esc_html($item->quantity . ' ' . $item->unit); ?></td>
            <td><?php echo number_format($item->unit_price, 2, ',', '.'); ?> €</td>
            <td><?php echo number_format($item->line_total, 2, ',', '.'); ?> €</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="total-row"><td colspan="3">Materjalid kokku</td><td><?php echo number_format($mat_total, 2, ',', '.'); ?> €</td></tr></tfoot>
    </table>
    <?php endif; ?>
    <div style="margin-top:24px;text-align:right">
        <button onclick="window.print()" style="padding:8px 20px;background:#1e293b;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Prindi / Salvesta PDF</button>
    </div>
    <script>window.onload=function(){window.print();};</script>
    <?php
    return;
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🔨 Töökäsud <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Töökäsk lisatud!','updated'=>'Töökäsk uuendatud!','deleted'=>'Töökäsk kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action !== 'edit' && $action !== 'add' && $action !== 'view' && $action !== 'print' && !empty($worker_time_stats)) : ?>
    <div style="margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:10px">Töötajate tööaeg (kuu)</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($worker_time_stats as $ws) : ?>
        <div class="crm-stat-card" style="flex:1;min-width:160px;max-width:220px;padding:14px 16px">
            <div style="font-size:13px;font-weight:600;color:#1e293b;margin-bottom:6px"><?php echo esc_html($ws->name); ?></div>
            <div style="display:flex;gap:16px;font-size:12px;color:#6b7280">
                <span>Kuu: <strong style="color:#1e293b"><?php echo number_format((float)$ws->kuu_hours, 1); ?> h</strong></span>
                <span>Nädal: <strong style="color:#1e293b"><?php echo number_format((float)$ws->nadal_hours, 1); ?> h</strong></span>
            </div>
            <div style="font-size:11px;color:#9ca3af;margin-top:4px"><?php echo intval($ws->active_wo); ?> töökäsku · <?php echo intval($ws->active_wo); ?> aktiivset</div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda töökäsku':'Lisa uus töökäsk'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_workorder'); ?>
            <input type="hidden" name="action" value="vesho_save_workorder">
            <?php if ($edit) : ?><input type="hidden" name="workorder_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Pealkiri *</label>
                    <input class="crm-form-input" type="text" name="title" value="<?php echo esc_attr($edit->title??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Klient *</label>
                    <select class="crm-form-select" name="client_id" required>
                        <option value="">— Vali klient —</option>
                        <?php foreach ($all_clients as $c) : ?>
                            <option value="<?php echo $c->id; ?>" <?php selected($edit->client_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Seade</label>
                    <select class="crm-form-select" name="device_id">
                        <option value="">— Vali seade —</option>
                        <?php foreach ($all_devices as $d) : ?>
                            <option value="<?php echo $d->id; ?>" <?php selected($edit->device_id??0,$d->id); ?>><?php echo esc_html($d->client_name.' – '.$d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Töötaja</label>
                    <select class="crm-form-select" name="worker_id">
                        <option value="">— Määra töötaja —</option>
                        <?php foreach ($all_workers as $w) : ?>
                            <option value="<?php echo $w->id; ?>" <?php selected($edit->worker_id??0,$w->id); ?>><?php echo esc_html($w->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Staatus</label>
                    <select class="crm-form-select" name="status">
                        <?php foreach ($statuses as $v=>$l) : ?>
                            <option value="<?php echo $v; ?>" <?php selected($edit->status??'open',$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Prioriteet</label>
                    <select class="crm-form-select" name="priority">
                        <?php foreach ($priorities as $v=>$l) : ?>
                            <option value="<?php echo $v; ?>" <?php selected($edit->priority??'normal',$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Töö tüüp</label>
                    <select class="crm-form-select" name="work_type">
                        <option value="">— Vali töö tüüp —</option>
                        <option value="maintenance" <?php selected($edit->work_type??'','maintenance'); ?>>🔧 Hooldus</option>
                        <option value="installation" <?php selected($edit->work_type??'','installation'); ?>>🏗️ Paigaldus</option>
                        <option value="warranty" <?php selected($edit->work_type??'','warranty'); ?>>🛡️ Garantii</option>
                        <option value="other" <?php selected($edit->work_type??'','other'); ?>>📋 Muu</option>
                    </select>
                    <small style="color:#6b8599;font-size:12px">Määrab automaatse arve hinna hinnakirja järgi</small>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Planeeritud kuupäev</label>
                    <input class="crm-form-input" type="date" name="scheduled_date" value="<?php echo esc_attr($edit->scheduled_date??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Hind (€)</label>
                    <input class="crm-form-input" type="number" step="0.01" name="price" value="<?php echo esc_attr($edit->price??''); ?>">
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
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <?php if ($edit) : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=print&workorder_id='.$edit->id); ?>"
                   class="crm-btn crm-btn-outline" target="_blank">📄 PDF / Prindi</a>
                <?php endif; ?>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        <?php if ($edit) :
            $photos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d ORDER BY created_at ASC", $edit->id
            ));
        ?>
        <div style="padding:0 20px 20px">
            <div class="crm-form-label" style="margin-bottom:10px">📷 Fotod <?php if ($photos): ?><span style="color:#6b8599">(<?php echo count($photos); ?>)</span><?php endif; ?></div>
            <?php if ($photos) : ?>
            <?php
            $pt_labels = ['before'=>'🔴 Enne','after'=>'🟢 Pärast','other'=>'📷 Muu'];
            $pt_groups = ['before'=>[],'after'=>[],'other'=>[]];
            foreach ($photos as $p) { $pt_groups[$p->photo_type ?? 'other'][] = $p; }
            foreach ($pt_groups as $pt => $group) : if (empty($group)) continue; ?>
            <div style="margin-bottom:12px">
                <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;margin-bottom:6px"><?php echo $pt_labels[$pt] ?? $pt; ?></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <?php foreach ($group as $p) : ?>
                    <div style="position:relative">
                        <a href="<?php echo esc_url($p->filename); ?>" target="_blank">
                            <img src="<?php echo esc_url($p->filename); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_workorder_photo&photo_id='.$p->id.'&workorder_id='.$edit->id), 'vesho_delete_photo_'.$p->id); ?>"
                           style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;background:#ef4444;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;text-decoration:none;font-weight:700"
                           onclick="return confirm('Kustuta foto?')" title="Kustuta">✕</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <?php wp_nonce_field('vesho_upload_workorder_photo'); ?>
                <input type="hidden" name="action" value="vesho_upload_workorder_photo">
                <input type="hidden" name="workorder_id" value="<?php echo $edit->id; ?>">
                <select name="photo_type" style="font-size:12px;padding:4px 8px;border:1px solid #cbd5e1;border-radius:6px">
                    <option value="other">📷 Muu</option>
                    <option value="before">🔴 Enne</option>
                    <option value="after">🟢 Pärast</option>
                </select>
                <input type="file" name="workorder_photos[]" accept="image/*" multiple style="font-size:12px">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">📎 Lae üles</button>
            </form>
        </div>
        <?php endif; ?>
        </div>
    </div>
<script>
(function(){
    var workerSel = document.querySelector('select[name="worker_id"]');
    var statusSel = document.querySelector('select[name="status"]');
    if (workerSel && statusSel) {
        workerSel.addEventListener('change', function() {
            if (workerSel.value && statusSel.value === 'open') {
                statusSel.value = 'assigned';
            }
        });
    }
})();
</script>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa töökäsk</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-workorders">
                <select class="crm-form-select" name="status" style="max-width:160px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik staatused</option>
                    <?php foreach ($statuses as $v=>$l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($filter_st,$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi pealkirja, klienti..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($workorders)) : ?>
            <div class="crm-empty">Töökäske ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Pealkiri</th><th>Klient</th><th>Töötaja</th><th>Kuupäev</th><th>Töö tüüp</th><th>Prioriteet</th><th>Staatus</th><th>Hind</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($workorders as $wo) : ?>
            <tr id="wo-row-<?php echo $wo->id; ?>">
                <td style="color:#6b8599">#<?php echo $wo->id; ?></td>
                <td><strong><?php echo esc_html($wo->title); ?></strong></td>
                <td><?php echo $wo->client_id ? '<a href="'.admin_url('admin.php?page=vesho-crm-clients&action=view&client_id='.$wo->client_id).'">'.esc_html($wo->client_name).'</a>' : '–'; ?></td>
                <td><?php echo esc_html($wo->worker_name?:'–'); ?></td>
                <td><?php echo vesho_crm_format_date($wo->scheduled_date); ?></td>
                <td><?php
                    $wt_labels = ['maintenance'=>'🔧 Hooldus','installation'=>'🏗️ Paigaldus','warranty'=>'🛡️ Garantii','other'=>'📋 Muu'];
                    echo isset($wt_labels[$wo->work_type??'']) ? esc_html($wt_labels[$wo->work_type]) : '–';
                ?></td>
                <td><?php
                    $pcls = ['low'=>'badge-gray','normal'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-danger'];
                    echo '<span class="crm-badge '.esc_attr($pcls[$wo->priority]??'badge-gray').'">'.esc_html($priorities[$wo->priority]??$wo->priority).'</span>';
                ?></td>
                <td>
                    <select class="wo-status-sel" data-id="<?php echo $wo->id; ?>" onchange="updateWoStatus(this)"
                      style="padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700;border:1.5px solid #e2e8f0;cursor:pointer;background:#fff">
                      <?php foreach ($statuses as $v=>$l): ?>
                        <option value="<?php echo $v; ?>" <?php selected($wo->status,$v); ?>><?php echo $l; ?></option>
                      <?php endforeach; ?>
                    </select>
                </td>
                <td><?php echo $wo->price!==null ? vesho_crm_format_money($wo->price) : '–'; ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=edit&workorder_id='.$wo->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=print&workorder_id='.$wo->id); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="PDF / Prindi" target="_blank">📄</a>
                    <button class="crm-btn crm-btn-icon crm-btn-sm" title="Fotod" onclick="toggleWoPhotos(<?php echo $wo->id; ?>,this)" style="position:relative">
                        📷<?php if ($wo->photo_count > 0) : ?><sup style="font-size:9px;font-weight:700"><?php echo (int)$wo->photo_count; ?></sup><?php endif; ?>
                    </button>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_workorder&workorder_id='.$wo->id),'vesho_delete_workorder'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta töökäsk?')">🗑️</a>
                </td>
            </tr>
            <tr id="wo-photos-<?php echo $wo->id; ?>" style="display:none;background:#f8fafc">
                <td colspan="10" id="wo-photos-cell-<?php echo $wo->id; ?>" style="padding:12px 20px"></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script>
// Inline workorder status change
function updateWoStatus(sel) {
    var id     = sel.dataset.id;
    var status = sel.value;
    var nonce  = '<?php echo wp_create_nonce('vesho_admin_nonce'); ?>';
    sel.disabled = true;
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=vesho_update_workorder_status&workorder_id=' + id + '&status=' + encodeURIComponent(status) + '&_ajax_nonce=' + nonce
    }).then(function(r){ return r.json(); }).then(function(d){
        sel.disabled = false;
        if (!d.success) { alert('Viga salvestamisel'); sel.value = sel.dataset.prev || sel.value; }
    });
}
document.querySelectorAll('.wo-status-sel').forEach(function(s){
    s.dataset.prev = s.value;
    s.addEventListener('mousedown', function(){ this.dataset.prev = this.value; });
});

// Live search (debounced auto-submit)
(function(){
    var inp = document.querySelector('input[name="s"].crm-search');
    if (!inp) return;
    var timer;
    inp.addEventListener('input', function(){
        clearTimeout(timer);
        timer = setTimeout(function(){ inp.closest('form').submit(); }, 350);
    });
})();

// Photo expand (v2.9.54)
var woPhotoNonce = '<?php echo wp_create_nonce('vesho_admin_nonce'); ?>';
var woPhotoAjax  = '<?php echo admin_url('admin-ajax.php'); ?>';
function toggleWoPhotos(id, btn) {
    var row  = document.getElementById('wo-photos-'+id);
    var cell = document.getElementById('wo-photos-cell-'+id);
    if (row.style.display !== 'none') { row.style.display='none'; return; }
    cell.innerHTML = '<span style="color:#94a3b8;font-size:13px">Laadin fotosid...</span>';
    row.style.display = '';
    if (cell.dataset.loaded) return;
    fetch(woPhotoAjax, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_get_workorder_photos&workorder_id='+id+'&_ajax_nonce='+woPhotoNonce
    }).then(function(r){return r.json();}).then(function(d){
        cell.dataset.loaded = '1';
        if (!d.success || !d.data || !d.data.photos || !d.data.photos.length) {
            cell.innerHTML = '<span style="color:#94a3b8;font-size:13px">Fotosid ei leitud.</span>'; return;
        }
        var photos = d.data.photos;
        var groups = {before:[],after:[],other:[]};
        photos.forEach(function(p){ var g=p.photo_type; groups[g]?groups[g].push(p):groups.other.push(p); });
        var labels = {before:'🔴 Enne tööd',after:'🟢 Pärast tööd',other:'📷 Muu'};
        var html = '<div style="display:flex;gap:20px;flex-wrap:wrap">';
        ['before','after','other'].forEach(function(t){
            if (!groups[t].length) return;
            html += '<div><div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px">'+labels[t]+'</div>';
            html += '<div style="display:flex;gap:8px;flex-wrap:wrap">';
            groups[t].forEach(function(p){
                html += '<a href="'+p.filename+'" target="_blank"><img src="'+p.filename+'" style="width:72px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0" loading="lazy"></a>';
            });
            html += '</div></div>';
        });
        html += '</div>';
        cell.innerHTML = html;
    });
}
</script>
