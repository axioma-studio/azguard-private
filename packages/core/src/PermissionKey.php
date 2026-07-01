<?php

declare(strict_types=1);

namespace AzGuard;

/**
 * Public constants defining the AzGuard permission-key grammar.
 *
 * A permission key is a dotted string "{panel}.{resource}.{action}", where the
 * first segment is always the panel id (see {@see PermissionName}
 * and {@see Panel}). The single wildcard "*" is the global
 * super-admin permission — it matches every ability on a panel and short-circuits
 * resolution.
 *
 * Reference these constants instead of hardcoding the literals so a future change
 * to the grammar is a single edit, not a silent break across every consumer:
 *
 *   use AzGuard\PermissionKey;
 *
 *   $role->permissions();                 // [PermissionKey::WILDCARD] for super-admin
 *   in_array(PermissionKey::WILDCARD, …); // "is this the wildcard?"
 */
final class PermissionKey
{
    /** Global super-admin wildcard — matches every ability on a panel. */
    public const string WILDCARD = '*';

    /** Segment separator in a permission key ("{panel}.{resource}.{action}"). */
    public const string SEPARATOR = '.';

    private function __construct() {}
}
