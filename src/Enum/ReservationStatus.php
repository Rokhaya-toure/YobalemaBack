<?php

namespace App\Enum;

enum ReservationStatus: string
{
    case EN_ATTENTE = 'en_attente';   // demande envoyée
    case ACCEPTEE   = 'acceptee';     // conducteur accepte
    case REFUSEE    = 'refusee';
    // case PAYEE      = 'payee';        // paiement effectué
    case ANNULEE    = 'annulee';
    case TERMINEE   = 'terminee';     // trajet fini
}
