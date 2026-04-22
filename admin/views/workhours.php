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

// Summary stats (v2.9.54)
$week_start_dt  = date('Y-m-d', strtotime('monday this week'));
$month_start_dt = date('Y-m-01');
$wh_week_total  = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(hours),0) FROM {$wpdb->prefix}vesho_work_hours WHERE date >= %s", $week_start_dt));
$wh_month_total = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(hours),0) FROM {$wpdb->prefix}vesho_work_hours WHERE date >= %s", $month_start_dt));
$wh_active_now  = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_work_hours WHERE start_time IS NOT NULL AND (end_time IS NULL OR end_time='')");
$clockin_sessions = $wpdb->get_results(
    "SELECT wh.*, w.name as worker_name, wo.title as workorder_title
     FROM {$wpdb->prefix}vesho_work_hours wh
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON wh.worker_id=w.id
     LEFT JOIN {$wpdb->prefix}vesho_workorders wo ON wh.workorder_id=wo.id
     WHERE wh.start_time IS NOT NULL
     ORDER BY CASE WHEN wh.end_time IS NULL OR wh.end_time='' THEN 0 ELSE 1 END, wh.start_time DESC
     LIMIT 100"
);
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
    <!-- Summary cards (v2.9.54) -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
        <div class="crm-stat-card" style="border-top:3px solid #00b4c8">
            <div class="crm-stat-num" style="color:#00b4c8"><?php echo number_format($wh_week_total,1); ?> h</div>
            <div class="crm-stat-label">Selle nädala tunnid</div>
        </div>
        <div class="crm-stat-card" style="border-top:3px solid #6366f1">
            <div class="crm-stat-num" style="color:#6366f1"><?php echo number_format($wh_month_total,1); ?> h</div>
            <div class="crm-stat-label">Kuu tunnid (<?php echo date('M'); ?>)</div>
        </div>
        <div class="crm-stat-card" style="border-top:3px solid #10b981">
            <div class="crm-stat-num" style="color:#10b981"><?php echo $wh_active_now; ?></div>
            <div class="crm-stat-label">Praegu töös</div>
            <div style="font-size:11px;color:#9ca3af;margin-top:2px">aktiivne kellaaeg</div>
        </div>
    </div>

    <div class="crm-card">
        <!-- Tab buttons -->
        <div style="display:flex;gap:0;border-bottom:1px solid #e2e8f0">
            <button class="wh-tab-btn" data-tab="manual"
                    style="border:none;border-bottom:2px solid #00b4c8;font-family:var(--font-main);font-size:13px;padding:12px 20px;font-weight:600;color:#00b4c8;background:none;cursor:pointer">
                📋 Manuaalsed tunnid
            </button>
            <button class="wh-tab-btn" data-tab="clockin"
                    style="border:none;border-bottom:2px solid transparent;font-family:var(--font-main);font-size:13px;padding:12px 20px;font-weight:600;color:#64748b;background:none;cursor:pointer">
                🕐 Kellaaeg sisse/välja<?php if ($wh_active_now > 0): ?> <span style="background:#10b981;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:3px"><?php echo $wh_active_now; ?></span><?php endif; ?>
            </button>
        </div>

        <!-- Tab 1: Manual hours -->
        <div id="wh-tab-manual">
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
        </div><!-- #wh-tab-manual -->

        <!-- Tab 2: Clock-in sessions -->
        <div id="wh-tab-clockin" style="display:none">
            <?php if (empty($clockin_sessions)) : ?>
                <div class="crm-empty">Kellaajasisestusi ei leitud.</div>
            <?php else : ?>
            <table class="crm-table">
                <thead><tr>
                    <th>Töötaja</th><th>Töökäsk</th><th>Kuupäev</th><th>Algas</th><th>Lõppes</th><th>Kestus</th><th>Staatus</th>
                </tr></thead>
                <tbody>
                <?php foreach ($clockin_sessions as $cs) :
                    $cs_active = ($cs->start_time && (!$cs->end_time || $cs->end_time === ''));
                    $cs_dur = '–';
                    if ($cs->start_time) {
                        $cs_end  = $cs->end_time ?: date('Y-m-d H:i:s');
                        $cs_diff = strtotime($cs_end) - strtotime($cs->start_time);
                        if ($cs_diff > 0) {
                            $cs_dur = floor($cs_diff/3600).'h '.floor(($cs_diff%3600)/60).'min';
                        }
                    }
                ?>
                <tr <?php if ($cs_active) echo 'style="background:#f0fdf4"'; ?>>
                    <td><strong><?php echo esc_html($cs->worker_name?:'–'); ?></strong></td>
                    <td><?php echo $cs->workorder_title ? esc_html('#'.$cs->workorder_id.' '.$cs->workorder_title) : '–'; ?></td>
                    <td><?php echo vesho_crm_format_date($cs->date); ?></td>
                    <td><?php echo $cs->start_time ? date('H:i', strtotime($cs->start_time)) : '–'; ?></td>
                    <td><?php echo (!$cs->end_time || $cs->end_time==='') ? '–' : date('H:i', strtotime($cs->end_time)); ?></td>
                    <td><?php echo $cs_dur; ?></td>
                    <td>
                        <?php if ($cs_active) :
                            echo '<span class="crm-badge badge-success" style="font-size:10px">🟢 Töös</span>';
                        else :
                            echo '<span class="crm-badge badge-gray" style="font-size:10px">✓ Lõpetatud</span>';
                        endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div><!-- #wh-tab-clockin -->
    </div>
    <?php endif; ?>
</div>
<script>
(function(){
    var btns = document.querySelectorAll('.wh-tab-btn');
    btns.forEach(function(btn){
        btn.addEventListener('click', function(){
            btns.forEach(function(b){
                b.style.borderBottomColor = 'transparent';
                b.style.color = '#64748b';
            });
            btn.style.borderBottomColor = '#00b4c8';
            btn.style.color = '#00b4c8';
            document.getElementById('wh-tab-manual').style.display   = 'none';
            document.getElementById('wh-tab-clockin').style.display  = 'none';
            document.getElementById('wh-tab-' + btn.dataset.tab).style.display = 'block';
        });
    });
})();
</script>
