// ===== Login Script =====
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Remove erros anteriores
            const existingError = document.querySelector('.error-msg');
            if (existingError) existingError.remove();

            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value.trim();

            if (!email || !senha) {
                showError('Preencha todos os campos!');
                return;
            }

            const btn = loginForm.querySelector('.btn-login');
            const originalText = btn.textContent;
            btn.textContent = 'Entrando...';
            btn.disabled = true;

            try {
                const lembrar = document.getElementById('lembrar')?.checked || false;
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, senha, lembrar })
                });
                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect || '/home.php';
                } else if (data.banned) {
                    window.location.href = data.redirect || '/banido.php';
                } else {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    showError(data.message || 'E-mail ou senha incorretos!');
                }
            } catch (err) {
                btn.textContent = originalText;
                btn.disabled = false;
                showError('Erro de conexão com o servidor.');
            }
        });
    }
});

function showError(message) {
    const loginBox = document.querySelector('.login-box');
    const h2 = loginBox.querySelector('h2');
    const existing = loginBox.querySelector('.error-msg');
    if (existing) existing.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-msg';
    errorDiv.textContent = message;
    h2.insertAdjacentElement('afterend', errorDiv);
}
