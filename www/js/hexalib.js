/*==============================================================================
    HEXALIB v1.0.9
    last modified : 25-04-2019
        Personnal implementation of hexagonal grid management.
        Big thanks to :
            https://www.redblobgames.com/grids/hexagons/

    Written by Colin Boullard
    Licence Creatice Commons : BY-ND
        See : https://creativecommons.org/licenses/by-nd/4.0/
==============================================================================*/
/*------------------------------------------------------------------------------
    Objects for differents coordinates systems :
    ( *1 => miss type preventing)
 -----------------------------------------------------------------------------*/
function Cube(array) {
  if (array.length !== 3) {
    console.log('Error : incorect format.')
    return false
  }
  this.type = 'cube'
  this.x = array[0] * 1
  this.y = array[1] * 1
  this.z = array[2] * 1
  this.stg = function () {
    return this.x + ',' + this.y + ',' + this.z
  }
}
function Axial(array) {
  if (array.length !== 2) {
    console.log('Error : incorect format.')
    return false
  }
  this.type = 'axial'
  this.q = array[0] * 1
  this.r = array[1] * 1
  this.s = -this.q - this.r
  this.stg = function () {
    return this.q + ',' + this.r + ',' + this.s
  }
}
function Oddr(array) {
  if (array.length !== 2) {
    console.log('Error : incorect format.')
    return false
  }
  this.type = 'oddr'
  this.col = array[0] * 1
  this.row = array[1] * 1
  this.stg = function () {
    return this.col + ',' + this.row
  }
}
/*------------------------------------------------------------------------------
    Ze Library :
 -----------------------------------------------------------------------------*/
function Hexalib() {
  // Coordinate Manipulation -------------------------------------------------
  this.coord = function (array, format) {
    // Capitalize :
    var coord_class = format.charAt(0).toUpperCase() + format.slice(1)
    // var coord_class = window[format];
    if (coord_class === 'Cube') {
      return new Cube(array)
    }
    if (coord_class === 'Axial') {
      return new Axial(array)
    }
    if (coord_class === 'Oddr') {
      return new Oddr(array)
    }
    return false

    // return new coord_class(array);
  }
  this.convert = function (coord, cible) {
    if (coord.type === cible) {
      return coord
    }
    var convert_func = coord.type + '_to_' + cible
    return this[convert_func](coord)
  }
  this.distance = function (coord_a, coord_b) {
    // Working with easiest coordinate system :
    var cube_a = this.convert(coord_a, 'cube')
    var cube_b = this.convert(coord_b, 'cube')
    return this.cube_distance(cube_a, cube_b)
  }
  this.closest = function (array, target) {
    var best_distance = null
    var best_coord = null
    for (var i = 0; i < array.length; i++) {
      var coord = array[i]
      var dist = this.distance(coord, target)
      if (best_distance === null) {
        best_distance = dist
        best_coord = coord
      } else {
        if (best_distance > dist) {
          best_distance = dist
          best_coord = coord
        }
      }
    }
    return best_coord
  }
  this.pixel_to = function (array, target, w, s) {
    // Find hex coord according to pixel coords
    // w = WIDTH; s = SIZE
    var x = array[0] * 1
    var y = array[1] * 1
    // Magical code from : (kiss & love)
    // https://www.redblobgames.com/grids/hexagons/#comment-1063818420
    x = (x - w / 2) / w
    var temp1 = y / s
    var temp2 = Math.floor(x + temp1)
    var r = Math.floor((Math.floor(temp1 - x) + temp2) / 3)
    var q = Math.floor((Math.floor(2 * x + 1) + temp2) / 3) - r
    // r & q sont en coordonnées "Axiales" - On converti avant return :
    return this.convert(this.coord([q, r], 'axial'), target)
  }
  this.coord_to_pixel = function (coord, w, h, s) {
    // Find pixel according to coord (center of hex)
    // w = WIDTH; s = SIZE
    coord = this.convert(coord, 'oddr')
    // From Offset coords :
    var x = s * Math.sqrt(3) * (coord.col + 0.5 * (coord.row & 1))
    var y = ((s * 3) / 2) * coord.row

    x = x + w / 2
    y = y + h / 2
    return [x, y]
  }

  // Neighborhood ------------------------------------------------------------
  // Precalculate Coords
  this.cube_directions = [
    new Cube([+1, -1, 0]),
    new Cube([+1, 0, -1]),
    new Cube([0, +1, -1]),
    new Cube([-1, +1, 0]),
    new Cube([-1, 0, +1]),
    new Cube([0, -1, +1])
  ]
  this.neighbour = function (coord, i) {
    // Finding current coordinate system :
    var that_sys = coord.type
    // Working with easiest coordinate system :
    var cube = this.convert(coord, 'cube')
    // Then, find this neighbour :
    var neighbour = this.cube_add(cube, this.cube_directions[i])
    // Convert back & return :
    return this.convert(neighbour, that_sys)
  }
  this.all_neighbours = function (coord) {
    // Finding current coordinate system :
    var that_sys = coord.type
    // Working with easiest coordinate system :
    var cube = this.convert(coord, 'cube')
    // Then, find those neighbour :
    var results = []
    for (var i = 0; i <= 5; i++) {
      var neighbour = this.cube_add(cube, this.cube_directions[i])
      // Convert it back, and push in results array:
      results.push(this.convert(neighbour, that_sys))
    }
    return results
  }
  this.random_neighbour = function (coord) {
    // Finding current coordinate system :
    var that_sys = coord.type
    // Working with easiest coordinate system :
    var cube = this.convert(coord, 'cube')
    // Then, find this neighbour :
    var neighbour = this.cube_add(cube, this.cube_directions[Math.floor(Math.random() * 6)])
    // Convert back & return :
    return this.convert(neighbour, that_sys)
  }
  this.is_neighbour = function (coord_a, coord_b) {
    // Test if coord_a is neighbour of coord_b
    var neighb_a = this.all_neighbours(coord_a)
    for (var i = 0; i < neighb_a.length; i++) {
      if (neighb_a[i].col === coord_b.col && neighb_a[i].row === coord_b.row) {
        return true
      }
    }
    return false
  }
  this.common_neighbours = function (coord_a, coord_b) {
    var neighb_a = this.all_neighbours(coord_a)
    var neighb_b = this.all_neighbours(coord_b)
    var results = []
    for (var i = 0; i < neighb_a.length; i++) {
      for (var j = 0; j < neighb_b.length; j++) {
        if (neighb_a[i].col === neighb_b[j].col && neighb_a[i].row === neighb_b[j].row) {
          results.push(neighb_a[i])
        }
      }
    }
    return results
  }
  this.cube_direction = function (cube_a, cube_b) {
    // Find direction (orientation) between cube_a -> cube_b
    var diff = this.cube_sub(cube_b, cube_a)
    for (var i = 0; i < this.cube_directions.length; i++) {
      if (
        diff.x === this.cube_directions[i].x &&
        diff.y === this.cube_directions[i].y &&
        diff.z === this.cube_directions[i].z
      ) {
        return i
      }
    }
    return false
  }
  // Draws & Fun -------------------------------------------------------------
  this.line_draw = function (coord_a, coord_b) {
    // Finding current coordinate system :
    var that_sys = coord_a.type
    // Working with easiest coordinate system :
    var cube_a = this.convert(coord_a, 'cube')
    var cube_b = this.convert(coord_b, 'cube')
    // Let's "draw" :
    var N = this.distance(cube_a, cube_b)
    var results = []
    for (var i = 0; i <= N; i++) {
      // Finding the cube coords :
      var cube_step = this.cube_round(this.cube_lerp(cube_a, cube_b, (1 / N) * i))
      // Convert it back, and push in results array:
      results.push(this.convert(cube_step, that_sys))
    }
    return results
  }
  this.random_line_draw = function (coord_a, coord_b, factor) {
    // A line with random zigzag according to factor :
    var current = coord_a
    var normal_line_coords = this.line_draw(coord_a, coord_b)
    var end_place = normal_line_coords[normal_line_coords.length - 1]
    var results = []
    results.push(current)
    var previous = null
    var key = 0
    do {
      // target is a spot on the straight line :
      if (typeof normal_line_coords[key] !== 'undefined') {
        var target = normal_line_coords[key]
      }
      key++
      var all_n = this.all_neighbours(current)
      var closest = this.closest(all_n, target)
      var choices = []
      choices.push(closest)
      var choices = choices.concat(this.common_neighbours(current, closest))
      if (this.distance(current, target) >= factor) {
        // If too far, get closest to target by removing farest choices:
        var worst_d = 0
        var worst_key = null
        for (var j = 0; j < choices.length; j++) {
          var this_d = this.distance(target, choices[j])
          if (this_d > worst_d) {
            worst_d = this_d
            worst_key = j
          }
        }
        choices.splice(worst_key, 1)
      }
      // Current chose :
      previous = current
      current = choices[Math.floor(Math.random() * choices.length)]
      results.push(current)
    } while (
      (current.col !== end_place.col || current.row !== end_place.row) &&
      key < normal_line_coords.length * 2
    )
    return results
  }
  this.ring = function (coord, radius) {
    // Finding current coordinate system :
    var that_sys = coord.type
    // Working with easiest coordinate system :
    var center = this.convert(coord, 'cube')
    // Let's Ring !
    var results = []
    // this code doesn't work for radius = 0; can you see why?
    var cube_step = this.cube_scale(center, this.cube_directions[4], radius)
    results.push(this.convert(cube_step, that_sys))
    for (var i = 0; i <= 5; i++) {
      for (var j = 0; j < radius; j++) {
        cube_step = this.neighbour(cube_step, i)
        results.push(this.convert(cube_step, that_sys))
      }
    }
    return results
  }
  this.spiral = function (coord, radius) {
    // Let's Spiral ! (multiple rings)
    var results = [coord]
    for (var i = 1; i <= radius; i++) {
      results = results.concat(this.ring(coord, i))
    }
    return results
  }
  // Arythmetics -------------------------------------------------------------
  this.lerp = function (a, b, t) {
    return a + (b - a) * t
  }
  this.cube_scale = function (origin, dir_matrix, d) {
    var new_coord = origin
    for (var i = 0; i < d; i++) {
      new_coord = this.cube_add(new_coord, dir_matrix)
    }
    return new_coord
  }
  this.cube_lerp = function (cube_a, cube_b, t) {
    return new Cube([
      this.lerp(cube_a.x, cube_b.x, t),
      this.lerp(cube_a.y, cube_b.y, t),
      this.lerp(cube_a.z, cube_b.z, t)
    ])
  }
  this.cube_round = function (cube) {
    var rx = Math.round(cube.x)
    var ry = Math.round(cube.y)
    var rz = Math.round(cube.z)

    var x_diff = Math.abs(rx - cube.x)
    var y_diff = Math.abs(ry - cube.y)
    var z_diff = Math.abs(rz - cube.z)

    if (x_diff > y_diff && x_diff > z_diff) {
      rx = -ry - rz
    } else if (y_diff > z_diff) {
      ry = -rx - rz
    } else {
      rz = -rx - ry
    }

    return new Cube([rx, ry, rz])
  }
  this.cube_distance = function (cube_a, cube_b) {
    return (
      (Math.abs(cube_a.x - cube_b.x) +
        Math.abs(cube_a.y - cube_b.y) +
        Math.abs(cube_a.z - cube_b.z)) /
      2
    )
  }
  this.cube_add = function (cube_a, cube_b) {
    var rx = cube_a.x * 1 + cube_b.x * 1
    var ry = cube_a.y * 1 + cube_b.y * 1
    var rz = cube_a.z * 1 + cube_b.z * 1
    return new Cube([rx, ry, rz])
  }
  this.cube_sub = function (cube_a, cube_b) {
    var rx = cube_a.x * 1 - cube_b.x * 1
    var ry = cube_a.y * 1 - cube_b.y * 1
    var rz = cube_a.z * 1 - cube_b.z * 1
    return new Cube([rx, ry, rz])
  }
  // Convert (from) > (to) ---------------------------------------------------
  this.cube_to_axial = function (cube) {
    var q = cube.x
    var r = cube.z
    return new Axial([q, r])
  }
  this.axial_to_cube = function (axial) {
    var x = axial.q
    var z = axial.r
    var y = axial.s
    return new Cube([x, y, z])
  }
  this.cube_to_oddr = function (cube) {
    var col = cube.x + (cube.z - (cube.z & 1)) / 2
    var row = cube.z
    return new Oddr([col, row])
  }
  this.oddr_to_cube = function (oddr) {
    var x = oddr.col - (oddr.row - (oddr.row & 1)) / 2
    var z = oddr.row
    var y = -x - z
    return new Cube([x, y, z])
  }
  this.oddr_to_axial = function (oddr) {
    var cube = this.oddr_to_cube(oddr)
    return this.cube_to_axial(cube)
  }
  this.axial_to_oddr = function (axial) {
    var cube = this.axial_to_cube(axial)
    return this.cube_to_oddr(cube)
  }
}
const H = new Hexalib()
