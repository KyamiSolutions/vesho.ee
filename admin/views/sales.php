<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$year  = isset($_GET['year'])  ? absint($_GET['year'])  : intval(date('Y'));
$month = isset($_GET['month']) ? absint($_GET['month']) : 0;

// ── Current month KPI ─────────────────────────────────────────────────────
$cur_month_start = date('Y-m-01');
$cur_month_end   = date('Y-m-t');

$kuu_tulu = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices
     WHERE status IN ('paid','sent')
       AND invoice_date >= '$cur_month_start' AND invoice_date <= '$cur_month_end'"
);

$kuu_tookasud = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders
     WHERE status = 'completed'
       AND completed_date >= '$cur_month_start' AND completed_date <= '$cur_month_end'"
);

$kuu_hooldused = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances
     WHERE status = 'completed'
       AND completed_date >= '$cur_month_start' AND completed_date <= '$cur_month_end'"
);

$kuu_arved_cnt = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_invoices
     WHERE status IN ('paid','sent')
       AND invoice_date >= '$cur_month_start' AND invoice_date <= '$cur_month_end'"
);
$kuu_keskmine = $kuu_arved_cnt > 0 ? $kuu_tulu / $kuu_arved_cnt : 0;

// ── Monthly bar chart data for selected year ─────────────────────────────
$chart_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE_FORMAT(invoice_date,'%Y-%m') as mon, SUM(amount) as total
     FROM {$wpdb->prefix}vesho_invoices
     WHERE status IN ('paid','sent')
       AND YEAR(invoice_date) = %d
     GROUP BY mon ORDER BY mon ASC",
    $year
));
$chart_map = [];
foreach ($chart_rows as $r) $chart_map[$r->mon] = (float)$r->total;

// Build array of all 12 months for selected year
$chart_months = [];
for ($i = 1; $i <= 12; $i++) {
    $key = $year . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $chart_months[] = [
        'key'   => $key,
        'label' => date('M', mktime(0,0,0,$i,1,$year)),
        'total' => $chart_map[$key] ?? 0,
    ];
}
$chart_max = max(array_column($chart_months, 'total') ?: [1]);
if ($chart_max < 1) $chart_max = 1;

// ── Revenue breakdown: hoolduselt vs e-poest ─────────────────────────────
$rev_hooldus = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices
     WHERE status IN ('paid','sent') AND maintenance_id IS NOT NULL
       AND YEAR(invoice_date)=" . intval($year)
);

$rev_epood = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(i.amount),0)
     FROM {$wpdb->prefix}vesho_invoices i
     INNER JOIN {$wpdb->prefix}vesho_shop_orders so ON so.client_id = i.client_id
     WHERE i.status IN ('paid','sent') AND i.maintenance_id IS NULL
       AND YEAR(i.invoice_date)=" . intval($year)
);
// fallback: if no shop_orders join — use remaining amount
if ($rev_epood <= 0) {
    $rev_total_year = (float) $wpdb->get_var(
        "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices
         WHERE status IN ('paid','sent') AND YEAR(invoice_date)=" . intval($year)
    );
    $rev_epood = max(0, $rev_total_year - $rev_hooldus);
}

// ── Revenue by month for selected year ───────────────────────────────────
$monthly_paid = $wpdb->get_results($wpdb->prepare(
    "SELECT MONTH(invoice_date) as m, SUM(amount) as total, COUNT(*) as count
     FROM {$wpdb->prefix}vesho_invoices
     WHERE status='paid' AND YEAR(invoice_date)=%d
     GROUP BY MONTH(invoice_date) ORDER BY m ASC", $year
));
$monthly_sent = $wpdb->get_results($wpdb->prepare(
    "SELECT MONTH(invoice_date) as m, SUM(amount) as total, COUNT(*) as count
     FROM {$wpdb->prefix}vesho_invoices
     WHERE status='sent' AND YEAR(invoice_date)=%d
     GROUP BY MONTH(invoice_date) ORDER BY m ASC", $year
));

$monthly_paid_map = [];
foreach ($monthly_paid as $row) $monthly_paid_map[$row->m] = $row;
$monthly_sent_map = [];
foreach ($monthly_sent as $row) $monthly_sent_map[$row->m] = $row;

// Completed workorders by month
$monthly_wo = $wpdb->get_results($wpdb->prepare(
    "SELECT MONTH(completed_date) as m, COUNT(*) as cnt
     FROM {$wpdb->prefix}vesho_workorders
     WHERE status='completed' AND YEAR(completed_date)=%d
     GROUP BY m", $year
));
$monthly_wo_map = [];
foreach ($monthly_wo as $r) $monthly_wo_map[$r->m] = (int)$r->cnt;

$year_total = 0;
$year_count = 0;
for ($m=1;$m<=12;$m++){
    $year_total += isset($monthly_paid_map[$m]) ? floatval($monthly_paid_map[$m]->total) : 0;
    $year_total += isset($monthly_sent_map[$m]) ? floatval($monthly_sent_map[$m]->total) : 0;
    $year_count += isset($monthly_paid_map[$m]) ? intval($monthly_paid_map[$m]->count) : 0;
    $year_count += isset($monthly_sent_map[$m]) ? intval($monthly_sent_map[$m]->count) : 0;
}

// Unpaid
$unpaid = $wpdb->get_row($wpdb->prepare(
    "SELECT COUNT(*) as cnt, SUM(amount) as total FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('unpaid','overdue') AND YEAR(invoice_date)=%d", $year
));

// Top 5 clients
$top_clients = $wpdb->get_results($wpdb->prepare(
    "SELECT client_name, client_company, COUNT(*) as invoice_count, SUM(amount) as total
     FROM {$wpdb->prefix}vesho_invoices
     WHERE status='paid' AND YEAR(invoice_date)=%d
     GROUP BY client_id ORDER BY total DESC LIMIT 5", $year
));

// Previous year comparison
$prev_year_total = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('paid','sent') AND YEAR(invoice_date)=%d",
    $year - 1
));

// Top workers by hours this year
$top_workers = $wpdb->get_results($wpdb->prepare(
    "SELECT w.name, SUM(wh.hours) as total_hours, COUNT(DISTINCT wh.workorder_id) as orders
     FROM {$wpdb->prefix}vesho_work_hours wh
     JOIN {$wpdb->prefix}vesho_workers w ON w.id=wh.worker_id
     WHERE YEAR(wh.date)=%d AND wh.hours > 0
     GROUP BY wh.worker_id ORDER BY total_hours DESC LIMIT 10", $year
));

// Shop orders revenue by month
$shop_monthly_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT MONTH(created_at) as m, SUM(total_price) as total, COUNT(*) as count
     FROM {$wpdb->prefix}vesho_shop_orders
     WHERE status IN ('shipped','completed','paid','confirmed') AND YEAR(created_at)=%d
     GROUP BY MONTH(created_at) ORDER BY m ASC", $year
));
$shop_monthly_map = [];
$shop_year_total  = 0;
foreach ($shop_monthly_rows as $r) {
    $shop_monthly_map[$r->m] = $r;
    $shop_year_total += (float)$r->total;
}

$months_et = ['','Jaanuar','Veebruar','Märts','Aprill','Mai','Juuni','Juuli','August','September','Oktoober','November','Detsember'];
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📊 Müügiraport</h1>

    <!-- Current month KPI cards -->
    <div class="crm-stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px">
        <div class="crm-stat-card">
            <div class="crm-stat-num"><?php echo vesho_crm_format_money($kuu_tulu); ?></div>
            <div class="crm-stat-label">Kuu tulu (<?php echo date('M Y'); ?>)</div>
        </div>
        <div class="crm-stat-card">
            <div class="crm-stat-num"><?php echo $kuu_tookasud; ?></div>
            <div class="crm-stat-label">Kuu töökäsud</div>
        </div>
        <div class="crm-stat-card">
            <div class="crm-stat-num"><?php echo $kuu_hooldused; ?></div>
            <div class="crm-stat-label">Kuu hooldused</div>
        </div>
        <div class="crm-stat-card">
            <div class="crm-stat-num"><?php echo $kuu_keskmine > 0 ? vesho_crm_format_money($kuu_keskmine) : '–'; ?></div>
            <div class="crm-stat-label">Keskmine arve</div>
        </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="page" value="vesho-crm-sales">
            <label class="crm-form-label" style="margin:0">Aasta:</label>
            <select class="crm-form-select" name="year" style="max-width:100px;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
                <?php for ($y=date('Y'); $y>=2020; $y--) : ?>
                    <option value="<?php echo $y; ?>" <?php selected($year,$y); ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <!-- Year KPI cards -->
    <div class="crm-stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px">
        <div class="crm-stat-card"><div class="crm-stat-num"><?php echo vesho_crm_format_money($year_total); ?></div><div class="crm-stat-label">Arved <?php echo $year; ?></div></div>
        <?php if ($shop_year_total > 0) : ?>
        <div class="crm-stat-card"><div class="crm-stat-num" style="color:#1d4ed8"><?php echo vesho_crm_format_money($shop_year_total); ?></div><div class="crm-stat-label">🛒 Pood <?php echo $year; ?></div></div>
        <?php endif; ?>
        <div class="crm-stat-card"><div class="crm-stat-num"><?php echo $year_count; ?></div><div class="crm-stat-label">Arvet (makstud+saadetud)</div></div>
        <div class="crm-stat-card"><div class="crm-stat-num" style="color:#ef4444"><?php echo vesho_crm_format_money($unpaid->total??0); ?></div><div class="crm-stat-label">Maksmata (<?php echo intval($unpaid->cnt); ?> arvet)</div></div>
        <div class="crm-stat-card"><div class="crm-stat-num"><?php echo $year_count>0 ? vesho_crm_format_money($year_total/$year_count) : '–'; ?></div><div class="crm-stat-label">Keskmine arve</div></div>
        <?php if ($prev_year_total > 0) :
            $yoy = round(($year_total - $prev_year_total) / $prev_year_total * 100, 1);
            $yoy_color = $yoy >= 0 ? '#16a34a' : '#ef4444';
        ?>
        <div class="crm-stat-card">
            <div class="crm-stat-num" style="color:<?php echo $yoy_color; ?>"><?php echo ($yoy>=0?'+':'').$yoy; ?>%</div>
            <div class="crm-stat-label">vs <?php echo $year-1; ?> (<?php echo vesho_crm_format_money($prev_year_total); ?>)</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Last 12 months HTML/CSS bar chart -->
    <div class="crm-card" style="margin-bottom:20px">
        <div class="crm-card-header"><span class="crm-card-title">📈 <?php echo intval($year); ?>. aasta käive kuude kaupa</span></div>
        <div style="padding:20px 20px 12px">
            <div style="display:flex;align-items:flex-end;gap:6px;height:160px">
                <?php foreach ($chart_months as $cm) :
                    $pct = $chart_max > 0 ? round(($cm['total'] / $chart_max) * 100) : 0;
                    $bar_h = max($pct, $cm['total'] > 0 ? 2 : 0);
                    $is_current = ($cm['key'] === date('Y-m') && intval($year) === intval(date('Y')));
                ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end">
                    <?php if ($cm['total'] > 0) : ?>
                    <div style="font-size:10px;color:#0369a1;font-weight:600;white-space:nowrap">
                        <?php echo $cm['total'] >= 1000 ? number_format($cm['total']/1000,1).'k' : number_format($cm['total'],0); ?>
                    </div>
                    <?php endif; ?>
                    <div style="width:100%;background:<?php echo $is_current ? '#0284c7' : '#00b4c8'; ?>;height:<?php echo $bar_h; ?>%;border-radius:3px 3px 0 0;min-height:<?php echo $cm['total']>0?'3px':'1px'; ?>;<?php echo $cm['total']<=0?'background:#e2e8f0;':''; ?>"></div>
                    <div style="font-size:10px;color:#64748b;white-space:nowrap;transform:rotate(-30deg);transform-origin:top center;margin-top:4px;height:28px">
                        <?php echo esc_html($cm['label']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Revenue breakdown -->
    <div class="crm-card" style="margin-bottom:20px">
        <div class="crm-card-header"><span class="crm-card-title">💰 Tulu jaotus <?php echo $year; ?></span></div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div style="padding:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px">
                    <div style="font-size:12px;color:#16a34a;font-weight:600;margin-bottom:6px">🔧 Hoolduselt</div>
                    <div style="font-size:22px;font-weight:700;color:#166534"><?php echo vesho_crm_format_money($rev_hooldus); ?></div>
                    <?php if (($rev_hooldus + $rev_epood) > 0) : $pct_h = round($rev_hooldus / ($rev_hooldus + $rev_epood) * 100); ?>
                    <div style="font-size:12px;color:#16a34a;margin-top:4px"><?php echo $pct_h; ?>% kogutulust</div>
                    <?php endif; ?>
                </div>
                <div style="padding:16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px">
                    <div style="font-size:12px;color:#1d4ed8;font-weight:600;margin-bottom:6px">🛒 E-poest</div>
                    <div style="font-size:22px;font-weight:700;color:#1e3a8a"><?php echo vesho_crm_format_money($rev_epood); ?></div>
                    <?php if (($rev_hooldus + $rev_epood) > 0) : $pct_e = round($rev_epood / ($rev_hooldus + $rev_epood) * 100); ?>
                    <div style="font-size:12px;color:#1d4ed8;margin-top:4px"><?php echo $pct_e; ?>% kogutulust</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <!-- Monthly table with all columns -->
        <div class="crm-card">
            <div class="crm-card-header"><span class="crm-card-title">Kuukäive <?php echo $year; ?></span></div>
            <table class="crm-table">
                <thead><tr>
                    <th>Kuu</th>
                    <th>Arved kokku</th>
                    <th>Tasutud</th>
                    <th>Saadetud</th>
                    <th>Pood (tellimused)</th>
                    <th>Töökäsud lõpetatud</th>
                </tr></thead>
                <tbody>
                <?php
                for ($m=1; $m<=12; $m++) :
                    $paid_row = $monthly_paid_map[$m] ?? null;
                    $sent_row = $monthly_sent_map[$m] ?? null;
                    $paid_amt  = $paid_row ? floatval($paid_row->total) : 0;
                    $sent_amt  = $sent_row ? floatval($sent_row->total) : 0;
                    $paid_cnt  = $paid_row ? intval($paid_row->count) : 0;
                    $sent_cnt  = $sent_row ? intval($sent_row->count) : 0;
                    $total_cnt = $paid_cnt + $sent_cnt;
                    $wo_cnt    = $monthly_wo_map[$m] ?? 0;
                    $shop_row  = $shop_monthly_map[$m] ?? null;
                    $shop_amt  = $shop_row ? floatval($shop_row->total) : 0;
                    $shop_cnt  = $shop_row ? intval($shop_row->count) : 0;
                ?>
                <tr>
                    <td><?php echo $months_et[$m]; ?></td>
                    <td><?php echo $total_cnt > 0 ? $total_cnt : '–'; ?></td>
                    <td><?php echo $paid_amt > 0 ? vesho_crm_format_money($paid_amt) : '–'; ?></td>
                    <td><?php echo $sent_amt > 0 ? vesho_crm_format_money($sent_amt) : '–'; ?></td>
                    <td><?php echo $shop_amt > 0 ? vesho_crm_format_money($shop_amt).'<small style="color:#6b8599"> ('.$shop_cnt.')</small>' : '–'; ?></td>
                    <td><?php echo $wo_cnt > 0 ? $wo_cnt : '–'; ?></td>
                </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- Top 5 clients -->
        <div class="crm-card">
            <div class="crm-card-header"><span class="crm-card-title">Top 5 kliendid</span></div>
            <?php if (empty($top_clients)) : ?>
                <div class="crm-empty">Andmed puuduvad.</div>
            <?php else : ?>
            <table class="crm-table">
                <thead><tr><th>Klient</th><th>Käive</th></tr></thead>
                <tbody>
                <?php foreach ($top_clients as $tc) :
                    $display_name = !empty($tc->client_company) ? $tc->client_company : $tc->client_name;
                ?>
                <tr>
                    <td><?php echo esc_html($display_name); ?><br><small style="color:#6b8599"><?php echo intval($tc->invoice_count); ?> arvet</small></td>
                    <td><strong><?php echo vesho_crm_format_money($tc->total); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($top_workers)) : ?>
    <div class="crm-card" style="margin-bottom:20px">
        <div class="crm-card-header"><span class="crm-card-title">👷 Töötajate tulemuslikkus <?php echo $year; ?></span></div>
        <table class="crm-table">
            <thead><tr><th>#</th><th>Töötaja</th><th>Tunnid</th><th>Töökäsud</th><th style="width:200px">Aktiivsus</th></tr></thead>
            <tbody>
            <?php
            $max_h = max(array_column((array)$top_workers, 'total_hours') ?: [0]);
            foreach ($top_workers as $i => $tw) :
                $pct = $max_h > 0 ? round(($tw->total_hours / $max_h) * 100) : 0;
            ?>
            <tr>
                <td style="color:#6b8599"><?php echo $i+1; ?></td>
                <td><strong><?php echo esc_html($tw->name); ?></strong></td>
                <td><?php echo number_format((float)$tw->total_hours, 1); ?> h</td>
                <td><?php echo intval($tw->orders ?: 0); ?></td>
                <td>
                    <div style="background:#e8f4f8;border-radius:4px;overflow:hidden;height:14px">
                        <div style="width:<?php echo $pct; ?>%;background:#10b981;height:100%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
