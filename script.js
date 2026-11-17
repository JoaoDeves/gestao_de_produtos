
document.addEventListener("DOMContentLoaded", function () {

    // ========================================
    // 1. IDENTIFICAR OS FORMULÁRIOS
    // ========================================

    const formulario = document.querySelector(
        "#formFornecedor, #formProduto"
    );

    const formCesta = document.getElementById("formCesta");

    // ========================================
    // 2. FUNÇÃO PARA EXIBIR MENSAGENS
    // ========================================

    function mostrarMensagem(mensagem) {
        alert(mensagem);
    }

    // ========================================
    // 3. ATUALIZAR AS TABELAS VIA AJAX
    // ========================================

    async function atualizarTabelas() {

        const resposta = await fetch(window.location.pathname, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!resposta.ok) {
            throw new Error("Erro ao atualizar as tabelas.");
        }

        const html = await resposta.text();

        // Converte o HTML recebido em um documento
        const documento = new DOMParser().parseFromString(
            html,
            "text/html"
        );

        // Atualiza a tabela de fornecedores ou produtos
        const tabelaAtual = document.querySelector(
            ".table-responsive table tbody"
        );

        const tabelaNova = documento.querySelector(
            ".table-responsive table tbody"
        );

        if (tabelaAtual && tabelaNova) {
            tabelaAtual.innerHTML = tabelaNova.innerHTML;
        }

        // Atualiza também a seção de seleção para a cesta
        const cestaAtual = document.getElementById("formCesta");
        const cestaNova = documento.getElementById("formCesta");

        if (cestaAtual && cestaNova) {
            cestaAtual.innerHTML = cestaNova.innerHTML;
        }

        // Atualiza a mensagem de lista vazia, se existir
        const cardsAtuais = document.querySelectorAll(".card");
        const cardsNovos = documento.querySelectorAll(".card");

        // Mantém o restante da página intacto
        // Os formulários principais não são substituídos
    }

    // ========================================
    // 4. CADASTRAR E EDITAR VIA AJAX
    // ========================================

    if (formulario) {

        formulario.addEventListener("submit", async function (evento) {

            evento.preventDefault();

            const dados = new FormData(formulario);

            const botao = formulario.querySelector(
                'button[type="submit"]'
            );

            if (botao) {
                botao.disabled = true;
            }

            try {

                const resposta = await fetch(
                    window.location.pathname,
                    {
                        method: "POST",
                        credentials: "same-origin",
                        body: dados,
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    }
                );

                const resultado = await resposta.json();

                if (!resposta.ok || !resultado.sucesso) {
                    mostrarMensagem(
                        resultado.mensagem || "Erro ao salvar."
                    );
                    return;
                }

                // Atualiza as tabelas sem recarregar a página
                await atualizarTabelas();

                mostrarMensagem(resultado.mensagem);

                // Limpa o formulário após salvar
                if (typeof limparFormulario === "function") {
                    limparFormulario();
                }

            } catch (erro) {

                console.error("Erro no AJAX:", erro);

                mostrarMensagem(
                    "Erro de comunicação com o servidor."
                );

            } finally {

                if (botao) {
                    botao.disabled = false;
                }
            }
        });
    }

    // ========================================
    // 5. EXCLUIR VIA AJAX
    // ========================================

    document.addEventListener("submit", async function (evento) {

        const form = evento.target;

        if (!form.matches("form.form-excluir")) {
            return;
        }

        evento.preventDefault();

        const confirmar = confirm(
            "Deseja realmente excluir este registro?"
        );

        if (!confirmar) {
            return;
        }

        const botao = form.querySelector(
            'button[type="submit"]'
        );

        if (botao) {
            botao.disabled = true;
        }

        try {

            const resposta = await fetch(
                window.location.pathname,
                {
                    method: "POST",
                    credentials: "same-origin",
                    body: new FormData(form),
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                }
            );

            const resultado = await resposta.json();

            if (!resposta.ok || !resultado.sucesso) {
                mostrarMensagem(
                    resultado.mensagem || "Erro ao excluir."
                );
                return;
            }

            await atualizarTabelas();

            mostrarMensagem(resultado.mensagem);

        } catch (erro) {

            console.error("Erro no AJAX:", erro);

            mostrarMensagem(
                "Erro de comunicação com o servidor."
            );

        } finally {

            if (botao) {
                botao.disabled = false;
            }
        }
    });

    // ========================================
    // 6. VALIDAÇÃO DA CESTA DE COMPRAS
    // ========================================

    if (formCesta) {

        formCesta.addEventListener("submit", function (evento) {

            const selecionados = formCesta.querySelectorAll(
                ".checkbox-produto:checked"
            );

            if (selecionados.length === 0) {

                evento.preventDefault();

                mostrarMensagem(
                    "Selecione pelo menos um produto para adicionar à cesta."
                );

                return;
            }

            // Se houver produtos selecionados,
            // o formulário será enviado para cesta.php
        });
    }

});
