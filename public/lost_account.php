<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorkut - Recuperação de Conta</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos específicos da página de recuperação */
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

        .recovery-form .form-group {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .recovery-form .form-group label {
            width: 100%;
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-bottom: 4px;
        }

        .recovery-form .form-group input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #7f9db9;
            border-radius: 4px;
            background: #fff;
            font-size: 13px;
            outline: none;
            transition: 0.2s;
        }

        .recovery-form .form-group input:focus {
            border-color: #e6399b;
            box-shadow: 0 0 3px rgba(230, 57, 155, 0.4);
        }

        .recovery-form .btn-login {
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

        @media (max-width: 480px) {
            .login-box h2 {
                text-align: center;
                font-size: 18px;
                margin-bottom: 10px;
            }

            .login-box p {
                text-align: center;
                font-size: 13px;
                margin-bottom: 20px;
            }

            .recovery-form .form-group label {
                font-size: 14px;
            }

            .recovery-form .form-group input[type="email"] {
                padding: 14px;
                font-size: 16px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                background: #f9f9f9;
            }

            .recovery-form .btn-login {
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
                    <h2>Recuperação de Conta</h2>

                    <div id="msgArea"></div>

                    <div id="formArea">
                        <p>Insira o seu e-mail cadastrado abaixo. Enviaremos um link para você redefinir sua senha.</p>

                        <form id="recoveryForm" class="recovery-form">
                            <div class="form-group">
                                <label for="email">E-mail cadastrado:</label>
                                <input type="email" id="email" name="email" required placeholder="seu@email.com">
                            </div>
                            <button type="submit" class="btn-login" id="btnRecovery">Recuperar Conta</button>
                        </form>
                    </div>

                    <a href="/index.php" class="login-link">Lembrou a senha? Fazer Login</a>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; 2026 Yorkut - <a href="#">Sobre o Yorkut</a> - <a href="#">Centro de segurança</a> - <a href="#">Privacidade</a> - <a href="#">Termos</a> - <a href="#">Contato</a>
        </div>
    </div>

    <script>
        document.getElementById('recoveryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const btn = document.getElementById('btnRecovery');
            const msgArea = document.getElementById('msgArea');

            if (!email) {
                msgArea.innerHTML = '<div class="error-msg-box">Preencha o campo de e-mail!</div>';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Enviando...';
            msgArea.innerHTML = '';

            try {
                const resp = await fetch('/api/recuperar-conta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const data = await resp.json();

                if (data.success) {
                    msgArea.innerHTML = '<div class="success-msg">' + data.message + '</div>';
                    document.getElementById('formArea').style.display = 'none';
                } else {
                    msgArea.innerHTML = '<div class="error-msg-box">' + data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Recuperar Conta';
                }
            } catch (err) {
                msgArea.innerHTML = '<div class="error-msg-box">Erro ao conectar com o servidor. Tente novamente.</div>';
                btn.disabled = false;
                btn.textContent = 'Recuperar Conta';
            }
        });
    </script>
</body>
</html>
