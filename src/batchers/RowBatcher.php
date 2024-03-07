<?php

namespace putyourlightson\campaign\batchers;

use craft\base\Batchable;

/**
 * @since 2.13.0
 */
class RowBatcher implements Batchable
{
    public function __construct(
        private array $rows,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * @inheritdoc
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        return array_slice($this->rows, $offset, $limit);
    }
}
