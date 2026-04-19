<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$tab    = sanitize_key( $_GET['tab'] ?? 'draft' );
$search = sanitize_text_field( $_GET['s'] ?? '' );
$nonce  = wp_create_nonce('vesho_admin_nonce');

$all_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");
$inventory_items = $wpdb->get_results("SELECT id, name, sku, ean, unit, purchase_price, shop_price FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY name ASC");

// Status tab query
$status_filter = in_array( $tab, ['draft','pending','received','approved','rejected'] ) ? $tab : null;
$where = $status_filter ? $wpdb->prepare('WHERE sr.status=%s', $status_filter) : 'WHERE 1=1';
if ($search) {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $where .= $wpdb->prepare(' AND (sr.batch_ref LIKE %s OR sr.supplier LIKE %s OR sr.worker_name LIKE %s)', $like, $like, $like);
}

$receipts = $wpdb->get_results(
    "SELECT sr.*, COUNT(sri.id) as item_count, SUM(sri.quantity * COALESCE(sri.unit_price,sri.purchase_price,0)) as total_value
     FROM {$wpdb->prefix}vesho_stock_receipts sr
     LEFT JOIN {$wpdb->prefix}vesho_stock_receipt_items sri ON sri.receipt_id=sr.id
     $where GROUP BY sr.id ORDER BY sr.created_at DESC LIMIT 200"
);

// Badge counts for tabs
$counts = [];
foreach (['draft','pending','received','approved'] as $s) {
    $counts[$s] = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_stock_receipts WHERE status=%s", $s));
}

$tabs = [
    'draft'    => 'Mustandid',
    'pending'  => 'Töötajal',
    'received' => 'Vastuvõetud',
    'approved' => 'Kinnitatud',
    'all'      => 'Kõik',
];

$st_label = ['draft'=>'Mustrand','pending'=>'Töötajal','received'=>'Vastuvõetud','approved'=>'Kinnitatud','rejected'=>'Tagasi lükatud'];
$st_color = ['draft'=>'#64748b','pending'=>'#d97706','received'=>'#2563eb','approved'=>'#16a34a','rejected'=>'#dc2626'];
$st_bg    = ['draft'=>'#f1f5f9','pending'=>'#fef3c7','received'=>'#dbeafe','approved'=>'#dcfce7','rejected'=>'#fee2e2'];
?>
<div class="crm-wrap">
<h1 class="crm-page-title">📥 Kauba vastuvõtt</h1>
<p style="color:#6b7280;font-size:13px;margin-top:-12px;margin-bottom:16px">Sisesta arve, salvesta mustrand — saada töötajale vastuvõtuks</p>

<?php if (isset($_GET['msg'])) :
    $msgs = ['added'=>'Mustrand salvestatud!','deleted'=>'Kustutatud!'];
    echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
endif; ?>

<!-- Toolbar -->
<div class="crm-toolbar" style="margin-bottom:16px">
    <button class="crm-btn crm-btn-primary" onclick="openCreateModal()">+ Uus tarnimine</button>
    <form method="GET" style="display:flex;gap:6px;flex:1">
        <input type="hidden" name="page" value="vesho-crm-receipts">
        <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
        <input class="crm-search" type="search" name="s" placeholder="Otsi arve nr, tarnija, töötaja..." value="<?php echo esc_attr($search); ?>">
        <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
    </form>
    <div id="bulk-toolbar" style="display:none;gap:8px;align-items:center">
        <span id="bulk-count" style="font-size:13px;color:#6b7280"></span>
        <button class="crm-btn crm-btn-sm" style="background:#dcfce7;color:#16a34a;border:1px solid #86efac" onclick="bulkApprove()">✓ Kinnita valitud</button>
        <button class="crm-btn crm-btn-sm crm-btn-outline" onclick="clearBulk()">Tühista</button>
    </div>
</div>

<!-- Status tabs -->
<div style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;margin-bottom:20px;overflow-x:auto">
<?php foreach ($tabs as $key => $label) :
    $active = ($tab === $key || ($key === 'all' && !in_array($tab, array_keys($tabs))));
    $cnt = $counts[$key] ?? null;
?>
    <a href="<?php echo esc_url(add_query_arg(['page'=>'vesho-crm-receipts','tab'=>$key,'s'=>$search], admin_url('admin.php'))); ?>"
       style="padding:9px 18px;font-size:13px;font-weight:<?php echo $active?'700':'400'; ?>;color:<?php echo $active?'#00b4c8':'#6b7280'; ?>;text-decoration:none;border-bottom:2px solid <?php echo $active?'#00b4c8':'transparent'; ?>;white-space:nowrap;margin-bottom:-2px">
        <?php echo $label; ?>
        <?php if ($cnt > 0) echo '<span style="background:#e0f7fa;color:#006064;border-radius:999px;font-size:11px;padding:1px 7px;margin-left:5px;font-weight:700">'.$cnt.'</span>'; ?>
    </a>
<?php endforeach; ?>
</div>

<!-- Receipt list -->
<?php if (empty($receipts)) : ?>
    <div class="crm-empty"><?php echo $tab === 'draft' ? 'Mustandeid pole — klõpsa "+ Uus tarnimine"' : 'Kirjeid pole'; ?></div>
<?php else : ?>
<div style="display:flex;flex-direction:column;gap:8px">
<?php foreach ($receipts as $r) :
    $st = $r->status ?? 'draft';
    $bg = $st_bg[$st] ?? '#f1f5f9';
    $clr = $st_color[$st] ?? '#64748b';
?>
<div class="crm-card" style="padding:14px 18px">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:0;cursor:pointer" onclick="toggleReceiptItems(<?php echo $r->id; ?>)">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <?php if ($r->batch_ref) : ?>
                    <span style="font-family:monospace;font-weight:700;font-size:14px"><?php echo esc_html($r->batch_ref); ?></span>
                <?php endif; ?>
                <?php if ($r->supplier) : ?>
                    <span style="font-size:13px;color:#6b7280"><?php echo esc_html($r->supplier); ?></span>
                <?php endif; ?>
                <?php if (!$r->batch_ref && !$r->supplier) : ?>
                    <span style="font-size:14px;font-weight:600">Vastuvõtt #<?php echo $r->id; ?></span>
                <?php endif; ?>
                <span style="background:<?php echo $bg; ?>;color:<?php echo $clr; ?>;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600"><?php echo $st_label[$st] ?? $st; ?></span>
            </div>
            <div style="font-size:12px;color:#94a3b8;margin-top:3px">
                <?php echo (int)$r->item_count; ?> kaupa
                · <?php echo date('d.m.Y', strtotime($r->created_at)); ?>
                <?php if ($r->worker_name) echo ' · <strong>' . esc_html($r->worker_name) . '</strong>'; ?>
                <?php if ($r->total_value) echo ' · ' . number_format($r->total_value, 2) . ' €'; ?>
            </div>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
            <?php if ($st === 'draft') : ?>
                <input type="checkbox" class="bulk-cb" value="<?php echo $r->id; ?>" style="accent-color:#00b4c8;width:16px;height:16px">
                <button class="crm-btn crm-btn-primary crm-btn-sm" onclick="openSendModal(<?php echo $r->id; ?>)">📤 Saada töötajale</button>
            <?php elseif ($st === 'pending') : ?>
                <button class="crm-btn crm-btn-sm" style="background:#fef3c7;color:#d97706;border:1px solid #fcd34d" onclick="openSendModal(<?php echo $r->id; ?>)">🔄 Töötajat</button>
            <?php elseif ($st === 'received') : ?>
                <input type="checkbox" class="bulk-cb" value="<?php echo $r->id; ?>" style="accent-color:#00b4c8;width:16px;height:16px">
                <button class="crm-btn crm-btn-sm" style="background:#dcfce7;color:#16a34a;border:1px solid #86efac" onclick="approveReceipt(<?php echo $r->id; ?>, this)">✓ Kinnita</button>
                <button class="crm-btn crm-btn-sm" style="background:#dbeafe;color:#2563eb;border:1px solid #93c5fd" onclick="openToInvoiceModal(<?php echo $r->id; ?>)" title="Lisa arvele">📄 Arvele</button>
                <button class="crm-btn crm-btn-sm" style="background:#fef3c7;color:#d97706;border:1px solid #fcd34d" onclick="returnReceipt(<?php echo $r->id; ?>, this)" title="Tagasta tarnijale">↩</button>
            <?php endif; ?>
            <?php if (in_array($st, ['draft','pending','received'])) : ?>
                <button class="crm-btn crm-btn-sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5" onclick="rejectReceipt(<?php echo $r->id; ?>, this)">✗</button>
            <?php endif; ?>
            <button class="crm-btn crm-btn-outline crm-btn-sm" onclick="toggleReceiptItems(<?php echo $r->id; ?>)">&#8250;</button>
        </div>
    </div>
    <!-- Items (collapsed) -->
    <div id="recv-items-<?php echo $r->id; ?>" style="display:none;margin-top:14px;border-top:1px solid #f1f5f9;padding-top:12px">
        <div style="color:#94a3b8;font-size:13px;text-align:center;padding:8px">Laen...</div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- Create modal (AJAX-based, like 3006) -->
<div id="modal-create" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center;overflow-y:auto;padding:20px">
<div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;margin:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <strong style="font-size:16px">Uus tarnimine</strong>
        <button onclick="closeModal('modal-create')" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">✕</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div>
            <label class="crm-form-label">Arve nr</label>
            <input class="crm-form-input" type="text" id="new-batch-ref" placeholder="nt INV-2024-001">
        </div>
        <div>
            <label class="crm-form-label">Tarnija</label>
            <input class="crm-form-input" type="text" id="new-supplier" placeholder="Firma nimi">
        </div>
    </div>
    <div style="margin-bottom:16px">
        <label class="crm-form-label">Märkmed töötajale</label>
        <textarea class="crm-form-textarea" id="new-notes" rows="2"></textarea>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <strong style="font-size:13px">Kaubad</strong>
        <div style="display:flex;gap:6px">
            <button type="button" onclick="scanReceiptEan()" style="padding:4px 12px;font-size:12px;background:#e0f7fa;border:1px solid #00b4c8;color:#007a8a;border-radius:6px;cursor:pointer">📷 EAN</button>
            <button type="button" onclick="addNewLine()" style="padding:4px 12px;font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer">+ Lisa kaup</button>
        </div>
    </div>
    <div id="new-receipt-lines" style="display:flex;flex-direction:column;gap:8px"></div>
    <div id="new-receipt-msg" style="display:none;margin-top:10px;font-size:13px;color:#ef4444"></div>
    <div style="display:flex;gap:8px;margin-top:20px">
        <button type="button" onclick="closeModal('modal-create')" class="crm-btn crm-btn-outline" style="flex:1">Tühista</button>
        <button type="button" id="btn-save-receipt" onclick="saveNewReceipt()" class="crm-btn crm-btn-primary" style="flex:2">💾 Salvesta mustrand</button>
    </div>
</div>
</div>

<!-- Send to worker modal -->
<div id="modal-send" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:360px;margin:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <strong style="font-size:16px">Saada töötajale</strong>
        <button onclick="closeModal('modal-send')" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">✕</button>
    </div>
    <input type="hidden" id="send-receipt-id">
    <div style="margin-bottom:16px">
        <label class="crm-form-label">Töötaja *</label>
        <select id="send-worker-id" class="crm-form-select">
            <option value="">— Vali töötaja —</option>
            <?php foreach ($all_workers as $w) : ?>
                <option value="<?php echo (int)$w->id; ?>"><?php echo esc_html($w->name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="send-msg" style="display:none;margin-bottom:10px;font-size:13px;color:#16a34a"></div>
    <div style="display:flex;gap:8px">
        <button onclick="closeModal('modal-send')" class="crm-btn crm-btn-outline" style="flex:1">Tühista</button>
        <button onclick="doSend()" class="crm-btn crm-btn-primary" style="flex:2">📤 Saada</button>
    </div>
</div>
</div>

<!-- To-invoice modal -->
<div id="modal-to-invoice" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:400px;margin:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <strong style="font-size:16px">Lisa arvele</strong>
        <button onclick="closeModal('modal-to-invoice')" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">✕</button>
    </div>
    <input type="hidden" id="toi-receipt-id">
    <div style="margin-bottom:12px">
        <label class="crm-form-label">Arve ID *</label>
        <input id="toi-invoice-id" type="number" min="1" class="crm-form-input" placeholder="nt 42">
    </div>
    <div style="margin-bottom:16px">
        <label class="crm-form-label">KM määr (%)</label>
        <input id="toi-vat" type="number" min="0" max="100" value="0" class="crm-form-input">
    </div>
    <div id="toi-msg" style="display:none;margin-bottom:10px;font-size:13px"></div>
    <div style="display:flex;gap:8px">
        <button onclick="closeModal('modal-to-invoice')" class="crm-btn crm-btn-outline" style="flex:1">Tühista</button>
        <button onclick="doToInvoice()" class="crm-btn crm-btn-primary" style="flex:2">📄 Lisa arvele</button>
    </div>
</div>
</div>

<script>
var veshoNonce = '<?php echo esc_js($nonce); ?>';
var ajaxUrl    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var invData    = <?php echo json_encode(array_map(fn($i)=>['id'=>(int)$i->id,'name'=>$i->name,'sku'=>$i->sku??'','ean'=>$i->ean??'','unit'=>$i->unit??'tk','price'=>(float)$i->purchase_price,'sell'=>(float)$i->shop_price], $inventory_items)); ?>;
var receiptStatuses = <?php echo json_encode(array_reduce($receipts, fn($c,$r)=>array_merge($c,[(int)$r->id=>$r->status??'draft']), [])); ?>;
var loadedItems = {};
var lineIdx = 0;
var adminAddItemRid = 0;

function closeModal(id) { document.getElementById(id).style.display='none'; }
function openModal(id)  { document.getElementById(id).style.display='flex'; }

/* ---- Create modal (AJAX, 3006 stiil) ---- */
var newLines = [];  // [{inventory_id, product_name, product_sku, product_ean, product_unit, quantity, unit_price, selling_price}]
var newLineIdx = 0;

function openCreateModal() {
    newLines = [];
    newLineIdx = 0;
    document.getElementById('new-batch-ref').value = '';
    document.getElementById('new-supplier').value = '';
    document.getElementById('new-notes').value = '';
    document.getElementById('new-receipt-lines').innerHTML = '';
    document.getElementById('new-receipt-msg').style.display = 'none';
    document.getElementById('btn-save-receipt').disabled = false;
    document.getElementById('btn-save-receipt').textContent = '💾 Salvesta mustrand';
    addNewLine();
    openModal('modal-create');
}

function addNewLine(eanPreselect) {
    var i = newLineIdx++;
    newLines[i] = { inventory_id: '', product_name: '', product_sku: '', product_ean: '', product_unit: 'tk', quantity: '', unit_price: '', selling_price: '' };
    var container = document.getElementById('new-receipt-lines');
    var div = document.createElement('div');
    div.id = 'nl-'+i;
    div.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;position:relative';
    div.innerHTML = '<div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px;position:relative">'
        + '<div style="position:relative">'
        + '<input id="nlq-'+i+'" type="text" class="crm-form-input" placeholder="Toote nimi, SKU või EAN *" style="font-size:13px;margin:0;width:100%" oninput="nlSearch('+i+',this.value)" autocomplete="off">'
        + '<div id="nlsugg-'+i+'" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;z-index:10000;box-shadow:0 4px 20px rgba(0,0,0,.12);overflow:hidden;max-height:200px;overflow-y:auto"></div>'
        + '</div>'
        + (i > 0 ? '<button type="button" onclick="removeNewLine('+i+')" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:18px;padding:0 6px;align-self:center">✕</button>' : '<div></div>')
        + '</div>'
        + '<div style="display:grid;grid-template-columns:1fr 1fr 70px;gap:8px;margin-bottom:8px">'
        + '<input id="nlean-'+i+'" type="text" class="crm-form-input" placeholder="EAN" style="font-size:12px;font-family:monospace;margin:0" oninput="newLines['+i+'].product_ean=this.value">'
        + '<input id="nlsku-'+i+'" type="text" class="crm-form-input" placeholder="SKU" style="font-size:12px;font-family:monospace;margin:0" oninput="newLines['+i+'].product_sku=this.value">'
        + '<select id="nlunit-'+i+'" class="crm-form-input" style="font-size:12px;margin:0;padding:6px 4px" onchange="newLines['+i+'].product_unit=this.value"><option>tk</option><option>kg</option><option>l</option><option>m</option><option>pk</option><option>kast</option></select>'
        + '</div>'
        + '<div style="display:grid;grid-template-columns:90px 1fr 1fr;gap:8px">'
        + '<input id="nlqty-'+i+'" type="number" step="0.01" min="0.01" class="crm-form-input" placeholder="Kogus *" style="font-size:13px;text-align:right;margin:0" oninput="newLines['+i+'].quantity=this.value">'
        + '<input id="nlbuy-'+i+'" type="number" step="0.01" min="0" class="crm-form-input" placeholder="Sisseostuhind €" style="font-size:13px;text-align:right;margin:0" oninput="newLines['+i+'].unit_price=this.value">'
        + '<input id="nlsell-'+i+'" type="number" step="0.01" min="0" class="crm-form-input" placeholder="Müügihind €" style="font-size:13px;text-align:right;margin:0" oninput="newLines['+i+'].selling_price=this.value">'
        + '</div>';
    container.appendChild(div);
    if (eanPreselect) {
        var match = invData.find(function(inv){ return inv.ean===eanPreselect || inv.sku===eanPreselect; });
        if (match) { nlSelectInv(i, match); }
        else { document.getElementById('nlean-'+i).value = eanPreselect; newLines[i].product_ean = eanPreselect; }
    }
    // Close suggestion dropdowns on outside click
    document.addEventListener('click', function nlClose(e){
        var s = document.getElementById('nlsugg-'+i);
        var q = document.getElementById('nlq-'+i);
        if (s && q && !q.contains(e.target) && !s.contains(e.target)) s.style.display='none';
    });
}

function nlSearch(i, q) {
    newLines[i].product_name = q;
    newLines[i].inventory_id = '';
    var sugg = document.getElementById('nlsugg-'+i);
    if (!q.trim()) { sugg.style.display='none'; return; }
    var ql = q.toLowerCase();
    var matches = invData.filter(function(inv){
        return inv.name.toLowerCase().includes(ql) || (inv.sku||'').toLowerCase().includes(ql) || (inv.ean||'').includes(q);
    }).slice(0, 7);
    if (!matches.length) { sugg.style.display='none'; return; }
    sugg.innerHTML = matches.map(function(inv){
        return '<div onmousedown="nlSelectInv('+i+',{id:'+inv.id+',name:\''+inv.name.replace(/'/g,"\\'")
            +'\',ean:\''+(inv.ean||'')+'\',sku:\''+(inv.sku||'')+'\',unit:\''+(inv.unit||'tk')
            +'\',price:'+inv.price+',sell:'+inv.sell+'})" '
            +'style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px" '
            +'onmouseover="this.style.background=\'#f0f9ff\'" onmouseout="this.style.background=\'\'">'
            +'<div style="font-weight:600;color:#0d1f2d">'+inv.name+'</div>'
            +'<div style="font-size:11px;color:#94a3b8">'+[inv.sku?'SKU:'+inv.sku:'',inv.ean?'EAN:'+inv.ean:''].filter(Boolean).join(' · ')+'</div>'
            +'</div>';
    }).join('')
    + '<div onmousedown="nlKeepNew('+i+')" style="padding:8px 12px;cursor:pointer;font-size:13px;font-weight:600;color:#00b4c8" onmouseover="this.style.background=\'#f0f9ff\'" onmouseout="this.style.background=\'\'">+ Uus toode: „'+q+'"</div>';
    sugg.style.display='block';
}

function nlSelectInv(i, inv) {
    document.getElementById('nlq-'+i).value = inv.name;
    document.getElementById('nlean-'+i).value = inv.ean || '';
    document.getElementById('nlsku-'+i).value = inv.sku || '';
    document.getElementById('nlunit-'+i).value = inv.unit || 'tk';
    document.getElementById('nlbuy-'+i).value = inv.price || '';
    document.getElementById('nlsell-'+i).value = inv.sell || '';
    var sugg = document.getElementById('nlsugg-'+i);
    if (sugg) sugg.style.display='none';
    newLines[i].inventory_id = inv.id;
    newLines[i].product_name = inv.name;
    newLines[i].product_ean  = inv.ean || '';
    newLines[i].product_sku  = inv.sku || '';
    newLines[i].product_unit = inv.unit || 'tk';
    newLines[i].unit_price   = inv.price || '';
    newLines[i].selling_price = inv.sell || '';
}

function nlKeepNew(i) {
    var sugg = document.getElementById('nlsugg-'+i);
    if (sugg) sugg.style.display='none';
}

function removeNewLine(i) {
    var el = document.getElementById('nl-'+i);
    if (el) el.remove();
}

function saveNewReceipt() {
    var batchRef = document.getElementById('new-batch-ref').value.trim();
    var supplier = document.getElementById('new-supplier').value.trim();
    var notes    = document.getElementById('new-notes').value.trim();
    var msg      = document.getElementById('new-receipt-msg');
    // Collect valid lines from DOM
    var validLines = [];
    document.querySelectorAll('#new-receipt-lines [id^="nl-"]').forEach(function(div){
        var i = div.id.replace('nl-','');
        var name  = (document.getElementById('nlq-'+i)||{}).value||'';
        var qty   = parseFloat((document.getElementById('nlqty-'+i)||{}).value||0);
        var ean   = (document.getElementById('nlean-'+i)||{}).value||'';
        var sku   = (document.getElementById('nlsku-'+i)||{}).value||'';
        var unit  = (document.getElementById('nlunit-'+i)||{}).value||'tk';
        var buy   = (document.getElementById('nlbuy-'+i)||{}).value||'';
        var sell  = (document.getElementById('nlsell-'+i)||{}).value||'';
        if (name.trim() && qty > 0) {
            validLines.push({
                inventory_id:  newLines[i] ? (newLines[i].inventory_id||'') : '',
                product_name:  name.trim(),
                product_ean:   ean,
                product_sku:   sku,
                product_unit:  unit,
                quantity:      qty,
                unit_price:    buy || null,
                selling_price: sell || null,
            });
        }
    });
    if (!validLines.length) {
        msg.textContent = 'Lisa vähemalt üks kaup (nimi + kogus)!';
        msg.style.display = '';
        return;
    }
    var btn = document.getElementById('btn-save-receipt');
    btn.disabled = true; btn.textContent = 'Salvestamine...';
    msg.style.display = 'none';
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=vesho_admin_create_receipt&nonce='+veshoNonce
            + '&batch_ref='+encodeURIComponent(batchRef)
            + '&supplier='+encodeURIComponent(supplier)
            + '&notes='+encodeURIComponent(notes)
            + '&items='+encodeURIComponent(JSON.stringify(validLines))
    }).then(function(r){ return r.json(); }).then(function(d){
        btn.disabled = false; btn.textContent = '💾 Salvesta mustrand';
        if (d.success) {
            closeModal('modal-create');
            location.reload();
        } else {
            msg.textContent = d.data || 'Viga salvestamisel';
            msg.style.display = '';
        }
    }).catch(function(){
        btn.disabled = false; btn.textContent = '💾 Salvesta mustrand';
        msg.textContent = 'Ühenduse viga'; msg.style.display='';
    });
}

window.scanReceiptEan = function(){
    if (!window.VeshoScanner) return;
    window.VeshoScanner.open({ title:'Skänni kaup', autoConfirm:true, onScan:function(code){ addNewLine(code); } });
};

window.addNewLine = addNewLine;

/* ---- Send to worker ---- */
function openSendModal(receiptId) {
    document.getElementById('send-receipt-id').value = receiptId;
    document.getElementById('send-worker-id').value = '';
    document.getElementById('send-msg').style.display = 'none';
    openModal('modal-send');
}

function doSend() {
    var rid = document.getElementById('send-receipt-id').value;
    var wid = document.getElementById('send-worker-id').value;
    if (!wid) { alert('Vali töötaja!'); return; }
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_send&nonce='+veshoNonce+'&receipt_id='+rid+'&worker_id='+wid})
    .then(r=>r.json()).then(function(d){
        if (d.success) {
            closeModal('modal-send');
            location.reload();
        } else {
            var msg = document.getElementById('send-msg');
            msg.textContent = d.data || 'Viga';
            msg.style.color = '#ef4444';
            msg.style.display = '';
        }
    });
}

/* ---- Approve / Reject ---- */
function approveReceipt(id, btn) {
    btn.disabled = true; btn.textContent = '⏳';
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_approve&nonce='+veshoNonce+'&receipt_id='+id})
    .then(r=>r.json()).then(function(d){
        if (d.success) location.reload();
        else { btn.disabled=false; btn.textContent='✓ Kinnita'; alert('Viga: '+(d.data||'')); }
    });
}

function rejectReceipt(id, btn) {
    if (!confirm('Lükka saadetis tagasi?')) return;
    btn.disabled = true;
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_reject&nonce='+veshoNonce+'&receipt_id='+id})
    .then(r=>r.json()).then(function(d){
        if (d.success) location.reload();
        else { btn.disabled=false; alert('Viga: '+(d.data||'')); }
    });
}

function returnReceipt(id, btn) {
    if (!confirm('Tagasta tarnijale? Staatus muutub "Tagasi lükatud".')) return;
    btn.disabled = true;
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_return&nonce='+veshoNonce+'&receipt_id='+id})
    .then(r=>r.json()).then(function(d){
        if (d.success) location.reload();
        else { btn.disabled=false; alert('Viga: '+(d.data?.message||d.data||'')); }
    });
}

function openToInvoiceModal(id) {
    document.getElementById('toi-receipt-id').value = id;
    document.getElementById('toi-invoice-id').value = '';
    document.getElementById('toi-msg').style.display = 'none';
    document.getElementById('modal-to-invoice').style.display = 'flex';
}

function doToInvoice() {
    var rid = document.getElementById('toi-receipt-id').value;
    var invId = document.getElementById('toi-invoice-id').value;
    var vat = document.getElementById('toi-vat').value || 0;
    var msg = document.getElementById('toi-msg');
    if (!invId) { msg.textContent='Arve ID on kohustuslik'; msg.style.color='#dc2626'; msg.style.display=''; return; }
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_to_invoice&nonce='+veshoNonce+'&receipt_id='+rid+'&invoice_id='+invId+'&vat_rate='+vat})
    .then(r=>r.json()).then(function(d){
        if (d.success) {
            msg.textContent = d.data?.message || 'Lisatud!';
            msg.style.color = '#16a34a'; msg.style.display = '';
            setTimeout(function(){ location.reload(); }, 1200);
        } else { msg.textContent = d.data?.message || 'Viga'; msg.style.color='#dc2626'; msg.style.display=''; }
    });
}

/* ---- Bulk approve ---- */
document.addEventListener('change', function(e){
    if (e.target.classList.contains('bulk-cb')) updateBulkToolbar();
});

function updateBulkToolbar(){
    var checked = document.querySelectorAll('.bulk-cb:checked');
    var toolbar = document.getElementById('bulk-toolbar');
    var cnt = document.getElementById('bulk-count');
    if (checked.length > 0) {
        toolbar.style.display = 'flex';
        cnt.textContent = checked.length + ' valitud';
    } else {
        toolbar.style.display = 'none';
    }
}

function clearBulk(){
    document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked=false; });
    document.getElementById('bulk-toolbar').style.display = 'none';
}

function bulkApprove(){
    var ids = Array.from(document.querySelectorAll('.bulk-cb:checked')).map(function(cb){return parseInt(cb.value);});
    if (!ids.length) return;
    if (!confirm('Kinnita '+ids.length+' saadetist? Laoseis uuendatakse.')) return;
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_receipt_bulk_approve&nonce='+veshoNonce+'&ids='+encodeURIComponent(JSON.stringify(ids))})
    .then(r=>r.json()).then(function(d){
        if (d.success) {
            alert('Kinnitatud: '+d.data.approved);
            location.reload();
        } else alert('Viga: '+(d.data?.message||''));
    });
}

/* ---- Toggle items ---- */
function toggleReceiptItems(id) {
    var el = document.getElementById('recv-items-'+id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
        if (loadedItems[id]) return;
        loadedItems[id] = true;
        fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=vesho_admin_get_receipt_items&nonce='+veshoNonce+'&receipt_id='+id})
        .then(r=>r.json()).then(function(d){
            if (!d.success || !d.data) { el.innerHTML='<div style="color:#ef4444;padding:8px">Viga</div>'; return; }
            var items = d.data.items || d.data;
            var status = receiptStatuses[id] || 'draft';
            var canEdit = status === 'draft' || status === 'pending';
            var html = '';
            if (items.length === 0) {
                html += '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px">Tooteid pole lisatud</div>';
            } else {
                html += '<table class="crm-table"><thead><tr><th>Toode</th><th>SKU/EAN</th><th>Oodatav</th><th>Tegelik</th><th>Asukoht</th></tr></thead><tbody>';
                items.forEach(function(item){
                    html += '<tr>'
                        +'<td><strong>'+(item.name||item.item_name||'–')+'</strong></td>'
                        +'<td style="font-family:monospace;font-size:12px">'+(item.sku||'–')+'</td>'
                        +'<td>'+(item.expected_qty||item.quantity||'–')+' '+(item.unit||'tk')+'</td>'
                        +'<td style="color:'+(item.actual_qty!=null?'#16a34a':'#94a3b8')+';font-weight:600">'+(item.actual_qty!=null?item.actual_qty+' '+(item.unit||'tk'):'–')+'</td>'
                        +'<td style="font-family:monospace;font-size:12px">'+(item.location||'–')+'</td>'
                        +'</tr>';
                });
                html += '</tbody></table>';
            }
            if (canEdit) {
                html += '<div style="padding:10px 0 4px;display:flex;gap:8px">'
                    +'<button onclick="openAdminAddItem('+id+')" style="padding:6px 14px;font-size:12px;background:#e0f7fa;border:1px solid #00b4c8;color:#007a8a;border-radius:6px;cursor:pointer">+ Lisa kaup</button>'
                    +'</div>';
            }
            el.innerHTML = html;
        }).catch(function(){ el.innerHTML = '<div style="color:#ef4444;padding:8px">Ühenduse viga</div>'; });
    } else {
        el.style.display = 'none';
    }
}

function openAdminAddItem(rid) {
    adminAddItemRid = rid;
    document.getElementById('aai-inv').value = '';
    document.getElementById('aai-name').value = '';
    document.getElementById('aai-ean').value = '';
    document.getElementById('aai-qty').value = '';
    document.getElementById('aai-unit').value = 'tk';
    document.getElementById('aai-msg').style.display = 'none';
    openModal('modal-add-item');
}

function onAaiInvChange(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    var inv = invData.find(function(i){ return i.id == opt.value; });
    if (!inv) return;
    document.getElementById('aai-name').value = inv.name;
    document.getElementById('aai-ean').value  = inv.ean || '';
    document.getElementById('aai-unit').value = inv.unit || 'tk';
}

function doAdminAddItem() {
    var inv_id = document.getElementById('aai-inv').value;
    var name   = document.getElementById('aai-name').value.trim();
    var ean    = document.getElementById('aai-ean').value.trim();
    var qty    = document.getElementById('aai-qty').value;
    var unit   = document.getElementById('aai-unit').value;
    var msg    = document.getElementById('aai-msg');
    if (!name || !qty || parseFloat(qty) <= 0) { msg.textContent='Nimi ja kogus on kohustuslikud'; msg.style.display=''; return; }
    var btn = document.getElementById('aai-btn');
    btn.disabled = true; btn.textContent = 'Lisamine...';
    fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_admin_add_receipt_item&nonce='+veshoNonce
            +'&receipt_id='+adminAddItemRid
            +'&inventory_id='+encodeURIComponent(inv_id)
            +'&product_name='+encodeURIComponent(name)
            +'&product_ean='+encodeURIComponent(ean)
            +'&product_unit='+encodeURIComponent(unit)
            +'&quantity='+encodeURIComponent(qty)
    }).then(r=>r.json()).then(function(d){
        btn.disabled=false; btn.textContent='Lisa kaup';
        if (d.success) {
            closeModal('modal-add-item');
            var el = document.getElementById('recv-items-'+adminAddItemRid);
            if (el) { el.style.display='block'; el.innerHTML='<div style="color:#94a3b8;font-size:13px;padding:8px">Laen...</div>'; }
            fetch(ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=vesho_admin_get_receipt_items&nonce='+veshoNonce+'&receipt_id='+adminAddItemRid})
            .then(function(r){ return r.json(); }).then(function(d2){
                loadedItems[adminAddItemRid] = true;
                if (!d2.success || !d2.data) { if(el) el.innerHTML='<div style="color:#ef4444;padding:8px">Viga</div>'; return; }
                var items = d2.data.items || d2.data;
                var status = receiptStatuses[adminAddItemRid] || 'draft';
                var canEdit = status === 'draft' || status === 'pending';
                var html = '';
                if (!items.length) { html += '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px">Tooteid pole lisatud</div>'; }
                else {
                    html += '<table class="crm-table"><thead><tr><th>Toode</th><th>SKU/EAN</th><th>Oodatav</th><th>Tegelik</th><th>Asukoht</th></tr></thead><tbody>';
                    items.forEach(function(item){
                        html += '<tr><td><strong>'+(item.name||'–')+'</strong></td>'
                            +'<td style="font-family:monospace;font-size:12px">'+(item.sku||'–')+'</td>'
                            +'<td>'+(item.expected_qty||item.quantity||'–')+' '+(item.unit||'tk')+'</td>'
                            +'<td style="color:'+(item.actual_qty!=null?'#16a34a':'#94a3b8')+';font-weight:600">'+(item.actual_qty!=null?item.actual_qty+' '+(item.unit||'tk'):'–')+'</td>'
                            +'<td style="font-family:monospace;font-size:12px">'+(item.location||'–')+'</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                if (canEdit) html += '<div style="padding:10px 0 4px;display:flex;gap:8px"><button onclick="openAdminAddItem('+adminAddItemRid+')" style="padding:6px 14px;font-size:12px;background:#e0f7fa;border:1px solid #00b4c8;color:#007a8a;border-radius:6px;cursor:pointer">+ Lisa kaup</button></div>';
                if (el) el.innerHTML = html;
            });
            return;
        } else {
            msg.textContent = d.data?.message || 'Viga'; msg.style.display='';
        }
    });
}
</script>

<!-- Add item to receipt modal -->
<div id="modal-add-item" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
<div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:420px;margin:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <strong style="font-size:16px">Lisa kaup arvetele</strong>
        <button onclick="closeModal('modal-add-item')" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8">✕</button>
    </div>
    <div style="margin-bottom:12px">
        <label class="crm-form-label">Toode laost (valikuline)</label>
        <select id="aai-inv" class="crm-form-select crm-form-select-search" onchange="onAaiInvChange(this)">
            <option value="">— Vali olemasolev toode —</option>
            <?php foreach ($inventory_items as $inv): ?>
            <option value="<?php echo (int)$inv->id; ?>"><?php echo esc_html($inv->name); ?> <?php if($inv->sku): ?>(<?php echo esc_html($inv->sku); ?>)<?php endif; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div>
            <label class="crm-form-label">Toote nimi *</label>
            <input id="aai-name" class="crm-form-input" type="text" placeholder="Toote nimi">
        </div>
        <div>
            <label class="crm-form-label">EAN</label>
            <input id="aai-ean" class="crm-form-input" type="text" placeholder="EAN kood" style="font-family:monospace">
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 80px;gap:10px;margin-bottom:16px">
        <div>
            <label class="crm-form-label">Kogus *</label>
            <input id="aai-qty" class="crm-form-input" type="number" min="0.01" step="0.01" placeholder="Kogus">
        </div>
        <div>
            <label class="crm-form-label">Ühik</label>
            <select id="aai-unit" class="crm-form-select" style="height:38px">
                <option>tk</option><option>kg</option><option>l</option><option>m</option><option>pk</option><option>kast</option>
            </select>
        </div>
    </div>
    <div id="aai-msg" style="display:none;color:#ef4444;font-size:13px;margin-bottom:10px"></div>
    <div style="display:flex;gap:8px">
        <button onclick="closeModal('modal-add-item')" class="crm-btn crm-btn-outline" style="flex:1">Tühista</button>
        <button id="aai-btn" onclick="doAdminAddItem()" class="crm-btn crm-btn-primary" style="flex:2">Lisa kaup</button>
    </div>
</div>
</div>
