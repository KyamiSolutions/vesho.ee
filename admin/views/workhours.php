<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$worker_id_filter = isset($_GET['worker_id']) ? absint($_GET['worker_id']) : 0;
$search           = isset($_GET['s'])         ? sanitize_text_field($_GET['s']) : '';
$month_filter     = isset($_GET['month'])     ? sanitize_text_field($_GET['month']) : '';

$action       = isset($_GET['action'])      ? sanitize_text_field($_GET['action']) : '';
$workhour_id  = isset($_GET['workhour_id']) ? absint($_GET['workhour_id'])          : 0;

$all_workers  = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");
$all_workorders = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}vesho_workorders ORDER BY created_at DESC LIMIT 100");

$edit = null;
if ( $action === 'edit' && $workhour_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_work_hours WHERE id=%d", $workhour_id));
}

$where = '1=1';
if ($worker_id_filter) { $where .= $wpdb->prepare(' AND wh.worker_id=%d', $worker_id_filter); }
if ($month_filter)     { $where .= $wpdb->prepare(" AND DATE_FORMAT(wh.date,'%%Y-%%m')=%s", $month_filter); }

$workhours = $wpdb->get_results(
    "SELECT wh.*, w.name as worker_name, wo.title as workorder_title
     FROM {$wpdb->prefix}vesho_work_hours wh
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON wh.worker_id=w.id
     LEFT JOIN {$wpdb->prefix}vesho_workorders wo ON wh.workorder_id=wo.id
     WHERE $where ORDER BY wh.date DESC LIMIT 300"
);
$total = count($workhours);
$total_hours = array_sum(array_column($workhours, 'hours'));
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">⏱️ Töötunnid <span class="crm-count">(<?php echo $total; ?> kirjet, <?php echo number_format($total_hours,1); ?> h)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Töötunnid lisatud!','updated'=>'Töötunnid uuendatud!','deleted'=>'Töötunnid kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda töötunde':'Lisa töötunnid'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workhours'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_workhours'); ?>
            <input type="hidden" name="action" value="vesho_save_workhours">
            <?php if ($edit) : ?><input type="hidden" name="workhour_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group">
                    <label class="crm-form-label">Töötaja *</label>
                    <select class="crm-form-select" name="worker_id" required>
                        <option value="">— Vali töötaja —</option>
                        <?php foreach ($all_workers as $w) : ?>
                            <option value="<?php echo $w->id; ?>" <?php selected($edit->worker_id??$worker_id_filter,$w->id); ?>><?php echo esc_html($w->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Töökäsk</label>
                    <select class="crm-form-select" name="workorder_id">
                        <option value="">— Vali töökäsk —</option>
                        <?php foreach ($all_workorders as $wo) : ?>
                            <option value="<?php echo $wo->id; ?>" <?php selected($edit->workorder_id??0,$wo->id); ?>>#<?php echo $wo->id; ?> – <?php echo esc_html($wo->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Kuupäev *</label>
                    <input class="crm-form-input" type="date" name="work_date" value="<?php echo esc_attr($edit->date??date('Y-m-d')); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Tunnid *</label>
                    <input class="crm-form-input" type="number" step="0.25" min="0.25" max="24" name="hours" value="<?php echo esc_attr($edit->hours??''); ?>" required>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus</label>
                    <textarea class="crm-form-textarea" name="description"><?php echo esc_textarea($edit->description??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-workhours'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workhours&action=add'.($worker_id_filter?'&worker_id='.$worker_id_filter:'')); ?>" class="crm-btn crm-btn-primary">+ Lisa tunnid</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-workhours">
                <select class="crm-form-select" name="worker_id" style="max-width:160px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik töötajad</option>
                    <?php foreach ($all_workers as $w) : ?>
                        <option value="<?php echo $w->id; ?>" <?php selected($worker_id_filter,$w->id); ?>><?php echo esc_html($w->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-form-input" type="month" name="month" value="<?php echo esc_attr($month_filter); ?>" style="max-width:140px;padding:7px 10px;font-size:13px">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Filtreeri</button>
            </form>
        </div>
        <?php if (empty($workhours)) : ?>
            <div class="crm-empty">Töötunde ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Töötaja</th><th>Töökäsk</th><th>Kuupäev</th><th>Algas</th><th>Lõppes</th><th>Tunnid</th><th>Kirjeldus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($workhours as $wh) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $wh->id; ?></td>
                <td><strong><?php echo esc_html($wh->worker_name?:'–'); ?></strong></td>
                <td><?php echo $wh->workorder_title ? esc_html('#'.$wh->workorder_id.' '.$wh->workorder_title) : '–'; ?></td>
                <td><?php echo vesho_crm_format_date($wh->date); ?></td>
                <td><?php echo $wh->start_time ? date('H:i', strtotime($wh->start_time)) : '–'; ?></td>
                <td>
                    <?php if ($wh->start_time && !$wh->end_time) :
                        echo '<span class="crm-badge badge-success" style="font-size:10px">🟢 Töös</span>';
                    elseif ($wh->end_time) :
                        echo date('H:i', strtotime($wh->end_time));
                    else :
                        echo '–';
                    endif; ?>
                </td>
                <td><strong><?php echo number_format($wh->hours,1); ?> h</strong></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($wh->description?:'–'); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workhours&action=edit&workhour_id='.$wh->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_workhours&workhour_id='.$wh->id),'vesho_delete_workhours'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta kirje?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
