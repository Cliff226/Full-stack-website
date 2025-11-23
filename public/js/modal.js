document.addEventListener('DOMContentLoaded', () => {

    // Make sure the variable exists
    if (typeof register === 'undefined' || !register) return;

    if (register === 'success') {
        const modalEl = document.getElementById('successModal');
        if (modalEl) { // Only show if the modal is actually on the page
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
        // setTimeout ensures the modal opens AFTER the page is rendered
        // To avoid the "aria-hidden" accessibility warning.

        setTimeout(() => {
            window.location.href = '/dashboard';
        }, 3000);

    } else if (register === 'error') {

        const modalEl = document.getElementById('errorModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
});
