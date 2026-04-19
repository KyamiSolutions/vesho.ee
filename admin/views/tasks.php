<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action  = sanitize_text_field( $_GET['action'] ?? '' );
$task_id = absint( $_GET['task_id'] ?? 0 );
$filter_st = sanitize_text_field( $_GET['status'] ?? '' );
$filter_w  = absint( $_GET['worker_id'] ?? 0 );
$search    = sanitize_text_field( $_GET['s'] ?? '' );

$all_workers = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC" );

$statuses   = ['open' => 'Avatud', 'in_progress' => 'Töös', 'done' => 'Valmis', 'cancelled' => 'Tühistatud'];
$priorities = ['low' => 'Madal', 'normal' => 'Tavaline', 'high' => 'Kõrge', 'urgent' => 'Kiire'];
$pcls       = ['low' => 'badge-gray', 'normal' => 'badge-info', 'high' => 'badge-warning', 'urgent' => 'badge-danger'];
$scls       = ['open' => 'badge-blue', 'in_progress' => 'badge-warning', 'done' => 'badge-success', 'cancelled' => 'badge-gray'];

// ── Edit / create form ─────────────────────────────────────────────────────────
$edit = null;
if ( $action === 'edit' && $task_id ) {
    $edit = $wpdb->get_row( $wpdb->prepare(
        "SELECT t.*, w.name as worker_name FROM {$wpdb->prefix}vesho_tasks t
         LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id=t.assigned_worker_id
         WHERE t.id=%d LIMIT 1", $task_id
    ) );
}

// ── List query ─────────────────────────────────────────────────────────────────
$where = '1=1';
if ( $filter_st )  { $where .= $wpdb->prepare( ' AND t.status=%s', $filter_st ); }
if ( $filter_w )   { $where .= $wpdb->prepare( ' AND t.assigned_worker_id=%d', $filter_w ); }
if ( $search )     { $where .= $wpdb->prepare( ' AND t.title LIKE %s', '%' . $wpdb->esc_like($search) . '%' ); }

$tasks = $wpdb->get_results(
    "SELECT t.*, w.name as worker_name
     FROM {$wpdb->prefix}vesho_tasks t
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id=t.assigned_worker_id
     WHERE $where ORDER BY
        FIELD(t.status,'open','in_progress','done','cancelled'),
        FIELD(t.priority,'urgent','high','normal','low'),
        t.due_date IS NULL ASC, t.due_date ASC
     LIMIT 500"
);
$total      = count($tasks);
$open_cnt   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_tasks WHERE status NOT IN ('done','cancelled')" );
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">✅ Ülesanded <span class="crm-count">(<?php echo $total; ?>)</span>
        <?php if ($open_cnt > 0) : ?><span class="crm-badge badge-warning" style="font-size:13px;vertical-align:middle"><?php echo $open_cnt; ?> aktiivsed</span><?php endif; ?>
    </h1>

    <?php if ( isset($_GET['msg']) ) :
        $msgs = ['saved' => 'Salvestatud!', 'deleted' => 'Kustutatud!', 'done' => 'Ülesanne täidetud!'];
        echo '<div class="crm-alert crm-alert-success">' . esc_html($msgs[$_GET['msg']] ?? 'OK!') . '</div>';
    endif; ?>

    <?php if ( $action === 'edit' ) : ?>
    <!-- ── Create / Edit form ─────────────────────────────────────────────── -->
    <div class="crm-card" style="margin-bottom:24px">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $edit ? 'Muuda ülesannet #' . $edit->id : 'Uus ülesanne'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-tasks'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_task'); ?>
            <input type="hidden" name="action" value="vesho_save_task">
            <?php if ($edit) : ?><input type="hidden" name="task_id" value="<?php echo $edit->id; ?>"><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div style="grid-column:1/-1">
                    <label class="crm-form-label">Pealkiri *</label>
                    <input class="crm-form-input" type="text" name="title" value="<?php echo esc_attr($edit->title ?? ''); ?>" required placeholder="Ülesande pealkiri...">
                </div>
                <div style="grid-column:1/-1">
                    <label class="crm-form-label">Kirjeldus</label>
                    <textarea class="crm-form-textarea" name="description" rows="3" placeholder="Täpsem kirjeldus..."><?php echo esc_textarea($edit->description ?? ''); ?></textarea>
                </div>
                <div>
                    <label class="crm-form-label">Prioriteet</label>
                    <select class="crm-form-select" name="priority">
                        <?php foreach ($priorities as $v => $l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($edit->priority ?? 'normal', $v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="crm-form-label">Staatus</label>
                    <select class="crm-form-select" name="status">
                        <?php foreach ($statuses as $v => $l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($edit->status ?? 'open', $v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="crm-form-label">Vastutav töötaja</label>
                    <select class="crm-form-select" name="assigned_worker_id">
                        <option value="0">— Määramata —</option>
                        <?php foreach ($all_workers as $w) : ?>
                        <option value="<?php echo $w->id; ?>" <?php selected((int)($edit->assigned_worker_id ?? 0), (int)$w->id); ?>><?php echo esc_html($w->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="crm-form-label">Tähtaeg</label>
                    <input class="crm-form-input" type="date" name="due_date" value="<?php echo esc_attr($edit->due_date ?? ''); ?>">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-tasks'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>

    <?php else : ?>
    <!-- ── Toolbar ────────────────────────────────────────────────────────── -->
    <div class="crm-card">
        <div class="crm-toolbar" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap">
                <input type="hidden" name="page" value="vesho-crm-tasks">
                <select class="crm-form-select" name="status" style="max-width:140px;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
                    <option value="">Kõik staatused</option>
                    <?php foreach ($statuses as $v => $l) : ?>
                    <option value="<?php echo $v; ?>" <?php selected($filter_st, $v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="crm-form-select" name="worker_id" style="max-width:160px;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
                    <option value="0">Kõik töötajad</option>
                    <?php foreach ($all_workers as $w) : ?>
                    <option value="<?php echo $w->id; ?>" <?php selected($filter_w, (int)$w->id); ?>><?php echo esc_html($w->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi pealkirja..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-tasks&action=edit'); ?>" class="crm-btn crm-btn-primary crm-btn-sm">+ Uus ülesanne</a>
        </div>

        <?php if ( empty($tasks) ) : ?>
        <div class="crm-empty">Ülesandeid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Pealkiri</th><th>Töötaja</th><th>Prioriteet</th><th>Tähtaeg</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($tasks as $t) :
                $is_overdue = $t->due_date && $t->status !== 'done' && $t->status !== 'cancelled' && strtotime($t->due_date) < strtotime('today');
                $row_style  = '';
                if ($t->status === 'done')      $row_style = 'opacity:.6';
                elseif ($is_overdue)             $row_style = 'background:#fff1f2';
                elseif ($t->status === 'open')   $row_style = 'background:#fef9e7';
            ?>
            <tr style="<?php echo $row_style; ?>">
                <td style="color:#6b8599">#<?php echo $t->id; ?></td>
                <td>
                    <div style="font-weight:600"><?php echo esc_html($t->title); ?></div>
                    <?php if (!empty($t->description)) : ?>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($t->description); ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748b"><?php echo $t->worker_name ? '👷 ' . esc_html($t->worker_name) : '—'; ?></td>
                <td><span class="crm-badge <?php echo esc_attr($pcls[$t->priority] ?? 'badge-gray'); ?>"><?php echo esc_html($priorities[$t->priority] ?? $t->priority); ?></span></td>
                <td>
                    <?php if ($t->due_date) : ?>
                    <span style="<?php echo $is_overdue ? 'color:#dc2626;font-weight:600' : ''; ?>">
                        <?php echo $is_overdue ? '⚠️ ' : ''; ?><?php echo vesho_crm_format_date($t->due_date, 'd.m.Y'); ?>
                    </span>
                    <?php else : echo '—'; endif; ?>
                </td>
                <td><span class="crm-badge <?php echo esc_attr($scls[$t->status] ?? 'badge-gray'); ?>"><?php echo esc_html($statuses[$t->status] ?? $t->status); ?></span></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-tasks&action=edit&task_id=' . $t->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <?php if ($t->status !== 'done' && $t->status !== 'cancelled') : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_complete_task&task_id=' . $t->id), 'vesho_complete_task_' . $t->id); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Märgi tehtuks" onclick="return confirm('Märid ülesande tehtuks?')">✅</a>
                    <?php endif; ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_task&task_id=' . $t->id), 'vesho_delete_task_' . $t->id); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm crm-btn-danger" title="Kustuta" onclick="return confirm('Kustutad ülesande?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
