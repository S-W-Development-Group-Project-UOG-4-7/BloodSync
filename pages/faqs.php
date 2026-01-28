<?php include 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Frequently Asked Questions</h1>
        <p class="text-gray-600 mb-10">Find answers to common questions about blood donation and BloodSync.</p>

        <div class="space-y-6">
            <?php
            $faqs = [
                [
                    'question' => 'Who can donate blood?',
                    'answer' => 'Most people in good health can donate blood. Basic requirements include being at least 17 years old (16 with parental consent in some areas), weighing at least 110 pounds, and being in generally good health. You\'ll need to pass a health screening on the day of donation.'
                ],
                [
                    'question' => 'How often can I donate blood?',
                    'answer' => 'You can donate whole blood every 56 days (8 weeks). Platelet donations can be made more frequently - up to 24 times per year. Your body replenishes the donated blood components within a few weeks.'
                ],
                [
                    'question' => 'Is it safe to donate blood?',
                    'answer' => 'Yes, blood donation is very safe. New, sterile equipment is used for each donor, so there\'s no risk of contracting any infectious disease. The process is supervised by trained medical professionals.'
                ],
                [
                    'question' => 'How long does the donation process take?',
                    'answer' => 'The entire process takes about 45-60 minutes. This includes registration, health screening, donation (8-10 minutes), and post-donation rest with refreshments.'
                ],
                [
                    'question' => 'Will I feel weak after donating blood?',
                    'answer' => 'Most people feel fine after donating blood. Some may feel slightly lightheaded, but this usually passes quickly. Drinking plenty of fluids and eating a healthy meal after donation helps. Your body replaces the donated blood volume within 24-48 hours.'
                ],
                [
                    'question' => 'Can I donate if I have a tattoo or piercing?',
                    'answer' => 'Yes, but there\'s usually a waiting period. If the tattoo or piercing was done at a licensed facility in a state that regulates them, you may donate immediately. Otherwise, there\'s typically a 3-month waiting period.'
                ],
                [
                    'question' => 'What blood type is most needed?',
                    'answer' => 'All blood types are needed, but O-negative blood is especially valuable as it\'s the universal donor type that can be given to patients of any blood type in emergency situations.'
                ],
                [
                    'question' => 'How does BloodSync protect my privacy?',
                    'answer' => 'BloodSync uses industry-standard encryption and security measures to protect your personal information. Contact between donors and recipients is facilitated through our secure messaging system, and personal contact details are only shared with mutual consent.'
                ],
            ];
            
            foreach ($faqs as $index => $faq): ?>
            <div class="bg-white rounded-xl shadow-lg">
                <button class="w-full text-left p-6 focus:outline-none" onclick="toggleFAQ(<?php echo $index; ?>)">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $faq['question']; ?></h3>
                        <svg id="icon-<?php echo $index; ?>" class="w-6 h-6 text-red-600 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div id="answer-<?php echo $index; ?>" class="mt-3 text-gray-600 hidden">
                        <p><?php echo $faq['answer']; ?></p>
                    </div>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleFAQ(index) {
    const answer = document.getElementById(`answer-${index}`);
    const icon = document.getElementById(`icon-${index}`);
    
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        answer.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<?php include 'includes/footer.php'; ?>