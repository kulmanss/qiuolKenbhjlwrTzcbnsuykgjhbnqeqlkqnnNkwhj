<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Yorkut - Rede social inspirada no Orkut. Conecte-se com amigos, participe de comunidades e jogue Colheita Feliz!">
    <title>Yorkut - Login</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <div class="warning-box">
            Aviso: O yorkut.com.br não tem vínculo com o Google.
        </div>
        <div class="content-wrapper">
            <div class="left-col">
                <div class="logo">yorkut<sup>BR</sup></div>
                <div class="features">
                    <span>Conecte-se</span> aos seus amigos e familiares usando recados e mensagens instantâneas<br>
                    <span>Conheça</span> novas pessoas através de amigos de seus amigos e comunidades<br>
                    <span>Compartilhe</span> seus vídeos, fotos e paixões em um só lugar
                </div>
            </div>
            <div class="right-col">
                <div class="login-box">
                    <h2>Acesse o <b>yorkut.com.br</b> com a sua conta</h2>

                    <form id="loginForm" onsubmit="return false">
                        <div class="form-group">
                            <label for="email">E-mail:</label>
                            <input type="text" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha:</label>
                            <input type="password" id="senha" name="senha" required>
                        </div>
                        <div class="login-actions">
                            <label class="remember-me" for="lembrar">
                                <input type="checkbox" id="lembrar" name="lembrar">
                                <span>Salvar as minhas informações neste computador.</span>
                            </label>
                            <button type="submit" class="btn-login">Login</button>
                        </div>
                    </form>

                    <a href="/lost_account.php" class="forgot-link">Não consegue acessar a sua conta?</a>
                </div>
                <div class="join-box">
                    <div>Ainda não é membro?</div>
                    <a href="/registro.php">ENTRAR JÁ</a>
                </div>
            </div>
        </div>
        <div class="footer">
            &copy; 2026 Yorkut - <a href="#">Sobre o Yorkut</a> - <a href="#">Centro de segurança</a> - <a href="#">Privacidade</a> - <a href="#">Termos</a> - <a href="#">Contato</a>
        </div>
    </div>

    <script src="/js/script.js?v=20260309"></script>
</body>
</html>
