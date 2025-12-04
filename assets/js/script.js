document.addEventListener("DOMContentLoaded", () => {

    console.log("BloodSync script loaded.");

    // === COUNT UP ANIMATION (existing) ===
    const counters = document.querySelectorAll('.countup');
    const speed = 80;

    if (counters.length > 0) {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const increment = Math.ceil(target / speed);

                if (count < target) {
                    counter.innerText = count + increment;
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            updateCount();
        });
    }

    // ================================
    // LOGIN PAGE FEATURES
    // ================================

    const passwordField = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const emailField = document.getElementById("email");
    const emailError = document.getElementById("emailError");

    if (passwordField || emailField) {
        console.log("Login page JS active.");
    }

    // === Show / Hide Password ===
    if (passwordField && togglePassword) {
        togglePassword.addEventListener("click", function () {
            const isHidden = passwordField.type === "password";

            // Toggle password visibility
            passwordField.type = isHidden ? "text" : "password";

            if (isHidden) {
                // SHOW password
                this.classList.remove("ri-eye-line");
                this.classList.add("ri-eye-off-line");
                this.classList.add("text-red-600");
                this.classList.remove("text-gray-500");
            } else {
                // HIDE password
                this.classList.remove("ri-eye-off-line");
                this.classList.add("ri-eye-line");
                this.classList.remove("text-red-600");
                this.classList.add("text-gray-500");
            }
        });
    }

    // === Email Validation ===
    if (emailField && emailError) {
        emailField.addEventListener("input", () => {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value);
            emailError.classList.toggle("hidden", valid);
        });
    }

    // --------------------------
    // REGISTER PAGE LOGIC
    // --------------------------

    const regEmail = document.getElementById("regEmail");
    const regEmailError = document.getElementById("regEmailError");
    const contact = document.getElementById("contact");
    const contactError = document.getElementById("contactError");

    const regPassword = document.getElementById("regPassword");
    const toggleRegPassword = document.getElementById("toggleRegPassword");

    const confirmPassword = document.getElementById("confirmPassword");
    const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");

    const passwordMismatch = document.getElementById("passwordMismatch");

    // Email Validation
    if (regEmail && regEmailError) {
        regEmail.addEventListener("input", () => {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(regEmail.value);
            regEmailError.classList.toggle("hidden", valid);
        });
    }

    // Contact number validation (simple)
    if (contact && contactError) {
        contact.addEventListener("input", () => {
            const valid = /^[0-9+\-()\s]{7,20}$/.test(contact.value);
            contactError.classList.toggle("hidden", valid);
        });
    }

    // Password toggle (main password)
    if (regPassword && toggleRegPassword) {
        toggleRegPassword.addEventListener("click", function () {
            const hidden = regPassword.type === "password";
            regPassword.type = hidden ? "text" : "password";

            this.classList.toggle("ri-eye-line", !hidden);
            this.classList.toggle("ri-eye-off-line", hidden);
            this.classList.toggle("text-red-600", hidden);
            this.classList.toggle("text-gray-500", !hidden);
        });
    }

    // Password toggle (confirm password)
    if (confirmPassword && toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener("click", function () {
            const hidden = confirmPassword.type === "password";
            confirmPassword.type = hidden ? "text" : "password";

            this.classList.toggle("ri-eye-line", !hidden);
            this.classList.toggle("ri-eye-off-line", hidden);
            this.classList.toggle("text-red-600", hidden);
            this.classList.toggleDo;
        });
    }

    // Real-time password match check
    if (regPassword && confirmPassword && passwordMismatch) {
        function validateMatch() {
            const match = regPassword.value === confirmPassword.value;
            passwordMismatch.classList.toggle("hidden", match);
        }

        regPassword.addEventListener("input", validateMatch);
        confirmPassword.addEventListener("input", validateMatch);
    }
});