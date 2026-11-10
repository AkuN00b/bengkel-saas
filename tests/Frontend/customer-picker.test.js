import assert from 'node:assert/strict';
import { test } from 'node:test';
import { customerPicker } from '../../resources/js/customer-picker.js';

test('a workshop change never replaces options with another workshops customers', async (context) => {
    context.mock.method(globalThis, 'fetch', async () => ({ ok: true, json: async () => ({
        workshop_id: 2, customers: [{ id: 99, name: 'Other workshop' }], next_page: null,
    }) }));
    globalThis.window = { location: { origin: 'http://localhost' } };
    try {
        const picker = customerPicker('/vehicles/customer-options', 1, null);
        await picker.search();
        assert.deepEqual(picker.options, []);
        assert.match(picker.error, /Bengkel aktif berubah/);
    } finally { delete globalThis.window; }
});

test('slow earlier searches cannot overwrite newer results', async (context) => {
    const requests = [];
    context.mock.method(globalThis, 'fetch', (url, options) => new Promise((resolve) => {
        requests.push({ url, options, resolve });
    }));
    globalThis.window = { location: { origin: 'http://localhost' } };
    try {
        const picker = customerPicker('/vehicles/customer-options', 1, null);
        picker.query = 'old'; const first = picker.search();
        picker.query = '%_new'; const second = picker.search();
        assert.equal(requests[0].options.signal.aborted, true);
        assert.equal(requests[1].url.searchParams.get('q'), '%_new');
        requests[1].resolve({ ok: true, json: async () => ({workshop_id: 1, customers: [{id: 2, name: 'New'}], next_page: null}) });
        await second;
        requests[0].resolve({ ok: true, json: async () => ({workshop_id: 1, customers: [{id: 1, name: 'Old'}], next_page: null}) });
        await first;
        assert.deepEqual(picker.options, [{id: 2, name: 'New'}]);
        assert.equal(picker.loading, false);
        assert.equal(picker.error, '');
        picker.destroy();
    } finally { delete globalThis.window; }
});

test('failed search clears old suggestions and allows retry without losing the selection', async (context) => {
    globalThis.window = { location: { origin: 'http://localhost' } };
    context.mock.method(globalThis, 'fetch', async () => ({ok: false}));
    try {
        const selected = {id: 3, name: 'Selected'};
        const picker = customerPicker('/vehicles/customer-options', 1, selected);
        picker.options = [{id: 1, name: 'Old'}];
        await picker.search();
        assert.deepEqual(picker.options, []);
        assert.equal(picker.selected, selected);
        assert.match(picker.error, /Coba lagi/);
        assert.equal(picker.loading, false);
    } finally { delete globalThis.window; }
});
