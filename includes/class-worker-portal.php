<?php defined('ABSPATH') || exit;

class Vesho_CRM_Worker_Portal {

    public static function init() {
        add_shortcode('vesho_worker_portal', [__CLASS__, 'shortcode']);

        $nopriv = ['vesho_worker_login', 'vesho_worker_logout', 'vesho_worker_scan_checkin'];
        $auth   = [
            'vesho_worker_start_order',
            'vesho_worker_complete_order',
            'vesho_worker_complete_maintenance',
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
            // New: tellimused
            'vesho_worker_claim_order',
            'vesho_worker_release_order',
            'vesho_worker_pick_item',
            'vesho_worker_pack_order',
        ];
        foreach ($nopriv as $a) {
            add_action('wp_ajax_nopriv_' . $a, [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
            add_action('wp_ajax_' . $a,        [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
        }
        foreach ($auth as $a) {
            add_action('wp_ajax_' . $a, [__CLASS__, 'ajax_' . str_replace('vesho_worker_', '', $a)]);
        }

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
        // Count pending shop orders (claimed by this worker)
        $shop_count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE worker_id=%d AND status IN ('processing','confirmed')", $wid
        ));

        $nav_items = [
            'overview'   => ['icon' => '&#9634;',  'label' => 'Ülevaade'],
            'active'     => ['icon' => '&#9651;',  'label' => 'Aktiivsed',  'badge' => $active_count ?: null],
            'history'    => ['icon' => '&#9643;',  'label' => 'Ajalugu'],
            'schedule'   => ['icon' => '&#128197;','label' => 'Graafik'],
            'tellimused' => ['icon' => '&#128722;','label' => 'Tellimused', 'badge' => $shop_count ?: null],
        ];
        if ( ! empty( $worker->can_inventory ) ) {
            $nav_items['inventuur'] = ['icon' => '&#128270;','label' => 'Inventuur'];
            $nav_items['vastuvott'] = ['icon' => '&#128230;','label' => 'Vastuvõtt'];
        }
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
          <span style="background:#f59e0b;color:#fff;font-size:10px;font-weight:700;border-radius:999px;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 5px"><?php echo (int)$item['badge']; ?></span>
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
      switch ($tab) {
          case 'overview':   self::tab_overview($worker, $wid, $nonce, $ajax); break;
          case 'active':     self::tab_active($wid, $nonce, $ajax); break;
          case 'history':    self::tab_history($wid); break;
          case 'schedule':   self::tab_schedule($wid, $ajax); break;
          case 'inventuur':
              if ( ! empty( $worker->can_inventory ) ) { self::tab_inventuur($wid, $nonce, $ajax); }
              else { echo '<div class="vwp-empty">Juurdepääs puudub.</div>'; }
              break;
          case 'vastuvott':
              if ( ! empty( $worker->can_inventory ) ) { self::tab_vastuvott($wid, $nonce, $ajax); }
              else { echo '<div class="vwp-empty">Juurdepääs puudub.</div>'; }
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
  if(ham&&sidebar){ ham.addEventListener('click',function(){sidebar.classList.toggle('open');}); }
  document.addEventListener('click',function(e){
    if(sidebar&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==ham){
      sidebar.classList.remove('open');
    }
  });
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
        ?>
<?php
// Portal notices
$notices = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vesho_portal_notices
     WHERE active=1 AND target IN ('worker','both')
     AND (starts_at IS NULL OR starts_at <= %s)
     AND (ends_at IS NULL OR ends_at >= %s)
     ORDER BY created_at DESC LIMIT 5",
    $today, $today
));
foreach ($notices as $notice) : ?>
<div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;gap:10px">
  <span style="font-size:20px">📢</span>
  <div>
    <strong style="font-size:14px"><?php echo esc_html($notice->title); ?></strong>
    <p style="margin:4px 0 0;font-size:13px;color:#4b5563"><?php echo esc_html($notice->message); ?></p>
  </div>
</div>
<?php endforeach; ?>

<div class="vwp-page-header">
  <h1>Tere, <?php echo esc_html(explode(' ', $worker->name)[0]); ?>!</h1>
</div>

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

  var ci=document.getElementById('vwp-clockin-btn');
  if(ci) ci.addEventListener('click',function(){
    var fd=new FormData(); fd.append('action','vesho_worker_clock_in'); fd.append('nonce',NONCE);
    ci.disabled=true; ci.textContent='...';
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){ showMsg(true,d.data.message||'Sisse löödud!'); setTimeout(function(){window.location.reload();},800); }
      else { showMsg(false,(d.data&&d.data.message)||'Viga'); ci.disabled=false; ci.textContent='▶ Alusta tööpäeva'; }
    });
  });

  var co=document.getElementById('vwp-clockout-btn');
  if(co) co.addEventListener('click',function(){
    var fd=new FormData(); fd.append('action','vesho_worker_clock_out'); fd.append('nonce',NONCE); fd.append('entry_id',co.dataset.id);
    co.disabled=true; co.textContent='...';
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      if(d.success){ showMsg(true,d.data.message||'Lõpetatud!'); setTimeout(function(){window.location.reload();},800); }
      else { showMsg(false,(d.data&&d.data.message)||'Viga'); co.disabled=false; co.textContent='■ Lõpeta tööpäev'; }
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
            "SELECT wo.*, c.name as client_name, d.name as device_name
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=wo.device_id
             WHERE wo.worker_id=%d AND wo.status IN ('open','assigned','in_progress')
             ORDER BY wo.scheduled_date ASC, wo.created_at DESC LIMIT 50", $wid
        ));
        ?>
<h2 class="vwp-section-title">Aktiivsed töökäsud</h2>
<?php if (empty($orders)): ?>
  <div class="vwp-empty">Aktiivseid töökäske pole.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($orders as $wo):
  $pc = ['urgent'=>'#ef4444','high'=>'#f59e0b','normal'=>'#00b4c8','low'=>'#94a3b8'][$wo->priority??'normal']??'#94a3b8';
  $mats = $wo->materials_used ? json_decode($wo->materials_used, true) : [];
?>
<div class="vwp-order-card" style="border-left:4px solid <?php echo $pc; ?>">
  <div class="vwp-order-header">
    <div>
      <strong><?php echo esc_html($wo->title); ?></strong>
      <?php echo self::status_badge($wo->status); ?>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <span class="vwp-order-meta"><?php echo esc_html($wo->scheduled_date ? date('d.m.Y',strtotime($wo->scheduled_date)) : '—'); ?></span>
      <button class="vwp-btn-outline vwp-detail-btn" style="padding:4px 10px;font-size:12px"
        data-id="<?php echo $wo->id; ?>" data-title="<?php echo esc_attr($wo->title); ?>"
        data-client="<?php echo esc_attr($wo->client_name??'—'); ?>"
        data-device="<?php echo esc_attr($wo->device_name??'—'); ?>"
        data-service="<?php echo esc_attr($wo->service_type??'—'); ?>"
        data-status="<?php echo esc_attr($wo->status); ?>"
        data-date="<?php echo esc_attr($wo->scheduled_date ? date('d.m.Y',strtotime($wo->scheduled_date)) : '—'); ?>"
        data-priority="<?php echo esc_attr($wo->priority??'—'); ?>"
        data-desc="<?php echo esc_attr($wo->description??''); ?>"
        data-notes="<?php echo esc_attr($wo->notes??''); ?>">&#128065;</button>
    </div>
  </div>
  <div class="vwp-order-body">
    <div style="font-size:13px;color:#64748b;margin-bottom:6px">
      <strong style="color:#1e293b">Klient:</strong> <?php echo esc_html($wo->client_name??'—'); ?>
      <?php if ($wo->device_name): ?> &nbsp;·&nbsp; <strong style="color:#1e293b">Seade:</strong> <?php echo esc_html($wo->device_name); ?><?php endif; ?>
    </div>
    <?php if ($wo->description): ?><p class="vwp-desc"><?php echo esc_html($wo->description); ?></p><?php endif; ?>
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
    <div class="vwp-order-actions" style="margin-top:10px">
      <?php if (in_array($wo->status,['open','assigned'])): ?>
      <button class="vwp-btn-outline vwp-start-btn2" data-id="<?php echo $wo->id; ?>" data-nonce="<?php echo $nonce; ?>">&#9654; Alusta</button>
      <?php endif; ?>
      <button class="vwp-btn-primary vwp-complete-btn2" data-id="<?php echo $wo->id; ?>" data-nonce="<?php echo $nonce; ?>">&#10003; Lõpeta</button>
    </div>
    <?php
    // Photos
    $photos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d ORDER BY created_at ASC",
        $wo->id
    ));
    $photo_count = count($photos);
    ?>
    <div class="vwp-photos-wrap" style="margin-top:12px">
        <?php if ($photos) : ?>
        <div class="vwp-photos-grid" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
            <?php foreach ($photos as $p) : ?>
            <a href="<?php echo esc_url($p->filename); ?>" target="_blank">
                <img src="<?php echo esc_url($p->filename); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0">
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($photo_count < 5) : ?>
        <label class="vwp-btn-outline" style="cursor:pointer;font-size:12px;padding:6px 12px">
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
<?php echo self::orders_detail_modal(); ?>
<script>
(function(){
  var AJAX='<?php echo $ajax; ?>',NONCE='<?php echo $nonce; ?>';
  window.vwpUploadPhoto=function(input,woid){if(!input.files[0])return;var fd=new FormData();fd.append('action','vesho_upload_workorder_photo');fd.append('nonce','<?php echo wp_create_nonce('vesho_worker_nonce'); ?>');fd.append('workorder_id',woid);fd.append('photo',input.files[0]);fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){location.reload();}else{alert(d.data.message||'Viga foto üleslaadimisel');}});}
  var invCache=null;
  function loadInv(cb){if(invCache){cb(invCache);return;}var fd=new FormData();fd.append('action','vesho_worker_get_inventory');fd.append('nonce',NONCE);fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{invCache=(d.success&&d.data.items)||[];cb(invCache);});}
  document.querySelectorAll('[id^="mat-inv-a-"]').forEach(sel=>{loadInv(items=>{items.forEach(item=>{var o=document.createElement('option');o.value=JSON.stringify({id:item.id,name:item.name,unit:item.unit,price:item.sell_price});o.textContent=item.name+' ('+item.quantity+' '+item.unit+')';sel.appendChild(o);});});});
  window.addMat=function(oid,pfx,nonce){var sel=document.getElementById('mat-inv-'+pfx+'-'+oid);var qEl=document.getElementById('mat-qty-'+pfx+'-'+oid);if(!sel||!sel.value)return;var mat=JSON.parse(sel.value),qty=parseFloat(qEl.value)||1;var fd=new FormData();fd.append('action','vesho_worker_save_materials');fd.append('nonce',nonce);fd.append('order_id',oid);fd.append('material_id',mat.id);fd.append('material_name',mat.name);fd.append('material_unit',mat.unit);fd.append('material_price',mat.price);fd.append('qty',qty);fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){var div=document.getElementById('mats-'+pfx+'-'+oid);if(div){var row=document.createElement('div');row.style='display:flex;gap:8px;align-items:center;margin-bottom:5px;font-size:13px';row.innerHTML='<span style="flex:1">'+mat.name+'</span><span style="color:#64748b">'+qty+' '+mat.unit+'</span>';div.appendChild(row);}qEl.value=1;sel.value='';}else alert(d.data?.message||'Viga');});};
  document.querySelectorAll('.vwp-start-btn2').forEach(btn=>{btn.addEventListener('click',()=>{if(!confirm('Alusta töökäsku?'))return;var fd=new FormData();fd.append('action','vesho_worker_start_order');fd.append('nonce',btn.dataset.nonce);fd.append('order_id',btn.dataset.id);btn.disabled=true;fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success)location.reload();else{alert(d.data?.message||'Viga');btn.disabled=false;}});});});
  document.querySelectorAll('.vwp-complete-btn2').forEach(btn=>{btn.addEventListener('click',()=>{if(!confirm('Märgi lõpetatuks?'))return;var fd=new FormData();fd.append('action','vesho_worker_complete_order');fd.append('nonce',btn.dataset.nonce);fd.append('order_id',btn.dataset.id);fd.append('auto_invoice','1');btn.disabled=true;fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){btn.closest('.vwp-order-card').style.opacity='.4';btn.textContent='✓ Lõpetatud';}else{alert(d.data?.message||'Viga');btn.disabled=false;}});});});
  <?php echo self::detail_modal_js(); ?>
})();
</script>
        <?php
    }

    // ── Tab: Ajalugu ──────────────────────────────────────────────────────────

    private static function tab_history($wid) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT wo.*, c.name as client_name, d.name as device_name
             FROM {$wpdb->prefix}vesho_workorders wo
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON c.id=wo.client_id
             LEFT JOIN {$wpdb->prefix}vesho_devices d ON d.id=wo.device_id
             WHERE wo.worker_id=%d AND wo.status='completed'
             ORDER BY wo.completed_date DESC, wo.created_at DESC LIMIT 60", $wid
        ));
        ?>
<h2 class="vwp-section-title">Lõpetatud töökäsud</h2>
<?php if (empty($rows)): ?>
  <div class="vwp-empty">Lõpetatud töökäske pole.</div>
<?php else: ?>
<table class="vwp-table">
  <thead><tr><th>Töökäsk</th><th>Klient</th><th>Seade</th><th>Töö liik</th><th>Lõpetatud</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $wo): ?>
  <tr>
    <td><strong><?php echo esc_html($wo->title); ?></strong></td>
    <td><?php echo esc_html($wo->client_name??'—'); ?></td>
    <td style="color:#64748b;font-size:12px"><?php echo esc_html($wo->device_name??'—'); ?></td>
    <td style="color:#64748b;font-size:12px"><?php echo esc_html($wo->service_type??'—'); ?></td>
    <td style="color:#64748b;font-size:12px"><?php echo esc_html($wo->completed_date ? date('d.m.Y',strtotime($wo->completed_date)) : '—'); ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
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
    <span id="inv-scan-feedback" style="font-size:20px"></span>
  </div>

  <div id="vwp-inv-msg" class="vwp-msg" style="display:none"></div>
  <div id="vwp-inv-items"></div>
</div>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>', NONCE='<?php echo $nonce; ?>';

  // EAN scan handler
  document.getElementById('inv-ean-scan')?.addEventListener('change', function(){
    var ean = this.value.trim();
    this.value = '';
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
        $receipts = $wpdb->get_results($wpdb->prepare(
            "SELECT sr.*, COUNT(sri.id) as item_count,
                    SUM(CASE WHEN sri.actual_qty IS NULL THEN 1 ELSE 0 END) as pending_count
             FROM {$wpdb->prefix}vesho_stock_receipts sr
             LEFT JOIN {$wpdb->prefix}vesho_stock_receipt_items sri ON sri.receipt_id=sr.id
             WHERE sr.worker_id=%d OR sr.status='pending'
             GROUP BY sr.id
             ORDER BY sr.created_at DESC LIMIT 20", $wid
        ));
        ?>
<h2 class="vwp-section-title">Vastuvõtt</h2>
<?php if (empty($receipts)): ?>
  <div class="vwp-empty">Vastuvõetavaid kaupu pole.</div>
<?php else: ?>
<div id="vwp-recv-list" style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($receipts as $sr):
  $pend = (int)($sr->pending_count??0);
?>
<div class="vwp-card">
  <div style="display:flex;justify-content:space-between;align-items:center;cursor:pointer" onclick="toggleRecv(<?php echo $sr->id; ?>)">
    <div>
      <strong style="font-size:14px">#<?php echo esc_html($sr->receipt_num); ?></strong>
      <?php if ($sr->supplier): ?><span style="font-size:12px;color:#64748b;margin-left:8px"><?php echo esc_html($sr->supplier); ?></span><?php endif; ?>
      <div style="font-size:12px;color:#64748b;margin-top:2px"><?php echo (int)$sr->item_count; ?> kaupa · <?php echo esc_html(date('d.m.Y',strtotime($sr->created_at))); ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <?php if ($pend > 0): ?>
        <span style="background:#fef9c3;color:#b45309;border-radius:999px;font-size:11px;font-weight:700;padding:3px 10px"><?php echo $pend; ?> ootel</span>
      <?php else: ?>
        <span style="background:#dcfce7;color:#16a34a;border-radius:999px;font-size:11px;font-weight:700;padding:3px 10px">Kõik vastu võetud</span>
      <?php endif; ?>
      <span style="color:#94a3b8" id="recv-arrow-<?php echo $sr->id; ?>">&#8250;</span>
    </div>
  </div>
  <div id="recv-items-<?php echo $sr->id; ?>" style="display:none;margin-top:14px;border-top:1px solid #f1f5f9;padding-top:12px">
    <div style="color:#94a3b8;font-size:13px;text-align:center;padding:10px">Laen...</div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>', NONCE='<?php echo $nonce; ?>';
  var loaded={};

  window.toggleRecv = function(id){
    var el=document.getElementById('recv-items-'+id);
    var arrow=document.getElementById('recv-arrow-'+id);
    if(el.style.display==='none'){
      el.style.display=''; arrow.textContent='❮';
      if(loaded[id]) return;
      loaded[id]=true;
      var fd=new FormData(); fd.append('action','vesho_worker_get_receipt_items'); fd.append('nonce',NONCE); fd.append('receipt_id',id);
      fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(!d.success){el.innerHTML='<div style="color:#ef4444">'+(d.data?.message||'Viga')+'</div>';return;}
        var items=d.data.items, html='';
        items.forEach(item=>{
          var received=item.actual_qty!==null;
          html+='<div style="padding:10px 0;border-bottom:1px solid #f8fafc;display:grid;grid-template-columns:1fr auto auto auto;gap:10px;align-items:center">'+
            '<div>'+
              '<div style="font-size:13px;font-weight:600">'+item.name+'</div>'+
              (item.sku?'<div style="font-size:11px;color:#94a3b8;font-family:monospace">'+item.sku+'</div>':'')+
              '<div style="font-size:11px;color:#94a3b8">Oodata: '+item.expected_qty+' '+item.unit+'</div>'+
            '</div>'+
            '<input type="number" id="recv-qty-'+item.id+'" value="'+(item.actual_qty!==null?item.actual_qty:item.expected_qty)+'"'+
              ' min="0" step="0.01" style="width:75px;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;text-align:center" '+
              (received?'disabled':'')+'>'+
            '<input type="text" id="recv-loc-'+item.id+'" value="'+(item.location||'')+'"'+
              ' placeholder="Asukoht" style="width:90px;padding:6px 8px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px" '+
              (received?'disabled':'')+'>'+
            '<button onclick="confirmRecv('+item.id+','+id+')" class="vwp-btn-outline" style="font-size:12px;padding:5px 10px" '+
              (received?'disabled style="opacity:.4"':'')+'>'+
              (received?'✓ OK':'Kinnita')+
            '</button>'+
          '</div>';
        });
        el.innerHTML=html||'<div style="color:#94a3b8;padding:10px;text-align:center">Kaupasid pole.</div>';
      });
    } else { el.style.display='none'; arrow.textContent='›'; }
  };

  window.confirmRecv = function(itemId, receiptId){
    var qty=document.getElementById('recv-qty-'+itemId)?.value;
    var loc=document.getElementById('recv-loc-'+itemId)?.value||'';
    if(!qty){alert('Sisesta kogus');return;}
    var fd=new FormData(); fd.append('action','vesho_worker_confirm_receipt'); fd.append('nonce',NONCE);
    fd.append('item_id',itemId); fd.append('actual_qty',qty); fd.append('location',loc);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){
        var btn=document.querySelector('[onclick="confirmRecv('+itemId+','+receiptId+')"]');
        if(btn){btn.textContent='✓ OK';btn.disabled=true;btn.style.opacity='.4';}
        document.getElementById('recv-qty-'+itemId).disabled=true;
        document.getElementById('recv-loc-'+itemId).disabled=true;
      } else alert(d.data?.message||'Viga');
    });
  };
})();
</script>
        <?php
    }

    // ── Tab: Tellimused ───────────────────────────────────────────────────────

    private static function tab_tellimused($wid, $nonce, $ajax) {
        global $wpdb;
        // Pending orders (not yet claimed)
        $pending_count = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_shop_orders WHERE status='pending' AND (worker_id IS NULL OR worker_id=0)"
        );
        // My claimed order
        $my_order = $wpdb->get_row($wpdb->prepare(
            "SELECT so.* FROM {$wpdb->prefix}vesho_shop_orders so
             WHERE so.worker_id=%d AND so.status IN ('processing','confirmed') LIMIT 1", $wid
        ));
        $my_items = $my_order ? $wpdb->get_results($wpdb->prepare(
            "SELECT soi.*, COALESCE(inv.ean, '') AS ean
             FROM {$wpdb->prefix}vesho_shop_order_items soi
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id = soi.inventory_id
             WHERE soi.order_id=%d ORDER BY soi.id ASC", $my_order->id
        )) : [];
        ?>
<h2 class="vwp-section-title">E-poe tellimused</h2>
<div id="vwp-shop-msg" class="vwp-msg" style="display:none"></div>

<?php if (!$my_order): ?>
<!-- Queue view -->
<div class="vwp-card" style="text-align:center;padding:36px 24px">
  <div style="font-size:42px;margin-bottom:12px">🛒</div>
  <div style="font-size:22px;font-weight:700;color:#1e293b;margin-bottom:6px"><?php echo $pending_count; ?></div>
  <div style="font-size:13px;color:#64748b;margin-bottom:24px">Tellimust ootab komplekteerimist</div>
  <?php if ($pending_count > 0): ?>
  <button id="vwp-claim-btn" data-nonce="<?php echo $nonce; ?>"
          class="vwp-btn-primary" style="font-size:14px;padding:10px 28px">
    &#9654; Võta tellimus
  </button>
  <?php else: ?>
  <div style="font-size:13px;color:#94a3b8">Kõik tellimused on komplekteeritud.</div>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- Active order -->
<?php
$client_name = $my_order->client_id
    ? $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}vesho_clients WHERE id=%d", $my_order->client_id))
    : ($my_order->guest_name ?: '—');
$all_picked = !empty($my_items) && count(array_filter($my_items, fn($i) => $i->picked)) === count($my_items);
?>
<div class="vwp-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div>
      <div style="font-size:20px;font-weight:800;color:#00b4c8">#<?php echo esc_html($my_order->order_number); ?></div>
      <div style="font-size:13px;color:#64748b"><?php echo esc_html($client_name); ?> · <?php echo date('d.m.Y',strtotime($my_order->created_at)); ?></div>
    </div>
    <button id="vwp-release-btn" data-nonce="<?php echo $nonce; ?>" data-id="<?php echo $my_order->id; ?>"
            class="vwp-btn-outline" style="font-size:12px">&#8617; Vabasta</button>
  </div>

  <!-- Progress steps -->
  <div style="display:flex;gap:0;margin-bottom:20px;background:#f8fafc;border-radius:8px;overflow:hidden">
    <?php foreach (['Kogu','Arve','Kleeps','Valmis'] as $i => $step): ?>
    <div style="flex:1;text-align:center;padding:8px 4px;font-size:11px;font-weight:600;color:<?php echo $i===0?'#fff':'#94a3b8'; ?>;background:<?php echo $i===0?'#00b4c8':'transparent'; ?>">
      <?php echo $step; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($my_order->shipping_method && $my_order->shipping_method !== 'pickup'): ?>
  <div style="background:#f8fafc;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px">
    🚚 <strong>Tarne:</strong> <?php echo esc_html($my_order->shipping_name??''); ?> · <?php echo esc_html($my_order->shipping_address??''); ?>
  </div>
  <?php endif; ?>

  <!-- EAN scan bar -->
  <div class="scan-bar" style="display:flex;align-items:center;gap:10px;margin-bottom:14px;background:#f8fafc;border-radius:8px;padding:8px 12px">
    <input type="text" id="vw-ean-scan" placeholder="Skanni EAN..." autofocus autocomplete="off"
           class="scan-input" style="flex:1;border:1px solid #dce3e9;border-radius:6px;padding:8px 12px;font-size:14px;outline:none">
    <span id="vw-scan-feedback" style="font-size:13px;min-width:160px"></span>
  </div>

  <!-- Items -->
  <div id="vwp-shop-items">
  <?php foreach ($my_items as $item): ?>
  <div id="shop-row-<?php echo $item->id; ?>"
       class="pick-item-row<?php echo $item->picked ? ' picked' : ''; ?>"
       data-ean="<?php echo esc_attr($item->ean ?? ''); ?>"
       style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;<?php echo $item->picked?'opacity:.4':''; ?>">
    <input type="checkbox" id="shop-cb-<?php echo $item->id; ?>"
           class="pick-item-btn"
           <?php checked($item->picked, 1); ?>
           onchange="togglePick(<?php echo $item->id; ?>, this.checked)"
           style="width:18px;height:18px;accent-color:#00b4c8;cursor:pointer;flex-shrink:0">
    <div style="flex:1;min-width:0">
      <div style="font-size:13px;font-weight:600;<?php echo $item->picked?'text-decoration:line-through':''; ?>"><?php echo esc_html($item->name); ?></div>
      <?php if ($item->sku): ?><div style="font-size:11px;color:#94a3b8;font-family:monospace"><?php echo esc_html($item->sku); ?></div><?php endif; ?>
    </div>
    <div style="font-size:13px;font-weight:600;color:#1e293b;white-space:nowrap"><?php echo number_format($item->quantity,1); ?> tk</div>
    <div style="font-size:12px;color:#64748b;white-space:nowrap"><?php echo number_format($item->unit_price,2,',','.'); ?> €</div>
  </div>
  <?php endforeach; ?>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:2px solid #f1f5f9">
    <div style="font-size:12px;color:#64748b" id="vwp-shop-progress">
      <?php $picked_n = count(array_filter($my_items, fn($i) => $i->picked)); ?>
      <?php echo $picked_n; ?>/<?php echo count($my_items); ?> komplekteeritud
    </div>
    <strong style="font-size:15px"><?php echo number_format($my_order->total,2,',','.'); ?> €</strong>
  </div>

  <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
    <button id="vwp-print-invoice-btn" class="vwp-btn-outline" style="flex:1" <?php echo !$all_picked?'disabled':''; ?>>🖨 Arve</button>
    <button id="vwp-print-label-btn" class="vwp-btn-outline" style="flex:1" disabled>📦 Kleeps</button>
    <button id="vwp-pack-btn" data-order-complete-id="<?php echo $my_order->id; ?>"
            data-id="<?php echo $my_order->id; ?>" data-nonce="<?php echo $nonce; ?>"
            class="vwp-btn-primary" style="flex:1" disabled>✓ Pakitud</button>
  </div>
  <p class="description" id="pick-gate-msg" style="color:#e74c3c;display:none;margin:8px 0 0;font-size:12px">Kõik tooted peavad olema komplekteeritud enne lõpetamist.</p>
</div>
<?php endif; ?>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>', NONCE='<?php echo $nonce; ?>';
  var allItems=<?php echo json_encode(array_map(fn($i)=>['id'=>$i->id,'name'=>$i->name,'qty'=>$i->quantity,'price'=>$i->unit_price,'picked'=>(bool)$i->picked], $my_items??[])); ?>;
  var invoicePrinted=false, labelPrinted=false;

  // Claim order
  var claimBtn=document.getElementById('vwp-claim-btn');
  if(claimBtn) claimBtn.addEventListener('click',()=>{
    claimBtn.disabled=true; claimBtn.textContent='...';
    var fd=new FormData(); fd.append('action','vesho_worker_claim_order'); fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success) location.reload(); else{showMsg(false,d.data?.message||'Viga');claimBtn.disabled=false;claimBtn.textContent='▶ Võta tellimus';}
    });
  });

  // Release order
  var relBtn=document.getElementById('vwp-release-btn');
  if(relBtn) relBtn.addEventListener('click',()=>{
    if(!confirm('Vabasta tellimus tagasi järjekorda?')) return;
    var fd=new FormData(); fd.append('action','vesho_worker_release_order'); fd.append('nonce',NONCE); fd.append('order_id',relBtn.dataset.id);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success) location.reload();});
  });

  // Toggle pick
  window.togglePick = function(itemId, picked){
    var fd=new FormData(); fd.append('action','vesho_worker_pick_item'); fd.append('nonce',NONCE);
    fd.append('item_id',itemId); fd.append('picked',picked?1:0);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){
        var row=document.getElementById('shop-row-'+itemId);
        if(row){
          row.style.opacity=picked?'.4':'1';
          var nameEl=row.querySelector('div[style*="font-size:13px"]');
          if(nameEl)nameEl.style.textDecoration=picked?'line-through':'';
          if(picked){ row.classList.add('picked'); } else { row.classList.remove('picked'); }
        }
        updateProgress();
        checkPickGate();
      }
    });
  };

  // ── Pick gate: enable complete button only when all items picked ─────────
  function checkPickGate(){
    var rows   = document.querySelectorAll('.pick-item-row');
    var picked = document.querySelectorAll('.pick-item-row.picked');
    var btn    = document.getElementById('vwp-pack-btn');
    var gateMsg= document.getElementById('pick-gate-msg');
    if(btn){
      if(rows.length > 0 && picked.length < rows.length){
        btn.disabled = true;
        if(gateMsg) gateMsg.style.display='block';
      } else {
        // All picked — gate message hidden; label-print gate still controls final enable
        if(gateMsg) gateMsg.style.display='none';
      }
    }
  }
  // Run on load
  checkPickGate();

  // ── EAN scan input ────────────────────────────────────────────────────────
  document.getElementById('vw-ean-scan')?.addEventListener('change', function(){
    var ean = this.value.trim();
    this.value = '';
    var row = document.querySelector('[data-ean="'+ean+'"]');
    if(row){
      row.style.background='#d4edda';
      var cb = row.querySelector('.pick-item-btn');
      if(cb && !cb.disabled && !cb.checked){
        cb.checked = true;
        cb.dispatchEvent(new Event('change'));
      }
      var feedback = document.getElementById('vw-scan-feedback');
      feedback.textContent = '\u2705 '+ean;
      feedback.style.color = 'green';
    } else {
      var feedback = document.getElementById('vw-scan-feedback');
      feedback.textContent = '\u274c EAN ei leitud: '+ean;
      feedback.style.color = 'red';
    }
    setTimeout(function(){ var f=document.getElementById('vw-scan-feedback'); if(f) f.textContent=''; }, 2000);
  });

  function updateProgress(){
    var total=document.querySelectorAll('[id^="shop-cb-"]').length;
    var done=document.querySelectorAll('[id^="shop-cb-"]:checked').length;
    var prog=document.getElementById('vwp-shop-progress');
    if(prog) prog.textContent=done+'/'+total+' komplekteeritud';
    var invoiceBtn=document.getElementById('vwp-print-invoice-btn');
    if(invoiceBtn) invoiceBtn.disabled=(done<total);
  }

  // Print invoice
  var printInvBtn=document.getElementById('vwp-print-invoice-btn');
  if(printInvBtn) printInvBtn.addEventListener('click',()=>{
    var win=window.open('','_blank','width=700,height=900');
    var rows=allItems.map(it=>'<tr><td>'+it.name+'</td><td style="text-align:center">'+it.qty+'</td><td style="text-align:right">'+parseFloat(it.price).toFixed(2)+'€</td><td style="text-align:right">'+(it.qty*it.price).toFixed(2)+'€</td></tr>').join('');
    win.document.write('<html><head><title>Arve</title><style>body{font-family:Arial,sans-serif;padding:32px}table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #e5e7eb}th{background:#f9fafb;font-size:12px}</style></head><body><h2>Arve #<?php echo esc_js($my_order->order_number??''); ?></h2><table><thead><tr><th>Nimetus</th><th>Kogus</th><th>Hind</th><th>Kokku</th></tr></thead><tbody>'+rows+'</tbody></table><script>window.print();<\/script></body></html>');
    win.document.close();
    invoicePrinted=true;
    var labelBtn=document.getElementById('vwp-print-label-btn');
    if(labelBtn) labelBtn.disabled=false;
  });

  // Print label
  var printLblBtn=document.getElementById('vwp-print-label-btn');
  if(printLblBtn) printLblBtn.addEventListener('click',()=>{
    var win=window.open('','_blank','width=400,height=300');
    win.document.write('<html><head><title>Kleeps</title><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script></head><body style="padding:20px;font-family:Arial,sans-serif;text-align:center"><p style="font-weight:700;font-size:18px">#<?php echo esc_js($my_order->order_number??''); ?></p><p><?php echo esc_js($client_name??''); ?></p><?php if(!empty($my_order->shipping_address)): ?><p style="font-size:12px"><?php echo esc_js($my_order->shipping_address); ?></p><?php endif; ?><svg id="bc"></svg><script>JsBarcode("#bc","<?php echo esc_js($my_order->order_number??'ORD000'); ?>",{format:"CODE128",width:2,height:50,displayValue:true});<\/script><script>window.print();<\/script></body></html>');
    win.document.close();
    labelPrinted=true;
    var packBtn=document.getElementById('vwp-pack-btn');
    if(packBtn) packBtn.disabled=false;
  });

  // Pack / complete
  var packBtn=document.getElementById('vwp-pack-btn');
  if(packBtn) packBtn.addEventListener('click',()=>{
    if(!confirm('Märgi tellimus pakituks ja lõpetatuks?')) return;
    var fd=new FormData(); fd.append('action','vesho_worker_pack_order'); fd.append('nonce',NONCE); fd.append('order_id',packBtn.dataset.id);
    packBtn.disabled=true;
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success) location.reload(); else{showMsg(false,d.data?.message||'Viga');packBtn.disabled=false;}
    });
  });

  function showMsg(ok,txt){var m=document.getElementById('vwp-shop-msg');if(!m)return;m.style.display='block';m.className='vwp-msg '+(ok?'success':'error');m.textContent=txt;}
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
  document.querySelectorAll('.vwp-maint-complete-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!confirm('Märgi hooldus lõpetatuks?')) return;
      var fd=new FormData(); fd.append('action','vesho_worker_complete_maintenance'); fd.append('nonce',btn.dataset.nonce); fd.append('maintenance_id',btn.dataset.id);
      btn.disabled=true;
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){ btn.closest('.vwp-order-card').style.opacity='.4'; btn.textContent='Lõpetatud'; }
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
        // Match by name (case-insensitive) + PIN
        $worker = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE LOWER(name)=LOWER(%s) AND pin=%s AND active=1 LIMIT 1",
            $name, $pin
        ));
        if ( ! $worker ) {
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
            $vat_rate = (float) Vesho_CRM_Database::get_setting('vat_rate', '22');
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

        wp_send_json_success(['message' => 'Töökäsk lõpetatud!', 'invoice_number' => $invoice_number]);
    }

    // ── AJAX: Get inventory ───────────────────────────────────────────────────

    public static function ajax_get_inventory() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $items = $wpdb->get_results(
            "SELECT id, name, unit, quantity, sell_price FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND quantity>0 ORDER BY name ASC LIMIT 200"
        );
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
        $wid   = (int) $worker->id;
        $mid   = absint($_POST['maintenance_id'] ?? 0);
        $result = $wpdb->update(
            $wpdb->prefix . 'vesho_maintenances',
            ['status' => 'completed', 'completed_date' => current_time('mysql')],
            ['id' => $mid, 'worker_id' => $wid]
        );
        if ($result === false) wp_send_json_error(['message' => 'Uuendamine ebaõnnestus']);
        wp_send_json_success(['message' => 'Hooldus lõpetatud!']);
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
        $wpdb->insert($wpdb->prefix . 'vesho_work_hours', [
            'worker_id'  => $wid,
            'date'       => current_time('Y-m-d'),
            'hours'      => 0,
            'start_time' => $now,
            'created_at' => $now,
        ]);
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

        $wpdb->update(
            $wpdb->prefix . 'vesho_work_hours',
            ['end_time' => $now, 'hours' => max(0.01, $hours_float)],
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
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $receipt_id = absint($_POST['receipt_id']??0);
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT sri.*, inv.name, inv.sku, inv.unit
             FROM {$wpdb->prefix}vesho_stock_receipt_items sri
             LEFT JOIN {$wpdb->prefix}vesho_inventory inv ON inv.id=sri.inventory_id
             WHERE sri.receipt_id=%d
             ORDER BY inv.name ASC", $receipt_id
        ));
        // Add location/actual_qty columns if they exist
        foreach ($items as &$item) {
            if (!isset($item->actual_qty)) $item->actual_qty = null;
            if (!isset($item->location)) $item->location = '';
            $item->expected_qty = $item->quantity ?? 0;
        }
        wp_send_json_success(['items'=>$items]);
    }

    public static function ajax_confirm_receipt() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        if (!self::get_current_worker()) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $item_id  = absint($_POST['item_id']??0);
        $actual   = (float)($_POST['actual_qty']??0);
        $location = sanitize_text_field($_POST['location']??'');
        // Update actual_qty if column exists, else use quantity
        $cols = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_stock_receipt_items`");
        $data = ['quantity'=>$actual];
        if (in_array('actual_qty',$cols)) $data['actual_qty'] = $actual;
        if (in_array('location',$cols))   $data['location']   = $location;
        $wpdb->update("{$wpdb->prefix}vesho_stock_receipt_items", $data, ['id'=>$item_id]);
        // Update inventory quantity
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT inventory_id FROM {$wpdb->prefix}vesho_stock_receipt_items WHERE id=%d", $item_id
        ));
        if ($item && $item->inventory_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id=%d",
                $actual, $item->inventory_id
            ));
            if ($location) {
                $wpdb->update("{$wpdb->prefix}vesho_inventory",['location'=>$location],['id'=>$item->inventory_id]);
            }
        }
        wp_send_json_success(['message'=>'Vastu võetud']);
    }

    // ── AJAX: Tellimused ──────────────────────────────────────────────────────

    public static function ajax_claim_order() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $order = $wpdb->get_row(
            "SELECT id FROM {$wpdb->prefix}vesho_shop_orders
             WHERE status='pending' AND (worker_id IS NULL OR worker_id=0)
             ORDER BY created_at ASC LIMIT 1"
        );
        if (!$order) wp_send_json_error(['message'=>'Tellimusi pole saadaval']);
        $wpdb->update("{$wpdb->prefix}vesho_shop_orders",
            ['worker_id'=>$worker->id,'status'=>'processing'],['id'=>$order->id]);
        wp_send_json_success(['message'=>'Tellimus võetud']);
    }

    public static function ajax_release_order() {
        check_ajax_referer('vesho_portal_nonce','nonce');
        $worker = self::get_current_worker();
        if (!$worker) wp_send_json_error(['message'=>'Pole sisse logitud']);
        global $wpdb;
        $order_id = absint($_POST['order_id']??0);
        $wpdb->update("{$wpdb->prefix}vesho_shop_orders",
            ['worker_id'=>null,'status'=>'pending'],['id'=>$order_id,'worker_id'=>$worker->id]);
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
        $order_id = absint($_POST['order_id']??0);
        $wpdb->update("{$wpdb->prefix}vesho_shop_orders",
            ['status'=>'shipped','updated_at'=>current_time('mysql')],
            ['id'=>$order_id,'worker_id'=>$worker->id]);
        // Deduct picked items from inventory
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND picked=1", $order_id
        ));
        foreach ($items as $it) {
            if ($it->inventory_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity=GREATEST(0,quantity-%f) WHERE id=%d",
                    $it->quantity, $it->inventory_id
                ));
            }
        }
        wp_send_json_success(['message'=>'Tellimus pakitud ja lõpetatud!']);
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
  .vwp-sidebar{position:fixed;left:-260px;top:0;bottom:0;z-index:200;transition:left .25s;width:240px}
  .vwp-sidebar.open{left:0}
  .vwp-hamburger{display:block}
  .vwp-content{padding:16px}
  .vwp-stat-grid{grid-template-columns:1fr 1fr}
}
';
    }
}
