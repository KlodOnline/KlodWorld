<?php
/* =============================================================================
    xml_rules.php
    Load Rules for GUI by CSS & Javascript injections !

    - This code is a mix of JS & PHP and should be the only one in the frontend
    who behave this way, because it's really ugly and hard to maintain.

============================================================================= */
include_once __DIR__.'/frontend_init.php';
$ruleManager = new XMLObjectManager();

?>

<style>
	/* Placeholder for Eventual CSS injections */
</style>

<script type="text/javascript">

	function getColorFromType(type) {
		const colors = {
<?php
        $items = $ruleManager->allItems('lands');
$toEcho = implode(',', array_map(function ($item) {
    return " ".$item->__get('id').": '".$item->__get('rgb')."'";
}, $items));
echo $toEcho;
?>
    	};
    	return colors[type] || '#000'; 
	}

	function getPngUrlForGroundType(type, num = 0) {
		const urls = {
<?php
        $items = $ruleManager->allItems('lands');
$toEcho = implode(',', array_map(function ($item) {
    return " ".$item->__get('id').": '".$item->__get('url')."'";
}, $items));
echo $toEcho;
?>
    	};
		// Remplacement de # par num (si présent)
		let url_pattern = urls[type] || ".\\h\\99.png"; // Valeur par défaut
		return url_pattern.replace("#", num);
	}

	/* -----------------------------------------------------------------------------
		Constant for ALL JS GUI.

	----------------------------------------------------------------------------- */
	const Rules = {

		/* -------------------------------------------------------------------------
			Simple Values
		------------------------------------------------------------------------- */
		tick: <?php echo TIC_SEC ?>,
		max_col: <?php echo MAX_COL; ?>,
		max_row: <?php echo MAX_ROW; ?>,
		website: '<?php echo WEBSITE; ?>',

		/* -------------------------------------------------------------------------
			Complex Arrays
		------------------------------------------------------------------------- */
		colors: {
			"Ba": '#000000',
			"Rb": '#6E0000',
			"Wb": '#8F4800',
			"Or": '#FF7009',
			"Cr": '#F0002A',
			"Da": '#FFEA00',
			"Db": '#9E7F61',
			"Lf": '#267843',
			"Kg": '#34B944',
			"Nb": '#1ED9FF',
			"Te": '#22BDAC',
			"Db": '#00005F',
			"Bl": '#2048DE',
			"Hm": '#FD0ACD',
			"Pi": '#FC97C2',
			"Dl": '#8F72A4'
		},

		/* All Lands informations & definitions --------------------------------- */
		lands: [
<?php
            $items = $ruleManager->allItems('lands');
$toEcho = implode(', ', array_map(function ($item) {
    return '{'.$item->javascriptData().'}';
}, $items));
echo $toEcho;
?>
		],

		/* All Units informations & definitions --------------------------------- */
		units: [
<?php
            $items = $ruleManager->allItems('units');
$toEcho = implode(', ', array_map(function ($item) {
    return '{'.$item->javascriptData().'}';
}, $items));
echo $toEcho;
?>
		],

		/* -------------------------------------------------------------------------
			Functions : Access to XML Object properties (Generic)
		------------------------------------------------------------------------- */
		getObjectProperty(groupName, targetId, property = null) {
		    // targetId = String(targetId);

		    // Vérifie si le groupe existe dans Rules
		    if (!(groupName in this)) {
		        console.log(`Group "${groupName}" not found in <Rules>`);
		        return null;
		    }

		    // Récupère le groupe d'objets
		    let group = this[groupName];

		    // Vérifie si le groupe est un tableau
		    if (!Array.isArray(group)) {
		        console.log(`Group "${groupName}" is not an array`);
		        return null;
		    }

		    // Cherche l'objet correspondant
		    let result = group.find(item => item.id === targetId);

		    if (!result) {
		        console.log(`Object with ID "${targetId}" not found in group "${groupName}"`);
		        return null;
		    }

		    // Si aucune propriété spécifique n'est demandée, retourne toutes les propriétés
		    if (property === null) {
		        return result;
		    }

		    // Vérifie si la propriété existe dans l'objet
		    if (!(property in result)) {
		        console.log(`Property "${property}" does not exist on object with ID "${targetId}"`);
		        return null;
		    }

		    // Retourne la valeur de la propriété demandée
		    return result[property];
		},

		/* -------------------------------------------------------------------------
			Functions : Access to XML Object properties (Specific)
		------------------------------------------------------------------------- */
		getLandUrl(targetId, num = 0) {
		    let url = this.getObjectProperty('lands', targetId, 'url');
		    if (!url) { console.log(`No URL defined for land with ID "${targetId}"`); }
		    return url.replace("#", num); // Remplace `#` par `num` dans l'URL
		},
		getLandColor(targetId) {
		    return this.getObjectProperty('lands', targetId, 'rgb');
		},
		getLandInfo(targetId) {
		    return this.getObjectProperty('lands', targetId);
		}
	};
</script>
