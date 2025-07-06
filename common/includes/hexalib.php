<?php
/*==============================================================================
    HEXALIB v1.1.0
    last modified : 09-DEC-2024
        Personnal $implementation of hexagonal grid management.
        Big thanks to :
            https://www.redblobgames.com/grids/hexagons/

    Written by Colin Boullard
    Licence Creatice Commons : BY-ND
        See : https://creativecommons.org/licenses/by-nd/4.0/
==============================================================================*/
/*------------------------------------------------------------------------------
    Objects for differents $coordinates systems :
    ( *1 => miss type preventing)
 -----------------------------------------------------------------------------*/
class Cube {
    public $x;
    public $y;
    public $z;
    public $type;
	public function __construct($array) {	
	  	if (count($array)!==3) { print_r('Error : $incorrect $format.'); return false; }    
	    $this->type = 'cube';
	    $this->x = $array[0]*1;
	    $this->y = $array[1]*1;
	    $this->z = $array[2]*1;
  	}
  public function stg() { return $this->x.','.$this->y.','.$this->z; }
}
class Axial {
    public $q;
    public $r;
    public $s;
    public $type;
	public function __construct($array) {		
		if (count($array)!==2) { print_r('Error : $incorrect $format.'); return false; }    
		$this->type = 'axial';
		$this->q = $array[0]*1;
		$this->r = $array[1]*1;
		$this->s = -$this->q-$this->r;
	}
	public function stg() {return $this->q.','.$this->r.','.$this->s;}
}    
class Oddr {
    public $col;
    public $row;
    public $type;
	public function __construct($array) {		
		if (count($array)!==2) { print_r('Error : $incorrect $format.'); return false; }    
		$this->type = 'oddr';
		$this->col = $array[0]*1;
		$this->row = $array[1]*1;
	}
	public function stg() {return $this->col.','.$this->row;}
}

/*------------------------------------------------------------------------------
    Ze Library :
 -----------------------------------------------------------------------------*/
class Hexalib {

	private $cube_directions;

	public function __construct() {
		// Precalculate $coords
    	$this->cube_directions = [
	    	new Cube([+1, -1, 0]), new Cube([+1, 0, -1]),
        	new Cube([0, +1, -1]), new Cube([-1, +1, 0]),
        	new Cube([-1, 0, +1]), new Cube([0, -1, +1])
        ];
	}

    // $coordinate Manipulation -------------------------------------------------
    public function coord($array, $format)
        {
        // Capitalize :
		$coord_class = ucfirst(strtolower($format));
        if ($coord_class==='Cube') {return new Cube($array);}
        if ($coord_class==='Axial') {return new Axial($array);}
        if ($coord_class==='Oddr') {return new Oddr($array);}
        return false;
        }
    public function convert($coord, $cible)
        {
		$type = (string) ($coord->type ?? '');
	    if (strcasecmp($type, $cible) === 0) { return $coord; }
        $convert_func = $coord->type.'_to_'.$cible;    
        return $this->$convert_func($coord);
        }
    public function distance($coord_a, $coord_b)
        {
        // Working with easiest $coordinate system :
        $cube_a = $this->convert($coord_a, 'cube');
        $cube_b = $this->convert($coord_b, 'cube');
        return $this->cube_distance($cube_a, $cube_b);
        }
    public function closest($array, $target)
        {
        $best_distance = INF;
        $best_coord = null;
	    foreach ($array as $coord) {
	        $dist = $this->distance($coord, $target);
	        if ($dist < $best_distance) {
	            $best_distance = $dist;
	            $best_coord = $coord;
	        }
	    }
        return $best_coord;
        }
    public function pixel_to($array, $target, $w, $s)
        {
        // Find hex $coord according to pixel $coords
        // w = WIDTH; $s = SIZE
        $x = $array[0]*1; $y = $array[1]*1;
        // Magical code from : (kiss & love)
        // https://www.redblobgames.com/grids/hexagons/#comment-1063818420
        $x = ($x - $w/2) / $w;
        $temp1 = $y / $s;
        $temp2 = floor($x + $temp1);
        $r = floor((floor($temp1 - $x) + $temp2) / 3);
        $q = floor((floor((2 * $x) + 1) + $temp2) / 3) - $r;
        // r & q sont en $coordonnées "Axiales" - On converti avant return :
        return $this->convert($this->coord([$q, $r],'axial'), $target);
        }
    public function coord_to_pixel($coord, $w, $h, $s)
        {
        // Find pixel according to $coord (center of hex)
        // w = WIDTH; $s = SIZE
        $coord = $this->convert($coord, 'oddr');
        // From Offset $coords :
        $x = $s * sqrt(3) * ($coord->col + 0.5 * ($coord->row&1));
        $y = $s * 3/2 * $coord->row;
        $x=$x+$w/2;
        $y=$y+$h/2;
        return [$x, $y];
        }
    // Neighborhood ------------------------------------------------------------
    public function neighbour($coord, $i)
        {
        // Finding current $coordinate system :
        $that_sys = $coord->type;
        // Working with easiest $coordinate system :
        $cube = $this->convert($coord, 'cube');
        // Then, find $this neighbour :
        $neighbour = $this->cube_add($cube, $this->cube_directions[$i]);
        // Convert back & return :
        return $this->convert($neighbour, $that_sys);
        }
    public function all_neighbours($coord)
        {
        // Finding current $coordinate system :
        $that_sys = $coord->type;
        // Working with easiest $coordinate system :
        $cube = $this->convert($coord, 'cube');
        // Then, find those neighbour :
        $results = [];
        for ($i=0; $i<=5; $i++)
            {
            $neighbour = $this->cube_add($cube, $this->cube_directions[$i]);
            // Convert it back, and push in results array:
            $results[]=($this->convert($neighbour, $that_sys));
            }
        return $results;
        }
	public function is_neighbour($coord_a, $coord_b)
		{
		// Récupère tous les voisins de $coord_a
		$neighbours_a = $this->all_neighbours($coord_a);
		// Parcourt les voisins pour voir si $coord_b est parmi eux
		foreach ($neighbours_a as $neighbour) {
			if ($neighbour->col === $coord_b->col && $neighbour->row === $coord_b->row) {
		    	return true; // $coord_b est un voisin
				}
			}
		return false; // Aucun voisin ne correspond
		}

    public function common_neighbours($coord_a, $coord_b)
        {
        $neighb_a = $this->all_neighbours($coord_a);
        $neighb_b = $this->all_neighbours($coord_b);
        $results = [];
        for ($i=0; $i<count($neighb_a); $i++) { for($j=0; $j<count($neighb_b); $j++)
            {
            if ($neighb_a[$i]->col===$neighb_b[$j]->col && $neighb_a[$i]->row===$neighb_b[$j]->row )
                { $results[] = $neighb_a[$i]; }
            }}
        return $results;
        }

	public function cube_direction($cube_a, $cube_b)
		{
		$cube_a = $this->convert($cube_a, 'cube');
		$cube_b = $this->convert($cube_b, 'cube');
		$diff = $this->cube_sub($cube_b, $cube_a);
		foreach ($this->cube_directions as $i => $direction) {
			if (
		    	$diff->x === $direction->x &&
		        $diff->y === $direction->y &&
		        $diff->z === $direction->z
			) {
				return $i;
			}
		}
		return false;
		}
    // Draws & Fun -------------------------------------------------------------    
    public function line_draw($coord_a, $coord_b)
        {
        // Finding current $coordinate system :
        $that_sys = $coord_a->type;
        // Working with easiest $coordinate system :
        $cube_a = $this->convert($coord_a, 'cube');
        $cube_b = $this->convert($coord_b, 'cube');
        // Let's "draw" :
        $N = $this->distance($cube_a, $cube_b);
        // If someone gives us both the same coords stupid bastard.
		if ($N == 0) { return [$this->convert($cube_a, $that_sys)]; }
        $results = [];
        for ($i=0; $i<=$N; $i++)
            {
            // Finding the cube $coords :
            // $cube_step = ($this->cube_round($this->cube_lerp($cube_a, $cube_b, 1/$N*$i)));
            $cube_step = ($this->cube_round($this->cube_lerp($cube_a, $cube_b, $i/$N)));
            // Convert $it back, and push $in $results $array:
            $results[] = ($this->convert($cube_step, $that_sys));
            }
        return $results;
        }
	public function noisy_line($coord_a, $coord_b, int $depth = 2, float $amplitude = 1): array
		{
		// A line semi-random from a to b. depth is the number of recursive split
		// (middle of lines taken) amplitude, the random allowed around
	    $that_sys = $coord_a->type;
	    $a = $this->convert($coord_a, 'cube');
	    $b = $this->convert($coord_b, 'cube');
	    // Obtenir la série de points bruités en cube
	    $controlPoints = $this->recursive_noisy_line($a, $b, $depth, $amplitude);
	    // Relier les points 2 à 2 avec line_draw()
	    $result = [];
	    $count = count($controlPoints);
	    for ($i = 0; $i < $count - 1; $i++) {
	        $segment = $this->line_draw($controlPoints[$i], $controlPoints[$i + 1]);
	        array_pop($segment); // Évite la duplication du point suivant
	        $result = array_merge($result, $segment);
	    }
	    // Ajouter le dernier point
	    $result[] = $this->convert($controlPoints[$count - 1], $that_sys);
	    return $result;
		}
	private function recursive_noisy_line($a, $b, int $depth, float $amplitude): array
		{
	    if ($depth === 0) { return [$a, $b]; }
	    // Midpoint simple
	    $mid = $this->cube_lerp($a, $b, 0.5);
	    // Ajout de bruit aléatoire : décalage sur x/y/z
	    $mid->x += rand(-100, 100) / 100 * $amplitude;
	    $mid->y += rand(-100, 100) / 100 * $amplitude;
	    $mid->z = -$mid->x - $mid->y; // Contrôle : x+y+z = 0
	    $mid = $this->cube_round($mid);
	    // Appel récursif sur A→M et M→B
	    $first_half = $this->recursive_noisy_line($a, $mid, $depth - 1, $amplitude / 2);
	    $second_half = $this->recursive_noisy_line($mid, $b, $depth - 1, $amplitude / 2);
	    // Fusion sans dupliquer le point milieu
	    array_pop($first_half);
	    return array_merge($first_half, $second_half);
		}

	public function noisyline_draw($coord_a, $coord_b, $variation_strength = 1)
		{
	    // Determine the current coordinate system
	    $that_sys = $coord_a->type;
	    // Convert coordinates to 'cube' system for easier calculations
	    $cube_a = $this->convert($coord_a, 'cube');
	    $cube_b = $this->convert($coord_b, 'cube');
	    // Calculate the number of steps
	    $N = $this->distance($cube_a, $cube_b);
	    // Handle the edge case where both coordinates are the same
	    if ($N == 0) { return [$this->convert($cube_a, $that_sys)]; }
	    $results = [];
	    // Interpolate and add random variations
	    for ($i = 0; $i <= $N; $i++)
	    	{
	        // Calculate interpolated position
	        $interpolated = $this->cube_lerp($cube_a, $cube_b, $i / $N);

	        // Apply random variation to the cube coordinates
	        $random_offset = [
	            'x' => mt_rand(-$variation_strength, $variation_strength),
	            'y' => mt_rand(-$variation_strength, $variation_strength),
	            'z' => mt_rand(-$variation_strength, $variation_strength)
	        ];

	        // Adjust to maintain the cube coordinate constraint x + y + z = 0
	        $random_offset['z'] = -($random_offset['x'] + $random_offset['y']);

	        $rand_point = new Cube([$random_offset['x'], $random_offset['y'], $random_offset['z']]);

	        $noisy_point = $this->cube_add($interpolated, $rand_point);

	        // Round the noisy point to the nearest hex
	        $cube_step = $this->cube_round($noisy_point);

	        // Convert it back to the original coordinate system and store in results
	        $results[] = $this->convert($cube_step, $that_sys);
	    	}
	    return $results;
		}
    public function ring($coord, $radius)
        {
        // Finding current $coordinate system :
        $that_sys = $coord->type;
        // Working with easiest $coordinate system :
        $center = $this->convert($coord, 'cube');
        // Let's Ring !
        $results = [];
        // This code doesn't work for $radius = 0; can you see why?
        $cube_step = $this->cube_scale($center, $this->cube_directions[4], $radius);
        array_push($results, $this->convert($cube_step, $that_sys));
        for ($i=0; $i<=5; $i++) { for ($j=0; $j<$radius; $j++)
            {
            $cube_step = $this->neighbour($cube_step, $i);
            array_push($results, $this->convert($cube_step, $that_sys));
            } }
        return $results;
        }
    public function spiral($coord, $radius)
        {
        // Let's Spiral ! (multiple rings)
        $results = [$coord];
        for ($i=1; $i<=$radius; $i++) { $results = array_merge($results, $this->ring($coord, $i));}
        return $results;
        }

public function noisy_spiral($coord, $radius) 
{
    $coord = $this->convert($coord, 'Oddr');
    $done = [];
    $result = [];
    $originKey = "{$coord->col},{$coord->row}";
    $done[$originKey] = true;
    $result[] = $coord; // Centre toujours inclus
    
    if ($radius <= 0) return $result;
    
    $frontier = [$coord];
    $baseProbability = 80; // pct Probabilité de base pour le 1er anneau
    
    for ($step = 1; $step <= $radius; $step++) 
    {
        $newFrontier = [];
        // Probabilité qui décroît avec la distance
        // $currentProb = $baseProbability * (1 - ($step / $radius));

        $currentProb = 100 - round($step * $baseProbability / $radius);
        
        foreach ($frontier as $c) 
        {
            $ring = $this->ring($c, 1);
            foreach ($ring as $n) 
            {
                $key = "{$n->col},{$n->row}";
                if (isset($done[$key])) continue;
                
                // Pour le centre, on a déjà inclus, pour les autres on teste
                if ((rand(0, 100) > $currentProb)) continue;
                
                $done[$key] = true;
                $result[] = $n;
                $newFrontier[] = $n;
            }
        }
        
        $frontier = $newFrontier;
        if (empty($frontier)) break;
    }
    
    return $result;
}

    // Arythmetics -------------------------------------------------------------
    public function lerp($a, $b, $t)
        {
        return $a + ($b - $a) * $t;
        }
    public function cube_scale($origin, $dir_matrix, $d)
        {
        $new_coord = $origin;
        for ($i=0; $i<$d; $i++){$new_coord = $this->cube_add($new_coord, $dir_matrix);}
        return $new_coord;
        }
    public function cube_lerp($cube_a, $cube_b, $t)
        {
        return new Cube([$this->lerp($cube_a->x, $cube_b->x, $t), $this->lerp($cube_a->y, $cube_b->y, $t), $this->lerp($cube_a->z, $cube_b->z, $t)]);
        }
    public function cube_round($cube)
        {
        $rx = round($cube->x);
        $ry = round($cube->y);
        $rz = round($cube->z);

        $x_diff = abs($rx - $cube->x);
        $y_diff = abs($ry - $cube->y);
        $z_diff = abs($rz - $cube->z);

        if ($x_diff > $y_diff && $x_diff > $z_diff) {$rx = -$ry-$rz;}
        else if ($y_diff > $z_diff) {$ry = -$rx-$rz;}
        else {$rz = -$rx-$ry;}
        
        return new Cube([$rx,$ry,$rz]);
        }
    public function cube_distance($cube_a, $cube_b)
        {
        return (abs($cube_a->x - $cube_b->x) + abs($cube_a->y - $cube_b->y) + abs($cube_a->z - $cube_b->z)) / 2;
        }
    public function cube_add($cube_a, $cube_b)
        {
        $rx = $cube_a->x*1 + $cube_b->x*1;
        $ry = $cube_a->y*1 + $cube_b->y*1;
        $rz = $cube_a->z*1 + $cube_b->z*1;
        return new Cube([$rx,$ry,$rz]);
        }
    public function cube_sub($cube_a, $cube_b)
        {
        $rx = $cube_a->x*1 - $cube_b->x*1;
        $ry = $cube_a->y*1 - $cube_b->y*1;
        $rz = $cube_a->z*1 - $cube_b->z*1;
        return new Cube([$rx,$ry,$rz]);
        }        
    // Convert (from)=>(to) ----------------------------------------------------
    public function cube_to_axial($cube)
        {
        $q = $cube->x;
        $r = $cube->z;
        return new Axial([$q,$r]);
        }
    public function axial_to_cube($axial)    
        {
        $x = $axial->q;
        $z = $axial->r;
        $y = $axial->s;
        return new Cube([$x,$y,$z]);
        }
    public function cube_to_oddr($cube)    
        {
        $col = $cube->x + ($cube->z - ($cube->z&1)) / 2;
        $row = $cube->z;
        return new Oddr([$col, $row]);
        }
    public function oddr_to_cube($oddr)    
        {
        $x = $oddr->col - ($oddr->row - ($oddr->row&1)) / 2;
        $z = $oddr->row;
        $y = -$x-$z;
        return new Cube([$x, $y, $z]);
        }
    public function oddr_to_axial($oddr)    
        {
        $cube = $this->oddr_to_cube($oddr);
        return $this->cube_to_axial($cube);
        }
    public function axial_to_oddr($axial)    
        {
        $cube = $this->axial_to_cube($axial);
        return $this->cube_to_oddr($cube);
        }        
    }

?>