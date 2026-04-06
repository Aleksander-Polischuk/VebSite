/* =========================================
   ВВОД і рахування різниці
   ========================================= */
function handleMeterInput(input, prevValue, diffSpanId, addressIdx, contractIdx) {
    let val = input.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
    const dots = val.split('.');
    input.value = dots.length > 2 ? dots[0] + '.' + dots.slice(1).join('') : val;

    input.classList.remove('error', 'warning', 'valid');
    
    const diffSpan = document.getElementById(diffSpanId);
    let message = "";
    let statusClass = "";

    // Отримуємо ліміти з бази
    const maxVol = parseFloat(input.dataset.maxVol) || 0;
    const warningVol = parseFloat(input.dataset.warningVol) || 0;

    if (input.value !== '') {
        const currentNum = parseFloat(input.value);
        const prevNum = parseFloat(prevValue);
        const diff = currentNum - prevNum;


        if (currentNum < 0 || currentNum < prevNum || (maxVol > 0 && diff > maxVol)) {
            statusClass = 'error';
            if (currentNum < 0) message = "Число не може бути від'ємним";
            else if (currentNum < prevNum) message = "Показник менший за попередній";
            else message = `Перевищено ліміт: ${diff.toFixed(3)} (макс. ${maxVol})`;
        } 
        else if ((warningVol > 0 && diff >= warningVol) || input.defaultValue.trim() !== '') {
            statusClass = 'warning';
            if (warningVol > 0 && diff >= warningVol) {
                message = `Увага! Великий об'єм: ${diff.toFixed(3)}`;
            } else {
                const savedValue = parseFloat(input.defaultValue).toFixed(3).replace('.', ',');
                message = `Показник за поточний місяць вже передано: ${savedValue}`;
            }
        } 
        else {
            statusClass = 'valid';
        }

        input.classList.add(statusClass);
        
        if (diffSpan) {
            diffSpan.innerText = diff.toFixed(3).replace('.', ',');
            diffSpan.style.color = (statusClass === 'error') ? "#e74c3c" : "#3C9ADC";
        }
    } else if (diffSpan) {
        diffSpan.innerText = "0,000";
        diffSpan.style.color = "#3C9ADC";
    }

    // Керування іконкою та підказкою
    const icon = input.nextElementSibling;
    if (icon && icon.classList.contains('error-icon')) {
        const tooltip = icon.querySelector('.error-tooltip');
        if (tooltip) tooltip.textContent = message;

        if (statusClass === 'error' || statusClass === 'warning') {
            icon.style.display = 'flex';
            icon.style.visibility = 'visible';
            icon.style.opacity = '1';
            icon.style.backgroundColor = (statusClass === 'error') ? '#e74c3c' : '#f39c12';
            icon.childNodes[0].nodeValue = (statusClass === 'warning' && input.defaultValue.trim() !== '' && !(warningVol > 0 && (parseFloat(input.value) - parseFloat(prevValue)) >= warningVol)) ? 'i' : '!';
        } else {
            icon.style.display = 'none';
            icon.style.visibility = 'hidden';
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
        addrSpan.innerText = addrSum.toFixed(3).replace('.', ',');
        addrSpan.style.color = addrSum < 0 ? "#e74c3c" : "#555";
    }

    let contractSum = 0;
    document.querySelectorAll(`.sub-total-val.contract-group-${contractIdx}`).forEach(s => {
        const v = parseFloat(s.innerText.replace(',', '.'));
        if (!isNaN(v)) contractSum += v;
    });

    const contractSpan = document.getElementById(`sum_contract_${contractIdx}`);
    if (contractSpan) {
        contractSpan.innerText = contractSum.toFixed(3).replace('.', ',');
        contractSpan.style.color = contractSum < 0 ? "#e74c3c" : "#000";
    }
}

function formatOnBlur(input) {
    const num = parseFloat(input.value.replace(',', '.'));
    if (!isNaN(num)) input.value = num.toFixed(3);
}

/* =========================================
    ЗБЕРЕЖЕННЯ
   ========================================= */
function saveReadings() {
    const inputs = document.querySelectorAll('.input-reading');
    const dataToSend = [];
    const summaryData = {};
    let hasError = false; 
    let isVolumeWarning = false; 

    inputs.forEach(input => {
        const val = input.value.trim();
        if (!val) return;

        const currentValNum = parseFloat(val.replace(',', '.'));
        const prevValNum = parseFloat(input.dataset.prev);
        const diff = currentValNum - prevValNum;
        const warningLimit = parseFloat(input.dataset.warningVol) || 0;

        // Перевірка на червоні поля
        if (input.classList.contains('error')) {
            hasError = true;
            return;
        }

        // Перевірка: чи є помаранчевий колір саме через великий об'єм?
        if (warningLimit > 0 && diff >= warningLimit) {
            isVolumeWarning = true;
        }

        const defaultValNum = parseFloat(input.defaultValue.replace(',', '.'));
        if (!isNaN(defaultValNum) && currentValNum === defaultValNum) return; 

        dataToSend.push({
            id_ref_account: input.dataset.account,
            id_ref_service: input.dataset.service,
            id_ref_counter: input.dataset.counter,
            cnt_last: prevValNum,
            cnt_current: currentValNum
        });

        const c = input.dataset.contractName || ("Договір №" + input.dataset.account);
        const a = input.dataset.addressName || "Адреса не вказана";
        if (!summaryData[c]) summaryData[c] = {};
        if (!summaryData[c][a]) summaryData[c][a] = [];
        summaryData[c][a].push({ name: input.dataset.counterName || "Лічильник", val: currentValNum.toFixed(3) });
    });

    if (hasError) {
        return typeof showAlert === 'function' 
            ? showAlert("Виправте помилкові (червоні) поля перед збереженням. Деякі показники перевищують ліміт або менші за попередні.", 'error', 'Помилка валідації') 
            : alert("Виправте червоні поля.");
    }

    if (!dataToSend.length) {
        return typeof showAlert === 'function' 
            ? showAlert("Ви не ввели жодного НОВОГО показника.", 'warning', 'Увага') 
            : alert("Нічого не змінено.");
    }

    // Визначаємо текст підтвердження
    let subConfirmText = 'Зберегти ці дані?';
    if (isVolumeWarning) {
        subConfirmText = 'Увага! Ви ввели дуже великі об’єми споживання (виділено помаранчевим). Ви впевнені, що показники вірні?';
    }

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

    if (typeof showAlert === 'function') {
        showAlert(html, 'warning', 'Перевірка даних', [
            { text: 'Зберегти', className: 'btn-alert-ok', onClick: () => sendReadingsToServer(dataToSend) },
            { text: 'Скасувати', className: 'btn-alert-cancel' }
        ], subConfirmText); 
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
            showAlert('Дані успішно передані!', 'success', 'Дані прийнято');
            if (window.refreshActiveContent) window.refreshActiveContent();
        } else {
            showAlert("Помилка: " + (res.message || "Невідома помилка"), 'error', 'Відмова сервера');
        }
    });
}