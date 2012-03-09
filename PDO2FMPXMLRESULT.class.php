<?php

/**
 * PDO2FMPXMLREUSLT
 *
 * @author 7gou (kiku33.com)
 * @author Hiroaki TOMIDA <tomida at hiroakio.org>
 * @license The BSD License
 * @version 1.0.0
 */

// Set Error Field Name
// (Appear into <RESULTSET> Elements)
define('PDO2FMPXMLRESULT_ERROR_FIELDNAME', 'Error');

// Date Format
define('PDO2FMPXMLRESULT_DATE_FORMAT', 'Y/m/d');
//define('PDO2FMPXMLRESULT_DATE_FORMAT', 'm/d/Y');

class pdo2fmxml
{

    var $fmpxmlresult_dom = '';
    var $t_FMPXMLRESULT = '';
    var $t_ERRORCODE = '';
    var $t_PDODUCT = '';
    var $t_DATABASE = '';
    var $t_METADATA = '';
    var $t_fields = array();
    var $t_fieldType = array();
    var $t_fieldName = array();
    var $t_RESULTSET = '';
    var $t_row = array();
    var $t_PDO = '';
    var $fxphp_array = array();
    var $errorCode = 0;
    var $fieldArray = array();
    var $recordArray = array();

    /**
     * Constructor sets up.
     * @param boolean $db Set <DATABASE> Name attribute.
     * @param boolean $layout Set <DATABASE> Layout attribute.
     * @return null
     */
    function pdo2fmxml($db = 'from_PDO_Database', $layout= 'from_PDO_Database' )
    {
        $this->fmpxmlresult_dom = new domDocument('1.0', 'UTF-8');

        // FMPXMLRESULT
        $this->t_FMPXMLRESULT = $this->fmpxmlresult_dom->appendChild($this->fmpxmlresult_dom->createElement('FMPXMLRESULT'));
        $this->t_FMPXMLRESULT->setAttribute('xmlns', '');

        // ErrorCode
        $this->t_ERRORCODE = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('ERRORCODE'));
        $this->t_ERRORCODE->appendChild($this->fmpxmlresult_dom->createTextNode('0'));

        // PRODUCT
        $this->t_PRODUCT = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('PRODUCT'));
        $this->t_PRODUCT->setAttribute('BUILD', '09/11/2011');
        $this->t_PRODUCT->setAttribute('NAME', 'PDO-Data to FMPXMLRESULT');
        $this->t_PRODUCT->setAttribute('VERSION', '1.0.0');

        // DATABASE
        $this->t_DATABASE = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('DATABASE'));
        $this->t_DATABASE->setAttribute('DATEFORMAT', 'MM/dd/yyyy');
        // LAYOUT Name
        $this->t_DATABASE->setAttribute('LAYOUT', $layout);
        // DB Name
        $this->t_DATABASE->setAttribute('NAME', $db);
        // Record Number
        $this->t_DATABASE->setAttribute('RECORDS', 0); 
        // TIMEFORMAT
        $this->t_DATABASE->setAttribute('TIMEFORMAT', 'HH:mm:ss');

        // METADATA
        $this->t_METADATA = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('METADATA'));

        // RESULTSET
        $this->t_RESULTSET = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('RESULTSET'));
        $this->t_RESULTSET->setAttribute('FOUND', 0);

        return null;
    }

    /**
     * Add database field's meta infomation.
     * @param string $fieldName Set <META> Name attribute.
     * @param string $type Set <META> Type attribute.
     * @return null
     */
    function addMetaData($fieldName, $type)
    {
        $i = count($this->t_fields)+1;
        $this->t_fields[$i] = $this->t_METADATA->appendChild($this->fmpxmlresult_dom->createElement('FIELD'));
        $this->t_fields[$i]->setAttribute('EMPTYOK', 'YES');
        $this->t_fields[$i]->setAttribute('MAXREPEAT', '1');
        $this->t_fields[$i]->setAttribute('NAME', $fieldName);
        
        // TYPE detect
        $fmtype = null;
        switch($type)
        {
            case 'TEXT': 
                $fmtype = 'TEXT';
                break;
            case 'INTEGER': 
                $fmtype = 'NUMBER';
                break;
            case 'NUMERIC': 
                $fmtype = 'NUMBER';
                break;
            case 'DATE': 
                $fmtype = 'DATE';
                break;
            case 'TIME': 
                $fmtype = 'TIME';
                break;
            case 'DATETIME': 
                $fmtype = 'TIMESTAMP';
                break;
            default:
                $fmtype = 'TEXT';
                break;
        }
        $this->t_fields[$i]->setAttribute('TYPE', $fmtype);
        $this->t_fieldType[$i] = $fmtype;
        $this->fieldArray[$i] = array
        (
            'EMPTYOK' => 'YES',
            'MAXREPEAT' => '1',
            'NAME' => $fieldName,
            'TYPE' => $fmtype
        );
        $fmtype = null;

        return null;
    }

    /**
     * Add database record's data from PDO::fetch().
     * @param object $recordObject Add record information under the <RESULTSET> elements.
     * @return null
     */
    function addRecordObject($recordObject)
    {
        $i = count($this->t_row);
        $this->t_row[$i] = $this->t_RESULTSET->appendChild($this->fmpxmlresult_dom->createElement('ROW'));
        $this->t_row[$i]->setAttribute('MODID', '0');
        $this->t_row[$i]->setAttribute('RECORDID', ($i+1));
        $field_no = 1;
        foreach( $recordObject as $fieldValues )
        {
            // Convert vertical tabulation to line feed
            $fieldValues = str_replace("\v", "\n", $fieldValues);
            $this->t_col[$i] = $this->t_row[$i]->appendChild($this->fmpxmlresult_dom->createElement('COL'));
            $this->t_data[$i] = $this->t_col[$i]->appendChild($this->fmpxmlresult_dom->createElement('DATA'));
            // Convert date format
            switch($this->t_fieldType[$field_no])
            {
                case 'DATE':
                    if ( '' !== (string)$fieldValues)
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT, strtotime($fieldValues))));
                    }
                    else
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                    }
                    break;

                case 'TIMESTAMP':
                    if ( '' !== (string)$fieldValues)
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT . ' H:i:s', strtotime($fieldValues))));
                    }
                    else
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                    }
                    break;

                default:
                    $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode($fieldValues));
                    break;
            }
            // Set recordArray for execute() 
            $this->recordArray[ $i+1 . '.0'] = array
            (
                $this->fieldArray[$field_no]['NAME'] => array( 0 => $fieldValues )
            );

            $field_no++;
        }

        return null;
    } 

    /**
     * Add database record's data from PDO::fetchAll().
     * @param object $recordsObject Add record information under the <RESULTSET> elements.
     * @return null
     */
    function addAllRecordsObject($recordsObject)
    {
        foreach ($recordsObject as $recordObject )
        {
            $i = count($this->t_row);
            $this->t_row[$i] = $this->t_RESULTSET->appendChild($this->fmpxmlresult_dom->createElement('ROW'));
            $this->t_row[$i]->setAttribute('MODID', '0');
            $this->t_row[$i]->setAttribute('RECORDID', ($i+1));
            $field_no = 1;
            foreach( $recordObject as $fieldValues )
            {
                // Convert vertical tabulation to line feed
                $fieldValues = str_replace("\v", "\n", $fieldValues);
                $this->t_col[$i] = $this->t_row[$i]->appendChild($this->fmpxmlresult_dom->createElement('COL'));
                $this->t_data[$i] = $this->t_col[$i]->appendChild($this->fmpxmlresult_dom->createElement('DATA'));
                // Convert date format
                switch($this->t_fieldType[$field_no])
                {
                    case 'DATE':
                        if ( '' !== (string)$fieldValues)
                        {
                            $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT, strtotime($fieldValues))));
                        }
                        else
                        {
                            $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                        }
                        break;

                    case 'TIMESTAMP':
                        if ( '' !== (string)$fieldValues)
                        {
                            $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT . ' H:i:s', strtotime($fieldValues))));
                        }
                        else
                        {
                            $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                        }
                        break;

                    default:
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode($fieldValues));
                        break;
                }
                // Set recordArray for execute() 
                $this->recordArray[ $i+1 . '.0'][$this->fieldArray[$field_no+1]['NAME']] = array
                (
                     0 => $fieldValues
                );
                $field_no++;
            }
        }
        return null;
    } 

    /**
     * Add database record data from array
     * @param array $record Add record information under the <RESULTSET> elements.
     * @return null
     */
    function addRecord($record)
    {
        $i = count($this->t_row);
        $this->t_row[$i] = $this->t_RESULTSET->appendChild($this->fmpxmlresult_dom->createElement('ROW'));
        $this->t_row[$i]->setAttribute('MODID', '0');
        $this->t_row[$i]->setAttribute('RECORDID', ($i+1));
        $field_no = 1;
        foreach( $record as $fieldValues )
        {
            // Convert vertical tabulation to line feed
            $fieldValues = str_replace("\v", "\n", $fieldValues);
            $this->t_col[$i] = $this->t_row[$i]->appendChild($this->fmpxmlresult_dom->createElement('COL'));
            $this->t_data[$i] = $this->t_col[$i]->appendChild($this->fmpxmlresult_dom->createElement('DATA'));
            // Convert date format
            switch($this->t_fieldType[$field_no])
            {
                case 'DATE':
                    if ( '' !== (string)$fieldValues)
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT, strtotime($fieldValues))));
                    }
                    else
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                    }
                    break;

                case 'TIMESTAMP':
                    if ( '' !== (string)$fieldValues)
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(date(PDO2FMPXMLRESULT_DATE_FORMAT . ' H:i:s', strtotime($fieldValues))));
                    }
                    else
                    {
                        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode(''));
                    }
                    break;

                default:
                    $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode($fieldValues));
                    break;
            }
            // Set recordArray for execute() 
            $this->recordArray[ $i+1 . '.0'] = array
            (
                $this->fieldArray[$field_no]['NAME'] => array( 0 => $fieldValues )
            );

            $field_no++;
        }

        return null;
    } 

    /**
     * Making Error FMPXMLRESULT.
     * @param int $errorCode Set FileMaker errorCode.
     * @return null
     */
    function makeErrorFMXML($errorCode = 401)
    {
        // delete RESULTSET
        $this->t_FMPXMLRESULT->removeChild($this->t_RESULTSET);
        $this->t_RESULTSET = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('RESULTSET'));
        $this->t_RESULTSET->setAttribute('FOUND', 0);
        $this->t_row = array();
        // delete META
        $this->t_FMPXMLRESULT->removeChild($this->t_METADATA);
        $this->t_METADATA = $this->t_FMPXMLRESULT->insertBefore($this->fmpxmlresult_dom->createElement('METADATA'), $this->t_RESULTSET);
        $this->t_fields[$lastFieldsNo] = $this->t_METADATA->appendChild($this->fmpxmlresult_dom->createElement('FIELD'));
        $this->t_fields[$lastFieldsNo]->setAttribute('EMPTYOK', 'YES');
        $this->t_fields[$lastFieldsNo]->setAttribute('MAXREPEAT', '1');
        $this->t_fields[$lastFieldsNo]->setAttribute('NAME', PDO2FMPXMLRESULT_ERROR_FIELDNAME);
        $this->t_fields[$lastFieldsNo]->setAttribute('TYPE', 'NUMBER');
        $this->fieldArray[$lastFieldsNo] = array
        (
            'EMPTYOK' => 'YES',
            'MAXREPEAT' => '1',
            'NAME' => PDO2FMPXMLRESULT_ERROR_FIELDNAME,
            'TYPE' => 'NUMBER' 
        );
        
        // delete array for execute
        $this->fxphp_array = array();
        $this->fieldArray = array();
        $this->recordArray = array();

        $i = 0; 
        $this->t_row[$i] = $this->t_RESULTSET->appendChild($this->fmpxmlresult_dom->createElement('ROW'));
        $this->t_row[$i]->setAttribute('MODID', '0');
        $this->t_row[$i]->setAttribute('RECORDID', ($i+1));
        $this->t_col[$i] = $this->t_row[$i]->appendChild($this->fmpxmlresult_dom->createElement('COL'));
        $this->t_data[$i] = $this->t_col[$i]->appendChild($this->fmpxmlresult_dom->createElement('DATA'));
        $this->t_data[$i]->appendChild($this->fmpxmlresult_dom->createTextNode($errorCode));
        // Set recordArray for execute() 
        $this->recordArray['1.0'][PDO2FMPXMLRESULT_ERROR_FIELDNAME] = array
        (
             0 => $errorCode
        );
 
        // Re-set ErrorCode
        $this->t_FMPXMLRESULT->removeChild($this->t_ERRORCODE);
        $this->t_ERRORCODE = $this->t_FMPXMLRESULT->insertBefore($this->fmpxmlresult_dom->createElement('ERRORCODE'), $this->t_PRODUCT);
        $this->t_ERRORCODE->appendChild($this->fmpxmlresult_dom->createTextNode($errorCode));
        $this->errorCode = $errorCode;

        return null;
    }

    /**
     * Re-count record's and write foundCount.
     * @return null
     */
    function refreshFoundCount()
    {
        $foundCount = 0;
        $foundCount = count($this->t_row);
        $this->t_DATABASE->setAttribute('RECORDS', $foundCount); 
        $this->t_RESULTSET->setAttribute('FOUND', $foundCount);

        return null;
    }

    /**
     * Set PDO error infomations.
     * @param string $pdoErrorCode set PDO::errorCode()
     * @param string $pdoErrorInfo set PDO::errorInfo()
     * @return null
     */
    function setPDOError($pdoErrorCode = '', $pdoErrorInfo = '')
    {
        // Set PDO error infomation
        $this->t_PDO = $this->t_FMPXMLRESULT->appendChild($this->fmpxmlresult_dom->createElement('PDO'));
        $this->t_PDO->setAttribute('errorCode', $pdoErrorCode);
        $this->t_PDO->setAttribute('errorInfo', $pdoErrorInfo[2]);
        return null;
    }

    /**
     * Output FMPXMLRESULT strings 
     * @return string FMPXMLRESULT XML
     */
    function saveXML()
    {
        $this->refreshFoundCount();
        $this->t_FMPXMLRESULT->removeChild($this->t_PDO);
        $this->fmpxmlresult_dom->formatOutput = true;
        return $this->fmpxmlresult_dom->saveXML();
    }

    /**
     * Output FMPXMLRESULT Array ( Like a FX.php $ReturnedData ) 
     *
     * Supported attributes
     *  * foundCount
     *  * fields
     *  * data
     *  * errorCode
     *
     * Not Supported attributes
     *  * linkNext
     *  * linkPrevious
     *  * URL
     *  * valueLists
     *   
     * @return Array FMPXMLRESULT Array
     */
    function execute()
    {
        $this->refreshFoundCount();
        // ErrorCode
        $this->fxphp_array['errorCode'] = (int)$this->errorCode;
        // foundCount
        $this->fxphp_array['foundCount'] = count($this->t_row);
        // fields
        $this->fxphp_array['fields'] = $this->fieldArray;
        // data
        $this->fxphp_array['data'] = $this->recordArray;
        return $this->fxphp_array;
    }

}

?>
