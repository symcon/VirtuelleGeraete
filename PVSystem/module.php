<?php

declare(strict_types=1);
class PVSystem extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('Capacity', 10);
    }

    public function ApplyChanges()
    {
        $profileName = 'WattSlider' . $this->ReadPropertyInteger('Capacity');
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, VARIABLETYPE_FLOAT);
            IPS_SetVariableProfileText($profileName, '', ' W');
            IPS_SetVariableProfileValues($profileName, 0, $this->ReadPropertyInteger('Capacity') * 1000, 50);
            IPS_SetVariableProfileDigits($profileName, 1);
        }

        $this->RegisterVariableFloat('Production', $this->Translate('Production'), $profileName, 0);
        $this->EnableAction('Production');
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SetValue($Ident, $Value);
    }
}