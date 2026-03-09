<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Conta Suspensa</title>
<style>
    * { box-sizing: border-box; }
    body {
        background: linear-gradient(to bottom, #dbe3ef 0%, #cdd9ea 100%);
        font-family: Tahoma, Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #000;
        margin: 0;
        padding: 20px 10px;
        display: flex;
        justify-content: center;
        min-height: 100vh;
    }
    a { color: #0000cc; text-decoration: none; }
    a:hover { text-decoration: underline; }

    .container { width: 100%; max-width: 900px; }

    .warning-box {
        background: linear-gradient(#ffe6e6, #ffd6d6);
        border: 1px solid #e08080;
        padding: 10px;
        margin-bottom: 15px;
        color: #cc0000;
        font-weight: bold;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
    }

    .content-wrapper {
        display: flex;
        gap: 15px;
    }

    /* Coluna esquerda - logo */
    .left-col {
        flex: 1;
        background: #fff;
        border: 1px solid #a5bce3;
        padding: 50px 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .logo {
        font-size: 75px;
        font-weight: bold;
        color: #e6399b;
        margin-bottom: 25px;
        letter-spacing: -4px;
        text-shadow: 2px 2px 0 #f4a1cd, 4px 4px 8px rgba(0,0,0,0.1);
    }
    .logo sup { font-size: 14px; color: #888; letter-spacing: 0; text-shadow: none; }
    .left-text {
        line-height: 1.7;
        text-align: center;
        font-size: 13px;
        color: #333;
    }
    .left-text span { color: #b30059; font-weight: bold; }

    /* Coluna direita - info ban */
    .right-col {
        width: 340px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .ban-box {
        background: linear-gradient(#edf3fb, #e4ebf5);
        border: 1px solid #a5bce3;
        padding: 18px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .ban-box h2 {
        font-size: 12px;
        font-weight: bold;
        color: #cc0000;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #c0d0e6;
    }

    .ban-tipo-perm {
        background: linear-gradient(#ffe6e6, #ffcccc);
        border: 1px solid #e08080;
        border-radius: 6px;
        padding: 12px;
        text-align: center;
        margin-bottom: 12px;
    }
    .ban-tipo-perm strong { color: #cc0000; font-size: 13px; }
    .ban-tipo-perm p { color: #8b0000; font-size: 11px; margin-top: 4px; }

    .ban-tipo-temp {
        background: linear-gradient(#fffbdc, #fef8cc);
        border: 1px solid #ffd324;
        border-radius: 6px;
        padding: 12px;
        text-align: center;
        margin-bottom: 12px;
    }
    .ban-tipo-temp strong { color: #996600; font-size: 13px; }

    /* Countdown */
    .ban-countdown {
        background: #fff;
        border: 1px solid #a5bce3;
        border-radius: 6px;
        padding: 12px 8px;
        text-align: center;
        margin-bottom: 12px;
    }
    .ban-countdown-title {
        font-size: 11px;
        color: #666;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .ban-timer { display: flex; justify-content: center; gap: 8px; }
    .ban-timer-unit { text-align: center; }
    .ban-timer-num {
        font-size: 22px;
        font-weight: bold;
        color: #e6399b;
        font-family: Tahoma, Arial, sans-serif;
        background: linear-gradient(#fff, #f0e6f0);
        border: 1px solid #d0a0c0;
        border-radius: 4px;
        padding: 4px 8px;
        min-width: 40px;
        display: inline-block;
    }
    .ban-timer-sep {
        font-size: 22px;
        font-weight: bold;
        color: #999;
        line-height: 34px;
    }
    .ban-timer-label {
        font-size: 9px;
        color: #888;
        text-transform: uppercase;
        margin-top: 2px;
    }

    .ban-info-table {
        width: 100%;
        margin-bottom: 12px;
    }
    .ban-info-table td {
        padding: 4px 0;
        font-size: 11px;
        color: #333;
        border-bottom: 1px dotted #d0dce8;
        vertical-align: top;
    }
    .ban-info-table td:first-child {
        font-weight: bold;
        color: #2f4f87;
        width: 100px;
        white-space: nowrap;
        padding-right: 8px;
    }

    .ban-motivo {
        background: #fff;
        border: 1px solid #d0dce8;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 12px;
    }
    .ban-motivo-title {
        font-size: 11px;
        font-weight: bold;
        color: #2f4f87;
        margin-bottom: 5px;
    }
    .ban-motivo p {
        font-size: 11px;
        color: #555;
        line-height: 1.6;
    }

    .ban-actions { text-align: center; margin-top: 5px; }
    .btn-login {
        padding: 5px 14px;
        background: linear-gradient(#ffffff, #dcdcdc);
        border: 1px solid #666;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-family: Tahoma, Arial, Helvetica, sans-serif;
        text-decoration: none;
        color: #000;
        display: inline-block;
    }
    .btn-login:hover { background: linear-gradient(#f8f8f8, #cfcfcf); text-decoration: none; }

    .join-box {
        background: linear-gradient(#edf3fb, #e4ebf5);
        border: 1px solid #a5bce3;
        padding: 18px 15px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        text-align: center;
    }
    .join-box a {
        font-size: 11px;
        color: #e6399b;
        font-weight: bold;
    }

    .ban-loading { text-align: center; padding: 30px; color: #999; font-size: 11px; }
    .ban-expired { text-align: center; padding: 15px; }
    .ban-expired strong { color: #009900; font-size: 13px; }
    .ban-expired p { color: #666; font-size: 11px; margin: 6px 0 12px; }

    /* Footer */
    .footer {
        background-color: #c0d0e6;
        text-align: center;
        padding: 10px;
        margin-top: 15px;
        border: 1px solid #a5bce3;
        border-radius: 0 0 8px 8px;
        font-size: 11px;
        color: #333;
    }

    @media (max-width: 900px) {
        .content-wrapper { flex-direction: column; }
        .right-col { width: 100%; }
        .left-col { padding: 35px 20px; }
        .logo { font-size: 60px; }
    }
    @media (max-width: 480px) {
        body { padding: 10px; }
        .logo { font-size: 48px; letter-spacing: -2px; }
        .ban-timer-num { font-size: 18px; min-width: 34px; padding: 3px 5px; }
        .ban-timer-sep { font-size: 18px; }
    }
</style>
</head>
<body>

<div class="container">
    <div class="warning-box">
        Aviso: Sua conta está suspensa. Você não pode acessar o yorkut.com.br no momento.
    </div>
    <div class="content-wrapper">
        <div class="left-col">
            <div class="logo">yorkut<sup>BR</sup></div>
            <div class="left-text">
                <span>Sua conta</span> foi suspensa pela equipe de moderação do Yorkut.<br><br>
                Se você acredita que isso foi um erro, entre em contato com a administração.
            </div>
        </div>
        <div class="right-col">
            <div class="ban-box">
                <h2 id="banTitle">&#9888; Conta Suspensa</h2>
                <div id="banBody">
                    <div class="ban-loading">Carregando informações...</div>
                </div>
            </div>
            <div class="join-box">
                <div>Deseja voltar à página de login?</div>
                <a href="/index.php">VOLTAR AO LOGIN</a>
            </div>
        </div>
    </div>
    <div class="footer">
        &copy; 2026 Yorkut - <a href="#">Sobre o Yorkut</a> - <a href="#">Centro de segurança</a> - <a href="#">Privacidade</a> - <a href="#">Termos</a> - <a href="#">Contato</a>
    </div>
</div>

<script>
let _banTimerInterval = null;

async function loadBanInfo() {
    try {
        const resp = await fetch('/api/ban-info');
        const data = await resp.json();
        
        if (!data.success) {
            document.getElementById('banBody').innerHTML = `
                <div style="text-align:center;padding:15px;">
                    <p style="font-size:11px;color:#666;margin-bottom:12px;">Não foi possível carregar as informações ou você não está banido.</p>
                    <a href="/index.php" class="btn-login">Voltar ao Login</a>
                </div>
            `;
            return;
        }

        if (data.nome) {
            document.querySelector('.left-text').innerHTML = 
                '<span>' + escHtml(data.nome) + '</span>, sua conta foi suspensa pela equipe de moderação do Yorkut.<br><br>' +
                'Se você acredita que isso foi um erro, entre em contato com a administração.';
        }

        let html = '';

        if (data.permanent) {
            document.getElementById('banTitle').innerHTML = '&#9888; Banimento Permanente';
            html += `
                <div class="ban-tipo-perm">
                    <strong>BANIMENTO PERMANENTE</strong>
                    <p>Sua conta foi banida permanentemente e não poderá mais acessar o Yorkut.</p>
                </div>
            `;
        } else {
            document.getElementById('banTitle').innerHTML = '&#9888; Suspensão Temporária';
            html += `
                <div class="ban-tipo-temp">
                    <strong>Suspensão Temporária</strong>
                </div>
                <div class="ban-countdown">
                    <div class="ban-countdown-title">Tempo restante da suspensão:</div>
                    <div class="ban-timer" id="banTimer">
                        <div class="ban-timer-unit">
                            <div class="ban-timer-num" id="timerDias">--</div>
                            <div class="ban-timer-label">Dias</div>
                        </div>
                        <div class="ban-timer-sep">:</div>
                        <div class="ban-timer-unit">
                            <div class="ban-timer-num" id="timerHoras">--</div>
                            <div class="ban-timer-label">Horas</div>
                        </div>
                        <div class="ban-timer-sep">:</div>
                        <div class="ban-timer-unit">
                            <div class="ban-timer-num" id="timerMinutos">--</div>
                            <div class="ban-timer-label">Min</div>
                        </div>
                        <div class="ban-timer-sep">:</div>
                        <div class="ban-timer-unit">
                            <div class="ban-timer-num" id="timerSegundos">--</div>
                            <div class="ban-timer-label">Seg</div>
                        </div>
                    </div>
                </div>
            `;
        }

        if (data.motivo) {
            html += `
                <div class="ban-motivo">
                    <div class="ban-motivo-title">Motivo:</div>
                    <p>${escHtml(data.motivo)}</p>
                </div>
            `;
        }

        html += '<table class="ban-info-table">';
        html += '<tr><td>Tipo:</td><td>' + (data.permanent ? 'Permanente' : 'Temporário') + '</td></tr>';
        if (data.banido_em) {
            html += '<tr><td>Banido em:</td><td>' + formatDate(data.banido_em) + '</td></tr>';
        }
        if (!data.permanent && data.banido_ate) {
            html += '<tr><td>Expira em:</td><td>' + formatDate(data.banido_ate) + '</td></tr>';
        }
        html += '</table>';

        html += '<div class="ban-actions"><a href="/index.php" class="btn-login">Voltar ao Login</a></div>';

        document.getElementById('banBody').innerHTML = html;

        if (!data.permanent && data.banido_ate) {
            startCountdown(data.banido_ate);
        }

    } catch(err) {
        console.error('Erro ao carregar ban info:', err);
        document.getElementById('banBody').innerHTML = `
            <div style="text-align:center;padding:15px;">
                <p style="color:#cc0000;font-size:11px;">Erro ao carregar informações.</p>
                <a href="/index.php" class="btn-login" style="margin-top:10px;">Voltar ao Login</a>
            </div>
        `;
    }
}

function startCountdown(banEndStr) {
    function update() {
        const now = new Date();
        const end = new Date(banEndStr);
        let diff = Math.max(0, Math.floor((end - now) / 1000));

        if (diff <= 0) {
            clearInterval(_banTimerInterval);
            document.getElementById('banBody').innerHTML = `
                <div class="ban-expired">
                    <strong>Sua suspensão expirou!</strong>
                    <p>Você já pode acessar sua conta novamente.</p>
                    <a href="/index.php" class="btn-login">Fazer Login</a>
                </div>
            `;
            document.querySelector('.warning-box').style.background = 'linear-gradient(#fffbdc, #fef8cc)';
            document.querySelector('.warning-box').style.borderColor = '#ffd324';
            document.querySelector('.warning-box').style.color = '#009900';
            document.querySelector('.warning-box').textContent = 'Sua suspensão expirou! Você já pode fazer login novamente.';
            return;
        }

        const dias = Math.floor(diff / 86400);
        diff %= 86400;
        const horas = Math.floor(diff / 3600);
        diff %= 3600;
        const minutos = Math.floor(diff / 60);
        const segundos = diff % 60;

        const pad = n => String(n).padStart(2, '0');
        document.getElementById('timerDias').textContent = pad(dias);
        document.getElementById('timerHoras').textContent = pad(horas);
        document.getElementById('timerMinutos').textContent = pad(minutos);
        document.getElementById('timerSegundos').textContent = pad(segundos);
    }

    update();
    _banTimerInterval = setInterval(update, 1000);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const pad = n => String(n).padStart(2, '0');
    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' às ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', loadBanInfo);
</script>
</body>
</html>
