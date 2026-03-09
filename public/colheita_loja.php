<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loja da Fazenda</title>
<script src="/js/toast.js"></script>
<style>
    :root { --gold: #f39c12; --kutcoin: #4CAF50; --bg: #e4ebf5; --main-color: #3b5998; }
    body { font-family: Tahoma, Arial, sans-serif; background: var(--bg); margin: 0; padding: 15px; color: #333; }
    
    /* Header conecta nas abas (sem borda embaixo) */
    .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #c0d0e6; border-bottom: none; }
    .bal { font-weight: bold; font-size: 15px; display: flex; align-items: center; gap: 8px; }
    .bal-gold { color: #d35400; }
    .bal-kc { color: #27ae60; }
    
    /* Sistema de abas estilo original */
    .tabs-container { display: flex; align-items: flex-end; background: #c0d0e6; padding: 10px 20px 0 20px; border-left: 1px solid #c0d0e6; border-right: 1px solid #c0d0e6; gap: 5px; }
    .tab-btn { background: #dbe3ef; border: 1px solid #a5bce3; border-bottom: none; padding: 10px 20px; font-family: Tahoma, Arial, sans-serif; font-weight: bold; font-size: 13px; color: #555; cursor: pointer; border-radius: 8px 8px 0 0; transition: 0.2s; margin-bottom: -1px; }
    .tab-btn:hover { background: #eef4ff; color: var(--main-color); }
    .tab-btn.active { background: #fff; color: var(--main-color); border-color: #c0d0e6; z-index: 2; position: relative; }
    
    .tab-content { display: none; background: #fff; padding: 20px; border: 1px solid #c0d0e6; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); max-height: 420px; overflow-y: auto; }
    .tab-content.active { display: block; }
    .tab-content::-webkit-scrollbar { width: 8px; }
    .tab-content::-webkit-scrollbar-track { background: #f4f7fc; border-radius: 4px; }
    .tab-content::-webkit-scrollbar-thumb { background: #a5bce3; border-radius: 4px; }
    .tab-content::-webkit-scrollbar-thumb:hover { background: #6d84b4; }

    /* Grade da loja (3 itens por linha, 6 slots por tela) */
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; align-items: stretch; }
    
    .shop-card { background: #fff; border: 1px solid #e4ebf5; padding: 15px; border-radius: 8px; text-align: center; transition: transform 0.2s, box-shadow 0.2s; position: relative; display: grid; grid-template-rows: 1fr auto; }
    .shop-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); border-color: #a5bce3; z-index: 10; }
    .shop-card.premium { border-color: #a5d6a7; background: #f1f8e9; }
    .shop-card > div:first-child { display: flex; flex-direction: column; }
    
    .shop-img { width: 60px; height: 60px; margin: 0 auto 10px auto; background-size: contain; background-repeat: no-repeat; background-position: center; filter: drop-shadow(0 4px 4px rgba(0,0,0,0.15)); transition: transform 0.3s; cursor: pointer; }
    .shop-card:hover .shop-img { transform: scale(1.15) rotate(5deg); }
    
    .shop-name { font-weight: bold; font-size: 13px; color: var(--main-color); margin-bottom: 8px; min-height: 36px; display: flex; align-items: flex-end; justify-content: center; }
    .shop-desc { font-size: 10px; color: #666; margin-bottom: 12px; background: #f9fbfc; padding: 6px; border-radius: 4px; border: 1px solid #ecf0f1; text-align: left; line-height: 1.4; flex: 1; min-height: 50px; }
    .shop-desc b { color: #333; }

    #mouse-tooltip { display: none; position: fixed; background: rgba(0,0,0,0.9); color: #fff; padding: 8px 14px; border-radius: 8px; font-size: 12px; z-index: 9999; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.4); max-width: 220px; text-align: center; line-height: 1.4; white-space: normal; border: 1px solid rgba(255,255,255,0.15); }
    
    /* Botões de compra */
    .btn-buy-img { border: none; padding: 5px 12px; cursor: pointer; min-width: 66px; height: 25px; background: linear-gradient(to bottom, #5b9bd5, #3a75b0); border-radius: 4px; transition: transform 0.2s, box-shadow 0.2s; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); border: 1px solid #2e6090; }
    .btn-buy-img img { display: none; }
    .btn-buy-img span { color: #fff; font-size: 11px; font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); white-space: nowrap; }
    .btn-buy-img:hover { transform: scale(1.08); box-shadow: 0 3px 8px rgba(0,0,0,0.3); }
    .btn-buy-img:active { transform: scale(0.95); }
    .btn-buy-img:disabled { filter: grayscale(1); opacity: 0.6; cursor: not-allowed; transform: none; }
    .btn-buy-kc { background: linear-gradient(to bottom, #5cb85c, #3d8b3d); border-color: #2d6e2d; }

    .buy-actions { display: flex; align-items: center; justify-content: center; min-height: 35px; padding-top: 5px; }
    .dual-buy { display: flex; gap: 6px; justify-content: center; }
    .dual-buy .btn-buy-img { width: 76px; }
    .dual-buy .btn-buy-img span { font-size: 10px; }

    #msg-toast { position: fixed; bottom: -50px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.9); color: #fff; padding: 15px 30px; border-radius: 30px; font-size: 14px; font-weight: bold; transition: bottom 0.4s, opacity 0.4s; opacity: 0; pointer-events: none; z-index: 9999; border: 2px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
    #msg-toast.show { bottom: 30px; opacity: 1; }

    @media (max-width: 600px) { .grid { grid-template-columns: repeat(2, 1fr); } .tab-btn { padding: 8px 12px; font-size: 11px; } }
</style>
</head>
<body>
    <div class="header">
        <div class="bal bal-gold">🪙 <span id="my-gold">0</span></div>
        <div class="bal bal-kc">K$ <span id="my-kc">0</span></div>
    </div>

    <div class="tabs-container">
        <button class="tab-btn active" onclick="switchTab('sementes', this)">🌱 Sementes</button>
        <button class="tab-btn" onclick="switchTab('fertilizantes', this)">🧪 Fertilizantes</button>
        <button class="tab-btn" onclick="switchTab('flores', this)">🌺 Flores</button>
        <button class="tab-btn" onclick="switchTab('animais', this)">🐄 Animais</button>
        <button class="tab-btn" onclick="switchTab('decoracao', this)">🏡 Decoração</button>
    </div>

    <div id="tab-sementes" class="tab-content active">
        <div class="grid" id="shop-grid-seeds"></div>
    </div>
    <div id="tab-fertilizantes" class="tab-content">
        <div class="grid" id="shop-grid-ferts"></div>
    </div>
    <div id="tab-flores" class="tab-content">
        <div class="grid"><div style="grid-column: 1 / -1; text-align:center; padding: 40px; color:#888; border:1px dashed #ccc; border-radius:8px;">Em breve...</div></div>
    </div>
    <div id="tab-animais" class="tab-content">
        <div class="grid"><div style="grid-column: 1 / -1; text-align:center; padding: 40px; color:#888; border:1px dashed #ccc; border-radius:8px;">Em breve...</div></div>
    </div>
    <div id="tab-decoracao" class="tab-content">
        <div class="grid"><div style="grid-column: 1 / -1; text-align:center; padding: 40px; color:#888; border:1px dashed #ccc; border-radius:8px;">Em breve...</div></div>
    </div>

    <div id="mouse-tooltip"></div>
    <div id="msg-toast">Mensagem</div>

    <script>
        function moneySymbol(moneytype) {
            return moneytype === 2 ? 'K$' : '🪙';
        }
        function moneyCurrency(moneytype) {
            return moneytype === 2 ? 'kutcoin' : 'gold';
        }

        function switchTab(tab, btn) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            btn.classList.add('active');
        }

        function showMsg(text, isError = false) {
            const toast = document.getElementById('msg-toast');
            toast.innerText = text;
            toast.style.borderColor = isError ? '#ffcccc' : '#8bc59e';
            toast.style.background = isError ? 'rgba(204,0,0,0.9)' : 'rgba(42,107,42,0.9)';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }

        function updateBalances() {
            if (window.parent && typeof window.parent.getMyGold === 'function') {
                document.getElementById('my-gold').innerText = window.parent.getMyGold();
                document.getElementById('my-kc').innerText = window.parent.getMyKutCoin();
            }
        }

        function formatTime(seconds) {
            if (seconds >= 3600) return Math.round(seconds / 3600) + 'h';
            if (seconds >= 60) return Math.round(seconds / 60) + ' min';
            return seconds + 's';
        }

        async function loadShop() {
            try {
                // Carregar sementes
                const seedResp = await fetch('/api/colheita/seeds-config');
                const seedData = await seedResp.json();
                if (seedData.success) {
                    const grid = document.getElementById('shop-grid-seeds');
                    const seeds = seedData.seeds;
                    for (let key in seeds) {
                        const seed = seeds[key];
                        const itemKey = 'seed_' + key;
                        const growSeconds = (seed.tempo_fase2 || 60) + (seed.tempo_fase3 || 60) + (seed.tempo_fase4 || 60);
                        const liveSeconds = seed.tempo_fase5 || 300;
                        const mt = seed.moeda === 'kutcoin' ? 2 : 1;
                        const card = document.createElement('div');
                        card.className = 'shop-card' + (mt === 2 ? ' premium' : '');
                        const temporadas = seed.temporadas || 1;
                        const totalRendimento = seed.rendimento * temporadas;
                        const tempText = temporadas > 1 ? `Temporadas: <b>${temporadas}x</b><br>` : '';
                        card.innerHTML = `
                            <div>
                                <div class="shop-img" style="background-image:url('${seed.img}')" data-tooltip="${seed.descricao || seed.nome}"></div>
                                <div class="shop-name">${seed.nome}</div>
                                <div class="shop-desc">
                                    Cresce em: <b>${formatTime(growSeconds)}</b><br>
                                    Vive por: <b>${formatTime(liveSeconds)}</b><br>
                                    ${tempText}Produz: <b>${temporadas > 1 ? totalRendimento + ' (' + seed.rendimento + ' x' + temporadas + ')' : seed.rendimento} un.</b><br>
                                    Cada fruto rende: <b style="color:#d35400;">🪙 ${seed.preco_venda || 1}</b>
                                </div>
                            </div>
                            <div class="buy-actions">
                                <button class="btn-buy-img${mt === 2 ? ' btn-buy-kc' : ''}" onclick="buy(this, '${itemKey}', ${seed.preco_compra}, '${moneyCurrency(mt)}', '${seed.nome}', 'seed')">
                                    <img src="imagens_colheita/ok.png" alt="Comprar">
                                    <span>${moneySymbol(mt)} ${seed.preco_compra}</span>
                                </button>
                            </div>
                        `;
                        const imgEl = card.querySelector('.shop-img');
                        imgEl.addEventListener('mouseenter', showTooltip);
                        imgEl.addEventListener('mousemove', moveTooltip);
                        imgEl.addEventListener('mouseleave', hideTooltip);
                        grid.appendChild(card);
                    }
                }

                // Carregar fertilizantes
                const fertResp = await fetch('/api/colheita/fertilizers-config');
                const fertData = await fertResp.json();
                if (fertData.success) {
                    const grid = document.getElementById('shop-grid-ferts');
                    const ferts = fertData.fertilizers;
                    for (let key in ferts) {
                        const fert = ferts[key];
                        const itemKey = 'fert_' + key;
                        const hasGold = fert.preco_gold > 0;
                        const hasKC = fert.preco_kutcoin > 0;
                        const isPremium = hasKC && !hasGold;
                        const card = document.createElement('div');
                        card.className = 'shop-card' + (isPremium ? ' premium' : '');

                        let priceHtml = '';
                        if (hasGold && hasKC) {
                            priceHtml = `🪙 ${fert.preco_gold} &nbsp;ou&nbsp; K$ ${fert.preco_kutcoin}`;
                        } else if (hasGold) {
                            priceHtml = `🪙 ${fert.preco_gold}`;
                        } else if (hasKC) {
                            priceHtml = `K$ ${fert.preco_kutcoin}`;
                        }

                        let buttonsInner = '';
                        if (hasGold && hasKC) {
                            buttonsInner = `
                                <div class="dual-buy">
                                    <button class="btn-buy-img" onclick="buy(this, '${itemKey}', ${fert.preco_gold}, 'gold', '${fert.nome}', 'fert')">
                                        <img src="imagens_colheita/ok.png" alt="Comprar">
                                        <span>🪙 ${fert.preco_gold}</span>
                                    </button>
                                    <button class="btn-buy-img btn-buy-kc" onclick="buy(this, '${itemKey}', ${fert.preco_kutcoin}, 'kutcoin', '${fert.nome}', 'fert')">
                                        <img src="imagens_colheita/ok.png" alt="Comprar">
                                        <span>K$ ${fert.preco_kutcoin}</span>
                                    </button>
                                </div>`;
                        } else if (hasGold) {
                            buttonsInner = `
                                <button class="btn-buy-img" onclick="buy(this, '${itemKey}', ${fert.preco_gold}, 'gold', '${fert.nome}', 'fert')">
                                    <img src="imagens_colheita/ok.png" alt="Comprar">
                                    <span>🪙 ${fert.preco_gold}</span>
                                </button>`;
                        } else if (hasKC) {
                            buttonsInner = `
                                <button class="btn-buy-img btn-buy-kc" onclick="buy(this, '${itemKey}', ${fert.preco_kutcoin}, 'kutcoin', '${fert.nome}', 'fert')">
                                    <img src="imagens_colheita/ok.png" alt="Comprar">
                                    <span>K$ ${fert.preco_kutcoin}</span>
                                </button>`;
                        }
                        let buttonsHtml = `<div class="buy-actions">${buttonsInner}</div>`;

                        card.innerHTML = `
                            <div>
                                <div class="shop-img" style="background-image:url('imagens_colheita/fertilizantes/${fert.icone}')" data-tooltip="${fert.descricao || fert.nome}"></div>
                                <div class="shop-name">${fert.nome}</div>
                                <div class="shop-desc">
                                    Reduz: ${formatTime(fert.tempo_reducao)}<br>
                                    Preço: ${priceHtml}
                                </div>
                            </div>
                            ${buttonsHtml}
                        `;
                        const imgEl = card.querySelector('.shop-img');
                        imgEl.addEventListener('mouseenter', showTooltip);
                        imgEl.addEventListener('mousemove', moveTooltip);
                        imgEl.addEventListener('mouseleave', hideTooltip);
                        grid.appendChild(card);
                    }
                }

                updateBalances();
            } catch(e) {
                console.error('Erro carregar loja:', e);
            }
        }

        let isProcessing = false;

        window.buy = function(btnElement, item, price, currency, itemName, type) {
            if (isProcessing) return;
            
            const currencySymbol = currency === 'kutcoin' ? 'K$' : '🪙';
            const confirmMsg = `Deseja comprar ${itemName} por ${currencySymbol} ${price}?`;
            
            window.showConfirm(confirmMsg, function() {
                processBuy(btnElement, item, price, currency, type);
            }, { title: 'Confirmar Compra' });
        };
        
        function processBuy(btnElement, item, price, currency, type) {
            if (window.parent && typeof window.parent.buyItem === 'function') {
                isProcessing = true;
                const textSpan = btnElement.querySelector('span');
                const originalText = textSpan.innerText;
                btnElement.disabled = true;
                textSpan.innerText = 'Comprando...';

                let res = window.parent.buyItem(item, price, currency);
                
                if (res === true) {
                    updateBalances();
                    const msg = type === 'fert' ? 'Fertilizante comprado! Enviado ao inventário.' : 'Semente comprada! Enviada ao armazém.';
                    showMsg(msg);
                    setTimeout(() => { 
                        isProcessing = false; 
                        btnElement.disabled = false; 
                        textSpan.innerText = originalText;
                    }, 500);
                } else if (res === 'pending') {
                    setTimeout(() => {
                        updateBalances();
                        isProcessing = false;
                        btnElement.disabled = false;
                        textSpan.innerText = originalText;
                    }, 1000);
                } else {
                    showMsg("Você não tem saldo suficiente!", true);
                    isProcessing = false;
                    btnElement.disabled = false;
                    textSpan.innerText = originalText;
                }
            } else {
                alert("Erro: A loja deve ser aberta por dentro do jogo.");
            }
        }

        const tooltipEl = document.getElementById('mouse-tooltip');
        function showTooltip(e) {
            tooltipEl.innerText = e.currentTarget.dataset.tooltip;
            tooltipEl.style.display = 'block';
            moveTooltip(e);
        }
        function moveTooltip(e) {
            tooltipEl.style.left = (e.clientX + 15) + 'px';
            tooltipEl.style.top = (e.clientY + 15) + 'px';
        }
        function hideTooltip() {
            tooltipEl.style.display = 'none';
        }

        loadShop();
    </script>
</body>
</html>
