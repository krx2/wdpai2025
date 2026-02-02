// Modal functions
function openModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.add('active');
    document.body.classList.add('modal-open');
    
    // Focus na pierwszym polu
    setTimeout(() => {
        document.getElementById('title').focus();
    }, 100);
}

function closeModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.remove('active');
    document.body.classList.remove('modal-open');
    
    // Resetuj formularz
    document.getElementById('projectForm').reset();
    document.getElementById('formMessage').classList.add('hidden');
    updateCharCounters();
}

// Zamknij modal klawiszem ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('projectModal');
        if (modal.classList.contains('active')) {
            closeModal();
        }
    }
});

// Liczniki znaków
function updateCharCounters() {
    const titleInput = document.getElementById('title');
    const subtitleInput = document.getElementById('subtitle');
    const titleCounter = document.getElementById('titleCounter');
    const subtitleCounter = document.getElementById('subtitleCounter');
    
    if (titleInput && titleCounter) {
        titleCounter.textContent = titleInput.value.length;
        
        // Zmień kolor jeśli blisko limitu
        if (titleInput.value.length > 180) {
            titleCounter.style.color = '#dc2626';
        } else {
            titleCounter.style.color = '#666666';
        }
    }
    
    if (subtitleInput && subtitleCounter) {
        subtitleCounter.textContent = subtitleInput.value.length;
        
        if (subtitleInput.value.length > 235) {
            subtitleCounter.style.color = '#dc2626';
        } else {
            subtitleCounter.style.color = '#666666';
        }
    }
}

// Event listenery dla liczników
document.addEventListener('DOMContentLoaded', () => {
    const titleInput = document.getElementById('title');
    const subtitleInput = document.getElementById('subtitle');
    
    if (titleInput) {
        titleInput.addEventListener('input', updateCharCounters);
    }
    
    if (subtitleInput) {
        subtitleInput.addEventListener('input', updateCharCounters);
    }
    
    // Inicjalizuj liczniki
    updateCharCounters();
});

// Obsługa formularza
document.getElementById('projectForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('.btn-submit');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    const messageDiv = document.getElementById('formMessage');
    
    // Walidacja po stronie klienta
    const title = document.getElementById('title').value.trim();
    if (!title) {
        showMessage('Tytuł projektu jest wymagany', 'error');
        return;
    }
    
    if (title.length > 200) {
        showMessage('Tytuł nie może być dłuższy niż 200 znaków', 'error');
        return;
    }
    
    // Pokaż loader
    submitBtn.disabled = true;
    btnText.classList.add('hidden');
    btnLoader.classList.remove('hidden');
    messageDiv.classList.add('hidden');
    
    try {
        // Wyślij formularz
        const formData = new FormData(form);
        const response = await fetch('/projects/create', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Sukces - usuń draft i przeładuj stronę
            localStorage.removeItem('projectDraft');
            showMessage('Projekt został utworzony!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            // Błąd - pokaż komunikat
            const data = await response.json();
            showMessage(data.error || 'Wystąpił błąd podczas tworzenia projektu', 'error');
            
            // Przywróć przycisk
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('Wystąpił błąd połączenia. Spróbuj ponownie.', 'error');
        
        // Przywróć przycisk
        submitBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnLoader.classList.add('hidden');
    }
});

// Funkcja pomocnicza do wyświetlania komunikatów
function showMessage(message, type) {
    const messageDiv = document.getElementById('formMessage');
    messageDiv.textContent = message;
    messageDiv.className = `form-message ${type}`;
    messageDiv.classList.remove('hidden');
    
    // Scroll do komunikatu
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Podgląd obrazka (opcjonalnie)
const imageUrlInput = document.getElementById('image_url');
if (imageUrlInput) {
    imageUrlInput.addEventListener('blur', () => {
        const url = imageUrlInput.value.trim();
        if (url) {
            // Można dodać podgląd obrazka w przyszłości
            console.log('Image URL:', url);
        }
    });
}

// Auto-save do localStorage (opcjonalnie)
let autoSaveTimeout;
const formInputs = document.querySelectorAll('#projectForm input, #projectForm textarea');

formInputs.forEach(input => {
    input.addEventListener('input', () => {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            saveFormDraft();
        }, 1000);
    });
});

function saveFormDraft() {
    const formData = {
        title: document.getElementById('title').value,
        subtitle: document.getElementById('subtitle').value,
        image_url: document.getElementById('image_url').value,
        completion_date: document.getElementById('completion_date').value,
        description: document.getElementById('description').value,
        timestamp: Date.now()
    };
    
    localStorage.setItem('projectDraft', JSON.stringify(formData));
    console.log('Draft saved');
}

function loadFormDraft() {
    const draft = localStorage.getItem('projectDraft');
    if (draft) {
        const data = JSON.parse(draft);
        
        // Sprawdź czy draft nie jest starszy niż 1 dzień
        const oneDayAgo = Date.now() - (24 * 60 * 60 * 1000);
        if (data.timestamp > oneDayAgo) {
            // Załaduj dane tylko jeśli użytkownik się zgodzi
            if (confirm('Znaleziono zapisaną wersję roboczą. Czy chcesz ją przywrócić?')) {
                document.getElementById('title').value = data.title || '';
                document.getElementById('subtitle').value = data.subtitle || '';
                document.getElementById('image_url').value = data.image_url || '';
                document.getElementById('completion_date').value = data.completion_date || '';
                document.getElementById('description').value = data.description || '';
                updateCharCounters();
            }
        } else {
            // Usuń stary draft
            localStorage.removeItem('projectDraft');
        }
    }
}

// Załaduj draft przy otwieraniu modala (jeśli istnieje)
const originalOpenModal = openModal;
openModal = function() {
    originalOpenModal();
    loadFormDraft();
};