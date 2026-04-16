<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$device_id = isset($_GET['device_id']) ? absint($_GET['device_id']) : 0;
$client_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
$search    = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get all clients for dropdown
$all_clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_clients ORDER BY name ASC");

// Edit mode
$edit = null;
if ( $action === 'edit' && $device_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_devices WHERE id=%d",$device_id));
}

// List
$where = '1=1';
$params = [];
if ($client_id) { $where .= $wpdb->prepare(' AND d.client_id=%d', $client_id); }
if ($search) { $where .= $wpdb->prepare(' AND (d.name LIKE %s OR d.model LIKE %s OR d.serial_number LIKE %s)',
    '%'.$wpdb->esc_like($search).'%','%'.$wpdb->esc_like($search).'%','%'.$wpdb->esc_like($search).'%'); }

$devices = $wpdb->get_results(
    "SELECT d.*, c.name as client_name FROM {$wpdb->prefix}vesho_devices d
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id=c.id
     WHERE $where ORDER BY d.created_at DESC LIMIT 200"
);
$total = count($devices);
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🔧 Seadmed <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if ( isset($_GET['msg']) ) : ?>
        <?php $msgs=['added'=>'Seade lisatud!','updated'=>'Seade uuendatud!','deleted'=>'Seade kustutatud!']; ?>
        <div class="crm-alert crm-alert-success"><?php echo esc_html($msgs[$_GET['msg']]??'Salvestatud!'); ?></div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda seadet':'Lisa uus seade'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_device'); ?>
            <input type="hidden" name="action" value="vesho_save_device">
            <input type="hidden" name="redirect_client" value="<?php echo $client_id; ?>">
            <?php if ($edit) : ?><input type="hidden" name="device_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Klient *</label>
                    <select class="crm-form-select" name="client_id" required>
                        <option value="">— Vali klient —</option>
                        <?php foreach ($all_clients as $c) : ?>
                            <option value="<?php echo $c->id; ?>" <?php selected($edit->client_id??$client_id, $c->id); ?>><?php echo esc_html($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Seadme nimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit->name??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Mudel</label>
                    <input class="crm-form-input" type="text" name="model" value="<?php echo esc_attr($edit->model??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Seerianumber</label>
                    <input class="crm-form-input" type="text" name="serial_number" value="<?php echo esc_attr($edit->serial_number??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Paigalduse kuupäev</label>
                    <input class="crm-form-input" type="date" name="install_date" value="<?php echo esc_attr($edit->install_date??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Asukoht</label>
                    <input class="crm-form-input" type="text" name="location" value="<?php echo esc_attr($edit->location??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Hooldusintervall (kuudes)</label>
                    <input class="crm-form-input" type="number" name="maintenance_interval" min="0" max="120"
                           value="<?php echo esc_attr($edit->maintenance_interval??''); ?>"
                           style="max-width:100px" placeholder="nt 6">
                    <small style="color:#6b8599;font-size:12px">0 / tühi = automaatne loomine keelatud</small>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Märkused</label>
                    <textarea class="crm-form-textarea" name="notes"><?php echo esc_textarea($edit->notes??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&action=add'.($client_id?'&client_id='.$client_id:'')); ?>" class="crm-btn crm-btn-primary">+ Lisa seade</a>
            <button class="crm-btn-scanner crm-open-scanner" data-mode="scan" title="Skanni QR/vöötkood">📷 Skanni</button>
            <button class="crm-btn-scanner crm-open-scanner" data-mode="qr" title="Genereeri QR kood">QR</button>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-devices">
                <input class="crm-search" type="search" name="s" placeholder="Otsi nime, mudelit, seerianumbrit..." value="<?php echo esc_attr($search); ?>">
                <?php if ($client_id) : ?><input type="hidden" name="client_id" value="<?php echo $client_id; ?>"><?php endif; ?>
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
            <?php if ($client_id) : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$client_id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Kliendi juurde</a>
            <?php endif; ?>
        </div>
        <?php if (empty($devices)) : ?>
            <div class="crm-empty">Seadmeid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimi</th><th>Mudel</th><th>Klient</th><th>Seerianr</th><th>Paigaldus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($devices as $d) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $d->id; ?></td>
                <td><strong><?php echo esc_html($d->name); ?></strong></td>
                <td><?php echo esc_html($d->model?:'–'); ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$d->client_id); ?>"><?php echo esc_html($d->client_name); ?></a></td>
                <td style="font-family:monospace"><?php echo esc_html($d->serial_number?:'–'); ?></td>
                <td><?php echo vesho_crm_format_date($d->install_date); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&action=edit&device_id='.$d->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-maintenances&device_id='.$d->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Hooldused">📅</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_device&device_id='.$d->id),'vesho_delete_device'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta seade?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
