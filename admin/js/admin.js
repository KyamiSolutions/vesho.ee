/* Vesho CRM – Admin JS */
(function($) {
    'use strict';

    var CRM = window.VeshoCRM || {};

    // ── Modal helpers ──────────────────────────────────────────────────────────
    function openModal(id) {
        $('#' + id).addClass('is-open');
        $('body').css('overflow', 'hidden');
    }
    function closeModal(id) {
        $('#' + id).removeClass('is-open');
        $('body').css('overflow', '');
    }
    function closeAllModals() {
        $('.crm-modal-backdrop').removeClass('is-open');
        $('body').css('overflow', '');
    }

    // Close on backdrop click
    $(document).on('click', '.crm-modal-backdrop', function(e) {
        if ($(e.target).hasClass('crm-modal-backdrop')) closeAllModals();
    });
    $(document).on('click', '.crm-modal__close', function() {
        closeAllModals();
    });
    // Close on ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') closeAllModals();
    });

    // ── Search / filter ────────────────────────────────────────────────────────
    $(document).on('input', '.crm-search', function() {
        var q = $(this).val().toLowerCase();
        var $table = $(this).closest('.crm-card').find('table');
        $table.find('tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    // ── Open add/edit modal ────────────────────────────────────────────────────
    $(document).on('click', '[data-modal-open]', function() {
        openModal($(this).data('modal-open'));
    });

    // ── Edit: fill form from data attrs ───────────────────────────────────────
    $(document).on('click', '.crm-edit-btn', function() {
        var $btn = $(this);
        var modalId = $btn.data('modal');
        var $modal = $('#' + modalId);
        var data = $btn.data();

        // Fill form fields matching data-* keys
        $.each(data, function(key, val) {
            if (key === 'modal') return;
            var $field = $modal.find('[name="' + key + '"]');
            if ($field.is('select')) {
                $field.val(val);
            } else if ($field.is('input[type="checkbox"]')) {
                $field.prop('checked', !!val);
            } else {
                $field.val(val !== null ? val : '');
            }
        });

        // Set modal title to "Muuda"
        $modal.find('.crm-modal-edit-title').show();
        $modal.find('.crm-modal-add-title').hide();

        openModal(modalId);
    });

    // ── Add: clear form ────────────────────────────────────────────────────────
    $(document).on('click', '.crm-add-btn', function() {
        var modalId = $(this).data('modal');
        var $modal = $('#' + modalId);
        $modal.find('form')[0] && $modal.find('form')[0].reset();
        $modal.find('[name$="_id"]').val(''); // clear hidden IDs
        $modal.find('.crm-modal-edit-title').hide();
        $modal.find('.crm-modal-add-title').show();
        openModal(modalId);
    });

    // ── AJAX form submit (REST API) ────────────────────────────────────────────
    $(document).on('submit', '.crm-ajax-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        var endpoint = $form.data('endpoint');
        var method = $form.data('method') || 'POST';
        var idField = $form.data('id-field') || 'id';
        var id = $form.find('[name="' + idField + '"]').val();

        // Build data object
        var formData = {};
        $form.serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });
        delete formData[idField];

        var url = CRM.restUrl + endpoint;
        if (id) { url += '/' + id; method = 'PUT'; }

        $btn.prop('disabled', true).text(CRM.i18n.saving);

        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            headers: { 'X-WP-Nonce': CRM.restNonce },
            success: function() {
                showNotice('success', CRM.i18n.saved);
                closeAllModals();
                setTimeout(function() { location.reload(); }, 600);
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : CRM.i18n.error;
                showNotice('error', msg);
                $btn.prop('disabled', false).text('Salvesta');
            }
        });
    });

    // ── Delete ─────────────────────────────────────────────────────────────────
    $(document).on('click', '.crm-delete-btn', function(e) {
        e.preventDefault();
        if (!confirm(CRM.i18n.confirm_delete)) return;
        var $btn = $(this);
        var endpoint = $btn.data('endpoint');
        var id = $btn.data('id');

        $.ajax({
            url: CRM.restUrl + endpoint + '/' + id,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': CRM.restNonce },
            success: function() {
                $btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                showNotice('success', 'Kustutatud!');
            },
            error: function() { showNotice('error', CRM.i18n.error); }
        });
    });

    // ── Notice helper ──────────────────────────────────────────────────────────
    function showNotice(type, msg) {
        var $n = $('<div class="crm-alert crm-alert-' + (type === 'success' ? 'success' : 'error') + '" style="position:fixed;top:60px;right:20px;z-index:999999;min-width:260px;box-shadow:0 4px 20px rgba(0,0,0,.15)">' + msg + '</div>');
        $('body').append($n);
        setTimeout(function() { $n.fadeOut(400, function() { $n.remove(); }); }, 3000);
    }

    // ── Status inline update ───────────────────────────────────────────────────
    $(document).on('change', '.crm-status-select', function() {
        var $sel = $(this);
        var endpoint = $sel.data('endpoint');
        var id = $sel.data('id');
        $.ajax({
            url: CRM.restUrl + endpoint + '/' + id,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ status: $sel.val() }),
            headers: { 'X-WP-Nonce': CRM.restNonce },
            success: function() { showNotice('success', 'Staatus uuendatud'); },
            error: function() { showNotice('error', CRM.i18n.error); }
        });
    });

    // ── Init page-specific scripts ─────────────────────────────────────────────
    $(document).ready(function() {

        // ── Select2: searchable dropdowns for all CRM selects ─────────────────
        if (typeof $.fn.select2 !== 'undefined') {
            $('.crm-form-select, .crm-form-select-search').select2({
                width: '100%',
                allowClear: true,
                dropdownParent: $(document.body),
                placeholder: function() { return $(this).find('option[value=""]').text() || '— Vali —'; },
                language: {
                    noResults: function() { return 'Tulemusi ei leitud'; },
                    searching: function() { return 'Otsin...'; }
                }
            });
        }

        // ── Sidebar navigation group labels ───────────────────────────────────
        var navGroups = [
            { page: 'vesho-crm-clients',   label: '👥 KLIENDID' },
            { page: 'vesho-crm-calendar',  label: '🔧 HOOLDUSED' },
            { page: 'vesho-crm-workers',   label: '👷 TÖÖ' },
            { page: 'vesho-crm-invoices',  label: '💰 ARVELDUS' },
            { page: 'vesho-crm-inventory', label: '📦 LADU' },
            { page: 'vesho-crm-services',  label: '⚙️ SEADED' },
        ];
        navGroups.forEach(function(g) {
            var $a = $('#adminmenu a[href*="page=' + g.page + '"]').first();
            if ($a.length) {
                $a.closest('li').before('<li class="vesho-nav-group">' + g.label + '</li>');
            }
        });

        // Show alert from URL param
        var params = new URLSearchParams(window.location.search);
        var msg = params.get('msg');
        if (msg === 'added')   showNotice('success', 'Lisatud!');
        if (msg === 'updated') showNotice('success', 'Uuendatud!');
        if (msg === 'deleted') showNotice('success', 'Kustutatud!');
        if (msg === 'saved')   showNotice('success', 'Salvestatud!');
        if (msg === 'error')   showNotice('error', 'Viga!');

        // Inject scanner/QR modal into page
        if (!document.getElementById('crm-scanner-modal')) {
            $('body').append('<div class="crm-scanner-modal" id="crm-scanner-modal">'
                + '<div class="crm-scanner-box">'
                + '<button class="crm-scanner-close" id="crm-scanner-close">✕</button>'
                + '<div id="crm-scanner-tabs" style="display:flex;gap:0;border-bottom:2px solid #dce8ef;margin-bottom:16px">'
                + '<button class="crm-scanner-tab active" data-mode="scan" style="flex:1;padding:9px;background:none;border:none;border-bottom:2px solid #00b4c8;font-size:13px;font-weight:600;color:#00b4c8;cursor:pointer;margin-bottom:-2px">📷 Skanner</button>'
                + '<button class="crm-scanner-tab" data-mode="qr" style="flex:1;padding:9px;background:none;border:none;border-bottom:2px solid transparent;font-size:13px;font-weight:600;color:#6b8599;cursor:pointer;margin-bottom:-2px">QR Generaator</button>'
                + '<button class="crm-scanner-tab" data-mode="ean" style="flex:1;padding:9px;background:none;border:none;border-bottom:2px solid transparent;font-size:13px;font-weight:600;color:#6b8599;cursor:pointer;margin-bottom:-2px">EAN Kood</button>'
                + '</div>'
                // Scanner panel
                + '<div id="crm-mode-scan">'
                + '<video id="crm-scanner-video" playsinline></video>'
                + '<div class="crm-scanner-result" id="crm-scan-result"></div>'
                + '<div style="margin-top:10px;display:flex;gap:8px">'
                + '<button id="crm-scan-start" class="crm-btn crm-btn-primary crm-btn-sm">▶ Käivita</button>'
                + '<button id="crm-scan-stop" class="crm-btn crm-btn-outline crm-btn-sm" style="display:none">⏹ Peata</button>'
                + '<button id="crm-scan-copy" class="crm-btn crm-btn-outline crm-btn-sm" style="display:none">📋 Kopeeri</button>'
                + '</div></div>'
                // QR generator panel
                + '<div id="crm-mode-qr" style="display:none">'
                + '<label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:6px">Tekst / URL QR koodile</label>'
                + '<input id="crm-qr-input" type="text" placeholder="nt. seadme ID, URL..." style="width:100%;padding:9px 11px;border:1px solid #dce8ef;border-radius:7px;font-size:13px;box-sizing:border-box;margin-bottom:10px">'
                + '<button id="crm-qr-gen" class="crm-btn crm-btn-primary crm-btn-sm">Genereeri QR</button>'
                + '<div class="crm-qr-output" id="crm-qr-output"></div>'
                + '<div style="margin-top:10px;display:flex;gap:8px" id="crm-qr-actions" style="display:none">'
                + '<button id="crm-qr-download" class="crm-btn crm-btn-outline crm-btn-sm">⬇ Laadi alla</button>'
                + '<button id="crm-qr-print" class="crm-btn crm-btn-outline crm-btn-sm">🖨️ Prindi</button>'
                + '</div></div>'
                // EAN generator panel
                + '<div id="crm-mode-ean" style="display:none">'
                + '<label style="font-size:12px;font-weight:600;color:#0d1f2d;display:block;margin-bottom:6px">EAN-13 number (12 numbrit)</label>'
                + '<input id="crm-ean-input" type="text" maxlength="12" placeholder="nt. 460012345678" style="width:100%;padding:9px 11px;border:1px solid #dce8ef;border-radius:7px;font-size:13px;box-sizing:border-box;margin-bottom:10px;font-family:monospace">'
                + '<button id="crm-ean-gen" class="crm-btn crm-btn-primary crm-btn-sm">Genereeri EAN</button>'
                + '<div class="crm-qr-output" id="crm-ean-output" style="font-family:monospace;font-size:22px;letter-spacing:3px;margin-top:12px"></div>'
                + '</div>'
                + '</div></div>');
        }

        // Scanner modal open/close
        $(document).on('click', '#crm-scanner-close', function() {
            stopScan();
            $('#crm-scanner-modal').removeClass('open');
        });
        $(document).on('click', '.crm-open-scanner', function() {
            var mode = $(this).data('mode') || 'scan';
            switchScanMode(mode);
            $('#crm-scanner-modal').addClass('open');
        });
        $(document).on('click', '.crm-scanner-tab', function() {
            switchScanMode($(this).data('mode'));
        });

        function switchScanMode(mode) {
            stopScan();
            $('.crm-scanner-tab').each(function() {
                var active = $(this).data('mode') === mode;
                $(this).toggleClass('active', active)
                    .css({'color': active ? '#00b4c8' : '#6b8599', 'border-bottom-color': active ? '#00b4c8' : 'transparent'});
            });
            $('#crm-mode-scan, #crm-mode-qr, #crm-mode-ean').hide();
            $('#crm-mode-' + mode).show();
        }

        // ── Barcode scanner (jsQR via CDN) ────────────────────────────────────
        var scanStream = null, scanCanvas = null, scanCtx = null, scanning = false;

        $('#crm-scan-start').on('click', function() {
            if (!navigator.mediaDevices) { showNotice('error','Kaamera pole saadaval'); return; }
            navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(stream) {
                scanStream = stream;
                var video = document.getElementById('crm-scanner-video');
                video.srcObject = stream;
                video.play();
                scanning = true;
                $('#crm-scan-start').hide();
                $('#crm-scan-stop').show();
                if (!scanCanvas) { scanCanvas = document.createElement('canvas'); scanCtx = scanCanvas.getContext('2d'); }

                // Load jsQR dynamically
                if (!window.jsQR) {
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                    s.onload = function() { scanLoop(video); };
                    document.head.appendChild(s);
                } else {
                    scanLoop(video);
                }
            }).catch(function() { showNotice('error','Kaamera juurdepääs keelatud'); });
        });

        function scanLoop(video) {
            if (!scanning) return;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                scanCanvas.height = video.videoHeight;
                scanCanvas.width  = video.videoWidth;
                scanCtx.drawImage(video, 0, 0, scanCanvas.width, scanCanvas.height);
                var img = scanCtx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
                var code = window.jsQR && window.jsQR(img.data, img.width, img.height);
                if (code) {
                    var result = code.data;
                    $('#crm-scan-result').show().text(result);
                    $('#crm-scan-copy').show();
                    $('#crm-scan-copy').off('click').on('click', function() {
                        navigator.clipboard && navigator.clipboard.writeText(result);
                        showNotice('success','Kopeeritud: ' + result);
                    });
                    stopScan();
                    return;
                }
            }
            requestAnimationFrame(function() { scanLoop(video); });
        }

        function stopScan() {
            scanning = false;
            if (scanStream) { scanStream.getTracks().forEach(function(t){t.stop();}); scanStream=null; }
            $('#crm-scan-start').show();
            $('#crm-scan-stop').hide();
        }
        $('#crm-scan-stop').on('click', stopScan);

        // ── QR Generator ─────────────────────────────────────────────────────
        $('#crm-qr-gen').on('click', function() {
            var text = $('#crm-qr-input').val().trim();
            if (!text) return;
            var out = document.getElementById('crm-qr-output');
            out.innerHTML = '';

            function generateQR() {
                var qr = new window.QRCode(out, {
                    text: text, width: 200, height: 200,
                    colorDark: '#0d1f2d', colorLight: '#ffffff',
                    correctLevel: window.QRCode.CorrectLevel.H
                });
                $('#crm-qr-actions').show();
                $('#crm-qr-download').off('click').on('click', function() {
                    var img = out.querySelector('img') || out.querySelector('canvas');
                    if (!img) return;
                    var src = img.tagName==='IMG' ? img.src : img.toDataURL();
                    var a = document.createElement('a');
                    a.href = src; a.download = 'qr-' + text.slice(0,20) + '.png'; a.click();
                });
                $('#crm-qr-print').off('click').on('click', function() {
                    var img = out.querySelector('img') || out.querySelector('canvas');
                    if (!img) return;
                    var src = img.tagName==='IMG' ? img.src : img.toDataURL();
                    var w = window.open('');
                    w.document.write('<img src="'+src+'" style="width:200px"><br><p>'+text+'</p>');
                    w.print(); w.close();
                });
            }

            if (!window.QRCode) {
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
                s.onload = generateQR;
                document.head.appendChild(s);
            } else {
                generateQR();
            }
        });

        // ── EAN-13 Generator ──────────────────────────────────────────────────
        $('#crm-ean-gen').on('click', function() {
            var num = $('#crm-ean-input').val().replace(/\D/g,'');
            if (num.length < 12) { showNotice('error','Sisesta täpselt 12 numbrit'); return; }
            num = num.slice(0,12);
            // Calculate check digit
            var sum = 0;
            for (var i=0; i<12; i++) sum += parseInt(num[i]) * (i%2===0 ? 1 : 3);
            var check = (10 - (sum % 10)) % 10;
            var ean = num + check;
            var out = document.getElementById('crm-ean-output');
            out.innerHTML = '<div style="font-size:28px;letter-spacing:4px;margin-bottom:8px">'+ean+'</div>'
                + '<div style="font-size:12px;color:#6b8599">EAN-13: '+ean+'</div>'
                + '<button class="crm-btn crm-btn-outline crm-btn-sm" style="margin-top:10px" onclick="navigator.clipboard&&navigator.clipboard.writeText(\''+ean+'\')">📋 Kopeeri</button>';
        });

    });

    // ── Mobile: auto data-label tabelitele ────────────────────────────────────
    // Loeb veeru nimed thead-ist ja lisab iga td-le data-label atribuudi.
    // CSS kasutab seda pseudo-elemendina kaardikujul mobiilivaates.
    function initMobileTableLabels() {
        document.querySelectorAll('.crm-table').forEach(function(table) {
            var headers = [];
            table.querySelectorAll('thead th').forEach(function(th) {
                headers.push(th.textContent.trim());
            });
            if (!headers.length) return;
            table.querySelectorAll('tbody tr').forEach(function(row) {
                row.querySelectorAll('td').forEach(function(td, i) {
                    if (headers[i] && headers[i] !== 'Toimingud') {
                        td.setAttribute('data-label', headers[i]);
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileTableLabels);
    } else {
        initMobileTableLabels();
    }

})(jQuery);
