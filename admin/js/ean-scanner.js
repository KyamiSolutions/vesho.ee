/**
 * Vesho CRM – EAN/Barcode Scanner Module
 * Uses ZXing BrowserMultiFormatReader via CDN
 * Fallback: BarcodeDetector API (Chrome/Edge)
 * Vanilla JS, no dependencies beyond ZXing
 */
(function(window) {
    'use strict';

    var VeshoScanner = {
        _reader: null,
        _overlay: null,
        _lastScan: 0,
        _startTime: 0,
        _fired: false,
        _animFrame: null,
        _stream: null,

        /**
         * Open the scanner overlay.
         * @param {object} opts
         *   onScan(code)    – called when barcode detected
         *   onClose()       – called when closed without scan
         *   autoConfirm     – instant scan without confirm button (default false)
         *   manualInput     – show manual input field (default true)
         *   title           – overlay title (default 'Skänni vöötkoodi')
         *   wide            – wider target area for longer barcodes (default false)
         */
        open: function(opts) {
            opts = opts || {};
            if (this._overlay) this.close();

            var self = this;
            this._lastScan = 0;
            this._startTime = Date.now();
            this._fired = false;

            // Build overlay HTML
            var tealColor = '#00b4c8';
            var overlay = document.createElement('div');
            overlay.id = 'vesho-ean-overlay';
            overlay.innerHTML = [
                '<div style="position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,0.94);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px">',
                  '<div style="width:100%;max-width:420px">',

                    // Header
                    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">',
                      '<span style="color:#fff;font-weight:700;font-size:16px;font-family:system-ui">',
                        opts.title || 'Skänni vöötkoodi',
                      '</span>',
                      '<button id="vesho-scan-close" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;color:#fff;font-size:18px;width:36px;height:36px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1">✕</button>',
                    '</div>',

                    // Camera area
                    '<div id="vesho-scan-camera-wrap" style="position:relative;border-radius:14px;overflow:hidden;background:#000;min-height:240px">',
                      '<video id="vesho-scan-video" style="width:100%;display:block;max-height:60vh;object-fit:cover" playsinline muted autoplay></video>',

                      // Targeting frame
                      '<div id="vesho-scan-frame" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">',
                        '<div style="position:relative;width:',opts.wide?'88%':'72%',';height:',opts.wide?'90px':'72px',';box-shadow:0 0 0 9999px rgba(0,0,0,.4);border-radius:6px">',
                          '<div style="position:absolute;top:0;left:0;width:22px;height:22px;border-top:3px solid '+tealColor+';border-left:3px solid '+tealColor+';border-radius:4px 0 0 0"></div>',
                          '<div style="position:absolute;top:0;right:0;width:22px;height:22px;border-top:3px solid '+tealColor+';border-right:3px solid '+tealColor+';border-radius:0 4px 0 0"></div>',
                          '<div style="position:absolute;bottom:0;left:0;width:22px;height:22px;border-bottom:3px solid '+tealColor+';border-left:3px solid '+tealColor+';border-radius:0 0 0 4px"></div>',
                          '<div style="position:absolute;bottom:0;right:0;width:22px;height:22px;border-bottom:3px solid '+tealColor+';border-right:3px solid '+tealColor+';border-radius:0 0 4px 0"></div>',
                        '</div>',
                      '</div>',

                      // Detected overlay
                      '<div id="vesho-scan-detected" style="display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,.78);gap:10px">',
                        '<span style="color:#4ade80;font-size:40px">✓</span>',
                        '<span id="vesho-scan-code-display" style="color:#fff;font-weight:700;font-size:18px;letter-spacing:1px;font-family:monospace"></span>',
                        '<div style="display:flex;gap:10px;margin-top:4px">',
                          '<button id="vesho-scan-use" style="background:'+tealColor+';border:none;border-radius:10px;color:#fff;font-size:14px;font-weight:700;padding:10px 24px;cursor:pointer">Kasuta</button>',
                          '<button id="vesho-scan-again" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;color:#fff;font-size:14px;font-weight:600;padding:10px 18px;cursor:pointer">Uuesti</button>',
                        '</div>',
                      '</div>',

                      // Error overlay
                      '<div id="vesho-scan-error" style="display:none;padding:32px;text-align:center;color:#fca5a5;font-size:14px;font-family:system-ui"></div>',
                    '</div>',

                    // Manual input
                    (opts.manualInput !== false) ? [
                      '<div style="margin-top:12px;display:flex;gap:8px">',
                        '<input id="vesho-scan-manual" type="text" placeholder="Sisesta käsitsi (EAN/SKU)..." ',
                          'style="flex:1;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);border-radius:8px;color:#fff;font-size:14px;font-family:monospace;padding:9px 13px;outline:none" autocomplete="off">',
                        '<button id="vesho-scan-manual-ok" style="background:'+tealColor+';border:none;border-radius:8px;color:#fff;font-size:14px;font-weight:700;padding:9px 16px;cursor:pointer;opacity:.4" disabled>OK</button>',
                      '</div>',
                    ].join('') : '<p style="color:rgba(255,255,255,.4);text-align:center;font-size:12px;margin-top:10px;font-family:system-ui">Suuna kaamera vöötkoodi poole</p>',

                  '</div>',
                '</div>'
            ].join('');

            document.body.appendChild(overlay);
            this._overlay = overlay;

            // Wire up close button
            document.getElementById('vesho-scan-close').onclick = function() {
                self.close();
                if (opts.onClose) opts.onClose();
            };

            // ESC to close
            this._escHandler = function(e) {
                if (e.key === 'Escape') { self.close(); if (opts.onClose) opts.onClose(); }
            };
            document.addEventListener('keydown', this._escHandler);

            // Manual input
            if (opts.manualInput !== false) {
                var manualInput = document.getElementById('vesho-scan-manual');
                var manualOk = document.getElementById('vesho-scan-manual-ok');
                manualInput.addEventListener('input', function() {
                    var val = manualInput.value.trim();
                    manualOk.disabled = !val;
                    manualOk.style.opacity = val ? '1' : '.4';
                });
                manualInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && manualInput.value.trim()) {
                        self._onDetected(manualInput.value.trim(), opts);
                    }
                });
                manualOk.onclick = function() {
                    if (manualInput.value.trim()) self._onDetected(manualInput.value.trim(), opts);
                };
            }

            // Start camera
            this._startCamera(opts);
        },

        _startCamera: function(opts) {
            var self = this;
            var video = document.getElementById('vesho-scan-video');
            if (!video) return;

            // Try ZXing first (loaded via CDN in enqueue)
            if (window.ZXing && window.ZXing.BrowserMultiFormatReader) {
                self._reader = new window.ZXing.BrowserMultiFormatReader();
                self._reader.decodeFromConstraints(
                    { video: { facingMode: 'environment' } },
                    video,
                    function(result, err) {
                        if (!result) return;
                        var text = result.getText();
                        var now = Date.now();
                        if (now - self._startTime < 600) return;
                        if (now - self._lastScan < 1500) return;
                        self._lastScan = now;
                        self._onDetected(text, opts);
                    }
                ).catch(function(e) {
                    self._showError('Kaamera ei avane: ' + (e && e.message ? e.message : e));
                });
                return;
            }

            // Fallback: BarcodeDetector API (Chrome 83+, Edge, Android Chrome)
            if ('BarcodeDetector' in window) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(function(stream) {
                        self._stream = stream;
                        video.srcObject = stream;
                        video.play();
                        var detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','qr_code','upc_a','upc_e','itf'] });
                        function detect() {
                            if (!document.getElementById('vesho-scan-video')) return;
                            detector.detect(video).then(function(codes) {
                                if (codes.length > 0) {
                                    var now = Date.now();
                                    if (now - self._startTime < 600) { self._animFrame = requestAnimationFrame(detect); return; }
                                    if (now - self._lastScan < 1500) { self._animFrame = requestAnimationFrame(detect); return; }
                                    self._lastScan = now;
                                    self._onDetected(codes[0].rawValue, opts);
                                } else {
                                    self._animFrame = requestAnimationFrame(detect);
                                }
                            }).catch(function() { self._animFrame = requestAnimationFrame(detect); });
                        }
                        video.addEventListener('loadeddata', function() { self._animFrame = requestAnimationFrame(detect); });
                    })
                    .catch(function(e) {
                        self._showError('Kaamera ei avane. Kontrolli luba. (' + (e.message || e) + ')');
                    });
                return;
            }

            // No scanner support — show manual input only
            self._showError('Selles brauseris kaameraskännerit ei toetata. Kasuta käsitsi sisestust.');
        },

        _onDetected: function(code, opts) {
            if (this._fired && opts.autoConfirm) return;

            if (opts.autoConfirm) {
                this._fired = true;
                this.close();
                if (opts.onScan) opts.onScan(code);
                return;
            }

            // Show confirm UI
            var det = document.getElementById('vesho-scan-detected');
            var codeDisplay = document.getElementById('vesho-scan-code-display');
            if (det) {
                codeDisplay.textContent = code;
                det.style.display = 'flex';

                var self = this;
                document.getElementById('vesho-scan-use').onclick = function() {
                    self.close();
                    if (opts.onScan) opts.onScan(code);
                };
                document.getElementById('vesho-scan-again').onclick = function() {
                    self._lastScan = 0;
                    self._startTime = Date.now();
                    det.style.display = 'none';
                };
            }
        },

        _showError: function(msg) {
            var errEl = document.getElementById('vesho-scan-error');
            var videoEl = document.getElementById('vesho-scan-video');
            var frameEl = document.getElementById('vesho-scan-frame');
            if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
            if (videoEl) videoEl.style.display = 'none';
            if (frameEl) frameEl.style.display = 'none';
        },

        close: function() {
            // Stop ZXing reader
            if (this._reader) {
                try { ZXing.BrowserMultiFormatReader.releaseAllStreams(); } catch(e) {}
                this._reader = null;
            }
            // Stop BarcodeDetector stream
            if (this._stream) {
                this._stream.getTracks().forEach(function(t) { t.stop(); });
                this._stream = null;
            }
            // Stop animation frame
            if (this._animFrame) { cancelAnimationFrame(this._animFrame); this._animFrame = null; }
            // Remove ESC handler
            if (this._escHandler) { document.removeEventListener('keydown', this._escHandler); this._escHandler = null; }
            // Remove overlay
            if (this._overlay && this._overlay.parentNode) {
                this._overlay.parentNode.removeChild(this._overlay);
            }
            this._overlay = null;
            this._fired = false;
        }
    };

    window.VeshoScanner = VeshoScanner;

})(window);
