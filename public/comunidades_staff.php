<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Staff</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<style>
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .left-col { width: 200px; flex-shrink: 0; position: sticky; top: 15px; max-height: calc(100vh - 30px); overflow-y: auto; }
    .center-col { flex: 1; min-width: 0; }

    /* Staff styles */
    .staff-section-title { font-size: 14px; font-weight: bold; color: var(--title); margin-top: 20px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px dashed #ccc; display: flex; align-items: center; gap: 8px; }
    .staff-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; text-align: center; }

    .staff-card { display: flex; flex-direction: column; align-items: center; background: #fff; border: 1px solid #e4ebf5; border-radius: 6px; padding: 15px 10px; transition: 0.2s; position: relative; }
    .staff-card:hover { border-color: #a5bce3; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transform: translateY(-2px); }

    .staff-pic { width: 70px; height: 70px; border-radius: 5px; object-fit: cover; border: 2px solid #e4ebf5; margin-bottom: 10px; }
    .staff-card.owner .staff-pic { border-color: #f1c40f; box-shadow: 0 0 8px rgba(241, 196, 15, 0.4); }
    .staff-card.mod .staff-pic { border-color: #2ecc71; }

    .staff-name { font-size: 12px; font-weight: bold; color: var(--link); text-decoration: none; word-break: break-word; line-height: 1.2; }
    .staff-name:hover { text-decoration: underline; }

    .badge-role { font-size: 10px; font-weight: bold; padding: 3px 8px; border-radius: 20px; margin-top: 8px; display: inline-block; }
    .badge-owner { background: #fffdf5; color: #d35400; border: 1px solid #f39c12; }
    .badge-mod { background: #e4f2e9; color: #2a6b2a; border: 1px solid #8bc59e; }

    .btn-remove-mod { position: absolute; top: 5px; right: 5px; background: #e8eef7; border: 1px solid var(--link); color: var(--link); font-size: 9px; padding: 2px 6px; border-radius: 3px; cursor: pointer; transition: 0.2s; }
    .btn-remove-mod:hover { background: #d0dae8; }

    .staff-empty { font-size: 11px; color: #999; padding: 15px; text-align: center; border: 1px dashed #ccc; border-radius: 6px; margin-top: 10px; }

    /* Add mod form */
    .add-mod-section { margin-top: 30px; border-top: 1px solid var(--line); padding-top: 20px; }
    .add-mod-title { font-size: 13px; font-weight: bold; color: var(--title); margin-bottom: 10px; }
    .add-mod-row { display: flex; gap: 10px; align-items: center; }
    .add-mod-input { flex: 1; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px; outline: none; }
    .add-mod-input:focus { border-color: var(--orkut-blue); }
    .btn-add-mod { background: var(--orkut-blue); color: #fff; border: none; padding: 8px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; white-space: nowrap; }
    .btn-add-mod:hover { background: #5a72a0; }

    /* Lookup modal */
    .mod-lookup-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .mod-lookup-modal { background: #fff; border-radius: 8px; padding: 25px; max-width: 360px; width: 90%; box-shadow: 0 8px 30px rgba(0,0,0,0.3); text-align: center; }
    .mod-lookup-modal .lookup-pic { width: 80px; height: 80px; border-radius: 6px; object-fit: cover; border: 2px solid #e4ebf5; margin: 0 auto 12px; display: block; }
    .mod-lookup-modal .lookup-name { font-size: 15px; font-weight: bold; color: var(--link); margin-bottom: 5px; }
    .mod-lookup-modal .lookup-msg { font-size: 11px; color: #666; margin-bottom: 20px; }
    .mod-lookup-modal .lookup-btns { display: flex; gap: 10px; justify-content: center; }
    .mod-lookup-modal .lookup-btns button { padding: 8px 20px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; border: none; }
    .mod-lookup-modal .btn-confirm { background: #27ae60; color: #fff; }
    .mod-lookup-modal .btn-confirm:hover { background: #219a52; }
    .mod-lookup-modal .btn-cancel { background: #e4ebf5; color: #333; }
    .mod-lookup-modal .btn-cancel:hover { background: #d0dae8; }

    @media (max-width: 768px) {
        .staff-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 480px) {
        .staff-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
</head>
<body>

<div id="app-header"></div>
<div class="container">
    <div class="left-col" id="left-col">Carregando...</div>
    <div class="center-col" id="center-col">Carregando...</div>
    <div class="right-col" id="right-col"></div>
</div>

<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
var _commId = null;
var _isOwner = false;

document.addEventListener('DOMContentLoaded', async function() {
    await loadLayout({ activePage: 'comunidades' });

    var params = new URLSearchParams(window.location.search);
    _commId = params.get('id');
    if (!_commId) {
        document.getElementById('center-col').innerHTML = '<div class="card"><p>Comunidade não especificada.</p></div>';
        return;
    }

    loadStaff();
});

function getFoto(f) {
    if (!f) return '/perfilsemfoto.jpg';
    if (f.startsWith('http') || f.startsWith('/')) return f;
    return '/' + f;
}

function getFotoComm(f) {
    if (!f) return '/perfilsemfoto.jpg';
    if (f.startsWith('http') || f.startsWith('/')) return f;
    return '/' + f;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function loadStaff() {
    try {
        var resp = await fetch('/api/comunidade/' + _commId + '/staff');
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('center-col').innerHTML = '<div class="card"><p>' + (data.message || 'Erro ao carregar staff.') + '</p></div>';
            return;
        }

        _isOwner = data.isOwner;

        buildSidebar(data.community, data.membrosCount, data.isMember, data.isOwner);
        buildStaffContent(data.owner, data.moderadores, data.community, data.isOwner);
        buildRightSidebar(data.membros, data.membrosCount, data.community);
    } catch(err) {
        console.error(err);
        document.getElementById('center-col').innerHTML = '<div class="card"><p>Erro ao carregar staff.</p></div>';
    }
}

function buildSidebar(comm, membrosCount, isMember, isOwner) {
    var html = '<div class="card-left">';
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
    html += '    <li><a href="/enquetes.php?id=' + comm.id + '"><span>📊</span> enquetes</a></li>';
    html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=membros"><span>👥</span> membros</a></li>';
    html += '    <li class="active"><a href="comunidades_staff.php?id=' + comm.id + '"><span>👑</span> staff</a></li>';
    html += '    <li><a href="sorteio.php?id=' + comm.id + '"><span>🎁</span> sorteios</a></li>';
    if (isOwner) {
        html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=config"><span>⚙️</span> configurações</a></li>';
    }
    html += '  </ul>';
    html += '</div>';
    document.getElementById('left-col').innerHTML = html;
}

function buildStaffContent(owner, moderadores, comm, isOwner) {
    var html = '';

    // Breadcrumb
    html += '<div class="breadcrumb">';
    html += '<a href="profile.php">Início</a> &gt; ';
    html += '<a href="comunidades.php?id=' + comm.id + '">' + escapeHtml(comm.nome) + '</a> &gt; ';
    html += 'Staff';
    html += '</div>';

    html += '<div class="card">';
    html += '<h1 class="orkut-name" style="font-size:22px; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:10px;">';
    html += 'Staff da Comunidade';
    html += '</h1>';
    html += '<p style="font-size:11px; color:#666; margin-bottom:20px;">';
    html += 'Estes são os responsáveis por manter a ordem, criar regras e moderar o conteúdo desta comunidade.';
    html += '</p>';

    // === Owner section ===
    html += '<div class="staff-section-title">👑 Dono (Criador)</div>';
    html += '<div class="staff-grid">';
    html += '  <div class="staff-card owner">';
    html += '    <a href="profile.php?uid=' + owner.id + '">';
    html += '      <img src="' + getFoto(owner.foto_perfil) + '" class="staff-pic">';
    html += '    </a>';
    html += '    <a href="profile.php?uid=' + owner.id + '" class="staff-name">' + escapeHtml(owner.nome) + '</a>';
    html += '    <span class="badge-role badge-owner">Dono</span>';
    html += '  </div>';
    html += '</div>';

    // === Moderators section ===
    html += '<div class="staff-section-title" style="margin-top:30px;">🛡️ Moderadores</div>';

    if (moderadores.length === 0) {
        html += '<div class="staff-empty">Nenhum moderador nesta comunidade.</div>';
    } else {
        html += '<div class="staff-grid">';
        for (var i = 0; i < moderadores.length; i++) {
            var m = moderadores[i];
            html += '<div class="staff-card mod">';
            if (isOwner) {
                html += '<button class="btn-remove-mod" onclick="removeMod(\'' + m.id + '\', \'' + escapeHtml(m.nome).replace(/'/g, "\\'") + '\')" title="Remover moderador">✕</button>';
            }
            html += '  <a href="profile.php?uid=' + m.id + '">';
            html += '    <img src="' + getFoto(m.foto_perfil) + '" class="staff-pic">';
            html += '  </a>';
            html += '  <a href="profile.php?uid=' + m.id + '" class="staff-name">' + escapeHtml(m.nome) + '</a>';
            html += '  <span class="badge-role badge-mod">Moderador</span>';
            html += '</div>';
        }
        html += '</div>';
    }

    // === Add moderator section (owner only) ===
    if (isOwner) {
        html += '<div class="add-mod-section">';
        html += '  <div class="add-mod-title">➕ Adicionar Moderador</div>';
        html += '  <p style="font-size:11px; color:#666; margin-bottom:10px;">Cole o link do perfil do membro que deseja promover a moderador:</p>';
        html += '  <div class="add-mod-row">';
        html += '    <input type="text" id="inputProfileLink" class="add-mod-input" placeholder="Ex: profile.php?uid=202603030206474547 ou cole a URL completa">';
        html += '    <button class="btn-add-mod" onclick="buscarUsuario()">Buscar</button>';
        html += '  </div>';
        html += '</div>';
    }

    html += '</div>';

    document.getElementById('center-col').innerHTML = html;
}

function buildRightSidebar(membros, membrosCount, comm) {
    var html = '<div class="box-sidebar">';
    html += '<div class="box-title">membros (' + membrosCount + ') <a href="comunidades.php?id=' + comm.id + '&view=membros">ver todos</a></div>';
    html += '<div class="grid" style="grid-template-columns: repeat(3, 1fr);">';

    for (var i = 0; i < membros.length; i++) {
        var m = membros[i];
        var mNome = escapeHtml(m.nome);
        if (mNome.length > 10) mNome = mNome.substring(0, 8) + '..';
        html += '<div class="grid-item">';
        html += '<a href="profile.php?uid=' + m.id + '">';
        html += '  <div class="grid-thumb">';
        if (m.foto_perfil) {
            html += '    <img src="' + getFoto(m.foto_perfil) + '" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            html += '    <div style="width:100%;height:100%;background:#e4ebf5;display:flex;align-items:center;justify-content:center;font-size:20px;">👤</div>';
        }
        html += '  </div>';
        html += mNome;
        html += '</a></div>';
    }

    html += '</div></div>';
    document.getElementById('right-col').innerHTML = html;
}

function extractUidFromLink(link) {
    if (!link) return null;
    link = link.trim();
    // Try to extract uid from various formats
    var match = link.match(/[?&]uid=([^&#]+)/);
    if (match) return match[1];
    // Maybe they pasted just the ID
    if (/^\d{10,}$/.test(link)) return link;
    return null;
}

async function buscarUsuario() {
    var input = document.getElementById('inputProfileLink');
    if (!input || !input.value.trim()) {
        showToast('Cole o link do perfil do membro.', 'error');
        return;
    }

    var uid = extractUidFromLink(input.value);
    if (!uid) {
        showToast('Link inválido. Cole um link como: profile.php?uid=123456', 'error');
        return;
    }

    try {
        var resp = await fetch('/api/comunidade/' + _commId + '/lookup-user?uid=' + encodeURIComponent(uid));
        var data = await resp.json();
        if (!data.success) {
            showToast(data.message || 'Usuário não encontrado.', 'error');
            return;
        }

        showLookupModal(data.user);
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
}

function showLookupModal(user) {
    // Remove existing modal if any
    var existing = document.getElementById('mod-lookup-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.id = 'mod-lookup-overlay';
    overlay.className = 'mod-lookup-overlay';

    var modal = document.createElement('div');
    modal.className = 'mod-lookup-modal';
    modal.innerHTML = '<img src="' + getFoto(user.foto_perfil) + '" class="lookup-pic">'
        + '<div class="lookup-name">' + escapeHtml(user.nome) + '</div>'
        + '<a href="profile.php?uid=' + user.id + '" target="_blank" style="font-size:11px; color:var(--link); margin-bottom:12px; display:inline-block;">visualizar perfil ↗</a>'
        + '<div class="lookup-msg">Deseja promover este membro a <b>moderador</b> da comunidade?</div>'
        + '<div class="lookup-btns">'
        + '  <button class="btn-cancel" id="btnLookupCancel">Cancelar</button>'
        + '  <button class="btn-confirm" id="btnLookupConfirm">✔ Confirmar</button>'
        + '</div>';

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Close on overlay click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });

    document.getElementById('btnLookupCancel').addEventListener('click', function() {
        overlay.remove();
    });

    document.getElementById('btnLookupConfirm').addEventListener('click', async function() {
        try {
            var resp = await fetch('/api/comunidade/' + _commId + '/staff/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: user.id })
            });
            var data = await resp.json();
            overlay.remove();
            if (data.success) {
                showToast(data.message || 'Moderador adicionado!', 'success');
                document.getElementById('inputProfileLink').value = '';
                loadStaff();
            } else {
                showToast(data.message || 'Erro ao adicionar moderador.', 'error');
            }
        } catch(err) {
            overlay.remove();
            showToast('Erro de conexão.', 'error');
        }
    });
}

function removeMod(userId, userName) {
    showConfirm('Deseja remover <b>' + userName + '</b> do cargo de moderador?', async function() {
        try {
            var resp = await fetch('/api/comunidade/' + _commId + '/staff/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: userId })
            });
            var data = await resp.json();
            if (data.success) {
                showToast(data.message || 'Moderador removido!', 'success');
                loadStaff();
            } else {
                showToast(data.message || 'Erro ao remover moderador.', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão.', 'error');
        }
    });
}
</script>
</body>
</html>
