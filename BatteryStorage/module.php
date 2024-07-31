<?php

declare(strict_types=1);
class VirtualBatteryStorage extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyFloat('Capacity', 22000);
        $this->RegisterPropertyFloat('Power', 4600);
        $this->RegisterPropertyInteger('Variance', 6);
        $this->RegisterPropertyInteger('Interval', 3);

        $profileNameChangeMode = 'ChargeMode';
        if (!IPS_VariableProfileExists($profileNameChangeMode)) {
            IPS_CreateVariableProfile($profileNameChangeMode, VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileValues($profileNameChangeMode, 0, 2, 0);
            IPS_SetVariableProfileAssociation($profileNameChangeMode, 0, $this->Translate('Off'), '', -1);
            IPS_SetVariableProfileAssociation($profileNameChangeMode, 1, $this->Translate('Automatic'), '', -1);
            IPS_SetVariableProfileAssociation($profileNameChangeMode, 2, $this->Translate('Manual'), '', -1);
        }

        $this->RegisterVariableInteger('Mode', $this->Translate('Mode'), $profileNameChangeMode, 0);
        $this->EnableAction('Mode');

        $profileNamePower = 'ChargeSlider_' . $this->ReadPropertyFloat('Power') . '_' . $this->ReadPropertyFloat('Power');
        if (!IPS_VariableProfileExists($profileNamePower)) {
            IPS_CreateVariableProfile($profileNamePower, VARIABLETYPE_FLOAT);
            IPS_SetVariableProfileText($profileNamePower, '', ' W');
            IPS_SetVariableProfileValues($profileNamePower, -$this->ReadPropertyFloat('Power'), $this->ReadPropertyFloat('Power'), 100);
            IPS_SetVariableProfileDigits($profileNamePower, 0);
        }

        $this->RegisterVariableFloat('ChargePower', $this->Translate('Charge Power'), $profileNamePower, 1);

        $this->UpdateChargeAction();

        $this->RegisterVariableFloat('Consumption', $this->Translate('Consumption'), '~Watt', 2);

        $this->RegisterVariableInteger('SoC', $this->Translate('SoC'), '~Intensity.100', 3);
        $this->EnableAction('SoC');

        $this->RegisterTimer('Update', 0, 'VG_Update($_IPS["TARGET"]);');

        $this->RegisterTimer('Charge', 1000, 'VG_Charge($_IPS["TARGET"]);');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('Interval') * 1000);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'ChargePower':
                $this->SetValue('ChargePower', $Value);
                break;
            case 'Mode':
                $this->SetValue('Mode', $Value);
                $this->UpdateChargeAction();
                break;
            case 'SoC':
                $this->SetValue('SoC', $Value);
                break;
        }
        if (!$this->ReadPropertyInteger('Interval')) {
            $this->Update();
        }
    }

    public function Update()
    {
        if (!$this->GetValue('Mode') || ($this->GetValue('SoC') == 0 && $this->GetValue('ChargePower') < 0) || ($this->GetValue('SoC') == 100 && $this->GetValue('ChargePower') > 0)) {
            $this->SetValue('Consumption', 0);
        }
        else {
            $this->SetValue('Consumption', $this->GetValue('ChargePower') * ((100 + (rand(0, $this->ReadPropertyInteger('Variance') * 100) / 100) - ($this->ReadPropertyInteger('Variance') / 2)) / 100));
        }
    }

    public function Charge()
    {
        if ($this->GetValue('Consumption') > 0) {
            $this->SetValue('SoC', min(100, $this->GetValue('SoC') + 1));
        }
        elseif ($this->GetValue('Consumption') < 0) {
            $this->SetValue('SoC', max(0, $this->GetValue('SoC') - 1));
        }
    }

    private function UpdateChargeAction()
    {
        if ($this->GetValue('Mode') == 2) {
            $this->EnableAction('ChargePower');
        }
        else {
            $this->DisableAction('ChargePower');
        }
    }
}
