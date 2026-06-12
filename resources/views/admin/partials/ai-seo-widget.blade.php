{{--
  دستیار هوشمند AI برای تولید محتوای SEO-محور
  پارامترها:
    $aiType          — نوع محتوا: article | product_content | blog | product
    $aiWidgetId      — شناسه یکتا (فقط حروف/عدد/خط تیره)
    $aiTitleId       — id فیلد عنوان (اختیاری)
    $aiBodyId        — id textarea یا TinyMCE editor بدنه (اختیاری)
    $aiExcerptId     — id فیلد خلاصه (اختیاری)
    $aiMetaTitleId   — id فیلد meta title (اختیاری)
    $aiMetaDescId    — id فیلد meta description (اختیاری)
    $aiTagsId        — id فیلد برچسب‌ها (اختیاری)
    $aiUseTinymce    — آیا body از TinyMCE استفاده می‌کند؟ (bool)
--}}
@php
  $wid      = $aiWidgetId    ?? 'ai';
  $fnSafe   = 'aiSeoGen_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $wid);
  $type     = $aiType        ?? 'article';
  $titleId  = $aiTitleId     ?? '';
  $bodyId   = $aiBodyId      ?? '';
  $excId    = $aiExcerptId   ?? '';
  $mtitleId = $aiMetaTitleId ?? '';
  $mdescId  = $aiMetaDescId  ?? '';
  $tagsId   = $aiTagsId      ?? '';
  $useTmc   = $aiUseTinymce  ?? false;
@endphp

<div id="ai-widget-{{ $wid }}"
     style="background:linear-gradient(135deg,#f0f4ff 0%,#fdf4ff 100%);
            border:1.5px solid #c7d2fe; border-radius:1rem;
            padding:1.25rem 1.5rem; margin-bottom:1.5rem;">

  {{-- هدر --}}
  <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.85rem;">
    <x-core-ui::icon name="spark" style="width:1.35rem;height:1.35rem;flex-shrink:0;color:#6366f1" />
    <span style="font-weight:700;color:#3730a3;font-size:0.95rem;">
      دستیار هوش مصنوعی — تولید محتوای SEO-محور
    </span>
    <span style="font-size:0.72rem;color:#818cf8;margin-right:0.25rem;">
      (GapGPT · DeepSeek · Claude)
    </span>
  </div>

  {{-- ورودی + دکمه --}}
  <div style="display:flex;gap:0.6rem;flex-wrap:wrap;align-items:flex-start;">
    <input id="ai-prompt-{{ $wid }}" type="text"
      placeholder="موضوع را بنویسید... مثلاً: کفش ورزشی نایک"
      style="flex:1;min-width:220px;border:1.5px solid #c7d2fe;border-radius:0.6rem;
             padding:0.55rem 0.85rem;font-size:0.875rem;outline:none;
             background:#fff;font-family:inherit;"
      onkeydown="if(event.key==='Enter'){event.preventDefault();window.{{ $fnSafe }}&&window.{{ $fnSafe }}();}">
    <button type="button" id="ai-btn-{{ $wid }}"
      onclick="window.{{ $fnSafe }}&&window.{{ $fnSafe }}()"
      style="padding:0.55rem 1.2rem;background:#4f46e5;color:#fff;border:none;
             border-radius:0.6rem;font-size:0.875rem;font-weight:600;cursor:pointer;
             display:flex;align-items:center;gap:0.4rem;font-family:inherit;"
      onmouseover="this.style.background='#4338ca'"
      onmouseout="this.style.background='#4f46e5'">
      <svg id="ai-spin-{{ $wid }}"
           style="display:none;width:1rem;height:1rem;animation:_aiSpinKf 1s linear infinite"
           fill="none" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke="#fff" stroke-width="4" opacity=".25"/>
        <path d="M4 12a8 8 0 018-8" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
      </svg>
      <span id="ai-btn-label-{{ $wid }}">✨ تولید محتوا</span>
    </button>
  </div>

  {{-- پیام وضعیت --}}
  <div id="ai-msg-{{ $wid }}"
       style="display:none;margin-top:0.6rem;font-size:0.82rem;
              padding:0.4rem 0.75rem;border-radius:0.4rem;"></div>

  {{-- کلمات کلیدی تصویر --}}
  <div id="ai-img-kw-{{ $wid }}" style="display:none;margin-top:0.75rem;">
    <div style="font-size:0.78rem;color:#6366f1;font-weight:600;margin-bottom:0.3rem;">
      🖼️ کلمات کلیدی پیشنهادی برای جستجوی تصویر:
    </div>
    <div id="ai-img-kw-list-{{ $wid }}" style="display:flex;flex-wrap:wrap;gap:0.35rem;"></div>
    <div style="font-size:0.72rem;color:#9ca3af;margin-top:0.25rem;">
      در Unsplash، Adobe Stock یا سایت‌های مشابه جستجو کنید.
    </div>
  </div>
</div>

{{-- Keyframe CSS (فقط یکبار) --}}
@once
<style>@keyframes _aiSpinKf{to{transform:rotate(360deg)}}</style>
@endonce

{{-- اسکریپت مستقیم (بدون @push تا از مشکل ترتیب Blade جلوگیری شود) --}}
<script>
(function () {
  var WID       = @json($wid);
  var FN        = @json($fnSafe);
  var TYPE      = @json($type);
  var TITLE_ID  = @json($titleId);
  var BODY_ID   = @json($bodyId);
  var EXC_ID    = @json($excId);
  var MTITLE_ID = @json($mtitleId);
  var MDESC_ID  = @json($mdescId);
  var TAGS_ID   = @json($tagsId);
  var USE_TMC   = @json($useTmc);
  var ENDPOINT  = @json(route('admin.site-content.ai-generate'));
  var CSRF      = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var _pollT    = null;

  function showMsg(txt, kind) {
    var el = document.getElementById('ai-msg-' + WID);
    if (!el) return;
    el.textContent   = txt;
    el.style.display = txt ? 'block' : 'none';
    el.style.background = kind==='error'?'#fee2e2':kind==='ok'?'#d1fae5':'#fef3c7';
    el.style.color      = kind==='error'?'#991b1b':kind==='ok'?'#065f46':'#92400e';
  }

  function setLoading(on) {
    var btn   = document.getElementById('ai-btn-'       + WID);
    var spin  = document.getElementById('ai-spin-'      + WID);
    var label = document.getElementById('ai-btn-label-' + WID);
    if (!btn) return;
    btn.disabled = on;
    if (spin)  spin.style.display = on ? 'block' : 'none';
    if (label) label.textContent  = on ? 'در حال تولید...' : '✨ تولید محتوا';
  }

  function setField(id, val) {
    if (!id || !val) return;
    var el = document.getElementById(id);
    if (el) el.value = val;
  }

  function applyResult(data) {
    setField(TITLE_ID, data.title);
    setField(EXC_ID,   data.excerpt);
    setField(MTITLE_ID, MTITLE_ID && !document.getElementById(MTITLE_ID)?.value ? data.title : '');
    setField(MDESC_ID, data.meta_description);
    setField(TAGS_ID,  data.tags);

    if (BODY_ID && data.html_body) {
      var isTmc = USE_TMC && typeof tinymce !== 'undefined' && tinymce.get(BODY_ID);
      if (isTmc) {
        tinymce.get(BODY_ID).setContent(data.html_body);
      } else {
        var el = document.getElementById(BODY_ID);
        if (el) el.value = data.html_body;
      }
    }

    if (data.image_keywords && data.image_keywords.length) {
      var kDiv = document.getElementById('ai-img-kw-'      + WID);
      var kLst = document.getElementById('ai-img-kw-list-' + WID);
      if (kDiv && kLst) {
        kLst.innerHTML = data.image_keywords.map(function(k){
          return '<span style="font-size:.78rem;padding:.2rem .55rem;background:#ede9fe;'
               + 'color:#5b21b6;border-radius:9999px;">' + k + '</span>';
        }).join('');
        kDiv.style.display = 'block';
      }
    }
    showMsg('✅ محتوا تولید شد — می‌توانید ویرایش کنید.', 'ok');
  }

  function pollJob(jobId) {
    var attempts = 0;
    _pollT = setInterval(function () {
      attempts++;
      fetch('/api/ai-queue/' + jobId + '/status', {headers:{Accept:'application/json'}})
        .then(function(r){return r.json();})
        .then(function(d){
          if (d.status === 'done') {
            clearInterval(_pollT); setLoading(false);
            var raw = d.data;
            if (typeof raw === 'string') { try { raw = JSON.parse(raw); } catch(e){} }
            if (raw && typeof raw === 'object' && raw.title) {
              applyResult(raw);
            } else {
              showMsg('⚠️ محتوا دریافت شد اما ساختار کامل ندارد.', 'warn');
            }
          } else if (d.status === 'failed') {
            clearInterval(_pollT); setLoading(false);
            showMsg('❌ ' + (d.error || 'پردازش AI ناموفق بود'), 'error');
          } else {
            showMsg('⏳ در حال پردازش... (' + (attempts*3) + 'ث)', 'warn');
            if (attempts >= 80) {
              clearInterval(_pollT); setLoading(false);
              showMsg('⏱️ پاسخ AI دریافت نشد — API Key یا ai-worker را بررسی کنید.', 'error');
            }
          }
        }).catch(function(){});
    }, 3000);
  }

  window[FN] = function () {
    var inp    = document.getElementById('ai-prompt-' + WID);
    var prompt = inp ? inp.value.trim() : '';
    if (!prompt) {
      if (inp) inp.focus();
      showMsg('موضوع را وارد کنید.', 'error');
      return;
    }
    if (_pollT) clearInterval(_pollT);
    setLoading(true);
    showMsg('', '');

    fetch(ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type':  'application/json',
        'X-CSRF-TOKEN':  CSRF,
        'Accept':        'application/json',
      },
      body: JSON.stringify({type: TYPE, prompt: prompt, mode: 'seo'}),
    })
    .then(function(r){return r.json();})
    .then(function(data){
      if (!data.success && data.queued) {
        showMsg('⏳ در صف پردازش — لطفاً صبر کنید...', 'warn');
        pollJob(data.job_id);
        return;
      }
      setLoading(false);
      if (!data.success) {
        showMsg('❌ ' + (data.error || 'خطا در تولید محتوا'), 'error');
        return;
      }
      applyResult(data.data);
    })
    .catch(function(e){
      setLoading(false);
      showMsg('❌ خطا در اتصال: ' + e.message, 'error');
    });
  };
})();
</script>
