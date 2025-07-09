<?php

/* =============================================================================
    BDDObject Class
    - Needs to Contains everything needed to expose an obj
    saved in DB.
    - Don't save or read (ObjectModelManager does)

============================================================================= */
class BDDObject
{
    /* -------------------------------------------------------------------------
        Constructor: Initializes an object with the provided data array.
        Each key-value pair in the array is used to set the corresponding property
        of the object dynamically.

        @param array $data An associative array of property names and values.
    ------------------------------------------------------------------------- */
    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            try {
                $this->$key = $value;
            } catch (\Throwable $e) {
                // logMessage($e);
                // Barbaric mood: ignore errors and let the object get his
                // default property !
            }
        }

    }
    /* -------------------------------------------------------------------------
        Magic method __set: Handles attempts to set undefined properties.
        Logs a message indicating the undefined property access attempt.

        @param string $name The name of the undefined property.
        @param mixed  $value The value being assigned to the undefined property.
    ------------------------------------------------------------------------- */
    public function __set($name, $value)
    {
        $message = "The property '{$name}' is not defined in the class " . __CLASS__;
        logMessage($message);
    }
    /* -------------------------------------------------------------------------
        Converts the object's properties into a JSON string.
        Uses get_object_vars to retrieve all accessible non-static properties
        of the object as an associative array and encodes it in JSON format.

        @return string A JSON-encoded string representation of the object.
    ------------------------------------------------------------------------- */
    public function jsonData()
    {
        return json_encode(get_object_vars($this));
    }
    /* -------------------------------------------------------------------------
        Generates a unique key for the object based on its key fields.
        The key is a string composed of the values of the fields returned by the
        keyFields() method, concatenated with underscores ('_').

        @return string A unique key for the object.
    ------------------------------------------------------------------------- */
    public function key()
    {
        $keys = [];
        foreach ($this->keyFields() as $field) {
            $keys[] = $this->{$field};
        }
        return implode('_', $keys);
    }

    public function isLocatable()
    {
        if (isset($this->col) and isset($this->row)) {
            return true;
        }
        return false;
    }

    public function changeKeysValues($newValues)
    {
        foreach ($this->keyFields() as $key => $field) {
            $this->{$field} = $newValues[$key];
        }
    }

}
