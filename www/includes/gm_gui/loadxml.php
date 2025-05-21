<?php
/* =============================================================================
    gmLoadXml.php
    - Handles AJAX requests for the GM interface.
    - Interacts with XMLObjectManager to load and display XML data.
============================================================================= */
include_once __DIR__.'/gm_init.php';

/* -------------------------------------------------------------------------
    Initialize the rule manager to interact with XML files.
------------------------------------------------------------------------- */
$ruleManager = new XMLObjectManager();

/**
 * Retrieves a list of names from the XML based on type.
 *
 * @param string $type The type of items to retrieve (e.g., 'units', 'buildings').
 * @return array List of item names.
 */
function XMLnameList($type) {
    $ruleManager = new XMLObjectManager();
    $items = $ruleManager->allItems($type);
    $names = [];
    foreach ($items as $item) {  $names[] = $item->__get('name'); }
    return $names;
}

// Retrieve predefined lists
$unitsList = XMLnameList('units');
$buildingsList = XMLnameList('buildings');
$resourcesList = XMLnameList('resources');
$landsList = XMLnameList('lands');

echo '<script>
    var unitsList = ' . json_encode($unitsList) . ';
    var buildingsList = ' . json_encode($buildingsList) . ';
    var resourcesList = ' . json_encode($resourcesList) . ';
    var landsList = ' . json_encode($landsList) . ';
</script>';

/* -------------------------------------------------------------------------
    Handle AJAX action: Load data
    - Outputs an HTML table based on the requested type.
------------------------------------------------------------------------- */
if ($_GET['action'] == 'load_data') {
    $type = $_GET['type'];
    $items = $ruleManager->allItems($type);
    $output = '';

    // Generate title and main buttons
    $output .= '<h2>'.$type.' management</h2>';
    $output .= '<div class="floatingbuttons">';
    $output .= '<button onclick="addItem()">Add Item</button>';
    $output .= '<button onclick="addColumn()" class="floatbutton">Add Column</button>';
    $output .= '<button type="button" id="saveButton" class="floatbutton">Save</button>';
    $output .= '<span id="saveMessage" class="floatbutton"></span>';
    $output .= '</div>';



    // Generate table headers
    $output .= '<table id="data-table" border="1"><thead><tr>';
    
    foreach ($items[0]->ColumnHeader() as $field) {
        $type = $field['type']; // 'simple' or 'complex' type

        // Generate header with editable text and a type dropdown
        $output .= '<th>';
        $output .= '<div class="th-container">';
        $output .= '<input type="text" value="' . (string)$field['column'] . '" class="editable-cell">';
        $output .= '<select name="type_' . $field['column'] . '" class="type-selector" onchange="updateColumnType(this)">';
        $output .= '<option value="simple" ' . ($type == 'simple' ? 'selected' : '') . '>Simple</option>';
        $output .= '<option value="buildings" ' . ($type == 'buildings' ? 'selected' : '') . '>Building</option>';
        $output .= '<option value="units" ' . ($type == 'units' ? 'selected' : '') . '>Units</option>';
        $output .= '<option value="resources" ' . ($type == 'resources' ? 'selected' : '') . '>Resource</option>';
        $output .= '<option value="lands" ' . ($type == 'lands' ? 'selected' : '') . '>Lands</option>';
        $output .= '</select>';
        $output .= '</div>';
        $output .= '</th>';
    }
    $output .= '</tr></thead><tbody>';

    // Generate table rows
    foreach ($items as $item) {
        $output .= '<tr>';
        
        $rowData = $item->rowData();

        foreach ($rowData as $field) {
            if (is_array($field)) {
                // Complex field: name, value, and controls
                $output .= '<td class="input-wrapper">';
                $output .= '<div class="field-container">';

	                // var_dump($rowData);
	                // echo('============================<br/>');

                foreach ($field as $subField) {
                    $output .= '<div class="complex-field">';

                    $output .= '<button type="button" class="row-btn" onclick="removeSubfield(this.closest(\'div\'))">-</button>';

	                $output .= '<select class="editable-name">';
	                $list = []; // Default empty list

	                // Determine which list to use based on column type
	                switch ($subField['class']) { // Assuming 'type' is provided for the field
	                    case 'units':
	                        $list = $unitsList;
	                        break;
	                    case 'buildings':
	                        $list = $buildingsList;
	                        break;
	                    case 'resources':
	                        $list = $resourcesList;
	                        break;
	                    case 'lands':
	                        $list = $landsList;
	                        break;
	                    default:
	                        $list = []; // Empty for 'simple' or undefined types
	                        break;
	                }

	                // Populate the dropdown
	                // $selected = '';

	                // var_dump($subField);
	                // echo('_______________________________<br/>');

	                
	                $output .= '<option value=""></option>';

	                foreach ($list as $name) {

	                    $selected = ((string)$subField['column'] == $name) ? 'selected' : '';

	                // var_dump($selected);
	                // echo('_______________________________<br/>');

	                    $output .= '<option value="' . $name . '" ' . $selected . '>' . $name . '</option>';
                	}

                	$output .= '</select>';
                    $output .= '<input type="text" value="' . (string)$subField['value'] . '" class="editable-value">';
                    $output .= '</div>';
                }

                $output .= '</div>';
                $output .= '<button type="button" class="row-btn" onclick="addSubfield(this, \''.$subField['class'].'\')">+</button>';
                // $output .= '<button type="button" class="add-field-btn" data-type="'.$subField['class'].'">+</button>';
                $output .= '</td>';
            } else {
                // Simple field: single input
                $output .= '<td><input type="text" value="' . (string)$field . '" class="editable-cell"></td>';
            }
        }
        $output .= '</tr>';
    }
    $output .= '</tbody></table>';

    echo $output;
}
