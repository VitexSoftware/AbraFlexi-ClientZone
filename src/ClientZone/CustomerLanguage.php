<?php

declare(strict_types=1);

/**
 * This file is part of the ClientZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-ClientZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ClientZone;

/**
 * Language of the customer, remembered in AbraFlexi so that everything sent to the
 * customer afterwards (contracts, the welcome e-mail with the access to MultiFlexi)
 * is written in the language chosen in ClientZone.
 *
 * The language is stored as a label (štítek) of the company (adresar), because
 * AbraFlexi has no language column there: "LANG_CS", "LANG_EN", ... Anybody reading
 * the company can find it: the provisioning playbook, the document mailer, a salesman.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class CustomerLanguage
{
    public const LABEL_PREFIX = 'LANG_';

    /**
     * Language used when nothing is known.
     */
    public const DEFAULT = 'en';

    /**
     * Two letter language code of a locale: "cs_CZ" gives "cs", "en_US.UTF-8" gives "en".
     *
     * @return string lower case code, DEFAULT for anything that is not a locale
     */
    public static function fromLocale(?string $locale): string
    {
        if (preg_match('/^([a-z]{2})(?:[_-][A-Za-z]{2})?(?:[.@].*)?$/i', trim((string) $locale), $matches) === 1) {
            return strtolower($matches[1]);
        }

        return self::DEFAULT;
    }

    /**
     * Language of the page the customer uses now.
     */
    public static function current(): string
    {
        return self::fromLocale(\Ease\Locale::$localeUsed);
    }

    /**
     * Label code of a language: "cs" gives "LANG_CS".
     */
    public static function label(string $language): string
    {
        return self::LABEL_PREFIX.strtoupper(self::fromLocale($language));
    }

    /**
     * Language found among the labels of a company.
     *
     * @param array<int, string>|string|null $labels labels as a list, or as the comma separated text AbraFlexi sends
     *
     * @return null|string language code, null when the company has no language label
     */
    public static function fromLabels(array|string|null $labels): ?string
    {
        $codes = \is_array($labels) ? $labels : explode(',', (string) $labels);

        foreach ($codes as $code) {
            $code = strtoupper(trim((string) preg_replace('/^code:/i', '', trim((string) $code))));

            if (preg_match('/^'.self::LABEL_PREFIX.'([A-Z]{2})$/', $code, $matches) === 1) {
                return strtolower($matches[1]);
            }
        }

        return null;
    }

    /**
     * Labels of a company with the language label set: other labels are kept, an older
     * language label is replaced.
     *
     * @param array<int, string>|string|null $labels current labels
     *
     * @return array<int, string>
     */
    public static function withLanguage(array|string|null $labels, string $language): array
    {
        $codes = \is_array($labels) ? $labels : explode(',', (string) $labels);
        $kept = [];

        foreach ($codes as $code) {
            $code = trim((string) preg_replace('/^code:/i', '', trim((string) $code)));

            if ($code !== '' && self::fromLabels([$code]) === null) {
                $kept[] = $code;
            }
        }

        $kept[] = self::label($language);

        return array_values(array_unique($kept));
    }

    /**
     * Make sure the label exists in AbraFlexi: a label that is not defined cannot be put on a record.
     */
    public static function ensureLabel(string $language): bool
    {
        $code = self::label($language);
        $label = new \AbraFlexi\Stitek(null, ['ignore404' => true]);
        $label->throwException = false;

        if ($label->recordExists('code:'.$code)) {
            return true;
        }

        $label->insertToAbraFlexi(['kod' => $code, 'nazev' => $code, 'vsbAdr' => true]);

        return $label->lastResponseCode === 201;
    }
}
