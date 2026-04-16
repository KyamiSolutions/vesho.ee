<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action     = isset($_GET['action'])     ? sanitize_text_field($_GET['action'])  : '';
$invoice_id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id'])            : 0;
$search     = isset($_GET['s'])          ? sanitize_text_field($_GET['s'])        : '';
$filter_st  = isset($_GET['status'])     ? sanitize_text_field($_GET['status'])  : '';

$all_clients  = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_clients ORDER BY name ASC");
$price_list   = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_price_list WHERE active=1 ORDER BY category ASC, name ASC");

$edit = null;
$edit_items = [];
if ( $action === 'edit' && $invoice_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d", $invoice_id));
    if ($edit) {
        $edit_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC",
            $invoice_id
        ));
    }
}

$where = '1=1';
if ($filter_st) { $where .= $wpdb->prepare(' AND i.status=%s', $filter_st); }
if ($search)    { $where .= $wpdb->prepare(' AND (i.invoice_number LIKE %s OR c.name LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$invoices = $wpdb->get_results(
    "SELECT i.*, c.name as client_name FROM {$wpdb->prefix}vesho_invoices i
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id
     WHERE $where ORDER BY i.invoice_date DESC LIMIT 300"
);
$total = count($invoices);

$statuses        = ['draft'=>'Mustand','sent'=>'Saadetud','paid'=>'Makstud','unpaid'=>'Maksmata','overdue'=>'Tähtaeg ületatud'];
$statuses_edit   = ['draft'=>'Mustand','sent'=>'Saadetud','paid'=>'Makstud'];

// Stats
$stats = $wpdb->get_row("SELECT
    SUM(CASE WHEN status='unpaid' OR status='overdue' THEN amount ELSE 0 END) as unpaid_total,
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid_total,
    COUNT(CASE WHEN status='unpaid' OR status='overdue' THEN 1 END) as unpaid_count
    FROM {$wpdb->prefix}vesho_invoices");

$vat_rate = (float) Vesho_CRM_Database::get_setting('vat_rate', '22');

// Print/PDF view
if ( $action === 'print' && $invoice_id ) {
    $inv  = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.name as client_name, c.email as client_email, c.address as client_address, c.company as client_company FROM {$wpdb->prefix}vesho_invoices i LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id WHERE i.id=%d", $invoice_id));
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC", $invoice_id));
    $co_name = get_option('vesho_company_name','Vesho OÜ');
    $co_reg  = get_option('vesho_company_reg','');
    $co_vat  = get_option('vesho_company_vat','');
    $co_addr = get_option('vesho_company_address','');
    $co_bank = get_option('vesho_company_bank','');
    $co_iban = get_option('vesho_company_iban','');
    $co_email= get_option('vesho_company_email','');
    ?>
    <style>
    body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:0;padding:24px}
    .inv-header{display:flex;justify-content:space-between;margin-bottom:24px}
    .inv-title{font-size:28px;font-weight:700;color:#0d1f2d}
    table{width:100%;border-collapse:collapse;margin:16px 0}
    th{background:#f0f4f8;padding:8px 10px;text-align:left;font-size:12px;text-transform:uppercase}
    td{padding:8px 10px;border-bottom:1px solid #eee}
    .inv-totals{width:300px;margin-left:auto}
    .inv-totals td{border:none;padding:4px 8px}
    .inv-total-row td{font-weight:700;font-size:16px;border-top:2px solid #333}
    @media print{button{display:none}}
    </style>
    <div class="inv-header">
        <div>
            <div class="inv-title">ARVE</div>
            <div style="font-size:20px;font-weight:600;color:#2271b1"><?php echo esc_html($inv->invoice_number); ?></div>
            <div style="margin-top:8px;color:#555">Kuupäev: <?php echo vesho_crm_format_date($inv->invoice_date); ?></div>
            <?php if ($inv->due_date) : ?><div style="color:#555">Tähtaeg: <?php echo vesho_crm_format_date($inv->due_date); ?></div><?php endif; ?>
        </div>
        <div style="text-align:right">
            <div style="font-weight:700;font-size:16px"><?php echo esc_html($co_name); ?></div>
            <?php if ($co_reg) echo '<div>Reg: '.esc_html($co_reg).'</div>'; ?>
            <?php if ($co_vat) echo '<div>KMKR: '.esc_html($co_vat).'</div>'; ?>
            <?php if ($co_addr) echo '<div>'.esc_html($co_addr).'</div>'; ?>
            <?php if ($co_email) echo '<div>'.esc_html($co_email).'</div>'; ?>
        </div>
    </div>
    <div style="margin-bottom:20px">
        <strong>Klient:</strong><br>
        <?php echo esc_html($inv->client_company ?: $inv->client_name); ?><br>
        <?php echo esc_html($inv->client_name); ?><br>
        <?php echo esc_html($inv->client_address ?: ''); ?><br>
        <?php echo esc_html($inv->client_email ?: ''); ?>
    </div>
    <table>
        <thead><tr><th>#</th><th>Kirjeldus</th><th>Kogus</th><th>Ühikuhind</th><th>KM %</th><th>Kokku</th></tr></thead>
        <tbody>
        <?php $subtotal=0; $vat_sum=0; foreach ($items as $i=>$item) :
            $net = $item->quantity * $item->unit_price;
            $vat_a = $net * $item->vat_rate / 100;
            $subtotal += $net; $vat_sum += $vat_a;
        ?>
        <tr>
            <td><?php echo $i+1; ?></td>
            <td><?php echo esc_html($item->description); ?></td>
            <td><?php echo number_format($item->quantity,2,',','.'); ?></td>
            <td><?php echo number_format($item->unit_price,2,',','.'); ?> €</td>
            <td><?php echo number_format($item->vat_rate,0); ?>%</td>
            <td><?php echo number_format($item->total,2,',','.'); ?> €</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <table class="inv-totals">
        <tr><td>Summa ilma KM-ta:</td><td style="text-align:right"><?php echo number_format($subtotal,2,',','.'); ?> €</td></tr>
        <tr><td>KM:</td><td style="text-align:right"><?php echo number_format($vat_sum,2,',','.'); ?> €</td></tr>
        <tr class="inv-total-row"><td>KOKKU:</td><td style="text-align:right"><?php echo number_format($inv->amount,2,',','.'); ?> €</td></tr>
    </table>
    <?php if ($co_bank || $co_iban) : ?>
    <div style="margin-top:24px;padding:12px;background:#f0f4f8;border-radius:6px;font-size:12px">
        <strong>Pangaandmed:</strong> <?php echo esc_html($co_bank); ?> | IBAN: <?php echo esc_html($co_iban); ?>
    </div>
    <?php endif; ?>
    <?php if ($inv->description) : ?>
    <div style="margin-top:16px;color:#555;font-size:12px"><?php echo esc_html($inv->description); ?></div>
    <?php endif; ?>
    <div style="margin-top:20px;text-align:right">
        <button onclick="window.print()" style="padding:8px 20px;background:#2271b1;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Prindi / Salvesta PDF</button>
    </div>
    <script>window.onload=function(){window.print();}</script>
    <?php
    return;
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🧾 Arved <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Arve lisatud!','updated'=>'Arve uuendatud!','deleted'=>'Arve kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action !== 'edit' && $action !== 'add') : ?>
    <div class="crm-stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px">
        <div class="crm-stat-card"><div class="crm-stat-num"><?php echo vesho_crm_format_money($stats->paid_total??0); ?></div><div class="crm-stat-label">Makstud kokku</div></div>
        <div class="crm-stat-card"><div class="crm-stat-num" style="color:#ef4444"><?php echo vesho_crm_format_money($stats->unpaid_total??0); ?></div><div class="crm-stat-label">Maksmata</div></div>
        <div class="crm-stat-card"><div class="crm-stat-num"><?php echo intval($stats->unpaid_count??0); ?></div><div class="crm-stat-label">Maksmata arvet</div></div>
    </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda arvet':'Lisa uus arve'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" id="invoice-form">
            <?php wp_nonce_field('vesho_save_invoice'); ?>
            <input type="hidden" name="action" value="vesho_save_invoice">
            <?php if ($edit) : ?><input type="hidden" name="invoice_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group">
                    <label class="crm-form-label">Klient *</label>
                    <select class="crm-form-select" name="client_id" required>
                        <option value="">— Vali klient —</option>
                        <?php foreach ($all_clients as $c) : ?>
                            <option value="<?php echo $c->id; ?>" <?php selected($edit->client_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($edit) : ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">Arve number</label>
                    <input class="crm-form-input" type="text" name="invoice_number" value="<?php echo esc_attr($edit->invoice_number??''); ?>">
                </div>
                <?php endif; ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">Arve kuupäev</label>
                    <input class="crm-form-input" type="date" name="invoice_date" value="<?php echo esc_attr($edit->invoice_date??date('Y-m-d')); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Tähtaeg</label>
                    <input class="crm-form-input" type="date" name="due_date" value="<?php echo esc_attr($edit->due_date??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Staatus</label>
                    <select class="crm-form-select" name="status">
                        <?php foreach ($statuses_edit as $v=>$l) : ?>
                            <option value="<?php echo $v; ?>" <?php selected($edit->status??'draft',$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus / märkused</label>
                    <textarea class="crm-form-textarea" name="description" style="min-height:60px"><?php echo esc_textarea($edit->description??''); ?></textarea>
                </div>
            </div>

            <!-- Line items -->
            <div style="margin-top:24px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <strong style="font-size:14px">Arve read</strong>
                    <?php if (!empty($price_list)) : ?>
                    <div style="display:flex;gap:8px;align-items:center">
                        <select id="price-list-picker" class="crm-form-select" style="max-width:220px;font-size:12px;padding:5px 8px">
                            <option value="">+ Lisa hinnakirjast</option>
                            <?php
                            $cur_cat = '';
                            foreach ($price_list as $pl) :
                                if ($pl->category !== $cur_cat) {
                                    if ($cur_cat) echo '</optgroup>';
                                    echo '<optgroup label="'.esc_attr($pl->category).'">';
                                    $cur_cat = $pl->category;
                                }
                            ?>
                                <option value="<?php echo esc_attr(json_encode(['desc'=>$pl->name,'price'=>$pl->price,'vat'=>$pl->vat_rate])); ?>">
                                    <?php echo esc_html($pl->name.' — '.number_format($pl->price,2,',','.').' €'); ?>
                                </option>
                            <?php endforeach; if ($cur_cat) echo '</optgroup>'; ?>
                        </select>
                        <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" onclick="addFromPriceList()">Lisa</button>
                    </div>
                    <?php endif; ?>
                </div>
                <table class="crm-table" id="items-table">
                    <thead><tr>
                        <th style="width:40%">Kirjeldus</th>
                        <th style="width:10%">Kogus</th>
                        <th style="width:14%">Ühikuhind €</th>
                        <th style="width:10%">KM %</th>
                        <th style="width:13%">Kokku</th>
                        <th style="width:36px"></th>
                    </tr></thead>
                    <tbody id="items-body">
                    <?php if (!empty($edit_items)) :
                        foreach ($edit_items as $item) : ?>
                        <tr class="item-row">
                            <td><input type="text" name="items[desc][]" class="crm-form-input item-desc" value="<?php echo esc_attr($item->description); ?>" placeholder="Kirjeldus" required></td>
                            <td><input type="number" name="items[qty][]" class="crm-form-input item-qty" value="<?php echo esc_attr($item->quantity); ?>" step="0.001" min="0"></td>
                            <td><input type="number" name="items[price][]" class="crm-form-input item-price" value="<?php echo esc_attr($item->unit_price); ?>" step="0.01" min="0"></td>
                            <td><input type="number" name="items[vat][]" class="crm-form-input item-vat" value="<?php echo esc_attr($item->vat_rate); ?>" step="0.01" min="0" max="100"></td>
                            <td><span class="item-total" style="font-weight:600"><?php echo number_format($item->total,2,',','.'); ?> €</span></td>
                            <td><button type="button" class="crm-btn crm-btn-icon crm-btn-sm" onclick="removeRow(this)" title="Kustuta">✕</button></td>
                        </tr>
                        <?php endforeach;
                    else : ?>
                        <tr class="item-row">
                            <td><input type="text" name="items[desc][]" class="crm-form-input item-desc" placeholder="Kirjeldus" required></td>
                            <td><input type="number" name="items[qty][]" class="crm-form-input item-qty" value="1" step="0.001" min="0"></td>
                            <td><input type="number" name="items[price][]" class="crm-form-input item-price" value="" step="0.01" min="0" placeholder="0.00"></td>
                            <td><input type="number" name="items[vat][]" class="crm-form-input item-vat" value="<?php echo $vat_rate; ?>" step="0.01" min="0" max="100"></td>
                            <td><span class="item-total" style="font-weight:600">0,00 €</span></td>
                            <td><button type="button" class="crm-btn crm-btn-icon crm-btn-sm" onclick="removeRow(this)" title="Kustuta">✕</button></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">
                    <button type="button" onclick="addRow()" class="crm-btn crm-btn-outline crm-btn-sm">+ Lisa rida</button>
                    <div style="text-align:right;font-size:14px">
                        <div>Summa ilma KM-ta: <strong id="subtotal">0,00 €</strong></div>
                        <div>KM: <strong id="vat-total">0,00 €</strong></div>
                        <div style="font-size:16px;margin-top:4px">Kokku: <strong id="grand-total" style="color:#2271b1">0,00 €</strong></div>
                    </div>
                </div>
                <input type="hidden" name="amount" id="amount-field" value="<?php echo esc_attr($edit->amount ?? '0'); ?>">
            </div>

            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <?php if ($edit) : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=print&invoice_id='.$edit->id); ?>"
                   class="crm-btn crm-btn-outline" target="_blank">📄 PDF / Prindi</a>
                <?php endif; ?>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <script>
    var defaultVat = <?php echo $vat_rate; ?>;

    function recalcRow(row) {
        var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
        var price = parseFloat(row.querySelector('.item-price').value) || 0;
        var vat   = parseFloat(row.querySelector('.item-vat').value)   || 0;
        var net   = qty * price;
        var total = net * (1 + vat / 100);
        row.querySelector('.item-total').textContent = total.toFixed(2).replace('.',',') + ' €';
        recalcTotals();
    }

    function recalcTotals() {
        var subtotal = 0, vatSum = 0;
        document.querySelectorAll('.item-row').forEach(function(row){
            var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var vat   = parseFloat(row.querySelector('.item-vat').value)   || 0;
            var net   = qty * price;
            subtotal += net;
            vatSum   += net * vat / 100;
        });
        var grand = subtotal + vatSum;
        document.getElementById('subtotal').textContent   = subtotal.toFixed(2).replace('.',',') + ' €';
        document.getElementById('vat-total').textContent  = vatSum.toFixed(2).replace('.',',') + ' €';
        document.getElementById('grand-total').textContent = grand.toFixed(2).replace('.',',') + ' €';
        document.getElementById('amount-field').value = grand.toFixed(2);
    }

    function addRow(desc, qty, price, vat) {
        desc  = desc  || '';
        qty   = qty   !== undefined ? qty   : 1;
        price = price !== undefined ? price : '';
        vat   = vat   !== undefined ? vat   : defaultVat;
        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = '<td><input type="text" name="items[desc][]" class="crm-form-input item-desc" value="'+escHtml(desc)+'" placeholder="Kirjeldus" required></td>' +
            '<td><input type="number" name="items[qty][]" class="crm-form-input item-qty" value="'+qty+'" step="0.001" min="0"></td>' +
            '<td><input type="number" name="items[price][]" class="crm-form-input item-price" value="'+price+'" step="0.01" min="0" placeholder="0.00"></td>' +
            '<td><input type="number" name="items[vat][]" class="crm-form-input item-vat" value="'+vat+'" step="0.01" min="0" max="100"></td>' +
            '<td><span class="item-total" style="font-weight:600">0,00 €</span></td>' +
            '<td><button type="button" class="crm-btn crm-btn-icon crm-btn-sm" onclick="removeRow(this)" title="Kustuta">✕</button></td>';
        document.getElementById('items-body').appendChild(tr);
        bindRow(tr);
        recalcRow(tr);
    }

    function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function removeRow(btn) {
        var rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) { alert('Vähemalt üks rida peab jääma.'); return; }
        btn.closest('tr').remove();
        recalcTotals();
    }

    function bindRow(row) {
        row.querySelectorAll('.item-qty,.item-price,.item-vat').forEach(function(inp){
            inp.addEventListener('input', function(){ recalcRow(row); });
        });
    }

    function addFromPriceList() {
        var sel = document.getElementById('price-list-picker');
        if (!sel.value) return;
        try {
            var d = JSON.parse(sel.value);
            addRow(d.desc, 1, d.price, d.vat);
        } catch(e) {}
        sel.value = '';
    }

    document.querySelectorAll('.item-row').forEach(function(row){
        bindRow(row);
        recalcRow(row);
    });
    </script>

    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa arve</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-invoices">
                <select class="crm-form-select" name="status" style="max-width:160px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik staatused</option>
                    <?php foreach ($statuses as $v=>$l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($filter_st,$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi arve nr, klienti..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($invoices)) : ?>
            <div class="crm-empty">Arveid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>Arve nr</th><th>Klient</th><th>Kuupäev</th><th>Tähtaeg</th><th>Summa</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv) : ?>
            <tr>
                <td><strong><?php echo esc_html($inv->invoice_number); ?></strong></td>
                <td><a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$inv->client_id); ?>"><?php echo esc_html($inv->client_name); ?></a></td>
                <td><?php echo vesho_crm_format_date($inv->invoice_date); ?></td>
                <td>
                    <?php
                    $due = $inv->due_date;
                    $overdue = $due && $due < date('Y-m-d') && !in_array($inv->status, ['paid','draft']);
                    echo '<span'.($overdue?' style="color:#ef4444;font-weight:600"':'').'>'.vesho_crm_format_date($due).'</span>';
                    ?>
                </td>
                <td><strong><?php echo vesho_crm_format_money($inv->amount); ?></strong></td>
                <td>
                    <?php
                    echo vesho_crm_status_badge($inv->status);
                    if ( $inv->status === 'sent' && $inv->due_date && $inv->due_date < date('Y-m-d') ) {
                        echo ' <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background:#fee2e2;color:#dc2626">Tähtaeg ületatud</span>';
                    }
                    ?>
                </td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=edit&invoice_id='.$inv->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <?php if ($inv->status !== 'paid') : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_invoice_mark_paid&invoice_id='.$inv->id),'vesho_invoice_mark_paid'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Märgi makstud">✅</a>
                    <?php endif; ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_send_invoice_email&invoice_id='.$inv->id),'vesho_send_invoice_email'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Saada e-postiga"
                       onclick="return confirm('Saada arve kliendi e-postile?')">✉️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=print&invoice_id='.$inv->id); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="PDF / Prindi" target="_blank">📄</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_invoice&invoice_id='.$inv->id),'vesho_delete_invoice'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta arve?')">🗑️</a>
                    <?php if (in_array($inv->status, ['paid','sent'])) : ?>
                    <button class="crm-btn crm-btn-sm" style="background:#f0f9ff;border:1px solid #00b4c8;color:#00b4c8;padding:3px 8px;font-size:11px;font-weight:700" onclick="openCreditNoteModal(<?php echo $inv->id; ?>, '<?php echo esc_js($inv->invoice_number); ?>', <?php echo $inv->amount; ?>)">KARV</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php
    // ── Credit notes list ──────────────────────────────────────────────────
    $credit_notes = $wpdb->get_results("
        SELECT cn.*, c.name as client_name, i.invoice_number
        FROM {$wpdb->prefix}vesho_credit_notes cn
        LEFT JOIN {$wpdb->prefix}vesho_invoices i ON i.id = cn.invoice_id
        LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id = cn.client_id
        ORDER BY cn.created_at DESC LIMIT 50
    ");
    if (!empty($credit_notes)) :
    ?>
    <div class="crm-card" style="margin-top:24px">
        <div class="crm-card-header">
            <span class="crm-card-title">Kreeditarved (KARV)</span>
        </div>
        <table class="crm-table">
            <thead><tr>
                <th>KARV number</th><th>Arve</th><th>Klient</th><th>Summa</th><th>Põhjus</th><th>Kuupäev</th>
            </tr></thead>
            <tbody>
            <?php foreach ($credit_notes as $cn) : ?>
            <tr>
                <td><strong><?php echo esc_html($cn->credit_note_number); ?></strong></td>
                <td><?php echo esc_html($cn->invoice_number ?? '—'); ?></td>
                <td><?php echo esc_html($cn->client_name ?? '—'); ?></td>
                <td><strong><?php echo vesho_crm_format_money($cn->amount); ?></strong></td>
                <td><?php echo esc_html($cn->reason ?: '—'); ?></td>
                <td><?php echo $cn->issued_date ? vesho_crm_format_date($cn->issued_date) : '—'; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Credit note modal -->
    <div id="credit-note-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
      <div style="background:#fff;border-radius:12px;padding:28px;width:420px;max-width:95vw">
        <h3 style="margin-top:0">Lisa kreeditarve</h3>
        <p>Arve: <strong id="cn-inv-number"></strong></p>
        <div style="margin-bottom:12px">
          <label>Summa (€)</label><br>
          <input type="number" id="cn-amount" step="0.01" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:4px">
        </div>
        <div style="margin-bottom:16px">
          <label>Põhjus</label><br>
          <input type="text" id="cn-reason" placeholder="nt. Tagastus, Tühistamine..." style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:4px">
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button onclick="document.getElementById('credit-note-modal').style.display='none'" class="button">Tühista</button>
          <button onclick="submitCreditNote()" class="button button-primary">Loo kreeditarve</button>
        </div>
      </div>
    </div>
    <script>
    var cnInvId = 0;
    function openCreditNoteModal(invId, invNum, amount) {
      cnInvId = invId;
      document.getElementById('cn-inv-number').textContent = invNum;
      document.getElementById('cn-amount').value = amount;
      document.getElementById('cn-reason').value = '';
      document.getElementById('credit-note-modal').style.display = 'flex';
    }
    function submitCreditNote() {
      fetch(ajaxurl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=vesho_create_credit_note&nonce=' + veshoNonce + '&invoice_id=' + cnInvId + '&amount=' + document.getElementById('cn-amount').value + '&reason=' + encodeURIComponent(document.getElementById('cn-reason').value)
      }).then(r=>r.json()).then(d=>{
        if(d.success){ alert('Kreeditarve ' + d.data.number + ' loodud!'); location.reload(); }
        else alert('Viga: ' + d.data);
      });
    }
    </script>

    <?php endif; ?>
</div>
