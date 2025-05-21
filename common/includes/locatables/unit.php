<?php
/* =============================================================================
    Unit
============================================================================= */
class Unit extends Locatable {
	public function __construct(array $data) { 
		$this->setDefaultJsonData();
		parent::__construct($data); 
		$this->kind = 'Unit';
		$this->loadJsonData();
	}

    /*--------------------------------------------------------------------------
		JSON Stuff
    --------------------------------------------------------------------------*/
    public function setDefaultJsonData(): void {
        $defaultData = [
            'name' => 'nobody',
            'unit_type' => 0,
            'city_id' => 0,
            'moral' => 100,
			'boat' => false,
        ];
        foreach ($defaultData as $key => $value) {
            $this->setJsonData($key, $value);
        }
    }

    // Getters for "data" field
    public function getName() { return (string) $this->getJsonData('name'); }
    public function getUnitType() { return (int) $this->getJsonData('unit_type'); }
    public function getCityId() { return (int) $this->getJsonData('city_id'); }
    public function getMoral() { return (int) $this->getJsonData('moral'); }
    public function getBoat() { return (bool) $this->getJsonData('boat'); }
    

    /*--------------------------------------------------------------------------
		GUI Stuff
    --------------------------------------------------------------------------*/
    public function tokenize() {
    	$data = [];
    	$data['t'] = 'U';
    	$data['na'] = $this->getName();
    	$data['id'] = $this->id;
    	$data['ty'] = $this->getUnitType();
    	$data['co'] = '#FFFFFF';
    	$data['bo'] = $this->getBoat();
    	$data['cr'] = $this->col.','.$this->row; // <-- p.-e. inutile !!!!
    	return $data;
    }

    /*--------------------------------------------------------------------------
		XML Stuff
    --------------------------------------------------------------------------*/

    // Specific
    public function haveOrder($string) {
    	logMessage('Does '.$this->getUnitTypeName().' have '.$string.' ?');
    	$orders = $this->getUnitPossibleOrders();
    	foreach($orders as $order) {
    		if ($order==$string) {return true;}
    	}

    	return false;
    }

	// Getters for XML Rules
	public function getUnitTypeName() { return (string) $this->rulesValue('name'); }
	public function getUnitTypeMovement() { return (int) $this->rulesValue('move'); }
	public function getUnitPossibleOrders() {
		// Its some strings. In an array.
		$fullString = (string) $this->rulesValue('orders');
		$orders = explode(',', $fullString);
		return $orders;
	}

/*	
	public function getUnitTypeName() { return (string) $this->rulesValue('name'); }
	public function getUnitTypeName() { return (string) $this->rulesValue('name'); }
	public function getUnitTypeName() { return (string) $this->rulesValue('name'); }
*/

    // CAREFULL ! Gives a SimpleXMLElement as all are not simple value as string or numeric
    // You HAVE TO know what you ask for there !
    private function rulesValue($column) {
    	$ruleManager = new XMLObjectManager();
    	return $ruleManager->retrieveValueFromXml('units', 'id', $this->getUnitType(), $column);
    }

}
