<?php defined('ABSPATH') || exit; ?>
<div class="crm-page-header">
  <div class="crm-page-header__logo">
    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
  </div>
  <div class="crm-page-header__body">
    <h1 class="crm-page-header__title">Inventuur</h1>
    <p class="crm-page-header__subtitle">Laosaldo kontrollimine ja kinnitamine</p>
  </div>
  <div class="crm-page-header__actions">
    <button class="crm-btn crm-btn--primary crm-btn--sm" id="sc-new-btn">+ Uus inventuur</button>
  </div>
</div>

<div style="max-width:1100px;margin:0 auto;padding:0 24px 40px">

<!-- Uus inventuur vorm -->
<div id="sc-new-form" class="crm-card" style="display:none;margin-bottom:20px">
  <div class="crm-card-header"><span>Loo uus inventuur</span></div>
  <div class="crm-card-body">
    <div class="crm-form-grid crm-form-grid--2">
      <div class="crm-form-group crm-form-group--full">
        <label class="crm-form-label">Nimi <span class="required">*</span></label>
        <input type="text" id="sc-name" class="crm-form-input" placeholder="nt. Aprill 2026 inventuur">
      </div>
    </div>
    <div class="crm-form-actions">
      <button class="crm-btn crm-btn--ghost crm-btn--sm" id="sc-cancel-btn">Tühista</button>
      <button class="crm-btn crm-btn--primary crm-btn--sm" id="sc-create-btn">Loo inventuur</button>
    </div>
  </div>
</div>

<!-- Inventuuride nimekiri -->
<div id="sc-list-wrap" class="crm-card" style="margin-bottom:20px">
  <div class="crm-card-header"><span>Inventuurid</span></div>
  <div class="crm-table-wrap">
    <table class="crm-table" id="sc-table">
      <thead><tr>
        <th>Nimi</th><th>Loodud</th><th>Loodud poolt</th><th>Tooteid</th><th>Loendatud</th><th>Staatus</th><th></th>
      </tr></thead>
      <tbody id="sc-tbody"><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--crm-muted)">Laadimine...</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Inventuuri detailvaade -->
<div id="sc-detail-wrap" style="display:none">
  <div class="crm-card" style="margin-bottom:16px">
    <div class="crm-card-header" style="display:flex;justify-content:space-between;align-items:center">
      <span id="sc-detail-title">Inventuur</span>
      <div style="display:flex;gap:8px">
        <button class="crm-btn crm-btn--ghost crm-btn--sm" id="sc-back-btn">← Tagasi</button>
        <button class="crm-btn crm-btn--danger crm-btn--sm" id="sc-finalize-btn">✓ Kinnita inventuur</button>
      </div>
    </div>
    <div class="crm-card-body" style="padding:12px 20px">
      <div style="display:flex;gap:24px;font-size:13px;color:var(--crm-text-light)">
        <span>Tooteid: <strong id="sc-d-total">0</strong></span>
        <span>Loendatud: <strong id="sc-d-counted">0</strong></span>
        <span>Erinevusi: <strong id="sc-d-diffs" style="color:var(--crm-danger)">0</strong></span>
      </div>
    </div>
  </div>

  <!-- Filter + otsing -->
  <div class="crm-toolbar" style="margin-bottom:12px">
    <div class="crm-toolbar__left">
      <input type="text" id="sc-filter" class="crm-search" placeholder="Otsi toodet...">
      <select id="sc-filter-status" class="crm-form-select" style="width:160px">
        <option value="">Kõik read</option>
        <option value="counted">Loendatud</option>
        <option value="missing">Loendamata</option>
        <option value="diff">Erinevused</option>
      </select>
    </div>
  </div>

  <div class="crm-card">
    <div class="crm-table-wrap">
      <table class="crm-table" id="sc-items-table">
        <thead><tr>
          <th>Toode</th><th>SKU</th><th>Kategooria</th><th>Asukoht</th><th>Oodatav kogus</th><th>Loendatud kogus</th><th>Erinevus</th>
        </tr></thead>
        <tbody id="sc-items-tbody"></tbody>
      </table>
    </div>
  </div>
</div>

</div><!-- /wrap -->

<script>
(function(){
var nonce = '<?php echo wp_create_nonce("vesho_inv_nonce"); ?>';
var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
var currentCountId = null;
var allItems = [];

function ajax(action, data, cb) {
  data.action = action;
  data.nonce = nonce;
  jQuery.post(ajaxUrl, data, function(r){ cb(r); }, 'json');
}

// Load list
function loadList() {
  ajax('vesho_get_stock_counts', {}, function(r){
    if(!r.success) return;
    var counts = r.data;
    var tbody = document.getElementById('sc-tbody');
    if(!counts.length){
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--crm-muted)">Inventuure ei ole. Loo uus.</td></tr>';
      return;
    }
    tbody.innerHTML = counts.map(function(c){
      var pct = c.item_count > 0 ? Math.round((c.counted_count/c.item_count)*100) : 0;
      var statusBadge = c.status === 'finalized'
        ? '<span class="crm-badge crm-badge--success">Kinnitatud</span>'
        : '<span class="crm-badge crm-badge--warning">Avatud</span>';
      var actions = c.status !== 'finalized'
        ? '<button class="crm-btn crm-btn--ghost crm-btn--xs sc-open-btn" data-id="'+c.id+'">Ava</button>'
          + '<button class="crm-btn crm-btn--danger crm-btn--xs sc-del-btn" data-id="'+c.id+'" style="margin-left:4px">Kustuta</button>'
        : '<button class="crm-btn crm-btn--ghost crm-btn--xs sc-open-btn" data-id="'+c.id+'">Vaata</button>';
      return '<tr>'
        + '<td><strong>'+esc(c.name)+'</strong></td>'
        + '<td>'+c.created_at.slice(0,10)+'</td>'
        + '<td>'+esc(c.created_by||'')+'</td>'
        + '<td>'+c.item_count+'</td>'
        + '<td>'+c.counted_count+' / '+c.item_count+' ('+pct+'%)</td>'
        + '<td>'+statusBadge+'</td>'
        + '<td>'+actions+'</td>'
        + '</tr>';
    }).join('');

    document.querySelectorAll('.sc-open-btn').forEach(function(b){
      b.addEventListener('click', function(){ openCount(parseInt(this.dataset.id)); });
    });
    document.querySelectorAll('.sc-del-btn').forEach(function(b){
      b.addEventListener('click', function(){
        if(!confirm('Kustuta inventuur?')) return;
        ajax('vesho_delete_stock_count', {stock_count_id: this.dataset.id}, function(){ loadList(); });
      });
    });
  });
}

// Open count detail
function openCount(id) {
  currentCountId = id;
  ajax('vesho_get_stock_count', {stock_count_id: id}, function(r){
    if(!r.success) return alert('Viga: ' + r.data.message);
    var c = r.data;
    allItems = c.items;
    document.getElementById('sc-detail-title').textContent = c.name;
    document.getElementById('sc-finalize-btn').style.display = c.status === 'finalized' ? 'none' : '';
    document.getElementById('sc-list-wrap').style.display = 'none';
    document.getElementById('sc-detail-wrap').style.display = '';
    renderItems(allItems);
    updateStats(allItems);
  });
}

function renderItems(items) {
  var filter = (document.getElementById('sc-filter').value||'').toLowerCase();
  var statusF = document.getElementById('sc-filter-status').value;
  var filtered = items.filter(function(i){
    if(filter && !(i.name||'').toLowerCase().includes(filter) && !(i.sku||'').toLowerCase().includes(filter)) return false;
    if(statusF === 'counted' && i.counted_qty === null) return false;
    if(statusF === 'missing' && i.counted_qty !== null) return false;
    if(statusF === 'diff' && (i.counted_qty === null || parseFloat(i.counted_qty) === parseFloat(i.expected_qty))) return false;
    return true;
  });

  var finalized = document.getElementById('sc-finalize-btn').style.display === 'none';
  document.getElementById('sc-items-tbody').innerHTML = filtered.map(function(item){
    var diff = item.counted_qty !== null ? (parseFloat(item.counted_qty) - parseFloat(item.expected_qty)) : null;
    var diffCell = diff === null ? '<span style="color:var(--crm-muted)">—</span>'
      : diff === 0 ? '<span style="color:var(--crm-success)">0</span>'
      : '<span style="color:'+(diff<0?'var(--crm-danger)':'var(--crm-success))+'">'+(diff>0?'+':'')+diff+'</span>';
    var input = finalized
      ? (item.counted_qty !== null ? item.counted_qty : '—')
      : '<input type="number" step="0.01" class="crm-form-input sc-qty-input" style="width:90px;padding:5px 8px" '
        + 'data-invid="'+item.inventory_id+'" data-scid="'+currentCountId+'" '
        + 'value="'+(item.counted_qty !== null ? item.counted_qty : '')+'" placeholder="—">';
    return '<tr>'
      + '<td><strong>'+esc(item.name||'')+'</strong></td>'
      + '<td style="color:var(--crm-muted)">'+esc(item.sku||'')+'</td>'
      + '<td>'+esc(item.category||'')+'</td>'
      + '<td>'+esc(item.location||'')+'</td>'
      + '<td>'+parseFloat(item.expected_qty||0)+' '+esc(item.unit||'')+'</td>'
      + '<td>'+input+'</td>'
      + '<td>'+diffCell+'</td>'
      + '</tr>';
  }).join('') || '<tr><td colspan="7" style="text-align:center;padding:20px;color:var(--crm-muted)">Tulemusi ei leitud</td></tr>';

  // Bind qty inputs
  document.querySelectorAll('.sc-qty-input').forEach(function(inp){
    var timer;
    inp.addEventListener('input', function(){
      clearTimeout(timer);
      var invId = this.dataset.invid;
      var val = this.value;
      var row = this.closest('tr');
      timer = setTimeout(function(){
        ajax('vesho_save_count_item', {stock_count_id: currentCountId, inventory_id: invId, counted_qty: val}, function(r){
          if(r.success){
            // Update allItems
            allItems.forEach(function(i){ if(i.inventory_id == invId){ i.counted_qty = val === '' ? null : parseFloat(val); } });
            updateStats(allItems);
            // Update diff cell
            var expected = parseFloat(row.querySelector('td:nth-child(5)').textContent);
            var counted = val === '' ? null : parseFloat(val);
            var diffCell = row.querySelector('td:nth-child(7)');
            if(counted === null){ diffCell.innerHTML = '<span style="color:var(--crm-muted)">—</span>'; }
            else {
              var d = counted - expected;
              diffCell.innerHTML = d === 0 ? '<span style="color:var(--crm-success)">0</span>'
                : '<span style="color:'+(d<0?'var(--crm-danger)':'var(--crm-success))+'">'+(d>0?'+':'')+d+'</span>';
            }
          }
        });
      }, 500);
    });
  });
}

function updateStats(items) {
  var counted = items.filter(function(i){ return i.counted_qty !== null; }).length;
  var diffs = items.filter(function(i){ return i.counted_qty !== null && parseFloat(i.counted_qty) !== parseFloat(i.expected_qty); }).length;
  document.getElementById('sc-d-total').textContent = items.length;
  document.getElementById('sc-d-counted').textContent = counted;
  document.getElementById('sc-d-diffs').textContent = diffs;
}

function esc(s){ var d=document.createElement('div');d.textContent=s;return d.innerHTML; }

// Events
document.getElementById('sc-new-btn').addEventListener('click', function(){
  document.getElementById('sc-new-form').style.display = '';
  document.getElementById('sc-name').focus();
});
document.getElementById('sc-cancel-btn').addEventListener('click', function(){
  document.getElementById('sc-new-form').style.display = 'none';
});
document.getElementById('sc-create-btn').addEventListener('click', function(){
  var name = document.getElementById('sc-name').value.trim();
  if(!name) return alert('Sisesta nimi');
  this.disabled = true;
  this.textContent = 'Loon...';
  var btn = this;
  ajax('vesho_create_stock_count', {name: name}, function(r){
    btn.disabled = false;
    btn.textContent = 'Loo inventuur';
    if(!r.success) return alert(r.data.message);
    document.getElementById('sc-new-form').style.display = 'none';
    document.getElementById('sc-name').value = '';
    loadList();
    openCount(r.data.id);
  });
});
document.getElementById('sc-back-btn').addEventListener('click', function(){
  currentCountId = null;
  allItems = [];
  document.getElementById('sc-list-wrap').style.display = '';
  document.getElementById('sc-detail-wrap').style.display = 'none';
  loadList();
});
document.getElementById('sc-finalize-btn').addEventListener('click', function(){
  if(!confirm('Kinnita inventuur? Laosaldod uuendatakse loendatud koguste järgi.')) return;
  this.disabled = true;
  var btn = this;
  ajax('vesho_finalize_stock_count', {stock_count_id: currentCountId}, function(r){
    btn.disabled = false;
    if(!r.success) return alert(r.data.message);
    alert('Inventuur kinnitatud! Erinevusi: ' + r.data.diffs);
    btn.style.display = 'none';
    // Reload items to show final state
    ajax('vesho_get_stock_count', {stock_count_id: currentCountId}, function(r2){
      if(r2.success){ allItems = r2.data.items; renderItems(allItems); updateStats(allItems); }
    });
  });
});
document.getElementById('sc-filter').addEventListener('input', function(){ renderItems(allItems); });
document.getElementById('sc-filter-status').addEventListener('change', function(){ renderItems(allItems); });

loadList();
})(jQuery||window.jQuery);
</script>
