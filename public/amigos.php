<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Amigos</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .toolbar { background: #fdfdfd; border: 1px solid #c0d0e6; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .toolbar input, .toolbar select { padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 12px; outline: none; }
    .toolbar input { flex: 1; min-width: 200px; }
    .toolbar input:focus { border-color: #3b5998; box-shadow: 0 0 5px rgba(59,89,152,0.3); }

    /* LAYOUT DE CARDS EM 3 COLUNAS */
    .friend-list { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 15px; 
    }
    .friend-row { 
        background: #fff; 
        border: 1px solid #e4ebf5; 
        border-radius: 6px; 
        padding: 15px; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        text-align: center; 
        transition: 0.2s; 
        position: relative;
    }
    .friend-row:hover { border-color: #a5bce3; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transform: translateY(-2px); }
    
    .f-pic { width: 80px; height: 80px; flex-shrink: 0; background: #e4ebf5; border: 1px solid #ccc; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .f-pic img { width: 100%; height: 100%; object-fit: cover; }
    
    .f-info { width: 100%; margin-bottom: 10px; }
    .f-name { font-size: 14px; font-weight: bold; color: var(--link); text-decoration: none; display: block; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    .f-name:hover { text-decoration: underline; }
    .f-status { font-size: 11px; color: #555; font-style: italic; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;}
    .f-date { font-size: 10px; color: #999; margin-bottom: 10px; background: #f9f9f9; padding: 2px; border-radius: 3px;}

    /* Comunidades em comum */
    .f-comms { display: flex; justify-content: center; gap: 5px; align-items: center; }
    .f-comm-pic { width: 26px; height: 34px; border-radius: 3px; object-fit: cover; border: 1px solid #ccc; transition: 0.2s; }
    .f-comm-pic:hover { transform: scale(1.1); border-color: var(--link); }
    .f-comm-more { font-size: 10px; font-weight: bold; color: var(--link); background: #f0f0f0; padding: 2px 5px; border-radius: 3px; text-decoration: none;}
    .f-comm-more:hover { background: #e0e0e0; }

    /* Área de Ações do Card */
    .f-actions { width: 100%; border-top: 1px dashed #eee; padding-top: 12px; display: flex; flex-direction: column; align-items: center; gap: 10px;}
    
    /* Avaliação Visual */
    .rating-box { display: flex; gap: 10px; align-items: center; background: #f9fbfc; padding: 5px 15px; border-radius: 20px; border: 1px solid #eee; }
    .stars { color: #ccc; font-size: 14px; cursor: pointer; }
    .stars .active { color: #f39c12; }
    .stars span:hover { color: #f39c12; }
    .fan-badge { font-size: 11px; color: #f39c12; font-weight: bold; display: flex; align-items: center; gap: 4px; }

    .btn-tools { display: flex; flex-direction: column; gap: 5px; width: 100%; }
    .btn-mini { background: #fff; border: 1px solid #ccc; color: #555; font-size: 11px; padding: 6px; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; width: 100%; transition: 0.2s;}
    .btn-mini:hover { background: #f0f0f0; color: #333; }
    .btn-mini.danger { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-mini.danger:hover { background: #ffebee; border-color: #cc0000; }

    /* Paginação */
    .pagination { text-align: right; margin-top: 20px; font-size: 12px; }
    .pagination a { padding: 5px 10px; border: 1px solid var(--link); color: var(--link); text-decoration: none; border-radius: 4px; margin-left: 5px; font-weight: bold;}
    .pagination a:hover { background: var(--link); color: #fff; }

    /* Loading */
    .friends-loading { grid-column: 1 / -1; text-align: center; padding: 40px; color: #999; font-style: italic; }

    @media (max-width: 900px) {
        .friend-list { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .friend-list { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > 
            Amigos
        </div>

        <div class="card">
            <h1 class="orkut-name" id="friends-title" style="font-size: 20px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px;">
                Meus Amigos <span style="font-size: 14px; color:#888;" id="friends-count">(0)</span>
            </h1>

            <div class="toolbar" id="toolbar">
                <input type="text" id="searchName" placeholder="🔍 Buscar amigo pelo nome..." onkeyup="filterFriends()">
                
                <select id="filterStars" onchange="filterFriends()">
                    <option value="">Todas as Estrelas</option>
                    <option value="5">5 Estrelas 🌟</option>
                    <option value="4">4+ Estrelas</option>
                    <option value="3">3+ Estrelas</option>
                </select>

                <select id="filterFan" onchange="filterFriends()">
                    <option value="">Todos (Fãs e Não Fãs)</option>
                    <option value="1">Apenas Fãs ⭐</option>
                </select>
            </div>

            <div class="friend-list" id="friendsList">
                <div class="friends-loading">Carregando amigos...</div>
            </div>
        </div>
    </div>
    <div class="right-col" id="app-right-col"></div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
let amigosData = [];
let isOwnPage = true;
let targetUid = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadLayout({ activePage: 'amigos' });

    const urlParams = new URLSearchParams(window.location.search);
    const uidParam = urlParams.get('uid');
    targetUid = uidParam ? uidParam : (getUserData() ? getUserData().id : null);

    if (!targetUid) return;

    // Update breadcrumb
    if (isVisiting()) {
        const user = getVisitingUser();
        document.getElementById('breadcrumb').innerHTML = 
            '<a href="/profile.php">Início</a> > ' +
            '<a href="/profile.php?uid=' + targetUid + '">' + user.nome + '</a> > ' +
            'Amigos';
        document.title = 'Yorkut - Amigos de ' + user.nome;
    } else {
        document.getElementById('breadcrumb').innerHTML = 
            '<a href="/profile.php">Início</a> > ' +
            '<a href="/profile.php?uid=' + targetUid + '">' + (getUserData() ? getUserData().nome : '') + '</a> > ' +
            'Amigos';
    }

    // Load friends
    await loadFriends();
});

async function loadFriends() {
    try {
        const resp = await fetch('/api/amigos/' + targetUid);
        const data = await resp.json();
        if (!data.success) return;

        amigosData = data.amigos || [];
        isOwnPage = data.isOwn;

        const total = data.total || 0;
        const titleEl = document.getElementById('friends-title');
        const countEl = document.getElementById('friends-count');
        countEl.textContent = '(' + total + ')';

        if (isOwnPage) {
            titleEl.childNodes[0].textContent = 'Meus Amigos ';
        } else {
            const nome = isVisiting() ? getVisitingUser().nome : '';
            titleEl.childNodes[0].textContent = 'Amigos de ' + nome + ' ';
        }

        // Show "Amigos em Comum" filter when visiting
        if (!isOwnPage) {
            const toolbar = document.getElementById('toolbar');
            const sel = document.createElement('select');
            sel.id = 'filterCommon';
            sel.onchange = filterFriends;
            sel.innerHTML = '<option value="">Todos os Amigos</option><option value="1">Amigos em Comum 👥</option>';
            toolbar.appendChild(sel);
        }

        renderFriends(amigosData);
    } catch(err) {
        console.error('Erro ao carregar amigos:', err);
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T') + (dateStr.includes('T') ? '' : 'Z'));
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return dd + '/' + mm + '/' + yyyy;
}

function renderFriends(list) {
    const container = document.getElementById('friendsList');

    if (list.length === 0) {
        container.innerHTML = '<div class="friends-loading" style="border:1px dashed var(--line); border-radius:4px; background:#f9f9f9;">' +
            (isOwnPage ? 'Convide seus amigos para o Yorkut!' : 'Este usuário ainda não possui amigos.') +
            '</div>';
        return;
    }

    let html = '';
    list.forEach(function(a) {
        const foto = a.foto_perfil || getDefaultAvatar(a.sexo);
        const nome = a.nome || '';
        const statusText = a.status_texto || 'Nenhum status definido.';
        const stars = a.estrelas || 0;
        const emComum = a.em_comum || 0;
        const dateSince = a.aceito_em ? formatDate(a.aceito_em) : '';

        html += '<div class="friend-row" data-name="' + nome.toLowerCase() + '" data-stars="' + stars + '" data-fan="0" data-common="' + emComum + '">';
        
        // Photo
        html += '<div class="f-pic"><a href="profile.php?uid=' + a.id + '"><img src="' + foto + '" alt=""></a></div>';
        
        // Info
        html += '<div class="f-info">';
        html += '<a href="profile.php?uid=' + a.id + '" class="f-name" title="' + nome + '">' + nome + '</a>';
        html += '<div class="f-status">"' + escapeHtml(statusText) + '"</div>';
        if (dateSince) {
            html += '<div class="f-date">Amigos desde: ' + dateSince + '</div>';
        }
        html += '</div>';

        // Actions
        html += '<div class="f-actions">';
        
        // Stars rating
        html += '<div class="rating-box">';
        html += '<div class="stars" data-uid="' + a.id + '" title="Avaliação de Amizade"' + (isOwnPage ? ' onclick="rateUser(\'' + a.id + '\', this, event)"' : '') + '>'; 
        for (let i = 1; i <= 5; i++) {
            html += '<span class="' + (i <= stars ? 'active' : '') + '" data-val="' + i + '">★</span>';
        }
        html += '</div>';
        html += '</div>';

        // Button tools
        html += '<div class="btn-tools">';
        html += '<button type="button" class="btn-mini" onclick="copyLink(\'' + a.id + '\')">🔗 Compartilhar perfil</button>';
        
        if (isOwnPage) {
            html += '<button type="button" class="btn-mini danger" onclick="unfriend(\'' + a.id + '\', \'' + nome.replace(/'/g, "\\'") + '\')">❌ Desfazer amizade</button>';
        }
        html += '</div>';

        html += '</div>'; // f-actions
        html += '</div>'; // friend-row
    });

    container.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// --- Lógica de Filtro Instantâneo ---
function filterFriends() {
    let nameQ = document.getElementById('searchName').value.toLowerCase();
    let starQ = document.getElementById('filterStars').value;
    let fanQ = document.getElementById('filterFan').value;
    let commEl = document.getElementById('filterCommon');
    let commQ = commEl ? commEl.value : '';

    let rows = document.querySelectorAll('.friend-row');
    
    rows.forEach(row => {
        let name = row.getAttribute('data-name');
        let stars = parseInt(row.getAttribute('data-stars'));
        let fan = row.getAttribute('data-fan');
        let common = row.getAttribute('data-common');

        let show = true;

        if (nameQ && !name.includes(nameQ)) show = false;
        if (starQ && stars < parseInt(starQ)) show = false;
        if (fanQ && fan !== fanQ) show = false;
        if (commQ && common !== commQ) show = false;

        row.style.display = show ? 'flex' : 'none';
    });
}

// --- Copiar Link do Perfil ---
function copyLink(uid) {
    let url = window.location.origin + '/profile.php?uid=' + uid;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('🔗 Link do perfil copiado!');
        });
    } else {
        let tempInput = document.createElement("input");
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        showToast('🔗 Link do perfil copiado!');
    }
}

// --- Avaliar amigo ---
async function rateUser(friendId, element, event) {
    let starClicked = event.target;
    if (starClicked.tagName !== 'SPAN') return;
    
    let val = parseInt(starClicked.getAttribute('data-val'));
    
    try {
        const resp = await fetch('/api/amigos/avaliar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amigo_id: friendId, estrelas: val })
        });
        const data = await resp.json();
        if (data.success) {
            // Update stars visually
            const spans = element.querySelectorAll('span');
            spans.forEach(s => {
                if (parseInt(s.getAttribute('data-val')) <= val) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            // Update data attr
            element.closest('.friend-row').setAttribute('data-stars', val);
            showToast('⭐ Avaliação salva!');
        } else {
            showToast(data.message || 'Erro ao avaliar.');
        }
    } catch(err) {
        showToast('Erro ao avaliar.');
    }
}

// --- Desfazer amizade ---
function unfriend(friendId, friendName) {
    showConfirm('Você tem certeza absoluta que deseja desfazer a amizade com ' + friendName + '? Essa ação não pode ser desfeita e vocês precisarão se adicionar novamente.', async () => {
        try {
            const resp = await fetch('/api/amizade/desfazer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amigo_id: friendId })
            });
            const data = await resp.json();
            if (data.success) {
                showToast('Amizade desfeita.');
                // Remove from data and re-render
                amigosData = amigosData.filter(a => a.id !== friendId);
                document.getElementById('friends-count').textContent = '(' + amigosData.length + ')';
                renderFriends(amigosData);
            } else {
                showToast(data.message || 'Erro ao desfazer amizade.');
            }
        } catch(err) {
            showToast('Erro ao desfazer amizade.');
        }
    });
}
</script>
</body>
</html>
