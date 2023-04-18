<?php

declare(strict_types=1);
	class VirtualLight extends IPSModule
	{
		public function Create()
		{
			parent::Create();

    
            $this->RegisterVariableBoolean("Status", $this->Translate("Status"), "~Switch", 0);
            $this->EnableAction("Status");
            $this->RegisterVariableInteger("Intensity", $this->Translate("Intensity"), "~Intensity.100", 1);
            $this->EnableAction("Intensity");
            $this->RegisterVariableInteger("Color", $this->Translate("Color"), "~HexColor", 2);
            $this->EnableAction("Color");
            $this->RegisterVariableInteger("Temperature", $this->Translate("Temperature"), "~TWColor", 2);
            $this->EnableAction("Temperature");
		}

		public function ApplyChanges()
		{
			parent::ApplyChanges();
            //Set default values
            if ($this->GetValue('Color') == 0) {
                $this->SetValue('Color', 3328428);
                $this->SetValue('Intensity', 40);
                $this->SetValue('Temperature',3408);
            }
		}

        public function RequestAction($Ident, $Value)
        {
            $this->SetValue($Ident, $Value);
        }
	}