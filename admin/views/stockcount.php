<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action   = sanitize_text_field( $_GET['action'] ?? '' );
$count_id = absint( $_GET['count_id'] ?? 0 );

// ── Finalize action ────────────────────────────────────────────────────────────
if ( $action === 'finalize' && $count_id ) {
    check_admin_referer( 'vesho_finalize_stockcount_' . $count_id );
    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_stock_count_items WHERE stock_count_id=%d", $count_id
    ) );
    foreach ( $items as $item ) {
        if ( $item->counted_qty !== null ) {
            $wpdb->update(
                $wpdb->prefix . 'vesho_inventory',
                ['quantity' => $item->counted_qty],
                ['id' => $item->inventory_id]
            );
        }
    }
    $wpdb->update( $wpdb->prefix . 'vesho_stock_counts', ['status' => 'finalized'], ['id' => $count_id] );

    // ── Send finalize summary email to admin ───────────────────────────────
    $count_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_stock_counts WHERE id=%d", $count_id ) );
    $diff_items = array_filter( $items, fn($i) => $i->counted_qty !== null && (float)$i->counted_qty !== (float)$i->expected_qty );
    $admin_email = get_option('admin_email');
    $site_name   = get_bloginfo('name');
    $subject     = '[' . $site_name . '] Inventuur kinnitatud: ' . ( $count_row->name ?? '#' . $count_id );
    $rows = '';
    foreach ( $diff_items as $di ) {
        $diff = round( (float)$di->counted_qty - (float)$di->expected_qty, 2 );
        $sign = $diff >= 0 ? '+' : '';
        $rows .= '<tr>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e2e8f0">' . esc_html($di->name) . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:right">' . number_format((float)$di->expected_qty, 2, ',', ' ') . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:right">' . number_format((float)$di->counted_qty, 2, ',', ' ') . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:right;color:' . ($diff >= 0 ? '#0284c7' : '#dc2626') . ';font-weight:600">' . $sign . number_format($diff, 2, ',', ' ') . '</td>'
            . '</tr>';
    }
    if ( $rows === '' ) $rows = '<tr><td colspan="4" style="padding:10px;text-align:center;color:#16a34a">Kõik saldod vastavad — erinevusi pole! ✅</td></tr>';
    $body = '<html><body style="font-family:sans-serif;font-size:14px;color:#1a2a38">'
        . '<h2 style="color:#00b4c8">Inventuur kinnitatud</h2>'
        . '<p><strong>' . esc_html($count_row->name ?? '') . '</strong> — kinnitatud ' . current_time('d.m.Y H:i') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;margin-top:12px">'
        . '<thead><tr style="background:#f1f5f9">'
        . '<th style="padding:8px 10px;text-align:left">Nimetus</th>'
        . '<th style="padding:8px 10px;text-align:right">Oodatud</th>'
        . '<th style="padding:8px 10px;text-align:right">Loendatud</th>'
        . '<th style="padding:8px 10px;text-align:right">Vahe</th>'
        . '</tr></thead>'
        . '<tbody>' . $rows . '</tbody>'
        . '</table>'
        . '<p style="margin-top:16px;color:#64748b;font-size:12px">Laosaldod on uuendatud.</p>'
        . '</body></html>';
    wp_mail( $admin_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8'] );

    wp_redirect( add_query_arg( ['page' => 'vesho-crm-stockcount', 'msg' => 'finalized'], admin_url('admin.php') ) );
    exit;
}

// ── Create new count ───────────────────────────────────────────────────────────
if ( $action === 'create' ) {
    check_admin_referer( 'vesho_create_stockcount' );
    $name = sanitize_text_field( $_POST['count_name'] ?? 'Inventuur ' . date('d.m.Y') );
    $wpdb->insert( $wpdb->prefix . 'vesho_stock_counts', [
        'name'       => $name,
        'status'     => 'active',
        'created_at' => current_time('mysql'),
    ] );
    $new_id = $wpdb->insert_id;
    // Snapshot current inventory quantities
    $inv_items = $wpdb->get_results(
        "SELECT id, quantity FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY id ASC"
    );
    foreach ( $inv_items as $inv ) {
        $wpdb->insert( $wpdb->prefix . 'vesho_stock_count_items', [
            'stock_count_id' => $new_id,
            'inventory_id'   => $inv->id,
            'expected_qty'   => $inv->quantity,
            'counted_qty'    => null,
        ] );
    }
    wp_redirect( add_query_arg( ['page' => 'vesho-crm-stockcount', 'action' => 'view', 'count_id' => $new_id], admin_url('admin.php') ) );
    exit;
}

// ── Save counted qty (inline form) ────────────────────────────────────────────
if ( $action === 'save_count' && $count_id ) {
    check_admin_referer( 'vesho_save_count_' . $count_id );
    $items_post = $_POST['counted'] ?? [];
    foreach ( $items_post as $item_id => $qty ) {
        $item_id = absint($item_id);
        $qty     = $qty === '' ? null : (float)$qty;
        $wpdb->update(
            $wpdb->prefix . 'vesho_stock_count_items',
            ['counted_qty' => $qty],
            ['id' => $item_id, 'stock_count_id' => $count_id]
        );
    }
    wp_redirect( add_query_arg( ['page' => 'vesho-crm-stockcount', 'action' => 'view', 'count_id' => $count_id, 'msg' => 'saved'], admin_url('admin.php') ) );
    exit;
}

// ── Single count view ─────────────────────────────────────────────────────────
if ( $action === 'view' && $count_id ) {
    $count = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_stock_counts WHERE id=%d", $count_id
    ) );
    if ( ! $count ) wp_die('Inventuuri ei leitud');

    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT sci.*, i.name, i.sku, i.unit, i.category
         FROM {$wpdb->prefix}vesho_stock_count_items sci
         JOIN {$wpdb->prefix}vesho_inventory i ON i.id = sci.inventory_id
         WHERE sci.stock_count_id = %d
         ORDER BY i.category ASC, i.name ASC",
        $count_id
    ) );

    $total   = count($items);
    $counted = count(array_filter($items, fn($r) => $r->counted_qty !== null));
    $diffs   = count(array_filter($items, fn($r) => $r->counted_qty !== null && (float)$r->counted_qty !== (float)$r->expected_qty));
    ?>
    <div class="crm-wrap">
    <div class="crm-page-header">
        <div class="crm-page-header__logo">📦</div>
        <div class="crm-page-header__body">
            <h1 class="crm-page-header__title"><?php echo esc_html($count->name); ?></h1>
            <p class="crm-page-header__subtitle">
                <?php echo $counted; ?>/<?php echo $total; ?> loendatud
                <?php if ($diffs) echo ' · <span style="color:#ef4444">' . $diffs . ' erinevust</span>'; ?>
            </p>
        </div>
        <div class="crm-page-header__actions">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-stockcount'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">← Tagasi</a>
            <?php if ( $count->status !== 'finalized' ) : ?>
            <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=vesho-crm-stockcount&action=finalize&count_id='.$count_id), 'vesho_finalize_stockcount_'.$count_id ); ?>"
               class="crm-btn crm-btn-sm" style="background:#16a34a;color:#fff"
               onclick="return confirm('Kinnitad inventuuri ja uuendad laosaldod?')">✅ Kinnita inventuur</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( isset($_GET['msg']) ) : ?>
    <div class="crm-alert crm-alert-success"><?php echo ['saved'=>'Salvestatud!','finalized'=>'Inventuur kinnitatud! Laosaldod uuendatud.'][$_GET['msg']] ?? 'OK'; ?></div>
    <?php endif; ?>

    <?php if ( $count->status === 'finalized' ) : ?>
    <div class="crm-alert" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a">⚠️ See inventuur on kinnitatud. Laosaldod on uuendatud.</div>
    <?php endif; ?>

    <?php if ( ! empty($items) ) : ?>
    <form method="POST" action="<?php echo admin_url('admin.php'); ?>">
        <?php wp_nonce_field('vesho_save_count_' . $count_id); ?>
        <input type="hidden" name="page" value="vesho-crm-stockcount">
        <input type="hidden" name="action" value="save_count">
        <input type="hidden" name="count_id" value="<?php echo $count_id; ?>">
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">Laonimestik</span>
            <?php if ( $count->status !== 'finalized' ) : ?>
            <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm">💾 Salvesta loendused</button>
            <?php endif; ?>
        </div>
        <table class="crm-table">
            <thead><tr>
                <th>Nimetus</th><th>SKU</th><th>Kategooria</th><th>Ühik</th>
                <th style="text-align:right">Oodatud</th>
                <th style="text-align:right">Loendatud</th>
                <th style="text-align:right">Vahe</th>
            </tr></thead>
            <tbody>
            <?php
            $cur_cat = null;
            foreach ( $items as $item ) :
                if ($item->category !== $cur_cat) {
                    $cur_cat = $item->category;
                    ?>
                    <tr style="background:#f8fafc"><td colspan="7" style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;padding:6px 12px"><?php echo esc_html($cur_cat ?: 'Kategooriata'); ?></td></tr>
                    <?php
                }
                $diff = $item->counted_qty !== null ? (float)$item->counted_qty - (float)$item->expected_qty : null;
                $row_style = '';
                if ($diff !== null && $diff != 0) $row_style = 'background:#fff7ed';
            ?>
            <tr style="<?php echo $row_style; ?>">
                <td><?php echo esc_html($item->name); ?></td>
                <td style="color:#94a3b8;font-size:12px"><?php echo esc_html($item->sku ?: '—'); ?></td>
                <td><?php echo esc_html($item->category ?: '—'); ?></td>
                <td><?php echo esc_html($item->unit); ?></td>
                <td style="text-align:right"><?php echo number_format((float)$item->expected_qty, 2, ',', ' '); ?></td>
                <td style="text-align:right">
                    <?php if ($count->status === 'finalized') : ?>
                        <?php echo $item->counted_qty !== null ? number_format((float)$item->counted_qty, 2, ',', ' ') : '–'; ?>
                    <?php else : ?>
                    <input type="number" name="counted[<?php echo $item->id; ?>]"
                           value="<?php echo $item->counted_qty !== null ? esc_attr((float)$item->counted_qty) : ''; ?>"
                           step="0.01" placeholder="–"
                           style="width:80px;padding:3px 6px;border:1px solid #cbd5e1;border-radius:4px;text-align:right;font-size:13px">
                    <?php endif; ?>
                </td>
                <td style="text-align:right">
                    <?php if ($diff !== null) : ?>
                    <span style="color:<?php echo $diff == 0 ? '#16a34a' : ($diff > 0 ? '#0284c7' : '#dc2626'); ?>;font-weight:600">
                        <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', ' '); ?>
                    </span>
                    <?php else : ?>
                    <span style="color:#94a3b8">–</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ( $count->status !== 'finalized' ) : ?>
        <div style="padding:12px 16px;text-align:right">
            <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta loendused</button>
        </div>
        <?php endif; ?>
    </div>
    </form>
    <?php else : ?>
    <div class="crm-card"><div class="crm-empty">Laos pole ühtegi kaupa.</div></div>
    <?php endif; ?>
    </div>
    <?php
    return;
}

// ── List view ─────────────────────────────────────────────────────────────────
$counts = $wpdb->get_results(
    "SELECT sc.*,
        COUNT(sci.id) as item_count,
        SUM(CASE WHEN sci.counted_qty IS NOT NULL THEN 1 ELSE 0 END) as counted_count,
        SUM(CASE WHEN sci.counted_qty IS NOT NULL AND ROUND(sci.counted_qty,2) != ROUND(sci.expected_qty,2) THEN 1 ELSE 0 END) as diff_count
     FROM {$wpdb->prefix}vesho_stock_counts sc
     LEFT JOIN {$wpdb->prefix}vesho_stock_count_items sci ON sci.stock_count_id = sc.id
     GROUP BY sc.id ORDER BY sc.created_at DESC LIMIT 50"
);
?>
<div class="crm-wrap">
<div class="crm-page-header">
    <div class="crm-page-header__logo">📦</div>
    <div class="crm-page-header__body">
        <h1 class="crm-page-header__title">Inventuur</h1>
        <p class="crm-page-header__subtitle">Laoseisude kontroll ja korrigeerimine</p>
    </div>
    <div class="crm-page-header__actions">
        <button onclick="document.getElementById('new-count-form').classList.toggle('hidden')" class="crm-btn crm-btn-primary crm-btn-sm">+ Uus inventuur</button>
    </div>
</div>

<?php if ( isset($_GET['msg']) ) : ?>
<div class="crm-alert crm-alert-success"><?php echo ['finalized'=>'Inventuur kinnitatud! Laosaldod uuendatud.'][$_GET['msg']] ?? 'Salvestatud!'; ?></div>
<?php endif; ?>

<div id="new-count-form" class="crm-card hidden" style="margin-bottom:20px">
    <div class="crm-card-header"><span class="crm-card-title">Uus inventuur</span></div>
    <div style="padding:20px">
    <form method="POST" action="<?php echo admin_url('admin.php'); ?>">
        <?php wp_nonce_field('vesho_create_stockcount'); ?>
        <input type="hidden" name="page" value="vesho-crm-stockcount">
        <input type="hidden" name="action" value="create">
        <div style="display:flex;gap:12px;align-items:flex-end">
            <div style="flex:1">
                <label class="crm-form-label">Inventuuri nimi</label>
                <input class="crm-form-input" type="text" name="count_name" value="Inventuur <?php echo date('d.m.Y'); ?>" required>
            </div>
            <button type="submit" class="crm-btn crm-btn-primary">📸 Loo inventuur (snapshot)</button>
        </div>
        <p style="color:#64748b;font-size:12px;margin-top:8px">Luuakse hetke laoseisust snapshot. Saad siis igale kaubale loendatud koguse sisestada.</p>
    </form>
    </div>
</div>
<style>.hidden{display:none!important}</style>

<div class="crm-card">
    <div class="crm-card-header">
        <span class="crm-card-title">Inventuurid <span class="crm-count">(<?php echo count($counts); ?>)</span></span>
    </div>
    <?php if ( empty($counts) ) : ?>
    <div class="crm-empty">Inventuure pole tehtud.</div>
    <?php else : ?>
    <table class="crm-table">
        <thead><tr>
            <th>Nimi</th><th>Staatus</th><th>Kaupasid</th><th>Loendatud</th><th>Erinevusi</th><th>Loodud</th><th class="td-actions">Toimingud</th>
        </tr></thead>
        <tbody>
        <?php foreach ( $counts as $c ) :
            $pct = $c->item_count > 0 ? round(100 * $c->counted_count / $c->item_count) : 0;
        ?>
        <tr>
            <td style="font-weight:600"><?php echo esc_html($c->name); ?></td>
            <td>
                <?php if ($c->status === 'finalized') : ?>
                <span class="crm-badge badge-success">Kinnitatud</span>
                <?php else : ?>
                <span class="crm-badge badge-blue">Aktiivne</span>
                <?php endif; ?>
            </td>
            <td><?php echo (int)$c->item_count; ?></td>
            <td>
                <?php echo (int)$c->counted_count; ?> / <?php echo (int)$c->item_count; ?>
                <?php if ($c->item_count > 0) : ?>
                <div style="width:80px;height:4px;background:#e2e8f0;border-radius:2px;margin-top:3px;display:inline-block;vertical-align:middle">
                    <div style="width:<?php echo $pct; ?>%;height:100%;background:#00b4c8;border-radius:2px"></div>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($c->diff_count > 0) : ?>
                <span style="color:#dc2626;font-weight:600"><?php echo (int)$c->diff_count; ?></span>
                <?php else : ?>
                <span style="color:#16a34a">0</span>
                <?php endif; ?>
            </td>
            <td><?php echo vesho_crm_format_date($c->created_at); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-stockcount&action=view&count_id='.$c->id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Ava</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>
