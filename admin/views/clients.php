<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$client_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
$search    = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$type_f    = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$per_page  = 50;
$paged     = max(1, (int)($_GET['paged'] ?? 1));
$offset    = ($paged - 1) * $per_page;

$args = ['limit' => $per_page, 'offset' => $offset, 'search' => $search, 'type' => $type_f];
$clients = Vesho_CRM_Database::get_clients($args);
$total   = Vesho_CRM_Database::count_clients();
$pages   = ceil($total / $per_page);

// Unpaid invoice sum per client for list view
$_client_ids = array_column((array)$clients, 'id');
$_unpaid_map = [];
if ( ! empty($_client_ids) ) {
    $_ids_sql = implode(',', array_map('intval', $_client_ids));
    $_rows = $wpdb->get_results(
        "SELECT client_id, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total
         FROM {$wpdb->prefix}vesho_invoices
         WHERE status IN ('unpaid','sent','overdue') AND client_id IN ({$_ids_sql})
         GROUP BY client_id"
    );
    foreach ($_rows as $_r) $_unpaid_map[$_r->client_id] = $_r;
}

// Edit mode
$edit_client = null;
if ( $action === 'edit' && $client_id ) {
    $edit_client = Vesho_CRM_Database::get_client($client_id);
}
// Prefill from guest request
$prefill = [];
if ( $action === 'add' && isset($_GET['prefill_name']) ) {
    $prefill['name']  = sanitize_text_field($_GET['prefill_name'] ?? '');
    $prefill['email'] = sanitize_email($_GET['prefill_email'] ?? '');
    $prefill['phone'] = sanitize_text_field($_GET['prefill_phone'] ?? '');
}

// ── Client history PDF print view ─────────────────────────────────────────────
if ( $action === 'history-pdf' && $client_id ) {
    $client = Vesho_CRM_Database::get_client($client_id);
    if ( ! $client ) wp_die( 'Klienti ei leitud' );
    $maintenances = $wpdb->get_results( $wpdb->prepare(
        "SELECT m.id, m.scheduled_date, m.completed_date, m.status, m.description,
                d.name as device_name, d.model as device_model
         FROM {$wpdb->prefix}vesho_maintenances m
         JOIN {$wpdb->prefix}vesho_devices d ON d.id = m.device_id
         WHERE d.client_id = %d ORDER BY m.scheduled_date DESC", $client_id
    ) );
    $co_name = get_option( 'vesho_company_name', 'Vesho OÜ' );
    $status_labels = ['scheduled'=>'Planeeritud','completed'=>'Tehtud','overdue'=>'Hilines','pending'=>'Ootel','cancelled'=>'Tühistatud'];
    ?>
    <style>
    body{font-family:Arial,sans-serif;font-size:13px;color:#111;margin:0;padding:24px}
    .hist-header{display:flex;justify-content:space-between;margin-bottom:16px;border-bottom:2px solid #1e293b;padding-bottom:12px}
    table{width:100%;border-collapse:collapse;margin:12px 0}
    th{background:#f0f4f8;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
    td{padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13px}
    @media print{button{display:none}}
    </style>
    <div class="hist-header">
        <div>
            <div style="font-size:22px;font-weight:700;color:#1e293b">Hooldusajalugu</div>
            <div style="font-size:16px;margin-top:4px"><?php echo esc_html($client->name); ?></div>
            <?php if (!empty($client->address)) echo '<div style="font-size:12px;color:#64748b">'.esc_html($client->address).'</div>'; ?>
            <?php if (!empty($client->phone))   echo '<div style="font-size:12px;color:#64748b">'.esc_html($client->phone).'</div>'; ?>
            <?php if (!empty($client->email))   echo '<div style="font-size:12px;color:#64748b">'.esc_html($client->email).'</div>'; ?>
        </div>
        <div style="text-align:right">
            <div style="font-weight:700;font-size:15px"><?php echo esc_html($co_name); ?></div>
            <div style="font-size:12px;color:#64748b">Genereeritud: <?php echo date('d.m.Y'); ?></div>
        </div>
    </div>
    <p style="color:#64748b;font-size:13px">Hooldusi kokku: <strong><?php echo count($maintenances); ?></strong></p>
    <table>
        <thead><tr><th>Seade</th><th>Planeeritud</th><th>Tehtud</th><th>Staatus</th><th>Kirjeldus</th></tr></thead>
        <tbody>
        <?php foreach ($maintenances as $m) : ?>
        <tr>
            <td><?php echo esc_html($m->device_name . ($m->device_model ? ' ('.$m->device_model.')' : '')); ?></td>
            <td><?php echo $m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'; ?></td>
            <td><?php echo $m->completed_date ? date('d.m.Y', strtotime($m->completed_date)) : '—'; ?></td>
            <td><?php echo esc_html($status_labels[$m->status] ?? $m->status); ?></td>
            <td><?php echo esc_html($m->description ?: '—'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top:20px;text-align:right">
        <button onclick="window.print()" style="padding:8px 20px;background:#1e293b;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Prindi / Salvesta PDF</button>
    </div>
    <script>window.onload=function(){window.print();};</script>
    <?php
    return;
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">👥 Kliendid <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if ( isset($_GET['msg']) ) :
        $msg   = sanitize_text_field($_GET['msg']);
        $count = absint($_GET['count'] ?? 0);
        $cid   = absint($_GET['cid']   ?? 0);
        if ( $msg === 'blocked_invoices' ) : ?>
            <div class="crm-alert crm-alert-error">
                ⛔ Klienti ei saa kustutada — tal on <strong><?php echo $count; ?> maksmata arve<?php echo $count > 1 ? 't' : ''; ?></strong>.
                <?php if ($cid) : ?><a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&status=unpaid'); ?>" style="margin-left:8px;text-decoration:underline">Vaata arveid →</a><?php endif; ?>
            </div>
        <?php elseif ( $msg === 'blocked_workorders' ) : ?>
            <div class="crm-alert crm-alert-error">
                ⛔ Klienti ei saa kustutada — tal on <strong><?php echo $count; ?> avatud töökäsk<?php echo $count > 1 ? 'u' : ''; ?></strong>.
                <?php if ($cid) : ?><a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=view&client_id='.$cid); ?>" style="margin-left:8px;text-decoration:underline">Vaata töökäske →</a><?php endif; ?>
            </div>
        <?php else :
            $msgs = ['added'=>'Klient lisatud!','updated'=>'Klient uuendatud!','deleted'=>'Klient kustutatud!'];
            echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$msg] ?? 'Muudatused salvestatud!').'</div>';
        endif;
    endif; ?>

    <?php if ($action === 'view' && $client_id) :
        $c = Vesho_CRM_Database::get_client($client_id);
        if (!$c) { echo '<div class="crm-alert crm-alert-error">Klienti ei leitud.</div>'; return; }
        // Load related data
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d ORDER BY name ASC", $client_id));
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE client_id=%d ORDER BY created_at DESC LIMIT 50", $client_id));
        $maintenances = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, d.name as device_name FROM {$wpdb->prefix}vesho_maintenances m
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             WHERE d.client_id=%d ORDER BY m.scheduled_date DESC LIMIT 50", $client_id));
        $workorders = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, w.name as worker_name FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id=wo.worker_id
             WHERE wo.client_id=%d ORDER BY wo.created_at DESC LIMIT 50", $client_id));
        $status_labels = ['pending'=>'Ootel','scheduled'=>'Planeeritud','completed'=>'Tehtud','cancelled'=>'Tühistatud','open'=>'Avatud','assigned'=>'Määratud','in_progress'=>'Töös','paid'=>'Makstud','overdue'=>'Tähtaeg möödas','sent'=>'Saadetud','draft'=>'Mustand'];
    ?>
    <div class="crm-card" style="margin-bottom:20px">
        <div class="crm-card-header">
            <div>
                <span class="crm-card-title"><?php echo esc_html($c->name); ?></span>
                <?php if (!empty($c->company)) echo '<span style="margin-left:8px;color:#64748b;font-size:13px">'.esc_html($c->company).'</span>'; ?>
            </div>
            <div style="display:flex;gap:8px">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$c->id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">✏️ Muuda</a>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
            </div>
        </div>
        <div style="padding:16px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px 24px">
            <?php if (!empty($c->email))   echo '<div><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">E-post</span><br><a href="mailto:'.esc_attr($c->email).'">'.esc_html($c->email).'</a></div>'; ?>
            <?php if (!empty($c->phone))   echo '<div><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Telefon</span><br>'.esc_html($c->phone).'</div>'; ?>
            <?php if (!empty($c->address)) echo '<div><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Aadress</span><br>'.esc_html($c->address).'</div>'; ?>
            <?php if (!empty($c->reg_code))echo '<div><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">Reg. kood</span><br>'.esc_html($c->reg_code).'</div>'; ?>
            <?php if (!empty($c->vat_number))echo '<div><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600">KMKR</span><br>'.esc_html($c->vat_number).'</div>'; ?>
        </div>
    </div>

    <!-- Quick-add buttons -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&action=add&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">+ Seade</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=add&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">+ Arve</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&action=add&device_id=0&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">+ Hooldus</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=add&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">+ Töökäsk</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=history-pdf&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm" target="_blank">📋 Hooldusajalugu PDF</a>
    </div>

    <!-- Tabs -->
    <div class="crm-tabs" style="margin-bottom:0">
        <a class="crm-tab is-active" href="#" data-tab="devices">🔧 Seadmed <span class="crm-tab__count"><?php echo count($devices); ?></span></a>
        <a class="crm-tab" href="#" data-tab="invoices">🧾 Arved <span class="crm-tab__count"><?php echo count($invoices); ?></span></a>
        <a class="crm-tab" href="#" data-tab="maintenances">📅 Hooldused <span class="crm-tab__count"><?php echo count($maintenances); ?></span></a>
        <a class="crm-tab" href="#" data-tab="workorders">🛠️ Töökäsud <span class="crm-tab__count"><?php echo count($workorders); ?></span></a>
    </div>

    <!-- Devices -->
    <div class="crm-card crm-client-tab" id="tab-devices">
        <?php if (empty($devices)) : ?><div class="crm-empty">Seadmeid pole.</div><?php else : ?>
        <table class="crm-table"><thead><tr><th>Seade</th><th>Mudel</th><th>Seerianumber</th><th>Lisatud</th><th class="td-actions">Toimingud</th></tr></thead><tbody>
        <?php foreach ($devices as $d) : ?>
        <tr>
            <td><strong><?php echo esc_html($d->name); ?></strong></td>
            <td><?php echo esc_html($d->model ?? '–'); ?></td>
            <td><?php echo esc_html($d->serial_number ?? '–'); ?></td>
            <td><?php echo vesho_crm_format_date($d->created_at, 'd.m.Y'); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&action=edit&device_id='.$d->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&device_id='.$d->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Hooldused">📅</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>

    <!-- Invoices -->
    <div class="crm-card crm-client-tab" id="tab-invoices" style="display:none">
        <?php if (empty($invoices)) : ?><div class="crm-empty">Arveid pole.</div><?php else : ?>
        <table class="crm-table"><thead><tr><th>Nr</th><th>Kuupäev</th><th>Tähtaeg</th><th>Summa</th><th>Staatus</th><th class="td-actions">Toimingud</th></tr></thead><tbody>
        <?php foreach ($invoices as $inv) : ?>
        <tr>
            <td><strong><?php echo esc_html($inv->invoice_number); ?></strong></td>
            <td><?php echo vesho_crm_format_date($inv->invoice_date); ?></td>
            <td><?php echo vesho_crm_format_date($inv->due_date); ?></td>
            <td><?php echo vesho_crm_format_money($inv->amount); ?></td>
            <td><?php echo vesho_crm_status_badge($inv->status); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-invoices&action=edit&invoice_id='.$inv->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>

    <!-- Maintenances -->
    <div class="crm-card crm-client-tab" id="tab-maintenances" style="display:none">
        <?php if (empty($maintenances)) : ?><div class="crm-empty">Hooldusi pole.</div><?php else : ?>
        <table class="crm-table"><thead><tr><th>Seade</th><th>Planeeritud</th><th>Tehtud</th><th>Staatus</th><th>Hind</th><th class="td-actions">Toimingud</th></tr></thead><tbody>
        <?php foreach ($maintenances as $m) : ?>
        <tr>
            <td><?php echo esc_html($m->device_name ?? '–'); ?></td>
            <td><?php echo vesho_crm_format_date($m->scheduled_date); ?></td>
            <td><?php echo vesho_crm_format_date($m->completed_date); ?></td>
            <td><?php echo vesho_crm_status_badge($m->status); ?></td>
            <td><?php echo $m->locked_price !== null ? vesho_crm_format_money($m->locked_price) : '–'; ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&action=edit&maintenance_id='.$m->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>

    <!-- Workorders -->
    <div class="crm-card crm-client-tab" id="tab-workorders" style="display:none">
        <?php if (empty($workorders)) : ?><div class="crm-empty">Töökäske pole.</div><?php else : ?>
        <table class="crm-table"><thead><tr><th>Pealkiri</th><th>Töötaja</th><th>Kuupäev</th><th>Prioriteet</th><th>Staatus</th><th class="td-actions">Toimingud</th></tr></thead><tbody>
        <?php foreach ($workorders as $wo) : ?>
        <tr>
            <td><strong><?php echo esc_html($wo->title); ?></strong></td>
            <td><?php echo esc_html($wo->worker_name ?? '–'); ?></td>
            <td><?php echo vesho_crm_format_date($wo->scheduled_date); ?></td>
            <td><?php echo vesho_crm_status_badge($wo->priority ?? 'normal'); ?></td>
            <td><?php echo vesho_crm_status_badge($wo->status); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&action=edit&workorder_id='.$wo->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
            </td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>

    <script>
    document.querySelectorAll('.crm-tab[data-tab]').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.crm-tab[data-tab]').forEach(function(t) { t.classList.remove('is-active'); });
            document.querySelectorAll('.crm-client-tab').forEach(function(p) { p.style.display = 'none'; });
            this.classList.add('is-active');
            document.getElementById('tab-' + this.dataset.tab).style.display = '';
        });
    });
    </script>

    <?php elseif ($action === 'edit' || $action === 'add') : ?>
    <!-- ── Add / Edit form ── -->
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action === 'edit' ? 'Muuda klienti' : 'Lisa uus klient'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_client'); ?>
            <input type="hidden" name="action" value="vesho_save_client">
            <?php if ($edit_client) : ?>
                <input type="hidden" name="client_id" value="<?php echo $edit_client->id; ?>">
            <?php endif; ?>

            <!-- Tüüp toggle -->
            <div class="crm-form-group crm-form-full" style="margin-bottom:16px">
                <label class="crm-form-label">Kliendi tüüp</label>
                <div style="display:flex;gap:8px;margin-top:4px">
                    <label style="cursor:pointer">
                        <input type="radio" name="client_type" value="eraisik" id="ct_eraisik"
                            <?php checked($edit_client->client_type ?? 'eraisik', 'eraisik'); ?>>
                        <span class="ct-btn" data-type="eraisik" style="display:inline-block;padding:6px 16px;border:2px solid #ddd;border-radius:6px;font-weight:600;font-size:13px;transition:.15s">👤 Eraisik</span>
                    </label>
                    <label style="cursor:pointer">
                        <input type="radio" name="client_type" value="ettevote" id="ct_ettevote"
                            <?php checked($edit_client->client_type ?? '', 'ettevote'); ?>>
                        <span class="ct-btn" data-type="ettevote" style="display:inline-block;padding:6px 16px;border:2px solid #ddd;border-radius:6px;font-weight:600;font-size:13px;transition:.15s">🏢 Ettevõte</span>
                    </label>
                </div>
            </div>

            <div class="crm-form-grid">
                <div class="crm-form-group" id="lbl-name-group">
                    <label class="crm-form-label" id="lbl-name">Täisnimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit_client->name ?? $prefill['name'] ?? ''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">E-post *</label>
                    <input class="crm-form-input" type="email" name="email" value="<?php echo esc_attr($edit_client->email ?? $prefill['email'] ?? ''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Telefon</label>
                    <input class="crm-form-input" type="text" name="phone" value="<?php echo esc_attr($edit_client->phone ?? $prefill['phone'] ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field" id="company-group">
                    <label class="crm-form-label">Ettevõtte nimi *</label>
                    <input class="crm-form-input" type="text" name="company" id="company_input" value="<?php echo esc_attr($edit_client->company ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field">
                    <label class="crm-form-label">Reg. kood</label>
                    <input class="crm-form-input" type="text" name="reg_code" value="<?php echo esc_attr($edit_client->reg_code ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field">
                    <label class="crm-form-label">KM registreering</label>
                    <input class="crm-form-input" type="text" name="vat_number" value="<?php echo esc_attr($edit_client->vat_number ?? ''); ?>">
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Aadress</label>
                    <input class="crm-form-input" type="text" name="address" value="<?php echo esc_attr($edit_client->address ?? ''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Portaali parool <?php echo $edit_client ? '(tühi = ei muutu)' : ''; ?></label>
                    <input class="crm-form-input" type="password" name="password" placeholder="<?php echo $edit_client ? 'Jäta tühjaks muutmata jätmiseks' : 'Portaali ligipääsu parool'; ?>" autocomplete="new-password">
                </div>
                <?php if ($edit_client && $edit_client->user_id) :
                    $wp_u = get_userdata($edit_client->user_id); ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">WP kasutaja</label>
                    <div style="padding:8px 12px;background:#f0f9f0;border:1px solid #b8e6b8;border-radius:6px;font-size:13px;color:#2d6a2d">
                        ✅ <?php echo $wp_u ? esc_html($wp_u->user_login) : 'Kasutaja #'.$edit_client->user_id; ?>
                        &nbsp;·&nbsp;<a href="<?php echo admin_url('user-edit.php?user_id='.$edit_client->user_id); ?>" target="_blank">WP profiil →</a>
                    </div>
                </div>
                <?php elseif ($edit_client) : ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">WP kasutaja</label>
                    <div style="padding:8px 12px;background:#fff8e1;border:1px solid #f0d060;border-radius:6px;font-size:13px;color:#7a5c00">
                        ⚠️ Portaali ligipääs puudub — salvesta parooliga et luua
                    </div>
                </div>
                <?php endif; ?>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Märkused</label>
                    <textarea class="crm-form-textarea" name="notes"><?php echo esc_textarea($edit_client->notes ?? ''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <script>
    (function(){
        var radios = document.querySelectorAll('input[name="client_type"]');
        var firmaFields = document.querySelectorAll('.firma-field');
        var lblName = document.getElementById('lbl-name');
        var companyInput = document.getElementById('company_input');
        function updateType(val){
            var isFirma = val === 'ettevote';
            firmaFields.forEach(function(el){ el.style.display = isFirma ? '' : 'none'; });
            if(lblName) lblName.textContent = isFirma ? 'Kontaktisik *' : 'Täisnimi *';
            if(companyInput) companyInput.required = isFirma;
            document.querySelectorAll('.ct-btn').forEach(function(btn){
                var active = btn.dataset.type === val;
                btn.style.borderColor = active ? '#2271b1' : '#ddd';
                btn.style.background  = active ? '#e8f0fb' : '';
                btn.style.color       = active ? '#2271b1' : '';
            });
        }
        radios.forEach(function(r){
            r.addEventListener('change', function(){ updateType(this.value); });
            if(r.checked) updateType(r.value);
        });
        // init
        var checked = document.querySelector('input[name="client_type"]:checked');
        if(checked) updateType(checked.value);
    })();
    </script>
    <?php else : ?>

    <!-- ── List ── -->
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa klient</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-clients">
                <input class="crm-search" type="search" name="s" placeholder="Otsi nime, e-posti, telefoni..." value="<?php echo esc_attr($search); ?>">
                <select class="crm-filter" name="type">
                    <option value="">Kõik tüübid</option>
                    <option value="eraisik" <?php selected($type_f,'eraisik'); ?>>Eraisik</option>
                    <option value="ettevote" <?php selected($type_f,'ettevote'); ?>>Ettevõte</option>
                </select>
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($clients)) : ?>
            <div class="crm-empty">Kliente ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimi</th><th>Ettevõte</th><th>E-post</th><th>Telefon</th><th>Maksmata</th><th>Tüüp</th><th>Portaal</th><th>Lisatud</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($clients as $c) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $c->id; ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=view&client_id='.$c->id); ?>" style="font-weight:600"><?php echo esc_html($c->name); ?></a></td>
                <td><?php echo esc_html($c->company ?? '–'); ?></td>
                <td><a href="mailto:<?php echo esc_attr($c->email); ?>"><?php echo esc_html($c->email); ?></a></td>
                <td><?php echo esc_html($c->phone ?: '–'); ?></td>
                <td>
                    <?php $_up = $_unpaid_map[$c->id] ?? null; ?>
                    <?php if ($_up && (int)$_up->cnt > 0) : ?>
                        <span style="color:#b91c1c;font-weight:600;font-size:12px"><?php echo (int)$_up->cnt; ?> arvet</span><br>
                        <span style="color:#dc2626;font-size:11px"><?php echo number_format((float)$_up->total,0,',','&nbsp;'); ?> €</span>
                    <?php else : ?>
                        <span style="color:#6b8599;font-size:12px">–</span>
                    <?php endif; ?>
                </td>
                <td><?php echo vesho_crm_status_badge($c->client_type); ?></td>
                <td>
                    <?php if (!empty($c->user_id)) : ?>
                        <span class="crm-badge badge-success" title="WP kasutaja #<?php echo $c->user_id; ?>">✅ Aktiivne</span>
                    <?php else : ?>
                        <span class="crm-badge badge-gray">– Puudub</span>
                    <?php endif; ?>
                </td>
                <td><?php echo vesho_crm_format_date($c->created_at, 'd.m.Y'); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$c->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&client_id='.$c->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Seadmed">🔧</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=history-pdf&client_id='.$c->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Hooldusajalugu PDF" target="_blank">📋</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_client_send_access&client_id='.$c->id),'vesho_client_send_access'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Saada portaali ligipääs e-postile"
                       onclick="return confirm('Saada kliendile portaali parool e-postiga?')">📧</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_client&client_id='.$c->id), 'vesho_delete_client'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Kustuta"
                       onclick="return confirm('<?php esc_attr_e('Kustuta klient?','vesho-crm'); ?>')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pages > 1) : ?>
        <div class="crm-pager">
            <?php for ($p=1; $p<=$pages; $p++) : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&paged='.$p.($search?'&s='.urlencode($search):'')); ?>"
                   <?php if ($p===$paged) echo 'class="current"'; ?>><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script>
// Live search — auto-submit on keyup
(function(){
    var inp = document.querySelector('input[name="s"].crm-search');
    if (!inp) return;
    var t;
    inp.addEventListener('input', function(){ clearTimeout(t); t = setTimeout(function(){ inp.closest('form').submit(); }, 350); });
})();
</script>
