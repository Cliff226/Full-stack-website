document.addEventListener('DOMContentLoaded', () => {

const loginButton = document.getElementById('loginShowPasswordButton');
    const loginInput = document.getElementById('inputPassword');

    if (loginButton && loginInput) {
        loginButton.addEventListener('click', () => {
            const show = loginInput.type === 'password';
            loginInput.type = show ? 'text' : 'password';
            loginButton.textContent = show ? 'Hide Password' : 'Show Password';
        });
    }

});


