document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById('pdf');
    const fileNameDisplay = document.getElementById('file-name');
    const fileLabel = document.getElementById('file-label'); // Label
    const fileLabelText = document.getElementById('file-label-text'); // Texto dentro do label
    const form = input ? input.closest('form') : null;
    const btnSubmit = document.getElementById('btn-submit');
    const btnRemove = document.getElementById('btn-remove'); // Botão X

    // 1. Quando o arquivo mudar (selecionar)
    if (input) {
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                // Muda o nome exibido
                fileNameDisplay.textContent = this.files[0].name;
                // Muda o texto do LABEL
                fileLabelText.textContent = "Arquivo selecionado";
                fileLabel.classList.add('is-selected');
                // Mostra o botão "X"
                btnRemove.style.display = "inline-block";
            }
        });
    }

    // 2. Quando clicar no botão "X" (remover)
    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            // Limpa o input de arquivo (Mágica: para resetar um input file, igualamos o value a vazio)
            input.value = "";

            // Volta os textos ao normal usando os atributos de dados (centralizado no Blade)
            fileNameDisplay.textContent = fileNameDisplay.getAttribute('data-original-text');
            fileLabelText.textContent = fileLabel.getAttribute('data-original-text');
            fileLabel.classList.remove('is-selected');

            // Esconde o botão "X" novamente
            this.style.display = "none";
        });
    }

    // 3. Mostra que o PDF está sendo processado (o bloqueio de envio duplicado está em ui.js)
    if (form && btnSubmit) {
        form.addEventListener('submit', function() {
            btnSubmit.textContent = btnSubmit.dataset.loadingText;
        });
    }
});
