<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Depoimentos</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .write-box { background: #f4f7fc; border: 1px dashed #a5bce3; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: right; }
    .write-box textarea { width: 100%; height: 80px; padding: 10px; border: 1px solid #ccc; border-radius: 3px; font-family: Arial; font-size: 11px; margin-bottom: 10px; resize: vertical; box-sizing: border-box; }
    
    .item-box { display: flex; gap: 15px; padding: 15px; background: #fdfdfd; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 10px; }
    .item-pic { width: 60px; height: 60px; flex-shrink: 0; background: #e4ebf5; border: 1px solid #c0d0e6; border-radius: 3px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .item-pic img { width: 100%; height: 100%; object-fit: cover; }
    .item-content { flex: 1; }
    .item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .item-header a { font-weight: bold; font-size: 12px; }
    .item-date { font-size: 9px; color: #888; }
    .item-text { color: #444; line-height: 1.4; font-style: italic; font-size: 11px; margin-bottom: 10px; word-wrap: break-word; }
    
    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 10px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }
    .btn-success-outline { color: #2a6b2a; border-color: #8bc59e; background: #e4f2e9; }
    .btn-success-outline:hover { background: #d1e8d9; border-color: #2a6b2a; }
    .btn-danger-outline { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-danger-outline:hover { background: #ffe6e6; border-color: #cc0000; }
    
    .dep-pendente { border-left: 3px solid #f39c12 !important; background: #fffdf5 !important; }
    .pendente-aviso { font-size: 10px; color: #f39c12; font-weight: bold; margin-bottom: 5px; display:block; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > 
            Depoimentos
        </div>
        <div class="card">
            <h1 class="orkut-name" id="dep-title" style="font-size: 22px;">Depoimentos <span style="font-size: 16px; color:#666;">(0)</span></h1>
            <div id="write-box-container"></div>
            <div id="dep-list">
                <div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; margin-top:15px;">
                    Carregando...
                </div>
            </div>
        </div>
    </div>
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script src="/js/mention.js"></script>
<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const min = String(d.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

function getFoto(foto, sexo) {
    if (!foto || foto.includes('default-avatar')) return getDefaultAvatar(sexo);
    return foto;
}

async function aprovarDepoimento(id) {
    try {
        const resp = await fetch('/api/depoimentos/' + id + '/aprovar', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        const data = await resp.json();
        if (data.success) {
            loadDepoimentos();
        } else {
            alert(data.message || 'Erro ao aprovar.');
        }
    } catch(e) { alert('Erro de conexão.'); }
}

async function recusarDepoimento(id) {
    showConfirm('Recusar e apagar este depoimento?', async function() {
        try {
            const resp = await fetch('/api/depoimentos/' + id + '/recusar', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
            const data = await resp.json();
            if (data.success) {
                loadDepoimentos();
            } else {
                alert(data.message || 'Erro ao recusar.');
            }
        } catch(e) { alert('Erro de conexão.'); }
    });
}

async function enviarDepoimento(destId) {
    const textarea = document.getElementById('dep-textarea');
    const msg = textarea.value.trim();
    if (!msg) { alert('Escreva algo antes de enviar.'); return; }

    try {
        const resp = await fetch('/api/depoimentos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: destId, mensagem: msg })
        });
        const data = await resp.json();
        if (data.success) {
            textarea.value = '';
            alert('Depoimento enviado! Aguardando aprovação.');
            loadDepoimentos();
        } else {
            alert(data.message || 'Erro ao enviar.');
        }
    } catch(e) { alert('Erro de conexão.'); }
}

let _depUid = null;
let _depIsOwn = false;

async function loadDepoimentos() {
    const urlParams = new URLSearchParams(window.location.search);
    const uidParam = urlParams.get('uid');
    
    // Determinar o uid do perfil
    const meResp = await fetch('/api/me');
    const meData = await meResp.json();
    if (!meData.success) { window.location.href = '/index.php'; return; }
    
    const myId = meData.user.id;
    _depUid = uidParam ? uidParam : myId;
    _depIsOwn = (String(_depUid) === String(myId));

    // Buscar depoimentos
    const resp = await fetch('/api/depoimentos/' + _depUid);
    const data = await resp.json();
    if (!data.success) return;

    const { depoimentos, perfil, isOwner } = data;
    const totalAprovados = depoimentos.filter(d => d.aprovado === 1).length;
    const total = isOwner ? depoimentos.length : totalAprovados;
    const hasPendingFromMe = !isOwner && depoimentos.some(d => d.remetente_id === myId && d.aprovado === 0);

    // Atualizar título
    document.title = 'Yorkut - Depoimentos de ' + perfil.nome;
    document.getElementById('dep-title').innerHTML = 'Depoimentos <span style="font-size: 16px; color:#666;">(' + total + ')</span>';
    
    // Breadcrumb
    const bc = document.getElementById('breadcrumb');
    bc.innerHTML = '<a href="/profile.php?uid=' + _depUid + '">Início</a> > Depoimentos de ' + escapeHtml(perfil.nome);

    // Write box (only for visitors, not own profile, and not if already has pending)
    const writeBox = document.getElementById('write-box-container');
    if (!_depIsOwn && !hasPendingFromMe) {
        writeBox.innerHTML = `
            <div class="write-box">
                <textarea id="dep-textarea" placeholder="Escreva um depoimento para ${escapeHtml(perfil.nome)} (Não será publicado até que ele(a) aprove)..."></textarea>
                <button type="button" onclick="enviarDepoimento('${_depUid}')" class="icon-action-btn" style="font-size: 11px; padding: 6px 15px;">Enviar Depoimento</button>
            </div>
        `;
        // Inicializar @menção no textarea de depoimentos
        initMention('#dep-textarea');
    } else {
        writeBox.innerHTML = '';
    }

    // Lista de depoimentos
    const list = document.getElementById('dep-list');
    if (depoimentos.length === 0) {
        list.innerHTML = `
            <div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; margin-top:15px;">
                Nenhum depoimento encontrado.
            </div>
        `;
        return;
    }

    let html = '';
    for (const dep of depoimentos) {
        const foto = getFoto(dep.remetente_foto, dep.remetente_sexo);
        const isPendente = dep.aprovado === 0;
        
        html += `<div class="item-box ${isPendente ? 'dep-pendente' : ''}">
            <div class="item-pic">
                <a href="/profile.php?uid=${dep.remetente_id}">
                    <img src="${foto}">
                </a>
            </div>
            <div class="item-content">
                <div class="item-header">
                    <a href="/profile.php?uid=${dep.remetente_id}">${escapeHtml(dep.remetente_nome)}</a>
                    <span class="item-date">${formatDate(dep.criado_em)}</span>
                </div>
                <div class="item-text">"${renderMentions(dep.mensagem.replace(/</g, '&lt;').replace(/>/g, '&gt;'))}"</div>`;

        if (isPendente && isOwner) {
            html += `
                <div style="margin-top:10px; border-top:1px dashed #ccc; padding-top:10px; display:flex; gap:10px; align-items:center;">
                    <span class="pendente-aviso" style="margin:0;">Aguardando aprovação</span>
                    <button type="button" onclick="aprovarDepoimento(${dep.id})" class="icon-action-btn btn-success-outline">✔️ Aceitar</button>
                    <button type="button" onclick="recusarDepoimento(${dep.id})" class="icon-action-btn btn-danger-outline">❌ Recusar</button>
                </div>`;
        } else if (isPendente && !isOwner && dep.remetente_id === myId) {
            html += `
                <div style="margin-top:10px; border-top:1px dashed #ccc; padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:10px; color:#f39c12; font-weight:bold;">⚠ Seu depoimento está aguardando aprovação.</span>
                    <button type="button" onclick="recusarDepoimento(${dep.id})" class="icon-action-btn btn-danger-outline">🗑️ Apagar</button>
                </div>`;
        } else if (isOwner && dep.aprovado === 1) {
            html += `
                <div style="margin-top:5px;">
                    <button type="button" onclick="recusarDepoimento(${dep.id})" class="icon-action-btn btn-danger-outline" style="font-size:9px;">🗑️ apagar</button>
                </div>`;
        }

        html += `</div></div>`;
    }

    list.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const uidParam = urlParams.get('uid');
    
    await loadLayout({ activePage: 'depoimentos' });
    
    // Se é próprio perfil ou sem uid, marcar depoimentos como vistos
    const meResp = await fetch('/api/me');
    const meData = await meResp.json();
    if (meData.success) {
        const myId = meData.user.id;
        const targetUid = uidParam ? uidParam : myId;
        if (String(targetUid) === String(myId)) {
            await fetch('/api/depoimentos/marcar-vistos', { method: 'POST' });
        }
    }
    
    await loadDepoimentos();
    
    startBadgePolling(15000);
});
</script>
</body>
</html>
