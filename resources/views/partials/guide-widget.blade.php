{{-- ظˆغŒط¬طھ ط±ط§ظ‡ظ†ظ…ط§: ط¯ط± ط§ط¯ظ…غŒظ† ظ‡ظ…غŒط´ظ‡ ظ†ظ…ط§غŒط´ (ط§غŒظ† ظ¾ط§ط±ط´غŒط§ظ„ ظپظ‚ط· ط§ط² admin/layout ظپط±ط§ط®ظˆط§ظ†غŒ ظ…غŒâ€Œط´ظˆط¯). ظˆط§ط¨ط³طھظ‡ ط¨ظ‡ Vite ظ†غŒط³طھ. --}}
@php
    $guideContext = isset($guideContext) ? $guideContext : null;
    $gw = ['guide_widget_btn_bg' => '#2563eb', 'guide_widget_btn_text' => '#ffffff', 'guide_widget_panel_bg' => '#ffffff', 'guide_widget_panel_header_bg' => '#f9fafb', 'guide_widget_panel_text' => '#111827'];
    $posClasses = 'bottom-6 right-6';
    try {
        $gw = \App\Helpers\PlatformUx::guideWidget();
        $posClasses = \App\Helpers\PlatformUx::guideWidgetPositionClasses();
    } catch (\Throwable $e) {
        // ط§ط² ظ…ظ‚ط§ط¯غŒط± ظ¾غŒط´â€Œظپط±ط¶ ط¨ط§ظ„ط§ ط§ط³طھظپط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯
    }
@endphp
<style>
#guide-widget-panel #guide-widget-search {
    width: 100%; padding: 0.6rem 0.75rem; font-size: 0.95rem; font-family: inherit;
    border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; direction: rtl;
    background: #fff; color: #1e293b; transition: border-color 0.15s, box-shadow 0.15s;
}
#guide-widget-panel #guide-widget-search:focus {
    outline: none; border-color: var(--platform-button-primary-bg, #4f46e5);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}
#guide-widget-panel #guide-widget-search::placeholder { color: #94a3b8; }
#guide-widget-results .guide-loading { color: #64748b; font-size: 0.9rem; padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem; }
#guide-widget-results .guide-loading::before { content: ''; width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top-color: var(--platform-button-primary-bg, #4f46e5); border-radius: 50%; animation: guide-spin 0.6s linear infinite; }
@keyframes guide-spin { to { transform: rotate(360deg); } }
#guide-widget-results ul { list-style: none; padding: 0; margin: 0; }
#guide-widget-results ul li { margin-bottom: 0.5rem; }
#guide-widget-results ul li a {
    display: block; padding: 0.65rem 0.85rem; border-radius: 6px; text-decoration: none;
    border: 1px solid #e2e8f0; background: #fff; color: #1e293b; text-align: right; font-size: 0.9rem;
    transition: background 0.15s, border-color 0.15s;
}
#guide-widget-results ul li a:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--platform-link-color, #4f46e5); }
#guide-widget-results .guide-empty, #guide-widget-results .guide-error { font-size: 0.9rem; padding: 0.5rem 0; color: #64748b; }
#guide-widget-results .guide-error { color: #dc2626; }
</style>
<div id="guide-widget" class="fixed {{ $posClasses }}" style="font-family: inherit; z-index: 9997;">
    <button type="button" id="guide-widget-toggle" aria-label="ط±ط§ظ‡ظ†ظ…ط§" class="flex items-center justify-center w-14 h-14 rounded-full shadow-lg transition hover:scale-105" style="background: {{ $gw['guide_widget_btn_bg'] ?? '#2563eb' }}; color: {{ $gw['guide_widget_btn_text'] ?? '#ffffff' }};">
        <x-core-ui::icon name="help" class="h-6 w-6" />
    </button>
    <div id="guide-widget-backdrop" class="hidden fixed inset-0" style="z-index: 9998; background: rgba(0,0,0,0.4);" aria-hidden="true"></div>
    <div id="guide-widget-panel" class="hidden fixed top-0 right-0 h-full w-full sm:w-96 border-l border-gray-200 shadow-2xl flex flex-col" style="z-index: 9999; background: {{ $gw['guide_widget_panel_bg'] ?? '#ffffff' }};">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 shrink-0" style="background: {{ $gw['guide_widget_panel_header_bg'] ?? '#f9fafb' }}; color: {{ $gw['guide_widget_panel_text'] ?? '#111827' }};">
            <h2 class="text-lg font-bold">ط±ط§ظ‡ظ†ظ…ط§غŒ ط§ط³طھظپط§ط¯ظ‡</h2>
            <button type="button" id="guide-widget-close" aria-label="ط¨ط³طھظ†" class="p-2 rounded hover:opacity-80" style="color: inherit;">âœ•</button>
        </div>
        <div class="p-4 border-b border-gray-100 shrink-0">
            <input type="text" id="guide-widget-search" placeholder="ط¬ط³طھط¬ظˆ ط¯ط± ط±ط§ظ‡ظ†ظ…ط§..." dir="rtl" autocomplete="off">
        </div>
        <div id="guide-widget-results" class="flex-1 min-h-0 overflow-y-auto p-4" style="color: {{ $gw['guide_widget_panel_text'] ?? '#111827' }}; background: {{ $gw['guide_widget_panel_bg'] ?? '#ffffff' }};">
            <p class="guide-empty">ط¹ط¨ط§ط±طھغŒ طھط§غŒظ¾ ع©ظ†غŒط¯ غŒط§ ظ„غŒط³طھ ط²غŒط± ط±ط§ ط¨ط¨غŒظ†غŒط¯.</p>
        </div>
    </div>
</div>
<script>
(function() {
    var searchUrl = @json(route('guide.search'));
    var guideContext = @json($guideContext);
    var toggle = document.getElementById('guide-widget-toggle');
    var panel = document.getElementById('guide-widget-panel');
    var closeBtn = document.getElementById('guide-widget-close');
    var backdrop = document.getElementById('guide-widget-backdrop');
    var searchInput = document.getElementById('guide-widget-search');
    var resultsEl = document.getElementById('guide-widget-results');
    var debounce = null;

    function openPanel() {
        panel.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (searchInput.value.trim() === '') fetchResults('');
        searchInput.focus();
    }
    function closePanel() {
        panel.classList.add('hidden');
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function render(pages) {
        if (!pages || pages.length === 0) {
            resultsEl.innerHTML = '<p class="guide-empty">ظ†طھغŒط¬ظ‡â€Œط§غŒ غŒط§ظپطھ ظ†ط´ط¯.</p>';
            return;
        }
        resultsEl.innerHTML = '<ul>' + pages.map(function(p) {
            return '<li><a href="' + p.url + '" target="_blank" rel="noopener">' + (p.title || p.slug) + '</a></li>';
        }).join('') + '</ul>';
    }

    function setLoading(loading) {
        if (loading) {
            resultsEl.innerHTML = '<p class="guide-loading">ط¯ط± ط­ط§ظ„ ط¬ط³طھط¬ظˆ...</p>';
        }
    }

    function fetchResults(q) {
        var url = searchUrl + (q ? '?q=' + encodeURIComponent(q) : '');
        if (guideContext) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'context=' + encodeURIComponent(guideContext);
        setLoading(true);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) { render(data.pages || []); })
            .catch(function() { resultsEl.innerHTML = '<p class="guide-error">ط®ط·ط§ ط¯ط± ط¨ط§ط±ع¯ط°ط§ط±غŒ. ط¯ظˆط¨ط§ط±ظ‡ طھظ„ط§ط´ ع©ظ†غŒط¯.</p>'; });
    }

    toggle.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);
    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        debounce = setTimeout(function() { fetchResults(q); }, 300);
    });
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePanel();
    });
})();
</script>


