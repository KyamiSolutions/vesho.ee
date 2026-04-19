<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$raw_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date) ) {
    $raw_date = date('Y-m-d');
}

// ── Geocode helper (Nominatim) ─────────────────────────────────────────────────
function vesho_geocode_address( $address ) {
    if ( empty($address) ) return null;
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q'              => $address,
        'format'         => 'json',
        'limit'          => 1,
        'addressdetails' => 0,
    ]);
    $resp = wp_remote_get( $url, [
        'timeout'    => 5,
        'user-agent' => 'VeshoCRM/2.8 (vesho.ee)',
    ]);
    if ( is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200 ) return null;
    $data = json_decode( wp_remote_retrieve_body($resp), true );
    if ( ! empty($data[0]['lat']) && ! empty($data[0]['lon']) ) {
        return ['lat' => (float)$data[0]['lat'], 'lng' => (float)$data[0]['lon']];
    }
    return null;
}

// ── Nearest-neighbour sort ─────────────────────────────────────────────────────
function vesho_nearest_neighbour( $points, $start_lat, $start_lng ) {
    $remaining = $points;
    $sorted    = [];
    $cur_lat   = $start_lat;
    $cur_lng   = $start_lng;
    while ( ! empty($remaining) ) {
        $best_idx  = 0;
        $best_dist = PHP_INT_MAX;
        foreach ( $remaining as $idx => $pt ) {
            if ( $pt['lat'] === null || $pt['lng'] === null ) {
                // push address-less items to end
                continue;
            }
            $dist = sqrt( pow($pt['lat'] - $cur_lat, 2) + pow($pt['lng'] - $cur_lng, 2) );
            if ( $dist < $best_dist ) {
                $best_dist = $dist;
                $best_idx  = $idx;
            }
        }
        $sorted[]  = $remaining[$best_idx];
        $cur_lat   = $remaining[$best_idx]['lat'] ?? $cur_lat;
        $cur_lng   = $remaining[$best_idx]['lng'] ?? $cur_lng;
        unset($remaining[$best_idx]);
        $remaining = array_values($remaining);
    }
    return $sorted;
}

// ── Query maintenances ─────────────────────────────────────────────────────────
$maintenances = $wpdb->get_results( $wpdb->prepare(
    "SELECT m.id, m.description, m.status, m.scheduled_date,
            d.name as device_name, d.model as device_model,
            c.id as client_id, c.name as client_name,
            c.address as client_address, c.phone as client_phone,
            c.lat as client_lat, c.lng as client_lng
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
     WHERE m.scheduled_date = %s
       AND m.status IN ('scheduled', 'pending')
     ORDER BY c.address ASC",
    $raw_date
) );

// ── Geocode missing coordinates ───────────────────────────────────────────────
$geocoded_any = false;
foreach ( $maintenances as $m ) {
    if ( $m->client_id && $m->client_address && ( $m->client_lat === null || $m->client_lat == 0 ) ) {
        $coords = vesho_geocode_address( $m->client_address . ', Eesti' );
        if ( $coords ) {
            $wpdb->update(
                $wpdb->prefix . 'vesho_clients',
                ['lat' => $coords['lat'], 'lng' => $coords['lng']],
                ['id'  => $m->client_id]
            );
            $m->client_lat = $coords['lat'];
            $m->client_lng = $coords['lng'];
            $geocoded_any  = true;
        }
        usleep(500000); // Nominatim rate limit: max 1 req/s
    }
}

// ── Build points for NN algorithm ─────────────────────────────────────────────
$company_lat = (float) get_option('vesho_company_lat', '59.4370');  // default Tallinn
$company_lng = (float) get_option('vesho_company_lng', '24.7536');

$points = [];
foreach ( $maintenances as $m ) {
    $points[] = [
        'obj' => $m,
        'lat' => ( $m->client_lat !== null && $m->client_lat != 0 ) ? (float)$m->client_lat : null,
        'lng' => ( $m->client_lng !== null && $m->client_lng != 0 ) ? (float)$m->client_lng : null,
    ];
}

$sorted_points = vesho_nearest_neighbour( $points, $company_lat, $company_lng );
$maintenances_sorted = array_map( fn($p) => $p['obj'], $sorted_points );

$date_label = date('d.m.Y', strtotime($raw_date));

// Build all addresses for multi-stop Google Maps URL (sorted order)
$addresses = [];
foreach ( $maintenances_sorted as $m ) {
    if (!empty($m->client_address)) $addresses[] = $m->client_address;
}
$maps_multi_url = '';
if (!empty($addresses)) {
    $maps_multi_url = 'https://www.google.com/maps/dir/' . implode('/', array_map('rawurlencode', $addresses));
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🗺️ Päeva marsruut</h1>

    <?php if ($geocoded_any) : ?>
    <div class="crm-alert crm-alert-success" style="margin-bottom:12px">📍 Geokodeeritud uued aadressid — marsruut optimeeritud koordinaatide järgi.</div>
    <?php endif; ?>

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

            <span style="font-size:12px;color:#64748b">
                <?php if ( count($maintenances_sorted) > 0 ) :
                    $have_coords = count(array_filter($sorted_points, fn($p) => $p['lat'] !== null));
                    echo $have_coords . '/' . count($maintenances_sorted) . ' koordinaatidega';
                    if ($have_coords > 0) echo ' · lähima-naabri järjekord';
                endif; ?>
            </span>
        </div>
    </div>

    <?php if (empty($maintenances_sorted)) : ?>
    <div class="crm-card">
        <div class="crm-empty">
            <?php echo esc_html($date_label); ?> ei ole planeeritud ega ootel hooldusi.
        </div>
    </div>
    <?php else : ?>

    <div class="crm-card">
        <div style="padding:14px 16px 4px">
            <strong style="font-size:14px"><?php echo esc_html($date_label); ?></strong>
            <span style="color:#6b7280;font-size:13px;margin-left:8px"><?php echo count($maintenances_sorted); ?> hooldust</span>
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
            <?php $i = 1; foreach ( $maintenances_sorted as $idx => $m ) :
                $addr     = $m->client_address ?: '';
                $maps_url = $addr ? 'https://maps.google.com/?q=' . rawurlencode($addr) : '';
                $waze_url = $addr ? 'https://waze.com/ul?q=' . rawurlencode($addr) : '';
                $has_coords = isset($sorted_points[$idx]) && $sorted_points[$idx]['lat'] !== null;
            ?>
            <tr>
                <td style="font-weight:600;color:#6b7280"><?php echo $i++; ?></td>
                <td>
                    <strong><?php echo esc_html($m->client_name ?: '–'); ?></strong>
                    <?php if ($m->client_phone) : ?>
                        <br><a href="tel:<?php echo esc_attr($m->client_phone); ?>" style="font-size:12px;color:#6b7280"><?php echo esc_html($m->client_phone); ?></a>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:#374151">
                    <?php echo esc_html($addr ?: '–'); ?>
                    <?php if (!$has_coords && $addr) : ?>
                        <span title="Koordinaadid puuduvad" style="color:#f59e0b;font-size:10px;margin-left:4px">📍?</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px"><?php echo esc_html(trim(($m->device_name?:'') . ' ' . ($m->device_model?:'')) ?: '–'); ?></td>
                <td style="font-size:13px;color:#6b7280"><?php echo esc_html(mb_substr($m->description ?: '', 0, 80)); ?></td>
                <td><?php echo vesho_crm_status_badge($m->status); ?></td>
                <td class="td-actions" style="white-space:nowrap">
                    <?php if ($maps_url) : ?>
                    <a href="<?php echo esc_url($maps_url); ?>" target="_blank"
                       class="crm-btn crm-btn-sm crm-btn-outline" title="Ava Google Mapsis">🗺️</a>
                    <a href="<?php echo esc_url($waze_url); ?>" target="_blank"
                       class="crm-btn crm-btn-sm crm-btn-outline" title="Ava Wazeis">🚗 Waze</a>
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
