<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - KutCoin</title>
<link rel="stylesheet" href="/styles/profile.css">
<link rel="stylesheet" href="/css/fontawesome/all.min.css">
<style>
    .kut-card { text-align: center; padding: 20px; }
    .coin-img { width: 120px; margin-bottom: 15px; animation: float 3s ease-in-out infinite; }
    @keyframes float { 0%, 100% {transform: translateY(0);} 50% {transform: translateY(-10px);} }
    .balance-box { background: #eef4ff; border: 1px solid var(--line); border-radius: 8px; padding: 15px; display: inline-flex; align-items: center; gap: 10px; margin-top: 20px; position: relative; }
    .balance-box img { width: 30px; }
    .balance-box span { font-size: 20px; font-weight: bold; color: var(--title); }
    .eye-icon { cursor: pointer; color: #4CAF50; font-size: 16px; margin-left: 10px; }
    .info-text { font-size: 13px; line-height: 1.6; color: #555; max-width: 600px; margin: 20px auto; text-align: justify; }
    .tabs-nav { display: flex; justify-content: center; gap: 10px; margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; flex-wrap: wrap; }
    .tab-btn { background: #f0f0f0; border: none; padding: 10px 20px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.3s; color: #555; }
    .tab-btn.active { background: #4CAF50; color: white; }
    .tab-content { display: none; padding: 20px 0; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .form-carteira { background: #f9f9f9; border: 1px solid #ddd; border-radius: 6px; padding: 20px; text-align: left; }
    .form-carteira h3 { margin-top: 0; color: var(--title); font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    .input-group { margin-bottom: 15px; }
    .input-group label { display: block; font-size: 11px; font-weight: bold; color: #666; margin-bottom: 5px; }
    .input-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .btn-donate { background: #4CAF50; color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; display: inline-block; text-decoration: none; }
    .btn-donate:hover:not(:disabled) { background: #45a049; transform: scale(1.05); }
    .btn-disabled { background: #ccc !important; cursor: not-allowed !important; transform: none !important; }
    .extrato-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .extrato-table th { background: #f0f0f0; padding: 10px; border-bottom: 2px solid #ddd; }
    .extrato-table td { padding: 10px; border-bottom: 1px solid #eee; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Moeda Virtual KutCoin</div>
        
        <div class="card kut-card">
            <div style="font-size:80px; margin-bottom:15px; animation: float 3s ease-in-out infinite;">💰</div>
            <h1 class="orkut-name" style="font-size: 26px;">KutCoin - A Moeda do Yorkut</h1>
            
            <div class="balance-box" id="caixa-saldo">
                <span>💰</span>
                <span id="saldo-valor">K$ 0</span>
                <i class="fas fa-eye eye-icon" id="toggle-saldo" onclick="toggleSaldo()"></i>
            </div>

            <div class="tabs-nav">
                <button class="tab-btn active" onclick="openTab('tab-carteira')">Carteira e Doação</button>
                <button class="tab-btn btn-disabled" disabled>Transferir K$</button>
                <button class="tab-btn btn-disabled" disabled>Extrato</button>
                <button class="tab-btn btn-disabled" disabled><i class="fas fa-cog"></i> Segurança</button>
            </div>

            <div id="tab-carteira" class="tab-content active">
                <div class="form-carteira">
                    <h3>💳 Seus Dados da Carteira Digital</h3>
                    <form method="POST">
                        <div class="input-group">
                            <label>Nome Completo (Sem abreviações):</label>
                            <input type="text" name="nome_completo" required value="">
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div class="input-group"><label>CPF:</label><input type="text" name="cpf" required value=""></div>
                            <div class="input-group"><label>RG:</label><input type="text" name="rg" required value=""></div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div class="input-group"><label>Data de Nascimento:</label><input type="date" name="data_nasc" required value=""></div>
                            <div class="input-group"><label>E-mail Financeiro:</label><input type="email" name="email_financeiro" required value=""></div>
                        </div>
                        <div style="text-align:right;">
                            <button type="submit" name="validar_carteira" class="btn-donate" style="border-radius: 4px; padding: 10px 20px;">Ativar Carteira</button>
                        </div>
                    </form>
                    <div style="text-align:center; margin-top:20px;">
                        <p style="font-size:11px; color:#cc0000; font-weight:bold; margin-bottom: 10px;">⚠️ Ative a carteira para liberar doações e transferências.</p>
                        <button class="btn-donate btn-disabled" disabled>Fazer Doação agora</button>
                    </div>
                </div>
            </div>

            <div id="tab-transferir" class="tab-content">
                <div class="form-carteira">
                    <h3>💸 Transferir KutCoins</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Transfira seus K$ para amigos instantaneamente.</p>
                    <form method="POST">
                        <div class="input-group"><label>E-mail do Amigo:</label><input type="email" name="email_amigo" required placeholder="email@exemplo.com"></div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div class="input-group"><label>Valor (K$):</label><input type="number" name="valor_transferencia" required min="1" placeholder="Ex: 50"></div>
                            <div class="input-group"><label>Seu PIN:</label><input type="password" name="pin_transf" required maxlength="8" placeholder="****"></div>
                        </div>
                        <div style="text-align:right; margin-top:10px;">
                            <button type="submit" class="btn-donate" style="border-radius: 4px; padding: 10px 20px;">Confirmar Transferência</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-extrato" class="tab-content">
                <div class="form-carteira">
                    <h3>📊 Histórico de Transações</h3>
                    <table class="extrato-table">
                        <thead><tr><th>Data / Hora</th><th>Tipo</th><th>Detalhes</th><th>Valor</th></tr></thead>
                        <tbody><tr><td colspan="4" style="text-align:center;">Nenhuma transação encontrada.</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div id="tab-config" class="tab-content">
                <div class="form-carteira">
                    <h3><i class="fas fa-lock"></i> Configurações de Segurança</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Proteja sua carteira habilitando um PIN.</p>
                    <form method="POST">
                        <div class="input-group" style="max-width: 300px;">
                            <label>Criar / Alterar PIN (Apenas Números, até 8 dígitos):</label>
                            <input type="password" name="pin_seguranca" maxlength="8" placeholder="Digite seu PIN">
                            <small style="font-size: 10px; color:#999;">Deixe em branco se quiser remover o PIN.</small>
                        </div>
                        <div style="margin-top:20px;">
                            <button type="submit" class="btn-donate" style="border-radius: 4px; padding: 10px 20px; background:#2196F3;">Salvar Configurações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => { loadLayout({ activePage: 'kutcoin' }); });

let saldoVisivel = true;
function toggleSaldo() {
    let el = document.getElementById('saldo-valor');
    let icon = document.getElementById('toggle-saldo');
    if (saldoVisivel) { el.innerText = 'K$ ***'; icon.className = 'fas fa-eye-slash eye-icon'; }
    else { el.innerText = 'K$ 0'; icon.className = 'fas fa-eye eye-icon'; }
    saldoVisivel = !saldoVisivel;
}

function openTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    let tab = document.getElementById(tabId);
    if (tab) { tab.classList.add('active'); event.target.classList.add('active'); }
}
</script>
</body>
</html>
