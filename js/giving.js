/* =========================================================
   GIVING PAGE — JavaScript
   Real-time totals, jump-to-category, M-Pesa STK Push
   ========================================================= */

(function () {
  'use strict';

  // ─── DOM refs ───
  const allInputs      = document.querySelectorAll('.ag-input');
  const totalTitheEl   = document.getElementById('totalTithe');
  const totalLocalEl   = document.getElementById('totalLocal');
  const totalConfEl    = document.getElementById('totalConference');
  const totalWorldEl   = document.getElementById('totalWorld');
  const grandTotalEl   = document.getElementById('grandTotal');
  const continueBtn    = document.getElementById('continueBtn');
  const stkModal       = document.getElementById('stkModal');
  const modalBody      = document.getElementById('modalBody');
  const modalClose     = document.getElementById('modalClose');
  const phoneInput     = document.getElementById('mpesaPhone');

  // ─── Format currency ───
  function fmt(n) {
    return 'KSh ' + Number(n).toLocaleString('en-KE');
  }

  // ─── Compute totals ───
  function recalc() {
    let tithe = 0, local = 0, conf = 0, world = 0;

    allInputs.forEach(function (inp) {
      const val = parseFloat(inp.value) || 0;
      const cat = inp.dataset.category;
      if (cat === 'tithe')      tithe += val;
      else if (cat === 'local') local += val;
      else if (cat === 'conference') conf += val;
      else if (cat === 'world') world += val;
    });

    totalTitheEl.textContent = fmt(tithe);
    totalLocalEl.textContent = fmt(local);
    totalConfEl.textContent  = fmt(conf);
    totalWorldEl.textContent = fmt(world);
    grandTotalEl.textContent = fmt(tithe + local + conf + world);
  }

  allInputs.forEach(function (inp) {
    inp.addEventListener('input', recalc);
  });

  // ─── Jump to category ───
  document.querySelectorAll('.ag-jump-item').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var cat = this.dataset.jump;
      var target;
      if (cat === 'tithe') {
        target = document.querySelector('[data-category="tithe"]');
      } else if (cat === 'local') {
        target = document.getElementById('local-offerings');
      } else {
        target = document.querySelector('[data-category="' + cat + '"]');
        if (target) target = target.closest('.ag-category-card');
      }
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Briefly highlight
        target.style.boxShadow = '0 0 0 3px #006B75';
        setTimeout(function () { target.style.boxShadow = ''; }, 1200);
      }
    });
  });

  // ─── Gather donation items ───
  function gatherDonations() {
    var items = [];
    allInputs.forEach(function (inp) {
      var val = parseFloat(inp.value) || 0;
      if (val > 0) {
        items.push({
          category: inp.dataset.category,
          name: inp.dataset.name || 'Tithe',
          amount: val
        });
      }
    });
    return items;
  }

  // ─── Validate phone ───
  function cleanPhone(raw) {
    var digits = raw.replace(/\D/g, '');
    // Accept 07XXXXXXXX or 7XXXXXXXX or 2547XXXXXXXX
    if (digits.startsWith('254') && digits.length === 12) return digits;
    if (digits.startsWith('0') && digits.length === 10) return '254' + digits.slice(1);
    if (digits.length === 9 && (digits.startsWith('7') || digits.startsWith('1'))) return '254' + digits;
    return null;
  }

  // ─── CONTINUE TO DONATE ───
  continueBtn.addEventListener('click', function () {
    var donations = gatherDonations();
    if (donations.length === 0) {
      showToast('Please enter at least one offering amount.');
      return;
    }

    var phone = cleanPhone(phoneInput.value);
    if (!phone) {
      showToast('Please enter a valid Safaricom phone number above.');
      phoneInput.focus();
      return;
    }

    // Build summary
    var total = donations.reduce(function (s, d) { return s + d.amount; }, 0);
    var rows = donations.map(function (d) {
      return '<tr><td>' + escHtml(d.name) + '</td><td>' + fmt(d.amount) + '</td></tr>';
    }).join('');

    modalBody.innerHTML =
      '<div class="modal-summary">' +
        '<h3>Donation Summary</h3>' +
        '<table class="modal-summary-table">' + rows +
        '<tr class="modal-total-row"><td>TOTAL</td><td>' + fmt(total) + '</td></tr>' +
        '</table>' +
      '</div>' +
      '<div class="modal-phone-confirm">' +
        '<label>M-Pesa Phone Number</label>' +
        '<input type="text" id="modalPhoneDisplay" value="+' + phone.slice(0,3) + ' ' + phone.slice(3,6) + ' ' + phone.slice(6,9) + ' ' + phone.slice(9) + '" readonly style="background:#fff;opacity:.7">' +
      '</div>' +
      '<button class="modal-btn-primary" id="confirmSTK">SEND M-PESA STK PUSH</button>';

    stkModal.classList.add('show');

    document.getElementById('confirmSTK').addEventListener('click', function () {
      initiateSTKPush(phone, total, donations);
    });
  });

  // ─── Modal close ───
  modalClose.addEventListener('click', function () { stkModal.classList.remove('show'); });
  stkModal.addEventListener('click', function (e) {
    if (e.target === stkModal) stkModal.classList.remove('show');
  });

  // ─── STK Push integration ───
  function initiateSTKPush(phone, amount, donations) {
    // Show loading
    modalBody.innerHTML =
      '<div class="modal-status">' +
        '<div class="spinner"></div>' +
        '<p>Sending M-Pesa STK push to <strong>+' + phone + '</strong>…</p>' +
        '<p style="font-size:0.8rem;color:#888">Please check your phone and enter your M-Pesa PIN.</p>' +
      '</div>';

    /* ============================================================
       M-PESA STK PUSH API INTEGRATION
       ============================================================
       
       To make this work in production, you need a backend server
       that communicates with the Safaricom Daraja API.
       
       BACKEND ENDPOINT STRUCTURE:
       
       POST /api/mpesa/stkpush
       {
         "phone": "254712345678",
         "amount": 1000,
         "accountReference": "KUSDA-TITHE",
         "transactionDesc": "Tithe & Offerings - Kisii University SDA Church",
         "donations": [
           { "name": "Tithe", "amount": 500 },
           { "name": "Church Budget", "amount": 500 }
         ]
       }
       
       RESPONSE:
       {
         "success": true,
         "CheckoutRequestID": "ws_CO_DMZ_123456789_...",
         "MerchantRequestID": "12345-67890-1",
         "ResponseDescription": "Success. Request accepted for processing"
       }
       
       Then poll for completion:
       POST /api/mpesa/query
       { "CheckoutRequestID": "ws_CO_DMZ_123456789_..." }
       
       SAFARICOM DARAJA API SETUP:
       1. Register at https://developer.safaricom.co.ke
       2. Create an app → get Consumer Key & Secret
       3. Get shortcode (Paybill/Till Number)
       4. Set callback URL for payment confirmations
       5. Use Lipa Na M-Pesa Online API (STK Push)
       
       ============================================================ */

    // Build reference string
    var refParts = donations.map(function(d) { return d.name; });
    var accountRef = 'KUSDA-' + (refParts[0] || 'OFFERING').toUpperCase().replace(/\s+/g, '').slice(0, 12);
    var transDesc = 'Tithe & Offerings - Kisii University SDA Church';

    var payload = {
      phone: phone,
      amount: Math.ceil(amount),
      accountReference: accountRef,
      transactionDesc: transDesc,
      donations: donations
    };

    // ── PRODUCTION: Replace this URL with your backend endpoint ──
    var API_URL = '/api/mpesa/stkpush';

    fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(function (res) {
      if (!res.ok) throw new Error('Server error: ' + res.status);
      return res.json();
    })
    .then(function (data) {
      if (data.success) {
        // Start polling for result
        pollSTKResult(data.CheckoutRequestID, phone, amount);
      } else {
        showSTKError(data.message || 'STK push request failed. Please try again.');
      }
    })
    .catch(function (err) {
      console.error('STK Push Error:', err);
      // DEMO MODE: simulate STK push for testing
      simulateSTKPush(phone, amount);
    });
  }

  // ─── Poll for STK result ───
  function pollSTKResult(checkoutId, phone, amount) {
    var attempts = 0;
    var maxAttempts = 20; // Poll for ~60 seconds

    var pollInterval = setInterval(function () {
      attempts++;
      if (attempts > maxAttempts) {
        clearInterval(pollInterval);
        showSTKTimeout(phone);
        return;
      }

      fetch('/api/mpesa/query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ CheckoutRequestID: checkoutId })
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.ResultCode === '0' || data.ResultCode === 0) {
          clearInterval(pollInterval);
          showSTKSuccess(phone, amount, data.MpesaReceiptNumber || '');
        } else if (data.ResultCode && data.ResultCode !== '0') {
          clearInterval(pollInterval);
          showSTKError(data.ResultDesc || 'Transaction was cancelled or failed.');
        }
        // Otherwise keep polling
      })
      .catch(function () {
        // Network error during poll, continue
      });
    }, 3000);
  }

  // ─── DEMO: Simulated STK Push (remove in production) ───
  function simulateSTKPush(phone, amount) {
    modalBody.innerHTML =
      '<div class="modal-status">' +
        '<div class="spinner"></div>' +
        '<p style="font-weight:600;color:#333">⚠️ Demo Mode</p>' +
        '<p>Backend not connected. Simulating STK push to <strong>+' + phone + '</strong>…</p>' +
        '<p style="font-size:0.78rem;color:#888">In production, connect your Safaricom Daraja API backend.</p>' +
      '</div>';

    setTimeout(function () {
      showSTKSuccess(phone, amount, 'DEMO' + Date.now().toString().slice(-8));
    }, 3000);
  }

  // ─── Success / Error / Timeout screens ───
  function showSTKSuccess(phone, amount, receipt) {
    modalBody.innerHTML =
      '<div class="modal-status success">' +
        '<div class="status-icon">✅</div>' +
        '<h3 style="margin:0 0 8px;color:#2e7d32">Payment Successful!</h3>' +
        '<p>Your offering of <strong>' + fmt(amount) + '</strong> has been received.</p>' +
        (receipt ? '<p style="font-size:0.82rem;color:#666">M-Pesa Receipt: <strong>' + receipt + '</strong></p>' : '') +
        '<p style="font-size:0.82rem;color:#888">Phone: +' + phone + '</p>' +
        '<p style="margin-top:16px;font-size:0.85rem;color:#555">"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." — 2 Corinthians 9:7</p>' +
        '<button class="modal-btn-primary" style="margin-top:20px;background:#006B75" onclick="location.reload()">MAKE ANOTHER DONATION</button>' +
      '</div>';
  }

  function showSTKError(message) {
    modalBody.innerHTML =
      '<div class="modal-status error">' +
        '<div class="status-icon">❌</div>' +
        '<h3 style="margin:0 0 8px;color:#c62828">Payment Failed</h3>' +
        '<p>' + escHtml(message) + '</p>' +
        '<button class="modal-btn-primary" style="margin-top:20px" onclick="document.getElementById(\'stkModal\').classList.remove(\'show\')">TRY AGAIN</button>' +
      '</div>';
  }

  function showSTKTimeout(phone) {
    modalBody.innerHTML =
      '<div class="modal-status">' +
        '<div class="status-icon" style="color:#f57c00">⏳</div>' +
        '<h3 style="margin:0 0 8px;color:#f57c00">Request Timed Out</h3>' +
        '<p>We didn\'t receive a response from M-Pesa. Please check your phone for an STK push prompt, or try again.</p>' +
        '<button class="modal-btn-primary" style="margin-top:20px" onclick="document.getElementById(\'stkModal\').classList.remove(\'show\')">TRY AGAIN</button>' +
      '</div>';
  }

  // ─── Toast notification ───
  function showToast(msg) {
    var existing = document.querySelector('.ag-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'ag-toast';
    toast.textContent = msg;
    toast.style.cssText =
      'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
      'background:#333;color:#fff;padding:12px 24px;border-radius:8px;' +
      'font-size:0.88rem;z-index:3000;box-shadow:0 4px 20px rgba(0,0,0,.3);' +
      'animation:toastIn .3s ease';
    document.body.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 4000);
  }

  // ─── Utility ───
  function escHtml(s) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(s));
    return div.innerHTML;
  }

  // ─── Toast animation ───
  var style = document.createElement('style');
  style.textContent = '@keyframes toastIn{from{opacity:0;transform:translateX(-50%) translateY(20px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}';
  document.head.appendChild(style);

})();
