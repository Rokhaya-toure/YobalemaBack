<?php
namespace App\Enum;

enum TrajetStatus: string
{
    case DISPONIBLE = 'disponible';
    case COMPLET = 'complet';
    case TERMINE = 'termine';
    case ANNULE = 'annule';
}