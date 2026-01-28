<?php include 'includes/header.php'; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">BloodSync Blog</h1>
        <p class="text-gray-600 mb-10">Stay informed about blood donation, health tips, and inspiring stories.</p>

        <div class="grid md:grid-cols-3 gap-8">
            <?php
            $blog_posts = [
                [
                    'title' => 'The Lifesaving Power of Regular Donors',
                    'excerpt' => 'Learn how consistent blood donors help maintain stable blood supplies and save lives throughout the year.',
                    'date' => 'March 15, 2024',
                    'category' => 'Donor Stories',
                    'read_time' => '4 min read',
                    'image' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?w=800&auto=format&fit=crop'
                ],
                [
                    'title' => 'Understanding Blood Components and Their Uses',
                    'excerpt' => 'Different blood components help different patients. Discover how your donation gets separated and utilized.',
                    'date' => 'March 10, 2024',
                    'category' => 'Education',
                    'read_time' => '6 min read',
                    'image' => 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w-800&auto=format&fit=crop'
                ],
                [
                    'title' => 'Summer Blood Shortages: Why They Happen',
                    'excerpt' => 'Blood donations typically drop during summer months. Here\'s why and how you can help prevent shortages.',
                    'date' => 'March 5, 2024',
                    'category' => 'Awareness',
                    'read_time' => '5 min read',
                    'image' => 'https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=800&auto=format&fit=crop'
                ],
                [
                    'title' => 'Mobile Blood Drives: Bringing Donation to You',
                    'excerpt' => 'How mobile blood donation units are making it easier for communities to donate blood.',
                    'date' => 'February 28, 2024',
                    'category' => 'Community',
                    'read_time' => '3 min read',
                    'image' => 'https://images.unsplash.com/photo-1584036561566-baf8f5f1b144?w=800&auto=format&fit=crop'
                ],
                [
                    'title' => 'Iron and Blood Donation: What You Need to Know',
                    'excerpt' => 'Maintaining healthy iron levels is crucial for regular donors. Tips for iron-rich diets and supplements.',
                    'date' => 'February 22, 2024',
                    'category' => 'Health',
                    'read_time' => '7 min read',
                    'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=800&auto=format&fit=crop'
                ],
                [
                    'title' => 'How Technology is Revolutionizing Blood Banking',
                    'excerpt' => 'From AI matching to blockchain tracking, discover how tech is improving blood supply chains.',
                    'date' => 'February 18, 2024',
                    'category' => 'Technology',
                    'read_time' => '8 min read',
                    'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop'
                ],
            ];
            
            foreach ($blog_posts as $post): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <img src="<?php echo $post['image']; ?>" alt="<?php echo $post['title']; ?>" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">
                            <?php echo $post['category']; ?>
                        </span>
                        <span class="text-gray-500 text-sm"><?php echo $post['read_time']; ?></span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3"><?php echo $post['title']; ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo $post['excerpt']; ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 text-sm"><?php echo $post['date']; ?></span>
                        <a href="#" class="text-red-600 hover:text-red-800 font-medium text-sm">
                            Read More →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>