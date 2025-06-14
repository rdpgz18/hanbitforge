//js test


// src/js/main.js/hsmburgerMenu

document.addEventListener('DOMContentLoaded', () => {
    const openMenuBtn = document.getElementById('open-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    const body = document.body;

    // Fungsi untuk membuka menu mobile
    const openMobileMenu = () => {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        mobileMenuOverlay.classList.add('active');
        body.classList.add('overflow-hidden'); // Mencegah scroll body saat menu terbuka
    };

    // Fungsi untuk menutup menu mobile
    const closeMobileMenu = () => {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        mobileMenuOverlay.classList.remove('active');
        body.classList.remove('overflow-hidden'); // Mengembalikan scroll body
    };

    // Event Listener untuk tombol buka menu
    if (openMenuBtn) {
        openMenuBtn.addEventListener('click', openMobileMenu);
    }

    // Event Listener untuk tombol tutup menu di dalam sidebar
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', closeMobileMenu);
    }

    // Event Listener untuk klik pada overlay (menutup menu)
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    }

    // Menutup menu jika ukuran layar berubah ke desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) { // 768px adalah breakpoint 'md' di Tailwind
            closeMobileMenu();
        }
    });

    // Opsional: Menutup menu ketika salah satu link navigasi diklik
    const navLinks = document.querySelectorAll('#sidebar nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Hanya tutup jika sedang di mode mobile
            if (window.innerWidth < 768) {
                closeMobileMenu();
            }
        });
    });


    
});


 // Fungsi untuk mengambil rekomendasi dari API
        async function fetchRecommendations() {
            const lunchDiv = document.getElementById('lunch-recommendation');
            const exerciseDiv = document.getElementById('exercise-recommendation');
            const userMoodInput = document.getElementById('user-mood');
            const userMood = userMoodInput.value.trim().toLowerCase(); // Ambil input mood pengguna

            // Tampilkan status loading
            lunchDiv.innerHTML = `<h4 class="text-xl font-semibold text-purple-700 mb-3">Memuat Rekomendasi Menu Makan Siang...</h4><p class="text-gray-600 text-sm">Harap tunggu sebentar.</p>`;
            exerciseDiv.innerHTML = `<h4 class="text-xl font-semibold text-green-700 mb-3">Memuat Rekomendasi Latihan Fisik...</h4><p class="text-gray-600 text-sm">Harap tunggu sebentar.</p>`;

            try {
                const response = await fetch('http://localhost:3000/api/get-recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ mood: userMood }) // Kirim data mood ke backend
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Rekomendasi dari API:', data);

                // Perbarui div rekomendasi makan siang
                lunchDiv.innerHTML = `
                    <h4 class="text-xl font-semibold text-purple-700 mb-3">${data.lunch.title}</h4>
                    <p class="text-lg font-medium text-gray-800 mb-2">${data.lunch.title}</p>
                    <p class="text-gray-600 text-sm">${data.lunch.description}</p>
                    <button class="mt-4 bg-purple-500 text-white py-2 px-4 rounded-lg text-sm hover:bg-purple-600 transition-colors duration-200">
                        ${data.lunch.button_text}
                    </button>
                `;

                // Perbarui div rekomendasi latihan fisik
                exerciseDiv.innerHTML = `
                    <h4 class="text-xl font-semibold text-green-700 mb-3">${data.exercise.title}</h4>
                    <p class="text-lg font-medium text-gray-800 mb-2">${data.exercise.title}</p>
                    <p class="text-gray-600 text-sm">${data.exercise.description}</p>
                    <button class="mt-4 bg-green-500 text-white py-2 px-4 rounded-lg text-sm hover:bg-green-600 transition-colors duration-200">
                        ${data.exercise.button_text}
                    </button>
                `;

            } catch (error) {
                console.error('Ada masalah saat mengambil rekomendasi:', error);
                lunchDiv.innerHTML = `<h4 class="text-xl font-semibold text-red-700 mb-3">Gagal Memuat Rekomendasi</h4><p class="text-gray-600 text-sm">Terjadi kesalahan. Coba lagi nanti.</p>`;
                exerciseDiv.innerHTML = `<h4 class="text-xl font-semibold text-red-700 mb-3">Gagal Memuat Rekomendasi</h4><p class="text-gray-600 text-sm">Terjadi kesalahan. Coba lagi nanti.</p>`;
            }
        }

        // Panggil fungsi saat halaman dimuat pertama kali
        document.addEventListener('DOMContentLoaded', fetchRecommendations);

        // Tambahkan event listener untuk tombol refresh
        document.getElementById('refresh-button').addEventListener('click', fetchRecommendations);

