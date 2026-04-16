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
    "SELECT wo.*, c.name as client_name, w.name as worker_name, d.name as device_name
     FROM {$wpdb->prefix}vesho_workorders wo
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON wo.client_id=c.id
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON wo.worker_id=w.id
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON wo.device_id=d.id
     WHERE $where ORDER BY wo.created_at DESC LIMIT 200"
);
$total = count($workorders);

$statuses   = ['open'=>'Ootel','assigned'=>'Määratud','in_progress'=>'Töös','completed'=>'Lõpetatud','cancelled'=>'Tühistatud'];
$priorities = ['low'=>'Madal','normal'=>'Tavaline','high'=>'Kõrge','urgent'=>'Kiire'];
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🔨 Töökäsud <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Töökäsk lisatud!','updated'=>'Töökäsk uuendatud!','deleted'=>'Töökäsk kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

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
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        <?php if ($edit) :
            $photos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d ORDER BY created_at ASC", $edit->id
            ));
            if ($photos) : ?>
        <div style="padding:0 20px 20px">
            <div class="crm-form-label" style="margin-bottom:10px">📷 Lisatud fotod (<?php echo count($photos); ?>)</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <?php foreach ($photos as $p) : ?>
                <a href="<?php echo esc_url($p->filename); ?>" target="_blank">
                    <img src="<?php echo esc_url($p->filename); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; endif; ?>
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
            <tr>
                <td style="color:#6b8599">#<?php echo $wo->id; ?></td>
                <td><strong><?php echo esc_html($wo->title); ?></strong></td>
                <td><?php echo esc_html($wo->client_name?:'–'); ?></td>
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
                <td><?php echo vesho_crm_status_badge($wo->status); ?></td>
                <td><?php echo $wo->price!==null ? vesho_crm_format_money($wo->price) : '–'; ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=edit&workorder_id='.$wo->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_workorder&workorder_id='.$wo->id),'vesho_delete_workorder'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta töökäsk?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
