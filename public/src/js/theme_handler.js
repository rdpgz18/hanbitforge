// src/js/theme_handler.js

console.log('Javascript theme_handler ok');

/**
 * Menerapkan tema ke elemen body dan menyimpan preferensi ke localStorage.
 *
 * @param {string} theme 'light', 'dark', or 'system'
 */
function applyTheme(theme) {
  const body = document.body;
  body.classList.remove("light", "dark"); // Hapus semua kelas tema

  if (theme === "system") {
    // Cek preferensi sistem
    if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
      body.classList.add("dark");
    } else {
      body.classList.add("light"); // default ke light jika sistem tidak dark
    }
  } else {
    body.classList.add(theme);
  }
  localStorage.setItem("app_theme", theme); // Simpan preferensi tema di localStorage
}

// Inisialisasi tema saat script dimuat (sebelum DOMContentLoaded jika memungkinkan,
// agar tidak ada flash of unstyled content)
// Prioritas: localStorage -> system -> light
const savedTheme = localStorage.getItem("app_theme");
if (savedTheme) {
  applyTheme(savedTheme);
} else {
  // Jika tidak ada di localStorage, coba preferensi sistem
  if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
    applyTheme("system"); // Terapkan 'system' sebagai default jika gelap
  } else {
    applyTheme("light"); // Default ke 'light'
  }
}

// Event listener untuk perubahan tema sistem
window
  .matchMedia("(prefers-color-scheme: dark)")
  .addEventListener("change", (e) => {
    // Hanya terapkan ulang jika tema yang saat ini dipilih adalah 'system'
    // Atau jika tema saat ini adalah 'system' dari localStorage
    const currentAppliedTheme = localStorage.getItem("app_theme");
    if (currentAppliedTheme === "system") {
      applyTheme("system");
    }
  });

// --- Google Translate Widget Logic ---
// Fungsi ini akan dipanggil oleh script Google Translate
function googleTranslateElementInit() {
  new google.translate.TranslateElement(
    {
      pageLanguage: "id", // Bahasa asli halaman Anda
      includedLanguages: "en,id,es,fr,de", // Bahasa yang ingin Anda sediakan
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
      autoDisplay: false, // Agar tidak muncul secara otomatis
    },
    "google_translate_element"
  );
}

// Tambahkan script Google Translate API ke halaman saat theme_handler.js dimuat
// Perhatikan: ini akan memuat widget di semua halaman yang menyertakan theme_handler.js
// Jika Anda hanya ingin widget di halaman settings.php, Anda bisa memindahkan bagian ini ke settings.php
const googleTranslateScript = document.createElement("script");
googleTranslateScript.type = "text/javascript";
googleTranslateScript.src =
  "//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
document.body.appendChild(googleTranslateScript);

// Pastikan DOM sudah dimuat sebelum mencoba mengakses elemen form
document.addEventListener("DOMContentLoaded", function () {
  const themeSelectElement = document.getElementById("theme");

  if (themeSelectElement) {
    // Set nilai dropdown sesuai dengan tema yang sedang aktif (dari localStorage/sistem)
    const currentTheme =
      localStorage.getItem("app_theme") ||
      (window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "system"
        : "light");
    themeSelectElement.value = currentTheme;

    // Event listener untuk select box tema di halaman settings.php
    themeSelectElement.addEventListener("change", function () {
      const newTheme = this.value;
      applyTheme(newTheme);

      // Tampilkan notifikasi
      // Asumsikan showModalNotification dan showToastNotification tersedia secara global
      if (typeof showModalNotification === "function") {
        showModalNotification(
          "Berhasil!",
          `Tema aplikasi berhasil diubah menjadi ${newTheme}.`,
          "success"
        );
        showToastNotification(
          `Tema berhasil diubah menjadi ${newTheme}.`,
          "success"
        );
      } else {
        console.warn(
          "Notification functions (showModalNotification, showToastNotification) not found."
        );
      }
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const pageLoader = document.getElementById("page-loader");
  const body = document.body;

  // Function to show the page loader
  function showPageLoader() {
    if (pageLoader) {
      pageLoader.classList.remove("hidden"); // Hapus kelas display: none dari HTML jika ada
      void pageLoader.offsetWidth; // Force reflow
      pageLoader.classList.add("show");
      body.classList.add("no-scroll"); // Optional: Prevent scrolling during loading
      console.log("Page loader shown.");
    }
  }

  // Function to hide the page loader
  function hidePageLoader() {
    if (pageLoader) {
      pageLoader.classList.remove("show");
      pageLoader.addEventListener(
        "transitionend",
        function handler() {
          pageLoader.classList.add("hidden"); // Tambahkan kembali hidden setelah transisi selesai
          body.classList.remove("no-scroll"); // Remove no-scroll
          pageLoader.removeEventListener("transitionend", handler);
          console.log("Page loader hidden.");
        },
        { once: true }
      );
    }
  }

  // --- Logic untuk menampilkan loader saat navigasi ---
  // Tambahkan event listener ke semua link yang akan memicu pindah halaman
  document.querySelectorAll("a").forEach((link) => {
    // Abaikan link internal (misal #id) atau link yang membuka tab baru (_blank)
    // dan link yang memiliki atribut data-no-loader
    if (
      link.hostname === window.location.hostname &&
      link.getAttribute("target") !== "_blank" &&
      !link.href.startsWith("#") && // Ignore anchor links
      link.href !== "" && // Ignore empty links
      !link.hasAttribute("data-no-loader")
    ) {
      // Optional: Link yang tidak ingin pakai loader
      link.addEventListener("click", function (event) {
        // Mencegah default navigasi sementara
        event.preventDefault();
        showPageLoader();

        // Tunda sedikit sebelum navigasi, agar animasi loader sempat terlihat
        setTimeout(() => {
          window.location.href = link.href;
        }, 300); // Sesuaikan dengan durasi transisi CSS (0.3s = 300ms)
      });
    }
  });

  // --- Logic untuk menyembunyikan loader saat halaman baru dimuat ---
  // Ini akan berjalan di halaman tujuan setelah dimuat.
  // Pastikan DOMContentLoaded ini adalah yang pertama di dalam file JS Anda,
  // atau loader disembunyikan segera setelah semua konten siap.
  // Jika loader sudah terlihat saat halaman dimuat (misal dari navigasi sebelumnya), sembunyikan.
  if (pageLoader && pageLoader.classList.contains("show")) {
    hidePageLoader();
  } else {
    // Jika halaman dimuat langsung tanpa transisi (misal refresh, atau pertama kali buka)
    // Pastikan loader dalam keadaan tersembunyi
    if (pageLoader) {
      pageLoader.classList.add("hidden"); // Memastikan tersembunyi jika belum
      pageLoader.classList.remove("show");
      body.classList.remove("no-scroll");
    }
  }

  // Untuk memastikan loader tersembunyi setelah hard refresh atau direct access
  window.addEventListener("load", () => {
    hidePageLoader();
  });

  // Ini adalah kode untuk notifikasi cache/cookie Anda, letakkan di dalam DOMContentLoaded yang sama
  // atau pastikan tidak ada konflik jika di file terpisah.
  // (Kode notifikasi cache/cookie Anda dari jawaban sebelumnya akan diletakkan di sini)

  // ... KODE NOTIFIKASI CACHE/COOKIE ANDA DI SINI ...
  const consentNotification = document.getElementById("consent-notification");
  const acceptConsentBtn = document.getElementById("accept-consent-btn");
  const declineConsentBtn = document.getElementById("decline-consent-btn");
  const CONSENT_STATUS_KEY = "user_consent_status";
  const CONSENT_VERSION = "v1.0";

  function showConsentNotification() {
    if (consentNotification) {
      void consentNotification.offsetWidth;
      consentNotification.classList.add("show");
      console.log("Consent notification shown.");
    } else {
      console.error("consentNotification element not found!");
    }
  }

  function hideConsentNotification() {
    if (consentNotification) {
      consentNotification.classList.remove("show");
      consentNotification.addEventListener(
        "transitionend",
        function handler() {
          consentNotification.removeEventListener("transitionend", handler);
        },
        { once: true }
      );
      console.log("Consent notification hidden.");
    }
  }

  function recordConsent(isAccepted) {
    const consentEndpoint = "./actions/record_consent.php"; // Sesuaikan path jika perlu
    fetch(consentEndpoint, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `is_accepted=${
        isAccepted ? "true" : "false"
      }&consent_type=cache_cookie`,
    })
      .then((response) => response.json())
      .then((data) => console.log("Consent recorded:", data.message))
      .catch((error) => console.error("Error recording consent:", error));
  }

  const storedConsentStatus = localStorage.getItem(CONSENT_STATUS_KEY);
  const storedConsentVersion = localStorage.getItem(
    CONSENT_STATUS_KEY + "_version"
  );

  if (!storedConsentStatus || storedConsentVersion !== CONSENT_VERSION) {
    setTimeout(showConsentNotification, 500); // Tunda 0.5 detik
  } else {
    hideConsentNotification();
  }

  if (acceptConsentBtn) {
    acceptConsentBtn.addEventListener("click", function () {
      localStorage.setItem(CONSENT_STATUS_KEY, "accepted");
      localStorage.setItem(CONSENT_STATUS_KEY + "_version", CONSENT_VERSION);
      hideConsentNotification();
      recordConsent(true);
      showToastNotification(
        "Anda telah menyetujui penggunaan cookie dan cache.",
        "success"
      );
    });
  }

  if (declineConsentBtn) {
    declineConsentBtn.addEventListener("click", function () {
      localStorage.setItem(CONSENT_STATUS_KEY, "declined");
      localStorage.setItem(CONSENT_STATUS_KEY + "_version", CONSENT_VERSION);
      hideConsentNotification();
      recordConsent(false);
      showToastNotification(
        "Anda menolak penggunaan cookie dan cache. Beberapa fitur mungkin terbatas.",
        "info"
      );
    });
  }

  // Pastikan showToastNotification function is available
  function showToastNotification(message, type = "info", duration = 3000) {
    const container = document.getElementById("notification-container");
    if (!container) {
      const body = document.querySelector("body");
      const newContainer = document.createElement("div");
      newContainer.id = "notification-container";
      newContainer.style = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
      body.appendChild(newContainer);
      container = newContainer;
    }

    const notification = document.createElement("div");
    notification.style.cssText = `
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
    if (type === "success") {
      notification.style.backgroundColor = "#4CAF50";
    } else if (type === "error") {
      notification.style.backgroundColor = "#f44336";
    } else {
      // info
      notification.style.backgroundColor = "#2196F3";
    }

    notification.textContent = message;

    container.appendChild(notification);

    void notification.offsetWidth; // Trigger reflow for animation
    notification.classList.add("show"); // Apply 'show' class for fade-in effect

    setTimeout(() => {
      notification.classList.remove("show");
      notification.addEventListener(
        "transitionend",
        () => {
          notification.remove();
        },
        { once: true }
      );
    }, duration);
  }
});
