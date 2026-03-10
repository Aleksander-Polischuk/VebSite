function toggleFaq(element) {
    const answer = element.nextElementSibling;
    if (!answer) return;
    
    const isOpening = answer.style.display !== 'block';
    
    // Закриваємо всі інші відкриті питання
    document.querySelectorAll('.faq-answer').forEach(el => {
        el.style.display = 'none';
    });
    document.querySelectorAll('.faq-question').forEach(el => {
        el.classList.remove('open');
    });

    if (isOpening) {
        answer.style.display = 'block';
        element.classList.add('open');
    }
}

// Перехоплювач кліків для посилань всередині відповідей FAQ
document.addEventListener('click', function(e) {
    // 1. Перевіряємо, чи клік був саме по посиланню всередині нашого редактора
    const link = e.target.closest('.quill-content a');
    if (!link) return;

    // 2. Отримуємо те, що адмін ввів у поле "URL"
    let href = link.getAttribute('href');
    if (!href) return;

    // 3. Якщо це зовнішнє посилання (на інший сайт або пошту), нічого не робимо
    if (href.startsWith('http') || href.startsWith('mailto')) {
        link.setAttribute('target', '_blank'); // Хай відкривається в новому вікні
        return; 
    }

    // 4. Очищаємо назву (прибираємо можливі решітки або слеші, якщо адмін ввів "#Тарифи")
    let targetName = decodeURIComponent(href).replace(/^#|^\//, '').trim().toLowerCase();

    // 5. Шукаємо відповідну вкладку в боковому меню
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    for (let sidebarLink of sidebarLinks) {
        let linkText = sidebarLink.innerText.trim().toLowerCase();
        
        // Якщо назва збіглася з назвою вкладки (наприклад, "тарифи" == "тарифи")
        if (linkText === targetName) {
            e.preventDefault(); // Зупиняємо стандартний перехід браузера
            sidebarLink.click(); // Програмно "клікаємо" по вкладці меню
            
            // Прокручуємо сторінку нагору, щоб користувач побачив нову вкладку
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
    }
});