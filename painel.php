
<?php
session_start();

// Impede acesso sem login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Permite sair do sistema
if (isset($_GET['sair'])) {
    $_SESSION = [];
    session_destroy();

    header("Location: index.php");
    exit;
}

$nome = $_SESSION['usuario_nome'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Mini Gestão</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">

        <span class="navbar-brand">
            Mini Gestão
        </span>

        <a href="painel.php?sair=1" class="btn btn-light btn-sm">
            Sair
        </a>

    </div>
</nav>

<div class="container mt-5">

    <h2>
        Bem-vindo,
        <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>!
    </h2>

    <p class="text-muted">
        Sistema de Gestão de Produtos
    </p>

    <hr>

    <h4>Menu principal</h4>

    <div class="d-flex gap-3 mt-4 flex-wrap">

        <a href="fornecedores.php" class="btn btn-primary">
            Fornecedores
        </a>

        <a href="produtos.php" class="btn btn-primary">
            Produtos
        </a>

        <a href="cesta.php" class="btn btn-success">
            Cesta de Compras
        </a>

    </div>

</div>

</body>
</html>
