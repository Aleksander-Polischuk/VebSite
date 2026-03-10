/**
 * Функція для розгортання/згортання дерева в таблиці
 */

function getGroupIdFromOnclick(element) {
    if (!element) return null;
    const onclickStr = element.getAttribute('onclick');
    if (!onclickStr) return null;
    const match = onclickStr.match(/toggleTree\(this,\s*['"]([^'"]+)['"]\)/);
    return match ? match[1] : null;
}

function toggleTree(element, groupId) {
    // 1. Знаходимо рядок TR та його батьківську таблицю
    const row = element.closest('tr');
    if (!row) return;
    
    const table = row.closest('table'); // Шукаємо тільки в поточній таблиці
    if (!table) return;

    const isOpening = !row.classList.contains('open');
    
    // 2. Змінюємо стан стрілки
    if (isOpening) {
        row.classList.add('open');
    } else {
        row.classList.remove('open');
    }

    if (isOpening) {
        // ВІДКРИТТЯ (Шукаємо класи тільки в поточній таблиці)
        const directChildren = table.getElementsByClassName(groupId);
        Array.from(directChildren).forEach(child => {
            child.classList.add('show');
        });
    } else {
        // ЗАКРИТТЯ (Шукаємо класи тільки в поточній таблиці)
        const descendants = table.querySelectorAll(`tr[class*="${groupId}"]`);
        
        descendants.forEach(el => {
            if (el !== row) {
                el.classList.remove('show');
                el.classList.remove('open');
                el.style.display = ''; 
            }
        });
    }
}

function stepTree(direction) {
    // Беремо ТІЛЬКИ ті рядки, які зараз видимі на екрані
    // (у прихованих вкладок offsetParent дорівнює null)
    const allTriggers = Array.from(document.querySelectorAll('tr[onclick*="toggleTree"]'))
        .filter(row => row.offsetParent !== null);

    if (direction === 1) {
        // --- РОЗГОРТАННЯ ПО ОДНОМУ РІВНЮ ---
        const toOpen = allTriggers.filter(row => {
            const isVisible = row.classList.contains('show') || row.classList.contains('parent-row');
            const isClosed = !row.classList.contains('open');
            return isVisible && isClosed;
        });

        toOpen.forEach(row => {
            const gid = getGroupIdFromOnclick(row);
            if (gid) toggleTree(row, gid);
        });
    } else {
        // --- ЗГОРТАННЯ ПО ОДНОМУ РІВНЮ ---
        const openTriggers = allTriggers.filter(r => r.classList.contains('open'));
        
        const toClose = openTriggers.filter(parentRow => {
            const gid = getGroupIdFromOnclick(parentRow);
            if (!gid) return true;

            const table = parentRow.closest('table');
            if (!table) return true;

            // Шукаємо відкриті елементи тільки в поточній таблиці
            const children = table.getElementsByClassName(gid);
            let hasOpenChildTrigger = false;
            
            for (let child of children) {
                if (child.classList.contains('open')) {
                    hasOpenChildTrigger = true;
                    break;
                }
            }
            return !hasOpenChildTrigger;
        });

        toClose.forEach(row => {
            const gid = getGroupIdFromOnclick(row);
            if (gid) toggleTree(row, gid);
        });
    }
}

function toggleTreeDeep(element, parentId) {
    // 1. Спочатку перемикаємо сам період
    if (typeof toggleTree === 'function') {
        toggleTree(element, parentId);
    }

    const table = element.closest('table'); // Ізолюємо пошук поточною таблицею
    if (!table) return;

    const isOpen = element.classList.contains('open');
    
    // 2. Знаходимо всі групи, які належать до цього періоду тільки в цій таблиці
    const subGroups = table.querySelectorAll(`.sub-parent.${parentId}`);
    
    subGroups.forEach(group => {
        const onclickAttr = group.getAttribute('onclick');
        const groupIdMatch = onclickAttr ? onclickAttr.match(/'([^']+)'/) : null;
        
        if (groupIdMatch && groupIdMatch[1]) {
            const groupId = groupIdMatch[1];
            
            if (isOpen) {
                group.classList.add('open');
                table.querySelectorAll(`.${groupId}`).forEach(row => {
                    row.classList.add('show');
                });
            } else {
                group.classList.remove('open');
                table.querySelectorAll(`.${groupId}`).forEach(row => {
                    row.classList.remove('show');
                });
            }
        }
    });
}