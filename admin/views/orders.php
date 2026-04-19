<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action   = isset($_GET['action'])   ? sanitize_text_field($_GET['action'])  : '';
$order_id = isset($_GET['order_id']) ? absint($_GET['order_id'])              : 0;
$search   = isset($_GET['s'])        ? sanitize_text_field($_GET['s'])        : '';
$fstatus  = isset($_GET['status'])   ? sanitize_text_field($_GET['status'])  : '';

$all_clients   = $wpdb->get_results("SELECT id, name, email FROM {$wpdb->prefix}vesho_clients ORDER BY name ASC");
$all_inventory = $wpdb->get_results("SELECT id, name, sku, unit, sell_price, shop_price, quantity FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND (shop_price > 0 OR sell_price > 0) ORDER BY name ASC");

$statuses = [
    'pending_payment'  => ['Makse ootel',      '#f59e0b', '#fef9c3'],
    'pending'          => ['Uus tellimus',      '#6b7280', '#f3f4f6'],
    'processing'       => ['Komplekteerimisel','#8b5cf6', '#ede9fe'],
    'confirmed'        => ['Valmis saatmiseks','#3b82f6', '#dbeafe'],
    'shipped'          => ['Saadetud',         '#0ea5e9', '#e0f2fe'],
    'completed'        => ['Täidetud',         '#10b981', '#dcfce7'],
    'cancelled'        => ['Tühistatud',       '#6b7280', '#f3f4f6'],
    'returned'         => ['Tagastatud',       '#f97316', '#ffedd5'],
    // Legacy fallbacks
    'new'              => ['Uus (vana)',        '#ef4444', '#fee2e2'],
    'picking'          => ['Komplekt. (vana)', '#f59e0b', '#fef9c3'],
    'ready'            => ['Valmis (vana)',     '#3b82f6', '#dbeafe'],
    'fulfilled'        => ['Täidetud (vana)',   '#10b981', '#dcfce7'],
];

// ── Detail / edit view ───────────────────────────────────────────────────────
$edit = null;
$edit_items = [];
if ( ($action === 'edit' || $action === 'view') && $order_id ) {
    $edit = $wpdb->get_row($wpdb->prepare(
        "SELECT o.*, c.name as client_name, c.email as client_email
         FROM {$wpdb->prefix}vesho_shop_orders o
         LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=o.client_id
         WHERE o.id=%d", $order_id
    ));
    if ( $edit ) {
        $edit_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d ORDER BY id ASC",
            $order_id
        ));
    }
}

// ── List query ───────────────────────────────────────────────────────────────
$where = '1=1';
if ( $fstatus ) { $where .= $wpdb->prepare(' AND o.status=%s', $fstatus); }
if ( $search  ) { $where .= $wpdb->prepare(
    ' AND (o.order_number LIKE %s OR o.guest_name LIKE %s OR o.guest_email LIKE %s OR c.name LIKE %s)',
    '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%',
    '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'
); }

$orders = $wpdb->get_results(
    "SELECT o.*, c.name as client_name
     FROM {$wpdb->prefix}vesho_shop_orders o
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=o.client_id
     WHERE $where ORDER BY o.created_at DESC LIMIT 300"
);
$total     = count($orders);
$new_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE status IN ('pending_payment','pending','new')");

function vesho_so_badge($status, $statuses) {
    $s = $statuses[$status] ?? ['?', '#6b7280', '#f3f4f6'];
    return '<span style="display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;background:'.$s[2].';color:'.$s[1].'">'.esc_html($s[0]).'</span>';
}
?>
<div class="crm-wrap">
<h1 class="crm-page-title">🛒 E-poe tellimused <span class="crm-count">(<?php echo $total; ?>)</span>
    <?php if ($new_count > 0): ?>
    <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&status=pending'); ?>"
       style="margin-left:12px;font-size:13px;padding:3px 10px;background:#10b981;color:#fff;border-radius:12px;text-decoration:none;font-weight:600">
        🔔 <?php echo $new_count; ?> ootel
    </a>
    <?php endif; ?>
</h1>

<?php if (isset($_GET['msg'])):
    $msgs=[
        'added'           => 'Tellimus lisatud!',
        'updated'         => 'Tellimus uuendatud!',
        'deleted'         => 'Tellimus kustutatud!',
        'status'          => 'Staatus uuendatud!',
        'sent'            => 'Saadetud töötajatele!',
        'return_approved' => '✅ Tagastus kinnitatud, tagasimakse väljastatud!',
        'return_rejected' => 'Tagastustaotlus lükati tagasi.',
    ];
    $is_err = in_array($_GET['msg'], ['err']);
    $cls = $is_err ? 'crm-alert crm-alert-error' : 'crm-alert crm-alert-success';
?>
<div class="<?php echo $cls; ?>"><?php echo esc_html($msgs[$_GET['msg']]??'Salvestatud!'); ?></div>
<?php endif; ?>

<?php /* ─── DETAIL / EDIT VIEW ─────────────────────────────────────────── */ ?>
<?php if ( ($action === 'edit' || $action === 'add') && ($edit || $action === 'add') ): ?>
<div class="crm-card">
    <div class="crm-card-header">
        <span class="crm-card-title"><?php echo $action==='edit' ? 'Muuda tellimust #'.esc_html($edit->order_number) : 'Lisa uus tellimus'; ?></span>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
    </div>
    <div style="padding:20px">
    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" id="shop-order-form">
        <?php wp_nonce_field('vesho_save_shop_order'); ?>
        <input type="hidden" name="action" value="vesho_save_shop_order">
        <?php if ($edit): ?><input type="hidden" name="order_id" value="<?php echo $edit->id; ?>"><?php endif; ?>

        <div class="crm-form-grid">
            <div class="crm-form-group">
                <label class="crm-form-label">Klient (kui registreeritud)</label>
                <select class="crm-form-select" name="client_id" id="so-client-sel">
                    <option value="">— Külaline / käsitsi —</option>
                    <?php foreach ($all_clients as $c): ?>
                    <option value="<?php echo $c->id; ?>" <?php selected($edit->client_id??0,$c->id); ?> data-email="<?php echo esc_attr($c->email); ?>" data-name="<?php echo esc_attr($c->name); ?>">
                        <?php echo esc_html($c->name.' ('.$c->email.')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Staatus</label>
                <select class="crm-form-select" name="status">
                    <?php foreach ($statuses as $v=>[$l,$c,$bg]): ?>
                    <option value="<?php echo $v; ?>" <?php selected($edit->status??'new',$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Nimi</label>
                <input class="crm-form-input" type="text" name="guest_name" value="<?php echo esc_attr($edit->guest_name??''); ?>" id="so-name">
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">E-post</label>
                <input class="crm-form-input" type="email" name="guest_email" value="<?php echo esc_attr($edit->guest_email??''); ?>" id="so-email">
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Telefon</label>
                <input class="crm-form-input" type="text" name="guest_phone" value="<?php echo esc_attr($edit->guest_phone??''); ?>">
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Tarneviis</label>
                <select class="crm-form-select" name="shipping_method">
                    <option value="pickup"     <?php selected($edit->shipping_method??'pickup','pickup'); ?>>🏪 Järeltulek</option>
                    <option value="courier"    <?php selected($edit->shipping_method??'','courier'); ?>>🚚 Kuller</option>
                    <option value="parcelshop" <?php selected($edit->shipping_method??'','parcelshop'); ?>>📦 Pakiautomaat</option>
                </select>
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Tarnekulud (€)</label>
                <input class="crm-form-input" type="number" step="0.01" name="shipping_price" value="<?php echo esc_attr($edit->shipping_price??'0'); ?>">
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Jälgimisnumber</label>
                <input class="crm-form-input" type="text" name="tracking_number" value="<?php echo esc_attr($edit->tracking_number??''); ?>">
            </div>
            <div class="crm-form-group crm-form-full">
                <label class="crm-form-label">Tarneaadress</label>
                <input class="crm-form-input" type="text" name="shipping_address" value="<?php echo esc_attr($edit->shipping_address??''); ?>">
            </div>
            <div class="crm-form-group crm-form-full">
                <label class="crm-form-label">Märkused</label>
                <textarea class="crm-form-textarea" name="notes"><?php echo esc_textarea($edit->notes??''); ?></textarea>
            </div>
        </div>

        <!-- ── Tooted ──────────────────────────────────────────────────── -->
        <div style="margin:20px 0 8px;font-size:14px;font-weight:600;color:#1a2a38">📦 Tooted</div>
        <div id="so-items-wrap">
        <?php
        $items_to_show = !empty($edit_items) ? $edit_items : [null];
        foreach ($items_to_show as $i => $item):
        ?>
        <div class="so-item-row" style="display:grid;grid-template-columns:2fr 80px 100px 100px 40px;gap:8px;align-items:center;margin-bottom:8px">
            <select class="crm-form-select so-product-sel" name="item_inventory_id[]"
                    style="padding:7px 10px;font-size:13px">
                <option value="">— Vali toode —</option>
                <?php foreach ($all_inventory as $p):
                    $pp = $p->shop_price ?? $p->sell_price;
                    $sel = $item ? ($item->inventory_id == $p->id ? 'selected' : '') : '';
                ?>
                <option value="<?php echo $p->id; ?>" data-price="<?php echo $pp; ?>"
                        data-name="<?php echo esc_attr($p->name.($p->sku?' ['.$p->sku.']':'')); ?>"
                        <?php echo $sel; ?>><?php echo esc_html($p->name.($p->sku?' ['.$p->sku.']':'')); ?> — <?php echo number_format($pp,2,',','.'); ?>€</option>
                <?php endforeach; ?>
            </select>
            <input class="crm-form-input so-item-name" type="text" name="item_name[]"
                   placeholder="Nimi" style="padding:7px 10px;font-size:13px"
                   value="<?php echo esc_attr($item->name??''); ?>">
            <input class="crm-form-input so-item-qty" type="number" name="item_qty[]" min="0.001" step="0.001"
                   placeholder="Kogus" style="padding:7px 10px;font-size:13px"
                   value="<?php echo esc_attr($item->quantity??'1'); ?>">
            <input class="crm-form-input so-item-price" type="number" name="item_price[]" min="0" step="0.01"
                   placeholder="Hind €" style="padding:7px 10px;font-size:13px"
                   value="<?php echo esc_attr($item->unit_price??''); ?>">
            <button type="button" class="crm-btn crm-btn-sm so-remove-item"
                    style="background:#fee2e2;color:#b91c1c;border:none;cursor:pointer;padding:6px 10px">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" id="so-add-item" class="crm-btn crm-btn-outline crm-btn-sm" style="margin-top:4px">+ Lisa toode</button>

        <div style="margin-top:16px;text-align:right;font-size:14px;color:#1a2a38">
            Kokku (automaatne): <strong id="so-total-preview">—</strong>
        </div>

        <div class="crm-form-actions">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
            <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
        </div>
    </form>
    </div>
</div>

<?php /* ─── VIEW ONLY (detail page) ─────────────────────────────────── */ ?>
<?php elseif ( $action === 'view' && $edit ): ?>
<?php
$sm = ['pickup'=>'🏪 Järeltulek','courier'=>'🚚 Kuller','parcelshop'=>'📦 Pakiautomaat'];
$display_name = $edit->client_name ?: $edit->guest_name ?: '—';
?>
<div class="crm-card" style="margin-bottom:16px">
    <div class="crm-card-header">
        <span class="crm-card-title">Tellimus #<?php echo esc_html($edit->order_number); ?>
            &nbsp;<?php echo vesho_so_badge($edit->status, $statuses); ?>
        </span>
        <div style="display:flex;gap:8px">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&action=edit&order_id='.$edit->id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">✏️ Muuda</a>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:#6b8599;margin-bottom:6px">Klient</div>
            <div style="font-weight:600"><?php echo esc_html($display_name); ?></div>
            <?php if ($edit->guest_email): ?><div style="font-size:13px;color:#6b8599"><?php echo esc_html($edit->guest_email); ?></div><?php endif; ?>
            <?php if ($edit->guest_phone): ?><div style="font-size:13px;color:#6b8599"><?php echo esc_html($edit->guest_phone); ?></div><?php endif; ?>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:#6b8599;margin-bottom:6px">Tarne</div>
            <div style="font-weight:600"><?php echo $sm[$edit->shipping_method] ?? esc_html($edit->shipping_method); ?></div>
            <?php if ($edit->shipping_address): ?><div style="font-size:13px;color:#6b8599"><?php echo esc_html($edit->shipping_address); ?></div><?php endif; ?>
            <?php if ($edit->tracking_number): ?><div style="margin-top:4px;font-size:13px"><span style="background:#ede9fe;color:#7c3aed;padding:2px 8px;border-radius:8px;font-weight:600">📍 <?php echo esc_html($edit->tracking_number); ?></span></div><?php endif; ?>
        </div>
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:#6b8599;margin-bottom:6px">Maksed</div>
            <div style="font-size:18px;font-weight:700;color:#10b981"><?php echo number_format($edit->total,2,',','.'); ?> €</div>
            <?php if ($edit->shipping_price > 0): ?><div style="font-size:12px;color:#6b8599">sh tarne <?php echo number_format($edit->shipping_price,2,',','.'); ?> €</div><?php endif; ?>
            <?php if ($edit->payment_method): ?><div style="font-size:12px;color:#6b8599;margin-top:4px">Makse: <?php echo esc_html($edit->payment_method); ?><?php echo $edit->paid_at ? ' ('.date('d.m.Y',strtotime($edit->paid_at)).')' : ' (ootel)'; ?></div><?php endif; ?>
        </div>
    </div>
    <?php if ($edit->notes): ?>
    <div style="padding:0 20px 16px;font-size:13px;color:#6b8599"><strong>Märkused:</strong> <?php echo esc_html($edit->notes); ?></div>
    <?php endif; ?>
</div>

<?php
// ── Osaline komplekteerimine — tagasimakse bänner ────────────────────────────
$refund_pending = (float)( $edit->refund_pending_amount ?? 0 );
if ( $refund_pending > 0 ):
?>
<div id="partial-refund-banner" style="background:#fff7ed;border:2px solid #f97316;border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div>
        <div style="font-weight:700;color:#c2410c;font-size:14px">⚠ Osaline komplekteerimine — tagasimakse ootel: <?php echo number_format($refund_pending,2,',','.'); ?> €</div>
        <div style="font-size:12px;color:#7c3b1e;margin-top:3px">Mõned tooted komplekteeriti osaliselt. Vajuta et väljastada tagasimakse kliendile.</div>
    </div>
    <button id="btn-issue-refund" onclick="issuePartialRefund(<?php echo $edit->id; ?>,<?php echo $refund_pending; ?>)"
            style="background:#ea580c;color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap">
        💸 Väljasta tagasimakse <?php echo number_format($refund_pending,2,',','.'); ?> €
    </button>
</div>
<?php endif; ?>

<?php
// ── Tagastustaotluse bänner ──────────────────────────────────────────────────
if ( $edit->status === 'return_requested' ):
?>
<div style="background:#fff7ed;border:2px solid #f97316;border-radius:10px;padding:16px 20px;margin-bottom:16px">
    <div style="font-weight:700;color:#c2410c;font-size:15px;margin-bottom:10px">📦 Klient on esitanud tagastustaotluse</div>
    <?php if ($edit->return_reason): ?>
    <div style="font-size:13px;margin-bottom:6px"><strong>Põhjus:</strong> <?php echo esc_html($edit->return_reason); ?></div>
    <?php endif; ?>
    <?php if (!empty($edit->return_description)): ?>
    <div style="font-size:13px;margin-bottom:6px"><strong>Kirjeldus:</strong> <?php echo esc_html($edit->return_description); ?></div>
    <?php endif; ?>
    <?php if (!empty($edit->return_photo_url)): ?>
    <div style="margin-bottom:12px">
        <a href="<?php echo esc_url($edit->return_photo_url); ?>" target="_blank">
            <img src="<?php echo esc_url($edit->return_photo_url); ?>" alt="Tagastuse foto" style="max-width:240px;max-height:160px;border-radius:6px;border:1px solid #fed7aa;cursor:pointer">
        </a>
    </div>
    <?php endif; ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <!-- Approve return with disposition -->
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-flex;gap:6px;align-items:center">
            <?php wp_nonce_field('vesho_approve_return'); ?>
            <input type="hidden" name="action" value="vesho_approve_return">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <select name="disposition" style="padding:7px 10px;border:1px solid #fed7aa;border-radius:6px;font-size:13px;background:#fff">
                <option value="stock">📦 Tagasta lattu</option>
                <option value="used">🔧 Märgi kasutatud</option>
                <option value="writeoff">🗑️ Mahakandmine</option>
            </select>
            <button type="submit" class="crm-btn crm-btn-primary" style="background:#16a34a"
                    onclick="return confirm('Kinnita tagastus ja väljasta tagasimakse?')">
                ✓ Kinnita tagastus
            </button>
        </form>
        <!-- Reject return -->
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
            <?php wp_nonce_field('vesho_reject_return'); ?>
            <input type="hidden" name="action" value="vesho_reject_return">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <button type="submit" class="crm-btn crm-btn-outline" style="color:#dc2626;border-color:#dc2626"
                    onclick="return confirm('Lükka tagastustaotlus tagasi?')">
                ✕ Lükka tagasi
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
// ── Käsitsi tagasimakse nupp makstud tellimustele ────────────────────────────
$can_refund = !empty($edit->paid_at) && !empty($edit->payment_method) && in_array($edit->payment_method, ['stripe','mc','maksekeskus','montonio']);
if ( $can_refund && in_array($edit->status, ['confirmed','shipped','completed','returned','ready','fulfilled']) ):
?>
<div style="margin-bottom:16px;text-align:right">
    <button onclick="openManualRefund(<?php echo $edit->id; ?>)"
            style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;padding:7px 14px;border-radius:8px;font-size:13px;cursor:pointer">
        💸 Tagasimakse kliendile
    </button>
</div>
<?php endif; ?>

<!-- Tooted -->
<div class="crm-card" style="margin-bottom:16px">
    <div class="crm-card-header"><span class="crm-card-title">📦 Tooted</span></div>
    <?php if (empty($edit_items)): ?>
    <div class="crm-empty">Tooteid pole</div>
    <?php else: ?>
    <table class="crm-table">
        <thead><tr><th>Toode</th><th>Kogus</th><th>Ühikuhind</th><th>Kokku</th>
        <?php if (in_array($edit->status,['picking','processing'])): ?><th>Kogutud</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php $subtotal=0; foreach ($edit_items as $it):
            $subtotal += $it->total;
            $picked_qty = $it->picked_qty !== null ? $it->picked_qty : $it->quantity;
        ?>
        <tr>
            <td>
                <strong><?php echo esc_html($it->name); ?></strong>
                <?php if ($it->sku ?? ''): ?><span style="font-size:11px;color:#6b8599">[<?php echo esc_html($it->sku??''); ?>]</span><?php endif; ?>
            </td>
            <td><?php echo rtrim(rtrim(number_format($it->quantity,3,',','.'),'0'),','); ?></td>
            <td><?php echo number_format($it->unit_price,2,',','.'); ?> €</td>
            <td><strong><?php echo number_format($it->total,2,',','.'); ?> €</strong></td>
            <?php if (in_array($edit->status,['picking','processing'])): ?>
            <td>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:flex;gap:6px;align-items:center">
                    <?php wp_nonce_field('vesho_pick_item'); ?>
                    <input type="hidden" name="action" value="vesho_pick_item">
                    <input type="hidden" name="item_id" value="<?php echo $it->id; ?>">
                    <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
                    <input type="number" name="picked_qty" min="0" step="0.001"
                           value="<?php echo $picked_qty; ?>"
                           style="width:70px;padding:4px 6px;border:1px solid #dce3e9;border-radius:6px;font-size:13px">
                    <button type="submit" class="crm-btn crm-btn-sm"
                            style="background:<?php echo $it->picked ? '#dcfce7' : '#f3f4f6'; ?>;color:<?php echo $it->picked ? '#16a34a' : '#374151'; ?>;border:none;cursor:pointer">
                        <?php echo $it->picked ? '✓ Kogutud' : '○ Kogu'; ?>
                    </button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" style="text-align:right;font-weight:600">Tooted kokku:</td><td style="font-weight:600"><?php echo number_format($subtotal,2,',','.'); ?> €</td><?php if(in_array($edit->status,['picking','processing'])): ?><td></td><?php endif; ?></tr>
            <?php if ($edit->shipping_price > 0): ?>
            <tr><td colspan="3" style="text-align:right;color:#6b8599">Tarne:</td><td style="color:#6b8599"><?php echo number_format($edit->shipping_price,2,',','.'); ?> €</td><?php if(in_array($edit->status,['picking','processing'])): ?><td></td><?php endif; ?></tr>
            <?php endif; ?>
            <tr><td colspan="3" style="text-align:right;font-weight:700;font-size:15px">Kokku:</td><td style="font-weight:700;font-size:15px;color:#10b981"><?php echo number_format($edit->total,2,',','.'); ?> €</td><?php if(in_array($edit->status,['picking','processing'])): ?><td></td><?php endif; ?></tr>
        </tfoot>
    </table>
    <?php if (in_array($edit->status, ['picking','processing'])):
        $all_picked = !in_array(0, array_column((array)$edit_items,'picked'));
    ?>
    <div style="padding:12px 16px;border-top:1px solid #e8edf1;display:flex;justify-content:flex-end">
        <?php if (!$all_picked): ?>
        <span style="font-size:13px;color:#f59e0b;margin-right:12px;align-self:center">⚠ Kõik tooted pole veel kogutud</span>
        <?php endif; ?>
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_shop_order_status'); ?>
            <input type="hidden" name="action" value="vesho_shop_order_status">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="crm-btn crm-btn-primary"
                    <?php echo !$all_picked ? 'style="opacity:.5" disabled' : ''; ?>>
                ✓ Märgi Valmis saatmiseks
            </button>
        </form>
    </div>
    <?php endif; ?>
    <?php if (in_array($edit->status, ['confirmed','ready'])): ?>
    <div style="padding:12px 16px;border-top:1px solid #e8edf1;display:flex;justify-content:flex-end;gap:10px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_shop_order_status'); ?>
            <input type="hidden" name="action" value="vesho_shop_order_status">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <input type="hidden" name="status" value="shipped">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="tracking_number" placeholder="Jälgimisnumber (kui kuller/pakiautomaat)"
                       value="<?php echo esc_attr($edit->tracking_number ?? ''); ?>"
                       style="padding:7px 10px;border:1px solid #dce3e9;border-radius:6px;font-size:13px;min-width:220px">
                <button type="submit" class="crm-btn crm-btn-primary">📦 Märgi Saadetud</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <?php if ($edit->status === 'shipped'): ?>
    <div style="padding:12px 16px;border-top:1px solid #e8edf1;display:flex;justify-content:flex-end;gap:10px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
            <?php wp_nonce_field('vesho_shop_order_status'); ?>
            <input type="hidden" name="action" value="vesho_shop_order_status">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <input type="hidden" name="status" value="completed">
            <button type="submit" class="crm-btn crm-btn-primary">✓ Märgi Täidetud</button>
        </form>
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
            <?php wp_nonce_field('vesho_shop_order_status'); ?>
            <input type="hidden" name="action" value="vesho_shop_order_status">
            <input type="hidden" name="order_id" value="<?php echo $edit->id; ?>">
            <input type="hidden" name="status" value="returned">
            <button type="submit" class="crm-btn crm-btn-outline" onclick="return confirm('Märgi tagastatuks?')">📦 Ei võetud vastu</button>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; // end if empty($edit_items) ?>
    </div>
</div>

<?php /* ─── LIST VIEW ───────────────────────────────────────────────────── */ ?>
<?php else: ?>
<div class="crm-card">
    <div class="crm-toolbar" style="flex-wrap:wrap;gap:8px">
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&action=add'); ?>" class="crm-btn crm-btn-primary">+ Uus tellimus</a>

        <!-- Barcode / package scan -->
        <div style="display:flex;align-items:center;gap:6px">
            <input type="text" id="order-barcode-scan"
                   placeholder="📦 Pakikaart / jälgimisnumber..."
                   autocomplete="off"
                   style="padding:7px 10px;border:1px solid #dce3e9;border-radius:8px;font-size:13px;color:#1a2a38;min-width:200px"
                   title="HID skänner või kirjuta käsitsi">
            <button type="button" id="order-camera-scan-btn"
                    style="background:#00b4c8;color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px">
                📷 Skänni
            </button>
        </div>

        <!-- Bulk send to workers -->
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-form" style="display:inline">
            <?php wp_nonce_field('vesho_bulk_shop_orders'); ?>
            <input type="hidden" name="action" value="vesho_bulk_shop_orders">
            <input type="hidden" name="bulk_action" value="send_to_workers">
            <div id="bulk-selected-container"></div>
            <button type="submit" id="bulk-send-btn" class="crm-btn crm-btn-outline crm-btn-sm" style="display:none" onclick="return confirm('Saada valitud tellimused töötajatele?')">▶ Saada töötajatele</button>
        </form>

        <form method="GET" style="display:flex;gap:8px;flex:1">
            <input type="hidden" name="page" value="vesho-crm-orders">
            <select class="crm-form-select" name="status" style="max-width:180px;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
                <option value="">Kõik staatused</option>
                <?php foreach ($statuses as $v=>[$l,$c,$bg]): ?>
                <option value="<?php echo $v; ?>" <?php selected($fstatus,$v); ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
            <input class="crm-search" type="search" name="s" placeholder="Otsi nime, emaili, tellimuse nr..." value="<?php echo esc_attr($search); ?>">
            <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
        </form>
    </div>

    <?php if (empty($orders)): ?>
    <div class="crm-empty">Tellimusi ei leitud.</div>
    <?php else: ?>
    <table class="crm-table">
        <thead><tr>
            <th style="width:32px"><input type="checkbox" id="check-all" title="Vali kõik"></th>
            <th>Nr</th><th>Klient</th><th>Tooted</th><th>Kogusumma</th><th>Tarne</th><th>Staatus</th><th>Kuupäev</th><th class="td-actions">Toimingud</th>
        </tr></thead>
        <tbody>
        <?php
        $shipping_icons = ['pickup'=>'🏪','courier'=>'🚚','parcelshop'=>'📦'];
        foreach ($orders as $o):
            $item_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d",$o->id));
            $display_name = $o->client_name ?: $o->guest_name ?: '—';
        ?>
        <tr>
            <td><input type="checkbox" class="row-check" value="<?php echo $o->id; ?>"
                       <?php echo !in_array($o->status,['pending','new']) ? 'disabled style="opacity:.3"' : ''; ?>></td>
            <td><strong style="color:#1a2a38"><a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&action=view&order_id='.$o->id); ?>" style="color:inherit;text-decoration:none">#<?php echo esc_html($o->order_number); ?></a></strong></td>
            <td>
                <div style="font-weight:500"><?php echo $o->client_id ? '<a href="'.admin_url('admin.php?page=vesho-crm-clients&action=view&client_id='.$o->client_id).'">'.esc_html($display_name).'</a>' : esc_html($display_name); ?></div>
                <?php if ($o->guest_email): ?><div style="font-size:11px;color:#6b8599"><?php echo esc_html($o->guest_email); ?></div><?php endif; ?>
            </td>
            <td style="color:#6b8599"><?php echo $item_count; ?> tk</td>
            <td><strong><?php echo number_format($o->total,2,',','.'); ?> €</strong></td>
            <td><?php echo ($shipping_icons[$o->shipping_method]??'📦'); ?></td>
            <td>
                <?php echo vesho_so_badge($o->status, $statuses); ?>
                <?php if ($o->status === 'shipped'): ?>
                <button type="button" class="crm-btn crm-btn-sm not-received-btn"
                        data-order-id="<?php echo $o->id; ?>"
                        data-nonce="<?php echo wp_create_nonce('vesho_admin_nonce'); ?>"
                        style="background:#fff3cd;color:#856404;border:1px solid #ffc107;cursor:pointer;margin-left:4px;font-size:11px">
                    ↩ Ei võetud vastu
                </button>
                <?php endif; ?>
            </td>
            <td style="color:#6b8599;font-size:12px"><?php echo date('d.m.Y H:i',strtotime($o->created_at)); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&action=view&order_id='.$o->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Vaata">👁️</a>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-orders&action=edit&order_id='.$o->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                <?php if (in_array($o->status, ['pending','new'])): ?>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                    <?php wp_nonce_field('vesho_shop_order_status'); ?>
                    <input type="hidden" name="action" value="vesho_shop_order_status">
                    <input type="hidden" name="order_id" value="<?php echo $o->id; ?>">
                    <input type="hidden" name="status" value="processing">
                    <button type="submit" class="crm-btn crm-btn-sm" style="background:#fef9c3;color:#b45309;border:none;cursor:pointer" title="Saada töötajale komplekteerima">▶ Saada töötajale</button>
                </form>
                <?php endif; ?>
                <?php if (in_array($o->status,['pending_payment','pending','new','processing','picking'])): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_shop_order&order_id='.$o->id),'vesho_delete_shop_order'); ?>"
                   class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta tellimus? Laokogused taastatakse.')">🗑️</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
(function(){
// ── Checkbox bulk select ──────────────────────────────────────────────────
var checkAll = document.getElementById('check-all');
var bulkBtn  = document.getElementById('bulk-send-btn');
var container= document.getElementById('bulk-selected-container');

function updateBulk() {
    var checked = document.querySelectorAll('.row-check:checked');
    if (bulkBtn) bulkBtn.style.display = checked.length ? 'inline-flex' : 'none';
    if (container) {
        container.innerHTML = '';
        checked.forEach(function(c){
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'order_ids[]'; inp.value = c.value;
            container.appendChild(inp);
        });
    }
}

if (checkAll) {
    checkAll.addEventListener('change', function(){
        document.querySelectorAll('.row-check:not([disabled])').forEach(function(c){ c.checked = checkAll.checked; });
        updateBulk();
    });
}
document.querySelectorAll('.row-check').forEach(function(c){ c.addEventListener('change', updateBulk); });

// ── Dynamic product rows ──────────────────────────────────────────────────
var wrap = document.getElementById('so-items-wrap');
var addBtn = document.getElementById('so-add-item');

function newRow() {
    var first = wrap ? wrap.querySelector('.so-item-row') : null;
    if (!first) return;
    var clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(function(i){ i.value=''; });
    clone.querySelectorAll('select').forEach(function(s){ s.selectedIndex=0; });
    bindRow(clone);
    wrap.appendChild(clone);
}

function bindRow(row) {
    var sel = row.querySelector('.so-product-sel');
    if (sel) sel.addEventListener('change', function(){
        var opt = sel.options[sel.selectedIndex];
        var nameI = row.querySelector('.so-item-name');
        var priceI = row.querySelector('.so-item-price');
        if (nameI && opt.dataset.name) nameI.value = opt.dataset.name;
        if (priceI && opt.dataset.price) priceI.value = parseFloat(opt.dataset.price).toFixed(2);
        calcTotal();
    });
    row.querySelector('.so-remove-item')?.addEventListener('click', function(){
        if (wrap.querySelectorAll('.so-item-row').length > 1) { row.remove(); calcTotal(); }
    });
    row.querySelectorAll('.so-item-qty,.so-item-price').forEach(function(i){ i.addEventListener('input', calcTotal); });
}

function calcTotal() {
    var total = 0;
    document.querySelectorAll('.so-item-row').forEach(function(row){
        var q = parseFloat(row.querySelector('.so-item-qty')?.value||0);
        var p = parseFloat(row.querySelector('.so-item-price')?.value||0);
        total += q * p;
    });
    var shipping = parseFloat(document.querySelector('[name=shipping_price]')?.value||0);
    total += shipping;
    var el = document.getElementById('so-total-preview');
    if (el) el.textContent = total.toFixed(2).replace('.',',') + ' €';
}

if (wrap) { wrap.querySelectorAll('.so-item-row').forEach(bindRow); calcTotal(); }
if (addBtn) addBtn.addEventListener('click', newRow);

// Shipping price changes
document.querySelector('[name=shipping_price]')?.addEventListener('input', calcTotal);

// ── Client autofill ───────────────────────────────────────────────────────
var clientSel = document.getElementById('so-client-sel');
if (clientSel) clientSel.addEventListener('change', function(){
    var opt = clientSel.options[clientSel.selectedIndex];
    var nameI = document.getElementById('so-name');
    var emailI = document.getElementById('so-email');
    if (nameI && opt.dataset.name) nameI.value = opt.dataset.name;
    if (emailI && opt.dataset.email) emailI.value = opt.dataset.email;
});

// ── "Ei võetud vastu" confirm dialog ────────────────────────────────────
document.querySelectorAll('.not-received-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var orderId = btn.dataset.orderId;
        var nonce   = btn.dataset.nonce;

        // Build custom confirm dialog
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center';

        var box = document.createElement('div');
        box.style.cssText = 'background:#fff;border-radius:10px;padding:28px 24px;max-width:400px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)';
        box.innerHTML = '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#1a2a38">Kinnita, et pakk ei v&otilde;etud vastu? See m&auml;rgib tellimuse tagastatuvaks ja algatab tagasimakse.</p>'
            + '<div style="display:flex;gap:10px;justify-content:flex-end">'
            + '<button id="nr-cancel" class="crm-btn crm-btn-outline crm-btn-sm">T&uuml;hista</button>'
            + '<button id="nr-confirm" class="crm-btn crm-btn-sm" style="background:#f59e0b;color:#fff;border:none;cursor:pointer">Ei v&otilde;etud vastu</button>'
            + '</div>';

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        document.getElementById('nr-cancel').addEventListener('click', function(){ document.body.removeChild(overlay); });
        overlay.addEventListener('click', function(e){ if(e.target===overlay){ document.body.removeChild(overlay); } });

        document.getElementById('nr-confirm').addEventListener('click', function(){
            var confirmBtn = this;
            confirmBtn.disabled = true;
            confirmBtn.textContent = '...';

            var fd = new FormData();
            fd.append('action',   'vesho_order_not_received');
            fd.append('nonce',    nonce);
            fd.append('order_id', orderId);

            fetch(ajaxurl, {method:'POST', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    document.body.removeChild(overlay);
                    if(d.success){
                        btn.closest('tr').style.opacity = '.5';
                        btn.textContent = 'Tagastatud';
                        btn.disabled = true;
                        // Refresh status badge in the same row
                        var badgeCell = btn.closest('td');
                        if(badgeCell){
                            var badge = badgeCell.querySelector('span');
                            if(badge){ badge.textContent='Tagastatud'; badge.style.background='#ffedd5'; badge.style.color='#f97316'; }
                        }
                    } else {
                        alert('Viga: ' + (d.data || 'Tundmatu viga'));
                    }
                })
                .catch(function(){ document.body.removeChild(overlay); alert('Ühenduse viga'); });
        });
    });
});

// ── Partial refund helpers ────────────────────────────────────────────────────
var veshoRefundNonce = '<?php echo wp_create_nonce("vesho_portal_nonce"); ?>';

function issuePartialRefund(orderId, amount) {
    if (!confirm('Väljasta tagasimakse ' + amount.toFixed(2).replace('.',',') + ' €?')) return;
    var btn = document.getElementById('btn-issue-refund');
    if (btn) { btn.disabled = true; btn.textContent = 'Töötlen...'; }
    var fd = new FormData();
    fd.append('action',   'vesho_order_issue_refund');
    fd.append('nonce',    veshoRefundNonce);
    fd.append('order_id', orderId);
    fetch(ajaxurl, {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) {
                var banner = document.getElementById('partial-refund-banner');
                if (banner) banner.remove();
                alert('✅ ' + (d.data && d.data.message ? d.data.message : 'Tagasimakse tehtud'));
            } else {
                var msg = (d.data && d.data.message) ? d.data.message : 'Viga';
                alert('❌ ' + msg);
                if (btn) { btn.disabled = false; btn.textContent = '💸 Väljasta tagasimakse'; }
            }
        })
        .catch(function(){ alert('Ühenduse viga'); if(btn){btn.disabled=false;} });
}

function openManualRefund(orderId) {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = '<div style="background:#fff;border-radius:12px;padding:28px;min-width:320px;max-width:440px;box-shadow:0 8px 32px rgba(0,0,0,.15)">'
        + '<h3 style="margin:0 0 16px;color:#0d1f2d;font-size:16px">💸 Tagasimakse kliendile</h3>'
        + '<p style="font-size:13px;color:#6b8599;margin:0 0 14px">Sisesta tagastatav summa (€). Stripe ja Maksekeskus töödeldakse automaatselt. Montonio puhul tee tagastus käsitsi Montonio halduspaneelil.</p>'
        + '<input id="mr-amount" type="number" min="0.01" step="0.01" placeholder="Summa (€)" style="width:100%;padding:9px 12px;border:1px solid #dce3e9;border-radius:8px;font-size:15px;box-sizing:border-box;margin-bottom:14px">'
        + '<div id="mr-msg" style="font-size:13px;color:#dc2626;margin-bottom:10px;display:none"></div>'
        + '<div style="display:flex;gap:10px;justify-content:flex-end">'
        + '<button id="mr-cancel" style="padding:8px 18px;border:1px solid #dce3e9;border-radius:8px;background:#fff;cursor:pointer;font-size:14px">Tühista</button>'
        + '<button id="mr-confirm" style="padding:8px 18px;background:#ea580c;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:700">Väljasta tagasimakse</button>'
        + '</div></div>';
    document.body.appendChild(overlay);
    document.getElementById('mr-cancel').addEventListener('click', function(){ document.body.removeChild(overlay); });
    overlay.addEventListener('click', function(e){ if(e.target===overlay) document.body.removeChild(overlay); });
    document.getElementById('mr-confirm').addEventListener('click', function(){
        var amount = parseFloat(document.getElementById('mr-amount').value);
        if (!amount || amount <= 0) { var m=document.getElementById('mr-msg'); m.style.display='block'; m.textContent='Sisesta kehtiv summa'; return; }
        var btn = document.getElementById('mr-confirm');
        btn.disabled = true; btn.textContent = 'Töötlen...';
        var fd = new FormData();
        fd.append('action',   'vesho_order_manual_refund');
        fd.append('nonce',    veshoRefundNonce);
        fd.append('order_id', orderId);
        fd.append('amount',   amount);
        fetch(ajaxurl, {method:'POST', body:fd})
            .then(function(r){ return r.json(); })
            .then(function(d){
                document.body.removeChild(overlay);
                if (d.success) {
                    alert('✅ ' + (d.data && d.data.message ? d.data.message : 'Tagasimakse tehtud'));
                    // Remove pending banner if it matches
                    var banner = document.getElementById('partial-refund-banner');
                    if (banner) banner.remove();
                } else {
                    var msg = (d.data && d.data.message) ? d.data.message : 'Viga';
                    alert('❌ ' + msg);
                }
            })
            .catch(function(){ document.body.removeChild(overlay); alert('Ühenduse viga'); });
    });
}

// ── Package barcode / tracking number scanner ────────────────────────────────
var ORDERS_URL = '<?php echo admin_url('admin.php?page=vesho-crm-orders&s='); ?>';

function doOrderSearch(val) {
    val = (val || '').trim();
    if (val.length > 0) {
        window.location.href = ORDERS_URL + encodeURIComponent(val);
    }
}

// HID barcode scanner (text input — fires Enter / change)
var barcodeInput = document.getElementById('order-barcode-scan');
if (barcodeInput) {
    barcodeInput.addEventListener('change', function() { doOrderSearch(this.value); });
    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); doOrderSearch(this.value); }
    });
}

// Camera scan button
var camBtn = document.getElementById('order-camera-scan-btn');
if (camBtn) {
    camBtn.addEventListener('click', function() {
        if (typeof window.VeshoScanner === 'undefined') {
            alert('Skänner laadib... Proovi uuesti sekundi pärast.');
            return;
        }
        window.VeshoScanner.open({
            title: '📦 Skänni pakikaart / jälgimisnumber',
            autoConfirm: true,
            manualInput: true,
            wide: true,
            onScan: function(code) { doOrderSearch(code); }
        });
    });
}

})();
</script>
