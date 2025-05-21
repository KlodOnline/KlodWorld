<?php
/* =============================================================================
	player class

============================================================================= */
class Player extends BDDObject {

    public const TABLE_NAME = 'players';
	
    public int $meta_id = 0;
    public string $name = 'errNoName';
    public string $color = '#FFFFFF';
    public string $discover = '{}';
    public int $paidto = 0;

    public function __construct($data) {
        parent::__construct($data);

        // if ($this->meta_id==1) { $this->discover = {}; }
        // else { $this->discover = {}; }
    }

    public static function keyFields() {
        return ['meta_id'];
    }
}
