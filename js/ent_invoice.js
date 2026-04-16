// Функція для відкриття вікна підпису з автооновленням батьківського вікна
function openSignWindow(id, type) {
    const url = `/api/content/SigningDocs.php?id=${id}&doctype=${type}`;
    const signWin = window.open(url, 'sign_window_' + id);
    
    // Перевірка, чи не заблокував браузер вікно
    if (!signWin || signWin.closed || typeof signWin.closed === 'undefined') {
        showAlert(
            "Ваш браузер заблокував спливаюче вікно для накладання КЕП. <br><br>Будь ласка, дозвольте спливаючі вікна для цього сайту в налаштуваннях браузера (значок у адресному рядку).", 
            "warning", 
            "Вікно заблоковано!"
        );
        return;
    }
    if (signWin) {
        const timer = setInterval(() => {
            if (signWin.closed) {
                clearInterval(timer);
                
                // Це оновить і цифру в сайдбарі, і заблокує кнопку вилучення в таблиці
                window.location.reload(); 
            }
        }, 1000);
    }
}
function confirmDeleteDocument(id, type, btnElement) {
    if (type !== 'act') return;

    showAlert(
        "Ви дійсно бажаєте вилучити цей акт та всі пов'язані з ним показники?<br><br><b style='color:#e74c3c;'>Цю дію неможливо буде скасувати.</b>", 
        "warning", 
        "Підтвердження вилучення", 
        [
            {
                text: "Так, вилучити", 
                className: "btn-alert-ok",
                onClick: () => {
                    // Передаємо кнопку далі на серверний запит
                    deleteDocumentFromServer(id, btnElement);
                }
            },
            {
                text: "Скасувати", 
                className: "btn-alert-cancel"
            }
        ]
    );
}

function deleteDocumentFromServer(id, btnElement) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('/api/delete_document.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // МИТТЄВЕ ВІЗУАЛЬНЕ ВИДАЛЕННЯ: знаходимо рядок <tr>, у якому лежить кнопка, і видаляємо його
            if (btnElement) {
                const row = btnElement.closest('tr');
                if (row) row.remove();
            }

            showAlert("Акт та показники успішно вилучено.", "success", "Виконано");
            
            // Оновлюємо помаранчевий кружечок
            if (typeof window.updateDocumentBadge === 'function') window.updateDocumentBadge();
            
            // Фонове оновлення контенту (про всяк випадок)
            if (typeof refreshActiveContent === 'function') refreshActiveContent();
        } else {
            showAlert("Помилка: " + data.error, "error", "Помилка вилучення");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert("Помилка зв'язку з сервером", "error");
    });
}

window.filterDocuments = function() {
    const typeFilter = document.getElementById('filter-type').value;
    const statusFilter = document.getElementById('filter-status').value;

    const detailRows = document.querySelectorAll('.data-table tbody tr.detail-row');
    
    // 1. Фільтруємо самі документи
    detailRows.forEach(row => {
        const docType = row.getAttribute('data-type');
        const docStatus = row.getAttribute('data-status');
        
        const typeMatch = (typeFilter === 'all' || typeFilter === docType);
        const statusMatch = (statusFilter === 'all' || statusFilter === docStatus);
        
        if (typeMatch && statusMatch) {
            row.classList.remove('filtered-hidden');
        } else {
            row.classList.add('filtered-hidden');
        }
    });

    // 2. Сховуємо/Показуємо рядки договорів
    const parentRows = document.querySelectorAll('.data-table tbody tr.parent-row');
    parentRows.forEach(parent => {
        const groupId = parent.getAttribute('data-group');
        if (!groupId) return;
        
        const children = document.querySelectorAll(`.data-table tbody tr.detail-row.${groupId}`);
        // Шукаємо, чи є хоча б один видимий документ у цьому договорі
        const visibleChildren = Array.from(children).filter(child => !child.classList.contains('filtered-hidden'));
        
        if (visibleChildren.length === 0 && children.length > 0) {
            parent.classList.add('filtered-hidden'); // Ховаємо договір, бо він порожній
        } else {
            parent.classList.remove('filtered-hidden'); // Показуємо договір
        }
    });
};