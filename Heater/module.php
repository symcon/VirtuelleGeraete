<?php

declare(strict_types=1);
class VirtualHeater extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyFloat('Capacity', 500);
        $this->RegisterPropertyInteger('Variance', 6);
        $this->RegisterPropertyInteger('Interval', 3);

        $this->RegisterVariableBoolean('Status', 'Status', '~Switch', 0);
        $this->EnableAction('Status');
        $this->RegisterVariableInteger('Intensity', $this->Translate('Intensity'), '~Intensity.100', 1);
        $this->EnableAction('Intensity');
        $this->RegisterVariableFloat('Consumption', $this->Translate('Consumption'), '~Watt', 2);

        $this->RegisterTimer('Update', 0, 'VG_Update($_IPS["TARGET"]);');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('Interval') * 1000);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Status':
                $this->SetValue('Status', $Value);
                $this->SetValue('Intensity', $Value ? 100 : 0);
                break;
            case 'Intensity':
                $this->SetValue('Status', $Value);
                $this->SetValue('Intensity', $Value);
                break;
        }
        if (!$this->ReadPropertyInteger('Interval')) {
            $this->Update();
        }
    }

    public function Update()
    {
        if (!$this->GetValue('Intensity')) {
            $this->SetValue('Consumption', 0);
        } else {
            $this->SetValue('Consumption', $this->ReadPropertyFloat('Capacity') * ($this->GetValue('Intensity') / 100) * ((100 + (rand(0, $this->ReadPropertyInteger('Variance') * 100) / 100) - ($this->ReadPropertyInteger('Variance') / 2)) / 100));
        }
    }
}
