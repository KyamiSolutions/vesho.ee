<?php defined('ABSPATH') || exit; ?>
<div class="crm-page-header">
  <div class="crm-page-header__logo">
    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
  </div>
  <div class="crm-page-header__body">
    <h1 class="crm-page-header__title">Laadressid</h1>
    <p class="crm-page-header__subtitle">Riiulite ja asukohtade haldus</p>
  </div>
  <div class="crm-page-header__actions">
    <button class="crm-btn crm-btn--ghost crm-btn--sm" id="wl-add-btn">+ Lisa käsitsi</button>
    <button class="crm-btn crm-btn--primary crm-btn--sm" id="wl-bulk-btn">⚡ Bulk genereeri</button>
  </div>
</div>

<div style="max-width:1100px;margin:0 auto;padding:0 24px 40px">

<!-- Lisa käsitsi -->
<div id="wl-add-form" class="crm-card" style="display:none;margin-bottom:16px">
  <div class="crm-card-header">Lisa üksik aadress</div>
  <div class="crm-card-body">
    <div class="crm-form-grid crm-form-grid--2">
      <div class="crm-form-group">
        <label class="crm-form-label">Kood <span class="required">*</span></label>
        <input type="text" id="wl-code" class="crm-form-input" placeholder="nt. A-01-03" style="text-transform:uppercase">
      </div>
      <div class="crm-form-group">
        <label class="crm-form-label">Kirjeldus</label>
        <input type="text" id="wl-desc" class="crm-form-input" placeholder="vabatahtlik">
      </div>
    </div>
    <div class="crm-form-actions">
      <button class="crm-btn crm-btn--ghost crm-btn--sm" onclick="document.getElementById('wl-add-form').style.display='none'">Tühista</button>
      <button class="crm-btn crm-btn--primary crm-btn--sm" id="wl-add-save-btn">Lisa</button>
    </div>
  </div>
</div>

<!-- Bulk genereerimine -->
<div id="wl-bulk-form" class="crm-card" style="display:none;margin-bottom:16px">
  <div class="crm-card-header">Bulk genereerimine (nt. A-01-01 kuni C-05-10)</div>
  <div class="crm-card-body">
    <div class="crm-form-grid crm-form-grid--2">
      <div class="crm-form-group">
        <label class="crm-form-label">Blokid (nt. A või A-C)</label>
        <input type="text" id="wl-block" class="crm-form-input" placeholder="A" value="A" style="text-transform:uppercase">
      </div>
      <div class="crm-form-group">
        <label class="crm-form-label">Kirjeldus (vabatahtlik)</label>
        <input type="text" id="wl-bulk-desc" class="crm-form-input" placeholder="">
      </div>
      <div class="crm-form-group">
        <label class="crm-form-label">Riiuleid (ridu)</label>
        <input type="number" id="wl-rows" class="crm-form-input" value="5" min="1" max="99">
      </div>
      <div class="crm-form-group">
        <label class="crm-form-label">Positsioone (veerge)</label>
        <input type="number" id="wl-cols" class="crm-form-input" value="10" min="1" max="99">
      </div>
    </div>
    <p style="font-size:12px;color:var(--crm-text-light);margin-top:8px">Koodid luuakse kujul: BLOKK-RIIUL-POSITSIOON (nt. A-01-03)</p>
    <div class="crm-form-actions">
      <button class="crm-btn crm-btn--ghost crm-btn--sm" onclick="document.getElementById('wl-bulk-form').style.display='none'">Tühista</button>
      <button class="crm-btn crm-btn--primary crm-btn--sm" id="wl-bulk-save-btn">Genereeri</button>
    </div>
  </div>
</div>

<!-- Locations table -->
<div class="crm-card">
  <div class="crm-card-header" style="display:flex;justify-content:space-between;align-items:center">
    <span>Laadressid <span id="wl-count" style="color:var(--crm-muted);font-weight:400;font-size:13px"></span></span>
    <input type="text" id="wl-search" class="crm-search" placeholder="Otsi koodi..." style="width:200px">
  </div>
  <div class="crm-table-wrap">
    <table class="crm-table" id="wl-table">
      <thead><tr>
        <th>Kood</th><th>Kirjeldus</th><th>Toode</th><th>SKU / EAN</th><th>Kogus</th><th></th>
      </tr></thead>
      <tbody id="wl-tbody"><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--crm-muted)">Laadimine...</td></tr></tbody>
    </table>
  </div>
</div>

</div>

<script>
(function(){
var nonce = '<?php echo wp_create_nonce("vesho_inv_nonce"); ?>';
var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
var allLocs = [];

function ajax(action, data, cb) {
  data.action = action;
  data.nonce = nonce;
  jQuery.post(ajaxUrl, data, function(r){ cb(r); }, 'json');
}

function loadLocations() {
  ajax('vesho_get_locations', {}, function(r){
    if(!r.success) return;
    allLocs = r.data;
    renderLocations(allLocs);
  });
}

function renderLocations(locs) {
  var search = (document.getElementById('wl-search').value||'').toLowerCase();
  var filtered = search ? locs.filter(function(l){
    return (l.code||'').toLowerCase().includes(search) || (l.item_name||'').toLowerCase().includes(search) || (l.sku||'').toLowerCase().includes(search);
  }) : locs;

  document.getElementById('wl-count').textContent = '(' + locs.length + ' kokku)';
  document.getElementById('wl-tbody').innerHTML = filtered.length ? filtered.map(function(l){
    var itemCell = l.item_name
      ? '<span style="font-weight:500">'+esc(l.item_name)+'</span>'
      : '<span style="color:var(--crm-muted)">—</span>';
    var skuCell = l.sku || l.ean
      ? esc((l.sku||'') + (l.sku&&l.ean?' / ':'' ) + (l.ean||''))
      : '<span style="color:var(--crm-muted)">—</span>';
    var qtyCell = l.quantity !== null && l.item_name
      ? l.quantity + ' ' + esc(l.unit||'')
      : '<span style="color:var(--crm-muted)">—</span>';
    return '<tr>'
      + '<td><strong style="font-family:monospace;font-size:13px">'+esc(l.code)+'</strong></td>'
      + '<td style="color:var(--crm-muted)">'+esc(l.description||'')+'</td>'
      + '<td>'+itemCell+'</td>'
      + '<td style="color:var(--crm-muted);font-size:12px">'+skuCell+'</td>'
      + '<td>'+qtyCell+'</td>'
      + '<td><button class="crm-btn crm-btn--danger crm-btn--xs wl-del-btn" data-id="'+l.id+'">Kustuta</button></td>'
      + '</tr>';
  }).join('') : '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--crm-muted)">Tulemusi ei leitud</td></tr>';

  document.querySelectorAll('.wl-del-btn').forEach(function(b){
    b.addEventListener('click', function(){
      if(!confirm('Kustuta aadress ' + this.closest('tr').querySelector('td strong').textContent + '?')) return;
      ajax('vesho_delete_location', {location_id: this.dataset.id}, function(r){
        if(r.success) loadLocations();
        else alert(r.data.message);
      });
    });
  });
}

function esc(s){ var d=document.createElement('div');d.textContent=s;return d.innerHTML; }

// Events
document.getElementById('wl-add-btn').addEventListener('click', function(){
  document.getElementById('wl-add-form').style.display = '';
  document.getElementById('wl-bulk-form').style.display = 'none';
  document.getElementById('wl-code').focus();
});
document.getElementById('wl-bulk-btn').addEventListener('click', function(){
  document.getElementById('wl-bulk-form').style.display = '';
  document.getElementById('wl-add-form').style.display = 'none';
});
document.getElementById('wl-add-save-btn').addEventListener('click', function(){
  var code = document.getElementById('wl-code').value.trim().toUpperCase();
  var desc = document.getElementById('wl-desc').value.trim();
  if(!code) return alert('Sisesta kood');
  this.disabled = true;
  var btn = this;
  ajax('vesho_add_location', {code: code, description: desc}, function(r){
    btn.disabled = false;
    if(!r.success) return alert(r.data.message);
    document.getElementById('wl-add-form').style.display = 'none';
    document.getElementById('wl-code').value = '';
    document.getElementById('wl-desc').value = '';
    loadLocations();
  });
});
document.getElementById('wl-bulk-save-btn').addEventListener('click', function(){
  var block = document.getElementById('wl-block').value.trim().toUpperCase() || 'A';
  var rows  = parseInt(document.getElementById('wl-rows').value) || 5;
  var cols  = parseInt(document.getElementById('wl-cols').value) || 10;
  var desc  = document.getElementById('wl-bulk-desc').value.trim();
  if(!confirm('Genereerida ' + block + '-blokki ' + rows + 'x' + cols + ' aadressi?')) return;
  this.disabled = true;
  this.textContent = 'Genereeritakse...';
  var btn = this;
  ajax('vesho_gen_locations', {block_range: block, rows: rows, cols: cols, description: desc}, function(r){
    btn.disabled = false;
    btn.textContent = 'Genereeri';
    if(!r.success) return alert(r.data.message||'Viga');
    alert('Loodud: ' + r.data.created + ' aadressi');
    document.getElementById('wl-bulk-form').style.display = 'none';
    loadLocations();
  });
});
document.getElementById('wl-search').addEventListener('input', function(){ renderLocations(allLocs); });

loadLocations();
})(jQuery||window.jQuery);
</script>
