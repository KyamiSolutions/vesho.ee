<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action = sanitize_text_field($_GET['action'] ?? '');
$po_id  = absint($_GET['po_id'] ?? 0);
$edit   = null;
$items  = [];

if (in_array($action, ['edit','view']) && $po_id) {
    $edit  = $wpdb->get_row($wpdb->prepare(
        "SELECT po.*, s.name AS supplier_name FROM {$wpdb->prefix}vesho_purchase_orders po
         LEFT JOIN {$wpdb->prefix}vesho_suppliers s ON s.id=po.supplier_id
         WHERE po.id=%d", $po_id
    ));
    if ($edit) {
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT poi.*, inv.name AS inv_name FROM {$wpdb->prefix}vesho_purchase_order_items poi
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id=poi.inventory_id
             WHERE poi.purchase_order_id=%d ORDER BY poi.id ASC", $po_id
        ));
    }
}

$suppliers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_suppliers WHERE active=1 ORDER BY name ASC");
$inventory = $wpdb->get_results("SELECT id, name, sku, unit FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY name ASC");

$orders = $wpdb->get_results(
    "SELECT po.*, s.name AS supplier_name FROM {$wpdb->prefix}vesho_purchase_orders po
     LEFT JOIN {$wpdb->prefix}vesho_suppliers s ON s.id=po.supplier_id
     ORDER BY po.created_at DESC LIMIT 100"
);

$statuses = ['draft'=>'Mustand','ordered'=>'Tellitud','partial'=>'Osaliselt saabunud','received'=>'Saabunud','cancelled'=>'Tühistatud'];
$status_colors = ['draft'=>['#f3f4f6','#6b7280'],'ordered'=>['#dbeafe','#1d4ed8'],'partial'=>['#fef9c3','#b45309'],'received'=>['#dcfce7','#16a34a'],'cancelled'=>['#f3f4f6','#6b7280']];
?>
<div class="crm-wrap">
<div class="crm-page-header">
    <div class="crm-page-header__logo">📦</div>
    <div class="crm-page-header__body">
        <h1 class="crm-page-header__title">Ostutellimused</h1>
        <p class="crm-page-header__subtitle">Halda tarnijatelt tellimusi</p>
    </div>
    <div class="crm-page-header__actions">
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-purchase-orders&action=add'); ?>" class="crm-btn crm-btn-primary crm-btn-sm">+ Uus tellimus</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-suppliers'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">🏭 Tarnijad</a>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="crm-alert crm-alert-success"><?php echo ['saved'=>'Salvestatud!','deleted'=>'Kustutatud!'][$_GET['msg']] ?? 'OK'; ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $edit)): ?>
<div class="crm-card" style="margin-bottom:24px">
    <div class="crm-card-header">
        <span class="crm-card-title"><?php echo $edit ? 'Muuda tellimust #'.$edit->id : 'Uus ostutellimus'; ?></span>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-purchase-orders'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
    </div>
    <div style="padding:20px">
    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('vesho_save_purchase_order'); ?>
        <input type="hidden" name="action" value="vesho_save_purchase_order">
        <input type="hidden" name="po_id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
            <div>
                <label class="crm-form-label">Tarnija *</label>
                <select name="supplier_id" class="crm-form-select" required>
                    <option value="">— vali tarnija —</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?php echo $s->id; ?>" <?php selected($edit->supplier_id ?? 0, $s->id); ?>><?php echo esc_html($s->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="crm-form-label">Tellimuse nr</label>
                <input type="text" name="order_number" class="crm-form-input" value="<?php echo esc_attr($edit->order_number ?? ''); ?>" placeholder="PO-2026-001">
            </div>
            <div>
                <label class="crm-form-label">Staatus</label>
                <select name="status" class="crm-form-select">
                    <?php foreach ($statuses as $v=>$l): ?>
                    <option value="<?php echo $v; ?>" <?php selected($edit->status ?? 'draft', $v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="crm-form-label">Tellimuse kuupäev</label>
                <input type="date" name="order_date" class="crm-form-input" value="<?php echo esc_attr($edit->order_date ?? date('Y-m-d')); ?>">
            </div>
            <div>
                <label class="crm-form-label">Eeldatav tarneaeg</label>
                <input type="date" name="expected_date" class="crm-form-input" value="<?php echo esc_attr($edit->expected_date ?? ''); ?>">
            </div>
            <div style="grid-column:1/-1">
                <label class="crm-form-label">Märkused</label>
                <textarea name="notes" class="crm-form-textarea" rows="2"><?php echo esc_textarea($edit->notes ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Tooted -->
        <div class="crm-form-label" style="margin-bottom:10px">Tellitavad tooted</div>
        <div id="po-items">
        <?php
        $existing_items = $items ?: [null];
        foreach ($existing_items as $i => $item): ?>
        <div class="po-item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr 36px;gap:8px;margin-bottom:8px;align-items:center">
            <select name="items[<?php echo $i; ?>][inventory_id]" class="crm-form-select" style="font-size:13px">
                <option value="">— vali toode —</option>
                <?php foreach ($inventory as $inv): ?>
                <option value="<?php echo $inv->id; ?>" <?php selected($item->inventory_id ?? 0, $inv->id); ?>>
                    <?php echo esc_html($inv->name.($inv->sku ? ' ('.$inv->sku.')' : '')); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="items[<?php echo $i; ?>][quantity]" class="crm-form-input" style="font-size:13px" placeholder="Kogus" min="0" step="0.001" value="<?php echo esc_attr($item->quantity ?? ''); ?>">
            <input type="number" name="items[<?php echo $i; ?>][unit_price]" class="crm-form-input" style="font-size:13px" placeholder="Ühikuhind €" min="0" step="0.01" value="<?php echo esc_attr($item->unit_price ?? ''); ?>">
            <input type="text" name="items[<?php echo $i; ?>][product_name]" class="crm-form-input" style="font-size:13px" placeholder="Vaba toode" value="<?php echo esc_attr($item->product_name ?? ''); ?>">
            <button type="button" onclick="this.closest('.po-item-row').remove()" style="background:#fee2e2;border:none;border-radius:6px;color:#b91c1c;cursor:pointer;padding:6px 10px;font-size:14px">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" id="add-po-item" class="crm-btn crm-btn-outline crm-btn-sm" style="margin-bottom:20px">+ Lisa rida</button>

        <div style="display:flex;gap:12px">
            <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            <?php if ($edit && $edit->status === 'draft'): ?>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_purchase_order&po_id='.$edit->id), 'vesho_delete_po_'.$edit->id); ?>"
               class="crm-btn crm-btn-outline crm-btn-sm" style="color:#ef4444;border-color:#ef4444"
               onclick="return confirm('Kustuta tellimus?')">🗑 Kustuta</a>
            <?php endif; ?>
        </div>
    </form>
    </div>
</div>
<script>
(function(){
    var idx = <?php echo count($existing_items ?? [0]); ?>;
    var inv = <?php echo json_encode(array_map(fn($p)=>['id'=>$p->id,'name'=>$p->name.($p->sku?' ('.$p->sku.')':'')],$inventory)); ?>;
    document.getElementById('add-po-item').addEventListener('click', function(){
        var opts = inv.map(function(p){ return '<option value="'+p.id+'">'+p.name+'</option>'; }).join('');
        var row = '<div class="po-item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr 36px;gap:8px;margin-bottom:8px;align-items:center">'
            + '<select name="items['+idx+'][inventory_id]" class="crm-form-select" style="font-size:13px"><option value="">— vali toode —</option>'+opts+'</select>'
            + '<input type="number" name="items['+idx+'][quantity]" class="crm-form-input" style="font-size:13px" placeholder="Kogus" min="0" step="0.001">'
            + '<input type="number" name="items['+idx+'][unit_price]" class="crm-form-input" style="font-size:13px" placeholder="Ühikuhind €" min="0" step="0.01">'
            + '<input type="text" name="items['+idx+'][product_name]" class="crm-form-input" style="font-size:13px" placeholder="Vaba toode">'
            + '<button type="button" onclick="this.closest(\'.po-item-row\').remove()" style="background:#fee2e2;border:none;border-radius:6px;color:#b91c1c;cursor:pointer;padding:6px 10px;font-size:14px">✕</button>'
            + '</div>';
        document.getElementById('po-items').insertAdjacentHTML('beforeend', row);
        idx++;
    });
})();
</script>
<?php else: ?>

<div class="crm-card">
    <div class="crm-card-header">
        <span class="crm-card-title">Ostutellimused <span class="crm-count">(<?php echo count($orders); ?>)</span></span>
    </div>
    <?php if (empty($orders)): ?>
        <div class="crm-empty">Ostutellimusi pole.</div>
    <?php else: ?>
    <table class="crm-table">
        <thead><tr>
            <th>ID</th><th>Tarnija</th><th>Tellimuse nr</th><th>Kuupäev</th><th>Tarnekuupäev</th><th>Summa</th><th>Staatus</th><th class="td-actions">Toimingud</th>
        </tr></thead>
        <tbody>
        <?php foreach ($orders as $o):
            $sc = $status_colors[$o->status] ?? ['#f3f4f6','#6b7280'];
        ?>
        <tr>
            <td style="color:#6b8599">#<?php echo $o->id; ?></td>
            <td style="font-weight:500"><?php echo esc_html($o->supplier_name ?? '—'); ?></td>
            <td><?php echo esc_html($o->order_number ?: '—'); ?></td>
            <td><?php echo $o->order_date ? date('d.m.Y', strtotime($o->order_date)) : '—'; ?></td>
            <td><?php echo $o->expected_date ? date('d.m.Y', strtotime($o->expected_date)) : '—'; ?></td>
            <td style="font-weight:600"><?php echo $o->total_amount > 0 ? number_format($o->total_amount, 2, ',', ' ').' €' : '—'; ?></td>
            <td><span style="display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;background:<?php echo $sc[0]; ?>;color:<?php echo $sc[1]; ?>"><?php echo $statuses[$o->status] ?? $o->status; ?></span></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-purchase-orders&action=edit&po_id='.$o->id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Muuda</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>
