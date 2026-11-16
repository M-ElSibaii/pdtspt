/**
 * Property origin filter: hides groups and properties by where they come from
 * (this template, an IsSubtypeOf ancestor, the master template). Display only: exports,
 * downloads and the API are never filtered.
 *
 * Markup contract (rendered by <x-origin-filter> and the pages):
 *   - every element carrying data-origin="<key>" belongs to one origin;
 *   - every element carrying data-origins="<key> <key>…" (a heading that merges groups
 *     of several origins) is shown while at least one of its origins is checked.
 * Both live inside the element that holds x-data="originFilter(...)".
 *
 * The choice is kept in the URL as ?origins=own,master,<guid>; with everything checked the
 * parameter is dropped, with nothing checked it is ?origins= (empty).
 */
export default function originFilter(config) {
    return {
        origins: config.origins,          // [{key, count}]
        total: config.total,
        shownTemplate: config.shownTemplate,
        selected: [],

        init() {
            const all = this.origins.map(o => o.key);
            const params = new URLSearchParams(window.location.search);
            if (params.has('origins')) {
                const wanted = params.get('origins').split(',').map(s => s.trim()).filter(Boolean);
                this.selected = all.filter(k => wanted.includes(k));
            } else {
                this.selected = all.slice();
            }
            this.$watch('selected', () => { this.apply(); this.remember(); });
            this.apply();
        },

        get visibleCount() {
            return this.origins.filter(o => this.selected.includes(o.key)).reduce((n, o) => n + o.count, 0);
        },

        get noneSelected() {
            return this.selected.length === 0;
        },

        get allSelected() {
            return this.selected.length === this.origins.length;
        },

        showAll() {
            this.selected = this.origins.map(o => o.key);
        },

        apply() {
            const on = new Set(this.selected);
            this.$root.querySelectorAll('[data-origin]').forEach(el => {
                el.classList.toggle('origin-off', !on.has(el.dataset.origin));
            });
            this.$root.querySelectorAll('[data-origins]').forEach(el => {
                const keys = el.dataset.origins.split(' ').filter(Boolean);
                el.classList.toggle('origin-off', !keys.some(k => on.has(k)));
            });
        },

        remember() {
            const url = new URL(window.location.href);
            if (this.allSelected) {
                url.searchParams.delete('origins');
            } else {
                // Keep the commas readable in a shared link.
                url.searchParams.set('origins', '__ORIGINS__');
                url.search = url.search.replace('__ORIGINS__', this.selected.join(','));
            }
            window.history.replaceState(window.history.state, '', url.toString());
        },
    };
}
