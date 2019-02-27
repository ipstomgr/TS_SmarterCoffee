<?php

require_once __DIR__ . '/../libs/helper.php';

class TS_SmarterCoffee extends IPSModule
{
    use VariablenHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
        $this->RegisterPropertyInteger('CupsSoll', 12);
        $this->RegisterPropertyInteger('FilterBohnen', 1);
        $this->RegisterPropertyInteger('Strength', 2);
        $this->RegisterPropertyInteger('ZeitHeizplatte', 30);
        $this->RegisterPropertyInteger('ErkennungKanne', 0);  //0=ein, 1=aus
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

        $this->createVariablenProfiles();
        $this->RegisterVariableInteger('Cups', 'Tassen', 'Coffee_Cups');
        $this->RegisterVariableInteger('CupsSoll', 'Tassen Soll', 'Coffee_Cups', -4);
        $this->RegisterVariableInteger('Status', 'Status', '', 110);
        $this->RegisterVariableString('StatusHex', 'Status Hex', '', 111);
        $this->RegisterVariableInteger('Strength', 'Stärke', 'Coffee_Strength', -5);
        $this->RegisterVariableInteger('WaterLevel', 'Wasserstand', 'Coffee_water');
        $this->RegisterVariableInteger('ZeitHeizplatte', 'Zeit Heizplatte', 'Coffee_Warmhalten', -6);
        $this->RegisterVariableBoolean('FilterBohnen', 'Filter/Bohnen', 'Coffee_Filter', -7);
        $this->RegisterVariableBoolean('genugWasser', 'genug Wasser?', 'Coffee_Kanne', -1);
        $this->RegisterVariableBoolean('Heizplatte', 'Heizplatte', '~Switch');
        $this->RegisterVariableBoolean('Kaffeefertig', 'Kaffee fertig', 'Coffee_Kanne');
        $this->RegisterVariableBoolean('KanneinMaschine', 'Kanne in Maschine ?', 'Coffee_Kanne', -1);
        $this->RegisterVariableBoolean('Start', 'Start', '~Switch', -2);
        $this->RegisterVariableBoolean('Stop', 'Stop', '~Switch', -3);

        $this->RegisterVariableBoolean('Boiler', 'Boiler', '~Switch');
        $this->RegisterVariableBoolean('Working', 'Working', 'Coffee_Kanne');
        $this->RegisterVariableBoolean('Mahlwerk', 'Mahlwerk', '~Switch');
        $this->RegisterVariableString('Meldung', 'letzte Meldung', '');
        $this->RegisterVariableString('Meldung2', 'letzte Meldung2', '');

        $this->EnableAction('Strength');
        $this->EnableAction('CupsSoll');
        $this->EnableAction('Start');
        $this->EnableAction('Stop');
        $this->EnableAction('FilterBohnen');
        $this->EnableAction('Heizplatte');
        $this->EnableAction('ZeitHeizplatte');
		
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        // Buffer decodieren und in eine Variable schreiben
        $Buffer = utf8_decode($data->Buffer);
//    $this->SendDebug('ReceiveData',$Buffer, 0);
//    $this->SendDebug('Status Tassen',$this->parseStatus($Buffer)["cups"], 0);
    $byte0 = ord(substr($Buffer, 0, 1)); // immer 0x32 - 50 Startbyte
    if ($byte0 == 50) { //0x32
        SetValue($this->GetIDForIdent('Status'), $this->parseStatus($Buffer)['status']);
        SetValue($this->GetIDForIdent('Cups'), $this->parseStatus($Buffer)['cups']);
        SetValue($this->GetIDForIdent('CupsSoll'), $this->parseStatus($Buffer)['cups_soll']);
        SetValue($this->GetIDForIdent('Status'), $this->parseStatus($Buffer)['status']);
        SetValue($this->GetIDForIdent('StatusHex'), $this->parseStatus($Buffer)['statushex']);
        SetValue($this->GetIDForIdent('Strength'), $this->parseStatus($Buffer)['strength']);
        SetValue($this->GetIDForIdent('WaterLevel'), $this->parseStatus($Buffer)['waterlevel']);
        SetValue($this->GetIDForIdent('FilterBohnen'), $this->parseStatus($Buffer)['filter']);
        SetValue($this->GetIDForIdent('genugWasser'), $this->parseStatus($Buffer)['genugwasser']);
        SetValue($this->GetIDForIdent('Heizplatte'), $this->parseStatus($Buffer)['heizplatte']);
        SetValue($this->GetIDForIdent('Kaffeefertig'), $this->parseStatus($Buffer)['fertig']);
        SetValue($this->GetIDForIdent('KanneinMaschine'), $this->parseStatus($Buffer)['kanne']);
        SetValue($this->GetIDForIdent('Boiler'), $this->parseStatus($Buffer)['boiler']);
        SetValue($this->GetIDForIdent('Working'), $this->parseStatus($Buffer)['working']);
        SetValue($this->GetIDForIdent('Mahlwerk'), $this->parseStatus($Buffer)['grinder']);
    }

        if ($byte0 == 3) {  //0x32
            $byte1 = ord(substr($Buffer, 1, 1));
            switch ($byte1) {
            case 0:
                $meldung = 'Ok';
                break;
            case 1:
                $meldung = 'brühen in Arbeit';
                break;
            case 4:
                $meldung = 'gestoppt';
                break;
            case 5:
                $meldung = 'keine Kanne';
                break;
            case 6:
                $meldung = 'kein Wasser';
                break;
            case 7:
                $meldung = 'wenig Wasser';
                break;
            case 105:
                $meldung = 'fehlerhaftes Kommando';
                break;

            default:
                $meldung = 'unbekannt';
        }
            setValue($this->GetIDForIdent('Meldung'), $meldung);
        }

        if ($byte0 == 77) { //0x4d
            $byte1 = ord(substr($Buffer, 1, 1));
            switch ($byte1) {
            case 0:
                $meldung = 'Kannenerkennung ein';
                break;
            case 1:
                $meldung = 'Kannenerkennung aus';
                break;
            default:
                $meldung = 'unbekannt';
        }

            setValue($this->GetIDForIdent('Meldung2'), $meldung);
        }

        if ($byte0 == 80) {//0x4d
            $byte1 = ord(substr($Buffer, 1, 1));
            switch ($byte1) {
            case 0:
                $meldung = 'Ein-Tassen Mode aus';
                break;
            case 1:
                $meldung = 'Ein-Tassen Mode ein';
                break;
            default:
                $meldung = 'unbekannt';
        }
            setValue($this->GetIDForIdent('Meldung2'), $meldung);
        }
    }

    public function parseStatus($data)
    {
//    $byte0      = ord(substr($data,0,1));// immer 0x32 - 50 Startbyte
    $result['status'] = ord(substr($data, 1, 1)); //(carafe << 0) + (grind << 1) + (ready << 2) + (grinder << 3) + (heater << 4) + (hotplate << 6) + (working << 5) + (timer << 7))
    $result['statushex'] = dechex(ord(substr($data, 1, 1))); //(carafe << 0) + (grind << 1) + (ready << 2) + (grinder << 3) + (heater << 4) + (hotplate << 6) + (working << 5) + (timer << 7))

    $result['waterlevel'] = ord(substr($data, 2, 1));
//    $byte3      = ord(substr($data,3,1));// immer 0x00 - 0
        $result['strength'] = ord(substr($data, 4, 1));
        $result['cups'] = dechex(ord(substr($data, 5, 1))); // passt hier nicht,44 wird angezeigt bei 2C ist es aber 12 Tassen....
        // 1te Stelle die Anzahl die gekocht werden, 2te Stelle Sollwert
        //	  $byte6      = ord(substr($data,6,1));// immer 0x7E - 126 Endbyte

        $cups = str_pad($result['cups'], 2, '0', STR_PAD_LEFT);
        $arr = str_split($cups, 1);
        $result['cups'] = hexdec($arr[0]);
        $result['cups_soll'] = hexdec($arr[1]);

        $waterlevel = dechex($result['waterlevel']);
        $waterlevel = (str_pad($waterlevel, 2, '0', STR_PAD_LEFT));
        $arr = str_split($waterlevel, 1);
        $result['genugwasser'] = hexdec($arr[0]);
        $result['waterlevel'] = (hexdec($arr[1]));

        $stat = (str_pad(decbin($result['status']), 8, '0', STR_PAD_LEFT));
        $result['filter'] = substr($stat, 6, 1);
        $result['kanne'] = substr($stat, 7, 1);
        $result['heizplatte'] = substr($stat, 1, 1);
        $result['fertig'] = substr($stat, 5, 1);
        $result['boiler'] = substr($stat, 3, 1);
        $result['working'] = substr($stat, 2, 1);
        $result['grinder'] = substr($stat, 4, 1);

        return $result;
    }

    public function RequestAction($ident, $value)
    {
        switch ($ident) {
            case 'CupsSoll':
                $this->SetCups($value);
            break;
            case 'Strength':
                $this->SetStrength($value);
            break;
            case 'Start':
                $this->SetStart($value);
            break;
            case 'Stop':
                $this->SetStop($value);
            break;
            case 'FilterBohnen':
                $this->SetFilterBohnen($value);
            break;
            case 'Heizplatte':
                $this->SetHeizplatte($value);
            break;
            case 'ZeitHeizplatte':
                $this->SetZeitHeizplatte($value);
            break;

            default:
                throw new Exception('Invalid Ident');
        }
    }
    public function SetZeitHeizplatte(int $value)
    {
        SetValue($this->GetIDForIdent('ZeitHeizplatte'), $value);
    }

    public function SetConfig()
    {
        $cups = $this->ReadPropertyInteger('CupsSoll');
        $cups = dechex($cups); //hex2bin("08");
        $cups = hex2bin(str_pad($cups, 2, '0', STR_PAD_LEFT));

        $strength = $this->ReadPropertyInteger('Strength');
        $strength = dechex($strength); //hex2bin("02");
        $strength = hex2bin(str_pad($strength, 2, '0', STR_PAD_LEFT));

        $grind = $this->ReadPropertyInteger('FilterBohnen');
        $grind = intval($grind);
        $grind = dechex($grind);
        $grind = hex2bin(str_pad($grind, 2, '0', STR_PAD_LEFT));

        $minutes = $this->ReadPropertyInteger('ZeitHeizplatte');
        $minutes = dechex($minutes);
        $minutes = hex2bin(str_pad($minutes, 2, '0', STR_PAD_LEFT));

        $packet = CMD_SET_CONFIG;
        $packet .= $strength;
        $packet .= $cups;
        $packet .= $grind;
        $packet .= $minutes;
        $packet .= CMD_END;

        $this->SendPacket($packet);

        //$packet = CMD_SET_CONFIG.$stärke.$tassen.$grind.$minuten.CMD_END;

        sleep(1);
        $carafe = $this->ReadPropertyInteger('ErkennungKanne');
        $carafe = dechex($carafe);
        $carafe = hex2bin(str_pad($carafe, 2, '0', STR_PAD_LEFT));

        $packet = CMD_SET_CARAFE;
        $packet .= $carafe;
        $packet .= CMD_END;
        $this->SendPacket($packet);

        sleep(1);

        // 4D 00 7E = Kanne erkennung ein oder 4D 01 7E Kanne erkennung aus
        $packet = CMD_GET_CARAFE;
        $packet .= CMD_END;

        $this->SendPacket($packet);
    }

    public function SetStop(bool $value)
    {
        SetValue($this->GetIDForIdent('Stop'), $value);
        switch ($value) {
        case true:
          $packet = CMD_STOP_BREWING;
          $packet .= CMD_END;

          $this->SendPacket($packet);

          sleep(1);
          SetValue($this->GetIDForIdent('Stop'), false);
        break;
    }
    }

    public function SetStart(bool $value)
    {
        SetValue($this->GetIDForIdent('Start'), $value);
        if ($value == true) {
            $cups = GetValue($this->GetIDForIdent('CupsSoll'));
            $cups = dechex($cups); //hex2bin("08");
            $cups = hex2bin(str_pad($cups, 2, '0', STR_PAD_LEFT));

            $strength = GetValue($this->GetIDForIdent('Strength'));
            $strength = dechex($strength); //hex2bin("02");
            $strength = hex2bin(str_pad($strength, 2, '0', STR_PAD_LEFT));

            $grind = GetValue($this->GetIDForIdent('FilterBohnen'));
            $grind = intval($grind);
            $grind = dechex($grind);
            $grind = hex2bin(str_pad($grind, 2, '0', STR_PAD_LEFT));

            $minutes = GetValue($this->GetIDForIdent('ZeitHeizplatte'));
            $minutes = dechex($minutes);
            $minutes = hex2bin(str_pad($minutes, 2, '0', STR_PAD_LEFT));

            $packet = CMD_START_BREWING;
            $packet .= $cups;
            $packet .= $strength;
            $packet .= $minutes;
            $packet .= $grind;
            $packet .= CMD_END;

            $this->SendPacket($packet);

            sleep(1);
            SetValue($this->GetIDForIdent('Start'), false);
        }
    }

    public function SetFilterBohnen(bool $value)
    {
        $packet = CMD_SET_GRINDER;
        $packet .= CMD_END;

        $this->SendPacket($packet);
    }

    public function SetHeizplatte(bool $value)
    {
        $minutes = GetValue($this->GetIDForIdent('ZeitHeizplatte'));
        $minutes = hex2bin(str_pad(dechex($minutes), 2, '0', STR_PAD_LEFT));

        if ($value === true) {
            $packet = CMD_ENABLE_WARMING;
            $packet .= $minutes;
            $packet .= CMD_END;
        } else {
            $packet = CMD_DISABLE_WARMING;
            $packet .= CMD_END;
        }

        $this->SendPacket($packet);
    }

    public function SetCups(int $value)
    {
        $cups = hex2bin(str_pad(dechex($value), 2, '0', STR_PAD_LEFT));

        $packet = CMD_SET_CUPS;
        $packet .= $cups;
        $packet .= CMD_END;

        $this->SendPacket($packet);
    }

    public function SetStrength(int $value)
    {
        $strength = hex2bin(str_pad(dechex($value), 2, '0', STR_PAD_LEFT));

        $packet = CMD_SET_STRENGTH;
        $packet .= $strength;
        $packet .= CMD_END;

        $this->SendPacket($packet);
    }

    private function SendPacket($packet)
    {
        $JSON['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
        $JSON['Buffer'] = utf8_encode($packet);
        $SendData = json_encode($JSON);
        $this->SendDataToParent($SendData);
    }

    private function createVariablenProfiles()
    {
        $this->RegisterProfileIntegerEx('Coffee_Strength', 'Move', '', '', array(
            array(0, 'Schwach',  '', -1),
            array(1, 'Mittel',  '', -1),
            array(2, 'Stark', '', -1)
        ));
        $this->RegisterProfileIntegerEx('Coffee_water', 'Drops', '', '', array(
            array(0, 'leer',  '', -1),
            array(1, 'niedrig',  '', -1),
            array(2, 'halb', '', -1),
            array(3, 'voll', '', -1)
        ));
        $this->RegisterProfileIntegerEx('Coffee_Warmhalten', 'Clock', '', '', array(
            array(0, '0 Min.',  '', -1),
            array(5, '5 Min.',  '', -1),
            array(10, '10 Min.', '', -1),
            array(15, '15 Min.', '', -1),
            array(20, '20 Min.', '', -1),
            array(25, '25 Min.', '', -1),
            array(30, '30 Min.', '', -1)
        ));
        $this->RegisterProfileBooleanEx('Coffee_Filter', 'Information', '', '', array(
            array(false, 'Filter',  '', 0xFF0000),
            array(true, 'Bohnen',  '', 0x00FF00)
        ));
        $this->RegisterProfileBooleanEx('Coffee_Kanne', 'Information', '', '', array(
            array(false, 'Nein',  '', 0xFF0000),
            array(true, 'Ja',  '', 0x00FF00)
        ));
        $this->RegisterProfileInteger('Coffee_Cups', 'Intensity', '', ' Stk', 1, 12, 1);
    }
}
