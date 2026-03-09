<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Enquetes</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<style>
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

    .left-col { width: 200px; flex-shrink: 0; position: sticky; top: 15px; max-height: calc(100vh - 30px); overflow-y: auto; }
    .center-col { flex: 1; min-width: 0; }

    .poll-opt-container { display:flex; align-items:center; gap:15px; margin-bottom:10px; border:1px solid #e4ebf5; padding:8px; border-radius:4px; background:#fcfcfc;}
    .poll-opt-img { width:40px; height:40px; border-radius:4px; object-fit:cover; border:1px solid #ccc; flex-shrink:0; }
    .poll-opt-text { flex:1; font-weight:bold; color:var(--title); min-width:0; }
    .poll-opt-check { width:20px; flex-shrink:0; text-align:center; }

    .opt-row { display:flex; gap:10px; margin-bottom:5px; align-items:center;}

    .btn-share { background: #fff; border: 1px solid #ccc; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; font-weight:bold;}
    .btn-share:hover { background: #f0f0f0; }

    /* Poll card styles */
    .poll-card { border: 1px solid #e4ebf5; border-radius: 6px; padding: 15px; margin-bottom: 15px; background: #fff; }
    .poll-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .poll-title { font-size: 14px; font-weight: bold; color: var(--title); margin: 0; }
    .poll-meta { font-size: 10px; color: #999; margin-top: 3px; }
    .poll-actions { display: flex; gap: 5px; }

    .poll-option { display: flex; align-items: center; gap: 10px; padding: 8px; margin-bottom: 5px; border: 1px solid #e4ebf5; border-radius: 4px; cursor: pointer; transition: 0.2s; position: relative; overflow: hidden; }
    .poll-option:hover { border-color: #a5bce3; background: #f9fbfc; }
    .poll-option.voted { border-color: #4caf50; background: #e8f5e9; }
    .poll-option.disabled { cursor: default; }
    .poll-option.disabled:hover { border-color: #e4ebf5; background: #fff; }

    .poll-option-bar { position: absolute; left: 0; top: 0; bottom: 0; background: #e8eef7; z-index: 0; transition: width 0.5s ease; }
    .poll-option-content { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; width: 100%; }

    .poll-option input[type="radio"] { flex-shrink: 0; }
    .poll-option-label { flex: 1; font-size: 12px; font-weight: bold; color: var(--title); }
    .poll-option-percent { font-size: 11px; font-weight: bold; color: #666; white-space: nowrap; }
    .poll-option-votes { font-size: 10px; color: #999; }

    .poll-total { font-size: 10px; color: #999; text-align: right; margin-top: 8px; }
    .poll-status { display: inline-block; font-size: 9px; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
    .poll-status.active { background: #e8f5e9; color: #2e7d32; }
    .poll-status.ended { background: #ffebee; color: #cc0000; }
    .poll-status.scheduled { background: #fff3e0; color: #e65100; }

    .poll-dates { font-size: 10px; color: #888; margin-top: 5px; }

    .msg-box { padding: 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-align: center; margin-bottom: 15px; }
    .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .msg-error { background: #ffebee; color: #cc0000; border: 1px solid #ffcdd2; }

    @media (max-width: 768px) {
        .poll-header { flex-direction: column; gap: 8px; }
    }

    /* Mention dropdown */
    .mention-dropdown { display: none; position: absolute; background: #fff; border: 1px solid #a5bce3; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 150px; overflow-y: auto; width: 220px; z-index: 1000; border-radius: 4px; }
    .mention-item { display: flex; align-items: center; padding: 6px 10px; cursor: pointer; border-bottom: 1px dotted #e4ebf5; font-size: 11px; color: var(--link); font-weight:bold;}
    .mention-item:hover { background: #eef4ff; }
    .mention-item img { width: 24px; height: 24px; border-radius: 3px; object-fit: cover; margin-right: 8px; border: 1px solid #ccc; }
    .mention-tag { color: #3b5998; font-weight: bold; background: #eef4ff; padding: 1px 3px; border-radius: 3px; }

    .poll-bar-bg { height:12px; background:#e4ebf5; border-radius:3px; overflow:hidden; display:inline-block; vertical-align:middle; }
    .poll-bar-fill { height:100%; background:var(--orkut-blue, #6d84b4); border-radius:3px; transition:width 0.5s ease; }

    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
    }
</style>
<script>
    function toggleForm(id) {
        var e = document.getElementById(id);
        if (e) e.style.display = (e.style.display === 'block') ? 'none' : 'block';
    }
    function addOptionField() {
        const container = document.getElementById('options-container');
        const num = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'opt-row';
        div.innerHTML = '<input type="text" class="poll-opt-input" placeholder="Opção ' + num + '" style="flex:1; padding:6px; border:1px solid #ccc;">';
        container.appendChild(div);
    }
    function checkDateToggle() {
        let endDate = document.getElementById('endDateField').value;
        let showVotersBox = document.getElementById('showVotersBox');
        if (endDate !== '') {
            showVotersBox.style.display = 'flex';
        } else {
            showVotersBox.style.display = 'none';
        }
    }
    function copiarLink(url) {
        let tempInput = document.createElement("input");
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        alert("🔗 Link copiado para a área de transferência!");
    }
</script>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div class="left-col" id="left-col">
        <div style="text-align:center; padding:20px; color:#999;">Carregando...</div>
    </div>
    <div class="center-col" id="center-col">
        <div class="breadcrumb" id="breadcrumb">
            <a href="profile.php">Início</a> &gt; Carregando...
        </div>
        <div class="card" id="main-card">
            <div style="text-align:center; padding:40px; color:#999;">Carregando...</div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<div id="mentionDropdown" class="mention-dropdown"></div>

<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
function getQueryParam(p) {
    var s = new URLSearchParams(window.location.search);
    return s.get(p);
}
function escapeHtml(t) {
    if (!t) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(t));
    return d.innerHTML;
}
function getFotoComm(f) {
    if (!f) return '/semfotocomunidade.jpg';
    if (f.startsWith('http') || f.startsWith('/')) return f;
    return '/uploads/comunidades/' + f;
}
function getFotoUser(f) {
    if (!f) return '/perfilsemfoto.jpg';
    if (f.startsWith('http') || f.startsWith('/')) return f;
    return '/uploads/profiles/' + f;
}
function formatDate(d) {
    if (!d) return '';
    var dt = new Date(d);
    var dd = String(dt.getDate()).padStart(2,'0');
    var mm = String(dt.getMonth()+1).padStart(2,'0');
    var yy = dt.getFullYear();
    var hh = String(dt.getHours()).padStart(2,'0');
    var mi = String(dt.getMinutes()).padStart(2,'0');
    return dd+'/'+mm+'/'+yy+' '+hh+':'+mi;
}

var commId = getQueryParam('id');
var pollId = getQueryParam('pid');
var commData = null;
var _isOwnerEnq = false;

async function loadPage() {
    await loadLayout({ activePage: 'comunidades' });
    if (!commId) {
        document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">Comunidade não especificada.</p>';
        return;
    }

    if (pollId) {
        await loadPollDetail();
    } else {
        await loadPollList();
    }
}

async function loadPollList() {
    try {
        var resp = await fetch('/api/enquetes/' + commId);
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">' + escapeHtml(data.message) + '</p>';
            return;
        }
        commData = data.community;
        _isOwnerEnq = !!data.isOwner;
        document.getElementById('breadcrumb').innerHTML =
            '<a href="profile.php">Início</a> &gt; <a href="comunidades.php?id=' + commData.id + '">' + escapeHtml(commData.nome) + '</a> &gt; Enquetes';
        buildLeftCol(commData, data.membrosCount);
        buildMainContent(data.enquetes, commData);
    } catch(err) {
        console.error(err);
        document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">Erro ao carregar enquetes.</p>';
    }
}

async function loadPollDetail() {
    try {
        var resp = await fetch('/api/enquetes/' + commId + '/' + pollId);
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">' + escapeHtml(data.message) + '</p>';
            return;
        }
        commData = data.community;
        _isOwnerEnq = !!data.isOwner;
        document.getElementById('breadcrumb').innerHTML =
            '<a href="profile.php">Início</a> &gt; <a href="comunidades.php?id=' + commData.id + '">' + escapeHtml(commData.nome) + '</a> &gt; <a href="enquetes.php?id=' + commData.id + '">Enquetes</a>';
        buildLeftCol(commData, data.membrosCount);
        buildPollDetailView(data.enquete, commData);
    } catch(err) {
        console.error(err);
        document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">Erro ao carregar enquete.</p>';
    }
}

function buildLeftCol(comm, membrosCount) {
    renderLeftCol(comm, membrosCount || '?');
}

function renderLeftCol(comm, membrosCount) {
    var html = '';
    html += '<div class="card-left">';
    html += '  <div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;">';
    html += '    <img src="' + getFotoComm(comm.foto) + '">';
    html += '  </div>';
    html += '  <div style="text-align:center; font-size:11px; margin-bottom:15px;">';
    html += '    <strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(comm.nome) + '</strong>';
    html += '    membros: ' + membrosCount;
    html += '  </div>';
    html += '  <ul class="menu-left hide-on-mobile" style="margin-top:0;">';
    html += '    <li><a href="comunidades.php?id=' + comm.id + '"><span>🏠</span> comunidade</a></li>';
    html += '    <li><a href="comunidade_convidar_amigo.php?id=' + comm.id + '"><span>📱</span> convidar amigos</a></li>';
    html += '    <li><a href="/forum.php?id=' + comm.id + '"><span>💬</span> fórum</a></li>';
    html += '    <li class="active"><a href="enquetes.php?id=' + comm.id + '"><span>📊</span> enquetes</a></li>';
    html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=membros"><span>👥</span> membros</a></li>';
    html += '    <li><a href="comunidades_staff.php?id=' + comm.id + '"><span>👑</span> staff</a></li>';
    html += '    <li><a href="sorteio.php?id=' + comm.id + '"><span>🎁</span> sorteios</a></li>';
    if (_isOwnerEnq) {
        html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=config"><span>⚙️</span> configurações</a></li>';
    }
    html += '  </ul>';
    html += '</div>';
    document.getElementById('left-col').innerHTML = html;
}

// ===== LIST VIEW =====
function getPollStatusInfo(enq) {
    var now = new Date();
    if (enq.data_inicio) {
        var inicio = new Date(enq.data_inicio);
        if (now < inicio) return { label: 'Agendada', color: '#e65100' };
    }
    if (enq.data_fim) {
        var fim = new Date(enq.data_fim);
        if (now > fim) return { label: 'Encerrada', color: '#cc0000' };
    }
    return { label: 'Ativa', color: 'green' };
}

function buildMainContent(enquetes, comm) {
    var html = '';

    // Toolbar
    html += '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:15px;">';
    html += '  <h1 class="orkut-name" style="font-size:20px; margin:0;">Enquetes da Comunidade</h1>';
    html += '  <div style="display:flex; gap:10px;">';
    html += '    <a class="btn-share" onclick="copiarLink(\'' + window.location.origin + '/enquetes.php?id=' + comm.id + '\')">🔗 Copiar URL Enquetes</a>';
    html += '    <button class="btn-action" style="padding:4px 10px;" onclick="toggleForm(\'newPoll\')">Criar Enquete</button>';
    html += '  </div>';
    html += '</div>';

    // New poll form
    html += '<div id="newPoll" style="display:none; background:#f4f7fc; padding:15px; border:1px solid var(--line); border-radius:4px; margin-bottom:15px;">';
    html += '  <div style="position:relative;">';
    html += '    <input type="text" id="pollTitle" placeholder="Qual a sua pergunta? (Use @ para marcar amigos)" required style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; font-weight:bold; font-size:14px; box-sizing:border-box;">';
    html += '  </div>';
    html += '  <div style="margin-bottom:15px;">';
    html += '    <label style="font-size:10px; font-weight:bold; color:#666;">Término (opcional):</label>';
    html += '    <input type="datetime-local" id="endDateField" style="width:100%; padding:6px; border:1px solid #ccc;" onchange="checkDateToggle()">';
    html += '    <input type="hidden" id="startDateField" value="">';
    html += '  </div>';
    html += '  <label id="showVotersBox" style="font-size:11px; font-weight:bold; color:var(--title); display:none; align-items:center; gap:5px; margin-bottom:15px; background:#fffdf5; border:1px solid #f2e08c; padding:10px; border-radius:4px;">';
    html += '    <input type="checkbox" id="showVotersCheck" value="1"> Mostrar relatório detalhado de quem votou APÓS O TÉRMINO da enquete';
    html += '  </label>';
    html += '  <b style="color:var(--title); display:block; margin-bottom:5px;">Opções de Voto:</b>';
    html += '  <div id="options-container">';
    html += '    <div class="opt-row"><input type="text" class="poll-opt-input" placeholder="Opção 1" required style="flex:1; padding:6px; border:1px solid #ccc;"></div>';
    html += '    <div class="opt-row"><input type="text" class="poll-opt-input" placeholder="Opção 2" required style="flex:1; padding:6px; border:1px solid #ccc;"></div>';
    html += '  </div>';
    html += '  <button type="button" class="btn-action" style="font-size:10px; padding:3px 8px; margin-bottom:15px;" onclick="addOptionField()">+ Adicionar opção</button>';
    html += '  <div style="text-align:right;">';
    html += '    <button type="button" class="btn-action" style="background:#fff; color:#666;" onclick="toggleForm(\'newPoll\')">Cancelar</button>';
    html += '    <button type="button" class="btn-action" onclick="criarEnquete()">Lançar Enquete</button>';
    html += '  </div>';
    html += '</div>';

    // Message box
    html += '<div id="msg-box" style="display:none;"></div>';

    // Enquetes list - simple cards like original
    if (enquetes.length === 0) {
        html += '<div style="text-align:center;color:#999;padding:20px;">Nenhuma enquete criada ainda.</div>';
    } else {
        for (var i = 0; i < enquetes.length; i++) {
            html += renderPollListItem(enquetes[i]);
        }
    }

    document.getElementById('main-card').innerHTML = html;
}

function renderPollListItem(enq) {
    var status = getPollStatusInfo(enq);
    var html = '<div class="poll-card" style="position:relative;">';
    html += '  <a href="enquetes.php?id=' + commId + '&pid=' + enq.id + '" style="text-decoration:none; display:block;">';
    html += '    <div class="poll-title">📊 ' + escapeHtml(enq.titulo) + '</div>';
    html += '    <div style="font-size:11px; color:#666; margin-top:5px;">';
    html += '      Total de votos: <b>' + enq.total_votos + '</b> | Criada por ' + escapeHtml(enq.criador_nome) + '<br>';
    html += '      <span style="color:' + status.color + '; font-weight:bold;">● ' + status.label + '</span>';
    html += '    </div>';
    html += '  </a>';
    if (enq.is_owner) {
        html += '  <div style="position:absolute; top:15px; right:15px;">';
        html += '    <button onclick="excluirEnquete(' + enq.id + ')" style="background:none; border:none; color:#cc0000; cursor:pointer; font-size:10px; text-decoration:underline;">[apagar]</button>';
        html += '  </div>';
    }
    html += '</div>';
    return html;
}

// ===== DETAIL VIEW =====
var PIE_COLORS = ['#ff6b6b','#4ecdc4','#45b7d1','#96ceb4','#ffeaa7','#dda0dd','#98d8c8','#f7dc6f','#bb8fce','#85c1e9','#f1948a','#82e0aa'];

function buildPollDetailView(enq, comm) {
    var status = getPollStatusInfo(enq);
    var jaVotou = enq.meu_voto !== null;
    var isEncerrada = status.label === 'Encerrada';
    var canVote = status.label === 'Ativa' && !jaVotou;

    var html = '';
    html += '<div id="printArea" class="poll-card" style="border:none; padding:0; background:none;">';

    // Header
    html += '  <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:5px; margin-bottom:10px;">';
    html += '    <h1 class="orkut-name" style="font-size:20px; margin:0;">' + escapeHtml(enq.titulo) + '</h1>';
    html += '    <div style="display:flex; gap:10px;">';
    html += '      <a class="btn-share no-print" onclick="copiarLink(\'' + window.location.origin + '/enquetes.php?id=' + comm.id + '&pid=' + enq.id + '\')">🔗 Compartilhar</a>';
    html += '      <a href="enquetes.php?id=' + comm.id + '" class="no-print" style="font-size:11px; font-weight:bold;">🔙 Voltar</a>';
    html += '    </div>';
    html += '  </div>';

    // Encerrada banner
    if (isEncerrada && enq.data_fim) {
        html += '<div style="background:#fff8c3; border:1px solid #f2e08c; padding:10px; text-align:center; font-weight:bold; margin-bottom:15px; border-radius:4px; color:#cc6600;">';
        html += '⏳ Enquete encerrada em ' + formatDate(enq.data_fim);
        html += '</div>';
    }

    // Poll info (only show for non-encerrada or if no banner)
    if (!isEncerrada) {
        html += '  <div style="font-size:11px; color:#666; margin-bottom:15px;">';
        html += '    Criada por <b>' + escapeHtml(enq.criador_nome) + '</b> — ' + formatDate(enq.criado_em);
        html += '    <span style="color:' + status.color + '; font-weight:bold; margin-left:10px;">● ' + status.label + '</span>';
        if (enq.data_fim) {
            html += '<br>';
            html += '⏰ Enquete termina em: ' + formatDate(enq.data_fim);
        }
        html += '  </div>';
    }

    // Options
    for (var j = 0; j < enq.opcoes.length; j++) {
        var op = enq.opcoes[j];
        var isMyVote = enq.meu_voto === op.id;
        html += '  <div class="poll-opt-container"' + (isMyVote ? ' style="border-color:#4caf50; background:#e8f5e9;"' : '') + '>';
        if (canVote) {
            html += '    <div class="poll-opt-check"><input type="radio" name="option_id" value="' + op.id + '" class="poll-radio" required></div>';
        } else if (jaVotou && !isEncerrada) {
            html += '    <div class="poll-opt-check">' + (isMyVote ? '<span style="color:#4caf50; font-weight:bold;">✔</span>' : '') + '</div>';
        }
        if (op.foto) {
            html += '    <img class="poll-opt-img" src="' + escapeHtml(op.foto) + '">';
        }
        html += '    <div class="poll-opt-text">' + escapeHtml(op.texto) + '</div>';
        html += '    <div class="poll-bar-bg" style="width:200px;"><div class="poll-bar-fill" style="width:' + op.percentual + '%;"></div></div>';
        html += '    <div style="width:70px; text-align:right; font-size:10px; color:#666; font-weight:bold;">' + op.percentual + '% (' + op.votos + ')</div>';
        html += '  </div>';
    }

    // Total / footer message
    if (isEncerrada) {
        html += '<div style="margin-top:20px; text-align:right;">';
        html += '  <span style="font-size:11px; color:#888; font-style:italic;">Apenas membros votam no prazo correto. Total: ' + enq.total_votos + ' voto(s).</span>';
        html += '</div>';
    } else {
        html += '  <div style="font-size:11px; color:#666; margin-top:10px;">Total de votos: <b>' + enq.total_votos + '</b></div>';
    }

    // Vote button
    if (canVote) {
        html += '  <div style="margin-top:20px; text-align:right;" class="no-print">';
        html += '    <button class="btn-action" style="padding:8px 25px; font-size:14px;" onclick="votarDetail()">Confirmar Voto</button>';
        html += '  </div>';
    } else if (jaVotou && !isEncerrada) {
        html += '  <div style="margin-top:10px; font-size:11px; color:#4caf50; font-weight:bold;">✔ Você já votou nesta enquete.</div>';
    }

    // Delete button for owner
    if (enq.is_owner) {
        html += '  <div style="margin-top:15px; text-align:right;" class="no-print">';
        html += '    <button onclick="excluirEnquete(' + enq.id + ')" style="background:none; border:none; color:#cc0000; cursor:pointer; font-size:10px; text-decoration:underline;">[apagar enquete]</button>';
        html += '  </div>';
    }

    html += '</div>'; // close poll-card

    // ===== Relatório Detalhado (sempre para encerrada) =====
    if (isEncerrada) {
        var opcoes = enq.opcoes;
        var totalVotos = enq.total_votos;

        html += '<div style="margin-top:40px; border-top:2px dashed var(--line); padding-top:20px;">';

        // Header
        html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">';
        html += '  <h3 style="margin:0; color:var(--title);">Relatório Detalhado de Votos</h3>';
        html += '  <button class="btn-action no-print" onclick="window.print()">🖨️ Imprimir</button>';
        html += '</div>';

        // Pie chart + result table
        html += '<div style="display:flex; gap:25px; align-items:center; margin-bottom:25px; background:#fdfdfd; border:1px solid #e4ebf5; padding:20px; border-radius:6px;">';

        // Build conic-gradient
        var gradientParts = [];
        var accum = 0;
        for (var i = 0; i < opcoes.length; i++) {
            var pct = totalVotos > 0 ? (opcoes[i].votos / totalVotos) * 100 : 0;
            var color = PIE_COLORS[i % PIE_COLORS.length];
            gradientParts.push(color + ' ' + accum + '% ' + (accum + pct) + '%');
            accum += pct;
        }
        var gradient = gradientParts.length > 0 ? gradientParts.join(', ') : '#e4ebf5 0% 100%';
        if (totalVotos === 0) gradient = '#e4ebf5 0% 100%';

        html += '<div style="width:140px; height:140px; border-radius:50%; background:conic-gradient(' + gradient + '); box-shadow:0 4px 10px rgba(0,0,0,0.15); flex-shrink:0; border:3px solid #fff;"></div>';

        // Result table
        html += '<div style="flex:1;">';
        html += '<b style="font-size:13px; color:var(--title); display:block; border-bottom:1px solid #e4ebf5; padding-bottom:5px; margin-bottom:8px;">Resultado Geral (Total: ' + totalVotos + ' votos)</b>';
        html += '<table style="width:100%; border-collapse:collapse; font-size:12px;">';
        for (var i = 0; i < opcoes.length; i++) {
            var op = opcoes[i];
            var color = PIE_COLORS[i % PIE_COLORS.length];
            html += '<tr>';
            html += '<td style="padding:6px 0; border-bottom:1px dotted #e4ebf5;">';
            html += '  <span style="display:inline-block; width:12px; height:12px; background:' + color + '; margin-right:5px; border-radius:3px; vertical-align:middle; border:1px solid rgba(0,0,0,0.1);"></span>';
            html += '  <b>' + escapeHtml(op.texto) + '</b>';
            html += '</td>';
            html += '<td style="padding:6px 0; border-bottom:1px dotted #e4ebf5; text-align:right; color:#444;">' + op.votos + ' votos</td>';
            html += '<td style="padding:6px 0; border-bottom:1px dotted #e4ebf5; text-align:right; font-weight:bold; color:#222; width:50px;">' + op.percentual + '%</td>';
            html += '</tr>';
        }
        html += '</table>';
        html += '</div>';
        html += '</div>'; // close pie+table flex

        // Voter list placeholder (always for ended polls)
        html += '<div id="votantes-container"><p style="font-size:11px; color:#999;">Carregando lista de eleitores...</p></div>';

        html += '</div>'; // close report section
    }

    document.getElementById('main-card').innerHTML = html;

    // Load voter list async if encerrada
    if (isEncerrada) {
        loadVotantesList(enq);
    }
}

async function loadVotantesList(enq) {
    var container = document.getElementById('votantes-container');
    if (!container) return;

    try {
        var resp = await fetch('/api/enquetes/' + commId + '/' + enq.id + '/votantes');
        var data = await resp.json();

        if (!data.success) {
            container.innerHTML = '<p style="font-size:11px; color:#cc0000;">' + (data.message || 'Lista não disponível.') + '</p>';
            return;
        }

        var votantes = data.votantes || [];
        var html = '';

        html += '<b style="font-size:12px; color:var(--title); display:block; margin-bottom:8px;">Lista Nominal de Eleitores:</b>';
        html += '<table style="width:100%; border-collapse:collapse; font-size:11px; text-align:left;">';
        html += '<tr style="background:#e8eef7;">';
        html += '  <th style="padding:8px; border:1px solid #c0d0e6;">Membro</th>';
        html += '  <th style="padding:8px; border:1px solid #c0d0e6;">Votou Em</th>';
        html += '  <th style="padding:8px; border:1px solid #c0d0e6;">Data/Hora do Voto</th>';
        html += '</tr>';

        if (votantes.length === 0) {
            html += '<tr><td colspan="3" style="padding:10px; text-align:center; color:#999;">Nenhum voto registrado.</td></tr>';
        } else {
            for (var i = 0; i < votantes.length; i++) {
                var v = votantes[i];
                html += '<tr>';
                html += '<td style="padding:6px 8px; border-bottom:1px dotted #ccc;"><a href="profile.php?id=' + v.user_id + '" style="color:var(--link); text-decoration:none; font-weight:bold;">' + escapeHtml(v.nome) + '</a></td>';
                html += '<td style="padding:6px 8px; border-bottom:1px dotted #ccc; font-weight:bold; color:var(--link);">' + escapeHtml(v.opcao_texto) + '</td>';
                html += '<td style="padding:6px 8px; border-bottom:1px dotted #ccc; color:#888;">' + (v.votado_em ? formatDate(v.votado_em) : '—') + '</td>';
                html += '</tr>';
            }
        }
        html += '</table>';

        container.innerHTML = html;
    } catch(err) {
        console.error('Erro ao carregar lista de votantes:', err);
        container.innerHTML = '<p style="font-size:11px; color:#cc0000;">Erro ao carregar lista de votantes.</p>';
    }
}

// ===== ACTIONS =====
async function criarEnquete() {
    var titulo = document.getElementById('pollTitle').value.trim();
    var startDate = document.getElementById('startDateField').value;
    var endDate = document.getElementById('endDateField').value;
    var showVoters = document.getElementById('showVotersCheck') ? document.getElementById('showVotersCheck').checked : false;

    var inputs = document.querySelectorAll('#options-container .poll-opt-input');
    var opcoes = [];
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].value.trim()) opcoes.push(inputs[i].value.trim());
    }

    if (!titulo) { showMsg('Escreva uma pergunta para a enquete.', 'error'); return; }
    if (opcoes.length < 2) { showMsg('Adicione pelo menos 2 opções.', 'error'); return; }

    try {
        var resp = await fetch('/api/enquetes/' + commId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                titulo: titulo,
                opcoes: opcoes,
                data_inicio: startDate || null,
                data_fim: endDate || null,
                mostrar_votantes: showVoters
            })
        });
        var data = await resp.json();
        if (data.success) {
            showMsg('Enquete criada com sucesso!', 'success');
            setTimeout(function(){ location.reload(); }, 800);
        } else {
            showMsg(data.message || 'Erro ao criar enquete.', 'error');
        }
    } catch(err) {
        showMsg('Erro de conexão.', 'error');
    }
}

function votarDetail() {
    var selected = document.querySelector('input[name="option_id"]:checked');
    if (!selected) {
        alert('Selecione uma opção antes de votar.');
        return;
    }
    var opcaoId = selected.value;
    showConfirm('Atenção: Seu voto é único e não poderá ser alterado depois. Tem certeza que deseja confirmar esta opção?', async function() {
        try {
            var resp = await fetch('/api/enquetes/' + commId + '/' + pollId + '/votar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ opcao_id: parseInt(opcaoId) })
            });
            var data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao votar.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    }, { title: 'Confirmar Voto', yesText: 'Sim, votar', noText: 'Cancelar' });
}

function excluirEnquete(enqueteId) {
    showConfirm('Deseja realmente DELETAR esta enquete?', async function() {
        try {
            var resp = await fetch('/api/enquetes/' + commId + '/' + enqueteId + '/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            var data = await resp.json();
            if (data.success) {
                if (pollId) {
                    window.location.href = 'enquetes.php?id=' + commId;
                } else {
                    location.reload();
                }
            } else {
                alert(data.message || 'Erro ao excluir.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    }, { title: 'Excluir Enquete', yesText: 'Sim, excluir', noText: 'Cancelar' });
}

function showMsg(text, type) {
    var box = document.getElementById('msg-box');
    if (!box) return;
    box.className = 'msg-box msg-' + type;
    box.textContent = text;
    box.style.display = 'block';
    setTimeout(function() { box.style.display = 'none'; }, 4000);
}

loadPage();

// --- Sistema de Menções (@) no Título da Enquete ---
var mentionDropdown = document.getElementById('mentionDropdown');
var listaAmigos = [];
var mentionActiveInput = null;

async function carregarAmigosParaMencao() {
    try {
        var meResp = await fetch('/api/me');
        var meData = await meResp.json();
        if (!meData.success) return;
        var resp = await fetch('/api/amigos/' + meData.user.id);
        var data = await resp.json();
        if (data.success && data.amigos) {
            listaAmigos = data.amigos.map(function(a) {
                var tag = a.nome.replace(/\s+/g, '_');
                var foto = a.foto_perfil || '/perfilsemfoto.jpg';
                if (!foto.startsWith('/') && !foto.startsWith('http')) foto = '/uploads/profiles/' + foto;
                return { tag: tag, nome: a.nome, foto: foto };
            });
        }
    } catch(e) { console.error('Erro ao carregar amigos:', e); }
}
carregarAmigosParaMencao();

document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'pollTitle') {
        var input = e.target;
        mentionActiveInput = input;
        var cursorPos = input.selectionStart;
        var textBefore = input.value.substring(0, cursorPos);
        var match = textBefore.match(/@(\w*)$/);

        if (match) {
            var searchStr = match[1].toLowerCase();
            var filtrados = listaAmigos.filter(function(a) { return a.tag.toLowerCase().startsWith(searchStr) || a.nome.toLowerCase().startsWith(searchStr); });

            if (filtrados.length > 0) {
                mentionDropdown.innerHTML = '';
                filtrados.forEach(function(amigo) {
                    var div = document.createElement('div');
                    div.className = 'mention-item';
                    div.innerHTML = '<img src="' + amigo.foto + '"> <span>' + amigo.nome + '</span>';
                    div.onclick = function() {
                        var prefix = textBefore.substring(0, match.index);
                        var suffix = input.value.substring(cursorPos);
                        input.value = prefix + '@' + amigo.tag + ' ' + suffix;
                        mentionDropdown.style.display = 'none';
                        input.focus();
                    };
                    mentionDropdown.appendChild(div);
                });
                mentionDropdown.style.display = 'block';
                var rect = input.getBoundingClientRect();
                mentionDropdown.style.top = (window.scrollY + rect.bottom + 2) + 'px';
                mentionDropdown.style.left = (window.scrollX + rect.left + 10) + 'px';
            } else { mentionDropdown.style.display = 'none'; }
        } else { mentionDropdown.style.display = 'none'; }
    }
});

document.addEventListener('click', function(e) {
    if (e.target !== mentionActiveInput && e.target !== mentionDropdown && !mentionDropdown.contains(e.target)) {
        mentionDropdown.style.display = 'none';
    }
});
</script>
</body>
</html>
