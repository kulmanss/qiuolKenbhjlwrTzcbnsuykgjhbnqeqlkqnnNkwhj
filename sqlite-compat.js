/**
 * sqlite-compat.js
 * Wrapper de compatibilidade que faz sql.js funcionar com a mesma API do better-sqlite3.
 * Assim o server.js não precisa de nenhuma mudança nas queries.
 * 
 * Diferença: sql.js roda SQLite em WASM puro (JavaScript) — não precisa compilar nada.
 * O banco é carregado na memória e salvo em disco periodicamente.
 */

const initSqlJs = require('sql.js');
const fs = require('fs');
const path = require('path');

class SqliteCompat {
    /**
     * @param {import('sql.js').Database} sqlDb - instância sql.js
     * @param {string} filePath - caminho do arquivo .db para auto-save
     */
    constructor(sqlDb, filePath) {
        this._db = sqlDb;
        this._filePath = filePath;
        this._saveTimer = null;
        this._dirty = false;
        this._stmtCache = new Map(); // Cache de statements preparados
        this._stmtCacheMax = 200;    // Máximo de statements em cache

        // Auto-save a cada 10 segundos SE houve mudanças
        this._saveTimer = setInterval(() => {
            if (this._dirty) {
                this.saveToFile();
                this._dirty = false;
            }
        }, 10000);

        // Salvar ao encerrar
        const gracefulSave = () => {
            this.saveToFile();
            process.exit(0);
        };
        process.on('SIGINT', gracefulSave);
        process.on('SIGTERM', gracefulSave);
    }

    /** Salva o banco em disco */
    saveToFile() {
        try {
            const data = this._db.export();
            const buffer = Buffer.from(data);
            // Tentar escrita atômica (tmp + rename)
            const tmpPath = this._filePath + '.tmp';
            try {
                fs.writeFileSync(tmpPath, buffer);
                fs.renameSync(tmpPath, this._filePath);
            } catch (renameErr) {
                // No Windows, rename pode falhar se o arquivo estiver em uso
                // Fallback: escrever diretamente
                fs.writeFileSync(this._filePath, buffer);
                // Limpar tmp se existir
                try { fs.unlinkSync(tmpPath); } catch (_) {}
            }
        } catch (e) {
            console.error('[sqlite-compat] Erro ao salvar banco:', e.message);
        }
    }

    /** Marca que houve mudança (para o auto-save) */
    _markDirty() {
        this._dirty = true;
    }

    /**
     * Obtém ou cria um statement compilado do cache
     * Statements compilados são MUITO mais rápidos que recriar toda vez
     */
    _getCachedStmt(sql) {
        let stmt = this._stmtCache.get(sql);
        if (stmt) return stmt;
        // Evitar vazamento de memória - limpar statements antigos
        if (this._stmtCache.size >= this._stmtCacheMax) {
            const firstKey = this._stmtCache.keys().next().value;
            try { this._stmtCache.get(firstKey).free(); } catch(_) {}
            this._stmtCache.delete(firstKey);
        }
        stmt = this._db.prepare(sql);
        this._stmtCache.set(sql, stmt);
        return stmt;
    }

    /**
     * Emula db.prepare(sql) do better-sqlite3
     * Retorna um objeto com .run(), .get(), .all()
     * Usa cache de statements para performance
     */
    prepare(sql) {
        const self = this;
        return {
            /**
             * Executa INSERT/UPDATE/DELETE e retorna { changes, lastInsertRowid }
             */
            run(...params) {
                const flatParams = self._flattenParams(params);
                const resolvedSql = self._resolveNamedParams(sql, flatParams);
                const finalParams = resolvedSql.params;
                try {
                    self._db.run(resolvedSql.sql, finalParams);
                    self._markDirty();
                    const changes = self._db.getRowsModified();
                    // Obter last_insert_rowid
                    const lastRow = self._db.exec("SELECT last_insert_rowid() as id");
                    const lastInsertRowid = lastRow.length > 0 ? lastRow[0].values[0][0] : 0;
                    return { changes, lastInsertRowid };
                } catch (e) {
                    throw new Error(`[sqlite-compat] run() error: ${e.message}\nSQL: ${sql}\nParams: ${JSON.stringify(finalParams)}`);
                }
            },

            /**
             * Retorna uma única linha (ou undefined)
             * Usa statement cacheado para performance
             */
            get(...params) {
                const flatParams = self._flattenParams(params);
                const resolvedSql = self._resolveNamedParams(sql, flatParams);
                const finalParams = resolvedSql.params;
                try {
                    const stmt = self._getCachedStmt(resolvedSql.sql);
                    stmt.reset();
                    stmt.bind(finalParams);
                    let result = undefined;
                    if (stmt.step()) {
                        result = stmt.getAsObject();
                    }
                    stmt.reset();
                    return result;
                } catch (e) {
                    // Se cache deu problema, tentar sem cache
                    try {
                        self._stmtCache.delete(resolvedSql.sql);
                        const stmt = self._db.prepare(resolvedSql.sql);
                        stmt.bind(finalParams);
                        let result = undefined;
                        if (stmt.step()) { result = stmt.getAsObject(); }
                        stmt.free();
                        return result;
                    } catch (e2) {
                        throw new Error(`[sqlite-compat] get() error: ${e2.message}\nSQL: ${sql}\nParams: ${JSON.stringify(finalParams)}`);
                    }
                }
            },

            /**
             * Retorna todas as linhas como array de objetos
             * Usa statement cacheado para performance
             */
            all(...params) {
                const flatParams = self._flattenParams(params);
                const resolvedSql = self._resolveNamedParams(sql, flatParams);
                const finalParams = resolvedSql.params;
                try {
                    const stmt = self._getCachedStmt(resolvedSql.sql);
                    stmt.reset();
                    stmt.bind(finalParams);
                    const results = [];
                    while (stmt.step()) {
                        results.push(stmt.getAsObject());
                    }
                    stmt.reset();
                    return results;
                } catch (e) {
                    // Se cache deu problema, tentar sem cache
                    try {
                        self._stmtCache.delete(resolvedSql.sql);
                        const stmt = self._db.prepare(resolvedSql.sql);
                        stmt.bind(finalParams);
                        const results = [];
                        while (stmt.step()) { results.push(stmt.getAsObject()); }
                        stmt.free();
                        return results;
                    } catch (e2) {
                        throw new Error(`[sqlite-compat] all() error: ${e2.message}\nSQL: ${sql}\nParams: ${JSON.stringify(finalParams)}`);
                    }
                }
            }
        };
    }

    /**
     * Emula db.exec(sql) - executa SQL sem parâmetros (pode ser multi-statement)
     * Usa db.exec() do sql.js que suporta múltiplas statements separadas por ;
     */
    exec(sql) {
        try {
            this._db.exec(sql);
            this._markDirty();
        } catch (e) {
            throw new Error(`[sqlite-compat] exec() error: ${e.message}\nSQL: ${sql.substring(0, 200)}`);
        }
    }

    /**
     * Emula db.pragma(str) do better-sqlite3
     */
    pragma(pragmaStr) {
        try {
            const result = this._db.exec(`PRAGMA ${pragmaStr}`);
            if (result.length > 0 && result[0].values.length > 0) {
                return result[0].values[0][0];
            }
            return undefined;
        } catch (e) {
            // Ignorar erros de pragma não suportados
            console.warn(`[sqlite-compat] PRAGMA ${pragmaStr} ignorado:`, e.message);
        }
    }

    /**
     * Emula db.transaction(fn) do better-sqlite3
     * Retorna uma função que executa fn dentro de BEGIN/COMMIT
     */
    transaction(fn) {
        const self = this;
        return function (...args) {
            self._db.run('BEGIN TRANSACTION');
            try {
                const result = fn.apply(this, args);
                self._db.run('COMMIT');
                self._markDirty();
                return result;
            } catch (e) {
                self._db.run('ROLLBACK');
                throw e;
            }
        };
    }

    /** Fecha o banco e salva */
    close() {
        if (this._saveTimer) {
            clearInterval(this._saveTimer);
            this._saveTimer = null;
        }
        // Liberar statements em cache
        for (const stmt of this._stmtCache.values()) {
            try { stmt.free(); } catch(_) {}
        }
        this._stmtCache.clear();
        this.saveToFile();
        this._db.close();
    }

    /**
     * Converte parâmetros nomeados (@param) para posicionais (?)
     * better-sqlite3 suporta { param: value } - sql.js não
     */
    _resolveNamedParams(sql, params) {
        // Se params é um array simples, retornar como está
        if (Array.isArray(params) && params.length > 0 && typeof params[0] !== 'object') {
            return { sql, params };
        }

        // Se é um objeto com named params (ex: { uid: '123', recadosVistosEm: '...' })
        if (params.length === 1 && typeof params[0] === 'object' && params[0] !== null && !Array.isArray(params[0])) {
            const obj = params[0];
            const newParams = [];
            const newSql = sql.replace(/@(\w+)/g, (match, name) => {
                if (name in obj) {
                    newParams.push(obj[name]);
                    return '?';
                }
                return match; // Manter se não encontrar
            });
            return { sql: newSql, params: newParams };
        }

        // Array vazio ou params normais
        return { sql, params: params.length > 0 ? params : [] };
    }

    /**
     * Achata parâmetros: better-sqlite3 aceita (a, b, c) ou ([a, b, c])
     * sql.js espera array
     */
    _flattenParams(params) {
        if (params.length === 0) return [];
        // Se primeiro param é object (named params), retornar como está
        if (params.length === 1 && typeof params[0] === 'object' && params[0] !== null && !Array.isArray(params[0])) {
            return params;
        }
        // Achatar se necessário
        return params;
    }
}

/**
 * Factory: cria uma instância compatível com better-sqlite3
 * Uso: const db = await createDatabase('caminho/banco.db');
 *
 * @param {string} filePath - caminho do arquivo .db
 * @returns {Promise<SqliteCompat>}
 */
async function createDatabase(filePath) {
    // Forçar caminho do WASM para funcionar em qualquer ambiente (cPanel, etc.)
    const wasmPath = path.join(
        path.dirname(require.resolve('sql.js')),
        'sql-wasm.wasm'
    );
    const SQL = await initSqlJs({
        locateFile: () => wasmPath
    });

    let sqlDb;
    if (fs.existsSync(filePath)) {
        const fileBuffer = fs.readFileSync(filePath);
        sqlDb = new SQL.Database(fileBuffer);
        console.log(`[sqlite-compat] Banco carregado: ${filePath} (${(fileBuffer.length / 1024).toFixed(0)} KB)`);
    } else {
        sqlDb = new SQL.Database();
        console.log(`[sqlite-compat] Novo banco criado: ${filePath}`);
    }

    return new SqliteCompat(sqlDb, filePath);
}

module.exports = createDatabase;
module.exports.SqliteCompat = SqliteCompat;
