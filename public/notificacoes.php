<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Minhas Notificações</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .notif-toolbar { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 15px; gap: 10px; flex-wrap: wrap; }
    .notif-toolbar-left { display: flex; align-items: center; gap: 10px; }
    .notif-toolbar-right { display: flex; align-items: center; gap: 8px; }
    .notif-btn { padding: 5px 12px; border-radius: 3px; border: 1px solid #ccc; background: #f5f5f5; color: #333; font-size: 11px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .notif-btn:hover { background: #e8e8e8; border-color: #999; }
    .notif-btn.danger { background: #fff0f0; border-color: #e74c3c; color: #c0392b; }
    .notif-btn.danger:hover { background: #fde2e2; }
    .notif-btn.primary { background: var(--orkut-blue); border-color: var(--orkut-blue); color: #fff; }
    .notif-btn.primary:hover { opacity: 0.9; }

    .notif-count { font-size: 14px; color: #666; font-weight: normal; }

    .notif-page-list { display: flex; flex-direction: column; gap: 0; }
    .notif-page-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px 12px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; transition: background 0.15s; position: relative; }
    .notif-page-item:hover { background: #f4f7fc; }
    .notif-page-item.unread { background: #fffdf5; border-left: 3px solid var(--orkut-pink); }
    .notif-page-item:last-child { border-bottom: none; }

    .notif-icon { width: 40px; height: 40px; flex-shrink: 0; background: #e8f0fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .notif-pic { width: 40px; height: 40px; flex-shrink: 0; border-radius: 4px; overflow: hidden; border: 1px solid #ccc; background: #e4ebf5; }
    .notif-pic img { width: 100%; height: 100%; object-fit: cover; }
    .notif-page-content { flex: 1; min-width: 0; }
    .notif-page-title { font-size: 12px; font-weight: bold; color: var(--title); margin-bottom: 3px; line-height: 1.4; }
    .notif-page-msg { font-size: 11px; color: #555; line-height: 1.4; margin-bottom: 4px; word-break: break-word; }
    .notif-page-date { font-size: 10px; color: #999; }

    .notif-delete-btn { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #ccc; cursor: pointer; font-size: 14px; padding: 2px 6px; border-radius: 3px; transition: 0.15s; }
    .notif-delete-btn:hover { background: #fee; color: #e74c3c; }

    .notif-empty { text-align: center; padding: 40px 20px; color: #999; font-style: italic; border: 1px dashed var(--line); border-radius: 4px; background: #f9f9f9; font-size: 13px; }

    @keyframes notifFadeOut {
        from { opacity: 1; max-height: 100px; padding: 14px 12px; }
        to { opacity: 0; max-height: 0; padding: 0 12px; overflow: hidden; }
    }
    .notif-removing { animation: notifFadeOut 0.3s ease forwards; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> &gt; Minhas Notificações</div>
        <div class="card">
            <div class="notif-toolbar">
                <div class="notif-toolbar-left">
                    <h1 class="orkut-name" style="font-size: 20px; margin: 0;">🔔 Minhas Notificações <span class="notif-count" id="notif-total-count"></span></h1>
                </div>
                <div class="notif-toolbar-right" id="notif-toolbar-btns">
                    <button onclick="marcarTodasLidas()" class="notif-btn primary" id="btn-marcar-lidas" style="display:none;">✓ Marcar todas como lidas</button>
                    <button onclick="excluirTodasNotificacoes()" class="notif-btn danger" id="btn-excluir-todas" style="display:none;">🗑 Excluir todas</button>
                </div>
            </div>
            <div id="notif-list-container">
                <div style="text-align:center; padding:30px; color:#999;">Carregando notificações...</div>
            </div>
        </div>
    </div>
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'notificacoes' }).then(() => {
        carregarNotificacoes();
    });
});

let _notificacoesList = [];

async function carregarNotificacoes() {
    try {
        const resp = await fetch('/api/notificacoes');
        const data = await resp.json();
        if (!data.success) { showToast('Erro ao carregar notificações.', 'error'); return; }

        _notificacoesList = data.notificacoes || [];
        renderNotificacoes();

        // Marcar como lidas automaticamente ao visitar a página
        if (data.naoLidas > 0) {
            fetch('/api/notificacoes/marcar-lidas', { method: 'POST' });
        }
    } catch(err) {
        document.getElementById('notif-list-container').innerHTML = '<div class="notif-empty">Erro ao carregar notificações.</div>';
    }
}

function renderNotificacoes() {
    const container = document.getElementById('notif-list-container');
    const countEl = document.getElementById('notif-total-count');
    const btnLidas = document.getElementById('btn-marcar-lidas');
    const btnExcluir = document.getElementById('btn-excluir-todas');

    countEl.textContent = '(' + _notificacoesList.length + ')';

    if (_notificacoesList.length === 0) {
        container.innerHTML = '<div class="notif-empty">📭 Você não possui nenhuma notificação no momento.</div>';
        btnLidas.style.display = 'none';
        btnExcluir.style.display = 'none';
        return;
    }

    const temNaoLida = _notificacoesList.some(n => !n.lida);
    btnLidas.style.display = temNaoLida ? '' : 'none';
    btnExcluir.style.display = '';

    let html = '<div class="notif-page-list">';
    _notificacoesList.forEach(function(n) {
        const isUnread = !n.lida;
        const dtRaw = n.criado_em || '';
        let dtFormatted = dtRaw;
        try {
            const d = new Date(dtRaw.replace(' ', 'T') + '-03:00');
            dtFormatted = d.toLocaleDateString('pt-BR', {day:'2-digit', month:'long', year:'numeric'}) + ' às ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
        } catch(e) {}

        const iconMap = {
            'denuncia_resposta': '📩',
            'anuncio': '📢'
        };
        const icon = iconMap[n.tipo] || '🔔';

        // Determinar se mostra foto do remetente ou ícone
        let avatarHtml = '';
        if (n.tipo === 'anuncio') {
            avatarHtml = '<div class="notif-icon" style="background:#fff3cd;">📢</div>';
        } else if (n.remetente_foto || n.remetente_id) {
            let foto = n.remetente_foto || '';
            if (!foto) foto = 'perfilsemfoto.jpg';
            if (!foto.startsWith('http') && !foto.startsWith('/')) foto = '/' + foto;
            avatarHtml = '<div class="notif-pic"><img src="' + foto + '" alt=""></div>';
        } else {
            avatarHtml = '<div class="notif-icon">' + icon + '</div>';
        }

        // Determinar texto do link baseado no tipo
        let linkText = 'Ver →';
        if (n.tipo === 'denuncia_resposta') linkText = 'Ver denúncia →';
        else if (n.tipo === 'convite_comunidade') linkText = 'Ver comunidade →';
        else if (n.tipo === 'anuncio') linkText = 'Ver anúncio →';

        html += '<div class="notif-page-item' + (isUnread ? ' unread' : '') + '" id="notif-item-' + n.id + '">';
        html += avatarHtml;
        html += '<div class="notif-page-content">';
        html += '<div class="notif-page-title">' + escapeHtml(n.titulo || '') + '</div>';
        html += '<div class="notif-page-msg">' + escapeHtml(n.mensagem || '') + '</div>';
        if (n.link) {
            html += '<a href="' + n.link + '" style="font-size:11px; color:var(--link);">' + linkText + '</a> ';
        }
        html += '<div class="notif-page-date">' + dtFormatted + '</div>';
        html += '</div>';
        html += '<button class="notif-delete-btn" onclick="excluirNotificacao(' + n.id + ', event)" title="Excluir notificação">✕</button>';
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

async function excluirNotificacao(id, event) {
    if (event) event.stopPropagation();
    try {
        const resp = await fetch('/api/notificacoes/excluir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await resp.json();
        if (data.success) {
            const item = document.getElementById('notif-item-' + id);
            if (item) {
                item.classList.add('notif-removing');
                setTimeout(() => {
                    _notificacoesList = _notificacoesList.filter(n => n.id !== id);
                    renderNotificacoes();
                }, 300);
            }
        } else {
            showToast(data.message || 'Erro ao excluir.', 'error');
        }
    } catch(err) {
        showToast('Erro ao excluir notificação.', 'error');
    }
}

function excluirTodasNotificacoes() {
    showConfirm('Tem certeza que deseja excluir todas as notificações?', async () => {
        try {
            const resp = await fetch('/api/notificacoes/excluir-todas', { method: 'POST' });
            const data = await resp.json();
            if (data.success) {
                _notificacoesList = [];
                renderNotificacoes();
                showToast('Todas as notificações foram excluídas.');
            } else {
                showToast('Erro ao excluir.', 'error');
            }
        } catch(err) {
            showToast('Erro ao excluir notificações.', 'error');
        }
    });
}

async function marcarTodasLidas() {
    try {
        const resp = await fetch('/api/notificacoes/marcar-lidas', { method: 'POST' });
        const data = await resp.json();
        if (data.success) {
            _notificacoesList.forEach(n => n.lida = 1);
            renderNotificacoes();
            showToast('Todas as notificações foram marcadas como lidas.');
        }
    } catch(err) {
        showToast('Erro.', 'error');
    }
}
</script>
</body>
</html>
