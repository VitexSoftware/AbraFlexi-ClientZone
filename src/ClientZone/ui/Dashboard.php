<?php

declare(strict_types=1);

/**
 * clientzone - Customer dashboard after login.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

use ClientZone\Customer;

/**
 * Attention items first (unpaid / pending), then relationship summary.
 */
class Dashboard extends \Ease\Html\DivTag
{
    public function __construct(Customer $customer)
    {
        parent::__construct(null, ['class' => 'cz-dashboard']);

        $summary = $customer->getDashboardSummary();
        $hasAttention = $summary['unpaid'] !== []
            || $summary['pendingOrders'] !== []
            || $summary['unpaidReceived'] !== [];

        $intro = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-intro']));
        $intro->addItem(new \Ease\Html\DivTag(_('My overview'), ['class' => 'eyebrow']));
        $intro->addItem(new \Ease\Html\H1Tag(
            $summary['companyName'] !== ''
                ? sprintf(_('Welcome, %s'), $summary['companyName'])
                : _('Welcome'),
        ));
        $intro->addItem(new \Ease\Html\PTag(
            $hasAttention
                ? _('Please review the open items below — they need your attention.')
                : _('Here is a summary of your business relationship with us.'),
            ['class' => 'dim'],
        ));

        if ($summary['unpaid'] !== []) {
            $this->addUnpaidSection($summary, $customer);
        }

        if ($summary['unpaidReceived'] !== []) {
            $this->addUnpaidReceivedSection($summary);
        }

        if ($summary['pendingOrders'] !== []) {
            $this->addPendingOrdersSection($summary);
        }

        $this->addSummarySection($summary);

        $links = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-links']));
        $links->addItem(new \Ease\Html\ATag(
            'myinvoices.php',
            _('All invoices').' <span class="arrow">→</span>',
            ['class' => 'btn btn-line'],
        ));
        $links->addItem(new \Ease\Html\ATag(
            'myorders.php',
            _('All orders').' <span class="arrow">→</span>',
            ['class' => 'btn btn-line'],
        ));
        if (!empty($summary['isSupplierCustomer'])) {
            $links->addItem(new \Ease\Html\ATag(
                'myreceivedinvoices.php',
                _('All received invoices').' <span class="arrow">→</span>',
                ['class' => 'btn btn-line'],
            ));
        }
        $links->addItem(new \Ease\Html\ATag(
            'index.php#catalog',
            _('Browse catalog').' <span class="arrow">→</span>',
            ['class' => 'btn btn-glow'],
        ));
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function addUnpaidSection(array $summary, Customer $customer): void
    {
        $section = $this->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-dashboard-section cz-dashboard-alert',
            'id' => 'unpaid',
        ]));
        $head = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-section-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Action required'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Unpaid invoices')));
        $head->addItem(new \Ease\Html\PTag(
            sprintf(
                _('Outstanding balance: %s'),
                $this->money((float) $summary['unpaidTotal'], (string) $summary['currency']),
            ),
            ['class' => 'cz-dashboard-alert-total'],
        ));
        $head->addItem(new \Ease\Html\ATag(
            'myinvoices.php',
            _('All invoices').' <span class="arrow">→</span>',
            ['class' => 'cz-dashboard-section-link'],
        ));

        $list = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-list']));
        $today = new \DateTimeImmutable('today');

        foreach ($summary['unpaid'] as $invoice) {
            $id = (int) ($invoice['id'] ?? 0);
            $kod = (string) ($invoice['kod'] ?? '');
            $dueIso = Customer::dateToIso($invoice['datSplat'] ?? null);
            $dueLabel = Customer::formatDate($invoice['datSplat'] ?? null) ?? '—';
            $overdue = false;
            $overdueDays = 0;
            if ($dueIso !== null) {
                $due = new \DateTimeImmutable($dueIso);
                if ($due < $today) {
                    $overdue = true;
                    $overdueDays = (int) $due->diff($today)->days;
                }
            }

            $row = $list->addItem(new \Ease\Html\DivTag(null, [
                'class' => 'cz-dashboard-row'.($overdue ? ' is-overdue' : ''),
            ]));

            $main = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-main']));
            $main->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=faktura-vydana',
                $kod,
                ['class' => 'cz-dashboard-doc'],
            ));
            $meta = [];
            $meta[] = sprintf(_('Due %s'), $dueLabel);
            if ($overdue) {
                $meta[] = $overdueDays === 1
                    ? _('1 day overdue')
                    : sprintf(_('%d days overdue'), $overdueDays);
            }
            $main->addItem(new \Ease\Html\DivTag(implode(' · ', $meta), [
                'class' => 'cz-dashboard-meta',
            ]));

            $reminders = $this->reminderText($invoice);
            if ($reminders !== null) {
                $main->addItem(new \Ease\Html\DivTag($reminders, [
                    'class' => 'cz-dashboard-reminders',
                ]));
            }

            $aside = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-aside']));
            $amount = (float) ($invoice['zbyvaUhradit'] ?? $invoice['sumCelkem'] ?? 0);
            $mena = !empty($invoice['mena'])
                ? \AbraFlexi\Functions::uncode((string) $invoice['mena'])
                : (string) $summary['currency'];
            $aside->addItem(new \Ease\Html\DivTag(
                $this->money($amount, $mena === 'CZK' ? 'Kč' : $mena),
                ['class' => 'cz-dashboard-amount'],
            ));

            $actions = $aside->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-actions']));
            $actions->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=faktura-vydana',
                _('Show'),
                ['class' => 'btn btn-line btn-sm'],
            ));
            $actions->addItem(new \Ease\Html\ATag(
                'getpdf.php?id='.$id.'&evidence=faktura-vydana',
                _('Download'),
                ['class' => 'btn btn-line btn-sm'],
            ));

            // QR for overdue / payable items when invoicer is available
            if ($id > 0) {
                try {
                    $customer->getInvoicer()->setMyKey($id);
                    $qr = $customer->getInvoicer()->getQrCodeBase64(120);
                    if (\is_string($qr) && $qr !== '') {
                        $row->addItem(new \Ease\Html\ImgTag(
                            $qr,
                            sprintf(_('QR payment for %s'), $kod),
                            ['class' => 'cz-dashboard-qr'],
                        ));
                    }
                } catch (\Throwable) {
                    // QR is optional — never break the dashboard
                }
            }
        }
    }

    /**
     * Supplier role: invoices we issued that are still unpaid by the provider.
     *
     * @param array<string, mixed> $summary
     */
    private function addUnpaidReceivedSection(array $summary): void
    {
        $section = $this->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-dashboard-section cz-dashboard-alert cz-dashboard-received',
            'id' => 'unpaid-received',
        ]));
        $head = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-section-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Awaiting settlement'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Unpaid received invoices')));
        $head->addItem(new \Ease\Html\PTag(
            sprintf(
                _('Outstanding receivable: %s'),
                $this->money((float) $summary['unpaidReceivedTotal'], (string) $summary['currency']),
            ),
            ['class' => 'cz-dashboard-alert-total'],
        ));
        $head->addItem(new \Ease\Html\ATag(
            'myreceivedinvoices.php',
            _('All received invoices').' <span class="arrow">→</span>',
            ['class' => 'cz-dashboard-section-link'],
        ));

        $list = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-list']));
        $today = new \DateTimeImmutable('today');

        foreach ($summary['unpaidReceived'] as $invoice) {
            $id = (int) ($invoice['id'] ?? 0);
            $kod = (string) ($invoice['kod'] ?? '');
            $dueIso = Customer::dateToIso($invoice['datSplat'] ?? null);
            $dueLabel = Customer::formatDate($invoice['datSplat'] ?? null) ?? '—';
            $overdue = false;
            $overdueDays = 0;
            if ($dueIso !== null) {
                $due = new \DateTimeImmutable($dueIso);
                if ($due < $today) {
                    $overdue = true;
                    $overdueDays = (int) $due->diff($today)->days;
                }
            }

            $row = $list->addItem(new \Ease\Html\DivTag(null, [
                'class' => 'cz-dashboard-row'.($overdue ? ' is-overdue' : ''),
            ]));

            $main = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-main']));
            $main->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=faktura-prijata',
                $kod,
                ['class' => 'cz-dashboard-doc'],
            ));
            $meta = [];
            $meta[] = sprintf(_('Due %s'), $dueLabel);
            if ($overdue) {
                $meta[] = $overdueDays === 1
                    ? _('1 day overdue')
                    : sprintf(_('%d days overdue'), $overdueDays);
            }
            $main->addItem(new \Ease\Html\DivTag(implode(' · ', $meta), [
                'class' => 'cz-dashboard-meta',
            ]));

            $aside = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-aside']));
            $amount = (float) ($invoice['zbyvaUhradit'] ?? $invoice['sumCelkem'] ?? 0);
            $mena = !empty($invoice['mena'])
                ? \AbraFlexi\Functions::uncode((string) $invoice['mena'])
                : (string) $summary['currency'];
            $aside->addItem(new \Ease\Html\DivTag(
                $this->money($amount, $mena === 'CZK' ? 'Kč' : $mena),
                ['class' => 'cz-dashboard-amount'],
            ));

            $actions = $aside->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-actions']));
            $actions->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=faktura-prijata',
                _('Show'),
                ['class' => 'btn btn-line btn-sm'],
            ));
            $actions->addItem(new \Ease\Html\ATag(
                'getpdf.php?id='.$id.'&evidence=faktura-prijata',
                _('Download'),
                ['class' => 'btn btn-line btn-sm'],
            ));
        }
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function addPendingOrdersSection(array $summary): void
    {
        $section = $this->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-dashboard-section cz-dashboard-alert cz-dashboard-orders',
            'id' => 'pending-orders',
        ]));
        $head = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-section-head']));
        $head->addItem(new \Ease\Html\DivTag(_('In progress'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Open orders')));
        $pendingCount = count($summary['pendingOrders']);
        $head->addItem(new \Ease\Html\PTag(
            $pendingCount === 1
                ? _('1 order awaiting completion')
                : sprintf(_('%d orders awaiting completion'), $pendingCount),
            ['class' => 'dim'],
        ));
        $head->addItem(new \Ease\Html\ATag(
            'myorders.php',
            _('All orders').' <span class="arrow">→</span>',
            ['class' => 'cz-dashboard-section-link'],
        ));

        $list = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-list']));
        foreach ($summary['pendingOrders'] as $order) {
            $id = (int) ($order['id'] ?? 0);
            $kod = (string) ($order['kod'] ?? '');
            $row = $list->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row']));
            $main = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-main']));
            $main->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=objednavka-prijata',
                $kod,
                ['class' => 'cz-dashboard-doc'],
            ));
            $issued = Customer::formatDate($order['datVyst'] ?? null) ?? '—';
            $note = trim((string) ($order['popis'] ?? $order['poznam'] ?? ''));
            $main->addItem(new \Ease\Html\DivTag(
                sprintf(_('Issued %s'), $issued).($note !== '' ? ' · '.$note : ''),
                ['class' => 'cz-dashboard-meta'],
            ));

            $aside = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-row-aside']));
            $mena = !empty($order['mena'])
                ? \AbraFlexi\Functions::uncode((string) $order['mena'])
                : (string) $summary['currency'];
            $aside->addItem(new \Ease\Html\DivTag(
                $this->money((float) ($order['sumCelkem'] ?? 0), $mena === 'CZK' ? 'Kč' : $mena),
                ['class' => 'cz-dashboard-amount'],
            ));
            $aside->addItem(new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence=objednavka-prijata',
                _('Show'),
                ['class' => 'btn btn-line btn-sm'],
            ));
        }
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function addSummarySection(array $summary): void
    {
        $section = $this->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-dashboard-section cz-dashboard-summary',
        ]));
        $head = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-section-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Relationship'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('With us')));

        $counts = $section->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-dashboard-stats cz-dashboard-counts',
        ]));
        $documentCount = (int) $summary['documentCount'];
        $invoiceCount = (int) $summary['invoiceCount'];
        $orderCount = (int) $summary['orderCount'];
        $receivedCount = (int) $summary['receivedInvoiceCount'];
        $isSupplier = !empty($summary['isSupplierCustomer']);

        $this->addStat(
            $counts,
            _('Documents'),
            (string) $documentCount,
            $documentCount === 1
                ? _('1 document on record')
                : sprintf(_('%d documents on record'), $documentCount),
        );
        $this->addStat(
            $counts,
            _('Invoices'),
            (string) $invoiceCount,
            $invoiceCount === 1
                ? _('1 invoice issued')
                : sprintf(_('%d invoices issued'), $invoiceCount),
            'myinvoices.php',
        );
        $this->addStat(
            $counts,
            _('Orders'),
            (string) $orderCount,
            $orderCount === 1
                ? _('1 order issued')
                : sprintf(_('%d orders issued'), $orderCount),
            'myorders.php',
        );
        if ($isSupplier) {
            $this->addStat(
                $counts,
                _('Received invoices'),
                (string) $receivedCount,
                $receivedCount === 1
                    ? _('1 received invoice')
                    : sprintf(_('%d received invoices'), $receivedCount),
                'myreceivedinvoices.php',
            );
        }

        $grid = $section->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-stats']));

        $sinceValue = $summary['sinceLabel'] ?? _('not available');
        $years = '';
        if (!empty($summary['since'])) {
            $start = new \DateTimeImmutable((string) $summary['since']);
            $yearsN = (int) $start->diff(new \DateTimeImmutable('today'))->y;
            if ($yearsN === 1) {
                $years = ' · '._('1 year');
            } elseif ($yearsN > 1) {
                $years = ' · '.sprintf(_('%d years'), $yearsN);
            }
        }

        $this->addStat(
            $grid,
            _('Customer since'),
            $sinceValue.$years,
            _('First order or invoice on record'),
        );
        $this->addStat(
            $grid,
            _('Total spent'),
            $this->money((float) $summary['totalSpent'], (string) $summary['currency']),
            $invoiceCount === 1
                ? _('1 invoice')
                : sprintf(_('%d invoices'), $invoiceCount),
            'myinvoices.php',
        );
        if ($isSupplier) {
            $this->addStat(
                $grid,
                _('Total received'),
                $this->money((float) $summary['receivedTotal'], (string) $summary['currency']),
                $receivedCount === 1
                    ? _('1 received invoice')
                    : sprintf(_('%d received invoices'), $receivedCount),
                'myreceivedinvoices.php',
            );
        }
        $openItems = count($summary['unpaid'])
            + count($summary['pendingOrders'])
            + count($summary['unpaidReceived']);
        $openHint = sprintf(
            _('%d unpaid · %d open orders'),
            count($summary['unpaid']),
            count($summary['pendingOrders']),
        );
        if ($isSupplier && count($summary['unpaidReceived']) > 0) {
            $openHint .= ' · '.sprintf(
                _('%d unpaid received'),
                count($summary['unpaidReceived']),
            );
        }
        $this->addStat(
            $grid,
            _('Open items'),
            (string) $openItems,
            $openHint,
        );
    }

    private function addStat(
        \Ease\Html\DivTag $grid,
        string $label,
        string $value,
        string $hint,
        ?string $href = null,
    ): void {
        if ($href !== null && $href !== '') {
            $stat = $grid->addItem(new \Ease\Html\ATag($href, null, [
                'class' => 'cz-dashboard-stat cz-dashboard-stat-link',
            ]));
        } else {
            $stat = $grid->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-dashboard-stat']));
        }
        $stat->addItem(new \Ease\Html\DivTag($label, ['class' => 'cz-dashboard-stat-label']));
        $stat->addItem(new \Ease\Html\DivTag($value, ['class' => 'cz-dashboard-stat-value']));
        $stat->addItem(new \Ease\Html\DivTag($hint, ['class' => 'cz-dashboard-stat-hint dim']));
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function reminderText(array $invoice): ?string
    {
        $parts = [];
        $up1 = Customer::formatDate($invoice['datUp1'] ?? null);
        $up2 = Customer::formatDate($invoice['datUp2'] ?? null);
        if ($up1 !== null) {
            $parts[] = sprintf(_('1st reminder: %s'), $up1);
        }
        if ($up2 !== null) {
            $parts[] = sprintf(_('2nd reminder: %s'), $up2);
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2, ',', ' ').' '.$currency;
    }
}
