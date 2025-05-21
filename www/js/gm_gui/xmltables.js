/* =============================================================================
    gm_xmltables.js
    
    - for some reasons, I had to generate HTML here and in loadxml.php
    	> so don't forget to double check in case of modifications or...
    	> if you need to modify, maybe it's time to refactor the whole shit
============================================================================= */

// Prevent default value from being cleared on focus
$(document).on('focus', '.editable-cell', function () {
    if ($(this).val() === 'default_champs') return;
});


/* -----------------------------------------------------------------------------
    Save Functionality
----------------------------------------------------------------------------- */

// Generate XML based on the section
function generateXML(sectionId) {
    let xmlContent = '';
    let itemName = '';

    switch (sectionId) {
        case 'units': xmlContent = '<units>'; itemName = 'unit'; break;
        case 'buildings': xmlContent = '<buildings>'; itemName = 'building'; break;
        case 'resources': xmlContent = '<resources>'; itemName = 'resource'; break;
        case 'lands': xmlContent = '<lands>'; itemName = 'land'; break;
    }

    // Retrieve table headers
    let headers = [];
    $('#' + sectionId + ' thead th').each(function () {
        let headerName = $(this).find('input[type="text"]').val().trim();
        let classSelect = $(this).find('select').val();
        headers.push({ name: headerName, class: classSelect });
    });

    // Retrieve table rows
    $('#' + sectionId + ' tbody tr').each(function () {
        let itemXML = `<${itemName}>`;

        $(this).find('td').each(function (index) {
            let header = headers[index].name;
            let className = headers[index].class;
            let inputText = $(this).find('input').val();
            let inputSelect = $(this).find('select').val();

            if (className !== 'simple') {
                itemXML += `<${header} class="${className}">`;
                $(this).find('.complex-field').each(function () {
                    let complexName = $(this).find('.editable-name').val();
                    let complexValue = $(this).find('.editable-value').val();
                    console.log(complexName);
                    console.log(complexValue);
                    if (complexName!=='' && complexValue!=='') { itemXML += `<${complexName}>${complexValue}</${complexName}>`; }
                });
                itemXML += `</${header}>`;
            } else {
                itemXML += `<${header}>${inputText || inputSelect}</${header}>`;
            }
        });

        itemXML += `</${itemName}>`;
        xmlContent += itemXML;
    });

    xmlContent += `</${sectionId}>`;
    return xmlContent;
}

// Generate and log the XML
function saveXMLData(sectionId) {
    let xmlContent = generateXML(sectionId);

    console.log('Saving ...');
    console.log(xmlContent);

    $.ajax({
        url: 'includes/gm_gui/savexml.php',
        type: 'POST',
        data: { type: sectionId, xml: xmlContent },
        success: function (response) {
            console.log('Data saved successfully:', response);
            // Affiche un message de succès près du bouton
            $('#saveMessage')
                .text('Saved successfully!')
                .css('color', 'green')
                .fadeIn()
                .delay(2000) // Le message disparaît après 2 secondes
                .fadeOut();

        },
        error: function (xhr, status, error) {
            console.error('Error saving data:', error);
            // Affiche un message d'erreur près du bouton
            $('#saveMessage')
                .text('Error saving data: ' + error)
                .css('color', 'red')
                .fadeIn()
                .delay(4000) // Le message disparaît après 4 secondes
                .fadeOut();

        }
    });
}

/* -----------------------------------------------------------------------------
    Cell Management
----------------------------------------------------------------------------- */

// Create a simple editable cell
function simpleContentCell(defaultValue = "NewValue") {
    let input = document.createElement('input');
    input.type = 'text';
    input.value = defaultValue;
    input.className = 'editable-cell';
    return input;
}

// Create a complex editable cell with a "+" button for adding fields
function complexeContentCell(type = "units", defaultName = "", defaultValue = "1") {
    let cellWrapper = document.createElement('div');
    cellWrapper.className = 'complex-field-wrapper';

    let fieldContainer = document.createElement('div');
    fieldContainer.className = 'field-container';

    fieldContainer.appendChild(createComplexField(type, defaultName, defaultValue));

    let addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'add-field-btn';
    addButton.textContent = '+';
    addButton.onclick = () => {
        fieldContainer.appendChild(createComplexField(type));
    };

    cellWrapper.appendChild(fieldContainer);
    cellWrapper.appendChild(addButton);
    return cellWrapper;
}

// Create a subfield for complex cells
function createComplexField(type = "units", defaultName = "", defaultValue = "") {
    let fieldWrapper = document.createElement('div');
    fieldWrapper.className = 'complex-field';

    let select = document.createElement('select');
    select.className = 'editable-name';
    getListByType(type).forEach(item => {
        let option = document.createElement('option');
        option.value = item.name || item[0];
        option.textContent = item.name || item[0];
        select.appendChild(option);
    });
    select.value = defaultName;

    let valueInput = document.createElement('input');
    valueInput.type = 'text';
    valueInput.className = 'editable-value';
    valueInput.value = defaultValue;

    let removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'row-btn';
    removeButton.textContent = '-';
    removeButton.onclick = () => fieldWrapper.remove();

    fieldWrapper.appendChild(removeButton);
    fieldWrapper.appendChild(select);
    fieldWrapper.appendChild(valueInput);
    

    return fieldWrapper;
}

// Fetch the list of options based on type
function getListByType(type) {
    switch (type) {
        case 'units': return unitsList || [];
        case 'buildings': return buildingsList || [];
        case 'resources': return resourcesList || [];
        case 'lands': return landsList || [];
        default: return [];
    }
}

// DIRTY !! ====================================================================
function addSubfield(button, type) {
	var inputWrapper = button.closest('.input-wrapper');
    if (inputWrapper) { 
    	var fieldContainer = inputWrapper.querySelector('.field-container');
	}
    
    if (fieldContainer) {
        fieldContainer.appendChild(createComplexField(type)); 
    } else {
        console.error("Le conteneur parent n'a pas été trouvé.");
    }
}

function removeSubfield(fieldDiv) {
    if (fieldDiv) {
        fieldDiv.remove(); // Supprime l'élément du DOM
    } else {
        console.error("Le champ n'a pas été trouvé.");
    }
}


/* -----------------------------------------------------------------------------
    Column and Row Management
----------------------------------------------------------------------------- */

// Attach editing events to table cells
function attachEditableCells() {
    document.querySelectorAll('#data-table td').forEach(cell => {
        if (!cell.querySelector('input')) {
            cell.addEventListener('click', function () {
                let currentValue = this.textContent || this.innerText;
                let input = simpleContentCell(currentValue);
                this.innerHTML = '';
                this.appendChild(input);
                input.focus();

                input.addEventListener('blur', () => {
                    this.textContent = input.value;
                });
            });
        }
    });
}

// Add a column to the table
function addColumn() {
    let table = document.getElementById('data-table');
    let headerRow = table.querySelector('thead tr');
    let newHeaderCell = document.createElement('th');

    let container = document.createElement('div');
    container.className = 'th-container';

    let headerInput = simpleContentCell('NewColumn');

    let selectMenu = document.createElement('select');
    selectMenu.className = 'type-selector';
    selectMenu.innerHTML = `
        <option value="simple">simple</option>
        <option value="buildings">buildings</option>
        <option value="units">units</option>
        <option value="resources">resources</option>
        <option value="lands">lands</option>
    `;
    selectMenu.onchange = () => updateColumnType(selectMenu);

    container.appendChild(headerInput);
    container.appendChild(selectMenu);
    newHeaderCell.appendChild(container);
    headerRow.appendChild(newHeaderCell);

    table.querySelectorAll('tbody tr').forEach(row => {
        let newCell = document.createElement('td');
        newCell.appendChild(simpleContentCell());
        row.appendChild(newCell);
    });
}

// Update column cells based on its type
function updateColumnType(selectElement) {
    let columnType = selectElement.value;
    let th = selectElement.closest('th');
    let columnIndex = Array.from(th.parentNode.children).indexOf(th);

    th.closest('table').querySelectorAll('tbody tr').forEach(row => {
        let cell = row.children[columnIndex];
        cell.innerHTML = '';
        if (columnType === 'simple') {
            cell.appendChild(simpleContentCell());
        } else {
            cell.appendChild(complexeContentCell(columnType));
        }
    });
}

// Add a new row to the table
function addItem() {
    let table = document.getElementById('data-table');
    let newRow = document.createElement('tr');

    table.querySelectorAll('thead th').forEach(th => {
        let cell = document.createElement('td');
        let columnType = th.querySelector('.type-selector').value;
        cell.appendChild(columnType === 'simple' ? simpleContentCell() : complexeContentCell(columnType));
        newRow.appendChild(cell);
    });

    table.querySelector('tbody').appendChild(newRow);
}
