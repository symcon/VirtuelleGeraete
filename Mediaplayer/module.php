<?php

declare(strict_types=1);
class VirtualMediaPlayer extends IPSModule
{
    public const PREVIOUS = 0;
    public const STOP = 1;
    public const PLAY = 2;
    public const PAUSE = 3;
    public const NEXT = 4;

    public const REPEAT_OFF = 0;
    public const REPEAT_CONTEXT = 1;
    public const REPEAT_TRACK = 2;

    public const PLACEHOLDER_NONE = '-';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterVariableInteger('Playback', $this->Translate('Playback'), '~PlaybackPreviousNext', 0);
        $this->EnableAction('Playback');
        $this->RegisterVariableFloat('Progress', $this->Translate('Progress'), '~Progress', 0);
        $this->EnableAction('Progress');
        $this->RegisterVariableString('Playlist', $this->Translate('Playlist'), '~Playlist', 0);
        $this->EnableAction('Playlist');
        $this->RegisterVariableString('Artist', $this->Translate('Artist'), '~Artist', 0);
        $this->RegisterVariableString('Song', $this->Translate('Song'), '~Song', 0);
        $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), '~Volume', 0);
        $this->EnableAction('Volume');
        $this->RegisterVariableInteger('Repeat', $this->Translate('Repeat'), '~Repeat', 0);
        $this->EnableAction('Repeat');
        $this->RegisterVariableBoolean('Mute', $this->Translate('Mute'), '~Mute', 0);
        $this->EnableAction('Mute');
        $this->RegisterVariableBoolean('Shuffle', $this->Translate('Shuffle'), '~Shuffle', 0);
        $this->EnableAction('Shuffle');
        $this->RegisterTimer('ProgressTimer', 0, 'VG_UpdateProgress($_IPS[\'TARGET\']);');
        if (@!IPS_GetObjectIDByIdent('Cover', $this->InstanceID)) {
            $mediaID = IPS_CreateMedia(1);
            $image = __DIR__ . '/cover.jpg';
            IPS_SetMediaFile($mediaID, 'file.png', false);
            IPS_SetMediaContent($mediaID, base64_encode((file_get_contents($image))));
            IPS_SetName($mediaID, 'Cover');
            IPS_SetParent($mediaID, $this->InstanceID);
            IPS_SetIdent($mediaID, 'Cover');
        }
    }

    public function RequestAction($Ident, $Value)
    {

        switch ($Ident) {
            case 'Playback':
                switch ($Value) {
                    case self::PREVIOUS:
                        $this->changeTitle(self::PREVIOUS);
                        $this->startPlayback();
                        return;

                    case self::STOP:
                        $this->stopPlayback();
                        return;

                    case self::PLAY:
                        $this->startPlayback();
                        $playlist = json_decode($this->GetValue('Playlist'), true);
                        $this->updateTitle($playlist['entries'], $playlist['current']);
                        return;

                    case self::PAUSE:
                        $this->stopPlayback();
                        return;

                    case self::NEXT:
                        $this->changeTitle(self::NEXT);
                        $this->startPlayback();
                        return;
                }
                break;

            case 'Playlist':
                $oldIndex = json_decode($this->GetValue('Playlist'), true)['current'];
                $newPlaylist = json_decode($Value, true);
                $newIndex = $newPlaylist['current'];
                if ($oldIndex != $newIndex) {
                    $this->SetValue('Progress', 0);
                    $this->updateTitle($newPlaylist['entries'], $newIndex);
                }

                // No break. Add additional comment above this line if intentional
            default:
                break;
        }

        $this->SetValue($Ident, $Value);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Set default values
        if ($this->GetValue('Playlist') == '') {
            $this->SetValue('Playlist', file_get_contents(__DIR__ . '/playlist.json'));
            $this->SetValue('Playback', self::PAUSE);
            $this->SetValue('Repeat', self::REPEAT_CONTEXT);
            $this->SetValue('Volume', 60);
        }
    }

    public function UpdateProgress()
    {
        $this->SetValue('Progress', $this->GetValue('Progress'));
        $playlist = json_decode($this->GetValue('Playlist'), true);
        $index = $playlist['current'];
        $duration = $playlist['entries'][$index]['duration'];
        $newValue = $this->GetValue('Progress') + 100 / $duration;
        if ($newValue > 100) {
            $this->changeTitle(self::NEXT);
        } else {
            $this->RequestAction('Progress', $newValue);
        }
    }

    private function startPlayback()
    {
        $this->SetTimerInterval('ProgressTimer', 1000);
        $this->SetValue('Playback', self::PLAY);
    }

    private function stopPlayback()
    {
        $this->SetTimerInterval('ProgressTimer', 0);
        $this->SetValue('Playback', self::PAUSE);
    }

    private function changeTitle($direction)
    {
        $playlist = json_decode($this->GetValue('Playlist'), true);
        $repeat = $this->GetValue('Repeat');
        $length = count($playlist['entries']);
        if ($repeat == self::REPEAT_TRACK) {
            $newIndex = $playlist['current'];
        } elseif ($this->GetValue('Shuffle')) {
            $newIndex = random_int(0, $length - 1);
        } else {
            $currentIndex = $playlist['current'];
            if ($direction == self::NEXT) {
                $newIndex = $currentIndex + 1;
            } else {
                $newIndex = $currentIndex - 1;
            }
            if ($newIndex > $length - 1) {
                switch ($repeat) {
                    case self::REPEAT_OFF:
                        $this->RequestAction('Playback', self::PAUSE);
                        $newIndex = 0;
                        break;

                    case self::REPEAT_CONTEXT:
                        $newIndex = 0;
                        break;

                    case self::REPEAT_TRACK:
                        $newIndex = $currentIndex;
                        break;
                }
            }
            if ($newIndex < 0) {
                $newIndex = $length - 1;
            }
        }
        $this->updateTitleProgress($playlist, $newIndex);
    }

    private function updateTitleProgress($playlist, $index)
    {
        $playlist['current'] = $index;
        $this->SetValue('Progress', 0);
        $this->RequestAction('Playlist', json_encode($playlist));
        $this->updateTitle($playlist['entries'], $index);
    }

    private function updateTitle($entries, $index)
    {
        $this->SetValue('Artist', $entries[$index]['artist']);
        $this->SetValue('Song', $entries[$index]['song']);
    }
}