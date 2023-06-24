<?php

declare(strict_types=1);
	class EAuto extends IPSModule
	{
		public function Create()
		{
			parent::Create();

            $this->RegisterPropertyInteger("Mode", 0); // 0 = Simple, 1 = Wallbox

            // Simple
            $this->RegisterPropertyFloat("Capacity", 6000);

            // Wallbox
            $this->RegisterPropertyInteger("Phases", 1);
            $this->RegisterPropertyFloat("MinCurrent", 6);
            $this->RegisterPropertyFloat("MaxCurrent", 16);
            $this->RegisterPropertyBoolean("SplitPhases", false);

            $this->RegisterPropertyInteger("Variance", 3);
            $this->RegisterPropertyInteger("Interval", 3);

            $this->RegisterVariableFloat("Consumption", "Verbrauch", "~Watt", 2);

            $this->RegisterVariableInteger("SoC", "SoC (Ist)", "~Intensity.100", 3);
            $this->EnableAction("SoC");

            $this->RegisterTimer("Update", 0, "VM_Update(\$_IPS[\"TARGET\"]);");
            $this->RegisterTimer("Increase", 1000, "VM_Increase(\$_IPS[\"TARGET\"]);");
		}

		public function ApplyChanges()
		{
			parent::ApplyChanges();

            $simpleMode = $this->ReadPropertyInteger("Mode") == 0;

            // Use/load the car like a dimmable device
            $this->MaintainVariable("Status", "Status", VARIABLETYPE_BOOLEAN, "~Switch", 0, $simpleMode);
            $this->MaintainAction("Status", $simpleMode);
            $this->MaintainVariable("Intensity", "Intensität", VARIABLETYPE_INTEGER, "~Intensity.100", 1, $simpleMode);
            $this->MaintainAction("Intensity", $simpleMode);

            // Use/load the car through a wallbox
            $profileName = "AmpereSlider_" . $this->ReadPropertyFloat("MinCurrent") . "_" . $this->ReadPropertyFloat("MaxCurrent");
            if (!$simpleMode && !IPS_VariableProfileExists($profileName)) {
                IPS_CreateVariableProfile($profileName, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText($profileName, "", " A");
                IPS_SetVariableProfileValues($profileName, $this->ReadPropertyFloat("MinCurrent"),$this->ReadPropertyFloat("MaxCurrent"), 1);
                IPS_SetVariableProfileDigits($profileName, 1);
            }

            $singlePhase = $this->ReadPropertyInteger("Phases") == 1;
            $splitPhases = $this->ReadPropertyBoolean("SplitPhases");
            $this->MaintainVariable("Current", "Current", VARIABLETYPE_FLOAT, $profileName, 0, !$simpleMode && $singlePhase);
            $this->MaintainAction("Current", !$simpleMode && $singlePhase);
            $this->MaintainVariable("CurrentL123", "Current (L1/L2/L3)", VARIABLETYPE_FLOAT, $profileName, 0, !$simpleMode && !$splitPhases && !$singlePhase);
            $this->MaintainAction("CurrentL123", !$simpleMode && !$splitPhases && !$singlePhase);
            $this->MaintainVariable("CurrentL1", "Current (L1)", VARIABLETYPE_FLOAT, $profileName, 0, !$simpleMode && $splitPhases && !$singlePhase);
            $this->MaintainAction("CurrentL1", !$simpleMode && $splitPhases && !$singlePhase);
            $this->MaintainVariable("CurrentL2", "Current (L2)", VARIABLETYPE_FLOAT, $profileName, 0, !$simpleMode && $splitPhases && !$singlePhase);
            $this->MaintainAction("CurrentL2", !$simpleMode && $splitPhases && !$singlePhase);
            $this->MaintainVariable("CurrentL3", "Current (L3)", VARIABLETYPE_FLOAT, $profileName, 0, !$simpleMode && $splitPhases && !$singlePhase);
            $this->MaintainAction("CurrentL3", !$simpleMode && $splitPhases && !$singlePhase);

            $this->SetTimerInterval("Update", $this->ReadPropertyInteger("Interval") * 1000);
		}

        public function RequestAction($Ident, $Value)
        {
            switch ($Ident) {
                case "Status":
                    $this->SetValue("Status", $Value);
                    $this->SetValue("Intensity", $Value ? 100 : 0);
                    break;
                case "Intensity":
                    $this->SetValue("Status", $Value);
                    $this->SetValue("Intensity", $Value);
                    break;
                case "Current":
                    $this->SetValue("Current", $Value);
                    break;
                case "CurrentL123":
                    $this->SetValue("CurrentL123", $Value);
                    break;
                case "CurrentL1":
                    $this->SetValue("CurrentL1", $Value);
                    break;
                case "CurrentL2":
                    $this->SetValue("CurrentL2", $Value);
                    break;
                case "CurrentL3":
                    $this->SetValue("CurrentL3", $Value);
                    break;
                case "SoC":
                    $this->SetValue("SoC", $Value);
            }
            if (!$this->ReadPropertyInteger("Interval")) {
                $this->Update();
            }
        }

        public function Update()
        {
            $volt = 230;
            $value = 0;
            switch($this->ReadPropertyInteger("Mode")) {
                case 0: // Simple Mode
                    $value = $this->ReadPropertyFloat("Capacity") * ($this->GetValue("Intensity") / 100);
                    break;
                case 1: //Wallbox Mode
                    if ($this->ReadPropertyInteger("Phases") == 1) {
                        $value = $this->GetValue("Current") * $volt;
                    } else if (!$this->ReadPropertyBoolean("SplitPhases")){
                        $value = $this->GetValue("CurrentL123") * $volt * $this->ReadPropertyInteger("Phases");
                    } else if ($this->ReadPropertyBoolean("SplitPhases")){
                        $value += $this->GetValue("CurrentL1") * $volt;
                        $value += $this->GetValue("CurrentL2") * $volt;
                        $value += $this->GetValue("CurrentL3") * $volt;
                    }
                    break;
            }
            // Write value with variance
            $this->SetValue("Consumption", $value * ((100 + (rand(0, $this->ReadPropertyInteger("Variance") * 100)/100) - ($this->ReadPropertyInteger("Variance")/2)) / 100));
        }

        public function Increase()
        {
            if ($this->GetValue("Consumption")) {
                $this->SetValue("SoC", min(100, $this->GetValue("SoC")+1));
            }
        }
	}