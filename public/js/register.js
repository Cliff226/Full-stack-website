// Wait until page is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Get the button that will toggle password visibility
    const registerButton = document.getElementById('toggleAllPasswords');
    // Get all password input fields with the class 'register-password'
    const registerPasswords = document.querySelectorAll('.register-password');
    // Only proceed if the button exists and there is one password field
    if (registerButton && registerPasswords.length > 0) {
        // Add a click event listener to the button
        registerButton.addEventListener('click', () => {
             // If it is password' show it otherwise hide
            const show = registerPasswords[0].type === 'password';
            // Loop through all password fields and change their type
            registerPasswords.forEach(field => {
                field.type = show ? 'text' : 'password';
            });
            // Change the button text based on whether passwords are visible
            registerButton.textContent = show ? 'Hide Passwords' : 'Show Passwords';
        });
    }
});
