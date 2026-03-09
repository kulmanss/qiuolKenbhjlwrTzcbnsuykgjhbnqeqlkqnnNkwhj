<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Anúncio</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .anuncio-detail { padding: 0; }

    .anuncio-header-bar {
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 2px solid var(--orkut-blue); padding-bottom: 10px; margin-bottom: 20px;
    }
    .anuncio-header-bar h1 {
        font-size: 14px; margin: 0; color: var(--title); font-weight: bold;
    }
    .anuncio-header-bar a {
        font-size: 12px; color: var(--link); text-decoration: none;
    }
    .anuncio-header-bar a:hover { text-decoration: underline; }

    .anuncio-source {
        font-size: 11px; font-weight: bold;
        color: var(--orkut-blue); text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .anuncio-titulo {
        font-size: 22px; font-weight: bold;
        color: var(--title); line-height: 1.35;
        margin-bottom: 10px; text-align: center;
    }

    .anuncio-meta {
        display: flex; align-items: center; gap: 12px;
        font-size: 12px; color: #999;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--line);
    }
    .anuncio-meta-date { color: var(--orkut-blue); font-weight: 500; }
    .anuncio-meta-author { color: #666; }
    .anuncio-meta-author img {
        width: 20px; height: 20px; border-radius: 50%;
        vertical-align: middle; margin-right: 4px;
    }

    .anuncio-image {
        max-width: 100%;
        border-radius: 6px; overflow: hidden;
        margin: 15px auto 20px auto;
        text-align: center;
    }
    .anuncio-image img {
        max-width: 100%; height: auto; display: block;
        margin: 0 auto;
    }

    .anuncio-body {
        font-size: 14px; line-height: 1.8;
        color: #333;
        word-wrap: break-word;
    }
    .anuncio-body h1, .anuncio-body h2, .anuncio-body h3 {
        color: var(--title); margin: 12px 0 6px 0;
    }
    .anuncio-body h1 { font-size: 20px; }
    .anuncio-body h2 { font-size: 17px; }
    .anuncio-body h3 { font-size: 15px; }
    .anuncio-body p { margin: 0 0 10px 0; }
    .anuncio-body ul, .anuncio-body ol { margin: 8px 0; padding-left: 24px; }
    .anuncio-body li { margin-bottom: 4px; }
    .anuncio-body blockquote {
        border-left: 3px solid var(--orkut-blue); margin: 10px 0;
        padding: 6px 12px; color: #555; background: #f7f9fc;
    }
    .anuncio-body a { color: var(--link); }
    .anuncio-body a:hover { color: var(--orkut-pink); }
    .anuncio-body strong { font-weight: bold; }
    .anuncio-body em { font-style: italic; }
    .anuncio-body u { text-decoration: underline; }
    .anuncio-body s { text-decoration: line-through; }

    .anuncio-footer {
        margin-top: 25px; padding-top: 15px;
        border-top: 1px solid var(--line);
        display: flex; justify-content: space-between; align-items: center;
    }
    .anuncio-footer a {
        font-size: 12px; color: var(--link); text-decoration: none;
    }
    .anuncio-footer a:hover { text-decoration: underline; }

    .anuncio-nav {
        display: flex; gap: 15px;
    }
    .anuncio-nav a {
        font-size: 12px; color: var(--link); text-decoration: none;
    }
    .anuncio-nav a:hover { text-decoration: underline; color: var(--orkut-pink); }

    .anuncio-loading {
        text-align: center; padding: 50px 20px;
        color: #999; font-size: 13px;
    }
    .anuncio-error {
        text-align: center; padding: 50px 20px;
        color: #c0392b; font-size: 14px;
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> &gt; <a href="/anuncios.php">Anúncios</a> &gt; <span id="bc-titulo">Carregando...</span></div>
        <div class="card">
            <div id="anuncio-detail-container">
                <div class="anuncio-loading">Carregando anúncio...</div>
            </div>
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
        const id = params.get('id');
        if (!id) {
            document.getElementById('anuncio-detail-container').innerHTML = '<div class="anuncio-error">Anúncio não encontrado.</div>';
            return;
        }
        carregarAnuncio(id);
    });
});

async function carregarAnuncio(id) {
    try {
        const resp = await fetch('/api/anuncio/' + id);
        const data = await resp.json();
        if (!data.success) {
            document.getElementById('anuncio-detail-container').innerHTML = '<div class="anuncio-error">' + escapeHtml(data.message || 'Anúncio não encontrado.') + '</div>';
            document.getElementById('bc-titulo').textContent = 'Não encontrado';
            return;
        }
        renderAnuncio(data.anuncio, data.anterior, data.proximo);
    } catch(err) {
        document.getElementById('anuncio-detail-container').innerHTML = '<div class="anuncio-error">Erro ao carregar anúncio.</div>';
    }
}

function renderAnuncio(a, anterior, proximo) {
    document.getElementById('bc-titulo').textContent = a.titulo;
    document.title = 'Yorkut - ' + a.titulo;

    const dtRaw = a.criado_em || '';
    let dtFormatted = dtRaw;
    try {
        const d = new Date(dtRaw.replace(' ', 'T') + '-03:00');
        dtFormatted = d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'}) + ' - ' + d.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit', year:'numeric'});
    } catch(e) {}

    const adminFoto = a.admin_foto || '/img/default-avatar.png';

    let html = '<div class="anuncio-detail">';

    // Header
    html += '<div class="anuncio-header-bar">';
    html += '<h1>📢 Anúncio Oficial</h1>';
    html += '<a href="/anuncios.php">&laquo; Voltar aos anúncios</a>';
    html += '</div>';

    // Source label
    html += '<div class="anuncio-source">EQUIPE YORKUT</div>';

    // Meta
    html += '<div class="anuncio-meta">';
    html += '<span class="anuncio-meta-date">' + dtFormatted + '</span>';
    html += '</div>';

    // Title (centered, large, above image)
    html += '<div class="anuncio-titulo">' + escapeHtml(a.titulo) + '</div>';

    // Image
    if (a.foto) {
        html += '<div class="anuncio-image"><img src="' + escapeHtml(a.foto) + '" alt="' + escapeHtml(a.titulo) + '"></div>';
    }

    // Body (HTML from rich text editor)
    html += '<div class="anuncio-body">' + sanitizeHtml(a.mensagem) + '</div>';

    // Author
    html += '<div style="margin-top:20px;font-size:12px;color:var(--orkut-blue);font-weight:bold;">Equipe Yorkut</div>';

    // Footer with navigation
    html += '<div class="anuncio-footer">';
    html += '<a href="/anuncios.php">&laquo; Todos os anúncios</a>';
    html += '<div class="anuncio-nav">';
    if (anterior) {
        html += '<a href="/anuncio.php?id=' + anterior.id + '">&laquo; ' + escapeHtml(truncate(anterior.titulo, 30)) + '</a>';
    }
    if (proximo) {
        html += '<a href="/anuncio.php?id=' + proximo.id + '">' + escapeHtml(truncate(proximo.titulo, 30)) + ' &raquo;</a>';
    }
    html += '</div>';
    html += '</div>';

    html += '</div>';
    document.getElementById('anuncio-detail-container').innerHTML = html;
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function sanitizeHtml(str) {
    if (!str) return '';
    // Allow safe HTML tags from Quill editor
    const allowed = ['p','br','strong','em','u','s','b','i','h1','h2','h3','ul','ol','li','blockquote','a','span','sub','sup'];
    const allowedAttrs = ['href','target','style','class'];
    const tmp = document.createElement('div');
    tmp.innerHTML = str;
    function clean(el) {
        const children = Array.from(el.childNodes);
        children.forEach(child => {
            if (child.nodeType === 3) return; // text node
            if (child.nodeType === 1) {
                const tag = child.tagName.toLowerCase();
                if (!allowed.includes(tag)) {
                    // Replace with its children
                    while (child.firstChild) el.insertBefore(child.firstChild, child);
                    el.removeChild(child);
                } else {
                    // Remove disallowed attributes
                    Array.from(child.attributes).forEach(attr => {
                        if (!allowedAttrs.includes(attr.name)) child.removeAttribute(attr.name);
                    });
                    // Force links to open in new tab
                    if (tag === 'a') child.setAttribute('target', '_blank');
                    clean(child);
                }
            }
        });
    }
    clean(tmp);
    return tmp.innerHTML;
}
</script>
</body>
</html>
