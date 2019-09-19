<?php namespace ProsperWorks\SubResources;

use InvalidArgumentException as InvalidArg;
use ProsperWorks\CRM;
use ProsperWorks\TranslateResource;

/**
 * Easy way to include custom fields in requests. Provides some validation as well.
 *
 * @property string $name      The field name
 * @property string $valueName The value name
 *\SubResources
 * @author igorsantos07
 */
class CustomField
{
    use TranslateResource;

    //those two are used by the PW API
    public $custom_field_definition_id;
    public $value;
	
    //those two are just for consulting
    protected $name;
    protected $valueName;
    protected $altFields = ['name','valueName'];

    //and those, used for internal checks
    protected $type;
    protected $resources = [];
    protected $options = [];

    /**
     * CustomField constructor.
     * @param string|int $idOrName
     * @param string|array   $value
     * @param string     $resource (singular) Needed to distinguish between same-name fields available in different
     *                             resources, when the key given is the field name.
     */
    public function __construct(string $idOrName, $value = null, $resource = null)
    {
        $values = [];
        $field = CRM::fieldList('customFieldDefinition', $idOrName, true);

        // This hack is required to avoid a single object breaking sizeof below.
        if (is_object($field)) {
            $field = [$field];
        }

        switch (sizeof($field)) {
            case 1:
                if (is_array($field)) {
                    $field = current($field); //gets the first entry directly
                }
                break;

            case 0:
                throw new InvalidArg("Custom Field not found: $idOrName");

            default: //will only happen on string identifiers (name)
                if ($resource) {
                    //returns the first item found with the resource name in the available list
                    $field = array_filter($field, function ($f) use ($resource) {
                        return in_array($resource, $f->available_on);
                    });
                } else {
                    throw new InvalidArg("There's more than one '$idOrName' field. To distinguish we need the resource name as well.");
                }
        }
        $this->custom_field_definition_id = $field->id;
        $this->name = $field->name;
        $this->type = $field->data_type;
        $this->resources = $field->available_on;
        $this->options = $field->options ?? [];

        //validating $resource and options, if available
        if (is_array($value) && $this->type != "MultiSelect") {
			throw new InvalidArg("Invalid multiple values for field $field->name ($field->data_type) that is not a MultiSelect field.");
		}
		
        if ($resource && !in_array($resource, $this->resources)) {
            throw new InvalidArg("You requested a field ($idOrName) that is not available for ($resource).");
        }
        
        if ($this->options && $value) {
			if (!is_array($value)) $value = [$value];
			
			foreach ($value as $val) {
				if (!is_numeric($val)) {
					if ($val == "") {
						$valueName = "";
						$id = null;
					} else {
						$valueName = $val;
						$id = array_flip($this->options)[$val] ?? false;
					}
				} else {
					$valueName = $this->options[$val] ?? false;
					$id = $val;
				}
				
				if ( $valueName !== "" && (!$id || !$valueName) ) {
					$name = ($resource ? "$resource." : '') . $idOrName;
					$options = implode(', ', $this->options);
					
					throw new InvalidArg("Invalid value ($val) for field $name. Valid options are: $options");
				} else {
					$values[] = $id;
				}
			}
			
            if ($this->type == "MultiSelect") {
				$this->value = $values;
			} else {
				$this->value = $values[0];
			}
        } else {
            $this->value = $value;
        }
    }
	
	public function getValue() {
		if (count($this->options) > 0) {
			if ($this->type == "MultiSelect") {
				$values = [];
				foreach ($this->value as $val) {
					$values[] = $this->options[$val];
				}
				return $values;
			}
			
			if (!empty($this->value)) {
				return $this->options[$this->value];
			} else
				return false;
		} else {
			return $this->value;
		}
	}
}
