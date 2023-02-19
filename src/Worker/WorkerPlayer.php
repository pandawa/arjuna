<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

use Pandawa\Arjuna\Event\WorkerPaused;
use Pandawa\Arjuna\Event\WorkerPlaying;
use Pandawa\Arjuna\Event\WorkerQuit;
use Pandawa\Arjuna\Event\WorkerResumed;
use Pandawa\Arjuna\Event\WorkerStopped;
use Pandawa\Contracts\Event\EventBusInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class WorkerPlayer
{
    protected bool $shouldQuit = false;

    protected bool $paused = false;

    /**
     * @var callable
     */
    protected $process;

    public function __construct(
        protected readonly EventBusInterface $eventBus,
        protected readonly WorkerOptions $options,
        callable $process
    ) {
        $this->process = $process;
    }

    public function play(): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $this->eventBus->fire(new WorkerPlaying($this->options->broker, $this->options->topics));

        while(true) {
            if ($this->paused) {
                $this->sleep(1);

                continue;
            }

            call_user_func($this->process, $this, $this->options);

            $this->stopIfNecessary();
        }
    }

    public function sleep($seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    public function pause(): void
    {
        $this->paused = true;

        $this->eventBus->fire(new WorkerPaused($this->options->broker, $this->options->topics));
    }

    public function resume(): void
    {
        $this->paused = false;

        $this->eventBus->fire(new WorkerResumed($this->options->broker, $this->options->topics));
    }

    public function stop(): void
    {
        $this->shouldQuit = true;

        $this->eventBus->fire(new WorkerStopped($this->options->broker, $this->options->topics));
    }

    public function quit(int $status = 0): void
    {
        $this->eventBus->fire(new WorkerQuit($this->options->broker, $this->options->topics));
        exit($status);
    }

    protected function stopIfNecessary(): void
    {
        if ($this->shouldQuit) {
            $this->quit();
        }
    }

    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->stop();
        });

        pcntl_signal(SIGUSR2, function () {
            $this->pause();;
        });

        pcntl_signal(SIGCONT, function () {
            $this->resume();
        });
    }

    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }
}
