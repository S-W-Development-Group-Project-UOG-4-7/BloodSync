<?php
require_once '../config/database.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';
?>

<!-- HERO SECTION -->
<section class="bg-red-50 px-6 md:px-20 py-20 flex flex-col md:flex-row items-center justify-between">
    <div class="md:w-1/2">
        <h1 class="text-5xl font-bold leading-tight">
            Connecting Donors, <span class="text-red-600">Saving Lives</span>
        </h1>
        <p class="mt-6 text-lg text-gray-700">
            Join our community of life-savers. BloodSync connects blood donors with
            hospitals and patients in need—making donation fast, simple, and lifesaving.
        </p>

        <div class="mt-8 flex space-x-4">
            <a href="register.php"
               class="bg-red-600 text-white px-6 py-3 rounded-lg shadow hover:bg-red-700">
               Become a Donor
            </a>
            <a href="#"
               class="border border-red-600 text-red-600 px-6 py-3 rounded-lg hover:bg-red-100">
               Find Blood
            </a>
        </div>

        <!-- STATS -->
        <div class="mt-10 flex space-x-10">
            <div>
                <p class="text-3xl font-bold text-red-600">10,000+</p>
                <p class="text-gray-600">Active Donors</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-red-600">5,000+</p>
                <p class="text-gray-600">Lives Saved</p>
            </div>
            <div>
                <p class="text-3xl font-bold text-red-600">50+</p>
                <p class="text-gray-600">Cities</p>
            </div>
        </div>
    </div>

    <div class="md:w-1/2 mt-10 md:mt-0 flex justify-center">
        <img src="../assets/img/home.png" class="w-[370px] opacity-80" alt="Blood Donation Network">
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="px-6 md:px-20 py-16">
    <h2 class="text-4xl font-bold text-center">How BloodSync Works</h2>
    <p class="text-center mt-2 text-gray-600">
        Simple, fast, and efficient blood donor coordination
    </p>

    <div class="grid md:grid-cols-4 gap-10 mt-12 text-center">
        <!-- Register -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <i class="ri-user-add-line text-4xl text-red-600"></i>
            </div>
            <h3 class="mt-4 font-bold text-xl">Register</h3>
            <p class="text-gray-600 mt-2">
                Create your profile with blood type and contact information
            </p>
        </div>

        <!-- Verify -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <i class="ri-file-check-line text-4xl text-red-600"></i>
            </div>
            <h3 class="mt-4 font-bold text-xl">Verify</h3>
            <p class="text-gray-600 mt-2">
                Upload medical report for verification by our admin team
            </p>
        </div>

        <!-- Donate -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <i class="ri-heart-pulse-line text-4xl text-red-600"></i>
            </div>
            <h3 class="mt-4 font-bold text-xl">Donate</h3>
            <p class="text-gray-600 mt-2">
                Book appointments and donate blood to save lives
            </p>
        </div>

        <!-- Save Lives -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <i class="ri-heart-3-line text-4xl text-red-600"></i>
            </div>
            <h3 class="mt-4 font-bold text-xl">Save Lives</h3>
            <p class="text-gray-600 mt-2">
                Your donation helps patients in hospitals across the country
            </p>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="bg-red-600 text-white py-16 text-center">
    <h2 class="text-4xl font-bold">Ready to Make a Difference?</h2>
    <p class="mt-2">Join thousands of donors saving lives every day.</p>

    <a href="register.php"
       class="mt-6 inline-block bg-white text-red-600 px-8 py-3 rounded-lg shadow hover:bg-gray-100">
        Get Started Today
    </a>
</section>

<?php require_once 'includes/footer.php'; ?>