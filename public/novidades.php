<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Novidades</title>
<link rel="stylesheet" href="/styles/main.css">
<style>
    .news-box { border-left: 3px solid var(--orkut-pink); background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 0 4px 4px 0; }
    .news-date { font-size: 10px; color: #888; margin-bottom: 5px; font-weight: bold; }
    .news-title { font-size: 14px; color: var(--link); margin: 0 0 5px 0; }
    .news-text { font-size: 12px; color: #444; line-height: 1.5; }
    .news-text ul { margin-top: 5px; padding-left: 20px; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Novidades do Yorkut</div>
        <div class="card">
            <h1 class="orkut-name" style="font-size: 22px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px;">O que estamos construindo?</h1>
            <p style="font-size: 12px; color: #555; line-height: 1.5; margin-bottom: 20px;">
                Bem-vindo à página de Novidades! A nossa equipe de desenvolvedores (que sente muita saudade dos anos 2000) está trabalhando duro todos os dias para trazer a verdadeira experiência nostálgica de volta para você. Veja o que já aprontamos:
            </p>
            <div class="news-box">
                <div class="news-date">Hoje</div>
                <h3 class="news-title">A base está pronta e os recursos clássicos chegaram!</h3>
                <div class="news-text">
                    Nós recriamos do zero toda a estrutura para garantir que sua experiência seja o mais autêntica possível. Já estão funcionando no Yorkut:
                    <ul>
                        <li><b>Recados (Scraps):</b> Mande mensagens para seus amigos (e apague as que se arrepender).</li>
                        <li><b>Depoimentos:</b> Escreva coisas bonitas, mas lembre-se: só aparece no perfil se o dono aceitar!</li>
                        <li><b>Fotos & Vídeos:</b> Álbuns de fotos limitados (para escolher só as melhores) e integração direta com vídeos do YouTube.</li>
                        <li><b>Comunidades:</b> O coração da rede! Você já pode criar, pesquisar e participar dos fóruns.</li>
                        <li><b>Sorte do Dia:</b> Para você já começar o dia com uma frase inspiradora.</li>
                        <li><b>Sistema de Confiança:</b> Avalie seus amigos como Confiável, Legal e Sexy!</li>
                    </ul>
                </div>
            </div>
            <div class="news-box" style="border-left-color: var(--orkut-blue);">
                <div class="news-date">Em breve</div>
                <h3 class="news-title">O que vem por aí?</h3>
                <div class="news-text">
                    Ainda não terminamos! Estamos preparando muitas novidades na nossa oficina:
                    <ul>
                        <li>Fóruns completos e organizados dentro das comunidades.</li>
                        <li>Mais privacidade e personalização de perfil (Aba de configurações em reforma).</li>
                        <li>Novos selos e conquistas para os usuários mais ativos.</li>
                    </ul>
                    Fique ligado e convide seus amigos para testarem junto com você!
                </div>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { loadLayout({ activePage: 'novidades' }); });</script>
</body>
</html>
