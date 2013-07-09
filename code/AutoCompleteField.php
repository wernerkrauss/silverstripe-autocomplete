<?php

/**
 * Autocompleting text field, using jQuery
 * @package forms
 * @subpackage fields-formattedinput
 */
class AutoCompleteField extends TextField {
	/**
	 * Name of the class this field searches
	 * @var string
	 */
	private $sourceClass;

	/**
	 * Name of the field to use as a filter for searches and results
	 * @var string
	 */
	private $sourceField = array();

	/**
	 * Constant SQL condition used to filter out search results
	 * @var string 
	 */
	private $sourceFilter;
        
        /**
         * Array of filter classes for source fields
         * @var array
         */
        private $sourceFilterClasses = array();
        
        /**
         * Template for rendering the output for the value
         * @var string|array|SSViewer
         * @see ViewableData->renderWith()
         */
        private $valueTemplate;
        
        /**
         * Does the current autocomplete has relations?
         * @var array
         */
        private $hasRelation = array();

        /**
         * Is the autocomplete dependent to another form field?
         * @var string
         */
        private $filterField;
        
        /**
	 * The url to use as the live search source
	 * @var string
	 */
	protected $suggestURL;

	/**
	 * Maximum numder of search results to display per search
	 * @var integer
	 */
	protected $limit = 10;

	/**
	 * Minimum number of characters that a search will act on
	 * @var integer
	 */
	protected $minSearchLength = 2;

	/**
	 * Flag indicating whether a selection must be made from the existing list.
	 * By default free text entry is allowed.
	 * @var boolean
	 */
	protected $requireSelection = false;

	/**
	 * Create a new AutocompleteField. 
	 * 
	 * @param string $name The name of the field.
	 * @param string $title [optional] The title to use in the form.
	 * @param string $value [optional] The initial value of this field.
	 * @param int $maxLength [optional] Maximum number of characters.
	 * @param string $sourceClass [optional] The suggestion source class.
	 * @param string $sourceField [optional] The suggestion source field.
	 * @param string $sourceFilter [optional] The suggestion source filter.
	 */
	function __construct($name, $title = null, $value = '', $maxLength = null, $form = null, $sourceClass = null, $sourceField = null, $sourceFilter = null) {
		// set source
		$this->sourceClass = $sourceClass;
		$this->sourceField = $sourceField;
		$this->sourceFilter = $sourceFilter;

		// construct the TextField
		parent::__construct($name, $title, $value, $maxLength, $form);
	}

	function getAttributes() {
                $attributes = array(
				'data-source' => $this->getSuggestURL(),
				'data-min-length' => $this->getMinSearchLength(),
				'data-require-selection' => $this->getRequireSelection(),
				'autocomplete' => 'off'
			);
                if ($this->filterField) {
                    list($filter, $filterAs) = explode(':', $this->filterField);
                        
                    $attributes['data-filter-field'] = $filter;
                    if ($filterAs) {
                        $attributes['data-filter-field-as'] = $filterAs;
                    }
                    
                } 
		return array_merge(parent::getAttributes(), $attributes);
	}

	function Type() {
		return 'autocomplete text';
	}

	function Field($properties = array()) {

		// jQuery Autocomplete Requirements
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');

		// init script for this field
		Requirements::javascript(AUTOCOMPLETEFIELD_DIR . '/javascript/AutocompleteField.js');

		return parent::Field($properties);
	}

	/**
	 * Set the class from which to get Autocomplete suggestions.
	 * 
	 * @param string $className The name of the source class.
	 */
	public function setSourceClass(string $className) {
		$this->sourceClass = $className;
	}

	/**
	 * Get the class which is used for Autocomplete suggestions.
	 * 
	 * @return The name of the source class. 
	 */
	public function getSourceClass() {
		return $this->sourceClass;
	}

	/**
	 * Set the field from which to get Autocomplete suggestions.
	 * 
	 * @param string $field Name The name of the source field.
         * @param string $filter filter class, e.g. PartialMatch
	 */
	public function setSourceField(string $fieldName, string $filter = null) {
		$this->sourceField[$fieldName] = $fieldName;
                $this->sourceFilterClasses[$fieldName] = $filter ? $filter : 'PartialMatch';
                
                if (strpos($fieldName, '.')) {
                    $parts = explode('.',$fieldName);
                    $this->hasRelation[] = $parts[0];
                }
                
	}

	/**
	 * Get the field which is used for Autocomplete suggestions.
	 * 
	 * @return The name of the source field.
	 */
	public function getSourceField() {
		if (isset($this->sourceField))
			return $this->sourceField;
		return $this->getName();
	}

	/**
	 * Set the filter used to get Autocomplete suggestions.
	 * 
	 * @param string $filter The source filter.
	 */
	public function setSourceFilter(string $filter) {
		$this->sourceFilter = $filter;
	}

	/**
	 * Get the filter used for Autocomplete suggestions.
	 * 
	 * @return The source filter.
	 */
	public function getSourceFilter() {
		return $this->sourceFilter;
	}
        
	/**
	 * Set a Form Field as filter to get Autocomplete suggestions.
	 * 
	 * @param string|array $filter the filter field.
	 */
	public function setFilterField($filter, $filterAs = null) {
		$this->filterField =  $filter . ':' . $filterAs;
	}      
        
	public function setValueTemplate($template) {
		$this->valueTemplate = $template;
	}
	/**
	 * Set the URL used to fetch Autocomplete suggestions.
	 * 
	 * @param string $URL The URL used for suggestions.
	 */
	public function setSuggestURL($URL) {
		$this->suggestURL = $url;
	}

	public function setLimit($limit) {
		$this->limit = $limit;
	}

	public function getLimit() {
		return $this->limit;
	}

	public function setMinSearchLength($length) {
		$this->minSearchLength = $length;
	}

	public function getMinSearchLength() {
		return $this->minSearchLength;
	}

	public function setRequireSelection($requireSelection) {
		$this->requireSelection = $requireSelection;
	}

	public function getRequireSelection() {
		return $this->requireSelection;
	}

	/**
	 * Get the URL used to fetch Autocomplete suggestions. Returns null
	 * if the built-in mechanism is used.
	 *  
	 * @return The URL used for suggestions.
	 */
	public function getSuggestURL() {

		if (!empty($this->suggestURL))
			return $this->suggestURL;

		// Attempt to link back to itself
		return parse_url($this->Link(), PHP_URL_PATH) . '/Suggest';
	}

	protected function determineSourceClass() {
		if ($sourceClass = $this->sourceClass)
			return $sourceClass;

		$form = $this->getForm();
		if (!$form)
			return null;

		$record = $form->getRecord();
		if (!$record)
			return null;

		return $record->ClassName;
	}

	/**
	 * Handle a request for an Autocomplete list.
	 * 
	 * @param HTTPRequest $request The request to handle.
	 * @return A list of items for Autocomplete.
	 */
	function Suggest(HTTPRequest $request) {
		// Find class to search within
		$sourceClass = $this->determineSourceClass();
		if (!$sourceClass)
			return;

		// Find field to search within
		$sourceFields = $this->getSourceField();
		// input
		$q = Convert::raw2sql($request->getVar('term'));
		$limit = $this->getLimit();
                
                $sourceFieldFilter = array();
                foreach ($sourceFields as $field) {
                    $key = $field . ':' . $this->sourceFilterClasses[$field];
                    $sourceFieldFilter[$key] = $q;
                }
                
                $firstSourceField = reset($sourceFields);

		// Generate query
		$query = DataList::create($sourceClass)
//				->where("\"{$sourceField}\" LIKE '%{$q}%'")
                                ->filterAny($sourceFieldFilter)
                                ->setQueriedColumns(array('ID','Code'))
				->sort($firstSourceField)
				->limit($limit);
		if (isset($this->sourceFilter))
			$query = $query->where($this->sourceFilter);
                
                if ($filters = $request->getVar('filter')) {
                    foreach ($filters as $key => $val) {
                        $query = $query->filter(array(Convert::raw2sql($key) => Convert::raw2sql($val)));
                    }
                }

		// generate items from result
		$items = array();
                
                
                /**
                 * for now only one relation
                 */
                $relationName = count($this->hasRelation)
                        ? $this->hasRelation[0]
                        : false;

                
		foreach ($query as $item) {
                        if ($relationName) {
                            foreach ($item->$relationName() as $relationItem) {
                            $value = $this->valueTemplate
                                    ? $relationItem->customise(array($sourceClass => $item))->renderWith($this->valueTemplate)
                                    : $relationItem->$firstSourceField;
                            if (!in_array($value, $items) &&  stristr($value, $q)) {
                                    $items[] = $value;
                            }                                
                            }
                        } else {
                            $value = $this->valueTemplate
                                    ? $item->renderWith($this->valueTemplate)
                                    : $item->$firstSourceField;
                            if (!in_array($value, $items) && stristr($value, $q)) {
                                    $items[] = $value;
                            }
                        }
                        
		}
                
		// the response body
		return json_encode($items);
	}

}