<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/helper.php';

class TS_SmarterCoffee extends IPSModule
{
    use VariablenHelper;

    private const DATA_ID = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';

    // Antworten der Maschine (Byte 1 der Quittung 0x03)
    private const MESSAGES = [
        0   => 'Ok',
        1   => 'brühen in Arbeit',
        4   => 'gestoppt',
        5   => 'keine Kanne',
        6   => 'kein Wasser',
        7   => 'wenig Wasser',
        105 => 'fehlerhaftes Kommando',
    ];
    private const CARAFE_MESSAGES = [
        0 => 'Kannenerkennung ein',
        1 => 'Kannenerkennung aus',
    ];
    private const ONE_CUP_MESSAGES = [
        0 => 'Ein-Tassen Mode aus',
        1 => 'Ein-Tassen Mode ein',
    ];

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
        // Der Buffer kommt UTF-8-kodiert an und wird wieder in Rohbytes gewandelt
        $buffer = mb_convert_encoding((string) ($data->Buffer ?? ''), 'ISO-8859-1', 'UTF-8');
        $this->SendDebug('Receive', $buffer, 1);

        if ($buffer === '') {
            return;
        }

        switch (ord($buffer[0])) {
            case 0x32: // Statusmeldung
                $this->HandleStatus($buffer);
                break;
            case 0x03: // Quittung eines Kommandos
                $this->HandleMessage('Meldung', $buffer, self::MESSAGES);
                break;
            case 0x4D: // Kannenerkennung
                $this->HandleMessage('Meldung2', $buffer, self::CARAFE_MESSAGES);
                break;
            case 0x50: // Ein-Tassen-Modus
                $this->HandleMessage('Meldung2', $buffer, self::ONE_CUP_MESSAGES);
                break;
        }
    }

    public function parseStatus(string $data)
    {
        if (strlen($data) < 6) {
            return [];
        }

        $status = ord($data[1]);
        $water = ord($data[2]);
        $cups = ord($data[5]); // oberes Nibble: Tassen, unteres Nibble: Sollwert
        $result = [];

        $result['status'] = $status;
        $result['statushex'] = dechex($status);
        $result['strength'] = ord($data[4]);
        $result['cups'] = $cups >> 4;
        $result['cups_soll'] = $cups & 0x0F;
        $result['genugwasser'] = ($water >> 4) > 0;
        $result['waterlevel'] = $water & 0x0F;

        // Statusbits: carafe=0, filter=1, ready=2, grinder=3, heater=4, working=5, hotplate=6, timer=7
        $result['kanne'] = (bool) ($status & 0x01);
        $result['filter'] = (bool) ($status & 0x02);
        $result['fertig'] = (bool) ($status & 0x04);
        $result['grinder'] = (bool) ($status & 0x08);
        $result['boiler'] = (bool) ($status & 0x10);
        $result['working'] = (bool) ($status & 0x20);
        $result['heizplatte'] = (bool) ($status & 0x40);

        return $result;
    }

    public function RequestAction($ident, $value)
    {
        switch ($ident) {
            case 'CupsSoll':
                $this->SetCups((int) $value);
                break;
            case 'Strength':
                $this->SetStrength((int) $value);
                break;
            case 'Start':
                $this->SetStart((bool) $value);
                break;
            case 'Stop':
                $this->SetStop((bool) $value);
                break;
            case 'FilterBohnen':
                $this->SetFilterBohnen((bool) $value);
                break;
            case 'Heizplatte':
                $this->SetHeizplatte((bool) $value);
                break;
            case 'ZeitHeizplatte':
                $this->SetZeitHeizplatte((int) $value);
                break;
            default:
                throw new Exception('Invalid Ident: ' . $ident);
        }
    }

    public function SetZeitHeizplatte(int $value)
    {
        $this->SetValue('ZeitHeizplatte', $value);
    }

    public function SetConfig()
    {
        $packet = CMD_SET_CONFIG
            . $this->toByte($this->ReadPropertyInteger('Strength'))
            . $this->toByte($this->ReadPropertyInteger('CupsSoll'))
            . $this->toByte($this->ReadPropertyInteger('FilterBohnen'))
            . $this->toByte($this->ReadPropertyInteger('ZeitHeizplatte'))
            . CMD_END;
        $this->SendPacket($packet);

        // Die Maschine braucht zwischen den Kommandos etwas Zeit
        sleep(1);
        $this->SendPacket(CMD_SET_CARAFE . $this->toByte($this->ReadPropertyInteger('ErkennungKanne')) . CMD_END);

        sleep(1);
        // 4D 00 7E = Kannenerkennung ein, 4D 01 7E = Kannenerkennung aus
        $this->SendPacket(CMD_GET_CARAFE . CMD_END);
    }

    public function SetStop(bool $value)
    {
        $this->SetValue('Stop', $value);
        if ($value) {
            $this->SendPacket(CMD_STOP_BREWING . CMD_END);
            sleep(1);
            $this->SetValue('Stop', false);
        }
    }

    public function SetStart(bool $value)
    {
        $this->SetValue('Start', $value);
        if ($value) {
            $packet = CMD_START_BREWING
                . $this->toByte($this->GetValue('CupsSoll'))
                . $this->toByte($this->GetValue('Strength'))
                . $this->toByte($this->GetValue('ZeitHeizplatte'))
                . $this->toByte((int) $this->GetValue('FilterBohnen'))
                . CMD_END;
            $this->SendPacket($packet);

            sleep(1);
            $this->SetValue('Start', false);
        }
    }

    public function SetFilterBohnen(bool $value)
    {
        // Das Kommando schaltet in der Maschine zwischen Filter und Bohnen um
        $this->SendPacket(CMD_SET_GRINDER . CMD_END);
    }

    public function SetHeizplatte(bool $value)
    {
        if ($value) {
            $packet = CMD_ENABLE_WARMING . $this->toByte($this->GetValue('ZeitHeizplatte')) . CMD_END;
        } else {
            $packet = CMD_DISABLE_WARMING . CMD_END;
        }
        $this->SendPacket($packet);
    }

    public function SetCups(int $value)
    {
        $this->SendPacket(CMD_SET_CUPS . $this->toByte($value) . CMD_END);
    }

    public function SetStrength(int $value)
    {
        $this->SendPacket(CMD_SET_STRENGTH . $this->toByte($value) . CMD_END);
    }

    private function HandleStatus(string $buffer): void
    {
        $s = $this->parseStatus($buffer);
        if ($s === []) {
            $this->SendDebug('Status', 'Paket zu kurz: ' . strlen($buffer) . ' Bytes', 0);
            return;
        }

        $this->SetValue('Status', $s['status']);
        $this->SetValue('StatusHex', $s['statushex']);
        $this->SetValue('Cups', $s['cups']);
        $this->SetValue('CupsSoll', $s['cups_soll']);
        $this->SetValue('Strength', $s['strength']);
        $this->SetValue('WaterLevel', $s['waterlevel']);
        $this->SetValue('FilterBohnen', $s['filter']);
        $this->SetValue('genugWasser', $s['genugwasser']);
        $this->SetValue('Heizplatte', $s['heizplatte']);
        $this->SetValue('Kaffeefertig', $s['fertig']);
        $this->SetValue('KanneinMaschine', $s['kanne']);
        $this->SetValue('Boiler', $s['boiler']);
        $this->SetValue('Working', $s['working']);
        $this->SetValue('Mahlwerk', $s['grinder']);
    }

    private function HandleMessage(string $ident, string $buffer, array $messages): void
    {
        $code = strlen($buffer) > 1 ? ord($buffer[1]) : -1;
        $this->SetValue($ident, $messages[$code] ?? 'unbekannt');
    }

    private function toByte($value): string
    {
        return pack('C', max(0, min(255, (int) $value)));
    }

    private function SendPacket(string $packet): void
    {
        $this->SendDebug('Send', $packet, 1);
        $json = json_encode([
            'DataID' => self::DATA_ID,
            'Buffer' => mb_convert_encoding($packet, 'UTF-8', 'ISO-8859-1'),
        ]);
        $this->SendDataToParent($json);
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
