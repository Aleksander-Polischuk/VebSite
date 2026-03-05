    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Введіть текст або скопіюйте з Word...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Підготовка HTML перед відправкою форми
    document.getElementById('faqForm').onsubmit = function() {
        var htmlContent = quill.root.innerHTML;
        if (htmlContent === '<p><br></p>') htmlContent = '';
        document.getElementById('content_data').value = htmlContent;
    };

    // Функція активації режиму "Редагування"
    function editFAQ(id, question, sortOrder) {
        // Отримуємо контент з прихованого div
        var rawContent = document.getElementById('raw_content_' + id).innerText || document.getElementById('raw_content_' + id).textContent;
        
        // Заповнюємо форму
        document.getElementById('faq_id').value = id;
        document.getElementById('form-action').value = 'edit_faq';
        document.getElementById('faq_question').value = question;
        document.getElementById('faq_sort_order').value = sortOrder;
        
        // Вставляємо контент у Quill
        quill.clipboard.dangerouslyPasteHTML(rawContent);
        
        // Змінюємо інтерфейс
        document.getElementById('form-title').innerText = 'Редагувати питання ID: ' + id;
        document.getElementById('btn-save').innerText = 'Зберегти зміни';
        document.getElementById('btn-cancel').style.display = 'inline-block';
        
        // Скролимо сторінку до форми
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Функція скидання форми (повернення в режим "Додавання")
    function resetForm() {
        document.getElementById('faq_id').value = '0';
        document.getElementById('form-action').value = 'add_faq';
        document.getElementById('faq_question').value = '';
        document.getElementById('faq_sort_order').value = '10';
        
        quill.setContents([]); // Очищаємо редактор
        
        document.getElementById('form-title').innerText = 'Додати нове питання в FAQ';
        document.getElementById('btn-save').innerText = 'Додати питання';
        document.getElementById('btn-cancel').style.display = 'none';
    }
    
    // Функція для миттєвої зміни статусу
    function toggleActive(id, isChecked) {
        var formData = new FormData();
        formData.append('action', 'toggle_active');
        formData.append('faq_id', id);
        formData.append('is_active', isChecked ? 1 : 0);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showAlert('Помилка при збереженні статусу!', 'error');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            showAlert('Помилка з\'єднання з сервером!', 'error');
        });
    }