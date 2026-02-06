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
    // 1. Знаходимо рядок TR
    const row = element.closest('tr');
    if (!row) return;

    const isOpening = !row.classList.contains('open');
    
    // 2. Змінюємо стан стрілки
    if (isOpening) {
        row.classList.add('open');
    } else {
        row.classList.remove('open');
    }

    if (isOpening) {
        // ВІДКРИТТЯ
        const directChildren = document.getElementsByClassName(groupId);
        Array.from(directChildren).forEach(child => {
            child.classList.add('show');
        });
    } else {
        // ЗАКРИТТЯ
        const descendants = document.querySelectorAll(`tr[class*="${groupId}"]`);
        
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
    // Отримуємо всі рядки, які мають можливість перемикання
    const allTriggers = Array.from(document.querySelectorAll('tr[onclick*="toggleTree"]'));

    if (direction === 1) {
        // --- РОЗГОРТАННЯ ПО ОДНОМУ РІВНЮ ---
        const toOpen = allTriggers.filter(row => {
            const isVisible = row.classList.contains('show') || row.classList.contains('parent-row');
            const isClosed = !row.classList.contains('open');
            return isVisible && isClosed;
        });

        toOpen.forEach(row => {
            const gid = getGroupIdFromOnclick(row);
            if (gid) toggleTree(row, gid); // Використовуємо нашу робочу функцію
        });
    } else {
        // --- ЗГОРТАННЯ ПО ОДНОМУ РІВНЮ ---
        const openTriggers = allTriggers.filter(r => r.classList.contains('open'));
        
        const toClose = openTriggers.filter(parentRow => {
            const gid = getGroupIdFromOnclick(parentRow);
            if (!gid) return true;

            const children = document.getElementsByClassName(gid);
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
