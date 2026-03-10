/**
 * mysql-compat.js
 * Wrapper MySQL com a mesma API do sqlite-compat (better-sqlite3 style).
 * Converte automaticamente sintaxe SQLite → MySQL.
 * 
 * API:
 *   db.prepare(sql).get(...params)   → primeira linha ou undefined
 *   db.prepare(sql).run(...params)   → { changes, lastInsertRowid }
 *   db.prepare(sql).all(...params)   → array de linhas
 *   db.exec(sql)                     → executa SQL raw (CREATE TABLE, etc.)
 *   db.pragma(str)                   → SET session variable
 *   db.transaction(fn)               → wrapper BEGIN/COMMIT/ROLLBACK
 */

const mysql = require('mysql2/promise');
const { AsyncLocalStorage } = require('async_hooks');

class MysqlCompat {
    constructor(pool, tz) {
        this._pool = pool;
        this._tz = tz || '-03:00';
        this._txStore = new AsyncLocalStorage();
    }

    // ===== Obter conexão (com timezone configurado) =====

    async _acquire() {
        // Se estamos dentro de uma transaction, usa a conexão da transaction
        const txConn = this._txStore.getStore();
        if (txConn) return txConn;

        // Pega conexão do pool e configura timezone
        const conn = await this._pool.getConnection();
        await conn.query(`SET time_zone = ?`, [this._tz]);
        return conn;
    }

    _release(conn) {
        // Não liberar se estiver em transaction
        const txConn = this._txStore.getStore();
        if (txConn) return;
        conn.release();
    }

    // ===== Conversão SQLite → MySQL =====

    _convertSql(sql) {
        let s = sql;

        // ===== Estrutura de tabelas =====

        // INTEGER PRIMARY KEY AUTOINCREMENT → INT AUTO_INCREMENT PRIMARY KEY
        s = s.replace(/INTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT/gi, 'INT AUTO_INCREMENT PRIMARY KEY');

        // INTEGER PRIMARY KEY (sem auto) → INT PRIMARY KEY
        s = s.replace(/INTEGER\s+PRIMARY\s+KEY(?!\s+AUTO)/gi, 'INT PRIMARY KEY');

        // TEXT com DEFAULT datetime → DATETIME DEFAULT CURRENT_TIMESTAMP
        s = s.replace(/\bTEXT\s+DEFAULT\s+\(?datetime\([^)]*\)\)?/gi, 'DATETIME DEFAULT CURRENT_TIMESTAMP');

        // TEXT PRIMARY KEY → VARCHAR(255) PRIMARY KEY
        s = s.replace(/\bTEXT\s+PRIMARY\s+KEY\b/gi, 'VARCHAR(255) PRIMARY KEY');

        // TEXT NOT NULL UNIQUE → VARCHAR(255) NOT NULL UNIQUE
        s = s.replace(/\bTEXT\s+NOT\s+NULL\s+UNIQUE\b/gi, 'VARCHAR(255) NOT NULL UNIQUE');

        // TEXT UNIQUE → VARCHAR(255) UNIQUE
        s = s.replace(/\bTEXT\s+UNIQUE\b/gi, 'VARCHAR(255) UNIQUE');

        // CREATE INDEX IF NOT EXISTS → CREATE INDEX (MySQL não suporta IF NOT EXISTS para índices)
        s = s.replace(/CREATE\s+INDEX\s+IF\s+NOT\s+EXISTS/gi, 'CREATE INDEX');

        // ===== Datetime functions (ordem importa: mais específico primeiro) =====

        // 3 args: datetime('now','tz','+N units')
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*,\s*'\+(\d+)\s+hours?'\s*\)/gi, 'DATE_ADD(NOW(), INTERVAL $1 HOUR)');
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*,\s*'-(\d+)\s+hours?'\s*\)/gi, 'DATE_SUB(NOW(), INTERVAL $1 HOUR)');
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*,\s*'\+(\d+)\s+days?'\s*\)/gi, 'DATE_ADD(NOW(), INTERVAL $1 DAY)');
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*,\s*'-(\d+)\s+days?'\s*\)/gi, 'DATE_SUB(NOW(), INTERVAL $1 DAY)');
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*,\s*'\+(\d+)\s+minutes?'\s*\)/gi, 'DATE_ADD(NOW(), INTERVAL $1 MINUTE)');

        // Resultado encadeado: datetime(NOW(), '+N units')
        s = s.replace(/datetime\(\s*NOW\(\)\s*,\s*'\+(\d+)\s+hours?'\s*\)/gi, 'DATE_ADD(NOW(), INTERVAL $1 HOUR)');
        s = s.replace(/datetime\(\s*NOW\(\)\s*,\s*'\+(\d+)\s+days?'\s*\)/gi, 'DATE_ADD(NOW(), INTERVAL $1 DAY)');
        s = s.replace(/datetime\(\s*NOW\(\)\s*,\s*'-(\d+)\s+days?'\s*\)/gi, 'DATE_SUB(NOW(), INTERVAL $1 DAY)');

        // DEFAULT datetime(...) residual → DEFAULT CURRENT_TIMESTAMP
        s = s.replace(/DEFAULT\s+\(?datetime\([^)]*\)\)?/gi, 'DEFAULT CURRENT_TIMESTAMP');

        // 2 args: datetime('now','tz') → NOW()
        s = s.replace(/datetime\(\s*'now'\s*,\s*'[^']*'\s*\)/gi, 'NOW()');

        // 1 arg: datetime('now') → NOW()
        s = s.replace(/datetime\(\s*'now'\s*\)/gi, 'NOW()');

        // datetime(column, 'tz') → column (timezone já configurado na sessão)
        s = s.replace(/datetime\(\s*(\w+(?:\.\w+)?)\s*,\s*'[^']*'\s*\)/gi, '$1');

        // ===== INSERT/UPDATE =====

        // INSERT OR IGNORE → INSERT IGNORE
        s = s.replace(/INSERT\s+OR\s+IGNORE/gi, 'INSERT IGNORE');

        // INSERT OR REPLACE → REPLACE
        s = s.replace(/INSERT\s+OR\s+REPLACE/gi, 'REPLACE');

        // ON CONFLICT(...) DO UPDATE SET x = excluded.x → ON DUPLICATE KEY UPDATE x = VALUES(x)
        s = s.replace(
            /ON\s+CONFLICT\s*\([^)]*\)\s*DO\s+UPDATE\s+SET\s+(.+?)(?=\s*$|\s*;)/gim,
            (match, setClauses) => {
                const mysqlSet = setClauses.replace(/excluded\.(\w+)/gi, 'VALUES($1)');
                return 'ON DUPLICATE KEY UPDATE ' + mysqlSet;
            }
        );

        // ===== Outros =====

        // COLLATE NOCASE → remove (MySQL utf8mb4_general_ci já é case-insensitive)
        s = s.replace(/\s*COLLATE\s+NOCASE/gi, '');

        // GROUP_CONCAT(col, 'sep') → GROUP_CONCAT(col SEPARATOR 'sep')
        s = s.replace(/GROUP_CONCAT\((\w+(?:\.\w+)?)\s*,\s*'([^']*)'\)/gi, "GROUP_CONCAT($1 SEPARATOR '$2')");

        return s;
    }

    // ===== API principal =====

    /**
     * db.prepare(sql) → retorna objeto com .get(), .run(), .all()
     * prepare() é SÍNCRONO. Os métodos retornados são ASYNC.
     */
    prepare(sql) {
        const mysqlSql = this._convertSql(sql);
        const self = this;

        return {
            /** Retorna a primeira linha ou undefined */
            get: async (...params) => {
                const conn = await self._acquire();
                try {
                    const flat = self._flattenParams(params);
                    const [rows] = await conn.execute(mysqlSql, flat);
                    return rows[0] || undefined;
                } finally {
                    self._release(conn);
                }
            },

            /** Executa INSERT/UPDATE/DELETE, retorna { changes, lastInsertRowid } */
            run: async (...params) => {
                const conn = await self._acquire();
                try {
                    const flat = self._flattenParams(params);
                    const [result] = await conn.execute(mysqlSql, flat);
                    return {
                        changes: result.affectedRows || 0,
                        lastInsertRowid: Number(result.insertId) || 0
                    };
                } finally {
                    self._release(conn);
                }
            },

            /** Retorna todas as linhas como array */
            all: async (...params) => {
                const conn = await self._acquire();
                try {
                    const flat = self._flattenParams(params);
                    const [rows] = await conn.execute(mysqlSql, flat);
                    return rows;
                } finally {
                    self._release(conn);
                }
            }
        };
    }

    /**
     * Executa SQL raw (sem parâmetros).
     * Usado para CREATE TABLE, ALTER TABLE, CREATE INDEX, etc.
     * Suporta múltiplos statements separados por ;
     */
    async exec(sql) {
        const conn = await this._acquire();
        try {
            const statements = this._splitStatements(sql);
            for (const stmt of statements) {
                const mysqlStmt = this._convertSql(stmt);
                if (mysqlStmt.trim()) {
                    try {
                        await conn.query(mysqlStmt);
                    } catch (err) {
                        // Ignorar erros comuns de migração:
                        // 1060: Duplicate column name (ALTER TABLE ADD COLUMN que já existe)
                        // 1061: Duplicate key name (CREATE INDEX que já existe)
                        // 1050: Table already exists
                        if (err.errno === 1060 || err.errno === 1061 || err.errno === 1050) {
                            // Silencioso
                        } else {
                            throw err;
                        }
                    }
                }
            }
        } finally {
            this._release(conn);
        }
    }

    /** Substitui PRAGMA do SQLite */
    async pragma(str) {
        if (/foreign_keys/i.test(str)) {
            const conn = await this._acquire();
            try {
                await conn.query('SET FOREIGN_KEY_CHECKS = 1');
            } finally {
                this._release(conn);
            }
        }
    }

    /**
     * Wrapper para transactions.
     * Retorna uma função async que executa fn dentro de BEGIN/COMMIT.
     * Durante a transaction, todas as chamadas db usam a mesma conexão.
     */
    transaction(fn) {
        const self = this;
        return async function (...args) {
            const conn = await self._pool.getConnection();
            await conn.query(`SET time_zone = ?`, [self._tz]);
            try {
                await conn.beginTransaction();
                const result = await self._txStore.run(conn, async () => {
                    return await fn(...args);
                });
                await conn.commit();
                return result;
            } catch (err) {
                try { await conn.rollback(); } catch (_) {}
                throw err;
            } finally {
                conn.release();
            }
        };
    }

    /**
     * Achata parâmetros para array e converte tipos.
     * SQLite aceita undefined, MySQL não.
     */
    _flattenParams(params) {
        if (params.length === 0) return [];
        let flat;
        if (params.length === 1 && Array.isArray(params[0])) {
            flat = params[0];
        } else {
            flat = params.flat();
        }
        return flat.map(v => {
            if (v === undefined) return null;
            // Converter boolean para int (compatibilidade SQLite)
            if (typeof v === 'boolean') return v ? 1 : 0;
            return v;
        });
    }

    /**
     * Divide SQL em statements respeitando strings e comentários.
     */
    _splitStatements(sql) {
        const statements = [];
        let current = '';
        let inString = false;
        let stringChar = '';
        let inLineComment = false;
        let inBlockComment = false;

        for (let i = 0; i < sql.length; i++) {
            const c = sql[i];
            const next = sql[i + 1];

            // Comentário de linha
            if (!inString && !inBlockComment && c === '-' && next === '-') {
                inLineComment = true;
                current += c;
                continue;
            }
            if (inLineComment) {
                current += c;
                if (c === '\n') inLineComment = false;
                continue;
            }

            // Comentário de bloco
            if (!inString && !inLineComment && c === '/' && next === '*') {
                inBlockComment = true;
                current += c;
                continue;
            }
            if (inBlockComment) {
                current += c;
                if (c === '*' && next === '/') {
                    current += next;
                    i++;
                    inBlockComment = false;
                }
                continue;
            }

            // Strings
            if (inString) {
                current += c;
                if (c === stringChar) {
                    // Escaped quote ('')
                    if (next === stringChar) {
                        current += next;
                        i++;
                    } else {
                        inString = false;
                    }
                }
                continue;
            }
            if (c === "'" || c === '"') {
                inString = true;
                stringChar = c;
                current += c;
                continue;
            }

            // Semicolon = statement separator
            if (c === ';') {
                if (current.trim()) {
                    statements.push(current.trim());
                }
                current = '';
                continue;
            }

            current += c;
        }

        if (current.trim()) {
            statements.push(current.trim());
        }
        return statements;
    }
}

/**
 * Factory: cria conexão MySQL com API compatível.
 * Config via .env: DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, TZ_OFFSET
 */
async function createDatabase() {
    const tz = process.env.TZ_OFFSET || '-03:00';

    const pool = mysql.createPool({
        host: process.env.DB_HOST || 'localhost',
        port: parseInt(process.env.DB_PORT) || 3306,
        user: process.env.DB_USER,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_NAME,
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0,
        charset: 'utf8mb4',
        dateStrings: true, // Retorna DATETIME como string (compatível com SQLite)
    });

    // Testar conexão
    const conn = await pool.getConnection();
    await conn.query(`SET time_zone = ?`, [tz]);
    console.log(`[mysql-compat] Conectado ao MySQL: ${process.env.DB_NAME}`);
    conn.release();

    return new MysqlCompat(pool, tz);
}

module.exports = createDatabase;
module.exports.MysqlCompat = MysqlCompat;
