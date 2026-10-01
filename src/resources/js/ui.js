// Comportamentos comuns a todas as telas: modal de confirmação, modais de edição e envio único de formulários.

const dialog = document.getElementById('confirm-dialog');

// 1. Confirmação padrão: qualquer <form data-confirm="mensagem"> abre o modal antes de enviar
document.addEventListener('submit', function (e) {
    const form = e.target;

    if (form.dataset.confirm && form.dataset.confirmed !== '1' && dialog) {
        e.preventDefault();

        document.getElementById('confirm-title').textContent = form.dataset.confirmTitle || 'Confirmar ação';
        document.getElementById('confirm-message').textContent = form.dataset.confirm;
        document.getElementById('confirm-ok').textContent = form.dataset.confirmButton || 'Confirmar';

        dialog.returnValue = '';
        dialog.addEventListener('close', function aoFechar() {
            dialog.removeEventListener('close', aoFechar);
            if (dialog.returnValue === 'confirm') {
                form.dataset.confirmed = '1';
                form.requestSubmit();
            }
        });
        dialog.showModal();
        return;
    }

    // 2. Evita envio duplicado: desabilita os botões de envio após o submit
    if (!e.defaultPrevented && form.method.toLowerCase() === 'post') {
        setTimeout(() => form.querySelectorAll('button[type="submit"]').forEach(b => { b.disabled = true; }), 0);
    }
});

// Voltar pelo botão do navegador não deve deixar botões travados
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        document.querySelectorAll('button[type="submit"]').forEach(b => { b.disabled = false; });
    }
});

// Modais de edição: <button data-open-modal="id"> abre, <button data-close-modal> fecha
document.addEventListener('click', function (e) {
    const abrir = e.target.closest('[data-open-modal]');
    if (abrir) {
        document.getElementById(abrir.dataset.openModal)?.showModal();
        return;
    }

    if (e.target.closest('[data-close-modal]')) {
        e.target.closest('dialog')?.close();
        return;
    }

    // Clique no fundo escuro fecha qualquer modal (o alvo é o próprio <dialog>)
    if (e.target instanceof HTMLDialogElement && e.target.open) {
        e.target.close('cancel');
    }
});

// Reabre automaticamente o modal marcado (ex.: após erro de validação)
document.querySelectorAll('dialog[data-auto-open]').forEach(d => d.showModal());
