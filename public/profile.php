<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Yorkut - Seu perfil na rede social. Veja amigos, recados, fotos e comunidades.">
<title>Yorkut - Perfil</title>
<script src="/js/toast.js"></script>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .icon-edit { font-size:12px; color:#a5bce3; text-decoration:none; margin-left:8px; vertical-align:middle; transition:0.2s; display:inline-block; background:#f4f7fc; border-radius:50%; padding:4px; border:1px solid #dbe3ef; line-height:1; }
    .icon-edit:hover { color:var(--link); background:#eef4ff; border-color:var(--orkut-blue); transform:scale(1.1); text-decoration:none; }
    .perfil-foto-botoes { display:flex; gap:5px; justify-content:center; margin-bottom:15px; }
    .btn-foto { background:#fff; border:1px solid #c0d0e6; color:var(--link); font-size:10px; padding:6px 12px; border-radius:12px; cursor:pointer; font-weight:bold; transition:0.2s;}
    .btn-foto:hover { background:#eef4ff; border-color:var(--orkut-blue); }
    .detalhes-usuario-centro { text-align:center; font-style:italic; color:#888; font-size:11px; margin-bottom:15px; line-height:1.4; padding:0 10px; }
    .dep-pendente { border-left: 3px solid #f39c12 !important; background: #fffdf5 !important; }
    .iframe-colheita { width: 100%; height: 650px; border: none; border-radius: 4px; overflow: hidden; background: #fff; }
    .menu-btn-action { width:100%; background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:8px; padding:8px 15px; font-size:12px; color:var(--link); text-align:left; transition:0.2s; box-sizing:border-box; }
    .menu-btn-action:hover { background:#eef4ff; color:var(--orkut-pink); }
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; }
    .modal-box { background:#fff; padding:20px; border-radius:8px; max-width:400px; width:90%; box-shadow:0 4px 20px rgba(0,0,0,0.3); }
    .modal-box h3 { margin-bottom:10px; color:#333; }
    .modal-box textarea { width:100%; height:80px; border:1px solid #ccc; border-radius:4px; padding:8px; font-size:12px; resize:vertical; margin-bottom:10px; box-sizing:border-box; }
    .btn-danger { background:#cc0000; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-weight:bold; }
    .btn-danger:hover { background:#aa0000; }
</style>
<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function getDefaultAvatar(sexo) { return sexo === 'F' ? '/img/default-avatar-female.png' : '/img/default-avatar.png'; }
function toggleForm(id){var e=document.getElementById(id);if(e)e.style.display=(e.style.display==='flex'||e.style.display==='block')?'none':'flex';}
function showTab(tabId){document.querySelectorAll('.tab-content').forEach(el=>el.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(el=>el.classList.remove('active'));document.getElementById('tab-'+tabId).classList.add('active');document.getElementById('btn-'+tabId).classList.add('active');}

let img=new Image(),canvas,ctx,scale=1,offsetX=0,offsetY=0,isDragging=false,startX,startY;

// State
let _visitingUid = null;
let _isVisiting = false;
let _meData = null;
let _viewUser = null;

window.onload = async function(){
    const urlParams = new URLSearchParams(window.location.search);
    _visitingUid = urlParams.get('uid');

    canvas = document.getElementById('cropCanvas');
    if(canvas){
        ctx = canvas.getContext('2d');
        var fInp = document.getElementById('fileInput');
        if(fInp) fInp.addEventListener('change', function(e){
            let r = new FileReader();
            r.onload = function(ev){
                img.onload = function(){
                    document.getElementById('cropContainer').style.display='block';
                    scale = Math.max(160/img.width, 160/img.height);
                    document.getElementById('zoomRange').min = scale;
                    document.getElementById('zoomRange').max = scale*3;
                    document.getElementById('zoomRange').value = scale;
                    offsetX = (160-img.width*scale)/2;
                    offsetY = (160-img.height*scale)/2;
                    drawCrop();
                };
                img.src = ev.target.result;
            };
            if(e.target.files[0]) r.readAsDataURL(e.target.files[0]);
        });
        var zRng = document.getElementById('zoomRange');
        if(zRng) zRng.addEventListener('input', function(){ scale=parseFloat(this.value); drawCrop(); });
        canvas.addEventListener('mousedown', function(e){ isDragging=true; startX=e.offsetX-offsetX; startY=e.offsetY-offsetY; });
        canvas.addEventListener('mousemove', function(e){ if(isDragging){ offsetX=e.offsetX-startX; offsetY=e.offsetY-startY; drawCrop(); }});
        canvas.addEventListener('mouseup', function(){ isDragging=false; });
        canvas.addEventListener('mouseleave', function(){ isDragging=false; });
    }

    await loadUserProfile();

    // Load pending friend requests for header dropdown
    profileLoadHeaderRequests();

    // Atualizar badges (recados, msgs, deps, notifs, etc.)
    async function profileRefreshBadges() {
        try {
            const resp = await fetch('/api/me');
            const data = await resp.json();
            if (!data.success) return;

            const msgs = data.mensagensNaoLidas || 0;
            const recs = data.recadosNaoLidos || 0;
            const deps = data.depoimentosNaoLidos || 0;

            // Header: recados
            const hdrRec = document.getElementById('hdr-recado-link');
            if (hdrRec) hdrRec.innerHTML = 'recados' + (recs > 0 ? ' <span class="sub-badge">' + recs + '</span>' : '');

            // Header: mensagens
            const hdrMsg = document.getElementById('hdr-msg-link');
            if (hdrMsg) hdrMsg.innerHTML = 'mensagens' + (msgs > 0 ? ' <span class="sub-badge">' + msgs + '</span>' : '');

            // Header: depoimentos
            const hdrDep = document.getElementById('hdr-dep-link');
            if (hdrDep) hdrDep.innerHTML = 'depoimentos' + (deps > 0 ? ' <span class="sub-badge">' + deps + '</span>' : '');

            // Header: solicitações
            const solic = data.solicitacoesPendentes || 0;
            const hdrReqs = document.getElementById('profile-hdr-reqs-link');
            if (hdrReqs) hdrReqs.innerHTML = 'Solicitações' + (solic > 0 ? ' <span class="hdr-badge" id="hdr-req-badge">' + solic + '</span>' : '');

            // Menu: recados
            const menuRec = document.getElementById('menu-recado-link');
            if (menuRec) menuRec.innerHTML = '<span>📝</span> recados' + (recs > 0 ? ' <span class="menu-badge">' + recs + '</span>' : '');

            // Menu: mensagens
            const menuMsg = document.getElementById('menu-msg-link');
            if (menuMsg) menuMsg.innerHTML = '<span>✉️</span> mensagens' + (msgs > 0 ? ' <span class="menu-badge">' + msgs + '</span>' : '');

            // Menu: depoimentos
            const menuDep = document.getElementById('menu-dep-link');
            if (menuDep) menuDep.innerHTML = '<span>🌟</span> depoimentos' + (deps > 0 ? ' <span class="menu-badge">' + deps + '</span>' : '');

            // Header: notificações badge
            const notifs = data.notificacoesNaoLidas || 0;
            const hdrNotifs = document.getElementById('profile-hdr-notifs-link');
            if (hdrNotifs) hdrNotifs.innerHTML = '\u{1F514} Notificações' + (notifs > 0 ? ' <span class="hdr-badge" id="profile-hdr-notif-badge" style="background:#cc0000;">' + notifs + '</span>' : '');

            // Menu esquerdo: notificações badge
            const menuNotif = document.getElementById('profile-menu-notif-link');
            if (menuNotif) menuNotif.innerHTML = '<span>\u{1F514}</span> notificações' + (notifs > 0 ? ' <span class="menu-badge">' + notifs + '</span>' : '');

            // Admin: denúncias badge
            const denPend = data.denunciasPendentes || 0;
            const adminLink = document.getElementById('profile-hdr-admin-link');
            if (adminLink) adminLink.innerHTML = '⚙️ Admin' + (denPend > 0 ? ' <span class="hdr-badge" style="background:#cc0000;">' + denPend + '</span>' : '');

            // Título da aba
            const total = msgs + recs + deps + solic + notifs;
            const baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
            document.title = total > 0 ? '(' + total + ') ' + baseTitle : baseTitle;
        } catch(e) { /* silencioso */ }
    }

    // Rodar imediatamente + polling a cada 15s
    profileRefreshBadges();
    setInterval(profileRefreshBadges, 15000);
};

function drawCrop(){ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(img,offsetX,offsetY,img.width*scale,img.height*scale);document.getElementById('fotoBase64').value=canvas.toDataURL('image/jpeg',0.9);}

function openAjustarFoto(){
    let currPic = document.getElementById('profileImage')?.src || '';
    if(!currPic || currPic.includes('default-avatar')){alert('Você precisa fazer o upload de uma foto primeiro!');return;}
    document.getElementById('formFoto').style.display='block';
    document.getElementById('cropContainer').style.display='block';
    img.onload = function(){
        scale = Math.max(160/img.width, 160/img.height);
        document.getElementById('zoomRange').min = scale;
        document.getElementById('zoomRange').max = scale*3;
        document.getElementById('zoomRange').value = scale;
        offsetX = (160-img.width*scale)/2;
        offsetY = (160-img.height*scale)/2;
        drawCrop();
    };
    img.src = currPic;
}

async function enviarFoto(e) {
    e.preventDefault();
    const base64 = document.getElementById('fotoBase64').value;
    if (!base64 || !base64.startsWith('data:image/')) {
        alert('Selecione uma imagem e ajuste o recorte antes de salvar.');
        return false;
    }
    const btn = document.getElementById('btnSalvarFoto');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const resp = await fetch('/api/upload-foto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ foto_base64: base64 })
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('profileImage').src = data.foto_perfil + '?t=' + Date.now();
            document.getElementById('formFoto').style.display = 'none';
        } else {
            alert(data.message || 'Erro ao salvar foto.');
        }
    } catch (err) { alert('Erro de conexão ao salvar foto.'); }
    btn.disabled = false; btn.textContent = 'Salvar Foto';
    return false;
}

async function loadUserProfile() {
    try {
        // 1. Sempre buscar dados do usuario logado (para header)
        const meResp = await fetch('/api/me');
        if (!meResp.ok) { window.location.href = '/index.php'; return; }
        const meData = await meResp.json();
        if (!meData.success) { window.location.href = '/index.php'; return; }
        _meData = meData;

        // Atualizar badges de mensagens não lidas
        const naoLidas = meData.mensagensNaoLidas || 0;
        if (naoLidas > 0) {
            const hdrLink = document.getElementById('hdr-msg-link');
            if (hdrLink) hdrLink.innerHTML = 'mensagens <span class="sub-badge">' + naoLidas + '</span>';
            const menuLink = document.getElementById('menu-msg-link');
            if (menuLink) menuLink.innerHTML = '<span>✉️</span> mensagens <span class="menu-badge">' + naoLidas + '</span>';
        }

        // Atualizar badges de recados não lidos
        const recadosNaoLidos = meData.recadosNaoLidos || 0;
        if (recadosNaoLidos > 0) {
            const hdrRecado = document.getElementById('hdr-recado-link');
            if (hdrRecado) hdrRecado.innerHTML = 'recados <span class="sub-badge">' + recadosNaoLidos + '</span>';
            const menuRecado = document.getElementById('menu-recado-link');
            if (menuRecado) menuRecado.innerHTML = '<span>📝</span> recados <span class="menu-badge">' + recadosNaoLidos + '</span>';
        }

        // Atualizar badges de depoimentos pendentes
        const depoimentosNaoLidos = meData.depoimentosNaoLidos || 0;
        if (depoimentosNaoLidos > 0) {
            const hdrDep = document.getElementById('hdr-dep-link');
            if (hdrDep) hdrDep.innerHTML = 'depoimentos <span class="sub-badge">' + depoimentosNaoLidos + '</span>';
            const menuDep = document.getElementById('menu-dep-link');
            if (menuDep) menuDep.innerHTML = '<span>🌟</span> depoimentos <span class="menu-badge">' + depoimentosNaoLidos + '</span>';
        }

        const myId = meData.user.id;
        let user = null;
        let tema = null;

        // 2. Determinar se estamos visitando outro perfil
        if (_visitingUid) {
            const visitUid = _visitingUid;
            if (visitUid && String(visitUid) !== String(myId)) {
                _isVisiting = true;
                const visitResp = await fetch('/api/user/' + visitUid);
                if (!visitResp.ok) {
                    alert('Erro ao buscar perfil.');
                    window.location.href = '/profile.php';
                    return;
                }
                const visitData = await visitResp.json();
                if (!visitData.success) {
                    alert(visitData.message || 'Usuário não encontrado!');
                    window.location.href = '/profile.php';
                    return;
                }
                user = visitData.user;
                tema = visitData.tema || null;
                _visitData = visitData;
            } else {
                _isVisiting = false;
                user = meData.user;
                tema = meData.tema || null;
            }
        } else {
            _isVisiting = false;
            user = meData.user;
            tema = meData.tema || null;
        }

        _viewUser = user;

        // 3. HEADER: sempre mostra dados do logado
        renderHeader(meData);

        // 4. PAGE: renderizar baseado no modo
        if (_isVisiting) {
            renderVisitorProfile(user, meData, _visitData);
        } else {
            renderOwnProfile(user, meData);
        }

        // 5. Aplicar tema do LOGADO
        const meuTema = meData.tema || null;
        const semTema = meData.user.sem_tema;
        if (meuTema && !semTema) {
            document.body.setAttribute('data-theme-slug', meuTema.slug);
            document.body.style.backgroundImage = "url('/" + meuTema.imagem + "')";
            document.body.style.backgroundRepeat = 'repeat';
            document.body.style.backgroundSize = '200px';
            document.documentElement.style.setProperty('--title', meuTema.cor1);
            document.documentElement.style.setProperty('--orkut-blue', meuTema.cor1);
            document.documentElement.style.setProperty('--orkut-light', meuTema.cor2);
        }

    } catch(err) {
        console.error('Erro ao carregar perfil:', err);
    }
}

function renderHeader(meData) {
    const me = meData.user;
    document.getElementById('headerEmail').textContent = me.email;
    const convites = meData.convites || [];
    const maxConvites = meData.max_convites || 10;
    const restantes = maxConvites - convites.length;
    const btnConvite = document.getElementById('btnConvite');
    if (restantes > 0) {
        btnConvite.textContent = 'Convide até ' + restantes + ' amigos';
        btnConvite.className = 'btn-invite btn-invite-active';
    } else {
        btnConvite.textContent = 'Sem convites';
        btnConvite.className = 'btn-invite btn-invite-empty';
    }
    // Admin link + badge
    if (me.is_admin) {
        const wrap = document.getElementById('profile-admin-link-wrap');
        if (wrap) wrap.style.display = 'inline';
        const denPend = meData.denunciasPendentes || 0;
        const adminLink = document.getElementById('profile-hdr-admin-link');
        if (adminLink) {
            adminLink.innerHTML = '⚙️ Admin' + (denPend > 0 ? ' <span class="hdr-badge" style="background:#cc0000;">' + denPend + '</span>' : '');
        }
    }
}

function calcIdade(nascimento) {
    if (!nascimento) return null;
    const nasc = new Date(nascimento);
    const hoje = new Date();
    let idade = hoje.getFullYear() - nasc.getFullYear();
    const m = hoje.getMonth() - nasc.getMonth();
    if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
    return idade;
}

function calcSigno(nascimento) {
    if (!nascimento) return null;
    const d = new Date(nascimento);
    const dia = d.getDate();
    const mes = d.getMonth() + 1;
    const signos = [
        { nome: 'Capricórnio', ini: [1,1], fim: [1,19] },
        { nome: 'Aquário', ini: [1,20], fim: [2,18] },
        { nome: 'Peixes', ini: [2,19], fim: [3,20] },
        { nome: 'Áries', ini: [3,21], fim: [4,19] },
        { nome: 'Touro', ini: [4,20], fim: [5,20] },
        { nome: 'Gêmeos', ini: [5,21], fim: [6,20] },
        { nome: 'Câncer', ini: [6,21], fim: [7,22] },
        { nome: 'Leão', ini: [7,23], fim: [8,22] },
        { nome: 'Virgem', ini: [8,23], fim: [9,22] },
        { nome: 'Libra', ini: [9,23], fim: [10,22] },
        { nome: 'Escorpião', ini: [10,23], fim: [11,21] },
        { nome: 'Sagitário', ini: [11,22], fim: [12,21] },
        { nome: 'Capricórnio', ini: [12,22], fim: [12,31] },
    ];
    for (const s of signos) {
        const [mi, di] = s.ini;
        const [mf, df] = s.fim;
        if ((mes === mi && dia >= di) || (mes === mf && dia <= df)) return s.nome;
    }
    return null;
}

// =============================================
// VISITOR PROFILE - replaces entire page content
// =============================================
function renderVisitorProfile(user, meData, visitData) {
    const uid = user.id;
    const idade = calcIdade(user.nascimento);
    const signo = calcSigno(user.nascimento);
    const totalRecados = (visitData && visitData.totalRecados) || 0;
    const totalFotos = (visitData && visitData.totalFotos) || 0;
    const totalVideos = (visitData && visitData.totalVideos) || 0;

    document.title = 'Yorkut - ' + user.nome;

    const fotoPerfil = user.foto_perfil || getDefaultAvatar(user.sexo);

    // Build detalhes text
    let detalhes = escapeHtml(user.nome);
    if (idade) detalhes += ', ' + idade + ' anos';
    if (user.estado_civil) detalhes += ', ' + escapeHtml(user.estado_civil);
    if (user.cidade) detalhes += ', ' + escapeHtml(user.cidade);
    if (user.estado) detalhes += ', ' + escapeHtml(user.estado);

    // === LEFT COLUMN (completely replaced) ===
    const leftCol = document.querySelector('.left-col');
    leftCol.innerHTML = `
        <div class="card-left">
            <div class="profile-pic" style="margin:0 auto 10px;">
                <img src="${fotoPerfil}" id="profileImage">
            </div>
            <div class="detalhes-usuario-centro">${detalhes}</div>

            <style>
                .menu-badge { background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 10px; margin-left: auto; display: inline-block; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2); animation: pulseBadgeLeft 1.5s infinite; }
                .menu-left li a { display: flex; align-items: center; width: 100%; box-sizing: border-box; }
                .menu-category { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #7992b5; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid var(--line); padding-left: 5px; letter-spacing: 0.5px; }
                .menu-category:first-child { margin-top: 5px; }
            </style>

            <ul class="menu-left hide-on-mobile">
                <div class="menu-category">Perfil</div>
                <li style="background-color:#eef4ff;"><a href="profile.php?uid=${uid}"><span>&#x1F464;</span> perfil</a></li>
                <li><a href="recados.php?uid=${uid}"><span>&#x1F4DD;</span> recados</a></li>
                <li><a href="fotos.php?uid=${uid}"><span>&#x1F4F7;</span> fotos</a></li>
                <li><a href="videos.php?uid=${uid}"><span>&#x1F3A5;</span> vídeos</a></li>
                <li><a href="mensagens_particular.php?to=${uid}"><span>&#x2709;&#xFE0F;</span> mensagens</a></li>
                <li><a href="depoimentos.php?uid=${uid}"><span>&#x1F31F;</span> depoimentos</a></li>
                <div class="menu-category">Jogos e Apps</div>
                <li><a href="colheita.php?uid=${uid}"><span>&#x1F33D;</span> colheita feliz</a></li>
                <li><a href="buddypoke.php?uid=${uid}"><span>&#x1FAC2;</span> buddy poke</a></li>
                <li><a href="cafemania.php?uid=${uid}"><span>&#x2615;</span> café mania</a></li>

                <li style="margin-top: 15px; border-top: 1px dashed var(--line); padding-top: 5px;"></li>
                <li id="friend-action-li">
                    <button type="button" onclick="adicionarAmigo('${uid}')" id="btn-friend-action" class="menu-btn-action">
                        <span>&#x2795;</span> adicionar amigo
                    </button>
                </li>
                <li>
                    <button type="button" onclick="toggleForm('reportModal')" class="menu-btn-action">
                        <span>&#x26A0;&#xFE0F;</span> denunciar
                    </button>
                </li>
                <li id="block-action-li">
                    <button type="button" onclick="bloquearUsuario('${uid}')" class="menu-btn-action" id="btn-block-action">
                        <span>&#x1F6AB;</span> bloquear
                    </button>
                </li>
            </ul>

            <div id="reportModal" class="modal-overlay">
                <div class="modal-box">
                    <h3>Denunciar Usuário</h3>
                    <p style="color:red; font-size:10px; margin-bottom: 10px;">Atenção: Falsas denúncias podem resultar no banimento da sua conta.</p>
                    <form onsubmit="return false;">
                        <textarea id="motivo-denuncia" name="motivo_denuncia" required placeholder="Motivo da denúncia..." style="min-height:80px;"></textarea>
                        <button type="button" onclick="enviarDenuncia()" class="btn-danger" style="margin-bottom: 5px; width: 100%;">Enviar Denúncia</button>
                        <button type="button" onclick="toggleForm('reportModal')" style="background:#fff; width: 100%; border:1px solid #ccc; padding:8px; cursor:pointer;">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>
    `;

    // === CENTER COLUMN (completely replaced) ===
    const centerCol = document.querySelector('.center-col');

    // Build social tab
    let socialHtml = '<table class="info-table">';
    socialHtml += '<tr><td colspan="2" style="background:#f4f7fc; text-align:center; padding:10px;"><a href="amigos.php?uid=' + uid + '" style="color:var(--link); font-weight:bold; font-size:14px; text-decoration:none;" id="social-amigos-link">&#x1F465; Amigos: <span id="social-amigos-count">0</span> (Ver todos)</a></td></tr>';
    if (user.estado_civil) socialHtml += "<tr><td class='info-label' style='width:35%;'>Relacionamento:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.estado_civil) + "</td></tr>";
    if (user.quem_sou_eu) socialHtml += "<tr><td class='info-label' style='width:35%;'>Quem sou eu:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.quem_sou_eu) + "</td></tr>";
    if (user.interesse_em) socialHtml += "<tr><td class='info-label'>Interesse em:</td><td class='info-value'>" + renderCommunityMentions(user.interesse_em) + "</td></tr>";
    if (user.whatsapp) socialHtml += "<tr><td class='info-label'>WhatsApp:</td><td class='info-value'><a href='https://wa.me/" + escapeHtml(user.whatsapp) + "' target='_blank' style='color:var(--orkut-pink); font-weight:bold;'>&#x1F4F1; WhatsApp (" + escapeHtml(user.whatsapp) + ")</a></td></tr>";
    if (user.interesses) socialHtml += "<tr><td class='info-label'>Interesses:</td><td class='info-value'>" + escapeHtml(user.interesses) + "</td></tr>";
    if (user.atividades) socialHtml += "<tr><td class='info-label'>Atividades:</td><td class='info-value'>" + escapeHtml(user.atividades) + "</td></tr>";
    if (user.musica) socialHtml += "<tr><td class='info-label'>Música:</td><td class='info-value'>" + escapeHtml(user.musica) + "</td></tr>";
    if (user.filmes) socialHtml += "<tr><td class='info-label'>Filmes:</td><td class='info-value'>" + escapeHtml(user.filmes) + "</td></tr>";
    if (user.tv) socialHtml += "<tr><td class='info-label'>Programas de TV:</td><td class='info-value'>" + escapeHtml(user.tv) + "</td></tr>";
    if (user.livros) socialHtml += "<tr><td class='info-label'>Livros:</td><td class='info-value'>" + escapeHtml(user.livros) + "</td></tr>";
    if (user.esportes) socialHtml += "<tr><td class='info-label'>Esportes:</td><td class='info-value'>" + escapeHtml(user.esportes) + "</td></tr>";
    if (user.atividades_favoritas) socialHtml += "<tr><td class='info-label'>Atividades favoritas:</td><td class='info-value'>" + escapeHtml(user.atividades_favoritas) + "</td></tr>";
    if (user.comidas) socialHtml += "<tr><td class='info-label'>Comidas:</td><td class='info-value'>" + escapeHtml(user.comidas) + "</td></tr>";
    if (user.herois) socialHtml += "<tr><td class='info-label'>Meus heróis:</td><td class='info-value'>" + escapeHtml(user.herois) + "</td></tr>";
    socialHtml += '</table>';

    // Build pessoal tab
    let pessoalHtml = '<table class="info-table">';
    if (user.apelido) pessoalHtml += "<tr><td class='info-label' style='width:35%;'>Apelido:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.apelido) + "</td></tr>";
    pessoalHtml += "<tr><td class='info-label' style='width:35%;'>Data de nascimento:</td><td class='info-value' style='width:65%;'>" + (user.nascimento ? new Date(user.nascimento).toLocaleDateString('pt-BR') : '-') + "</td></tr>";
    if (user.hora_nascimento) pessoalHtml += "<tr><td class='info-label'>Hora do nascimento:</td><td class='info-value'>" + escapeHtml(user.hora_nascimento) + "</td></tr>";
    pessoalHtml += "<tr><td class='info-label'>Idade:</td><td class='info-value'>" + (idade ? idade + ' anos' : '-') + "</td></tr>";
    if (signo) pessoalHtml += "<tr><td class='info-label'>Signo:</td><td class='info-value'>" + signo + "</td></tr>";
    if (user.cidade_natal) pessoalHtml += "<tr><td class='info-label'>Cidade natal:</td><td class='info-value'>" + escapeHtml(user.cidade_natal) + "</td></tr>";
    const loc = [user.cidade, user.estado, user.pais].filter(Boolean).map(v => escapeHtml(v)).join(', ') || 'Brasil';
    pessoalHtml += "<tr><td class='info-label'>Localização atual:</td><td class='info-value'>" + loc + "</td></tr>";
    if (user.sexo) pessoalHtml += "<tr><td class='info-label'>Sexo:</td><td class='info-value'>" + escapeHtml(user.sexo) + "</td></tr>";
    if (user.orientacao_sexual) pessoalHtml += "<tr><td class='info-label'>Orientação sexual:</td><td class='info-value'>" + escapeHtml(user.orientacao_sexual) + "</td></tr>";
    if (user.filhos) pessoalHtml += "<tr><td class='info-label'>Filhos:</td><td class='info-value'>" + escapeHtml(user.filhos) + "</td></tr>";
    if (user.altura) pessoalHtml += "<tr><td class='info-label'>Altura:</td><td class='info-value'>" + escapeHtml(user.altura) + " cm</td></tr>";
    if (user.tipo_fisico) pessoalHtml += "<tr><td class='info-label'>Tipo físico:</td><td class='info-value'>" + escapeHtml(user.tipo_fisico) + "</td></tr>";
    if (user.etnia) pessoalHtml += "<tr><td class='info-label'>Etnia:</td><td class='info-value'>" + escapeHtml(user.etnia) + "</td></tr>";
    if (user.religiao) pessoalHtml += "<tr><td class='info-label'>Religião:</td><td class='info-value'>" + escapeHtml(user.religiao) + "</td></tr>";
    if (user.humor) pessoalHtml += "<tr><td class='info-label'>Humor:</td><td class='info-value'>" + escapeHtml(user.humor) + "</td></tr>";
    if (user.estilo) pessoalHtml += "<tr><td class='info-label'>Estilo:</td><td class='info-value'>" + escapeHtml(user.estilo) + "</td></tr>";
    if (user.fumo) pessoalHtml += "<tr><td class='info-label'>Fuma:</td><td class='info-value'>" + escapeHtml(user.fumo) + "</td></tr>";
    if (user.bebo) pessoalHtml += "<tr><td class='info-label'>Bebe:</td><td class='info-value'>" + escapeHtml(user.bebo) + "</td></tr>";
    if (user.animais_estimacao) pessoalHtml += "<tr><td class='info-label'>Animais de estimação:</td><td class='info-value'>" + escapeHtml(user.animais_estimacao) + "</td></tr>";
    if (user.mora_com) pessoalHtml += "<tr><td class='info-label'>Com quem mora:</td><td class='info-value'>" + escapeHtml(user.mora_com) + "</td></tr>";
    pessoalHtml += '</table>';

    // Build profissional tab
    let profHtml = '<table class="info-table">';
    const temProf = user.escolaridade || user.universidade || user.curso || user.ocupacao || user.profissao || user.empresa;
    if (!temProf) {
        profHtml += '<tr><td colspan="2" style="color:#999; text-align:center; padding:15px;">Nenhuma informação profissional cadastrada.</td></tr>';
    } else {
        if (user.escolaridade) profHtml += "<tr><td class='info-label' style='width:35%;'>Escolaridade:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.escolaridade) + "</td></tr>";
        if (user.ensino_medio) profHtml += "<tr><td class='info-label'>Ensino médio:</td><td class='info-value'>" + escapeHtml(user.ensino_medio) + "</td></tr>";
        if (user.universidade) profHtml += "<tr><td class='info-label'>Universidade:</td><td class='info-value'>" + escapeHtml(user.universidade) + "</td></tr>";
        if (user.curso) profHtml += "<tr><td class='info-label'>Curso:</td><td class='info-value'>" + escapeHtml(user.curso) + "</td></tr>";
        if (user.ano_inicio) profHtml += "<tr><td class='info-label'>Ano de início:</td><td class='info-value'>" + escapeHtml(user.ano_inicio) + "</td></tr>";
        if (user.ano_conclusao_prof) profHtml += "<tr><td class='info-label'>Ano de conclusão:</td><td class='info-value'>" + escapeHtml(user.ano_conclusao_prof) + "</td></tr>";
        if (user.grau) profHtml += "<tr><td class='info-label'>Grau:</td><td class='info-value'>" + escapeHtml(user.grau) + "</td></tr>";
        if (user.ocupacao) profHtml += "<tr><td class='info-label'>Ocupação:</td><td class='info-value'>" + escapeHtml(user.ocupacao) + "</td></tr>";
        if (user.profissao) profHtml += "<tr><td class='info-label'>Profissão:</td><td class='info-value'>" + escapeHtml(user.profissao) + "</td></tr>";
        if (user.empresa) profHtml += "<tr><td class='info-label'>Empresa:</td><td class='info-value'>" + escapeHtml(user.empresa) + "</td></tr>";
        if (user.cargo) profHtml += "<tr><td class='info-label'>Cargo:</td><td class='info-value'>" + escapeHtml(user.cargo) + "</td></tr>";
        if (user.area_atuacao) profHtml += "<tr><td class='info-label'>Área de atuação:</td><td class='info-value'>" + escapeHtml(user.area_atuacao) + "</td></tr>";
    }
    profHtml += '</table>';

    const statusTexto = user.status_texto ? escapeHtml(user.status_texto) : 'Defina seu status aqui';

    centerCol.innerHTML = `
        <div class="breadcrumb"><a href="profile.php">Início</a> > Perfil de ${escapeHtml(user.nome)}</div>
        
        <div class="card">
            <h1 class="orkut-name" style="text-align:left;">${escapeHtml(user.nome)}</h1>
            <div class="status-text" style="text-align:left;">"${statusTexto}"</div>
            
            <div class="stats-container">
                <div class="stats-info">
                    <div class="stat-box">
                        <span class="stat-label">recados</span>
                        <a href="recados.php?uid=${uid}" class="stat-num" title="Ver recados">&#x1F4DD; ${totalRecados}</a>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">fotos</span>
                        <a href="fotos.php?uid=${uid}" class="stat-num" title="Ver fotos">&#x1F4F7; ${totalFotos}</a>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">vídeos</span>
                        <a href="videos.php?uid=${uid}" class="stat-num" title="Ver vídeos">&#x1F3A5; ${totalVideos}</a>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">fãs</span>
                        <button type="button" class="stat-num btn-fan" id="btn-fan-visitor" style="border:none;background:none;padding:0;cursor:pointer;" title="Virar fã" onclick="toggleFan('${uid}')">&#x2B50; <span id="fan-count-visitor">0</span></button>
                    </div>
                </div>
                
                <div class="trust-row">
                    <div class="trust-item">
                        <span class="trust-label">confiável</span>
                        <div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="confiavel" data-uid="${uid}">
                            <div class="trust-base">&#x1F60A;&#x1F60A;&#x1F60A;</div>
                            <div class="trust-fill" style="width: 0%;">&#x1F60A;&#x1F60A;&#x1F60A;</div>
                            <div class="trust-tooltip">Dar nota!</div>
                        </div>
                    </div>
                    <div class="trust-item">
                        <span class="trust-label">legal</span>
                        <div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="legal" data-uid="${uid}">
                            <div class="trust-base">&#x1F9CA;&#x1F9CA;&#x1F9CA;</div>
                            <div class="trust-fill" style="width: 0%;">&#x1F9CA;&#x1F9CA;&#x1F9CA;</div>
                            <div class="trust-tooltip">Dar nota!</div>
                        </div>
                    </div>
                    <div class="trust-item">
                        <span class="trust-label">sexy</span>
                        <div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="sexy" data-uid="${uid}">
                            <div class="trust-base">&#x2764;&#xFE0F;&#x2764;&#xFE0F;&#x2764;&#xFE0F;</div>
                            <div class="trust-fill" style="width: 0%;">&#x2764;&#xFE0F;&#x2764;&#xFE0F;&#x2764;&#xFE0F;</div>
                            <div class="trust-tooltip">Dar nota!</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tabs-nav">
                <div class="tab-btn active" id="btn-social" onclick="showTab('social')">social</div>
                <div class="tab-btn" id="btn-pessoal" onclick="showTab('pessoal')">pessoal</div>
                <div class="tab-btn" id="btn-profissional" onclick="showTab('profissional')">profissional</div>
            </div>

            <div class="card" style="padding: 10px; border-top: none; border-radius: 0 0 4px 4px; margin-top: -10px;">
                <div id="tab-social" class="tab-content active">${socialHtml}</div>
                <div id="tab-pessoal" class="tab-content">${pessoalHtml}</div>
                <div id="tab-profissional" class="tab-content">${profHtml}</div>
            </div>
        </div>
        <div id="profile-depoimentos-section"></div>
    `;

    // === RIGHT COLUMN (completely replaced) ===
    const rightCol = document.querySelector('.right-col');
    rightCol.innerHTML = `
        <div class="box-sidebar" id="box-amigos-visitor">
            <div class="box-title"><span id="visitor-amigos-title">amigos (0)</span> <a href="amigos.php?uid=${uid}">ver todos</a></div>
            <div class="grid" id="visitor-amigos-grid">
                <div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Carregando...</div>
            </div>
        </div>
        <div class="box-sidebar" id="box-comunidades-visitor">
            <div class="box-title"><span id="visitor-comunidades-title">comunidades (0)</span> <a href="user_comunidades.php?uid=${uid}">ver todas</a></div>
            <div class="grid" id="visitor-comunidades-grid">
                <div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Carregando...</div>
            </div>
        </div>
    `;
    // Visitantes não aparecem no perfil de visitante (só no próprio)

    // Load friends for this user
    loadAmigos(uid, true);

    // Load communities for this user
    loadUserComunidades(uid, true);

    // Check friendship status and update button
    checkFriendshipStatus(uid);

    // Setup interactive trust handlers
    setupTrustHandlers();

    // Load trust ratings
    loadTrustRatings(uid);

    // Load fan count
    loadFanCount(uid, true);

    // Load depoimentos section
    loadProfileDepoimentos(uid, false);
}

// =============================================
// OWN PROFILE - updates existing DOM elements
// =============================================
function renderOwnProfile(user, meData) {
    const idade = calcIdade(user.nascimento);

    document.title = 'Yorkut - ' + user.nome;

    // Load fan count for own profile
    loadFanCount(user.id, false);

    // Update stats counts (recados, fotos, vídeos)
    const ownRecados = meData.totalRecados || 0;
    const ownFotos = meData.totalFotos || 0;
    const ownVideos = meData.totalVideos || 0;
    const elR = document.getElementById('own-stat-recados');
    if (elR) elR.innerHTML = '&#x1F4DD; ' + ownRecados;
    const elF = document.getElementById('own-stat-fotos');
    if (elF) elF.innerHTML = '&#x1F4F7; ' + ownFotos;
    const elV = document.getElementById('own-stat-videos');
    if (elV) elV.innerHTML = '&#x1F3A5; ' + ownVideos;

    // Carregar sorte do dia
    fetch('/api/sorte').then(r => r.json()).then(d => {
        if (d.sorte) {
            const el = document.getElementById('sorteTexto');
            if (el) el.textContent = d.sorte;
        }
    }).catch(() => {});

    // Update static elements
    document.getElementById('userName').textContent = user.nome;
    document.getElementById('userNameInput').value = user.nome;
    // breadcrumb stays as 'Meu Perfil' for own profile

    // Photo - always set based on what user has
    const fotoFinal = (user.foto_perfil && !user.foto_perfil.includes('default-avatar'))
        ? user.foto_perfil
        : getDefaultAvatar(user.sexo);
    document.getElementById('profileImage').src = fotoFinal;

    // User details (left column)
    let detalhes = user.nome;
    if (idade) detalhes += ', ' + idade + ' anos';
    if (user.estado_civil) detalhes += ', ' + user.estado_civil;
    if (user.cidade) detalhes += ', ' + user.cidade;
    if (user.estado) detalhes += ', ' + user.estado;
    document.getElementById('userDetails').textContent = detalhes;

    // Status
    const statusEl = document.querySelector('.status-text');
    if (statusEl && user.status_texto) {
        const editBtn = statusEl.querySelector('.edit-link');
        statusEl.innerHTML = '"' + escapeHtml(user.status_texto) + '" ';
        if (editBtn) statusEl.appendChild(editBtn);
    }
    document.getElementById('statusInput').value = user.status_texto || '';

    // Fill the edit form
    preencherFormulario(user);

    // Social tab
    const socialTab = document.getElementById('tab-social');
    if (socialTab) {
        let socialHtml = '<table class="info-table">';
        socialHtml += '<tr><td colspan="2" style="background:#f4f7fc; text-align:center; padding:10px;"><a href="amigos.php" style="color:var(--link); font-weight:bold; font-size:14px; text-decoration:none;" id="social-amigos-link">&#x1F465; Amigos: <span id="social-amigos-count">0</span> (Ver todos)</a></td></tr>';
        if (user.estado_civil) socialHtml += "<tr><td class='info-label'>Relacionamento:</td><td class='info-value'>" + escapeHtml(user.estado_civil) + "</td></tr>";
        if (user.quem_sou_eu) socialHtml += "<tr><td class='info-label' style='width:35%;'>Quem sou eu:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.quem_sou_eu) + "</td></tr>";
        if (user.interesse_em) socialHtml += "<tr><td class='info-label'>Interesse em:</td><td class='info-value'>" + renderCommunityMentions(user.interesse_em) + "</td></tr>";
        if (user.whatsapp) socialHtml += "<tr><td class='info-label'>WhatsApp:</td><td class='info-value'><a href='https://wa.me/" + escapeHtml(user.whatsapp) + "' target='_blank' style='color:var(--orkut-pink); font-weight:bold;'>&#x1F4F1; WhatsApp (" + escapeHtml(user.whatsapp) + ")</a></td></tr>";
        if (user.interesses) socialHtml += "<tr><td class='info-label'>Interesses:</td><td class='info-value'>" + escapeHtml(user.interesses) + "</td></tr>";
        if (user.atividades) socialHtml += "<tr><td class='info-label'>Atividades:</td><td class='info-value'>" + escapeHtml(user.atividades) + "</td></tr>";
        if (user.musica) socialHtml += "<tr><td class='info-label'>Música:</td><td class='info-value'>" + escapeHtml(user.musica) + "</td></tr>";
        if (user.filmes) socialHtml += "<tr><td class='info-label'>Filmes:</td><td class='info-value'>" + escapeHtml(user.filmes) + "</td></tr>";
        if (user.tv) socialHtml += "<tr><td class='info-label'>Programas de TV:</td><td class='info-value'>" + escapeHtml(user.tv) + "</td></tr>";
        if (user.livros) socialHtml += "<tr><td class='info-label'>Livros:</td><td class='info-value'>" + escapeHtml(user.livros) + "</td></tr>";
        if (user.esportes) socialHtml += "<tr><td class='info-label'>Esportes:</td><td class='info-value'>" + escapeHtml(user.esportes) + "</td></tr>";
        if (user.atividades_favoritas) socialHtml += "<tr><td class='info-label'>Atividades favoritas:</td><td class='info-value'>" + escapeHtml(user.atividades_favoritas) + "</td></tr>";
        if (user.comidas) socialHtml += "<tr><td class='info-label'>Comidas:</td><td class='info-value'>" + escapeHtml(user.comidas) + "</td></tr>";
        if (user.herois) socialHtml += "<tr><td class='info-label'>Meus heróis:</td><td class='info-value'>" + escapeHtml(user.herois) + "</td></tr>";
        socialHtml += '</table>';
        socialTab.innerHTML = socialHtml;
    }

    // Pessoal tab
    const pessoalTab = document.getElementById('tab-pessoal');
    if (pessoalTab) {
        let pessoalHtml = '<table class="info-table">';
        if (user.apelido) pessoalHtml += "<tr><td class='info-label' style='width:35%;'>Apelido:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.apelido) + "</td></tr>";
        pessoalHtml += "<tr><td class='info-label' style='width:35%;'>Data de nascimento:</td><td class='info-value' style='width:65%;'>" + (user.nascimento ? new Date(user.nascimento).toLocaleDateString('pt-BR') : '-') + "</td></tr>";
        if (user.hora_nascimento) pessoalHtml += "<tr><td class='info-label'>Hora do nascimento:</td><td class='info-value'>" + escapeHtml(user.hora_nascimento) + "</td></tr>";
        pessoalHtml += "<tr><td class='info-label'>Idade:</td><td class='info-value'>" + (idade ? idade + ' anos' : '-') + "</td></tr>";
        const signo = calcSigno(user.nascimento);
        if (signo) pessoalHtml += "<tr><td class='info-label'>Signo:</td><td class='info-value'>" + signo + "</td></tr>";
        if (user.cidade_natal) pessoalHtml += "<tr><td class='info-label'>Cidade natal:</td><td class='info-value'>" + escapeHtml(user.cidade_natal) + "</td></tr>";
        const loc = [user.cidade, user.estado, user.pais].filter(Boolean).map(v => escapeHtml(v)).join(', ') || 'Brasil';
        pessoalHtml += "<tr><td class='info-label'>Localização atual:</td><td class='info-value'>" + loc + "</td></tr>";
        if (user.sexo) pessoalHtml += "<tr><td class='info-label'>Sexo:</td><td class='info-value'>" + escapeHtml(user.sexo) + "</td></tr>";
        if (user.orientacao_sexual) pessoalHtml += "<tr><td class='info-label'>Orientação sexual:</td><td class='info-value'>" + escapeHtml(user.orientacao_sexual) + "</td></tr>";
        if (user.filhos) pessoalHtml += "<tr><td class='info-label'>Filhos:</td><td class='info-value'>" + escapeHtml(user.filhos) + "</td></tr>";
        if (user.altura) pessoalHtml += "<tr><td class='info-label'>Altura:</td><td class='info-value'>" + escapeHtml(user.altura) + " cm</td></tr>";
        if (user.tipo_fisico) pessoalHtml += "<tr><td class='info-label'>Tipo físico:</td><td class='info-value'>" + escapeHtml(user.tipo_fisico) + "</td></tr>";
        if (user.etnia) pessoalHtml += "<tr><td class='info-label'>Etnia:</td><td class='info-value'>" + escapeHtml(user.etnia) + "</td></tr>";
        if (user.religiao) pessoalHtml += "<tr><td class='info-label'>Religião:</td><td class='info-value'>" + escapeHtml(user.religiao) + "</td></tr>";
        if (user.humor) pessoalHtml += "<tr><td class='info-label'>Humor:</td><td class='info-value'>" + escapeHtml(user.humor) + "</td></tr>";
        if (user.estilo) pessoalHtml += "<tr><td class='info-label'>Estilo:</td><td class='info-value'>" + escapeHtml(user.estilo) + "</td></tr>";
        if (user.fumo) pessoalHtml += "<tr><td class='info-label'>Fuma:</td><td class='info-value'>" + escapeHtml(user.fumo) + "</td></tr>";
        if (user.bebo) pessoalHtml += "<tr><td class='info-label'>Bebe:</td><td class='info-value'>" + escapeHtml(user.bebo) + "</td></tr>";
        if (user.animais_estimacao) pessoalHtml += "<tr><td class='info-label'>Animais de estimação:</td><td class='info-value'>" + escapeHtml(user.animais_estimacao) + "</td></tr>";
        if (user.mora_com) pessoalHtml += "<tr><td class='info-label'>Com quem mora:</td><td class='info-value'>" + escapeHtml(user.mora_com) + "</td></tr>";
        pessoalHtml += '</table>';
        pessoalTab.innerHTML = pessoalHtml;
    }

    // Profissional tab
    const profTab = document.getElementById('tab-profissional');
    if (profTab) {
        const temProf = user.escolaridade || user.universidade || user.curso || user.ocupacao || user.profissao || user.empresa;
        let profHtml = '<table class="info-table">';
        if (!temProf) {
            profHtml += '<tr><td colspan="2" style="color:#999; text-align:center; padding:15px;">Nenhuma informação profissional cadastrada.</td></tr>';
        } else {
            if (user.escolaridade) profHtml += "<tr><td class='info-label' style='width:35%;'>Escolaridade:</td><td class='info-value' style='width:65%;'>" + escapeHtml(user.escolaridade) + "</td></tr>";
            if (user.ensino_medio) profHtml += "<tr><td class='info-label'>Ensino médio:</td><td class='info-value'>" + escapeHtml(user.ensino_medio) + "</td></tr>";
            if (user.universidade) profHtml += "<tr><td class='info-label'>Universidade:</td><td class='info-value'>" + escapeHtml(user.universidade) + "</td></tr>";
            if (user.curso) profHtml += "<tr><td class='info-label'>Curso:</td><td class='info-value'>" + escapeHtml(user.curso) + "</td></tr>";
            if (user.ano_inicio) profHtml += "<tr><td class='info-label'>Ano de início:</td><td class='info-value'>" + escapeHtml(user.ano_inicio) + "</td></tr>";
            if (user.ano_conclusao_prof) profHtml += "<tr><td class='info-label'>Ano de conclusão:</td><td class='info-value'>" + escapeHtml(user.ano_conclusao_prof) + "</td></tr>";
            if (user.grau) profHtml += "<tr><td class='info-label'>Grau:</td><td class='info-value'>" + escapeHtml(user.grau) + "</td></tr>";
            if (user.ocupacao) profHtml += "<tr><td class='info-label'>Ocupação:</td><td class='info-value'>" + escapeHtml(user.ocupacao) + "</td></tr>";
            if (user.profissao) profHtml += "<tr><td class='info-label'>Profissão:</td><td class='info-value'>" + escapeHtml(user.profissao) + "</td></tr>";
            if (user.empresa) profHtml += "<tr><td class='info-label'>Empresa:</td><td class='info-value'>" + escapeHtml(user.empresa) + "</td></tr>";
            if (user.cargo) profHtml += "<tr><td class='info-label'>Cargo:</td><td class='info-value'>" + escapeHtml(user.cargo) + "</td></tr>";
            if (user.area_atuacao) profHtml += "<tr><td class='info-label'>Área de atuação:</td><td class='info-value'>" + escapeHtml(user.area_atuacao) + "</td></tr>";
        }
        profHtml += '</table>';
        profTab.innerHTML = profHtml;
    }

    // Integrity bar
    atualizarIntegridade(user);

    // Trust handlers (already in DOM)
    setupTrustHandlers();

    // Load trust ratings for own profile
    loadTrustRatings(user.id);

    // Load depoimentos section
    loadProfileDepoimentos(user.id, true);

    // Load visitantes
    loadVisitantes(user.id);

    // Load friends on the sidebar
    loadAmigos(user.id, false);

    // Load communities on the sidebar
    loadUserComunidades(user.id, false);
}

// =============================================
// CARREGAR VISITANTES
// =============================================
async function loadVisitantes(uid) {
    try {
        const resp = await fetch('/api/visitas/' + uid);
        const data = await resp.json();
        const grid = document.getElementById('visitantes-grid');
        const box = document.getElementById('box-visitantes');
        if (!grid || !box) return;

        if (!data.success || data.rastro_desativado) {
            grid.innerHTML = '<div style="grid-column:1/-1;padding:15px 10px;color:#666;text-align:center;font-size:11px;line-height:1.4;background:#f9f9f9;border:1px dashed #ccc;border-radius:4px;">Você desativou o rastro. Ative nas <a href=\'configuracoes.php\' style=\'color:var(--link);font-weight:bold;\'>Configurações</a> para ver seus visitantes.</div>';
            return;
        }

        if (!data.visitas || data.visitas.length === 0) {
            grid.innerHTML = '<div class="visitante-quadrado"><span class="visitante-sem-foto">Nenhuma visita ainda</span></div>';
            return;
        }

        let html = '';
        data.visitas.forEach(v => {
            const fotoSrc = v.foto_perfil || getDefaultAvatar(v.sexo);
            const titulo = `${escapeHtml(v.nome)} visitou voc\u00ea dia ${v.data}`;
            html += `<a href="profile.php?uid=${v.visitante_id}" title="${titulo}" class="visitante-quadrado"><img src="${fotoSrc}" alt="Foto"></a>`;
        });
        grid.innerHTML = html;
    } catch(err) {
        console.error('Erro ao carregar visitantes:', err);
    }
}

// =============================================
// CARREGAR COMUNIDADES (sidebar)
// =============================================
async function loadUserComunidades(uid, isVisitor) {
    try {
        const resp = await fetch('/api/user-comunidades/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const total = data.total || 0;
        const allComms = [...(data.owned || []), ...(data.joined || [])];

        if (isVisitor) {
            const titleEl = document.getElementById('visitor-comunidades-title');
            if (titleEl) titleEl.textContent = 'comunidades (' + total + ')';

            const gridEl = document.getElementById('visitor-comunidades-grid');
            if (gridEl) {
                if (total === 0) {
                    gridEl.innerHTML = '<div class="grid-item" style="grid-column:1/-1;text-align:center;color:#999;padding:15px;">Nenhuma comunidade.</div>';
                } else {
                    let html = '';
                    allComms.slice(0, 9).forEach(c => {
                        const foto = c.foto || '/semfotocomunidade.jpg';
                        const nome = c.nome.length > 10 ? c.nome.substring(0, 10).trim() + '..' : c.nome;
                        html += '<div class="grid-item"><a href="comunidades.php?id=' + c.id + '"><div class="grid-thumb" style="aspect-ratio:3/4;height:auto;overflow:hidden;"><img src="' + foto + '" style="width:100%;height:100%;object-fit:cover;"></div>' + escapeHtml(nome) + '</a></div>';
                    });
                    gridEl.innerHTML = html;
                }
            }
        } else {
            const titleEl = document.getElementById('own-comunidades-title');
            if (titleEl) titleEl.textContent = 'minhas comunidades (' + total + ')';

            const gridEl = document.getElementById('own-comunidades-grid');
            if (gridEl) {
                if (total === 0) {
                    gridEl.innerHTML = '<div class="grid-item" style="grid-column:1/-1;text-align:center;color:#999;padding:15px;">Nenhuma comunidade.</div>';
                } else {
                    let html = '';
                    allComms.slice(0, 9).forEach(c => {
                        const foto = c.foto || '/semfotocomunidade.jpg';
                        const nome = c.nome.length > 10 ? c.nome.substring(0, 10).trim() + '..' : c.nome;
                        html += '<div class="grid-item"><a href="comunidades.php?id=' + c.id + '"><div class="grid-thumb" style="aspect-ratio:3/4;height:auto;overflow:hidden;"><img src="' + foto + '" style="width:100%;height:100%;object-fit:cover;"></div>' + escapeHtml(nome) + '</a></div>';
                    });
                    gridEl.innerHTML = html;
                }
            }
        }
    } catch(err) {
        console.error('Erro ao carregar comunidades:', err);
    }
}

// =============================================
// CARREGAR AMIGOS (sidebar)
// =============================================
async function loadAmigos(uid, isVisitor) {
    try {
        const resp = await fetch('/api/amigos/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const total = data.total || 0;

        // Update all friend count displays
        const socialCount = document.getElementById('social-amigos-count');
        if (socialCount) socialCount.textContent = total;

        if (isVisitor) {
            // Visitor profile
            const titleEl = document.getElementById('visitor-amigos-title');
            if (titleEl) titleEl.textContent = 'amigos (' + total + ')';

            const gridEl = document.getElementById('visitor-amigos-grid');
            if (gridEl) {
                if (total === 0) {
                    gridEl.innerHTML = '<div class="grid-item" style="grid-column:1/-1;text-align:center;color:#999;padding:15px;">Nenhum amigo ainda.</div>';
                } else {
                    let html = '';
                    data.amigos.slice(0, 9).forEach(a => {
                        const foto = a.foto_perfil || getDefaultAvatar(a.sexo);
                        const nome = a.nome.length > 10 ? a.nome.substring(0, 10).trim() + '..' : a.nome;
                        html += '<div class="grid-item"><a href="profile.php?uid=' + a.id + '"><div class="grid-thumb"><img src="' + foto + '"></div>' + escapeHtml(nome) + '</a></div>';
                    });
                    gridEl.innerHTML = html;
                }
            }
        } else {
            // Own profile sidebar
            const ownTitle = document.querySelector('#box-amigos-own .box-title');
            if (ownTitle) ownTitle.innerHTML = 'meus amigos (' + total + ') <a href="amigos.php">ver todos</a>';

            const ownGrid = document.querySelector('#box-amigos-own .grid');
            if (ownGrid) {
                if (total === 0) {
                    ownGrid.innerHTML = '<div class="grid-item" style="grid-column:1/-1;text-align:center;color:#999;padding:15px;">Nenhum amigo ainda. Convide seus amigos!</div>';
                } else {
                    let html = '';
                    data.amigos.slice(0, 9).forEach(a => {
                        const foto = a.foto_perfil || getDefaultAvatar(a.sexo);
                        const nome = a.nome.length > 10 ? a.nome.substring(0, 10).trim() + '..' : a.nome;
                        html += '<div class="grid-item"><a href="profile.php?uid=' + a.id + '"><div class="grid-thumb"><img src="' + foto + '"></div>' + escapeHtml(nome) + '</a></div>';
                    });
                    ownGrid.innerHTML = html;
                }
            }
        }
    } catch(err) {
        console.error('Erro ao carregar amigos:', err);
    }
}

// =============================================
// AMIZADE: Ações
// =============================================
async function checkFriendshipStatus(uid) {
    try {
        const resp = await fetch('/api/amizade/status/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const li = document.getElementById('friend-action-li');
        if (!li) return;

        if (data.status === 'amigos') {
            li.innerHTML = '<button type="button" onclick="desfazerAmizade(\'' + uid + '\')" class="menu-btn-action"><span>💔</span> desfazer amizade</button>';
        } else if (data.status === 'enviada') {
            li.innerHTML = '<button type="button" onclick="cancelarSolicitacao(\'' + uid + '\')" class="menu-btn-action" style="color:#999;"><span>⏳</span> solicitação enviada</button>';
        } else if (data.status === 'recebida') {
            li.innerHTML = '<button type="button" onclick="aceitarAmizade(' + data.request_id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>✅</span> aceitar amizade</button>';
        } else {
            li.innerHTML = '<button type="button" onclick="adicionarAmigo(\'' + uid + '\')" id="btn-friend-action" class="menu-btn-action"><span>➕</span> adicionar amigo</button>';
        }
    } catch(err) {
        console.error('Erro ao verificar amizade:', err);
    }

    // Check block status
    try {
        const bResp = await fetch('/api/bloqueio/status/' + uid);
        const bData = await bResp.json();
        const blockLi = document.getElementById('block-action-li');
        if (blockLi && bData.success && bData.i_blocked) {
            blockLi.innerHTML = '<button type="button" onclick="desbloquearUsuario(\'' + uid + '\')" class="menu-btn-action" style="color:#c00;"><span>🔓</span> desbloquear</button>';
        }
    } catch(err) {
        console.error('Erro ao verificar bloqueio:', err);
    }
}

async function enviarDenuncia() {
    const motivo = document.getElementById('motivo-denuncia').value.trim();
    if (!motivo) { alert('Preencha o motivo da denúncia.'); return; }
    try {
        const resp = await fetch('/api/denunciar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ denunciado_id: _visitingUid, motivo })
        });
        const data = await resp.json();
        if (data.success) {
            window.location.href = '/configuracoes.php?denuncias=1' + (data.denunciaId ? '&did=' + data.denunciaId : '');
        } else {
            alert(data.message || 'Erro ao enviar denúncia.');
        }
    } catch(err) {
        console.error('Erro ao denunciar:', err);
        alert('Erro ao enviar denúncia.');
    }
}

async function bloquearUsuario(uid) {
    showConfirm('ATENÇÃO: Ao bloquear este usuário, a amizade será desfeita e vocês não poderão mais se encontrar. Continuar?', async function() {
        try {
            const resp = await fetch('/api/bloquear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bloqueado_id: uid })
            });
            const data = await resp.json();
            if (data.success) {
                window.location.href = '/configuracoes.php?bloqueados=1&uid=' + uid;
            } else {
                alert(data.message || 'Erro ao bloquear.');
            }
        } catch(err) {
            console.error('Erro ao bloquear:', err);
            alert('Erro ao bloquear usuário.');
        }
    });
}

async function desbloquearUsuario(uid) {
    showConfirm('Deseja desbloquear este usuário?', async function() {
        try {
            const resp = await fetch('/api/desbloquear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bloqueado_id: uid })
            });
            const data = await resp.json();
            if (data.success) {
                alert('Usuário desbloqueado.');
                location.reload();
            } else {
                alert(data.message || 'Erro ao desbloquear.');
            }
        } catch(err) {
            console.error('Erro ao desbloquear:', err);
            alert('Erro ao desbloquear.');
        }
    });
}

async function adicionarAmigo(uid) {
    try {
        const resp = await fetch('/api/amizade/solicitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ destinatario_id: uid })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Solicitação de amizade enviada!');
            checkFriendshipStatus(uid);
        } else {
            alert(data.message || 'Erro ao enviar solicitação.');
        }
    } catch(err) {
        alert('Erro ao enviar solicitação.');
    }
}

// ===== FÃS =====
async function loadFanCount(uid, isVisitor) {
    try {
        const resp = await fetch('/api/fas/' + uid);
        const data = await resp.json();
        if (!data.success) return;
        if (isVisitor) {
            const el = document.getElementById('fan-count-visitor');
            if (el) el.textContent = data.count;
            const btn = document.getElementById('btn-fan-visitor');
            if (btn) {
                btn.title = data.isFan ? 'Deixar de ser fã' : 'Virar fã';
                btn.style.color = data.isFan ? '#e74c3c' : '';
            }
        } else {
            const el = document.getElementById('fan-count-own');
            if (el) el.innerHTML = '&#x2B50; ' + data.count;
        }
    } catch(e) {}
}

async function toggleFan(uid) {
    try {
        const resp = await fetch('/api/fas/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: uid })
        });
        const data = await resp.json();
        if (data.success) {
            const el = document.getElementById('fan-count-visitor');
            if (el) el.textContent = data.count;
            const btn = document.getElementById('btn-fan-visitor');
            if (btn) {
                btn.title = data.isFan ? 'Deixar de ser fã' : 'Virar fã';
                btn.style.color = data.isFan ? '#e74c3c' : '';
            }
            if (typeof showToast === 'function') showToast(data.message);
        } else {
            alert(data.message || 'Erro.');
        }
    } catch(e) {
        alert('Erro ao processar.');
    }
}

async function desfazerAmizade(uid) {
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
                checkFriendshipStatus(uid);
                loadAmigos(uid, true);
            } else {
                alert(data.message || 'Erro ao desfazer amizade.');
            }
        } catch(err) {
            alert('Erro ao desfazer amizade.');
        }
    });
}

async function cancelarSolicitacao(uid) {
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
                checkFriendshipStatus(uid);
            } else {
                alert(data.message || 'Erro.');
            }
        } catch(err) {
            alert('Erro ao cancelar solicitação.');
        }
    });
}

async function aceitarAmizade(requestId) {
    try {
        const resp = await fetch('/api/amizade/aceitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Amizade aceita!');
            // Reload the page to reflect changes
            window.location.reload();
        } else {
            alert(data.message || 'Erro.');
        }
    } catch(err) {
        alert('Erro ao aceitar amizade.');
    }
}

// =============================================
// HEADER: Load pending friend requests dropdown
// =============================================
async function profileLoadHeaderRequests() {
    try {
        const resp = await fetch('/api/amizade/pendentes');
        const data = await resp.json();
        if (!data.success) return;

        const total = data.total || 0;

        // Update badge
        const linkEl = document.getElementById('profile-hdr-reqs-link');
        if (linkEl) {
            linkEl.innerHTML = 'Solicitações' + (total > 0 ? ' <span class="hdr-badge" id="hdr-req-badge">' + total + '</span>' : '');
        }

        const contentEl = document.getElementById('profile-drop-reqs-content');
        if (!contentEl) return;

        if (total === 0) {
            contentEl.innerHTML = '<div style="padding:20px; color:#999; text-align:center; font-size:11px;">Nenhuma solicitação pendente.</div>';
            return;
        }

        let html = '';
        data.pendentes.forEach(function(p) {
            const foto = p.foto_perfil || getDefaultAvatar(p.sexo);
            html += '<div class="hdr-drop-item" id="profile-hdr-req-' + p.request_id + '">';
            html += '<div class="hdr-drop-pic"><a href="profile.php?uid=' + p.remetente_id + '"><img src="' + foto + '"></a></div>';
            html += '<div style="flex:1;">';
            html += '<b><a href="profile.php?uid=' + p.remetente_id + '" style="color:var(--link);">' + escapeHtml(p.nome) + '</a></b>';
            html += '<div style="margin-top:5px;display:flex;gap:5px;">';
            html += '<button type="button" onclick="profileHdrAcceptReq(' + p.request_id + ')" class="btn-req accept">Aprovar</button>';
            html += '<button type="button" onclick="profileHdrRejectReq(' + p.request_id + ')" class="btn-req">Recusar</button>';
            html += '</div></div></div>';
        });
        contentEl.innerHTML = html;
    } catch(err) {}
}

async function profileHdrAcceptReq(reqId) {
    try {
        await fetch('/api/amizade/aceitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: reqId })
        });
        const item = document.getElementById('profile-hdr-req-' + reqId);
        if (item) item.style.display = 'none';
        const badge = document.getElementById('hdr-req-badge');
        if (badge) {
            const c = parseInt(badge.innerText) - 1;
            if (c > 0) { badge.innerText = c; } else { badge.style.display = 'none'; }
        }
        profileLoadHeaderRequests();
    } catch(err) {}
}

async function profileHdrRejectReq(reqId) {
    try {
        await fetch('/api/amizade/recusar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: reqId })
        });
        const item = document.getElementById('profile-hdr-req-' + reqId);
        if (item) item.style.display = 'none';
        const badge = document.getElementById('hdr-req-badge');
        if (badge) {
            const c = parseInt(badge.innerText) - 1;
            if (c > 0) { badge.innerText = c; } else { badge.style.display = 'none'; }
        }
        profileLoadHeaderRequests();
    } catch(err) {}
}

let _profileNotifsLoaded = false;
async function profileLoadHeaderNotificacoes() {
    try {
        const resp = await fetch('/api/notificacoes');
        const data = await resp.json();
        if (!data.success) return;

        const contentEl = document.getElementById('profile-drop-notifs-content');
        if (!contentEl) return;

        if (!data.notificacoes || data.notificacoes.length === 0) {
            contentEl.innerHTML = '<div style="padding:20px; color:#999; text-align:center; font-size:11px;">Nenhuma nova notificação.</div>';
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

        const dropNotifs = document.getElementById('drop-notifs');
        const dropdownVisible = dropNotifs && dropNotifs.style.display !== 'none' && dropNotifs.style.display !== '';
        if (data.naoLidas > 0 && dropdownVisible && !_profileNotifsLoaded) {
            _profileNotifsLoaded = true;
            setTimeout(async () => {
                try {
                    await fetch('/api/notificacoes/marcar-lidas', { method: 'POST' });
                    const badge = document.getElementById('profile-hdr-notif-badge');
                    if (badge) badge.style.display = 'none';
                } catch(e) {}
            }, 2000);
        }
    } catch(err) {}
}

function setupTrustHandlers() {
    document.querySelectorAll('.interactive-trust').forEach(wrapper => {
        const fill = wrapper.querySelector('.trust-fill');
        const tooltip = wrapper.querySelector('.trust-tooltip');
        const uid = wrapper.dataset.uid;
        const category = wrapper.dataset.category;
        const isOwn = (uid === 'own');

        // Own profile: no interaction, just display
        if (isOwn) {
            wrapper.style.cursor = 'default';
            return;
        }

        function getDefaultPercent() {
            return parseFloat(wrapper.dataset.percent) || 0;
        }

        wrapper.addEventListener('mousemove', (e) => {
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left;
            let vote = Math.ceil(((x / rect.width) * 100) / 20);
            if(vote < 1) vote = 1;
            if(vote > 5) vote = 5;
            fill.style.width = (vote * 20) + '%';
            tooltip.innerHTML = 'Dar nota: <b>' + vote + '</b>';
        });

        wrapper.addEventListener('mouseleave', () => {
            fill.style.width = getDefaultPercent() + '%';
            const media = wrapper.dataset.media || '0';
            const total = wrapper.dataset.total || '0';
            const minha = wrapper.dataset.minha || '0';
            tooltip.innerHTML = 'Média: ' + media + ' (' + total + ' votos)' + (minha > 0 ? ' · Sua: ' + minha : '');
        });

        wrapper.addEventListener('click', async (e) => {
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left;
            let vote = Math.ceil(((x / rect.width) * 100) / 20);
            if(vote < 1) vote = 1;
            if(vote > 5) vote = 5;

            try {
                const resp = await fetch('/api/avaliacoes-perfil', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario_id: uid, categoria: category, nota: vote })
                });
                const data = await resp.json();
                if (data.success) {
                    const pct = (data.media / 5) * 100;
                    wrapper.dataset.percent = pct;
                    wrapper.dataset.media = data.media;
                    wrapper.dataset.total = data.total;
                    wrapper.dataset.minha = data.minhaNota;
                    fill.style.width = pct + '%';
                    tooltip.innerHTML = 'Média: ' + data.media + ' (' + data.total + ' votos) · Sua: ' + data.minhaNota;
                    if (typeof showToast === 'function') showToast(data.message);
                } else {
                    alert(data.message || 'Erro.');
                }
            } catch(err) {
                alert('Erro ao avaliar.');
            }
        });
    });
}

async function loadTrustRatings(uid) {
    try {
        const resp = await fetch('/api/avaliacoes-perfil/' + uid);
        const data = await resp.json();
        if (!data.success) return;

        const cats = { confiavel: data.avaliacoes.confiavel, legal: data.avaliacoes.legal, sexy: data.avaliacoes.sexy };
        for (const [cat, info] of Object.entries(cats)) {
            const wrappers = document.querySelectorAll('.interactive-trust[data-category="' + cat + '"]');
            wrappers.forEach(w => {
                const pct = (info.media / 5) * 100;
                w.dataset.percent = pct;
                w.dataset.media = info.media;
                w.dataset.total = info.total;
                w.dataset.minha = info.minhaNota;
                const fill = w.querySelector('.trust-fill');
                const tooltip = w.querySelector('.trust-tooltip');
                if (fill) fill.style.width = pct + '%';
                if (tooltip) {
                    if (w.dataset.uid === 'own') {
                        tooltip.innerHTML = 'Média: ' + info.media + ' (' + info.total + ' votos)';
                    } else {
                        tooltip.innerHTML = 'Média: ' + info.media + ' (' + info.total + ' votos)' + (info.minhaNota > 0 ? ' · Sua: ' + info.minhaNota : '');
                    }
                }
            });
        }
    } catch(e) {}
}

function formatDepDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const min = String(d.getMinutes()).padStart(2, '0');
    return dd + '/' + mm + '/' + yyyy + ' às ' + hh + ':' + min;
}

function getDepFoto(foto, sexo) {
    if (!foto || foto.includes('default-avatar')) return getDefaultAvatar(sexo);
    return foto;
}

async function aprovarDepPerfil(id) {
    try {
        const resp = await fetch('/api/depoimentos/' + id + '/aprovar', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        const data = await resp.json();
        if (data.success) {
            const uid = _viewUser ? _viewUser.id : null;
            if (uid) loadProfileDepoimentos(uid, !_isVisiting);
        } else { alert(data.message || 'Erro ao aprovar.'); }
    } catch(e) { alert('Erro de conexão.'); }
}

async function recusarDepPerfil(id) {
    showConfirm('Recusar e apagar este depoimento?', async function() {
        try {
            const resp = await fetch('/api/depoimentos/' + id + '/recusar', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
            const data = await resp.json();
            if (data.success) {
                const uid = _viewUser ? _viewUser.id : null;
                if (uid) loadProfileDepoimentos(uid, !_isVisiting);
            } else { alert(data.message || 'Erro ao recusar.'); }
        } catch(e) { alert('Erro de conexão.'); }
    });
}

async function loadProfileDepoimentos(uid, isOwner) {
    const container = document.getElementById('profile-depoimentos-section');
    if (!container) return;
    try {
        const resp = await fetch('/api/depoimentos/' + uid);
        const data = await resp.json();
        if (!data.success || !data.depoimentos || data.depoimentos.length === 0) {
            container.innerHTML = '';
            return;
        }
        const { depoimentos, perfil, myId } = data;
        let html = '<div class="card depoimentos-section" style="margin-top:0;">';
        html += '<h3 style="margin:0 0 8px 0;">Depoimentos de ' + escapeHtml(perfil.nome) + '</h3>';
        for (const dep of depoimentos) {
            const foto = getDepFoto(dep.remetente_foto, dep.remetente_sexo);
            const isPendente = dep.aprovado === 0;
            html += '<div class="dep-item' + (isPendente ? ' dep-pendente' : '') + '">';
            html += '<div class="dep-pic"><a href="profile.php?uid=' + dep.remetente_id + '"><img src="' + foto + '"></a></div>';
            html += '<div class="dep-content">';
            html += '<div class="dep-header"><a href="profile.php?uid=' + dep.remetente_id + '">' + escapeHtml(dep.remetente_nome) + '</a> <span class="dep-date">' + formatDepDate(dep.criado_em) + '</span></div>';
            html += '<div class="dep-text">"' + dep.mensagem.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"</div>';
            if (isPendente && isOwner) {
                html += '<div style="margin-top:10px; display:inline-flex; gap:5px; background:#fff; padding:5px; border:1px dashed #ccc;">';
                html += '<button type="button" onclick="aprovarDepPerfil(' + dep.id + ')" style="background:#e4f2e9; border:1px solid #8bc59e; color:#2a6b2a; cursor:pointer; padding:2px 8px; border-radius:3px;">Aceitar</button>';
                html += '<button type="button" onclick="recusarDepPerfil(' + dep.id + ')" style="background:#ffe6e6; border:1px solid #cc0000; color:#cc0000; cursor:pointer; padding:2px 8px; border-radius:3px;">Recusar / Excluir</button>';
                html += '</div>';
            } else if (isPendente && !isOwner && dep.remetente_id === myId) {
                html += '<div style="margin-top:10px; border-top:1px dashed #ccc; padding-top:10px; display:flex; justify-content:space-between; align-items:center;">';
                html += '<span style="font-size:10px; color:#f39c12; font-weight:bold;">⚠ Seu depoimento está aguardando aprovação.</span>';
                html += '<button type="button" onclick="recusarDepPerfil(' + dep.id + ')" style="background:#ffe6e6; border:1px solid #cc0000; color:#cc0000; cursor:pointer; padding:2px 8px; border-radius:3px; font-size:10px; font-weight:bold;">🗑️ Apagar</button>';
                html += '</div>';
            }
            html += '</div></div>';
        }
        html += '<div style="text-align:right;margin-top:10px;"><a href="depoimentos.php?uid=' + uid + '" style="font-weight:bold;font-size:11px;">Ver todos os depoimentos ></a></div>';
        html += '</div>';
        container.innerHTML = html;
    } catch(e) {
        console.error('Erro ao carregar depoimentos no perfil:', e);
    }
}

function atualizarIntegridade(user) {
    const campos = [
        'nome', 'email', 'nascimento', 'sexo', 'foto_perfil',
        'status_texto', 'quem_sou_eu', 'estado_civil', 'interesse_em',
        'interesses', 'atividades', 'musica', 'filmes', 'tv', 'livros',
        'esportes', 'atividades_favoritas', 'comidas', 'herois',
        'apelido', 'hora_nascimento', 'cidade_natal', 'cidade', 'estado',
        'pais', 'orientacao_sexual', 'filhos', 'altura', 'tipo_fisico',
        'etnia', 'religiao', 'humor', 'estilo', 'fumo', 'bebo',
        'animais_estimacao', 'mora_com', 'escolaridade', 'ensino_medio',
        'universidade', 'curso', 'ocupacao', 'profissao', 'empresa',
        'cargo', 'area_atuacao', 'whatsapp'
    ];
    let preenchidos = 0;
    campos.forEach(c => {
        const val = user[c];
        if (val && val !== '' && !String(val).includes('default-avatar')) preenchidos++;
    });
    const pct = Math.round((preenchidos / campos.length) * 100);
    let hue = Math.round((pct / 100) * 120);
    const cor = 'hsl(' + hue + ', 85%, 45%)';
    const spanEl = document.getElementById('perfilIntegridade');
    const barEl = document.getElementById('perfilBarraProgresso');
    if (spanEl) { spanEl.textContent = pct + '% preenchido'; spanEl.style.color = cor; }
    if (barEl) { barEl.style.width = pct + '%'; barEl.style.background = cor; if (pct >= 100) barEl.style.borderRadius = '4px'; }
}

async function doLogout() {
    await fetch('/api/logout', { method: 'POST' });
    window.location.href = '/index.php';
}

async function salvarNome(e) {
    e.preventDefault();
    const nome = document.getElementById('userNameInput').value.trim();
    if (!nome) { alert('Nome não pode ficar vazio!'); return false; }
    try {
        const resp = await fetch('/api/salvar-nome', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nome }) });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('userName').textContent = nome;
            // breadcrumb stays as 'Meu Perfil'
            document.getElementById('formNome').style.display = 'none';
        } else { alert(data.message || 'Erro ao salvar nome.'); }
    } catch(err) { alert('Erro de conexão.'); }
    return false;
}

async function salvarStatus(e) {
    e.preventDefault();
    const status = document.getElementById('statusInput').value;
    try {
        const resp = await fetch('/api/salvar-status', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ status_texto: status }) });
        const data = await resp.json();
        if (data.success) {
            const el = document.querySelector('.status-text');
            if (el) {
                const editBtn = el.querySelector('.edit-link');
                el.innerHTML = '"' + (status || 'Defina seu status aqui') + '" ';
                if (editBtn) el.appendChild(editBtn);
            }
            document.getElementById('formStatus').style.display = 'none';
        } else { alert(data.message || 'Erro ao salvar status.'); }
    } catch(err) { alert('Erro de conexão.'); }
    return false;
}

async function salvarPerfilCompleto(e) {
    e.preventDefault();
    const editor = document.getElementById('editor-content');
    const hidden = document.getElementById('hidden_quem_sou_eu');
    if (editor && hidden) hidden.value = editor.innerHTML;

    const form = document.getElementById('mainProfileForm');
    const formData = new FormData(form);
    const dados = {};
    formData.forEach((val, key) => { if (key !== 'salvar_perfil_avancado') dados[key] = val; });

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const resp = await fetch('/api/salvar-perfil', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) });
        const data = await resp.json();
        if (data.success) {
            alert('Perfil salvo com sucesso!');
            document.getElementById('formEditProfile').style.display = 'none';
            loadUserProfile();
        } else { alert(data.message || 'Erro ao salvar perfil.'); }
    } catch(err) { alert('Erro de conexão ao salvar perfil.'); }
    btn.disabled = false; btn.textContent = 'Salvar Perfil Completo';
    return false;
}

function preencherFormulario(user) {
    const form = document.getElementById('mainProfileForm');
    if (!form) return;
    const camposTexto = [
        'status_texto', 'interesse_em', 'whatsapp', 'interesses', 'atividades', 'musica',
        'filmes', 'tv', 'livros', 'esportes', 'atividades_favoritas',
        'comidas', 'herois', 'apelido', 'cidade_natal', 'cidade',
        'estado', 'pais', 'filhos', 'altura', 'tipo_fisico', 'etnia',
        'religiao', 'humor', 'estilo', 'fumo', 'bebo', 'animais_estimacao',
        'mora_com', 'ensino_medio', 'universidade', 'curso', 'grau',
        'ocupacao', 'profissao', 'empresa', 'cargo', 'area_atuacao'
    ];
    camposTexto.forEach(campo => {
        const el = form.querySelector('[name="' + campo + '"]');
        if (el && user[campo]) el.value = user[campo];
    });
    if (user.nascimento) { const el = form.querySelector('[name="data_nascimento"]'); if (el) el.value = user.nascimento; }
    if (user.hora_nascimento) { const el = form.querySelector('[name="hora_nascimento"]'); if (el) el.value = user.hora_nascimento; }
    if (user.ano_inicio) { const el = form.querySelector('[name="ano_inicio"]'); if (el) el.value = user.ano_inicio; }
    if (user.ano_conclusao_prof) { const el = form.querySelector('[name="ano_conclusao_prof"]'); if (el) el.value = user.ano_conclusao_prof; }
    const selects = ['estado_civil', 'sexo', 'orientacao_sexual', 'escolaridade'];
    selects.forEach(campo => {
        const el = form.querySelector('[name="' + campo + '"]');
        if (el && user[campo]) { for (let opt of el.options) { if (opt.value === user[campo]) { el.value = user[campo]; break; } } }
    });
    const editor = document.getElementById('editor-content');
    if (editor && user.quem_sou_eu) editor.innerHTML = user.quem_sou_eu;
}

// === Renderizar @menções de comunidades como links ===
function renderCommunityMentions(text) {
    if (!text) return '';
    // Escapa HTML primeiro
    const escaped = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    // Converte @[NomeComunidade](id) para links clicáveis
    return escaped.replace(/@\[([^\]]+)\]\((\d+)\)/g, function(match, nome, id) {
        return '<a href="/comunidades.php?id=' + id + '" class="comm-link">@' + nome + '</a>';
    });
}

// === Autocomplete @ comunidades no campo interesse_em ===
(function() {
    let _acTimeout = null;
    let _acResults = [];
    let _acIndex = -1;

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('interesse_em_input');
        const dropdown = document.getElementById('ac-comunidades');
        if (!input || !dropdown) return;

        input.addEventListener('input', function() {
            const val = input.value;
            const curPos = input.selectionStart;
            const atInfo = findAtQuery(val, curPos);

            if (!atInfo) {
                hideAC();
                return;
            }

            clearTimeout(_acTimeout);
            _acTimeout = setTimeout(() => searchCommunities(atInfo.query), 250);
        });

        input.addEventListener('keydown', function(e) {
            if (!dropdown.classList.contains('show')) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                _acIndex = Math.min(_acIndex + 1, _acResults.length - 1);
                highlightAC();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                _acIndex = Math.max(_acIndex - 1, 0);
                highlightAC();
            } else if (e.key === 'Enter' && _acIndex >= 0) {
                e.preventDefault();
                selectCommunity(_acResults[_acIndex]);
            } else if (e.key === 'Escape') {
                hideAC();
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.interesse-wrapper')) hideAC();
        });

        function findAtQuery(text, cursorPos) {
            // Procura o @ mais recente antes do cursor
            const before = text.substring(0, cursorPos);
            const atIdx = before.lastIndexOf('@');
            if (atIdx === -1) return null;
            // Verifica se não está dentro de um @[...]() já completo
            const afterAt = before.substring(atIdx);
            if (afterAt.match(/@\[[^\]]*\]\(\d+\)/)) return null;
            const query = before.substring(atIdx + 1);
            // Não ativar se tem espaço logo antes do @ (exceto início de string)
            if (atIdx > 0 && text[atIdx-1] !== ' ' && text[atIdx-1] !== ',') return null;
            return { start: atIdx, query: query };
        }

        async function searchCommunities(query) {
            if (query.length < 1) { hideAC(); return; }
            try {
                const resp = await fetch('/api/buscar-comunidades?q=' + encodeURIComponent(query));
                const data = await resp.json();
                if (!data.success || !data.comunidades.length) {
                    dropdown.innerHTML = '<div class="ac-empty">Nenhuma comunidade encontrada</div>';
                    dropdown.classList.add('show');
                    _acResults = [];
                    _acIndex = -1;
                    return;
                }
                _acResults = data.comunidades;
                _acIndex = -1;
                dropdown.innerHTML = data.comunidades.map((c, i) =>
                    '<div class="ac-item" data-idx="' + i + '">' +
                    '<img src="' + (c.foto || '/img/default-community.png') + '" onerror="this.src=\'/img/default-community.png\'">' +
                    '<div class="ac-item-info">' +
                    '<div class="ac-item-name">' + escapeHtml(c.nome) + '</div>' +
                    '<div class="ac-item-meta">' + (c.categoria || 'Geral') + ' · ' + (c.membros || 0) + ' membros</div>' +
                    '</div></div>'
                ).join('');
                dropdown.classList.add('show');

                dropdown.querySelectorAll('.ac-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const idx = parseInt(this.dataset.idx);
                        selectCommunity(_acResults[idx]);
                    });
                });
            } catch(err) { console.error(err); hideAC(); }
        }

        function selectCommunity(comm) {
            const val = input.value;
            const curPos = input.selectionStart;
            const before = val.substring(0, curPos);
            const atIdx = before.lastIndexOf('@');
            const after = val.substring(curPos);

            // Substitui @query pela menção formatada
            const mention = '@[' + comm.nome + '](' + comm.id + ')';
            const newVal = val.substring(0, atIdx) + mention + (after.startsWith(' ') ? after : ' ' + after);
            input.value = newVal.trimEnd() + (newVal.endsWith(' ') ? '' : ' ');
            hideAC();
            // Posicionar cursor depois da menção
            const newPos = atIdx + mention.length + 1;
            input.setSelectionRange(newPos, newPos);
            input.focus();
        }

        function highlightAC() {
            dropdown.querySelectorAll('.ac-item').forEach((item, i) => {
                item.classList.toggle('ac-active', i === _acIndex);
            });
            const active = dropdown.querySelector('.ac-active');
            if (active) active.scrollIntoView({ block: 'nearest' });
        }

        function hideAC() {
            dropdown.classList.remove('show');
            _acResults = [];
            _acIndex = -1;
        }
    });
})();
</script>
</head>
<body>

<style>
    :root { --orkut-pink: #e6399b; }
    body[data-theme-slug] { }
    .hdr-drop-container { position: relative; display: inline-block; }
    .hdr-dropdown { display: none; position: absolute; top: 25px; right: -50px; background: #fff; border: 1px solid var(--orkut-blue); box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999; border-radius: 4px; text-align: left; width: 320px; color: #333; }
    .hdr-dropdown::before { content: ''; position: absolute; top: -10px; right: 60px; border-width: 0 10px 10px 10px; border-style: solid; border-color: transparent transparent var(--orkut-blue) transparent; }
    .hdr-drop-item { display: flex; align-items:flex-start; gap:10px; padding: 10px; border-bottom: 1px dotted var(--line); font-size: 11px; color: #444; line-height: 1.3; text-decoration: none; transition: 0.2s;}
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
    .btn-invite { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .btn-invite-active { background-color: var(--orkut-pink); color: #ffffff !important; border: 1px solid var(--orkut-pink); }
    .btn-invite-active:hover { background-color: var(--orkut-pink); color: #ffffff !important; text-decoration: none; }
    .btn-invite-empty { background-color: #dbe3ef; color: #666 !important; border: 1px solid #a5bce3; }
    .btn-invite-empty:hover { background-color: #e4ebf5; text-decoration: none; }
    .menu-badge { background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 10px; margin-left: auto; display: inline-block; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2); animation: pulseBadgeLeft 1.5s infinite; }
    @keyframes pulseBadgeLeft { 0% { transform: scale(1); } 50% { transform: scale(1.15); background: #ff4db8; } 100% { transform: scale(1); } }
    .sub-badge { position: absolute; top: -8px; right: -15px; background: var(--orkut-pink); color: #fff; font-size: 9px; padding: 1px 5px; border-radius: 10px; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.2); animation: pulseSubBadge 1.5s infinite; line-height: 1; z-index:10; }
    @keyframes pulseSubBadge { 0% { transform: scale(1); } 50% { transform: scale(1.15); background: #ff4db8; } 100% { transform: scale(1); } }
    .menu-left li a { display: flex; align-items: center; width: 100%; box-sizing: border-box; }
    .menu-category { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #7992b5; padding-bottom: 4px; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid var(--line); padding-left: 5px; letter-spacing: 0.5px; }
    .menu-category:first-child { margin-top: 5px; }
    .editor-toolbar { background: #e8eef7; padding: 5px; border: 1px solid #c0d0e6; border-bottom: none; display: flex; gap: 5px; border-radius: 3px 3px 0 0; }
    .editor-btn { background: #fff; border: 1px solid #a5bce3; cursor: pointer; padding: 4px 8px; font-weight: bold; color: #3b5998; border-radius: 2px; font-size: 11px; }
    .editor-btn:hover { background: #dbe3ef; }
    #editor-content { border: 1px solid #c0d0e6; padding: 10px; min-height: 100px; border-radius: 0 0 3px 3px; background: #fff; font-size: 12px; margin-bottom: 15px; outline: none; }
    /* Autocomplete @ comunidades */
    .interesse-wrapper { position: relative; width: 100%; }
    .interesse-wrapper input { width: 100%; box-sizing: border-box; }
    .ac-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #c0d0e6; border-top: none; border-radius: 0 0 4px 4px; max-height: 220px; overflow-y: auto; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none; }
    .ac-dropdown.show { display: block; }
    .ac-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; cursor: pointer; font-size: 12px; border-bottom: 1px dotted #eee; transition: background 0.15s; }
    .ac-item:hover, .ac-item.ac-active { background: #e4ebf5; }
    .ac-item img { width: 28px; height: 28px; border-radius: 3px; object-fit: cover; border: 1px solid #ccc; }
    .ac-item-info { flex: 1; }
    .ac-item-name { font-weight: bold; color: var(--title, #333); }
    .ac-item-meta { font-size: 10px; color: #888; }
    .ac-empty { padding: 12px; text-align: center; color: #999; font-size: 11px; }
    .comm-link { color: var(--orkut-pink, #e6399b); font-weight: bold; text-decoration: none; }
    .comm-link:hover { text-decoration: underline; }
</style>

<script>
    function toggleHdrDrop(id, event) {
        event.stopPropagation();
        document.querySelectorAll('.hdr-dropdown').forEach(d => { if(d.id !== id) d.style.display = 'none'; });
        let el = document.getElementById(id);
        el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.hdr-dropdown').forEach(d => { if(!d.contains(e.target) && d.style.display === 'block') d.style.display = 'none'; });
    });
</script>

<header>
    <div class="header-wrapper">
        <div class="header-main">
            <div class="logo-container">
                <a href="profile.php" class="logo">yorkut</a>
                <nav class="nav-main hide-on-mobile">
                    <a href="profile.php">Início</a>
                    <a href="profile.php">Perfil</a>
                    <a href="recados.php">Página de Recados</a>
                    <a href="amigos.php">Amigos</a>
                    <a href="user_comunidades.php">Comunidades</a>
                </nav>
            </div>
            <div style="font-size:11px; display:flex; align-items:center; gap:15px;">
                <a href="meus_convites.php" id="btnConvite" class="btn-invite btn-invite-active">Convite</a> |
                <div class="hdr-drop-container">
                    <a href="javascript:void(0);" onclick="toggleHdrDrop('drop-reqs', event)" style="color:#fff; text-decoration:none;" id="profile-hdr-reqs-link">Solicitações</a>
                    <div id="drop-reqs" class="hdr-dropdown">
                        <div id="profile-drop-reqs-content"><div style="padding:20px; color:#999; text-align:center; font-size:11px;">Carregando...</div></div>
                        <a href="solicitacoes.php" class="hdr-drop-all">Ver todas as solicitações</a>
                    </div>
                </div> |
                <div class="hdr-drop-container">
                    <a href="javascript:void(0);" onclick="toggleHdrDrop('drop-notifs', event); profileLoadHeaderNotificacoes();" style="color:#fff; text-decoration:none;" id="profile-hdr-notifs-link">&#x1F514; Notificações</a>
                    <div id="drop-notifs" class="hdr-dropdown">
                        <div id="profile-drop-notifs-content"><div style="padding:20px; color:#999; text-align:center; font-size:11px;">Carregando...</div></div>
                        <a href="notificacoes.php" class="hdr-drop-all">Ver todas as notificações</a>
                    </div>
                </div> |
                <span id="profile-admin-link-wrap" style="display:none;"><a href="/admin.php" style="color:#ffcaca; text-decoration:none; font-weight:bold;" id="profile-hdr-admin-link">⚙️ Admin</a> | </span>
                <span id="headerEmail">email@example.com</span> | <a href="javascript:void(0);" onclick="doLogout()" style="color:#fff;">sair</a>
            </div>
        </div>
    </div>
    <div class="header-sub-wrapper">
        <div class="header-sub">
            <a href="recados.php" id="hdr-recado-link" style="margin-right: 25px; position: relative;">recados</a>
            <a href="mensagens_particular.php" id="hdr-msg-link" style="margin-right: 25px; position: relative;">mensagens</a>
            <a href="fotos.php" style="margin-right: 10px;">fotos</a>
            <a href="videos.php" style="margin-right: 10px;">vídeos</a>
            <a href="depoimentos.php" id="hdr-dep-link" style="margin-right: 25px; position: relative;">depoimentos</a>
            <a href="configuracoes.php" style="margin-right: 10px;">configurações</a>
            <a href="anuncios.php" style="margin-right: 10px;">anúncios</a>
            <div class="search-bars hide-on-mobile">
                <form action="search_user.php" method="GET" class="search-form"><input type="text" name="q" placeholder="buscar amigo..." required><button type="submit">&#x1F50D;</button></form>
                <form action="search_community.php" method="GET" class="search-form"><input type="text" name="q" placeholder="buscar comunidade..." required><button type="submit">&#x1F50D;</button></form>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <!-- LEFT COLUMN -->
    <div class="left-col">
        <div class="card-left">
            <div class="profile-pic" style="margin:0 auto 10px;">
                <img id="profileImage" src="/img/default-avatar.png" style="min-height:160px;">
            </div>
            <div id="userDetails" class="detalhes-usuario-centro"></div>
            <div class="perfil-foto-botoes">
                <button class="btn-foto" onclick="toggleForm('formFoto')">&#x1F4F7; alterar foto</button>
                <button class="btn-foto" onclick="openAjustarFoto()">&#x1F4D0; ajustar</button>
            </div>
            <div id="formFoto" class="hidden-form" style="text-align:center;">
                <form id="uploadForm" onsubmit="return enviarFoto(event);">
                    <input type="file" id="fileInput" accept="image/jpeg, image/png" style="width:100%;font-size:10px;margin-bottom:5px;">
                    <div id="cropContainer" style="display:none;">
                        <canvas id="cropCanvas" width="160" height="160" style="border:1px solid #ccc;"></canvas><br>
                        <input type="range" id="zoomRange" step="0.01" style="width:100%;"><br>
                        <input type="hidden" name="foto_base64" id="fotoBase64">
                        <button type="submit" id="btnSalvarFoto" style="width:100%;margin-top:5px; background:var(--orkut-blue); color:#fff; border:none; padding:6px; border-radius:3px; cursor:pointer;">Salvar Foto</button>
                    </div>
                    <button type="button" onclick="document.getElementById('formFoto').style.display='none';" style="width:100%;margin-top:5px;background:#fff;border:1px solid #ccc;color:#666; padding:6px; border-radius:3px; cursor:pointer;">Cancelar</button>
                </form>
            </div>
            <ul class="menu-left hide-on-mobile">
                <div class="menu-category">PERFIL</div>
                <li style="background-color:#eef4ff; display:flex; align-items:center; justify-content:space-between;"><a href="profile.php" style="flex:1;"><span>&#x1F464;</span> perfil</a><a href="javascript:void(0);" onclick="document.getElementById('formEditProfile').style.display='block';" style="font-size:10px; color:var(--link); text-decoration:none; width:auto; flex-shrink:0; padding-right:10px;">editar</a></li>
                <li><a href="recados.php" id="menu-recado-link"><span>&#x1F4DD;</span> recados</a></li>
                <li><a href="fotos.php"><span>&#x1F4F7;</span> fotos</a></li>
                <li><a href="videos.php"><span>&#x1F3A5;</span> vídeos</a></li>
                <li><a href="mensagens_particular.php" id="menu-msg-link"><span>&#x2709;&#xFE0F;</span> mensagens</a></li>
                <li><a href="depoimentos.php" id="menu-dep-link"><span>&#x1F31F;</span> depoimentos</a></li>
                <div class="menu-category">Jogos e Apps</div>
                <li><a href="colheita.php"><span>&#x1F33D;</span> colheita feliz</a></li>
                <li><a href="buddypoke.php"><span>&#x1FAC2;</span> buddy poke</a></li>
                <li><a href="cafemania.php"><span>&#x2615;</span> café mania</a></li>
                <div class="menu-category">Sistema</div>
                <li><a href="temas.php"><span>&#x1F3A8;</span> temas</a></li>
                <li><a href="configuracoes.php"><span>&#x2699;&#xFE0F;</span> configurações</a></li>
                <li><a href="anuncios.php"><span>&#x1F4E2;</span> anúncios</a></li>
                <li><a href="notificacoes.php" id="profile-menu-notif-link"><span>&#x1F514;</span> notificações</a></li>
                <li><a href="kutcoin.php"><span>&#x1FA99;</span> KutCoin</a></li>
                <li><a href="sugestoes.php"><span>&#x1F4A1;</span> sugestões</a></li>
                <li><a href="reportar_bug.php"><span>&#x1F41B;</span> reportar bug</a></li>
            </ul>
        </div>
    </div>
    
    <!-- CENTER COLUMN -->
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="profile.php">Início</a> > <span id="breadcrumbName">Meu Perfil</span></div>
        <div class="card">
            <h1 class="orkut-name" style="text-align:left;">
                <span id="userName"></span>
                <a href="javascript:void(0);" onclick="document.getElementById('formNome').style.display='block';" class="edit-link" style="font-size:12px; color:#6d84b4; text-decoration:none; font-weight:bold; margin-left:8px; vertical-align:middle; border:1px solid #bccde6; border-radius:14px; padding:3px 14px; background:#fff;">editar</a>
            </h1>
            <div id="formNome" class="hidden-form">
                <form method="POST" onsubmit="return salvarNome(event);">
                    <input type="text" id="userNameInput" name="nome" value="" required>
                    <button type="submit" name="salvar_nome">Salvar</button>
                    <button type="button" onclick="document.getElementById('formNome').style.display='none';" style="background:#fff;color:#666;">Cancelar</button>
                </form>
            </div>
            <div class="status-text" style="text-align:left;">
                "Defina seu status aqui"
                <a href="javascript:void(0);" onclick="document.getElementById('formStatus').style.display='block';" class="edit-link" style="font-size:12px; color:#6d84b4; text-decoration:none; font-weight:bold; font-style:normal; margin-left:8px; border:1px solid #bccde6; border-radius:14px; padding:3px 14px; background:#fff;">editar</a>
            </div>
            <div id="formStatus" class="hidden-form">
                <form method="POST" onsubmit="return salvarStatus(event);">
                    <input type="text" name="status_texto" id="statusInput" value="" maxlength="255">
                    <button type="submit" name="salvar_status">Salvar</button>
                    <button type="button" onclick="document.getElementById('formStatus').style.display='none';" style="background:#fff;color:#666;">Cancelar</button>
                </form>
            </div>
            <div class="sorte-box"><b style="color:#cc6600;">Sorte de hoje:</b> <span id="sorteTexto">A felicidade não é um destino, é uma jornada.</span></div>
            <div class="stats-container">
                <div class="stats-info">
                    <div class="stat-box"><span class="stat-label">recados</span><a href="recados.php" class="stat-num" id="own-stat-recados" title="Ver recados">&#x1F4DD; 0</a></div>
                    <div class="stat-box"><span class="stat-label">fotos</span><a href="fotos.php" class="stat-num" id="own-stat-fotos" title="Ver fotos">&#x1F4F7; 0</a></div>
                    <div class="stat-box"><span class="stat-label">vídeos</span><a href="videos.php" class="stat-num" id="own-stat-videos" title="Ver vídeos">&#x1F3A5; 0</a></div>
                    <div class="stat-box"><span class="stat-label">fãs</span><div class="stat-num" id="fan-count-own" style="cursor:default;">&#x2B50; 0</div></div>
                </div>
                <div class="trust-row">
                    <div class="trust-item"><span class="trust-label">confiável</span><div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="confiavel" data-uid="own"><div class="trust-base">&#x1F60A;&#x1F60A;&#x1F60A;</div><div class="trust-fill" style="width: 0%;">&#x1F60A;&#x1F60A;&#x1F60A;</div><div class="trust-tooltip">Média: 0</div></div></div>
                    <div class="trust-item"><span class="trust-label">legal</span><div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="legal" data-uid="own"><div class="trust-base">&#x1F9CA;&#x1F9CA;&#x1F9CA;</div><div class="trust-fill" style="width: 0%;">&#x1F9CA;&#x1F9CA;&#x1F9CA;</div><div class="trust-tooltip">Média: 0</div></div></div>
                    <div class="trust-item"><span class="trust-label">sexy</span><div class="trust-stars-wrapper interactive-trust" data-percent="0" data-category="sexy" data-uid="own"><div class="trust-base">&#x2764;&#xFE0F;&#x2764;&#xFE0F;&#x2764;&#xFE0F;</div><div class="trust-fill" style="width: 0%;">&#x2764;&#xFE0F;&#x2764;&#xFE0F;&#x2764;&#xFE0F;</div><div class="trust-tooltip">Média: 0</div></div></div>
                </div>
            </div>
            <div style="font-size:11px; color:#666; margin-bottom: 15px; text-align: right; font-weight: bold;">
                Integridade do perfil: <span id="perfilIntegridade" style="color: hsl(11, 85%, 45%);">0% preenchido</span>
                <a href="javascript:void(0);" onclick="document.getElementById('formEditProfile').style.display='block';" style="color: var(--link); margin-left: 5px; font-weight: normal; text-decoration: underline;">(completar)</a>
                <div style="width: 100%; height: 10px; background: #e4ebf5; border: 1px solid #c0d0e6; border-radius: 5px; margin-top: 5px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                    <div id="perfilBarraProgresso" style="width: 0%; height: 100%; background: hsl(11, 85%, 45%); transition: width 0.8s ease, background 0.8s ease; box-shadow: inset 0 -2px 4px rgba(0,0,0,0.15); border-radius: 4px 0 0 4px;"></div>
                </div>
            </div>
            <div id="formEditProfile" class="hidden-form" style="position:relative;">
                <form method="POST" id="mainProfileForm" onsubmit="return salvarPerfilCompleto(event);">
                    <div class="form-group-title">Frase / Status</div>
                    <input type="text" name="status_texto" value="" placeholder="Sua frase ou status (ex: Curtindo a vida!)" maxlength="255">
                    <div class="form-group-title">Aba Social (Gostos e Estilo de Vida)</div>
                    <select name="estado_civil"><option value="">Relacionamento...</option><option value="Solteiro(a)">Solteiro(a)</option><option value="Namorando">Namorando</option><option value="Noivo(a)">Noivo(a)</option><option value="Casado(a)">Casado(a)</option><option value="Divorciado(a)">Divorciado(a)</option><option value="Separado(a)">Separado(a)</option><option value="Viúvo(a)">Viúvo(a)</option><option value="Em um relacionamento aberto">Em um relacionamento aberto</option><option value="Enrolado(a)">Enrolado(a)</option><option value="Prefiro não dizer">Prefiro não dizer</option></select>
                    <label style="font-size:11px; font-weight:bold; color:#555; margin-top:10px; display:block;">Quem sou eu (HTML Permitido):</label>
                    <div class="editor-toolbar">
                        <button type="button" class="editor-btn" onclick="document.execCommand('bold',false,null)"><b>B</b></button>
                        <button type="button" class="editor-btn" onclick="document.execCommand('italic',false,null)"><i>I</i></button>
                        <button type="button" class="editor-btn" onclick="document.execCommand('underline',false,null)"><u>U</u></button>
                        <input type="color" id="textColor" style="border:none; width:25px; height:25px; padding:0; cursor:pointer;" onchange="document.execCommand('foreColor',false,this.value)">
                    </div>
                    <div id="editor-content" contenteditable="true"></div>
                    <input type="hidden" name="quem_sou_eu" id="hidden_quem_sou_eu">
                    <div class="interesse-wrapper">
                        <input type="text" name="interesse_em" id="interesse_em_input" value="" placeholder="Interesse em (use @ para marcar comunidades)" autocomplete="off">
                        <div class="ac-dropdown" id="ac-comunidades"></div>
                    </div>
                    <input type="number" name="whatsapp" value="" placeholder="WhatsApp (Apenas números + DDI + DDD)">
                    <input type="text" name="interesses" value="" placeholder="Interesses">
                    <input type="text" name="atividades" value="" placeholder="Atividades">
                    <input type="text" name="musica" value="" placeholder="Música">
                    <input type="text" name="filmes" value="" placeholder="Filmes">
                    <input type="text" name="tv" value="" placeholder="Programas de TV">
                    <input type="text" name="livros" value="" placeholder="Livros">
                    <input type="text" name="esportes" value="" placeholder="Esportes">
                    <input type="text" name="atividades_favoritas" value="" placeholder="Atividades Favoritas">
                    <input type="text" name="comidas" value="" placeholder="Comidas">
                    <input type="text" name="herois" value="" placeholder="Meus Heróis">
                    <div class="form-group-title">Aba Pessoal</div>
                    <input type="text" name="apelido" value="" placeholder="Apelido">
                    <label style="font-size:10px; color:#777;">Data de Nascimento:</label>
                    <input type="date" name="data_nascimento" value="">
                    <label style="font-size:10px; color:#777;">Hora do Nascimento:</label>
                    <input type="time" name="hora_nascimento" value="">
                    <input type="text" name="cidade_natal" value="" placeholder="Cidade Natal">
                    <input type="text" name="cidade" value="" placeholder="Cidade Atual">
                    <input type="text" name="estado" value="" placeholder="Estado Atual">
                    <input type="text" name="pais" value="Brasil" placeholder="País Atual">
                    <select name="sexo"><option value="">Sexo / gênero...</option><option value="Heterossexual">Heterossexual</option><option value="Homossexual (Gay)">Homossexual (Gay)</option><option value="Lésbica">Lésbica</option><option value="Bissexual">Bissexual</option><option value="Pansexual">Pansexual</option><option value="Assexual">Assexual</option><option value="Demissexual">Demissexual</option><option value="Queer">Queer</option><option value="Questionando">Questionando</option><option value="Prefiro não dizer">Prefiro não dizer</option><option value="Outro">Outro</option></select>
                    <select name="orientacao_sexual"><option value="">Orientação Sexual...</option><option value="Heterossexual">Heterossexual</option><option value="Homossexual (Gay)">Homossexual (Gay)</option><option value="Lésbica">Lésbica</option><option value="Bissexual">Bissexual</option><option value="Pansexual">Pansexual</option><option value="Assexual">Assexual</option><option value="Demissexual">Demissexual</option><option value="Queer">Queer</option><option value="Questionando">Questionando</option><option value="Prefiro não dizer">Prefiro não dizer</option><option value="Outro">Outro</option></select>
                    <input type="text" name="filhos" value="" placeholder="Filhos">
                    <input type="number" name="altura" value="" placeholder="Altura em cm (Ex: 175)">
                    <input type="text" name="tipo_fisico" value="" placeholder="Tipo físico">
                    <input type="text" name="etnia" value="" placeholder="Etnia">
                    <input type="text" name="religiao" value="" placeholder="Religião">
                    <input type="text" name="humor" value="" placeholder="Humor">
                    <input type="text" name="estilo" value="" placeholder="Estilo">
                    <input type="text" name="fumo" value="" placeholder="Fuma?">
                    <input type="text" name="bebo" value="" placeholder="Bebe?">
                    <input type="text" name="animais_estimacao" value="" placeholder="Animais de Estimação">
                    <input type="text" name="mora_com" value="" placeholder="Mora com quem?">
                    <div class="form-group-title">Aba Profissional</div>
                    <select name="escolaridade"><option value="">Escolaridade...</option><option value="Ensino fundamental incompleto">Ensino fundamental incompleto</option><option value="Ensino fundamental completo">Ensino fundamental completo</option><option value="Ensino médio incompleto">Ensino médio incompleto</option><option value="Ensino médio completo">Ensino médio completo</option><option value="Ensino técnico incompleto">Ensino técnico incompleto</option><option value="Ensino técnico completo">Ensino técnico completo</option><option value="Ensino superior incompleto">Ensino superior incompleto</option><option value="Ensino superior completo">Ensino superior completo</option><option value="Pós-graduação">Pós-graduação</option><option value="Especialização">Especialização</option><option value="Mestrado">Mestrado</option><option value="Doutorado">Doutorado</option><option value="Pós-doutorado">Pós-doutorado</option><option value="Outro">Outro</option><option value="Prefiro não dizer">Prefiro não dizer</option></select>
                    <input type="text" name="ensino_medio" value="" placeholder="Ensino Médio (Nome da Escola)">
                    <input type="text" name="universidade" value="" placeholder="Universidade">
                    <input type="text" name="curso" value="" placeholder="Curso">
                    <label style="font-size:10px; color:#777;">Ano de Início:</label>
                    <input type="date" name="ano_inicio" value="">
                    <label style="font-size:10px; color:#777;">Ano de Conclusão:</label>
                    <input type="date" name="ano_conclusao_prof" value="">
                    <input type="text" name="grau" value="" placeholder="Grau">
                    <input type="text" name="ocupacao" value="" placeholder="Ocupação Atual">
                    <input type="text" name="profissao" value="" placeholder="Profissão Formada">
                    <input type="text" name="empresa" value="" placeholder="Empresa Atual">
                    <input type="text" name="cargo" value="" placeholder="Cargo">
                    <input type="text" name="area_atuacao" value="" placeholder="Área de Atuação">
                    <button type="submit" name="salvar_perfil_avancado" class="btn-action" style="margin-top: 15px; width:100%; padding:10px; font-size:14px;">Salvar Perfil Completo</button>
                    <button type="button" onclick="document.getElementById('formEditProfile').style.display='none';" style="background:#fff; color:#666; width:100%; padding:10px; margin-top:5px; border:1px solid #ccc;">Cancelar</button>
                </form>
            </div>
            <div class="tabs-nav">
                <div class="tab-btn active" id="btn-social" onclick="showTab('social')">social</div>
                <div class="tab-btn" id="btn-pessoal" onclick="showTab('pessoal')">pessoal</div>
                <div class="tab-btn" id="btn-profissional" onclick="showTab('profissional')">profissional</div>
            </div>
            <div class="card" style="padding: 10px; border-top: none; border-radius: 0 0 4px 4px; margin-top: -10px;">
                <div id="tab-social" class="tab-content active"><table class="info-table"><tr><td colspan="2" style="background:#f4f7fc; text-align:center; padding:10px;"><a href="amigos.php" style="color:var(--link); font-weight:bold; font-size:14px; text-decoration:none;">&#x1F465; Amigos: 0 (Ver todos)</a></td></tr></table></div>
                <div id="tab-pessoal" class="tab-content"><table class="info-table"><tr><td class='info-label'>Data de nascimento:</td><td class='info-value'>-</td></tr></table></div>
                <div id="tab-profissional" class="tab-content"><table class="info-table"><tr><td colspan="2" style="color:#999; text-align:center; padding:15px;">Nenhuma informação profissional cadastrada.</td></tr></table></div>
            </div>
        </div>
        <div id="profile-depoimentos-section"></div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-col">
        <style>
            .box-visitantes-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; padding-bottom: 5px; }
            .visitante-quadrado { width: 100%; aspect-ratio: 1 / 1; border-radius: 4px; border: 1px solid #c0d0e6; background: #e4ebf5; display: flex; align-items: center; justify-content: center; overflow: hidden; text-decoration: none; transition: all 0.2s ease; }
            .visitante-quadrado:hover { border-color: var(--orkut-pink); transform: scale(1.08); box-shadow: 0 3px 8px rgba(0,0,0,0.15); z-index: 2; }
            .visitante-quadrado img { width: 100%; height: 100%; object-fit: cover; display: block; }
            .visitante-sem-foto { font-size: 10px; color: #999; text-align: center; line-height: 1.2; }
        </style>
        <div id="box-visitantes" class="box-sidebar hide-on-mobile">
            <div class="box-title" style="margin-bottom: 5px;"><span>quem me visitou</span></div>
            <div id="visitantes-grid" class="box-visitantes-grid"><div class="visitante-quadrado"><span class="visitante-sem-foto">Carregando...</span></div></div>
        </div>
        <div class="box-sidebar" id="box-amigos-own">
            <div class="box-title">meus amigos (0) <a href="amigos.php">ver todos</a></div>
            <div class="grid"><div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Carregando...</div></div>
        </div>
        <div class="box-sidebar" id="box-comunidades-own">
            <div class="box-title"><span id="own-comunidades-title">minhas comunidades (0)</span> <a href="user_comunidades.php">ver todas</a></div>
            <div class="grid" id="own-comunidades-grid"><div class="grid-item" style="grid-column: 1 / -1; text-align:center; color:#999; padding:15px;">Carregando...</div></div>
        </div>
    </div>
</div>

<footer class="footer">
    <span class="google-logo">yorkut</span> &copy; 2026 | 
    <a href="sobre.php">Sobre o yorkut</a> | 
    <a href="novidades.php">Novidades</a> | 
    <a href="seguranca.php">Centro de segurança</a> | 
    <a href="privacidade.php">Privacidade</a> | 
    <a href="termos.php">Termos de uso</a> | 
    <a href="contato.php">Contato</a>
</footer>
</body>
</html>
