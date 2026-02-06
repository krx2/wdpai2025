// Monthly Report JavaScript
// Handle report generation, rate inputs, and calculations

document.addEventListener('DOMContentLoaded', () => {
    const generateBtn = document.getElementById('generateReportBtn');
    const printBtn = document.getElementById('printReportBtn');
    const yearSelect = document.getElementById('reportYear');
    const monthSelect = document.getElementById('reportMonth');
    const reportResults = document.getElementById('reportResults');
    const reportLoading = document.getElementById('reportLoading');
    const reportError = document.getElementById('reportError');
    const reportEmpty = document.getElementById('reportEmpty');
    const reportTable = document.getElementById('reportTable');
    const reportTableBody = document.getElementById('reportTableBody');
    const reportPeriod = document.getElementById('reportPeriod');
    
    const currentMonth = new Date().getMonth() + 1;
    monthSelect.value = currentMonth;
    
    let reportData = [];
    
    const monthNames = [
        'Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
        'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'
    ];

    // ================================================================
    // NUMBER FORMATTERS (Polish notation)
    // ================================================================
    const plnFormatter = new Intl.NumberFormat('pl-PL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const hoursFormatter = new Intl.NumberFormat('pl-PL', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    });

    function formatPLN(value) {
        return plnFormatter.format(value) + ' PLN';
    }

    function formatHours(value) {
        return hoursFormatter.format(value);
    }

    // ================================================================
    // GENERATE REPORT
    // ================================================================
    if (generateBtn) {
        generateBtn.addEventListener('click', async () => {
            const year = yearSelect.value;
            const month = monthSelect.value;

            reportResults.classList.remove('hidden');
            showState('loading');
            reportPeriod.textContent = `${monthNames[month - 1]} ${year}`;

            setTimeout(() => {
                reportResults.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);

            try {
                const response = await fetch(`/report/data?year=${year}&month=${month}`);
                if (!response.ok) throw new Error('Failed to fetch report data');

                const data = await response.json();

                if (!data.projects || data.projects.length === 0) {
                    showState('empty');
                    return;
                }

                reportData = data.projects;
                renderReportTable();
                showState('table');

            } catch (error) {
                console.error(error);
                document.getElementById('reportErrorMessage').textContent =
                    'Wystąpił błąd podczas generowania raportu. Spróbuj ponownie.';
                showState('error');
            }
        });
    }

    // ================================================================
    // RENDER REPORT TABLE
    // ================================================================
    function renderReportTable() {
        reportTableBody.innerHTML = '';
        const savedRates = JSON.parse(localStorage.getItem('projectRates') || '{}');

        reportData.forEach(project => {
            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            nameCell.textContent = project.title;
            row.appendChild(nameCell);

            const hoursCell = document.createElement('td');
            hoursCell.className = 'text-right';
            hoursCell.textContent = formatHours(parseFloat(project.total_hours));
            row.appendChild(hoursCell);

            const rateCell = document.createElement('td');
            rateCell.className = 'text-right';

            const rateInput = document.createElement('input');
            rateInput.type = 'number';
            rateInput.className = 'rate-input';
            rateInput.min = '0';
            rateInput.step = '0.01';
            rateInput.placeholder = '0.00';
            rateInput.value = savedRates[project.project_id] || '';
            rateInput.dataset.projectId = project.project_id;
            rateInput.dataset.hours = project.total_hours;

            rateInput.addEventListener('input', () => {
                updateCalculations();
                saveRates();
            });

            rateCell.appendChild(rateInput);
            row.appendChild(rateCell);

            const valueCell = document.createElement('td');
            valueCell.className = 'text-right value-cell';
            valueCell.dataset.projectId = project.project_id;
            valueCell.textContent = formatPLN(0);
            row.appendChild(valueCell);

            reportTableBody.appendChild(row);
        });

        updateCalculations();
    }

    // ================================================================
    // UPDATE CALCULATIONS
    // ================================================================
    function updateCalculations() {
        let totalHours = 0;
        let totalValue = 0;
        const rateInputs = document.querySelectorAll('.rate-input');

        rateInputs.forEach(input => {
            const hours = parseFloat(input.dataset.hours) || 0;
            const rate = parseFloat(input.value) || 0;
            const value = hours * rate;

            totalHours += hours;
            totalValue += value;

            const valueCell = document.querySelector(`.value-cell[data-project-id="${input.dataset.projectId}"]`);
            if (valueCell) valueCell.textContent = formatPLN(value);
        });

        document.getElementById('totalHours').textContent = formatHours(totalHours);
        document.getElementById('totalValue').textContent = formatPLN(totalValue);
    }

    // ================================================================
    // SAVE RATES
    // ================================================================
    function saveRates() {
        const rates = {};
        document.querySelectorAll('.rate-input').forEach(input => {
            if (input.value) rates[input.dataset.projectId] = input.value;
        });
        localStorage.setItem('projectRates', JSON.stringify(rates));
    }

    // ================================================================
    // SHOW STATE
    // ================================================================
    function showState(state) {
        reportLoading.classList.add('hidden');
        reportError.classList.add('hidden');
        reportEmpty.classList.add('hidden');
        reportTable.classList.add('hidden');

        if (state === 'loading') reportLoading.classList.remove('hidden');
        if (state === 'error') reportError.classList.remove('hidden');
        if (state === 'empty') reportEmpty.classList.remove('hidden');
        if (state === 'table') reportTable.classList.remove('hidden');
    }

    // ================================================================
    // PRINT REPORT
    // ================================================================
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }

    // ================================================================
    // SHORTCUTS
    // ================================================================
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p' && !reportTable.classList.contains('hidden')) {
            e.preventDefault();
            window.print();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
            e.preventDefault();
            generateBtn.click();
        }
    });
});
