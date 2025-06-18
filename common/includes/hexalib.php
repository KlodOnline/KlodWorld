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
        $coord_class = ucfirst($format);
        if ($coord_class==='Cube') {return new Cube($array);}
        if ($coord_class==='Axial') {return new Axial($array);}
        if ($coord_class==='Oddr') {return new Oddr($array);}
        return false;
        }
    public function convert($coord, $cible)
        {
        if (ucfirst($coord->type)===ucfirst($cible)) {return $coord;}
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
/*        
    public function is_neighbour($coord_a, $coord_b)
        {
        // Test if $coord_a $is neighbour of $coord_b    
        $neighb_a = $this->all_neighbours($coord_a);
        for ($i=0; $i<count($neighb_a); $i++) 
            {  if ($neighb_a[$i].col===$coord_b->col && $neighb_a[$i].row===$coord_b->row)
                { return true; } }
        return false;    
        }
*/
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


public function noisyline_draw_new($coord_a, $coord_b, $variation_strength = 1)
{
    $that_sys = $coord_a->type;
    $cube_a = $this->convert($coord_a, 'cube');
    $cube_b = $this->convert($coord_b, 'cube');
    $N = $this->distance($cube_a, $cube_b);

    if ($N === 0) {
        return [$this->convert($cube_a, $that_sys)];
    }

    $results = [];
    $step_ratio = 1 / $N;

    for ($i = 0; $i <= $N; $i++) {
        // Interpolation
        $interpolated = $this->cube_lerp($cube_a, $cube_b, $i * $step_ratio);

        // Génération de l'offset aléatoire
        $random_offset = new Cube([
            'x' => random_int(-$variation_strength, $variation_strength),
            'y' => random_int(-$variation_strength, $variation_strength),
            'z' => 0 // Temporaire
        ]);

        // Ajustement pour respecter x + y + z = 0
        $random_offset->z = -($random_offset->x + $random_offset->y);

        // Application de l'offset et arrondi
        $noisy_point = $this->cube_add($interpolated, $random_offset);
        $cube_step = $this->cube_round($noisy_point);

        // Conversion finale
        $results[] = $this->convert($cube_step, $that_sys);
    }

    return $results;
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
        // $this code doesn't work for $radius = 0; can you see why?
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
		$result[] = $coord;
		$frontier = [$coord];
		for ($step = 0; $step < $radius; $step++) 
			{
			$newFrontier = [];
			foreach ($frontier as $c) 
				{
				$ring = $this->ring($c, 1);
				foreach ($ring as $n) 
					{
					$key = "{$n->col},{$n->row}";
					if (isset($done[$key])) { continue; }
					if (rand(0, 1) === 0) { continue; } // bruit : 50% de chance d'être inclus
					$done[$key] = true;
					$result[] = $n;
					$newFrontier[] = $n;
					}
				}
			$frontier = $newFrontier;
			}
		if (count($result) >= $radius) { return $result; }			
		return $this->noisy_spiral($coord, $radius);
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