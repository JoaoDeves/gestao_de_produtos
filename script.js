
document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.querySelector(
        "#formFornecedor, #formProduto"
    );

    if (!formulario) return;

    const tabela = document.querySelector("table tbody");

    // Envia os dados por AJAX
    formulario.addEventListener("submit", async function (evento) {

        evento.preventDefault();

        const dados = new FormData(formulario);

        try {
            const resposta = await fetch(window.location.pathname, {
                method: "POST",
                body: dados,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const resultado = await resposta.json();

            if (!resposta.ok || !resultado.sucesso) {
                alert(resultado.mensagem || "Erro ao salvar.");
                return;
            }

            alert(resultado.mensagem);

            // Atualiza a listagem sem recarregar a página
            await atualizarTabela();

            // Limpa o formulário
            if (typeof limparFormulario === "function") {
                limparFormulario();
            }

        } catch (erro) {
            console.error(erro);
            alert("Erro na comunicação com o servidor.");
        }
    });

    // Intercepta os formulários de exclusão
    document.addEventListener("submit", async function (evento) {

        const form = evento.target;

        if (!form.matches("form.form-excluir")) return;

        evento.preventDefault();

        if (!confirm("Deseja realmente excluir este registro?")) {
            return;
        }

        try {
            const resposta = await fetch(window.location.pathname, {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const resultado = await resposta.json();

            if (!resposta.ok || !resultado.sucesso) {
                alert(resultado.mensagem || "Erro ao excluir.");
                return;
            }

            alert(resultado.mensagem);
            await atualizarTabela();

        } catch (erro) {
            console.error(erro);
            alert("Erro na comunicação com o servidor.");
        }
    });

    // Consulta a página e substitui somente a tabela
    async function atualizarTabela() {

        const resposta = await fetch(window.location.pathname, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!resposta.ok) {
            throw new Error("Erro ao atualizar tabela.");
        }

        const html = await resposta.text();

        const documento = new DOMParser().parseFromString(
            html,
            "text/html"
        );

        const novaTabela = documento.querySelector("table tbody");

        if (novaTabela && tabela) {
            tabela.innerHTML = novaTabela.innerHTML;
        }
    }

});
