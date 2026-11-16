<?php
session_start();
require_once 'conexao.php';

// Se já estiver logado, abre o painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: painel.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email == "" || $senha == "") {
        $erro = "Preencha todos os campos.";
    } else {

        try {
            $pdo = conectar();

            // Procura o usuário pelo e-mail
            $sql = "SELECT * FROM usuarios WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            $usuario = $stmt->fetch();

            // Confere a senha
            if ($usuario && password_verify($senha, $usuario['senha_hash'])) {

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];

                header("Location: painel.php");
                exit;

            } else {
                $erro = "E-mail ou senha incorretos.";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erro = "Erro ao acessar o banco de dados.";
        }
    }
}

$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini Gestão</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow-sm mx-auto" style="max-width: 450px;">

        <div class="card-body p-4">

            <h3 class="text-center mb-4">Mini Gestão - Login</h3>

            <?php if ($sucesso != ""): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($sucesso) ?>
                </div>
            <?php endif; ?>

            <?php if ($erro != ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha"
                           class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Entrar
                </button>

            </form>

            <p class="text-center mt-3">
                Não possui conta?
                <a href="cadastro.php">Cadastre-se</a>
            </p>

        </div>
    </div>

</div>

</body>
</html>