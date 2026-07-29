<x-app-layout>
    <div style="background-color: white;">
        <div class="container py-9" id="api-tester">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="mb-0">API tester</h1>
                <a href="{{ route('admin') }}" class="btn btn-secondary ml-auto">← Back to admin</a>
            </div>
            <p class="text-sm text-gray-600 mt-1">
                Exercise every endpoint. By default this calls <strong>this site's own origin</strong>, so it behaves
                the same on localhost and once deployed to pdts.pt. You may point the Base URL at another deployment
                (e.g. <code>https://pdts.pt</code>) — but only <code>/api/*</code> endpoints allow cross-origin (CORS)
                calls; the reference pages (<code>/unit</code>, <code>/quantitykind</code>, <code>/dimension</code>)
                and the POST export/download routes only work against <em>this</em> site.
            </p>

            <div class="at-box">
                <label class="at-lbl">API base URL</label>
                <div class="flex gap-2 items-center flex-wrap">
                    <input id="baseUrl" class="at-in" style="min-width:340px;flex:1" />
                    <button id="resetBase" class="at-btn">Reset to this site</button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Requests go to <code><span id="baseEcho"></span>/…</code></p>
            </div>

            <div class="at-box">
                <div class="at-lbl mb-2">Parameters</div>
                <div class="at-grid">
                    <div><label>PDT ID</label><input id="p_pdtId" value="1" /></div>
                    <div><label>Data dictionary Id</label><input id="p_dictId" value="1" /></div>
                    <div><label>Group of properties Id</label><input id="p_gopId" value="1" /></div>
                    <div><label>Reference document GUID</label><input id="p_refGuid" value="00324bcf88be48c3845a83050460d72b" /></div>
                    <div><label>Unit code</label><input id="p_unit" value="mm" /></div>
                    <div><label>Quantity kind name</label><input id="p_qk" value="millimetre" /></div>
                    <div><label>Dimension canonical</label><input id="p_dim" value="1" /></div>
                </div>
            </div>

            <div class="at-toolbar">
                <button class="at-btn at-primary" id="runAll">Run all</button>
                <button class="at-btn" id="clearAll">Clear results</button>
                <span class="at-meta" id="summary"></span>
            </div>

            <div id="endpoints"></div>
        </div>
    </div>

    <style>
        #api-tester .at-box { background:#fff; border:1px solid #d0d7de; border-radius:8px; padding:14px 16px; margin:16px 0; }
        #api-tester .at-lbl { font-size:13px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; }
        #api-tester .at-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px 16px; }
        #api-tester .at-grid label { display:block; font-size:12px; font-weight:600; margin-bottom:3px; }
        #api-tester .at-grid input, #api-tester .at-in { width:100%; padding:6px 8px; border:1px solid #d0d7de; border-radius:5px; font-size:13px; }
        #api-tester .at-toolbar { display:flex; gap:10px; align-items:center; margin-bottom:14px; flex-wrap:wrap; }
        #api-tester .at-btn { padding:7px 14px; font-size:13px; border:1px solid #d0d7de; border-radius:6px; background:#fff; cursor:pointer; }
        #api-tester .at-btn:hover { background:#f3f4f6; }
        #api-tester .at-primary { background:#0b3d5c; color:#fff; border-color:#0b3d5c; }
        #api-tester .at-btn:disabled { opacity:.5; cursor:default; }
        #api-tester .group-title { font-size:13px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; margin:22px 0 8px; }
        #api-tester .ep { background:#fff; border:1px solid #d0d7de; border-radius:8px; margin-bottom:8px; overflow:hidden; }
        #api-tester .ep-row { display:flex; align-items:center; gap:12px; padding:10px 14px; }
        #api-tester .method { font-size:11px; font-weight:700; padding:2px 7px; border-radius:4px; min-width:46px; text-align:center; }
        #api-tester .m-GET { background:#e6f0ff; color:#0b58c6; }
        #api-tester .m-POST { background:#fff3e0; color:#b26a00; }
        #api-tester .path { font-family:ui-monospace,Consolas,monospace; font-size:13px; word-break:break-all; flex:1; }
        #api-tester .pill { font-size:12px; font-weight:600; padding:2px 9px; border-radius:20px; white-space:nowrap; }
        #api-tester .pill.ok { background:#e6f4ea; color:#137333; }
        #api-tester .pill.err { background:#fce8e6; color:#b3261e; }
        #api-tester .pill.pending { background:#f0f0f0; color:#57606a; }
        #api-tester .at-meta { font-size:12px; color:#57606a; white-space:nowrap; }
        #api-tester .ep pre { margin:0; border-top:1px solid #d0d7de; background:#0d1117; color:#c9d1d9; padding:12px 14px;
                              font-size:12px; max-height:340px; overflow:auto; white-space:pre-wrap; word-break:break-word; display:none; }
        #api-tester .ep pre.show { display:block; }
        #api-tester .linkbtn { font-size:12px; background:none; border:none; color:#0b58c6; cursor:pointer; padding:0; text-decoration:underline; }
    </style>

    <script>
        (function () {
            const root = document.getElementById("api-tester");
            const $ = (id) => document.getElementById(id);
            const enc = encodeURIComponent;
            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
                ? document.querySelector('meta[name="csrf-token"]').getAttribute("content") : null;

            // Same-origin app root, derived from where THIS page is served — correct on
            // localhost (…/pdtspt/public/admin/api-tester) and live (https://pdts.pt/admin/api-tester).
            function detectBase() {
                const suffix = "/admin/api-tester";
                const p = location.pathname;
                const rootPath = p.endsWith(suffix) ? p.slice(0, -suffix.length) : p.replace(/\/[^\/]*\/?$/, "");
                return (location.origin + rootPath).replace(/\/$/, "");
            }
            const SELF_BASE = detectBase();
            const baseInput = $("baseUrl");
            baseInput.value = SELF_BASE;
            const BASE = () => (baseInput.value || "").trim().replace(/\/$/, "");
            const sameOrigin = () => BASE().indexOf(SELF_BASE) === 0 || BASE().startsWith("/");

            const P = {
                pdtId:  () => ($("p_pdtId").value || "").trim(),
                dictId: () => ($("p_dictId").value || "").trim(),
                gopId:  () => ($("p_gopId").value || "").trim(),
                refGuid:() => ($("p_refGuid").value || "").trim(),
                unit:   () => ($("p_unit").value || "").trim(),
                qk:     () => ($("p_qk").value || "").trim(),
                dim:    () => ($("p_dim").value || "").trim(),
            };

            const CATALOG = [
                ["Collections", [
                    ["GET", "All PDTs",             () => `/api/productDataTemplates`],
                    ["GET", "Construction objects", () => `/api/constructionObjects`],
                    ["GET", "Data dictionary",      () => `/api/dataDictionary`],
                    ["GET", "Reference documents",  () => `/api/referenceDocuments`],
                    ["GET", "Groups of properties", () => `/api/groupsOfProperties`],
                    ["GET", "Units",                () => `/api/units`],
                    ["GET", "Quantity kinds",       () => `/api/quantityKinds`],
                    ["GET", "Dimensions",           () => `/api/dimensions`],
                ]],
                ["Single item", [
                    ["GET", "Full PDT",              () => `/api/${enc(P.pdtId())}`],
                    ["GET", "PDT as ISO 23387 JSON", () => `/api/${enc(P.pdtId())}/json`],
                    ["GET", "PDT as ISO 23387 XML",  () => `/api/${enc(P.pdtId())}/xml`],
                    ["GET", "Dictionary property",   () => `/api/dataDictionary/${enc(P.dictId())}`],
                    ["GET", "Reference document",    () => `/api/referenceDocuments/${enc(P.refGuid())}`],
                    ["GET", "Group of properties",   () => `/api/groupsOfProperties/${enc(P.gopId())}`],
                ]],
                ["Reference layer (dereferenceable, JSON) — same-origin only", [
                    ["GET", "Unit",          () => `/unit/${enc(P.unit())}?format=json`],
                    ["GET", "Quantity kind", () => `/quantitykind/${enc(P.qk())}?format=json`],
                    ["GET", "Dimension",     () => `/dimension/${enc(P.dim())}?format=json`],
                ]],
                ["Exports (POST · download) — same-origin only", [
                    ["POST", "Download PDT JSON", () => `/pdt-export/json/${enc(P.pdtId())}`],
                    ["POST", "Download PDT XML",  () => `/pdt-export/xml/${enc(P.pdtId())}`],
                ]],
            ];

            const rows = [];
            const container = $("endpoints");
            CATALOG.forEach(([groupName, eps]) => {
                const gt = document.createElement("div");
                gt.className = "group-title";
                gt.textContent = groupName;
                container.appendChild(gt);

                eps.forEach(([method, label, build]) => {
                    const ep = document.createElement("div");
                    ep.className = "ep";
                    ep.innerHTML = `
                        <div class="ep-row">
                            <span class="method m-${method}">${method}</span>
                            <span class="path"></span>
                            <span class="pill pending">idle</span>
                            <span class="at-meta"></span>
                            <button class="linkbtn resp" style="display:none">response</button>
                            <button class="linkbtn dl" style="display:none">download</button>
                            <button class="at-btn run">Test</button>
                        </div>
                        <pre></pre>`;
                    container.appendChild(ep);

                    const rec = {
                        method, label, build, ep,
                        pathEl: ep.querySelector(".path"),
                        pill:   ep.querySelector(".pill"),
                        metaEl: ep.querySelector(".at-meta"),
                        respBtn:ep.querySelector(".resp"),
                        dlBtn:  ep.querySelector(".dl"),
                        preEl:  ep.querySelector("pre"),
                        runBtn: ep.querySelector(".run"),
                    };
                    const refresh = () => (rec.pathEl.textContent = BASE() + build());
                    refresh();
                    rec.refresh = refresh;
                    rec.runBtn.addEventListener("click", () => runOne(rec));
                    rec.respBtn.addEventListener("click", () => rec.preEl.classList.toggle("show"));
                    rec.dlBtn.addEventListener("click", () => download(rec));
                    rows.push(rec);
                });
            });

            function refreshAll() {
                $("baseEcho").textContent = BASE();
                rows.forEach((r) => r.refresh());
            }
            root.querySelectorAll(".at-grid input").forEach((inp) => inp.addEventListener("input", refreshAll));
            baseInput.addEventListener("input", refreshAll);
            $("resetBase").addEventListener("click", () => { baseInput.value = SELF_BASE; refreshAll(); });
            refreshAll();

            // Build a sensible filename from the endpoint path + content-type extension.
            function fileNameFor(rec, ctype) {
                let ext = "txt";
                if (ctype.includes("json")) ext = "json";
                else if (ctype.includes("xml")) ext = "xml";
                const base = rec.build()
                    .replace(/\?.*$/, "")            // drop query string
                    .replace(/^\/+/, "")             // leading slash
                    .replace(/[^\w.-]+/g, "_");       // safe filename
                return (base || "response") + "." + ext;
            }

            // Download the last response body for this endpoint.
            function download(rec) {
                if (rec.lastBody == null) return;
                const blob = new Blob([rec.lastBody], { type: rec.lastCtype || "text/plain" });
                const a = document.createElement("a");
                a.href = URL.createObjectURL(blob);
                a.download = rec.lastFile || "response.txt";
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(a.href), 1000);
            }

            async function runOne(rec) {
                const url = BASE() + rec.build();
                rec.pathEl.textContent = url;
                rec.pill.className = "pill pending";
                rec.pill.textContent = "…";
                rec.metaEl.textContent = "";
                rec.runBtn.disabled = true;
                const t0 = performance.now();

                const opts = { method: rec.method, headers: { Accept: "application/json" } };
                if (rec.method === "POST") {
                    if (!sameOrigin()) {
                        rec.pill.className = "pill err";
                        rec.pill.textContent = "same-origin only";
                        rec.metaEl.textContent = "POST exports can't be called cross-origin.";
                        rec.runBtn.disabled = false;
                        return false;
                    }
                    if (csrf) opts.headers["X-CSRF-TOKEN"] = csrf;
                }

                try {
                    const r = await fetch(url, opts);
                    const ms = Math.round(performance.now() - t0);
                    const text = await r.text();
                    const ctype = r.headers.get("content-type") || "";

                    rec.pill.className = "pill " + (r.ok ? "ok" : "err");
                    rec.pill.textContent = r.status + " " + r.statusText;
                    rec.metaEl.textContent = `${text.length.toLocaleString()} bytes · ${ms} ms · ${ctype.split(";")[0]}`;

                    let body = text;
                    if (ctype.includes("application/json")) {
                        try { body = JSON.stringify(JSON.parse(text), null, 2); } catch (e) {}
                    }
                    rec.preEl.textContent = body;   // full response, never truncated
                    rec.respBtn.style.display = "";
                    rec.lastBody = body; rec.lastCtype = ctype; rec.lastFile = fileNameFor(rec, ctype);
                    rec.dlBtn.style.display = "";
                    return r.ok;
                } catch (e) {
                    rec.pill.className = "pill err";
                    rec.pill.textContent = "network / CORS error";
                    rec.metaEl.textContent = sameOrigin() ? "" : "cross-origin request blocked (see note above)";
                    rec.preEl.textContent = String(e);
                    rec.respBtn.style.display = "";
                    rec.dlBtn.style.display = "none";
                    return false;
                } finally {
                    rec.runBtn.disabled = false;
                }
            }

            $("runAll").addEventListener("click", async () => {
                $("runAll").disabled = true;
                let ok = 0, fail = 0;
                for (const rec of rows) {
                    const good = await runOne(rec);
                    good ? ok++ : fail++;
                    $("summary").textContent = `${ok} passed · ${fail} failed · ${rows.length - ok - fail} left`;
                }
                $("summary").textContent = `Done — ${ok} passed · ${fail} failed of ${rows.length}`;
                $("runAll").disabled = false;
            });

            $("clearAll").addEventListener("click", () => {
                rows.forEach((r) => {
                    r.pill.className = "pill pending"; r.pill.textContent = "idle";
                    r.metaEl.textContent = ""; r.preEl.textContent = ""; r.preEl.classList.remove("show");
                    r.respBtn.style.display = "none";
                    r.dlBtn.style.display = "none"; r.lastBody = null;
                });
                $("summary").textContent = "";
            });
        })();
    </script>
</x-app-layout>
