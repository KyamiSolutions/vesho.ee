<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$raw_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date) ) {
    $raw_date = date('Y-m-d');
}

$maintenances = $wpdb->get_results( $wpdb->prepare(
    "SELECT m.id, m.description, m.status, m.scheduled_date,
            d.name as device_name, d.model as device_model,
            c.name as client_name, c.address as client_address, c.phone as client_phone
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
     WHERE m.scheduled_date = %s
       AND m.status IN ('scheduled', 'pending')
     ORDER BY c.address ASC",
    $raw_date
) );

$date_label = date('d.m.Y', strtotime($raw_date));

// Build all addresses for multi-stop Google Maps URL
$addresses = [];
foreach ($maintenances as $m) {
    if (!empty($m->client_address)) {
        $addresses[] = $m->client_address;
    }
}
$maps_multi_url = '';
if (!empty($addresses)) {
    $maps_multi_url = 'https://www.google.com/maps/dir/' . implode('/', array_map('rawurlencode', $addresses));
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🗺️ Päeva marsruut</h1>

    <!-- Date selector & top actions -->
    <div class="crm-card" style="margin-bottom:16px">
        <div class="crm-toolbar" style="padding:14px 16px;flex-wrap:wrap;gap:10px">
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="page" value="vesho-crm-route">
                <label style="font-size:13px;font-weight:500">Kuupäev:</label>
                <input type="date" name="date" value="<?php echo esc_attr($raw_date); ?>"
                       class="crm-form-input" style="width:160px;padding:6px 10px;font-size:13px">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Lae</button>
            </form>

            <?php if (!empty($maps_multi_url)) : ?>
            <a href="<?php echo esc_url($maps_multi_url); ?>" target="_blank"
               class="crm-btn crm-btn-primary">
                📍 Ava kõik Google Mapsis
            </a>
            <?php endif; ?>

            <?php if (count($maintenances) > 1) : ?>
            <button type="button" class="crm-btn crm-btn-outline" onclick="optimiseRoute()">
                🔀 Optimeeri marsruut
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($maintenances)) : ?>
    <div class="crm-card">
        <div class="crm-empty">
            <?php echo esc_html($date_label); ?> ei ole planeeritud ega ootel hooldusi.
        </div>
    </div>
    <?php else : ?>

    <div class="crm-card">
        <div style="padding:14px 16px 4px">
            <strong style="font-size:14px"><?php echo esc_html($date_label); ?></strong>
            <span style="color:#6b7280;font-size:13px;margin-left:8px"><?php echo count($maintenances); ?> hooldust</span>
        </div>
        <table class="crm-table" id="route-table">
            <thead>
            <tr>
                <th style="width:36px">#</th>
                <th>Klient</th>
                <th>Aadress</th>
                <th>Seade</th>
                <th>Kirjeldus</th>
                <th style="width:120px">Staatus</th>
                <th class="td-actions">Kaardid</th>
            </tr>
            </thead>
            <tbody id="route-tbody">
            <?php $i = 1; foreach ($maintenances as $m) :
                $addr = $m->client_address ?: '';
                $maps_url  = $addr ? 'https://maps.google.com/?q=' . rawurlencode($addr) : '';
                $waze_url  = $addr ? 'https://waze.com/ul?q=' . rawurlencode($addr) : '';
            ?>
            <tr data-address="<?php echo esc_attr($addr); ?>">
                <td style="font-weight:600;color:#6b7280"><?php echo $i++; ?></td>
                <td>
                    <strong><?php echo esc_html($m->client_name ?: '–'); ?></strong>
                    <?php if ($m->client_phone) : ?>
                        <br><a href="tel:<?php echo esc_attr($m->client_phone); ?>" style="font-size:12px;color:#6b7280"><?php echo esc_html($m->client_phone); ?></a>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:#374151"><?php echo esc_html($addr ?: '–'); ?></td>
                <td style="font-size:13px"><?php echo esc_html(trim(($m->device_name?:'') . ' ' . ($m->device_model?:'')) ?: '–'); ?></td>
                <td style="font-size:13px;color:#6b7280"><?php echo esc_html(mb_substr($m->description ?: '', 0, 80)); ?></td>
                <td><?php echo vesho_crm_status_badge($m->status); ?></td>
                <td class="td-actions" style="white-space:nowrap">
                    <?php if ($maps_url) : ?>
                    <a href="<?php echo esc_url($maps_url); ?>" target="_blank"
                       class="crm-btn crm-btn-sm crm-btn-outline" title="Ava Google Mapsis">
                        🗺️
                    </a>
                    <a href="<?php echo esc_url($waze_url); ?>" target="_blank"
                       class="crm-btn crm-btn-sm crm-btn-outline" title="Ava Wazeis">
                        🚗 Waze
                    </a>
                    <?php else : ?>
                    <span style="color:#9ca3af;font-size:12px">Aadress puudub</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function optimiseRoute() {
    var tbody = document.getElementById('route-tbody');
    if (!tbody) return;
    var rows = Array.from(tbody.querySelectorAll('tr'));
    // Sort alphabetically by address (data-address attribute)
    rows.sort(function(a, b) {
        var addrA = (a.getAttribute('data-address') || '').toLowerCase();
        var addrB = (b.getAttribute('data-address') || '').toLowerCase();
        return addrA.localeCompare(addrB);
    });
    // Re-append sorted rows and renumber
    rows.forEach(function(row, idx) {
        tbody.appendChild(row);
        var numCell = row.querySelector('td:first-child');
        if (numCell) numCell.textContent = (idx + 1);
    });
}
</script>
