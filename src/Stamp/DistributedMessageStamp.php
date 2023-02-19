<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Stamp;

use Pandawa\Contracts\Bus\StampInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class DistributedMessageStamp implements StampInterface
{
    public function __construct(
        public readonly ?string $produceKey = null,
        public readonly ?string $produceTopic = null,
        public readonly ?array $versions = null,
    ) {
    }
}
