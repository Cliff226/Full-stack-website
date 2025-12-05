// Wait until page is loaded
document.addEventListener("DOMContentLoaded", function () {
      // If the variable TEAM_NOT_FOUND is true show the 'searchModal'
    if (TEAM_NOT_FOUND === true) {
        setTimeout(() => {
        // Get the modal element by ID and create a modal
        let indexModal = new bootstrap.Modal(document.getElementById('searchModal'));
        // Show the modal
        indexModal.show();
        }, 0);
    }
    // If the variable NOT_LOGGED_IN is true show 'loginModal'
    if (NOT_LOGGED_IN === true) {
        let indexModal = new bootstrap.Modal(document.getElementById('loginModal'));
        // Show the modal
        indexModal.show();
    }
});