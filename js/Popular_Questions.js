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
