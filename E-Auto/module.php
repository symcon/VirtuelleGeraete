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
            $this->RegisterPropertyBoolean("Switching", false);
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
            if ($simpleMode) {
                $this->EnableAction("Status");
            }
            $this->MaintainVariable("Intensity", "Intensität", VARIABLETYPE_INTEGER, "~Intensity.100", 1, $simpleMode);
            if ($simpleMode) {
                $this->EnableAction("Intensity");
            }

            // Use/load the car through a wallbox
            $profileNameAmpere = "AmpereSlider_" . $this->ReadPropertyFloat("MinCurrent") . "_" . $this->ReadPropertyFloat("MaxCurrent");
            if (!$simpleMode && !IPS_VariableProfileExists($profileNameAmpere)) {
                IPS_CreateVariableProfile($profileNameAmpere, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText($profileNameAmpere, "", " A");
                IPS_SetVariableProfileValues($profileNameAmpere, $this->ReadPropertyFloat("MinCurrent"),$this->ReadPropertyFloat("MaxCurrent"), .1);
                IPS_SetVariableProfileDigits($profileNameAmpere, 1);
            }

            $profileNamePower = "PowerSlider_" . $this->ReadPropertyFloat("MinCurrent") . "_" . $this->ReadPropertyFloat("MaxCurrent");
            if (!$simpleMode && !IPS_VariableProfileExists($profileNamePower)) {
                IPS_CreateVariableProfile($profileNamePower, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText($profileNamePower, "", " W");
                IPS_SetVariableProfileValues($profileNamePower, 0,$this->ReadPropertyFloat("MaxCurrent") * 3 * 230, 100);
                IPS_SetVariableProfileDigits($profileNamePower, 0);
            }

            $numberPhases = $this->ReadPropertyInteger("Phases");
            $splitPhases = $this->ReadPropertyBoolean("SplitPhases");
            $switchPhase = $this->ReadPropertyBoolean("Switching");

            $profileNamePhases = "PhaseState_" . $numberPhases;
            if (!$simpleMode && ($numberPhases > 1) && !IPS_VariableProfileExists($profileNamePhases)) {
                IPS_CreateVariableProfile($profileNamePhases, VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileValues($profileNamePhases, 1,3, 0);
                IPS_SetVariableProfileAssociation($profileNamePhases, 1, "1-phased", "", 0);
                if ($numberPhases == 2) {
                    IPS_SetVariableProfileAssociation($profileNamePhases, 2, "2-phased", "", 0);
                }
                else {
                    IPS_SetVariableProfileAssociation($profileNamePhases, 3, "3-phased", "", 0);
                }
            }

            $this->MaintainVariable("Power", "Power (Target)", VARIABLETYPE_FLOAT, $profileNamePower, 0, !$simpleMode);
            if (!$simpleMode) {
                $this->EnableAction("Power");
            }

            $this->MaintainVariable("Phases", "Phases", VARIABLETYPE_INTEGER, $profileNamePhases, 1, !$simpleMode && $numberPhases > 1 && $switchPhase);
            if (!$simpleMode && $numberPhases > 1 && $switchPhase) {
                $this->EnableAction("Phases");
                if ($this->GetValue("Phases") == 0) {
                    $this->SetValue("Phases", $numberPhases);
                }
            }

            $this->MaintainVariable("Current", "Current", VARIABLETYPE_FLOAT, $profileNameAmpere, 2, !$simpleMode && $numberPhases == 1);
            if (!$simpleMode && $numberPhases == 1) {
                $this->EnableAction("Current");
            }
            $this->MaintainVariable("CurrentL123", "Current (L1/L2/L3)", VARIABLETYPE_FLOAT, $profileNameAmpere, 3, !$simpleMode && !$splitPhases && $numberPhases == 3);
            if (!$simpleMode && !$splitPhases && $numberPhases == 3) {
                $this->EnableAction("CurrentL123");
            }
            $this->MaintainVariable("CurrentL12", "Current (L1/L2)", VARIABLETYPE_FLOAT, $profileNameAmpere, 3, !$simpleMode && !$splitPhases && $numberPhases == 2);
            if (!$simpleMode && !$splitPhases && $numberPhases == 2) {
                $this->EnableAction("CurrentL12");
            }
            $this->MaintainVariable("CurrentL1", "Current (L1)", VARIABLETYPE_FLOAT, $profileNameAmpere, 4, !$simpleMode && $splitPhases && $numberPhases > 1);
            if (!$simpleMode && $splitPhases && $numberPhases > 1) {
                $this->EnableAction("CurrentL1");
            }
            $this->MaintainVariable("CurrentL2", "Current (L2)", VARIABLETYPE_FLOAT, $profileNameAmpere, 5, !$simpleMode && $splitPhases && $numberPhases >= 2);
            if (!$simpleMode && $splitPhases && $numberPhases >= 2) {
                $this->EnableAction("CurrentL2");
            }
            $this->MaintainVariable("CurrentL3", "Current (L3)", VARIABLETYPE_FLOAT, $profileNameAmpere, 6, !$simpleMode && $splitPhases && $numberPhases >= 3);
            if (!$simpleMode && $splitPhases && $numberPhases >= 3) {
                $this->EnableAction("CurrentL3");
            }

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
                case "Phases":
                    $this->SetValue("Phases", $Value);
                    $this->SetValue("CurrentL2", 0);
                    $this->SetValue("CurrentL3", 0);
                    break;
                case "CurrentL1":
                    $this->SetValue("CurrentL1", $Value);
                    break;
                case "CurrentL2":
                    if ($this->GetValue("Phases") != 3) {
                        die("Cannot set L2 when in 1 phase mode");
                    }
                    $this->SetValue("CurrentL2", $Value);
                    break;
                case "CurrentL3":
                    if ($this->GetValue("Phases") != 3) {
                        die("Cannot set L3 when in 1 phase mode");
                    }
                    $this->SetValue("CurrentL3", $Value);
                    break;
                case "CurrentL12":
                    $this->SetValue("CurrentL12", $Value);
                    break;
                case "CurrentL123":
                    $this->SetValue("CurrentL123", $Value);
                    break;
                case "SoC":
                    $this->SetValue("SoC", $Value);
                    break;
                case "Power":
                    $current = $Value / 230 / $this->ReadPropertyInteger("Phases");
                    if ($current < $this->ReadPropertyFloat("MinCurrent")) {
                        $current = 0;
                    }
                    if ($current > $this->ReadPropertyFloat("MaxCurrent")) {
                        $current = $this->ReadPropertyFloat("MaxCurrent");
                    }
                    $onePhaseCurrent = $Value / 230;
                    if ($onePhaseCurrent < $this->ReadPropertyFloat("MinCurrent")) {
                        $onePhaseCurrent = 0;
                    }
                    if ($onePhaseCurrent > $this->ReadPropertyFloat("MaxCurrent")) {
                        $onePhaseCurrent = $this->ReadPropertyFloat("MaxCurrent");
                    }

                    switch($this->ReadPropertyInteger("Phases")) {
                        case 1:
                            $this->SetValue("Phases", 1);
                            $this->SetValue("Current", $current);
                            break;
                        case 2:
                            if ($current == 0 && $this->ReadPropertyBoolean("Switching")) {
                                $this->SetValue("Phases", 1);
                                if ($this->ReadPropertyBoolean("SplitPhases")) {
                                    $this->SetValue("CurrentL1", $onePhaseCurrent);
                                    $this->SetValue("CurrentL2", 0);
                                }
                                else {
                                    $this->SetValue("CurrentL12", $onePhaseCurrent);
                                }
                            }
                            else {
                                $this->SetValue("Phases", 2);
                                if ($this->ReadPropertyBoolean("SplitPhases")) {
                                    $total = $current * 2;
                                    $this->SetValue("CurrentL1", floor($current));
                                    $this->SetValue("CurrentL2", $total - floor($current));
                                }
                                else {
                                    $this->SetValue("CurrentL12", $current);
                                }
                            }
                            break;
                        case 3:
                            if ($current == 0 && $this->ReadPropertyBoolean("Switching")) {
                                $this->SetValue("Phases", 1);
                                if ($this->ReadPropertyBoolean("SplitPhases")) {
                                    $this->SetValue("CurrentL1", $onePhaseCurrent);
                                    $this->SetValue("CurrentL2", 0);
                                    $this->SetValue("CurrentL3", 0);
                                }
                                else {
                                    $this->SetValue("CurrentL123", $onePhaseCurrent);
                                }
                            }
                            else {
                                $this->SetValue("Phases", 3);
                                if ($this->ReadPropertyBoolean("SplitPhases")) {
                                    $total = $current * 3;
                                    $this->SetValue("CurrentL1", floor($current));
                                    $this->SetValue("CurrentL2", floor($current));
                                    $this->SetValue("CurrentL3", $total - floor($current) * 2);
                                } else {
                                    $this->SetValue("CurrentL123", $current);
                                }
                            }
                            break;
                    }
                    break;
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
                    switch($this->ReadPropertyInteger("Phases")) {
                        case 1:
                            $value = $this->GetValue("Current") * $volt;
                            break;
                        case 2:
                            if (!$this->ReadPropertyBoolean("SplitPhases")) {
                                $value = $this->GetValue("CurrentL12") * $volt * $this->ReadPropertyInteger("Phases");
                            } else if ($this->ReadPropertyBoolean("SplitPhases")) {
                                $value += $this->GetValue("CurrentL1") * $volt;
                                $value += $this->GetValue("CurrentL2") * $volt;
                            }
                            break;
                        case 3:
                            if (!$this->ReadPropertyBoolean("SplitPhases")) {
                                $value = $this->GetValue("CurrentL123") * $volt * $this->ReadPropertyInteger("Phases");
                            } else if ($this->ReadPropertyBoolean("SplitPhases")) {
                                $value += $this->GetValue("CurrentL1") * $volt;
                                $value += $this->GetValue("CurrentL2") * $volt;
                                $value += $this->GetValue("CurrentL3") * $volt;
                            }
                            break;
                    }
                    break;
            }
            $this->SetValue("Power", $value);
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