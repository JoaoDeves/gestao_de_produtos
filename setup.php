
<?php

require_once 'conexao.php';

try {

    // Conectar ao servidor MySQL
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Criar o banco de dados
    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS mini_gestao
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    // Selecionar o banco criado
    $pdo->exec("USE mini_gestao");

    // Criar tabela de usuários
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            senha_hash VARCHAR(255) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Criar tabela de fornecedores
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fornecedores (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            cnpj VARCHAR(18),
            telefone VARCHAR(30),
            email VARCHAR(190),
            endereco VARCHAR(255),
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Criar tabela de produtos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fornecedor_id INT UNSIGNED NOT NULL,
            nome VARCHAR(150) NOT NULL,
            descricao TEXT,
            preco DECIMAL(10,2) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_produtos_fornecedores
            FOREIGN KEY (fornecedor_id)
            REFERENCES fornecedores(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT,

            CONSTRAINT chk_produto_preco
            CHECK (preco >= 0)
        )
    ");

    echo "<h2>Banco de dados criado com sucesso!</h2>";
    echo "<p>Banco: mini_gestao</p>";
    echo "<p>Tabelas criadas:</p>";
    echo "<ul>";
    echo "<li>usuarios</li>";
    echo "<li>fornecedores</li>";
    echo "<li>produtos</li>";
    echo "</ul>";

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo "Erro ao criar o banco de dados. Verifique as configurações do MySQL.";
}

?>
