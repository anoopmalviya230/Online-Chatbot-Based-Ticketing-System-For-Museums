<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exhibitions - The National Museum of India</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .hero-pattern {
            background-color: #312e81;
            background-image: url('images/hero_bg.jpg');
            background-blend-mode: overlay;
            background-size: cover;
            background-position: center;
        }

        .card-zoom:hover img {
            transform: scale(1.05);
        }
    </style>
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">

    <!-- Navbar (Matches Homepage) -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <svg xmlns="" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18" />
                        <path d="M5 21V7l8-4 8 4v14" />
                        <path d="M12 10v4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800 leading-tight">The National Museum</h1>
                    <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wider">of India</p>
                </div>
            </div>
            <nav class="hidden md:flex gap-8 items-center">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'userpage.php' : 'homepage.html'; ?>"
                    class="text-gray-600 hover:text-indigo-600 font-medium transition">Home</a>
                <a href="exhibitions.php" class="text-indigo-600 font-bold transition">Exhibitions</a>
                <a href="backend/history.php"
                    class="text-gray-600 hover:text-indigo-600 font-medium transition">Reports</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login_page.html"
                        class="text-gray-600 hover:text-indigo-600 font-medium transition">Login/Signup</a>
                <?php endif; ?>
                <a href="contact.html" class="text-gray-600 hover:text-indigo-600 font-medium transition">Contact Us</a>
            </nav>
            <div class="flex items-center gap-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="backend/admin/login.php"
                        class="hidden md:block text-red-500 font-semibold text-sm hover:text-red-700">Admin Login</a>
                <?php endif; ?>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'backend/index.php' : 'login_page.html'; ?>"
                    class="bg-indigo-600 text-white px-5 py-2.5 rounded-full font-medium hover:bg-indigo-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">Book
                    Tickets</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <div class="hero-pattern text-white py-24 px-6 text-center">
        <h1 class="text-5xl font-extrabold mb-4 tracking-tight">Our Collections</h1>
        <p class="text-xl text-indigo-100 max-w-2xl mx-auto">Immerse yourself in centuries of history, art, and culture
            through our curated exhibitions.</p>
    </div>

    <!-- Gallery Grid -->
    <main class="container mx-auto px-6 py-16 flex-grow">
        <h2 class="text-3xl font-bold text-gray-800 mb-10 border-l-4 border-indigo-600 pl-4">Current Exhibitions</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <!-- Card 1 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/ancient_india.jpg" alt="Ancient India"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <span
                        class="absolute bottom-4 left-4 bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Permanent</span>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Ancient
                        India</h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">Explore the dawn of civilization with artifacts from
                        Mohenjo-Daro, Harappa, and the Vedic age. Witness the mastery of early craftsmanship.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

            <!-- Card 2 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/mughal_miniatures.jpg" alt="Mughal Art"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <span
                        class="absolute bottom-4 left-4 bg-purple-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Special</span>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Mughal
                        Miniatures</h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">A breathtaking collection of miniature paintings from the
                        Mughal courts, showcasing intricate details, vibrant colors, and royal storytelling.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

            <!-- Card 3 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/sculptures.jpg" alt="Sculptures"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Bronze
                        Sculptures</h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">Marvel at the Chola bronzes, including the world-famous
                        Nataraja. These masterpieces define the pinnacle of Indian metallurgical art.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

            <!-- Card 4 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/arms_armour.jpg" alt="Arms and Armour"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Arms &
                        Armour</h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">A fascinating display of swords, shields, daggers, and
                        body armor used by Indian warriors throughout history.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

            <!-- Card 5 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/textiles.jpg" alt="Textiles"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Royal
                        Textiles</h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">From Kashmiri shawls to Banarasi brocades, witness the
                        exquisite fabrics that draped the royalty of India.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

            <!-- Card 6 -->
            <div
                class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 card-zoom">
                <div class="h-64 overflow-hidden relative">
                    <img src="images/numismatics.jpg" alt="Numismatics"
                        class="w-full h-full object-cover transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition">Numismatics
                    </h3>
                    <p class="text-gray-600 mb-4 line-clamp-3">A journey through time via coins. See the evolution of
                        currency from punch-marked coins to the British Raj era.</p>
                    <a href="#"
                        class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-800">Learn More
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg></a>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-indigo-900 text-white py-8">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; 2025 National Museum of India | Designed for Museum Ticket Booking Project</p>
        </div>
    </footer>

</body>

</html>