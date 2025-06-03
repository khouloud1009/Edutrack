/**
 * EduTrack Admin Dashboard JavaScript
 * Professional functionality and interactions
 */

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    initializeCharts();
    initializeTooltips();
    initializeAnimations();
    initializeFormValidations();
    initializeDataTables();
    initializeNotifications();
});

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // Add loading states
    addLoadingStates();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-dismiss alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert.fade.show');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

/**
 * Initialize charts if Chart.js is available
 */
function initializeCharts() {
    if (typeof Chart !== 'undefined') {
        // Students by level chart
        const studentChartCtx = document.getElementById('studentChart');
        if (studentChartCtx) {
            new Chart(studentChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['L1', 'L2', 'L3', 'M1', 'M2'],
                    datasets: [{
                        data: window.chartData?.students || [0, 0, 0, 0, 0],
                        backgroundColor: [
                            '#2563eb',
                            '#6366f1',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }
        
        // Grades distribution chart
        const gradesChartCtx = document.getElementById('gradesChart');
        if (gradesChartCtx) {
            new Chart(gradesChartCtx, {
                type: 'bar',
                data: {
                    labels: ['0-5', '6-10', '11-15', '16-20'],
                    datasets: [{
                        label: 'Nombre d\'étudiants',
                        data: window.chartData?.grades || [0, 0, 0, 0],
                        backgroundColor: 'rgba(37, 99, 235, 0.8)',
                        borderColor: '#2563eb',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Add tooltips to action buttons
    const actionButtons = document.querySelectorAll('.btn[data-action]');
    actionButtons.forEach(btn => {
        if (!btn.hasAttribute('title')) {
            const action = btn.getAttribute('data-action');
            const titles = {
                'edit': 'Modifier',
                'delete': 'Supprimer',
                'view': 'Voir détails',
                'download': 'Télécharger'
            };
            btn.setAttribute('title', titles[action] || 'Action');
            btn.setAttribute('data-bs-toggle', 'tooltip');
        }
    });
}

/**
 * Initialize animations
 */
function initializeAnimations() {
    // Animate statistics cards
    const statsCards = document.querySelectorAll('.stats-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                
                // Animate numbers
                const numberElement = entry.target.querySelector('.stats-number');
                if (numberElement) {
                    animateNumber(numberElement);
                }
            }
        });
    }, { threshold: 0.1 });
    
    statsCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

/**
 * Animate number counting
 */
function animateNumber(element) {
    const finalNumber = parseInt(element.textContent);
    const duration = 1000;
    const startTime = Date.now();
    
    function updateNumber() {
        const currentTime = Date.now();
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const currentNumber = Math.floor(finalNumber * easeOutQuart(progress));
        element.textContent = currentNumber;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    element.textContent = '0';
    requestAnimationFrame(updateNumber);
}

/**
 * Easing function
 */
function easeOutQuart(t) {
    return 1 - (--t) * t * t * t;
}

/**
 * Initialize form validations
 */
function initializeFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            
            form.classList.add('was-validated');
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });
    });
}

/**
 * Validate individual field
 */
function validateField(field) {
    const isValid = field.checkValidity();
    
    field.classList.remove('is-valid', 'is-invalid');
    field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    
    // Custom validation messages
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback && !isValid) {
        if (field.validity.valueMissing) {
            feedback.textContent = 'Ce champ est requis.';
        } else if (field.validity.typeMismatch) {
            feedback.textContent = 'Format invalide.';
        } else if (field.validity.patternMismatch) {
            feedback.textContent = 'Format invalide.';
        }
    }
}

/**
 * Initialize data tables
 */
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add search functionality
        addTableSearch(table);
        
        // Add sorting
        addTableSorting(table);
        
        // Add row actions
        addRowActions(table);
    });
}

/**
 * Add search functionality to table
 */
function addTableSearch(table) {
    const searchInput = table.parentNode.querySelector('.table-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
        
        updateTableInfo(table);
    });
}

/**
 * Add sorting functionality to table
 */
function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sort]');
    
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            const direction = this.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
            
            // Reset other headers
            headers.forEach(h => h.removeAttribute('data-direction'));
            this.setAttribute('data-direction', direction);
            
            sortTable(table, column, direction);
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-value="${column}"]`)?.textContent || a.cells[column]?.textContent || '';
        const bValue = b.querySelector(`[data-value="${column}"]`)?.textContent || b.cells[column]?.textContent || '';
        
        const comparison = aValue.localeCompare(bValue, 'fr', { numeric: true });
        return direction === 'asc' ? comparison : -comparison;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Add row actions
 */
function addRowActions(table) {
    const actionBtns = table.querySelectorAll('[data-action]');
    
    actionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const action = this.getAttribute('data-action');
            const row = this.closest('tr');
            
            switch(action) {
                case 'delete':
                    handleDelete(this, row);
                    break;
                case 'edit':
                    handleEdit(this, row);
                    break;
                case 'view':
                    handleView(this, row);
                    break;
            }
        });
    });
}

/**
 * Handle delete action
 */
function handleDelete(button, row) {
    const itemName = row.querySelector('td').textContent;
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer "${itemName}" ?`)) {
        // Add loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Submit delete form or make AJAX call
        const form = button.closest('form');
        if (form) {
            form.submit();
        }
    }
}

/**
 * Handle edit action
 */
function handleEdit(button, row) {
    const modal = document.querySelector('#editModal');
    if (modal) {
        // Populate modal with row data
        populateEditModal(modal, row);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * Handle view action
 */
function handleView(button, row) {
    const modal = document.querySelector('#viewModal');
    if (modal) {
        // Populate modal with row data
        populateViewModal(modal, row);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * Populate edit modal with data
 */
function populateEditModal(modal, row) {
    const cells = row.querySelectorAll('td');
    const inputs = modal.querySelectorAll('input, select, textarea');
    
    inputs.forEach((input, index) => {
        if (cells[index]) {
            input.value = cells[index].textContent.trim();
        }
    });
}

/**
 * Populate view modal with data
 */
function populateViewModal(modal, row) {
    const cells = row.querySelectorAll('td');
    const displays = modal.querySelectorAll('[data-field]');
    
    displays.forEach(display => {
        const field = display.getAttribute('data-field');
        const cellIndex = parseInt(field);
        if (cells[cellIndex]) {
            display.textContent = cells[cellIndex].textContent.trim();
        }
    });
}

/**
 * Update table info
 */
function updateTableInfo(table) {
    const infoElement = table.parentNode.querySelector('.table-info');
    if (!infoElement) return;
    
    const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])').length;
    const totalRows = table.querySelectorAll('tbody tr').length;
    
    infoElement.textContent = `Affichage de ${visibleRows} sur ${totalRows} entrées`;
}

/**
 * Initialize notifications
 */
function initializeNotifications() {
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }, 4000);
        }
    });
}

/**
 * Add loading states
 */
function addLoadingStates() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Chargement...';
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
    });
}

/**
 * Utility function to show notifications
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/**
 * Utility function to format numbers
 */
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

/**
 * Utility function to format dates
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('fr-FR').format(new Date(date));
}

/**
 * Export functions for global use
 */
window.EduTrack = {
    showNotification,
    formatNumber,
    formatDate,
    animateNumber
};
