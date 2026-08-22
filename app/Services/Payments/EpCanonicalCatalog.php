<?php

namespace App\Services\Payments;

use App\Support\UserType;
use LogicException;

/**
 * F11 canonical 17/41 municipal catalog (EP-KF-001 names + PO availability).
 * Not a seeder. Not loaded on deploy. #18 Bedemi is excluded.
 */
final class EpCanonicalCatalog
{
    public const EXCLUDED_BEDEMI_ACCOUNT = '530-92262338-74';

    public const SET_ALL8 = 'all8';

    public const SET_FL2 = 'fl2';

    public const SET_PRED2 = 'pred2';

    public const SET_LEGAL6 = 'legal6';

    public const SET_BIZ6 = 'biz6';

    public const SET_ALL8_MINUS_FL = 'all8MinusFl';

    /**
     * @return list<array{code: string, name: string, type_set: string, accounts: list<array{number: string, name: string, set: string}>}>
     */
    public static function types(): array
    {
        return [
            [
                'code' => 'prirez-porezu-na-dohodak',
                'name' => 'Prirez porezu na dohodak fizičkih lica',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9228009-77', 'name' => 'Prirez porezu na dohodak fizičkih lica.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'lokalni-porezi',
                'name' => 'Lokalni porezi',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9228014-62', 'name' => 'Porez na nepokretnosti.', 'set' => self::SET_ALL8],
                    ['number' => '530-9228020-44', 'name' => 'Porez na promet nepokretnosti.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'lokalne-administrativne-takse',
                'name' => 'Lokalne administrativne takse',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9226777-87', 'name' => 'Administrativne takse.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'lokalne-komunalne-takse',
                'name' => 'Lokalne komunalne takse',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92232405-51', 'name' => 'Komunalna taksa za korišćenje prostora na javnim površinama, osim radi prodaje štampe, knjiga i drugih publikacija, proizvoda starih i umjetničkih zanata i domaće radinosti.', 'set' => self::SET_ALL8],
                    ['number' => '530-92232494-75', 'name' => 'Komunalna taksa za držanje (priređivanje) muzike u ugostiteljskim objektima, osim muzike koja se reprodukuje mehaničkim sredstvima (gramofon, magnetofon, radio, TV i sl.).', 'set' => self::SET_BIZ6],
                    ['number' => '530-92232473-41', 'name' => 'Komunalna taksa za korišćenje vitrina radi izlaganja robe van poslovne prostorije.', 'set' => self::SET_BIZ6],
                    ['number' => '530-92232517-06', 'name' => 'Komunalna taksa za korišćenje reklamnih panoa i bilborda, osim pored magistralnih i regionalnih puteva.', 'set' => self::SET_ALL8_MINUS_FL],
                    ['number' => '530-92232468-56', 'name' => 'Komunalna taksa za korišćenje prostora za parkiranje motornih i priključnih vozila, motocikala i bicikala na uređenim i obilježenim mjestima.', 'set' => self::SET_BIZ6],
                    ['number' => '530-92232538-40', 'name' => 'Komunalna taksa za korišćenje slobodnih površina za kampove, postavljanje šatora ili drugih objekata privremenog karaktera.', 'set' => self::SET_ALL8],
                    ['number' => '530-92232431-70', 'name' => 'Komunalna taksa za držanje plovnih postrojenja, plovnih naprava i drugih objekata na vodi.', 'set' => self::SET_ALL8],
                    ['number' => '530-92232447-22', 'name' => 'Komunalna taksa za držanje restorana i drugih ugostiteljskih objekata i zabavnih objekata na vodi.', 'set' => self::SET_BIZ6],
                    ['number' => '530-9223247-07', 'name' => 'Ostale komunalne takse.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'komunalno-opremanje-zemljista',
                'name' => 'Naknada za komunalno opremanje građevinskog zemljišta',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92223906-37', 'name' => 'Naknada za komunalno opremanje građevinskog zemljišta za pravna lica.', 'set' => self::SET_LEGAL6],
                    ['number' => '530-92223911-22', 'name' => 'Naknada za komunalno opremanje građevinskog zemljišta za preduzetnike.', 'set' => self::SET_PRED2],
                    ['number' => '530-92223932-56', 'name' => 'Naknada za komunalno opremanje građevinskog zemljišta za građane.', 'set' => self::SET_FL2],
                ],
            ],
            [
                'code' => 'koriscenje-gradjevinskog-zemljista',
                'name' => 'Naknada za korišćenje građevinskog zemljišta (za zaostale obaveze)',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92223927-71', 'name' => 'Naknada za korišćenje građevinskog zemljišta za pravna lica.', 'set' => self::SET_LEGAL6],
                    ['number' => '530-92223948-08', 'name' => 'Naknada za korišćenje građevinskog zemljišta za preduzetnike.', 'set' => self::SET_PRED2],
                    ['number' => '530-92223953-90', 'name' => 'Naknada za korišćenje građevinskog zemljišta za građane.', 'set' => self::SET_FL2],
                ],
            ],
            [
                'code' => 'koriscenje-puteva',
                'name' => 'Naknada za korišćenje opštinskih i nekategorisanih puteva',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262320-31', 'name' => 'Naknada za vanredni prevoz.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262329-04', 'name' => 'Naknada za postavljanje natpisa na putu i pored puta.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262321-28', 'name' => 'Naknada za zakup putnog zemljišta.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262322-25', 'name' => 'Naknada za zakup drugog zemljišta koje pripada upravljaču puta.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262323-22', 'name' => 'Naknada za priključenje prilaznog puta na javni put.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262324-19', 'name' => 'Naknada za postavljanje cjevovoda, vodovoda, kanalizacije, električnih, telefonskih i telegrafskih vodova na javnom putu i sl.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262326-13', 'name' => 'Naknada za izgradnju komercijalnih objekata kojima je omogućen pristup sa puta.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262327-10', 'name' => 'Naknada za korišćenje komercijalnih objekata kojima je omogućen pristup sa puta.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'izgradnja-odrzavanje-lokalnih-puteva',
                'name' => 'Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opštinskog značaja (za zaostale obaveze)',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262296-06', 'name' => 'Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za pravna lica.', 'set' => self::SET_LEGAL6],
                    ['number' => '530-92262303-82', 'name' => 'Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za preduzetnike.', 'set' => self::SET_PRED2],
                    ['number' => '530-92262319-34', 'name' => 'Naknada za izgradnju i održavanje lokalnih puteva i drugih javnih objekata od opšteg značaja za građane.', 'set' => self::SET_FL2],
                ],
            ],
            [
                'code' => 'prihodi-opstinskih-organa',
                'name' => 'Prihodi koje svojom djelatnošću ostvare opštinski organi, organizacije i službe',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9226121-18', 'name' => 'Prihodi opštinskih organa, organizacija i službi.', 'set' => self::SET_ALL8],
                    ['number' => '530-9226228-85', 'name' => 'Ostali opštinski prihodi.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'kamate-i-kazne',
                'name' => 'Prihodi po osnovu kamata i kazni',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262371-72', 'name' => 'Prihodi po osnovu kamata za neblagovremeno plaćene lokalne prihode.', 'set' => self::SET_ALL8],
                    ['number' => '530-92262387-24', 'name' => 'Novčane kazne za koje je pokrenut prekršajni postupak prije 1. septembra 2011. godine.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'boravisna-taksa',
                'name' => 'Boravišna taksa',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9223205-36', 'name' => 'Boravišna taksa.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'turisticka-taksa',
                'name' => 'Turistička taksa',
                'type_set' => self::SET_FL2,
                'accounts' => [
                    ['number' => '530-9223206-33', 'name' => 'Turistička taksa.', 'set' => self::SET_FL2],
                ],
            ],
            [
                'code' => 'clanski-doprinos-turistickim-organizacijama',
                'name' => 'Članski doprinos u turističkim organizacijama',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-9223207-30', 'name' => 'Članski doprinos u turističkim organizacijama.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'troskovi-slobodan-pristup-informacijama',
                'name' => 'Troškovi postupka za slobodan pristup informacijama',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262334-86', 'name' => 'Troškovi postupka za slobodan pristup informacijama.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'taksa-akusticni-uredjaji',
                'name' => 'Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa',
                'type_set' => self::SET_BIZ6,
                'accounts' => [
                    ['number' => '530-92262335-83', 'name' => 'Taksa na upotrebu elektroakustičnih i akustičnih uređaja u ugostiteljskim objektima nakon 24 časa.', 'set' => self::SET_BIZ6],
                ],
            ],
            [
                'code' => 'premjestanje-vozila',
                'name' => 'Naknada troškova za premještanje vozila',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262336-80', 'name' => 'Naknada troškova za premještanje vozila.', 'set' => self::SET_ALL8],
                ],
            ],
            [
                'code' => 'ekonomsko-iskoriscavanje-kulturnih-dobara',
                'name' => 'Naknada za ekonomsko iskorišćavanje kulturnih dobara',
                'type_set' => self::SET_ALL8,
                'accounts' => [
                    ['number' => '530-92262337-77', 'name' => 'Naknada za ekonomsko iskorišćavanje kulturnih dobara.', 'set' => self::SET_ALL8],
                ],
            ],
        ];
    }

    /**
     * @return list<array{user_type: string, residential_status: string|null}>
     */
    public static function availabilityRows(string $set): array
    {
        return match ($set) {
            self::SET_ALL8 => array_merge(self::natural(UserType::PHYSICAL_PERSON), self::natural(UserType::ENTREPRENEUR), self::legal6()),
            self::SET_FL2 => self::natural(UserType::PHYSICAL_PERSON),
            self::SET_PRED2 => self::natural(UserType::ENTREPRENEUR),
            self::SET_LEGAL6 => self::legal6(),
            self::SET_BIZ6 => array_merge(self::natural(UserType::ENTREPRENEUR), self::companies4()),
            self::SET_ALL8_MINUS_FL => array_merge(self::natural(UserType::ENTREPRENEUR), self::legal6()),
            default => throw new LogicException('Unknown EP availability set: '.$set),
        };
    }

    /**
     * @return list<string>
     */
    public static function typeCodes(): array
    {
        return array_map(fn (array $type): string => $type['code'], self::types());
    }

    /**
     * @return list<string>
     */
    public static function accountNumbers(): array
    {
        $numbers = [];
        foreach (self::types() as $type) {
            foreach ($type['accounts'] as $account) {
                $numbers[] = $account['number'];
            }
        }

        return $numbers;
    }

    public static function assertConsistent(): void
    {
        $types = self::types();
        if (count($types) !== 17) {
            throw new LogicException('Canonical catalog must contain 17 types.');
        }

        $codes = self::typeCodes();
        if (count($codes) !== count(array_unique($codes))) {
            throw new LogicException('Canonical type codes must be unique.');
        }

        $numbers = self::accountNumbers();
        if (count($numbers) !== 41 || count($numbers) !== count(array_unique($numbers))) {
            throw new LogicException('Canonical catalog must contain 41 unique accounts.');
        }

        if (in_array(self::EXCLUDED_BEDEMI_ACCOUNT, $numbers, true)) {
            throw new LogicException('#18 Bedemi must not be in the EP catalog.');
        }

        foreach ($types as $type) {
            $union = [];
            foreach ($type['accounts'] as $account) {
                foreach (self::availabilityRows($account['set']) as $row) {
                    $union[self::rowKey($row)] = $row;
                }
            }
            ksort($union);

            $declared = [];
            foreach (self::availabilityRows($type['type_set']) as $row) {
                $declared[self::rowKey($row)] = $row;
            }
            ksort($declared);

            if (array_keys($union) !== array_keys($declared)) {
                throw new LogicException('Type-level set is not the union of accounts for '.$type['code']);
            }
        }
    }

    /**
     * @param  array{user_type: string, residential_status: string|null}  $row
     */
    public static function rowKey(array $row): string
    {
        return $row['user_type'].'|'.($row['residential_status'] ?? '');
    }

    /**
     * @return list<array{user_type: string, residential_status: string|null}>
     */
    private static function natural(string $userType): array
    {
        return [
            ['user_type' => $userType, 'residential_status' => 'resident'],
            ['user_type' => $userType, 'residential_status' => 'non-resident'],
        ];
    }

    /**
     * @return list<array{user_type: string, residential_status: string|null}>
     */
    private static function companies4(): array
    {
        return [
            ['user_type' => UserType::LIMITED_LIABILITY_COMPANY, 'residential_status' => null],
            ['user_type' => UserType::JOINT_STOCK_COMPANY, 'residential_status' => null],
            ['user_type' => UserType::GENERAL_PARTNERSHIP, 'residential_status' => null],
            ['user_type' => UserType::LIMITED_PARTNERSHIP, 'residential_status' => null],
        ];
    }

    /**
     * @return list<array{user_type: string, residential_status: string|null}>
     */
    private static function legal6(): array
    {
        return array_merge(self::companies4(), [
            ['user_type' => UserType::NGO_ASSOCIATION, 'residential_status' => null],
            ['user_type' => UserType::SPORTS_ORGANIZATION, 'residential_status' => null],
        ]);
    }
}
