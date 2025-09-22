document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => hideToast(toast));
    }, 5000);

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        });
    });
});

function hideToast(toast) {
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 5000);
}

function showToast(type, message, duration = 5000) {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => hideToast(toast), duration);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
    return container;
}

function showLoader(button = null) {
    isLoading = true;
    if (button) {
        button.classList.add('btn-loading');
        button.disabled = true;
    } else {
        const overlay = document.createElement('div');
        overlay.className = 'overlay-loader';
        overlay.innerHTML = '<div class="loader"></div>';
        overlay.id = 'globalLoader';
        document.body.appendChild(overlay);
    }
}

function hideLoader(button = null) {
    isLoading = false;
    if (button) {
        button.classList.remove('btn-loading');
        button.disabled = false;
    } else {
        const overlay = document.getElementById('globalLoader');
        if (overlay) overlay.remove();
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';
}