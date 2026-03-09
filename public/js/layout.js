// ===== layout.js - Componentes compartilhados do Yorkut =====
// Gera header, menu esquerdo e footer em todas as páginas

// Helper global: avatar padrão baseado no sexo
function getDefaultAvatar(sexo) {
    return sexo === 'F' ? '/img/default-avatar-female.png' : '/img/default-avatar.png';
}

let _userData = null;
let _convitesData = [];
let _temaData = null;
let _visitingUser = null;  // Dados do usuario visitado (null = proprio perfil)
let _visitingUid = null;   // UID do usuario visitado
let _mensagensNaoLidas = 0;
let _recadosNaoLidos = 0;
let _depoimentosNaoLidos = 0;
let _solicitacoesPendentes = 0;
let _totalAmigos = 0;
let _denunciasPendentes = 0;
let _notificacoesNaoLidas = 0;
let _activePageGlobal = ''; // página ativa (para suprimir badge na própria página)

async function loadLayout(options = {}) {
    const { showRightCol = false, activePage = '' } = options;
    _activePageGlobal = activePage;

    // Detectar uid na URL
    const urlParams = new URLSearchParams(window.location.search);
    const uidParam = urlParams.get('uid');

    // Carregar dados do usuário logado
    try {
        const resp = await fetch('/api/me');
        const data = await resp.json();
        if (data.success) {
            _userData = data.user;
            _convitesData = data.convites || [];
            _temaData = data.tema || null;
            _mensagensNaoLidas = data.mensagensNaoLidas || 0;
            _recadosNaoLidos = data.recadosNaoLidos || 0;
            _depoimentosNaoLidos = data.depoimentosNaoLidos || 0;
            _solicitacoesPendentes = data.solicitacoesPendentes || 0;
            _totalAmigos = data.totalAmigos || 0;
            _denunciasPendentes = data.denunciasPendentes || 0;
            _notificacoesNaoLidas = data.notificacoesNaoLidas || 0;
        }
    } catch(e) { console.error('Erro ao carregar dados:', e); }

    // Se estamos na própria página de recados, zerar o badge
    // (a visita à página já marca como visto no backend)
    const isOwnPage = !uidParam || (_userData && uidParam === String(_userData.id));
    if (isOwnPage && activePage === 'recados') {
        _recadosNaoLidos = 0;
    }
    if (isOwnPage && activePage === 'depoimentos') {
        _depoimentosNaoLidos = 0;
    }

    // Se tem uid e é diferente do logado, buscar dados do visitado
    if (uidParam && _userData && uidParam !== String(_userData.id)) {
        _visitingUid = uidParam;
        try {
            const visitResp = await fetch('/api/user/' + _visitingUid);
            const visitData = await visitResp.json();
            if (visitData.success) {
                _visitingUser = visitData.user;
            }
        } catch(e) { console.error('Erro ao carregar perfil visitado:', e); }
    }

    // Aplicar tema ao body (sempre do logado)
    applyTheme();

    // Gerar header (sempre do logado)
    const headerEl = document.getElementById('app-header');
    if (headerEl) headerEl.innerHTML = generateHeader();

    // Gerar menu esquerdo (visitor-aware)
    const leftCol = document.getElementById('app-left-col');
    if (leftCol) {
        if (_visitingUser) {
            leftCol.innerHTML = generateVisitorLeftMenu(activePage);
        } else {
            leftCol.innerHTML = generateLeftMenu(activePage);
        }
    }

    // Gerar right col (se existir o elemento e ainda estiver vazio)
    const rightCol = document.getElementById('app-right-col');
    if (rightCol && !rightCol.innerHTML.trim()) {
        rightCol.innerHTML = generateRightCol();
    }

    // Gerar footer
    const footerEl = document.getElementById('app-footer');
    if (footerEl) footerEl.innerHTML = generateFooter();

    // Atualizar título
    if (_visitingUser) {
        document.title = 'Yorkut - ' + _visitingUser.nome;
    } else if (_userData && !document.title.includes(_userData.nome)) {
        document.title = document.title.replace('Yorkut', 'Yorkut - ' + _userData.nome);
    }

    // Iniciar polling de badges (atualiza a cada 15s sem refresh)
    startBadgePolling(15000);

    // Load pending friend requests for header dropdown
    loadHeaderPendingRequests();

    // Notifications will be loaded on-demand when dropdown is opened

    // If visiting, check friendship status and load their friends
    if (_visitingUser && _visitingUid) {
        layoutCheckFriendship(_visitingUid);
        layoutLoadAmigos(_visitingUid);
    } else if (_userData) {
        // Own pages - load own friends into right col if present
        layoutLoadAmigos(_userData.id);
        // Load visitors (only own profile pages)
        layoutLoadVisitantes(_userData.id);
    }
}

function isVisiting() { return !!_visitingUser; }
function getVisitingUser() { return _visitingUser; }
function getVisitingUid() { return _visitingUid; }

function generateHeader() {
    const email = _userData ? _userData.email : '';
    const convitesGerados = _convitesData.length;
    const maxConvites = 10;
    const restantes = maxConvites - convitesGerados;
    const btnConviteText = restantes > 0 ? `Convide até ${restantes} amigos` : 'Sem convites';
    const btnConviteClass = restantes > 0 ? 'btn-invite-active' : 'btn-invite-empty';

    return `
    <style>
        :root { --orkut-pink: #e6399b; }
        .hdr-drop-container { position: relative; display: inline-block; }
        .hdr-dropdown { display: none; position: absolute; top: 25px; right: -50px; background: #fff; border: 1px solid var(--orkut-blue); box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999; border-radius: 4px; text-align: left; width: 320px; color: #333; }
        .hdr-dropdown::before { content: ''; position: absolute; top: -10px; right: 60px; border-width: 0 10px 10px 10px; border-style: solid; border-color: transparent transparent var(--orkut-blue) transparent; }
        .hdr-drop-item { display: flex; align-items:flex-start; gap:10px; padding: 10px; border-bottom: 1px dotted var(--line); font-size: 11px; color: #444; line-height: 1.3; text-decoration: none; transition: 0.2s; }
        .hdr-drop-item:hover { background: #eef4ff; text-decoration: none; }
        .hdr-drop-pic { width: 35px; height: 35px; border-radius: 3px; background: #e4ebf5; flex-shrink: 0; overflow:hidden; border: 1px solid #ccc; display:flex; align-items:center; justify-content:center; }
        .hdr-drop-pic img { width: 100%; height: 100%; object-fit: cover; }
        .hdr-drop-all { display: block; text-align: center; padding: 8px; background: #f4f7fc; font-size: 11px; font-weight: bold; color: var(--link); text-decoration: none; border-radius: 0 0 3px 3px; }
        .hdr-drop-all:hover { text-decoration: underline; background: #eef4ff; }
        .hdr-badge { background: #cc0000; color: #fff; padding: 1px 5px; border-radius: 4px; font-size: 10px; margin-left: 3px; vertical-align: middle; font-weight: bold; }
        .btn-req { background: #fff; border: 1px solid #ccc; padding: 3px 6px; font-size: 9px; cursor: pointer; border-radius: 2px; color: #333; }
        .btn-req:hover { background: #e4ebf5; border-color: var(--orkut-blue); }
        .btn-req.accept { background: #e4f2e9; border-color: #8bc59e; color: #2a6b2a; }
        .btn-req.accept:hover { background: #d1e8d9; }
        .btn-invite-active { background-color: var(--orkut-pink); color: #ffffff !important; border: 1px solid var(--orkut-pink); }
        .btn-invite-active:hover { background-color: var(--orkut-pink); color: #ffffff !important; text-decoration: none; }
        .btn-invite-empty { background-color: #dbe3ef; color: #666 !important; border: 1px solid #a5bce3; }
        .btn-invite-empty:hover { background-color: #e4ebf5; text-decoration: none; }
        .sub-badge { position: absolute; top: -8px; right: -15px; background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 1px 5px; border-radius: 10px; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2); animation: pulseSubBadge 1.5s infinite; line-height: 1; z-index:10; }
        @keyframes pulseSubBadge { 0% { transform: scale(1); } 50% { transform: scale(1.15); background: #ff4db8; } 100% { transform: scale(1); } }
    </style>
    <header>
        <div class="header-wrapper">
            <div class="header-main">
                <div class="logo-container">
                    <a href="/profile.php" class="logo">yorkut</a>
                    <nav class="nav-main hide-on-mobile">
                        <a href="/profile.php">Início</a>
                        <a href="/profile.php?uid=${_userData ? _userData.id : ''}">Perfil</a>
                        <a href="/recados.php?uid=${_userData ? _userData.id : ''}">Página de Recados</a>
                        <a href="/amigos.php?uid=${_userData ? _userData.id : ''}">Amigos</a>
                        <a href="/user_comunidades.php?uid=${_userData ? _userData.id : ''}">Comunidades</a>
                    </nav>
                </div>
                <div style="font-size:11px; display:flex; align-items:center; gap:15px;">
                    <a href="/meus_convites.php" class="btn-invite ${btnConviteClass}" style="padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                        ${btnConviteText}
                    </a> |
                    <div class="hdr-drop-container">
                        <a href="javascript:void(0);" onclick="toggleHdrDrop('drop-reqs', event)" style="color:#fff; text-decoration:none;" id="hdr-reqs-link">Solicitações${_solicitacoesPendentes > 0 ? ' <span class=\'hdr-badge\' id=\'hdr-req-badge\'>' + _solicitacoesPendentes + '</span>' : ''}</a>
                        <div id="drop-reqs" class="hdr-dropdown">
                            <div id="drop-reqs-content"><div style="padding:20px; color:#999; text-align:center; font-size:11px;">Carregando...</div></div>
                            <a href="/solicitacoes.php" class="hdr-drop-all">Ver todas as solicitações</a>
                        </div>
                    </div> |
                    <div class="hdr-drop-container">
                        <a href="javascript:void(0);" onclick="toggleHdrDrop('drop-notifs', event); loadHeaderNotificacoes();" style="color:#fff; text-decoration:none;" id="hdr-notifs-link">🔔 Notificações${_notificacoesNaoLidas > 0 ? ' <span class="hdr-badge" id="hdr-notif-badge" style="background:#cc0000;">' + _notificacoesNaoLidas + '</span>' : ''}</a>
                        <div id="drop-notifs" class="hdr-dropdown">
                            <div id="drop-notifs-content"><div style="padding:20px; color:#999; text-align:center; font-size:11px;">Carregando...</div></div>
                            <a href="/notificacoes.php" class="hdr-drop-all">Ver todas as notificações</a>
                        </div>
                    </div> |
                    ${_userData && _userData.is_admin ? '<a href="/admin.php" style="color:#ffcaca; text-decoration:none; font-weight:bold;" id="hdr-admin-link">⚙️ Admin' + (_denunciasPendentes > 0 ? ' <span class="hdr-badge" id="hdr-den-badge" style="background:#cc0000;">' + _denunciasPendentes + '</span>' : '') + '</a> | ' : ''}
                    <span>${email}</span> | <a href="javascript:void(0);" onclick="doLogout()" style="color:#fff;">sair</a>
                </div>
            </div>
        </div>
        <div class="header-sub-wrapper">
            <div class="header-sub">
                <a href="/recados.php?uid=${_userData ? _userData.id : ''}" id="hdr-recados-link" style="margin-right: 10px; position: relative;">recados ${_recadosNaoLidos > 0 ? '<span class="sub-badge">' + _recadosNaoLidos + '</span>' : ''}</a>
                <a href="/mensagens_particular.php" id="hdr-msg-link" style="margin-right: 25px; position: relative;">mensagens ${_mensagensNaoLidas > 0 ? '<span class="sub-badge">' + _mensagensNaoLidas + '</span>' : ''}</a>
                <a href="/fotos.php?uid=${_userData ? _userData.id : ''}" style="margin-right: 10px;">fotos</a>
                <a href="/videos.php?uid=${_userData ? _userData.id : ''}" style="margin-right: 10px;">vídeos</a>
                <a href="/depoimentos.php?uid=${_userData ? _userData.id : ''}" id="hdr-dep-link" style="margin-right: 25px; position: relative;">depoimentos ${_depoimentosNaoLidos > 0 ? '<span class="sub-badge">' + _depoimentosNaoLidos + '</span>' : ''}</a>
                <a href="/configuracoes.php" style="margin-right: 10px;">configurações</a>
                <a href="/anuncios.php" style="margin-right: 10px;">anúncios</a>
                <div class="search-bars hide-on-mobile">
                    <form action="/search_user.php" method="GET" class="search-form"><input type="text" name="q" placeholder="buscar amigo..." required><button type="submit">🔍</button></form>
                    <form action="/search_community.php" method="GET" class="search-form"><input type="text" name="q" placeholder="buscar comunidade..." required><button type="submit">🔍</button></form>
                </div>
            </div>
        </div>
    </header>`;
}

function generateLeftMenu(activePage) {
    const uid = _userData?.id || '';
    const foto = _userData?.foto_perfil || getDefaultAvatar(_userData?.sexo);
    const nome = _userData?.nome || '';
    let idade = '';
    if (_userData?.nascimento) {
        const nasc = new Date(_userData.nascimento);
        const hoje = new Date();
        idade = hoje.getFullYear() - nasc.getFullYear();
        const m = hoje.getMonth() - nasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
        idade = ', ' + idade + ' anos';
    }

    function isActive(page) { return activePage === page ? 'style="background-color:#eef4ff;"' : ''; }

    return `
    <style>
        .menu-badge { background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 10px; margin-left: auto; display: inline-block; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2); animation: pulseBadgeLeft 1.5s infinite; line-height: 1; }
        @keyframes pulseBadgeLeft { 0% { transform: scale(1); } 50% { transform: scale(1.15); background: #ff4db8; } 100% { transform: scale(1); } }
        .menu-left li a { display: flex; align-items: center; width: 100%; box-sizing: border-box; }
        .menu-category { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #7992b5; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid var(--line); padding-left: 5px; letter-spacing: 0.5px; }
        .menu-category:first-child { margin-top: 5px; }
    </style>
    <div class="card-left">
        <div class="profile-pic" style="margin:0 auto 10px;width:160px;height:160px;">
            <img src="${foto}" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div style="text-align:center; font-style:italic; color:#888; font-size:11px; margin-bottom:15px; line-height:1.4; padding:0 10px;">
            <div style="margin-bottom:5px;">
                <strong style="color:var(--link); font-size:13px; font-style:normal;">${nome}</strong>
            </div>
            ${nome}${idade}
        </div>
        <ul class="menu-left hide-on-mobile">
            <div class="menu-category">PERFIL</div>
            <li ${isActive('profile')} style="display:flex; align-items:center; justify-content:space-between;"><a href="/profile.php?uid=${uid}" style="flex:1;"><span>👤</span> perfil</a><a href="/profile.php?uid=${uid}" style="font-size:10px; color:var(--link); text-decoration:none; width:auto; flex-shrink:0; padding-right:10px;">editar</a></li>
            <li ${isActive('recados')}><a href="/recados.php?uid=${uid}" id="menu-recados-link"><span>📝</span> recados${_recadosNaoLidos > 0 ? ' <span class="menu-badge">' + _recadosNaoLidos + '</span>' : ''}</a></li>
            <li ${isActive('fotos')}><a href="/fotos.php?uid=${uid}"><span>📷</span> fotos</a></li>
            <li ${isActive('videos')}><a href="/videos.php?uid=${uid}"><span>🎥</span> vídeos</a></li>
            <li ${isActive('mensagens')}><a href="/mensagens_particular.php" id="menu-msg-link"><span>✉️</span> mensagens${_mensagensNaoLidas > 0 ? ' <span class="menu-badge">' + _mensagensNaoLidas + '</span>' : ''}</a></li>
            <li ${isActive('depoimentos')}><a href="/depoimentos.php?uid=${uid}" id="menu-dep-link"><span>🌟</span> depoimentos${_depoimentosNaoLidos > 0 ? ' <span class="menu-badge">' + _depoimentosNaoLidos + '</span>' : ''}</a></li>
            <div class="menu-category">Jogos e Apps</div>
            <li><a href="/colheita.php?uid=${uid}"><span>🌽</span> colheita feliz</a></li>
            <li><a href="/buddypoke.php?uid=${uid}"><span>🫂</span> buddy poke</a></li>
            <li><a href="/cafemania.php?uid=${uid}"><span>☕</span> café mania</a></li>
            <div class="menu-category">Sistema</div>
            <li ${isActive('temas')}><a href="/temas.php"><span>🎨</span> temas</a></li>
            <li ${isActive('configuracoes')}><a href="/configuracoes.php"><span>⚙️</span> configurações</a></li>
            <li ${isActive('anuncios')}><a href="/anuncios.php"><span>📢</span> anúncios</a></li>
            <li ${isActive('notificacoes')}><a href="/notificacoes.php"><span>🔔</span> notificações${_notificacoesNaoLidas > 0 ? ' <span class="menu-badge">' + _notificacoesNaoLidas + '</span>' : ''}</a></li>
            <li ${isActive('kutcoin')}><a href="/kutcoin.php"><span>🪙</span> KutCoin</a></li>
            <li ${isActive('sugestoes')}><a href="/sugestoes.php"><span>💡</span> sugestões</a></li>
            <li ${isActive('reportar_bug')}><a href="/reportar_bug.php"><span>🐛</span> reportar bug</a></li>
        </ul>
    </div>`;
}

function generateVisitorLeftMenu(activePage) {
    const user = _visitingUser;
    const uid = _visitingUid;
    const foto = user.foto_perfil || getDefaultAvatar(user.sexo);
    const nome = user.nome || '';
    let idade = '';
    if (user.nascimento) {
        const nasc = new Date(user.nascimento);
        const hoje = new Date();
        idade = hoje.getFullYear() - nasc.getFullYear();
        const m = hoje.getMonth() - nasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
        idade = ', ' + idade + ' anos';
    }
    let detalhes = nome + idade;
    if (user.estado_civil) detalhes += ', ' + user.estado_civil;
    if (user.cidade) detalhes += ', ' + user.cidade;
    if (user.estado) detalhes += ', ' + user.estado;

    function isActive(page) { return activePage === page ? 'style="background-color:#eef4ff;"' : ''; }

    return `
    <style>
        .menu-badge { background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 10px; margin-left: auto; display: inline-block; font-weight: bold; }
        .menu-left li a { display: flex; align-items: center; width: 100%; box-sizing: border-box; }
        .menu-category { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #7992b5; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid var(--line); padding-left: 5px; letter-spacing: 0.5px; }
        .menu-category:first-child { margin-top: 5px; }
        .menu-btn-action { width:100%; background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:8px; padding:8px 15px; font-size:12px; color:var(--link); text-align:left; transition:0.2s; box-sizing:border-box; }
        .menu-btn-action:hover { background:#eef4ff; color:var(--orkut-pink); }
    </style>
    <div class="card-left">
        <div class="profile-pic" style="margin:0 auto 10px;width:160px;height:160px;">
            <img src="${foto}" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div style="text-align:center; font-style:italic; color:#888; font-size:11px; margin-bottom:15px; line-height:1.4; padding:0 10px;">
            ${detalhes}
        </div>
        <ul class="menu-left hide-on-mobile">
            <div class="menu-category">Perfil</div>
            <li ${isActive('profile')}><a href="/profile.php?uid=${uid}"><span>👤</span> perfil</a></li>
            <li ${isActive('recados')}><a href="/recados.php?uid=${uid}"><span>📝</span> recados</a></li>
            <li ${isActive('fotos')}><a href="/fotos.php?uid=${uid}"><span>📷</span> fotos</a></li>
            <li ${isActive('videos')}><a href="/videos.php?uid=${uid}"><span>🎥</span> vídeos</a></li>
            <li ${isActive('mensagens')}><a href="/mensagens_particular.php?to=${uid}"><span>✉️</span> mensagens</a></li>
            <li ${isActive('depoimentos')}><a href="/depoimentos.php?uid=${uid}"><span>🌟</span> depoimentos</a></li>
            <div class="menu-category">Jogos e Apps</div>
            <li><a href="/colheita.php?uid=${uid}"><span>🌽</span> colheita feliz</a></li>
            <li><a href="/buddypoke.php?uid=${uid}"><span>🫂</span> buddy poke</a></li>
            <li><a href="/cafemania.php?uid=${uid}"><span>☕</span> café mania</a></li>
            <div class="menu-category">Interação</div>
            <li id="layout-friend-action-li"><button type="button" onclick="layoutAdicionarAmigo('${uid}')" class="menu-btn-action"><span>➕</span> adicionar amigo</button></li>
            <li><button type="button" onclick="layoutAbrirDenuncia('${uid}')" class="menu-btn-action"><span>⚠️</span> denunciar</button></li>
            <li><button type="button" onclick="showConfirm('ATENÇÃO: Ao bloquear, a amizade é desfeita. Continuar?', function() { alert('Função em breve!'); })" class="menu-btn-action"><span>🚫</span> bloquear</button></li>
        </ul>
        <div id="layout-report-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:6px;padding:20px;max-width:400px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                <h3 style="margin:0 0 8px;font-size:14px;color:var(--title);">Denunciar Usuário</h3>
                <p style="color:red;font-size:10px;margin-bottom:10px;">Atenção: Falsas denúncias podem resultar no banimento da sua conta.</p>
                <textarea id="layout-motivo-denuncia" placeholder="Motivo da denúncia..." style="width:100%;min-height:80px;padding:8px;border:1px solid #ccc;border-radius:4px;font-family:inherit;font-size:12px;box-sizing:border-box;resize:vertical;"></textarea>
                <button type="button" onclick="layoutEnviarDenuncia()" style="background:#c62828;color:#fff;border:none;padding:8px;cursor:pointer;border-radius:4px;width:100%;margin-top:8px;font-size:12px;font-weight:bold;">Enviar Denúncia</button>
                <button type="button" onclick="layoutFecharDenuncia()" style="background:#fff;width:100%;border:1px solid #ccc;padding:8px;cursor:pointer;border-radius:4px;margin-top:5px;font-size:12px;">Cancelar</button>
            </div>
        </div>
    </div>`;
}

function generateRightCol() {
    // Se visitando, mostra amigos/comunidades do visitado
    const uid = _visitingUid;
    const amigosLink = uid ? '/amigos.php?uid=' + uid : '/amigos.php';
    const comunidadesLink = uid ? '/user_comunidades.php?uid=' + uid : (_userData ? '/user_comunidades.php?uid=' + _userData.id : '/user_comunidades.php');

    return `
    <style>
        .box-visitantes-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; padding-bottom: 5px; }
        .visitante-quadrado { width: 100%; aspect-ratio: 1 / 1; border-radius: 4px; border: 1px solid #c0d0e6; background: #e4ebf5; display: flex; align-items: center; justify-content: center; overflow: hidden; text-decoration: none; transition: all 0.2s ease; }
        .visitante-quadrado:hover { border-color: var(--orkut-pink); transform: scale(1.08); box-shadow: 0 3px 8px rgba(0,0,0,0.15); z-index: 2; }
        .visitante-quadrado img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .visitante-sem-foto { font-size: 10px; color: #999; text-align: center; line-height: 1.2; }
    </style>
    ${!uid ? `<div id="layout-box-visitantes" class="box-sidebar hide-on-mobile">
        <div class="box-title" style="margin-bottom: 5px;"><span>quem me visitou</span></div>
        <div id="layout-visitantes-grid" class="box-visitantes-grid">
            <div class="visitante-quadrado"><span class="visitante-sem-foto">Carregando...</span></div>
        </div>
    </div>` : ''}
    <div class="box-sidebar" id="layout-box-amigos">
        <div class="box-title" id="layout-amigos-title">amigos (0) <a href="${amigosLink}">ver todos</a></div>
        <div class="grid" id="layout-amigos-grid">
            <div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Carregando...</div>
        </div>
    </div>
    <div class="box-sidebar">
        <div class="box-title">comunidades (0) <a href="${comunidadesLink}">ver todas</a></div>
        <div class="grid">
            <div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Nenhuma comunidade.</div>
        </div>
    </div>`;
}

function generateFooter() {
    return `
    <footer class="footer">
        <span class="google-logo">yorkut</span> © 2026 | 
        <a href="/sobre.php">Sobre o yorkut</a> | 
        <a href="/novidades.php">Novidades</a> | 
        <a href="/seguranca.php">Centro de segurança</a> | 
        <a href="/privacidade.php">Privacidade</a> | 
        <a href="/termos.php">Termos de uso</a> | 
        <a href="/contato.php">Contato</a>
    </footer>`;
}

// Funções globais do header
function toggleHdrDrop(id, event) {
    event.stopPropagation();
    document.querySelectorAll('.hdr-dropdown').forEach(d => { if(d.id !== id) d.style.display = 'none'; });
    let el = document.getElementById(id);
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('click', function(e) {
    document.querySelectorAll('.hdr-dropdown').forEach(d => { if(!d.contains(e.target) && d.style.display === 'block') d.style.display = 'none'; });
});

async function doLogout() {
    await fetch('/api/logout', { method: 'POST' });
    window.location.href = '/index.php';
}

function getUserData() { return _userData; }
function getConvitesData() { return _convitesData; }

// ===== Aplicação de temas (igual ao original yorkut.com.br) =====
function applyTheme() {
    if (!_userData || _userData.sem_tema || !_temaData) {
        // Sem tema: limpar qualquer tema aplicado anteriormente
        document.body.removeAttribute('data-theme-slug');
        document.body.style.backgroundImage = '';
        document.body.style.backgroundRepeat = '';
        document.body.style.backgroundSize = '';
        // Restaurar variáveis CSS padrão
        document.documentElement.style.removeProperty('--title');
        document.documentElement.style.removeProperty('--orkut-blue');
        document.documentElement.style.removeProperty('--orkut-light');
        return;
    }

    const tema = _temaData;

    // Atributo data-theme-slug no body (igual original)
    document.body.setAttribute('data-theme-slug', tema.slug);

    // Background tiled (igual original: background-size:200px)
    document.body.style.backgroundImage = "url('/" + tema.imagem + "')";
    document.body.style.backgroundRepeat = 'repeat';
    document.body.style.backgroundSize = '200px';

    // Sobrescrever variáveis CSS (igual original)
    // cor1 → --title, --orkut-blue
    // cor2 → --orkut-light
    document.documentElement.style.setProperty('--title', tema.cor1);
    document.documentElement.style.setProperty('--orkut-blue', tema.cor1);
    document.documentElement.style.setProperty('--orkut-light', tema.cor2);
}

// ===== Polling: atualizar badges em tempo real =====
let _badgePollingInterval = null;

function startBadgePolling(intervalMs = 15000) {
    if (_badgePollingInterval) return; // já rodando
    _badgePollingInterval = setInterval(async () => {
        try {
            const resp = await fetch('/api/me');
            const data = await resp.json();
            if (!data.success) return;

            let msgs = data.mensagensNaoLidas || 0;
            let recs = data.recadosNaoLidos || 0;
            let deps = data.depoimentosNaoLidos || 0;

            // Se estamos na página de recados, suprimir o badge (já está sendo visto)
            if (_activePageGlobal === 'recados' && !_visitingUser) recs = 0;
            if (_activePageGlobal === 'depoimentos' && !_visitingUser) deps = 0;

            // Atualizar globais
            _mensagensNaoLidas = msgs;
            _recadosNaoLidos = recs;
            _depoimentosNaoLidos = deps;

            // Header sub-bar: recados
            const hdrRec = document.getElementById('hdr-recados-link') || document.getElementById('hdr-recado-link');
            if (hdrRec) hdrRec.innerHTML = 'recados' + (recs > 0 ? ' <span class="sub-badge">' + recs + '</span>' : '');

            // Header sub-bar: mensagens
            const hdrMsg = document.getElementById('hdr-msg-link');
            if (hdrMsg) hdrMsg.innerHTML = 'mensagens' + (msgs > 0 ? ' <span class="sub-badge">' + msgs + '</span>' : '');

            // Header sub-bar: depoimentos
            const hdrDep = document.getElementById('hdr-dep-link');
            if (hdrDep) hdrDep.innerHTML = 'depoimentos' + (deps > 0 ? ' <span class="sub-badge">' + deps + '</span>' : '');

            // Menu lateral: recados
            const menuRec = document.getElementById('menu-recados-link') || document.getElementById('menu-recado-link');
            if (menuRec) menuRec.innerHTML = '<span>📝</span> recados' + (recs > 0 ? ' <span class="menu-badge">' + recs + '</span>' : '');

            // Menu lateral: mensagens
            const menuMsg = document.getElementById('menu-msg-link');
            if (menuMsg) menuMsg.innerHTML = '<span>✉️</span> mensagens' + (msgs > 0 ? ' <span class="menu-badge">' + msgs + '</span>' : '');

            // Menu lateral: depoimentos
            const menuDep = document.getElementById('menu-dep-link');
            if (menuDep) menuDep.innerHTML = '<span>🌟</span> depoimentos' + (deps > 0 ? ' <span class="menu-badge">' + deps + '</span>' : '');

            // Header: solicitações de amizade
            let sols = data.solicitacoesPendentes || 0;
            _solicitacoesPendentes = sols;
            const hdrReqs = document.getElementById('hdr-reqs-link');
            if (hdrReqs) hdrReqs.innerHTML = 'Solicitações' + (sols > 0 ? " <span class='hdr-badge' id='hdr-req-badge'>" + sols + '</span>' : '');

            // Header: notificações badge
            let notifs = data.notificacoesNaoLidas || 0;
            _notificacoesNaoLidas = notifs;
            const hdrNotifs = document.getElementById('hdr-notifs-link');
            if (hdrNotifs) hdrNotifs.innerHTML = '🔔 Notificações' + (notifs > 0 ? ' <span class="hdr-badge" id="hdr-notif-badge" style="background:#cc0000;">' + notifs + '</span>' : '');

            // Menu esquerdo: notificações badge
            const menuNotifLinks = document.querySelectorAll('a[href="/notificacoes.php"]');
            menuNotifLinks.forEach(link => {
                if (link.closest('.menu-left')) {
                    link.innerHTML = '<span>🔔</span> notificações' + (notifs > 0 ? ' <span class="menu-badge">' + notifs + '</span>' : '');
                }
            });

            // Header: admin denúncias badge
            let denPend = data.denunciasPendentes || 0;
            _denunciasPendentes = denPend;
            const hdrAdmin = document.getElementById('hdr-admin-link');
            if (hdrAdmin) hdrAdmin.innerHTML = '⚙️ Admin' + (denPend > 0 ? ' <span class="hdr-badge" id="hdr-den-badge" style="background:#cc0000;">' + denPend + '</span>' : '');

            // Atualizar título da aba com contagem total
            const total = msgs + recs + deps + sols + notifs;
            const baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
            document.title = total > 0 ? '(' + total + ') ' + baseTitle : baseTitle;
        } catch(e) { /* silencioso */ }
    }, intervalMs);
}

function stopBadgePolling() {
    if (_badgePollingInterval) {
        clearInterval(_badgePollingInterval);
        _badgePollingInterval = null;
    }
}

// ===== VISITANTES: Carregar "quem me visitou" no right-col =====
async function layoutLoadVisitantes(uid) {
    try {
        const grid = document.getElementById('layout-visitantes-grid');
        const box = document.getElementById('layout-box-visitantes');
        if (!grid || !box) return;

        const resp = await fetch('/api/visitas/' + uid);
        const data = await resp.json();

        if (!data.success || data.rastro_desativado) {
            grid.innerHTML = '<div style="grid-column:1/-1;padding:15px 10px;color:#666;text-align:center;font-size:11px;line-height:1.4;background:#f9f9f9;border:1px dashed #ccc;border-radius:4px;">Você desativou o rastro. Ative nas <a href=\'configuracoes.php\' style=\'color:var(--link);font-weight:bold;\'>Configurações</a> para ver seus visitantes.</div>';
            return;
        }

        if (!data.visitas || data.visitas.length === 0) {
            grid.innerHTML = '<div class="visitante-quadrado"><span class="visitante-sem-foto">Nenhuma visita ainda</span></div>';
            return;
        }

        let html = '';
        data.visitas.forEach(function(v) {
            const fotoSrc = v.foto_perfil || getDefaultAvatar(v.sexo);
            const titulo = v.nome + ' visitou você dia ' + v.data;
            html += '<a href="profile.php?uid=' + v.visitante_id + '" title="' + titulo + '" class="visitante-quadrado"><img src="' + fotoSrc + '" alt="Foto"></a>';
        });
        grid.innerHTML = html;
    } catch(err) {
        console.error('Erro ao carregar visitantes (layout):', err);
    }
}

// ===== AMIZADE: Funções do layout (usadas no menu lateral de visitante e outras páginas) =====

async function layoutLoadAmigos(uid) {
    try {
        const resp = await fetch('/api/amigos/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const total = data.total || 0;
        const amigosLink = '/amigos.php?uid=' + uid;

        const titleEl = document.getElementById('layout-amigos-title');
        if (titleEl) titleEl.innerHTML = 'amigos (' + total + ') <a href="' + amigosLink + '">ver todos</a>';

        const gridEl = document.getElementById('layout-amigos-grid');
        if (gridEl) {
            if (total === 0) {
                gridEl.innerHTML = '<div class="grid-item" style="grid-column:1/-1;text-align:center;color:#999;padding:15px;">Nenhum amigo ainda.</div>';
            } else {
                let html = '';
                data.amigos.slice(0, 9).forEach(function(a) {
                    const foto = a.foto_perfil || getDefaultAvatar(a.sexo);
                    const nome = a.nome.length > 10 ? a.nome.substring(0, 10).trim() + '..' : a.nome;
                    html += '<div class="grid-item"><a href="profile.php?uid=' + a.id + '"><div class="grid-thumb"><img src="' + foto + '"></div>' + nome + '</a></div>';
                });
                gridEl.innerHTML = html;
            }
        }
    } catch(err) {
        console.error('Erro ao carregar amigos:', err);
    }
}

async function layoutCheckFriendship(uid) {
    try {
        const resp = await fetch('/api/amizade/status/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const li = document.getElementById('layout-friend-action-li');
        if (!li) return;

        if (data.status === 'amigos') {
            li.innerHTML = '<button type="button" onclick="layoutDesfazerAmizade(\'' + uid + '\')" class="menu-btn-action"><span>💔</span> desfazer amizade</button>';
        } else if (data.status === 'enviada') {
            li.innerHTML = '<button type="button" onclick="layoutCancelarSolicitacao(\'' + uid + '\')" class="menu-btn-action" style="color:#999;"><span>⏳</span> solicitação enviada</button>';
        } else if (data.status === 'recebida') {
            li.innerHTML = '<button type="button" onclick="layoutAceitarAmizade(' + data.request_id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>✅</span> aceitar amizade</button>';
        } else {
            li.innerHTML = '<button type="button" onclick="layoutAdicionarAmigo(\'' + uid + '\')" class="menu-btn-action"><span>➕</span> adicionar amigo</button>';
        }
    } catch(err) {}
}

async function layoutAdicionarAmigo(uid) {
    try {
        const resp = await fetch('/api/amizade/solicitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: uid })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Solicitação de amizade enviada!');
            layoutCheckFriendship(uid);
        } else {
            alert(data.message || 'Erro ao enviar solicitação.');
        }
    } catch(err) {
        alert('Erro ao enviar solicitação.');
    }
}

async function layoutDesfazerAmizade(uid) {
    showConfirm('Tem certeza que deseja desfazer a amizade?', async function() {
        try {
            const resp = await fetch('/api/amizade/desfazer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amigo_id: uid })
            });
            const data = await resp.json();
            if (data.success) {
                alert('Amizade desfeita.');
                layoutCheckFriendship(uid);
                layoutLoadAmigos(uid);
            } else {
                alert(data.message || 'Erro.');
            }
        } catch(err) { alert('Erro.'); }
    });
}

async function layoutCancelarSolicitacao(uid) {
    showConfirm('Cancelar a solicitação de amizade?', async function() {
        try {
            const resp = await fetch('/api/amizade/cancelar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ destinatario_id: uid })
            });
            const data = await resp.json();
            if (data.success) {
                alert('Solicitação cancelada.');
                layoutCheckFriendship(uid);
            } else { alert(data.message || 'Erro.'); }
        } catch(err) { alert('Erro.'); }
    });
}

async function layoutAceitarAmizade(requestId) {
    try {
        const resp = await fetch('/api/amizade/aceitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Amizade aceita!');
            window.location.reload();
        } else { alert(data.message || 'Erro.'); }
    } catch(err) { alert('Erro.'); }
}

async function loadHeaderPendingRequests() {
    try {
        const resp = await fetch('/api/amizade/pendentes');
        const data = await resp.json();
        if (!data.success) return;

        const contentEl = document.getElementById('drop-reqs-content');
        if (!contentEl) return;

        if (data.total === 0) {
            contentEl.innerHTML = '<div style="padding:20px; color:#999; text-align:center; font-size:11px;">Nenhuma solicitação pendente.</div>';
            return;
        }

        let html = '';
        data.pendentes.forEach(function(p) {
            const foto = p.foto_perfil || getDefaultAvatar(p.sexo);
            html += '<div class="hdr-drop-item" id="hdr-req-item-' + p.request_id + '">';
            html += '<div class="hdr-drop-pic"><a href="profile.php?uid=' + p.remetente_id + '"><img src="' + foto + '"></a></div>';
            html += '<div style="flex:1;">';
            html += '<b><a href="profile.php?uid=' + p.remetente_id + '" style="color:var(--link);">' + p.nome + '</a></b>';
            html += '<div style="margin-top:5px; display:flex; gap:5px;">';
            html += '<button type="button" onclick="hdrAcceptReq(' + p.request_id + ')" class="btn-req accept">Aprovar</button>';
            html += '<button type="button" onclick="hdrRejectReq(' + p.request_id + ')" class="btn-req">Recusar</button>';
            html += '</div></div></div>';
        });
        contentEl.innerHTML = html;
    } catch(err) {}
}

let _notifsLoaded = false;
async function loadHeaderNotificacoes() {
    try {
        const resp = await fetch('/api/notificacoes');
        const data = await resp.json();
        if (!data.success) return;

        const contentEl = document.getElementById('drop-notifs-content');
        if (!contentEl) return;

        if (!data.notificacoes || data.notificacoes.length === 0) {
            contentEl.innerHTML = '<div style="padding:20px; color:#999; text-align:center; font-size:11px;">Nenhuma nova menção.</div>';
            return;
        }

        let html = '';
        data.notificacoes.forEach(function(n) {
            const isUnread = !n.lida;
            const bgStyle = isUnread ? 'background:#fff8e1;' : '';
            const dtRaw = n.criado_em || '';
            let dtFormatted = dtRaw;
            try {
                const d = new Date(dtRaw.replace(' ', 'T') + '-03:00');
                dtFormatted = d.toLocaleDateString('pt-BR', {day:'2-digit',month:'2-digit'}) + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
            } catch(e) {}

            // Build photo or icon prefix
            let photoHtml = '';
            if (n.tipo === 'anuncio') {
                photoHtml = '<span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:#fff3cd;border-radius:50%;margin-right:8px;flex-shrink:0;font-size:16px;">📢</span>';
            } else if (n.remetente_foto) {
                photoHtml = '<img src="' + n.remetente_foto + '" style="width:32px;height:32px;border-radius:4px;object-fit:cover;margin-right:8px;flex-shrink:0;" />';
            } else {
                photoHtml = '<span style="margin-right:4px;">📩</span>';
            }

            html += '<a href="' + (n.link || '#') + '" class="hdr-notif-item" style="display:flex; align-items:flex-start; padding:10px 12px; border-bottom:1px solid #e5e5e5; text-decoration:none; color:#333; font-size:11px; ' + bgStyle + '">';
            html += photoHtml;
            html += '<div style="flex:1; min-width:0;">';
            html += '<div style="font-weight:' + (isUnread ? 'bold' : 'normal') + '; color:var(--title); margin-bottom:3px;">' + (n.titulo || '') + '</div>';
            html += '<div style="color:#666; font-size:10px; margin-bottom:2px;">' + (n.mensagem || '') + '</div>';
            html += '<div style="color:#999; font-size:9px;">' + dtFormatted + '</div>';
            html += '</div>';
            html += '</a>';
        });
        contentEl.innerHTML = html;

        // Marcar como lidas somente se o dropdown está realmente aberto (clicou)
        const dropNotifs = document.getElementById('drop-notifs');
        const dropdownVisible = dropNotifs && dropNotifs.style.display !== 'none' && dropNotifs.style.display !== '';
        if (data.naoLidas > 0 && dropdownVisible && !_notifsLoaded) {
            _notifsLoaded = true;
            setTimeout(async () => {
                try {
                    await fetch('/api/notificacoes/marcar-lidas', { method: 'POST' });
                    _notificacoesNaoLidas = 0;
                    const badge = document.getElementById('hdr-notif-badge');
                    if (badge) badge.style.display = 'none';
                } catch(e) {}
            }, 2000);
        }
    } catch(err) {}
}

async function hdrAcceptReq(reqId) {
    try {
        const resp = await fetch('/api/amizade/aceitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: reqId })
        });
        const data = await resp.json();
        const item = document.getElementById('hdr-req-item-' + reqId);
        if (item) item.style.display = 'none';
        // Update badge
        const badge = document.getElementById('hdr-req-badge');
        if (badge) {
            let c = parseInt(badge.innerText) - 1;
            if (c > 0) badge.innerText = c;
            else badge.style.display = 'none';
        }
    } catch(err) {}
}

async function hdrRejectReq(reqId) {
    try {
        const resp = await fetch('/api/amizade/recusar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: reqId })
        });
        const data = await resp.json();
        const item = document.getElementById('hdr-req-item-' + reqId);
        if (item) item.style.display = 'none';
        const badge = document.getElementById('hdr-req-badge');
        if (badge) {
            let c = parseInt(badge.innerText) - 1;
            if (c > 0) badge.innerText = c;
            else badge.style.display = 'none';
        }
    } catch(err) {}
}

let _layoutDenunciaUid = null;

function layoutAbrirDenuncia(uid) {
    _layoutDenunciaUid = uid;
    const modal = document.getElementById('layout-report-modal');
    if (modal) { modal.style.display = 'flex'; }
}

function layoutFecharDenuncia() {
    const modal = document.getElementById('layout-report-modal');
    if (modal) { modal.style.display = 'none'; }
    const ta = document.getElementById('layout-motivo-denuncia');
    if (ta) ta.value = '';
}

async function layoutEnviarDenuncia() {
    const motivo = document.getElementById('layout-motivo-denuncia').value.trim();
    if (!motivo) { alert('Preencha o motivo da denúncia.'); return; }
    try {
        const resp = await fetch('/api/denunciar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ denunciado_id: _layoutDenunciaUid, motivo })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Denúncia enviada com sucesso! Nossa equipe irá analisar.');
            layoutFecharDenuncia();
        } else {
            alert(data.message || 'Erro ao enviar denúncia.');
        }
    } catch(err) {
        console.error('Erro ao denunciar:', err);
        alert('Erro ao enviar denúncia.');
    }
}
