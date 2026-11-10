export function customerPicker(endpoint, workshopId, selected) {
    return {
        selected,
        query: '',
        options: [],
        nextPage: null,
        loading: false,
        error: '',
        controller: null,
        sequence: 0,
        init() { this.search(); },
        search() { return this.load(1, false); },
        async load(page, append) {
            this.controller?.abort();
            const sequence = ++this.sequence;
            this.controller = new AbortController();
            this.loading = true;
            this.error = '';
            if (!append) { this.options = []; this.nextPage = null; }
            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', this.query);
                url.searchParams.set('page', page);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' }, credentials: 'same-origin',
                    cache: 'no-store', signal: this.controller.signal,
                });
                if (!response.ok) { throw new Error('request'); }
                const data = await response.json();
                if (sequence !== this.sequence) { return; }
                if (data.workshop_id !== workshopId) {
                    this.options = [];
                    this.nextPage = null;
                    this.error = 'Bengkel aktif berubah. Buka ulang form kendaraan.';
                    return;
                }
                this.options = append ? [...this.options, ...data.customers] : data.customers;
                this.nextPage = data.next_page;
            } catch (error) {
                if (sequence === this.sequence && error.name !== 'AbortError') {
                    this.error = 'Pilihan pelanggan belum dapat dimuat. Coba lagi.';
                }
            } finally {
                if (sequence === this.sequence) { this.loading = false; }
            }
        },
        destroy() { this.controller?.abort(); },
    };
}
