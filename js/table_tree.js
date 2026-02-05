/**
 * Функція для розгортання/згортання дерева в таблиці
 * Працює в парі з cabinet_ent.css
 */
function toggleTree(row, groupId) {
    row.classList.toggle('open');
    const isOpening = row.classList.contains('open');

    // 2. Знаходимо всі дочірні елементи
    const children = document.querySelectorAll('.' + groupId);

    children.forEach(child => {
        if (isOpening) {
            // ВІДКРИТТЯ: Додаємо клас show, щоб CSS показав рядок
            child.classList.add('show');
        } else {
            // ЗАКРИТТЯ: Прибираємо клас show
            child.classList.remove('show');

            // 3. якщо закриваємо договір -> закрити і місяці
            if (child.classList.contains('sub-parent')) {
                child.classList.remove('open');
                const match = child.getAttribute('onclick')?.match(/'([^']+)'/);
                
                if (match && match[1]) {
                    const subGroupId = match[1];
                    document.querySelectorAll('.' + subGroupId).forEach(subChild => {
                        subChild.classList.remove('show');
                    });
                }
            }
        }
    });
}