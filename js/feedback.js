if (typeof uploadedFiles === 'undefined') { var uploadedFiles = []; }

function initQuillEditor() {
    var container = document.getElementById('editor-container');
    if (!container) return;

    // Якщо всередині вже є редактор — видаляємо його ознаки, щоб перестворити
    if (container.classList.contains('ql-container')) {
        return; 
    }

    // Примусово обнуляємо глобальну змінну перед ініціалізацією
    window.quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Введіть текст повідомлення...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
}
function handleFileSelect(event) {
    var input = event.target;
    var list = document.getElementById('attachedFilesList');
    if (!input || !input.files) return;

    Array.from(input.files).forEach(function(file) {
        if (uploadedFiles.some(f => f.name === file.name)) return;
        uploadedFiles.push(file);
        var chip = document.createElement('div');
        chip.className = 'file-chip';
        var safeName = file.name.replace(/'/g, "\\'");
        chip.innerHTML = file.name + ' <span onclick="removeFile(\'' + safeName + '\', this)">&times;</span>';
        list.appendChild(chip);
    });
    input.value = ''; 
}

function removeFile(fileName, element) {
    uploadedFiles = uploadedFiles.filter(f => f.name !== fileName);
    if (element && element.parentElement) element.parentElement.remove();
}

function sendFeedback(event) {
    event.preventDefault();
    var subjectInput = document.getElementById('feedbackSubject');
    var content = window.quill ? window.quill.root.innerHTML : '';

    // Твої кастомні стилі помилок
    if (!subjectInput.value.trim()) {
        if (typeof applyErrorStyle === 'function') {
            return applyErrorStyle(subjectInput, "Вкажіть тему повідомлення");
        } else {
            return alert("Вкажіть тему повідомлення");
        }
    }
    
    if (window.quill && window.quill.getText().trim() === "") {
        return showAlert("Поле повідомлення не може бути порожнім.", 'warning', 'Увага');
    }

    var formData = new FormData();
    formData.append('action', 'send_feedback');
    formData.append('subject', subjectInput.value);
    formData.append('message', content);
    uploadedFiles.forEach(function(file) { formData.append('files[]', file); });

    fetch('/api/feedback_handler.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.status === 'success') {
            // Використовуємо твій showAlert
            showAlert(res.message, 'success', 'Успіх');
            uploadedFiles = [];
            if (window.refreshActiveContent) window.refreshActiveContent();
        } else {
            showAlert(res.message || "Помилка сервера", 'error');
        }
    })
    .catch(function(err) {
        console.error(err);
        showAlert("Не вдалося надіслати повідомлення.", 'error');
    });
}

// Запуск ініціалізації
initQuillEditor();