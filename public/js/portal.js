/* Vesho CRM – Client Portal JS */
(function() {
    'use strict';

    var P = window.VeshoPortal || {};

    // ── Tab switching (login page) ──
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.vpl-tab');
        if (!btn) return;
        var tabs = document.querySelectorAll('.vpl-tab');
        var panels = document.querySelectorAll('.vpl-panel');
        tabs.forEach(function(t){ t.classList.remove('active'); });
        panels.forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        var panel = document.getElementById('tab-' + btn.dataset.tab);
        if (panel) panel.classList.add('active');
    });

    // ── Portal tab switching ──
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.vp-tab[data-ptab]');
        if (!btn) return;
        e.preventDefault();
        var allTabs = document.querySelectorAll('.vp-tab[data-ptab]');
        var allPanels = document.querySelectorAll('.vp-panel[data-ptab]');
        allTabs.forEach(function(t){ t.classList.remove('active'); });
        allPanels.forEach(function(p){ p.style.display='none'; });
        btn.classList.add('active');
        var panel = document.querySelector('.vp-panel[data-ptab="'+btn.dataset.ptab+'"]');
        if (panel) panel.style.display='block';
    });

    // ── AJAX form helper ──
    function ajaxForm(form, successMsg, onSuccess) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var msg = form.querySelector('.vp-form-msg') || form.previousElementSibling;
            var btn = form.querySelector('[type="submit"]');
            var origText = btn ? btn.textContent : '';
            if (btn) { btn.disabled=true; btn.textContent='...'; }

            var fd = new FormData(form);

            fetch(P.ajaxUrl || '/wp-admin/admin-ajax.php', { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(d) {
                if (d.success) {
                    if (msg) { msg.style.display='block'; msg.className='vp-alert vp-alert-success'; msg.textContent=successMsg||'Salvestatud!'; }
                    if (onSuccess) onSuccess(d.data);
                    else setTimeout(function(){ location.reload(); }, 700);
                } else {
                    var errText = (d.data && d.data.message) ? d.data.message : 'Viga! Proovi uuesti.';
                    if (msg) { msg.style.display='block'; msg.className='vp-alert vp-alert-error'; msg.textContent=errText; }
                }
                if (btn) { btn.disabled=false; btn.textContent=origText; }
            })
            .catch(function() {
                if (msg) { msg.style.display='block'; msg.className='vp-alert vp-alert-error'; msg.textContent='Ühenduse viga'; }
                if (btn) { btn.disabled=false; btn.textContent=origText; }
            });
        });
    }

    // ── Init forms on page ──
    document.addEventListener('DOMContentLoaded', function() {
        // Book maintenance form
        var bookForm = document.getElementById('vp-book-form');
        if (bookForm) ajaxForm(bookForm, 'Broneeringutaotlus saadetud!');

        // Support ticket form
        var ticketForm = document.getElementById('vp-ticket-form');
        if (ticketForm) ajaxForm(ticketForm, 'Tugipilet esitatud!');

        // Profile form
        var profileForm = document.getElementById('vp-profile-form');
        if (profileForm) ajaxForm(profileForm, 'Profiil uuendatud!');
    });
})();
