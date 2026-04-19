<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$today       = current_time('Y-m-d');
$month_start = date('Y-m-01');

// ── KPI cards (Guide.jsx spec) ───────────────────────────────────────────────
$total_clients    = Vesho_CRM_Database::count_clients();
$active_workorders_count = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders WHERE status IN ('in_progress','assigned')");
$today_maintenances_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances WHERE scheduled_date=%s", $today));
$unpaid_count     = Vesho_CRM_Database::count_unpaid_invoices();
$unpaid_total     = Vesho_CRM_Database::sum_unpaid_invoices();
$month_revenue    = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices WHERE status='paid' AND invoice_date>=%s", $month_start));

// ── Tänased hooldused ────────────────────────────────────────────────────────
$today_maintenances = $wpdb->get_results($wpdb->prepare(
    "SELECT m.id, m.status, d.name AS device_name, c.name AS client_name,
            COALESCE(w.name, '—') AS worker_name
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id = m.device_id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id = COALESCE(m.client_id, d.client_id)
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id = m.worker_id
     WHERE m.scheduled_date = %s
     ORDER BY m.id ASC", $today));

// ── Meeldetuletused — seadmed, millele hooldus tulemas ───────────────────────
$reminders = $wpdb->get_results(
    "SELECT d.id, d.name AS device_name, d.maintenance_interval,
            c.name AS client_name, c.id AS client_id,
            MAX(m.completed_date) AS last_maintenance
     FROM {$wpdb->prefix}vesho_devices d
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id = d.client_id
     LEFT JOIN {$wpdb->prefix}vesho_maintenances m ON m.device_id = d.id AND m.status = 'completed'
     WHERE d.maintenance_interval > 0
     GROUP BY d.id
     HAVING last_maintenance IS NULL
        OR DATE_ADD(last_maintenance, INTERVAL d.maintenance_interval MONTH) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY last_maintenance ASC
     LIMIT 10");

// ── Kinnitust ootavad broneeringud ────────────────────────────────────────────
$pending_bookings_count = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances WHERE status='pending'");

// ── Viimased töökäsud ─────────────────────────────────────────────────────────
$recent_workorders = $wpdb->get_results(
    "SELECT wo.id, wo.title, wo.status, wo.created_at, c.name AS client_name
     FROM {$wpdb->prefix}vesho_workorders wo
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id = wo.client_id
     ORDER BY wo.created_at DESC LIMIT 5");

// ── Overdue invoices ──────────────────────────────────────────────────────────
$overdue_count  = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('unpaid','sent') AND due_date < %s", $today));
$overdue_total  = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('unpaid','sent') AND due_date < %s", $today));

// ── 6-month revenue trend ─────────────────────────────────────────────────────
$revenue_trend = [];
for ( $i = 5; $i >= 0; $i-- ) {
    $m_start = date('Y-m-01', strtotime("-{$i} months"));
    $m_end   = date('Y-m-t',  strtotime("-{$i} months"));
    $m_label = date_i18n('M', strtotime($m_start));
    $m_rev   = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices WHERE status='paid' AND invoice_date BETWEEN %s AND %s",
        $m_start, $m_end
    ));
    $revenue_trend[] = ['label' => $m_label, 'value' => $m_rev];
}

// ── Top clients by revenue (6 months) ─────────────────────────────────────────
$six_months_ago = date('Y-m-01', strtotime('-5 months'));
$top_clients = $wpdb->get_results($wpdb->prepare(
    "SELECT c.name, COALESCE(SUM(i.amount),0) AS total
     FROM {$wpdb->prefix}vesho_invoices i
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=i.client_id
     WHERE i.status='paid' AND i.invoice_date >= %s
     GROUP BY i.client_id ORDER BY total DESC LIMIT 5", $six_months_ago
));

// ── Row 2 extras (shop, requests, stock, tickets) ─────────────────────────────
$shop_orders    = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE status IN ('new','picking','pending','processing')");
$new_requests   = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_guest_requests WHERE status='new'");
$low_stock      = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND (
        (min_quantity IS NOT NULL AND quantity <= min_quantity) OR
        (min_quantity IS NULL AND quantity <= 0)
    )");

// ── Prev month revenue for trend ──────────────────────────────────────────────
$prev_month_start = date('Y-m-01', strtotime('-1 month'));
$prev_month_end   = date('Y-m-t',  strtotime('-1 month'));
$prev_month_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices WHERE status='paid' AND invoice_date BETWEEN %s AND %s",
    $prev_month_start, $prev_month_end));

// ── Worker activity this month ─────────────────────────────────────────────────
$worker_stats = $wpdb->get_results($wpdb->prepare(
    "SELECT w.name, COALESCE(SUM(wh.hours),0) as month_hours, COUNT(DISTINCT wh.workorder_id) as orders
     FROM {$wpdb->prefix}vesho_workers w
     LEFT JOIN {$wpdb->prefix}vesho_work_hours wh ON wh.worker_id=w.id AND wh.date>=%s
     WHERE w.active=1
     GROUP BY w.id ORDER BY month_hours DESC, w.name ASC LIMIT 8",
    $month_start));
$open_tickets   = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_support_tickets WHERE status IN ('open','in_progress')");
$open_tasks     = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_tasks WHERE status NOT IN ('done','cancelled')" );

// ── Helpers ───────────────────────────────────────────────────────────────────
$status_colors = [
    'open'       => ['#dbeafe','#1d4ed8'], 'pending'    => ['#fef9c3','#b45309'],
    'assigned'   => ['#dbeafe','#1d4ed8'], 'in_progress'=> ['#e0f2fe','#0369a1'],
    'completed'  => ['#dcfce7','#16a34a'], 'cancelled'  => ['#f3f4f6','#6b7280'],
    'paid'       => ['#dcfce7','#16a34a'], 'unpaid'     => ['#fee2e2','#b91c1c'],
    'overdue'    => ['#fee2e2','#b91c1c'], 'sent'       => ['#dbeafe','#1d4ed8'],
    'draft'      => ['#f3f4f6','#6b7280'], 'scheduled'  => ['#e0f2fe','#0369a1'],
    'new'        => ['#fef9c3','#b45309'],
];
if ( ! function_exists('dash_badge') ) {
    function dash_badge($status, $label, $map) {
        $c = $map[$status] ?? ['#f3f4f6','#6b7280'];
        return '<span style="display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;background:'.$c[0].';color:'.$c[1].'">'.esc_html($label).'</span>';
    }
}
$status_labels = [
    'open'       => 'Avatud',       'pending'    => 'Ootel',
    'assigned'   => 'Määratud',     'in_progress'=> 'Töös',
    'completed'  => 'Lõpetatud',    'cancelled'  => 'Tühistatud',
    'paid'       => 'Makstud',      'unpaid'     => 'Maksmata',
    'overdue'    => 'Tähtaeg ületatud', 'sent'   => 'Saadetud',
    'draft'      => 'Mustand',      'new'        => 'Uus',
    'scheduled'  => 'Planeeritud',
];
?>
<div class="crm-wrap">

<!-- ── Premium Page Header ────────────────────────────────────────────────── -->
<div class="crm-page-header">
    <div class="crm-page-header__logo">
        <svg width="26" height="26" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;width:26px;height:26px;flex-shrink:0">
            <path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#fff"/>
        </svg>
    </div>
    <div class="crm-page-header__body">
        <h1 class="crm-page-header__title">Vesho CRM — Töölaud</h1>
        <p class="crm-page-header__subtitle"><?php echo date_i18n('l, j. F Y'); ?></p>
    </div>
    <div class="crm-page-header__actions">
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=add'); ?>" class="crm-btn crm-btn-primary crm-btn-sm">+ Lisa klient</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&action=add'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">+ Lisa hooldus</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=add'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">+ Lisa arve</a>
    </div>
</div>

<!-- ── Row 1: 4 KPI cards (Guide.jsx spec) ────────────────────────────────── -->
<div class="crm-stat-grid crm-stat-grid--4" style="padding:0 2px">

    <div class="crm-stat" data-accent="teal">
        <div class="crm-stat__icon">👥</div>
        <div>
            <span class="crm-stat__num"><?php echo $total_clients; ?></span>
            <span class="crm-stat__label">Aktiivsed kliendid</span>
        </div>
    </div>

    <div class="crm-stat" data-accent="<?php echo $active_workorders_count > 0 ? 'success' : 'navy'; ?>">
        <div class="crm-stat__icon" style="background:<?php echo $active_workorders_count > 0 ? '#d1fae5' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $active_workorders_count > 0 ? '#10b981' : 'var(--crm-teal)'; ?>">⚡</div>
        <div>
            <span class="crm-stat__num"><?php echo $active_workorders_count; ?></span>
            <span class="crm-stat__label">Töös praegu</span>
        </div>
    </div>

    <div class="crm-stat" data-accent="<?php echo $today_maintenances_count > 0 ? 'warning' : 'navy'; ?>">
        <div class="crm-stat__icon" style="background:<?php echo $today_maintenances_count > 0 ? '#fef9c3' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $today_maintenances_count > 0 ? '#b45309' : 'var(--crm-teal)'; ?>">🔧</div>
        <div>
            <span class="crm-stat__num"><?php echo $today_maintenances_count; ?></span>
            <span class="crm-stat__label">Hooldusi täna</span>
        </div>
    </div>

    <div class="crm-stat" data-accent="<?php echo $unpaid_count > 0 ? 'warning' : 'success'; ?>">
        <div class="crm-stat__icon" style="background:<?php echo $unpaid_count > 0 ? '#fef9c3' : '#d1fae5'; ?>;color:<?php echo $unpaid_count > 0 ? '#b45309' : '#10b981'; ?>">📬</div>
        <div>
            <span class="crm-stat__num" style="<?php echo $unpaid_count > 0 ? 'color:#b45309' : ''; ?>"><?php echo $unpaid_count; ?></span>
            <span class="crm-stat__label">Arveid ootel<?php if ($unpaid_total > 0): ?> <span style="font-size:12px;font-weight:500">(<?php echo number_format($unpaid_total,0,',','&nbsp;'); ?> €)</span><?php endif; ?></span>
        </div>
    </div>

</div>

<!-- ── Row 2: secondary KPIs ──────────────────────────────────────────────── -->
<div class="crm-stat-grid crm-stat-grid--4" style="padding:0 2px;margin-bottom:28px">

    <div class="crm-stat" data-accent="success">
        <div class="crm-stat__icon" style="background:#d1fae5;color:#10b981">💰</div>
        <div>
            <span class="crm-stat__num" style="color:#10b981;font-size:20px"><?php echo number_format($month_revenue,0,',','&nbsp;'); ?> €</span>
            <span class="crm-stat__label">Kuu tulu (tasutud)
                <?php if ($prev_month_revenue > 0) :
                    $trend_pct = round(($month_revenue - $prev_month_revenue) / $prev_month_revenue * 100, 1);
                    $trend_col = $trend_pct >= 0 ? '#16a34a' : '#dc2626';
                    $trend_arrow = $trend_pct >= 0 ? '↑' : '↓';
                ?>
                <span style="color:<?php echo $trend_col; ?>;font-weight:700;font-size:11px;margin-left:4px"><?php echo $trend_arrow; ?><?php echo abs($trend_pct); ?>%</span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders'); ?>" class="crm-stat" data-accent="<?php echo $shop_orders > 0 ? 'danger' : 'navy'; ?>" style="text-decoration:none">
        <div class="crm-stat__icon" style="background:<?php echo $shop_orders > 0 ? '#fee2e2' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $shop_orders > 0 ? '#b91c1c' : 'var(--crm-teal)'; ?>">🛒</div>
        <div>
            <span class="crm-stat__num" style="<?php echo $shop_orders > 0 ? 'color:#ef4444' : ''; ?>"><?php echo $shop_orders; ?></span>
            <span class="crm-stat__label">Aktiivsed tellimused</span>
        </div>
    </a>

    <div class="crm-stat" data-accent="<?php echo $new_requests > 0 ? 'warning' : 'navy'; ?>">
        <div class="crm-stat__icon" style="background:<?php echo $new_requests > 0 ? '#fef9c3' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $new_requests > 0 ? '#b45309' : 'var(--crm-teal)'; ?>">🔔</div>
        <div>
            <span class="crm-stat__num"><?php echo $new_requests; ?></span>
            <span class="crm-stat__label">Uut päringut</span>
        </div>
    </div>

    <div class="crm-stat" data-accent="<?php echo $low_stock > 0 ? 'danger' : 'navy'; ?>">
        <div class="crm-stat__icon" style="background:<?php echo $low_stock > 0 ? '#fee2e2' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $low_stock > 0 ? '#b91c1c' : 'var(--crm-teal)'; ?>">📦</div>
        <div>
            <span class="crm-stat__num"><?php echo $low_stock; ?></span>
            <span class="crm-stat__label">Laopuudus</span>
        </div>
    </div>

</div>
<!-- ── Row 3: tickets + tasks ─────────────────────────────────────────────── -->
<div class="crm-stat-grid crm-stat-grid--4" style="padding:0 2px;margin-bottom:28px">
    <a href="<?php echo admin_url('admin.php?page=vesho-crm-tickets&status=open'); ?>" class="crm-stat" data-accent="<?php echo $open_tickets > 0 ? 'warning' : 'navy'; ?>" style="text-decoration:none">
        <div class="crm-stat__icon" style="background:<?php echo $open_tickets > 0 ? '#fef9c3' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $open_tickets > 0 ? '#b45309' : 'var(--crm-teal)'; ?>">🎫</div>
        <div>
            <span class="crm-stat__num" style="<?php echo $open_tickets > 0 ? 'color:#b45309' : ''; ?>"><?php echo $open_tickets; ?></span>
            <span class="crm-stat__label">Avatud piletid</span>
        </div>
    </a>
    <a href="<?php echo admin_url('admin.php?page=vesho-crm-tasks'); ?>" class="crm-stat" data-accent="<?php echo $open_tasks > 0 ? 'warning' : 'navy'; ?>" style="text-decoration:none">
        <div class="crm-stat__icon" style="background:<?php echo $open_tasks > 0 ? '#fef9c3' : 'rgba(0,180,200,.1)'; ?>;color:<?php echo $open_tasks > 0 ? '#b45309' : 'var(--crm-teal)'; ?>">✅</div>
        <div>
            <span class="crm-stat__num" style="<?php echo $open_tasks > 0 ? 'color:#b45309' : ''; ?>"><?php echo $open_tasks; ?></span>
            <span class="crm-stat__label">Avatud ülesanded</span>
        </div>
    </a>
</div>

<!-- ── Tähtaja ületanud arved ──────────────────────────────────────────────── -->
<?php if ($overdue_count > 0): ?>
<div style="padding:0 2px;margin-bottom:16px">
    <div class="crm-card" style="margin:0;border-left:4px solid #ef4444;background:#fff5f5">
        <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px">
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:22px">⚠️</span>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#b91c1c">Tähtaja ületanud arved</div>
                    <div style="font-size:12px;color:#dc2626"><?php echo $overdue_count; ?> arvet — kokku <?php echo number_format($overdue_total,2,',','&nbsp;'); ?> €</div>
                </div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&status=overdue'); ?>" class="crm-btn crm-btn-sm" style="background:#ef4444;color:#fff;border:none;white-space:nowrap">→ Vaata arveid</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Kinnitust ootavad broneeringud ─────────────────────────────────────── -->
<?php if ($pending_bookings_count > 0): ?>
<div style="padding:0 2px;margin-bottom:20px">
    <div class="crm-card" style="margin:0;border-left:4px solid #f59e0b;background:#fffbeb">
        <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px">
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:22px">📋</span>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#92400e">Kinnitust ootavad broneeringud</div>
                    <div style="font-size:12px;color:#b45309"><?php echo $pending_bookings_count; ?> broneeringut ootab kinnitust</div>
                </div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&status=pending'); ?>" class="crm-btn crm-btn-sm" style="background:#f59e0b;color:#fff;border:none;white-space:nowrap">→ Vaata kõiki</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Tänased hooldused ───────────────────────────────────────────────────── -->
<div style="padding:0 2px;margin-bottom:20px">
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">🔧 Tänased hooldused <span class="crm-count">(<?php echo count($today_maintenances); ?>)</span></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Vaata kõiki</a>
        </div>
        <?php if (empty($today_maintenances)): ?>
            <div class="crm-empty" style="padding:32px">Tänaseid hooldusi pole</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--crm-border-light,#f0f4f7)">
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Klient</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Seade</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Tehnik</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Staatus</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($today_maintenances as $m):
                $sl = $status_labels[$m->status] ?? $m->status; ?>
                <tr style="border-bottom:1px solid var(--crm-border-light,#f0f4f7)">
                    <td style="padding:10px 18px;font-size:13px;font-weight:500;color:#1a2a38"><?php echo esc_html($m->client_name ?? '—'); ?></td>
                    <td style="padding:10px 18px;font-size:13px;color:#374151"><?php echo esc_html($m->device_name ?? '—'); ?></td>
                    <td style="padding:10px 18px;font-size:13px;color:#6b8599"><?php echo esc_html($m->worker_name); ?></td>
                    <td style="padding:10px 18px"><?php echo dash_badge($m->status, $sl, $status_colors); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ── Meeldetuletused (hooldusintervall) ─────────────────────────────────── -->
<div style="padding:0 2px;margin-bottom:20px">
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">⏰ Meeldetuletused <span class="crm-count">(<?php echo count($reminders); ?>)</span></span>
        </div>
        <?php if (empty($reminders)): ?>
            <div class="crm-empty" style="padding:32px">Tähtaegseid hooldusi pole</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--crm-border-light,#f0f4f7)">
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Seade</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Klient</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Viimane hooldus</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Järgmine hooldus</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Staatus</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reminders as $r):
                $last_date = $r->last_maintenance ? date_create($r->last_maintenance) : null;
                $next_date = $last_date
                    ? date_create($r->last_maintenance)->modify('+' . (int)$r->maintenance_interval . ' months')
                    : null;
                $today_dt  = date_create($today);
                $is_overdue = $next_date && $next_date < $today_dt;
                $days_diff  = $next_date ? (int)$today_dt->diff($next_date)->days * ($is_overdue ? -1 : 1) : null;
            ?>
                <tr style="border-bottom:1px solid var(--crm-border-light,#f0f4f7)">
                    <td style="padding:10px 18px;font-size:13px;font-weight:500;color:#1a2a38"><?php echo esc_html($r->device_name); ?></td>
                    <td style="padding:10px 18px;font-size:13px;color:#374151">
                        <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&id='.intval($r->client_id)); ?>" style="color:var(--crm-teal,#00b4c8);text-decoration:none"><?php echo esc_html($r->client_name ?? '—'); ?></a>
                    </td>
                    <td style="padding:10px 18px;font-size:13px;color:#6b8599">
                        <?php echo $last_date ? esc_html(date_format($last_date,'d.m.Y')) : '—'; ?>
                    </td>
                    <td style="padding:10px 18px;font-size:13px;color:#374151">
                        <?php echo $next_date ? esc_html(date_format($next_date,'d.m.Y')) : '—'; ?>
                    </td>
                    <td style="padding:10px 18px">
                        <?php if ($is_overdue): ?>
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#b91c1c;background:#fee2e2;padding:3px 10px;border-radius:99px">🔴 Ületähtaeg</span>
                        <?php elseif ($next_date): ?>
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#b45309;background:#fef9c3;padding:3px 10px;border-radius:99px">🟡 Tulemas <?php echo abs((int)$days_diff); ?>p</span>
                        <?php else: ?>
                            <span style="font-size:12px;color:#6b7280">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ── Viimased töökäsud ───────────────────────────────────────────────────── -->
<div style="padding:0 2px;margin-bottom:20px">
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">📋 Viimased töökäsud</span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Vaata kõiki</a>
        </div>
        <?php if (empty($recent_workorders)): ?>
            <div class="crm-empty" style="padding:32px">Töökäske pole</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--crm-border-light,#f0f4f7)">
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">#</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Pealkiri</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Klient</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Staatus</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Kuupäev</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent_workorders as $wo):
                $sl = $status_labels[$wo->status] ?? $wo->status; ?>
                <tr style="border-bottom:1px solid var(--crm-border-light,#f0f4f7)">
                    <td style="padding:10px 18px;font-size:12px;color:#6b8599">#<?php echo (int)$wo->id; ?></td>
                    <td style="padding:10px 18px;font-size:13px;font-weight:500;color:#1a2a38"><?php echo esc_html($wo->title ?: '—'); ?></td>
                    <td style="padding:10px 18px;font-size:13px;color:#374151"><?php echo esc_html($wo->client_name ?? '—'); ?></td>
                    <td style="padding:10px 18px"><?php echo dash_badge($wo->status, $sl, $status_colors); ?></td>
                    <td style="padding:10px 18px;font-size:12px;color:#6b8599;white-space:nowrap"><?php echo $wo->created_at ? date_i18n('d.m.Y', strtotime($wo->created_at)) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ── 6-kuu käibegraafik + Top kliendid ───────────────────────────────────── -->
<div style="padding:0 2px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- 6-kuu graafik (canvas) -->
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">📈 Käive (6 kuud)</span>
        </div>
        <div style="padding:16px 20px 20px">
            <?php
            $trend_max = max(array_column($revenue_trend,'value') ?: [1]);
            $trend_max = $trend_max ?: 1;
            ?>
            <div style="display:flex;align-items:flex-end;gap:8px;height:100px;border-bottom:1px solid #e5e7eb;padding-bottom:4px">
            <?php foreach ($revenue_trend as $tm):
                $pct = $trend_max > 0 ? round(($tm['value'] / $trend_max) * 100) : 0;
                $pct = max($pct, 2); // min visible bar
            ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%">
                    <div style="margin-top:auto;width:100%;background:var(--crm-teal,#00b4c8);border-radius:4px 4px 0 0;height:<?php echo $pct; ?>%;min-height:3px;transition:height .3s"></div>
                </div>
            <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;margin-top:6px">
            <?php foreach ($revenue_trend as $tm): ?>
                <div style="flex:1;text-align:center;font-size:10px;color:#6b8599"><?php echo esc_html($tm['label']); ?></div>
            <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap">
            <?php foreach ($revenue_trend as $tm): ?>
                <div style="font-size:11px;color:#6b8599"><?php echo esc_html($tm['label']); ?>: <strong style="color:#1a2a38"><?php echo number_format($tm['value'],0,',','&nbsp;'); ?> €</strong></div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top kliendid -->
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">🏆 Top kliendid (6 kuud)</span>
        </div>
        <?php if (empty($top_clients)): ?>
            <div class="crm-empty" style="padding:32px">Andmed puuduvad</div>
        <?php else: ?>
        <div style="padding:8px 0">
        <?php
        $tc_max = (float)($top_clients[0]->total ?? 1) ?: 1;
        foreach ($top_clients as $i => $tc):
            $pct = $tc_max > 0 ? round(($tc->total / $tc_max) * 100) : 0;
        ?>
            <div style="padding:10px 20px;display:flex;flex-direction:column;gap:4px">
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span style="font-weight:500;color:#1a2a38"><?php echo esc_html($tc->name ?? '—'); ?></span>
                    <span style="color:#10b981;font-weight:600"><?php echo number_format((float)$tc->total,0,',','&nbsp;'); ?> €</span>
                </div>
                <div style="height:4px;background:#f0f4f7;border-radius:2px">
                    <div style="height:4px;background:var(--crm-teal,#00b4c8);border-radius:2px;width:<?php echo $pct; ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Töötajate aktiivsus (kuu) ────────────────────────────────────────────── -->
<?php if (!empty($worker_stats)) : ?>
<div style="padding:0 2px;margin-bottom:20px">
    <div class="crm-card" style="margin:0">
        <div class="crm-card-header">
            <span class="crm-card-title">👷 Töötajate aktiivsus <?php echo date_i18n('F Y'); ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-sales'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Müügiraport</a>
        </div>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid var(--crm-border-light,#f0f4f7)">
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Töötaja</th>
                    <th style="padding:10px 18px;text-align:right;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Tunnid</th>
                    <th style="padding:10px 18px;text-align:right;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Töökäsud</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:600;color:#6b8599;text-transform:uppercase">Aktiivsus</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ws_max = max(array_column((array)$worker_stats, 'month_hours') ?: [0]);
            $ws_max = $ws_max > 0 ? $ws_max : 1;
            foreach ($worker_stats as $ws) :
                $pct = round(($ws->month_hours / $ws_max) * 100);
            ?>
            <tr style="border-bottom:1px solid var(--crm-border-light,#f0f4f7)">
                <td style="padding:10px 18px;font-size:13px;font-weight:500;color:#1a2a38"><?php echo esc_html($ws->name); ?></td>
                <td style="padding:10px 18px;font-size:13px;color:#374151;text-align:right"><?php echo number_format((float)$ws->month_hours,1); ?> h</td>
                <td style="padding:10px 18px;font-size:13px;color:#374151;text-align:right"><?php echo (int)$ws->orders; ?></td>
                <td style="padding:10px 18px">
                    <div style="background:#f0f4f7;border-radius:4px;overflow:hidden;height:8px;min-width:80px">
                        <div style="width:<?php echo $pct; ?>%;background:var(--crm-teal,#00b4c8);height:100%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- .crm-wrap -->
