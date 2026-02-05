const alertOverlay = document.getElementById('customAlertOverlay');
const alertText = document.getElementById('customAlertText');
const alertOkBtn = document.querySelector('.btn-alert-ok');

// Функція виклику (заміна alert)
function showAlert(message) {
    if (!alertOverlay) {
        console.error('Custom Alert HTML not found!');
        // Фоллбек на звичайний алерт, якщо HTML не підключено
        alert(message); 
        return;
    }
    
    alertText.innerHTML = message; // Дозволяє використовувати HTML теги, наприклад <br>
    alertOverlay.style.display = 'flex';
    
    // Фокус на кнопку OK, щоб можна було натиснути Enter
    setTimeout(() => {
        alertOkBtn.focus();
    }, 100);
}

// Функція закриття
function closeAlert() {
    if (alertOverlay) {
        alertOverlay.style.display = 'none';
    }
}

// Закриття по кліку поза вікном (опціонально)
if (alertOverlay) {
    alertOverlay.addEventListener('click', function(e) {
        if (e.target === alertOverlay) {
            closeAlert();
        }
    });
}

// Закриття по Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && alertOverlay.style.display === 'flex') {
        closeAlert();
    }
});