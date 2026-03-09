<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Buscar Amigos</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .search-box { background: #fff; border: 1px solid #c0d0e6; padding: 20px; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .search-box h2 { font-size: 16px; color: var(--orkut-blue); margin: 0 0 15px 0; }
    .search-input-row { display: flex; gap: 10px; }
    .search-input-row input { flex: 1; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; outline: none; }
    .search-input-row input:focus { border-color: #3b5998; box-shadow: 0 0 5px rgba(59,89,152,0.3); }
    .search-input-row button { padding: 10px 20px; background: var(--orkut-blue); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold; }
    .search-input-row button:hover { background: #2d4a86; }

    .results-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 20px;
    }
    .user-card {
        background: #fff;
        border: 1px solid #e4ebf5;
        border-radius: 6px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: 0.2s;
    }
    .user-card:hover { border-color: var(--orkut-pink); box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
    .user-card .pic { width: 80px; height: 80px; border-radius: 50%; overflow: hidden; margin-bottom: 10px; border: 2px solid #e4ebf5; }
    .user-card .pic img { width: 100%; height: 100%; object-fit: cover; }
    .user-card .name { font-weight: bold; color: var(--link); font-size: 13px; text-decoration: none; display: block; margin-bottom: 5px; }
    .user-card .name:hover { text-decoration: underline; }
    .user-card .loc { font-size: 10px; color: #999; }
    .user-card .btn-visit { display: inline-block; margin-top: 10px; padding: 5px 15px; background: var(--orkut-blue); color: #fff; border-radius: 3px; text-decoration: none; font-size: 11px; font-weight: bold; }
    .user-card .btn-visit:hover { background: #2d4a86; }

    .no-results { text-align: center; color: #999; padding: 40px 20px; font-size: 13px; }
    .search-tip { font-size: 11px; color: #999; margin-top: 8px; }

    @media (max-width: 768px) {
        .results-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .results-grid { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb">
            <a href="/profile.php">Início</a> > Buscar Amigos
        </div>

        <div class="search-box">
            <h2>🔍 Buscar Amigos</h2>
            <div class="search-input-row">
                <input type="text" id="searchInput" placeholder="Digite o nome ou e-mail do amigo..." autofocus>
                <button type="button" onclick="buscarUsuario()">Buscar</button>
            </div>
            <div class="search-tip">Dica: Busque pelo nome ou e-mail do amigo que deseja encontrar.</div>
        </div>

        <div id="searchResults"></div>
    </div>
</div>
<div id="app-footer"></div>

<script src="/js/layout.js"></script>
<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() { loadLayout({ activePage: 'amigos' }); });

function getDefaultAvatar(sexo) {
    return '/img/default-avatar.png';
}

async function buscarUsuario() {
    const q = document.getElementById('searchInput').value.trim();
    if (!q || q.length < 2) {
        document.getElementById('searchResults').innerHTML = '<div class="no-results">Digite pelo menos 2 caracteres para buscar.</div>';
        return;
    }

    document.getElementById('searchResults').innerHTML = '<div class="no-results">Buscando...</div>';

    try {
        const resp = await fetch('/api/buscar-usuario?q=' + encodeURIComponent(q));
        const data = await resp.json();

        if (!data.success) {
            document.getElementById('searchResults').innerHTML = '<div class="no-results">' + (data.message || 'Erro ao buscar.') + '</div>';
            return;
        }

        if (!data.usuarios || data.usuarios.length === 0) {
            document.getElementById('searchResults').innerHTML = '<div class="no-results">Nenhum usuário encontrado com "' + q.replace(/</g, '&lt;') + '".</div>';
            return;
        }

        let html = '<div class="results-grid">';
        data.usuarios.forEach(function(u) {
            const foto = u.foto_perfil || getDefaultAvatar(u.sexo);
            const loc = [u.cidade, u.estado].filter(Boolean).join(', ') || '';
            html += '<div class="user-card">';
            html += '<div class="pic"><a href="profile.php?uid=' + u.id + '"><img src="' + foto + '"></a></div>';
            html += '<a href="profile.php?uid=' + u.id + '" class="name">' + escapeHtml(u.nome) + '</a>';
            if (loc) html += '<div class="loc">' + escapeHtml(loc) + '</div>';
            html += '<a href="profile.php?uid=' + u.id + '" class="btn-visit">Ver perfil</a>';
            html += '</div>';
        });
        html += '</div>';
        document.getElementById('searchResults').innerHTML = html;
    } catch(err) {
        console.error('Erro ao buscar:', err);
        document.getElementById('searchResults').innerHTML = '<div class="no-results">Erro de conexão.</div>';
    }
}

// Auto-search on Enter
document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); buscarUsuario(); }
});

// If ?q= param exists, auto-search
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const q = urlParams.get('q') || urlParams.get('email');
    if (q) {
        document.getElementById('searchInput').value = q;
        buscarUsuario();
    }
});
</script>
</body>
</html>
