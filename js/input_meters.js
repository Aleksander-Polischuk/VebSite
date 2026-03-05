/* =========================================
   3. ЛОГІКА ВВОДУ і рахування різниці
   ========================================= */
function handleMeterInput(input, prevValue, diffSpanId, addressIdx, contractIdx) {
    let val = input.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
    const dots = val.split('.');
    input.value = dots.length > 2 ? dots[0] + '.' + dots.slice(1).join('') : val;

    // Очищаємо всі статуси
    input.classList.remove('error', 'valid', 'warning');
    const diffSpan = document.getElementById(diffSpanId);
    let message = "";
    let statusClass = "";

    if (input.value !== '') {
        const currentNum = parseFloat(input.value);
        const prevNum = parseFloat(prevValue);

        // 1. Пріоритет помилкам (червоний)
        if (currentNum < 0) {
            message = "Число не може бути від'ємним";
            statusClass = 'error';
        } else if (currentNum < prevNum) {
            message = "Показник менший за попередній";
            statusClass = 'error';
        } 
        // 2. Попередження, якщо показник вже був переданий
        else if (input.defaultValue.trim() !== '') {
            const savedValue = parseFloat(input.defaultValue).toFixed(3).replace('.', ',');
            message = `Показник за поточний місяць вже передано: ${savedValue}`;
            statusClass = 'warning';
        } 
        // 3. Якщо все добре і це новий ввід
        else {
            statusClass = 'valid';
        }
        input.classList.add(statusClass);
        
        if (diffSpan) {
            const diff = currentNum - prevNum;
            diffSpan.textContent = diff.toFixed(3).replace('.', ',');
            diffSpan.style.color = diff < 0 ? "#e74c3c" : "#3C9ADC";
        }
    } else if (diffSpan) {
        diffSpan.textContent = "0,000";
        diffSpan.style.color = "#3C9ADC";
    }

    // Керування іконкою та підказкою
    const icon = input.nextElementSibling;
    if (icon && icon.classList.contains('error-icon')) {
        const tooltip = icon.querySelector('.error-tooltip');
        if (tooltip) tooltip.textContent = message;
        
        if (statusClass === 'error') {
            icon.style.visibility = 'visible'; // Робимо видимою
            icon.style.opacity = '1';
            icon.style.backgroundColor = '#e74c3c'; // Червоний
            icon.childNodes[0].nodeValue = '!'; 
        } else if (statusClass === 'warning') {
            icon.style.visibility = 'visible'; // Робимо видимою
            icon.style.opacity = '1';
            icon.style.backgroundColor = '#f39c12'; // Помаранчевий
            icon.childNodes[0].nodeValue = 'i'; 
        } else {
            icon.style.visibility = 'hidden';  // Ховаємо, але місце залишається
            icon.style.opacity = '0';
        }
    }

    recalculateTotals(addressIdx, contractIdx);
}

function recalculateTotals(addressIdx, contractIdx) {
    const calcSum = (selector) => {
        let sum = 0;
        document.querySelectorAll(selector).forEach(inp => {
            const v = parseFloat(inp.value.replace(',', '.')), p = parseFloat(inp.dataset.prev);
            if (!isNaN(v) && !isNaN(p)) sum += (v - p);
        });
        return sum;
    };

    const addrSum = calcSum(`.input-reading.address-group-${addressIdx}`);
    const addrSpan = document.getElementById(`sum_address_${addressIdx}`);
    if (addrSpan) {
        addrSpan.textContent = addrSum.toFixed(3).replace('.', ',');
        addrSpan.style.color = addrSum < 0 ? "#e74c3c" : "#555";
    }

    let contractSum = 0;
    document.querySelectorAll(`.sub-total-val.contract-group-${contractIdx}`).forEach(s => {
        const v = parseFloat(s.textContent.replace(',', '.'));
        if (!isNaN(v)) contractSum += v;
    });

    const contractSpan = document.getElementById(`sum_contract_${contractIdx}`);
    if (contractSpan) {
        contractSpan.textContent = contractSum.toFixed(3).replace('.', ',');
        contractSpan.style.color = contractSum < 0 ? "#e74c3c" : "#000";
    }
}

function formatOnBlur(input) {
    const num = parseFloat(input.value.replace(',', '.'));
    if (!isNaN(num)) input.value = num.toFixed(3);
}

/* =========================================
   4. ЛОГІКА ЗБЕРЕЖЕННЯ
   ========================================= */
function saveReadings() {
    const inputs = document.querySelectorAll('.input-reading');
    const dataToSend = [];
    const summaryData = {};
    let hasError = false;

    inputs.forEach(input => {
        const val = input.value.trim();
        if (!val) return;

        if (input.classList.contains('error')) {
            hasError = true;
            return;
        }

        const currentVal = val.replace(',', '.');
        const currentValNum = parseFloat(currentVal);
        const prevVal = parseFloat(input.dataset.prev);

        // ВАЖЛИВО: Перевіряємо, чи змінився показник порівняно з БД
        const defaultValNum = parseFloat(input.defaultValue.replace(',', '.'));
        
        // Якщо поле вже було передано (є defaultValue) і цифра така сама — пропускаємо його!
        if (!isNaN(defaultValNum) && currentValNum === defaultValNum) {
            return; 
        }

        if (isNaN(currentValNum)) {
            hasError = true;
            input.classList.add('error');
            return;
        }

        dataToSend.push({
            id_ref_account: input.dataset.account,
            id_ref_service: input.dataset.service,
            id_ref_counter: input.dataset.counter,
            cnt_last: prevVal,
            cnt_current: currentVal
        });

        const c = input.dataset.contractName || ("Договір №" + input.dataset.account);
        const a = input.dataset.addressName || "Адреса не вказана";
        if (!summaryData[c]) summaryData[c] = {};
        if (!summaryData[c][a]) summaryData[c][a] = [];
        summaryData[c][a].push({ name: input.dataset.counterName || "Лічильник", val: currentVal });
    });

    // 1. Перевірки валідації
    if (hasError) {
        return typeof showAlert === 'function' 
            ? showAlert("Виправте помилкові (червоні) поля перед збереженням.", 'error', 'Помилка валідації') 
            : alert("У вас є помилкові поля.");
    }

    // 2. Якщо масив порожній (нічого нового не ввели)
    if (!dataToSend.length) {
        return typeof showAlert === 'function' 
            ? showAlert("Ви не ввели жодного НОВОГО показника. Всі заповнені дані вже передані раніше.", 'warning', 'Увага') 
            : alert("Ви не ввели жодного нового показника.");
    }

    // 3. Генерація HTML для вікна підтвердження
    const htmlContent = Object.entries(summaryData).map(([contract, addresses]) => `
        <div style="margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
            <strong style="color: #3C9ADC; font-size: 1.1em; display: block; margin-bottom: 5px;">📄 ${contract}</strong>
            ${Object.entries(addresses).map(([address, meters]) => `
                <div style="margin-left: 10px; margin-bottom: 5px;">
                    <div style="font-weight: 600; color: #555;">🏠 ${address}</div>
                    <ul style="margin: 2px 0 5px 20px; padding: 0; list-style-type: disc; color: #444;">
                        ${meters.map(m => `<li>${m.name}: <b>${m.val}</b></li>`).join('')}
                    </ul>
                </div>
            `).join('')}
        </div>
    `).join('');

    const html = `<div class="alert-scroll-container">${htmlContent}</div>`;

    document.getElementById('customAlertText').innerHTML = html;
    const subText = document.getElementById('customAlertSubText');
    if (subText) {
        subText.textContent = 'Зберегти ці дані?';
        subText.style.display = 'block';
    }
    
    const alertOverlay = document.getElementById('customAlertOverlay');
    if (alertOverlay) {
        alertOverlay.style.display = 'flex';
    }

    if (typeof showAlert === 'function') {
        showAlert(html, 'warning', 'Перевірка даних', [
            { 
                text: 'Зберегти', 
                className: 'btn-alert-ok', 
                onClick: () => sendReadingsToServer(dataToSend)
            },
            { 
                text: 'Скасувати', 
                className: 'btn-alert-cancel' 
            }
        ], 'Зберегти ці дані?'); 
    }
}

function sendReadingsToServer(data) {
    fetch('/api/save_meters.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success') {
            showAlert(
                'Дані передані в чергу на обробку!', 
                'success', 
                'Дані прийнято'
            );
            
            if (window.refreshActiveContent) {
                window.refreshActiveContent();
            }
        } else {
            showAlert(
                "Помилка при збереженні: " + (res.message || "Невідома помилка"), 
                'error', 
                'Відмова сервера'
            );
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert(
            "Не вдалося надіслати дані. Перевірте з'єднання з інтернетом або зверніться до підтримки.", 
            'error', 
            'Помилка мережі'
        );
    });
}
