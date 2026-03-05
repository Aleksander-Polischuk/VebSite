// Глобальна функція для FAQ, доступна для всіх завантажених сторінок
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

// Обробник кліків для внутрішніх посилань (наприклад, на вкладки кабінету)
// Використовуємо делегування подій, щоб працювало для динамічно завантаженого контенту
document.addEventListener('click', function(e) {
    // Шукаємо найближчий тег <a> по якому клікнули
    const link = e.target.closest('a');
    
    // Якщо клік був по посиланню, і його href починається з "#tab-"
    if (link && link.getAttribute('href') && link.getAttribute('href').startsWith('#tab-')) {
        e.preventDefault(); // Зупиняємо стандартний перехід браузера
        
        // Витягуємо назву вкладки (наприклад, "Передача показників")
        // decodeURIComponent перетворює закодовані символи (%D0%9F...) назад у нормальну кирилицю
        const targetName = decodeURIComponent(link.getAttribute('href').replace('#tab-', ''));
        
        // Шукаємо цю вкладку в боковому меню і "клікаємо" по ній
        let tabFound = false;
        document.querySelectorAll('.sidebar a').forEach(function(a) { 
            if (a.innerText.trim() === targetName) {
                a.click();
                tabFound = true;
            }
        });
        
        // Якщо хтось помилився в назві вкладки при створенні питання, виводимо попередження в консоль
        if (!tabFound) {
            console.warn('Вкладку з назвою "' + targetName + '" не знайдено в меню.');
        }
    }
});