<?php include('partials/header.php'); ?>

<!-- HERO SECTION -->
<section class="bg-red-50 px-6 md:px-20 py-20 flex flex-col md:flex-row items-center justify-between">

    <div class="md:w-1/2 fade-in-slow">
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
            <a href="search.php"
               class="border border-red-600 text-red-600 px-6 py-3 rounded-lg hover:bg-red-100">
               Find Blood
            </a>
        </div>

        <!-- STATS -->
        <div class="mt-10 flex space-x-10">
            <div>
                <p class="countup text-3xl font-bold text-red-600" data-target="10000">0</p>
                <p class="text-gray-600">Active Donors</p>
            </div>
            <div>
                <p class="countup text-3xl font-bold text-red-600" data-target="5000">0</p>
                <p class="text-gray-600">Lives Saved</p>
            </div>
            <div>
                <p class="countup text-3xl font-bold text-red-600" data-target="50">0</p>
                <p class="text-gray-600">Cities</p>
            </div>
        </div>
    </div>

    <div class="md:w-1/2 mt-10 md:mt-0 flex justify-center fade-in-slow">
        <img src="../assets/img/home.png" class="w-[370px] opacity-80" alt="Blood Donation Network">
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="px-6 md:px-20 py-16 fade-in-slow">
    <h2 class="text-4xl font-bold text-center">How BloodSync Works</h2>
    <p class="text-center mt-2 text-gray-600">
        Simple, fast, and efficient blood donor coordination
    </p>

    <div class="grid md:grid-cols-4 gap-10 mt-12 text-center">

        <!-- Register -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <svg xmlns="http://www.w3.org/2000/svg" 
                    width="44" height="44" 
                    fill="none" 
                    stroke="#E43D53" 
                    stroke-width="1.5" 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3"/>
                    <path d="M18 20c0-3.3-2.7-6-6-6s-6 2.7-6 6"/>
                </svg>
            </div>
            <h3 class="mt-4 font-bold text-xl">Register</h3>
            <p class="text-gray-600 mt-2">
                Create your profile with blood type and contact information
            </p>
        </div>

        <!-- Find Matches -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <svg xmlns="http://www.w3.org/2000/svg" 
                    width="44" height="44" 
                    fill="none" 
                    stroke="#E43D53" 
                    stroke-width="1.5" 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    viewBox="0 0 24 24">
                    <path d="M12 21s6-5.1 6-10A6 6 0 0 0 6 11c0 4.9 6 10 6 10z"/>
                    <circle cx="12" cy="11" r="2.5"/>
                </svg>
            </div>
            <h3 class="mt-4 font-bold text-xl">Find Matches</h3>
            <p class="text-gray-600 mt-2">
                Search for compatible donors or recipients in your area
            </p>
        </div>

        <!-- Get Notified -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <svg xmlns="http://www.w3.org/2000/svg"
                    width="44" height="44"
                    fill="none"
                    stroke="#E43D53"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    viewBox="0 0 24 24">
                    <path d="M18 16v-4a6 6 0 1 0-12 0v4"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    <line x1="6" y1="16" x2="18" y2="16"/>
                </svg>
            </div>
            <h3 class="mt-4 font-bold text-xl">Get Notified</h3>
            <p class="text-gray-600 mt-2">
                Receive alerts when your blood type is urgently needed
            </p>
        </div>

        <!-- Save Lives -->
        <div>
            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full" style="background:#FDECEC;">
                <svg xmlns="http://www.w3.org/2000/svg"
                    width="40" height="40"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#E43D53"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 21
                            L4.5 12.8
                            A4.2 4.2 0 0 1 4.4 6
                            C6 4.4 8.8 4.6 10.5 6.4
                            L12 8.1
                            L13.5 6.4
                            C15.2 4.6 18 4.4 19.6 6
                            A4.2 4.2 0 0 1 19.5 12.8
                            L12 21z"/>
                </svg>
            </div>
            <h3 class="mt-4 font-bold text-xl">Save Lives</h3>
            <p class="text-gray-600 mt-2">
                Make a difference by donating blood and helping others
            </p>
        </div>

    </div>
</section>

<!-- CALL TO ACTION -->
<section class="bg-red-600 text-white py-16 text-center fade-in-slow">
    <h2 class="text-4xl font-bold">Ready to Make a Difference?</h2>
    <p class="mt-2">Join thousands of donors saving lives every day.</p>

    <a href="register.php"
       class="mt-6 inline-block bg-white text-red-600 px-8 py-3 rounded-lg shadow hover:bg-gray-100">
        Get Started Today
    </a>
</section>

<?php include('partials/footer.php'); ?>