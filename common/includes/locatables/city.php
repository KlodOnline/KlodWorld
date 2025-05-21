<?php
/* =============================================================================
    City
============================================================================= */
class City extends Locatable {
	public function __construct(array $data) { 
		$this->setDefaultJsonData();
		parent::__construct($data); 
		$this->kind = 'City';
		$this->loadJsonData();
	}
    public function setDefaultJsonData(): void {
        $defaultData = [
            'name' => 'NowhereTown',
            'buildings' => '{}',
            'population' => 0,
        ];
        foreach ($defaultData as $key => $value) {
            $this->setJsonData($key, $value);
        }
    }
    public function getName() { return (string) $this->getJsonData('name'); }
    public function getBuildings() { return (string) $this->getJsonData('buildings'); }
    public function getPopulation() { return (int) $this->getJsonData('population'); }

    public function is_virtual() {
        if ( $this->col < 0 or $this->row < 0 ) { return true; }
        return false;
    }

    public function tokenize() {
        $data = [];
        $data['t'] = 'C';
        $data['id'] = $this->id;
        $data['na'] = $this->getName();
        $data['co'] = '#FFFFFF';
        $data['cr'] = $this->col.','.$this->row;
        return $data;
    }

    public function haveBuilding($buildingId) {
    	return false;
    }

	// CAREFULL ! Gives a SimpleXMLElement as all are not simple value as string or numeric
    // You HAVE TO know what you ask for there !
    private function rulesBuildingValue($column, $buildingId) {
    	$ruleManager = new XMLObjectManager();
    	return $ruleManager->retrieveValueFromXml('buildings', 'id', $buildingId, $column);
    }

}