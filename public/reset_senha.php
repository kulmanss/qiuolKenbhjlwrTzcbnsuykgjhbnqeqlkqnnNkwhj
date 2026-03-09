<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorkut - Redefinir Senha</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .login-box h2 {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #3b5998;
        }

        .login-box p {
            font-size: 12px;
            margin-bottom: 15px;
            color: #555;
            line-height: 1.4;
        }

        .reset-form .form-group {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .reset-form .form-group label {
            width: 100%;
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-bottom: 4px;
        }

        .reset-form .form-group input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #7f9db9;
            border-radius: 4px;
            background: #fff;
            font-size: 13px;
            outline: none;
            transition: 0.2s;
        }

        .reset-form .form-group input:focus {
            border-color: #e6399b;
            box-shadow: 0 0 3px rgba(230, 57, 155, 0.4);
        }

        .reset-form .btn-login {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            font-size: 14px;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            font-weight: bold;
            color: #e6399b;
        }

        .success-msg {
            background: #e4f2e9;
            border: 1px solid #8bc59e;
            color: #2a6b2a;
            padding: 15px;
            margin-bottom: 15px;
            font-weight: bold;
            border-radius: 4px;
            font-size: 13px;
            text-align: center;
            line-height: 1.6;
        }

        .error-msg-box {
            background: #ffe6e6;
            border: 1px solid #cc0000;
            color: #cc0000;
            padding: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }

        .user-info {
            background: #fff;
            border: 1px solid #d0d8e8;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 13px;
            color: #333;
        }

        .user-info strong {
            color: #3b5998;
        }

        @media (max-width: 480px) {
            .login-box h2 {
                text-align: center;
                font-size: 18px;
                margin-bottom: 10px;
            }

            .login-box p {
                text-align: center;
                font-size: 13px;
            }

            .reset-form .form-group label {
                font-size: 14px;
            }

            .reset-form .form-group input[type="password"] {
                padding: 14px;
                font-size: 16px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                background: #f9f9f9;
            }

            .reset-form .btn-login {
                padding: 16px;
                font-size: 18px;
                border-radius: 12px;
                background: linear-gradient(145deg, #e6399b, #b30059);
                color: white;
                border: none;
                box-shadow: 0 4px 15px rgba(230, 57, 155, 0.3);
            }

            .login-link {
                font-size: 16px;
                border: 2px solid #e6399b;
                padding: 14px;
                border-radius: 12px;
                background: #fff;
                margin-top: 20px;
                text-decoration: none !important;
            }
        }
    </style>
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
                    <h2>Redefinir Senha</h2>

                    <div id="msgArea"></div>

                    <div id="loadingArea" style="text-align:center; padding:20px;">
                        <p>Verificando link...</p>
                    </div>

                    <div id="formArea" style="display:none;">
                        <div id="userInfo" class="user-info"></div>

                        <form id="resetForm" class="reset-form">
                            <div class="form-group">
                                <label for="novaSenha">Nova senha:</label>
                                <input type="password" id="novaSenha" name="nova_senha" required placeholder="Digite sua nova senha" minlength="4">
                            </div>
                            <div class="form-group">
                                <label for="confirmarSenha">Confirmar nova senha:</label>
                                <input type="password" id="confirmarSenha" required placeholder="Repita a nova senha" minlength="4">
                            </div>
                            <button type="submit" class="btn-login" id="btnReset">Redefinir Senha</button>
                        </form>
                    </div>

                    <a href="/index.php" class="login-link">Voltar ao Login</a>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; 2026 Yorkut - <a href="#">Sobre o Yorkut</a> - <a href="#">Centro de segurança</a> - <a href="#">Privacidade</a> - <a href="#">Termos</a> - <a href="#">Contato</a>
        </div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const token = params.get('token');

        async function init() {
            const loadingArea = document.getElementById('loadingArea');
            const formArea = document.getElementById('formArea');
            const msgArea = document.getElementById('msgArea');

            if (!token) {
                loadingArea.style.display = 'none';
                msgArea.innerHTML = '<div class="error-msg-box">Link inválido. Nenhum token fornecido.</div>';
                return;
            }

            try {
                const resp = await fetch('/api/validar-token-reset?token=' + encodeURIComponent(token));
                const data = await resp.json();

                loadingArea.style.display = 'none';

                if (data.success) {
                    formArea.style.display = 'block';
                    document.getElementById('userInfo').innerHTML = 'Redefinindo senha de: <strong>' + data.nome + '</strong> (' + data.email + ')';
                } else {
                    msgArea.innerHTML = '<div class="error-msg-box">' + data.message + '</div>';
                }
            } catch (err) {
                loadingArea.style.display = 'none';
                msgArea.innerHTML = '<div class="error-msg-box">Erro ao verificar o link. Tente novamente.</div>';
            }
        }

        init();

        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const novaSenha = document.getElementById('novaSenha').value;
            const confirmarSenha = document.getElementById('confirmarSenha').value;
            const btn = document.getElementById('btnReset');
            const msgArea = document.getElementById('msgArea');

            if (novaSenha !== confirmarSenha) {
                msgArea.innerHTML = '<div class="error-msg-box">As senhas não coincidem!</div>';
                return;
            }

            if (novaSenha.length < 4) {
                msgArea.innerHTML = '<div class="error-msg-box">A senha deve ter pelo menos 4 caracteres.</div>';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Redefinindo...';
            msgArea.innerHTML = '';

            try {
                const resp = await fetch('/api/redefinir-senha', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token, nova_senha: novaSenha })
                });
                const data = await resp.json();

                if (data.success) {
                    msgArea.innerHTML = '<div class="success-msg">' + data.message + '</div>';
                    document.getElementById('formArea').style.display = 'none';
                } else {
                    msgArea.innerHTML = '<div class="error-msg-box">' + data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Redefinir Senha';
                }
            } catch (err) {
                msgArea.innerHTML = '<div class="error-msg-box">Erro de conexão. Tente novamente.</div>';
                btn.disabled = false;
                btn.textContent = 'Redefinir Senha';
            }
        });
    </script>
</body>
</html>
