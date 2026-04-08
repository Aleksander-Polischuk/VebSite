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


//Функція для підтвердження та формування акту передачі показників
 
/**
 * Функція для підтвердження та формування акту передачі показників
 */
function confirmGenerateAct() {
    const inputs = document.querySelectorAll('.input-reading');
    const contracts = {};

    // 1. Збираємо статистику по кожному договору
    inputs.forEach(input => {
        const cName = input.dataset.contractName || 'Без договору';
        const mName = input.dataset.counterName || 'Невідомий лічильник';
        const prevVal = input.dataset.prev || '0.000'; 
        const val = input.value.trim();

        // Ініціалізуємо договір у нашому об'єкті, якщо його ще немає
        if (!contracts[cName]) {
            contracts[cName] = { total: 0, filled: 0, emptyMeters: [], data: [] };
        }

        contracts[cName].total++;

        if (val !== '') {
            contracts[cName].filled++;
            contracts[cName].data.push({
                // ДОДАЙ ОСЬ ЦІ ДВА РЯДКИ:
                contractName: cName,
                counteragent: input.dataset.counteragent || "Організація",
                
                // Це те, що в тебе вже є:
                meterName: mName,
                prevValue: prevVal,
                currValue: val,
                objectName: input.dataset.objectName || '---', 
                addressName: input.dataset.addressName || '---',
                meterMark: input.dataset.meterMark || '---',
                meterNum: input.dataset.meterNum || '---'
            });
        } else {
            // Якщо пусто, запам'ятовуємо назву лічильника для тексту помилки
            contracts[cName].emptyMeters.push(mName);
        }
    });

    let hasPartiallyFilled = false;
    let completelyEmptyContracts = 0;
    let totalContracts = Object.keys(contracts).length;
    let errorMessage = "";
    let finalDataToGenerate = [];

    // 2. Перевіряємо правила заповнення для кожного договору
    for (const [cName, stats] of Object.entries(contracts)) {
        // Якщо заповнено більше 0, але менше ніж усього лічильників
        if (stats.filled > 0 && stats.filled < stats.total) {
            hasPartiallyFilled = true;
            errorMessage += `<br><br><b style="color:#e74c3c;">${cName}</b><br>Пропущено: ${stats.emptyMeters.join(', ')}`;
        } 
        // Якщо договір заповнено повністю
        else if (stats.filled === stats.total) {
            // Додаємо дані цього договору в загальний масив для генерації ПДФ
            finalDataToGenerate = finalDataToGenerate.concat(stats.data);
        } 
        // Якщо взагалі не чіпали (ігноруємо)
        else if (stats.filled === 0) {
            completelyEmptyContracts++;
        }
    }

    // 3. Відловлюємо помилки

    // Якщо взагалі на сторінці нічого не заповнили
    if (completelyEmptyContracts === totalContracts) {
        if (typeof showAlert === 'function') {
            showAlert("Будь ласка, введіть показники для формування акту.", "error", "Помилка");
        } else {
            alert("Будь ласка, введіть показники.");
        }
        return;
    }

    // Якщо є договори, заповнені лише частково
    if (hasPartiallyFilled) {
        const msg = "Для формування акту необхідно заповнити <b>всі</b> лічильники в межах договору!" + errorMessage;
        if (typeof showAlert === 'function') {
            showAlert(msg, "warning", "Увага! Незаповнені поля");
        } else {
            alert("Заповніть всі лічильники для розпочатого договору!");
        }
        return;
    }

    // 4. Якщо перевірки пройдені успішно — викликаємо фінальне вікно
    if (typeof showAlert === 'function') {
        showAlert(
            "Сформувати акт передачі за поточними показниками?", 
            "warning", 
            "Підтвердження", 
            [
                {
                    text: "Сформувати", 
                    className: "btn-alert-ok",
                    onClick: () => {
                        // Передаємо ТІЛЬКИ повністю заповнені договори в pdfmake
                        generatePdfClientSide(finalDataToGenerate);
                    }
                },
                {
                    text: "Скасувати", 
                    className: "btn-alert-cancel",
                    onClick: () => {}
                }
            ]
        );
    }
}
// ОСНОВНА ФУНКЦІЯ ГЕНЕРАЦІЇ ПДФ НА СТОРОНІ КЛІЄНТА (через pdfmake)
function generatePdfClientSide(data) {
    const currentDateStr = new Date().toLocaleDateString('uk-UA');
    
    document.getElementById('overlay').style.display = 'block';
    
    // Дістаємо загальні дані з першого лічильника (назва контрагента і номер договору)
    const contractName = data[0]?.contractName || "___";
    const counteragentName = data[0]?.counteragent || "Організація";

    // 2. Формуємо шапку таблиці
    const tableBody = [
        [
            { text: '№ п/п', rowSpan: 2, alignment: 'center', margin: [0, 10, 0, 0] },
            { text: 'Назва об’єкту', rowSpan: 2, alignment: 'center', margin: [0, 10, 0, 0] },
            { text: 'Адреса', rowSpan: 2, alignment: 'center', margin: [0, 10, 0, 0] },
            { text: 'Лічильник', colSpan: 2, alignment: 'center' },
            {}, 
            { text: 'Показники лічильника (м3)', colSpan: 2, alignment: 'center' },
            {}, 
            { text: 'Різниця всього (м3)', rowSpan: 2, alignment: 'center', margin: [0, 5, 0, 0] },
            { text: 'в т.ч. різниця населення', rowSpan: 2, alignment: 'center' }
        ],
        [
            {}, {}, {}, 
            { text: 'марка', alignment: 'center' },
            { text: 'заводський №', alignment: 'center' },
            { text: 'попередні', alignment: 'center' },
            { text: 'поточні', alignment: 'center' },
            {}, {} 
        ]
    ];

    // 3. Динамічно заповнюємо рядки таблиці реальними даними з БД
    data.forEach((item, index) => {
        let prev = parseFloat(item.prevValue.replace(',', '.')) || 0;
        let curr = parseFloat(item.currValue.replace(',', '.')) || 0;
        let diff = (curr - prev).toFixed(3);

        tableBody.push([
            { text: (index + 1).toString(), alignment: 'center' },
            { text: item.objectName, alignment: 'left' },   // З БД
            { text: item.addressName, alignment: 'left' },  // З БД
            { text: item.meterMark, alignment: 'center' },    // З БД
            { text: item.meterNum, alignment: 'center' },     // З БД
            { text: item.prevValue, alignment: 'center' },
            { text: item.currValue, alignment: 'center', bold: true },
            { text: diff, alignment: 'center' },
            { text: '0.000', alignment: 'center' }            // З БД (якщо є такий показник)
        ]);
    });

    // 4. Структура PDF документа
    const docDefinition = {
        pageOrientation: 'portrait', 
        pageSize: 'A4',
        pageMargins: [30, 40, 30, 40], 

        content: [
            { text: `Додаток № 4 до договору ${contractName}`, alignment: 'right', margin: [0, 0, 0, 15] },
            { text: 'Звіт про об’єми використаної води', fontSize: 12, bold: true, alignment: 'center' },
            { text: `станом на ${currentDateStr}`, alignment: 'center', margin: [0, 0, 0, 20] },

            { text: counteragentName, alignment: 'center', bold: true, decoration: 'underline' },
            { text: '(назва організації)', alignment: 'center', fontSize: 9, color: '#555', margin: [0, 0, 0, 15] },

            {
                style: 'tableStyle',
                table: {
                    headerRows: 2,
                    widths: ['2%', '20%', '25%', '8%', '10%', '9%', '9%', '8%', '9%'],
                    body: tableBody
                }
            },
            {
                text: '"Рахунок виставлений згідно звіту, зобов’язуємось оплатити протягом 3-х днів з дня його отримання."',
                italics: true,
                margin: [0, 30, 0, 0]
            }
        ],
        styles: { tableStyle: { fontSize: 8 } },
        defaultStyle: { fontSize: 10 }
    };

    const pdfDocGenerator = pdfMake.createPdf(docDefinition);
    pdfDocGenerator.getBlob((blob) => {
        document.getElementById('overlay').style.display = 'none';
        const blobUrl = URL.createObjectURL(blob);
        openPdfModal(blobUrl, blob); // Відкриваємо вікно для підпису
    });
}

// Функція відкриття модалки
function openPdfModal(pdfUrl, pdfBlob) {
    const modal = document.getElementById('pdf-preview-modal');
    const iframe = document.getElementById('pdf-iframe');
    const signBtn = document.getElementById('btn-sign-act'); // Твоя кнопка "Підписати КЕП"
    const cancelBtn = document.querySelector('.close-pdf-modal'); // Кнопка "Скасувати"

    // Показуємо PDF в модалці
    iframe.src = pdfUrl;
    modal.style.display = 'flex'; // або 'block'

    // Якщо натиснули "Скасувати"
    cancelBtn.onclick = function() {
        modal.style.display = 'none';
        URL.revokeObjectURL(pdfUrl); // Очищаємо пам'ять
    };

    // ЯКЩО НАТИСНУЛИ "ПІДПИСАТИ КЕП"
    signBtn.onclick = function() {
        
        // Змінюємо текст кнопки, щоб користувач не клікав двічі
        const originalText = signBtn.innerText;
        signBtn.innerText = 'Відправка на сервер...';
        signBtn.disabled = true;

        // Пакуємо наш PDF-файл для відправки
        let formData = new FormData();
        formData.append('act_pdf', pdfBlob, 'act_readings.pdf');
        
        // Якщо треба передати ID контрагента чи договору з JS, розкоментуй і додай:
        // formData.append('id_contract', myContractIdVariable); 
        // formData.append('id_counteragent', myCounteragentIdVariable);

        // Відправляємо файл на наш новий API
        fetch('/api/save_act_pdf.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Закриваємо модалку
                modal.style.display = 'none';
                URL.revokeObjectURL(pdfUrl);
                
                // ВАЖЛИВО: Робимо редірект на сторінку підпису, 
                // передаючи ID збереженого акту та параметр doctype=act
                window.location.href = `index.php?page=SigningDocs&id=${data.doc_id}&doctype=act`;
            } else {
                // Якщо помилка — повертаємо кнопку як було
                signBtn.innerText = originalText;
                signBtn.disabled = false;
                
                if (typeof showAlert === 'function') {
                    showAlert('Помилка збереження: ' + data.error, 'error');
                } else {
                    alert('Помилка збереження: ' + data.error);
                }
            }
        })
        .catch(error => {
            console.error('Помилка fetch:', error);
            signBtn.innerText = originalText;
            signBtn.disabled = false;
            
            if (typeof showAlert === 'function') {
                showAlert('Помилка з\'єднання з сервером', 'error');
            }
        });
    };
}

function closePdfModal() {
    document.getElementById('pdf-preview-modal').style.display = 'none';
    document.getElementById('pdf-iframe').src = '';
}

/**
 * Зберігає згенерований PDF акт у базу даних та перенаправляє на підпис
 * @param {Object} docDefinition - структура PDF для pdfMake
 * @param {Blob} pdfBlob - готовий файл (якщо він вже згенерований)
 */
function saveActAndRedirect(docDefinition, pdfBlob) {
    const signBtn = document.getElementById('btn-sign-act');
    const originalText = signBtn.innerText;

    // Блокуємо кнопку, щоб уникнути дублів при повільному інтернеті
    signBtn.innerText = 'Збереження...';
    signBtn.disabled = true;

    // Якщо ми передали docDefinition, але не передали Blob - генеруємо його
    const processUpload = (blob) => {
        let formData = new FormData();
        formData.append('act_pdf', blob, 'act_readings.pdf');
        
        // Відправляємо на бекенд
        fetch('/api/save_act_pdf.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Закриваємо модалку перед редіректом (опціонально)
                if (typeof closePdfModal === 'function') closePdfModal();
                
                // Перекидаємо на сторінку підписання
                // Важливо: додаємо doctype=act, щоб SigningDocs.php знав, з якою таблицею працювати
                window.location.href = `index.php?page=SigningDocs&id=${data.doc_id}&doctype=act`;
            } else {
                throw new Error(data.error || 'Невідома помилка сервера');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            signBtn.innerText = originalText;
            signBtn.disabled = false;
            
            if (typeof showAlert === 'function') {
                showAlert('Помилка збереження акту: ' + error.message, 'error');
            } else {
                alert('Помилка збереження акту: ' + error.message);
            }
        });
    };

    if (pdfBlob) {
        processUpload(pdfBlob);
    } else {
        pdfMake.createPdf(docDefinition).getBlob(processUpload);
    }
}

// Виклик усередині твоєї openPdfModal тепер виглядатиме так:
// signBtn.onclick = function() { saveActAndRedirect(null, pdfBlob); };