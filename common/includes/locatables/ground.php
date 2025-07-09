<?php

/* =============================================================================
    Ground
============================================================================= */
class Ground extends Locatable
{
    public function __construct(array $data)
    {
        $this->setDefaultJsonData();
        parent::__construct($data);
        $this->kind = 'Ground';
        $this->loadJsonData();
    }
    public function setDefaultJsonData(): void
    {
        $defaultData = [
            'ground_type' => 2,
            'road' => 0,
        ];
        foreach ($defaultData as $key => $value) {
            $this->setJsonData($key, $value);
        }
    }

    public function getGroundType()
    {
        return (int) $this->getJsonData('ground_type');
    }
    public function getRoad()
    {
        return (int) $this->getJsonData('road');
    }

    // Méthode pour obtenir les coordonnées en système Hexagonal (Cube ou Axial)
    public function coord($coordType = 'Cube')
    {
        // Créer une instance de Hexalib uniquement au moment de la conversion
        $hexalib = new Hexalib();

        // Convertir les coordonnées classiques col/row en coordonnées Hexagonales
        $coord = $hexalib->coord([$this->col, $this->row], 'Oddr'); // Utilisation de Axial pour obtenir la coordonnée

        // Convertir ensuite en Cube si nécessaire
        if ($coordType == 'Cube') {
            return $hexalib->convert($coord, 'Cube');
        }
        // Retourner en coordonnées Axial si c'est ce que l'on souhaite
        return $coord;
    }

    public function exposedData()
    {
        $resp = ['col' => $this->col, 'row' => $this->row, 'ground_type' => $this->getJsonData('ground_type')];

        return json_encode($resp);

    }

    public function tokenize()
    {
        $data = [];
        $data['t'] = 'T';
        $data['id'] = $this->getJsonData('ground_type');
        $data['ro'] = $this->getJsonData('road');
        if ($this->owner_id !== null) {
            $data['ow'] = $this->owner_id;
            $data['co'] = '#FFFFFF';
        }
        return $data;
    }

    // Getters for XML Rules
    public function getLandTypeName()
    {
        return (string) $this->rulesValue('name');
    }
    public function getLandTypeMove()
    {
        return (int) $this->rulesValue('move');
    }
    public function getLandTypeFov()
    {
        return (int) $this->rulesValue('fov');
    }

    /*
        public function getLandTypeMove() { return (int) $this->rulesValue('move'); }
        public function getLandTypeMove() { return (int) $this->rulesValue('move'); }
        public function getLandTypeMove() { return (int) $this->rulesValue('move'); }
    */

    // CAREFULL ! Gives a SimpleXMLElement as all are not simple value as string or numeric
    // You HAVE TO know what you ask for there !
    private function rulesValue($column)
    {
        $ruleManager = new XMLObjectManager();
        return $ruleManager->retrieveValueFromXml('lands', 'id', $this->getGroundType(), $column);
    }

}
