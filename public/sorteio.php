<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Sorteios</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .left-col { width: 200px; flex-shrink: 0; position: sticky; top: 15px; max-height: calc(100vh - 30px); overflow-y: auto; }
    .center-col { flex: 1; min-width: 0; }

    /* Sorteio cards */
    .sorteio-item { border: 1px solid #e4ebf5; border-radius: 8px; padding: 18px; margin-top: 15px; background: #fff; transition: 0.2s; }
    .sorteio-item:hover { border-color: #a5bce3; box-shadow: 0 3px 10px rgba(0,0,0,0.04); }
    .sorteio-item.encerrado { background: #fafafa; opacity: 0.85; }

    .sorteio-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
    .sorteio-titulo { font-size: 15px; font-weight: bold; color: var(--title); margin: 0; }
    .sorteio-badge { font-size: 10px; font-weight: bold; padding: 3px 10px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
    .badge-ativo { background: #e8f8ec; color: #009933; border: 1px solid #b3e6c2; }
    .badge-encerrado { background: #f5f5f5; color: #999; border: 1px solid #ddd; }

    .sorteio-premio { font-size: 12px; color: #333; margin: 8px 0; }
    .sorteio-premio strong { color: var(--orkut-pink); }

    .sorteio-meta { display: flex; flex-wrap: wrap; gap: 15px; font-size: 11px; color: #888; margin-bottom: 12px; }
    .sorteio-meta span { display: flex; align-items: center; gap: 4px; }

    .sorteio-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .sorteio-participar-area { text-align: center; margin-top: 32px; }
    .btn-participar { background: url('/img/ok.png') no-repeat center center / 100% 100%; color: #fff; border: none; padding: 7px 18px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-participar:hover { opacity: 0.85; }
    .btn-participar:disabled { background: #ccc; cursor: default; }
    .btn-participando { background: #e8f8ec; color: #27ae60; border: 1px solid #b3e6c2; padding: 7px 18px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: default; }
    .btn-sortear { background: var(--orkut-pink); color: #fff; border: none; padding: 7px 18px; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-sortear:hover { background: #c42f82; }
    .btn-excluir-sort { background: none; border: none; color: #cc0000; font-size: 10px; cursor: pointer; text-decoration: underline; }
    .btn-excluir-sort:hover { color: #990000; }

    /* Winner card */
    .vencedor-box { display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, #fffdf5, #fff8e1); border: 1px solid #f1c40f; border-radius: 8px; padding: 12px 15px; margin-top: 10px; }
    .vencedor-pic { width: 45px; height: 45px; border-radius: 5px; object-fit: cover; border: 2px solid #f1c40f; }
    .vencedor-info { font-size: 12px; color: #333; }
    .vencedor-info strong { color: var(--link); }
    .vencedor-info .trophy { font-size: 18px; }

    /* Inline create form (enquete style) */
    .sort-form-inline { background: #f4f7fc; padding: 15px; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 15px; }
    .sort-form-inline label { font-size: 10px; font-weight: bold; color: #666; display: block; margin-bottom: 3px; }
    .sort-form-inline input[type="text"],
    .sort-form-inline input[type="number"],
    .sort-form-inline input[type="datetime-local"] { width: 100%; padding: 7px 8px; border: 1px solid #ccc; font-size: 12px; box-sizing: border-box; border-radius: 3px; }
    .sort-form-inline input:focus { border-color: var(--orkut-blue); outline: none; }
    .sort-form-row { display: flex; gap: 12px; margin-bottom: 10px; }
    .sort-form-row > div { flex: 1; }
    .sort-form-check { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #555; margin-bottom: 12px; }

    .empty-sorteios { text-align: center; padding: 50px 20px; color: #999; font-size: 13px; border: 1px dashed #c0d0e6; border-radius: 8px; margin-top: 20px; }
    .empty-sorteios .icon { font-size: 40px; margin-bottom: 10px; }

    .mod-tag { font-size: 9px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 1px 6px; border-radius: 3px; }

    /* Participants modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #fff; border-radius: 8px; width: 380px; max-width: 90vw; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid var(--line); }
    .modal-header h3 { margin: 0; font-size: 14px; color: var(--title); }
    .modal-close { background: none; border: none; font-size: 18px; cursor: pointer; color: #999; padding: 0 4px; }
    .modal-close:hover { color: #333; }
    .modal-body { overflow-y: auto; padding: 10px 16px; flex: 1; }
    .part-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .part-item:last-child { border-bottom: none; }
    .part-pic { width: 38px; height: 38px; border-radius: 4px; object-fit: cover; flex-shrink: 0; }
    .part-nome { font-size: 12px; color: var(--link); text-decoration: none; font-weight: bold; }
    .part-nome:hover { text-decoration: underline; }
    .btn-lista { background: none; border: none; color: var(--link); font-size: 10px; cursor: pointer; text-decoration: underline; padding: 0; margin-left: 2px; }
    .btn-lista:hover { color: var(--orkut-pink); }

    /* Regras do sorteio */
    .sorteio-regras { margin: 10px 0 6px 0; background: #f8f9fc; border: 1px solid #e0e4ec; border-radius: 6px; padding: 10px 14px; }
    .sorteio-regras-label { font-size: 11px; font-weight: bold; color: #555; margin-bottom: 6px; }
    .sorteio-regras-body { font-size: 13px; line-height: 1.7; color: #333; word-wrap: break-word; }
    .sorteio-regras-body h1, .sorteio-regras-body h2, .sorteio-regras-body h3 { color: var(--title); margin: 8px 0 4px 0; }
    .sorteio-regras-body h1 { font-size: 18px; }
    .sorteio-regras-body h2 { font-size: 16px; }
    .sorteio-regras-body h3 { font-size: 14px; }
    .sorteio-regras-body p { margin: 0 0 8px 0; }
    .sorteio-regras-body ul, .sorteio-regras-body ol { margin: 6px 0; padding-left: 22px; }
    .sorteio-regras-body blockquote { border-left: 3px solid var(--orkut-blue); margin: 6px 0; padding: 4px 10px; background: #f0f5fb; font-style: italic; }
    .sorteio-regras-body strong { font-weight: bold; }
    .sorteio-regras-body em { font-style: italic; }
    .sorteio-regras-body u { text-decoration: underline; }
    .sorteio-regras-body s { text-decoration: line-through; }
    .sorteio-regras-body a { color: var(--link); }

    /* Quill editor adjustments inside form */
    .sort-form-inline .ql-toolbar { border-radius: 4px 4px 0 0; }
    .sort-form-inline .ql-container { border-radius: 0 0 4px 4px; }
</style>
<script>
    function toggleForm(id) {
        var e = document.getElementById(id);
        if (e) {
            e.style.display = (e.style.display === 'block') ? 'none' : 'block';
            if (e.style.display === 'block' && id === 'newSorteio') {
                setTimeout(function(){ initQuillSortRegras(); }, 100);
            }
        }
    }
</script>
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
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
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

    loadSorteios();
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

function formatDate(d) {
    if (!d) return '—';
    var dt = new Date(d);
    if (isNaN(dt.getTime())) return d;
    var dd = String(dt.getDate()).padStart(2, '0');
    var mm = String(dt.getMonth() + 1).padStart(2, '0');
    var yy = dt.getFullYear();
    var hh = String(dt.getHours()).padStart(2, '0');
    var mi = String(dt.getMinutes()).padStart(2, '0');
    var ss = String(dt.getSeconds()).padStart(2, '0');
    return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi + ':' + ss;
}

function isExpired(dataFim) {
    if (!dataFim) return false;
    var fim = new Date(dataFim);
    return fim <= new Date();
}

async function loadSorteios() {
    try {
        var resp = await fetch('/api/sorteios/' + _commId);
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('center-col').innerHTML = '<div class="card"><p>' + (data.message || 'Erro ao carregar.') + '</p></div>';
            return;
        }

        _isOwner = data.isOwner;

        buildSidebar(data.community, data.membrosCount);
        buildCenter(data.community, data.sorteios, data.isOwner, data.isMember);
        buildRightSidebar(data.membros, data.membrosCount, data.community);
    } catch(err) {
        console.error(err);
        document.getElementById('center-col').innerHTML = '<div class="card"><p>Erro ao carregar sorteios.</p></div>';
    }
}

function buildSidebar(comm, membrosCount) {
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
    html += '    <li><a href="comunidades_staff.php?id=' + comm.id + '"><span>👑</span> staff</a></li>';
    html += '    <li class="active"><a href="sorteio.php?id=' + comm.id + '"><span>🎁</span> sorteios</a></li>';
    if (_isOwner) {
        html += '    <li><a href="comunidades.php?id=' + comm.id + '&view=config"><span>⚙️</span> configurações</a></li>';
    }
    html += '  </ul>';
    html += '</div>';
    document.getElementById('left-col').innerHTML = html;
}

function buildCenter(comm, sorteios, isOwner, isMember) {
    var html = '';

    html += '<div class="breadcrumb">';
    html += '<a href="profile.php">Início</a> &gt; ';
    html += '<a href="comunidades.php?id=' + comm.id + '">' + escapeHtml(comm.nome) + '</a> &gt; ';
    html += 'Sorteios';
    html += '</div>';

    html += '<div class="card">';

    // Toolbar (enquete style)
    html += '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:15px;">';
    html += '  <h1 class="orkut-name" style="font-size:20px; margin:0;">🎁 Sorteios da Comunidade</h1>';
    if (isOwner) {
        html += '  <button class="btn-action" style="padding:4px 10px;" onclick="toggleForm(\'newSorteio\')">Criar Sorteio</button>';
    }
    html += '</div>';

    // Inline create form (hidden by default, owner only)
    if (isOwner) {
        html += '<div id="newSorteio" style="display:none;" class="sort-form-inline">';
        html += '  <div style="margin-bottom:10px;">';
        html += '    <label>Item Sorteado *</label>';
        html += '    <input type="text" id="sortPremio" maxlength="200" placeholder="Ex: Vale presente de R$50, KutCoins, skin rara...">';
        html += '  </div>';
        html += '  <div class="sort-form-row">';
        html += '    <div>';
        html += '      <label>Data de Encerramento *</label>';
        html += '      <input type="datetime-local" id="sortDataFim" step="1">';
        html += '    </div>';
        html += '    <div>';
        html += '      <label>Quantidade de Vencedores</label>';
        html += '      <input type="number" id="sortQtdVencedores" value="1" min="1" max="50">';
        html += '    </div>';
        html += '  </div>';
        html += '  <div style="margin-bottom:10px;">';
        html += '    <label>Regras do Sorteio</label>';
        html += '    <div id="sortRegrasContainer" style="background:#fff;border:1px solid #ccc;border-radius:4px;">';
        html += '      <div id="sortRegrasEditor" style="height:150px;font-size:13px;"></div>';
        html += '    </div>';
        html += '  </div>';
        html += '  <label class="sort-form-check">';
        html += '    <input type="checkbox" id="sortModParticipa" checked> Moderadores podem participar do sorteio';
        html += '  </label>';
        html += '  <div style="text-align:right;">';
        html += '    <button type="button" class="btn-action" style="background:#fff; color:#666;" onclick="toggleForm(\'newSorteio\')">Cancelar</button>';
        html += '    <button type="button" class="btn-action" onclick="criarSorteio()">🎁 Criar Sorteio</button>';
        html += '  </div>';
        html += '</div>';
    }

    if (sorteios.length === 0) {
        html += '<div class="empty-sorteios">';
        html += '  <div class="icon">🎁</div>';
        html += '  <p>Não há nenhum sorteio acontecendo no momento...</p>';
        if (isOwner) {
            html += '  <p style="font-size:11px; color:#aaa; margin-top:5px;">Crie o primeiro sorteio para seus membros!</p>';
        }
        html += '</div>';
    } else {
        for (var i = 0; i < sorteios.length; i++) {
            var s = sorteios[i];
            var ended = s.sorteado || isExpired(s.data_fim);
            html += buildSorteioCard(s, ended, isOwner, isMember);
        }
    }

    html += '</div>';
    document.getElementById('center-col').innerHTML = html;
}

function buildSorteioCard(s, ended, isOwner, isMember) {
    var html = '';
    html += '<div class="sorteio-item' + (ended ? ' encerrado' : '') + '">';

    html += '<div class="sorteio-header">';
    html += '  <h3 class="sorteio-titulo">🏆 ' + escapeHtml(s.premio) + '</h3>';
    if (s.regras && s.regras.trim() && s.regras.trim() !== '<p><br></p>') {
        html += '<div class="sorteio-regras">';
        html += '  <div class="sorteio-regras-label">📜 Regras do Sorteio:</div>';
        html += '  <div class="sorteio-regras-body">' + sanitizeHtml(s.regras) + '</div>';
        html += '</div>';
    }
    if (s.sorteado) {
        html += '  <span class="sorteio-badge badge-encerrado">✅ Sorteado</span>';
    } else if (ended) {
        html += '  <span class="sorteio-badge badge-encerrado">⏰ Expirado</span>';
    } else {
        html += '  <span class="sorteio-badge badge-ativo">🔥 Ativo</span>';
    }
    html += '</div>';

    html += '<div class="sorteio-meta">';
    if (ended) {
        html += '  <span>📅 Sorteio ocorreu em: ' + formatDate(s.data_fim) + '</span>';
    } else {
        html += '  <span>📅 O sorteio acontecerá em: ' + formatDate(s.data_fim) + '</span>';
    }
    html += '  <span>👥 ' + s.total_participantes + ' participante' + (s.total_participantes !== 1 ? 's' : '');
    html += ' <button class="btn-lista" onclick="verParticipantes(' + s.id + ')">(lista)</button>';
    html += '</span>';
    html += '  <span>🎯 ' + (s.qtd_vencedores || 1) + ' vencedor' + ((s.qtd_vencedores || 1) > 1 ? 'es' : '') + '</span>';
    if (!s.mod_participa) {
        html += '  <span class="mod-tag">Mods não participam</span>';
    }
    html += '  <span>📝 Criado por: <a href="profile.php?uid=' + s.criador_id + '" style="color:var(--link);">' + escapeHtml(s.criador_nome) + '</a></span>';
    html += '</div>';

    // Winners display (multiple)
    if (s.sorteio_nao_realizado) {
            html += '<div style="color:#cc0000; font-size:13px; margin:28px 0 12px 0; font-weight:bold; text-align:center;">SORTEIO NÃO REALIZADO: HÁ MENOS PARTICIPANTES QUE VENCEDORES</div>';
    } else if (s.sorteado || (ended && s.vencedores && s.vencedores.length > 0)) {
        if (s.vencedores && s.vencedores.length === 1) {
            var ven = s.vencedores[0];
            html += '<div class="vencedor-box" style="justify-content:center;">';
            html += '  <span class="trophy">🏆</span>';
            if (ven.is_membro) {
                html += '  <a href="profile.php?uid=' + ven.id + '"><img src="' + getFoto(ven.foto_perfil) + '" class="vencedor-pic"></a>';
                html += '  <div class="vencedor-info">Parabéns ao vencedor:<br><strong><a href="profile.php?uid=' + ven.id + '" style="color:var(--link); text-decoration:none;">' + escapeHtml(ven.nome) + '</a></strong></div>';
            } else {
                html += '  <img src="' + getFoto(ven.foto_perfil) + '" class="vencedor-pic" style="opacity:0.5;">';
                html += '  <div class="vencedor-info">Parabéns ao vencedor:<br><strong style="color:#999;">' + escapeHtml(ven.nome) + '</strong></div>';
            }
            html += '</div>';
        } else if (s.vencedores && s.vencedores.length === 2) {
            html += '<div style="font-size:13px; color:var(--title); margin-bottom:6px; text-align:center;">Parabéns aos vencedores:</div>';
            for (var w = 0; w < 2; w++) {
                var ven = s.vencedores[w];
                html += '<div class="vencedor-box" style="justify-content:center;">';
                html += '  <span class="trophy">🏆</span>';
                if (ven.is_membro) {
                    html += '  <a href="profile.php?uid=' + ven.id + '"><img src="' + getFoto(ven.foto_perfil) + '" class="vencedor-pic"></a>';
                    html += '  <div class="vencedor-info"><strong><a href="profile.php?uid=' + ven.id + '" style="color:var(--link); text-decoration:none;">' + escapeHtml(ven.nome) + '</a></strong></div>';
                } else {
                    html += '  <img src="' + getFoto(ven.foto_perfil) + '" class="vencedor-pic" style="opacity:0.5;">';
                    html += '  <div class="vencedor-info"><strong style="color:#999;">' + escapeHtml(ven.nome) + '</strong></div>';
                }
                html += '</div>';
            }
        } else if (s.vencedores && s.vencedores.length >= 3) {
            html += '<div style="font-size:13px; color:var(--title); margin-bottom:6px; text-align:center;">Parabéns aos vencedores:</div>';
            html += '<div style="text-align:center;"><button class="btn-lista" style="font-size:12px; margin-bottom:8px;" onclick="verVencedores(' + s.id + ')">[Ver Lista de vencedores]</button></div>';
        }
    }

    // Actions
    var premioEsc = escapeHtml(s.premio).replace(/'/g, "\\'");
    if (!ended) {
        // Participate button - centered
        html += '<div class="sorteio-participar-area">';
        if (isMember && !s.participando) {
            html += '  <button class="btn-participar" onclick="participar(' + s.id + ')">Participar</button>';
        } else if (s.participando) {
            html += '  <span class="btn-participando">✅ Você está participando!</span>';
        }
        html += '</div>';
        // Owner actions
        if (isOwner) {
            html += '<div class="sorteio-footer">';
            html += '  <div></div>';
            html += '  <div style="display:flex; gap:8px; align-items:center;">';
            html += '    <button class="btn-sortear" onclick="sortear(' + s.id + ', \'' + premioEsc + '\')">🎲 Sortear Agora</button>';
            html += '    <button class="btn-excluir-sort" onclick="excluirSorteio(' + s.id + ', \'' + premioEsc + '\')">excluir</button>';
            html += '  </div>';
            html += '</div>';
        }
    } else if (!s.sorteado && isOwner) {
        // Expired but not drawn yet
        html += '<div class="sorteio-footer">';
        html += '  <div style="font-size:11px; color:#999;">⏰ O prazo expirou.</div>';
        html += '  <div style="display:flex; gap:8px; align-items:center;">';
        html += '    <button class="btn-sortear" onclick="sortear(' + s.id + ', \'' + premioEsc + '\')">🎲 Sortear Agora</button>';
        html += '    <button class="btn-excluir-sort" onclick="excluirSorteio(' + s.id + ', \'' + premioEsc + '\')">excluir</button>';
        html += '  </div>';
        html += '</div>';
    } else if (s.sorteado && isOwner) {
        html += '<div style="text-align:right; margin-top:8px;">';
        html += '  <button class="btn-excluir-sort" onclick="excluirSorteio(' + s.id + ', \'' + premioEsc + '\')">excluir sorteio</button>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}

async function participar(sorteioId) {
    try {
        var resp = await fetch('/api/sorteios/' + _commId + '/' + sorteioId + '/participar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        var data = await resp.json();
        if (data.success) {
            showToast(data.message || 'Participação confirmada!', 'success');
            loadSorteios();
        } else {
            showToast(data.message || 'Erro ao participar.', 'error');
        }
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
}

async function sortear(sorteioId, premio) {
    showConfirm('Sortear vencedor(es) de <b>' + premio + '</b>? Esta ação não pode ser desfeita.', async function() {
        try {
            var resp = await fetch('/api/sorteios/' + _commId + '/' + sorteioId + '/sortear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            var data = await resp.json();
            if (data.success) {
                if (data.vencedores && data.vencedores.length === 1) {
                    showToast('🎉 Vencedor: ' + data.vencedores[0].nome + '!', 'success');
                } else if (data.vencedores && data.vencedores.length > 1) {
                    showToast('🎉 ' + data.vencedores.length + ' vencedores sorteados!', 'success');
                } else {
                    showToast(data.message || 'Sorteio realizado!', 'success');
                }
                loadSorteios();
            } else {
                showToast(data.message || 'Erro ao sortear.', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão.', 'error');
        }
    });
}

async function excluirSorteio(sorteioId, premio) {
    showConfirm('Deseja excluir o sorteio <b>' + premio + '</b>?', async function() {
        try {
            var resp = await fetch('/api/sorteios/' + _commId + '/' + sorteioId + '/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            var data = await resp.json();
            if (data.success) {
                showToast('Sorteio excluído.', 'success');
                loadSorteios();
            } else {
                showToast(data.message || 'Erro ao excluir.', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão.', 'error');
        }
    }, { danger: true });
}

var quillSortRegras = null;
function initQuillSortRegras() {
    if (quillSortRegras) return;
    quillSortRegras = new Quill('#sortRegrasEditor', {
        theme: 'snow',
        placeholder: 'Escreva as regras do sorteio (opcional)...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote'],
                ['link'],
                ['clean']
            ]
        }
    });
}

async function criarSorteio() {
    var premio = document.getElementById('sortPremio').value.trim();
    var dataFim = document.getElementById('sortDataFim').value;
    var qtdVencedores = parseInt(document.getElementById('sortQtdVencedores').value) || 1;
    var modParticipa = document.getElementById('sortModParticipa').checked;
    var regras = quillSortRegras ? quillSortRegras.root.innerHTML.trim() : '';
    // Se só tiver tags vazias do Quill, considerar vazio
    if (quillSortRegras && !quillSortRegras.getText().trim()) regras = '';

    if (!premio) return showToast('Informe o item/prêmio do sorteio.', 'error');
    if (!dataFim) return showToast('Informe a data de encerramento.', 'error');

    try {
        var resp = await fetch('/api/sorteios/' + _commId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ premio: premio, data_fim: dataFim, qtd_vencedores: qtdVencedores, mod_participa: modParticipa, regras: regras })
        });
        var data = await resp.json();
        if (data.success) {
            showToast(data.message || 'Sorteio criado!', 'success');
            loadSorteios();
        } else {
            showToast(data.message || 'Erro ao criar sorteio.', 'error');
        }
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
}

async function verParticipantes(sorteioId) {
    try {
        var resp = await fetch('/api/sorteios/' + _commId + '/' + sorteioId + '/participantes');
        var data = await resp.json();
        if (!data.success) return showToast(data.message || 'Erro ao carregar participantes.', 'error');

        var parts = data.participantes;
        var html = '<div class="modal-overlay" id="modalParticipantes" onclick="if(event.target===this)this.remove()">';
        html += '<div class="modal-box">';
        html += '  <div class="modal-header">';
        html += '    <h3>👥 Participantes (' + parts.length + ')</h3>';
        html += '    <button class="modal-close" onclick="document.getElementById(\'modalParticipantes\').remove()">&times;</button>';
        html += '  </div>';
        html += '  <div class="modal-body">';
        if (parts.length === 0) {
            html += '<p style="text-align:center; color:#999; font-size:12px; padding:20px 0;">Nenhum participante ainda.</p>';
        } else {
            for (var i = 0; i < parts.length; i++) {
                var p = parts[i];
                html += '<div class="part-item">';
                html += '  <a href="profile.php?uid=' + p.id + '"><img src="' + getFoto(p.foto_perfil) + '" class="part-pic"></a>';
                html += '  <a href="profile.php?uid=' + p.id + '" class="part-nome">' + escapeHtml(p.nome) + '</a>';
                html += '</div>';
            }
        }
        html += '  </div>';
        html += '</div>';
        html += '</div>';

        var old = document.getElementById('modalParticipantes');
        if (old) old.remove();
        document.body.insertAdjacentHTML('beforeend', html);
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
}

async function verVencedores(sorteioId) {
    try {
        var resp = await fetch('/api/sorteios/' + _commId + '/' + sorteioId + '/participantes');
        var data = await resp.json();
        // Instead of participantes, get vencedores from loaded sorteios
        var sorteio = null;
        for (var i = 0; i < window._lastSorteios.length; i++) {
            if (window._lastSorteios[i].id === sorteioId) {
                sorteio = window._lastSorteios[i];
                break;
            }
        }
        var vens = sorteio && sorteio.vencedores ? sorteio.vencedores : [];
        var html = '<div class="modal-overlay" id="modalVencedores" onclick="if(event.target===this)this.remove()">';
        html += '<div class="modal-box">';
        html += '  <div class="modal-header">';
        html += '    <h3>🏆 Vencedores (' + vens.length + ')</h3>';
        html += '    <button class="modal-close" onclick="document.getElementById(\'modalVencedores\').remove()">&times;</button>';
        html += '  </div>';
        html += '  <div class="modal-body">';
        for (var i = 0; i < vens.length; i++) {
            var v = vens[i];
            html += '<div class="part-item">';
            if (v.is_membro) {
                html += '  <a href="profile.php?uid=' + v.id + '"><img src="' + getFoto(v.foto_perfil) + '" class="part-pic"></a>';
                html += '  <a href="profile.php?uid=' + v.id + '" class="part-nome">' + escapeHtml(v.nome) + '</a>';
            } else {
                html += '  <img src="' + getFoto(v.foto_perfil) + '" class="part-pic" style="opacity:0.5;">';
                html += '  <strong style="font-size:12px; color:#999;">' + escapeHtml(v.nome) + '</strong>';
            }
            html += '</div>';
        }
        html += '  </div>';
        html += '</div>';
        html += '</div>';
        var old = document.getElementById('modalVencedores');
        if (old) old.remove();
        document.body.insertAdjacentHTML('beforeend', html);
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
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

function sanitizeHtml(str) {
    if (!str) return '';
    var allowed = ['p','br','strong','em','u','s','b','i','h1','h2','h3','ul','ol','li','blockquote','a','span','sub','sup'];
    var allowedAttrs = ['href','target','style','class'];
    var tmp = document.createElement('div');
    tmp.innerHTML = str;
    function clean(el) {
        var children = Array.from(el.childNodes);
        children.forEach(function(child) {
            if (child.nodeType === 3) return;
            if (child.nodeType === 1) {
                var tag = child.tagName.toLowerCase();
                if (allowed.indexOf(tag) === -1) {
                    while (child.firstChild) el.insertBefore(child.firstChild, child);
                    el.removeChild(child);
                } else {
                    Array.from(child.attributes).forEach(function(attr) {
                        if (allowedAttrs.indexOf(attr.name) === -1) child.removeAttribute(attr.name);
                    });
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
