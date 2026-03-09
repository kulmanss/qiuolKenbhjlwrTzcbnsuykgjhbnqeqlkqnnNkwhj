<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorkut - Início</title>
    <script src="/js/toast.js"></script>
    <link rel="stylesheet" href="/css/home.css">
</head>
<body>
    <!-- Barra superior -->
    <div class="topbar">
        <div class="topbar-content">
            <a href="/home.php" class="topbar-logo">yorkut<sup>BR</sup></a>
            <div class="topbar-search">
                <input type="text" placeholder="Pesquisar no yorkut..." id="searchInput">
                <button type="button" class="search-btn">🔍</button>
            </div>
            <div class="topbar-nav">
                <a href="/home.php" class="topbar-link active">Início</a>
                <a href="/perfil.php" class="topbar-link">Perfil</a>
                <a href="#" class="topbar-link">Recados</a>
                <a href="#" class="topbar-link">Comunidades</a>
                <a href="#" class="topbar-link" id="btnLogout">Sair</a>
            </div>
        </div>
    </div>

    <!-- Conteúdo principal -->
    <div class="main-container">
        <div class="content-area">
            <!-- Coluna esquerda: Mini perfil -->
            <aside class="sidebar-left">
                <div class="mini-profile">
                    <img src="/img/default-avatar.png" alt="Avatar" class="mini-avatar" id="userAvatar">
                    <h3 class="mini-name" id="userName">Carregando...</h3>
                    <p class="mini-email" id="userEmail"></p>
                </div>

                <div class="sidebar-box">
                    <h4>Meus convites</h4>
                    <div id="convitesContainer">
                        <p class="loading-text">Carregando...</p>
                    </div>
                </div>

                <div class="sidebar-box">
                    <h4>Links rápidos</h4>
                    <ul class="quick-links">
                        <li><a href="/perfil.php">👤 Meu perfil</a></li>
                        <li><a href="#">💬 Recados</a></li>
                        <li><a href="#">👥 Amigos</a></li>
                        <li><a href="#">🏘️ Comunidades</a></li>
                        <li><a href="#">📷 Fotos</a></li>
                        <li><a href="#">🎬 Vídeos</a></li>
                    </ul>
                </div>
            </aside>

            <!-- Coluna central: Feed -->
            <main class="feed">
                <div class="feed-box">
                    <h4>Bem-vindo ao yorkut!</h4>
                    <p>Esta é sua página inicial. Aqui você verá as atualizações dos seus amigos e comunidades.</p>
                </div>

                <div class="feed-box">
                    <h4>📢 Atualizações</h4>
                    <div class="feed-item">
                        <p><b>Sistema:</b> Bem-vindo(a) à rede yorkut! Convide seus amigos usando seus tokens de convite.</p>
                        <span class="feed-time">Agora</span>
                    </div>
                    <div class="feed-item">
                        <p><b>Dica:</b> Complete seu perfil para que seus amigos possam te encontrar!</p>
                        <span class="feed-time">Agora</span>
                    </div>
                </div>

                <div class="feed-box">
                    <h4>✉️ Escrever recado</h4>
                    <textarea placeholder="Escreva algo..." class="scrap-input" rows="3"></textarea>
                    <button class="btn-scrap">Enviar recado</button>
                </div>
            </main>

            <!-- Coluna direita -->
            <aside class="sidebar-right">
                <div class="sidebar-box">
                    <h4>Estatísticas</h4>
                    <ul class="stats-list">
                        <li>Recados: <b>0</b></li>
                        <li>Amigos: <b>0</b></li>
                        <li>Comunidades: <b>0</b></li>
                        <li>Fotos: <b>0</b></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2026 Yorkut &bull; <a href="#">Sobre</a> &bull; <a href="#">Segurança</a> &bull; <a href="#">Privacidade</a> &bull; <a href="#">Termos</a> &bull; <a href="#">Contato</a>
    </div>

<script>
    // Carregar dados do usuário
    async function loadUser() {
        try {
            const res = await fetch('/api/me');
            const data = await res.json();

            if (!data.success) {
                window.location.href = '/index.php';
                return;
            }

            document.getElementById('userName').textContent = data.user.nome;
            document.getElementById('userEmail').textContent = data.user.email;
            if (data.user.foto_perfil) {
                document.getElementById('userAvatar').src = data.user.foto_perfil;
            }

            // Exibir convites
            const container = document.getElementById('convitesContainer');
            if (data.convites && data.convites.length > 0) {
                let html = '<ul class="convites-list">';
                data.convites.forEach(c => {
                    const status = c.usado ? '❌ Usado' : '✅ Disponível';
                    html += `<li><code>${c.token}</code> <small>${status}</small></li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="loading-text">Nenhum convite ainda.</p>';
            }
        } catch (err) {
            console.error('Erro ao carregar dados:', err);
            window.location.href = '/index.php';
        }
    });

    // Logout
    document.getElementById('btnLogout').addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await fetch('/api/logout', { method: 'POST' });
            window.location.href = '/index.php';
        } catch (err) {
            window.location.href = '/index.php';
        }
    });

    // Botão de recado (placeholder)
    document.querySelector('.btn-scrap')?.addEventListener('click', () => {
        const textarea = document.querySelector('.scrap-input');
        if (textarea.value.trim()) {
            alert('Funcionalidade em construção!');
            textarea.value = '';
        }
    });

    loadUser();
</script>
</body>
</html>
