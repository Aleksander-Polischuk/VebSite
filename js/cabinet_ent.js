/* =========================================
   1. ІНІЦІАЛІЗАЦІЯ ТА НАВІГАЦІЯ
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('mainContent');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const scrollBtn = document.getElementById("scrollToTopBtn");

    // === Відновлюємо збережену вкладку до завантаження ===
    const savedPage = localStorage.getItem('activeCabinetPage');
    if (savedPage) {
        document.querySelectorAll('.sidebar a:not(.btn-logout)').forEach(link => {
            if (link.innerText.trim() === savedPage) {
                // Знімаємо клас з тієї вкладки, яку випадково призначив PHP
                document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
                // Ставимо збережену
                link.classList.add('active');
            }
        });
    }
    // ============================================================

    const pageContainer = document.createElement('div');
    pageContainer.id = 'pageContainer';
    mainContent.innerHTML = '';
    mainContent.appendChild(pageContainer);

    const loadedPages = {};

    const loadPage = (pageName, linkElement) => {
        return fetch(`api/get_content.php?page=${encodeURIComponent(pageName)}`)
            .then(r => r.text())
            .then(data => {
                const pageWrapper = document.createElement('div');
                pageWrapper.classList.add('page-wrapper');
                pageWrapper.id = `page-${pageName}`;
                pageWrapper.innerHTML = data;
                pageWrapper.style.display = 'none';
                pageContainer.appendChild(pageWrapper);

                loadedPages[pageName] = pageWrapper;

                if (typeof initQuillEditor === 'function') initQuillEditor();
                if (typeof initHistoryPageLogic === 'function') initHistoryPageLogic();
                
                const inputs = pageWrapper.querySelectorAll('.input-reading');
                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
                
                return pageWrapper;
            });
    };

    const loadAllPages = () => {
        mainContent.style.opacity = '0.6'; 
        const links = Array.from(document.querySelectorAll('.sidebar a:not(.btn-logout)'));
        
        const fetchPromises = links.map(link => {
            const pageName = link.innerText.trim();
            return loadPage(pageName, link);
        });

        Promise.all(fetchPromises)
            .then(() => {
                mainContent.style.opacity = '1';
                // Шукаємо вкладку з класом active
                const activeLink = document.querySelector('.sidebar a.active');
                if (activeLink) {
                   showPage(activeLink.innerText.trim());
                } else if (links.length > 0) {
                   showPage(links[0].innerText.trim());
                   links[0].classList.add('active');
                }
            })
            .catch(error => {
                console.error("Error preloading pages:", error);
                mainContent.style.opacity = '1';
                mainContent.innerHTML = "Помилка завантаження контенту.";
            });
    };

    const showPage = (pageName) => {
        Object.values(loadedPages).forEach(pageElement => {
            pageElement.style.display = 'none';
        });

        const pageElement = loadedPages[pageName];
        if (pageElement) {
            pageElement.style.display = 'block';

            const historyTable = pageElement.querySelector('#history-container');
            if (historyTable && window.innerWidth <= 850) {
                setTimeout(() => historyTable.scrollLeft = historyTable.scrollWidth, 100);
            }
        } else {
             console.error(`Page ${pageName} not found in preloaded pages.`);
        }
    };

    // Навігація меню
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function(e) {

            if (this.classList.contains('btn-logout')) return;
            e.preventDefault();

            document.querySelectorAll('.sidebar a').forEach(item => item.classList.remove('active'));
            this.classList.add('active');

            const pageName = this.innerText.trim();
            
            // === Зберігаємо вибір вкладки в пам'ять браузера ===
            localStorage.setItem('activeCabinetPage', pageName);
            // ==========================================================

            showPage(pageName);

            if (window.innerWidth <= 850) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            }
        });
    });

    loadAllPages();

    document.getElementById('burgerBtn')?.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    document.addEventListener('scroll', () => {
        if(scrollBtn) scrollBtn.classList.toggle("show", window.scrollY > 300);
    }, true);

    if (scrollBtn) {
        scrollBtn.onclick = () => {
            document.querySelector(".table-container")?.scrollTo({ top: 0, behavior: "smooth" });
            window.scrollTo({ top: 0, behavior: "smooth" });
        };
    }

    if (typeof updateHeaderBalance === 'function') updateHeaderBalance();
});

/* =========================================
   2. Оновлення балансу та зміна року
   ========================================= */
function updateHeaderBalance() {
    fetch('/api/get_balance.php')
        .then(r => r.json())
        .then(data => {
            // Оновлюємо елемент балансу
            const balanceEl = document.querySelector('.balance');
            if (balanceEl) {
                balanceEl.innerText = data.balance + ' грн';
                let numValue = parseFloat(data.balance.replace(/\s/g, '').replace(',', '.'));
                balanceEl.classList.toggle('negative', numValue < 0);
            }
            // Оновлюємо дату
            const dateSpan = document.querySelector('.balance-sub span');
            if (dateSpan) dateSpan.innerText = data.date;
        })
        .catch(err => console.error('Error updating balance:', err));
}

// функція зміни року: оновлює контент тільки активної вкладки
function changeYear(year) {
    const activeLink = document.querySelector('.sidebar a.active');
    if (!activeLink) return;
    
    const pageName = activeLink.innerText.trim();
    const pageWrapper = document.getElementById(`page-${pageName}`);
    
    if (!pageWrapper) return;

    const mainContent = document.getElementById('mainContent');
    mainContent.style.opacity = '0.5';

    fetch(`/api/get_content.php?page=${encodeURIComponent(pageName)}&year=${year}`)
        .then(r => r.text())
        .then(data => {
            // Оновлюємо вміст активної сторінки
            pageWrapper.innerHTML = data;
            mainContent.style.opacity = '1';
            
            if (typeof initHistoryPageLogic === 'function') {
                initHistoryPageLogic();
            }
        })
        .catch(err => {
            console.error('Помилка оновлення року:', err);
            mainContent.style.opacity = '1';
        });
}

// Оновлення контенту всіх вкладок
window.refreshActiveContent = () => {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.style.opacity = '0.5';

    //  Беремо всі вкладки з меню
    const links = Array.from(document.querySelectorAll('.sidebar a:not(.btn-logout)'));
    
    //  Створюємо запити для кожної вкладки
    const fetchPromises = links.map(link => {
        const pageName = link.innerText.trim();
        const pageWrapper = document.getElementById(`page-${pageName}`);
        
        if (!pageWrapper) return Promise.resolve();

        // Завантажуємо свіжі дані з новим контрагентом для кожної сторінки
        return fetch(`/api/get_content.php?page=${encodeURIComponent(pageName)}`)
            .then(r => r.text())
            .then(data => {
                 pageWrapper.innerHTML = data; 
            });
    });

    //  Чекаємо, поки всі вкладки оновляться
    Promise.all(fetchPromises)
        .then(() => {
            mainContent.style.opacity = '1';
            
            // Перезапуск скриптів
            if (typeof initQuillEditor === 'function') initQuillEditor();
            if (typeof initHistoryPageLogic === 'function') initHistoryPageLogic();
            
            const activeLink = document.querySelector('.sidebar a.active');
            if (activeLink) {
                const activePageName = activeLink.innerText.trim();
                const activeWrapper = document.getElementById(`page-${activePageName}`);
                if (activeWrapper) {
                    const inputs = activeWrapper.querySelectorAll('.input-reading');
                    inputs.forEach(input => {
                        if (input.value.trim() !== '') {
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                }
            }
        })
        .catch(err => {
            console.error("Помилка оновлення сторінок:", err);
            mainContent.style.opacity = '1';
        });
};

function openSignWindow(invoiceId) {
    window.open('/api/content/SigningDocs.php?id=' + invoiceId, 'sign_window_' + invoiceId);
}