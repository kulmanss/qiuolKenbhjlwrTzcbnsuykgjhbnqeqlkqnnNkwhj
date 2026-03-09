<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yorkut - Reportar Bug</title>
<link rel="stylesheet" href="/styles/profile.css">
<style>
    .bug-form { background: #f9f9f9; border: 1px solid var(--line); border-radius: 6px; padding: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 13px; }
    .form-group input[type="text"], .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 13px; box-sizing: border-box; }
    .form-group input[type="text"]:focus, .form-group textarea:focus { border-color: var(--orkut-blue); outline: none; box-shadow: 0 0 3px rgba(59,89,152,0.3); }
    .form-group textarea { height: 120px; resize: vertical; }
    .file-upload-box { display: flex; gap: 15px; flex-wrap: wrap; }
    .file-item { flex: 1; min-width: 150px; border: 2px dashed #a5bce3; background: #fff; padding: 15px; border-radius: 4px; text-align: center; color: #666; font-size: 12px; position: relative; }
    .file-item input[type="file"] { margin-top: 5px; max-width: 100%; }
    .file-item .preview-img { max-width: 100%; max-height: 80px; margin-top: 6px; border-radius: 4px; display: none; }
    .file-item .remove-img { position: absolute; top: 4px; right: 6px; background: #e74c3c; color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; cursor: pointer; display: none; line-height: 18px; }
    .btn-submit-bug { background-color: var(--orkut-pink); color: #fff; border: none; padding: 12px 25px; font-size: 14px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: 0.2s; }
    .btn-submit-bug:hover { background-color: #c92c85; }
    .btn-submit-bug:disabled { background: #aaa; cursor: not-allowed; }
    .success-msg { background: #e8f5e9; border: 1px solid #a5d6a7; padding: 20px; border-radius: 6px; text-align: center; margin-top: 15px; display: none; }
    .success-msg h3 { color: #2e7d32; margin: 0 0 8px 0; }
    .success-msg p { color: #555; font-size: 13px; margin: 0; }
</style>
</head>
<body>
<div id="app-header"></div>
<div class="container">
    <div id="app-left-col" class="left-col"></div>
    <div class="center-col" style="flex:1;">
        <div class="breadcrumb"><a href="/profile.php">Início</a> > Reportar Bug</div>
        <div class="card">
            <h1 class="orkut-name" style="font-size: 20px; border-bottom: 1px solid var(--line); padding-bottom: 10px; margin-bottom: 20px;">
                🐞 Reportar um Bug / Erro
            </h1>
            <p style="color: #555; font-size: 13px; line-height: 1.5; margin-bottom: 20px;">
                Encontrou algo estranho no Yorkut? Uma página em branco, um botão que não funciona ou algo fora do lugar? 
                Por favor, descreva o problema abaixo para que nossa equipe técnica possa resolver!
            </p>
            <div class="bug-form" id="form-container">
                <form id="bug-form">
                    <div class="form-group"><label>Título do Problema (Resumo)</label><input type="text" id="bug-titulo" placeholder="Ex: Não consigo enviar recado pelo celular" required maxlength="200"></div>
                    <div class="form-group"><label>Descrição Detalhada</label><textarea id="bug-descricao" placeholder="Onde o erro acontece? Qual mensagem aparece na tela? Qual dispositivo você está usando?" required maxlength="5000"></textarea></div>
                    <div class="form-group">
                        <label>Anexar Prints da Tela (Opcional - Máx 3 Imagens, 3MB cada)</label>
                        <div class="file-upload-box">
                            <div class="file-item"><b>Print 1</b><br><input type="file" class="bug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                            <div class="file-item"><b>Print 2</b><br><input type="file" class="bug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                            <div class="file-item"><b>Print 3</b><br><input type="file" class="bug-file" accept="image/*"><img class="preview-img"><button type="button" class="remove-img">&times;</button></div>
                        </div>
                    </div>
                    <div style="text-align: right;"><button type="submit" id="btn-enviar" class="btn-submit-bug">Enviar Relatório 🚀</button></div>
                </form>
            </div>
            <div class="success-msg" id="success-msg">
                <h3>✅ Bug reportado com sucesso!</h3>
                <p>Obrigado pelo seu relatório! Nossa equipe técnica vai analisar e você será notificado quando houver uma atualização.</p>
                <br>
                <button class="btn-submit-bug" onclick="location.reload()">Reportar outro bug</button>
            </div>
        </div>
    </div>
</div>
<div id="app-footer"></div>
<script src="/js/toast.js"></script>
<script src="/js/layout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadLayout({ activePage: 'reportar_bug' });

    // Preview de imagens
    document.querySelectorAll('.bug-file').forEach(input => {
        const item = input.closest('.file-item');
        const preview = item.querySelector('.preview-img');
        const removeBtn = item.querySelector('.remove-img');

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) {
                if (file.size > 3 * 1024 * 1024) {
                    showToast('Imagem muito grande! Máximo 3MB.', 'error');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    removeBtn.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', () => {
            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            removeBtn.style.display = 'none';
        });
    });

    // Envio do formulário
    document.getElementById('bug-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const titulo = document.getElementById('bug-titulo').value.trim();
        const descricao = document.getElementById('bug-descricao').value.trim();
        if (!titulo || !descricao) return showToast('Preencha todos os campos obrigatórios.', 'error');

        const btn = document.getElementById('btn-enviar');
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        try {
            const imagens = [];
            const fileInputs = document.querySelectorAll('.bug-file');
            for (const input of fileInputs) {
                if (input.files[0]) {
                    const base64 = await fileToBase64(input.files[0]);
                    imagens.push(base64);
                }
            }

            const resp = await fetch('/api/bug', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titulo, descricao, imagens })
            });
            const data = await resp.json();
            if (data.success) {
                document.getElementById('form-container').style.display = 'none';
                document.getElementById('success-msg').style.display = 'block';
                showToast('Bug reportado com sucesso!', 'success');
            } else {
                showToast(data.message || 'Erro ao reportar bug.', 'error');
                btn.disabled = false;
                btn.textContent = 'Enviar Relatório 🚀';
            }
        } catch(err) {
            console.error(err);
            showToast('Erro de conexão. Tente novamente.', 'error');
            btn.disabled = false;
            btn.textContent = 'Enviar Relatório 🚀';
        }
    });
});

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>
