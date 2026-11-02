{{-- Shared look for the admin pages (hub, drafts, editor, review, status check).
     Plain scoped CSS rather than Tailwind utilities so the pages do not depend on an asset
     rebuild, and colour/spacing stay identical across them. Included once per page. --}}
<style>
    .adm { --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --soft:#f8fafc; --brand:#334155; --brand-dk:#0f172a;
           --ok:#15803d; --ok-bg:#dcfce7; --warn:#b45309; --warn-bg:#fef3c7; --bad:#b91c1c; --bad-bg:#fee2e2;
           --info:#1d4ed8; --info-bg:#dbeafe; color:var(--ink); font-size:14px; }
    .adm-page { max-width:1320px; margin:0 auto; padding:28px 20px 60px; }
    .adm h1 { font-size:24px; font-weight:700; margin:0; line-height:1.25; }
    .adm h2 { font-size:17px; font-weight:700; margin:0; }
    .adm h3 { font-size:15px; font-weight:600; margin:0; }
    .adm-sub { color:var(--muted); margin-top:4px; }
    .adm-head { display:flex; flex-wrap:wrap; align-items:flex-start; gap:12px 20px; margin-bottom:20px; }
    .adm-head > :first-child { flex:1 1 320px; min-width:0; }
    .adm-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }

    .adm-card { background:#fff; border:1px solid var(--line); border-radius:10px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .adm-card + .adm-card { margin-top:16px; }
    .adm-card-h { display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; padding:14px 16px; border-bottom:1px solid var(--line); }
    .adm-card-b { padding:16px; }
    .adm-card-h .adm-sub { margin:0; }

    /* Scoped under .adm: the site stylesheet has a:link { background-color:transparent }, which outranks one class. */
    .adm .adm-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:7px; border:1px solid var(--brand);
               background:var(--brand); color:#fff !important; font-weight:600; font-size:13px; line-height:1.3; cursor:pointer;
               text-decoration:none !important; white-space:nowrap; transition:background .12s; }
    .adm-btn:hover { background:var(--brand-dk); }
    .adm-btn:disabled { opacity:.5; cursor:not-allowed; }
    .adm-btn.is-ghost { background:#fff; color:var(--brand) !important; border-color:#cbd5e1; }
    .adm-btn.is-ghost:hover { background:var(--soft); }
    .adm-btn.is-danger { background:#fff; color:var(--bad) !important; border-color:#fecaca; }
    .adm-btn.is-danger:hover { background:var(--bad-bg); }
    .adm-btn.is-go { background:var(--ok); border-color:var(--ok); }
    .adm-btn.is-go:hover { background:#166534; }
    .adm-btn.is-sm { padding:4px 10px; font-size:12px; }
    .adm .adm-link { color:var(--info); text-decoration:underline; cursor:pointer; background:none; border:0; padding:0; font:inherit; }

    .adm .adm-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; }
    .adm-pill.is-active { background:var(--ok-bg); color:var(--ok); }
    .adm-pill.is-preview, .adm-pill.is-draft { background:var(--warn-bg); color:var(--warn); }
    .adm-pill.is-inactive { background:#f1f5f9; color:var(--muted); }
    .adm-pill.is-added { background:var(--ok-bg); color:var(--ok); }
    .adm-pill.is-modified, .adm-pill.is-changed { background:var(--info-bg); color:var(--info); }
    .adm-pill.is-removed { background:var(--bad-bg); color:var(--bad); }
    .adm-pill.is-neutral { background:#f1f5f9; color:#334155; }
    .adm-ver { font-variant-numeric:tabular-nums; font-weight:600; color:#334155; }

    .adm-banner { display:flex; gap:12px; align-items:flex-start; padding:12px 14px; border-radius:9px; border:1px solid; margin-bottom:16px; }
    .adm-banner.is-info { background:#eff6ff; border-color:#bfdbfe; color:#1e3a8a; }
    .adm-banner.is-warn { background:#fffbeb; border-color:#fde68a; color:#78350f; }
    .adm-banner.is-ok { background:#f0fdf4; border-color:#bbf7d0; color:#14532d; }
    .adm-banner.is-bad { background:#fef2f2; border-color:#fecaca; color:#7f1d1d; }
    .adm-banner b { font-weight:700; }

    .adm-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:20px; }
    .adm .adm-stat { background:#fff; border:1px solid var(--line); border-radius:10px; padding:14px 16px; text-decoration:none !important; color:inherit !important; display:block; }
    a.adm-stat:hover { border-color:#94a3b8; }
    .adm-stat .n { font-size:26px; font-weight:700; line-height:1.1; font-variant-numeric:tabular-nums; }
    .adm-stat .l { color:var(--muted); font-size:13px; margin-top:2px; }
    .adm-stat.is-warn .n { color:var(--warn); }
    .adm-stat.is-ok .n { color:var(--ok); }

    .adm-table { width:100%; border-collapse:collapse; }
    .adm-table th { text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:600;
                    padding:8px 12px; border-bottom:1px solid var(--line); background:var(--soft); }
    .adm-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .adm-table tr:last-child td { border-bottom:0; }
    .adm-table tr:hover td { background:#fcfdfe; }
    .adm-scroll { overflow-x:auto; }

    .adm-input { width:100%; border:1px solid #cbd5e1; border-radius:7px; padding:7px 10px; font-size:14px; }
    .adm-input:focus { outline:2px solid #93c5fd; outline-offset:0; border-color:#93c5fd; }
    .adm-muted { color:var(--muted); }
    .adm-small { font-size:12px; }
    .adm-mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
    .adm-grid2 { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:16px; }
    .adm-empty { padding:24px; text-align:center; color:var(--muted); }

    .adm-diff-old { background:#fef2f2; color:#991b1b; text-decoration:line-through; text-decoration-color:rgba(153,27,27,.45); padding:1px 4px; border-radius:4px; }
    .adm-diff-new { background:#f0fdf4; color:#166534; padding:1px 4px; border-radius:4px; }

    @media (max-width: 640px) {
        .adm-page { padding:18px 12px 40px; }
        .adm-table th, .adm-table td { padding:8px; }
    }
</style>
