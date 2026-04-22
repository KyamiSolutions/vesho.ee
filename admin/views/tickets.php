<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action'])    ? sanitize_text_field($_GET['action']) : '';
$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id'])            : 0;
$filter_st = isset($_GET['status'])    ? sanitize_text_field($_GET['status'])  : '';
$search    = isset($_GET['s'])         ? sanitize_text_field($_GET['s'])       : '';

$all_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");

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
if ($search)    { $where .= $wpdb->prepare(' AND (t.subject LIKE %s OR c.name LIKE %s OR c.email LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$tickets = $wpdb->get_results(
    "SELECT t.*, c.name as client_name, w.name as worker_name
     FROM {$wpdb->prefix}vesho_support_tickets t
     LEFT JOIN {$wpdb->prefix}vesho_clients c ON t.client_id=c.id
     LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id=t.assigned_worker_id
     WHERE $where ORDER BY t.created_at DESC LIMIT 200"
);
$total    = count($tickets);
$open_cnt = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_support_tickets WHERE status='open'");

// KPI counts (v2.9.54)
$kpi_rows = $wpdb->get_results("SELECT status, COUNT(*) as cnt FROM {$wpdb->prefix}vesho_support_tickets GROUP BY status");
$kpi_map  = [];
foreach ($kpi_rows as $k) $kpi_map[$k->status] = (int)$k->cnt;
$kpi_all    = array_sum($kpi_map);
$kpi_open   = $kpi_map['open']        ?? 0;
$kpi_inprog = $kpi_map['in_progress'] ?? 0;
$kpi_closed = $kpi_map['closed']      ?? 0;

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

            <!-- Worker assignment -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <span style="font-size:13px;font-weight:600;color:#475569">👷 Vastutav töötaja:</span>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="display:flex;align-items:center;gap:8px">
                    <?php wp_nonce_field('vesho_ticket_action'); ?>
                    <input type="hidden" name="action" value="vesho_assign_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $edit->id; ?>">
                    <select name="assigned_worker_id" class="crm-form-select" style="font-size:13px;padding:5px 10px;min-width:180px">
                        <option value="0">— Määramata —</option>
                        <?php foreach ($all_workers as $w): ?>
                        <option value="<?php echo $w->id; ?>" <?php selected((int)($edit->assigned_worker_id??0), (int)$w->id); ?>><?php echo esc_html($w->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Salvesta</button>
                </form>
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
    <!-- KPI cards (v2.9.54) -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
        <div class="crm-stat-card" style="border-top:3px solid #6b7280;cursor:pointer" onclick="tkFilterStatus('')">
            <div class="crm-stat-num"><?php echo $kpi_all; ?></div>
            <div class="crm-stat-label">Kõik piletid</div>
        </div>
        <div class="crm-stat-card" style="border-top:3px solid #f59e0b;cursor:pointer" onclick="tkFilterStatus('open')">
            <div class="crm-stat-num" style="color:#f59e0b"><?php echo $kpi_open; ?></div>
            <div class="crm-stat-label">Avatud</div>
        </div>
        <div class="crm-stat-card" style="border-top:3px solid #3b82f6;cursor:pointer" onclick="tkFilterStatus('in_progress')">
            <div class="crm-stat-num" style="color:#3b82f6"><?php echo $kpi_inprog; ?></div>
            <div class="crm-stat-label">Töös</div>
        </div>
        <div class="crm-stat-card" style="border-top:3px solid #10b981;cursor:pointer" onclick="tkFilterStatus('closed')">
            <div class="crm-stat-num" style="color:#10b981"><?php echo $kpi_closed; ?></div>
            <div class="crm-stat-label">Suletud</div>
        </div>
    </div>

    <!-- Split view -->
    <div class="crm-card" style="overflow:hidden">
        <div style="display:flex;min-height:560px">

            <!-- Left panel: ticket list -->
            <div style="width:360px;flex-shrink:0;border-right:1px solid #e2e8f0;display:flex;flex-direction:column">
                <!-- Search/filter toolbar -->
                <div style="padding:10px 12px;border-bottom:1px solid #e2e8f0;flex-shrink:0">
                    <form method="GET" style="display:flex;gap:6px">
                        <input type="hidden" name="page" value="vesho-crm-tickets">
                        <select id="tk-status-filter" class="crm-form-select" name="status" style="font-size:12px;padding:5px 8px;max-width:120px">
                            <option value="">Kõik</option>
                            <?php foreach ($statuses as $v=>$l) : ?>
                                <option value="<?php echo $v; ?>" <?php selected($filter_st,$v); ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="crm-form-input" type="search" name="s" placeholder="Otsi..." value="<?php echo esc_attr($search); ?>" style="font-size:12px;padding:5px 8px">
                        <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm" style="padding:5px 10px;font-size:11px">🔍</button>
                    </form>
                </div>
                <!-- Ticket rows -->
                <div style="overflow-y:auto;flex:1">
                <?php if (empty($tickets)) : ?>
                    <div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px">Pileteid ei leitud.</div>
                <?php else : ?>
                <?php foreach ($tickets as $t) : ?>
                <div class="tk-list-row" data-id="<?php echo $t->id; ?>"
                     style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:.12s<?php if ($t->status==='open') echo ';background:#fefce8'; ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">
                        <span style="font-size:11px;color:#94a3b8">#<?php echo $t->id; ?></span>
                        <?php echo vesho_crm_status_badge($t->status); ?>
                    </div>
                    <div style="font-size:13px;font-weight:600;color:#1a2a38;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($t->subject); ?></div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px"><?php echo esc_html($t->client_name??'–'); ?> · <?php echo vesho_crm_format_date($t->created_at,'d.m.Y'); ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>

            <!-- Right panel: detail (AJAX) -->
            <div id="tk-detail-panel" style="flex:1;overflow-y:auto">
                <div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:300px;color:#94a3b8;font-size:14px;flex-direction:column;gap:8px">
                    <span style="font-size:32px">🎫</span>
                    <span>← Vali pilet nimekirjast</span>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>
</div>
<script>
var tkNonce  = '<?php echo wp_create_nonce('vesho_admin_nonce'); ?>';
var tkAjax   = '<?php echo admin_url('admin-ajax.php'); ?>';
var tkStatuses   = <?php echo json_encode($statuses); ?>;
var tkPriorities = <?php echo json_encode($priorities); ?>;
var tkPcls       = <?php echo json_encode($pcls); ?>;

function tkFilterStatus(st) {
    var sel = document.getElementById('tk-status-filter');
    if (sel) { sel.value = st; sel.closest('form').submit(); }
}

document.querySelectorAll('.tk-list-row').forEach(function(row) {
    row.addEventListener('click', function() {
        document.querySelectorAll('.tk-list-row').forEach(function(r){ r.style.background = r.dataset.origBg || ''; });
        row.dataset.origBg = row.style.background;
        row.style.background = '#eff6ff';
        loadTicketDetail(parseInt(row.dataset.id));
    });
});

function loadTicketDetail(id) {
    var panel = document.getElementById('tk-detail-panel');
    panel.innerHTML = '<div style="padding:32px;text-align:center;color:#94a3b8">Laadin...</div>';
    fetch(tkAjax, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_get_ticket_detail&ticket_id='+id+'&_ajax_nonce='+tkNonce
    }).then(function(r){return r.json();}).then(function(d){
        if (!d.success) { panel.innerHTML='<div style="padding:24px;color:#ef4444">Viga laadimisel</div>'; return; }
        renderTicketDetail(d.data, panel);
    });
}

function renderTicketDetail(data, panel) {
    var t = data.ticket; var replies = data.replies;
    var stLabel = tkStatuses[t.status]||t.status;
    var prLabel = tkPriorities[t.priority]||t.priority;
    var prCls   = tkPcls[t.priority]||'badge-gray';
    var html    = '<div style="padding:20px">';
    // Header
    html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:8px">';
    html += '<div><div style="font-size:11px;color:#94a3b8;margin-bottom:3px">#'+t.id+'</div>';
    html += '<h3 style="font-size:15px;font-weight:700;color:#1a2a38;margin:0 0 4px">'+tkEsc(t.subject)+'</h3>';
    html += '<div style="font-size:12px;color:#64748b">'+tkEsc(t.client_name||'–')+(t.client_email?(' · <a href="mailto:'+tkEsc(t.client_email)+'">'+tkEsc(t.client_email)+'</a>'):'')+'</div></div>';
    html += '<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">';
    html += '<span id="tk-sbadge-'+t.id+'" class="crm-badge '+tkStCls(t.status)+'">'+stLabel+'</span>';
    html += '<span class="crm-badge '+prCls+'">'+prLabel+'</span></div></div>';
    // Thread
    html += '<div id="tk-thread" style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;max-height:320px;overflow-y:auto">';
    html += tkBuildMsg(t.client_name||'Klient', t.created_at, t.message, false);
    for (var i=0;i<replies.length;i++) { html += tkBuildMsg(replies[i].author, replies[i].created_at, replies[i].message, replies[i].author!=='client'); }
    html += '</div>';
    // Status buttons
    html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;align-items:center">';
    html += '<strong style="font-size:12px;color:#64748b">Muuda:</strong>';
    ['open','in_progress','closed','spam'].forEach(function(sv){
        var dis = sv===t.status ? ' disabled style="opacity:.4"' : '';
        html += '<button class="crm-btn crm-btn-outline crm-btn-sm" onclick="tkSetStatus('+t.id+',\''+sv+'\',this)"'+dis+'>'+(tkStatuses[sv]||sv)+'</button>';
    });
    html += '</div>';
    // Reply form
    html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px">';
    html += '<label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px">✉️ Vastus kliendile</label>';
    html += '<textarea id="tk-reply-'+t.id+'" class="crm-form-textarea" style="min-height:80px;font-size:13px" placeholder="Kirjuta vastus..."></textarea>';
    html += '<div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">';
    html += '<label style="font-size:12px;cursor:pointer;display:flex;gap:5px;align-items:center"><input type="checkbox" id="tk-close-'+t.id+'"> Sulge pilet pärast vastust</label>';
    html += '<button class="crm-btn crm-btn-primary crm-btn-sm" onclick="tkReply('+t.id+')">✉️ Saada</button>';
    html += '</div></div></div>';
    panel.innerHTML = html;
}

function tkStCls(s) {
    return {open:'badge-warning',in_progress:'badge-info',closed:'badge-success',spam:'badge-danger'}[s]||'badge-gray';
}

function tkBuildMsg(author, time, msg, isAdmin) {
    var d = isAdmin?'flex-direction:row-reverse;':'';
    var bb = isAdmin?'background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px 0 8px 8px':'background:#f4f7f9;border-radius:0 8px 8px 8px';
    var na = isAdmin?'text-align:right;':'';
    var ic = isAdmin?'🛠️':'👤'; var ib = isAdmin?'#d1fae5':'#e0f2fe';
    return '<div style="display:flex;gap:10px;align-items:flex-start;'+d+'">'
        +'<div style="width:28px;height:28px;border-radius:50%;background:'+ib+';display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0">'+ic+'</div>'
        +'<div style="flex:1"><div style="font-size:11px;color:#6b8599;margin-bottom:3px;'+na+'"><strong style="color:#1a2a38">'+tkEsc(author)+'</strong> · '+tkEsc(time||'')+'</div>'
        +'<div style="'+bb+';padding:10px 14px;font-size:13px;line-height:1.6;white-space:pre-wrap">'+tkEsc(msg||'')+'</div></div></div>';
}

function tkSetStatus(id, status, btn) {
    if (btn.disabled) return;
    fetch(tkAjax, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_ajax_ticket_status&ticket_id='+id+'&status='+status+'&_ajax_nonce='+tkNonce
    }).then(function(r){return r.json();}).then(function(d){
        if (d.success) {
            var badge = document.getElementById('tk-sbadge-'+id);
            if (badge) { badge.className='crm-badge '+tkStCls(status); badge.textContent=tkStatuses[status]||status; }
            var listRow = document.querySelector('.tk-list-row[data-id="'+id+'"]');
            if (listRow) {
                var lb = listRow.querySelector('.crm-badge');
                if (lb) { lb.className='crm-badge '+tkStCls(status); lb.textContent=tkStatuses[status]||status; }
            }
            document.querySelectorAll('[onclick^="tkSetStatus('+id+'"]').forEach(function(b){ b.disabled=false; });
            btn.disabled = true;
        }
    });
}

function tkReply(id) {
    var ta = document.getElementById('tk-reply-'+id);
    var msg = ta ? ta.value.trim() : '';
    if (!msg) { ta && ta.focus(); return; }
    var close = document.getElementById('tk-close-'+id);
    var shouldClose = (close && close.checked) ? '1' : '0';
    fetch(tkAjax, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_ajax_reply_ticket&ticket_id='+id+'&message='+encodeURIComponent(msg)+'&close_ticket='+shouldClose+'&_ajax_nonce='+tkNonce
    }).then(function(r){return r.json();}).then(function(d){
        if (d.success) {
            var thread = document.getElementById('tk-thread');
            if (thread) {
                thread.insertAdjacentHTML('beforeend', tkBuildMsg(d.data.author, d.data.time, d.data.message, true));
                thread.scrollTop = thread.scrollHeight;
            }
            if (ta) ta.value = '';
            if (close) close.checked = false;
            if (d.data.closed) tkSetStatus(id, 'closed', document.querySelector('[onclick="tkSetStatus('+id+',\'closed\',this)"]')||{disabled:false});
        } else {
            alert(d.data||'Viga saatmisel');
        }
    });
}

function tkEsc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
