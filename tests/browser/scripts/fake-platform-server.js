import { createServer } from 'node:http';

const port = Number(process.env.BROWSER_PLATFORM_PORT || 8792);
const licensed = new Set();

const themes = [
    { id: 101, name: 'Assigned Browser Theme', slug: 'assigned-browser-theme', description: 'A locally assigned Theme', is_free: true, price_rial: 0, category: 'landing', current_version: '2.0.0', release_status: 'released', quality_score: 96, dependencies: [], dependency_status: 'resolved' },
    { id: 202, name: 'Independent Browser Theme', slug: 'independent-browser-theme', description: 'Isolation control Theme', is_free: true, price_rial: 0, category: 'landing', current_version: '1.0.0', release_status: 'released', quality_score: 94, dependencies: [], dependency_status: 'resolved' },
];

function theme(id) {
    const item = themes.find((candidate) => candidate.id === Number(id));
    return item ? { ...item, has_license: licensed.has(item.id) } : null;
}

function json(response, status, body) {
    response.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8' });
    response.end(JSON.stringify(body));
}

// ADR-079 (saas-platform): a one-asset Motion Graphics runtime manifest whose
// asset declares runtime parameters, and the SVG it points at. The SVG echoes
// the `window_title` query value so a spec can prove an inspector edit
// reached the rendered page.
const motionManifest = {
    available: true,
    schema_version: '1.0',
    site_reference: '1',
    template_reference: 'motion-library:core',
    package_id: '00000000-0000-4000-8000-000000000e2e',
    package_version: 1,
    generated_at: '2026-11-06T00:00:00+00:00',
    manifest_checksum: 'e'.repeat(64),
    assets: [{
        asset_id: 'e2e-hero-motion',
        published_version: 2,
        optimized_reference: '/motion/e2e-hero/v2/animation.svg',
        checksum: 'a'.repeat(64),
        asset_type: 'svg',
        theme_tokens: {},
        placement_rules: { dimensions: { width: 960, height: 540, viewBox: '0 0 960 540' } },
        responsive_rules: {},
        name: 'موشن آزمایشی هیرو',
        category: 'hero-motion',
        parameters: [
            { key: 'window_title', type: 'text', group: 'content', label: 'عنوان داشبورد', default: 'داشبورد هوشمند سازمان', max: 28 },
            { key: 'primary', type: 'color', group: 'style', label: 'رنگ اصلی', default: '#2563eb' },
            { key: 'share', type: 'number', group: 'content', label: 'سهم (درصد)', min: 0, max: 100, step: 1, default: 36, format: 'percent' },
            // ADR-080: colour scheme + a section switch (group `sections`).
            { key: 'scheme', type: 'select', role: 'scheme', group: 'style', label: 'حالت رنگی', options: { auto: 'خودکار (مطابق دستگاه)', light: 'همیشه روشن', dark: 'همیشه تیره' }, default: 'auto' },
            { key: 'show_kpis', type: 'boolean', role: 'show', group: 'sections', label: 'کارت‌های شاخص', default: true },
            { key: 'loop', type: 'boolean', group: 'motion', label: 'حرکت پیوسته (لوپ)', default: true },
            { key: 'speed', type: 'select', group: 'motion', label: 'سرعت انیمیشن', options: { slow: 'آرام', normal: 'عادی', fast: 'تند' }, default: 'normal' },
            { key: 'Bad Key', type: 'text', label: 'dropped by the reader', default: 'x' },
        ],
    }],
};

function motionSvg(title) {
    const safe = String(title).replace(/[<>&"]/g, '');
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 540" role="img"><title>${safe}</title><rect width="960" height="540" fill="#e0e7ff"/><text x="480" y="270" text-anchor="middle" font-size="40">${safe}</text></svg>`;
}

createServer((request, response) => {
    const url = new URL(request.url, `http://127.0.0.1:${port}`);
    if (request.method === 'GET' && url.pathname === '/api/support-tickets/unread-count') {
        response.setHeader('Access-Control-Allow-Origin', `http://127.0.0.1:${Number(process.env.BROWSER_PORT || 8791)}`);
        return json(response, 200, { success: true, count: 0 });
    }
    if (request.method === 'GET' && url.pathname === '/health') return json(response, 200, { ok: true });
    if (request.method === 'GET' && url.pathname === '/api/motion-runtime/manifest') return json(response, 200, motionManifest);
    if (request.method === 'GET' && url.pathname === '/motion/e2e-hero/v2/animation.svg') {
        response.writeHead(200, { 'Content-Type': 'image/svg+xml; charset=utf-8', 'Cross-Origin-Resource-Policy': 'cross-origin' });
        return response.end(motionSvg(url.searchParams.get('window_title') || 'داشبورد هوشمند سازمان'));
    }
    if (request.method === 'GET' && url.pathname === '/api/theme-marketplace') return json(response, 200, { success: true, themes: themes.map((item) => theme(item.id)) });
    if (request.method === 'GET' && url.pathname === '/api/theme-marketplace/my-licenses') {
        return json(response, 200, { success: true, licenses: [...licensed].map((id) => ({ license_key: `BROWSER-THEME-${id}`, purchased_at: '2026-09-21', is_permanent: true, expires_at: null, installed_version: null, is_outdated_by_ledger: null, theme: theme(id) })) });
    }
    const detail = url.pathname.match(/^\/api\/theme-marketplace\/(\d+)$/);
    if (request.method === 'GET' && detail) {
        const item = theme(detail[1]);
        return item ? json(response, 200, { success: true, theme: item }) : json(response, 404, { success: false });
    }
    const purchase = url.pathname.match(/^\/api\/theme-marketplace\/(\d+)\/initiate-purchase$/);
    if (request.method === 'POST' && purchase) {
        const id = Number(purchase[1]);
        if (!theme(id)) return json(response, 404, { success: false, error: 'theme_not_available' });
        if (licensed.has(id)) return json(response, 422, { success: false, error: 'already_purchased' });
        licensed.add(id);
        return json(response, 200, { success: true, free: true, license_key: `BROWSER-THEME-${id}`, release_id: id + 1000 });
    }
    const download = url.pathname.match(/^\/api\/theme-marketplace\/(\d+)\/download$/);
    if (request.method === 'GET' && download) {
        const id = Number(download[1]);
        const item = theme(id);
        if (!item || !licensed.has(id)) return json(response, 403, { success: false, error: 'no_license' });
        return json(response, 200, { success: true, theme_id: id, theme_name: item.name, theme_slug: item.slug, release_version: item.current_version, release_id: id + 1000, header_blocks: [], footer_blocks: [], global_styles: {}, settings: {}, pages: { home: { title: 'Home', slug: '', is_home: true, blocks: [{ id: 'assigned-heading', type: 'heading', props: { text: 'Assigned Browser Theme is active', level: 1 } }] } } });
    }
    return json(response, 404, { success: false, error: 'not_found' });
}).listen(port, '127.0.0.1');
