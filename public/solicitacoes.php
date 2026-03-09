<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Solicitações de Amizade</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .requests-list { display: flex; flex-direction: column; gap: 10px; }
    .request-item { display: flex; gap: 15px; background: #fdfdfd; border: 1px solid var(--line); padding: 15px; border-radius: 4px; align-items: center; transition: 0.2s; }
    .request-item:hover { border-color: #a5bce3; background: #f4f7fc; }

    .request-pic { width: 60px; height: 60px; background: #e4ebf5; border: 1px solid #c0d0e6; overflow: hidden; border-radius: 3px; flex-shrink: 0; display:flex; align-items:center; justify-content:center;}
    .request-pic img { width: 100%; height: 100%; object-fit: cover; }

    .request-info { flex: 1; }
    .request-name { font-size: 14px; font-weight: bold; margin-bottom: 5px; display: inline-block; color: var(--link); text-decoration: none; }
    .request-name:hover { text-decoration: underline; }
    .request-meta { color: #666; font-size: 11px; }

    .request-actions { display: flex; gap: 5px; }
    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 6px 15px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }

    .btn-accept { color: #2a6b2a; border-color: #8bc59e; background: #e4f2e9; }
    .btn-accept:hover { background: #d1e8d9; border-color: #2a6b2a; }

    .btn-reject { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-reject:hover { background: #ffe6e6; border-color: #cc0000; }

    .empty-msg { text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Solicitações de Amizade</div>
        <div class="card">
            <h1 class="orkut-name" id="requests-title" style="font-size: 22px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 20px;">
                Solicitações Pendentes <span style="font-size: 16px; color:#666;" id="requests-count">(0)</span>
            </h1>
            <div class="requests-list" id="requests-list">
                <div class="empty-msg">Carregando...</div>
            </div>
        </div>
    </div>
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T') + (dateStr.includes('Z') ? '' : '-03:00'));
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    return dd + '/' + mm + '/' + yyyy + ' às ' + hh + ':' + mi;
}

async function loadRequests() {
    try {
        const resp = await fetch('/api/amizade/pendentes');
        const data = await resp.json();
        if (!data.success) return;

        const total = data.total || 0;
        document.getElementById('requests-count').textContent = '(' + total + ')';

        const list = document.getElementById('requests-list');

        if (total === 0) {
            list.innerHTML = '<div class="empty-msg">Você não possui nenhuma solicitação de amizade pendente no momento.</div>';
            return;
        }

        let html = '';
        data.pendentes.forEach(function(p) {
            const foto = p.foto_perfil || getDefaultAvatar(p.sexo);
            const loc = [p.cidade, p.estado, 'Brasil'].filter(Boolean).join(', ');
            const dateStr = p.criado_em ? formatDate(p.criado_em) : '';

            html += '<div class="request-item" id="req-item-' + p.request_id + '">';
            html += '<div class="request-pic">';
            html += '<a href="profile.php?uid=' + p.remetente_id + '"><img src="' + foto + '" alt=""></a>';
            html += '</div>';

            html += '<div class="request-info">';
            html += '<a href="profile.php?uid=' + p.remetente_id + '" class="request-name">' + escapeHtml(p.nome) + '</a>';
            html += ' <a href="profile.php?uid=' + p.remetente_id + '" style="font-size:9px; color:#999; margin-left:5px; text-decoration:underline;">[ver perfil]</a>';
            if (loc) html += '<div class="request-meta">' + escapeHtml(loc) + '</div>';
            if (dateStr) html += '<div style="font-size: 9px; color: #aaa; margin-top: 3px;">Recebido em ' + dateStr + '</div>';
            html += '</div>';

            html += '<div class="request-actions">';
            html += '<button type="button" onclick="acceptRequest(' + p.request_id + ')" class="icon-action-btn btn-accept">✔️ Aceitar</button>';
            html += '<button type="button" onclick="rejectRequest(' + p.request_id + ')" class="icon-action-btn btn-reject">❌ Recusar</button>';
            html += '</div>';
            html += '</div>';
        });
        list.innerHTML = html;
    } catch(err) {
        console.error('Erro ao carregar solicitações:', err);
    }
}

async function acceptRequest(reqId) {
    try {
        const resp = await fetch('/api/amizade/aceitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: reqId })
        });
        const data = await resp.json();
        if (data.success) {
            const item = document.getElementById('req-item-' + reqId);
            if (item) {
                item.style.background = '#e4f2e9';
                item.innerHTML = '<div style="padding:10px;color:#2a6b2a;font-weight:bold;text-align:center;width:100%;">✔️ Amizade aceita!</div>';
                setTimeout(function() { item.style.display = 'none'; loadRequests(); }, 1500);
            }
            // Update header badge
            const badge = document.getElementById('hdr-req-badge');
            if (badge) {
                let c = parseInt(badge.innerText) - 1;
                if (c > 0) badge.innerText = c;
                else badge.style.display = 'none';
            }
        } else {
            alert(data.message || 'Erro ao aceitar.');
        }
    } catch(err) { alert('Erro ao aceitar.'); }
}

async function rejectRequest(reqId) {
    showConfirm('Tem certeza que deseja recusar este convite?', async function() {
        try {
            const resp = await fetch('/api/amizade/recusar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: reqId })
            });
            const data = await resp.json();
            if (data.success) {
                const item = document.getElementById('req-item-' + reqId);
                if (item) item.style.display = 'none';
                loadRequests();
                // Update header badge
                const badge = document.getElementById('hdr-req-badge');
                if (badge) {
                    let c = parseInt(badge.innerText) - 1;
                    if (c > 0) badge.innerText = c;
                    else badge.style.display = 'none';
                }
            } else {
                alert(data.message || 'Erro ao recusar.');
            }
        } catch(err) { alert('Erro ao recusar.'); }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadLayout({ activePage: 'solicitacoes' });
    loadRequests();
});
</script>
</body>
</html>
