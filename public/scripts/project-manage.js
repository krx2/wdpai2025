// Project Manage Page JavaScript
// Handle collapsible usage section and form submission

document.addEventListener('DOMContentLoaded', () => {
    // Toggle usage section (Uwaga)
    const usageToggle = document.querySelector('.usage-toggle');
    const usageContent = document.querySelector('.usage-content');
    
    if (usageToggle && usageContent) {
        usageToggle.addEventListener('click', () => {
            usageToggle.classList.toggle('open');
            usageContent.classList.toggle('open');
        });
    }
    
    // Tab switching (desktop)
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active from all tabs
            tabBtns.forEach(t => t.classList.remove('active'));
            // Add active to clicked tab
            btn.classList.add('active');
            
            // TODO: Load content based on selected tab
            console.log('Tab clicked:', btn.textContent);
        });
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
    const titleInput = document.getElementById('title');
    const subtitleInput = document.getElementById('subtitle');
    
    if (titleInput) {
        titleInput.addEventListener('input', updateCharCounters);
    }
    
    if (subtitleInput) {
        subtitleInput.addEventListener('input', updateCharCounters);
    }
    
    // Podgląd obrazka
    const imageUrlInput = document.getElementById('image_url');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageUrlInput && imagePreview) {
        imageUrlInput.addEventListener('blur', () => {
            const url = imageUrlInput.value.trim();
            if (url) {
                // Sprawdź czy URL jest poprawny
                const img = new Image();
                img.onload = () => {
                    imagePreview.src = url;
                };
                img.onerror = () => {
                    console.error('Invalid image URL');
                    // Możesz dodać komunikat dla użytkownika
                };
                img.src = url;
            }
        });
    }
    
    // Obsługa formularza edycji
    const editForm = document.getElementById('projectEditForm');
    
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = editForm.querySelector('.btn-submit-form');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            const messageDiv = document.getElementById('updateMessage');
            
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
                // Pobierz project_id z URL
                const pathParts = window.location.pathname.split('/');
                const projectId = pathParts[pathParts.length - 1];
                
                // Wyślij formularz
                const formData = new FormData(editForm);
                const response = await fetch(`/projects/update/${projectId}`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Sukces - pokaż komunikat
                    showMessage(data.message || 'Projekt zaktualizowany pomyślnie!', 'success');
                    
                    // Odśwież stronę po 1.5s
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Błąd - pokaż komunikat
                    showMessage(data.error || 'Wystąpił błąd podczas aktualizacji projektu', 'error');
                    
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
    }
    
    // Funkcja pomocnicza do wyświetlania komunikatów
    function showMessage(message, type) {
        const messageDiv = document.getElementById('updateMessage');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `form-message ${type}`;
            messageDiv.classList.remove('hidden');
            
            // Scroll do komunikatu
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Save button status change (mobile sidebar)
    const btnSave = document.querySelector('.btn-save');
    
    if (btnSave) {
        btnSave.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const stageSelect = document.querySelector('.date-select');
            const dateInput = document.querySelector('.date-input');
            
            const statusId = stageSelect.value;
            const deadlineDate = dateInput.value;
            
            console.log('Saving status change:', { statusId, deadlineDate });
            
            // TODO: Implementacja zapisu zmiany statusu
            // const response = await fetch('/projects/update-status', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ 
            //         project_id: projectId,
            //         status_id: statusId, 
            //         deadline_date: deadlineDate 
            //     })
            // });
            
            alert('Funkcja zmiany statusu będzie wkrótce dostępna!');
        });
    }
    
    // Inicjalizuj liczniki przy ładowaniu
    updateCharCounters();
});