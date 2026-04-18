<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$nonce = wp_create_nonce('vesho_admin_nonce');
$company = get_option('vesho_company_name', get_option('blogname'));
?>
<div class="crm-wrap">
<h1 class="crm-page-title">🗺 Laoaadressid</h1>
<p style="color:#6b7280;font-size:13px;margin-top:-12px;margin-bottom:16px">Halda laopositsioone. Genereeri aadresside grid või lisa üksikuid.</p>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

  <!-- Generaator -->
  <div class="crm-card" style="padding:18px">
    <div style="font-size:14px;font-weight:700;color:#0d1f2d;margin-bottom:14px">Genereeri aadressid</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
      <div>
        <label class="crm-form-label" style="font-size:11px">Plokk algus</label>
        <input id="loc-blk-start" value="A" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;text-transform:uppercase;font-size:14px;text-align:center;box-sizing:border-box" oninput="this.value=this.value.toUpperCase()">
      </div>
      <div>
        <label class="crm-form-label" style="font-size:11px">Plokk lõpp</label>
        <input id="loc-blk-end" value="A" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;text-transform:uppercase;font-size:14px;text-align:center;box-sizing:border-box" oninput="this.value=this.value.toUpperCase()">
      </div>
      <div></div>
      <div>
        <label class="crm-form-label" style="font-size:11px">Riiul algus</label>
        <input id="loc-sh-start" value="1" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;font-size:14px;text-align:center;box-sizing:border-box">
      </div>
      <div>
        <label class="crm-form-label" style="font-size:11px">Riiul lõpp</label>
        <input id="loc-sh-end" value="5" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;font-size:14px;text-align:center;box-sizing:border-box">
      </div>
      <div></div>
      <div>
        <label class="crm-form-label" style="font-size:11px">Pos. algus</label>
        <input id="loc-pos-start" value="1" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;font-size:14px;text-align:center;box-sizing:border-box">
      </div>
      <div>
        <label class="crm-form-label" style="font-size:11px">Pos. lõpp</label>
        <input id="loc-pos-end" value="10" maxlength="3" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;font-size:14px;text-align:center;box-sizing:border-box">
      </div>
    </div>
    <div style="margin-bottom:12px">
      <label class="crm-form-label" style="font-size:11px">Kirjeldus (valikuline)</label>
      <input id="loc-desc" placeholder="nt Põhiladu, Riiuliplokk A" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;box-sizing:border-box">
    </div>
    <div style="font-size:12px;color:#94a3b8;margin-bottom:10px">
      Loob aadressid kujul <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px">A-01-01</code>, <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px">A-01-02</code> jne.
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="bulkGenerate()" class="crm-btn crm-btn-primary">Genereeri</button>
      <span id="gen-msg" style="font-size:13px;align-self:center;color:#16a34a"></span>
    </div>
  </div>

  <!-- Lisa üksik -->
  <div class="crm-card" style="padding:18px">
    <div style="font-size:14px;font-weight:700;color:#0d1f2d;margin-bottom:14px">Lisa üksik aadress</div>
    <div style="margin-bottom:10px">
      <label class="crm-form-label" style="font-size:11px">Kood *</label>
      <input id="single-code" placeholder="nt A-01-01" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:monospace;font-size:14px;box-sizing:border-box;text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
    </div>
    <div style="margin-bottom:14px">
      <label class="crm-form-label" style="font-size:11px">Kirjeldus (valikuline)</label>
      <input id="single-desc" placeholder="Kirjeldus" style="width:100%;padding:8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;box-sizing:border-box">
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="addSingle()" class="crm-btn crm-btn-primary">Lisa</button>
      <span id="single-msg" style="font-size:13px;align-self:center"></span>
    </div>
  </div>
</div>

<!-- Toolbar -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
  <div>
    <strong id="loc-count" style="font-size:13px;color:#6b7280">Laadin...</strong>
  </div>
  <div style="display:flex;gap:8px">
    <input id="loc-search" placeholder="Otsi koodi..." style="padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px" oninput="filterLocs(this.value)">
    <button onclick="printAllLabels()" class="crm-btn crm-btn-outline crm-btn-sm">🖨️ Prindi kõik</button>
  </div>
</div>

<!-- Table -->
<div id="loc-table-wrap" class="crm-card" style="padding:0;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead>
      <tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb">
        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151">Aadress</th>
        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151">Kirjeldus</th>
        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151">Toode</th>
        <th style="padding:10px 16px;text-align:right;font-weight:600;color:#374151">Kogus</th>
        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#374151;font-family:monospace">EAN</th>
        <th style="padding:10px 16px;text-align:center;font-weight:600;color:#374151">Tegevused</th>
      </tr>
    </thead>
    <tbody id="loc-tbody">
      <tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8">Laadin...</td></tr>
    </tbody>
  </table>
</div>

<script>
var AJAX='<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var NONCE='<?php echo $nonce; ?>';
var allLocs=[];

function post(action,data){
  var fd=new FormData(); fd.append('action',action); fd.append('nonce',NONCE);
  Object.entries(data).forEach(function(e){fd.append(e[0],e[1]);});
  return fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();});
}

function loadLocs(){
  post('vesho_list_warehouse_locations',{}).then(function(d){
    if(!d.success) return;
    allLocs=d.data.locations;
    document.getElementById('loc-count').textContent=allLocs.length+' aadressi';
    renderLocs(allLocs);
  });
}

function renderLocs(locs){
  var tb=document.getElementById('loc-tbody');
  if(!locs.length){tb.innerHTML='<tr><td colspan="6" style="padding:40px;text-align:center;color:#94a3b8">Laoaadresse pole.</td></tr>';return;}
  tb.innerHTML=locs.map(function(l){
    var item=l.item_name?('<strong>'+escH(l.item_name)+'</strong>'+(l.sku?'<span style="font-size:11px;color:#94a3b8;margin-left:6px">'+escH(l.sku)+'</span>':'')):'<span style="color:#94a3b8;font-size:12px">Tühi</span>';
    var qty=l.quantity!=null?(Number(l.quantity)+' '+(l.unit||'')):'–';
    return '<tr style="border-bottom:1px solid #f1f5f9">'+
      '<td style="padding:10px 16px;font-family:monospace;font-weight:700;color:#00b4c8">'+escH(l.code)+'</td>'+
      '<td style="padding:10px 16px;color:#6b7280;font-size:12px">'+escH(l.description||l.label||'')+'</td>'+
      '<td style="padding:10px 16px">'+item+'</td>'+
      '<td style="padding:10px 16px;text-align:right;font-weight:'+(l.item_name?'600':'400')+';color:'+(l.item_name?'#16a34a':'#94a3b8')+'">'+qty+'</td>'+
      '<td style="padding:10px 16px;font-family:monospace;font-size:12px;color:#94a3b8">'+escH(l.ean||'–')+'</td>'+
      '<td style="padding:10px 16px;text-align:center">'+
        '<div style="display:flex;gap:5px;justify-content:center">'+
          '<button onclick="printLabel(\''+escJ(l.code)+'\',\''+escJ(l.description||l.label||'')+'\''+')" style="padding:4px 8px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:6px;cursor:pointer;font-size:12px">🖨️</button>'+
          '<button onclick="deleteLoc('+l.id+',\''+escJ(l.code)+'\')" style="padding:4px 8px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;cursor:pointer;font-size:12px">🗑</button>'+
        '</div>'+
      '</td>'+
    '</tr>';
  }).join('');
}

function filterLocs(q){
  var f=allLocs.filter(function(l){return !q||l.code.toLowerCase().includes(q.toLowerCase())||(l.item_name&&l.item_name.toLowerCase().includes(q.toLowerCase()));});
  renderLocs(f);
}

function bulkGenerate(){
  var msg=document.getElementById('gen-msg');
  var data={
    blockStart:document.getElementById('loc-blk-start').value,
    blockEnd:document.getElementById('loc-blk-end').value,
    shelfStart:document.getElementById('loc-sh-start').value,
    shelfEnd:document.getElementById('loc-sh-end').value,
    posStart:document.getElementById('loc-pos-start').value,
    posEnd:document.getElementById('loc-pos-end').value,
    description:document.getElementById('loc-desc').value
  };
  msg.textContent='Genereerib...'; msg.style.color='#94a3b8';
  post('vesho_bulk_warehouse_locations',data).then(function(d){
    if(d.success){
      msg.textContent='Loodud: '+d.data.created+', olemas: '+d.data.skipped;
      msg.style.color='#16a34a';
      loadLocs();
    } else { msg.textContent=d.data||'Viga'; msg.style.color='#dc2626'; }
  });
}

function addSingle(){
  var code=document.getElementById('single-code').value.trim();
  var desc=document.getElementById('single-desc').value.trim();
  var msg=document.getElementById('single-msg');
  if(!code){msg.textContent='Kood on kohustuslik';msg.style.color='#dc2626';return;}
  post('vesho_add_warehouse_location',{code:code,description:desc}).then(function(d){
    if(d.success){
      msg.textContent='Lisatud!'; msg.style.color='#16a34a';
      document.getElementById('single-code').value='';
      document.getElementById('single-desc').value='';
      loadLocs();
    } else { msg.textContent=d.data||'Viga (juba olemas?)'; msg.style.color='#dc2626'; }
  });
}

function deleteLoc(id,code){
  if(!confirm('Kustuta aadress '+code+'?')) return;
  post('vesho_delete_warehouse_location',{location_id:id}).then(function(d){
    if(d.success) loadLocs();
    else alert(d.data||'Viga');
  });
}

function printLabel(code, desc){
  var w=window.open('','_blank','width=380,height=240');
  w.document.write('<!DOCTYPE html><html><head><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh}.label{width:340px;border:2px solid #111;border-radius:6px;padding:10px 14px;text-align:center}.desc{font-size:11px;color:#555;margin-bottom:4px}@media print{body{min-height:auto}}</style></head><body><div class="label">'+(desc?'<div class="desc">'+desc+'</div>':'')+'<svg id="b"></svg></div><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script><script>window.onload=function(){JsBarcode(\'#b\',\''+code+'\',{format:\'CODE39\',width:2,height:60,displayValue:true,fontSize:14,margin:4});setTimeout(function(){window.print()},300)}<\/script></body></html>');
  w.document.close();
}

function printAllLabels(){
  if(!allLocs.length){alert('Laoaadresse pole.');return;}
  var items=allLocs.map(function(l){return '<div class="label"><div class="code">'+l.code+'</div>'+(l.description||l.label?'<div class="desc">'+(l.description||l.label)+'</div>':'')+'<svg class="b" data-code="'+l.code+'"></svg></div>';}).join('');
  var w=window.open('','_blank');
  w.document.write('<!DOCTYPE html><html><head><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;background:#fff}.label{display:inline-block;width:280px;border:2px solid #111;border-radius:6px;padding:10px 14px;text-align:center;margin:6px;page-break-inside:avoid;vertical-align:top}.code{font-size:12px;font-weight:700;color:#555;margin-bottom:2px}.desc{font-size:10px;color:#777;margin-bottom:4px}@media print{body{margin:0}.label{margin:4px}}</style></head><body>'+items+'<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script><script>window.onload=function(){document.querySelectorAll(\'.b\').forEach(function(el){JsBarcode(el,el.dataset.code,{format:\'CODE39\',width:2,height:55,displayValue:true,fontSize:13,margin:4})});setTimeout(function(){window.print()},400)}<\/script></body></html>');
  w.document.close();
}

function escH(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function escJ(s){return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");}

loadLocs();
</script>
</div>
