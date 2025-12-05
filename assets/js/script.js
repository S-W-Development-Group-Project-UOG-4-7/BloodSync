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

    if (passwordField && togglePassword) {
        togglePassword.addEventListener("click", function () {
            const hidden = passwordField.type === "password";
            passwordField.type = hidden ? "text" : "password";

            this.classList.toggle("ri-eye-line", !hidden);
            this.classList.toggle("ri-eye-off-line", hidden);
            this.classList.toggle("text-red-600", hidden);
            this.classList.toggle("text-gray-500", !hidden);
        });
    }

    if (emailField && emailError) {
        emailField.addEventListener("input", () => {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value);
            emailError.classList.toggle("hidden", valid);
        });
    }

    // ================================
    // REGISTER PAGE FEATURES
    // ================================
    const regEmail = document.getElementById("regEmail");
    const regEmailError = document.getElementById("regEmailError");

    const contact = document.getElementById("contact");
    const contactError = document.getElementById("contactError");

    const regPassword = document.getElementById("regPassword");
    const toggleRegPassword = document.getElementById("toggleRegPassword");

    const confirmPassword = document.getElementById("confirmPassword");
    const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");

    const passwordMismatch = document.getElementById("passwordMismatch");

    const strengthBar = document.getElementById("strengthBar");
    const strengthText = document.getElementById("strengthText");

    // Email validation
    if (regEmail && regEmailError) {
        regEmail.addEventListener("input", () => {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(regEmail.value);
            regEmailError.classList.toggle("hidden", valid);
        });
    }

    // Contact validation
    if (contact && contactError) {
        contact.addEventListener("input", () => {
            const valid = /^[0-9+\-()\s]{7,20}$/.test(contact.value);
            contactError.classList.toggle("hidden", valid);
        });
    }

    // Password toggle (register)
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

    // Confirm password toggle
    if (confirmPassword && toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener("click", function () {
            const hidden = confirmPassword.type === "password";
            confirmPassword.type = hidden ? "text" : "password";

            this.classList.toggle("ri-eye-line", !hidden);
            this.classList.toggle("ri-eye-off-line", hidden);
            this.classList.toggle("text-red-600", hidden);
            this.classList.toggle("text-gray-500", !hidden);
        });
    }

    // ================================
    // PASSWORD STRENGTH METER
    // ================================
    function evaluateStrength(password) {
        let score = 0;

        if (password.length >= 6) score++;       // length
        if (password.length >= 10) score++;      // longer
        if (/[A-Z]/.test(password)) score++;     // uppercase
        if (/[0-9]/.test(password)) score++;     // digit
        if (/[^A-Za-z0-9]/.test(password)) score++; // special char

        return score;
    }

    if (regPassword && strengthBar && strengthText) {
        regPassword.addEventListener("input", () => {
            const pass = regPassword.value.trim();
            const score = evaluateStrength(pass);

            let width = "0%";
            let color = "#dc2626"; // default red
            let label = "";

            if (pass.length === 0) {
                width = "0%";
                label = "";
            } else if (score <= 1) {
                width = "33%";
                color = "#dc2626"; // weak - red
                label = "Weak";
            } else if (score <= 3) {
                width = "66%";
                color = "#f59e0b"; // medium - amber
                label = "Medium";
            } else {
                width = "100%";
                color = "#16a34a"; // strong - green
                label = "Strong";
            }

            strengthBar.style.width = width;
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = label;
        });
    }

    // ================================
    // PASSWORD MATCH CHECK
    // ================================
    if (regPassword && confirmPassword && passwordMismatch) {
        function validateMatch() {
            const match = regPassword.value === confirmPassword.value;
            passwordMismatch.classList.toggle("hidden", match || !confirmPassword.value);
        }

        regPassword.addEventListener("input", validateMatch);
        confirmPassword.addEventListener("input", validateMatch);
    }
});