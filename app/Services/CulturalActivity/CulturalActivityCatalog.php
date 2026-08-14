<?php

namespace App\Services\CulturalActivity;

use App\Exceptions\CulturalActivityRecordException;

/**
 * Frozen TS-012 §7.1 catalog. IDs are not RG-001 abbreviations.
 *
 * @phpstan-type CatalogRow array{source: string, type: string, target: string}
 */
final class CulturalActivityCatalog
{
    public const MOD_01 = 'TS12-MOD-01';

    public const MOD_02 = 'TS12-MOD-02';

    public const MOD_03 = 'TS12-MOD-03';

    public const MOD_04 = 'TS12-MOD-04';

    public const MOD_05 = 'TS12-MOD-05';

    public const MOD_06 = 'TS12-MOD-06';

    public const MOD_07 = 'TS12-MOD-07';

    public const ORG_01 = 'TS12-ORG-01';

    public const ORG_02 = 'TS12-ORG-02';

    public const ORG_03 = 'TS12-ORG-03';

    public const ORG_04 = 'TS12-ORG-04';

    public const ORG_05 = 'TS12-ORG-05';

    public const ORG_06 = 'TS12-ORG-06';

    public const ORG_07 = 'TS12-ORG-07';

    public const EV_01 = 'TS12-EV-01';

    public const EV_02 = 'TS12-EV-02';

    public const EV_03 = 'TS12-EV-03';

    public const EV_04 = 'TS12-EV-04';

    public const EV_05 = 'TS12-EV-05';

    public const EV_06 = 'TS12-EV-06';

    public const EV_07 = 'TS12-EV-07';

    public const EV_08 = 'TS12-EV-08';

    public const EV_09 = 'TS12-EV-09';

    public const EV_10 = 'TS12-EV-10';

    public const EV_11 = 'TS12-EV-11';

    public const EV_12 = 'TS12-EV-12';

    public const EV_13 = 'TS12-EV-13';

    public const EV_14 = 'TS12-EV-14';

    public const EV_15 = 'TS12-EV-15';

    public const EV_16 = 'TS12-EV-16';

    public const EV_17 = 'TS12-EV-17';

    public const EV_18 = 'TS12-EV-18';

    public const EV_19 = 'TS12-EV-19';

    public const EV_20 = 'TS12-EV-20';

    public const EV_21 = 'TS12-EV-21';

    public const MF_01 = 'TS12-MF-01';

    public const MF_02 = 'TS12-MF-02';

    public const MF_03 = 'TS12-MF-03';

    public const MF_04 = 'TS12-MF-04';

    public const MF_05 = 'TS12-MF-05';

    public const MF_06 = 'TS12-MF-06';

    public const MF_07 = 'TS12-MF-07';

    public const MF_08 = 'TS12-MF-08';

    public const MF_09 = 'TS12-MF-09';

    public const MF_10 = 'TS12-MF-10';

    public const MF_11 = 'TS12-MF-11';

    public const MF_12 = 'TS12-MF-12';

    public const NL_01 = 'TS12-NL-01';

    public const NL_02 = 'TS12-NL-02';

    public const NL_03 = 'TS12-NL-03';

    public const NL_04 = 'TS12-NL-04';

    public const NL_05 = 'TS12-NL-05';

    public const NL_06 = 'TS12-NL-06';

    /**
     * @var array<string, CatalogRow>
     */
    private const ROWS = [
        self::MOD_01 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.add.submit', 'target' => 'moderator_request'],
        self::MOD_02 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.add.approve', 'target' => 'moderator_request'],
        self::MOD_03 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.add.reject', 'target' => 'moderator_request'],
        self::MOD_04 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.remove.submit', 'target' => 'moderator_request'],
        self::MOD_05 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.remove.approve', 'target' => 'moderator_request'],
        self::MOD_06 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.remove.reject', 'target' => 'moderator_request'],
        self::MOD_07 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'mod.request.eligible', 'target' => 'request'],
        self::ORG_01 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.request.submit', 'target' => 'organizer_request'],
        self::ORG_02 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.request.approve', 'target' => 'organizer'],
        self::ORG_03 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.request.reject', 'target' => 'organizer_request'],
        self::ORG_04 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.deactivate', 'target' => 'organizer'],
        self::ORG_05 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'org.event.link', 'target' => 'event'],
        self::ORG_06 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.profile.significant', 'target' => 'organizer'],
        self::ORG_07 => ['source' => CulturalActivitySourceModule::TS_001, 'type' => 'org.initial_moderator.grant', 'target' => 'moderator_grant'],
        self::EV_01 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.create', 'target' => 'event'],
        self::EV_02 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.submit', 'target' => 'event'],
        self::EV_03 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.return', 'target' => 'event'],
        self::EV_04 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.resubmit', 'target' => 'event'],
        self::EV_05 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.approve', 'target' => 'event'],
        self::EV_06 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.direct_publish', 'target' => 'event'],
        self::EV_07 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.feature', 'target' => 'event'],
        self::EV_08 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.unfeature', 'target' => 'event'],
        self::EV_09 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.cancel', 'target' => 'event'],
        self::EV_10 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.cancellation_reason', 'target' => 'event'],
        self::EV_11 => ['source' => CulturalActivitySourceModule::TS_004, 'type' => 'occ.postpone', 'target' => 'occurrence'],
        self::EV_12 => ['source' => CulturalActivitySourceModule::TS_004, 'type' => 'occ.cancel', 'target' => 'occurrence'],
        self::EV_13 => ['source' => CulturalActivitySourceModule::TS_004, 'type' => 'occ.reschedule', 'target' => 'occurrence'],
        self::EV_14 => ['source' => CulturalActivitySourceModule::TS_004, 'type' => 'occ.location_change', 'target' => 'occurrence'],
        self::EV_15 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.proposal.submit', 'target' => 'proposal'],
        self::EV_16 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.proposal.approve', 'target' => 'proposal'],
        self::EV_17 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.proposal.return', 'target' => 'proposal'],
        self::EV_18 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.auto_archive', 'target' => 'event'],
        self::EV_19 => ['source' => CulturalActivitySourceModule::TS_004, 'type' => 'occ.auto_finish', 'target' => 'occurrence'],
        self::EV_20 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.published_direct_edit', 'target' => 'event'],
        self::EV_21 => ['source' => CulturalActivitySourceModule::TS_003, 'type' => 'event.unpublished_delete', 'target' => 'event'],
        self::MF_01 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.create', 'target' => 'manifestation'],
        self::MF_02 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.submit', 'target' => 'manifestation'],
        self::MF_03 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.return', 'target' => 'manifestation'],
        self::MF_04 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.publish', 'target' => 'manifestation'],
        self::MF_05 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.cancel', 'target' => 'manifestation'],
        self::MF_06 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.auto_archive', 'target' => 'manifestation'],
        self::MF_07 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.event.add', 'target' => 'manifestation'],
        self::MF_08 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.event.remove', 'target' => 'manifestation'],
        self::MF_09 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.event.move', 'target' => 'manifestation'],
        self::MF_10 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.organizer.change', 'target' => 'manifestation'],
        self::MF_11 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.cover.change', 'target' => 'manifestation'],
        self::MF_12 => ['source' => CulturalActivitySourceModule::TS_005, 'type' => 'mf.webinfo.change', 'target' => 'manifestation'],
        self::NL_01 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.activate', 'target' => 'subscription'],
        self::NL_02 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.unsubscribe', 'target' => 'subscription'],
        self::NL_03 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.reactivate', 'target' => 'subscription'],
        self::NL_04 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.preferences.change', 'target' => 'subscription'],
        self::NL_05 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.send.regular', 'target' => 'newsletter_cycle'],
        self::NL_06 => ['source' => CulturalActivitySourceModule::TS_011, 'type' => 'nl.send.priority', 'target' => 'newsletter_cycle'],
    ];

    /**
     * TS-012 §7.1 kolona „Poslovna radnja“, ključ = event_type.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'mod.add.submit' => 'Podnošenje zahtjeva za dodjelu Moderatora',
        'mod.add.approve' => 'Odobravanje dodjele Moderatora',
        'mod.add.reject' => 'Odbijanje dodjele Moderatora',
        'mod.remove.submit' => 'Pokretanje uklanjanja Moderatora',
        'mod.remove.approve' => 'Odobravanje uklanjanja Moderatora',
        'mod.remove.reject' => 'Odbijanje uklanjanja Moderatora',
        'mod.request.eligible' => 'Čeka registraciju → Podnesen (ADD ili Org-creation predloženi Moderator)',
        'org.request.submit' => 'Podnošenje zahtjeva za kreiranje Organizatora',
        'org.request.approve' => 'Odobrenje zahtjeva i kreiranje Organizatora',
        'org.request.reject' => 'Odbijanje zahtjeva za kreiranje Organizatora',
        'org.deactivate' => 'Deaktivacija Organizatora',
        'org.event.link' => 'Naknadno povezivanje događaja sa Organizatorom',
        'org.profile.significant' => 'Poslovno značajna izmjena podataka Organizatora',
        'org.initial_moderator.grant' => 'Dodjela početnog Moderatora pri odobrenju kreiranja',
        'event.create' => 'Kreiranje događaja',
        'event.submit' => 'Slanje na odobrenje',
        'event.return' => 'Vraćanje na doradu',
        'event.resubmit' => 'Ponovno slanje na odobrenje',
        'event.approve' => 'Odobravanje događaja',
        'event.direct_publish' => 'Direktna objava Urednika',
        'event.feature' => 'Isticanje događaja',
        'event.unfeature' => 'Uklanjanje isticanja',
        'event.cancel' => 'Otkazivanje događaja',
        'event.cancellation_reason' => 'Unos/dopuna razloga otkazivanja',
        'occ.postpone' => 'Odlaganje Održavanja',
        'occ.cancel' => 'Otkazivanje pojedinačnog Održavanja (nije kaskada Event cancel)',
        'occ.reschedule' => 'Promjena termina Održavanja',
        'occ.location_change' => 'Promjena lokacije Održavanja',
        'event.proposal.submit' => 'Podnošenje prijedloga izmjena',
        'event.proposal.approve' => 'Odobravanje prijedloga izmjena',
        'event.proposal.return' => 'Vraćanje prijedloga na doradu',
        'event.auto_archive' => 'Automatsko arhiviranje događaja',
        'occ.auto_finish' => 'Automatsko završavanje Održavanja',
        'event.published_direct_edit' => 'Direktna izmjena objavljenog (Urednik, bez registrovanog Org)',
        'event.unpublished_delete' => 'Trajno brisanje nikad objavljenog događaja',
        'mf.create' => 'Kreiranje Manifestacije',
        'mf.submit' => 'Slanje na odobrenje',
        'mf.return' => 'Vraćanje na doradu',
        'mf.publish' => 'Odobravanje / objava (uključujući uredničku direktnu objavu)',
        'mf.cancel' => 'Otkazivanje Manifestacije',
        'mf.auto_archive' => 'Automatsko arhiviranje Manifestacije',
        'mf.event.add' => 'Dodavanje događaja Manifestaciji',
        'mf.event.remove' => 'Uklanjanje događaja iz Manifestacije',
        'mf.event.move' => 'Premještanje događaja između Manifestacija',
        'mf.organizer.change' => 'Promjena Organizatora Manifestacije',
        'mf.cover.change' => 'Promjena naslovne fotografije',
        'mf.webinfo.change' => 'Promjena Web stranica / Više informacije',
        'nl.activate' => 'Aktivacija pretplate',
        'nl.unsubscribe' => 'Odjava',
        'nl.reactivate' => 'Ponovna aktivacija',
        'nl.preferences.change' => 'Promjena izbora Organizatora / preferenci',
        'nl.send.regular' => 'Slanje redovnog Newslettera',
        'nl.send.priority' => 'Slanje prioritetnog obavještenja',
    ];

    /**
     * @return CatalogRow
     */
    public static function row(string $catalogId): array
    {
        if (! isset(self::ROWS[$catalogId])) {
            throw new CulturalActivityRecordException('Nepoznat TS12 katalog ID.');
        }

        return self::ROWS[$catalogId];
    }

    public static function labelForEventType(string $eventType): string
    {
        return self::LABELS[$eventType] ?? $eventType;
    }
}
