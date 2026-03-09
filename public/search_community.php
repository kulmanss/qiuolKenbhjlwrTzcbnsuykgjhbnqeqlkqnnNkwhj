<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Buscar Comunidades</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .result-list { display: flex; flex-direction: column; gap: 10px; }
    .result-item { display: flex; gap: 15px; background: #fdfdfd; border: 1px solid var(--line); padding: 15px; border-radius: 4px; align-items: center; transition: 0.2s; }
    .result-item:hover { border-color: #a5bce3; background: #f4f7fc; }

    .result-pic { width: 60px; height: 60px; background: #e4ebf5; border: 1px solid #c0d0e6; overflow: hidden; border-radius: 3px; flex-shrink: 0; display:flex; align-items:center; justify-content:center;}
    .result-pic img { width: 100%; height: 100%; object-fit: cover; }

    .result-info { flex: 1; }
    .result-name { font-size: 14px; font-weight: bold; margin-bottom: 5px; display: inline-block; color: var(--link); text-decoration: none; }
    .result-name:hover { text-decoration: underline; }
    .result-details { color: #666; font-size: 11px; }

    .result-actions { text-align: right; }
    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 6px 15px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }

    .status-badge { display: inline-block; padding: 6px 12px; font-size: 11px; font-weight: bold; background-color: #e4f2e9; border: 1px solid #8bc59e; color: #2a6b2a; border-radius: 20px; }

    .no-results-box { text-align:center; padding:30px; color:#cc0000; font-weight:bold; border:1px dashed var(--line); border-radius:4px; background:#ffe6e6; }

    .pagination-bar {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }
    .page-btn {
        padding: 5px 10px;
        border: 1px solid #c0d0e6;
        border-radius: 3px;
        font-size: 11px;
        background: #fff;
        color: var(--link);
        cursor: pointer;
        text-decoration: none;
        transition: 0.15s;
    }
    .page-btn:hover { background: #e4ebf5; }
    .page-btn.active { background: var(--orkut-blue); color: #fff; border-color: var(--orkut-blue); }

    .search-inline-form { display: flex; gap: 8px; margin-bottom: 20px; position: relative; }
    .search-inline-form input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 3px;
        font-size: 13px;
        outline: none;
    }
    .search-inline-form input:focus { border-color: var(--orkut-blue); box-shadow: 0 0 5px rgba(59,89,152,0.2); }
    .search-inline-form button {
        padding: 8px 18px;
        background: var(--orkut-blue);
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
        font-weight: bold;
    }
    .search-inline-form button:hover { background: #2d4a86; }

    /* Live dropdown */
    .search-live-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 2px;
        background: #fff;
        border: 1px solid var(--orkut-blue);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 320px;
        overflow-y: auto;
        z-index: 9999;
        display: none;
        border-radius: 0 0 4px 4px;
    }
    .search-live-dropdown.show { display: block; }
    .live-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        cursor: pointer;
        transition: 0.15s;
        border-bottom: 1px dotted var(--line);
        text-decoration: none;
        color: inherit;
        font-size: 11px;
    }
    .live-item:last-of-type { border-bottom: none; }
    .live-item:hover { background: #eef4ff; }
    .live-item img {
        width: 35px;
        height: 35px;
        border-radius: 3px;
        object-fit: cover;
        border: 1px solid #ccc;
        flex-shrink: 0;
        background: #e4ebf5;
    }
    .live-item-info { flex: 1; overflow: hidden; }
    .live-item-name { font-weight: bold; font-size: 12px; color: var(--title, #333); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .live-item-name mark { background: #fff3cd; padding: 0 1px; border-radius: 2px; }
    .live-item-meta { font-size: 10px; color: #888; margin-top: 1px; }
    .live-empty { padding: 15px; text-align: center; color: #999; font-size: 11px; }
    .live-footer {
        padding: 8px;
        text-align: center;
        background: #f4f7fc;
        border-top: 1px solid #eee;
        border-radius: 0 0 3px 3px;
    }
    .live-footer a {
        color: var(--link);
        text-decoration: none;
        font-size: 11px;
        font-weight: bold;
    }
    .live-footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> &gt; Buscar Comunidades</div>

        <div class="card">
            <h1 class="orkut-name" style="font-size: 20px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 20px;" id="pageTitle">
                Buscar Comunidades
            </h1>

            <div class="search-inline-form" id="searchForm">
                <input type="text" id="searchInput" placeholder="buscar comunidade..." autocomplete="off" autofocus>
                <button type="button" onclick="doSearch()">🔍 Buscar</button>
                <div class="search-live-dropdown" id="liveDropdown"></div>
            </div>

            <div id="resultsArea">
                <div style="text-align:center; padding:30px; color:#999; font-size:12px;">
                    Digite o nome de uma comunidade para buscar...
                </div>
            </div>

            <div class="pagination-bar" id="pagination"></div>
        </div>
    </div>
</div>
<div id="app-footer"></div>

<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
let _currentPage = 1;
let _currentQuery = '';
let _liveTimeout = null;

document.addEventListener('DOMContentLoaded', async function() {
    await loadLayout({ activePage: 'comunidades' });

    const urlParams = new URLSearchParams(window.location.search);
    const q = urlParams.get('q') || '';
    if (q) {
        document.getElementById('searchInput').value = q;
        _currentQuery = q;
        loadResults(1);
    }

    setupLiveSearch();
});

function setupLiveSearch() {
    const input = document.getElementById('searchInput');
    const dropdown = document.getElementById('liveDropdown');

    input.addEventListener('input', function() {
        const q = input.value.trim();
        clearTimeout(_liveTimeout);

        if (q.length < 1) {
            dropdown.classList.remove('show');
            return;
        }

        _liveTimeout = setTimeout(() => {
            fetchLiveResults(q);
        }, 200);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            dropdown.classList.remove('show');
            doSearch();
        }
        if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-inline-form')) {
            dropdown.classList.remove('show');
        }
    });

    input.addEventListener('focus', function() {
        if (input.value.trim().length >= 1 && dropdown.children.length > 0) {
            dropdown.classList.add('show');
        }
    });
}

async function fetchLiveResults(q) {
    const dropdown = document.getElementById('liveDropdown');
    try {
        const resp = await fetch('/api/buscar-comunidades?q=' + encodeURIComponent(q));
        const data = await resp.json();

        if (!data.success || !data.comunidades || data.comunidades.length === 0) {
            dropdown.innerHTML = '<div class="live-empty">Nenhuma comunidade encontrada para "<b>' + escapeHtml(q) + '</b>"</div>';
            dropdown.classList.add('show');
            return;
        }

        let html = '';
        data.comunidades.forEach(function(c) {
            const foto = c.foto || 'semfotocomunidade.jpg';
            const name = highlightMatch(escapeHtml(c.nome), q);
            html += '<a href="/comunidades.php?id=' + c.id + '" class="live-item">';
            html += '<img src="' + foto + '" onerror="this.src=\'semfotocomunidade.jpg\'">';
            html += '<div class="live-item-info">';
            html += '<div class="live-item-name">' + name + '</div>';
            html += '<div class="live-item-meta">' + escapeHtml(c.categoria || 'Geral') + ' · ' + (c.membros || 0) + ' membros</div>';
            html += '</div>';
            html += '</a>';
        });

        html += '<div class="live-footer"><a href="javascript:void(0)" onclick="doSearch()">Ver todos os resultados →</a></div>';

        dropdown.innerHTML = html;
        dropdown.classList.add('show');
    } catch(err) {
        console.error(err);
    }
}

function doSearch() {
    const input = document.getElementById('searchInput');
    document.getElementById('liveDropdown').classList.remove('show');
    _currentQuery = input.value.trim();
    _currentPage = 1;
    if (_currentQuery) {
        loadResults(1);
    }
}

async function loadResults(page) {
    _currentPage = page;
    const area = document.getElementById('resultsArea');
    const title = document.getElementById('pageTitle');

    title.textContent = 'Resultados para a comunidade: "' + _currentQuery + '"';
    area.innerHTML = '<div style="text-align:center; padding:20px; color:#999; font-size:11px;">Buscando...</div>';

    try {
        let url = '/api/buscar-comunidades-full?page=' + page;
        if (_currentQuery) url += '&q=' + encodeURIComponent(_currentQuery);

        const resp = await fetch(url);
        const data = await resp.json();

        if (!data.success) {
            area.innerHTML = '<div class="no-results-box">Erro ao buscar comunidades.</div>';
            return;
        }

        if (!data.comunidades || data.comunidades.length === 0) {
            area.innerHTML = '<div class="no-results-box">Nenhuma comunidade encontrada com este nome. Tente pesquisar outro termo.</div>';
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        let html = '<div class="result-list">';
        data.comunidades.forEach(function(c) {
            const foto = c.foto || 'semfotocomunidade.jpg';
            html += '<div class="result-item">';

            // Photo
            html += '<a href="/comunidades.php?id=' + c.id + '" class="result-pic">';
            html += '<img src="' + foto + '" onerror="this.src=\'semfotocomunidade.jpg\'">';
            html += '</a>';

            // Info
            html += '<div class="result-info">';
            html += '<a href="/comunidades.php?id=' + c.id + '" class="result-name">' + escapeHtml(c.nome) + '</a>';
            html += '<div class="result-details">';
            if (c.categoria) html += escapeHtml(c.categoria) + ' · ';
            html += c.membros + ' membro' + (c.membros !== 1 ? 's' : '');
            if (c.dono_nome) html += ' · dono: ' + escapeHtml(c.dono_nome);
            html += '</div>';
            if (c.descricao) {
                html += '<div class="result-details" style="margin-top:4px; color:#888;">' + escapeHtml(c.descricao).substring(0, 120);
                if (c.descricao.length > 120) html += '...';
                html += '</div>';
            }
            html += '</div>';

            // Actions
            html += '<div class="result-actions">';
            if (c.is_member) {
                html += '<span class="status-badge">✓ Membro</span>';
            } else {
                html += '<button class="icon-action-btn" onclick="entrarComunidade(' + c.id + ', this)">+ Entrar</button>';
            }
            html += '</div>';

            html += '</div>';
        });
        html += '</div>';

        // Total count
        html += '<div style="text-align:right; font-size:10px; color:#999; margin-top:10px;">' + data.total + ' comunidade' + (data.total !== 1 ? 's' : '') + ' encontrada' + (data.total !== 1 ? 's' : '') + '</div>';

        area.innerHTML = html;
        renderPagination(data.page, data.totalPages);

    } catch(err) {
        console.error(err);
        area.innerHTML = '<div class="no-results-box">Erro de conexão.</div>';
    }
}

function renderPagination(currentPage, totalPages) {
    const container = document.getElementById('pagination');
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    let html = '';
    if (currentPage > 1) {
        html += '<a class="page-btn" href="javascript:void(0)" onclick="loadResults(' + (currentPage - 1) + ')">← Anterior</a>';
    }
    const start = Math.max(1, currentPage - 3);
    const end = Math.min(totalPages, currentPage + 3);
    for (let i = start; i <= end; i++) {
        html += '<a class="page-btn' + (i === currentPage ? ' active' : '') + '" href="javascript:void(0)" onclick="loadResults(' + i + ')">' + i + '</a>';
    }
    if (currentPage < totalPages) {
        html += '<a class="page-btn" href="javascript:void(0)" onclick="loadResults(' + (currentPage + 1) + ')">Próxima →</a>';
    }
    container.innerHTML = html;
}

async function entrarComunidade(id, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const resp = await fetch('/api/comunidades/entrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: id })
        });
        const data = await resp.json();
        if (data.success) {
            btn.className = 'status-badge';
            btn.textContent = '✓ Membro';
            btn.disabled = true;
            btn.onclick = null;
            if (typeof showToast === 'function') showToast(data.message || 'Você entrou na comunidade!', 'success');
        } else {
            btn.textContent = '+ Entrar';
            btn.disabled = false;
            if (typeof showToast === 'function') showToast(data.message || 'Erro ao entrar.', 'error');
        }
    } catch(err) {
        btn.textContent = '+ Entrar';
        btn.disabled = false;
        if (typeof showToast === 'function') showToast('Erro de conexão.', 'error');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function highlightMatch(text, query) {
    if (!query) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark>$1</mark>');
}
</script>
</body>
</html>
