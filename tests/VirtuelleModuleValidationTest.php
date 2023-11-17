<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class VirtuelleModuleValidationTest extends TestCaseSymconValidation
{
    public function testValidateVirtuelleModule(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateECarModule(): void
    {
        $this->validateModule(__DIR__ . '/../E-Car');
    }
    public function testValidateHeaterModule(): void
    {
        $this->validateModule(__DIR__ . '/../Heater');
    }
    public function testValidateLightModule(): void
    {
        $this->validateModule(__DIR__ . '/../Virtual Light');
    }
    public function testValidateMediaplayerModule(): void
    {
        $this->validateModule(__DIR__ . '/../Mediaplayer');
    }
    public function testValidatePVSystemModule(): void
    {
        $this->validateModule(__DIR__ . '/../PVSystem');
    }
    public function testValidateShutterModule(): void
    {
        $this->validateModule(__DIR__ . '/../Shutter');
    }
    public function testValidateThermostatModule(): void
    {
        $this->validateModule(__DIR__ . '/../Thermostat');
    }
    public function testValidateConsumptionCostsModule(): void
    {
        $this->validateModule(__DIR__ . '/../ConsumptionCosts');
    }
    public function testValidateVirtualCounterModule(): void
    {
        $this->validateModule(__DIR__ . '/../Virtual Counter');
    }

}