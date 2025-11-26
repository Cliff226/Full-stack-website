document.addEventListener('DOMContentLoaded', () => {

    // Make sure the variable exists
    if (typeof window.status === 'undefined' || !window.status) return;

    if (window.status === 'success') {
        const modalEl = document.getElementById('successModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        setTimeout(() => {
            window.location.href = 'index.php';
         }, 3000);

    } else if (window.status === 'error') {
        const modalEl = document.getElementById('errorModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
});