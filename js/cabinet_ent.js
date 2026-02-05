/* =========================================
   1. ІНІЦІАЛІЗАЦІЯ (DOM READY)
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    // --- 1.1. Елементи інтерфейсу ---
    const mainContent = document.getElementById('mainContent');
    const menuLinks = document.querySelectorAll('.sidebar a');
    const burger = document.getElementById('burgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const scrollBtn = document.getElementById("scrollToTopBtn");
    const tableContainer = document.querySelector(".table-container");

    // --- 1.2. Навігація меню ---
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('btn-logout')) return;
            e.preventDefault();

            // Активність кнопок
            menuLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');

            // Завантаження контенту
            const pageName = this.innerText.trim();
            
            // Анімація завантаження (опціонально)
            if(mainContent) mainContent.style.opacity = '0.6';

            fetch(`/api/get_content.php?page=${encodeURIComponent(pageName)}`)
                .then(response => response.text())
                .then(data => {
                    mainContent.innerHTML = data;
                    mainContent.style.opacity = '1';

                    //автоматичне прогортання таблиці "Історія розрахунків" праворуч на телфоні
                    const historyTable = document.getElementById('history-container');
                    if (historyTable && window.innerWidth <= 850) {
                        // Невелика затримка, щоб браузер встиг намалювати таблицю
                        setTimeout(() => {
                            historyTable.scrollLeft = historyTable.scrollWidth;
                        }, 100);
                    }
                })
                .catch(error => {
                    console.error('Помилка завантаження:', error);
                    mainContent.innerHTML = "<p>Сталася помилка при завантаженні даних.</p>";
                    mainContent.style.opacity = '1';
                });

            // Закриття меню на мобільному
            if (window.innerWidth <= 850) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            }
        });
    });

    // --- 1.3 Мобільне меню (Бургер) ---
    if (burger) {
        burger.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // --- 1.4. Скрол вгору ---
    document.addEventListener('scroll', function(e) {
        // Якщо скролиться вся сторінка
        if (window.scrollY > 300) {
            if(scrollBtn) scrollBtn.classList.add("show");
        } else {
            if(scrollBtn) scrollBtn.classList.remove("show");
        }
    }, true); // true для перехоплення подій скролу у вкладених елементах (table-container)

    if (scrollBtn) {
        scrollBtn.onclick = function() {
            // Скролимо і таблицю, і body
            const container = document.querySelector(".table-container");
            if (container) container.scrollTo({ top: 0, behavior: "smooth" });
            window.scrollTo({ top: 0, behavior: "smooth" });
        };
    }

    // --- 1.5. Автозавантаження першої сторінки ---
    const activeLink = document.querySelector('.sidebar a.active');
    if (activeLink) activeLink.click();

    // Оновлення балансу в шапці
    if (typeof updateHeaderBalance === 'function') {
        updateHeaderBalance();
    }
});


/* =========================================
   2. НАВІГАЦІЯ ТА API ЗАПИТИ
   ========================================= */
// Оновлення балансу в шапці
function updateHeaderBalance() {
    fetch('/api/get_balance.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            // 1. Оновлення суми
            const balanceEl = document.querySelector('.balance');
            if (balanceEl) {
                balanceEl.innerText = data.balance + ' грн';
                
                // Перевірка на мінус
                // Видалення пробілів і заміна коми на краку
                let numValue = parseFloat(data.balance.replace(/\s/g, '').replace(',', '.'));
                
                if (numValue < 0) {
                    balanceEl.classList.add('negative');
                } else {
                    balanceEl.classList.remove('negative');
                }
            }
            const dateSpan = document.querySelector('.balance-sub span'); 
            
            if (dateSpan) {
                dateSpan.innerText = data.date;
            }
        })
        .catch(error => console.error('Error updating balance:', error));
}

// Зміна року в фільтрі
function changeYear(year) {
    const activeLink = document.querySelector('.sidebar a.active');
    if (!activeLink) return;

    const pageName = activeLink.innerText.trim();
    const mainContent = document.getElementById('mainContent');

    if (mainContent) {
        mainContent.style.opacity = '0.5';
        
        fetch(`/api/get_content.php?page=${encodeURIComponent(pageName)}&year=${year}`)
            .then(response => response.text())
            .then(data => {
                mainContent.innerHTML = data;
                mainContent.style.opacity = '1';
            })
            .catch(error => {
                console.error('Помилка оновлення року:', error);
                mainContent.style.opacity = '1';
            });
    }
}

// Оновлення поточної сторінки (хелпер)
window.refreshActiveContent = function() {
    const activeLink = document.querySelector('.sidebar a.active');
    if (activeLink) activeLink.click();
};


/* =========================================
   3. РОБОТА З ТАБЛИЦЕЮ (ДЕРЕВО)
   ========================================= */
/**
 * Відкриття/Закриття одного рядка по рядку
 */
function toggleTree(row, groupId) {
    const isOpening = !row.classList.contains('open');
    toggleTreeState(row, groupId, isOpening);
}


/**
 * Функція для кнопок "Розгорнути все" / "Згорнути все"
 */
function stepTree(direction) {
    const allTriggers = Array.from(document.querySelectorAll('tr[onclick*="toggleTree"]'));

    if (direction === 1) {
        // === РОЗГОРТАННЯ (ВНИЗ) ===
        const toOpen = allTriggers.filter(row => {
            const isVisible = row.offsetParent !== null; 
            const isClosed = !row.classList.contains('open');
            return isVisible && isClosed;
        });

        toOpen.forEach(row => {
            const groupId = getGroupIdFromOnclick(row);
            if (groupId) toggleTreeState(row, groupId, true);
        });

    } else {
        // === ЗГОРТАННЯ (ВГОРУ) ===
        const openTriggers = allTriggers.filter(r => r.classList.contains('open'));

        // Шукаємо крайні відкриті елементи
        const toClose = openTriggers.filter(parentRow => {
            const groupId = getGroupIdFromOnclick(parentRow);
            if (!groupId) return true;

            const children = document.getElementsByClassName(groupId);
            let hasOpenChildTrigger = false;
            for (let child of children) {
                if (child.hasAttribute('onclick') && child.classList.contains('open')) {
                    hasOpenChildTrigger = true;
                    break;
                }
            }
            return !hasOpenChildTrigger;
        });

        toClose.forEach(row => {
            const groupId = getGroupIdFromOnclick(row);
            if (groupId) toggleTreeState(row, groupId, false);
        });
    }
}


/* =========================================
   ДОПОМІЖНІ ФУНКЦІЇ (СЕРЦЕ ЛОГІКИ)
   ========================================= */

/**
 * Витягує ID групи з атрибуту onclick
 */
function getGroupIdFromOnclick(row) {
    const onClickText = row.getAttribute('onclick');
    if (!onClickText) return null;
    const match = onClickText.match(/['"]([^'"]+)['"]/);
    return match ? match[1] : null;
}

/**
 * forceOpen: true (відкрити), false (закрити)
 */
function toggleTreeState(row, groupId, forceOpen) {
    // 1. Змінюємо стан самого рядка
    if (forceOpen) {
        row.classList.add('open');
    } else {
        row.classList.remove('open');
    }

    // 2. Шукаємо рядки нижче
    const children = document.getElementsByClassName(groupId);
    
    Array.from(children).forEach(child => {
        if (forceOpen) {
            // показати класи нижче
            child.classList.add('show');
        } else {
            // сховати класи нижче
            child.classList.remove('show');
            if (child.classList.contains('open')) {
                child.classList.remove('open'); 
                
                const childGroupId = getGroupIdFromOnclick(child);
                if (childGroupId) {
                    toggleTreeState(child, childGroupId, false); 
                }
            }
        }
        child.style.display = ''; 
    });
}
/* =========================================
   4. ЛОГІКА ПЕРЕДАЧІ ПОКАЗНИКІВ
   ========================================= */
function handleMeterInput(input, prevValue, diffSpanId, addressIdx, contractIdx) {
    // Валідація вводу
    let val = input.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
    
    // Захист від двох крапок
    const dots = val.split('.');
    if (dots.length > 2) val = dots[0] + '.' + dots.slice(1).join('');
    
    input.value = val;
    input.classList.remove('error', 'valid');
    
    const errorIcon = input.nextElementSibling;
    const tooltip = errorIcon ? errorIcon.querySelector('.error-tooltip') : null;
    const diffSpan = document.getElementById(diffSpanId);
    
    let currentDiff = 0;

    if (val === '') {
        if (diffSpan) {
            diffSpan.innerText = "0,000";
            diffSpan.style.color = "#3C9ADC";
        }
    } else {
        const currentNum = parseFloat(val);
        const prevNum = parseFloat(prevValue);
        let errorMessage = "";

        if (currentNum < 0) {
            input.classList.add('error');
            errorMessage = "Число не може бути від'ємним";
        } else if (currentNum < prevNum) {
            input.classList.add('error');
            errorMessage = "Показник менший за попередній";
        } else {
            input.classList.add('valid');
        }

        if (tooltip && errorMessage) tooltip.innerText = errorMessage;

        // Рахуємо різницю
        currentDiff = currentNum - prevNum;
        
        if (diffSpan) {
            diffSpan.innerText = currentDiff.toFixed(3).replace('.', ',');
            diffSpan.style.color = (currentDiff < 0) ? "#e74c3c" : "#3C9ADC";
        }
    }

    // Перерахунок загальних сум
    recalculateTotals(addressIdx, contractIdx);
}

function recalculateTotals(addressIdx, contractIdx) {
    // 1. Сума по АДРЕСІ
    const addressInputs = document.querySelectorAll(`.input-reading.address-group-${addressIdx}`);
    let addressSum = 0;

    addressInputs.forEach(inp => {
        let currentVal = parseFloat(inp.value.replace(',', '.'));
        let prevVal = parseFloat(inp.dataset.prev);

        if (!isNaN(currentVal) && !isNaN(prevVal)) {
            addressSum += (currentVal - prevVal);
        }
    });

    const addrSpan = document.getElementById(`sum_address_${addressIdx}`);
    if (addrSpan) {
        addrSpan.innerText = addressSum.toFixed(3).replace('.', ',');
        addrSpan.style.color = (addressSum < 0) ? "#e74c3c" : "#555"; 
    }

    // 2. Сума по ДОГОВОРУ
    const contractSubTotals = document.querySelectorAll(`.sub-total-val.contract-group-${contractIdx}`);
    let contractSum = 0;

    contractSubTotals.forEach(span => {
        const val = parseFloat(span.innerText.replace(',', '.'));
        if (!isNaN(val)) {
            contractSum += val;
        }
    });

    const contractSpan = document.getElementById(`sum_contract_${contractIdx}`);
    if (contractSpan) {
        contractSpan.innerText = contractSum.toFixed(3).replace('.', ',');
        contractSpan.style.color = (contractSum < 0) ? "#e74c3c" : "#000";
    }
}

// Форматування при втраті фокусу (додає .000)
function formatOnBlur(input) {
    let val = input.value;
    if (val === '') return;
    val = val.replace(',', '.');
    const num = parseFloat(val);
    if (!isNaN(num)) {
        input.value = num.toFixed(3);
    }
}

// Збереження даних
function saveReadings() {
    const inputs = document.querySelectorAll('.input-reading');
    const dataToSend = [];
    let hasError = false;

    inputs.forEach(input => {
        if (input.value.trim() === "") return;

        if (input.classList.contains('error')) {
            hasError = true;
            return; 
        }

        let currentVal = input.value.replace(',', '.');
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
            cnt_last:       prevVal,
            cnt_current:    currentVal
        });
    });

    if (hasError) {
        alert("У вас є помилкові (червоні) поля. Виправте їх перед збереженням.");
        return;
    }

    if (dataToSend.length === 0) {
        alert("Ви не ввели жодного коректного показника.");
        return;
    }

    const btn = document.querySelector('.btn-save');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "Збереження...";

    fetch('api/save_meters.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSend)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Показники успішно збережено!');
            // Очищення форми
            inputs.forEach(inp => { if(inp.value !== "") inp.value = ""; });
            document.querySelectorAll('.diff-val, .sub-total-val, .total-val').forEach(el => {
                 el.innerText = "0,000"; el.style.color = ""; 
            });
            document.querySelectorAll('.input-reading').forEach(inp => inp.classList.remove('valid'));
        } else {
            alert('Помилка сервера: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Помилка мережі або скрипта.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerText = originalText;
    });
}