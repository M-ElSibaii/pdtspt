{{-- Unit-driven auto-fill for dictionary properties (create / preview / new-version editors).
     The user picks a UNIT only (searchable). Physical quantity and dimension are READ-ONLY and
     auto-derived from the ISO 23387 reference tables (unit -> physical_quantity -> dimension):
       - a unit  -> physicalQuantity = "<name>" (clean; "| language" is added only on export),
                    dimension = canonical (blank until physics is sourced)
       - no unit -> physicalQuantity = "without", dimension = ""
     Scoped to the dictionary attribute block so it never touches context. Include once per editor. --}}
<script>
    (function () {
        // unit code => { pq: "physical quantity" (clean), dim: "canonical or ''" }
        const MAP = @json(\App\Services\UnitsReference::autofillMap());
        const WITHOUT = @json(\App\Services\UnitsReference::WITHOUT);

        function scopeOf(el) { return el.closest('.js-def-attrs, .js-newprop'); }

        function fill(unitEl) {
            const scope = scopeOf(unitEl);
            if (!scope) return;
            const pqEl  = scope.querySelector('.js-auto-pq,  .js-attr[data-field="physicalQuantity"]');
            const dimEl = scope.querySelector('.js-auto-dim, .js-attr[data-field="dimension"]');
            const code = (unitEl.value || '').trim();
            const ref = code === '' ? null : (MAP[code] || null);

            // Unknown code (typed, not in the dictionary) => treat as no unit for the derived
            // fields; the unit value itself is still flagged/reconciled server-side.
            const pq  = code === '' ? WITHOUT : (ref ? ref.pq  : WITHOUT);
            const dim = ref ? ref.dim : '';

            if (pqEl)  { pqEl.value  = pq;  pqEl.dispatchEvent(new Event('change', { bubbles: true })); }
            if (dimEl) { dimEl.value = dim; dimEl.dispatchEvent(new Event('change', { bubbles: true })); }
        }

        function isUnit(t) {
            return t && t.classList && t.classList.contains('js-attr') && t.dataset.field === 'units';
        }

        // Keep derived fields hard read-only even if some editor renders them editable.
        function lockDerived(root) {
            (root || document).querySelectorAll('.js-attr[data-field="physicalQuantity"], .js-attr[data-field="dimension"]')
                .forEach(el => { el.readOnly = true; el.classList.add('bg-gray-100', 'text-gray-600'); });
        }

        // Add an explicit "No unit" affordance next to each unit field (clears -> "without").
        function addNoUnitButtons(root) {
            (root || document).querySelectorAll('.js-attr[data-field="units"]').forEach(unitEl => {
                if (unitEl.dataset.noUnitWired) return;
                unitEl.dataset.noUnitWired = '1';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = 'No unit';
                btn.className = 'btn btn-secondary text-xs ml-1 js-no-unit';
                btn.title = 'Clear the unit (physical quantity becomes "without")';
                btn.addEventListener('click', () => {
                    unitEl.value = '';
                    fill(unitEl);
                    unitEl.dispatchEvent(new Event('change', { bubbles: true }));
                });
                unitEl.insertAdjacentElement('afterend', btn);
            });
        }

        document.addEventListener('change', e => { if (isUnit(e.target)) fill(e.target); });
        document.addEventListener('input',  e => { if (isUnit(e.target)) fill(e.target); });
        document.addEventListener('DOMContentLoaded', () => { lockDerived(); addNoUnitButtons(); });
        // Re-wire dynamically-added property rows (e.g. "add property").
        document.addEventListener('click', () => setTimeout(() => { lockDerived(); addNoUnitButtons(); }, 50));
    })();
</script>
