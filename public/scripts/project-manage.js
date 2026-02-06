// Project Manage Page JavaScript
// Handle collapsible usage section, status change form, and project edit form

document.addEventListener('DOMContentLoaded', () => {
    // ================================================================
    // TOGGLE USAGE SECTION (Uwaga)
    // ================================================================
    const usageToggle = document.querySelector('.usage-toggle');
    const usageContent = document.querySelector('.usage-content');
    
    if (usageToggle && usageContent) {
        usageToggle.addEventListener('click', () => {
            usageToggle.classList.toggle('open');
            usageContent.classList.toggle('open');
        });
    }
    
    // ================================================================
    // TAB SWITCHING (Desktop only)
    // ================================================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            console.log('Tab clicked:', btn.textContent);
        });
    });
    
    // ================================================================
    // STATUS CHANGE FORM HANDLING
    // ================================================================
    const statusForm = document.getElementById('statusChangeForm');
    
    if (statusForm) {
        statusForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = statusForm.querySelector('.btn-save');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            const messageDiv = document.getElementById('statusMessage');
            
            // Pobierz dane z formularza
            const statusId = document.getElementById('status_id').value;
            const deadlineDate = document.getElementById('deadline_date').value;
            const notes = document.getElementById('notes').value;
            
            // Walidacja
            if (!statusId) {
                showStatusMessage('Wybierz status', 'error');
                return;
            }
            
            // Pokaż loader
            submitBtn.disabled = true;
            if (btnText) btnText.classList.add('hidden');
            if (btnLoader) btnLoader.classList.remove('hidden');
            if (messageDiv) messageDiv.classList.add('hidden');
            
            try {
                // Wyślij żądanie zmiany statusu
                const formData = new FormData();
                formData.append('status_id', statusId);
                formData.append('deadline_date', deadlineDate);
                formData.append('notes', notes);
                
                const response = await fetch(`/projects/update-status/${PROJECT_ID}`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Sukces - zaktualizuj UI
                    showStatusMessage(data.message || 'Status zaktualizowany pomyślnie!', 'success');
                    
                    // Zaktualizuj wyświetlany status
                    if (data.currentStatus) {
                        const statusDisplay = document.getElementById('currentStatusDisplay');
                        if (statusDisplay) {
                            statusDisplay.textContent = data.currentStatus.status_name;
                            statusDisplay.style.color = data.currentStatus.status_color;
                        }
                        
                        // Zaktualizuj badge w statystykach
                        const statBadges = document.querySelectorAll('.stat-card-value');
                        statBadges.forEach(badge => {
                            if (badge.style.color) {
                                badge.textContent = data.currentStatus.status_name;
                                badge.style.color = data.currentStatus.status_color;
                            }
                        });
                    }
                    
                    // Wyczyść notatki
                    document.getElementById('notes').value = '';
                    
                    // Przeładuj stronę po 2 sekundach aby pokazać nowy wpis w historii
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Błąd
                    showStatusMessage(data.error || 'Wystąpił błąd podczas aktualizacji statusu', 'error');
                    
                    // Przywróć przycisk
                    submitBtn.disabled = false;
                    if (btnText) btnText.classList.remove('hidden');
                    if (btnLoader) btnLoader.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                showStatusMessage('Wystąpił błąd połączenia. Spróbuj ponownie.', 'error');
                
                // Przywróć przycisk
                submitBtn.disabled = false;
                if (btnText) btnText.classList.remove('hidden');
                if (btnLoader) btnLoader.classList.add('hidden');
            }
        });
    }
    
    // ================================================================
    // CHARACTER COUNTERS
    // ================================================================
    function updateCharCounters() {
        const titleInput = document.getElementById('title');
        const subtitleInput = document.getElementById('subtitle');
        const titleCounter = document.getElementById('titleCounter');
        const subtitleCounter = document.getElementById('subtitleCounter');
        
        if (titleInput && titleCounter) {
            titleCounter.textContent = titleInput.value.length;
            titleCounter.style.color = titleInput.value.length > 180 ? '#dc2626' : '#666666';
        }
        
        if (subtitleInput && subtitleCounter) {
            subtitleCounter.textContent = subtitleInput.value.length;
            subtitleCounter.style.color = subtitleInput.value.length > 235 ? '#dc2626' : '#666666';
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
    
    // ================================================================
    // IMAGE PREVIEW
    // ================================================================
    const imageUrlInput = document.getElementById('image_url');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageUrlInput && imagePreview) {
        imageUrlInput.addEventListener('blur', () => {
            const url = imageUrlInput.value.trim();
            if (url) {
                const img = new Image();
                img.onload = () => {
                    imagePreview.src = url;
                };
                img.onerror = () => {
                    console.error('Invalid image URL');
                };
                img.src = url;
            }
        });
    }
    
    // ================================================================
    // PROJECT EDIT FORM HANDLING
    // ================================================================
    const editForm = document.getElementById('projectEditForm');
    
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = editForm.querySelector('.btn-submit-form');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            const messageDiv = document.getElementById('updateMessage');
            
            // Walidacja
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
                const formData = new FormData(editForm);
                const response = await fetch(`/projects/update/${PROJECT_ID}`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    showMessage(data.message || 'Projekt zaktualizowany pomyślnie!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.error || 'Wystąpił błąd podczas aktualizacji projektu', 'error');
                    submitBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoader.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Wystąpił błąd połączenia. Spróbuj ponownie.', 'error');
                submitBtn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
            }
        });
    }
    
    // ================================================================
    // HELPER FUNCTIONS
    // ================================================================
    function showStatusMessage(message, type) {
        const messageDiv = document.getElementById('statusMessage');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `form-message ${type}`;
            messageDiv.classList.remove('hidden');
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    function showMessage(message, type) {
        const messageDiv = document.getElementById('updateMessage');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `form-message ${type}`;
            messageDiv.classList.remove('hidden');
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Inicjalizuj liczniki przy ładowaniu
    updateCharCounters();
});