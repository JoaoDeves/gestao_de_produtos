<?php
session_start();

// Conexão com o banco de dados
require_once __DIR__ . '/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = conectar();

// Cria o token de segurança
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa a cesta
if (!isset($_SESSION['cesta'])) {
    $_SESSION['cesta'] = [];
}

$mensagem = "";
$tipoMensagem = "success";

// PROCESSAMENTO DAS AÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    // Validação de segurança
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Requisição inválida.");
    }

    $acao = $_POST['acao'] ?? '';

    try {

        // ADICIONAR PRODUTOS
        if ($acao === 'adicionar') {

            $selecionados = $_POST['produtos'] ?? [];

            if (
                !is_array($selecionados) ||
                count($selecionados) === 0
            ) {
                throw new Exception(
                    "Selecione pelo menos um produto."
                );
            }

            $ids = [];

            // Valida os produtos selecionados
            foreach ($selecionados as $produtoId) {

                $id = filter_var(
                    $produtoId,
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id <= 0) {
                    throw new Exception(
                        "Produto selecionado inválido."
                    );
                }

                $ids[] = $id;
            }

            // Impede IDs duplicados
            $ids = array_values(array_unique($ids));

            // Confere se os produtos existem no banco
            $marcadores = implode(
                ',',
                array_fill(0, count($ids), '?')
            );

            $stmt = $pdo->prepare(
                "SELECT id FROM produtos
                 WHERE id IN ($marcadores)"
            );

            $stmt->execute($ids);

            $idsValidos = array_map(
                'intval',
                $stmt->fetchAll(PDO::FETCH_COLUMN)
            );

            // Adiciona somente produtos existentes
            foreach ($idsValidos as $id) {

                if (!in_array(
                    $id,
                    $_SESSION['cesta'],
                    true
                )) {
                    $_SESSION['cesta'][] = $id;
                }
            }

            $mensagem = "Produtos adicionados à cesta!";
        }

        // REMOVER PRODUTO
        elseif ($acao === 'remover') {

            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id <= 0) {
                throw new Exception(
                    "Produto inválido."
                );
            }

            $_SESSION['cesta'] = array_values(
                array_filter(
                    $_SESSION['cesta'],
                    function ($produtoId) use ($id) {
                        return (int)$produtoId !== $id;
                    }
                )
            );

            $mensagem = "Produto removido da cesta!";
        }

        // ESVAZIAR CESTA
        elseif ($acao === 'esvaziar') {

            $_SESSION['cesta'] = [];

            $mensagem = "Cesta esvaziada com sucesso!";
        }

        else {
            throw new Exception("Ação inválida.");
        }

    } catch (Exception $e) {

        $mensagem = $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// CONSULTAR PRODUTOS DA CESTA
$produtos = [];

if (!empty($_SESSION['cesta'])) {

    $ids = array_values(
        array_unique(
            array_map('intval', $_SESSION['cesta'])
        )
    );

    $marcadores = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $sql = "SELECT
                p.id,
                p.nome,
                p.preco,
                f.nome AS fornecedor_nome
            FROM produtos p
            INNER JOIN fornecedores f
                ON p.fornecedor_id = f.id
            WHERE p.id IN ($marcadores)
            ORDER BY p.nome ASC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($ids);

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remove da sessão produtos que não existem mais
    $_SESSION['cesta'] = array_map(
        'intval',
        array_column($produtos, 'id')
    );
}

// CALCULAR QUANTIDADE E TOTAL
$quantidade = count($produtos);

$total = 0;

foreach ($produtos as $produto) {
    $total += (float)$produto['preco'];
}

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

    <title>Cesta de Compras - Mini Gestão</title>

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

            <a href="fornecedores.php"
               class="btn btn-light btn-sm">
                Fornecedores
            </a>

        </div>

    </div>

</nav>

<!-- CONTEÚDO PRINCIPAL -->
<div class="container mt-5 mb-5">

    <h2>Minha Cesta de Compras</h2>

    <p class="text-muted">
        Visualize os produtos selecionados e o valor total.
    </p>

    <!-- MENSAGENS -->
    <?php if ($mensagem !== ''): ?>

        <div class="alert alert-<?= h($tipoMensagem) ?>">
            <?= h($mensagem) ?>
        </div>

    <?php endif; ?>

    <!-- RESUMO DA CESTA -->
    <div class="row mb-4">

        <div class="col-md-6 mb-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h5>Quantidade de Produtos</h5>

                    <h3><?= $quantidade ?></h3>

                </div>

            </div>

        </div>

        <div class="col-md-6 mb-3">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h5>Valor Total</h5>

                    <h3>
                        R$ <?= number_format(
                            $total,
                            2,
                            ',',
                            '.'
                        ) ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <!-- LISTAGEM DOS PRODUTOS -->
    <div class="card shadow-sm">

        <div class="card-body">

            <h5>Produtos Selecionados</h5>

            <?php if ($quantidade > 0): ?>

                <div class="table-responsive">

                    <table class="table table-striped table-hover">

                        <thead>

                            <tr>
                                <th>Produto</th>
                                <th>Fornecedor</th>
                                <th>Quantidade</th>
                                <th>Preço</th>
                                <th>Ações</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($produtos as $produto): ?>

                            <tr>

                                <td>
                                    <?= h($produto['nome']) ?>
                                </td>

                                <td>
                                    <?= h($produto['fornecedor_nome']) ?>
                                </td>

                                <td>1</td>

                                <td>
                                    R$ <?= number_format(
                                        (float)$produto['preco'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>

                                    <!-- REMOVER PRODUTO -->
                                    <form method="POST"
                                          class="form-remover-cesta">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h($_SESSION['csrf_token']) ?>">

                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="remover">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= h($produto['id']) ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm">
                                            Remover
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- TOTAL -->
                <div class="text-end mt-3">

                    <h4>
                        Total: R$ <?= number_format(
                            $total,
                            2,
                            ',',
                            '.'
                        ) ?>
                    </h4>

                </div>

                <!-- ESVAZIAR CESTA -->
                <form method="POST"
                      id="formEsvaziarCesta">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($_SESSION['csrf_token']) ?>">

                    <input
                        type="hidden"
                        name="acao"
                        value="esvaziar">

                    <button
                        type="submit"
                        class="btn btn-outline-danger">
                        Esvaziar Cesta
                    </button>

                </form>

            <?php else: ?>

                <p class="text-muted mt-3">
                    Sua cesta está vazia.
                </p>

            <?php endif; ?>

            <!-- VOLTAR PARA PRODUTOS -->
            <a href="produtos.php"
               class="btn btn-primary mt-3">
                Selecionar Mais Produtos
            </a>

        </div>

    </div>

</div>

<!-- JavaScript externo -->
<script src="script.js"></script>

</body>
</html>