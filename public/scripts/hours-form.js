// Hours Logging Form JavaScript
// Obsługa formularza dodawania godzin pracy

document.addEventListener('DOMContentLoaded', () => {
    const hoursForm = document.getElementById('hoursForm');
    
    if (!hoursForm) return;
    
    // Obsługa wysyłania formularza
    hoursForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitBtn = hoursForm.querySelector('.btn-submit-hours');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        const messageDiv = document.getElementById('hoursMessage');
        
        // Walidacja po stronie klienta
        const projectId = document.getElementById('project_id').value;
        const hours = parseFloat(document.getElementById('hours').value);
        
        if (!projectId) {
            showMessage('Wybierz projekt', 'error');
            return;
        }
        
        if (!hours || hours <= 0 || hours > 24) {
            showMessage('Podaj prawidłową liczbę godzin (0.5-24)', 'error');
            return;
        }
        
        // Pokaż loader
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoader.classList.remove('hidden');
        messageDiv.classList.add('hidden');
        
        try {
            // Wyślij formularz
            const formData = new FormData(hoursForm);
            const response = await fetch('/dashboard/log-hours', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Sukces
                showMessage(data.message || 'Godziny zostały zapisane!', 'success');
                
                // Zaktualizuj dzisiejszą sumę
                const todayTotal = document.getElementById('todayTotal');
                if (todayTotal && data.todayTotalHours !== undefined) {
                    todayTotal.textContent = parseFloat(data.todayTotalHours).toFixed(1) + 'h';
                    
                    // Animacja zmiany
                    todayTotal.style.transform = 'scale(1.2)';
                    todayTotal.style.color = '#10B981';
                    setTimeout(() => {
                        todayTotal.style.transform = 'scale(1)';
                        todayTotal.style.color = '#000000';
                    }, 300);
                }
                
                // Resetuj formularz po 1 sekundzie
                setTimeout(() => {
                    hoursForm.reset();
                    // Ustaw dzisiejszą datę z powrotem
                    document.getElementById('log_date').value = new Date().toISOString().split('T')[0];
                    
                    // Opcjonalnie - przeładuj stronę aby pokazać nowy wpis w liście
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, 1000);
            } else {
                // Błąd
                showMessage(data.error || 'Wystąpił błąd podczas zapisywania godzin', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Wystąpił błąd połączenia. Spróbuj ponownie.', 'error');
        } finally {
            // Przywróć przycisk
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
        }
    });
    
    // Funkcja pomocnicza do wyświetlania komunikatów
    function showMessage(message, type) {
        const messageDiv = document.getElementById('hoursMessage');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = `form-message ${type}`;
            messageDiv.classList.remove('hidden');
            
            // Scroll do komunikatu
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Automatyczne ukrycie sukcesu po 5 sekundach
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.classList.add('hidden');
                }, 5000);
            }
        }
    }
    
    // Walidacja godzin - zaokrąglanie do 0.5
    const hoursInput = document.getElementById('hours');
    if (hoursInput) {
        hoursInput.addEventListener('blur', () => {
            let value = parseFloat(hoursInput.value);
            if (!isNaN(value)) {
                // Zaokrąglij do najbliższej połowy godziny
                value = Math.round(value * 2) / 2;
                // Ogranicz do 0.5-24
                value = Math.max(0.5, Math.min(24, value));
                hoursInput.value = value;
            }
        });
    }
    
    // Podgląd wybranego projektu
    const projectSelect = document.getElementById('project_id');
    if (projectSelect) {
        projectSelect.addEventListener('change', () => {
            const selectedOption = projectSelect.options[projectSelect.selectedIndex];
            if (selectedOption.value) {
                // Możesz dodać dodatkowe informacje o projekcie
                console.log('Wybrany projekt:', selectedOption.text);
            }
        });
    }
    
    // Ustawienie maksymalnej daty na dzisiaj
    const logDateInput = document.getElementById('log_date');
    if (logDateInput) {
        const today = new Date().toISOString().split('T')[0];
        logDateInput.setAttribute('max', today);
        
        // Walidacja - nie pozwól na daty w przyszłości
        logDateInput.addEventListener('change', () => {
            const selectedDate = new Date(logDateInput.value);
            const todayDate = new Date(today);
            
            if (selectedDate > todayDate) {
                showMessage('Nie możesz dodać godzin dla przyszłych dni', 'error');
                logDateInput.value = today;
            }
        });
    }
});