document.addEventListener("DOMContentLoaded", () => {
    // Elemen utama
    const navbar = document.querySelector(".navbar");
    const searchForm = document.querySelector(".search-box");
    const searchInput = searchForm?.querySelector('input[name="cari"]');
    const bookCards = document.querySelectorAll(".book-card");
    const navLinks = document.querySelectorAll(".nav-links a");

    // 1. Efek navbar ketika halaman digulir
    function updateNavbar() {
        if (!navbar) return;

        navbar.classList.toggle(
            "navbar-scrolled",
            window.scrollY > 20
        );
    }

    window.addEventListener("scroll", updateNavbar, {
        passive: true
    });

    updateNavbar();

    // 2. Validasi pencarian
    if (searchForm && searchInput) {
        searchForm.addEventListener("submit", (event) => {
            const keyword = searchInput.value.trim();

            if (keyword.length === 0) {
                event.preventDefault();
                searchInput.focus();
                searchInput.setCustomValidity(
                    "Masukkan judul, pengarang, atau kategori buku."
                );
                searchInput.reportValidity();
            }
        });

        searchInput.addEventListener("input", () => {
            searchInput.setCustomValidity("");
        });
    }

    // 3. Animasi kartu buku ketika masuk layar
    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries, currentObserver) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("book-visible");
                        currentObserver.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.12
            }
        );

        bookCards.forEach((card, index) => {
            card.classList.add("book-animate");
            card.style.setProperty(
                "--card-delay",
                `${(index % 4) * 80}ms`
            );

            observer.observe(card);
        });
    }

    // 4. Penanda menu sesuai bagian halaman
    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.forEach((item) => {
                item.classList.remove("active");
            });

            link.classList.add("active");
        });
    });

    // 5. Tombol kembali ke atas
    const backToTop = document.querySelector("#backToTop");

    if (backToTop) {
        window.addEventListener("scroll", () => {
            backToTop.classList.toggle(
                "show",
                window.scrollY > 400
            );
        }, {
            passive: true
        });

        backToTop.addEventListener("click", () => {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    // 6. Tahun footer otomatis
    const footerYear = document.querySelector("[data-current-year]");

    if (footerYear) {
        footerYear.textContent = new Date().getFullYear();
    }

    console.log("Arunika Library berhasil dimuat.");
});