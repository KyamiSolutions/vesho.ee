<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

// --- Month & filter params ---
$raw_month     = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
if ( ! preg_match('/^\d{4}-\d{2}$/', $raw_month) ) {
    $raw_month = date('Y-m');
}
$client_filter = isset($_GET['client_filter']) ? sanitize_text_field($_GET['client_filter']) : '';

$year  = (int) substr($raw_month, 0, 4);
$month = (int) substr($raw_month, 5, 2);
if ( $month < 1 || $month > 12 ) $month = (int)date('m');

$month_start = sprintf('%04d-%02d-01', $year, $month);
$month_end   = date('Y-m-t', strtotime($month_start));

$prev_month = date('Y-m', strtotime('-1 month', strtotime($month_start)));
$next_month = date('Y-m', strtotime('+1 month', strtotime($month_start)));

$et_months = ['','Jaanuar','Veebruar','Märts','Aprill','Mai','Juuni','Juuli','August','September','Oktoober','November','Detsember'];
$month_label = ($et_months[$month] ?? date('F', mktime(0,0,0,$month,1,$year))) . ' ' . $year;

// --- Load maintenances for this month ---
$where_client = '';
if ( $client_filter ) {
    $where_client = $wpdb->prepare(' AND c.name LIKE %s', '%' . $wpdb->esc_like($client_filter) . '%');
}

$maintenances = $wpdb->get_results( $wpdb->prepare(
    "SELECT m.id, m.device_id, m.scheduled_date, m.status, m.description, m.worker_id,
            d.name as device_name, c.name as client_name, c.id as client_id,
            w.name as worker_name
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON m.worker_id = w.id
     WHERE m.scheduled_date BETWEEN %s AND %s $where_client
     ORDER BY m.scheduled_date ASC",
    $month_start, $month_end
) );

// Group by date
$by_date = [];
foreach ( $maintenances as $m ) {
    $by_date[$m->scheduled_date][] = $m;
}

// All clients for datalist
$all_clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_clients ORDER BY name ASC");
// All workers for add modal
$all_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");

// Status colors [bg, text]
$status_colors = [
    'scheduled' => ['#dbeafe', '#1d4ed8'],
    'completed' => ['#dcfce7', '#16a34a'],
    'cancelled' => ['#fee2e2', '#dc2626'],
    'pending'   => ['#fef3c7', '#92400e'],
];

// Calendar grid setup
$first_day_ts   = mktime(0, 0, 0, $month, 1, $year);
$days_in_month  = (int) date('t', $first_day_ts);
$first_weekday  = (int) date('N', $first_day_ts); // 1=Mon, 7=Sun
$day_names      = ['E', 'T', 'K', 'N', 'R', 'L', 'P'];

// Helper: month label
function vesho_month_label_str($year, $month) {
    $months_et = ['', 'Jaanuar', 'Veebruar', 'Märts', 'Aprill', 'Mai', 'Juuni',
                  'Juuli', 'August', 'September', 'Oktoober', 'November', 'Detsember'];
    return ($months_et[$month] ?? '') . ' ' . $year;
}

$ajax_nonce = wp_create_nonce('vesho_admin_nonce');
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📅 Kalender</h1>

    <!-- Toolbar -->
    <div class="crm-card" style="margin-bottom:16px">
        <div class="crm-toolbar" style="flex-wrap:wrap;gap:12px;padding:14px 16px">
            <!-- Month navigation -->
            <div style="display:flex;align-items:center;gap:8px">
                <a href="<?php echo esc_url(add_query_arg(['page'=>'vesho-crm-calendar','month'=>$prev_month], admin_url('admin.php'))); ?>"
                   class="crm-btn crm-btn-outline crm-btn-sm">← Eelmine</a>
                <strong style="font-size:15px;min-width:160px;text-align:center"><?php echo esc_html(vesho_month_label_str($year, $month)); ?></strong>
                <a href="<?php echo esc_url(add_query_arg(['page'=>'vesho-crm-calendar','month'=>$next_month], admin_url('admin.php'))); ?>"
                   class="crm-btn crm-btn-outline crm-btn-sm">Järgmine →</a>
                <a href="<?php echo esc_url(add_query_arg(['page'=>'vesho-crm-calendar','month'=>date('Y-m')], admin_url('admin.php'))); ?>"
                   class="crm-btn crm-btn-sm" style="background:#6b7280;color:#fff">Täna</a>
            </div>
            <!-- Client filter -->
            <form method="GET" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="page" value="vesho-crm-calendar">
                <input type="hidden" name="month" value="<?php echo esc_attr($raw_month); ?>">
                <input type="text" name="client_filter"
                       placeholder="Filtreeri kliendi järgi..."
                       value="<?php echo esc_attr($client_filter); ?>"
                       class="crm-form-input" style="width:200px;padding:6px 10px;font-size:13px"
                       list="cal-clients-list">
                <datalist id="cal-clients-list">
                    <?php foreach ($all_clients as $cl) : ?>
                        <option value="<?php echo esc_attr($cl->name); ?>">
                    <?php endforeach; ?>
                </datalist>
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Filtreeri</button>
                <?php if ($client_filter) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['page'=>'vesho-crm-calendar','month'=>$raw_month], admin_url('admin.php'))); ?>"
                       class="crm-btn crm-btn-outline crm-btn-sm">✕</a>
                <?php endif; ?>
            </form>
            <!-- Add button -->
            <button type="button" class="crm-btn crm-btn-primary" onclick="openAddModal()">+ Lisa hooldus</button>
        </div>
    </div>

    <!-- Calendar grid -->
    <div class="crm-card">
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:700px">
            <thead>
            <tr>
                <?php foreach ($day_names as $dn) : ?>
                    <th style="padding:8px;text-align:center;font-size:12px;font-weight:600;color:#6b7280;border-bottom:1px solid #e5e7eb;background:#f9fafb"><?php echo $dn; ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $today = date('Y-m-d');
            $cell  = 0;
            $day   = 1;
            // We need to render weeks
            $total_cells = $first_weekday - 1 + $days_in_month;
            $rows = ceil($total_cells / 7);
            for ($row = 0; $row < $rows; $row++) :
            ?>
            <tr>
                <?php for ($col = 1; $col <= 7; $col++) :
                    $cell_num = $row * 7 + $col;
                    $cur_day  = $cell_num - ($first_weekday - 1);
                    $is_valid = ($cur_day >= 1 && $cur_day <= $days_in_month);
                    $date_str = $is_valid ? sprintf('%04d-%02d-%02d', $year, $month, $cur_day) : '';
                    $is_today = ($date_str === $today);
                    $events   = $is_valid ? ($by_date[$date_str] ?? []) : [];
                ?>
                <td style="vertical-align:top;border:1px solid #e5e7eb;padding:6px;min-height:90px;width:14.28%;<?php echo $is_today ? 'background:#eff6ff;' : ''; ?>">
                    <?php if ($is_valid) : ?>
                    <div style="font-size:12px;font-weight:<?php echo $is_today ? '700' : '500'; ?>;color:<?php echo $is_today ? '#1d4ed8' : '#374151'; ?>;margin-bottom:4px">
                        <?php echo $cur_day; ?>
                    </div>
                    <?php foreach ($events as $ev) :
                        $col_pair = $status_colors[$ev->status] ?? ['#f3f4f6','#374151'];
                        $short    = mb_substr($ev->client_name ?: 'Klient', 0, 18);
                        $ev_id    = (int)$ev->id;
                    ?>
                    <div class="cal-pill"
                         style="background:<?php echo esc_attr($col_pair[0]); ?>;color:<?php echo esc_attr($col_pair[1]); ?>;padding:2px 6px;border-radius:4px;font-size:11px;margin-bottom:2px;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"
                         onclick="openEventModal(<?php echo $ev_id; ?>)"
                         title="<?php echo esc_attr($ev->client_name . ' – ' . $ev->device_name); ?>">
                        <?php echo esc_html($short); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <?php endfor; ?>
            </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Legend -->
    <div style="display:flex;gap:16px;margin-top:12px;flex-wrap:wrap">
        <?php
        $legend = ['pending'=>'Ootel', 'scheduled'=>'Planeeritud', 'completed'=>'Tehtud', 'cancelled'=>'Tühistatud'];
        foreach ($legend as $st => $lbl) :
            $cp = $status_colors[$st];
        ?>
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px">
            <span style="width:12px;height:12px;border-radius:3px;background:<?php echo $cp[0]; ?>;border:1px solid <?php echo $cp[1]; ?>"></span>
            <?php echo $lbl; ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Event detail modal -->
<div id="vesho-event-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:24px;min-width:360px;max-width:500px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="margin:0;font-size:15px" id="event-modal-title">Hoolduse detailid</h3>
            <button onclick="closeEventModal()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280">✕</button>
        </div>
        <div id="event-modal-body" style="font-size:13px;color:#374151;line-height:1.6"></div>
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap" id="event-modal-actions"></div>
        <!-- Postpone inline -->
        <div id="postpone-area" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb">
            <label style="font-size:13px;font-weight:500">Uus kuupäev:</label>
            <input type="date" id="postpone-date" class="crm-form-input" style="margin-top:4px;width:100%">
            <div style="display:flex;gap:8px;margin-top:8px">
                <button class="crm-btn crm-btn-outline crm-btn-sm" onclick="document.getElementById('postpone-area').style.display='none'">Tühista</button>
                <button class="crm-btn crm-btn-primary crm-btn-sm" onclick="doPostpone()">Salvesta kuupäev</button>
            </div>
        </div>
    </div>
</div>

<!-- Add maintenance modal -->
<div id="vesho-add-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:24px;min-width:400px;max-width:560px;box-shadow:0 8px 32px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0;font-size:15px">Lisa hooldus</h3>
            <button onclick="closeAddModal()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#6b7280">✕</button>
        </div>
        <div id="add-modal-msg" style="display:none;padding:8px 12px;border-radius:6px;margin-bottom:12px;font-size:13px"></div>
        <div class="crm-form-grid">
            <div class="crm-form-group crm-form-full">
                <label class="crm-form-label">Klient *</label>
                <input type="text" id="add-client-search" class="crm-form-input" placeholder="Kirjuta kliendi nimi..." list="add-clients-list" autocomplete="off">
                <datalist id="add-clients-list">
                    <?php foreach ($all_clients as $cl) : ?>
                        <option data-id="<?php echo (int)$cl->id; ?>" value="<?php echo esc_attr($cl->name); ?>">
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" id="add-client-id" value="">
            </div>
            <div class="crm-form-group crm-form-full">
                <label class="crm-form-label">Seade *</label>
                <select id="add-device-id" class="crm-form-select">
                    <option value="">— Vali klient esmalt —</option>
                </select>
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Kuupäev *</label>
                <input type="date" id="add-date" class="crm-form-input" value="<?php echo esc_attr($month_start); ?>">
            </div>
            <div class="crm-form-group">
                <label class="crm-form-label">Töötaja</label>
                <select id="add-worker-id" class="crm-form-select">
                    <option value="">— Vali töötaja —</option>
                    <?php foreach ($all_workers as $w) : ?>
                        <option value="<?php echo (int)$w->id; ?>"><?php echo esc_html($w->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="crm-form-group crm-form-full">
                <label class="crm-form-label">Kirjeldus</label>
                <textarea id="add-description" class="crm-form-input" rows="3" style="resize:vertical"></textarea>
            </div>
        </div>
        <div class="crm-form-actions" style="margin-top:16px">
            <button type="button" class="crm-btn crm-btn-outline" onclick="closeAddModal()">Tühista</button>
            <button type="button" class="crm-btn crm-btn-primary" onclick="doAddMaintenance()">+ Lisa hooldus</button>
        </div>
    </div>
</div>

<script>
var veshoAjaxUrl  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var veshoNonce    = '<?php echo esc_js($ajax_nonce); ?>';
var veshoRemindersUrl = '<?php echo esc_js(admin_url('admin.php?page=vesho-crm-reminders')); ?>';

// All client data for datalist matching
var veshoClients = <?php
    $clients_js = [];
    foreach ($all_clients as $cl) {
        $clients_js[] = ['id' => (int)$cl->id, 'name' => $cl->name];
    }
    echo json_encode($clients_js);
?>;

// All maintenance data for event modal
var veshoEvents = <?php
    $ev_js = [];
    foreach ($maintenances as $m) {
        $ev_js[(int)$m->id] = [
            'id'          => (int)$m->id,
            'client'      => $m->client_name ?: '–',
            'device'      => $m->device_name ?: '–',
            'date'        => $m->scheduled_date ?: '',
            'status'      => $m->status,
            'description' => $m->description ?: '',
            'worker'      => $m->worker_name ?: '–',
        ];
    }
    echo json_encode($ev_js);
?>;

var statusLabels = {pending:'Ootel', scheduled:'Planeeritud', completed:'Tehtud', cancelled:'Tühistatud'};
var currentEventId = null;

/* ---- Event modal ---- */
function openEventModal(id) {
    currentEventId = id;
    var ev = veshoEvents[id];
    if (!ev) return;
    var dateStr = ev.date ? ev.date.split('-').reverse().join('.') : '–';
    document.getElementById('event-modal-title').textContent = ev.client + ' – ' + ev.device;
    document.getElementById('event-modal-body').innerHTML =
        '<b>Kuupäev:</b> ' + dateStr + '<br>' +
        '<b>Staatus:</b> ' + (statusLabels[ev.status] || ev.status) + '<br>' +
        '<b>Töötaja:</b> ' + ev.worker + '<br>' +
        (ev.description ? '<b>Kirjeldus:</b> ' + ev.description : '');
    document.getElementById('postpone-area').style.display = 'none';
    if (ev.date) document.getElementById('postpone-date').value = ev.date;

    var acts = document.getElementById('event-modal-actions');
    acts.innerHTML = '<a href="<?php echo esc_js(admin_url('admin.php?page=vesho-crm-maintenances&action=edit&maintenance_id=')); ?>' + id +
        '" class="crm-btn crm-btn-sm crm-btn-outline">✏️ Muuda</a>' +
        '<button class="crm-btn crm-btn-sm" style="background:#f59e0b;color:#fff" onclick="showPostpone()">📅 Edasilükka</button>';

    document.getElementById('vesho-event-modal').style.display = 'flex';
}
function closeEventModal() {
    document.getElementById('vesho-event-modal').style.display = 'none';
    currentEventId = null;
}
function showPostpone() {
    document.getElementById('postpone-area').style.display = 'block';
}
function doPostpone() {
    var newDate = document.getElementById('postpone-date').value;
    if (!newDate || !currentEventId) return;
    fetch(veshoAjaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=vesho_postpone_maintenance&maintenance_id=' + currentEventId + '&new_date=' + encodeURIComponent(newDate) + '&_ajax_nonce=' + veshoNonce
    }).then(function(r){ return r.json(); }).then(function(data){
        if (data.success) {
            closeEventModal();
            window.location.reload();
        } else {
            alert('Viga: ' + (data.data || 'Teadmata viga'));
        }
    });
}
document.getElementById('vesho-event-modal').addEventListener('click', function(e){ if(e.target===this) closeEventModal(); });

/* ---- Add modal ---- */
function openAddModal() {
    document.getElementById('add-modal-msg').style.display = 'none';
    document.getElementById('vesho-add-modal').style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('vesho-add-modal').style.display = 'none';
}
document.getElementById('vesho-add-modal').addEventListener('click', function(e){ if(e.target===this) closeAddModal(); });

// Client search -> load devices
document.getElementById('add-client-search').addEventListener('change', function() {
    var name = this.value.trim();
    var clientId = '';
    for (var i=0; i<veshoClients.length; i++) {
        if (veshoClients[i].name === name) { clientId = veshoClients[i].id; break; }
    }
    document.getElementById('add-client-id').value = clientId;
    var sel = document.getElementById('add-device-id');
    sel.innerHTML = '<option value="">Laen seadmeid...</option>';
    if (!clientId) {
        sel.innerHTML = '<option value="">— Vali klient esmalt —</option>';
        return;
    }
    fetch(veshoAjaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=vesho_get_client_devices&client_id=' + clientId + '&_ajax_nonce=' + veshoNonce
    }).then(function(r){ return r.json(); }).then(function(data){
        sel.innerHTML = '<option value="">— Vali seade —</option>';
        if (data.success && data.data) {
            data.data.forEach(function(d){
                sel.innerHTML += '<option value="' + d.id + '">' + d.name + '</option>';
            });
        }
    });
});

function doAddMaintenance() {
    var clientId  = document.getElementById('add-client-id').value;
    var deviceId  = document.getElementById('add-device-id').value;
    var date      = document.getElementById('add-date').value;
    var workerId  = document.getElementById('add-worker-id').value;
    var desc      = document.getElementById('add-description').value;

    if (!deviceId || !date) {
        alert('Palun vali seade ja kuupäev.');
        return;
    }
    fetch(veshoAjaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=vesho_add_maintenance_ajax' +
              '&device_id=' + encodeURIComponent(deviceId) +
              '&scheduled_date=' + encodeURIComponent(date) +
              '&worker_id=' + encodeURIComponent(workerId) +
              '&description=' + encodeURIComponent(desc) +
              '&_ajax_nonce=' + veshoNonce
    }).then(function(r){ return r.json(); }).then(function(data){
        if (data.success) {
            closeAddModal();
            window.location.reload();
        } else {
            var msg = document.getElementById('add-modal-msg');
            msg.style.display = 'block';
            msg.style.background = '#fee2e2';
            msg.style.color = '#dc2626';
            msg.textContent = 'Viga: ' + (data.data || 'Teadmata viga');
        }
    });
}
</script>
