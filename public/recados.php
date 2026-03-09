<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Recados</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/recados.css">
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > 
            Minha página de recados
        </div>

        <div class="card">
            <div class="alert-publico" id="alertPublico">
                ⚠️ <b>Lembrete:</b> Os recados são públicos e ficam visíveis para todos que visitarem este perfil.
                Para enviar algo particular, use a aba <a href="/mensagens_particular.php" id="linkMsgParticular">Mensagens Particular</a>.
            </div>

            <div style="margin-bottom: 20px;" id="scrapFormWrapper">
                <form id="scrapForm">
                    <textarea name="mensagem" id="scrapMessage" class="editor-area-simple" placeholder="Escreva um recado público..." required></textarea>
                    
                    <div id="loadingBarContainer" class="loading-container">
                        <div id="loadingBar" class="loading-bar"></div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="submit" id="btnSubmitScrap" class="btn-action">enviar recado</button>
                    </div>
                </form>
            </div>
            
            <!-- Tabs: Recebidos / Enviados -->
            <div class="msg-tabs" style="margin-bottom:10px;">
                <a href="javascript:void(0);" class="msg-tab active" id="tabRecebidos" onclick="switchTab('recebidos')">📥 Recebidos</a>
                <a href="javascript:void(0);" class="msg-tab" id="tabEnviados" onclick="switchTab('enviados')">📤 Enviados</a>
            </div>

            <h1 class="orkut-name" style="font-size: 22px;" id="recadosTitle">
                Meus Recados 
                <span id="recadosCount" style="font-size: 16px; color:#666;">(0)</span>
            </h1>
            
            <div class="toolbar-top" id="toolbarTop">
                <div class="toolbar-left" id="toolbarLeft">
                    <button class="btn-delete" id="btnExcluir" style="margin-bottom:5px;" onclick="excluirSelecionados()">🗑️ excluir selecionados</button><br>
                    <div class="select-links">Selecionar: <a href="javascript:void(0);" onclick="selecionarTodos(true)">Todos</a>, <a href="javascript:void(0);" onclick="selecionarTodos(false)">Nenhum</a></div>
                </div>

                <div class="pagination-controls" style="text-align: right;">
                    <select id="limitSelect" onchange="changeLimit(this.value)" style="padding:4px; border:1px solid #ccc; font-size:11px;">
                        <option value="5">Ver 5</option>
                        <option value="10" selected>Ver 10</option>
                        <option value="15">Ver 15</option>
                    </select>
                    <div id="paginationLinks" style="margin-top:5px;"></div>
                </div>
            </div>

            <div class="scrap-list" id="scrapList">
                <div class="empty-msg">Carregando recados...</div>
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
let _currentTab = 'recebidos';
let _currentPage = 1;
let _currentLimit = 10;
let _profileUid = null; // uid do perfil sendo visualizado
let _isOwnProfile = true;
let _myId = null;

document.addEventListener('DOMContentLoaded', async () => {
    // Marcar recados como vistos ANTES de carregar layout (garante que /api/me já retorna 0)
    try { await fetch('/api/recados/marcar-vistos', { method: 'POST' }); } catch(e) {}

    await loadLayout({ activePage: 'recados' });

    const me = getUserData();
    _myId = me.id;

    // Determinar de quem é a página de recados
    if (isVisiting()) {
        const user = getVisitingUser();
        const uid = getVisitingUid();
        _profileUid = uid;
        _isOwnProfile = false;

        // Atualizar breadcrumb
        document.getElementById('breadcrumb').innerHTML = 
            '<a href="/profile.php">Início</a> > <a href="/profile.php?uid=' + uid + '">' + escapeHtml(user.nome) + '</a> > Recados';
        
        // Atualizar titulo
        document.getElementById('recadosTitle').innerHTML = 
            'Recados de ' + escapeHtml(user.nome) + ' <span id="recadosCount" style="font-size:16px;color:#666;">(0)</span>';
        
        // Esconder tab enviados (visitando outro perfil)
        document.getElementById('tabEnviados').style.display = 'none';
        
        // Mudar placeholder
        document.getElementById('scrapMessage').placeholder = 'Escreva um recado para ' + user.nome + '...';
        
        // Atualizar link de mensagem particular
        const linkMsg = document.getElementById('linkMsgParticular');
        if (linkMsg) linkMsg.href = '/mensagens_particular.php?to=' + uid;
    } else {
        _profileUid = me.id;
        _isOwnProfile = true;
    }

    // Inicializar @menção no textarea de recados
    initMention('#scrapMessage');

    // Enviar recado
    document.getElementById('scrapForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const texto = document.getElementById('scrapMessage').value.trim();
        if (!texto) return;

        const btn = document.getElementById('btnSubmitScrap');
        const container = document.getElementById('loadingBarContainer');
        const bar = document.getElementById('loadingBar');

        btn.disabled = true;
        btn.innerText = 'Enviando...';
        container.style.display = 'block';
        setTimeout(() => { bar.style.width = '100%'; }, 50);

        // Esperar a barra carregar (efeito visual)
        await new Promise(r => setTimeout(r, 1500));

        try {
            const resp = await fetch('/api/recados', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    destinatario_id: _profileUid,
                    mensagem: texto
                })
            });
            const data = await resp.json();
            
            if (data.success) {
                btn.innerText = 'Enviado! ✅';
                btn.style.background = '#4CAF50';
                btn.style.color = '#fff';
                btn.style.borderColor = '#388E3C';
                document.getElementById('scrapMessage').value = '';
                
                // Recarregar recados
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerText = 'enviar recado';
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.style.borderColor = '';
                    container.style.display = 'none';
                    bar.style.width = '0%';
                    loadRecados();
                }, 1500);
            } else {
                alert(data.message || 'Erro ao enviar recado.');
                btn.disabled = false;
                btn.innerText = 'enviar recado';
                container.style.display = 'none';
                bar.style.width = '0%';
            }
        } catch(err) {
            alert('Erro de conexão.');
            btn.disabled = false;
            btn.innerText = 'enviar recado';
            container.style.display = 'none';
            bar.style.width = '0%';
        }
    });

    // Carregar recados
    loadRecados();
});

function switchTab(tab) {
    _currentTab = tab;
    _currentPage = 1;
    document.getElementById('tabRecebidos').classList.toggle('active', tab === 'recebidos');
    document.getElementById('tabEnviados').classList.toggle('active', tab === 'enviados');
    
    // Esconder form de envio na aba enviados
    document.getElementById('scrapFormWrapper').style.display = tab === 'recebidos' ? 'block' : 'none';
    document.getElementById('alertPublico').style.display = tab === 'recebidos' ? 'block' : 'none';
    
    // Mostrar controles de delete em ambas as abas (dono pode excluir recebidos, remetente pode excluir enviados)
    if (_isOwnProfile) {
        document.getElementById('btnExcluir').style.display = '';
        document.querySelector('.select-links').style.display = '';
    }
    
    loadRecados();
}

function changeLimit(val) {
    _currentLimit = parseInt(val);
    _currentPage = 1;
    loadRecados();
}

async function loadRecados() {
    const list = document.getElementById('scrapList');
    list.innerHTML = '<div class="empty-msg">Carregando...</div>';

    let url;
    if (_currentTab === 'enviados') {
        url = `/api/recados-enviados?page=${_currentPage}&limit=${_currentLimit}`;
    } else {
        url = `/api/recados/${_profileUid}?page=${_currentPage}&limit=${_currentLimit}`;
    }

    try {
        const resp = await fetch(url);
        const data = await resp.json();

        if (!data.success) {
            list.innerHTML = '<div class="empty-msg">Erro ao carregar recados.</div>';
            return;
        }

        // Atualizar contagem
        document.getElementById('recadosCount').textContent = '(' + data.total + ')';

        // Atualizar título conforme aba
        const titleEl = document.getElementById('recadosTitle');
        if (_currentTab === 'enviados') {
            titleEl.innerHTML = 'Recados Enviados <span id="recadosCount" style="font-size:16px;color:#666;">(' + data.total + ')</span>';
        } else if (!_isOwnProfile) {
            const vUser = getVisitingUser();
            titleEl.innerHTML = 'Recados de ' + escapeHtml(vUser.nome) + ' <span id="recadosCount" style="font-size:16px;color:#666;">(' + data.total + ')</span>';
        } else {
            titleEl.innerHTML = 'Meus Recados <span id="recadosCount" style="font-size:16px;color:#666;">(' + data.total + ')</span>';
        }

        if (data.recados.length === 0) {
            list.innerHTML = '<div class="empty-msg">Ainda não há nenhum recado.</div>';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        // Renderizar recados
        let html = '';
        data.recados.forEach(r => {
            if (_currentTab === 'enviados') {
                html += renderRecadoEnviado(r);
            } else {
                html += renderRecado(r);
            }
        });
        list.innerHTML = html;

        // Renderizar pagination
        renderPagination(data.page, data.totalPages);
    } catch(err) {
        console.error(err);
        list.innerHTML = '<div class="empty-msg">Erro de conexão.</div>';
    }
}

function renderRecado(r) {
    const canDelete = _isOwnProfile || (r.remetente_id === _myId);
    const canReply = _isOwnProfile && !r.resposta;
    const timeStr = formatTime(r.criado_em);

    let html = '<div class="scrap-item" id="scrap_' + r.id + '">';
    
    // Checkbox para excluir (dono do perfil OU remetente)
    if (canDelete) {
        html += '<div style="display:flex;align-items:flex-start;padding-top:5px;"><input type="checkbox" class="scrap-checkbox" value="' + r.id + '"></div>';
    }

    // Foto do remetente
    html += '<div class="scrap-sender-pic">';
    html += '<a href="/profile.php?uid=' + r.remetente_id + '">';
    html += '<img src="' + escapeHtml(r.remetente_foto || getDefaultAvatar(r.remetente_sexo)) + '" alt="foto">';
    html += '</a>';
    html += '</div>';

    // Conteúdo
    html += '<div class="scrap-content">';
    html += '<div class="scrap-header">';
    html += '<div class="scrap-info-left"><a href="/profile.php?uid=' + r.remetente_id + '">' + escapeHtml(r.remetente_nome) + '</a></div>';
    html += '<div style="display:flex; gap:10px; align-items:center;">';
    html += '<span class="scrap-time">' + timeStr + '</span>';
    if (canDelete) {
        html += '<button class="btn-delete" onclick="apagarRecado(' + r.id + ')">🗑️ apagar</button>';
    }
    html += '</div>';
    html += '</div>';
    html += '<div class="scrap-text">' + sanitizeScrapHtml(renderMentions(r.mensagem)) + '</div>';

    // Resposta existente
    if (r.resposta) {
        html += '<div class="scrap-reply-box">';
        html += '<div class="scrap-reply-pic"><img src="' + escapeHtml(r.respondido_foto || getDefaultAvatar(r.respondido_sexo)) + '" alt="foto"></div>';
        html += '<div class="scrap-reply-content">';
        html += '<div><strong style="color:var(--link);font-size:11px;">' + escapeHtml(r.respondido_nome || '') + '</strong></div>';
        html += '<div class="scrap-reply-text">' + escapeHtml(r.resposta) + '</div>';
        html += '<div class="scrap-reply-time">' + formatTime(r.resposta_em) + '</div>';
        html += '</div></div>';
    }

    // Botão responder
    if (canReply) {
        html += '<div class="reply-action"><a href="javascript:void(0);" onclick="toggleReplyForm(' + r.id + ')">💬 responder</a></div>';
        html += '<div class="reply-form" id="reply_form_' + r.id + '">';
        html += '<textarea id="reply_text_' + r.id + '" class="editor-area-simple" style="min-height:50px;" placeholder="Escreva sua resposta..."></textarea>';
        html += '<div style="text-align:right;margin-top:5px;"><button class="btn-action" onclick="enviarResposta(' + r.id + ')">enviar resposta</button></div>';
        html += '</div>';
    }

    html += '</div>'; // scrap-content
    html += '</div>'; // scrap-item
    return html;
}

function renderRecadoEnviado(r) {
    const timeStr = formatTime(r.criado_em);

    let html = '<div class="scrap-item" id="scrap_' + r.id + '">';

    // Checkbox para excluir (remetente pode excluir)
    html += '<div style="display:flex;align-items:flex-start;padding-top:5px;"><input type="checkbox" class="scrap-checkbox" value="' + r.id + '"></div>';

    // Foto do destinatário
    html += '<div class="scrap-sender-pic">';
    html += '<a href="/profile.php?uid=' + r.destinatario_id + '">';
    html += '<img src="' + escapeHtml(r.destinatario_foto || getDefaultAvatar(r.destinatario_sexo)) + '" alt="foto">';
    html += '</a>';
    html += '</div>';

    // Conteúdo
    html += '<div class="scrap-content">';
    html += '<div class="scrap-header">';
    html += '<div class="scrap-info-left">Para: <a href="/profile.php?uid=' + r.destinatario_id + '">' + escapeHtml(r.destinatario_nome) + '</a></div>';
    html += '<div style="display:flex; gap:10px; align-items:center;">';
    html += '<span class="scrap-time">' + timeStr + '</span>';
    html += '<button class="btn-delete" onclick="apagarRecado(' + r.id + ')">🗑️ apagar</button>';
    html += '</div>';
    html += '</div>';
    html += '<div class="scrap-text">' + sanitizeScrapHtml(renderMentions(r.mensagem)) + '</div>';

    // Resposta existente
    if (r.resposta) {
        html += '<div class="scrap-reply-box">';
        html += '<div class="scrap-reply-content" style="flex:1;">';
        html += '<div><strong style="color:var(--link);font-size:11px;">' + escapeHtml(r.destinatario_nome) + ' respondeu:</strong></div>';
        html += '<div class="scrap-reply-text">' + escapeHtml(r.resposta) + '</div>';
        html += '<div class="scrap-reply-time">' + formatTime(r.resposta_em) + '</div>';
        html += '</div></div>';
    }

    html += '</div>'; // scrap-content
    html += '</div>'; // scrap-item
    return html;
}

function renderPagination(current, total) {
    const el = document.getElementById('paginationLinks');
    if (total <= 1) { el.innerHTML = ''; return; }

    let html = '';
    if (current > 1) {
        html += '<a href="javascript:void(0);" onclick="goToPage(' + (current - 1) + ')" style="color:var(--link);font-size:11px;margin-right:5px;">« anterior</a>';
    }
    
    for (let i = 1; i <= total; i++) {
        if (i === current) {
            html += '<strong style="font-size:11px;margin:0 3px;color:var(--title);">' + i + '</strong>';
        } else {
            html += '<a href="javascript:void(0);" onclick="goToPage(' + i + ')" style="color:var(--link);font-size:11px;margin:0 3px;">' + i + '</a>';
        }
    }

    if (current < total) {
        html += '<a href="javascript:void(0);" onclick="goToPage(' + (current + 1) + ')" style="color:var(--link);font-size:11px;margin-left:5px;">próxima »</a>';
    }
    el.innerHTML = html;
}

function goToPage(p) {
    _currentPage = p;
    loadRecados();
}

async function enviarResposta(recadoId) {
    const textarea = document.getElementById('reply_text_' + recadoId);
    const texto = textarea.value.trim();
    if (!texto) { alert('Escreva uma resposta!'); return; }

    try {
        const resp = await fetch('/api/recados/' + recadoId + '/responder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ resposta: texto })
        });
        const data = await resp.json();
        if (data.success) {
            loadRecados();
        } else {
            alert(data.message || 'Erro ao responder.');
        }
    } catch(err) {
        alert('Erro de conexão.');
    }
}

async function excluirSelecionados() {
    const checked = document.querySelectorAll('.scrap-checkbox:checked');
    if (checked.length === 0) { alert('Selecione pelo menos um recado.'); return; }
    showConfirm('Excluir ' + checked.length + ' recado(s) selecionado(s)?', async function() {
        const ids = Array.from(checked).map(cb => parseInt(cb.value));
        
        try {
            const resp = await fetch('/api/recados/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
            const data = await resp.json();
            if (data.success) {
                loadRecados();
            } else {
                alert(data.message || 'Erro ao excluir.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    });
}

function toggleReplyForm(id) {
    var el = document.getElementById('reply_form_' + id);
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

function selecionarTodos(selecionar) {
    var checkboxes = document.querySelectorAll('.scrap-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = selecionar; });
}

async function apagarRecado(id) {
    showConfirm('Apagar este recado permanentemente?', async function() {
        try {
            const resp = await fetch('/api/recados/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [id] })
            });
            const data = await resp.json();
            if (data.success) {
                loadRecados();
            } else {
                alert(data.message || 'Erro ao apagar.');
            }
        } catch(err) {
            alert('Erro de conexão.');
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function sanitizeScrapHtml(str) {
    if (!str) return '';
    // Tags permitidas (seguras para scraps estilo Orkut)
    const allowedTags = ['b', 'i', 'u', 's', 'em', 'strong', 'br', 'p', 'a', 'img', 'font', 'center', 'marquee', 'blink', 'big', 'small', 'sub', 'sup', 'hr', 'div', 'span', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'tr', 'td', 'th', 'tbody', 'thead'];
    // Atributos permitidos por tag
    const allowedAttrs = {
        'a': ['href', 'title', 'target'],
        'img': ['src', 'alt', 'title', 'border', 'width', 'height', 'style'],
        'font': ['color', 'size', 'face'],
        'div': ['style', 'align'],
        'span': ['style'],
        'p': ['style', 'align'],
        'center': [],
        'marquee': ['direction', 'scrollamount', 'behavior'],
        'table': ['border', 'cellpadding', 'cellspacing', 'width', 'style'],
        'td': ['colspan', 'rowspan', 'width', 'style', 'align', 'valign'],
        'th': ['colspan', 'rowspan', 'width', 'style', 'align', 'valign'],
        'tr': ['style'],
        'h1': ['style'], 'h2': ['style'], 'h3': ['style'], 'h4': ['style'], 'h5': ['style'], 'h6': ['style'],
        'hr': ['style'],
        'blockquote': ['style']
    };
    
    const parser = new DOMParser();
    const doc = parser.parseFromString(str, 'text/html');
    
    function sanitizeNode(node) {
        const result = document.createDocumentFragment();
        for (const child of Array.from(node.childNodes)) {
            if (child.nodeType === Node.TEXT_NODE) {
                result.appendChild(document.createTextNode(child.textContent));
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                const tagName = child.tagName.toLowerCase();
                if (allowedTags.includes(tagName)) {
                    const el = document.createElement(tagName);
                    // Copiar apenas atributos permitidos
                    const allowed = allowedAttrs[tagName] || [];
                    for (const attr of Array.from(child.attributes)) {
                        if (allowed.includes(attr.name.toLowerCase())) {
                            let val = attr.value;
                            // Bloquear javascript: em href/src
                            if ((attr.name === 'href' || attr.name === 'src') && val.replace(/\s/g, '').toLowerCase().startsWith('javascript:')) {
                                continue;
                            }
                            el.setAttribute(attr.name, val);
                        }
                    }
                    // Links abrem em nova aba e têm noopener
                    if (tagName === 'a') {
                        el.setAttribute('target', '_blank');
                        el.setAttribute('rel', 'noopener noreferrer');
                    }
                    // Imagens: limitar tamanho máximo
                    if (tagName === 'img') {
                        el.style.maxWidth = '100%';
                        el.style.height = 'auto';
                    }
                    el.appendChild(sanitizeNode(child));
                    result.appendChild(el);
                } else {
                    // Tag não permitida: incluir apenas o conteúdo (sem a tag)
                    result.appendChild(sanitizeNode(child));
                }
            }
        }
        return result;
    }
    
    const sanitized = sanitizeNode(doc.body);
    const wrapper = document.createElement('div');
    wrapper.appendChild(sanitized);
    return wrapper.innerHTML;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr.replace(' ', 'T'));
        const now = new Date();
        const diff = now - d;
        const mins = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        const fullDate = dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;

        let relative = '';
        if (mins < 1) relative = 'agora mesmo';
        else if (mins === 1) relative = '1 min atrás';
        else if (mins < 60) relative = mins + ' minutos atrás';
        else if (hours === 1) relative = '1 hora atrás';
        else if (hours < 24) relative = hours + ' horas atrás';
        else if (days === 1) relative = '1 dia atrás';
        else if (days < 30) relative = days + ' dias atrás';
        else {
            const months = Math.floor(days / 30);
            const years = Math.floor(days / 365);
            if (years >= 1) relative = years === 1 ? '1 ano atrás' : years + ' anos atrás';
            else relative = months === 1 ? '1 mes atrás' : months + ' meses atrás';
        }

        return fullDate + ' (' + relative + ')';
    } catch(e) {
        return dateStr;
    }
}
</script>
</body>
</html>
