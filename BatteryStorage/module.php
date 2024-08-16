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

        $profileNamePower = 'ChargeSlider_' . $this->ReadPropertyFloat('Power');
        if (!IPS_VariableProfileExists($profileNamePower)) {
            IPS_CreateVariableProfile($profileNamePower, VARIABLETYPE_FLOAT);
            IPS_SetVariableProfileText($profileNamePower, '', ' W');
            IPS_SetVariableProfileValues($profileNamePower, 0, $this->ReadPropertyFloat('Power'), 100);
            IPS_SetVariableProfileDigits($profileNamePower, 0);
        }

        $profileNameCapacity = 'Capacity_' . $this->ReadPropertyFloat('Capacity');
        if (!IPS_VariableProfileExists($profileNameCapacity)) {
            IPS_CreateVariableProfile($profileNameCapacity, VARIABLETYPE_FLOAT);
            IPS_SetVariableProfileText($profileNameCapacity, '', ' kWh');
            IPS_SetVariableProfileValues($profileNameCapacity, 0, $this->ReadPropertyFloat('Capacity'), 100);
            IPS_SetVariableProfileDigits($profileNameCapacity, 2);
        }

        $this->RegisterVariableFloat('ChargePower', $this->Translate('Charge Power'), $profileNamePower);
        $this->EnableAction('ChargePower');
        $this->RegisterVariableFloat('DischargePower', $this->Translate('Discharge Power'), $profileNamePower);
        $this->EnableAction('DischargePower');

        $this->RegisterVariableFloat('Consumption', $this->Translate('Consumption'), '~Watt');

        $this->RegisterVariableFloat('SoC', $this->Translate('SoC'), $profileNameCapacity);
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
                $this->SetValue('DischargePower', 0);
                break;
            case 'DischargePower':
                $this->SetValue('DischargePower', $Value);
                $this->SetValue('ChargePower', 0);
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
        $charge = $this->GetValue('ChargePower') - $this->GetValue('DischargePower');
        if (($this->GetValue('SoC') == 0 && ($charge < 0)) || ($this->GetValue('SoC') == 100 && ($charge > 0))) {
            $this->SetValue('Consumption', 0);
        }
        else {
            $this->SetValue('Consumption', $charge * ((100 + (rand(0, $this->ReadPropertyInteger('Variance') * 100) / 100) - ($this->ReadPropertyInteger('Variance') / 2)) / 100));
        }
    }

    public function Charge()
    {
         // As charge runs every second, we produced Consumption W/s in that time
         // Convert it to kW/h
        $newSoC = $this->GetValue('SoC') + ($this->GetValue('Consumption') / 60 / 60 / 1000);
        $this->SetValue('SoC', max(0, min($this->ReadPropertyFloat('Capacity'), $newSoC)));
    }
}
