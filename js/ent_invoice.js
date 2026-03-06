document.addEventListener('DOMContentLoaded', function() {
    // Знаходимо всі кнопки "Підписати"
    const signButtons = document.querySelectorAll('.btn-sign');
    
    signButtons.forEach(button => {
        button.addEventListener('click', function() {
            const invoiceId = this.getAttribute('data-id');
            
            // Якщо кнопка заблокована (документ вже підписано), нічого не робимо
            if (this.hasAttribute('disabled')) return;
            
            // Відкриваємо вікно підписання
            window.open('/api/content/SigningDocs.php?id=' + invoiceId, 'sign_window_' + invoiceId);
        });
    });
});