<?php

/*==============================================================================
    HEXALIB v1.2.0
    last modified : 07-JUL-2025
        Personnal $implementation of hexagonal grid management.
        Big thanks to :
            https://www.redblobgames.com/grids/hexagons/

    Written by Colin Boullard
    Licence Creatice Commons : BY-ND
        See : https://creativecommons.org/licenses/by-nd/4.0/
==============================================================================*/
/*------------------------------------------------------------------------------
    Objects for differents $coordinates systems :
 -----------------------------------------------------------------------------*/
abstract class HexCoordinate
{
    public string $type;
    abstract public function toString(): string;
    protected function validateInput(array $input, int $expected_length): void
    {
        if (count($input) !== $expected_length) {
            throw new InvalidArgumentException("Format error. Need $expected_length coords.");
        }
        foreach ($input as $value) {
            if (!is_numeric($value)) {
                throw new InvalidArgumentException("Coords must be numeric.");
            }
        }
    }
}
class Cube extends HexCoordinate
{
    public float $x;
    public float $y;
    public float $z;
    public function __construct(array $coords)
    {
        $this->validateInput($coords, 3);
        $this->type = 'cube';
        $this->x = (float)$coords[0];
        $this->y = (float)$coords[1];
        $this->z = (float)$coords[2];
        // if ($this->x + $this->y + $this->z !== 0) { throw new InvalidArgumentException("Cube coords must sum to 0."); }
    }
    public function toString(): string
    {
        return "$this->x,$this->y,$this->z";
    }
}
class Axial extends HexCoordinate
{
    public int $q;
    public int $r;
    public int $s;
    public function __construct(array $coords)
    {
        $this->validateInput($coords, 2);
        $this->type = 'axial';
        $this->q = (int)$coords[0];
        $this->r = (int)$coords[1];
        $this->s = -$this->q - $this->r;
    }
    public function toString(): string
    {
        return "$this->q,$this->r,$this->s";
    }
}
class Oddr extends HexCoordinate
{
    public int $col;
    public int $row;
    public function __construct(array $coords)
    {
        $this->validateInput($coords, 2);
        $this->type = 'oddr';
        $this->col = (int)$coords[0];
        $this->row = (int)$coords[1];
    }
    public function toString(): string
    {
        return "$this->col,$this->row";
    }
}

/*------------------------------------------------------------------------------
    Ze Library :
 -----------------------------------------------------------------------------*/
class Hexalib
{
    private const CUBE_DIRECTIONS = [
        [1, -1, 0], [1, 0, -1],
        [0, 1, -1], [-1, 1, 0],
        [-1, 0, 1], [0, -1, 1]
    ];
    private array $cube_directions = [];
    public function __construct()
    {
        $this->initializeCubeDirections();
    }
    // Private Methods ---------------------------------------------------------
    private function initializeCubeDirections(): void
    {
        if (empty($this->cube_directions)) {
            $this->cube_directions = array_map(fn ($dir) => new Cube($dir), self::CUBE_DIRECTIONS);
        }
    }
    private function getConversionMethodName(string $source_format, string $target_format): string
    {
        return "from{$source_format}To{$target_format}";
    }
    public function coordsEqual(HexCoordinate $a, HexCoordinate $b): bool
    {
        $cube_a = $this->convert($a, 'cube');
        $cube_b = $this->convert($b, 'cube');
        return $cube_a->toString() === $cube_b->toString();
    }

    // Coordinate System Manipulation ------------------------------------------
    public function coord(array $coords, string $format): HexCoordinate
    {
        // Create a coordinate
        $format = strtolower($format);
        return match($format) {
            'cube' => new Cube($coords),
            'axial' => new Axial($coords),
            'oddr' => new Oddr($coords),
            default => throw new InvalidArgumentException("Invalid format: $format. Use cube/axial/oddr")
        };
    }
    public function convert(HexCoordinate $coord, string $target_format): HexCoordinate
    {
        $source = ucfirst(strtolower($coord->type));
        $target = ucfirst(strtolower($target_format));
        if ($source === $target) {
            return $coord;
        }
        $method = "from{$source}To{$target}";
        return $this->{$method}($coord)
            ?? throw new BadMethodCallException("Conversion {$source}→{$target} impossible");
    }
    public function fromCubeToAxial(Cube $cube): Axial
    {
        return new Axial([$cube->x, $cube->z]);
    }
    public function fromAxialToCube(Axial $axial): Cube
    {
        return new Cube([$axial->q, $axial->s, $axial->r]);
    }
    public function fromCubeToOddr(Cube $cube): Oddr
    {
        $col = $cube->x + ($cube->z - ($cube->z & 1)) / 2;
        $row = $cube->z;
        return new Oddr([$col, $row]);
    }
    public function fromOddrToCube(Oddr $oddr): Cube
    {
        $x = $oddr->col - ($oddr->row - ($oddr->row & 1)) / 2;
        $z = $oddr->row;
        $y = -$x - $z;
        return new Cube([$x, $y, $z]);
    }
    public function fromOddrToAxial(Oddr $oddr): Axial
    {
        return $this->fromCubeToAxial($this->fromOddrToCube($oddr));
    }
    public function fromAxialToOddr(Axial $axial): Oddr
    {
        return $this->fromCubeToOddr($this->fromAxialToCube($axial));
    }
    public function pixelToHex(array $pixel_coords, string $target_format, float $width, float $size): HexCoordinate
    {
        // Convert pixel coordinates to hex coordinates
        // width = container width, size = hex size
        $x = (float)$pixel_coords[0];
        $y = (float)$pixel_coords[1];
        // Algorithm from Red Blob Games:
        // https://www.redblobgames.com/grids/hexagons/#comment-1063818420
        $x = ($x - $width / 2) / $width;
        $temp1 = $y / $size;
        $temp2 = floor($x + $temp1);
        $r = floor((floor($temp1 - $x) + $temp2) / 3);
        $q = floor((floor((2 * $x) + 1) + $temp2) / 3) - $r;
        // q and r are in axial coordinates - convert before returning
        $axial_coord = $this->createCoordinate([$q, $r], 'axial');
        return $this->convert($axial_coord, $target_format);
    }
    public function hexToPixel(HexCoordinate $hex_coord, float $width, float $height, float $size): array
    {
        // Convert hex coordinates to pixel coordinates (center of hex)
        // width = container width, height = container height, size = hex size
        $oddr_coord = $this->convert($hex_coord, 'oddr');
        // Convert from offset coordinates to pixel position
        $x = $size * sqrt(3) * ($oddr_coord->col + 0.5 * ($oddr_coord->row & 1));
        $y = $size * 3 / 2 * $oddr_coord->row;
        // Center in container
        $x += $width / 2;
        $y += $height / 2;
        return [$x, $y];
    }
    public function uniqueCoords(array $coords): array
    {
        $unique_map = [];
        foreach ($coords as $coord) {
            $unique_map[$coord->toString()] ??= $coord;
        }
        return array_values($unique_map);
    }
    public function coordsIntersect(array $list1, array $list2): bool
    {
        $keys = [];
        foreach ($list1 as $coord) {
            $keys[$coord->toString()] = true;
        }
        foreach ($list2 as $coord) {
            if (isset($keys[$coord->toString()])) {
                return true;
            }
        }
        return false;
    }
    // Mathematical function ---------------------------------------------------
    public function cubeDistance(Cube $a, Cube $b): int
    {
        return (int) ((abs($a->x - $b->x) + abs($a->y - $b->y) + abs($a->z - $b->z)) / 2);
    }
    public function distance($coord_a, $coord_b): int
    {
        $cube_a = $this->convert($coord_a, 'cube');
        $cube_b = $this->convert($coord_b, 'cube');
        return $this->cubeDistance($cube_a, $cube_b);
    }
    public function closest(array $coords, $target): ?object
    {
        $bestDist = PHP_INT_MAX;
        $bestCoord = null;
        foreach ($coords as $coord) {
            $dist = $this->distance($coord, $target);
            if ($dist < $bestDist) {
                $best_dist = $dist;
                $best_coord = $coord;
            }
        }
        return $best_coord;
    }
    public function lerp(float $a, float $b, float $t): float
    {
        return $a * (1 - $t) + $b * $t;
    }
    public function cubeScale(Cube $origin, Cube $direction, int $distance): Cube
    {
        return new Cube([
            $origin->x + $direction->x * $distance,
            $origin->y + $direction->y * $distance,
            $origin->z + $direction->z * $distance
        ]);
    }
    public function cubeLerp(Cube $a, Cube $b, float $t): Cube
    {
        return new Cube([
            $this->lerp($a->x, $b->x, $t),
            $this->lerp($a->y, $b->y, $t),
            $this->lerp($a->z, $b->z, $t)
        ]);
    }
    public function cubeRound(Cube $cube): Cube
    {
        $rx = round($cube->x);
        $ry = round($cube->y);
        $rz = round($cube->z);
        $x_diff = abs($rx - $cube->x);
        $y_diff = abs($ry - $cube->y);
        $z_diff = abs($rz - $cube->z);
        if ($x_diff > $y_diff && $x_diff > $z_diff) {
            $rx = -$ry - $rz;
        } elseif ($y_diff > $z_diff) {
            $ry = -$rx - $rz;
        } else {
            $rz = -$rx - $ry;
        }
        return new Cube([$rx, $ry, $rz]);
    }
    public function cubeAdd(Cube $a, Cube $b): Cube
    {
        return new Cube([
            $a->x + $b->x,
            $a->y + $b->y,
            $a->z + $b->z
        ]);
    }
    public function cubeSubstract(Cube $a, Cube $b): Cube
    {
        return new Cube([
            $a->x - $b->x,
            $a->y - $b->y,
            $a->z - $b->z
        ]);
    }

    // Neighbourhood -----------------------------------------------------------
    public function neighbour(HexCoordinate $coord, int $direction): HexCoordinate
    {
        $original_system = $coord->type;
        $cube_coord = $this->convert($coord, 'cube');
        $neighbour_coord = $this->cubeAdd($cube_coord, $this->cube_directions[$direction]);
        return $this->convert($neighbour_coord, $original_system);
    }
    public function allNeighbours(HexCoordinate $coord): array
    {
        $original_system = $coord->type;
        $cube_coord = $this->convert($coord, 'cube');
        $neighbours = [];
        foreach ($this->cube_directions as $direction) {
            $neighbour_coord = $this->cubeAdd($cube_coord, $direction);
            $neighbours[] = $this->convert($neighbour_coord, $original_system);
        }
        return $neighbours;
    }
    public function isNeighbour(HexCoordinate $coord_a, HexCoordinate $coord_b): bool
    {
        $target_cube_str = $this->convert($coord_b, 'cube')->toString();
        foreach ($this->allNeighbours($coord_a) as $neighbour) {
            if ($this->convert($neighbour, 'cube')->toString() === $target_cube_str) {
                return true;
            }
        }
        return false;
    }
    public function commonNeighbours(HexCoordinate $coord_a, HexCoordinate $coord_b): array
    {
        $neighbours_a = $this->allNeighbours($coord_a);
        $neighbours_b_str = array_flip(array_map(
            fn ($n) => $this->convert($n, 'cube')->toString(),
            $this->allNeighbours($coord_b)
        ));
        return array_values(array_filter(
            $neighbours_a,
            fn ($n) => isset($neighbours_b_str[$this->convert($n, 'cube')->toString()])
        ));
    }
    public function cubeDirection(HexCoordinate $coord_a, HexCoordinate $coord_b): ?int
    {
        $cube_a = $this->convert($coord_a, 'cube');
        $cube_b = $this->convert($coord_b, 'cube');
        $diff = $this->cubeSubstract(
            $this->convert($cube_b, 'cube'),
            $this->convert($cube_a, 'cube')
        );
        foreach ($this->cube_directions as $i => $direction) {
            if ($this->coordsEqual($diff, $direction)) {
                return $i;
            }
        }
        return null;
    }

    // Draws & Fun -------------------------------------------------------------
    public function drawLine(HexCoordinate $start, HexCoordinate $end): array
    {
        $start_cube = $this->convert($start, 'cube');
        $end_cube = $this->convert($end, 'cube');
        $original_system = $start->type;
        $dx = $end_cube->x - $start_cube->x;
        $dy = $end_cube->y - $start_cube->y;
        $dz = $end_cube->z - $start_cube->z;
        $n = max(abs($dx), abs($dy), abs($dz));
        if ($n === 0.0 || $n === 0) {
            return [$start];
        }
        $line = [];
        for ($i = 0; $i <= $n; $i++) {
            $x = $start_cube->x + $dx * $i / $n;
            $y = $start_cube->y + $dy * $i / $n;
            $z = -$x - $y; // Maintain cube constraint
            $rounded = $this->cubeRound(new Cube([$x, $y, $z]));
            $line[] = $this->convert($rounded, $original_system);
        }
        return array_values(array_unique($line, SORT_REGULAR));
    }
    public function generateNoisyLine($coord_a, $coord_b, int $depth = 2, int $amplitude = 1): array
    {
        // A line semi-random from a to b. depth is the number of recursive split
        // (middle of lines taken) amplitude, the random allowed around
        $that_sys = $coord_a->type;
        $a = $this->convert($coord_a, 'cube');
        $b = $this->convert($coord_b, 'cube');
        // Obtenir la série de points bruités en cube
        $controlPoints = $this->recursiveNoisyLine($a, $b, $depth, $amplitude);
        // Relier les points 2 à 2 avec line_draw()
        $result = [];
        $count = count($controlPoints);
        for ($i = 0; $i < $count - 1; $i++) {
            $segment = $this->drawLine($controlPoints[$i], $controlPoints[$i + 1]);
            array_pop($segment); // Évite la duplication du point suivant
            $result = array_merge($result, $segment);
        }
        // Ajouter le dernier point
        $result[] = $this->convert($controlPoints[$count - 1], $that_sys);
        return $result;
    }
    private function recursiveNoisyLine($a, $b, int $depth, int $amplitude): array
    {
        if ($depth === 0) {
            return [$a, $b];
        }
        $mid = $this->cubeLerp($a, $b, 0.5);
        $mid = $this->cubeRound($mid);
        // Ajout de bruit entier sur X et Y, Z compensé
        $dx = rand(-$amplitude, $amplitude);
        $dy = rand(-$amplitude, $amplitude);
        $mid->x += $dx;
        $mid->y += $dy;
        $mid->z = -$mid->x - $mid->y;
        $first_half = $this->recursiveNoisyLine($a, $mid, $depth - 1, max(1, intdiv($amplitude, 2)));
        $second_half = $this->recursiveNoisyLine($mid, $b, $depth - 1, max(1, intdiv($amplitude, 2)));
        array_pop($first_half);
        return array_merge($first_half, $second_half);
    }
    public function ring(HexCoordinate $center, int $radius): array
    {
        if ($radius < 0) {
            throw new InvalidArgumentException("Radius must be positive");
        }
        $original_system = $center->type;
        $center_cube = $this->convert($center, 'cube');
        $ring = [];
        // Special case: radius 0 returns just the center
        if ($radius === 0) {
            return [$this->convert($center_cube, $original_system)];
        }
        // Get starting position (4th direction = sud-ouest en cube)
        $current_cube = $this->cubeScale($center_cube, $this->cube_directions[4], $radius);
        $ring[] = $this->convert($current_cube, $original_system);
        // Generate each side of the hexagon
        for ($direction = 0; $direction < 6; $direction++) {
            for ($step = 1; $step <= $radius; $step++) {
                $current_cube = $this->neighbour($current_cube, $direction);
                $ring[] = $this->convert($current_cube, $original_system);
            }
        }
        return $ring;
    }
    public function spiral(HexCoordinate $center, int $radius): array
    {
        // Deepseek said this is optimal, and said to avoir array_merge !!!
        if ($radius < 0) {
            throw new InvalidArgumentException("Radius must be positive");
        }
        $spiral = [$center];
        if ($radius === 0) {
            return $spiral;
        }
        // Pre-allocate (optimisation of the death)
        $estimated_size = 1 + 3 * $radius * ($radius + 1); // 1 + Σ(6r)
        $spiral = [$center];
        $spiral = array_pad($spiral, $estimated_size, null);
        $index = 1;
        for ($current_radius = 1; $current_radius <= $radius; $current_radius++) {
            $ring = $this->ring($center, $current_radius);
            foreach ($ring as $hex) {
                $spiral[$index++] = $hex;
            }
        }
        return array_slice($spiral, 0, $index);
    }
    public function noisySpiral(HexCoordinate $center, int $radius): array
    {
        if ($radius < 0) {
            throw new InvalidArgumentException("Radius must be positive");
        }
        $oddr_center = $this->convert($center, 'oddr');
        $result = [$oddr_center];
        if ($radius === 0) {
            return $result;
        }
        // Optimisation: pré-allocation mémoire
        $max_possible_results = (int)(3 * $radius * ($radius + 1) * 0.8 + 1); // Estimation 80% de remplissage
        $result = array_pad([$oddr_center], $max_possible_results, null);
        $result_count = 1;
        $visited = [$oddr_center->col => [$oddr_center->row => true]];
        $frontier = [$oddr_center];
        $base_probability = 80;
        for ($step = 1; $step <= $radius; $step++) {
            $new_frontier = [];
            $current_probability = 100 - (int)round($step * $base_probability / $radius);
            $probability_threshold = $current_probability / 100;
            foreach ($frontier as $current) {
                $neighbors = $this->ring($current, 1);
                foreach ($neighbors as $neighbor) {
                    $col = $neighbor->col;
                    $row = $neighbor->row;
                    // Vérification de visite plus rapide avec tableau 2D
                    if (isset($visited[$col][$row])) {
                        continue;
                    }
                    // Marquer comme visité immédiatement
                    $visited[$col][$row] = true;
                    if (mt_rand() / mt_getrandmax() <= $probability_threshold) {
                        $result[$result_count++] = $neighbor;
                        $new_frontier[] = $neighbor;
                    }
                }
            }
            if (empty($new_frontier)) {
                break;
            }
            $frontier = $new_frontier;
        }
        return array_slice($result, 0, $result_count);
    }

}
