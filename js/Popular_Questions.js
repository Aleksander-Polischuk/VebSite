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

document.addEventListener('click', function(e) {
    const link = e.target.closest('.quill-content a');
    if (!link) return;

    let href = link.getAttribute('href');
    if (!href) return;

    let rawTarget = '';
    try {
        rawTarget = decodeURIComponent(href).toLowerCase();
    } catch(err) {
        rawTarget = href.toLowerCase();
    }
    
    // Якщо це зовнішнє посилання АБО файл, дозволяємо браузеру просто відкрити його!
    if (href.startsWith('http') || href.startsWith('mailto') || href.includes('/uploads/') || href.match(/\.(pdf|doc|docx|xls|xlsx|csv|png|jpg|zip|rar)$/i)) {
        link.setAttribute('target', '_blank'); // Відкрити в новому вікні
        return; 
    }
    
    // очистка посилання
    let cleanTarget = rawTarget.replace(/^https?:\/\//, '').replace(/^#|^\//, '').trim();
    
    // пошук вкладки в меню
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    let foundMatch = false;

    for (let sidebarLink of sidebarLinks) {
        let linkText = sidebarLink.innerText.trim().toLowerCase();
        
        if (cleanTarget.includes(linkText) || (cleanTarget.length > 3 && linkText.includes(cleanTarget))) {
            e.preventDefault(); 
            
            // Перемикаємо меню
            sidebarLink.click(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            foundMatch = true;
            break; 
        }
    }

    // якщо це не наша менюшка, а якесь інше посилання, то відкриваємо в новій вкладці
    if (!foundMatch) {
        link.setAttribute('target', '_blank'); 
    }
});