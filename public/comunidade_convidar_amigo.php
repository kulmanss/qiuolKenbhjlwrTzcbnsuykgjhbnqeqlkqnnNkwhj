<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Convidar Amigos</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<style>
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .share-box { background: #fdfdfd; border: 1px dashed #a5bce3; padding: 20px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    .share-box p { margin-top: 0; font-size: 13px; color: #555; }
    .share-input-group { display: flex; gap: 10px; justify-content: center; max-width: 500px; margin: 0 auto; }
    .share-input-group input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; background: #eee; color: #555; }

    .toolbar { background: #f4f7fc; border: 1px solid #c0d0e6; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .toolbar input { padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; outline: none; width: 300px; max-width: 100%; }

    .friends-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .friend-card { background: #fff; border: 1px solid #e4ebf5; border-radius: 6px; padding: 10px; text-align: center; display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: 0.2s; position: relative; }
    .friend-card:hover { border-color: #a5bce3; background: #f9fbfc; }
    .friend-card.selected { border-color: #4caf50; background: #e8f5e9; box-shadow: 0 0 5px rgba(76,175,80,0.4); }

    .f-pic { width: 60px; height: 60px; border-radius: 4px; object-fit: cover; border: 1px solid #ccc; margin-bottom: 8px; }
    .f-name { font-size: 11px; font-weight: bold; color: var(--link); margin-bottom: 10px; word-break: break-word; }

    .custom-checkbox { position: absolute; top: 5px; right: 5px; width: 18px; height: 18px; cursor: pointer; }

    .msg-box { padding: 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-align: center; margin-bottom: 15px; }
    .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .msg-error { background: #ffebee; color: #cc0000; border: 1px solid #ffcdd2; }

    @media (max-width: 768px) {
        .friends-grid { grid-template-columns: repeat(3, 1fr); }
        .toolbar { flex-direction: column; gap: 10px; }
        .toolbar input { width: 100%; }
        .share-input-group { flex-direction: column; }
    }
    @media (max-width: 480px) {
        .friends-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div class="left-col" id="left-col">
        <div style="text-align:center; padding:20px; color:#999;">Carregando...</div>
    </div>

    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="profile.php">Início</a> &gt; Carregando...
        </div>

        <div class="card" id="main-card">
            <div style="text-align:center; padding:40px; color:#999;">Carregando...</div>
        </div>
    </div>
</div>
<div id="app-footer"></div>

<script src="/js/layout.js"></script>
<script src="/js/toast.js"></script>
<script>
var currentCommId = null;
var currentComm = null;

function getQueryParam(name) {
    var url = new URLSearchParams(window.location.search);
    return url.get(name);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function getFotoPerfil(foto) {
    if (!foto) return '/uploads/profiles/default_avatar.jpg';
    if (foto.startsWith('http') || foto.startsWith('/')) return foto;
    return '/' + foto;
}

function getFotoComm(foto) {
    if (!foto) return '/semfotocomunidade.jpg';
    if (foto.startsWith('http') || foto.startsWith('/')) return foto;
    return '/' + foto;
}

async function loadPage() {
    await loadLayout({ activePage: 'comunidades' });
    var commId = getQueryParam('id');
    if (!commId) {
        document.getElementById('main-card').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000; font-weight:bold;">Comunidade não especificada.</div>';
        return;
    }
    currentCommId = commId;

    try {
        var resp = await fetch('/api/comunidade/' + commId + '/amigos-para-convidar');
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000; font-weight:bold;">' + escapeHtml(data.message) + '</div>';
            return;
        }

        currentComm = data.community;
        var amigos = data.amigos;
        var membrosCount = data.membrosCount;

        // Update page title
        document.title = 'Convidar Amigos - ' + currentComm.nome;

        // Breadcrumb
        document.getElementById('breadcrumb').innerHTML =
            '<a href="profile.php">Início</a> &gt; ' +
            '<a href="comunidades.php?id=' + commId + '">' + escapeHtml(currentComm.nome) + '</a> &gt; ' +
            'Convidar Amigos';

        // Left sidebar
        buildLeftCol(currentComm, membrosCount);

        // Main content
        buildMainContent(currentComm, amigos, commId);

    } catch (err) {
        document.getElementById('main-card').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000; font-weight:bold;">Erro ao carregar página.</div>';
    }
}

function buildLeftCol(comm, membrosCount) {
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
    html += '    <li class="active"><a href="comunidade_convidar_amigo.php?id=' + comm.id + '"><span>📱</span> convidar amigos</a></li>';
    html += '    <li><a href="/forum.php?id=' + comm.id + '"><span>💬</span> fórum</a></li>';
    html += '    <li><a href="/enquetes.php?id=' + comm.id + '"><span>📊</span> enquetes</a></li>';
    html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=membros"><span>👥</span> membros</a></li>';
    html += '    <li><a href="comunidades_staff.php?id=' + comm.id + '"><span>👑</span> staff</a></li>';
    html += '    <li><a href="sorteio.php?id=' + comm.id + '"><span>🎁</span> sorteios</a></li>';
    html += '  </ul>';
    html += '</div>';

    document.getElementById('left-col').innerHTML = html;
}

function buildMainContent(comm, amigos, commId) {
    var html = '';

    html += '<h1 class="orkut-name" style="font-size: 22px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px;">';
    html += '  Convidar para a comunidade';
    html += '</h1>';

    // Share box
    html += '<div class="share-box">';
    html += '  <p><b>Compartilhar fora do Yorkut</b><br>Copie o link abaixo para convidar amigos via WhatsApp, Telegram ou outras redes sociais.</p>';
    html += '  <div class="share-input-group">';
    html += '    <input type="text" id="commLinkInput" value="' + window.location.origin + '/comunidades.php?id=' + commId + '" readonly>';
    html += '    <button type="button" class="btn-action" onclick="copyShareLink()">🔗 Copiar Link</button>';
    html += '  </div>';
    html += '</div>';

    html += '<h2 style="font-size: 14px; color: var(--title); margin-bottom: 10px;">Convidar amigos do Yorkut</h2>';
    html += '<p style="font-size:11px; color:#666;">Abaixo estão seus amigos que <b>ainda não participam</b> desta comunidade.</p>';

    // Message area for success/error
    html += '<div id="invite-msg"></div>';

    if (amigos.length === 0) {
        html += '<div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; margin-top:15px;">Todos os seus amigos já são membros desta comunidade! 🎉</div>';
    } else {
        // Toolbar
        html += '<div class="toolbar">';
        html += '  <input type="text" id="searchFriend" placeholder="🔍 Buscar amigo pelo nome..." onkeyup="filterFriends()">';
        html += '  <div style="font-size:11px; color:#555;">';
        html += '    <a href="javascript:void(0)" onclick="selectAll(true)" style="color:var(--link); font-weight:bold;">Todos</a> | ';
        html += '    <a href="javascript:void(0)" onclick="selectAll(false)" style="color:var(--link); font-weight:bold;">Nenhum</a>';
        html += '  </div>';
        html += '</div>';

        // Friends grid
        html += '<div class="friends-grid" id="friendsGrid">';
        for (var i = 0; i < amigos.length; i++) {
            var a = amigos[i];
            var nameLower = (a.nome || '').toLowerCase();
            html += '<label class="friend-card" data-name="' + escapeHtml(nameLower) + '">';
            html += '  <input type="checkbox" name="friends_ids[]" value="' + a.id + '" class="custom-checkbox" onchange="toggleCardStyle(this)">';
            html += '  <img src="' + getFotoPerfil(a.foto_perfil) + '" class="f-pic">';
            html += '  <span class="f-name">' + escapeHtml(a.nome) + '</span>';
            html += '</label>';
        }
        html += '</div>';

        // Submit button
        html += '<div style="text-align:right; margin-top:20px; border-top:1px solid #eee; padding-top:15px;">';
        html += '  <button type="button" class="btn-action" onclick="sendInvites()" style="padding:10px 30px; font-size:14px; background:#4caf50; border-color:#388e3c;">';
        html += '    📩 Enviar Convites Selecionados';
        html += '  </button>';
        html += '</div>';
    }

    document.getElementById('main-card').innerHTML = html;
}

// --- Copiar Link Externo ---
function copyShareLink() {
    var input = document.getElementById('commLinkInput');
    input.select();
    document.execCommand('copy');
    showToast('🔗 Link da comunidade copiado com sucesso!', 'success');
}

// --- Filtro de Amigos (Barra de Pesquisa) ---
function filterFriends() {
    var input = document.getElementById('searchFriend').value.toLowerCase();
    var cards = document.querySelectorAll('.friend-card');
    cards.forEach(function(card) {
        var name = card.getAttribute('data-name');
        if (name.includes(input)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// --- Estilo Visual ao Selecionar o Checkbox ---
function toggleCardStyle(checkbox) {
    var card = checkbox.closest('.friend-card');
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

// --- Marcar/Desmarcar Todos ---
function selectAll(select) {
    var checkboxes = document.querySelectorAll('.custom-checkbox');
    checkboxes.forEach(function(cb) {
        var card = cb.closest('.friend-card');
        if (card.style.display !== 'none') {
            cb.checked = select;
            toggleCardStyle(cb);
        }
    });
}

// --- Enviar Convites ---
async function sendInvites() {
    var checkboxes = document.querySelectorAll('.custom-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Selecione pelo menos um amigo para convidar.', 'warning');
        return;
    }

    var ids = [];
    checkboxes.forEach(function(cb) { ids.push(cb.value); });

    try {
        var resp = await fetch('/api/comunidades/convidar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: currentCommId, amigos_ids: ids })
        });
        var data = await resp.json();

        if (data.success) {
            showToast(data.message, 'success');
            // Reload the page to refresh the friends list
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Erro ao enviar convites.', 'error');
    }
}

// Initialize
loadPage();
</script>
</body>
</html>
