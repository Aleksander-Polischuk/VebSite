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

    let subConfirmText = 'Зберегти ці дані? Формування наступного акту буде доступне лише в наступному місяці!';
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


/* =========================================
 * Функція для підтвердження та формування акту передачі показників
=========================================*/
function confirmGenerateAct() {
    const inputs = document.querySelectorAll('.input-reading');
    const contracts = {};
    let validationErrors = []; 

    inputs.forEach(input => {
        const cName = input.dataset.contractName || 'Без договору';
        const mName = input.dataset.counterName || 'Невідомий лічильник';
        const prevVal = input.dataset.prev || '0.000'; 
        const val = input.value.trim();

        if (!contracts[cName]) {
            contracts[cName] = { total: 0, filled: 0, emptyMeters: [], data: [] };
        }

        contracts[cName].total++;

        if (val !== '') {
            const currentNum = parseFloat(val.replace(/,/g, '.')) || 0;
            const prevNum = parseFloat(prevVal.replace(/,/g, '.')) || 0;
            const diff = currentNum - prevNum;
            const maxVol = parseFloat(input.dataset.maxVol) || 0;

            let hasError = false;

            if (currentNum < 0) {
                validationErrors.push(`[${cName}] <b>${mName}</b>: показник не може бути від'ємним.`);
                hasError = true;
            } 
            else if (currentNum < prevNum) {
                validationErrors.push(`[${cName}] <b>${mName}</b>: поточний показник (${currentNum}) менший за попередній (${prevNum}).`);
                hasError = true;
            } 
            else if (maxVol > 0 && diff > maxVol) {
                validationErrors.push(`[${cName}] <b>${mName}</b>: різниця <b>${diff.toFixed(3)} м³</b> перевищує допустимий ліміт (макс. ${maxVol} м³).`);
                hasError = true;
            }

            if (!hasError) {
                contracts[cName].filled++;
                contracts[cName].data.push({
                    contractName: cName,
                    idContract: input.dataset.contractId || input.dataset.contract || 0,
                    idAccount: input.dataset.account || 0,     
                    idService: input.dataset.service || 0,     
                    idCounter: input.dataset.counter || 0,    
                    counteragent: input.dataset.counteragent || "Організація",
                    meterName: mName,
                    prevValue: prevVal,
                    currValue: val,
                    objectName: input.dataset.objectName || '---', 
                    addressName: input.dataset.addressName || '---',
                    meterMark: input.dataset.meterMark || '---',
                    meterNum: input.dataset.meterNum || '---'
                });
            }
        } else {
            contracts[cName].emptyMeters.push(mName);
        }
    });

    if (validationErrors.length > 0) {
        const errorHtml = `
            Виправте наступні помилки перед формуванням акту:
            <br><br>
            <div style="text-align: left; background: #fff3f3; padding: 12px; border-left: 4px solid #dc3545; border-radius: 4px; font-size: 13px; max-height: 30vh; overflow-y: auto;">
                ${validationErrors.join('<br><br>')}
            </div>
        `;
        if (typeof showAlert === 'function') {
            showAlert(errorHtml, "error", "Помилка валідації показників");
        } else {
            alert("Помилки:\n" + validationErrors.join('\n').replace(/<[^>]*>?/gm, ''));
        }
        return; 
    }

    let hasPartiallyFilled = false;
    let completelyEmptyContracts = 0;
    let totalContracts = Object.keys(contracts).length;
    let errorMessage = "";
    
    let contractsQueue = [];
    let finalDataToGenerate = []; 

    for (const [cName, stats] of Object.entries(contracts)) {
        if (stats.filled > 0 && stats.filled < stats.total) {
            hasPartiallyFilled = true;
            errorMessage += `<br><br><b style="color:#e74c3c;">${cName}</b><br>Пропущено: ${stats.emptyMeters.join(', ')}`;
        } 
        else if (stats.filled === stats.total) {
            finalDataToGenerate = finalDataToGenerate.concat(stats.data);
            contractsQueue.push(stats.data); // Зберігаємо як окремий договір
        } 
        else if (stats.filled === 0) {
            completelyEmptyContracts++;
        }
    }

    if (completelyEmptyContracts === totalContracts) {
        if (typeof showAlert === 'function') {
            showAlert("Будь ласка, введіть показники для формування акту.", "error", "Помилка");
        } else {
            alert("Будь ласка, введіть показники.");
        }
        return;
    }

    if (hasPartiallyFilled) {
        const msg = "Для формування акту необхідно заповнити <b>всі</b> лічильники в межах договору!" + errorMessage;
        if (typeof showAlert === 'function') {
            showAlert(msg, "warning", "Увага! Незаповнені поля");
        } else {
            alert("Заповніть всі лічильники для розпочатого договору!");
        }
        return;
    }

    let summaryHtml = `
        <div style="text-align: left; max-height: 45vh; overflow-y: auto; margin-top: 15px; border: 1px solid #e3e6f0; border-radius: 8px; background: #fff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="background: #f8f9fc; position: sticky; top: 0; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);">
                    <tr>
                        <th style="padding: 12px 10px; text-align: left; color: #4e73df; border-bottom: 2px solid #e3e6f0;">Договір / Адреса</th>
                        <th style="padding: 12px 10px; text-align: left; color: #4e73df; border-bottom: 2px solid #e3e6f0;">Лічильник</th>
                        <th style="padding: 12px 10px; text-align: center; color: #4e73df; border-bottom: 2px solid #e3e6f0;">Об'єм</th>
                    </tr>
                </thead>
                <tbody>
    `;

    let totalVolume = 0;

    finalDataToGenerate.forEach((item, index) => {
        let prev = parseFloat(item.prevValue.replace(',', '.')) || 0;
        let curr = parseFloat(item.currValue.replace(',', '.')) || 0;
        let diff = (curr - prev);
        totalVolume += diff;
        let rowBg = index % 2 === 0 ? '#ffffff' : '#fcfcfc'; 

        summaryHtml += `
            <tr style="background: ${rowBg}; border-bottom: 1px solid #f1f1f1;">
                <td style="padding: 10px; vertical-align: top;">
                    <strong style="color: #333;">${item.contractName}</strong><br>
                    <span style="font-size: 11px; color: #888;">${item.addressName}</span>
                </td>
                <td style="padding: 10px; vertical-align: middle; color: #555;">
                    ${item.meterName}
                </td>
                <td style="padding: 10px; vertical-align: middle; text-align: center; font-weight: bold; color: #1cc88a; font-size: 14px;">
                    +${diff.toFixed(3)} м³
                </td>
            </tr>
        `;
    });

    summaryHtml += `
                </tbody>
                <tfoot style="background: #f8f9fc;">
                    <tr>
                        <td colspan="2" style="padding: 12px 10px; text-align: right; font-weight: bold; color: #333;">Всього передано:</td>
                        <td style="padding: 12px 10px; text-align: center; font-weight: bold; color: #1cc88a; font-size: 15px;">
                            ${totalVolume.toFixed(3)} м³
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p style="margin-top: 15px; font-size: 14px; text-align: center; color: #555;">
            Ви підтверджуєте правильність введених даних та готові сформувати акт(и)?
        </p>
    `;

    if (typeof showAlert === 'function') {
        showAlert(
            summaryHtml, 
            "info", 
            "Перевірка даних перед збереженням", 
            [
                {
                    text: "Так, сформувати та підписати КЕП", 
                    className: "btn-alert-ok",
                    onClick: () => {
                        window.generatedDocIds = [];
                        window.globalContractsQueue = contractsQueue; 
                        processNextContractInQueue();                 
                    }
                },
                {
                    text: "Скасувати", 
                    className: "btn-alert-cancel",
                    onClick: () => {}
                }
            ]
        );
    } else {
        if (confirm("Сформувати акт(и) передачі показників?")) {
            window.globalContractsQueue = contractsQueue;
            processNextContractInQueue();
        }
    }
}

// ---------------------------------------------------------
// Бере наступний договір і генерує для нього ПДФ
// ---------------------------------------------------------
function processNextContractInQueue() {
    if (!window.globalContractsQueue || window.globalContractsQueue.length === 0) {
        const overlay = document.getElementById('overlay');
        if (overlay) overlay.style.display = 'none';
        
        if (window.generatedDocIds && window.generatedDocIds.length > 0) {
            const ids = window.generatedDocIds.join(',');
            const signUrl = `/api/content/SigningDocs.php?ids=${ids}&doctype=act`;
            
            // Відкриваємо вкладку підпису
            window.open(signUrl, '_blank');
            
            window.generatedDocIds = [];

            // оновлення кабінету
            if (typeof window.updateDocumentBadge === 'function') {
                window.updateDocumentBadge();
            }

            if (typeof window.refreshActiveContent === 'function') {
                window.refreshActiveContent();
            } else {
                window.location.reload();
            }
        }
        return;
    }

    // Беремо перший договір з черги
    const currentContractData = window.globalContractsQueue.shift();
    generatePdfClientSide(currentContractData);
}

// ---------------------------------------------------------
// ГЕНЕРАЦІЯ ПДФ
// ---------------------------------------------------------
function generatePdfClientSide(data) {
    const currentDateStr = new Date().toLocaleDateString('uk-UA');
    const overlay = document.getElementById('overlay');
    if (overlay) overlay.style.display = 'block';
    
    const contractName = data[0]?.contractName || "___";
    const counteragentName = data[0]?.counteragent || "Організація";

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

    data.forEach((item, index) => {
        let prev = parseFloat(item.prevValue.replace(',', '.')) || 0;
        let curr = parseFloat(item.currValue.replace(',', '.')) || 0;
        let diff = (curr - prev).toFixed(3);

        tableBody.push([
            { text: (index + 1).toString(), alignment: 'center' },    //номер   
            { text: item.counteragent, alignment: 'left' },           //контрагент 
            { text: item.addressName, alignment: 'left' },            //адреса  
            { text: item.meterMark, alignment: 'center' },            //марка лічильника  
            { text: item.meterNum, alignment: 'center' },             //заводський  
            { text: item.prevValue, alignment: 'center' },            //попередні  
            { text: item.currValue, alignment: 'center', bold: true },//поточні
            { text: diff, alignment: 'center' },                      //різниця
            { text: '0.000', alignment: 'center' }                    //в т.ч. населення
        ]);
    });

    const docDefinition = {
        pageOrientation: 'portrait', pageSize: 'A4', pageMargins: [30, 40, 30, 40], 
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
                    widths: ['2%', '20%', '25%', '8%', '10%', '9%', '9%', '8%', '9%'], //розміри кожної колонки у таблиці
                    body: tableBody
                }
            },
            {
                text: '"Рахунок виставлений згідно звіту, зобов’язуємось оплатити протягом 3-х днів з дня його отримання."',
                italics: true, margin: [0, 30, 0, 0]
            }
        ],
        styles: { tableStyle: { fontSize: 8 } },
        defaultStyle: { fontSize: 10 }
    };

    const pdfDocGenerator = pdfMake.createPdf(docDefinition);
    pdfDocGenerator.getBlob((blob) => {
        const idContract = data[0]?.idContract || 0; 
        saveAndSignActDirectly(blob, idContract, data);
    });
}

// ---------------------------------------------------------
// Зберігає на сервер
// ---------------------------------------------------------
function saveAndSignActDirectly(pdfBlob, idContract = 0, metersData = []) {
    const formData = new FormData();
    formData.append('act_pdf', pdfBlob, 'act_readings.pdf');
    formData.append('id_contract', idContract); 
    formData.append('meters_data', JSON.stringify(metersData));
    
    if (typeof setStatus === 'function') setStatus('Збереження акту на сервері...');

    fetch('/api/save_act_pdf.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        return JSON.parse(text);
    })
    .then(data => {
        if (data.success && data.doc_id) {
            if (!window.generatedDocIds) window.generatedDocIds = [];
            window.generatedDocIds.push(data.doc_id);
            
            //наступний договір
            processNextContractInQueue();
        } else {
            throw new Error(data.error || 'Сервер не повернув ID документа');
        }
    })
    .catch(error => {
        const overlay = document.getElementById('overlay');
        if (overlay) overlay.style.display = 'none';
        alert('Помилка збереження: ' + error.message);
    });
}