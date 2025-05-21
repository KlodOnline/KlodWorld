<?php
/* =============================================================================
	InventoryCell class

============================================================================= */
class InventoryCell extends BDDObject {

	public const TABLE_NAME = 'inventory_cells';

    public int $id;
    public int $item_id;
    public int $volume;
    public int $owner_id;

    // public function __construct($data) { parent::__construct($data); }

    public static function keyFields() {
        return ['id'];
    }
}
