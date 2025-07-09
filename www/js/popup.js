/*==============================================================================
    POPUPS
        Any popups !
==============================================================================*/
"use strict";

function Popup() {
    const self = this;
    let currentOkAction = null;
    const $popup = $('#popup');

    // Init : rendu draggable, masqué au début (mais avec z-index haut en CSS)
    $popup.hide().draggable({ containment: 'body' });

    this.confirmPopup = function({ text, ok, okText = "OK", cancelText = "CANCEL" }) {
        if (typeof ok !== "function") {
            console.error("Pp.confirmPopup() : le paramètre 'ok' doit être une fonction !");
            return;
        }

        currentOkAction = ok;

        $popup
            .html(`
                <div class="popup-content">
                    <p>${text}</p>
                    <button class="popup-btn popup-ok">${okText}</button>
                    <button class="popup-btn popup-cancel">${cancelText}</button>
                </div>
            `)
            .show(); // Visible sans toucher au z-index
    };

    this.delete = function() {
        $popup.empty().hide();
        currentOkAction = null;
    };

    $popup.on('click', '.popup-ok', () => {
        if (currentOkAction) currentOkAction();
        self.delete();
    });

    $popup.on('click', '.popup-btn.popup-cancel', () => {
        self.delete();
    });

    $(document).on('keydown', (e) => {
        if (e.key === "Escape") self.delete();
    });
}
