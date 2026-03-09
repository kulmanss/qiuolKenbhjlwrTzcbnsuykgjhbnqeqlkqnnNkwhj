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
    /* Tabela de Mensagens */
    .msg-row { cursor: pointer; transition: background 0.2s; }
    .msg-row:hover { background-color: #f4f7fc; }
    .msg-row.unread { font-weight: bold; background-color: #fffdf5; }
    .msg-row.unread:hover { background-color: #fdf5d3; }
    .msg-table { width: 100%; border-collapse: collapse; }
    .msg-table th { background: #e8eef7; padding: 8px 10px; text-align: left; font-size: 11px; color: #555; border-bottom: 1px solid #c0d0e6; }
    .msg-table td { padding: 8px 10px; border-bottom: 1px dotted #e4ebf5; font-size: 11px; vertical-align: middle; }
    .msg-icon { font-size: 16px; width: 30px; text-align: center; }
    .msg-link { color: var(--link); }

    /* Auto-complete */
    .search-friend-wrapper { position: relative; width: 100%; margin-bottom: 10px; }
    .friend-dropdown { display: none; position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #a5bce3; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; z-index: 1000; border-radius: 0 0 4px 4px; overflow: hidden; }
    .friend-item { display: flex; align-items: center; padding: 8px 10px; cursor: pointer; border-bottom: 1px dotted #e4ebf5; font-size: 11px; color: var(--link); }
    .friend-item:hover { background: #eef4ff; }
    .friend-item img { width: 25px; height: 25px; border-radius: 3px; object-fit: cover; margin-right: 10px; border: 1px solid #ccc; }

    /* Editor */
    .editor-toolbar { background: #e8eef7; padding: 5px; border: 1px solid #c0d0e6; border-bottom: none; display: flex; gap: 5px; border-radius: 3px 3px 0 0; }
    .editor-toolbar button { background: #fff; border: 1px solid #a5bce3; cursor: pointer; padding: 4px 8px; font-weight: bold; color: #3b5998; border-radius: 2px; font-size: 11px; }
    .editor-toolbar button:hover { background: #dbe3ef; }
    .editor-area { border: 1px solid #c0d0e6; padding: 10px; min-height: 150px; border-radius: 0 0 3px 3px; background: #fff; font-size: 12px; margin-bottom: 10px; outline: none; line-height: 1.4; overflow-y: auto; }

    /* Detalhe */
    .msg-detail-box { background: #fff; border: 1px solid #c0d0e6; border-radius: 4px; padding: 15px; margin-top: 10px; }
    .msg-detail-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px dotted #e4ebf5; }
    .msg-detail-pic { width: 40px; height: 40px; border-radius: 3px; object-fit: cover; border: 1px solid #ccc; }
    .msg-detail-body { font-size: 12px; line-height: 1.5; color: #333; min-height: 80px; }

    /* Paginação */
    .pagination-links { text-align: center; margin-top: 10px; }
    .pagination-links a { color: var(--link); font-size: 11px; margin: 0 3px; cursor: pointer; }
    .pagination-links strong { font-size: 11px; color: var(--title); margin: 0 3px; }

    .msg-empty { text-align: center; padding: 30px; color: #999; font-style: italic; font-size: 11px; }
</style>
<script>
    function formatMsg(cmd, val=null) { 
        document.execCommand(cmd, false, val); 
        syncMsg(); 
        document.getElementById('editorMsg').focus(); 
    }
    function insertMsgLink() {
        let url = prompt("Digite a URL do link (ex: http://www.google.com):", "http://");
        if (url) { document.execCommand('createLink', false, url); syncMsg(); }
    }
    function syncMsg() { 
        let content = document.getElementById('editorMsg');
        let hidden = document.getElementById('msgHidden');
        if (content && hidden) hidden.value = content.innerHTML; 
    }
</script>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col">
        <div class="breadcrumb" id="breadcrumb"><a href="/profile.php">Início</a> > Minhas Mensagens</div>
        
        <div class="card">
            <h1 class="orkut-name" style="font-size:22px; margin-bottom:15px;">
                Caixa de Mensagens
            </h1>

            <div class="msg-tabs" id="msgTabs">
                <a href="?view=inbox" class="msg-tab" id="tabInbox">📥 Entrada</a>
                <a href="?view=outbox" class="msg-tab" id="tabOutbox">📤 Enviadas</a>
                <a href="?view=compose" class="msg-tab" id="tabCompose">✏️ Escrever</a>
            </div>

            <!-- INBOX / OUTBOX -->
            <div id="viewList" style="display:none;">
                <table class="msg-table">
                    <thead id="msgTableHead">
                        <tr>
                            <th style="width:30px;"></th>
                            <th id="colSender">Remetente</th>
                            <th>Assunto</th>
                            <th>Data</th>
                            <th id="colStatus" style="display:none;">Status</th>
                            <th style="text-align:right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="msgTableBody">
                        <tr><td colspan="6" class="msg-empty">Carregando...</td></tr>
                    </tbody>
                </table>
                <div class="pagination-links" id="paginationLinks"></div>
            </div>

            <!-- COMPOSE -->
            <div id="viewCompose" style="display:none;">
                <div class="form-msg">
                    <form method="POST" onsubmit="syncMsg(); return enviarMensagem(event);">
                        <label style='font-weight:bold; color:var(--title); margin-bottom:5px; display:block;'>Para:</label>
                        <input type='hidden' name='receiver_id' id='receiverId' value=''>
                        <input type='text' id='receiverName' value='' disabled style='background:#f4f7fc; color:#666; width:100%; padding:8px; border:1px solid #ccc; margin-bottom:10px; box-sizing:border-box;'>
                        
                        <label style='font-weight:bold; color:var(--title); margin-bottom:5px; display:block;'>Assunto:</label>
                        <input type="text" name="assunto" id="assuntoInput" value="" placeholder="Título da mensagem" maxlength="150" required style="width:100%; padding:8px; border:1px solid #a5bce3; margin-bottom:10px; box-sizing: border-box;">
                        
                        <label style='font-weight:bold; color:var(--title); margin-bottom:5px; display:block;'>Mensagem:</label>
                        <div class="editor-toolbar">
                            <button type="button" onclick="formatMsg('bold')"><b>B</b></button>
                            <button type="button" onclick="formatMsg('italic')"><i>I</i></button>
                            <button type="button" onclick="formatMsg('underline')"><u>U</u></button>
                            <button type="button" onclick="formatMsg('strikeThrough')"><s>S</s></button>
                            <button type="button" onclick="insertMsgLink()">🔗 Link</button>
                            <input type="color" onchange="formatMsg('foreColor', this.value)" style="height:20px; width:25px; padding:0; border:none; cursor:pointer;" title="Cor do texto">
                        </div>
                        <div id="editorMsg" class="editor-area" contenteditable="true" oninput="syncMsg()"></div>
                        <input type="hidden" name="mensagem" id="msgHidden" required>
                        
                        <button type="submit" name="send_message" id="btnEnviar" class="btn-action" style="width:auto; padding:8px 25px; cursor: pointer;">Enviar Mensagem 📤</button>
                    </form>
                </div>
            </div>

            <!-- READ MESSAGE -->
            <div id="viewRead" style="display:none;">
                <div style="margin-bottom:10px;">
                    <a href="javascript:void(0);" onclick="goToView(_lastListView)" style="color:var(--link); font-size:11px;">← Voltar para a lista</a>
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
                    <div id="detailReadStatus" style="margin-top:10px; font-size:11px;"></div>
                    <div style="margin-top:15px; text-align:right;" id="detailActions">
                        <a href="#" id="btnResponder" class="btn-action" style="text-decoration:none; padding:6px 15px; font-size:11px;">💬 Responder</a>
                        <a href="javascript:void(0);" id="btnExcluirMsg" onclick="excluirMsgAtual()" style="color:#cc0000; font-size:11px; margin-left:10px;">🗑️ Apagar</a>
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
let _currentMsgId = null;
let _lastListView = 'inbox';

document.addEventListener('DOMContentLoaded', async () => {
    await loadLayout({ activePage: 'mensagens' });

    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const toUid = urlParams.get('to');
    const msgId = urlParams.get('id');

    if (toUid) {
        showView('compose');
        carregarDestinatario(toUid);
    } else if (view === 'outbox') {
        showView('outbox');
    } else if (view === 'compose') {
        showView('compose');
        habilitarCampoPara();
    } else if (view === 'read' && msgId) {
        abrirMensagem(parseInt(msgId));
    } else {
        showView('inbox');
    }
});

function showView(view) {
    document.getElementById('viewList').style.display = 'none';
    document.getElementById('viewCompose').style.display = 'none';
    document.getElementById('viewRead').style.display = 'none';

    document.getElementById('tabInbox').classList.remove('active');
    document.getElementById('tabOutbox').classList.remove('active');
    document.getElementById('tabCompose').classList.remove('active');

    // Reset breadcrumb
    document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Minhas Mensagens';

    if (view === 'inbox' || view === 'outbox') {
        _currentView = view;
        _lastListView = view;
        document.getElementById('viewList').style.display = 'block';
        document.getElementById('colSender').textContent = view === 'inbox' ? 'Remetente' : 'Para';
        document.getElementById('colStatus').style.display = view === 'outbox' ? '' : 'none';
        if (view === 'inbox') document.getElementById('tabInbox').classList.add('active');
        else document.getElementById('tabOutbox').classList.add('active');
        _currentPage = 1;
        loadMensagens();
    } else if (view === 'compose') {
        document.getElementById('viewCompose').style.display = 'block';
        document.getElementById('tabCompose').classList.add('active');
    } else if (view === 'read') {
        document.getElementById('viewRead').style.display = 'block';
    }
}

function goToView(view) {
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    url.searchParams.delete('id');
    url.searchParams.delete('to');
    window.history.pushState({}, '', url);
    showView(view);
}

// Interceptar cliques nas tabs (sem recarregar página)
document.addEventListener('click', function(e) {
    const tab = e.target.closest('.msg-tab');
    if (tab) {
        e.preventDefault();
        const tabUrl = new URL(tab.href, window.location);
        const view = tabUrl.searchParams.get('view') || 'inbox';
        goToView(view);
        if (view === 'compose' && !document.getElementById('receiverId').value) {
            habilitarCampoPara();
        }
    }
});

// Botão voltar do navegador
window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view') || 'inbox';
    const msgId = urlParams.get('id');
    if (view === 'read' && msgId) {
        abrirMensagem(parseInt(msgId));
    } else {
        showView(view);
    }
});

async function carregarDestinatario(uid) {
    try {
        const resp = await fetch('/api/user/' + uid);
        const data = await resp.json();
        if (data.success) {
            document.getElementById('receiverId').value = data.user.id;
            document.getElementById('receiverName').value = data.user.nome;
            document.getElementById('receiverName').disabled = true;
            document.getElementById('receiverName').style.background = '#f4f7fc';
            document.getElementById('receiverName').style.color = '#666';
            document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > <a href="/profile.php?uid=' + data.user.id + '">' + escapeHtml(data.user.nome) + '</a> > Enviar Mensagem';
            document.title = 'Yorkut - Mensagem para ' + data.user.nome;
        } else {
            document.getElementById('receiverName').value = 'Usuário não encontrado';
        }
    } catch(e) {
        document.getElementById('receiverName').value = 'Erro ao buscar usuário';
    }
}

function habilitarCampoPara() {
    const campo = document.getElementById('receiverName');
    campo.disabled = false;
    campo.placeholder = 'Digite o nome do amigo...';
    campo.value = '';
    campo.style.background = '#fff';
    campo.style.color = '#333';
    document.getElementById('receiverId').value = '';
}

async function loadMensagens() {
    const tbody = document.getElementById('msgTableBody');
    const colSpan = _currentView === 'outbox' ? 6 : 5;
    tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="msg-empty">Carregando...</td></tr>';

    const url = _currentView === 'outbox'
        ? '/api/mensagens-enviadas?page=' + _currentPage + '&limit=' + _currentLimit
        : '/api/mensagens?page=' + _currentPage + '&limit=' + _currentLimit;

    try {
        const resp = await fetch(url);
        const data = await resp.json();

        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="msg-empty">Erro ao carregar.</td></tr>';
            return;
        }

        if (data.mensagens.length === 0) {
            const msg = _currentView === 'inbox' 
                ? 'Nenhuma mensagem na caixa de entrada.' 
                : 'Nenhuma mensagem enviada.';
            tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="msg-empty">' + msg + '</td></tr>';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        data.mensagens.forEach(function(m) {
            const isUnread = _currentView === 'inbox' && !m.lida;
            const nome = _currentView === 'inbox' ? m.remetente_nome : m.destinatario_nome;
            const uid = _currentView === 'inbox' ? m.remetente_id : m.destinatario_id;
            const icon = _currentView === 'outbox' ? '📤' : (isUnread ? '📬' : '📭');

            html += '<tr class="msg-row' + (isUnread ? ' unread' : '') + '" onclick="abrirMensagem(' + m.id + ')">';
            html += '<td class="msg-icon">' + icon + '</td>';
            html += '<td><a href="/profile.php?uid=' + uid + '" style="color:var(--link);" onclick="event.stopPropagation();">' + escapeHtml(nome) + '</a></td>';
            html += '<td><span class="msg-link" style="color:var(--link);">' + escapeHtml(m.assunto) + '</span></td>';
            html += '<td style="color:#666; font-size:10px;">' + formatTime(m.criado_em) + '</td>';
            if (_currentView === 'outbox') {
                html += '<td>';
                if (m.lida) {
                    html += '<span class="read-status status-lido" style="font-size:10px; color:#2e7d32;">✅ Lido</span>';
                } else {
                    html += '<span class="read-status status-naolido" style="font-size:10px; color:#cc6600;">⏳ Não lido</span>';
                }
                html += '</td>';
            }
            html += '<td style="text-align:right;">';
            html += '<button onclick="event.stopPropagation(); excluirMensagem(' + m.id + ')" style="background:none;border:none;color:#cc0000;cursor:pointer;font-size:16px;" title="Apagar">🗑️</button>';
            html += '</td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;

        renderPagination(data.page, data.totalPages);
    } catch(err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="msg-empty">Erro de conexão.</td></tr>';
    }
}

async function abrirMensagem(id) {
    _currentMsgId = id;

    const url = new URL(window.location);
    url.searchParams.set('view', 'read');
    url.searchParams.set('id', id);
    url.searchParams.delete('to');
    window.history.pushState({}, '', url);

    showView('read');

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
            document.getElementById('btnResponder').href = '?to=' + m.remetente_id;
            document.getElementById('btnResponder').style.display = '';
            // Limpar status de leitura para mensagens recebidas
            const statusEl2 = document.getElementById('detailReadStatus');
            if (statusEl2) statusEl2.innerHTML = '';
        } else {
            document.getElementById('detailSenderLabel').textContent = 'Para:';
            document.getElementById('detailSender').textContent = m.destinatario_nome;
            document.getElementById('detailSender').href = '/profile.php?uid=' + m.destinatario_id;
            document.getElementById('detailPic').src = m.destinatario_foto || getDefaultAvatar(m.destinatario_sexo);
            document.getElementById('btnResponder').style.display = 'none';
            // Mostrar status de leitura na visualização de mensagem enviada
            const statusEl = document.getElementById('detailReadStatus');
            if (statusEl) statusEl.innerHTML = m.lida ? '<span style="color:#2e7d32;">✅ Mensagem lida pelo destinatário</span>' : '<span style="color:#999;">⏳ Mensagem ainda não lida</span>';
        }

        document.getElementById('detailAssunto').textContent = m.assunto;
        document.getElementById('detailDate').textContent = formatTime(m.criado_em);
        document.getElementById('detailBody').innerHTML = m.mensagem;
        document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > <a href="javascript:void(0);" onclick="goToView(\'inbox\')">Mensagens</a> > ' + escapeHtml(m.assunto);
    } catch(err) {
        document.getElementById('detailBody').textContent = 'Erro de conexão.';
    }
}

async function excluirMensagem(id) {
    showConfirm('Apagar mensagem?', async function() {
        try {
            const resp = await fetch('/api/mensagens/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [id], tipo: _currentView === 'outbox' ? 'outbox' : 'inbox' })
            });
            const data = await resp.json();
            if (data.success) { loadMensagens(); }
            else { alert(data.message || 'Erro ao excluir.'); }
        } catch(err) { alert('Erro de conexão.'); }
    });
}

async function excluirMsgAtual() {
    if (!_currentMsgId) return;
    showConfirm('Apagar esta mensagem?', async function() {
        try {
            const resp = await fetch('/api/mensagens/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [_currentMsgId], tipo: _lastListView === 'outbox' ? 'outbox' : 'inbox' })
            });
            const data = await resp.json();
            if (data.success) { goToView(_lastListView); }
            else { alert(data.message || 'Erro ao excluir.'); }
        } catch(err) { alert('Erro de conexão.'); }
    });
}

async function enviarMensagem(e) {
    e.preventDefault();
    const receiverId = document.getElementById('receiverId').value;
    if (!receiverId) { alert('Selecione um destinatário!'); return false; }
    const assunto = document.getElementById('assuntoInput').value.trim();
    if (!assunto) { alert('Digite um assunto!'); return false; }
    syncMsg();
    const mensagem = document.getElementById('msgHidden').value;
    if (!mensagem || mensagem.trim() === '') { alert('Digite uma mensagem!'); return false; }

    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    try {
        const resp = await fetch('/api/mensagens', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: receiverId, assunto: assunto, mensagem: mensagem })
        });
        const data = await resp.json();
        if (data.success) {
            btn.textContent = 'Mensagem Enviada! ✅';
            btn.style.background = '#4CAF50';
            btn.style.borderColor = '#388E3C';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'Enviar Mensagem 📤';
                btn.style.background = '';
                btn.style.borderColor = '';
                document.getElementById('editorMsg').innerHTML = '';
                document.getElementById('msgHidden').value = '';
                document.getElementById('assuntoInput').value = '';
            }, 2000);
        } else {
            alert(data.message || 'Erro ao enviar mensagem.');
            btn.disabled = false;
            btn.textContent = 'Enviar Mensagem 📤';
        }
    } catch(err) {
        alert('Erro de conexão.');
        btn.disabled = false;
        btn.textContent = 'Enviar Mensagem 📤';
    }
    return false;
}

function renderPagination(current, total) {
    const el = document.getElementById('paginationLinks');
    if (total <= 1) { el.innerHTML = ''; return; }
    let html = '';
    if (current > 1) html += '<a onclick="changePage(' + (current - 1) + ')">« anterior</a> ';
    for (let i = 1; i <= total; i++) {
        if (i === current) html += '<strong>' + i + '</strong> ';
        else html += '<a onclick="changePage(' + i + ')">' + i + '</a> ';
    }
    if (current < total) html += '<a onclick="changePage(' + (current + 1) + ')">próxima »</a>';
    el.innerHTML = html;
}

function changePage(p) { _currentPage = p; loadMensagens(); }

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    try {
        // O banco já armazena em UTC-3 (Brasília). Parsear manualmente para não aplicar fuso do navegador.
        const parts = dateStr.match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
        if (!parts) return dateStr;
        const dd = parts[3];
        const mm = parts[2];
        const yy = parts[1];
        const hh = parts[4];
        const mi = parts[5];
        return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
    } catch(e) { return dateStr; }
}
</script>
</body>
</html>
