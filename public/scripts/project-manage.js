// Project Manage Page JavaScript
// Handle collapsible usage section and other interactions

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
    
    // Save button (mobile sidebar)
    const btnSave = document.querySelector('.btn-save');
    
    if (btnSave) {
        btnSave.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const stageSelect = document.querySelector('.date-select');
            const dateInput = document.querySelector('.date-input');
            
            const stage = stageSelect.value;
            const date = dateInput.value;
            
            console.log('Saving:', { stage, date });
            
            // TODO: Send to backend
            // Example:
            // const response = await fetch('/projects/update-status', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ stage, date })
            // });
            
            // Show success message
            alert('Zapisano zmiany!');
        });
    }
    
    // Save button (desktop)
    const btnSaveDesktop = document.querySelector('.btn-save-desktop');
    
    if (btnSaveDesktop) {
        btnSaveDesktop.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const stageSelect = document.querySelector('.date-select-desktop');
            const dateInput = document.querySelector('.date-input-desktop');
            
            const stage = stageSelect.value;
            const date = dateInput.value;
            
            console.log('Saving (desktop):', { stage, date });
            
            // TODO: Send to backend
            alert('Zapisano zmiany!');
        });
    }
    
    // Edit button
    const btnEdit = document.querySelector('.btn-edit');
    
    if (btnEdit) {
        btnEdit.addEventListener('click', () => {
            console.log('Edit history clicked');
            // TODO: Open edit modal or navigate to edit page
        });
    }
    
    // Show all statistics button
    const btnShowAll = document.querySelector('.btn-show-all');
    
    if (btnShowAll) {
        btnShowAll.addEventListener('click', () => {
            console.log('Show all statistics clicked');
            // TODO: Navigate to statistics page or expand view
        });
    }
});