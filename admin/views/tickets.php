<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action'])    ? sanitize_text_field($_GET['action']) : '';
$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id'])            : 0;
$filter_st = isset($_GET['status'])    ? sanitize_text_field($_GET['status'])  : '';
$search    = isset($_GET['s'])         ? sanitize_text_field($_GET['s'])       : '';

$edit = null;
$edit_client = null;
$ticket_replies = [];
if ( $action === 'view' && $ticket_id ) {
    $edit = $wpdb->get_row($wpdb->prepare(
        "SELECT t.*, c.name as client_name, c.email as client_email
         FROM {$wpdb->prefix}vesho_support_tickets t
         LEFT JOIN {$wpdb->prefix}vesho_clients c ON t.client_id=c.id
         WHERE t.id=%d LIMIT 1",
        $ticket_id
    ));
    if ( $edit ) {
        $ticket_replies = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_ticket_replies WHERE ticket_id=%d ORDER BY created_at ASC",
            $ticket_id
        ));
    }
}

$where = '1=1';
if ($filter_st) { $where .= $wpdb->prepare(' AND t.status=%s', $filter_st); }
if ($search)    { $where .= $wpdb->prepare(' AND (t.subject LIKE %s OR c.name LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$tickets = $wpdb->get_results(
    "SELECT t.*, c.name as client_name
     FROM {$wpdb->prefix}vesho_support_tickets t
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON t.client_id=c.id
     WHERE $where ORDER BY t.created_at DESC LIMIT 200"
);
$total    = count($tickets);
$open_cnt = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_support_tickets WHERE status='open'");

$statuses   = ['open'=>'Avatud','in_progress'=>'Töös','closed'=>'Suletud','spam'=>'Rämpspost'];
$priorities = ['low'=>'Madal','normal'=>'Tavaline','high'=>'Kõrge','urgent'=>'Kiire'];
$pcls       = ['low'=>'badge-gray','normal'=>'badge-info','high'=>'badge-warning','urgent'=>'badge-danger'];
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🎫 Tugipiletid <span class="crm-count">(<?php echo $total; ?>)</span>
        <?php if ($open_cnt > 0) : ?><span class="crm-badge badge-warning" style="font-size:13px;vertical-align:middle"><?php echo $open_cnt; ?> avatud</span><?php endif; ?>
    </h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['replied'=>'Vastus saadetud!','updated'=>'Staatus uuendatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'view' && $edit) : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">Pilet #<?php echo $edit->id; ?> — <?php echo esc_html($edit->subject); ?></span>
            <div style="display:flex;gap:8px;align-items:center">
                <?php echo vesho_crm_status_badge($edit->status); ?>
                <span class="crm-badge <?php echo esc_attr($pcls[$edit->priority]??'badge-gray'); ?>"><?php echo esc_html($priorities[$edit->priority]??$edit->priority); ?></span>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-tickets'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
            </div>
        </div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
                <div>
                    <div class="crm-form-label">Klient</div>
                    <div style="font-weight:600">
                        <?php if ($edit->client_id) : ?>
                            <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$edit->client_id); ?>"><?php echo esc_html($edit->client_name); ?></a>
                        <?php else : echo '–'; endif; ?>
                    </div>
                </div>
                <div>
                    <div class="crm-form-label">E-post</div>
                    <div><a href="mailto:<?php echo esc_attr($edit->client_email ?? ''); ?>"><?php echo esc_html($edit->client_email ?? '–'); ?></a></div>
                </div>
                <div>
                    <div class="crm-form-label">Saadetud</div>
                    <div><?php echo vesho_crm_format_date($edit->created_at, 'd.m.Y H:i'); ?></div>
                </div>
                <div>
                    <div class="crm-form-label">Uuendatud</div>
                    <div><?php echo vesho_crm_format_date($edit->updated_at, 'd.m.Y H:i'); ?></div>
                </div>
            </div>

            <!-- ── Sõnumite ahel ──────────────────────────────────────────── -->
            <div class="crm-form-label" style="margin-bottom:8px">💬 Sõnumid</div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">

                <!-- Kliendi algne sõnum -->
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <div style="width:32px;height:32px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">👤</div>
                    <div style="flex:1">
                        <div style="font-size:11px;color:#6b8599;margin-bottom:4px">
                            <strong style="color:#1a2a38"><?php echo esc_html($edit->client_name ?? 'Klient'); ?></strong>
                            &nbsp;·&nbsp;<?php echo vesho_crm_format_date($edit->created_at,'d.m.Y H:i'); ?>
                        </div>
                        <div style="background:#f4f7f9;border-radius:0 8px 8px 8px;padding:12px 16px;font-size:14px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($edit->message ?? ''); ?></div>
                    </div>
                </div>

                <?php if (!empty($ticket_replies)) : ?>
                <?php foreach ($ticket_replies as $rep) :
                    $is_admin = ($rep->author !== 'client');
                ?>
                <div style="display:flex;gap:12px;align-items:flex-start<?php echo $is_admin ? ';flex-direction:row-reverse' : ''; ?>">
                    <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $is_admin ? '#d1fae5' : '#e0f2fe'; ?>;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0"><?php echo $is_admin ? '🛠️' : '👤'; ?></div>
                    <div style="flex:1">
                        <div style="font-size:11px;color:#6b8599;margin-bottom:4px;<?php echo $is_admin ? 'text-align:right' : ''; ?>">
                            <strong style="color:#1a2a38"><?php echo esc_html($rep->author); ?></strong>
                            &nbsp;·&nbsp;<?php echo vesho_crm_format_date($rep->created_at,'d.m.Y H:i'); ?>
                        </div>
                        <div style="background:<?php echo $is_admin ? '#f0fdf4;border:1px solid #bbf7d0' : '#f4f7f9'; ?>;border-radius:<?php echo $is_admin ? '8px 0 8px 8px' : '0 8px 8px 8px'; ?>;padding:12px 16px;font-size:14px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($rep->message); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php elseif (!empty($edit->reply)) : ?>
                <!-- Legacy single-reply fallback -->
                <div style="display:flex;gap:12px;align-items:flex-start;flex-direction:row-reverse">
                    <div style="width:32px;height:32px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">🛠️</div>
                    <div style="flex:1">
                        <div style="font-size:11px;color:#6b8599;margin-bottom:4px;text-align:right"><strong style="color:#1a2a38">Admin</strong></div>
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px 0 8px 8px;padding:12px 16px;font-size:14px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($edit->reply); ?></div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
                <strong style="align-self:center;font-size:13px">Muuda staatus:</strong>
                <?php foreach ($statuses as $v=>$l) :
                    if ($v === $edit->status) continue; ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_update_ticket_status&ticket_id='.$edit->id.'&status='.$v),'vesho_ticket_action'); ?>"
                       class="crm-btn crm-btn-outline crm-btn-sm"><?php echo $l; ?></a>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('vesho_ticket_action'); ?>
                <input type="hidden" name="action" value="vesho_reply_ticket">
                <input type="hidden" name="ticket_id" value="<?php echo $edit->id; ?>">
                <label class="crm-form-label">Vastus kliendile</label>
                <textarea class="crm-form-textarea" name="reply" style="min-height:100px;margin-top:4px" placeholder="Kirjuta vastus..."></textarea>
                <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                        <input type="checkbox" name="close_ticket" value="1" style="width:15px;height:15px">
                        Sulge pilet pärast vastust
                    </label>
                    <button type="submit" class="crm-btn crm-btn-primary">✉️ Saada vastus</button>
                </div>
            </form>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-tickets">
                <select class="crm-form-select" name="status" style="max-width:140px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik</option>
                    <?php foreach ($statuses as $v=>$l) : ?>
                        <option value="<?php echo $v; ?>" <?php selected($filter_st,$v); ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="crm-search" type="search" name="s" placeholder="Otsi teemat, klienti..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($tickets)) : ?>
            <div class="crm-empty">Tugipileteid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Klient</th><th>Teema</th><th>Prioriteet</th><th>Saadetud</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($tickets as $t) : ?>
            <tr <?php if ($t->status==='open') echo 'style="font-weight:600;background:#fef9e7"'; ?>>
                <td style="color:#6b8599">#<?php echo $t->id; ?></td>
                <td><?php echo esc_html($t->client_name ?? '–'); ?></td>
                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($t->subject); ?></td>
                <td><span class="crm-badge <?php echo esc_attr($pcls[$t->priority]??'badge-gray'); ?>"><?php echo esc_html($priorities[$t->priority]??$t->priority); ?></span></td>
                <td><?php echo vesho_crm_format_date($t->created_at, 'd.m H:i'); ?></td>
                <td><?php echo vesho_crm_status_badge($t->status); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-tickets&action=view&ticket_id='.$t->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Vaata">👁️</a>
                    <?php if ($t->status !== 'closed') : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_update_ticket_status&ticket_id='.$t->id.'&status=closed'),'vesho_ticket_action'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Sulge">✅</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
