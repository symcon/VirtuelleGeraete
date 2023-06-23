<?php

declare(strict_types=1);
	class EAuto extends IPSModule
	{
		public function Create()
		{
			parent::Create();

            $this->RegisterPropertyFloat("Capacity", 6000);
            $this->RegisterPropertyInteger("Variance", 3);
            $this->RegisterPropertyInteger("Interval", 3);

            $this->RegisterVariableBoolean("Status", "Status", "~Switch", 0);
            $this->EnableAction("Status");
            $this->RegisterVariableInteger("Intensity", "Intensität", "~Intensity.100", 1);
            $this->EnableAction("Intensity");
            $this->RegisterVariableFloat("Consumption", "Verbrauch", "~Watt", 2);

            $this->RegisterVariableInteger("SoC", "SoC (Ist)", "~Intensity.100", 3);
            $this->EnableAction("SoC");

            $this->RegisterTimer("Update", 0, "VM_Update(\$_IPS[\"TARGET\"]);");
            $this->RegisterTimer("Increase", 1000, "VM_Increase(\$_IPS[\"TARGET\"]);");
		}

		public function ApplyChanges()
		{
			parent::ApplyChanges();

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
                case "SoC":
                    $this->SetValue("SoC", $Value);
            }
            if (!$this->ReadPropertyInteger("Interval")) {
                $this->Update();
            }
        }

        public function Update()
        {
            if (!$this->GetValue("Intensity")) {
                $this->SetValue("Consumption", 0);
            }
            else {
                $this->SetValue("Consumption", $this->ReadPropertyFloat("Capacity") * ($this->GetValue("Intensity") / 100) * ((100 + (rand(0, $this->ReadPropertyInteger("Variance") * 100)/100) - ($this->ReadPropertyInteger("Variance")/2)) / 100));
            }
        }

        public function Increase()
        {
            if ($this->GetValue("Intensity")) {
                $this->SetValue("SoC", min(100, $this->GetValue("SoC")+1));
            }
        }
	}