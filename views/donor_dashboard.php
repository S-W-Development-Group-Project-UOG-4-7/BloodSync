<?php include('partials/header.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="min-h-screen bg-gray-50 flex font-sans">

    <aside class="hidden md:flex flex-col w-72 bg-white border-r border-gray-200 h-screen fixed z-20">
        <div class="p-8 flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-700 rounded-xl flex items-center justify-center text-white font-bold shadow-red-200 shadow-lg">
                <i class="ri-drop-fill text-xl"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800 tracking-tight">BloodSync</span>
        </div>
        
        <nav class="flex-1 px-6 space-y-3 mt-4">
            <a href="donor_dashboard.php" class="flex items-center gap-4 px-4 py-3.5 bg-red-50 text-red-700 rounded-2xl font-semibold transition-all">
                <i class="ri-dashboard-line text-xl"></i> Dashboard
            </a>
            <a href="donor_profile.php" class="flex items-center gap-4 px-4 py-3.5 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-2xl font-medium transition-all">
                <i class="ri-user-smile-line text-xl"></i> My Profile
            </a>
            <a href="#" class="flex items-center gap-4 px-4 py-3.5 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-2xl font-medium transition-all">
                <i class="ri-calendar-event-line text-xl"></i> Appointments
            </a>
            <a href="#" class="flex items-center gap-4 px-4 py-3.5 text-gray-500 hover:bg-gray-50 hover:text-gray-900 rounded-2xl font-medium transition-all">
                <i class="ri-file-list-3-line text-xl"></i> History
            </a>
        </nav>
    </aside>

    <main class="flex-1 md:ml-72 p-6 lg:p-10">

        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-1">Overview of your donation journey.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <button class="relative p-3 bg-white border border-gray-100 rounded-xl hover:shadow-md transition">
                    <i class="ri-notification-3-line text-xl text-gray-600"></i>
                    <span class="absolute top-2 right-3 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <div class="h-12 w-12 rounded-xl bg-gray-200 overflow-hidden border-2 border-white shadow-sm">
                    <img src="https://i.pravatar.cc/150?img=11" alt="User" class="h-full w-full object-cover">
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Last Donation</span>
                        <div class="mt-4 flex items-end gap-2">
                            <h3 class="text-3xl font-bold text-gray-900">Sep 15</h3>
                            <span class="text-sm text-gray-500 mb-1">2025</span>
                        </div>
                        <div class="mt-4 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 w-full rounded-full"></div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col relative overflow-hidden">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Next Eligible</span>
                        <div class="mt-4 flex items-end gap-2 relative z-10">
                            <h3 class="text-3xl font-bold text-green-600">Jan 15</h3>
                            <span class="text-sm text-gray-500 mb-1">2026</span>
                        </div>
                        <i class="ri-calendar-check-fill absolute -bottom-4 -right-4 text-8xl text-green-50 opacity-50 z-0"></i>
                    </div>

                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-6 rounded-3xl shadow-lg shadow-gray-200 text-white flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">My Type</span>
                            <span class="bg-white/20 px-2 py-1 rounded text-xs backdrop-blur-sm">Rare</span>
                        </div>
                        <h3 class="text-4xl font-extrabold mt-2">O+</h3>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Donation Analysis</h3>
                        <select class="bg-gray-50 border-none text-sm font-semibold text-gray-500 rounded-lg p-2 focus:ring-0 cursor-pointer">
                            <option>Last 6 Months</option>
                            <option>Last Year</option>
                        </select>
                    </div>
                    <div class="h-64 w-full">
                        <canvas id="donationChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900">Recent History</h3>
                        <a href="#" class="text-sm text-red-600 font-semibold hover:underline">View All</a>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Date</th>
                                <th class="px-6 py-4 font-semibold">Location</th>
                                <th class="px-6 py-4 font-semibold">Units</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900">2025-09-15</td>
                                <td class="px-6 py-4 text-gray-500">Matale District Hospital</td>
                                <td class="px-6 py-4 text-gray-500">450ml</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Completed</span></td>
                            </tr>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900">2025-05-10</td>
                                <td class="px-6 py-4 text-gray-500">Mobile Camp (Kandy)</td>
                                <td class="px-6 py-4 text-gray-500">450ml</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="lg:col-span-1 space-y-8">
                
                <div class="bg-white p-6 rounded-3xl shadow-xl shadow-red-100/50 border border-red-50">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                            <i class="ri-calendar-add-fill text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Book Appointment</h3>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-4 mb-6">
                        <div class="grid grid-cols-7 gap-2 text-center text-sm mb-2 text-gray-400 font-medium">
                            <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                        </div>
                        <div class="grid grid-cols-7 gap-2 text-center font-semibold text-gray-700">
                            <span class="p-2">1</span><span class="p-2">2</span><span class="p-2 bg-red-600 text-white rounded-lg shadow-md">3</span><span class="p-2">4</span><span class="p-2">5</span><span class="p-2">6</span><span class="p-2 text-gray-300">7</span>
                        </div>
                    </div>

                    <form>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase">Center</label>
                                <select class="w-full mt-1 bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-red-500 focus:border-red-500">
                                    <option>Teaching Hospital, Peradeniya</option>
                                    <option>District Hospital, Matale</option>
                                </select>
                            </div>
                            <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition transform hover:-translate-y-1">
                                Find Slots
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-red-50 p-6 rounded-3xl border border-red-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-red-800 font-bold flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            Urgent Needs
                        </h3>
                        <span class="text-xs font-semibold text-red-600 bg-white px-2 py-1 rounded-md">Nearby</span>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-white p-4 rounded-2xl shadow-sm flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400">Kandy Hospital</span>
                                <p class="font-bold text-gray-800 text-sm mt-0.5">A+ needed urgently</p>
                            </div>
                            <button class="text-xs bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200">
                                View
                            </button>
                        </div>
                        <div class="bg-white p-4 rounded-2xl shadow-sm flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-400">Matale Base</span>
                                <p class="font-bold text-gray-800 text-sm mt-0.5">O- needed (Accident)</p>
                            </div>
                            <button class="text-xs bg-red-100 text-red-700 font-bold px-3 py-2 rounded-lg hover:bg-red-200">
                                View
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
    const ctx = document.getElementById('donationChart').getContext('2d');
    const donationChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Hemoglobin Level (g/dL)',
                data: [13.5, 14.0, 13.8, 14.2, 14.5, 14.1, 13.9, 14.3, 14.0, null, null, null], // Mock Data
                borderColor: '#DC2626', // Tailwind Red-600
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                borderWidth: 3,
                tension: 0.4, // Smooth curves
                pointBackgroundColor: '#FFFFFF',
                pointBorderColor: '#DC2626',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { borderDash: [5, 5], color: '#F3F4F6' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

