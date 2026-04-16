<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action     = isset($_GET['action'])     ? sanitize_text_field($_GET['action'])  : '';
$service_id = isset($_GET['service_id']) ? absint($_GET['service_id'])            : 0;

$edit = null;
if ( $action === 'edit' && $service_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_services WHERE id=%d", $service_id));
}

$services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_services ORDER BY sort_order ASC, name ASC");
$total    = count($services);
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">⚙️ Teenused <span class="crm-count">(<?php echo $total; ?>)</span></h1>
    <p style="color:#6b8599;margin-bottom:16px;font-size:13px">Teenused kuvatakse veebilehel ning kasutatakse pakkumiste ja arvete loomisel.</p>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['saved'=>'Teenus salvestatud!','deleted'=>'Teenus kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda teenust':'Lisa uus teenus'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-services'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_service'); ?>
            <input type="hidden" name="action" value="vesho_save_service">
            <?php if ($edit) : ?><input type="hidden" name="service_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Teenuse nimi *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit->name??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Ikoon (emoji)</label>
                    <input class="crm-form-input" type="text" name="icon" value="<?php echo esc_attr($edit->icon??'💧'); ?>" style="max-width:80px;font-size:22px;text-align:center">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Alghind (€)</label>
                    <input class="crm-form-input" type="number" step="0.01" name="price" value="<?php echo esc_attr($edit->price??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Ühik (nt. /kuu, /kord)</label>
                    <input class="crm-form-input" type="text" name="price_unit" value="<?php echo esc_attr($edit->price_unit??''); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Järjekord</label>
                    <input class="crm-form-input" type="number" name="sort_order" value="<?php echo esc_attr($edit->sort_order??0); ?>">
                </div>
                <div class="crm-form-group" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:8px;padding-top:22px">
                    <input type="checkbox" name="active" id="svc_active" value="1" <?php checked($edit->active??1,1); ?>>
                    <label for="svc_active" class="crm-form-label" style="margin:0">Aktiivne (näidatakse veebis)</label>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus</label>
                    <textarea class="crm-form-textarea" name="description" rows="4"><?php echo esc_textarea($edit->description??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-services'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-services&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa teenus</a>
        </div>
        <?php if (empty($services)) : ?>
            <div class="crm-empty">Teenuseid ei leitud.</div>
        <?php else : ?>
        <table class="crm-table">
            <thead><tr>
                <th>Ikoon</th><th>Nimi</th><th>Alghind</th><th>Ühik</th><th>Staatus</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($services as $svc) : ?>
            <tr>
                <td style="font-size:22px;text-align:center"><?php echo esc_html($svc->icon); ?></td>
                <td>
                    <strong><?php echo esc_html($svc->name); ?></strong>
                    <?php if ($svc->description) : ?><br><small style="color:#6b8599"><?php echo esc_html(substr($svc->description,0,70)); ?></small><?php endif; ?>
                </td>
                <td><?php echo $svc->price ? 'alates '.vesho_crm_format_money($svc->price) : '–'; ?></td>
                <td><?php echo esc_html($svc->price_unit?:'–'); ?></td>
                <td><?php echo $svc->active ? '<span class="crm-badge badge-success">Aktiivne</span>' : '<span class="crm-badge badge-gray">Peidetud</span>'; ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-services&action=edit&service_id='.$svc->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_service&service_id='.$svc->id),'vesho_delete_service'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta teenus?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
