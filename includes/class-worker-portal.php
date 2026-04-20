<?php defined('ABSPATH') || exit;

class Vesho_CRM_Worker_Portal {

    public static function init() {
        add_shortcode('vesho_worker_portal', [__CLASS__, 'shortcode']);

        $nopriv = ['vesho_worker_login', 'vesho_worker_logout', 'vesho_worker_scan_checkin', 'vesho_worker_barcode_login'];
        $auth   = [
            'vesho_worker_start_order',
            'vesho_worker_complete_order',
            'vesho_worker_complete_maintenance',
            'vesho_upload_maintenance_photo',
            'vesho_worker_log_hours',
            'vesho_worker_clock_in',
            'vesho_worker_clock_out',
            'vesho_worker_save_materials',
            'vesho_worker_get_inventory',
            'vesho_worker_save_store_material',
            // New: inventuur
            'vesho_worker_get_inv_count',
            'vesho_worker_save_count_item',
            'vesho_worker_lock_count_item',
            'vesho_worker_complete_stock_section',
            // New: vastuvõtt
            'vesho_worker_get_receipt_items',
            'vesho_worker_confirm_receipt',
            'vesho_worker_submit_batch',
            'vesho_worker_add_batch_item',
            // New: tellimused
            'vesho_worker_claim_order',
            'vesho_worker_release_order',
            'vesho_worker_pick_item',
            'vesho_worker_pack_order',
            'vesho_worker_shop_count',
            // Location check
            'vesho_check_warehouse_location',
            // EAN lookup
            'vesho_worker_lookup_ean',
            // Photo delete
            'vesho_worker_delete_photo',
        ];
        foreach ($nopriv as $a) {
            add_action('wp_ajax_nopriv_' . $a, [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
            add_action('wp_ajax_' . $a,        [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
        }
        foreach ($auth as $a) {
            add_action('wp_ajax_' . $a, [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
        }
        // Direct registration for actions that don't follow vesho_worker_ prefix
        add_action('wp_ajax_vesho_upload_maintenance_photo', [__CLASS__, 'ajax_upload_maintenance_photo']);

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public static function enqueue_assets() {
        global $post;
        if (!$post) return;
        if (has_shortcode($post->post_content, 'vesho_worker_portal')) {
            if (!wp_style_is('vesho-worker-portal', 'enqueued')) {
                $theme_css = get_template_directory_uri() . '/assets/css/worker-portal.css';
                wp_enqueue_style('vesho-worker-portal', $theme_css, array(), filemtime(get_template_directory() . '/assets/css/worker-portal.css'));
            }
            // ZXing barcode scanner (for QR card login + shop picking)
            wp_enqueue_script('zxing-library', 'https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js', array(), '0.21.3', true);
            wp_enqueue_script('vesho-ean-scanner', VESHO_CRM_URL . 'admin/js/ean-scanner.js', array('zxing-library'), VESHO_CRM_VERSION, true);
        }
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    private static function get_worker() {
        return self::get_current_worker();
    }

    public static function get_current_worker() {
        if (!is_user_logged_in()) return null;
        $user = wp_get_current_user();
        global $wpdb;
        // Admin preview: use first worker in DB
        if (current_user_can('manage_options')) {
            $w = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}vesho_workers WHERE active=1 ORDER BY id ASC LIMIT 1");
            return $w ?: null;
        }
        if (!in_array('vesho_worker', (array) $user->roles)) return null;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE user_id=%d AND active=1 LIMIT 1",
            $user->ID
        ));
    }

    // ── Shortcode ─────────────────────────────────────────────────────────────

    public static function shortcode() {
        // WP admin — show portal directly
        if ( current_user_can('manage_options') ) {
            global $wpdb;
            $worker = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}vesho_workers ORDER BY id ASC LIMIT 1");
            if ( !$worker ) {
                $worker = (object)['id'=>0,'name'=>'Töötaja puudub','phone'=>'','email'=>'','role'=>'technician','active'=>1,'user_id'=>0];
            }
            ob_start();
            self::render_dashboard($worker);
            return ob_get_clean();
        }
        if (!is_user_logged_in() || !current_user_can('vesho_worker')) {
            ob_start();
            self::render_login();
            return ob_get_clean();
        }
        $worker = self::get_current_worker();
        if (!$worker) return '<p>Töötaja konto ei ole seotud CRM töötajaga. <a href="'.esc_url(wp_logout_url(home_url())).'">Logi välja</a></p>';
        ob_start();
        self::render_dashboard($worker);
        return ob_get_clean();
    }

    // ── Login form ────────────────────────────────────────────────────────────

    private static function render_login() {
        $ajax  = esc_url(admin_url('admin-ajax.php'));
        $nonce = wp_create_nonce('vesho_portal_nonce');
        ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap');
.vwauth-wrap{min-height:100vh;background:linear-gradient(135deg,#0d1f2d 0%,#1a3a2a 100%);display:flex;align-items:center;justify-content:center;padding:24px;font-family:'Barlow',sans-serif}
.vwauth-logo{text-align:center;margin-bottom:28px}
.vwauth-logo a{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.9rem;color:#fff;text-decoration:none;letter-spacing:2px;text-transform:uppercase;display:inline-flex;align-items:center;gap:10px}
.vwauth-logo span{color:#10b981}
.vwauth-card{background:#fff;border-radius:12px;padding:40px 36px;box-shadow:0 20px 60px rgba(0,0,0,.35);width:100%;max-width:400px;position:relative}
.vwauth-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#10b981,#059669);border-radius:12px 12px 0 0}
.vwauth-card h2{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.5rem;text-transform:uppercase;color:#0d1f2d;text-align:center;margin:0 0 24px}
.vwauth-group{margin-bottom:18px}
.vwauth-group label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#0d1f2d;margin-bottom:6px}
.vwauth-group input{width:100%;padding:12px 14px;border:2px solid #e0e6eb;border-radius:6px;font-family:'Barlow',sans-serif;font-size:15px;color:#0d1f2d;background:#f4f7f9;box-sizing:border-box;transition:.2s;outline:none}
.vwauth-group input:focus{border-color:#10b981;background:#fff;box-shadow:0 0 0 3px rgba(16,185,129,.1)}
.vwauth-pin-wrap{display:flex;gap:10px;justify-content:center;margin-bottom:4px}
.vwauth-pin-dot{width:16px;height:16px;border-radius:50%;border:2px solid #c0cdd6;background:transparent;transition:.2s}
.vwauth-pin-dot.filled{background:#10b981;border-color:#10b981}
.vwauth-btn{width:100%;padding:14px;background:#10b981;color:#fff;border:none;border-radius:6px;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1.05rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:.2s;margin-top:6px}
.vwauth-btn:hover{background:#059669}
.vwauth-btn:disabled{background:#a0b0bc;cursor:not-allowed}
.vwauth-msg{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:14px;display:none}
.vwauth-msg.success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.vwauth-msg.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.vwauth-hint{text-align:center;margin-top:16px;font-size:12px;color:#6b8599}
</style>
<div class="vwauth-wrap">
  <div>
    <div class="vwauth-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <svg viewBox="0 0 32 32" fill="none" width="36" height="36"><path d="M16 4C16 4 6 13 6 20a10 10 0 0020 0C26 13 16 4 16 4Z" fill="#10b981"/><path d="M16 12C16 12 10 18 10 22a6 6 0 0012 0C22 18 16 12 16 12Z" fill="white" opacity=".5"/></svg>
        VESHO<span>.</span>
      </a>
    </div>
    <div class="vwauth-card">
      <h2>Töötaja Portaal</h2>
      <div class="vwauth-msg" id="vwauth-msg"></div>
      <form id="vwauth-form">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
        <div class="vwauth-group">
          <label>Nimi</label>
          <input type="text" name="worker_name" required placeholder="Sinu nimi" autocomplete="name">
        </div>
        <div class="vwauth-group">
          <label>PIN-kood</label>
          <div class="vwauth-pin-wrap" id="vwauth-pin-dots">
            <div class="vwauth-pin-dot" id="vwdot-0"></div>
            <div class="vwauth-pin-dot" id="vwdot-1"></div>
            <div class="vwauth-pin-dot" id="vwdot-2"></div>
            <div class="vwauth-pin-dot" id="vwdot-3"></div>
          </div>
          <input type="password" name="pin" id="vwauth-pin" required placeholder="••••" maxlength="10" inputmode="numeric" autocomplete="off" style="text-align:center;font-size:1.4rem;letter-spacing:6px">
        </div>
        <button type="submit" class="vwauth-btn">Logi sisse</button>
      </form>
      <div style="text-align:center;margin-top:16px">
        <button type="button" id="vwauth-scan-btn" class="vwauth-btn" style="background:#0d1f2d;margin-top:0" onclick="startQRLogin()">
          📷 Skänni töötaja QR kaart
        </button>
      </div>
      <div class="vwauth-hint">PIN-koodi annab administraator</div>
    </div>
  </div>
</div>
<script>
(function(){
  var ajaxUrl='<?php echo $ajax; ?>';
  var nonce='<?php echo $nonce; ?>';

  // PIN dot indicator
  var pinInput=document.getElementById('vwauth-pin');
  if(pinInput){
    pinInput.addEventListener('input',function(){
      var len=this.value.length;
      for(var i=0;i<4;i++){
        var dot=document.getElementById('vwdot-'+i);
        if(dot) dot.className='vwauth-pin-dot'+(i<len?' filled':'');
      }
    });
  }

  var form=document.getElementById('vwauth-form');
  if(form){
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var msg=document.getElementById('vwauth-msg');
      var fd=new FormData(form); fd.append('action','vesho_worker_login');
      var btn=form.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
      msg.style.display='none';
      fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        msg.style.display='block';
        msg.className='vwauth-msg '+(d.success?'success':'error');
        msg.textContent=(d.data&&d.data.message)||(d.success?'Õnnestus!':'Viga!');
        if(d.success) setTimeout(function(){window.location.href=(d.data&&d.data.redirect)||window.location.href;},600);
        else{btn.disabled=false; btn.textContent='Logi sisse';}
      }).catch(function(){msg.style.display='block';msg.className='vwauth-msg error';msg.textContent='Ühenduse viga';btn.disabled=false;btn.textContent='Logi sisse';});
    });
  }

  window.startQRLogin = function(){
    if(typeof window.VeshoScanner === 'undefined'){
      alert('Skänner pole laaditud. Proovi lehte uuendada.');
      return;
    }
    window.VeshoScanner.open({
      title: 'Skänni töötaja QR kaart',
      autoConfirm: true,
      manualInput: false,
      onScan: function(token){
        var msg=document.getElementById('vwauth-msg');
        msg.style.display='block'; msg.className='vwauth-msg'; msg.textContent='Sisselogimine...';
        var fd=new FormData();
        fd.append('action','vesho_worker_barcode_login');
        fd.append('nonce',nonce);
        fd.append('token',token);
        fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
          msg.className='vwauth-msg '+(d.success?'success':'error');
          msg.textContent=(d.data&&d.data.message)||(d.success?'Tere, '+(d.data&&d.data.name||'')+'!':'Kaart ei leitud');
          if(d.success) setTimeout(function(){window.location.href=(d.data&&d.data.redirect)||window.location.href;},500);
        }).catch(function(){msg.className='vwauth-msg error';msg.textContent='Ühenduse viga';});
      }
    });
  };
})();
</script>
        <?php
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    private static function render_dashboard($worker) {
        global $wpdb;
        $wid  = (int) $worker->id;
        $tab  = isset($_GET['wtab']) ? sanitize_text_field($_GET['wtab']) : 'overview';
        $base = get_permalink();
        $logout_url = wp_logout_url(home_url('/worker/'));
        $ajax  = esc_url(admin_url('admin-ajax.php'));
        $nonce = wp_create_nonce('vesho_portal_nonce');
        $avatar    = strtoupper(mb_substr($worker->name ?? 'T', 0, 1));
        $logo_id   = get_theme_mod('custom_logo');
        $site_name = esc_html(get_theme_mod('vesho_company_display_name', get_option('blogname', 'VESHO')));

        // Count active orders for badge
        $active_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders WHERE worker_id=%d AND status IN ('pending','assigned','in_progress')", $wid
        ));
        // Count active (uncompleted) shop orders claimed by this worker
        $shop_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE worker_id=%d AND status='processing'", $wid
        ));

        $nav_items = [
            'overview'   => ['icon' => '&#9634;',  'label' => 'Ülevaade'],
            'active'     => ['icon' => '&#9651;',  'label' => 'Aktiivsed',  'badge' => $active_count ?: null],
            'history'    => ['icon' => '&#9643;',  'label' => 'Ajalugu'],
            'schedule'   => ['icon' => '&#128197;','label' => 'Graafik'],
            'tellimused' => ['icon' => '&#128722;','label' => 'Tellimused', 'badge' => $shop_count ?: null],
        ];
        $nav_items['vastuvott'] = ['icon' => '&#128230;','label' => 'Vastuvõtt'];
        $nav_items['inventuur'] = ['icon' => '&#128270;','label' => 'Inventuur'];
        ?>
<div class="vwp-wrap">
  <aside class="vwp-sidebar" id="vwpSidebar">
    <div class="vwp-sidebar-logo">
      <?php if ($logo_id): ?>
        <a href="<?php echo esc_url(home_url('/')); ?>">
          <img src="<?php echo esc_url(wp_get_attachment_image_url($logo_id, 'full')); ?>"
               alt="<?php echo esc_attr(get_option('blogname')); ?>">
        </a>
      <?php else: ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="vwp-sidebar-logo-text">
          <?php echo $site_name; ?><span>.</span>
        </a>
      <?php endif; ?>
    </div>
    <div class="vwp-sidebar-label">Menüü</div>
    <ul class="vwp-nav">
      <?php foreach ($nav_items as $tid => $item): ?>
      <li><a href="<?php echo esc_url(add_query_arg('wtab', $tid, $base)); ?>"
             class="vwp-nav-link <?php echo $tab === $tid ? 'active' : ''; ?>"
             style="justify-content:space-between">
        <span style="display:flex;align-items:center;gap:10px">
          <span class="vwp-nav-icon"><?php echo $item['icon']; ?></span>
          <?php echo esc_html($item['label']); ?>
        </span>
        <?php if (!empty($item['badge'])): ?>
          <span id="vwp-nav-badge-<?php echo esc_attr($tid); ?>" style="background:#f59e0b;color:#fff;font-size:10px;font-weight:700;border-radius:999px;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 5px"><?php echo (int)$item['badge']; ?></span>
        <?php endif; ?>
      </a></li>
      <?php endforeach; ?>
    </ul>
    <div class="vwp-sidebar-footer">
      <div class="vwp-sidebar-user">
        <div class="vwp-avatar"><?php echo esc_html($avatar); ?></div>
        <div>
          <div class="vwp-sidebar-name"><?php echo esc_html($worker->name); ?></div>
          <div class="vwp-sidebar-role"><?php echo esc_html($worker->role ?? 'Töötaja'); ?></div>
        </div>
      </div>
      <a href="<?php echo esc_url($logout_url); ?>" class="vwp-logout-link">Logi välja</a>
    </div>
  </aside>

  <div class="vwp-main">
    <header class="vwp-topbar">
      <button class="vwp-hamburger" id="vwpHamburger" aria-label="Menüü">&#9776;</button>
      <span class="vwp-topbar-title"><?php echo esc_html($nav_items[$tab]['label'] ?? 'Portaal'); ?></span>
      <div class="vwp-topbar-right">Tere, <strong><?php echo esc_html(explode(' ', $worker->name)[0]); ?></strong></div>
    </header>
    <div class="vwp-content">
      <?php
      // ── Global announcement ───────────────────────────────────────────────────
      $g_ann_w = get_option('vesho_global_announcement', '');
      $g_type_w = get_option('vesho_global_announcement_type', 'info');
      if ($g_ann_w):
          $g_bg_w  = $g_type_w==='warning' ? '#fff7ed' : ($g_type_w==='success' ? '#f0fdf4' : '#eef2ff');
          $g_brd_w = $g_type_w==='warning' ? '#fcd34d' : ($g_type_w==='success' ? '#86efac' : '#c7d2fe');
          $g_col_w = $g_type_w==='warning' ? '#92400e' : ($g_type_w==='success' ? '#166534' : '#3730a3');
          $g_icon_w = $g_type_w==='warning' ? '⚠️' : ($g_type_w==='success' ? '✅' : '📣');
      ?>
      <div style="background:<?php echo $g_bg_w; ?>;border-bottom:2px solid <?php echo $g_brd_w; ?>;padding:10px 20px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:<?php echo $g_col_w; ?>">
        <span><?php echo $g_icon_w; ?></span>
        <span><?php echo esc_html($g_ann_w); ?></span>
      </div>
      <?php endif; ?>
      <?php
      // ── Portal notices (layout-tasemel, igas tab-is, 3006 stiil) ─────────────
      $today_wn = current_time('Y-m-d');
      $worker_notices = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}vesho_portal_notices
           WHERE active=1 AND target IN ('worker','both')
           AND (starts_at IS NULL OR starts_at <= %s)
           AND (ends_at IS NULL OR ends_at >= %s)
           ORDER BY created_at DESC LIMIT 5",
          $today_wn, $today_wn
      ));
      if (!empty($worker_notices)): ?>
      <script>var _vwn_dm=(function(){try{return JSON.parse(sessionStorage.getItem("vwn_dm")||"[]");}catch(e){return [];}})();</script>
      <?php endif; ?>
      <?php foreach ($worker_notices as $wn) :
          $wtype  = $wn->type ?? 'info';
          $wbg    = $wtype==='warning' ? 'rgba(245,158,11,0.12)' : ($wtype==='success' ? 'rgba(16,185,129,0.12)' : 'rgba(99,102,241,0.12)');
          $wbord  = $wtype==='warning' ? 'rgba(245,158,11,0.3)'  : ($wtype==='success' ? 'rgba(16,185,129,0.3)'  : 'rgba(99,102,241,0.3)');
          $wcol   = $wtype==='warning' ? '#f59e0b' : ($wtype==='success' ? '#10b981' : '#818cf8');
          $wicon  = $wtype==='warning' ? '⚠️' : ($wtype==='success' ? '✅' : 'ℹ️');
          $wid_n  = (int)$wn->id;
      ?>
      <div id="vwpn-<?php echo $wid_n; ?>" style="background:<?php echo $wbg; ?>;border-bottom:1px solid <?php echo $wbord; ?>;padding:10px 20px;display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:8px;border-radius:6px">
        <span style="font-size:16px"><?php echo $wicon; ?></span>
        <span style="color:<?php echo $wcol; ?>;font-weight:600"><?php echo esc_html($wn->title); ?></span>
        <span style="color:#9ca3af">— <?php echo esc_html($wn->message); ?></span>
        <button onclick="var dm=(function(){try{return JSON.parse(sessionStorage.getItem('vwn_dm')||'[]');}catch(e){return [];}})();dm.push(<?php echo $wid_n; ?>);sessionStorage.setItem('vwn_dm',JSON.stringify(dm));document.getElementById('vwpn-<?php echo $wid_n; ?>').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:0 4px">×</button>
      </div>
      <?php endforeach; ?>
      <?php if (!empty($worker_notices)): ?>
      <script>(function(){_vwn_dm.forEach(function(id){var el=document.getElementById("vwpn-"+id);if(el)el.remove();});})();</script>
      <?php endif; ?>
      <?php
      switch ($tab) {
          case 'overview':   self::tab_overview($worker, $wid, $nonce, $ajax); break;
          case 'active':     self::tab_active($wid, $nonce, $ajax); break;
          case 'history':    self::tab_history($wid); break;
          case 'schedule':   self::tab_schedule($wid, $ajax); break;
          case 'inventuur':
              self::tab_inventuur($wid, $nonce, $ajax);
              break;
          case 'vastuvott':
              self::tab_vastuvott($wid, $nonce, $ajax);
              break;
          case 'tellimused': self::tab_tellimused($wid, $nonce, $ajax); break;
          default:           self::tab_overview($worker, $wid, $nonce, $ajax);
      }
      ?>
    </div>
  </div>
</div>
<script>
(function(){
  var sidebar=document.getElementById('vwpSidebar');
  var ham=document.getElementById('vwpHamburger');
  var backdrop=document.createElement('div');
  backdrop.className='vwp-backdrop';
  document.body.appendChild(backdrop);
  function openSidebar(){
    sidebar.classList.add('open');
    backdrop.classList.add('active');
    document.body.classList.add('vwp-sidebar-open');
  }
  function closeSidebar(){
    sidebar.classList.remove('open');
    backdrop.classList.remove('active');
    document.body.classList.remove('vwp-sidebar-open');
  }
  if(ham&&sidebar){
    ham.addEventListener('click',function(e){
      e.stopPropagation();
      sidebar.classList.contains('open')?closeSidebar():openSidebar();
    });
  }
  backdrop.addEventListener('click',closeSidebar);
})();
</script>
        <?php
    }

    // ── Tab: Overview ─────────────────────────────────────────────────────────

    private static function tab_overview($worker, $wid, $nonce, $ajax) {
        global $wpdb;
        $today = current_time('Y-m-d');

        // Stats matching 3006: today / in_progress / completed_today
        $today_orders = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders WHERE worker_id=%d AND scheduled_date=%s AND status NOT IN ('cancelled')", $wid, $today
        ));
        $in_progress = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders WHERE worker_id=%d AND status IN ('assigned','in_progress')", $wid
        ));
        $completed_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders WHERE worker_id=%d AND status='completed' AND completed_date=%s", $wid, $today
        ));

        // Clock status
        $open_clock = $wpdb->get_row($wpdb->prepare(
            "SELECT id, start_time FROM {$wpdb->prefix}vesho_work_hours WHERE worker_id=%d AND end_time IS NULL ORDER BY start_time DESC LIMIT 1", $wid
        ));
        $clocked_in  = (bool) $open_clock;
        $clock_since = $clocked_in ? esc_html(date('H:i', strtotime($open_clock->start_time))) : '';

        // Today's work orders list
        $today_list = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             WHERE wo.worker_id=%d AND wo.scheduled_date=%s AND wo.status IN ('open','assigned','in_progress')
             ORDER BY wo.created_at ASC LIMIT 20", $wid, $today
        ));

        // Upcoming tasks (future, not today)
        $upcoming = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             WHERE wo.worker_id=%d AND wo.scheduled_date > %s AND wo.status NOT IN ('completed','cancelled')
             ORDER BY wo.scheduled_date ASC LIMIT 5", $wid, $today
        ));

        $barcode_token = esc_js($worker->barcode_token ?? '');
        $has_qr = !empty($worker->barcode_token);

        // Work time stats from vesho_work_hours
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today_end   = current_time('Y-m-d') . ' 23:59:59';
        $week_start  = date('Y-m-d', strtotime('monday this week', current_time('timestamp'))) . ' 00:00:00';
        $month_start = date('Y-m-01', current_time('timestamp')) . ' 00:00:00';

        $today_mins = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))), 0)
             FROM {$wpdb->prefix}vesho_work_hours
             WHERE worker_id=%d AND start_time >= %s AND start_time <= %s",
            $wid, $today_start, $today_end
        ));
        $week_mins = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))), 0)
             FROM {$wpdb->prefix}vesho_work_hours
             WHERE worker_id=%d AND start_time >= %s",
            $wid, $week_start
        ));
        $month_mins = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, IFNULL(end_time, NOW()))), 0)
             FROM {$wpdb->prefix}vesho_work_hours
             WHERE worker_id=%d AND start_time >= %s",
            $wid, $month_start
        ));

        // In-progress highlight
        $active_order = $wpdb->get_row($wpdb->prepare(
            "SELECT wo.*, c.name as client_name FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             WHERE wo.worker_id=%d AND wo.status='in_progress' ORDER BY wo.created_at DESC LIMIT 1", $wid
        ));

        // Next-week jobs (workorders + maintenances)
        $next_week_start = date('Y-m-d', strtotime('monday next week', current_time('timestamp')));
        $next_week_end   = date('Y-m-d', strtotime('sunday next week', current_time('timestamp')));
        $next_week_wo = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorders
             WHERE worker_id=%d AND scheduled_date BETWEEN %s AND %s AND status NOT IN ('completed','cancelled')",
            $wid, $next_week_start, $next_week_end
        ));
        $next_week_maint = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances
             WHERE worker_id=%d AND scheduled_date BETWEEN %s AND %s AND status NOT IN ('completed','cancelled')",
            $wid, $next_week_start, $next_week_end
        ));
        $next_week_total = $next_week_wo + $next_week_maint;

        $fmt_mins = function(int $m): string {
            if ($m < 60) return "{$m} min";
            $h = intdiv($m, 60); $rem = $m % 60;
            return $rem ? "{$h}h {$rem}min" : "{$h}h";
        };
        ?>

<div class="vwp-page-header">
  <h1>Tere, <?php echo esc_html(explode(' ', $worker->name)[0]); ?>!</h1>
</div>

<?php if ($active_order): ?>
<div class="vwp-card" style="margin-bottom:16px;border-left:4px solid #10b981;background:rgba(16,185,129,0.05)">
  <div style="font-size:11px;font-weight:700;color:#10b981;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">🟢 Hetkel töös</div>
  <div style="font-weight:700;font-size:15px;color:#1e293b"><?php echo esc_html($active_order->title); ?></div>
  <div style="font-size:13px;color:#64748b;margin-top:2px"><?php echo esc_html($active_order->client_name ?? '—'); ?></div>
</div>
<?php endif; ?>

<div class="vwp-stat-grid">
  <div class="vwp-stat-card vwp-stat-orange">
    <div class="vwp-stat-label">Tänased tööd</div>
    <div class="vwp-stat-value"><?php echo $today_orders; ?></div>
  </div>
  <div class="vwp-stat-card vwp-stat-blue">
    <div class="vwp-stat-label">Pooleli</div>
    <div class="vwp-stat-value"><?php echo $in_progress; ?></div>
  </div>
  <div class="vwp-stat-card vwp-stat-green">
    <div class="vwp-stat-label">Tehtud täna</div>
    <div class="vwp-stat-value"><?php echo $completed_today; ?></div>
  </div>
  <div class="vwp-stat-card" style="border-top:3px solid #f59e0b;background:#fffbeb">
    <div class="vwp-stat-label" style="color:#92400e">Järgmisel nädalal</div>
    <div class="vwp-stat-value" style="color:#d97706"><?php echo $next_week_total; ?></div>
  </div>
</div>

<!-- Time stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px">
  <div class="vwp-card" style="padding:14px;text-align:center">
    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px">Täna</div>
    <div style="font-size:20px;font-weight:800;color:#1e293b"><?php echo $fmt_mins($today_mins); ?></div>
  </div>
  <div class="vwp-card" style="padding:14px;text-align:center">
    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px">Nädal</div>
    <div style="font-size:20px;font-weight:800;color:#1e293b"><?php echo $fmt_mins($week_mins); ?></div>
  </div>
  <div class="vwp-card" style="padding:14px;text-align:center">
    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px">Kuu</div>
    <div style="font-size:20px;font-weight:800;color:#1e293b"><?php echo $fmt_mins($month_mins); ?></div>
  </div>
</div>

<!-- Clock in/out -->
<div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;margin-bottom:28px">
<div class="vwp-card" style="flex:1;min-width:280px;max-width:380px">
  <h3 style="margin:0 0 14px;font-size:15px;font-weight:600">&#9200; Tööaeg</h3>
  <div id="vwp-clock-msg" class="vwp-msg" style="display:none"></div>
  <?php if ($clocked_in): ?>
    <p style="margin:0 0 12px;color:#16a34a;font-size:13px;display:flex;align-items:center;gap:6px">
      <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block"></span>
      Tööl alates <?php echo $clock_since; ?>
    </p>
    <div style="display:flex;gap:8px">
      <button id="vwp-clockout-btn" data-id="<?php echo $open_clock->id; ?>"
              data-nonce="<?php echo $nonce; ?>"
              class="vwp-btn-outline" style="flex:1">&#9632; Lõpeta tööpäev</button>
      <?php if ($has_qr): ?>
      <button id="vwp-scan-btn" class="vwp-btn-outline" style="flex:1">&#128247; Skänni välja</button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <p style="margin:0 0 12px;color:#94a3b8;font-size:13px;display:flex;align-items:center;gap:6px">
      <span style="width:8px;height:8px;border-radius:50%;background:#cbd5e1;display:inline-block"></span>
      Tööpäev pole alustatud
    </p>
    <div style="display:flex;gap:8px">
      <button id="vwp-clockin-btn" data-nonce="<?php echo $nonce; ?>"
              class="vwp-btn-primary" style="flex:1">&#9654; Alusta tööpäeva</button>
      <?php if ($has_qr): ?>
      <button id="vwp-scan-btn" class="vwp-btn-outline" style="flex:1">&#128247; Skänni sisse</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php if ($has_qr): ?>
<div class="vwp-card" style="text-align:center;min-width:180px">
  <h3 style="margin:0 0 10px;font-size:13px;font-weight:600;color:#64748b">Minu QR kaart</h3>
  <div id="vwp-qr-canvas" style="display:inline-block;padding:6px;background:#fff;border-radius:6px"></div>
  <p style="margin:8px 0 0;font-size:11px;color:#94a3b8">Skänni sisse/välja</p>
</div>
<?php endif; ?>
</div>

<!-- Today's work orders -->
<h2 class="vwp-section-title">Tänased töökäsud</h2>
<?php if (empty($today_list)): ?>
  <div class="vwp-empty" style="margin-bottom:28px">Täna pole töökäske planeeritud.</div>
<?php else: ?>
<div style="margin-bottom:28px">
  <table class="vwp-table">
    <thead><tr><th>Töökäsk</th><th>Klient</th><th>Töö liik</th><th>Staatus</th></tr></thead>
    <tbody>
    <?php foreach ($today_list as $wo): ?>
    <tr>
      <td><strong><?php echo esc_html($wo->title); ?></strong></td>
      <td><?php echo esc_html($wo->client_name ?? '—'); ?></td>
      <td style="color:#64748b;font-size:12px"><?php echo esc_html($wo->service_type ?? '—'); ?></td>
      <td><?php echo self::status_badge($wo->status); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Upcoming tasks -->
<?php if (!empty($upcoming)): ?>
<h2 class="vwp-section-title">Tulevased ülesanded</h2>
<div style="margin-bottom:28px">
  <table class="vwp-table">
    <thead><tr><th>Töökäsk</th><th>Klient</th><th>Kuupäev</th><th>Staatus</th></tr></thead>
    <tbody>
    <?php foreach ($upcoming as $wo): ?>
    <tr>
      <td><strong><?php echo esc_html($wo->title); ?></strong></td>
      <td><?php echo esc_html($wo->client_name ?? '—'); ?></td>
      <td style="color:#64748b;font-size:12px"><?php echo esc_html($wo->scheduled_date ? date('d.m.Y', strtotime($wo->scheduled_date)) : '—'); ?></td>
      <td><?php echo self::status_badge($wo->status); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  var NONCE='<?php echo $nonce; ?>';
  var TOKEN='<?php echo $barcode_token; ?>';

  var qrEl=document.getElementById('vwp-qr-canvas');
  if(qrEl&&TOKEN&&typeof QRCode!=='undefined'){
    qrEl.innerHTML='';
    new QRCode(qrEl,{text:TOKEN,width:140,height:140,colorDark:'#1e293b',colorLight:'#fff',correctLevel:QRCode.CorrectLevel.M});
  }

  function showMsg(ok,txt){var m=document.getElementById('vwp-clock-msg');if(!m)return;m.style.display='block';m.className='vwp-msg '+(ok?'success':'error');m.textContent=txt;}

  function getGPS(cb) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function(pos){ cb(pos.coords.latitude, pos.coords.longitude); },
        function(){ cb(null, null); },
        { timeout: 5000, maximumAge: 60000 }
      );
    } else { cb(null, null); }
  }

  var ci=document.getElementById('vwp-clockin-btn');
  if(ci) ci.addEventListener('click',function(){
    ci.disabled=true; ci.textContent='...';
    getGPS(function(lat,lng){
      var fd=new FormData(); fd.append('action','vesho_worker_clock_in'); fd.append('nonce',NONCE);
      if(lat!==null){ fd.append('lat',lat); fd.append('lng',lng); }
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){ showMsg(true,d.data.message||'Sisse löödud!'); setTimeout(function(){window.location.reload();},800); }
        else { showMsg(false,(d.data&&d.data.message)||'Viga'); ci.disabled=false; ci.textContent='▶ Alusta tööpäeva'; }
      });
    });
  });

  var co=document.getElementById('vwp-clockout-btn');
  if(co) co.addEventListener('click',function(){
    co.disabled=true; co.textContent='...';
    getGPS(function(lat,lng){
      var fd=new FormData(); fd.append('action','vesho_worker_clock_out'); fd.append('nonce',NONCE); fd.append('entry_id',co.dataset.id);
      if(lat!==null){ fd.append('lat',lat); fd.append('lng',lng); }
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){ showMsg(true,d.data.message||'Lõpetatud!'); setTimeout(function(){window.location.reload();},800); }
        else { showMsg(false,(d.data&&d.data.message)||'Viga'); co.disabled=false; co.textContent='■ Lõpeta tööpäev'; }
      });
    });
  });

  var scanBtn=document.getElementById('vwp-scan-btn');
  if(scanBtn) scanBtn.addEventListener('click',function(){
    if(typeof window.VeshoScanner!=='undefined'){
      window.VeshoScanner.open({title:'Skänni töötaja QR kaart',autoConfirm:true,manualInput:false,
        onScan:function(token){
          var fd=new FormData(); fd.append('action','vesho_worker_scan_checkin'); fd.append('nonce',NONCE); fd.append('token',token);
          fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
            if(d.success){ showMsg(true,d.data.message||'OK'); setTimeout(function(){window.location.reload();},800); }
            else { showMsg(false,(d.data&&d.data.message)||'Vigane QR kood'); }
          });
        }
      });
    } else { alert('Skänner pole laaditud.'); }
  });
})();
</script>
        <?php
    }

    // ── Tab: Work Orders ──────────────────────────────────────────────────────

    private static function tab_orders($wid, $nonce, $ajax) {
        global $wpdb;
        // Load all non-cancelled orders for this worker
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             WHERE wo.worker_id=%d AND wo.status != 'cancelled'
             ORDER BY wo.scheduled_date ASC, wo.created_at DESC LIMIT 100", $wid
        ));
        ?>
<!-- Filter tabs (matching 3006) -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h2 class="vwp-section-title" style="margin:0">Minu töökäsud</h2>
  <div style="display:flex;gap:4px;background:#f1f5f9;border-radius:8px;padding:3px">
    <?php
    $filters = ['all'=>'Kõik','pending'=>'Ootel','in_progress'=>'Pooleli','completed'=>'Lõpetatud'];
    foreach ($filters as $fval => $flabel):
    ?>
    <button class="vwp-filter-btn <?php echo $fval==='all'?'active':''; ?>"
            data-filter="<?php echo $fval; ?>"
            style="padding:5px 12px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;background:<?php echo $fval==='all'?'#fff':'transparent'; ?>;color:<?php echo $fval==='all'?'#1e293b':'#64748b'; ?>;box-shadow:<?php echo $fval==='all'?'0 1px 3px rgba(0,0,0,.1)':'none'; ?>">
      <?php echo $flabel; ?>
    </button>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($orders)): ?>
  <div class="vwp-empty">Töökäske pole.</div>
<?php else: ?>
<div id="vwp-orders-list" style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($orders as $wo):
  $pc = ['urgent'=>'#ef4444','high'=>'#f59e0b','normal'=>'#00b4c8','low'=>'#94a3b8'][$wo->priority ?? 'normal'] ?? '#94a3b8';
  $mats = $wo->materials_used ? json_decode($wo->materials_used, true) : [];
?>
<div class="vwp-order-card" data-status="<?php echo esc_attr($wo->status); ?>" style="border-left:4px solid <?php echo $pc; ?>">
  <div class="vwp-order-header">
    <div>
      <strong><?php echo esc_html($wo->title); ?></strong>
      <?php echo self::status_badge($wo->status); ?>
      <?php if ($wo->priority): ?>
        <span class="vwp-priority" style="background:<?php echo $pc; ?>"><?php echo esc_html(ucfirst($wo->priority)); ?></span>
      <?php endif; ?>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <span class="vwp-order-meta"><?php echo esc_html($wo->scheduled_date ? date('d.m.Y', strtotime($wo->scheduled_date)) : '—'); ?></span>
      <button class="vwp-btn-outline vwp-detail-btn"
              data-id="<?php echo $wo->id; ?>"
              data-title="<?php echo esc_attr($wo->title); ?>"
              data-client="<?php echo esc_attr($wo->client_name ?? '—'); ?>"
              data-service="<?php echo esc_attr($wo->service_type ?? '—'); ?>"
              data-status="<?php echo esc_attr($wo->status); ?>"
              data-date="<?php echo esc_attr($wo->scheduled_date ? date('d.m.Y', strtotime($wo->scheduled_date)) : '—'); ?>"
              data-priority="<?php echo esc_attr($wo->priority ?? '—'); ?>"
              data-desc="<?php echo esc_attr($wo->description ?? ''); ?>"
              data-notes="<?php echo esc_attr($wo->notes ?? ''); ?>"
              style="padding:4px 10px;font-size:12px">&#128065;</button>
    </div>
  </div>
  <div class="vwp-order-body">
    <div style="font-size:13px;color:#64748b;margin-bottom:8px"><strong style="color:#1e293b">Klient:</strong> <?php echo esc_html($wo->client_name ?? '—'); ?></div>
    <?php if ($wo->service_type): ?>
    <div style="font-size:13px;color:#64748b;margin-bottom:8px"><strong style="color:#1e293b">Töö liik:</strong> <?php echo esc_html($wo->service_type); ?></div>
    <?php endif; ?>
    <?php if ($wo->description): ?><p class="vwp-desc"><?php echo esc_html($wo->description); ?></p><?php endif; ?>

    <?php if ($wo->status === 'in_progress'): ?>
    <div class="vwp-materials-panel" style="padding:12px 0 4px">
      <div style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;margin-bottom:8px">Kasutatud materjalid</div>
      <div id="mats-<?php echo $wo->id; ?>">
      <?php if (!empty($mats)): foreach ($mats as $m): ?>
        <div class="vwp-mat-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;font-size:13px">
          <span style="flex:1"><?php echo esc_html(($m['source']??'')==='store'?'🛒 ':'').$m['name']; ?></span>
          <span style="color:#64748b"><?php echo esc_html($m['qty']); ?> <?php echo esc_html($m['unit']??'tk'); ?></span>
          <span style="color:#0369a1"><?php echo number_format($m['price']??0,2,',','.'); ?> €/<?php echo esc_html($m['unit']??'tk'); ?></span>
        </div>
      <?php endforeach; endif; ?>
      </div>
      <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
        <select id="mat-inv-<?php echo $wo->id; ?>" class="vwp-input" style="flex:1;min-width:140px;font-size:12px;padding:6px 8px">
          <option value="">+ Lisa laomaterjal...</option>
        </select>
        <input type="number" id="mat-qty-<?php echo $wo->id; ?>" class="vwp-input" placeholder="Kogus" min="0.001" step="0.001" style="width:70px;font-size:12px;padding:6px 8px" value="1">
        <button class="vwp-btn-outline" style="font-size:12px;padding:6px 10px"
                onclick="addMaterial(<?php echo $wo->id; ?>,'<?php echo $nonce; ?>')">Lisa</button>
      </div>
      <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;align-items:center">
        <span style="font-size:11px;color:#64748b;font-weight:600">🛒 Poest (KM-ta):</span>
        <input type="text" id="store-name-<?php echo $wo->id; ?>" class="vwp-input" placeholder="Nimi" style="flex:1;min-width:100px;font-size:12px;padding:6px 8px">
        <input type="number" id="store-qty-<?php echo $wo->id; ?>" class="vwp-input" placeholder="Kogus" min="0.001" step="0.001" style="width:65px;font-size:12px;padding:6px 8px" value="1">
        <input type="number" id="store-price-<?php echo $wo->id; ?>" class="vwp-input" placeholder="Hind€" min="0" step="0.01" style="width:70px;font-size:12px;padding:6px 8px">
        <button class="vwp-btn-outline" style="font-size:12px;padding:6px 10px;border-color:#f59e0b;color:#b45309"
                onclick="addStoreMaterial(<?php echo $wo->id; ?>,'<?php echo $nonce; ?>')">🛒 Lisa</button>
      </div>
    </div>
    <?php endif; ?>

    <div class="vwp-order-actions">
      <?php if ($wo->status === 'pending' || $wo->status === 'assigned'): ?>
      <button class="vwp-btn-outline vwp-start-btn" data-id="<?php echo $wo->id; ?>" data-nonce="<?php echo $nonce; ?>">&#9654; Alusta</button>
      <?php endif; ?>
      <?php if (!in_array($wo->status, ['completed','cancelled'])): ?>
      <button class="vwp-btn-primary vwp-complete-btn"
              data-id="<?php echo $wo->id; ?>"
              data-nonce="<?php echo $nonce; ?>">&#10003; Lõpeta<?php if ($wo->status==='in_progress') echo ' + Arve'; ?></button>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Detail modal -->
<div id="vwp-detail-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px;max-width:480px;width:90%;max-height:80vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById('vwp-detail-modal').style.display='none'"
            style="position:absolute;top:12px;right:12px;background:none;border:none;font-size:18px;cursor:pointer;color:#64748b">&#10005;</button>
    <h3 id="vwp-dm-title" style="margin:0 0 20px;font-size:17px;font-weight:700;padding-right:32px"></h3>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b;width:40%">Klient</td><td id="vwp-dm-client" style="padding:8px 0;font-weight:600"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Töö liik</td><td id="vwp-dm-service" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Staatus</td><td id="vwp-dm-status" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Kuupäev</td><td id="vwp-dm-date" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Prioriteet</td><td id="vwp-dm-priority" style="padding:8px 0"></td></tr>
    </table>
    <div id="vwp-dm-desc-wrap" style="margin-top:14px;display:none">
      <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-bottom:6px">Kirjeldus</div>
      <p id="vwp-dm-desc" style="margin:0;font-size:13px;color:#1e293b;line-height:1.6"></p>
    </div>
    <div id="vwp-dm-notes-wrap" style="margin-top:12px;display:none">
      <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-bottom:6px">Märkused</div>
      <p id="vwp-dm-notes" style="margin:0;font-size:13px;color:#1e293b;line-height:1.6"></p>
    </div>
  </div>
</div>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  var NONCE='<?php echo $nonce; ?>';

  // Filter tabs
  document.querySelectorAll('.vwp-filter-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      document.querySelectorAll('.vwp-filter-btn').forEach(function(b){
        b.classList.remove('active');
        b.style.background='transparent'; b.style.color='#64748b'; b.style.boxShadow='none';
      });
      btn.classList.add('active');
      btn.style.background='#fff'; btn.style.color='#1e293b'; btn.style.boxShadow='0 1px 3px rgba(0,0,0,.1)';
      var f=btn.dataset.filter;
      document.querySelectorAll('.vwp-order-card').forEach(function(card){
        card.style.display=(f==='all'||card.dataset.status===f)?'':'none';
      });
    });
  });

  // Detail modal
  document.querySelectorAll('.vwp-detail-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      var d=btn.dataset;
      document.getElementById('vwp-dm-title').textContent=d.title;
      document.getElementById('vwp-dm-client').textContent=d.client;
      document.getElementById('vwp-dm-service').textContent=d.service;
      document.getElementById('vwp-dm-date').textContent=d.date;
      document.getElementById('vwp-dm-priority').textContent=d.priority;
      // Status badge
      var statusMap={pending:'Ootel',assigned:'Määratud',in_progress:'Pooleli',completed:'Lõpetatud',cancelled:'Tühistatud'};
      document.getElementById('vwp-dm-status').textContent=statusMap[d.status]||d.status;
      // Description
      var dw=document.getElementById('vwp-dm-desc-wrap');
      if(d.desc){dw.style.display='';document.getElementById('vwp-dm-desc').textContent=d.desc;}
      else dw.style.display='none';
      // Notes
      var nw=document.getElementById('vwp-dm-notes-wrap');
      if(d.notes){nw.style.display='';document.getElementById('vwp-dm-notes').textContent=d.notes;}
      else nw.style.display='none';
      document.getElementById('vwp-detail-modal').style.display='flex';
    });
  });
  document.getElementById('vwp-detail-modal').addEventListener('click',function(e){
    if(e.target===this) this.style.display='none';
  });

  // Inventory dropdown loader
  var invCache=null;
  function loadInventory(cb){
    if(invCache){cb(invCache);return;}
    var fd=new FormData(); fd.append('action','vesho_worker_get_inventory'); fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      invCache=(d.success&&d.data.items)||[]; cb(invCache);
    });
  }
  document.querySelectorAll('[id^="mat-inv-"]').forEach(function(sel){
    loadInventory(function(items){
      items.forEach(function(item){
        var o=document.createElement('option');
        o.value=JSON.stringify({id:item.id,name:item.name,unit:item.unit,price:item.sell_price});
        o.textContent=item.name+' ('+item.quantity+' '+item.unit+')';
        sel.appendChild(o);
      });
    });
  });

  window.addStoreMaterial=function(oid,nonce){
    var nameEl=document.getElementById('store-name-'+oid);
    var qtyEl=document.getElementById('store-qty-'+oid);
    var priceEl=document.getElementById('store-price-'+oid);
    if(!nameEl||!nameEl.value.trim()){alert('Sisesta materjali nimi');return;}
    var name=nameEl.value.trim(),qty=parseFloat(qtyEl.value)||1,price=parseFloat(priceEl.value)||0;
    var fd=new FormData();
    fd.append('action','vesho_worker_save_store_material'); fd.append('nonce',nonce);
    fd.append('order_id',oid); fd.append('name',name); fd.append('qty',qty); fd.append('price',price);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){
        var div=document.getElementById('mats-'+oid);
        if(div){var row=document.createElement('div');row.className='vwp-mat-row';row.style='display:flex;gap:8px;align-items:center;margin-bottom:6px;font-size:13px';
          row.innerHTML='<span style="flex:1">🛒 '+name+'</span><span style="color:#64748b">'+qty+' tk</span><span style="color:#b45309">'+price.toFixed(2).replace('.',',')+' € (KM-ta)</span>';
          div.appendChild(row);}
        nameEl.value=''; qtyEl.value=1; priceEl.value='';
      } else { alert((d.data&&d.data.message)||'Viga'); }
    });
  };

  window.addMaterial=function(oid,nonce){
    var sel=document.getElementById('mat-inv-'+oid);
    var qtyEl=document.getElementById('mat-qty-'+oid);
    if(!sel||!sel.value) return;
    var mat=JSON.parse(sel.value),qty=parseFloat(qtyEl.value)||1;
    var fd=new FormData();
    fd.append('action','vesho_worker_save_materials'); fd.append('nonce',nonce);
    fd.append('order_id',oid); fd.append('material_id',mat.id); fd.append('material_name',mat.name);
    fd.append('material_unit',mat.unit); fd.append('material_price',mat.price); fd.append('qty',qty);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){
        var div=document.getElementById('mats-'+oid);
        if(div){var row=document.createElement('div');row.className='vwp-mat-row';row.style='display:flex;gap:8px;align-items:center;margin-bottom:6px;font-size:13px';
          row.innerHTML='<span style="flex:1">'+mat.name+'</span><span style="color:#64748b">'+qty+' '+mat.unit+'</span><span style="color:#0369a1">'+parseFloat(mat.price).toFixed(2).replace('.',',')+' €/'+mat.unit+'</span>';
          div.appendChild(row);}
        qtyEl.value=1; sel.value='';
      } else { alert((d.data&&d.data.message)||'Viga'); }
    });
  };

  document.querySelectorAll('.vwp-start-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!confirm('Alusta töökäsku?')) return;
      var fd=new FormData(); fd.append('action','vesho_worker_start_order'); fd.append('nonce',btn.dataset.nonce); fd.append('order_id',btn.dataset.id);
      btn.disabled=true;
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){ location.reload(); } else { alert((d.data&&d.data.message)||'Viga'); btn.disabled=false; }
      });
    });
  });

  document.querySelectorAll('.vwp-complete-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      var txt=btn.textContent;
      var msg=txt.indexOf('Arve')>=0?'Lõpeta töökäsk ja genereeri arve automaatselt?':'Märgi töökäsk lõpetatuks?';
      if(!confirm(msg)) return;
      var fd=new FormData(); fd.append('action','vesho_worker_complete_order'); fd.append('nonce',btn.dataset.nonce); fd.append('order_id',btn.dataset.id); fd.append('auto_invoice','1');
      btn.disabled=true;
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){
          var card=btn.closest('.vwp-order-card'); if(card) card.dataset.status='completed';
          btn.textContent='✓ Lõpetatud'+(d.data&&d.data.invoice_number?' · Arve '+d.data.invoice_number:'');
          btn.disabled=true;
        } else { alert((d.data&&d.data.message)||'Viga'); btn.disabled=false; }
      });
    });
  });
})();
</script>
        <?php
    }

    // ── Tab: Aktiivsed ───────────────────────────────────────────────────────

    private static function tab_active($wid, $nonce, $ajax) {
        global $wpdb;
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name, c.address as client_address, d.name as device_name
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=wo.device_id
             WHERE wo.worker_id=%d AND wo.status IN ('open','assigned','in_progress')
             ORDER BY wo.scheduled_date ASC, wo.created_at DESC LIMIT 50", $wid
        ));
        $upload_nonce = wp_create_nonce('vesho_worker_nonce');
        ?>
<h2 class="vwp-section-title">Aktiivsed töökäsud</h2>
<?php if (empty($orders)): ?>
  <div class="vwp-empty">Aktiivseid töökäske pole.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($orders as $wo):
  $pc = ['urgent'=>'#ef4444','high'=>'#f59e0b','normal'=>'#00b4c8','low'=>'#94a3b8'][$wo->priority??'normal']??'#94a3b8';
  $mats = $wo->materials_used ? json_decode($wo->materials_used, true) : [];
  $photos = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d ORDER BY created_at ASC", $wo->id
  ));
  $photo_count = count($photos);
?>
<div class="vwp-order-card" style="border-left:4px solid <?php echo $pc; ?>">
  <div class="vwp-order-header">
    <div>
      <strong><?php echo esc_html($wo->title); ?></strong>
      <?php echo self::status_badge($wo->status); ?>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <span class="vwp-order-meta"><?php echo esc_html($wo->scheduled_date ? date('d.m.Y',strtotime($wo->scheduled_date)) : '—'); ?></span>
      <?php if (!empty($wo->client_address)): ?>
      <a href="https://waze.com/ul?q=<?php echo urlencode($wo->client_address); ?>&navigate=yes"
         target="_blank" rel="noopener"
         class="vwp-btn-outline" style="font-size:12px;padding:4px 10px;text-decoration:none">🗺</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="vwp-order-body">
    <div style="font-size:13px;color:#64748b;margin-bottom:6px">
      <strong style="color:#1e293b">Klient:</strong> <?php echo esc_html($wo->client_name??'—'); ?>
      <?php if ($wo->device_name): ?> &nbsp;·&nbsp; <strong style="color:#1e293b">Seade:</strong> <?php echo esc_html($wo->device_name); ?><?php endif; ?>
      <?php if (!empty($wo->client_address)): ?><br><span style="font-size:12px">📍 <?php echo esc_html($wo->client_address); ?></span><?php endif; ?>
    </div>
    <?php if ($wo->description): ?><p class="vwp-desc"><?php echo esc_html($wo->description); ?></p><?php endif; ?>

    <!-- Materials panel -->
    <?php if ($wo->status==='in_progress'): ?>
    <div style="margin-top:8px;padding:10px 0 4px;border-top:1px solid #f1f5f9">
      <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;margin-bottom:8px">Kasutatud materjalid</div>
      <div id="mats-a-<?php echo $wo->id; ?>">
      <?php foreach ($mats as $m): ?>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:5px;font-size:13px">
          <span style="flex:1"><?php echo esc_html(($m['source']??'')==='store'?'🛒 ':'').$m['name']; ?></span>
          <span style="color:#64748b"><?php echo $m['qty']; ?> <?php echo esc_html($m['unit']??'tk'); ?></span>
        </div>
      <?php endforeach; ?>
      </div>
      <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap">
        <select id="mat-inv-a-<?php echo $wo->id; ?>" class="vwp-input" style="flex:1;min-width:140px;font-size:12px;padding:6px 8px"><option value="">+ Lisa laomaterjal...</option></select>
        <input type="number" id="mat-qty-a-<?php echo $wo->id; ?>" class="vwp-input" value="1" min="0.001" step="0.001" style="width:70px;font-size:12px;padding:6px 8px">
        <button class="vwp-btn-outline" style="font-size:12px;padding:6px 10px" onclick="addMat(<?php echo $wo->id; ?>,'a','<?php echo $nonce; ?>')">Lisa</button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="vwp-order-actions" style="margin-top:10px">
      <?php if (in_array($wo->status,['open','assigned'])): ?>
      <button class="vwp-btn-outline vwp-start-btn2" data-id="<?php echo $wo->id; ?>" data-nonce="<?php echo $nonce; ?>">&#9654; Alusta</button>
      <?php endif; ?>
      <button class="vwp-btn-primary vwp-complete-toggle" data-id="<?php echo $wo->id; ?>">&#10003; Lõpeta</button>
    </div>

    <!-- Inline completion form -->
    <div id="vwp-complete-form-<?php echo $wo->id; ?>" style="display:none;margin-top:12px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0">
      <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:6px">Märkused (valikuline)</div>
      <textarea id="vwp-notes-<?php echo $wo->id; ?>" class="vwp-input" rows="3" placeholder="Töö märkused..." style="width:100%;font-size:13px;margin-bottom:8px"></textarea>
      <div style="display:flex;gap:8px">
        <button class="vwp-btn-primary vwp-complete-btn2" data-id="<?php echo $wo->id; ?>" data-nonce="<?php echo $nonce; ?>" style="font-size:13px">✓ Kinnita lõpetamine</button>
        <button class="vwp-btn-outline vwp-complete-cancel" data-id="<?php echo $wo->id; ?>" style="font-size:13px">Tühista</button>
      </div>
    </div>

    <!-- Photos -->
    <div class="vwp-photos-wrap" style="margin-top:12px">
      <?php if ($photos): ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px" id="vwp-photos-<?php echo $wo->id; ?>">
        <?php foreach ($photos as $p): ?>
        <div style="position:relative" data-photo-wrap="<?php echo $p->id; ?>">
          <a href="<?php echo esc_url($p->filename); ?>" target="_blank">
            <img src="<?php echo esc_url($p->filename); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0">
          </a>
          <button class="vwp-photo-delete" data-photo-id="<?php echo $p->id; ?>" data-nonce="<?php echo $nonce; ?>"
                  style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;border-radius:50%;background:#ef4444;border:none;color:#fff;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($photo_count < 5): ?>
      <label class="vwp-btn-outline" style="cursor:pointer;font-size:12px;padding:6px 12px;display:inline-flex;align-items:center;gap:6px">
        📷 Lisa foto (<?php echo $photo_count; ?>/5)
        <input type="file" accept="image/*" capture="environment" style="display:none"
               onchange="vwpUploadPhoto(this, <?php echo $wo->id; ?>)">
      </label>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<script>
(function(){
  var AJAX='<?php echo $ajax; ?>',NONCE='<?php echo $nonce; ?>';
  var UPLOAD_NONCE='<?php echo $upload_nonce; ?>';

  window.vwpUploadPhoto=function(input,woid){
    if(!input.files[0])return;
    var fd=new FormData();
    fd.append('action','vesho_upload_workorder_photo');
    fd.append('nonce',UPLOAD_NONCE);
    fd.append('workorder_id',woid);
    fd.append('photo',input.files[0]);
    fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>',{method:'POST',body:fd})
      .then(r=>r.json()).then(d=>{if(d.success){location.reload();}else{alert(d.data.message||'Viga foto üleslaadimisel');}});
  };

  // Photo delete
  document.querySelectorAll('.vwp-photo-delete').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      if(!confirm('Kustuta foto?'))return;
      var fd=new FormData();
      fd.append('action','vesho_worker_delete_photo');
      fd.append('nonce',btn.dataset.nonce);
      fd.append('photo_id',btn.dataset.photoId);
      fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){
          var wrap=btn.closest('[data-photo-wrap]');
          if(wrap) wrap.remove();
        } else alert(d.data?.message||'Viga');
      });
    });
  });

  // Inventory loader
  var invCache=null;
  function loadInv(cb){if(invCache){cb(invCache);return;}var fd=new FormData();fd.append('action','vesho_worker_get_inventory');fd.append('nonce',NONCE);fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{invCache=(d.success&&d.data.items)||[];cb(invCache);});}
  document.querySelectorAll('[id^="mat-inv-a-"]').forEach(sel=>{loadInv(items=>{items.forEach(item=>{var o=document.createElement('option');o.value=JSON.stringify({id:item.id,name:item.name,unit:item.unit,price:item.sell_price});o.textContent=item.name+' ('+item.quantity+' '+item.unit+')';sel.appendChild(o);});});});

  window.addMat=function(oid,pfx,nonce){
    var sel=document.getElementById('mat-inv-'+pfx+'-'+oid);
    var qEl=document.getElementById('mat-qty-'+pfx+'-'+oid);
    if(!sel||!sel.value)return;
    var mat=JSON.parse(sel.value),qty=parseFloat(qEl.value)||1;
    var fd=new FormData();
    fd.append('action','vesho_worker_save_materials');fd.append('nonce',nonce);fd.append('order_id',oid);
    fd.append('material_id',mat.id);fd.append('material_name',mat.name);fd.append('material_unit',mat.unit);
    fd.append('material_price',mat.price);fd.append('qty',qty);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){
        var div=document.getElementById('mats-'+pfx+'-'+oid);
        if(div){var row=document.createElement('div');row.style='display:flex;gap:8px;align-items:center;margin-bottom:5px;font-size:13px';row.innerHTML='<span style="flex:1">'+mat.name+'</span><span style="color:#64748b">'+qty+' '+mat.unit+'</span>';div.appendChild(row);}
        qEl.value=1;sel.value='';
      }else alert(d.data?.message||'Viga');
    });
  };

  // Start order
  document.querySelectorAll('.vwp-start-btn2').forEach(btn=>{btn.addEventListener('click',()=>{
    if(!confirm('Alusta töökäsku?'))return;
    var fd=new FormData();fd.append('action','vesho_worker_start_order');fd.append('nonce',btn.dataset.nonce);fd.append('order_id',btn.dataset.id);
    btn.disabled=true;
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();else{alert(d.data?.message||'Viga');btn.disabled=false;}});
  });});

  // Toggle completion form
  document.querySelectorAll('.vwp-complete-toggle').forEach(btn=>{btn.addEventListener('click',function(){
    var form=document.getElementById('vwp-complete-form-'+btn.dataset.id);
    if(form) form.style.display = form.style.display==='none'?'block':'none';
  });});

  document.querySelectorAll('.vwp-complete-cancel').forEach(btn=>{btn.addEventListener('click',function(){
    var form=document.getElementById('vwp-complete-form-'+btn.dataset.id);
    if(form) form.style.display='none';
  });});

  // Complete order with inline notes
  document.querySelectorAll('.vwp-complete-btn2').forEach(btn=>{btn.addEventListener('click',()=>{
    var notesEl=document.getElementById('vwp-notes-'+btn.dataset.id);
    var notes=notesEl?notesEl.value.trim():'';
    var fd=new FormData();
    fd.append('action','vesho_worker_complete_order');fd.append('nonce',btn.dataset.nonce);
    fd.append('order_id',btn.dataset.id);fd.append('auto_invoice','1');
    if(notes) fd.append('notes',notes);
    btn.disabled=true;
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){btn.closest('.vwp-order-card').style.opacity='.4';btn.textContent='✓ Lõpetatud';}
      else{alert(d.data?.message||'Viga');btn.disabled=false;}
    });
  });});
})();
</script>
        <?php
    }

    // ── Tab: Ajalugu ──────────────────────────────────────────────────────────

    private static function tab_history($wid) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name, d.name as device_name,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, wh.start_time, IFNULL(wh.end_time, wh.start_time))), 0) as duration_mins
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=wo.device_id
             LEFT JOIN {$wpdb->prefix}vesho_work_hours wh ON wh.workorder_id=wo.id AND wh.worker_id=%d
             WHERE wo.worker_id=%d AND wo.status='completed'
             GROUP BY wo.id
             ORDER BY wo.completed_date DESC, wo.created_at DESC LIMIT 60", $wid, $wid
        ));
        ?>
<h2 class="vwp-section-title">Lõpetatud töökäsud</h2>
<?php if (empty($rows)): ?>
  <div class="vwp-empty">Lõpetatud töökäske pole.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px">
<?php foreach ($rows as $wo):
  $dur = (int)$wo->duration_mins;
  $dur_str = $dur > 0 ? ($dur < 60 ? "{$dur} min" : intdiv($dur,60).'h '.($dur%60?($dur%60).'min':'')) : '—';
?>
<div class="vwp-card" style="padding:0;overflow:hidden">
  <div class="vwp-hist-header" data-id="<?php echo $wo->id; ?>"
       style="display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;user-select:none">
    <div style="flex:1;min-width:0">
      <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo esc_html($wo->title); ?></div>
      <div style="font-size:12px;color:#64748b;margin-top:2px"><?php echo esc_html($wo->client_name??'—'); ?>
        <?php if ($wo->device_name): ?> · <?php echo esc_html($wo->device_name); ?><?php endif; ?>
      </div>
    </div>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-size:12px;color:#64748b"><?php echo esc_html($wo->completed_date ? date('d.m.Y',strtotime($wo->completed_date)) : '—'); ?></div>
      <?php if ($dur > 0): ?><div style="font-size:11px;font-weight:600;color:#00b4c8;margin-top:2px">⏱ <?php echo $dur_str; ?></div><?php endif; ?>
    </div>
    <div class="vwp-hist-chevron" style="font-size:11px;color:#94a3b8;transition:transform 0.2s;flex-shrink:0">▼</div>
  </div>
  <div class="vwp-hist-body" id="vwp-hist-<?php echo $wo->id; ?>" style="display:none;border-top:1px solid #f1f5f9;padding:12px 16px">
    <?php if ($wo->service_type): ?><div style="font-size:12px;color:#64748b;margin-bottom:6px">🔧 <?php echo esc_html($wo->service_type); ?></div><?php endif; ?>
    <?php if ($wo->description): ?><p class="vwp-desc" style="margin-bottom:8px"><?php echo esc_html($wo->description); ?></p><?php endif; ?>
    <?php if ($wo->notes): ?><div style="padding:8px 12px;background:#f8fafc;border-radius:8px;font-size:12px;color:#475569;border-left:3px solid #00b4c8">💬 <?php echo esc_html($wo->notes); ?></div><?php endif; ?>
    <?php
    $photos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d ORDER BY created_at ASC", $wo->id
    ));
    if ($photos): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
      <?php foreach ($photos as $p): ?>
      <a href="<?php echo esc_url($p->filename); ?>" target="_blank">
        <img src="<?php echo esc_url($p->filename); ?>" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0">
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>
<script>
document.querySelectorAll('.vwp-hist-header').forEach(function(hdr){
  hdr.addEventListener('click',function(){
    var body=document.getElementById('vwp-hist-'+hdr.dataset.id);
    var ch=hdr.querySelector('.vwp-hist-chevron');
    if(!body)return;
    var open=body.style.display!=='none';
    body.style.display=open?'none':'block';
    if(ch) ch.style.transform=open?'':'rotate(180deg)';
  });
});
</script>
<?php endif; ?>
        <?php
    }

    // ── Tab: Graafik ──────────────────────────────────────────────────────────

    private static function tab_schedule($wid, $ajax) {
        global $wpdb;
        $today = current_time('Y-m-d');
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name, c.address as client_address, d.name as device_name
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=wo.device_id
             WHERE wo.worker_id=%d AND wo.scheduled_date >= %s AND wo.status NOT IN ('completed','cancelled')
             ORDER BY wo.scheduled_date ASC, wo.created_at ASC LIMIT 50", $wid, $today
        ));
        // Group by date
        $grouped = [];
        foreach ($rows as $wo) {
            $grouped[$wo->scheduled_date][] = $wo;
        }
        $days_et = ['Pühapäev','Esmaspäev','Teisipäev','Kolmapäev','Neljapäev','Reede','Laupäev'];
        $months_et = ['','jaan','veebr','märts','apr','mai','juuni','juuli','aug','sept','okt','nov','dets'];
        ?>
<h2 class="vwp-section-title">Töögraafik</h2>
<?php if (empty($grouped)): ?>
  <div class="vwp-empty">Planeeritud töid pole.</div>
<?php else: ?>
<?php foreach ($grouped as $date => $items):
  $ts = strtotime($date);
  $dow = (int)date('w', $ts);
  $day_label = $date === $today ? 'Täna' : ($date === date('Y-m-d', strtotime('+1 day', strtotime($today))) ? 'Homme' : $days_et[$dow]);
  $day_color = $date === $today ? '#ef4444' : ($date === date('Y-m-d', strtotime('+1 day', strtotime($today))) ? '#f59e0b' : '#00b4c8');
  $day_str = (int)date('j',$ts).' '.$months_et[(int)date('n',$ts)];
?>
<div style="margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
    <span style="font-weight:700;font-size:14px;color:<?php echo $day_color; ?>"><?php echo $day_label; ?></span>
    <span style="font-size:12px;color:#94a3b8"><?php echo $day_str; ?></span>
    <span style="background:#f1f5f9;border-radius:999px;font-size:11px;font-weight:600;color:#64748b;padding:2px 8px"><?php echo count($items); ?></span>
  </div>
  <div style="display:flex;flex-direction:column;gap:8px">
  <?php foreach ($items as $wo): ?>
  <div class="vwp-card" style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
      <div style="font-weight:600;font-size:13px;margin-bottom:2px"><?php echo esc_html($wo->title); ?></div>
      <div style="font-size:12px;color:#64748b"><?php echo esc_html($wo->client_name??'—'); ?>
        <?php if ($wo->device_name): ?> · <?php echo esc_html($wo->device_name); ?><?php endif; ?>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <?php echo self::status_badge($wo->status); ?>
      <?php if (!empty($wo->client_address)): ?>
      <a href="https://waze.com/ul?q=<?php echo urlencode($wo->client_address); ?>&navigate=yes"
         target="_blank" rel="noopener"
         class="vwp-btn-outline" style="font-size:12px;padding:5px 10px;text-decoration:none">
        🗺 Waze
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
        <?php
    }

    // ── Tab: Inventuur ────────────────────────────────────────────────────────

    private static function tab_inventuur($wid, $nonce, $ajax) {
        global $wpdb;

        // Get sections assigned to this worker (or unassigned) in active stock counts
        $sections = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM {$wpdb->prefix}vesho_stock_count_sections s
             JOIN {$wpdb->prefix}vesho_stock_counts sc ON sc.id = s.stock_count_id
             WHERE sc.status = 'active' AND (s.worker_id = %d OR s.worker_id IS NULL)",
            $wid
        ));
        $section_ids = wp_list_pluck($sections, 'id');

        // Get items only from those sections
        if (!empty($section_ids)) {
            $placeholders = implode(',', array_fill(0, count($section_ids), '%d'));
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT sci.*, inv.name, inv.sku, inv.ean, inv.unit, inv.category, inv.location
                 FROM {$wpdb->prefix}vesho_stock_count_items sci
                 JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id = sci.inventory_id
                 WHERE sci.section_id IN ($placeholders)
                 ORDER BY inv.category, inv.name",
                ...$section_ids
            ));
        } else {
            $items = [];
        }

        // Group items by section_id
        $items_by_section = [];
        foreach ($items as $item) {
            $items_by_section[$item->section_id][] = $item;
        }

        // Build section lookup keyed by id
        $section_map = [];
        foreach ($sections as $s) {
            $section_map[$s->id] = $s;
        }

        // Get active stock counts for the overview list (worker-filtered)
        $active_count_ids = array_unique(wp_list_pluck($sections, 'stock_count_id'));
        if (!empty($active_count_ids)) {
            $id_ph = implode(',', array_map('intval', $active_count_ids));
            $counts = $wpdb->get_results(
                "SELECT sc.*, COUNT(sci.id) as total_items,
                        SUM(CASE WHEN sci.worker_counted IS NOT NULL THEN 1 ELSE 0 END) as counted_items
                 FROM {$wpdb->prefix}vesho_stock_counts sc
                 LEFT JOIN {$wpdb->prefix}vesho_stock_count_sections scs ON scs.stock_count_id=sc.id AND (scs.worker_id={$wid} OR scs.worker_id IS NULL)
                 LEFT JOIN {$wpdb->prefix}vesho_stock_count_items sci ON sci.section_id=scs.id
                 WHERE sc.id IN ($id_ph)
                 GROUP BY sc.id
                 ORDER BY sc.created_at DESC"
            );
        } else {
            $counts = [];
        }
        ?>
<div id="vwp-inv-list">
<h2 class="vwp-section-title">Inventuur</h2>
<?php if (empty($counts)): ?>
  <div class="vwp-empty">Aktiivseid inventuure pole.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($counts as $sc): ?>
<div class="vwp-card" style="cursor:pointer" onclick="loadInvCount(<?php echo $sc->id; ?>, '<?php echo esc_js($sc->name); ?>')">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <div>
      <strong style="font-size:14px"><?php echo esc_html($sc->name); ?></strong>
      <div style="font-size:12px;color:#64748b;margin-top:2px">
        <?php echo (int)($sc->counted_items??0); ?> / <?php echo (int)($sc->total_items??0); ?> kirjet loendatud
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <?php $pct = $sc->total_items > 0 ? round(($sc->counted_items/$sc->total_items)*100) : 0; ?>
      <div style="font-size:13px;font-weight:700;color:#00b4c8"><?php echo $pct; ?>%</div>
      <span style="color:#94a3b8">&#8250;</span>
    </div>
  </div>
  <?php if ($sc->total_items > 0): ?>
  <div style="background:#e2e8f0;border-radius:999px;height:4px;margin-top:10px;overflow:hidden">
    <div style="background:#00b4c8;height:100%;width:<?php echo $pct; ?>%;transition:width .3s"></div>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- Section view (hidden initially) -->
<div id="vwp-inv-section" style="display:none">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <button onclick="document.getElementById('vwp-inv-list').style.display='';document.getElementById('vwp-inv-section').style.display='none'"
            class="vwp-btn-outline" style="padding:6px 12px;font-size:13px">&#8592; Tagasi</button>
    <div>
      <h2 class="vwp-section-title" style="margin:0" id="vwp-inv-count-title"></h2>
    </div>
  </div>

  <!-- EAN scan bar -->
  <div style="background:#f0f9ff;border:1px solid #00b4c8;border-radius:8px;padding:12px;margin-bottom:16px;display:flex;gap:10px;align-items:center">
    <span>&#128247;</span>
    <input type="text" id="inv-ean-scan" placeholder="Skanni EAN toote leidmiseks..." autocomplete="off"
           style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:16px">
    <button id="inv-camera-btn" type="button" title="Kaamera"
            style="padding:8px 12px;border-radius:6px;border:1px solid #00b4c8;background:#e0f7fa;cursor:pointer;font-size:16px;flex-shrink:0">📷</button>
    <span id="inv-scan-feedback" style="font-size:20px"></span>
  </div>

  <div id="vwp-inv-msg" class="vwp-msg" style="display:none"></div>
  <div id="vwp-inv-items"></div>
</div>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>', NONCE='<?php echo $nonce; ?>';

  // EAN scan handler
  function handleInvEan(ean) {
    ean = (ean||'').trim();
    if (!ean) return;
    var row = document.querySelector('tr[data-ean="'+ean+'"]');
    if (!row) row = document.querySelector('[data-ean="'+ean+'"]');
    if(row){
      row.scrollIntoView({behavior:'smooth', block:'center'});
      row.style.background = '#fff3cd';
      setTimeout(function(){ row.style.background=''; }, 2000);
      var qtyInput = row.querySelector('.count-qty-input');
      if(qtyInput){ qtyInput.focus(); qtyInput.select(); }
      document.getElementById('inv-scan-feedback').textContent = '✅';
    } else {
      document.getElementById('inv-scan-feedback').textContent = '❌';
    }
    setTimeout(function(){ document.getElementById('inv-scan-feedback').textContent=''; }, 2000);
  }
  document.getElementById('inv-ean-scan')?.addEventListener('change', function(){
    handleInvEan(this.value);
    this.value = '';
  });
  document.getElementById('inv-camera-btn')?.addEventListener('click', function(){
    if(typeof window.VeshoScanner==='undefined'){alert('Scanner ei ole saadaval');return;}
    window.VeshoScanner.open({title:'Skänni EAN',autoConfirm:true,manualInput:false,onResult:function(code){handleInvEan(code);}});
  });

  window.loadInvCount = function(id, name) {
    document.getElementById('vwp-inv-list').style.display='none';
    document.getElementById('vwp-inv-section').style.display='';
    document.getElementById('vwp-inv-count-title').textContent=name;
    document.getElementById('vwp-inv-items').innerHTML='<div style="color:#94a3b8;padding:20px;text-align:center">Laen...</div>';
    var fd=new FormData(); fd.append('action','vesho_worker_get_inv_count'); fd.append('nonce',NONCE); fd.append('count_id',id);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(!d.success){document.getElementById('vwp-inv-items').innerHTML='<div style="color:#ef4444">'+( d.data?.message||'Viga')+'</div>';return;}
      var items=d.data.items, html='';
      // Group by section
      var sections={}, sectionOrder=[];
      items.forEach(it=>{
        var sid=it.section_id||'__none__';
        if(!sections[sid]){sections[sid]={name:it.section_name||'Üldine',items:[]};sectionOrder.push(sid);}
        sections[sid].items.push(it);
      });
      sectionOrder.forEach(function(sid){
        var sec=sections[sid];
        var sectionDone = sec.items.every(function(it){ return it.worker_done==1; });
        html+='<div class="inv-section-block" data-section-id="'+sid+'" style="'+(sectionDone?'opacity:.6':'')+'margin-bottom:24px">';
        html+='<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#00b4c8;letter-spacing:.06em;margin:0 0 10px;padding:6px 10px;background:#f0f9ff;border-radius:6px;border-left:3px solid #00b4c8">'+sec.name+'</div>';
        // Group items within section by category
        var catGroups={}, catOrder=[];
        sec.items.forEach(it=>{var cat=it.category||'Muud';if(!catGroups[cat]){catGroups[cat]=[];catOrder.push(cat);}catGroups[cat].push(it);});
        catOrder.forEach(function(cat){
          html+='<div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.05em;margin:12px 0 6px;padding-bottom:4px;border-bottom:1px solid #f1f5f9">'+cat+'</div>';
          catGroups[cat].forEach(it=>{
            var variance=''; var locked=it.worker_done==1;
            if(it.worker_counted!==null&&it.worker_counted!==undefined&&it.worker_counted!==''){
              var diff=parseFloat(it.worker_counted)-parseFloat(it.expected_qty||0);
              if(diff===0) variance='<span style="color:#16a34a;font-weight:700">&#10003;</span>';
              else if(diff>0) variance='<span style="color:#f59e0b;font-weight:700">+'+diff.toFixed(1)+'</span>';
              else variance='<span style="color:#ef4444;font-weight:700">'+diff.toFixed(1)+'</span>';
            } else { variance='<span style="color:#cbd5e1">—</span>'; }
            var ean=it.ean||'';
            html+='<div data-ean="'+ean+'" style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f8fafc;'+(locked?'opacity:.5':'')+'">'+
              '<div style="flex:1;min-width:0">'+
                '<div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+it.name+'</div>'+
                (it.sku?'<div style="font-size:11px;color:#94a3b8;font-family:monospace">'+it.sku+'</div>':'')+
                '<div style="font-size:11px;color:#94a3b8">Oodata: '+it.expected_qty+' '+it.unit+'</div>'+
              '</div>'+
              '<div style="font-size:16px;min-width:24px;text-align:center">'+variance+'</div>'+
              '<input type="number" id="inv-item-'+it.id+'" class="count-qty-input" value="'+(it.worker_counted!==null&&it.worker_counted!==''?it.worker_counted:'')+'"'+
                ' placeholder="0" min="0" step="0.01" style="width:80px;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;text-align:center" '+
                (locked?'disabled':'')+' onchange="saveInvItem('+it.id+',this.value,'+it.section_id+')">'+
              '<button onclick="lockInvItem('+it.id+','+it.section_id+')" style="background:none;border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:12px;cursor:pointer;color:#64748b" '+(locked?'disabled style="opacity:.4"':'')+' title="Lukusta">'+
                (locked?'&#128274;':'&#10003; Lukusta')+
              '</button>'+
            '</div>';
          });
        });
        // "Märgi osa lõpetatuks" button
        html+='<div style="text-align:right;margin-top:10px">'+
          '<button class="complete-section-btn" data-section="'+sid+'" '+
            (sectionDone?'disabled style="background:#95a5a6;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:default"':'style="background:#27ae60;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer"')+'>'+
            (sectionDone?'&#10003; Lõpetatud':'&#10003; Märgi osa lõpetatuks')+
          '</button>'+
        '</div>';
        html+='</div>'; // close section-block
      });
      document.getElementById('vwp-inv-items').innerHTML=html||'<div style="color:#94a3b8;padding:20px;text-align:center">Kirjeid pole.</div>';

      // Attach complete-section listeners
      document.querySelectorAll('.complete-section-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
          var sid = this.dataset.section;
          if(!confirm('Märkida osa lõpetatuks? Peale seda ei saa enam muudatusi teha.')) return;
          var self=this;
          fetch(AJAX, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=vesho_worker_complete_stock_section&nonce='+NONCE+'&section_id='+sid
          }).then(r=>r.json()).then(d=>{
            if(d.success){
              var block = document.querySelector('.inv-section-block[data-section-id="'+sid+'"]');
              if(block){
                block.style.opacity='0.6';
                self.disabled=true;
                self.textContent='&#10003; Lõpetatud';
                self.style.background='#95a5a6';
                self.style.cursor='default';
              }
            }
          });
        });
      });
    });
  };

  window.saveInvItem = function(itemId, val, sectionId) {
    var fd=new FormData(); fd.append('action','vesho_worker_save_count_item'); fd.append('nonce',NONCE);
    fd.append('item_id',itemId); fd.append('counted',val); fd.append('section_id',sectionId);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json());
  };

  window.lockInvItem = function(itemId, sectionId) {
    var inp=document.getElementById('inv-item-'+itemId);
    if(!inp||inp.value===''){alert('Sisesta kogus enne lukustamist');return;}
    saveInvItem(itemId, inp.value, sectionId);
    var fd=new FormData(); fd.append('action','vesho_worker_lock_count_item'); fd.append('nonce',NONCE);
    fd.append('item_id',itemId);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){inp.disabled=true;var btn=inp.nextElementSibling;if(btn){btn.innerHTML='&#128274;';btn.disabled=true;btn.style.opacity='.4';}}
    });
  };
})();
</script>
        <?php
    }

    // ── Tab: Vastuvõtt ────────────────────────────────────────────────────────

    private static function tab_vastuvott($wid, $nonce, $ajax) {
        global $wpdb;
        $pending = $wpdb->get_results($wpdb->prepare(
            "SELECT sr.*, COUNT(sri.id) as item_count
             FROM {$wpdb->prefix}vesho_stock_receipts sr
             LEFT JOIN {$wpdb->prefix}vesho_stock_receipt_items sri ON sri.receipt_id=sr.id
             WHERE sr.worker_id=%d AND sr.status IN ('pending','received')
             GROUP BY sr.id ORDER BY sr.created_at DESC LIMIT 30", $wid
        ));
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT sr.*, COUNT(sri.id) as item_count
             FROM {$wpdb->prefix}vesho_stock_receipts sr
             LEFT JOIN {$wpdb->prefix}vesho_stock_receipt_items sri ON sri.receipt_id=sr.id
             WHERE sr.worker_id=%d AND sr.status IN ('approved','rejected','received')
             GROUP BY sr.id ORDER BY sr.created_at DESC LIMIT 20", $wid
        ));

        // ── Load ALL items for pending receipts upfront (3006 style – no lazy AJAX) ──
        $pending_ids     = array_map(fn($r) => (int)$r->id, $pending ?: []);
        $preloaded_items = [];
        if (!empty($pending_ids)) {
            // Safe column check — avoid query failure if migrations haven't run
            $sri_cols    = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_stock_receipt_items`") ?: [];
            $has         = fn($c) => in_array($c, $sri_cols, true);
            $name_sel    = $has('product_name') ? "COALESCE(inv.name, sri.product_name, '')" : "COALESCE(inv.name, '')";
            $sku_sel     = $has('product_sku')  ? "COALESCE(inv.sku,  sri.product_sku,  '')" : "COALESCE(inv.sku, '')";
            $unit_sel    = $has('product_unit') ? "COALESCE(inv.unit, sri.product_unit, 'tk')" : "COALESCE(inv.unit, 'tk')";
            $ean_sel     = $has('ean')          ? "COALESCE(inv.ean,  sri.ean, '')"           : "COALESCE(inv.ean, '')";
            $actual_sel  = $has('actual_qty')   ? 'sri.actual_qty'   : 'NULL';
            $loc_sel     = $has('location')     ? 'sri.location'     : "''";
            $sell_sel    = $has('selling_price') ? 'sri.selling_price' : 'NULL';

            $placeholders = implode(',', array_fill(0, count($pending_ids), '%d'));
            // Check inventory table columns too
            $inv_cols   = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_inventory`") ?: [];
            $inv_has    = fn($c) => in_array($c, $inv_cols, true);
            $inv_name   = $inv_has('name')  ? 'inv.name'  : 'NULL';
            $inv_sku    = $inv_has('sku')   ? 'inv.sku'   : 'NULL';
            $inv_unit   = $inv_has('unit')  ? 'inv.unit'  : 'NULL';
            $inv_ean    = $inv_has('ean')   ? 'inv.ean'   : 'NULL';
            // inv table uses sell_price, not selling_price
            $inv_sell   = $inv_has('sell_price') ? 'inv.sell_price' : ( $inv_has('selling_price') ? 'inv.selling_price' : 'NULL' );
            $name_sel   = "COALESCE({$inv_name}, {$name_sel})";
            $sku_sel    = "COALESCE({$inv_sku},  {$sku_sel})";
            $unit_sel   = "COALESCE({$inv_unit}, {$unit_sel})";
            $ean_sel    = "COALESCE({$inv_ean},  {$ean_sel})";
            $sell_sel   = "COALESCE({$inv_sell}, {$sell_sel})";

            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT sri.id, sri.receipt_id, sri.quantity, sri.inventory_id,
                        {$actual_sel} AS actual_qty,
                        {$loc_sel}    AS location,
                        {$name_sel}   AS name,
                        {$sku_sel}    AS sku,
                        {$unit_sel}   AS unit,
                        {$ean_sel}    AS ean,
                        {$sell_sel}   AS sell_price
                 FROM {$wpdb->prefix}vesho_stock_receipt_items sri
                 LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id = sri.inventory_id
                 WHERE sri.receipt_id IN ($placeholders)
                 ORDER BY sri.receipt_id ASC, sri.id ASC",
                ...$pending_ids
            ) ?: []);
            if ($wpdb->last_error) error_log('vesho preload items error: ' . $wpdb->last_error);
            foreach ($rows as $row) {
                $rid = (int)$row->receipt_id;
                $preloaded_items[$rid][] = [
                    'id'           => (int)$row->id,
                    'receipt_id'   => $rid,
                    'name'         => $row->name ?: '',
                    'sku'          => $row->sku  ?: '',
                    'unit'         => $row->unit ?: 'tk',
                    'ean'          => $row->ean  ?: '',
                    'quantity'     => (float)($row->quantity ?? 0),
                    'actual_qty'   => $row->actual_qty !== null ? (float)$row->actual_qty : null,
                    'location'     => $row->location ?: '',
                    'selling_price'=> $row->sell_price !== null ? (float)$row->sell_price : null,
                    'inventory_id' => $row->inventory_id ? (int)$row->inventory_id : null,
                ];
            }
        }
        $status_label = ['pending'=>'Ootel kinnitamist','received'=>'Vastuvõetud','approved'=>'✓ Kinnitatud','rejected'=>'✗ Tagasi lükatud'];
        $status_color = ['pending'=>'#d97706','received'=>'#2563eb','approved'=>'#16a34a','rejected'=>'#dc2626'];
        $status_bg    = ['pending'=>'#fef9c3','received'=>'#dbeafe','approved'=>'#dcfce7','rejected'=>'#fee2e2'];
        ?>
<h2 class="vwp-section-title">Kauba vastuvõtt</h2>

<?php if (!empty($pending)): ?>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:8px">
  Ootel vastuvõtmine
  <span style="background:#fef9c3;color:#b45309;border-radius:999px;padding:1px 8px;font-size:11px;margin-left:4px"><?php echo count($pending); ?></span>
</div>
<div id="vwp-recv-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
<?php foreach ($pending as $sr):
  $bref = $sr->batch_ref ?: $sr->receipt_num;
?>
<div class="vwp-card" id="recv-card-<?php echo $sr->id; ?>" style="overflow:hidden">
  <div style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;padding:2px 0" onclick="toggleRecv(<?php echo $sr->id; ?>, '<?php echo esc_js($bref); ?>')">
    <div>
      <div style="font-weight:700;font-size:14px;color:#0d1f2d">
        <?php echo $bref ? 'Arve: '.esc_html($bref) : esc_html($sr->receipt_num); ?>
        <?php if ($sr->supplier): ?><span style="font-weight:400;color:#64748b;margin-left:8px;font-size:13px">· <?php echo esc_html($sr->supplier); ?></span><?php endif; ?>
      </div>
      <div style="font-size:12px;color:#94a3b8;margin-top:2px"><?php echo (int)$sr->item_count; ?> kaupa · <?php echo esc_html(date('d.m.Y',strtotime($sr->created_at))); ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
      <span style="background:#fef9c3;color:#b45309;border-radius:999px;font-size:11px;font-weight:700;padding:2px 8px" id="recv-badge-<?php echo $sr->id; ?>"><?php echo (int)$sr->item_count; ?> ootel</span>
      <span style="color:#94a3b8;font-size:14px" id="recv-arrow-<?php echo $sr->id; ?>">▼</span>
    </div>
  </div>
  <div id="recv-items-<?php echo $sr->id; ?>" style="display:none;margin-top:12px;border-top:1px solid #f1f5f9">
    <div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px">Laen...</div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="vwp-card" style="text-align:center;color:#94a3b8;padding:24px;margin-bottom:20px">Sulle pole saadetusi määratud</div>
<?php endif; ?>

<?php if (!empty($history)): ?>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:8px">Ajalugu</div>
<div style="display:flex;flex-direction:column;gap:8px">
<?php foreach ($history as $sr):
  $st = $sr->status ?? 'received';
  $bref = $sr->batch_ref ?: $sr->receipt_num;
?>
<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#fff;border:1px solid #e8ecf0;border-radius:10px">
  <div>
    <div style="font-weight:600;font-size:13px;color:#0d1f2d"><?php echo esc_html($bref); ?><?php if($sr->supplier): ?> <span style="color:#94a3b8;font-weight:400">· <?php echo esc_html($sr->supplier); ?></span><?php endif; ?></div>
    <div style="font-size:12px;color:#94a3b8"><?php echo (int)$sr->item_count; ?> kaupa · <?php echo esc_html(date('d.m.Y',strtotime($sr->created_at))); ?></div>
  </div>
  <span style="font-size:12px;font-weight:600;color:<?php echo $status_color[$st]??'#64748b'; ?>;background:<?php echo $status_bg[$st]??'#f1f5f9'; ?>;padding:3px 8px;border-radius:999px;flex-shrink:0;margin-left:8px">
    <?php echo $status_label[$st] ?? $st; ?>
  </span>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lisa tundmatu kaup modal -->
<div id="vwp-add-item-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:24px;width:92%;max-width:420px;max-height:90vh;overflow-y:auto">
    <div style="font-size:16px;font-weight:700;color:#0d1f2d;margin-bottom:4px">Lisa tundmatu kaup</div>
    <div style="font-size:12px;color:#64748b;margin-bottom:16px">Arve: <span id="vwp-add-item-bref"></span></div>
    <div style="display:flex;flex-direction:column;gap:12px">
      <!-- EAN scan/lookup at top (auto-fills form from inventory) -->
      <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px 12px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:6px">📷 Skänni EAN — täidab vormi automaatselt</div>
        <div style="display:flex;gap:6px">
          <input id="vwp-ai-ean-scan" placeholder="Skänni või sisesta EAN..." autocomplete="off"
            style="flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;min-width:0"
            onkeydown="if(event.key==='Enter'){event.preventDefault();vwpLookupEan();}">
          <button type="button" onclick="vwpLookupEan()" style="padding:0 12px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0">Otsi</button>
        </div>
        <div id="vwp-ai-ean-msg" style="font-size:12px;margin-top:6px;display:none"></div>
      </div>
      <label style="font-size:13px;color:#374151">Toote nimi *<br>
        <input id="vwp-ai-name" placeholder="Toote nimetus" style="width:100%;margin-top:4px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box"></label>
      <label style="font-size:13px;color:#374151">EAN / Vöötkood<br>
        <div style="display:flex;gap:6px;margin-top:4px">
          <input id="vwp-ai-ean" placeholder="Skänni või sisesta" style="flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace">
          <button type="button" onclick="vwpGenEan()" style="padding:0 12px;background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);border-radius:8px;font-size:12px;cursor:pointer;white-space:nowrap">✦ Genereeri</button>
        </div>
      </label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <label style="font-size:13px;color:#374151">Kogus *<br>
          <input id="vwp-ai-qty" type="number" min="0.01" step="0.01" placeholder="0" style="width:100%;margin-top:4px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box"></label>
        <label style="font-size:13px;color:#374151">Ühik<br>
          <select id="vwp-ai-unit" style="width:100%;margin-top:4px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
            <option>tk</option><option>l</option><option>kg</option><option>m</option><option>m²</option><option>pakk</option>
          </select></label>
      </div>
      <label style="font-size:13px;color:#374151">Müügihind (€)<br>
        <input id="vwp-ai-price" type="number" min="0" step="0.01" placeholder="0.00" style="width:100%;margin-top:4px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box"></label>
      <label style="font-size:13px;color:#374151">Laoasukoht<br>
        <div style="display:flex;gap:6px;margin-top:4px">
          <input id="vwp-ai-loc" placeholder="nt A-01-03" style="flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;box-sizing:border-box" oninput="this.value=this.value.toUpperCase()">
          <button type="button" onclick="vwpScanAddItemLoc()" title="Skänni asukoht" style="padding:0 12px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:8px;font-size:14px;cursor:pointer">&#128247;</button>
        </div>
      </label>
      <label style="font-size:13px;color:#374151">Märkused<br>
        <textarea id="vwp-ai-notes" rows="2" placeholder="Valikuline" style="width:100%;margin-top:4px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box"></textarea></label>
      <div id="vwp-add-item-msg" style="color:#dc2626;font-size:13px;display:none"></div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="button" onclick="document.getElementById('vwp-add-item-modal').style.display='none'" style="flex:1;padding:11px;background:#f1f5f9;border:none;border-radius:8px;font-size:14px;cursor:pointer">Tühista</button>
        <button type="button" onclick="vwpAddItem()" id="vwp-add-item-btn" style="flex:2;padding:11px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer">Lisa kaup</button>
      </div>
    </div>
  </div>
</div>

<!-- Otsi laost modal -->
<div id="vwp-inv-search-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:flex-start;justify-content:center;padding-top:60px">
  <div style="background:#fff;border-radius:16px;padding:24px;width:92%;max-width:440px;max-height:80vh;display:flex;flex-direction:column">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
      <div style="font-size:16px;font-weight:700;color:#0d1f2d">🔍 Otsi laost</div>
      <button type="button" onclick="document.getElementById('vwp-inv-search-modal').style.display='none'" style="background:none;border:none;font-size:18px;color:#94a3b8;cursor:pointer;padding:0 4px">✕</button>
    </div>
    <div style="font-size:12px;color:#64748b;margin-bottom:12px">Arve: <span id="vwp-inv-search-bref"></span></div>
    <input id="vwp-inv-search-q" type="search" placeholder="Otsi nime või SKU järgi..." autocomplete="off"
      style="width:100%;padding:10px 12px;border:1.5px solid #00b4c8;border-radius:8px;font-size:14px;box-sizing:border-box;margin-bottom:10px;outline:none"
      oninput="vwpInvFilter(this.value)">
    <div id="vwp-inv-search-status" style="font-size:12px;color:#94a3b8;margin-bottom:6px;display:none"></div>
    <div id="vwp-inv-search-results" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:6px">
      <div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px">Sisesta otsisõna...</div>
    </div>
  </div>
</div>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>', NONCE='<?php echo $nonce; ?>';
  var loaded={}, itemData={}, currentRecvId=null;
  var _invCache=null, _invLoading=false;
  // Preloaded items (3006 style – all loaded at page render, no lazy AJAX)
  var preloadedItems=<?php echo json_encode($preloaded_items); ?>;
  var receiptBref=<?php
    $bref_map = [];
    foreach ( $pending ?: [] as $r ) {
        $bref_map[(int)$r->id] = $r->batch_ref ?: $r->receipt_num;
    }
    echo json_encode($bref_map);
  ?>;

  // Location ↔ EAN encoding (matches 3006 InventoryList.jsx)
  function eanToLocation(code){
    var s=String(code||'').replace(/\D/g,'');
    if(s.length===13&&s[0]==='9'){
      var blockNum=parseInt(s.slice(1,3),10);
      if(blockNum>=1&&blockNum<=26){
        var block=String.fromCharCode(64+blockNum);
        var shelf=String(parseInt(s.slice(3,5),10)).padStart(2,'0');
        var pos=String(parseInt(s.slice(5,7),10)).padStart(2,'0');
        if(shelf&&pos) return block+'-'+shelf+'-'+pos;
      }
    }
    return code.toUpperCase();
  }
  function locToEan12(loc){
    var m=String(loc||'').toUpperCase().match(/^([A-Z])-(\d+)-(\d+)$/);
    if(!m) return null;
    var block=String(m[1].charCodeAt(0)-64).padStart(2,'0');
    var shelf=m[2].padStart(2,'0');
    var pos=m[3].padStart(2,'0');
    return '9'+block+shelf+pos+'00000';
  }

  window.vwpScanLocation = function(rid, itemId){
    if(typeof window.VeshoScanner==='undefined'){alert('Scanner ei ole saadaval');return;}
    window.VeshoScanner.open({
      title:'Skänni laoaadress',
      autoConfirm:true,
      manualInput:true,
      wide:true,
      onScan:function(code){
        var loc=eanToLocation(code);
        var inp=document.getElementById('rl-'+itemId);
        if(inp) inp.value=loc;
        if(itemData[rid]&&itemData[rid][itemId]) itemData[rid][itemId].location=loc;
        // Occupancy check
        var fd2=new FormData();
        fd2.append('action','vesho_check_warehouse_location');
        fd2.append('nonce',NONCE);
        fd2.append('location_code',loc);
        fetch(AJAX,{method:'POST',body:fd2}).then(r=>r.json()).then(function(d){
          if(d.success&&d.data.occupied){
            var ok=confirm('\u26a0\ufe0f Aadressil '+loc+' on juba:\n'+d.data.item_name+'\n\nKas soovid samale aadressile panna?');
            if(!ok){
              if(inp) inp.value='';
              if(itemData[rid]&&itemData[rid][itemId]) itemData[rid][itemId].location='';
            }
          }
        });
      }
    });
  };

  window.vwpScanAddItemLoc = function(){
    if(typeof window.VeshoScanner==='undefined'){alert('Scanner ei ole saadaval');return;}
    window.VeshoScanner.open({
      title:'Skänni laoaadress',
      autoConfirm:true,
      manualInput:true,
      wide:true,
      onScan:function(code){
        var loc=eanToLocation(code);
        var inp=document.getElementById('vwp-ai-loc');
        if(inp) inp.value=loc;
      }
    });
  };

  window.toggleRecv = function(id, bref){
    var el=document.getElementById('recv-items-'+id);
    var arrow=document.getElementById('recv-arrow-'+id);
    if(el.style.display==='none'){
      el.style.display='';
      arrow.textContent='▲';
      currentRecvId=id;
      if(loaded[id]) return;
      loaded[id]=true;
      itemData[id]={};
      // Use preloaded items (3006 style) — no AJAX needed
      var items=preloadedItems[id]||[];
      items.forEach(function(it){
        itemData[id][it.id]={actual_qty:it.actual_qty!==null?it.actual_qty:it.quantity,location:it.location||'',ean:it.ean||''};
      });
      renderItems(id,items,bref);
    } else {
      el.style.display='none';
      arrow.textContent='▼';
    }
  };

  function renderItems(rid, items, bref){
    var el=document.getElementById('recv-items-'+rid);
    var html='';
    // Header row
    html+='<div style="display:grid;grid-template-columns:1fr 80px 80px 36px;gap:8px;padding:8px 16px 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8">';
    html+='<span>Toode</span><span style="text-align:right">Oodatav</span><span style="text-align:center">Tegelik</span><span></span>';
    html+='</div>';
    items.forEach(function(it){
      var done=it.actual_qty!==null;
      html+='<div style="border-top:1px solid #f1f5f9;padding:10px 16px" id="recv-row-'+it.id+'">';
      // Main row
      html+='<div style="display:grid;grid-template-columns:1fr 80px 80px 36px;gap:8px;align-items:center;margin-bottom:6px">';
      html+='<div><div style="font-size:13px;font-weight:600;color:#0d1f2d">'+escH(it.name||it.product_name||'?')+'</div>'+(it.sku?'<div style="font-size:11px;color:#94a3b8;font-family:monospace">'+escH(it.sku)+'</div>':'')+'</div>';
      html+='<div style="font-size:13px;color:#64748b;text-align:right;font-weight:500">'+(it.expected_qty||it.quantity)+' '+(it.unit||'tk')+'</div>';
      html+='<input type="number" id="rq-'+it.id+'" value="'+(done?it.actual_qty:it.expected_qty||it.quantity||'')+'" min="0.01" step="0.01"'+(done?' disabled':'')+
        ' onchange="itemData['+rid+']['+it.id+'].actual_qty=this.value"'+
        ' style="padding:5px 6px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;text-align:center;width:100%'+(done?';opacity:.5':'')+'">'+
      '</div>';
      // EAN print btn
      var ean=it.ean||it.product_ean||'';
      if(ean){
        html+='<button type="button" onclick="vwpPrintEan(\''+escJ(ean)+'\',\''+escJ(it.name||it.product_name||'')+'\','+(it.selling_price||0)+')" title="Prindi EAN" style="padding:0 6px;height:32px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:6px;cursor:pointer;font-size:13px">🖨️</button>';
      } else {
        html+='<button type="button" onclick="vwpGenAndPrint('+it.id+','+rid+',\''+escJ(it.name||it.product_name||'')+'\','+(it.selling_price||0)+')" title="Genereeri EAN ja prindi" style="padding:0 6px;height:32px;background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);border-radius:6px;cursor:pointer;font-size:13px">✦</button>';
      }
      html+='</div>';
      // Location row
      html+='<div style="display:flex;gap:6px;align-items:center">';
      html+='<span style="font-size:11px;color:#94a3b8;flex-shrink:0">📍</span>';
      html+='<input id="rl-'+it.id+'" value="'+(it.location||'')+'" placeholder="Laoasukoht (nt A-01-03)"'+(done?' disabled':'')+
        ' oninput="this.value=this.value.toUpperCase();itemData['+rid+']['+it.id+'].location=this.value"'+
        ' style="flex:1;padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;font-family:monospace'+(done?';opacity:.5':'')+'">'+
      (done?'':'<button type="button" onclick="vwpScanLocation('+rid+','+it.id+')" title="Skänni asukoht" style="flex-shrink:0;padding:0 8px;height:32px;background:rgba(0,180,200,.1);color:#00b4c8;border:1px solid rgba(0,180,200,.25);border-radius:6px;cursor:pointer;font-size:14px">&#128247;</button>');
      html+='</div>';
      html+='</div>';
    });
    // Empty state
    if (!items.length) {
      html+='<div style="padding:20px 16px;text-align:center;color:#94a3b8;font-size:13px">Admin ei lisanud tooteid — kasuta <strong>+ Lisa tundmatu kaup</strong></div>';
    }
    // Footer buttons
    html+='<div style="border-top:1px solid #f1f5f9;padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">';
    html+='<button type="button" onclick="vwpScanReceiptEan('+rid+',\''+escJ(bref)+'\')" style="padding:7px 14px;background:rgba(245,158,11,.1);color:#d97706;border:1px solid rgba(245,158,11,.3);border-radius:8px;font-size:12px;cursor:pointer">📷 Skänni EAN</button>';
    html+='<button type="button" onclick="vwpOpenInvSearch('+rid+',\''+escJ(bref)+'\')" style="padding:7px 14px;background:rgba(16,185,129,.08);color:#10b981;border:1px solid rgba(16,185,129,.3);border-radius:8px;font-size:12px;cursor:pointer">🔍 Otsi laost</button>';
    html+='<button type="button" onclick="vwpOpenAddItem('+rid+',\''+escJ(bref)+'\')" style="padding:7px 14px;background:rgba(0,180,200,.08);color:#00b4c8;border:1px dashed rgba(0,180,200,.4);border-radius:8px;font-size:12px;cursor:pointer">+ Lisa tundmatu kaup</button>';
    html+='<button type="button" onclick="vwpSubmitBatch('+rid+')" id="recv-submit-'+rid+'" style="margin-left:auto;padding:7px 20px;background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">✓ Saada adminile</button>';
    html+='</div>';
    html+='<div id="recv-msg-'+rid+'" style="padding:0 16px 10px;font-size:13px;color:#dc2626;display:none"></div>';
    el.innerHTML=html;
  }

  window.vwpSubmitBatch = function(rid){
    var btn=document.getElementById('recv-submit-'+rid);
    var msg=document.getElementById('recv-msg-'+rid);
    var data=itemData[rid]||{};
    var items=Object.entries(data).map(function(e){return{id:e[0],actual_qty:e[1].actual_qty,location:e[1].location,ean:e[1].ean};});
    var invalid=items.find(function(i){return !i.actual_qty||Number(i.actual_qty)<=0;});
    if(invalid){msg.textContent='Kõikidel kaupadel peab olema kogus';msg.style.display='';return;}
    btn.disabled=true; btn.textContent='Saadan...';
    var fd=new FormData();
    fd.append('action','vesho_worker_submit_batch');
    fd.append('nonce',NONCE);
    fd.append('receipt_id',rid);
    fd.append('items',JSON.stringify(items));
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      if(d.success){
        var card=document.getElementById('recv-card-'+rid);
        if(card) card.style.display='none';
        var badge=document.getElementById('recv-badge-'+rid);
        if(badge){badge.textContent='✓ Saadetud';badge.style.background='#dcfce7';badge.style.color='#16a34a';}
      } else {
        btn.disabled=false; btn.textContent='✓ Saada adminile';
        msg.textContent=d.data?.message||'Viga';msg.style.display='';
      }
    }).catch(function(){
      btn.disabled=false; btn.textContent='✓ Saada adminile';
      msg.textContent='Ühenduse viga';msg.style.display='';
    });
  };

  window.vwpOpenAddItem = function(rid, bref, preEan){
    currentRecvId=rid;
    document.getElementById('vwp-add-item-bref').textContent=bref;
    document.getElementById('vwp-ai-name').value='';
    document.getElementById('vwp-ai-ean-scan').value=preEan||'';
    document.getElementById('vwp-ai-ean').value=preEan||'';
    document.getElementById('vwp-ai-qty').value='';
    document.getElementById('vwp-ai-price').value='';
    document.getElementById('vwp-ai-loc').value='';
    document.getElementById('vwp-ai-notes').value='';
    document.getElementById('vwp-add-item-msg').style.display='none';
    document.getElementById('vwp-ai-ean-msg').style.display='none';
    document.getElementById('vwp-add-item-modal').style.display='flex';
    // If pre-filled EAN, auto-lookup
    if(preEan) setTimeout(vwpLookupEan,50);
  };

  window.vwpLookupEan = function(){
    var scanInp=document.getElementById('vwp-ai-ean-scan');
    var ean=(scanInp?scanInp.value:'').trim();
    var msgEl=document.getElementById('vwp-ai-ean-msg');
    if(!ean){if(msgEl){msgEl.style.display='none';}return;}
    if(msgEl){msgEl.textContent='Otsin...';msgEl.style.color='#64748b';msgEl.style.display='';}
    var fd=new FormData();
    fd.append('action','vesho_worker_lookup_ean');
    fd.append('nonce',NONCE);
    fd.append('ean',ean);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){
        var p=d.data;
        document.getElementById('vwp-ai-name').value=p.name||'';
        document.getElementById('vwp-ai-ean').value=p.ean||ean;
        if(p.unit){var usel=document.getElementById('vwp-ai-unit');if(usel)usel.value=p.unit;}
        if(p.selling_price){document.getElementById('vwp-ai-price').value=p.selling_price;}
        if(msgEl){msgEl.textContent='Leitud: '+p.name;msgEl.style.color='#10b981';msgEl.style.display='';}
        document.getElementById('vwp-ai-qty').focus();
      } else {
        // Not found — copy EAN to EAN field, let user fill manually
        document.getElementById('vwp-ai-ean').value=ean;
        if(msgEl){msgEl.textContent='EAN ei leitud — täida käsitsi';msgEl.style.color='#f59e0b';msgEl.style.display='';}
        document.getElementById('vwp-ai-name').focus();
      }
    }).catch(function(){
      if(msgEl){msgEl.textContent='Ühenduse viga';msgEl.style.color='#dc2626';msgEl.style.display='';}
    });
  };

  window.vwpScanReceiptEan = function(rid, bref){
    if(typeof window.VeshoScanner==='undefined'){alert('Scanner ei ole saadaval');return;}
    window.VeshoScanner.open({
      title:'Skänni EAN',
      autoConfirm:true,
      onScan:function(code){
        var data=itemData[rid]||{};
        var match=Object.entries(data).find(function(e){return e[1].ean===code;});
        if(match){
          var row=document.getElementById('recv-row-'+match[0]);
          if(row){row.style.transition='background .3s';row.style.background='rgba(0,180,200,.15)';row.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){row.style.background='';},2000);}
        } else {
          vwpOpenAddItem(rid,bref,code);
        }
      }
    });
  };

  window.vwpAddItem = function(){
    var name=document.getElementById('vwp-ai-name').value.trim();
    var qty=document.getElementById('vwp-ai-qty').value;
    if(!name||!qty){
      var m=document.getElementById('vwp-add-item-msg');
      m.textContent='Toote nimi ja kogus on kohustuslikud';m.style.display='';return;
    }
    var btn=document.getElementById('vwp-add-item-btn');
    btn.disabled=true; btn.textContent='Lisamine...';
    var fd=new FormData();
    fd.append('action','vesho_worker_add_batch_item');
    fd.append('nonce',NONCE);
    fd.append('receipt_id',currentRecvId);
    fd.append('product_name',name);
    fd.append('product_ean',document.getElementById('vwp-ai-ean').value);
    fd.append('actual_qty',qty);
    fd.append('product_unit',document.getElementById('vwp-ai-unit').value);
    fd.append('selling_price',document.getElementById('vwp-ai-price').value||0);
    fd.append('location',document.getElementById('vwp-ai-loc').value);
    fd.append('notes',document.getElementById('vwp-ai-notes').value);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      btn.disabled=false; btn.textContent='Lisa kaup';
      if(d.success){
        document.getElementById('vwp-add-item-modal').style.display='none';
        // Reload page to refresh preloaded items (simplest, ensures data consistency)
        location.reload();
      } else {
        var m=document.getElementById('vwp-add-item-msg');
        m.textContent=d.data?.message||'Viga';m.style.display='';
      }
    });
  };

  window.vwpGenEan = function(){
    var base='200'+String(Math.floor(Math.random()*1000000000)).padStart(9,'0');
    var sum=0; for(var i=0;i<12;i++) sum+=parseInt(base[i])*(i%2===0?1:3);
    document.getElementById('vwp-ai-ean').value=base+(10-sum%10)%10;
  };

  window.vwpPrintEan = function(ean, name, price){
    var priceHtml=price?'<div style="font-size:20px;font-weight:800;margin:4px 0">'+Number(price).toFixed(2)+' €</div>':'';
    var w=window.open('','_blank','width=320,height=260');
    w.document.write('<!DOCTYPE html><html><head><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Helvetica,Arial,sans-serif;background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh}.label{width:280px;border:1.5px solid #222;border-radius:8px;padding:14px 16px;text-align:center}.name{font-size:13px;font-weight:700;margin-bottom:4px;line-height:1.3}@media print{body{min-height:auto}}</style></head><body><div class="label"><div class="name">'+name+'</div>'+priceHtml+'<svg id="b" style="max-width:100%"></svg></div><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script><script>window.onload=function(){JsBarcode(\'#b\',\''+ean+'\',{format:\'EAN13\',width:2,height:55,displayValue:true,fontSize:13,margin:4});setTimeout(function(){window.print()},300)}<\/script></body></html>');
    w.document.close();
  };

  window.vwpGenAndPrint = function(itemId, rid, name, price){
    var base='200'+String(Math.floor(Math.random()*1000000000)).padStart(9,'0');
    var sum=0; for(var i=0;i<12;i++) sum+=parseInt(base[i])*(i%2===0?1:3);
    var ean=base+(10-sum%10)%10;
    if(itemData[rid]&&itemData[rid][itemId]) itemData[rid][itemId].ean=ean;
    vwpPrintEan(ean, name, price);
  };

  // ── Inventory search ─────────────────────────────────────────────────────
  window.vwpOpenInvSearch = function(rid, bref){
    currentRecvId=rid;
    document.getElementById('vwp-inv-search-bref').textContent=bref;
    document.getElementById('vwp-inv-search-q').value='';
    document.getElementById('vwp-inv-search-status').style.display='none';
    document.getElementById('vwp-inv-search-results').innerHTML='<div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px">Sisesta otsisõna...</div>';
    document.getElementById('vwp-inv-search-modal').style.display='flex';
    setTimeout(function(){document.getElementById('vwp-inv-search-q').focus();},100);
    // Pre-load inventory if not cached yet
    if(!_invCache&&!_invLoading) _vwpLoadInventory();
  };

  function _vwpLoadInventory(){
    _invLoading=true;
    var status=document.getElementById('vwp-inv-search-status');
    if(status){status.textContent='Laen laovaru...';status.style.display='';}
    var fd=new FormData();
    fd.append('action','vesho_worker_get_inventory');
    fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      _invLoading=false;
      if(d.success){
        _invCache=d.data.items||[];
        if(status){status.style.display='none';}
        // If search box already has text, filter now
        var q=document.getElementById('vwp-inv-search-q');
        if(q&&q.value.trim()) vwpInvFilter(q.value);
      } else {
        if(status){status.textContent='Viga laovaru laadimisel';status.style.color='#dc2626';status.style.display='';}
      }
    }).catch(function(){
      _invLoading=false;
      if(status){status.textContent='Ühenduse viga';status.style.color='#dc2626';status.style.display='';}
    });
  }

  window.vwpInvFilter = function(q){
    var el=document.getElementById('vwp-inv-search-results');
    if(!el) return;
    q=q.trim().toLowerCase();
    if(!q){
      el.innerHTML='<div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px">Sisesta otsisõna...</div>';
      return;
    }
    if(!_invCache){
      el.innerHTML='<div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px">Laen laovaru...</div>';
      if(!_invLoading) _vwpLoadInventory();
      return;
    }
    var results=_invCache.filter(function(it){
      return (it.name||'').toLowerCase().indexOf(q)!==-1||(it.sku||'').toLowerCase().indexOf(q)!==-1;
    });
    if(!results.length){
      el.innerHTML='<div style="text-align:center;color:#94a3b8;font-size:13px;padding:20px">Ei leitud ühtegi vastet</div>';
      return;
    }
    var html='';
    results.slice(0,40).forEach(function(it){
      html+='<div onclick="vwpSelectInvItem('+it.id+')" style="padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:background .15s" '+
        'onmouseover="this.style.background=\'#f0fdf4\';this.style.borderColor=\'#10b981\'" onmouseout="this.style.background=\'\';this.style.borderColor=\'#e2e8f0\'">';
      html+='<div style="font-size:13px;font-weight:600;color:#0d1f2d">'+escH(it.name||'?')+'</div>';
      html+='<div style="display:flex;gap:10px;margin-top:2px;font-size:11px;color:#94a3b8">';
      if(it.sku) html+='<span>SKU: '+escH(it.sku)+'</span>';
      if(it.quantity!==undefined) html+='<span>Laos: '+parseFloat(it.quantity||0)+' '+(it.unit||'tk')+'</span>';
      if(it.sell_price) html+='<span>'+parseFloat(it.sell_price).toFixed(2)+' €</span>';
      html+='</div>';
      html+='</div>';
    });
    if(results.length>40) html+='<div style="text-align:center;color:#94a3b8;font-size:12px;padding:8px">+'+(results.length-40)+' veel — täpsusta otsisõna</div>';
    el.innerHTML=html;
  };

  window.vwpSelectInvItem = function(id){
    if(!_invCache) return;
    var it=_invCache.find(function(x){return String(x.id)===String(id);});
    if(!it) return;
    // Close search modal, open add-item modal pre-filled
    document.getElementById('vwp-inv-search-modal').style.display='none';
    var bref=receiptBref[currentRecvId]||'';
    document.getElementById('vwp-add-item-bref').textContent=bref;
    document.getElementById('vwp-ai-name').value=it.name||'';
    document.getElementById('vwp-ai-ean-scan').value='';
    document.getElementById('vwp-ai-ean').value=it.ean||'';
    document.getElementById('vwp-ai-qty').value='';
    document.getElementById('vwp-ai-price').value=it.sell_price?parseFloat(it.sell_price).toFixed(2):'';
    document.getElementById('vwp-ai-loc').value='';
    document.getElementById('vwp-ai-notes').value='';
    document.getElementById('vwp-add-item-msg').style.display='none';
    document.getElementById('vwp-ai-ean-msg').style.display='none';
    var usel=document.getElementById('vwp-ai-unit');
    if(usel&&it.unit) usel.value=it.unit;
    // Store inventory_id for later use
    document.getElementById('vwp-add-item-modal').dataset.invId=it.id;
    document.getElementById('vwp-add-item-modal').style.display='flex';
    setTimeout(function(){document.getElementById('vwp-ai-qty').focus();},100);
  };

  function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
  function escJ(s){return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'");}
})();
</script>
        <?php
    }

    // ── Tab: Tellimused ───────────────────────────────────────────────────────

    private static function tab_tellimused($wid, $nonce, $ajax) {
        global $wpdb;
        // Unclaimed orders (admin pushed to processing, no worker yet)
        $pending_count = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE status='processing' AND (worker_id IS NULL OR worker_id=0)"
        );
        // My claimed order — only processing (confirmed = already packed, don't show modal again)
        $my_order = $wpdb->get_row($wpdb->prepare(
            "SELECT so.* FROM {$wpdb->prefix}vesho_shop_orders so
             WHERE so.worker_id=%d AND so.status='processing' LIMIT 1", $wid
        ));
        $my_items = $my_order ? $wpdb->get_results($wpdb->prepare(
            "SELECT soi.*, COALESCE(inv.ean, '') AS ean, COALESCE(inv.sku,'') AS sku
             FROM {$wpdb->prefix}vesho_shop_order_items soi
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id = soi.inventory_id
             WHERE soi.order_id=%d ORDER BY soi.id ASC", $my_order->id
        )) : [];

        $client_name = '';
        $delivery_address = '';
        $delivery_label = '';
        if ($my_order) {
            $client_name = $my_order->client_id
                ? $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}vesho_clients WHERE id=%d", $my_order->client_id))
                : ($my_order->guest_name ?: '—');
            $delivery_address = $my_order->shipping_address ?? $my_order->delivery_address ?? '';
            $dm = $my_order->delivery_method ?? $my_order->shipping_method ?? '';
            $delivery_labels = ['pickup' => '🏪 Kaupluses', 'courier' => '🚚 Kuller', 'parcel' => '📦 Pakiautomaat'];
            $delivery_label = $delivery_labels[$dm] ?? ($my_order->shipping_name ?? $dm ?? '—');
        }

        // Company info for invoice
        $co_name  = get_option('vesho_company_name', get_bloginfo('name'));
        $co_email = get_option('vesho_company_email', get_bloginfo('admin_email'));
        $co_phone = get_option('vesho_company_phone', '');

        $items_json = json_encode(array_map(fn($i) => [
            'id'    => (int)$i->id,
            'name'  => $i->name,
            'qty'   => (float)$i->quantity,
            'price' => (float)($i->shop_price ?? $i->unit_price ?? 0),
            'ean'   => $i->ean ?? '',
            'sku'   => $i->sku ?? '',
            'picked'=> (bool)$i->picked,
        ], $my_items ?? []), JSON_UNESCAPED_UNICODE);
        ?>
<h2 class="vwp-section-title">Tellimuste täitmine</h2>
<div id="vwp-shop-msg" class="vwp-msg" style="display:none"></div>

<!-- Queue / done screen -->
<div id="vwp-queue-screen">
<?php if (!$my_order): ?>
<div class="vwp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:48px;margin-bottom:16px">🛒</div>
  <div style="font-size:16px;font-weight:700;color:var(--vwp-text,#1e293b);margin-bottom:8px">Valmis komplekteerima?</div>
  <div style="font-size:13px;color:#64748b;margin-bottom:24px" id="vwp-queue-count">
    <?php echo $pending_count > 0 ? "Järjekorras: {$pending_count} tellimust." : 'Hetkel pole aktiivseid tellimusi.'; ?>
  </div>
  <?php if ($pending_count > 0): ?>
  <button id="vwp-claim-btn"
          style="background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:12px 28px;font-size:15px;font-weight:700;cursor:pointer">
    &#9654; Alusta
  </button>
  <?php else: ?>
  <div style="font-size:13px;color:#94a3b8">— Pole tellimusi</div>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<!-- Done screen (shown after packing) -->
<div id="vwp-done-screen" style="display:none">
  <div class="vwp-card" style="text-align:center;padding:40px 24px;border-color:rgba(16,185,129,0.3)">
    <div style="font-size:52px;margin-bottom:12px">✅</div>
    <div style="font-size:18px;font-weight:800;color:#10b981;margin-bottom:6px">Tellimus pakitud!</div>
    <div style="font-size:13px;color:#64748b;margin-bottom:28px" id="vwp-done-msg"></div>
    <button id="vwp-next-btn"
            style="background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.35);border-radius:10px;padding:13px 32px;font-size:15px;font-weight:700;cursor:pointer">
      ⏳ Laadin...
    </button>
  </div>
</div>

<?php if ($my_order): ?>
<!-- Active order modal overlay -->
<div id="vwp-shop-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);backdrop-filter:blur(6px);z-index:300;display:block"></div>
<div id="vwp-shop-modal" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:301;background:#fff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 20px 60px rgba(0,0,0,0.2);width:calc(100% - 32px);max-width:500px;max-height:90vh;overflow-y:auto;padding:24px">

  <!-- Päis -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
    <div>
      <div style="font-size:20px;font-weight:900;color:#00b4c8;letter-spacing:-0.02em"><?php echo esc_html($my_order->order_number); ?></div>
      <div style="font-size:12px;color:#64748b;margin-top:2px">📅 <?php echo date('d.m.Y', strtotime($my_order->created_at)); ?></div>
    </div>
    <button id="vwp-release-btn" data-id="<?php echo (int)$my_order->id; ?>"
            style="background:rgba(239,68,68,0.08);color:#ef4444;border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:6px 12px;font-size:12px;cursor:pointer">Loobu</button>
  </div>

  <!-- Dynamic stepper -->
  <div id="vwp-stepper" style="display:flex;align-items:center;margin-bottom:20px;overflow-x:auto;padding-bottom:2px"></div>

  <!-- Klient + Tarne -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
    <div style="background:#f8fafc;border-radius:10px;padding:10px 14px">
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:4px">Klient</div>
      <div style="font-weight:600;color:#1e293b;font-size:14px"><?php echo esc_html($client_name); ?></div>
      <?php if (!empty($my_order->phone)): ?><div style="font-size:12px;color:#64748b;margin-top:2px">📞 <?php echo esc_html($my_order->phone); ?></div><?php endif; ?>
    </div>
    <div style="background:#f8fafc;border-radius:10px;padding:10px 14px">
      <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:4px">Tarne</div>
      <div style="font-weight:600;color:#1e293b;font-size:13px"><?php echo esc_html($delivery_label); ?></div>
      <?php if ($delivery_address): ?><div style="font-size:11px;color:#64748b;margin-top:2px;line-height:1.4"><?php echo esc_html($delivery_address); ?></div><?php endif; ?>
    </div>
  </div>
  <?php if (!empty($my_order->notes)): ?>
  <div style="margin-bottom:14px;padding:8px 12px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:8px;font-size:12px;color:#1e293b">📋 <?php echo esc_html($my_order->notes); ?></div>
  <?php endif; ?>

  <!-- Tooted -->
  <div style="background:#f8fafc;border-radius:14px;padding:14px 16px;margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:700">Tooted</div>
      <div style="font-size:12px;font-weight:600;color:#64748b" id="vwp-shop-progress">
        0/<?php echo count($my_items); ?> kogutud
      </div>
    </div>
    <!-- EAN scan -->
    <div style="display:flex;gap:6px;margin-bottom:10px">
      <input type="text" id="vw-ean-scan" placeholder="Skanni EAN..." autocomplete="off"
             style="flex:1;padding:8px 12px;border-radius:10px;border:2px solid #e2e8f0;background:#fff;font-size:14px;outline:none;font-family:monospace">
      <button id="vw-ean-camera-btn" type="button"
              style="padding:8px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-size:18px;flex-shrink:0">📷</button>
    </div>
    <div id="vw-scan-feedback" style="font-size:12px;min-height:18px;margin-bottom:6px;font-weight:600"></div>
    <!-- Item list -->
    <div id="vwp-shop-items" style="display:flex;flex-direction:column;gap:6px">
    <?php foreach ($my_items as $item): ?>
    <div id="shop-row-<?php echo $item->id; ?>"
         class="pick-item-row<?php echo $item->picked ? ' picked' : ''; ?>"
         data-ean="<?php echo esc_attr($item->ean ?? ''); ?>"
         data-orig-qty="<?php echo (float)$item->quantity; ?>"
         data-unit-price="<?php echo (float)($item->shop_price ?? $item->unit_price ?? 0); ?>"
         style="border-radius:10px;background:<?php echo $item->picked ? 'rgba(16,185,129,0.08)' : '#fff'; ?>;border:1px solid <?php echo $item->picked ? 'rgba(16,185,129,0.25)' : '#e2e8f0'; ?>;overflow:hidden;transition:all 0.15s">
      <div onclick="togglePickRow(<?php echo $item->id; ?>)"
           style="display:flex;align-items:center;gap:12px;padding:10px 12px;cursor:pointer">
        <div id="shop-check-<?php echo $item->id; ?>"
             style="width:22px;height:22px;border-radius:7px;border:2px solid <?php echo $item->picked ? '#10b981' : '#e2e8f0'; ?>;background:<?php echo $item->picked ? '#10b981' : 'transparent'; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0">
          <?php echo $item->picked ? '✓' : ''; ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;color:#1e293b;font-size:14px;text-decoration:<?php echo $item->picked ? 'line-through' : 'none'; ?>;opacity:<?php echo $item->picked ? '0.6' : '1'; ?>"><?php echo esc_html($item->name); ?></div>
          <?php if (!empty($item->ean)): ?><div style="font-size:11px;color:#94a3b8;font-family:monospace;margin-top:1px"><?php echo esc_html($item->ean); ?></div><?php endif; ?>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-weight:700;color:#1e293b;font-size:14px" id="qty-display-<?php echo $item->id; ?>"><?php echo (float)$item->quantity; ?> tk</div>
          <div style="font-size:11px;color:#64748b"><?php echo number_format((float)($item->shop_price ?? $item->unit_price ?? 0),2,',','.'); ?> €</div>
        </div>
      </div>
      <!-- Qty adjuster -->
      <div style="display:flex;align-items:center;gap:6px;padding:6px 12px 10px;border-top:1px solid #f1f5f9" onclick="event.stopPropagation()">
        <span style="font-size:11px;color:#94a3b8;flex-shrink:0">Kogus:</span>
        <button type="button" onclick="adjQty(<?php echo $item->id; ?>,-1)"
                style="width:26px;height:26px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;font-size:16px;cursor:pointer;line-height:1;padding:0">−</button>
        <input type="number" id="qty-<?php echo $item->id; ?>"
               value="<?php echo (float)$item->quantity; ?>"
               min="0" max="<?php echo (float)$item->quantity; ?>" step="1"
               onchange="onQtyChange(<?php echo $item->id; ?>)"
               style="width:52px;text-align:center;padding:3px 6px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;font-size:13px;font-weight:700;outline:none">
        <button type="button" onclick="adjQty(<?php echo $item->id; ?>,1)"
                style="width:26px;height:26px;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;font-size:16px;cursor:pointer;line-height:1;padding:0">+</button>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;padding-top:10px;margin-top:8px;border-top:1px solid #e2e8f0">
      <span style="font-weight:700;color:#1e293b">Kokku</span>
      <span style="font-size:16px;font-weight:800;color:#1e293b"><?php echo number_format((float)$my_order->total,2,',','.'); ?> €</span>
    </div>
  </div>

  <!-- Print buttons -->
  <div style="display:flex;gap:10px;margin-bottom:14px" id="vwp-print-row">
    <button id="vwp-print-invoice-btn" disabled
            style="flex:1;padding:10px;border-radius:10px;border:1px solid rgba(99,102,241,0.25);background:rgba(99,102,241,0.1);color:#818cf8;font-size:13px;font-weight:600;cursor:pointer;opacity:0.45">
      🖨️ Arve (A4)
    </button>
    <button id="vwp-print-label-btn" disabled
            style="flex:1;padding:10px;border-radius:10px;border:1px solid rgba(99,102,241,0.2);background:rgba(99,102,241,0.08);color:#818cf8;font-size:13px;font-weight:600;cursor:pointer;opacity:0.45">
      🏷️ Pakikaart
    </button>
  </div>

  <!-- Pack button -->
  <button id="vwp-pack-btn" data-id="<?php echo (int)$my_order->id; ?>" disabled
          style="width:100%;padding:14px;font-size:15px;font-weight:800;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;color:#94a3b8;cursor:not-allowed">
    Kogu kõik tooted (0/<?php echo count($my_items); ?>)
  </button>
</div><!-- end modal -->
<?php endif; ?>

<script>
(function(){
  var AJAX='<?php echo esc_js($ajax); ?>', NONCE='<?php echo esc_js($nonce); ?>';
  var allItems=<?php echo $items_json; ?>;
  var CO_NAME=<?php echo json_encode($co_name); ?>;
  var CO_EMAIL=<?php echo json_encode($co_email); ?>;
  var CO_PHONE=<?php echo json_encode($co_phone); ?>;
  var ORDER_NUM=<?php echo json_encode($my_order ? ($my_order->order_number ?? '') : ''); ?>;
  var CLIENT_NAME=<?php echo json_encode($client_name); ?>;
  var CLIENT_PHONE=<?php echo json_encode($my_order ? ($my_order->phone ?? '') : ''); ?>;
  var CLIENT_EMAIL=<?php echo json_encode($my_order ? ($my_order->guest_email ?? $my_order->client_email ?? '') : ''); ?>;
  var DELIVERY_ADDR=<?php echo json_encode($delivery_address); ?>;
  var DELIVERY_LABEL=<?php echo json_encode($delivery_label); ?>;
  var ORDER_NOTES=<?php echo json_encode($my_order ? ($my_order->notes ?? '') : ''); ?>;
  var ORDER_DATE=<?php echo json_encode($my_order ? ($my_order->created_at ?? '') : ''); ?>;
  var CURRENT_ORDER_ID=<?php echo $my_order ? (int)$my_order->id : 'null'; ?>;
  var PENDING_COUNT=<?php echo (int)$pending_count; ?>;

  var invoicePrinted=false, labelPrinted=false;
  var pickedState={}; // itemId -> bool
  var qtyState={};    // itemId -> number

  // Init picked/qty state from PHP data
  allItems.forEach(function(it){
    pickedState[it.id]=it.picked;
    qtyState[it.id]=it.qty;
  });

  // ── localStorage crash recovery ───────────────────────────────────────────
  var LS_PICKED='workerPicked', LS_QTY='workerPickQty';
  function lsGetPicked(){try{return JSON.parse(localStorage.getItem(LS_PICKED)||'{}')}catch(e){return {}}}
  function lsGetQty(){try{return JSON.parse(localStorage.getItem(LS_QTY)||'{}')}catch(e){return {}}}
  function lsSavePicked(){try{localStorage.setItem(LS_PICKED,JSON.stringify(pickedState));}catch(e){}}
  function lsSaveQty(){try{localStorage.setItem(LS_QTY,JSON.stringify(qtyState));}catch(e){}}
  function lsClear(){try{localStorage.removeItem(LS_PICKED);localStorage.removeItem(LS_QTY);}catch(e){}}

  if(CURRENT_ORDER_ID){
    // Restore from localStorage
    var savedPicked=lsGetPicked(), savedQty=lsGetQty();
    allItems.forEach(function(it){
      if(savedPicked[it.id]!==undefined) pickedState[it.id]=savedPicked[it.id];
      if(savedQty[it.id]!==undefined) qtyState[it.id]=savedQty[it.id];
    });
    // Apply to DOM
    allItems.forEach(function(it){
      setRowUI(it.id, pickedState[it.id], qtyState[it.id]);
    });
  }

  // ── Stepper ───────────────────────────────────────────────────────────────
  function renderStepper(){
    var el=document.getElementById('vwp-stepper');
    if(!el) return;
    var allPicked=allItems.length===0||Object.values(pickedState).filter(Boolean).length===allItems.length;
    var steps=[{k:'picking',l:'Kogu'},{k:'invoice',l:'Arve'},{k:'label',l:'Kleeps'},{k:'done',l:'Valmis'}];
    var activeKey=!allPicked?'picking':!invoicePrinted?'invoice':!labelPrinted?'label':'done';
    var order=['picking','invoice','label','done'];
    var activeIdx=order.indexOf(activeKey);
    var html='';
    steps.forEach(function(step,i){
      var done=i<activeIdx;
      var active=step.k===activeKey;
      var col=done?'#10b981':active?'#00b4c8':'#cbd5e1';
      var bg=done?'rgba(16,185,129,0.15)':active?'rgba(0,180,200,0.15)':'#f8fafc';
      var txt=active?'#00b4c8':done?'#10b981':'#94a3b8';
      html+='<div style="display:flex;align-items:center;flex-shrink:0">';
      html+='<div style="display:flex;flex-direction:column;align-items:center;gap:4px">';
      html+='<div style="width:30px;height:30px;border-radius:50%;border:2px solid '+col+';background:'+bg+';display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:'+col+'">'+(done?'✓':(i+1))+'</div>';
      html+='<div style="font-size:10px;color:'+txt+';font-weight:'+(active?700:400)+';white-space:nowrap">'+step.l+'</div>';
      html+='</div>';
      if(i<steps.length-1){
        html+='<div style="width:32px;height:2px;background:'+(i<activeIdx?'#10b981':'#e2e8f0')+';margin:0 4px;margin-bottom:16px;flex-shrink:0"></div>';
      }
      html+='</div>';
    });
    el.innerHTML=html;
  }

  // ── Pack button state ─────────────────────────────────────────────────────
  function updatePackBtn(){
    var btn=document.getElementById('vwp-pack-btn');
    if(!btn) return;
    var total=allItems.length;
    var picked=Object.values(pickedState).filter(Boolean).length;
    var allPicked=total===0||picked===total;
    var readyToPack=allPicked&&invoicePrinted&&labelPrinted;

    if(readyToPack){
      btn.disabled=false;
      btn.style.background='rgba(16,185,129,0.15)';
      btn.style.color='#10b981';
      btn.style.border='1px solid rgba(16,185,129,0.35)';
      btn.style.cursor='pointer';
      btn.textContent='✓ Komplekteeritud';
    } else if(!allPicked){
      btn.disabled=true;
      btn.style.background='#f8fafc';
      btn.style.color='#94a3b8';
      btn.style.border='1px solid #e2e8f0';
      btn.style.cursor='not-allowed';
      btn.textContent='Kogu kõik tooted ('+picked+'/'+total+')';
    } else if(!invoicePrinted){
      btn.disabled=true;
      btn.style.background='#f8fafc';
      btn.style.color='#94a3b8';
      btn.style.border='1px solid #e2e8f0';
      btn.style.cursor='not-allowed';
      btn.textContent='Prindi arve enne kinnitamist';
    } else {
      btn.disabled=true;
      btn.style.background='#f8fafc';
      btn.style.color='#94a3b8';
      btn.style.border='1px solid #e2e8f0';
      btn.style.cursor='not-allowed';
      btn.textContent='Prindi pakikaart enne kinnitamist';
    }
  }

  // ── Print buttons state ───────────────────────────────────────────────────
  function updatePrintBtns(){
    var total=allItems.length;
    var picked=Object.values(pickedState).filter(Boolean).length;
    var allPicked=total===0||picked===total;
    var inv=document.getElementById('vwp-print-invoice-btn');
    var lbl=document.getElementById('vwp-print-label-btn');
    var row=document.getElementById('vwp-print-row');
    if(row) row.style.opacity=allPicked?'1':'0.45';
    if(inv){
      inv.disabled=!allPicked;
      if(invoicePrinted){inv.style.background='rgba(16,185,129,0.12)';inv.style.color='#10b981';inv.style.border='1px solid rgba(16,185,129,0.3)';inv.textContent='✓ Arve';}
      else{inv.style.background='rgba(99,102,241,0.1)';inv.style.color='#818cf8';inv.style.border='1px solid rgba(99,102,241,0.25)';inv.textContent='🖨️ Arve (A4)';}
    }
    if(lbl){
      lbl.disabled=!allPicked;
      if(labelPrinted){lbl.style.background='rgba(16,185,129,0.12)';lbl.style.color='#10b981';lbl.style.border='1px solid rgba(16,185,129,0.3)';lbl.textContent='✓ Kleeps';}
      else{lbl.style.background='rgba(99,102,241,0.08)';lbl.style.color='#818cf8';lbl.style.border='1px solid rgba(99,102,241,0.2)';lbl.textContent='🏷️ Pakikaart';}
    }
  }

  // ── Progress display ──────────────────────────────────────────────────────
  function updateProgress(){
    var total=allItems.length;
    var picked=Object.values(pickedState).filter(Boolean).length;
    var el=document.getElementById('vwp-shop-progress');
    if(el){el.textContent=picked+'/'+total+' kogutud';el.style.color=picked===total?'#10b981':'#64748b';}
    renderStepper();
    updatePrintBtns();
    updatePackBtn();
  }

  // ── Row UI update ─────────────────────────────────────────────────────────
  function setRowUI(itemId, picked, qty){
    var row=document.getElementById('shop-row-'+itemId);
    var chk=document.getElementById('shop-check-'+itemId);
    var qtyInp=document.getElementById('qty-'+itemId);
    var qtyDisp=document.getElementById('qty-display-'+itemId);
    if(row){
      row.style.background=picked?'rgba(16,185,129,0.08)':'#fff';
      row.style.border='1px solid '+(picked?'rgba(16,185,129,0.25)':'#e2e8f0');
      var nameEl=row.querySelector('[style*="font-weight:600;color:#1e293b;font-size:14px"]');
      if(nameEl){nameEl.style.textDecoration=picked?'line-through':'none';nameEl.style.opacity=picked?'0.6':'1';}
    }
    if(chk){
      chk.style.background=picked?'#10b981':'transparent';
      chk.style.borderColor=picked?'#10b981':'#e2e8f0';
      chk.textContent=picked?'✓':'';
    }
    if(qtyInp){
      var orig=parseFloat(row?row.dataset.origQty:qtyInp.max)||1;
      var cur=qty!==undefined?qty:parseFloat(qtyInp.value);
      qtyInp.value=cur;
      var mod=cur<orig;
      qtyInp.style.background=mod?'#fef9c3':'#f8fafc';
      qtyInp.style.borderColor=mod?'#f59e0b':'#e2e8f0';
      qtyInp.style.color=mod?'#d97706':'#1e293b';
    }
    if(qtyDisp&&qty!==undefined){
      var orig2=parseFloat(row?row.dataset.origQty:qty)||qty;
      var mod2=qty<orig2;
      qtyDisp.textContent=qty+' tk'+(mod2?' ('+orig2+')':'');
      qtyDisp.style.color=mod2?'#f59e0b':'#1e293b';
    }
  }

  // ── Toggle pick ───────────────────────────────────────────────────────────
  window.togglePickRow=function(itemId){
    var newPicked=!pickedState[itemId];
    pickedState[itemId]=newPicked;
    setRowUI(itemId,newPicked,qtyState[itemId]);
    lsSavePicked();
    updateProgress();
    // Send to server
    var fd=new FormData();fd.append('action','vesho_worker_pick_item');fd.append('nonce',NONCE);
    fd.append('item_id',itemId);fd.append('picked',newPicked?1:0);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(!d.success){
        pickedState[itemId]=!newPicked;
        setRowUI(itemId,!newPicked,qtyState[itemId]);
        lsSavePicked();
        updateProgress();
      }
    });
  };

  // ── Quantity adjuster ─────────────────────────────────────────────────────
  window.adjQty=function(itemId,delta){
    var inp=document.getElementById('qty-'+itemId);
    var row=document.getElementById('shop-row-'+itemId);
    if(!inp) return;
    var orig=parseFloat(row?row.dataset.origQty:inp.max)||1;
    var cur=parseFloat(inp.value)||0;
    var next=Math.min(orig,Math.max(0,cur+delta));
    qtyState[itemId]=next;
    setRowUI(itemId,pickedState[itemId],next);
    lsSaveQty();
    if(next>0&&!pickedState[itemId]){pickedState[itemId]=true;setRowUI(itemId,true,next);lsSavePicked();}
    else if(next===0&&pickedState[itemId]){pickedState[itemId]=false;setRowUI(itemId,false,next);lsSavePicked();}
    updateProgress();
  };

  window.onQtyChange=function(itemId){
    var inp=document.getElementById('qty-'+itemId);
    var row=document.getElementById('shop-row-'+itemId);
    if(!inp||!row) return;
    var orig=parseFloat(row.dataset.origQty)||parseFloat(inp.max)||1;
    var cur=parseFloat(inp.value)||0;
    qtyState[itemId]=cur;
    setRowUI(itemId,pickedState[itemId],cur);
    lsSaveQty();
    if(cur>0&&!pickedState[itemId]){pickedState[itemId]=true;setRowUI(itemId,true,cur);lsSavePicked();}
    else if(cur===0&&pickedState[itemId]){pickedState[itemId]=false;setRowUI(itemId,false,cur);lsSavePicked();}
    updateProgress();
  };

  // ── EAN scan ──────────────────────────────────────────────────────────────
  var eanInp=document.getElementById('vw-ean-scan');
  var feedback=document.getElementById('vw-scan-feedback');
  function handleEan(ean){
    ean=(ean||'').trim();
    if(!ean) return;
    var idx=allItems.findIndex(function(it){return it.ean&&it.ean.trim()===ean;});
    if(eanInp) eanInp.value='';
    if(idx>=0){
      var it=allItems[idx];
      // Auto-set qty to original (matches 3006 WorkerPortal.jsx EAN scan behaviour)
      var origQty=it.qty;
      qtyState[it.id]=origQty;
      pickedState[it.id]=true;
      setRowUI(it.id,true,origQty);
      lsSavePicked();lsSaveQty();
      updateProgress();
      var row=document.getElementById('shop-row-'+it.id);
      if(row) row.scrollIntoView({behavior:'smooth',block:'center'});
      if(feedback){feedback.textContent='✓ '+it.name+' — '+origQty+' tk';feedback.style.color='#10b981';}
    } else {
      if(feedback){feedback.textContent='✗ EAN ei vasta ühelegi tootele';feedback.style.color='#ef4444';}
    }
    setTimeout(function(){if(feedback)feedback.textContent='';if(eanInp)eanInp.focus();},1500);
  }
  if(eanInp){
    eanInp.addEventListener('keydown',function(e){if(e.key==='Enter'){handleEan(this.value);}});
    eanInp.addEventListener('change',function(){handleEan(this.value);});
  }
  var eanCameraBtn=document.getElementById('vw-ean-camera-btn');
  if(eanCameraBtn){
    eanCameraBtn.addEventListener('click',function(){
      if(typeof window.VeshoScanner==='undefined'){alert('Scanner ei ole saadaval');return;}
      window.VeshoScanner.open({title:'Skänni toote EAN',autoConfirm:true,manualInput:false,onResult:function(code){handleEan(code);}});
    });
  }

  // ── Print invoice (3006-style) ────────────────────────────────────────────
  function printInvoice(){
    var items=allItems.map(function(it,idx){
      return Object.assign({},it,{quantity:qtyState[it.id]!==undefined?qtyState[it.id]:it.qty});
    });
    var name=CLIENT_NAME||'—';
    var total=items.reduce(function(s,it){return s+it.quantity*it.price;},0);
    var rows=items.map(function(it){
      return '<tr><td>'+it.name+'</td><td style="text-align:center">'+it.quantity+'</td><td style="text-align:right">'+Number(it.price).toFixed(2)+' €</td><td style="text-align:right;font-weight:600">'+(it.quantity*it.price).toFixed(2)+' €</td></tr>';
    }).join('');
    var w=window.open('','_blank','width=760,height=960');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:"Helvetica Neue",Arial,sans-serif;background:#fff;color:#111;font-size:13px}.page{max-width:740px;margin:0 auto;padding:48px 48px 40px}.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:40px}.co-name{font-size:26px;font-weight:900;letter-spacing:-0.04em;color:#111}.co-meta{font-size:11px;color:#888;line-height:1.8;margin-top:2px}.doc-info{text-align:right}.doc-label{font-size:10px;text-transform:uppercase;letter-spacing:0.12em;color:#aaa;font-weight:600;margin-bottom:6px}.doc-num{font-size:28px;font-weight:900;color:#00b4c8;letter-spacing:-0.03em}.doc-date{font-size:11px;color:#888;margin-top:4px}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:36px}.info-block{background:#f8f8f8;border-radius:10px;padding:16px 18px}.info-label{font-size:9px;text-transform:uppercase;letter-spacing:0.14em;color:#aaa;font-weight:700;margin-bottom:8px}.info-name{font-size:15px;font-weight:800;margin-bottom:4px}.info-meta{font-size:12px;color:#555;line-height:1.8}table{width:100%;border-collapse:collapse;margin-bottom:0}.thead tr{border-bottom:2px solid #111}th{padding:10px 12px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;color:#555}tbody tr{border-bottom:1px solid #ececec}td{padding:11px 12px;font-size:13px}.totals{margin-top:20px;display:flex;justify-content:flex-end}.totals-box{min-width:200px}.total-final{display:flex;justify-content:space-between;padding:12px 16px;background:#111;color:#fff;border-radius:8px;margin-top:8px;font-size:15px;font-weight:800}.footer{margin-top:40px;padding-top:14px;border-top:1px solid #e5e5e5;display:flex;justify-content:space-between;font-size:10px;color:#bbb}@media print{.page{padding:28px 32px}}</style></head><body><div class="page"><div class="header"><div><div class="co-name">'+CO_NAME+'</div><div class="co-meta">'+CO_EMAIL+(CO_PHONE?'<br>'+CO_PHONE:'')+'</div></div><div class="doc-info"><div class="doc-label">Tellimus</div><div class="doc-num">'+ORDER_NUM+'</div><div class="doc-date">'+new Date(ORDER_DATE||Date.now()).toLocaleDateString("et-EE",{day:"2-digit",month:"long",year:"numeric"})+'</div></div></div><div class="two-col"><div class="info-block"><div class="info-label">Klient</div><div class="info-name">'+name+'</div><div class="info-meta">'+CLIENT_EMAIL+(CLIENT_PHONE?'<br>📞 '+CLIENT_PHONE:'')+'</div></div>'+(DELIVERY_ADDR?'<div class="info-block"><div class="info-label">Tarneaadress</div><div class="info-meta" style="font-size:13px;color:#333;line-height:1.7">'+DELIVERY_ADDR+'</div></div>':'<div></div>')+'</div><table><thead class="thead"><tr><th>Toode</th><th style="text-align:center;width:80px">Kogus</th><th style="text-align:right;width:100px">Ühikuhind</th><th style="text-align:right;width:100px">Kokku</th></tr></thead><tbody>'+rows+'</tbody></table><div class="totals"><div class="totals-box"><div class="total-final"><span>Kokku</span><span>'+total.toFixed(2)+' €</span></div></div></div>'+(ORDER_NOTES?'<div style="margin-top:24px;padding:12px 16px;background:#fffbeb;border-radius:8px;border-left:3px solid #f59e0b;font-size:12px;color:#555"><b>Märkused:</b> '+ORDER_NOTES+'</div>':'')+'<div class="footer"><span>'+CO_NAME+'</span><span>'+ORDER_NUM+'</span></div></div><script>window.onload=()=>setTimeout(()=>window.print(),200)<\/script></body></html>');
    w.document.close();
    invoicePrinted=true;
    updatePrintBtns();
    renderStepper();
    updatePackBtn();
  }

  // ── Print label ───────────────────────────────────────────────────────────
  function printLabel(){
    var name=CLIENT_NAME||'—';
    var w=window.open('','_blank','width=520,height=440');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:"Helvetica Neue",Arial,sans-serif;background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh}.label{display:inline-block;border:2px solid #111;border-radius:8px;padding:14px 16px;text-align:center}.from-name{font-size:11px;font-weight:700;color:#555;margin-bottom:2px}.divider{border:none;border-top:1px dashed #ccc;margin:10px 0}.to-name{font-size:20px;font-weight:900;margin:6px 0 4px}.to-addr{font-size:12px;color:#333;line-height:1.5}.to-phone{font-size:11px;color:#555;margin-top:4px}.ref-num{font-weight:800;font-size:13px;color:#111;margin-bottom:8px}@media print{body{min-height:auto}}</style></head><body><div class="label"><div class="from-name">'+CO_NAME+'</div><hr class="divider"><div class="to-name">'+name+'</div><div class="to-addr">'+DELIVERY_ADDR+'</div>'+(CLIENT_PHONE?'<div class="to-phone">📞 '+CLIENT_PHONE+'</div>':'')+'<hr class="divider"><div class="ref-num">'+ORDER_NUM+'</div><svg id="b"></svg></div><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script><script>window.onload=()=>{JsBarcode("#b","'+ORDER_NUM+'",{format:"CODE39",width:2,height:55,displayValue:true,fontSize:13,margin:4});setTimeout(()=>window.print(),300)}<\/script></body></html>');
    w.document.close();
    labelPrinted=true;
    updatePrintBtns();
    renderStepper();
    updatePackBtn();
  }

  var invBtn=document.getElementById('vwp-print-invoice-btn');
  var lblBtn=document.getElementById('vwp-print-label-btn');
  if(invBtn) invBtn.addEventListener('click',function(){if(!this.disabled) printInvoice();});
  if(lblBtn) lblBtn.addEventListener('click',function(){if(!this.disabled) printLabel();});

  // ── Claim order ───────────────────────────────────────────────────────────
  var claimBtn=document.getElementById('vwp-claim-btn');
  if(claimBtn) claimBtn.addEventListener('click',function(){
    this.disabled=true;this.textContent='⏳ Laadin...';
    var fd=new FormData();fd.append('action','vesho_worker_claim_order');fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success) location.reload();
      else{showMsg(false,d.data?.message||'Viga');claimBtn.disabled=false;claimBtn.textContent='▶ Alusta';}
    });
  });

  // ── Release order ─────────────────────────────────────────────────────────
  var relBtn=document.getElementById('vwp-release-btn');
  if(relBtn) relBtn.addEventListener('click',function(){
    if(!confirm('Loobud tellimusest? See läheb teise töötaja järjekorda.')) return;
    var fd=new FormData();fd.append('action','vesho_worker_release_order');fd.append('nonce',NONCE);fd.append('order_id',this.dataset.id);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){lsClear();location.reload();}
    });
  });

  // ── Pack order ────────────────────────────────────────────────────────────
  var packBtn=document.getElementById('vwp-pack-btn');
  if(packBtn) packBtn.addEventListener('click',function(){
    if(this.disabled) return;
    this.disabled=true;this.textContent='⏳ Töötlen...';
    var fd=new FormData();fd.append('action','vesho_worker_pack_order');fd.append('nonce',NONCE);fd.append('order_id',this.dataset.id);
    allItems.forEach(function(it){
      fd.append('picked_qtys['+it.id+']',qtyState[it.id]!==undefined?qtyState[it.id]:it.qty);
    });
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      if(d.success){
        lsClear();
        // Show done screen
        var overlay=document.getElementById('vwp-shop-overlay');
        var modal=document.getElementById('vwp-shop-modal');
        if(overlay) overlay.style.display='none';
        if(modal) modal.style.display='none';
        var queue=document.getElementById('vwp-queue-screen');
        if(queue) queue.style.display='none';
        var done=document.getElementById('vwp-done-screen');
        if(done) done.style.display='block';
        var doneMsg=document.getElementById('vwp-done-msg');
        if(doneMsg) doneMsg.textContent=ORDER_NUM+' on komplekteeritud ja ootab saatmist.';
        // Peida nav badge kohe — tellimus on komplekteeritud
        var navBadge=document.getElementById('vwp-nav-badge-tellimused');
        if(navBadge) navBadge.style.display='none';
        // Load next count
        loadShopCount(function(count){
          var nextBtn=document.getElementById('vwp-next-btn');
          if(!nextBtn) return;
          if(count>0){
            nextBtn.disabled=false;
            nextBtn.style.background='rgba(99,102,241,0.15)';
            nextBtn.style.color='#818cf8';
            nextBtn.style.border='1px solid rgba(99,102,241,0.35)';
            nextBtn.textContent='▶ Järgmine tellimus ('+count+')';
            nextBtn.addEventListener('click',function(){this.disabled=true;this.textContent='⏳ Laadin...';
              var fd2=new FormData();fd2.append('action','vesho_worker_claim_order');fd2.append('nonce',NONCE);
              fetch(AJAX,{method:'POST',body:fd2}).then(r=>r.json()).then(d2=>{if(d2.success)location.reload();});
            });
          } else {
            nextBtn.disabled=true;
            nextBtn.style.background='#f8fafc';
            nextBtn.style.color='#94a3b8';
            nextBtn.style.border='1px solid #e2e8f0';
            nextBtn.textContent='— Pole rohkem tellimusi';
          }
        });
      } else {
        packBtn.disabled=false;
        updatePackBtn();
        showMsg(false,d.data?.message||'Viga');
      }
    });
  });

  // ── Load shop count via AJAX ──────────────────────────────────────────────
  function loadShopCount(cb){
    var fd=new FormData();fd.append('action','vesho_worker_shop_count');fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      if(d.success&&cb) cb(d.data.count);
    }).catch(function(){if(cb) cb(0);});
  }

  function showMsg(ok,txt){
    var m=document.getElementById('vwp-shop-msg');
    if(!m) return;
    m.style.display='block';
    m.className='vwp-msg '+(ok?'success':'error');
    m.textContent=txt;
    setTimeout(function(){m.style.display='none';},4000);
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  if(CURRENT_ORDER_ID){
    updateProgress();
  }
})();
</script>
        <?php
    }

    // ── Shared: detail modal HTML ─────────────────────────────────────────────

    private static function orders_detail_modal() {
        return '
<div id="vwp-detail-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px;max-width:480px;width:90%;max-height:80vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById(\'vwp-detail-modal\').style.display=\'none\'" style="position:absolute;top:12px;right:12px;background:none;border:none;font-size:18px;cursor:pointer;color:#64748b">✕</button>
    <h3 id="vwp-dm-title" style="margin:0 0 20px;font-size:17px;font-weight:700;padding-right:32px"></h3>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b;width:40%">Klient</td><td id="vwp-dm-client" style="padding:8px 0;font-weight:600"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Seade</td><td id="vwp-dm-device" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Töö liik</td><td id="vwp-dm-service" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Staatus</td><td id="vwp-dm-status" style="padding:8px 0"></td></tr>
      <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:8px 0;color:#64748b">Kuupäev</td><td id="vwp-dm-date" style="padding:8px 0"></td></tr>
      <tr><td style="padding:8px 0;color:#64748b">Prioriteet</td><td id="vwp-dm-priority" style="padding:8px 0"></td></tr>
    </table>
    <div id="vwp-dm-desc-wrap" style="margin-top:14px;display:none"><div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-bottom:6px">Kirjeldus</div><p id="vwp-dm-desc" style="margin:0;font-size:13px;color:#1e293b;line-height:1.6"></p></div>
    <div id="vwp-dm-notes-wrap" style="margin-top:12px;display:none"><div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#64748b;margin-bottom:6px">Märkused</div><p id="vwp-dm-notes" style="margin:0;font-size:13px;color:#1e293b;line-height:1.6"></p></div>
  </div>
</div>';
    }

    private static function detail_modal_js() {
        return '
  document.querySelectorAll(".vwp-detail-btn").forEach(btn=>{
    btn.addEventListener("click",()=>{
      var d=btn.dataset;
      document.getElementById("vwp-dm-title").textContent=d.title;
      document.getElementById("vwp-dm-client").textContent=d.client;
      document.getElementById("vwp-dm-device").textContent=d.device||"—";
      document.getElementById("vwp-dm-service").textContent=d.service;
      document.getElementById("vwp-dm-date").textContent=d.date;
      document.getElementById("vwp-dm-priority").textContent=d.priority;
      var sm={pending:"Ootel",assigned:"Määratud",in_progress:"Pooleli",completed:"Lõpetatud",cancelled:"Tühistatud"};
      document.getElementById("vwp-dm-status").textContent=sm[d.status]||d.status;
      var dw=document.getElementById("vwp-dm-desc-wrap");
      if(d.desc){dw.style.display="";document.getElementById("vwp-dm-desc").textContent=d.desc;}else dw.style.display="none";
      var nw=document.getElementById("vwp-dm-notes-wrap");
      if(d.notes){nw.style.display="";document.getElementById("vwp-dm-notes").textContent=d.notes;}else nw.style.display="none";
      document.getElementById("vwp-detail-modal").style.display="flex";
    });
  });
  document.getElementById("vwp-detail-modal")?.addEventListener("click",function(e){if(e.target===this)this.style.display="none";});';
    }

    // ── Tab: Maintenances (worker) ────────────────────────────────────────────

    private static function tab_maintenances_worker($wid, $nonce, $ajax) {
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, d.name as device_name, c.name as client_name
             FROM {$wpdb->prefix}vesho_maintenances m
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=d.client_id
             WHERE m.worker_id=%d AND m.status NOT IN ('completed','cancelled')
             ORDER BY m.scheduled_date ASC LIMIT 40", $wid
        ));
        ?>
<h2 class="vwp-section-title">Minu hooldused</h2>
<?php if (empty($items)): ?>
  <div class="vwp-empty">Planeeritud hooldusi pole.</div>
<?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
  <?php foreach ($items as $m): ?>
  <div class="vwp-order-card">
    <div class="vwp-order-header">
      <div>
        <strong><?php echo esc_html($m->device_name ?? '—'); ?></strong>
        <span style="margin-left:8px;color:#64748b;font-size:12px"><?php echo esc_html($m->client_name ?? '—'); ?></span>
        <?php echo self::status_badge($m->status); ?>
      </div>
      <div class="vwp-order-meta">
        <?php echo esc_html($m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'); ?>
      </div>
    </div>
    <?php if ($m->description): ?>
    <div class="vwp-order-body"><p class="vwp-desc"><?php echo esc_html($m->description); ?></p></div>
    <?php endif; ?>
    <div style="padding:0 16px 14px">
      <!-- Notes textarea -->
      <textarea id="vwp-maint-notes-<?php echo $m->id; ?>"
                placeholder="Töötaja märkused (valikuline)..."
                style="width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.15);border-radius:8px;background:rgba(255,255,255,.08);color:#fff;font-size:13px;font-family:inherit;resize:vertical;min-height:70px;box-sizing:border-box;margin-bottom:10px"></textarea>
      <!-- Photo upload -->
      <?php
      $maint_photos = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE maintenance_id=%d ORDER BY created_at ASC",
          $m->id
      ));
      $maint_photo_count = count($maint_photos);
      ?>
      <?php if ($maint_photos): ?>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">
        <?php foreach ($maint_photos as $mp): ?>
        <a href="<?php echo esc_url($mp->filename); ?>" target="_blank" style="display:block;width:56px;height:56px;border-radius:6px;overflow:hidden;border:1.5px solid rgba(255,255,255,.2)">
          <img src="<?php echo esc_url($mp->filename); ?>" style="width:100%;height:100%;object-fit:cover" alt="">
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($maint_photo_count < 5): ?>
      <label style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid rgba(255,255,255,.25);border-radius:6px;cursor:pointer;font-size:12px;color:rgba(255,255,255,.7);margin-bottom:10px">
        <input type="file" accept="image/*" capture="environment" style="display:none"
               onchange="vwpUploadMaintPhoto(this,<?php echo $m->id; ?>)">
        📷 Lisa foto (<?php echo $maint_photo_count; ?>/5)
      </label>
      <?php endif; ?>
      <button class="vwp-btn-primary vwp-maint-complete-btn"
              data-id="<?php echo $m->id; ?>"
              data-nonce="<?php echo $nonce; ?>">&#10003; Hooldus lõpetatud</button>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  window.vwpUploadMaintPhoto=function(input,mid){
    if(!input.files[0]) return;
    var fd=new FormData(); fd.append('action','vesho_upload_maintenance_photo');
    fd.append('nonce','<?php echo wp_create_nonce('vesho_portal_nonce'); ?>');
    fd.append('maintenance_id',mid); fd.append('photo',input.files[0]);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){location.reload();}else{alert(d.data&&d.data.message||'Viga foto üleslaadimisel');}
    });
  };
  document.querySelectorAll('.vwp-maint-complete-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!confirm('Märgi hooldus lõpetatuks?')) return;
      var mid=btn.dataset.id;
      var notes=document.getElementById('vwp-maint-notes-'+mid);
      var fd=new FormData();
      fd.append('action','vesho_worker_complete_maintenance');
      fd.append('nonce',btn.dataset.nonce);
      fd.append('maintenance_id',mid);
      if(notes) fd.append('worker_notes',notes.value);
      btn.disabled=true;
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){ btn.closest('.vwp-order-card').style.opacity='.4'; btn.textContent='✓ Lõpetatud'; }
        else { alert((d.data&&d.data.message)||'Viga'); btn.disabled=false; }
      });
    });
  });
})();
</script>
        <?php
    }

    // ── Tab: Work hours list ──────────────────────────────────────────────────

    private static function tab_workhours($wid) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT wh.*, wo.title as order_title
             FROM {$wpdb->prefix}vesho_work_hours wh
             LEFT JOIN {$wpdb->prefix}vesho_workorders wo ON wo.id=wh.work_order_id
             WHERE wh.worker_id=%d AND wh.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             ORDER BY wh.date DESC, wh.id DESC LIMIT 100", $wid
        ));
        $total = array_sum(array_column((array) $rows, 'hours'));
        ?>
<h2 class="vwp-section-title">Töötunnid (viimased 30 päeva)</h2>
<div class="vwp-stat-grid" style="margin-bottom:20px">
  <div class="vwp-stat-card vwp-stat-green">
    <div class="vwp-stat-label">Kokku tunde</div>
    <div class="vwp-stat-value"><?php echo number_format((float)$total, 1); ?> h</div>
  </div>
</div>
<?php if (empty($rows)): ?>
  <div class="vwp-empty">Töötunde pole logitud.</div>
<?php else: ?>
  <table class="vwp-table">
    <thead><tr><th>Kuupäev</th><th>Töökäsk</th><th>Tunnid</th><th>Kellaaeg</th><th>Kirjeldus</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $h):
      $clock_info = '';
      if ($h->start_time) {
          $clock_info = date('H:i', strtotime($h->start_time));
          if ($h->end_time) $clock_info .= '–' . date('H:i', strtotime($h->end_time));
      }
    ?>
    <tr>
      <td><?php echo esc_html($h->date ? date('d.m.Y', strtotime($h->date)) : '—'); ?></td>
      <td><?php echo esc_html($h->order_title ?? '—'); ?></td>
      <td><strong><?php echo number_format((float)$h->hours, 1); ?> h</strong></td>
      <td style="color:#64748b;font-size:12px"><?php echo esc_html($clock_info); ?></td>
      <td style="color:#64748b"><?php echo esc_html($h->description ?? '—'); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
        <?php
    }

    // ── Tab: Log hours form ───────────────────────────────────────────────────

    private static function tab_log_hours($wid, $nonce, $ajax) {
        global $wpdb;
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title FROM {$wpdb->prefix}vesho_workorders WHERE worker_id=%d AND status NOT IN ('completed','cancelled') ORDER BY scheduled_date ASC LIMIT 50", $wid
        ));
        ?>
<h2 class="vwp-section-title">Lisa töötunnid</h2>
<div class="vwp-card" style="max-width:480px">
  <div id="vwp-log-msg" class="vwp-msg" style="display:none"></div>
  <form id="vwp-log-form" style="display:flex;flex-direction:column;gap:14px">
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    <div class="vwp-field">
      <label>Töökäsk (valikuline)</label>
      <select name="work_order_id" class="vwp-input">
        <option value="">— Ilma töökäsuta —</option>
        <?php foreach ($orders as $wo): ?>
          <option value="<?php echo $wo->id; ?>">#<?php echo $wo->id; ?> <?php echo esc_html($wo->title); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="vwp-field">
      <label>Kuupäev *</label>
      <input type="date" name="work_date" required class="vwp-input" value="<?php echo date('Y-m-d'); ?>">
    </div>
    <div class="vwp-field">
      <label>Tunnid * (nt. 4.5)</label>
      <input type="number" name="hours" required class="vwp-input" step="0.25" min="0.25" max="24" placeholder="4.5">
    </div>
    <div class="vwp-field">
      <label>Kirjeldus</label>
      <textarea name="description" rows="3" class="vwp-input" placeholder="Mida tegid..."></textarea>
    </div>
    <button type="submit" class="vwp-btn-primary" style="align-self:flex-start">&#43; Lisa tunnid</button>
  </form>
</div>
<script>
(function(){
  var form=document.getElementById('vwp-log-form');
  if(!form) return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    var msg=document.getElementById('vwp-log-msg');
    var fd=new FormData(form); fd.append('action','vesho_worker_log_hours');
    var btn=form.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
    fetch('<?php echo $ajax; ?>',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      msg.style.display='block'; msg.className='vwp-msg '+(d.success?'success':'error');
      msg.textContent=(d.data&&d.data.message)||(d.success?'Tunnid salvestatud!':'Viga');
      if(d.success) form.reset();
      btn.disabled=false; btn.textContent='+ Lisa tunnid';
    }).catch(function(){ msg.style.display='block'; msg.className='vwp-msg error'; msg.textContent='Viga'; btn.disabled=false; btn.textContent='+ Lisa tunnid'; });
  });
})();
</script>
        <?php
    }

    // ── AJAX: Login ───────────────────────────────────────────────────────────

    public static function ajax_login() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $name = sanitize_text_field($_POST['worker_name'] ?? '');
        $pin  = sanitize_text_field($_POST['pin'] ?? '');
        if ( empty($name) || empty($pin) ) {
            wp_send_json_error(['message' => 'Sisesta nimi ja PIN-kood']);
        }
        global $wpdb;
        // Match by name (case-insensitive) — verify hashed PIN with wp_check_password
        $worker = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE LOWER(name)=LOWER(%s) AND active=1 LIMIT 1",
            $name
        ));
        if ( ! $worker || empty($worker->pin) || ! wp_check_password($pin, $worker->pin) ) {
            wp_send_json_error(['message' => 'Vale nimi või PIN-kood']);
        }
        // Log in via linked WordPress user
        if ( ! empty($worker->user_id) ) {
            $user = get_user_by('id', (int)$worker->user_id);
            if ( $user && in_array('vesho_worker', (array)$user->roles) ) {
                wp_set_auth_cookie($user->ID, true, is_ssl());
                wp_send_json_success(['message' => 'Tere, ' . esc_html($worker->name) . '!', 'redirect' => home_url('/worker/')]);
            }
        }
        // Fallback: wp_signon (legacy — worker name = WP username, pin = WP password)
        $creds = ['user_login' => $worker->name, 'user_password' => $pin, 'remember' => true];
        $user  = wp_signon($creds, is_ssl());
        if ( is_wp_error($user) ) {
            wp_send_json_error(['message' => 'Konto seadistus puudub. Palun pöördu administraatori poole.']);
        }
        wp_send_json_success(['message' => 'Tere, ' . esc_html($worker->name) . '!', 'redirect' => home_url('/worker/')]);
    }

    // ── AJAX: Logout ──────────────────────────────────────────────────────────

    public static function ajax_logout() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        wp_logout();
        wp_send_json_success(['message' => 'Väljalogimine õnnestus', 'redirect' => home_url('/worker/')]);
    }

    // ── AJAX: Barcode (QR kaart) login ───────────────────────────────────────

    public static function ajax_barcode_login() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $token = sanitize_text_field($_POST['token'] ?? '');
        if (empty($token)) {
            wp_send_json_error(['message' => 'Token puudub']);
        }
        global $wpdb;
        // Workers have a barcode_token field — look up by it
        $worker = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE barcode_token = %s AND active = 1 LIMIT 1",
            $token
        ));
        if (!$worker) {
            wp_send_json_error(['message' => 'Kehtetu kaart — töötajat ei leitud']);
        }
        if (empty($worker->user_id)) {
            wp_send_json_error(['message' => 'Konto seadistus puudub. Pöördu administraatori poole.']);
        }
        $user = get_user_by('id', (int) $worker->user_id);
        if (!$user || !in_array('vesho_worker', (array) $user->roles)) {
            wp_send_json_error(['message' => 'Konto seadistus puudub. Pöördu administraatori poole.']);
        }
        wp_set_auth_cookie($user->ID, true, is_ssl());
        wp_send_json_success([
            'message'  => 'Tere, ' . esc_html($worker->name) . '!',
            'name'     => esc_html($worker->name),
            'redirect' => home_url('/worker/'),
        ]);
    }

    // ── AJAX: Start order ─────────────────────────────────────────────────────

    public static function ajax_start_order() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $wid      = (int) $worker->id;
        $order_id = absint($_POST['order_id'] ?? 0);
        $result   = $wpdb->update(
            $wpdb->prefix . 'vesho_workorders',
            ['status' => 'in_progress'],
            ['id' => $order_id, 'worker_id' => $wid]
        );
        if ($result === false) wp_send_json_error(['message' => 'Uuendamine ebaõnnestus']);
        wp_send_json_success(['message' => 'Töökäsk alustatud']);
    }

    // ── AJAX: Complete order ──────────────────────────────────────────────────

    public static function ajax_complete_order() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $wid        = (int) $worker->id;
        $order_id   = absint($_POST['order_id'] ?? 0);
        $auto_inv   = (int) ($_POST['auto_invoice'] ?? 0);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT wo.*, c.name as client_name FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             WHERE wo.id=%d AND wo.worker_id=%d LIMIT 1", $order_id, $wid
        ));
        if (!$order) wp_send_json_error(['message' => 'Töökäsku ei leitud']);

        $result = $wpdb->update(
            $wpdb->prefix . 'vesho_workorders',
            ['status' => 'completed', 'completed_date' => current_time('Y-m-d')],
            ['id' => $order_id, 'worker_id' => $wid]
        );
        if ($result === false) wp_send_json_error(['message' => 'Uuendamine ebaõnnestus']);

        $invoice_number = null;
        if ($auto_inv && $order->client_id) {
            $mats = $order->materials_used ? json_decode($order->materials_used, true) : [];
            $total = 0;
            $vat_rate = (float) Vesho_CRM_Database::get_setting('vat_rate', '24');
            foreach ($mats as $m) {
                $net = ($m['qty'] ?? 0) * ($m['price'] ?? 0);
                $total += $net * (1 + $vat_rate / 100);
            }
            // Add work hours for this order
            $hours_row = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM(hours) as total_hours FROM {$wpdb->prefix}vesho_work_hours WHERE worker_id=%d AND workorder_id=%d",
                $wid, $order_id
            ));
            $hours = (float) ($hours_row->total_hours ?? 0);
            // Use price from work order if set
            if ($order->price) {
                $total += $order->price;
            } elseif ($hours > 0) {
                $hour_rate = (float) Vesho_CRM_Database::get_setting('hour_rate', '50');
                $net = $hours * $hour_rate;
                $total += $net * (1 + $vat_rate / 100);
            }

            $inv_num = Vesho_CRM_Database::get_next_invoice_number();
            $wpdb->insert($wpdb->prefix.'vesho_invoices', [
                'client_id'      => $order->client_id,
                'invoice_number' => $inv_num,
                'invoice_date'   => current_time('Y-m-d'),
                'due_date'       => date('Y-m-d', strtotime('+14 days')),
                'amount'         => round($total, 2),
                'status'         => 'unpaid',
                'description'    => 'Töökäsk #' . $order_id . ': ' . ($order->title ?? ''),
                'created_at'     => current_time('mysql'),
            ]);
            $inv_id = $wpdb->insert_id;
            if ($inv_id) {
                // Add materials as invoice items
                foreach ($mats as $m) {
                    $net = ($m['qty'] ?? 0) * ($m['price'] ?? 0);
                    $wpdb->insert($wpdb->prefix.'vesho_invoice_items', [
                        'invoice_id'  => $inv_id,
                        'description' => $m['name'],
                        'quantity'    => $m['qty'],
                        'unit_price'  => $m['price'],
                        'vat_rate'    => $vat_rate,
                        'total'       => round($net * (1 + $vat_rate/100), 2),
                    ]);
                }
                // Add hours item if applicable
                if ($hours > 0 && !$order->price) {
                    $hour_rate = (float) Vesho_CRM_Database::get_setting('hour_rate', '50');
                    $net = $hours * $hour_rate;
                    $wpdb->insert($wpdb->prefix.'vesho_invoice_items', [
                        'invoice_id'  => $inv_id,
                        'description' => 'Tööaeg — ' . number_format($hours, 1) . ' h',
                        'quantity'    => $hours,
                        'unit_price'  => $hour_rate,
                        'vat_rate'    => $vat_rate,
                        'total'       => round($net * (1 + $vat_rate/100), 2),
                    ]);
                }
            }
            $invoice_number = $inv_num;
        }

        // ── Auto-schedule next maintenance if device has service_interval ───
        if (!empty($order->device_id)) {
            $device = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, client_id, service_interval FROM {$wpdb->prefix}vesho_devices WHERE id=%d LIMIT 1",
                $order->device_id
            ));
            if ($device && (int)($device->service_interval ?? 0) > 0) {
                $next_date = date('Y-m-d', strtotime('+' . (int)$device->service_interval . ' months'));
                // Only create if no future maintenance already scheduled
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances WHERE device_id=%d AND scheduled_date>=%s AND status NOT IN ('completed','cancelled')",
                    $device->id, date('Y-m-d')
                ));
                if (!$existing) {
                    $wpdb->insert($wpdb->prefix . 'vesho_maintenances', [
                        'device_id'      => $device->id,
                        'scheduled_date' => $next_date,
                        'description'    => 'Automaatne järgmine hooldus (' . $device->service_interval . ' kuu järel)',
                        'status'         => 'scheduled',
                        'created_at'     => current_time('mysql'),
                    ]);
                    vesho_crm_log_activity('auto_schedule', "Automaatne järgmine hooldus seadmele #{$device->id} ({$device->name}) kuupäevaks {$next_date}", 'maintenance', $wpdb->insert_id);
                }
            }
        }

        // ── Work-done email to client ─────────────────────────────────────────
        if ( $order->client_id ) {
            $client_email = $wpdb->get_var( $wpdb->prepare(
                "SELECT email FROM {$wpdb->prefix}vesho_clients WHERE id=%d", $order->client_id
            ) );
            if ( $client_email ) {
                $co      = get_option( 'vesho_company_name', 'Vesho OÜ' );
                $subject = $co . ' — Töö on lõpetatud';
                $body    = "Tere!\n\n";
                $body   .= "Teie töökäsk on lõpetatud.\n";
                if ( !empty($order->title) ) $body .= "Töö: {$order->title}\n";
                $body   .= "Tehnik: {$worker->name}\n";
                $body   .= "Kuupäev: " . current_time('d.m.Y') . "\n\n";
                if ( $invoice_number ) $body .= "Arve number: {$invoice_number}\n\n";
                $body   .= "Küsimuste korral võtke meiega ühendust.\n\nLugupidamisega,\n{$co}";
                wp_mail( $client_email, $subject, $body );
            }
        }

        wp_send_json_success(['message' => 'Töökäsk lõpetatud!', 'invoice_number' => $invoice_number]);
    }

    // ── AJAX: Get inventory ───────────────────────────────────────────────────

    public static function ajax_get_inventory() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $search = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, sku, unit, quantity, sell_price FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND (name LIKE %s OR sku LIKE %s) ORDER BY name ASC LIMIT 60",
                $like, $like
            ));
        } else {
            $items = $wpdb->get_results(
                "SELECT id, name, sku, unit, quantity, sell_price FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY name ASC LIMIT 200"
            );
        }
        wp_send_json_success(['items' => $items]);
    }

    // ── AJAX: Save materials ──────────────────────────────────────────────────

    public static function ajax_save_materials() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $order_id = absint($_POST['order_id'] ?? 0);
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, materials_used FROM {$wpdb->prefix}vesho_workorders WHERE id=%d AND worker_id=%d LIMIT 1",
            $order_id, (int) $worker->id
        ));
        if (!$order) wp_send_json_error(['message' => 'Töökäsku ei leitud']);

        $mats = $order->materials_used ? json_decode($order->materials_used, true) : [];
        $mats[] = [
            'id'    => absint($_POST['material_id'] ?? 0),
            'name'  => sanitize_text_field($_POST['material_name'] ?? ''),
            'unit'  => sanitize_text_field($_POST['material_unit'] ?? 'tk'),
            'price' => (float) ($_POST['material_price'] ?? 0),
            'qty'   => (float) ($_POST['qty'] ?? 1),
        ];
        $wpdb->update(
            $wpdb->prefix . 'vesho_workorders',
            ['materials_used' => wp_json_encode($mats)],
            ['id' => $order_id]
        );
        // Deduct from inventory
        $inv_id = absint($_POST['material_id'] ?? 0);
        $qty    = (float) ($_POST['qty'] ?? 1);
        if ($inv_id && $qty > 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = GREATEST(0, quantity - %f) WHERE id=%d",
                $qty, $inv_id
            ));
        }
        wp_send_json_success(['message' => 'Materjal lisatud']);
    }

    // ── AJAX: Save store-bought material ─────────────────────────────────────

    public static function ajax_save_store_material() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $order_id = absint($_POST['order_id'] ?? 0);
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, materials_used FROM {$wpdb->prefix}vesho_workorders WHERE id=%d AND worker_id=%d LIMIT 1",
            $order_id, (int) $worker->id
        ));
        if (!$order) wp_send_json_error(['message' => 'Töökäsku ei leitud']);

        $name  = sanitize_text_field($_POST['name'] ?? '');
        $qty   = (float) ($_POST['qty'] ?? 1);
        $price = (float) ($_POST['price'] ?? 0);
        if (!$name) wp_send_json_error(['message' => 'Nimi on kohustuslik']);

        $mats = $order->materials_used ? json_decode($order->materials_used, true) : [];
        $mats[] = [
            'id'     => 0,
            'name'   => $name,
            'unit'   => 'tk',
            'price'  => $price,
            'qty'    => $qty,
            'source' => 'store',
        ];
        $wpdb->update(
            $wpdb->prefix . 'vesho_workorders',
            ['materials_used' => wp_json_encode($mats)],
            ['id' => $order_id]
        );
        wp_send_json_success(['message' => 'Materjal lisatud']);
    }

    // ── AJAX: Complete maintenance ────────────────────────────────────────────

    public static function ajax_complete_maintenance() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $wid         = (int) $worker->id;
        $mid         = absint($_POST['maintenance_id'] ?? 0);
        $worker_notes = sanitize_textarea_field($_POST['worker_notes'] ?? '');
        $update = ['status' => 'completed', 'completed_date' => current_time('mysql')];
        if ($worker_notes !== '') $update['worker_notes'] = $worker_notes;
        $result = $wpdb->update(
            $wpdb->prefix . 'vesho_maintenances',
            $update,
            ['id' => $mid, 'worker_id' => $wid]
        );
        if ($result === false) wp_send_json_error(['message' => 'Uuendamine ebaõnnestus']);
        wp_send_json_success(['message' => 'Hooldus lõpetatud!']);
    }

    // ── AJAX: Upload maintenance photo ────────────────────────────────────────

    public static function ajax_upload_maintenance_photo() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $mid = absint($_POST['maintenance_id'] ?? 0);
        if (!$mid) wp_send_json_error(['message' => 'Puuduv hooldus ID']);
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorder_photos WHERE maintenance_id=%d", $mid
        ));
        if ($count >= 5) wp_send_json_error(['message' => 'Maksimaalselt 5 fotot']);
        if (empty($_FILES['photo']['tmp_name'])) wp_send_json_error(['message' => 'Fail puudub']);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $_FILES['photo']['name'] = 'maint-' . $mid . '-' . time() . '-' . basename($_FILES['photo']['name']);
        $attachment_id = media_handle_upload('photo', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        $url = wp_get_attachment_url($attachment_id);
        $wpdb->insert($wpdb->prefix . 'vesho_workorder_photos', [
            'workorder_id'   => 0,
            'maintenance_id' => $mid,
            'worker_id'      => (int)$worker->id,
            'filename'       => $url,
            'created_at'     => current_time('mysql'),
        ]);
        wp_send_json_success(['message' => 'Foto lisatud', 'url' => $url]);
    }

    // ── AJAX: Delete photo ────────────────────────────────────────────────────

    public static function ajax_delete_photo() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $photo_id = absint($_POST['photo_id'] ?? 0);
        $photo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE id=%d", $photo_id
        ));
        if (!$photo) wp_send_json_error(['message' => 'Fotot ei leitud']);
        // Only own photos or admin
        if ((int)$photo->worker_id !== (int)$worker->id && !$worker->isAdmin) {
            wp_send_json_error(['message' => 'Puudub õigus']);
        }
        $wpdb->delete($wpdb->prefix . 'vesho_workorder_photos', ['id' => $photo_id]);
        wp_send_json_success(['message' => 'Foto kustutatud']);
    }

    // ── AJAX: Log hours ───────────────────────────────────────────────────────

    public static function ajax_log_hours() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $wid          = (int) $worker->id;
        $work_order_id = absint($_POST['work_order_id'] ?? 0);
        $work_date    = sanitize_text_field($_POST['work_date'] ?? '');
        $hours        = (float) ($_POST['hours'] ?? 0);
        $description  = sanitize_textarea_field($_POST['description'] ?? '');
        if (!$work_date || $hours <= 0) wp_send_json_error(['message' => 'Kuupäev ja tunnid on kohustuslikud']);
        if ($hours > 24) wp_send_json_error(['message' => 'Tunde ei saa olla rohkem kui 24']);

        $data = [
            'worker_id'  => $wid,
            'date'       => $work_date,
            'hours'      => $hours,
            'description'=> $description ?: null,
            'created_at' => current_time('mysql'),
        ];
        if ($work_order_id) $data['work_order_id'] = $work_order_id;

        $wpdb->insert($wpdb->prefix . 'vesho_work_hours', $data);
        if (!$wpdb->insert_id) wp_send_json_error(['message' => 'Salvestamine ebaõnnestus']);
        wp_send_json_success(['message' => 'Tunnid salvestatud!']);
    }

    // ── AJAX: Clock in ────────────────────────────────────────────────────────

    public static function ajax_clock_in() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) {
            $msg = current_user_can('manage_options') ? 'Admin preview: töötajaid pole lisatud. Lisa töötaja CRM → Töötajad all.' : 'Pole sisse logitud';
            wp_send_json_error(['message' => $msg]);
        }
        global $wpdb;
        $wid = (int) $worker->id;

        // Check for existing open entry
        $open = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vesho_work_hours WHERE worker_id=%d AND end_time IS NULL LIMIT 1", $wid
        ));
        if ($open) wp_send_json_error(['message' => 'Oled juba sisse löödud']);

        $now = current_time('mysql');
        $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;
        $row = [
            'worker_id'  => $wid,
            'date'       => current_time('Y-m-d'),
            'hours'      => 0,
            'start_time' => $now,
            'created_at' => $now,
        ];
        if ($lat !== null && $lng !== null && $lat !== 0.0 && $lng !== 0.0) {
            $row['clock_in_lat'] = $lat;
            $row['clock_in_lng'] = $lng;
        }
        $wpdb->insert($wpdb->prefix . 'vesho_work_hours', $row);
        wp_send_json_success(['message' => 'Sisse löödud kell ' . date('H:i', strtotime($now))]);
    }

    // ── AJAX: Clock out ───────────────────────────────────────────────────────

    public static function ajax_clock_out() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) {
            $msg = current_user_can('manage_options') ? 'Admin preview: töötajaid pole lisatud.' : 'Pole sisse logitud';
            wp_send_json_error(['message' => $msg]);
        }
        global $wpdb;
        $wid      = (int) $worker->id;
        $entry_id = absint($_POST['entry_id'] ?? 0);

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_work_hours WHERE id=%d AND worker_id=%d AND end_time IS NULL LIMIT 1",
            $entry_id, $wid
        ));
        if (!$entry) wp_send_json_error(['message' => 'Avatud kirjet ei leitud']);

        $now         = current_time('mysql');
        $start_ts    = strtotime($entry->start_time);
        $end_ts      = strtotime($now);
        $hours_float = round(($end_ts - $start_ts) / 3600, 2);

        $update_data = ['end_time' => $now, 'hours' => max(0.01, $hours_float)];
        $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;
        if ($lat !== null && $lng !== null && $lat !== 0.0 && $lng !== 0.0) {
            $update_data['clock_out_lat'] = $lat;
            $update_data['clock_out_lng'] = $lng;
        }
        $wpdb->update(
            $wpdb->prefix . 'vesho_work_hours',
            $update_data,
            ['id' => $entry_id, 'worker_id' => $wid]
        );
        wp_send_json_success(['message' => sprintf('Välja löödud. Töötasid %.1f tundi.', $hours_float)]);
    }

    // ── AJAX: Scan checkin (QR) ───────────────────────────────────────────────

    public static function ajax_scan_checkin() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $token = sanitize_text_field($_POST['token'] ?? '');
        if (!$token) wp_send_json_error(['message' => 'Token puudub']);
        global $wpdb;
        $worker = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE barcode_token=%s AND active=1 LIMIT 1",
            $token
        ));
        if (!$worker) wp_send_json_error(['message' => 'Vigane QR kood']);
        $wid = (int) $worker->id;
        $now = current_time('mysql');
        // Check open entry
        $open = $wpdb->get_row($wpdb->prepare(
            "SELECT id, start_time FROM {$wpdb->prefix}vesho_work_hours WHERE worker_id=%d AND end_time IS NULL ORDER BY start_time DESC LIMIT 1", $wid
        ));
        if ($open) {
            $hours_float = round((strtotime($now) - strtotime($open->start_time)) / 3600, 2);
            $wpdb->update(
                $wpdb->prefix . 'vesho_work_hours',
                ['end_time' => $now, 'hours' => max(0.01, $hours_float)],
                ['id' => $open->id, 'worker_id' => $wid]
            );
            wp_send_json_success(['action' => 'clock-out', 'message' => sprintf('Välja löödud. Töötasid %.1f tundi.', $hours_float)]);
        } else {
            $wpdb->insert($wpdb->prefix . 'vesho_work_hours', [
                'worker_id'  => $wid,
                'date'       => current_time('Y-m-d'),
                'hours'      => 0,
                'start_time' => $now,
                'created_at' => $now,
            ]);
            wp_send_json_success(['action' => 'clock-in', 'message' => 'Sisse löödud kell ' . date('H:i', strtotime($now))]);
        }
    }

    // ── AJAX: Inventuur ───────────────────────────────────────────────────────

    public static function ajax_get_inv_count() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $count_id = absint($_POST['count_id']??0);
        $wid      = (int) $worker->id;
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT sci.*, scs.name as section_name, inv.name, inv.sku, inv.ean, inv.category, inv.unit,
                    sci.expected_qty, sci.worker_counted, sci.worker_done, scs.id as section_id
             FROM {$wpdb->prefix}vesho_stock_count_items sci
             JOIN {$wpdb->prefix}vesho_stock_count_sections scs ON scs.id=sci.section_id
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id=sci.inventory_id
             WHERE scs.stock_count_id=%d AND (scs.worker_id=%d OR scs.worker_id IS NULL)
             ORDER BY scs.id ASC, inv.category ASC, inv.name ASC", $count_id, $wid
        ));
        wp_send_json_success(['items'=>$items]);
    }

    public static function ajax_save_count_item() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $item_id = absint($_POST['item_id']??0);
        $counted = (float)($_POST['counted']??0);
        $wpdb->update("{$wpdb->prefix}vesho_stock_count_items",
            ['worker_counted'=>$counted], ['id'=>$item_id]);
        wp_send_json_success(['message'=>'Salvestatud']);
    }

    public static function ajax_lock_count_item() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $item_id = absint($_POST['item_id']??0);
        $wpdb->update("{$wpdb->prefix}vesho_stock_count_items",
            ['worker_done'=>1], ['id'=>$item_id]);
        wp_send_json_success(['message'=>'Lukustatud']);
    }

    public static function ajax_complete_stock_section() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $section_id = absint($_POST['section_id']??0);
        if (!$section_id) wp_send_json_error(['message'=>'Vale sektsiooni ID']);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}vesho_stock_count_items SET worker_done=1 WHERE section_id=%d",
            $section_id
        ));
        wp_send_json_success(['message'=>'Osa märgitud lõpetatuks']);
    }

    // ── AJAX: Vastuvõtt ───────────────────────────────────────────────────────

    public static function ajax_get_receipt_items() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker() && !current_user_can('manage_options')) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $receipt_id = absint($_POST['receipt_id']??0);

        // Get existing columns of the items table (safe against missing columns)
        $existing_cols = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_stock_receipt_items`") ?: [];
        $has = fn($c) => in_array($c, $existing_cols, true);

        // Build safe SELECT with fallbacks for columns that may not exist yet
        $name_expr  = $has('product_name') ? "COALESCE(inv.name, sri.product_name)" : "COALESCE(inv.name, '')";
        $sku_expr   = $has('product_sku')  ? "COALESCE(inv.sku, sri.product_sku)"   : "COALESCE(inv.sku, '')";
        $unit_expr  = $has('product_unit') ? "COALESCE(inv.unit, sri.product_unit)" : "COALESCE(inv.unit, 'tk')";
        $ean_expr   = $has('ean')          ? "COALESCE(inv.ean, sri.ean)"           : "inv.ean";

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT sri.*,
                    {$name_expr} AS name,
                    {$sku_expr}  AS sku,
                    {$unit_expr} AS unit,
                    {$ean_expr}  AS ean,
                    inv.selling_price AS inv_selling_price
             FROM {$wpdb->prefix}vesho_stock_receipt_items sri
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id=sri.inventory_id
             WHERE sri.receipt_id=%d
             ORDER BY sri.id ASC", $receipt_id
        ));

        $debug_error = $wpdb->last_error;
        $debug_cols  = $existing_cols;

        // Fallback: if no items in detail table, read from main receipts row (legacy format)
        if (empty($items)) {
            $sr = $wpdb->get_row($wpdb->prepare(
                "SELECT sr.*, inv.selling_price AS inv_selling_price
                 FROM {$wpdb->prefix}vesho_stock_receipts sr
                 LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id=sr.inventory_id
                 WHERE sr.id=%d", $receipt_id
            ));
            if ($sr && !empty($sr->item_name)) {
                $fake = (object)[
                    'id'           => 'sr_' . $sr->id,
                    'receipt_id'   => $sr->id,
                    'product_name' => $sr->item_name,
                    'product_sku'  => $sr->sku ?? '',
                    'product_ean'  => $sr->ean ?? '',
                    'product_unit' => $sr->unit ?? 'tk',
                    'quantity'     => $sr->quantity ?? 0,
                    'actual_qty'   => $sr->actual_quantity ?? null,
                    'location'     => $sr->location ?? '',
                    'ean'          => $sr->ean ?? '',
                    'name'         => $sr->item_name,
                    'sku'          => $sr->sku ?? '',
                    'unit'         => $sr->unit ?? 'tk',
                    'selling_price'=> $sr->selling_price ?? $sr->inv_selling_price ?? null,
                    'inventory_id' => $sr->inventory_id ?? null,
                    '_legacy'      => true,
                ];
                $items = [$fake];
            }
        }

        foreach ($items as &$item) {
            if (!isset($item->actual_qty)) $item->actual_qty = null;
            if (!isset($item->location))   $item->location   = '';
            if (!isset($item->ean))        $item->ean        = '';
            $item->expected_qty   = $item->quantity ?? 0;
            $item->selling_price  = $item->selling_price ?? $item->inv_selling_price ?? null;
        }
        wp_send_json_success(['items'=>$items, '_debug'=>['receipt_id'=>$receipt_id,'cols'=>$debug_cols,'sql_error'=>$debug_error,'count'=>count($items)]]);
    }

    public static function ajax_confirm_receipt() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $item_id  = absint($_POST['item_id']??0);
        $actual   = (float)($_POST['actual_qty']??0);
        $location = sanitize_text_field($_POST['location']??'');
        // Only mark item as received — inventory update happens at admin approval
        $wpdb->update(
            "{$wpdb->prefix}vesho_stock_receipt_items",
            ['actual_qty' => $actual, 'location' => $location],
            ['id' => $item_id]
        );
        wp_send_json_success(['message'=>'Vastu võetud']);
    }

    public static function ajax_submit_batch() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $receipt_id = absint($_POST['receipt_id']??0);
        $items_raw  = sanitize_text_field($_POST['items']??'[]');
        $items      = json_decode(stripslashes($items_raw), true);
        if (!$receipt_id || !is_array($items)) wp_send_json_error(['message'=>'Vigased andmed']);
        // Validate receipt belongs to this worker or is pending
        $receipt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_stock_receipts WHERE id=%d AND status='pending'", $receipt_id
        ));
        if (!$receipt) wp_send_json_error(['message'=>'Saadetist ei leitud või juba kinnitatud']);
        // Update each item
        foreach ($items as $item) {
            $iid = absint($item['id']??0);
            $qty = (float)($item['actual_qty']??0);
            $loc = sanitize_text_field($item['location']??'');
            $ean = sanitize_text_field($item['ean']??'');
            if (!$iid || $qty <= 0) continue;
            $data = ['actual_qty'=>$qty, 'location'=>$loc];
            if ($ean) $data['ean'] = $ean;
            $wpdb->update("{$wpdb->prefix}vesho_stock_receipt_items", $data, ['id'=>$iid, 'receipt_id'=>$receipt_id]);
        }
        // Change receipt status to received
        $wpdb->update(
            "{$wpdb->prefix}vesho_stock_receipts",
            ['status'=>'received', 'worker_id'=>(int)$worker->id, 'worker_name'=>$worker->name],
            ['id'=>$receipt_id]
        );
        wp_send_json_success(['message'=>'Saadetud adminile kinnitamiseks']);
    }

    public static function ajax_add_batch_item() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $receipt_id   = absint($_POST['receipt_id']??0);
        $product_name = sanitize_text_field($_POST['product_name']??'');
        $actual_qty   = (float)($_POST['actual_qty']??0);
        if (!$receipt_id || !$product_name || $actual_qty <= 0)
            wp_send_json_error(['message'=>'Toote nimi ja kogus on kohustuslikud']);
        // Verify receipt access — allow both pending (worker still receiving)
        // and received (worker can still add items after sending to admin, until approved)
        $receipt = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vesho_stock_receipts WHERE id=%d AND status IN ('pending','received')", $receipt_id
        ));
        if (!$receipt) wp_send_json_error(['message'=>'Saadetist ei leitud või vale staatus']);
        $ean          = sanitize_text_field($_POST['product_ean']??'');
        $unit         = sanitize_text_field($_POST['product_unit']??'tk');
        $selling_price= (float)($_POST['selling_price']??0);
        $location     = sanitize_text_field($_POST['location']??'');
        $notes        = sanitize_textarea_field($_POST['notes']??'');
        $wpdb->insert("{$wpdb->prefix}vesho_stock_receipt_items", [
            'receipt_id'    => $receipt_id,
            'product_name'  => $product_name,
            'product_sku'   => sanitize_text_field($_POST['product_sku']??''),
            'product_unit'  => $unit,
            'ean'           => $ean,
            'quantity'      => $actual_qty,
            'actual_qty'    => $actual_qty,
            'selling_price' => $selling_price ?: null,
            'location'      => $location,
            'notes'         => $notes,
        ]);
        wp_send_json_success(['id'=>$wpdb->insert_id]);
    }

    // ── AJAX: Tellimused ──────────────────────────────────────────────────────

    public static function ajax_shop_count() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $count = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE status='processing' AND (worker_id IS NULL OR worker_id=0)"
        );
        wp_send_json_success(['count' => $count]);
    }

    public static function ajax_claim_order() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $order = $wpdb->get_row(
            "SELECT id FROM {$wpdb->prefix}vesho_shop_orders
             WHERE status='processing' AND (worker_id IS NULL OR worker_id=0)
             ORDER BY created_at ASC LIMIT 1"
        );
        if (!$order) wp_send_json_error(['message'=>'Tellimusi pole saadaval']);
        // Claim: assign worker_id, status stays 'processing' (same as 3006)
        $wpdb->update("{$wpdb->prefix}vesho_shop_orders",
            ['worker_id'=>$worker->id],['id'=>$order->id]);
        wp_send_json_success(['message'=>'Tellimus võetud']);
    }

    public static function ajax_release_order() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $order_id = absint($_POST['order_id']??0);
        // Release: only clear worker_id, status stays 'processing' (matches 3006)
        // Use raw query — $wpdb->update() doesn't handle NULL correctly
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}vesho_shop_orders SET worker_id=NULL WHERE id=%d AND worker_id=%d",
            $order_id, (int)$worker->id
        ));
        wp_send_json_success(['message'=>'Tellimus vabastatud']);
    }

    public static function ajax_pick_item() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $item_id = absint($_POST['item_id']??0);
        $picked  = (int)($_POST['picked']??0);
        $wpdb->update("{$wpdb->prefix}vesho_shop_order_items",['picked'=>$picked],['id'=>$item_id]);
        wp_send_json_success(['message'=>'OK']);
    }

    public static function ajax_pack_order() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $order_id   = absint($_POST['order_id']??0);
        $picked_qtys = isset($_POST['picked_qtys']) ? (array)$_POST['picked_qtys'] : [];

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d", $order_id
        ));

        $refund_pending = 0.0;

        foreach ($items as $it) {
            $orig_qty = (float)$it->quantity;
            // Use posted picked_qty if provided, else fall back to picked flag
            if (isset($picked_qtys[$it->id])) {
                $picked_qty = max(0.0, min($orig_qty, (float)$picked_qtys[$it->id]));
            } else {
                $picked_qty = $it->picked ? $orig_qty : 0.0;
            }

            // Save picked_qty and update picked flag
            $wpdb->update(
                "{$wpdb->prefix}vesho_shop_order_items",
                ['picked_qty' => $picked_qty, 'picked' => $picked_qty > 0 ? 1 : 0],
                ['id' => $it->id]
            );

            // Deduct picked quantity from inventory (not original)
            if ($it->inventory_id && $picked_qty > 0) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity=GREATEST(0,quantity-%f) WHERE id=%d",
                    $picked_qty, $it->inventory_id
                ));
            }

            // Calculate partial refund
            $diff = $orig_qty - $picked_qty;
            if ($diff > 0) {
                $refund_pending += $diff * (float)$it->unit_price;
            }
        }

        $order_update = ['status' => 'confirmed', 'updated_at' => current_time('mysql'), 'packed_at' => current_time('mysql')];
        if ($refund_pending > 0.001) {
            $order_update['refund_pending_amount'] = round($refund_pending, 2);
        }

        $wpdb->update("{$wpdb->prefix}vesho_shop_orders", $order_update,
            ['id' => $order_id, 'worker_id' => $worker->id]);

        $msg = $refund_pending > 0.001
            ? sprintf('Tellimus pakitud! Osaline tagasimakse %.2f € ootel.', $refund_pending)
            : 'Tellimus pakitud ja lõpetatud!';

        wp_send_json_success(['message' => $msg, 'refund_pending' => $refund_pending]);
    }

    // ── Warehouse location occupancy check ────────────────────────────────────

    public static function ajax_check_warehouse_location() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $code = strtoupper(sanitize_text_field($_POST['location_code'] ?? ''));
        if (!$code) wp_send_json_error(['message' => 'Kood puudub']);
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, sku FROM {$wpdb->prefix}vesho_inventory WHERE location=%s LIMIT 1", $code
        ));
        if ($item) {
            wp_send_json_success(['occupied' => true, 'item_name' => $item->name, 'item_sku' => $item->sku ?? '']);
        } else {
            wp_send_json_success(['occupied' => false]);
        }
    }

    // ── AJAX: EAN lookup in inventory ─────────────────────────────────────────

    public static function ajax_lookup_ean() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $ean = sanitize_text_field($_POST['ean'] ?? '');
        if (!$ean) wp_send_json_error(['message' => 'EAN puudub']);
        // Check if ean column exists
        $inv_cols = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_inventory`") ?: [];
        if (!in_array('ean', $inv_cols, true)) {
            wp_send_json_error(['message' => 'EAN ei leitud', 'not_found' => true]);
        }
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, sku, unit, selling_price, ean FROM {$wpdb->prefix}vesho_inventory WHERE ean=%s AND quantity > 0 LIMIT 1",
            $ean
        ));
        if (!$item) {
            // Try with any quantity
            $item = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, sku, unit, selling_price, ean FROM {$wpdb->prefix}vesho_inventory WHERE ean=%s LIMIT 1",
                $ean
            ));
        }
        if (!$item) {
            wp_send_json_error(['message' => 'EAN ei leitud', 'not_found' => true]);
        }
        wp_send_json_success([
            'id'            => (int) $item->id,
            'name'          => $item->name,
            'sku'           => $item->sku ?? '',
            'unit'          => $item->unit ?? 'tk',
            'selling_price' => $item->selling_price ? (float) $item->selling_price : null,
            'ean'           => $item->ean,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function status_badge($status) {
        $map = [
            'pending'     => ['Ootel',         '#fef9c3', '#b45309'],
            'assigned'    => ['Määratud',      '#dbeafe', '#1d4ed8'],
            'in_progress' => ['Töös',          '#e0f2fe', '#0369a1'],
            'completed'   => ['Lõpetatud',     '#dcfce7', '#16a34a'],
            'cancelled'   => ['Tühistatud',    '#f3f4f6', '#6b7280'],
            'scheduled'   => ['Planeeritud',   '#dbeafe', '#1d4ed8'],
        ];
        $info = $map[$status] ?? [esc_html($status), '#f3f4f6', '#6b7280'];
        return sprintf(
            '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:%s;color:%s;margin-left:6px">%s</span>',
            esc_attr($info[1]), esc_attr($info[2]), esc_html($info[0])
        );
    }

    // ── CSS ───────────────────────────────────────────────────────────────────

    private static function portal_css() {
        return '
/* === Vesho Worker Portal CSS === */
.vwp-wrap{display:flex;min-height:100vh;font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:14px;color:#1e293b;background:#f8fafc}
.vwp-sidebar{width:240px;min-height:100vh;background:#1e293b;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0}
.vwp-sidebar-logo{padding:24px 20px 12px;font-size:22px;font-weight:800;color:#fff;letter-spacing:-.5px}
.vwp-sidebar-logo span{color:#f59e0b}
.vwp-sidebar-label{padding:4px 20px 8px;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.35)}
.vwp-nav{list-style:none;margin:0;padding:0 8px}
.vwp-nav li{margin-bottom:2px}
.vwp-nav-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;font-weight:500;transition:background .15s,color .15s}
.vwp-nav-link:hover,.vwp-nav-link.active{background:rgba(245,158,11,.15);color:#fff}
.vwp-nav-link.active{color:#f59e0b}
.vwp-nav-icon{width:18px;text-align:center;font-size:15px}
.vwp-sidebar-footer{margin-top:auto;padding:16px 16px 20px;border-top:1px solid rgba(255,255,255,.08)}
.vwp-sidebar-user{display:flex;gap:10px;align-items:center;margin-bottom:12px}
.vwp-avatar{width:34px;height:34px;border-radius:50%;background:#f59e0b;color:#fff;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.vwp-sidebar-name{color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.vwp-sidebar-role{color:rgba(255,255,255,.4);font-size:11px}
.vwp-logout-link{display:block;color:rgba(255,255,255,.4);font-size:12px;text-decoration:none;padding:4px 0;transition:color .15s}
.vwp-logout-link:hover{color:#fff}
.vwp-main{flex:1;display:flex;flex-direction:column;min-width:0}
.vwp-topbar{height:56px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;padding:0 24px;gap:12px;position:sticky;top:0;z-index:50}
.vwp-topbar-title{font-size:16px;font-weight:600;flex:1}
.vwp-topbar-right{font-size:13px;color:#64748b}
.vwp-hamburger{display:none;background:none;border:none;font-size:20px;cursor:pointer;color:#1e293b;padding:4px 8px}
.vwp-content{padding:28px;max-width:1100px;width:100%}
.vwp-page-header{margin-bottom:24px}
.vwp-page-header h1{font-size:22px;font-weight:700;margin:0 0 4px}
.vwp-section-title{font-size:16px;font-weight:600;margin:0 0 12px}
.vwp-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px}
.vwp-stat-card{background:#fff;border-radius:12px;padding:20px;border-left:4px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vwp-stat-blue{border-left-color:#3b82f6}
.vwp-stat-green{border-left-color:#10b981}
.vwp-stat-orange{border-left-color:#f59e0b}
.vwp-stat-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px}
.vwp-stat-value{font-size:1.6rem;font-weight:700;color:#1e293b}
.vwp-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-bottom:16px}
.vwp-order-card{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden}
.vwp-order-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:8px}
.vwp-order-meta{font-size:12px;color:#64748b;white-space:nowrap}
.vwp-order-body{padding:12px 16px}
.vwp-desc{margin:0 0 10px;color:#64748b;font-size:13px}
.vwp-order-actions{display:flex;gap:8px;flex-wrap:wrap}
.vwp-priority{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;color:#fff;margin-left:6px}
.vwp-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vwp-table thead tr{background:#f8fafc}
.vwp-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0}
.vwp-table td{padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px}
.vwp-table tbody tr:last-child td{border-bottom:none}
.vwp-table tbody tr:nth-child(even){background:#fafafa}
.vwp-table tbody tr:hover{background:#f1f5f9}
.vwp-empty{padding:32px;text-align:center;color:#94a3b8;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vwp-btn-primary{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s}
.vwp-btn-primary:hover{background:#d97706}
.vwp-btn-outline{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#fff;color:#1e293b;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:border-color .15s}
.vwp-btn-outline:hover{border-color:#94a3b8}
.vwp-field{display:flex;flex-direction:column;gap:4px}
.vwp-field label{font-size:12px;font-weight:600;color:#64748b}
.vwp-input{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;font-family:inherit;transition:border-color .15s;outline:none}
.vwp-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.12)}
.vwp-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px}
.vwp-msg.success{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.vwp-msg.error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
/* Login card */
.vwp-login-wrap{display:flex;align-items:center;justify-content:center;min-height:60vh;padding:24px}
.vwp-login-card{background:#fff;border-radius:16px;padding:36px;width:100%;max-width:380px;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.vwp-login-logo{font-size:24px;font-weight:800;color:#1e293b;margin-bottom:4px;letter-spacing:-.5px}
.vwp-login-logo span{color:#f59e0b}
.vwp-login-title{font-size:18px;font-weight:600;color:#1e293b;margin:0 0 24px}
/* Responsive */
@media(max-width:768px){
  html:has(.vwp-wrap),body:has(.vwp-wrap){overflow-x:hidden}
  body:has(.vwp-wrap) #page,body:has(.vwp-wrap) #primary,body:has(.vwp-wrap) #main,body:has(.vwp-wrap) .entry-content,body:has(.vwp-wrap) article{padding:0 !important;margin:0 !important}
  .vwp-wrap{overflow-x:hidden}
  .vwp-sidebar{width:52px;position:fixed;left:0;top:var(--site-top-height,0px);height:calc(100svh - var(--site-top-height,0px));transition:width .22s ease;overflow:hidden;z-index:200;flex-shrink:0}
  .vwp-sidebar.open{width:240px;box-shadow:4px 0 24px rgba(0,0,0,.3)}
  .vwp-main{margin-left:52px;overflow-x:hidden;min-width:0}
  .vwp-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:150}
  .vwp-backdrop.active{display:block}
  body.vwp-sidebar-open{overflow:hidden}
  .vwp-sidebar-logo{padding:12px 0;justify-content:center;overflow:hidden}
  .vwp-sidebar-logo img{max-width:32px;max-height:32px;margin:0 auto}
  .vwp-sidebar.open .vwp-sidebar-logo{padding:20px 20px 10px;justify-content:flex-start}
  .vwp-sidebar.open .vwp-sidebar-logo img{max-width:160px;max-height:40px;margin:0}
  .vwp-sidebar-logo-text{display:none}
  .vwp-sidebar.open .vwp-sidebar-logo-text{display:block}
  .vwp-sidebar-label{display:none}
  .vwp-sidebar.open .vwp-sidebar-label{display:block}
  .vwp-nav{padding:0 4px}
  .vwp-nav-link{font-size:0;justify-content:center;padding:10px 0}
  .vwp-nav-link .vwp-nav-icon{font-size:17px;width:auto}
  .vwp-sidebar.open .vwp-nav{padding:0 8px}
  .vwp-sidebar.open .vwp-nav-link{font-size:13px;justify-content:flex-start;padding:9px 12px}
  .vwp-sidebar.open .vwp-nav-link .vwp-nav-icon{width:18px;font-size:15px}
  .vwp-sidebar-user{justify-content:center}
  .vwp-sidebar.open .vwp-sidebar-user{justify-content:flex-start}
  .vwp-sidebar-name,.vwp-sidebar-role,.vwp-logout-link{display:none}
  .vwp-sidebar.open .vwp-sidebar-name,.vwp-sidebar.open .vwp-sidebar-role,.vwp-sidebar.open .vwp-logout-link{display:block}
  .vwp-hamburger{display:block}
  .vwp-topbar{padding:0 10px;position:sticky;top:0;z-index:50}
  .vwp-topbar-right{font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px}
  .vwp-content{padding:16px;overflow-x:hidden}
  .vwp-stat-grid{grid-template-columns:1fr 1fr}
  .vwp-table{width:100%;table-layout:fixed}
  .vwp-table th,.vwp-table td{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:8px}
  .vwp-table th:nth-child(n+4),.vwp-table td:nth-child(n+4){display:none}
  .vwp-table th:nth-child(3),.vwp-table td:nth-child(3){max-width:80px}
  .vwp-table th:last-child,.vwp-table td:last-child{display:table-cell !important;width:60px;text-align:right}
}
';
    }
}
