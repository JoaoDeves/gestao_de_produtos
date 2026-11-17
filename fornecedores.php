<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Conexão com o banco
$pdo = conectar();

$mensagem = "";
$tipoMensagem = "success";

// Identifica requisições AJAX
$ajax = (
    $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''
) === 'XMLHttpRequest';

// Proteção contra envio de formulários de outros sites
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processamento dos formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {

        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);

            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Requisição inválida.'
            ]);
        } else {
            http_response_code(403);
            echo "Requisição inválida.";
        }

        exit;
    }

    $acao = $_POST['acao'] ?? '';

    try {

        // CADASTRAR OU EDITAR
        if ($acao === 'salvar') {

            $idInformado = $_POST['id'] ?? '';

            $id = filter_var(
                $idInformado,
                FILTER_VALIDATE_INT
            );

            $nome = trim($_POST['nome'] ?? '');
            $cnpj = trim($_POST['cnpj'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');

            // Validações
            if ($idInformado !== '' && (!$id || $id <= 0)) {
                throw new Exception("Fornecedor inválido.");
            }

            if ($nome === '') {
                throw new Exception(
                    "O nome do fornecedor é obrigatório."
                );
            }

            if (strlen($nome) > 150) {
                throw new Exception(
                    "O nome deve ter até 150 caracteres."
                );
            }

            if (
                $email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                throw new Exception(
                    "Informe um e-mail válido."
                );
            }

            if (
                strlen($cnpj) > 18 ||
                strlen($telefone) > 30 ||
                strlen($email) > 190 ||
                strlen($endereco) > 255
            ) {
                throw new Exception(
                    "Um dos campos ultrapassou o limite permitido."
                );
            }

            if ($id) {

                // Atualiza fornecedor existente
                $sql = "UPDATE fornecedores SET
                            nome = ?,
                            cnpj = ?,
                            telefone = ?,
                            email = ?,
                            endereco = ?
                        WHERE id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nome,
                    $cnpj ?: null,
                    $telefone ?: null,
                    $email ?: null,
                    $endereco ?: null,
                    $id
                ]);

                $mensagem = "Fornecedor atualizado com sucesso!";

            } else {

                // Cadastra novo fornecedor
                $sql = "INSERT INTO fornecedores
                        (nome, cnpj, telefone, email, endereco)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nome,
                    $cnpj ?: null,
                    $telefone ?: null,
                    $email ?: null,
                    $endereco ?: null
                ]);

                $mensagem = "Fornecedor cadastrado com sucesso!";
            }
        }

        // EXCLUIR
        elseif ($acao === 'excluir') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id <= 0) {
                throw new Exception("Fornecedor inválido.");
            }

            $stmt = $pdo->prepare(
                "DELETE FROM fornecedores WHERE id = ?"
            );

            $stmt->execute([$id]);

            $mensagem = "Fornecedor excluído com sucesso!";
        }

        else {
            throw new Exception("Ação inválida.");
        }

    } catch (PDOException $e) {

        error_log($e->getMessage());

        $mensagem = "Não foi possível concluir a operação. "
                  . "Verifique se o fornecedor possui produtos vinculados.";

        $tipoMensagem = "danger";

    } catch (Exception $e) {

        $mensagem = $e->getMessage();
        $tipoMensagem = "danger";
    }

    // RESPOSTA JSON PARA AJAX
    // Este é o novo trecho do passo 8
    if ($ajax) {

        header('Content-Type: application/json; charset=utf-8');

        $sucesso = $tipoMensagem === 'success';

        if (!$sucesso) {
            http_response_code(400);
        }

        echo json_encode([
            'sucesso' => $sucesso,
            'mensagem' => $mensagem
        ]);

        exit;
    }
}

// Busca todos os fornecedores
$stmt = $pdo->query(
    "SELECT * FROM fornecedores ORDER BY nome ASC"
);

$fornecedores = $stmt->fetchAll();

// Função para exibir textos com segurança
function h($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fornecedores - Mini Gestão</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<!-- MENU -->
<nav class="navbar navbar-dark bg-primary">
    <div class="container">

        <a class="navbar-brand" href="painel.php">
            Mini Gestão
        </a>

        <div>
            <a href="painel.php"
               class="btn btn-light btn-sm">
                Painel
            </a>

            <a href="produtos.php"
               class="btn btn-light btn-sm">
                Produtos
            </a>

            <a href="cesta.php"
               class="btn btn-light btn-sm">
                Cesta
            </a>
        </div>

    </div>
</nav>

<div class="container mt-5 mb-5">

    <h2>Cadastro de Fornecedores</h2>

    <p class="text-muted">
        Cadastre e gerencie os fornecedores dos produtos.
    </p>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-<?= h($tipoMensagem) ?>">
            <?= h($mensagem) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 id="tituloFormulario">
                Novo Fornecedor
            </h5>

            <form method="POST" id="formFornecedor">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= h($_SESSION['csrf_token']) ?>">

                <input type="hidden"
                       name="acao"
                       value="salvar">

                <input type="hidden"
                       name="id"
                       id="fornecedorId">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Nome *
                        </label>

                        <input type="text"
                               name="nome"
                               id="nome"
                               class="form-control"
                               maxlength="150"
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            CNPJ
                        </label>

                        <input type="text"
                               name="cnpj"
                               id="cnpj"
                               class="form-control"
                               maxlength="18">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Telefone
                        </label>

                        <input type="text"
                               name="telefone"
                               id="telefone"
                               class="form-control"
                               maxlength="30">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            E-mail
                        </label>

                        <input type="email"
                               name="email"
                               id="email"
                               class="form-control"
                               maxlength="190">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">
                            Endereço
                        </label>

                        <input type="text"
                               name="endereco"
                               id="endereco"
                               class="form-control"
                               maxlength="255">
                    </div>

                </div>

                <button type="submit"
                        class="btn btn-primary">
                    Salvar Fornecedor
                </button>

                <button type="button"
                        class="btn btn-secondary"
                        onclick="limparFormulario()">
                    Limpar
                </button>

            </form>

        </div>
    </div>

    <!-- LISTAGEM -->
    <div class="card shadow-sm">

        <div class="card-body">

            <h5>Fornecedores Cadastrados</h5>

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($fornecedores as $f): ?>

                        <tr>
                            <td><?= h($f['id']) ?></td>
                            <td><?= h($f['nome']) ?></td>
                            <td><?= h($f['cnpj']) ?></td>
                            <td><?= h($f['telefone']) ?></td>
                            <td><?= h($f['email']) ?></td>

                            <td>

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm btn-editar"
                                    data-fornecedor="<?= h(json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>">
                                    Editar
                                </button>

                                <!-- Exclusão via AJAX -->
                                <form method="POST"
                                      class="d-inline form-excluir">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?= h($_SESSION['csrf_token']) ?>">

                                    <input type="hidden"
                                           name="acao"
                                           value="excluir">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= h($f['id']) ?>">

                                    <button type="submit"
                                            class="btn btn-danger btn-sm">
                                        Excluir
                                    </button>

                                </form>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

            <?php if (count($fornecedores) === 0): ?>
                <p class="text-muted">
                    Nenhum fornecedor cadastrado.
                </p>
            <?php endif; ?>

        </div>
    </div>

</div>

<script>
function editarFornecedor(f) {

    document.getElementById('fornecedorId').value = f.id;
    document.getElementById('nome').value = f.nome;
    document.getElementById('cnpj').value = f.cnpj ?? '';
    document.getElementById('telefone').value = f.telefone ?? '';
    document.getElementById('email').value = f.email ?? '';
    document.getElementById('endereco').value = f.endereco ?? '';

    document.getElementById('tituloFormulario').innerText =
        'Editar Fornecedor';

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function limparFormulario() {

    document.getElementById('formFornecedor').reset();
    document.getElementById('fornecedorId').value = '';

    document.getElementById('tituloFormulario').innerText =
        'Novo Fornecedor';
}

// Delegação de eventos: funciona mesmo após atualizar a tabela
document.addEventListener('click', function(evento) {

    const botao = evento.target.closest('.btn-editar');

    if (!botao || !botao.dataset.fornecedor) return;

    editarFornecedor(JSON.parse(botao.dataset.fornecedor));
});
</script>

<!-- Carrega o AJAX do passo 8 -->
<script src="script.js"></script>

</body>
</html>
