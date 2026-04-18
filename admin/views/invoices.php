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

// Print/PDF view — professional A4 invoice
if ( $action === 'print' && $invoice_id ) {
    $inv  = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, c.name as client_name, c.email as client_email, c.address as client_address,
                c.company as client_company, c.reg_code as client_reg, c.vat_number as client_vat
         FROM {$wpdb->prefix}vesho_invoices i
         LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id
         WHERE i.id=%d", $invoice_id));
    if (!$inv) wp_die('Arve ei leitud');
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC",
        $invoice_id));
    $co_name  = get_option('vesho_company_name','Vesho OÜ');
    $co_reg   = get_option('vesho_company_reg','');
    $co_vat   = get_option('vesho_company_vat','');
    $co_addr  = get_option('vesho_company_address','');
    $co_bank  = get_option('vesho_company_bank','');
    $co_iban  = get_option('vesho_company_iban','');
    $co_email = get_option('vesho_company_email','');
    $co_phone = get_option('vesho_company_phone','');
    $co_logo  = get_option('vesho_company_logo','');
    $status_labels = ['draft'=>'Mustand','sent'=>'Saadetud','paid'=>'Makstud','unpaid'=>'Maksmata','overdue'=>'Tähtaeg ületatud'];
    $status_colors = ['paid'=>'#16a34a','draft'=>'#6b7280','sent'=>'#2563eb','unpaid'=>'#dc2626','overdue'=>'#dc2626'];
    $inv_status = $inv->status ?? 'draft';
    // Remove WP theme for clean print page
    remove_all_actions('wp_head');
    remove_all_actions('wp_footer');
    ?><!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arve <?php echo esc_html($inv->invoice_number); ?></title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: A4; margin: 15mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #1a1a2e; background: #fff; }
    .page { max-width: 794px; margin: 0 auto; padding: 32px; background: #fff; }

    /* Header */
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; padding-bottom: 24px; border-bottom: 3px solid #00b4c8; }
    .inv-logo img { max-height: 60px; max-width: 180px; }
    .inv-logo-name { font-size: 22px; font-weight: 800; color: #00b4c8; letter-spacing: -0.5px; }
    .inv-company-info { text-align: right; font-size: 11px; color: #555; line-height: 1.6; }
    .inv-company-info strong { font-size: 14px; color: #1a1a2e; display: block; margin-bottom: 4px; }

    /* Invoice title block */
    .inv-title-block { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
    .inv-title { font-size: 32px; font-weight: 800; color: #1a1a2e; letter-spacing: -1px; }
    .inv-number { font-size: 18px; font-weight: 700; color: #00b4c8; margin-top: 4px; }
    .inv-meta { font-size: 11px; color: #666; margin-top: 8px; line-height: 2; }
    .inv-meta strong { color: #1a1a2e; min-width: 90px; display: inline-block; }
    .inv-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
                  letter-spacing: 0.5px; text-transform: uppercase; color: #fff;
                  background: <?php echo $status_colors[$inv_status] ?? '#6b7280'; ?>; }

    /* Parties */
    .inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
    .inv-party { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
    .inv-party-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 8px; }
    .inv-party-name { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
    .inv-party-detail { font-size: 11px; color: #555; line-height: 1.8; }

    /* Items table */
    .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .inv-table thead tr { background: #0d1f2d; color: #fff; }
    .inv-table thead th { padding: 10px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
    .inv-table thead th:nth-child(n+3) { text-align: right; }
    .inv-table tbody tr:nth-child(even) { background: #f8fafc; }
    .inv-table tbody tr:hover { background: #f0f9ff; }
    .inv-table tbody td { padding: 9px 12px; font-size: 11.5px; border-bottom: 1px solid #e9ecef; }
    .inv-table tbody td:nth-child(n+3) { text-align: right; }
    .inv-table tfoot td { padding: 8px 12px; font-size: 12px; }
    .inv-table tfoot .total-row td { font-size: 15px; font-weight: 800; background: #f0f9ff; border-top: 2px solid #00b4c8; color: #0d1f2d; }

    /* Payment info */
    .inv-payment { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; margin-top: 8px; }
    .inv-payment-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0369a1; margin-bottom: 8px; }
    .inv-payment-row { display: flex; gap: 24px; font-size: 11px; line-height: 2; }
    .inv-payment-row span { color: #555; }
    .inv-payment-row strong { color: #1a1a2e; }

    /* Notes */
    .inv-notes { margin-top: 20px; padding: 12px 16px; background: #fffbeb; border-left: 3px solid #f59e0b; font-size: 11px; color: #555; border-radius: 0 6px 6px 0; }

    /* Footer */
    .inv-footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e9ecef; padding-top: 12px; }

    /* Print button */
    .print-btn { position: fixed; bottom: 24px; right: 24px; padding: 12px 24px; background: #00b4c8; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,180,200,.4); transition: background .15s; z-index: 1000; }
    .print-btn:hover { background: #0097a9; }
    @media print { .print-btn { display: none !important; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="inv-header">
        <div class="inv-logo">
            <?php if ($co_logo) : ?>
                <img src="<?php echo esc_url($co_logo); ?>" alt="<?php echo esc_attr($co_name); ?>">
            <?php else : ?>
                <div class="inv-logo-name"><?php echo esc_html($co_name); ?></div>
            <?php endif; ?>
        </div>
        <div class="inv-company-info">
            <strong><?php echo esc_html($co_name); ?></strong>
            <?php if ($co_reg) echo 'Reg: ' . esc_html($co_reg) . '<br>'; ?>
            <?php if ($co_vat) echo 'KMKR: ' . esc_html($co_vat) . '<br>'; ?>
            <?php if ($co_addr) echo esc_html($co_addr) . '<br>'; ?>
            <?php if ($co_email) echo esc_html($co_email) . '<br>'; ?>
            <?php if ($co_phone) echo esc_html($co_phone); ?>
        </div>
    </div>

    <!-- Title block -->
    <div class="inv-title-block">
        <div>
            <div class="inv-title">ARVE</div>
            <div class="inv-number"><?php echo esc_html($inv->invoice_number); ?></div>
            <div class="inv-meta">
                <span><strong>Kuupäev:</strong> <?php echo vesho_crm_format_date($inv->invoice_date); ?></span><br>
                <?php if ($inv->due_date) : ?>
                <span><strong>Maksetähtaeg:</strong> <?php echo vesho_crm_format_date($inv->due_date); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right">
            <span class="inv-status"><?php echo esc_html($status_labels[$inv_status] ?? $inv_status); ?></span>
        </div>
    </div>

    <!-- Parties -->
    <div class="inv-parties">
        <div class="inv-party">
            <div class="inv-party-label">Müüja</div>
            <div class="inv-party-name"><?php echo esc_html($co_name); ?></div>
            <div class="inv-party-detail">
                <?php if ($co_reg) echo 'Reg: ' . esc_html($co_reg) . '<br>'; ?>
                <?php if ($co_vat) echo 'KMKR: ' . esc_html($co_vat) . '<br>'; ?>
                <?php if ($co_addr) echo esc_html($co_addr) . '<br>'; ?>
                <?php if ($co_email) echo esc_html($co_email); ?>
            </div>
        </div>
        <div class="inv-party">
            <div class="inv-party-label">Ostja</div>
            <div class="inv-party-name"><?php echo esc_html($inv->client_company ?: $inv->client_name); ?></div>
            <div class="inv-party-detail">
                <?php if ($inv->client_company && $inv->client_name !== $inv->client_company) echo esc_html($inv->client_name) . '<br>'; ?>
                <?php if ($inv->client_reg ?? '') echo 'Reg: ' . esc_html($inv->client_reg) . '<br>'; ?>
                <?php if ($inv->client_vat ?? '') echo 'KMKR: ' . esc_html($inv->client_vat) . '<br>'; ?>
                <?php if ($inv->client_address) echo esc_html($inv->client_address) . '<br>'; ?>
                <?php if ($inv->client_email) echo esc_html($inv->client_email); ?>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <?php $subtotal = 0; $vat_sum = 0; ?>
    <table class="inv-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Kirjeldus</th>
                <th style="width:70px">Kogus</th>
                <th style="width:90px">Ühikuhind</th>
                <th style="width:55px">KM %</th>
                <th style="width:90px">KM</th>
                <th style="width:100px">Kokku</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item) :
            $net   = $item->quantity * $item->unit_price;
            $vat_a = $net * $item->vat_rate / 100;
            $subtotal += $net;
            $vat_sum  += $vat_a;
        ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo esc_html($item->description); ?></td>
            <td><?php echo number_format((float)$item->quantity, 2, ',', '.'); ?></td>
            <td><?php echo number_format((float)$item->unit_price, 2, ',', '.'); ?> €</td>
            <td><?php echo number_format((float)$item->vat_rate, 0); ?>%</td>
            <td><?php echo number_format($vat_a, 2, ',', '.'); ?> €</td>
            <td><strong><?php echo number_format((float)$item->total, 2, ',', '.'); ?> €</strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"></td>
                <td style="color:#555;font-size:11px;text-align:right">Summa (km-ta):</td>
                <td style="text-align:right"><?php echo number_format($subtotal, 2, ',', '.'); ?> €</td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td style="color:#555;font-size:11px;text-align:right">Käibemaks:</td>
                <td style="text-align:right"><?php echo number_format($vat_sum, 2, ',', '.'); ?> €</td>
            </tr>
            <tr class="total-row">
                <td colspan="5"></td>
                <td style="text-align:right">KOKKU TASUDA:</td>
                <td style="text-align:right"><?php echo number_format((float)$inv->amount, 2, ',', '.'); ?> €</td>
            </tr>
        </tfoot>
    </table>

    <?php if ($co_bank || $co_iban) : ?>
    <div class="inv-payment">
        <div class="inv-payment-title">💳 Makse info</div>
        <div class="inv-payment-row">
            <?php if ($co_bank) : ?><span>Pank: <strong><?php echo esc_html($co_bank); ?></strong></span><?php endif; ?>
            <?php if ($co_iban) : ?><span>IBAN: <strong><?php echo esc_html($co_iban); ?></strong></span><?php endif; ?>
            <?php if ($inv->due_date) : ?><span>Tähtaeg: <strong><?php echo vesho_crm_format_date($inv->due_date); ?></strong></span><?php endif; ?>
            <span>Viitenumber: <strong><?php echo esc_html($inv->invoice_number); ?></strong></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($inv->description)) : ?>
    <div class="inv-notes">
        <strong>Märkused:</strong> <?php echo esc_html($inv->description); ?>
    </div>
    <?php endif; ?>

    <div class="inv-footer">
        <?php echo esc_html($co_name); ?>
        <?php if ($co_reg) echo ' · Reg: ' . esc_html($co_reg); ?>
        <?php if ($co_vat) echo ' · KMKR: ' . esc_html($co_vat); ?>
        <?php if ($co_email) echo ' · ' . esc_html($co_email); ?>
    </div>

</div>

<button class="print-btn" onclick="window.print()">📄 Laadi PDF</button>
<script>window.onload = function() { window.print(); };</script>
</body>
</html>
    <?php
    return; // end print view
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
                   class="crm-btn crm-btn-outline" target="_blank">📄 Laadi PDF</a>
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
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_export_invoices_csv'),'vesho_export_invoices_csv'); ?>" class="crm-btn crm-btn-outline crm-btn-sm" title="Laadi alla CSV (Excel)">⬇️ CSV</a>
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
    var veshoNonce = '<?php echo wp_create_nonce("vesho_admin_nonce"); ?>';
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
