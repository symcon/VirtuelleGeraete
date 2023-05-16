<?php

declare(strict_types=1);
    class VirtualCounter extends IPSModule
    {
        public function Create()
        {
            parent::Create();

            $this->RegisterPropertyInteger('NumberOfCounters', 50);
            $this->RegisterPropertyFloat('MaxHourValue', 30);
            $this->RegisterPropertyFloat('MinHourValue', 10);
            $this->RegisterPropertyFloat('MaxDistanceToNextValue', 5);

            $this->RegisterTimer('AddValues', 1000 * 60 * 60, 'VC_AddValues(' . $this->InstanceID . ');');
        }

        public function ApplyChanges()
        {
            parent::ApplyChanges();

            for ($i = 1; $i <= $this->ReadPropertyInteger('NumberOfCounters'); $i++) {
                $this->RegisterVariableFloat('Counter' . $i, $this->Translate('Counter') . ' ' . $i, '~Electricity', 0);
            }

            $i = $this->ReadPropertyInteger('NumberOfCounters') + 1;
            while(@$this->GetIDForIdent('Counter' . $i) !== false) {
                $this->UnregisterVariable('Counter' . $i);
                $i++;
            }

            $this->AddValues();
        }

        public function AddValues() {
            $now = time();
            $archiveID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            for ($i = 1; $i <= $this->ReadPropertyInteger('NumberOfCounters'); $i++) {
                $counterID = $this->GetIDForIdent('Counter' . $i);
                if (!AC_GetLoggingStatus($archiveID, $counterID)) {
                    AC_SetLoggingStatus($archiveID, $counterID, true);
                    AC_SetAggregationType($archiveID, $counterID, 1);
                    // Delete initially logged value, so we can properly fill with dummy values
                    AC_DeleteVariableData($archiveID, $counterID, $now - (24 * 60 * 60), 0);
                }
                // Check for previous values within the last 30 days
                $lastLoggedTimestamp = $now - (30 * 24 * 60 * 60);
                $lastLoggedValue = 0;
                $lastLoggedDiff = $this->GetRandomFloat($this->ReadPropertyFloat('MinHourValue'), $this->ReadPropertyFloat('MaxHourValue'));
                $lastLoggedValues = AC_GetLoggedValues($archiveID, $counterID, $lastLoggedTimestamp, 0, 2);
                if (count($lastLoggedValues) > 0) {
                    $lastLoggedTimestamp = $lastLoggedValues[0]['TimeStamp'];
                    $lastLoggedValue = $lastLoggedValues[0]['Value'];
                    if (count($lastLoggedValues) > 1) {
                        $lastLoggedDiff = $lastLoggedValues[1]['Value'] - $lastLoggedValues[0]['Value'];
                    }
                }
                else {
                    $values[] = ['TimeStamp' => $lastLoggedTimestamp, 'Value' => 0];
                }
                $values = [];
                $lastLoggedTimestamp+= (60*60);
                while($lastLoggedTimestamp < $now) {
                    $nextValue = $lastLoggedValue + $this->GetRandomFloat(
                        max(
                            $this->ReadPropertyFloat('MinHourValue'),
                            $lastLoggedDiff - $this->ReadPropertyFloat('MaxDistanceToNextValue')
                        ),
                        min(
                            $this->ReadPropertyFloat('MaxHourValue'),
                            $lastLoggedDiff + $this->ReadPropertyFloat('MaxDistanceToNextValue')
                        )
                    );
                    $values[] = ['TimeStamp' => $lastLoggedTimestamp, 'Value' => $nextValue]; 
                    $lastLoggedTimestamp+= (60*60);
                    $lastLoggedDiff = $nextValue - $lastLoggedValue;
                    $lastLoggedValue = $nextValue;
                }
                AC_AddLoggedValues($archiveID, $counterID, $values);
                AC_ReAggregateVariable($archiveID, $counterID);
                $this->SetValue('Counter' . $i, $lastLoggedValue);
            }
        }

        public function CreateChart() {
            $mediaID = IPS_CreateMedia(4);
            IPS_SetParent($mediaID, $this->InstanceID);
            IPS_SetName($mediaID, $this->Translate('Chart'));
            IPS_SetMediaFile($mediaID, "media/$mediaID.chart", false);
            $datasets = [];
            for ($i = 1; $i <= $this->ReadPropertyInteger('NumberOfCounters'); $i++) {
                $color = '#' . dechex(rand(0, 0xffffff));
                $datasets[] = [
                    'variableID' => $this->GetIDForIdent('Counter' . $i),
                    'fillColor' => $color,
                    'strokeColor' => $color,
                    'timeOffset' => 0,
                    'side' => 'left',
                ];
            }
            IPS_SetMediaContent($mediaID, base64_encode(json_encode([ 'datasets' => $datasets ])));
        }

        private function GetRandomFloat($min, $max) {
            return $min + ($max - $min) * lcg_value();
        }
    }