<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

use Illuminate\Contracts\Events\Dispatcher;
use Pandawa\Arjuna\Event\WorkerPaused;
use Pandawa\Arjuna\Event\WorkerPlaying;
use Pandawa\Arjuna\Event\WorkerQuit;
use Pandawa\Arjuna\Event\WorkerResumed;
use Pandawa\Arjuna\Event\WorkerStopped;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class WorkerPlayer
{
    protected $shouldQuit = false;
    protected $paused = false;
    protected $event;
    protected $process;
    protected $options;

    /**
     * Constructor.
     *
     * @param Dispatcher    $event
     * @param WorkerOptions $options
     * @param callable      $process
     */
    public function __construct(Dispatcher $event, WorkerOptions $options, callable $process)
    {
        $this->event = $event;
        $this->options = $options;
        $this->process = $process;
    }

    public function play(): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $this->event->dispatch(new WorkerPlaying($this->options->getBroker(), $this->options->getTopics()));

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

        $this->event->dispatch(new WorkerPaused($this->options->getBroker(), $this->options->getTopics()));
    }

    public function resume(): void
    {
        $this->paused = false;

        $this->event->dispatch(new WorkerResumed($this->options->getBroker(), $this->options->getTopics()));
    }

    public function stop(): void
    {
        $this->shouldQuit = true;

        $this->event->dispatch(new WorkerStopped($this->options->getBroker(), $this->options->getTopics()));
    }

    public function quit(int $status = 0): void
    {
        $this->event->dispatch(new WorkerQuit($this->options->getBroker(), $this->options->getTopics()));
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
