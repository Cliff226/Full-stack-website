// Wait for html to load
document.addEventListener('DOMContentLoaded', () => {

    // Make sure the variable exists
    if (typeof window.status === 'undefined' || !window.status) return;
    // If the status is success show the success modal
    if (window.status === 'success') {
        // Get the success modal element
        const modalEl = document.getElementById('successModal');
        if (modalEl) {
            // Initialise the modal
            const modal = new bootstrap.Modal(modalEl);
             // Show the modal
            modal.show();
        }
         // After 3 seconds, redirect to 'index.php'
        setTimeout(() => {
            window.location.href = 'index.php';
         }, 3000);
    // If the status is error show error modal
    } else if (window.status === 'error') {
        const modalEl = document.getElementById('errorModal');
        // Initialise the modal
        if (modalEl) {
            // Show the modal
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
});