<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action      = isset($_GET['action'])      ? sanitize_text_field($_GET['action'])  : '';
$campaign_id = isset($_GET['campaign_id']) ? absint($_GET['campaign_id'])          : 0;
$search      = isset($_GET['s'])           ? sanitize_text_field($_GET['s'])       : '';

$edit = null;
if ( $action === 'edit' && $campaign_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_campaigns WHERE id=%d", $campaign_id));
}

// Compute statuses dynamically based on dates + paused flag
$today = date('Y-m-d');
$where = '1=1';
if ($search) { $where .= $wpdb->prepare(' AND name LIKE %s', '%'.$wpdb->esc_like($search).'%'); }
$campaigns = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_campaigns WHERE $where ORDER BY created_at DESC LIMIT 100");
$total = count($campaigns);

function vesho_campaign_status( $cam ) {
    $today = date('Y-m-d');
    if ( $cam->paused ) return ['paused', '⏸ Peatatud', '#f59e0b', '#fff8ed'];
    if ( $cam->valid_from && $cam->valid_from > $today ) return ['upcoming', '🟡 Tulemas', '#d97706', '#fffbeb'];
    if ( $cam->valid_until && $cam->valid_until < $today ) return ['ended', '🔴 Lõppenud', '#ef4444', '#fff0f0'];
    if ( $cam->valid_from && $cam->valid_until ) return ['active', '🟢 Aktiivne', '#10b981', '#f0fdf4'];
    return ['draft', '⚙️ Mustand', '#6b7280', '#f9fafb'];
}
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">🎉 Kampaaniad <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        echo '<div class="crm-alert crm-alert-success">Salvestatud!</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda kampaaniat':'➕ Uus kampaania'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-campaigns'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_campaign'); ?>
            <input type="hidden" name="action" value="vesho_save_campaign">
            <?php if ($edit) : ?><input type="hidden" name="campaign_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kampaania nimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit->name??''); ?>"
                           placeholder="nt. Kevadkampaania 2026" required>
                    <small style="color:#6b8599;font-size:12px">Kuvatakse kliendile bänneris</small>
                </div>

                <div class="crm-form-group">
                    <label class="crm-form-label">Alguskuupäev</label>
                    <input class="crm-form-input" type="date" name="valid_from" value="<?php echo esc_attr($edit->valid_from??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Lõppkuupäev</label>
                    <input class="crm-form-input" type="date" name="valid_until" value="<?php echo esc_attr($edit->valid_until??''); ?>">
                </div>

                <div class="crm-form-group">
                    <label class="crm-form-label">Sihtmärk</label>
                    <select class="crm-form-select" name="target">
                        <option value="both"  <?php selected($edit->target??'both','both'); ?>>🛒+🔧 Mõlemad</option>
                        <option value="epood" <?php selected($edit->target??'','epood'); ?>>🛒 E-pood</option>
                        <option value="hooldus" <?php selected($edit->target??'','hooldus'); ?>>🔧 Hooldus</option>
                    </select>
                </div>

                <div class="crm-form-group" style="display:flex;flex-direction:column;gap:10px;justify-content:flex-end">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                        <input type="checkbox" name="visible_to_guests" value="1" <?php checked($edit->visible_to_guests??1,1); ?> style="accent-color:#00b4c8">
                        Kehtib ka külalis-ostjatele (sisselogimata)
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                        <input type="checkbox" name="paused" value="1" <?php checked($edit->paused??0,1); ?> style="accent-color:#f59e0b">
                        ⏸ Kampaania peatatud
                    </label>
                </div>

                <div style="grid-column:1/-1;border-top:1px solid #e2e8f0;padding-top:16px;margin-top:4px">
                    <div style="font-weight:600;font-size:13px;margin-bottom:12px">🛒 E-poe soodustused</div>
                    <div class="crm-form-grid" style="grid-template-columns:1fr 1fr;gap:12px">
                        <div class="crm-form-group">
                            <label class="crm-form-label">E-poe soodustus %</label>
                            <input class="crm-form-input" type="number" step="1" min="0" max="100"
                                   name="discount_percent" value="<?php echo esc_attr($edit->discount_percent??''); ?>"
                                   placeholder="nt. 15">
                            <small style="color:#6b8599;font-size:12px">0 = ei rakendu. Kuvatakse tootekaartidel.</small>
                        </div>
                        <div class="crm-form-group" style="display:flex;align-items:flex-end">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                                <input type="checkbox" name="free_shipping" value="1" <?php checked($edit->free_shipping??0,1); ?> style="accent-color:#00b4c8">
                                🚚 Tasuta tarne (tühistab seadetes määratud tarnehinna)
                            </label>
                        </div>
                    </div>
                </div>

                <div style="grid-column:1/-1;border-top:1px solid #e2e8f0;padding-top:16px">
                    <div style="font-weight:600;font-size:13px;margin-bottom:12px">🔧 Hoolduse soodustus</div>
                    <div class="crm-form-grid" style="grid-template-columns:1fr 1fr;gap:12px">
                        <div class="crm-form-group">
                            <label class="crm-form-label">Hoolduse soodustus %</label>
                            <input class="crm-form-input" type="number" step="1" min="0" max="100"
                                   name="maintenance_discount_percent" value="<?php echo esc_attr($edit->maintenance_discount_percent??''); ?>"
                                   placeholder="nt. 10">
                            <small style="color:#6b8599;font-size:12px">0 = ei rakendu. Rakendub hoolduse hinnakaartidel.</small>
                        </div>
                    </div>
                </div>

                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus (kuvatakse kliendile bänneris)</label>
                    <textarea class="crm-form-textarea" name="notes" rows="2"><?php echo esc_textarea($edit->notes??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-campaigns'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>

    <?php else : ?>

    <?php
    // Show any currently active campaign as info
    $active_cams = array_filter($campaigns, function($cam) {
        $s = vesho_campaign_status($cam);
        return $s[0] === 'active';
    });
    if (!empty($active_cams)) :
        $ac = reset($active_cams);
    ?>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;gap:12px;align-items:center">
        <div style="font-size:20px">🎉</div>
        <div>
            <strong style="color:#166534"><?php echo esc_html($ac->name); ?></strong> on praegu aktiivne
            <?php if ($ac->discount_percent > 0) echo ' — <strong>🛒 '.floatval($ac->discount_percent).'% e-poe allahindlus</strong>'; ?>
            <?php if (!empty($ac->maintenance_discount_percent) && $ac->maintenance_discount_percent > 0) echo ' — <strong>🔧 '.floatval($ac->maintenance_discount_percent).'% hoolduse allahindlus</strong>'; ?>
            <?php if ($ac->free_shipping) echo ' + <strong>tasuta tarne</strong>'; ?>
            <?php if ($ac->valid_until) echo ' kuni <strong>'.vesho_crm_format_date($ac->valid_until).'</strong>'; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-campaigns&action=add'); ?>" class="crm-btn crm-btn-primary">➕ Uus kampaania</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-campaigns">
                <input class="crm-search" type="search" name="s" placeholder="Otsi kampaaniat..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($campaigns)) : ?>
            <div class="crm-empty">Kampaaniaid ei leitud. <a href="<?php echo admin_url('admin.php?page=vesho-crm-campaigns&action=add'); ?>">Lisa esimene kampaania →</a></div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>Nimi</th><th>Sihtmärk</th><th>Allahindlus</th><th>Kehtib alates</th><th>Kehtib kuni</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($campaigns as $cam) :
                [$slug, $label, $color, $bg] = vesho_campaign_status($cam);
                $target_labels = ['both'=>'🛒+🔧 Mõlemad','epood'=>'🛒 E-pood','hooldus'=>'🔧 Hooldus'];
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($cam->name); ?></strong>
                    <?php if ($cam->notes) : ?><br><small style="color:#6b8599"><?php echo esc_html(substr($cam->notes,0,60)); ?></small><?php endif; ?>
                </td>
                <td><?php echo esc_html($target_labels[$cam->target??'both']??$cam->target); ?></td>
                <td>
                    <?php if ($cam->discount_percent > 0) echo '🛒 <strong>'.floatval($cam->discount_percent).'%</strong>'; ?>
                    <?php if (!empty($cam->maintenance_discount_percent) && $cam->maintenance_discount_percent > 0) echo ($cam->discount_percent > 0 ? '<br>' : '') . '🔧 <strong>'.floatval($cam->maintenance_discount_percent).'%</strong>'; ?>
                    <?php if ($cam->discount_percent <= 0 && (empty($cam->maintenance_discount_percent) || $cam->maintenance_discount_percent <= 0)) echo '<span style="color:#aaa">–</span>'; ?>
                    <?php if ($cam->free_shipping) echo ' <span style="font-size:11px;color:#6b8599">+ tasuta tarne</span>'; ?>
                </td>
                <td><?php echo $cam->valid_from ? vesho_crm_format_date($cam->valid_from) : '<span style="color:#aaa">–</span>'; ?></td>
                <td><?php echo $cam->valid_until ? vesho_crm_format_date($cam->valid_until) : '<span style="color:#aaa">–</span>'; ?></td>
                <td>
                    <span style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;white-space:nowrap">
                        <?php echo $label; ?>
                    </span>
                </td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-campaigns&action=edit&campaign_id='.$cam->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <?php if (!$cam->paused) : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_pause_campaign&campaign_id='.$cam->id),'vesho_pause_campaign'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Peata">⏸</a>
                    <?php else : ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_resume_campaign&campaign_id='.$cam->id),'vesho_resume_campaign'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" title="Jätka">▶</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="padding:12px 20px;font-size:12px;color:#6b8599;border-top:1px solid #e2e8f0">
            Aktiivne kampaania rakendatakse automaatselt e-poes ja hoolduse kaartidel. Kampaania allahindlus arvutatakse serveris — klient ei saa seda manipuleerida.
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
