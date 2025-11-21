document.addEventListener('DOMContentLoaded', () => {

    const registerButton = document.getElementById('toggleAllPasswords');
    const registerPasswords = document.querySelectorAll('.register-password');

    if (registerButton && registerPasswords.length > 0) {
        registerButton.addEventListener('click', () => {
            const show = registerPasswords[0].type === 'password';

            registerPasswords.forEach(field => {
                field.type = show ? 'text' : 'password';
            });

            registerButton.textContent = show ? 'Hide Passwords' : 'Show Passwords';
        });
    }
});
