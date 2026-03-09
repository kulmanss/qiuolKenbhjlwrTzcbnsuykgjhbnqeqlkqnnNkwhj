// ===== server.js - Backend Yorkut Clone =====
require('dotenv').config();
process.env.TZ = 'America/Sao_Paulo';
const express = require('express');
const session = require('express-session');
const createDatabase = require('./sqlite-compat');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const path = require('path');
const fs = require('fs');
const SORTE_FRASES = require('./sorte');
const nodemailer = require('nodemailer');
const rateLimit = require('express-rate-limit');
const helmet = require('helmet');
const compression = require('compression');
const FileStore = require('session-file-store')(session);

// ===== Helpers de sanitização =====
function stripHtml(str) {
    if (!str) return '';
    return String(str).replace(/<[^>]*>/g, '').trim();
}
function sanitizeText(str, maxLen = 500) {
    if (!str) return '';
    return stripHtml(str).substring(0, maxLen);
}
function escapeHtmlServer(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ===== Configuração SMTP (via .env) =====
const SMTP_CONFIG = {
    host: process.env.SMTP_HOST || 'localhost',
    port: parseInt(process.env.SMTP_PORT) || 465,
    secure: process.env.SMTP_SECURE !== 'false',
    auth: {
        user: process.env.SMTP_USER || '',
        pass: process.env.SMTP_PASS || ''
    },
    tls: { rejectUnauthorized: process.env.NODE_ENV === 'production' }
};
const SMTP_FROM = process.env.SMTP_FROM || '"Yorkut" <noreply@localhost>';

const app = express();
const PORT = parseInt(process.env.PORT) || 3000;

// Trust proxy (Nginx, Cloudflare, cPanel) para cookies secure funcionarem
if (process.env.NODE_ENV === 'production') {
    app.set('trust proxy', 1);
}

// ===== Banco de Dados SQLite (via sql.js - WASM puro, sem compilação nativa) =====
(async () => {

try {

const db = await createDatabase(path.join(__dirname, 'yorkut.db'));
console.log('[startup] Banco de dados carregado com sucesso.');
db.pragma('foreign_keys = ON');

// ===== Tabela de configuração de timezone =====
db.exec(`
    CREATE TABLE IF NOT EXISTS configuracao (
        chave TEXT PRIMARY KEY,
        valor TEXT NOT NULL
    );
`);
// Inserir timezone padrão (Brasília, UTC-3) se não existir
const tzExiste = db.prepare("SELECT valor FROM configuracao WHERE chave = 'timezone_offset'").get();
if (!tzExiste) {
    db.prepare("INSERT INTO configuracao (chave, valor) VALUES ('timezone_offset', '-3 hours')").run();
}

// Função helper: retorna a hora atual na timezone configurada (SQL)
let _cachedTzOffset = null;
function getTzOffset() {
    if (_cachedTzOffset === null) {
        const row = db.prepare("SELECT valor FROM configuracao WHERE chave = 'timezone_offset'").get();
        _cachedTzOffset = row ? row.valor : '-3 hours';
    }
    return _cachedTzOffset;
}
function invalidateTzCache() { _cachedTzOffset = null; }

// Helper JS: retorna datetime string ajustada para usar em queries
function agora() {
    return `datetime('now','${getTzOffset()}')`;
}

// Helper: extrair @menções do texto e criar notificações
function processarMencoes(mensagem, remetenteId, tipo, link) {
    // Regex captura @[Nome do Usuário](uid)
    const regex = /@\[([^\]]+)\]\(([^)]+)\)/g;
    let match;
    const mencionados = new Set();
    while ((match = regex.exec(mensagem)) !== null) {
        const uid = match[2];
        if (uid !== remetenteId && !mencionados.has(uid)) {
            mencionados.add(uid);
            const user = db.prepare('SELECT id, nome FROM usuarios WHERE id = ?').get(uid);
            if (user) {
                const remetente = db.prepare('SELECT nome FROM usuarios WHERE id = ?').get(remetenteId);
                const nomeRemetente = remetente ? remetente.nome : 'Alguém';
                const titulo = tipo === 'mencao_recado'
                    ? `${nomeRemetente} mencionou você em um recado`
                    : `${nomeRemetente} mencionou você em um depoimento`;
                const preview = mensagem.replace(/@\[([^\]]+)\]\([^)]+\)/g, '@$1').substring(0, 80);
                db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id) VALUES (?, ?, ?, ?, ?, ?)`)
                    .run(uid, tipo, titulo, preview, link, remetenteId);
            }
        }
    }
}

// Helper: retorna avatar padrão baseado no sexo
function defaultAvatar(sexo) {
    return sexo === 'F' ? '/img/default-avatar-female.png' : '/img/default-avatar.png';
}

// Helper: gera ID baseado em timestamp (18 dígitos: YYYYMMDDHHmmss + 4 random)
function generateTimestampId() {
    const now = new Date();
    const pad = (n, len = 2) => String(n).padStart(len, '0');
    const ts = '' + now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate())
              + pad(now.getHours()) + pad(now.getMinutes()) + pad(now.getSeconds());
    const rand = Math.floor(1000 + Math.random() * 9000); // 1000-9999
    return ts + rand;
}

// Criar tabelas
db.exec(`
    CREATE TABLE IF NOT EXISTS usuarios (
        id TEXT PRIMARY KEY,
        nome TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        senha TEXT NOT NULL,
        nascimento TEXT,
        sexo TEXT,
        ddi TEXT DEFAULT '+55',
        whatsapp TEXT,
        foto_perfil TEXT DEFAULT '/img/default-avatar.png',
        tema_id INTEGER DEFAULT NULL,
        sem_tema INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        ultimo_acesso DATETIME DEFAULT (datetime('now','-3 hours')),
        recados_vistos_em DATETIME DEFAULT NULL
    );

    CREATE TABLE IF NOT EXISTS convites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE NOT NULL,
        criado_por TEXT,
        usado_por TEXT,
        usado INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        usado_em DATETIME,
        FOREIGN KEY (criado_por) REFERENCES usuarios(id),
        FOREIGN KEY (usado_por) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS sessoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        session_token TEXT UNIQUE NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (user_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS recados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        destinatario_id TEXT NOT NULL,
        remetente_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        resposta TEXT DEFAULT NULL,
        resposta_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (destinatario_id) REFERENCES usuarios(id),
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS mensagens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        remetente_id TEXT NOT NULL,
        destinatario_id TEXT NOT NULL,
        assunto TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        lida INTEGER DEFAULT 0,
        excluida_remetente INTEGER DEFAULT 0,
        excluida_destinatario INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
        FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS depoimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        destinatario_id TEXT NOT NULL,
        remetente_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        aprovado INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (destinatario_id) REFERENCES usuarios(id),
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS fotos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        arquivo TEXT NOT NULL,
        descricao TEXT DEFAULT '',
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS fotos_curtidas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        foto_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (foto_id) REFERENCES fotos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        UNIQUE(foto_id, usuario_id)
    );

    CREATE TABLE IF NOT EXISTS fotos_comentarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        foto_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (foto_id) REFERENCES fotos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS videos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        youtube_id TEXT NOT NULL,
        descricao TEXT DEFAULT '',
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS videos_curtidas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        video_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        UNIQUE(video_id, usuario_id)
    );

    CREATE TABLE IF NOT EXISTS videos_comentarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        video_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS visitas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        visitado_id TEXT NOT NULL,
        visitante_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (visitado_id) REFERENCES usuarios(id),
        FOREIGN KEY (visitante_id) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS amizades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        remetente_id TEXT NOT NULL,
        destinatario_id TEXT NOT NULL,
        status TEXT DEFAULT 'pendente',
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        aceito_em DATETIME DEFAULT NULL,
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
        FOREIGN KEY (destinatario_id) REFERENCES usuarios(id),
        UNIQUE(remetente_id, destinatario_id)
    );

    CREATE TABLE IF NOT EXISTS avaliacoes_amigos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        avaliador_id TEXT NOT NULL,
        avaliado_id TEXT NOT NULL,
        estrelas INTEGER NOT NULL DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (avaliador_id) REFERENCES usuarios(id),
        FOREIGN KEY (avaliado_id) REFERENCES usuarios(id),
        UNIQUE(avaliador_id, avaliado_id)
    );

    CREATE TABLE IF NOT EXISTS denuncias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        denunciante_id TEXT NOT NULL,
        denunciado_id TEXT NOT NULL,
        motivo TEXT NOT NULL,
        status TEXT DEFAULT 'pendente',
        resposta_admin TEXT DEFAULT NULL,
        resolvido_por TEXT DEFAULT NULL,
        resolvido_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (denunciante_id) REFERENCES usuarios(id),
        FOREIGN KEY (denunciado_id) REFERENCES usuarios(id),
        FOREIGN KEY (resolvido_por) REFERENCES usuarios(id)
    );

    CREATE TABLE IF NOT EXISTS denuncia_mensagens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        denuncia_id INTEGER NOT NULL,
        remetente_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        lida INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE,
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
    );
`);

// ===== Tabelas de Denúncias de Comunidades =====
db.exec(`
    CREATE TABLE IF NOT EXISTS denuncias_comunidades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        denunciante_id TEXT NOT NULL,
        comunidade_id INTEGER NOT NULL,
        motivo TEXT NOT NULL,
        status TEXT DEFAULT 'pendente',
        resposta_admin TEXT DEFAULT NULL,
        resolvido_por TEXT DEFAULT NULL,
        resolvido_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (denunciante_id) REFERENCES usuarios(id),
        FOREIGN KEY (comunidade_id) REFERENCES comunidades(id),
        FOREIGN KEY (resolvido_por) REFERENCES usuarios(id)
    );
`);
db.exec(`
    CREATE TABLE IF NOT EXISTS denuncia_comunidade_mensagens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        denuncia_id INTEGER NOT NULL,
        remetente_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        lida INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (denuncia_id) REFERENCES denuncias_comunidades(id) ON DELETE CASCADE,
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
    );
`);

// Adicionar colunas tema_id e sem_tema se não existem (migração)
try { db.exec('ALTER TABLE usuarios ADD COLUMN tema_id INTEGER DEFAULT NULL'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN sem_tema INTEGER DEFAULT 0'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN recados_vistos_em DATETIME DEFAULT NULL'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN depoimentos_vistos_em DATETIME DEFAULT NULL'); } catch(e) {}

// Migração: colunas de privacidade/configurações
const privacidadeColunas = [
    'visitas_rastro TEXT DEFAULT "sim"',
    'escrever_recado TEXT DEFAULT "todos"',
    'escrever_depoimento TEXT DEFAULT "todos"',
    'enviar_mensagem TEXT DEFAULT "amigos"',
    'mencionar TEXT DEFAULT "amigos"',
    'ver_recado TEXT DEFAULT "todos"',
    'ver_foto TEXT DEFAULT "todos"',
    'ver_video TEXT DEFAULT "todos"',
    'ver_depoimento TEXT DEFAULT "todos"',
    'ver_amigos TEXT DEFAULT "todos"',
    'ver_comunidades TEXT DEFAULT "todos"',
    'ver_social TEXT DEFAULT "todos"',
    'ver_pessoal TEXT DEFAULT "todos"',
    'ver_profissional TEXT DEFAULT "todos"',
    'ver_online TEXT DEFAULT "todos"',
    'votos TEXT DEFAULT "todos"',
    'ver_comunidades_presenca TEXT DEFAULT "todos"',
    'aparecer_pesquisa TEXT DEFAULT "sim"',
    'conta_excluir_em DATETIME DEFAULT NULL'
];
privacidadeColunas.forEach(col => {
    try { db.exec('ALTER TABLE usuarios ADD COLUMN ' + col); } catch(e) {}
});

// Migração: colunas do perfil completo
const perfilColunas = [
    'status_texto TEXT DEFAULT ""',
    'quem_sou_eu TEXT DEFAULT ""',
    'estado_civil TEXT DEFAULT ""',
    'interesse_em TEXT DEFAULT ""',
    'interesses TEXT DEFAULT ""',
    'atividades TEXT DEFAULT ""',
    'musica TEXT DEFAULT ""',
    'filmes TEXT DEFAULT ""',
    'tv TEXT DEFAULT ""',
    'livros TEXT DEFAULT ""',
    'esportes TEXT DEFAULT ""',
    'atividades_favoritas TEXT DEFAULT ""',
    'comidas TEXT DEFAULT ""',
    'herois TEXT DEFAULT ""',
    'apelido TEXT DEFAULT ""',
    'hora_nascimento TEXT DEFAULT ""',
    'cidade_natal TEXT DEFAULT ""',
    'cidade TEXT DEFAULT ""',
    'estado TEXT DEFAULT ""',
    'pais TEXT DEFAULT "Brasil"',
    'orientacao_sexual TEXT DEFAULT ""',
    'filhos TEXT DEFAULT ""',
    'altura TEXT DEFAULT ""',
    'tipo_fisico TEXT DEFAULT ""',
    'etnia TEXT DEFAULT ""',
    'religiao TEXT DEFAULT ""',
    'humor TEXT DEFAULT ""',
    'estilo TEXT DEFAULT ""',
    'fumo TEXT DEFAULT ""',
    'bebo TEXT DEFAULT ""',
    'animais_estimacao TEXT DEFAULT ""',
    'mora_com TEXT DEFAULT ""',
    'escolaridade TEXT DEFAULT ""',
    'ensino_medio TEXT DEFAULT ""',
    'universidade TEXT DEFAULT ""',
    'curso TEXT DEFAULT ""',
    'ano_inicio TEXT DEFAULT ""',
    'ano_conclusao_prof TEXT DEFAULT ""',
    'grau TEXT DEFAULT ""',
    'ocupacao TEXT DEFAULT ""',
    'profissao TEXT DEFAULT ""',
    'empresa TEXT DEFAULT ""',
    'cargo TEXT DEFAULT ""',
    'area_atuacao TEXT DEFAULT ""'
];
perfilColunas.forEach(col => {
    try { db.exec('ALTER TABLE usuarios ADD COLUMN ' + col); } catch(e) {}
});

// Migração: corrigir avatar padrão para usuárias femininas
db.prepare(`UPDATE usuarios SET foto_perfil = '/img/default-avatar-female.png' WHERE sexo = 'F' AND foto_perfil = '/img/default-avatar.png'`).run();

// Migração: campo admin
try { db.exec('ALTER TABLE usuarios ADD COLUMN is_admin INTEGER DEFAULT 0'); } catch(e) {}
// Tornar primeiro usuário registrado admin (apenas se NENHUM admin existir)
const existingAdmin = db.prepare('SELECT id FROM usuarios WHERE is_admin = 1 LIMIT 1').get();
if (!existingAdmin) {
    const firstUser = db.prepare('SELECT id FROM usuarios ORDER BY criado_em ASC LIMIT 1').get();
    if (firstUser) {
        db.prepare('UPDATE usuarios SET is_admin = 1 WHERE id = ?').run(firstUser.id);
    }
}

// Migração: campos de banimento
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido INTEGER DEFAULT 0'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido_permanente INTEGER DEFAULT 0'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido_ate DATETIME DEFAULT NULL'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido_motivo TEXT DEFAULT NULL'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido_por TEXT DEFAULT NULL'); } catch(e) {}
try { db.exec('ALTER TABLE usuarios ADD COLUMN banido_em DATETIME DEFAULT NULL'); } catch(e) {}

// Tabela de bloqueios entre usuários
db.exec(`
    CREATE TABLE IF NOT EXISTS bloqueios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bloqueador_id TEXT NOT NULL,
        bloqueado_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (bloqueador_id) REFERENCES usuarios(id),
        FOREIGN KEY (bloqueado_id) REFERENCES usuarios(id),
        UNIQUE(bloqueador_id, bloqueado_id)
    )
`);

// Tabela de notificações
db.exec(`
    CREATE TABLE IF NOT EXISTS notificacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        tipo TEXT NOT NULL DEFAULT 'denuncia_resposta',
        titulo TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        link TEXT DEFAULT NULL,
        remetente_id TEXT DEFAULT NULL,
        lida INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
`);

// Migração: adicionar coluna remetente_id se não existir
try {
    db.prepare("SELECT remetente_id FROM notificacoes LIMIT 1").get();
} catch(e) {
    db.exec('ALTER TABLE notificacoes ADD COLUMN remetente_id TEXT DEFAULT NULL');
    console.log('Migração: coluna remetente_id adicionada a notificacoes');
}

// Tabela de anúncios do admin
db.exec(`
    CREATE TABLE IF NOT EXISTS anuncios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        foto TEXT DEFAULT NULL,
        admin_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (admin_id) REFERENCES usuarios(id)
    )
`);

// Adicionar coluna foto à tabela anuncios (para bancos existentes)
try { db.exec("ALTER TABLE anuncios ADD COLUMN foto TEXT DEFAULT NULL"); } catch(e) {}

// Tabela de sugestões
db.exec(`
    CREATE TABLE IF NOT EXISTS sugestoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        titulo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        imagens TEXT DEFAULT NULL,
        status TEXT DEFAULT 'nova',
        resposta_admin TEXT DEFAULT NULL,
        resolvido_por TEXT DEFAULT NULL,
        resolvido_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
`);

// Tabela de bugs
db.exec(`
    CREATE TABLE IF NOT EXISTS bugs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        titulo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        imagens TEXT DEFAULT NULL,
        status TEXT DEFAULT 'novo',
        resposta_admin TEXT DEFAULT NULL,
        resolvido_por TEXT DEFAULT NULL,
        resolvido_em DATETIME DEFAULT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
`);

// Tabela de fazendas (Colheita Feliz)
db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_farms (
        user_id TEXT PRIMARY KEY REFERENCES usuarios(id),
        farm_data TEXT DEFAULT '{}',
        inventory TEXT DEFAULT '{}',
        level INTEGER DEFAULT 1,
        xp INTEGER DEFAULT 0,
        ouro INTEGER DEFAULT 100,
        grid_size INTEGER DEFAULT 24,
        last_updated INTEGER DEFAULT 0
    )
`);

// Migração: adicionar coluna level se não existir
try { db.exec('ALTER TABLE colheita_farms ADD COLUMN level INTEGER DEFAULT 1'); } catch(e) { /* já existe */ }

// Migração: adicionar coluna unlocked_tiles se não existir
try { db.exec("ALTER TABLE colheita_farms ADD COLUMN unlocked_tiles TEXT DEFAULT '[0]'"); } catch(e) { /* já existe */ }

// Tabela de logs/visitas da fazenda
db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        farm_owner_id TEXT NOT NULL,
        visitor_id TEXT NOT NULL,
        action TEXT NOT NULL,
        details TEXT DEFAULT '',
        created_at DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (farm_owner_id) REFERENCES usuarios(id),
        FOREIGN KEY (visitor_id) REFERENCES usuarios(id)
    )
`);

// Tabela de itens/sementes (Colheita Feliz)
// Migrar tabela antiga se existir com schema antigo
{
    const tableInfo = db.prepare("PRAGMA table_info(colheita_items)").all();
    const colNames = tableInfo.map(c => c.name);
    if (colNames.length > 0 && !colNames.includes('descricao')) {
        // Schema antigo detectado — recriar tabela
        const oldItems = db.prepare('SELECT * FROM colheita_items').all();
        db.exec('DROP TABLE colheita_items');
        console.log('[Colheita] Tabela colheita_items recriada com novo schema.');
    }
    // Adicionar coluna temporadas se não existir
    if (colNames.length > 0 && !colNames.includes('temporadas')) {
        db.exec("ALTER TABLE colheita_items ADD COLUMN temporadas INTEGER DEFAULT 1");
        console.log('[Colheita] Coluna temporadas adicionada à tabela colheita_items.');
    }
}

db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_items (
        id INTEGER PRIMARY KEY,
        nome TEXT NOT NULL,
        descricao TEXT DEFAULT '',
        moeda TEXT DEFAULT 'gold',
        preco_compra INTEGER DEFAULT 0,
        preco_venda INTEGER DEFAULT 0,
        rendimento INTEGER DEFAULT 50,
        frutos_roubo INTEGER DEFAULT 5,
        estrelas_colheita INTEGER DEFAULT 1,
        tempo_fase2 INTEGER DEFAULT 60,
        tempo_fase3 INTEGER DEFAULT 60,
        tempo_fase4 INTEGER DEFAULT 60,
        tempo_fase5 INTEGER DEFAULT 300,
        temporadas INTEGER DEFAULT 1,
        nivel_minimo INTEGER DEFAULT 1,
        ativo INTEGER DEFAULT 1
    )
`);

// Inserir item 1001 (Laranja) se não existir
{
    const existing = db.prepare('SELECT id FROM colheita_items WHERE id = 1001').get();
    if (!existing) {
        db.prepare(`INSERT INTO colheita_items (id, nome, descricao, moeda, preco_compra, preco_venda, rendimento, frutos_roubo, estrelas_colheita, tempo_fase2, tempo_fase3, tempo_fase4, tempo_fase5, nivel_minimo)
            VALUES (1001, 'Laranja', 'Uma deliciosa laranja suculenta.', 'gold', 100, 15, 50, 5, 1, 60, 60, 60, 300, 1)`).run();
    }
}

// Tabela de fertilizantes (Colheita Feliz)
db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_fertilizantes (
        id INTEGER PRIMARY KEY,
        nome TEXT NOT NULL,
        descricao TEXT DEFAULT '',
        icone TEXT DEFAULT '',
        preco_gold INTEGER DEFAULT 0,
        preco_kutcoin INTEGER DEFAULT 0,
        tempo_reducao INTEGER DEFAULT 60,
        nivel_minimo INTEGER DEFAULT 1,
        ativo INTEGER DEFAULT 1
    )
`);

// Migração: adicionar colunas preco_gold/preco_kutcoin se não existirem (migração de moneytype/preco)
{
    const cols = db.prepare("PRAGMA table_info(colheita_fertilizantes)").all().map(c => c.name);
    if (!cols.includes('preco_gold')) {
        db.exec('ALTER TABLE colheita_fertilizantes ADD COLUMN preco_gold INTEGER DEFAULT 0');
        db.exec('ALTER TABLE colheita_fertilizantes ADD COLUMN preco_kutcoin INTEGER DEFAULT 0');
        // Migrar dados antigos de moneytype/preco
        if (cols.includes('moneytype') && cols.includes('preco')) {
            db.exec('UPDATE colheita_fertilizantes SET preco_gold = preco WHERE moneytype = 1');
            db.exec('UPDATE colheita_fertilizantes SET preco_kutcoin = preco WHERE moneytype = 2');
        }
        console.log('[Colheita] Migração: preco_gold/preco_kutcoin adicionados.');
    }
}

// Inserir fertilizantes padrão se tabela estiver vazia
{
    const count = db.prepare('SELECT COUNT(*) as cnt FROM colheita_fertilizantes').get().cnt;
    if (count === 0) {
        const insertFert = db.prepare('INSERT INTO colheita_fertilizantes (id, nome, descricao, icone, preco_gold, preco_kutcoin, tempo_reducao, nivel_minimo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        insertFert.run(1, 'Fertilizante Básico', 'Reduz 1 minuto do tempo de crescimento.', 'fertilizante1.png', 50, 0, 60, 1);
        insertFert.run(2, 'Fertilizante Médio', 'Reduz 5 minutos do tempo de crescimento.', 'fertilizante2.png', 200, 2, 300, 5);
        insertFert.run(3, 'Fertilizante Forte', 'Reduz 15 minutos do tempo de crescimento.', 'fertilizante3.png', 500, 5, 900, 10);
        console.log('[Colheita] Fertilizantes padrão inseridos (3 itens).');
    }
}

// Tabela de requisitos de tiles (Colheita Feliz)
db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_tile_reqs (
        tile_id INTEGER PRIMARY KEY,
        nivel_minimo INTEGER DEFAULT 1,
        preco INTEGER DEFAULT 0
    )
`);

// Inserir requisitos dos tiles se tabela estiver vazia
{
    const count = db.prepare('SELECT COUNT(*) as cnt FROM colheita_tile_reqs').get().cnt;
    if (count === 0) {
        const tileConfigs = [
            [0, 1, 0], [1, 3, 1000], [2, 5, 10000], [3, 10, 50000],
            [4, 12, 100000], [5, 15, 200000], [6, 18, 350000], [7, 20, 500000],
            [8, 22, 750000], [9, 25, 1000000], [10, 28, 1500000], [11, 30, 2000000],
            [12, 32, 2500000], [13, 35, 3000000], [14, 37, 4000000], [15, 40, 5000000],
            [16, 42, 6000000], [17, 45, 7500000], [18, 48, 9000000], [19, 50, 11000000],
            [20, 53, 13000000], [21, 56, 16000000], [22, 60, 20000000], [23, 65, 25000000]
        ];
        const insertTile = db.prepare('INSERT INTO colheita_tile_reqs (tile_id, nivel_minimo, preco) VALUES (?, ?, ?)');
        for (const [tid, lvl, price] of tileConfigs) {
            insertTile.run(tid, lvl, price);
        }
        console.log('[Colheita] Requisitos de tiles inseridos (24 tiles).');
    }
}

// ===== COMUNIDADES =====
// dono_id e usuario_id são TEXT para suportar IDs longos (18+ dígitos)
db.exec(`
    CREATE TABLE IF NOT EXISTS comunidades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        descricao TEXT DEFAULT '',
        categoria TEXT DEFAULT 'Geral',
        tipo TEXT DEFAULT 'publica',
        foto TEXT DEFAULT NULL,
        idioma TEXT DEFAULT 'Português',
        local_text TEXT DEFAULT 'Brasil',
        dono_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS comunidade_membros (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        cargo TEXT DEFAULT 'membro',
        entrou_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(comunidade_id, usuario_id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS comunidade_bans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        banido_por TEXT NOT NULL,
        motivo TEXT DEFAULT '',
        banido_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(comunidade_id, usuario_id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS comunidade_pendentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        mensagem TEXT DEFAULT '',
        solicitado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(comunidade_id, usuario_id)
    )
`);
try { db.exec('ALTER TABLE comunidade_pendentes ADD COLUMN mensagem TEXT DEFAULT ""'); } catch(e) {}

// Tabelas do Fórum
db.exec(`
    CREATE TABLE IF NOT EXISTS forum_topicos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        autor_id TEXT NOT NULL,
        titulo TEXT NOT NULL,
        fixado INTEGER DEFAULT 0,
        trancado INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_resposta_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS forum_respostas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        topico_id INTEGER NOT NULL,
        autor_id TEXT NOT NULL,
        mensagem TEXT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

// Tabelas de Enquetes
db.exec(`
    CREATE TABLE IF NOT EXISTS enquetes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        criador_id TEXT NOT NULL,
        titulo TEXT NOT NULL,
        data_inicio DATETIME,
        data_fim DATETIME,
        mostrar_votantes INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours'))
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS enquete_opcoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        enquete_id INTEGER NOT NULL,
        texto TEXT NOT NULL,
        foto TEXT DEFAULT NULL,
        FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS enquete_votos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        enquete_id INTEGER NOT NULL,
        opcao_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        votado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
        FOREIGN KEY (opcao_id) REFERENCES enquete_opcoes(id) ON DELETE CASCADE,
        UNIQUE(enquete_id, usuario_id)
    )
`);

// Sorteios
db.exec(`
    CREATE TABLE IF NOT EXISTS sorteios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        comunidade_id INTEGER NOT NULL,
        criador_id TEXT NOT NULL,
        premio TEXT NOT NULL,
        regras TEXT DEFAULT '',
        data_fim DATETIME NOT NULL,
        qtd_vencedores INTEGER DEFAULT 1,
        mod_participa INTEGER DEFAULT 1,
        sorteado INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (comunidade_id) REFERENCES comunidades(id) ON DELETE CASCADE
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS sorteio_participantes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sorteio_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        participou_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (sorteio_id) REFERENCES sorteios(id) ON DELETE CASCADE,
        UNIQUE(sorteio_id, usuario_id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS sorteio_vencedores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sorteio_id INTEGER NOT NULL,
        usuario_id TEXT NOT NULL,
        FOREIGN KEY (sorteio_id) REFERENCES sorteios(id) ON DELETE CASCADE
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS fas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fa_id TEXT NOT NULL,
        usuario_id TEXT NOT NULL,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (fa_id) REFERENCES usuarios(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        UNIQUE(fa_id, usuario_id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS avaliacoes_perfil (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        avaliador_id TEXT NOT NULL,
        avaliado_id TEXT NOT NULL,
        categoria TEXT NOT NULL,
        nota INTEGER NOT NULL DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (avaliador_id) REFERENCES usuarios(id),
        FOREIGN KEY (avaliado_id) REFERENCES usuarios(id),
        UNIQUE(avaliador_id, avaliado_id, categoria)
    )
`);

// Migrate: add idioma and local_text columns if missing
try { db.exec("ALTER TABLE comunidades ADD COLUMN idioma TEXT DEFAULT 'Português'"); } catch(e) {}
try { db.exec("ALTER TABLE comunidades ADD COLUMN local_text TEXT DEFAULT 'Brasil'"); } catch(e) {}
try { db.exec("ALTER TABLE comunidades ADD COLUMN excluir_em DATETIME DEFAULT NULL"); } catch(e) {}

// ===== Tabela de tokens de recuperação de senha =====
db.exec(`
    CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id TEXT NOT NULL,
        token TEXT NOT NULL UNIQUE,
        expira_em DATETIME NOT NULL,
        usado INTEGER DEFAULT 0,
        criado_em DATETIME DEFAULT (datetime('now','-3 hours')),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
`);

// Migrate: rebuild tables if dono_id/usuario_id are INTEGER (BigInt precision fix)
(function migrateCommunityTables() {
    const colInfo = db.prepare("PRAGMA table_info(comunidades)").all();
    const donoCol = colInfo.find(c => c.name === 'dono_id');
    if (donoCol && donoCol.type === 'INTEGER') {
        console.log('[MIGRATE] Rebuilding comunidades/comunidade_membros tables (INTEGER→TEXT fix)...');
        db.exec('DROP TABLE IF EXISTS comunidade_membros');
        db.exec('DROP TABLE IF EXISTS comunidades');
        db.exec(`
            CREATE TABLE comunidades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                descricao TEXT DEFAULT '',
                categoria TEXT DEFAULT 'Geral',
                tipo TEXT DEFAULT 'publica',
                foto TEXT DEFAULT NULL,
                idioma TEXT DEFAULT 'Português',
                local_text TEXT DEFAULT 'Brasil',
                dono_id TEXT NOT NULL,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        `);
        db.exec(`
            CREATE TABLE comunidade_membros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comunidade_id INTEGER NOT NULL,
                usuario_id TEXT NOT NULL,
                cargo TEXT DEFAULT 'membro',
                entrou_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(comunidade_id, usuario_id)
            )
        `);
        console.log('[MIGRATE] Done. Old corrupted data removed — communities can be recreated.');
    }
})();

// Tabela de níveis (Colheita Feliz) — armazenada no banco de dados
db.exec(`
    CREATE TABLE IF NOT EXISTS colheita_levels (
        level INTEGER PRIMARY KEY,
        exp_needed INTEGER NOT NULL
    );
`);

// ===== INDEXES para performance =====
const indexes = [
    'CREATE INDEX IF NOT EXISTS idx_recados_dest ON recados(destinatario_id, criado_em)',
    'CREATE INDEX IF NOT EXISTS idx_recados_rem ON recados(remetente_id)',
    'CREATE INDEX IF NOT EXISTS idx_mensagens_dest ON mensagens(destinatario_id, lida, excluida_destinatario)',
    'CREATE INDEX IF NOT EXISTS idx_mensagens_rem ON mensagens(remetente_id)',
    'CREATE INDEX IF NOT EXISTS idx_amizades_dest ON amizades(destinatario_id, status)',
    'CREATE INDEX IF NOT EXISTS idx_amizades_rem ON amizades(remetente_id, status)',
    'CREATE INDEX IF NOT EXISTS idx_depoimentos_dest ON depoimentos(destinatario_id, aprovado)',
    'CREATE INDEX IF NOT EXISTS idx_notifs_user ON notificacoes(usuario_id, lida)',
    'CREATE INDEX IF NOT EXISTS idx_visitas_visitado ON visitas(visitado_id, criado_em)',
    'CREATE INDEX IF NOT EXISTS idx_visitas_visitante ON visitas(visitante_id)',
    'CREATE INDEX IF NOT EXISTS idx_fotos_user ON fotos(usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_videos_user ON videos(usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_com_membros_com ON comunidade_membros(comunidade_id)',
    'CREATE INDEX IF NOT EXISTS idx_com_membros_user ON comunidade_membros(usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_com_pendentes_com ON comunidade_pendentes(comunidade_id)',
    'CREATE INDEX IF NOT EXISTS idx_forum_topicos_com ON forum_topicos(comunidade_id)',
    'CREATE INDEX IF NOT EXISTS idx_forum_respostas_top ON forum_respostas(topico_id)',
    'CREATE INDEX IF NOT EXISTS idx_bloqueios_bloqueador ON bloqueios(bloqueador_id)',
    'CREATE INDEX IF NOT EXISTS idx_bloqueios_bloqueado ON bloqueios(bloqueado_id)',
    'CREATE INDEX IF NOT EXISTS idx_denuncias_status ON denuncias(status)',
    'CREATE INDEX IF NOT EXISTS idx_denuncias_com_status ON denuncias_comunidades(status)',
    'CREATE INDEX IF NOT EXISTS idx_colheita_logs_visitor ON colheita_logs(visitor_id, farm_owner_id, action)',
    'CREATE INDEX IF NOT EXISTS idx_fas_usuario ON fas(usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_fas_fa ON fas(fa_id)',
    'CREATE INDEX IF NOT EXISTS idx_enquete_votos_enquete ON enquete_votos(enquete_id, usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_fotos_curtidas ON fotos_curtidas(foto_id, usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_videos_curtidas ON videos_curtidas(video_id, usuario_id)',
    'CREATE INDEX IF NOT EXISTS idx_sessoes_user ON sessoes(user_id)',
    'CREATE INDEX IF NOT EXISTS idx_convites_criado ON convites(criado_por)',
];
for (const idx of indexes) {
    try { db.exec(idx); } catch(e) { /* tabela pode não existir */ }
}

// Popular tabela de níveis se estiver vazia
(function seedLevelsTable() {
    const count = db.prepare('SELECT COUNT(*) as c FROM colheita_levels').get().c;
    if (count === 0) {
        const defaultLevels = {
            2:422, 3:863, 4:1314, 5:1995, 6:2625, 7:3424, 8:4202, 9:5156, 10:6090,
            11:7930, 12:9491, 13:11824, 14:14630, 15:16487, 16:19613, 17:21740, 18:24444, 19:28655, 20:33733,
            21:38598, 22:46099, 23:53437, 24:60997, 25:69229, 26:78301, 27:88297, 28:99301, 29:112405, 30:127987,
            31:146257, 32:167761, 33:192870, 34:222102, 35:254232, 36:301440, 37:353352, 38:410472, 39:473304, 40:542352,
            41:618456, 42:702120, 43:794184, 44:895320, 45:1006704, 46:1159794, 47:1328214, 48:1513434, 49:1717134, 50:1941204,
            51:2187744, 52:2458854, 53:2757054, 54:3150678, 55:3583866, 56:4060398, 57:4584558, 58:5161134, 59:5795166, 60:6608958,
            61:7504188, 62:8488794, 63:9571890, 64:10933698, 65:12431586, 66:14285298, 67:16324230, 68:18816090, 69:21557430, 70:24874590,
            71:28523004, 72:32901252, 73:37717476, 74:43457028, 75:49770426, 76:57249198, 77:65475906, 78:75171606, 79:89391966, 80:108802350,
            81:129137038, 82:150396030, 83:174427934, 84:203081358
        };
        const insert = db.prepare('INSERT INTO colheita_levels (level, exp_needed) VALUES (?, ?)');
        const tx = db.transaction(() => {
            for (const [lvl, xp] of Object.entries(defaultLevels)) {
                insert.run(Number(lvl), xp);
            }
        });
        tx();
        console.log('[Colheita] Tabela colheita_levels populada com dados padrão.');
    }
})();

// Construir EXP_TABLE a partir do banco
function buildExpTable() {
    const rows = db.prepare('SELECT level, exp_needed FROM colheita_levels ORDER BY level').all();
    const table = {};
    for (const row of rows) {
        table[row.level] = row.exp_needed;
    }
    return table;
}
let EXP_TABLE = buildExpTable();
let MAX_LEVEL = Math.max(...Object.keys(EXP_TABLE).map(Number));

function getXPNeeded(level) {
    if (level >= MAX_LEVEL) return 999999999;
    return EXP_TABLE[level + 1] || 999999999;
}

// Adiciona XP a um jogador no servidor e processa level-ups
function addXPToFarm(userId, amount) {
    const farm = ensureColheitaFarm(userId);
    let level = farm.level || 1;
    let xp = (farm.xp || 0) + amount;
    while (level < MAX_LEVEL) {
        const needed = getXPNeeded(level);
        if (xp >= needed) { xp -= needed; level++; } else break;
    }
    db.prepare('UPDATE colheita_farms SET level = ?, xp = ? WHERE user_id = ?').run(level, xp, userId);
    return { level, xp };
}

// Configuração de sementes (Colheita Feliz) — gerado a partir da tabela colheita_items
function buildSeedsConfig() {
    const items = db.prepare('SELECT * FROM colheita_items WHERE ativo = 1').all();
    const config = {};
    for (const item of items) {
        config[String(item.id)] = {
            nome: item.nome,
            descricao: item.descricao || '',
            moeda: item.moeda || 'gold',
            preco_compra: item.preco_compra,
            preco_venda: item.preco_venda,
            rendimento: item.rendimento,
            frutos_roubo: item.frutos_roubo || 5,
            estrelas_colheita: item.estrelas_colheita || 1,
            tempo_fase2: item.tempo_fase2,
            tempo_fase3: item.tempo_fase3,
            tempo_fase4: item.tempo_fase4,
            tempo_fase5: item.tempo_fase5,
            temporadas: item.temporadas || 1,
            img: `imagens_colheita/itens/${item.id}.png`,
            f1_img: `imagens_colheita/itens/fase1_${item.id}.png`,
            f2_img: `imagens_colheita/itens/fase2_${item.id}.png`,
            f3_img: `imagens_colheita/itens/fase3_${item.id}.png`,
            f4_img: `imagens_colheita/itens/fase4_${item.id}.png`,
            f5_img: `imagens_colheita/itens/fase5_${item.id}.png`,
            nivel_minimo: item.nivel_minimo || 1
        };
    }
    return config;
}
let SEEDS_CONFIG = buildSeedsConfig();

// Configuração de fertilizantes (Colheita Feliz) — gerado a partir da tabela colheita_fertilizantes
function buildFertilizersConfig() {
    const items = db.prepare('SELECT * FROM colheita_fertilizantes WHERE ativo = 1').all();
    const config = {};
    for (const item of items) {
        config[String(item.id)] = {
            nome: item.nome,
            descricao: item.descricao || '',
            icone: item.icone || 'fertilizante1.png',
            preco_gold: item.preco_gold || 0,
            preco_kutcoin: item.preco_kutcoin || 0,
            tempo_reducao: item.tempo_reducao,
            nivel_minimo: item.nivel_minimo || 1
        };
    }
    return config;
}
let FERTILIZERS_CONFIG = buildFertilizersConfig();

// Migração: converter chaves antigas (e.g. "laranja", "eijo") para IDs numéricos
{
    const OLD_TO_NEW = {
        'laranja': '1001',
        'eijo': '1001'
    };
    const farms = db.prepare('SELECT user_id, farm_data, inventory FROM colheita_farms').all();
    let migrated = 0;
    for (const farm of farms) {
        let changed = false;
        // Migrar farm_data (planted keys)
        const fd = JSON.parse(farm.farm_data || '{}');
        for (const tileId in fd) {
            if (fd[tileId].planted && OLD_TO_NEW[fd[tileId].planted]) {
                fd[tileId].planted = OLD_TO_NEW[fd[tileId].planted];
                changed = true;
            }
        }
        // Migrar inventory keys
        const inv = JSON.parse(farm.inventory || '{}');
        for (const oldKey in OLD_TO_NEW) {
            const newKey = OLD_TO_NEW[oldKey];
            // Migrar seed_oldKey → seed_newKey
            if (inv['seed_' + oldKey]) {
                inv['seed_' + newKey] = (inv['seed_' + newKey] || 0) + inv['seed_' + oldKey];
                delete inv['seed_' + oldKey];
                changed = true;
            }
            // Migrar harvested oldKey → newKey
            if (inv[oldKey]) {
                inv[newKey] = (inv[newKey] || 0) + inv[oldKey];
                delete inv[oldKey];
                changed = true;
            }
        }
        if (changed) {
            db.prepare('UPDATE colheita_farms SET farm_data = ?, inventory = ? WHERE user_id = ?').run(
                JSON.stringify(fd), JSON.stringify(inv), farm.user_id);
            migrated++;
        }
    }
    if (migrated > 0) console.log(`[Colheita] Migrados ${migrated} farms (chaves antigas → IDs).`);
}

// ===== Dados dos temas =====
const TEMAS = {
    11: { nome: 'Caveira Lacinho', slug: 'caveira-lacinho', categoria: 'Caveiras', imagem: 'themes/theme_699c98fa413f5.png', cor1: '#000000', cor2: '#e6399b' },
    12: { nome: 'Ossos', slug: 'ossos', categoria: 'Caveiras', imagem: 'themes/theme_699c992d4427e.jpg', cor1: '#bcafd7', cor2: '#000000' },
    5:  { nome: 'Aquafans', slug: 'aquafans', categoria: 'Desenhos abstratos', imagem: 'themes/aquafans.png', cor1: '#0077b3', cor2: '#f4f8fb' },
    1:  { nome: 'Bambu', slug: 'bambu', categoria: 'Desenhos abstratos', imagem: 'themes/bambu.png', cor1: '#7ba543', cor2: '#f4e3b2' },
    3:  { nome: 'Explosão solar', slug: 'explosao-solar', categoria: 'Desenhos abstratos', imagem: 'themes/explosao_solar.png', cor1: '#ff9900', cor2: '#f4e3b2' },
    4:  { nome: 'Floral', slug: 'floral', categoria: 'Desenhos abstratos', imagem: 'themes/floral.png', cor1: '#b34799', cor2: '#f4e3b2' },
    2:  { nome: 'Trama', slug: 'trama', categoria: 'Desenhos abstratos', imagem: 'themes/trama.png', cor1: '#6d84b4', cor2: '#f4e3b2' },
    14: { nome: 'Tubos Animado', slug: 'tubos-animado', categoria: 'Desenhos abstratos', imagem: 'themes/theme_699d13bb5d8ce.gif', cor1: '#fe6363', cor2: '#fefee5' },
    13: { nome: 'Fibra de Carbono', slug: 'fibra-de-carbono', categoria: 'Materiais', imagem: 'themes/theme_699d140dd4efa.jpg', cor1: '#000000', cor2: '#df1616' },
    17: { nome: 'Flamengo', slug: 'flamengo', categoria: 'Times', imagem: 'themes/theme_699d34f4242f9.png', cor1: '#c2281e', cor2: '#000000' },
    16: { nome: 'Palmeiras', slug: 'palmeiras', categoria: 'Times', imagem: 'themes/theme_699d33bc96af6.png', cor1: '#026330', cor2: '#ffffff' }
};

// ===== Gerar token de teste se não existir nenhum =====
const tokenCount = db.prepare('SELECT COUNT(*) as total FROM convites WHERE usado = 0').get();
if (tokenCount.total === 0) {
    const testToken = crypto.randomBytes(8).toString('hex').toUpperCase();
    db.prepare(`INSERT INTO convites (token, criado_por, criado_em) VALUES (?, NULL, ${agora()})`).run(testToken);
    console.log('Novo token de convite gerado (consulte via painel admin).');
}

// Contagem de tokens disponíveis (sem expor valores)
const tokensDisponiveis = db.prepare('SELECT COUNT(*) as total FROM convites WHERE usado = 0').get();
if (tokensDisponiveis.total > 0) {
    console.log(`Tokens de convite disponíveis: ${tokensDisponiveis.total}`);
}

// ===== Middleware =====

// Compressão gzip/deflate — reduz tamanho de HTML, CSS, JS, JSON em ~70%
app.use(compression({
    level: 6,         // nível de compressão (1-9, 6 é bom equilíbrio velocidade/tamanho)
    threshold: 1024,  // só comprimir respostas > 1KB
    filter: (req, res) => {
        // Não comprimir imagens (já são comprimidas)
        if (req.path.match(/\.(jpg|jpeg|png|gif|webp|ico|woff2?)$/i)) return false;
        return compression.filter(req, res);
    }
}));

app.use(helmet({
    contentSecurityPolicy: false, // CSP desabilitado (inline scripts nas páginas PHP)
    crossOriginEmbedderPolicy: false
}));

// Remover header que revela tecnologia usada
app.disable('x-powered-by');

// Impedir que bots/crawlers indexem páginas privadas
app.use((req, res, next) => {
    if (req.path !== '/' && req.path !== '/index.php' && req.path !== '/registro.php') {
        res.set('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
    next();
});

// Bloquear user-agents de bots/scrapers conhecidos
const blockedAgents = /curl|wget|python-requests|scrapy|httpclient|java\/|libwww|bot(?!.*google)|spider|crawl|phantom|headless|selenium|puppeteer/i;
app.use((req, res, next) => {
    const ua = req.headers['user-agent'] || '';
    // Permitir requests sem user-agent (pode ser healthcheck do servidor)
    if (ua && blockedAgents.test(ua)) {
        return res.status(403).send('Acesso negado.');
    }
    next();
});

// Cache-Control: impedir que proxies/CDN cacheem dados sensíveis das APIs
app.use('/api/', (req, res, next) => {
    res.set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    res.set('Pragma', 'no-cache');
    next();
});
app.use(express.json({ limit: '5mb' }));
app.use(express.urlencoded({ extended: true, limit: '5mb' }));

// CSRF: exibir header customizado em todas as mutações via fetch
function csrfCheck(req, res, next) {
    if (['POST','PUT','DELETE','PATCH'].includes(req.method)) {
        const xReq = req.headers['x-requested-with'];
        const contentType = req.headers['content-type'] || '';
        // Permitir se tem header X-Requested-With OU se é JSON (forms cross-origin não enviam JSON)
        if (xReq === 'XMLHttpRequest' || contentType.includes('application/json')) {
            return next();
        }
        // Também permitir multipart (uploads via fetch)
        if (contentType.includes('multipart/form-data')) {
            return next();
        }
        return res.status(403).json({ success: false, message: 'Requisição bloqueada (CSRF).' });
    }
    next();
}
app.use('/api/', csrfCheck);

// Rate limiters
const loginLimiter = rateLimit({ windowMs: 15 * 60 * 1000, max: 15, message: { success: false, message: 'Muitas tentativas. Tente novamente em 15 minutos.' }, standardHeaders: true, legacyHeaders: false });
const registroLimiter = rateLimit({ windowMs: 60 * 60 * 1000, max: 5, message: { success: false, message: 'Muitas contas criadas. Tente novamente em 1 hora.' }, standardHeaders: true, legacyHeaders: false });
const emailLimiter = rateLimit({ windowMs: 15 * 60 * 1000, max: 3, message: { success: false, message: 'Muitos emails enviados. Aguarde 15 minutos.' }, standardHeaders: true, legacyHeaders: false });
const apiLimiter = rateLimit({ windowMs: 1 * 60 * 1000, max: 120, standardHeaders: true, legacyHeaders: false });

app.use('/api/', apiLimiter);

// Rate limiter para páginas (anti-scraping: max 60 páginas/minuto)
const pageLimiter = rateLimit({
    windowMs: 1 * 60 * 1000, max: 60,
    message: '<h1>Muitas requisições</h1><p>Aguarde um momento antes de continuar navegando.</p>',
    standardHeaders: false, legacyHeaders: false,
    skip: (req) => req.path.startsWith('/api/') || req.path.startsWith('/css/') || req.path.startsWith('/js/') || req.path.startsWith('/img/') || req.path.startsWith('/uploads/') || req.path.startsWith('/imagens_colheita/') || req.path.startsWith('/styles/') || req.path.startsWith('/themes/') || req.path === '/favicon.ico'
});
app.use(pageLimiter);

// Criar pasta de sessões se não existir
const sessionsDir = path.join(__dirname, 'sessions');
if (!fs.existsSync(sessionsDir)) fs.mkdirSync(sessionsDir, { recursive: true });

app.use(session({
    store: new FileStore({
        path: sessionsDir,
        ttl: 3 * 24 * 60 * 60, // 3 dias (em segundos)
        reapInterval: 900, // limpar sessões expiradas a cada 15 min
        retries: 0,
        logFn: function(){} // silenciar logs do file-store
    }),
    secret: process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex'),
    name: 'yk.sid', // Nome customizado (esconde que usa Express/Node)
    resave: false,
    saveUninitialized: false,
    cookie: {
        maxAge: 24 * 60 * 60 * 1000, // 24h
        httpOnly: true,   // JS não acessa o cookie (protege contra XSS)
        sameSite: 'lax',  // Protege contra CSRF via links externos
        secure: process.env.NODE_ENV === 'production' // true quando HTTPS via trust proxy
    }
}));

// Criar pasta de uploads se não existir
const uploadsDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadsDir)) fs.mkdirSync(uploadsDir, { recursive: true });

// Criar pasta de uploads/fotos se não existir
const fotosDir = path.join(uploadsDir, 'fotos');
if (!fs.existsSync(fotosDir)) fs.mkdirSync(fotosDir, { recursive: true });

// Criar pasta de uploads/sugestoes e uploads/bugs
const sugestoesDir = path.join(uploadsDir, 'sugestoes');
if (!fs.existsSync(sugestoesDir)) fs.mkdirSync(sugestoesDir, { recursive: true });
const bugsDir = path.join(uploadsDir, 'bugs');
if (!fs.existsSync(bugsDir)) fs.mkdirSync(bugsDir, { recursive: true });

// Servir arquivos estáticos com cache agressivo
// Imagens/uploads: 30 dias (raramente mudam)
// CSS/JS: 7 dias (podem mudar com atualizações, usar ?v= para bust)
// Temas: 30 dias
const cacheCSS_JS = { maxAge: '7d', etag: true, lastModified: true };
const cacheImagens = { maxAge: '30d', etag: true, lastModified: true, immutable: true };

app.use('/css', express.static(path.join(__dirname, 'public', 'css'), cacheCSS_JS));
app.use('/js', express.static(path.join(__dirname, 'public', 'js'), cacheCSS_JS));
app.use('/img', express.static(path.join(__dirname, 'public', 'img'), cacheImagens));
app.use('/styles', express.static(path.join(__dirname, 'public', 'styles'), cacheCSS_JS));
app.use('/themes', express.static(path.join(__dirname, 'public', 'themes'), cacheImagens));
app.use('/uploads', express.static(uploadsDir, cacheImagens));
app.use('/imagens_colheita', express.static(path.join(__dirname, 'public', 'imagens_colheita'), cacheImagens));
app.get('/favicon.ico', (req, res) => {
  res.set('Cache-Control', 'public, max-age=2592000, immutable'); // 30 dias
  res.sendFile(path.join(__dirname, 'public', 'favicon.ico'));
});
app.get('/robots.txt', (req, res) => {
  res.set('Cache-Control', 'public, max-age=86400'); // 1 dia
  res.sendFile(path.join(__dirname, 'public', 'robots.txt'));
});

// ===== Helper: verificar banimento =====
function checkBanStatus(user) {
    if (!user || !user.banido) return null;
    if (user.banido_permanente) {
        return { banned: true, permanent: true, motivo: user.banido_motivo, banido_em: user.banido_em };
    }
    if (user.banido_ate) {
        const now = new Date();
        const banEnd = new Date(user.banido_ate);
        if (now < banEnd) {
            return { banned: true, permanent: false, banido_ate: user.banido_ate, motivo: user.banido_motivo, banido_em: user.banido_em };
        } else {
            // Ban expirou, desbanir automaticamente
            db.prepare('UPDATE usuarios SET banido = 0, banido_permanente = 0, banido_ate = NULL, banido_motivo = NULL, banido_por = NULL, banido_em = NULL WHERE id = ?').run(user.id);
            return null;
        }
    }
    return null;
}

// ===== Helper: verificar bloqueio entre dois usuários =====
function isBlocked(userId1, userId2) {
    const block = db.prepare(
        'SELECT id FROM bloqueios WHERE (bloqueador_id = ? AND bloqueado_id = ?) OR (bloqueador_id = ? AND bloqueado_id = ?)'
    ).get(userId1, userId2, userId2, userId1);
    return !!block;
}

// Retorna SQL snippet para excluir usuários bloqueados - usado em queries
// Exemplo: WHERE u.id NOT IN (SELECT ...) 
function blockedIdsSubquery(userId) {
    // Valida que userId é alfanumérico para prevenir SQL injection
    const safeId = String(userId).replace(/[^a-zA-Z0-9_-]/g, '');
    return `(SELECT bloqueado_id FROM bloqueios WHERE bloqueador_id = '${safeId}' UNION SELECT bloqueador_id FROM bloqueios WHERE bloqueado_id = '${safeId}')`;
}

// ===== Middleware de autenticação =====
function requireLogin(req, res, next) {
    if (req.session && req.session.userId) {
        // Verificar banimento
        const user = db.prepare('SELECT id, banido, banido_permanente, banido_ate, banido_motivo, banido_em FROM usuarios WHERE id = ?').get(req.session.userId);
        const banStatus = checkBanStatus(user);
        if (banStatus) {
            req.session.destroy();
            if (req.path.startsWith('/api/')) {
                return res.status(403).json({ success: false, message: 'Sua conta está banida.', banned: true });
            }
            return res.redirect('/banido.php');
        }
        return next();
    }
    // Para chamadas API, retornar JSON em vez de redirect
    if (req.path.startsWith('/api/')) {
        return res.status(401).json({ success: false, message: 'Sessão expirada. Faça login novamente.' });
    }
    return res.redirect('/index.php');
}

function getUserFromSession(req) {
    if (!req.session || !req.session.userId) return null;
    return db.prepare('SELECT * FROM usuarios WHERE id = ?').get(req.session.userId);
}

// ===== ROTAS DE PÁGINAS =====

// Helper: enviar .php como HTML
function sendPhp(res, filename) {
    res.set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    res.type('text/html').sendFile(path.join(__dirname, 'public', filename));
}

// Login
function handleLogin(req, res) {
    if (req.session && req.session.userId) {
        return res.redirect('/profile.php');
    }
    sendPhp(res, 'index.php');
}
app.get('/', handleLogin);
app.get('/index.php', handleLogin);

// Registro
app.get('/registro.php', (req, res) => {
    sendPhp(res, 'registro.php');
});

// Home (logado) - redireciona para perfil
app.get('/home.php', requireLogin, (req, res) => {
    res.redirect('/profile.php');
});

// Perfil (página principal após login)
app.get('/profile.php', requireLogin, (req, res) => {
    sendPhp(res, 'profile.php');
});

// ===== Páginas autenticadas (com layout.js) =====
const authenticatedPages = [
    'recados.php', 'amigos.php', 'fotos.php', 'videos.php',
    'depoimentos.php', 'mensagens_particular.php',
    'configuracoes.php', 'meus_convites.php', 'comunidades.php',
    'user_comunidades.php', 'solicitacoes.php', 'notificacoes.php',
    'kutcoin.php', 'sugestoes.php', 'reportar_bug.php',
    'sobre.php', 'novidades.php', 'search_user.php', 'search_community.php',
    'colheita.php', 'colheita_loja.php', 'colheita_sementes.php', 'colheita_armazem.php',
    'comunidade_convidar_amigo.php', 'anuncios.php', 'anuncio.php', 'forum.php', 'enquetes.php',
    'comunidades_staff.php', 'sorteio.php'
];
authenticatedPages.forEach(page => {
    app.get('/' + page, requireLogin, (req, res) => {
        sendPhp(res, page);
    });
});

// Redirecionar mensagens.php para mensagens_particular.php (página unificada)
app.get('/mensagens.php', requireLogin, (req, res) => {
    const query = req.url.includes('?') ? req.url.substring(req.url.indexOf('?')) : '';
    res.redirect('/mensagens_particular.php' + query);
});

// ===== Temas - GET e POST =====
app.get('/temas.php', requireLogin, (req, res) => {
    sendPhp(res, 'temas.php');
});

app.post('/temas.php', requireLogin, (req, res) => {
    const userId = req.session.userId;
    const { theme_id, aplicar_tema, sem_tema_toggle, remover_tema } = req.body;

    if (sem_tema_toggle !== undefined) {
        // Toggle "navegar sem temas"
        const user = db.prepare('SELECT sem_tema FROM usuarios WHERE id = ?').get(userId);
        const novoValor = user.sem_tema ? 0 : 1;
        db.prepare('UPDATE usuarios SET sem_tema = ? WHERE id = ?').run(novoValor, userId);
    } else if (remover_tema !== undefined) {
        // Remover tema atual
        db.prepare('UPDATE usuarios SET tema_id = NULL WHERE id = ?').run(userId);
    } else if (aplicar_tema !== undefined && theme_id) {
        // Aplicar tema
        const tid = parseInt(theme_id);
        if (TEMAS[tid]) {
            db.prepare('UPDATE usuarios SET tema_id = ? WHERE id = ?').run(tid, userId);
        }
    }

    res.redirect('/temas.php');
});

// API: Dados do tema atual do usuário
app.get('/api/tema', requireLogin, (req, res) => {
    const user = db.prepare('SELECT tema_id, sem_tema FROM usuarios WHERE id = ?').get(req.session.userId);
    const temaAtual = user.tema_id ? TEMAS[user.tema_id] : null;
    res.json({
        tema_id: user.tema_id,
        sem_tema: user.sem_tema,
        tema: temaAtual,
        temas: TEMAS
    });
});

// Página de banimento
app.get('/banido.php', (req, res) => {
    sendPhp(res, 'banido.php');
});

// API: Info de banimento (para a página de banido)
app.get('/api/ban-info', (req, res) => {
    const uid = req.session.bannedUserId || req.session.userId;
    if (!uid) return res.json({ success: false });
    const user = db.prepare('SELECT id, nome, banido, banido_permanente, banido_ate, banido_motivo, banido_em FROM usuarios WHERE id = ?').get(uid);
    if (!user) return res.json({ success: false });
    const banStatus = checkBanStatus(user);
    if (!banStatus) return res.json({ success: false, message: 'Você não está banido.' });
    res.json({ success: true, nome: user.nome, ...banStatus });
});

// ===== Páginas públicas (standalone, sem login) =====
const publicPages = ['termos.php', 'privacidade.php', 'contato.php'];
publicPages.forEach(page => {
    app.get('/' + page, (req, res) => {
        sendPhp(res, page);
    });
});

// Página de recuperação de conta
app.get('/lost_account.php', (req, res) => {
    sendPhp(res, 'lost_account.php');
});

// API: Recuperar conta (enviar senha por e-mail)
app.post('/api/recuperar-conta', emailLimiter, async (req, res) => {
    try {
        const { email } = req.body;
        if (!email) {
            return res.json({ success: false, message: 'Informe o seu e-mail!' });
        }

        const user = db.prepare('SELECT id, nome, email FROM usuarios WHERE email = ?').get(email.trim().toLowerCase());
        if (!user) {
            return res.json({ success: false, message: 'E-mail não encontrado no sistema!' });
        }

        // Invalidar tokens anteriores deste usuário
        db.prepare('UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0').run(user.id);

        // Gerar token seguro (expira em 1 hora)
        const token = crypto.randomBytes(32).toString('hex');
        const expiraEm = new Date(Date.now() + 60 * 60 * 1000).toISOString(); // 1 hora

        db.prepare('INSERT INTO password_resets (usuario_id, token, expira_em) VALUES (?, ?, ?)').run(user.id, token, expiraEm);

        // Montar link de redefinição
        const host = req.headers.host || 'localhost:3000';
        const protocol = req.headers['x-forwarded-proto'] || 'http';
        const resetLink = `${protocol}://${host}/reset_senha.php?token=${token}`;

        // Enviar e-mail via SMTP
        const transporter = nodemailer.createTransport(SMTP_CONFIG);

        const info = await transporter.sendMail({
            from: SMTP_FROM,
            to: user.email,
            subject: 'Yorkut - Recuperação de Conta',
            html: `
                <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #e6399b;">Yorkut - Recuperação de Conta</h2>
                    <p>Olá, <strong>${escapeHtmlServer(user.nome)}</strong>!</p>
                    <p>Você solicitou a recuperação da sua conta. Clique no botão abaixo para redefinir sua senha:</p>
                    <div style="text-align: center; margin: 25px 0;">
                        <a href="${resetLink}" style="background: #e6399b; color: #fff; padding: 14px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block;">Redefinir Minha Senha</a>
                    </div>
                    <p style="font-size: 12px; color: #666;">Ou copie e cole este link no seu navegador:</p>
                    <p style="font-size: 11px; color: #999; word-break: break-all;">${resetLink}</p>
                    <p style="font-size: 12px; color: #666;"><strong>Este link expira em 1 hora.</strong></p>
                    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                    <p style="font-size: 11px; color: #999;">Se você não solicitou essa recuperação, ignore este e-mail. Sua senha não será alterada.</p>
                </div>
            `
        });

        return res.json({ success: true, message: 'Um link de recuperação foi enviado para <strong>' + escapeHtmlServer(user.email) + '</strong>.<br>Verifique sua caixa de entrada e a pasta de spam.<br>O link expira em 1 hora.' });
    } catch (err) {
        console.error('[SMTP] Erro ao enviar e-mail:', err.message);
        return res.json({ success: false, message: 'Ocorreu uma falha ao enviar a recuperação por Email!' });
    }
});

// Página de redefinição de senha (com token)
app.get('/reset_senha.php', (req, res) => {
    sendPhp(res, 'reset_senha.php');
});

// API: Validar token de recuperação
app.get('/api/validar-token-reset', (req, res) => {
    const { token } = req.query;
    if (!token) return res.json({ success: false, message: 'Token não informado.' });

    const reset = db.prepare('SELECT pr.*, u.nome, u.email FROM password_resets pr JOIN usuarios u ON u.id = pr.usuario_id WHERE pr.token = ? AND pr.usado = 0').get(token);
    if (!reset) return res.json({ success: false, message: 'Link inválido ou já utilizado.' });

    if (new Date(reset.expira_em) < new Date()) {
        return res.json({ success: false, message: 'Este link expirou. Solicite uma nova recuperação.' });
    }

    return res.json({ success: true, nome: reset.nome, email: reset.email });
});

// API: Redefinir senha com token
app.post('/api/redefinir-senha', async (req, res) => {
    try {
        const { token, nova_senha } = req.body;
        if (!token || !nova_senha) {
            return res.json({ success: false, message: 'Dados incompletos.' });
        }

        if (nova_senha.length < 6) {
            return res.json({ success: false, message: 'A senha deve ter pelo menos 6 caracteres.' });
        }

        const reset = db.prepare('SELECT * FROM password_resets WHERE token = ? AND usado = 0').get(token);
        if (!reset) return res.json({ success: false, message: 'Link inválido ou já utilizado.' });

        if (new Date(reset.expira_em) < new Date()) {
            return res.json({ success: false, message: 'Este link expirou. Solicite uma nova recuperação.' });
        }

        // Agora sim, alterar a senha
        const senhaHash = await bcrypt.hash(nova_senha, 10);
        db.prepare('UPDATE usuarios SET senha = ? WHERE id = ?').run(senhaHash, reset.usuario_id);

        // Marcar token como usado
        db.prepare('UPDATE password_resets SET usado = 1 WHERE id = ?').run(reset.id);

        return res.json({ success: true, message: 'Senha alterada com sucesso! Faça login com sua nova senha.' });
    } catch (err) {
        console.error('Erro ao redefinir senha:', err);
        return res.json({ success: false, message: 'Erro interno ao redefinir senha.' });
    }
});

// ===== Páginas ainda em construção =====
const placeholderPages = ['seguranca.php'];
placeholderPages.forEach(page => {
    app.get('/' + page, (req, res) => {
        res.type('text/html').send(`
            <!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">
            <title>Yorkut - ${page.replace('.php','')}</title>
            <link rel="stylesheet" href="/styles/profile.css">
            </head><body>
            <header><div class="header-wrapper"><div class="header-main">
            <div class="logo-container"><a href="/profile.php" class="logo">yorkut</a>
            <nav class="nav-main"><a href="/profile.php">início</a></nav></div>
            </div></div></header>
            <div class="container" style="justify-content:center;">
            <div class="card" style="max-width:600px;text-align:center;padding:40px;">
            <h1 style="color:var(--orkut-pink);font-size:18px;">🚧 ${page.replace('.php','')} 🚧</h1>
            <p style="color:#666;margin-top:15px;">Esta página está em construção.</p>
            <a href="/profile.php" style="display:inline-block;margin-top:20px;padding:8px 20px;background:var(--orkut-blue);color:#fff;border-radius:3px;text-decoration:none;">Voltar ao perfil</a>
            </div></div></body></html>
        `);
    });
});

// ===== ROTAS DE API =====

// API: Login
app.post('/api/login', loginLimiter, async (req, res) => {
    try {
        const { email, senha } = req.body;

        if (!email || !senha) {
            return res.json({ success: false, message: 'Preencha todos os campos!' });
        }

        const user = db.prepare('SELECT * FROM usuarios WHERE email = ?').get(email);
        if (!user) {
            return res.json({ success: false, message: 'E-mail ou senha incorretos!' });
        }

        const senhaOk = await bcrypt.compare(senha, user.senha);
        if (!senhaOk) {
            return res.json({ success: false, message: 'E-mail ou senha incorretos!' });
        }

        // Verificar se está banido
        const banStatus = checkBanStatus(user);
        if (banStatus) {
            // Salvar ID na sessão temporariamente para a página de banido
            req.session.bannedUserId = user.id;
            return res.json({ success: false, message: 'Sua conta está banida.', banned: true, redirect: '/banido.php' });
        }

        // Atualizar último acesso
        db.prepare(`UPDATE usuarios SET ultimo_acesso = ${agora()} WHERE id = ?`).run(user.id);

        // Criar sessão
        req.session.userId = user.id;
        req.session.userName = user.nome;

        // "Salvar dados neste computador" = cookie dura 3 dias; senão 24h
        if (req.body.lembrar) {
            req.session.cookie.maxAge = 3 * 24 * 60 * 60 * 1000; // 3 dias
        }

        return res.json({ success: true, message: 'Login realizado!', redirect: '/profile.php' });
    } catch (err) {
        console.error('Erro no login:', err);
        return res.json({ success: false, message: 'Erro interno do servidor.' });
    }
});

// API: Validar token de convite
app.post('/api/validar-token', (req, res) => {
    try {
        const { token } = req.body;

        if (!token) {
            return res.json({ valid: false, message: 'Digite o código do convite!' });
        }

        const convite = db.prepare('SELECT * FROM convites WHERE token = ? AND usado = 0').get(token.toUpperCase().trim());

        if (!convite) {
            return res.json({ valid: false, message: 'Token inválido ou já utilizado!' });
        }

        return res.json({ valid: true, message: 'Token válido! Preencha seus dados.' });
    } catch (err) {
        console.error('Erro na validação do token:', err);
        return res.json({ valid: false, message: 'Erro interno do servidor.' });
    }
});

// API: Registro
app.post('/api/registro', registroLimiter, async (req, res) => {
    try {
        const { token, nome: nomeRaw, email, senha, nascimento, sexo, ddi, whatsapp } = req.body;
        const nome = sanitizeText(nomeRaw, 70);

        // Validações
        if (!token || !nome || !email || !senha) {
            return res.json({ success: false, message: 'Preencha todos os campos obrigatórios!' });
        }

        if (senha.length < 6) {
            return res.json({ success: false, message: 'A senha deve ter no mínimo 6 caracteres!' });
        }

        if (!isValidEmail(email)) {
            return res.json({ success: false, message: 'E-mail inválido!' });
        }

        // Verificar token
        const convite = db.prepare('SELECT * FROM convites WHERE token = ? AND usado = 0').get(token.toUpperCase().trim());
        if (!convite) {
            return res.json({ success: false, message: 'Token inválido ou já utilizado!' });
        }

        // Verificar se email já existe
        const existente = db.prepare('SELECT id FROM usuarios WHERE email = ?').get(email);
        if (existente) {
            return res.json({ success: false, message: 'Este e-mail já está cadastrado!' });
        }

        // Hash da senha
        const senhaHash = await bcrypt.hash(senha, 10);

        // Inserir usuário com ID baseado em timestamp
        const fotoPadrao = defaultAvatar(sexo);
        const userId = generateTimestampId();
        db.prepare(`
            INSERT INTO usuarios (id, nome, email, senha, nascimento, sexo, ddi, whatsapp, foto_perfil, criado_em, ultimo_acesso)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ${agora()}, ${agora()})
        `).run(userId, nome, email, senhaHash, nascimento || null, sexo || null, ddi || '+55', whatsapp || null, fotoPadrao);

        // Marcar token como usado
        db.prepare(`UPDATE convites SET usado = 1, usado_por = ?, usado_em = ${agora()} WHERE id = ?`).run(userId, convite.id);

        // Nota: convites NÃO são gerados automaticamente.
        // O usuário gera manualmente clicando no botão (máx 10).

        // Login automático
        req.session.userId = userId;
        req.session.userName = nome;

        return res.json({ success: true, message: 'Conta criada com sucesso!', redirect: '/profile.php' });
    } catch (err) {
        console.error('Erro no registro:', err);
        return res.json({ success: false, message: 'Erro interno do servidor.' });
    }
});

// API: Dados do usuário logado
app.get('/api/me', requireLogin, (req, res) => {
    const user = getUserFromSession(req);
    if (!user) {
        return res.json({ success: false, message: 'Sessão inválida.' });
    }

    // Remover senha do retorno
    const { senha, ...userSafe } = user;

    // Buscar convites do usuário (com nome de quem usou)
    const convites = db.prepare(`
        SELECT c.token, c.usado, c.criado_em, c.usado_em,
               u.nome AS usado_por_nome, u.id AS usado_por_id
        FROM convites c
        LEFT JOIN usuarios u ON u.id = c.usado_por
        WHERE c.criado_por = ?
        ORDER BY c.usado ASC, c.criado_em ASC
    `).all(user.id);

    const totalGerados = convites.length;
    const MAX_CONVITES = 10;

    // Buscar dados do tema ativo
    let tema = null;
    if (user.tema_id && TEMAS[user.tema_id]) {
        tema = TEMAS[user.tema_id];
    }

    // Contagens consolidadas em uma única query (performance)
    const counts = db.prepare(`
        SELECT
            (SELECT COUNT(*) FROM mensagens WHERE destinatario_id = @uid AND lida = 0 AND excluida_destinatario = 0) AS mensagensNaoLidas,
            (SELECT COUNT(*) FROM recados WHERE destinatario_id = @uid AND criado_em > COALESCE(@recadosVistosEm, '2000-01-01')) AS recadosNaoLidos,
            (SELECT COUNT(*) FROM depoimentos WHERE destinatario_id = @uid AND aprovado = 0) AS depoimentosNaoLidos,
            (SELECT COUNT(*) FROM amizades WHERE destinatario_id = @uid AND status = 'pendente') AS solicitacoesPendentes,
            (SELECT COUNT(*) FROM amizades WHERE status = 'aceita' AND (remetente_id = @uid OR destinatario_id = @uid)) AS totalAmigos,
            (SELECT COUNT(*) FROM notificacoes WHERE usuario_id = @uid AND lida = 0) AS notificacoesNaoLidas,
            (SELECT COUNT(*) FROM recados WHERE destinatario_id = @uid) AS totalRecados,
            (SELECT COUNT(*) FROM fotos WHERE usuario_id = @uid) AS totalFotos,
            (SELECT COUNT(*) FROM videos WHERE usuario_id = @uid) AS totalVideos
    `).get({ uid: user.id, recadosVistosEm: user.recados_vistos_em || null });

    // Se admin, contar denúncias pendentes
    let denunciasPendentes = 0;
    let denunciasComunidadesPendentes = 0;
    if (user.is_admin) {
        const adminCounts = db.prepare(`
            SELECT
                (SELECT COUNT(*) FROM denuncias WHERE status = 'pendente') AS denuncias,
                (SELECT COUNT(*) FROM denuncias_comunidades WHERE status = 'pendente') AS denunciasCom
        `).get();
        denunciasPendentes = adminCounts.denuncias;
        denunciasComunidadesPendentes = adminCounts.denunciasCom;
    }

    return res.json({
        success: true,
        user: userSafe,
        convites: convites,
        max_convites: MAX_CONVITES,
        convites_restantes: MAX_CONVITES - totalGerados,
        tema: tema,
        mensagensNaoLidas: counts.mensagensNaoLidas,
        recadosNaoLidos: counts.recadosNaoLidos,
        depoimentosNaoLidos: counts.depoimentosNaoLidos,
        solicitacoesPendentes: counts.solicitacoesPendentes,
        totalAmigos: counts.totalAmigos,
        denunciasPendentes: denunciasPendentes,
        denunciasComunidadesPendentes: denunciasComunidadesPendentes,
        notificacoesNaoLidas: counts.notificacoesNaoLidas,
        totalRecados: counts.totalRecados,
        totalFotos: counts.totalFotos,
        totalVideos: counts.totalVideos
    });
});

// API: Upload de foto de perfil (base64)
app.post('/api/upload-foto', requireLogin, (req, res) => {
    try {
        const { foto_base64 } = req.body;
        if (!foto_base64 || !foto_base64.startsWith('data:image/')) {
            return res.json({ success: false, message: 'Imagem inválida.' });
        }

        // Extrair tipo e dados
        const matches = foto_base64.match(/^data:image\/(jpeg|png|jpg|webp);base64,(.+)$/);
        if (!matches) {
            return res.json({ success: false, message: 'Formato de imagem não suportado.' });
        }

        const ext = matches[1] === 'jpeg' ? 'jpg' : matches[1];
        const buffer = Buffer.from(matches[2], 'base64');

        // Limitar tamanho (2MB)
        if (buffer.length > 2 * 1024 * 1024) {
            return res.json({ success: false, message: 'Imagem muito grande. Máximo 2MB.' });
        }

        const userId = req.session.userId;
        const filename = `avatar_${userId}_${Date.now()}.${ext}`;
        const filepath = path.join(uploadsDir, filename);

        // Remover foto anterior se existir
        const userAtual = db.prepare('SELECT foto_perfil FROM usuarios WHERE id = ?').get(userId);
        if (userAtual && userAtual.foto_perfil && userAtual.foto_perfil.startsWith('/uploads/')) {
            const oldPath = path.join(__dirname, userAtual.foto_perfil.substring(1));
            if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
        }

        // Salvar arquivo
        fs.writeFileSync(filepath, buffer);

        // Atualizar banco
        const fotoUrl = '/uploads/' + filename;
        db.prepare('UPDATE usuarios SET foto_perfil = ? WHERE id = ?').run(fotoUrl, userId);

        return res.json({ success: true, foto_perfil: fotoUrl });
    } catch (err) {
        console.error('Erro no upload de foto:', err);
        return res.json({ success: false, message: 'Erro interno ao salvar foto.' });
    }
});

// API: Buscar usuários por nome ou email
app.get('/api/buscar-usuario', requireLogin, (req, res) => {
    try {
        const q = (req.query.q || '').trim();
        if (!q || q.length < 2) {
            return res.json({ success: false, message: 'Digite pelo menos 2 caracteres.' });
        }
        const meId = req.session.userId;
        const termo = '%' + q + '%';
        const usuarios = db.prepare(`
            SELECT id, nome, foto_perfil, sexo, cidade, estado
            FROM usuarios
            WHERE (nome LIKE ? COLLATE NOCASE OR email LIKE ? COLLATE NOCASE)
              AND id != ?
              AND id NOT IN ${blockedIdsSubquery(meId)}
            ORDER BY nome COLLATE NOCASE
            LIMIT 20
        `).all(termo, termo, meId);
        return res.json({ success: true, usuarios });
    } catch(err) {
        console.error('Erro ao buscar usuários:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Dados de outro usuário (perfil público)
app.get('/api/user/:id', requireLogin, (req, res) => {
    const uid = req.params.id;
    if (!uid) {
        return res.json({ success: false, message: 'ID inválido.' });
    }
    const user = db.prepare('SELECT * FROM usuarios WHERE id = ?').get(uid);
    if (!user) {
        return res.json({ success: false, message: 'Usuário não encontrado.' });
    }

    // Verificar bloqueio
    if (uid !== req.session.userId && isBlocked(req.session.userId, uid)) {
        return res.json({ success: false, message: 'Usuário não encontrado.', blocked: true });
    }

    // Remover dados sensíveis
    const { senha, email, ...userSafe } = user;
    // Só incluir email se for o próprio perfil
    if (uid === req.session.userId) {
        userSafe.email = user.email;
    }

    // Registrar visita (se não é o próprio perfil)
    if (uid !== req.session.userId) {
        try {
            const visitante = db.prepare('SELECT visitas_rastro FROM usuarios WHERE id = ?').get(req.session.userId);
            // Só registra se o visitante permite deixar rastro
            if (visitante && visitante.visitas_rastro === 'sim') {
                // Remover visita anterior desse mesmo visitante para não duplicar
                db.prepare('DELETE FROM visitas WHERE visitado_id = ? AND visitante_id = ?').run(uid, req.session.userId);
                // Inserir nova visita (sempre no topo = mais recente)
                db.prepare(`INSERT INTO visitas (visitado_id, visitante_id, criado_em) VALUES (?, ?, ${agora()})`).run(uid, req.session.userId);
                // Manter apenas as últimas 50 visitas por perfil
                db.prepare('DELETE FROM visitas WHERE visitado_id = ? AND id NOT IN (SELECT id FROM visitas WHERE visitado_id = ? ORDER BY criado_em DESC LIMIT 50)').run(uid, uid);
            }
        } catch(e) { console.error('Erro ao registrar visita:', e); }
    }

    // Buscar tema do usuário visitado
    let tema = null;
    if (user.tema_id && TEMAS[user.tema_id]) {
        tema = TEMAS[user.tema_id];
    }

    // Verificar status de amizade
    let friendshipStatus = 'none';
    let friendshipRequestId = null;
    if (uid !== req.session.userId) {
        const amizade = db.prepare(`
            SELECT * FROM amizades 
            WHERE (remetente_id = ? AND destinatario_id = ?) 
               OR (remetente_id = ? AND destinatario_id = ?)
        `).get(req.session.userId, uid, uid, req.session.userId);
        if (amizade) {
            if (amizade.status === 'aceita') friendshipStatus = 'amigos';
            else if (amizade.status === 'pendente' && amizade.remetente_id === req.session.userId) friendshipStatus = 'enviada';
            else if (amizade.status === 'pendente' && amizade.destinatario_id === req.session.userId) {
                friendshipStatus = 'recebida';
                friendshipRequestId = amizade.id;
            }
        }
    }

    // Contar amigos do usuário visitado
    const totalAmigos = db.prepare(
        `SELECT COUNT(*) AS total FROM amizades WHERE status = 'aceita' AND (remetente_id = ? OR destinatario_id = ?)`
    ).get(uid, uid).total;

    // Contar recados, fotos e vídeos do usuário
    const totalRecados = db.prepare('SELECT COUNT(*) AS c FROM recados WHERE destinatario_id = ?').get(uid).c;
    const totalFotos = db.prepare('SELECT COUNT(*) AS c FROM fotos WHERE usuario_id = ?').get(uid).c;
    const totalVideos = db.prepare('SELECT COUNT(*) AS c FROM videos WHERE usuario_id = ?').get(uid).c;

    return res.json({
        success: true,
        user: userSafe,
        tema: tema,
        is_own: uid === req.session.userId,
        friendshipStatus: friendshipStatus,
        friendshipRequestId: friendshipRequestId,
        totalAmigos: totalAmigos,
        totalRecados: totalRecados,
        totalFotos: totalFotos,
        totalVideos: totalVideos
    });
});

// API: Listar visitantes de um perfil
app.get('/api/visitas/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        if (!uid) return res.json({ success: false, message: 'ID inválido.' });

        // Verificar se o dono do perfil tem visitas_rastro ativo
        const dono = db.prepare('SELECT visitas_rastro FROM usuarios WHERE id = ?').get(uid);
        if (!dono || dono.visitas_rastro !== 'sim') {
            return res.json({ success: true, visitas: [], rastro_desativado: true });
        }

        const visitas = db.prepare(`
            SELECT v.criado_em, u.id AS visitante_id, u.nome, u.foto_perfil, u.sexo
            FROM visitas v
            JOIN usuarios u ON u.id = v.visitante_id
            WHERE v.visitado_id = ? AND u.visitas_rastro = 'sim'
              AND u.id NOT IN ${blockedIdsSubquery(req.session.userId)}
            ORDER BY v.criado_em DESC
            LIMIT 12
        `).all(uid);

        // Formatar datas para dd/mm/yyyy hh:mm
        const visitasFormatadas = visitas.map(v => {
            let dataFormatada = '';
            if (v.criado_em) {
                const d = v.criado_em.replace(' ', 'T');
                const dt = new Date(d + 'Z'); // Já está em UTC-3 no DB
                const dd = String(dt.getUTCDate()).padStart(2, '0');
                const mm = String(dt.getUTCMonth() + 1).padStart(2, '0');
                const yyyy = dt.getUTCFullYear();
                const hh = String(dt.getUTCHours()).padStart(2, '0');
                const min = String(dt.getUTCMinutes()).padStart(2, '0');
                dataFormatada = `${dd}/${mm}/${yyyy} às ${hh}:${min}`;
            }
            return {
                visitante_id: v.visitante_id,
                nome: v.nome,
                foto_perfil: v.foto_perfil || defaultAvatar(v.sexo),
                sexo: v.sexo,
                data: dataFormatada
            };
        });

        return res.json({ success: true, visitas: visitasFormatadas });
    } catch(err) {
        console.error('Erro ao buscar visitas:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Salvar nome
app.post('/api/salvar-nome', requireLogin, (req, res) => {
    let { nome } = req.body;
    nome = sanitizeText(nome, 70);
    if (!nome) {
        return res.json({ success: false, message: 'Nome não pode ficar vazio.' });
    }
    db.prepare('UPDATE usuarios SET nome = ? WHERE id = ?').run(nome, req.session.userId);
    req.session.userName = nome;
    return res.json({ success: true, message: 'Nome atualizado!' });
});

// API: Salvar status/frase
app.post('/api/salvar-status', requireLogin, (req, res) => {
    const status_texto = sanitizeText(req.body.status_texto, 200);
    db.prepare('UPDATE usuarios SET status_texto = ? WHERE id = ?').run(status_texto, req.session.userId);
    return res.json({ success: true, message: 'Status atualizado!' });
});

// API: Buscar comunidades (para autocomplete @)
app.get('/api/buscar-comunidades', requireLogin, (req, res) => {
    try {
        const q = (req.query.q || '').trim();
        if (q.length < 1) return res.json({ success: true, comunidades: [] });
        const comunidades = db.prepare(`
            SELECT id, nome, foto, categoria,
                   (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = comunidades.id) as membros
            FROM comunidades
            WHERE nome LIKE ?
            ORDER BY membros DESC
            LIMIT 10
        `).all('%' + q + '%');
        res.json({ success: true, comunidades });
    } catch(err) {
        console.error('Erro buscar comunidades:', err);
        res.json({ success: true, comunidades: [] });
    }
});

// API: Buscar comunidades (para página de busca com paginação)
app.get('/api/buscar-comunidades-full', requireLogin, (req, res) => {
    try {
        const q = (req.query.q || '').trim();
        const categoria = (req.query.categoria || '').trim();
        const page = Math.max(1, parseInt(req.query.page) || 1);
        const limit = 12;
        const offset = (page - 1) * limit;
        const userId = req.session.userId;

        let where = 'WHERE 1=1';
        const params = [];
        if (q.length >= 1) {
            where += ' AND c.nome LIKE ?';
            params.push('%' + q + '%');
        }
        if (categoria) {
            where += ' AND c.categoria = ?';
            params.push(categoria);
        }

        const countRow = db.prepare(`SELECT COUNT(*) as total FROM comunidades c ${where}`).get(...params);
        const total = countRow.total;
        const totalPages = Math.ceil(total / limit);

        const comunidades = db.prepare(`
            SELECT c.id, c.nome, c.foto, c.categoria, c.tipo, c.descricao, c.criado_em,
                   c.dono_id,
                   (SELECT nome FROM usuarios WHERE id = c.dono_id) as dono_nome,
                   (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as membros,
                   (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id AND usuario_id = ?) as is_member
            FROM comunidades c
            ${where}
            ORDER BY membros DESC, c.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(userId, ...params, limit, offset);

        // Categorias disponíveis
        const categorias = db.prepare('SELECT DISTINCT categoria FROM comunidades ORDER BY categoria').all().map(r => r.categoria);

        res.json({ success: true, comunidades, page, totalPages, total, categorias });
    } catch(err) {
        console.error('Erro buscar comunidades full:', err);
        res.json({ success: false, message: 'Erro ao buscar.' });
    }
});

// API: Salvar perfil completo
app.post('/api/salvar-perfil', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const campos = [
            'status_texto', 'estado_civil', 'quem_sou_eu', 'interesse_em', 'interesses',
            'atividades', 'musica', 'filmes', 'tv', 'livros', 'esportes',
            'atividades_favoritas', 'comidas', 'herois', 'apelido',
            'data_nascimento', 'hora_nascimento', 'cidade_natal', 'cidade',
            'estado', 'pais', 'sexo', 'orientacao_sexual', 'filhos',
            'altura', 'tipo_fisico', 'etnia', 'religiao', 'humor',
            'estilo', 'fumo', 'bebo', 'animais_estimacao', 'mora_com',
            'escolaridade', 'ensino_medio', 'universidade', 'curso',
            'ano_inicio', 'ano_conclusao_prof', 'grau', 'ocupacao',
            'profissao', 'empresa', 'cargo', 'area_atuacao', 'whatsapp'
        ];

        // Campos de texto livre que precisam de sanitização (com limites)
        const textLimits = {
            'status_texto': 200, 'quem_sou_eu': 2000, 'interesses': 500,
            'atividades': 500, 'musica': 500, 'filmes': 500, 'tv': 500,
            'livros': 500, 'esportes': 500, 'atividades_favoritas': 500,
            'comidas': 500, 'herois': 500, 'apelido': 50,
            'cidade_natal': 100, 'cidade': 100, 'estado': 100, 'pais': 100,
            'ensino_medio': 200, 'universidade': 200, 'curso': 200,
            'ocupacao': 200, 'profissao': 200, 'empresa': 200, 'cargo': 200,
            'area_atuacao': 200, 'whatsapp': 20
        };

        // Montar SET dinâmico
        const sets = [];
        const values = [];
        campos.forEach(campo => {
            if (req.body[campo] !== undefined) {
                // data_nascimento mapeia para coluna nascimento
                const coluna = campo === 'data_nascimento' ? 'nascimento' : campo;
                let val = req.body[campo] || '';
                // Sanitizar campos de texto livre
                if (textLimits[campo]) {
                    val = sanitizeText(val, textLimits[campo]);
                } else {
                    val = sanitizeText(val, 200); // limite padrão para campos select/curtos
                }
                sets.push(coluna + ' = ?');
                values.push(val);
            }
        });

        if (sets.length === 0) {
            return res.json({ success: false, message: 'Nenhum dado para salvar.' });
        }

        values.push(userId);
        db.prepare('UPDATE usuarios SET ' + sets.join(', ') + ' WHERE id = ?').run(...values);

        return res.json({ success: true, message: 'Perfil salvo com sucesso!' });
    } catch (err) {
        console.error('Erro ao salvar perfil:', err);
        return res.json({ success: false, message: 'Erro interno ao salvar perfil.' });
    }
});

// ===== API: Recados =====

// Marcar recados como vistos (chamado ao entrar na página de recados)
app.post('/api/recados/marcar-vistos', requireLogin, (req, res) => {
    try {
        db.prepare(`UPDATE usuarios SET recados_vistos_em = ${agora()} WHERE id = ?`).run(req.session.userId);
        return res.json({ success: true });
    } catch (err) {
        return res.json({ success: false });
    }
});

// Listar recados de um usuário
app.get('/api/recados/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const page = parseInt(req.query.page) || 1;
        const limit = Math.min(parseInt(req.query.limit) || 10, 50);
        const offset = (page - 1) * limit;

        if (!uid) {
            return res.json({ success: false, message: 'ID inválido.' });
        }

        const blockedSq = blockedIdsSubquery(req.session.userId);
        const total = db.prepare(`SELECT COUNT(*) as total FROM recados WHERE destinatario_id = ? AND remetente_id NOT IN ${blockedSq}`).get(uid).total;

        // Se o dono está visualizando seus próprios recados, marcar como vistos
        if (uid === req.session.userId) {
            db.prepare(`UPDATE usuarios SET recados_vistos_em = ${agora()} WHERE id = ?`).run(uid);
        }

        const recados = db.prepare(`
            SELECT r.*, 
                   u.nome AS remetente_nome, u.foto_perfil AS remetente_foto, u.sexo AS remetente_sexo,
                   ur.nome AS respondido_nome, ur.foto_perfil AS respondido_foto, ur.sexo AS respondido_sexo
            FROM recados r
            JOIN usuarios u ON u.id = r.remetente_id
            LEFT JOIN usuarios ur ON ur.id = r.destinatario_id
            WHERE r.destinatario_id = ? AND r.remetente_id NOT IN ${blockedSq}
            ORDER BY r.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(uid, limit, offset);

        return res.json({
            success: true,
            recados,
            total,
            page,
            limit,
            totalPages: Math.ceil(total / limit)
        });
    } catch (err) {
        console.error('Erro ao buscar recados:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Enviar recado
app.post('/api/recados', requireLogin, (req, res) => {
    try {
        const { destinatario_id } = req.body;
        let mensagem = req.body.mensagem;
        const remetente_id = req.session.userId;

        if (!destinatario_id || !mensagem || !mensagem.trim()) {
            return res.json({ success: false, message: 'Dados incompletos.' });
        }
        mensagem = mensagem.trim().substring(0, 5000);

        const destId = String(destinatario_id);
        // Verificar se destinatário existe
        const dest = db.prepare('SELECT id FROM usuarios WHERE id = ?').get(destId);
        if (!dest) {
            return res.json({ success: false, message: 'Usuário não encontrado.' });
        }

        // Verificar bloqueio
        if (isBlocked(remetente_id, destId)) {
            return res.json({ success: false, message: 'Não é possível enviar recado para este usuário.' });
        }

        const result = db.prepare(
            `INSERT INTO recados (destinatario_id, remetente_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`
        ).run(destId, remetente_id, mensagem.trim());

        // Processar @menções e enviar notificações
        processarMencoes(mensagem.trim(), remetente_id, 'mencao_recado', '/recados.php?uid=' + destId);

        return res.json({ success: true, message: 'Recado enviado!', id: result.lastInsertRowid });
    } catch (err) {
        console.error('Erro ao enviar recado:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Responder recado
app.post('/api/recados/:id/responder', requireLogin, (req, res) => {
    try {
        const recadoId = parseInt(req.params.id);
        const { resposta } = req.body;
        const userId = req.session.userId;

        if (!resposta || !resposta.trim()) {
            return res.json({ success: false, message: 'Resposta vazia.' });
        }

        // Verificar se o recado pertence ao usuário
        const recado = db.prepare('SELECT * FROM recados WHERE id = ? AND destinatario_id = ?').get(recadoId, userId);
        if (!recado) {
            return res.json({ success: false, message: 'Recado não encontrado.' });
        }

        db.prepare(`UPDATE recados SET resposta = ?, resposta_em = ${agora()} WHERE id = ?`).run(resposta.trim(), recadoId);

        return res.json({ success: true, message: 'Resposta enviada!' });
    } catch (err) {
        console.error('Erro ao responder recado:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir recados (dono do perfil OU remetente)
app.post('/api/recados/excluir', requireLogin, (req, res) => {
    try {
        const { ids } = req.body;
        const userId = req.session.userId;

        if (!ids || !Array.isArray(ids) || ids.length === 0) {
            return res.json({ success: false, message: 'Nenhum recado selecionado.' });
        }

        const placeholders = ids.map(() => '?').join(',');
        const deleted = db.prepare(
            `DELETE FROM recados WHERE id IN (${placeholders}) AND (destinatario_id = ? OR remetente_id = ?)`
        ).run(...ids.map(Number), userId, userId);

        return res.json({ success: true, message: `${deleted.changes} recado(s) excluído(s).` });
    } catch (err) {
        console.error('Erro ao excluir recados:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Recados enviados pelo usuário
app.get('/api/recados-enviados', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const page = parseInt(req.query.page) || 1;
        const limit = Math.min(parseInt(req.query.limit) || 10, 50);
        const offset = (page - 1) * limit;

        const total = db.prepare('SELECT COUNT(*) as total FROM recados WHERE remetente_id = ?').get(userId).total;

        const recados = db.prepare(`
            SELECT r.*, 
                   u.nome AS destinatario_nome, u.foto_perfil AS destinatario_foto, u.sexo AS destinatario_sexo
            FROM recados r
            JOIN usuarios u ON u.id = r.destinatario_id
            WHERE r.remetente_id = ?
            ORDER BY r.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(userId, limit, offset);

        return res.json({
            success: true,
            recados,
            total,
            page,
            limit,
            totalPages: Math.ceil(total / limit)
        });
    } catch (err) {
        console.error('Erro ao buscar recados enviados:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== API: Mensagens Particulares =====

// Enviar mensagem
app.post('/api/mensagens', requireLogin, (req, res) => {
    try {
        const { destinatario_id, assunto, mensagem } = req.body;
        const remetente_id = req.session.userId;

        if (!destinatario_id || !assunto || !assunto.trim() || !mensagem || !mensagem.trim()) {
            return res.json({ success: false, message: 'Preencha todos os campos.' });
        }

        const destId = String(destinatario_id);
        const dest = db.prepare('SELECT id FROM usuarios WHERE id = ?').get(destId);
        if (!dest) {
            return res.json({ success: false, message: 'Destinatário não encontrado.' });
        }

        if (destId === remetente_id) {
            return res.json({ success: false, message: 'Você não pode enviar mensagem para si mesmo.' });
        }

        // Verificar bloqueio
        if (isBlocked(remetente_id, destId)) {
            return res.json({ success: false, message: 'Não é possível enviar mensagem para este usuário.' });
        }

        const result = db.prepare(
            `INSERT INTO mensagens (remetente_id, destinatario_id, assunto, mensagem, criado_em) VALUES (?, ?, ?, ?, ${agora()})`
        ).run(remetente_id, destId, assunto.trim(), mensagem.trim());

        return res.json({ success: true, message: 'Mensagem enviada com sucesso!', id: result.lastInsertRowid });
    } catch (err) {
        console.error('Erro ao enviar mensagem:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar caixa de entrada
app.get('/api/mensagens', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const page = parseInt(req.query.page) || 1;
        const limit = Math.min(parseInt(req.query.limit) || 10, 50);
        const offset = (page - 1) * limit;

        const blockedSqM = blockedIdsSubquery(userId);
        const total = db.prepare(
            `SELECT COUNT(*) as total FROM mensagens WHERE destinatario_id = ? AND excluida_destinatario = 0 AND remetente_id NOT IN ${blockedSqM}`
        ).get(userId).total;

        const mensagens = db.prepare(`
            SELECT m.*, u.nome AS remetente_nome, u.foto_perfil AS remetente_foto, u.sexo AS remetente_sexo
            FROM mensagens m
            JOIN usuarios u ON u.id = m.remetente_id
            WHERE m.destinatario_id = ? AND m.excluida_destinatario = 0
              AND m.remetente_id NOT IN ${blockedSqM}
            ORDER BY m.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(userId, limit, offset);

        const naoLidas = db.prepare(
            `SELECT COUNT(*) as total FROM mensagens WHERE destinatario_id = ? AND lida = 0 AND excluida_destinatario = 0 AND remetente_id NOT IN ${blockedSqM}`
        ).get(userId).total;

        return res.json({
            success: true,
            mensagens,
            total,
            naoLidas,
            page,
            limit,
            totalPages: Math.ceil(total / limit)
        });
    } catch (err) {
        console.error('Erro ao buscar mensagens:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar mensagens enviadas
app.get('/api/mensagens-enviadas', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const page = parseInt(req.query.page) || 1;
        const limit = Math.min(parseInt(req.query.limit) || 10, 50);
        const offset = (page - 1) * limit;

        const total = db.prepare(
            'SELECT COUNT(*) as total FROM mensagens WHERE remetente_id = ? AND excluida_remetente = 0'
        ).get(userId).total;

        const mensagens = db.prepare(`
            SELECT m.*, u.nome AS destinatario_nome, u.foto_perfil AS destinatario_foto, u.sexo AS destinatario_sexo
            FROM mensagens m
            JOIN usuarios u ON u.id = m.destinatario_id
            WHERE m.remetente_id = ? AND m.excluida_remetente = 0
            ORDER BY m.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(userId, limit, offset);

        return res.json({
            success: true,
            mensagens,
            total,
            page,
            limit,
            totalPages: Math.ceil(total / limit)
        });
    } catch (err) {
        console.error('Erro ao buscar mensagens enviadas:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Ler mensagem (marcar como lida e retornar conteúdo)
app.get('/api/mensagens/:id', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const msgId = parseInt(req.params.id);

        const msg = db.prepare(`
            SELECT m.*, 
                   ur.nome AS remetente_nome, ur.foto_perfil AS remetente_foto, ur.sexo AS remetente_sexo,
                   ud.nome AS destinatario_nome, ud.foto_perfil AS destinatario_foto, ud.sexo AS destinatario_sexo
            FROM mensagens m
            JOIN usuarios ur ON ur.id = m.remetente_id
            JOIN usuarios ud ON ud.id = m.destinatario_id
            WHERE m.id = ? AND (m.destinatario_id = ? OR m.remetente_id = ?)
        `).get(msgId, userId, userId);

        if (!msg) {
            return res.json({ success: false, message: 'Mensagem não encontrada.' });
        }

        // Marcar como lida se for o destinatário
        if (msg.destinatario_id === userId && !msg.lida) {
            db.prepare('UPDATE mensagens SET lida = 1 WHERE id = ?').run(msgId);
        }

        return res.json({ success: true, mensagem: msg });
    } catch (err) {
        console.error('Erro ao ler mensagem:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir mensagens
app.post('/api/mensagens/excluir', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { ids, tipo } = req.body; // tipo: 'inbox' ou 'outbox'

        if (!ids || !Array.isArray(ids) || ids.length === 0) {
            return res.json({ success: false, message: 'Nenhuma mensagem selecionada.' });
        }

        const placeholders = ids.map(() => '?').join(',');
        
        if (tipo === 'outbox') {
            db.prepare(
                `UPDATE mensagens SET excluida_remetente = 1 WHERE id IN (${placeholders}) AND remetente_id = ?`
            ).run(...ids, userId);
        } else {
            db.prepare(
                `UPDATE mensagens SET excluida_destinatario = 1 WHERE id IN (${placeholders}) AND destinatario_id = ?`
            ).run(...ids, userId);
        }

        return res.json({ success: true, message: 'Mensagem(ns) excluída(s).' });
    } catch (err) {
        console.error('Erro ao excluir mensagens:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Gerar convite (1 por vez, máx 10)
app.post('/api/gerar-convite', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const MAX_CONVITES = 10;

        // Contar quantos já gerou
        const totalGerados = db.prepare('SELECT COUNT(*) as total FROM convites WHERE criado_por = ?').get(userId).total;

        if (totalGerados >= MAX_CONVITES) {
            return res.json({ success: false, message: 'Você já gerou todos os seus ' + MAX_CONVITES + ' convites.' });
        }

        const novoToken = crypto.randomBytes(8).toString('hex').toUpperCase();
        db.prepare(`INSERT INTO convites (token, criado_por, criado_em) VALUES (?, ?, ${agora()})`).run(novoToken, userId);

        const restantes = MAX_CONVITES - totalGerados - 1;

        return res.json({
            success: true,
            token: novoToken,
            message: 'Convite gerado!',
            convites_restantes: restantes
        });
    } catch (err) {
        console.error('Erro ao gerar convite:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== API: Depoimentos =====

// Marcar depoimentos como vistos (DEVE vir antes das rotas com :id)
app.post('/api/depoimentos/marcar-vistos', requireLogin, (req, res) => {
    try {
        db.prepare(`UPDATE usuarios SET depoimentos_vistos_em = ${agora()} WHERE id = ?`).run(req.session.userId);
        return res.json({ success: true });
    } catch (err) {
        return res.json({ success: false });
    }
});

// Enviar depoimento para alguém
app.post('/api/depoimentos', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { destinatario_id, mensagem } = req.body;

        if (!destinatario_id || !mensagem || !mensagem.trim()) {
            return res.json({ success: false, message: 'Depoimento não pode ser vazio.' });
        }

        const destId = String(destinatario_id);
        if (destId === userId) {
            return res.json({ success: false, message: 'Você não pode escrever depoimento para si mesmo.' });
        }

        const dest = db.prepare('SELECT id FROM usuarios WHERE id = ?').get(destId);
        if (!dest) {
            return res.json({ success: false, message: 'Usuário não encontrado.' });
        }

        // Verificar bloqueio
        if (isBlocked(userId, destId)) {
            return res.json({ success: false, message: 'Não é possível enviar depoimento para este usuário.' });
        }

        db.prepare(
            `INSERT INTO depoimentos (destinatario_id, remetente_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`
        ).run(destId, userId, mensagem.trim());

        // Processar @menções e enviar notificações
        processarMencoes(mensagem.trim(), userId, 'mencao_depoimento', '/depoimentos.php?uid=' + destId);

        return res.json({ success: true, message: 'Depoimento enviado! Aguardando aprovação.' });
    } catch (err) {
        console.error('Erro ao enviar depoimento:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar depoimentos de um usuário (aprovados + pendentes para o dono)
app.get('/api/depoimentos/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const userId = req.session.userId;
        const isOwner = (uid === userId);

        const blockedSqD = blockedIdsSubquery(userId);
        let depoimentos;
        if (isOwner) {
            depoimentos = db.prepare(`
                SELECT d.*, u.nome AS remetente_nome, u.foto_perfil AS remetente_foto, u.sexo AS remetente_sexo
                FROM depoimentos d
                JOIN usuarios u ON u.id = d.remetente_id
                WHERE d.destinatario_id = ? AND d.remetente_id NOT IN ${blockedSqD}
                ORDER BY d.criado_em DESC
            `).all(uid);
        } else {
            // Show approved + sender's own pending depoimentos
            depoimentos = db.prepare(`
                SELECT d.*, u.nome AS remetente_nome, u.foto_perfil AS remetente_foto, u.sexo AS remetente_sexo
                FROM depoimentos d
                JOIN usuarios u ON u.id = d.remetente_id
                WHERE d.destinatario_id = ? AND (d.aprovado = 1 OR d.remetente_id = ?) AND d.remetente_id NOT IN ${blockedSqD}
                ORDER BY d.criado_em DESC
            `).all(uid, userId);
        }

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);
        return res.json({ success: true, depoimentos, perfil, isOwner, myId: userId });
    } catch (err) {
        console.error('Erro ao buscar depoimentos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Aprovar depoimento
app.post('/api/depoimentos/:id/aprovar', requireLogin, (req, res) => {
    try {
        const depId = parseInt(req.params.id);
        const userId = req.session.userId;

        const dep = db.prepare('SELECT * FROM depoimentos WHERE id = ?').get(depId);
        if (!dep) return res.json({ success: false, message: 'Depoimento não encontrado.' });
        if (dep.destinatario_id !== userId) return res.json({ success: false, message: 'Sem permissão.' });

        db.prepare('UPDATE depoimentos SET aprovado = 1 WHERE id = ?').run(depId);
        return res.json({ success: true, message: 'Depoimento aprovado!' });
    } catch (err) {
        console.error('Erro ao aprovar depoimento:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Recusar (excluir) depoimento
app.post('/api/depoimentos/:id/recusar', requireLogin, (req, res) => {
    try {
        const depId = parseInt(req.params.id);
        const userId = req.session.userId;

        const dep = db.prepare('SELECT * FROM depoimentos WHERE id = ?').get(depId);
        if (!dep) return res.json({ success: false, message: 'Depoimento não encontrado.' });
        if (dep.destinatario_id !== userId && dep.remetente_id !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM depoimentos WHERE id = ?').run(depId);
        return res.json({ success: true, message: 'Depoimento removido.' });
    } catch (err) {
        console.error('Erro ao recusar depoimento:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== FOTOS =====
const MAX_FOTOS = 24;

// Upload de foto (base64) — MUST be before /:uid route
app.post('/api/fotos/upload', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { foto_base64, descricao } = req.body;

        // Verificar limite
        const count = db.prepare('SELECT COUNT(*) AS total FROM fotos WHERE usuario_id = ?').get(userId);
        if (count.total >= MAX_FOTOS) {
            return res.json({ success: false, message: `Limite de ${MAX_FOTOS} fotos atingido.` });
        }

        if (!foto_base64 || !foto_base64.startsWith('data:image/')) {
            return res.json({ success: false, message: 'Imagem inválida.' });
        }

        const matches = foto_base64.match(/^data:image\/(jpeg|png|jpg|gif|webp);base64,(.+)$/);
        if (!matches) return res.json({ success: false, message: 'Formato de imagem inválido.' });

        const ext = matches[1] === 'jpeg' ? 'jpg' : matches[1];
        const buffer = Buffer.from(matches[2], 'base64');

        // Limitar tamanho (2MB)
        if (buffer.length > 2 * 1024 * 1024) {
            return res.json({ success: false, message: 'Imagem muito grande. Máximo 2MB.' });
        }

        const filename = 'foto_' + crypto.randomBytes(8).toString('hex') + '.' + ext;
        const filepath = path.join(fotosDir, filename);

        fs.writeFileSync(filepath, buffer);

        const arquivo = '/uploads/fotos/' + filename;
        const desc = (descricao || '').trim().substring(0, 200);

        db.prepare(`INSERT INTO fotos (usuario_id, arquivo, descricao, criado_em) VALUES (?, ?, ?, ${agora()})`).run(userId, arquivo, desc);

        return res.json({ success: true, message: 'Foto enviada com sucesso!' });
    } catch (err) {
        console.error('Erro no upload de foto:', err);
        return res.json({ success: false, message: 'Erro interno ao salvar foto.' });
    }
});

// Deletar comentário (owner da foto OU autor do comentário) — MUST be before /api/fotos/:id
app.delete('/api/fotos/comentario/:id', requireLogin, (req, res) => {
    try {
        const commentId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comment = db.prepare('SELECT c.*, f.usuario_id AS foto_owner FROM fotos_comentarios c JOIN fotos f ON f.id = c.foto_id WHERE c.id = ?').get(commentId);
        if (!comment) return res.json({ success: false, message: 'Comentário não encontrado.' });
        if (comment.usuario_id !== userId && comment.foto_owner !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM fotos_comentarios WHERE id = ?').run(commentId);
        return res.json({ success: true, message: 'Comentário removido.' });
    } catch (err) {
        console.error('Erro ao deletar comentário:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar fotos de um usuário
app.get('/api/fotos/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const userId = req.session.userId;
        const isOwner = uid === userId;

        // Verificar bloqueio
        if (!isOwner && isBlocked(userId, uid)) {
            return res.json({ success: false, message: 'Usuário não encontrado.', blocked: true });
        }

        const fotos = db.prepare(`
            SELECT f.*, 
                (SELECT COUNT(*) FROM fotos_curtidas WHERE foto_id = f.id) AS curtidas,
                (SELECT COUNT(*) FROM fotos_comentarios WHERE foto_id = f.id) AS comentarios
            FROM fotos f 
            WHERE f.usuario_id = ? 
            ORDER BY f.criado_em DESC
        `).all(uid);

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);
        const total = fotos.length;

        return res.json({ success: true, fotos, perfil, isOwner, total, maxFotos: MAX_FOTOS });
    } catch (err) {
        console.error('Erro ao listar fotos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Detalhe de uma foto (com curtidas e comentários)
app.get('/api/fotos/:uid/:fotoId', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const fotoId = parseInt(req.params.fotoId);
        const userId = req.session.userId;
        const isOwner = uid === userId;

        const foto = db.prepare(`
            SELECT f.*,
                (SELECT COUNT(*) FROM fotos_curtidas WHERE foto_id = f.id) AS curtidas
            FROM fotos f
            WHERE f.id = ? AND f.usuario_id = ?
        `).get(fotoId, uid);

        if (!foto) return res.json({ success: false, message: 'Foto não encontrada.' });

        const minhaCurtida = db.prepare('SELECT id FROM fotos_curtidas WHERE foto_id = ? AND usuario_id = ?').get(fotoId, userId);
        foto.curti = !!minhaCurtida;

        const comentarios = db.prepare(`
            SELECT c.*, u.nome AS autor_nome, u.foto_perfil AS autor_foto, u.sexo AS autor_sexo
            FROM fotos_comentarios c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.foto_id = ?
            ORDER BY c.criado_em ASC
        `).all(fotoId);

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);

        return res.json({ success: true, foto, comentarios, perfil, isOwner, myId: userId });
    } catch (err) {
        console.error('Erro ao buscar foto:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Curtir/descurtir foto
app.post('/api/fotos/:id/curtir', requireLogin, (req, res) => {
    try {
        const fotoId = parseInt(req.params.id);
        const userId = req.session.userId;

        const foto = db.prepare('SELECT * FROM fotos WHERE id = ?').get(fotoId);
        if (!foto) return res.json({ success: false, message: 'Foto não encontrada.' });

        const existing = db.prepare('SELECT id FROM fotos_curtidas WHERE foto_id = ? AND usuario_id = ?').get(fotoId, userId);
        let liked;
        if (existing) {
            db.prepare('DELETE FROM fotos_curtidas WHERE id = ?').run(existing.id);
            liked = false;
        } else {
            db.prepare(`INSERT INTO fotos_curtidas (foto_id, usuario_id, criado_em) VALUES (?, ?, ${agora()})`).run(fotoId, userId);
            liked = true;
        }

        const total = db.prepare('SELECT COUNT(*) AS c FROM fotos_curtidas WHERE foto_id = ?').get(fotoId).c;
        return res.json({ success: true, liked, total });
    } catch (err) {
        console.error('Erro ao curtir foto:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Comentar foto
app.post('/api/fotos/:id/comentar', requireLogin, (req, res) => {
    try {
        const fotoId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { mensagem } = req.body;

        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Comentário vazio.' });

        const foto = db.prepare('SELECT * FROM fotos WHERE id = ?').get(fotoId);
        if (!foto) return res.json({ success: false, message: 'Foto não encontrada.' });

        db.prepare(`INSERT INTO fotos_comentarios (foto_id, usuario_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`).run(fotoId, userId, mensagem.trim());

        return res.json({ success: true, message: 'Comentário enviado.' });
    } catch (err) {
        console.error('Erro ao comentar foto:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Atualizar descrição da foto (owner only)
app.put('/api/fotos/:id/descricao', requireLogin, (req, res) => {
    try {
        const fotoId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { descricao } = req.body;

        const foto = db.prepare('SELECT * FROM fotos WHERE id = ?').get(fotoId);
        if (!foto) return res.json({ success: false, message: 'Foto não encontrada.' });
        if (foto.usuario_id !== userId) return res.json({ success: false, message: 'Sem permissão.' });

        const desc = (descricao || '').trim().substring(0, 200);
        db.prepare('UPDATE fotos SET descricao = ? WHERE id = ?').run(desc, fotoId);

        return res.json({ success: true, message: 'Descrição atualizada.' });
    } catch (err) {
        console.error('Erro ao atualizar descrição:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Deletar foto (owner only)
app.delete('/api/fotos/:id', requireLogin, (req, res) => {
    try {
        const fotoId = parseInt(req.params.id);
        const userId = req.session.userId;

        const foto = db.prepare('SELECT * FROM fotos WHERE id = ?').get(fotoId);
        if (!foto) return res.json({ success: false, message: 'Foto não encontrada.' });
        if (foto.usuario_id !== userId) return res.json({ success: false, message: 'Sem permissão.' });

        // Deletar arquivo físico
        const filePath = path.join(__dirname, foto.arquivo.substring(1));
        if (fs.existsSync(filePath)) {
            try { fs.unlinkSync(filePath); } catch(e) {}
        }

        // Deletar da DB (curtidas e comentários são CASCADE)
        db.prepare('DELETE FROM fotos_curtidas WHERE foto_id = ?').run(fotoId);
        db.prepare('DELETE FROM fotos_comentarios WHERE foto_id = ?').run(fotoId);
        db.prepare('DELETE FROM fotos WHERE id = ?').run(fotoId);

        return res.json({ success: true, message: 'Foto removida.' });
    } catch (err) {
        console.error('Erro ao deletar foto:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== VÍDEOS =====
const MAX_VIDEOS = 6;

// Helper: extrair YouTube ID de URL
function extractYoutubeId(url) {
    if (!url) return null;
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/,
        /^([a-zA-Z0-9_-]{11})$/
    ];
    for (const p of patterns) {
        const m = url.match(p);
        if (m) return m[1];
    }
    return null;
}

// Adicionar vídeo — MUST be before /:uid route
app.post('/api/videos/add', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { youtube_url, descricao } = req.body;

        const count = db.prepare('SELECT COUNT(*) AS total FROM videos WHERE usuario_id = ?').get(userId);
        if (count.total >= MAX_VIDEOS) {
            return res.json({ success: false, message: `Limite de ${MAX_VIDEOS} vídeos atingido.` });
        }

        const ytId = extractYoutubeId(youtube_url);
        if (!ytId) {
            return res.json({ success: false, message: 'Link do YouTube inválido.' });
        }

        const desc = (descricao || '').trim().substring(0, 200);
        db.prepare(`INSERT INTO videos (usuario_id, youtube_id, descricao, criado_em) VALUES (?, ?, ?, ${agora()})`).run(userId, ytId, desc);

        return res.json({ success: true, message: 'Vídeo adicionado com sucesso!' });
    } catch (err) {
        console.error('Erro ao adicionar vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Deletar comentário de vídeo — MUST be before /api/videos/:id
app.delete('/api/videos/comentario/:id', requireLogin, (req, res) => {
    try {
        const commentId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comment = db.prepare('SELECT c.*, v.usuario_id AS video_owner FROM videos_comentarios c JOIN videos v ON v.id = c.video_id WHERE c.id = ?').get(commentId);
        if (!comment) return res.json({ success: false, message: 'Comentário não encontrado.' });
        if (comment.usuario_id !== userId && comment.video_owner !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM videos_comentarios WHERE id = ?').run(commentId);
        return res.json({ success: true, message: 'Comentário removido.' });
    } catch (err) {
        console.error('Erro ao deletar comentário de vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar vídeos de um usuário
app.get('/api/videos/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const userId = req.session.userId;
        const isOwner = uid === userId;

        // Verificar bloqueio
        if (!isOwner && isBlocked(userId, uid)) {
            return res.json({ success: false, message: 'Usuário não encontrado.', blocked: true });
        }

        const videos = db.prepare(`
            SELECT v.*, 
                (SELECT COUNT(*) FROM videos_curtidas WHERE video_id = v.id) AS curtidas,
                (SELECT COUNT(*) FROM videos_comentarios WHERE video_id = v.id) AS comentarios
            FROM videos v 
            WHERE v.usuario_id = ? 
            ORDER BY v.criado_em DESC
        `).all(uid);

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);
        const total = videos.length;

        return res.json({ success: true, videos, perfil, isOwner, total, maxVideos: MAX_VIDEOS });
    } catch (err) {
        console.error('Erro ao listar vídeos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Detalhe de um vídeo (com curtidas e comentários)
app.get('/api/videos/:uid/:videoId', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const videoId = parseInt(req.params.videoId);
        const userId = req.session.userId;
        const isOwner = uid === userId;

        const video = db.prepare(`
            SELECT v.*,
                (SELECT COUNT(*) FROM videos_curtidas WHERE video_id = v.id) AS curtidas
            FROM videos v
            WHERE v.id = ? AND v.usuario_id = ?
        `).get(videoId, uid);

        if (!video) return res.json({ success: false, message: 'Vídeo não encontrado.' });

        const minhaCurtida = db.prepare('SELECT id FROM videos_curtidas WHERE video_id = ? AND usuario_id = ?').get(videoId, userId);
        video.curti = !!minhaCurtida;

        const comentarios = db.prepare(`
            SELECT c.*, u.nome AS autor_nome, u.foto_perfil AS autor_foto, u.sexo AS autor_sexo
            FROM videos_comentarios c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.video_id = ?
            ORDER BY c.criado_em ASC
        `).all(videoId);

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);

        return res.json({ success: true, video, comentarios, perfil, isOwner, myId: userId });
    } catch (err) {
        console.error('Erro ao buscar vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Curtir/descurtir vídeo
app.post('/api/videos/:id/curtir', requireLogin, (req, res) => {
    try {
        const videoId = parseInt(req.params.id);
        const userId = req.session.userId;

        const video = db.prepare('SELECT * FROM videos WHERE id = ?').get(videoId);
        if (!video) return res.json({ success: false, message: 'Vídeo não encontrado.' });

        const existing = db.prepare('SELECT id FROM videos_curtidas WHERE video_id = ? AND usuario_id = ?').get(videoId, userId);
        let liked;
        if (existing) {
            db.prepare('DELETE FROM videos_curtidas WHERE id = ?').run(existing.id);
            liked = false;
        } else {
            db.prepare(`INSERT INTO videos_curtidas (video_id, usuario_id, criado_em) VALUES (?, ?, ${agora()})`).run(videoId, userId);
            liked = true;
        }

        const total = db.prepare('SELECT COUNT(*) AS c FROM videos_curtidas WHERE video_id = ?').get(videoId).c;
        return res.json({ success: true, liked, total });
    } catch (err) {
        console.error('Erro ao curtir vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Comentar vídeo
app.post('/api/videos/:id/comentar', requireLogin, (req, res) => {
    try {
        const videoId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { mensagem } = req.body;

        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Comentário vazio.' });

        const video = db.prepare('SELECT * FROM videos WHERE id = ?').get(videoId);
        if (!video) return res.json({ success: false, message: 'Vídeo não encontrado.' });

        db.prepare(`INSERT INTO videos_comentarios (video_id, usuario_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`).run(videoId, userId, mensagem.trim());

        return res.json({ success: true, message: 'Comentário enviado.' });
    } catch (err) {
        console.error('Erro ao comentar vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Atualizar descrição do vídeo (owner only)
app.put('/api/videos/:id/descricao', requireLogin, (req, res) => {
    try {
        const videoId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { descricao } = req.body;

        const video = db.prepare('SELECT * FROM videos WHERE id = ?').get(videoId);
        if (!video) return res.json({ success: false, message: 'Vídeo não encontrado.' });
        if (video.usuario_id !== userId) return res.json({ success: false, message: 'Sem permissão.' });

        const desc = (descricao || '').trim().substring(0, 200);
        db.prepare('UPDATE videos SET descricao = ? WHERE id = ?').run(desc, videoId);

        return res.json({ success: true, message: 'Descrição atualizada.' });
    } catch (err) {
        console.error('Erro ao atualizar descrição do vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Deletar vídeo (owner only)
app.delete('/api/videos/:id', requireLogin, (req, res) => {
    try {
        const videoId = parseInt(req.params.id);
        const userId = req.session.userId;

        const video = db.prepare('SELECT * FROM videos WHERE id = ?').get(videoId);
        if (!video) return res.json({ success: false, message: 'Vídeo não encontrado.' });
        if (video.usuario_id !== userId) return res.json({ success: false, message: 'Sem permissão.' });

        db.prepare('DELETE FROM videos_curtidas WHERE video_id = ?').run(videoId);
        db.prepare('DELETE FROM videos_comentarios WHERE video_id = ?').run(videoId);
        db.prepare('DELETE FROM videos WHERE id = ?').run(videoId);

        return res.json({ success: true, message: 'Vídeo removido.' });
    } catch (err) {
        console.error('Erro ao deletar vídeo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== API: Configurações / Privacidade =====

// GET: Obter configurações atuais
app.get('/api/configuracoes', requireLogin, (req, res) => {
    try {
        const user = db.prepare(`SELECT visitas_rastro, escrever_recado, escrever_depoimento,
            enviar_mensagem, mencionar, ver_recado, ver_foto, ver_video, ver_depoimento,
            ver_amigos, ver_comunidades, ver_social, ver_pessoal, ver_profissional,
            ver_online, votos, ver_comunidades_presenca, aparecer_pesquisa, conta_excluir_em
            FROM usuarios WHERE id = ?`).get(req.session.userId);
        return res.json({ success: true, config: user });
    } catch (err) {
        console.error('Erro ao obter configurações:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST: Salvar configurações de privacidade
app.post('/api/configuracoes/salvar', requireLogin, (req, res) => {
    try {
        const camposPermitidos = [
            'visitas_rastro', 'escrever_recado', 'escrever_depoimento',
            'enviar_mensagem', 'mencionar', 'ver_recado', 'ver_foto',
            'ver_video', 'ver_depoimento', 'ver_amigos', 'ver_comunidades',
            'ver_social', 'ver_pessoal', 'ver_profissional', 'ver_online',
            'votos', 'ver_comunidades_presenca', 'aparecer_pesquisa'
        ];

        const sets = [];
        const values = [];
        camposPermitidos.forEach(campo => {
            if (req.body[campo] !== undefined) {
                sets.push(campo + ' = ?');
                values.push(req.body[campo]);
            }
        });

        if (sets.length === 0) {
            return res.json({ success: false, message: 'Nenhum dado para salvar.' });
        }

        values.push(req.session.userId);
        db.prepare('UPDATE usuarios SET ' + sets.join(', ') + ' WHERE id = ?').run(...values);

        // Se ativou modo fantasma, apagar todas as visitas feitas por este usuário
        if (req.body.visitas_rastro === 'nao') {
            db.prepare('DELETE FROM visitas WHERE visitante_id = ?').run(req.session.userId);
        }

        return res.json({ success: true, message: 'Preferências salvas com sucesso!' });
    } catch (err) {
        console.error('Erro ao salvar configurações:', err);
        return res.json({ success: false, message: 'Erro interno ao salvar.' });
    }
});

// POST: Alterar senha
app.post('/api/configuracoes/alterar-senha', requireLogin, (req, res) => {
    try {
        const { senha_atual, nova_senha } = req.body;
        if (!senha_atual || !nova_senha) {
            return res.json({ success: false, message: 'Preencha todos os campos.' });
        }
        if (nova_senha.length < 6) {
            return res.json({ success: false, message: 'A nova senha deve ter no mínimo 6 caracteres.' });
        }

        const user = db.prepare('SELECT senha FROM usuarios WHERE id = ?').get(req.session.userId);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        const senhaCorreta = bcrypt.compareSync(senha_atual, user.senha);
        if (!senhaCorreta) {
            return res.json({ success: false, message: 'Senha atual incorreta!' });
        }

        const novaHash = bcrypt.hashSync(nova_senha, 10);
        db.prepare('UPDATE usuarios SET senha = ? WHERE id = ?').run(novaHash, req.session.userId);

        return res.json({ success: true, message: 'Senha alterada com sucesso!' });
    } catch (err) {
        console.error('Erro ao alterar senha:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST: Excluir conta (agenda exclusão em 24h)
app.post('/api/configuracoes/excluir-conta', requireLogin, (req, res) => {
    try {
        const { senha } = req.body;
        if (!senha) {
            return res.json({ success: false, message: 'Digite sua senha para confirmar.' });
        }

        const user = db.prepare('SELECT senha FROM usuarios WHERE id = ?').get(req.session.userId);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        const senhaCorreta = bcrypt.compareSync(senha, user.senha);
        if (!senhaCorreta) {
            return res.json({ success: false, message: 'Senha incorreta! A exclusão não foi iniciada.' });
        }

        // Agendar exclusão para 24h a partir de agora
        db.prepare(`UPDATE usuarios SET conta_excluir_em = datetime(${agora()}, '+24 hours') WHERE id = ?`).run(req.session.userId);

        const row = db.prepare('SELECT conta_excluir_em FROM usuarios WHERE id = ?').get(req.session.userId);
        return res.json({ success: true, message: 'Exclusão agendada! Sua conta será removida permanentemente em 24 horas.', conta_excluir_em: row.conta_excluir_em });
    } catch (err) {
        console.error('Erro ao excluir conta:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST: Cancelar exclusão de conta
app.post('/api/configuracoes/cancelar-exclusao', requireLogin, (req, res) => {
    try {
        const user = db.prepare('SELECT conta_excluir_em FROM usuarios WHERE id = ?').get(req.session.userId);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });
        if (!user.conta_excluir_em) {
            return res.json({ success: false, message: 'Não há exclusão agendada.' });
        }
        db.prepare('UPDATE usuarios SET conta_excluir_em = NULL WHERE id = ?').run(req.session.userId);
        return res.json({ success: true, message: 'Exclusão cancelada com sucesso!' });
    } catch (err) {
        console.error('Erro ao cancelar exclusão de conta:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== API: AMIZADES =====

// Enviar solicitação de amizade
app.post('/api/amizade/solicitar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { destinatario_id } = req.body;
        const destId = String(destinatario_id);

        if (!destId || destId === userId) {
            return res.json({ success: false, message: 'Solicitação inválida.' });
        }

        // Verificar se o destinatário existe
        const dest = db.prepare('SELECT id FROM usuarios WHERE id = ?').get(destId);
        if (!dest) return res.json({ success: false, message: 'Usuário não encontrado.' });

        // Verificar bloqueio
        if (isBlocked(userId, destId)) {
            return res.json({ success: false, message: 'Não é possível enviar solicitação para este usuário.' });
        }

        // Verificar se já existe amizade ou solicitação (em qualquer direção)
        const existente = db.prepare(`
            SELECT * FROM amizades 
            WHERE (remetente_id = ? AND destinatario_id = ?) 
               OR (remetente_id = ? AND destinatario_id = ?)
        `).get(userId, destId, destId, userId);

        if (existente) {
            if (existente.status === 'aceita') return res.json({ success: false, message: 'Vocês já são amigos.' });
            if (existente.status === 'pendente') return res.json({ success: false, message: 'Solicitação já enviada.' });
        }

        db.prepare(`INSERT INTO amizades (remetente_id, destinatario_id, status, criado_em) VALUES (?, ?, 'pendente', ${agora()})`)
          .run(userId, destId);

        return res.json({ success: true, message: 'Solicitação de amizade enviada!' });
    } catch(err) {
        console.error('Erro ao solicitar amizade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Aceitar solicitação de amizade
app.post('/api/amizade/aceitar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { request_id } = req.body;
        const reqId = parseInt(request_id);

        const solicitacao = db.prepare('SELECT * FROM amizades WHERE id = ? AND destinatario_id = ? AND status = ?').get(reqId, userId, 'pendente');
        if (!solicitacao) return res.json({ success: false, message: 'Solicitação não encontrada.' });

        db.prepare(`UPDATE amizades SET status = 'aceita', aceito_em = ${agora()} WHERE id = ?`).run(reqId);

        return res.json({ success: true, message: 'Amizade aceita!' });
    } catch(err) {
        console.error('Erro ao aceitar amizade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Recusar solicitação de amizade
app.post('/api/amizade/recusar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { request_id } = req.body;
        const reqId = parseInt(request_id);

        const solicitacao = db.prepare('SELECT * FROM amizades WHERE id = ? AND destinatario_id = ? AND status = ?').get(reqId, userId, 'pendente');
        if (!solicitacao) return res.json({ success: false, message: 'Solicitação não encontrada.' });

        db.prepare('DELETE FROM amizades WHERE id = ?').run(reqId);

        return res.json({ success: true, message: 'Solicitação recusada.' });
    } catch(err) {
        console.error('Erro ao recusar amizade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Desfazer amizade (remover amigo)
app.post('/api/amizade/desfazer', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { amigo_id } = req.body;
        const amigoId = String(amigo_id);

        const result = db.prepare(`
            DELETE FROM amizades 
            WHERE status = 'aceita' AND (
                (remetente_id = ? AND destinatario_id = ?) OR 
                (remetente_id = ? AND destinatario_id = ?)
            )
        `).run(userId, amigoId, amigoId, userId);

        if (result.changes === 0) return res.json({ success: false, message: 'Amizade não encontrada.' });

        return res.json({ success: true, message: 'Amizade desfeita.' });
    } catch(err) {
        console.error('Erro ao desfazer amizade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Cancelar solicitação enviada
app.post('/api/amizade/cancelar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { destinatario_id } = req.body;
        const destId = String(destinatario_id);

        db.prepare(`DELETE FROM amizades WHERE remetente_id = ? AND destinatario_id = ? AND status = 'pendente'`).run(userId, destId);

        return res.json({ success: true, message: 'Solicitação cancelada.' });
    } catch(err) {
        console.error('Erro ao cancelar solicitação:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Buscar amigos por nome (autocomplete para @menções)
app.get('/api/amigos/buscar', requireLogin, (req, res) => {
    try {
        const meId = req.session.userId;
        const q = (req.query.q || '').trim().toLowerCase();
        if (!q || q.length < 1) return res.json({ success: true, amigos: [] });

        const amigos = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil, u.sexo
            FROM amizades a
            JOIN usuarios u ON (
                CASE 
                    WHEN a.remetente_id = ? THEN u.id = a.destinatario_id
                    WHEN a.destinatario_id = ? THEN u.id = a.remetente_id
                END
            )
            WHERE a.status = 'aceita' AND (a.remetente_id = ? OR a.destinatario_id = ?)
              AND LOWER(u.nome) LIKE ?
            ORDER BY u.nome ASC
            LIMIT 8
        `).all(meId, meId, meId, meId, '%' + q + '%');

        return res.json({ success: true, amigos });
    } catch(err) {
        console.error('Erro ao buscar amigos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar amigos de um usuário
app.get('/api/amigos/:uid', requireLogin, (req, res) => {
    try {
        const uid = req.params.uid;
        const meId = req.session.userId;
        if (!uid) return res.json({ success: false, message: 'ID inválido.' });

        const amigos = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil, u.sexo, u.cidade, u.estado, u.status_texto,
                   a.aceito_em,
                   COALESCE(av.estrelas, 0) AS estrelas
            FROM amizades a
            JOIN usuarios u ON (
                CASE 
                    WHEN a.remetente_id = ? THEN u.id = a.destinatario_id
                    WHEN a.destinatario_id = ? THEN u.id = a.remetente_id
                END
            )
            LEFT JOIN avaliacoes_amigos av ON av.avaliador_id = ? AND av.avaliado_id = u.id
            WHERE a.status = 'aceita' AND (a.remetente_id = ? OR a.destinatario_id = ?)
              AND u.id NOT IN ${blockedIdsSubquery(meId)}
            ORDER BY a.aceito_em DESC
        `).all(uid, uid, meId, uid, uid);

        // Check if each friend is also my friend (for "amigos em comum" filter)
        const isOwn = uid === meId;
        if (!isOwn) {
            const myFriendIds = new Set(
                db.prepare(`SELECT CASE WHEN remetente_id = ? THEN destinatario_id ELSE remetente_id END AS fid FROM amizades WHERE status = 'aceita' AND (remetente_id = ? OR destinatario_id = ?)`).all(meId, meId, meId).map(r => r.fid)
            );
            amigos.forEach(a => { a.em_comum = myFriendIds.has(a.id) ? 1 : 0; });
        }

        return res.json({ success: true, amigos: amigos, total: amigos.length, isOwn: isOwn });
    } catch(err) {
        console.error('Erro ao listar amigos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Avaliar amigo (estrelas)
app.post('/api/amigos/avaliar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { amigo_id, estrelas } = req.body;
        const amigoId = String(amigo_id);
        const stars = parseInt(estrelas);
        if (!amigoId || stars < 1 || stars > 5) return res.json({ success: false, message: 'Dados inválidos.' });

        // Verify they are friends
        const friendship = db.prepare(`SELECT id FROM amizades WHERE status = 'aceita' AND ((remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?))`).get(userId, amigoId, amigoId, userId);
        if (!friendship) return res.json({ success: false, message: 'Vocês não são amigos.' });

        db.prepare(`INSERT INTO avaliacoes_amigos (avaliador_id, avaliado_id, estrelas) VALUES (?, ?, ?) ON CONFLICT(avaliador_id, avaliado_id) DO UPDATE SET estrelas = ?`).run(userId, amigoId, stars, stars);

        return res.json({ success: true, message: 'Avaliação salva!' });
    } catch(err) {
        console.error('Erro ao avaliar amigo:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== FÃS =====
// Virar fã / deixar de ser fã (toggle)
app.post('/api/fas/toggle', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { usuario_id } = req.body;
        const targetId = String(usuario_id);
        if (!targetId) return res.json({ success: false, message: 'ID do usuário obrigatório.' });
        if (targetId === userId) return res.json({ success: false, message: 'Você não pode ser fã de si mesmo.' });

        // Check if already a fan
        const existing = db.prepare('SELECT id FROM fas WHERE fa_id = ? AND usuario_id = ?').get(userId, targetId);
        if (existing) {
            db.prepare('DELETE FROM fas WHERE id = ?').run(existing.id);
            const count = db.prepare('SELECT COUNT(*) as total FROM fas WHERE usuario_id = ?').get(targetId).total;
            return res.json({ success: true, isFan: false, count, message: 'Você deixou de ser fã.' });
        } else {
            db.prepare('INSERT INTO fas (fa_id, usuario_id) VALUES (?, ?)').run(userId, targetId);
            const count = db.prepare('SELECT COUNT(*) as total FROM fas WHERE usuario_id = ?').get(targetId).total;
            return res.json({ success: true, isFan: true, count, message: 'Você agora é fã!' });
        }
    } catch(err) {
        console.error('Erro ao toggle fã:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Obter contagem de fãs e se o usuário logado é fã
app.get('/api/fas/:uid', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const targetId = String(req.params.uid);
        const count = db.prepare('SELECT COUNT(*) as total FROM fas WHERE usuario_id = ?').get(targetId).total;
        const isFan = !!db.prepare('SELECT id FROM fas WHERE fa_id = ? AND usuario_id = ?').get(userId, targetId);
        return res.json({ success: true, count, isFan });
    } catch(err) {
        console.error('Erro ao buscar fãs:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== AVALIAÇÕES DE PERFIL (confiável, legal, sexy) =====
// Avaliar perfil
app.post('/api/avaliacoes-perfil', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { usuario_id, categoria, nota } = req.body;
        const targetId = String(usuario_id);
        const cat = String(categoria);
        const rating = parseInt(nota);

        if (!targetId) return res.json({ success: false, message: 'ID do usuário obrigatório.' });
        if (targetId === userId) return res.json({ success: false, message: 'Você não pode avaliar a si mesmo.' });
        if (!['confiavel', 'legal', 'sexy'].includes(cat)) return res.json({ success: false, message: 'Categoria inválida.' });
        if (rating < 1 || rating > 5 || isNaN(rating)) return res.json({ success: false, message: 'Nota deve ser de 1 a 5.' });

        db.prepare(`INSERT INTO avaliacoes_perfil (avaliador_id, avaliado_id, categoria, nota)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(avaliador_id, avaliado_id, categoria) DO UPDATE SET nota = ?, criado_em = datetime('now','-3 hours')`
        ).run(userId, targetId, cat, rating, rating);

        // Return updated average
        const avg = db.prepare('SELECT AVG(nota) as media, COUNT(*) as total FROM avaliacoes_perfil WHERE avaliado_id = ? AND categoria = ?').get(targetId, cat);
        const myRating = db.prepare('SELECT nota FROM avaliacoes_perfil WHERE avaliador_id = ? AND avaliado_id = ? AND categoria = ?').get(userId, targetId, cat);

        return res.json({
            success: true,
            media: avg.media ? Math.round(avg.media * 10) / 10 : 0,
            total: avg.total,
            minhaNota: myRating ? myRating.nota : 0,
            message: 'Avaliação salva!'
        });
    } catch(err) {
        console.error('Erro ao avaliar perfil:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Obter médias de avaliação de um perfil
app.get('/api/avaliacoes-perfil/:uid', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const targetId = String(req.params.uid);
        const categorias = ['confiavel', 'legal', 'sexy'];
        const result = {};

        for (const cat of categorias) {
            const avg = db.prepare('SELECT AVG(nota) as media, COUNT(*) as total FROM avaliacoes_perfil WHERE avaliado_id = ? AND categoria = ?').get(targetId, cat);
            const myRating = db.prepare('SELECT nota FROM avaliacoes_perfil WHERE avaliador_id = ? AND avaliado_id = ? AND categoria = ?').get(userId, targetId, cat);
            result[cat] = {
                media: avg.media ? Math.round(avg.media * 10) / 10 : 0,
                total: avg.total || 0,
                minhaNota: myRating ? myRating.nota : 0
            };
        }

        return res.json({ success: true, avaliacoes: result });
    } catch(err) {
        console.error('Erro ao buscar avaliações:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar solicitações pendentes (recebidas)
app.get('/api/amizade/pendentes', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;

        const pendentes = db.prepare(`
            SELECT a.id AS request_id, a.remetente_id, a.criado_em,
                   u.nome, u.foto_perfil, u.sexo, u.cidade, u.estado
            FROM amizades a
            JOIN usuarios u ON u.id = a.remetente_id
            WHERE a.destinatario_id = ? AND a.status = 'pendente'
              AND u.id NOT IN ${blockedIdsSubquery(userId)}
            ORDER BY a.criado_em DESC
        `).all(userId);

        return res.json({ success: true, pendentes: pendentes, total: pendentes.length });
    } catch(err) {
        console.error('Erro ao listar solicitações:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Verificar status de amizade com outro usuário
app.get('/api/amizade/status/:uid', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const uid = req.params.uid;
        if (!uid || uid === userId) return res.json({ success: true, status: 'self' });

        const amizade = db.prepare(`
            SELECT * FROM amizades 
            WHERE (remetente_id = ? AND destinatario_id = ?) 
               OR (remetente_id = ? AND destinatario_id = ?)
        `).get(userId, uid, uid, userId);

        if (!amizade) return res.json({ success: true, status: 'none' });
        if (amizade.status === 'aceita') return res.json({ success: true, status: 'amigos' });
        if (amizade.status === 'pendente' && amizade.remetente_id === userId) {
            return res.json({ success: true, status: 'enviada' });
        }
        if (amizade.status === 'pendente' && amizade.destinatario_id === userId) {
            return res.json({ success: true, status: 'recebida', request_id: amizade.id });
        }

        return res.json({ success: true, status: 'none' });
    } catch(err) {
        console.error('Erro ao verificar amizade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== API: Bloqueios =====

// Bloquear usuário
app.post('/api/bloquear', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { bloqueado_id } = req.body;
        const bloqId = String(bloqueado_id);

        if (!bloqId || bloqId === userId) {
            return res.json({ success: false, message: 'Operação inválida.' });
        }

        const dest = db.prepare('SELECT id FROM usuarios WHERE id = ?').get(bloqId);
        if (!dest) return res.json({ success: false, message: 'Usuário não encontrado.' });

        // Verificar se já está bloqueado
        const existing = db.prepare('SELECT id FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?').get(userId, bloqId);
        if (existing) return res.json({ success: false, message: 'Usuário já está bloqueado.' });

        // Inserir bloqueio
        db.prepare(`INSERT INTO bloqueios (bloqueador_id, bloqueado_id, criado_em) VALUES (?, ?, ${agora()})`).run(userId, bloqId);

        // Desfazer amizade se existir (em qualquer direção)
        db.prepare(`DELETE FROM amizades WHERE (remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?)`).run(userId, bloqId, bloqId, userId);

        return res.json({ success: true, message: 'Usuário bloqueado com sucesso.' });
    } catch(err) {
        console.error('Erro ao bloquear:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Desbloquear usuário
app.post('/api/desbloquear', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { bloqueado_id } = req.body;
        const bloqId = String(bloqueado_id);

        if (!bloqId) return res.json({ success: false, message: 'Operação inválida.' });

        const result = db.prepare('DELETE FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?').run(userId, bloqId);
        if (result.changes === 0) return res.json({ success: false, message: 'Usuário não está bloqueado.' });

        return res.json({ success: true, message: 'Usuário desbloqueado com sucesso.' });
    } catch(err) {
        console.error('Erro ao desbloquear:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar bloqueados
app.get('/api/bloqueados', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;

        const bloqueados = db.prepare(`
            SELECT b.id, b.criado_em, u.id AS user_id, u.nome, u.foto_perfil, u.sexo
            FROM bloqueios b
            JOIN usuarios u ON u.id = b.bloqueado_id
            WHERE b.bloqueador_id = ?
            ORDER BY b.criado_em DESC
        `).all(userId);

        return res.json({ success: true, bloqueados, total: bloqueados.length });
    } catch(err) {
        console.error('Erro ao listar bloqueados:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// Verificar se está bloqueado
app.get('/api/bloqueio/status/:uid', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const uid = req.params.uid;
        if (!uid) return res.json({ success: true, blocked: false });

        // Eu bloqueei ele?
        const euBloqueei = db.prepare('SELECT id FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?').get(userId, uid);
        // Ele me bloqueou?
        const eleBloqueou = db.prepare('SELECT id FROM bloqueios WHERE bloqueador_id = ? AND bloqueado_id = ?').get(uid, userId);

        return res.json({
            success: true,
            blocked: !!(euBloqueei || eleBloqueou),
            i_blocked: !!euBloqueei,
            blocked_me: !!eleBloqueou
        });
    } catch(err) {
        console.error('Erro ao verificar bloqueio:', err);
        return res.json({ success: false });
    }
});

// Logout via GET (para o link direto)
// ===== ADMIN PANEL =====

// Middleware: requer admin
function requireAdmin(req, res, next) {
    if (!req.session || !req.session.userId) {
        if (req.path.startsWith('/api/')) return res.status(401).json({ success: false, message: 'Não autorizado.' });
        return res.redirect('/index.php');
    }
    const user = db.prepare('SELECT is_admin FROM usuarios WHERE id = ?').get(req.session.userId);
    if (!user || !user.is_admin) {
        if (req.path.startsWith('/api/')) return res.status(403).json({ success: false, message: 'Acesso negado.' });
        return res.redirect('/profile.php');
    }
    next();
}

// Página admin
app.get('/admin.php', requireAdmin, (req, res) => {
    sendPhp(res, 'admin.php');
});

// API: Dashboard stats
app.get('/api/admin/stats', requireAdmin, (req, res) => {
    try {
        const totalUsuarios = db.prepare('SELECT COUNT(*) as c FROM usuarios').get().c;
        const hojeBR = agora().split(' ')[0];
        const novosHoje = db.prepare("SELECT COUNT(*) as c FROM usuarios WHERE criado_em LIKE ?").get(hojeBR + '%').c;
        const totalRecados = db.prepare('SELECT COUNT(*) as c FROM recados').get().c;
        const totalMensagens = db.prepare('SELECT COUNT(*) as c FROM mensagens').get().c;
        const totalDepoimentos = db.prepare('SELECT COUNT(*) as c FROM depoimentos').get().c;
        const totalAmizades = db.prepare("SELECT COUNT(*) as c FROM amizades WHERE status = 'aceita'").get().c;
        const totalFotos = db.prepare('SELECT COUNT(*) as c FROM fotos').get().c;
        const totalVideos = db.prepare('SELECT COUNT(*) as c FROM videos').get().c;
        const totalVisitas = db.prepare('SELECT COUNT(*) as c FROM visitas').get().c;
        const convitesUsados = db.prepare('SELECT COUNT(*) as c FROM convites WHERE usado = 1').get().c;
        const convitesDisponiveis = db.prepare('SELECT COUNT(*) as c FROM convites WHERE usado = 0').get().c;
        const totalConvites = convitesUsados + convitesDisponiveis;
        const totalDenuncias = db.prepare('SELECT COUNT(*) as c FROM denuncias').get().c;
        const denunciasPendentes = db.prepare("SELECT COUNT(*) as c FROM denuncias WHERE status = 'pendente'").get().c;
        const totalSugestoes = db.prepare('SELECT COUNT(*) as c FROM sugestoes').get().c;
        const sugestoesNovas = db.prepare("SELECT COUNT(*) as c FROM sugestoes WHERE status = 'nova'").get().c;
        const totalBugs = db.prepare('SELECT COUNT(*) as c FROM bugs').get().c;
        const bugsNovos = db.prepare("SELECT COUNT(*) as c FROM bugs WHERE status = 'novo'").get().c;
        const totalDenunciasComunidades = db.prepare('SELECT COUNT(*) as c FROM denuncias_comunidades').get().c;
        const denunciasComunidadesPendentes = db.prepare("SELECT COUNT(*) as c FROM denuncias_comunidades WHERE status = 'pendente'").get().c;

        // Usuários recentes (últimos 10)
        const recentes = db.prepare('SELECT id, nome, email, criado_em, ultimo_acesso FROM usuarios ORDER BY id DESC LIMIT 10').all();

        res.json({
            success: true,
            stats: {
                totalUsuarios, novosHoje, totalRecados, totalMensagens,
                totalDepoimentos, totalAmizades, totalFotos, totalVideos,
                totalVisitas, totalConvites, convitesUsados, convitesDisponiveis,
                totalDenuncias, denunciasPendentes,
                totalSugestoes, sugestoesNovas, totalBugs, bugsNovos,
                totalDenunciasComunidades, denunciasComunidadesPendentes
            },
            recentes
        });
    } catch(err) {
        console.error('Erro admin stats:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Listar usuários (com busca e paginação)
app.get('/api/admin/usuarios', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;
        const busca = req.query.busca || '';

        let where = '';
        let params = [];
        if (busca) {
            where = "WHERE nome LIKE ? OR email LIKE ? OR id = ?";
            params = ['%' + busca + '%', '%' + busca + '%', busca];
        }

        const total = db.prepare('SELECT COUNT(*) as c FROM usuarios ' + where).get(...params).c;
        const usuarios = db.prepare(
            'SELECT id, nome, email, sexo, foto_perfil, criado_em, ultimo_acesso, is_admin, conta_excluir_em, banido, banido_permanente, banido_ate, banido_motivo, banido_em FROM usuarios ' +
            where + ' ORDER BY id DESC LIMIT ? OFFSET ?'
        ).all(...params, limit, offset);

        res.json({ success: true, usuarios, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin usuarios:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Detalhes de um usuário
app.get('/api/admin/usuario/:id', requireAdmin, (req, res) => {
    try {
        const uid = req.params.id;
        const user = db.prepare('SELECT * FROM usuarios WHERE id = ?').get(uid);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        const totalAmigos = db.prepare("SELECT COUNT(*) as c FROM amizades WHERE status = 'aceita' AND (remetente_id = ? OR destinatario_id = ?)").get(uid, uid).c;
        const totalRecados = db.prepare('SELECT COUNT(*) as c FROM recados WHERE destinatario_id = ?').get(uid).c;
        const totalFotos = db.prepare('SELECT COUNT(*) as c FROM fotos WHERE usuario_id = ?').get(uid).c;
        const totalVideos = db.prepare('SELECT COUNT(*) as c FROM videos WHERE usuario_id = ?').get(uid).c;
        const convitesGerados = db.prepare('SELECT COUNT(*) as c FROM convites WHERE criado_por = ?').get(uid).c;

        // Remove senha do retorno
        delete user.senha;

        res.json({ success: true, user, totalAmigos, totalRecados, totalFotos, totalVideos, convitesGerados });
    } catch(err) {
        console.error('Erro admin usuario detalhe:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Editar usuário (admin)
app.post('/api/admin/usuario/editar', requireAdmin, (req, res) => {
    try {
        const { id, nome, email, sexo, is_admin } = req.body;
        if (!id) return res.json({ success: false, message: 'ID obrigatório.' });

        const user = db.prepare('SELECT * FROM usuarios WHERE id = ?').get(id);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        db.prepare('UPDATE usuarios SET nome = ?, email = ?, sexo = ?, is_admin = ? WHERE id = ?')
          .run(nome || user.nome, email || user.email, sexo || user.sexo, is_admin !== undefined ? is_admin : user.is_admin, id);

        res.json({ success: true, message: 'Usuário atualizado.' });
    } catch(err) {
        console.error('Erro admin editar usuario:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Excluir usuário (admin)
app.post('/api/admin/usuario/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        if (!id) return res.json({ success: false, message: 'ID obrigatório.' });
        if (id === req.session.userId) return res.json({ success: false, message: 'Não é possível excluir a si mesmo.' });

        const user = db.prepare('SELECT * FROM usuarios WHERE id = ?').get(id);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        // Excluir dados relacionados
        db.prepare('DELETE FROM recados WHERE remetente_id = ? OR destinatario_id = ?').run(id, id);
        db.prepare('DELETE FROM mensagens WHERE remetente_id = ? OR destinatario_id = ?').run(id, id);
        db.prepare('DELETE FROM depoimentos WHERE remetente_id = ? OR destinatario_id = ?').run(id, id);
        db.prepare('DELETE FROM amizades WHERE remetente_id = ? OR destinatario_id = ?').run(id, id);
        db.prepare('DELETE FROM visitas WHERE visitante_id = ? OR visitado_id = ?').run(id, id);
        db.prepare('DELETE FROM fotos_comentarios WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM fotos_curtidas WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM fotos WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM videos_comentarios WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM videos_curtidas WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM videos WHERE usuario_id = ?').run(id);
        db.prepare('DELETE FROM usuarios WHERE id = ?').run(id);

        res.json({ success: true, message: 'Usuário excluído com sucesso.' });
    } catch(err) {
        console.error('Erro admin excluir usuario:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Resetar senha (admin)
app.post('/api/admin/usuario/resetar-senha', requireAdmin, async (req, res) => {
    try {
        const { id, novaSenha } = req.body;
        if (!id || !novaSenha) return res.json({ success: false, message: 'ID e nova senha obrigatórios.' });

        const hash = await bcrypt.hash(novaSenha, 10);
        db.prepare('UPDATE usuarios SET senha = ? WHERE id = ?').run(hash, id);

        res.json({ success: true, message: 'Senha resetada com sucesso.' });
    } catch(err) {
        console.error('Erro admin resetar senha:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Banir usuário (admin)
app.post('/api/admin/usuario/banir', requireAdmin, (req, res) => {
    try {
        const { id, tipo, dias, motivo } = req.body;
        if (!id) return res.json({ success: false, message: 'ID obrigatório.' });
        if (id === req.session.userId) return res.json({ success: false, message: 'Não é possível banir a si mesmo.' });

        const user = db.prepare('SELECT * FROM usuarios WHERE id = ?').get(id);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });
        if (user.is_admin) return res.json({ success: false, message: 'Não é possível banir um administrador.' });

        if (tipo === 'permanente') {
            db.prepare(`UPDATE usuarios SET banido = 1, banido_permanente = 1, banido_ate = NULL, banido_motivo = ?, banido_por = ?, banido_em = ${agora()} WHERE id = ?`)
              .run(motivo || null, req.session.userId, id);
        } else {
            const numDias = parseInt(dias) || 1;
            db.prepare(`UPDATE usuarios SET banido = 1, banido_permanente = 0, banido_ate = datetime('now','-3 hours','+${numDias} days'), banido_motivo = ?, banido_por = ?, banido_em = ${agora()} WHERE id = ?`)
              .run(motivo || null, req.session.userId, id);
        }

        res.json({ success: true, message: tipo === 'permanente' ? 'Usuário banido permanentemente.' : 'Usuário banido por ' + (parseInt(dias) || 1) + ' dia(s).' });
    } catch(err) {
        console.error('Erro admin banir usuario:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Desbanir usuário (admin)
app.post('/api/admin/usuario/desbanir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        if (!id) return res.json({ success: false, message: 'ID obrigatório.' });

        db.prepare('UPDATE usuarios SET banido = 0, banido_permanente = 0, banido_ate = NULL, banido_motivo = NULL, banido_por = NULL, banido_em = NULL WHERE id = ?').run(id);

        res.json({ success: true, message: 'Usuário desbanido com sucesso.' });
    } catch(err) {
        console.error('Erro admin desbanir usuario:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Listar convites
app.get('/api/admin/convites', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;
        const filtro = req.query.filtro || 'todos'; // todos, usados, disponiveis

        let where = '';
        if (filtro === 'usados') where = 'WHERE c.usado = 1';
        else if (filtro === 'disponiveis') where = 'WHERE c.usado = 0';

        const total = db.prepare('SELECT COUNT(*) as cnt FROM convites c ' + where).get().cnt;
        const convites = db.prepare(`
            SELECT c.*, 
                   criador.nome as criador_nome, 
                   usuario.nome as usuario_nome
            FROM convites c
            LEFT JOIN usuarios criador ON criador.id = c.criado_por
            LEFT JOIN usuarios usuario ON usuario.id = c.usado_por
            ${where}
            ORDER BY c.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({ success: true, convites, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin convites:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Gerar convites (admin)
app.post('/api/admin/convites/gerar', requireAdmin, (req, res) => {
    try {
        const quantidade = Math.min(parseInt(req.body.quantidade) || 1, 50);
        const tokens = [];

        for (let i = 0; i < quantidade; i++) {
            const token = Math.random().toString(36).substring(2, 8).toUpperCase();
            try {
                db.prepare('INSERT INTO convites (token, criado_por, criado_em) VALUES (?, ?, ?)').run(token, req.session.userId, agora());
                tokens.push(token);
            } catch(e) { /* token duplicado, ignora */ }
        }

        res.json({ success: true, message: tokens.length + ' convites gerados.', tokens });
    } catch(err) {
        console.error('Erro admin gerar convites:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Excluir convite
app.post('/api/admin/convite/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        db.prepare('DELETE FROM convites WHERE id = ?').run(id);
        res.json({ success: true, message: 'Convite excluído.' });
    } catch(err) {
        console.error('Erro admin excluir convite:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - listar recados (com paginação)
app.get('/api/admin/recados', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;

        const total = db.prepare('SELECT COUNT(*) as c FROM recados').get().c;
        const recados = db.prepare(`
            SELECT r.*, 
                   rem.nome as remetente_nome, rem.foto_perfil as remetente_foto,
                   dest.nome as destinatario_nome
            FROM recados r
            JOIN usuarios rem ON rem.id = r.remetente_id
            JOIN usuarios dest ON dest.id = r.destinatario_id
            ORDER BY r.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({ success: true, recados, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin recados:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - excluir recado
app.post('/api/admin/recado/excluir', requireAdmin, (req, res) => {
    try {
        db.prepare('DELETE FROM recados WHERE id = ?').run(req.body.id);
        res.json({ success: true, message: 'Recado excluído.' });
    } catch(err) {
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - listar depoimentos
app.get('/api/admin/depoimentos', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;

        const total = db.prepare('SELECT COUNT(*) as c FROM depoimentos').get().c;
        const depoimentos = db.prepare(`
            SELECT d.*, 
                   rem.nome as remetente_nome, rem.foto_perfil as remetente_foto,
                   dest.nome as destinatario_nome
            FROM depoimentos d
            JOIN usuarios rem ON rem.id = d.remetente_id
            JOIN usuarios dest ON dest.id = d.destinatario_id
            ORDER BY d.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({ success: true, depoimentos, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin depoimentos:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - excluir depoimento
app.post('/api/admin/depoimento/excluir', requireAdmin, (req, res) => {
    try {
        db.prepare('DELETE FROM depoimentos WHERE id = ?').run(req.body.id);
        res.json({ success: true, message: 'Depoimento excluído.' });
    } catch(err) {
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - listar mensagens
app.get('/api/admin/mensagens', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;

        const total = db.prepare('SELECT COUNT(*) as c FROM mensagens').get().c;
        const mensagens = db.prepare(`
            SELECT m.*, 
                   rem.nome as remetente_nome,
                   dest.nome as destinatario_nome
            FROM mensagens m
            JOIN usuarios rem ON rem.id = m.remetente_id
            JOIN usuarios dest ON dest.id = m.destinatario_id
            ORDER BY m.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({ success: true, mensagens, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin mensagens:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Moderação - excluir mensagem
app.post('/api/admin/mensagem/excluir', requireAdmin, (req, res) => {
    try {
        db.prepare('DELETE FROM mensagens WHERE id = ?').run(req.body.id);
        res.json({ success: true, message: 'Mensagem excluída.' });
    } catch(err) {
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - detalhes completos de uma denúncia (perfis, mensagens, recados, depoimentos entre os dois)
app.get('/api/admin/denuncia/:id', requireAdmin, (req, res) => {
    try {
        const dId = parseInt(req.params.id);
        const denuncia = db.prepare(`
            SELECT d.*,
                   den.nome as denunciante_nome, den.foto_perfil as denunciante_foto, den.email as denunciante_email,
                   den.sexo as denunciante_sexo, den.criado_em as denunciante_criado, den.ultimo_acesso as denunciante_acesso,
                   den.cidade as denunciante_cidade, den.estado as denunciante_estado, den.quem_sou_eu as denunciante_bio,
                   alv.nome as denunciado_nome, alv.foto_perfil as denunciado_foto, alv.email as denunciado_email,
                   alv.sexo as denunciado_sexo, alv.criado_em as denunciado_criado, alv.ultimo_acesso as denunciado_acesso,
                   alv.cidade as denunciado_cidade, alv.estado as denunciado_estado, alv.quem_sou_eu as denunciado_bio,
                   adm.nome as resolvido_por_nome
            FROM denuncias d
            JOIN usuarios den ON den.id = d.denunciante_id
            JOIN usuarios alv ON alv.id = d.denunciado_id
            LEFT JOIN usuarios adm ON adm.id = d.resolvido_por
            WHERE d.id = ?
        `).get(dId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        // Mensagens entre os dois (últimas 30)
        const mensagens = db.prepare(`
            SELECT m.id, m.remetente_id, m.destinatario_id, m.assunto, m.mensagem, m.criado_em,
                   r.nome as remetente_nome
            FROM mensagens m
            JOIN usuarios r ON r.id = m.remetente_id
            WHERE (m.remetente_id = ? AND m.destinatario_id = ?)
               OR (m.remetente_id = ? AND m.destinatario_id = ?)
            ORDER BY m.criado_em DESC LIMIT 30
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Recados entre os dois (últimos 30)
        const recados = db.prepare(`
            SELECT rec.id, rec.remetente_id, rec.destinatario_id, rec.mensagem, rec.criado_em,
                   r.nome as remetente_nome
            FROM recados rec
            JOIN usuarios r ON r.id = rec.remetente_id
            WHERE (rec.remetente_id = ? AND rec.destinatario_id = ?)
               OR (rec.remetente_id = ? AND rec.destinatario_id = ?)
            ORDER BY rec.criado_em DESC LIMIT 30
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Depoimentos entre os dois (últimos 20)
        const depoimentos = db.prepare(`
            SELECT dep.id, dep.remetente_id, dep.destinatario_id, dep.mensagem, dep.aprovado, dep.criado_em,
                   r.nome as remetente_nome
            FROM depoimentos dep
            JOIN usuarios r ON r.id = dep.remetente_id
            WHERE (dep.remetente_id = ? AND dep.destinatario_id = ?)
               OR (dep.remetente_id = ? AND dep.destinatario_id = ?)
            ORDER BY dep.criado_em DESC LIMIT 20
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Outras denúncias envolvendo o denunciado (como denunciado)
        const outrasDenuncias = db.prepare(`
            SELECT d.id, d.denunciante_id, d.motivo, d.status, d.criado_em, u.nome as denunciante_nome
            FROM denuncias d
            JOIN usuarios u ON u.id = d.denunciante_id
            WHERE d.denunciado_id = ? AND d.id != ?
            ORDER BY d.criado_em DESC LIMIT 10
        `).all(denuncia.denunciado_id, dId);

        // Contagens do denunciado
        const denunciadoStats = {
            totalAmigos: db.prepare("SELECT COUNT(*) as c FROM amizades WHERE status = 'aceita' AND (remetente_id = ? OR destinatario_id = ?)").get(denuncia.denunciado_id, denuncia.denunciado_id).c,
            totalRecados: db.prepare("SELECT COUNT(*) as c FROM recados WHERE destinatario_id = ?").get(denuncia.denunciado_id).c,
            totalFotos: db.prepare("SELECT COUNT(*) as c FROM fotos WHERE usuario_id = ?").get(denuncia.denunciado_id).c,
            totalDenuncias: db.prepare("SELECT COUNT(*) as c FROM denuncias WHERE denunciado_id = ?").get(denuncia.denunciado_id).c
        };

        // Contagens do denunciante
        const denuncianteStats = {
            totalAmigos: db.prepare("SELECT COUNT(*) as c FROM amizades WHERE status = 'aceita' AND (remetente_id = ? OR destinatario_id = ?)").get(denuncia.denunciante_id, denuncia.denunciante_id).c,
            totalDenuncias: db.prepare("SELECT COUNT(*) as c FROM denuncias WHERE denunciante_id = ?").get(denuncia.denunciante_id).c
        };

        res.json({
            success: true,
            denuncia,
            mensagens,
            recados,
            depoimentos,
            outrasDenuncias,
            denunciadoStats,
            denuncianteStats
        });
    } catch(err) {
        console.error('Erro admin denuncia detalhe:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Denunciar usuário (endpoint público, requireLogin)
app.post('/api/denunciar', requireLogin, (req, res) => {
    try {
        const { denunciado_id, motivo } = req.body;
        const denunciante_id = req.session.userId;
        if (!denunciado_id || !motivo || !motivo.trim()) {
            return res.json({ success: false, message: 'Preencha o motivo da denúncia.' });
        }
        if (String(denunciado_id) === String(denunciante_id)) {
            return res.json({ success: false, message: 'Você não pode denunciar a si mesmo.' });
        }
        // Verificar se já existe denúncia pendente deste user para este alvo
        const existente = db.prepare('SELECT id FROM denuncias WHERE denunciante_id = ? AND denunciado_id = ? AND status = ?').get(denunciante_id, denunciado_id, 'pendente');
        if (existente) {
            return res.json({ success: false, message: 'Você já possui uma denúncia pendente para este usuário.' });
        }
        const result = db.prepare('INSERT INTO denuncias (denunciante_id, denunciado_id, motivo) VALUES (?, ?, ?)').run(denunciante_id, denunciado_id, motivo.trim());
        res.json({ success: true, message: 'Denúncia enviada com sucesso. Nossa equipe irá analisar.', denunciaId: result.lastInsertRowid });
    } catch(err) {
        console.error('Erro ao denunciar:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Minhas denúncias
app.get('/api/minhas-denuncias', requireLogin, (req, res) => {
    try {
        const denuncias = db.prepare(`
            SELECT d.*, u.nome as denunciado_nome, u.foto_perfil as denunciado_foto,
                   (SELECT COUNT(*) FROM denuncia_mensagens dm WHERE dm.denuncia_id = d.id AND dm.is_admin = 1 AND dm.lida = 0) as mensagens_nao_lidas,
                   (SELECT COUNT(*) FROM denuncia_mensagens dm WHERE dm.denuncia_id = d.id) as total_mensagens,
                   (SELECT dm2.mensagem FROM denuncia_mensagens dm2 WHERE dm2.denuncia_id = d.id AND dm2.is_admin = 1 ORDER BY dm2.criado_em DESC LIMIT 1) as ultima_resposta_equipe
            FROM denuncias d
            JOIN usuarios u ON u.id = d.denunciado_id
            WHERE d.denunciante_id = ?
            ORDER BY d.id DESC
        `).all(req.session.userId);
        res.json({ success: true, denuncias });
    } catch(err) {
        console.error('Erro minhas denúncias:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - listar denúncias
app.get('/api/admin/denuncias', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;
        const filtro = req.query.filtro || 'todos';

        let where = '';
        if (filtro === 'pendente') where = "WHERE d.status = 'pendente'";
        else if (filtro === 'analisando') where = "WHERE d.status = 'analisando'";
        else if (filtro === 'respondido') where = "WHERE d.status = 'respondido'";
        else if (filtro === 'resolvida') where = "WHERE d.status = 'resolvida'";
        else if (filtro === 'rejeitada') where = "WHERE d.status = 'rejeitada'";

        const total = db.prepare('SELECT COUNT(*) as c FROM denuncias d ' + where).get().c;
        const denuncias = db.prepare(`
            SELECT d.*,
                   den.nome as denunciante_nome,
                   alv.nome as denunciado_nome
            FROM denuncias d
            JOIN usuarios den ON den.id = d.denunciante_id
            JOIN usuarios alv ON alv.id = d.denunciado_id
            ${where}
            ORDER BY d.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({ success: true, denuncias, total, page, totalPages: Math.ceil(total / limit) });
    } catch(err) {
        console.error('Erro admin denúncias:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - atualizar status da denúncia
app.post('/api/admin/denuncia/status', requireAdmin, (req, res) => {
    try {
        const { id, status, resposta_admin } = req.body;
        if (!['pendente', 'analisando', 'respondido', 'resolvida', 'rejeitada'].includes(status)) {
            return res.json({ success: false, message: 'Status inválido.' });
        }
        const updates = ['status = ?'];
        const params = [status];
        if (resposta_admin !== undefined) {
            updates.push('resposta_admin = ?');
            params.push(resposta_admin);
        }
        if (status === 'resolvida' || status === 'rejeitada') {
            updates.push('resolvido_por = ?');
            updates.push("resolvido_em = datetime('now','-3 hours')");
            params.push(req.session.userId);
        }
        params.push(id);
        db.prepare('UPDATE denuncias SET ' + updates.join(', ') + ' WHERE id = ?').run(...params);
        res.json({ success: true, message: 'Denúncia atualizada.' });
    } catch(err) {
        console.error('Erro atualizar denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - excluir denúncia
app.post('/api/admin/denuncia/excluir', requireAdmin, (req, res) => {
    try {
        db.prepare('DELETE FROM denuncia_mensagens WHERE denuncia_id = ?').run(req.body.id);
        db.prepare('DELETE FROM denuncias WHERE id = ?').run(req.body.id);
        res.json({ success: true, message: 'Denúncia excluída.' });
    } catch(err) {
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - detalhe completo da denúncia
app.get('/api/admin/denuncia-detalhe/:id', requireAdmin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare(`
            SELECT d.*,
                   den.nome as denunciante_nome, den.foto_perfil as denunciante_foto, den.email as denunciante_email,
                   den.cidade as denunciante_cidade, den.estado as denunciante_estado, den.criado_em as denunciante_criado,
                   den.ultimo_acesso as denunciante_acesso, den.quem_sou_eu as denunciante_bio,
                   alv.nome as denunciado_nome, alv.foto_perfil as denunciado_foto, alv.email as denunciado_email,
                   alv.cidade as denunciado_cidade, alv.estado as denunciado_estado, alv.criado_em as denunciado_criado,
                   alv.ultimo_acesso as denunciado_acesso, alv.quem_sou_eu as denunciado_bio,
                   adm.nome as resolvido_por_nome
            FROM denuncias d
            JOIN usuarios den ON den.id = d.denunciante_id
            JOIN usuarios alv ON alv.id = d.denunciado_id
            LEFT JOIN usuarios adm ON adm.id = d.resolvido_por
            WHERE d.id = ?
        `).get(denId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        // Stats
        const getStats = (uid) => ({
            totalAmigos: db.prepare("SELECT COUNT(*) as c FROM amizades WHERE (remetente_id=? OR destinatario_id=?) AND status='aceita'").get(uid, uid).c,
            totalRecados: db.prepare("SELECT COUNT(*) as c FROM recados WHERE destinatario_id=?").get(uid).c,
            totalFotos: db.prepare("SELECT COUNT(*) as c FROM fotos WHERE usuario_id=?").get(uid).c,
            totalDenuncias: db.prepare("SELECT COUNT(*) as c FROM denuncias WHERE denunciado_id=?").get(uid).c
        });

        // Mensagens entre os dois
        const mensagens = db.prepare(`
            SELECT m.*, u.nome as remetente_nome FROM mensagens m
            JOIN usuarios u ON u.id = m.remetente_id
            WHERE (m.remetente_id = ? AND m.destinatario_id = ?) OR (m.remetente_id = ? AND m.destinatario_id = ?)
            ORDER BY m.criado_em DESC LIMIT 20
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Recados entre os dois
        const recados = db.prepare(`
            SELECT r.*, u.nome as remetente_nome FROM recados r
            JOIN usuarios u ON u.id = r.remetente_id
            WHERE (r.remetente_id = ? AND r.destinatario_id = ?) OR (r.remetente_id = ? AND r.destinatario_id = ?)
            ORDER BY r.criado_em DESC LIMIT 20
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Depoimentos entre os dois
        const depoimentos = db.prepare(`
            SELECT d2.*, u.nome as remetente_nome FROM depoimentos d2
            JOIN usuarios u ON u.id = d2.remetente_id
            WHERE (d2.remetente_id = ? AND d2.destinatario_id = ?) OR (d2.remetente_id = ? AND d2.destinatario_id = ?)
            ORDER BY d2.criado_em DESC LIMIT 20
        `).all(denuncia.denunciante_id, denuncia.denunciado_id, denuncia.denunciado_id, denuncia.denunciante_id);

        // Outras denúncias contra o denunciado
        const outrasDenuncias = db.prepare(`
            SELECT d2.*, u.nome as denunciante_nome FROM denuncias d2
            JOIN usuarios u ON u.id = d2.denunciante_id
            WHERE d2.denunciado_id = ? AND d2.id != ?
            ORDER BY d2.criado_em DESC LIMIT 10
        `).all(denuncia.denunciado_id, denId);

        // Chat da denúncia (mensagens admin<->user)
        const chatMensagens = db.prepare(`
            SELECT dm.*, u.nome as remetente_nome FROM denuncia_mensagens dm
            JOIN usuarios u ON u.id = dm.remetente_id
            WHERE dm.denuncia_id = ?
            ORDER BY dm.criado_em ASC
        `).all(denId);

        // Marcar mensagens do user como lidas pelo admin
        db.prepare("UPDATE denuncia_mensagens SET lida = 1 WHERE denuncia_id = ? AND is_admin = 0").run(denId);

        res.json({
            success: true,
            denuncia,
            denuncianteStats: getStats(denuncia.denunciante_id),
            denunciadoStats: getStats(denuncia.denunciado_id),
            mensagens, recados, depoimentos, outrasDenuncias, chatMensagens
        });
    } catch(err) {
        console.error('Erro detalhe denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - enviar mensagem no chat da denúncia
app.post('/api/admin/denuncia-chat/:id', requireAdmin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const { mensagem } = req.body;
        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Mensagem vazia.' });

        const denuncia = db.prepare('SELECT id, status FROM denuncias WHERE id = ?').get(denId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        db.prepare('INSERT INTO denuncia_mensagens (denuncia_id, remetente_id, mensagem, is_admin) VALUES (?, ?, ?, 1)').run(denId, req.session.userId, mensagem.trim());

        // Atualizar status para 'respondido' automaticamente
        if (denuncia.status !== 'resolvida' && denuncia.status !== 'rejeitada') {
            db.prepare("UPDATE denuncias SET status = 'respondido' WHERE id = ?").run(denId);
        }

        // Criar notificação para o denunciante
        const denCompleta = db.prepare('SELECT denunciante_id FROM denuncias WHERE id = ?').get(denId);
        if (denCompleta) {
            const previewMsg = mensagem.trim().length > 80 ? mensagem.trim().substring(0, 80) + '...' : mensagem.trim();
            db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) VALUES (?, 'denuncia_resposta', 'Voce recebeu uma nova mensagem em sua Denúncia', ?, ?)`)
                .run(denCompleta.denunciante_id, previewMsg, '/configuracoes.php?denuncias=1&did=' + denId);
        }

        res.json({ success: true });
    } catch(err) {
        console.error('Erro chat admin denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Usuário - mensagens do chat da denúncia
app.get('/api/denuncia-chat/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare('SELECT * FROM denuncias WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        const mensagens = db.prepare(`
            SELECT dm.*, u.nome as remetente_nome FROM denuncia_mensagens dm
            JOIN usuarios u ON u.id = dm.remetente_id
            WHERE dm.denuncia_id = ?
            ORDER BY dm.criado_em ASC
        `).all(denId);

        // Marcar mensagens do admin como lidas pelo user
        db.prepare("UPDATE denuncia_mensagens SET lida = 1 WHERE denuncia_id = ? AND is_admin = 1").run(denId);

        res.json({ success: true, mensagens, denuncia });
    } catch(err) {
        console.error('Erro chat denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Usuário - desistir da denúncia (deletar permanentemente)
app.post('/api/denuncia-desistir/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare('SELECT * FROM denuncias WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        // Deletar a denúncia (mensagens são removidas via CASCADE)
        db.prepare('DELETE FROM denuncias WHERE id = ?').run(denId);
        res.json({ success: true, message: 'Denúncia removida com sucesso.' });
    } catch(err) {
        console.error('Erro ao desistir da denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Usuário - enviar mensagem no chat da denúncia
app.post('/api/denuncia-chat/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const { mensagem } = req.body;
        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Mensagem vazia.' });

        const denuncia = db.prepare('SELECT * FROM denuncias WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        if (denuncia.status === 'resolvida' || denuncia.status === 'rejeitada') {
            return res.json({ success: false, message: 'Esta denúncia já foi encerrada.' });
        }

        db.prepare('INSERT INTO denuncia_mensagens (denuncia_id, remetente_id, mensagem, is_admin) VALUES (?, ?, ?, 0)').run(denId, req.session.userId, mensagem.trim());

        // Voltar status para 'pendente' quando o usuário responde
        if (denuncia.status === 'respondido') {
            db.prepare("UPDATE denuncias SET status = 'pendente' WHERE id = ?").run(denId);
        }

        res.json({ success: true });
    } catch(err) {
        console.error('Erro enviar chat denúncia:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== DENÚNCIAS DE COMUNIDADES =====

// API: Denunciar comunidade
app.post('/api/denunciar-comunidade', requireLogin, (req, res) => {
    try {
        const { comunidade_id, motivo } = req.body;
        const denunciante_id = req.session.userId;
        if (!comunidade_id || !motivo || !motivo.trim()) {
            return res.json({ success: false, message: 'Preencha o motivo da denúncia.' });
        }
        // Verificar se comunidade existe
        const com = db.prepare('SELECT id, dono_id FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!com) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(com.dono_id) === String(denunciante_id)) {
            return res.json({ success: false, message: 'Você não pode denunciar sua própria comunidade.' });
        }
        // Verificar se já existe denúncia pendente
        const existente = db.prepare('SELECT id FROM denuncias_comunidades WHERE denunciante_id = ? AND comunidade_id = ? AND status = ?').get(denunciante_id, comunidade_id, 'pendente');
        if (existente) {
            return res.json({ success: false, message: 'Você já possui uma denúncia pendente para esta comunidade.' });
        }
        const result = db.prepare('INSERT INTO denuncias_comunidades (denunciante_id, comunidade_id, motivo) VALUES (?, ?, ?)').run(denunciante_id, comunidade_id, motivo.trim());
        res.json({ success: true, message: 'Denúncia enviada com sucesso.', denunciaId: result.lastInsertRowid });
    } catch(err) {
        console.error('Erro ao denunciar comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Minhas denúncias de comunidades
app.get('/api/minhas-denuncias-comunidades', requireLogin, (req, res) => {
    try {
        const denuncias = db.prepare(`
            SELECT dc.*, c.nome as comunidade_nome, c.foto as comunidade_foto,
                   (SELECT COUNT(*) FROM denuncia_comunidade_mensagens dcm WHERE dcm.denuncia_id = dc.id AND dcm.is_admin = 1 AND dcm.lida = 0) as mensagens_nao_lidas,
                   (SELECT COUNT(*) FROM denuncia_comunidade_mensagens dcm WHERE dcm.denuncia_id = dc.id) as total_mensagens,
                   (SELECT dcm2.mensagem FROM denuncia_comunidade_mensagens dcm2 WHERE dcm2.denuncia_id = dc.id AND dcm2.is_admin = 1 ORDER BY dcm2.criado_em DESC LIMIT 1) as ultima_resposta_equipe
            FROM denuncias_comunidades dc
            JOIN comunidades c ON c.id = dc.comunidade_id
            WHERE dc.denunciante_id = ?
            ORDER BY dc.id DESC
        `).all(req.session.userId);
        res.json({ success: true, denuncias });
    } catch(err) {
        console.error('Erro minhas denúncias comunidades:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Chat da denúncia de comunidade (user)
app.get('/api/denuncia-comunidade-chat/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare('SELECT * FROM denuncias_comunidades WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        const mensagens = db.prepare(`
            SELECT dm.*, u.nome as remetente_nome FROM denuncia_comunidade_mensagens dm
            JOIN usuarios u ON u.id = dm.remetente_id
            WHERE dm.denuncia_id = ? ORDER BY dm.criado_em ASC
        `).all(denId);

        // Marcar mensagens admin como lidas
        db.prepare("UPDATE denuncia_comunidade_mensagens SET lida = 1 WHERE denuncia_id = ? AND is_admin = 1").run(denId);

        res.json({ success: true, mensagens, denuncia });
    } catch(err) {
        console.error('Erro chat denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: User - enviar msg no chat da denúncia de comunidade
app.post('/api/denuncia-comunidade-chat/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const { mensagem } = req.body;
        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Mensagem vazia.' });

        const denuncia = db.prepare('SELECT * FROM denuncias_comunidades WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });
        if (denuncia.status === 'resolvida' || denuncia.status === 'rejeitada') {
            return res.json({ success: false, message: 'Esta denúncia já foi encerrada.' });
        }

        db.prepare('INSERT INTO denuncia_comunidade_mensagens (denuncia_id, remetente_id, mensagem, is_admin) VALUES (?, ?, ?, 0)').run(denId, req.session.userId, mensagem.trim());
        if (denuncia.status === 'respondido') {
            db.prepare("UPDATE denuncias_comunidades SET status = 'pendente' WHERE id = ?").run(denId);
        }
        res.json({ success: true });
    } catch(err) {
        console.error('Erro enviar chat denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: User - desistir da denúncia de comunidade
app.post('/api/denuncia-comunidade-desistir/:id', requireLogin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare('SELECT * FROM denuncias_comunidades WHERE id = ? AND denunciante_id = ?').get(denId, req.session.userId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });
        db.prepare('DELETE FROM denuncias_comunidades WHERE id = ?').run(denId);
        res.json({ success: true, message: 'Denúncia removida com sucesso.' });
    } catch(err) {
        console.error('Erro ao desistir denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - listar denúncias de comunidades
app.get('/api/admin/denuncias-comunidades', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;
        const filtro = req.query.filtro || 'todos';

        let where = '';
        if (filtro !== 'todos') where = "WHERE dc.status = '" + filtro.replace(/'/g, '') + "'";

        const total = db.prepare('SELECT COUNT(*) as c FROM denuncias_comunidades dc ' + where).get().c;
        const denuncias = db.prepare(`
            SELECT dc.*, u.nome as denunciante_nome, c.nome as comunidade_nome, c.foto as comunidade_foto,
                   u2.nome as dono_nome
            FROM denuncias_comunidades dc
            JOIN usuarios u ON u.id = dc.denunciante_id
            JOIN comunidades c ON c.id = dc.comunidade_id
            JOIN usuarios u2 ON u2.id = c.dono_id
            ${where}
            ORDER BY dc.id DESC LIMIT ? OFFSET ?
        `).all(limit, offset);

        res.json({
            success: true, denuncias,
            page, totalPages: Math.ceil(total / limit) || 1, total
        });
    } catch(err) {
        console.error('Erro admin denúncias comunidades:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - detalhe denúncia de comunidade
app.get('/api/admin/denuncia-comunidade-detalhe/:id', requireAdmin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const denuncia = db.prepare(`
            SELECT dc.*,
                   u.nome as denunciante_nome, u.foto_perfil as denunciante_foto, u.email as denunciante_email,
                   u.cidade as denunciante_cidade, u.estado as denunciante_estado, u.criado_em as denunciante_criado,
                   u.ultimo_acesso as denunciante_acesso,
                   c.nome as comunidade_nome, c.foto as comunidade_foto, c.descricao as comunidade_descricao,
                   c.categoria as comunidade_categoria, c.tipo as comunidade_tipo, c.idioma as comunidade_idioma,
                   c.criado_em as comunidade_criado, c.dono_id as comunidade_dono_id,
                   dono.nome as dono_nome, dono.foto_perfil as dono_foto,
                   adm.nome as resolvido_por_nome
            FROM denuncias_comunidades dc
            JOIN usuarios u ON u.id = dc.denunciante_id
            JOIN comunidades c ON c.id = dc.comunidade_id
            JOIN usuarios dono ON dono.id = c.dono_id
            LEFT JOIN usuarios adm ON adm.id = dc.resolvido_por
            WHERE dc.id = ?
        `).get(denId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        // Stats da comunidade
        const totalMembros = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(denuncia.comunidade_id).c;
        const totalDenuncias = db.prepare('SELECT COUNT(*) as c FROM denuncias_comunidades WHERE comunidade_id = ?').get(denuncia.comunidade_id).c;

        // Outras denúncias contra esta comunidade
        const outrasDenuncias = db.prepare(`
            SELECT dc2.*, u2.nome as denunciante_nome FROM denuncias_comunidades dc2
            JOIN usuarios u2 ON u2.id = dc2.denunciante_id
            WHERE dc2.comunidade_id = ? AND dc2.id != ?
            ORDER BY dc2.criado_em DESC LIMIT 10
        `).all(denuncia.comunidade_id, denId);

        // Chat
        const chatMensagens = db.prepare(`
            SELECT dm.*, u3.nome as remetente_nome FROM denuncia_comunidade_mensagens dm
            JOIN usuarios u3 ON u3.id = dm.remetente_id
            WHERE dm.denuncia_id = ? ORDER BY dm.criado_em ASC
        `).all(denId);

        // Marcar mensagens do user como lidas pelo admin
        db.prepare("UPDATE denuncia_comunidade_mensagens SET lida = 1 WHERE denuncia_id = ? AND is_admin = 0").run(denId);

        res.json({
            success: true, denuncia,
            comunidadeStats: { totalMembros, totalDenuncias },
            outrasDenuncias, chatMensagens
        });
    } catch(err) {
        console.error('Erro detalhe denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - enviar msg chat denúncia de comunidade
app.post('/api/admin/denuncia-comunidade-chat/:id', requireAdmin, (req, res) => {
    try {
        const denId = parseInt(req.params.id);
        const { mensagem } = req.body;
        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Mensagem vazia.' });

        const denuncia = db.prepare('SELECT id, status, denunciante_id FROM denuncias_comunidades WHERE id = ?').get(denId);
        if (!denuncia) return res.json({ success: false, message: 'Denúncia não encontrada.' });

        db.prepare('INSERT INTO denuncia_comunidade_mensagens (denuncia_id, remetente_id, mensagem, is_admin) VALUES (?, ?, ?, 1)').run(denId, req.session.userId, mensagem.trim());

        if (denuncia.status !== 'resolvida' && denuncia.status !== 'rejeitada') {
            db.prepare("UPDATE denuncias_comunidades SET status = 'respondido' WHERE id = ?").run(denId);
        }

        // Notificar o denunciante
        const previewMsg = mensagem.trim().length > 80 ? mensagem.trim().substring(0, 80) + '...' : mensagem.trim();
        db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id) VALUES (?, 'denuncia_resposta', 'Voce recebeu uma nova mensagem em sua Denúncia de Comunidade', ?, ?, ?)`).run(
            denuncia.denunciante_id, previewMsg, '/configuracoes.php?denuncias=1&dcid=' + denId, req.session.userId
        );

        res.json({ success: true });
    } catch(err) {
        console.error('Erro admin chat denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - alterar status denúncia de comunidade
app.post('/api/admin/denuncia-comunidade/status', requireAdmin, (req, res) => {
    try {
        const { id, status, resposta_admin } = req.body;
        if (!id || !status) return res.json({ success: false, message: 'Dados inválidos.' });
        const validos = ['pendente', 'analisando', 'respondido', 'resolvida', 'rejeitada'];
        if (!validos.includes(status)) return res.json({ success: false, message: 'Status inválido.' });

        const update = status === 'resolvida' || status === 'rejeitada'
            ? db.prepare('UPDATE denuncias_comunidades SET status = ?, resposta_admin = ?, resolvido_por = ?, resolvido_em = datetime(\'now\',\'-3 hours\') WHERE id = ?')
            : db.prepare('UPDATE denuncias_comunidades SET status = ?, resposta_admin = ?, resolvido_por = NULL, resolvido_em = NULL WHERE id = ?');

        if (status === 'resolvida' || status === 'rejeitada') {
            update.run(status, resposta_admin || null, req.session.userId, id);
        } else {
            update.run(status, resposta_admin || null, id);
        }
        res.json({ success: true, message: 'Status atualizado.' });
    } catch(err) {
        console.error('Erro status denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Admin - excluir denúncia de comunidade
app.post('/api/admin/denuncia-comunidade/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        if (!id) return res.json({ success: false, message: 'ID inválido.' });
        db.prepare('DELETE FROM denuncia_comunidade_mensagens WHERE denuncia_id = ?').run(id);
        db.prepare('DELETE FROM denuncias_comunidades WHERE id = ?').run(id);
        res.json({ success: true, message: 'Denúncia excluída.' });
    } catch(err) {
        console.error('Erro excluir denúncia comunidade:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Buscar notificações do usuário
app.get('/api/notificacoes', requireLogin, (req, res) => {
    try {
        const notifs = db.prepare(`
            SELECT n.*, u.nome AS remetente_nome, u.foto_perfil AS remetente_foto
            FROM notificacoes n
            LEFT JOIN usuarios u ON u.id = n.remetente_id
            WHERE n.usuario_id = ?
            ORDER BY n.criado_em DESC LIMIT 20
        `).all(req.session.userId);
        const naoLidas = db.prepare(
            'SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = ? AND lida = 0'
        ).get(req.session.userId).total;
        res.json({ success: true, notificacoes: notifs, naoLidas });
    } catch(err) {
        console.error('Erro ao buscar notificações:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Marcar notificações como lidas
app.post('/api/notificacoes/marcar-lidas', requireLogin, (req, res) => {
    try {
        db.prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0').run(req.session.userId);
        res.json({ success: true });
    } catch(err) {
        res.json({ success: false });
    }
});

// API: Marcar uma notificação específica como lida (read_notif)
app.post('/api/notificacoes/marcar-lida/:id', requireLogin, (req, res) => {
    try {
        const notifId = req.params.id;
        db.prepare('UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?').run(notifId, req.session.userId);
        res.json({ success: true });
    } catch(err) {
        res.json({ success: false });
    }
});

// API: Excluir uma notificação
app.post('/api/notificacoes/excluir', requireLogin, (req, res) => {
    try {
        const { id } = req.body;
        if (!id) return res.json({ success: false, message: 'ID obrigatório.' });
        const result = db.prepare('DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?').run(id, req.session.userId);
        if (result.changes === 0) return res.json({ success: false, message: 'Notificação não encontrada.' });
        res.json({ success: true });
    } catch(err) {
        console.error('Erro ao excluir notificação:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Excluir todas as notificações
app.post('/api/notificacoes/excluir-todas', requireLogin, (req, res) => {
    try {
        db.prepare('DELETE FROM notificacoes WHERE usuario_id = ?').run(req.session.userId);
        res.json({ success: true });
    } catch(err) {
        res.json({ success: false, message: 'Erro interno.' });
    }
});

app.get('/logout.php', (req, res) => {
    req.session.destroy();
    res.redirect('/index.php');
});

// API: Logout
app.post('/api/logout', (req, res) => {
    req.session.destroy();
    return res.json({ success: true, redirect: '/index.php' });
});

// ===== SUGESTÕES E BUGS =====

// Helper: salvar imagens base64 para uma pasta
function saveBase64Images(imagesArray, folder) {
    const saved = [];
    const dir = path.join(uploadsDir, folder);
    for (let i = 0; i < imagesArray.length && i < 3; i++) {
        const img = imagesArray[i];
        if (!img || !img.startsWith('data:image/')) continue;
        const matches = img.match(/^data:image\/(jpeg|png|jpg|gif|webp);base64,(.+)$/);
        if (!matches) continue;
        const ext = matches[1] === 'jpeg' ? 'jpg' : matches[1];
        const buffer = Buffer.from(matches[2], 'base64');
        if (buffer.length > 3 * 1024 * 1024) continue; // max 3MB per image
        const filename = `${folder}_${Date.now()}_${i}.${ext}`;
        fs.writeFileSync(path.join(dir, filename), buffer);
        saved.push(`/uploads/${folder}/${filename}`);
    }
    return saved;
}

// API: Enviar sugestão
app.post('/api/sugestao', requireLogin, (req, res) => {
    try {
        const { titulo, descricao, imagens } = req.body;
        if (!titulo || !titulo.trim()) return res.json({ success: false, message: 'Título obrigatório.' });
        if (!descricao || !descricao.trim()) return res.json({ success: false, message: 'Descrição obrigatória.' });
        if (titulo.trim().length > 200) return res.json({ success: false, message: 'Título muito longo (máx 200 caracteres).' });
        if (descricao.trim().length > 5000) return res.json({ success: false, message: 'Descrição muito longa (máx 5000 caracteres).' });

        let imagensJson = null;
        if (imagens && Array.isArray(imagens) && imagens.length > 0) {
            const saved = saveBase64Images(imagens, 'sugestoes');
            if (saved.length > 0) imagensJson = JSON.stringify(saved);
        }

        db.prepare('INSERT INTO sugestoes (usuario_id, titulo, descricao, imagens) VALUES (?, ?, ?, ?)').run(
            req.session.userId, titulo.trim(), descricao.trim(), imagensJson
        );
        res.json({ success: true, message: 'Sugestão enviada com sucesso!' });
    } catch(err) {
        console.error('Erro ao enviar sugestão:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API: Enviar bug
app.post('/api/bug', requireLogin, (req, res) => {
    try {
        const { titulo, descricao, imagens } = req.body;
        if (!titulo || !titulo.trim()) return res.json({ success: false, message: 'Título obrigatório.' });
        if (!descricao || !descricao.trim()) return res.json({ success: false, message: 'Descrição obrigatória.' });
        if (titulo.trim().length > 200) return res.json({ success: false, message: 'Título muito longo (máx 200 caracteres).' });
        if (descricao.trim().length > 5000) return res.json({ success: false, message: 'Descrição muito longa (máx 5000 caracteres).' });

        let imagensJson = null;
        if (imagens && Array.isArray(imagens) && imagens.length > 0) {
            const saved = saveBase64Images(imagens, 'bugs');
            if (saved.length > 0) imagensJson = JSON.stringify(saved);
        }

        db.prepare('INSERT INTO bugs (usuario_id, titulo, descricao, imagens) VALUES (?, ?, ?, ?)').run(
            req.session.userId, titulo.trim(), descricao.trim(), imagensJson
        );
        res.json({ success: true, message: 'Bug reportado com sucesso!' });
    } catch(err) {
        console.error('Erro ao reportar bug:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Listar sugestões
app.get('/api/admin/sugestoes', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 15;
        const offset = (page - 1) * limit;
        const filtro = req.query.filtro || 'todos';
        let where = '';
        if (filtro !== 'todos') where = " WHERE s.status = '" + filtro.replace(/'/g, '') + "'";
        const total = db.prepare('SELECT COUNT(*) as c FROM sugestoes s' + where).get().c;
        const sugestoes = db.prepare(`
            SELECT s.*, u.nome as autor_nome, u.foto_perfil as autor_foto
            FROM sugestoes s JOIN usuarios u ON u.id = s.usuario_id
            ${where} ORDER BY s.criado_em DESC LIMIT ? OFFSET ?
        `).all(limit, offset);
        res.json({ success: true, sugestoes, page, totalPages: Math.ceil(total / limit), total });
    } catch(err) {
        console.error('Erro admin sugestões:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Listar bugs
app.get('/api/admin/bugs', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 15;
        const offset = (page - 1) * limit;
        const filtro = req.query.filtro || 'todos';
        let where = '';
        if (filtro !== 'todos') where = " WHERE b.status = '" + filtro.replace(/'/g, '') + "'";
        const total = db.prepare('SELECT COUNT(*) as c FROM bugs b' + where).get().c;
        const bugs = db.prepare(`
            SELECT b.*, u.nome as autor_nome, u.foto_perfil as autor_foto
            FROM bugs b JOIN usuarios u ON u.id = b.usuario_id
            ${where} ORDER BY b.criado_em DESC LIMIT ? OFFSET ?
        `).all(limit, offset);
        res.json({ success: true, bugs, page, totalPages: Math.ceil(total / limit), total });
    } catch(err) {
        console.error('Erro admin bugs:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Alterar status de sugestão
app.post('/api/admin/sugestao/status', requireAdmin, (req, res) => {
    try {
        const { id, status, resposta } = req.body;
        if (!id || !status) return res.json({ success: false, message: 'Dados insuficientes.' });
        const validStatus = ['nova', 'analisando', 'aprovada', 'implementada', 'rejeitada'];
        if (!validStatus.includes(status)) return res.json({ success: false, message: 'Status inválido.' });
        db.prepare('UPDATE sugestoes SET status = ?, resposta_admin = ?, resolvido_por = ?, resolvido_em = ? WHERE id = ?').run(
            status, resposta || null, req.session.userId, agora(), id
        );
        // Notificar o autor
        const sug = db.prepare('SELECT usuario_id, titulo FROM sugestoes WHERE id = ?').get(id);
        if (sug) {
            const statusLabels = {nova:'Nova', analisando:'Em Análise', aprovada:'Aprovada', implementada:'Implementada', rejeitada:'Rejeitada'};
            db.prepare('INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES (?, ?, ?, ?)').run(
                sug.usuario_id, 'sugestao_resposta', 'Sugestão atualizada',
                'Sua sugestão "' + sug.titulo.substring(0, 50) + '" foi atualizada para: ' + (statusLabels[status] || status) + (resposta ? ' — ' + resposta.substring(0, 100) : '')
            );
        }
        res.json({ success: true });
    } catch(err) {
        console.error('Erro status sugestão:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Alterar status de bug
app.post('/api/admin/bug/status', requireAdmin, (req, res) => {
    try {
        const { id, status, resposta } = req.body;
        if (!id || !status) return res.json({ success: false, message: 'Dados insuficientes.' });
        const validStatus = ['novo', 'analisando', 'corrigido', 'nao_reproduzivel', 'rejeitado'];
        if (!validStatus.includes(status)) return res.json({ success: false, message: 'Status inválido.' });
        db.prepare('UPDATE bugs SET status = ?, resposta_admin = ?, resolvido_por = ?, resolvido_em = ? WHERE id = ?').run(
            status, resposta || null, req.session.userId, agora(), id
        );
        // Notificar o autor
        const bug = db.prepare('SELECT usuario_id, titulo FROM bugs WHERE id = ?').get(id);
        if (bug) {
            const statusLabels = {novo:'Novo', analisando:'Em Análise', corrigido:'Corrigido', nao_reproduzivel:'Não Reproduzível', rejeitado:'Rejeitado'};
            db.prepare('INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES (?, ?, ?, ?)').run(
                bug.usuario_id, 'bug_resposta', 'Bug atualizado',
                'Seu bug "' + bug.titulo.substring(0, 50) + '" foi atualizado para: ' + (statusLabels[status] || status) + (resposta ? ' — ' + resposta.substring(0, 100) : '')
            );
        }
        res.json({ success: true });
    } catch(err) {
        console.error('Erro status bug:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Excluir sugestão
app.post('/api/admin/sugestao/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        const sug = db.prepare('SELECT imagens FROM sugestoes WHERE id = ?').get(id);
        if (sug && sug.imagens) {
            try {
                JSON.parse(sug.imagens).forEach(img => {
                    const p = path.join(__dirname, img.substring(1));
                    if (fs.existsSync(p)) fs.unlinkSync(p);
                });
            } catch(e) {}
        }
        db.prepare('DELETE FROM sugestoes WHERE id = ?').run(id);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir sugestão:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// API Admin: Excluir bug
app.post('/api/admin/bug/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        const bug = db.prepare('SELECT imagens FROM bugs WHERE id = ?').get(id);
        if (bug && bug.imagens) {
            try {
                JSON.parse(bug.imagens).forEach(img => {
                    const p = path.join(__dirname, img.substring(1));
                    if (fs.existsSync(p)) fs.unlinkSync(p);
                });
            } catch(e) {}
        }
        db.prepare('DELETE FROM bugs WHERE id = ?').run(id);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir bug:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== ANÚNCIOS =====

// Listar anúncios (público - qualquer usuário logado)
app.get('/api/anuncios', requireLogin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;
        const total = db.prepare('SELECT COUNT(*) AS total FROM anuncios').get().total;
        const totalPages = Math.ceil(total / limit) || 1;
        const anuncios = db.prepare(`
            SELECT a.*, u.nome AS admin_nome, u.foto_perfil AS admin_foto
            FROM anuncios a
            LEFT JOIN usuarios u ON u.id = a.admin_id
            ORDER BY a.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(limit, offset);
        res.json({ success: true, anuncios, page, totalPages, total });
    } catch(err) {
        console.error('Erro listar anúncios:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Detalhe de um anúncio
app.get('/api/anuncio/:id', requireLogin, (req, res) => {
    try {
        const id = parseInt(req.params.id);
        if (!id) return res.json({ success: false, message: 'ID inválido.' });
        const anuncio = db.prepare(`
            SELECT a.*, u.nome AS admin_nome, u.foto_perfil AS admin_foto
            FROM anuncios a
            LEFT JOIN usuarios u ON u.id = a.admin_id
            WHERE a.id = ?
        `).get(id);
        if (!anuncio) return res.json({ success: false, message: 'Anúncio não encontrado.' });
        // Anterior (mais recente) e próximo (mais antigo)
        const anterior = db.prepare('SELECT id, titulo FROM anuncios WHERE criado_em > ? ORDER BY criado_em ASC LIMIT 1').get(anuncio.criado_em);
        const proximo = db.prepare('SELECT id, titulo FROM anuncios WHERE criado_em < ? ORDER BY criado_em DESC LIMIT 1').get(anuncio.criado_em);
        res.json({ success: true, anuncio, anterior: anterior || null, proximo: proximo || null });
    } catch(err) {
        console.error('Erro detalhe anúncio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Listar anúncios (admin)
app.get('/api/admin/anuncios', requireAdmin, (req, res) => {
    try {
        const page = parseInt(req.query.page) || 1;
        const limit = 15;
        const offset = (page - 1) * limit;
        const total = db.prepare('SELECT COUNT(*) AS total FROM anuncios').get().total;
        const totalPages = Math.ceil(total / limit) || 1;
        const anuncios = db.prepare(`
            SELECT a.*, u.nome AS admin_nome, u.foto_perfil AS admin_foto
            FROM anuncios a
            LEFT JOIN usuarios u ON u.id = a.admin_id
            ORDER BY a.criado_em DESC
            LIMIT ? OFFSET ?
        `).all(limit, offset);
        res.json({ success: true, anuncios, page, totalPages, total });
    } catch(err) {
        console.error('Erro listar anúncios:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Criar anúncio e notificar todos os usuários
app.post('/api/admin/anuncio/criar', requireAdmin, (req, res) => {
    try {
        const { titulo, mensagem } = req.body;
        if (!titulo || !mensagem) return res.json({ success: false, message: 'Preencha título e mensagem.' });
        if (titulo.length > 100) return res.json({ success: false, message: 'Título muito longo (máx 100).' });
        if (mensagem.length > 10000) return res.json({ success: false, message: 'Mensagem muito longa.' });

        // Processar foto base64
        const { foto_base64 } = req.body;
        let fotoPath = null;
        if (foto_base64 && foto_base64.startsWith('data:image/')) {
            const matches = foto_base64.match(/^data:image\/(jpeg|png|jpg|gif|webp);base64,(.+)$/s);
            if (matches) {
                const ext = matches[1] === 'png' ? 'png' : 'jpg';
                const buffer = Buffer.from(matches[2], 'base64');
                if (buffer.length > 2 * 1024 * 1024) {
                    return res.json({ success: false, message: 'Imagem muito grande. Máximo 2MB.' });
                }
                const filename = `anuncio_${Date.now()}.${ext}`;
                const uploadDir = path.join(__dirname, 'uploads', 'anuncios');
                if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });
                fs.writeFileSync(path.join(uploadDir, filename), buffer);
                fotoPath = `/uploads/anuncios/${filename}`;
            }
        }

        const result = db.prepare(`INSERT INTO anuncios (titulo, mensagem, foto, admin_id, criado_em) VALUES (?, ?, ?, ?, ${agora()})`).run(titulo, mensagem, fotoPath, req.session.userId);
        const anuncioId = result.lastInsertRowid;

        // Notificar todos os usuários ativos (não banidos)
        const usuarios = db.prepare("SELECT id FROM usuarios WHERE banido != 1 OR banido IS NULL").all();
        const insertNotif = db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id, criado_em) VALUES (?, 'anuncio', ?, ?, ?, ?, ${agora()})`);
        const notifTitulo = 'Nova notícia oficial: ' + titulo;
        const notifLink = '/anuncio.php?id=' + anuncioId;
        const notifMsgClean = mensagem.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
        const notifMsg = notifMsgClean.length > 200 ? notifMsgClean.substring(0, 200) + '...' : notifMsgClean;
        const insertMany = db.transaction((users) => {
            for (const u of users) {
                insertNotif.run(u.id, notifTitulo, notifMsg, notifLink, req.session.userId);
            }
        });
        insertMany(usuarios);

        res.json({ success: true, id: result.lastInsertRowid, notificados: usuarios.length });
    } catch(err) {
        console.error('Erro criar anúncio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir anúncio
app.post('/api/admin/anuncio/excluir', requireAdmin, (req, res) => {
    try {
        const { id } = req.body;
        db.prepare('DELETE FROM anuncios WHERE id = ?').run(id);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir anúncio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Editar anúncio (sem notificação)
app.post('/api/admin/anuncio/editar', requireAdmin, (req, res) => {
    try {
        const { id, titulo, mensagem, foto_base64, remover_foto } = req.body;
        if (!id || !titulo || !mensagem) return res.json({ success: false, message: 'Preencha título e mensagem.' });
        if (titulo.length > 100) return res.json({ success: false, message: 'Título muito longo (máx 100).' });
        if (mensagem.length > 10000) return res.json({ success: false, message: 'Mensagem muito longa.' });

        const anuncio = db.prepare('SELECT * FROM anuncios WHERE id = ?').get(id);
        if (!anuncio) return res.json({ success: false, message: 'Anúncio não encontrado.' });

        let fotoPath = anuncio.foto;

        // Remover foto se solicitado
        if (remover_foto) {
            if (anuncio.foto) {
                const oldFile = path.join(__dirname, anuncio.foto);
                if (fs.existsSync(oldFile)) fs.unlinkSync(oldFile);
            }
            fotoPath = null;
        }

        // Nova foto
        if (foto_base64 && foto_base64.startsWith('data:image/')) {
            const matches = foto_base64.match(/^data:image\/(jpeg|png|jpg|gif|webp);base64,(.+)$/s);
            if (matches) {
                // Remover foto antiga
                if (anuncio.foto) {
                    const oldFile = path.join(__dirname, anuncio.foto);
                    if (fs.existsSync(oldFile)) fs.unlinkSync(oldFile);
                }
                const ext = matches[1] === 'png' ? 'png' : 'jpg';
                const buffer = Buffer.from(matches[2], 'base64');
                const filename = `anuncio_${Date.now()}.${ext}`;
                const uploadDir = path.join(__dirname, 'uploads', 'anuncios');
                if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });
                fs.writeFileSync(path.join(uploadDir, filename), buffer);
                fotoPath = `/uploads/anuncios/${filename}`;
            }
        }

        db.prepare('UPDATE anuncios SET titulo = ?, mensagem = ?, foto = ? WHERE id = ?').run(titulo, mensagem, fotoPath, id);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro editar anúncio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== COLHEITA FELIZ - API =====

// Helper: garantir que o farm existe para um usuário
function ensureColheitaFarm(userId) {
    let farm = db.prepare('SELECT * FROM colheita_farms WHERE user_id = ?').get(userId);
    if (!farm) {
        // Inicializar fazenda com 24 tiles no estado 3 (grama normal) e inventário com 10 sementes de feijão
        const initialFarm = {};
        for (let i = 0; i < 24; i++) {
            initialFarm[i] = { state: 3, timer: 0, planted: null, catalyst: false, phase4_timer: 0, harvested: 0, stolenBy: [], yield: 50 };
        }
        const initialInventory = { seed_eijo: 10 };
        const now = Math.floor(Date.now() / 1000);
        db.prepare('INSERT INTO colheita_farms (user_id, farm_data, inventory, level, xp, ouro, grid_size, last_updated, unlocked_tiles) VALUES (?, ?, ?, 1, 0, 100, 24, ?, ?)').run(
            userId, JSON.stringify(initialFarm), JSON.stringify(initialInventory), now, '[0]'
        );
        farm = db.prepare('SELECT * FROM colheita_farms WHERE user_id = ?').get(userId);
    }
    return farm;
}

// Helper: migrar tiles antigos sem timestamps (retrocompatibilidade)
function migrateTimestamps(farmData, lastUpdatedMs) {
    for (const id in farmData) {
        const tile = farmData[id];
        // State 10 (crescendo) sem planted_at → estimar baseado no timer salvo
        if (tile.state === 10 && tile.planted && !tile.planted_at) {
            tile.planted_at = lastUpdatedMs - (tile.timer || 0);
            tile.fert_bonus = tile.fert_bonus || 0;
        }
        // State 11 (colheita pronta) sem harvestable_at → estimar
        if (tile.state === 11 && tile.planted && !tile.harvestable_at) {
            const seed = SEEDS_CONFIG[tile.planted];
            if (seed) {
                const totalGrow = ((seed.tempo_fase2 || 60) + (seed.tempo_fase3 || 60) + (seed.tempo_fase4 || 60)) * 1000;
                // planted_at estimado = last_updated - totalGrow - timer_no_state_11
                tile.planted_at = tile.planted_at || (lastUpdatedMs - totalGrow - (tile.timer || 0));
                tile.harvestable_at = lastUpdatedMs - (tile.timer || 0);
            }
        }
        // State 2/3 sem planta e sem watered_at → estimar
        if ((tile.state === 2 || tile.state === 3) && !tile.planted && !tile.watered_at) {
            tile.watered_at = lastUpdatedMs - (tile.timer || 0);
        }
    }
}

// Helper: recalcular estados dos tiles baseado em timestamps do servidor
function recalculateTileStates(farmData, nowMs) {
    for (const id in farmData) {
        const tile = farmData[id];

        // Solo molhado/normal sem planta → seca após 5 minutos
        if ((tile.state === 2 || tile.state === 3) && !tile.planted && tile.watered_at) {
            const elapsed = nowMs - tile.watered_at;
            if (elapsed >= 300000) {
                tile.state = 4;
                tile.timer = 0;
                delete tile.watered_at;
            } else {
                tile.timer = elapsed;
            }
        }

        // Crescendo → colheita pronta → morta
        if (tile.state === 10 && tile.planted && tile.planted_at) {
            const seed = SEEDS_CONFIG[tile.planted];
            if (seed) {
                const t1 = (seed.tempo_fase2 || 60) * 1000;
                const t2 = (seed.tempo_fase3 || 60) * 1000;
                const t3 = (seed.tempo_fase4 || 60) * 1000;
                const totalGrow = t1 + t2 + t3;
                const fert = tile.fert_bonus || 0;
                const growElapsed = nowMs - tile.planted_at + fert;

                if (growElapsed >= totalGrow) {
                    // Planta ficou pronta para colher
                    tile.state = 11;
                    tile.harvestable_at = tile.planted_at + totalGrow - fert;
                    tile.timer = 0;
                    delete tile.fertPhase;

                    // Verificar se também já morreu
                    const f4_time = (seed.tempo_fase5 || 300) * 1000;
                    const harvestElapsed = nowMs - tile.harvestable_at;
                    if (harvestElapsed >= f4_time) {
                        tile.state = 12;
                        tile.timer = 0;
                        delete tile.harvestable_at;
                    } else {
                        tile.timer = harvestElapsed;
                    }
                } else {
                    tile.timer = growElapsed;
                }
            }
        }
        // Colheita pronta → morta
        else if (tile.state === 11 && tile.planted && tile.harvestable_at) {
            const seed = SEEDS_CONFIG[tile.planted];
            const f4_time = (seed ? (seed.tempo_fase5 || 300) : 300) * 1000;
            const elapsed = nowMs - tile.harvestable_at;

            if (elapsed >= f4_time) {
                tile.state = 12;
                tile.timer = 0;
                delete tile.harvestable_at;
            } else {
                tile.timer = elapsed;
            }
        }
    }
}

// GET /api/colheita/farm/:uid - Obter dados da fazenda
app.get('/api/colheita/farm/:uid', requireLogin, (req, res) => {
    try {
        const targetUid = req.params.uid;
        const loggedUid = req.session.userId;
        const isOwner = (targetUid === loggedUid);

        const farm = ensureColheitaFarm(targetUid);
        const owner = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(targetUid);
        if (!owner) return res.json({ success: false, message: 'Usuário não encontrado.' });
        const loggedUser = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(loggedUid);

        // Registrar visita se não for o dono
        if (!isOwner) {
            db.prepare('INSERT INTO colheita_logs (farm_owner_id, visitor_id, action, details) VALUES (?, ?, ?, ?)').run(
                targetUid, loggedUid, 'visit', ''
            );
        }

        // Obter amigos para o painel de amigos
        const friends = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil, u.sexo
            FROM amizades a
            JOIN usuarios u ON (CASE WHEN a.remetente_id = ? THEN a.destinatario_id ELSE a.remetente_id END) = u.id
            WHERE (a.remetente_id = ? OR a.destinatario_id = ?) AND a.status = 'aceita'
            LIMIT 20
        `).all(loggedUid, loggedUid, loggedUid);

        // Para cada amigo, obter seus dados de fazenda
        const friendsData = friends.map(f => {
            const ff = ensureColheitaFarm(f.id);
            return {
                id: f.id,
                nome: f.nome,
                foto_perfil: f.foto_perfil || (f.sexo === 'Feminino' ? 'img/avatar_feminino.png' : 'img/avatar_masculino.png'),
                level: ff.level || 1,
                ouro: ff.ouro,
                kutcoin: 0
            };
        });

        // Obter logs recentes
        const logs = db.prepare(`
            SELECT cl.action, cl.details, cl.created_at, u.nome, u.foto_perfil, u.sexo
            FROM colheita_logs cl
            JOIN usuarios u ON cl.visitor_id = u.id
            WHERE cl.farm_owner_id = ?
            ORDER BY cl.created_at DESC
            LIMIT 30
        `).all(targetUid);

        // Processar level-ups pendentes antes de retornar os dados
        let farmLevel = farm.level || 1;
        let farmXP = farm.xp || 0;
        while (farmLevel < MAX_LEVEL) {
            const needed = getXPNeeded(farmLevel);
            if (farmXP >= needed) { farmXP -= needed; farmLevel++; } else break;
        }
        if (farmLevel !== (farm.level || 1) || farmXP !== (farm.xp || 0)) {
            db.prepare('UPDATE colheita_farms SET level = ?, xp = ? WHERE user_id = ?').run(farmLevel, farmXP, targetUid);
        }

        // Recalcular estados dos tiles baseado em timestamps do servidor
        const farmDataParsed = JSON.parse(farm.farm_data);
        const nowMs = Date.now();
        migrateTimestamps(farmDataParsed, (farm.last_updated || Math.floor(nowMs / 1000)) * 1000);
        recalculateTileStates(farmDataParsed, nowMs);
        // Salvar estados recalculados
        const nowSec = Math.floor(nowMs / 1000);
        db.prepare('UPDATE colheita_farms SET farm_data = ?, last_updated = ? WHERE user_id = ?').run(
            JSON.stringify(farmDataParsed), nowSec, targetUid
        );

        res.json({
            success: true,
            isOwner,
            profileId: targetUid,
            loggedUserId: loggedUid,
            owner: {
                id: owner.id,
                nome: owner.nome,
                foto_perfil: owner.foto_perfil || (owner.sexo === 'Feminino' ? 'img/avatar_feminino.png' : 'img/avatar_masculino.png')
            },
            loggedUser: {
                id: loggedUser.id,
                nome: loggedUser.nome,
                foto_perfil: loggedUser.foto_perfil || (loggedUser.sexo === 'Feminino' ? 'img/avatar_feminino.png' : 'img/avatar_masculino.png')
            },
            farmData: farmDataParsed,
            inventory: JSON.parse(farm.inventory),
            level: farmLevel,
            xp: farmXP,
            ouro: farm.ouro,
            kutcoin: 0,
            gridSize: farm.grid_size,
            unlockedTiles: JSON.parse(farm.unlocked_tiles || '[0]'),
            tileReqs: db.prepare('SELECT * FROM colheita_tile_reqs ORDER BY tile_id').all(),
            lastUpdated: farm.last_updated,
            serverNow: Math.floor(Date.now() / 1000),
            seedsConfig: SEEDS_CONFIG,
            fertilizersConfig: FERTILIZERS_CONFIG,
            expTable: EXP_TABLE,
            maxLevel: MAX_LEVEL,
            friends: friendsData,
            logs: logs.map(l => ({
                action: l.action,
                details: l.details,
                created_at: l.created_at,
                nome: l.nome,
                foto_perfil: l.foto_perfil || (l.sexo === 'Feminino' ? 'img/avatar_feminino.png' : 'img/avatar_masculino.png')
            }))
        });
    } catch(err) {
        console.error('Erro colheita farm:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/unlock-tile - Comprar/liberar um tile
app.post('/api/colheita/unlock-tile', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const tile_id = parseInt(req.body.tile_id);

        // Validar tile_id: inteiro entre 0 e 23
        if (isNaN(tile_id) || tile_id < 0 || tile_id > 23) {
            return res.json({ success: false, message: 'Tile inválido.' });
        }

        const farm = ensureColheitaFarm(userId);
        let unlockedTiles = JSON.parse(farm.unlocked_tiles || '[0]');

        if (unlockedTiles.includes(tile_id)) {
            return res.json({ success: false, message: 'Tile já está liberado.' });
        }

        // Verificar sequência (tile anterior deve estar liberado)
        if (tile_id > 0 && !unlockedTiles.includes(tile_id - 1)) {
            return res.json({ success: false, message: 'Você precisa liberar o tile anterior primeiro.' });
        }

        // Buscar requisitos
        const tileConfig = db.prepare('SELECT * FROM colheita_tile_reqs WHERE tile_id = ?').get(tile_id);
        if (!tileConfig) {
            return res.json({ success: false, message: 'Configuração do tile não encontrada.' });
        }

        // Verificar nível
        if (farm.level < tileConfig.nivel_minimo) {
            return res.json({ success: false, message: `Nível insuficiente. Necessário: ${tileConfig.nivel_minimo}` });
        }

        // Verificar ouro
        if (farm.ouro < tileConfig.preco) {
            return res.json({ success: false, message: `Ouro insuficiente. Necessário: ${tileConfig.preco}` });
        }

        // Descontar ouro e liberar tile
        unlockedTiles.push(tile_id);
        unlockedTiles.sort((a, b) => a - b);
        const newOuro = farm.ouro - tileConfig.preco;

        db.prepare('UPDATE colheita_farms SET unlocked_tiles = ?, ouro = ? WHERE user_id = ?').run(
            JSON.stringify(unlockedTiles), newOuro, userId
        );

        res.json({ success: true, unlockedTiles, ouro: newOuro });
    } catch(err) {
        console.error('Erro unlock tile:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/save - Salvar fazenda (dono) — Validado pelo servidor com timestamps
app.post('/api/colheita/save', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { farm_data } = req.body;

        if (!farm_data || typeof farm_data !== 'object') return res.json({ success: false, message: 'Dados inválidos.' });

        const nowMs = Date.now();
        const nowSec = Math.floor(nowMs / 1000);
        const farm = ensureColheitaFarm(userId);
        const serverData = JSON.parse(farm.farm_data);
        const serverInv = JSON.parse(farm.inventory);

        // Migrar timestamps se necessário
        migrateTimestamps(serverData, (farm.last_updated || nowSec) * 1000);

        // Comparar tile a tile com dados do servidor
        for (const tileId in farm_data) {
            const clientTile = farm_data[tileId];
            const serverTile = serverData[tileId];
            if (!serverTile || !clientTile) continue;

            // === Ação: Plantar semente (servidor sem planta, cliente plantou) ===
            if (!serverTile.planted && clientTile.planted && clientTile.state === 10) {
                // Verificar se o tile estava molhado (state 2)
                if (serverTile.state !== 2) continue;
                const seedKey = 'seed_' + clientTile.planted;
                // Verificar inventário do servidor
                if (!serverInv[seedKey] || serverInv[seedKey] <= 0) continue;
                // Validar que é uma semente válida
                if (!SEEDS_CONFIG[String(clientTile.planted)]) continue;
                // Aplicar plantio com timestamp do servidor
                serverInv[seedKey]--;
                if (serverInv[seedKey] <= 0) delete serverInv[seedKey];
                serverTile.planted = clientTile.planted;
                serverTile.state = 10;
                serverTile.planted_at = nowMs;
                serverTile.timer = 0;
                serverTile.harvested = 0;
                serverTile.stolenBy = [];
                serverTile.currentSeason = 1;
                delete serverTile.fertPhase;
                delete serverTile.fert_bonus;
                delete serverTile.watered_at;
                continue;
            }

            // === Ação: Regar (servidor state 3/4, cliente state 2 sem planta) ===
            if ((serverTile.state === 3 || serverTile.state === 4) && clientTile.state === 2 && !clientTile.planted) {
                serverTile.state = 2;
                serverTile.watered_at = nowMs;
                serverTile.timer = 0;
                serverTile.planted = null;
                serverTile.harvested = 0;
                serverTile.stolenBy = [];
                continue;
            }

            // === Ação: Remover planta (enxada) — servidor tem planta, cliente removeu ===
            if (serverTile.planted && !clientTile.planted && (clientTile.state === 3 || clientTile.state === 2)) {
                serverTile.planted = null;
                serverTile.state = clientTile.state;
                serverTile.timer = 0;
                serverTile.harvested = 0;
                serverTile.stolenBy = [];
                delete serverTile.planted_at;
                delete serverTile.harvestable_at;
                delete serverTile.watered_at;
                delete serverTile.currentSeason;
                delete serverTile.fertPhase;
                delete serverTile.fert_bonus;
                continue;
            }

            // === Ação: Reinício de temporada (cliente state 10 com planted, servidor state 11) ===
            if (serverTile.state === 11 && clientTile.state === 10 && clientTile.planted && serverTile.planted) {
                const seed = SEEDS_CONFIG[serverTile.planted];
                if (seed) {
                    const maxTemporadas = seed.temporadas || 1;
                    const currentSeason = serverTile.currentSeason || 1;
                    if (currentSeason < maxTemporadas) {
                        // Reinício por roubo: começar na fase 3 (pular fases 1 e 2)
                        const t1 = (seed.tempo_fase2 || 60) * 1000;
                        const t2 = (seed.tempo_fase3 || 60) * 1000;
                        serverTile.state = 10;
                        serverTile.currentSeason = currentSeason + 1;
                        serverTile.planted_at = nowMs - t1 - t2; // Iniciar na fase 3
                        serverTile.timer = t1 + t2;
                        serverTile.harvested = 0;
                        serverTile.stolenBy = [];
                        delete serverTile.harvestable_at;
                        delete serverTile.fertPhase;
                        delete serverTile.fert_bonus;
                    }
                }
                continue;
            }

            // === Tiles com planta e planted_at: servidor controla os estados ===
            if (serverTile.planted && serverTile.planted_at) {
                // Não aceita mudanças do cliente para tiles crescendo/colheita
                continue;
            }

            // === Outros estados (sem planta): aceitar mudanças limitadas ===
            if (!clientTile.planted && !serverTile.planted) {
                // Regar: só de state 3/4 → 2 (requer transição válida)
                if (clientTile.state === 2 && (serverTile.state === 3 || serverTile.state === 4)) {
                    serverTile.state = 2;
                    serverTile.watered_at = nowMs;
                } else if ([3, 4].includes(clientTile.state)) {
                    // Permitir secar (2→3/4 é natural, não precisa bloquear)
                    serverTile.state = clientTile.state;
                }
            }
        }

        // Recalcular estados dos tiles baseado em timestamps
        recalculateTileStates(serverData, nowMs);

        // Salvar dados validados (inventário é do servidor, não do cliente)
        db.prepare('UPDATE colheita_farms SET farm_data = ?, inventory = ?, last_updated = ? WHERE user_id = ?').run(
            JSON.stringify(serverData), JSON.stringify(serverInv), nowSec, userId
        );
        res.json({ success: true, inventory: serverInv });
    } catch(err) {
        console.error('Erro salvar colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/save-visitor - Salvar fazenda de visitante (regar)
// Validação: só permite regar tiles (state 3/4 → 2)
app.post('/api/colheita/save-visitor', requireLogin, (req, res) => {
    try {
        const { farm_owner_id, farm_data } = req.body;
        const visitorId = req.session.userId;
        if (farm_owner_id === visitorId) return res.json({ success: false, message: 'Não pode auto-salvar como visitante.' });
        if (!farm_owner_id || !farm_data) return res.json({ success: false, message: 'Dados inválidos.' });

        const ownerFarm = db.prepare('SELECT * FROM colheita_farms WHERE user_id = ?').get(farm_owner_id);
        if (!ownerFarm) return res.json({ success: false, message: 'Fazenda não encontrada.' });

        const amizade = db.prepare(`SELECT id FROM amizades WHERE status = 'aceita' AND ((remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?))`).get(visitorId, farm_owner_id, farm_owner_id, visitorId);
        if (!amizade) return res.json({ success: false, message: 'Apenas amigos podem interagir.' });

        // Validar: apenas permitir regar (state 3/4 → 2)
        const serverData = JSON.parse(ownerFarm.farm_data);
        const nowMs = Date.now();
        const nowSec = Math.floor(nowMs / 1000);
        for (const tileId in farm_data) {
            const clientTile = farm_data[tileId];
            const serverTile = serverData[tileId];
            if (!serverTile || !clientTile) continue;
            // Só permitir regar: server state 3/4, client state 2, sem planta
            if ((serverTile.state === 3 || serverTile.state === 4) && clientTile.state === 2 && !clientTile.planted) {
                serverTile.state = 2;
                serverTile.watered_at = nowMs;
                serverTile.timer = 0;
            }
            // Todas as outras mudanças do visitante são ignoradas
        }
        db.prepare('UPDATE colheita_farms SET farm_data = ?, last_updated = ? WHERE user_id = ?').run(
            JSON.stringify(serverData), nowSec, farm_owner_id
        );
        res.json({ success: true });
    } catch(err) {
        console.error('Erro salvar visitante colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/buy - Comprar item (validado no servidor)
app.post('/api/colheita/buy', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { item_id, quantity } = req.body;
        const qty = parseInt(quantity) || 1;
        if (!item_id || qty < 1 || qty > 100) return res.json({ success: false, message: 'Dados inválidos.' });

        const seed = SEEDS_CONFIG[String(item_id)];
        if (!seed) return res.json({ success: false, message: 'Item não encontrado.' });

        const farm = ensureColheitaFarm(userId);
        const totalPrice = seed.preco_compra * qty;

        if (seed.moeda === 'gold' || seed.moeda === 'ouro') {
            if (farm.ouro < totalPrice) return res.json({ success: false, message: 'Ouro insuficiente.' });
            const inv = JSON.parse(farm.inventory);
            inv[String(item_id)] = (inv[String(item_id)] || 0) + qty;
            db.prepare('UPDATE colheita_farms SET ouro = ouro - ?, inventory = ? WHERE user_id = ?').run(totalPrice, JSON.stringify(inv), userId);
            const updated = ensureColheitaFarm(userId);
            return res.json({ success: true, ouro: updated.ouro, inventory: JSON.parse(updated.inventory) });
        } else {
            // kutcoin etc — expandir conforme necessário
            return res.json({ success: false, message: 'Moeda não suportada server-side ainda.' });
        }
    } catch(err) {
        console.error('Erro comprar colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/sell - Vender item (validado no servidor)
app.post('/api/colheita/sell', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { item_id, quantity } = req.body;
        const qty = parseInt(quantity) || 1;
        if (!item_id || qty < 1 || qty > 10000) return res.json({ success: false, message: 'Dados inválidos.' });

        const seed = SEEDS_CONFIG[String(item_id)];
        if (!seed) return res.json({ success: false, message: 'Item não encontrado.' });

        const farm = ensureColheitaFarm(userId);
        const inv = JSON.parse(farm.inventory);
        const have = inv[String(item_id)] || 0;
        if (have < qty) return res.json({ success: false, message: 'Quantidade insuficiente.' });

        const totalPrice = seed.preco_venda * qty;
        inv[String(item_id)] = have - qty;
        if (inv[String(item_id)] <= 0) delete inv[String(item_id)];

        db.prepare('UPDATE colheita_farms SET ouro = ouro + ?, inventory = ? WHERE user_id = ?').run(totalPrice, JSON.stringify(inv), userId);
        const updated = ensureColheitaFarm(userId);
        return res.json({ success: true, ouro: updated.ouro, inventory: JSON.parse(updated.inventory) });
    } catch(err) {
        console.error('Erro vender colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/use-fertilizer - Usar fertilizante (validado no servidor)
app.post('/api/colheita/use-fertilizer', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { tile_id, fert_key } = req.body;
        if (tile_id === undefined || !fert_key) return res.json({ success: false, message: 'Dados inválidos.' });

        const fertId = String(fert_key).replace('fert_', '');
        const fert = FERTILIZERS_CONFIG[fertId];
        if (!fert) return res.json({ success: false, message: 'Fertilizante não encontrado.' });

        const farm = ensureColheitaFarm(userId);
        const farmData = JSON.parse(farm.farm_data);
        const inv = JSON.parse(farm.inventory);
        const tile = farmData[tile_id];

        if (!tile || tile.state !== 10 || !tile.planted) {
            return res.json({ success: false, message: 'Fertilizante só funciona em plantas crescendo!' });
        }
        const fertInvKey = 'fert_' + fertId;
        if (!inv[fertInvKey] || inv[fertInvKey] <= 0) {
            return res.json({ success: false, message: 'Sem estoque deste fertilizante!' });
        }

        const seed = SEEDS_CONFIG[tile.planted];
        if (!seed) return res.json({ success: false, message: 'Planta inválida.' });

        const nowMs = Date.now();
        const t1 = (seed.tempo_fase2 || 60) * 1000;
        const t2 = (seed.tempo_fase3 || 60) * 1000;
        const t3 = (seed.tempo_fase4 || 60) * 1000;
        const currentFert = tile.fert_bonus || 0;
        const elapsed = nowMs - (tile.planted_at || nowMs) + currentFert;

        // Determinar fase atual
        let currentPhase;
        if (elapsed < t1) currentPhase = 1;
        else if (elapsed < t1 + t2) currentPhase = 2;
        else currentPhase = 3;

        if (tile.fertPhase === currentPhase) {
            return res.json({ success: false, message: 'Já foi usado um fertilizante nesta fase!' });
        }

        // Calcular redução
        let phaseEnd;
        if (currentPhase === 1) phaseEnd = t1;
        else if (currentPhase === 2) phaseEnd = t1 + t2;
        else phaseEnd = t1 + t2 + t3;

        const phaseRemaining = Math.max(0, phaseEnd - elapsed);
        const realReducao = Math.min(fert.tempo_reducao * 1000, phaseRemaining);

        // Aplicar fertilizante
        tile.fert_bonus = currentFert + realReducao;
        tile.fertPhase = currentPhase;
        inv[fertInvKey]--;
        if (inv[fertInvKey] <= 0) delete inv[fertInvKey];

        // Recalcular estados
        recalculateTileStates(farmData, nowMs);

        const nowSec = Math.floor(nowMs / 1000);
        db.prepare('UPDATE colheita_farms SET farm_data = ?, inventory = ?, last_updated = ? WHERE user_id = ?').run(
            JSON.stringify(farmData), JSON.stringify(inv), nowSec, userId
        );
        res.json({ success: true, inventory: inv, farm_data: farmData, fert_used: fertId, reducao: realReducao });
    } catch(err) {
        console.error('Erro fertilizante colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/colheita/harvest - Colher planta (XP e ouro validados + timestamp verificado)
app.post('/api/colheita/harvest', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { tile_id, amount } = req.body;
        if (tile_id === undefined) return res.json({ success: false, message: 'Tile inválido.' });

        const farm = ensureColheitaFarm(userId);
        const farmData = JSON.parse(farm.farm_data);
        const nowMs = Date.now();

        // Migrar e recalcular estados antes de verificar
        migrateTimestamps(farmData, (farm.last_updated || Math.floor(nowMs / 1000)) * 1000);
        recalculateTileStates(farmData, nowMs);

        const tile = farmData[tile_id];
        if (!tile || !tile.planted) return res.json({ success: false, message: 'Nada plantado neste tile.' });

        // Após recalcular, verificar se o tile está realmente no state 11
        if (tile.state !== 11) return res.json({ success: false, message: 'Planta não está pronta para colher.' });

        const seed = SEEDS_CONFIG[tile.planted];
        if (!seed) return res.json({ success: false, message: 'Planta inválida.' });

        // Validação extra de timestamp: verificar que tempo real passou
        if (tile.planted_at) {
            const t1 = (seed.tempo_fase2 || 60) * 1000;
            const t2 = (seed.tempo_fase3 || 60) * 1000;
            const t3 = (seed.tempo_fase4 || 60) * 1000;
            const totalGrow = t1 + t2 + t3;
            const fert = tile.fert_bonus || 0;
            const realElapsed = nowMs - tile.planted_at + fert;
            if (realElapsed < totalGrow) {
                return res.json({ success: false, message: 'Planta ainda está crescendo!' });
            }
        }

        const maxYield = seed.rendimento || 50;
        const harvested = tile.harvested || 0;
        const available = maxYield - harvested;
        const harvestAmount = Math.min(parseInt(amount) || available, available);
        if (harvestAmount <= 0) return res.json({ success: false, message: 'Nada para colher.' });

        const xpGain = harvestAmount * (seed.estrelas_colheita || 1);
        const ouroGain = harvestAmount * (seed.preco_venda || 1);

        const inv = JSON.parse(farm.inventory);
        inv[tile.planted] = (inv[tile.planted] || 0) + harvestAmount;
        tile.harvested = harvested + harvestAmount;

        // Verificar temporadas
        const maxTemporadas = seed.temporadas || 1;
        const currentSeason = tile.currentSeason || 1;
        if (tile.harvested >= maxYield) {
            if (currentSeason < maxTemporadas) {
                tile.currentSeason = currentSeason + 1;
                tile.harvested = 0;
                tile.state = 10;
                tile.planted_at = nowMs; // Novo ciclo de crescimento
                tile.timer = 0;
                delete tile.harvestable_at;
                delete tile.fertPhase;
                delete tile.fert_bonus;
            }
        }

        const xpResult = addXPToFarm(userId, xpGain);

        const nowSec = Math.floor(nowMs / 1000);
        db.prepare('UPDATE colheita_farms SET farm_data = ?, inventory = ?, ouro = ouro + ?, last_updated = ? WHERE user_id = ?').run(
            JSON.stringify(farmData), JSON.stringify(inv), ouroGain, nowSec, userId
        );

        const updated = ensureColheitaFarm(userId);
        return res.json({
            success: true,
            harvested: harvestAmount,
            xp_gain: xpGain,
            ouro_gain: ouroGain,
            level: updated.level,
            xp: updated.xp,
            ouro: updated.ouro,
            inventory: JSON.parse(updated.inventory),
            farm_data: JSON.parse(updated.farm_data)
        });
    } catch(err) {
        console.error('Erro colheita harvest:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Rate limiter específico para ações de visitante (roubar/ajudar)
const visitorActionLimiter = rateLimit({ windowMs: 1 * 60 * 1000, max: 30, message: { success: false, message: 'Muitas ações. Aguarde.' }, standardHeaders: true, legacyHeaders: false });

// POST /api/colheita/visitor-action - Ações de visitante (roubar/ajudar)
app.post('/api/colheita/visitor-action', requireLogin, visitorActionLimiter, (req, res) => {
    try {
        const { farm_owner_id, type, tile_id } = req.body;
        const visitorId = req.session.userId;
        if (farm_owner_id === visitorId) return res.json({ success: false, message: 'Ação inválida.' });

        // Verificar amizade obrigatória
        const amizade = db.prepare(`SELECT id FROM amizades WHERE status = 'aceita' AND ((remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?))`).get(visitorId, farm_owner_id, farm_owner_id, visitorId);
        if (!amizade) return res.json({ success: false, message: 'Apenas amigos podem interagir.' });

        const farm = ensureColheitaFarm(farm_owner_id);
        const farmData = JSON.parse(farm.farm_data);
        const nowMs = Date.now();
        const nowSec = Math.floor(nowMs / 1000);

        // Recalcular estados antes de ler (autoridade do servidor)
        migrateTimestamps(farmData, (farm.last_updated || nowSec) * 1000);
        recalculateTileStates(farmData, nowMs);

        if (type === 'steal') {
            // Validar tile_id
            const tileIdInt = parseInt(tile_id);
            if (isNaN(tileIdInt) || tileIdInt < 0 || tileIdInt > 23) return res.json({ success: false, msg: 'Tile inválido.' });

            const tile = farmData[tileIdInt];
            // State 11 = frutos maduros (pronto para colheita), não state 4
            if (!tile || tile.state !== 11) return res.json({ success: false, msg: 'Sem frutos para roubar.' });
            if (tile.stolenBy && tile.stolenBy.includes(visitorId)) return res.json({ success: false, msg: 'Você já roubou esta planta!' });

            const seed = SEEDS_CONFIG[tile.planted];
            if (!seed) return res.json({ success: false, msg: 'Planta inválida.' });

            // Rouba a quantidade definida no item (frutos_roubo)
            const stealAmount = seed.frutos_roubo || Math.max(1, Math.floor(seed.rendimento * 0.1));
            if (!tile.stolenBy) tile.stolenBy = [];
            tile.stolenBy.push(visitorId);
            tile.harvested = (tile.harvested || 0) + stealAmount;

            // Salvar farm do dono
            db.prepare('UPDATE colheita_farms SET farm_data = ?, last_updated = ? WHERE user_id = ?').run(
                JSON.stringify(farmData), nowSec, farm_owner_id);

            // Salvar inventário do visitante
            const visitorFarm = ensureColheitaFarm(visitorId);
            const visitorInv = JSON.parse(visitorFarm.inventory);
            visitorInv[tile.planted] = (visitorInv[tile.planted] || 0) + stealAmount;
            addXPToFarm(visitorId, 5);
            db.prepare('UPDATE colheita_farms SET inventory = ? WHERE user_id = ?').run(
                JSON.stringify(visitorInv), visitorId);

            // Log
            db.prepare('INSERT INTO colheita_logs (farm_owner_id, visitor_id, action, details) VALUES (?, ?, ?, ?)').run(
                farm_owner_id, visitorId, 'steal', `Roubou ${stealAmount} ${seed.nome}`
            );

            res.json({ success: true, status: 'ok', amount: stealAmount, plant: tile.planted });
        } else if (type === 'help') {
            // Validar tile_id para a ação de ajuda
            const tileIdInt = parseInt(tile_id);
            if (isNaN(tileIdInt) || tileIdInt < 0 || tileIdInt > 23) return res.json({ success: false, msg: 'Tile inválido.' });

            const tile = farmData[tileIdInt];
            // Só pode ajudar (regar) tiles com grama seca (state 3 ou 4)
            if (!tile || (tile.state !== 3 && tile.state !== 4)) return res.json({ success: false, msg: 'Este tile não precisa de ajuda.' });

            // Verificar se já ajudou este tile (evita XP infinito)
            const alreadyHelped = db.prepare(`SELECT id FROM colheita_logs WHERE visitor_id = ? AND farm_owner_id = ? AND action = 'help' AND details = ? AND created_at >= datetime('now','-3 hours','-1 day')`).get(visitorId, farm_owner_id, `tile_${tileIdInt}`);
            if (alreadyHelped) return res.json({ success: false, msg: 'Você já ajudou este tile hoje.' });

            // Aplicar rega
            tile.state = 2;
            tile.watered_at = nowMs;
            db.prepare('UPDATE colheita_farms SET farm_data = ?, last_updated = ? WHERE user_id = ?').run(
                JSON.stringify(farmData), nowSec, farm_owner_id);

            // Log de ajuda (com tile_id para evitar repetição)
            db.prepare('INSERT INTO colheita_logs (farm_owner_id, visitor_id, action, details) VALUES (?, ?, ?, ?)').run(
                farm_owner_id, visitorId, 'help', `tile_${tileIdInt}`
            );
            // +1 XP para o visitante
            addXPToFarm(visitorId, 1);
            res.json({ success: true, status: 'ok' });
        } else {
            res.json({ success: false, message: 'Ação desconhecida.' });
        }
    } catch(err) {
        console.error('Erro visitor action colheita:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/colheita/sync/:uid - Sincronizar fazenda (visitante polling)
app.get('/api/colheita/sync/:uid', requireLogin, (req, res) => {
    try {
        const farm = ensureColheitaFarm(req.params.uid);
        const farmData = JSON.parse(farm.farm_data);
        const nowMs = Date.now();
        migrateTimestamps(farmData, (farm.last_updated || Math.floor(nowMs / 1000)) * 1000);
        recalculateTileStates(farmData, nowMs);
        res.json({
            success: true,
            farm_data: farmData,
            last_updated: farm.last_updated,
            server_now: Math.floor(nowMs / 1000)
        });
    } catch(err) {
        console.error('Erro sync colheita:', err);
        res.json({ success: false });
    }
});

// GET /api/colheita/seeds-config - Obter configuração de sementes
app.get('/api/colheita/seeds-config', requireLogin, (req, res) => {
    res.json({ success: true, seeds: SEEDS_CONFIG });
});

// GET /api/colheita/fertilizers-config - Obter configuração de fertilizantes
app.get('/api/colheita/fertilizers-config', requireLogin, (req, res) => {
    res.json({ success: true, fertilizers: FERTILIZERS_CONFIG });
});

// ===== COMUNIDADES - API =====

// GET /api/user-comunidades/:uid - Listar comunidades de um usuário
app.get('/api/user-comunidades/:uid', requireLogin, (req, res) => {
    try {
        const uid = String(req.params.uid);
        const userId = String(req.session.userId);
        const isOwner = (uid === userId);

        // Verificar bloqueio
        if (!isOwner && isBlocked(userId, String(uid))) {
            return res.json({ success: false, message: 'Usuário não encontrado.', blocked: true });
        }

        const perfil = db.prepare('SELECT id, nome, foto_perfil, sexo FROM usuarios WHERE id = ?').get(uid);
        if (!perfil) return res.json({ success: false, message: 'Usuário não encontrado.' });

        // Comunidades que é dono
        const owned = db.prepare(`
            SELECT c.*, 
                (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as membros
            FROM comunidades c 
            WHERE c.dono_id = ? 
            ORDER BY c.criado_em DESC
        `).all(uid);

        // Comunidades que participa (mas não é dono)
        const joined = db.prepare(`
            SELECT c.*, cm.entrou_em as membro_desde,
                (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as membros
            FROM comunidade_membros cm 
            JOIN comunidades c ON c.id = cm.comunidade_id 
            WHERE cm.usuario_id = ? AND c.dono_id != ?
            ORDER BY cm.entrou_em DESC
        `).all(uid, uid);

        const total = owned.length + joined.length;

        return res.json({ success: true, owned, joined, perfil, isOwner, total });
    } catch (err) {
        console.error('Erro ao listar comunidades:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id - Detalhes de uma comunidade
app.get('/api/comunidade/:id', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const tzOff = getTzOffset();
        const comm = db.prepare(`
            SELECT c.*, datetime(c.criado_em, '${tzOff}') as criado_em_local, u.nome as dono_nome, u.foto_perfil as dono_foto
            FROM comunidades c
            LEFT JOIN usuarios u ON u.id = c.dono_id
            WHERE c.id = ?
        `).get(commId);

        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se o usuário está bloqueado desta comunidade
        const isBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (isBanned) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        // Contar membros
        const membrosCount = db.prepare('SELECT COUNT(*) as total FROM comunidade_membros WHERE comunidade_id = ?').get(commId).total;

        // Verificar se o usuário logado é membro
        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isMember = !!membership;
        const isOwner = String(comm.dono_id) === String(userId);
        const cargo = membership ? membership.cargo : null;

        // Verificar se tem solicitação pendente
        const isPending = !isMember && !!db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);

        // Contar solicitações pendentes (para badge no menu - dono/moderador)
        const pendingCount = (isOwner || (cargo === 'moderador')) ? db.prepare('SELECT COUNT(*) as c FROM comunidade_pendentes WHERE comunidade_id = ?').get(commId).c : 0;

        // Pegar últimos 9 membros (para sidebar)
        const membros = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ?
            ORDER BY cm.entrou_em DESC
            LIMIT 9
        `).all(commId);

        // Pegar moderadores (nomes para info table)
        const moderadores = db.prepare(`
            SELECT u.id, u.nome
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ? AND cm.cargo = 'moderador'
            ORDER BY u.nome
        `).all(commId);

        return res.json({
            success: true,
            community: {
                id: comm.id,
                nome: comm.nome,
                descricao: comm.descricao,
                categoria: comm.categoria,
                tipo: comm.tipo,
                foto: comm.foto,
                idioma: comm.idioma || 'Português',
                local: comm.local_text || 'Brasil',
                criado_em: comm.criado_em_local || comm.criado_em,
                dono_id: comm.dono_id,
                dono_nome: comm.dono_nome || 'Usuário Excluído',
                dono_foto: comm.dono_foto
            },
            membrosCount,
            membros,
            moderadores,
            isMember,
            isOwner,
            isPending,
            pendingCount,
            cargo
        });
    } catch (err) {
        console.error('Erro ao carregar comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/membros - Lista todos os membros de uma comunidade
app.get('/api/comunidade/:id/membros', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const tzOff = getTzOffset();

        const comm = db.prepare(`
            SELECT c.*, u.nome as dono_nome, u.foto_perfil as dono_foto
            FROM comunidades c
            LEFT JOIN usuarios u ON u.id = c.dono_id
            WHERE c.id = ?
        `).get(commId);

        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se o usuário está bloqueado desta comunidade
        const isBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (isBanned) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        const membrosCount = db.prepare('SELECT COUNT(*) as total FROM comunidade_membros WHERE comunidade_id = ?').get(commId).total;
        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isMember = !!membership;
        const isOwner = String(comm.dono_id) === String(userId);
        const cargo = membership ? membership.cargo : null;

        // Verificar se tem solicitação pendente
        const isPending = !isMember && !!db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const pendingCount = (isOwner || (cargo === 'moderador')) ? db.prepare('SELECT COUNT(*) as c FROM comunidade_pendentes WHERE comunidade_id = ?').get(commId).c : 0;

        // Pegar TODOS os membros
        const membros = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil, cm.cargo, datetime(cm.entrou_em, '${tzOff}') as entrou_em
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ?
            ORDER BY cm.entrou_em DESC
        `).all(commId);

        return res.json({
            success: true,
            community: {
                id: comm.id,
                nome: comm.nome,
                descricao: comm.descricao,
                categoria: comm.categoria,
                tipo: comm.tipo,
                foto: comm.foto,
                idioma: comm.idioma || 'Português',
                local: comm.local_text || 'Brasil',
                dono_id: comm.dono_id,
                dono_nome: comm.dono_nome || 'Usuário Excluído',
                dono_foto: comm.dono_foto
            },
            membrosCount,
            membros,
            isMember,
            isOwner,
            isPending,
            pendingCount,
            cargo: membership ? membership.cargo : null,
            loggedUserId: userId
        });
    } catch (err) {
        console.error('Erro ao listar membros:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/expulsar - Expulsar membro da comunidade (dono ou moderador)
app.post('/api/comunidade/:id/expulsar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { membro_id } = req.body;

        if (!membro_id) return res.json({ success: false, message: 'ID do membro não informado.' });

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const isOwner = String(comm.dono_id) === String(userId);
        const myMembership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isModerador = myMembership && myMembership.cargo === 'moderador';

        if (!isOwner && !isModerador) {
            return res.json({ success: false, message: 'Sem permissão para expulsar membros.' });
        }

        // Não pode expulsar o dono
        if (String(membro_id) === String(comm.dono_id)) {
            return res.json({ success: false, message: 'Não é possível expulsar o dono da comunidade.' });
        }

        // Não pode expulsar moderadores — primeiro tire o cargo na página de staff
        const targetMembership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, membro_id);
        if (targetMembership && targetMembership.cargo === 'moderador') {
            return res.json({ success: false, message: 'Não é possível expulsar um moderador. Remova o cargo primeiro na página de staff.' });
        }

        // Verificar se o membro existe na comunidade
        const targetExists = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, membro_id);
        if (!targetExists) {
            return res.json({ success: false, message: 'Este usuário não é membro da comunidade.' });
        }

        db.prepare('DELETE FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').run(commId, membro_id);

        // Bloquear membro se solicitado
        const { bloquear } = req.body;
        if (bloquear) {
            const alreadyBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, membro_id);
            if (!alreadyBanned) {
                db.prepare('INSERT INTO comunidade_bans (comunidade_id, usuario_id, banido_por) VALUES (?, ?, ?)').run(commId, membro_id, userId);
            }
            // Limpar eventual solicitação pendente
            db.prepare('DELETE FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').run(commId, membro_id);
        }

        return res.json({ success: true, message: bloquear ? 'Membro expulso e bloqueado com sucesso.' : 'Membro expulso com sucesso.' });
    } catch (err) {
        console.error('Erro ao expulsar membro:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/bans - Listar membros banidos (apenas dono)
app.get('/api/comunidade/:id/bans', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const isOwner = String(comm.dono_id) === String(userId);
        if (!isOwner) return res.json({ success: false, message: 'Apenas o dono pode acessar as configurações.' });

        const bans = db.prepare(`
            SELECT cb.id, cb.usuario_id, cb.banido_por, cb.motivo, cb.banido_em,
                   u.nome as usuario_nome, u.foto_perfil as usuario_foto,
                   u2.nome as banido_por_nome
            FROM comunidade_bans cb
            JOIN usuarios u ON u.id = cb.usuario_id
            LEFT JOIN usuarios u2 ON u2.id = cb.banido_por
            WHERE cb.comunidade_id = ?
            ORDER BY cb.banido_em DESC
        `).all(commId);

        const membrosCount = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(commId).c;

        // Listar moderadores para o dropdown de transferência de posse
        const moderadores = db.prepare(`SELECT u.id, u.nome FROM comunidade_membros cm JOIN usuarios u ON u.id = cm.usuario_id WHERE cm.comunidade_id = ? AND cm.cargo = 'moderador' ORDER BY u.nome`).all(commId);

        return res.json({
            success: true,
            bans,
            community: { id: comm.id, nome: comm.nome, foto: comm.foto, dono_id: comm.dono_id, tipo: comm.tipo, excluir_em: comm.excluir_em },
            membrosCount,
            moderadores,
            isOwner: true
        });
    } catch (err) {
        console.error('Erro ao listar bans:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/desbanir - Desbanir membro (apenas dono)
app.post('/api/comunidade/:id/desbanir', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { usuario_id } = req.body;

        if (!usuario_id) return res.json({ success: false, message: 'ID do usuário não informado.' });

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const isOwner = String(comm.dono_id) === String(userId);
        if (!isOwner) return res.json({ success: false, message: 'Apenas o dono pode desbanir membros.' });

        const ban = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, usuario_id);
        if (!ban) return res.json({ success: false, message: 'Este usuário não está banido.' });

        db.prepare('DELETE FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').run(commId, usuario_id);

        return res.json({ success: true, message: 'Usuário desbanido com sucesso.' });
    } catch (err) {
        console.error('Erro ao desbanir membro:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/pendentes - Listar solicitações pendentes (dono ou moderador)
app.get('/api/comunidade/:id/pendentes', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const tzOff = getTzOffset();

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isOwner = String(comm.dono_id) === String(userId);
        const isModerator = membership && membership.cargo === 'moderador';
        if (!isOwner && !isModerator) return res.json({ success: false, message: 'Apenas o dono ou moderadores podem ver solicitações.' });

        const pendentes = db.prepare(`
            SELECT cp.id, cp.usuario_id, cp.mensagem, datetime(cp.solicitado_em, '${tzOff}') as solicitado_em,
                   u.nome, u.foto_perfil
            FROM comunidade_pendentes cp
            JOIN usuarios u ON u.id = cp.usuario_id
            WHERE cp.comunidade_id = ?
            ORDER BY cp.solicitado_em ASC
        `).all(commId);

        const membrosCount = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(commId).c;

        return res.json({
            success: true,
            pendentes,
            community: { id: comm.id, nome: comm.nome, foto: comm.foto, tipo: comm.tipo, dono_id: comm.dono_id },
            membrosCount,
            isOwner,
            isModerator
        });
    } catch (err) {
        console.error('Erro ao listar pendentes:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/pendentes/aprovar - Aprovar solicitação
app.post('/api/comunidade/:id/pendentes/aprovar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { usuario_id } = req.body;

        if (!usuario_id) return res.json({ success: false, message: 'ID do usuário não informado.' });

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isOwner = String(comm.dono_id) === String(userId);
        const isModerator = membership && membership.cargo === 'moderador';
        if (!isOwner && !isModerator) return res.json({ success: false, message: 'Sem permissão.' });

        const pending = db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(commId, usuario_id);
        if (!pending) return res.json({ success: false, message: 'Solicitação não encontrada.' });

        // Remover da pendentes e adicionar como membro
        db.prepare('DELETE FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').run(commId, usuario_id);
        db.prepare('INSERT OR IGNORE INTO comunidade_membros (comunidade_id, usuario_id) VALUES (?, ?)').run(commId, usuario_id);

        // Notificar o usuário aprovado
        db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id) VALUES (?, 'comunidade_aprovado', ?, ?, ?, ?)`).run(
            usuario_id,
            'Solicitação aprovada!',
            'Sua solicitação para entrar na comunidade "' + comm.nome + '" foi aprovada. Você agora é membro!',
            '/comunidades.php?id=' + commId,
            userId
        );

        return res.json({ success: true, message: 'Membro aprovado com sucesso!' });
    } catch (err) {
        console.error('Erro ao aprovar membro:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/pendentes/rejeitar - Rejeitar solicitação
app.post('/api/comunidade/:id/pendentes/rejeitar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { usuario_id, bloquear } = req.body;

        if (!usuario_id) return res.json({ success: false, message: 'ID do usuário não informado.' });

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isOwner = String(comm.dono_id) === String(userId);
        const isModerator = membership && membership.cargo === 'moderador';
        if (!isOwner && !isModerator) return res.json({ success: false, message: 'Sem permissão.' });

        const pending = db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(commId, usuario_id);
        if (!pending) return res.json({ success: false, message: 'Solicitação não encontrada.' });

        db.prepare('DELETE FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').run(commId, usuario_id);

        // Bloquear usuário se solicitado
        if (bloquear) {
            const alreadyBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, usuario_id);
            if (!alreadyBanned) {
                db.prepare('INSERT INTO comunidade_bans (comunidade_id, usuario_id, banido_por) VALUES (?, ?, ?)').run(commId, usuario_id, userId);
            }
        }

        // Notificar o usuário rejeitado
        db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id) VALUES (?, 'comunidade_rejeitado', ?, ?, ?, ?)`).run(
            usuario_id,
            'Solicitação recusada',
            'Sua solicitação para entrar na comunidade "' + comm.nome + '" foi recusada.',
            '/comunidades.php?id=' + commId,
            userId
        );

        return res.json({ success: true, message: 'Solicitação rejeitada.' });
    } catch (err) {
        console.error('Erro ao rejeitar solicitação:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/pendentes/cancelar - Cancelar própria solicitação
app.post('/api/comunidade/:id/pendentes/cancelar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const pending = db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (!pending) return res.json({ success: false, message: 'Nenhuma solicitação pendente encontrada.' });

        db.prepare('DELETE FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').run(commId, userId);

        return res.json({ success: true, message: 'Solicitação cancelada.' });
    } catch (err) {
        console.error('Erro ao cancelar solicitação:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/criar - Criar nova comunidade
app.post('/api/comunidades/criar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { nome, descricao, categoria, tipo, idioma, local, foto_base64 } = req.body;

        if (!nome || nome.trim().length < 3) {
            return res.json({ success: false, message: 'O nome deve ter pelo menos 3 caracteres.' });
        }
        if (nome.trim().length > 100) {
            return res.json({ success: false, message: 'O nome deve ter no máximo 100 caracteres.' });
        }

        // Verificar se já existe comunidade com esse nome
        const existing = db.prepare('SELECT id FROM comunidades WHERE LOWER(nome) = LOWER(?)').get(nome.trim());
        if (existing) {
            return res.json({ success: false, message: 'Já existe uma comunidade com esse nome.' });
        }

        const cat = (categoria || 'Geral').trim();
        const tipoVal = (tipo === 'privada') ? 'privada' : 'publica';
        const idiomaVal = (idioma || 'Português').trim();
        const localVal = (local || 'Brasil').trim();

        // Processar foto base64
        let fotoPath = null;
        if (foto_base64 && foto_base64.startsWith('data:image/')) {
            const fs = require('fs'); // redundante mas mantido por segurança
            const path = require('path'); // redundante mas mantido por segurança
            const matches = foto_base64.match(/^data:image\/(jpeg|png|jpg);base64,(.+)$/);
            if (matches) {
                const ext = matches[1] === 'png' ? 'png' : 'jpg';
                const buffer = Buffer.from(matches[2], 'base64');
                if (buffer.length > 2 * 1024 * 1024) {
                    return res.json({ success: false, message: 'Imagem muito grande. Máximo 2MB.' });
                }
                const filename = `comm_${Date.now()}_${userId}.${ext}`;
                const uploadDir = path.join(__dirname, 'uploads');
                if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });
                fs.writeFileSync(path.join(uploadDir, filename), buffer);
                fotoPath = `/uploads/${filename}`;
            }
        }

        const result = db.prepare('INSERT INTO comunidades (nome, descricao, categoria, tipo, foto, idioma, local_text, dono_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)').run(
            nome.trim(), (descricao || '').trim(), cat, tipoVal, fotoPath, idiomaVal, localVal, userId
        );
        const commId = result.lastInsertRowid;

        // Dono entra automaticamente como membro
        db.prepare('INSERT INTO comunidade_membros (comunidade_id, usuario_id, cargo) VALUES (?, ?, ?)').run(commId, userId, 'dono');

        return res.json({ success: true, id: commId, message: 'Comunidade criada com sucesso!' });
    } catch (err) {
        console.error('Erro ao criar comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/entrar - Entrar em uma comunidade
app.post('/api/comunidades/entrar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id } = req.body;

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se está bloqueado
        const isBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, userId);
        if (isBanned) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        const already = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, userId);
        if (already) return res.json({ success: false, message: 'Você já é membro desta comunidade.' });

        // Comunidade privada: criar solicitação pendente
        if (comm.tipo === 'privada') {
            const alreadyPending = db.prepare('SELECT id FROM comunidade_pendentes WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, userId);
            if (alreadyPending) return res.json({ success: false, message: 'Sua solicitação já foi enviada. Aguarde aprovação.' });

            const mensagemPendente = (req.body.mensagem || '').trim().substring(0, 500);
            db.prepare('INSERT INTO comunidade_pendentes (comunidade_id, usuario_id, mensagem) VALUES (?, ?, ?)').run(comunidade_id, userId, mensagemPendente);
            return res.json({ success: true, message: 'Solicitação enviada! Aguarde aprovação.', pending: true });
        }

        db.prepare('INSERT INTO comunidade_membros (comunidade_id, usuario_id) VALUES (?, ?)').run(comunidade_id, userId);
        return res.json({ success: true, message: 'Você entrou na comunidade!' });
    } catch (err) {
        console.error('Erro ao entrar na comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/sair - Sair de uma comunidade
app.post('/api/comunidades/sair', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id } = req.body;

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        if (comm.dono_id === userId) {
            return res.json({ success: false, message: 'O dono não pode sair da comunidade. Delete-a ao invés disso.' });
        }

        db.prepare('DELETE FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').run(comunidade_id, userId);
        return res.json({ success: true, message: 'Você saiu da comunidade.' });
    } catch (err) {
        console.error('Erro ao sair da comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/transferir - Transferir posse e sair
app.post('/api/comunidades/transferir', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id, novo_dono_id, senha } = req.body;

        if (!comunidade_id || !novo_dono_id) {
            return res.json({ success: false, message: 'Dados incompletos.' });
        }

        if (!senha) {
            return res.json({ success: false, message: 'Digite sua senha para confirmar a transferência.' });
        }

        // Verificar senha do usuário
        const user = db.prepare('SELECT senha FROM usuarios WHERE id = ?').get(String(userId));
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        const senhaCorreta = bcrypt.compareSync(senha, user.senha);
        if (!senhaCorreta) {
            return res.json({ success: false, message: 'Senha incorreta.' });
        }

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        if (String(comm.dono_id) !== String(userId)) {
            return res.json({ success: false, message: 'Apenas o dono pode transferir a posse.' });
        }

        if (String(novo_dono_id) === String(userId)) {
            return res.json({ success: false, message: 'Você já é o dono.' });
        }

        // Verificar se o novo dono é moderador
        const membroInfo = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, String(novo_dono_id));
        if (!membroInfo) {
            return res.json({ success: false, message: 'O usuário precisa ser membro da comunidade.' });
        }
        if (membroInfo.cargo !== 'moderador') {
            return res.json({ success: false, message: 'A posse só pode ser transferida para um moderador.' });
        }

        // Transferir: atualizar dono_id na tabela comunidades
        db.prepare('UPDATE comunidades SET dono_id = ? WHERE id = ?').run(String(novo_dono_id), comunidade_id);

        // Atualizar cargo do novo dono para 'dono'
        db.prepare('UPDATE comunidade_membros SET cargo = ? WHERE comunidade_id = ? AND usuario_id = ?').run('dono', comunidade_id, String(novo_dono_id));

        // Atualizar cargo do antigo dono para 'membro'
        db.prepare('UPDATE comunidade_membros SET cargo = ? WHERE comunidade_id = ? AND usuario_id = ?').run('membro', comunidade_id, String(userId));

        // Remover o antigo dono da comunidade (ele está "saindo")
        db.prepare('DELETE FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').run(comunidade_id, String(userId));

        return res.json({ success: true, message: 'Posse transferida com sucesso! Você saiu da comunidade.' });
    } catch (err) {
        console.error('Erro ao transferir posse:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/excluir - Agendar exclusão da comunidade em 24h (somente dono, com senha)
app.post('/api/comunidades/excluir', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id, senha } = req.body;

        if (!senha) {
            return res.json({ success: false, message: 'Digite sua senha para confirmar.' });
        }

        const user = db.prepare('SELECT senha FROM usuarios WHERE id = ?').get(userId);
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        const senhaCorreta = bcrypt.compareSync(senha, user.senha);
        if (!senhaCorreta) {
            return res.json({ success: false, message: 'Senha incorreta! A exclusão não foi iniciada.' });
        }

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode excluir a comunidade.' });

        if (comm.excluir_em) {
            return res.json({ success: false, message: 'A exclusão já foi agendada.' });
        }

        // Agendar exclusão para 24h a partir de agora
        db.prepare(`UPDATE comunidades SET excluir_em = datetime(${agora()}, '+24 hours') WHERE id = ?`).run(comunidade_id);

        return res.json({ success: true, message: 'Exclusão agendada! A comunidade será removida permanentemente em 24 horas.' });
    } catch (err) {
        console.error('Erro ao agendar exclusão de comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/cancelar-exclusao - Cancelar exclusão agendada (somente dono)
app.post('/api/comunidades/cancelar-exclusao', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id } = req.body;

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode cancelar a exclusão.' });

        if (!comm.excluir_em) {
            return res.json({ success: false, message: 'Não há exclusão agendada.' });
        }

        db.prepare('UPDATE comunidades SET excluir_em = NULL WHERE id = ?').run(comunidade_id);

        return res.json({ success: true, message: 'Exclusão cancelada com sucesso!' });
    } catch (err) {
        console.error('Erro ao cancelar exclusão:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/editar - Editar comunidade (somente dono)
app.post('/api/comunidades/editar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id, nome, categoria, idioma, tipo, local, descricao, foto_base64 } = req.body;

        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode editar a comunidade.' });

        if (!nome || nome.trim().length < 3) return res.json({ success: false, message: 'Nome deve ter pelo menos 3 caracteres.' });

        let fotoPath = comm.foto;
        if (foto_base64 && foto_base64.startsWith('data:image')) {
            const base64Data = foto_base64.replace(/^data:image\/\w+;base64,/, '');
            const buffer = Buffer.from(base64Data, 'base64');
            const filename = 'cmm_' + Date.now() + '.jpg';
            const commsDir = path.join(__dirname, 'uploads', 'comunidades');
            if (!fs.existsSync(commsDir)) fs.mkdirSync(commsDir, { recursive: true });
            fs.writeFileSync(path.join(commsDir, filename), buffer);
            fotoPath = '/uploads/comunidades/' + filename;
        }

        const tipoNorm = (tipo === 'privada' || tipo === 'Privada') ? 'privada' : 'publica';
        db.prepare(`
            UPDATE comunidades SET nome = ?, descricao = ?, categoria = ?, tipo = ?, foto = ?, idioma = ?, local_text = ?
            WHERE id = ?
        `).run(nome.trim(), descricao || '', categoria || 'Geral', tipoNorm, fotoPath, idioma || 'Português', local || 'Brasil', comunidade_id);

        return res.json({ success: true, message: 'Comunidade atualizada com sucesso!' });
    } catch (err) {
        console.error('Erro ao editar comunidade:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/amigos-para-convidar - Lista amigos que NÃO são membros da comunidade
app.get('/api/comunidade/:id/amigos-para-convidar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT id, nome, foto FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se o usuário é membro
        const membership = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (!membership) return res.json({ success: false, message: 'Você precisa ser membro para convidar amigos.' });

        // Contar total de membros
        const membrosCount = db.prepare('SELECT COUNT(*) as total FROM comunidade_membros WHERE comunidade_id = ?').get(commId).total;

        // Buscar amigos que NÃO são membros desta comunidade
        const amigos = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM amizades a
            JOIN usuarios u ON (
                CASE
                    WHEN a.remetente_id = ? THEN u.id = a.destinatario_id
                    WHEN a.destinatario_id = ? THEN u.id = a.remetente_id
                END
            )
            WHERE a.status = 'aceita' AND (a.remetente_id = ? OR a.destinatario_id = ?)
              AND u.id NOT IN (SELECT usuario_id FROM comunidade_membros WHERE comunidade_id = ?)
              AND u.id NOT IN (SELECT usuario_id FROM comunidade_bans WHERE comunidade_id = ?)
              AND u.id NOT IN ${blockedIdsSubquery(userId)}
            ORDER BY u.nome ASC
        `).all(userId, userId, userId, userId, commId, commId);

        return res.json({
            success: true,
            community: comm,
            membrosCount,
            amigos
        });
    } catch (err) {
        console.error('Erro ao listar amigos para convidar:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidades/convidar - Enviar convites (notificações) para amigos
app.post('/api/comunidades/convidar', requireLogin, (req, res) => {
    try {
        const userId = req.session.userId;
        const { comunidade_id, amigos_ids } = req.body;

        if (!comunidade_id || !amigos_ids || !Array.isArray(amigos_ids) || amigos_ids.length === 0) {
            return res.json({ success: false, message: 'Selecione pelo menos um amigo.' });
        }

        const comm = db.prepare('SELECT id, nome FROM comunidades WHERE id = ?').get(comunidade_id);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se o usuário é membro
        const membership = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, userId);
        if (!membership) return res.json({ success: false, message: 'Você precisa ser membro para convidar amigos.' });

        const userName = db.prepare('SELECT nome FROM usuarios WHERE id = ?').get(userId);
        let convidados = 0;

        for (const amigoId of amigos_ids) {
            // Verificar se já é membro
            const jaMembro = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidade_id, String(amigoId));
            if (jaMembro) continue;

            // Verificar se é realmente amigo
            const isAmigo = db.prepare(`SELECT id FROM amizades WHERE status = 'aceita' AND ((remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?))`).get(userId, String(amigoId), String(amigoId), userId);
            if (!isAmigo) continue;

            // Verificar se já existe convite pendente (não lido) para evitar spam
            const jaConvidado = db.prepare(`SELECT id FROM notificacoes WHERE usuario_id = ? AND tipo = 'convite_comunidade' AND link LIKE ? AND lida = 0`).get(String(amigoId), 'comunidades.php?id=' + comunidade_id + '%');
            if (jaConvidado) continue;

            // Criar notificação de convite (NÃO adiciona como membro)
            const insertResult = db.prepare(`INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link, remetente_id) VALUES (?, 'convite_comunidade', ?, ?, ?, ?)`).run(
                String(amigoId),
                (userName ? userName.nome : 'Alguém'),
                'Convidou você para a comunidade "' + comm.nome + '".',
                'comunidades.php?id=' + comunidade_id,
                userId
            );
            // Atualizar link com read_notif para marcar como lida ao clicar
            const notifId = insertResult.lastInsertRowid;
            db.prepare('UPDATE notificacoes SET link = ? WHERE id = ?').run(
                'comunidades.php?id=' + comunidade_id + '&read_notif=' + notifId,
                notifId
            );

            convidados++;
        }

        if (convidados === 0) {
            return res.json({ success: false, message: 'Nenhum convite enviado (todos já são membros ou já foram convidados).' });
        }

        return res.json({ success: true, message: convidados + (convidados === 1 ? ' convite enviado' : ' convites enviados') + ' com sucesso!' });
    } catch (err) {
        console.error('Erro ao convidar amigos:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== FÓRUM DE COMUNIDADES =====

// Listar tópicos do fórum de uma comunidade
app.get('/api/forum/:comunidadeId/topicos', requireLogin, (req, res) => {
    try {
        const comunidadeId = req.params.comunidadeId;
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;

        // Verificar se comunidade existe
        const comm = db.prepare('SELECT id, nome, foto, dono_id, tipo FROM comunidades WHERE id = ?').get(comunidadeId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se está bloqueado
        const isBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(comunidadeId, String(req.session.userId));
        if (isBanned) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        // Verificar se é membro
        const membro = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidadeId, String(req.session.userId));

        // Comunidade privada: apenas membros podem ver o fórum
        if (comm.tipo === 'privada' && !membro && String(comm.dono_id) !== String(req.session.userId)) {
            return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar o fórum.' });
        }

        const totalMembros = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(comunidadeId).c;
        comm.total_membros = totalMembros;

        const isOwner = String(comm.dono_id) === String(req.session.userId);

        const total = db.prepare('SELECT COUNT(*) as c FROM forum_topicos WHERE comunidade_id = ?').get(comunidadeId).c;

        const topicos = db.prepare(`
            SELECT t.*, u.nome as autor_nome, u.foto_perfil as autor_foto,
                   (SELECT COUNT(*) FROM forum_respostas WHERE topico_id = t.id) as total_respostas,
                   (SELECT u2.nome FROM forum_respostas r2 JOIN usuarios u2 ON u2.id = r2.autor_id WHERE r2.topico_id = t.id ORDER BY r2.criado_em DESC LIMIT 1) as ultima_resposta_autor,
                   (SELECT r3.criado_em FROM forum_respostas r3 WHERE r3.topico_id = t.id ORDER BY r3.criado_em DESC LIMIT 1) as ultima_resposta_data
            FROM forum_topicos t
            JOIN usuarios u ON u.id = t.autor_id
            WHERE t.comunidade_id = ?
            ORDER BY t.fixado DESC, t.ultima_resposta_em DESC
            LIMIT ? OFFSET ?
        `).all(comunidadeId, limit, offset);

        res.json({
            success: true,
            comunidade: comm,
            topicos,
            isMembro: !!membro,
            isOwner,
            page,
            totalPages: Math.ceil(total / limit),
            total
        });
    } catch(err) {
        console.error('Erro listar tópicos:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Criar tópico
app.post('/api/forum/:comunidadeId/topico/criar', requireLogin, (req, res) => {
    try {
        const comunidadeId = req.params.comunidadeId;
        const { titulo, mensagem } = req.body;
        if (!titulo || !mensagem) return res.json({ success: false, message: 'Preencha título e mensagem.' });
        if (titulo.length > 150) return res.json({ success: false, message: 'Título muito longo (máx 150).' });
        if (mensagem.length > 5000) return res.json({ success: false, message: 'Mensagem muito longa.' });

        // Verificar se é membro
        const membro = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(comunidadeId, String(req.session.userId));
        if (!membro) return res.json({ success: false, message: 'Você precisa ser membro da comunidade.' });

        const result = db.prepare(`INSERT INTO forum_topicos (comunidade_id, autor_id, titulo, criado_em, ultima_resposta_em) VALUES (?, ?, ?, ${agora()}, ${agora()})`).run(comunidadeId, String(req.session.userId), titulo);
        const topicoId = result.lastInsertRowid;

        // Primeira resposta = mensagem do tópico
        db.prepare(`INSERT INTO forum_respostas (topico_id, autor_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`).run(topicoId, String(req.session.userId), mensagem);

        res.json({ success: true, id: topicoId });
    } catch(err) {
        console.error('Erro criar tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Ver tópico com respostas
app.get('/api/forum/topico/:topicoId', requireLogin, (req, res) => {
    try {
        const topicoId = req.params.topicoId;
        const page = parseInt(req.query.page) || 1;
        const limit = 20;
        const offset = (page - 1) * limit;

        const topico = db.prepare(`
            SELECT t.*, u.nome as autor_nome, u.foto_perfil as autor_foto, c.nome as comunidade_nome, c.id as comunidade_id, c.foto as comunidade_foto, c.dono_id, c.tipo as comunidade_tipo
            FROM forum_topicos t
            JOIN usuarios u ON u.id = t.autor_id
            JOIN comunidades c ON c.id = t.comunidade_id
            WHERE t.id = ?
        `).get(topicoId);

        if (!topico) return res.json({ success: false, message: 'Tópico não encontrado.' });

        // Verificar se é membro
        const membro = db.prepare('SELECT id, cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(topico.comunidade_id, String(req.session.userId));

        // Comunidade privada: apenas membros podem ver tópicos
        if (topico.comunidade_tipo === 'privada' && !membro && String(topico.dono_id) !== String(req.session.userId)) {
            return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar o fórum.' });
        }

        const totalRespostas = db.prepare('SELECT COUNT(*) as c FROM forum_respostas WHERE topico_id = ?').get(topicoId).c;

        const respostas = db.prepare(`
            SELECT r.*, u.nome as autor_nome, u.foto_perfil as autor_foto, u.sexo as autor_sexo,
                   (SELECT COUNT(*) FROM forum_respostas WHERE topico_id = r.topico_id AND autor_id = r.autor_id) as total_posts_autor
            FROM forum_respostas r
            JOIN usuarios u ON u.id = r.autor_id
            WHERE r.topico_id = ?
            ORDER BY r.criado_em ASC
            LIMIT ? OFFSET ?
        `).all(topicoId, limit, offset);

        const isOwner = topico.dono_id === String(req.session.userId);
        const isAutor = topico.autor_id === String(req.session.userId);
        const totalMembros = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(topico.comunidade_id).c;

        res.json({
            success: true,
            topico,
            respostas,
            isMembro: !!membro,
            isOwner,
            isAutor,
            page,
            totalPages: Math.ceil(totalRespostas / limit),
            totalRespostas,
            totalMembros
        });
    } catch(err) {
        console.error('Erro ver tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Responder tópico
app.post('/api/forum/topico/:topicoId/responder', requireLogin, (req, res) => {
    try {
        const topicoId = req.params.topicoId;
        const { mensagem } = req.body;
        if (!mensagem || !mensagem.trim()) return res.json({ success: false, message: 'Digite uma mensagem.' });
        if (mensagem.length > 5000) return res.json({ success: false, message: 'Mensagem muito longa.' });

        const topico = db.prepare('SELECT * FROM forum_topicos WHERE id = ?').get(topicoId);
        if (!topico) return res.json({ success: false, message: 'Tópico não encontrado.' });
        if (topico.trancado) return res.json({ success: false, message: 'Este tópico está trancado.' });

        // Verificar se é membro
        const membro = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(topico.comunidade_id, String(req.session.userId));
        if (!membro) return res.json({ success: false, message: 'Você precisa ser membro da comunidade.' });

        db.prepare(`INSERT INTO forum_respostas (topico_id, autor_id, mensagem, criado_em) VALUES (?, ?, ?, ${agora()})`).run(topicoId, String(req.session.userId), mensagem);

        // Atualizar ultima_resposta_em
        db.prepare(`UPDATE forum_topicos SET ultima_resposta_em = ${agora()} WHERE id = ?`).run(topicoId);

        res.json({ success: true });
    } catch(err) {
        console.error('Erro responder tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir tópico (dono da comunidade ou autor do tópico)
app.post('/api/forum/topico/:topicoId/excluir', requireLogin, (req, res) => {
    try {
        const topicoId = req.params.topicoId;
        const topico = db.prepare(`SELECT t.*, c.dono_id FROM forum_topicos t JOIN comunidades c ON c.id = t.comunidade_id WHERE t.id = ?`).get(topicoId);
        if (!topico) return res.json({ success: false, message: 'Tópico não encontrado.' });

        const userId = String(req.session.userId);
        if (topico.autor_id !== userId && topico.dono_id !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM forum_respostas WHERE topico_id = ?').run(topicoId);
        db.prepare('DELETE FROM forum_topicos WHERE id = ?').run(topicoId);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir resposta (dono da comunidade ou autor da resposta)
app.post('/api/forum/resposta/:respostaId/excluir', requireLogin, (req, res) => {
    try {
        const respostaId = req.params.respostaId;
        const resposta = db.prepare(`
            SELECT r.*, t.comunidade_id, c.dono_id
            FROM forum_respostas r
            JOIN forum_topicos t ON t.id = r.topico_id
            JOIN comunidades c ON c.id = t.comunidade_id
            WHERE r.id = ?
        `).get(respostaId);
        if (!resposta) return res.json({ success: false, message: 'Resposta não encontrada.' });

        const userId = String(req.session.userId);
        if (resposta.autor_id !== userId && resposta.dono_id !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM forum_respostas WHERE id = ?').run(respostaId);
        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir resposta:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Fixar/desfixar tópico (dono da comunidade)
app.post('/api/forum/topico/:topicoId/fixar', requireLogin, (req, res) => {
    try {
        const topicoId = req.params.topicoId;
        const topico = db.prepare(`SELECT t.*, c.dono_id FROM forum_topicos t JOIN comunidades c ON c.id = t.comunidade_id WHERE t.id = ?`).get(topicoId);
        if (!topico) return res.json({ success: false, message: 'Tópico não encontrado.' });
        if (topico.dono_id !== String(req.session.userId)) return res.json({ success: false, message: 'Sem permissão.' });

        const newVal = topico.fixado ? 0 : 1;
        db.prepare('UPDATE forum_topicos SET fixado = ? WHERE id = ?').run(newVal, topicoId);
        res.json({ success: true, fixado: newVal });
    } catch(err) {
        console.error('Erro fixar tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Trancar/destrancar tópico (dono da comunidade)
app.post('/api/forum/topico/:topicoId/trancar', requireLogin, (req, res) => {
    try {
        const topicoId = req.params.topicoId;
        const topico = db.prepare(`SELECT t.*, c.dono_id FROM forum_topicos t JOIN comunidades c ON c.id = t.comunidade_id WHERE t.id = ?`).get(topicoId);
        if (!topico) return res.json({ success: false, message: 'Tópico não encontrado.' });
        if (topico.dono_id !== String(req.session.userId)) return res.json({ success: false, message: 'Sem permissão.' });

        const newVal = topico.trancado ? 0 : 1;
        db.prepare('UPDATE forum_topicos SET trancado = ? WHERE id = ?').run(newVal, topicoId);
        res.json({ success: true, trancado: newVal });
    } catch(err) {
        console.error('Erro trancar tópico:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== ENQUETES (Polls) =====

// Listar enquetes de uma comunidade
app.get('/api/enquetes/:commId', requireLogin, (req, res) => {
    try {
        const commId = req.params.commId;
        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se está bloqueado
        const isBanned = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(req.session.userId));
        if (isBanned) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        // Comunidade privada: apenas membros podem ver enquetes
        if (comm.tipo === 'privada') {
            const membroEnq = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(req.session.userId));
            if (!membroEnq && String(comm.dono_id) !== String(req.session.userId)) {
                return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar as enquetes.' });
            }
        }

        const enquetes = db.prepare(`
            SELECT e.*, u.nome as criador_nome, u.foto_perfil as criador_foto
            FROM enquetes e
            JOIN usuarios u ON u.id = e.criador_id
            WHERE e.comunidade_id = ?
            ORDER BY e.criado_em DESC
        `).all(commId);

        const userId = String(req.session.userId);

        const result = enquetes.map(enq => {
            const opcoes = db.prepare(`
                SELECT o.*, 
                    (SELECT COUNT(*) FROM enquete_votos WHERE opcao_id = o.id) as votos
                FROM enquete_opcoes o
                WHERE o.enquete_id = ?
                ORDER BY o.id
            `).all(enq.id);

            const totalVotos = opcoes.reduce((sum, o) => sum + o.votos, 0);

            const meuVoto = db.prepare('SELECT opcao_id FROM enquete_votos WHERE enquete_id = ? AND usuario_id = ?').get(enq.id, userId);

            return {
                ...enq,
                opcoes: opcoes.map(o => ({
                    id: o.id,
                    texto: o.texto,
                    foto: o.foto,
                    votos: o.votos,
                    percentual: totalVotos > 0 ? Math.round((o.votos / totalVotos) * 100) : 0
                })),
                total_votos: totalVotos,
                meu_voto: meuVoto ? meuVoto.opcao_id : null,
                is_owner: enq.criador_id === userId || comm.dono_id === userId
            };
        });

        const membrosCount = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(commId).c;
        const isOwner = String(comm.dono_id) === String(userId);
        res.json({ success: true, enquetes: result, community: { id: comm.id, nome: comm.nome, foto: comm.foto, dono_id: comm.dono_id }, membrosCount, isOwner });
    } catch(err) {
        console.error('Erro listar enquetes:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Criar enquete
app.post('/api/enquetes/:commId', requireLogin, (req, res) => {
    try {
        const commId = req.params.commId;
        const userId = String(req.session.userId);
        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se é membro
        const isMember = db.prepare('SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (!isMember) return res.json({ success: false, message: 'Você precisa ser membro da comunidade.' });

        const { titulo, opcoes, data_inicio, data_fim, mostrar_votantes } = req.body;

        if (!titulo || !titulo.trim()) return res.json({ success: false, message: 'Título é obrigatório.' });
        if (!opcoes || !Array.isArray(opcoes) || opcoes.length < 2) return res.json({ success: false, message: 'Mínimo de 2 opções.' });

        const validOpcoes = opcoes.filter(o => o && o.trim());
        if (validOpcoes.length < 2) return res.json({ success: false, message: 'Mínimo de 2 opções válidas.' });

        const info = db.prepare(`
            INSERT INTO enquetes (comunidade_id, criador_id, titulo, data_inicio, data_fim, mostrar_votantes)
            VALUES (?, ?, ?, ?, ?, ?)
        `).run(commId, userId, titulo.trim(), data_inicio || null, data_fim || null, mostrar_votantes ? 1 : 0);

        const enqueteId = info.lastInsertRowid;

        const insertOpcao = db.prepare('INSERT INTO enquete_opcoes (enquete_id, texto) VALUES (?, ?)');
        for (const opcao of validOpcoes) {
            insertOpcao.run(enqueteId, opcao.trim());
        }

        res.json({ success: true, enquete_id: enqueteId });
    } catch(err) {
        console.error('Erro criar enquete:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Buscar uma enquete específica
app.get('/api/enquetes/:commId/:enqueteId', requireLogin, (req, res) => {
    try {
        const commId = req.params.commId;
        const enqueteId = req.params.enqueteId;
        const comm = db.prepare('SELECT * FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Comunidade privada: apenas membros podem ver enquetes
        if (comm.tipo === 'privada') {
            const membroEnqDet = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(req.session.userId));
            if (!membroEnqDet && String(comm.dono_id) !== String(req.session.userId)) {
                return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar as enquetes.' });
            }
        }

        const enq = db.prepare(`
            SELECT e.*, u.nome as criador_nome, u.foto_perfil as criador_foto
            FROM enquetes e
            JOIN usuarios u ON u.id = e.criador_id
            WHERE e.id = ? AND e.comunidade_id = ?
        `).get(enqueteId, commId);

        if (!enq) return res.json({ success: false, message: 'Enquete não encontrada.' });

        const userId = String(req.session.userId);

        const opcoes = db.prepare(`
            SELECT o.*, 
                (SELECT COUNT(*) FROM enquete_votos WHERE opcao_id = o.id) as votos
            FROM enquete_opcoes o
            WHERE o.enquete_id = ?
            ORDER BY o.id
        `).all(enq.id);

        const totalVotos = opcoes.reduce((sum, o) => sum + o.votos, 0);
        const meuVoto = db.prepare('SELECT opcao_id FROM enquete_votos WHERE enquete_id = ? AND usuario_id = ?').get(enq.id, userId);

        const result = {
            ...enq,
            opcoes: opcoes.map(o => ({
                id: o.id,
                texto: o.texto,
                foto: o.foto,
                votos: o.votos,
                percentual: totalVotos > 0 ? Math.round((o.votos / totalVotos) * 100) : 0
            })),
            total_votos: totalVotos,
            meu_voto: meuVoto ? meuVoto.opcao_id : null,
            is_owner: enq.criador_id === userId || comm.dono_id === userId
        };

        const membrosCountDetail = db.prepare('SELECT COUNT(*) as c FROM comunidade_membros WHERE comunidade_id = ?').get(commId).c;
        const isOwner = String(comm.dono_id) === String(userId);
        res.json({ success: true, enquete: result, community: { id: comm.id, nome: comm.nome, foto: comm.foto, dono_id: comm.dono_id }, membrosCount: membrosCountDetail, isOwner });
    } catch(err) {
        console.error('Erro buscar enquete:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Votar em enquete
app.post('/api/enquetes/:commId/:enqueteId/votar', requireLogin, (req, res) => {
    try {
        const { enqueteId } = req.params;
        const userId = String(req.session.userId);
        const { opcao_id } = req.body;

        if (!opcao_id) return res.json({ success: false, message: 'Selecione uma opção.' });

        const enquete = db.prepare('SELECT * FROM enquetes WHERE id = ?').get(enqueteId);
        if (!enquete) return res.json({ success: false, message: 'Enquete não encontrada.' });

        // Verificar se a enquete já encerrou
        if (enquete.data_fim) {
            const now = new Date();
            const fim = new Date(enquete.data_fim);
            if (now > fim) return res.json({ success: false, message: 'Esta enquete já encerrou.' });
        }

        // Verificar se já votou
        const jaVotou = db.prepare('SELECT 1 FROM enquete_votos WHERE enquete_id = ? AND usuario_id = ?').get(enqueteId, userId);
        if (jaVotou) return res.json({ success: false, message: 'Você já votou nesta enquete.' });

        // Verificar se a opção pertence a esta enquete
        const opcao = db.prepare('SELECT 1 FROM enquete_opcoes WHERE id = ? AND enquete_id = ?').get(opcao_id, enqueteId);
        if (!opcao) return res.json({ success: false, message: 'Opção inválida.' });

        db.prepare('INSERT INTO enquete_votos (enquete_id, opcao_id, usuario_id) VALUES (?, ?, ?)').run(enqueteId, opcao_id, userId);

        // Retornar dados atualizados
        const opcoes = db.prepare(`
            SELECT o.id, o.texto, o.foto,
                (SELECT COUNT(*) FROM enquete_votos WHERE opcao_id = o.id) as votos
            FROM enquete_opcoes o WHERE o.enquete_id = ? ORDER BY o.id
        `).all(enqueteId);
        const totalVotos = opcoes.reduce((sum, o) => sum + o.votos, 0);

        res.json({
            success: true,
            opcoes: opcoes.map(o => ({
                ...o,
                percentual: totalVotos > 0 ? Math.round((o.votos / totalVotos) * 100) : 0
            })),
            total_votos: totalVotos,
            meu_voto: Number(opcao_id)
        });
    } catch(err) {
        console.error('Erro votar enquete:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Excluir enquete (dono da comunidade ou criador da enquete)
app.post('/api/enquetes/:commId/:enqueteId/excluir', requireLogin, (req, res) => {
    try {
        const { commId, enqueteId } = req.params;
        const userId = String(req.session.userId);

        const enquete = db.prepare('SELECT e.*, c.dono_id FROM enquetes e JOIN comunidades c ON c.id = e.comunidade_id WHERE e.id = ? AND e.comunidade_id = ?').get(enqueteId, commId);
        if (!enquete) return res.json({ success: false, message: 'Enquete não encontrada.' });

        if (enquete.criador_id !== userId && enquete.dono_id !== userId) {
            return res.json({ success: false, message: 'Sem permissão.' });
        }

        db.prepare('DELETE FROM enquete_votos WHERE enquete_id = ?').run(enqueteId);
        db.prepare('DELETE FROM enquete_opcoes WHERE enquete_id = ?').run(enqueteId);
        db.prepare('DELETE FROM enquetes WHERE id = ?').run(enqueteId);

        res.json({ success: true });
    } catch(err) {
        console.error('Erro excluir enquete:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// Detalhes de votos de uma enquete (apenas se mostrar_votantes e enquete encerrada)
app.get('/api/enquetes/:commId/:enqueteId/votantes', requireLogin, (req, res) => {
    try {
        const { commId, enqueteId } = req.params;
        const enquete = db.prepare('SELECT * FROM enquetes WHERE id = ?').get(enqueteId);
        if (!enquete) return res.json({ success: false, message: 'Enquete não encontrada.' });

        // Comunidade privada: apenas membros podem ver votantes
        const commVot = db.prepare('SELECT tipo, dono_id FROM comunidades WHERE id = ?').get(commId);
        if (commVot && commVot.tipo === 'privada') {
            const membroVot = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(req.session.userId));
            if (!membroVot && String(commVot.dono_id) !== String(req.session.userId)) {
                return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar.' });
            }
        }

        if (enquete.data_fim) {
            const now = new Date();
            const fim = new Date(enquete.data_fim);
            if (now <= fim) return res.json({ success: false, message: 'Enquete ainda não encerrou.' });
        } else {
            return res.json({ success: false, message: 'Enquete sem data de encerramento.' });
        }

        const votantes = db.prepare(`
            SELECT v.opcao_id, v.votado_em, u.id as user_id, u.nome, u.foto_perfil as foto, o.texto as opcao_texto
            FROM enquete_votos v
            JOIN usuarios u ON u.id = v.usuario_id
            JOIN enquete_opcoes o ON o.id = v.opcao_id
            WHERE v.enquete_id = ?
            ORDER BY o.id, u.nome
        `).all(enqueteId);

        res.json({ success: true, votantes });
    } catch(err) {
        console.error('Erro buscar votantes:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== STAFF DA COMUNIDADE =====

// GET /api/comunidade/:id/staff - Listar staff (dono + moderadores)
app.get('/api/comunidade/:id/staff', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comm = db.prepare(`
            SELECT c.id, c.nome, c.foto, c.dono_id, u.nome as dono_nome, u.foto_perfil as dono_foto
            FROM comunidades c
            LEFT JOIN usuarios u ON u.id = c.dono_id
            WHERE c.id = ?
        `).get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const membrosCount = db.prepare('SELECT COUNT(*) as total FROM comunidade_membros WHERE comunidade_id = ?').get(commId).total;

        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isMember = !!membership;
        const isOwner = String(comm.dono_id) === String(userId);

        // Dono
        const owner = {
            id: comm.dono_id,
            nome: comm.dono_nome || 'Usuário Excluído',
            foto_perfil: comm.dono_foto
        };

        // Moderadores
        const moderadores = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ? AND cm.cargo = 'moderador'
            ORDER BY u.nome
        `).all(commId);

        // Membros para sidebar (últimos 9)
        const membros = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ?
            ORDER BY cm.entrou_em DESC
            LIMIT 9
        `).all(commId);

        return res.json({
            success: true,
            community: { id: comm.id, nome: comm.nome, foto: comm.foto, dono_id: comm.dono_id },
            owner,
            moderadores,
            membrosCount,
            membros,
            isMember,
            isOwner
        });
    } catch (err) {
        console.error('Erro ao carregar staff:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/staff/add - Adicionar moderador (dono only)
app.post('/api/comunidade/:id/staff/add', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { usuario_id } = req.body;

        if (!usuario_id) return res.json({ success: false, message: 'Informe o usuário.' });

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode gerenciar o staff.' });

        if (String(usuario_id) === String(comm.dono_id)) return res.json({ success: false, message: 'O dono já faz parte do staff.' });

        const member = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(usuario_id));
        if (!member) return res.json({ success: false, message: 'Este usuário não é membro da comunidade.' });
        if (member.cargo === 'moderador') return res.json({ success: false, message: 'Este usuário já é moderador.' });

        db.prepare('UPDATE comunidade_membros SET cargo = ? WHERE comunidade_id = ? AND usuario_id = ?').run('moderador', commId, String(usuario_id));

        return res.json({ success: true, message: 'Moderador adicionado com sucesso!' });
    } catch (err) {
        console.error('Erro ao adicionar moderador:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/comunidade/:id/staff/remove - Remover moderador (dono only)
app.post('/api/comunidade/:id/staff/remove', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const { usuario_id } = req.body;

        if (!usuario_id) return res.json({ success: false, message: 'Informe o usuário.' });

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode gerenciar o staff.' });

        db.prepare('UPDATE comunidade_membros SET cargo = ? WHERE comunidade_id = ? AND usuario_id = ?').run('membro', commId, String(usuario_id));

        return res.json({ success: true, message: 'Moderador removido com sucesso!' });
    } catch (err) {
        console.error('Erro ao remover moderador:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/membros-para-staff - Lista membros que podem ser promovidos
app.get('/api/comunidade/:id/membros-para-staff', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode gerenciar o staff.' });

        // Membros que não são moderadores e não são o dono
        const membros = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM comunidade_membros cm
            JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ? AND cm.cargo = 'membro' AND cm.usuario_id != ?
            ORDER BY u.nome
        `).all(commId, String(comm.dono_id));

        return res.json({ success: true, membros });
    } catch (err) {
        console.error('Erro ao listar membros para staff:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/comunidade/:id/lookup-user?uid=xxx - Buscar usuário para staff
app.get('/api/comunidade/:id/lookup-user', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.id);
        const userId = req.session.userId;
        const uid = req.query.uid;

        if (!uid) return res.json({ success: false, message: 'Informe o ID do usuário.' });

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode gerenciar o staff.' });

        const user = db.prepare('SELECT id, nome, foto_perfil FROM usuarios WHERE id = ?').get(String(uid));
        if (!user) return res.json({ success: false, message: 'Usuário não encontrado.' });

        if (String(uid) === String(comm.dono_id)) return res.json({ success: false, message: 'O dono já faz parte do staff.' });

        const member = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, String(uid));
        if (!member) return res.json({ success: false, message: 'Este usuário não é membro da comunidade.' });
        if (member.cargo === 'moderador') return res.json({ success: false, message: 'Este usuário já é moderador.' });

        return res.json({ success: true, user: { id: user.id, nome: user.nome, foto_perfil: user.foto_perfil } });
    } catch (err) {
        console.error('Erro ao buscar usuário para staff:', err);
        return res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== Sorte do Dia =====
app.get('/api/sorte', (req, res) => {
    if (!req.session.userId) return res.status(401).json({ error: 'Não autenticado' });
    // Calcular data na timezone configurada (ex: '-3 hours' -> -3)
    const tzStr = getTzOffset();
    const offsetHours = parseInt(tzStr.replace(/\s*hours?\s*/i, ''), 10) || -3;
    const now = new Date(Date.now() + offsetHours * 3600000);
    const dateStr = now.getUTCFullYear() + '-' + (now.getUTCMonth()+1) + '-' + now.getUTCDate();
    // Hash único por usuário+dia para sorte aleatória
    const seed = crypto.createHash('md5').update(req.session.userId + ':' + dateStr).digest();
    const index = seed.readUInt32BE(0) % SORTE_FRASES.length;
    return res.json({ sorte: SORTE_FRASES[index] });
});

// ===== SORTEIOS DA COMUNIDADE =====

// GET /api/sorteios/:commId - Listar sorteios da comunidade
app.get('/api/sorteios/:commId', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const userId = req.session.userId;

        const comm = db.prepare(`
            SELECT c.id, c.nome, c.foto, c.dono_id, c.tipo
            FROM comunidades c WHERE c.id = ?
        `).get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        // Verificar se está bloqueado
        const isBannedSorteio = db.prepare('SELECT id FROM comunidade_bans WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (isBannedSorteio) return res.json({ success: false, message: 'Você não pode acessar esta comunidade! Entre em contato com os donos da comunidade.' });

        // Comunidade privada: apenas membros podem ver sorteios
        if (comm.tipo === 'privada') {
            const membroSort = db.prepare('SELECT id FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
            if (!membroSort && String(comm.dono_id) !== String(userId)) {
                return res.json({ success: false, message: 'Esta comunidade é privada. Apenas membros podem acessar os sorteios.' });
            }
        }

        const membrosCount = db.prepare('SELECT COUNT(*) as total FROM comunidade_membros WHERE comunidade_id = ?').get(commId).total;
        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        const isMember = !!membership;
        const isOwner = String(comm.dono_id) === String(userId);
        const isMod = membership && membership.cargo === 'moderador';

        const membros = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM comunidade_membros cm JOIN usuarios u ON u.id = cm.usuario_id
            WHERE cm.comunidade_id = ? ORDER BY cm.entrou_em DESC LIMIT 9
        `).all(commId);

        const sorteios = db.prepare(`
            SELECT s.*,
                   u.nome as criador_nome,
                   (SELECT COUNT(*) FROM sorteio_participantes WHERE sorteio_id = s.id) as total_participantes
            FROM sorteios s
            LEFT JOIN usuarios u ON u.id = s.criador_id
            WHERE s.comunidade_id = ?
            ORDER BY s.criado_em DESC
        `).all(commId);

        // Enrich each sorteio with participation + winners
        for (const s of sorteios) {
            const part = db.prepare('SELECT 1 FROM sorteio_participantes WHERE sorteio_id = ? AND usuario_id = ?').get(s.id, userId);
            s.participando = !!part;

            // Sorteio automático se expirado e não sorteado
            const tzOff = getTzOffset();
            const now = db.prepare(`SELECT datetime('now','${tzOff}') as agora`).get().agora;
            const dataFimNorm = s.data_fim ? s.data_fim.replace('T', ' ') : '';
            if (!s.sorteado && dataFimNorm && dataFimNorm <= now) {
                // Verifica participantes
                const participantes = db.prepare(`
                    SELECT sp.usuario_id, u.nome, u.foto_perfil
                    FROM sorteio_participantes sp
                    JOIN usuarios u ON u.id = sp.usuario_id
                    WHERE sp.sorteio_id = ?
                `).all(s.id);
                if (participantes.length === 0) {
                    s.sorteio_nao_realizado = true;
                    s.vencedores = [];
                    db.prepare('UPDATE sorteios SET sorteado = 1 WHERE id = ?').run(s.id);
                } else if (participantes.length < (s.qtd_vencedores || 1)) {
                    s.sorteio_nao_realizado = true;
                    s.vencedores = [];
                    db.prepare('UPDATE sorteios SET sorteado = 1 WHERE id = ?').run(s.id);
                } else {
                    // Sorteia vencedores únicos
                    const qtd = Math.min(s.qtd_vencedores || 1, participantes.length);
                    // Embaralha e seleciona vencedores únicos
                    const shuffled = participantes.sort(() => Math.random() - 0.5);
                    const winners = shuffled.slice(0, qtd);
                    // Evita duplicidade de vencedores
                    const existingWinners = db.prepare('SELECT usuario_id FROM sorteio_vencedores WHERE sorteio_id = ?').all(s.id).map(r => r.usuario_id);
                    const insertWinner = db.prepare('INSERT INTO sorteio_vencedores (sorteio_id, usuario_id) VALUES (?, ?)');
                    for (const w of winners) {
                        if (!existingWinners.includes(w.usuario_id)) {
                            insertWinner.run(s.id, w.usuario_id);
                        }
                    }
                    db.prepare('UPDATE sorteios SET sorteado = 1 WHERE id = ?').run(s.id);
                    s.vencedores = winners.map(w => {
                        const isMembro = !!db.prepare('SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, w.usuario_id);
                        return { id: w.usuario_id, nome: w.nome, foto_perfil: w.foto_perfil, is_membro: isMembro };
                    });
                }
                s.sorteado = 1;
            } else if (s.sorteado) {
                s.vencedores = db.prepare(`
                    SELECT sv.usuario_id as id, u.nome, u.foto_perfil,
                           CASE WHEN cm.usuario_id IS NOT NULL THEN 1 ELSE 0 END as is_membro
                    FROM sorteio_vencedores sv
                    JOIN usuarios u ON u.id = sv.usuario_id
                    LEFT JOIN comunidade_membros cm ON cm.comunidade_id = ? AND cm.usuario_id = sv.usuario_id
                    WHERE sv.sorteio_id = ?
                `).all(commId, s.id);
                // Se sorteado mas sem vencedores, marcar como nao realizado
                if (s.vencedores.length === 0) {
                    s.sorteio_nao_realizado = true;
                }
            } else {
                s.vencedores = [];
            }
        }

        return res.json({
            success: true,
            community: { id: comm.id, nome: comm.nome, foto: comm.foto, dono_id: comm.dono_id },
            membrosCount, membros, isMember, isOwner, isMod, sorteios
        });
    } catch(err) {
        console.error('Erro listar sorteios:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/sorteios/:commId - Criar sorteio (owner only)
app.post('/api/sorteios/:commId', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });

        const isOwner = String(comm.dono_id) === String(userId);
        if (!isOwner) return res.json({ success: false, message: 'Apenas o dono da comunidade pode criar sorteios.' });

        const { premio, data_fim, qtd_vencedores, mod_participa, regras } = req.body;
        if (!premio || !premio.trim()) return res.json({ success: false, message: 'Informe o item/prêmio do sorteio.' });
        if (!data_fim) return res.json({ success: false, message: 'Data de encerramento é obrigatória.' });

        const qtdV = Math.max(1, parseInt(qtd_vencedores) || 1);
        const modP = mod_participa ? 1 : 0;
        const regrasText = (regras || '').trim();

        // Normalizar data_fim: trocar T por espaço para compatibilidade com datetime() do SQLite
        const dataFimNorm = data_fim ? data_fim.replace('T', ' ') : data_fim;

        db.prepare(`
            INSERT INTO sorteios (comunidade_id, criador_id, premio, regras, data_fim, qtd_vencedores, mod_participa)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        `).run(commId, userId, premio.trim(), regrasText, dataFimNorm, qtdV, modP);

        return res.json({ success: true, message: 'Sorteio criado com sucesso!' });
    } catch(err) {
        console.error('Erro criar sorteio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// GET /api/sorteios/:commId/:sorteioId/participantes - Listar participantes
app.get('/api/sorteios/:commId/:sorteioId/participantes', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const sorteioId = parseInt(req.params.sorteioId);

        const sorteio = db.prepare('SELECT id FROM sorteios WHERE id = ? AND comunidade_id = ?').get(sorteioId, commId);
        if (!sorteio) return res.json({ success: false, message: 'Sorteio não encontrado.' });

        const participantes = db.prepare(`
            SELECT u.id, u.nome, u.foto_perfil
            FROM sorteio_participantes sp
            JOIN usuarios u ON u.id = sp.usuario_id
            WHERE sp.sorteio_id = ?
            ORDER BY sp.participou_em ASC
        `).all(sorteioId);

        return res.json({ success: true, participantes });
    } catch(err) {
        console.error('Erro listar participantes sorteio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/sorteios/:commId/:sorteioId/participar - Participar de um sorteio
app.post('/api/sorteios/:commId/:sorteioId/participar', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const sorteioId = parseInt(req.params.sorteioId);
        const userId = req.session.userId;

        const membership = db.prepare('SELECT cargo FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?').get(commId, userId);
        if (!membership) return res.json({ success: false, message: 'Você precisa ser membro da comunidade.' });

        const sorteio = db.prepare('SELECT * FROM sorteios WHERE id = ? AND comunidade_id = ?').get(sorteioId, commId);
        if (!sorteio) return res.json({ success: false, message: 'Sorteio não encontrado.' });
        if (sorteio.sorteado) return res.json({ success: false, message: 'Este sorteio já foi encerrado.' });

        // Owner cannot participate
        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (String(comm.dono_id) === String(userId)) return res.json({ success: false, message: 'O dono da comunidade não pode participar dos sorteios.' });

        // Check mod restriction
        if (membership.cargo === 'moderador' && !sorteio.mod_participa) {
            return res.json({ success: false, message: 'Moderadores não podem participar deste sorteio.' });
        }

        // Check if ended by date
        const tzOff = getTzOffset();
        const now = db.prepare(`SELECT datetime('now','${tzOff}') as agora`).get().agora;
        const dataFimNorm = sorteio.data_fim ? sorteio.data_fim.replace('T', ' ') : '';
        if (dataFimNorm && dataFimNorm <= now) return res.json({ success: false, message: 'O prazo deste sorteio já expirou.' });

        // Already participating?
        const existing = db.prepare('SELECT 1 FROM sorteio_participantes WHERE sorteio_id = ? AND usuario_id = ?').get(sorteioId, userId);
        if (existing) return res.json({ success: false, message: 'Você já está participando deste sorteio.' });

        db.prepare('INSERT INTO sorteio_participantes (sorteio_id, usuario_id) VALUES (?, ?)').run(sorteioId, userId);

        const total = db.prepare('SELECT COUNT(*) as c FROM sorteio_participantes WHERE sorteio_id = ?').get(sorteioId).c;
        return res.json({ success: true, message: 'Você está participando! Boa sorte! 🍀', total_participantes: total });
    } catch(err) {
        console.error('Erro participar sorteio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/sorteios/:commId/:sorteioId/sortear - Sortear vencedor(es) (owner only)
app.post('/api/sorteios/:commId/:sorteioId/sortear', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const sorteioId = parseInt(req.params.sorteioId);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode sortear.' });

        const sorteio = db.prepare('SELECT * FROM sorteios WHERE id = ? AND comunidade_id = ?').get(sorteioId, commId);
        if (!sorteio) return res.json({ success: false, message: 'Sorteio não encontrado.' });
        if (sorteio.sorteado) return res.json({ success: false, message: 'Este sorteio já foi sorteado.' });

        const participantes = db.prepare(`
            SELECT sp.usuario_id, u.nome, u.foto_perfil
            FROM sorteio_participantes sp
            JOIN usuarios u ON u.id = sp.usuario_id
            WHERE sp.sorteio_id = ?
        `).all(sorteioId);

        if (participantes.length === 0) return res.json({ success: false, message: 'Nenhum participante para sortear.' });

        // Shuffle and pick N winners
        const qtd = Math.min(sorteio.qtd_vencedores || 1, participantes.length);
        const shuffled = participantes.sort(() => Math.random() - 0.5);
        const winners = shuffled.slice(0, qtd);

        // Save winners
        const insertWinner = db.prepare('INSERT INTO sorteio_vencedores (sorteio_id, usuario_id) VALUES (?, ?)');
        for (const w of winners) {
            insertWinner.run(sorteioId, w.usuario_id);
        }

        db.prepare('UPDATE sorteios SET sorteado = 1 WHERE id = ?').run(sorteioId);

        return res.json({
            success: true,
            message: winners.length === 1
                ? '🎉 Vencedor sorteado: ' + winners[0].nome + '!'
                : '🎉 ' + winners.length + ' vencedores sorteados!',
            vencedores: winners.map(w => ({ id: w.usuario_id, nome: w.nome, foto_perfil: w.foto_perfil }))
        });
    } catch(err) {
        console.error('Erro sortear:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// POST /api/sorteios/:commId/:sorteioId/excluir - Excluir sorteio (owner only)
app.post('/api/sorteios/:commId/:sorteioId/excluir', requireLogin, (req, res) => {
    try {
        const commId = parseInt(req.params.commId);
        const sorteioId = parseInt(req.params.sorteioId);
        const userId = req.session.userId;

        const comm = db.prepare('SELECT dono_id FROM comunidades WHERE id = ?').get(commId);
        if (!comm) return res.json({ success: false, message: 'Comunidade não encontrada.' });
        if (String(comm.dono_id) !== String(userId)) return res.json({ success: false, message: 'Apenas o dono pode excluir sorteios.' });

        db.prepare('DELETE FROM sorteio_vencedores WHERE sorteio_id = ?').run(sorteioId);
        db.prepare('DELETE FROM sorteio_participantes WHERE sorteio_id = ?').run(sorteioId);
        db.prepare('DELETE FROM sorteios WHERE id = ? AND comunidade_id = ?').run(sorteioId, commId);

        return res.json({ success: true, message: 'Sorteio excluído.' });
    } catch(err) {
        console.error('Erro excluir sorteio:', err);
        res.json({ success: false, message: 'Erro interno.' });
    }
});

// ===== Job: Excluir contas de usuário agendadas (a cada 5 min) =====
setInterval(() => {
    try {
        const expirados = db.prepare(`SELECT id, nome FROM usuarios WHERE conta_excluir_em IS NOT NULL AND conta_excluir_em <= ${agora()}`).all();
        for (const user of expirados) {
            console.log(`[CLEANUP] Excluindo conta "${user.nome}" (id=${user.id}) - prazo de 24h expirado.`);
            const deleteUser = db.transaction(() => {
                try { db.prepare('DELETE FROM recados WHERE remetente_id = ? OR destinatario_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM mensagens WHERE remetente_id = ? OR destinatario_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM depoimentos WHERE remetente_id = ? OR destinatario_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM amizades WHERE remetente_id = ? OR destinatario_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM visitas WHERE visitante_id = ? OR visitado_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM fotos_comentarios WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM fotos_curtidas WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM fotos WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM videos_comentarios WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM videos_curtidas WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM videos WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM comunidade_membros WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM comunidade_pendentes WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM comunidade_bans WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM bloqueios WHERE bloqueador_id = ? OR bloqueado_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM fas WHERE usuario_id = ? OR fa_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM convites WHERE criado_por = ? OR usado_por = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM sessoes WHERE user_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM avaliacoes_amigos WHERE avaliador_id = ? OR avaliado_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM avaliacoes_perfil WHERE avaliador_id = ? OR avaliado_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM notificacoes WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM password_resets WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM denuncia_mensagens WHERE remetente_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM denuncias WHERE denunciante_id = ? OR denunciado_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('UPDATE denuncias SET resolvido_por = NULL WHERE resolvido_por = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM denuncia_comunidade_mensagens WHERE remetente_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('UPDATE denuncias_comunidades SET resolvido_por = NULL WHERE resolvido_por = ?').run(user.id); } catch(e) {}
                try { db.prepare('UPDATE denuncias_comunidades SET denunciante_id = NULL WHERE denunciante_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM sugestoes WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM bugs WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM anuncios WHERE admin_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM colheita_logs WHERE farm_owner_id = ? OR visitor_id = ?').run(user.id, user.id); } catch(e) {}
                try { db.prepare('DELETE FROM colheita_farms WHERE user_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM enquete_votos WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM sorteio_participantes WHERE usuario_id = ?').run(user.id); } catch(e) {}
                try { db.prepare('DELETE FROM sorteio_vencedores WHERE usuario_id = ?').run(user.id); } catch(e) {}
                // Comunidades onde o usuário é dono: agendar exclusão
                try {
                    const comms = db.prepare('SELECT id FROM comunidades WHERE dono_id = ?').all(user.id);
                    for (const c of comms) {
                        db.prepare(`UPDATE comunidades SET excluir_em = ${agora()} WHERE id = ? AND excluir_em IS NULL`).run(c.id);
                    }
                } catch(e) {}
                db.prepare('DELETE FROM usuarios WHERE id = ?').run(user.id);
            });
            try {
                deleteUser();
                console.log(`[CLEANUP] Conta "${user.nome}" excluída com sucesso.`);
            } catch(txErr) {
                console.error(`[CLEANUP] Erro transacional ao excluir "${user.nome}":`, txErr);
            }
        }
    } catch (err) {
        console.error('[CLEANUP] Erro ao excluir contas agendadas:', err);
    }
}, 5 * 60 * 1000);

// ===== Job: Excluir comunidades agendadas (a cada 5 min) =====
setInterval(() => {
    try {
        const expiradas = db.prepare(`SELECT id, nome FROM comunidades WHERE excluir_em IS NOT NULL AND excluir_em <= ${agora()}`).all();
        for (const comm of expiradas) {
            console.log(`[CLEANUP] Excluindo comunidade "${comm.nome}" (id=${comm.id}) - prazo de 24h expirado.`);
            db.prepare('DELETE FROM comunidade_bans WHERE comunidade_id = ?').run(comm.id);
            db.prepare('DELETE FROM comunidade_membros WHERE comunidade_id = ?').run(comm.id);
            db.prepare('DELETE FROM comunidade_pendentes WHERE comunidade_id = ?').run(comm.id);
            // Deletar tópicos e respostas do fórum
            const topicos = db.prepare('SELECT id FROM forum_topicos WHERE comunidade_id = ?').all(comm.id);
            for (const t of topicos) {
                db.prepare('DELETE FROM forum_respostas WHERE topico_id = ?').run(t.id);
            }
            db.prepare('DELETE FROM forum_topicos WHERE comunidade_id = ?').run(comm.id);
            // Deletar enquetes
            try {
                const enquetes = db.prepare('SELECT id FROM enquetes WHERE comunidade_id = ?').all(comm.id);
                for (const enq of enquetes) {
                    db.prepare('DELETE FROM enquete_opcoes WHERE enquete_id = ?').run(enq.id);
                    db.prepare('DELETE FROM enquete_votos WHERE enquete_id = ?').run(enq.id);
                }
                db.prepare('DELETE FROM enquetes WHERE comunidade_id = ?').run(comm.id);
            } catch(e) {}
            // Deletar sorteios
            try {
                db.prepare('DELETE FROM sorteio_participantes WHERE sorteio_id IN (SELECT id FROM sorteios WHERE comunidade_id = ?)').run(comm.id);
                db.prepare('DELETE FROM sorteios WHERE comunidade_id = ?').run(comm.id);
            } catch(e) {}
            // Deletar denúncias
            try { db.prepare('DELETE FROM denuncias_comunidades WHERE comunidade_id = ?').run(comm.id); } catch(e) {}
            // Deletar a comunidade
            db.prepare('DELETE FROM comunidades WHERE id = ?').run(comm.id);
        }
    } catch (err) {
        console.error('[CLEANUP] Erro ao excluir comunidades agendadas:', err);
    }
}, 5 * 60 * 1000); // a cada 5 minutos

// ===== 404 e Error Handler =====
app.use((req, res) => {
    if (req.path.startsWith('/api/')) {
        return res.status(404).json({ success: false, message: 'Rota não encontrada.' });
    }
    res.status(404).send('<h1>404 - Página não encontrada</h1>');
});
app.use((err, req, res, next) => {
    console.error('Erro não tratado:', err);
    if (req.path.startsWith('/api/')) {
        return res.status(500).json({ success: false, message: 'Erro interno do servidor.' });
    }
    res.status(500).send('<h1>500 - Erro interno</h1>');
});

// ===== Iniciar servidor =====
app.listen(PORT, () => {
    console.log(`Servidor Yorkut rodando em http://localhost:${PORT}`);
    console.log(`Login:    http://localhost:${PORT}/index.php`);
    console.log(`Registro: http://localhost:${PORT}/registro.php`);
    console.log('');
});

} catch (err) {
    console.error('[FATAL] Erro ao iniciar servidor:', err);
    process.exit(1);
}

})(); // fim do async IIFE
