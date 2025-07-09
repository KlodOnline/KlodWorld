/*==============================================================================
    POPUPS
    	Any popups !

==============================================================================*/
'use strict'

function Popup() {
  var self = this // Pour accéder à `this` dans les callbacks
  var currentOkAction = null // Stocke la fonction "OK"

  this.confirmPopup = function ({ text, ok, okText = 'OK', cancelText = 'CANCEL' }) {
    if (typeof ok !== 'function') {
      console.error("Pp.confirmPopup() : le paramètre 'ok' doit être une fonction !")
      return
    }

    // Stocke l'action à exécuter si "OK" est cliqué
    currentOkAction = ok

    // Génère la popup
    var popupHtml = `
            <div class="popup-content">
                <p>${text}</p>
                <button class="popup-btn popup-ok">${okText}</button>
                <button class="popup-btn popup-cancel">${cancelText}</button>
            </div>
        `

    // Affiche la popup
    $('#popup').html(popupHtml).css('z-index', 15)
  }

  this.delete = function () {
    $('#popup').css('z-index', -5).empty()
    currentOkAction = null // Reset l'action après fermeture
  }

  // Gestion des événements
  $('#popup').on('click', '.popup-ok', function () {
    if (currentOkAction) currentOkAction() // Exécute la fonction stockée
    self.delete() // Ferme la popup après l'action
  })

  $('#popup').on('click', '.popup-cancel', function () {
    self.delete() // Ferme simplement la popup
  })

  // Fermer la popup avec "Échap"
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      self.delete()
    }
  })

  // Rendre la popup déplaçable
  $('#popup').draggable({ containment: 'body' })
}
