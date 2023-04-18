<?php

declare(strict_types=1);
	class VirtualShutter extends IPSModule
	{
		public function Create()
		{
			parent::Create();

    
            $this->RegisterVariableInteger("Shutter", $this->Translate("Shutter"), "~Shutter", 0);
            $this->EnableAction("Shutter");
            $this->RegisterVariableInteger("Lamella", $this->Translate("Lamella"), "~Lamella", 1);
            $this->EnableAction("Lamella");
		}

		public function ApplyChanges()
		{
			parent::ApplyChanges();
		}

        public function RequestAction($Ident, $Value)
        {
            $this->SetValue($Ident, $Value);
        }
	}