
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

// Proteção dos formulários
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processamento dos formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit("Requisição inválida.");
    }

    $acao = $_POST['acao'] ?? '';

    try {

        // CADASTRAR OU EDITAR
        if ($acao === 'salvar') {

            $idOriginal = $_POST['id'] ?? '';
            $id = filter_var(
                $idOriginal,
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
            if ($idOriginal !== '' && (!$id || $id <= 0)) {
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

            // Valida o preço no formato 99.90
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

            // Atualiza um produto
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

                // Cadastra um produto
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

        // EXCLUIR
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
}

// Buscar fornecedores para o campo select
$stmt = $pdo->query(
    "SELECT id, nome FROM fornecedores ORDER BY nome"
);

$fornecedores = $stmt->fetchAll();

// Buscar produtos com seus fornecedores
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
    ORDER BY p.nome
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
        Cadastre e gerencie os produtos do sistema.
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

    <!-- LISTAGEM -->
    <div class="card shadow-sm">

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

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm btn-editar"
                                    data-produto="<?= h(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>">
                                    Editar
                                </button>

                                <form method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Deseja excluir este produto?')">

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

</div>

<script>
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

function limparFormulario() {

    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = '';

    document.getElementById('tituloFormulario').innerText =
        'Novo Produto';
}

document.querySelectorAll('.btn-editar').forEach(botao => {
    botao.addEventListener('click', () => {
        editarProduto(JSON.parse(botao.dataset.produto));
    });
});
</script>

</body>
</html>
