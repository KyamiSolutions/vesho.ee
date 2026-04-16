<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';

// Section 1: Pending maintenances (client submitted)
$pending = $wpdb->get_results(
    "SELECT m.*, d.name as device_name, c.name as client_name, c.email as client_email
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
     WHERE m.status = 'pending'
     ORDER BY m.scheduled_date ASC"
);

// Section 2: Upcoming scheduled (next 30 days)
$upcoming = $wpdb->get_results(
    "SELECT m.*, d.name as device_name, c.name as client_name,
            w.name as worker_name
     FROM {$wpdb->prefix}vesho_maintenances m
     LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON m.worker_id = w.id
     WHERE m.status = 'scheduled'
       AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY m.scheduled_date ASC"
);
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🔔 Meeldetuletused</h1>

    <?php if ( $msg ) :
        $msgs = [
            'confirmed' => 'Hooldus kinnitatud ja kliendile saadetud teade.',
            'rejected'  => 'Broneering lükati tagasi.',
            'cancelled' => 'Hooldus tühistatud.',
        ];
        $text = $msgs[$msg] ?? 'Toimingutu teostatud.';
        echo '<div class="crm-alert crm-alert-success">' . esc_html($text) . '</div>';
    endif; ?>

    <!-- Section 1: Pending -->
    <div class="crm-card" style="margin-bottom:24px">
        <div class="crm-card-header">
            <span class="crm-card-title">⏳ Kinnitust ootavad (<?php echo count($pending); ?>)</span>
        </div>
        <?php if ( empty($pending) ) : ?>
            <div class="crm-empty">Kinnitust ootavaid broneeringuid ei ole.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>Klient</th>
                <th>Seade</th>
                <th>Soovitud kuupäev</th>
                <th>Kirjeldus</th>
                <th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $pending as $m ) : ?>
            <tr>
                <td><?php echo esc_html($m->client_name ?: '–'); ?></td>
                <td><?php echo esc_html($m->device_name ?: '–'); ?></td>
                <td><?php echo $m->scheduled_date ? esc_html(date('d.m.Y', strtotime($m->scheduled_date))) : '–'; ?></td>
                <td><?php echo esc_html(mb_substr($m->description ?: '', 0, 80)); ?></td>
                <td class="td-actions">
                    <button type="button"
                            class="crm-btn crm-btn-sm crm-btn-primary"
                            onclick="openConfirmModal(<?php echo (int)$m->id; ?>, '<?php echo esc_js($m->scheduled_date ?: date('Y-m-d')); ?>')"
                            title="Kinnita broneering">
                        ✓ Kinnita
                    </button>
                    <a href="<?php echo wp_nonce_url(
                        admin_url('admin-post.php?action=vesho_reject_maintenance&maintenance_id=' . (int)$m->id),
                        'vesho_reject_maintenance_' . (int)$m->id
                    ); ?>"
                       class="crm-btn crm-btn-sm"
                       style="background:#ef4444;color:#fff;border:none"
                       onclick="return confirm('Lükka broneering tagasi?')">
                        ✕ Lükka tagasi
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Section 2: Upcoming -->
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">📅 Lähiaja hooldused – järgmised 30 päeva (<?php echo count($upcoming); ?>)</span>
        </div>
        <?php if ( empty($upcoming) ) : ?>
            <div class="crm-empty">Lähiajal hooldusi ei ole.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>Kuupäev</th>
                <th>Klient</th>
                <th>Seade</th>
                <th>Kirjeldus</th>
                <th>Töötaja</th>
                <th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $upcoming as $m ) : ?>
            <tr>
                <td><strong><?php echo $m->scheduled_date ? esc_html(date('d.m.Y', strtotime($m->scheduled_date))) : '–'; ?></strong></td>
                <td><?php echo esc_html($m->client_name ?: '–'); ?></td>
                <td><?php echo esc_html($m->device_name ?: '–'); ?></td>
                <td><?php echo esc_html(mb_substr($m->description ?: '', 0, 80)); ?></td>
                <td><?php echo esc_html($m->worker_name ?: '–'); ?></td>
                <td class="td-actions">
                    <a href="<?php echo wp_nonce_url(
                        admin_url('admin-post.php?action=vesho_cancel_maintenance&maintenance_id=' . (int)$m->id),
                        'vesho_cancel_maintenance_' . (int)$m->id
                    ); ?>"
                       class="crm-btn crm-btn-sm"
                       style="background:#ef4444;color:#fff;border:none"
                       onclick="return confirm('Tühista hooldus?')">
                        ✕ Tühista
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Confirm modal -->
<div id="vesho-confirm-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px;min-width:340px;max-width:460px;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <h3 style="margin:0 0 16px;font-size:16px">Kinnita hooldus</h3>
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" id="vesho-confirm-form">
            <?php wp_nonce_field('vesho_confirm_maintenance', '_wpnonce'); ?>
            <input type="hidden" name="action" value="vesho_confirm_maintenance">
            <input type="hidden" name="maintenance_id" id="confirm-maintenance-id" value="">
            <div class="crm-form-group">
                <label class="crm-form-label">Kinnitatud kuupäev</label>
                <input type="date" class="crm-form-input" name="scheduled_date" id="confirm-date" required>
            </div>
            <div class="crm-form-actions" style="margin-top:16px">
                <button type="button" class="crm-btn crm-btn-outline" onclick="closeConfirmModal()">Tühista</button>
                <button type="submit" class="crm-btn crm-btn-primary">✓ Kinnita</button>
            </div>
        </form>
    </div>
</div>

<script>
function openConfirmModal(id, date) {
    document.getElementById('confirm-maintenance-id').value = id;
    document.getElementById('confirm-date').value = date;
    var modal = document.getElementById('vesho-confirm-modal');
    modal.style.display = 'flex';
}
function closeConfirmModal() {
    document.getElementById('vesho-confirm-modal').style.display = 'none';
}
document.getElementById('vesho-confirm-modal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirmModal();
});
</script>
