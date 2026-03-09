<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Anúncios</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .news-list { display: flex; flex-direction: column; gap: 0; }

    .news-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 0;
        border-bottom: 1px solid var(--line);
        cursor: pointer;
        transition: background 0.15s;
        padding-left: 8px;
        padding-right: 8px;
        margin-left: -8px;
        margin-right: -8px;
        border-radius: 4px;
    }
    .news-item:hover { background: #eef4ff; }
    .news-item:last-child { border-bottom: none; }

    .news-item a.news-link {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        text-decoration: none;
        color: inherit;
        width: 100%;
    }
    .news-item a.news-link:hover { text-decoration: none; }

    .news-thumb {
        width: 300px; height: 169px; flex-shrink: 0;
        border-radius: 4px; overflow: hidden;
        background: #e4ebf5;
    }
    .news-thumb img {
        width: 100%; height: 100%; object-fit: contain;
        display: block;
    }
    .news-thumb-placeholder {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; background: linear-gradient(135deg, #e4ebf5 0%, #bfd0ea 100%);
        color: var(--orkut-blue);
    }

    .news-content { flex: 1; min-width: 0; }

    .news-source {
        font-size: 10px; font-weight: bold;
        color: var(--orkut-blue); text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .news-titulo {
        font-size: 15px; font-weight: bold;
        color: var(--title); line-height: 1.35;
        margin-bottom: 6px;
    }
    .news-item:hover .news-titulo { color: var(--orkut-pink); }

    .news-date {
        font-size: 12px; color: #888;
    }

    .news-empty {
        text-align: center; padding: 50px 20px;
        color: #999; font-style: italic;
        border: 1px dashed var(--line); border-radius: 4px;
        background: #f9f9f9; font-size: 13px;
    }

    .news-pagination {
        display: flex; justify-content: center; gap: 6px;
        padding: 15px 0; margin-top: 5px;
    }
    .news-pagination button {
        padding: 5px 12px; border: 1px solid var(--line);
        border-radius: 3px; background: #e4ebf5;
        color: var(--title); font-size: 11px; cursor: pointer;
    }
    .news-pagination button:hover { background: var(--orkut-light); }
    .news-pagination button.active {
        background: var(--orkut-blue); color: #fff;
        border-color: var(--orkut-blue); font-weight: bold;
    }
    .news-pagination button:disabled { opacity: 0.4; cursor: default; }

    .news-header {
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 2px solid var(--orkut-blue); padding-bottom: 10px; margin-bottom: 0;
    }
    .news-header h1 {
        font-size: 14px; margin: 0; color: var(--title); font-weight: bold;
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> &gt; Anúncios Oficiais</div>
        <div class="card">
            <div class="news-header">
                <h1>Anúncios Oficiais <span style="font-size:11px; color:#999; font-weight:normal;" id="anuncio-count"></span></h1>
            </div>
            <div id="anuncio-list-container">
                <div style="text-align:center; padding:30px; color:#999;">Carregando anúncios...</div>
            </div>
            <div id="anuncio-pagination" class="news-pagination"></div>
        </div>
    </div>
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'anuncios' }).then(() => {
        const params = new URLSearchParams(window.location.search);
        const p = parseInt(params.get('page')) || 1;
        carregarAnuncios(p);
    });
});

let _currentPage = 1;

async function carregarAnuncios(page) {
    try {
        const resp = await fetch('/api/anuncios?page=' + page);
        const data = await resp.json();
        if (!data.success) { showToast('Erro ao carregar anúncios.', 'error'); return; }

        _currentPage = data.page;
        renderAnuncios(data.anuncios, data.total);
        renderPagination(data.page, data.totalPages);
    } catch(err) {
        document.getElementById('anuncio-list-container').innerHTML = '<div class="news-empty">Erro ao carregar anúncios.</div>';
    }
}

function renderAnuncios(anuncios, total) {
    const container = document.getElementById('anuncio-list-container');
    const countEl = document.getElementById('anuncio-count');

    countEl.textContent = '(' + total + ')';

    if (!anuncios || anuncios.length === 0) {
        container.innerHTML = '<div class="news-empty">📭 Nenhum anúncio publicado ainda.</div>';
        return;
    }

    let html = '<div class="news-list">';
    anuncios.forEach(function(a) {
        const dtRaw = a.criado_em || '';
        let dtFormatted = dtRaw;
        try {
            const d = new Date(dtRaw.replace(' ', 'T') + '-03:00');
            dtFormatted = d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'}) + ' - ' + d.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit', year:'numeric'});
        } catch(e) {}

        let thumbHtml = '';
        if (a.foto) {
            thumbHtml = '<div class="news-thumb"><img src="' + escapeHtml(a.foto) + '" alt=""></div>';
        } else {
            thumbHtml = '<div class="news-thumb"><div class="news-thumb-placeholder">📢</div></div>';
        }

        html += '<div class="news-item">';
        html += '<a class="news-link" href="/anuncio.php?id=' + a.id + '">';
        html += thumbHtml;
        html += '<div class="news-content">';
        html += '<div class="news-source">Equipe Yorkut</div>';
        html += '<div class="news-titulo">' + escapeHtml(a.titulo) + '</div>';
        html += '<div class="news-date">' + dtFormatted + '</div>';
        html += '</div>';
        html += '</a>';
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderPagination(page, totalPages) {
    const pag = document.getElementById('anuncio-pagination');
    if (totalPages <= 1) { pag.innerHTML = ''; return; }

    let html = '';
    html += '<button onclick="carregarAnuncios(' + (page - 1) + ')" ' + (page <= 1 ? 'disabled' : '') + '>&laquo; Anterior</button>';

    let start = Math.max(1, page - 3);
    let end = Math.min(totalPages, page + 3);
    if (start > 1) html += '<button onclick="carregarAnuncios(1)">1</button><span style="padding:0 4px;color:#999;">...</span>';
    for (let i = start; i <= end; i++) {
        html += '<button onclick="carregarAnuncios(' + i + ')" class="' + (i === page ? 'active' : '') + '">' + i + '</button>';
    }
    if (end < totalPages) html += '<span style="padding:0 4px;color:#999;">...</span><button onclick="carregarAnuncios(' + totalPages + ')">' + totalPages + '</button>';

    html += '<button onclick="carregarAnuncios(' + (page + 1) + ')" ' + (page >= totalPages ? 'disabled' : '') + '>Próximo &raquo;</button>';
    pag.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>