<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Conexão com o banco de dados
$pdo = conectar();

$mensagem = "";
$tipoMensagem = "success";

// Identifica requisições AJAX
$ajax = (
    $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''
) === 'XMLHttpRequest';

// Proteção dos formulários
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processamento dos formulários de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {

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
            $descricao = trim($_POST['descricao'] ?? '');
            $precoInformado = trim($_POST['preco'] ?? '');

            $fornecedorId = filter_var(
                $_POST['fornecedor_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            // Validações
            if (
                $idInformado !== '' &&
                (!$id || $id <= 0)
            ) {
                throw new Exception("Produto inválido.");
            }

            if ($nome === '') {
                throw new Exception(
                    "O nome do produto é obrigatório."
                );
            }

            if (strlen($nome) > 150) {
                throw new Exception(
                    "O nome deve ter até 150 caracteres."
                );
            }

            if (!$fornecedorId || $fornecedorId <= 0) {
                throw new Exception(
                    "Selecione um fornecedor válido."
                );
            }

            // Valida o preço
            if (!preg_match(
                '/^\d{1,8}(\.\d{1,2})?$/',
                $precoInformado
            )) {
                throw new Exception(
                    "Informe um preço válido."
                );
            }

            // Confere se o fornecedor existe
            $stmt = $pdo->prepare(
                "SELECT id FROM fornecedores WHERE id = ?"
            );

            $stmt->execute([$fornecedorId]);

            if (!$stmt->fetch()) {
                throw new Exception(
                    "O fornecedor selecionado não existe."
                );
            }

            // ATUALIZAR PRODUTO
            if ($id) {

                $sql = "UPDATE produtos SET
                            nome = ?,
                            descricao = ?,
                            preco = ?,
                            fornecedor_id = ?
                        WHERE id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nome,
                    $descricao ?: null,
                    $precoInformado,
                    $fornecedorId,
                    $id
                ]);

                $mensagem = "Produto atualizado com sucesso!";

            } else {

                // CADASTRAR PRODUTO
                $sql = "INSERT INTO produtos
                        (nome, descricao, preco, fornecedor_id)
                        VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nome,
                    $descricao ?: null,
                    $precoInformado,
                    $fornecedorId
                ]);

                $mensagem = "Produto cadastrado com sucesso!";
            }
        }

        // EXCLUIR PRODUTO
        elseif ($acao === 'excluir') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id <= 0) {
                throw new Exception("Produto inválido.");
            }

            $stmt = $pdo->prepare(
                "DELETE FROM produtos WHERE id = ?"
            );

            $stmt->execute([$id]);

            $mensagem = "Produto excluído com sucesso!";
        }

        else {
            throw new Exception("Ação inválida.");
        }

    } catch (PDOException $e) {

        error_log($e->getMessage());

        $mensagem = "Erro ao realizar operação no banco.";
        $tipoMensagem = "danger";

    } catch (Exception $e) {

        $mensagem = $e->getMessage();
        $tipoMensagem = "danger";
    }

    // RESPOSTA JSON PARA AJAX
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

// Busca os fornecedores para o campo select
$stmt = $pdo->query(
    "SELECT id, nome FROM fornecedores ORDER BY nome ASC"
);

$fornecedores = $stmt->fetchAll();

// Busca os produtos com seus fornecedores
$stmt = $pdo->query("
    SELECT
        p.id,
        p.nome,
        p.descricao,
        p.preco,
        p.fornecedor_id,
        f.nome AS fornecedor_nome
    FROM produtos p
    INNER JOIN fornecedores f
        ON p.fornecedor_id = f.id
    ORDER BY p.nome ASC
");

$produtos = $stmt->fetchAll();

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

    <title>Produtos - Mini Gestão</title>

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

            <a href="fornecedores.php"
               class="btn btn-light btn-sm">
                Fornecedores
            </a>

            <a href="cesta.php"
               class="btn btn-light btn-sm">
                Cesta
            </a>
        </div>

    </div>
</nav>

<div class="container mt-5 mb-5">

    <h2>Cadastro de Produtos</h2>

    <p class="text-muted">
        Cadastre, gerencie e selecione produtos para a cesta.
    </p>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-<?= h($tipoMensagem) ?>">
            <?= h($mensagem) ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO DE CADASTRO -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 id="tituloFormulario">
                Novo Produto
            </h5>

            <form method="POST" id="formProduto">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= h($_SESSION['csrf_token']) ?>">

                <input type="hidden"
                       name="acao"
                       value="salvar">

                <input type="hidden"
                       name="id"
                       id="produtoId">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Nome do Produto *
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
                            Fornecedor *
                        </label>

                        <select name="fornecedor_id"
                                id="fornecedor_id"
                                class="form-select"
                                required>

                            <option value="">
                                Selecione um fornecedor
                            </option>

                            <?php foreach ($fornecedores as $f): ?>

                                <option value="<?= h($f['id']) ?>">
                                    <?= h($f['nome']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Preço (R$) *
                        </label>

                        <input type="number"
                               name="preco"
                               id="preco"
                               class="form-control"
                               min="0"
                               max="99999999.99"
                               step="0.01"
                               placeholder="0.00"
                               required>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">
                            Descrição
                        </label>

                        <textarea name="descricao"
                                  id="descricao"
                                  class="form-control"
                                  rows="3"></textarea>
                    </div>

                </div>

                <button type="submit"
                        class="btn btn-primary">
                    Salvar Produto
                </button>

                <button type="button"
                        class="btn btn-secondary"
                        onclick="limparFormulario()">
                    Limpar
                </button>

            </form>

        </div>
    </div>

    <!-- LISTAGEM DE PRODUTOS -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5>Produtos Cadastrados</h5>

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Fornecedor</th>
                            <th>Preço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($produtos as $p): ?>

                        <tr>
                            <td><?= h($p['id']) ?></td>
                            <td><?= h($p['nome']) ?></td>

                            <td>
                                <?= h($p['fornecedor_nome']) ?>
                            </td>

                            <td>
                                R$ <?= number_format(
                                    (float)$p['preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>

                                <!-- EDITAR -->
                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm btn-editar"
                                    data-produto="<?= h(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>">
                                    Editar
                                </button>

                                <!-- EXCLUIR -->
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
                                           value="<?= h($p['id']) ?>">

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

            <?php if (count($produtos) === 0): ?>
                <p class="text-muted">
                    Nenhum produto cadastrado.
                </p>
            <?php endif; ?>

        </div>
    </div>

    <!-- SELEÇÃO DE PRODUTOS PARA A CESTA -->
    <div class="card shadow-sm">

        <div class="card-body">

            <h5>Selecionar Produtos para a Cesta</h5>

            <p class="text-muted">
                Marque os produtos que deseja adicionar.
                Cada produto será incluído com quantidade igual a 1.
            </p>

            <form method="POST"
                  action="cesta.php"
                  id="formCesta">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= h($_SESSION['csrf_token']) ?>">

                <input type="hidden"
                       name="acao"
                       value="adicionar">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>
                            <tr>
                                <th>Selecionar</th>
                                <th>Produto</th>
                                <th>Fornecedor</th>
                                <th>Preço</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($produtos as $p): ?>

                            <tr>

                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input checkbox-produto"
                                        name="produtos[]"
                                        value="<?= h($p['id']) ?>">
                                </td>

                                <td><?= h($p['nome']) ?></td>

                                <td>
                                    <?= h($p['fornecedor_nome']) ?>
                                </td>

                                <td>
                                    R$ <?= number_format(
                                        (float)$p['preco'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

                <?php if (count($produtos) > 0): ?>

                    <button type="submit"
                            class="btn btn-success">
                        Adicionar à Cesta
                    </button>

                <?php else: ?>

                    <p class="text-muted">
                        Cadastre produtos antes de utilizar a cesta.
                    </p>

                <?php endif; ?>

                <a href="cesta.php"
                   class="btn btn-outline-primary">
                    Visualizar Cesta
                </a>

            </form>

        </div>
    </div>

</div>

<script>
// Preenche o formulário para edição
function editarProduto(p) {

    document.getElementById('produtoId').value = p.id;
    document.getElementById('nome').value = p.nome;
    document.getElementById('descricao').value = p.descricao ?? '';
    document.getElementById('preco').value = p.preco;
    document.getElementById('fornecedor_id').value = p.fornecedor_id;

    document.getElementById('tituloFormulario').innerText =
        'Editar Produto';

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Limpa o formulário
function limparFormulario() {

    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = '';

    document.getElementById('tituloFormulario').innerText =
        'Novo Produto';
}

// Delegação de eventos para o botão Editar
document.addEventListener('click', function(evento) {

    const botao = evento.target.closest('.btn-editar');

    if (!botao || !botao.dataset.produto) return;

    editarProduto(JSON.parse(botao.dataset.produto));
});

</script>

<!-- AJAX do passo 8 -->
<script src="script.js"></script>

</body>
</html>