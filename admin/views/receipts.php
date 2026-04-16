<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action     = isset($_GET['action'])     ? sanitize_text_field($_GET['action'])  : '';
$receipt_id = isset($_GET['receipt_id']) ? absint($_GET['receipt_id'])            : 0;
$search     = isset($_GET['s'])          ? sanitize_text_field($_GET['s'])        : '';

$inventory_items = $wpdb->get_results("SELECT id, name, sku, ean, unit, purchase_price FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY name ASC");

$edit = null;
$edit_items = [];
if ( $action === 'view' && $receipt_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_stock_receipts WHERE id=%d", $receipt_id));
    if ($edit) {
        $edit_items = $wpdb->get_results($wpdb->prepare(
            "SELECT sri.*, inv.name as item_name, inv.unit FROM {$wpdb->prefix}vesho_stock_receipt_items sri
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON sri.inventory_id=inv.id
             WHERE sri.receipt_id=%d", $receipt_id
        ));
    }
}

$where = '1=1';
if ($search) { $where .= $wpdb->prepare(' AND (r.reference_number LIKE %s OR r.supplier LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$receipts = $wpdb->get_results(
    "SELECT r.*, COUNT(ri.id) as line_count, SUM(ri.quantity*ri.unit_price) as total_value
     FROM {$wpdb->prefix}vesho_stock_receipts r
     LEFT JOIN {$wpdb->prefix}vesho_stock_receipt_items ri ON r.id=ri.receipt_id
     WHERE $where GROUP BY r.id ORDER BY r.receipt_date DESC LIMIT 200"
);
$total = count($receipts);
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📥 Vastuvõtt <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Vastuvõtt salvestatud! Laoseis uuendatud.','deleted'=>'Vastuvõtt kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">Uus kauba vastuvõtt</span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-receipts'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_receipt'); ?>
            <input type="hidden" name="action" value="vesho_save_receipt">
            <div class="crm-form-grid" style="margin-bottom:20px">
                <div class="crm-form-group">
                    <label class="crm-form-label">Vastuvõtu kuupäev *</label>
                    <input class="crm-form-input" type="date" name="receipt_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Viitenumber / Arve nr</label>
                    <input class="crm-form-input" type="text" name="reference_number">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Tarnija</label>
                    <input class="crm-form-input" type="text" name="supplier">
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Märkused</label>
                    <textarea class="crm-form-textarea" name="notes" rows="2"></textarea>
                </div>
            </div>

            <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:#0d1f2d">Vastuvõetud kaubad
                <button type="button" onclick="scanReceiptEan()" style="margin-left:10px;padding:4px 12px;font-size:12px;background:#e0f7fa;border:1px solid #00b4c8;color:#007a8a;border-radius:6px;cursor:pointer;font-weight:600">📷 Skänni EAN</button>
            </h3>
            <div id="receipt-lines">
                <div class="receipt-line" style="display:grid;grid-template-columns:1fr 100px 120px 120px 40px;gap:8px;margin-bottom:8px;align-items:end">
                    <div>
                        <label class="crm-form-label">Toode</label>
                        <select class="crm-form-select" name="lines[0][inventory_id]" required>
                            <option value="">— Vali toode —</option>
                            <?php foreach ($inventory_items as $item) : ?>
                                <option value="<?php echo $item->id; ?>" data-price="<?php echo esc_attr($item->purchase_price); ?>"><?php echo esc_html($item->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="crm-form-label">Kogus</label>
                        <input class="crm-form-input" type="number" step="0.01" min="0.01" name="lines[0][quantity]" required>
                    </div>
                    <div>
                        <label class="crm-form-label">Ühikuhind (€)</label>
                        <input class="crm-form-input" type="number" step="0.01" name="lines[0][unit_price]">
                    </div>
                    <div>
                        <label class="crm-form-label">Partii nr</label>
                        <input class="crm-form-input" type="text" name="lines[0][batch_number]">
                    </div>
                    <div style="padding-top:22px"><button type="button" onclick="this.closest('.receipt-line').remove()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#ef4444">✕</button></div>
                </div>
            </div>
            <button type="button" id="add-line" class="crm-btn crm-btn-outline crm-btn-sm" style="margin-top:8px">+ Lisa rida</button>

            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-receipts'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta & uuenda laoseis</button>
            </div>
        </form>
        </div>
    </div>
    <script>
    (function(){
        var idx = 1;
        var inv = <?php echo json_encode(array_map(fn($i)=>['id'=>$i->id,'name'=>$i->name,'price'=>$i->purchase_price,'ean'=>$i->ean??'','sku'=>$i->sku??''], $inventory_items)); ?>;
        var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce   = '<?php echo wp_create_nonce('vesho_crm_nonce'); ?>';

        function buildLineHtml(i, preselect){
            preselect = preselect || 0;
            var opts = inv.map(function(item){
                return '<option value="'+item.id+'" data-price="'+item.price+'"'+(item.id==preselect?' selected':'')+'>'+item.name+'</option>';
            }).join('');
            var price = preselect ? (inv.find(function(x){ return x.id==preselect; })||{}).price || '' : '';
            return '<div class="receipt-line" style="display:grid;grid-template-columns:1fr 100px 120px 120px 40px;gap:8px;margin-bottom:8px;align-items:end">'
                +'<div><label class="crm-form-label">Toode</label><select class="crm-form-select" name="lines['+i+'][inventory_id]" required><option value="">— Vali toode —</option>'+opts+'</select></div>'
                +'<div><label class="crm-form-label">Kogus</label><input class="crm-form-input" type="number" step="0.01" min="0.01" name="lines['+i+'][quantity]" value="1" required></div>'
                +'<div><label class="crm-form-label">Ühikuhind (€)</label><input class="crm-form-input" type="number" step="0.01" name="lines['+i+'][unit_price]" value="'+(price||'')+'"></div>'
                +'<div><label class="crm-form-label">Partii nr</label><input class="crm-form-input" type="text" name="lines['+i+'][batch_number]"></div>'
                +'<div style="padding-top:22px"><button type="button" onclick="this.closest(\'.receipt-line\').remove()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#ef4444">✕</button></div>'
                +'</div>';
        }

        function addLine(preselect){ document.getElementById('receipt-lines').insertAdjacentHTML('beforeend', buildLineHtml(idx++, preselect)); }

        document.getElementById('add-line').addEventListener('click', function(){
            addLine(0);
        });

        // EAN scan → find item in inventory list → add line pre-selected
        window.scanReceiptEan = function(){
            window.VeshoScanner.open({
                title: 'Skänni vastuvõetud kaup',
                autoConfirm: true,
                onScan: function(code){
                    // Match by EAN or SKU locally first
                    var match = inv.find(function(i){ return i.ean===code || i.sku===code; });
                    if (match) {
                        addLine(match.id);
                        return;
                    }
                    // AJAX lookup
                    var fd = new FormData();
                    fd.append('action', 'vesho_ean_lookup');
                    fd.append('nonce', nonce);
                    fd.append('ean', code);
                    fetch(ajaxUrl, {method:'POST', body:fd})
                        .then(function(r){ return r.json(); })
                        .then(function(d){
                            if (d.success) {
                                // Push to local inv list if not present
                                if (!inv.find(function(i){ return i.id==d.data.id; })) {
                                    inv.push({id:d.data.id, name:d.data.name, price:d.data.purchase_price, ean:d.data.ean||'', sku:d.data.sku||''});
                                }
                                addLine(d.data.id);
                            } else {
                                alert('EAN toodet ei leitud: ' + code + '\nLisa toode kõigepealt laosse.');
                            }
                        })
                        .catch(function(){ alert('Ühenduse viga'); });
                }
            });
        };
    })();
    </script>
    <?php elseif ($action === 'view' && $edit) : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">Vastuvõtt #<?php echo $edit->id; ?> — <?php echo vesho_crm_format_date($edit->receipt_date); ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-receipts'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
            <?php if ($edit->supplier) echo '<p><strong>Tarnija:</strong> '.esc_html($edit->supplier).'</p>'; ?>
            <?php if ($edit->reference_number) echo '<p><strong>Viitenumber:</strong> '.esc_html($edit->reference_number).'</p>'; ?>
            <?php if ($edit->notes) echo '<p><strong>Märkused:</strong> '.esc_html($edit->notes).'</p>'; ?>
            <table class="crm-table" style="margin-top:16px">
                <thead><tr><th>Toode</th><th>Kogus</th><th>Ühikuhind</th><th>Kokku</th></tr></thead>
                <tbody>
                <?php foreach ($edit_items as $li) : ?>
                <tr>
                    <td><?php echo esc_html($li->item_name); ?></td>
                    <td><?php echo number_format($li->quantity,2); ?> <?php echo esc_html($li->unit); ?></td>
                    <td><?php echo vesho_crm_format_money($li->unit_price); ?></td>
                    <td><strong><?php echo vesho_crm_format_money($li->quantity * $li->unit_price); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-receipts&action=add'); ?>" class="crm-btn crm-btn-primary">+ Uus vastuvõtt</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-receipts">
                <input class="crm-search" type="search" name="s" placeholder="Otsi viitenumbrit, tarnijat..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($receipts)) : ?>
            <div class="crm-empty">Vastuvõtte ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Kuupäev</th><th>Tarnija</th><th>Viitenumber</th><th>Ridu</th><th>Väärtus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($receipts as $r) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $r->id; ?></td>
                <td><?php echo vesho_crm_format_date($r->receipt_date); ?></td>
                <td><?php echo esc_html($r->supplier?:'–'); ?></td>
                <td style="font-family:monospace"><?php echo esc_html($r->reference_number?:'–'); ?></td>
                <td><?php echo intval($r->line_count); ?> toodet</td>
                <td><?php echo $r->total_value ? vesho_crm_format_money($r->total_value) : '–'; ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-receipts&action=view&receipt_id='.$r->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Vaata">👁️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
