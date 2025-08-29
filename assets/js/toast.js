let toastTimeout;

function showToast(text, type = "erro") {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = text;
    toast.className = `toast show ${type}`;

    // Limpa timeout anterior para evitar sobreposição
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.className = 'toast';
    }, 2500);
}