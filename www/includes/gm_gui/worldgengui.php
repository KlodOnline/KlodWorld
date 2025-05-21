<?php
include_once __DIR__.'/gm_init.php';

$ruleManager = new XMLObjectManager();

?>

<style>
	/* Placeholder for CSS injections */
</style>

<script type="text/javascript">
	function getColorFromType(type) {
		const colors = {
<?php
		$items = $ruleManager->allItems('lands');
		$toEcho = implode(',', array_map(function($item) {
	    	return " ".$item->__get('id').": '".$item->__get('rgb')."'";
		}, $items));
		echo $toEcho;
?>
    	};
    	return colors[type] || '#000'; 
	}
</script>

<script src="./js/gm_gui/worldgen.js"></script>

<h2>World Generator v0.00</h2>
<div class="worldbuttons">
	<button onclick="FullCreation()">Full Creation</button>
    <button onclick="loadWorld()">Load</button>
    <button onclick="clearWorld()">Clear</button>
    <button onclick="createTectonicPlates()">Tectonic Plates</button>
    <button onclick="Humidity()">Humidity</button>
    <button onclick="LatitudeClimate()">Climatic variations</button>
    <button onclick="Rivers()">Rivers</button>
    <button onclick="DesertsCoasts()">Deserts & Coasts</button>

    <div id="progress-bar-container">
    	<div id="progress-bar"></div>
    </div>
    <div id="loading-indicator" style="display:none; position:fixed; top:40%; left:50%; transform:translateX(-50%); background:#000; color:#fff; padding:10px; border-radius:5px; z-index:1000; font-weight:bold;">
    Waiting...
	</div>

</div>
</div>

<div id="worldmap">
	
</div>

<canvas id="gridCanvas"></canvas>
