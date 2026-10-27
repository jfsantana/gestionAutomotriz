<?php

namespace App\Domain\Vehicles\Enums;

enum VehicleType: string
{
    case Motorcycle = 'motocicleta';
    case Car = 'carro';
    case Pickup = 'camioneta';
    case Truck = 'camion';
    case TractorUnit = 'chuto';
    case Bus = 'autobus';
    case Van = 'van';
    case HeavyMachinery = 'maquinaria_pesada';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Motorcycle->value => 'Motocicleta',
            self::Car->value => 'Carro',
            self::Pickup->value => 'Camioneta',
            self::Truck->value => 'Camión',
            self::TractorUnit->value => 'Chuto / Tractocamión',
            self::Bus->value => 'Autobús',
            self::Van->value => 'Van / Minibús',
            self::HeavyMachinery->value => 'Maquinaria pesada',
        ];
    }
}
