document.addEventListener("DOMContentLoaded", function () {
    if (TEAM_NOT_FOUND === true) {
        let searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
        searchModal.show();
    }
});