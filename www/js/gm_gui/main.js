/* =============================================================================
    gm_gui.js
    - Manages all interactivity for gm_index.php
============================================================================= */

/* -----------------------------------------------------------------------------
    AJAX Management
----------------------------------------------------------------------------- */

// Function to display the clicked section
function showSection(sectionId) {
    // Clear all sections
    $('.section').empty();
    // Display the corresponding section
    $('#' + sectionId).show();
    // Dynamically load data via AJAX
    loadSectionData(sectionId);
}

// Function to load section data via AJAX
function loadSectionData(sectionId) {
    if (sectionId === 'units' || sectionId === 'buildings' || sectionId === 'resources' || sectionId === 'lands') {
        $.ajax({
            url: 'includes/gm_gui/loadxml.php',
            type: 'GET',
            data: { action: 'load_data', type: sectionId },
            success: function (response) {
                $('#' + sectionId).html(response);
                attachEditableCells(); // Reattach editable cell events
                // Attach click event for the XML Save button
                $('#saveButton').on('click', function () {
                    saveXMLData(sectionId);
                });
            },
            error: function (xhr, error) {
                $('#' + sectionId).html('<p>(MAIN) Error loading data.' + error + '</p>');
                console.log(error);
                console.log(xhr.responseText); // Affiche le texte de la réponse en cas d'erreur
            }
        });
        return;
    }

    if (sectionId === 'world') {
        $.ajax({
            url: 'includes/gm_gui/worldgengui.php',
            success: function (response) {
                $('#' + sectionId).html(response);
            },
            error: function (xhr, error) {
                $('#' + sectionId).html('<p>(MAIN) Error loading data.' + error + '</p>');
                console.log(error);
                console.log(xhr.responseText);
            }
        });
        return;
    }
}


