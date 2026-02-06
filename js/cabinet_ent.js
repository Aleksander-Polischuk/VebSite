/* =========================================
   1. ІНІЦІАЛІЗАЦІЯ ТА НАВІГАЦІЯ
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('mainContent');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const scrollBtn = document.getElementById("scrollToTopBtn");

    // Навігація меню
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('btn-logout')) return;
            e.preventDefault();

            document.querySelectorAll('.sidebar a').forEach(item => item.classList.remove('active'));
            this.classList.add('active');

            const pageName = this.innerText.trim();
            if(mainContent) mainContent.style.opacity = '0.6';

            fetch(`/api/get_content.php?page=${encodeURIComponent(pageName)}`)
                .then(r => r.text())
                .then(data => {
                    mainContent.innerHTML = data;
                    mainContent.style.opacity = '1';

                    const historyTable = document.getElementById('history-container');
                    if (historyTable && window.innerWidth <= 850) {
                        setTimeout(() => historyTable.scrollLeft = historyTable.scrollWidth, 100);
                    }
                })
                .catch(err => {
                    console.error('Помилка завантаження:', err);
                    mainContent.innerHTML = "<p>Сталася помилка при завантаженні даних.</p>";
                    mainContent.style.opacity = '1';
                });

            if (window.innerWidth <= 850) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            }
        });
    });

    // Мобільне меню та Скрол
    document.getElementById('burgerBtn')?.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    document.addEventListener('scroll', () => {
        if(scrollBtn) scrollBtn.classList.toggle("show", window.scrollY > 300);
    }, true);

    if (scrollBtn) {
        scrollBtn.onclick = () => {
            document.querySelector(".table-container")?.scrollTo({ top: 0, behavior: "smooth" });
            window.scrollTo({ top: 0, behavior: "smooth" });
        };
    }

    document.querySelector('.sidebar a.active')?.click();
    if (typeof updateHeaderBalance === 'function') updateHeaderBalance();
});

/* =========================================
   2. Оновлення балансу та зміна року
   ========================================= */
function updateHeaderBalance() {
    fetch('/api/get_balance.php')
        .then(r => r.json())
        .then(data => {
            const balanceEl = document.querySelector('.balance');
            if (balanceEl) {
                balanceEl.innerText = data.balance + ' грн';
                let numValue = parseFloat(data.balance.replace(/\s/g, '').replace(',', '.'));
                balanceEl.classList.toggle('negative', numValue < 0);
            }
            const dateSpan = document.querySelector('.balance-sub span');
            if (dateSpan) dateSpan.innerText = data.date;
        })
        .catch(err => console.error('Error updating balance:', err));
}

function changeYear(year) {
    const activeLink = document.querySelector('.sidebar a.active');
    const mainContent = document.getElementById('mainContent');
    if (!activeLink || !mainContent) return;

    mainContent.style.opacity = '0.5';
    fetch(`/api/get_content.php?page=${encodeURIComponent(activeLink.innerText.trim())}&year=${year}`)
        .then(r => r.text())
        .then(data => {
            mainContent.innerHTML = data;
            mainContent.style.opacity = '1';
        })
        .catch(err => {
            console.error('Помилка оновлення року:', err);
            mainContent.style.opacity = '1';
        });
}

window.refreshActiveContent = () => document.querySelector('.sidebar a.active')?.click();

/* =========================================
   3. ЛОГІКА ВВОДУ і рахування різниці
   ========================================= */
function handleMeterInput(input, prevValue, diffSpanId, addressIdx, contractIdx) {
    let val = input.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
    const dots = val.split('.');
    input.value = dots.length > 2 ? dots[0] + '.' + dots.slice(1).join('') : val;

    input.classList.remove('error', 'valid');
    const diffSpan = document.getElementById(diffSpanId);
    let errorMessage = "";

    if (input.value !== '') {
        const currentNum = parseFloat(input.value);
        const prevNum = parseFloat(prevValue);

        if (currentNum < 0) errorMessage = "Число не може бути від'ємним";
        else if (currentNum < prevNum) errorMessage = "Показник менший за попередній";
        
        input.classList.add(errorMessage ? 'error' : 'valid');
        
        if (diffSpan) {
            const diff = currentNum - prevNum;
            diffSpan.innerText = diff.toFixed(3).replace('.', ',');
            diffSpan.style.color = diff < 0 ? "#e74c3c" : "#3C9ADC";
        }
    } else if (diffSpan) {
        diffSpan.innerText = "0,000";
        diffSpan.style.color = "#3C9ADC";
    }

    const tooltip = input.nextElementSibling?.querySelector('.error-tooltip');
    if (tooltip) tooltip.innerText = errorMessage;

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
        const prevVal = parseFloat(input.dataset.prev);

        if (isNaN(parseFloat(currentVal))) {
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

    // 1. Перевірки валідації (коротший запис)
    if (hasError) {
        return typeof showAlert === 'function' 
            ? showAlert("Виправте помилкові (червоні) поля перед збереженням.", 'error', 'Помилка валідації') 
            : alert("У вас є помилкові поля.");
    }

    if (!dataToSend.length) {
        return typeof showAlert === 'function' 
            ? showAlert("Ви не ввели жодного показника.", 'warning', 'Увага') 
            : alert("Ви не ввели жодного показника.");
    }

    // 2. Генерація HTML через map (більш читабельно)
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
    subText.innerText = 'Зберегти ці дані?';
    subText.style.display = 'block';
    document.getElementById('customAlertOverlay').style.display = 'flex';

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