<?php

declare(strict_types=1);

/**
 * Shared DataTables helpers for AbraFlexi document listings.
 *
 * Mirrors MultiFlexi ApplicationLister: columns() + getAllForDataTable() + completeDataRow().
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

/**
 * @phpstan-type DataTableColumn array{name: string, type: string, label: string, searchable?: bool, hidden?: bool}
 */
trait DocumentLister
{
    /**
     * DataTables column definitions (ApplicationLister style).
     *
     * @param list<DataTableColumn> $columns
     *
     * @return list<DataTableColumn>
     */
    abstract public function columns($columns = []);

    /**
     * @return list<DataTableColumn>
     */
    public function getGetDataTableColumns(): array
    {
        return $this->columns();
    }

    /**
     * Map columns() to AbraFlexi DataTable / getOnlineColumnsInfo shape.
     *
     * @return array<string, array<string, string>>
     */
    public function getOnlineColumnsInfo($evidence = null): array
    {
        $properties = [];
        foreach ($this->columns() as $col) {
            $name = $col['name'];
            $properties[$name] = [
                'propertyName' => $name,
                'title' => $col['label'],
                'name' => $col['label'],
                'type' => ($col['type'] ?? 'text') === 'text' ? 'string' : (string) $col['type'],
            ];
        }

        return $properties;
    }

    /**
     * Fetch and format rows for DataTables (client-side mode).
     *
     * @param array<string, mixed> $conditions AbraFlexi filter (e.g. firma)
     *
     * @return array{data: list<array<string, mixed>>, recordsTotal: int, recordsFiltered: int}
     */
    public function getAllForDataTable(array $conditions = []): array
    {
        $columnNames = ['id'];
        foreach ($this->columns() as $col) {
            if (($col['hidden'] ?? false) === true) {
                continue;
            }
            if ($col['name'] === 'actions') {
                continue;
            }
            if (!\in_array($col['name'], $columnNames, true)) {
                $columnNames[] = $col['name'];
            }
        }

        foreach ($this->extraFetchColumns() as $extra) {
            if (!\in_array($extra, $columnNames, true)) {
                $columnNames[] = $extra;
            }
        }

        // limit=0 = all rows (AbraFlexi default is 20).
        $conditions = array_merge(['limit' => 0], $conditions);
        $raw = $this->getColumnsFromAbraFlexi($columnNames, $conditions);
        if (!\is_array($raw)) {
            $raw = [];
        }

        $data = [];
        foreach ($raw as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $data[] = $this->completeDataRow($row);
        }

        return [
            'data' => $data,
            'recordsTotal' => \count($data),
            'recordsFiltered' => \count($data),
        ];
    }

    /**
     * @return list<string>
     */
    protected function extraFetchColumns(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected function normalizeScalars(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value instanceof \AbraFlexi\Relation) {
                $row[$key] = (string) ($value->showAs ?: $value);
            } elseif ($value instanceof \AbraFlexi\Date) {
                $row[$key] = $value->isNull ? '' : $value->format('d.m.Y');
            } elseif ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('d.m.Y');
            }
        }

        return $row;
    }

    /**
     * Enrich a raw AbraFlexi row for HTML display in DataTables.
     *
     * @param array<string, mixed> $dataRowRaw
     *
     * @return array<string, mixed>
     */
    public function completeDataRow(array $dataRowRaw): array
    {
        return $this->normalizeScalars($dataRowRaw);
    }
}
