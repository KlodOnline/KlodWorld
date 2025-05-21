<?php
/* =============================================================================
	UnitOrder class
============================================================================= */
class Order extends BDDObject {

	public const TABLE_NAME = 'orders';

    public int $owner_id;
    public string $order_type;
    public string $data;
    public int $turn;

    public static function keyFields() { return ['owner_id']; }

    public function advancedOrder($board) {
    	return OrderFactory::createOrder($this->order_type, $this->owner_id, $this->data, $board);
    }

    public function tokenize($board, $owner) {

    	// To really tokenize, we need to factory this shit :
		$order = $this->advancedOrder($board);

    	$data = [];
    	$data['name'] = $this->order_type;
    	$data['tic'] = $this->turn;
    	$data['hash'] = 'hash';
    	if ($owner) {
    		$data['fpt'] = $order->totalTurns(); // $this->data;	
    	} else {
    		$data['fpt'] = 0;
    	}
    	if ($order->havePath()) {
    		$path = $order->path();
    		if ($path!=null) { $data['path'] = $order->tokenizePath($owner); }    		
    	}
    	return $data;
    }
}
