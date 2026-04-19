<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action      = sanitize_text_field($_GET['action'] ?? '');
$supplier_id = absint($_GET['supplier_id'] ?? 0);
$edit        = null;

if (in_array($action, ['edit','add']) && ($action === 'add' || $supplier_id)) {
    if ($supplier_id) {
        $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_suppliers WHERE id=%d", $supplier_id));
    }
}

$suppliers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_suppliers ORDER BY name ASC");
?>
<div class="crm-wrap">
<div class="crm-page-header">
    <div class="crm-page-header__logo">🏭</div>
    <div class="crm-page-header__body">
        <h1 class="crm-page-header__title">Tarnijad</h1>
        <p class="crm-page-header__subtitle">Halda tarnijaid ja ostutellimusi</p>
    </div>
    <div class="crm-page-header__actions">
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-suppliers&action=add'); ?>" class="crm-btn crm-btn-primary crm-btn-sm">+ Lisa tarnija</a>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-purchase-orders'); ?>" class="crm-btn crm-btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)">📦 Ostutellimused</a>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="crm-alert crm-alert-success"><?php echo ['saved'=>'Salvestatud!','deleted'=>'Kustutatud!'][$_GET['msg']] ?? 'OK'; ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="crm-card" style="margin-bottom:24px">
    <div class="crm-card-header">
        <span class="crm-card-title"><?php echo $edit ? 'Muuda tarnijat' : 'Lisa uus tarnija'; ?></span>
        <a href="<?php echo admin_url('admin.php?page=vesho-crm-suppliers'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
    </div>
    <div style="padding:20px">
    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('vesho_save_supplier'); ?>
        <input type="hidden" name="action" value="vesho_save_supplier">
        <input type="hidden" name="supplier_id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div>
                <label class="crm-form-label">Nimi *</label>
                <input type="text" name="name" class="crm-form-input" required value="<?php echo esc_attr($edit->name ?? ''); ?>" placeholder="Firma nimi">
            </div>
            <div>
                <label class="crm-form-label">Kontaktisik</label>
                <input type="text" name="contact" class="crm-form-input" value="<?php echo esc_attr($edit->contact ?? ''); ?>" placeholder="Kontaktisiku nimi">
            </div>
            <div>
                <label class="crm-form-label">E-post</label>
                <input type="email" name="email" class="crm-form-input" value="<?php echo esc_attr($edit->email ?? ''); ?>">
            </div>
            <div>
                <label class="crm-form-label">Telefon</label>
                <input type="text" name="phone" class="crm-form-input" value="<?php echo esc_attr($edit->phone ?? ''); ?>">
            </div>
            <div>
                <label class="crm-form-label">Reg. kood</label>
                <input type="text" name="reg_code" class="crm-form-input" value="<?php echo esc_attr($edit->reg_code ?? ''); ?>">
            </div>
            <div>
                <label class="crm-form-label">KM-kood</label>
                <input type="text" name="vat_number" class="crm-form-input" value="<?php echo esc_attr($edit->vat_number ?? ''); ?>">
            </div>
            <div style="grid-column:1/-1">
                <label class="crm-form-label">Aadress</label>
                <input type="text" name="address" class="crm-form-input" value="<?php echo esc_attr($edit->address ?? ''); ?>">
            </div>
            <div style="grid-column:1/-1">
                <label class="crm-form-label">Märkused</label>
                <textarea name="notes" class="crm-form-textarea" rows="3"><?php echo esc_textarea($edit->notes ?? ''); ?></textarea>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center">
            <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            <?php if ($edit): ?>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_supplier&supplier_id='.$edit->id), 'vesho_delete_supplier_'.$edit->id); ?>"
               class="crm-btn crm-btn-outline crm-btn-sm" style="color:#ef4444;border-color:#ef4444"
               onclick="return confirm('Kustuta tarnija?')">🗑 Kustuta</a>
            <?php endif; ?>
        </div>
    </form>
    </div>
</div>
<?php endif; ?>

<div class="crm-card">
    <div class="crm-card-header">
        <span class="crm-card-title">Tarnijad <span class="crm-count">(<?php echo count($suppliers); ?>)</span></span>
    </div>
    <?php if (empty($suppliers)): ?>
        <div class="crm-empty">Tarnijaid pole lisatud.</div>
    <?php else: ?>
    <table class="crm-table">
        <thead><tr>
            <th>Nimi</th><th>Kontakt</th><th>E-post</th><th>Telefon</th><th>Reg. kood</th><th class="td-actions">Toimingud</th>
        </tr></thead>
        <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
            <td style="font-weight:600"><?php echo esc_html($s->name); ?></td>
            <td><?php echo esc_html($s->contact); ?></td>
            <td><?php echo $s->email ? '<a href="mailto:'.esc_attr($s->email).'">'.esc_html($s->email).'</a>' : '—'; ?></td>
            <td><?php echo esc_html($s->phone ?: '—'); ?></td>
            <td><?php echo esc_html($s->reg_code ?: '—'); ?></td>
            <td class="td-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-suppliers&action=edit&supplier_id='.$s->id); ?>" class="crm-btn crm-btn-outline crm-btn-sm">Muuda</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>
