<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Mensagens</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/mensagens.css">
<style>
    .msg-row { cursor: pointer; transition: background 0.2s; display: table-row; }
    .msg-row:hover { background-color: #f4f7fc; }
    .msg-row.unread { font-weight: bold; background-color: #fffdf5; }
    .msg-row.unread:hover { background-color: #fdf5d3; }
    .msg-row td { padding: 8px 10px; border-bottom: 1px dotted #e4ebf5; font-size: 11px; vertical-align: middle; }
    .msg-row td a { color: var(--link); text-decoration: none; }
    .msg-row td a:hover { text-decoration: underline; }
    .msg-table { width: 100%; border-collapse: collapse; }
    .msg-table th { background: #e8eef7; padding: 8px 10px; text-align: left; font-size: 11px; color: #555; border-bottom: 1px solid #c0d0e6; }
    .msg-sender-pic { width: 30px; height: 30px; border-radius: 3px; object-fit: cover; border: 1px solid #ccc; vertical-align: middle; margin-right: 5px; }
    .msg-detail-box { background: #fff; border: 1px solid #c0d0e6; border-radius: 4px; padding: 15px; margin-top: 10px; }
    .msg-detail-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px dotted #e4ebf5; }
    .msg-detail-pic { width: 40px; height: 40px; border-radius: 3px; object-fit: cover; border: 1px solid #ccc; }
    .msg-detail-body { font-size: 12px; line-height: 1.5; color: #333; min-height: 80px; }
    .msg-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .msg-toolbar-left { display: flex; gap: 5px; align-items: center; }
    .select-links { font-size: 10px; color: #666; }
    .select-links a { color: var(--link); font-size: 10px; }
    .btn-delete-msg { background: #e74c3c; color: #fff; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 11px; }
    .btn-delete-msg:hover { background: #c0392b; }
    .pagination-links { text-align: center; margin-top: 10px; }
    .pagination-links a { color: var(--link); font-size: 11px; margin: 0 3px; }
    .pagination-links strong { font-size: 11px; color: var(--title); margin: 0 3px; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col">
        <div class="breadcrumb" id="breadcrumb"><a href="/profile.php">Início</a> > Minhas Mensagens</div>
        
        <div class="card">
            <h1 class="orkut-name" style="font-size:22px; margin-bottom:15px;" id="pageTitle">
                Caixa de Mensagens
            </h1>

            <div class="msg-tabs">
                <a href="javascript:void(0);" class="msg-tab active" id="tabInbox" onclick="switchView('inbox')">📥 Entrada <span id="countInbox"></span></a>
                <a href="javascript:void(0);" class="msg-tab" id="tabOutbox" onclick="switchView('outbox')">📤 Enviadas</a>
                <a href="/mensagens_particular.php" class="msg-tab">✏️ Escrever</a>
            </div>

            <!-- Lista de mensagens -->
            <div id="msgListView">
                <div class="msg-toolbar" id="msgToolbar">
                    <div class="msg-toolbar-left">
                        <button class="btn-delete-msg" onclick="excluirSelecionadas()">🗑️ excluir</button>
                        <div class="select-links">Selecionar: <a href="javascript:void(0);" onclick="selecionarTodas(true)">Todas</a>, <a href="javascript:void(0);" onclick="selecionarTodas(false)">Nenhuma</a></div>
                    </div>
                    <div>
                        <select id="limitSelect" onchange="changeLimit(this.value)" style="padding:4px; border:1px solid #ccc; font-size:11px;">
                            <option value="5">Ver 5</option>
                            <option value="10" selected>Ver 10</option>
                            <option value="15">Ver 15</option>
                        </select>
                    </div>
                </div>

                <table class="msg-table">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th id="colSender">Remetente</th>
                            <th>Assunto</th>
                            <th style="width:120px;">Data</th>
                        </tr>
                    </thead>
                    <tbody id="msgTableBody">
                        <tr><td colspan="4" style="text-align:center; padding:30px; color:#999; font-style:italic;">Carregando...</td></tr>
                    </tbody>
                </table>

                <div class="pagination-links" id="paginationLinks"></div>
            </div>

            <!-- Detalhe de uma mensagem -->
            <div id="msgDetailView" style="display:none;">
                <div style="margin-bottom:10px;">
                    <a href="javascript:void(0);" onclick="voltarLista()" style="color:var(--link); font-size:11px;">← Voltar para a lista</a>
                </div>
                <div class="msg-detail-box">
                    <div class="msg-detail-header">
                        <img id="detailPic" class="msg-detail-pic" src="/img/default-avatar.png">
                        <div>
                            <div style="font-weight:bold; color:var(--link); font-size:12px;">
                                <span id="detailSenderLabel">De:</span> <a href="#" id="detailSender"></a>
                            </div>
                            <div style="font-size:11px; color:#666;" id="detailDate"></div>
                        </div>
                    </div>
                    <div style="font-weight:bold; color:var(--title); font-size:13px; margin-bottom:10px;">
                        Assunto: <span id="detailAssunto"></span>
                    </div>
                    <div class="msg-detail-body" id="detailBody"></div>
                    <div style="margin-top:15px; text-align:right;" id="detailActions">
                        <a href="#" id="btnResponder" class="btn-action" style="text-decoration:none; padding:6px 15px; font-size:11px;">💬 Responder</a>
                    </div>
                </div>
            </div>
        </div> 
    </div> 
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
let _currentView = 'inbox';
let _currentPage = 1;
let _currentLimit = 10;

document.addEventListener('DOMContentLoaded', async () => {
    await loadLayout({ activePage: 'mensagens' });

    // Checar URL para view
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const msgId = urlParams.get('id');

    if (view === 'outbox') {
        _currentView = 'outbox';
        document.getElementById('tabInbox').classList.remove('active');
        document.getElementById('tabOutbox').classList.add('active');
        document.getElementById('colSender').textContent = 'Destinatário';
    }

    if (msgId) {
        abrirMensagem(parseInt(msgId));
    } else {
        loadMensagens();
    }
});

function switchView(view) {
    _currentView = view;
    _currentPage = 1;
    document.getElementById('tabInbox').classList.toggle('active', view === 'inbox');
    document.getElementById('tabOutbox').classList.toggle('active', view === 'outbox');
    document.getElementById('colSender').textContent = view === 'inbox' ? 'Remetente' : 'Destinatário';
    document.getElementById('msgDetailView').style.display = 'none';
    document.getElementById('msgListView').style.display = 'block';
    loadMensagens();
}

function changeLimit(val) {
    _currentLimit = parseInt(val);
    _currentPage = 1;
    loadMensagens();
}

async function loadMensagens() {
    const tbody = document.getElementById('msgTableBody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">Carregando...</td></tr>';

    const url = _currentView === 'outbox'
        ? '/api/mensagens-enviadas?page=' + _currentPage + '&limit=' + _currentLimit
        : '/api/mensagens?page=' + _currentPage + '&limit=' + _currentLimit;

    try {
        const resp = await fetch(url);
        const data = await resp.json();

        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">Erro ao carregar.</td></tr>';
            return;
        }

        // Atualizar badge de não lidas
        if (_currentView === 'inbox' && data.naoLidas > 0) {
            document.getElementById('countInbox').textContent = '(' + data.naoLidas + ')';
        } else if (_currentView === 'inbox') {
            document.getElementById('countInbox').textContent = '';
        }

        if (data.mensagens.length === 0) {
            const msg = _currentView === 'inbox' 
                ? 'Nenhuma mensagem na caixa de entrada.' 
                : 'Nenhuma mensagem enviada.';
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#999; font-style:italic;">' + msg + '</td></tr>';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        data.mensagens.forEach(function(m) {
            const isUnread = _currentView === 'inbox' && !m.lida;
            const nome = _currentView === 'inbox' ? m.remetente_nome : m.destinatario_nome;
            const foto = _currentView === 'inbox' ? (m.remetente_foto || getDefaultAvatar(m.remetente_sexo)) : (m.destinatario_foto || getDefaultAvatar(m.destinatario_sexo));
            const uid = _currentView === 'inbox' ? m.remetente_id : m.destinatario_id;

            html += '<tr class="msg-row' + (isUnread ? ' unread' : '') + '" onclick="abrirMensagem(' + m.id + ')">';
            html += '<td><input type="checkbox" class="msg-checkbox" value="' + m.id + '" onclick="event.stopPropagation();"></td>';
            html += '<td><img class="msg-sender-pic" src="' + escapeHtml(foto) + '"> <a href="/profile.php?uid=' + uid + '" onclick="event.stopPropagation();">' + escapeHtml(nome) + '</a></td>';
            html += '<td>' + escapeHtml(m.assunto) + '</td>';
            html += '<td style="font-size:10px; color:#666;">' + formatTime(m.criado_em) + '</td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;

        renderPagination(data.page, data.totalPages);
    } catch(err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#999;">Erro de conexão.</td></tr>';
    }
}

async function abrirMensagem(id) {
    document.getElementById('msgListView').style.display = 'none';
    document.getElementById('msgDetailView').style.display = 'block';

    try {
        const resp = await fetch('/api/mensagens/' + id);
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('detailBody').textContent = data.message || 'Erro ao carregar mensagem.';
            return;
        }

        const m = data.mensagem;
        const me = getUserData();
        const isInbox = m.destinatario_id === me.id;

        if (isInbox) {
            document.getElementById('detailSenderLabel').textContent = 'De:';
            document.getElementById('detailSender').textContent = m.remetente_nome;
            document.getElementById('detailSender').href = '/profile.php?uid=' + m.remetente_id;
            document.getElementById('detailPic').src = m.remetente_foto || getDefaultAvatar(m.remetente_sexo);
            document.getElementById('btnResponder').href = '/mensagens_particular.php?to=' + m.remetente_id;
            document.getElementById('btnResponder').style.display = '';
        } else {
            document.getElementById('detailSenderLabel').textContent = 'Para:';
            document.getElementById('detailSender').textContent = m.destinatario_nome;
            document.getElementById('detailSender').href = '/profile.php?uid=' + m.destinatario_id;
            document.getElementById('detailPic').src = m.destinatario_foto || getDefaultAvatar(m.destinatario_sexo);
            document.getElementById('btnResponder').style.display = 'none';
        }

        document.getElementById('detailAssunto').textContent = m.assunto;
        document.getElementById('detailDate').textContent = formatTime(m.criado_em);
        document.getElementById('detailBody').innerHTML = m.mensagem;

        document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > <a href="/mensagens.php">Mensagens</a> > ' + escapeHtml(m.assunto);
    } catch(err) {
        document.getElementById('detailBody').textContent = 'Erro de conexão.';
    }
}

function voltarLista() {
    document.getElementById('msgDetailView').style.display = 'none';
    document.getElementById('msgListView').style.display = 'block';
    document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Minhas Mensagens';
    loadMensagens();
}

async function excluirSelecionadas() {
    const checked = document.querySelectorAll('.msg-checkbox:checked');
    if (checked.length === 0) { alert('Selecione pelo menos uma mensagem.'); return; }
    showConfirm('Excluir ' + checked.length + ' mensagem(ns) selecionada(s)?', async function() {
        const ids = Array.from(checked).map(function(cb) { return parseInt(cb.value); });

        try {
            const resp = await fetch('/api/mensagens/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids, tipo: _currentView === 'outbox' ? 'outbox' : 'inbox' })
            });
            const data = await resp.json();
            if (data.success) {
                loadMensagens();
            } else {
                alert(data.message || 'Erro ao excluir.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    });
}

function selecionarTodas(sel) {
    document.querySelectorAll('.msg-checkbox').forEach(function(cb) { cb.checked = sel; });
}

function renderPagination(current, total) {
    const el = document.getElementById('paginationLinks');
    if (total <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (current > 1) html += '<a href="javascript:void(0);" onclick="goToPage(' + (current - 1) + ')">« anterior</a> ';
    for (let i = 1; i <= total; i++) {
        if (i === current) html += '<strong>' + i + '</strong> ';
        else html += '<a href="javascript:void(0);" onclick="goToPage(' + i + ')">' + i + '</a> ';
    }
    if (current < total) html += '<a href="javascript:void(0);" onclick="goToPage(' + (current + 1) + ')">próxima »</a>';
    el.innerHTML = html;
}

function goToPage(p) { _currentPage = p; loadMensagens(); }

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        const diff = now - d;
        const mins = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        const fullDate = dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;

        let relative = '';
        if (mins < 1) relative = 'agora mesmo';
        else if (mins === 1) relative = '1 min atrás';
        else if (mins < 60) relative = mins + ' minutos atrás';
        else if (hours === 1) relative = '1 hora atrás';
        else if (hours < 24) relative = hours + ' horas atrás';
        else if (days === 1) relative = '1 dia atrás';
        else if (days < 30) relative = days + ' dias atrás';
        else {
            const months = Math.floor(days / 30);
            const years = Math.floor(days / 365);
            if (years >= 1) relative = years === 1 ? '1 ano atrás' : years + ' anos atrás';
            else relative = months === 1 ? '1 mes atrás' : months + ' meses atrás';
        }

        return fullDate + ' (' + relative + ')';
    } catch(e) { return dateStr; }
}
</script>
</body>
</html>
