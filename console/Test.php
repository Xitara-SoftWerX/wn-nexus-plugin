<?php

namespace Xitara\Nexus\Console;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'nexus:test';

    /**
     * @var string The console command description.
     */
    protected $description = 'No description provided yet...';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->output->writeln('Hello world!');

        $section1 = $this->output->section('1');
        $section2 = $this->output->section('2');

        $progress1 = $this->output->createProgressBar($section1);
        $progress2 = $this->output->createProgressBar($section2);

        $progress1->start(100);
        $progress2->start(100);

        $i = 0;
        while (++$i < 100) {
            $progress1->advance();

            if ($i % 2 === 0) {
                $progress2->advance(4);
            }

            usleep(50000);
        }

        // $bar1 = $this->createProgressBar($this->output->section(), 10);
        // $bar2 = $this->createProgressBar($this->output->section(), 20);
        // $bar2->setProgressCharacter('#');
        // $bar1->start();
        // print "\n";
        // $bar2->start();

        // for ($i = 1; $i <= 20; $i++) {
        //     // up one line
        //     $this->output->write("\033[1A");
        //     usleep(100000);
        //     if ($i <= 10) {
        //         $bar1->advance();
        //     }
        //     print "\n";
        //     $bar2->advance();
        // }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
