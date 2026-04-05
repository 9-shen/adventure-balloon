<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LegalSettings extends Settings
{
    public ?string $identifiant_fiscal; // IF
    public ?string $cnss_number;        // CNSS
    public ?string $patente_number;     // Patente
    public ?string $registre_commerce;  // RC - Registre de Commerce
    public ?string $ice_number;         // ICE - Identifiant Commun de l'Entreprise

    public static function group(): string
    {
        return 'legal';
    }
}
