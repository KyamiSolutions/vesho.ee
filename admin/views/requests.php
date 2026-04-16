<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action     = isset($_GET['action'])     ? sanitize_text_field($_GET['action'])  : '';
$request_id = isset($_GET['request_id']) ? absint($_GET['request_id'])            : 0;
$filter_st  = isset($_GET['status'])     ? sanitize_text_field($_GET['status'])  : '';
$search     = isset($_GET['s'])          ? sanitize_text_field($_GET['s'])        : '';

$edit = null;
if ( $action === 'view' && $request_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_guest_requests WHERE id=%d", $request_id));
    // Auto mark as read
    if ($edit && $edit->status === 'new') {
        $wpdb->update($wpdb->prefix.'vesho_guest_requests', ['status'=>'open'], ['id'=>$request_id]);
        $edit->status = 'open';
    }
}

$where = '1=1';
if ($filter_st) { $where .= $wpdb->prepare(' AND status=%s', $filter_st); }
if ($search)    { $where .= $wpdb->prepare(' AND (name LIKE %s OR email LIKE %s OR message LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$requests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_guest_requests WHERE $where ORDER BY created_at DESC LIMIT 200");
$total    = count($requests);
$new_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_guest_requests WHERE status='new'");

$statuses = ['new'=>'Uus','open'=>'Avatud','resolved'=>'Lahendatud','spam'=>'Rämpspost'];
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">📨 Päringud <span class="crm-count">(<?php echo $total; ?>)</span>
        <?php if ($new_count > 0) : ?><span class="crm-badge badge-danger" style="font-size:13px;vertical-align:middle"><?php echo $new_count; ?> uut</span><?php endif; ?>
    </h1>

    <?php if (isset($_GET['msg'])) :
        echo '<div class="crm-alert crm-alert-success">Staatus uuendatud!</div>';
    endif; ?>

    <?php if ($action === 'view' && $edit) : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">Päring #<?php echo $edit->id; ?> — <?php echo esc_html($edit->name); ?></span>
            <div style="display:flex;gap:8px;align-items:center">
                <?php echo vesho_crm_status_badge($edit->status); ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-requests'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
            </div>
        </div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
                <div>
                    <div class="crm-form-label">Nimi</div>
                    <div style="font-weight:600"><?php echo esc_html($edit->name); ?></div>
                </div>
                <div>
                    <div class="crm-form-label">E-post</div>
                    <div><a href="mailto:<?php echo esc_attr($edit->email); ?>"><?php echo esc_html($edit->email); ?></a></div>
                </div>
                <?php if ($edit->phone) : ?>
                <div>
                    <div class="crm-form-label">Telefon</div>
                    <div><?php echo esc_html($edit->phone); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($edit->service_type) || !empty($edit->device_name)) : ?>
                <div>
                    <div class="crm-form-label">Seade / Teenus</div>
                    <div><?php echo esc_html(($edit->device_name ?? '') . ((!empty($edit->device_name) && !empty($edit->service_type)) ? ' — ' : '') . ($edit->service_type ?? '')); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($edit->preferred_date)) : ?>
                <div>
                    <div class="crm-form-label">Eelistatud kuupäev</div>
                    <div><?php echo vesho_crm_format_date($edit->preferred_date); ?></div>
                </div>
                <?php endif; ?>
                <div>
                    <div class="crm-form-label">Saadetud</div>
                    <div><?php echo vesho_crm_format_date($edit->created_at, 'd.m.Y H:i'); ?></div>
                </div>
            </div>

            <div class="crm-form-label" style="margin-bottom:6px">Kirjeldus</div>
            <div style="background:#f4f7f9;border-radius:8px;padding:16px;font-size:14px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($edit->description ?? ''); ?></div>
            <?php if (!empty($edit->admin_notes)) : ?>
            <div class="crm-form-label" style="margin-bottom:6px;margin-top:16px">Admin märkused</div>
            <div style="background:#fff8e1;border-radius:8px;padding:16px;font-size:14px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($edit->admin_notes); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:16px">
                <?php wp_nonce_field('vesho_update_request'); ?>
                <input type="hidden" name="action" value="vesho_update_request_notes">
                <input type="hidden" name="request_id" value="<?php echo $edit->id; ?>">
                <label class="crm-form-label">Lisa admin märkus</label>
                <textarea class="crm-form-textarea" name="admin_notes" style="min-height:80px;margin-top:4px"><?php echo esc_textarea($edit->admin_notes ?? ''); ?></textarea>
                <div style="margin-top:8px;display:flex;justify-content:flex-end">
                    <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">💾 Salvesta märkus</button>
                </div>
            </form>

            <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
                <strong style="align-self:center;font-size:13px">Muuda staatus:</strong>
                <?php foreach ($statuses as $v=>$l) :
                    if ($v === $edit->status) continue;
                ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_update_request&request_id='.$edit->id.'&status='.$v),'vesho_update_request'); ?>"
                       class="crm-btn crm-btn-outline crm-btn-sm"><?php echo $l; ?></a>
                <?php endforeach; ?>
                <a href="mailto:<?php echo esc_attr($edit->email); ?>?subject=Re:+Teie+päring+Vesho+OÜ-le" class="crm-btn crm-btn-primary crm-btn-sm">✉️ Vasta e-postiga</a>
                <?php
                $existing_client = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}vesho_clients WHERE email=%s LIMIT 1",
                    $edit->email
                ));
                if (!$existing_client) :
                    $add_url = add_query_arg([
                        'page'        => 'vesho-crm-clients',
                        'action'      => 'add',
                        'prefill_name'  => urlencode($edit->name),
                        'prefill_email' => urlencode($edit->email),
                        'prefill_phone' => urlencode($edit->phone ?? ''),
                        'from_request'  => $edit->id,
                    ], admin_url('admin.php'));
                ?>
                <a href="<?php echo esc_url($add_url); ?>" class="crm-btn crm-btn-outline crm-btn-sm" style="background:#f0fdf4;border-color:#86efac;color:#166534">👤 Lisa kliendiks</a>
                <?php else : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$existing_client->id); ?>"
                   class="crm-btn crm-btn-outline crm-btn-sm" style="font-size:12px;color:#6b8599">✅ Klient juba lisatud</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-requests">
                <select class="crm-form-select" name="status" style="max-width:140px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik</option>
                    <?php foreach ($statuses as $v=>$l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($filter_st,$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi nime, e-posti..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($requests)) : ?>
            <div class="crm-empty">Päringuid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimi</th><th>E-post</th><th>Teenus</th><th>Sõnum</th><th>Saadetud</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($requests as $req) : ?>
            <tr <?php if ($req->status==='new') echo 'style="font-weight:600;background:#fefce8"'; ?>>
                <td style="color:#6b8599">#<?php echo $req->id; ?></td>
                <td><?php echo esc_html($req->name); ?></td>
                <td><a href="mailto:<?php echo esc_attr($req->email); ?>"><?php echo esc_html($req->email); ?></a></td>
                <td><?php echo esc_html($req->service_type ?: '–'); ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html(substr($req->description ?? '', 0, 80)); ?></td>
                <td><?php echo vesho_crm_format_date($req->created_at, 'd.m H:i'); ?></td>
                <td><?php echo vesho_crm_status_badge($req->status); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-requests&action=view&request_id='.$req->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Vaata">👁️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_update_request&request_id='.$req->id.'&status=resolved'),'vesho_update_request'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Lahendatud">✅</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
