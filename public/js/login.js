// Wait for the page to load
document.addEventListener('DOMContentLoaded', () => {

// Get the button and input field
const loginButton = document.getElementById('loginShowPasswordButton');
    const loginInput = document.getElementById('inputPassword');

    // Check if elements exist
    if (loginButton && loginInput) {
        // Add click event listener
        loginButton.addEventListener('click', () => {
            // Toggle input type
            const show = loginInput.type === 'password';
            // Update button text
            loginInput.type = show ? 'text' : 'password';
            loginButton.textContent = show ? 'Hide Password' : 'Show Password';
        });
    }

});


