<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minha Bolsa</title>
<style>
    body { font-family: Tahoma, Arial, sans-serif; background: #f4f7fc; margin: 0; padding: 20px; color: #333; }
    
    .section-title { font-size: 14px; font-weight: bold; color: #3b5998; margin: 15px 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #c0d0e6; }
    .section-title:first-child { margin-top: 0; }
    
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
    
    .seed-card { background: #fff; border: 1px solid #c0d0e6; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s; }
    .seed-card:hover { transform: translateY(-3px); border-color: #a5bce3; box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
    .seed-card.fert-card { border-color: #a5d6a7; }
    .seed-card.fert-card:hover { border-color: #66bb6a; }
    
    .seed-img { width: 60px; height: 60px; margin: 0 auto 10px auto; background-size: contain; background-repeat: no-repeat; background-position: center; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15)); }
    .seed-card:hover .seed-img { transform: scale(1.1); transition: 0.3s; }
    
    .seed-name { font-weight: bold; font-size: 13px; color: #3b5998; margin-bottom: 5px; }
    .seed-qtd { font-size: 11px; color: #555; margin-bottom: 12px; background: #fdfdfd; padding: 4px; border: 1px solid #eee; border-radius: 4px; font-weight: bold;}
    .fert-desc { font-size: 10px; color: #888; margin-bottom: 8px; }
    
    .btn-plant { border: none; padding: 10px; font-weight: bold; font-size: 12px; border-radius: 20px; cursor: pointer; width: 100%; color: #fff; background: linear-gradient(to bottom, #4CAF50, #2E7D32); transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .btn-plant:hover { background: linear-gradient(to bottom, #43A047, #1B5E20); transform: scale(1.05); }
    .btn-plant:active { transform: scale(0.95); }

    .btn-fert { background: linear-gradient(to bottom, #7B1FA2, #4A148C); }
    .btn-fert:hover { background: linear-gradient(to bottom, #6A1B9A, #38006b); }

    .empty-msg { grid-column: 1 / -1; text-align:center; padding: 40px; color:#888; background:#fff; border-radius:8px; border:1px dashed #ccc; }
    .empty-msg h3 { margin-top:0; color:#bbb; }
</style>
</head>
<body>
    <div id="bag-content"></div>

    <script>
        async function loadBag() {
            let SEEDS = {};
            let FERTS = {};
            try {
                const [seedResp, fertResp] = await Promise.all([
                    fetch('/api/colheita/seeds-config').then(r => r.json()),
                    fetch('/api/colheita/fertilizers-config').then(r => r.json())
                ]);
                if (seedResp.success) SEEDS = seedResp.seeds;
                if (fertResp.success) FERTS = fertResp.fertilizers;
            } catch(e) {}

            const container = document.getElementById('bag-content');

            if (!window.parent || typeof window.parent.getInventory !== 'function') {
                container.innerHTML = '<div class="empty-msg"><h3>Erro</h3>Abra esta bolsa por dentro da Fazenda.</div>';
                return;
            }

            const inventory = window.parent.getInventory();
            container.innerHTML = '';
            let hasAnything = false;

            // === SEMENTES ===
            let seedsHtml = '';
            for (let key in inventory) {
                if (key.startsWith('seed_')) {
                    let realKey = key.replace('seed_', '');
                    let qtd = inventory[key];
                    if (qtd > 0 && SEEDS[realKey]) {
                        hasAnything = true;
                        let seed = SEEDS[realKey];
                        let imgUrl = seed.f1_img || 'imagens_colheita/fase1_semente.png';
                        seedsHtml += `
                            <div class="seed-card">
                                <div class="seed-img" style="background-image:url('${imgUrl}')"></div>
                                <div class="seed-name">Semente de ${seed.nome}</div>
                                <div class="seed-qtd">Na bolsa: ${qtd}</div>
                                <button class="btn-plant" onclick="plant('${key}')">🌱 Pegar</button>
                            </div>
                        `;
                    }
                }
            }
            if (seedsHtml) {
                container.innerHTML += '<div class="section-title">🌱 Sementes</div><div class="grid">' + seedsHtml + '</div>';
            }

            // === FERTILIZANTES ===
            let fertsHtml = '';
            for (let key in inventory) {
                if (key.startsWith('fert_')) {
                    let realKey = key.replace('fert_', '');
                    let qtd = inventory[key];
                    if (qtd > 0 && FERTS[realKey]) {
                        hasAnything = true;
                        let fert = FERTS[realKey];
                        let timeStr = fert.tempo_reducao >= 3600 ? Math.round(fert.tempo_reducao / 3600) + 'h' : (fert.tempo_reducao >= 60 ? Math.round(fert.tempo_reducao / 60) + ' min' : fert.tempo_reducao + 's');
                        fertsHtml += `
                            <div class="seed-card fert-card">
                                <div class="seed-img" style="background-image:url('imagens_colheita/fertilizantes/${fert.icone}')"></div>
                                <div class="seed-name">${fert.nome}</div>
                                <div class="fert-desc">Reduz ${timeStr}</div>
                                <div class="seed-qtd">Na bolsa: ${qtd}</div>
                                <button class="btn-plant btn-fert" onclick="useFert('${key}')">🧪 Usar</button>
                            </div>
                        `;
                    }
                }
            }
            if (fertsHtml) {
                container.innerHTML += '<div class="section-title">🧪 Fertilizantes</div><div class="grid">' + fertsHtml + '</div>';
            }

            if (!hasAnything) {
                container.innerHTML = `
                    <div class="empty-msg">
                        <h3>Bolsa Vazia</h3>
                        Você não tem sementes ou fertilizantes.<br>Vá até a Loja da Fazenda para comprar!
                    </div>
                `;
            }
        }

        window.plant = function(key) {
            if (window.parent && typeof window.parent.selectSeed === 'function') {
                window.parent.selectSeed(key);
            }
        };

        window.useFert = function(fertKey) {
            if (window.parent && typeof window.parent.selectFertilizer === 'function') {
                window.parent.selectFertilizer(fertKey);
            }
        };

        loadBag();
    </script>
</body>
</html>
