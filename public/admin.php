<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Painel Admin</title>
<link rel="stylesheet" href="/styles/profile.css">
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
/* ===== ADMIN PANEL STYLES ===== */
.admin-header {
    background: linear-gradient(135deg, #2f4f87 0%, #1a3366 100%);
    color: #fff;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid var(--orkut-pink);
}
.admin-header h1 { font-size: 18px; margin: 0; display: flex; align-items: center; gap: 10px; }
.admin-header .logo { font-size: 20px; }
.admin-header-links { display: flex; gap: 15px; }
.admin-header-links a { color: #bfd0ea; text-decoration: none; font-size: 12px; }
.admin-header-links a:hover { color: #fff; }

.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
    display: flex;
    gap: 15px;
}

/* Sidebar */
.admin-sidebar {
    width: 200px;
    min-width: 200px;
}
.admin-nav {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 4px;
    overflow: hidden;
}
.admin-nav-item {
    display: block;
    width: 100%;
    padding: 10px 15px;
    border: none;
    background: none;
    text-align: left;
    font-size: 12px;
    font-family: inherit;
    color: var(--link);
    cursor: pointer;
    border-bottom: 1px solid #eef3fa;
    transition: background 0.15s;
}
.admin-nav-item:hover { background: #f0f5fb; }
.admin-nav-item.active { background: var(--orkut-blue); color: #fff; font-weight: bold; }
.admin-nav-item:last-child { border-bottom: none; }
.den-filter { background: #f0f5fb !important; color: var(--link) !important; border: 1px solid var(--line) !important; }
.den-filter.active { background: var(--orkut-blue) !important; color: #fff !important; }
.nav-icon { margin-right: 8px; }

/* Denúncia detail tabs */
.den-tab {
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 10px 14px;
    font-size: 11px;
    font-family: inherit;
    color: #666;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.den-tab:hover { color: var(--title); background: #eef4ff; }
.den-tab.active { color: var(--title); border-bottom-color: var(--orkut-blue); font-weight: bold; }
.den-tab-count { background: #e0e0e0; color: #666; font-size: 9px; padding: 1px 5px; border-radius: 8px; margin-left: 3px; }
.den-tab.active .den-tab-count { background: var(--orkut-blue); color: #fff; }

/* Conversation bubble */
.den-msg-item {
    padding: 8px 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 11px;
    line-height: 1.5;
}
.den-msg-item:last-child { border-bottom: none; }
.den-msg-item.from-denunciante { background: #f0fff0; }
.den-msg-item.from-denunciado { background: #fff5f5; }
.den-msg-meta { font-size: 10px; color: #999; margin-bottom: 3px; display: flex; justify-content: space-between; }
.den-msg-author { font-weight: bold; }
.den-msg-author.denunciante { color: #27ae60; }
.den-msg-author.denunciado { color: #e74c3c; }

/* Nav badge for denúncias */
.nav-badge {
    background: #e74c3c;
    color: #fff;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 8px;
    margin-left: 5px;
    font-weight: bold;
    animation: pulseDenBadge 2s infinite;
}
@keyframes pulseDenBadge { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

/* Profile card in modal */
.den-profile-card {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.den-profile-card img {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid var(--line);
}
.den-profile-info { flex: 1; line-height: 1.7; }
.den-profile-info .name { font-weight: bold; color: var(--title); font-size: 13px; }
.den-profile-info .name a { color: var(--link); text-decoration: none; }
.den-profile-info .name a:hover { text-decoration: underline; }
.den-stat-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.den-stat-chip { background: #eef3fa; padding: 2px 6px; border-radius: 3px; font-size: 10px; color: #555; }
.den-stat-chip.danger { background: #fde8e8; color: #c0392b; }

/* Main content */
.admin-main {
    flex: 1;
    min-width: 0;
}
.admin-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 4px;
    margin-bottom: 15px;
}
.admin-card-title {
    font-size: 14px;
    font-weight: bold;
    color: var(--title);
    padding: 10px 15px;
    border-bottom: 1px solid var(--line);
    background: #f0f5fb;
    border-radius: 4px 4px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.admin-card-body { padding: 15px; }

/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.stat-box {
    background: linear-gradient(135deg, #f0f5fb 0%, #e4ebf5 100%);
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 15px;
    text-align: center;
}
.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: var(--title);
    line-height: 1.2;
}
.stat-label {
    font-size: 10px;
    color: #666;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-box.pink { border-color: var(--orkut-pink); }
.stat-box.pink .stat-number { color: var(--orkut-pink); }

/* Table */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}
.admin-table th {
    background: #f0f5fb;
    color: var(--title);
    padding: 8px 10px;
    text-align: left;
    border-bottom: 2px solid var(--line);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.admin-table td {
    padding: 7px 10px;
    border-bottom: 1px solid #eef3fa;
    vertical-align: middle;
}
.admin-table tr:hover td { background: #fafcfe; }
.admin-table .user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}
.admin-table .user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 3px;
    object-fit: cover;
    border: 1px solid var(--line);
}

/* Buttons */
.btn-admin {
    padding: 4px 10px;
    border: 1px solid var(--line);
    border-radius: 3px;
    font-size: 10px;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-admin-primary { background: var(--orkut-blue); color: #fff; border-color: #5a72a0; }
.btn-admin-primary:hover { background: #5a72a0; }
.btn-admin-danger { background: #e74c3c; color: #fff; border-color: #c0392b; }
.btn-admin-danger:hover { background: #c0392b; }
.btn-admin-success { background: #27ae60; color: #fff; border-color: #1e8449; }
.btn-admin-success:hover { background: #1e8449; }
.btn-admin-sm { padding: 3px 8px; font-size: 10px; }
.btn-group { display: flex; gap: 5px; }

/* Search bar */
.admin-search {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.admin-search input {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid var(--line);
    border-radius: 3px;
    font-size: 11px;
    font-family: inherit;
}
.admin-search input:focus { outline: none; border-color: var(--orkut-blue); }

/* Filter tabs */
.admin-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.filter-btn {
    padding: 5px 12px;
    border: 1px solid var(--line);
    border-radius: 3px;
    background: #fff;
    font-size: 11px;
    font-family: inherit;
    cursor: pointer;
    color: var(--link);
}
.filter-btn.active { background: var(--orkut-blue); color: #fff; border-color: var(--orkut-blue); }

/* Pagination */
.admin-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    font-size: 11px;
}
.admin-pagination button {
    padding: 4px 10px;
    border: 1px solid var(--line);
    border-radius: 3px;
    background: #fff;
    cursor: pointer;
    font-family: inherit;
    font-size: 11px;
}
.admin-pagination button:hover { background: #f0f5fb; }
.admin-pagination button:disabled { opacity: 0.4; cursor: default; }
.admin-pagination .page-info { color: #666; }

/* Modal */
.admin-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.admin-modal-overlay.active { display: flex; }
.admin-modal {
    background: #fff;
    border-radius: 6px;
    width: 500px;
    max-width: 90vw;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.admin-modal-header {
    padding: 12px 15px;
    border-bottom: 1px solid var(--line);
    font-weight: bold;
    font-size: 13px;
    color: var(--title);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0f5fb;
    border-radius: 6px 6px 0 0;
}
.admin-modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #999;
    padding: 0 5px;
}
.admin-modal-close:hover { color: #e74c3c; }
.admin-modal-body { padding: 15px; }
.admin-modal-body label {
    display: block;
    font-size: 11px;
    font-weight: bold;
    color: #555;
    margin-bottom: 3px;
    margin-top: 10px;
}
.admin-modal-body label:first-child { margin-top: 0; }
.admin-modal-body input, .admin-modal-body select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--line);
    border-radius: 3px;
    font-size: 11px;
    font-family: inherit;
    box-sizing: border-box;
}
.admin-modal-footer {
    padding: 12px 15px;
    border-top: 1px solid var(--line);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* Content preview */
.content-preview {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Badge */
.badge-admin { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
.badge-admin.admin { background: #e74c3c; color: #fff; }
.badge-admin.user { background: #eef3fa; color: #666; }
.badge-admin.banned { background: #1a1a2e; color: #ff4444; }
.badge-admin.ban-temp { background: #ff9800; color: #fff; }

/* Toast notification */
.admin-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 4px;
    color: #fff;
    font-size: 12px;
    z-index: 99999;
    animation: slideIn 0.3s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}
.admin-toast.success { background: #27ae60; }
.admin-toast.error { background: #e74c3c; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

/* Recentes */
.recent-list { list-style: none; padding: 0; margin: 0; }
.recent-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    border-bottom: 1px solid #eef3fa;
    font-size: 11px;
}
.recent-list li:last-child { border-bottom: none; }
.recent-avatar { width: 24px; height: 24px; border-radius: 3px; object-fit: cover; }

@media (max-width: 768px) {
    .admin-container { flex-direction: column; }
    .admin-sidebar { width: 100%; min-width: auto; }
    .admin-nav { display: flex; flex-wrap: wrap; }
    .admin-nav-item { flex: 1; min-width: 120px; text-align: center; border-bottom: none; border-right: 1px solid #eef3fa; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body style="background: var(--bg);">

<!-- HEADER -->
<div class="admin-header">
    <h1><span class="logo">yorkut</span> Painel Administrativo</h1>
    <div class="admin-header-links">
        <a href="/profile.php">← Voltar ao site</a>
        <a href="/logout.php">Sair</a>
    </div>
</div>

<!-- CONTAINER -->
<div class="admin-container">
    <!-- SIDEBAR -->
    <div class="admin-sidebar">
        <nav class="admin-nav">
            <button class="admin-nav-item active" onclick="showSection('dashboard')"><span class="nav-icon">📊</span> Dashboard</button>
            <button class="admin-nav-item" onclick="showSection('usuarios')"><span class="nav-icon">👥</span> Usuários</button>
            <button class="admin-nav-item" onclick="showSection('convites')"><span class="nav-icon">🎟️</span> Convites</button>
            <button class="admin-nav-item" onclick="showSection('recados')"><span class="nav-icon">💬</span> Recados</button>
            <button class="admin-nav-item" onclick="showSection('depoimentos')"><span class="nav-icon">📝</span> Depoimentos</button>
            <button class="admin-nav-item" onclick="showSection('mensagens')"><span class="nav-icon">✉️</span> Mensagens</button>
            <button class="admin-nav-item" onclick="showSection('denuncias')"><span class="nav-icon">⚠️</span> Denúncias <span id="nav-den-badge"></span></button>
            <button class="admin-nav-item" onclick="showSection('denuncias_comunidades')"><span class="nav-icon">🏠</span> Den. Comunidades <span id="nav-dencomm-badge"></span></button>
            <button class="admin-nav-item" onclick="showSection('sugestoes')"><span class="nav-icon">💡</span> Sugestões <span id="nav-sug-badge"></span></button>
            <button class="admin-nav-item" onclick="showSection('bugs')"><span class="nav-icon">🐞</span> Bugs <span id="nav-bug-badge"></span></button>
            <button class="admin-nav-item" onclick="showSection('anuncios')"><span class="nav-icon">📢</span> Anúncios</button>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="admin-main">

        <!-- ===== DASHBOARD ===== -->
        <div id="section-dashboard" class="admin-section">
            <div class="admin-card">
                <div class="admin-card-title">📊 Visão Geral</div>
                <div class="admin-card-body">
                    <div class="stats-grid" id="stats-grid">
                        <div class="stat-box"><div class="stat-number">-</div><div class="stat-label">Carregando...</div></div>
                    </div>
                </div>
            </div>
            <div class="admin-card">
                <div class="admin-card-title">🆕 Usuários Recentes</div>
                <div class="admin-card-body">
                    <ul class="recent-list" id="recent-users"></ul>
                </div>
            </div>
        </div>

        <!-- ===== USUÁRIOS ===== -->
        <div id="section-usuarios" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">
                    👥 Gerenciar Usuários
                    <span id="usuarios-total" style="font-weight:normal;font-size:11px;color:#666;"></span>
                </div>
                <div class="admin-card-body">
                    <div class="admin-search">
                        <input type="text" id="user-search" placeholder="Buscar por nome, email ou ID..." onkeydown="if(event.key==='Enter') loadUsuarios(1)">
                        <button class="btn-admin btn-admin-primary" onclick="loadUsuarios(1)">Buscar</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Cadastro</th>
                                <th>Último Acesso</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="usuarios-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="usuarios-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== CONVITES ===== -->
        <div id="section-convites" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">
                    🎟️ Gerenciar Convites
                    <button class="btn-admin btn-admin-success btn-admin-sm" onclick="openGerarConvites()">+ Gerar Convites</button>
                </div>
                <div class="admin-card-body">
                    <div class="admin-filters">
                        <button class="filter-btn active" onclick="setConviteFilter('todos', this)">Todos</button>
                        <button class="filter-btn" onclick="setConviteFilter('disponiveis', this)">Disponíveis</button>
                        <button class="filter-btn" onclick="setConviteFilter('usados', this)">Usados</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Token</th>
                                <th>Criado por</th>
                                <th>Status</th>
                                <th>Usado por</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="convites-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="convites-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== RECADOS ===== -->
        <div id="section-recados" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">💬 Moderação de Recados</div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>De</th>
                                <th>Para</th>
                                <th>Mensagem</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="recados-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="recados-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== DEPOIMENTOS ===== -->
        <div id="section-depoimentos" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">📝 Moderação de Depoimentos</div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>De</th>
                                <th>Para</th>
                                <th>Mensagem</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="depoimentos-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="depoimentos-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== MENSAGENS ===== -->
        <div id="section-mensagens" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">✉️ Moderação de Mensagens</div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>De</th>
                                <th>Para</th>
                                <th>Assunto</th>
                                <th>Mensagem</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="mensagens-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="mensagens-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== DENÚNCIAS ===== -->
        <div id="section-denuncias" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">⚠️ Moderação de Denúncias</div>
                <div class="admin-card-body">
                    <div style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="btn-admin btn-admin-sm den-filter active" data-filtro="todos" onclick="setDenunciaFilter('todos',this)">Todos</button>
                        <button class="btn-admin btn-admin-sm den-filter" data-filtro="pendente" onclick="setDenunciaFilter('pendente',this)">🟡 Pendentes</button>
                        <button class="btn-admin btn-admin-sm den-filter" data-filtro="analisando" onclick="setDenunciaFilter('analisando',this)">🔵 Analisando</button>
                        <button class="btn-admin btn-admin-sm den-filter" data-filtro="respondido" onclick="setDenunciaFilter('respondido',this)">💬 Respondidas</button>
                    <button class="btn-admin btn-admin-sm den-filter" data-filtro="resolvida" onclick="setDenunciaFilter('resolvida',this)">🟢 Resolvidas</button>
                        <button class="btn-admin btn-admin-sm den-filter" data-filtro="rejeitada" onclick="setDenunciaFilter('rejeitada',this)">🔴 Rejeitadas</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Denunciante</th>
                                <th>Denunciado</th>
                                <th>Motivo</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="denuncias-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="denuncias-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== DENÚNCIAS COMUNIDADES ===== -->
        <div id="section-denuncias_comunidades" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">🏠 Denúncias de Comunidades</div>
                <div class="admin-card-body">
                    <div style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="btn-admin btn-admin-sm dencomm-filter active" data-filtro="todos" onclick="setDenCommFilter('todos',this)">Todos</button>
                        <button class="btn-admin btn-admin-sm dencomm-filter" data-filtro="pendente" onclick="setDenCommFilter('pendente',this)">🟡 Pendentes</button>
                        <button class="btn-admin btn-admin-sm dencomm-filter" data-filtro="analisando" onclick="setDenCommFilter('analisando',this)">🔵 Analisando</button>
                        <button class="btn-admin btn-admin-sm dencomm-filter" data-filtro="respondido" onclick="setDenCommFilter('respondido',this)">💬 Respondidas</button>
                        <button class="btn-admin btn-admin-sm dencomm-filter" data-filtro="resolvida" onclick="setDenCommFilter('resolvida',this)">🟢 Resolvidas</button>
                        <button class="btn-admin btn-admin-sm dencomm-filter" data-filtro="rejeitada" onclick="setDenCommFilter('rejeitada',this)">🔴 Rejeitadas</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Denunciante</th>
                                <th>Comunidade</th>
                                <th>Dono</th>
                                <th>Motivo</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="dencomm-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="dencomm-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== SUGESTÕES ===== -->
        <div id="section-sugestoes" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">💡 Sugestões dos Usuários</div>
                <div class="admin-card-body">
                    <div style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="btn-admin btn-admin-sm sug-filter active" data-filtro="todos" onclick="setSugestaoFilter('todos',this)">Todos</button>
                        <button class="btn-admin btn-admin-sm sug-filter" data-filtro="nova" onclick="setSugestaoFilter('nova',this)">🟡 Novas</button>
                        <button class="btn-admin btn-admin-sm sug-filter" data-filtro="analisando" onclick="setSugestaoFilter('analisando',this)">🔵 Analisando</button>
                        <button class="btn-admin btn-admin-sm sug-filter" data-filtro="aprovada" onclick="setSugestaoFilter('aprovada',this)">✅ Aprovadas</button>
                        <button class="btn-admin btn-admin-sm sug-filter" data-filtro="implementada" onclick="setSugestaoFilter('implementada',this)">🟢 Implementadas</button>
                        <button class="btn-admin btn-admin-sm sug-filter" data-filtro="rejeitada" onclick="setSugestaoFilter('rejeitada',this)">🔴 Rejeitadas</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Título</th>
                                <th>Anexos</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="sugestoes-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="sugestoes-pagination"></div>
                </div>
            </div>
        </div>

        <!-- ===== BUGS ===== -->
        <div id="section-bugs" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">🐞 Relatórios de Bugs</div>
                <div class="admin-card-body">
                    <div style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="btn-admin btn-admin-sm bug-filter active" data-filtro="todos" onclick="setBugFilter('todos',this)">Todos</button>
                        <button class="btn-admin btn-admin-sm bug-filter" data-filtro="novo" onclick="setBugFilter('novo',this)">🟡 Novos</button>
                        <button class="btn-admin btn-admin-sm bug-filter" data-filtro="analisando" onclick="setBugFilter('analisando',this)">🔵 Analisando</button>
                        <button class="btn-admin btn-admin-sm bug-filter" data-filtro="corrigido" onclick="setBugFilter('corrigido',this)">🟢 Corrigidos</button>
                        <button class="btn-admin btn-admin-sm bug-filter" data-filtro="nao_reproduzivel" onclick="setBugFilter('nao_reproduzivel',this)">🟠 Não Reproduzível</button>
                        <button class="btn-admin btn-admin-sm bug-filter" data-filtro="rejeitado" onclick="setBugFilter('rejeitado',this)">🔴 Rejeitados</button>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Título</th>
                                <th>Anexos</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="bugs-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="bugs-pagination"></div>
                </div>
            </div>
        </div>

        <!-- SECTION: Anúncios -->
        <div id="section-anuncios" class="admin-section" style="display:none;">
            <div class="admin-card">
                <div class="admin-card-title">📢 Anúncios</div>
                <div class="admin-card-body">
                    <div style="background:#f0f5fb;border:1px solid #c0d0e6;border-radius:6px;padding:15px;margin-bottom:15px;">
                        <div style="font-size:12px;font-weight:bold;color:var(--title);margin-bottom:10px;">Novo Anúncio</div>
                        <input type="text" id="anuncio-titulo" placeholder="Título do anúncio (máx 100 caracteres)" maxlength="100" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:12px;margin-bottom:8px;box-sizing:border-box;">
                        <div id="anuncio-editor-container" style="background:#fff;border-radius:4px;margin-bottom:4px;">
                            <div id="anuncio-editor" style="height:180px;font-size:13px;"></div>
                        </div>
                        <div style="font-size:10px;color:#999;text-align:right;margin-bottom:4px;">Use a barra acima para formatar o texto</div>
                        <div style="margin-top:8px;margin-bottom:8px;">
                            <label style="font-size:11px;color:#555;display:block;margin-bottom:4px;">📷 Foto do anúncio <span style="color:#999;">(recomendado: 300x169px, JPG/PNG, máx 200KB)</span></label>
                            <input type="file" id="anuncio-foto" accept="image/*" style="font-size:11px;" onchange="previewAnuncioFoto(this)">
                            <div id="anuncio-foto-preview" style="margin-top:6px;display:none;"><img id="anuncio-foto-img" style="max-width:300px;max-height:169px;border-radius:4px;border:1px solid #ccc;"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                            <span style="font-size:10px;color:#999;">Todos os usuários ativos serão notificados.</span>
                            <button class="btn-admin btn-admin-primary" onclick="criarAnuncio()">Publicar Anúncio</button>
                        </div>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th>Título</th>
                                <th>Mensagem</th>
                                <th>Admin</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="anuncios-tbody"></tbody>
                    </table>
                    <div class="admin-pagination" id="anuncios-pagination"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: Editar Anúncio -->
<div class="admin-modal-overlay" id="modal-editar-anuncio">
    <div class="admin-modal" style="width:700px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;">
        <div class="admin-modal-header"><span>✏️ Editar Anúncio #<span id="edit-anuncio-id"></span></span> <button class="admin-modal-close" onclick="closeModal('modal-editar-anuncio')">&times;</button></div>
        <div class="admin-modal-body" style="overflow-y:auto;flex:1;padding:15px;">
            <input type="hidden" id="edit-anuncio-hidden-id">
            <div style="margin-bottom:10px;">
                <label style="font-size:11px;color:#555;display:block;margin-bottom:4px;font-weight:bold;">Título</label>
                <input type="text" id="edit-anuncio-titulo" maxlength="100" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:12px;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:11px;color:#555;display:block;margin-bottom:4px;font-weight:bold;">Mensagem</label>
                <div id="edit-anuncio-editor" style="height:200px;font-size:13px;background:#fff;"></div>
            </div>
            <div style="margin-bottom:10px;">
                <label style="font-size:11px;color:#555;display:block;margin-bottom:4px;font-weight:bold;">📷 Foto</label>
                <div id="edit-anuncio-foto-atual" style="margin-bottom:6px;"></div>
                <input type="file" id="edit-anuncio-foto" accept="image/*" style="font-size:11px;" onchange="previewEditAnuncioFoto(this)">
                <div id="edit-anuncio-foto-preview" style="margin-top:6px;display:none;"><img id="edit-anuncio-foto-img" style="max-width:300px;max-height:169px;border-radius:4px;border:1px solid #ccc;"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:15px;">
                <button class="btn-admin" onclick="closeModal('modal-editar-anuncio')" style="background:#ccc;color:#333;">Cancelar</button>
                <button class="btn-admin btn-admin-primary" onclick="salvarAnuncioEdit()">💾 Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Gerenciar Denúncia (expandido) -->
<div class="admin-modal-overlay" id="modal-denuncia">
    <div class="admin-modal" style="width:900px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;">
        <div class="admin-modal-header"><span>⚠️ Gerenciar Denúncia #<span id="denuncia-title-id"></span></span> <button class="admin-modal-close" onclick="closeModal('modal-denuncia')">&times;</button></div>
        <div class="admin-modal-body" style="overflow-y:auto;flex:1;padding:0;">
            <!-- Topo: Info da denúncia -->
            <div style="padding:15px;background:#fef9e7;border-bottom:1px solid #f0e0a0;">
                <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:1;min-width:250px;">
                        <div style="font-size:11px;color:#666;margin-bottom:4px;">MOTIVO DA DENÚNCIA</div>
                        <div id="den-motivo-text" style="background:#fff;padding:10px;border:1px solid #e8d88e;border-radius:4px;font-size:12px;white-space:pre-wrap;max-height:80px;overflow-y:auto;"></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:4px;font-size:11px;min-width:160px;">
                        <div><strong>Status:</strong> <span id="den-status-badge"></span></div>
                        <div><strong>Data:</strong> <span id="den-data-text"></span></div>
                        <div id="den-resolvido-info" style="display:none;"><strong>Resolvido por:</strong> <span id="den-resolvido-nome"></span></div>
                    </div>
                </div>
            </div>

            <!-- Perfis lado a lado -->
            <div style="display:flex;gap:0;border-bottom:1px solid var(--line);">
                <!-- Denunciante -->
                <div style="flex:1;padding:12px;border-right:1px solid var(--line);">
                    <div style="font-size:10px;font-weight:bold;color:#27ae60;text-transform:uppercase;margin-bottom:8px;">👤 Denunciante</div>
                    <div id="den-perfil-denunciante" style="font-size:11px;"></div>
                </div>
                <!-- Denunciado -->
                <div style="flex:1;padding:12px;">
                    <div style="font-size:10px;font-weight:bold;color:#e74c3c;text-transform:uppercase;margin-bottom:8px;">🚨 Denunciado</div>
                    <div id="den-perfil-denunciado" style="font-size:11px;"></div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="border-bottom:1px solid var(--line);background:#f8f9fa;padding:0 12px;">
                <div style="display:flex;gap:0;" id="den-tabs">
                    <button class="den-tab active" onclick="switchDenTab('mensagens',this)">✉️ Mensagens <span id="den-tab-msgs-count" class="den-tab-count"></span></button>
                    <button class="den-tab" onclick="switchDenTab('recados',this)">📝 Recados <span id="den-tab-recs-count" class="den-tab-count"></span></button>
                    <button class="den-tab" onclick="switchDenTab('depoimentos',this)">🌟 Depoimentos <span id="den-tab-deps-count" class="den-tab-count"></span></button>
                    <button class="den-tab" onclick="switchDenTab('outras',this)">⚠️ Outras Denúncias <span id="den-tab-outras-count" class="den-tab-count"></span></button>
                    <button class="den-tab" onclick="switchDenTab('chat',this)">💬 Chat <span id="den-tab-chat-count" class="den-tab-count"></span></button>
                </div>
            </div>

            <!-- Conteúdo das tabs -->
            <div style="padding:12px;min-height:150px;max-height:250px;overflow-y:auto;" id="den-tab-content">
                <div style="text-align:center;color:#999;padding:30px;font-size:11px;">Carregando...</div>
            </div>

            <!-- Ações do admin -->
            <div style="padding:12px;border-top:1px solid var(--line);background:#f8f9fa;">
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="flex:1;">
                        <label style="font-size:11px;font-weight:bold;color:var(--title);display:block;margin-bottom:4px;">Alterar Status</label>
                        <select id="denuncia-status-select" style="width:100%;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:12px;">
                            <option value="pendente">🟡 Pendente</option>
                            <option value="analisando">🔵 Analisando</option>
                            <option value="respondido">💬 Respondido</option>
                            <option value="resolvida">🟢 Resolvida</option>
                            <option value="rejeitada">🔴 Rejeitada</option>
                        </select>
                    </div>
                    <div style="flex:2;">
                        <label style="font-size:11px;font-weight:bold;color:var(--title);display:block;margin-bottom:4px;">Informações Internas sobre a denúncia</label>
                        <textarea id="denuncia-resposta" style="width:100%;min-height:50px;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:12px;font-family:inherit;box-sizing:border-box;resize:vertical;" placeholder="Observações internas para moderadores sobre este caso..."></textarea>
                    </div>
                </div>
                <input type="hidden" id="denuncia-id-modal">
            </div>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-denuncia')">Cancelar</button>
            <button class="btn-admin btn-admin-danger" onclick="excluirDenunciaFromModal()" style="margin-right:auto;">🗑️ Excluir Denúncia</button>
            <button class="btn-admin btn-admin-primary" onclick="salvarStatusDenuncia()">💾 Salvar</button>
        </div>
    </div>
</div>

<!-- MODAL: Gerenciar Denúncia de Comunidade -->
<div class="admin-modal-overlay" id="modal-denuncia-comm">
    <div class="admin-modal" style="width:900px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;">
        <div class="admin-modal-header"><span>🏠 Denúncia Comunidade #<span id="dencomm-title-id"></span></span> <button class="admin-modal-close" onclick="closeModal('modal-denuncia-comm')">&times;</button></div>
        <div class="admin-modal-body" style="overflow-y:auto;flex:1;padding:0;">
            <!-- Topo: Info -->
            <div style="padding:15px;background:#fef9e7;border-bottom:1px solid #f0e0a0;">
                <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:1;min-width:250px;">
                        <div style="font-size:11px;color:#666;margin-bottom:4px;">MOTIVO DA DENÚNCIA</div>
                        <div id="dencomm-motivo-text" style="background:#fff;padding:10px;border:1px solid #e8d88e;border-radius:4px;font-size:12px;white-space:pre-wrap;max-height:80px;overflow-y:auto;"></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:4px;font-size:11px;min-width:160px;">
                        <div><strong>Status:</strong> <span id="dencomm-status-badge"></span></div>
                        <div><strong>Data:</strong> <span id="dencomm-data-text"></span></div>
                        <div id="dencomm-resolvido-info" style="display:none;"><strong>Resolvido por:</strong> <span id="dencomm-resolvido-nome"></span></div>
                    </div>
                </div>
            </div>

            <!-- Perfis: Denunciante + Comunidade lado a lado -->
            <div style="display:flex;gap:0;border-bottom:1px solid var(--line);">
                <div style="flex:1;padding:12px;border-right:1px solid var(--line);">
                    <div style="font-size:10px;font-weight:bold;color:#27ae60;text-transform:uppercase;margin-bottom:8px;">👤 Denunciante</div>
                    <div id="dencomm-perfil-denunciante" style="font-size:11px;"></div>
                </div>
                <div style="flex:1;padding:12px;">
                    <div style="font-size:10px;font-weight:bold;color:#e74c3c;text-transform:uppercase;margin-bottom:8px;">🏠 Comunidade Denunciada</div>
                    <div id="dencomm-perfil-comunidade" style="font-size:11px;"></div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="border-bottom:1px solid var(--line);background:#f8f9fa;padding:0 12px;">
                <div style="display:flex;gap:0;" id="dencomm-tabs">
                    <button class="den-tab active" onclick="switchDenCommTab('outras',this)">⚠️ Outras Denúncias <span id="dencomm-tab-outras-count" class="den-tab-count"></span></button>
                    <button class="den-tab" onclick="switchDenCommTab('chat',this)">💬 Chat <span id="dencomm-tab-chat-count" class="den-tab-count"></span></button>
                </div>
            </div>

            <!-- Conteúdo -->
            <div style="padding:12px;min-height:150px;max-height:250px;overflow-y:auto;" id="dencomm-tab-content">
                <div style="text-align:center;color:#999;padding:30px;font-size:11px;">Carregando...</div>
            </div>

            <!-- Ações -->
            <div style="padding:12px;border-top:1px solid var(--line);background:#f8f9fa;">
                <div style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="flex:1;">
                        <label style="font-size:11px;font-weight:bold;color:var(--title);display:block;margin-bottom:4px;">Alterar Status</label>
                        <select id="dencomm-status-select" style="width:100%;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:12px;">
                            <option value="pendente">🟡 Pendente</option>
                            <option value="analisando">🔵 Analisando</option>
                            <option value="respondido">💬 Respondido</option>
                            <option value="resolvida">🟢 Resolvida</option>
                            <option value="rejeitada">🔴 Rejeitada</option>
                        </select>
                    </div>
                    <div style="flex:2;">
                        <label style="font-size:11px;font-weight:bold;color:var(--title);display:block;margin-bottom:4px;">Informações Internas</label>
                        <textarea id="dencomm-resposta" style="width:100%;min-height:50px;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:12px;font-family:inherit;box-sizing:border-box;resize:vertical;" placeholder="Observações internas..."></textarea>
                    </div>
                </div>
                <input type="hidden" id="dencomm-id-modal">
            </div>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-denuncia-comm')">Cancelar</button>
            <button class="btn-admin btn-admin-danger" onclick="excluirDenunciaCommFromModal()" style="margin-right:auto;">🗑️ Excluir</button>
            <button class="btn-admin btn-admin-primary" onclick="salvarStatusDenunciaComm()">💾 Salvar</button>
        </div>
    </div>
</div>

<!-- MODAL: Editar Usuário -->
<div class="admin-modal-overlay" id="modal-editar-user">
    <div class="admin-modal">
        <div class="admin-modal-header">Editar Usuário <button class="admin-modal-close" onclick="closeModal('modal-editar-user')">&times;</button></div>
        <div class="admin-modal-body">
            <input type="hidden" id="edit-user-id">
            <label>Nome</label>
            <input type="text" id="edit-user-nome">
            <label>Email</label>
            <input type="email" id="edit-user-email">
            <label>Sexo</label>
            <select id="edit-user-sexo">
                <option value="M">Masculino</option>
                <option value="F">Feminino</option>
            </select>
            <label>Admin</label>
            <select id="edit-user-admin">
                <option value="0">Não</option>
                <option value="1">Sim</option>
            </select>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-editar-user')">Cancelar</button>
            <button class="btn-admin btn-admin-primary" onclick="salvarEditUser()">Salvar</button>
        </div>
    </div>
</div>

<!-- MODAL: Resetar Senha -->
<div class="admin-modal-overlay" id="modal-resetar-senha">
    <div class="admin-modal">
        <div class="admin-modal-header">Resetar Senha <button class="admin-modal-close" onclick="closeModal('modal-resetar-senha')">&times;</button></div>
        <div class="admin-modal-body">
            <input type="hidden" id="reset-user-id">
            <p style="font-size:11px;color:#666;margin:0 0 10px;">Defina uma nova senha para o usuário <strong id="reset-user-name"></strong>:</p>
            <label>Nova Senha</label>
            <input type="text" id="reset-new-password" placeholder="Digite a nova senha...">
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-resetar-senha')">Cancelar</button>
            <button class="btn-admin btn-admin-danger" onclick="confirmarResetSenha()">Resetar Senha</button>
        </div>
    </div>
</div>

<!-- MODAL: Banir Usuário -->
<div class="admin-modal-overlay" id="modal-banir-user">
    <div class="admin-modal" style="width:480px;">
        <div class="admin-modal-header" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#ff4444;">
            🚫 Banir Usuário <button class="admin-modal-close" onclick="closeModal('modal-banir-user')">&times;</button>
        </div>
        <div class="admin-modal-body">
            <input type="hidden" id="ban-user-id">
            <p style="font-size:12px;color:#666;margin:0 0 15px;">Banir o usuário <strong id="ban-user-name" style="color:#cc0000;"></strong>:</p>
            
            <label style="font-size:11px;font-weight:bold;color:var(--title);">Tipo de Banimento</label>
            <div style="display:flex;gap:10px;margin:8px 0 15px;">
                <label style="flex:1;display:flex;align-items:center;gap:6px;padding:10px;border:2px solid var(--line);border-radius:6px;cursor:pointer;font-size:12px;transition:0.2s;" id="ban-tipo-temp-label">
                    <input type="radio" name="ban-tipo" value="temporario" id="ban-tipo-temp" checked onchange="toggleBanTipo()">
                    <span>⏳ <b>Temporário</b><br><span style="font-size:10px;color:#888;">Por dias</span></span>
                </label>
                <label style="flex:1;display:flex;align-items:center;gap:6px;padding:10px;border:2px solid var(--line);border-radius:6px;cursor:pointer;font-size:12px;transition:0.2s;" id="ban-tipo-perm-label">
                    <input type="radio" name="ban-tipo" value="permanente" id="ban-tipo-perm" onchange="toggleBanTipo()">
                    <span>⛔ <b>Permanente</b><br><span style="font-size:10px;color:#888;">Para sempre</span></span>
                </label>
            </div>

            <div id="ban-dias-wrap">
                <label style="font-size:11px;font-weight:bold;color:var(--title);">Duração (dias)</label>
                <div style="display:flex;gap:8px;margin:5px 0 15px;flex-wrap:wrap;">
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(1)" style="font-size:11px;">1 dia</button>
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(3)" style="font-size:11px;">3 dias</button>
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(7)" style="font-size:11px;">7 dias</button>
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(15)" style="font-size:11px;">15 dias</button>
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(30)" style="font-size:11px;">30 dias</button>
                    <button type="button" class="btn-admin btn-admin-sm" onclick="setBanDias(90)" style="font-size:11px;">90 dias</button>
                </div>
                <input type="number" id="ban-dias" min="1" max="3650" value="7" style="width:100%;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:13px;text-align:center;">
            </div>

            <label style="font-size:11px;font-weight:bold;color:var(--title);">Motivo (opcional)</label>
            <textarea id="ban-motivo" style="width:100%;min-height:60px;padding:8px;border:1px solid var(--line);border-radius:3px;font-size:12px;font-family:inherit;box-sizing:border-box;resize:vertical;" placeholder="Descreva o motivo do banimento..."></textarea>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-banir-user')">Cancelar</button>
            <button class="btn-admin" onclick="confirmarBanir()" style="background:#cc0000;color:#fff;border-color:#990000;">🚫 Confirmar Banimento</button>
        </div>
    </div>
</div>

<!-- MODAL: Gerar Convites -->
<div class="admin-modal-overlay" id="modal-gerar-convites">
    <div class="admin-modal">
        <div class="admin-modal-header">Gerar Convites <button class="admin-modal-close" onclick="closeModal('modal-gerar-convites')">&times;</button></div>
        <div class="admin-modal-body">
            <label>Quantidade (máx 50)</label>
            <input type="number" id="gerar-qtd" min="1" max="50" value="5">
            <div id="generated-tokens" style="margin-top:10px;display:none;">
                <label>Tokens Gerados:</label>
                <textarea id="tokens-list" style="width:100%;height:100px;font-family:monospace;font-size:12px;padding:8px;border:1px solid var(--line);border-radius:3px;box-sizing:border-box;" readonly></textarea>
            </div>
        </div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-gerar-convites')">Fechar</button>
            <button class="btn-admin btn-admin-success" id="btn-gerar-convites" onclick="gerarConvites()">Gerar</button>
        </div>
    </div>
</div>

<!-- MODAL: Detalhes Usuário -->
<div class="admin-modal-overlay" id="modal-detalhes-user">
    <div class="admin-modal" style="width:550px;">
        <div class="admin-modal-header">Detalhes do Usuário <button class="admin-modal-close" onclick="closeModal('modal-detalhes-user')">&times;</button></div>
        <div class="admin-modal-body" id="detalhes-user-body"></div>
        <div class="admin-modal-footer">
            <button class="btn-admin" onclick="closeModal('modal-detalhes-user')">Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL: Detalhe Sugestão -->
<div class="admin-modal-overlay" id="modal-sugestao">
    <div class="admin-modal" style="width:700px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;">
        <div class="admin-modal-header"><span>💡 Sugestão #<span id="sug-detail-id"></span></span> <button class="admin-modal-close" onclick="closeModal('modal-sugestao')">&times;</button></div>
        <div class="admin-modal-body" style="overflow-y:auto;flex:1;" id="sug-detail-body"></div>
        <div class="admin-modal-footer" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select id="sug-status-select" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
                <option value="nova">🟡 Nova</option>
                <option value="analisando">🔵 Analisando</option>
                <option value="aprovada">✅ Aprovada</option>
                <option value="implementada">🟢 Implementada</option>
                <option value="rejeitada">🔴 Rejeitada</option>
            </select>
            <input type="text" id="sug-resposta-input" placeholder="Resposta ao usuário (opcional)" style="flex:1;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:12px;min-width:150px;">
            <button class="btn-admin btn-admin-primary" onclick="salvarStatusSugestao()">💾 Salvar</button>
            <button class="btn-admin btn-admin-danger" onclick="excluirSugestaoModal()">🗑️ Excluir</button>
            <button class="btn-admin" onclick="closeModal('modal-sugestao')">Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL: Detalhe Bug -->
<div class="admin-modal-overlay" id="modal-bug">
    <div class="admin-modal" style="width:700px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;">
        <div class="admin-modal-header"><span>🐞 Bug #<span id="bug-detail-id"></span></span> <button class="admin-modal-close" onclick="closeModal('modal-bug')">&times;</button></div>
        <div class="admin-modal-body" style="overflow-y:auto;flex:1;" id="bug-detail-body"></div>
        <div class="admin-modal-footer" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select id="bug-status-select" style="padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
                <option value="novo">🟡 Novo</option>
                <option value="analisando">🔵 Analisando</option>
                <option value="corrigido">🟢 Corrigido</option>
                <option value="nao_reproduzivel">🟠 Não Reproduzível</option>
                <option value="rejeitado">🔴 Rejeitado</option>
            </select>
            <input type="text" id="bug-resposta-input" placeholder="Resposta ao usuário (opcional)" style="flex:1;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:12px;min-width:150px;">
            <button class="btn-admin btn-admin-primary" onclick="salvarStatusBug()">💾 Salvar</button>
            <button class="btn-admin btn-admin-danger" onclick="excluirBugModal()">🗑️ Excluir</button>
            <button class="btn-admin" onclick="closeModal('modal-bug')">Fechar</button>
        </div>
    </div>
</div>

<script src="/js/toast.js"></script>
<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ===== GLOBAL STATE =====
let currentSection = 'dashboard';
let conviteFilter = 'todos';
let sugestaoFilter = 'todos';
let bugFilter = 'todos';

// ===== NAVIGATION =====
function showSection(section) {
    document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.admin-nav-item').forEach(b => b.classList.remove('active'));
    document.getElementById('section-' + section).style.display = 'block';
    event.target.closest('.admin-nav-item').classList.add('active');
    currentSection = section;

    // Load data
    if (section === 'dashboard') loadDashboard();
    else if (section === 'usuarios') loadUsuarios(1);
    else if (section === 'convites') loadConvites(1);
    else if (section === 'recados') loadRecados(1);
    else if (section === 'depoimentos') loadDepoimentos(1);
    else if (section === 'mensagens') loadMensagens(1);
    else if (section === 'denuncias') loadDenuncias(1);
    else if (section === 'denuncias_comunidades') loadDenunciasComunidades(1);
    else if (section === 'sugestoes') loadSugestoes(1);
    else if (section === 'bugs') loadBugs(1);
    else if (section === 'anuncios') { loadAnuncios(1); setTimeout(initQuillAnuncio, 100); }
}

// ===== TOAST =====
function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = 'admin-toast ' + (type || 'success');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ===== MODAL =====
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// ===== HELPERS =====
function formatDate(d) {
    if (!d) return '-';
    return d.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
}

function truncate(str, len) {
    if (!str) return '-';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

// ===== DASHBOARD =====
async function loadDashboard() {
    try {
        const resp = await fetch('/api/admin/stats');
        const data = await resp.json();
        if (!data.success) return;

        const s = data.stats;
        document.getElementById('stats-grid').innerHTML = `
            <div class="stat-box pink"><div class="stat-number">${s.totalUsuarios}</div><div class="stat-label">Usuários</div></div>
            <div class="stat-box"><div class="stat-number">${s.novosHoje}</div><div class="stat-label">Novos Hoje</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalAmizades}</div><div class="stat-label">Amizades</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalRecados}</div><div class="stat-label">Recados</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalMensagens}</div><div class="stat-label">Mensagens</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalDepoimentos}</div><div class="stat-label">Depoimentos</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalFotos}</div><div class="stat-label">Fotos</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalVideos}</div><div class="stat-label">Vídeos</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalVisitas}</div><div class="stat-label">Visitas</div></div>
            <div class="stat-box"><div class="stat-number">${s.totalConvites}</div><div class="stat-label">Convites Total</div></div>
            <div class="stat-box"><div class="stat-number">${s.convitesDisponiveis}</div><div class="stat-label">Convites Disp.</div></div>
            <div class="stat-box"><div class="stat-number">${s.convitesUsados}</div><div class="stat-label">Convites Usados</div></div>
            <div class="stat-box" style="border-left:3px solid #e67e22;"><div class="stat-number">${s.totalDenuncias}</div><div class="stat-label">Denúncias</div></div>
            <div class="stat-box" style="border-left:3px solid #c0392b;"><div class="stat-number">${s.denunciasPendentes}</div><div class="stat-label">Den. Pendentes</div></div>
            <div class="stat-box" style="border-left:3px solid #e67e22;"><div class="stat-number">${s.totalDenunciasComunidades || 0}</div><div class="stat-label">Den. Comunid.</div></div>
            <div class="stat-box" style="border-left:3px solid #c0392b;"><div class="stat-number">${s.denunciasComunidadesPendentes || 0}</div><div class="stat-label">Den.Com. Pend.</div></div>
            <div class="stat-box" style="border-left:3px solid #f1c40f;"><div class="stat-number">${s.totalSugestoes}</div><div class="stat-label">Sugestões</div></div>
            <div class="stat-box" style="border-left:3px solid #f39c12;"><div class="stat-number">${s.sugestoesNovas}</div><div class="stat-label">Sug. Novas</div></div>
            <div class="stat-box" style="border-left:3px solid #9b59b6;"><div class="stat-number">${s.totalBugs}</div><div class="stat-label">Bugs</div></div>
            <div class="stat-box" style="border-left:3px solid #8e44ad;"><div class="stat-number">${s.bugsNovos}</div><div class="stat-label">Bugs Novos</div></div>
        `;

        const list = document.getElementById('recent-users');
        if (data.recentes.length === 0) {
            list.innerHTML = '<li style="color:#999;">Nenhum usuário ainda.</li>';
        } else {
            list.innerHTML = data.recentes.map(u =>
                `<li><strong style="color:var(--link);">#${u.id}</strong> <a href="/profile.php?uid=${u.id}" target="_blank" style="color:var(--title);text-decoration:none;">${escapeHtml(u.nome)}</a> <span style="color:#999;">(${escapeHtml(u.email)})</span> <span style="margin-left:auto;color:#999;font-size:10px;">${formatDate(u.criado_em)}</span></li>`
            ).join('');
        }
    } catch(err) {
        console.error('Dashboard error:', err);
    }
}

// ===== USUARIOS =====
async function loadUsuarios(page) {
    try {
        const busca = document.getElementById('user-search').value;
        const resp = await fetch('/api/admin/usuarios?page=' + page + '&busca=' + encodeURIComponent(busca));
        const data = await resp.json();
        if (!data.success) return;

        document.getElementById('usuarios-total').textContent = data.total + ' usuários';
        const tbody = document.getElementById('usuarios-tbody');
        
        if (data.usuarios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum usuário encontrado.</td></tr>';
        } else {
            tbody.innerHTML = data.usuarios.map(u => {
                let roleBadge = u.is_admin ? '<span class="badge-admin admin">ADMIN</span>' : '<span class="badge-admin user">Usuário</span>';
                if (u.banido) {
                    roleBadge += ' ' + (u.banido_permanente ? '<span class="badge-admin banned">🚫 BANIDO</span>' : '<span class="badge-admin ban-temp">⏳ SUSPENSO</span>');
                }
                const banBtn = u.banido
                    ? `<button class="btn-admin btn-admin-sm" onclick="desbanirUser('${u.id}', '${escapeHtml(u.nome).replace(/'/g,"\\'")}')" title="Desbanir" style="background:#27ae60;color:#fff;border-color:#1e8449;">✅</button>`
                    : `<button class="btn-admin btn-admin-sm" onclick="abrirBanir('${u.id}', '${escapeHtml(u.nome).replace(/'/g,"\\'")}')" title="Banir" style="background:#1a1a2e;color:#ff4444;border-color:#333;">🚫</button>`;
                return `
                <tr${u.banido ? ' style="opacity:0.7;background:#fff5f5;"' : ''}>
                    <td><strong>#${u.id}</strong></td>
                    <td><div class="user-cell"><img class="user-avatar" src="${u.foto_perfil || '/img/default-avatar.png'}"> <a href="/profile.php?uid=${u.id}" target="_blank" style="color:var(--title);text-decoration:none;">${escapeHtml(truncate(u.nome, 25))}</a></div></td>
                    <td>${escapeHtml(u.email)}</td>
                    <td>${roleBadge}</td>
                    <td>${formatDate(u.criado_em)}</td>
                    <td>${formatDate(u.ultimo_acesso)}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="verDetalhes('${u.id}')" title="Detalhes">👁️</button>
                            <button class="btn-admin btn-admin-sm" onclick="editarUser('${u.id}', '${escapeHtml(u.nome).replace(/'/g,"\\'")}', '${escapeHtml(u.email)}', '${u.sexo}', ${u.is_admin})" title="Editar">✏️</button>
                            <button class="btn-admin btn-admin-sm" onclick="abrirResetSenha('${u.id}', '${escapeHtml(u.nome).replace(/'/g,"\\'")}')" title="Resetar Senha">🔑</button>
                            ${banBtn}
                            <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirUser('${u.id}', '${escapeHtml(u.nome).replace(/'/g,"\\'")}')" title="Excluir">🗑️</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        renderPagination('usuarios-pagination', data.page, data.totalPages, 'loadUsuarios');
    } catch(err) {
        console.error('Usuarios error:', err);
    }
}

async function verDetalhes(id) {
    try {
        const resp = await fetch('/api/admin/usuario/' + id);
        const data = await resp.json();
        if (!data.success) return showToast('Erro ao carregar detalhes.', 'error');

        const u = data.user;
        document.getElementById('detalhes-user-body').innerHTML = `
            <div style="display:flex;gap:15px;align-items:flex-start;">
                <img src="${u.foto_perfil || '/img/default-avatar.png'}" style="width:80px;height:80px;border-radius:4px;object-fit:cover;border:1px solid var(--line);">
                <div style="flex:1;">
                    <div style="font-size:16px;font-weight:bold;color:var(--title);"><a href="/profile.php?uid=${u.id}" target="_blank" style="color:inherit;text-decoration:none;">${escapeHtml(u.nome)}</a></div>
                    <div style="color:#666;font-size:11px;margin-top:2px;">${escapeHtml(u.email)}</div>
                    <div style="margin-top:5px;">
                        ${u.is_admin ? '<span class="badge-admin admin">ADMIN</span>' : '<span class="badge-admin user">Usuário</span>'}
                    </div>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid var(--line);margin:12px 0;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:11px;">
                <div><strong>ID:</strong> #${u.id}</div>
                <div><strong>Sexo:</strong> ${u.sexo === 'F' ? 'Feminino' : 'Masculino'}</div>
                <div><strong>Cadastro:</strong> ${formatDate(u.criado_em)}</div>
                <div><strong>Último Acesso:</strong> ${formatDate(u.ultimo_acesso)}</div>
                <div><strong>Cidade:</strong> ${escapeHtml(u.cidade || '-')}</div>
                <div><strong>Estado:</strong> ${escapeHtml(u.estado || '-')}</div>
                <div><strong>Amigos:</strong> ${data.totalAmigos}</div>
                <div><strong>Recados:</strong> ${data.totalRecados}</div>
                <div><strong>Fotos:</strong> ${data.totalFotos}</div>
                <div><strong>Vídeos:</strong> ${data.totalVideos}</div>
                <div><strong>Convites Gerados:</strong> ${data.convitesGerados}</div>
                <div><strong>Nascimento:</strong> ${u.nascimento || '-'}</div>
            </div>
            ${u.quem_sou_eu ? '<hr style="border:none;border-top:1px solid var(--line);margin:12px 0;"><div style="font-size:11px;"><strong>Quem sou eu:</strong><div style="color:#555;margin-top:3px;">' + escapeHtml(u.quem_sou_eu) + '</div></div>' : ''}
            ${u.banido ? `
            <hr style="border:none;border-top:1px solid var(--line);margin:12px 0;">
            <div style="background:${u.banido_permanente ? '#fee' : '#fff8e1'};border:1px solid ${u.banido_permanente ? '#c00' : '#ff9800'};border-radius:4px;padding:8px;font-size:11px;">
                <div style="font-weight:bold;color:${u.banido_permanente ? '#c00' : '#e65100'};margin-bottom:4px;">
                    ${u.banido_permanente ? '🚫 BANIDO PERMANENTEMENTE' : '⏳ SUSPENSO TEMPORARIAMENTE'}
                </div>
                <div><strong>Motivo:</strong> ${escapeHtml(u.banido_motivo || 'Não informado')}</div>
                <div><strong>Banido em:</strong> ${formatDate(u.banido_em)}</div>
                ${!u.banido_permanente && u.banido_ate ? '<div><strong>Expira em:</strong> ' + formatDate(u.banido_ate) + '</div>' : ''}
            </div>` : ''}
        `;
        openModal('modal-detalhes-user');
    } catch(err) {
        console.error(err);
    }
}

function editarUser(id, nome, email, sexo, isAdmin) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-user-nome').value = nome;
    document.getElementById('edit-user-email').value = email;
    document.getElementById('edit-user-sexo').value = sexo || 'M';
    document.getElementById('edit-user-admin').value = isAdmin ? '1' : '0';
    openModal('modal-editar-user');
}

async function salvarEditUser() {
    const body = {
        id: document.getElementById('edit-user-id').value,
        nome: document.getElementById('edit-user-nome').value,
        email: document.getElementById('edit-user-email').value,
        sexo: document.getElementById('edit-user-sexo').value,
        is_admin: parseInt(document.getElementById('edit-user-admin').value)
    };
    try {
        const resp = await fetch('/api/admin/usuario/editar', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
        const data = await resp.json();
        if (data.success) {
            showToast('Usuário atualizado!');
            closeModal('modal-editar-user');
            loadUsuarios(1);
        } else {
            showToast(data.message, 'error');
        }
    } catch(err) { showToast('Erro ao salvar.', 'error'); }
}

function abrirResetSenha(id, nome) {
    document.getElementById('reset-user-id').value = id;
    document.getElementById('reset-user-name').textContent = nome;
    document.getElementById('reset-new-password').value = '';
    openModal('modal-resetar-senha');
}

async function confirmarResetSenha() {
    const id = document.getElementById('reset-user-id').value;
    const novaSenha = document.getElementById('reset-new-password').value;
    if (!novaSenha || novaSenha.length < 4) return showToast('Senha deve ter no mínimo 4 caracteres.', 'error');

    try {
        const resp = await fetch('/api/admin/usuario/resetar-senha', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id, novaSenha }) });
        const data = await resp.json();
        if (data.success) {
            showToast('Senha resetada!');
            closeModal('modal-resetar-senha');
        } else {
            showToast(data.message, 'error');
        }
    } catch(err) { showToast('Erro ao resetar.', 'error'); }
}

// ===== Banir/Desbanir =====
function abrirBanir(id, nome) {
    document.getElementById('ban-user-id').value = id;
    document.getElementById('ban-user-name').textContent = nome;
    document.getElementById('ban-dias').value = 7;
    document.getElementById('ban-motivo').value = '';
    document.getElementById('ban-tipo-temp').checked = true;
    toggleBanTipo();
    openModal('modal-banir-user');
}

function toggleBanTipo() {
    const isPerm = document.getElementById('ban-tipo-perm').checked;
    document.getElementById('ban-dias-wrap').style.display = isPerm ? 'none' : 'block';
    document.getElementById('ban-tipo-temp-label').style.borderColor = isPerm ? 'var(--line)' : '#2196F3';
    document.getElementById('ban-tipo-perm-label').style.borderColor = isPerm ? '#cc0000' : 'var(--line)';
}

function setBanDias(d) {
    document.getElementById('ban-dias').value = d;
}

async function confirmarBanir() {
    const id = document.getElementById('ban-user-id').value;
    const tipo = document.getElementById('ban-tipo-perm').checked ? 'permanente' : 'temporario';
    const dias = parseInt(document.getElementById('ban-dias').value) || 7;
    const motivo = document.getElementById('ban-motivo').value.trim();

    const nome = document.getElementById('ban-user-name').textContent;
    const msg = tipo === 'permanente'
        ? 'ATENÇÃO: Banir "' + nome + '" PERMANENTEMENTE?\n\nO usuário não poderá mais acessar o Yorkut.'
        : 'Banir "' + nome + '" por ' + dias + ' dia(s)?';
    showConfirm(msg, async function() {
        try {
            const resp = await fetch('/api/admin/usuario/banir', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ id, tipo, dias, motivo })
            });
            const data = await resp.json();
            if (data.success) {
                showToast(data.message);
                closeModal('modal-banir-user');
                loadUsuarios(1);
            } else {
                showToast(data.message, 'error');
            }
        } catch(err) { showToast('Erro ao banir.', 'error'); }
    });
}

async function desbanirUser(id, nome) {
    showConfirm('Desbanir o usuário "' + nome + '"?', async function() {
        try {
            const resp = await fetch('/api/admin/usuario/desbanir', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ id })
            });
            const data = await resp.json();
            if (data.success) {
                showToast(data.message);
                loadUsuarios(1);
            } else {
                showToast(data.message, 'error');
            }
        } catch(err) { showToast('Erro ao desbanir.', 'error'); }
    });
}

async function excluirUser(id, nome) {
    showConfirm('ATENÇÃO: Excluir o usuário "' + nome + '" (ID #' + id + ')?\n\nTodos os dados serão permanentemente removidos!', async function() {
        try {
            const resp = await fetch('/api/admin/usuario/excluir', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
            const data = await resp.json();
            if (data.success) {
                showToast('Usuário excluído!');
                loadUsuarios(1);
            } else {
                showToast(data.message, 'error');
            }
        } catch(err) { showToast('Erro ao excluir.', 'error'); }
    });
}

// ===== CONVITES =====
function setConviteFilter(f, btn) {
    conviteFilter = f;
    document.querySelectorAll('#section-convites .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadConvites(1);
}

async function loadConvites(page) {
    try {
        const resp = await fetch('/api/admin/convites?page=' + page + '&filtro=' + conviteFilter);
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('convites-tbody');
        if (data.convites.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum convite encontrado.</td></tr>';
        } else {
            tbody.innerHTML = data.convites.map(c => `
                <tr>
                    <td>#${c.id}</td>
                    <td><strong style="font-family:monospace;font-size:12px;color:var(--title);">${c.token}</strong></td>
                    <td>${c.criado_por ? '<a href="/profile.php?uid=' + c.criado_por + '" target="_blank" style="color:var(--title);text-decoration:none;">' + c.criador_nome + '</a>' : '<span style="color:#999;">Sistema</span>'}</td>
                    <td>${c.usado ? '<span style="color:#27ae60;font-weight:bold;">✓ Usado</span>' : '<span style="color:var(--orkut-blue);">Disponível</span>'}</td>
                    <td>${c.usado_por ? '<a href="/profile.php?uid=' + c.usado_por + '" target="_blank" style="color:var(--title);text-decoration:none;">' + c.usuario_nome + '</a>' : '-'}</td>
                    <td>${formatDate(c.criado_em)}</td>
                    <td><button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirConvite(${c.id})">🗑️</button></td>
                </tr>
            `).join('');
        }

        renderPagination('convites-pagination', data.page, data.totalPages, 'loadConvites');
    } catch(err) {
        console.error(err);
    }
}

function openGerarConvites() {
    document.getElementById('gerar-qtd').value = 5;
    document.getElementById('generated-tokens').style.display = 'none';
    document.getElementById('btn-gerar-convites').style.display = '';
    openModal('modal-gerar-convites');
}

async function gerarConvites() {
    const qtd = parseInt(document.getElementById('gerar-qtd').value) || 5;
    try {
        const resp = await fetch('/api/admin/convites/gerar', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ quantidade: qtd }) });
        const data = await resp.json();
        if (data.success) {
            showToast(data.message);
            document.getElementById('tokens-list').value = data.tokens.join('\n');
            document.getElementById('generated-tokens').style.display = 'block';
            document.getElementById('btn-gerar-convites').style.display = 'none';
            loadConvites(1);
        } else {
            showToast(data.message, 'error');
        }
    } catch(err) { showToast('Erro ao gerar.', 'error'); }
}

async function excluirConvite(id) {
    showConfirm('Excluir este convite?', async function() {
        try {
            const resp = await fetch('/api/admin/convite/excluir', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
            const data = await resp.json();
            if (data.success) { showToast('Convite excluído!'); loadConvites(1); }
            else showToast(data.message, 'error');
        } catch(err) { showToast('Erro.', 'error'); }
    });
}

// ===== MODERAÇÃO: RECADOS =====
async function loadRecados(page) {
    try {
        const resp = await fetch('/api/admin/recados?page=' + page);
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('recados-tbody');
        if (data.recados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">Nenhum recado.</td></tr>';
        } else {
            tbody.innerHTML = data.recados.map(r => `
                <tr>
                    <td>#${r.id}</td>
                    <td><a href="/profile.php?uid=${r.remetente_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(r.remetente_nome, 20)}</a></td>
                    <td><a href="/profile.php?uid=${r.destinatario_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(r.destinatario_nome, 20)}</a></td>
                    <td><div class="content-preview">${truncate(r.mensagem, 60)}</div></td>
                    <td>${formatDate(r.criado_em)}</td>
                    <td><button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirRecado(${r.id})">🗑️</button></td>
                </tr>
            `).join('');
        }

        renderPagination('recados-pagination', data.page, data.totalPages, 'loadRecados');
    } catch(err) { console.error(err); }
}

async function excluirRecado(id) {
    showConfirm('Excluir recado #' + id + '?', async function() {
        try {
            const resp = await fetch('/api/admin/recado/excluir', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
            const data = await resp.json();
            if (data.success) { showToast('Recado excluído!'); loadRecados(1); }
            else showToast(data.message, 'error');
        } catch(err) { showToast('Erro.', 'error'); }
    });
}

// ===== MODERAÇÃO: DEPOIMENTOS =====
async function loadDepoimentos(page) {
    try {
        const resp = await fetch('/api/admin/depoimentos?page=' + page);
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('depoimentos-tbody');
        if (data.depoimentos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum depoimento.</td></tr>';
        } else {
            tbody.innerHTML = data.depoimentos.map(d => `
                <tr>
                    <td>#${d.id}</td>
                    <td><a href="/profile.php?uid=${d.remetente_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.remetente_nome, 20)}</a></td>
                    <td><a href="/profile.php?uid=${d.destinatario_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.destinatario_nome, 20)}</a></td>
                    <td><div class="content-preview">${truncate(d.mensagem, 60)}</div></td>
                    <td>${d.aprovado ? '<span style="color:#27ae60;">Aprovado</span>' : '<span style="color:#e67e22;">Pendente</span>'}</td>
                    <td>${formatDate(d.criado_em)}</td>
                    <td><button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirDepoimento(${d.id})">🗑️</button></td>
                </tr>
            `).join('');
        }

        renderPagination('depoimentos-pagination', data.page, data.totalPages, 'loadDepoimentos');
    } catch(err) { console.error(err); }
}

async function excluirDepoimento(id) {
    showConfirm('Excluir depoimento #' + id + '?', async function() {
        try {
            const resp = await fetch('/api/admin/depoimento/excluir', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
            const data = await resp.json();
            if (data.success) { showToast('Depoimento excluído!'); loadDepoimentos(1); }
            else showToast(data.message, 'error');
        } catch(err) { showToast('Erro.', 'error'); }
    });
}

// ===== MODERAÇÃO: MENSAGENS =====
async function loadMensagens(page) {
    try {
        const resp = await fetch('/api/admin/mensagens?page=' + page);
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('mensagens-tbody');
        if (data.mensagens.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhuma mensagem.</td></tr>';
        } else {
            tbody.innerHTML = data.mensagens.map(m => `
                <tr>
                    <td>#${m.id}</td>
                    <td><a href="/profile.php?uid=${m.remetente_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(m.remetente_nome, 20)}</a></td>
                    <td><a href="/profile.php?uid=${m.destinatario_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(m.destinatario_nome, 20)}</a></td>
                    <td>${truncate(m.assunto, 30)}</td>
                    <td><div class="content-preview">${truncate(m.mensagem, 50)}</div></td>
                    <td>${formatDate(m.criado_em)}</td>
                    <td><button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirMensagem(${m.id})">🗑️</button></td>
                </tr>
            `).join('');
        }

        renderPagination('mensagens-pagination', data.page, data.totalPages, 'loadMensagens');
    } catch(err) { console.error(err); }
}

async function excluirMensagem(id) {
    showConfirm('Excluir mensagem #' + id + '?', async function() {
        try {
            const resp = await fetch('/api/admin/mensagem/excluir', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
            const data = await resp.json();
            if (data.success) { showToast('Mensagem excluída!'); loadMensagens(1); }
            else showToast(data.message, 'error');
        } catch(err) { showToast('Erro.', 'error'); }
    });
}

// ===== MODERAÇÃO: DENÚNCIAS =====
let denunciaFilter = 'todos';
let _denunciaData = null; // dados completos da denúncia aberta

function setDenunciaFilter(filtro, btn) {
    denunciaFilter = filtro;
    document.querySelectorAll('.den-filter').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    loadDenuncias(1);
}

function statusBadge(status) {
    const map = { pendente: '🟡 Pendente', analisando: '🔵 Analisando', respondido: '💬 Respondido', resolvida: '🟢 Resolvida', rejeitada: '🔴 Rejeitada' };
    const colorMap = { pendente: '#f39c12', analisando: '#3498db', respondido: '#9b59b6', resolvida: '#27ae60', rejeitada: '#e74c3c' };
    return '<span style="color:' + (colorMap[status]||'#999') + ';font-weight:bold;font-size:11px;">' + (map[status]||status) + '</span>';
}

async function loadDenuncias(page) {
    try {
        const resp = await fetch('/api/admin/denuncias?page=' + page + '&filtro=' + encodeURIComponent(denunciaFilter));
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('denuncias-tbody');
        if (data.denuncias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhuma denúncia encontrada.</td></tr>';
        } else {
            tbody.innerHTML = data.denuncias.map(d => `
                <tr>
                    <td>#${d.id}</td>
                    <td><a href="/profile.php?uid=${d.denunciante_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.denunciante_nome, 20)}</a></td>
                    <td><a href="/profile.php?uid=${d.denunciado_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.denunciado_nome, 20)}</a></td>
                    <td><div class="content-preview">${truncate(d.motivo, 50)}</div></td>
                    <td>${statusBadge(d.status)}</td>
                    <td>${formatDate(d.criado_em)}</td>
                    <td style="display:flex;gap:4px;">
                        <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="abrirDenuncia(${d.id})">📋 Analisar</button>
                        <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirDenuncia(${d.id})">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        renderPagination('denuncias-pagination', data.page, data.totalPages, 'loadDenuncias');
    } catch(err) { console.error(err); }
}

function renderDenProfile(prefix, d, stats) {
    const foto = d[prefix + '_foto'] || '/img/default-avatar.png';
    const nome = d[prefix + '_nome'] || '?';
    const email = d[prefix + '_email'] || '';
    const cidade = d[prefix + '_cidade'] || '';
    const estado = d[prefix + '_estado'] || '';
    const criado = d[prefix + '_criado'] || '';
    const acesso = d[prefix + '_acesso'] || '';
    const bio = d[prefix + '_bio'] || '';
    const uid = prefix === 'denunciante' ? d.denunciante_id : d.denunciado_id;

    const banLabel = prefix === 'denunciante' ? 'Banir denunciante' : 'Banir denunciado';
    const banBtnHtml = ' <button class="btn-admin btn-admin-sm" onclick="abrirBanir(\'' + uid + '\', \'' + nome.replace(/'/g, "\\'") + '\')" title="' + banLabel + '" style="background:#1a1a2e;color:#ff4444;border-color:#333;font-size:9px;padding:1px 6px;margin-left:6px;vertical-align:middle;">🚫 Banir</button>';

    let statsHtml = '';
    if (stats) {
        statsHtml = '<div class="den-stat-row">';
        if (stats.totalAmigos !== undefined) statsHtml += '<span class="den-stat-chip">👥 ' + stats.totalAmigos + ' amigos</span>';
        if (stats.totalRecados !== undefined) statsHtml += '<span class="den-stat-chip">📝 ' + stats.totalRecados + ' recados</span>';
        if (stats.totalFotos !== undefined) statsHtml += '<span class="den-stat-chip">📷 ' + stats.totalFotos + ' fotos</span>';
        if (stats.totalDenuncias !== undefined) {
            const cls = stats.totalDenuncias > 1 ? 'den-stat-chip danger' : 'den-stat-chip';
            const label = prefix === 'denunciante' ? 'denúncias feitas' : 'denúncias recebidas';
            statsHtml += '<span class="' + cls + '">⚠️ ' + stats.totalDenuncias + ' ' + label + '</span>';
        }
        statsHtml += '</div>';
    }

    return `
        <div class="den-profile-card">
            <img src="${foto}" onerror="this.src='/img/default-avatar.png'">
            <div class="den-profile-info">
                <div class="name"><a href="/profile.php?uid=${uid}" target="_blank">${nome}</a>${banBtnHtml}</div>
                <div style="color:#666;font-size:10px;">${email}</div>
                ${cidade || estado ? '<div style="color:#888;font-size:10px;">📍 ' + [cidade, estado].filter(Boolean).join(', ') + '</div>' : ''}
                <div style="color:#888;font-size:10px;">📅 Cadastro: ${formatDate(criado)} | Último acesso: ${formatDate(acesso)}</div>
                ${statsHtml}
                ${bio ? '<div style="margin-top:4px;font-size:10px;color:#555;max-height:40px;overflow:hidden;">"' + truncate(bio, 100) + '"</div>' : ''}
            </div>
        </div>
    `;
}

function renderMsgList(items, denuncianteId, tipo) {
    if (!items || items.length === 0) {
        return '<div style="text-align:center;color:#999;padding:20px;font-size:11px;">Nenhum' + (tipo === 'mensagens' ? 'a mensagem' : tipo === 'recados' ? ' recado' : ' depoimento') + ' entre os envolvidos.</div>';
    }
    return items.map(m => {
        const isDenunciante = m.remetente_id === denuncianteId;
        const cls = isDenunciante ? 'from-denunciante' : 'from-denunciado';
        const authorCls = isDenunciante ? 'denunciante' : 'denunciado';
        let content = m.mensagem || '';
        if (tipo === 'mensagens' && m.assunto) content = '<strong>' + m.assunto + '</strong><br>' + content;
        if (tipo === 'depoimentos') content += m.aprovado ? ' <span style="color:#27ae60;font-size:9px;">✓ aprovado</span>' : ' <span style="color:#e67e22;font-size:9px;">⏳ pendente</span>';
        return `
            <div class="den-msg-item ${cls}">
                <div class="den-msg-meta">
                    <span class="den-msg-author ${authorCls}"><a href="/profile.php?uid=${m.remetente_id}" target="_blank" style="color:inherit;text-decoration:none;">${m.remetente_nome}</a> ${isDenunciante ? '(denunciante)' : '(denunciado)'}</span>
                    <span>${formatDate(m.criado_em)}</span>
                </div>
                <div>${content}</div>
            </div>
        `;
    }).join('');
}

function switchDenTab(tab, btn) {
    document.querySelectorAll('#den-tabs .den-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const content = document.getElementById('den-tab-content');
    if (!_denunciaData) return;
    const d = _denunciaData;

    if (tab === 'mensagens') {
        content.innerHTML = renderMsgList(d.mensagens, d.denuncia.denunciante_id, 'mensagens');
    } else if (tab === 'recados') {
        content.innerHTML = renderMsgList(d.recados, d.denuncia.denunciante_id, 'recados');
    } else if (tab === 'depoimentos') {
        content.innerHTML = renderMsgList(d.depoimentos, d.denuncia.denunciante_id, 'depoimentos');
    } else if (tab === 'outras') {
        if (!d.outrasDenuncias || d.outrasDenuncias.length === 0) {
            content.innerHTML = '<div style="text-align:center;color:#999;padding:20px;font-size:11px;">Nenhuma outra denúncia contra este usuário.</div>';
        } else {
            content.innerHTML = '<table class="admin-table" style="font-size:11px;"><thead><tr><th>ID</th><th>Denunciante</th><th>Motivo</th><th>Status</th><th>Data</th></tr></thead><tbody>' +
                d.outrasDenuncias.map(o => `
                    <tr>
                        <td>#${o.id}</td>
                        <td><a href="/profile.php?uid=${o.denunciante_id}" target="_blank" style="color:var(--title);text-decoration:none;">${o.denunciante_nome}</a></td>
                        <td>${truncate(o.motivo, 60)}</td>
                        <td>${statusBadge(o.status)}</td>
                        <td>${formatDate(o.criado_em)}</td>
                    </tr>
                `).join('') +
                '</tbody></table>';
        }
    } else if (tab === 'chat') {
        renderAdminChat(d, content);
    }
}

function renderAdminChat(d, container) {
    const chatMsgs = d.chatMensagens || [];
    let html = '<div style="max-height:200px;overflow-y:auto;padding:5px;background:#f4f6fb;border-radius:4px;margin-bottom:8px;" id="admin-chat-scroll">';
    if (chatMsgs.length === 0) {
        html += '<div style="text-align:center;color:#999;padding:20px;font-size:11px;">Nenhuma mensagem no chat desta denúncia.</div>';
    } else {
        chatMsgs.forEach(m => {
            const isAdmin = m.is_admin ? true : false;
            const align = isAdmin ? 'flex-end' : 'flex-start';
            const bg = isAdmin ? '#fff3cd' : '#d4edff';
            const border = isAdmin ? 'border-bottom-right-radius:2px;border:1px solid #ffe8a1;' : 'border-bottom-left-radius:2px;';
            const senderName = isAdmin ? '🛡️ ' + m.remetente_nome + ' (admin)' : m.remetente_nome;
            const senderColor = isAdmin ? '#c77c00' : '#3b5998';
            html += `<div style="display:flex;justify-content:${align};margin-bottom:6px;">
                <div style="max-width:75%;padding:6px 10px;border-radius:10px;font-size:11px;line-height:1.4;background:${bg};${border}">
                    <div style="font-size:9px;font-weight:bold;color:${senderColor};margin-bottom:2px;">${senderName}</div>
                    ${m.mensagem.replace(/</g,'&lt;').replace(/>/g,'&gt;')}
                    <div style="font-size:8px;color:#999;text-align:right;margin-top:2px;">${formatDate(m.criado_em)}</div>
                </div>
            </div>`;
        });
    }
    html += '</div>';
    html += `<div style="display:flex;gap:6px;">
        <input type="text" id="admin-chat-input" placeholder="Enviar mensagem ao denunciante..." style="flex:1;padding:6px 10px;border:1px solid var(--line);border-radius:15px;font-size:11px;" maxlength="1000" onkeydown="if(event.key==='Enter')enviarMsgAdminChat();">
        <button onclick="enviarMsgAdminChat()" style="background:var(--orkut-blue);color:#fff;border:none;border-radius:15px;padding:6px 14px;font-size:11px;cursor:pointer;font-weight:bold;">Enviar</button>
    </div>`;
    container.innerHTML = html;
    const scroll = document.getElementById('admin-chat-scroll');
    if (scroll) scroll.scrollTop = scroll.scrollHeight;
}

async function enviarMsgAdminChat() {
    const input = document.getElementById('admin-chat-input');
    if (!input) return;
    const msg = input.value.trim();
    if (!msg) return;
    const denId = document.getElementById('denuncia-id-modal').value;
    input.disabled = true;
    try {
        const resp = await fetch('/api/admin/denuncia-chat/' + denId, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ mensagem: msg })
        });
        const data = await resp.json();
        if (data.success) {
            input.value = '';
            // Re-fetch to update chat
            const resp2 = await fetch('/api/admin/denuncia-detalhe/' + denId);
            const data2 = await resp2.json();
            if (data2.success) {
                _denunciaData = data2;
                document.getElementById('den-tab-chat-count').textContent = (data2.chatMensagens || []).length || '';
                renderAdminChat(data2, document.getElementById('den-tab-content'));
            }
        } else {
            showToast(data.message || 'Erro.', 'error');
        }
    } catch(err) {
        showToast('Erro de conexão.', 'error');
    }
    input.disabled = false;
    input.focus();
}

async function abrirDenuncia(id) {
    // Mostrar modal com loading
    document.getElementById('denuncia-title-id').textContent = id;
    document.getElementById('denuncia-id-modal').value = id;
    document.getElementById('den-motivo-text').textContent = 'Carregando...';
    document.getElementById('den-perfil-denunciante').innerHTML = '<div style="color:#999;">Carregando...</div>';
    document.getElementById('den-perfil-denunciado').innerHTML = '<div style="color:#999;">Carregando...</div>';
    document.getElementById('den-tab-content').innerHTML = '<div style="text-align:center;color:#999;padding:30px;font-size:11px;">Carregando...</div>';
    openModal('modal-denuncia');

    try {
        const resp = await fetch('/api/admin/denuncia-detalhe/' + id);
        const data = await resp.json();
        if (!data.success) return showToast(data.message || 'Erro ao carregar.', 'error');

        _denunciaData = data;
        const d = data.denuncia;

        // Preencher info
        document.getElementById('den-motivo-text').textContent = d.motivo;
        document.getElementById('den-status-badge').innerHTML = statusBadge(d.status);
        document.getElementById('den-data-text').textContent = formatDate(d.criado_em);
        document.getElementById('denuncia-status-select').value = d.status;
        document.getElementById('denuncia-resposta').value = d.resposta_admin || '';

        // Resolvido info
        if (d.resolvido_por_nome) {
            document.getElementById('den-resolvido-info').style.display = 'block';
            document.getElementById('den-resolvido-nome').textContent = d.resolvido_por_nome + ' em ' + formatDate(d.resolvido_em);
        } else {
            document.getElementById('den-resolvido-info').style.display = 'none';
        }

        // Perfis
        document.getElementById('den-perfil-denunciante').innerHTML = renderDenProfile('denunciante', d, data.denuncianteStats);
        document.getElementById('den-perfil-denunciado').innerHTML = renderDenProfile('denunciado', d, data.denunciadoStats);

        // Tab counts
        document.getElementById('den-tab-msgs-count').textContent = data.mensagens.length || '';
        document.getElementById('den-tab-recs-count').textContent = data.recados.length || '';
        document.getElementById('den-tab-deps-count').textContent = data.depoimentos.length || '';
        document.getElementById('den-tab-outras-count').textContent = data.outrasDenuncias.length || '';
        document.getElementById('den-tab-chat-count').textContent = (data.chatMensagens || []).length || '';

        // Ativar primeira tab
        document.querySelectorAll('#den-tabs .den-tab').forEach(t => t.classList.remove('active'));
        document.querySelector('#den-tabs .den-tab').classList.add('active');
        switchDenTab('mensagens', document.querySelector('#den-tabs .den-tab'));

    } catch(err) {
        console.error(err);
        showToast('Erro ao carregar denúncia.', 'error');
    }
}

async function salvarStatusDenuncia() {
    const id = document.getElementById('denuncia-id-modal').value;
    const status = document.getElementById('denuncia-status-select').value;
    const resposta_admin = document.getElementById('denuncia-resposta').value.trim();
    try {
        const resp = await fetch('/api/admin/denuncia/status', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: parseInt(id), status, resposta_admin })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Denúncia atualizada!');
            closeModal('modal-denuncia');
            loadDenuncias(1);
            loadDenBadge();
        } else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

function excluirDenunciaFromModal() {
    const id = parseInt(document.getElementById('denuncia-id-modal').value);
    showConfirm('Excluir denúncia #' + id + '?', function() {
        excluirDenuncia(id, true);
        closeModal('modal-denuncia');
    });
}

async function excluirDenuncia(id, skipConfirm) {
    if (!skipConfirm) {
        showConfirm('Excluir denúncia #' + id + '?', function() {
            excluirDenuncia(id, true);
        });
        return;
    }
    try {
        const resp = await fetch('/api/admin/denuncia/excluir', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await resp.json();
        if (data.success) { showToast('Denúncia excluída!'); loadDenuncias(1); loadDenBadge(); }
        else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

// ===== MODERAÇÃO: DENÚNCIAS DE COMUNIDADES =====
let denCommFilter = 'todos';
let _denCommData = null;
let _denCommCurrentTab = 'outras';

function setDenCommFilter(filtro, btn) {
    denCommFilter = filtro;
    document.querySelectorAll('.dencomm-filter').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    loadDenunciasComunidades(1);
}

async function loadDenunciasComunidades(page) {
    try {
        const resp = await fetch('/api/admin/denuncias-comunidades?page=' + page + '&filtro=' + encodeURIComponent(denCommFilter));
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('dencomm-tbody');
        if (data.denuncias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#999;padding:20px;">Nenhuma denúncia encontrada.</td></tr>';
        } else {
            tbody.innerHTML = data.denuncias.map(d => `
                <tr>
                    <td>#${d.id}</td>
                    <td><a href="/profile.php?uid=${d.denunciante_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.denunciante_nome, 18)}</a></td>
                    <td><a href="/comunidades.php?id=${d.comunidade_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(d.comunidade_nome, 22)}</a></td>
                    <td>${truncate(d.dono_nome || '?', 18)}</td>
                    <td><div class="content-preview">${truncate(d.motivo, 40)}</div></td>
                    <td>${statusBadge(d.status)}</td>
                    <td>${formatDate(d.criado_em)}</td>
                    <td style="display:flex;gap:4px;">
                        <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="abrirDenunciaComm(${d.id})">📋 Analisar</button>
                        <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirDenunciaComm(${d.id})">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        renderPagination('dencomm-pagination', data.page, data.totalPages, 'loadDenunciasComunidades');
    } catch(err) { console.error(err); }
}

async function abrirDenunciaComm(id) {
    try {
        const resp = await fetch('/api/admin/denuncia-comunidade-detalhe/' + id);
        const data = await resp.json();
        if (!data.success) { showToast(data.message || 'Erro', 'error'); return; }

        _denCommData = data;
        const d = data.denuncia;

        document.getElementById('dencomm-title-id').textContent = d.id;
        document.getElementById('dencomm-id-modal').value = d.id;
        document.getElementById('dencomm-motivo-text').textContent = d.motivo;
        document.getElementById('dencomm-status-badge').innerHTML = statusBadge(d.status);
        document.getElementById('dencomm-data-text').textContent = formatDate(d.criado_em);
        document.getElementById('dencomm-status-select').value = d.status;
        document.getElementById('dencomm-resposta').value = d.resposta_admin || '';

        if (d.resolvido_por_nome) {
            document.getElementById('dencomm-resolvido-info').style.display = 'block';
            document.getElementById('dencomm-resolvido-nome').textContent = d.resolvido_por_nome;
        } else {
            document.getElementById('dencomm-resolvido-info').style.display = 'none';
        }

        // Denunciante
        const denFoto = d.denunciante_foto || '/img/default-avatar.png';
        let denHtml = '<div style="display:flex;gap:8px;align-items:flex-start;">';
        denHtml += '<img src="' + denFoto + '" style="width:45px;height:45px;border-radius:50%;border:2px solid #27ae60;" onerror="this.src=\'/img/default-avatar.png\'">';
        denHtml += '<div>';
        denHtml += '<div style="font-weight:bold;"><a href="/profile.php?uid=' + d.denunciante_id + '" target="_blank" style="color:var(--title);">' + (d.denunciante_nome||'?') + '</a></div>';
        denHtml += '<div style="color:#666;font-size:10px;">' + (d.denunciante_email||'') + '</div>';
        if (d.denunciante_cidade || d.denunciante_estado) denHtml += '<div style="color:#666;font-size:10px;">📍 ' + (d.denunciante_cidade||'') + (d.denunciante_estado ? ', ' + d.denunciante_estado : '') + '</div>';
        denHtml += '</div></div>';
        document.getElementById('dencomm-perfil-denunciante').innerHTML = denHtml;

        // Comunidade
        const comFoto = d.comunidade_foto || '/img/default-community.png';
        let comHtml = '<div style="display:flex;gap:8px;align-items:flex-start;">';
        comHtml += '<img src="' + comFoto + '" style="width:45px;height:45px;border-radius:4px;border:2px solid #e74c3c;" onerror="this.src=\'/img/default-community.png\'">';
        comHtml += '<div>';
        comHtml += '<div style="font-weight:bold;"><a href="/comunidades.php?id=' + d.comunidade_id + '" target="_blank" style="color:var(--title);">' + (d.comunidade_nome||'?') + '</a></div>';
        comHtml += '<div style="color:#666;font-size:10px;">Categoria: ' + (d.comunidade_categoria||'Geral') + ' | Tipo: ' + (d.comunidade_tipo||'publica') + '</div>';
        comHtml += '<div style="color:#666;font-size:10px;">Dono: <a href="/profile.php?uid=' + d.comunidade_dono_id + '" target="_blank" style="color:var(--title);">' + (d.dono_nome||'?') + '</a></div>';
        comHtml += '<div style="color:#666;font-size:10px;">👥 ' + (data.comunidadeStats.totalMembros||0) + ' membros | ⚠️ ' + (data.comunidadeStats.totalDenuncias||0) + ' denúncias</div>';
        if (d.comunidade_descricao) {
            const descShort = d.comunidade_descricao.length > 100 ? d.comunidade_descricao.substring(0,100) + '...' : d.comunidade_descricao;
            comHtml += '<div style="color:#555;font-size:10px;margin-top:4px;font-style:italic;">' + descShort + '</div>';
        }
        comHtml += '</div></div>';
        document.getElementById('dencomm-perfil-comunidade').innerHTML = comHtml;

        // Tab counts
        document.getElementById('dencomm-tab-outras-count').textContent = data.outrasDenuncias.length || '';
        document.getElementById('dencomm-tab-chat-count').textContent = data.chatMensagens.length || '';

        // Show default tab
        _denCommCurrentTab = 'outras';
        document.querySelectorAll('#dencomm-tabs .den-tab').forEach(t => t.classList.remove('active'));
        document.querySelector('#dencomm-tabs .den-tab').classList.add('active');
        renderDenCommTabContent();

        openModal('modal-denuncia-comm');
    } catch(err) { console.error(err); showToast('Erro ao carregar denúncia.', 'error'); }
}

function switchDenCommTab(tab, btn) {
    _denCommCurrentTab = tab;
    document.querySelectorAll('#dencomm-tabs .den-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderDenCommTabContent();
}

function renderDenCommTabContent() {
    const container = document.getElementById('dencomm-tab-content');
    if (!_denCommData) return;
    const d = _denCommData.denuncia;

    if (_denCommCurrentTab === 'outras') {
        if (!_denCommData.outrasDenuncias || _denCommData.outrasDenuncias.length === 0) {
            container.innerHTML = '<div style="text-align:center;color:#999;padding:30px;font-size:11px;">Nenhuma outra denúncia contra esta comunidade.</div>';
        } else {
            container.innerHTML = '<div style="display:flex;flex-direction:column;gap:6px;">' +
                _denCommData.outrasDenuncias.map(o => `
                    <div style="display:flex;gap:8px;align-items:center;padding:6px;border:1px solid #eee;border-radius:4px;font-size:11px;">
                        <div style="flex:1;"><b>${o.denunciante_nome}</b></div>
                        <div style="flex:2;color:#666;">${truncate(o.motivo, 60)}</div>
                        <div>${statusBadge(o.status)}</div>
                        <div style="color:#999;font-size:10px;">${formatDate(o.criado_em)}</div>
                    </div>
                `).join('') + '</div>';
        }
    } else if (_denCommCurrentTab === 'chat') {
        const msgs = _denCommData.chatMensagens || [];
        if (msgs.length === 0) {
            container.innerHTML = '<div style="text-align:center;color:#999;padding:20px;font-size:11px;">Nenhuma mensagem no chat.</div>';
        } else {
            container.innerHTML = msgs.map(m => {
                const isAdmin = m.is_admin;
                const bg = isAdmin ? '#e8f5e9' : '#e3f2fd';
                const label = isAdmin ? '🛡️ Equipe' : '👤 ' + (m.remetente_nome || 'Usuário');
                return `<div style="padding:6px 8px;margin-bottom:4px;background:${bg};border-radius:4px;font-size:11px;">
                    <div style="font-weight:bold;font-size:10px;color:#666;">${label} <span style="float:right;font-weight:normal;">${formatDate(m.criado_em)}</span></div>
                    <div style="margin-top:2px;">${m.mensagem}</div>
                </div>`;
            }).join('');
        }
        // Admin chat input
        container.innerHTML += `
            <div style="display:flex;gap:6px;margin-top:10px;padding-top:8px;border-top:1px solid #eee;">
                <input type="text" id="dencomm-chat-input" placeholder="Responder ao denunciante..." style="flex:1;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:12px;" onkeydown="if(event.key==='Enter')enviarMsgAdminChatComm();">
                <button class="btn-admin btn-admin-primary btn-admin-sm" onclick="enviarMsgAdminChatComm()">Enviar</button>
            </div>
        `;
    }
}

async function enviarMsgAdminChatComm() {
    const input = document.getElementById('dencomm-chat-input');
    if (!input || !input.value.trim()) return;
    const denId = document.getElementById('dencomm-id-modal').value;
    try {
        const resp = await fetch('/api/admin/denuncia-comunidade-chat/' + denId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensagem: input.value.trim() })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Mensagem enviada!');
            // Reload the denuncia detail
            abrirDenunciaComm(parseInt(denId));
        } else { showToast(data.message || 'Erro', 'error'); }
    } catch(err) { showToast('Erro de conexão.', 'error'); }
}

async function salvarStatusDenunciaComm() {
    const id = document.getElementById('dencomm-id-modal').value;
    const status = document.getElementById('dencomm-status-select').value;
    const resposta = document.getElementById('dencomm-resposta').value;

    try {
        const resp = await fetch('/api/admin/denuncia-comunidade/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status, resposta_admin: resposta })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Status atualizado!');
            closeModal('modal-denuncia-comm');
            loadDenunciasComunidades(1);
            loadBadges();
        } else { showToast(data.message || 'Erro', 'error'); }
    } catch(err) { showToast('Erro.', 'error'); }
}

async function excluirDenunciaComm(id) {
    if (!confirm('Excluir esta denúncia de comunidade?')) return;
    try {
        const resp = await fetch('/api/admin/denuncia-comunidade/excluir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await resp.json();
        if (data.success) { showToast('Denúncia excluída!'); loadDenunciasComunidades(1); loadBadges(); }
        else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

function excluirDenunciaCommFromModal() {
    const id = document.getElementById('dencomm-id-modal').value;
    if (!id) return;
    excluirDenunciaComm(parseInt(id)).then(() => closeModal('modal-denuncia-comm'));
}

// ===== BADGE: Denúncias/Sugestões/Bugs pendentes no sidebar =====
async function loadBadges() {
    try {
        const resp = await fetch('/api/admin/stats');
        const data = await resp.json();
        if (!data.success) return;
        const s = data.stats;

        const denBadge = document.getElementById('nav-den-badge');
        if (denBadge) denBadge.innerHTML = (s.denunciasPendentes || 0) > 0 ? '<span class="nav-badge">' + s.denunciasPendentes + '</span>' : '';

        const denCommBadge = document.getElementById('nav-dencomm-badge');
        if (denCommBadge) denCommBadge.innerHTML = (s.denunciasComunidadesPendentes || 0) > 0 ? '<span class="nav-badge">' + s.denunciasComunidadesPendentes + '</span>' : '';

        const sugBadge = document.getElementById('nav-sug-badge');
        if (sugBadge) sugBadge.innerHTML = (s.sugestoesNovas || 0) > 0 ? '<span class="nav-badge" style="background:#f1c40f;color:#333;">' + s.sugestoesNovas + '</span>' : '';

        const bugBadge = document.getElementById('nav-bug-badge');
        if (bugBadge) bugBadge.innerHTML = (s.bugsNovos || 0) > 0 ? '<span class="nav-badge" style="background:#9b59b6;">' + s.bugsNovos + '</span>' : '';
    } catch(e) {}
}
function loadDenBadge() { loadBadges(); }

// Polling do badge a cada 30s
setInterval(loadBadges, 30000);

// ===== SUGESTÕES =====
function sugStatusBadge(status) {
    const map = { nova: '🟡 Nova', analisando: '🔵 Analisando', aprovada: '✅ Aprovada', implementada: '🟢 Implementada', rejeitada: '🔴 Rejeitada' };
    const colorMap = { nova: '#f39c12', analisando: '#3498db', aprovada: '#27ae60', implementada: '#2ecc71', rejeitada: '#e74c3c' };
    return '<span style="color:' + (colorMap[status]||'#999') + ';font-weight:bold;font-size:11px;">' + (map[status]||status) + '</span>';
}

function bugStatusBadge(status) {
    const map = { novo: '🟡 Novo', analisando: '🔵 Analisando', corrigido: '🟢 Corrigido', nao_reproduzivel: '🟠 Não Reproduzível', rejeitado: '🔴 Rejeitado' };
    const colorMap = { novo: '#f39c12', analisando: '#3498db', corrigido: '#27ae60', nao_reproduzivel: '#e67e22', rejeitado: '#e74c3c' };
    return '<span style="color:' + (colorMap[status]||'#999') + ';font-weight:bold;font-size:11px;">' + (map[status]||status) + '</span>';
}

function setSugestaoFilter(filtro, btn) {
    sugestaoFilter = filtro;
    document.querySelectorAll('.sug-filter').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    loadSugestoes(1);
}

function setBugFilter(filtro, btn) {
    bugFilter = filtro;
    document.querySelectorAll('.bug-filter').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    loadBugs(1);
}

async function loadSugestoes(page) {
    try {
        const resp = await fetch('/api/admin/sugestoes?page=' + page + '&filtro=' + encodeURIComponent(sugestaoFilter));
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('sugestoes-tbody');
        if (data.sugestoes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhuma sugestão encontrada.</td></tr>';
        } else {
            tbody.innerHTML = data.sugestoes.map(s => {
                const imgs = s.imagens ? JSON.parse(s.imagens) : [];
                return `
                <tr>
                    <td>#${s.id}</td>
                    <td><a href="/profile.php?uid=${s.usuario_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(s.autor_nome || 'Desconhecido', 20)}</a></td>
                    <td><div class="content-preview">${truncate(s.titulo, 50)}</div></td>
                    <td>${imgs.length > 0 ? '📎 ' + imgs.length : '-'}</td>
                    <td>${sugStatusBadge(s.status)}</td>
                    <td>${formatDate(s.criado_em)}</td>
                    <td style="display:flex;gap:4px;">
                        <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="abrirSugestao(${s.id})">📋 Ver</button>
                        <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirSugestao(${s.id})">🗑️</button>
                    </td>
                </tr>`;
            }).join('');
        }

        renderPagination('sugestoes-pagination', data.page, data.totalPages, 'loadSugestoes');
    } catch(err) { console.error(err); }
}

async function loadBugs(page) {
    try {
        const resp = await fetch('/api/admin/bugs?page=' + page + '&filtro=' + encodeURIComponent(bugFilter));
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('bugs-tbody');
        if (data.bugs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum bug encontrado.</td></tr>';
        } else {
            tbody.innerHTML = data.bugs.map(b => {
                const imgs = b.imagens ? JSON.parse(b.imagens) : [];
                return `
                <tr>
                    <td>#${b.id}</td>
                    <td><a href="/profile.php?uid=${b.usuario_id}" target="_blank" style="color:var(--title);text-decoration:none;">${truncate(b.autor_nome || 'Desconhecido', 20)}</a></td>
                    <td><div class="content-preview">${truncate(b.titulo, 50)}</div></td>
                    <td>${imgs.length > 0 ? '📎 ' + imgs.length : '-'}</td>
                    <td>${bugStatusBadge(b.status)}</td>
                    <td>${formatDate(b.criado_em)}</td>
                    <td style="display:flex;gap:4px;">
                        <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="abrirBug(${b.id})">📋 Ver</button>
                        <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirBug(${b.id})">🗑️</button>
                    </td>
                </tr>`;
            }).join('');
        }

        renderPagination('bugs-pagination', data.page, data.totalPages, 'loadBugs');
    } catch(err) { console.error(err); }
}

let _currentSugestaoId = null;
let _currentBugId = null;

async function abrirSugestao(id) {
    _currentSugestaoId = id;
    try {
        const resp = await fetch('/api/admin/sugestoes?page=1&filtro=todos');
        const data = await resp.json();
        if (!data.success) return;
        // Find the specific suggestion
        let s = data.sugestoes.find(x => x.id === id);
        if (!s) {
            // Try loading all pages or do single lookup - for now just show error
            showToast('Sugestão não encontrada na página atual.', 'error');
            return;
        }

        document.getElementById('sug-detail-id').textContent = id;
        const imgs = s.imagens ? JSON.parse(s.imagens) : [];
        let imgsHtml = '';
        if (imgs.length > 0) {
            imgsHtml = '<div style="margin-top:12px;"><strong style="font-size:11px;color:#666;">📎 Anexos:</strong><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">' +
                imgs.map(img => '<a href="' + img + '" target="_blank"><img src="' + img + '" style="max-width:180px;max-height:140px;border:1px solid #ddd;border-radius:4px;cursor:pointer;" onerror="this.style.display=\'none\'"></a>').join('') +
                '</div></div>';
        }

        document.getElementById('sug-detail-body').innerHTML = `
            <div style="padding:15px;">
                <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px;">
                    <div style="flex:1;">
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Usuário</div>
                        <a href="/profile.php?uid=${s.usuario_id}" target="_blank" style="color:var(--title);font-weight:bold;text-decoration:none;">${s.autor_nome || 'Desconhecido'}</a>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Status</div>
                        ${sugStatusBadge(s.status)}
                    </div>
                    <div>
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Data</div>
                        <span style="font-size:11px;">${formatDate(s.criado_em)}</span>
                    </div>
                </div>
                <div style="background:#f4f7fc;border:1px solid #c0d0e6;padding:12px;border-radius:6px;">
                    <div style="font-weight:bold;color:var(--orkut-blue);margin-bottom:6px;font-size:14px;">${s.titulo}</div>
                    <div style="font-size:13px;color:#333;white-space:pre-wrap;line-height:1.6;">${s.descricao}</div>
                </div>
                ${imgsHtml}
                ${s.resposta_admin ? '<div style="margin-top:12px;background:#e8f5e9;border:1px solid #a5d6a7;padding:10px;border-radius:4px;font-size:12px;"><strong>💬 Resposta admin:</strong> ' + s.resposta_admin + '</div>' : ''}
                ${s.resolvido_por ? '<div style="margin-top:6px;font-size:10px;color:#888;">Atualizado por admin #' + s.resolvido_por + ' em ' + formatDate(s.resolvido_em) + '</div>' : ''}
            </div>
        `;

        document.getElementById('sug-status-select').value = s.status;
        document.getElementById('sug-resposta-input').value = s.resposta_admin || '';
        openModal('modal-sugestao');
    } catch(err) {
        console.error(err);
        showToast('Erro ao carregar sugestão.', 'error');
    }
}

async function abrirBug(id) {
    _currentBugId = id;
    try {
        const resp = await fetch('/api/admin/bugs?page=1&filtro=todos');
        const data = await resp.json();
        if (!data.success) return;
        let b = data.bugs.find(x => x.id === id);
        if (!b) {
            showToast('Bug não encontrado na página atual.', 'error');
            return;
        }

        document.getElementById('bug-detail-id').textContent = id;
        const imgs = b.imagens ? JSON.parse(b.imagens) : [];
        let imgsHtml = '';
        if (imgs.length > 0) {
            imgsHtml = '<div style="margin-top:12px;"><strong style="font-size:11px;color:#666;">📎 Anexos:</strong><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">' +
                imgs.map(img => '<a href="' + img + '" target="_blank"><img src="' + img + '" style="max-width:180px;max-height:140px;border:1px solid #ddd;border-radius:4px;cursor:pointer;" onerror="this.style.display=\'none\'"></a>').join('') +
                '</div></div>';
        }

        document.getElementById('bug-detail-body').innerHTML = `
            <div style="padding:15px;">
                <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:12px;">
                    <div style="flex:1;">
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Usuário</div>
                        <a href="/profile.php?uid=${b.usuario_id}" target="_blank" style="color:var(--title);font-weight:bold;text-decoration:none;">${b.autor_nome || 'Desconhecido'}</a>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Status</div>
                        ${bugStatusBadge(b.status)}
                    </div>
                    <div>
                        <div style="font-size:10px;color:#999;text-transform:uppercase;">Data</div>
                        <span style="font-size:11px;">${formatDate(b.criado_em)}</span>
                    </div>
                </div>
                <div style="background:#fdf2f8;border:1px solid #f5c6cb;padding:12px;border-radius:6px;">
                    <div style="font-weight:bold;color:#c0392b;margin-bottom:6px;font-size:14px;">${b.titulo}</div>
                    <div style="font-size:13px;color:#333;white-space:pre-wrap;line-height:1.6;">${b.descricao}</div>
                </div>
                ${imgsHtml}
                ${b.resposta_admin ? '<div style="margin-top:12px;background:#e8f5e9;border:1px solid #a5d6a7;padding:10px;border-radius:4px;font-size:12px;"><strong>💬 Resposta admin:</strong> ' + b.resposta_admin + '</div>' : ''}
                ${b.resolvido_por ? '<div style="margin-top:6px;font-size:10px;color:#888;">Atualizado por admin #' + b.resolvido_por + ' em ' + formatDate(b.resolvido_em) + '</div>' : ''}
            </div>
        `;

        document.getElementById('bug-status-select').value = b.status;
        document.getElementById('bug-resposta-input').value = b.resposta_admin || '';
        openModal('modal-bug');
    } catch(err) {
        console.error(err);
        showToast('Erro ao carregar bug.', 'error');
    }
}

async function salvarStatusSugestao() {
    if (!_currentSugestaoId) return;
    const status = document.getElementById('sug-status-select').value;
    const resposta = document.getElementById('sug-resposta-input').value.trim();
    try {
        const resp = await fetch('/api/admin/sugestao/status', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: _currentSugestaoId, status, resposta })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Sugestão atualizada!');
            closeModal('modal-sugestao');
            loadSugestoes(1);
            loadBadges();
        } else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

async function salvarStatusBug() {
    if (!_currentBugId) return;
    const status = document.getElementById('bug-status-select').value;
    const resposta = document.getElementById('bug-resposta-input').value.trim();
    try {
        const resp = await fetch('/api/admin/bug/status', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: _currentBugId, status, resposta })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Bug atualizado!');
            closeModal('modal-bug');
            loadBugs(1);
            loadBadges();
        } else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

function excluirSugestaoModal() {
    if (!_currentSugestaoId) return;
    showConfirm('Excluir sugestão #' + _currentSugestaoId + '?', function() {
        excluirSugestao(_currentSugestaoId, true);
        closeModal('modal-sugestao');
    });
}

function excluirBugModal() {
    if (!_currentBugId) return;
    showConfirm('Excluir bug #' + _currentBugId + '?', function() {
        excluirBug(_currentBugId, true);
        closeModal('modal-bug');
    });
}

async function excluirSugestao(id, skipConfirm) {
    if (!skipConfirm) {
        showConfirm('Excluir sugestão #' + id + '?', function() {
            excluirSugestao(id, true);
        });
        return;
    }
    try {
        const resp = await fetch('/api/admin/sugestao/excluir', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await resp.json();
        if (data.success) { showToast('Sugestão excluída!'); loadSugestoes(1); loadBadges(); }
        else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

async function excluirBug(id, skipConfirm) {
    if (!skipConfirm) {
        showConfirm('Excluir bug #' + id + '?', function() {
            excluirBug(id, true);
        });
        return;
    }
    try {
        const resp = await fetch('/api/admin/bug/excluir', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await resp.json();
        if (data.success) { showToast('Bug excluído!'); loadBugs(1); loadBadges(); }
        else showToast(data.message, 'error');
    } catch(err) { showToast('Erro.', 'error'); }
}

// ===== PAGINATION =====
function renderPagination(containerId, page, totalPages, fnName) {
    const el = document.getElementById(containerId);
    if (totalPages <= 1) { el.innerHTML = ''; return; }
    el.innerHTML = `
        <button onclick="${fnName}(${page - 1})" ${page <= 1 ? 'disabled' : ''}>← Anterior</button>
        <span class="page-info">Página ${page} de ${totalPages}</span>
        <button onclick="${fnName}(${page + 1})" ${page >= totalPages ? 'disabled' : ''}>Próxima →</button>
    `;
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadBadges();
});

// ===== ANÚNCIOS =====
async function loadAnuncios(page) {
    try {
        const resp = await fetch('/api/admin/anuncios?page=' + page);
        const data = await resp.json();
        if (!data.success) return;

        const tbody = document.getElementById('anuncios-tbody');
        if (data.anuncios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum anúncio publicado.</td></tr>';
        } else {
            tbody.innerHTML = data.anuncios.map(a => `
                <tr>
                    <td>#${a.id}</td>
                    <td>${a.foto ? '<img src="' + a.foto + '" style="width:60px;height:45px;object-fit:cover;border-radius:3px;border:1px solid #ddd;">' : '<span style="color:#ccc;">—</span>'}</td>
                    <td><strong>${truncate(a.titulo, 40)}</strong></td>
                    <td><div class="content-preview">${truncate(a.mensagem, 60)}</div></td>
                    <td>${a.admin_nome || 'Admin'}</td>
                    <td>${formatDate(a.criado_em)}</td>
                    <td>
                        <button class="btn-admin btn-admin-sm" style="background:var(--orkut-blue);color:#fff;margin-right:4px;" onclick="editarAnuncio(${a.id})">✏️</button>
                        <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="excluirAnuncio(${a.id})">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }

        renderPagination('anuncios-pagination', data.page, data.totalPages, 'loadAnuncios');
    } catch(err) { console.error(err); }
}

// Quill editor instance
let quillAnuncio = null;
function initQuillAnuncio() {
    if (quillAnuncio) return;
    quillAnuncio = new Quill('#anuncio-editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do anúncio...',
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

async function criarAnuncio() {
    const titulo = document.getElementById('anuncio-titulo').value.trim();
    if (!quillAnuncio) { showToast('Editor não carregou.', 'error'); return; }
    const mensagemHtml = quillAnuncio.root.innerHTML.trim();
    const mensagemTexto = quillAnuncio.getText().trim();
    if (!titulo || !mensagemTexto) {
        showToast('Preencha título e mensagem.', 'error');
        return;
    }

    // Obter foto base64 se selecionada
    let foto_base64 = null;
    const fotoInput = document.getElementById('anuncio-foto');
    if (fotoInput.files && fotoInput.files[0]) {
        foto_base64 = await fileToBase64(fotoInput.files[0]);
    }

    try {
        const resp = await fetch('/api/admin/anuncio/criar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ titulo, mensagem: mensagemHtml, foto_base64 })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Anúncio publicado! ' + data.notificados + ' usuários notificados.', 'success');
            document.getElementById('anuncio-titulo').value = '';
            quillAnuncio.setContents([]);
            document.getElementById('anuncio-foto').value = '';
            document.getElementById('anuncio-foto-preview').style.display = 'none';
            loadAnuncios(1);
        } else {
            showToast(data.message || 'Erro ao publicar.', 'error');
        }
    } catch(err) {
        console.error(err);
        showToast('Erro de conexão.', 'error');
    }
}

async function excluirAnuncio(id) {
    if (!confirm('Excluir este anúncio?')) return;
    try {
        const resp = await fetch('/api/admin/anuncio/excluir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Anúncio excluído.', 'success');
            loadAnuncios(1);
        } else {
            showToast(data.message || 'Erro.', 'error');
        }
    } catch(err) { console.error(err); }
}

// ===== EDITAR ANÚNCIO =====
let quillAnuncioEdit = null;
let editAnuncioRemoverFoto = false;

function initQuillAnuncioEdit() {
    if (quillAnuncioEdit) return;
    quillAnuncioEdit = new Quill('#edit-anuncio-editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do anúncio...',
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

async function editarAnuncio(id) {
    try {
        const resp = await fetch('/api/anuncio/' + id);
        const data = await resp.json();
        if (!data.success) { showToast('Anúncio não encontrado.', 'error'); return; }
        const a = data.anuncio;

        document.getElementById('edit-anuncio-id').textContent = a.id;
        document.getElementById('edit-anuncio-hidden-id').value = a.id;
        document.getElementById('edit-anuncio-titulo').value = a.titulo;

        // Foto atual
        editAnuncioRemoverFoto = false;
        const fotoAtual = document.getElementById('edit-anuncio-foto-atual');
        if (a.foto) {
            fotoAtual.innerHTML = '<div style="margin-bottom:6px;"><img src="' + a.foto + '" style="max-width:300px;max-height:169px;border-radius:4px;border:1px solid #ccc;"></div>' +
                '<button class="btn-admin btn-admin-sm btn-admin-danger" onclick="removerFotoAnuncioEdit()" style="font-size:10px;">🗑️ Remover foto atual</button>';
        } else {
            fotoAtual.innerHTML = '<span style="color:#999;font-size:11px;">Sem foto</span>';
        }
        document.getElementById('edit-anuncio-foto').value = '';
        document.getElementById('edit-anuncio-foto-preview').style.display = 'none';

        openModal('modal-editar-anuncio');

        // Init Quill editor for edit
        setTimeout(() => {
            initQuillAnuncioEdit();
            quillAnuncioEdit.root.innerHTML = a.mensagem || '';
        }, 150);
    } catch(err) {
        console.error(err);
        showToast('Erro ao carregar anúncio.', 'error');
    }
}

function removerFotoAnuncioEdit() {
    editAnuncioRemoverFoto = true;
    document.getElementById('edit-anuncio-foto-atual').innerHTML = '<span style="color:#c0392b;font-size:11px;">⚠️ Foto será removida ao salvar</span>';
}

function previewEditAnuncioFoto(input) {
    const preview = document.getElementById('edit-anuncio-foto-preview');
    const img = document.getElementById('edit-anuncio-foto-img');
    if (input.files && input.files[0]) {
        editAnuncioRemoverFoto = false;
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

async function salvarAnuncioEdit() {
    const id = document.getElementById('edit-anuncio-hidden-id').value;
    const titulo = document.getElementById('edit-anuncio-titulo').value.trim();
    if (!quillAnuncioEdit) { showToast('Editor não carregou.', 'error'); return; }
    const mensagem = quillAnuncioEdit.root.innerHTML.trim();
    const textoPlano = quillAnuncioEdit.getText().trim();
    if (!titulo || !textoPlano) {
        showToast('Preencha título e mensagem.', 'error');
        return;
    }

    let foto_base64 = null;
    const fotoInput = document.getElementById('edit-anuncio-foto');
    if (fotoInput.files && fotoInput.files[0]) {
        foto_base64 = await fileToBase64(fotoInput.files[0]);
    }

    try {
        const resp = await fetch('/api/admin/anuncio/editar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: Number(id), titulo, mensagem, foto_base64, remover_foto: editAnuncioRemoverFoto })
        });
        const data = await resp.json();
        if (data.success) {
            showToast('Anúncio atualizado com sucesso!', 'success');
            closeModal('modal-editar-anuncio');
            loadAnuncios(1);
        } else {
            showToast(data.message || 'Erro ao salvar.', 'error');
        }
    } catch(err) {
        console.error(err);
        showToast('Erro de conexão.', 'error');
    }
}

function previewAnuncioFoto(input) {
    const preview = document.getElementById('anuncio-foto-preview');
    const img = document.getElementById('anuncio-foto-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
</script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
</body>
</html>
