<?php

// Dados de acesso ao MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'mini_gestao');
define('DB_USER', 'root');
define('DB_PASS', 'avecristorei');

// Função para conectar ao banco de dados
function conectar() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        return $pdo;

    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados.");
    }
}
?>
