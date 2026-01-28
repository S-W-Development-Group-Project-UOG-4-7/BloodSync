<?php include 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Contact Us</h1>
        <p class="text-gray-600 mb-10">Have questions or need assistance? We're here to help.</p>

        <div class="grid md:grid-cols-2 gap-10">
            <!-- Contact Information -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Get in Touch</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 text-red-600 mt-1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-medium text-gray-900">Address</h4>
                                <p class="text-gray-600">123 Medical Plaza, Suite 400<br>New York, NY 10001</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 text-red-600 mt-1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-medium text-gray-900">Email</h4>
                                <p class="text-gray-600">support@bloodsync.org<br>donors@bloodsync.org</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0 text-red-600 mt-1">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-medium text-gray-900">Phone</h4>
                                <p class="text-gray-600">+1 (555) 123-4567<br>Emergency: +1 (555) 987-6543</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Emergency Hotline</h3>
                    <p class="text-gray-600 mb-4">For urgent blood requirements or medical emergencies:</p>
                    <div class="bg-red-50 border-l-4 border-red-600 p-4">
                        <p class="text-red-800 font-bold text-lg">24/7 Emergency: +1 (800) 555-HELP</p>
                        <p class="text-red-600 text-sm">Available round the clock for critical situations</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Send us a Message</h3>
                <form class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <option value="">Select a topic</option>
                            <option value="donation">Blood Donation</option>
                            <option value="request">Blood Request</option>
                            <option value="partnership">Partnership</option>
                            <option value="technical">Technical Support</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg hover:bg-red-700 transition duration-300 font-medium">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>