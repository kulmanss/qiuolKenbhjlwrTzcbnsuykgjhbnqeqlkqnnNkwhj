<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Sobre</title>
<link rel="stylesheet" href="/styles/main.css">
<style>
    .about-box { background: #f9f9f9; padding: 20px; border-radius: 4px; border: 1px solid var(--line); color: #444; line-height: 1.6; font-size: 12px; }
    .about-box h2 { color: var(--orkut-blue); margin-top: 0; font-size: 18px; border-bottom: 1px dashed var(--line); padding-bottom: 10px; }
    .about-box p { margin-bottom: 15px; }
    .highlight-text { font-weight: bold; color: var(--orkut-pink); }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Sobre o Yorkut</div>
        <div class="card">
            <h1 class="orkut-name" style="font-size: 22px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 15px;">Sobre o Yorkut</h1>
            <div class="about-box">
                <h2>Saudades de uma internet mais simples? Nós também.</h2>
                <p>O <span class="highlight-text">Yorkut</span> nasceu de um sentimento muito forte: a nostalgia. Em uma época onde a internet era dominada por redes sociais simples, focadas puramente em conectar pessoas e criar laços (e não em vender produtos ou ditar o que você deve assistir), existia um espaço azul onde todo mundo se sentia em casa.</p>
                <p>Nós construímos este projeto não como uma empresa gigante, mas como um grupo de fãs da internet dos anos 2000. Queríamos trazer de volta a emoção de chegar em casa, ligar o computador e ver que você recebeu um novo <b>"Recado" (Scrap)</b>, ou a ansiedade de escrever aquele <b>Depoimento</b> gigante para o seu melhor amigo e esperar ele aceitar.</p>
                <h2>Nossos Princípios</h2>
                <ul>
                    <li style="margin-bottom: 5px;"><b>Sem Algoritmos Obscuros:</b> Aqui, você vê o que os seus amigos postam, na ordem em que eles postam. Ninguém dita o seu feed.</li>
                    <li style="margin-bottom: 5px;"><b>Comunidades de Verdade:</b> Acreditamos que fóruns são a melhor forma de discutir hobbies, paixões e fazer perguntas. Por isso, as comunidades são o coração da nossa rede.</li>
                    <li style="margin-bottom: 5px;"><b>Privacidade e Controle:</b> Seus depoimentos só aparecem se você aprovar, suas fotos ficam organizadas no seu álbum e você tem o controle de quem interage com você.</li>
                </ul>
                <h2>Faça parte da nossa história</h2>
                <p>Este é um projeto em constante evolução. Cada perfil criado, cada recado enviado e cada comunidade fundada nos ajuda a reviver essa época de ouro da internet brasileira e mundial.</p>
                <p>Obrigado por fazer parte do Yorkut. Chame seus amigos, crie suas comunidades e divirta-se!</p>
                <div style="text-align: right; font-style: italic; color: #888; margin-top: 20px;">- Com carinho, a equipe de desenvolvedores do Yorkut.</div>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { loadLayout({ activePage: 'sobre' }); });</script>
</body>
</html>
