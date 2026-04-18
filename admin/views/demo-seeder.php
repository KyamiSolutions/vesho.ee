<?php
if ( ! defined('ABSPATH') ) exit;
if ( ! current_user_can('manage_options') ) wp_die('Puuduvad õigused.');

// Only on localhost / WP_DEBUG
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1','::1'])
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')
    || ( defined('WP_DEBUG') && WP_DEBUG );

if ( ! $is_local ) {
    wp_die('Demo seeder on saadaval ainult arenduskeskkonnas.');
}

global $wpdb;
$p = $wpdb->prefix;
$msg = '';

// ── Handle seed action ────────────────────────────────────────────────────────
if ( isset($_POST['vesho_seed']) && check_admin_referer('vesho_demo_seed') ) {

    $type = sanitize_key($_POST['vesho_seed']);

    if ( $type === 'all' || $type === 'clients' ) {
        $clients = [
            ['Mart Tamm',      'mart.tamm@gmail.com',    '+372 5123 4567', 'Pärnu mnt 15-3, Tallinn', 'eraisik', null, null],
            ['Mari Mägi',      'mari.magi@hotmail.com',  '+372 5234 5678', 'Liivalaia 12, Tallinn',   'eraisik', null, null],
            ['OÜ Tehnoabi',    'info@tehnoabi.ee',       '+372 5345 6789', 'Mustamäe tee 44, Tallinn','firma',   '12345678', 'EE101234567'],
            ['Jüri Kask',      'jyri.kask@gmail.com',   '+372 5456 7890', 'Tartu mnt 80, Tallinn',   'eraisik', null, null],
            ['AS Külmakeskus', 'info@kylmakeskus.ee',   '+372 5567 8901', 'Peterburi tee 2, Tallinn','firma',   '87654321', 'EE987654321'],
            ['Tiina Lepik',    'tiina@vesho.ee',         '+372 5678 9012', 'Kadriorg, Tallinn',       'eraisik', null, null],
        ];
        $pwd = wp_hash_password('demo1234');
        foreach ($clients as [$name, $email, $phone, $addr, $type2, $reg, $vat]) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}vesho_clients WHERE email=%s", $email));
            if (!$exists) {
                $wpdb->insert("{$p}vesho_clients", [
                    'name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>$addr,
                    'client_type'=>$type2,'reg_code'=>$reg,'vat_number'=>$vat,
                    'password'=>$pwd,'email_verified'=>1,
                ]);
            }
        }
        $msg .= '✅ Kliendid lisatud. ';
    }

    if ( $type === 'all' || $type === 'workers' ) {
        $workers = [
            ['Peeter Puu',   '+372 5111 1111', 'peeter@vesho.ee', '1234', 1, 1],
            ['Siim Sepp',    '+372 5222 2222', 'siim@vesho.ee',   '5678', 1, 0],
            ['Anna Aas',     '+372 5333 3333', 'anna@vesho.ee',   '9012', 1, 1],
        ];
        foreach ($workers as [$name, $phone, $email, $pin, $active, $can_inv]) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}vesho_workers WHERE name=%s", $name));
            if (!$exists) {
                $wpdb->insert("{$p}vesho_workers", [
                    'name'=>$name,'phone'=>$phone,'email'=>$email,
                    'pin'=>wp_hash_password($pin),'active'=>$active,'can_inventory'=>$can_inv,
                ]);
            }
        }
        $msg .= '✅ Töötajad lisatud. ';
    }

    if ( $type === 'all' || $type === 'devices' ) {
        $clients_db = $wpdb->get_results("SELECT id FROM {$p}vesho_clients ORDER BY id LIMIT 6");
        if ($clients_db) {
            $devices = [
                ['Daikin FTXB25C kliimasade', 'Daikin', 'DTX-2024-001', 12, $clients_db[0]->id],
                ['Mitsubishi MSZ-AP25VG',      'Mitsubishi', 'MSZ-2023-042', 24, $clients_db[0]->id],
                ['LG S09EQ kliimasade',        'LG', 'LG-2022-117',    6,  $clients_db[1]->id ?? $clients_db[0]->id],
                ['Panasonic CS-PZ25WKE',       'Panasonic', 'PAN-2024-033', 12, $clients_db[2]->id ?? $clients_db[0]->id],
                ['Daikin FTXF20D',             'Daikin', 'DTX-2023-088', 24, $clients_db[3]->id ?? $clients_db[0]->id],
                ['Samsung AR09TXHQASINEU',     'Samsung', 'SAM-2021-055', 12, $clients_db[4]->id ?? $clients_db[0]->id],
            ];
            foreach ($devices as [$name, $model, $serial, $interval, $cid]) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}vesho_devices WHERE serial_number=%s", $serial));
                if (!$exists) {
                    $wpdb->insert("{$p}vesho_devices", [
                        'client_id'=>$cid,'name'=>$name,'model'=>$model,
                        'serial_number'=>$serial,'service_interval'=>$interval,
                        'install_date'=>date('Y-m-d', strtotime('-'.rand(6,24).' months')),
                    ]);
                }
            }
        }
        $msg .= '✅ Seadmed lisatud. ';
    }

    if ( $type === 'all' || $type === 'maintenances' ) {
        $devices_db = $wpdb->get_results("SELECT id, client_id FROM {$p}vesho_devices ORDER BY id LIMIT 6");
        $statuses   = ['scheduled','scheduled','completed','completed','pending'];
        foreach ($devices_db as $i => $dev) {
            $status = $statuses[$i % count($statuses)];
            $days   = $status === 'completed' ? -rand(5,60) : rand(1,30);
            $sdate  = date('Y-m-d', strtotime("{$days} days"));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}vesho_maintenances WHERE device_id=%d AND scheduled_date=%s", $dev->id, $sdate
            ));
            if (!$exists) {
                $wpdb->insert("{$p}vesho_maintenances", [
                    'device_id'      => $dev->id,
                    'client_id'      => $dev->client_id,
                    'scheduled_date' => $sdate,
                    'completed_date' => $status === 'completed' ? $sdate : null,
                    'status'         => $status,
                    'description'    => 'Demo hooldus — filtrite puhastus, freooni kontroll',
                ]);
            }
        }
        $msg .= '✅ Hooldused lisatud. ';
    }

    if ( $type === 'all' || $type === 'inventory' ) {
        $items = [
            ['Õhufilter F7 600x300', 'FLT-F7-001', '4740123000001', 'tk',  50,  8.50,  5,  'Filtrid',   'A1-01'],
            ['Õhufilter G4 500x250', 'FLT-G4-002', '4740123000002', 'tk',  30,  5.90,  5,  'Filtrid',   'A1-02'],
            ['Freoon R32 (9kg)',      'FRE-R32-001','4740123000003', 'tk',  8,   89.00, 2,  'Kemikaalid','B2-01'],
            ['Freoon R410A (11.3kg)','FRE-R41-001','4740123000004', 'tk',  5,   95.00, 2,  'Kemikaalid','B2-02'],
            ['Kondensaadipump',       'KON-PMP-001','4740123000005', 'tk',  12,  24.90, 3,  'Pumbad',    'C3-01'],
            ['Kliimakandur 12BTU',    'KLI-12B-001','4740123000006', 'tk',  3,   450.00,1,  'Seadmed',   'D4-01'],
            ['Isolatsioonitoru 2m',   'ISO-TOR-001','4740123000007', 'm',   200, 1.20,  20, 'Materjal',  'E5-01'],
            ['Montaažiplaat A',       'MON-PLA-001','4740123000008', 'tk',  15,  12.50, 5,  'Materjal',  'E5-02'],
        ];
        foreach ($items as [$name,$sku,$ean,$unit,$qty,$price,$min,$cat,$loc]) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}vesho_inventory WHERE sku=%s", $sku));
            if (!$exists) {
                $wpdb->insert("{$p}vesho_inventory", [
                    'name'=>$name,'sku'=>$sku,'ean'=>$ean,'unit'=>$unit,
                    'quantity'=>$qty,'sell_price'=>$price,'min_quantity'=>$min,
                    'category'=>$cat,'location'=>$loc,'shop_enabled'=>1,
                    'shop_price'=>round($price*1.22,2),
                ]);
            }
        }
        $msg .= '✅ Laoseis lisatud. ';
    }

    if ( $type === 'all' || $type === 'invoices' ) {
        $clients_db = $wpdb->get_results("SELECT id FROM {$p}vesho_clients ORDER BY id LIMIT 4");
        $statuses   = ['paid','paid','sent','draft'];
        $prefix     = get_option('vesho_invoice_prefix','INV');
        foreach ($clients_db as $i => $c) {
            $status  = $statuses[$i];
            $idate   = date('Y-m-d', strtotime('-'.($i*7).' days'));
            $due     = date('Y-m-d', strtotime($idate.' +14 days'));
            $inv_num = $prefix . '-' . date('Y') . '-' . str_pad(900+$i, 4, '0', STR_PAD_LEFT);
            $exists  = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}vesho_invoices WHERE invoice_number=%s", $inv_num));
            if (!$exists) {
                $wpdb->insert("{$p}vesho_invoices", [
                    'client_id'=>$c->id,'invoice_number'=>$inv_num,'invoice_date'=>$idate,
                    'due_date'=>$due,'status'=>$status,'invoice_type'=>'invoice',
                    'total'=>rand(6000,25000)/100,
                ]);
                $inv_id = $wpdb->insert_id;
                $lines  = [
                    ['Kliimaseadme hooldus', 1, 'tk', 65.00, 22],
                    ['Filtrite vahetus',     2, 'tk',  8.50, 22],
                ];
                foreach ($lines as [$desc,$qty,$unit,$up,$vat]) {
                    $wpdb->insert("{$p}vesho_invoice_items", [
                        'invoice_id'=>$inv_id,'description'=>$desc,'quantity'=>$qty,
                        'unit'=>$unit,'unit_price'=>$up,'vat_rate'=>$vat,
                    ]);
                }
                $total = array_sum(array_map(fn($l)=>$l[1]*$l[3]*(1+$l[4]/100), $lines));
                $wpdb->update("{$p}vesho_invoices", ['total'=>round($total,2)], ['id'=>$inv_id]);
            }
        }
        $msg .= '✅ Arved lisatud. ';
    }

    if ( $type === 'all' || $type === 'tickets' ) {
        $clients_db = $wpdb->get_results("SELECT id, name, email FROM {$p}vesho_clients LIMIT 3");
        $tickets = [
            ['Kliimasade ei jahuta piisavalt', 'Tere! Minu kliimasade ei jahuta enam korralikult. Temperatuur toas on 28 kraadi kuigi seade töötab.', 'open',   'high'],
            ['Kumisev heli töötamisel',         'Seade teeb töötades kumisevat häält. Häire algas eile õhtul.',                                       'pending','medium'],
            ['Küsimus hooldusse registreerimise kohta', 'Kuidas saan registreerida oma seadme järgmisele hooldusele?',                               'solved', 'low'],
        ];
        foreach ($tickets as $i => [$subj, $msg2, $status, $prio]) {
            $c = $clients_db[$i % count($clients_db)] ?? null;
            if ($c) {
                $wpdb->insert("{$p}vesho_support_tickets", [
                    'client_id'=>$c->id,'client_name'=>$c->name,'client_email'=>$c->email,
                    'subject'=>$subj,'message'=>$msg2,'status'=>$status,'priority'=>$prio,
                ]);
            }
        }
        $msg .= '✅ Tugipiletid lisatud. ';
    }

    if ( $type === 'wipe' && isset($_POST['confirm_wipe']) ) {
        $tables = [
            'vesho_support_ticket_replies','vesho_support_tickets',
            'vesho_invoice_items','vesho_invoices',
            'vesho_workorders','vesho_work_hours',
            'vesho_maintenances',
            'vesho_devices',
            'vesho_clients',
            'vesho_workers',
            'vesho_shop_order_items','vesho_shop_orders',
            'vesho_inventory_writeoffs','vesho_inventory',
        ];
        foreach ($tables as $t) {
            $wpdb->query("TRUNCATE TABLE {$p}{$t}");
        }
        $msg = '🗑️ Kõik andmed kustutatud.';
    }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$stats = [];
foreach (['vesho_clients','vesho_workers','vesho_devices','vesho_maintenances',
          'vesho_inventory','vesho_invoices','vesho_support_tickets'] as $t) {
    $stats[$t] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}{$t}");
}
?>
<div class="wrap">
<h1>🧪 Demo andmete seeder</h1>
<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px">
  ⚠️ <strong>Ainult arenduskeskkonnas.</strong> Live serveris see leht ei ole nähtav.
</div>

<?php if ($msg): ?>
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;font-weight:600">
  <?php echo esc_html($msg); ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-bottom:24px">
<?php
$labels = ['vesho_clients'=>'Kliendid','vesho_workers'=>'Töötajad','vesho_devices'=>'Seadmed',
           'vesho_maintenances'=>'Hooldused','vesho_inventory'=>'Laoseis',
           'vesho_invoices'=>'Arved','vesho_support_tickets'=>'Piletid'];
foreach ($stats as $t => $cnt):
?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center">
  <div style="font-size:22px;font-weight:800;color:#00b4c8"><?php echo $cnt; ?></div>
  <div style="font-size:11px;color:#64748b;margin-top:2px"><?php echo $labels[$t]??$t; ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- Seed buttons -->
<div class="crm-card" style="margin-bottom:20px">
  <div class="crm-card-header"><span class="crm-card-title">➕ Lisa demo andmed</span></div>
  <div style="padding:20px">
  <form method="POST" style="display:flex;flex-wrap:wrap;gap:10px">
    <?php wp_nonce_field('vesho_demo_seed'); ?>
    <button type="submit" name="vesho_seed" value="all"     class="crm-btn crm-btn-primary">🚀 Lisa kõik</button>
    <button type="submit" name="vesho_seed" value="clients" class="crm-btn crm-btn-outline">👥 Kliendid</button>
    <button type="submit" name="vesho_seed" value="workers" class="crm-btn crm-btn-outline">👷 Töötajad</button>
    <button type="submit" name="vesho_seed" value="devices" class="crm-btn crm-btn-outline">📟 Seadmed</button>
    <button type="submit" name="vesho_seed" value="maintenances" class="crm-btn crm-btn-outline">🔧 Hooldused</button>
    <button type="submit" name="vesho_seed" value="inventory"   class="crm-btn crm-btn-outline">📦 Laoseis</button>
    <button type="submit" name="vesho_seed" value="invoices"    class="crm-btn crm-btn-outline">🧾 Arved</button>
    <button type="submit" name="vesho_seed" value="tickets"     class="crm-btn crm-btn-outline">🎫 Piletid</button>
  </form>
  <p style="font-size:12px;color:#94a3b8;margin-top:10px">Duplikaate ei lisata — ohutu käivitada mitu korda.</p>
  </div>
</div>

<!-- Wipe -->
<div class="crm-card" style="border:2px solid #fee2e2">
  <div class="crm-card-header" style="background:#fef2f2"><span class="crm-card-title" style="color:#dc2626">🗑️ Kustuta kõik andmed</span></div>
  <div style="padding:20px">
  <form method="POST" onsubmit="return confirm('KUSTUTAB KÕIK andmed! Jätka?')">
    <?php wp_nonce_field('vesho_demo_seed'); ?>
    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;font-size:13px">
      <input type="checkbox" name="confirm_wipe" value="1" required>
      Saan aru, et see kustutab kõik kliendid, seadmed, hooldused, arved jms.
    </label>
    <button type="submit" name="vesho_seed" value="wipe"
            class="crm-btn" style="background:#dc2626;color:#fff;border:none">🗑️ Kustuta kõik andmed</button>
  </form>
  </div>
</div>
</div>
