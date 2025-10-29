/**
 * JavaScript pour la page de gestion des clients
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const ClientManagement = {
        
        /**
         * Initialisation
         */
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
        },
        
        /**
         * Lier les événements
         */
        bindEvents: function() {
            // Sélection/Désélection rapide des clients
            $('#select-all-clients').on('click', this.selectAllClients);
            $('#deselect-all-clients').on('click', this.deselectAllClients);
            
            // Validation du formulaire d'assignation en lot
            $('#bulk-assignment-form').on('submit', this.validateBulkAssignment);
            
            // Mise à jour en temps réel
            $('input[name="bulk_clients[]"]').on('change', this.updateBulkPreview);
            $('#bulk_project').on('change', this.updateBulkPreview);
            
            // Actions sur les cartes client
            $('.client-project-card').on('click', '.toggle-details', this.toggleClientDetails);
            
            // Recherche en temps réel
            let searchTimeout;
            $('#client-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(ClientManagement.performSearch, 300);
            });
        },
        
        /**
         * Sélectionner tous les clients
         */
        selectAllClients: function(e) {
            e.preventDefault();
            $('input[name="bulk_clients[]"]').prop('checked', true);
            ClientManagement.updateBulkPreview();
        },
        
        /**
         * Désélectionner tous les clients
         */
        deselectAllClients: function(e) {
            e.preventDefault();
            $('input[name="bulk_clients[]"]').prop('checked', false);
            ClientManagement.updateBulkPreview();
        },
        
        /**
         * Valider le formulaire d'assignation en lot
         */
        validateBulkAssignment: function(e) {
            const projectSelected = $('#bulk_project').val();
            const clientsSelected = $('input[name="bulk_clients[]"]:checked').length;
            
            if (!projectSelected) {
                e.preventDefault();
                ClientManagement.showNotification('Veuillez sélectionner un projet.', 'error');
                $('#bulk_project').focus();
                return false;
            }
            
            if (clientsSelected === 0) {
                e.preventDefault();
                ClientManagement.showNotification('Veuillez sélectionner au moins un client.', 'error');
                return false;
            }
            
            // Confirmation
            const projectName = $('#bulk_project option:selected').text();
            const confirmMessage = `Êtes-vous sûr d'assigner le projet "${projectName}" à ${clientsSelected} client(s) ?`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Ajouter un loader
            const $submit = $(this).find('input[type="submit"]');
            $submit.prop('disabled', true).val('Assignation en cours...');
            
            return true;
        },
        
        /**
         * Mettre à jour l'aperçu d'assignation en lot
         */
        updateBulkPreview: function() {
            const projectSelected = $('#bulk_project').val();
            const projectName = $('#bulk_project option:selected').text();
            const clientsSelected = $('input[name="bulk_clients[]"]:checked');
            const clientCount = clientsSelected.length;
            
            let $preview = $('#bulk-assignment-preview');
            if (!$preview.length) {
                $('#bulk-assignment-form').append('<div id="bulk-assignment-preview" class="bulk-preview"></div>');
                $preview = $('#bulk-assignment-preview');
            }
            
            if (projectSelected && clientCount > 0) {
                let clientNames = [];
                clientsSelected.each(function() {
                    const label = $(this).next('label').text();
                    clientNames.push(label);
                });
                
                const previewHtml = `
                    <div class="preview-content">
                        <h4>Aperçu de l'assignation :</h4>
                        <p><strong>Projet :</strong> ${projectName}</p>
                        <p><strong>Clients (${clientCount}) :</strong></p>
                        <ul class="preview-clients">
                            ${clientNames.map(name => `<li>${name}</li>`).join('')}
                        </ul>
                    </div>
                `;
                
                $preview.html(previewHtml).addClass('has-content');
            } else {
                $preview.html('').removeClass('has-content');
            }
        },
        
        /**
         * Basculer les détails d'un client
         */
        toggleClientDetails: function(e) {
            e.preventDefault();
            const $card = $(this).closest('.client-project-card');
            const $details = $card.find('.client-details');
            
            $details.slideToggle(200);
            $(this).text($details.is(':visible') ? 'Masquer détails' : 'Voir détails');
        },
        
        /**
         * Initialiser la recherche
         */
        initSearch: function() {
            // Ajouter un champ de recherche si il n'existe pas
            if (!$('#client-search').length) {
                $('.si-client-projects-grid').before(`
                    <div class="search-filter-bar">
                        <input type="search" id="client-search" placeholder="Rechercher un client..." />
                        <select id="project-filter">
                            <option value="">Tous les projets</option>
                        </select>
                        <button type="button" id="reset-filters" class="button">Réinitialiser</button>
                    </div>
                `);
                
                // Remplir le filtre des projets
                this.populateProjectFilter();
            }
        },
        
        /**
         * Remplir le filtre des projets
         */
        populateProjectFilter: function() {
            const projects = new Set();
            $('.project-name').each(function() {
                projects.add($(this).text());
            });
            
            const $filter = $('#project-filter');
            projects.forEach(project => {
                $filter.append(`<option value="${project}">${project}</option>`);
            });
        },
        
        /**
         * Initialiser les filtres
         */
        initFilters: function() {
            $('#project-filter').on('change', this.applyFilters);
            $('#reset-filters').on('click', this.resetFilters);
        },
        
        /**
         * Effectuer une recherche
         */
        performSearch: function() {
            const searchTerm = $('#client-search').val().toLowerCase();
            const projectFilter = $('#project-filter').val();
            
            $('.client-project-card').each(function() {
                const $card = $(this);
                const clientName = $card.find('h3').text().toLowerCase();
                const clientEmail = $card.find('.client-email').text().toLowerCase();
                const projects = $card.find('.project-name').map(function() {
                    return $(this).text();
                }).get();
                
                let showCard = true;
                
                // Filtre de recherche textuelle
                if (searchTerm) {
                    showCard = clientName.includes(searchTerm) || clientEmail.includes(searchTerm);
                }
                
                // Filtre par projet
                if (showCard && projectFilter) {
                    showCard = projects.includes(projectFilter);
                }
                
                $card.toggle(showCard);
            });
            
            // Mettre à jour le compteur
            ClientManagement.updateResultCount();
        },
        
        /**
         * Appliquer les filtres
         */
        applyFilters: function() {
            ClientManagement.performSearch();
        },
        
        /**
         * Réinitialiser les filtres
         */
        resetFilters: function() {
            $('#client-search').val('');
            $('#project-filter').val('');
            $('.client-project-card').show();
            ClientManagement.updateResultCount();
        },
        
        /**
         * Mettre à jour le compteur de résultats
         */
        updateResultCount: function() {
            const total = $('.client-project-card').length;
            const visible = $('.client-project-card:visible').length;
            
            let $counter = $('#results-counter');
            if (!$counter.length) {
                $('.si-client-projects-grid').before('<div id="results-counter"></div>');
                $counter = $('#results-counter');
            }
            
            if (visible === total) {
                $counter.html(`<p>${total} client(s) affiché(s)</p>`);
            } else {
                $counter.html(`<p>${visible} sur ${total} client(s) affiché(s)</p>`);
            }
        },
        
        /**
         * Afficher une notification
         */
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible si-notification">
                    <p>${message}</p>
                </div>
            `);
            
            $('.wrap h1').after($notification);
            
            // Auto-suppression après 5 secondes
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Mise à jour AJAX des projets client
         */
        updateClientProjects: function(clientId, projectIds) {
            $.post(siClientManagement.ajaxurl, {
                action: 'si_update_client_projects',
                nonce: siClientManagement.nonce,
                client_id: clientId,
                project_ids: projectIds
            })
            .done(function(response) {
                if (response.success) {
                    ClientManagement.showNotification(siClientManagement.strings.success_update, 'success');
                } else {
                    ClientManagement.showNotification(siClientManagement.strings.error_update, 'error');
                }
            })
            .fail(function() {
                ClientManagement.showNotification(siClientManagement.strings.error_update, 'error');
            });
        }
    };
    
    // Initialiser au chargement du DOM
    $(document).ready(function() {
        ClientManagement.init();
    });
    
    // Exposer globalement si nécessaire
    window.SIClientManagement = ClientManagement;
    
})(jQuery);

// Styles CSS additionnels
const additionalStyles = `
<style>
.search-filter-bar {
    display: flex;
    gap: 15px;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

#client-search {
    flex: 1;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #7e8993;
    border-radius: 4px;
}

#project-filter {
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #7e8993;
    border-radius: 4px;
}

#results-counter {
    margin: 10px 0;
    color: #646970;
    font-style: italic;
}

.bulk-preview {
    margin-top: 20px;
    padding: 0;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.bulk-preview.has-content {
    padding: 15px;
    background: #e7f3ff;
    border: 1px solid #00a0d2;
}

.preview-content h4 {
    margin-top: 0;
    color: #0073aa;
}

.preview-clients {
    max-height: 150px;
    overflow-y: auto;
    margin: 0;
    padding-left: 20px;
}

.preview-clients li {
    margin-bottom: 5px;
}

.client-details {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.toggle-details {
    margin-left: 10px;
    font-size: 12px;
}

/* Animation pour les cartes */
.client-project-card {
    transition: all 0.3s ease;
}

.client-project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .search-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    #client-search,
    #project-filter {
        max-width: none;
    }
    
    .si-client-projects-grid {
        grid-template-columns: 1fr;
    }
}
</style>
`;

// Injecter les styles
document.head.insertAdjacentHTML('beforeend', additionalStyles);