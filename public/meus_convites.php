<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Meus Convites</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .invite-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .invite-stats { background: #e8eef7; padding: 10px 15px; border-radius: 6px; border: 1px solid #c0d0e6; font-size: 13px; color: #3b5998; }

    .tokens-grid { display: flex; flex-direction: column; gap: 10px; }
    .token-card { background: #fff; border: 1px solid var(--line); padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .token-card:hover { border-color: var(--orkut-blue); background: #fdfdfd; }
    .token-code { background: #eef4ff; color: #1565c0; border: 1px dashed #3b5998; padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .token-code:hover { transform: scale(1.02); background: #dce7fa; }
    .token-status { font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
    .status-livre { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .status-usado { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .token-info { flex: 1; min-width: 200px; font-size: 12px; color: #555; }
    .token-info a { color: var(--link); font-weight: bold; text-decoration: none; }
    .token-info a:hover { text-decoration: underline; }
    .msg-box { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; font-size: 12px; text-align: center; }
    .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .btn-generate { background: var(--orkut-pink); color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
    .btn-generate:hover { background: #c92c85; }
    .btn-disabled { background: #ccc; cursor: not-allowed; color: #888; }
    .novo-token-flash { animation: flashToken 0.6s ease; }
    @keyframes flashToken { 0% { background: #ffe082; } 100% { background: #fff; } }
    @media (max-width: 768px) { .token-card { flex-direction: column; align-items: flex-start; } .token-code { width: 100%; text-align: center; font-size: 18px; padding: 15px; } }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col">
        <div class="breadcrumb">
            <a href="/profile.php">Início</a> > Meus Convites
        </div>
        <div class="card">
            <div class="invite-header">
                <h1 class="orkut-name" style="margin:0; font-size: 20px;">Meus Convites</h1>
                <div class="invite-stats" id="inviteStats">
                    Gerados: <b>0 / 10</b>
                </div>
            </div>

            <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                O Yorkut é uma comunidade fechada. Você pode gerar até 10 convites para chamar seus amigos. Clique no código azul para copiá-lo.
            </p>

            <div style="margin-bottom: 30px;" id="gerarArea">
                <button type="button" id="btnGerar" class="btn-generate" onclick="gerarConvite()">+ Gerar Novo Convite</button>
            </div>

            <div class="tokens-grid" id="tokensGrid">
            </div>
        </div>
    </div>
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

let _maxConvites = 10;
let _convitesRestantes = 10;

document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'meus_convites' }).then(() => {
        renderConvites();
    });
});

function renderConvites() {
    const convites = getConvitesData();
    const grid = document.getElementById('tokensGrid');
    const stats = document.getElementById('inviteStats');
    if (!grid) return;
    
    const totalGerados = convites.length;
    const livres = convites.filter(c => !c.usado).length;
    const usados = convites.filter(c => c.usado).length;
    
    _convitesRestantes = _maxConvites - totalGerados;
    if (_convitesRestantes < 0) _convitesRestantes = 0;
    
    stats.innerHTML = 'Gerados: <b>' + totalGerados + ' / ' + _maxConvites + '</b> | Livres: <b>' + livres + '</b> | Usados: <b>' + usados + '</b>';
    
    // Atualizar botao de gerar
    const btnGerar = document.getElementById('btnGerar');
    
    if (_convitesRestantes <= 0) {
        btnGerar.disabled = true;
        btnGerar.className = 'btn-generate btn-disabled';
        btnGerar.textContent = 'Limite de convites atingido';
    } else {
        btnGerar.disabled = false;
        btnGerar.className = 'btn-generate';
        btnGerar.textContent = '+ Gerar Novo Convite';
    }
    
    if (convites.length === 0) {
        grid.innerHTML = '<div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9;">Nenhum convite gerado ainda. Clique no botão acima para gerar!</div>';
        return;
    }
    
    grid.innerHTML = convites.map(c => {
        let infoHtml = 'Disponível para uso';
        if (c.usado && c.usado_por_nome) {
            const dataUso = c.usado_em ? new Date(c.usado_em).toLocaleDateString('pt-BR') + ' ' + new Date(c.usado_em).toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'}) : '';
            infoHtml = 'Usado por: <a href="profile.php?uid=' + c.usado_por_id + '">' + c.usado_por_nome + '</a>';
            if (dataUso) infoHtml += '<br><span style="font-size:11px;color:#999;">Em: ' + dataUso + '</span>';
        } else if (c.usado) {
            infoHtml = 'Usado';
        }
        return `
        <div class="token-card">
            <div>
                <span class="token-code" onclick="copyTokenText(this, '${c.token}')" title="Clique para copiar">
                    ${c.token}
                </span>
            </div>
            <div class="token-info">
                ${infoHtml}
            </div>
            <div>
                <span class="token-status ${c.usado ? 'status-usado' : 'status-livre'}">${c.usado ? 'USADO' : 'LIVRE'}</span>
            </div>
        </div>
    `;
    }).join('');
}

async function gerarConvite() {
    const btn = document.getElementById('btnGerar');
    btn.disabled = true;
    btn.textContent = 'Gerando...';
    
    try {
        const resp = await fetch('/api/gerar-convite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await resp.json();
        
        if (data.success) {
            _convitesRestantes = data.convites_restantes;
            
            // Recarregar dados do /api/me para atualizar _convitesData
            const meResp = await fetch('/api/me');
            const meData = await meResp.json();
            if (meData.success) {
                _convitesData = meData.convites || [];
                _maxConvites = meData.max_convites || 10;
            }
            
            renderConvites();
            
            // Flash no novo token
            const cards = document.querySelectorAll('.token-card');
            if (cards.length > 0) {
                const lastCard = cards[cards.length - 1];
                lastCard.classList.add('novo-token-flash');
                lastCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        } else {
            alert(data.message || 'Erro ao gerar convite.');
            btn.disabled = false;
            btn.className = 'btn-generate';
            btn.textContent = '+ Gerar Novo Convite';
        }
    } catch(err) {
        alert('Erro de conexão.');
        btn.disabled = false;
        btn.className = 'btn-generate';
        btn.textContent = '+ Gerar Novo Convite';
    }
}

function copyTokenText(element, text) {
    navigator.clipboard.writeText(text).then(() => {
        let originalContent = element.innerHTML;
        element.innerHTML = "✅ Copiado!";
        element.style.background = "#d4edda";
        element.style.color = "#155724";
        element.style.borderColor = "#c3e6cb";
        setTimeout(() => {
            element.innerHTML = originalContent;
            element.style.background = "";
            element.style.color = "";
            element.style.borderColor = "";
        }, 2000);
    });
}
</script>
</body>
</html>
