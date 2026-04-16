<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action    = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$client_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
$search    = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$type_f    = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$per_page  = 50;
$paged     = max(1, (int)($_GET['paged'] ?? 1));
$offset    = ($paged - 1) * $per_page;

$args = ['limit' => $per_page, 'offset' => $offset, 'search' => $search, 'type' => $type_f];
$clients = Vesho_CRM_Database::get_clients($args);
$total   = Vesho_CRM_Database::count_clients();
$pages   = ceil($total / $per_page);

// Edit mode
$edit_client = null;
if ( $action === 'edit' && $client_id ) {
    $edit_client = Vesho_CRM_Database::get_client($client_id);
}
// Prefill from guest request
$prefill = [];
if ( $action === 'add' && isset($_GET['prefill_name']) ) {
    $prefill['name']  = sanitize_text_field($_GET['prefill_name'] ?? '');
    $prefill['email'] = sanitize_email($_GET['prefill_email'] ?? '');
    $prefill['phone'] = sanitize_text_field($_GET['prefill_phone'] ?? '');
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">👥 Kliendid <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if ( isset($_GET['msg']) ) : ?>
        <?php $msgs = ['added'=>'Klient lisatud!','updated'=>'Klient uuendatud!','deleted'=>'Klient kustutatud!']; ?>
        <div class="crm-alert crm-alert-success"><?php echo esc_html($msgs[$_GET['msg']] ?? 'Muudatused salvestatud!'); ?></div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <!-- ── Add / Edit form ── -->
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action === 'edit' ? 'Muuda klienti' : 'Lisa uus klient'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_client'); ?>
            <input type="hidden" name="action" value="vesho_save_client">
            <?php if ($edit_client) : ?>
                <input type="hidden" name="client_id" value="<?php echo $edit_client->id; ?>">
            <?php endif; ?>

            <!-- Tüüp toggle -->
            <div class="crm-form-group crm-form-full" style="margin-bottom:16px">
                <label class="crm-form-label">Kliendi tüüp</label>
                <div style="display:flex;gap:8px;margin-top:4px">
                    <label style="cursor:pointer">
                        <input type="radio" name="client_type" value="eraisik" id="ct_eraisik"
                            <?php checked($edit_client->client_type ?? 'eraisik', 'eraisik'); ?>>
                        <span class="ct-btn" data-type="eraisik" style="display:inline-block;padding:6px 16px;border:2px solid #ddd;border-radius:6px;font-weight:600;font-size:13px;transition:.15s">👤 Eraisik</span>
                    </label>
                    <label style="cursor:pointer">
                        <input type="radio" name="client_type" value="ettevote" id="ct_ettevote"
                            <?php checked($edit_client->client_type ?? '', 'ettevote'); ?>>
                        <span class="ct-btn" data-type="ettevote" style="display:inline-block;padding:6px 16px;border:2px solid #ddd;border-radius:6px;font-weight:600;font-size:13px;transition:.15s">🏢 Ettevõte</span>
                    </label>
                </div>
            </div>

            <div class="crm-form-grid">
                <div class="crm-form-group" id="lbl-name-group">
                    <label class="crm-form-label" id="lbl-name">Täisnimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit_client->name ?? $prefill['name'] ?? ''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">E-post *</label>
                    <input class="crm-form-input" type="email" name="email" value="<?php echo esc_attr($edit_client->email ?? $prefill['email'] ?? ''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Telefon</label>
                    <input class="crm-form-input" type="text" name="phone" value="<?php echo esc_attr($edit_client->phone ?? $prefill['phone'] ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field" id="company-group">
                    <label class="crm-form-label">Ettevõtte nimi *</label>
                    <input class="crm-form-input" type="text" name="company" id="company_input" value="<?php echo esc_attr($edit_client->company ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field">
                    <label class="crm-form-label">Reg. kood</label>
                    <input class="crm-form-input" type="text" name="reg_code" value="<?php echo esc_attr($edit_client->reg_code ?? ''); ?>">
                </div>
                <div class="crm-form-group firma-field">
                    <label class="crm-form-label">KM registreering</label>
                    <input class="crm-form-input" type="text" name="vat_number" value="<?php echo esc_attr($edit_client->vat_number ?? ''); ?>">
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Aadress</label>
                    <input class="crm-form-input" type="text" name="address" value="<?php echo esc_attr($edit_client->address ?? ''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Portaali parool <?php echo $edit_client ? '(tühi = ei muutu)' : ''; ?></label>
                    <input class="crm-form-input" type="password" name="password" placeholder="<?php echo $edit_client ? 'Jäta tühjaks muutmata jätmiseks' : 'Portaali ligipääsu parool'; ?>" autocomplete="new-password">
                </div>
                <?php if ($edit_client && $edit_client->user_id) :
                    $wp_u = get_userdata($edit_client->user_id); ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">WP kasutaja</label>
                    <div style="padding:8px 12px;background:#f0f9f0;border:1px solid #b8e6b8;border-radius:6px;font-size:13px;color:#2d6a2d">
                        ✅ <?php echo $wp_u ? esc_html($wp_u->user_login) : 'Kasutaja #'.$edit_client->user_id; ?>
                        &nbsp;·&nbsp;<a href="<?php echo admin_url('user-edit.php?user_id='.$edit_client->user_id); ?>" target="_blank">WP profiil →</a>
                    </div>
                </div>
                <?php elseif ($edit_client) : ?>
                <div class="crm-form-group">
                    <label class="crm-form-label">WP kasutaja</label>
                    <div style="padding:8px 12px;background:#fff8e1;border:1px solid #f0d060;border-radius:6px;font-size:13px;color:#7a5c00">
                        ⚠️ Portaali ligipääs puudub — salvesta parooliga et luua
                    </div>
                </div>
                <?php endif; ?>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Märkused</label>
                    <textarea class="crm-form-textarea" name="notes"><?php echo esc_textarea($edit_client->notes ?? ''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <script>
    (function(){
        var radios = document.querySelectorAll('input[name="client_type"]');
        var firmaFields = document.querySelectorAll('.firma-field');
        var lblName = document.getElementById('lbl-name');
        var companyInput = document.getElementById('company_input');
        function updateType(val){
            var isFirma = val === 'ettevote';
            firmaFields.forEach(function(el){ el.style.display = isFirma ? '' : 'none'; });
            if(lblName) lblName.textContent = isFirma ? 'Kontaktisik *' : 'Täisnimi *';
            if(companyInput) companyInput.required = isFirma;
            document.querySelectorAll('.ct-btn').forEach(function(btn){
                var active = btn.dataset.type === val;
                btn.style.borderColor = active ? '#2271b1' : '#ddd';
                btn.style.background  = active ? '#e8f0fb' : '';
                btn.style.color       = active ? '#2271b1' : '';
            });
        }
        radios.forEach(function(r){
            r.addEventListener('change', function(){ updateType(this.value); });
            if(r.checked) updateType(r.value);
        });
        // init
        var checked = document.querySelector('input[name="client_type"]:checked');
        if(checked) updateType(checked.value);
    })();
    </script>
    <?php else : ?>

    <!-- ── List ── -->
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa klient</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-clients">
                <input class="crm-search" type="search" name="s" placeholder="Otsi nime, e-posti, telefoni..." value="<?php echo esc_attr($search); ?>">
                <select class="crm-filter" name="type">
                    <option value="">Kõik tüübid</option>
                    <option value="eraisik" <?php selected($type_f,'eraisik'); ?>>Eraisik</option>
                    <option value="ettevote" <?php selected($type_f,'ettevote'); ?>>Ettevõte</option>
                </select>
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($clients)) : ?>
            <div class="crm-empty">Kliente ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimi</th><th>Ettevõte</th><th>E-post</th><th>Telefon</th><th>Tüüp</th><th>Portaal</th><th>Lisatud</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($clients as $c) : ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $c->id; ?></td>
                <td><strong><?php echo esc_html($c->name); ?></strong></td>
                <td><?php echo esc_html($c->company ?? '–'); ?></td>
                <td><a href="mailto:<?php echo esc_attr($c->email); ?>"><?php echo esc_html($c->email); ?></a></td>
                <td><?php echo esc_html($c->phone ?: '–'); ?></td>
                <td><?php echo vesho_crm_status_badge($c->client_type); ?></td>
                <td>
                    <?php if (!empty($c->user_id)) : ?>
                        <span class="crm-badge badge-success" title="WP kasutaja #<?php echo $c->user_id; ?>">✅ Aktiivne</span>
                    <?php else : ?>
                        <span class="crm-badge badge-gray">– Puudub</span>
                    <?php endif; ?>
                </td>
                <td><?php echo vesho_crm_format_date($c->created_at, 'd.m.Y'); ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&action=edit&client_id='.$c->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-devices&client_id='.$c->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Seadmed">🔧</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_client_send_access&client_id='.$c->id),'vesho_client_send_access'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Saada portaali ligipääs e-postile"
                       onclick="return confirm('Saada kliendile portaali parool e-postiga?')">📧</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_client&client_id='.$c->id), 'vesho_delete_client'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Kustuta"
                       onclick="return confirm('<?php esc_attr_e('Kustuta klient?','vesho-crm'); ?>')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pages > 1) : ?>
        <div class="crm-pager">
            <?php for ($p=1; $p<=$pages; $p++) : ?>
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-clients&paged='.$p.($search?'&s='.urlencode($search):'')); ?>"
                   <?php if ($p===$paged) echo 'class="current"'; ?>><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
