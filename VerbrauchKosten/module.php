<?php

declare(strict_types=1);
	class VerbrauchKosten extends IPSModule
	{
		public function Create()
		{
			parent::Create();

            $profileName = "WattSlider";
            if (!IPS_VariableProfileExists($profileName)) {
                IPS_CreateVariableProfile($profileName, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText($profileName, "", " W");
                IPS_SetVariableProfileValues($profileName, 0,5000, 10);
                IPS_SetVariableProfileDigits($profileName, 1);
                IPS_SetVariableProfileIcon($profileName, "Electricity");
            }

            $this->RegisterVariableFloat("Consumption", $this->Translate("StandBy Consumption"), $profileName, 0);
            $this->EnableAction("Consumption");

            $profileName = "EuroCent";
            if (!IPS_VariableProfileExists($profileName)) {
                IPS_CreateVariableProfile($profileName, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText($profileName, "", " €");
                IPS_SetVariableProfileValues($profileName, 0,10, 0.01);
                IPS_SetVariableProfileDigits($profileName, 2);
                IPS_SetVariableProfileIcon($profileName, "Repeat");
            }

            $this->RegisterVariableFloat("CostPerKiloWatt", $this->Translate("Cost per kWh"), "EuroCent", 1);
            $this->EnableAction("CostPerKiloWatt");

            $this->RegisterVariableFloat("TotalCostPerYear", $this->Translate("Total cost per year"), "~Euro", 2);
		}

        public function RequestAction($Ident, $Value)
        {
            $this->SetValue($Ident, $Value);
            $this->SetValue("TotalCostPerYear", $this->GetValue("Consumption")/1000*$this->GetValue("CostPerKiloWatt")*24*365);
        }
    }