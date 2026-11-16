
<?php
session_start();
require_once 'conexao.php';

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome == "" || $email == "" || $senha == "") {
        $mensagem = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
    } else {

        try {
            $pdo = conectar();

            // Verifica se o e-mail já existe
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $mensagem = "Este e-mail já está cadastrado.";
            } else {

                // Protege a senha com hash
                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                // Salva o usuário no banco
                $sql = "INSERT INTO usuarios
                        (nome, email, senha_hash)
                        VALUES (?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nome,
                    $email,
                    $senhaHash
                ]);

                $_SESSION['sucesso'] = "Cadastro realizado com sucesso!";
                header("Location: index.php");
                exit;
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $mensagem = "Erro ao realizar cadastro.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Mini Gestão</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow-sm mx-auto" style="max-width: 450px;">

        <div class="card-body p-4">

            <h3 class="text-center mb-4">Cadastro de Usuário</h3>

            <?php if ($mensagem != ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha"
                           class="form-control"
                           minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Cadastrar
                </button>

            </form>

            <p class="text-center mt-3">
                Já possui conta?
                <a href="index.php">Fazer login</a>
            </p>

        </div>
    </div>

</div>

</body>
</html>
