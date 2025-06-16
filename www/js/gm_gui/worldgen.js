/* =============================================================================
    gm_worldgen.js
    -
============================================================================= */

/* -----------------------------------------------------------------------------
	Progress Bar ...
----------------------------------------------------------------------------- */
function showBar() {
	const container = document.getElementById("progress-bar-container");
    container.style.display = "block";
    updateBar(0);
}

function updateBar(percent) {
	if (percent>100 || percent<0) {return;}
	const bar = document.getElementById("progress-bar");
    bar.style.width = (percent+'%');
}

function deleteBar() {
	const container = document.getElementById("progress-bar-container");
	container.style.display = "none";
}

/* -----------------------------------------------------------------------------
	Actions from buttons
----------------------------------------------------------------------------- */
function FullCreation() { ajaxExecutor('FullCreation'); }
function createTectonicPlates() { ajaxExecutor('createTectonicPlates'); }
function loadWorld() 	{ ajaxExecutor('loadWorld'); }
function clearWorld() 	{ ajaxExecutor('clearWorld'); }
function Humidity() 	{ ajaxExecutor('Humidity'); }
function LatitudeClimate() 	{ ajaxExecutor('LatitudeClimate'); }
function Rivers() 	{ ajaxExecutor('Rivers'); }
function DesertsCoasts() 	{ ajaxExecutor('DesertsCoasts'); }
function LessBoring() 	{ ajaxExecutor('LessBoring'); }

/* -----------------------------------------------------------------------------
	Aja Call & Page Refresh
----------------------------------------------------------------------------- */
function ajaxExecutor(actionToDo, params=[]) {
    
    // Affiche un indicateur "Waiting..."
    const loadingIndicator = document.getElementById("loading-indicator");
    loadingIndicator.style.display = "block";
    loadingIndicator.textContent = "Waiting..."; // Texte que tu veux afficher

	$.ajax({
    	url: 'includes/gm_gui/worldgen_io.php',
        type: 'GET',
        data: { action: actionToDo},
        success: function (response) {
			// worldgen_io.php renvoie directement la grille des terrains !
            // On la converti en table HTML et on la place dans le bon div
        	console.log('Data received ! Drawing .... ');
        	const canvas = document.getElementById('gridCanvas');

			generateHexCanvasGrid(canvas, response, 2);

			// Cache l'indicateur après le succès
            loadingIndicator.style.display = "none";

		},
        error: function (xhr, error) {
        	$('#worldmap').html('<p>(WORLDGEN) Error loading data.' + error + '</p>');
            console.log(error);
            console.log(xhr.responseText);
		}
	});
}

function drawHexagon(ctx, x, y, size, color) {
	size = size - (size / 20);
    const angleStep = Math.PI / 3; // Un hexagone a 6 côtés
    ctx.beginPath();
    for (let i = 0; i < 6; i++) {
        const angle = angleStep * i - Math.PI / 6; // Décalage de -30° pour avoir la pointe vers le haut
        const xOffset = Math.cos(angle) * size;
        const yOffset = Math.sin(angle) * size;
        if (i === 0) {
            ctx.moveTo(x + xOffset, y + yOffset);
        } else {
            ctx.lineTo(x + xOffset, y + yOffset);
        }
    }
    ctx.closePath();
    ctx.fillStyle = color;
    ctx.fill();
}


function generateHexCanvasGrid(canvas, coordinates, hexSize = 10) {
    const ctx = canvas.getContext('2d');
    if (!coordinates || !coordinates.length) {
        console.error("No coordinates to generate the grid!");
        return;
    }

    const indexedCoords = indexCoordinates(coordinates);

    const maxCol = Math.max(...coordinates.map(coord => coord.col));
    const maxRow = Math.max(...coordinates.map(coord => coord.row));

    // Ajuster la taille du canvas
    // SIZE => "Rayon" de l'hexagone.
    // Hauteur hex = size*2 /////  Largeur hex = sqrt(3)*size
    const hexWidth = Math.sqrt(3) * hexSize; // Largeur d'un hexagone
    const hexHeight = 2 * hexSize; // Hauteur d'un hexagone

    const vertOffset = hexHeight * 0.75; // Décalage vertical entre deux lignes

    canvas.width = maxCol * hexWidth + hexWidth*1.5;
    canvas.height = maxRow * vertOffset + hexHeight;

    ctx.fillStyle = 'black';
	ctx.fillRect(0, 0, canvas.width, canvas.height);


    let currentRow = maxRow;
	function updateRowProgress() {
		// Because Leaflet inverts y in a mirror-like fashion for some reason, 
		// rather than correcting it, I've simply inverted the display from here.
	    if (currentRow >= 0 ) {
	        for (let currentCol = 0; currentCol <= maxCol; currentCol++) {
	            const cellKey = `${currentRow}-${currentCol}`;
	            const cellType = indexedCoords.get(cellKey) || 'defaultType';
	            
	            // console.log(cellKey);
	            // console.log(cellType);

	            const cellColor = getColorFromType(cellType);
	            // Calcul des coordonnées pour le centre de l'hexagone
	            let x = (currentCol * hexWidth) + hexWidth / 2;
	            const y = ( (maxRow - currentRow) * vertOffset) + hexHeight / 2;  // Inverser seulement y
	            // Décaler les colonnes impaires
	            if (currentRow % 2 !== 0) { x += hexWidth / 2; }
	            drawHexagon(ctx, x, y, hexSize, cellColor);
	        }
	        currentRow-- // Décrémenter currentRow au lieu de l'incrémenter
	        requestAnimationFrame(updateRowProgress);
	    } else {
	        console.log("Hexagonal canvas grid generation completed!");
	    }
	}

    updateRowProgress(); // Démarrer le processus
}

function generateCanvasGrid(canvas, coordinates, cellSize = 10) {
    const ctx = canvas.getContext('2d');
    if (!coordinates || !coordinates.length) {
        console.error("No coordinates to generate the grid!");
        return;
    }

    const indexedCoords = indexCoordinates(coordinates);

    const maxCol = Math.max(...coordinates.map(coord => coord.col));
    const maxRow = Math.max(...coordinates.map(coord => coord.row));

    // Ajuste la taille du canvas
    canvas.width = (maxCol + 3) * cellSize;
    canvas.height = (maxRow + 3) * cellSize;

    let currentRow = 0;

    function updateRowProgress() {
        if (currentRow <= maxRow) {
            for (let currentCol = 0; currentCol <= maxCol; currentCol++) {
                const cellKey = `${currentRow}-${currentCol}`;
                const cellType = indexedCoords.get(cellKey) || 'defaultType';
                const cellColor = getColorFromType(cellType);

                // Décalage pour les lignes impaires
                const offsetX = (currentRow % 2 !== 0) ? cellSize / 2 : 0;

                ctx.fillStyle = cellColor;
                ctx.fillRect(
                    (currentCol * cellSize) + offsetX,
                    currentRow * cellSize,
                    cellSize,
                    cellSize
                );
            }
            currentRow++;
            requestAnimationFrame(updateRowProgress);
        } else {
            console.log("Canvas grid generation completed!");
        }
    }

    updateRowProgress(); // Démarrer le processus
}

function indexCoordinates(coordinates) {
    const indexedCoords = new Map();
    coordinates.forEach(coord => {
        indexedCoords.set(`${coord.row}-${coord.col}`, coord.ground_type);
    });
    return indexedCoords;
}
