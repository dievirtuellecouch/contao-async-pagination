const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../src/Resources/public/async-pagination.js'), 'utf8');

function fixture({ enabled = true, reduced = false, status = 200, missingHtml = false, replaced = true, transition = false } = {}) {
    const calls = [];
    let finish;
    const finished = new Promise(resolve => { finish = resolve; });
    const target = {
        children: [{}],
        getAttribute: key => ({ 'data-type': 'module', 'data-id': '13', 'data-endpoint': '/preview.php/_dvc/ajax/module/13' })[key],
        scrollIntoView: options => calls.push({ type: 'scroll', ...options }),
    };
    const container = {
        getAttribute: () => enabled ? 'true' : 'false',
        querySelector: selector => selector === '[data-element="target"]' ? target : null,
        addEventListener() {},
    };
    const document = { querySelectorAll: () => [] };
    if (transition) {
        document.startViewTransition = callback => {
            const updateCallbackDone = Promise.resolve().then(callback);
            return { updateCallbackDone, finished };
        };
    }
    const context = vm.createContext({
        document,
        window: { location: { pathname: '/preview.php/blog', search: '' }, matchMedia: () => ({ matches: reduced }) },
        URLSearchParams, URL, Element: class {}, console,
        fetch: async url => { calls.push({ type: 'request', url }); return { status, json: async () => missingHtml ? {} : { html: '<div>Updated list</div>' } }; },
    });
    vm.runInContext(source + '\nglobalThis.instance = new ListReload(globalThis.container);', Object.assign(context, { container }));
    const instance = context.instance;
    instance.replace = () => { calls.push({ type: 'replace' }); return replaced; };
    return { instance, calls, finish };
}

(async () => {
    for (const options of [{}, { enabled: false }, { reduced: true }, { status: 404 }, { missingHtml: true }, { replaced: false }]) {
        const f = fixture(options);
        assert.equal(f.calls.length, 0, 'Initial rendering must not scroll or fetch.');
        await f.instance.load({ page_n13: '2' });
        const scrolls = f.calls.filter(c => c.type === 'scroll');
        const shouldScroll = options.enabled !== false && options.status !== 404 && !options.missingHtml && options.replaced !== false;
        assert.equal(scrolls.length, shouldScroll ? 1 : 0);
        if (shouldScroll) {
            assert.equal(scrolls[0].behavior, options.reduced ? 'instant' : 'smooth');
            assert.equal(scrolls[0].block, 'start');
        }
        assert.equal(f.instance.state.isLoading, false, 'Loading must finish on success and failure.');
        assert.match(f.calls[0].url, /^\/preview\.php\/_dvc\/ajax\/module\/13\?/);
    }

    const f = fixture({ transition: true });
    const loading = f.instance.load({ category: 'physio' });
    await new Promise(resolve => setImmediate(resolve));
    assert(f.calls.some(c => c.type === 'replace'));
    assert(!f.calls.some(c => c.type === 'scroll'), 'Do not scroll while the transition covers the new list.');
    f.finish();
    await loading;
    assert.equal(f.calls.filter(c => c.type === 'scroll').length, 1);

    const skipped = fixture({ transition: true });
    const skippedLoad = skipped.instance.load({ page_n13: '2' });
    skipped.finish();
    await skippedLoad;
    assert.equal(skipped.calls.filter(c => c.type === 'scroll').length, 1);
    console.log('PASS opt-in, initial load, pagination/filter, reduced motion, errors and view-transition timing');
})().catch(error => { console.error(error); process.exitCode = 1; });
