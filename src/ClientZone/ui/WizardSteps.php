<?php

declare(strict_types=1);

/**
 * clientzone - Step indicator of the registration wizard.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

use ClientZone\Registration;

/**
 * Numbered list of wizard steps with the finished ones and the current one marked.
 */
class WizardSteps extends \Ease\Html\OlTag
{
    /**
     * @param list<string> $steps   Registration::STEP_* in order
     * @param string       $current step being shown
     */
    public function __construct(array $steps, string $current)
    {
        parent::__construct(null, ['class' => 'cz-wizard', 'aria-label' => _('Registration progress')]);

        $titles = [
            Registration::STEP_EMAIL => _('E-mail'),
            Registration::STEP_COMPANY => _('Company'),
            Registration::STEP_ACCESS => _('Access'),
            Registration::STEP_DONE => _('Finish'),
        ];
        $position = (int) array_search($current, $steps, true);

        foreach ($steps as $index => $step) {
            $class = $index < $position ? 'is-done' : ($index === $position ? 'is-current' : '');
            $attributes = ['class' => $class];

            if ($index === $position) {
                $attributes['aria-current'] = 'step';
            }

            $this->addItem(new \Ease\Html\LiTag(
                '<span class="cz-wizard-num">'.($index + 1).'</span> <span class="cz-wizard-title">'.$titles[$step].'</span>',
                $attributes,
            ));
        }
    }
}
