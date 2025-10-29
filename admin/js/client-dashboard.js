/**
 * JavaScript pour le dashboard client
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const ClientDashboard = {
        
        /**
         * Initialisation
         */
        init: function() {
            this.bindEvents();
            this.initCounters();
        },
        
        /**
         * Lier les événements
         */
        bindEvents: function() {
            // Filtres
            $('#filter-projet').on('change', this.applyFilters.bind(this));
            $('#filter-status').on('change', this.applyFilters.bind(this));
            $('#filter-date-from').on('change', this.applyFilters.bind(this));
            $('#filter-date-to').on('change', this.applyFilters.bind(this));
            
            // Recherche en temps réel
            let searchTimeout;
            $('#search-interventions').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(ClientDashboard.applyFilters.bind(ClientDashboard), 300);
            });
            
            // Raccourcis de dates
            $('.date-shortcut').on('click', this.applyDateShortcut.bind(this));
            
            // Réinitialiser les filtres
            $('#reset-filters').on('click', this.resetFilters.bind(this));
            
            // Validation des dates
            this.setupDateValidation();
        },
        
        /**
         * Appliquer un raccourci de date
         */
        applyDateShortcut: function(e) {
            const $button = $(e.currentTarget);
            const period = $button.data('period');
            const today = new Date();
            let dateFrom, dateTo;
            
            // Retirer la classe active de tous les boutons
            $('.date-shortcut').removeClass('active');
            $button.addClass('active');
            
            switch(period) {
                case 'today':
                    dateFrom = dateTo = this.formatDate(today);
                    break;
                    
                case 'week':
                    // Début de la semaine (lundi)
                    const firstDay = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
                    const monday = new Date(today.setDate(firstDay));
                    dateFrom = this.formatDate(monday);
                    dateTo = this.formatDate(new Date());
                    break;
                    
                case 'month':
                    // Début du mois
                    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    dateFrom = this.formatDate(firstDayOfMonth);
                    dateTo = this.formatDate(new Date());
                    break;
                    
                case 'year':
                    // Début de l'année
                    const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                    dateFrom = this.formatDate(firstDayOfYear);
                    dateTo = this.formatDate(new Date());
                    break;
            }
            
            // Appliquer les dates
            $('#filter-date-from').val(dateFrom);
            $('#filter-date-to').val(dateTo);
            
            // Appliquer les filtres
            this.applyFilters();
            
            // console.log('Raccourci de date appliqué:', period, dateFrom, 'à', dateTo);
        },
        
        /**
         * Formater une date en format YYYY-MM-DD
         */
        formatDate: function(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        
        /**
         * Configurer la validation des dates
         */
        setupDateValidation: function() {
            $('#filter-date-from').on('change', function() {
                const dateFrom = $(this).val();
                const dateTo = $('#filter-date-to').val();
                
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    alert('La date de début doit être antérieure ou égale à la date de fin.');
                    $(this).val('');
                }
            });
            
            $('#filter-date-to').on('change', function() {
                const dateFrom = $('#filter-date-from').val();
                const dateTo = $(this).val();
                
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    alert('La date de fin doit être postérieure ou égale à la date de début.');
                    $(this).val('');
                }
            });
        },
        
        /**
         * Appliquer les filtres
         */
        applyFilters: function() {
            const projetFilter = $('#filter-projet').val();
            const statusFilter = $('#filter-status').val();
            const searchTerm = $('#search-interventions').val().toLowerCase();
            const dateFrom = $('#filter-date-from').val();
            const dateTo = $('#filter-date-to').val();
            
            // Convertir les dates en timestamps pour comparaison
            const timestampFrom = dateFrom ? new Date(dateFrom).getTime() / 1000 : null;
            const timestampTo = dateTo ? new Date(dateTo).getTime() / 1000 : null;
            
            let visibleCount = 0;
            
            $('.intervention-card').each(function() {
                const $card = $(this);
                const cardProjet = $card.data('projet');
                const cardStatus = $card.data('status');
                const cardTimestamp = parseInt($card.data('date-timestamp'));
                const cardTitle = $card.find('.intervention-title').text().toLowerCase();
                const cardContent = $card.find('.intervention-content').text().toLowerCase();
                
                let show = true;
                
                // Filtre par projet
                if (projetFilter && cardProjet != projetFilter) {
                    show = false;
                }
                
                // Filtre par statut
                if (statusFilter && cardStatus !== statusFilter) {
                    show = false;
                }
                
                // Filtre par date
                if (timestampFrom && cardTimestamp && cardTimestamp < timestampFrom) {
                    show = false;
                }
                
                if (timestampTo && cardTimestamp && cardTimestamp > timestampTo) {
                    show = false;
                }
                
                // Filtre par recherche
                if (searchTerm && !cardTitle.includes(searchTerm) && !cardContent.includes(searchTerm)) {
                    show = false;
                }
                
                if (show) {
                    $card.show();
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });
            
            // Mettre à jour le compteur
            $('.interventions-count').text('(' + visibleCount + ')');
            
            // Afficher/masquer le message "aucun résultat"
            if (visibleCount === 0) {
                $('.no-results').show();
            } else {
                $('.no-results').hide();
            }
            
            // Log pour debug
            // console.log('Filtres appliqués:', {
            //     projet: projetFilter || 'tous',
            //     status: statusFilter || 'tous',
            //     dateFrom: dateFrom || 'aucune',
            //     dateTo: dateTo || 'aucune',
            //     search: searchTerm || 'aucune',
            //     visibleCount: visibleCount
            // });
        },
        
        /**
         * Réinitialiser les filtres
         */
        resetFilters: function() {
            $('#filter-projet').val('');
            $('#filter-status').val('');
            $('#filter-date-from').val('');
            $('#filter-date-to').val('');
            $('#search-interventions').val('');
            
            // Retirer la classe active des raccourcis
            $('.date-shortcut').removeClass('active');
            
            $('.intervention-card').show();
            
            const totalCount = $('.intervention-card').length;
            $('.interventions-count').text('(' + totalCount + ')');
            $('.no-results').hide();
            
            // console.log('Filtres réinitialisés - ' + totalCount + ' interventions affichées');
        },
        
        /**
         * Initialiser les compteurs animés
         */
        initCounters: function() {
            $('.stat-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text());
                const duration = 1000;
                const increment = target / (duration / 16);
                
                let current = 0;
                const timer = setInterval(function() {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    $this.text(Math.floor(current));
                }, 16);
            });
        },
        
        /**
         * Animer les barres de progression
         */
        animateProgressBars: function() {
            $('.progress-fill').each(function() {
                const $this = $(this);
                const width = $this.css('width');
                $this.css('width', '0%');
                
                setTimeout(function() {
                    $this.css('width', width);
                }, 100);
            });
        }
    };
    
    // Initialiser au chargement du DOM
    $(document).ready(function() {
        // console.log('=== CLIENT DASHBOARD LOADED ===');
        ClientDashboard.init();
        
        // Animer les barres de progression après un court délai
        setTimeout(function() {
            ClientDashboard.animateProgressBars();
        }, 500);
    });
    
    // Exposer globalement
    window.ClientDashboard = ClientDashboard;
    
})(jQuery);