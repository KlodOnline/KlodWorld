<?php

/* =============================================================================
    Any object with coords !

============================================================================= */
class Locatable extends BDDObject
{
    public const TABLE_NAME = 'locatables';

    public int $id = 0;
    public int $col = -5;
    public int $row = -5;
    // public int $owner_id=0;
    public ?int $owner_id = null;
    public string $kind = 'Ground'; // ground/city/unit/loot/etc.
    public string $data = '{}';

    // public function __construct($data) 	{ parent::__construct($data); }
    public static function keyFields()
    {
        return ['id'];
    }
    public function nullableFields()
    {
        return ['owner_id'];
    }

    public function loadJsonData(): void
    {
        $jsonData = json_decode($this->data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                $this->setJsonData($key, $value);
            }
        }
    }

    public function setJsonData(string $key, $value): void
    {
        // Vérifie si la propriété est valide via l'enfant
        if (method_exists($this, 'isValidJsonData') && !$this->isValidJsonData($key, $value)) {
            throw new InvalidArgumentException("Invalid Json '$key' for " . get_class($this) . ".");
        }
        $jsonData = json_decode($this->data, true) ?: [];
        $jsonData[$key] = $value;
        $this->data = json_encode($jsonData);
    }

    protected function getJsonData(string $key)
    {
        $jsonData = json_decode($this->data, true) ?: [];
        return $jsonData[$key] ?? null;
    }

    public function isValidJsonData(string $key, $value): bool
    {
        $currentData = $this->getJsonData($key);
        // Vérifie si la clé existe et si le type est valide
        logMessage('is '.$key.' accept '.$value.' wich is '.gettype($value).' and is ATM '.gettype($currentData).' ? ');
        return ($currentData === null || gettype($value) === gettype($currentData));
    }

}
