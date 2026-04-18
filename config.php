<?php
/**
 * config.php
 *
 * Configuracoes globais do sistema de Carteirinha Ministerial.
 * Conexao com Supabase (PostgreSQL), constantes da organizacao
 * e funcoes auxiliares de infraestrutura.
 *
 * @package  CarteirinhaMinisterial
 * @author   Igreja Missoes em Cristo
 */

// Conexão Supabase (PostgreSQL) — usa variáveis de ambiente em produção
define('SUPABASE_HOST', getenv('SUPABASE_HOST') ?: 'db.mrzfopdvijuwixovfsql.supabase.co');
define('SUPABASE_PORT', getenv('SUPABASE_PORT') ?: '5432');
define('SUPABASE_DB',   getenv('SUPABASE_DB')   ?: 'postgres');
define('SUPABASE_USER', getenv('SUPABASE_USER') ?: 'postgres');
define('SUPABASE_PASS', getenv('SUPABASE_PASS') ?: 'UHARfJQ290EukfEx');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações da Igreja/Organização
define('IGREJA_NOME',      getenv('IGREJA_NOME')      ?: 'Igreja Missões em Cristo');
define('IGREJA_ENDERECO',  getenv('IGREJA_ENDERECO')  ?: 'Rua Ceará, Qd. 102, Ll. 09, Setor Oeste, Paraíso do Tocantins-TO');
define('IGREJA_CNPJ',      getenv('IGREJA_CNPJ')      ?: 'CNPJ: 13.004.897/0001-73');
define('PRESIDENTE_NOME',  getenv('PRESIDENTE_NOME')  ?: 'Idevaldo de Lima');
define('PRESIDENTE_CARGO', getenv('PRESIDENTE_CARGO') ?: 'Pastor Presidente');

session_start();

// Headers de performance
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

function conectar(): PDO {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        SUPABASE_HOST, SUPABASE_PORT, SUPABASE_DB
    );
    $pdo = new PDO($dsn, SUPABASE_USER, SUPABASE_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => true,
    ]);
    return $pdo;
}

function criarTabelas(): void {
    $pdo = conectar();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ministros (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(200) NOT NULL,
            cargo VARCHAR(100) NOT NULL,
            registro VARCHAR(20) NOT NULL,
            rg VARCHAR(30),
            cpf VARCHAR(20),
            data_ordenacao DATE,
            data_validade DATE,
            nacionalidade VARCHAR(50) DEFAULT 'Brasileira',
            naturalidade VARCHAR(100),
            estado_civil VARCHAR(30),
            foto VARCHAR(255),
            ativo SMALLINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// Auto-criar tabela na primeira execução
criarTabelas();
?>
