/**
 * Professional Interactions and Micro-animations
 * EduTrack Student Portal Enhancement
 */

(function() {
    'use strict';

    // =========================================
    // Utility Functions
    // =========================================

    /**
     * Debounce function to limit function calls
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    }

    /**
     * Throttle function to limit function calls
     */
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Check if element is in viewport
     */
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Add class with animation delay
     */
    function addClassWithDelay(element, className, delay = 0) {
        setTimeout(() => {
            element.classList.add(className);
        }, delay);
    }

    // =========================================
    // Enhanced Navigation Effects
    // =========================================

    class NavigationEnhancer {
        constructor() {
            this.navbar = document.querySelector('.navbar');
            this.navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            this.dropdownItems = document.querySelectorAll('.dropdown-item');
            
            this.init();
        }

        init() {
            this.handleScrollEffects();
            this.enhanceNavLinks();
            this.enhanceDropdowns();
            this.addActiveIndicator();
        }

        handleScrollEffects() {
            if (!this.navbar) return;

            let lastScrollTop = 0;
            const scrollHandler = throttle(() => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Add/remove scrolled class
                if (scrollTop > 50) {
                    this.navbar.classList.add('navbar-scrolled');
                } else {
                    this.navbar.classList.remove('navbar-scrolled');
                }

                // Hide/show navbar on scroll
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    this.navbar.style.transform = 'translateY(-100%)';
                } else {
                    this.navbar.style.transform = 'translateY(0)';
                }

                lastScrollTop = scrollTop;
            }, 10);

            window.addEventListener('scroll', scrollHandler);
        }

        enhanceNavLinks() {
            this.navLinks.forEach(link => {
                // Add ripple effect
                link.addEventListener('click', this.createRipple.bind(this));
                
                // Enhanced hover effects
                link.addEventListener('mouseenter', (e) => {
                    e.target.style.transform = 'translateY(-2px)';
                });
                
                link.addEventListener('mouseleave', (e) => {
                    e.target.style.transform = 'translateY(0)';
                });
            });
        }

        enhanceDropdowns() {
            this.dropdownItems.forEach(item => {
                item.addEventListener('mouseenter', (e) => {
                    e.target.style.transform = 'translateX(5px)';
                    e.target.style.backgroundColor = 'rgba(var(--primary), 0.1)';
                });
                
                item.addEventListener('mouseleave', (e) => {
                    e.target.style.transform = 'translateX(0)';
                    e.target.style.backgroundColor = '';
                });
            });
        }

        addActiveIndicator() {
            const activeLink = document.querySelector('.navbar-nav .nav-link.active');
            if (activeLink) {
                const indicator = document.createElement('div');
                indicator.className = 'nav-active-indicator';
                indicator.style.cssText = `
                    position: absolute;
                    bottom: -2px;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, transparent, white, transparent);
                    border-radius: 2px;
                    animation: nav-indicator-glow 2s ease-in-out infinite alternate;
                `;
                activeLink.style.position = 'relative';
                activeLink.appendChild(indicator);
            }
        }

        createRipple(e) {
            const button = e.currentTarget;
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
            `;
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        }
    }

    // =========================================
    // Enhanced Card Interactions
    // =========================================

    class CardEnhancer {
        constructor() {
            this.cards = document.querySelectorAll('.card');
            this.init();
        }

        init() {
            this.enhanceCards();
            this.addParallaxEffect();
            this.handleIntersectionObserver();
        }

        enhanceCards() {
            this.cards.forEach(card => {
                // Add hover enhancement
                card.addEventListener('mouseenter', this.handleCardHover.bind(this));
                card.addEventListener('mouseleave', this.handleCardLeave.bind(this));
                card.addEventListener('mousemove', this.handleCardMouseMove.bind(this));
                
                // Add click enhancement
                card.addEventListener('click', this.handleCardClick.bind(this));
                
                // Add loading shimmer for new content
                this.addLoadingShimmer(card);
            });
        }

        handleCardHover(e) {
            const card = e.currentTarget;
            card.style.transform = 'translateY(-8px) scale(1.02)';
            card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
            card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            
            // Add glow effect
            this.addGlowEffect(card);
        }

        handleCardLeave(e) {
            const card = e.currentTarget;
            card.style.transform = 'translateY(0) scale(1)';
            card.style.boxShadow = '';
            
            // Remove glow effect
            this.removeGlowEffect(card);
        }

        handleCardMouseMove(e) {
            const card = e.currentTarget;
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `
                translateY(-8px) 
                scale(1.02) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg)
            `;
        }

        handleCardClick(e) {
            const card = e.currentTarget;
            card.classList.add('card-clicked');
            setTimeout(() => card.classList.remove('card-clicked'), 200);
        }

        addGlowEffect(card) {
            if (!card.querySelector('.card-glow')) {
                const glow = document.createElement('div');
                glow.className = 'card-glow';
                glow.style.cssText = `
                    position: absolute;
                    top: -2px;
                    left: -2px;
                    right: -2px;
                    bottom: -2px;
                    background: linear-gradient(45deg, rgba(var(--primary), 0.3), rgba(var(--secondary), 0.3));
                    border-radius: inherit;
                    z-index: -1;
                    filter: blur(8px);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                card.style.position = 'relative';
                card.appendChild(glow);
                
                setTimeout(() => glow.style.opacity = '1', 10);
            }
        }

        removeGlowEffect(card) {
            const glow = card.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '0';
                setTimeout(() => glow.remove(), 300);
            }
        }

        addLoadingShimmer(card) {
            // Add shimmer effect for dynamic content loading
            if (card.dataset.loading === 'true') {
                card.classList.add('loading-shimmer');
                
                // Simulate loading completion
                setTimeout(() => {
                    card.classList.remove('loading-shimmer');
                    card.classList.add('fade-in-up');
                }, Math.random() * 1000 + 500);
            }
        }

        addParallaxEffect() {
            const cards = document.querySelectorAll('.card[data-parallax="true"]');
            
            const handleScroll = throttle(() => {
                cards.forEach(card => {
                    const rect = card.getBoundingClientRect();
                    const speed = card.dataset.parallaxSpeed || 0.5;
                    const yPos = -(rect.top * speed);
                    card.style.transform = `translateY(${yPos}px)`;
                });
            }, 10);
            
            window.addEventListener('scroll', handleScroll);
        }

        handleIntersectionObserver() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('card-visible');
                        
                        // Stagger animation for multiple cards
                        const cards = [...entry.target.parentElement.children];
                        const index = cards.indexOf(entry.target);
                        entry.target.style.animationDelay = `${index * 0.1}s`;
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            this.cards.forEach(card => observer.observe(card));
        }
    }

    // =========================================
    // Enhanced Form Interactions
    // =========================================

    class FormEnhancer {
        constructor() {
            this.forms = document.querySelectorAll('form');
            this.inputs = document.querySelectorAll('.form-control');
            this.buttons = document.querySelectorAll('.btn');
            this.init();
        }

        init() {
            this.enhanceInputs();
            this.enhanceButtons();
            this.addFormValidation();
            this.addProgressiveEnhancement();
        }

        enhanceInputs() {
            this.inputs.forEach(input => {
                // Add floating label effect
                this.addFloatingLabel(input);
                
                // Add focus enhancement
                input.addEventListener('focus', this.handleInputFocus.bind(this));
                input.addEventListener('blur', this.handleInputBlur.bind(this));
                input.addEventListener('input', this.handleInputChange.bind(this));
                
                // Add validation styling
                input.addEventListener('invalid', this.handleInputInvalid.bind(this));
            });
        }

        addFloatingLabel(input) {
            const label = input.closest('.form-group')?.querySelector('.form-label');
            if (label && input.placeholder) {
                const wrapper = document.createElement('div');
                wrapper.className = 'form-floating-enhanced';
                wrapper.style.position = 'relative';
                
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                wrapper.appendChild(label);
                
                label.style.cssText = `
                    position: absolute;
                    top: 50%;
                    left: 1rem;
                    transform: translateY(-50%);
                    background: white;
                    padding: 0 0.5rem;
                    color: var(--text-muted);
                    pointer-events: none;
                    transition: all 0.3s ease;
                    z-index: 1;
                `;
                
                this.updateFloatingLabel(input, label);
            }
        }

        updateFloatingLabel(input, label) {
            const hasValue = input.value.length > 0;
            const isFocused = document.activeElement === input;
            
            if (hasValue || isFocused) {
                label.style.transform = 'translateY(-150%) scale(0.8)';
                label.style.color = 'rgba(var(--primary), 1)';
            } else {
                label.style.transform = 'translateY(-50%) scale(1)';
                label.style.color = 'var(--text-muted)';
            }
        }

        handleInputFocus(e) {
            const input = e.target;
            input.parentElement.classList.add('form-focused');
            
            // Add glow effect
            input.style.boxShadow = '0 0 0 3px rgba(var(--primary), 0.1), 0 0 20px rgba(var(--primary), 0.1)';
            input.style.transform = 'translateY(-2px)';
            
            // Update floating label
            const label = input.parentElement.querySelector('.form-label');
            if (label) this.updateFloatingLabel(input, label);
        }

        handleInputBlur(e) {
            const input = e.target;
            input.parentElement.classList.remove('form-focused');
            
            input.style.boxShadow = '';
            input.style.transform = 'translateY(0)';
            
            // Update floating label
            const label = input.parentElement.querySelector('.form-label');
            if (label) this.updateFloatingLabel(input, label);
        }

        handleInputChange(e) {
            const input = e.target;
            const label = input.parentElement.querySelector('.form-label');
            if (label) this.updateFloatingLabel(input, label);
            
            // Add typing effect
            input.classList.add('form-typing');
            clearTimeout(input.typingTimeout);
            input.typingTimeout = setTimeout(() => {
                input.classList.remove('form-typing');
            }, 1000);
        }

        handleInputInvalid(e) {
            const input = e.target;
            input.classList.add('shake');
            setTimeout(() => input.classList.remove('shake'), 500);
        }

        enhanceButtons() {
            this.buttons.forEach(button => {
                // Add ripple effect
                button.addEventListener('click', this.createButtonRipple.bind(this));
                
                // Add loading state
                this.addLoadingState(button);
                
                // Add hover enhancement
                button.addEventListener('mouseenter', this.handleButtonHover.bind(this));
                button.addEventListener('mouseleave', this.handleButtonLeave.bind(this));
            });
        }

        createButtonRipple(e) {
            const button = e.currentTarget;
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
            `;
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        }

        addLoadingState(button) {
            if (button.type === 'submit') {
                const form = button.closest('form');
                if (form) {
                    form.addEventListener('submit', () => {
                        button.classList.add('btn-loading');
                        button.disabled = true;
                        
                        const originalText = button.innerHTML;
                        button.innerHTML = `
                            <span class="loading-spinner"></span>
                            <span class="ms-2">Chargement...</span>
                        `;
                        
                        // Reset after 5 seconds (fallback)
                        setTimeout(() => {
                            button.classList.remove('btn-loading');
                            button.disabled = false;
                            button.innerHTML = originalText;
                        }, 5000);
                    });
                }
            }
        }

        handleButtonHover(e) {
            const button = e.target;
            if (!button.disabled) {
                button.style.transform = 'translateY(-2px) scale(1.02)';
                button.style.filter = 'brightness(1.1)';
            }
        }

        handleButtonLeave(e) {
            const button = e.target;
            button.style.transform = 'translateY(0) scale(1)';
            button.style.filter = 'brightness(1)';
        }

        addFormValidation() {
            this.forms.forEach(form => {
                form.addEventListener('submit', this.handleFormSubmit.bind(this));
                
                // Real-time validation
                const inputs = form.querySelectorAll('.form-control[required]');
                inputs.forEach(input => {
                    input.addEventListener('blur', () => this.validateField(input));
                    input.addEventListener('input', () => this.clearFieldError(input));
                });
            });
        }

        handleFormSubmit(e) {
            const form = e.target;
            const isValid = this.validateForm(form);
            
            if (!isValid) {
                e.preventDefault();
                this.showFormErrors(form);
            } else {
                this.showFormSuccess(form);
            }
        }

        validateForm(form) {
            let isValid = true;
            const inputs = form.querySelectorAll('.form-control[required]');
            
            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }

        validateField(input) {
            const value = input.value.trim();
            const isRequired = input.hasAttribute('required');
            const type = input.type;
            
            let isValid = true;
            let errorMessage = '';
            
            if (isRequired && !value) {
                isValid = false;
                errorMessage = 'Ce champ est obligatoire';
            } else if (type === 'email' && value && !this.isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Format d\'email invalide';
            } else if (type === 'password' && value && value.length < 6) {
                isValid = false;
                errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
            }
            
            this.updateFieldValidation(input, isValid, errorMessage);
            return isValid;
        }

        updateFieldValidation(input, isValid, errorMessage) {
            const wrapper = input.closest('.form-group') || input.parentElement;
            const existingError = wrapper.querySelector('.field-error');
            
            if (existingError) {
                existingError.remove();
            }
            
            input.classList.remove('is-valid', 'is-invalid');
            
            if (!isValid) {
                input.classList.add('is-invalid');
                
                const errorElement = document.createElement('div');
                errorElement.className = 'field-error text-danger mt-1';
                errorElement.textContent = errorMessage;
                errorElement.style.fontSize = '0.875rem';
                
                wrapper.appendChild(errorElement);
            } else if (input.value) {
                input.classList.add('is-valid');
            }
        }

        clearFieldError(input) {
            const wrapper = input.closest('.form-group') || input.parentElement;
            const existingError = wrapper.querySelector('.field-error');
            
            if (existingError) {
                existingError.remove();
            }
            
            input.classList.remove('is-invalid');
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        showFormErrors(form) {
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 500);
        }

        showFormSuccess(form) {
            form.classList.add('form-success');
            
            // Show success animation
            const successIndicator = document.createElement('div');
            successIndicator.className = 'form-success-indicator';
            successIndicator.innerHTML = '<i class="fas fa-check-circle"></i>';
            successIndicator.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0);
                color: rgba(var(--success), 1);
                font-size: 2rem;
                animation: scaleIn 0.5s ease-out forwards;
                z-index: 1000;
            `;
            
            form.style.position = 'relative';
            form.appendChild(successIndicator);
            
            setTimeout(() => successIndicator.remove(), 2000);
        }

        addProgressiveEnhancement() {
            // Add auto-save functionality
            const inputs = document.querySelectorAll('.form-control[data-autosave="true"]');
            inputs.forEach(input => {
                const debouncedSave = debounce(() => {
                    this.autoSave(input);
                }, 1000);
                
                input.addEventListener('input', debouncedSave);
            });
            
            // Add character counter
            const textareas = document.querySelectorAll('textarea[maxlength]');
            textareas.forEach(textarea => {
                this.addCharacterCounter(textarea);
            });
        }

        autoSave(input) {
            const key = `autosave_${input.name || input.id}`;
            localStorage.setItem(key, input.value);
            
            // Show save indicator
            this.showSaveIndicator(input);
        }

        showSaveIndicator(input) {
            const indicator = document.createElement('div');
            indicator.className = 'save-indicator';
            indicator.innerHTML = '<i class="fas fa-check"></i> Sauvegardé';
            indicator.style.cssText = `
                position: absolute;
                top: -25px;
                right: 0;
                background: rgba(var(--success), 1);
                color: white;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                font-size: 0.75rem;
                opacity: 0;
                animation: fadeInUp 0.3s ease-out forwards;
                z-index: 10;
            `;
            
            const wrapper = input.parentElement;
            wrapper.style.position = 'relative';
            wrapper.appendChild(indicator);
            
            setTimeout(() => {
                indicator.style.animation = 'fadeInUp 0.3s ease-out reverse';
                setTimeout(() => indicator.remove(), 300);
            }, 2000);
        }

        addCharacterCounter(textarea) {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'character-counter';
            counter.style.cssText = `
                text-align: right;
                margin-top: 0.25rem;
                font-size: 0.875rem;
                color: var(--text-muted);
            `;
            
            const updateCounter = () => {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${textarea.value.length}/${maxLength}`;
                
                if (remaining < 20) {
                    counter.style.color = 'rgba(var(--warning), 1)';
                } else if (remaining < 0) {
                    counter.style.color = 'rgba(var(--danger), 1)';
                } else {
                    counter.style.color = 'var(--text-muted)';
                }
            };
            
            textarea.parentElement.appendChild(counter);
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    }

    // =========================================
    // Enhanced Table Interactions
    // =========================================

    class TableEnhancer {
        constructor() {
            this.tables = document.querySelectorAll('.table');
            this.init();
        }

        init() {
            this.enhanceTables();
            this.addSortFunctionality();
            this.addFilterFunctionality();
            this.addRowHoverEffects();
        }

        enhanceTables() {
            this.tables.forEach(table => {
                // Add responsive wrapper
                if (!table.closest('.table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
                
                // Add loading shimmer
                this.addTableLoadingEffect(table);
                
                // Add row animations
                this.addRowAnimations(table);
            });
        }

        addTableLoadingEffect(table) {
            if (table.dataset.loading === 'true') {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }
        }

        addRowAnimations(table) {
            const rows = table.querySelectorAll('tbody tr');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const row = entry.target;
                        const index = [...row.parentElement.children].indexOf(row);
                        
                        setTimeout(() => {
                            row.classList.add('row-visible');
                        }, index * 50);
                    }
                });
            }, { threshold: 0.1 });
            
            rows.forEach(row => {
                row.classList.add('row-hidden');
                observer.observe(row);
            });
        }

        addSortFunctionality() {
            this.tables.forEach(table => {
                const headers = table.querySelectorAll('th[data-sort]');
                
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.style.userSelect = 'none';
                    header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
                    
                    header.addEventListener('click', () => {
                        this.sortTable(table, header);
                    });
                });
            });
        }

        sortTable(table, header) {
            const column = header.dataset.sort;
            const isAscending = !header.classList.contains('sort-desc');
            
            // Remove sort classes from all headers
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
                const icon = th.querySelector('i');
                if (icon) icon.className = 'fas fa-sort text-muted';
            });
            
            // Add sort class to current header
            header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
            const icon = header.querySelector('i');
            if (icon) {
                icon.className = isAscending ? 'fas fa-sort-up text-primary' : 'fas fa-sort-down text-primary';
            }
            
            // Sort rows
            const tbody = table.querySelector('tbody');
            const rows = [...tbody.querySelectorAll('tr')];
            
            rows.sort((a, b) => {
                const aValue = a.querySelector(`[data-value="${column}"]`)?.textContent || a.cells[header.cellIndex]?.textContent || '';
                const bValue = b.querySelector(`[data-value="${column}"]`)?.textContent || b.cells[header.cellIndex]?.textContent || '';
                
                const comparison = aValue.localeCompare(bValue, 'fr', { numeric: true });
                return isAscending ? comparison : -comparison;
            });
            
            // Animate row changes
            rows.forEach((row, index) => {
                row.style.transform = 'translateX(-100%)';
                row.style.opacity = '0';
                
                setTimeout(() => {
                    tbody.appendChild(row);
                    row.style.transform = 'translateX(0)';
                    row.style.opacity = '1';
                }, index * 50);
            });
        }

        addFilterFunctionality() {
            const filterInputs = document.querySelectorAll('.table-filter');
            
            filterInputs.forEach(input => {
                const targetTable = document.querySelector(input.dataset.target);
                if (targetTable) {
                    const debouncedFilter = debounce(() => {
                        this.filterTable(targetTable, input.value);
                    }, 300);
                    
                    input.addEventListener('input', debouncedFilter);
                }
            });
        }

        filterTable(table, searchTerm) {
            const rows = table.querySelectorAll('tbody tr');
            searchTerm = searchTerm.toLowerCase();
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                
                if (shouldShow) {
                    row.style.display = '';
                    row.classList.add('filter-match');
                } else {
                    row.style.display = 'none';
                    row.classList.remove('filter-match');
                }
            });
            
            // Update table statistics
            this.updateTableStats(table);
        }

        updateTableStats(table) {
            const totalRows = table.querySelectorAll('tbody tr').length;
            const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])').length;
            
            const statsElement = table.closest('.table-responsive').querySelector('.table-stats');
            if (statsElement) {
                statsElement.textContent = `Affichage de ${visibleRows} sur ${totalRows} éléments`;
            }
        }

        addRowHoverEffects() {
            this.tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    row.addEventListener('mouseenter', () => {
                        row.style.transform = 'scale(1.01)';
                        row.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                        row.style.zIndex = '1';
                    });
                    
                    row.addEventListener('mouseleave', () => {
                        row.style.transform = 'scale(1)';
                        row.style.boxShadow = '';
                        row.style.zIndex = '';
                    });
                });
            });
        }
    }

    // =========================================
    // Enhanced Alert System
    // =========================================

    class AlertEnhancer {
        constructor() {
            this.alerts = document.querySelectorAll('.alert');
            this.init();
        }

        init() {
            this.enhanceAlerts();
            this.addProgressBars();
            this.addAutoHide();
        }

        enhanceAlerts() {
            this.alerts.forEach(alert => {
                // Add entrance animation
                alert.classList.add('fade-in-up');
                
                // Add close button enhancement
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        this.hideAlert(alert);
                    });
                }
                
                // Add interactive elements
                this.addAlertInteractions(alert);
            });
        }

        addAlertInteractions(alert) {
            alert.addEventListener('mouseenter', () => {
                alert.style.transform = 'translateY(-2px)';
                alert.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
            });
            
            alert.addEventListener('mouseleave', () => {
                alert.style.transform = 'translateY(0)';
                alert.style.boxShadow = '';
            });
        }

        addProgressBars() {
            this.alerts.forEach(alert => {
                if (alert.dataset.timeout) {
                    const timeout = parseInt(alert.dataset.timeout);
                    const progressBar = document.createElement('div');
                    
                    progressBar.style.cssText = `
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        height: 3px;
                        background: currentColor;
                        width: 100%;
                        opacity: 0.7;
                        animation: alert-progress ${timeout}ms linear forwards;
                    `;
                    
                    alert.style.position = 'relative';
                    alert.appendChild(progressBar);
                }
            });
        }

        addAutoHide() {
            this.alerts.forEach(alert => {
                const timeout = parseInt(alert.dataset.timeout) || 5000;
                
                setTimeout(() => {
                    this.hideAlert(alert);
                }, timeout);
            });
        }

        hideAlert(alert) {
            alert.style.animation = 'fadeOutUp 0.3s ease-in forwards';
            
            setTimeout(() => {
                alert.remove();
            }, 300);
        }

        static createAlert(type, message, timeout = 5000) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.setAttribute('role', 'alert');
            alert.dataset.timeout = timeout;
            
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.alert-container') || document.body;
            container.appendChild(alert);
            
            new AlertEnhancer();
            
            return alert;
        }
    }

    // =========================================
    // Performance Optimizations
    // =========================================

    class PerformanceOptimizer {
        constructor() {
            this.init();
        }

        init() {
            this.optimizeImages();
            this.addIntersectionObserver();
            this.enableHardwareAcceleration();
            this.addVisibilityChangeHandler();
        }

        optimizeImages() {
            const images = document.querySelectorAll('img[data-src]');
            
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('fade-in');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        }

        addIntersectionObserver() {
            const elements = document.querySelectorAll('[data-animate]');
            
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const animation = element.dataset.animate;
                        element.classList.add(animation);
                        animationObserver.unobserve(element);
                    }
                });
            }, { threshold: 0.1 });
            
            elements.forEach(el => animationObserver.observe(el));
        }

        enableHardwareAcceleration() {
            const animatedElements = document.querySelectorAll('.card, .btn, .navbar');
            
            animatedElements.forEach(el => {
                el.style.willChange = 'transform';
                el.style.transform = 'translateZ(0)';
            });
        }

        addVisibilityChangeHandler() {
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    // Pause animations when tab is hidden
                    document.body.classList.add('paused');
                } else {
                    // Resume animations when tab is visible
                    document.body.classList.remove('paused');
                }
            });
        }
    }

    // =========================================
    // Enhanced Chart Interactions
    // =========================================

    class ChartEnhancer {
        constructor() {
            this.charts = document.querySelectorAll('canvas');
            this.init();
        }

        init() {
            this.enhanceChartContainers();
            this.addChartAnimations();
            this.addChartInteractions();
        }

        enhanceChartContainers() {
            this.charts.forEach(chart => {
                const container = chart.closest('.chart-container') || chart.parentElement;
                container.classList.add('chart-fade-in');
                
                // Add loading state
                if (chart.dataset.loading === 'true') {
                    this.addChartLoading(container);
                }
            });
        }

        addChartLoading(container) {
            const loader = document.createElement('div');
            loader.className = 'chart-loader';
            loader.innerHTML = `
                <div class="loading-spinner"></div>
                <p>Chargement des données...</p>
            `;
            loader.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                color: var(--text-muted);
            `;
            
            container.style.position = 'relative';
            container.appendChild(loader);
            
            // Simulate loading completion
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 300);
            }, Math.random() * 2000 + 1000);
        }

        addChartAnimations() {
            // Enhance Chart.js animations if available
            if (window.Chart) {
                Chart.defaults.animation.duration = 1000;
                Chart.defaults.animation.easing = 'easeInOutQuart';
            }
        }

        addChartInteractions() {
            this.charts.forEach(chart => {
                chart.addEventListener('mouseenter', () => {
                    chart.style.transform = 'scale(1.02)';
                });
                
                chart.addEventListener('mouseleave', () => {
                    chart.style.transform = 'scale(1)';
                });
            });
        }
    }

    // =========================================
    // Accessibility Enhancements
    // =========================================

    class AccessibilityEnhancer {
        constructor() {
            this.init();
        }

        init() {
            this.addKeyboardNavigation();
            this.enhanceFocusManagement();
            this.addAriaLabels();
            this.addReducedMotionSupport();
        }

        addKeyboardNavigation() {
            // Enhanced Tab navigation
            const focusableElements = document.querySelectorAll(
                'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
            );
            
            focusableElements.forEach(element => {
                element.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && element.tagName !== 'INPUT' && element.tagName !== 'TEXTAREA') {
                        element.click();
                    }
                });
            });
        }

        enhanceFocusManagement() {
            // Add focus indicators
            const focusableElements = document.querySelectorAll('a, button, input, textarea, select');
            
            focusableElements.forEach(element => {
                element.addEventListener('focus', () => {
                    element.style.outline = '2px solid rgba(var(--primary), 1)';
                    element.style.outlineOffset = '2px';
                });
                
                element.addEventListener('blur', () => {
                    element.style.outline = '';
                    element.style.outlineOffset = '';
                });
            });
        }

        addAriaLabels() {
            // Add missing aria-labels
            const buttons = document.querySelectorAll('button:not([aria-label])');
            buttons.forEach(button => {
                if (!button.textContent.trim()) {
                    const icon = button.querySelector('i');
                    if (icon) {
                        button.setAttribute('aria-label', this.getIconDescription(icon.className));
                    }
                }
            });
        }

        getIconDescription(iconClass) {
            const iconMap = {
                'fa-search': 'Rechercher',
                'fa-edit': 'Modifier',
                'fa-delete': 'Supprimer',
                'fa-plus': 'Ajouter',
                'fa-download': 'Télécharger',
                'fa-upload': 'Téléverser',
                'fa-save': 'Sauvegarder',
                'fa-close': 'Fermer',
                'fa-menu': 'Menu'
            };
            
            for (const [className, description] of Object.entries(iconMap)) {
                if (iconClass.includes(className)) {
                    return description;
                }
            }
            
            return 'Action';
        }

        addReducedMotionSupport() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.body.classList.add('reduced-motion');
                
                // Disable animations
                const style = document.createElement('style');
                style.textContent = `
                    .reduced-motion *,
                    .reduced-motion *::before,
                    .reduced-motion *::after {
                        animation-duration: 0.01ms !important;
                        animation-iteration-count: 1 !important;
                        transition-duration: 0.01ms !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }

    // =========================================
    // Add Required CSS Animations
    // =========================================

    function addRequiredStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes nav-indicator-glow {
                0%, 100% { opacity: 0.7; }
                50% { opacity: 1; }
            }
            
            @keyframes alert-progress {
                from { width: 100%; }
                to { width: 0%; }
            }
            
            @keyframes fadeOutUp {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-30px);
                }
            }
            
            .card-clicked {
                transform: scale(0.98) !important;
                transition: transform 0.1s ease !important;
            }
            
            .form-typing {
                border-color: rgba(var(--info), 1) !important;
            }
            
            .btn-loading {
                pointer-events: none;
                opacity: 0.7;
            }
            
            .row-hidden {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s ease;
            }
            
            .row-visible {
                opacity: 1;
                transform: translateY(0);
            }
            
            .filter-match {
                background: rgba(var(--success), 0.1) !important;
            }
            
            .card-visible {
                animation: fadeInUp 0.6s ease-out forwards;
            }
            
            .navbar-scrolled {
                background: rgba(var(--primary), 0.95) !important;
                backdrop-filter: blur(20px);
            }
            
            .will-change-transform {
                will-change: transform;
            }
            
            .hardware-accelerated {
                transform: translateZ(0);
                backface-visibility: hidden;
            }
        `;
        document.head.appendChild(style);
    }

    // =========================================
    // Initialize All Enhancements
    // =========================================

    function initializeEnhancements() {
        // Add required styles
        addRequiredStyles();
        
        // Initialize all enhancement classes
        new NavigationEnhancer();
        new CardEnhancer();
        new FormEnhancer();
        new TableEnhancer();
        new AlertEnhancer();
        new PerformanceOptimizer();
        new ChartEnhancer();
        new AccessibilityEnhancer();
        
        // Add global event listeners
        addGlobalEventListeners();
    }

    function addGlobalEventListeners() {
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Enhanced back to top functionality
        const backToTop = document.querySelector('.back-to-top');
        if (backToTop) {
            window.addEventListener('scroll', throttle(() => {
                if (window.pageYOffset > 300) {
                    backToTop.style.opacity = '1';
                    backToTop.style.pointerEvents = 'auto';
                } else {
                    backToTop.style.opacity = '0';
                    backToTop.style.pointerEvents = 'none';
                }
            }, 100));
        }
        
        // Add stagger animation to page load
        const animatedElements = document.querySelectorAll('.fade-in-up');
        animatedElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
        });
    }

    // =========================================
    // Page Load and Ready States
    // =========================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeEnhancements);
    } else {
        initializeEnhancements();
    }

    // Export for global access
    window.EduTrackEnhancements = {
        AlertEnhancer,
        NavigationEnhancer,
        CardEnhancer,
        FormEnhancer,
        TableEnhancer,
        PerformanceOptimizer,
        ChartEnhancer,
        AccessibilityEnhancer
    };

})();
