<?php
/* =============================================================================
    XMLObject Class
    - Exposes an object derived from XML data.
============================================================================= */
class GRXMLAttribute {
    public $column;   // Nom du champ (par exemple, "name", "requisite", etc.)
    public $class;  // Classe du champ (par exemple, "unit", "building", "resource", "land", etc.)
    public $value;  // Valeur du champ (par exemple, "SoldierMonk", 10, true/false, etc.)

    // Constructeur pour initialiser un attribut
    public function __construct($column, $class, $value) {
        $this->column = $column;
        $this->class = $class;
        $this->value = $value;
    }

    public function javascriptData() {
    	$output = '';
        $output .= '"'.$this->column.'":';
        // Si la valeur est un tableau d'objets, on appelle leur dumpData()
        if (is_array($this->value)) {
        	$output .='{';
	        // Iterate over all the properties (including nested objects)
			$output .= implode(',', array_map(function($subItem) {
		    	return $subItem->javascriptData();
			}, $this->value));
			$output .='}';
        } else {
        	// Sinon, c'est une valeur simple qu'on encode correctement
			$value = (string) $this->value;
       		if (!is_numeric($value)) { $value = '"'.$value.'"'; }
        	$output .= $value;
        }
        return $output;
    }
}

class XMLObject {

    private $data = [];

    /* -------------------------------------------------------------------------
        Constructor to initialize the XMLObject object with data.
        
        @param SimpleXMLElement $itemData The XML data to hydrate the object.
    ------------------------------------------------------------------------- */
	public function __construct($itemData) {
        foreach ($itemData as $key => $value) {
        	// Si un element n'a pas d'attribut, on fait simple :
        	if (!$value->attributes()) {
        		$attr = new GRXMLAttribute($key, '', $value);
        	} else {
        		$valueArray = [];
        		// $value should have only one attribute...
        		foreach ($value->attributes() as $attrKey => $attrValue) {
        		}
            	if ($value->count() > 0) {
	                foreach ($value as $subKey => $subValue) {
        				$valueArray[] = new GRXMLAttribute($subKey, '', $subValue);
	                }        		
            	}
        		$attr = new GRXMLAttribute($key, $class=$attrValue, $valueArray);
        	}
        	$this->__set($key, $attr);
		}
    }

    /* -------------------------------------------------------------------------
        Magic method __get to retrieve dynamic properties.
        
        @param string $column The name of the property.
        @return mixed The value of the property or null if not set.
    ------------------------------------------------------------------------- */
    public function __get($column) {
		return isset($this->data[$column]) ? $this->data[$column]->value : null;
    }

    /* -------------------------------------------------------------------------
        Magic method __set to set dynamic properties.
        
        @param string $column The name of the property.
        @param mixed $value The value to set.
    ------------------------------------------------------------------------- */
    public function __set($column, $value) {
        $this->data[$column] = $value;
    }
    /* -------------------------------------------------------------------------
        Method to show data to JS
    ------------------------------------------------------------------------- */
    public function javascriptData() {
        $output = "";
        // Iterate over all the properties (including nested objects)
		$output = implode(',', array_map(function($item) {
	    	return $item->javascriptData();
		}, $this->data));
        return $output;
    }

    /* -------------------------------------------------------------------------
        Method to dump data for debugging purposes.
    ------------------------------------------------------------------------- */
    public function dumpData() {
        $output = "<pre>";
        $output .= "Dumping Data:\n";
        
        // Iterate over all the properties (including nested objects)
        foreach ($this->data as $item) {
        	$item->dumpData();
        }
        $output .= "</pre>";
        return $output;
    }

    /* -------------------------------------------------------------------------
		rowData
    ------------------------------------------------------------------------- */
	public function rowData() {
	    $rowData = [];
	    if (!empty($this->data)) {

	        foreach ($this->data as $item) {
	            // Vérification si l'élément est de type simple ou complexe

	        	// var_dump($item);
	        	// var_dump($item->dumpData());
	        	

	            if (is_array($item->value)) {

	                // Pour les types complexes, renvoyer un tableau contenant "nom" et "valeur"
	                $complexData = [];

	                foreach ($item->value as $subItem) {

	                	// echo ('##'.$item->class.'##');

	                    // Structure des données complexes avec nom et valeur
	                    $complexData[] = [
	                        'column' => $subItem->column,  // Nom de l'attribut
	                        'value' => $subItem->value, // Valeur de l'attribut
	                        'class' => $item->class
	                    ];
	                }

	                // Ajout d'une cellule vide pour eviter des erreurs
	                $complexData[] = [
	                	'column' => '',  // Nom de l'attribut
	                    'value' => '', // Valeur de l'attribut
	                    'class' => $item->class
					];

	                $rowData[] = $complexData;

	            } else {
	                // Pour les types simples, on ajoute directement la valeur
	                $rowData[] = $item->value;
	            }
	        }
	        return $rowData;
	    }
	}

    /* -------------------------------------------------------------------------
    	ColumnHeader
    ------------------------------------------------------------------------- */
	public function ColumnHeader() {
	    $headers = [];
	    if (!empty($this->data)) {
	        foreach ($this->data as $item) {
	            // Vérifier si la colonne est simple ou complexe
	            $type = $this->determineColumnType($item);
	            $headers[] = [
	                'column' => $item->column,
	                'type' => $type
	            ];
	        }
	        return $headers;
	    }

	    // Si aucune donnée n'est présente, retour d'un tableau vide
	    return [];
	}

    /* -------------------------------------------------------------------------
		Nouvelle méthode pour déterminer si la valeur est simple ou complexe
    ------------------------------------------------------------------------- */
	private function determineColumnType($item) {
	    if ($item->class!='') {return $item->class;}
	    // Si c'est un type simple (string, int, etc.), c'est simple
	    return 'simple';
	}
}
