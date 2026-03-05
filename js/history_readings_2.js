/* =========================================
   5. ЛОГІКА СТОРІНКИ "Історія показників" (Виправлено)
   ========================================= */

function initHistoryPageLogic() {
    const selC = document.getElementById('sel_contract');
    const selA = document.getElementById('sel_address');
    const mapDataEl = document.getElementById('address_map_data');
    
    if (!selC || !selA || !mapDataEl) return;

    const addressMap = JSON.parse(mapDataEl.value || "{}");
    const allRows = document.querySelectorAll('.history-data-row');
    const noDataRow = document.getElementById('history_no_data');

    const savedC = document.getElementById('php_saved_contract').value;
    let savedA = document.getElementById('php_saved_address').value;

    // Прапорець, щоб не відправляти POST при ініціалізації
    let isInitialLoad = true;

    if (savedC && addressMap[savedC]) {
        selC.value = savedC;
    }

    selC.addEventListener('change', function() {
        const cID = this.value;
        selA.innerHTML = '';
        selA.disabled = true;

        if (cID && addressMap[cID]) {
            selA.disabled = false;
            Object.keys(addressMap[cID]).forEach(aKey => {
                selA.add(new Option(addressMap[cID][aKey], aKey));
            });

            if (savedA && addressMap[cID][savedA]) {
                selA.value = savedA;
                savedA = ""; 
            } else if (selA.options.length > 0) {
                selA.value = selA.options[0].value;
            }
        }
        
        selA.dispatchEvent(new Event('change')); 
    });

    selA.addEventListener('change', function() {
        const cID = selC.value;
        const aID = selA.value;
        let visibleCount = 0;

        allRows.forEach(row => {
            if (row.dataset.contract === cID && row.dataset.address === aID) {
                row.style.display = ''; 
                visibleCount++;
            } else {
                row.style.display = 'none'; 
            }
        });

        if (noDataRow) {
            noDataRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    });

    // Запускаємо логіку, але позначаємо, що завантаження завершене після виконання
    selC.dispatchEvent(new Event('change'));
    isInitialLoad = false; 
}