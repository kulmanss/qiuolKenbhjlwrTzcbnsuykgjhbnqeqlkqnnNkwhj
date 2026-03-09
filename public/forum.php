<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Fórum</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<style>
    /* Estilos base */
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; } 
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .left-col { width: 200px; flex-shrink: 0; position: sticky; top: 15px; max-height: calc(100vh - 30px); overflow-y: auto; }
    .center-col { flex: 1; min-width: 0; }
    
    /* Toolbar Superior */
    .forum-toolbar { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .search-box { display: flex; align-items: center; border: 1px solid #ccc; border-radius: 4px; overflow: hidden; background: #fff; }
    .search-box input { border: none; padding: 6px 10px; font-size: 11px; outline: none; width: 200px; }
    .search-box button { background: #f0f0f0; border: none; border-left: 1px solid #ccc; padding: 6px 10px; cursor: pointer; color: #555; }
    .search-box button:hover { background: #e0e0e0; }

    /* Estilos do Fórum Geral */
    .forum-table { width:100%; border-collapse:collapse; font-size:12px; }
    .forum-table th { background:#e8eef7; padding:8px; text-align:left; color:var(--title); border:1px solid var(--line); }
    .forum-table td { padding:10px 8px; border-bottom:1px dotted var(--line); vertical-align:top; }
    .forum-table tr:nth-child(even) td { background-color: #f4f7fc; }
    .forum-title { font-weight:bold; font-size:13px; color:var(--link); }
    .forum-meta { font-size:10px; color:#666; margin-top:3px; }
    
    /* Layout dos Posts Isolados */
    .topic-post { display:flex; gap:15px; border:1px solid var(--line); background:#fdfdfd; margin-bottom:10px; border-radius:4px; position: relative;}
    .topic-author { width:110px; background:#f4f7fc; padding:15px 10px; text-align:center; font-size:11px; font-weight:bold; border-right:1px solid var(--line); }
    .topic-author img { width:70px; height:70px; object-fit:cover; border:1px solid #ccc; margin-bottom:5px; border-radius:3px; }
    .topic-content { flex:1; padding:15px; font-size:12px; line-height:1.5; color:#333; overflow-wrap: break-word;}
    .topic-date { display:flex; justify-content:space-between; align-items:center; font-size:10px; color:#999; margin-bottom:15px; border-bottom:1px dotted #ccc; padding-bottom:5px; }
    
    .badge-crown { font-size: 10px; padding: 2px 4px; border-radius: 4px; margin-top: 2px; display: inline-block; }
    .badge-owner { background: #fffdf5; color: #d35400; border: 1px solid #f39c12; }
    .badge-mod { background: #e4f2e9; color: #2a6b2a; border: 1px solid #8bc59e; }

    .badge-fixado { background: #fff3cd; color: #856404; font-size: 9px; padding: 1px 5px; border-radius: 3px; font-weight: bold; margin-left: 4px; }
    .badge-trancado { background: #f8d7da; color: #721c24; font-size: 9px; padding: 1px 5px; border-radius: 3px; font-weight: bold; margin-left: 4px; }

    /* Paginação e Botões */
    .pagination { margin: 15px 0; text-align: right; font-size: 11px; }
    .pagination a { padding: 4px 8px; border: 1px solid var(--line); background: #f4f7fc; color: var(--link); text-decoration: none; margin-left: 3px; border-radius: 3px; }
    .pagination a.active { font-weight: bold; background: var(--orkut-blue); color: #fff; border-color: var(--title); }

    .btn-share { background: #fff; border: 1px solid #ccc; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; font-weight:bold;}
    .btn-share:hover { background: #f0f0f0; }

    /* Textarea */
    .editor-area-simple { width: 100%; min-height: 80px; padding: 10px; border: 1px solid #c0d0e6; border-radius: 4px; font-family: Tahoma, Arial; font-size: 12px; resize: vertical; box-sizing: border-box; }

    /* Owner actions */
    .owner-actions { display: flex; gap: 4px; }
    .owner-actions button {
        background: #fff; border: 1px solid #ccc; padding: 2px 8px;
        font-size: 10px; cursor: pointer; border-radius: 2px; color: #666;
    }
    .owner-actions button:hover { background: #e8eef7; border-color: #a5bce3; }
    .owner-actions button.btn-danger { color: #cc0000; border-color: #ffcccc; }
    .owner-actions button.btn-danger:hover { background: #ffebee; }
    
    @media (max-width: 768px) {
        .topic-post { flex-direction: column; }
        .topic-author { width: 100%; border-right: none; border-bottom: 1px solid var(--line); display:flex; align-items:center; gap:10px; padding:10px;}
        .topic-author img { width: 40px; height: 40px; margin: 0; }
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container" id="main-container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" id="center-col">
        <div class="breadcrumb" id="forum-breadcrumb">
            <a href="/profile.php">Início</a> > <span>Carregando...</span>
        </div>
        <div class="card" id="forum-container">
            <div style="text-align:center;padding:30px;color:#999;font-size:12px;">Carregando fórum...</div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'comunidades' }).then(() => {
        const params = new URLSearchParams(window.location.search);
        const commId = params.get('id');
        const topicoId = params.get('topico');
        if (topicoId) carregarTopico(topicoId);
        else if (commId) carregarForum(commId);
        else document.getElementById('forum-container').innerHTML = '<div style="text-align:center;padding:30px;color:#999;">Comunidade não especificada.</div>';
    });
});

let _commId = null;
let _isMembro = false;
let _topicoId = null;
let _topicoData = null;
let _commData = null;

function renderLeftSidebar(commId, commNome, commFoto, totalMembros, isOwner) {
    const foto = commFoto || '/img/default-comm.png';
    let html = '<div class="card-left">';
    html += '<div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;"><a href="/comunidades.php?id=' + commId + '" style="display:block;width:100%;height:100%;"><img src="' + escapeHtml(foto) + '"></a></div>';
    html += '<div style="text-align:center; font-size:11px; margin-bottom:15px;">';
    html += '<strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(commNome) + '</strong>';
    if (totalMembros !== undefined) html += 'membros: ' + totalMembros;
    html += '</div>';
    html += '<ul class="menu-left hide-on-mobile" style="margin-top:0;">';
    html += '<li><a href="/comunidades.php?id=' + commId + '"><span>🏠</span> comunidade</a></li>';
    html += '<li><a href="/comunidade_convidar_amigo.php?id=' + commId + '"><span>📱</span> convidar amigos</a></li>';
    html += '<li class="active"><a href="/forum.php?id=' + commId + '"><span>💬</span> fórum</a></li>';
    html += '<li><a href="/enquetes.php?id=' + commId + '"><span>📊</span> enquetes</a></li>';
    html += '<li><a href="/comunidades.php?id=' + commId + '&view=membros"><span>👥</span> membros</a></li>';
    html += '<li><a href="/comunidades_staff.php?id=' + commId + '"><span>👑</span> staff</a></li>';
    html += '<li><a href="/sorteio.php?id=' + commId + '"><span>🎁</span> sorteios</a></li>';
    if (isOwner) {
        html += '<li><a href="/comunidades.php?id=' + commId + '&view=config"><span>⚙️</span> configurações</a></li>';
    }
    html += '</ul>';
    html += '</div>';
    document.getElementById('app-left-col').innerHTML = html;
}

async function carregarForum(commId, page) {
    _commId = commId;
    page = page || 1;
    try {
        const resp = await fetch('/api/forum/' + commId + '/topicos?page=' + page);
        const data = await resp.json();
        if (!data.success) {
            document.getElementById('forum-container').innerHTML = '<div style="text-align:center;padding:30px;color:#999;">' + escapeHtml(data.message) + '</div>';
            return;
        }
        _isMembro = data.isMembro;
        _commData = data.comunidade;
        renderLeftSidebar(data.comunidade.id, data.comunidade.nome, data.comunidade.foto, data.comunidade.total_membros, data.isOwner);
        renderForum(data);
    } catch(err) {
        document.getElementById('forum-container').innerHTML = '<div style="text-align:center;padding:30px;color:#999;">Erro ao carregar fórum.</div>';
    }
}

function renderForum(data) {
    const c = data.comunidade;

    document.getElementById('forum-breadcrumb').innerHTML =
        '<a href="/profile.php">Início</a> > <a href="/comunidades.php?id=' + c.id + '">' + escapeHtml(c.nome) + '</a> > Fórum';
    document.title = 'Yorkut - Fórum: ' + c.nome;

    let html = '';

    // Toolbar (identical to original)
    html += '<div class="forum-toolbar">';
    html += '<h1 class="orkut-name" style="font-size:20px; margin:0;">Fórum da Comunidade</h1>';
    html += '<div style="display:flex; gap:10px; align-items:center;">';
    html += '<a class="btn-share" onclick="copiarLink(window.location.href)">🔗 Compartilhar Fórum</a>';
    if (_isMembro) {
        html += '<button class="btn-action" style="padding:6px 12px; font-size:12px;" onclick="toggleNovoTopico()">Criar Tópico</button>';
    }
    html += '</div></div>';

    // Search box
    html += '<div style="margin-bottom:15px;">';
    html += '<div class="search-box" style="max-width:300px;">';
    html += '<input type="text" id="forum-search-input" placeholder="Buscar no fórum e dê Enter..." onkeydown="if(event.key===\'Enter\')buscarForum()">';
    html += '<button onclick="buscarForum()">🔍</button>';
    html += '</div></div>';

    // New topic form (hidden, identical style to original)
    if (_isMembro) {
        html += '<div id="newTopic" style="display:none; background:#f4f7fc; padding:15px; border:1px solid var(--line); border-radius:4px; margin-bottom:15px;">';
        html += '<input type="text" id="novo-topico-titulo" placeholder="Título do Tópico" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px; font-size:12px; box-sizing:border-box; font-weight:bold;">';
        html += '<textarea id="novo-topico-mensagem" class="editor-area-simple" placeholder="Mensagem principal..."></textarea>';
        html += '<div style="text-align:right; margin-top:10px;">';
        html += '<button class="btn-action" style="background:#fff; color:#666;" onclick="toggleNovoTopico()">Cancelar</button> ';
        html += '<button class="btn-action" onclick="criarTopico()">Postar Tópico</button>';
        html += '</div></div>';
    }

    // Forum table (identical to original)
    html += '<table class="forum-table">';
    html += '<tr><th style="width:50%;">Tópico</th><th style="width:15%;text-align:center;">Respostas</th><th style="width:35%;">Última postagem</th></tr>';
    
    if (data.topicos.length === 0) {
        html += '<tr><td colspan="3" style="text-align:center;color:#999;padding:30px;font-style:italic;">Nenhum tópico criado ainda. Seja o primeiro!</td></tr>';
    } else {
        data.topicos.forEach(t => {
            let badges = '';
            if (t.fixado) badges += ' <span class="badge-fixado">📌 fixado</span>';
            if (t.trancado) badges += ' <span class="badge-trancado">🔒 trancado</span>';
            const resps = t.total_respostas > 0 ? t.total_respostas - 1 : 0;

            html += '<tr>';
            html += '<td>';
            html += '<a class="forum-title" href="/forum.php?topico=' + t.id + '">' + escapeHtml(t.titulo) + '</a>' + badges;
            html += '<div class="forum-meta">por <a href="/profile.php?uid=' + t.autor_id + '" style="color:var(--link);text-decoration:none;">' + escapeHtml(t.autor_nome) + '</a> · ' + formatDate(t.criado_em) + '</div>';
            html += '</td>';
            html += '<td style="text-align:center;font-weight:bold;color:var(--title);">' + resps + '</td>';
            html += '<td style="font-size:10px;color:#666;">';
            if (t.ultima_resposta_data) {
                html += formatDate(t.ultima_resposta_data);
                if (t.ultima_resposta_autor) html += '<br>por <b>' + escapeHtml(t.ultima_resposta_autor) + '</b>';
            } else { html += '—'; }
            html += '</td></tr>';
        });
    }
    html += '</table>';

    // Pagination
    if (data.totalPages > 1) {
        html += renderPagination(data.page, data.totalPages, 'carregarForum(' + _commId + ', {page})');
    }

    document.getElementById('forum-container').innerHTML = html;
}

function toggleNovoTopico() {
    const form = document.getElementById('newTopic');
    if (!form) return;
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') document.getElementById('novo-topico-titulo').focus();
}

function buscarForum() {
    const q = document.getElementById('forum-search-input').value.trim();
    if (q && _commId) carregarForum(_commId, 1);
}

function copiarLink(url) {
    let tempInput = document.createElement("input");
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    showToast("🔗 Link copiado!", "success");
}

async function criarTopico() {
    const titulo = document.getElementById('novo-topico-titulo').value.trim();
    const mensagem = document.getElementById('novo-topico-mensagem').value.trim();
    if (!titulo || !mensagem) { showToast('Preencha título e mensagem.', 'error'); return; }
    try {
        const resp = await fetch('/api/forum/' + _commId + '/topico/criar', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo, mensagem })
        });
        const data = await resp.json();
        if (data.success) { showToast('Tópico criado!', 'success'); window.location.href = '/forum.php?topico=' + data.id; }
        else { showToast(data.message || 'Erro.', 'error'); }
    } catch(err) { showToast('Erro de conexão.', 'error'); }
}

// ===== TOPIC DETAIL =====
async function carregarTopico(topicoId, page) {
    _topicoId = topicoId;
    page = page || 1;
    try {
        const resp = await fetch('/api/forum/topico/' + topicoId + '?page=' + page);
        const data = await resp.json();
        if (!data.success) {
            document.getElementById('forum-container').innerHTML = '<div style="text-align:center;padding:30px;color:#999;">' + escapeHtml(data.message) + '</div>';
            return;
        }
        _topicoData = data;
        _commId = data.topico.comunidade_id;
        renderLeftSidebar(data.topico.comunidade_id, data.topico.comunidade_nome, data.topico.comunidade_foto, data.totalMembros, data.isOwner);
        renderTopico(data);
    } catch(err) {
        document.getElementById('forum-container').innerHTML = '<div style="text-align:center;padding:30px;color:#999;">Erro ao carregar tópico.</div>';
    }
}

function renderTopico(data) {
    const t = data.topico;

    document.getElementById('forum-breadcrumb').innerHTML =
        '<a href="/profile.php">Início</a> > <a href="/comunidades.php?id=' + t.comunidade_id + '">' + escapeHtml(t.comunidade_nome) + '</a> > <a href="/forum.php?id=' + t.comunidade_id + '">Fórum</a> > ' + escapeHtml(truncate(t.titulo, 40));
    document.title = 'Yorkut - ' + t.titulo;

    let html = '';

    // Topic toolbar (same style as forum list)
    html += '<div class="forum-toolbar">';
    html += '<h1 class="orkut-name" style="font-size:20px; margin:0;">' + escapeHtml(t.titulo) + '</h1>';
    html += '<div style="display:flex; gap:10px; align-items:center;">';
    html += '<a class="btn-share" onclick="copiarLink(window.location.href)">🔗 Compartilhar</a>';
    if (data.isOwner || data.isAutor) {
        if (data.isOwner) {
            html += '<button class="btn-action" style="padding:4px 8px; font-size:10px;" onclick="fixarTopico()">' + (t.fixado ? '📌 Desfixar' : '📌 Fixar') + '</button>';
            html += '<button class="btn-action" style="padding:4px 8px; font-size:10px;" onclick="trancarTopico()">' + (t.trancado ? '🔓 Destrancar' : '🔒 Trancar') + '</button>';
        }
        html += '<button class="btn-action" style="padding:4px 8px; font-size:10px; color:#cc0000; border-color:#ffcccc;" onclick="excluirTopico()">🗑️ Excluir</button>';
    }
    html += '</div></div>';

    // Badges
    if (t.fixado || t.trancado) {
        html += '<div style="margin-bottom:10px;">';
        if (t.fixado) html += '<span class="badge-fixado">📌 fixado</span> ';
        if (t.trancado) html += '<span class="badge-trancado">🔒 trancado</span>';
        html += '</div>';
    }

    // Posts (identical to original topic-post layout)
    data.respostas.forEach((r, i) => {
        const foto = r.autor_foto || '/img/default-avatar.png';
        const isFirst = (data.page === 1 && i === 0);
        
        html += '<div class="topic-post">';
        
        // Author panel (identical to original)
        html += '<div class="topic-author">';
        html += '<a href="/profile.php?uid=' + r.autor_id + '"><img src="' + escapeHtml(foto) + '" alt=""></a>';
        html += '<br><a href="/profile.php?uid=' + r.autor_id + '" style="color:var(--link);text-decoration:none;">' + escapeHtml(r.autor_nome) + '</a>';
        html += '<br><span style="font-weight:normal;color:#999;font-size:10px;">' + r.total_posts_autor + ' posts</span>';
        html += '</div>';
        
        // Content panel (identical to original)
        html += '<div class="topic-content">';
        
        // Date bar (flex, space-between, dotted bottom — identical to original)
        html += '<div class="topic-date">';
        html += '<span>';
        if (isFirst) html += '<b style="color:var(--link);">tópico original</b> · ';
        html += formatDate(r.criado_em);
        html += '</span>';
        html += '<span>';
        if (data.isOwner || r.autor_id === _getUserId()) {
            html += '<a href="javascript:void(0);" onclick="excluirResposta(' + r.id + ')" style="color:#cc0000;font-size:10px;text-decoration:none;">excluir</a>';
        }
        html += '</span>';
        html += '</div>';
        
        html += '<div style="white-space:pre-wrap;overflow-wrap:break-word;">' + escapeHtml(r.mensagem) + '</div>';
        html += '</div>';
        html += '</div>';
    });

    // Pagination
    if (data.totalPages > 1) {
        html += renderPagination(data.page, data.totalPages, 'carregarTopico(' + _topicoId + ', {page})');
    }

    // Reply form (identical style to original new topic form)
    if (data.isMembro && !t.trancado) {
        html += '<div style="background:#f4f7fc; padding:15px; border:1px solid var(--line); border-radius:4px; margin-top:15px;">';
        html += '<textarea id="resposta-mensagem" class="editor-area-simple" placeholder="Escreva sua resposta..."></textarea>';
        html += '<div style="text-align:right; margin-top:10px;">';
        html += '<button class="btn-action" onclick="responderTopico()">Responder</button>';
        html += '</div></div>';
    } else if (t.trancado) {
        html += '<div style="text-align:center;padding:15px;color:#999;font-size:11px;border:1px solid var(--line);border-radius:4px;margin-top:15px;background:#f4f7fc;">🔒 Este tópico está trancado. Não é possível responder.</div>';
    }

    document.getElementById('forum-container').innerHTML = html;
}

async function responderTopico() {
    const mensagem = document.getElementById('resposta-mensagem').value.trim();
    if (!mensagem) { showToast('Digite uma mensagem.', 'error'); return; }
    try {
        const resp = await fetch('/api/forum/topico/' + _topicoId + '/responder', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensagem })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Resposta enviada!', 'success');
            const lastPage = _topicoData ? _topicoData.totalPages : 1;
            carregarTopico(_topicoId, Math.max(lastPage, 1));
        } else { showToast(data.message || 'Erro.', 'error'); }
    } catch(err) { showToast('Erro de conexão.', 'error'); }
}

async function excluirTopico() {
    if (!confirm('Excluir este tópico e todas as respostas?')) return;
    try {
        const resp = await fetch('/api/forum/topico/' + _topicoId + '/excluir', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}'
        });
        const data = await resp.json();
        if (data.success) { showToast('Tópico excluído.', 'success'); window.location.href = '/forum.php?id=' + _topicoData.topico.comunidade_id; }
        else { showToast(data.message, 'error'); }
    } catch(err) { showToast('Erro.', 'error'); }
}

async function excluirResposta(respostaId) {
    if (!confirm('Excluir esta resposta?')) return;
    try {
        const resp = await fetch('/api/forum/resposta/' + respostaId + '/excluir', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}'
        });
        const data = await resp.json();
        if (data.success) { showToast('Resposta excluída.', 'success'); carregarTopico(_topicoId, _topicoData ? _topicoData.page : 1); }
        else { showToast(data.message, 'error'); }
    } catch(err) { showToast('Erro.', 'error'); }
}

async function fixarTopico() {
    try {
        const resp = await fetch('/api/forum/topico/' + _topicoId + '/fixar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
        const data = await resp.json();
        if (data.success) { showToast(data.fixado ? 'Tópico fixado!' : 'Tópico desfixado.', 'success'); carregarTopico(_topicoId); }
        else { showToast(data.message, 'error'); }
    } catch(err) { showToast('Erro.', 'error'); }
}

async function trancarTopico() {
    try {
        const resp = await fetch('/api/forum/topico/' + _topicoId + '/trancar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
        const data = await resp.json();
        if (data.success) { showToast(data.trancado ? 'Tópico trancado!' : 'Tópico destrancado.', 'success'); carregarTopico(_topicoId); }
        else { showToast(data.message, 'error'); }
    } catch(err) { showToast('Erro.', 'error'); }
}

function _getUserId() { try { return String(_userData.id); } catch(e) { return ''; } }

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function formatDate(dt) {
    if (!dt) return '';
    try {
        const d = new Date(dt.replace(' ', 'T') + '-03:00');
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch(e) { return dt; }
}

function renderPagination(current, total, callbackTpl) {
    let html = '<div class="pagination">';
    for (let i = 1; i <= total; i++) {
        if (i === current) { html += '<a class="active" href="javascript:void(0);">' + i + '</a>'; }
        else if (i <= 2 || i >= total - 1 || Math.abs(i - current) <= 2) { html += '<a href="javascript:void(0);" onclick="' + callbackTpl.replace('{page}', i) + '">' + i + '</a>'; }
        else if (i === 3 || i === total - 2) { html += '<span style="margin:0 3px;">...</span>'; }
    }
    html += '</div>';
    return html;
}
</script>
</body>
</html>
