<?php

namespace BspbSDK\Other;

class FloatPrecisionSetSaver {

	private $systemPrecition;
	private $systemSerializePrecition;

	private $deprecateChange;

	public function __construct() {
		$this->deprecateChange = false;

		$disabledFunctions = explode(',', ini_get('disable_functions'));
		if (array_key_exists('ini_set', $disabledFunctions)) {
			$this->deprecateChange = true;
			return;
		}
		$this->systemPrecition = ini_get('precision');
		$this->systemSerializePrecition = ini_get('serialize_precision');
	}

	public function changePrecisionForApiCall()
	{
		if ($this->deprecateChange) return;
        if ( ($this->systemPrecition !== false) && (intval($this->systemPrecition) !== -1) ) {
			ini_set('precision','-1');
		}
		if ( ($this->systemSerializePrecition !== false) && (intval($this->systemSerializePrecition) !== -1) ) {
			ini_set('serialize_precision', '-1');
		}
	}

	public function restoreUserSystemPrecision()
	{
		if ($this->deprecateChange) return;
        if ( ($this->systemPrecition !== false) && (intval($this->systemPrecition) != -1) ) {
			ini_set('precision',$this->systemPrecition);
		}
		if ( ($this->systemSerializePrecition !== false) && (intval($this->systemSerializePrecition) !== -1) ) {
			ini_set('serialize_precision', $this->systemPrecition);
		}
	}

}
