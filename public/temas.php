<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Temas</title>
<link rel="stylesheet" href="/styles/main.css">
<style>
    .themes-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        font-size: 11px;
        color: #555;
    }
    .themes-header form {
        margin: 0;
    }
    .themes-header label {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    .themes-header span {
        font-style: italic;
        color: #888;
    }
    .theme-category {
        margin-bottom: 25px;
    }
    .theme-category-title {
        font-weight: bold;
        color: var(--title);
        font-size: 12px;
        margin-bottom: 10px;
        border-bottom: 1px solid var(--line);
        padding-bottom: 4px;
    }
    .theme-list {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .theme-item {
        width: 110px;
        text-align: center;
        font-size: 11px;
    }
    .theme-preview {
        width: 80px;
        height: 80px;
        margin: 0 auto 6px;
        border: 1px solid #ccc;
        background-size: auto;
        background-repeat: repeat;
        position: relative;
        overflow: hidden;
    }
    .theme-preview-inner {
        position: absolute;
        bottom: 4px;
        left: 4px;
        right: 4px;
        height: 26px;
        background-color: #ffffff;
        border-radius: 2px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        padding: 3px;
        box-sizing: border-box;
    }
    .theme-color-block {
        width: 55%;
        height: 14px;
        border-radius: 2px;
    }
    .theme-color-block-secondary {
        width: 35%;
        height: 10px;
        border-radius: 2px;
    }
    .theme-name {
        margin-bottom: 4px;
        color: #003399;
    }
    .theme-name span {
        display: block;
    }
    .theme-button {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 2px;
        border: 1px solid #ccc;
        background-color: #f5f5f5;
        cursor: pointer;
    }
    .theme-button:hover {
        background-color: #eaeaea;
    }
    .theme-selected-label {
        display: inline-block;
        margin-top: 3px;
        font-size: 10px;
        color: #008000;
    }
    @media (max-width: 768px) {
        .theme-list {
            gap: 10px;
        }
        .theme-item {
            width: 85px;
        }
        .theme-preview {
            width: 70px;
            height: 70px;
        }
    }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Temas do perfil</div>
        <div class="card">
            <h1 class="orkut-name" style="font-size: 22px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 10px;">Temas</h1>
            <div class="themes-header">
                <div id="tema-atual-info">Tema atual: <b>sem tema aplicado</b></div>
                <form method="POST">
                    <input type="hidden" name="sem_tema_toggle" value="1">
                    <label><input type="checkbox" id="chk-sem-tema" onchange="this.form.submit();"> Navegar no yorkut sem temas</label>
                </form>
            </div>

            <div id="themes-container">
                <!-- Temas carregados dinamicamente -->
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'temas' });
    carregarTemas();
});

async function carregarTemas() {
    try {
        const resp = await fetch('/api/tema');
        const data = await resp.json();

        const temaAtualId = data.tema_id;
        const semTema = data.sem_tema;
        const temas = data.temas;

        // Atualizar info do tema atual
        const infoEl = document.getElementById('tema-atual-info');
        if (temaAtualId && temas[temaAtualId]) {
            infoEl.innerHTML = 'Tema atual: <b>' + temas[temaAtualId].nome + '</b> &nbsp; <form method="POST" style="display:inline;"><input type="hidden" name="remover_tema" value="1"><button type="submit" class="theme-button" style="font-size:10px;">remover tema</button></form>';
        } else {
            infoEl.innerHTML = 'Tema atual: <b>sem tema aplicado</b>';
        }

        // Atualizar checkbox sem tema
        document.getElementById('chk-sem-tema').checked = semTema ? true : false;

        // Agrupar por categoria (ordem fixa igual ao original)
        const categorias = {};
        const ordemCategorias = ['Caveiras', 'Desenhos abstratos', 'Materiais', 'Times'];

        for (const [id, tema] of Object.entries(temas)) {
            if (!categorias[tema.categoria]) categorias[tema.categoria] = [];
            categorias[tema.categoria].push({ id: id, ...tema });
        }

        // Renderizar
        const container = document.getElementById('themes-container');
        let html = '';

        ordemCategorias.forEach(cat => {
            if (!categorias[cat]) return;
            html += '<div class="theme-category">';
            html += '<div class="theme-category-title">' + cat + '</div>';
            html += '<div class="theme-list">';

            categorias[cat].forEach(tema => {
                const isSelected = parseInt(tema.id) === temaAtualId;
                html += '<div class="theme-item">';
                html += '<div class="theme-preview" style="background-image:url(\'/' + tema.imagem + '\'); background-size: cover;">';
                html += '<div class="theme-preview-inner">';
                html += '<div class="theme-color-block" style="background-color: ' + tema.cor1 + ';"></div>';
                html += '<div class="theme-color-block-secondary" style="background-color: ' + tema.cor2 + ';"></div>';
                html += '</div></div>';
                html += '<div class="theme-name"><span>' + tema.nome + '</span></div>';

                if (isSelected) {
                    html += '<span class="theme-selected-label">✔ tema aplicado</span>';
                } else {
                    html += '<form method="POST" style="margin:0;">';
                    html += '<input type="hidden" name="theme_id" value="' + tema.id + '">';
                    html += '<button type="submit" name="aplicar_tema" class="theme-button">visualizar</button>';
                    html += '</form>';
                }

                html += '</div>';
            });

            html += '</div></div>';
        });

        container.innerHTML = html;

    } catch (err) {
        console.error('Erro ao carregar temas:', err);
    }
}
</script>
</body>
</html>
