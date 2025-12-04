<?php include('partials/header.php'); ?>

<style>
@keyframes fadeInSlow {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.fade-in-slow {
    animation: fadeInSlow 0.8s ease-out forwards;
}
</style>

<section class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-6 py-20">

    <div class="bg-white shadow-xl rounded-3xl p-10 w-full max-w-md opacity-0 fade-in-slow">

        <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Create Account</h2>

        <form id="registerForm" method="POST" action="register_handler.php">

            <!-- Full Name -->
            <div class="mb-6">
                <label class="block text-gray-800 font-medium mb-2">Full Name</label>
                <input
                    id="fullname"
                    name="fullname"
                    type="text"
                    placeholder="Your full name"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 
                           focus:ring-2 focus:ring-red-500 focus:outline-none transition"
                    required
                >
            </div>

            <!-- Contact -->
            <div class="mb-6">
                <label class="block text-gray-800 font-medium mb-2">Contact Number</label>
                <input
                    id="contact"
                    name="contact"
                    type="text"
                    placeholder="Mobile or phone number"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 
                           focus:ring-2 focus:ring-red-500 focus:outline-none transition"
                    required
                >
                <small id="contactError" class="text-red-600 text-sm hidden">Invalid contact number.</small>
            </div>

            <!-- Email -->
            <div class="mb-6">
                <label class="block text-gray-800 font-medium mb-2">Email</label>
                <input
                    id="regEmail"
                    name="email"
                    type="email"
                    placeholder="Email"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 
                           focus:ring-2 focus:ring-red-500 focus:outline-none transition"
                    required
                >
                <small id="regEmailError" class="text-red-600 text-sm hidden">Invalid email address.</small>
            </div>

            <!-- Password -->
            <div class="mb-6">
                <label class="block text-gray-800 font-medium mb-2">Password</label>
                <div class="relative">
                    <input
                        id="regPassword"
                        name="password"
                        type="password"
                        placeholder="Password"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 
                               focus:ring-2 focus:ring-red-500 focus:outline-none transition"
                        required
                    >
                    <i id="toggleRegPassword"
                       class="ri-eye-line absolute right-4 top-3.5 text-gray-500 text-xl cursor-pointer transition hover:text-red-600">
                    </i>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label class="block text-gray-800 font-medium mb-2">Confirm Password</label>
                <div class="relative">
                    <input
                        id="confirmPassword"
                        name="confirm_password"
                        type="password"
                        placeholder="Confirm password"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 
                               focus:ring-2 focus:ring-red-500 focus:outline-none transition"
                        required
                    >
                    <i id="toggleConfirmPassword"
                       class="ri-eye-line absolute right-4 top-3.5 text-gray-500 text-xl cursor-pointer transition hover:text-red-600">
                    </i>
                </div>
                <small id="passwordMismatch" class="text-red-600 text-sm hidden">Passwords do not match.</small>
            </div>

            <!-- Create Account Button -->
            <button
                type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold text-lg py-3 rounded-xl 
                       shadow transition-transform transform hover:-translate-y-1"
            >
                Sign Up
            </button>

        </form>

    </div>

</section>

<?php include('partials/footer.php'); ?>