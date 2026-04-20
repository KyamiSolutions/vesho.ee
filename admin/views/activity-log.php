<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$search      = isset($_GET['s'])           ? sanitize_text_field($_GET['s'])           : '';
$date_from   = isset($_GET['date_from'])   ? sanitize_text_field($_GET['date_from'])   : '';
$date_to     = isset($_GET['date_to'])     ? sanitize_text_field($_GET['date_to'])     : '';
$action_type = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$filter_user = isset($_GET['user_id'])     ? absint($_GET['user_id'])                  : 0;
$paged       = max(1, absint($_GET['paged'] ?? 1));
$limit       = 50;
$offset      = ($paged - 1) * $limit;

$table  = $wpdb->prefix . 'vesho_activity_log';

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($date_from) { $where[] = 'DATE(created_at) >= %s'; $params[] = $date_from; }
if ($date_to)   { $where[] = 'DATE(created_at) <= %s'; $params[] = $date_to; }
if ($action_type) { $where[] = 'action = %s'; $params[] = $action_type; }
if ($filter_user) { $where[] = 'user_id = %d'; $params[] = $filter_user; }
if ($search) {
    $where[]  = 'description LIKE %s';
    $params[] = '%' . $wpdb->esc_like($search) . '%';
}

$where_sql = implode(' AND ', $where);

$total = $params
    ? (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", ...$params) )
    : (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_sql");

$logs = $params
    ? $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d", ...array_merge($params, [$limit, $offset])) )
    : $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset) );

$pages = $total > 0 ? ceil($total / $limit) : 1;

// ── CSV Export ────────────────────────────────────────────────────────────────
if (!empty($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tegevuslogi-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kuupäev', 'Kasutaja', 'Tegevus', 'Kirjeldus'], ';');
    // Fetch all rows for export (no pagination)
    $all_logs = $params
        ? $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC", ...$params) )
        : $wpdb->get_results("SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC");
    foreach ($all_logs as $log) {
        $user = get_userdata($log->user_id);
        fputcsv($out, [
            $log->created_at,
            $user ? $user->display_name : ($log->user_name ?: 'Süsteem'),
            $log->action,
            $log->description,
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Populate filter dropdowns ─────────────────────────────────────────────────
$distinct_actions = $wpdb->get_col("SELECT DISTINCT action FROM $table ORDER BY action");
$wp_users         = get_users(['role__in' => ['administrator','editor','author','vesho_worker'], 'orderby' => 'display_name', 'fields' => ['ID','display_name']]);

$action_labels = [
    // Clients
    'client_created'          => ['Klient loodud',              '#dbeafe'],
    'client_updated'          => ['Klient muudetud',            '#e0f2fe'],
    'client_deleted'          => ['Klient kustutatud',          '#fee2e2'],
    'client_saved'            => ['Klient salvestatud',         '#dbeafe'], // legacy
    // Devices
    'device_created'          => ['Seade loodud',               '#dbeafe'],
    'device_updated'          => ['Seade muudetud',             '#e0f2fe'],
    'device_deleted'          => ['Seade kustutatud',           '#fee2e2'],
    // Maintenances
    'maintenance_created'     => ['Hooldus loodud',             '#dbeafe'],
    'maintenance_updated'     => ['Hooldus muudetud',           '#e0f2fe'],
    'maintenance_deleted'     => ['Hooldus kustutatud',         '#fee2e2'],
    'maintenance_confirmed'   => ['Hooldus kinnitatud',         '#dcfce7'],
    'maintenance_rejected'    => ['Hooldus tagasi lükatud',     '#fee2e2'],
    'maintenance_cancelled'   => ['Hooldus tühistatud',         '#fef9c3'],
    // Invoices
    'invoice_created'         => ['Arve loodud',                '#dbeafe'],
    'invoice_updated'         => ['Arve muudetud',              '#e0f2fe'],
    'invoice_deleted'         => ['Arve kustutatud',            '#fee2e2'],
    'invoice_paid'            => ['Arve makstud',               '#dcfce7'],
    'invoice_status_updated'  => ['Arve staatus muudetud',      '#f0fdf4'],
    'invoice_email_sent'      => ['Arve e-mailiga saadetud',    '#f0f9ff'],
    'invoice_sent'            => ['Arve saadetud',              '#f0fdf4'], // legacy
    // Workers
    'worker_created'          => ['Töötaja loodud',             '#dbeafe'],
    'worker_updated'          => ['Töötaja muudetud',           '#e0f2fe'],
    'worker_deleted'          => ['Töötaja kustutatud',         '#fee2e2'],
    // Work orders
    'workorder_created'       => ['Töökäsk loodud',             '#dbeafe'],
    'workorder_updated'       => ['Töökäsk muudetud',           '#e0f2fe'],
    'workorder_deleted'       => ['Töökäsk kustutatud',         '#fee2e2'],
    'order_completed'         => ['Töökäsk lõpetatud',          '#dcfce7'], // legacy
    // Work hours
    'workhours_saved'         => ['Tööaeg salvestatud',         '#f0fdf4'],
    'workhours_deleted'       => ['Tööaeg kustutatud',          '#fee2e2'],
    // Shop orders
    'order_status_updated'    => ['Tellimuse staatus muudetud', '#e0f2fe'],
    // Access / settings / notices
    'access_sent'             => ['Ligipääs saadetud',          '#f0f9ff'],
    'settings_saved'          => ['Seaded salvestatud',         '#f8fafc'],
    'notice_added'            => ['Teade lisatud',              '#fef9c3'],
    'notice_deleted'          => ['Teade kustutatud',           '#fee2e2'],
    // Auth
    'login'                   => ['Sisselogimine',              '#f8fafc'],
    'login_failed'            => ['Ebaõnnestunud sisselogimine','#fee2e2'],
    'logout'                  => ['Väljalogimine',              '#f8fafc'],
];

// Build current filter query string (without export_csv/paged)
$filter_qs = http_build_query(array_filter([
    'page'        => 'vesho-crm-activity',
    's'           => $search,
    'date_from'   => $date_from,
    'date_to'     => $date_to,
    'action_type' => $action_type,
    'user_id'     => $filter_user ?: '',
]));
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📋 Tegevuslogi <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <div class="crm-card">
        <!-- Filter bar -->
        <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end">
                <input type="hidden" name="page" value="vesho-crm-activity">

                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Kuupäevast</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"
                           style="padding:6px 10px;border:1.5px solid #dde3ea;border-radius:7px;font-size:13px;color:#1a2a38">
                </div>

                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Kuupäevani</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"
                           style="padding:6px 10px;border:1.5px solid #dde3ea;border-radius:7px;font-size:13px;color:#1a2a38">
                </div>

                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Tegevus</label>
                    <select name="action_type" style="padding:6px 10px;border:1.5px solid #dde3ea;border-radius:7px;font-size:13px;color:#1a2a38;background:#fff">
                        <option value="">— Kõik —</option>
                        <?php foreach ($distinct_actions as $act): ?>
                        <option value="<?php echo esc_attr($act); ?>" <?php selected($action_type, $act); ?>>
                            <?php echo esc_html(isset($action_labels[$act]) ? $action_labels[$act][0] : $act); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Kasutaja</label>
                    <select name="user_id" style="padding:6px 10px;border:1.5px solid #dde3ea;border-radius:7px;font-size:13px;color:#1a2a38;background:#fff">
                        <option value="">— Kõik —</option>
                        <?php foreach ($wp_users as $u): ?>
                        <option value="<?php echo esc_attr($u->ID); ?>" <?php selected($filter_user, $u->ID); ?>>
                            <?php echo esc_html($u->display_name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Otsi kirjeldusest</label>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Kirjeldus..."
                           style="padding:6px 10px;border:1.5px solid #dde3ea;border-radius:7px;font-size:13px;color:#1a2a38;min-width:160px">
                </div>

                <div style="display:flex;gap:6px;align-items:flex-end">
                    <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm">Filtreeri</button>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-activity'); ?>"
                       class="crm-btn crm-btn-outline crm-btn-sm">Tühjenda</a>
                    <a href="<?php echo admin_url('admin.php?' . $filter_qs . '&export_csv=1'); ?>"
                       class="crm-btn crm-btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a">⬇ Ekspordi CSV</a>
                </div>
            </form>
        </div>

        <?php if (empty($logs)): ?>
            <div class="crm-empty">Logisid ei leitud.</div>
        <?php else: ?>
        <table class="crm-table">
            <thead>
                <tr>
                    <th style="width:140px">Aeg</th>
                    <th style="width:130px">Kasutaja</th>
                    <th style="width:160px">Toiming</th>
                    <th>Kirjeldus</th>
                    <th style="width:100px">IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $row): ?>
            <?php
                $lbl = isset($action_labels[$row->action]) ? $action_labels[$row->action] : [esc_html($row->action), '#f8fafc'];
            ?>
            <tr>
                <td style="color:#6b8599;font-size:12px;white-space:nowrap"><?php echo esc_html(date('d.m.Y H:i', strtotime($row->created_at))); ?></td>
                <td><strong><?php echo esc_html($row->user_name ?: 'System'); ?></strong></td>
                <td>
                    <span style="background:<?php echo esc_attr($lbl[1]); ?>;padding:2px 8px;border-radius:4px;font-size:12px;white-space:nowrap">
                        <?php echo esc_html($lbl[0]); ?>
                    </span>
                </td>
                <td style="font-size:13px"><?php echo esc_html($row->description); ?></td>
                <td style="color:#94a3b8;font-size:12px"><?php echo esc_html($row->ip_address ?: '—'); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div style="padding:16px 20px;display:flex;gap:6px;align-items:center">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="<?php echo admin_url('admin.php?' . http_build_query(array_filter([
                'page'        => 'vesho-crm-activity',
                's'           => $search,
                'date_from'   => $date_from,
                'date_to'     => $date_to,
                'action_type' => $action_type,
                'user_id'     => $filter_user ?: '',
                'paged'       => $p,
            ]))); ?>"
               class="crm-btn crm-btn-sm <?php echo $p === $paged ? 'crm-btn-primary' : 'crm-btn-outline'; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <span style="margin-left:8px;color:#6b8599;font-size:13px">Kokku: <?php echo $total; ?> kirjet</span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
