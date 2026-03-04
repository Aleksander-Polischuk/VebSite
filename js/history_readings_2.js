/* =========================================
   5. ЛОГІКА СТОРІНКИ "Історія показників"
   ========================================= */

function initHistoryPageLogic() {
    const selC = document.getElementById('sel_contract');
    const selA = document.getElementById('sel_address');
    const mapDataEl = document.getElementById('address_map_data');
    
    if (!selC || !selA || !mapDataEl) return;

    // Зчитуємо тільки карту адрес
    const addressMap = JSON.parse(mapDataEl.value || "{}");
    const allRows = document.querySelectorAll('.history-data-row');
    const noDataRow = document.getElementById('history_no_data');

    // Відновлюємо збережені в PHP значення
    const savedC = document.getElementById('php_saved_contract').value;
    let savedA = document.getElementById('php_saved_address').value;

    if (savedC && addressMap[savedC]) {
        selC.value = savedC;
    }

    // 1. Коли змінюється договір -> Оновлюємо список адрес
    selC.addEventListener('change', function() {
        const cID = this.value;
        selA.innerHTML = '';
        selA.disabled = true;

        if (cID && addressMap[cID]) {
            selA.disabled = false;
            Object.keys(addressMap[cID]).forEach(aKey => {
                selA.add(new Option(addressMap[cID][aKey], aKey));
            });

            // Вибираємо збережену адресу або першу
            if (savedA && addressMap[cID][savedA]) {
                selA.value = savedA;
                savedA = ""; // Скидаємо після першого застосування
            } else if (selA.options.length > 0) {
                selA.value = selA.options[0].value;
            }
        }
        
        selA.dispatchEvent(new Event('change')); // Тригеримо оновлення таблиці
    });

    // 2. Коли змінюється адреса -> Фільтруємо готову таблицю і зберігаємо в сесію
    selA.addEventListener('change', function() {
        const cID = selC.value;
        const aID = selA.value;
        let visibleCount = 0;

        // Просто приховуємо або показуємо рядки, які вже намалював PHP
        allRows.forEach(row => {
            if (row.dataset.contract === cID && row.dataset.address === aID) {
                row.style.display = ''; // Показуємо
                visibleCount++;
            } else {
                row.style.display = 'none'; // Ховаємо
            }
        });

        // Показуємо плашку "Немає даних", якщо показано 0 рядків
        if (noDataRow) {
            noDataRow.style.display = (visibleCount === 0) ? '' : 'none';
        }

        // Фонове збереження стану
        const fd = new FormData();
        fd.append('action', 'save_state');
        fd.append('c', cID);
        fd.append('a', aID || '');
        fetch(window.location.href, { method: 'POST', body: fd });
    });

    // Запускаємо логіку при старті сторінки
    selC.dispatchEvent(new Event('change'));
}