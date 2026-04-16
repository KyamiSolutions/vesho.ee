<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$action      = isset($_GET['action'])      ? sanitize_text_field($_GET['action'])  : '';
$item_id     = isset($_GET['pricelist_id'])? absint($_GET['pricelist_id'])          : 0;
$search      = isset($_GET['s'])           ? sanitize_text_field($_GET['s'])        : '';
$filter_cat  = isset($_GET['category'])    ? sanitize_text_field($_GET['category']) : '';

$edit = null;
if ( $action === 'edit' && $item_id ) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_price_list WHERE id=%d", $item_id));
}

$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}vesho_price_list WHERE category!='' ORDER BY category ASC");

$where = '1=1';
if ($filter_cat) { $where .= $wpdb->prepare(' AND category=%s', $filter_cat); }
if ($search)     { $where .= $wpdb->prepare(' AND (name LIKE %s OR description LIKE %s)', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%'); }

$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_price_list WHERE $where ORDER BY category,name ASC LIMIT 300");
$total = count($items);
?>
<div class="crm-wrap">
    <h1 class="crm-page-title">💰 Hinnakiri <span class="crm-count">(<?php echo $total; ?>)</span></h1>

    <?php if (isset($_GET['msg'])) :
        $msgs = ['added'=>'Kirje lisatud!','updated'=>'Kirje uuendatud!','deleted'=>'Kirje kustutatud!'];
        echo '<div class="crm-alert crm-alert-success">'.esc_html($msgs[$_GET['msg']]??'Salvestatud!').'</div>';
    endif; ?>

    <?php if ($action === 'edit' || $action === 'add') : ?>
    <div class="crm-card">
        <div class="crm-card-header">
            <span class="crm-card-title"><?php echo $action==='edit'?'Muuda hinnakirja kirjet':'Lisa uus hinnakirja kirje'; ?></span>
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-pricelist'); ?>" class="crm-btn crm-btn-outline crm-btn-sm">← Tagasi</a>
        </div>
        <div style="padding:20px">
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('vesho_save_pricelist'); ?>
            <input type="hidden" name="action" value="vesho_save_pricelist">
            <?php if ($edit) : ?><input type="hidden" name="pricelist_id" value="<?php echo $edit->id; ?>"><?php endif; ?>
            <div class="crm-form-grid">
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Nimetus *</label>
                    <input class="crm-form-input" type="text" name="name" value="<?php echo esc_attr($edit->name??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Kategooria</label>
                    <input class="crm-form-input" type="text" name="category" value="<?php echo esc_attr($edit->category??''); ?>" list="pl-cats">
                    <datalist id="pl-cats"><?php foreach ($categories as $cat) echo '<option value="'.esc_attr($cat).'">'; ?></datalist>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Ühik</label>
                    <input class="crm-form-input" type="text" name="unit" value="<?php echo esc_attr($edit->unit??'tk'); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Hind (€) *</label>
                    <input class="crm-form-input" type="number" step="0.01" name="price" value="<?php echo esc_attr($edit->price??''); ?>" required>
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">KM %</label>
                    <input class="crm-form-input" type="number" step="1" name="vat_rate" value="<?php echo esc_attr($edit->vat_rate??20); ?>">
                </div>
                <div class="crm-form-group">
                    <label class="crm-form-label">Seosta töötüübiga</label>
                    <select class="crm-form-select" name="work_type">
                        <option value="" <?php selected($edit->work_type??'',''); ?>>— Vali töötüüp —</option>
                        <option value="maintenance" <?php selected($edit->work_type??'','maintenance'); ?>>🔧 Hooldus</option>
                        <option value="installation" <?php selected($edit->work_type??'','installation'); ?>>🏗️ Paigaldus</option>
                        <option value="repair" <?php selected($edit->work_type??'','repair'); ?>>🛠️ Remont</option>
                        <option value="other" <?php selected($edit->work_type??'','other'); ?>>📋 Muu</option>
                    </select>
                    <small style="color:#6b8599;font-size:12px">Valitud töötüübiga töökäsud kasutavad seda hinda automaatselt arvel</small>
                </div>
                <div class="crm-form-group" style="display:flex;align-items:flex-end">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                        <input type="checkbox" name="visible_public" value="1" <?php checked($edit->visible_public??0,1); ?> style="accent-color:#00b4c8;width:16px;height:16px">
                        🌐 Nähtav avalikul hinnakirja lehel
                    </label>
                </div>
                <div class="crm-form-group crm-form-full">
                    <label class="crm-form-label">Kirjeldus</label>
                    <textarea class="crm-form-textarea" name="description"><?php echo esc_textarea($edit->description??''); ?></textarea>
                </div>
            </div>
            <div class="crm-form-actions">
                <a href="<?php echo admin_url('admin.php?page=vesho-crm-pricelist'); ?>" class="crm-btn crm-btn-outline">Tühista</a>
                <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
            </div>
        </form>
        </div>
    </div>
    <?php else : ?>
    <div class="crm-card">
        <div class="crm-toolbar">
            <a href="<?php echo admin_url('admin.php?page=vesho-crm-pricelist&action=add'); ?>" class="crm-btn crm-btn-primary">+ Lisa kirje</a>
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="hidden" name="page" value="vesho-crm-pricelist">
                <?php if (!empty($categories)) : ?>
                <select class="crm-form-select" name="category" style="max-width:160px;padding:7px 10px;font-size:13px">
                    <option value="">Kõik kategooriad</option>
                    <?php foreach ($categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat); ?>" <?php selected($filter_cat,$cat); ?>><?php echo esc_html($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <input class="crm-search" type="search" name="s" placeholder="Otsi nimetust..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
            </form>
        </div>
        <?php if (empty($items)) : ?>
            <div class="crm-empty">Hinnakirjas pole kirjeid.</div>
        <?php else : ?>
        <?php
        $work_type_labels = ['maintenance'=>'🔧 Hooldus','installation'=>'🏗️ Paigaldus','repair'=>'🛠️ Remont','other'=>'📋 Muu'];
        ?>
        <table class="crm-table">
            <thead><tr>
                <th>ID</th><th>Nimetus</th><th>Kategooria</th><th>Ühik</th><th>Hind (km-ta)</th><th>KM %</th><th>Hind (km-ga)</th><th>Töötüüp</th><th>Avalik</th><th class="td-actions">Toimingud</th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $item) :
                $price_with_vat = $item->price * (1 + ($item->vat_rate / 100));
            ?>
            <tr>
                <td style="color:#6b8599">#<?php echo $item->id; ?></td>
                <td><strong><?php echo esc_html($item->name); ?></strong>
                    <?php if ($item->description) : ?><br><small style="color:#6b8599"><?php echo esc_html(substr($item->description,0,60)); ?></small><?php endif; ?></td>
                <td><?php echo esc_html($item->category?:'–'); ?></td>
                <td><?php echo esc_html($item->unit); ?></td>
                <td><?php echo vesho_crm_format_money($item->price); ?></td>
                <td><?php echo intval($item->vat_rate); ?>%</td>
                <td><strong><?php echo vesho_crm_format_money($price_with_vat); ?></strong></td>
                <td><?php echo $item->work_type ? esc_html($work_type_labels[$item->work_type]??$item->work_type) : '<span style="color:#6b8599">–</span>'; ?></td>
                <td><?php echo $item->visible_public ? '<span class="crm-badge badge-success">✓</span>' : '<span style="color:#aaa">–</span>'; ?></td>
                <td class="td-actions">
                    <a href="<?php echo admin_url('admin.php?page=vesho-crm-pricelist&action=edit&pricelist_id='.$item->id); ?>" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda">✏️</a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_delete_pricelist&pricelist_id='.$item->id),'vesho_delete_pricelist'); ?>"
                       class="crm-btn crm-btn-icon crm-btn-sm" onclick="return confirm('Kustuta kirje?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
