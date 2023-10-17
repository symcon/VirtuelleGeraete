<?php

declare(strict_types=1);
    class VirtualThermostat extends IPSModule
    {
        public function Create()
        {
            parent::Create();

    
            $this->RegisterVariableFloat('SetPoint', $this->Translate('Set Point Temprature'), '~Temperature.Room', 0);
            $this->EnableAction('SetPoint');
            $this->RegisterVariableFloat('Current', $this->Translate('Current Temperature'), '~Temperature.Room', 1);

            $this->RegisterPropertyBoolean('LogCurrentTemperature', false);
            // Default values based on 16th October 2023, measured by WebFront Demo
            $this->RegisterPropertyString('HourValues', json_encode([
                [
                    'value' => 4.9
                ],
                [
                    'value' => 4.9
                ],
                [
                    'value' => 4.6
                ],
                [
                    'value' => 4.5
                ],
                [
                    'value' => 4.4
                ],
                [
                    'value' => 4.5
                ],
                [
                    'value' => 4.8
                ],
                [
                    'value' => 5.3
                ],
                [
                    'value' => 5.2
                ],
                [
                    'value' => 6.0
                ],
                [
                    'value' => 7.3
                ],
                [
                    'value' => 8.3
                ],
                [
                    'value' => 8.7
                ],
                [
                    'value' => 9.4
                ],
                [
                    'value' => 10.1
                ],
                [
                    'value' => 10.1
                ],
                [
                    'value' => 9.9
                ],
                [
                    'value' => 9.5
                ],
                [
                    'value' => 8.5
                ],
                [
                    'value' => 7.7
                ],
                [
                    'value' => 7.2
                ],
                [
                    'value' => 6.0
                ],
                [
                    'value' => 5.7
                ],
                [
                    'value' => 5.2
                ],
            ]));
            $this->RegisterTimer('AddValues', 0, 'VM_AddValues(' . $this->InstanceID . ');');
        }

        public function ApplyChanges()
        {
            parent::ApplyChanges();
            if ($this->GetValue('Current') == 0) {
                $this->SetValue('Current', 19.5);
                $this->SetValue('SetPoint', 22.0);
            }

            $logTemperature = $this->ReadPropertyBoolean('LogCurrentTemperature');
            $this->SetTimerInterval('AddValues', $logTemperature ? 60 * 60 * 1000 : 0);
            if ($logTemperature) {
                $this->AddValues();
            }
        }

        public function RequestAction($Ident, $Value)
        {
            $this->SetValue($Ident, $Value);
        }

        public function AddValues() {
            if (!$this->ReadPropertyBoolean('LogCurrentTemperature')) {
                return;
            }
            $now = time();
            $archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $variableID = $this->GetIDForIdent('Current');
            if (!AC_GetLoggingStatus($archiveID, $variableID)) {
                AC_SetLoggingStatus($archiveID, $variableID, true);
                AC_SetAggregationType($archiveID, $variableID, 0);
                // Delete initially logged value, so we can properly fill with dummy values
                AC_DeleteVariableData($archiveID, $variableID, $now - (24 * 60 * 60), 0);
            }
            // Check for previous values within the last 30 days
            $lastLoggedTimestamp = $now - (30 * 24 * 60 * 60);
            $lastLoggedValues = AC_GetLoggedValues($archiveID, $variableID, $lastLoggedTimestamp, 0, 1);
            if (count($lastLoggedValues) > 0) {
                $lastLoggedTimestamp = $lastLoggedValues[0]['TimeStamp'];
            }
            $values = [];
            $lastLoggedTimestamp -= $lastLoggedTimestamp % 3600;
            $lastLoggedTimestamp+= (60*60);
            $hourValues = json_decode($this->ReadPropertyString('HourValues'), true);
            $dateTime = new DateTime();
            while($lastLoggedTimestamp < $now) {
                $dateTime->setTimestamp($lastLoggedTimestamp);
                $nextValue = $hourValues[intval($dateTime->format('G'))]['value'];
                $values[] = ['TimeStamp' => $lastLoggedTimestamp, 'Value' => $nextValue]; 
                $lastLoggedTimestamp+= (60*60);
            }
            if (count($values) > 0) {
                AC_AddLoggedValues($archiveID, $variableID, $values);
                AC_ReAggregateVariable($archiveID, $variableID);
                $this->SetValue('Current', $values[count($values) - 1]['Value']);
            }
        }
    }