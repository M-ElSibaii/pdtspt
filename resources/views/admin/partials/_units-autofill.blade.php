{{-- When a unit is chosen on a dictionary property, auto-fill PhysicalQuantity to
     "{written name} | en.EN" (blank unit => "without"). Include once per editor that has a
     dictionary form. Scoped to the dictionary attribute block so it never touches context. --}}
<script>
    (function () {
        const MAP = @json(\App\Services\BsddEnums::unitsMap());
        function fill(unitEl) {
            const scope = unitEl.closest('.js-def-attrs, .js-newprop');
            if (!scope) return;
            const pq = scope.querySelector('.js-attr[data-field="physicalQuantity"]');
            if (!pq || pq.disabled) return;
            const code = (unitEl.value || '').trim();
            pq.value = code === '' ? 'without' : ((MAP[code] || code) + ' | en.EN');
        }
        function isUnit(t) { return t && t.classList && t.classList.contains('js-attr') && t.dataset.field === 'units'; }
        document.addEventListener('change', e => { if (isUnit(e.target)) fill(e.target); });
        document.addEventListener('input', e => { if (isUnit(e.target)) fill(e.target); });
    })();
</script>
