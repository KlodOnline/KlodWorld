<?php

/* =============================================================================
    XMLObjectManager Class
    - Loads the appropriate XML file based on the type and hydrates XMLObject objects from it.
============================================================================= */
class XMLObjectManager
{
    /* -------------------------------------------------------------------------
        Retrieves a specific value from an XML file based on index and column.

        @param string $file Path to the XML file.
        @param string $indexName The attribute name to match.
        @param string $indexValue The attribute value to search for.
        @param string $columnName The column name whose value is to be retrieved.
        @return mixed|null The value found, or null if not found.
    ------------------------------------------------------------------------- */
    public function retrieveValueFromXml($file = '', $indexName = '', $indexValue = 0, $columnName = '')
    {
        $items = $this->allItems($file);
        foreach ($items as $item) {
            // Check if the current item matches the index name and value
            if ($item->__get($indexName) == $indexValue) {
                return $item->__get($columnName); // Return the requested column value
            }
        }
        return null; // Return null if no matching item is found
    }

    /* -------------------------------------------------------------------------
        Loads the XML file based on the type and hydrates the XMLObject objects.

        @param string $type The type of rules to load (e.g., 'units', 'buildings', etc.).
        @return array Array of hydrated XMLObject objects.


        Cache system ? Or put XML in RAM for faster access ?

    ------------------------------------------------------------------------- */
    public function allItems($type)
    {
        $cacheKey = "xml_" . $type;

        // if (apcu_exists($cacheKey)) { return apcu_fetch($cacheKey); }
        // $xmlItems = SessionManager::get('XML-'.$type);

        // if ($xmlItems==null) {
        $filePath = $this->getXmlFilePath($type);
        $xmlData = $this->load($filePath);
        $xmlItems = $this->hydrate($xmlData);

        // SessionManager::set('XML-'.$type, $xmlItems);
        // apcu_store($cacheKey, $xmlItems);

        // }
        logMessage('XML '.$type.' loaded.');
        return $xmlItems;
    }

    /* -------------------------------------------------------------------------
        Retrieves the correct XML file path based on the rule type.

        @param string $type The type of rules (e.g., 'units', 'buildings', etc.).
        @return string The XML file path.
    ------------------------------------------------------------------------- */
    private function getXmlFilePath($type)
    {
        $rule_folder = COMMON_PATH . '/param/rules/';
        return $rule_folder . $type.'.xml';
    }

    /* -------------------------------------------------------------------------
        Loads the XML file and hydrates the XMLObject objects based on the type.
    ------------------------------------------------------------------------- */
    private function load($filePath)
    {
        if (!file_exists($filePath)) {
            logForce("Can't find XML: " . $filePath);
            return null;
        }
        $xml = simplexml_load_file($filePath);
        if ($xml === false) {
            logForce("Invalid XML: " . $filePath);
            return null;
        }
        return $xml;
    }

    private function hydrate($xml)
    {
        $items = [];
        foreach ($xml as $itemData) {
            $items[] = new XMLObject($itemData);
        }
        return $items;
    }

}
