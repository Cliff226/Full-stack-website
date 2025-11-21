
document.addEventListener('DOMContentLoaded', () => {
    if (!register) return;

    if (register === 'success') {
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();

        setTimeout(() => {
            window.location.href = '/dashboard';
        }, 3000);
    } else if (register === 'error') {
        const modal = new bootstrap.Modal(document.getElementById('errorModal'));
        modal.show();
    }
});
