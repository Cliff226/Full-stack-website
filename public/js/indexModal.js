document.addEventListener("DOMContentLoaded", function () {
    if (TEAM_NOT_FOUND === true) {
        setTimeout(() => {
        let indexModal = new bootstrap.Modal(document.getElementById('searchModal'));
        indexModal.show();
        }, 0);
    }
    if (NOT_LOGGED_IN === true) {
        let indexModal = new bootstrap.Modal(document.getElementById('loginModal'));
        indexModal.show();
    }
});