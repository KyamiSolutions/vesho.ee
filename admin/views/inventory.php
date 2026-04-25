<?php defined( 'ABSPATH' ) || exit;
global $wpdb;

$search      = isset($_GET['s'])        ? sanitize_text_field($_GET['s'])        : '';
$filter_cat  = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$active_tab  = isset($_GET['tab'])      ? sanitize_text_field($_GET['tab'])      : 'all';

$categories  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_inventory_categories ORDER BY sort_order ASC, name ASC");
$cat_map     = [];
foreach ($categories as $c) $cat_map[$c->name] = $c;

$where = 'archived=0';
if ($filter_cat) { $where .= $wpdb->prepare(' AND category=%s', $filter_cat); }
if ($search)     { $s = '%'.$wpdb->esc_like($search).'%'; $where .= $wpdb->prepare(' AND (name LIKE %s OR sku LIKE %s OR ean LIKE %s OR supplier LIKE %s)', $s, $s, $s, $s); }

$items      = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_inventory WHERE $where ORDER BY name ASC LIMIT 500");
$total      = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE archived=0");
$low_stock  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND min_quantity IS NOT NULL AND min_quantity > 0 AND quantity <= min_quantity");
$low_items  = $wpdb->get_results("SELECT name FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND min_quantity IS NOT NULL AND min_quantity > 0 AND quantity <= min_quantity ORDER BY name ASC LIMIT 20");
$shop_cnt   = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND shop_enabled=1");
$pending    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_guest_requests WHERE service_type='Pood' AND status='new'");
$archived   = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_inventory WHERE archived=1 ORDER BY name ASC LIMIT 200");
$writeoffs  = $wpdb->get_results("SELECT w.*, i.name as item_name FROM {$wpdb->prefix}vesho_inventory_writeoffs w LEFT JOIN {$wpdb->prefix}vesho_inventory i ON i.id=w.inventory_id ORDER BY w.created_at DESC LIMIT 300");
$used_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND used_quantity > 0 ORDER BY used_quantity DESC LIMIT 200");
$locations  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vesho_warehouse_locations ORDER BY code ASC LIMIT 1000");
$stock_counts = $wpdb->get_results("SELECT sc.*, COUNT(sci.id) as item_count FROM {$wpdb->prefix}vesho_stock_counts sc LEFT JOIN {$wpdb->prefix}vesho_stock_count_items sci ON sci.stock_count_id=sc.id GROUP BY sc.id ORDER BY sc.created_at DESC LIMIT 50");

$nonce_val = wp_create_nonce('vesho_crm_nonce');
$ajax_url  = admin_url('admin-ajax.php');
$sc_workers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY name ASC");

function inv_fmt($n) { return rtrim(rtrim(number_format((float)$n, 3, '.', ''), '0'), '.') ?: '0'; }
?>
<style>
/* ── Layout ───────────────────────────────────────────────────────────────── */
.inv-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px}
.inv-title{font-size:22px;font-weight:800;color:#0d1f2d;margin:0;line-height:1.2}
.inv-subtitle{font-size:13px;color:#6b8599;margin:4px 0 0}
.inv-header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.inv-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px}
.inv-stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px}
.inv-stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.inv-stat-val{font-size:22px;font-weight:800;color:#0d1f2d;line-height:1}
.inv-stat-lbl{font-size:12px;color:#6b8599;margin-top:3px}
/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.inv-tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding-bottom:2px}
.inv-tabs::-webkit-scrollbar{display:none}
.inv-tab{padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:#64748b;background:#f1f5f9;border:none;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0}
.inv-tab:hover{background:#e2e8f0;color:#0d1f2d}
.inv-tab.active{background:#00b4c8;color:#fff}
.inv-tab .badge{background:rgba(0,0,0,.15);color:inherit;border-radius:20px;font-size:11px;padding:1px 6px;min-width:18px;text-align:center}
.inv-tab.active .badge{background:rgba(255,255,255,.25)}
/* ── Cards & Tables ───────────────────────────────────────────────────────── */
.inv-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:20px}
.inv-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid #f1f5f9}
.inv-table-wrap{overflow-x:auto}
.inv-table{width:100%;border-collapse:collapse;font-size:13px}
.inv-table thead tr{background:#f8fafc}
.inv-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b8599;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;border-bottom:1px solid #e2e8f0}
.inv-table td{padding:11px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.inv-table tbody tr:hover{background:#f8fafc}
.inv-table tbody tr:last-child td{border-bottom:none}
.inv-name-cell strong{color:#0d1f2d;font-size:13px}
.inv-name-cell small{color:#94a3b8;font-size:11px;display:block;margin-top:2px}
.inv-qty{font-weight:700;color:#0d1f2d}
.inv-qty.low{color:#ef4444}
.inv-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;gap:12px}
.inv-empty-icon{font-size:48px;opacity:.3}
.inv-empty-text{font-size:15px;color:#94a3b8;font-weight:500}
/* ── Inline form ──────────────────────────────────────────────────────────── */
#inv-inline-form{display:none;background:#fff;border:1.5px solid rgba(0,180,200,.35);border-radius:16px;padding:0;margin-bottom:20px;box-shadow:0 8px 32px rgba(0,180,200,.08);overflow:hidden}
#inv-inline-form.open{display:block}
.isf-header{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(to right,rgba(0,180,200,.04),transparent)}
.isf-header-left{display:flex;align-items:center;gap:12px}
.isf-header-icon{width:36px;height:36px;background:rgba(0,180,200,.12);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.isf-header h3{margin:0;font-size:16px;font-weight:700;color:#0d1f2d}
.isf-body{padding:24px;display:flex;flex-direction:column;gap:20px}
.isf-section{display:flex;flex-direction:column;gap:14px}
.isf-section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:2px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
.isf-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.isf-full{grid-column:1/-1}
.isf-box{padding:14px;background:rgba(0,180,200,.04);border-radius:10px;border:1px solid rgba(0,180,200,.15)}
.isf-box-gray{padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #f1f5f9}
.inv-fg{margin-bottom:0}
.inv-fg label,.isf-label{display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px}
.inv-fg input,.inv-fg select,.inv-fg textarea,
.isf-input{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;color:#0d1f2d;background:#fff;transition:border-color .15s}
.inv-fg input:focus,.inv-fg select:focus,.inv-fg textarea:focus,.isf-input:focus{outline:none;border-color:#00b4c8;box-shadow:0 0 0 3px rgba(0,180,200,.08)}
.inv-grid,.isf-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.inv-full,.isf-full{grid-column:1/-1}
.isf-actions{display:flex;gap:10px;padding:16px 24px;border-top:1px solid #f1f5f9;background:#fafcfe}
/* ── Write-off modal ──────────────────────────────────────────────────────── */
#wo-modal{display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:center;justify-content:center}
#wo-modal.open{display:flex}
.wo-box{background:#fff;border-radius:16px;padding:28px;width:380px;max-width:calc(100vw - 32px);box-shadow:0 12px 48px rgba(0,0,0,.2)}
.wo-box h3{margin:0 0 18px;font-size:17px;font-weight:700;color:#0d1f2d}
.wo-row{margin-bottom:14px}
.wo-row label{display:block;font-size:12px;font-weight:600;color:#6b8599;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.wo-row input,.wo-row textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;color:#0d1f2d}
.wo-row input:focus,.wo-row textarea:focus{outline:none;border-color:#00b4c8}
.wo-actions{display:flex;gap:8px;margin-top:20px}
/* ── Low stock bar ────────────────────────────────────────────────────────── */
.inv-low-bar{background:#fef9c3;border:1px solid #fde047;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#854d0e}
/* ── CSV preview ──────────────────────────────────────────────────────────── */
#csv-preview-wrap{display:none;margin-bottom:20px}
#csv-preview-wrap.open{display:block}
.csv-preview-box{background:#fff;border:1px solid #00b4c8;border-radius:14px;padding:20px;box-shadow:0 4px 20px rgba(0,180,200,.1)}
/* ── Shop badge ───────────────────────────────────────────────────────────── */
.shop-badge{padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700;cursor:pointer;border:none;transition:.15s}
.shop-badge.on{background:#d1fae5;color:#065f46}
.shop-badge.off{background:#f1f5f9;color:#94a3b8}
/* ── Bulk bar ─────────────────────────────────────────────────────────────── */
#bulk-bar{display:none;background:#0d1f2d;color:#fff;padding:10px 16px;border-radius:10px;margin-bottom:12px;align-items:center;gap:12px;font-size:13px}
#bulk-bar.open{display:flex}
/* ── Stock count view ─────────────────────────────────────────────────────── */
#sc-list-view,#sc-detail-view{transition:.15s}
/* ── Toast ────────────────────────────────────────────────────────────────── */
#inv-toast{display:none;position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:12px 20px;border-radius:10px;font-weight:600;font-size:14px;z-index:99998;box-shadow:0 4px 20px rgba(0,0,0,.2);max-width:320px}
</style>

<div class="crm-wrap">

<!-- ── CSV/XLSX Preview (global, appears above tabs) ────────────────────── -->
<div id="csv-preview-wrap">
  <div class="csv-preview-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <strong style="font-size:15px;color:#0d1f2d">📋 CSV/XLSX eelvaade</strong>
      <span id="csv-preview-count" style="font-size:13px;color:#6b8599"></span>
    </div>
    <div style="overflow-x:auto;max-height:320px;overflow-y:auto">
      <table class="inv-table" id="csv-preview-table">
        <thead><tr>
          <th>Nimi</th><th>SKU</th><th>EAN</th><th>Ühik</th><th>Kogus</th><th>Ostuhind</th><th>Müügihind</th><th>Kategooria</th>
        </tr></thead>
        <tbody id="csv-preview-body"></tbody>
      </table>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button type="button" id="csv-import-btn" class="crm-btn crm-btn-primary">✅ Impordi 0 artiklit</button>
      <button type="button" onclick="cancelCsvPreview()" class="crm-btn crm-btn-outline">Tühista</button>
    </div>
    <div id="csv-import-msg" style="margin-top:10px;font-size:13px"></div>
  </div>
</div>

<!-- ── Header ────────────────────────────────────────────────────────────── -->
<div class="inv-header">
  <div>
    <h1 class="inv-title">📦 Ladu</h1>
    <p class="inv-subtitle">Laohaldus ja toodete varude jälgimine</p>
  </div>
  <div class="inv-header-actions">
    <input type="file" id="csv-file-input" accept=".csv,.xlsx,.xls" style="display:none">
    <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" onclick="document.getElementById('csv-file-input').click()">📂 Impordi CSV/XLSX</button>
    <button type="button" class="crm-btn crm-btn-primary" onclick="openInlineForm()">+ Lisa artikkel</button>
  </div>
</div>

<!-- ── Stats cards ────────────────────────────────────────────────────────── -->
<div class="inv-stats">
  <div class="inv-stat-card">
    <div class="inv-stat-icon" style="background:#e0f7fa">📦</div>
    <div><div class="inv-stat-val"><?php echo $total; ?></div><div class="inv-stat-lbl">Artikleid</div></div>
  </div>
  <div class="inv-stat-card">
    <div class="inv-stat-icon" style="background:#fef3c7">⚠️</div>
    <div>
      <div class="inv-stat-val" style="<?php echo $low_stock > 0 ? 'color:#ef4444' : ''; ?>"><?php echo $low_stock; ?></div>
      <div class="inv-stat-lbl">Vähe laos</div>
    </div>
  </div>
  <div class="inv-stat-card">
    <div class="inv-stat-icon" style="background:#d1fae5">🛒</div>
    <div><div class="inv-stat-val"><?php echo $shop_cnt; ?></div><div class="inv-stat-lbl">E-poes</div></div>
  </div>
  <div class="inv-stat-card">
    <div class="inv-stat-icon" style="background:#f0f4ff">📋</div>
    <div><div class="inv-stat-val"><?php echo $pending; ?></div><div class="inv-stat-lbl">Ootel tellimused</div></div>
  </div>
</div>

<!-- ── Low stock warning bar ──────────────────────────────────────────────── -->
<?php if ($low_stock > 0) : ?>
<div class="inv-low-bar">
  ⚠️ <strong>Vähe laos:</strong>
  <?php echo esc_html(implode(', ', array_column($low_items, 'name'))); ?>
  <?php if ($low_stock > count($low_items)) echo ' ja veel ' . ($low_stock - count($low_items)) . ' teist'; ?>
</div>
<?php endif; ?>

<!-- ── Inline Add/Edit Form ───────────────────────────────────────────────── -->
<div id="inv-inline-form">
  <div class="isf-header">
    <div class="isf-header-left">
      <div class="isf-header-icon">📦</div>
      <h3 id="inv-form-title">Lisa artikkel</h3>
    </div>
    <button type="button" onclick="closeInlineForm()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;line-height:1;padding:4px">✕</button>
  </div>

  <form id="inv-save-form">
    <input type="hidden" id="isf-id" name="inventory_id" value="">
    <div class="isf-body">

      <!-- Põhiandmed -->
      <div class="isf-section">
        <div class="isf-section-title">Põhiandmed</div>
        <div class="isf-grid">
          <div class="isf-full">
            <label class="isf-label">Toote nimi *</label>
            <input type="text" id="isf-name" name="name" required placeholder="Toote nimetus" class="isf-input">
          </div>
          <div>
            <label class="isf-label">SKU / Kood</label>
            <input type="text" id="isf-sku" name="sku" placeholder="FILT-5M-1" class="isf-input">
          </div>
          <div>
            <label class="isf-label">Kategooria</label>
            <select id="isf-category" name="category" class="isf-input">
              <option value="">— Vali —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?php echo esc_attr($cat->name); ?>"><?php echo esc_html($cat->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="isf-label">Ühik</label>
            <select id="isf-unit" name="unit" class="isf-input">
              <?php foreach (['tk','kg','l','m','m²','pakk','paar'] as $u) echo '<option value="'.$u.'">'.$u.'</option>'; ?>
            </select>
          </div>
          <div>
            <label class="isf-label">Kogus laos</label>
            <input type="number" id="isf-qty" name="quantity" step="0.001" value="0" class="isf-input">
          </div>
          <div>
            <label class="isf-label">Min kogus (hoiatus)</label>
            <input type="number" id="isf-minqty" name="min_quantity" step="0.001" placeholder="0" class="isf-input">
          </div>
        </div>
      </div>

      <!-- EAN + asukoht -->
      <div class="isf-section">
        <div class="isf-section-title">Vöötkood &amp; asukoht</div>
        <div class="isf-grid">
          <div>
            <label class="isf-label">EAN vöötkood</label>
            <div style="display:flex;gap:6px">
              <input type="text" id="isf-ean" name="ean" maxlength="20" placeholder="1234567890123" class="isf-input" style="margin:0;flex:1">
              <button type="button" onclick="isf_genEAN()" title="Genereeri" style="flex-shrink:0;padding:0 10px;height:38px;background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.3);border-radius:8px;cursor:pointer;font-size:13px">✦</button>
              <button type="button" onclick="isf_scanEAN()" title="Skänni" style="flex-shrink:0;padding:0 10px;height:38px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;cursor:pointer;font-size:14px">📷</button>
              <button type="button" onclick="isf_printEAN()" title="Prindi silt" style="flex-shrink:0;padding:0 10px;height:38px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;cursor:pointer;font-size:14px">🖨️</button>
            </div>
          </div>
          <div>
            <label class="isf-label">Laoasukoht</label>
            <div style="display:flex;gap:6px">
              <input type="text" id="isf-loc" name="location" placeholder="A-01-03" class="isf-input" style="margin:0;flex:1;font-family:monospace">
              <button type="button" onclick="isf_scanLocation()" title="Skänni asukoht" style="flex-shrink:0;padding:0 10px;height:38px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;cursor:pointer;font-size:14px">📷</button>
              <button type="button" onclick="isf_printLocation()" title="Prindi asukoha silt" style="flex-shrink:0;padding:0 10px;height:38px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;cursor:pointer;font-size:14px">🖨️</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Hinnad -->
      <div class="isf-section">
        <div class="isf-section-title">Hinnad</div>
        <div class="isf-grid">
          <div>
            <label class="isf-label">Ostuhind € (KM-ta)</label>
            <input type="number" id="isf-purchase" name="purchase_price" step="0.01" placeholder="0.00" class="isf-input">
          </div>
          <div>
            <label class="isf-label">Müügihind € (KM-ta)</label>
            <input type="number" id="isf-sell" name="sell_price" step="0.01" placeholder="0.00" class="isf-input">
          </div>
        </div>
      </div>

      <!-- Märkmed -->
      <div>
        <label class="isf-label">Märkmed</label>
        <textarea id="isf-notes" name="notes" rows="2" placeholder="Lisainfo..." class="isf-input" style="resize:vertical"></textarea>
      </div>

      <!-- E-pood -->
      <div class="isf-box">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:#0d1f2d;margin-bottom:0">
          <input type="checkbox" id="isf-shop-enabled" name="shop_enabled" value="1" onchange="toggleShopDesc(this.checked)" style="width:16px;height:16px;accent-color:#00b4c8">
          🛒 Müügis e-poes
        </label>
        <div id="isf-shop-desc-row" style="display:none;margin-top:12px;display:none;flex-direction:column;gap:10px">
          <div>
            <label class="isf-label">E-poe kirjeldus</label>
            <textarea id="isf-shop-desc" name="shop_description" rows="2" placeholder="Lühikirjeldus e-poes..." class="isf-input" style="resize:vertical"></textarea>
          </div>
          <div>
            <label class="isf-label">E-poe hind €</label>
            <input type="number" id="isf-shop-price" name="shop_price" step="0.01" placeholder="0.00" class="isf-input" style="max-width:180px">
          </div>
        </div>
      </div>

      <!-- Toote pilt -->
      <div class="isf-box-gray">
        <div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:10px">🖼️ Toote pilt</div>
        <div id="isf-img-current" style="display:none;margin-bottom:10px;align-items:flex-start;gap:10px">
          <img id="isf-img-thumb" src="" style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:1px solid #e5edf4;display:block">
          <div style="display:flex;flex-direction:column;gap:6px">
            <span style="font-size:11px;color:#94a3b8">Praegune pilt</span>
            <button type="button" onclick="isf_removeImage()" style="font-size:12px;padding:4px 10px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;cursor:pointer">🗑️ Eemalda</button>
          </div>
        </div>
        <div id="isf-img-new-preview" style="display:none;margin-bottom:10px;align-items:flex-start;gap:10px">
          <img id="isf-img-new-thumb" src="" style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid #00b4c8;display:block">
          <div style="display:flex;flex-direction:column;gap:4px">
            <span style="font-size:11px;color:#00b4c8;font-weight:600">Salvestatakse koos tootega</span>
            <span style="font-size:11px;color:#94a3b8" id="isf-img-new-name"></span>
            <button type="button" onclick="isf_cancelNewImage()" style="font-size:12px;padding:4px 10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer">✕ Tühista</button>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <label style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;cursor:pointer;font-weight:500;color:#374151">
            📤 Laadi üles
            <input type="file" id="isf-image-file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
          </label>
          <span style="font-size:11px;color:#94a3b8">Max 5 MB · JPG, PNG, WebP</span>
        </div>
        <input type="hidden" id="isf-image-url" name="image_url">
        <input type="hidden" id="isf-image-delete" name="image_delete" value="0">
      </div>

    </div><!-- /.isf-body -->

    <div class="isf-actions">
      <button type="submit" class="crm-btn crm-btn-primary">💾 Salvesta</button>
      <button type="button" onclick="closeInlineForm()" class="crm-btn crm-btn-outline">Tühista</button>
      <span id="isf-saving" style="display:none;font-size:13px;color:#6b8599;align-self:center">Salvestan...</span>
    </div>
  </form>
</div>

<!-- ── Tabs ────────────────────────────────────────────────────────────────── -->
<div class="inv-tabs">
<?php
$tabs = [
  'all'        => ['🏷️', 'Laoseis',          count($items)],
  'categories' => ['🗂️', 'Kategooriad',      count($categories)],
  'history'    => ['📋', 'Kasutuse ajalugu',  null],
  'count'      => ['🔍', 'Inventuur',         count($stock_counts)],
  'used'       => ['♻️', 'Kasutatud',         count($used_items)],
  'writeoffs'  => ['🗑️', 'Mahakantud',        null],
  'archived'   => ['📁', 'Arhiveeritud',       count($archived)],
  'import'     => ['⬆️', 'Import',            null],
];
foreach ($tabs as $tid => [$icon, $tlabel, $tcount]):
  $is_active = $active_tab === $tid;
  $url = admin_url('admin.php?'.http_build_query(['page'=>'vesho-crm-inventory','tab'=>$tid,'s'=>$search,'category'=>$filter_cat]));
?>
<a href="<?php echo esc_url($url); ?>" class="inv-tab <?php echo $is_active ? 'active' : ''; ?>">
  <?php echo $icon; ?> <?php echo $tlabel; ?>
  <?php if ($tcount !== null): ?><span class="badge"><?php echo $tcount; ?></span><?php endif; ?>
</a>
<?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Laoseis                                                            -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php if ($active_tab === 'all'): ?>

<!-- Bulk action bar -->
<div id="bulk-bar">
  <span id="bulk-count">0 valitud</span>
  <button type="button" class="crm-btn crm-btn-sm" style="background:#ef4444;color:#fff;border:none" onclick="bulkDelete()">🗑️ Kustuta valitud</button>
  <button type="button" class="crm-btn crm-btn-sm crm-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)" onclick="clearBulk()">Tühista valik</button>
</div>

<div class="inv-card">
  <div class="inv-toolbar">
    <input type="checkbox" id="bulk-all-cb" title="Vali kõik" style="width:16px;height:16px;cursor:pointer" onchange="toggleBulkAll(this.checked)">
    <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" style="border-color:#00b4c8;color:#00b4c8" onclick="openInventoryEanScan()">📷 Skänni EAN</button>
    <form method="GET" style="display:flex;gap:8px;flex:1;align-items:center" id="crm-search-form">
      <input type="hidden" name="page" value="vesho-crm-inventory">
      <input type="hidden" name="tab" value="all">
      <?php if (!empty($categories)): ?>
      <select class="crm-form-select" name="category" style="max-width:160px;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
        <option value="">Kõik kategooriad</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?php echo esc_attr($cat->name); ?>" <?php selected($filter_cat, $cat->name); ?>><?php echo esc_html($cat->name); ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <input class="crm-search" type="search" name="s" id="crm-search-input" placeholder="Otsi nime, SKU, EAN..." value="<?php echo esc_attr($search); ?>" style="flex:1;min-width:160px;max-width:320px">
      <button type="submit" class="crm-btn crm-btn-outline crm-btn-sm">Otsi</button>
    </form>
  </div>

  <?php if (empty($items)): ?>
  <div class="inv-empty">
    <div class="inv-empty-icon">📦</div>
    <div class="inv-empty-text"><?php echo $search || $filter_cat ? 'Otsingutulemusi ei leitud' : 'Ladu on tühi'; ?></div>
    <?php if (!$search && !$filter_cat): ?>
    <button type="button" class="crm-btn crm-btn-primary crm-btn-sm" onclick="openInlineForm()">+ Lisa artikkel</button>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table">
    <thead><tr>
      <th style="width:32px"></th>
      <th>Artikkel</th>
      <th>SKU</th>
      <th>Kogus</th>
      <th>Ühik</th>
      <th>Hind</th>
      <th>🛒</th>
      <th>Tegevused</th>
    </tr></thead>
    <tbody>
    <?php foreach ($items as $item):
      $low = $item->min_quantity && (float)$item->quantity <= (float)$item->min_quantity;
    ?>
    <tr id="inv-row-<?php echo $item->id; ?>">
      <td><input type="checkbox" class="bulk-cb" value="<?php echo $item->id; ?>" style="width:15px;height:15px;cursor:pointer" onchange="updateBulkBar()"></td>
      <td class="inv-name-cell">
        <strong><?php echo esc_html($item->name); ?></strong>
        <?php if ($item->category) echo '<small>'.esc_html($item->category).'</small>'; ?>
        <?php if ($item->ean) echo '<small style="font-family:monospace;color:#b0bec5">'.esc_html($item->ean).'</small>'; ?>
      </td>
      <td style="font-family:monospace;font-size:12px;color:#64748b"><?php echo esc_html($item->sku ?: '–'); ?></td>
      <td class="inv-qty-cell" data-id="<?php echo (int)$item->id; ?>" data-qty="<?php echo (float)$item->quantity; ?>" title="Kliki koguse muutmiseks" style="cursor:pointer">
        <span class="inv-qty <?php echo $low ? 'low' : ''; ?>"><?php echo inv_fmt($item->quantity); ?></span>
        <?php if ($low) echo '<span class="crm-badge badge-danger" style="margin-left:4px;font-size:10px">Vähe</span>'; ?>
      </td>
      <td style="color:#64748b"><?php echo esc_html($item->unit); ?></td>
      <td><?php echo $item->sell_price ? '<span style="font-size:13px">'.vesho_crm_format_money($item->sell_price).'</span>' : '<span style="color:#c0c9d4">–</span>'; ?></td>
      <td>
        <button type="button" class="shop-badge <?php echo $item->shop_enabled ? 'on' : 'off'; ?>"
          data-id="<?php echo $item->id; ?>" onclick="toggleShop(this)"
          title="<?php echo $item->shop_enabled ? 'E-poes aktiivne' : 'Lisada e-poodi?'; ?>">
          <?php echo $item->shop_enabled ? '🛒 Jah' : '–'; ?>
        </button>
      </td>
      <td style="white-space:nowrap">
        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda"
          onclick="openEditForm(<?php echo esc_js(json_encode($item)); ?>)">✏️</button>
        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Kanna maha"
          onclick="openWoModal(<?php echo $item->id; ?>,'<?php echo esc_js($item->name); ?>','<?php echo esc_js($item->unit); ?>','writeoff')">🗑️</button>
        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Märgi kasutatud"
          onclick="openWoModal(<?php echo $item->id; ?>,'<?php echo esc_js($item->name); ?>','<?php echo esc_js($item->unit); ?>','used')">♻️</button>
        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Arhiveeri"
          onclick="archiveItem(<?php echo $item->id; ?>,'<?php echo esc_js($item->name); ?>')">📁</button>
        <?php if ($item->ean): ?>
        <button type="button" class="crm-btn crm-btn-icon crm-btn-sm" title="Prindi silt"
          onclick="printItemLabel('<?php echo esc_js($item->name); ?>','<?php echo esc_js($item->sku); ?>','<?php echo esc_js($item->ean); ?>',<?php echo (float)$item->sell_price; ?>)">🖨</button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- Write-off modal -->
<div id="wo-modal">
  <div class="wo-box">
    <h3 id="wo-title">Mahakandmine</h3>
    <div id="wo-form">
      <input type="hidden" id="wo-inv-id" value="">
      <input type="hidden" id="wo-type" value="writeoff">
      <div class="wo-row">
        <label id="wo-qty-label">Kogus</label>
        <input type="number" id="wo-qty" step="0.001" min="0.001" value="1">
      </div>
      <div class="wo-row">
        <label>Põhjus / märkus (valikuline)</label>
        <textarea id="wo-reason" rows="2" placeholder="..."></textarea>
      </div>
      <div class="wo-actions">
        <button type="button" class="crm-btn crm-btn-primary" style="flex:1" id="wo-submit-btn" onclick="submitWo()">Kanna maha</button>
        <button type="button" class="crm-btn crm-btn-outline" onclick="closeWoModal()">Tühista</button>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Kategooriad                                                         -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'categories'): ?>

<style>
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:20px}
.cat-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:.15s}
.cat-card:hover{border-color:#00b4c8;box-shadow:0 2px 12px rgba(0,180,200,.1)}
.cat-swatch{width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px}
.cat-info{flex:1;min-width:0}
.cat-name{font-weight:700;font-size:14px;color:#0d1f2d;margin:0 0 2px}
.cat-count{font-size:12px;color:#94a3b8}
.cat-actions{display:flex;gap:6px;flex-shrink:0}
#cat-form-wrap{background:#fff;border:1.5px solid #00b4c8;border-radius:14px;padding:24px;margin-bottom:20px;box-shadow:0 4px 24px rgba(0,180,200,.1)}
.cat-form-row{display:grid;grid-template-columns:1fr 1fr auto auto;gap:12px;align-items:end}
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
  <div>
    <h2 style="font-size:18px;font-weight:800;color:#0d1f2d;margin:0">🗂️ Kategooriad</h2>
    <p style="font-size:13px;color:#6b8599;margin:4px 0 0">Halda e-poe ja lao tootekategooriaid</p>
  </div>
  <button class="crm-btn crm-btn-primary" onclick="openCatForm()">+ Lisa kategooria</button>
</div>

<!-- Add/Edit form -->
<div id="cat-form-wrap" style="display:none">
  <h3 id="cat-form-title" style="margin:0 0 16px;font-size:15px;font-weight:700;color:#0d1f2d">+ Lisa kategooria</h3>
  <div class="cat-form-row">
    <div class="inv-fg" style="margin:0">
      <label>Nimi *</label>
      <input type="text" id="cat-name" placeholder="nt. Filtrid, Ventilaatorid..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box">
    </div>
    <div class="inv-fg" style="margin:0">
      <label>Värv</label>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="color" id="cat-color" value="#00b4c8" style="width:42px;height:38px;border:1.5px solid #e2e8f0;border-radius:8px;padding:2px;cursor:pointer">
        <div id="cat-color-preview" style="flex:1;height:38px;border-radius:8px;background:#00b4c8;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700">Eelvaade</div>
      </div>
    </div>
    <div class="inv-fg" style="margin:0">
      <label>Järjekord</label>
      <input type="number" id="cat-sort" value="0" min="0" style="width:80px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px">
    </div>
    <div style="display:flex;gap:8px;padding-bottom:0">
      <button class="crm-btn crm-btn-primary" onclick="saveCat()">💾 Salvesta</button>
      <button class="crm-btn crm-btn-outline" onclick="closeCatForm()">Tühista</button>
    </div>
  </div>
  <input type="hidden" id="cat-edit-id" value="">
  <div id="cat-save-msg" style="margin-top:10px;font-size:13px"></div>
</div>

<!-- Category list -->
<?php
$cat_counts = [];
$rows = $wpdb->get_results("SELECT category, COUNT(*) as cnt FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND category!='' GROUP BY category");
foreach ($rows as $r) $cat_counts[$r->category] = (int)$r->cnt;
?>

<?php if (empty($categories)): ?>
<div class="inv-empty" style="margin-top:40px">
  <div class="inv-empty-icon">🗂️</div>
  <div class="inv-empty-text">Kategooriaid pole veel lisatud</div>
  <button class="crm-btn crm-btn-primary crm-btn-sm" onclick="openCatForm()">+ Lisa esimene kategooria</button>
</div>
<?php else: ?>
<div class="cat-grid" id="cat-grid">
<?php foreach ($categories as $cat):
  $cnt = $cat_counts[$cat->name] ?? 0;
?>
<div class="cat-card" id="cat-card-<?php echo $cat->id; ?>">
  <div class="cat-swatch" style="background:<?php echo esc_attr($cat->color); ?>20;color:<?php echo esc_attr($cat->color); ?>">🗂️</div>
  <div class="cat-info">
    <div class="cat-name"><?php echo esc_html($cat->name); ?></div>
    <div class="cat-count"><?php echo $cnt; ?> toodet · järjek. <?php echo (int)$cat->sort_order; ?></div>
  </div>
  <div class="cat-actions">
    <button class="crm-btn crm-btn-icon crm-btn-sm" title="Muuda"
      onclick='editCat(<?php echo json_encode(['id'=>$cat->id,'name'=>$cat->name,'color'=>$cat->color,'sort_order'=>(int)$cat->sort_order]); ?>)'>✏️</button>
    <button class="crm-btn crm-btn-icon crm-btn-sm" title="Kustuta" style="color:#ef4444"
      onclick="deleteCat(<?php echo $cat->id; ?>,'<?php echo esc_js($cat->name); ?>')">🗑️</button>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const catNonce = '<?php echo wp_create_nonce('vesho_crm_nonce'); ?>';
const catAjax  = '<?php echo admin_url('admin-ajax.php'); ?>';

document.getElementById('cat-color')?.addEventListener('input', function() {
  document.getElementById('cat-color-preview').style.background = this.value;
});

function openCatForm(id) {
  if (!id) {
    document.getElementById('cat-form-title').textContent = '+ Lisa kategooria';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-color').value = '#00b4c8';
    document.getElementById('cat-color-preview').style.background = '#00b4c8';
    document.getElementById('cat-sort').value = '0';
    document.getElementById('cat-edit-id').value = '';
  }
  document.getElementById('cat-form-wrap').style.display = 'block';
  document.getElementById('cat-name').focus();
}

function closeCatForm() {
  document.getElementById('cat-form-wrap').style.display = 'none';
  document.getElementById('cat-save-msg').textContent = '';
}

function editCat(cat) {
  document.getElementById('cat-form-title').textContent = '✏️ Muuda kategooriat';
  document.getElementById('cat-name').value = cat.name;
  document.getElementById('cat-color').value = cat.color;
  document.getElementById('cat-color-preview').style.background = cat.color;
  document.getElementById('cat-sort').value = cat.sort_order;
  document.getElementById('cat-edit-id').value = cat.id;
  document.getElementById('cat-form-wrap').style.display = 'block';
  document.getElementById('cat-name').focus();
}

function saveCat() {
  const name  = document.getElementById('cat-name').value.trim();
  const color = document.getElementById('cat-color').value;
  const sort  = document.getElementById('cat-sort').value;
  const id    = document.getElementById('cat-edit-id').value;
  const msg   = document.getElementById('cat-save-msg');

  if (!name) { msg.textContent = '⚠️ Nimi on kohustuslik'; return; }
  msg.textContent = 'Salvestan...';

  const fd = new FormData();
  fd.append('action', 'vesho_save_inv_category');
  fd.append('nonce', catNonce);
  fd.append('cat_id', id);
  fd.append('name', name);
  fd.append('color', color);
  fd.append('sort_order', sort);

  fetch(catAjax, {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
      if (!d.success) { msg.textContent = '❌ ' + (d.data||'Viga'); return; }
      msg.textContent = '✅ Salvestatud';
      setTimeout(() => location.reload(), 600);
    });
}

function deleteCat(id, name) {
  if (!confirm('Kustuta kategooria "' + name + '"?\n\nTooted jäävad alles, neile ei määrata kategooriat.')) return;

  const fd = new FormData();
  fd.append('action', 'vesho_delete_inv_category');
  fd.append('nonce', catNonce);
  fd.append('cat_id', id);

  fetch(catAjax, {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        document.getElementById('cat-card-' + id)?.remove();
      }
    });
}
</script>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Laoaadressid                                                       -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'locations'): ?>
<div class="inv-card" style="margin-bottom:16px">
  <div style="padding:20px">
    <h3 style="margin:0 0 14px;font-size:15px;font-weight:700;color:#0d1f2d">Genereeri laoaadressid</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:16px">
      <div>
        <label style="font-size:11px;font-weight:700;color:#6b8599;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px">Blokk (A–Z)</label>
        <input type="text" id="loc-block" placeholder="A-C" class="crm-form-input" style="padding:9px 12px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#6b8599;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px">Riiuliread (1–N)</label>
        <input type="number" id="loc-rows" value="5" min="1" max="99" class="crm-form-input" style="padding:9px 12px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#6b8599;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px">Positsioonid (1–N)</label>
        <input type="number" id="loc-cols" value="10" min="1" max="99" class="crm-form-input" style="padding:9px 12px">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:#6b8599;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px">Kirjeldus</label>
        <input type="text" id="loc-desc" placeholder="Põhiladu" class="crm-form-input" style="padding:9px 12px">
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <button type="button" class="crm-btn crm-btn-primary" id="loc-gen-btn" onclick="generateLocations()">Genereeri aadressid</button>
      <span id="loc-gen-preview" style="font-size:13px;color:#6b8599"></span>
    </div>
    <div id="loc-gen-msg" style="margin-top:10px;font-size:13px"></div>
  </div>
</div>

<div class="inv-card">
  <?php if (empty($locations)): ?>
  <div class="inv-empty"><div class="inv-empty-icon">🗺</div><div class="inv-empty-text">Laoaadresse pole lisatud</div></div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table" id="loc-table">
    <thead><tr><th>Kood</th><th>Kirjeldus</th><th>EAN</th><th>Prindi</th><th>Kustuta</th></tr></thead>
    <tbody>
    <?php foreach ($locations as $loc):
      $locEan = locToEan12($loc->code);
    ?>
    <tr id="loc-row-<?php echo $loc->id; ?>">
      <td style="font-family:monospace;font-weight:700;color:#0d1f2d"><?php echo esc_html($loc->code); ?></td>
      <td style="color:#6b8599;font-size:13px"><?php echo esc_html($loc->description ?: '–'); ?></td>
      <td style="font-family:monospace;font-size:11px;color:#94a3b8"><?php echo esc_html($locEan); ?></td>
      <td><button type="button" class="crm-btn crm-btn-sm crm-btn-outline" onclick="printLocLabel('<?php echo esc_js($loc->code); ?>','<?php echo esc_js($locEan); ?>')">🖨 Prindi</button></td>
      <td><button type="button" class="crm-btn crm-btn-sm" style="background:#fee2e2;color:#991b1b;border:none" onclick="deleteLocation(<?php echo $loc->id; ?>,this)">✕ Kustuta</button></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php
function locToEan12($code) {
    // Parse code like A-01-03: block=A(1), shelf=01, pos=03
    $parts = explode('-', strtoupper($code));
    if (count($parts) < 3) return '';
    $block = ord($parts[0][0] ?? 'A') - ord('A') + 1; // A=1, B=2...
    $shelf = intval($parts[1]);
    $pos   = intval($parts[2]);
    $base  = sprintf('9%02d%02d%02d00000', $block, $shelf, $pos);
    $base  = substr($base, 0, 12);
    // EAN-13 check digit
    $s = 0;
    for ($i = 0; $i < 12; $i++) {
        $s += intval($base[$i]) * ($i % 2 === 0 ? 1 : 3);
    }
    $check = (10 - ($s % 10)) % 10;
    return $base . $check;
}
?>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Kasutuse ajalugu                                                   -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'history'): ?>
<?php
$hist_type = isset($_GET['hist_type']) ? sanitize_text_field($_GET['hist_type']) : '';
$hist_all  = $wpdb->get_results("SELECT w.*, i.name as item_name FROM {$wpdb->prefix}vesho_inventory_writeoffs w LEFT JOIN {$wpdb->prefix}vesho_inventory i ON i.id=w.inventory_id ORDER BY w.created_at DESC LIMIT 500");
?>
<div class="inv-card">
  <div class="inv-toolbar">
    <?php
    $hist_base = admin_url('admin.php?'.http_build_query(['page'=>'vesho-crm-inventory','tab'=>'history']));
    foreach ([''=>'Kõik','writeoff'=>'Mahakantud','used'=>'Kasutatud'] as $hv => $hl):
      $active_h = $hist_type === $hv;
    ?>
    <a href="<?php echo esc_url($hist_base.($hv ? '&hist_type='.$hv : '')); ?>"
       class="crm-btn crm-btn-sm <?php echo $active_h ? 'crm-btn-primary' : 'crm-btn-outline'; ?>"><?php echo $hl; ?></a>
    <?php endforeach; ?>
  </div>
  <?php
  $filtered = $hist_type ? array_filter($hist_all, fn($w) => $w->type === $hist_type) : $hist_all;
  if (empty($filtered)): ?>
  <div class="inv-empty"><div class="inv-empty-icon">📋</div><div class="inv-empty-text">Kirjeid pole</div></div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table">
    <thead><tr><th>Kuupäev</th><th>Artikkel</th><th>Kogus</th><th>Tüüp</th><th>Põhjus</th><th>Kasutaja</th></tr></thead>
    <tbody>
    <?php foreach ($filtered as $wo): ?>
    <tr>
      <td style="color:#6b8599;font-size:12px;white-space:nowrap"><?php echo esc_html(date('d.m.Y H:i', strtotime($wo->created_at))); ?></td>
      <td class="inv-name-cell"><strong><?php echo esc_html($wo->item_name ?? '—'); ?></strong></td>
      <td style="font-weight:700;color:#ef4444">−<?php echo inv_fmt($wo->qty); ?></td>
      <td><?php echo $wo->type === 'used' ? '<span class="crm-badge badge-warning">Kasutatud</span>' : '<span class="crm-badge badge-danger">Mahakantud</span>'; ?></td>
      <td style="color:#6b8599;font-size:12px"><?php echo esc_html($wo->reason ?: '—'); ?></td>
      <td style="font-size:12px;color:#64748b"><?php echo esc_html($wo->user_name ?: '—'); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Inventuur (named stock counts)                                     -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'count'): ?>
<div id="sc-list-view">
  <!-- Create new count -->
  <div class="inv-card" style="margin-bottom:16px">
    <div style="padding:18px 20px">
      <h3 style="margin:0 0 12px;font-size:15px;font-weight:700;color:#0d1f2d">Uus inventuur</h3>
      <div style="display:flex;gap:10px;align-items:center">
        <input type="text" id="sc-new-name" placeholder="Inventuuri nimi (nt Aprill 2025)" class="crm-form-input" style="max-width:320px;padding:9px 12px">
        <button type="button" class="crm-btn crm-btn-primary" onclick="createStockCount()">Loo inventuur</button>
      </div>
      <div id="sc-create-msg" style="margin-top:8px;font-size:13px"></div>
    </div>
  </div>

  <!-- Existing counts list -->
  <div class="inv-card">
    <?php if (empty($stock_counts)): ?>
    <div class="inv-empty"><div class="inv-empty-icon">🔍</div><div class="inv-empty-text">Inventuure pole veel loodud</div></div>
    <?php else: ?>
    <div class="inv-table-wrap">
    <table class="inv-table">
      <thead><tr><th>Nimi</th><th>Staatus</th><th>Loodud</th><th>Artikleid</th><th>Tegevused</th></tr></thead>
      <tbody>
      <?php foreach ($stock_counts as $sc): ?>
      <tr>
        <td style="font-weight:600;color:#0d1f2d"><?php echo esc_html($sc->name); ?></td>
        <td><?php echo $sc->status === 'finalized'
          ? '<span class="crm-badge badge-success">Lõpetatud</span>'
          : '<span class="crm-badge badge-warning">Mustand</span>'; ?></td>
        <td style="color:#6b8599;font-size:12px"><?php echo esc_html(date('d.m.Y H:i', strtotime($sc->created_at))); ?></td>
        <td style="font-weight:600"><?php echo (int)$sc->item_count; ?></td>
        <td style="white-space:nowrap">
          <button type="button" class="crm-btn crm-btn-sm crm-btn-primary" onclick="openStockCount(<?php echo $sc->id; ?>,'<?php echo esc_js($sc->name); ?>')">Ava</button>
          <?php if ($sc->status === 'finalized'): ?>
          <button type="button" class="crm-btn crm-btn-sm crm-btn-outline" onclick="exportSCCsv(<?php echo $sc->id; ?>,'<?php echo esc_js($sc->name); ?>')">📥 CSV</button>
          <?php endif; ?>
          <button type="button" class="crm-btn crm-btn-sm" style="background:#fee2e2;color:#991b1b;border:none" onclick="deleteStockCount(<?php echo $sc->id; ?>,this)">✕</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Stock count detail view (hidden initially) -->
<div id="sc-detail-view" style="display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div>
      <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" onclick="closeStockCount()">← Tagasi</button>
      <strong id="sc-detail-name" style="font-size:16px;font-weight:700;color:#0d1f2d;margin-left:12px"></strong>
      <span id="sc-detail-status" style="margin-left:8px"></span>
    </div>
    <div style="display:flex;gap:8px">
      <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" style="border-color:#00b4c8;color:#00b4c8" onclick="openScScan()">📷 Skänni EAN</button>
      <button type="button" id="sc-finalize-btn" class="crm-btn crm-btn-primary crm-btn-sm" onclick="finalizeStockCount()">✅ Lõpeta inventuur</button>
    </div>
  </div>
  <!-- Halda osi -->
  <div class="inv-card" style="margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;cursor:pointer" onclick="toggleSectionsPanel()">
      <strong style="font-size:14px;color:#0d1f2d">Halda osi</strong>
      <span id="sc-sections-toggle" style="color:#64748b;font-size:12px">▼ Ava</span>
    </div>
    <div id="sc-sections-panel" style="display:none;padding:16px">
      <!-- Existing sections -->
      <div id="sc-sections-list" style="margin-bottom:16px"></div>
      <!-- Add section form -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;padding-top:12px;border-top:1px solid #f1f5f9">
        <div>
          <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px">Osa nimi</label>
          <input type="text" id="sc-new-section-name" placeholder="nt. Ladu A" class="crm-form-input" style="min-width:180px">
        </div>
        <div>
          <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px">Töötaja</label>
          <select id="sc-new-section-worker" class="crm-form-select" style="min-width:160px">
            <option value="">— Vali töötaja —</option>
            <?php foreach ($sc_workers as $w) : ?>
            <option value="<?php echo $w->id; ?>"><?php echo esc_html($w->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="button" class="crm-btn crm-btn-primary crm-btn-sm" onclick="saveStockSection()">+ Lisa osa</button>
      </div>
    </div>
  </div>

  <div class="inv-card">
    <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9">
      <input type="text" id="sc-filter" placeholder="Filtreeri nime, SKU, EAN..." class="crm-search" style="width:100%;max-width:400px">
    </div>
    <div class="inv-table-wrap">
    <table class="inv-table" id="sc-detail-table">
      <thead><tr>
        <th>Artikkel</th><th>SKU</th><th>EAN</th><th>Asukoht</th><th>Oodatav</th><th>Loendatud</th><th>Δ Delta</th>
      </tr></thead>
      <tbody id="sc-detail-body"></tbody>
    </table>
    </div>
  </div>
</div>
<script>
(function(){
var _scNonce = '<?php echo esc_js(wp_create_nonce('vesho_admin_nonce')); ?>';
var _scAjax  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

window.toggleSectionsPanel = function() {
  var p = document.getElementById('sc-sections-panel');
  var t = document.getElementById('sc-sections-toggle');
  if (p.style.display === 'none') {
    p.style.display = 'block';
    t.textContent = '▲ Sulge';
    loadSections();
  } else {
    p.style.display = 'none';
    t.textContent = '▼ Ava';
  }
};

function loadSections() {
  if (!window.currentScId) return;
  fetch(_scAjax, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=vesho_get_stock_sections&nonce=' + _scNonce + '&count_id=' + window.currentScId
  }).then(r=>r.json()).then(d=>{
    var el = document.getElementById('sc-sections-list');
    if (!d.success || !d.data.length) {
      el.innerHTML = '<p style="color:#94a3b8;font-size:13px;margin:0">Osasid pole lisatud.</p>';
      return;
    }
    var html = '<table class="inv-table"><thead><tr><th>Osa nimi</th><th>Töötaja</th><th>Artikleid</th><th>Staatus</th><th></th></tr></thead><tbody>';
    d.data.forEach(function(s){
      html += '<tr>' +
        '<td><strong>' + esc(s.name) + '</strong></td>' +
        '<td>' + esc(s.worker_name || '—') + '</td>' +
        '<td>' + (s.item_count || 0) + '</td>' +
        '<td>' + (s.status === 'done' ? '<span class="crm-badge badge-success">Valmis</span>' : '<span class="crm-badge badge-warning">Pooleli</span>') + '</td>' +
        '<td><button type="button" class="crm-btn crm-btn-sm" style="background:#fee2e2;color:#991b1b;border:none" onclick="deleteSection(' + s.id + ',this)">✕</button></td>' +
        '</tr>';
    });
    html += '</tbody></table>';
    el.innerHTML = html;
  });
}

window.saveStockSection = function() {
  var name = document.getElementById('sc-new-section-name').value.trim();
  if (!name) { alert('Sisesta osa nimi'); return; }
  var workerId = document.getElementById('sc-new-section-worker').value;
  fetch(_scAjax, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=vesho_save_stock_section&nonce=' + _scNonce + '&count_id=' + window.currentScId + '&name=' + encodeURIComponent(name) + '&worker_id=' + workerId
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      document.getElementById('sc-new-section-name').value = '';
      document.getElementById('sc-new-section-worker').value = '';
      loadSections();
    } else alert('Viga salvestamisel');
  });
};

window.deleteSection = function(id, btn) {
  if (!confirm('Kustuta osa?')) return;
  fetch(_scAjax, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=vesho_delete_stock_section&nonce=' + _scNonce + '&section_id=' + id
  }).then(r=>r.json()).then(d=>{
    if (d.success) loadSections();
  });
};

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Kasutatud                                                          -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'used'): ?>
<div class="inv-card">
  <?php if (empty($used_items)): ?>
  <div class="inv-empty"><div class="inv-empty-icon">♻️</div><div class="inv-empty-text">Kasutamise kirjeid pole</div></div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table">
    <thead><tr><th>Artikkel</th><th>SKU</th><th>Kasutatud kokku</th><th>Laoseis</th><th>Ühik</th></tr></thead>
    <tbody>
    <?php foreach ($used_items as $item): ?>
    <tr>
      <td class="inv-name-cell"><strong><?php echo esc_html($item->name); ?></strong></td>
      <td style="font-family:monospace;font-size:12px;color:#64748b"><?php echo esc_html($item->sku ?: '–'); ?></td>
      <td><span style="color:#b45309;font-weight:700"><?php echo inv_fmt($item->used_quantity); ?></span></td>
      <td class="inv-qty"><?php echo inv_fmt($item->quantity); ?></td>
      <td style="color:#64748b"><?php echo esc_html($item->unit); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Mahakantud                                                         -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'writeoffs'): ?>
<?php $wo_only = array_filter($writeoffs, fn($w) => $w->type === 'writeoff'); ?>
<div class="inv-card">
  <?php if (empty($wo_only)): ?>
  <div class="inv-empty"><div class="inv-empty-icon">🗑️</div><div class="inv-empty-text">Mahakandmisi pole</div></div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table">
    <thead><tr><th>Kuupäev</th><th>Artikkel</th><th>Kogus</th><th>Põhjus</th><th>Kasutaja</th></tr></thead>
    <tbody>
    <?php foreach ($wo_only as $wo): ?>
    <tr>
      <td style="color:#6b8599;font-size:12px;white-space:nowrap"><?php echo esc_html(date('d.m.Y H:i', strtotime($wo->created_at))); ?></td>
      <td class="inv-name-cell"><strong><?php echo esc_html($wo->item_name ?? '—'); ?></strong></td>
      <td style="font-weight:700;color:#ef4444">−<?php echo inv_fmt($wo->qty); ?></td>
      <td style="color:#6b8599;font-size:12px"><?php echo esc_html($wo->reason ?: '—'); ?></td>
      <td style="font-size:12px;color:#64748b"><?php echo esc_html($wo->user_name ?: '—'); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Arhiveeritud                                                       -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'archived'): ?>
<div class="inv-card">
  <?php if (empty($archived)): ?>
  <div class="inv-empty"><div class="inv-empty-icon">📁</div><div class="inv-empty-text">Arhiveeritud artikleid pole</div></div>
  <?php else: ?>
  <div class="inv-table-wrap">
  <table class="inv-table">
    <thead><tr><th>Artikkel</th><th>SKU</th><th>Kategooria</th><th>Tegevused</th></tr></thead>
    <tbody>
    <?php foreach ($archived as $item): ?>
    <tr style="opacity:.7" id="arch-row-<?php echo $item->id; ?>">
      <td class="inv-name-cell"><strong><?php echo esc_html($item->name); ?></strong></td>
      <td style="font-family:monospace;font-size:12px;color:#64748b"><?php echo esc_html($item->sku ?: '–'); ?></td>
      <td style="color:#64748b"><?php echo esc_html($item->category ?: '–'); ?></td>
      <td>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=vesho_restore_inventory&inventory_id='.$item->id), 'vesho_restore_inventory'); ?>"
           class="crm-btn crm-btn-sm crm-btn-outline">↩ Taasta</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Import                                                             -->
<!-- ════════════════════════════════════════════════════════════════════════ -->
<?php elseif ($active_tab === 'import'): ?>
<div class="inv-card">
  <div style="padding:24px">
    <h3 style="margin:0 0 8px;font-size:16px;font-weight:700;color:#0d1f2d">⬆ Impordi tooted CSV/XLSX-st</h3>
    <p style="margin:0 0 16px;color:#6b8599;font-size:13px">Kui sama SKU on juba laos, uuendatakse olemasolev toode.</p>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:16px;font-family:monospace;font-size:12px;color:#374151">
      <strong>CSV veerud:</strong>
      <code>name</code> (kohustuslik), <code>sku</code>, <code>ean</code>, <code>unit</code>, <code>quantity</code>,
      <code>purchase_price</code>, <code>sell_price</code>, <code>shop_price</code>, <code>min_quantity</code>,
      <code>category</code>, <code>location</code>, <code>supplier</code>, <code>description</code>
    </div>
    <div style="background:#fff8e1;border:1px solid #fde68a;border-radius:6px;padding:10px 14px;margin-bottom:20px;font-size:13px">
      ⚠️ <strong>Hinnad peavad olema KM-ta!</strong> Nt ostuhind 75€ KM-ga → sisesta <code>60.48</code>
    </div>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
      <input type="file" id="import-tab-file" accept=".csv,.xlsx,.xls" class="crm-form-input" style="max-width:360px">
      <button type="button" class="crm-btn crm-btn-outline" onclick="document.getElementById('import-tab-file').click();initCsvImport(document.getElementById('import-tab-file'))">Vali fail ja eelvaata</button>
    </div>
    <div style="margin-top:24px">
      <strong style="font-size:13px;color:#374151">Näidis CSV:</strong>
      <pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;font-size:12px;overflow:auto;margin-top:8px;color:#374151">name,sku,ean,unit,quantity,purchase_price,sell_price,min_quantity,category
Veefilter 5-mikron,FILT-5M-1,4012345678901,tk,50,8.50,12.00,10,Filtrid
Pumba tihend 32mm,TIHEND-32,,tk,20,2.10,3.50,5,Tihendid
Peristaltiline pump 12V,PUMP-12V-1,,tk,5,45.00,89.00,2,Pumbad</pre>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── Toast notification ─────────────────────────────────────────────────── -->
<div id="inv-toast"></div>

</div><!-- .crm-wrap -->

<!-- ── JsBarcode CDN ──────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<script>
(function(){
'use strict';
var nonce   = '<?php echo esc_js($nonce_val); ?>';
var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
var cats    = <?php echo json_encode($categories); ?>;

/* ── Toast ──────────────────────────────────────────────────────────────── */
function showToast(msg, isError) {
  var t = document.getElementById('inv-toast');
  t.textContent = msg;
  t.style.background = isError ? '#ef4444' : '#10b981';
  t.style.display = 'block';
  clearTimeout(t._timer);
  t._timer = setTimeout(function(){ t.style.display = 'none'; }, 2600);
}
window.invToast = showToast;

/* ── Helpers ────────────────────────────────────────────────────────────── */
function post(action, data) {
  var fd = new FormData();
  fd.append('action', action);
  fd.append('nonce', nonce);
  Object.keys(data).forEach(function(k){ fd.append(k, data[k] == null ? '' : data[k]); });
  return fetch(ajaxUrl, { method: 'POST', body: fd }).then(function(r){ return r.json(); });
}

/* ── Inline form ────────────────────────────────────────────────────────── */
window.openInlineForm = function() {
  var f = document.getElementById('inv-inline-form');
  document.getElementById('isf-id').value = '';
  document.getElementById('inv-form-title').textContent = 'Lisa artikkel';
  document.getElementById('inv-save-form').reset();
  document.getElementById('isf-shop-desc-row').style.display = 'none';
  document.getElementById('isf-img-current').style.display = 'none';
  document.getElementById('isf-img-new-preview').style.display = 'none';
  document.getElementById('isf-image-file').value = '';
  document.getElementById('isf-image-url').value = '';
  document.getElementById('isf-image-delete').value = '0';
  f.classList.add('open');
  f.scrollIntoView({ behavior: 'smooth', block: 'start' });
  document.getElementById('isf-name').focus();
};

window.closeInlineForm = function() {
  document.getElementById('inv-inline-form').classList.remove('open');
};

window.openEditForm = function(item) {
  var f = document.getElementById('inv-inline-form');
  document.getElementById('inv-form-title').textContent = '✏️ Muuda: ' + item.name;
  document.getElementById('isf-id').value          = item.id;
  document.getElementById('isf-name').value        = item.name || '';
  document.getElementById('isf-sku').value         = item.sku || '';
  document.getElementById('isf-ean').value         = item.ean || '';
  document.getElementById('isf-category').value    = item.category || '';
  document.getElementById('isf-unit').value        = item.unit || 'tk';
  document.getElementById('isf-qty').value         = item.quantity || '0';
  document.getElementById('isf-minqty').value      = item.min_quantity || '';
  document.getElementById('isf-purchase').value    = item.purchase_price || '';
  document.getElementById('isf-sell').value        = item.sell_price || '';
  document.getElementById('isf-loc').value         = item.location || '';
  document.getElementById('isf-notes').value       = item.notes || '';
  var shopOn = item.shop_enabled == 1 || item.shop_enabled === true;
  document.getElementById('isf-shop-enabled').checked = shopOn;
  document.getElementById('isf-shop-desc-row').style.display = shopOn ? 'flex' : 'none';
  if (shopOn) {
    document.getElementById('isf-shop-desc').value  = item.shop_description || '';
    document.getElementById('isf-shop-price').value = item.shop_price || '';
  }
  var imgUrl = item.image_url || '';
  document.getElementById('isf-image-url').value = imgUrl;
  document.getElementById('isf-image-delete').value = '0';
  document.getElementById('isf-image-file').value = '';
  document.getElementById('isf-img-new-preview').style.display = 'none';
  var cur = document.getElementById('isf-img-current');
  if (imgUrl) { document.getElementById('isf-img-thumb').src = imgUrl; cur.style.display = 'flex'; }
  else { cur.style.display = 'none'; }
  f.classList.add('open');
  f.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

window.toggleShopDesc = function(checked) {
  document.getElementById('isf-shop-desc-row').style.display = checked ? 'flex' : 'none';
};

window.isf_scanLocation = function() {
  window.VeshoScanner && window.VeshoScanner.open({
    title: 'Skänni laoaadress', autoConfirm: true, manualInput: true, wide: true,
    onScan: function(code) {
      var s = String(code).replace(/\D/g,'');
      var loc = code.toUpperCase();
      if (s.length === 13 && s[0] === '9') {
        var bn = parseInt(s.slice(1,3),10);
        if (bn >= 1 && bn <= 26) {
          var b = String.fromCharCode(64+bn);
          loc = b+'-'+String(parseInt(s.slice(3,5),10)).padStart(2,'0')+'-'+String(parseInt(s.slice(5,7),10)).padStart(2,'0');
        }
      }
      document.getElementById('isf-loc').value = loc;
    }
  });
};

document.getElementById('isf-image-file').addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) { showToast('Fail on liiga suur (max 5 MB)', true); this.value = ''; return; }
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('isf-img-new-thumb').src = e.target.result;
    document.getElementById('isf-img-new-name').textContent = file.name;
    document.getElementById('isf-img-new-preview').style.display = 'flex';
    document.getElementById('isf-image-delete').value = '0';
  };
  reader.readAsDataURL(file);
});

window.isf_cancelNewImage = function() {
  document.getElementById('isf-image-file').value = '';
  document.getElementById('isf-img-new-preview').style.display = 'none';
};

window.isf_removeImage = function() {
  document.getElementById('isf-image-url').value = '';
  document.getElementById('isf-image-delete').value = '1';
  document.getElementById('isf-img-current').style.display = 'none';
  document.getElementById('isf-img-thumb').src = '';
};

document.getElementById('inv-save-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = this.querySelector('[type=submit]');
  var saving = document.getElementById('isf-saving');
  btn.disabled = true;
  saving.style.display = 'inline';
  var fd = new FormData(this);
  fd.append('action', 'vesho_save_inventory_inline');
  fd.append('nonce', nonce);
  fetch(ajaxUrl, { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      btn.disabled = false;
      saving.style.display = 'none';
      if (d.success) {
        showToast('✓ ' + (d.data.message || 'Salvestatud'));
        closeInlineForm();
        setTimeout(function(){ location.reload(); }, 900);
      } else {
        showToast((d.data && d.data.message) || 'Viga salvestamisel', true);
      }
    })
    .catch(function(){ btn.disabled = false; saving.style.display = 'none'; showToast('Ühenduse viga', true); });
});

/* ── EAN generation & barcode ────────────────────────────────────────────── */
window.isf_genEAN = function() {
  var d = '200';
  for (var i = 0; i < 9; i++) d += Math.floor(Math.random() * 10);
  var s = 0;
  for (var j = 0; j < 12; j++) s += parseInt(d[j]) * (j % 2 === 0 ? 1 : 3);
  d += (10 - (s % 10)) % 10;
  document.getElementById('isf-ean').value = d;
};

window.isf_scanEAN = function() {
  window.VeshoScanner && window.VeshoScanner.open({
    title: 'Skänni EAN kood', autoConfirm: true,
    onScan: function(c){ document.getElementById('isf-ean').value = c; }
  });
};

window.isf_printEAN = function() {
  var name = document.getElementById('isf-name').value;
  var sku  = document.getElementById('isf-sku').value;
  var ean  = document.getElementById('isf-ean').value;
  var price= parseFloat(document.getElementById('isf-sell').value) || 0;
  if (!ean) { showToast('Sisesta EAN esmalt', true); return; }
  printItemLabel(name, sku, ean, price);
};

window.isf_printLocation = function() {
  var loc = document.getElementById('isf-loc').value.toUpperCase().trim();
  if (!loc) { showToast('Sisesta asukoht esmalt', true); return; }
  var ean = locToEan12JS(loc);
  printLocLabel(loc, ean);
};

/* ── Print helpers (barcode popup) ─────────────────────────────────────── */
window.printItemLabel = function(name, sku, ean, price) {
  var w = window.open('', '_blank', 'width=420,height=320');
  if (!w) return;
  var priceStr = price ? parseFloat(price).toFixed(2) + ' €' : '';
  w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Silt</title>'
    + '<style>body{margin:0;padding:16px;font-family:sans-serif;text-align:center}'
    + '.name{font-size:14px;font-weight:700;margin-bottom:2px}'
    + '.sub{font-size:11px;color:#666;margin-bottom:8px}'
    + '.price{font-size:16px;font-weight:800;margin-top:6px}'
    + '@media print{body{margin:0}}'
    + '</style>'
    + '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>'
    + '</head><body>'
    + '<div class="name">' + (name||'') + '</div>'
    + '<div class="sub">' + (sku || '') + '</div>'
    + '<svg id="bc"></svg>'
    + (priceStr ? '<div class="price">' + priceStr + '</div>' : '')
    + '<script>JsBarcode("#bc","' + ean + '",{format:"EAN13",width:2,height:60,displayValue:true,fontSize:12});<\/script>'
    + '<script>window.onload=function(){window.print();};<\/script>'
    + '</body></html>');
  w.document.close();
};

window.printLocLabel = function(code, ean) {
  var w = window.open('', '_blank', 'width=380,height=280');
  if (!w) return;
  w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Asukoht</title>'
    + '<style>body{margin:0;padding:16px;font-family:sans-serif;text-align:center}'
    + '.code{font-size:22px;font-weight:900;letter-spacing:2px;margin-bottom:8px}'
    + '@media print{body{margin:0}}'
    + '</style>'
    + '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>'
    + '</head><body>'
    + '<div class="code">' + code + '</div>'
    + '<svg id="bc"></svg>'
    + '<script>JsBarcode("#bc","' + (ean||code) + '",{format:"CODE128",width:2,height:55,displayValue:true,fontSize:11});<\/script>'
    + '<script>window.onload=function(){window.print();};<\/script>'
    + '</body></html>');
  w.document.close();
};

/* ── locToEan12 (JS version) ─────────────────────────────────────────────── */
function locToEan12JS(code) {
  var parts = code.toUpperCase().split('-');
  if (parts.length < 3) return code;
  var block = parts[0].charCodeAt(0) - 65 + 1;
  var shelf = parseInt(parts[1]) || 0;
  var pos   = parseInt(parts[2]) || 0;
  var base  = '9' + pad2(block) + pad2(shelf) + pad2(pos) + '00000';
  base = base.substring(0, 12);
  var s = 0;
  for (var i = 0; i < 12; i++) s += parseInt(base[i]) * (i % 2 === 0 ? 1 : 3);
  var check = (10 - (s % 10)) % 10;
  return base + check;
}
function pad2(n){ return n < 10 ? '0'+n : ''+n; }
window.locToEan12JS = locToEan12JS;

/* ── Write-off modal ─────────────────────────────────────────────────────── */
window.openWoModal = function(id, name, unit, type) {
  document.getElementById('wo-inv-id').value = id;
  document.getElementById('wo-type').value   = type;
  document.getElementById('wo-qty').value    = '1';
  document.getElementById('wo-reason').value = '';
  document.getElementById('wo-qty-label').textContent = 'Kogus (' + unit + ')';
  document.getElementById('wo-submit-btn').textContent = type === 'used' ? '♻️ Märgi kasutatud' : '🗑️ Kanna maha';
  document.getElementById('wo-title').textContent = (type === 'used' ? 'Kasutuse märkimine' : 'Mahakandmine') + ': ' + name;
  document.getElementById('wo-modal').classList.add('open');
  setTimeout(function(){ document.getElementById('wo-qty').focus(); }, 50);
};

window.closeWoModal = function() {
  document.getElementById('wo-modal').classList.remove('open');
};

var woModal = document.getElementById('wo-modal');
if (woModal) woModal.addEventListener('click', function(e){ if (e.target === this) closeWoModal(); });

window.submitWo = function() {
  var id     = document.getElementById('wo-inv-id').value;
  var type   = document.getElementById('wo-type').value;
  var qty    = document.getElementById('wo-qty').value;
  var reason = document.getElementById('wo-reason').value;
  var btn    = document.getElementById('wo-submit-btn');
  if (!qty || parseFloat(qty) <= 0) { showToast('Sisesta kogus', true); return; }
  btn.disabled = true;
  btn.textContent = '...';
  post('vesho_writeoff_inventory', { inventory_id: id, type: type, qty: qty, reason: reason })
    .then(function(d) {
      btn.disabled = false;
      if (d.success) {
        closeWoModal();
        showToast('✓ ' + (type === 'used' ? 'Kasutus märgitud' : 'Mahakantud'));
        setTimeout(function(){ location.reload(); }, 900);
      } else {
        btn.textContent = type === 'used' ? '♻️ Märgi kasutatud' : '🗑️ Kanna maha';
        showToast((d.data && d.data.message) || 'Viga', true);
      }
    })
    .catch(function(){ btn.disabled = false; showToast('Ühenduse viga', true); });
};

/* ── Archive item ────────────────────────────────────────────────────────── */
window.archiveItem = function(id, name) {
  if (!confirm('Arhiveeri "' + name + '"?')) return;
  post('vesho_archive_inventory', { inventory_id: id })
    .then(function(d) {
      if (d.success) {
        var row = document.getElementById('inv-row-' + id);
        if (row) { row.style.transition = 'opacity .4s'; row.style.opacity = '0'; setTimeout(function(){ row.remove(); }, 400); }
        showToast('✓ Arhiveeritud');
      } else { showToast((d.data && d.data.message) || 'Viga', true); }
    });
};

/* ── Shop toggle ─────────────────────────────────────────────────────────── */
window.toggleShop = function(btn) {
  var id  = btn.dataset.id;
  var cur = btn.classList.contains('on');
  btn.disabled = true;
  post('vesho_toggle_shop', { inventory_id: id, enabled: cur ? 0 : 1 })
    .then(function(d) {
      btn.disabled = false;
      if (d.success) {
        btn.classList.toggle('on',  !cur);
        btn.classList.toggle('off', cur);
        btn.textContent = cur ? '–' : '🛒 Jah';
        showToast(cur ? 'Eemaldatud e-poest' : 'Lisatud e-poodi');
      } else { showToast('Viga', true); }
    });
};

/* ── Bulk select ─────────────────────────────────────────────────────────── */
window.toggleBulkAll = function(checked) {
  document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = checked; });
  updateBulkBar();
};

window.updateBulkBar = function() {
  var sel   = document.querySelectorAll('.bulk-cb:checked');
  var bar   = document.getElementById('bulk-bar');
  var count = document.getElementById('bulk-count');
  if (!bar) return;
  if (sel.length > 0) {
    bar.classList.add('open');
    count.textContent = sel.length + ' valitud';
  } else {
    bar.classList.remove('open');
  }
};

window.clearBulk = function() {
  document.querySelectorAll('.bulk-cb').forEach(function(cb){ cb.checked = false; });
  var cb = document.getElementById('bulk-all-cb');
  if (cb) cb.checked = false;
  updateBulkBar();
};

window.bulkDelete = function() {
  var sel = Array.from(document.querySelectorAll('.bulk-cb:checked')).map(function(cb){ return cb.value; });
  if (!sel.length) return;
  if (!confirm('Kustuta ' + sel.length + ' artiklit jäädavalt?')) return;
  post('vesho_bulk_delete_inventory', { ids: sel.join(',') })
    .then(function(d) {
      if (d.success) {
        sel.forEach(function(id){ var r = document.getElementById('inv-row-' + id); if(r) r.remove(); });
        clearBulk();
        showToast('✓ ' + sel.length + ' artiklit kustutatud');
      } else { showToast('Viga kustutamisel', true); }
    });
};

/* ── EAN scan (main table) ──────────────────────────────────────────────── */
window.openInventoryEanScan = function() {
  window.VeshoScanner && window.VeshoScanner.open({
    title: 'Skänni toote EAN/SKU', autoConfirm: true,
    onScan: function(c) {
      document.getElementById('crm-search-input').value = c;
      document.getElementById('crm-search-form').submit();
    }
  });
};

/* ── CSV/XLSX preview & import ───────────────────────────────────────────── */
var csvRows = [];

function initCsvFileInput() {
  var inp = document.getElementById('csv-file-input');
  if (!inp) return;
  inp.addEventListener('change', function(e){
    var file = e.target.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('action', 'vesho_csv_preview');
    fd.append('nonce', nonce);
    fd.append('csv_file', file);
    fetch(ajaxUrl, { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (d.success && d.data.rows) {
          csvRows = d.data.rows;
          renderCsvPreview(csvRows);
        } else {
          showToast((d.data && d.data.message) || 'Faili lugemine ebaõnnestus', true);
        }
      })
      .catch(function(){ showToast('Viga faili lugemisel', true); });
  });
}

function renderCsvPreview(rows) {
  var body  = document.getElementById('csv-preview-body');
  var wrap  = document.getElementById('csv-preview-wrap');
  var count = document.getElementById('csv-preview-count');
  var btn   = document.getElementById('csv-import-btn');
  body.innerHTML = '';
  rows.forEach(function(r) {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td>'+esc(r.name||'')+'</td>'
      +'<td style="font-family:monospace;font-size:11px">'+esc(r.sku||'')+'</td>'
      +'<td style="font-family:monospace;font-size:11px">'+esc(r.ean||'')+'</td>'
      +'<td>'+esc(r.unit||'')+'</td>'
      +'<td>'+esc(r.quantity||'')+'</td>'
      +'<td>'+esc(r.purchase_price||'')+'</td>'
      +'<td>'+esc(r.sell_price||'')+'</td>'
      +'<td>'+esc(r.category||'')+'</td>';
    body.appendChild(tr);
  });
  count.textContent = rows.length + ' rida';
  btn.textContent   = '✅ Impordi ' + rows.length + ' artiklit';
  wrap.classList.add('open');
  wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

window.cancelCsvPreview = function() {
  document.getElementById('csv-preview-wrap').classList.remove('open');
  document.getElementById('csv-preview-body').innerHTML = '';
  csvRows = [];
  var inp = document.getElementById('csv-file-input');
  if (inp) inp.value = '';
  var inp2 = document.getElementById('import-tab-file');
  if (inp2) inp2.value = '';
};

document.getElementById('csv-import-btn').addEventListener('click', function() {
  if (!csvRows.length) { showToast('Laadi fail üles esmalt', true); return; }
  var btn = this;
  btn.disabled = true;
  btn.textContent = 'Importin...';
  var fd = new FormData();
  fd.append('action', 'vesho_import_inventory_json');
  fd.append('nonce', nonce);
  fd.append('rows_json', JSON.stringify(csvRows));
  fetch(ajaxUrl, { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      btn.disabled = false;
      if (d.success) {
        var msg = document.getElementById('csv-import-msg');
        msg.innerHTML = '<span style="color:#10b981;font-weight:700">✓ Imporditud: '+(d.data.imported||0)+' uut, '+(d.data.updated||0)+' uuendatud</span>';
        cancelCsvPreview();
        showToast('✓ Import lõpetatud');
        setTimeout(function(){ location.reload(); }, 1200);
      } else {
        btn.textContent = '✅ Impordi ' + csvRows.length + ' artiklit';
        showToast((d.data && d.data.message) || 'Importimine ebaõnnestus', true);
      }
    })
    .catch(function(){ btn.disabled = false; showToast('Ühenduse viga', true); });
});

/* ── Import tab file input ──────────────────────────────────────────────── */
var importTabFile = document.getElementById('import-tab-file');
if (importTabFile) {
  importTabFile.addEventListener('change', function(e){
    var file = e.target.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('action', 'vesho_csv_preview');
    fd.append('nonce', nonce);
    fd.append('csv_file', file);
    fetch(ajaxUrl, { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success && d.data.rows) { csvRows = d.data.rows; renderCsvPreview(csvRows); }
        else showToast((d.data && d.data.message)||'Faili lugemine ebaõnnestus', true);
      });
  });
}

/* ── Warehouse locations ─────────────────────────────────────────────────── */
window.generateLocations = function() {
  var blockRange = (document.getElementById('loc-block')||{}).value || 'A';
  var rows = (document.getElementById('loc-rows')||{}).value || 5;
  var cols = (document.getElementById('loc-cols')||{}).value || 10;
  var desc = (document.getElementById('loc-desc')||{}).value || '';
  var btn  = document.getElementById('loc-gen-btn');
  if (!btn) return;
  btn.disabled = true;
  btn.textContent = 'Genereerin...';
  post('vesho_gen_locations', { block_range: blockRange, rows: rows, cols: cols, description: desc })
    .then(function(d) {
      btn.disabled = false;
      btn.textContent = 'Genereeri aadressid';
      var msg = document.getElementById('loc-gen-msg');
      if (d.success) {
        msg.innerHTML = '<span style="color:#10b981;font-weight:700">✓ ' + (d.data.created||0) + ' aadressi loodud</span>';
        showToast('✓ ' + (d.data.created||0) + ' aadressi loodud');
        setTimeout(function(){ location.reload(); }, 1000);
      } else {
        msg.innerHTML = '<span style="color:#ef4444">' + esc((d.data && d.data.message)||'Viga') + '</span>';
        showToast('Viga', true);
      }
    });
};

window.deleteLocation = function(id, btn) {
  if (!confirm('Kustuta aadress?')) return;
  btn.disabled = true;
  post('vesho_delete_location', { location_id: id })
    .then(function(d) {
      if (d.success) {
        var row = document.getElementById('loc-row-' + id);
        if (row) row.remove();
        showToast('✓ Kustutatud');
      } else { btn.disabled = false; showToast('Viga', true); }
    });
};

/* ── Stock counts ────────────────────────────────────────────────────────── */
var currentScId = null;
var currentScStatus = 'draft';

window.createStockCount = function() {
  var name = (document.getElementById('sc-new-name')||{}).value;
  if (!name || !name.trim()) { showToast('Sisesta inventuuri nimi', true); return; }
  post('vesho_create_stock_count', { name: name.trim() })
    .then(function(d) {
      var msg = document.getElementById('sc-create-msg');
      if (d.success) {
        msg.innerHTML = '<span style="color:#10b981;font-weight:700">✓ Inventuur loodud</span>';
        showToast('✓ Inventuur loodud');
        setTimeout(function(){ location.reload(); }, 800);
      } else {
        msg.innerHTML = '<span style="color:#ef4444">' + esc((d.data && d.data.message)||'Viga') + '</span>';
      }
    });
};

window.openStockCount = function(id, name) {
  currentScId = id;
  window.currentScId = id;
  // Reset sections panel
  var sp = document.getElementById('sc-sections-panel');
  var st = document.getElementById('sc-sections-toggle');
  var sl = document.getElementById('sc-sections-list');
  if (sp) { sp.style.display = 'none'; }
  if (st) { st.textContent = '▼ Ava'; }
  if (sl) { sl.innerHTML = ''; }
  document.getElementById('sc-detail-name').textContent = name;
  document.getElementById('sc-detail-body').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8">Laadin...</td></tr>';
  document.getElementById('sc-list-view').style.display  = 'none';
  document.getElementById('sc-detail-view').style.display = '';
  post('vesho_get_stock_count', { stock_count_id: id })
    .then(function(d) {
      if (d.success) {
        currentScStatus = d.data.status;
        var statusEl = document.getElementById('sc-detail-status');
        statusEl.innerHTML = d.data.status === 'finalized'
          ? '<span class="crm-badge badge-success">Lõpetatud</span>'
          : '<span class="crm-badge badge-warning">Mustand</span>';
        var finBtn = document.getElementById('sc-finalize-btn');
        if (finBtn) finBtn.style.display = d.data.status === 'finalized' ? 'none' : '';
        renderScItems(d.data.items || []);
      } else { showToast('Viga laadimsel', true); }
    });
};

function renderScItems(items) {
  var body = document.getElementById('sc-detail-body');
  body.innerHTML = '';
  if (!items.length) {
    body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8">Artikleid pole</td></tr>';
    return;
  }
  items.forEach(function(it) {
    var tr = document.createElement('tr');
    tr.dataset.id  = it.inventory_id || it.id;
    tr.dataset.search = ((it.name||'') + ' ' + (it.sku||'') + ' ' + (it.ean||'') + ' ' + (it.location||'')).toLowerCase();
    var exp   = parseFloat(it.expected_qty || it.quantity || 0);
    var cnt   = parseFloat(it.counted_qty != null ? it.counted_qty : exp);
    var delta = cnt - exp;
    var dStyle = Math.abs(delta) < 0.001 ? 'color:#94a3b8' : (delta < 0 ? 'color:#ef4444;font-weight:700' : 'color:#10b981;font-weight:700');
    var dText  = Math.abs(delta) < 0.001 ? '=' : ((delta > 0 ? '+' : '') + delta.toFixed(3).replace(/\.?0+$/,''));
    tr.innerHTML = '<td class="inv-name-cell"><strong>'+esc(it.name||'')+'</strong>'
      +(it.category?'<small>'+esc(it.category)+'</small>':'')+'</td>'
      +'<td style="font-family:monospace;font-size:12px;color:#64748b">'+esc(it.sku||'–')+'</td>'
      +'<td style="font-family:monospace;font-size:11px;color:#94a3b8">'+esc(it.ean||'–')+'</td>'
      +'<td style="font-size:12px;color:#94a3b8">'+esc(it.location||'–')+'</td>'
      +'<td class="inv-qty">'+exp.toFixed(3).replace(/\.?0+$/,'')+' '+esc(it.unit||'')+'</td>'
      +'<td><div style="display:flex;gap:6px;align-items:center">'
      +'<input type="number" class="crm-form-input sc-count-inp" step="0.001" min="0" value="'+cnt+'"'
      +' data-orig="'+cnt+'" style="width:90px;padding:6px 8px;font-size:13px" data-inv-id="'+(it.inventory_id||it.id)+'"'
      +(currentScStatus==='finalized'?' disabled':'')+' oninput="updateScDelta(this)">'
      +'<span style="font-size:12px;color:#94a3b8">'+esc(it.unit||'')+'</span>'
      +(currentScStatus!=='finalized'?'<button type="button" class="crm-btn crm-btn-sm sc-save-btn" style="opacity:.4" disabled onclick="saveScItem(this)">Salvesta</button>':'')
      +'</div></td>'
      +'<td class="sc-delta" style="'+dStyle+'">'+dText+'</td>'
    tr.querySelector('.sc-count-inp') && tr.querySelector('.sc-count-inp').addEventListener('input', function(){ updateScDelta(this); });
    body.appendChild(tr);
  });
}

window.updateScDelta = function(inp) {
  var row = inp.closest('tr');
  var exp = parseFloat(row.querySelector('.inv-qty').textContent);
  var cnt = parseFloat(inp.value);
  var dEl = row.querySelector('.sc-delta');
  var btn = row.querySelector('.sc-save-btn');
  if (isNaN(cnt)) { dEl.textContent = '—'; if(btn){btn.disabled=true;btn.style.opacity='.4';} return; }
  var delta = cnt - exp;
  var changed = Math.abs(cnt - parseFloat(inp.dataset.orig)) >= 0.001;
  if (Math.abs(delta) < 0.001) { dEl.textContent = '='; dEl.style.cssText = 'color:#94a3b8'; }
  else { dEl.textContent = (delta>0?'+':'')+delta.toFixed(3).replace(/\.?0+$/,''); dEl.style.cssText = delta < 0 ? 'color:#ef4444;font-weight:700' : 'color:#10b981;font-weight:700'; }
  if (btn) { btn.disabled = !changed; btn.style.opacity = changed ? '1' : '.4'; }
};

window.saveScItem = function(btn) {
  var row    = btn.closest('tr');
  var inp    = row.querySelector('.sc-count-inp');
  var invId  = inp.dataset.invId;
  var qty    = parseFloat(inp.value);
  btn.disabled = true; btn.textContent = '...';
  post('vesho_save_count_item', { stock_count_id: currentScId, inventory_id: invId, counted_qty: qty })
    .then(function(d) {
      if (d.success) {
        inp.dataset.orig = qty;
        btn.textContent = '✓'; btn.style.background = '#10b981'; btn.style.color = '#fff';
        setTimeout(function(){ btn.textContent='Salvesta'; btn.style.background=''; btn.style.color=''; btn.disabled=true; btn.style.opacity='.4'; }, 1800);
        showToast('✓ Salvestatud');
      } else { btn.disabled=false; btn.textContent='Salvesta'; btn.style.opacity='1'; showToast('Viga', true); }
    });
};

window.closeStockCount = function() {
  document.getElementById('sc-list-view').style.display  = '';
  document.getElementById('sc-detail-view').style.display = 'none';
  currentScId = null;
};

window.finalizeStockCount = function() {
  if (!currentScId) return;
  if (!confirm('Lõpeta inventuur ja rakenda muutused laoseisusse?')) return;
  var btn = document.getElementById('sc-finalize-btn');
  btn.disabled = true; btn.textContent = '...';
  post('vesho_finalize_stock_count', { stock_count_id: currentScId })
    .then(function(d) {
      btn.disabled = false;
      if (d.success) {
        showToast('✓ Inventuur lõpetatud, laoseis uuendatud');
        closeStockCount();
        setTimeout(function(){ location.reload(); }, 900);
      } else { btn.textContent='✅ Lõpeta inventuur'; showToast((d.data&&d.data.message)||'Viga', true); }
    });
};

window.deleteStockCount = function(id, btn) {
  if (!confirm('Kustuta inventuur?')) return;
  btn.disabled = true;
  post('vesho_delete_stock_count', { stock_count_id: id })
    .then(function(d) {
      if (d.success) { showToast('✓ Kustutatud'); setTimeout(function(){ location.reload(); }, 700); }
      else { btn.disabled=false; showToast('Viga', true); }
    });
};

window.openScScan = function() {
  window.VeshoScanner && window.VeshoScanner.open({
    title: 'Skänni toote EAN', autoConfirm: true,
    onScan: function(code) {
      var q = code.toLowerCase(), found = null;
      var filter = document.getElementById('sc-filter');
      document.querySelectorAll('#sc-detail-table tbody tr').forEach(function(r){
        r.style.display = '';
        if (!found && r.dataset.search && r.dataset.search.indexOf(q) >= 0) found = r;
      });
      if (filter) filter.value = code;
      document.querySelectorAll('#sc-detail-table tbody tr').forEach(function(r){
        r.style.display = (r.dataset.search && r.dataset.search.indexOf(q) >= 0) ? '' : 'none';
      });
      if (found) {
        found.scrollIntoView({ block: 'center', behavior: 'smooth' });
        var inp = found.querySelector('.sc-count-inp');
        if (inp) { inp.focus(); inp.select(); }
        found.style.background = '#ecfdf5';
        setTimeout(function(){ found.style.background = ''; }, 1500);
      } else { showToast('⚠️ Toodet ei leitud: ' + code, true); }
    }
  });
};

var scFilter = document.getElementById('sc-filter');
if (scFilter) {
  scFilter.addEventListener('input', function(){
    var q = this.value.toLowerCase();
    document.querySelectorAll('#sc-detail-table tbody tr').forEach(function(r){
      r.style.display = (r.dataset.search && r.dataset.search.indexOf(q) >= 0) ? '' : 'none';
    });
  });
}

window.exportSCCsv = function(id, name) {
  post('vesho_get_stock_count', { stock_count_id: id })
    .then(function(d) {
      if (!d.success || !d.data.items) { showToast('Viga', true); return; }
      var rows = [['Artikkel','SKU','EAN','Asukoht','Ühik','Oodatav','Loendatud','Delta']];
      d.data.items.forEach(function(it){
        var exp = parseFloat(it.expected_qty || it.quantity || 0);
        var cnt = parseFloat(it.counted_qty != null ? it.counted_qty : exp);
        rows.push([it.name||'', it.sku||'', it.ean||'', it.location||'', it.unit||'', exp, cnt, (cnt-exp).toFixed(3)]);
      });
      var csv = rows.map(function(r){ return r.map(function(c){ return '"'+String(c).replace(/"/g,'""')+'"'; }).join(','); }).join('\r\n');
      var blob = new Blob(['\uFEFF'+csv], { type: 'text/csv;charset=utf-8;' });
      var url  = URL.createObjectURL(blob);
      var a    = document.createElement('a');
      a.href   = url;
      a.download = 'inventuur-' + name.replace(/[^a-z0-9]/gi, '-') + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
};

/* ── Location preview counter ────────────────────────────────────────────── */
function updateLocPreview() {
  var blockEl = document.getElementById('loc-block');
  var rowsEl  = document.getElementById('loc-rows');
  var colsEl  = document.getElementById('loc-cols');
  var prevEl  = document.getElementById('loc-gen-preview');
  if (!blockEl || !prevEl) return;
  var blocks = 0;
  var rangeStr = blockEl.value.toUpperCase().replace(/\s/g,'');
  if (rangeStr.includes('-') && rangeStr.length >= 3) {
    blocks = rangeStr.charCodeAt(2) - rangeStr.charCodeAt(0) + 1;
  } else if (rangeStr.length === 1) {
    blocks = 1;
  }
  if (blocks < 1) blocks = 1;
  var total = blocks * parseInt(rowsEl.value||1) * parseInt(colsEl.value||1);
  prevEl.textContent = total + ' aadressi';
}
['loc-block','loc-rows','loc-cols'].forEach(function(id){
  var el = document.getElementById(id);
  if (el) el.addEventListener('input', updateLocPreview);
});
updateLocPreview();

/* ── HTML escape helper ──────────────────────────────────────────────────── */
function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Init ────────────────────────────────────────────────────────────────── */
initCsvFileInput();

/* ── Inline quantity click-to-edit ──────────────────────────────────────── */
(function(){
  var nonce = '<?php echo esc_js($nonce_val); ?>';
  var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';

  document.querySelectorAll('.inv-qty-cell').forEach(function(cell){
    cell.addEventListener('click', function(){
      if (cell.querySelector('input')) return; // already editing
      var id  = cell.dataset.id;
      var qty = cell.dataset.qty;
      var origHtml = cell.innerHTML;
      cell.innerHTML = '';
      var inp = document.createElement('input');
      inp.type = 'number'; inp.step = '0.001'; inp.value = parseFloat(qty);
      inp.style.cssText = 'width:80px;padding:4px 6px;border:2px solid #00b4c8;border-radius:6px;font-size:13px;font-weight:700;outline:none';
      cell.appendChild(inp);
      inp.focus(); inp.select();

      function save() {
        var newQty = parseFloat(inp.value);
        if (isNaN(newQty)) { cell.innerHTML = origHtml; return; }
        var fd = new FormData();
        fd.append('action', 'vesho_update_inventory_qty');
        fd.append('nonce', nonce);
        fd.append('inventory_id', id);
        fd.append('quantity', newQty);
        fetch(ajaxUrl, {method:'POST', body:fd})
          .then(function(r){ return r.json(); })
          .then(function(d){
            if (d.success) {
              cell.dataset.qty = newQty;
              var fmt = newQty.toFixed(3).replace(/\.?0+$/,'') || '0';
              cell.innerHTML = '<span class="inv-qty">' + fmt + '</span>';
              // flash green
              cell.style.background = '#d1fae5';
              setTimeout(function(){ cell.style.background = ''; }, 800);
            } else {
              cell.innerHTML = origHtml;
            }
          }).catch(function(){ cell.innerHTML = origHtml; });
      }

      inp.addEventListener('keydown', function(e){
        if (e.key === 'Enter') { e.preventDefault(); save(); }
        if (e.key === 'Escape') { cell.innerHTML = origHtml; }
      });
      inp.addEventListener('blur', function(){ save(); });
    });
  });
})();

})();
</script>
