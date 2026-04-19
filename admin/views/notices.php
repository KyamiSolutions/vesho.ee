<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$notices = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}vesho_portal_notices ORDER BY created_at DESC LIMIT 100" );

$type_labels   = ['info'=>'ℹ️ Info','warning'=>'⚠️ Hoiatus','success'=>'✅ Positiivne'];
$target_labels = ['both'=>'Mõlemad','client'=>'Kliendid','worker'=>'Töötajad'];
?>
<div class="crm-wrap">
<div class="crm-page-header">
    <div class="crm-page-header__logo">📢</div>
    <div class="crm-page-header__body">
        <h1 class="crm-page-header__title">Portaali teated</h1>
        <p class="crm-page-header__subtitle">Halda kliendi- ja töötajaportaali teateid</p>
    </div>
</div>

<?php if ( isset($_GET['msg']) ) : ?>
<div class="crm-alert crm-alert-success"><?php echo ['saved'=>'Salvestatud!','deleted'=>'Kustutatud!'][$_GET['msg']] ?? 'OK'; ?></div>
<?php endif; ?>

<div class="crm-card" style="margin-bottom:24px">
    <div class="crm-card-header">
        <span class="crm-card-title">Lisa uus teade</span>
    </div>
    <div style="padding:20px">
    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('vesho_save_notice'); ?>
        <input type="hidden" name="action" value="vesho_save_notice">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
            <div>
                <label class="crm-form-label">Pealkiri *</label>
                <input class="crm-form-input" type="text" name="notice_title" required placeholder="Teate pealkiri">
            </div>
            <div>
                <label class="crm-form-label">Tüüp</label>
                <select class="crm-form-select" name="notice_type">
                    <option value="info">ℹ️ Info</option>
                    <option value="warning">⚠️ Hoiatus</option>
                    <option value="success">✅ Positiivne</option>
                </select>
            </div>
            <div>
                <label class="crm-form-label">Sihtkoht</label>
                <select class="crm-form-select" name="notice_target">
                    <option value="both">Mõlemad portaalid</option>
                    <option value="client">Ainult kliendid</option>
                    <option value="worker">Ainult töötajad</option>
                </select>
            </div>
            <div>
                <label class="crm-form-label">Alguskuupäev</label>
                <input class="crm-form-input" type="date" name="notice_starts">
            </div>
            <div>
                <label class="crm-form-label">Lõppkuupäev</label>
                <input class="crm-form-input" type="date" name="notice_ends">
            </div>
            <div style="grid-column:1/-1">
                <label class="crm-form-label">Sõnum</label>
                <textarea class="crm-form-textarea" name="notice_message" rows="3" placeholder="Teate sisu..."></textarea>
            </div>
        </div>
        <button type="submit" class="crm-btn crm-btn-primary">💾 Lisa teade</button>
    </form>
    </div>
</div>

<div class="crm-card">
    <div class="crm-card-header">
        <span class="crm-card-title">Aktiivsed teated <span class="crm-count">(<?php echo count($notices); ?>)</span></span>
    </div>
    <?php if ( empty($notices) ) : ?>
    <div class="crm-empty">Teateid pole lisatud.</div>
    <?php else : ?>
    <table class="crm-table">
        <thead><tr>
            <th>Pealkiri</th>
            <th>Tüüp</th>
            <th>Sihtkoht</th>
            <th>Algab</th>
            <th>Lõpeb</th>
            <th>Aktiivne</th>
            <th class="td-actions">Toimingud</th>
        </tr></thead>
        <tbody>
        <?php foreach ( $notices as $n ) : ?>
        <tr <?php if ( ! $n->active ) echo 'style="opacity:.5"'; ?>>
            <td>
                <strong><?php echo esc_html($n->title); ?></strong>
                <?php if ($n->message) : ?>
                <br><small style="color:#6b8599"><?php echo esc_html(mb_substr($n->message, 0, 70)); ?><?php if (mb_strlen($n->message) > 70) echo '…'; ?></small>
                <?php endif; ?>
            </td>
            <td><?php echo $type_labels[$n->type ?? 'info'] ?? esc_html($n->type); ?></td>
            <td><?php echo $target_labels[$n->target ?? 'both'] ?? esc_html($n->target); ?></td>
            <td><?php echo $n->starts_at ? vesho_crm_format_date($n->starts_at) : '–'; ?></td>
            <td><?php echo $n->ends_at   ? vesho_crm_format_date($n->ends_at)   : '–'; ?></td>
            <td>
                <?php if ($n->active) : ?>
                <span class="crm-badge badge-success">Jah</span>
                <?php else : ?>
                <span class="crm-badge badge-gray">Ei</span>
                <?php endif; ?>
            </td>
            <td class="td-actions">
                <a href="<?php echo wp_nonce_url( admin_url('admin-post.php?action=vesho_delete_notice&notice_id='.$n->id), 'vesho_delete_notice' ); ?>"
                   class="crm-btn crm-btn-outline crm-btn-sm" style="color:#ef4444;border-color:#ef4444"
                   onclick="return confirm('Kustuta teade?')">🗑 Kustuta</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</div>
