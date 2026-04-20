<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action'])    ? sanitize_text_field($_GET['action']) : '';
$worker_id = isset($_GET['worker_id']) ? absint($_GET['worker_id'])           : 0;
$search    = isset($_GET['s'])         ? sanitize_text_field($_GET['s'])      : '';

$edit = null;
if ( $action === 'edit' && $worker_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_workers WHERE id=%d", $worker_id));
}

$where = '1=1';
if ($search) { $where .= $wpdb->prepare(' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$workers = $wpdb->get_results(
    "SELECT w.*, COALESCE(ao.cnt,0) as active_orders
     FROM {$wpdb->prefix}vesho_workers w
     LEFT JOIN (
         SELECT worker_id, COUNT(*) as cnt
         FROM {$wpdb->prefix}vesho_workorders
         WHERE status IN ('assigned','in_progress','open')
         GROUP BY worker_id
     ) ao ON ao.worker_id = w.id
     WHERE $where ORDER BY w.name ASC LIMIT 200"
);
$total   = count($workers);

$roles = ['technician'=>'Tehnik','manager'=>'Juht','admin'=>'Admin','sales'=>'Müügijuht','other'=>'Muu'];

// Check if a worker is currently clocked in
function vesho_worker_is_clocked_in($worker_id) {
    global $wpdb;
    $open = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}vesho_work_hours WHERE worker_id=%d AND start_time IS NOT NULL AND end_time IS NULL ORDER BY id DESC LIMIT 1",
        $worker_id
    ));
    return !empty($open);
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">👷 Töötajad <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Töötaja lisatud!','updated'=>'Töötaja uuendatud!','deleted'=>'Töötaja kustutatud!','pin_sent'=>'PIN saadetud töö e-postile!','barcode_generated'=>'QR kood genereeritud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div style="display:grid;grid-template-columns:1fr <?php echo ($edit && !empty($edit->barcode_token)) ? '320px' : ''; ?>;gap:20px;align-items:start">
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda töötajat':'Lisa uus töötaja'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workers'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_worker'); ?>
            <input type="hidden" name="action" value="vesho_save_worker">
            <?php if ($edit) : ?><input type="hidden" name="worker_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group">
                    <label class="crm-form-label">Nimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit->name??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Isiklik e-post</label>
                    <input class="crm-form-input" type="email" name="email" value="<?php echo esc_attr($edit->email??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Töö e-post</label>
                    <input class="crm-form-input" type="email" name="work_email" value="<?php echo esc_attr($edit->work_email??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Telefon</label>
                    <input class="crm-form-input" type="text" name="phone" value="<?php echo esc_attr($edit->phone??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Roll</label>
                    <select class="crm-form-select" name="role">
                        <?php foreach ($roles as $v=>$l) : ?>
                            <option value="<?php echo $v; ?>" <?php selected($edit->role??'technician',$v); ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">PIN / Parool <?php echo $edit ? '(tühi = ei muuda)' : '*'; ?></label>
                    <div style="display:flex;gap:8px">
                        <input class="crm-form-input" type="text" name="password" id="worker_pin_field" <?php echo $edit?'':'required'; ?> placeholder="<?php echo $edit?'Jäta tühjaks muutmata jätmiseks':'6-kohaline PIN'; ?>">
                        <button type="button" onclick="genPin()" class="crm-btn crm-btn-outline crm-btn-sm" style="white-space:nowrap">🎲 Genereeri</button>
                    </div>
                </div>
                <?php if ($edit && !empty($edit->work_email)) : ?>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Saada PIN töö e-postile</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:13px;color:#6b8599"><?php echo esc_html($edit->work_email); ?></span>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_send_worker_pin&worker_id='.$edit->id),'vesho_send_worker_pin'); ?>"
                           class="crm-btn crm-btn-outline crm-btn-sm" onclick="return confirm('Saada uus PIN töö e-postile?')">📧 Saada PIN</a>
                    </div>
                </div>
                <?php endif; ?>
                <div class="crm-form-group crm-form-full" style="flex-direction:row;align-items:center;gap:24px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" name="active" id="worker_active" value="1" <?php checked($edit->active??1,1); ?> style="accent-color:#00b4c8">
                        <span class="crm-form-label" style="margin:0">Aktiivne</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer" title="Kuvatakse veebilehel 'Meist' meeskonna sektsioonis">
                        <input type="checkbox" name="show_on_website" id="worker_website" value="1" <?php checked($edit->show_on_website??0,1); ?> style="accent-color:#00b4c8">
                        <span class="crm-form-label" style="margin:0">🌐 Kuva veebilehel</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer" title="Töötajal on juurdepääs töötajaportaali laoseisu moodulile">
                        <input type="checkbox" name="can_inventory" id="worker_inventory" value="1" <?php checked($edit->can_inventory??0,1); ?> style="accent-color:#00b4c8">
                        <span class="crm-form-label" style="margin:0">📦 Inventuur (portaal)</span>
                    </label>
                </div>
                <?php if ($edit) : ?>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">QR / Barcodi token</label>
                    <?php if (!empty($edit->barcode_token)) : ?>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <code style="background:#f5f5f5;padding:6px 10px;border-radius:4px;font-size:12px;flex:1"><?php echo esc_html($edit->barcode_token); ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($edit->barcode_token); ?>').then(function(){this.textContent='✅';}.bind(this))" class="crm-btn crm-btn-outline crm-btn-sm">📋 Kopeeri</button>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_generate_worker_barcode&worker_id='.$edit->id),'vesho_generate_worker_barcode'); ?>"
                           class="crm-btn crm-btn-outline crm-btn-sm" onclick="return confirm('Genereeri uus QR kood? Vana enam ei tööta.')">🔄 Uuenda</a>
                    </div>
                    <?php else : ?>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:13px;color:#94a3b8">Token puudub</span>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_generate_worker_barcode&worker_id='.$edit->id),'vesho_generate_worker_barcode'); ?>"
                           class="crm-btn crm-btn-primary crm-btn-sm">📱 Genereeri QR kood</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-workers'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>

    <?php if ($edit && !empty($edit->barcode_token)) : ?>
    <!-- QR Code card -->
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title">📱 QR Kood</span>
        </div>
        <div style="padding:20px;text-align:center">
            <div id="worker-qr-<?php echo $edit->id; ?>" style="display:inline-block;padding:12px;background:#fff;border:1px solid #e0e0e0;border-radius:8px;margin-bottom:12px"></div>
            <div style="font-size:12px;color:#888;margin-bottom:12px">Skanni tööpäeva alustamiseks / lõpetamiseks</div>
            <div style="font-size:11px;color:#aaa;word-break:break-all;margin-bottom:12px"><?php echo esc_html($edit->barcode_token); ?></div>
            <button onclick="window.print()" class="crm-btn crm-btn-outline crm-btn-sm">🖨️ Prindi</button>
            <button onclick="downloadQR(<?php echo $edit->id; ?>,'<?php echo esc_js($edit->name); ?>')" class="crm-btn crm-btn-outline crm-btn-sm">⬇️ Lae alla</button>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    (function(){
        var qrEl = document.getElementById('worker-qr-<?php echo $edit->id; ?>');
        if(qrEl && typeof QRCode !== 'undefined'){
            new QRCode(qrEl, {
                text: '<?php echo esc_js($edit->barcode_token); ?>',
                width: 200, height: 200,
                colorDark: '#1a1a1a', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    })();
    function downloadQR(id, name){
        var canvas = document.querySelector('#worker-qr-' + id + ' canvas');
        if(!canvas){ alert('QR canvas ei leitud'); return; }
        var a = document.createElement('a');
        a.download = 'qr-' + name.replace(/[^a-z0-9]/gi,'_').toLowerCase() + '.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
    }
    </script>
    <?php endif; ?>
    </div><!-- /grid -->

    <script>
    function genPin(){
        var pin = Math.floor(100000 + Math.random() * 900000).toString();
        var el = document.getElementById('worker_pin_field');
        if(el){ el.value = pin; el.type = 'text'; }
    }
    </script>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-workers&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa töötaja</a>
            <?php
            // "Minu kaart" — näita praeguse sisselogitud kasutaja töötajakaart
            $my_worker = null;
            $current_uid = get_current_user_id();
            if ($current_uid) {
                $my_worker = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, name, barcode_token FROM {$wpdb->prefix}vesho_workers WHERE user_id=%d LIMIT 1",
                    $current_uid
                ));
            }
            if ($my_worker && !empty($my_worker->barcode_token)) : ?>
                <button type="button" class="crm-btn crm-btn-outline"
                    onclick="showQRModal('<?php echo esc_js($my_worker->barcode_token); ?>','<?php echo esc_js($my_worker->name); ?>',<?php echo (int)$my_worker->id; ?>)">
                    🪪 Minu kaart
                </button>
            <?php endif; ?>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-workers">
                <input class="crm-search" type="search" name="s" placeholder="Otsi nime, e-posti, telefoni..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($workers)) : ?>
            <div class="crm-empty">Töötajaid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimi</th><th>E-post</th><th>Telefon</th><th>Roll</th><th>Aktiivsed töökäsud</th><th>QR</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($workers as $w) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $w->id; ?></td>
                <td>
                    <strong><?php echo esc_html($w->name); ?></strong>
                    <?php if (vesho_worker_is_clocked_in($w->id)) : ?>
                        <span class="crm-badge badge-success" style="margin-left:6px;font-size:10px">🟢 Töös</span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($w->email?:'–'); ?></td>
                <td><?php echo esc_html($w->phone?:'–'); ?></td>
                <td><?php echo esc_html($roles[$w->role]??$w->role); ?></td>
                <td style="text-align:center">
                    <?php if ((int)$w->active_orders > 0) : ?>
                        <a href="<?php echo admin_url('admin.php?page=vesho-crm-workorders&worker_id='.$w->id); ?>"
                           style="font-weight:600;color:#0284c7"><?php echo (int)$w->active_orders; ?></a>
                    <?php else : ?>
                        <span style="color:#94a3b8">–</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($w->barcode_token)) : ?>
                        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Näita töötajakaarti"
                            onclick="showQRModal('<?php echo esc_js($w->barcode_token); ?>','<?php echo esc_js($w->name); ?>',<?php echo (int)$w->id; ?>)">🪪</button>
                    <?php else : ?>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_generate_worker_barcode&worker_id='.$w->id),'vesho_generate_worker_barcode'); ?>"
                           class="crm-btn crm-btn-icon crm-btn-sm" title="Genereeri QR kood">📱</a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $w->active ? '<span class="crm-badge badge-success">Aktiivne</span>' : '<span class="crm-badge badge-gray">Mitteaktiivne</span>'; ?>
                    <?php if ($w->show_on_website) echo '<span class="crm-badge" style="background:#e0f7fa;color:#00b4c8;margin-left:4px">🌐 Veebil</span>'; ?>
                </td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workers&action=edit&worker_id='.$w->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-workhours&worker_id='.$w->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Töötunnid">⏱️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_worker&worker_id='.$w->id),'vesho_delete_worker'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta töötaja?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- QR Modal -->
    <div id="qr-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:16px;padding:32px 28px 24px;text-align:center;width:320px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3)">
            <button onclick="document.getElementById('qr-modal').style.display='none'"
                style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;line-height:1">✕</button>
            <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;margin-bottom:10px">
                <?php echo esc_html(get_option('vesho_company_name','Vesho OÜ')); ?> · TÖÖTAJA KAART
            </div>
            <div id="qr-modal-name" style="font-size:22px;font-weight:800;color:#0d1f2d;margin-bottom:18px;font-family:'Barlow Condensed',sans-serif;letter-spacing:-.5px"></div>
            <div id="qr-modal-canvas" style="display:inline-block;padding:12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:8px;background:#fff"></div>
            <div id="qr-modal-token" style="font-size:10px;color:#94a3b8;word-break:break-all;margin-bottom:20px;font-family:monospace"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <button onclick="printWorkerCard()" style="padding:10px 0;background:#0284c7;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer">🖨️ Prindi</button>
                <button onclick="downloadModalQR()" style="padding:10px 0;background:#10b981;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer">⬇️ Lae alla</button>
                <button onclick="regenerateCard()" id="qr-regen-btn" style="padding:10px 0;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer">🔄 Uuenda kaart</button>
                <button onclick="document.getElementById('qr-modal').style.display='none'" style="padding:10px 0;background:#f1f5f9;color:#475569;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer">Sulge</button>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    var _qrModal = null;
    var _qrModalName = '';
    var _qrModalToken = '';
    var _qrModalWorkerId = 0;
    var _coName = <?php echo json_encode(get_option('vesho_company_name','Vesho OÜ')); ?>;

    function showQRModal(token, name, workerId){
        var modal = document.getElementById('qr-modal');
        var canvasEl = document.getElementById('qr-modal-canvas');
        canvasEl.innerHTML = '';
        _qrModalName = name;
        _qrModalToken = token;
        _qrModalWorkerId = workerId || 0;
        document.getElementById('qr-modal-name').textContent = name;
        document.getElementById('qr-modal-token').textContent = token;
        if(typeof QRCode !== 'undefined'){
            _qrModal = new QRCode(canvasEl, {
                text: token, width: 200, height: 200,
                colorDark: '#0d1f2d', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
        modal.style.display = 'flex';
    }
    document.getElementById('qr-modal').addEventListener('click', function(e){
        if(e.target === this) this.style.display = 'none';
    });
    function downloadModalQR(){
        var canvas = document.querySelector('#qr-modal-canvas canvas');
        if(!canvas) return;
        var a = document.createElement('a');
        a.download = 'tootajakaart-' + _qrModalName.replace(/[^a-z0-9]/gi,'_').toLowerCase() + '.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
    }
    function regenerateCard(){
        if(!_qrModalWorkerId) return;
        if(!confirm('Genereeri uus QR kood? Vana enam ei tööta.')) return;
        window.location = '<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_generate_worker_barcode&worker_id='), 'vesho_generate_worker_barcode'); ?>' + _qrModalWorkerId;
    }
    function printWorkerCard(){
        var canvas = document.querySelector('#qr-modal-canvas canvas');
        if(!canvas) return;
        var img = canvas.toDataURL('image/png');
        var w = window.open('','_blank','width=500,height=700');
        w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Töötaja kaart</title><style>'
            + '*{margin:0;padding:0;box-sizing:border-box}'
            + 'body{background:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:"Segoe UI",Arial,sans-serif}'
            + '.card{background:#fff;width:340px;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.18)}'
            + '.card-header{background:linear-gradient(135deg,#0d1f2d 0%,#162840 100%);padding:22px 20px 18px;text-align:center}'
            + '.card-header .co{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#00b4c8;margin-bottom:4px}'
            + '.card-header .title{font-size:13px;font-weight:600;color:rgba(255,255,255,.7);letter-spacing:.08em;text-transform:uppercase}'
            + '.card-body{padding:24px 20px 20px;text-align:center}'
            + '.worker-name{font-size:26px;font-weight:800;color:#0d1f2d;letter-spacing:-.5px;margin-bottom:16px;line-height:1.1}'
            + '.qr-wrap{display:inline-block;padding:12px;border:2px solid #e2e8f0;border-radius:12px;margin-bottom:12px;background:#fff}'
            + '.qr-wrap img{display:block;width:190px;height:190px}'
            + '.token{font-size:9px;color:#94a3b8;font-family:monospace;letter-spacing:.5px;margin-bottom:20px;word-break:break-all;padding:0 10px}'
            + '.card-footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:12px 20px;text-align:center}'
            + '.card-footer .site{font-size:11px;color:#94a3b8;font-weight:600}'
            + '@media print{'
            + '  body{background:#fff}'
            + '  .card{box-shadow:none;border-radius:0;width:100%}'
            + '  @page{margin:0;size:A6 portrait}'
            + '}'
        + '</style></head><body><div class="card">'
            + '<div class="card-header">'
            + '<div class="co">' + _coName + '</div>'
            + '<div class="title">Töötaja kaart</div>'
            + '</div>'
            + '<div class="card-body">'
            + '<div class="worker-name">' + _qrModalName + '</div>'
            + '<div class="qr-wrap"><img src="' + img + '"></div>'
            + '<div class="token">' + _qrModalToken + '</div>'
            + '</div>'
            + '<div class="card-footer"><div class="site"><?php echo esc_js(get_site_url()); ?></div></div>'
        + '</div><script>window.onload=function(){setTimeout(function(){window.print()},300)}<\/script></body></html>');
        w.document.close();
    }
    </script>
    <?php endif; ?>
</div>
