<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Yorkut - Explore e participe de comunidades com pessoas que compartilham seus interesses.">
<title>Yorkut - Comunidades</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/styles/comunidades.css">
<style>
    .comm-list-section { margin-bottom: 25px; }
    .comm-list-section h3 { font-size: 14px; color: var(--title); border-bottom: 1px solid var(--line); padding-bottom: 5px; margin-bottom: 15px; }
    .comm-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .comm-card { display: flex; gap: 15px; padding: 12px; border: 1px solid var(--line); border-radius: 4px; background: #fdfdfd; align-items: center; transition: 0.2s; }
    .comm-card:hover { border-color: #a5bce3; background: #f4f7fc; }
    .comm-pic { width: 80px; aspect-ratio: 3 / 4; flex-shrink: 0; background: #e4ebf5; border: 1px solid #c0d0e6; border-radius: 3px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .comm-pic img { width: 100%; height: 100%; object-fit: cover; }
    .comm-info { flex: 1; overflow: hidden; }
    .comm-name { font-weight: bold; font-size: 13px; color: var(--link); text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px; }
    .comm-name:hover { text-decoration: underline; }
    .comm-meta { font-size: 10px; color: #666; line-height: 1.4; }
    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 10px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }
    .btn-danger-outline { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-danger-outline:hover { background: #ffe6e6; border-color: #cc0000; }
    .empty-state { text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; }
    .msg-error { background: #ffebee; border: 1px solid #cc0000; color: #cc0000; padding: 10px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; text-align: center; }
    .msg-success { background: #e8f5e9; border: 1px solid #4caf50; color: #2e7d32; padding: 10px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; text-align: center; }
    @media (max-width: 768px) { .comm-grid { grid-template-columns: 1fr; } }

    /* Community Detail Page Styles */
    .mini-item { padding: 8px 10px; border-bottom: 1px dotted #e4ebf5; font-size: 11px; display: flex; justify-content: space-between; align-items: center; }
    .mini-item:last-child { border-bottom: none; }
    .mini-item:nth-child(even) { background-color: #f4f7fc; }
    .mini-item:nth-child(odd) { background-color: #ffffff; }
    .mini-box-list { background: #fff; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 15px; overflow: hidden; }
    .mini-box-list h3 { margin: 0; padding: 10px; font-size: 12px; color: #fff; background: var(--orkut-blue); display: flex; justify-content: space-between; align-items: center; }
    .mini-box-list h3 a { color: #fff; text-decoration: underline; }
    .info-table td a { color: var(--link); font-weight: bold; }
    .info-table td a:hover { text-decoration: underline; }
    .btn-share { background: #fff; border: 1px solid #ccc; color: #333; padding: 5px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight:bold;}
    .btn-share:hover { background: #f0f0f0; }
    .card-left .profile-pic { width: 100%; aspect-ratio: 3/4; height: auto; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .card-left .profile-pic img { width: 100%; height: 100%; object-fit: cover; }
    .editor-toolbar { background: #e8eef7; padding: 5px; border: 1px solid #c0d0e6; border-bottom: none; display: flex; gap: 5px; border-radius: 4px 4px 0 0; align-items:center;}
    .editor-btn { background: #fff; border: 1px solid #a5bce3; cursor: pointer; padding: 4px 8px; font-weight: bold; color: #3b5998; border-radius: 3px; font-size: 11px; }
    .editor-btn:hover { background: #dbe3ef; }
    .editor-area { border: 1px solid #c0d0e6; padding: 10px; min-height: 100px; border-radius: 0 0 4px 4px; background: #fff; font-size: 12px; outline: none; margin-bottom: 10px; overflow-y:auto; line-height: 1.4; font-family: Tahoma, Arial;}
</style>
<script>
    // Ferramentas do Editor
    function formatText(editorId, hiddenId, cmd, val) { 
        document.getElementById(editorId).focus();
        document.execCommand(cmd, false, val || null); 
        syncText(editorId, hiddenId); 
    }
    function insertLink(editorId, hiddenId) {
        var url = prompt("Digite a URL do link (ex: http://www.google.com):", "http://");
        if (url) { document.execCommand('createLink', false, url); syncText(editorId, hiddenId); }
    }
    function syncText(editorId, hiddenId) { 
        var content = document.getElementById(editorId);
        var hidden = document.getElementById(hiddenId);
        if (content && hidden) {
            if(content.innerText.trim() === '') hidden.value = '';
            else hidden.value = content.innerHTML; 
        }
    }

    // JS do Recorte da Foto
    var cropImg=new Image(),cropCanvas,cropCtx,cropScale=1,cropOffX=0,cropOffY=0,cropDragging=false,cropStartX,cropStartY;
    var CROP_W = 150, CROP_H = 200; 

    function initCropCanvas() {
        cropCanvas=document.getElementById('cropCanvas');if(!cropCanvas)return;cropCtx=cropCanvas.getContext('2d');
        var fInp=document.getElementById('fileInput');
        if(fInp)fInp.addEventListener('change',function(e){
            var r=new FileReader();
            r.onload=function(ev){
                cropImg.onload=function(){
                    document.getElementById('cropContainer').style.display='block';
                    cropScale=Math.max(CROP_W/cropImg.width,CROP_H/cropImg.height);
                    var zr=document.getElementById('zoomRange');
                    zr.min=cropScale;zr.max=cropScale*3;zr.value=cropScale;
                    cropOffX=(CROP_W-cropImg.width*cropScale)/2;
                    cropOffY=(CROP_H-cropImg.height*cropScale)/2;
                    drawCrop();
                };
                cropImg.src=ev.target.result;
            };
            if(e.target.files[0])r.readAsDataURL(e.target.files[0]);
        });
        var zRng=document.getElementById('zoomRange');
        if(zRng)zRng.addEventListener('input',function(){cropScale=parseFloat(this.value);drawCrop();});
        cropCanvas.addEventListener('mousedown',function(e){cropDragging=true;cropStartX=e.offsetX-cropOffX;cropStartY=e.offsetY-cropOffY;});
        cropCanvas.addEventListener('mousemove',function(e){if(cropDragging){cropOffX=e.offsetX-cropStartX;cropOffY=e.offsetY-cropStartY;drawCrop();}});
        cropCanvas.addEventListener('mouseup',function(){cropDragging=false;});
        cropCanvas.addEventListener('mouseleave',function(){cropDragging=false;});
    }
    function drawCrop(){
        cropCtx.clearRect(0,0,cropCanvas.width,cropCanvas.height);
        cropCtx.drawImage(cropImg,cropOffX,cropOffY,cropImg.width*cropScale,cropImg.height*cropScale);
        document.getElementById('fotoBase64').value=cropCanvas.toDataURL('image/jpeg',0.9);
    }
</script>
</head>
<body>
<div id="app-header"></div>
<div class="container" id="main-container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;" id="center-col">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > Comunidades
        </div>
        <div class="card" id="main-card">
            <div class="empty-state">Carregando...</div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
var _captchaAnswer = 0;

document.addEventListener('DOMContentLoaded', async function() {
    var urlParams = new URLSearchParams(window.location.search);
    var action = urlParams.get('action');
    var commId = urlParams.get('id');
    
    await loadLayout({ activePage: 'comunidades' });

    // Marcar notificação como lida se veio de read_notif
    var readNotif = urlParams.get('read_notif');
    if (readNotif) {
        fetch('/api/notificacoes/marcar-lida/' + readNotif, { method: 'POST' }).catch(function(){});
    }

    var viewParam = urlParams.get('view');

    if (action === 'create') {
        showCreateForm();
    } else if (commId && viewParam === 'membros') {
        showCommunityMembers(commId);
    } else if (commId && viewParam === 'config') {
        showCommunityConfig(commId);
    } else if (commId && viewParam === 'aprovar') {
        showApproveMembers(commId);
    } else if (commId) {
        showCommunityDetail(commId);
    } else {
        showMyCommunitiesList();
    }
});

function showCreateForm() {
    document.title = 'Yorkut - Criar Comunidade';
    document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Criar Comunidade';
    
    var a = Math.floor(Math.random() * 10) + 1;
    var b = Math.floor(Math.random() * 10) + 1;
    _captchaAnswer = a + b;
    
    var card = document.getElementById('main-card');
    card.innerHTML = 
        '<h1 class="orkut-name" style="font-size:20px; border-bottom:1px solid var(--line); padding-bottom:10px;">Criar nova comunidade</h1>' +
        '<div id="form-msg"></div>' +
        '<form id="create-form" onsubmit="return handleCreateCommunity(event)">' +
            '<table style="width:100%; font-size:11px;">' +
                '<tr><td style="width:120px; font-weight:bold; color:var(--title);">Nome:</td><td><input type="text" id="comm-nome" required style="width:100%; padding:6px; border:1px solid #a5bce3; margin-bottom:10px;" maxlength="100"></td></tr>' +
                '<tr>' +
                    '<td style="font-weight:bold; color:var(--title); vertical-align:top;">Foto Oficial:</td>' +
                    '<td>' +
                        '<input type="file" id="fileInput" accept="image/jpeg, image/png" style="width:100%;font-size:11px;margin-bottom:5px;">' +
                        '<div id="cropContainer" style="display:none; margin-bottom:10px;">' +
                            '<canvas id="cropCanvas" width="150" height="200" style="border:1px solid #ccc; cursor:move;"></canvas><br>' +
                            '<label>Zoom:</label> <input type="range" id="zoomRange" step="0.01" style="width:150px;"><br>' +
                            '<input type="hidden" id="fotoBase64">' +
                            '<span style="font-size:9px; color:#999;">Arraste a foto no quadro acima para ajustar.</span>' +
                        '</div>' +
                    '</td>' +
                '</tr>' +
                '<tr><td style="font-weight:bold; color:var(--title);">Categoria:</td><td>' +
                    '<select id="comm-categoria" style="width:100%; padding:6px; border:1px solid #a5bce3; margin-bottom:10px;">' +
                        '<optgroup label="🎭 Artes e Entretenimento">' +
                            '<option value="Filmes">Filmes</option><option value="Séries">Séries</option><option value="Novelas">Novelas</option>' +
                            '<option value="Programas de TV">Programas de TV</option><option value="Reality Shows">Reality Shows</option>' +
                            '<option value="Teatro">Teatro</option><option value="Humor">Humor</option><option value="Stand-up">Stand-up</option>' +
                            '<option value="Cultura Pop">Cultura Pop</option><option value="Famosos">Famosos</option><option value="Celebridades">Celebridades</option>' +
                            '<option value="Diretores">Diretores</option><option value="Atores">Atores</option><option value="Animações">Animações</option>' +
                            '<option value="Mangá / Anime">Mangá / Anime</option>' +
                        '</optgroup>' +
                        '<optgroup label="🎵 Música">' +
                            '<option value="Bandas">Bandas</option><option value="Cantores">Cantores</option><option value="DJs">DJs</option>' +
                            '<option value="Gêneros Musicais">Gêneros Musicais (Rock, Funk, etc)</option><option value="Letras de músicas">Letras de músicas</option>' +
                            '<option value="Instrumentos musicais">Instrumentos musicais</option><option value="Festivais">Festivais</option><option value="Shows">Shows</option>' +
                        '</optgroup>' +
                        '<optgroup label="🎮 Jogos">' +
                            '<option value="Jogos online">Jogos online</option><option value="MMORPG">MMORPG</option><option value="Jogos de navegador">Jogos de navegador</option>' +
                            '<option value="Consoles">Consoles</option><option value="Jogos de PC">Jogos de PC</option><option value="Dicas e Cheats">Dicas &amp; Cheats</option>' +
                            '<option value="Clãs">Clãs</option><option value="Game Designers">Game Designers</option><option value="Jogos do Orkut">Jogos do Orkut</option>' +
                        '</optgroup>' +
                        '<optgroup label="💻 Internet &amp; Tecnologia">' +
                            '<option value="Sites e Blogs">Sites e Blogs</option><option value="HTML e Web Design">HTML e Web Design</option>' +
                            '<option value="Programação">Programação</option><option value="Hackers">Hackers</option><option value="MSN Messenger">MSN Messenger</option>' +
                            '<option value="Fotolog">Fotolog</option><option value="YouTube">YouTube</option><option value="Downloads">Downloads</option>' +
                            '<option value="Lan Houses">Lan Houses</option><option value="Hardware e Software">Hardware e Softwares</option>' +
                        '</optgroup>' +
                        '<optgroup label="👥 Pessoas &amp; Relacionamentos">' +
                            '<option value="Amizade">Amizade</option><option value="Namoro e Casamento">Namoro e Casamento</option><option value="Ex-namorados">Ex-namorados</option>' +
                            '<option value="Ciúmes">Ciúmes</option><option value="Amor não correspondido">Amor não correspondido</option>' +
                            '<option value="Frases românticas">Frases românticas</option><option value="Relacionamento à distância">Relacionamento à distância</option>' +
                            '<option value="Solteiros e Casais">Solteiros e Casais</option><option value="Vida Real">Comunidades &quot;Vida Real&quot;</option>' +
                        '</optgroup>' +
                        '<optgroup label="🎓 Escola &amp; Educação">' +
                            '<option value="Escolas específicas">Escolas específicas</option><option value="Faculdades e Universidades">Faculdades e Universidades</option>' +
                            '<option value="Professores">Professores</option><option value="Vestibular e ENEM">Vestibular e ENEM</option>' +
                            '<option value="Concursos">Concursos</option><option value="Cursos técnicos e Intercâmbio">Cursos e Intercâmbio</option>' +
                        '</optgroup>' +
                        '<optgroup label="🏢 Empresas &amp; Negócios">' +
                            '<option value="Marcas famosas">Marcas famosas</option><option value="Empregos">Empregos</option><option value="Marketing">Marketing</option>' +
                            '<option value="Empreendedorismo e Vendas">Empreendedorismo e Vendas</option><option value="Publicidade">Publicidade</option><option value="Empresas específicas">Empresas específicas</option>' +
                        '</optgroup>' +
                        '<optgroup label="🏠 Casa &amp; Estilo de Vida">' +
                            '<option value="Culinária e Receitas">Culinária e Receitas</option><option value="Decoração">Decoração</option><option value="Moda e Beleza">Moda e Beleza</option>' +
                            '<option value="Cabelo e Tatuagem">Cabelo e Tatuagem</option><option value="Animais de estimação">Animais de estimação</option>' +
                        '</optgroup>' +
                        '<optgroup label="⚽ Esportes">' +
                            '<option value="Futebol e Clubes">Futebol e Clubes</option><option value="Seleções">Seleções</option><option value="Artes marciais">Artes marciais</option>' +
                            '<option value="Academia e Musculação">Academia e Musculação</option><option value="Skate e Surf">Skate e Surf</option><option value="Automobilismo">Automobilismo</option>' +
                        '</optgroup>' +
                        '<optgroup label="🌎 Países &amp; Regiões">' +
                            '<option value="Países e Estados">Países e Estados</option><option value="Cidades e Bairros">Cidades e Bairros</option><option value="Cultura e Orgulho">Cultura regional</option><option value="Turismo">Turismo</option>' +
                        '</optgroup>' +
                        '<optgroup label="✝ Religião &amp; Espiritualidade">' +
                            '<option value="Católicos">Católicos</option><option value="Evangélicos">Evangélicos</option><option value="Espíritas">Espíritas</option>' +
                            '<option value="Umbanda e Candomblé">Umbanda e Candomblé</option><option value="Budismo">Budismo</option><option value="Ateísmo">Ateísmo</option>' +
                            '<option value="Astrologia e Signos">Astrologia e Signos</option>' +
                        '</optgroup>' +
                    '</select>' +
                '</td></tr>' +
                '<tr><td style="font-weight:bold; color:var(--title);">Idioma:</td><td>' +
                    '<select id="comm-idioma" style="width:100%; padding:6px; border:1px solid #a5bce3; margin-bottom:10px;">' +
                        '<option value="Português">Português</option><option value="Inglês">Inglês</option><option value="Espanhol">Espanhol</option>' +
                    '</select>' +
                '</td></tr>' +
                '<tr><td style="font-weight:bold; color:var(--title);">Tipo:</td><td>' +
                    '<select id="comm-tipo" style="width:100%; padding:6px; border:1px solid #a5bce3; margin-bottom:10px;">' +
                        '<option value="publica">Pública (Qualquer um entra e vê fóruns/enquetes)</option>' +
                        '<option value="privada">Privada (Apenas com aprovação)</option>' +
                    '</select>' +
                '</td></tr>' +
                '<tr><td style="font-weight:bold; color:var(--title);">Local:</td><td><input type="text" id="comm-local" value="Brasil" style="width:100%; padding:6px; border:1px solid #a5bce3; margin-bottom:10px;"></td></tr>' +
                '<tr>' +
                    '<td style="font-weight:bold; color:var(--title); vertical-align:top;">Descrição:</td>' +
                    '<td>' +
                        '<div class="editor-toolbar">' +
                            '<button type="button" class="editor-btn" onclick="formatText(\'editorNew\',\'hiddenNew\',\'bold\')"><b>B</b></button>' +
                            '<button type="button" class="editor-btn" onclick="formatText(\'editorNew\',\'hiddenNew\',\'italic\')"><i>I</i></button>' +
                            '<button type="button" class="editor-btn" onclick="formatText(\'editorNew\',\'hiddenNew\',\'underline\')"><u>U</u></button>' +
                            '<button type="button" class="editor-btn" onclick="formatText(\'editorNew\',\'hiddenNew\',\'strikeThrough\')"><s>S</s></button>' +
                            '<button type="button" class="editor-btn" onclick="insertLink(\'editorNew\',\'hiddenNew\')">🔗 Link</button>' +
                            '<input type="color" onchange="formatText(\'editorNew\',\'hiddenNew\',\'foreColor\', this.value)" style="height:22px; width:28px; padding:0; border:none; cursor:pointer;" title="Cor do texto">' +
                        '</div>' +
                        '<div id="editorNew" class="editor-area" contenteditable="true" oninput="syncText(\'editorNew\',\'hiddenNew\')"></div>' +
                        '<input type="hidden" id="hiddenNew">' +
                    '</td>' +
                '</tr>' +
            '</table>' +
            '<div style="background:#f9f9f9; border:1px solid #ccc; padding:10px; margin-top:15px; border-radius:4px; text-align:center;">' +
                '<div style="font-size:12px; font-weight:bold; color:#555; margin-bottom:5px;">🔒 Anti-Spam: Quanto é ' + a + ' + ' + b + ' ?</div>' +
                '<input type="number" id="captcha-input" required style="width:80px; padding:6px; border:1px solid #aaa; text-align:center; font-weight:bold;" autocomplete="off">' +
            '</div>' +
            '<div style="text-align: right; margin-top:15px;">' +
                '<button type="submit" class="btn-action" style="padding: 10px 30px; font-size:14px;" id="btn-submit">Criar Comunidade</button>' +
            '</div>' +
        '</form>';

    initCropCanvas();
}

async function handleCreateCommunity(event) {
    event.preventDefault();
    
    var captchaVal = parseInt(document.getElementById('captcha-input').value);
    var msgDiv = document.getElementById('form-msg');
    
    if (captchaVal !== _captchaAnswer) {
        msgDiv.innerHTML = '<div class="msg-error">❌ Resposta errada no anti-spam. Tente novamente.</div>';
        return false;
    }

    var nome = document.getElementById('comm-nome').value.trim();
    var categoria = document.getElementById('comm-categoria').value;
    var tipo = document.getElementById('comm-tipo').value;
    var descricao = document.getElementById('hiddenNew') ? document.getElementById('hiddenNew').value : '';
    var idioma = document.getElementById('comm-idioma').value;
    var local = document.getElementById('comm-local').value.trim();
    var fotoBase64 = document.getElementById('fotoBase64') ? document.getElementById('fotoBase64').value : '';

    if (!nome || nome.length < 3) {
        msgDiv.innerHTML = '<div class="msg-error">O nome deve ter pelo menos 3 caracteres.</div>';
        return false;
    }

    var btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.textContent = 'Criando...';

    try {
        var resp = await fetch('/api/comunidades/criar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: nome, categoria: categoria, tipo: tipo, descricao: descricao, idioma: idioma, local: local, foto_base64: fotoBase64 })
        });
        var data = await resp.json();
        
        if (data.success) {
            msgDiv.innerHTML = '<div class="msg-success">✅ ' + data.message + ' Redirecionando...</div>';
            if (typeof showToast === 'function') showToast(data.message, 'success');
            setTimeout(function() {
                window.location.href = '/comunidades.php?id=' + data.id;
            }, 1500);
        } else {
            msgDiv.innerHTML = '<div class="msg-error">❌ ' + data.message + '</div>';
            btn.disabled = false;
            btn.textContent = 'Criar Comunidade';
        }
    } catch (err) {
        console.error('Erro:', err);
        msgDiv.innerHTML = '<div class="msg-error">Erro ao criar comunidade. Tente novamente.</div>';
        btn.disabled = false;
        btn.textContent = 'Criar Comunidade';
    }
    return false;
}

async function showCommunityDetail(commId) {
    try {
        var resp = await fetch('/api/comunidade/' + commId);
        var data = await resp.json();

        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<div class="msg-error">' + (data.message || 'Comunidade não encontrada.') + '</div>';
            return;
        }

        var c = data.community;
        var membrosCount = data.membrosCount;
        var membros = data.membros;
        var moderadores = data.moderadores || [];
        var isMember = data.isMember;
        var isOwner = data.isOwner;
        var isPending = data.isPending;
        var pendingCount = data.pendingCount || 0;
        var meuCargo = data.cargo;

        document.title = 'Yorkut - ' + c.nome;
        document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Comunidades > ' + escapeHtml(c.nome);

        // ===== LEFT COL =====
        var fotoSrc = c.foto || '/semfotocomunidade.jpg';
        var leftHtml = '<div class="card-left">';
        leftHtml += '<div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;">';
        leftHtml += '<img src="' + fotoSrc + '">';
        leftHtml += '</div>';

        leftHtml += '<div style="text-align:center; font-size:11px; margin-bottom:15px;">';
        leftHtml += '<strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(c.nome) + '</strong>';
        leftHtml += 'membros: ' + membrosCount;
        leftHtml += '</div>';

        // Owner buttons (Alterar Foto / Editar Perfil)
        if (isOwner) {
            leftHtml += '<div style="display:flex; flex-direction:column; gap:5px; margin-bottom:15px; border-bottom: 1px solid var(--line); padding-bottom: 15px;">';
            leftHtml += '<button class="btn-action" onclick="toggleForm(\'formEditCommInfo\')" style="background:#fff; border-color:#ccc; font-size:10px;">📷 Alterar Foto</button>';
            leftHtml += '<button class="btn-action" onclick="toggleForm(\'formEditCommInfo\')" style="background:#fff; border-color:#ccc; font-size:10px;">✏️ Editar Perfil</button>';
            leftHtml += '</div>';
        }

        leftHtml += '<ul class="menu-left hide-on-mobile" style="margin-top:0;">';

        if (!isMember && !isOwner) {
            if (isPending) {
                // Solicitação pendente
                leftHtml += '<li><button type="button" onclick="cancelPending(' + c.id + ')" class="menu-btn-action" style="color:#b08a00;"><span>⏳</span> solicitação enviada (cancelar)</button></li>';
            } else if (c.tipo === 'privada') {
                // Comunidade privada: solicitar aprovação
                leftHtml += '<li><button type="button" onclick="joinCommunity(' + c.id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>🔒</span> solicitar aprovação</button></li>';
            } else {
                // Comunidade pública: entrar direto
                leftHtml += '<li><button type="button" onclick="joinCommunity(' + c.id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>➕</span> participar da comunidade</button></li>';
            }
        }

        // Community link (active)
        leftHtml += '<li class="active"><a href="comunidades.php?id=' + c.id + '"><span>🏠</span> comunidade</a></li>';

        // Convidar amigos
        leftHtml += '<li><a href="comunidade_convidar_amigo.php?id=' + c.id + '"><span>📱</span> convidar amigos</a></li>';

        // Denunciar abuso (non-owner only)
        if (!isOwner) {
            leftHtml += '<li><button type="button" onclick="toggleForm(\'reportCommModalNew\')" class="menu-btn-action"><span>⚠️</span> denunciar abuso</button></li>';
        }

        // Fórum, Enquetes, Membros
        if (isMember || isOwner || c.tipo !== 'privada') {
            leftHtml += '<li><a href="/forum.php?id=' + c.id + '"><span>💬</span> fórum</a></li>';
            leftHtml += '<li><a href="/enquetes.php?id=' + c.id + '"><span>📊</span> enquetes</a></li>';
        }
        leftHtml += '<li><a href="comunidades.php?id=' + c.id + '&view=membros"><span>👥</span> membros</a></li>';

        // Staff
        leftHtml += '<li><a href="comunidades_staff.php?id=' + c.id + '"><span>👑</span> staff</a></li>';

        // Aprovar Membros (dono ou moderador, comunidade privada)
        if ((isOwner || meuCargo === 'moderador') && c.tipo === 'privada') {
            leftHtml += '<li><a href="comunidades.php?id=' + c.id + '&view=aprovar"><span>✅</span> aprovar membros';
            if (pendingCount > 0) leftHtml += ' <b style="background:#cc0000;color:#fff;border-radius:10px;padding:1px 6px;font-size:9px;margin-left:3px;">' + pendingCount + '</b>';
            leftHtml += '</a></li>';
        }

        // Sorteios
        if (isMember || isOwner || c.tipo !== 'privada') {
            leftHtml += '<li><a href="sorteio.php?id=' + c.id + '"><span>🎁</span> sorteios</a></li>';
        }

        // Configurações (owner only)
        if (isOwner) {
            leftHtml += '<li><a href="comunidades.php?id=' + c.id + '&view=config"><span>⚙️</span> configurações</a></li>';
        }

        // Sair (member but not owner)
        if (isMember && !isOwner) {
            leftHtml += '<li><button type="button" onclick="leaveCommunityDetail(' + c.id + ', \'' + escapeHtml(c.nome).replace(/\'/g, "\\'") + '\')" class="menu-btn-action" style="color:#cc0000;"><span>🚪</span> sair da comunidade</button></li>';
        }

        leftHtml += '</ul></div>';

        // Report modal (non-owner)
        if (!isOwner) {
            leftHtml += '<div id="reportCommModalNew" class="modal-overlay" style="display:none">';
            leftHtml += '<div class="modal-box">';
            leftHtml += '<h3 style="margin-top:0; color:var(--title);">⚠️ Denunciar Abuso</h3>';
            leftHtml += '<p style="color:#cc0000; font-size:10px; margin-bottom:10px;"><b>Atenção:</b> Falsas denúncias podem resultar no banimento da sua conta. Seja claro e objetivo.</p>';
            leftHtml += '<textarea id="report-motivo" maxlength="150" placeholder="Escreva o motivo da denúncia (Máx 150 caracteres)..." style="width:100%; padding:8px; border:1px solid #ccc; font-size:11px; margin-bottom:5px; height:80px;"></textarea>';
            leftHtml += '<div style="text-align:right; font-size:9px; color:#999; margin-bottom:10px;">Máx. 150 caracteres</div>';
            leftHtml += '<button type="button" onclick="denunciarComunidade()" class="btn-action" style="background:#cc0000; color:#fff; margin-bottom:5px; width:100%; border:none; padding:8px; cursor:pointer; border-radius:4px;">Enviar Denúncia</button>';
            leftHtml += '<button type="button" onclick="toggleForm(\'reportCommModalNew\')" class="btn-action" style="background:#fff; width:100%;">Cancelar</button>';
            leftHtml += '</div></div>';
        }

        // Transfer modal (owner)
        if (isOwner) {
            leftHtml += '<div id="transferModal" class="modal-overlay" style="display:none">';
            leftHtml += '<div class="modal-box">';
            leftHtml += '<h3 style="color:#cc0000; margin-top:0;">Atenção: Você é o dono!</h3>';
            leftHtml += '<p style="font-size:11px;">Para sair, transfira a posse.</p>';
            leftHtml += '<select id="transfer-novo-dono" style="width:100%; padding:8px; border:1px solid #ccc; font-size:11px; margin-bottom:15px;">';
            leftHtml += '<option value="">-- Novo Dono --</option>';
            for (var t = 0; t < membros.length; t++) {
                if (String(membros[t].id) !== String(c.dono_id)) {
                    leftHtml += '<option value="' + membros[t].id + '">' + escapeHtml(membros[t].nome) + '</option>';
                }
            }
            leftHtml += '</select>';
            leftHtml += '<button type="button" onclick="transferOwnership(' + c.id + ')" class="btn-action" style="background:#cc0000; color:#fff; border-color:#b71c1c; margin-bottom:5px; width:100%;">Transferir e Sair</button>';
            leftHtml += '<button type="button" onclick="toggleForm(\'transferModal\')" class="btn-action" style="background:#fff; width:100%;">Cancelar</button>';
            leftHtml += '</div></div>';
        }

        document.getElementById('app-left-col').innerHTML = leftHtml;

        // ===== CENTER COL =====
        var cardHtml = '';

        // Owner edit form (hidden by default)
        if (isOwner) {
            cardHtml += '<div id="formEditCommInfo" style="display:none; background:#fefefe; padding:15px; border:1px solid #ccc; margin-bottom:20px; border-radius:6px;">';
            cardHtml += '<h3 style="margin-top:0; color:var(--title); border-bottom:1px dashed #ccc; padding-bottom:5px;">Editar Perfil da Comunidade</h3>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Nova Imagem (Opcional):</label>';
            cardHtml += '<input type="file" id="fileInput" accept="image/jpeg, image/png" style="width:100%;font-size:11px;margin-bottom:10px;">';
            cardHtml += '<div id="cropContainer" style="display:none; margin-bottom:10px;">';
            cardHtml += '<canvas id="cropCanvas" width="150" height="200" style="border:1px solid #ccc; cursor:move;"></canvas><br>';
            cardHtml += '<input type="range" id="zoomRange" step="0.01" style="width:150px;"><br>';
            cardHtml += '<input type="hidden" id="fotoBase64">';
            cardHtml += '</div>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Nome:</label>';
            cardHtml += '<input type="text" id="edit-comm-nome" value="' + escapeHtml(c.nome) + '" required style="width:100%; padding:6px; border:1px solid #ccc; margin-bottom:10px;">';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Categoria:</label>';
            cardHtml += '<select id="edit-comm-categoria" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += buildCategoryOptions(c.categoria);
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Idioma:</label>';
            cardHtml += '<select id="edit-comm-idioma" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += '<option value="Português"' + (c.idioma === 'Português' ? ' selected' : '') + '>Português</option>';
            cardHtml += '<option value="Inglês"' + (c.idioma === 'Inglês' ? ' selected' : '') + '>Inglês</option>';
            cardHtml += '<option value="Espanhol"' + (c.idioma === 'Espanhol' ? ' selected' : '') + '>Espanhol</option>';
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Tipo:</label>';
            cardHtml += '<select id="edit-comm-tipo" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += '<option value="Pública"' + (c.tipo === 'publica' || c.tipo === 'Pública' ? ' selected' : '') + '>Pública</option>';
            cardHtml += '<option value="Privada"' + (c.tipo === 'privada' || c.tipo === 'Privada' ? ' selected' : '') + '>Privada</option>';
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Local:</label>';
            cardHtml += '<input type="text" id="edit-comm-local" value="' + escapeHtml(c.local) + '" required style="width:100%; padding:6px; border:1px solid #ccc; margin-bottom:10px;">';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Descrição:</label>';
            cardHtml += '<div class="editor-toolbar">';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'bold\')"><b>B</b></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'italic\')"><i>I</i></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'underline\')"><u>U</u></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'strikeThrough\')"><s>S</s></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="insertLink(\'editorEdit\',\'hiddenEdit\')">🔗 Link</button>';
            cardHtml += '<input type="color" onchange="formatText(\'editorEdit\',\'hiddenEdit\',\'foreColor\', this.value)" style="height:22px; width:28px; padding:0; border:none; cursor:pointer;" title="Cor do texto">';
            cardHtml += '</div>';
            cardHtml += '<div id="editorEdit" class="editor-area" contenteditable="true" oninput="syncText(\'editorEdit\',\'hiddenEdit\')">' + (c.descricao || '') + '</div>';
            cardHtml += '<input type="hidden" id="hiddenEdit">';

            cardHtml += '<div style="margin-top:10px;">';
            cardHtml += '<button type="button" onclick="saveEditCommunity(' + c.id + ')" class="btn-action">Salvar Alterações</button>';
            cardHtml += ' <button type="button" onclick="toggleForm(\'formEditCommInfo\')" class="btn-action" style="background:#fff; color:#666; border-color:#ccc;">Cancelar</button>';
            cardHtml += '</div>';

            cardHtml += '</div>';
        }

        // Title bar
        cardHtml += '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:5px; margin-bottom:15px;">';
        cardHtml += '<h1 class="orkut-name" style="font-size:22px; margin:0;">' + escapeHtml(c.nome) + '</h1>';
        cardHtml += '<a class="btn-share" onclick="copiarLink(' + c.id + ')">🔗 Compartilhar</a>';
        cardHtml += '</div>';

        // Info table
        var descText = c.descricao || '';
        var criadoEm = formatDateFull(c.criado_em);

        cardHtml += '<table class="info-table">';
        cardHtml += '<tr><td class="info-label">descrição:</td><td style="line-height:1.5; font-family:Tahoma; font-size:12px; color:#333;">' + descText + '</td></tr>';
        cardHtml += '<tr><td class="info-label">idioma:</td><td>' + escapeHtml(c.idioma) + '</td></tr>';
        cardHtml += '<tr><td class="info-label">categoria:</td><td>' + escapeHtml(c.categoria) + '</td></tr>';
        cardHtml += '<tr><td class="info-label">tipo:</td><td>' + (c.tipo === 'privada' ? '🔒 Privada' : 'Pública') + '</td></tr>';
        cardHtml += '<tr><td class="info-label">dono:</td><td><a href="profile.php?uid=' + c.dono_id + '">👑 ' + escapeHtml(c.dono_nome) + '</a></td></tr>';
        var modText = 'Nenhum';
        if (moderadores.length > 0) {
            modText = moderadores.map(function(m) {
                return '<a href="profile.php?uid=' + m.id + '" style="color:var(--link);">🛡️ ' + escapeHtml(m.nome) + '</a>';
            }).join(', ');
        }
        cardHtml += '<tr><td class="info-label">moderadores:</td><td>' + modText + '</td></tr>';
        cardHtml += '<tr><td class="info-label">local:</td><td>' + escapeHtml(c.local) + '</td></tr>';
        cardHtml += '<tr><td class="info-label">criado em:</td><td>' + criadoEm + '</td></tr>';
        cardHtml += '<tr><td class="info-label">membros:</td><td><a href="?id=' + c.id + '&view=membros">' + membrosCount + ' membros</a></td></tr>';
        cardHtml += '</table>';

        // Mini-box Últimos Fóruns (oculto para não-membros de comunidades privadas)
        if (isMember || isOwner || c.tipo !== 'privada') {
            cardHtml += '<div class="mini-box-list">';
            cardHtml += '<h3>💬 Últimos Fóruns <a href="/forum.php?id=' + c.id + '" style="font-weight:normal; font-size:10px;">ver todos</a></h3>';
            cardHtml += '<div id="mini-forum-box" style="font-size:10px;color:#999; padding:10px;">Carregando...</div>';
            cardHtml += '</div>';

            // Mini-box Últimas Enquetes
            cardHtml += '<div class="mini-box-list">';
            cardHtml += '<h3>📊 Últimas Enquetes <a href="/enquetes.php?id=' + c.id + '" style="font-weight:normal; font-size:10px;">ver todas</a></h3>';
            cardHtml += '<div id="mini-enquetes-box" style="font-size:10px;color:#999; padding:10px;">Carregando...</div>';
            cardHtml += '</div>';
        }

        document.getElementById('main-card').innerHTML = cardHtml;

        // Load recent forum topics in mini-box
        if (isMember || isOwner || c.tipo !== 'privada') {
            carregarMiniForuns(c.id);
            carregarMiniEnquetes(c.id);
        }

        // Init crop canvas if owner
        if (isOwner) {
            initCropCanvas();
            // Init editor content
            if (document.getElementById('editorEdit')) syncText('editorEdit', 'hiddenEdit');
        }

        // ===== RIGHT COL =====
        var rightCol = document.createElement('div');
        rightCol.className = 'right-col';
        var sideHtml = '<div class="box-sidebar">';
        sideHtml += '<div class="box-title">membros (' + membrosCount + ') <a href="comunidades.php?id=' + c.id + '&view=membros">ver todos</a></div>';
        sideHtml += '<div class="grid" style="grid-template-columns: repeat(3, 1fr);">';

        for (var i = 0; i < membros.length; i++) {
            var m = membros[i];
            var mFoto = m.foto_perfil ? m.foto_perfil : '';
            var mNome = escapeHtml(m.nome);
            if (mNome.length > 10) mNome = mNome.substring(0, 8) + '..';
            sideHtml += '<div class="grid-item">';
            sideHtml += '<a href="profile.php?uid=' + m.id + '">';
            sideHtml += '<div class="grid-thumb">';
            if (mFoto) {
                sideHtml += '<img src="' + mFoto + '">';
            } else {
                sideHtml += '<div style="width:100%;height:100%;background:#e4ebf5;display:flex;align-items:center;justify-content:center;font-size:20px;">👤</div>';
            }
            sideHtml += '</div>';
            sideHtml += mNome;
            sideHtml += '</a></div>';
        }

        sideHtml += '</div></div>';

        // Comunidades sugeridas (owner only)
        if (isOwner) {
            sideHtml += '<div class="box-sidebar">';
            sideHtml += '<div class="box-title" style="display:flex; justify-content:space-between; align-items:center;">comunidades sugeridas';
            sideHtml += ' <a href="javascript:void(0);" onclick="toggleSugeridas()" style="font-size:10px; font-weight:normal; color:#fff; text-decoration:underline;">[editar]</a>';
            sideHtml += '</div>';
            sideHtml += '<div id="editSugeridasBox" style="display:none; background:#f4f7fc; padding:10px; border-bottom:1px solid #c0d0e6; margin-bottom:10px;">';
            sideHtml += '<div style="display:flex; gap:5px; margin:0;">';
            sideHtml += '<input type="text" id="related-url" placeholder="Cole a URL da Comunidade aqui..." style="flex:1; padding:4px; font-size:10px; border:1px solid #ccc; border-radius:3px; outline:none;">';
            sideHtml += '<button type="button" onclick="alert(\'Em desenvolvimento\')" style="background:#3b5998; color:#fff; border:none; padding:4px 8px; border-radius:3px; font-size:10px; font-weight:bold; cursor:pointer;">Adicionar</button>';
            sideHtml += '</div>';
            sideHtml += '<div style="font-size:9px; color:#666; font-style:italic; margin-top:5px; line-height:1.3;">Copie e cole o <b>Link (URL)</b> da comunidade que você deseja sugerir. (Limite: 6)</div>';
            sideHtml += '</div>';
            sideHtml += '<div style="padding:20px 10px; text-align:center; color:#999; font-size:11px; font-style:italic;">Você ainda não indicou nenhuma comunidade. Clique em [editar] acima para adicionar.</div>';
            sideHtml += '</div>';
        }

        rightCol.innerHTML = sideHtml;

        var centerCol = document.getElementById('center-col');
        var existingRight = document.querySelector('.right-col');
        if (existingRight) existingRight.remove();
        centerCol.parentNode.insertBefore(rightCol, centerCol.nextSibling);

    } catch (err) {
        console.error('Erro ao carregar comunidade:', err);
        document.getElementById('main-card').innerHTML = '<div class="msg-error">Erro ao carregar comunidade.</div>';
    }
}

async function showCommunityMembers(commId) {
    try {
        var resp = await fetch('/api/comunidade/' + commId + '/membros');
        var data = await resp.json();

        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<div class="msg-error">' + (data.message || 'Comunidade não encontrada.') + '</div>';
            return;
        }

        var c = data.community;
        var membrosCount = data.membrosCount;
        var membros = data.membros;
        var isMember = data.isMember;
        var isOwner = data.isOwner;
        var isPending = data.isPending;
        var pendingCount = data.pendingCount || 0;
        var meuCargo = data.cargo;
        var loggedUserId = data.loggedUserId;
        var canKick = isOwner || meuCargo === 'moderador';

        document.title = 'Yorkut - ' + c.nome;
        document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Comunidades > ' + escapeHtml(c.nome);

        // ===== LEFT COL =====
        var fotoSrc = c.foto || '/semfotocomunidade.jpg';
        var leftHtml = '<div class="card-left">';
        leftHtml += '<div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;">';
        leftHtml += '<img src="' + fotoSrc + '">';
        leftHtml += '</div>';

        leftHtml += '<div style="text-align:center; font-size:11px; margin-bottom:15px;">';
        leftHtml += '<strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(c.nome) + '</strong>';
        leftHtml += 'membros: ' + membrosCount;
        leftHtml += '</div>';

        if (isOwner) {
            leftHtml += '<div style="display:flex; flex-direction:column; gap:5px; margin-bottom:15px; border-bottom: 1px solid var(--line); padding-bottom: 15px;">';
            leftHtml += '<button class="btn-action" onclick="toggleForm(\'formEditCommFoto\')" style="background:#fff; border-color:#ccc; font-size:10px;">📷 Alterar Foto</button>';
            leftHtml += '<button class="btn-action" onclick="toggleForm(\'formEditCommInfo\')" style="background:#fff; border-color:#ccc; font-size:10px;">✏️ Editar Perfil</button>';
            leftHtml += '</div>';
        }

        leftHtml += '<ul class="menu-left hide-on-mobile" style="margin-top:0;">';

        if (!isMember && !isOwner) {
            if (isPending) {
                leftHtml += '<li><button type="button" onclick="cancelPending(' + c.id + ')" class="menu-btn-action" style="color:#b08a00;"><span>⏳</span> solicitação enviada (cancelar)</button></li>';
            } else if (c.tipo === 'privada') {
                leftHtml += '<li><button type="button" onclick="joinCommunity(' + c.id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>🔒</span> solicitar aprovação</button></li>';
            } else {
                leftHtml += '<li><button type="button" onclick="joinCommunity(' + c.id + ')" class="menu-btn-action" style="color:#2a6b2a;"><span>➕</span> participar da comunidade</button></li>';
            }
        }

        leftHtml += '<li class=""><a href="comunidades.php?id=' + c.id + '"><span>🏠</span> comunidade</a></li>';
        leftHtml += '<li><a href="comunidade_convidar_amigo.php?id=' + c.id + '"><span>📱</span> convidar amigos</a></li>';

        if (!isOwner) {
            leftHtml += '<li><button type="button" onclick="toggleForm(\'reportCommModalNew\')" class="menu-btn-action"><span>⚠️</span> denunciar abuso</button></li>';
        }

        if (isMember || isOwner || c.tipo !== 'privada') {
            leftHtml += '<li class=""><a href="/forum.php?id=' + c.id + '"><span>💬</span> fórum</a></li>';
            leftHtml += '<li class=""><a href="/enquetes.php?id=' + c.id + '"><span>📊</span> enquetes</a></li>';
        }
        leftHtml += '<li class="active"><a href="comunidades.php?id=' + c.id + '&view=membros"><span>👥</span> membros</a></li>';
        leftHtml += '<li class=""><a href="comunidades_staff.php?id=' + c.id + '"><span>👑</span> staff</a></li>';

        // Aprovar Membros (dono ou moderador, comunidade privada)
        if ((isOwner || meuCargo === 'moderador') && c.tipo === 'privada') {
            leftHtml += '<li><a href="comunidades.php?id=' + c.id + '&view=aprovar"><span>✅</span> aprovar membros';
            if (pendingCount > 0) leftHtml += ' <b style="background:#cc0000;color:#fff;border-radius:10px;padding:1px 6px;font-size:9px;margin-left:3px;">' + pendingCount + '</b>';
            leftHtml += '</a></li>';
        }

        if (isMember || isOwner || c.tipo !== 'privada') {
            leftHtml += '<li><a href="sorteio.php?id=' + c.id + '"><span>🎁</span> sorteios</a></li>';
        }

        if (isOwner) {
            leftHtml += '<li><a href="comunidades.php?id=' + c.id + '&view=config"><span>⚙️</span> configurações</a></li>';
        }

        if (isMember && !isOwner) {
            leftHtml += '<li><button type="button" onclick="leaveCommunityDetail(' + c.id + ', \'' + escapeHtml(c.nome).replace(/\'/g, "\\'") + '\')" class="menu-btn-action" style="color:#cc0000;"><span>🚪</span> sair da comunidade</button></li>';
        }

        leftHtml += '</ul></div>';

        // Report modal (non-owner)
        if (!isOwner) {
            leftHtml += '<div id="reportCommModalNew" class="modal-overlay" style="display:none">';
            leftHtml += '<div class="modal-box">';
            leftHtml += '<h3 style="margin-top:0; color:var(--title);">⚠️ Denunciar Abuso</h3>';
            leftHtml += '<p style="color:#cc0000; font-size:10px; margin-bottom:10px;"><b>Atenção:</b> Falsas denúncias podem resultar no banimento da sua conta. Seja claro e objetivo.</p>';
            leftHtml += '<textarea id="report-motivo" maxlength="150" placeholder="Escreva o motivo da denúncia (Máx 150 caracteres)..." style="width:100%; padding:8px; border:1px solid #ccc; font-size:11px; margin-bottom:5px; height:80px;"></textarea>';
            leftHtml += '<div style="text-align:right; font-size:9px; color:#999; margin-bottom:10px;">Máx. 150 caracteres</div>';
            leftHtml += '<button type="button" onclick="denunciarComunidade()" class="btn-action" style="background:#cc0000; color:#fff; margin-bottom:5px; width:100%; border:none; padding:8px; cursor:pointer; border-radius:4px;">Enviar Denúncia</button>';
            leftHtml += '<button type="button" onclick="toggleForm(\'reportCommModalNew\')" class="btn-action" style="background:#fff; width:100%;">Cancelar</button>';
            leftHtml += '</div></div>';
        }

        // Transfer modal (owner)
        if (isOwner) {
            leftHtml += '<div id="transferModal" class="modal-overlay" style="display:none">';
            leftHtml += '<div class="modal-box">';
            leftHtml += '<h3 style="color:#cc0000; margin-top:0;">Atenção: Você é o dono!</h3>';
            leftHtml += '<p style="font-size:11px;">Para sair, transfira a posse.</p>';
            leftHtml += '<select id="transfer-novo-dono" style="width:100%; padding:8px; border:1px solid #ccc; font-size:11px; margin-bottom:15px;">';
            leftHtml += '<option value="">-- Novo Dono --</option>';
            for (var t = 0; t < membros.length; t++) {
                if (String(membros[t].id) !== String(c.dono_id)) {
                    leftHtml += '<option value="' + membros[t].id + '">' + escapeHtml(membros[t].nome) + '</option>';
                }
            }
            leftHtml += '</select>';
            leftHtml += '<button type="button" onclick="transferOwnership(' + c.id + ')" class="btn-action" style="background:#cc0000; color:#fff; border-color:#b71c1c; margin-bottom:5px; width:100%;">Transferir e Sair</button>';
            leftHtml += '<button type="button" onclick="toggleForm(\'transferModal\')" class="btn-action" style="background:#fff; width:100%;">Cancelar</button>';
            leftHtml += '</div></div>';
        }

        document.getElementById('app-left-col').innerHTML = leftHtml;

        // ===== CENTER COL =====
        var cardHtml = '';

        // Owner edit form (hidden by default)
        if (isOwner) {
            cardHtml += '<div id="formEditCommInfo" class="hidden-form" style="display:none; background:#fefefe; padding:15px; border:1px solid #ccc; margin-bottom:20px; border-radius:6px;">';
            cardHtml += '<h3 style="margin-top:0; color:var(--title); border-bottom:1px dashed #ccc; padding-bottom:5px;">Editar Perfil da Comunidade</h3>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Nova Imagem (Opcional):</label>';
            cardHtml += '<input type="file" id="fileInput" accept="image/jpeg, image/png" style="width:100%;font-size:11px;margin-bottom:10px;">';
            cardHtml += '<div id="cropContainer" style="display:none; margin-bottom:10px;">';
            cardHtml += '<canvas id="cropCanvas" width="150" height="200" style="border:1px solid #ccc; cursor:move;"></canvas><br>';
            cardHtml += '<input type="range" id="zoomRange" step="0.01" style="width:150px;"><br>';
            cardHtml += '<input type="hidden" id="fotoBase64">';
            cardHtml += '</div>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Nome:</label>';
            cardHtml += '<input type="text" id="edit-comm-nome" value="' + escapeHtml(c.nome) + '" required style="width:100%; padding:6px; border:1px solid #ccc; margin-bottom:10px;">';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Categoria:</label>';
            cardHtml += '<select id="edit-comm-categoria" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += buildCategoryOptions(c.categoria || 'Geral');
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Idioma:</label>';
            cardHtml += '<select id="edit-comm-idioma" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += '<option value="Português"' + ((c.idioma || 'Português') === 'Português' ? ' selected' : '') + '>Português</option>';
            cardHtml += '<option value="Inglês"' + ((c.idioma || '') === 'Inglês' ? ' selected' : '') + '>Inglês</option>';
            cardHtml += '<option value="Espanhol"' + ((c.idioma || '') === 'Espanhol' ? ' selected' : '') + '>Espanhol</option>';
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Tipo:</label>';
            cardHtml += '<select id="edit-comm-tipo" style="margin-bottom:10px; width:100%; padding:6px; border:1px solid #a5bce3;">';
            cardHtml += '<option value="publica"' + ((c.tipo === 'publica' || c.tipo === 'Pública') ? ' selected' : '') + '>Pública</option>';
            cardHtml += '<option value="privada"' + ((c.tipo === 'privada' || c.tipo === 'Privada') ? ' selected' : '') + '>Privada</option>';
            cardHtml += '</select>';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Local:</label>';
            cardHtml += '<input type="text" id="edit-comm-local" value="' + escapeHtml(c.local || 'Brasil') + '" required style="width:100%; padding:6px; border:1px solid #ccc; margin-bottom:10px;">';

            cardHtml += '<label style="font-size:11px;font-weight:bold;color:#666;">Descrição:</label>';
            cardHtml += '<div class="editor-toolbar">';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'bold\')"><b>B</b></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'italic\')"><i>I</i></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'underline\')"><u>U</u></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="formatText(\'editorEdit\',\'hiddenEdit\',\'strikeThrough\')"><s>S</s></button>';
            cardHtml += '<button type="button" class="editor-btn" onclick="insertLink(\'editorEdit\',\'hiddenEdit\')">🔗 Link</button>';
            cardHtml += '<input type="color" onchange="formatText(\'editorEdit\',\'hiddenEdit\',\'foreColor\', this.value)" style="height:22px; width:28px; padding:0; border:none; cursor:pointer;" title="Cor do texto">';
            cardHtml += '</div>';
            cardHtml += '<div id="editorEdit" class="editor-area" contenteditable="true" oninput="syncText(\'editorEdit\',\'hiddenEdit\')">' + (c.descricao || '') + '</div>';
            cardHtml += '<input type="hidden" id="hiddenEdit">';

            cardHtml += '<div style="margin-top:10px;">';
            cardHtml += '<button type="button" onclick="saveEditCommunity(' + c.id + ')" class="btn-action">Salvar Alterações</button>';
            cardHtml += ' <button type="button" onclick="toggleForm(\'formEditCommInfo\')" class="btn-action" style="background:#fff; color:#666; border-color:#ccc;">Cancelar</button>';
            cardHtml += '</div>';

            cardHtml += '</div>';
        }

        // Title
        cardHtml += '<h1 class="orkut-name" style="font-size:20px; border-bottom:1px solid var(--line); padding-bottom:10px;">Todos os Membros (' + membrosCount + ')</h1>';

        // Search bar
        cardHtml += '<div style="margin:10px 0 5px;">';
        cardHtml += '<input type="text" id="busca-membro" placeholder="Buscar membro pelo nome..." oninput="filtrarMembros()" style="width:100%; padding:7px 10px; border:1px solid #bcc7d6; border-radius:4px; font-size:12px; outline:none; box-sizing:border-box;">';
        cardHtml += '</div>';

        if (membros.length === 0) {
            cardHtml += '<div style="padding:30px; text-align:center; color:#999; font-size:12px; font-style:italic;">Nenhum membro encontrado.</div>';
        } else {
            // Members grid (4 columns, matching original)
            cardHtml += '<div id="membros-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-top:15px; text-align:center;">';
            for (var i = 0; i < membros.length; i++) {
                var m = membros[i];
                var mFoto = m.foto_perfil ? m.foto_perfil : '/perfilsemfoto.jpg';
                var mNome = escapeHtml(m.nome);
                var isDonoTag = String(m.id) === String(c.dono_id);
                var isModTag = (m.cargo === 'moderador');
                var isSelf = String(m.id) === String(loggedUserId);
                var canKickThis = canKick && !isDonoTag && !isModTag && !isSelf;

                cardHtml += '<div class="membro-item" data-nome="' + mNome.toLowerCase() + '" style="position:relative;">';
                cardHtml += '<a href="profile.php?uid=' + m.id + '" style="color:var(--link); font-size:11px; text-decoration:none;">';
                cardHtml += '<img src="' + mFoto + '" style="width:60px; height:60px; object-fit:cover; border:1px solid #ccc; border-radius:3px;"><br>';
                cardHtml += mNome;
                cardHtml += '</a>';
                if (isDonoTag) {
                    cardHtml += '<div style="font-size:9px;color:#cc0000;font-weight:bold;margin-top:2px;">Dono</div>';
                } else if (isModTag) {
                    cardHtml += '<div style="font-size:9px;color:#2a6b2a;font-weight:bold;margin-top:2px;">Moderador</div>';
                }
                if (canKickThis) {
                    cardHtml += '<div style="margin-top:4px;"><button onclick="abrirModalExpulsar(' + c.id + ', \'' + m.id + '\', \'' + mNome.replace(/\'/g, "\\'") + '\')" style="background:#cc0000; color:#fff; border:none; border-radius:3px; font-size:9px; padding:2px 6px; cursor:pointer;" title="Expulsar membro">✕ expulsar</button></div>';
                }
                cardHtml += '</div>';
            }
            cardHtml += '</div>';
        }

        document.getElementById('main-card').innerHTML = cardHtml;

        // Init crop canvas if owner
        if (isOwner) {
            initCropCanvas();
            if (document.getElementById('editorEdit')) syncText('editorEdit', 'hiddenEdit');
        }

        // ===== RIGHT COL =====
        var rightCol = document.createElement('div');
        rightCol.className = 'right-col';
        var sideHtml = '<div class="box-sidebar">';
        sideHtml += '<div class="box-title">membros (' + membrosCount + ') <a href="comunidades.php?id=' + c.id + '&view=membros">ver todos</a></div>';

        sideHtml += '<div class="grid" style="grid-template-columns: repeat(3, 1fr);">';
        var sideMax = Math.min(membros.length, 9);
        for (var j = 0; j < sideMax; j++) {
            var sm = membros[j];
            var smFoto = sm.foto_perfil ? sm.foto_perfil : '';
            var smNome = escapeHtml(sm.nome);
            if (smNome.length > 10) smNome = smNome.substring(0, 8) + '..';
            sideHtml += '<div class="grid-item">';
            sideHtml += '<a href="profile.php?uid=' + sm.id + '">';
            sideHtml += '<div class="grid-thumb">';
            if (smFoto) {
                sideHtml += '<img src="' + smFoto + '">';
            } else {
                sideHtml += '<img src="/perfilsemfoto.jpg">';
            }
            sideHtml += '</div>';
            sideHtml += smNome;
            sideHtml += '</a></div>';
        }
        sideHtml += '</div></div>';

        // Comunidades sugeridas (owner only)
        if (isOwner) {
            sideHtml += '<div class="box-sidebar">';
            sideHtml += '<div class="box-title" style="display:flex; justify-content:space-between; align-items:center;">comunidades sugeridas';
            sideHtml += ' <a href="javascript:void(0);" onclick="toggleSugeridas()" style="font-size:10px; font-weight:normal; color:#fff; text-decoration:underline;">[editar]</a>';
            sideHtml += '</div>';
            sideHtml += '<div id="editSugeridasBox" style="display:none; background:#f4f7fc; padding:10px; border-bottom:1px solid #c0d0e6; margin-bottom:10px;">';
            sideHtml += '<div style="display:flex; gap:5px; margin:0;">';
            sideHtml += '<input type="text" id="related-url" placeholder="Cole a URL da Comunidade aqui..." style="flex:1; padding:4px; font-size:10px; border:1px solid #ccc; border-radius:3px; outline:none;">';
            sideHtml += '<button type="button" onclick="alert(\'Em desenvolvimento\')" style="background:#3b5998; color:#fff; border:none; padding:4px 8px; border-radius:3px; font-size:10px; font-weight:bold; cursor:pointer;">Adicionar</button>';
            sideHtml += '</div>';
            sideHtml += '<div style="font-size:9px; color:#666; font-style:italic; margin-top:5px; line-height:1.3;">Copie e cole o <b>Link (URL)</b> da comunidade que você deseja sugerir. (Limite: 6)</div>';
            sideHtml += '</div>';
            sideHtml += '<div style="padding:20px 10px; text-align:center; color:#999; font-size:11px; font-style:italic;">Você ainda não indicou nenhuma comunidade. Clique em [editar] acima para adicionar.</div>';
            sideHtml += '</div>';
        }

        rightCol.innerHTML = sideHtml;
        var centerCol = document.getElementById('center-col');
        var existingRight = document.querySelector('.right-col');
        if (existingRight) existingRight.remove();
        centerCol.parentNode.insertBefore(rightCol, centerCol.nextSibling);

    } catch (err) {
        console.error('Erro ao carregar membros:', err);
        document.getElementById('main-card').innerHTML = '<div class="msg-error">Erro ao carregar membros.</div>';
    }
}

function formatDateFull(dateStr) {
    if (!dateStr) return '';
    var meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    // Parse directly from 'YYYY-MM-DD HH:MM:SS' without timezone conversion
    var parts = dateStr.split(/[\-\s:]/);
    if (parts.length >= 5) {
        var day = parseInt(parts[2], 10);
        var month = parseInt(parts[1], 10) - 1;
        var year = parts[0];
        var hour = parts[3];
        var min = parts[4];
        return String(day).padStart(2,'0') + ' de ' + meses[month] + ' de ' + year + ' as ' + hour + ':' + min;
    }
    var d = new Date(dateStr);
    return String(d.getDate()).padStart(2,'0') + ' de ' + meses[d.getMonth()] + ' de ' + d.getFullYear() + ' as ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}

function copiarLink(id) {
    var url = window.location.origin + '/comunidades.php?id=' + id;
    var tempInput = document.createElement('input'); tempInput.value = url; document.body.appendChild(tempInput);
    tempInput.select(); document.execCommand('copy'); document.body.removeChild(tempInput);
    if (typeof showToast === 'function') showToast('Link copiado!', 'success');
    else alert('🔗 Link da comunidade copiado!');
}

function toggleForm(id) { var e = document.getElementById(id); if(!e) return; var vis = window.getComputedStyle(e).display; e.style.display = (vis === 'none') ? 'block' : 'none'; }

async function denunciarComunidade() {
    var motivo = document.getElementById('report-motivo');
    if (!motivo || !motivo.value.trim()) {
        if (typeof showToast === 'function') showToast('Preencha o motivo da denúncia.', 'error');
        return;
    }
    // Pegar o comunidade_id da URL
    var params = new URLSearchParams(window.location.search);
    var commId = params.get('id');
    if (!commId) {
        if (typeof showToast === 'function') showToast('Comunidade não identificada.', 'error');
        return;
    }
    try {
        var resp = await fetch('/api/denunciar-comunidade', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId, motivo: motivo.value.trim() })
        });
        var data = await resp.json();
        if (data.success) {
            if (typeof showToast === 'function') showToast(data.message || 'Denúncia enviada!', 'success');
            toggleForm('reportCommModalNew');
            motivo.value = '';
            // Redirecionar para a denúncia criada
            setTimeout(function() {
                window.location.href = '/configuracoes.php?denuncias=1&dcid=' + data.denunciaId;
            }, 1200);
        } else {
            if (typeof showToast === 'function') showToast(data.message || 'Erro ao enviar denúncia.', 'error');
            else alert(data.message || 'Erro ao enviar denúncia.');
        }
    } catch (err) {
        if (typeof showToast === 'function') showToast('Erro de conexão.', 'error');
        else alert('Erro de conexão.');
    }
}

function toggleSugeridas() {
    var box = document.getElementById('editSugeridasBox');
    if (box) box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
}

function buildCategoryOptions(selected) {
    var cats = {
        '🎭 Artes e Entretenimento': ['Filmes','Séries','Novelas','Programas de TV','Reality Shows','Teatro','Humor','Stand-up','Cultura Pop','Famosos','Celebridades','Diretores','Atores','Animações','Mangá / Anime'],
        '🎵 Música': ['Bandas','Cantores','DJs','Gêneros Musicais','Letras de músicas','Instrumentos musicais','Festivais','Shows'],
        '🎮 Jogos': ['Jogos online','MMORPG','Jogos de navegador','Consoles','Jogos de PC','Dicas e Cheats','Clãs','Game Designers','Jogos do Orkut'],
        '💻 Internet &amp; Tecnologia': ['Sites e Blogs','HTML e Web Design','Programação','Hackers','MSN Messenger','Fotolog','YouTube','Downloads','Lan Houses','Hardware e Software'],
        '👥 Pessoas &amp; Relacionamentos': ['Amizade','Namoro e Casamento','Ex-namorados','Ciúmes','Amor não correspondido','Frases românticas','Relacionamento à distância','Solteiros e Casais','Vida Real'],
        '🎓 Escola &amp; Educação': ['Escolas específicas','Faculdades e Universidades','Professores','Vestibular e ENEM','Concursos','Cursos técnicos e Intercâmbio'],
        '🏢 Empresas &amp; Negócios': ['Marcas famosas','Empregos','Marketing','Empreendedorismo e Vendas','Publicidade','Empresas específicas'],
        '🏠 Casa &amp; Estilo de Vida': ['Culinária e Receitas','Decoração','Moda e Beleza','Cabelo e Tatuagem','Animais de estimação'],
        '⚽ Esportes': ['Futebol e Clubes','Seleções','Artes marciais','Academia e Musculação','Skate e Surf','Automobilismo'],
        '🌎 Países &amp; Regiões': ['Países e Estados','Cidades e Bairros','Cultura e Orgulho','Turismo'],
        '✝ Religião &amp; Espiritualidade': ['Católicos','Evangélicos','Espíritas','Umbanda e Candomblé','Budismo','Ateísmo','Astrologia e Signos']
    };
    var html = '<option value="Geral"' + (selected === 'Geral' ? ' selected' : '') + '>Geral</option>';
    for (var group in cats) {
        html += '<optgroup label="' + group + '">';
        for (var i = 0; i < cats[group].length; i++) {
            var v = cats[group][i];
            html += '<option value="' + v + '"' + (selected === v ? ' selected' : '') + '>' + v + '</option>';
        }
        html += '</optgroup>';
    }
    return html;
}

async function saveEditCommunity(commId) {
    var nome = document.getElementById('edit-comm-nome').value.trim();
    var categoria = document.getElementById('edit-comm-categoria').value;
    var idioma = document.getElementById('edit-comm-idioma').value;
    var tipo = document.getElementById('edit-comm-tipo').value;
    var local = document.getElementById('edit-comm-local').value.trim();
    syncText('editorEdit', 'hiddenEdit');
    var descricao = document.getElementById('hiddenEdit') ? document.getElementById('hiddenEdit').value : '';
    var fotoBase64 = document.getElementById('fotoBase64') ? document.getElementById('fotoBase64').value : '';

    if (!nome || nome.length < 3) {
        if (typeof showToast === 'function') showToast('O nome deve ter pelo menos 3 caracteres.', 'error');
        return;
    }

    try {
        var resp = await fetch('/api/comunidades/editar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId, nome: nome, categoria: categoria, idioma: idioma, tipo: tipo, local: local, descricao: descricao, foto_base64: fotoBase64 })
        });
        var data = await resp.json();
        if (data.success) {
            if (typeof showToast === 'function') showToast(data.message || 'Alterações salvas!', 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            if (typeof showToast === 'function') showToast(data.message || 'Erro ao salvar.', 'error');
            else alert(data.message || 'Erro ao salvar.');
        }
    } catch (err) {
        alert('Erro ao salvar alterações.');
    }
}

function filtrarMembros() {
    var termo = document.getElementById('busca-membro').value.toLowerCase().trim();
    var items = document.querySelectorAll('#membros-grid .membro-item');
    for (var i = 0; i < items.length; i++) {
        var nome = items[i].getAttribute('data-nome') || '';
        items[i].style.display = nome.indexOf(termo) !== -1 ? '' : 'none';
    }
}

function abrirModalExpulsar(commId, membroId, membroNome) {
    showConfirm('Tem certeza que deseja expulsar <b>' + membroNome + '</b> da comunidade?', async function() {
        var chkBloquear = document.getElementById('chk-bloquear-membro');
        var bloquear = chkBloquear ? chkBloquear.checked : false;
        try {
            var resp = await fetch('/api/comunidade/' + commId + '/expulsar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ membro_id: membroId, bloquear: bloquear })
            });
            var data = await resp.json();
            if (data.success) {
                showToast(data.message || 'Membro expulso com sucesso.', 'success');
                if (bloquear) {
                    // Redirecionar para a lista de banidos com destaque
                    setTimeout(function() { window.location.href = 'comunidades.php?id=' + commId + '&view=config&ban_uid=' + membroId; }, 600);
                } else {
                    setTimeout(function() { showCommunityMembers(commId); }, 600);
                }
            } else {
                showToast(data.message || 'Erro ao expulsar membro.', 'error');
            }
        } catch (err) {
            showToast('Erro ao expulsar membro.', 'error');
        }
    }, {
        title: 'Expulsar Membro',
        yesText: 'Expulsar',
        danger: true,
        inputHtml: '<div style="margin-top:12px; padding:10px; background:#fff8f0; border:1px solid #e0c6a0; border-radius:4px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:11px; color:#333;"><input type="checkbox" id="chk-bloquear-membro" style="cursor:pointer;"> <span>Bloquear membro de participar desta comunidade</span></label><div style="font-size:9px; color:#999; margin-top:4px; margin-left:24px;">Se marcado, o membro não poderá encontrar nem entrar novamente nesta comunidade.</div></div>'
    });
}

async function joinCommunity(commId) {
    // Verificar se a comunidade é privada (olha o botão que chamou)
    var btnText = event && event.target ? event.target.textContent : '';
    var isPrivada = btnText.indexOf('solicitar') >= 0 || btnText.indexOf('🔒') >= 0;

    if (isPrivada) {
        showConfirm('Envie uma mensagem para os moderadores explicando por que deseja entrar nesta comunidade:', async function() {
            var msgInput = document.getElementById('urkut-confirm-msg-pendente');
            var mensagem = msgInput ? msgInput.value.trim() : '';
            try {
                var resp = await fetch('/api/comunidades/entrar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ comunidade_id: commId, mensagem: mensagem })
                });
                var data = await resp.json();
                if (data.success) {
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    if (typeof showToast === 'function') showToast(data.message, 'error');
                    else alert(data.message);
                }
            } catch (err) { alert('Erro ao entrar na comunidade.'); }
        }, {
            title: '🔒 Solicitar Entrada',
            yesText: 'Enviar Solicitação',
            noText: 'Cancelar',
            inputHtml: '<textarea id="urkut-confirm-msg-pendente" placeholder="Ex: Gosto muito desse tema e quero participar das discussões..." style="width:100%; min-height:80px; padding:8px 10px; margin-top:8px; border:1px solid #ccc; border-radius:4px; font-size:11px; font-family:Tahoma,sans-serif; resize:vertical; box-sizing:border-box;" maxlength="500"></textarea><div style="font-size:9px; color:#999; margin-top:4px; text-align:right;">Opcional — máximo 500 caracteres</div>'
        });
        return;
    }

    try {
        var resp = await fetch('/api/comunidades/entrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId })
        });
        var data = await resp.json();
        if (data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            if (typeof showToast === 'function') showToast(data.message, 'error');
            else alert(data.message);
        }
    } catch (err) { alert('Erro ao entrar na comunidade.'); }
}

async function cancelPending(commId) {
    showConfirm('Deseja cancelar sua solicitação para entrar nesta comunidade?', async function() {
        try {
            var resp = await fetch('/api/comunidade/' + commId + '/pendentes/cancelar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            var data = await resp.json();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) { showToast('Erro ao cancelar solicitação.', 'error'); }
    }, { title: 'Cancelar Solicitação', yesText: 'Sim, cancelar', noText: 'Voltar' });
}

async function transferOwnership(commId) {
    var select = document.getElementById('transfer-novo-dono');
    if (!select || !select.value) {
        if (typeof showToast === 'function') showToast('Selecione o novo dono.', 'error');
        else alert('Selecione o novo dono.');
        return;
    }
    var novoDono = select.value;
    var novoDonoNome = select.options[select.selectedIndex].text;

    showConfirm('Para transferir a posse para <b>' + escapeHtml(novoDonoNome) + '</b>, digite sua senha:', function() {
        var senhaInput = document.getElementById('urkut-confirm-senha');
        if (!senhaInput || !senhaInput.value.trim()) {
            showToast('Digite sua senha para confirmar.', 'error');
            return;
        }
        var senha = senhaInput.value.trim();
        fetch('/api/comunidades/transferir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId, novo_dono_id: novoDono, senha: senha })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { window.location.href = '/comunidades.php'; }, 1000);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() {
            showToast('Erro ao transferir posse.', 'error');
        });
    }, {
        danger: true,
        title: 'Transferir Posse',
        yesText: 'Transferir',
        noText: 'Cancelar',
        inputHtml: '<input type="password" id="urkut-confirm-senha" placeholder="Sua senha" style="width:100%;padding:8px 10px;margin-top:10px;border:1px solid #ccc;border-radius:4px;font-size:13px;box-sizing:border-box;" />'
    });
}

function transferOwnershipConfig(commId) {
    var select = document.getElementById('transfer-novo-dono-config');
    if (!select || !select.value) {
        showToast('Selecione o novo dono.', 'error');
        return;
    }
    var novoDono = select.value;
    var novoDonoNome = select.options[select.selectedIndex].text;

    showConfirm('Para transferir a posse para <b>' + escapeHtml(novoDonoNome) + '</b>, digite sua senha:', function() {
        var senhaInput = document.getElementById('urkut-confirm-senha');
        if (!senhaInput || !senhaInput.value.trim()) {
            showToast('Digite sua senha para confirmar.', 'error');
            return;
        }
        var senha = senhaInput.value.trim();
        fetch('/api/comunidades/transferir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId, novo_dono_id: novoDono, senha: senha })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { window.location.href = '/comunidades.php'; }, 1000);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() {
            showToast('Erro ao transferir posse.', 'error');
        });
    }, {
        danger: true,
        title: 'Transferir Posse',
        yesText: 'Transferir',
        noText: 'Cancelar',
        inputHtml: '<input type="password" id="urkut-confirm-senha" placeholder="Sua senha" style="width:100%;padding:8px 10px;margin-top:10px;border:1px solid #ccc;border-radius:4px;font-size:13px;box-sizing:border-box;" />'
    });
}

function leaveCommunityDetail(commId, commName) {
    showConfirm('Deseja realmente sair da comunidade ' + commName + '?', function() {
        fetch('/api/comunidades/sair', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() { showToast('Erro ao sair da comunidade.', 'error'); });
    }, { title: 'Sair da Comunidade', yesText: 'Sim, sair', noText: 'Cancelar' });
}

function agendarExclusaoComunidade(commId) {
    var senhaInput = document.getElementById('senhaExcluirComm');
    if (!senhaInput) return;
    var senha = senhaInput.value;
    if (!senha) { showToast('Digite sua senha para confirmar.', 'error'); return; }

    showConfirm('ATENÇÃO! Tem certeza que deseja agendar a exclusão permanente desta comunidade? Após 24 horas ela será apagada para sempre!', function() {
        fetch('/api/comunidades/excluir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId, senha: senha })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { showCommunityConfig(commId); }, 1000);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() { showToast('Erro ao agendar exclusão.', 'error'); });
    }, { danger: true, title: 'Excluir Comunidade', yesText: 'Sim, agendar exclusão', noText: 'Cancelar' });
}

function cancelarExclusaoComunidade(commId) {
    showConfirm('Deseja cancelar a exclusão agendada? A comunidade continuará existindo normalmente.', function() {
        fetch('/api/comunidades/cancelar-exclusao', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { showCommunityConfig(commId); }, 1000);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() { showToast('Erro ao cancelar exclusão.', 'error'); });
    }, { title: 'Cancelar Exclusão', yesText: 'Sim, cancelar exclusão', noText: 'Voltar' });
}

async function showApproveMembers(commId) {
    try {
        var resp = await fetch('/api/comunidade/' + commId + '/pendentes');
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">' + escapeHtml(data.message) + '</p>';
            return;
        }

        var c = data.community;
        var pendentes = data.pendentes || [];

        document.title = 'Yorkut - Aprovar Membros';
        document.getElementById('breadcrumb').innerHTML =
            '<a href="/profile.php">Início</a> &gt; <a href="comunidades.php?id=' + c.id + '">' + escapeHtml(c.nome) + '</a> &gt; Aprovar Membros';

        // Left sidebar
        var leftHtml = '<div class="card-left">';
        leftHtml += '  <div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;">';
        leftHtml += '    <a href="comunidades.php?id=' + c.id + '" style="display:block;width:100%;height:100%;"><img src="' + (c.foto || '/semfotocomunidade.jpg') + '"></a>';
        leftHtml += '  </div>';
        leftHtml += '  <div style="text-align:center; font-size:11px; margin-bottom:15px;">';
        leftHtml += '    <strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(c.nome) + '</strong>';
        leftHtml += '    membros: ' + data.membrosCount;
        leftHtml += '  </div>';
        leftHtml += '  <ul class="menu-left hide-on-mobile" style="margin-top:0;">';
        leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '"><span>🏠</span> comunidade</a></li>';
        leftHtml += '    <li><a href="comunidade_convidar_amigo.php?id=' + c.id + '"><span>📱</span> convidar amigos</a></li>';
        leftHtml += '    <li><a href="/forum.php?id=' + c.id + '"><span>💬</span> fórum</a></li>';
        leftHtml += '    <li><a href="/enquetes.php?id=' + c.id + '"><span>📊</span> enquetes</a></li>';
        leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '&view=membros"><span>👥</span> membros</a></li>';
        leftHtml += '    <li><a href="comunidades_staff.php?id=' + c.id + '"><span>👑</span> staff</a></li>';
        leftHtml += '    <li class="active"><a href="comunidades.php?id=' + c.id + '&view=aprovar"><span>✅</span> aprovar membros';
        if (pendentes.length > 0) leftHtml += ' <b style="background:#cc0000;color:#fff;border-radius:10px;padding:1px 6px;font-size:9px;margin-left:3px;">' + pendentes.length + '</b>';
        leftHtml += '</a></li>';
        leftHtml += '    <li><a href="sorteio.php?id=' + c.id + '"><span>🎁</span> sorteios</a></li>';
        if (data.isOwner) {
            leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '&view=config"><span>⚙️</span> configurações</a></li>';
        }
        leftHtml += '  </ul>';
        leftHtml += '</div>';
        document.getElementById('app-left-col').innerHTML = leftHtml;

        // Main content
        var html = '<div class="card">';
        html += '<h1 class="orkut-name" style="font-size:22px; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:15px;">';
        html += '✅ Aprovar Membros';
        html += '<span style="font-size:11px; color:#999; font-weight:normal; margin-left:10px;">' + pendentes.length + ' solicitação(ões)</span>';
        html += '</h1>';

        if (pendentes.length === 0) {
            html += '<div style="text-align:center; padding:40px 20px; color:#999;">';
            html += '<div style="font-size:40px; margin-bottom:10px;">📭</div>';
            html += '<p style="font-size:13px;">Nenhuma solicitação pendente no momento.</p>';
            html += '</div>';
        } else {
            pendentes.forEach(function(p) {
                var foto = p.foto_perfil || '/img/default-avatar.png';
                var dataStr = formatDateFull(p.solicitado_em);
                html += '<div id="pending-' + p.usuario_id + '" style="display:flex; align-items:flex-start; gap:12px; padding:12px 10px; border-bottom:1px solid #f0f0f0;">';
                html += '  <a href="/profile.php?uid=' + p.usuario_id + '"><img src="' + escapeHtml(foto) + '" style="width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #ddd;"></a>';
                html += '  <div style="flex:1; min-width:0;">';
                html += '    <div style="font-weight:bold; font-size:12px;"><a href="/profile.php?uid=' + p.usuario_id + '" style="color:var(--title); text-decoration:none;">' + escapeHtml(p.nome) + '</a></div>';
                html += '    <div style="font-size:10px; color:#999;">Solicitado em ' + dataStr + '</div>';
                if (p.mensagem) {
                    html += '    <div style="margin-top:6px; padding:8px 10px; background:#f8f6f0; border:1px solid #e8e2d6; border-radius:4px; font-size:11px; color:#555; line-height:1.4; word-break:break-word; overflow-wrap:break-word;">';
                    html += '      <span style="font-size:9px; color:#999; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:3px;">📝 MENSAGEM DO SOLICITANTE:</span>';
                    html += '      ' + escapeHtml(p.mensagem);
                    html += '    </div>';
                }
                html += '  </div>';
                html += '  <div style="display:flex; gap:6px; flex-shrink:0; align-self:center;">';
                html += '    <button onclick="aprovarPendente(' + c.id + ', \'' + p.usuario_id + '\')" style="background:#2a6b2a; color:#fff; border:none; padding:5px 14px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold;">Aprovar</button>';
                html += '    <button onclick="rejeitarPendente(' + c.id + ', \'' + p.usuario_id + '\')" style="background:#cc0000; color:#fff; border:none; padding:5px 14px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold;">Rejeitar</button>';
                html += '  </div>';
                html += '</div>';
            });
        }

        html += '</div>';
        document.getElementById('main-card').innerHTML = html;

    } catch (err) {
        console.error('Erro ao carregar pendentes:', err);
        document.getElementById('main-card').innerHTML = '<div class="msg-error">Erro ao carregar solicitações.</div>';
    }
}

async function aprovarPendente(commId, userId) {
    try {
        var resp = await fetch('/api/comunidade/' + commId + '/pendentes/aprovar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: userId })
        });
        var data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            var el = document.getElementById('pending-' + userId);
            if (el) {
                el.style.transition = 'all 0.3s';
                el.style.background = '#e8f5e9';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); }, 300);
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Erro ao aprovar membro.', 'error'); }
}

async function rejeitarPendente(commId, userId) {
    showConfirm('Deseja rejeitar esta solicitação?', async function() {
        var chkBloquear = document.getElementById('chk-bloquear-pendente');
        var bloquear = chkBloquear ? chkBloquear.checked : false;
        try {
            var resp = await fetch('/api/comunidade/' + commId + '/pendentes/rejeitar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: userId, bloquear: bloquear })
            });
            var data = await resp.json();
            if (data.success) {
                showToast(data.message, 'success');
                var el = document.getElementById('pending-' + userId);
                if (el) {
                    el.style.transition = 'all 0.3s';
                    el.style.background = '#ffebee';
                    el.style.opacity = '0';
                    setTimeout(function() { el.remove(); }, 300);
                }
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) { showToast('Erro ao rejeitar solicitação.', 'error'); }
    }, {
        danger: true,
        title: 'Rejeitar Solicitação',
        yesText: 'Sim, rejeitar',
        noText: 'Cancelar',
        inputHtml: '<div style="margin-top:12px; padding:10px; background:#fff8f0; border:1px solid #e0c6a0; border-radius:4px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:11px; color:#333;"><input type="checkbox" id="chk-bloquear-pendente" style="cursor:pointer;"> <span>Bloquear usu\u00e1rio de solicitar novamente</span></label><div style="font-size:9px; color:#999; margin-top:4px; margin-left:24px;">Se marcado, o usu\u00e1rio n\u00e3o poder\u00e1 encontrar nem entrar nesta comunidade.</div></div>'
    });
}

async function showCommunityConfig(commId) {
    try {
        var resp = await fetch('/api/comunidade/' + commId + '/bans');
        var data = await resp.json();
        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">' + escapeHtml(data.message) + '</p>';
            return;
        }

        var c = data.community;
        var bans = data.bans || [];
        var moderadores = data.moderadores || [];

        document.title = 'Yorkut - Configurações';
        document.getElementById('breadcrumb').innerHTML =
            '<a href="/profile.php">Início</a> &gt; <a href="comunidades.php?id=' + c.id + '">' + escapeHtml(c.nome) + '</a> &gt; Configurações';

        // Left sidebar
        var leftHtml = '<div class="card-left">';
        leftHtml += '  <div class="profile-pic" style="margin:0 auto 10px; border-radius:3px;">';
        leftHtml += '    <a href="comunidades.php?id=' + c.id + '" style="display:block;width:100%;height:100%;"><img src="' + (c.foto || '/semfotocomunidade.jpg') + '"></a>';
        leftHtml += '  </div>';
        leftHtml += '  <div style="text-align:center; font-size:11px; margin-bottom:15px;">';
        leftHtml += '    <strong style="color:var(--link); font-size:13px; display:block; word-wrap:break-word;">' + escapeHtml(c.nome) + '</strong>';
        leftHtml += '    membros: ' + data.membrosCount;
        leftHtml += '  </div>';
        leftHtml += '  <ul class="menu-left hide-on-mobile" style="margin-top:0;">';
        leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '"><span>🏠</span> comunidade</a></li>';
        leftHtml += '    <li><a href="comunidade_convidar_amigo.php?id=' + c.id + '"><span>📱</span> convidar amigos</a></li>';
        leftHtml += '    <li><a href="/forum.php?id=' + c.id + '"><span>💬</span> fórum</a></li>';
        leftHtml += '    <li><a href="/enquetes.php?id=' + c.id + '"><span>📊</span> enquetes</a></li>';
        leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '&view=membros"><span>👥</span> membros</a></li>';
        leftHtml += '    <li><a href="comunidades_staff.php?id=' + c.id + '"><span>👑</span> staff</a></li>';
        if (c.tipo === 'privada') {
            leftHtml += '    <li><a href="comunidades.php?id=' + c.id + '&view=aprovar"><span>✅</span> aprovar membros</a></li>';
        }
        leftHtml += '    <li><a href="sorteio.php?id=' + c.id + '"><span>🎁</span> sorteios</a></li>';
        leftHtml += '    <li class="active"><a href="comunidades.php?id=' + c.id + '&view=config"><span>⚙️</span> configurações</a></li>';
        leftHtml += '  </ul>';
        leftHtml += '</div>';
        document.getElementById('app-left-col').innerHTML = leftHtml;

        // Main content
        var html = '<div class="card">';
        html += '<h1 class="orkut-name" style="font-size:22px; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:15px;">';
        html += '⚙️ Configurações da Comunidade';
        html += '</h1>';

        // Transfer ownership / Leave community section
        html += '<div style="border: 1px solid #e4c6a0; padding: 15px; border-radius: 4px; background: #fffdf8; margin-bottom:15px;">';
        html += '<h3 style="margin-top:0; color:#cc6600; font-size:13px;">💔 Deixar Comunidade</h3>';
        html += '<p style="font-size:11px; color:#444; line-height:1.4; margin-bottom:12px;">';
        html += '  Como dono, para sair da comunidade você precisa <b>transferir a posse</b> para um moderador.';
        html += '</p>';
        if (moderadores.length === 0) {
            html += '<p style="font-size:11px; color:#999; font-style:italic;">Não há moderadores nesta comunidade. Adicione um moderador na página de <a href="comunidades_staff.php?id=' + c.id + '" style="color:var(--link);">staff</a> primeiro.</p>';
        } else {
            html += '<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">';
            html += '  <select id="transfer-novo-dono-config" style="padding:5px 8px; border:1px solid #ccc; border-radius:3px; font-size:11px; font-family:Tahoma,sans-serif; min-width:180px;">';
            html += '    <option value="">-- Selecione o moderador --</option>';
            moderadores.forEach(function(m) {
                html += '    <option value="' + m.id + '">' + escapeHtml(m.nome) + '</option>';
            });
            html += '  </select>';
            html += '  <button type="button" onclick="transferOwnershipConfig(' + c.id + ')" style="background:#cc6600; border:1px solid #aa5500; color:#fff; padding:6px 16px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold;">Transferir e Sair</button>';
            html += '</div>';
        }
        html += '</div>';

        // Delete community section - same style as configuracoes.php account deletion
        html += '<div style="border: 1px solid #ffcccc; padding: 15px; border-radius: 4px; background: #fffdfd; margin-bottom:15px;">';
        if (c.excluir_em) {
            // Exclusion is already scheduled - show countdown
            var excluirDate = new Date(c.excluir_em.replace(' ', 'T'));
            var agora = new Date();
            var diffMs = excluirDate.getTime() - agora.getTime();
            var diffH = Math.max(0, Math.floor(diffMs / (1000 * 60 * 60)));
            var diffM = Math.max(0, Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60)));

            html += '<h3 style="margin-top:0; color:#cc0000; font-size:13px;">⏳ Exclusão Agendada</h3>';
            html += '<div style="background:#fff3f3; border:1px solid #ffcccc; border-radius:4px; padding:12px; margin-bottom:12px;">';
            html += '  <p style="font-size:12px; color:#cc0000; margin:0 0 8px 0; font-weight:bold;">Esta comunidade está programada para ser excluída permanentemente!</p>';
            if (diffMs > 0) {
                html += '  <p style="font-size:11px; color:#666; margin:0;">Tempo restante: <b style="color:#cc0000;">' + diffH + 'h ' + diffM + 'min</b></p>';
                html += '  <p style="font-size:10px; color:#999; margin:5px 0 0 0;">Data prevista: ' + formatDateFull(c.excluir_em) + '</p>';
            } else {
                html += '  <p style="font-size:11px; color:#cc0000; margin:0; font-weight:bold;">O prazo já expirou — a comunidade será removida em breve.</p>';
            }
            html += '</div>';
            if (diffMs > 0) {
                html += '<button type="button" onclick="cancelarExclusaoComunidade(' + c.id + ')" style="background:#fff; border:1px solid #090; color:#090; padding:6px 16px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold; transition:all .2s;" onmouseover="this.style.background=\'#090\';this.style.color=\'#fff\';" onmouseout="this.style.background=\'#fff\';this.style.color=\'#090\';">Cancelar Exclusão</button>';
            }
        } else {
            // No scheduled deletion - show form
            html += '<h3 style="margin-top:0; color:#cc0000; font-size:13px;">🗑️ Excluir Comunidade</h3>';
            html += '<p style="font-size:11px; color:#444; line-height:1.4; margin-bottom:15px;">';
            html += '  <b>ATENÇÃO: O PROCESSO DE EXCLUSÃO É PERMANENTE!</b><br>';
            html += '  A comunidade junto com todos os membros, fórum, enquetes e sorteios serão deletados para sempre.<br>';
            html += '  Ao confirmar, começará uma <b>contagem regressiva de 24 horas</b>. Após isso, todos os dados serão apagados permanentemente.';
            html += '</p>';
            html += '<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">';
            html += '  <input type="password" id="senhaExcluirComm" class="form-input" placeholder="Digite sua senha para confirmar" style="width:280px; padding:5px 8px; border:1px solid #ccc; border-radius:3px; font-size:11px; font-family:Tahoma,sans-serif;">';
            html += '  <button type="button" onclick="agendarExclusaoComunidade(' + c.id + ')" style="background:#cc0000; border:1px solid #990000; color:#fff; padding:6px 16px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; font-weight:bold; white-space:nowrap;">Iniciar Exclusão (24h)</button>';
            html += '</div>';
        }
        html += '</div>';

        // Banned members section
        html += '<div style="border: 1px solid #e4ebf5; padding: 15px; border-radius: 4px; background: #fdfdfd; margin-bottom:15px;">';
        html += '<h3 style="margin-top:0; color:var(--title); font-size:13px; border-bottom: 1px solid #e4ebf5; padding-bottom: 8px; display:flex; justify-content:space-between; align-items:center;">';
        html += '🚫 Membros Banidos';
        html += '<span style="font-size:10px; color:#999; font-weight:normal;">' + (bans.length > 0 ? bans.length + ' usuário(s)' : '') + '</span>';
        html += '</h3>';

        var banDestaqueUid = new URLSearchParams(window.location.search).get('ban_uid');
        if (bans.length === 0) {
            html += '<div style="text-align:center; padding:20px; color:#999; font-size:12px;">Nenhum membro banido nesta comunidade.</div>';
        } else {
            bans.forEach(function(ban) {
                var foto = ban.usuario_foto || '/img/default-avatar.png';
                var banDate = formatDateFull(ban.banido_em);
                var isBanDestaque = banDestaqueUid && String(ban.usuario_id) === String(banDestaqueUid);
                html += '<div class="' + (isBanDestaque ? 'ban-destaque' : '') + '" data-ban-uid="' + ban.usuario_id + '" style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f0f0f0;' + (isBanDestaque ? ' background:#fff8e1; border-left:3px solid #f5a623; padding-left:10px; border-radius:4px;' : '') + '">';
                html += '  <a href="/profile.php?uid=' + ban.usuario_id + '"><img src="' + escapeHtml(foto) + '" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #ddd;"></a>';
                html += '  <div style="flex:1; min-width:0;">';
                html += '    <div style="font-weight:bold; font-size:12px; color:var(--title);"><a href="/profile.php?uid=' + ban.usuario_id + '" style="color:var(--title); text-decoration:none;">' + escapeHtml(ban.usuario_nome) + '</a></div>';
                html += '    <div style="font-size:10px; color:#999;">Banido em ' + banDate + '</div>';
                if (ban.banido_por_nome) {
                    html += '    <div style="font-size:10px; color:#999;">Por: ' + escapeHtml(ban.banido_por_nome) + '</div>';
                }
                html += '  </div>';
                html += '  <button type="button" onclick="desbanirMembro(' + c.id + ', \'' + ban.usuario_id + '\', \'' + escapeHtml(ban.usuario_nome).replace(/\'/g, "\\'") + '\')" style="background:#fff; border:1px solid #c00; color:#c00; padding:5px 12px; border-radius:3px; cursor:pointer; font-size:11px; font-family:Tahoma,sans-serif; transition:all .2s;" onmouseover="this.style.background=\'#c00\';this.style.color=\'#fff\';" onmouseout="this.style.background=\'#fff\';this.style.color=\'#c00\';">Desbanir</button>';
                html += '</div>';
            });
        }
        html += '</div>';

        html += '</div>';
        document.getElementById('main-card').innerHTML = html;

        // Scroll e animação de destaque no membro recém-banido
        if (banDestaqueUid) {
            var elBan = document.querySelector('.ban-destaque');
            if (elBan) {
                elBan.scrollIntoView({ behavior: 'smooth', block: 'center' });
                elBan.style.transition = 'background 0.5s ease';
                setTimeout(function() {
                    elBan.style.background = '#fffde7';
                    setTimeout(function() { elBan.style.background = '#fff8e1'; }, 600);
                    setTimeout(function() { elBan.style.background = '#fffde7'; }, 1200);
                    setTimeout(function() { elBan.style.background = ''; elBan.style.borderLeft = ''; elBan.style.paddingLeft = ''; }, 3000);
                }, 300);
            }
        }

    } catch(err) {
        console.error(err);
        document.getElementById('main-card').innerHTML = '<p style="color:#cc0000;">Erro ao carregar configurações.</p>';
    }
}

function desbanirMembro(commId, usuarioId, nome) {
    showConfirm('Deseja desbanir <b>' + nome + '</b>? O usuário poderá acessar e entrar na comunidade novamente.', function() {
        fetch('/api/comunidade/' + commId + '/desbanir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: usuarioId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                showToast(data.message, 'success');
                // Reload the config page
                showCommunityConfig(commId);
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() { showToast('Erro ao desbanir membro.', 'error'); });
    }, { title: 'Desbanir Membro', yesText: 'Sim, desbanir', noText: 'Cancelar' });
}

async function showMyCommunitiesList() {
    try {
        var meResp = await fetch('/api/me');
        var meData = await meResp.json();
        if (!meData.success) {
            document.getElementById('main-card').innerHTML = '<div class="empty-state">Erro ao carregar dados.</div>';
            return;
        }
        var uid = meData.user.id;

        var resp = await fetch('/api/user-comunidades/' + uid);
        var data = await resp.json();

        if (!data.success) {
            document.getElementById('main-card').innerHTML = '<div class="empty-state">' + (data.message || 'Erro ao carregar.') + '</div>';
            return;
        }

        var owned = data.owned;
        var joined = data.joined;
        var total = data.total;

        document.getElementById('breadcrumb').innerHTML = '<a href="/profile.php">Início</a> > Minhas Comunidades';

        var html = '';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
        html += '  <h1 class="orkut-name" style="font-size: 22px; margin: 0;">Minhas Comunidades <span style="font-size: 16px; color:#666;">(' + total + ')</span></h1>';
        html += '  <a href="comunidades.php?action=create" class="icon-action-btn" style="padding: 6px 12px; font-size: 11px;">➕ Criar Comunidade</a>';
        html += '</div>';

        if (total === 0) {
            html += '<div class="empty-state">Você ainda não participa de nenhuma comunidade.</div>';
        } else {
            if (owned.length > 0) {
                html += '<div class="comm-list-section">';
                html += '<h3>👑 Comunidades que sou Dono (' + owned.length + ')</h3>';
                html += '<div class="comm-grid">';
                for (var i = 0; i < owned.length; i++) {
                    var c = owned[i];
                    var foto = c.foto || 'semfotocomunidade.jpg';
                    html += '<div class="comm-card">';
                    html += '  <div class="comm-pic"><a href="comunidades.php?id=' + c.id + '"><img src="' + foto + '"></a></div>';
                    html += '  <div class="comm-info">';
                    html += '    <a href="comunidades.php?id=' + c.id + '" class="comm-name">' + escapeHtml(c.nome) + '</a>';
                    html += '    <div class="comm-meta"><b>' + escapeHtml(c.categoria) + '</b><br>Desde ' + formatDate(c.criado_em) + '</div>';
                    html += '  </div>';
                    html += '  <a href="comunidades.php?id=' + c.id + '" class="icon-action-btn" style="padding:4px 8px; font-weight:normal;">Gerenciar</a>';
                    html += '</div>';
                }
                html += '</div></div>';
            }
            if (joined.length > 0) {
                html += '<div class="comm-list-section" style="margin-bottom:0;">';
                html += '<h3>👥 Comunidades que participo (' + joined.length + ')</h3>';
                html += '<div class="comm-grid">';
                for (var j = 0; j < joined.length; j++) {
                    var c2 = joined[j];
                    var foto2 = c2.foto || 'semfotocomunidade.jpg';
                    html += '<div class="comm-card" id="comm-card-' + c2.id + '">';
                    html += '  <div class="comm-pic"><a href="comunidades.php?id=' + c2.id + '"><img src="' + foto2 + '"></a></div>';
                    html += '  <div class="comm-info">';
                    html += '    <a href="comunidades.php?id=' + c2.id + '" class="comm-name">' + escapeHtml(c2.nome) + '</a>';
                    html += '    <div class="comm-meta"><b>' + escapeHtml(c2.categoria) + '</b><br>Desde ' + formatDate(c2.criado_em) + '</div>';
                    html += '  </div>';
                    html += '  <button onclick="leaveCommunity(' + c2.id + ', \'' + escapeHtml(c2.nome).replace(/\'/g, "\\'") + '\')" class="icon-action-btn btn-danger-outline" style="font-weight:normal;">🗑️ Sair</button>';
                    html += '</div>';
                }
                html += '</div></div>';
            }
        }

        document.getElementById('main-card').innerHTML = html;
    } catch (err) {
        console.error(err);
        document.getElementById('main-card').innerHTML = '<div class="empty-state">Erro ao carregar comunidades.</div>';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function leaveCommunity(commId, commName) {
    showConfirm('Deseja realmente sair da comunidade ' + commName + '?', function() {
        fetch('/api/comunidades/sair', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comunidade_id: commId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                var card = document.getElementById('comm-card-' + commId);
                if (card) { card.style.transition = 'opacity 0.3s'; card.style.opacity = '0'; setTimeout(function() { card.remove(); }, 300); }
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        }).catch(function() { showToast('Erro ao sair da comunidade.', 'error'); });
    }, { title: 'Sair da Comunidade', yesText: 'Sim, sair', noText: 'Cancelar' });
}

async function carregarMiniForuns(commId) {
    var box = document.getElementById('mini-forum-box');
    if (!box) return;
    try {
        var resp = await fetch('/api/forum/' + commId + '/topicos?page=1');
        var data = await resp.json();
        if (!data.success || data.topicos.length === 0) {
            box.innerHTML = 'Nenhum fórum criado ainda.';
            return;
        }
        var html = '';
        var tops = data.topicos.slice(0, 5);
        for (var i = 0; i < tops.length; i++) {
            var t = tops[i];
            html += '<div style="padding:4px 0;border-bottom:1px solid #eef2f8;">';
            html += '<a href="/forum.php?topico=' + t.id + '" style="color:var(--link);text-decoration:none;font-size:11px;">' + escapeHtml(t.titulo) + '</a>';
            html += '<div style="font-size:9px;color:#999;">por ' + escapeHtml(t.autor_nome) + ' · ' + (t.total_respostas > 0 ? (t.total_respostas - 1) : 0) + ' resp.</div>';
            html += '</div>';
        }
        if (data.total > 5) {
            html += '<div style="text-align:right;padding-top:4px;"><a href="/forum.php?id=' + commId + '" style="font-size:10px;color:var(--link);">ver todos (' + data.total + ')</a></div>';
        }
        box.innerHTML = html;
    } catch(e) { box.innerHTML = 'Erro ao carregar.'; }
}

async function carregarMiniEnquetes(commId) {
    var box = document.getElementById('mini-enquetes-box');
    if (!box) return;
    try {
        var resp = await fetch('/api/enquetes/' + commId);
        var data = await resp.json();
        if (!data.success || data.enquetes.length === 0) {
            box.innerHTML = 'Nenhuma enquete criada ainda.';
            return;
        }
        var html = '';
        var enqs = data.enquetes.slice(0, 5);
        for (var i = 0; i < enqs.length; i++) {
            var e = enqs[i];
            html += '<div style="padding:4px 0;border-bottom:1px solid #eef2f8;">';
            html += '<a href="/enquetes.php?id=' + commId + '" style="color:var(--link);text-decoration:none;font-size:11px;">' + escapeHtml(e.titulo) + '</a>';
            html += '<div style="font-size:9px;color:#999;">por ' + escapeHtml(e.criador_nome) + ' · ' + e.total_votos + ' votos</div>';
            html += '</div>';
        }
        if (data.enquetes.length > 5) {
            html += '<div style="text-align:right;padding-top:4px;"><a href="/enquetes.php?id=' + commId + '" style="font-size:10px;color:var(--link);">ver todas (' + data.enquetes.length + ')</a></div>';
        }
        box.innerHTML = html;
    } catch(e) { box.innerHTML = 'Erro ao carregar.'; }
}
</script>
</body>
</html>

