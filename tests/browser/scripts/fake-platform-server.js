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

createServer((request, response) => {
    const url = new URL(request.url, `http://127.0.0.1:${port}`);
    if (request.method === 'GET' && url.pathname === '/health') return json(response, 200, { ok: true });
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
