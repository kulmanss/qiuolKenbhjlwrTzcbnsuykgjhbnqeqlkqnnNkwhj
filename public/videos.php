<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Vídeos</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; }
    .video-item { border: 1px solid var(--line); background: #fdfdfd; padding: 10px; border-radius: 4px; text-align: center; }
    .video-item img { width: 100%; height: 110px; object-fit: cover; border-radius: 3px; border: 1px solid #ccc; margin-bottom: 8px; transition: 0.2s; }
    .video-item img:hover { filter: brightness(0.8); }
    .video-desc { font-size: 11px; color: #444; word-wrap: break-word; line-height: 1.3; height: 14px; overflow:hidden; }
    
    .upload-box { background: #f4f7fc; border: 1px dashed #a5bce3; padding: 15px; text-align: center; border-radius: 4px; margin-bottom: 20px; }
    .upload-box input[type="text"] { width: 100%; max-width: 400px; padding: 6px; font-size: 11px; border: 1px solid #ccc; margin-bottom: 10px; border-radius: 3px; box-sizing: border-box;}

    .full-video-container { padding: 20px; background: #fdfdfd; border: 1px solid var(--line); border-radius: 4px; margin-bottom: 20px; }
    .full-video-container iframe { width: 100%; height: 360px; border-radius: 4px; border: 1px solid #ccc; background:#000; }
    
    .desc-box { margin-top: 15px; font-size: 13px; color: #333; line-height: 1.5; text-align:center; }
    .edit-desc-form { display: none; margin-top: 10px; text-align:center; }
    .edit-desc-form input[type="text"] { width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; font-size: 12px; margin-bottom: 10px; box-sizing: border-box; }
    
    .like-box { margin-top: 15px; border-top: 1px dotted var(--line); padding-top: 15px; text-align:center; }
    
    .comments-section { margin-top: 20px; text-align: left; }
    .comments-section h3 { font-size: 14px; color: var(--title); border-bottom: 1px solid var(--line); padding-bottom: 5px; margin-bottom: 15px; }
    .comment-item { display: flex; gap: 15px; padding: 15px; border-bottom: 1px dotted var(--line); background: #fff; }
    .comment-item:last-child { border-bottom: none; }
    .comment-pic { width: 50px; height: 50px; flex-shrink: 0; background: #e4ebf5; border: 1px solid #c0d0e6; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 3px; }
    .comment-pic img { width: 100%; height: 100%; object-fit: cover; }
    .comment-content { flex: 1; font-size: 12px; }
    .comment-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .comment-author a { font-weight: bold; color: var(--link); text-decoration: none; }
    .comment-date { font-size: 10px; color: #999; }
    .comment-form { margin-top: 15px; background: #f4f7fc; padding: 15px; border-radius: 4px; border: 1px solid var(--line); }
    .comment-form textarea { width: 100%; height: 60px; padding: 10px; border: 1px solid #ccc; box-sizing: border-box; margin-bottom: 10px; resize: vertical; font-family: Arial; font-size: 12px; }

    .icon-action-btn { background: #f4f7fc; border: 1px solid #c0d0e6; color: var(--link); cursor: pointer; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .icon-action-btn:hover { background: #eef4ff; border-color: var(--orkut-blue); }
    .btn-danger-outline { color: #cc0000; border-color: #ffcccc; background: #fffdfd; }
    .btn-danger-outline:hover { background: #ffe6e6; border-color: #cc0000; }
    
    @keyframes pop { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }
    .anim-pop { animation: pop 0.3s ease-in-out; }
    .liked-star { color: var(--orkut-pink) !important; }
    .unliked-star { filter: grayscale(100%); opacity: 0.5; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb" id="breadcrumb">
            <a href="/profile.php">Início</a> > Vídeos
        </div>
        <div class="card">
            <h1 class="orkut-name" id="videos-title" style="font-size: 22px;">Vídeos <span style="color:#666; font-size:16px;">(0)</span></h1>
            <div id="upload-container"></div>
            <div id="videos-content">
                <div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; margin-top:15px;">
                    Carregando...
                </div>
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
    const videoId = urlParams.get('video_id');

    await loadLayout({ activePage: 'videos' });

    // Se não tem uid, usa o próprio usuário logado
    if (!uid) {
        const me = getUserData();
        if (me) uid = me.id;
    }

    if (videoId && uid) {
        await loadVideoDetalhe(uid, videoId);
    } else if (uid) {
        await loadVideosGrid(uid);
    }

    startBadgePolling(15000);
});

// ===== GRID VIEW =====
async function loadVideosGrid(uid) {
    try {
        const resp = await fetch('/api/videos/' + uid);
        const data = await resp.json();
        if (!data.success) {
            document.getElementById('videos-content').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000;">Erro ao carregar vídeos.</div>';
            return;
        }

        const { videos, perfil, isOwner, total, maxVideos } = data;

        // Breadcrumb
        document.getElementById('breadcrumb').innerHTML = `<a href="/profile.php?uid=${uid}">Início</a> > Vídeos de ${escapeHtml(perfil.nome)}`;

        // Title
        document.getElementById('videos-title').innerHTML = `Vídeos de ${escapeHtml(perfil.nome)} <span style="color:#666; font-size:16px;">(${total})</span>`;

        // Upload box (only owner)
        if (isOwner) {
            document.getElementById('upload-container').innerHTML = `
                <div class="upload-box">
                    <strong style="display:block; margin-bottom:5px; color:#555;">Adicionar novo vídeo (${total}/${maxVideos}):</strong>
                    <input type="text" id="video-url" placeholder="Link do YouTube (ex: https://www.youtube.com/watch?v=...)" required>
                    <br>
                    <input type="text" id="video-desc" placeholder="Descrição (opcional)">
                    <br>
                    <button type="button" onclick="adicionarVideo()" class="icon-action-btn">📤 Adicionar</button>
                </div>
            `;
        } else {
            document.getElementById('upload-container').innerHTML = '';
        }

        // Videos grid
        if (videos.length === 0) {
            document.getElementById('videos-content').innerHTML = `
                <div style="text-align:center; padding:30px; color:#999; font-style:italic; border:1px dashed var(--line); border-radius:4px; background:#f9f9f9; margin-top:15px;">Nenhum vídeo adicionado ainda.</div>
            `;
        } else {
            let html = '<div class="video-grid">';
            videos.forEach(v => {
                const thumb = 'https://img.youtube.com/vi/' + v.youtube_id + '/hqdefault.jpg';
                html += `
                    <div class="video-item">
                        <a href="/videos.php?uid=${uid}&video_id=${v.id}">
                            <img src="${thumb}" alt="">
                        </a>
                        <div class="video-desc">${escapeHtml(v.descricao || '')}</div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('videos-content').innerHTML = html;
        }

        document.title = 'Yorkut - Vídeos de ' + perfil.nome;

    } catch (err) {
        console.error('Erro:', err);
        document.getElementById('videos-content').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000;">Erro ao carregar vídeos.</div>';
    }
}

// ===== DETAIL VIEW =====
async function loadVideoDetalhe(uid, videoId) {
    try {
        const resp = await fetch(`/api/videos/${uid}/${videoId}`);
        const data = await resp.json();
        if (!data.success) {
            document.getElementById('videos-content').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000;">Vídeo não encontrado.</div>';
            return;
        }

        const { video, comentarios, perfil, isOwner, myId } = data;

        // Breadcrumb
        document.getElementById('breadcrumb').innerHTML = `
            <a href="/profile.php?uid=${uid}">Início</a> > 
            <a href="/videos.php?uid=${uid}">Vídeos de ${escapeHtml(perfil.nome)}</a> > 
            Assistir Vídeo
        `;

        // Title area with optional delete button
        let titleHtml = `<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px;">
            <h1 class="orkut-name" style="margin:0; font-size:20px;">Assistir Vídeo</h1>`;
        if (isOwner) {
            titleHtml += `<button type="button" onclick="deletarVideo(${video.id}, '${uid}')" class="icon-action-btn btn-danger-outline" style="font-size:10px;">🗑️ Excluir vídeo</button>`;
        }
        titleHtml += `</div>`;

        // Hide the default h1 title
        document.getElementById('videos-title').style.display = 'none';
        document.getElementById('upload-container').innerHTML = '';

        // Star classes
        const starClass = video.curti ? 'liked-star' : 'unliked-star';
        const btnStyle = video.curti ? 'background-color:#fff0f5; border-color:var(--orkut-pink);' : '';

        const embedUrl = 'https://www.youtube.com/embed/' + video.youtube_id;

        // Build content
        let html = titleHtml;
        html += `<div class="full-video-container">
            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
            
            <div class="desc-box">
                <div id="descText">${escapeHtml(video.descricao || '')}`;
        
        if (isOwner) {
            html += ` <a href="javascript:void(0);" onclick="toggleEditDesc()" style="font-size:10px; margin-left:10px;">✏️ editar</a>`;
        }
        
        html += `</div>`;

        if (isOwner) {
            html += `
                <div id="editDescForm" class="edit-desc-form">
                    <input type="text" id="newDescInput" value="${escapeAttr(video.descricao || '')}" placeholder="Nova descrição...">
                    <button type="button" onclick="salvarDescricao(${video.id}, '${uid}')" class="icon-action-btn" style="font-size:10px;">💾 Salvar</button>
                    <button type="button" onclick="toggleEditDesc()" class="icon-action-btn" style="font-size:10px; margin-left:5px;">Cancelar</button>
                </div>
            `;
        }

        html += `</div>

            <div class="like-box">
                <button type="button" id="btn-like-ajax" onclick="curtirVideo(${video.id})" class="icon-action-btn" style="font-size:13px; padding:8px 15px; ${btnStyle}">
                    <span id="like-star-icon" style="font-size:16px; margin-right:5px; transition:0.3s;" class="${starClass}">⭐</span> 
                    Curtir (<span id="like-count-span">${video.curtidas}</span>)
                </button>
            </div>

            <div class="comments-section">
                <h3>Comentários (${comentarios.length})</h3>`;

        comentarios.forEach(c => {
            const fotoSrc = c.autor_foto || getDefaultAvatar(c.autor_sexo);
            const dataFormatada = formatarData(c.criado_em);
            const canDelete = (c.usuario_id === myId || isOwner);

            html += `
                <div class="comment-item" id="comment-${c.id}">
                    <div class="comment-pic">
                        <a href="/profile.php?uid=${c.usuario_id}"><img src="${fotoSrc}"></a>
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-author"><a href="/profile.php?uid=${c.usuario_id}">${escapeHtml(c.autor_nome)}</a></span>
                            <span class="comment-date">${dataFormatada}</span>
                        </div>
                        <div style="line-height:1.4; color:#444;">${escapeHtml(c.mensagem)}</div>
                        ${canDelete ? `<div style="text-align:right; margin-top:5px;"><button type="button" onclick="deletarComentario(${c.id})" class="icon-action-btn btn-danger-outline" style="font-size:9px; padding:2px 8px;">🗑️ apagar</button></div>` : ''}
                    </div>
                </div>
            `;
        });

        // Comment form
        html += `
                <div class="comment-form">
                    <textarea id="comment-text" placeholder="Escreva um comentário..."></textarea>
                    <div style="text-align: right;"><button type="button" onclick="enviarComentario(${video.id}, '${uid}')" class="btn-action">💬 Comentar</button></div>
                </div>
            </div>
        </div>`;

        document.getElementById('videos-content').innerHTML = html;
        document.title = 'Yorkut - Vídeo de ' + perfil.nome;

    } catch (err) {
        console.error('Erro:', err);
        document.getElementById('videos-content').innerHTML = '<div style="text-align:center; padding:30px; color:#cc0000;">Erro ao carregar vídeo.</div>';
    }
}

// ===== ACTIONS =====
async function adicionarVideo() {
    const urlInput = document.getElementById('video-url');
    const descInput = document.getElementById('video-desc');
    const url = urlInput.value.trim();
    
    if (!url) {
        alert('Cole o link do YouTube.');
        return;
    }

    try {
        const resp = await fetch('/api/videos/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ youtube_url: url, descricao: descInput.value })
        });
        const data = await resp.json();
        if (data.success) {
            const urlParams = new URLSearchParams(window.location.search);
            let uid = urlParams.get('uid');
            if (!uid) {
                const me = getUserData();
                if (me) uid = me.id;
            }
            await loadVideosGrid(uid);
        } else {
            alert(data.message || 'Erro ao adicionar vídeo.');
        }
    } catch (err) {
        alert('Erro ao adicionar vídeo.');
    }
}

async function curtirVideo(videoId) {
    try {
        const resp = await fetch(`/api/videos/${videoId}/curtir`, { method: 'POST' });
        const data = await resp.json();
        if (data.success) {
            const btn = document.getElementById('btn-like-ajax');
            const star = document.getElementById('like-star-icon');
            const countSpan = document.getElementById('like-count-span');

            countSpan.innerText = data.total;
            star.classList.remove('anim-pop');
            void star.offsetWidth;
            star.classList.add('anim-pop');

            if (data.liked) {
                star.classList.add('liked-star');
                star.classList.remove('unliked-star');
                btn.style.backgroundColor = '#fff0f5';
                btn.style.borderColor = 'var(--orkut-pink)';
            } else {
                star.classList.remove('liked-star');
                star.classList.add('unliked-star');
                btn.style.backgroundColor = '#f4f7fc';
                btn.style.borderColor = '#c0d0e6';
            }
        }
    } catch (err) {
        console.error('Erro ao curtir:', err);
    }
}

async function enviarComentario(videoId, uid) {
    const textarea = document.getElementById('comment-text');
    const msg = textarea.value.trim();
    if (!msg) { alert('Escreva um comentário.'); return; }

    try {
        const resp = await fetch(`/api/videos/${videoId}/comentar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensagem: msg })
        });
        const data = await resp.json();
        if (data.success) {
            await loadVideoDetalhe(uid, videoId);
        } else {
            alert(data.message || 'Erro ao comentar.');
        }
    } catch (err) {
        alert('Erro ao enviar comentário.');
    }
}

async function deletarComentario(commentId) {
    showConfirm('Apagar este comentário?', async function() {
        try {
            const resp = await fetch(`/api/videos/comentario/${commentId}`, { method: 'DELETE' });
            const data = await resp.json();
            if (data.success) {
                const el = document.getElementById('comment-' + commentId);
                if (el) el.remove();
            } else {
                alert(data.message || 'Erro ao apagar.');
            }
        } catch (err) {
            alert('Erro ao apagar comentário.');
        }
    });
}

async function deletarVideo(videoId, uid) {
    showConfirm('Tem certeza que deseja excluir este vídeo?', async function() {
        try {
            const resp = await fetch(`/api/videos/${videoId}`, { method: 'DELETE' });
            const data = await resp.json();
            if (data.success) {
                window.location.href = '/videos.php?uid=' + uid;
            } else {
                alert(data.message || 'Erro ao excluir.');
            }
        } catch (err) {
            alert('Erro ao excluir vídeo.');
        }
    });
}

async function salvarDescricao(videoId, uid) {
    const input = document.getElementById('newDescInput');
    try {
        const resp = await fetch(`/api/videos/${videoId}/descricao`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descricao: input.value })
        });
        const data = await resp.json();
        if (data.success) {
            await loadVideoDetalhe(uid, videoId);
        } else {
            alert(data.message || 'Erro ao salvar.');
        }
    } catch (err) {
        alert('Erro ao salvar descrição.');
    }
}

function toggleEditDesc() {
    const form = document.getElementById('editDescForm');
    const text = document.getElementById('descText');
    if (form.style.display === 'none') { form.style.display = 'block'; text.style.display = 'none'; }
    else { form.style.display = 'none'; text.style.display = 'block'; }
}

// ===== HELPERS =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatarData(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
</script>
</body>
</html>
