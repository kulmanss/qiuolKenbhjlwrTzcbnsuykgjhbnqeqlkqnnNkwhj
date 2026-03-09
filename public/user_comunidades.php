<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Comunidades</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .comm-list-section { margin-bottom: 25px; }
    .comm-list-section h3 { font-size: 14px; color: var(--title); border-bottom: 1px solid var(--line); padding-bottom: 5px; margin-bottom: 15px; }
    
    .comm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .comm-card { display: flex; gap: 15px; padding: 12px; border: 1px solid var(--line); border-radius: 4px; background: #fdfdfd; align-items: center; transition: 0.2s; }
    .comm-card:hover { border-color: #a5bce3; background: #f4f7fc; }
    
    .comm-pic { 
        width: 80px;
        aspect-ratio: 3 / 4; 
        flex-shrink: 0; 
        background: #e4ebf5; 
        border: 1px solid #c0d0e6; 
        border-radius: 3px; 
        overflow: hidden; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
    }
    .comm-pic img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover;
    }
    
    .comm-info { flex: 1; overflow: hidden; }
    .comm-name { font-weight: bold; font-size: 13px; color: var(--link); text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px; }
    .comm-name:hover { text-decoration: underline; }
    .comm-meta { font-size: 10px; color: #666; line-height: 1.4; }

    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 10px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }
    .btn-danger-outline { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-danger-outline:hover { background: #ffe6e6; border-color: #cc0000; }
    
    .empty-state { text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; }

    @media (max-width: 768px) { .comm-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > Comunidades
        </div>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 class="orkut-name" style="font-size: 22px; margin: 0;" id="page-title">
                    Minhas Comunidades <span style="font-size: 16px; color:#666;" id="comm-count">(0)</span>
                </h1>
                <a href="comunidades.php?action=create" class="icon-action-btn" style="padding: 6px 12px; font-size: 11px;" id="btn-criar">➕ Criar Comunidade</a>
            </div>
            <div id="comm-content">
                <div class="empty-state">Carregando...</div>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    let uid = urlParams.get('uid');
    
    // Se não tem uid, buscar o próprio e redirecionar
    if (!uid) {
        try {
            const meResp = await fetch('/api/me');
            const meData = await meResp.json();
            if (meData.success && meData.user) {
                window.location.replace('/user_comunidades.php?uid=' + meData.user.id);
                return;
            }
        } catch(e) {}
        document.getElementById('comm-content').innerHTML = '<div class="empty-state">Usuário não especificado.</div>';
        return;
    }

    await loadLayout({ activePage: 'comunidades' });

    try {
        const resp = await fetch('/api/user-comunidades/' + uid);
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('comm-content').innerHTML = '<div class="empty-state">' + (data.message || 'Erro ao carregar.') + '</div>';
            return;
        }

        const { owned, joined, perfil, isOwner, total } = data;

        // Atualizar título da página
        document.title = isOwner ? 'Yorkut - Minhas Comunidades' : 'Yorkut - Comunidades de ' + perfil.nome;
        
        // Atualizar breadcrumb
        document.getElementById('breadcrumb').innerHTML = isOwner
            ? '<a href="/profile.php">Início</a> > Minhas Comunidades'
            : '<a href="/profile.php?uid=' + uid + '">Início</a> > Comunidades de ' + perfil.nome;

        // Atualizar título
        const titleText = isOwner ? 'Minhas Comunidades' : 'Comunidades de ' + perfil.nome;
        document.getElementById('page-title').innerHTML = 
            titleText + ' <span style="font-size: 16px; color:#666;">(' + total + ')</span>';

        // Esconder botão criar se não é o dono
        if (!isOwner) {
            document.getElementById('btn-criar').style.display = 'none';
        }

        // Contagem
        document.getElementById('comm-count');

        if (total === 0) {
            const msg = isOwner 
                ? 'Você ainda não participa de nenhuma comunidade.' 
                : perfil.nome + ' ainda não participa de nenhuma comunidade.';
            document.getElementById('comm-content').innerHTML = '<div class="empty-state">' + msg + '</div>';
            return;
        }

        let html = '';

        // Seção: Comunidades que é dono
        if (owned.length > 0) {
            html += '<div class="comm-list-section">';
            html += '<h3>👑 Comunidades que ' + (isOwner ? 'sou Dono' : 'é Dono') + ' (' + owned.length + ')</h3>';
            html += '<div class="comm-grid">';
            for (const c of owned) {
                const foto = c.foto || 'semfotocomunidade.jpg';
                const data_criacao = formatDate(c.criado_em);
                html += '<div class="comm-card">';
                html += '  <div class="comm-pic"><a href="comunidades.php?id=' + c.id + '"><img src="' + foto + '"></a></div>';
                html += '  <div class="comm-info">';
                html += '    <a href="comunidades.php?id=' + c.id + '" class="comm-name">' + escapeHtml(c.nome) + '</a>';
                html += '    <div class="comm-meta">';
                html += '      <b>' + escapeHtml(c.categoria) + '</b><br>';
                html += '      Desde ' + data_criacao;
                html += '    </div>';
                html += '  </div>';
                if (isOwner) {
                    html += '  <a href="comunidades.php?id=' + c.id + '" class="icon-action-btn" style="padding:4px 8px; font-weight:normal;">Gerenciar</a>';
                }
                html += '</div>';
            }
            html += '</div></div>';
        }

        // Seção: Comunidades que participa
        if (joined.length > 0) {
            html += '<div class="comm-list-section" style="margin-bottom:0;">';
            html += '<h3>👥 Comunidades que ' + (isOwner ? 'participo' : 'participa') + ' (' + joined.length + ')</h3>';
            html += '<div class="comm-grid">';
            for (const c of joined) {
                const foto = c.foto || 'semfotocomunidade.jpg';
                const data_criacao = formatDate(c.criado_em);
                html += '<div class="comm-card" id="comm-card-' + c.id + '">';
                html += '  <div class="comm-pic"><a href="comunidades.php?id=' + c.id + '"><img src="' + foto + '"></a></div>';
                html += '  <div class="comm-info">';
                html += '    <a href="comunidades.php?id=' + c.id + '" class="comm-name">' + escapeHtml(c.nome) + '</a>';
                html += '    <div class="comm-meta">';
                html += '      <b>' + escapeHtml(c.categoria) + '</b><br>';
                html += '      Desde ' + data_criacao;
                html += '    </div>';
                html += '  </div>';
                if (isOwner) {
                    html += '  <button onclick="leaveCommunity(' + c.id + ', \'' + escapeHtml(c.nome).replace(/'/g, "\\'") + '\')" class="icon-action-btn btn-danger-outline" style="font-weight:normal;">🗑️ Sair</button>';
                }
                html += '</div>';
            }
            html += '</div></div>';
        }

        document.getElementById('comm-content').innerHTML = html;

    } catch (err) {
        console.error('Erro ao carregar comunidades:', err);
        document.getElementById('comm-content').innerHTML = '<div class="empty-state">Erro ao carregar comunidades.</div>';
    }
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return day + '/' + month + '/' + year;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function leaveCommunity(commId, commName) {
    if (!confirm('Deseja realmente sair da comunidade ' + commName + '?')) return;
    
    try {
        const resp = await fetch('/api/comunidades/sair', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId })
        });
        const data = await resp.json();
        
        if (data.success) {
            const card = document.getElementById('comm-card-' + commId);
            if (card) {
                card.style.transition = 'opacity 0.3s';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
            if (typeof showToast === 'function') showToast(data.message, 'success');
        } else {
            if (typeof showToast === 'function') showToast(data.message, 'error');
            else alert(data.message);
        }
    } catch (err) {
        console.error('Erro ao sair da comunidade:', err);
        alert('Erro ao sair da comunidade.');
    }
}
</script>
</body>
</html>
