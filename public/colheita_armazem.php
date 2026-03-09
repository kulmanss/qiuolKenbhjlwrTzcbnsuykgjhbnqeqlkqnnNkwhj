<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<style>
    body { font-family: Tahoma, sans-serif; background: #f4f7fc; margin: 0; padding: 20px; color: #333; }
    
    #total-gold {
        text-align: right; margin-bottom: 20px; font-weight: bold; color: #d35400; font-size: 16px;
        background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #f2e08c; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .item-list { display: flex; flex-direction: column; gap: 15px; }
    
    .item-card {
        background: #fff; border: 1px solid #c0d0e6; padding: 15px; border-radius: 8px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s;
    }
    .item-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #a5bce3; }
    
    .item-info { display: flex; align-items: center; gap: 15px; }
    .item-img { width: 60px; height: 60px; background-size: contain; background-repeat: no-repeat; background-position: center; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15)); }
    .item-name { font-weight: bold; font-size: 16px; color: #3b5998; text-transform: capitalize; }
    .item-qtd { font-size: 12px; color: #555; margin-top: 4px; font-weight: bold; }
    .item-price { font-size: 11px; color: #888; margin-top: 2px; }

    .sell-controls { display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
    
    input[type=number] { width: 70px; padding: 6px; border: 1px solid #ccc; border-radius: 4px; text-align: center; font-weight: bold; }
    input[type=number]:focus { outline: none; border-color: #f1c40f; }

    .btn-sell {
        background: linear-gradient(to bottom, #f1c40f, #e67e22); border: none; padding: 8px 15px;
        font-weight: bold; border-radius: 20px; cursor: pointer; color: #fff; transition: 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-sell:hover { background: linear-gradient(to bottom, #f39c12, #d35400); transform: scale(1.05); }
    .btn-sell:active { transform: scale(0.95); }

    .empty-msg { text-align:center; color:#999; padding:40px; background:#fff; border-radius:8px; border:1px dashed #ccc; }
    .empty-msg h3 { margin-top:0; color:#bbb; }

    #msg-toast { position: fixed; bottom: -50px; left: 50%; transform: translateX(-50%); background: rgba(42, 107, 42, 0.9); color: #fff; padding: 10px 20px; border-radius: 20px; font-size: 12px; font-weight: bold; transition: 0.3s; opacity: 0; pointer-events: none; z-index: 9999; border: 2px solid #8bc59e; }
    #msg-toast.show { bottom: 20px; opacity: 1; }
</style>
</head>
<body>

<div id="total-gold">
    Ouro no Cofre: 🪙 <span id="gold-val">0</span>
</div>

<div class="item-list" id="armazem-list"></div>

<div id="msg-toast"></div>

<script>
let CONFIG = {};

function showMsg(text) {
    const toast = document.getElementById('msg-toast');
    toast.innerText = text;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

function updateGoldDisplay() {
    if (window.parent && typeof window.parent.getMyGold === 'function') {
        document.getElementById('gold-val').innerText = window.parent.getMyGold();
    }
}

async function render() {
    try {
        const resp = await fetch('/api/colheita/seeds-config');
        const data = await resp.json();
        if (data.success) CONFIG = data.seeds;
    } catch(e) {}

    const inventory = window.parent && typeof window.parent.getInventory === 'function' ? window.parent.getInventory() : {};
    const list = document.getElementById('armazem-list');
    list.innerHTML = '';
    let hasItems = false;

    for (let key in inventory) {
        const qtd = inventory[key] || 0;
        const item = CONFIG[key];

        if (qtd > 0 && item) {
            hasItems = true;
            let imgUrl = item.img || item.f4_img || 'imagens_colheita/itens/1001.png';
            let pVenda = item.preco_venda || 0;

            list.innerHTML += `
                <div class="item-card">
                    <div class="item-info">
                        <div class="item-img" style="background-image:url('${imgUrl}')"></div>
                        <div>
                            <div class="item-name">${item.nome}</div>
                            <div class="item-qtd">Em estoque: ${qtd} un.</div>
                            <div class="item-price">Venda unid: 🪙 ${pVenda}</div>
                        </div>
                    </div>
                    <div class="sell-controls">
                        <input type="number" min="1" max="${qtd}" value="${qtd}" id="qtd-${key}">
                        <button class="btn-sell" onclick="sell('${key}', ${pVenda})">
                            Vender Lucro
                        </button>
                    </div>
                </div>
            `;
        }
    }

    if (!hasItems) {
        list.innerHTML = `
            <div class="empty-msg">
                <h3>Armazém Vazio</h3>
                Você ainda não tem nenhum fruto colhido ou semente.<br>Plante, colha ou visite amigos para encher seu armazém!
            </div>
        `;
    }

    updateGoldDisplay();
}

window.sell = function(key, priceEach) {
    const input = document.getElementById('qtd-' + key);
    let amount = parseInt(input.value);
    if (!amount || amount <= 0) return;

    if (window.parent && typeof window.parent.sellItem === 'function') {
        if (window.parent.sellItem(key, amount, priceEach)) {
            let totalGained = amount * priceEach;
            showMsg(`Vendidos ${amount} itens por 🪙 ${totalGained} de Ouro!`);
            render();
        } else {
            alert("Erro: Você não tem essa quantidade no inventário.");
        }
    } else {
        alert("O armazém deve ser aberto pelo jogo.");
    }
};

render();
</script>

</body>
</html>
