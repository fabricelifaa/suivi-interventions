/**
 * Scripts JavaScript pour l'administration du plugin Suivi des Interventions
 *
 * @package SuiviInterventions
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Objet principal du plugin
  const SuiviInterventions = {
    /**
     * Initialisation
     */
    init: function () {
      this.bindEvents();
      this.initProgressBars();
      this.initFilters();
      this.initValidation();
      this.initTooltips();
    },

    /**
     * Lier les événements
     */
    bindEvents: function () {
      // Filtres en temps réel
      $("#the-list").on(
        "click",
        ".quota-progress-container",
        this.showQuotaDetails
      );

      // Validation des formulaires
      $("form").on("submit", this.validateForms);

      // Auto-refresh des barres de progression
      setInterval(this.updateProgressBars, 30000); // Toutes les 30 secondes

      // Gestion des filtres de date
      $('input[name="date_from"], input[name="date_to"]').on(
        "change",
        this.validateDateRange
      );

      // Recherche en temps réel
      let searchTimeout;
      $("#post-search-input").on("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(SuiviInterventions.performSearch, 500);
      });
    },

    /**
     * Initialiser les barres de progression
     */
    initProgressBars: function () {
      $(".quota-progress-fill").each(function () {
        const $fill = $(this);
        const width = $fill.data("width") || $fill.css("width");

        // Animation d'apparition
        $fill.css("width", "0%");
        setTimeout(function () {
          $fill.css("width", width);
        }, 100);
      });

      // Ajouter des tooltips
      $(".quota-progress-container").each(function () {
        const $container = $(this);
        const $text = $container.find(".quota-text");

        if ($text.length) {
          $container.attr("title", $text.text());
        }
      });
    },

    /**
     * Initialiser les filtres
     */
    initFilters: function () {
      // Sauvegarder les filtres dans le localStorage (si disponible et autorisé)
      const savedFilters = this.getSavedFilters();

      if (savedFilters) {
        $('select[name="intervention_status"]').val(savedFilters.status);
        $('select[name="projet_filter"]').val(savedFilters.projet);
        $('input[name="date_from"]').val(savedFilters.dateFrom);
        $('input[name="date_to"]').val(savedFilters.dateTo);
      }

      // Sauvegarder les filtres lors des changements
      $(
        'select[name="intervention_status"], select[name="projet_filter"], input[name="date_from"], input[name="date_to"]'
      ).on("change", function () {
        SuiviInterventions.saveFilters();
      });

      // Bouton pour réinitialiser les filtres
      this.addResetFiltersButton();
    },

    /**
     * Initialiser la validation
     */
    initValidation: function () {
      // Validation des dates d'intervention
      $('input[name="date_intervention"]').on("change", function () {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        const $warning = $("#date-warning");

        // Supprimer l'ancien avertissement
        $warning.remove();

        if (selectedDate > today) {
          $(this).after(
            '<p id="date-warning" style="color: #856404; font-size: 12px; margin-top: 5px;">⚠ Cette intervention est planifiée pour le futur.</p>'
          );
        }
      });

      // Validation des quotas
      $('input[name="quota"]').on("input", function () {
        const value = parseInt($(this).val());
        const $feedback = $("#quota-feedback");

        $feedback.remove();

        if (value < 0) {
          $(this).val(0);
        } else if (value > 1000) {
          $(this).after(
            '<p id="quota-feedback" style="color: #d63384; font-size: 12px; margin-top: 5px;">⚠ Ce quota semble très élevé. Êtes-vous sûr ?</p>'
          );
        }
      });
    },

    /**
     * Initialiser les tooltips
     */
    initTooltips: function () {
      // Créer des tooltips personnalisés
      $("[title]").each(function () {
        const $element = $(this);
        const title = $element.attr("title");

        $element
          .removeAttr("title")
          .on("mouseenter", function () {
            SuiviInterventions.showTooltip($(this), title);
          })
          .on("mouseleave", function () {
            SuiviInterventions.hideTooltip();
          });
      });
    },

    /**
     * Afficher les détails du quota
     */
    showQuotaDetails: function (e) {
      e.preventDefault();

      const $container = $(this);
      const $details = $container.find(".quota-details");

      if ($details.length) {
        $details.slideToggle(200);
      } else {
        // Créer et afficher les détails
        const quotaText = $container.find(".quota-text").text();
        const detailsHtml =
          '<div class="quota-details" style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 11px; border-left: 3px solid #0073aa;">' +
          "<strong>Détails :</strong><br>" +
          quotaText
            .replace(/\(|\)/g, "")
            .replace("restant", "<br>• Restant :")
            .replace("%", "%<br>• Pourcentage utilisé") +
          "</div>";

        $container.append(detailsHtml);
        $container.find(".quota-details").slideDown(200);
      }
    },

    /**
     * Mettre à jour les barres de progression
     */
    updateProgressBars: function () {
      // Ne pas actualiser si l'utilisateur interagit avec la page
      if (
        document.hidden ||
        $(document.activeElement).is("input, select, textarea")
      ) {
        return;
      }

      $(".quota-progress-container").each(function () {
        const $container = $(this);
        // Ajouter une classe pour indiquer la mise à jour
        $container.addClass("updating");

        setTimeout(function () {
          $container.removeClass("updating");
        }, 1000);
      });
    },

    /**
     * Valider la plage de dates
     */
    validateDateRange: function () {
      const dateFrom = $('input[name="date_from"]').val();
      const dateTo = $('input[name="date_to"]').val();
      const $error = $("#date-range-error");

      $error.remove();

      if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
        $('input[name="date_to"]').after(
          '<p id="date-range-error" style="color: #d63384; font-size: 12px; margin-top: 5px;">⚠ La date de fin doit être postérieure à la date de début.</p>'
        );
      }
    },

    /**
     * Effectuer une recherche
     */
    performSearch: function () {
      const searchTerm = $("#post-search-input").val().toLowerCase();

      if (!searchTerm) {
        $("#the-list tr").show();
        return;
      }

      $("#the-list tr").each(function () {
        const $row = $(this);
        const text = $row.text().toLowerCase();

        if (text.includes(searchTerm)) {
          $row.show();
        } else {
          $row.hide();
        }
      });

      // Mettre à jour le compteur de résultats
      const visibleRows = $("#the-list tr:visible").length;
      $("#search-results-count").remove();
      $(".tablenav-pages").after(
        '<span id="search-results-count" style="margin-left: 10px; color: #666;">(' +
          visibleRows +
          " résultat(s) trouvé(s))</span>"
      );
    },

    /**
     * Valider les formulaires
     */
    validateForms: function (e) {
      const $form = $(this);
      let isValid = true;

      // Validation des champs requis
      $form.find("[required]").each(function () {
        if (!$(this).val()) {
          $(this).addClass("error");
          isValid = false;
        } else {
          $(this).removeClass("error");
        }
      });

      if (!isValid) {
        e.preventDefault();
        SuiviInterventions.showNotification(
          "Veuillez remplir tous les champs requis.",
          "error"
        );
        return false;
      }

      // Ajouter un indicateur de chargement
      $form
        .find('input[type="submit"]')
        .prop("disabled", true)
        .after('<span class="loading-spinner"></span>');
    },

    /**
     * Ajouter le bouton de réinitialisation des filtres
     */
    addResetFiltersButton: function () {
      if ($(".reset-filters-btn").length) return;

      const resetButton =
        '<button type="button" class="button reset-filters-btn" style="margin-left: 5px;">Réinitialiser</button>';
      $(".actions").append(resetButton);

      $(".reset-filters-btn").on("click", function () {
        $('select[name="intervention_status"]').val("");
        $('select[name="projet_filter"]').val("");
        $('input[name="date_from"]').val("");
        $('input[name="date_to"]').val("");
        $("#search-results-count").remove();
        $("#the-list tr").show();

        // Soumettre le formulaire pour appliquer les changements
        $("#posts-filter").submit();
      });
    },

    /**
     * Afficher une notification
     */
    showNotification: function (message, type = "info") {
      const $notification = $(
        '<div class="notice notice-' +
          type +
          ' is-dismissible si-notification"><p>' +
          message +
          "</p></div>"
      );
      $(".wrap h1").after($notification);

      // Auto-suppression après 5 secondes
      setTimeout(function () {
        $notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    },

    /**
     * Afficher un tooltip
     */
    showTooltip: function ($element, text) {
      const $tooltip = $('<div class="si-tooltip">' + text + "</div>");
      $("body").append($tooltip);

      const offset = $element.offset();
      const tooltipWidth = $tooltip.outerWidth();
      const left = offset.left + $element.outerWidth() / 2 - tooltipWidth / 2;
      const top = offset.top - $tooltip.outerHeight() - 10;

      $tooltip
        .css({
          position: "absolute",
          left: left + "px",
          top: top + "px",
          background: "#333",
          color: "white",
          padding: "8px 12px",
          borderRadius: "4px",
          fontSize: "12px",
          zIndex: 999999,
          whiteSpace: "nowrap",
        })
        .fadeIn(200);
    },

    /**
     * Masquer le tooltip
     */
    hideTooltip: function () {
      $(".si-tooltip").fadeOut(200, function () {
        $(this).remove();
      });
    },

    /**
     * Sauvegarder les filtres (en mémoire uniquement)
     */
    saveFilters: function () {
      // Stocker en mémoire pour la session en cours
      this.currentFilters = {
        status: $('select[name="intervention_status"]').val(),
        projet: $('select[name="projet_filter"]').val(),
        dateFrom: $('input[name="date_from"]').val(),
        dateTo: $('input[name="date_to"]').val(),
      };
    },

    /**
     * Récupérer les filtres sauvegardés
     */
    getSavedFilters: function () {
      return this.currentFilters || null;
    },

    /**
     * Utilitaires
     */
    utils: {
      /**
       * Debounce function
       */
      debounce: function (func, wait, immediate) {
        let timeout;
        return function () {
          const context = this;
          const args = arguments;
          const later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
          };
          const callNow = immediate && !timeout;
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
          if (callNow) func.apply(context, args);
        };
      },

      /**
       * Formater une date
       */
      formatDate: function (date) {
        return new Intl.DateTimeFormat("fr-FR", {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
        }).format(new Date(date));
      },
    },
  };

  // Initialiser lors du chargement du DOM
  $(document).ready(function () {
    SuiviInterventions.init();

    // Validation côté client
    $("#post").submit(function (e) {
      var dateIntervention = $("#date_intervention").val();
      if (!dateIntervention) {
        e.preventDefault();
        if (suivdeinAdminError) {
          alert(suivdeinAdminError.msg);
        }
        $("#date_intervention").focus();
        return false;
      }
    });

    // Mettre à jour l'aperçu du statut
    $("#intervention_terminee")
      .change(function () {
        var $status = $(".intervention-status-preview");
        if (!$status.length) {
          $("#intervention_terminee")
            .parent()
            .append('<p class="intervention-status-preview"></p>');
          $status = $(".intervention-status-preview");
        }

        if ($(this).is(":checked")) {
          $status.html(
            `<strong style="color: green;">✓ ${suivdeinAdminError.quotaMsg}</strong>`
          );
        } else {
          $status.html(
            `<strong style="color: orange;">⏳ ${suivdeinAdminError.quotaMsg}</strong>`
          );
        }
      })
      .trigger("change");

    //
    // Validation du quota
    $("#quota").on("input", function () {
      var value = parseInt($(this).val());
      if (value < 0) {
        $(this).val(0);
      }
    });

    // Validation de l'URL
    $("#projet_url").on("blur", function () {
      var url = $(this).val();
      if (url && !url.match(/^https?:\/\//)) {
        $(this).val("https://" + url);
      }
    });

    // Prévisualisation de l'expiration
    $("#date_expiration").on("change", function () {
      var selectedDate = new Date($(this).val());
      var today = new Date();
      var $preview = $("#expiration-preview");

      if (!$preview.length) {
        $(this).after(
          '<p id="expiration-preview" style="margin-top: 5px; font-size: 12px;"></p>'
        );
        $preview = $("#expiration-preview");
      }

      if (selectedDate && selectedDate > today) {
        var diffTime = Math.abs(selectedDate - today);
        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        $preview.html(
          '<strong style="color: green;">✓ Expire dans ' +
            diffDays +
            " jour(s)</strong>"
        );
      } else if (selectedDate && selectedDate <= today) {
        $preview.html(
          '<strong style="color: red;">⚠ Cette date est déjà passée</strong>'
        );
      } else {
        $preview.html("");
      }
    });

    // fichier restriction roles php
    
    $(".projet-checkbox").on("change", function () {
      updateClientProjectPreview();
    });

    function updateClientProjectPreview() {
      var selectedProjects = [];
      $(".projet-checkbox:checked").each(function () {
        var projectName = $(this).closest(".projet-item").find("strong").text();
        selectedProjects.push(projectName);
      });

      console.log("Projets sélectionnés:", selectedProjects);

      var $preview = $("#client-project-preview");
      if (!$preview.length) {
        $(".client-projets-list").after(
          '<div id="client-project-preview" class="project-preview"></div>'
        );
        $preview = $("#client-project-preview");
      }

      if (selectedProjects.length > 0) {
        $preview.html(
          "<h4>Projets sélectionnés (" +
            selectedProjects.length +
            "):</h4><ul><li>" +
            selectedProjects.join("</li><li>") +
            "</li></ul>"
        );
        $preview.addClass("has-projects");
      } else {
        $preview.html(
          '<h4 style="color: #d63384;">⚠ Aucun projet sélectionné - Ce client ne pourra voir aucune intervention</h4>'
        );
        $preview.removeClass("has-projects");
      }
    }

    // Initialiser la prévisualisation
    updateClientProjectPreview();
  });

  // Exposer l'objet globalement si nécessaire
  window.SuiviInterventions = SuiviInterventions;
})(jQuery);
