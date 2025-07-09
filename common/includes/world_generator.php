<?php

/* =============================================================================
    World Generator
    - to be used by others to randomly generate world lands
    - must be autonomous on main functions
    - each we use coord, it MUST BE a coord object from hexalib.php !!!

============================================================================= */
class WorldGenerator
{
    private $board;
    private $hexalib;
    private $attribution;
    private array $climate_lines = [];
    private array $land = [];
    private array $land_id_to_name = [];

    /***************************************************************************
        Constructor: Initializes
    ***************************************************************************/
    public function __construct(Board $board)
    {
        $this->board = $board;
        $this->hexalib = $hexalib ?? new Hexalib();
        $polar_lat = round((MAX_ROW - 1) / 14);
        $tropic_lat = round((MAX_ROW - 1) / 8);
        $equator = round((MAX_ROW - 1) / 2);
        $this->climate_lines = [
            'equator' => $equator,
            'arctic_circle' =>  0 + $polar_lat,
            'tropic_north' => $equator - $tropic_lat,
            'tropic_south' => $equator + $tropic_lat,
            'antarctic_circle' => (MAX_ROW - 1) - $polar_lat
        ];
        $ruleManager = new XMLObjectManager();
        $lands = $ruleManager->allItems('lands');
        $this->land = [];
        foreach ($lands as $item) {
            $name = (string) $item->__get('name');
            $id   = (int) $item->__get('id');
            $this->land[$name] = $id;
            // Not sure if usefull, will have to check in the end
            $this->land_id_to_name[$id] = $name;
        }
    }

    /***************************************************************************
        Frontend Service
    ***************************************************************************/
    public function loadWorld()
    {
        $this->board->loadGrounds();
    }
    public function saveWorld()
    {
        $this->board->saveCollection('Ground', false);
    }
    public function FullCreation()
    {
        // each of those functions are also directly usable by Front, for testing purpose
        $this->clearWorld(true, false);
        $this->createTectonicPlates(false, false, null);
        $this->humidity(false, false);
        $this->lessBoring(false, false);
        $this->latitudeClimate(false, false);
        $this->rivers(false, false);
        $this->desertsCoasts(false, true);
    }

    /***************************************************************************
        External Interactions (with Board)
    ***************************************************************************/
    public function getGround($col, $row)
    {
        $ground = $this->board->getGround((int) $col, (int) $row);
        return $ground;
    }
    public function setGround(int $col, int $row, int $type): void
    {
        if ($row < 0 || $row >= MAX_ROW) {
            return;
        }
        $col = ($col % MAX_COL + MAX_COL) % MAX_COL; // Wrap propre
        $this->board->setGroundTypeByCoords($col, $row, $type);
    }
    /* -------------------------------------------------------------------------
        More way to use setGround functions
    ------------------------------------------------------------------------- */
    public function setGroundFromCoord(HexCoordinate $coord, int $type): void
    {
        if ($coord instanceof Oddr) {
            $oddr = $coord;
        } else {
            $oddr = $this->hexalib->convert($coord, 'Oddr');
        }
        $this->setGround($oddr->col, $oddr->row, $type);
    }
    public function setGroundsFromCoords(array $coords, int $type): void
    {
        foreach ($this->hexalib->uniqueCoords($coords) as $coord) {
            $this->setGroundFromCoord($coord, $type);
        }
    }
    public function setSpiralGroundsFromCoords(array $coords, int $type, int $radius): void
    {
        $all_coords = [];
        foreach ($coords as $coord) {
            $spiral_coords = $this->hexalib->spiral($coord, $radius);
            array_push($all_coords, ...$spiral_coords); // Faster than array_merge
        }
        $this->setGroundsFromCoords($all_coords, $type);
    }
    public function setGroundSpiral(int $col, int $row, int $radius, int $type): void
    {
        $coord = $this->hexalib->coord(['col' => $col, 'row' => $row], 'Oddr');
        $this->setSpiralGroundsFromCoords([$coord], $type, $radius);
    }

    /* -------------------------------------------------------------------------
        Converting Grounds from array
    ------------------------------------------------------------------------- */
    public function convertGround(HexCoordinate $coord, array|int $from_lands, int $to_land): bool
    {
        $from_lands = is_array($from_lands) ? $from_lands : [$from_lands];
        $ground = $this->getGround($coord->col, $coord->row);
        if ($ground === null) {
            return false;
        }
        $ground_type = $ground->getGroundType();
        if (in_array($ground_type, $from_lands, true)) { // Strict comparison
            $this->setGroundFromCoord($coord, $to_land);
            return true;
        }
        return false;
    }
    public function convertGrounds(array $coords, array|int $from_lands, int $to_land): void
    {
        $from_lands = is_array($from_lands) ? $from_lands : [$from_lands];
        foreach ($this->hexalib->uniqueCoords($coords) as $coord) {
            $ground = $this->getGround($coord->col, $coord->row);
            if ($ground !== null && in_array($ground->getGroundType(), $from_lands, true)) {
                $this->setGroundFromCoord($coord, $to_land);
            }
        }
    }
    public function convertGroundSpiral(HexCoordinate $center, int $radius, array|int $from_lands, int $to_land): void
    {
        $this->convertGrounds($this->hexalib->spiral($center, $radius), $from_lands, $to_land);
    }

    /* -------------------------------------------------------------------------
        Drawing Functions !!!!
    ------------------------------------------------------------------------- */
    public function drawSimpleLine($terrain, $coord1, $coord2)
    {
        $coords_line = $this->hexalib->drawLine($coord1, $coord2);
        $this->setGroundsFromCoords($coords_line, $terrain);
    }
    public function drawNoisyLine($terrain, $coord1, $coord2)
    {
        $coords_line = $this->hexalib->generateNoisyLine($coord1, $coord2, 4, 10);
        $this->setGroundsFromCoords($coords_line, $terrain);
    }
    public function widenTerrain(int $land_type_id, int $land_type_to_do, array $land_to_crush, int $thickness)
    {
        // widen a specifi terrain type, with randomness, onto an array of terrain to crush
        $visited = [];
        $current = [];
        // Get all interesting grounds
        $this->board->forEachGround(function ($col, $row) use (&$current, $land_type_id) {
            $ground = $this->getGround($col, $row);
            if ($ground && $ground->getGroundType() === $land_type_id) {
                $current[] = [$col, $row];
            }
        });
        for ($n = $thickness; $n > 0; $n--) {
            $next = [];
            foreach ($current as [$col, $row]) {
                $neighbors = $this->board->getGroundNeighborOfTypes([$land_type_id, $land_type_to_do], $col, $row, null, true);
                foreach ($neighbors as $neighbor) {
                    $nCol = $neighbor->col;
                    $nRow = $neighbor->row;
                    $key = "$nCol|$nRow";
                    if (isset($visited[$key])) {
                        continue;
                    }
                    // Probabilité décroissante :
                    $chances = round(100 * $n / ($thickness + 1));
                    if (mt_rand(1, 100) < $chances) {
                        $neighbor_type = $neighbor->getGroundType();
                        // Not allowed to crush ? Don't do it !
                        if (in_array($neighbor_type, $land_to_crush, true)) {
                            $this->setGround($neighbor->col, $neighbor->row, $land_type_to_do);
                        }
                        $next[] = [$nCol, $nRow];
                        $visited[$key] = true;
                    }
                }
            }
            $current = $next;
        }
    }
    public function deleteSmallerThan($terrainName, $minSize, $newTerrainName)
    {
        $landTypeId = $this->land[$terrainName] ?? null;
        $newLandTypeId = $this->land[$newTerrainName] ?? null;
        if ($landTypeId === null || $newLandTypeId === null) {
            error_log("Terrain name not found: $terrainName or $newTerrainName");
            return;
        }
        $visited = [];
        $areasToProcess = [];
        // Trouver toutes les cases du terrain spécifié
        $this->board->forEachGround(function ($col, $row) use ($landTypeId, &$visited, &$areasToProcess) {
            $key = "$col,$row";
            if (isset($visited[$key])) {
                return;
            }
            $ground = $this->getGround($col, $row);
            if ($ground && $ground->getGroundType() === $landTypeId) {
                $area = $this->findConnectedArea($col, $row, $landTypeId, $visited);
                if (!empty($area)) {
                    $areasToProcess[] = $area;
                }
            }
        });
        // Traiter chaque région trouvée
        foreach ($areasToProcess as $area) {
            if (count($area) < $minSize) {
                foreach ($area as $coord) {
                    list($col, $row) = $coord;
                    $this->setGround($col, $row, $newLandTypeId);
                }
            }
        }
    }
    private function findConnectedArea($startCol, $startRow, $landTypeId, &$visited)
    {
        $area = [];
        $queue = [[$startCol, $startRow]];
        while (!empty($queue)) {
            list($col, $row) = array_shift($queue);
            $key = "$col,$row";
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;
            $area[] = [$col, $row];
            // Obtenir tous les voisins du même type sans conversion hexagonale
            $neighbors = $this->board->getGroundNeighborOfTypes([$landTypeId], $col, $row);
            foreach ($neighbors as $neighbor) {
                // Supposons que $neighbor est un objet Ground avec des méthodes getCol()/getRow()
                try {
                    $nCol = $neighbor->col;
                    $nRow = $neighbor->row;
                    if (!isset($visited["$nCol,$nRow"])) {
                        $queue[] = [$nCol, $nRow];
                    }
                } catch (Exception $e) {
                    error_log("Error processing neighbor: " . $e->getMessage());
                    continue;
                }
            }
        }
        return $area;
    }

    /* -------------------------------------------------------------------------
        Clear the world (full plains)
    ------------------------------------------------------------------------- */
    public function clearWorld(bool $load = true, bool $save = true): void
    {
        logMessage('Clearing World ...');
        if ($load) {
            $this->loadWorld();
        }
        $all_coords = $this->generateAllMapCoords();
        $this->setGroundsFromCoords($all_coords, $this->land['plain']);
        $this->removeOutOfBoundsGrounds();
        if ($save) {
            $this->saveWorld();
        }
        logMessage('Clearing done !');
    }
    private function generateAllMapCoords(): array
    {
        $coords = [];
        for ($row = 0; $row < MAX_ROW; $row++) {
            for ($col = 0; $col < MAX_COL; $col++) {
                $coords[] = $this->hexalib->coord([$col, $row], 'Oddr');
            }
        }
        return $coords;
    }
    private function removeOutOfBoundsGrounds(): void
    {
        $to_delete = [];
        $this->board->forEachGround(function ($col, $row) use (&$to_delete) {
            if ($col < 0 || $col >= MAX_COL || $row < 0 || $row >= MAX_ROW) {
                $to_delete[] = $this->board->getGround($col, $row);
            }
        });
        foreach ($to_delete as $ground) {
            $this->board->deleteFromCollection($ground);
        }
    }

    /* -------------------------------------------------------------------------
        BREAKS TERRAIN MONOTONY

        Targets uniform zones of:
        - Ocean/forest/plains within 8-hex radius

        Actions:
        - Forest zones → Adds mountains/swamps
        - Plains zones → Adds forests/mountain ranges
        - Ocean zones → No modification (configurable)

        Splat System:
        - Core radius: 3-5 hexes
        - Nested terrain layers
        - Random pattern selection
    ------------------------------------------------------------------------- */
    public function lessBoring(bool $load = true, bool $save = true): void
    {
        if ($load) {
            $this->loadWorld();
        }
        $boring_radius = 8;
        $boring_types = [
            $this->land['ocean'],
            $this->land['forest'],
            $this->land['plain']
        ];
        $this->board->forEachGround(function ($curr_col, $curr_row) use ($boring_types, $boring_radius) {
            $ground = $this->getGround($curr_col, $curr_row);
            $ground_type = $ground->getGroundType();
            if (!in_array($ground_type, $boring_types)) {
                return;
            }
            $center = $this->hexalib->coord([$curr_col, $curr_row], 'Oddr');
            if ($this->isUniformArea($center, $ground_type, $boring_radius)) {
                $this->applyTerrainVariation($center, $ground_type, $boring_radius);
            }
        });
        if ($save) {
            $this->saveWorld();
        }
    }
    private function isUniformArea(Oddr $center, int $expected_type, int $radius): bool
    {
        $coords = $this->hexalib->spiral($center, $radius);
        foreach ($coords as $coord) {
            $oddr = $this->hexalib->convert($coord, 'Oddr');
            $neighbor = $this->getGround($oddr->col, $oddr->row);
            if (!$neighbor || $neighbor->getGroundType() !== $expected_type) {
                return false;
            }
        }
        return true;
    }
    private function applyTerrainVariation(Oddr $center, int $ground_type, int $radius): void
    {
        $splat_radius = rand((int)($radius / 1.2), (int)($radius / 2.5));
        $variations = [
            $this->land['forest'] => function () use ($center, $splat_radius) {
                $this->applyForestVariation($center, $splat_radius);
            },
            $this->land['plain'] => function () use ($center, $splat_radius) {
                $this->applyPlainsVariation($center, $splat_radius);
            }
        ];
        if (isset($variations[$ground_type])) {
            $variations[$ground_type]();
        }
    }
    private function applyForestVariation(Oddr $center, int $radius): void
    {
        $choice = rand(0, 1) ? 'mountain' : 'swamp';
        if ($choice === 'mountain') {
            $this->setGroundsFromCoords(
                $this->hexalib->noisySpiral($center, $radius),
                $this->land['forestHill']
            );
            $this->setGroundsFromCoords(
                $this->hexalib->noisySpiral($center, (int)($radius * 0.5)),
                $this->land['mountain']
            );
        } else {
            $this->setGroundsFromCoords(
                $this->hexalib->noisySpiral($center, (int)($radius * 0.6)),
                $this->land['swamp']
            );
        }
    }
    private function applyPlainsVariation(Oddr $center, int $radius): void
    {
        $variants = [
            'forest' => fn () => $this->setGroundsFromCoords(
                $this->hexalib->noisySpiral($center, $radius),
                $this->land['forest']
            ),
            'mountain' => fn () => $this->mountainVariation($center, $radius),
            'forest_hill' => fn () => $this->forestHillVariation($center, $radius)
        ];
        $variant = array_rand($variants);
        $variants[$variant]();
    }
    private function mountainVariation(Oddr $center, int $radius): void
    {
        $this->setGroundsFromCoords(
            $this->hexalib->noisySpiral($center, $radius),
            $this->land['plainHill']
        );
        $this->setGroundsFromCoords(
            $this->hexalib->noisySpiral($center, (int)($radius * 0.5)),
            $this->land['mountain']
        );
    }
    private function forestHillVariation(Oddr $center, int $radius): void
    {
        $this->setGroundsFromCoords(
            $this->hexalib->noisySpiral($center, $radius),
            $this->land['forest']
        );
        $this->setGroundsFromCoords(
            $this->hexalib->noisySpiral($center, (int)($radius * 0.5)),
            $this->land['forestHill']
        );
        $this->setGroundsFromCoords(
            $this->hexalib->noisySpiral($center, (int)($radius * 0.25)),
            $this->land['mountain']
        );
    }

    /* -------------------------------------------------------------------------
        Desert, Coast, and various changes
    ------------------------------------------------------------------------- */
    public function DesertsCoasts($load = true, $save = true)
    {
        if ($load) {
            $this->loadWorld();
        }
        $this->updateCoastlines();
        $this->board->forEachGround(function ($curr_col, $curr_row) {
            $actual_ground = $this->getGround($curr_col, $curr_row);
            if (isset($actual_ground)) {
                $coord = $actual_ground->coord('Oddr');
                // Drawing deserts / anything @$desert_factor from savanna borders
                $desert_factor = 3;
                if ($actual_ground->getGroundType() == $this->land['savana'] or $actual_ground->getGroundType() == $this->land['savanaHill']) {
                    // If nothing in a radius of X is something else than savana, can be a desert.
                    if (count($this->board->getGroundNeighborOfTypes([
                        $this->land['savana'],
                        $this->land['savanaHill'],
                        $this->land['desert'],
                        $this->land['desertHill'],
                        $this->land['mountain'],
                        ], $coord->col, $coord->row, null, true, $desert_factor)) < 1) {
                        $this->convertGround($coord, [$this->land['savana']], $this->land['desert']);
                        $this->convertGround($coord, [$this->land['savanaHill']], $this->land['desertHill']);
                    }
                }
            }
        });
        if ($save) {
            $this->saveWorld();
        }
        return;
    }
    public function updateCoastlines(): void
    {
        $ocean_types = [$this->land['ocean']];
        $water_types = [$this->land['ocean'], $this->land['coast']];
        $coast_type = $this->land['coast'];
        $this->board->forEachGround(function ($col, $row) use ($ocean_types, $water_types, $coast_type) {
            $ground = $this->getGround($col, $row);
            // not ocean ? Exit.
            if (!$ground || !in_array($ground->getGroundType(), $ocean_types, true)) {
                return;
            }
            if ($this->board->hasNeighborOfTypes($col, $row, $water_types, null, true, 2)) {
                $this->setGround($col, $row, $coast_type);
            }
        });
    }

    /* -------------------------------------------------------------------------
        Wind & Humdity & Consequences !

        La ça va être super touchy, vu qu'on veux que des nuages qui se "chargent"
        en humidité, se "dechargent" sur les terre, en fonction de comment le
        vent les a poussé. Une terre humide favorise les forets !
        Hexalib Cardinal conv :
        Testé :
        0=E 1=NE 2=NW 3=W 4=SW 5=SE
    ------------------------------------------------------------------------- */
    public function windDirection($col, $row)
    {
        // Zone arctique :
        if ($row <= $this->climate_lines['arctic_circle']) {
            return 4;
        }
        // Zone Temperee nord :
        $temperate_zone_size = ($this->climate_lines['tropic_north'] - $this->climate_lines['arctic_circle']) / 3;
        if ($row > $this->climate_lines['arctic_circle'] and $row < ($this->climate_lines['arctic_circle'] + $temperate_zone_size)) {
            return 0;
        }
        if ($row >= ($this->climate_lines['arctic_circle'] + $temperate_zone_size) and $row < ($this->climate_lines['arctic_circle'] + $temperate_zone_size * 2)) {
            return 1;
        }
        if ($row >= ($this->climate_lines['arctic_circle'] + $temperate_zone_size * 2) and $row < ($this->climate_lines['tropic_north'])) {
            return northDirection($row);
        }
        // Zone tropicale nord :
        $tropical_zone_size = ($this->climate_lines['equator'] - $this->climate_lines['tropic_north']) / 3;
        if ($row >= $this->climate_lines['tropic_north'] and $row < ($this->climate_lines['tropic_north'] + $tropical_zone_size)) {
            return southDirection($row);
        }
        if ($row >= ($this->climate_lines['tropic_north'] + $tropical_zone_size) and $row < ($this->climate_lines['tropic_north'] + $tropical_zone_size * 2)) {
            return 4;
        }
        if ($row >= ($this->climate_lines['tropic_north'] + $tropical_zone_size * 2) and $row <= ($this->climate_lines['equator'])) {
            return 3;
        }
        // Zone tropicale sud :
        if ($row >= $this->climate_lines['equator'] and $row < ($this->climate_lines['equator'] + $tropical_zone_size)) {
            return 3;
        }
        if ($row >= ($this->climate_lines['equator'] + $tropical_zone_size) and $row < ($this->climate_lines['equator'] + $tropical_zone_size * 2)) {
            return 2;
        }
        if ($row >= ($this->climate_lines['equator'] + $tropical_zone_size * 2) and $row < ($this->climate_lines['tropic_south'])) {
            return northDirection($row);
        }
        // Zone Temperee sud :
        if ($row >= $this->climate_lines['tropic_south'] and $row < ($this->climate_lines['tropic_south'] + $temperate_zone_size)) {
            return southDirection($row);
        }
        if ($row >= ($this->climate_lines['tropic_south'] + $temperate_zone_size) and $row < ($this->climate_lines['tropic_south'] + $temperate_zone_size * 2)) {
            return 5;
        }
        if ($row >= ($this->climate_lines['tropic_south'] + $temperate_zone_size * 2) and $row < ($this->climate_lines['antarctic_circle'])) {
            return 0;
        }
        // Zone antarctique :
        if ($row >= $this->climate_lines['antarctic_circle']) {
            return 2;
        }
        return;
    }
    public function humidity($load = true, $save = true)
    {
        if ($load) {
            $this->loadWorld();
        }
        $base_water = round(MAX_COL / 10);
        $humidity_score = [];
        $wet_spots = [];
        logMessage('Finding Wet Spot');
        // First : Finding "wet_spots", places where rains helps forests (plains
        // under oceans winds...)
        $this->board->forEachGround(function ($curr_col, $curr_row) use (&$humidity_score, &$wet_spots, $base_water) {
            // if Current place is an ocean ...
            $actual_land = $this->getGround($curr_col, $curr_row);
            if ($actual_land !== null && $actual_land->getGroundType() == 1) {
                $direction = $this->windDirection($curr_col, $curr_row);
                if ($direction !== null) {
                    // Find what type of neighbor...
                    $neighbors = $this->board->getGroundNeighborOfTypes([$this->land['plain']], $curr_col, $curr_row, $direction);
                    // Correction 1 : Vérification du tableau non vide avant accès
                    if (!empty($neighbors)) {
                        $first_neighbor = $neighbors[0];
                        $wet_value = $base_water;
                        // Correction 2 : Clé unique plus robuste
                        $coord_key = sprintf('%d-%d', $first_neighbor->col, $first_neighbor->row);
                        $humidity_score[$coord_key] = $wet_value;
                        // Correction 3 : Conversion coord sécurisée
                        $wet_spots[] = $this->hexalib->coord([
                            $first_neighbor->col,
                            $first_neighbor->row
                        ], 'oddr');
                    }
                }
            }
        }, false);
        logMessage('Wet Spots Found '.count($wet_spots));
        $forests = [];
        foreach ($wet_spots as $coord) {
            $water = $humidity_score[$coord->col.'-'.$coord->row];
            $result = true;
            $forests[] = $coord;
            $humid_col = $coord->col;
            $humid_row = $coord->row;
            while ($result and $water > 0) {
                $direction = $this->windDirection($humid_col, $humid_row);
                if ($direction !== null) {
                    $oddr_coord = $this->hexalib->coord([$humid_col,$humid_row], 'Oddr');
                    $neighbour_coord = $this->hexalib->neighbour($oddr_coord, $direction);
                    $neighbour_coord->col = magicCylinder($neighbour_coord->col);
                    $target_land = $this->getGround($neighbour_coord->col, $neighbour_coord->row);
                    if ($target_land !== null) {
                        // Next hex in the loop :
                        $target_coord = $this->hexalib->neighbour($oddr_coord, $direction);
                        $target_coord->col = magicCylinder($target_coord->col);
                        $humid_col = $target_coord->col;
                        $humid_row = $target_coord->row;
                        if ($coord->row < 0 or $coord->row > MAX_ROW - 1) {
                            break;
                        }
                        $water = $water - 1;
                        if ($target_land->getGroundType() == $this->land['mountain']) {
                            $water = $water - 9;
                        }
                        if ($target_land->getGroundType() == $this->land['plainHill']) {
                            $water = $water - 4;
                        }
                        if ($target_land->getGroundType() == $this->land['ocean']) {
                            $water = $water + 5;
                        }
                        if ($water > $base_water) {
                            $water = $base_water;
                        }
                        if ($target_land->getGroundType() == $this->land['plain'] or $target_land->getGroundType() == $this->land['plainHill']) {
                            $forests[] = $target_coord;
                        }
                    } else {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            }
            $humidity_score[$coord->col.'-'.$coord->row] = 0;
        }
        $this->convertGrounds($forests, $this->land['plain'], $this->land['forest']);
        $this->convertGrounds($forests, $this->land['plainHill'], $this->land['forestHill']);
        $this->widenTerrain($this->land['forest'], $this->land['forest'], [$this->land['plain']], 3);
        if ($save) {
            $this->saveWorld();
        }
        return;
    }
    public function drawForests($forests, $reproduce = true, $rate_pct = 30)
    {
        $seeds = [];
        foreach ($forests as $forest_spot) {
            $this->convertGround($forest_spot, 8, 2);
            $this->convertGround($forest_spot, 3, 15);
            $possible_seeds = $this->hexalib->allNeighbours($forest_spot);
            foreach ($possible_seeds as $each_possibility) {
                $each_possibility->col = magicCylinder($each_possibility->col);
                if (rand(0, 100) <= $rate_pct) {
                    $seeds[] = $each_possibility;
                }
            }
        }
        return $seeds;
    }

    /* -------------------------------------------------------------------------
        Rivers !
            From a hill/moutain to rhe ocean...
    ------------------------------------------------------------------------- */
    public function rivers($load = true, $save = true)
    {
        if ($load) {
            $this->loadWorld();
        }
        // Finding sources
        $riverables = [2, 8, 9, 10, 12];
        $sources = [];
        $existing_rivers = [];
        $this->board->forEachGround(function ($curr_col, $curr_row) use (&$sources, $riverables) {
            $old_source_count = count($sources);
            $actual_land = $this->getGround($curr_col, $curr_row);
            if (isset($actual_land)) {
                // Depuis à côté d'une montagne, ou d'une colline humide
                if ($actual_land->getGroundType() == 4 or $actual_land->getGroundType() == 15 or $actual_land->getGroundType() == 16) {
                    $sources  = array_merge($sources, $this->board->getGroundNeighborOfTypes($riverables, $curr_col, $curr_row));
                }
                if ($actual_land->getGroundType() == 13) {
                    $existing_rivers[] = $actual_land;
                }
            }
        });
        // Shuffling allow a better "replayability"
        shuffle($sources);
        // Filtering too close sources !
        $valid_sources = [];
        foreach ($sources as $source) {
            $too_close = false;
            // On compare cette source à toutes les autres sources déjà validées
            foreach ($valid_sources as $valid_source) {
                $distance = $this->hexalib->distance($valid_source->coord('oddr'), $source->coord('oddr'));
                if ($distance <= 25) {
                    $too_close = true; // Si la distance est trop courte, on marque cette source comme trop proche
                    break; // On arrête la comparaison dès qu'on trouve une source trop proche
                }
            }
            // Si on a rien trouvé, on compare néanmoins au rivieres qui existe
            if (!$too_close) {
                // On compare cette source à toutes les rivieres qui existent
                foreach ($existing_rivers as $river) {
                    $distance = $this->hexalib->distance($river->coord('oddr'), $source->coord('oddr'));
                    if ($distance <= 25) {
                        $too_close = true; // Si la distance est trop courte, on marque cette source comme trop proche
                        break; // On arrête la comparaison dès qu'on trouve une source trop proche
                    }
                }
            }
            // Si la source n'est pas trop proche des autres, on l'ajoute aux sources valides
            if (!$too_close) {
                $valid_sources[] = $source;
            }
        }
        $valid_start_num = count($valid_sources);
        // River Trace
        $tries = 30;
        while ($tries > 0) {
            // On essaie de tracre le tout 3x car parfois une riviere permet a une autre de deboucher
            foreach ($valid_sources as $key => $ground) {
                $done = $this->drawRiver($ground->coord('oddr'));
                if ($done) {
                    unset($valid_sources[$key]);
                }
            }
            $tries--;
        }
        // Loop to add a lot of new rivers
        if (count($valid_sources) * 1.5 < $valid_start_num) {
            $this->Rivers(false, false);
        }
        if ($save) {
            $this->saveWorld();
        }
        return;
    }
    public function drawRiver($coord)
    {
        $finished = false;
        $final_river_path = [];
        $riverables = [2, 8, 9, 10, 12];
        // On simule une origine :
        $possible_prev = $this->board->getGroundNeighborOfTypes([4, 15, 16], $coord->col, $coord->row);
        $old_coord = $possible_prev[array_rand($possible_prev)]->coord('oddr');
        // La boucle !
        while (!$finished) {
            $final_river_path[] = $coord;
            // Ocean/Riviere touche = fini !
            if ($this->board->getGroundNeighborOfTypes([1, 13], $coord->col, $coord->row)) {
                $finished = true;
                break;
            }
            $origin_direction = $this->hexalib->cubeDirection($coord, $old_coord);
            $candidates = [];
            for ($i = 2; $i <= 4; $i++) {
                $nextDirection = ($origin_direction + $i) % 6;
                $choosen_ary = $this->board->getGroundNeighborOfTypes($riverables, $coord->col, $coord->row, $nextDirection);
                if (!empty($choosen_ary)) {
                    $choosen = $choosen_ary[0];
                    // Si le candidat a 2+ voisin riviere on l'exclus :
                    $rivers_neighbors = $this->board->getGroundNeighborOfTypes([13], $choosen->col, $choosen->row);
                    if (count($rivers_neighbors) <= 1) {
                        // Le candidat n'a pas le droit d'être un voisin immediat de ce que l'on a tracé, à l'exception des 2 derniers
                        $test_path = array_slice($final_river_path, 0, -2);
                        if (!$this->isCoordNeighborOfArray($choosen->coord('oddr'), $test_path)) {
                            $candidates[] = $choosen;
                        }
                    }
                }
            }
            if (empty($candidates)) {
                $finished = false;
                break;
            }
            $old_coord = $coord;
            $coord = $candidates[array_rand($candidates)]->coord('oddr');
        }
        $all_path_neighbours = [];
        if ($finished and count($final_river_path) > 15) {
            foreach ($final_river_path as $eachcoord) {
                // La riviere
                $this->setGroundFromCoord($eachcoord, 13);
                // Les voisins :
                $neighbors = $this->board->getGroundNeighborOfTypes([10, 9, 2, 8], $eachcoord->col, $eachcoord->row);
                // On ne note les voisins de notre chemin de revieire qu'une fois unique !
                foreach ($neighbors as $each_ground) {
                    $key = $each_ground->col.'-'.$each_ground->row;
                    $all_path_neighbours[$key] = $each_ground;
                }
            }
            foreach ($all_path_neighbours as $each_ground) {
                // Plaines -> Marecages 1/6
                if (rand(1, 6) <= 1) {
                    $this->convertGround($each_ground->coord('oddr'), 8, 11);
                }
                // Les savanne deviennent plaines 1/2
                if (rand(1, 6) <= 3) {
                    $this->convertGround($each_ground->coord('oddr'), 10, 8);
                }
                // Les foret & jungle mutent à 1/3 en marecages !
                if (rand(1, 6) <= 2) {
                    $this->convertGround($each_ground->coord('oddr'), 9, 11);
                    $this->convertGround($each_ground->coord('oddr'), 2, 11);
                }
            }
            return true;
        }
        return false;
    }
    public function isCoordNeighborOfArray($coord, $array)
    {
        foreach ($array as $item) {
            if (!empty($this->hexalib->commonNeighbours($item, $coord))) {
                return true;
            }
        }
        return false;
    }

    /* -------------------------------------------------------------------------
        Hot & Cold zone

    ------------------------------------------------------------------------- */
    public function temperature($row)
    {
        // HOT PLACES !
        if ($row > $this->climate_lines['tropic_north'] and $row < $this->climate_lines['tropic_south']) {
            return 'hot';
        }
        if ($row == $this->climate_lines['tropic_north'] or $row == $this->climate_lines['tropic_south']) {
            if (rand(0, 1) == 1) {
                return 'hot';
            }
            return 'temperate';
        }
        // Frozen Places (must be before cold as we have generic '<' comparaison)
        if ($row < 3 or $row > (MAX_ROW - 4)) {
            return 'frozen';
        }
        if ($row == 3 or $row == (MAX_ROW - 4)) {
            if (rand(0, 1) == 1) {
                return 'frozen';
            }
            return 'cold';
        }
        // Cold places
        if ($row > $this->climate_lines['antarctic_circle'] or $row < $this->climate_lines['arctic_circle']) {
            return 'cold';
        }
        if ($row == $this->climate_lines['antarctic_circle'] or $row == $this->climate_lines['arctic_circle']) {
            if (rand(0, 1) == 1) {
                return 'cold';
            }
            return 'temperate';
        }
        // Anyway, temperate...
        return 'temperate';
    }
    public function latitudeClimate($load = true, $save = true)
    {
        if ($load) {
            $this->loadWorld();
        }
        // Adapting plains (and oceans) to current climate !
        $this->board->forEachGround(function ($curr_col, $curr_row) {
            $actual_land = $this->getGround($curr_col, $curr_row);
            if ($actual_land !== null) {
                $temperature = $this->temperature($actual_land->row);
                if ($temperature == 'hot') {
                    $this->convertGround($actual_land->coord('oddr'), 8, 10);
                    $this->convertGround($actual_land->coord('oddr'), 2, 9);
                    $this->convertGround($actual_land->coord('oddr'), 3, 17);
                    $this->convertGround($actual_land->coord('oddr'), 15, 16);
                }
                if ($temperature == 'temperate') {
                    // Nothing...
                }
                if ($temperature == 'cold') {
                    $this->convertGround($actual_land->coord('oddr'), 8, 12);
                    $this->convertGround($actual_land->coord('oddr'), 3, 14);
                    if (rand(0, 1) == 1) {
                        $this->convertGround($actual_land->coord('oddr'), 2, 12);
                        $this->convertGround($actual_land->coord('oddr'), 15, 14);
                    }
                }
                if ($temperature == 'frozen') {
                    $this->convertGround($actual_land->coord('oddr'), 8, 6);
                    $this->convertGround($actual_land->coord('oddr'), 12, 6);
                    $this->convertGround($actual_land->coord('oddr'), 3, 18);
                    $this->convertGround($actual_land->coord('oddr'), 2, 12);
                    if (rand(0, 1) == 1) {
                        $this->convertGround($actual_land->coord('oddr'), 1, 6);
                    }
                }
            }
        }, false);
        // Les bords Arides sont modifes en fonction de l'humidite
        $this->board->forEachGround(function ($curr_col, $curr_row) {
            $actual_land = $this->getGround($curr_col, $curr_row);
            if ($actual_land !== null) {
                // If is a Savanna
                if ($actual_land->getGroundType() == 10) {
                    // Check Humdity neigbors
                    $jungle_neighbors = $this->board->getGroundNeighborOfTypes([9,16], $curr_col, $curr_row);
                    if (!empty($jungle_neighbors)) {
                        $this->setGround($curr_col, $curr_row, 8);  // Convert savanna to fertile plain
                        // Finding savana close for random variations
                        $savana_neighbors = $this->board->getGroundNeighborOfTypes([10], $curr_col, $curr_row);
                        if (!empty($savana_neighbors)) {
                            $neighbour_to_convert = $savana_neighbors[array_rand($savana_neighbors)]; // Récupérer un voisin au hasard
                            $this->convertGround($neighbour_to_convert->coord('oddr'), 10, 8);  // Conversion du voisin
                        }
                    }
                }
                //A forest can't be close to banquise...
                if ($actual_land->getGroundType() == 15 or $actual_land->getGroundType() == 2) {
                    // Check Banquise neigbors
                    $banquise_neighbors = $this->board->getGroundNeighborOfTypes([6,18], $curr_col, $curr_row);
                    if (!empty($banquise_neighbors)) {
                        // Convert forest to toundra
                        $this->convertGround($actual_land->coord('oddr'), 15, 14);
                        $this->convertGround($actual_land->coord('oddr'), 2, 12);
                    }
                }
            }
        }, false);
        if ($save) {
            $this->saveWorld();
        }
        return;
    }

    /* -------------------------------------------------------------------------
        Tectonic plates are recognizable as  oceans deeps, or mountains chains
        - moutains chains are beetween lands & lands or lands & oceans
        - There are various spots randopmly on the map
        - then spots are linked by lines : mountains or oceans or moutain&oceans

    ------------------------------------------------------------------------- */
    public function createTectonicPlates($load = true, $save = true, $size = null)
    {
        //allowLogs();
        if ($load) {
            $this->loadWorld();
        }
        // continent size
        if ($size == null) {
            $size = round(MAX_ROW / 10);
        }
        // Map limits
        $max_row_limit = MAX_ROW - 1;
        $max_col_limit = MAX_COL - 1;
        $rand_factor = 0.2;
        // Generate base spots
        $spots = [];
        // Row 0 :
        $base_col = randomAround($size, $rand_factor);
        while ($base_col < MAX_COL) {
            $spots[] = $this->hexalib->coord([$base_col, 0], 'Oddr');
            $base_col += randomAround($size, 0.2);
        }
        // Row Max :
        $base_col = randomAround($size, $rand_factor);
        while ($base_col < MAX_COL) {
            $spots[] = $this->hexalib->coord([$base_col, MAX_ROW - 1], 'Oddr');
            $base_col += randomAround($size, 0.2);
        }
        // Col 0 & Col max :
        $base_row = randomAround($size, $rand_factor);
        while ($base_row < MAX_ROW) {
            $spots[] = $this->hexalib->coord([0, $base_row], 'Oddr');
            $spots[] = $this->hexalib->coord([MAX_COL - 1, $base_row], 'Oddr');
            $base_row += randomAround($size, 0.2);
        }
        // Generate "grid" spots
        $nb_spots = MAX_ROW / $size * MAX_COL / $size;
        $tries = 100;
        while ($nb_spots > 0 and $tries > 0) {
            $new_col = mt_rand(0, MAX_COL - 1);
            $new_row = mt_rand(0, MAX_ROW - 1);
            $new_coord = $this->hexalib->coord([$new_col, $new_row], 'Oddr');
            $spiral_zone = $this->hexalib->spiral($new_coord, round($size / 1.5));
            if (!$this->hexalib->coordsIntersect($spots, $spiral_zone)) {
                $spots[] = $new_coord;
                $nb_spots--;
            } else {
                $tries--;
            }
        }
        // Do line beetween spots, closest, never draw twice a line
        $lines = 3;
        $already_drawn = [];
        $lines_found = [];
        foreach ($spots as $i => $each_spot) {
            $distances = [];
            foreach ($spots as $j => $other_spot) {
                if ($i === $j) {
                    continue;
                }
                $distances[] = [
                    'index' => $j,
                    'spot'  => $other_spot,
                    'dist'  => $this->hexalib->distance($each_spot, $other_spot),
                ];
            }
            usort($distances, fn ($a, $b) => $a['dist'] <=> $b['dist']);
            $lines_drawn = 0;
            $k = 0;
            while ($lines_drawn < $lines && isset($distances[$k])) {
                $j = $distances[$k]['index'];
                $key = min($i, $j) . '|' . max($i, $j);
                $is_i_border = ($each_spot->row === 0 || $each_spot->row === MAX_ROW - 1);
                $is_j_border = ($spots[$j]->row === 0 || $spots[$j]->row === MAX_ROW - 1);
                // Exclure liaison bord ↔ bord
                if ($is_i_border && $is_j_border) {
                    $k++;
                    continue;
                }
                if (!isset($already_drawn[$key])) {
                    $lines_found[] = [$each_spot, $spots[$j]];
                    $already_drawn[$key] = true;
                    $lines_drawn++;
                }
                $k++;
            }
        }
        // All lines are now drawn
        $mountain_quota = 2;
        $mountain_count = []; // $mountain_count[$spotId] = nb de lignes montagne
        foreach ($lines_found as $line) {
            [$a, $b] = $line;
            $id_a = $a->col . '|' . $a->row;
            $id_b = $b->col . '|' . $b->row;
            // Choisir aléatoirement le type de terrain
            $type_key = (mt_rand(0, 1) === 0) ? 'ocean' : 'mountain';
            // Vérifie quota montagne UNIQUEMENT
            if ($type_key === 'mountain') {
                $count_a = $mountain_count[$id_a] ?? 0;
                $count_b = $mountain_count[$id_b] ?? 0;
                if ($count_a >= $mountain_quota || $count_b >= $mountain_quota) {
                    continue;
                }
                $mountain_count[$id_a] = $count_a + 1;
                $mountain_count[$id_b] = $count_b + 1;
            }
            // Trace la ligne quel que soit le type
            $this->drawNoisyLine($this->land[$type_key], $a, $b);
        }
        // Widen the lines
        $this->widenTerrain($this->land['ocean'], $this->land['ocean'], $this->land, 6);
        $this->widenTerrain($this->land['mountain'], $this->land['mountain'], $this->land, 1);
        $this->widenTerrain($this->land['mountain'], $this->land['plainHill'], $this->land, 3);
        // Attention les plaines grattent les oceans et colline
        // j'ai donc augmenté ces valeurs à 6 & 4 au lieu de 5 & 3 qui été ok
        $this->widenTerrain($this->land['plain'], $this->land['plain'], [$this->land['ocean']], 1);
        // il faut effacer les case oceans isolées
        $this->deleteSmallerThan('ocean', 20, 'plain');
        // Spot free space in ocean to set islands
        $radius = 8;
        $base_spots = [];
        $this->board->forEachGround(function ($col, $row) use ($radius, &$base_spots) {
            $ground = $this->getGround($col, $row);
            if (!$ground || $ground->getGroundType() !== $this->land['ocean']) {
                return;
            }
            $center = $this->hexalib->coord([$col, $row], 'Oddr');
            $coords = $this->hexalib->spiral($center, $radius);
            $all_same = true;
            foreach ($coords as $coord) {
                $coord = $this->hexalib->convert($coord, 'Oddr');
                $neighbor = $this->getGround($coord->col, $coord->row);
                if (!$neighbor || $neighbor->getGroundType() !== $this->land['ocean']) {
                    $all_same = false;
                    break;
                }
            }
            if ($all_same) {
                $base_spots[] = $center;
            }
        });
        // Dessiner les îles : couches plaine > colline > montagne, centre en désert
        $splat_radius = round($radius / 2);
        foreach ($base_spots as $spot) {
            $plain_patch = $this->hexalib->noisySpiral($spot, round($splat_radius * 1));
            $hill_patch = $this->hexalib->noisySpiral($spot, round($splat_radius * 0.6));
            $mountain_patch = $this->hexalib->noisySpiral($spot, round($splat_radius * 0.3));
            $this->setGroundsFromCoords($plain_patch, $this->land['plain']);
            $this->setGroundsFromCoords($hill_patch, $this->land['plainHill']);
            $this->setGroundsFromCoords($mountain_patch, $this->land['mountain']);
        }
        if ($save) {
            $this->saveWorld();
        }
        return;
    }

}
