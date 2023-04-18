<?php

declare(strict_types=1);
	class VirtualThermostat extends IPSModule
	{
		public function Create()
		{
			parent::Create();

    
            $this->RegisterVariableFloat('SetPoint', $this->Translate('Set Point Temprature'), '~Temperature.Room', 0);
            $this->EnableAction('SetPoint');
            $this->RegisterVariableFloat('Current', $this->Translate('Current Temperature'), '~Temperature.Room', 1);
		}

		public function ApplyChanges()
		{
			parent::ApplyChanges();
			if ($this->GetValue('Current') == 0) {
				$this->SetValue('Current', 19.5);
				$this->SetValue('SetPoint', 22.0);
			}
		}

        public function RequestAction($Ident, $Value)
        {
            $this->SetValue($Ident, $Value);
        }
	}