document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('itens-container');
    const btnAddItem = document.getElementById('add-item');
    const template = document.getElementById('item-template');

    if (!container) {
        return;
    }

    const cards = () => container.getElementsByClassName('item-form-card');

    // Renumera o título "Item #n" dos cards conforme a posição atual
    function renumerar() {
        Array.from(cards()).forEach((card, i) => {
            const numero = card.querySelector('.item-numero');
            if (numero) {
                numero.textContent = i + 1;
            }
        });
    }

    // Próximo índice livre para os nomes itens[n][campo] (evita colisão após remoções)
    let proximoIndice = Array.from(cards()).reduce((max, card) => {
        const input = card.querySelector('input[name^="itens["]');
        const match = input ? input.name.match(/^itens\[(\d+)\]/) : null;
        return match ? Math.max(max, Number(match[1]) + 1) : max;
    }, 0);

    // 1. Adicionar item a partir do <template> (funciona mesmo sem itens na tela)
    if (btnAddItem && template) {
        btnAddItem.addEventListener('click', function() {
            const html = template.innerHTML.replaceAll('__INDEX__', proximoIndice++);
            container.insertAdjacentHTML('beforeend', html);
            renumerar();
        });
    }

    // 2. Delegação de eventos para excluir e minimizar
    container.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-item')) {
            e.target.closest('.item-form-card').remove();
            renumerar();
        }

        if (e.target.closest('.btn-toggle-item')) {
            e.target.closest('.item-form-card').classList.toggle('item-collapsed');
        }
    });
});
