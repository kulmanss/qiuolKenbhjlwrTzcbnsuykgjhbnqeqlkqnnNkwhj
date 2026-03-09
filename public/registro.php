<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="description" content="Crie sua conta no Yorkut e conecte-se com amigos, comunidades e muito mais!">
    <title>Yorkut - Criar Conta</title>
    <link rel="stylesheet" href="/css/registro.css">
</head>
<body>
<div class="container">
    <div class="warning-box">Aviso: O yorkut.com.br não tem vínculo com o Google.</div>
    <div class="content-wrapper">
        <div class="left-col">
            <div class="logo">yorkut<sup>BR</sup></div>
            <div class="mobile-signup-text">Crie sua conta na rede</div>
            <div class="features">
                <span>Conecta-se</span> aos seus amigos e familiares usando recados e mensagens instantâneas<br>
                <span>Conheça</span> novas pessoas através de amigos de seus amigos e comunidades<br>
                <span>Compartilhe</span> seus vídeos, fotos e paixões em um só lugar
            </div>
        </div>
        <div class="right-col">

            <!-- ETAPA 1: Validar Token de Convite -->
            <div class="login-box" id="step-token">
                <h2>Bem-vindo(a) ao yorkut!</h2>
                <p>Para criar sua conta, insira o <b>código de convite (token)</b> que um amigo te enviou. O yorkut é uma rede exclusiva!</p>
                <div id="token-error" class="error-msg" style="display:none;"></div>
                <form id="tokenForm">
                    <div class="form-group">
                        <label>Código do Convite:</label>
                        <input type="text" name="token_convite" id="token_convite" class="token-input" maxlength="32" required placeholder="Digite seu token...">
                    </div>
                    <button type="submit" class="btn-login">Validar Convite</button>
                </form>
                <a href="/index.php" class="login-link">Já tem uma conta? Entrar</a>
            </div>

            <!-- ETAPA 2: Formulário de Cadastro (aparece após token válido) -->
            <div class="login-box" id="step-register" style="display:none;">
                <h2>Criar sua conta</h2>
                <p>Preencha seus dados abaixo para finalizar o cadastro.</p>
                <div id="register-error" class="error-msg" style="display:none;"></div>
                <div id="register-success" class="success-msg" style="display:none;"></div>
                <form id="registerForm">
                    <input type="hidden" id="validated_token" name="validated_token">

                    <div class="form-group">
                        <label for="nome">Nome completo:</label>
                        <input type="text" id="nome" name="nome" required placeholder="Seu nome completo">
                    </div>

                    <div class="form-group">
                        <label for="reg_email">E-mail:</label>
                        <input type="email" id="reg_email" name="email" required placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="reg_senha">Senha:</label>
                        <input type="password" id="reg_senha" name="senha" required placeholder="Mínimo 6 caracteres" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="reg_senha2">Confirmar senha:</label>
                        <input type="password" id="reg_senha2" name="senha2" required placeholder="Repita a senha">
                    </div>

                    <div class="form-group">
                        <label for="nascimento">Data de nascimento:</label>
                        <input type="date" id="nascimento" name="nascimento" required>
                    </div>

                    <div class="form-group">
                        <label>Sexo:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="sexo" value="M" required> Masculino</label>
                            <label><input type="radio" name="sexo" value="F"> Feminino</label>
                            <label><input type="radio" name="sexo" value="O"> Outro</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="whatsapp">WhatsApp:</label>
                        <div class="whatsapp-group">
                            <select name="ddi" id="ddi">
                                <option value="+55">🇧🇷 +55</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+351">🇵🇹 +351</option>
                            </select>
                            <input type="text" id="whatsapp" name="whatsapp" placeholder="(99) 99999-9999" oninput="formatarWhatsApp(this)" maxlength="15">
                        </div>
                    </div>

                    <div class="terms-group">
                        <input type="checkbox" id="termos" name="termos" required>
                        <label for="termos">Li e concordo com os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a> do Yorkut.</label>
                    </div>

                    <button type="submit" class="btn-login">Criar minha conta</button>
                </form>
                <a href="/index.php" class="login-link">Já tem uma conta? Entrar</a>
            </div>

        </div>
    </div>
    <div class="footer">
        &copy; 2026 Yorkut &bull; <a href="#">Sobre o Yorkut</a> &bull; <a href="#">Centro de segurança</a> &bull; <a href="#">Privacidade</a> &bull; <a href="#">Termos</a> &bull; <a href="#">Contato</a>
    </div>
</div>

<script>
    function formatarWhatsApp(el) {
        let v = el.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 2) {
            v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
            v = v.replace(/(\d{5})(\d{1,4})$/, '$1-$2');
        }
        el.value = v;
    }

    // ETAPA 1: Validar token
    document.getElementById('tokenForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = document.getElementById('token_convite').value.trim();
        const errorDiv = document.getElementById('token-error');
        errorDiv.style.display = 'none';

        if (!token) {
            errorDiv.textContent = 'Digite o código do convite!';
            errorDiv.style.display = 'block';
            return;
        }

        try {
            const res = await fetch('/api/validar-token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });
            const data = await res.json();

            if (data.valid) {
                document.getElementById('step-token').style.display = 'none';
                document.getElementById('step-register').style.display = 'block';
                document.getElementById('validated_token').value = token;
            } else {
                errorDiv.textContent = data.message || 'Token inválido ou já utilizado!';
                errorDiv.style.display = 'block';
            }
        } catch (err) {
            errorDiv.textContent = 'Erro de conexão com o servidor.';
            errorDiv.style.display = 'block';
        }
    });

    // ETAPA 2: Registrar conta
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errorDiv = document.getElementById('register-error');
        const successDiv = document.getElementById('register-success');
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const senha = document.getElementById('reg_senha').value;
        const senha2 = document.getElementById('reg_senha2').value;

        if (senha !== senha2) {
            errorDiv.textContent = 'As senhas não coincidem!';
            errorDiv.style.display = 'block';
            return;
        }

        if (senha.length < 6) {
            errorDiv.textContent = 'A senha deve ter no mínimo 6 caracteres!';
            errorDiv.style.display = 'block';
            return;
        }

        const termos = document.getElementById('termos');
        if (!termos.checked) {
            errorDiv.textContent = 'Você deve aceitar os termos de uso!';
            errorDiv.style.display = 'block';
            return;
        }

        const sexoRadio = document.querySelector('input[name="sexo"]:checked');
        const formData = {
            token: document.getElementById('validated_token').value,
            nome: document.getElementById('nome').value.trim(),
            email: document.getElementById('reg_email').value.trim(),
            senha: senha,
            nascimento: document.getElementById('nascimento').value,
            sexo: sexoRadio ? sexoRadio.value : '',
            ddi: document.getElementById('ddi').value,
            whatsapp: document.getElementById('whatsapp').value.replace(/\D/g, '')
        };

        try {
            const res = await fetch('/api/registro', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();

            if (data.success) {
                successDiv.textContent = 'Conta criada com sucesso! Redirecionando...';
                successDiv.style.display = 'block';
                document.getElementById('registerForm').style.display = 'none';
                setTimeout(() => { window.location.href = '/home.php'; }, 2000);
            } else {
                errorDiv.textContent = data.message || 'Erro ao criar conta.';
                errorDiv.style.display = 'block';
            }
        } catch (err) {
            errorDiv.textContent = 'Erro de conexão com o servidor.';
            errorDiv.style.display = 'block';
        }
    });
</script>
</body>
</html>
